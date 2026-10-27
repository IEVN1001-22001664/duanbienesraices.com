<?php
/**
 * Video de YouTube con "lite embed": se muestra la miniatura y el iframe
 * solo se carga al hacer clic (ahorra ~500 KB por video).
 * Variables: $item (elemento de videos.json).
 */
$id = (string) $item['youtube_id'];
$titulo = $item['titulo'] ?? 'Video de Du\'an Bienes Raíces';
?>
<li class="video">
    <div class="video__marco" data-youtube="<?= e($id) ?>" data-titulo="<?= e($titulo) ?>">
        <img class="video__miniatura" src="https://i.ytimg.com/vi/<?= e($id) ?>/hqdefault.jpg" alt=""
             width="480" height="360" loading="lazy" decoding="async">
        <a class="video__play" href="https://www.youtube.com/watch?v=<?= e($id) ?>" target="_blank" rel="noopener">
            <?= icono('play') ?><span class="sr-only">Reproducir video: <?= e($titulo) ?></span>
        </a>
    </div>
    <p class="video__titulo"><?= e($titulo) ?></p>
</li>
