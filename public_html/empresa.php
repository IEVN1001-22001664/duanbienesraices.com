<?php
/**
 * Nosotros (/empresa). Contenido fijo en código.
 * Estructura: hero → mensaje del fundador → pilares de blindaje → misión/visión/valores → cifras + CTA.
 */
require __DIR__ . '/includes/config.php';

$pagina = [
    'titulo'      => "Nosotros | Du'an Bienes Raíces, inmobiliaria en León desde 2013",
    'descripcion' => 'Somos una empresa con más de 10 años de experiencia en el sector. Profesionalismo y credibilidad nos respaldan.',
    'ruta'        => '/empresa',
    'activo'      => 'nosotros',
    'imagen'      => '/assets/img/nosotros-hero.webp',
    'precarga'    => '/assets/img/nosotros-hero.webp',
];

$pilares = [
    ['icono' => 'balanza', 'titulo' => 'Certeza Jurídica',
     'texto' => 'Revisamos la documentación de su propiedad para confirmar que todo esté legalmente en regla y, si hace falta, le orientamos en el trámite necesario. Así cada operación llega segura hasta la firma ante notario.'],
    ['icono' => 'escudo', 'titulo' => 'Valuación Real',
     'texto' => 'Fijamos el precio con base en un estudio comparativo de mercado (opinión de valor) o un avalúo comercial. Un precio adecuado desde el inicio protege el valor de su patrimonio.'],
    ['icono' => 'megafono', 'titulo' => 'Marketing Inmobiliario de Alto Nivel',
     'texto' => 'Fotografía profesional, tomas aéreas con dron, tour virtual 360° y video recorrido, con campañas en redes sociales y en los mejores portales inmobiliarios para llegar al cliente ideal.'],
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
    <div class="contenedor fundador__grid">
        <figure class="fundador__foto">
            <!-- TODO: contenido real — foto del Arq. Alejandro Jaqués Martínez (vertical, mín. 800 px de ancho) -->
            <div class="marcador-foto" role="img" aria-label="Foto del fundador pendiente">
                <img src="/assets/img/logo-duan-bienes-raices.webp" alt="" width="400" height="576" loading="lazy">
            </div>
        </figure>
        <div class="fundador__texto">
            <p class="antetitulo">Mensaje del fundador</p>
            <h2 class="titulo-seccion titulo-seccion--izq" id="titulo-fundador">Su patrimonio, en buenas manos</h2>
            <!-- TODO: contenido real — mensaje del fundador redactado por él (2 a 3 párrafos). -->
            <p>Du’an Bienes Raíces inicia sus operaciones en la ciudad de León, Guanajuato, en el año 2013, al ver la necesidad del mercado de encontrar inmobiliarias y asesores que garanticen a sus clientes confiabilidad y honestidad en todas las operaciones que realizan. A través de estos años nos hemos especializado y mejorado para ofrecer un excelente servicio.</p>
            <p>Apoyamos a nuestros clientes en el proceso de venta y renta a través de asesoría jurídica, estableciendo el valor del inmueble a un precio de mercado adecuado y llevando a cabo una excelente promoción. Contamos con una amplia cartera de clientes interesados y prospectos que buscan comprar o rentar inmuebles.</p>
            <p class="fundador__firma">
                <strong>Arq. Alejandro Jaqués Martínez</strong>
                <span>Fundador y Director</span>
            </p>
        </div>
    </div>
</section>

<section class="seccion seccion--oscura pilares" aria-labelledby="titulo-pilares">
    <div class="contenedor">
        <h2 class="titulo-seccion titulo-seccion--claro" id="titulo-pilares">Pilares de blindaje</h2>
        <p class="subtitulo-seccion subtitulo-seccion--claro">Tres compromisos que protegen cada operación que nos confía.</p>
        <div class="pilares__grid">
            <?php foreach ($pilares as $i => $p): ?>
                <article class="pilar">
                    <span class="pilar__numero" aria-hidden="true">0<?= $i + 1 ?></span>
                    <span class="pilar__icono"><?= icono($p['icono']) ?></span>
                    <h3 class="pilar__titulo"><?= e($p['titulo']) ?></h3>
                    <p><?= e($p['texto']) ?></p>
                </article>
            <?php endforeach; ?>
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
