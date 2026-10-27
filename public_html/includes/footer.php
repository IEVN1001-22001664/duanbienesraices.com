<?php
/**
 * Pie de página: slogan, redes, enlaces y créditos. Cierra <main> y <body>.
 */
?>
</main>

<footer class="pie">
    <div class="pie__contenido contenedor">
        <div class="pie__marca">
            <img src="/assets/img/slogan-footer.webp" alt="<?= e('“' . SLOGAN . '”') ?>" width="900" height="127" loading="lazy">
            <ul class="redes redes--pie" aria-label="Redes sociales">
                <?php foreach (REDES as $red => $url): ?>
                    <li><a href="<?= e($url) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($red)) ?>"><?= icono($red) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <nav class="pie__columna" aria-label="Sitio">
            <h2 class="pie__titulo">Sitio</h2>
            <ul>
                <?php foreach (MENU as $item): ?>
                    <li><a href="<?= e($item['url']) ?>"<?= !empty($item['externo']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($item['texto']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <nav class="pie__columna" aria-label="Servicios">
            <h2 class="pie__titulo">Servicios</h2>
            <ul>
                <?php foreach (SERVICIOS as $s): ?>
                    <li><a href="<?= e($s['url']) ?>"><?= e($s['texto']) ?></a></li>
                <?php endforeach; ?>
                <li><a href="/aviso-de-privacidad">Aviso de privacidad</a></li>
            </ul>
        </nav>

        <div class="pie__columna">
            <h2 class="pie__titulo">Contacto</h2>
            <ul class="pie__contacto">
                <li><?= icono('telefono') ?><a href="tel:<?= e(TELEFONO_E164) ?>"><?= e(TELEFONO) ?></a></li>
                <li><?= icono('whatsapp') ?><a href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener"><?= e(WHATSAPP_VISIBLE) ?></a></li>
                <li><?= icono('correo') ?><a href="mailto:<?= e(EMAIL_CONTACTO) ?>"><?= e(EMAIL_CONTACTO) ?></a></li>
                <li><?= icono('ubicacion') ?><a href="<?= e(URL_MAPS_OFICINA) ?>" target="_blank" rel="noopener"><?= e(DIRECCION['calle'] . ', ' . DIRECCION['colonia'] . ', ' . DIRECCION['ciudad'] . ', Gto.') ?></a></li>
            </ul>
        </div>
    </div>

    <div class="pie__legal">
        <p><?= e(NOMBRE_SITIO) ?> | <?= date('Y') ?></p>
        <p class="pie__lema">Compra - Vende - Renta - Invierte</p>
    </div>
</footer>

<?php require __DIR__ . '/whatsapp.php'; ?>

<a class="subir" href="#inicio" aria-label="Volver arriba"><?= icono('flecha-arriba') ?></a>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
