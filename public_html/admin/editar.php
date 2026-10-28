<?php
/**
 * Crear o editar un elemento de cualquier sección, con vista previa en vivo.
 *   /admin/editar.php?s=destacados            → nuevo
 *   /admin/editar.php?s=destacados&id=xxxxx   → editar
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

$s = seccion_solicitada();
$sec = SECCIONES_ADMIN[$s];
$esBlog = $s === 'blog';
$id = (string) ($_GET['id'] ?? '');
$esNuevo = $id === '';

// Valores iniciales de un elemento nuevo
$nuevo = ['activo' => true] + match ($s) {
    'destacados'  => ['operacion' => 'venta', 'moneda' => 'MXN', 'fotos' => []],
    'testimonios' => ['estrellas' => 5],
    'blog'        => ['fecha' => date('Y-m-d')],
    default       => [],
};

try {
    $actual = $esNuevo ? $nuevo : elemento($s, $id);
} catch (ErrorPanel $ex) {
    avisar($ex->getMessage(), 'error');
    redirigir('/admin/seccion.php?s=' . rawurlencode($s));
}
if ($actual === null) {
    avisar('Ese elemento ya no existe.', 'error');
    redirigir('/admin/seccion.php?s=' . rawurlencode($s));
}
$archivo = $esBlog ? ($esNuevo ? null : archivo_de('blog', $id)) : archivo_de($s);
$version = $archivo ? version_de($archivo) : '';

$item = $actual;
$errores = [];
$slugNuevo = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verificar_csrf();
    $version = (string) ($_POST['version'] ?? '');
    [$item, $errores] = recolectar($s, $_POST, $actual);

    // Dirección del artículo: solo se elige al crearlo (cambiarla rompería ligas ya compartidas)
    if ($esBlog && $esNuevo) {
        $slugNuevo = slug((string) ($_POST['slug'] ?? '') ?: (string) ($item['titulo'] ?? ''), 80);
        if (is_file(DIR_DATA . '/blog/' . $slugNuevo . '.json') || is_dir(DIR_DATA . '/backups/blog/' . $slugNuevo)) {
            $errores['slug'] = 'Ya existe (o existió) un artículo con esta dirección. Cambia la dirección.';
        }
    }

    if (!$errores) {
        // Última revisión con las mismas reglas que usa el sitio
        $prueba = $item + ($esBlog ? ['slug' => $esNuevo ? $slugNuevo : $id] : ['id' => $id ?: 'nuevo']);
        if ($fallas = validar_item($prueba, $s)) {
            $errores['_'] = implode('; ', $fallas);
        }
    }

    if (!$errores) {
        try {
            if ($esBlog) {
                $slug = $esNuevo ? $slugNuevo : $id;
                $post = ['slug' => $slug] + $item;
                unset($post['id']);
                $post['actualizado'] = date('Y-m-d');
                modificar_json(archivo_de('blog', $slug), $esNuevo ? '' : $version, function (?array $previo) use ($post) {
                    // Conserva campos que el formulario no maneja (p. ej. slug_anterior de Wix)
                    // y quita los opcionales que el editor vació.
                    $extra = array_diff_key($previo ?? [], SECCIONES_ADMIN['blog']['campos'], ['id' => 1]);
                    return $post + $extra;
                });
            } else {
                if ($esNuevo) {
                    $base = (string) ($item[$sec['campo_nombre']] ?? '') ?: (string) ($item['youtube_id'] ?? $sec['singular']);
                    $item = ['id' => id_unico($s, $base)] + $item;
                }
                modificar_json(archivo_de($s), $version, function (?array $datos) use ($item, $esNuevo, $id) {
                    $datos ??= ['items' => []];
                    $items = array_values($datos['items'] ?? []);
                    if ($esNuevo) {
                        array_unshift($items, $item);   // los nuevos aparecen primero
                    } else {
                        $i = array_search($id, array_column($items, 'id'), true);
                        if ($i === false) {
                            throw new ErrorPanel('Este elemento se eliminó mientras lo editabas.');
                        }
                        $items[$i] = $item;
                    }
                    $datos['items'] = $items;
                    $datos['actualizado'] = ahora_iso();
                    return $datos;
                });
            }
            limpiar_uploads($s);
            avisar($esNuevo ? 'Se creó «' . nombre_de($s, $item) . '».' : 'Cambios guardados.');
            redirigir('/admin/seccion.php?s=' . rawurlencode($s));
        } catch (ErrorPanel $ex) {
            $errores['_'] = $ex->getMessage();
        }
    }
}

$titulo = $esNuevo ? $sec['nuevo'] : 'Editar ' . $sec['singular'];
$accion = '/admin/editar.php?s=' . rawurlencode($s) . ($esNuevo ? '' : '&id=' . rawurlencode($id));
admin_cabecera($titulo, [['Inicio', '/admin/'], [$sec['titulo'], '/admin/seccion.php?s=' . rawurlencode($s)], [$titulo, null]]);
?>
<h1><?= e($titulo) ?></h1>

<?php if ($errores): ?>
    <div class="aviso aviso--error" role="alert" tabindex="-1" data-enfocar>
        <p><strong>No se guardó.</strong> <?= isset($errores['_']) ? e($errores['_']) : 'Revisa los campos marcados en rojo.' ?></p>
    </div>
<?php endif; ?>

<div class="edicion">
    <form class="formulario edicion__form" method="post" action="<?= e($accion) ?>" data-formulario data-seccion="<?= e($s) ?>" novalidate>
        <?= campo_csrf() ?>
        <input type="hidden" name="s" value="<?= e($s) ?>">
        <input type="hidden" name="version" value="<?= e($version) ?>">

        <?php if ($esBlog): ?>
            <div class="campo<?= isset($errores['slug']) ? ' campo--error' : '' ?>">
                <?php if ($esNuevo): ?>
                    <label class="campo__etiqueta" for="campo-slug">Dirección del artículo</label>
                    <p class="campo__ayuda" id="campo-slug-ayuda">Se arma sola con el título. No se puede cambiar después de publicar.</p>
                    <div class="campo__prefijo"><span>/blog/</span>
                        <input class="campo__control" type="text" id="campo-slug" name="slug" value="<?= e($slugNuevo ?: (string) ($_POST['slug'] ?? '')) ?>"
                               aria-describedby="campo-slug-ayuda" data-slug>
                    </div>
                    <?php if (isset($errores['slug'])): ?><p class="campo__error"><?= e($errores['slug']) ?></p><?php endif; ?>
                <?php else: ?>
                    <p class="campo__etiqueta">Dirección del artículo</p>
                    <p class="campo__fijo"><a href="/blog/<?= e($id) ?>" target="_blank" rel="noopener">/blog/<?= e($id) ?> ↗</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php pintar_campos($s, $item, $errores); ?>

        <div class="campo campo--check">
            <label><input type="checkbox" name="activo" value="1"<?= ($item['activo'] ?? true) !== false ? ' checked' : '' ?>> Visible en el sitio</label>
            <p class="campo__ayuda">Desmárcalo para guardarlo sin que aparezca todavía.</p>
        </div>

        <div class="edicion__botones">
            <button class="boton boton--grande" type="submit">Guardar</button>
            <a class="boton boton--secundario boton--grande" href="/admin/seccion.php?s=<?= e(rawurlencode($s)) ?>" data-cancelar>Cancelar</a>
            <button class="boton boton--texto" type="submit" formaction="/admin/vista-previa.php" formtarget="vista-previa" formnovalidate data-previa>Actualizar vista previa</button>
        </div>
    </form>

    <aside class="edicion__previa" aria-label="Vista previa">
        <h2>Vista previa</h2>
        <p class="campo__ayuda">Así se verá en el sitio. Se actualiza mientras escribes.</p>
        <iframe class="previa previa--<?= e($s) ?>" name="vista-previa" title="Vista previa de <?= e($sec['singular']) ?>" src="about:blank"></iframe>
    </aside>
</div>
<?php admin_pie();
