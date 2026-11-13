<?php
/**
 * Enlace del correo de confirmación: deja el correo pendiente como el correo de la cuenta.
 * No pide sesión, porque el enlace puede abrirse en otro dispositivo (el celular, por ejemplo).
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';

$correo = cuenta_confirmar_correo((string) ($_GET['t'] ?? ''));

if ($correo !== null && sesion_activa()) {
    avisar('Correo confirmado: ' . $correo);
    redirigir('/admin/cuenta.php');
}

admin_cabecera('Confirmar correo');
?>
<section class="acceso">
    <?php if ($correo !== null): ?>
        <h1>Correo confirmado</h1>
        <p class="aviso aviso--exito" role="status">Listo: <strong><?= e($correo) ?></strong> ya es el correo de la cuenta del panel.</p>
        <p class="acceso__enlace"><a href="/admin/">Ir al panel</a></p>
    <?php else: ?>
        <h1>El enlace ya no sirve</h1>
        <p class="aviso aviso--error" role="alert">Este enlace venció o ya se usó. Entra al panel y pide uno nuevo en «Mi cuenta».</p>
        <p class="acceso__enlace"><a href="/admin/cuenta.php">Ir a Mi cuenta</a></p>
    <?php endif; ?>
</section>
<?php admin_pie();
