<?php
/**
 * Inicio de sesión del panel.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';

// Solo se permite volver a una página del propio panel
$volver = (string) ($_GET['volver'] ?? $_POST['volver'] ?? '/admin/');
if (!preg_match('#^/admin/[\w\-./?=&%]*$#', $volver) || str_contains($volver, '//')) {
    $volver = '/admin/';
}

if (sesion_activa()) {
    redirigir($volver);
}

$error = null;
$usuario = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verificar_csrf();
    $usuario = (string) ($_POST['usuario'] ?? '');
    $error = iniciar_sesion($usuario, (string) ($_POST['clave'] ?? ''));
    if ($error === null) {
        redirigir($volver);
    }
}

admin_cabecera('Iniciar sesión');
?>
<section class="acceso">
    <h1>Panel de contenido</h1>
    <p>Entra para editar las propiedades destacadas, el slider, los testimonios, los videos y el blog.</p>

    <?php if (!panel_configurado()): ?>
        <p class="aviso aviso--error" role="alert">
            El panel aún no tiene usuario. Define <code>ADMIN_USUARIO</code> y <code>ADMIN_HASH</code> en <code>config.local.php</code>
            (instrucciones en <code>config.local.example.php</code>).
        </p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="aviso aviso--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form class="formulario acceso__form" method="post" action="/admin/login.php">
        <?= campo_csrf() ?>
        <input type="hidden" name="volver" value="<?= e($volver) ?>">
        <div class="campo">
            <label class="campo__etiqueta" for="usuario">Usuario</label>
            <input class="campo__control" type="text" id="usuario" name="usuario" value="<?= e($usuario) ?>" autocomplete="username" autocapitalize="none" required autofocus>
        </div>
        <div class="campo">
            <label class="campo__etiqueta" for="clave">Contraseña</label>
            <input class="campo__control" type="password" id="clave" name="clave" autocomplete="current-password" required>
        </div>
        <button class="boton boton--grande" type="submit">Entrar</button>
    </form>
</section>
<?php admin_pie();
