<?php
/**
 * Botón flotante de WhatsApp (todas las páginas).
 * Una página puede definir $mensajeWhatsapp antes de incluir el footer para personalizarlo.
 */
$mensajeWhatsapp = $mensajeWhatsapp ?? WHATSAPP_MENSAJE;
?>
<a class="whatsapp-flotante" href="<?= e(whatsapp_url($mensajeWhatsapp)) ?>" target="_blank" rel="noopener"
   aria-label="Escríbenos por WhatsApp (abre en otra pestaña)">
    <?= icono('whatsapp') ?>
    <span class="whatsapp-flotante__texto">¿Hablamos?</span>
</a>
