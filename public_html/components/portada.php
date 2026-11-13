<?php
/**
 * Portada de una página interna (foto de fondo con antetítulo, título y texto).
 * Editable en el panel: sección «portadas».
 * Variables: $portada (de portada_de() o portada_normalizar()), $baja (opcional, portada de menor altura),
 *            $boton (opcional, ['texto' => …, 'url' => …]).
 */
$baja = !empty($baja);
$boton = $boton ?? null;
?>
<section class="portada<?= $baja ? ' portada--baja' : '' ?>" style="--fondo: url('<?= e($portada['imagen']) ?>'); --fondo-pos: <?= e($portada['posicion']) ?>">
    <div class="contenedor portada__texto">
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
    </div>
</section>
