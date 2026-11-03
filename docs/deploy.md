# Deploy a SiteGround

Guía para subir el sitio por primera vez y para las actualizaciones. Los menús son los de **Site Tools** de SiteGround.
El DNS se queda en Wix (ver `dns-respaldo-wix.md`); aquí solo se cambian los registros que apuntan al sitio.

## Antes de empezar

Tener a la mano, guardados en un lugar seguro (nunca en el repo ni en el chat):

- Contraseña de aplicación de `mkt@duanbienesraices.com` (16 letras, sin espacios).
- Site Key y Secret Key de Cloudflare Turnstile.
- Hash de la contraseña del panel (`$2y$…`), generado con:
  ```
  php -r "echo password_hash(trim(fgets(STDIN)), PASSWORD_DEFAULT), PHP_EOL;"
  ```

## 1. Crear el sitio

1. En SiteGround: **Websites → New Website → Existing Domain** → `duanbienesraices.com`. No aceptar el cambio de nameservers ni el correo de SiteGround: el correo sigue en Google Workspace.
2. **Devs → PHP Manager** → PHP **8.3**.
3. **Speed → Caching**: dejar la caché dinámica apagada, o excluir `/admin` y `/api`, para que el panel y los formularios no se queden en caché.

## 2. Subir el código

La carpeta del sitio en el servidor es `~/www/duanbienesraices.com/`, con `public_html/` adentro: la misma estructura del repo.

### Opción A — Git por SSH (recomendada)

1. **Devs → SSH Keys Manager** → crear una llave y conectarse (puerto **18765**). Los datos de conexión salen en el mismo menú.
2. Borrar lo que SiteGround pone por defecto y clonar el repo (es público, no pide contraseña):
   ```
   cd ~/www/duanbienesraices.com
   rm -f public_html/index.html public_html/default.php
   git init
   git remote add origin https://github.com/IEVN1001-22001664/duanbienesraices.com.git
   git fetch origin main
   git checkout -f -B main origin/main
   ```
3. **Actualizaciones** posteriores:
   ```
   cd ~/www/duanbienesraices.com && git pull --ff-only
   ```
   `data/`, `public_html/uploads/` y `config.local.php` no están en Git, así que un `pull` nunca pisa lo que se editó en el panel.

### Opción B — SFTP (FileZilla)

Subir a `~/www/duanbienesraices.com/` las carpetas `public_html/`, `data-seed/` y `uploads-seed/` y el archivo `config.local.example.php`.
**No subir:** `.git/`, `data/`, `public_html/uploads/` (salvo su `.htaccess`), `config.local.php` local, `dev-router.php`, `docs/`.

## 3. Crear `config.local.php` en el servidor

Va en `~/www/duanbienesraices.com/config.local.php`, junto a `public_html/`, **no dentro**. Partir de `config.local.example.php`:

```php
<?php
// Producción: SIN MODO_DEV y SIN URL_SITIO (el valor por defecto es https://www.duanbienesraices.com)
define('ADMIN_USUARIO', 'editor');
define('ADMIN_HASH', '$2y$…');                       // comillas simples: el hash lleva $

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PUERTO', 465);
define('SMTP_USUARIO', 'mkt@duanbienesraices.com');
define('SMTP_CLAVE', '…');                           // contraseña de aplicación
define('SMTP_DESTINO', 'info@duanbienesraices.com');

define('TURNSTILE_SITE_KEY', '0x4…');
define('TURNSTILE_SECRET', '0x4…');
```

Permisos: `chmod 600 config.local.php`.

## 4. Permisos y primer arranque

1. Abrir el sitio una vez: `inicializar_datos()` crea `data/` y `public_html/uploads/` copiando `data-seed/` y `uploads-seed/` (~45 MB, la primera carga tarda unos segundos).
2. Revisar que existan `data/.inicializado` y las imágenes en `public_html/uploads/`.
3. PHP tiene que poder escribir en `data/` y `public_html/uploads/` (en SiteGround, carpetas `755` y archivos `644` funcionan porque PHP corre con el mismo usuario).

## 5. Probar antes de cambiar el DNS

SiteGround da un dominio temporal (`….sg-host.com`) en **Site Tools → Dashboard**.

- Agregar ese dominio temporal en el widget de Turnstile (Cloudflare → Turnstile → el widget → Hostnames) mientras se prueba; quitarlo después.
- Probar: todas las páginas, el blog, el panel (`/admin`: entrar, editar, subir una foto, restaurar) y los dos formularios con un envío real que llegue a info@.
- Los enlaces y el canonical apuntan a `www.duanbienesraices.com`; es normal mientras se prueba en el dominio temporal.

## 6. Cambiar el DNS (en Wix)

En Wix → Dominios → Administrar registros DNS, cambiar solo estos (ver `dns-respaldo-wix.md`):

| Nombre | Tipo | Valor nuevo |
|---|---|---|
| `@` | A | IP de SiteGround (**Site Tools → Dashboard → Site IP**); borrar las 3 A de Wix |
| `www` | CNAME | `duanbienesraices.com` |
| `es`, `en` | CNAME | `duanbienesraices.com` |

**No tocar:** MX, TXT de verificación, `propiedades`, `www.propiedades`.
Puede tardar hasta unas horas en propagarse (TTL de 1 h).

## 7. SSL

1. **Security → SSL Manager** → Let's Encrypt para `duanbienesraices.com` y `www`. Si deja agregar `es.` y `en.`, también (o un Let's Encrypt Wildcard).
2. El `.htaccess` ya fuerza `https://www.`; no hace falta activar «HTTPS Enforce».

## 8. Revisión final

- `http://duanbienesraices.com`, `https://duanbienesraices.com` y `es.duanbienesraices.com` → 301 a `https://www.duanbienesraices.com/`.
- `/post/_hola` → artículo nuevo; `/faq` → inicio.
- 403 en `/includes/config.php`, `/.htaccess` y en cualquier `.php` dentro de `/uploads/`. `data/` y `config.local.php` están fuera de `public_html/`, así que ni siquiera tienen URL.
- No aparece `noindex` en el código fuente de las páginas (si aparece, quedó `MODO_DEV` activo).
- Search Console: enviar `https://www.duanbienesraices.com/sitemap.xml`.
- Hasta entonces, no cancelar ni borrar el sitio de Wix.
