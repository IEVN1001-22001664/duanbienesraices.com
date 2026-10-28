<?php
/**
 * Lectura y escritura segura de los JSON desde el panel.
 *
 * - Escritura atómica: archivo temporal + rename(), con flock() para que dos
 *   guardados simultáneos no se pisen.
 * - Antes de cada escritura se copia la versión actual a data/backups/ (se
 *   conservan las últimas RESPALDOS_MAX por archivo).
 * - Cada formulario lleva la "versión" (hash) del archivo que se leyó; si alguien
 *   guardó en medio, se rechaza el cambio en lugar de perder el trabajo del otro.
 */

declare(strict_types=1);

const RESPALDOS_MAX = 20;
const HORAS_GRACIA_UPLOADS = 6;   // archivos recién subidos sin guardar no se borran antes de esto

/**
 * Definición de cada sección editable: campos del formulario, tamaños de imagen, etc.
 * Tipos de campo: texto, area, url, precio, opciones, youtube, fecha, imagen, fotos, video, markdown.
 * Las reglas finales siguen siendo las de ESQUEMAS (funciones.php), que también usa el sitio.
 */
const SECCIONES_ADMIN = [
    'destacados' => [
        'titulo'    => 'Propiedades destacadas',
        'singular'  => 'propiedad',
        'nuevo'     => 'Agregar propiedad',
        'descripcion' => 'Las tarjetas de propiedades del inicio, con su galería de fotos y liga a Tokko.',
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'fotos',
        'campos' => [
            'operacion' => ['tipo' => 'opciones', 'etiqueta' => 'Operación', 'req' => true,
                            'opciones' => ['venta' => 'Venta', 'renta' => 'Renta']],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 120,
                            'ayuda' => 'Ej.: Casa en Venta en Sacromonte Residencial'],
            'tipo'      => ['tipo' => 'texto', 'etiqueta' => 'Tipo de inmueble', 'max' => 60,
                            'ayuda' => 'Ej.: Casa Residencial, Bodega Industrial, Terreno Comercial'],
            'ubicacion' => ['tipo' => 'texto', 'etiqueta' => 'Ubicación', 'max' => 90,
                            'ayuda' => 'Ej.: Fracc. Sacromonte, León, Gto.'],
            'precio'    => ['tipo' => 'precio', 'etiqueta' => 'Precio', 'req' => true,
                            'ayuda' => 'Solo el número, sin comas ni signo de pesos. Ej.: 3900000'],
            'moneda'    => ['tipo' => 'opciones', 'etiqueta' => 'Moneda', 'req' => true,
                            'opciones' => ['MXN' => 'Pesos (MXN)', 'USD' => 'Dólares (USD)']],
            'descripcion' => ['tipo' => 'area', 'etiqueta' => 'Descripción corta', 'max' => 400, 'filas' => 4,
                            'ayuda' => 'Dos o tres líneas con lo más atractivo de la propiedad.'],
            'url_tokko' => ['tipo' => 'url', 'etiqueta' => 'Liga a la ficha en Tokko', 'req' => true,
                            'ayuda' => 'Copia la dirección de la ficha. Ej.: https://www.duanbienesraices-buscador.com/p/8448414-prop'],
            'fotos'     => ['tipo' => 'fotos', 'etiqueta' => 'Fotos', 'req' => true, 'max' => 12,
                            'tamanos' => ['card' => [720, 540, 'recortar'], 'full' => [1400, 1050, 'ajustar']],
                            'ayuda' => 'Hasta 12 fotos. La primera es la portada. Arrástralas para cambiar el orden.'],
        ],
    ],
    'hero' => [
        'titulo'    => 'Slider principal',
        'singular'  => 'diapositiva',
        'nuevo'     => 'Agregar diapositiva',
        'descripcion' => 'Las imágenes grandes (y videos) que rotan al inicio de la página.',
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'imagen',
        'campos' => [
            'imagen'    => ['tipo' => 'imagen', 'etiqueta' => 'Imagen de fondo', 'req' => true,
                            'tamanos' => ['' => [1920, 1080, 'recortar']],
                            'ayuda' => 'Horizontal, de preferencia 1920 × 1080. También se muestra en celulares y mientras carga el video.'],
            'alt'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la imagen', 'req' => true, 'max' => 150,
                            'ayuda' => 'Qué se ve en la imagen (lo leen Google y los lectores de pantalla).'],
            'video'     => ['tipo' => 'video', 'etiqueta' => 'Video de fondo (opcional)',
                            'ayuda' => 'MP4, máximo 40 MB. Solo se reproduce en pantallas grandes.'],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Texto principal (opcional)', 'max' => 90],
            'subtitulo' => ['tipo' => 'texto', 'etiqueta' => 'Texto secundario (opcional)', 'max' => 160],
            'texto_boton' => ['tipo' => 'texto', 'etiqueta' => 'Texto del botón (opcional)', 'max' => 40],
            'enlace'    => ['tipo' => 'url', 'etiqueta' => 'Liga del botón (opcional)',
                            'ayuda' => 'Una página del sitio (/contacto) o una dirección completa (https://…).'],
        ],
    ],
    'testimonios' => [
        'titulo'    => 'Testimonios',
        'singular'  => 'testimonio',
        'nuevo'     => 'Agregar testimonio',
        'descripcion' => 'Opiniones de clientes en el carrusel del inicio.',
        'campo_nombre' => 'nombre',
        'campo_miniatura' => 'foto',
        'campos' => [
            'nombre'    => ['tipo' => 'texto', 'etiqueta' => 'Nombre del cliente', 'req' => true, 'max' => 60,
                            'ayuda' => 'Como aparece en Google. Ej.: Jorge S.'],
            'texto'     => ['tipo' => 'area', 'etiqueta' => 'Testimonio', 'req' => true, 'max' => 700, 'filas' => 6,
                            'ayuda' => 'Cópialo tal cual de la reseña, sin comillas.'],
            'estrellas' => ['tipo' => 'opciones', 'etiqueta' => 'Estrellas', 'req' => true,
                            'opciones' => ['5' => '★★★★★ (5)', '4' => '★★★★ (4)', '3' => '★★★ (3)']],
            'foto'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto (opcional)',
                            'tamanos' => ['' => [640, 853, 'recortar']],
                            'ayuda' => 'Vertical. Se recorta automáticamente.'],
            'alt'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la foto (opcional)', 'max' => 150],
            'url_resena' => ['tipo' => 'url', 'etiqueta' => 'Liga a la reseña en Google (opcional)',
                            'ayuda' => 'Si la dejas vacía, el nombre lleva al perfil general de Google.'],
        ],
    ],
    'videos' => [
        'titulo'    => 'Videos de YouTube',
        'singular'  => 'video',
        'nuevo'     => 'Agregar video',
        'descripcion' => 'La galería "Conoce estas propiedades".',
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'youtube_id',
        'campos' => [
            'youtube_id' => ['tipo' => 'youtube', 'etiqueta' => 'Video de YouTube', 'req' => true,
                            'ayuda' => 'Pega la dirección del video. Ej.: https://www.youtube.com/watch?v=__qOj03d8QY'],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 140,
                            'ayuda' => 'El que aparece debajo del video.'],
        ],
    ],
    'blog' => [
        'titulo'    => 'Blog',
        'singular'  => 'artículo',
        'nuevo'     => 'Escribir artículo',
        'descripcion' => 'Artículos del blog. Se ordenan solos por fecha.',
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'portada',
        'campos' => [
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 140],
            'fecha'     => ['tipo' => 'fecha', 'etiqueta' => 'Fecha de publicación', 'req' => true],
            'autor'     => ['tipo' => 'texto', 'etiqueta' => 'Autor', 'max' => 80],
            'resumen'   => ['tipo' => 'area', 'etiqueta' => 'Resumen', 'max' => 300, 'filas' => 3,
                            'ayuda' => 'Aparece en el listado del blog.'],
            'portada'   => ['tipo' => 'imagen', 'etiqueta' => 'Imagen de portada',
                            'tamanos' => ['' => [1200, 800, 'recortar']]],
            'portada_alt' => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la portada', 'max' => 150],
            'contenido' => ['tipo' => 'markdown', 'etiqueta' => 'Contenido', 'req' => true,
                            'tamanos' => ['' => [1200, 1200, 'ajustar']]],
            'descripcion_seo' => ['tipo' => 'area', 'etiqueta' => 'Descripción para Google', 'max' => 160, 'filas' => 2,
                            'ayuda' => 'Máximo 160 caracteres. Si la dejas vacía se usa el resumen.'],
        ],
    ],
];

/** Error que se le puede mostrar tal cual al editor. */
final class ErrorPanel extends RuntimeException
{
}

// =============================================================================
// Rutas y versiones
// =============================================================================

/** Archivo JSON (relativo a data/) donde vive un elemento. El blog usa un archivo por artículo. */
function archivo_de(string $seccion, ?string $id = null): string
{
    if ($seccion === 'blog') {
        if ($id === null || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $id)) {
            throw new ErrorPanel('Artículo no válido.');
        }
        return 'blog/' . $id . '.json';
    }
    return $seccion . '.json';
}

function ruta_data(string $archivo): string
{
    return DIR_DATA . '/' . $archivo;
}

/** Huella del archivo tal como está en disco ('' si no existe). */
function version_de(string $archivo): string
{
    $ruta = ruta_data($archivo);
    return is_file($ruta) ? md5_file($ruta) : '';
}

/** Lee un JSON de data/ para editar (sin filtrar inactivos ni inválidos). */
function leer_para_editar(string $archivo): ?array
{
    $ruta = ruta_data($archivo);
    if (!is_file($ruta)) {
        return null;
    }
    $datos = json_decode((string) file_get_contents($ruta), true);
    if (!is_array($datos)) {
        throw new ErrorPanel('El archivo ' . $archivo . ' está dañado. Restaura una versión anterior.');
    }
    return $datos;
}

/** Elementos de una sección para el panel. En el blog, cada artículo con su versión. */
function elementos_de(string $seccion): array
{
    if ($seccion !== 'blog') {
        return leer_para_editar(archivo_de($seccion))['items'] ?? [];
    }
    $posts = [];
    foreach (glob(DIR_DATA . '/blog/*.json') ?: [] as $ruta) {
        $post = json_decode((string) file_get_contents($ruta), true);
        if (is_array($post) && !empty($post['slug'])) {
            $post['id'] = $post['slug'];
            $posts[] = $post;
        }
    }
    usort($posts, fn($a, $b) => strcmp((string) ($b['fecha'] ?? ''), (string) ($a['fecha'] ?? '')));
    return $posts;
}

/** Busca un elemento por id. */
function elemento(string $seccion, string $id): ?array
{
    if ($seccion === 'blog') {
        $post = leer_para_editar(archivo_de('blog', $id));
        return $post ? $post + ['id' => $post['slug'] ?? $id] : null;
    }
    foreach (elementos_de($seccion) as $item) {
        if (($item['id'] ?? null) === $id) {
            return $item;
        }
    }
    return null;
}

// =============================================================================
// Escritura atómica con respaldo
// =============================================================================

/**
 * Modifica un JSON de data/ de forma segura.
 *
 * $cambio recibe los datos actuales (o null si no existe) y devuelve los nuevos
 * (o null para eliminar el archivo). Devuelve la nueva versión del archivo.
 *
 * @throws ErrorPanel si la versión no coincide o no se pudo escribir.
 */
function modificar_json(string $archivo, ?string $versionEsperada, callable $cambio): string
{
    $ruta = ruta_data($archivo);
    $dir = dirname($ruta);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new ErrorPanel('No se pudo crear la carpeta de datos.');
    }

    $candado = fopen(DIR_DATA . '/.candado', 'c');
    if (!$candado || !flock($candado, LOCK_EX)) {
        throw new ErrorPanel('No se pudo bloquear el archivo para guardar. Intenta de nuevo.');
    }
    try {
        clearstatcache(true, $ruta);
        if ($versionEsperada !== null && !hash_equals(version_de($archivo), $versionEsperada)) {
            throw new ErrorPanel('Alguien más guardó cambios en esta sección mientras editabas. '
                . 'Recarga la página para ver la versión actual (tus cambios no se guardaron).');
        }
        $actual = is_file($ruta) ? json_decode((string) file_get_contents($ruta), true) : null;
        $nuevo = $cambio(is_array($actual) ? $actual : null);

        if (is_file($ruta)) {
            respaldar($archivo);
        }
        if ($nuevo === null) {
            if (is_file($ruta) && !@unlink($ruta)) {
                throw new ErrorPanel('No se pudo eliminar el archivo.');
            }
            return '';
        }

        $json = json_encode($nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $tmp = $ruta . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (file_put_contents($tmp, $json) !== strlen($json) || !@rename($tmp, $ruta)) {
            @unlink($tmp);
            throw new ErrorPanel('No se pudo guardar el archivo. Revisa los permisos de la carpeta data/.');
        }
        clearstatcache(true, $ruta);
        return md5($json);
    } finally {
        flock($candado, LOCK_UN);
        fclose($candado);
    }
}

/** Fecha de modificación para guardar en "actualizado". */
function ahora_iso(): string
{
    return date('c');
}

// =============================================================================
// Respaldos (data/backups/{seccion}/ o data/backups/blog/{slug}/)
// =============================================================================

function dir_respaldos(string $archivo): string
{
    return DIR_DATA . '/backups/' . preg_replace('/\.json$/', '', $archivo);
}

/** Copia la versión actual a backups/ con fecha y deja solo las últimas RESPALDOS_MAX. */
function respaldar(string $archivo): void
{
    $dir = dir_respaldos($archivo);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new ErrorPanel('No se pudo crear la carpeta de respaldos; no se guardó nada.');
    }
    // Microsegundos en el nombre: los respaldos del mismo segundo quedan bien ordenados
    $micro = sprintf('%06d', (int) (fmod(microtime(true), 1) * 1_000_000));
    $destino = $dir . '/' . date('Y-m-d_His') . '-' . $micro . '.json';
    if (!@copy(ruta_data($archivo), $destino)) {
        throw new ErrorPanel('No se pudo crear el respaldo; no se guardó nada.');
    }
    $todos = respaldos_de($archivo);
    foreach (array_slice($todos, RESPALDOS_MAX) as $viejo) {
        @unlink($viejo['ruta']);
    }
}

/** Respaldos de un archivo, del más reciente al más antiguo. */
function respaldos_de(string $archivo): array
{
    $lista = [];
    foreach (glob(dir_respaldos($archivo) . '/*.json') ?: [] as $ruta) {
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})_(\d{2})(\d{2})(\d{2})-[0-9a-f]+\.json$/', basename($ruta), $m)) {
            continue;
        }
        $lista[] = [
            'nombre' => basename($ruta),
            'ruta'   => $ruta,
            'fecha'  => strtotime("{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}") ?: filemtime($ruta),
        ];
    }
    usort($lista, fn($a, $b) => strcmp($b['nombre'], $a['nombre']));
    return $lista;
}

/** Ruta de un respaldo validando que el nombre no salga de su carpeta. */
function ruta_respaldo(string $archivo, string $nombre): string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}-[0-9a-f]+\.json$/', $nombre)) {
        throw new ErrorPanel('Respaldo no válido.');
    }
    $ruta = dir_respaldos($archivo) . '/' . $nombre;
    if (!is_file($ruta)) {
        throw new ErrorPanel('Ese respaldo ya no existe.');
    }
    return $ruta;
}

/** Restaura un respaldo (la versión actual también se respalda, así se puede deshacer). */
function restaurar_respaldo(string $archivo, string $nombre): void
{
    $datos = json_decode((string) file_get_contents(ruta_respaldo($archivo, $nombre)), true);
    if (!is_array($datos)) {
        throw new ErrorPanel('Ese respaldo está dañado y no se puede restaurar.');
    }
    modificar_json($archivo, null, function () use ($datos) {
        $datos['actualizado'] = ahora_iso();
        return $datos;
    });
}

/** Artículos del blog que ya no existen pero tienen respaldos (para recuperarlos). */
function blog_eliminados(): array
{
    $lista = [];
    foreach (glob(DIR_DATA . '/backups/blog/*', GLOB_ONLYDIR) ?: [] as $dir) {
        $slug = basename($dir);
        if (!is_file(DIR_DATA . '/blog/' . $slug . '.json') && respaldos_de('blog/' . $slug . '.json')) {
            $lista[] = $slug;
        }
    }
    return $lista;
}

// =============================================================================
// Limpieza de archivos subidos
// =============================================================================

/**
 * Borra de uploads/{seccion}/ los archivos que ya no usa nadie.
 *
 * Un archivo se conserva si aparece en el JSON actual O en cualquiera de sus
 * respaldos (así "Restaurar versión anterior" nunca deja fotos rotas). Las fotos
 * de un elemento eliminado se borran del disco cuando sale del último respaldo.
 * Los archivos recién subidos y aún sin guardar tienen HORAS_GRACIA_UPLOADS de gracia.
 */
function limpiar_uploads(string $seccion): int
{
    $carpeta = DIR_UPLOADS . '/' . $seccion;
    if (!is_dir($carpeta)) {
        return 0;
    }
    $textos = [];
    $patrones = $seccion === 'blog'
        ? [DIR_DATA . '/blog/*.json', DIR_DATA . '/backups/blog/*/*.json']
        : [DIR_DATA . '/' . $seccion . '.json', DIR_DATA . '/backups/' . $seccion . '/*.json'];
    foreach ($patrones as $patron) {
        foreach (glob($patron) ?: [] as $ruta) {
            $textos[] = str_replace('\/', '/', (string) file_get_contents($ruta));
        }
    }
    $todo = implode("\n", $textos);
    preg_match_all('#uploads/' . preg_quote($seccion, '#') . '/([\w\-.]+)#', $todo, $m);
    $usados = array_flip($m[1]);

    $borrados = 0;
    $limite = time() - HORAS_GRACIA_UPLOADS * 3600;
    foreach (scandir($carpeta) ?: [] as $nombre) {
        $ruta = $carpeta . '/' . $nombre;
        if ($nombre[0] === '.' || !is_file($ruta) || isset($usados[$nombre]) || filemtime($ruta) > $limite) {
            continue;
        }
        if (@unlink($ruta)) {
            $borrados++;
        }
    }
    return $borrados;
}

// =============================================================================
// Identificadores
// =============================================================================

/** "Casa en Venta en León" → "casa-en-venta-en-leon". */
function slug(string $texto, int $largo = 60): string
{
    $texto = mb_strtolower(trim($texto));
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $texto = (string) preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim(substr($texto, 0, $largo), '-');
    return $texto !== '' ? $texto : 'elemento';
}

/** Id único dentro de una sección a partir de un texto base. */
function id_unico(string $seccion, string $base): string
{
    $base = slug($base);
    $existe = $seccion === 'blog'
        ? fn(string $id) => is_file(DIR_DATA . '/blog/' . $id . '.json') || is_dir(DIR_DATA . '/backups/blog/' . $id)
        : fn(string $id) => (bool) array_filter(elementos_de($seccion), fn($i) => ($i['id'] ?? '') === $id);
    $id = $base;
    for ($n = 2; $existe($id); $n++) {
        $id = $base . '-' . $n;
    }
    return $id;
}
