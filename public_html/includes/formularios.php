<?php
/**
 * Formularios públicos (Fase 3): validación, anti-spam y envío por SMTP.
 *
 * Lo usa api/enviar.php. Las credenciales (SMTP_*, TURNSTILE_*) viven en
 * config.local.php; ver config.local.example.php.
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

const ENVIOS_MAX = 5;              // envíos permitidos por IP…
const ENVIOS_VENTANA = 1800;       // …en esta ventana (segundos)

/**
 * Campos de cada formulario: tipo, si es obligatorio, largo máximo y etiqueta
 * para el correo. Deben coincidir con los name="" de contacto.php y
 * ofrezca-su-propiedad.php.
 */
function definicion_formularios(): array
{
    return [
        'contacto' => [
            'titulo' => 'Contacto',
            'campos' => [
                'nombre'   => ['etiqueta' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'max' => 120],
                'email'    => ['etiqueta' => 'Correo', 'tipo' => 'email', 'requerido' => true, 'max' => 180],
                'telefono' => ['etiqueta' => 'Teléfono / WhatsApp', 'tipo' => 'telefono', 'requerido' => false, 'max' => 30],
                'asunto'   => ['etiqueta' => 'Me interesa', 'tipo' => 'opcion', 'opciones' => ASUNTOS_CONTACTO, 'requerido' => false],
                'mensaje'  => ['etiqueta' => 'Mensaje', 'tipo' => 'area', 'requerido' => true, 'max' => 4000],
            ],
        ],
        'propiedad' => [
            'titulo' => 'Ofrezca su propiedad',
            'campos' => [
                'tipo_propiedad' => ['etiqueta' => 'Tipo de propiedad', 'tipo' => 'opcion', 'opciones' => TIPOS_PROPIEDAD, 'requerido' => true],
                'operacion'      => ['etiqueta' => 'Tipo de operación', 'tipo' => 'opcion', 'opciones' => OPERACIONES, 'requerido' => true],
                'descripcion'    => ['etiqueta' => 'Descripción', 'tipo' => 'area', 'requerido' => false, 'max' => 4000],
                'nombre'         => ['etiqueta' => 'Nombre', 'tipo' => 'texto', 'requerido' => true, 'max' => 120],
                'email'          => ['etiqueta' => 'Correo', 'tipo' => 'email', 'requerido' => false, 'max' => 180],
                'whatsapp'       => ['etiqueta' => 'WhatsApp', 'tipo' => 'telefono', 'requerido' => true, 'max' => 30],
            ],
        ],
    ];
}

/**
 * Limpia y valida el POST de un formulario.
 * Devuelve [datos limpios, lista de errores en texto para el visitante].
 */
function validar_envio(string $formulario, array $post): array
{
    $def = definicion_formularios()[$formulario] ?? null;
    if ($def === null) {
        return [[], ['Formulario desconocido.']];
    }
    $datos = [];
    $errores = [];
    foreach ($def['campos'] as $nombre => $c) {
        $valor = is_string($post[$nombre] ?? null) ? $post[$nombre] : '';
        // Quita caracteres de control (salvo saltos de línea en áreas) y espacios sobrantes
        $valor = preg_replace($c['tipo'] === 'area' ? '/[^\P{C}\n]/u' : '/\p{C}/u', '', str_replace("\r\n", "\n", $valor)) ?? '';
        $valor = trim($valor);

        if ($valor === '') {
            if ($c['requerido']) {
                $errores[] = $c['etiqueta'] . ' es obligatorio.';
            }
            $datos[$nombre] = '';
            continue;
        }
        if (isset($c['max']) && mb_strlen($valor) > $c['max']) {
            $errores[] = $c['etiqueta'] . ' es demasiado largo.';
        }
        $ok = match ($c['tipo']) {
            'email'    => filter_var($valor, FILTER_VALIDATE_EMAIL) !== false,
            'telefono' => (bool) preg_match('/^[0-9 +()\-]{10,30}$/', $valor),
            'opcion'   => in_array($valor, $c['opciones'], true),
            default    => true,
        };
        if (!$ok) {
            $errores[] = $c['etiqueta'] . ' no es válido.';
        }
        $datos[$nombre] = $valor;
    }
    return [$datos, $errores];
}

/** IP del visitante. SiteGround pasa la IP real en REMOTE_ADDR. */
function ip_visitante(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

/**
 * Límite de envíos por IP (guarda solo un hash de la IP, no la IP).
 * Registra el intento y devuelve false si ya se pasó del límite.
 */
function registrar_envio_permitido(): bool
{
    $dir = DIR_DATA . '/seguridad';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $fp = fopen($dir . '/envios-formularios.json', 'c+');
    if ($fp === false) {
        return true; // si no se puede registrar, no bloquear a personas reales
    }
    flock($fp, LOCK_EX);
    $d = json_decode((string) stream_get_contents($fp), true);
    $d = is_array($d) ? $d : [];
    $ahora = time();
    // Descarta registros viejos de todas las IP
    foreach ($d as $k => $marcas) {
        $d[$k] = array_values(array_filter((array) $marcas, fn($t) => is_int($t) && $t > $ahora - ENVIOS_VENTANA));
        if (!$d[$k]) {
            unset($d[$k]);
        }
    }
    $clave = hash('sha256', ip_visitante());
    $permitido = count($d[$clave] ?? []) < ENVIOS_MAX;
    if ($permitido) {
        $d[$clave][] = $ahora;
    }
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($d));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $permitido;
}

/** ¿Está configurado Turnstile? (ambas claves en config.local.php) */
function turnstile_activo(): bool
{
    return defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY !== ''
        && defined('TURNSTILE_SECRET') && TURNSTILE_SECRET !== '';
}

/** Comprueba con Cloudflare el token que dejó el widget en el formulario. */
function verificar_turnstile(string $token): bool
{
    if (!turnstile_activo()) {
        // Sin claves solo se permite en desarrollo; en producción se rechaza.
        return MODO_DEV;
    }
    if ($token === '' || strlen($token) > 2048) {
        return false;
    }
    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => TURNSTILE_SECRET,
            'response' => $token,
            'remoteip' => ip_visitante(),
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $respuesta = curl_exec($ch);
    curl_close($ch);
    if (!is_string($respuesta)) {
        error_log('Turnstile: sin respuesta de Cloudflare');
        return false;
    }
    $json = json_decode($respuesta, true);
    return is_array($json) && ($json['success'] ?? false) === true;
}

/** ¿Están todas las constantes SMTP? */
function smtp_activo(): bool
{
    foreach (['SMTP_HOST', 'SMTP_PUERTO', 'SMTP_USUARIO', 'SMTP_CLAVE', 'SMTP_DESTINO'] as $c) {
        if (!defined($c) || (string) constant($c) === '') {
            return false;
        }
    }
    return true;
}

/**
 * Arma y envía el correo a SMTP_DESTINO. El remitente es la cuenta SMTP y,
 * si el visitante dejó correo, se usa como Reply-To para contestarle directo.
 */
function enviar_correo(string $formulario, array $datos): bool
{
    $def = definicion_formularios()[$formulario];
    $asunto = '[Sitio web] ' . $def['titulo'] . ' — ' . $datos['nombre'];

    $texto = "Nuevo mensaje desde el formulario «{$def['titulo']}» de " . URL_SITIO . "\n\n";
    $html = '<p>Nuevo mensaje desde el formulario <strong>' . e($def['titulo']) . '</strong> de ' . e(URL_SITIO) . '</p>'
        . '<table cellpadding="6" style="border-collapse:collapse;font-family:Arial,sans-serif;font-size:14px">';
    foreach ($def['campos'] as $nombre => $c) {
        $valor = $datos[$nombre] !== '' ? $datos[$nombre] : '—';
        $texto .= $c['etiqueta'] . ":\n" . $valor . "\n\n";
        $html .= '<tr><th align="left" valign="top" style="border-bottom:1px solid #ddd">' . e($c['etiqueta']) . '</th>'
            . '<td style="border-bottom:1px solid #ddd">' . nl2br(e($valor)) . '</td></tr>';
    }
    $html .= '</table>';
    $pie = 'Enviado el ' . date('d/m/Y H:i');
    $texto .= "—\n" . $pie;
    $html .= '<p style="color:#777;font-size:12px">' . e($pie) . '</p>';

    if (!smtp_activo()) {
        if (!MODO_DEV) {
            error_log('Formularios: faltan las constantes SMTP_* en config.local.php');
            return false;
        }
        // En local sin SMTP: el correo se guarda en data/correos-dev/ para revisarlo
        $dir = DIR_DATA . '/correos-dev';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $archivo = $dir . '/' . date('Ymd-His') . '-' . $formulario . '-' . bin2hex(random_bytes(3)) . '.txt';
        return file_put_contents($archivo, 'Asunto: ' . $asunto . "\n\n" . $texto) !== false;
    }

    require_once __DIR__ . '/vendor/phpmailer/Exception.php';
    require_once __DIR__ . '/vendor/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/vendor/phpmailer/SMTP.php';

    $correo = new PHPMailer(true);
    try {
        $correo->isSMTP();
        $correo->Host       = SMTP_HOST;
        $correo->Port       = (int) SMTP_PUERTO;
        $correo->SMTPAuth   = true;
        $correo->Username   = SMTP_USUARIO;
        $correo->Password   = SMTP_CLAVE;
        $correo->SMTPSecure = (int) SMTP_PUERTO === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $correo->Timeout    = 15;
        $correo->CharSet    = PHPMailer::CHARSET_UTF8;

        $correo->setFrom(SMTP_USUARIO, 'Sitio web ' . NOMBRE_SITIO);
        $correo->addAddress(SMTP_DESTINO);
        if ($datos['email'] !== '') {
            $correo->addReplyTo($datos['email'], $datos['nombre']);
        }
        $correo->Subject = $asunto;
        $correo->isHTML(true);
        $correo->Body    = $html;
        $correo->AltBody = $texto;
        $correo->send();
        return true;
    } catch (PHPMailerException $ex) {
        error_log('Formularios: no se pudo enviar — ' . $correo->ErrorInfo);
        return false;
    }
}
