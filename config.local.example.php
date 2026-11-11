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
// Clave de la API de Tokko (Configuración de la empresa > Permisos/API). Con ella los
// destacados del inicio se llenan solos desde Tokko (includes/tokko.php).
// define('TOKKO_API_KEY', '');

// --- Panel /admin (Fase 2) ---
// Genera el hash con: php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT);"
// define('ADMIN_USUARIO', 'editor');
// define('ADMIN_HASH', '');

// --- Correo de los formularios (Fase 3) ---
// La cuenta mkt@ envía y los mensajes llegan a info@. El correo está en Google Workspace.
// SMTP_CLAVE es una «contraseña de aplicación» de mkt@ (no su contraseña normal):
// requiere la verificación en 2 pasos activa en esa cuenta y se crea en
// myaccount.google.com > Seguridad > Contraseñas de aplicaciones (16 letras, sin espacios).
// Puerto 465 = SSL; 587 = STARTTLS. En local, si faltan, el correo se guarda en data/correos-dev/.
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PUERTO', 465);
// define('SMTP_USUARIO', 'mkt@duanbienesraices.com');
// define('SMTP_CLAVE', '');
// define('SMTP_DESTINO', 'info@duanbienesraices.com');

// --- Anti-spam: Cloudflare Turnstile (Fase 3) ---
// Claves en dash.cloudflare.com > Turnstile, con el dominio www.duanbienesraices.com.
// En local se pueden usar las claves públicas de prueba (siempre aprueban):
//   site key 1x00000000000000000000AA · secret 1x0000000000000000000000000000000AA
// En producción, sin claves, los formularios rechazan los envíos.
// define('TURNSTILE_SITE_KEY', '');
// define('TURNSTILE_SECRET', '');
