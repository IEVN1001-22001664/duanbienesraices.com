# CLAUDE.md — duanbienesraices.com

Migración del sitio de **Du'an Bienes Raíces** (inmobiliaria en León, Gto.) de Wix a **PHP 8 puro + JSON**, para hosting en **SiteGround**.
Repo: `github.com/IEVN1001-22001664/duanbienesraices.com`, rama `main`. Especificación original: `C:\Users\ADMIN\Downloads\prompt-claude-code-duan.md`.
Documentación técnica más detallada en `docs/README.md` (estructura, JSON y panel).

## Reglas de trabajo (del cliente; obligatorias)

- Responder y comentar el código **en español**.
- **Stack fijo:** PHP 8 sin frameworks ni CMS, HTML semántico, CSS propio y JS vanilla, sin build ni npm. Las librerías van locales en `assets/vendor/`. **Preguntar antes** de cambiar el stack o la estructura.
- **No inventar datos de negocio** (teléfonos, cifras, testimonios, horarios). Si falta algo, dejar `<!-- TODO: contenido real — … -->`.
- Priorizar el rendimiento: WebP, `loading="lazy"`, CSS y JS mínimos, `font-display: swap`.
- Commits atómicos con mensajes en español y `Co-Authored-By`. Los **cambios pequeños se confirman y se suben (commit + push a `main`) sin preguntar**; solo los cambios muy grandes o complicados se consultan antes de subirlos.
- Todo lo que viene de un JSON se imprime con `e()`.

## Comandos

PHP 8.3 instalado con winget: `C:/Users/ADMIN/AppData/Local/Microsoft/WinGet/Packages/PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe/php.exe`.
Tiene `php.ini` con mbstring, gd, fileinfo, openssl y curl, y `curl.cainfo`/`openssl.cafile` apuntando al `ca-bundle.crt` de Git (sin eso PHP no puede verificar Turnstile en local; respaldo en `php.ini.antes-cacert`).

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
  includes/   config.php (constantes del negocio, MENU, SERVICIOS, opciones de formularios, rutas), funciones.php, formularios.php, head/header/footer, whatsapp.php
  includes/vendor/phpmailer/   PHPMailer 6.9.3 (solo Exception, PHPMailer y SMTP)
  includes/admin/   núcleo del panel (ver abajo)
  components/ card-destacado, card-resultado, slide-hero, testimonio, video, card-blog, calculadora-gastos, cta-asesoria, envio-formulario
  admin/      páginas del panel
  api/        enviar.php (formularios de Contacto y Ofrezca su propiedad)
  assets/     css/estilos.css, css/admin*.css, js/main.js, js/admin.js, img/ (iconos.svg sprite), fonts/ (Montserrat, Nunito Sans woff2)
  uploads/    imágenes y videos del contenido editable (NO versionado; .htaccess impide ejecutar código)
data/        JSON vivos, fuera de la web (NO versionado): hero, destacados, testimonios, resultados, videos .json + blog/*.json + backups/ + seguridad/
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
  - Videos de YouTube: la tarjeta solo muestra la miniatura; al hacer clic se abre un `<dialog>` grande (a todo lo ancho en celular) y el iframe se borra al cerrar.
  - Galerías y carruseles con scroll-snap; cada uno guarda su propio índice.
  - El video del hero solo se carga a ≥768 px y sin reducir movimiento. Cada diapositiva con video avanza cuando el video termina (el fundido empieza 0,9 s antes del final y el video que sale sigue corriendo mientras se desvanece); sin video, cada 7 s. Siempre hay un temporizador de respaldo y ya no se pausa con el mouse, para que nunca se quede atorado.
  - El mapa se carga al hacer clic.
  - Los formularios se envían por `fetch` a `api/enviar.php`; el script de Turnstile se carga solo cuando el formulario está por verse.
  - Animación de entrada en celular.
  - Al enviarse bien un formulario hace `dataLayer.push({event: "formulario_enviado", formulario: "contacto"|"propiedad"})` para GTM.
- **Analítica (9 nov 2026):** solo `GTM-TQ649VLQ` (`GTM_ID` en `config.php`); dentro de GTM van GA4 `G-PZX0ZV3PB5` (propiedad 539887648), `clic_whatsapp`, `registro_propietario` (activadores: envío de formulario de Wix, que se quita al cambiar el dominio, y `formulario_enviado` con formulario=propiedad), `contacto_enviado` (etiqueta «GA4 - Evento - Registro Formulario Contacto», con formulario=contacto) y, por agregar, el Pixel de Meta `637426349761088`. `gtm_activo()` lo carga solo en `duanbienesraices.com` y sin `MODO_DEV`, así que se enciende solo al cambiar el dominio. Tokko usa otro GA4 (`G-17PZHDHJZ9`); el GA4 automático de Wix (`G-C264K4BY2N`) se deja al salir de Wix.
- **Encabezado:** una sola barra fija (`sticky`) con fondo translúcido; a ≥1024 px es una rejilla `1fr auto 1fr` para que el menú quede centrado en la página, con un subrayado rojo en la sección activa; en celular el menú y las redes se abren como panel debajo de la barra. Las clases `.pestana` solo quedan para la sub-navegación de servicios.
- **Estilos generales:** los párrafos dentro de `<main>` van justificados (`:where(main) p`); los bloques centrados se excluyen en la regla de al lado. Etiquetas de destacados rectas y pegadas al borde derecho; Renta en `--azul #1565c0`. El botón flotante de WhatsApp es solo el ícono.
- **Resultados comprobados** (4 nov 2026): cinta infinita de propiedades vendidas y rentadas en Tengo una propiedad, justo después de la portada; editable en el panel (sección `resultados`). Se mueve sola hacia la izquierda (~45 px/s, `--segundos` por tarjeta) y el usuario no la controla: la lista se pinta 2 veces y la animación CSS recorre -50 %. 1 fila en celular y 2 a ≥1024 px, de borde a borde. Con «reducir movimiento» queda quieta y se desliza a mano. Las 20 fotos iniciales vienen de la galería de Wix y ya traen la cinta impresa; la cinta CSS (`clip-path` del 15 al 30,5 % del lado) la tapa exacta, así que no hay que mover esas medidas.
- **Pilares de blindaje:** viven en Tengo una propiedad, debajo de «¿Cómo lo hacemos?», con un triángulo SVG de 3 piezas y «Resultados» al centro (ya no están en Nosotros).
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

- **Fuera de alcance:** la sección Propiedades vive en Tokko. Su URL está en la constante `URL_TOKKO`: `https://propiedades.duanbienesraices.com`, alias en Tokko de `duanbienesraices-buscador.com` (los dos dominios muestran el mismo sitio, sin redirección entre ellos).
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
- **Teléfono fijo retirado:** el (477) 688 6298 ya no se publica (pie, Contacto, aviso de privacidad ni datos para Google). El único número es el WhatsApp 477 551 4226, que también se usa como `telephone` en los datos para Google.
- Fuera del repo se entregaron un PDF de la maqueta de Inicio y una maqueta editable en Figma (https://www.figma.com/design/nIUU1M1w4JMsLv48ow0lg6).

## Pendientes

### Fase 3 — formularios y lanzamiento (en curso)

**Hecho (2 nov 2026):** `api/enviar.php` + `includes/formularios.php`.
- Envía mkt@duanbienesraices.com (cuenta SMTP) → recibe info@duanbienesraices.com; el correo del visitante va como Reply-To.
- Anti-spam: honeypot `sitio_web`, Cloudflare Turnstile (`appearance: interaction-only`) y máximo 5 envíos por IP cada 30 min (`data/seguridad/envios-formularios.json`, guarda solo el hash de la IP).
- Responde JSON con `X-Requested-With: fetch`; sin JS redirige con 303 a la página con `?envio=ok|error#formulario`.
- Sin `SMTP_*` en `MODO_DEV` el correo se guarda en `data/correos-dev/`; en producción falla y lo registra en el log. Sin claves de Turnstile, en producción se rechazan los envíos.
- `config.local.php` local usa las claves públicas de prueba de Turnstile (siempre aprueban).
- Probado: validación, honeypot, Origin ajeno, límite por IP, Turnstile real con claves de prueba y el flujo en el navegador.

**Falta:**
- En el servidor: `SMTP_*` y las claves reales de Turnstile en `config.local.php`. El correo `@duanbienesraices.com` está en **Google Workspace** (se paga directo a Google): `smtp.gmail.com`, puerto 465, usuario mkt@ y una contraseña de aplicación (requiere verificación en 2 pasos). Hacer un envío real de prueba.
- **Dominio y cuentas (3 nov 2026):** `duanbienesraices.com` se compró en Wix (registrador Network Solutions, vence 17 oct 2027). Workspace se paga directo a Google y lo administra el jefe; tiene 5 cuentas (a.jaquez, eli.mendoza, info, midoris.val y mkt). `duanbienesraices-buscador.com` (Tucows, vence 14 oct 2027) también es del jefe. El dominio se queda en Wix y se renueva; sacarlo de Wix se verá después del lanzamiento. Solo se cancela el plan Premium, que termina **antes del 18 nov 2026**: es la fecha límite para tener el sitio en SiteGround y el DNS cambiado. Respaldo de la zona en `docs/dns-respaldo-wix.md`.
- **SPF y DKIM activos (4 nov 2026):** publicados en el DNS de Wix; un correo de prueba dio SPF, DKIM y DMARC en PASS. Si algún día se cambia de DNS, copiarlos de `docs/dns-respaldo-wix.md`.
- Valorar si el aviso de privacidad debe mencionar Cloudflare Turnstile (decisión del cliente; no redactar texto legal sin confirmar).
- **Documentación:**
  - `docs/deploy.md`: hecho (3 nov 2026); adaptado al dominio temporal `emmanuelalejandrom4.sg-host.com` con el que se creó el sitio en SiteGround (5 nov 2026; en `….sg-host.com` el `.htaccess` manda `X-Robots-Tag: noindex`). Despliegue automático con GitHub Actions (`.github/workflows/deploy.yml`, 5 nov 2026): funcionando desde el 5 nov 2026 (secretos `SG_HOST`, `SG_USUARIO`, `SG_LLAVE` y variable `SG_RUTA`; llave `github-deploy` sin contraseña importada en SiteGround). Acceso SSH desde esta PC: `ssh siteground-duan` (llave `~/.ssh/siteground_duan`). El sitio ya corre en el temporal con `config.local.php` completo; panel y los dos formularios probados por el cliente (6 nov 2026).
  - `docs/guia-panel.md`: guía para la persona que edita, sin experiencia técnica.
- **Checklist de lanzamiento:**
  - DNS y registros MX, para no tumbar el correo `@duanbienesraices.com`.
  - HTTPS.
  - Search Console.
  - Verificar las 301.
  - Quitar `MODO_DEV`.

### Contenido real que falta (marcado con TODO; no inventar)

- **`empresa.php`:** una cifra adicional (la foto y la carta del fundador ya están, 4 nov 2026).
- **`contacto.php`:** horario de atención.
- **`testimonios.json`:** 5 de 6 no tienen `url_resena` individual; por ahora enlazan al perfil general de Google.

### Seguridad del panel (propuesta del 9 nov 2026; en revisión con desarrollo, NO implementar sin confirmar)

- Varios usuarios en `data/seguridad/usuarios.json` (nombre, usuario, correo propio, hash, rol administrador/editor, activo); el usuario actual de `config.local.php` pasaría a ser el primer administrador.
- Pantallas «Usuarios» (solo administrador: alta, desactivar, enlace para poner contraseña) y «Mi cuenta» (cambiar contraseña con la actual); «¿Olvidaste tu contraseña?» con enlace por correo de 30 min.
- Contraseña segura obligatoria: mínimo 12, sin usuario/nombre/«duan», lista de comunes, medidor.
- Código de 6 dígitos al correo de cada usuario (10 min), «confiar en este dispositivo» 30 días y aviso por correo al entrar desde un dispositivo nuevo. Alternativa más simple: Turnstile en el login + aviso por correo.
- Mientras tanto la contraseña se cambia generando un hash en el servidor y editando `ADMIN_HASH`. Acceso: guardar `/admin` en favoritos (no enlazarlo en el sitio).

### Opcional

- Llenar un destacado desde la API de Tokko pegando el ID de la propiedad, con caché de algunas horas. Necesita `TOKKO_API_KEY` y la especificación pide validarlo antes.
