# CLAUDE.md — duanbienesraices.com

Migración del sitio de **Du'an Bienes Raíces** (inmobiliaria en León, Gto.) de Wix a **PHP 8 puro + JSON**, para hosting en **SiteGround**.
Repo: `github.com/IEVN1001-22001664/duanbienesraices.com`, rama `main`. Especificación original: `C:\Users\ADMIN\Downloads\prompt-claude-code-duan.md`.
Documentación técnica más detallada en `docs/README.md` (estructura, JSON y panel).

## Reglas de trabajo (del cliente; obligatorias)

- Responder y comentar el código **en español**.
- **Stack fijo:** PHP 8 sin frameworks ni CMS, HTML semántico, CSS propio y JS vanilla, sin build ni npm. Las librerías van locales en `assets/vendor/`. **Preguntar antes** de cambiar el stack o la estructura.
- **No inventar datos de negocio** (teléfonos, cifras, testimonios, horarios). Si falta algo, dejar `<!-- TODO: contenido real — … -->`.
- Priorizar el rendimiento: WebP, `loading="lazy"`, CSS y JS mínimos, `font-display: swap`.
- Commits atómicos con mensajes en español y `Co-Authored-By`. Hacer **push solo cuando el usuario lo pide**.
- Todo lo que viene de un JSON se imprime con `e()`.

## Comandos

PHP 8.3 instalado con winget: `C:/Users/ADMIN/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe/php.exe`.
Tiene `php.ini` con mbstring, gd, fileinfo, openssl y curl.

```bash
# Servidor local (los -d hacen falta para subir fotos/videos en el panel)
php -d upload_max_filesize=48M -d post_max_size=64M -d memory_limit=512M -S localhost:8000 -t public_html dev-router.php
# Revisar sintaxis
for f in $(git ls-files '*.php'); do php -l "$f" | grep -v '^No syntax'; done
```

- En la app de escritorio el servidor se inicia con `preview_start` → `duan-php` (`.claude/launch.json`). Escucha en **:8000**, aunque la herramienta reporte otro puerto.
- El servidor integrado de PHP **no lee `.htaccess`**: `dev-router.php` imita las URLs limpias, las 301 y los bloqueos. Si se agrega una regla en `.htaccess`, hay que replicarla ahí.
- `config.local.php` (no se versiona) tiene `MODO_DEV`, `URL_SITIO=http://localhost:8000` y el usuario `editor` del panel. La contraseña local de prueba está anotada en un comentario del mismo archivo.

## Arquitectura

```
public_html/                 raíz web
  index.php, empresa.php (Nosotros), ofrezca-su-propiedad.php, contacto.php, aviso-de-privacidad.php, 404.php
  servicios/{renta,venta,administracion}.php   → includes/plantilla-servicio.php
  blog/index.php, blog/articulo.php (/blog/{slug}; slug_anterior de Wix → 301)
  sitemap.php (/sitemap.xml), robots.txt, .htaccess
  includes/   config.php (constantes del negocio, MENU, SERVICIOS, rutas), funciones.php, head/header/footer, whatsapp.php
  includes/admin/   núcleo del panel (ver abajo)
  components/ card-destacado, slide-hero, testimonio, video, card-blog, calculadora-gastos, cta-asesoria
  admin/      páginas del panel
  api/        (vacío; Fase 3: enviar.php)
  assets/     css/estilos.css, css/admin*.css, js/main.js, js/admin.js, img/ (iconos.svg sprite), fonts/ (Montserrat, Nunito Sans woff2)
  uploads/    imágenes y videos del contenido editable (NO versionado; .htaccess impide ejecutar código)
data/        JSON vivos, fuera de la web (NO versionado): hero, destacados, testimonios, videos .json + blog/*.json + backups/ + seguridad/
data-seed/   copia inicial versionada de data/
uploads-seed/ imágenes y videos iniciales versionados (los videos del hero pesan ~28 MB)
```

- **Primer arranque y deploys:** `inicializar_datos()` (llamada desde `config.php`) copia `data-seed/ → data/` y `uploads-seed/ → uploads/` **solo con archivos faltantes** y deja la marca `data/.inicializado`. Así un deploy nunca pisa lo que editó el cliente.
- **Lectura del contenido:**
  - `obtener_seccion($s)` devuelve solo los elementos activos y válidos; los inválidos se omiten y se registran en el log.
  - `obtener_posts()` / `obtener_post()` hacen lo mismo con el blog.
  - Las reglas de validación están en `ESQUEMAS` (`funciones.php`), compartidas entre el sitio y el panel.
- **Blog:** el contenido es Markdown y lo convierte `markdown()`, un renderer propio y seguro que escapa todo. Los shortcodes `[[calculadora-gastos]]` y `[[cta-asesoria]]` van solos en su línea.
- **main.js:**
  - Videos de YouTube con lite embed.
  - Galerías y carruseles con scroll-snap; cada uno guarda su propio índice.
  - El video del hero solo se carga a ≥768 px y sin reducir movimiento.
  - El mapa se carga al hacer clic.
  - Los formularios tienen `data-pendiente` y muestran un aviso de WhatsApp hasta la Fase 3.
  - Animación de entrada en celular.
- **Marca:** `--rojo #e21c21`, `--rojo-oscuro #b40000`, `--carbon #282d35`, `--carbon-2 #424a56`. Títulos en Montserrat y texto en Nunito Sans; reemplazan a Avenir, que tenía licencia.

### Panel /admin (Fase 2)

| Archivo | Rol |
|---|---|
| `includes/admin/nucleo.php` | Sesión (`__Host-` en HTTPS, Strict, expira tras 2 h sin uso o 12 h en total), login con `password_hash`, límite de 5 intentos en 15 min por IP, CSRF (campo `csrf` o cabecera `X-CSRF`, más revisión de `Origin`), avisos flash, cabeceras noindex/CSP |
| `includes/admin/almacen.php` | `SECCIONES_ADMIN` (campos, etiquetas, ayudas y tamaños de imagen de cada sección), `modificar_json()` (flock + temporal + rename, respaldo previo y control de versión por md5), respaldos (últimos 20 en `data/backups/{seccion}/` o `backups/blog/{slug}/`, con fecha y microsegundos), `limpiar_uploads()` |
| `includes/admin/imagenes.php` | MIME real con finfo, GD → WebP (calidad 80), orientación EXIF sin la extensión exif, modos `recortar`/`ajustar`, MP4 de hasta 40 MB |
| `includes/admin/formulario.php` | `recolectar()` (limpia y valida el POST) y `pintar_campo()` por tipo: texto, area, url, precio, opciones, youtube, fecha, imagen, fotos, video, markdown |
| `admin/accion.php` | POST: activar, eliminar, ordenar, mover, subir, restaurar. Responde JSON si la petición trae `X-Requested-With: fetch` |
| `admin/editar.php` + `vista-previa.php` | Formulario; la vista previa pinta el componente real del sitio en un iframe |
| `admin/.user.ini` | Límites de subida en SiteGround |

Decisiones del panel:
- **Sin SortableJS ni EasyMDE.** El arrastre con pointer events (funciona en pantallas táctiles), las flechas ▲▼ y el editor Markdown son JS propio.
- **Elementos nuevos:** aparecen al principio de la lista. El `id` sale del título y no cambia.
- **Slug del blog:** se fija al crear el artículo y no se puede reutilizar el de uno eliminado.
- **Archivos huérfanos:** solo se borran cuando no aparecen ni en el JSON ni en ningún respaldo, más 6 h de gracia para subidas sin guardar. Así «Restaurar» nunca rompe imágenes.
- **Tamaños de imagen:**
  - Destacados: `card` 720×540 (recorte) y `full` hasta 1400×1050.
  - Hero: 1920×1080.
  - Testimonios: 640×853.
  - Blog: portada 1200×800; imágenes dentro del texto hasta 1200.
- **Credenciales en producción:** `ADMIN_USUARIO` y `ADMIN_HASH` en `config.local.php` del servidor, generando una contraseña nueva (no usar la local).

## Decisiones tomadas en la Fase 1 (no revertir sin preguntar)

- **Fuera de alcance:** la sección Propiedades vive en Tokko. Su URL está en la constante `URL_TOKKO` (`https://www.duanbienesraices-buscador.com`); para cambiarla a un subdominio basta con modificar esa constante.
- **Páginas y archivos agregados:** carpeta `uploads-seed/`, `plantilla-servicio.php`, página `/aviso-de-privacidad`, componentes de calculadora y CTA, y `dev-router.php`.
- **Menú:** los Servicios no están en el menú principal, igual que en Wix; se enlazan desde las tarjetas del inicio y el footer.
- **Destacados:** galerías de hasta 12 fotos; el precio se guarda como número y se muestra con `formato_precio()`.
- **Videos:** el video del hero de Nosotros (24 MB) se quitó y se dejó un póster. El póster de la intro es el cuadro del logo a los 6 s del video.
- **WhatsApp:** se usa el número 524775514226; algunas páginas de Wix usaban 4771599316.
- **Blog:** los artículos tienen slugs nuevos y los de Wix redirigen con 301 (`/post/{slug}` → `/blog/{slug}` → `slug_anterior`).
- **Animación de entrada en celular** (réplica de la de Wix, commit f1e98d3):
  - Secuencia: una línea roja se abre, aparece el logo, el rojo se cierra en una línea y se desvanece.
  - Dura unos 3 s.
  - Solo a menos de 768 px, con JS y sin reducir movimiento.
  - Una vez por visita: la marca `sessionStorage['duan-intro']` y la clase `intro-vista` en `<html>`.
  - Código: marcado en `head.php`, estilos al final de `estilos.css` y lógica al final de `main.js`.

## Estado actual (2 nov 2026)

- **Fase 1** (maquetado y SEO) y **Fase 2** (panel) terminadas y subidas a `main`. Último commit: `f1e98d3`.
- El panel pasó 56 pruebas de extremo a extremo y la revisión en el navegador a 375 y 1280 px.
- **Sin commit:** `public_html/includes/footer.php` quita la línea del teléfono (477) 688 6298 del pie. Lo cambió el usuario y no lo confirmó. Preguntar si se sube o se descarta; no incluirlo en otros commits.
- Fuera del repo se entregaron un PDF de la maqueta de Inicio y una maqueta editable en Figma (https://www.figma.com/design/nIUU1M1w4JMsLv48ow0lg6).

## Pendientes

### Fase 3 — formularios y lanzamiento (siguiente paso)

- **`public_html/api/enviar.php` con PHPMailer local** en `assets/vendor/` o en `includes/`, usando el SMTP de SiteGround.
  - Las constantes `SMTP_*` y `TURNSTILE_*` ya están en `config.local.example.php`.
  - Agregar honeypot, Cloudflare Turnstile o reCAPTCHA (preguntar cuál) y mensaje de éxito o error.
  - Quitar `data-pendiente` de los formularios.
- **Campos de los formularios:**
  - `contacto.php`: nombre, email, telefono, asunto, mensaje.
  - `ofrezca-su-propiedad.php`: nombre, email, whatsapp, operacion, tipo_propiedad, descripcion.
  - Los dos traen el campo oculto `formulario` y el honeypot `sitio_web`.
- **Datos que hay que pedir al usuario:** la cuenta SMTP y el correo de destino.
- **Documentación:**
  - `docs/deploy.md`: deploy a SiteGround por Git o SFTP, crear `config.local.php` en el servidor y revisar los permisos de escritura de `data/` y `uploads/`.
  - `docs/guia-panel.md`: guía para la persona que edita, sin experiencia técnica.
- **Checklist de lanzamiento:**
  - DNS y registros MX, para no tumbar el correo `@duanbienesraices.com`.
  - HTTPS.
  - Search Console.
  - Verificar las 301.
  - Quitar `MODO_DEV`.

### Contenido real que falta (marcado con TODO; no inventar)

- **`empresa.php`:**
  - Foto del Arq. Alejandro Jaqués Martínez.
  - Mensaje del fundador.
  - Una cifra adicional.
  - Confirmar si el apellido es "Jaqués" (como en la especificación) o "Jáquez" (como en el blog).
- **`contacto.php`:** horario de atención.
- **`ofrezca-su-propiedad.php`:** en Wix había un widget que no se pudo leer; confirmar si se necesita.
- **`testimonios.json`:** 5 de 6 no tienen `url_resena` individual; por ahora enlazan al perfil general de Google.

### Opcional

- Llenar un destacado desde la API de Tokko pegando el ID de la propiedad, con caché de algunas horas. Necesita `TOKKO_API_KEY` y la especificación pide validarlo antes.
