<?php
/**
 * Vista previa de un banner de código sin guardar: vista-previa.php deja el código en la sesión
 * y su <iframe> pide esta página, que lo sirve aislado igual que api/cartel.php.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();

cabeceras_cartel_codigo();
header('Cache-Control: no-store');
echo cartel_documento((string) ($_SESSION['previa_cartel_codigo'] ?? ''));
