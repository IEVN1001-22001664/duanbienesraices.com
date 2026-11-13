<?php
/**
 * Enlace de «Olvidé mi contraseña»: con un token vigente deja elegir una contraseña nueva.
 * El token sirve una sola vez y dura 30 minutos.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';

$token = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$valido = cuenta_token_restablecer_valido($token);
$error = null;

if ($valido && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verificar_csrf();
    $cuenta = cuenta();
    $nueva = (string) ($_POST['clave_nueva'] ?? '');
    $error = validar_clave_nueva($nueva, (string) ($_POST['clave_repetida'] ?? ''), $cuenta['usuario']);
    if ($error === null) {
        cuenta_guardar_clave($nueva);          // también anula el token y cierra las sesiones abiertas
        limpiar_intentos_login();
        cuenta_avisar_cambio_clave();
        cerrar_sesion();
        session_start();
        avisar('Contraseña actualizada. Ya puedes entrar con la nueva.');
        redirigir('/admin/login.php');
    }
}

// Ojo: no poner aquí «Referrer-Policy: no-referrer». Con esa política el navegador manda
// «Origin: null» al enviar el formulario y verificar_csrf() lo rechaza. La política general
// del panel (same-origin, en nucleo.php) ya impide que el token salga del sitio.

admin_cabecera('Contraseña nueva');
?>
<section class="acceso">
    <?php if (!$valido): ?>
        <h1>El enlace ya no sirve</h1>
        <p class="aviso aviso--error" role="alert">Este enlace venció o ya se usó. Pide uno nuevo.</p>
        <p class="acceso__enlace"><a href="/admin/recuperar.php">Pedir otro enlace</a></p>
    <?php else: ?>
        <h1>Elige una contraseña nueva</h1>
        <?php if ($error): ?><p class="aviso aviso--error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form class="formulario acceso__form" method="post" action="/admin/restablecer.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="t" value="<?= e($token) ?>">
            <input type="text" name="usuario" value="<?= e(cuenta()['usuario']) ?>" autocomplete="username" hidden>
            <div class="campo">
                <label class="campo__etiqueta" for="clave-nueva">Contraseña nueva</label>
                <ul class="reglas-clave" id="reglas-clave">
                    <?php foreach (reglas_clave() as $regla): ?><li><?= e($regla) ?></li><?php endforeach; ?>
                </ul>
                <input class="campo__control" type="password" id="clave-nueva" name="clave_nueva" minlength="<?= CLAVE_MIN ?>" maxlength="<?= CLAVE_MAX ?>" autocomplete="new-password" aria-describedby="reglas-clave" required autofocus>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="clave-repetida">Repite la contraseña</label>
                <input class="campo__control" type="password" id="clave-repetida" name="clave_repetida" minlength="<?= CLAVE_MIN ?>" maxlength="<?= CLAVE_MAX ?>" autocomplete="new-password" required>
            </div>
            <button class="boton boton--grande" type="submit">Guardar contraseña</button>
        </form>
    <?php endif; ?>
</section>
<?php admin_pie();
