# Lanzamiento: cambiar `duanbienesraices.com` de Wix a SiteGround

Plan por fases para el día del cambio (13 nov 2026). El detalle técnico de cada paso está en `deploy.md` (secciones 6 a 8) y la zona DNS completa en `dns-respaldo-wix.md`.

**Fecha límite:** el plan Premium de Wix termina antes del 18 nov 2026. Mientras siga activo se puede regresar a Wix en minutos (ver «Marcha atrás»).

**Qué no cambia:** el dominio sigue registrado en Wix, el DNS se sigue administrando en Wix y el correo sigue en Google Workspace. Solo cambia a qué servidor apuntan `@`, `www`, `es` y `en`.

## Fase 0 — Antes de tocar nada (sitio temporal)

- [ ] Enviar el formulario de **Contacto** en `emmanuelalejandrom4.sg-host.com` y confirmar que llega a `info@`.
- [ ] Enviar el formulario de **Ofrezca su propiedad** y confirmar que llega a `info@`.
- [ ] Entrar al panel con la contraseña nueva y guardar un cambio cualquiera (por ejemplo, ocultar y volver a mostrar un testimonio).
- [ ] Revisar a ojo Inicio, Nosotros, Propiedades (listado y una ficha), Tengo una propiedad, Blog y Contacto, en computadora y en celular.
- [ ] Anotar la **IP del sitio**: SiteGround → Site Tools → Dashboard → *Site IP*. (Hoy el temporal responde en `35.215.119.253`; usar la que muestre Site Tools.)
- [ ] Tener a la mano `dns-respaldo-wix.md`: es la copia de cómo está el DNS hoy.
- [ ] Elegir la hora: entre el cambio de DNS y la emisión del certificado SSL el sitio puede mostrar un aviso de seguridad durante unos minutos. Conviene hacerlo en un rato de poco tráfico.

## Fase 1 — SiteGround: poner el dominio real

1. **Websites → (el sitio) → ⋮ → Change Primary Domain** → `duanbienesraices.com`. Elegir **no** cambiar nameservers y **no** crear correo.
2. Revisar en **Site Tools → Sitio → Administrador de archivos** cómo quedó la carpeta del sitio:
   - Si ahora se llama `duanbienesraices.com`: en GitHub (Settings → Secrets and variables → Actions → Variables) borrar la variable `SG_RUTA`.
   - Si sigue llamándose `emmanuelalejandrom4.sg-host.com`: no tocar `SG_RUTA`.
3. Confirmar que `config.local.php`, `data/` y `public_html/uploads/` siguen dentro de la carpeta del sitio.
4. En **Devs → SSH Keys Manager → SSH Credentials** ver el *hostname*. Si cambió, actualizar el secreto `SG_HOST` en GitHub y el `HostName` de `siteground-duan` en `~/.ssh/config` de esta PC.
5. Editar `config.local.php` del servidor: cambiar `URL_SITIO` a `https://www.duanbienesraices.com` (o borrar la línea; ese es el valor por defecto).

En este punto el sitio temporal puede dejar de responder; es normal. El dominio real todavía muestra Wix.

## Fase 2 — Wix: cambiar el DNS

En Wix → Dominios → `duanbienesraices.com` → Administrar registros DNS:

| Nombre | Tipo | Qué hacer |
|---|---|---|
| `@` | A | Borrar las 3 de Wix (`185.230.63.107`, `.171`, `.186`) y dejar **una** con la IP de SiteGround |
| `www` | CNAME | Cambiar `cdn3.wixdns.net` por `duanbienesraices.com` |
| `es` | CNAME | Igual que `www` |
| `en` | CNAME | Igual que `www` |

**No tocar:** los 5 MX, los TXT (verificación de Google, SPF, `google._domainkey`), `propiedades` y `www.propiedades` (buscador de Tokko).

Tarda entre unos minutos y una hora en verse (TTL de 1 h).

## Fase 3 — SSL

1. Cuando el dominio ya resuelva a SiteGround: **Security → SSL Manager** → Let's Encrypt para `duanbienesraices.com` y `www`. Si lo permite, incluir `es.` y `en.` (o un Let's Encrypt Wildcard).
2. No activar «HTTPS Enforce»: el `.htaccess` ya manda todo a `https://www.`.

## Fase 4 — Verificación (el mismo día)

Sitio:
- [ ] `http://duanbienesraices.com`, `https://duanbienesraices.com` y `http://www.duanbienesraices.com` terminan en `https://www.duanbienesraices.com/` sin avisos de seguridad.
- [ ] `es.duanbienesraices.com` y `en.duanbienesraices.com` redirigen a `www`.
- [ ] Enlaces viejos de Wix: `/post/…` lleva al artículo nuevo y `/faq` al inicio.
- [ ] En el código fuente de Inicio: el canonical es `https://www.duanbienesraices.com/` y **no** aparece `noindex`.
- [ ] `https://www.duanbienesraices.com/sitemap.xml` lista direcciones con `www.duanbienesraices.com`.
- [ ] Los dos formularios envían y llegan a `info@`.
- [ ] Panel en `https://www.duanbienesraices.com/admin`: entrar, guardar un cambio y probar «Olvidé mi contraseña» (el enlace del correo debe traer el dominio real). Guardarlo en favoritos.
- [ ] `/propiedades` y una ficha cargan con fotos y mapa.

Correo (lo más delicado):
- [ ] Enviar un correo desde una cuenta externa (Gmail personal) a `info@duanbienesraices.com` y confirmar que llega.
- [ ] Responder desde `info@` y confirmar que llega a la cuenta externa y no cae en spam.

Despliegue:
- [ ] En GitHub → Actions → Deploy → **Run workflow**: debe terminar en verde.

Tokko:
- [ ] `https://propiedades.duanbienesraices.com` sigue abriendo el buscador de Tokko.

## Fase 5 — Google y medición (el mismo día o al siguiente)

- [ ] **Google Tag Manager** (`GTM-TQ649VLQ`): se enciende solo en el dominio real. Con la vista previa de GTM confirmar que disparan GA4, `clic_whatsapp`, `contacto_enviado` y `registro_propietario`.
- [ ] En GTM, quitar del evento `registro_propietario` el activador del formulario de Wix y publicar el contenedor.
- [ ] **GA4** (`G-PZX0ZV3PB5`) → Tiempo real: debe aparecer la visita propia.
- [ ] **Search Console**: abrir la propiedad de `duanbienesraices.com` (el TXT de verificación ya está en el DNS), enviar `https://www.duanbienesraices.com/sitemap.xml` y pedir inspección de la página de inicio.
- [ ] Pixel de Meta `637426349761088`: agregarlo en GTM (pendiente desde antes).

## Fase 6 — Días siguientes

- [ ] Revisar en Search Console que no suban los errores 404 (indican enlaces viejos sin redirección).
- [ ] Cancelar el plan Premium de Wix **solo** cuando todo lo anterior esté verificado. No borrar el sitio de Wix antes.
- [ ] DNS de Wix que ya no sirven: reemplazar el CNAME `_dmarc` por el TXT `v=DMARC1; p=none; rua=mailto:info@duanbienesraices.com` y borrar `s1._domainkey`, `s2._domainkey`, `sel1._domainkey` y `sg` (eran de los envíos de Wix).
- [ ] En Cloudflare Turnstile, quitar `emmanuelalejandrom4.sg-host.com` de los dominios del widget.
- [ ] Decidir si el aviso de privacidad debe mencionar Cloudflare Turnstile.
- [ ] Contenido pendiente: horario de atención en Contacto y las ligas individuales de las reseñas.

## Marcha atrás

Si algo grave falla y Wix Premium sigue activo: en el DNS de Wix volver a poner las 3 A de Wix en `@` y `cdn3.wixdns.net` en `www`, `es` y `en` (valores en `dns-respaldo-wix.md`). En menos de una hora el dominio vuelve a mostrar el sitio de Wix. El correo no se ve afectado en ningún caso, porque los MX no se tocan.
