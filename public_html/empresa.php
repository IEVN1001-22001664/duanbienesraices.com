<?php
/**
 * Nosotros (/empresa). Contenido fijo en código.
 * Estructura: hero → mensaje del fundador → misión/visión/valores → cifras + CTA.
 * Los pilares de blindaje viven en Tengo una propiedad (ofrezca-su-propiedad.php).
 */
require __DIR__ . '/includes/config.php';

$pagina = [
    'titulo'      => "Nosotros | Du'an Bienes Raíces, inmobiliaria en León desde 2013",
    'descripcion' => 'Somos una empresa con más de 13 años de experiencia en el sector. Profesionalismo y credibilidad nos respaldan.',
    'ruta'        => '/empresa',
    'activo'      => 'nosotros',
    'imagen'      => '/assets/img/nosotros-hero.webp',
    'precarga'    => '/assets/img/nosotros-hero.webp',
];

$valores = [
    ['Honestidad', 'Ayudar y asegurar a nuestros clientes una venta o renta transparente.'],
    ['Positividad', 'Buscamos el cómo sí de las cosas para llevar a cabo y cumplir nuestros objetivos.'],
    ['Trabajo', 'Hacemos lo que hay que hacer, cuando hay que hacerlo.'],
    ['Perseverancia', 'Hasta alcanzar nuestras metas y objetivos, tanto de la empresa como de nuestros clientes.'],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<section class="portada" style="--fondo: url('/assets/img/nosotros-hero.webp')">
    <div class="contenedor portada__texto">
        <p class="portada__antetitulo">Desde 2013 en León, Guanajuato</p>
        <h1 class="portada__titulo">Nosotros</h1>
        <p class="portada__lead"><?= e(SLOGAN) ?></p>
    </div>
</section>

<section class="seccion fundador" aria-labelledby="titulo-fundador">
    <div class="contenedor">
        <div class="fundador__tarjeta">
            <figure class="fundador__foto">
                <img src="/assets/img/fundador-alejandro-jaquez.webp" alt="Arq. Alejandro Jáquez Martínez, director y fundador de Du'an Bienes Raíces" width="900" height="1194" loading="lazy">
                <figcaption class="fundador__etiqueta">Fundador</figcaption>
            </figure>
            <div class="fundador__texto">
                <p class="fundador__antetitulo">Carta del fundador</p>
                <h2 class="fundador__cita" id="titulo-fundador">“Un patrimonio no es un número en un contrato. Es la historia de una familia.”</h2>
                <p>Fundé Du’an Bienes Raíces en 2013, en León, después de años de diseñar y supervisar obra como arquitecto. Ese oficio me enseñó algo que pocos asesores toman en cuenta: una propiedad no vale solo por su ubicación o sus metros cuadrados, vale por cómo fue construida y qué tan bien se cuidó.</p>
                <p>Por eso en cada operación reviso personalmente la certeza legal del inmueble, superviso la valuación con criterios reales de mercado y me aseguro de que la promoción muestre la propiedad tal como es: bien hecha.</p>
                <p class="fundador__firma">
                    <strong>Arq. Alejandro Jáquez Martínez</strong>
                    <span>Director y fundador</span>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="seccion mision" aria-labelledby="titulo-mision">
    <div class="contenedor mision__grid">
        <div>
            <h2 class="titulo-seccion titulo-seccion--izq" id="titulo-mision">Misión</h2>
            <p>Ayudamos a las personas a vender o rentar su propiedad a través de una excelente asesoría en términos legales y de promoción, otorgando siempre un servicio personalizado para ofrecerles una mejor calidad de vida.</p>
            <h2 class="titulo-seccion titulo-seccion--izq">Visión</h2>
            <p>Llegar a las principales ciudades de la República Mexicana a través de sucursales propias y franquicias bien establecidas, posicionadas y estandarizadas.</p>
        </div>
        <div>
            <h2 class="titulo-seccion titulo-seccion--izq">Valores</h2>
            <ul class="valores">
                <?php foreach ($valores as [$nombre, $texto]): ?>
                    <li><?= icono('check') ?><div><strong><?= e($nombre) ?>:</strong> <?= e($texto) ?></div></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="contenedor">
        <div class="carrusel carrusel--galeria" data-carrusel aria-roledescription="carrusel" aria-label="Galería de propiedades">
            <ul class="carrusel__pista">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <li data-slide><img src="/assets/img/nosotros-galeria-<?= sprintf('%02d', $i) ?>.webp" alt="Propiedad comercializada por Du'an Bienes Raíces, foto <?= $i ?>" width="1400" height="933" loading="lazy" decoding="async"></li>
                <?php endfor; ?>
            </ul>
            <div class="carrusel__controles">
                <button class="carrusel__flecha" type="button" data-anterior aria-label="Foto anterior"><?= icono('flecha-izq') ?></button>
                <div class="puntos" data-puntos></div>
                <button class="carrusel__flecha" type="button" data-siguiente aria-label="Foto siguiente"><?= icono('flecha-der') ?></button>
            </div>
        </div>
    </div>
</section>

<section class="seccion seccion--gris cifras" aria-labelledby="titulo-cifras">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-cifras">Respaldo que se comprueba</h2>
        <ul class="cifras__grid">
            <li class="cifra-bloque"><span class="cifra-bloque__num">+13</span><span class="cifra-bloque__txt">años de experiencia</span></li>
            <li class="cifra-bloque"><span class="cifra-bloque__num">2013</span><span class="cifra-bloque__txt">inicio de operaciones en León</span></li>
            <li class="cifra-bloque"><span class="cifra-bloque__num">5★</span><span class="cifra-bloque__txt">calificación en Google</span></li>
            <!-- TODO: contenido real — cifra adicional (p. ej. propiedades vendidas/rentadas o familias atendidas). No inventar. -->
        </ul>
        <div class="cta-linea">
            <p>¿Quiere vender, rentar o que administremos su propiedad?</p>
            <div class="cta-linea__botones">
                <a class="boton" href="/ofrezca-su-propiedad#valuacion">Solicitar opinión de valor</a>
                <a class="boton boton--contorno" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> Escribir por WhatsApp</a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
