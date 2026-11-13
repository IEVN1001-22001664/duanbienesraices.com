<?php
/**
 * Mi cuenta: correo (con confirmación por enlace) y contraseña del único usuario del panel.
 * Todo cambio pide la contraseña actual.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

$errores = [];
$correoEscrito = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verificar_csrf();
    $accion = (string) ($_POST['accion'] ?? '');
    $cuenta = cuenta();

    if ($accion === 'cancelar_pendiente') {
        cuenta_modificar(function (array $c) {
            unset($c['pendiente']);
            return [$c, null];
        });
        avisar('Se canceló el cambio de correo.');
        redirigir('/admin/cuenta.php');
    }

    // Lo demás pide la contraseña actual; los fallos cuentan para el bloqueo por intentos
    if ($espera = login_bloqueado()) {
        $errores[$accion] = 'Demasiados intentos fallidos. Espera ' . (int) ceil($espera / 60) . ' min y vuelve a intentar.';
    } elseif (!password_verify((string) ($_POST['clave_actual'] ?? ''), $cuenta['hash'])) {
        registrar_fallo_login();
        usleep(random_int(300000, 700000));
        $errores[$accion] = 'La contraseña actual no es correcta.';
    } elseif ($accion === 'correo') {
        $correoEscrito = trim((string) ($_POST['correo'] ?? ''));
        $correo = mb_strtolower($correoEscrito);
        if (mb_strlen($correo) > 120 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Escribe un correo válido, por ejemplo nombre@dominio.com.';
        } elseif ($correo === mb_strtolower((string) ($cuenta['correo'] ?? ''))) {
            $errores['correo'] = 'Ese ya es el correo confirmado de la cuenta.';
        } elseif ($error = cuenta_pedir_confirmacion($correo)) {
            $errores['correo'] = $error;
        } else {
            avisar('Te enviamos un enlace a ' . $correo . '. Ábrelo para confirmar el correo (vence en 24 horas).');
            redirigir('/admin/cuenta.php');
        }
    } elseif ($accion === 'clave') {
        $nueva = (string) ($_POST['clave_nueva'] ?? '');
        if ($error = validar_clave_nueva($nueva, (string) ($_POST['clave_repetida'] ?? ''), $cuenta['usuario'])) {
            $errores['clave'] = $error;
        } elseif (password_verify($nueva, $cuenta['hash'])) {
            $errores['clave'] = 'La contraseña nueva debe ser distinta de la actual.';
        } else {
            cuenta_guardar_clave($nueva);
            session_regenerate_id(true);
            cuenta_avisar_cambio_clave();
            avisar('Contraseña actualizada. Las demás sesiones abiertas se cerraron.');
            redirigir('/admin/cuenta.php');
        }
    }
}

$cuenta = cuenta();
$correo = (string) ($cuenta['correo'] ?? '');
$pendiente = $cuenta['pendiente'] ?? null;
if (is_array($pendiente) && time() > (int) ($pendiente['expira'] ?? 0)) {
    $pendiente = null;
}

admin_cabecera('Mi cuenta', [['Inicio', '/admin/'], ['Mi cuenta', null]]);
?>
<h1>Mi cuenta</h1>
<div class="cuenta">
    <section class="formulario" aria-labelledby="titulo-correo">
        <h2 id="titulo-correo">Correo de la cuenta</h2>
        <p class="campo__ayuda">A este correo llega el enlace de «Olvidé mi contraseña» y el aviso cuando la contraseña cambia. Debe ser un buzón que revises tú.</p>
        <p class="cuenta__estado">
            <?php if ($correo !== ''): ?>
                <strong><?= e($correo) ?></strong> <span class="etiqueta-estado etiqueta-estado--ok">Confirmado</span>
            <?php else: ?>
                <span class="etiqueta-estado etiqueta-estado--falta">Sin correo confirmado</span>
                Mientras no confirmes uno, no podrás recuperar la contraseña por correo.
            <?php endif; ?>
        </p>

        <?php if ($pendiente): ?>
            <p class="aviso" role="status">Falta confirmar <strong><?= e($pendiente['correo']) ?></strong>: abre el enlace que te enviamos. Revisa también la carpeta de spam.</p>
            <div class="cuenta__pendiente">
                <form method="post" action="/admin/cuenta.php">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="accion" value="cancelar_pendiente">
                    <button class="boton boton--secundario boton--chico" type="submit">Cancelar este cambio</button>
                </form>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/cuenta.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="correo">
            <div class="campo<?= isset($errores['correo']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="correo"><?= $correo !== '' ? 'Correo nuevo' : 'Correo' ?></label>
                <input class="campo__control" type="email" id="correo" name="correo" value="<?= e($correoEscrito) ?>" maxlength="120" autocomplete="email" required>
                <?php if (isset($errores['correo'])): ?><p class="campo__error" role="alert"><?= e($errores['correo']) ?></p><?php endif; ?>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="clave-actual-correo">Contraseña actual</label>
                <input class="campo__control" type="password" id="clave-actual-correo" name="clave_actual" autocomplete="current-password" required>
            </div>
            <button class="boton" type="submit"><?= $pendiente ? 'Enviar enlace a este correo' : 'Enviar enlace de confirmación' ?></button>
        </form>
    </section>

    <section class="formulario" aria-labelledby="titulo-clave">
        <h2 id="titulo-clave">Cambiar contraseña</h2>
        <form method="post" action="/admin/cuenta.php">
            <?= campo_csrf() ?>
            <input type="hidden" name="accion" value="clave">
            <input type="text" name="usuario" value="<?= e($cuenta['usuario']) ?>" autocomplete="username" hidden>
            <div class="campo<?= isset($errores['clave']) ? ' campo--error' : '' ?>">
                <label class="campo__etiqueta" for="clave-actual">Contraseña actual</label>
                <input class="campo__control" type="password" id="clave-actual" name="clave_actual" autocomplete="current-password" required>
                <?php if (isset($errores['clave'])): ?><p class="campo__error" role="alert"><?= e($errores['clave']) ?></p><?php endif; ?>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="clave-nueva">Contraseña nueva</label>
                <ul class="reglas-clave" id="reglas-clave">
                    <?php foreach (reglas_clave() as $regla): ?><li><?= e($regla) ?></li><?php endforeach; ?>
                </ul>
                <input class="campo__control" type="password" id="clave-nueva" name="clave_nueva" minlength="<?= CLAVE_MIN ?>" maxlength="<?= CLAVE_MAX ?>" autocomplete="new-password" aria-describedby="reglas-clave" required>
            </div>
            <div class="campo">
                <label class="campo__etiqueta" for="clave-repetida">Repite la contraseña nueva</label>
                <input class="campo__control" type="password" id="clave-repetida" name="clave_repetida" minlength="<?= CLAVE_MIN ?>" maxlength="<?= CLAVE_MAX ?>" autocomplete="new-password" required>
            </div>
            <button class="boton" type="submit">Guardar contraseña</button>
        </form>
    </section>
</div>
<?php admin_pie();
