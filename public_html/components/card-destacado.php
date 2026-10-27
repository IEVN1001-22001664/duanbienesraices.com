<?php
/**
 * Tarjeta de propiedad destacada.
 * Variables: $item (elemento de destacados.json).
 * La galería usa scroll-snap (funciona sin JS); main.js agrega flechas y contador.
 */
$fotos = array_values(array_filter($item['fotos'] ?? [], fn($f) => is_array($f) && !empty($f['card'])));
$operacion = $item['operacion'] === 'renta' ? 'Renta' : 'Venta';
$idGaleria = 'galeria-' . preg_replace('/[^a-z0-9-]/', '', strtolower((string) $item['id']));
?>
<article class="destacado">
    <div class="destacado__galeria galeria" data-galeria>
        <span class="etiqueta etiqueta--<?= e($item['operacion']) ?>"><?= e(mb_strtoupper($operacion)) ?></span>
        <ul class="galeria__pista" id="<?= e($idGaleria) ?>" tabindex="0" aria-label="<?= e('Fotos de ' . $item['titulo']) ?>">
            <?php foreach ($fotos as $i => $foto): ?>
                <li class="galeria__foto">
                    <img src="<?= e(ruta_upload($foto['card'])) ?>"
                         alt="<?= e($item['titulo'] . ' — foto ' . ($i + 1) . ' de ' . count($fotos)) ?>"
                         width="720" height="540" loading="lazy" decoding="async">
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (count($fotos) > 1): ?>
            <button class="galeria__flecha galeria__flecha--izq" type="button" aria-controls="<?= e($idGaleria) ?>" aria-label="Foto anterior" hidden><?= icono('flecha-izq') ?></button>
            <button class="galeria__flecha galeria__flecha--der" type="button" aria-controls="<?= e($idGaleria) ?>" aria-label="Foto siguiente" hidden><?= icono('flecha-der') ?></button>
            <span class="galeria__contador" aria-hidden="true"><span data-actual>1</span>/<?= count($fotos) ?></span>
        <?php endif; ?>
    </div>

    <div class="destacado__cuerpo">
        <h3 class="destacado__titulo"><?= e($item['titulo']) ?></h3>
        <p class="destacado__meta">
            <?php if (!empty($item['tipo'])): ?><span><?= e($item['tipo']) ?></span><?php endif; ?>
            <?php if (!empty($item['ubicacion'])): ?><span><?= icono('ubicacion', 'icono icono--mini') ?><?= e($item['ubicacion']) ?></span><?php endif; ?>
        </p>
        <p class="destacado__precio"><?= e(formato_precio($item['precio'], $item['moneda'] ?? 'MXN')) ?></p>
        <?php if (!empty($item['descripcion'])): ?>
            <p class="destacado__descripcion"><?= e($item['descripcion']) ?></p>
        <?php endif; ?>
        <a class="boton boton--contorno destacado__boton" href="<?= e($item['url_tokko']) ?>" target="_blank" rel="noopener">
            Ver ficha <?= icono('externo', 'icono icono--mini') ?><span class="sr-only"> de <?= e($item['titulo']) ?> (abre en otra pestaña)</span>
        </a>
    </div>
</article>
