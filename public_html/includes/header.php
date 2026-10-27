<?php
/**
 * Encabezado: logo + slogan, menú tipo pestañas y redes sociales.
 * Usa $pagina['activo'] para marcar la pestaña actual.
 */
$activo = $pagina['activo'] ?? '';
?>
<header class="encabezado" id="inicio">
    <div class="encabezado__barra contenedor">
        <a class="marca" href="/" aria-label="<?= e(NOMBRE_SITIO) ?> — Inicio">
            <img class="marca__logo" src="/assets/img/logo-duan-bienes-raices.webp" alt="Logo Du'an Bienes Raíces" width="400" height="576">
            <img class="marca__slogan" src="/assets/img/slogan-su-patrimonio-en-buenas-manos.webp" alt="<?= e('“' . SLOGAN . '”') ?>" width="900" height="152">
        </a>

        <ul class="redes redes--encabezado" aria-label="Redes sociales">
            <?php foreach (REDES as $red => $url): ?>
                <li><a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($red)) ?>"><?= icono($red) ?></a></li>
            <?php endforeach; ?>
        </ul>

        <button class="menu-boton" type="button" aria-expanded="false" aria-controls="menu-principal">
            <span class="menu-boton__abrir"><?= icono('menu') ?></span>
            <span class="menu-boton__cerrar"><?= icono('cerrar') ?></span>
            <span class="menu-boton__texto">Menú</span>
        </button>
    </div>

    <nav class="navegacion" id="menu-principal" aria-label="Principal">
        <ul class="pestanas contenedor">
            <?php foreach (MENU as $item):
                $esActivo = $item['clave'] === $activo;
                $externo = !empty($item['externo']);
            ?>
                <li>
                    <a class="pestana<?= $esActivo ? ' es-activa' : '' ?>" href="<?= e($item['url']) ?>"
                        <?= $esActivo ? 'aria-current="page"' : '' ?>
                        <?= $externo ? 'target="_blank" rel="noopener"' : '' ?>>
                        <?= e($item['texto']) ?>
                        <?php if ($externo): ?><?= icono('externo', 'icono icono--mini') ?><span class="sr-only"> (abre en otra pestaña)</span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</header>
<main id="contenido">
