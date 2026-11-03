# Respaldo del DNS mientras está en Wix

Consultado el 3 nov 2026 en el DNS público (8.8.8.8). Sirve para recrear la zona en SiteGround sin tumbar el correo.
Los subdominios que no se consultaron aquí no aparecen; hay que compararlo con el panel de DNS de Wix antes del cambio.

| Nombre | Tipo | Valor actual | En SiteGround |
|---|---|---|---|
| `@` | NS | `ns14.wixdns.net`, `ns15.wixdns.net` | Cambiar a los NS de SiteGround (o dejar Wix y solo mover A/CNAME) |
| `@` | A | `185.230.63.107`, `.171`, `.186` (Wix) | IP del sitio en SiteGround |
| `www` | CNAME | `cdn3.wixdns.net` (Wix) | `duanbienesraices.com` o la IP de SiteGround |
| `@` | MX 10 | `aspmx.l.google.com` | **Igual** |
| `@` | MX 20 | `alt1.aspmx.l.google.com` | **Igual** |
| `@` | MX 30 | `alt2.aspmx.l.google.com` | **Igual** |
| `@` | MX 40 | `alt3.aspmx.l.google.com` | **Igual** |
| `@` | MX 50 | `alt4.aspmx.l.google.com` | **Igual** |
| `@` | TXT | `google-site-verification=fRGTAp6F_Lj7iKv6kbMBgC72lnmTPDHpFDn4afY2lfk` | **Igual** (verificación de Google Workspace / Search Console) |
| `_dmarc` | CNAME | `_dmarc.wixemails.com` → `v=DMARC1; p=none; rua=mailto:dmarc_agg@vali.email` | Reemplazar por un TXT propio (ver abajo); el de Wix deja de tener sentido |

## Lo que no existe hoy y conviene agregar

- **SPF** (no hay ninguno): TXT en `@` → `v=spf1 include:_spf.google.com ~all`.
- **DKIM** (no hay `google._domainkey`): se genera en admin.google.com → Apps → Google Workspace → Gmail → Autenticar correo; se publica el TXT que da Google y luego se pulsa «Iniciar autenticación».
- **DMARC propio**: TXT en `_dmarc` → `v=DMARC1; p=none; rua=mailto:info@duanbienesraices.com`.

Sin SPF ni DKIM, los avisos de los formularios (enviados por `smtp.gmail.com`) tienen más probabilidad de caer en spam.
