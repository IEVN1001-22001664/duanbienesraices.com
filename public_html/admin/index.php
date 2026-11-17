<?php
/**
 * Inicio del panel: una tarjeta por sección editable.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

admin_cabecera('Inicio');
?>
<?php if ((cuenta()['correo'] ?? '') === ''): ?>
    <p class="aviso" role="status">Aún no hay un correo confirmado en la cuenta. Regístralo en <a href="/admin/cuenta.php">Mi cuenta</a> para poder recuperar la contraseña si se olvida.</p>
<?php endif; ?>
<?php if (tokko_activo()):
    $tokkoFecha = tokko_ultima_sincronizacion(); ?>
    <?php /* Botón general: trae de Tokko propiedades, fichas, precios y destacados en ese momento */ ?>
    <section class="tokko-refresco" aria-labelledby="titulo-tokko">
        <div>
            <h2 id="titulo-tokko">Propiedades de Tokko</h2>
            <p>¿Cambiaste precios, fotos, textos o diste de alta o de baja propiedades en Tokko? Presiona el botón cuando termines para verlos en el sitio al momento. Además, el sitio se actualiza solo cada 15 minutos.</p>
            <p class="tokko-refresco__datos">
                <?= count(tokko_propiedades()) ?> propiedades activas
                · Última actualización: <?= $tokkoFecha ? e(date('d/m/Y H:i', $tokkoFecha)) : 'nunca' ?>
            </p>
        </div>
        <form method="post" action="/admin/accion.php" data-espera="Actualizando desde Tokko…">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="actualizar_tokko">
            <input type="hidden" name="s" value="destacados">
            <input type="hidden" name="volver" value="inicio">
            <button class="boton" type="submit">Actualizar desde Tokko</button>
        </form>
    </section>
<?php endif; ?>
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
                <?php elseif (!empty($sec['unico'])): ?>
                    <?= $activos ? 'Visible en el sitio' : 'Oculta en el sitio' ?>
                    <?php if ($cambio): ?> · Último cambio: <?= e(date('d/m/Y H:i', $cambio)) ?><?php endif; ?>
                <?php elseif (!empty($sec['fijo'])): ?>
                    <?= count($items) ?> páginas
                    <?php if ($cambio): ?> · Último cambio: <?= e(date('d/m/Y H:i', $cambio)) ?><?php endif; ?>
                <?php else: ?>
                    <?= $activos ?> visibles de <?= count($items) ?>
                    <?php if ($cambio): ?> · Último cambio: <?= e(date('d/m/Y H:i', $cambio)) ?><?php endif; ?>
                <?php endif; ?>
            </p>
        </li>
    <?php endforeach; ?>
</ul>
<?php admin_pie();
