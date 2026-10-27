<?php
/**
 * Testimonio de cliente (carrusel del inicio).
 * Variables: $item (elemento de testimonios.json).
 * Si no hay liga a la reseña individual, se enlaza al perfil general de Google.
 */
$estrellas = max(1, min(5, (int) ($item['estrellas'] ?? 5)));
$urlResena = !empty($item['url_resena']) ? $item['url_resena'] : URL_RESENAS_GOOGLE;
?>
<li class="testimonio" data-slide>
    <figure class="testimonio__tarjeta">
        <?php if (!empty($item['foto'])): ?>
            <img class="testimonio__foto" src="<?= e(ruta_upload($item['foto'])) ?>" alt="<?= e($item['alt'] ?? $item['nombre']) ?>"
                 width="640" height="900" loading="lazy" decoding="async">
        <?php endif; ?>
        <div class="testimonio__cuerpo">
            <p class="estrellas" aria-label="<?= e($estrellas . ' de 5 estrellas') ?>">
                <?php for ($i = 0; $i < $estrellas; $i++): ?><?= icono('estrella') ?><?php endfor; ?>
            </p>
            <blockquote class="testimonio__texto"><p>“<?= e($item['texto']) ?>”</p></blockquote>
            <figcaption class="testimonio__autor">
                <a href="<?= e($urlResena) ?>" target="_blank" rel="noopener">
                    <?= e($item['nombre']) ?><span class="sr-only"> — ver reseña en Google (abre en otra pestaña)</span>
                </a>
                <span class="testimonio__fuente"><?= icono('google', 'icono icono--mini') ?> Reseña de Google</span>
            </figcaption>
        </div>
    </figure>
</li>
