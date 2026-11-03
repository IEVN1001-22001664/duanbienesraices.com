# Respaldo del DNS mientras está en Wix

Zona completa del panel de Wix (3 nov 2026): 5 A, 8 CNAME, 1 TXT y 5 MX. Todos con TTL de 1 hora.
Servidores de nombres actuales: `ns14.wixdns.net` y `ns15.wixdns.net`.

## Se copian tal cual (no tocar)

| Nombre | Tipo | Valor | Para qué es |
|---|---|---|---|
| `@` | MX 10 | `aspmx.l.google.com` | Correo de Google Workspace |
| `@` | MX 20 | `alt1.aspmx.l.google.com` | Correo |
| `@` | MX 30 | `alt2.aspmx.l.google.com` | Correo |
| `@` | MX 40 | `alt3.aspmx.l.google.com` | Correo |
| `@` | MX 50 | `alt4.aspmx.l.google.com` | Correo |
| `@` | TXT | `google-site-verification=fRGTAp6F_Lj7iKv6kbMBgC72lnmTPDHpFDn4afY2lfk` | Verificación de Google (Workspace / Search Console) |
| `propiedades` | A | `23.21.123.17` | Buscador de Tokko (misma IP que `www.duanbienesraices-buscador.com`) |
| `www.propiedades` | A | `23.21.123.17` | Buscador de Tokko |

## Se cambian a SiteGround

| Nombre | Tipo | Hoy (Wix) | En SiteGround |
|---|---|---|---|
| `@` | A ×3 | `185.230.63.107`, `.171`, `.186` | Una sola A con la IP del sitio en SiteGround |
| `www` | CNAME | `cdn3.wixdns.net` | `duanbienesraices.com` (o A con la IP de SiteGround) |
| `es` | CNAME | `cdn3.wixdns.net` | Igual que `www`. El `.htaccess` lo redirige con 301 a `https://www.duanbienesraices.com` |
| `en` | CNAME | `cdn3.wixdns.net` | Igual que `www`, con la misma 301 |

`es.` y `en.` eran los subdominios de idioma de Wix y hoy ya redirigen a `www`; se conservan para no perder esos enlaces.
El certificado SSL de SiteGround debe cubrir también `es.` y `en.`, o la redirección mostrará un aviso de seguridad.

## Se reemplazan o eliminan al dejar Wix

| Nombre | Tipo | Valor | Qué hacer |
|---|---|---|---|
| `_dmarc` | CNAME | `_dmarc.wixemails.com` | Reemplazar por TXT `v=DMARC1; p=none; rua=mailto:info@duanbienesraices.com` |
| `s1._domainkey` | CNAME | `s1._domainkey.duanbienesraices.com.s021.ascendbywix.com` | Firma de correos de Wix (Ascend). Eliminar si ya no se mandan campañas desde Wix |
| `s2._domainkey` | CNAME | `s2._domainkey.duanbienesraices.com.s021.ascendbywix.com` | Igual que `s1` |
| `sel1._domainkey` | CNAME | `sel1._domainkey.duanbienesraices.com.s021.ascendbywix.com` | Igual que `s1` |
| `sg` | CNAME | `sg.duanbienesraices.com.s021.ascendbywix.com` | Envíos de Wix (Ascend). Igual que `s1` |

## Lo que no existe hoy y conviene agregar

- **SPF** (no hay ninguno): TXT en `@` → `v=spf1 include:_spf.google.com ~all`.
- **DKIM de Google** (no hay `google._domainkey`): se genera en admin.google.com → Apps → Google Workspace → Gmail → Autenticar correo; se publica el TXT que da Google y luego se pulsa «Iniciar autenticación».

Sin SPF ni DKIM, los avisos de los formularios (enviados por `smtp.gmail.com`) tienen más probabilidad de caer en spam.
