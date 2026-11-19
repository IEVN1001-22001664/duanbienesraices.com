<?php
/**
 * Un banner promocional de Propiedades destacadas (sección «vitrina» del panel).
 *   - tipo «diseno»: diseño Du'an (el de Torre Arbide). Escritorio: texto sobre carbón a la izquierda
 *     y la foto a la derecha con la firma encima. Celular: foto arriba, texto abajo, botones a lo ancho.
 *   - tipo «codigo»: el HTML que pegó el editor, dentro de un <iframe> (api/cartel.php). El aislamiento
 *     lo da la cabecera «Content-Security-Policy: sandbox» de esa respuesta (origen propio, sin cookies
 *     ni acceso a la página); no se usa el atributo sandbox porque algunos navegadores integrados bloquean
 *     esos marcos. main.js ajusta su alto con el mensaje que manda el documento.
 * Variables: $item; opcional $src_codigo (la vista previa del panel usa su propia dirección).
 */
$item ??= [];
$idTitulo = 'titulo-cartel-' . preg_replace('/[^\w-]/', '', (string) ($item['id'] ?? 'x'));

if (($item['tipo'] ?? '') === 'codigo'): ?>
    <div class="cartel cartel--codigo">
        <iframe class="cartel__marco" src="<?= e($src_codigo ?? '/api/cartel.php?id=' . rawurlencode((string) ($item['id'] ?? ''))) ?>"
                title="<?= e($item['nombre'] ?? 'Promoción') ?>" loading="lazy" data-cartel-codigo></iframe>
    </div>
<?php return; endif;

// Liga de cada botón: Cal.com abre la agenda en el modal; las direcciones externas, en otra pestaña
$boton = function (string $texto, string $url, string $clase): string {
    if ($texto === '' || $url === '') {
        return '';
    }
    $externa = (bool) preg_match('#^https?://#i', $url);
    $cal = (bool) preg_match('#^https://(?:app\.)?cal\.com/#i', $url);
    return '<a class="boton cartel__boton' . $clase . '" href="' . e($url) . '"'
        . ($externa ? ' target="_blank" rel="noopener"' : '')
        . ($cal ? ' data-agendar-cita="cal"' : '') . '>' . e($texto)
        . ($externa && !$cal ? '<span class="sr-only"> (se abre en otra pestaña)</span>' : '') . '</a>';
};
$botones = $boton((string) ($item['boton1_texto'] ?? ''), (string) ($item['boton1_url'] ?? ''), '')
         . $boton((string) ($item['boton2_texto'] ?? ''), (string) ($item['boton2_url'] ?? ''), ' cartel__boton--contorno');
$texto = (string) ($item['texto'] ?? '');
$textoCelular = (string) ($item['texto_celular'] ?? '');
// Encuadre con clase (la CSP del panel no permite estilos en línea en la vista previa)
$encuadre = in_array($item['encuadre'] ?? '', ['arriba', 'abajo'], true) ? $item['encuadre'] : 'centro';
?>
<aside class="cartel" aria-labelledby="<?= e($idTitulo) ?>">
    <div class="cartel__texto">
        <?php if (!empty($item['etiqueta'])): ?>
            <p class="cartel__etiqueta"><?= e($item['etiqueta']) ?></p>
        <?php endif; ?>
        <h3 class="cartel__titulo" id="<?= e($idTitulo) ?>"><?= e($item['titulo'] ?? '') ?></h3>
        <?php if ($texto !== '' || $textoCelular !== ''): ?>
            <p class="cartel__descripcion">
                <?php if ($textoCelular !== '' && $texto !== ''): ?>
                    <span class="cartel__solo-escritorio"><?= e($texto) ?></span>
                    <span class="cartel__solo-celular"><?= e($textoCelular) ?></span>
                <?php else: ?>
                    <?= e($texto ?: $textoCelular) ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($item['precio'])): ?>
            <p class="cartel__precio"><?= e($item['precio_antes'] ?? '') ?> <strong><?= e($item['precio']) ?></strong></p>
        <?php endif; ?>
        <?php if ($botones !== ''): ?>
            <div class="cartel__botones"><?= $botones /* armado arriba con e() */ ?></div>
        <?php endif; ?>
    </div>
    <div class="cartel__foto cartel__foto--<?= e($encuadre) ?>">
        <?php if (!empty($item['imagen'])): ?>
            <img src="<?= e(ruta_upload($item['imagen'])) ?>" alt="<?= e($item['alt'] ?? '') ?>" loading="lazy" decoding="async">
        <?php endif; ?>
    </div>
    <?php if (!empty($item['firma'])): ?>
        <p class="cartel__firma" aria-hidden="true">
            <span class="cartel__duan">Du’an<small>Bienes Raíces</small></span>
            <span class="cartel__proyecto"><?= e($item['firma']) ?></span>
        </p>
    <?php endif; ?>
</aside>
