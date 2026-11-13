<?php
/**
 * Cuenta del panel (un solo usuario): contraseña, correo y enlaces por correo.
 *
 * Los datos viven en data/seguridad/cuenta.json (fuera de la web):
 *   usuario, hash, clave_ts (cuándo cambió la contraseña; cierra las demás sesiones),
 *   correo (ya confirmado), pendiente {correo, token, expira}, restablecer {token, expira},
 *   envios [marcas de tiempo de los correos mandados, para limitar el abuso].
 * De los enlaces solo se guarda el sha256 del token, nunca el token.
 *
 * Mientras ese archivo no exista se usan ADMIN_USUARIO y ADMIN_HASH de config.local.php;
 * la cuenta se pasa sola al archivo la primera vez que algo cambia. Si se pierde el acceso
 * y el correo no funciona: borrar cuenta.json en el servidor y se vuelve a config.local.php.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/formularios.php';

const CLAVE_MIN = 10;                       // largo mínimo de la contraseña
const CLAVE_MAX = 72;                       // bcrypt ignora lo que pase de 72 bytes
const CONFIRMAR_VIGENCIA = 24 * 3600;       // el enlace para confirmar el correo dura 24 h
const RESTABLECER_VIGENCIA = 30 * 60;       // el de restablecer contraseña, 30 min
const CORREOS_MAX_HORA = 5;                 // correos de la cuenta por hora…
const CORREOS_ESPERA = 60;                  // …y al menos 1 min entre uno y otro

function ruta_cuenta(): string
{
    return DIR_DATA . '/seguridad/cuenta.json';
}

/** Cuenta inicial tomada de config.local.php (o vacía si no hay). */
function cuenta_inicial(): array
{
    return [
        'usuario'  => defined('ADMIN_USUARIO') ? (string) ADMIN_USUARIO : '',
        'hash'     => defined('ADMIN_HASH') ? (string) ADMIN_HASH : '',
        'clave_ts' => 0,
        'correo'   => '',
    ];
}

/** Datos de la cuenta (del archivo o, si aún no existe, de config.local.php). */
function cuenta(bool $releer = false): array
{
    static $cache = null;
    if ($cache !== null && !$releer) {
        return $cache;
    }
    $datos = is_file(ruta_cuenta()) ? json_decode((string) file_get_contents(ruta_cuenta()), true) : null;
    return $cache = (is_array($datos) && !empty($datos['usuario']) && !empty($datos['hash'])) ? $datos : cuenta_inicial();
}

/**
 * Lee, modifica y guarda la cuenta con bloqueo de archivo. $fn recibe la cuenta y
 * devuelve [cuenta nueva, resultado]. Si devuelve la cuenta en null no se escribe nada.
 */
function cuenta_modificar(callable $fn): mixed
{
    $ruta = ruta_cuenta();
    if (!is_dir(dirname($ruta))) {
        mkdir(dirname($ruta), 0750, true);
    }
    $fp = fopen($ruta, 'c+');
    if (!$fp) {
        throw new RuntimeException('No se pudo abrir el archivo de la cuenta.');
    }
    flock($fp, LOCK_EX);
    $datos = json_decode((string) stream_get_contents($fp), true);
    if (!is_array($datos) || empty($datos['usuario']) || empty($datos['hash'])) {
        $datos = cuenta_inicial();
    }
    [$nueva, $resultado] = $fn($datos);
    if (is_array($nueva)) {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($nueva, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fp);
    } elseif (fstat($fp)['size'] === 0) {
        // No había archivo y no hubo cambios: no se deja uno vacío
        flock($fp, LOCK_UN);
        fclose($fp);
        @unlink($ruta);
        return $resultado;
    }
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($ruta, 0640);
    cuenta(true);   // lo que se lea después en esta petición ya trae el cambio
    return $resultado;
}

// --- Contraseña -------------------------------------------------------------------

/** Reglas que se muestran junto al campo de contraseña nueva. */
function reglas_clave(): array
{
    return [
        'Al menos ' . CLAVE_MIN . ' caracteres.',
        'Una mayúscula, una minúscula y un número.',
        'Que no incluya tu usuario ni la palabra «duan».',
    ];
}

/** Devuelve null si la contraseña nueva cumple las reglas o el mensaje de lo que falta. */
function validar_clave_nueva(string $clave, string $repetida, string $usuario): ?string
{
    if ($clave !== $repetida) {
        return 'Las dos contraseñas nuevas no coinciden.';
    }
    if (mb_strlen($clave) < CLAVE_MIN) {
        return 'La contraseña debe tener al menos ' . CLAVE_MIN . ' caracteres.';
    }
    if (strlen($clave) > CLAVE_MAX) {
        return 'La contraseña es demasiado larga (máximo ' . CLAVE_MAX . ' caracteres).';
    }
    if (!preg_match('/\p{Lu}/u', $clave) || !preg_match('/\p{Ll}/u', $clave) || !preg_match('/\d/', $clave)) {
        return 'La contraseña debe llevar al menos una mayúscula, una minúscula y un número.';
    }
    $minus = mb_strtolower($clave);
    if (str_contains($minus, 'duan') || str_contains($minus, "du'an")
        || ($usuario !== '' && str_contains($minus, mb_strtolower($usuario)))) {
        return 'La contraseña no puede incluir tu usuario ni la palabra «duan».';
    }
    return null;
}

/** Guarda una contraseña nueva (ya validada), cierra las demás sesiones y anula los enlaces de restablecer. */
function cuenta_guardar_clave(string $clave): void
{
    $ts = time();
    cuenta_modificar(function (array $c) use ($clave, $ts) {
        $c['hash'] = password_hash($clave, PASSWORD_DEFAULT);
        $c['clave_ts'] = $ts;
        unset($c['restablecer']);
        return [$c, null];
    });
    // La sesión actual (si la hay) sigue viva; las demás dejan de coincidir con clave_ts
    if (!empty($_SESSION['admin'])) {
        $_SESSION['admin']['clave_ts'] = $ts;
    }
}

// --- Correos de la cuenta ---------------------------------------------------------

/** Token nuevo para un enlace: [token que va en el correo, hash que se guarda]. */
function token_enlace(): array
{
    $token = bin2hex(random_bytes(32));
    return [$token, hash('sha256', $token)];
}

/** Aparta un envío de correo si no se pasó el límite (devuelve la cuenta actualizada o null). */
function apartar_envio(array $c): ?array
{
    $ahora = time();
    $envios = array_values(array_filter($c['envios'] ?? [], fn($t) => is_int($t) && $ahora - $t < 3600));
    if (count($envios) >= CORREOS_MAX_HORA || ($envios && $ahora - max($envios) < CORREOS_ESPERA)) {
        return null;
    }
    $envios[] = $ahora;
    $c['envios'] = $envios;
    return $c;
}

/** Correo sencillo del panel: un párrafo, un botón con el enlace y una nota. */
function correo_panel(string $para, string $asunto, string $parrafo, ?string $enlace, string $textoBoton, string $nota, string $etiqueta): bool
{
    $texto = $parrafo . "\n\n" . ($enlace ? $enlace . "\n\n" : '') . $nota . "\n\n— Panel de " . NOMBRE_SITIO;
    $html = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#282d35;max-width:520px">'
        . '<p>' . e($parrafo) . '</p>'
        . ($enlace
            ? '<p><a href="' . e($enlace) . '" style="display:inline-block;background:#e21c21;color:#fff;padding:12px 22px;border-radius:8px;text-decoration:none;font-weight:bold">' . e($textoBoton) . '</a></p>'
              . '<p style="font-size:13px;color:#555">Si el botón no abre, copia esta dirección en tu navegador:<br>' . e($enlace) . '</p>'
            : '')
        . '<p style="font-size:13px;color:#555">' . e($nota) . '</p>'
        . '<p style="font-size:12px;color:#777">— Panel de ' . e(NOMBRE_SITIO) . '</p></div>';
    return mandar_correo($para, $asunto, $texto, $html, null, $etiqueta);
}

/**
 * Deja un correo pendiente de confirmar y manda el enlace a esa dirección.
 * Devuelve null si se envió o el mensaje de error.
 */
function cuenta_pedir_confirmacion(string $correo): ?string
{
    [$token, $hash] = token_enlace();
    $ok = cuenta_modificar(function (array $c) use ($correo, $hash) {
        $c = apartar_envio($c);
        if ($c === null) {
            return [null, false];
        }
        $c['pendiente'] = ['correo' => $correo, 'token' => $hash, 'expira' => time() + CONFIRMAR_VIGENCIA];
        return [$c, true];
    });
    if (!$ok) {
        return 'Se han pedido varios correos seguidos. Espera unos minutos y vuelve a intentar.';
    }
    $enviado = correo_panel(
        $correo,
        'Confirma tu correo del panel de ' . NOMBRE_SITIO,
        'Alguien registró esta dirección como el correo del panel de contenido de ' . NOMBRE_SITIO . '. Para confirmarla, abre este enlace:',
        url_absoluta('/admin/confirmar-correo.php?t=' . $token),
        'Confirmar mi correo',
        'El enlace vence en 24 horas. Si no fuiste tú, ignora este mensaje: no cambia nada.',
        'panel-confirmar'
    );
    return $enviado ? null : 'No se pudo enviar el correo de confirmación. Intenta de nuevo más tarde.';
}

/** Confirma el correo pendiente con el token del enlace. Devuelve el correo confirmado o null. */
function cuenta_confirmar_correo(string $token): ?string
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
        return null;
    }
    $hash = hash('sha256', $token);
    return cuenta_modificar(function (array $c) use ($hash) {
        $p = $c['pendiente'] ?? null;
        if (!is_array($p) || !hash_equals((string) ($p['token'] ?? ''), $hash) || time() > (int) ($p['expira'] ?? 0)) {
            return [null, null];
        }
        $c['correo'] = (string) $p['correo'];
        // Un enlace de restablecer mandado al correo anterior deja de servir
        unset($c['pendiente'], $c['restablecer']);
        return [$c, $c['correo']];
    });
}

/**
 * «Olvidé mi contraseña»: si el dato coincide con el correo confirmado (o el usuario),
 * manda el enlace. No dice si coincidió, para no revelar cuál es el correo del panel.
 */
function cuenta_pedir_restablecer(string $dato): void
{
    $dato = mb_strtolower(trim($dato));
    [$token, $hash] = token_enlace();
    $correo = cuenta_modificar(function (array $c) use ($dato, $hash) {
        $correo = (string) ($c['correo'] ?? '');
        $coincide = $correo !== '' && $dato !== ''
            && (hash_equals(mb_strtolower($correo), $dato) || hash_equals(mb_strtolower((string) $c['usuario']), $dato));
        if (!$coincide) {
            return [null, null];
        }
        $c = apartar_envio($c);
        if ($c === null) {
            return [null, null];
        }
        $c['restablecer'] = ['token' => $hash, 'expira' => time() + RESTABLECER_VIGENCIA];
        return [$c, $correo];
    });
    if ($correo === null) {
        usleep(random_int(300000, 700000));   // tarda parecido aunque no se mande nada
        return;
    }
    correo_panel(
        $correo,
        'Restablece tu contraseña del panel de ' . NOMBRE_SITIO,
        'Recibimos una solicitud para restablecer la contraseña del panel de contenido de ' . NOMBRE_SITIO . '. Para elegir una nueva, abre este enlace:',
        url_absoluta('/admin/restablecer.php?t=' . $token),
        'Elegir contraseña nueva',
        'El enlace vence en 30 minutos y solo sirve una vez. Si no lo pediste, ignora este mensaje: tu contraseña sigue igual.',
        'panel-restablecer'
    );
}

/** true si el token de restablecer es el vigente. */
function cuenta_token_restablecer_valido(string $token): bool
{
    if (!preg_match('/^[0-9a-f]{64}$/', $token)) {
        return false;
    }
    $r = cuenta()['restablecer'] ?? null;
    return is_array($r) && hash_equals((string) ($r['token'] ?? ''), hash('sha256', $token)) && time() <= (int) ($r['expira'] ?? 0);
}

/** Avisa al correo confirmado que la contraseña cambió (por si no fue la persona dueña). */
function cuenta_avisar_cambio_clave(): void
{
    $correo = (string) (cuenta()['correo'] ?? '');
    if ($correo === '') {
        return;
    }
    correo_panel(
        $correo,
        'Se cambió la contraseña del panel de ' . NOMBRE_SITIO,
        'La contraseña del panel de contenido de ' . NOMBRE_SITIO . ' se cambió el ' . date('d/m/Y') . ' a las ' . date('H:i') . '.',
        null,
        '',
        'Si no fuiste tú, entra a ' . url_absoluta('/admin/recuperar.php') . ' para restablecerla y avisa a quien administra el sitio.',
        'panel-aviso'
    );
}

/** Oculta parte del correo para mostrarlo en pantalla: ma•••@dominio.com */
function correo_oculto(string $correo): string
{
    [$nombre, $dominio] = array_pad(explode('@', $correo, 2), 2, '');
    return mb_substr($nombre, 0, 2) . '•••@' . $dominio;
}
