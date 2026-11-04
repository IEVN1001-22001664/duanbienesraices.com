<?php
/**
 * Tarjeta de un resultado (propiedad vendida o rentada) en «Tengo una propiedad».
 * Variables: $item (elemento de resultados.json).
 * La cinta diagonal se dibuja con CSS en la esquina y cubre la que traían las fotos de Wix.
 */
$estado = ($item['estado'] ?? '') === 'rentado' ? 'rentado' : 'vendido';
?>
<li class="resultado" data-resultado>
    <figure class="resultado__tarjeta">
        <img src="<?= e(ruta_upload($item['foto'])) ?>" alt="<?= e($item['titulo']) ?>" width="800" height="800" loading="lazy" decoding="async">
        <span class="resultado__cinta resultado__cinta--<?= $estado ?>"><span><?= $estado === 'rentado' ? 'Rentado' : 'Vendido' ?></span></span>
        <figcaption class="resultado__pie">
            <strong><?= e($item['titulo']) ?></strong>
            <?php if (!empty($item['subtitulo'])): ?><span><?= e($item['subtitulo']) ?></span><?php endif; ?>
        </figcaption>
    </figure>
</li>
