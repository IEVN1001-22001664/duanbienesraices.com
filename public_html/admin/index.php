<?php
/**
 * Inicio del panel: una tarjeta por sección editable.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

admin_cabecera('Inicio');
?>
<h1>¿Qué quieres editar hoy?</h1>
<ul class="secciones">
    <?php foreach (SECCIONES_ADMIN as $clave => $sec):
        try {
            $items = elementos_de($clave);
        } catch (ErrorPanel $ex) {
            $items = null;
        }
        $activos = $items === null ? 0 : count(array_filter($items, fn($i) => ($i['activo'] ?? true) !== false));
        $archivo = $clave === 'blog' ? null : archivo_de($clave);
        $cambio = $archivo && is_file(ruta_data($archivo)) ? filemtime(ruta_data($archivo)) : null;
    ?>
        <li class="seccion-tarjeta">
            <h2><a href="/admin/seccion.php?s=<?= e($clave) ?>"><?= e($sec['titulo']) ?></a></h2>
            <p><?= e($sec['descripcion']) ?></p>
            <p class="seccion-tarjeta__datos">
                <?php if ($items === null): ?>
                    <span class="texto-error">Archivo dañado: restaura una versión anterior.</span>
                <?php else: ?>
                    <?= $activos ?> visibles de <?= count($items) ?>
                    <?php if ($cambio): ?> · Último cambio: <?= e(date('d/m/Y H:i', $cambio)) ?><?php endif; ?>
                <?php endif; ?>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php admin_pie();
