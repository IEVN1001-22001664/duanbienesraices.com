<?php
/**
 * sitemap.xml dinámico (ver regla en .htaccess). Incluye los artículos del blog
 * automáticamente, así no hay que editarlo al publicar.
 */
require __DIR__ . '/includes/config.php';

$fijas = [
    ['/',                          '1.0', 'weekly'],
    ['/empresa',                   '0.8', 'monthly'],
    ['/servicios/venta',           '0.8', 'monthly'],
    ['/servicios/renta',           '0.8', 'monthly'],
    ['/servicios/administracion',  '0.8', 'monthly'],
    ['/ofrezca-su-propiedad',      '0.9', 'monthly'],
    ['/blog',                      '0.7', 'weekly'],
    ['/contacto',                  '0.7', 'yearly'],
    ['/aviso-de-privacidad',       '0.2', 'yearly'],
];

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($fijas as [$ruta, $prioridad, $frecuencia]): ?>
    <url>
        <loc><?= e(url_absoluta($ruta)) ?></loc>
        <changefreq><?= $frecuencia ?></changefreq>
        <priority><?= $prioridad ?></priority>
    </url>
<?php endforeach; ?>
<?php foreach (obtener_posts() as $post): ?>
    <url>
        <loc><?= e(url_absoluta('/blog/' . $post['slug'])) ?></loc>
        <lastmod><?= e(date('Y-m-d', strtotime($post['actualizado'] ?? $post['fecha']))) ?></lastmod>
        <changefreq>yearly</changefreq>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>
</urlset>
