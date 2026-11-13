<?php
/**
 * Contacto (/contacto). El formulario se conecta en la Fase 3.
 */
require __DIR__ . '/includes/config.php';

$pagina = [
    'titulo'      => "Contacto | Du'an Bienes Raíces, León, Guanajuato",
    'descripcion' => 'Con gusto le atendemos. Escríbanos por WhatsApp al 477 551 4226 o a info@duanbienesraices.com.',
    'ruta'        => '/contacto',
    'activo'      => 'contacto',
];
$portada = portada_de('contacto', ['imagen' => '/assets/img/contacto-ubicacion.webp', 'titulo' => 'Contáctenos', 'texto' => 'Con gusto atenderemos su llamada o mensaje.']);
$pagina['imagen'] = $pagina['precarga'] = $portada['imagen'];
$d = DIRECCION;
// Mapa embebido sin API key; se carga al hacer clic para no frenar la página.
$mapaSrc = 'https://www.google.com/maps?q=' . rawurlencode(NOMBRE_SITIO . ', ' . $d['calle'] . ', ' . $d['colonia'] . ', ' . $d['cp'] . ' León, Gto.') . '&z=16&output=embed';

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<?php componente('portada', ['portada' => $portada, 'baja' => true]); ?>

<section class="seccion contacto" aria-label="Datos de contacto y formulario">
    <div class="contenedor contacto__grid">
        <div class="contacto__datos">
            <h2 class="titulo-seccion titulo-seccion--izq">Estamos para ayudarle</h2>
            <ul class="datos-contacto">
                <li>
                    <span class="datos-contacto__icono datos-contacto__icono--wa"><?= icono('whatsapp') ?></span>
                    <div><strong>WhatsApp</strong><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener"><?= e(WHATSAPP_VISIBLE) ?></a></div>
                </li>
                <li>
                    <span class="datos-contacto__icono"><?= icono('correo') ?></span>
                    <div><strong>Correo</strong><a href="mailto:<?= e(EMAIL_CONTACTO) ?>"><?= e(EMAIL_CONTACTO) ?></a></div>
                </li>
                <li>
                    <span class="datos-contacto__icono"><?= icono('ubicacion') ?></span>
                    <div><strong>Oficina</strong>
                        <address><?= e($d['calle']) ?>, <?= e($d['colonia']) ?>,<br><?= e($d['cp']) ?> León de los Aldama, Gto.</address>
                        <a href="<?= e(URL_MAPS_OFICINA) ?>" target="_blank" rel="noopener">Cómo llegar</a>
                    </div>
                </li>
                <!-- TODO: contenido real — horario de atención (no aparece en el sitio actual). -->
            </ul>
            <p class="contacto__redes-titulo">Síganos en redes sociales</p>
            <ul class="redes redes--grandes" aria-label="Redes sociales">
                <?php foreach (REDES as $red => $url): ?>
                    <li><a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($red)) ?>"><?= icono($red) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <form class="formulario" id="formulario" action="/api/enviar.php" method="post" data-formulario novalidate>
            <h2 class="formulario__titulo">Envíenos un mensaje</h2>
            <input type="hidden" name="formulario" value="contacto">
            <div class="hp" aria-hidden="true"><label for="c-web">No llenar</label><input id="c-web" type="text" name="sitio_web" tabindex="-1" autocomplete="off"></div>
            <div class="campo">
                <label for="c-nombre">Nombre <span aria-hidden="true">*</span></label>
                <input id="c-nombre" type="text" name="nombre" autocomplete="name" required>
            </div>
            <div class="campo-doble">
                <div class="campo">
                    <label for="c-email">Correo electrónico <span aria-hidden="true">*</span></label>
                    <input id="c-email" type="email" name="email" autocomplete="email" required>
                </div>
                <div class="campo">
                    <label for="c-tel">Teléfono / WhatsApp</label>
                    <input id="c-tel" type="tel" name="telefono" autocomplete="tel" inputmode="tel">
                </div>
            </div>
            <div class="campo">
                <label for="c-asunto">Me interesa</label>
                <select id="c-asunto" name="asunto">
                    <?php foreach (ASUNTOS_CONTACTO as $op): ?>
                        <option><?= e($op) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="c-mensaje">Mensaje <span aria-hidden="true">*</span></label>
                <textarea id="c-mensaje" name="mensaje" rows="5" required></textarea>
            </div>
            <p class="formulario__requeridos"><span aria-hidden="true">*</span> Campos obligatorios. Al enviar acepta nuestro <a href="/aviso-de-privacidad">aviso de privacidad</a>.</p>
            <?php componente('envio-formulario', ['boton' => 'Enviar mensaje']); ?>
        </form>
    </div>
</section>

<section class="mapa" aria-label="Mapa de ubicación">
    <div class="mapa__marco" data-mapa="<?= e($mapaSrc) ?>">
        <img src="/assets/img/contacto-ubicacion.webp" alt="" width="1920" height="883" loading="lazy" decoding="async">
        <button class="boton mapa__boton" type="button"><?= icono('ubicacion') ?> Ver en Maps</button>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
