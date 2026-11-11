<?php
/**
 * Tarjeta de propiedad para el listado /propiedades y «Propiedades similares».
 * Variables: $prop (de data/tokko/propiedades.json), $operacion (opcional: venta|renta preferida),
 *            $titulo_nivel (opcional: 'h2' o 'h3').
 * Toda la tarjeta es un enlace a la ficha; la portada es la copia WebP local.
 */
$op = operacion_principal($prop, $operacion ?? '');
$h = ($titulo_nivel ?? 'h2') === 'h3' ? 'h3' : 'h2';
$portada = portada_propiedad($prop);
$datos = array_slice(datos_clave($prop), 0, 4);
?>
<article class="propiedad-card">
    <a class="propiedad-card__enlace" href="<?= e(url_propiedad($prop)) ?>">
        <div class="propiedad-card__foto">
            <?php if ($portada): ?>
                <img src="<?= e($portada) ?>" alt="" width="720" height="540" loading="lazy" decoding="async">
            <?php else: ?>
                <span class="propiedad-card__sin-foto"><?= icono('camara') ?></span>
            <?php endif; ?>
            <span class="etiqueta etiqueta--<?= e($op['tipo']) ?>"><?= $op['tipo'] === 'renta' ? 'RENTA' : 'VENTA' ?></span>
            <?php if (count($prop['fotos']) > 1): ?>
                <span class="propiedad-card__fotos"><?= icono('camara', 'icono icono--mini') ?> <?= count($prop['fotos']) ?></span>
            <?php endif; ?>
        </div>
        <div class="propiedad-card__cuerpo">
            <p class="propiedad-card__precio"><?= e(precio_propiedad($prop, $op)) ?></p>
            <<?= $h ?> class="propiedad-card__titulo"><?= e($prop['titulo']) ?></<?= $h ?>>
            <p class="propiedad-card__ubicacion"><?= icono('ubicacion', 'icono icono--mini') ?> <?= e($prop['ubicacion']) ?></p>
            <?php if ($datos): ?>
                <ul class="propiedad-card__datos">
                    <?php foreach ($datos as [$icono, $texto]): ?>
                        <li><?= icono($icono, 'icono icono--mini') ?> <?= e($texto) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <p class="propiedad-card__pie"><span><?= e($prop['tipo']) ?></span><span><?= e($prop['codigo']) ?></span></p>
        </div>
    </a>
</article>
