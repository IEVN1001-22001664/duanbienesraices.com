<?php
/**
 * Lista de una sección: ordenar (arrastrando o con flechas), mostrar/ocultar,
 * editar, eliminar y restaurar versiones anteriores.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

$s = seccion_solicitada();
$sec = SECCIONES_ADMIN[$s];
$esBlog = $s === 'blog';

$errorArchivo = null;
try {
    $items = elementos_de($s);
} catch (ErrorPanel $ex) {
    $items = [];
    $errorArchivo = $ex->getMessage();
}
$version = $esBlog ? null : version_de(archivo_de($s));

admin_cabecera($sec['titulo'], [['Inicio', '/admin/'], [$sec['titulo'], null]]);
?>
<div class="encabezado">
    <div>
        <h1><?= e($sec['titulo']) ?></h1>
        <p class="encabezado__texto"><?= e($sec['descripcion']) ?></p>
    </div>
    <div class="encabezado__acciones">
        <?php if (!$esBlog): ?>
            <a class="boton boton--secundario" href="/admin/respaldos.php?s=<?= e($s) ?>">Restaurar versión anterior</a>
        <?php endif; ?>
        <a class="boton" href="/admin/editar.php?s=<?= e($s) ?>">+ <?= e($sec['nuevo']) ?></a>
    </div>
</div>

<?php if ($errorArchivo): ?>
    <p class="aviso aviso--error" role="alert"><?= e($errorArchivo) ?>
        <a href="/admin/respaldos.php?s=<?= e($s) ?>">Ver respaldos</a></p>
<?php elseif (!$items): ?>
    <p class="vacio">Aún no hay elementos. Usa «<?= e($sec['nuevo']) ?>» para crear el primero.</p>
<?php else: ?>
    <?php if (!$esBlog): ?>
        <p class="ayuda-lista">Arrastra <span aria-hidden="true">⠿</span> o usa las flechas para cambiar el orden en que aparecen en el sitio. Se guarda solo.</p>
    <?php endif; ?>
    <ol class="lista" <?= $esBlog ? '' : 'data-ordenable data-seccion="' . e($s) . '"' ?>>
        <?php foreach ($items as $pos => $item):
            $id = (string) ($item['id'] ?? '');
            $activo = ($item['activo'] ?? true) !== false;
            $v = $esBlog ? version_de(archivo_de('blog', $id)) : $version;
            $nombre = nombre_de($s, $item);
            $errores = validar_item($item, $s);
        ?>
            <li class="lista__item<?= $activo ? '' : ' lista__item--oculto' ?>" data-item data-id="<?= e($id) ?>">
                <?php if (!$esBlog): ?>
                    <span class="lista__asa" data-asa title="Arrastra para mover" aria-hidden="true">⠿</span>
                <?php endif; ?>
                <?= miniatura_de($s, $item) ?>
                <div class="lista__texto">
                    <p class="lista__nombre"><a href="/admin/editar.php?s=<?= e($s) ?>&amp;id=<?= e(rawurlencode($id)) ?>"><?= e($nombre) ?></a></p>
                    <p class="lista__detalle"><?= e(detalle_de($s, $item)) ?></p>
                    <?php if ($errores): ?>
                        <p class="lista__alerta">No se muestra en el sitio: <?= e(implode('; ', $errores)) ?>. Edítalo para corregirlo.</p>
                    <?php endif; ?>
                </div>

                <div class="lista__acciones">
                    <form method="post" action="/admin/accion.php" data-accion="activar">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="accion" value="activar">
                        <input type="hidden" name="s" value="<?= e($s) ?>">
                        <input type="hidden" name="id" value="<?= e($id) ?>">
                        <input type="hidden" name="version" value="<?= e($v) ?>" data-version>
                        <button class="interruptor" type="submit" role="switch" aria-checked="<?= $activo ? 'true' : 'false' ?>"
                                aria-label="<?= e('Visible en el sitio: ' . $nombre) ?>">
                            <span class="interruptor__texto" data-estado-texto><?= $activo ? 'Visible' : 'Oculto' ?></span>
                        </button>
                    </form>

                    <?php if (!$esBlog): ?>
                        <form method="post" action="/admin/accion.php" class="lista__flechas" data-accion="mover">
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="mover">
                            <input type="hidden" name="s" value="<?= e($s) ?>">
                            <input type="hidden" name="id" value="<?= e($id) ?>">
                            <input type="hidden" name="version" value="<?= e($v) ?>" data-version>
                            <button class="mini" type="submit" name="dir" value="-1" data-mover-item="-1" aria-label="<?= e('Subir: ' . $nombre) ?>">▲</button>
                            <button class="mini" type="submit" name="dir" value="1" data-mover-item="1" aria-label="<?= e('Bajar: ' . $nombre) ?>">▼</button>
                        </form>
                    <?php endif; ?>

                    <a class="boton boton--secundario boton--chico" href="/admin/editar.php?s=<?= e($s) ?>&amp;id=<?= e(rawurlencode($id)) ?>">Editar</a>
                    <?php if ($esBlog): ?>
                        <a class="boton boton--texto boton--chico" href="/admin/respaldos.php?s=blog&amp;id=<?= e(rawurlencode($id)) ?>">Versiones</a>
                    <?php endif; ?>

                    <form method="post" action="/admin/accion.php" data-confirmar="<?= e('¿Eliminar «' . $nombre . '»? Podrás recuperarlo desde «Restaurar versión anterior».') ?>">
                        <?= campo_csrf() ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="s" value="<?= e($s) ?>">
                        <input type="hidden" name="id" value="<?= e($id) ?>">
                        <input type="hidden" name="version" value="<?= e($v) ?>" data-version>
                        <button class="boton boton--peligro boton--chico" type="submit">Eliminar</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>

<?php if ($esBlog && ($eliminados = blog_eliminados())): ?>
    <section class="papelera">
        <h2>Artículos eliminados</h2>
        <p>Puedes recuperarlos mientras tengan respaldos.</p>
        <ul>
            <?php foreach ($eliminados as $slug): ?>
                <li><a href="/admin/respaldos.php?s=blog&amp;id=<?= e(rawurlencode($slug)) ?>">/blog/<?= e($slug) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
<?php admin_pie();
