<?php
/**
 * «Olvidé mi contraseña»: pide el correo de la cuenta y, si coincide con el confirmado,
 * manda un enlace para elegir una contraseña nueva. La respuesta es siempre la misma,
 * para no revelar cuál es el correo del panel.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';

if (sesion_activa()) {
    redirigir('/admin/cuenta.php');
}

$enviado = false;
$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verificar_csrf();
    if ($espera = login_bloqueado()) {
        $error = 'Demasiados intentos. Espera ' . (int) ceil($espera / 60) . ' min y vuelve a intentar.';
    } else {
        // Cada solicitud cuenta como intento: frena a quien quiera adivinar el correo
        registrar_fallo_login();
        cuenta_pedir_restablecer((string) ($_POST['correo'] ?? ''));
        $enviado = true;
    }
}

admin_cabecera('Recuperar contraseña');
?>
<section class="acceso">
    <h1>Recuperar contraseña</h1>
    <?php if ($enviado): ?>
        <p class="aviso aviso--exito" role="status">Si ese es el correo de la cuenta, en unos minutos te llegará un enlace para elegir una contraseña nueva. Vence en 30 minutos; revisa también la carpeta de spam.</p>
        <p class="acceso__enlace"><a href="/admin/login.php">Volver a iniciar sesión</a></p>
    <?php else: ?>
        <p>Escribe el correo de la cuenta del panel y te enviaremos un enlace para elegir una contraseña nueva.</p>
        <?php if ($error): ?><p class="aviso aviso--error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <form class="formulario acceso__form" method="post" action="/admin/recuperar.php">
            <?= campo_csrf() ?>
            <div class="campo">
                <label class="campo__etiqueta" for="correo">Correo</label>
                <input class="campo__control" type="email" id="correo" name="correo" maxlength="120" autocomplete="email" required autofocus>
            </div>
            <button class="boton boton--grande" type="submit">Enviar enlace</button>
        </form>
        <p class="acceso__enlace"><a href="/admin/login.php">Volver a iniciar sesión</a></p>
    <?php endif; ?>
</section>
<?php admin_pie();
