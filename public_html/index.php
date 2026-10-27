<?php
/**
 * Inicio. Las secciones editables (hero, destacados, testimonios, videos)
 * se leen de data/*.json y se pintan con sus componentes.
 */
require __DIR__ . '/includes/config.php';

$hero        = obtener_seccion('hero');
$destacados  = obtener_seccion('destacados');
$testimonios = obtener_seccion('testimonios');
$videos      = obtener_seccion('videos');

$pagina = [
    'titulo'      => "Inmobiliaria en León, Guanajuato. Venta y Renta de Casas, Du'an Bienes Raíces",
    'descripcion' => "Inmobiliaria en León, Gto. con más de 13 años de experiencia. Le acompañamos en la venta, renta y administración de su propiedad: desde la opinión de valor hasta la firma ante notario.",
    'ruta'        => '/',
    'activo'      => 'inicio',
    'precarga'    => $hero ? ruta_upload($hero[0]['imagen']) : null,
    'jsonld'      => [[
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => NOMBRE_SITIO,
        'url'      => URL_SITIO . '/',
        'inLanguage' => 'es-MX',
    ]],
];

$servicios = [
    ['clave' => 'renta', 'titulo' => 'Renta', 'icono' => 'llave',
     'texto' => 'Perfilamos al mejor inquilino para su propiedad, verificando historial, referencias y capacidad de pago, para asegurar siempre una persona o empresa confiable y solvente.',
     'pasos' => 'Estos son los pasos que seguimos para lograr una RENTA EXITOSA.'],
    ['clave' => 'venta', 'titulo' => 'Venta', 'icono' => 'casa',
     'texto' => 'Le acompañamos de la mano durante todo el proceso de venta: desde la valoración y preparación del inmueble, hasta la firma de la escritura de compraventa ante notario público.',
     'pasos' => 'Estos son los pasos que seguimos para lograr una VENTA EXITOSA.'],
    ['clave' => 'administracion', 'titulo' => 'Administración', 'icono' => 'edificio',
     'texto' => 'Nos encargamos de la administración integral de sus rentas: cobro puntual, seguimiento a inquilinos y supervisión del estado de su propiedad, para que usted solo disfrute de sus ingresos.',
     'pasos' => 'Estos son los pasos que seguimos para lograr una ADMINISTRACIÓN EXITOSA.'],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<h1 class="sr-only">Du'an Bienes Raíces — Inmobiliaria en León, Guanajuato</h1>

<?php if ($hero): ?>
<section class="hero" aria-roledescription="carrusel" aria-label="Presentación" data-carrusel data-auto="7000">
    <ul class="hero__pista">
        <?php foreach ($hero as $i => $item) {
            componente('slide-hero', ['item' => $item, 'indice' => $i, 'total' => count($hero)]);
        } ?>
    </ul>
    <?php if (count($hero) > 1): ?>
        <button class="hero__flecha hero__flecha--izq" type="button" data-anterior aria-label="Diapositiva anterior"><?= icono('flecha-izq') ?></button>
        <button class="hero__flecha hero__flecha--der" type="button" data-siguiente aria-label="Diapositiva siguiente"><?= icono('flecha-der') ?></button>
        <div class="puntos" data-puntos></div>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="bienvenida">
    <div class="contenedor bienvenida__grid">
        <div class="bienvenida__texto">
            <h2 class="titulo-seccion titulo-seccion--izq">¡Bienvenido a <span class="texto-rojo">Du’an Bienes Raíces!</span></h2>
            <p class="bienvenida__lead">Si está pensando vender o rentar su propiedad, lo acompañamos en todo el proceso: <strong>desde la opinión de valor hasta la firma ante notario.</strong></p>
            <p class="bienvenida__exp"><span class="cifra">+13</span> años de experiencia y clientes satisfechos respaldan nuestro trabajo.</p>
        </div>
        <aside class="google-badge" aria-label="Calificación en Google">
            <?= icono('google', 'google-badge__logo') ?>
            <p class="estrellas estrellas--grandes" aria-hidden="true"><?php for ($i = 0; $i < 5; $i++): ?><?= icono('estrella') ?><?php endfor; ?></p>
            <p class="google-badge__texto">Calificación de <strong>5 estrellas</strong> en nuestro perfil de Google</p>
            <a class="boton boton--contorno" href="<?= e(URL_RESENAS_GOOGLE) ?>" target="_blank" rel="noopener">Ver opiniones en Google</a>
        </aside>
    </div>
</section>

<section class="servicios" aria-labelledby="titulo-servicios">
    <div class="contenedor">
        <h2 class="titulo-seccion titulo-seccion--claro" id="titulo-servicios">Servicios</h2>
        <div class="servicios__grid">
            <?php foreach ($servicios as $s): ?>
                <article class="servicio">
                    <span class="servicio__icono"><?= icono($s['icono']) ?></span>
                    <h3 class="servicio__titulo"><?= e(mb_strtoupper($s['titulo'])) ?></h3>
                    <p><?= e($s['texto']) ?></p>
                    <p class="servicio__pasos"><?= e($s['pasos']) ?></p>
                    <a class="boton" href="/servicios/<?= e($s['clave']) ?>">Más info<span class="sr-only"> sobre <?= e($s['titulo']) ?></span></a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($destacados): ?>
<section class="seccion destacados" aria-labelledby="titulo-destacados">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-destacados">Propiedades destacadas</h2>
        <p class="subtitulo-seccion">Algunas de las propiedades que actualmente comercializamos para nuestros clientes.</p>
        <div class="destacados__grid">
            <?php foreach ($destacados as $item) {
                componente('card-destacado', ['item' => $item]);
            } ?>
        </div>
        <div class="destacados__cierre">
            <p>Hacemos lo mismo por su propiedad. <a href="/ofrezca-su-propiedad">Contáctenos</a> y la incluimos en nuestras propiedades destacadas.</p>
            <a class="boton boton--grande" href="<?= e(URL_TOKKO_PROPIEDADES) ?>" target="_blank" rel="noopener">Ver todas las propiedades <?= icono('externo', 'icono icono--mini') ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($testimonios): ?>
<section class="seccion seccion--gris testimonios" aria-labelledby="titulo-testimonios">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-testimonios">Clientes que han confiado en nosotros</h2>
        <p class="subtitulo-seccion">Testimonios reales de clientes que han vendido, comprado o rentado una propiedad con Du’an Bienes Raíces.</p>
        <div class="carrusel" data-carrusel aria-roledescription="carrusel" aria-label="Testimonios">
            <ul class="carrusel__pista">
                <?php foreach ($testimonios as $item) {
                    componente('testimonio', ['item' => $item]);
                } ?>
            </ul>
            <?php if (count($testimonios) > 1): ?>
                <div class="carrusel__controles">
                    <button class="carrusel__flecha" type="button" data-anterior aria-label="Testimonio anterior"><?= icono('flecha-izq') ?></button>
                    <div class="puntos" data-puntos></div>
                    <button class="carrusel__flecha" type="button" data-siguiente aria-label="Testimonio siguiente"><?= icono('flecha-der') ?></button>
                </div>
            <?php endif; ?>
        </div>
        <p class="testimonios__google">
            <a class="boton boton--contorno" href="<?= e(URL_RESENAS_GOOGLE) ?>" target="_blank" rel="noopener"><?= icono('google', 'icono') ?> Ver todas las opiniones en Google</a>
        </p>
    </div>
</section>
<?php endif; ?>

<?php if ($videos): ?>
<section class="seccion videos" aria-labelledby="titulo-videos">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-videos">Conoce estas propiedades</h2>
        <ul class="videos__grid">
            <?php foreach ($videos as $item) {
                componente('video', ['item' => $item]);
            } ?>
        </ul>
        <p class="centrado">
            <a class="boton boton--contorno" href="<?= e(REDES['youtube']) ?>" target="_blank" rel="noopener"><?= icono('youtube') ?> Ver canal completo</a>
        </p>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
