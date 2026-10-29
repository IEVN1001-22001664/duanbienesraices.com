<?php
/**
 * <head> común a todas las páginas.
 *
 * Espera un arreglo $pagina con:
 *   titulo, descripcion, ruta (p. ej. '/empresa'), imagen (opcional),
 *   tipo ('website' | 'article'), activo (clave del menú), jsonld (opcional, arreglo extra),
 *   noindex (opcional), precarga (opcional, imagen LCP a precargar).
 */

$pagina = array_merge([
    'titulo'      => NOMBRE_SITIO,
    'descripcion' => '',
    'ruta'        => '/',
    'imagen'      => '/assets/img/og-default.jpg',
    'tipo'        => 'website',
    'activo'      => '',
    'jsonld'      => [],
    'noindex'     => false,
    'precarga'    => null,
    'clase_body'  => '',
], $pagina ?? []);

$canonical = url_absoluta($pagina['ruta']);
$imagenOg = url_absoluta($pagina['imagen']);
$jsonld = array_merge([jsonld_negocio()], $pagina['jsonld']);
?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pagina['titulo']) ?></title>
    <meta name="description" content="<?= e($pagina['descripcion']) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($pagina['noindex'] || MODO_DEV): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif; ?>

    <!-- Open Graph / Twitter -->
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="<?= e(NOMBRE_SITIO) ?>">
    <meta property="og:type" content="<?= e($pagina['tipo']) ?>">
    <meta property="og:title" content="<?= e($pagina['titulo']) ?>">
    <meta property="og:description" content="<?= e($pagina['descripcion']) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e($imagenOg) ?>">
    <meta property="fb:admins" content="duan.bienesraices">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pagina['titulo']) ?>">
    <meta name="twitter:description" content="<?= e($pagina['descripcion']) ?>">
    <meta name="twitter:image" content="<?= e($imagenOg) ?>">

    <!-- Verificaciones (heredadas del sitio en Wix) -->
<?php foreach (VERIFICACIONES as $nombre => $valor): ?>
    <meta name="<?= e($nombre) ?>" content="<?= e($valor) ?>">
<?php endforeach; ?>

    <meta name="theme-color" content="#e21c21">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <link rel="preload" href="/assets/fonts/montserrat-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/nunito-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
<?php if ($pagina['precarga']): ?>
    <link rel="preload" href="<?= e($pagina['precarga']) ?>" as="image" fetchpriority="high">
<?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset('css/estilos.css')) ?>">
    <script>
        document.documentElement.classList.add('js');
        // La animación de entrada en celular se muestra una sola vez por visita
        try { if (sessionStorage.getItem('duan-intro')) document.documentElement.classList.add('intro-vista'); } catch (e) {}
    </script>

<?php foreach ($jsonld as $bloque): ?>
    <script type="application/ld+json"><?= json_encode($bloque, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach; ?>
</head>
<body class="<?= e($pagina['clase_body']) ?>">
<div class="intro-movil" aria-hidden="true" data-intro>
    <span class="intro-movil__telon"></span>
    <img class="intro-movil__logo" src="<?= e(asset('img/logo-duan-bienes-raices.webp')) ?>" alt="" width="400" height="576" decoding="async">
</div>
<a class="saltar" href="#contenido">Saltar al contenido</a>
