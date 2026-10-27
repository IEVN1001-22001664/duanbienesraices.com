<?php
/**
 * Llamado a la acción para compradores (shortcode [[cta-asesoria]] del blog).
 */
?>
<aside class="cta-oscuro">
    <h2 class="cta-oscuro__titulo">¿Listo para dar el paso hacia tu nuevo hogar?</h2>
    <p>Encontrar la casa indicada en León no tiene por qué ser un proceso abrumador. En nuestro equipo inmobiliario te acompañamos desde el precalificado de tu crédito sin costo hasta la entrega de tus llaves.</p>
    <div class="cta-oscuro__botones">
        <a class="boton" href="<?= e(URL_TOKKO_PROPIEDADES) ?>" target="_blank" rel="noopener">Explora nuestro catálogo de casas en venta</a>
        <a class="boton boton--whatsapp" href="<?= e(whatsapp_url('Hola, leí su guía en el blog y me gustaría recibir asesoría para comprar mi primera casa.')) ?>" target="_blank" rel="noopener">
            <?= icono('whatsapp') ?> Contáctanos por WhatsApp
        </a>
    </div>
</aside>
