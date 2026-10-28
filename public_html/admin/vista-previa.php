<?php
/**
 * Vista previa de un elemento sin guardarlo: pinta el componente real del sitio
 * con los datos del formulario dentro del <iframe> de editar.php.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();
verificar_csrf();

$s = seccion_solicitada();
[$item] = recolectar($s, $_POST, [], false);
$item['id'] = 'vista-previa';

// Valores de relleno para que el componente se pueda pintar aunque falten datos
$relleno = match ($s) {
    'destacados'  => ['titulo' => 'Título de la propiedad', 'operacion' => 'venta', 'precio' => null, 'url_tokko' => '#', 'fotos' => []],
    'testimonios' => ['nombre' => 'Nombre del cliente', 'texto' => 'Texto del testimonio…'],
    'videos'      => ['youtube_id' => '', 'titulo' => ''],
    'blog'        => ['titulo' => 'Título del artículo', 'fecha' => date('Y-m-d'), 'contenido' => ''],
    default       => [],
};
foreach ($relleno as $campo => $valor) {
    if (($item[$campo] ?? '') === '' || ($campo === 'precio' && !is_numeric($item[$campo] ?? null))) {
        $item[$campo] = $valor;
    }
}
?>
<!DOCTYPE html>
<html lang="es-MX" class="js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Vista previa</title>
    <link rel="stylesheet" href="<?= e(asset('css/estilos.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin-previa.css')) ?>">
</head>
<body class="previa-cuerpo previa-cuerpo--<?= e($s) ?>">
<?php switch ($s):
    case 'destacados': ?>
        <div class="destacados__grid previa-una"><?php componente('card-destacado', ['item' => $item]); ?></div>
        <?php break;

    case 'hero':
        if (empty($item['imagen'])): ?>
            <p class="previa-vacia">Sube una imagen para ver la diapositiva.</p>
        <?php else: ?>
            <section class="hero" aria-label="Vista previa">
                <ul class="hero__pista"><?php componente('slide-hero', ['item' => $item, 'indice' => 0, 'total' => 1]); ?></ul>
            </section>
        <?php endif;
        break;

    case 'testimonios': ?>
        <div class="carrusel"><ul class="carrusel__pista previa-una"><?php componente('testimonio', ['item' => $item]); ?></ul></div>
        <?php break;

    case 'videos':
        if (empty($item['youtube_id'])): ?>
            <p class="previa-vacia">Pega la liga del video para verlo aquí.</p>
        <?php else: ?>
            <ul class="videos__grid previa-una"><?php componente('video', ['item' => $item]); ?></ul>
        <?php endif;
        break;

    case 'blog': ?>
        <article class="articulo">
            <header class="articulo__encabezado contenedor contenedor--texto">
                <h1 class="articulo__titulo"><?= e($item['titulo']) ?></h1>
                <p class="articulo__meta">
                    <?php if (!empty($item['autor'])): ?><span><?= e($item['autor']) ?></span> · <?php endif; ?>
                    <time datetime="<?= e($item['fecha']) ?>"><?= e(fecha_es((string) $item['fecha'])) ?></time>
                    · <?= tiempo_lectura((string) $item['contenido']) ?> min de lectura
                </p>
            </header>
            <?php if (!empty($item['portada'])): ?>
                <figure class="articulo__portada contenedor contenedor--ancho">
                    <img src="<?= e(ruta_upload($item['portada'])) ?>" alt="<?= e($item['portada_alt'] ?? '') ?>">
                </figure>
            <?php endif; ?>
            <div class="articulo__contenido contenedor contenedor--texto prosa">
                <?= markdown((string) $item['contenido']) /* markdown() escapa el texto */ ?>
            </div>
        </article>
        <?php break;
endswitch; ?>
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
