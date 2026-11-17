<?php
/**
 * Portada de una página interna (foto de fondo con antetítulo, título y texto).
 * Editable en el panel: sección «portadas».
 * Variables: $portada (de portada_de() o portada_normalizar()), $baja (opcional, portada de menor altura),
 *            $boton (opcional, ['texto' => …, 'url' => …]),
 *            $cifra (opcional, ['numero' => 500, 'prefijo' => '+', 'lectura' => 'Más de 500', 'texto' => …]):
 *            cifra con contador a la derecha del texto (debajo en celular).
 */
$baja = !empty($baja);
$boton = $boton ?? null;
$cifra = $cifra ?? null;
?>
<section class="portada<?= $baja ? ' portada--baja' : '' ?>" style="--fondo: url('<?= e($portada['imagen']) ?>'); --fondo-pos: <?= e($portada['posicion']) ?>">
    <div class="contenedor portada__texto<?= $cifra ? ' portada__texto--cifra' : '' ?>">
        <?php if ($cifra): ?><div class="portada__principal"><?php endif; ?>
        <?php if ($portada['antetitulo'] !== ''): ?>
            <p class="portada__antetitulo"><?= e($portada['antetitulo']) ?></p>
        <?php endif; ?>
        <h1 class="portada__titulo"><?= e($portada['titulo']) ?></h1>
        <?php if ($portada['texto'] !== ''): ?>
            <p class="portada__lead"><?= e($portada['texto']) ?></p>
        <?php endif; ?>
        <?php if ($boton): ?>
            <a class="boton boton--grande" href="<?= e($boton['url']) ?>"><?= e($boton['texto']) ?></a>
        <?php endif; ?>
        <?php if ($cifra): ?>
        </div>
        <p class="portada__cifra">
            <span class="portada__cifra-num" data-contador="<?= (int) $cifra['numero'] ?>" data-prefijo="<?= e($cifra['prefijo'] ?? '') ?>" aria-hidden="true"><?= e(($cifra['prefijo'] ?? '') . $cifra['numero']) ?></span>
            <span class="sr-only"><?= e($cifra['lectura']) ?></span>
            <span class="portada__cifra-txt"><?= e($cifra['texto']) ?></span>
        </p>
        <?php endif; ?>
    </div>
</section>
