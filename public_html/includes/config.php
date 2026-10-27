<?php
/**
 * Configuración general del sitio DU'AN Bienes Raíces.
 *
 * Aquí viven las constantes que no son secretas. Las credenciales
 * (SMTP, hash del admin, API key de Tokko) van en config.local.php,
 * que NO se versiona. Usa config.local.example.php como plantilla.
 */

declare(strict_types=1);

// --- Rutas -----------------------------------------------------------------
// public_html/ es la raíz web; data/ queda un nivel arriba, fuera de la web.
// Así funciona igual en local (repo) y en SiteGround (~/www/dominio/).
define('RAIZ_WEB', dirname(__DIR__));             // .../public_html
define('RAIZ_PROYECTO', dirname(RAIZ_WEB));        // .../duanbienesraices.com

// Credenciales y ajustes por entorno (opcional). Puede sobrescribir DIR_DATA, URL_SITIO, etc.
$configLocal = RAIZ_PROYECTO . '/config.local.php';
if (is_file($configLocal)) {
    require $configLocal;
}

defined('DIR_DATA')          || define('DIR_DATA', RAIZ_PROYECTO . '/data');
defined('DIR_DATA_SEED')     || define('DIR_DATA_SEED', RAIZ_PROYECTO . '/data-seed');
defined('DIR_UPLOADS')       || define('DIR_UPLOADS', RAIZ_WEB . '/uploads');
defined('DIR_UPLOADS_SEED')  || define('DIR_UPLOADS_SEED', RAIZ_PROYECTO . '/uploads-seed');

// --- Sitio -----------------------------------------------------------------
// URL canónica (sin diagonal final). Se usa en canonical, Open Graph y sitemap.
defined('URL_SITIO')  || define('URL_SITIO', 'https://www.duanbienesraices.com');
defined('MODO_DEV')   || define('MODO_DEV', false);

define('NOMBRE_SITIO', "Du'an Bienes Raíces");
define('SLOGAN', 'Su Patrimonio en Buenas Manos');

// --- Tokko Broker (sección Propiedades) --------------------------------------
// El listado y las fichas viven en Tokko. Si más adelante se usa un subdominio
// (p. ej. https://propiedades.duanbienesraices.com) solo se cambia aquí.
defined('URL_TOKKO') || define('URL_TOKKO', 'https://www.duanbienesraices-buscador.com');
define('URL_TOKKO_PROPIEDADES', URL_TOKKO . '/Propiedades');

// --- Contacto ----------------------------------------------------------------
define('TELEFONO', '(477) 688 6298');
define('TELEFONO_E164', '+524776886298');
define('WHATSAPP_NUMERO', '524775514226');
define('WHATSAPP_VISIBLE', '477 551 4226');
define('WHATSAPP_MENSAJE', '¡Hola! Vengo de su página web, tengo interés de...');
define('EMAIL_CONTACTO', 'info@duanbienesraices.com');

define('DIRECCION', [
    'calle'     => 'Valle del Yaqui 226',
    'colonia'   => 'Valle del Campestre',
    'cp'        => '37150',
    'ciudad'    => 'León',
    'estado'    => 'Guanajuato',
    'pais'      => 'MX',
    'latitud'   => 21.1544435,
    'longitud'  => -101.6956241,
]);
define('URL_MAPS_OFICINA', 'https://maps.app.goo.gl/MaoVC45FjkedXB5w6');
define('URL_RESENAS_GOOGLE', 'https://maps.app.goo.gl/4i9H3U41759pjZ2AA');

define('REDES', [
    'facebook'  => 'https://www.facebook.com/duan.bienesraices/',
    'instagram' => 'https://www.instagram.com/duanbienesraices/',
    'youtube'   => 'https://www.youtube.com/channel/UCIA2vHhcntbKeaHo8bRdtHQ',
]);

// --- Verificaciones de buscadores (se conservan del sitio en Wix) -------------
define('VERIFICACIONES', [
    'google-site-verification'     => 'Ln73FWFkTGN99kMFnjHcCWtuGRYt7Nptc9wjtTK34l0',
    'msvalidate.01'                => '31A090C2F346A8937C80FC8D580BD18D',
    'facebook-domain-verification' => 'ogbpl1tcaishbn2r9o64mvxsb8zx6t',
]);

// --- Menú principal ------------------------------------------------------------
// 'externo' => true abre en otra pestaña (Tokko).
define('MENU', [
    ['clave' => 'inicio',      'texto' => 'Inicio',              'url' => '/'],
    ['clave' => 'nosotros',    'texto' => 'Nosotros',            'url' => '/empresa'],
    ['clave' => 'propiedades', 'texto' => 'Propiedades',         'url' => URL_TOKKO_PROPIEDADES, 'externo' => true],
    ['clave' => 'propietario', 'texto' => 'Tengo una Propiedad', 'url' => '/ofrezca-su-propiedad'],
    ['clave' => 'blog',        'texto' => 'Blog',                'url' => '/blog'],
    ['clave' => 'contacto',    'texto' => 'Contacto',            'url' => '/contacto'],
]);

// Servicios: no están en el menú principal (igual que en Wix); se enlazan desde
// las tarjetas del inicio y el footer.
define('SERVICIOS', [
    ['clave' => 'renta',          'texto' => 'Renta',          'url' => '/servicios/renta'],
    ['clave' => 'venta',          'texto' => 'Venta',          'url' => '/servicios/venta'],
    ['clave' => 'administracion', 'texto' => 'Administración', 'url' => '/servicios/administracion'],
]);

// --- Errores ---------------------------------------------------------------------
if (MODO_DEV) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');

require __DIR__ . '/funciones.php';

// Primer arranque: copia data-seed/ y uploads-seed/ si aún no existen en el servidor.
inicializar_datos();
