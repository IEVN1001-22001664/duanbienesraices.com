# duanbienesraices.com — guía técnica

Sitio de Du'an Bienes Raíces en **PHP 8 puro** (sin frameworks), pensado para SiteGround.
El contenido que cambia seguido vive en JSON y se pinta con plantillas fijas.

## Estructura

```
public_html/        → lo público (raíz web)
  includes/         → config.php, funciones.php, head/header/footer, whatsapp, plantilla-servicio
  components/       → plantillas de cada bloque editable (destacado, slide, testimonio, video, blog…)
  assets/           → css/, js/, img/, fonts/, vendor/
  uploads/          → imágenes subidas desde el panel (NO se versiona)
  blog/             → index.php (listado) y articulo.php (/blog/{slug})
  servicios/        → renta, venta, administracion
data/               → JSON vivos de producción (NO se versiona, fuera de la web)
data-seed/          → copia inicial de los JSON (versionada)
uploads-seed/       → imágenes iniciales de destacados, hero, testimonios y blog (versionada)
dev-router.php      → solo para el servidor local de PHP
config.local.example.php → plantilla de credenciales
```

## Primer arranque y deploys

`includes/config.php` llama a `inicializar_datos()`: si `data/.inicializado` no existe, copia
`data-seed/ → data/` y `uploads-seed/ → public_html/uploads/` **solo con archivos que falten**.
Por eso un deploy nunca pisa lo que el editor cambió en producción.

- `data/`, `public_html/uploads/` y `config.local.php` están en `.gitignore`.
- Para cambiar la URL de Tokko (p. ej. a un subdominio) basta con `URL_TOKKO` en `config.php`
  o en `config.local.php`.

## Probar en local

Requiere PHP 8.1+ con extensiones `mbstring`, `json`, `fileinfo` y `gd` (con soporte WebP).

```bash
php -d upload_max_filesize=48M -d post_max_size=64M -d memory_limit=512M -S localhost:8000 -t public_html dev-router.php
```

(Los `-d` solo hacen falta para subir fotos y videos desde el panel; en SiteGround los pone `admin/.user.ini`).

El router imita las URLs limpias y las 301 del `.htaccess` (el servidor integrado de PHP no lee `.htaccess`).
Para no indexar ni usar la URL de producción en local, crea `config.local.php` con:

```php
<?php
define('MODO_DEV', true);
define('URL_SITIO', 'http://localhost:8000');
```

## Secciones editables (JSON)

| Archivo | Componente | Notas |
|---|---|---|
| `hero.json` | `slide-hero.php` | imagen (póster) + video opcional; el video solo se carga en pantallas ≥768 px |
| `destacados.json` | `card-destacado.php` | `precio` numérico; fotos en 2 tamaños (`card`, `full`); máx. 12 |
| `testimonios.json` | `testimonio.php` | `url_resena` vacía → enlaza al perfil general de Google |
| `videos.json` | `video.php` | solo el ID de YouTube (11 caracteres) |
| `blog/*.json` | `card-blog.php`, `articulo.php` | `contenido` en Markdown; `[[calculadora-gastos]]` y `[[cta-asesoria]]` insertan componentes |

Las reglas de validación están en `ESQUEMAS` (`funciones.php`); un elemento inválido se omite y se registra en el log de errores sin tumbar la página.

## Panel /admin

Panel propio en PHP para editar Destacados, Hero, Testimonios, Videos y Blog sin tocar código.

### Configurar el acceso

En `config.local.php` (no se versiona):

```php
define('ADMIN_USUARIO', 'editor');
define('ADMIN_HASH', '...');   // php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_DEFAULT);"
```

Sin estas dos constantes el login muestra un aviso y no deja entrar.

### Qué hace

| Función | Cómo |
|---|---|
| Sesión | cookie `HttpOnly` + `SameSite=Strict` (+ `Secure` y prefijo `__Host-` en HTTPS); expira tras 2 h sin uso o 12 h en total |
| CSRF | token por sesión en todos los formularios y en las peticiones `fetch` (cabecera `X-CSRF`); además se revisa `Origin` |
| Límite de intentos | 5 fallos por IP en 15 min → bloqueo de 15 min (`data/seguridad/intentos-login.json`) |
| CRUD | agregar, editar, eliminar, mostrar/ocultar sin borrar; reordenar arrastrando (ratón y táctil) o con flechas ▲▼ |
| Validación | campos obligatorios, precio numérico, URL válida, ID de YouTube, máx. 12 fotos y peso de archivo; al final se aplican las mismas reglas de `ESQUEMAS` que usa el sitio |
| Imágenes | MIME real con `finfo`, se redibujan con GD a WebP (quita metadatos/GPS y corrige la orientación de fotos de celular), nombres nuevos aleatorios; destacados en 2 tamaños: `card` 720×540 y `full` ≤1400×1050 |
| Videos del hero | MP4 de hasta 40 MB |
| Guardado seguro | `flock` + archivo temporal + `rename`; respaldo con fecha en `data/backups/` (últimos 20 por archivo) y botón «Restaurar versión anterior» |
| Ediciones simultáneas | cada formulario lleva el hash del JSON que leyó; si otra persona guardó en medio, se rechaza en vez de pisar su cambio |
| Vista previa | el formulario pinta el componente real del sitio en un `<iframe>` mientras se escribe |
| Blog | editor Markdown con barra de formato, subida de imágenes dentro del texto e inserción de `[[calculadora-gastos]]` / `[[cta-asesoria]]`; la dirección (`slug`) se fija al crear el artículo |

### Limpieza de archivos

Las fotos de un elemento eliminado (o reemplazadas) se borran de `uploads/` en cuanto dejan de aparecer
en el JSON **y** en sus respaldos. Así «Restaurar versión anterior» nunca deja imágenes rotas.
Las subidas que nunca se guardaron se borran pasadas 6 h.

### Archivos

```
public_html/admin/             → páginas del panel (login, inicio, sección, editar, respaldos, vista previa, acciones)
public_html/admin/.user.ini    → límites de subida en SiteGround
public_html/includes/admin/    → núcleo (sesión, CSRF), almacén (JSON, respaldos), imágenes, formularios, plantilla
public_html/assets/css/admin.css, admin-previa.css, assets/js/admin.js
```

No se usaron SortableJS ni EasyMDE: el arrastre y el editor son JS propio (≈ 20 KB sin dependencias).
