<?php
/**
 * "Restaurar versión anterior": lista los respaldos automáticos de una sección
 * (o de un artículo del blog) y permite volver a cualquiera de ellos.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

$s = seccion_solicitada();
$sec = SECCIONES_ADMIN[$s];
$id = $s === 'blog' ? (string) ($_GET['id'] ?? '') : null;

try {
    $archivo = archivo_de($s, $id);
} catch (ErrorPanel $ex) {
    avisar($ex->getMessage(), 'error');
    redirigir('/admin/seccion.php?s=' . rawurlencode($s));
}
$respaldos = respaldos_de($archivo);

/** Resumen legible de lo que contiene un respaldo. */
function resumen_respaldo(string $s, string $ruta): array
{
    $datos = json_decode((string) file_get_contents($ruta), true);
    if (!is_array($datos)) {
        return ['Archivo dañado', []];
    }
    if ($s === 'blog') {
        $estado = ($datos['activo'] ?? true) === false ? 'oculto' : 'visible';
        return [($datos['titulo'] ?? 'Sin título') . " ({$estado})", []];
    }
    $items = $datos['items'] ?? [];
    $nombres = array_map(fn($i) => nombre_de($s, $i) . (($i['activo'] ?? true) === false ? ' (oculto)' : ''), $items);
    return [count($items) . ' elementos', $nombres];
}

$nombreSeccion = $s === 'blog' ? 'Artículo /blog/' . $id : $sec['titulo'];
admin_cabecera('Restaurar versión anterior', [
    ['Inicio', '/admin/'],
    [$sec['titulo'], '/admin/seccion.php?s=' . rawurlencode($s)],
    ['Versiones anteriores', null],
]);
?>
<h1>Restaurar versión anterior</h1>
<p class="encabezado__texto"><?= e($nombreSeccion) ?>. Cada vez que guardas, la versión anterior se respalda
    automáticamente (se conservan las últimas <?= RESPALDOS_MAX ?>). Restaurar no borra nada: lo que hay ahora también se respalda.</p>

<?php if (!$respaldos): ?>
    <p class="vacio">Todavía no hay versiones anteriores.</p>
<?php else: ?>
    <ol class="respaldos">
        <?php foreach ($respaldos as $i => $r):
            [$resumen, $nombres] = resumen_respaldo($s, $r['ruta']); ?>
            <li class="respaldo">
                <div>
                    <p class="respaldo__fecha"><?= e(fecha_es(date('Y-m-d', $r['fecha']))) ?>, <?= e(date('H:i', $r['fecha'])) ?> h
                        <?php if ($i === 0): ?><span class="insignia">La más reciente</span><?php endif; ?></p>
                    <p class="respaldo__resumen"><?= e($resumen) ?></p>
                    <?php if ($nombres): ?>
                        <details>
                            <summary>Ver contenido</summary>
                            <ol><?php foreach ($nombres as $n): ?><li><?= e($n) ?></li><?php endforeach; ?></ol>
                        </details>
                    <?php endif; ?>
                </div>
                <form method="post" action="/admin/accion.php" data-confirmar="¿Restaurar la versión del <?= e(date('d/m/Y H:i', $r['fecha'])) ?>? Lo que hay ahora se guardará como respaldo.">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="restaurar">
                    <input type="hidden" name="s" value="<?= e($s) ?>">
                    <input type="hidden" name="id" value="<?= e((string) $id) ?>">
                    <input type="hidden" name="respaldo" value="<?= e($r['nombre']) ?>">
                    <button class="boton boton--secundario" type="submit">Restaurar esta versión</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
<?php admin_pie();
