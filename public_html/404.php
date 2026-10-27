<?php
/**
 * Página 404. Se usa desde .htaccess (ErrorDocument) y desde no_encontrado().
 */
if (!defined('RAIZ_WEB')) {
    require __DIR__ . '/includes/config.php';
    http_response_code(404);
}

$pagina = [
    'titulo'      => "Página no encontrada | Du'an Bienes Raíces",
    'descripcion' => 'La página que busca no existe o cambió de dirección.',
    'ruta'        => '/404',
    'noindex'     => true,
];

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<section class="seccion error-404">
    <div class="contenedor centrado">
        <p class="error-404__codigo" aria-hidden="true">404</p>
        <h1 class="titulo-seccion">No encontramos esta página</h1>
        <p>Es posible que haya cambiado de dirección al renovar nuestro sitio.</p>
        <div class="cta-linea__botones">
            <a class="boton" href="/">Ir al inicio</a>
            <a class="boton boton--contorno" href="<?= e(URL_TOKKO_PROPIEDADES) ?>" target="_blank" rel="noopener">Ver propiedades</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
