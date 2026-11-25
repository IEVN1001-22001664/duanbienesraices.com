<?php
/**
 * Tarjeta de propiedad destacada.
 * Variables: $item (elemento de destacados.json).
 * La galería usa scroll-snap (funciona sin JS); main.js agrega flechas y contador.
 * Versión compacta, con el mismo cuerpo que las tarjetas de /propiedades (clases .propiedad-card__*):
 * tipo y código, precio, título en 2 líneas, ubicación en 1 y datos clave de la copia de Tokko.
 */
$fotos = array_values(array_filter($item['fotos'] ?? [], fn($f) => is_array($f) && !empty($f['card'])));
$operacion = $item['operacion'] === 'renta' ? 'Renta' : 'Venta';
$idGaleria = 'galeria-' . preg_replace('/[^a-z0-9-]/', '', strtolower((string) $item['id']));
$prop = !empty($item['tokko_id']) && function_exists('tokko_propiedad') ? tokko_propiedad((int) $item['tokko_id']) : null;
$datos = $prop ? datos_clave_cortos($prop) : [];
// Destacados de Tokko: después de su portada van las demás fotos de la tarjeta (fotos_tarjeta()), que
// main.js descarga hasta que la persona desliza o toca una flecha (data-src). Al final, «Ver las N fotos».
$extras = $prop && count($fotos) === 1 ? array_slice(fotos_tarjeta($prop), 1) : [];
$totalFotos = $prop ? count($prop['fotos']) : count($fotos);
$ficha = (string) ($item['url_ficha'] ?? '') ?: $item['url_tokko'];
$externa = (bool) preg_match('#^https?://#i', $ficha);
$verMas = $extras && $totalFotos > 1 + count($extras);
$diapositivas = count($fotos) + count($extras) + ($verMas ? 1 : 0);
?>
<article class="destacado">
    <div class="destacado__galeria galeria<?= $extras && !FOTOS_TARJETA_ESCRITORIO ? ' fotos-solo-tactil' : '' ?>" data-galeria>
        <span class="etiqueta etiqueta--<?= e($item['operacion']) ?>"><?= e(mb_strtoupper($operacion)) ?></span>
        <ul class="galeria__pista" id="<?= e($idGaleria) ?>" tabindex="0" aria-label="<?= e('Fotos de ' . $item['titulo']) ?>">
            <?php foreach ($fotos as $i => $foto): ?>
                <li class="galeria__foto">
                    <img src="<?= e(ruta_upload($foto['card'])) ?>"
                         alt="<?= e($item['titulo'] . ' — foto ' . ($i + 1) . ' de ' . count($fotos)) ?>"
                         width="720" height="540" loading="lazy" decoding="async">
                </li>
            <?php endforeach; ?>
            <?php foreach ($extras as $i => $foto): ?>
                <li class="galeria__foto">
                    <img data-src="<?= e($foto) ?>" alt="<?= e($item['titulo'] . ' — foto ' . ($i + 2)) ?>" width="720" height="540" decoding="async">
                </li>
            <?php endforeach; ?>
            <?php if ($verMas): ?>
                <li class="galeria__foto tarjeta-fotos__mas">
                    <?php // #foto-N: la ficha abre la galería en la foto que sigue a las de la tarjeta ?>
                    <a href="<?= e($ficha . ($externa ? '' : '#foto-' . (count($fotos) + count($extras) + 1))) ?>"<?= $externa ? ' target="_blank" rel="noopener"' : '' ?>><?= icono('camara') ?> <span>Ver las <?= $totalFotos ?> fotos</span></a>
                </li>
            <?php endif; ?>
        </ul>
        <?php if ($diapositivas > 1): ?>
            <?php // Sin flechas (25 nov 2026, a pedido del cliente): en las tarjetas las fotos solo se deslizan ?>
            <?php if ($extras): ?>
                <span class="tarjeta-fotos__puntos" aria-hidden="true">
                    <?php for ($i = 0; $i < $diapositivas; $i++): ?><i<?= $i === 0 ? ' class="es-actual"' : '' ?>></i><?php endfor; ?>
                </span>
            <?php else: ?>
                <span class="galeria__contador" aria-hidden="true"><span data-actual>1</span>/<?= count($fotos) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="destacado__cuerpo propiedad-card__cuerpo">
        <?php if (!empty($item['tipo']) || $prop): ?>
            <p class="propiedad-card__chips">
                <?php if (!empty($item['tipo'])): ?><span><?= e($item['tipo']) ?></span><?php endif; ?>
                <?php if ($prop): ?><span class="propiedad-card__codigo"><?= e($prop['codigo']) ?></span><?php endif; ?>
            </p>
        <?php endif; ?>
        <p class="propiedad-card__precio"><?= ($item['precio_visible'] ?? true) === false ? 'Precio a consultar' : e(formato_precio($item['precio'], $item['moneda'] ?? 'MXN')) ?></p>
        <h3 class="propiedad-card__titulo destacado__titulo"><?= e($item['titulo']) ?></h3>
        <?php if (!empty($item['ubicacion'])): ?>
            <p class="propiedad-card__ubicacion" title="<?= e($item['ubicacion']) ?>"><?= icono('ubicacion', 'icono icono--mini') ?> <span><?= e($item['ubicacion']) ?></span></p>
        <?php endif; ?>
        <?php if ($datos): ?>
            <ul class="propiedad-card__datos">
                <?php foreach ($datos as [$icono, $corto, $completo]): ?>
                    <li title="<?= e($completo) ?>"><?= icono($icono, 'icono icono--mini') ?> <?= e($corto) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php // Los destacados de Tokko abren la ficha propia ($ficha); los manuales siguen enlazando a Tokko ?>
        <a class="boton boton--contorno boton--bloque destacado__boton" href="<?= e($ficha) ?>"<?= $externa ? ' target="_blank" rel="noopener"' : '' ?>>
            Ver ficha<?php if ($externa): ?> <?= icono('externo', 'icono icono--mini') ?><?php endif; ?><span class="sr-only"> de <?= e($item['titulo']) ?><?= $externa ? ' (abre en otra pestaña)' : '' ?></span>
        </a>
    </div>
</article>
