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

Requiere PHP 8.1+ con extensiones `mbstring`, `json` y `gd` (GD se usará en la Fase 2).

```bash
php -S localhost:8000 -t public_html dev-router.php
```

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
