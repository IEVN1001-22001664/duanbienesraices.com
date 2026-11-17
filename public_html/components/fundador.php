<?php
/**
 * Fundador (Nosotros). Editable en el panel: sección «fundador».
 * Si tiene «publicacion» (liga de Instagram) se muestra el embed oficial; si no, la carta con foto y mensaje.
 * Variables: $item (único elemento de fundador.json).
 * El mensaje es texto plano; una línea en blanco separa los párrafos.
 */
// Redes del fundador: solo las que tienen liga
$redes = array_filter(array_intersect_key($item, array_flip(REDES_FUNDADOR)), fn($u) => is_string($u) && $u !== '');
$embed = instagram_embed((string) ($item['publicacion'] ?? ''));
$parrafos = array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) ($item['mensaje'] ?? '')) ?: []));
$cita = trim((string) $item['cita'], " \t\n\"“”");
?>
<?php if ($embed): ?>
<?php /* Con publicación de Instagram: datos del fundador a la izquierda y el embed oficial a la derecha.
         main.js ajusta el alto del iframe con los mensajes MEASURE que manda Instagram (sin su script). */ ?>
<div class="fundador__tarjeta fundador__tarjeta--ig">
    <div class="fundador__texto">
        <p class="fundador__antetitulo">Nuestro fundador</p>
        <h2 class="fundador__nombre" id="titulo-fundador"><?= e($item['nombre']) ?></h2>
        <?php if (!empty($item['cargo'])): ?><p class="fundador__cargo"><?= e($item['cargo']) ?></p><?php endif; ?>
        <?php if ($cita !== ''): ?><p class="fundador__frase">“<?= e($cita) ?>”</p><?php endif; ?>
        <div class="fundador__acciones">
            <a class="boton fundador__seguir" href="<?= e($item['instagram'] ?? '' ?: $item['publicacion']) ?>" target="_blank" rel="noopener"><?= icono('instagram') ?> Seguir en Instagram</a>
            <?php if ($redes): ?>
                <ul class="redes fundador__redes" aria-label="<?= e('Redes sociales de ' . $item['nombre']) ?>">
                    <?php foreach ($redes as $red => $url): ?>
                        <li><a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($red) . ' de ' . $item['nombre']) ?>"><?= icono($red) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="fundador__publicacion">
        <?php /* El marco recorta el pie del embed («Agrega un comentario…»); main.js le da el alto */ ?>
        <div class="fundador__marco-ig" data-instagram-marco>
            <iframe src="<?= e($embed) ?>" title="<?= e('Publicación de Instagram de ' . $item['nombre']) ?>" loading="lazy"
                    width="540" height="838" scrolling="no" data-instagram></iframe>
        </div>
    </div>
</div>
<?php else: ?>
<div class="fundador__tarjeta">
    <figure class="fundador__foto">
        <img src="<?= e(ruta_upload((string) $item['foto'])) ?>" alt="<?= e($item['alt'] ?? $item['nombre']) ?>" width="900" height="1194" loading="lazy" decoding="async">
        <figcaption class="fundador__etiqueta">Fundador</figcaption>
    </figure>
    <div class="fundador__texto">
        <p class="fundador__antetitulo">Carta del fundador</p>
        <h2 class="fundador__cita" id="titulo-fundador">“<?= e($cita) ?>”</h2>
        <?php foreach ($parrafos as $parrafo): ?>
            <p><?= nl2br(e($parrafo), false) ?></p>
        <?php endforeach; ?>
        <div class="fundador__pie">
            <p class="fundador__firma">
                <strong><?= e($item['nombre']) ?></strong>
                <?php if (!empty($item['cargo'])): ?><span><?= e($item['cargo']) ?></span><?php endif; ?>
            </p>
            <?php if ($redes): ?>
                <ul class="redes fundador__redes" aria-label="<?= e('Redes sociales de ' . $item['nombre']) ?>">
                    <?php foreach ($redes as $red => $url): ?>
                        <li><a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($red) . ' de ' . $item['nombre']) ?>"><?= icono($red) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
