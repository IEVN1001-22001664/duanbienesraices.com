<?php
/**
 * Ficha de una propiedad: /propiedades/{slug}-{id} (ver .htaccess y dev-router.php).
 *
 * El id de Tokko va al final de la URL; si el título cambió en Tokko, la URL vieja
 * redirige con 301 a la nueva. Si la propiedad ya no está activa, responde 404.
 * Fotos: la galería usa las fotos de Tokko (1200 px) con carga diferida; solo la
 * portada del listado se copia al servidor.
 */
require dirname(__DIR__) . '/includes/config.php';

$slug = (string) ($_GET['slug'] ?? '');
$prop = preg_match('/-(\d+)$/', $slug, $m) ? tokko_propiedad((int) $m[1]) : null;
if (!$prop) {
    tokko_refrescar_al_terminar();
    no_encontrado();
}
if ($prop['slug'] !== $slug) {
    header('Location: ' . url_propiedad($prop), true, 301);
    exit;
}

$url = url_propiedad($prop);
$urlAbsoluta = url_absoluta($url);
$op = $prop['operaciones'][0];
$fotos = array_values(array_filter($prop['fotos'], fn($f) => empty($f['plano'])));
$planos = array_values(array_filter($prop['fotos'], fn($f) => !empty($f['plano'])));
$galeria = array_merge($fotos, $planos);
$portada = portada_propiedad($prop) ?? ($fotos[0]['url'] ?? null);
$datos = datos_clave($prop, true);
$parrafos = parrafos_descripcion($prop['descripcion']);
$esConstruccion = !in_array($prop['tipo'], ['Terreno', 'Campo', 'Quinta'], true);

// Características secundarias (solo las que Tokko trae)
$caracteristicas = array_filter([
    'Tipo'             => $prop['tipo'],
    'Código'           => $prop['codigo'],
    'Operación'        => implode(' y ', array_map(fn($o) => $o['tipo'] === 'renta' ? 'Renta' : 'Venta', $prop['operaciones'])),
    'Construcción'     => formato_m2($prop['construccion_m2']),
    'Terreno'          => formato_m2($prop['terreno_m2']),
    'Frente × fondo'   => $prop['frente_m'] && $prop['fondo_m'] ? rtrim(rtrim(number_format($prop['frente_m'], 2), '0'), '.') . ' × ' . rtrim(rtrim(number_format($prop['fondo_m'], 2), '0'), '.') . ' m' : '',
    'Recámaras'        => $prop['recamaras'] ? (string) $prop['recamaras'] : '',
    'Baños completos'  => $prop['banos'] ? (string) $prop['banos'] : '',
    'Medios baños'     => $prop['medios_banos'] ? (string) $prop['medios_banos'] : '',
    'Estacionamientos' => $prop['estacionamientos'] ? (string) $prop['estacionamientos'] : '',
    'Plantas'          => $prop['plantas'] ? (string) $prop['plantas'] : '',
    'Antigüedad'       => $esConstruccion && $prop['antiguedad'] !== null
        ? ($prop['antiguedad'] === 0 ? 'A estrenar' : $prop['antiguedad'] . ($prop['antiguedad'] === 1 ? ' año' : ' años')) : '',
    'Estado'           => $prop['condicion'],
]);
$gruposAmenidades = array_filter([
    'Espacios'    => $prop['amenidades']['espacios'] ?? [],
    'Amenidades'  => $prop['amenidades']['adicionales'] ?? [],
    'Servicios'   => $prop['amenidades']['servicios'] ?? [],
]);

// Propiedades parecidas: mismo tipo y operación; si no alcanzan, misma operación.
$similares = array_filter(tokko_propiedades(), fn($p) => $p['id'] !== $prop['id']
    && in_array($op['tipo'], array_column($p['operaciones'], 'tipo'), true));
usort($similares, fn($a, $b) => [($b['tipo'] === $prop['tipo']), ($b['ciudad'] === $prop['ciudad'])]
    <=> [($a['tipo'] === $prop['tipo']), ($a['ciudad'] === $prop['ciudad'])]);
$similares = array_slice($similares, 0, 3);

$mensajeWhatsapp = "¡Hola! Me interesa la propiedad {$prop['codigo']}: {$prop['titulo']}. {$urlAbsoluta}";
$urlAgendar = URL_AGENDAR_CITA !== ''
    ? URL_AGENDAR_CITA . (str_contains(URL_AGENDAR_CITA, '?') ? '&' : '?') . http_build_query([
        'notes'     => "Propiedad {$prop['codigo']}: {$prop['titulo']} — {$urlAbsoluta}",
        // Campo oculto «propiedad» del formulario de reserva de Cal.com; se usa en el nombre del evento del calendario
        'propiedad' => "{$prop['codigo']} – {$prop['titulo']}",
    ])
    : whatsapp_url("¡Hola! Quiero agendar una cita para ver la propiedad {$prop['codigo']}: {$prop['titulo']}. {$urlAbsoluta}");
$descripcionSeo = extracto(implode(' ', $parrafos) ?: $prop['titulo'], 155);

$pagina = [
    'titulo'      => $prop['titulo'] . " | Du'an Bienes Raíces",
    'descripcion' => $descripcionSeo,
    'ruta'        => $url,
    'activo'      => 'propiedades',
    'imagen'      => $portada ?? '/assets/img/og-default.jpg',
    'precarga'    => $fotos[0]['url'] ?? null,
    'jsonld'      => [
        array_filter([
            '@context'    => 'https://schema.org',
            '@type'       => 'RealEstateListing',
            'name'        => $prop['titulo'],
            'url'         => $urlAbsoluta,
            'description' => $descripcionSeo,
            'image'       => array_slice(array_column($fotos, 'url'), 0, 6),
            'datePosted'  => $prop['creada'] ? substr($prop['creada'], 0, 10) : null,
            'offers'      => $prop['precio_visible'] && $op['precio'] > 0 ? [
                '@type'         => 'Offer',
                'price'         => $op['precio'],
                'priceCurrency' => $op['moneda'],
                'availability'  => 'https://schema.org/InStock',
                'businessFunction' => $op['tipo'] === 'renta' ? 'http://purl.org/goodrelations/v1#LeaseOut' : 'http://purl.org/goodrelations/v1#Sell',
                'seller'        => ['@id' => URL_SITIO . '/#negocio'],
            ] : null,
            'contentLocation' => ['@type' => 'Place', 'name' => $prop['ubicacion_completa']],
        ]),
        [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Propiedades', 'item' => url_absoluta('/propiedades')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $prop['titulo'], 'item' => $urlAbsoluta],
            ],
        ],
    ],
];

tokko_refrescar_al_terminar();

require dirname(__DIR__) . '/includes/head.php';
require dirname(__DIR__) . '/includes/header.php';
?>

<article class="ficha">
    <div class="contenedor">
        <div class="ficha__barra">
            <?php /* Si se llegó desde el listado, main.js regresa con el historial (conserva filtros, página y
                     posición); si no, la liga abre /propiedades. En celular es más discreto: ahí se regresa deslizando. */ ?>
            <a class="ficha__regresar" href="/propiedades" data-regresar>
                <?= icono('flecha-izq') ?> <span>Regresar<span class="ficha__regresar-extra"> a la búsqueda</span></span>
            </a>
            <nav class="ficha__migas" aria-label="Ubicación en el sitio">
                <a href="/propiedades">Propiedades</a> <span aria-hidden="true">›</span>
                <a href="/propiedades?<?= e(http_build_query(['tipo' => $prop['tipo']])) ?>"><?= e($prop['tipo']) ?></a> <span aria-hidden="true">›</span>
                <span aria-current="page"><?= e($prop['codigo']) ?></span>
            </nav>
        </div>

        <?php if ($galeria): ?>
            <div class="ficha__galeria galeria" data-galeria data-ficha-galeria>
                <span class="etiqueta etiqueta--<?= e($op['tipo']) ?>"><?= $op['tipo'] === 'renta' ? 'RENTA' : 'VENTA' ?></span>
                <ul class="galeria__pista" id="galeria-ficha" tabindex="0" aria-label="<?= e('Fotos de ' . $prop['titulo']) ?>">
                    <?php foreach ($galeria as $i => $foto): ?>
                        <li class="galeria__foto<?= $foto['plano'] ? ' galeria__foto--plano' : '' ?>">
                            <img src="<?= e($foto['url']) ?>"
                                 alt="<?= e(($foto['plano'] ? 'Plano de ' : '') . $prop['titulo'] . ' — ' . ($i + 1) . ' de ' . count($galeria)) ?>"
                                 width="1200" height="900" decoding="async" referrerpolicy="no-referrer"
                                 <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($galeria) > 1): ?>
                    <button class="galeria__flecha galeria__flecha--izq" type="button" aria-controls="galeria-ficha" aria-label="Foto anterior" hidden><?= icono('flecha-izq') ?></button>
                    <button class="galeria__flecha galeria__flecha--der" type="button" aria-controls="galeria-ficha" aria-label="Foto siguiente" hidden><?= icono('flecha-der') ?></button>
                    <span class="galeria__contador" aria-hidden="true"><span data-actual>1</span>/<?= count($galeria) ?></span>
                <?php endif; ?>
            </div>
            <?php if (count($galeria) > 1): ?>
                <ul class="ficha__miniaturas" aria-label="Elegir foto" data-miniaturas>
                    <?php foreach ($galeria as $i => $foto): ?>
                        <li>
                            <button type="button" data-ir="<?= $i ?>" aria-label="<?= e('Ver foto ' . ($i + 1)) ?>" <?= $i === 0 ? 'aria-current="true"' : '' ?>>
                                <img src="<?= e($foto['mini'] ?: $foto['url']) ?>" alt="" width="120" height="90" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                                <?php if ($foto['plano']): ?><span class="ficha__miniatura-plano">Plano</span><?php endif; ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>

        <?php /* Arriba: título y datos principales a la izquierda y la tarjeta de contacto a la derecha,
                 con la misma altura. Abajo, a todo lo ancho, el resto de los bloques. */ ?>
        <div class="ficha__grid">
            <div class="ficha__resumen">
                <header class="ficha__encabezado">
                    <p class="ficha__antetitulo"><?= e($prop['tipo']) ?> en <?= e(implode(' y ', array_map(fn($o) => $o['tipo'], $prop['operaciones']))) ?> · <?= e($prop['codigo']) ?></p>
                    <h1 class="ficha__titulo"><?= e($prop['titulo']) ?></h1>
                    <p class="ficha__ubicacion"><?= icono('ubicacion') ?> <?= e($prop['ubicacion_completa']) ?></p>
                    <div class="ficha__precios">
                        <?php foreach ($prop['operaciones'] as $o): ?>
                            <p class="ficha__precio"><span><?= $o['tipo'] === 'renta' ? 'Renta' : 'Venta' ?></span> <?= e(precio_propiedad($prop, $o)) ?></p>
                        <?php endforeach; ?>
                    </div>
                </header>

                <?php if ($datos): ?>
                    <ul class="ficha__datos" aria-label="Datos principales">
                        <?php foreach ($datos as [$icono, $texto]): ?>
                            <li><?= icono($icono) ?><span><?= e($texto) ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <aside class="ficha__lateral" aria-label="Contacto sobre esta propiedad">
                <div class="ficha__contacto">
                    <p class="ficha__contacto-texto">¿Le interesa? Agende una visita o pregunte lo que necesite.</p>
                    <a class="boton boton--whatsapp boton--bloque" href="<?= e(whatsapp_url($mensajeWhatsapp)) ?>" target="_blank" rel="noopener" data-whatsapp-propiedad>
                        <?= icono('whatsapp') ?> Preguntar por WhatsApp
                    </a>
                    <a class="boton boton--bloque ficha__agendar" href="<?= e($urlAgendar) ?>" target="_blank" rel="noopener" data-agendar-cita="<?= URL_AGENDAR_CITA !== '' ? 'cal' : 'whatsapp' ?>">
                        <?= icono('calendario') ?> Agendar visita
                    </a>
                    <div class="ficha__compartir">
                        <p>Compartir:</p>
                        <ul class="compartir">
                            <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($urlAbsoluta)) ?>" target="_blank" rel="noopener"><?= icono('facebook') ?> Facebook</a></li>
                            <li><a href="https://api.whatsapp.com/send?text=<?= e(rawurlencode($prop['titulo'] . ' ' . $urlAbsoluta)) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> WhatsApp</a></li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>

        <div class="ficha__cuerpo">
                <?php if ($parrafos): ?>
                    <section class="ficha__bloque" aria-labelledby="titulo-descripcion">
                        <h2 class="ficha__subtitulo" id="titulo-descripcion">Descripción</h2>
                        <?php /* main.js la corta a 6 líneas y muestra «Ver más» solo si sobra texto; sin JS se ve completa */ ?>
                        <div class="ficha__descripcion" id="ficha-descripcion" data-plegable="6">
                            <?php foreach ($parrafos as $parrafo): ?>
                                <p><?= e($parrafo) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <button class="plegable__boton" type="button" aria-expanded="false" aria-controls="ficha-descripcion" hidden>
                            <span data-plegable-texto>Ver más</span> <?= icono('flecha-arriba') ?>
                        </button>
                    </section>
                <?php endif; ?>

                <section class="ficha__bloque" aria-labelledby="titulo-caracteristicas">
                    <h2 class="ficha__subtitulo" id="titulo-caracteristicas">Características</h2>
                    <dl class="ficha__tabla">
                        <?php foreach ($caracteristicas as $nombre => $valor): ?>
                            <div><dt><?= e($nombre) ?></dt><dd><?= e($valor) ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </section>

                <?php if ($gruposAmenidades): ?>
                    <section class="ficha__bloque" aria-labelledby="titulo-amenidades">
                        <h2 class="ficha__subtitulo" id="titulo-amenidades">Espacios y amenidades</h2>
                        <?php foreach ($gruposAmenidades as $grupo => $lista): ?>
                            <h3 class="ficha__grupo"><?= e($grupo) ?></h3>
                            <ul class="ficha__amenidades">
                                <?php foreach ($lista as $a): ?>
                                    <li><?= icono('check', 'icono icono--mini') ?> <?= e($a) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

                <?php if ($prop['videos']): ?>
                    <section class="ficha__bloque" aria-labelledby="titulo-recorridos">
                        <h2 class="ficha__subtitulo" id="titulo-recorridos">Recorrido virtual y videos</h2>
                        <ul class="ficha__videos">
                            <?php foreach ($prop['videos'] as $v): ?>
                                <li><a class="boton boton--contorno" href="<?= e($v['url']) ?>" target="_blank" rel="noopener">
                                    <?= icono($v['proveedor'] === 'youtube' ? 'play' : 'externo') ?> <?= e($v['titulo'] ?: ($v['proveedor'] === 'matterport' ? 'Recorrido virtual 360°' : 'Ver video')) ?>
                                    <span class="sr-only">(abre en otra pestaña)</span></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="ficha__bloque" aria-labelledby="titulo-zona">
                    <h2 class="ficha__subtitulo" id="titulo-zona">Zona</h2>
                    <p class="texto-suave">El mapa muestra la zona de la propiedad, no su dirección exacta. Con gusto le compartimos la ubicación al agendar una visita.</p>
                    <?php componente('mapa-zona', ['zonaGeo' => $prop['zona_geo'] ?? null, 'zonaNombre' => $prop['ubicacion_completa']]); ?>
                </section>
        </div>
    </div>
</article>

<?php if ($similares): ?>
<section class="seccion seccion--gris" aria-labelledby="titulo-similares">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-similares">Propiedades similares</h2>
        <div class="propiedades__grid propiedades__grid--3">
            <?php foreach ($similares as $p) {
                componente('card-propiedad', ['prop' => $p, 'operacion' => $op['tipo'], 'titulo_nivel' => 'h3']);
            } ?>
        </div>
        <p class="centrado"><a class="boton boton--contorno" href="/propiedades">Ver todas las propiedades</a></p>
    </div>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
