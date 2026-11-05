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

El sitio se creó (5 nov 2026) con el **dominio temporal** `emmanuelalejandrom4.sg-host.com`. Todo (SSH, Git, despliegue automático, pruebas) se hace con él; el dominio real se conecta al final (paso 6).

1. **Devs → PHP Manager** → PHP **8.3**.
2. **Speed → Caching**: dejar la caché dinámica apagada, o excluir `/admin` y `/api`, para que el panel y los formularios no se queden en caché.
3. Mientras se use el temporal, el `.htaccess` manda `X-Robots-Tag: noindex` en cualquier `….sg-host.com`, para que Google no lo indexe. No hay que hacer nada para quitarlo.

## 2. Subir el código

La carpeta del sitio en el servidor es `~/www/emmanuelalejandrom4.sg-host.com/`, con `public_html/` adentro: la misma estructura del repo. (Verificarla con `ls ~/www` al entrar por SSH; en el resto de la guía, cambiar `duanbienesraices.com` por esa carpeta mientras se use el temporal.)

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

### Despliegue automático (GitHub Actions)

Con la opción A ya hecha, cada push a `main` actualiza el servidor solo: `.github/workflows/deploy.yml` entra por SSH y hace `git pull --ff-only`. El resultado de cada despliegue se ve en GitHub → pestaña **Actions** (palomita verde o tache rojo). También se puede lanzar a mano con **Run workflow**.

Configuración, una sola vez:

1. **Llave para GitHub** (no usar la personal). En SiteGround: **Devs → SSH Keys Manager → Generate**, con nombre `github-deploy` y una contraseña. Después, en esa llave, **Actions → Private Key** y copiar todo el texto (de `-----BEGIN` a `-----END … KEY-----`).
2. En GitHub: **Settings → Secrets and variables → Actions → New repository secret**:

   | Secreto | Valor |
   |---|---|
   | `SG_HOST` | Hostname de **SSH Keys Manager → Actions → SSH Credentials**. Si incluye el dominio temporal (`ssh.emmanuelalejandrom4.sg-host.com`), hay que actualizarlo en el paso 6 |
   | `SG_USUARIO` | Username de esa misma pantalla (`u123-…`) |
   | `SG_LLAVE` | La llave privada completa del paso 1 |
   | `SG_LLAVE_FRASE` | La contraseña que se le puso a la llave en el paso 1 |
   | `SG_KNOWN_HOSTS` | *Opcional:* salida de `ssh-keyscan -p 18765 HOST`. Si falta, se lee en cada despliegue |

3. Pestaña **Variables** de la misma pantalla:
   - `SG_RUTA` = `www/emmanuelalejandrom4.sg-host.com` **mientras se use el dominio temporal** (relativa a la carpeta del usuario; por defecto es `www/duanbienesraices.com`).
   - `SG_PUERTO`, solo si SiteGround da otro distinto de `18765`.
4. Probar con **Actions → Desplegar en SiteGround → Run workflow**. El último paso debe mostrar el último commit.

Notas:
- Mientras falten los secretos, la acción no falla: solo avisa que no está configurada y no despliega.
- Si alguien edita a mano en el servidor un archivo versionado, el `pull` se detiene (tache rojo) en lugar de pisar el cambio. Se arregla entrando por SSH: `git status` y `git checkout -- archivo` (o guardar el cambio en el repo).
- Contenido nuevo en `data-seed/` o `uploads-seed/` **no** se copia después del primer arranque (lo impide la marca `data/.inicializado`, para no pisar lo editado en el panel). Ese contenido se agrega desde el panel o copiándolo a mano.
- La llave privada y su contraseña solo van en los secretos de GitHub; nunca en el repo ni en el chat.

### Opción B — SFTP (FileZilla)

Subir a `~/www/duanbienesraices.com/` las carpetas `public_html/`, `data-seed/` y `uploads-seed/` y el archivo `config.local.example.php`.
**No subir:** `.git/`, `data/`, `public_html/uploads/` (salvo su `.htaccess`), `config.local.php` local, `dev-router.php`, `docs/`.

## 3. Crear `config.local.php` en el servidor

Va en la carpeta del sitio (hoy `~/www/emmanuelalejandrom4.sg-host.com/config.local.php`), junto a `public_html/`, **no dentro**. Partir de `config.local.example.php`:

```php
<?php
// Producción: SIN MODO_DEV. URL_SITIO solo mientras se prueba en el temporal; se borra en el paso 6
// (sin ella se usa https://www.duanbienesraices.com).
define('URL_SITIO', 'https://emmanuelalejandrom4.sg-host.com');

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

## 5. Probar en el dominio temporal

En `https://emmanuelalejandrom4.sg-host.com`:

- Agregar `emmanuelalejandrom4.sg-host.com` en el widget de Turnstile (Cloudflare → Turnstile → el widget → Hostnames) mientras se prueba; quitarlo después del paso 6.
- Probar: todas las páginas, el blog, el panel (`/admin`: entrar, editar, subir una foto, restaurar) y los dos formularios con un envío real que llegue a info@.
- El formulario y el panel revisan que la petición venga del mismo dominio que se está visitando, así que funcionan igual en el temporal.
- Las 301 de `http`/`www` solo actúan con `duanbienesraices.com`; en el temporal no redirigen, es normal.

## 6. Conectar el dominio real

El dominio **no se transfiere**: sigue registrado en Wix y se renueva ahí. Cancelar el plan Premium de Wix no lo quita. Solo se cambia a dónde apuntan sus registros DNS.

### 6.1 En SiteGround

1. **Websites → (el sitio) → ⋮ → Change Domain** (o «Change Primary Domain»): poner `duanbienesraices.com` y elegir **no** cambiar nameservers ni crear correo.
2. Entrar por SSH y revisar `ls ~/www`. Si la carpeta pasó a llamarse `duanbienesraices.com`, borrar la variable `SG_RUTA` en GitHub (su valor por defecto ya es ese); si no, dejarla como esté.
3. Si `SG_HOST` llevaba el dominio temporal, cambiarlo por el que muestre ahora **SSH Keys Manager → SSH Credentials**, y lanzar **Run workflow** para confirmar que el despliegue sigue funcionando.
4. Quitar la línea `URL_SITIO` de `config.local.php`.

### 6.2 En Wix (DNS)

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
