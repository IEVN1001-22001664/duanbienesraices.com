<?php
/**
 * Diapositiva del slider principal.
 * Variables: $item (elemento de hero.json), $indice (0 = primera), $total.
 * El video de fondo se carga en pantallas grandes y sin "reducir movimiento" (main.js). Los videos
 * ligeros (menos de VIDEO_HERO_LIGERO bytes, p. ej. la intro con el logo) también se cargan en celular.
 */
$esPrimera = ($indice ?? 0) === 0;
$tieneVideo = !empty($item['video']);
$pesoVideo = $tieneVideo ? (int) @filesize(RAIZ_WEB . '/' . ltrim((string) $item['video'], '/')) : 0;
$videoLigero = $pesoVideo > 0 && $pesoVideo < VIDEO_HERO_LIGERO;
?>
<li class="hero__slide<?= $esPrimera ? ' es-activa' : '' ?>" data-slide
    role="group" aria-roledescription="diapositiva" aria-label="<?= e(($indice + 1) . ' de ' . $total) ?>"
    <?= $esPrimera ? '' : 'aria-hidden="true"' ?>>
    <img class="hero__fondo" src="<?= e(ruta_upload($item['imagen'])) ?>" alt="<?= e($item['alt'] ?? '') ?>"
         width="1920" height="1080" <?= $esPrimera ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
    <?php if ($tieneVideo): ?>
        <video class="hero__video" data-src="<?= e(ruta_upload($item['video'])) ?>" muted playsinline preload="none" aria-hidden="true"<?= $videoLigero ? ' data-ligero' : '' ?>></video>
    <?php endif; ?>

    <?php if (!empty($item['titulo'])): ?>
        <div class="hero__texto contenedor">
            <p class="hero__titulo"><?= e($item['titulo']) ?></p>
            <?php if (!empty($item['subtitulo'])): ?>
                <p class="hero__subtitulo"><?= e($item['subtitulo']) ?></p>
            <?php endif; ?>
            <?php if (!empty($item['enlace']) && !empty($item['texto_boton'])): ?>
                <a class="boton" href="<?= e($item['enlace']) ?>"<?= attr_externo($item['enlace']) ?>><?= e($item['texto_boton']) ?></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</li>
