<?php
/**
 * Banners promocionales de Propiedades destacadas (inicio), editables en el panel.
 * Uno solo se muestra fijo; con varios, carrusel con flechas y puntos que avanza solo
 * (data-auto en main.js; se detiene con el mouse encima y con «reducir movimiento»).
 */
$banners = obtener_seccion('vitrina');
if (!$banners) {
    return;
}
$varios = count($banners) > 1;
?>
<div class="vitrina carrusel" data-carrusel<?= $varios ? ' data-auto="8000" aria-roledescription="carrusel" aria-label="Promociones"' : '' ?>>
    <ul class="carrusel__pista">
        <?php foreach ($banners as $i => $item): ?>
            <li data-slide<?= $varios ? ' aria-roledescription="diapositiva" aria-label="' . ($i + 1) . ' de ' . count($banners) . '"' : '' ?>>
                <?php componente('cartel', ['item' => $item]); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($varios): ?>
        <div class="carrusel__controles">
            <button class="carrusel__flecha" type="button" data-anterior aria-label="Promoción anterior"><?= icono('flecha-izq') ?></button>
            <div class="puntos" data-puntos></div>
            <button class="carrusel__flecha" type="button" data-siguiente aria-label="Promoción siguiente"><?= icono('flecha-der') ?></button>
        </div>
    <?php endif; ?>
</div>
