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

## Agregados el 3 nov 2026 (copiar tal cual si se cambia de DNS)

| Nombre | Tipo | Valor |
|---|---|---|
| `@` | TXT | `v=spf1 include:_spf.google.com ~all` (SPF) |
| `google._domainkey` | TXT | `v=DKIM1; k=rsa; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA4wGQQfLOaX3oHDaJi0RmF5k995pCqp0hz9aeYCVmXUUi59Y9EYLn03FlM/JPayjQSMqflbzvEs/BstM8X6mKij3uK/5VUzJ5T5VZXDskfLrnQob5m2+33eMNG/SRAyGrFWXxasHy9RaQu3Q90p3jK/jFG/bgINjJJdWsIPbBz+yXxA6d1/StFfUyzyqr9kmV9F9j4LgLFK+cfrO+7e/yXHfabE7IutWNgWCjUbCBherq4pm31DAgu5AAind5aWcg0zlmSjxuBVX/oilDNVyqaLvnZiwZGVTplRLknw8lJ/XYEYG8C5fBm8gYgDBYffX9GdaJZl8LGXYZkFJJsqOuzwIDAQAB` (DKIM de Google Workspace, 2048 bits) |

El DKIM se generó en admin.google.com → Apps → Google Workspace → Gmail → Autenticar correo; después de publicarlo se pulsa «Iniciar autenticación».
