<?php
/**
 * Tarjeta de propiedad para el listado /propiedades y «Propiedades similares».
 * Variables: $prop (de data/tokko/propiedades.json), $operacion (opcional: venta|renta preferida),
 *            $titulo_nivel (opcional: 'h2' o 'h3').
 * Toda la tarjeta es un enlace a la ficha; las fotos son copias WebP locales (fotos_tarjeta()).
 * Con varias fotos la imagen se desliza con el dedo (scroll-snap): solo la portada se descarga al
 * cargar la página; main.js pide las demás cuando la persona empieza a deslizar ([data-tarjeta-fotos]).
 * La última diapositiva invita a ver todas las fotos en la ficha. Las flechas (solo con mouse) las
 * agrega main.js fuera del enlace, porque un botón no puede ir dentro de un <a>.
 */
$op = operacion_principal($prop, $operacion ?? '');
$h = ($titulo_nivel ?? 'h2') === 'h3' ? 'h3' : 'h2';
$fotosTarjeta = fotos_tarjeta($prop);
$portada = $fotosTarjeta[0] ?? null;
$totalFotos = count($prop['fotos']);
$verMas = count($fotosTarjeta) > 1 && $totalFotos > count($fotosTarjeta);   // diapositiva final «Ver las N fotos»
$datos = datos_clave_cortos($prop);
?>
<article class="propiedad-card">
    <a class="propiedad-card__enlace" href="<?= e(url_propiedad($prop)) ?>">
        <div class="propiedad-card__foto">
            <?php if (count($fotosTarjeta) > 1): ?>
                <ul class="tarjeta-fotos" data-tarjeta-fotos>
                    <?php foreach ($fotosTarjeta as $i => $foto): ?>
                        <li class="tarjeta-fotos__foto">
                            <?php if ($i === 0): ?>
                                <img src="<?= e($foto) ?>" alt="" width="720" height="540" loading="lazy" decoding="async">
                            <?php else: ?>
                                <img data-src="<?= e($foto) ?>" alt="" width="720" height="540" decoding="async">
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if ($verMas): ?>
                        <li class="tarjeta-fotos__mas"><?= icono('camara') ?> <span>Ver las <?= $totalFotos ?> fotos</span></li>
                    <?php endif; ?>
                </ul>
                <span class="tarjeta-fotos__puntos" aria-hidden="true">
                    <?php for ($i = 0, $n = count($fotosTarjeta) + ($verMas ? 1 : 0); $i < $n; $i++): ?><i<?= $i === 0 ? ' class="es-actual"' : '' ?>></i><?php endfor; ?>
                </span>
            <?php elseif ($portada): ?>
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
            <p class="propiedad-card__chips"><span><?= e($prop['tipo']) ?></span><span class="propiedad-card__codigo"><?= e($prop['codigo']) ?></span></p>
            <p class="propiedad-card__precio"><?= e(precio_propiedad($prop, $op)) ?></p>
            <<?= $h ?> class="propiedad-card__titulo"><?= e($prop['titulo']) ?></<?= $h ?>>
            <p class="propiedad-card__ubicacion" title="<?= e($prop['ubicacion']) ?>"><?= icono('ubicacion', 'icono icono--mini') ?> <span><?= e($prop['ubicacion']) ?></span></p>
            <?php if ($datos): ?>
                <ul class="propiedad-card__datos">
                    <?php foreach ($datos as [$icono, $corto, $completo]): ?>
                        <li title="<?= e($completo) ?>"><?= icono($icono, 'icono icono--mini') ?> <?= e($corto) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </a>
</article>
