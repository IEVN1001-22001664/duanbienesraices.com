<?php
/**
 * Cierre de los formularios públicos: widget de Turnstile, botón y mensaje de estado.
 *
 * Variables: $boton (texto del botón).
 * main.js carga el script de Cloudflare solo cuando el formulario está cerca de verse.
 * Sin JS, api/enviar.php regresa a la página con ?envio=ok|error y aquí se muestra el resultado.
 */

$siteKey = defined('TURNSTILE_SITE_KEY') ? (string) TURNSTILE_SITE_KEY : '';
$envio = $_GET['envio'] ?? '';
?>
<?php if ($siteKey !== ''): ?>
    <div class="formulario__turnstile" data-turnstile="<?= e($siteKey) ?>"></div>
<?php endif; ?>
<button class="boton boton--bloque" type="submit"><?= e($boton) ?></button>
<?php if ($envio === 'ok'): ?>
    <div class="formulario__estado es-exito" role="status" aria-live="polite">¡Gracias! Recibimos su mensaje y le responderemos a la brevedad.</div>
<?php elseif ($envio === 'error'): ?>
    <div class="formulario__estado" role="status" aria-live="polite">No pudimos enviar su mensaje. Revise los datos e intente de nuevo, o escríbanos por WhatsApp al <?= e(WHATSAPP_VISIBLE) ?>.</div>
<?php else: ?>
    <div class="formulario__estado" role="status" aria-live="polite"></div>
<?php endif; ?>
