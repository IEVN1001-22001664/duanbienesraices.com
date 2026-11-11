<?php
/**
 * Listado de propiedades (/propiedades), alimentado por la copia local de Tokko
 * (data/tokko/propiedades.json, ver includes/tokko.php).
 *
 * Los filtros van por GET para que funcionen sin JavaScript y se puedan compartir;
 * main.js solo los aplica al cambiar una lista. 12 por página.
 */
require dirname(__DIR__) . '/includes/config.php';

$todas = tokko_propiedades();
$f = filtros_propiedades($_GET);
$resultado = filtrar_propiedades($todas, $f);
$total = count($resultado);
$paginas = max(1, (int) ceil($total / PROPIEDADES_POR_PAGINA));
$actual = min($paginas, max(1, (int) ($_GET['pagina'] ?? 1)));
$visibles = array_slice($resultado, ($actual - 1) * PROPIEDADES_POR_PAGINA, PROPIEDADES_POR_PAGINA);

$tipos = opciones_propiedades($todas, 'tipo');
$ciudades = opciones_propiedades($todas, 'ciudad');

/** URL de la página $numero del listado con los filtros actuales (no se llama $url: header.php usa esa variable). */
$urlPagina = function (int $numero) use ($f): string {
    $q = array_filter($f, fn($v, $k) => $v !== '' && $v !== null && !($k === 'orden' && $v === 'recientes'), ARRAY_FILTER_USE_BOTH);
    if ($numero > 1) {
        $q['pagina'] = $numero;
    }
    return '/propiedades' . ($q ? '?' . http_build_query($q) : '');
};

$pagina = [
    'titulo'      => "Propiedades en venta y renta en León, Gto. | Du'an Bienes Raíces",
    'descripcion' => "Casas, departamentos, terrenos, bodegas y oficinas en venta y renta en León, Guanajuato. {$total} propiedades disponibles con Du'an Bienes Raíces.",
    'ruta'        => $actual > 1 && !hay_filtros($f) ? '/propiedades?pagina=' . $actual : '/propiedades',
    'activo'      => 'propiedades',
    'imagen'      => '/assets/img/servicio-venta.webp',
    // Las combinaciones de filtros no se indexan (contenido duplicado); el listado y sus páginas sí.
    'noindex'     => hay_filtros($f) || $f['orden'] !== 'recientes',
    'jsonld'      => [[
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => 'Propiedades en venta y renta',
        'numberOfItems'   => $total,
        'itemListElement' => array_map(fn($p, $i) => [
            '@type'    => 'ListItem',
            'position' => ($actual - 1) * PROPIEDADES_POR_PAGINA + $i + 1,
            'url'      => url_absoluta(url_propiedad($p)),
            'name'     => $p['titulo'],
        ], $visibles, array_keys($visibles)),
    ]],
];

tokko_refrescar_al_terminar();

require dirname(__DIR__) . '/includes/head.php';
require dirname(__DIR__) . '/includes/header.php';
?>

<section class="portada portada--baja" style="--fondo: url('/assets/img/servicio-venta.webp')">
    <div class="contenedor portada__texto">
        <p class="portada__antetitulo">Venta y renta en León y alrededores</p>
        <h1 class="portada__titulo">Propiedades</h1>
        <p class="portada__lead">Encuentre casa, departamento, terreno, bodega u oficina con el respaldo de Du'an Bienes Raíces.</p>
    </div>
</section>

<section class="seccion propiedades" aria-labelledby="titulo-resultados">
    <div class="contenedor">
        <form class="filtros-propiedades" action="/propiedades" method="get" role="search" aria-label="Filtrar propiedades" data-filtros-propiedades>
            <div class="filtros-propiedades__operacion" role="radiogroup" aria-label="Operación">
                <?php foreach (['' => 'Todas', 'venta' => 'Venta', 'renta' => 'Renta'] as $valor => $texto): ?>
                    <label class="chip">
                        <input type="radio" name="operacion" value="<?= e($valor) ?>" <?= $f['operacion'] === $valor ? 'checked' : '' ?>>
                        <span><?= e($texto) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="filtros-propiedades__campos">
                <div class="campo campo--buscar">
                    <label for="f-q">Buscar</label>
                    <input id="f-q" type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Colonia, fraccionamiento o código" autocomplete="off">
                </div>
                <div class="campo">
                    <label for="f-tipo">Tipo</label>
                    <select id="f-tipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $valor => $n): ?>
                            <option value="<?= e($valor) ?>" <?= $f['tipo'] === $valor ? 'selected' : '' ?>><?= e($valor) ?> (<?= $n ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="f-ciudad">Ciudad</label>
                    <select id="f-ciudad" name="ciudad">
                        <option value="">Todas</option>
                        <?php foreach ($ciudades as $valor => $n): ?>
                            <option value="<?= e($valor) ?>" <?= $f['ciudad'] === $valor ? 'selected' : '' ?>><?= e($valor) ?> (<?= $n ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="f-recamaras">Recámaras</label>
                    <select id="f-recamaras" name="recamaras">
                        <option value="">Cualquiera</option>
                        <?php foreach (RECAMARAS_FILTRO as $n): ?>
                            <option value="<?= $n ?>" <?= $f['recamaras'] === $n ? 'selected' : '' ?>><?= $n ?> o más</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="campo campo--precio">
                    <span class="campo__etiqueta" id="f-precio">Precio (MXN)</span>
                    <div class="campo__par" role="group" aria-labelledby="f-precio">
                        <input type="number" name="min" value="<?= e($f['min']) ?>" min="0" step="10000" inputmode="numeric" placeholder="Mínimo" aria-label="Precio mínimo">
                        <input type="number" name="max" value="<?= e($f['max']) ?>" min="0" step="10000" inputmode="numeric" placeholder="Máximo" aria-label="Precio máximo">
                    </div>
                </div>
            </div>
            <div class="filtros-propiedades__acciones">
                <button class="boton" type="submit"><?= icono('filtro') ?> Buscar</button>
                <?php if (hay_filtros($f)): ?>
                    <a class="boton boton--contorno" href="/propiedades">Limpiar filtros</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="propiedades__barra">
            <h2 class="propiedades__conteo" id="titulo-resultados" aria-live="polite">
                <?= $total === 1 ? '1 propiedad' . (hay_filtros($f) ? ' encontrada' : ' disponible') : $total . ' propiedades' . (hay_filtros($f) ? ' encontradas' : ' disponibles') ?>
            </h2>
            <div class="campo campo--orden">
                <label for="f-orden">Ordenar por</label>
                <select id="f-orden" name="orden" form="filtros-orden" data-orden>
                    <option value="recientes" <?= $f['orden'] === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                    <option value="precio-asc" <?= $f['orden'] === 'precio-asc' ? 'selected' : '' ?>>Menor precio</option>
                    <option value="precio-desc" <?= $f['orden'] === 'precio-desc' ? 'selected' : '' ?>>Mayor precio</option>
                </select>
            </div>
        </div>
        <?php /* El orden viaja con los filtros actuales (formulario aparte para no mover el diseño) */ ?>
        <form id="filtros-orden" action="/propiedades" method="get" hidden>
            <?php foreach (array_filter($f, fn($v, $k) => $k !== 'orden' && $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH) as $k => $v): ?>
                <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <noscript><button type="submit">Ordenar</button></noscript>
        </form>

        <?php if ($visibles): ?>
            <div class="propiedades__grid">
                <?php foreach ($visibles as $prop) {
                    componente('card-propiedad', ['prop' => $prop, 'operacion' => $f['operacion']]);
                } ?>
            </div>

            <?php if ($paginas > 1): ?>
                <nav class="paginacion" aria-label="Páginas de resultados">
                    <?php if ($actual > 1): ?>
                        <a class="paginacion__flecha" href="<?= e($urlPagina($actual - 1)) ?>" rel="prev"><?= icono('flecha-izq') ?><span class="sr-only">Página anterior</span></a>
                    <?php endif; ?>
                    <?php for ($n = 1; $n <= $paginas; $n++): ?>
                        <?php if ($n === 1 || $n === $paginas || abs($n - $actual) <= 1): ?>
                            <a class="paginacion__numero" href="<?= e($urlPagina($n)) ?>" <?= $n === $actual ? 'aria-current="page"' : '' ?>><?= $n ?></a>
                        <?php elseif (abs($n - $actual) === 2): ?>
                            <span class="paginacion__salto" aria-hidden="true">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($actual < $paginas): ?>
                        <a class="paginacion__flecha" href="<?= e($urlPagina($actual + 1)) ?>" rel="next"><?= icono('flecha-der') ?><span class="sr-only">Página siguiente</span></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="propiedades__vacio">
                <p><strong>No encontramos propiedades con esos filtros.</strong></p>
                <p>Pruebe con menos filtros o cuéntenos qué busca: le avisamos en cuanto tengamos algo para usted.</p>
                <div class="propiedades__vacio-acciones">
                    <a class="boton boton--contorno" href="/propiedades">Ver todas</a>
                    <a class="boton boton--whatsapp" href="<?= e(whatsapp_url('¡Hola! Estoy buscando una propiedad y no encontré lo que necesito en su sitio. Busco…')) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> Escribir por WhatsApp</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
