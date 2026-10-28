<?php
/**
 * Estructura HTML común de las páginas del panel.
 */

declare(strict_types=1);

/** Abre la página: <head>, barra superior y avisos. $migas = [['texto', 'url'], ...] */
function admin_cabecera(string $titulo, array $migas = []): void
{
    $conSesion = !empty($_SESSION['admin']);
    ?>
<!DOCTYPE html>
<html lang="es-MX">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($titulo) ?> · Panel Du'an</title>
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="panel">
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <header class="barra">
        <a class="barra__marca" href="/admin/">
            <img src="<?= e(asset('img/logo-duan-bienes-raices.webp')) ?>" alt="" width="40" height="40">
            <span>Panel <strong>Du'an</strong></span>
        </a>
        <?php if ($conSesion): ?>
            <nav class="barra__acciones" aria-label="Panel">
                <a href="/" target="_blank" rel="noopener">Ver sitio ↗</a>
                <form method="post" action="/admin/salir.php">
                    <?= campo_csrf() ?>
                    <button class="boton boton--texto boton--claro" type="submit">Cerrar sesión</button>
                </form>
            </nav>
        <?php endif; ?>
    </header>
    <main class="principal" id="contenido">
        <?php if ($migas): ?>
            <nav class="migas" aria-label="Ubicación">
                <ol>
                    <?php foreach ($migas as [$texto, $url]): ?>
                        <li><?= $url ? '<a href="' . e($url) . '">' . e($texto) . '</a>' : '<span aria-current="page">' . e($texto) . '</span>' ?></li>
                    <?php endforeach; ?>
                </ol>
            </nav>
        <?php endif; ?>
        <?php foreach (tomar_avisos() as $aviso): ?>
            <p class="aviso aviso--<?= e($aviso['tipo']) ?>" role="<?= $aviso['tipo'] === 'error' ? 'alert' : 'status' ?>"><?= e($aviso['texto']) ?></p>
        <?php endforeach; ?>
        <div class="toast" data-toast role="status" aria-live="polite" hidden></div>
    <?php
}

function admin_pie(): void
{
    ?>
    </main>
</body>
</html>
    <?php
}

/** Miniatura de un elemento para las listas. */
function miniatura_de(string $seccion, array $item): string
{
    $campo = SECCIONES_ADMIN[$seccion]['campo_miniatura'];
    $valor = $item[$campo] ?? null;
    $src = match (true) {
        $campo === 'fotos' && is_array($valor) && !empty($valor[0]['card']) => ruta_upload($valor[0]['card']),
        $campo === 'youtube_id' && is_string($valor) && $valor !== '' => 'https://i.ytimg.com/vi/' . rawurlencode($valor) . '/mqdefault.jpg',
        is_string($valor) && $valor !== '' => ruta_upload($valor),
        default => null,
    };
    return $src
        ? '<img class="lista__miniatura" src="' . e($src) . '" alt="" loading="lazy" width="96" height="72">'
        : '<span class="lista__miniatura lista__miniatura--vacia" aria-hidden="true">—</span>';
}

/** Nombre visible de un elemento en las listas. */
function nombre_de(string $seccion, array $item): string
{
    $nombre = trim((string) ($item[SECCIONES_ADMIN[$seccion]['campo_nombre']] ?? ''));
    if ($nombre !== '') {
        return $nombre;
    }
    return $seccion === 'hero' ? ($item['alt'] ?? 'Diapositiva sin texto') : 'Sin título';
}

/** Línea secundaria de cada elemento en la lista. */
function detalle_de(string $seccion, array $item): string
{
    return match ($seccion) {
        'destacados'  => ucfirst((string) ($item['operacion'] ?? '')) . ' · ' . formato_precio($item['precio'] ?? null, $item['moneda'] ?? 'MXN')
                         . ' · ' . count($item['fotos'] ?? []) . ' fotos',
        'hero'        => !empty($item['video']) ? 'Imagen + video' : 'Solo imagen',
        'testimonios' => extracto((string) ($item['texto'] ?? ''), 90),
        'videos'      => 'youtube.com/watch?v=' . ($item['youtube_id'] ?? ''),
        'blog'        => (!empty($item['fecha']) ? fecha_es((string) $item['fecha']) : 'Sin fecha') . ' · /blog/' . ($item['slug'] ?? ''),
        default       => '',
    };
}
