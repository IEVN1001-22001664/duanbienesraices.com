<?php
/**
 * Carta del fundador (Nosotros). Editable en el panel: sección «fundador».
 * Variables: $item (único elemento de fundador.json).
 * El mensaje es texto plano; una línea en blanco separa los párrafos.
 */
// Redes del fundador: solo las que tienen liga
$redes = array_filter(array_intersect_key($item, array_flip(REDES_FUNDADOR)), fn($u) => is_string($u) && $u !== '');
$parrafos = array_filter(array_map('trim', preg_split('/\R\s*\R/u', (string) ($item['mensaje'] ?? '')) ?: []));
?>
<div class="fundador__tarjeta">
    <figure class="fundador__foto">
        <img src="<?= e(ruta_upload((string) $item['foto'])) ?>" alt="<?= e($item['alt'] ?? $item['nombre']) ?>" width="900" height="1194" loading="lazy" decoding="async">
        <figcaption class="fundador__etiqueta">Fundador</figcaption>
    </figure>
    <div class="fundador__texto">
        <p class="fundador__antetitulo">Carta del fundador</p>
        <h2 class="fundador__cita" id="titulo-fundador">“<?= e(trim((string) $item['cita'], " \t\n\"“”")) ?>”</h2>
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
