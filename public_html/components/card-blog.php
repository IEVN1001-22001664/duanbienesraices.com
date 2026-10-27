<?php
/**
 * Tarjeta de artículo para el listado del blog.
 * Variables: $post (artículo de data/blog/*.json).
 */
$url = '/blog/' . $post['slug'];
?>
<article class="tarjeta-blog">
    <a class="tarjeta-blog__imagen" href="<?= e($url) ?>" tabindex="-1" aria-hidden="true">
        <?php if (!empty($post['portada'])): ?>
            <img src="<?= e(ruta_upload($post['portada'])) ?>" alt="" width="800" height="600" loading="lazy" decoding="async">
        <?php endif; ?>
    </a>
    <div class="tarjeta-blog__cuerpo">
        <p class="tarjeta-blog__meta">
            <time datetime="<?= e($post['fecha']) ?>"><?= e(fecha_es($post['fecha'])) ?></time>
            · <?= tiempo_lectura($post['contenido']) ?> min de lectura
        </p>
        <h2 class="tarjeta-blog__titulo"><a href="<?= e($url) ?>"><?= e($post['titulo']) ?></a></h2>
        <p class="tarjeta-blog__resumen"><?= e(extracto($post['resumen'] ?? $post['contenido'], 170)) ?></p>
        <?php if (!empty($post['autor'])): ?>
            <p class="tarjeta-blog__autor">Por <?= e($post['autor']) ?></p>
        <?php endif; ?>
    </div>
</article>
