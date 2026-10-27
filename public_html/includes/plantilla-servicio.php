<?php
/**
 * Plantilla compartida por las páginas de servicios (renta, venta, administración).
 * Cada página define $servicio y luego hace require de este archivo.
 *
 * $servicio = [
 *   clave, titulo, lead, imagen, imagen_alt, intro (párrafos), pasos [[titulo, texto(s), lista?, nota?]],
 *   lista_final (opcional), nota (opcional), cta_titulo, cta_texto, cta_boton, cta_mensaje
 * ]
 */

$pagina = array_merge([
    'activo'   => '',
    'precarga' => $servicio['imagen'],
    'imagen'   => $servicio['imagen'],
    'jsonld'   => [[
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $servicio['titulo'],
        'serviceType' => $servicio['titulo'],
        'description' => $servicio['lead'],
        'areaServed'  => 'León, Guanajuato',
        'provider'    => ['@id' => URL_SITIO . '/#negocio'],
    ]],
], $pagina);
$mensajeWhatsapp = $servicio['cta_mensaje'];

require __DIR__ . '/head.php';
require __DIR__ . '/header.php';
?>

<section class="portada portada--servicio" style="--fondo: url('<?= e($servicio['imagen']) ?>')">
    <div class="contenedor portada__texto">
        <p class="portada__antetitulo">Servicios</p>
        <h1 class="portada__titulo"><?= e($servicio['titulo']) ?></h1>
        <p class="portada__lead"><?= e($servicio['lead']) ?></p>
    </div>
</section>

<nav class="subnav" aria-label="Servicios">
    <ul class="pestanas pestanas--claras contenedor">
        <?php foreach (SERVICIOS as $s): ?>
            <li><a class="pestana<?= $s['clave'] === $servicio['clave'] ? ' es-activa' : '' ?>" href="<?= e($s['url']) ?>"
                   <?= $s['clave'] === $servicio['clave'] ? 'aria-current="page"' : '' ?>><?= e($s['texto']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<section class="seccion proceso" aria-labelledby="titulo-proceso">
    <div class="contenedor proceso__grid">
        <div class="proceso__contenido">
            <h2 class="titulo-seccion titulo-seccion--izq" id="titulo-proceso"><?= e($servicio['pregunta']) ?></h2>
            <?php foreach ($servicio['intro'] ?? [] as $p): ?>
                <p class="proceso__intro"><?= e($p) ?></p>
            <?php endforeach; ?>

            <?php if (!empty($servicio['pasos'])): ?>
            <ol class="pasos">
                <?php foreach ($servicio['pasos'] as $n => $paso): ?>
                    <li class="paso">
                        <span class="paso__numero" aria-hidden="true"><?= $n + 1 ?></span>
                        <div class="paso__cuerpo">
                            <h3 class="paso__titulo"><span class="sr-only">Paso <?= $n + 1 ?>. </span><?= e($paso['titulo']) ?></h3>
                            <?php foreach ((array) $paso['texto'] as $p): ?>
                                <p><?= e($p) ?></p>
                            <?php endforeach; ?>
                            <?php if (!empty($paso['lista'])): ?>
                                <ul class="lista-check">
                                    <?php foreach ($paso['lista'] as $li): ?><li><?= icono('check') ?><?= e($li) ?></li><?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php foreach ((array) ($paso['despues'] ?? []) as $p): ?>
                                <p><?= e($p) ?></p>
                            <?php endforeach; ?>
                            <?php if (!empty($paso['nota'])): ?>
                                <div class="nota">
                                    <p class="nota__titulo"><?= e($paso['nota']['titulo']) ?></p>
                                    <p><?= e($paso['nota']['texto']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>

            <?php if (!empty($servicio['lista_final'])): ?>
                <ul class="lista-check lista-check--grande">
                    <?php foreach ($servicio['lista_final'] as $li): ?><li><?= icono('check') ?><?= e($li) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php foreach ($servicio['cierre'] ?? [] as $p): ?>
                <p><?= e($p) ?></p>
            <?php endforeach; ?>

            <?php if (!empty($servicio['nota'])): ?>
                <p class="nota"><strong>Nota:</strong> <?= $servicio['nota'] /* HTML fijo escrito en código, no viene de JSON */ ?></p>
            <?php endif; ?>
        </div>

        <aside class="proceso__lateral">
            <img src="<?= e($servicio['imagen_lateral'] ?? $servicio['imagen']) ?>" alt="<?= e($servicio['imagen_alt']) ?>" width="1000" height="1400" loading="lazy" decoding="async">
            <div class="tarjeta-cta">
                <p class="tarjeta-cta__titulo"><?= e($servicio['cta_titulo']) ?></p>
                <p><?= e($servicio['cta_texto']) ?></p>
                <a class="boton boton--whatsapp boton--bloque" href="<?= e(whatsapp_url($servicio['cta_mensaje'])) ?>" target="_blank" rel="noopener">
                    <?= icono('whatsapp') ?> <?= e($servicio['cta_boton']) ?>
                </a>
                <a class="boton boton--contorno boton--bloque" href="/ofrezca-su-propiedad#valuacion">Llenar formulario</a>
            </div>
        </aside>
    </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
