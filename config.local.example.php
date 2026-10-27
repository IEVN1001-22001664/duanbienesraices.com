<?php
/**
 * Plantilla de configuración local / de servidor.
 *
 * 1. Copia este archivo como config.local.php (mismo nivel que public_html/).
 * 2. Llena los valores. config.local.php NO se sube a Git.
 */

// --- Entorno ---
// define('MODO_DEV', true);                         // muestra errores y agrega noindex
// define('URL_SITIO', 'http://localhost:8000');    // canonical/OG en local

// --- Rutas (solo si en el servidor data/ no queda junto a public_html/) ---
// define('DIR_DATA', '/home/customer/www/duanbienesraices.com/data');

// --- Tokko Broker (si cambia a subdominio) ---
// define('URL_TOKKO', 'https://propiedades.duanbienesraices.com');
// define('TOKKO_API_KEY', '');                    // Fase 2 (opcional)

// --- Panel /admin (Fase 2) ---
// Genera el hash con: php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT);"
// define('ADMIN_USUARIO', 'editor');
// define('ADMIN_HASH', '');

// --- Correo SMTP de SiteGround (Fase 3) ---
// define('SMTP_HOST', 'mail.duanbienesraices.com');
// define('SMTP_PUERTO', 465);
// define('SMTP_USUARIO', 'web@duanbienesraices.com');
// define('SMTP_CLAVE', '');
// define('SMTP_DESTINO', 'info@duanbienesraices.com');

// --- Anti-spam (Fase 3) ---
// define('TURNSTILE_SITE_KEY', '');
// define('TURNSTILE_SECRET', '');
