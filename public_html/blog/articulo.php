<?php
/**
 * Artículo del blog. URL limpia: /blog/{slug} (ver .htaccess).
 * Si llega el slug anterior de Wix, redirige con 301 al slug nuevo.
 */
require dirname(__DIR__) . '/includes/config.php';

$slug = (string) ($_GET['slug'] ?? '');
$post = $slug !== '' ? obtener_post($slug) : null;
if (!$post) {
    no_encontrado();
}
if ($post['slug'] !== $slug) {
    header('Location: /blog/' . rawurlencode($post['slug']), true, 301);
    exit;
}

$url = '/blog/' . $post['slug'];
$portada = !empty($post['portada']) ? ruta_upload($post['portada']) : null;
$recientes = array_slice(array_values(array_filter(obtener_posts(), fn($p) => $p['slug'] !== $post['slug'])), 0, 3);

$pagina = [
    'titulo'      => $post['titulo'] . " | Blog Du'an Bienes Raíces",
    'descripcion' => $post['descripcion_seo'] ?? extracto($post['resumen'] ?? $post['contenido'], 155),
    'ruta'        => $url,
    'activo'      => 'blog',
    'tipo'        => 'article',
    'imagen'      => $portada ?? '/assets/img/og-default.jpg',
    'jsonld'      => [array_filter([
        '@context'      => 'https://schema.org',
        '@type'         => 'BlogPosting',
        'headline'      => $post['titulo'],
        'description'   => $post['descripcion_seo'] ?? $post['resumen'] ?? '',
        'image'         => $portada ? url_absoluta($portada) : null,
        'datePublished' => $post['fecha'],
        'dateModified'  => $post['actualizado'] ?? $post['fecha'],
        'author'        => ['@type' => 'Person', 'name' => $post['autor'] ?? NOMBRE_SITIO],
        'publisher'     => ['@id' => URL_SITIO . '/#negocio'],
        'mainEntityOfPage' => URL_SITIO . $url,
    ])],
];

require dirname(__DIR__) . '/includes/head.php';
require dirname(__DIR__) . '/includes/header.php';
?>

<article class="articulo">
    <header class="articulo__encabezado contenedor contenedor--texto">
        <p class="articulo__migas"><a href="/blog">Blog</a> <span aria-hidden="true">›</span></p>
        <h1 class="articulo__titulo"><?= e($post['titulo']) ?></h1>
        <p class="articulo__meta">
            <?php if (!empty($post['autor'])): ?><span><?= e($post['autor']) ?></span> · <?php endif; ?>
            <time datetime="<?= e($post['fecha']) ?>"><?= e(fecha_es($post['fecha'])) ?></time>
            · <?= tiempo_lectura($post['contenido']) ?> min de lectura
        </p>
    </header>

    <?php if ($portada): ?>
        <figure class="articulo__portada contenedor contenedor--ancho">
            <img src="<?= e($portada) ?>" alt="<?= e($post['portada_alt'] ?? '') ?>" width="1440" height="1080" fetchpriority="high" decoding="async">
        </figure>
    <?php endif; ?>

    <div class="articulo__contenido contenedor contenedor--texto prosa">
        <?= markdown($post['contenido']) /* markdown() escapa todo el texto internamente */ ?>
    </div>

    <footer class="articulo__pie contenedor contenedor--texto">
        <p>¿Le fue útil? Compártalo:</p>
        <ul class="compartir">
            <li><a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode(URL_SITIO . $url)) ?>" target="_blank" rel="noopener"><?= icono('facebook') ?> Facebook</a></li>
            <li><a href="https://api.whatsapp.com/send?text=<?= e(rawurlencode($post['titulo'] . ' ' . URL_SITIO . $url)) ?>" target="_blank" rel="noopener"><?= icono('whatsapp') ?> WhatsApp</a></li>
        </ul>
    </footer>
</article>

<?php if ($recientes): ?>
<section class="seccion seccion--gris" aria-labelledby="titulo-recientes">
    <div class="contenedor">
        <h2 class="titulo-seccion" id="titulo-recientes">Entradas recientes</h2>
        <div class="blog__grid">
            <?php foreach ($recientes as $p) {
                componente('card-blog', ['post' => $p]);
            } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
