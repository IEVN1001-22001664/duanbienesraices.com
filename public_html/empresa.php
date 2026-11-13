<?php
/**
 * Nosotros (/empresa). La carta del fundador y la galería se editan en el panel
 * (secciones «fundador» y «galeria»); el resto es fijo en código.
 * Estructura: hero → mensaje del fundador → misión/visión/valores → cifras + CTA.
 * Los pilares de blindaje viven en Tengo una propiedad (ofrezca-su-propiedad.php).
 */
require __DIR__ . '/includes/config.php';

$pagina = [
    'titulo'      => "Nosotros | Du'an Bienes Raíces, inmobiliaria en León desde 2013",
    'descripcion' => 'Somos una empresa con más de 13 años de experiencia en el sector. Profesionalismo y credibilidad nos respaldan.',
    'ruta'        => '/empresa',
    'activo'      => 'nosotros',
];
$portada = portada_de('nosotros', ['imagen' => '/assets/img/nosotros-hero.webp', 'antetitulo' => 'Desde 2013 en León, Guanajuato', 'titulo' => 'Nosotros', 'texto' => SLOGAN]);
$pagina['imagen'] = $pagina['precarga'] = $portada['imagen'];

$fundador = obtener_seccion('fundador')[0] ?? null;
$galeria = obtener_seccion('galeria');

$valores = [
    ['Honestidad', 'Ayudar y asegurar a nuestros clientes una venta o renta transparente.'],
    ['Positividad', 'Buscamos el cómo sí de las cosas para llevar a cabo y cumplir nuestros objetivos.'],
    ['Trabajo', 'Hacemos lo que hay que hacer, cuando hay que hacerlo.'],
    ['Perseverancia', 'Hasta alcanzar nuestras metas y objetivos, tanto de la empresa como de nuestros clientes.'],
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<?php componente('portada', ['portada' => $portada]); ?>

<?php if ($fundador): ?>
<section class="seccion fundador" aria-labelledby="titulo-fundador">
    <div class="contenedor">
        <?php componente('fundador', ['item' => $fundador]); ?>
    </div>
</section>
<?php endif; ?>

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

    <?php if ($galeria): ?>
    <div class="contenedor">
        <div class="carrusel carrusel--galeria" data-carrusel aria-roledescription="carrusel" aria-label="Galería de propiedades">
            <ul class="carrusel__pista">
                <?php foreach ($galeria as $foto): ?>
                    <li data-slide><img src="<?= e(ruta_upload($foto['imagen'])) ?>" alt="<?= e($foto['alt']) ?>" width="1500" height="1000" loading="lazy" decoding="async"></li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($galeria) > 1): ?>
                <div class="carrusel__controles">
                    <button class="carrusel__flecha" type="button" data-anterior aria-label="Foto anterior"><?= icono('flecha-izq') ?></button>
                    <div class="puntos" data-puntos></div>
                    <button class="carrusel__flecha" type="button" data-siguiente aria-label="Foto siguiente"><?= icono('flecha-der') ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</section>

<section class="seccion seccion--gris cifras" aria-labelledby="titulo-cifras">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-cifras">Respaldo que se comprueba</h2>
        <?php /* Contador grande: main.js lo anima de 0 a la cifra al verse; sin JS muestra la cifra final */ ?>
        <p class="cifra-destacada">
            <span class="cifra-destacada__num" data-contador="500" data-prefijo="+" aria-hidden="true">+500</span>
            <span class="sr-only">Más de 500</span>
            <span class="cifra-destacada__txt">propiedades VENDIDAS / RENTADAS por Du'an Bienes Raíces</span>
        </p>
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
