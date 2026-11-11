<?php
/**
 * Elegir propiedades de Tokko para los destacados del inicio.
 * Lista la copia local de Tokko (la renueva si tiene más de 3 h) con buscador.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

if (!tokko_activo()) {
    avisar('Falta la clave de Tokko (TOKKO_API_KEY) en config.local.php.', 'error');
    redirigir('/admin/seccion.php?s=destacados');
}

$error = null;
if (time() - tokko_ultima_sincronizacion() > TOKKO_VIGENCIA) {
    try {
        tokko_sincronizar_todo();
    } catch (Throwable $ex) {
        error_log('[duan] Tokko: ' . $ex->getMessage());
        $error = 'No se pudo conectar con Tokko (' . $ex->getMessage() . ').'
            . (tokko_ultima_sincronizacion() ? ' Se muestra la última copia guardada.' : '');
    }
}

$propiedades = tokko_propiedades();
$enDestacados = [];
foreach (elementos_de('destacados') as $item) {
    if ($id = tokko_id_de_destacado($item)) {
        $enDestacados[$id] = true;
    }
}
$ultima = tokko_ultima_sincronizacion();

admin_cabecera('Elegir propiedad de Tokko', [['Inicio', '/admin/'], ['Propiedades destacadas', '/admin/seccion.php?s=destacados'], ['Elegir de Tokko', null]]);
?>
<div class="encabezado">
    <div>
        <h1>Elegir propiedad de Tokko</h1>
        <p class="encabezado__texto">Las propiedades activas en Tokko<?= $ultima ? ' (actualizado el ' . e(date('d/m/Y \a \l\a\s H:i', $ultima)) . ')' : '' ?>.
            Al agregar una, se copian sus fotos y datos; aparece al principio de los destacados.</p>
    </div>
    <div class="encabezado__acciones">
        <form method="post" action="/admin/accion.php" data-espera="Actualizando desde Tokko…">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="actualizar_tokko">
            <input type="hidden" name="s" value="destacados">
            <input type="hidden" name="volver" value="tokko">
            <button class="boton boton--secundario" type="submit">Actualizar lista</button>
        </form>
    </div>
</div>

<?php if ($error): ?>
    <p class="aviso aviso--error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<?php if (!$propiedades): ?>
    <p class="vacio">No hay propiedades para mostrar.</p>
<?php else: ?>
    <div class="filtros" data-filtros hidden>
        <label class="filtros__buscar">
            <span class="sr-only">Buscar</span>
            <input type="search" placeholder="Buscar por título, colonia, código o tipo…" data-buscar>
        </label>
        <label>
            <span class="sr-only">Operación</span>
            <select data-filtro-operacion>
                <option value="">Venta y renta</option>
                <option value="venta">Venta</option>
                <option value="renta">Renta</option>
            </select>
        </label>
        <p class="filtros__cuenta" aria-live="polite"><span data-cuenta><?= count($propiedades) ?></span> propiedades</p>
    </div>

    <ol class="lista lista--tokko">
        <?php foreach ($propiedades as $p):
            $op = $p['operaciones'][0];
            $operaciones = implode(' ', array_column($p['operaciones'], 'tipo'));
            $mini = $p['fotos'][0]['mini'] ?? '';
            $yaEsta = isset($enDestacados[(int) $p['id']]);
            $busqueda = mb_strtolower(implode(' ', [$p['titulo'], $p['titulo_tokko'] ?? '', $p['codigo'], $p['tipo'], $p['ubicacion_completa'], $operaciones]));
        ?>
            <li class="lista__item" data-propiedad data-busqueda="<?= e($busqueda) ?>" data-operaciones="<?= e($operaciones) ?>">
                <?php if ($mini !== ''): ?>
                    <img class="lista__miniatura" src="<?= e($mini) ?>" alt="" loading="lazy" width="96" height="72" referrerpolicy="no-referrer">
                <?php else: ?>
                    <span class="lista__miniatura lista__miniatura--vacia" aria-hidden="true">—</span>
                <?php endif; ?>
                <div class="lista__texto">
                    <p class="lista__nombre"><?= e($p['titulo']) ?></p>
                    <p class="lista__detalle">
                        <?= e(implode(' · ', array_filter([
                            ucfirst($op['tipo']) . ' · ' . ($p['precio_visible'] ? formato_precio($op['precio'], $op['moneda']) : 'Precio a consultar'),
                            $p['tipo'],
                            $p['ubicacion'],
                            $p['codigo'],
                            count($p['fotos']) . ' fotos',
                        ]))) ?>
                    </p>
                </div>
                <div class="lista__acciones">
                    <a class="boton boton--texto boton--chico" href="<?= e($p['url_tokko']) ?>" target="_blank" rel="noopener">Ver ficha ↗</a>
                    <?php if ($yaEsta): ?>
                        <span class="etiqueta-panel">Ya está en destacados</span>
                    <?php else: ?>
                        <form method="post" action="/admin/accion.php" data-agregar-tokko>
                            <?= campo_csrf() ?>
                            <input type="hidden" name="accion" value="agregar_tokko">
                            <input type="hidden" name="s" value="destacados">
                            <input type="hidden" name="tokko_id" value="<?= (int) $p['id'] ?>">
                            <button class="boton boton--chico" type="submit">Agregar a destacados</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="progreso" data-progreso hidden>
                    <div class="progreso__barra" role="progressbar" aria-label="Agregando la propiedad" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                        <span class="progreso__relleno" data-relleno></span>
                    </div>
                    <p class="progreso__texto" data-progreso-texto aria-live="polite"></p>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>
    <p class="vacio" data-sin-resultados hidden>Ninguna propiedad coincide con la búsqueda.</p>
<?php endif; ?>
<?php admin_pie();
