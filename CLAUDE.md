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
  propiedades/index.php (listado) y propiedades/ficha.php (/propiedades/{slug}-{id}), datos de Tokko
  sitemap.php (/sitemap.xml), robots.txt, .htaccess
  includes/   config.php (constantes del negocio, MENU, SERVICIOS, opciones de formularios, rutas), funciones.php, formularios.php, tokko.php, head/header/footer, whatsapp.php
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
  - El video del hero se carga a ≥768 px; en celular solo los ligeros (menos de 1 MB, `VIDEO_HERO_LIGERO`, marcados con `data-ligero`, hoy solo «Du'an Intro»). Nunca con «reducir movimiento» ni ahorro de datos. Cada diapositiva con video avanza cuando el video termina (el fundido empieza 0,9 s antes del final y el video que sale sigue corriendo mientras se desvanece); sin video, cada 7 s. Siempre hay un temporizador de respaldo y ya no se pausa con el mouse, para que nunca se quede atorado.
  - El mapa se carga al hacer clic.
  - Los formularios se envían por `fetch` a `api/enviar.php`; el script de Turnstile se carga solo cuando el formulario está por verse.
  - Animación de entrada en celular.
  - Al enviarse bien un formulario hace `dataLayer.push({event: "formulario_enviado", formulario: "contacto"|"propiedad"})` para GTM.
- **Analítica (9 nov 2026):** solo `GTM-TQ649VLQ` (`GTM_ID` en `config.php`); dentro de GTM van GA4 `G-PZX0ZV3PB5` (propiedad 539887648), `clic_whatsapp`, `registro_propietario` (activadores: envío de formulario de Wix, que se quita al cambiar el dominio, y `formulario_enviado` con formulario=propiedad), `contacto_enviado` (etiqueta «GA4 - Evento - Registro Formulario Contacto», con formulario=contacto) y, por agregar, el Pixel de Meta `637426349761088`. `gtm_activo()` lo carga solo en `duanbienesraices.com` y sin `MODO_DEV`, así que se enciende solo al cambiar el dominio. Tokko usa otro GA4 (`G-17PZHDHJZ9`); el GA4 automático de Wix (`G-C264K4BY2N`) se deja al salir de Wix.
- **Encabezado:** una sola barra fija (`sticky`) con fondo translúcido (es un `::before`: arriba ocupa todo el ancho y con `.es-desplazado` se encoge a tarjeta flotante redondeada; las redes van en una cápsula carbón); a ≥1024 px es una rejilla `1fr auto 1fr` para que el menú quede centrado en la página, con un bloque rojo en el menú (18 nov 2026: al pasar el mouse las líneas de arriba y abajo se cierran y el fondo crece desde el centro; la sección activa se queda con el bloque armado, que se anima al cargar, y al hacer clic el botón se hunde un poco); en celular el menú y las redes se abren como panel debajo de la barra. Las clases `.pestana` solo quedan para la sub-navegación de servicios.
- **Estilos generales:** los párrafos dentro de `<main>` van justificados (`:where(main) p`); los bloques centrados se excluyen en la regla de al lado. Etiquetas de destacados rectas y pegadas al borde derecho; Renta en `--azul #1565c0`. El botón flotante de WhatsApp es solo el ícono.
- **Contador +500** (13 nov 2026; desde el 17 nov 2026 en la portada de Tengo una propiedad): `components/portada.php` acepta `$cifra` y la pinta como `.portada__cifra` (a ≥860 px en una columna a la derecha separada por una línea; en celular debajo del botón). Mismo `data-contador` que Nosotros; cifra dada por el cliente.
- **Resultados comprobados** (4 nov 2026): cinta infinita de propiedades vendidas y rentadas en Tengo una propiedad, justo después de la portada; editable en el panel (sección `resultados`). Se mueve sola hacia la izquierda (~45 px/s, `--segundos` por tarjeta): la lista se pinta 2 veces y se recorre una copia. Sin JS la mueve una animación CSS (-50 %); con JS (17 nov 2026, a pedido del jefe) la mueve `main.js` (`[data-cinta]`, clase `.con-js`) para tener flechas ‹ › que avanzan o retroceden una tarjeta (junto al título), arrastre con el dedo y pausa con el mouse encima; tras usar las flechas o arrastrar sigue a los 4 s. 1 fila en celular y 2 a ≥1024 px, de borde a borde. Con «reducir movimiento» queda quieta, se desliza a mano y las flechas desplazan la ventana. Las 20 fotos iniciales vienen de la galería de Wix y ya traen la cinta impresa; la cinta CSS (`clip-path` del 15 al 30,5 % del lado) la tapa exacta, así que no hay que mover esas medidas.
- **Pilares fundamentales** (antes «Pilares de blindaje»): viven en Tengo una propiedad, debajo de «¿Cómo lo hacemos?»; a ≥1100 px las 3 tarjetas iguales van en columna a la izquierda y el triángulo SVG de 3 piezas con «Resultados» al centro a la derecha; al pasar por una tarjeta se resalta su pieza.
- **Efectos al desplazar (13 nov 2026):** parallax en los fondos de `.portada`, `.servicios` y `.banda-inventario` (la foto es un `::before` 16 % más alto que `main.js` mueve con `--paralaje`; el velo es `--velo` en `::after`) y aparición gradual (`.aparece` → `.es-visible`, la lista de selectores está en `main.js`; lo que ya se ve al cargar no se anima). Nada de esto corre con «reducir movimiento».
- **Tarjetas de tecnología** (`.equipo`, Tengo una propiedad): al pasar el mouse las tres cambian a rojo; si a un equipo se le pone `'foto'` en `$equipos`, aparece esa foto con velo rojo (hoy ninguna la tiene).
- **«Así presentamos su propiedad»** (13 nov 2026): ficha de ejemplo fija (casa de El Mayorazgo como se publicó en venta; datos en `$ejemplo*` de `ofrezca-su-propiedad.php`). Reutiliza la galería y el visor de la ficha (`data-ficha-galeria`, `data-miniaturas`) con 12 fotos locales en `assets/img/ejemplo/` (copiadas de Tokko 6888677), datos con íconos, descripción con «Ver más», video, mapa de la zona (el mismo componente, con [21.06, -101.6]) y redes. El tour virtual de Matterport ya no se muestra ahí.
- **Banners promocionales** (19 nov 2026; sección `vitrina` del panel, «Banners promocionales»): en el inicio, entre el subtítulo y las tarjetas de Propiedades destacadas (`components/vitrina.php` → `components/cartel.php`). Con varios visibles van en el carrusel genérico con `data-auto="8000"` (avanza solo; se detiene con el mouse o el foco encima, fuera de pantalla y con «reducir movimiento»). Dos tipos (campo `tipo`; los campos con `'si'` en `SECCIONES_ADMIN` solo aplican a uno y `admin.js` oculta los demás; `ESQUEMAS['vitrina']['cond']` exige los de cada tipo): **`diseno`** = el diseño de Torre Arbide (etiqueta, etiqueta de operación Venta/Renta opcional —la misma `.etiqueta` de los destacados, en la esquina de la foto; campo `operacion`—, título, descripción y descripción corta de celular, precio con texto previo, foto 1600×1200 con encuadre, firma «Du'an | nombre», botón rojo y botón contorno; una liga de cal.com en un botón abre el modal de la agenda); **`codigo`** = HTML/CSS/JS pegado por el editor, en un `<iframe>` a `api/cartel.php?id=` (vista previa: `admin/vista-cartel.php`, con el código en la sesión) servido con `Content-Security-Policy: sandbox …` (`cabeceras_cartel_codigo()`): origen aislado, sin cookies ni acceso a la página; las ligas abren arriba (`<base target="_top">`). Debajo de «Código del banner» hay instrucciones plegables para pedirle el código a una IA, con botón «Copiar» (`INSTRUCCIONES_CARTEL_IA` en `almacen.php`, campo `'instrucciones'`; si se cambian las reglas del banner, actualizarlas ahí). `cartel_documento()` agrega un script que manda el alto con `postMessage` y `main.js` ajusta el marco (60–1600 px). No se usa el atributo `sandbox` en el iframe porque el navegador integrado de la app bloquea esos marcos (`ERR_BLOCKED_BY_CLIENT`). **Nombres sin «banner»**: los bloqueadores de anuncios bloquean direcciones con `banner` y `/banners/`, por eso la sección, la carpeta `uploads/vitrina/` y las clases (`.vitrina`, `.cartel*`) se llaman así. Torre Arbide (17 nov 2026) es el primer elemento de la semilla (`data-seed/vitrina.json`, foto en `uploads-seed/vitrina/`, `SEMILLA_VERSION` 6): textos, «Últimas 7 unidades» y precio dados por el cliente; «Agendar visita» abre el evento «Visitas Torre Arbide» de Cal.com y «Ver departamentos» https://torrearbide.com/. Foto: portada de Tokko 10483841 recortada sin la marca de agua.
- **Resoluciones prioritarias** (GA4, 23 oct–19 nov 2026, 730 usuarios): 384×832, 360×800, 1920×1080, 385×854, 390×844, 440×956, 360×806, 412×915, 320×712 y 375×812. Casi todo es celular de 360–440 px; 1920×1080 es la única de escritorio y la de más interacción. Probar cada cambio al menos a 360, 384, 412 y 1920 px, y a 320 px que nada se rompa. Revisión del 20 nov 2026: `main { overflow-x: clip }` (las animaciones `.aparece--izq/--der` de Pilares abrían scroll horizontal en todos los celulares), área táctil de los puntos de carrusel con `::before` (el punto sigue de 12 px), `.chip` de 40 px de alto y barra de filtros compacta al quedar fija en celular (ver «Página /propiedades»).
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
| `includes/admin/cuenta.php` | Cuenta del único usuario: contraseña, correo confirmado y enlaces por correo (ver «Cuenta del panel») |
| `admin/.user.ini` | Límites de subida en SiteGround |

Secciones de Nosotros (11 nov 2026):
- **`fundador`** (Carta del fundador): sección de **un solo elemento** (`'unico' => true` en `SECCIONES_ADMIN`). `seccion.php` manda directo a `editar.php`, que edita el elemento existente, vuelve a sí mismo al guardar y muestra «Restaurar versión anterior»; `accion.php` no permite eliminar ni reordenar. Campos: foto (900×1194), alt, cita (las comillas se ponen solas), mensaje (texto plano, una línea en blanco separa párrafos), nombre y cargo. Se pinta con `components/fundador.php` (también en la vista previa).
- **Redes del fundador** (13 nov 2026): campos opcionales `facebook`, `instagram` y `youtube` en `fundador` (`REDES_FUNDADOR`); se pintan como íconos junto a la firma. `SEMILLA_VERSION` 4 los agrega una sola vez a la carta que ya existe en el servidor (migración en `inicializar_datos()`).
- **Publicación de Instagram del fundador** (17 nov 2026, a pedido del jefe): campo `publicacion` en `fundador` (liga a un post de Instagram; el panel solo acepta /p/, /reel/ o /tv/). Con liga, `components/fundador.php` muestra a la izquierda «Nuestro fundador», nombre, cargo, la frase, «Seguir en Instagram» y las redes, y a la derecha el embed oficial sin texto (`instagram_embed()` → `/p/{código}/embed/`, iframe `data-instagram`). No se carga el script de Instagram: `main.js` ajusta el alto con los mensajes `MEASURE` que manda el iframe (mientras no llegan, lo estima: ancho × 1.25 + 184 px; ese es el máximo para una foto 4:5 y se usa el menor de los dos, porque con la sesión de Instagram abierta el embed reporta un área en blanco de más) y el marco `.fundador__marco-ig` recorta los últimos 45 px, la fila «Agrega un comentario…». Sin liga vuelve la carta con foto y mensaje. La CSP del panel permite `frame-src https://www.instagram.com` para la vista previa. `SEMILLA_VERSION` 5 agrega la liga una sola vez a la carta del servidor y quita «Martínez» del nombre y del alt (el nombre publicado es «Arq. Alejandro Jáquez»).
- **`galeria`** (Galería de Nosotros): lista normal de fotos (1500×1000, recorte) con descripción, para el carrusel del final de Nosotros.
- Las fotos iniciales pasaron de `assets/img/` a `uploads-seed/fundador/` y `uploads-seed/galeria/`.
- **`portadas`** (13 nov 2026): foto, encuadre, antetítulo, título y texto de la portada de Nosotros, Propiedades, Tengo una propiedad, Blog y Contacto. Es una sección de **elementos fijos** (`'fijo' => true`): se listan y se editan, pero no se agregan, eliminan, ocultan ni reordenan (`accion.php` lo rechaza). Los ids y nombres están en `PORTADAS` (`funciones.php`); cada página llama `portada_de($id, $respaldo)` y pinta `components/portada.php` (si el elemento falta o es inválido se usa el respaldo escrito en la página). Las portadas de Servicios siguen fijas en `SERVICIOS`. `SEMILLA_VERSION` subió a 3.
- **Semilla versionada:** `SEMILLA_VERSION` (`funciones.php`) va escrita en `data/.inicializado`. Si cambia, `inicializar_datos()` copia solo las entradas de **primer nivel** que faltan por completo en `data/` y `uploads/` (así llegaron `fundador.json`, `galeria.json` y sus carpetas al servidor) sin resucitar lo que el cliente borró dentro de una sección. Subirla al agregar una sección nueva a la semilla.

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

### Integración con Tokko (11 nov 2026)

Plan del cliente (opción C): **1)** los destacados del inicio se alimentan de la API de Tokko (hecho); **2)** una página propia `/propiedades` (listado, filtros y ficha por propiedad) para dejar de mandar al sitio de Tokko (hecha el 11 nov 2026, ver abajo).
- `includes/tokko.php`: `tokko_sincronizar()` baja todas las propiedades (API v1 `property/`, de 50 en 50; 124 activas) y guarda en `data/tokko/propiedades.json` **solo campos públicos** con `tokko_normalizar()` (nunca `internal_data` —dueños, comisiones, notas—, sucursal, asesor ni dirección real). Ese archivo ya trae lo necesario para la futura `/propiedades` (operaciones, recámaras, baños, m², amenidades, fotos, videos).
- Destacados: el panel solo elige qué propiedades y en qué orden (`admin/tokko.php` con buscador → acción `agregar_tokko`); no se editan. `tokko_actualizar_destacados()` rellena título, tipo, ubicación, precio (`precio_visible` = `web_price`), descripción corta y **solo la foto de portada** (`TOKKO_FOTOS_DESTACADO = 1`; la futura `/propiedades` usará todas) copiada a WebP en `uploads/destacados/tokko-{id}-{hash de la URL}-{card|full}.webp` (si la foto no cambia, no se vuelve a bajar). Si una propiedad deja de estar activa en Tokko, se oculta sola (`tokko_no_disponible`). Los destacados viejos hechos a mano se enlazaron a Tokko por su liga `/p/{id}-prop`; los que no existen en Tokko se quedan como estaban y se pueden seguir editando.
- Actualización: cada 15 min (`TOKKO_VIGENCIA`; era 3 h hasta el 17 nov 2026), después de mandar la página (inicio, listado o ficha) a quien la visita (`tokko_refrescar_al_terminar()` + `fastcgi_finish_request`), con candado y un intento cada 15 min si Tokko falla; también con el botón «Actualizar desde Tokko», que está en el inicio del panel (recuadro «Propiedades de Tokko», vuelve a `/admin/`) y en Destacados. Solo escribe `destacados.json` (y genera respaldo) si algo cambió.
- **Título:** se usa `fake_address` («dirección pública»), que es el título que escribe el equipo y el que muestran el panel y el sitio de Tokko; `publication_title` lo genera Tokko («Casa en León») y se guarda como `titulo_tokko`. Si `fake_address` parece una dirección real (C.P. de 5 dígitos o «km»), se usa `publication_title`. El buscador del panel busca en los dos títulos, código, tipo, ubicación y operación.
- Al agregar desde el panel hay una barra de progreso por etapas estimadas (la petición no informa su avance real).
- Sin `TOKKO_API_KEY` en `config.local.php` todo funciona como antes (destacados manuales). En el servidor hay que agregar la clave.
- «Ver ficha» de los destacados lleva a la ficha propia (`url_ficha`); los destacados manuales que no están en Tokko siguen enlazando a `url_tokko` en otra pestaña.
- **Fotos:** se usa la versión `image` de Tokko (1200 px); la `original` puede pesar 7 MB y pasar de 50 MP (`IMAGEN_MAX_PIXELES`), y así fallaban algunas portadas.

### Página /propiedades (11 nov 2026)

- **Listado** (`propiedades/index.php`): filtros por GET (operación con chips, texto, tipo, precio mín./máx. y los del panel «Más filtros»; el selector de ciudad se quitó el 20 nov 2026 a pedido del cliente y `ciudad` ya no se lee de la URL) y orden (recientes, menor y mayor precio); 24 por página (`PROPIEDADES_POR_PAGINA`, 6 filas de 4 desde 1440 px; era 12). Las funciones están al final de `tokko.php` (`filtros_propiedades`, `filtrar_propiedades`, `precio_propiedad`, `datos_clave`…). Ningún filtro se aplica solo (20 nov 2026, a pedido del cliente), salvo los chips Todas/Venta/Renta, que buscan al tocarlos (24 nov 2026): lo demás corre al tocar «Buscar» (la lupa en celular) o Enter, y mientras hay cambios sin aplicar el botón late (`.hay-cambios`); los campos vacíos no van en la URL. El orden («Ordenar por») sí se aplica al cambiar. Con filtros u otro orden la página lleva `noindex`. En `index.php` el cierre de la paginación se llama `$urlPagina` porque `header.php` pisa `$url`.
- **Tarjeta** `components/card-propiedad.php`: portada WebP local (`uploads/propiedades/tokko-{id}-{hash}-card.webp`, la copia `tokko_copiar_portadas()` en cada sincronización y el mapa id → ruta queda en `data/tokko/portadas.json`).
- **Ficha** (`propiedades/ficha.php`): la URL termina en el id de Tokko; si el título cambió, la URL vieja redirige con 301 y si la propiedad ya no está, 404. Galería con todas las fotos tomadas directo de Tokko (carga diferida) y miniaturas (eventos `galeria:ir` / `galeria:cambio` en `main.js`); la galería va siempre en 4:3 con la foto llenando el marco (los planos enteros sobre blanco; a ≥900 px el ancho se limita a `76vh × 4/3`) y al hacer clic en una foto se abre el visor a pantalla completa (`<dialog class="visor-fotos">`, flechas, teclado y deslizar); datos clave, descripción, características, amenidades, videos/recorridos como enlaces, **mapa de la zona** (`components/mapa-zona.php`, 17 nov 2026: Leaflet 1.9.4 local en `assets/vendor/leaflet/` + mosaicos de OpenStreetMap, igual que Tokko; `main.js` lo carga al acercarse y dibuja un `L.circle` de `ZONA_RADIO_M` = 800 m que se mueve y escala con el mapa (el círculo CSS fijo de antes se quedaba quieto al arrastrar). Centrado en `zona_geo` = coordenadas de Tokko redondeadas a 2 decimales (~1 km; 800 m cubre el error, así que la propiedad siempre queda dentro); el punto exacto nunca se guarda. Botón propio «Abrir en Maps». Sin JS o si Leaflet falla, iframe de Google sin círculo; si no hay coordenadas, Google busca `ubicacion_completa`), tarjeta de contacto a la derecha del título y los datos, con la misma altura (19 nov 2026, a pedido del jefe: sin formulario; texto, «Preguntar por WhatsApp» con código y liga, «Agendar visita» en rojo y compartir al pie). «Agendar visita» usa `URL_AGENDAR_CITA` (`config.php`): evento «Visita a propiedad» de Cal.com (cuenta del cliente) con el código, título y liga de la propiedad en `notes`, que Cal.com pone en «Notas adicionales», y en `propiedad` (código – título) para un campo oculto con ese identificador en el formulario de reserva, que se usa en el nombre del evento del calendario. `main.js` lo abre en un modal propio (`dialog.agenda-modal` con iframe, sin el script de Cal.com; pantalla completa en celular, el iframe se borra al cerrar); sin JS abre otra pestaña. Si la constante se deja vacía, abre WhatsApp pidiendo la cita. Arriba de la galería (`.ficha__barra`, 20 nov 2026): «Regresar a la búsqueda» a la izquierda y las migas a la derecha; si la página anterior fue `/propiedades` del mismo sitio, `main.js` usa `history.back()` (conserva filtros, página y posición), si no la liga abre `/propiedades`. En celular solo dice «Regresar», pequeño y gris (ahí se regresa deslizando). Debajo, a todo lo ancho y alineados a la izquierda (`.ficha__cuerpo`): descripción (20 nov 2026: plegada a 6 líneas con degradado y botón «Ver más / Ver menos»; genérico con `[data-plegable="N"]` + `.plegable__boton` justo después, en `main.js`; solo se pliega si sobran 2 líneas o más y sin JS se ve completa), características, amenidades, videos y zona; y 3 similares. **Video de YouTube** (23 nov 2026): viene de Tokko (campo «Videos» de la propiedad; no hace falta panel) y `youtube_de()` saca el id aunque la liga traiga `?si=` mal pegado. Si hay video: botón rojo «Ver video» abajo a la izquierda de la galería, miniatura grande en «Recorrido virtual y videos» (los dos abren el modal `video-modal` con `[data-abrir-video]`) y un aviso flotante pequeño (`.video-aviso`, abajo a la izquierda sin tapar WhatsApp) que aparece a los 2.5 s, una vez por visita a cada ficha (`sessionStorage['duan-video-{id}']`), y se quita al cerrarlo, al abrir el video o al llegar a la sección de videos. Reemplaza el video que el sitio de Tokko abría solo al entrar (en celular tapaba toda la ficha). JSON-LD `RealEstateListing` + `BreadcrumbList`.
- Rutas en `.htaccess` y `dev-router.php`; el menú, el inicio, 404, Ofrezca su propiedad y el CTA del blog ya apuntan a `/propiedades`; el sitemap incluye el listado y las 124 fichas. `URL_TOKKO_PROPIEDADES` ya no se usa en el sitio.
- Slugs: `tokko_slug()` corta a 70 caracteres en el último guion.
- **Rediseño de pruebas (20 nov 2026, sin subir):** el listado ya no lleva portada (h1 oculto) y la barra de filtros va pegada al menú (`.seccion.propiedades { padding-top: 1rem }`, la misma altura a la que queda fija, así que no brinca). `--ancho` sube a 1400 px desde 1440 px de pantalla y a 1560 desde 1800 (todas las páginas); el listado y los destacados del inicio van a 4 columnas desde 1440 px. Tarjetas compactas: `card-destacado` usa el mismo cuerpo que `card-propiedad` (clases `.propiedad-card__*`: tipo y código, precio, título en 2 líneas, ubicación en 1, `datos_clave_cortos()`), sin descripción. En celular (<640 px, una columna; 20 nov 2026, a pedido del cliente) una tarjeta completa debe caber en pantalla debajo de la barra fija: va algo más angosta que el contenedor (`padding-inline` en `.propiedades__grid`), foto 3:2 y datos clave en 2 columnas con íconos rojos; mide ~450 px de alto a 384 px (411 a 320, 468 a 412). Se probó con foto 4:3 y letras más grandes y quedaba demasiado alta. La barra de filtros es fija (`position: sticky`) y sin etiquetas visibles: en celular (24 nov 2026, a pedido del jefe) fila 1 = chips Todas/Venta/Renta chicos + «Filtros» + lupa y fila 2 = buscar + «Tipo» (fuera del desplegable; `contain: inline-size` para que su opción más larga no ensanche las columnas); a menos de 360 px «Filtros» queda solo con ícono (al quedar fija se esconden los chips y el tipo y queda una fila: buscar + «Filtros» + lupa; `main.js` mide la barra con y sin `.es-compacta` y guarda la diferencia en `--alto-chips` en el contenedor, no en la barra, porque el relleno va en el hermano con la clase `.es-compacta`, que pone `main.js` con un marcador y un IntersectionObserver —con 2 px de margen, porque el marcador empieza justo a la altura donde la barra se fija—; un relleno en `.propiedades__barra` compensa su alto para que nada brinque; «Filtros» abre un desplegable encima de las tarjetas y se cierra al tocar fuera); de 760 a 1279 px dos filas; desde 1280 px una sola. «Limpiar filtros» pasó a la barra de resultados.
- **Panel «Más filtros» (20 nov 2026, sin subir; referencia: propiedades.com):** superficie construida/terreno (m² desde/hasta), recámaras, baños y estacionamientos «1+…4+» (recámaras estaba en la barra; se movió aquí), características agrupadas (`CARACTERISTICAS_FILTRO` en `tokko.php`: Espacios, Generales, Amenidades, Seguridad y servicios; cada clave junta varios nombres de amenidad de Tokko; se exigen todas las marcadas y se esconden las que ninguna propiedad tiene; cada una muestra cuántas hay), multimedia (video / recorrido 360°, de `videos`) y fecha de publicación (`creada`). En la URL: `recamaras`, `sup`, `m2min`, `m2max`, `banos`, `estac`, `carac[]`, `media[]`, `publicada`; `consulta_filtros()` arma la URL de la paginación y los campos ocultos de «Ordenar por». No se incluyen antigüedad (82 propiedades con 0 en Tokko) ni tipo de anunciante (todo es Du'an). Desde 760 px se abre con el botón «Más filtros» (con el número de filtros aplicados) como panel flotante a la derecha, con su propio scroll; se cierra con Esc o al tocar fuera. En celular va al final del desplegable de «Filtros» y, mientras está abierto, se esconden WhatsApp y «subir». Al pie, «Limpiar» (solo el panel, no busca) y «Ver resultados». Sin JS el panel se ve abierto dentro del formulario y la barra no es fija.

## Decisiones tomadas en la Fase 1 (no revertir sin preguntar)

- **Propiedades:** hasta el 11 nov 2026 la sección vivía en Tokko; ahora es la página propia `/propiedades` con datos de la API (ver arriba). El sitio de Tokko sigue existiendo. Su URL está en la constante `URL_TOKKO`: `https://propiedades.duanbienesraices.com`, alias en Tokko de `duanbienesraices-buscador.com` (los dos dominios muestran el mismo sitio, sin redirección entre ellos).
- **Páginas y archivos agregados:** carpeta `uploads-seed/`, `plantilla-servicio.php`, página `/aviso-de-privacidad`, componentes de calculadora y CTA, y `dev-router.php`.
- **Menú:** los Servicios no están en el menú principal, igual que en Wix; se enlazan desde las tarjetas del inicio y el footer.
- **Destacados:** galerías de hasta 12 fotos; el precio se guarda como número y se muestra con `formato_precio()`. Desde el 11 nov 2026 vienen de Tokko (ver «Integración con Tokko»).
- **Videos:** el video del hero de Nosotros (24 MB) se quitó y se dejó un póster. El póster de la intro es el cuadro del logo a los 6 s del video.
- **WhatsApp:** se usa el número 524775514226; algunas páginas de Wix usaban otro número personal que no debe publicarse.
- **Blog:** los artículos tienen slugs nuevos y los de Wix redirigen con 301 (`/post/{slug}` → `/blog/{slug}` → `slug_anterior`).
- **Animación de entrada en celular** (réplica de la de Wix, commit f1e98d3):
  - Secuencia: una línea roja se abre, aparece el logo, el rojo se cierra en una línea y se desvanece.
  - Dura unos 3 s.
  - Solo a menos de 768 px, con JS y sin reducir movimiento.
  - Una vez por visita: la marca `sessionStorage['duan-intro']` y la clase `intro-vista` en `<html>`.
  - Código: marcado en `head.php`, estilos al final de `estilos.css` y lógica al final de `main.js`.

## Estado actual (2 nov 2026)

- **Lanzado el 13 nov 2026:** `duanbienesraices.com` ya apunta a SiteGround (IP `35.215.119.253`). El dominio real quedó como **dominio aparcado** del sitio (también `es.` y `en.`); el dominio principal del hosting sigue siendo `emmanuelalejandrom4.sg-host.com`, así que la carpeta, `SG_RUTA`, `SG_HOST` y el deploy no cambiaron. DNS en Wix: `@` A → SiteGround y `www`/`es`/`en` CNAME → `duanbienesraices.com`; MX, SPF, DKIM y `propiedades` intactos. Let's Encrypt emitido para raíz+www, `es.` y `en.` (se renueva solo). `URL_SITIO` del servidor = `https://www.duanbienesraices.com`; GTM ya carga. El cliente probó formularios, panel, recuperación de contraseña y correo, y dejó GTM/GA4/Search Console configurados. Bitácora y pendientes en `docs/lanzamiento.md`.
- El sitio temporal sigue respondiendo (con `noindex`) y sirve para probar.

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

### Cuenta del panel (11 nov 2026)

El cliente decidió la versión sencilla: **un solo usuario**, sin roles ni códigos al entrar (la propuesta del 9 nov con varios usuarios y código de 6 dígitos quedó descartada).
- `includes/admin/cuenta.php` + `data/seguridad/cuenta.json` (usuario, hash, `clave_ts`, correo confirmado, correo pendiente, token de restablecer y marcas de envíos). Mientras el archivo no exista se usan `ADMIN_USUARIO` y `ADMIN_HASH` de `config.local.php`; la cuenta pasa sola al archivo en el primer cambio. **Salida de emergencia:** borrar `cuenta.json` en el servidor y vuelve a valer `config.local.php`.
- **Mi cuenta** (`admin/cuenta.php`, enlace en la barra): cambiar correo y contraseña, siempre con la contraseña actual. El correo no queda activo hasta abrir el enlace de confirmación (`admin/confirmar-correo.php`, 24 h, no pide sesión).
- **Contraseña:** mínimo 10 caracteres, mayúscula, minúscula y número, sin el usuario ni «duan», máximo 72 (`validar_clave_nueva()`). Al cambiarla se cierran las demás sesiones (`clave_ts` en la sesión) y se avisa al correo confirmado.
- **Olvidé mi contraseña** (`admin/recuperar.php` → `admin/restablecer.php`): enlace de un solo uso, 30 min, solo al correo confirmado; la respuesta es igual exista o no el correo. Cada solicitud cuenta para el bloqueo de 5 intentos por IP.
- De los enlaces solo se guarda el sha256 del token. Máximo 5 correos de la cuenta por hora y 1 por minuto. Los enlaces usan `URL_SITIO` (no la cabecera Host).
- Los correos salen con `mandar_correo()` (`formularios.php`), la misma cuenta SMTP de los formularios; en local sin SMTP se guardan en `data/correos-dev/*-panel-*.txt`.
- Acceso: guardar `/admin` en favoritos (no enlazarlo en el sitio).

### Opcional

