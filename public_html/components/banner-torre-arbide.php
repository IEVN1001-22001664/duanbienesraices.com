<?php
/**
 * Banner interno de Torre Arbide (proyecto exclusivo de venta de Du'an) en el inicio,
 * entre el texto y las tarjetas de Propiedades destacadas.
 * Escritorio: texto sobre carbón a la izquierda y la fachada a la derecha con la firma encima.
 * Celular: la foto arriba, el texto abajo, botones a lo ancho y la firma al final.
 * Textos y cifras dados por el cliente (17 nov 2026); la foto es la portada de Tokko 10483841.
 */
$arbide = [
    'etiqueta'       => 'Últimas 7 unidades',
    'titulo'         => 'Últimas unidades en Torre Arbide',
    'texto'          => 'Lofts con vista panorámica en la colonia Arbide, cerca de bancos, plazas y vías principales.',
    'texto_celular'  => 'Lofts con vista panorámica en la colonia Arbide.',
    'precio'         => '$2,320,000 MXN',
    'url'            => 'https://torrearbide.com/',
    // «Agendar visita»: evento «Visitas Torre Arbide» de Cal.com, en el mismo modal que las fichas.
    // Si se deja vacío, el botón abre WhatsApp con el mensaje de abajo.
    'agenda'         => 'https://cal.com/informacion-du-an-bienes-raices-jfgxcn/visitas-torre-arbide',
    'whatsapp'       => '¡Hola! Vengo de su página web y quiero agendar una visita a Torre Arbide.',
];
?>
<aside class="arbide" aria-labelledby="titulo-arbide">
    <div class="arbide__texto">
        <p class="arbide__etiqueta"><?= e($arbide['etiqueta']) ?></p>
        <h3 class="arbide__titulo" id="titulo-arbide"><?= e($arbide['titulo']) ?></h3>
        <p class="arbide__descripcion">
            <span class="arbide__solo-escritorio"><?= e($arbide['texto']) ?></span>
            <span class="arbide__solo-celular"><?= e($arbide['texto_celular']) ?></span>
        </p>
        <p class="arbide__precio">Desde <strong><?= e($arbide['precio']) ?></strong></p>
        <div class="arbide__botones">
            <?php if ($arbide['agenda'] !== ''): ?>
            <a class="boton arbide__boton" href="<?= e($arbide['agenda']) ?>" target="_blank" rel="noopener" data-agendar-cita="cal">Agendar visita</a>
            <?php else: ?>
            <a class="boton arbide__boton" href="<?= e(whatsapp_url($arbide['whatsapp'])) ?>" target="_blank" rel="noopener">Agendar visita</a>
            <?php endif; ?>
            <a class="boton arbide__boton arbide__boton--contorno" href="<?= e($arbide['url']) ?>" target="_blank" rel="noopener">Ver departamentos<span class="sr-only"> en torrearbide.com (se abre en otra pestaña)</span></a>
        </div>
    </div>
    <div class="arbide__foto">
        <img src="/assets/img/torre-arbide.webp" srcset="/assets/img/torre-arbide-800.webp 800w, /assets/img/torre-arbide.webp 1600w"
             sizes="(min-width: 860px) 55vw, 100vw" alt="Fachada de Torre Arbide, edificio de lofts en la colonia Arbide, León"
             width="1600" height="1000" loading="lazy" decoding="async">
    </div>
    <p class="arbide__firma" aria-hidden="true">
        <span class="arbide__duan">Du’an<small>Bienes Raíces</small></span>
        <span class="arbide__proyecto">Torre Arbide</span>
    </p>
</aside>
