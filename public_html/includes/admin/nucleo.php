<?php
/**
 * Núcleo del panel /admin: sesión segura, login, CSRF y avisos.
 *
 * Toda página del panel empieza con:
 *     require dirname(__DIR__) . '/includes/admin/nucleo.php';
 *     requerir_login();
 *
 * Credenciales en config.local.php (no se versiona):
 *     define('ADMIN_USUARIO', 'editor');
 *     define('ADMIN_HASH', '...');   // php -r "echo password_hash('...', PASSWORD_DEFAULT);"
 */

declare(strict_types=1);

require dirname(__DIR__) . '/config.php';
require __DIR__ . '/almacen.php';
require __DIR__ . '/imagenes.php';
require __DIR__ . '/formulario.php';
require __DIR__ . '/plantilla.php';

const ADMIN_INACTIVIDAD = 2 * 3600;     // cierra la sesión tras 2 h sin uso
const ADMIN_DURACION_MAX = 12 * 3600;   // y siempre tras 12 h
const LOGIN_MAX_INTENTOS = 5;           // intentos fallidos permitidos…
const LOGIN_VENTANA = 15 * 60;          // …en 15 minutos (luego se bloquea ese mismo tiempo)

// --- Cabeceras: nada del panel se indexa, se cachea ni se incrusta en otro sitio ----
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https://i.ytimg.com; "
    . "media-src 'self' blob:; frame-src 'self'; script-src 'self'; style-src 'self'; object-src 'none'; "
    . "base-uri 'none'; form-action 'self'; frame-ancestors 'self'");

// --- Sesión ------------------------------------------------------------------------
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name($https ? '__Host-duan_admin' : 'duan_admin');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',          // __Host- exige path "/"
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

/** true si hay una sesión de editor válida (y la renueva). */
function sesion_activa(): bool
{
    $s = $_SESSION['admin'] ?? null;
    if (!is_array($s)) {
        return false;
    }
    $ahora = time();
    if ($ahora - ($s['ultimo'] ?? 0) > ADMIN_INACTIVIDAD || $ahora - ($s['inicio'] ?? 0) > ADMIN_DURACION_MAX) {
        cerrar_sesion();
        return false;
    }
    $_SESSION['admin']['ultimo'] = $ahora;
    return true;
}

/** Corta la página si no hay sesión: redirige al login (o responde 401 a peticiones fetch). */
function requerir_login(): void
{
    if (sesion_activa()) {
        return;
    }
    if (es_peticion_fetch()) {
        responder_json(['ok' => false, 'error' => 'Tu sesión expiró. Vuelve a iniciar sesión.'], 401);
    }
    $volver = $_SERVER['REQUEST_URI'] ?? '/admin/';
    header('Location: /admin/login.php?volver=' . rawurlencode($volver));
    exit;
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600] + array_intersect_key($p, array_flip(['path', 'domain', 'secure', 'httponly', 'samesite'])));
    }
    session_destroy();
}

/** true si el panel tiene usuario y contraseña configurados. */
function panel_configurado(): bool
{
    return defined('ADMIN_USUARIO') && defined('ADMIN_HASH') && ADMIN_USUARIO !== '' && ADMIN_HASH !== '';
}

// --- Límite de intentos de login (por IP, guardado en data/seguridad/) ------------

function ruta_intentos(): string
{
    return DIR_DATA . '/seguridad/intentos-login.json';
}

/** Lee, modifica y guarda el registro de intentos con bloqueo de archivo. */
function con_intentos(callable $fn): mixed
{
    $ruta = ruta_intentos();
    if (!is_dir(dirname($ruta))) {
        @mkdir(dirname($ruta), 0750, true);
    }
    $fp = fopen($ruta, 'c+');
    if (!$fp) {
        return $fn([])[1];
    }
    flock($fp, LOCK_EX);
    $datos = json_decode((string) stream_get_contents($fp), true);
    $datos = is_array($datos) ? $datos : [];
    // Limpia registros viejos
    $datos = array_filter($datos, fn($r) => is_array($r) && time() - ($r['desde'] ?? 0) < LOGIN_VENTANA * 2);
    [$datos, $resultado] = $fn($datos);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($datos));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $resultado;
}

function clave_ip(): string
{
    return hash('sha256', 'duan|' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
}

/** Segundos que faltan para poder volver a intentar (0 = puede intentar). */
function login_bloqueado(): int
{
    $ip = clave_ip();
    return con_intentos(function (array $d) use ($ip) {
        $r = $d[$ip] ?? null;
        if (!$r || $r['fallos'] < LOGIN_MAX_INTENTOS) {
            return [$d, 0];
        }
        return [$d, max(0, ($r['ultimo'] + LOGIN_VENTANA) - time())];
    });
}

function registrar_fallo_login(): void
{
    $ip = clave_ip();
    con_intentos(function (array $d) use ($ip) {
        $r = $d[$ip] ?? ['fallos' => 0, 'desde' => time()];
        $r['fallos']++;
        $r['ultimo'] = time();
        $d[$ip] = $r;
        return [$d, null];
    });
}

function limpiar_intentos_login(): void
{
    $ip = clave_ip();
    con_intentos(function (array $d) use ($ip) {
        unset($d[$ip]);
        return [$d, null];
    });
}

/** Intenta iniciar sesión. Devuelve null si entró o el mensaje de error. */
function iniciar_sesion(string $usuario, string $clave): ?string
{
    if (!panel_configurado()) {
        return 'El panel aún no tiene usuario configurado. Revisa config.local.php.';
    }
    if ($espera = login_bloqueado()) {
        return 'Demasiados intentos fallidos. Espera ' . (int) ceil($espera / 60) . ' min y vuelve a intentar.';
    }
    // Se verifica siempre la contraseña (aunque el usuario no coincida) para no revelar cuál falló.
    $okClave = password_verify($clave, ADMIN_HASH);
    $okUsuario = hash_equals(mb_strtolower(ADMIN_USUARIO), mb_strtolower(trim($usuario)));
    if (!$okClave || !$okUsuario) {
        registrar_fallo_login();
        usleep(random_int(300000, 700000));
        return 'Usuario o contraseña incorrectos.';
    }
    limpiar_intentos_login();
    session_regenerate_id(true);
    $_SESSION = ['admin' => ['usuario' => ADMIN_USUARIO, 'inicio' => time(), 'ultimo' => time()]];
    // Si el hash usa un algoritmo viejo, avisa en el log para regenerarlo.
    if (password_needs_rehash(ADMIN_HASH, PASSWORD_DEFAULT)) {
        error_log('[duan] ADMIN_HASH usa un algoritmo antiguo; genera uno nuevo.');
    }
    return null;
}

// --- CSRF ---------------------------------------------------------------------------

function token_csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campo_csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(token_csrf()) . '">';
}

/** Corta la petición si no es POST con un token CSRF válido. */
function verificar_csrf(): void
{
    // Si el envío supera post_max_size, PHP descarta todo $_POST: no es un problema de token.
    if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0 && es_peticion_fetch()) {
        responder_json(['ok' => false, 'error' => 'El archivo pesa demasiado para el servidor.'], 413);
    }
    $token = (string) ($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF'] ?? '');
    $origen = $_SERVER['HTTP_ORIGIN'] ?? null;
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $origenOk = $origen === null || parse_url($origen, PHP_URL_HOST) . (parse_url($origen, PHP_URL_PORT) ? ':' . parse_url($origen, PHP_URL_PORT) : '') === $host;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !$origenOk
        || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        if (es_peticion_fetch()) {
            responder_json(['ok' => false, 'error' => 'La página caducó. Recárgala e intenta de nuevo.'], 400);
        }
        http_response_code(400);
        avisar('La página caducó. Intenta de nuevo.', 'error');
        header('Location: /admin/');
        exit;
    }
}

// --- Avisos de una sola vez ("Guardado", "Eliminado", errores) ------------------------

function avisar(string $mensaje, string $tipo = 'exito'): void
{
    $_SESSION['avisos'][] = ['texto' => $mensaje, 'tipo' => $tipo];
}

function tomar_avisos(): array
{
    $avisos = $_SESSION['avisos'] ?? [];
    unset($_SESSION['avisos']);
    return $avisos;
}

// --- Utilidades de respuesta ---------------------------------------------------------

function es_peticion_fetch(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
}

function responder_json(array $datos, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function redirigir(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Valida el parámetro de sección (?s=) y devuelve su configuración o corta con 404. */
function seccion_solicitada(?string $valor = null): string
{
    $s = (string) ($valor ?? $_GET['s'] ?? $_POST['s'] ?? '');
    if (!isset(SECCIONES_ADMIN[$s])) {
        http_response_code(404);
        exit('Sección no encontrada.');
    }
    return $s;
}
