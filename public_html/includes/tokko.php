<?php
/**
 * Integración con la API de Tokko Broker (solo lectura).
 *
 * - tokko_sincronizar() descarga todas las propiedades activas y guarda una
 *   copia "limpia" en data/tokko/propiedades.json. Solo se guardan campos
 *   públicos: nunca internal_data (dueños, comisiones, notas), la sucursal,
 *   el asesor ni la dirección real.
 * - tokko_actualizar_destacados() rellena destacados.json con esos datos: el
 *   panel solo elige qué propiedades van y en qué orden; textos, precio y
 *   fotos vienen siempre de Tokko. Las fotos se copian a WebP en uploads/destacados/.
 * - El sitio nunca espera a Tokko: lee los JSON locales y, si la copia tiene más
 *   de TOKKO_VIGENCIA, la actualiza después de enviar la página (ver
 *   tokko_refrescar_al_terminar()).
 *
 * La clave va en config.local.php: define('TOKKO_API_KEY', '…'); nunca llega al navegador.
 */

declare(strict_types=1);

const TOKKO_API = 'https://www.tokkobroker.com/api/v1/';
const TOKKO_VIGENCIA = 3 * 3600;          // la copia local se renueva cada 3 h
const TOKKO_ARCHIVO = 'tokko/propiedades.json';
const TOKKO_FOTOS_DESTACADO = 1;           // en destacados solo la portada (la futura /propiedades usará todas)
const TOKKO_TAMANOS_DESTACADO = ['card' => [720, 540, 'recortar'], 'full' => [1400, 1050, 'ajustar']];
const TOKKO_TAMANO_PORTADA = ['card' => [720, 540, 'recortar']];   // portada de cada propiedad en /propiedades

/** Nombres de Tokko (en español de Argentina) → como se dicen en México. */
const TOKKO_TRADUCCIONES = [
    'Cloaca' => 'Drenaje', 'Agua Corriente' => 'Agua', 'Pileta' => 'Alberca', 'Pileta Cubierta' => 'Alberca techada',
    'Piletero' => 'Mantenimiento de alberca', 'Amoblado' => 'Amueblado', 'Quincho' => 'Área de asador', 'Parrilla' => 'Asador',
    'Living comedor' => 'Sala comedor', 'Baulera' => 'Bodega', 'Dependencia' => 'Cuarto de servicio', 'Encargado' => 'Conserje',
    'Cochera subterránea' => 'Estacionamiento subterráneo', 'Garagistas' => 'Valet parking', 'Toilette' => 'Medio baño',
    'Seguridad 24hs' => 'Seguridad 24 h', 'Armarios Empotrados' => 'Clósets', 'SUM' => 'Salón de usos múltiples',
    'Luminoso' => 'Iluminado', 'Hall' => 'Recibidor', 'Escritorio' => 'Estudio', 'Lavadero' => 'Cuarto de lavado',
    'Comedor diario' => 'Desayunador', 'Celular' => 'Señal de celular', 'Energia trifasica' => 'Energía trifásica',
    'Accesibilidad C/ Movilidad Reducida' => 'Accesible para movilidad reducida', 'Aire Acondicionado individual' => 'Aire acondicionado',
    'Barrio privado' => 'Fraccionamiento privado', 'Una Planta' => 'Una planta',
];

/** ¿Hay clave de Tokko configurada? */
function tokko_activo(): bool
{
    return defined('TOKKO_API_KEY') && TOKKO_API_KEY !== '';
}

/**
 * GET a la API. Devuelve el JSON decodificado o lanza RuntimeException
 * (el mensaje nunca incluye la clave).
 */
function tokko_pedir(string $ruta, array $parametros = []): array
{
    if (!tokko_activo()) {
        throw new RuntimeException('Falta TOKKO_API_KEY en config.local.php.');
    }
    $url = TOKKO_API . $ruta . '?' . http_build_query($parametros + ['key' => TOKKO_API_KEY, 'format' => 'json', 'lang' => 'es_ar']);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $respuesta = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);
    if (!is_string($respuesta) || $codigo !== 200) {
        throw new RuntimeException('Tokko respondió ' . ($codigo ?: 'sin conexión') . ($errorCurl ? ' (' . $errorCurl . ')' : '') . '.');
    }
    $json = json_decode($respuesta, true);
    if (!is_array($json)) {
        throw new RuntimeException('Tokko devolvió una respuesta que no es JSON.');
    }
    return $json;
}

// =============================================================================
// Copia local de las propiedades
// =============================================================================

/** Propiedades guardadas en data/tokko/propiedades.json (sin llamar a Tokko). */
function tokko_propiedades(): array
{
    $datos = leer_json(TOKKO_ARCHIVO, ['propiedades' => []]);
    return is_array($datos['propiedades'] ?? null) ? $datos['propiedades'] : [];
}

/** Una propiedad de la copia local por su id de Tokko. */
function tokko_propiedad(int $id): ?array
{
    foreach (tokko_propiedades() as $p) {
        if ((int) ($p['id'] ?? 0) === $id) {
            return $p;
        }
    }
    return null;
}

/** Fecha (timestamp) de la última sincronización correcta; 0 si nunca. */
function tokko_ultima_sincronizacion(): int
{
    $ruta = DIR_DATA . '/' . TOKKO_ARCHIVO;
    return is_file($ruta) ? (int) filemtime($ruta) : 0;
}

/**
 * Descarga todas las propiedades activas de Tokko y reescribe la copia local.
 * Si algo falla, la copia anterior se queda como estaba. Devuelve cuántas hay.
 */
function tokko_sincronizar(): int
{
    $propiedades = [];
    $limite = 50;
    for ($offset = 0; $offset < 5000; $offset += $limite) {
        $pagina = tokko_pedir('property/', ['limit' => $limite, 'offset' => $offset]);
        foreach ($pagina['objects'] ?? [] as $p) {
            if (is_array($p) && ($n = tokko_normalizar($p))) {
                $propiedades[] = $n;
            }
        }
        $total = (int) ($pagina['meta']['total_count'] ?? 0);
        if ($offset + $limite >= $total || empty($pagina['objects'])) {
            break;
        }
    }

    $ruta = DIR_DATA . '/' . TOKKO_ARCHIVO;
    if (!is_dir(dirname($ruta))) {
        mkdir(dirname($ruta), 0755, true);
    }
    $json = json_encode(['actualizado' => date('c'), 'propiedades' => $propiedades],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $tmp = $ruta . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, $json) !== strlen($json) || !rename($tmp, $ruta)) {
        @unlink($tmp);
        throw new RuntimeException('No se pudo guardar data/' . TOKKO_ARCHIVO . '.');
    }
    clearstatcache(true, $ruta);
    return count($propiedades);
}

/** "Casa nueva en Venta en León" → "casa-nueva-en-venta-en-leon" (máx. 70 caracteres). */
function tokko_slug(string $texto): string
{
    $texto = mb_strtolower(tokko_texto($texto));
    $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    $texto = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');
    if (strlen($texto) > 70) {
        // Corta en el último guion para no dejar palabras a medias
        $corto = substr($texto, 0, 71);
        $texto = substr($corto, 0, (int) strrpos($corto, '-')) ?: substr($texto, 0, 70);
    }
    $texto = trim($texto, '-');
    return $texto !== '' ? $texto : 'propiedad';
}

/** URL de la ficha propia de una propiedad. */
function url_propiedad(array $prop): string
{
    return '/propiedades/' . $prop['slug'];
}

/** Texto limpio: sin espacios dobles, tabuladores ni saltos de línea. */
function tokko_texto(mixed $valor): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) $valor));
}

/**
 * Convierte una propiedad de la API al formato propio, con SOLO datos públicos.
 * Devuelve null si no tiene lo mínimo (id, título y una operación con precio).
 */
function tokko_normalizar(array $p): ?array
{
    $id = (int) ($p['id'] ?? 0);
    // En Tokko el título que el equipo escribe es la «dirección pública» (fake_address); es lo que
    // muestran el panel y el sitio de Tokko. publication_title lo arma Tokko solo («Casa en León»).
    // Si en la dirección pública escribieron una dirección real (C.P. o «km»), se usa el automático.
    $tituloTokko = tokko_texto($p['publication_title'] ?? '');
    $titulo = tokko_texto($p['fake_address'] ?? '');
    if ($titulo === '' || preg_match('/\b\d{5}\b|\bkm\b/iu', $titulo)) {
        $titulo = $tituloTokko;
    }
    if ($id <= 0 || $titulo === '') {
        return null;
    }

    $operaciones = [];
    foreach ($p['operations'] ?? [] as $op) {
        $precio = $op['prices'][0] ?? null;
        $tipo = match (mb_strtolower((string) ($op['operation_type'] ?? ''))) {
            'venta' => 'venta',
            'alquiler', 'alquiler temporario', 'renta' => 'renta',
            default => null,
        };
        if ($tipo && is_array($precio)) {
            $operaciones[] = [
                'tipo'   => $tipo,
                'precio' => is_numeric($precio['price'] ?? null) ? $precio['price'] + 0 : 0,
                'moneda' => in_array($precio['currency'] ?? '', ['MXN', 'USD'], true) ? $precio['currency'] : 'MXN',
            ];
        }
    }
    if (!$operaciones) {
        return null;
    }

    // Fotos en el orden de Tokko, con la portada primero; los planos van aparte.
    $fotos = [];
    foreach ($p['photos'] ?? [] as $f) {
        // «image» es la versión de Tokko a 1200 px; la original puede pesar 7 MB y pasar de 50 MP.
        $url = (string) ($f['image'] ?? '') ?: (string) ($f['original'] ?? '');
        if (!str_starts_with($url, 'https://')) {
            continue;
        }
        $fotos[] = [
            'url'     => $url,
            'mini'    => (string) ($f['thumb'] ?? ''),
            'plano'   => !empty($f['is_blueprint']),
            'portada' => !empty($f['is_front_cover']),
            'orden'   => (int) ($f['order'] ?? 0),
        ];
    }
    usort($fotos, fn($a, $b) => [$b['portada'], $a['orden']] <=> [$a['portada'], $b['orden']]);

    $videos = [];
    foreach ($p['videos'] ?? [] as $v) {
        if (!empty($v['url']) && str_starts_with((string) $v['url'], 'https://')) {
            $videos[] = ['proveedor' => (string) ($v['provider'] ?? ''), 'url' => (string) $v['url'], 'titulo' => tokko_texto($v['title'] ?? '')];
        }
    }

    // "Guanajuato | León | Fracc. X" → "Fracc. X, León"
    $partes = array_values(array_filter(array_map('trim', explode('|', (string) ($p['location']['short_location'] ?? ''))),
        fn($x) => $x !== '' && !in_array(mb_strtolower($x), ['méxico', 'mexico'], true)));
    // "Guanajuato | San Felipe | San Felipe" → sin el nombre repetido
    $partes = array_values(array_filter($partes, fn($x, $i) => $i === 0 || mb_strtolower($x) !== mb_strtolower($partes[$i - 1]), ARRAY_FILTER_USE_BOTH));
    $lat = (float) ($p['geo_lat'] ?? 0);
    $lng = (float) ($p['geo_long'] ?? 0);
    // Solo si parecen coordenadas válidas de México (Tokko a veces deja 0 o vacío)
    $zonaGeo = ($lat > 14 && $lat < 33 && $lng < -86 && $lng > -119) ? [round($lat, 2), round($lng, 2)] : null;
    $ubicacion = match (true) {
        count($partes) >= 3 => $partes[count($partes) - 1] . ', ' . $partes[count($partes) - 2],
        count($partes) === 2 => $partes[1] . ', ' . $partes[0],
        default => $partes[0] ?? '',
    };

    $tipo = tokko_texto($p['type']['name'] ?? '');
    $tipo = ['Depósito' => 'Bodega', 'Galpón' => 'Bodega', 'PH' => 'Departamento'][$tipo] ?? $tipo;

    $numero = fn($v) => is_numeric($v) && (float) $v > 0 ? (float) $v + 0 : null;

    // Amenidades agrupadas como en Tokko: 1 = servicios, 2 = espacios, 3 = adicionales
    $grupos = [1 => 'servicios', 2 => 'espacios', 3 => 'adicionales'];
    $amenidades = ['servicios' => [], 'espacios' => [], 'adicionales' => []];
    foreach ($p['tags'] ?? [] as $t) {
        $nombre = tokko_texto($t['name'] ?? '');
        if ($nombre !== '') {
            $amenidades[$grupos[(int) ($t['type'] ?? 3)] ?? 'adicionales'][] = TOKKO_TRADUCCIONES[$nombre] ?? $nombre;
        }
    }

    // Superficies: en esta cuenta «construido» va en total_surface (o roofed_surface) y el terreno en surface
    $construccion = $numero($p['total_surface'] ?? null) ?? $numero($p['roofed_surface'] ?? null);
    $terreno = $numero($p['surface'] ?? null);
    if (in_array($tipo, ['Terreno', 'Campo', 'Quinta'], true)) {
        $terreno ??= $construccion;
        $construccion = null;
    }

    return [
        'id'             => $id,
        'codigo'         => tokko_texto($p['reference_code'] ?? ''),
        'titulo'         => $titulo,
        'titulo_tokko'   => $tituloTokko,
        'tipo'           => $tipo,
        'operaciones'    => $operaciones,
        'precio_visible' => ($p['web_price'] ?? true) !== false,
        'ubicacion'      => $ubicacion,
        'ubicacion_completa' => implode(', ', array_reverse($partes)),
        'ciudad'         => count($partes) >= 2 ? $partes[1] : ($partes[0] ?? ''),
        'zona'           => count($partes) >= 3 ? $partes[count($partes) - 1] : '',
        // Centro aproximado de la zona: coordenadas redondeadas a 2 decimales (~1 km).
        // Nunca se guarda el punto exacto de Tokko.
        'zona_geo'       => $zonaGeo,
        'slug'           => tokko_slug($titulo) . '-' . $id,
        'descripcion'    => trim(str_replace("\r\n", "\n", (string) ($p['description'] ?? ''))),
        'recamaras'      => $numero($p['suite_amount'] ?? null) ?? $numero($p['room_amount'] ?? null),
        'banos'          => $numero($p['bathroom_amount'] ?? null),
        'medios_banos'   => $numero($p['toilet_amount'] ?? null),
        'estacionamientos' => $numero($p['parking_lot_amount'] ?? null),
        'construccion_m2'  => $construccion,
        'terreno_m2'       => $terreno,
        'frente_m'       => $numero($p['front_measure'] ?? null),
        'fondo_m'        => $numero($p['depth_measure'] ?? null),
        'plantas'        => $numero($p['floors_amount'] ?? null),
        'condicion'      => in_array($p['property_condition'] ?? '', ['', '---'], true) ? '' : tokko_texto($p['property_condition']),
        'antiguedad'     => is_numeric($p['age'] ?? null) ? (int) $p['age'] : null,
        'amenidades'     => $amenidades,
        'fotos'          => $fotos,
        'videos'         => $videos,
        'destacada_tokko' => !empty($p['is_starred_on_web']),
        'creada'         => (string) ($p['created_at'] ?? ''),
        'url_tokko'      => rtrim(URL_TOKKO, '/') . '/p/' . $id . '-prop',
    ];
}

// =============================================================================
// Destacados alimentados por Tokko
// =============================================================================

/**
 * Descripción corta para la tarjeta: las primeras líneas de la descripción
 * (sin repetir el título) hasta juntar unos 140 caracteres, recortada a 220.
 */
function tokko_descripcion_corta(string $texto, string $titulo = ''): string
{
    $comparable = fn(string $t) => trim(mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $t)));
    $resumen = '';
    foreach (preg_split('/\n/u', $texto) ?: [] as $linea) {
        $linea = tokko_texto($linea);
        if ($linea === '' || $comparable($linea) === $comparable($titulo)) {
            continue;
        }
        $resumen .= ($resumen === '' ? '' : (preg_match('/[.:;!?]$/u', $resumen) ? ' ' : '. ')) . $linea;
        if (mb_strlen($resumen) >= 140) {
            break;
        }
    }
    // En Tokko a veces falta el espacio después del punto: "Valenciana.Se ubica"
    $resumen = (string) preg_replace('/(\p{Ll})\.(\p{Lu})/u', '$1. $2', $resumen);
    return extracto($resumen, 220);
}

/**
 * Copia (o reutiliza) las fotos de una propiedad en uploads/destacados/ como WebP.
 * El nombre lleva un hash de la URL de Tokko: si la foto no cambió, no se vuelve a descargar.
 */
function tokko_fotos_destacado(array $prop): array
{
    $fuentes = array_slice(array_values(array_filter($prop['fotos'] ?? [], fn($f) => empty($f['plano']))), 0, TOKKO_FOTOS_DESTACADO);
    $rutas = [];
    foreach ($fuentes as $i => $f) {
        $base = 'tokko-' . (int) $prop['id'] . '-' . substr(md5((string) $f['url']), 0, 10);
        $existentes = [];
        foreach (TOKKO_TAMANOS_DESTACADO as $clave => $_) {
            $existentes[$clave] = 'uploads/destacados/' . $base . '-' . $clave . '.webp';
        }
        if (array_filter($existentes, fn($r) => is_file(RAIZ_WEB . '/' . $r)) === $existentes) {
            touch(RAIZ_WEB . '/' . $existentes['card']);   // sigue en uso
            $rutas[] = $existentes;
            continue;
        }
        try {
            $rutas[] = tokko_descargar_foto((string) $f['url'], $base);
        } catch (Throwable $ex) {
            error_log('[duan] Tokko: no se pudo copiar una foto de la propiedad ' . $prop['id'] . ': ' . $ex->getMessage());
        }
    }
    return $rutas;
}

/** Descarga una foto de Tokko (solo static.tokkobroker.com) y la guarda en los tamaños de destacados. */
function tokko_descargar_foto(string $url, string $base, string $carpeta = 'destacados', array $tamanos = TOKKO_TAMANOS_DESTACADO): array
{
    if (!preg_match('#^https://static\.tokkobroker\.com/#', $url)) {
        throw new RuntimeException('URL de foto no permitida.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'tokko');
    $fp = fopen($tmp, 'wb');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_MAXFILESIZE    => IMAGEN_MAX_MB * 1024 * 1024,
    ]);
    $ok = curl_exec($ch);
    $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);
    try {
        if (!$ok || $codigo !== 200) {
            throw new RuntimeException('HTTP ' . $codigo);
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $info = @getimagesize($tmp);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || !$info || $info[0] * $info[1] > IMAGEN_MAX_PIXELES) {
            throw new RuntimeException('no es una imagen válida');
        }
        @ini_set('memory_limit', '512M');
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
        };
        if (!$img) {
            throw new RuntimeException('GD no pudo abrirla');
        }
        if (!imageistruecolor($img)) {
            imagepalettetotruecolor($img);
        }
        try {
            return guardar_tamanos($img, $carpeta, $base, $tamanos);
        } finally {
            imagedestroy($img);
        }
    } finally {
        @unlink($tmp);
    }
}

/**
 * Datos de la tarjeta de destacado a partir de una propiedad de Tokko.
 * Conserva id y activo del elemento; todo lo demás viene de Tokko.
 */
function tokko_item_destacado(array $prop, array $actual = []): array
{
    $op = $prop['operaciones'][0];
    $item = [
        'id'             => $actual['id'] ?? 'tokko-' . $prop['id'],
        'activo'         => $actual['activo'] ?? true,
        'tokko_id'       => (int) $prop['id'],
        'operacion'      => $op['tipo'],
        'titulo'         => mb_substr($prop['titulo'], 0, 120),
        'tipo'           => $prop['tipo'],
        'ubicacion'      => $prop['ubicacion'],
        'precio'         => $op['precio'],
        'moneda'         => $op['moneda'],
        'precio_visible' => $prop['precio_visible'] && $op['precio'] > 0,
        'descripcion'    => tokko_descripcion_corta($prop['descripcion'], $prop['titulo']),
        'url_tokko'      => $prop['url_tokko'],
        'url_ficha'      => url_propiedad($prop),
        'fotos'          => tokko_fotos_destacado($prop),
    ];
    if (!$item['fotos']) {
        $item['fotos'] = $actual['fotos'] ?? [];   // si Tokko no responde, conserva las fotos que ya tenía
    }
    return $item;
}

/** Id de Tokko de un destacado: el campo tokko_id o, en los creados a mano, el de su liga /p/{id}-. */
function tokko_id_de_destacado(array $item): int
{
    if (!empty($item['tokko_id'])) {
        return (int) $item['tokko_id'];
    }
    return preg_match('#/p/(\d+)-#', (string) ($item['url_tokko'] ?? ''), $m) ? (int) $m[1] : 0;
}

/**
 * Pasa los datos de la copia local a destacados.json.
 * - Las propiedades que ya no están en Tokko (vendidas, rentadas o pausadas) se ocultan.
 * - Los destacados creados a mano antes de la integración se enlazan a Tokko por su liga.
 * Solo escribe (y genera respaldo) si algo cambió. Devuelve cuántos elementos cambiaron.
 */
function tokko_actualizar_destacados(): int
{
    $propiedades = array_column(tokko_propiedades(), null, 'id');
    if (!$propiedades) {
        return 0;   // sin copia local no se toca nada
    }
    $actual = leer_para_editar(archivo_de('destacados'));
    $items = $actual['items'] ?? [];
    $cambios = 0;
    $nuevos = [];
    foreach ($items as $item) {
        $id = tokko_id_de_destacado($item);
        if ($id && isset($propiedades[$id])) {
            $nuevo = tokko_item_destacado($propiedades[$id], $item);
            unset($nuevo['tokko_no_disponible']);
        } elseif (!empty($item['tokko_id'])) {
            $nuevo = $item;
            $nuevo['activo'] = false;
            $nuevo['tokko_no_disponible'] ??= date('Y-m-d');
        } else {
            $nuevo = $item;   // creado a mano y sin propiedad en Tokko: se queda igual
        }
        if ($nuevo !== $item) {
            $cambios++;
        }
        $nuevos[] = $nuevo;
    }
    if ($cambios) {
        modificar_json(archivo_de('destacados'), null, function (?array $datos) use ($nuevos) {
            $datos ??= [];
            $datos['items'] = $nuevos;
            $datos['actualizado'] = ahora_iso();
            return $datos;
        });
        limpiar_uploads('destacados');
    }
    return $cambios;
}

// =============================================================================
// Portadas del listado /propiedades
// =============================================================================

/**
 * Copia la portada de cada propiedad a uploads/propiedades/ (WebP 720×540) y borra
 * las de propiedades que ya no están o cuya portada cambió. Solo baja las nuevas.
 * Devuelve [id => ruta] y lo guarda en data/tokko/portadas.json.
 */
function tokko_copiar_portadas(): array
{
    $portadas = [];
    $usadas = [];
    foreach (tokko_propiedades() as $prop) {
        $foto = array_values(array_filter($prop['fotos'] ?? [], fn($f) => empty($f['plano'])))[0] ?? null;
        if (!$foto) {
            continue;
        }
        $base = 'tokko-' . (int) $prop['id'] . '-' . substr(md5((string) $foto['url']), 0, 10);
        $ruta = 'uploads/propiedades/' . $base . '-card.webp';
        if (!is_file(RAIZ_WEB . '/' . $ruta)) {
            try {
                $ruta = tokko_descargar_foto((string) $foto['url'], $base, 'propiedades', TOKKO_TAMANO_PORTADA)['card'];
            } catch (Throwable $ex) {
                error_log('[duan] Tokko: no se pudo copiar la portada de ' . $prop['id'] . ': ' . $ex->getMessage());
                continue;
            }
        }
        $portadas[(int) $prop['id']] = $ruta;
        $usadas[basename($ruta)] = true;
    }
    foreach (glob(DIR_UPLOADS . '/propiedades/tokko-*.webp') ?: [] as $archivo) {
        if (!isset($usadas[basename($archivo)])) {
            @unlink($archivo);
        }
    }
    $json = json_encode($portadas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    file_put_contents(DIR_DATA . '/tokko/portadas.json', $json, LOCK_EX);
    return $portadas;
}

/** Ruta de la portada local de una propiedad (o null si aún no se copia). */
function portada_propiedad(array $prop): ?string
{
    static $portadas = null;
    $portadas ??= leer_json('tokko/portadas.json', []);
    $ruta = $portadas[(int) $prop['id']] ?? null;
    return is_string($ruta) ? ruta_upload($ruta) : null;
}

/** Carga las funciones del panel que usa la sincronización (respaldos, imágenes). */
function tokko_cargar_dependencias(): void
{
    require_once __DIR__ . '/admin/almacen.php';
    require_once __DIR__ . '/admin/imagenes.php';
}

/**
 * Sincroniza todo (propiedades + destacados) con un candado para que dos
 * visitas no lo hagan al mismo tiempo. Devuelve false si otra ya estaba en curso.
 */
function tokko_sincronizar_todo(): bool
{
    tokko_cargar_dependencias();
    $dir = DIR_DATA . '/tokko';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $candado = fopen($dir . '/.sincronizando', 'c');
    if (!$candado || !flock($candado, LOCK_EX | LOCK_NB)) {
        return false;
    }
    try {
        @set_time_limit(300);
        tokko_sincronizar();
        tokko_actualizar_destacados();
        tokko_copiar_portadas();
        return true;
    } finally {
        flock($candado, LOCK_UN);
        fclose($candado);
    }
}

/**
 * Si la copia local está vencida, la renueva DESPUÉS de mandar la página al
 * visitante (fastcgi_finish_request en SiteGround), así nadie espera a Tokko.
 * Los errores solo se registran en el log; el sitio sigue con la copia anterior.
 */
function tokko_refrescar_al_terminar(): void
{
    if (!tokko_activo() || time() - tokko_ultima_sincronizacion() < TOKKO_VIGENCIA) {
        return;
    }
    // Evita reintentar en cada visita si Tokko está caído: un intento cada 15 min.
    $marca = DIR_DATA . '/tokko/.ultimo-intento';
    if (is_file($marca) && time() - (int) filemtime($marca) < 900) {
        return;
    }
    register_shutdown_function(function () use ($marca): void {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        @ignore_user_abort(true);
        @mkdir(dirname($marca), 0755, true);
        @touch($marca);
        try {
            tokko_sincronizar_todo();
        } catch (Throwable $ex) {
            error_log('[duan] Tokko: sincronización automática fallida: ' . $ex->getMessage());
        }
    });
}

// =============================================================================
// Página /propiedades: filtros y datos para mostrar
// =============================================================================

const PROPIEDADES_POR_PAGINA = 12;
const RECAMARAS_FILTRO = [1, 2, 3, 4];

/** Lee y limpia los filtros del listado desde $_GET (todo opcional). */
function filtros_propiedades(array $get): array
{
    $texto = fn($k, $max = 80) => mb_substr(tokko_texto(is_string($get[$k] ?? null) ? $get[$k] : ''), 0, $max);
    $entero = fn($k) => is_numeric($get[$k] ?? null) && (int) $get[$k] > 0 ? (int) $get[$k] : null;
    $operacion = $texto('operacion');
    $orden = $texto('orden');
    return [
        'q'         => $texto('q'),
        'operacion' => in_array($operacion, ['venta', 'renta'], true) ? $operacion : '',
        'tipo'      => $texto('tipo', 40),
        'ciudad'    => $texto('ciudad', 60),
        'recamaras' => in_array($entero('recamaras'), RECAMARAS_FILTRO, true) ? $entero('recamaras') : null,
        'min'       => $entero('min'),
        'max'       => $entero('max'),
        'orden'     => in_array($orden, ['recientes', 'precio-asc', 'precio-desc'], true) ? $orden : 'recientes',
    ];
}

/** ¿Hay algún filtro aplicado (además del orden)? */
function hay_filtros(array $f): bool
{
    return $f['q'] !== '' || $f['operacion'] !== '' || $f['tipo'] !== '' || $f['ciudad'] !== ''
        || $f['recamaras'] || $f['min'] || $f['max'];
}

/** Texto sin acentos y en minúsculas, para buscar. */
function texto_busqueda(string $t): string
{
    return strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
}

/** Operación que se muestra: la elegida en el filtro o la primera de la propiedad. */
function operacion_principal(array $prop, string $preferida = ''): array
{
    foreach ($prop['operaciones'] as $op) {
        if ($op['tipo'] === $preferida) {
            return $op;
        }
    }
    return $prop['operaciones'][0];
}

/** Aplica filtros y orden a la lista de propiedades. */
function filtrar_propiedades(array $props, array $f): array
{
    $palabras = $f['q'] !== '' ? preg_split('/\s+/', texto_busqueda($f['q'])) : [];
    $props = array_values(array_filter($props, function (array $p) use ($f, $palabras) {
        if ($f['operacion'] !== '' && !in_array($f['operacion'], array_column($p['operaciones'], 'tipo'), true)) {
            return false;
        }
        if ($f['tipo'] !== '' && $p['tipo'] !== $f['tipo']) {
            return false;
        }
        if ($f['ciudad'] !== '' && $p['ciudad'] !== $f['ciudad']) {
            return false;
        }
        if ($f['recamaras'] && (int) ($p['recamaras'] ?? 0) < $f['recamaras']) {
            return false;
        }
        if ($f['min'] || $f['max']) {
            $op = operacion_principal($p, $f['operacion']);
            if (!$p['precio_visible'] || ($f['min'] && $op['precio'] < $f['min']) || ($f['max'] && $op['precio'] > $f['max'])) {
                return false;
            }
        }
        if ($palabras) {
            $texto = texto_busqueda(implode(' ', [$p['titulo'], $p['titulo_tokko'] ?? '', $p['codigo'], $p['tipo'], $p['ubicacion_completa']]));
            foreach ($palabras as $palabra) {
                if (!str_contains($texto, $palabra)) {
                    return false;
                }
            }
        }
        return true;
    }));
    $precio = fn($p) => $p['precio_visible'] ? operacion_principal($p, $f['operacion'])['precio'] : PHP_INT_MAX;
    usort($props, match ($f['orden']) {
        'precio-asc'  => fn($a, $b) => $precio($a) <=> $precio($b),
        'precio-desc' => fn($a, $b) => ($b['precio_visible'] ? $precio($b) : -1) <=> ($a['precio_visible'] ? $precio($a) : -1),
        default       => fn($a, $b) => strcmp($b['creada'], $a['creada']),
    });
    return $props;
}

/** Valores distintos de un campo (para las listas de filtros), ordenados. */
function opciones_propiedades(array $props, string $campo): array
{
    $valores = array_count_values(array_filter(array_column($props, $campo), fn($v) => is_string($v) && $v !== ''));
    ksort($valores, SORT_LOCALE_STRING);
    return $valores;
}

/** Precio para mostrar, con «/mes» en rentas. */
function precio_propiedad(array $prop, array $op): string
{
    if (!$prop['precio_visible'] || $op['precio'] <= 0) {
        return 'Precio a consultar';
    }
    return formato_precio($op['precio'], $op['moneda']) . ($op['tipo'] === 'renta' ? ' /mes' : '');
}

/** "195 m²" (sin decimales si no hacen falta). */
function formato_m2(int|float|null $valor): string
{
    if (!$valor) {
        return '';
    }
    return number_format((float) $valor, floor((float) $valor) == (float) $valor ? 0 : 1, '.', ',') . ' m²';
}

/** Datos principales de una propiedad para las tarjetas y la ficha: [icono, texto]. */
function datos_clave(array $p, bool $largo = false): array
{
    $n = fn($v) => rtrim(rtrim(number_format((float) $v, 1, '.', ''), '0'), '.');
    $datos = [];
    if ($p['construccion_m2']) {
        $datos[] = ['construccion', formato_m2($p['construccion_m2']) . ($largo ? ' de construcción' : ' constr.')];
    }
    if ($p['terreno_m2']) {
        $datos[] = ['terreno', formato_m2($p['terreno_m2']) . ($largo ? ' de terreno' : ' terreno')];
    }
    if ($p['recamaras']) {
        $datos[] = ['cama', $n($p['recamaras']) . ($p['recamaras'] == 1 ? ' recámara' : ' recámaras')];
    }
    if ($p['banos']) {
        $datos[] = ['bano', $n($p['banos']) . ($p['banos'] == 1 ? ' baño' : ' baños')
            . ($largo && $p['medios_banos'] ? ' y ' . $n($p['medios_banos']) . ($p['medios_banos'] == 1 ? ' medio' : ' medios') : '')];
    }
    if ($p['estacionamientos']) {
        $datos[] = ['auto', $n($p['estacionamientos']) . ($p['estacionamientos'] == 1 ? ' estacionamiento' : ' estacionamientos')];
    }
    return $datos;
}

/** Párrafos de la descripción de Tokko (texto plano; se escapan al imprimir). */
function parrafos_descripcion(string $texto): array
{
    $texto = (string) preg_replace('/(\p{Ll})\.(\p{Lu})/u', '$1. $2', $texto);
    return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n|\n/u', $texto) ?: []), fn($l) => $l !== ''));
}
