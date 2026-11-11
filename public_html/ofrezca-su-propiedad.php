<?php
/**
 * Tengo una Propiedad (/ofrezca-su-propiedad).
 * El formulario se conecta a api/enviar.php en la Fase 3 (PHPMailer + anti-spam).
 */
require __DIR__ . '/includes/config.php';

$resultados = obtener_seccion('resultados');

$pagina = [
    'titulo'      => "Tengo una Propiedad: vendemos o rentamos su inmueble | Du'an Bienes Raíces",
    'descripcion' => 'Le apoyamos en todo el proceso de la renta o venta de su propiedad. Solicite una valoración personalizada, gratuita y sin compromiso.',
    'ruta'        => '/ofrezca-su-propiedad',
    'activo'      => 'propietario',
    'imagen'      => '/assets/img/fondo-inventario.webp',
];

// Pilares de blindaje: colores del gráfico triangular (arriba, abajo izq., abajo der.)
$pilares = [
    ['icono' => 'balanza', 'titulo' => 'Certeza Jurídica',
     'texto' => 'Revisamos la documentación de su propiedad para confirmar que todo esté legalmente en regla y, si hace falta, le orientamos en el trámite necesario. Así cada operación llega segura hasta la firma ante notario.'],
    ['icono' => 'escudo', 'titulo' => 'Valuación Real',
     'texto' => 'Fijamos el precio con base en un estudio comparativo de mercado (opinión de valor) o un avalúo comercial. Un precio adecuado desde el inicio protege el valor de su patrimonio.'],
    ['icono' => 'megafono', 'titulo' => 'Marketing Inmobiliario de Alto Nivel',
     'texto' => 'Fotografía profesional, tomas aéreas con dron, tour virtual 360° y video recorrido, con campañas en redes sociales y en los mejores portales inmobiliarios para llegar al cliente ideal.'],
];

$equipos = [
    ['img' => 'equipo-camara-profesional.webp', 'w' => 500, 'h' => 445, 'titulo' => 'Cámara profesional', 'texto' => 'Para fotos y videos de alta calidad', 'alt' => 'Cámara profesional Canon EOS'],
    ['img' => 'equipo-dron.webp',               'w' => 500, 'h' => 286, 'titulo' => 'Dron profesional',   'texto' => 'Para fotos y videos aéreos', 'alt' => 'Dron profesional Mavic Air'],
    ['img' => 'equipo-camara-360.webp',         'w' => 384, 'h' => 512, 'titulo' => 'Cámara 360°',        'texto' => 'Creamos un tour virtual de su propiedad', 'alt' => 'Cámara 360 para tour virtual'],
];

$mensajeWhatsapp = '¡Hola! Vengo de su página web, tengo una propiedad y me interesa venderla o rentarla.';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<section class="portada" style="--fondo: url('/assets/img/fondo-inventario.webp')">
    <div class="contenedor portada__texto">
        <p class="portada__antetitulo">Tengo una propiedad</p>
        <h1 class="portada__titulo">¡Resultados comprobados!</h1>
        <p class="portada__lead">Más de 13 años de experiencia nos respaldan.</p>
        <a class="boton boton--grande" href="#valuacion">Quiero una valoración gratuita</a>
    </div>
</section>

<?php if ($resultados): ?>
<section class="resultados" aria-labelledby="titulo-resultados">
    <div class="contenedor resultados__cabecera">
        <p class="resultados__antetitulo">Resultados comprobados</p>
        <h2 class="resultados__titulo" id="titulo-resultados">Propiedades que ya vendimos y rentamos</h2>
    </div>
    <?php // Cinta infinita: la lista se pinta dos veces y la animación recorre exactamente una copia, así el salto no se nota. ?>
    <div class="resultados__ventana">
        <div class="resultados__pista" style="--total: <?= count($resultados) ?>">
            <?php for ($copia = 0; $copia < 2; $copia++): ?>
                <ul class="resultados__grupo"<?= $copia ? ' aria-hidden="true"' : ' aria-label="Propiedades vendidas y rentadas"' ?>>
                    <?php foreach ($resultados as $item) {
                        componente('card-resultado', ['item' => $item]);
                    } ?>
                </ul>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="seccion como" aria-labelledby="titulo-como">
    <div class="contenedor como__grid">
        <div>
            <h2 class="titulo-seccion titulo-seccion--izq" id="titulo-como">¿Cómo lo hacemos?</h2>
            <p>En Du’an Bienes Raíces le asesoramos en todo el proceso para <strong>vender o rentar</strong> su inmueble al mejor precio, en el menor tiempo posible y sin problemas.</p>
            <p>Somos expertos en la materia: le brindamos toda la asesoría legal y comercial para fijar el precio de su propiedad a través de un estudio comparativo de mercado, <strong>sin costo alguno</strong>.</p>
            <p>Publicamos su inmueble en los <strong>mejores portales</strong> de pago para que su propiedad pueda llegar al cliente ideal.</p>
            <p class="como__enlaces">Conozca el proceso completo:
                <?php foreach (SERVICIOS as $i => $s): ?><?= $i ? ' · ' : ' ' ?><a href="<?= e($s['url']) ?>"><?= e($s['texto']) ?></a><?php endforeach; ?>
            </p>
        </div>
        <div class="especialidades">
            <h3 class="especialidades__titulo">Nos especializamos en inmuebles de tipo:</h3>
            <ul>
                <li><?= icono('casa') ?><div><strong>Residencial</strong><span>Casas, departamentos, terrenos</span></div></li>
                <li><?= icono('ubicacion') ?><div><strong>Terrenos</strong><span>Comerciales, campestres, ranchos</span></div></li>
                <li><?= icono('edificio') ?><div><strong>Comercial</strong><span>Locales, bodegas</span></div></li>
            </ul>
        </div>
    </div>
</section>

<section class="seccion seccion--oscura pilares" aria-labelledby="titulo-pilares">
    <div class="contenedor">
        <h2 class="titulo-seccion titulo-seccion--claro" id="titulo-pilares">Pilares de blindaje</h2>
        <p class="subtitulo-seccion subtitulo-seccion--claro">Tres compromisos que protegen cada operación que nos confía.</p>
        <div class="pilares__diagrama">
            <!-- Triángulo de 3 piezas; en el centro, los resultados que dan los tres pilares juntos -->
            <figure class="pilares__grafico">
                <svg viewBox="0 0 600 520" role="img" aria-labelledby="pilares-grafico-titulo">
                    <title id="pilares-grafico-titulo">Los tres pilares, <?= e(implode(', ', array_column($pilares, 'titulo'))) ?>, dan como fruto los resultados</title>
                    <g style="stroke: var(--carbon)" stroke-width="14" stroke-linejoin="round">
                        <polygon class="pilares__pieza pilares__pieza--1" points="300,20 435,260 300,340 165,260"/>
                        <polygon class="pilares__pieza pilares__pieza--2" points="165,260 300,340 300,500 30,500"/>
                        <polygon class="pilares__pieza pilares__pieza--3" points="435,260 570,500 300,500 300,340"/>
                    </g>
                    <?php
                    // Posición del ícono y del número dentro de cada pieza
                    $marcas = [[300, 105, 300, 222], [148, 370, 148, 462], [452, 370, 452, 462]];
                    foreach ($pilares as $i => $p):
                        [$ix, $iy, $nx, $ny] = $marcas[$i];
                    ?>
                        <use href="<?= e(asset('img/iconos.svg')) ?>#<?= e($p['icono']) ?>" x="<?= $ix - 30 ?>" y="<?= $iy - 30 ?>" width="60" height="60" color="#fff"/>
                        <text class="pilares__num" x="<?= $nx ?>" y="<?= $ny ?>" text-anchor="middle">0<?= $i + 1 ?></text>
                    <?php endforeach; ?>
                    <circle cx="300" cy="340" r="96" style="fill: var(--carbon)"/>
                    <circle cx="300" cy="340" r="84" fill="#fff"/>
                    <circle cx="300" cy="340" r="72" fill="none" style="stroke: var(--rojo)" stroke-width="3" stroke-dasharray="10 7"/>
                    <text class="pilares__centro" x="300" y="349" text-anchor="middle">Resultados</text>
                </svg>
            </figure>
            <?php foreach ($pilares as $i => $p): ?>
                <article class="pilar pilar--<?= $i + 1 ?>">
                    <span class="pilar__numero" aria-hidden="true">0<?= $i + 1 ?></span>
                    <h3 class="pilar__titulo"><?= e($p['titulo']) ?></h3>
                    <p><?= e($p['texto']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="seccion seccion--gris tecnologia" aria-labelledby="titulo-tecnologia">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-tecnologia">Usamos la más alta tecnología para captar su inmueble desde todos los ángulos</h2>
        <ul class="tecnologia__grid">
            <?php foreach ($equipos as $eq): ?>
                <li class="equipo">
                    <img src="/assets/img/<?= e($eq['img']) ?>" alt="<?= e($eq['alt']) ?>" width="<?= $eq['w'] ?>" height="<?= $eq['h'] ?>" loading="lazy" decoding="async">
                    <h3 class="equipo__titulo"><?= e($eq['titulo']) ?></h3>
                    <p><?= e($eq['texto']) ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="seccion ejemplo" aria-labelledby="titulo-ejemplo">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-ejemplo">Así presentamos su propiedad</h2>
        <p class="subtitulo-seccion">Cada inmueble recibe descripción profesional, video, tour virtual y ubicación.</p>
        <article class="ejemplo__tarjeta">
            <header class="ejemplo__encabezado">
                <span class="etiqueta etiqueta--venta">VENTA</span>
                <h3 class="ejemplo__titulo">Casa en Venta El Mayorazgo Residencial, Privada Mayorazgo Corralejo</h3>
                <p class="destacado__precio"><?= e(formato_precio(3250000)) ?></p>
            </header>
            <div class="ejemplo__grid">
                <div>
                    <h4>Descripción</h4>
                    <details class="leer-mas">
                        <summary>
                            <span>Casa en venta en El Mayorazgo Residencial, específicamente en la Privada Mayorazgo Corralejo, la 2.ª privada al acceso al fraccionamiento. Se ubica frente a área verde con gran arbolado y estacionamiento para visitas.</span>
                            <span class="leer-mas__boton">Ver más</span>
                        </summary>
                        <p>La casa mide 7 m de frente x 20 m de largo, cuenta con excelentes acabados, puertas, cocina, pisos, fachada y accesorios. Además cuenta con cisterna de 5,000 l y tanque estacionario.</p>
                        <p>A la entrada de la casa hay un pequeño recibidor, con medio baño de visitas y una bodega debajo de la escalera. El área de sala-comedor está conectada. La cocina cuenta con barra desayunador en granito, súper equipada con estufa con horno, tarja y alacena de muy buen tamaño, espacio para un refrigerador grande y conexión directa con el área de lavado.</p>
                        <p>En la parte de atrás hay un patio con piso en piedra y espacio techado prefabricado que puede servir como oficina para home office o como bodega.</p>
                        <p>En planta alta hay 3 recámaras. La recámara principal, a altura y media, cuenta con minisplit, walk-in closet y baño completo en cristal templado, con WC y regadera independientes. Las otras 2 recámaras son prácticamente espejo: ambas con clóset amplio y vista al jardín trasero; comparten un baño completo con lavamanos exterior.</p>
                        <p>La casa se entrega equipada, con lámparas, espejos, cortineros y algunas persianas; el piso de la cochera se cambió por uno nuevo.</p>
                    </details>
                    <h4>Tour virtual</h4>
                    <a class="tour" href="https://my.matterport.com/show/?m=2vTa6Qq3QCf" target="_blank" rel="noopener">
                        <img src="/assets/img/tour-virtual.webp" alt="Vista previa del tour virtual 360° de la propiedad" width="1000" height="563" loading="lazy" decoding="async">
                        <span class="tour__boton"><?= icono('play') ?> Recorrer en 360°</span>
                    </a>
                </div>
                <div>
                    <h4>Video de la propiedad</h4>
                    <ul class="videos__grid videos__grid--uno">
                        <?php componente('video', ['item' => ['youtube_id' => 'dofkKU11Qdo', 'titulo' => 'Video recorrido: Casa en Venta El Mayorazgo Residencial']]); ?>
                    </ul>
                    <h4>Ubicación</h4>
                    <p><a href="https://www.google.com/maps?q=21.061594,-101.597571" target="_blank" rel="noopener"><?= icono('ubicacion', 'icono icono--mini') ?> Ver ubicación en Google Maps</a></p>
                </div>
            </div>
        </article>
    </div>
</section>

<section class="banda-inventario" style="--fondo: url('/assets/img/fondo-inventario.webp')">
    <div class="contenedor banda-inventario__contenido">
        <p>Consulte aquí nuestro inventario de propiedades que actualmente tenemos a la venta o renta:</p>
        <a class="boton boton--grande" href="/propiedades">Ver propiedades</a>
    </div>
</section>

<section class="seccion formulario-seccion" id="valuacion" aria-labelledby="titulo-valuacion">
    <div class="contenedor formulario-seccion__grid">
        <div>
            <h2 class="titulo-seccion titulo-seccion--izq" id="titulo-valuacion">¿Listo para vender o rentar su propiedad?</h2>
            <p>Deje su propiedad en manos expertas: nuestros asesores inmobiliarios le ayudarán a determinar el valor adecuado de mercado de su propiedad y evaluarán su potencial comercial.</p>
            <p>Introduzca los datos más importantes de la propiedad que desea valuar para obtener una valoración personalizada, gratuita y <strong>sin compromiso</strong>.</p>
            <p class="texto-suave">La información será tratada de forma confidencial y no será comunicada a terceras partes. Consulte nuestro <a href="/aviso-de-privacidad">aviso de privacidad</a>.</p>
        </div>

        <form class="formulario" id="formulario" action="/api/enviar.php" method="post" data-formulario novalidate>
            <h3 class="formulario__titulo">¡Le ayudamos GRATIS a conocer el VALOR de su propiedad!</h3>
            <p class="formulario__desc">Bríndenos la siguiente información respecto a su inmueble y en breve será contactado.</p>
            <input type="hidden" name="formulario" value="propiedad">
            <!-- Honeypot anti-spam: los humanos no lo ven -->
            <div class="hp" aria-hidden="true"><label for="p-web">No llenar</label><input id="p-web" type="text" name="sitio_web" tabindex="-1" autocomplete="off"></div>

            <div class="campo-doble">
                <div class="campo">
                    <label for="p-tipo">Tipo de propiedad <span aria-hidden="true">*</span></label>
                    <select id="p-tipo" name="tipo_propiedad" required>
                        <option value="">Elige uno</option>
                        <?php foreach (TIPOS_PROPIEDAD as $op): ?>
                            <option><?= e($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="p-operacion">Tipo de operación <span aria-hidden="true">*</span></label>
                    <select id="p-operacion" name="operacion" required>
                        <option value="">Elige uno</option>
                        <?php foreach (OPERACIONES as $op): ?>
                            <option><?= e($op) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="campo">
                <label for="p-desc">Descripción de la propiedad</label>
                <textarea id="p-desc" name="descripcion" rows="4" placeholder="Ej. Casa de 3 recámaras en zona norte..."></textarea>
            </div>
            <div class="campo">
                <label for="p-nombre">Nombre <span aria-hidden="true">*</span></label>
                <input id="p-nombre" type="text" name="nombre" autocomplete="name" required>
            </div>
            <div class="campo-doble">
                <div class="campo">
                    <label for="p-email">Correo electrónico</label>
                    <input id="p-email" type="email" name="email" autocomplete="email">
                </div>
                <div class="campo">
                    <label for="p-whatsapp">WhatsApp <span aria-hidden="true">*</span></label>
                    <input id="p-whatsapp" type="tel" name="whatsapp" autocomplete="tel" inputmode="tel" required pattern="[0-9 +()-]{10,}">
                </div>
            </div>
            <p class="formulario__requeridos"><span aria-hidden="true">*</span> Campos obligatorios</p>
            <?php componente('envio-formulario', ['boton' => 'Enviar']); ?>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
