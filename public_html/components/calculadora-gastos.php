<?php
/**
 * Calculadora de gastos iniciales (shortcode [[calculadora-gastos]] del blog).
 * Recrea la calculadora que había en Wix dentro de un iframe.
 * Supuestos (los mismos del sitio anterior): enganche 10 %, notaría + ISAI 4–6 %,
 * crédito 90 %, mensualidad $11,000–$12,000 por cada millón.
 */
?>
<div class="calculadora" data-calculadora>
    <h3 class="calculadora__titulo">Calculadora de Gastos Iniciales</h3>
    <p class="calculadora__desc">Ingresa el valor de la vivienda para estimar tu enganche y gastos notariales en León, Gto.</p>

    <label class="calculadora__label" for="calc-precio">Precio estimado de la propiedad (MXN):</label>
    <div class="calculadora__entrada">
        <span aria-hidden="true">$</span>
        <input id="calc-precio" type="text" inputmode="numeric" value="3,000,000" autocomplete="off">
    </div>
    <div class="calculadora__chips" role="group" aria-label="Precios rápidos">
        <?php foreach ([1500000, 2000000, 2500000, 3000000, 3500000, 5000000] as $p): ?>
            <button type="button" class="chip" data-precio="<?= $p ?>">$<?= rtrim(rtrim(number_format($p / 1e6, 1), '0'), '.') ?>M</button>
        <?php endforeach; ?>
    </div>

    <div class="tabla-scroll">
        <table class="calculadora__tabla">
            <thead><tr><th scope="col">Concepto</th><th scope="col">% Estimado</th><th scope="col">Monto estimado</th></tr></thead>
            <tbody>
                <tr><td>Enganche mínimo</td><td>10%</td><td data-res="enganche">—</td></tr>
                <tr><td>Gastos notariales e ISAI</td><td>4% al 6%</td><td data-res="notaria">—</td></tr>
                <tr class="es-total"><td>Ahorro mínimo sugerido</td><td>14% al 16%</td><td data-res="total">—</td></tr>
            </tbody>
        </table>
    </div>

    <dl class="calculadora__resumen" aria-live="polite">
        <div><dt>Crédito a solicitar (90%)</dt><dd data-res="credito">—</dd></div>
        <div><dt>Mensualidad estimada</dt><dd data-res="mensualidad">—</dd></div>
        <div class="es-total"><dt>Liquidez inicial necesaria</dt><dd data-res="liquidez">—</dd></div>
    </dl>

    <a class="boton boton--whatsapp" data-res="whatsapp" href="<?= e(whatsapp_url('Hola, usé la calculadora de su blog y me gustaría recibir asesoría para tramitar mi crédito.')) ?>" target="_blank" rel="noopener">
        <?= icono('whatsapp') ?> Más información por WhatsApp
    </a>
    <p class="calculadora__nota">Cifras estimadas con fines informativos; no constituyen una oferta de crédito.</p>
</div>
