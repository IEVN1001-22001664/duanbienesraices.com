<?php
/**
 * Recibe los formularios de Contacto y Ofrezca su propiedad y los envía por correo.
 *
 * - Con `X-Requested-With: fetch` (main.js) responde JSON: {ok, mensaje}.
 * - Sin JS hace una redirección 303 a la página con ?envio=ok|error.
 * Anti-spam: honeypot `sitio_web`, Cloudflare Turnstile y límite de envíos por IP.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/formularios.php';

const PAGINAS_FORMULARIO = [
    'contacto'  => '/contacto',
    'propiedad' => '/ofrezca-su-propiedad',
];

header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');

$formulario = is_string($_POST['formulario'] ?? null) ? $_POST['formulario'] : '';

/** Termina la petición con el resultado, en JSON o con redirección. */
function responder(bool $ok, string $mensaje, int $codigo = 200): never
{
    // Sin variables globales: en local dev-router.php incluye este archivo dentro de una función
    $formulario = is_string($_POST['formulario'] ?? null) ? $_POST['formulario'] : '';
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
        http_response_code($ok ? 200 : $codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $pagina = PAGINAS_FORMULARIO[$formulario] ?? '/contacto';
    header('Location: ' . $pagina . '?envio=' . ($ok ? 'ok' : 'error') . '#formulario', true, 303);
    exit;
}

$errorGeneral = 'No pudimos enviar su mensaje. Intente de nuevo o escríbanos por WhatsApp al '
    . WHATSAPP_VISIBLE . ' o a ' . EMAIL_CONTACTO . '.';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responder(false, 'Método no permitido.', 405);
}

// Solo se aceptan envíos desde el propio sitio
$origen = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origen !== '' && parse_url($origen, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
    responder(false, $errorGeneral, 403);
}

if (!isset(PAGINAS_FORMULARIO[$formulario])) {
    responder(false, $errorGeneral, 400);
}

$exito = $formulario === 'propiedad'
    ? '¡Gracias! Recibimos los datos de su propiedad y en breve un asesor se pondrá en contacto.'
    : '¡Gracias! Recibimos su mensaje y le responderemos a la brevedad.';

// Honeypot: un robot lo llenó. Se responde como si todo saliera bien.
if (trim((string) ($_POST['sitio_web'] ?? '')) !== '') {
    responder(true, $exito);
}

[$datos, $errores] = validar_envio($formulario, $_POST);
if ($errores) {
    responder(false, 'Revise el formulario: ' . implode(' ', $errores), 422);
}

if (!verificar_turnstile((string) ($_POST['cf-turnstile-response'] ?? ''))) {
    responder(false, 'No pudimos verificar que no es un robot. Espere unos segundos y vuelva a enviar.', 403);
}

if (!registrar_envio_permitido()) {
    responder(false, 'Ha enviado varios mensajes en poco tiempo. Intente más tarde o escríbanos por WhatsApp al ' . WHATSAPP_VISIBLE . '.', 429);
}

if (!enviar_correo($formulario, $datos)) {
    responder(false, $errorGeneral, 500);
}

responder(true, $exito);
