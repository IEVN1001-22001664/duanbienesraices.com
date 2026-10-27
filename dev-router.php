<?php
/**
 * Router SOLO para desarrollo local con el servidor integrado de PHP,
 * que no lee .htaccess. Imita las URLs limpias y las 301 principales.
 *
 *   php -S localhost:8000 -t public_html dev-router.php
 *
 * En SiteGround no se usa: ahí manda public_html/.htaccess.
 */

$web = __DIR__ . '/public_html';
$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Archivos estáticos reales (css, js, imágenes, videos)
if ($ruta !== '/' && is_file($web . $ruta) && !str_ends_with($ruta, '.php')) {
    if (preg_match('#^/uploads/.*\.php#i', $ruta) || preg_match('#\.(json|md)$#i', $ruta)) {
        http_response_code(403);
        exit;
    }
    return false;
}

$redirigir = function (string $destino): never {
    header('Location: ' . $destino, true, 301);
    exit;
};

if (preg_match('#^/post/(.+)$#', $ruta, $m)) {
    $redirigir('/blog/' . rawurlencode($m[1]));
}
$mapa301 = [
    '/copia-de-venta' => '/servicios/renta',
    '/copia-de-renta' => '/servicios/venta',
    '/copia-de-renta-1' => '/servicios/administracion',
    '/bienesraices' => '/',
];
if (isset($mapa301[$ruta])) {
    $redirigir($mapa301[$ruta]);
}
if (preg_match('#^/(includes|components)/#', $ruta)) {
    http_response_code(403);
    exit;
}

$ejecutar = function (string $archivo, array $get = []) use ($web): void {
    $_GET = array_merge($_GET, $get);
    $_SERVER['SCRIPT_NAME'] = '/' . ltrim(str_replace($web, '', $archivo), '/');
    chdir(dirname($archivo));
    require $archivo;
};

if ($ruta === '/') {
    $ejecutar($web . '/index.php');
} elseif ($ruta === '/blog' || $ruta === '/blog/') {
    $ejecutar($web . '/blog/index.php');
} elseif (preg_match('#^/blog/([^/]+)$#', $ruta, $m)) {
    $ejecutar($web . '/blog/articulo.php', ['slug' => $m[1]]);
} elseif ($ruta === '/sitemap.xml') {
    $ejecutar($web . '/sitemap.php');
} elseif (is_file($web . rtrim($ruta, '/') . '.php')) {
    $ejecutar($web . rtrim($ruta, '/') . '.php');
} elseif (str_ends_with($ruta, '.php') && is_file($web . $ruta)) {
    $ejecutar($web . $ruta);
} else {
    http_response_code(404);
    $ejecutar($web . '/404.php');
}
