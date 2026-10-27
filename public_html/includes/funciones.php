<?php
/**
 * Funciones auxiliares del sitio.
 *
 * Regla de oro: todo lo que venga de un JSON se imprime con e().
 */

declare(strict_types=1);

// =============================================================================
// Salida segura y URLs
// =============================================================================

/** Escapa texto para HTML (contenido y atributos). */
function e(mixed $valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

/** Convierte una ruta del sitio en URL absoluta (para canonical, OG, sitemap). */
function url_absoluta(string $ruta = '/'): string
{
    if (preg_match('#^https?://#i', $ruta)) {
        return $ruta;
    }
    return URL_SITIO . '/' . ltrim($ruta, '/');
}

/** Ruta a un archivo de assets con versión (fecha de modificación) para romper caché. */
function asset(string $ruta): string
{
    $ruta = ltrim($ruta, '/');
    $archivo = RAIZ_WEB . '/assets/' . $ruta;
    $version = is_file($archivo) ? (string) filemtime($archivo) : '1';
    return '/assets/' . $ruta . '?v=' . $version;
}

/** Ruta pública de un archivo subido (uploads/...). Acepta rutas guardadas con o sin "/" inicial. */
function ruta_upload(string $ruta): string
{
    return '/' . ltrim($ruta, '/');
}

/** Enlace de WhatsApp con mensaje precargado. */
function whatsapp_url(string $mensaje = WHATSAPP_MENSAJE): string
{
    return 'https://api.whatsapp.com/send?phone=' . WHATSAPP_NUMERO . '&text=' . rawurlencode($mensaje);
}

/** true si la URL es segura para un href (http/https, ruta interna, ancla, mailto o tel). */
function es_url_segura(string $url): bool
{
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    if (preg_match('#^(/(?!/)|\#)#', $url)) {
        return true;
    }
    if (preg_match('#^(mailto:|tel:)#i', $url)) {
        return true;
    }
    return (bool) filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url);
}

/** true si la URL apunta fuera de este sitio. */
function es_externa(string $url): bool
{
    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }
    $limpiar = fn(string $h): string => preg_replace('/^www\./', '', strtolower($h)) ?? $h;
    $host = (string) parse_url($url, PHP_URL_HOST);
    $propio = (string) parse_url(URL_SITIO, PHP_URL_HOST);
    return $limpiar($host) !== $limpiar($propio);
}

/** Atributos target/rel para enlaces externos. */
function attr_externo(string $url): string
{
    return es_externa($url) ? ' target="_blank" rel="noopener"' : '';
}

// =============================================================================
// Formato
// =============================================================================

/** Formatea un precio numérico: 3900000 → "$3,900,000 MXN". */
function formato_precio(int|float|string|null $precio, string $moneda = 'MXN'): string
{
    if ($precio === null || $precio === '' || !is_numeric($precio)) {
        return 'Precio a consultar';
    }
    $decimales = (floor((float) $precio) == (float) $precio) ? 0 : 2;
    return '$' . number_format((float) $precio, $decimales, '.', ',') . ' ' . $moneda;
}

/** Fecha ISO (2025-07-19) → "19 de julio de 2025". */
function fecha_es(string $fecha): string
{
    $ts = strtotime($fecha);
    if ($ts === false) {
        return '';
    }
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return date('j', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/** Minutos estimados de lectura (200 palabras por minuto). */
function tiempo_lectura(string $texto): int
{
    $palabras = str_word_count(strip_tags($texto), 0, 'áéíóúüñÁÉÍÓÚÜÑ');
    return max(1, (int) ceil($palabras / 200));
}

/** Recorta un texto a N caracteres sin cortar palabras. */
function extracto(string $texto, int $largo = 160): string
{
    $texto = trim(preg_replace('/\s+/u', ' ', strip_tags($texto)) ?? '');
    if (mb_strlen($texto) <= $largo) {
        return $texto;
    }
    $corte = mb_substr($texto, 0, $largo);
    $espacio = mb_strrpos($corte, ' ');
    return rtrim(mb_substr($corte, 0, $espacio ?: $largo), ' ,.;:') . '…';
}

// =============================================================================
// Datos en JSON
// =============================================================================

/**
 * Esquemas mínimos de cada sección editable.
 * 'req' = campos obligatorios; 'tipos' = validaciones extra por campo.
 * El panel /admin (Fase 2) reutilizará estas mismas reglas.
 */
const ESQUEMAS = [
    'hero' => [
        'req'   => ['id', 'imagen'],
        'tipos' => ['imagen' => 'ruta', 'video' => 'ruta?', 'enlace' => 'url?'],
    ],
    'destacados' => [
        'req'   => ['id', 'operacion', 'titulo', 'precio', 'url_tokko', 'fotos'],
        'tipos' => ['operacion' => 'operacion', 'precio' => 'numero', 'url_tokko' => 'url', 'fotos' => 'lista'],
    ],
    'testimonios' => [
        'req'   => ['id', 'nombre', 'texto'],
        'tipos' => ['foto' => 'ruta?', 'url_resena' => 'url?'],
    ],
    'videos' => [
        'req'   => ['id', 'youtube_id'],
        'tipos' => ['youtube_id' => 'youtube'],
    ],
    'blog' => [
        'req'   => ['slug', 'titulo', 'fecha', 'contenido'],
        'tipos' => ['slug' => 'slug', 'fecha' => 'fecha', 'portada' => 'ruta?'],
    ],
];

/** Valida un elemento contra su esquema. Devuelve la lista de errores (vacía = válido). */
function validar_item(array $item, string $esquema): array
{
    $reglas = ESQUEMAS[$esquema] ?? ['req' => [], 'tipos' => []];
    $errores = [];

    foreach ($reglas['req'] as $campo) {
        if (!array_key_exists($campo, $item) || $item[$campo] === '' || $item[$campo] === null || $item[$campo] === []) {
            $errores[] = "Falta el campo «{$campo}»";
        }
    }

    foreach ($reglas['tipos'] as $campo => $tipo) {
        $opcional = str_ends_with($tipo, '?');
        $tipo = rtrim($tipo, '?');
        $valor = $item[$campo] ?? null;
        if ($valor === null || $valor === '') {
            if (!$opcional && !in_array($campo, $reglas['req'], true)) {
                $errores[] = "Falta el campo «{$campo}»";
            }
            continue;
        }
        $ok = match ($tipo) {
            'numero'    => is_int($valor) || is_float($valor),
            'url'       => is_string($valor) && es_url_segura($valor),
            'ruta'      => is_string($valor) && !str_contains($valor, '..') && preg_match('#^/?[\w\-./]+$#', $valor),
            'lista'     => is_array($valor) && array_is_list($valor),
            'operacion' => in_array($valor, ['venta', 'renta'], true),
            'youtube'   => is_string($valor) && preg_match('/^[\w-]{11}$/', $valor),
            'slug'      => is_string($valor) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $valor),
            'fecha'     => is_string($valor) && strtotime($valor) !== false,
            default     => true,
        };
        if (!$ok) {
            $errores[] = "Valor inválido en «{$campo}»";
        }
    }
    return $errores;
}

/**
 * Lee un JSON de data/ (o de data-seed/ si aún no existe) y lo decodifica.
 * Nunca lanza excepción: si el archivo está dañado devuelve $porDefecto y lo registra en el log.
 */
function leer_json(string $archivo, array $porDefecto = []): array
{
    $archivo = ltrim(str_replace('..', '', $archivo), '/');
    foreach ([DIR_DATA, DIR_DATA_SEED] as $dir) {
        $ruta = $dir . '/' . $archivo;
        if (!is_file($ruta)) {
            continue;
        }
        $contenido = file_get_contents($ruta);
        try {
            $datos = json_decode((string) $contenido, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            error_log("[duan] JSON inválido en {$ruta}: " . $ex->getMessage());
            return $porDefecto;
        }
        return is_array($datos) ? $datos : $porDefecto;
    }
    return $porDefecto;
}

/**
 * Devuelve los elementos activos y válidos de una sección (hero, destacados, testimonios, videos).
 * El orden es el del arreglo en el JSON (el panel lo reordena con drag & drop).
 */
function obtener_seccion(string $seccion): array
{
    $datos = leer_json($seccion . '.json', ['items' => []]);
    $items = $datos['items'] ?? [];
    if (!is_array($items)) {
        return [];
    }
    $validos = [];
    foreach ($items as $item) {
        if (!is_array($item) || ($item['activo'] ?? true) === false) {
            continue;
        }
        $errores = validar_item($item, $seccion);
        if ($errores) {
            error_log("[duan] {$seccion}: elemento omitido (" . ($item['id'] ?? '?') . '): ' . implode('; ', $errores));
            continue;
        }
        $validos[] = $item;
    }
    return $validos;
}

/** Lista de artículos del blog activos, del más reciente al más antiguo. */
function obtener_posts(): array
{
    $archivos = glob(DIR_DATA . '/blog/*.json') ?: [];
    if (!$archivos) {
        $archivos = glob(DIR_DATA_SEED . '/blog/*.json') ?: [];
    }
    $posts = [];
    foreach ($archivos as $ruta) {
        $post = leer_json('blog/' . basename($ruta));
        if (!$post || ($post['activo'] ?? true) === false) {
            continue;
        }
        if ($errores = validar_item($post, 'blog')) {
            error_log('[duan] blog: ' . basename($ruta) . ' omitido: ' . implode('; ', $errores));
            continue;
        }
        $posts[] = $post;
    }
    usort($posts, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    return $posts;
}

/** Busca un artículo por slug actual o por su slug anterior de Wix. */
function obtener_post(string $slug): ?array
{
    foreach (obtener_posts() as $post) {
        if ($post['slug'] === $slug || ($post['slug_anterior'] ?? null) === $slug) {
            return $post;
        }
    }
    return null;
}

/**
 * Primer arranque en un servidor nuevo: copia data-seed/ → data/ y uploads-seed/ → uploads/.
 * Solo copia archivos que NO existen, así un deploy jamás pisa lo que editó el cliente.
 */
function inicializar_datos(): void
{
    $marca = DIR_DATA . '/.inicializado';
    if (is_file($marca)) {
        return;
    }
    $copiar = function (string $origen, string $destino) use (&$copiar): void {
        if (!is_dir($origen)) {
            return;
        }
        if (!is_dir($destino) && !@mkdir($destino, 0755, true)) {
            return;
        }
        foreach (scandir($origen) ?: [] as $nombre) {
            if ($nombre === '.' || $nombre === '..' || $nombre === '.gitkeep') {
                continue;
            }
            $o = $origen . '/' . $nombre;
            $d = $destino . '/' . $nombre;
            if (is_dir($o)) {
                $copiar($o, $d);
            } elseif (!file_exists($d)) {
                @copy($o, $d);
            }
        }
    };
    $copiar(DIR_DATA_SEED, DIR_DATA);
    $copiar(DIR_UPLOADS_SEED, DIR_UPLOADS);
    if (is_dir(DIR_DATA)) {
        @mkdir(DIR_DATA . '/backups', 0755, true);
        @file_put_contents($marca, date('c'));
    }
}

// =============================================================================
// Plantillas
// =============================================================================

/** Pinta un componente de components/ con las variables dadas. */
function componente(string $nombre, array $vars = []): void
{
    $archivo = RAIZ_WEB . '/components/' . basename($nombre) . '.php';
    if (!is_file($archivo)) {
        error_log("[duan] componente inexistente: {$nombre}");
        return;
    }
    (static function (string $__archivo, array $__vars): void {
        extract($__vars, EXTR_SKIP);
        require $__archivo;
    })($archivo, $vars);
}

/** Devuelve el HTML de un componente como texto. */
function componente_html(string $nombre, array $vars = []): string
{
    ob_start();
    componente($nombre, $vars);
    return (string) ob_get_clean();
}

/** Ícono SVG en línea desde assets/img/iconos.svg (sprite). */
function icono(string $nombre, string $clase = 'icono'): string
{
    return '<svg class="' . e($clase) . '" aria-hidden="true" focusable="false"><use href="' . e(asset('img/iconos.svg')) . '#' . e($nombre) . '"></use></svg>';
}

/** Responde 404 con la página de error del sitio. */
function no_encontrado(): never
{
    http_response_code(404);
    require RAIZ_WEB . '/404.php';
    exit;
}

// =============================================================================
// Markdown seguro para el blog
// =============================================================================

/** Componentes que se pueden insertar en un artículo con [[nombre]] en su propia línea. */
const SHORTCODES_BLOG = [
    'calculadora-gastos' => 'calculadora-gastos',
    'cta-asesoria'       => 'cta-asesoria',
];

/** Formato en línea: **negritas**, *cursivas*, [enlaces](url). Recibe texto YA escapado. */
function markdown_linea(string $html): string
{
    // Enlaces [texto](url)
    $html = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/u', function ($m) {
        $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (!es_url_segura($url)) {
            return $m[1];
        }
        return '<a href="' . e($url) . '"' . attr_externo($url) . '>' . $m[1] . '</a>';
    }, $html) ?? $html;
    $html = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $html) ?? $html;
    $html = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/u', '<em>$1</em>', $html) ?? $html;
    return $html;
}

/**
 * Convierte Markdown básico a HTML seguro (todo el texto se escapa primero).
 * Soporta: ## títulos, párrafos, listas, > citas, ---, imágenes, tablas simples y [[shortcodes]].
 */
function markdown(string $texto): string
{
    $lineas = preg_split('/\R/u', str_replace("\t", '    ', $texto)) ?: [];
    $html = [];
    $parrafo = [];
    $lista = null;   // 'ul' | 'ol'
    $cita = [];
    $tabla = [];

    $cerrar = function () use (&$html, &$parrafo, &$lista, &$cita, &$tabla): void {
        if ($parrafo) {
            $html[] = '<p>' . markdown_linea(implode("\n", $parrafo)) . '</p>';
            $parrafo = [];
        }
        if ($lista) {
            $html[] = "</{$lista}>";
            $lista = null;
        }
        if ($cita) {
            $html[] = '<blockquote><p>' . markdown_linea(implode('<br>', $cita)) . '</p></blockquote>';
            $cita = [];
        }
        if ($tabla) {
            $filas = $tabla;
            $tabla = [];
            $out = '<div class="tabla-scroll"><table>';
            $encabezado = isset($filas[1]) && preg_match('/^\|?\s*:?-{3,}/', $filas[1]);
            foreach ($filas as $i => $fila) {
                if ($encabezado && $i === 1) {
                    continue;
                }
                $celdas = array_map('trim', explode('|', trim($fila, " |")));
                $tag = ($encabezado && $i === 0) ? 'th' : 'td';
                $out .= '<tr>' . implode('', array_map(fn($c) => "<{$tag}>" . markdown_linea(e($c)) . "</{$tag}>", $celdas)) . '</tr>';
            }
            $html[] = $out . '</table></div>';
        }
    };

    foreach ($lineas as $linea) {
        $t = trim($linea);

        if ($t === '') {
            $cerrar();
            continue;
        }
        if (preg_match('/^\[\[([a-z0-9-]+)\]\]$/', $t, $m)) {
            $cerrar();
            if (isset(SHORTCODES_BLOG[$m[1]])) {
                $html[] = componente_html(SHORTCODES_BLOG[$m[1]]);
            }
            continue;
        }
        if (preg_match('/^(#{1,4})\s+(.+)$/u', $t, $m)) {
            $cerrar();
            $nivel = max(2, strlen($m[1]));
            $html[] = "<h{$nivel}>" . markdown_linea(e($m[2])) . "</h{$nivel}>";
            continue;
        }
        if (preg_match('/^(-{3,}|\*{3,})$/', $t)) {
            $cerrar();
            $html[] = '<hr>';
            continue;
        }
        if (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)\)$/u', $t, $m)) {
            $cerrar();
            if (es_url_segura($m[2]) || preg_match('#^uploads/[\w\-./]+$#', $m[2])) {
                $src = preg_match('#^uploads/#', $m[2]) ? ruta_upload($m[2]) : $m[2];
                $html[] = '<figure><img src="' . e($src) . '" alt="' . e($m[1]) . '" loading="lazy" decoding="async"></figure>';
            }
            continue;
        }
        if (str_starts_with($t, '|')) {
            if (!$tabla) {
                $cerrar();
            }
            $tabla[] = $t;
            continue;
        }
        if (str_starts_with($t, '>')) {
            if (!$cita) {
                $cerrar();
            }
            $cita[] = e(ltrim(substr($t, 1)));
            continue;
        }
        if (preg_match('/^([-*]|\d+\.)\s+(.+)$/u', $t, $m)) {
            $tipo = ctype_digit(rtrim($m[1], '.')) ? 'ol' : 'ul';
            if ($lista !== $tipo) {
                $cerrar();
                $html[] = "<{$tipo}>";
                $lista = $tipo;
            }
            $html[] = '<li>' . markdown_linea(e($m[2])) . '</li>';
            continue;
        }
        if ($lista || $cita || $tabla) {
            $cerrar();
        }
        // Dos espacios al final = salto de línea dentro del párrafo
        $parrafo[] = e(rtrim($linea)) . (str_ends_with($linea, '  ') ? '<br>' : '');
    }
    $cerrar();
    return implode("\n", $html);
}

// =============================================================================
// SEO
// =============================================================================

/** JSON-LD del negocio (RealEstateAgent, que es un tipo de LocalBusiness). */
function jsonld_negocio(): array
{
    $d = DIRECCION;
    return [
        '@context'  => 'https://schema.org',
        '@type'     => 'RealEstateAgent',
        '@id'       => URL_SITIO . '/#negocio',
        'name'      => NOMBRE_SITIO,
        'slogan'    => SLOGAN,
        'url'       => URL_SITIO . '/',
        'logo'      => url_absoluta('/assets/img/logo-duan-bienes-raices.webp'),
        'image'     => url_absoluta('/assets/img/og-default.jpg'),
        'telephone' => TELEFONO_E164,
        'email'     => EMAIL_CONTACTO,
        'foundingDate' => '2013',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $d['calle'] . ', ' . $d['colonia'],
            'postalCode'      => $d['cp'],
            'addressLocality' => $d['ciudad'],
            'addressRegion'   => $d['estado'],
            'addressCountry'  => $d['pais'],
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $d['latitud'],
            'longitude' => $d['longitud'],
        ],
        'areaServed' => 'León, Guanajuato',
        'hasMap'     => URL_MAPS_OFICINA,
        'sameAs'     => array_values(REDES),
    ];
}
