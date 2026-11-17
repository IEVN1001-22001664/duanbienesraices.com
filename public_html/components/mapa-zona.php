<?php
/**
 * Mapa de la zona de una propiedad (ficha de /propiedades y ficha de ejemplo de Tengo una propiedad).
 *
 * Variables:
 *   $zonaGeo    [lat, lng] ya redondeadas a 2 decimales (~1 km) o null. Nunca el punto exacto.
 *   $zonaNombre colonia, ciudad y estado (texto de la etiqueta y respaldo para buscar en Google).
 *   $zonaClase  clases extra del marco (opcional).
 *
 * Con coordenadas, main.js carga Leaflet (assets/vendor/leaflet) cuando el mapa está por verse y dibuja
 * con OpenStreetMap un círculo de ZONA_RADIO_M metros que forma parte del mapa: se mueve y escala con él.
 * El radio cubre el error del redondeo, así que la propiedad siempre queda dentro.
 * Sin JS (o si Leaflet no carga) se muestra el mapa de Google centrado en la misma zona, sin círculo.
 */
$zonaClase = isset($zonaClase) ? ' ' . $zonaClase : '';
$zonaTitulo = 'Mapa de la zona: ' . $zonaNombre;
if (!empty($zonaGeo)) {
    $zonaCoords = $zonaGeo[0] . ',' . $zonaGeo[1];
    $zonaGoogle = 'https://www.google.com/maps?ll=' . $zonaCoords . '&z=14&t=m&output=embed';
    $zonaAbrir = 'https://www.google.com/maps/search/?api=1&query=' . $zonaCoords;
} else {
    $zonaGoogle = 'https://www.google.com/maps?q=' . rawurlencode($zonaNombre . ', México') . '&z=14&output=embed';
    $zonaAbrir = null;
}
?>
<div class="mapa__marco ficha__mapa<?= e($zonaClase) ?>">
    <?php if (!empty($zonaGeo)): ?>
        <div class="ficha__mapa-lienzo" role="region" aria-label="<?= e($zonaTitulo) ?>"
             data-mapa-zona data-lat="<?= e((string) $zonaGeo[0]) ?>" data-lng="<?= e((string) $zonaGeo[1]) ?>"
             data-radio="<?= ZONA_RADIO_M ?>" data-respaldo="<?= e($zonaGoogle) ?>"
             data-leaflet-js="<?= e(asset('vendor/leaflet/leaflet.js')) ?>" data-leaflet-css="<?= e(asset('vendor/leaflet/leaflet.css')) ?>">
            <noscript><iframe src="<?= e($zonaGoogle) ?>" title="<?= e($zonaTitulo) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></noscript>
        </div>
        <a class="ficha__mapa-abrir" href="<?= e($zonaAbrir) ?>" target="_blank" rel="noopener">Abrir en Maps <?= icono('externo', 'icono icono--mini') ?></a>
    <?php else: ?>
        <?php /* Sin coordenadas: búsqueda por colonia y ciudad en Google, con carga diferida nativa */ ?>
        <iframe src="<?= e($zonaGoogle) ?>" title="<?= e($zonaTitulo) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <?php endif; ?>
    <p class="ficha__mapa-zona"><?= icono('ubicacion', 'icono icono--mini') ?> <?= e($zonaNombre) ?></p>
</div>
