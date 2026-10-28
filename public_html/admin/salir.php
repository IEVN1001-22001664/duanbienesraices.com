<?php
/**
 * Cierra la sesión (solo por POST con CSRF, para que una liga externa no pueda sacarte).
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';

verificar_csrf();
cerrar_sesion();
session_start();
avisar('Cerraste sesión.');
redirigir('/admin/login.php');
