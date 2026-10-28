<?php
/**
 * Subida de imágenes y videos desde el panel.
 *
 * - Se comprueba el tipo REAL del archivo (finfo), no la extensión ni lo que diga el navegador.
 * - Las imágenes se vuelven a dibujar con GD y se guardan como WebP: así se descarta
 *   cualquier contenido extra escondido en el archivo original y se borran sus metadatos (GPS).
 * - Los archivos se renombran: {prefijo}-{aleatorio}-{tamaño}.webp
 */

declare(strict_types=1);

const IMAGEN_MAX_MB = 15;
const IMAGEN_MAX_PIXELES = 50_000_000;   // 50 MP: evita agotar la memoria con imágenes gigantes
const VIDEO_MAX_MB = 40;
const CALIDAD_WEBP = 80;
const TIPOS_IMAGEN = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

/** Mensaje claro para los errores de subida de PHP. */
function error_subida(int $codigo, int $maxMb): ?string
{
    return match ($codigo) {
        UPLOAD_ERR_OK => null,
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "El archivo pesa demasiado (máximo {$maxMb} MB).",
        UPLOAD_ERR_PARTIAL => 'El archivo llegó incompleto. Intenta de nuevo.',
        UPLOAD_ERR_NO_FILE => 'No se eligió ningún archivo.',
        default => 'El servidor no pudo recibir el archivo (código ' . $codigo . ').',
    };
}

/**
 * Procesa una imagen subida y la guarda en uploads/{carpeta}/ en uno o más tamaños.
 *
 * @param array $archivo  Elemento de $_FILES
 * @param array $tamanos  ['card' => [720, 540, 'recortar'], 'full' => [1400, 1050, 'ajustar']]
 *                        La clave '' significa "un solo tamaño" (sin sufijo en el nombre).
 * @return array          ['card' => 'uploads/destacados/...webp', ...]
 */
function subir_imagen(array $archivo, string $carpeta, string $prefijo, array $tamanos): array
{
    if ($error = error_subida((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE), IMAGEN_MAX_MB)) {
        throw new ErrorPanel($error);
    }
    $tmp = (string) $archivo['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new ErrorPanel('Archivo no válido.');
    }
    if (filesize($tmp) > IMAGEN_MAX_MB * 1024 * 1024) {
        throw new ErrorPanel('La imagen pesa más de ' . IMAGEN_MAX_MB . ' MB. Redúcela e intenta de nuevo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!in_array($mime, TIPOS_IMAGEN, true)) {
        $extra = in_array($mime, ['image/heic', 'image/heif'], true) ? ' Las fotos HEIC del iPhone se pueden exportar como JPG.' : '';
        throw new ErrorPanel('Formato no compatible. Usa JPG, PNG o WebP.' . $extra);
    }
    $info = @getimagesize($tmp);
    if (!$info || $info[0] < 1 || $info[1] < 1) {
        throw new ErrorPanel('El archivo no es una imagen válida.');
    }
    if ($info[0] * $info[1] > IMAGEN_MAX_PIXELES) {
        throw new ErrorPanel('La imagen es demasiado grande (más de 50 megapíxeles).');
    }

    @ini_set('memory_limit', '512M');
    $origen = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($tmp),
        'image/png'  => @imagecreatefrompng($tmp),
        'image/webp' => @imagecreatefromwebp($tmp),
        'image/gif'  => @imagecreatefromgif($tmp),
    };
    if (!$origen) {
        throw new ErrorPanel('No se pudo leer la imagen. Prueba con otro archivo.');
    }
    if ($mime === 'image/jpeg') {
        $origen = corregir_orientacion($origen, orientacion_exif($tmp));
    }
    if (!imageistruecolor($origen)) {
        imagepalettetotruecolor($origen);
    }

    $dir = DIR_UPLOADS . '/' . $carpeta;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new ErrorPanel('No se pudo crear la carpeta de imágenes.');
    }
    $base = slug($prefijo, 40) . '-' . bin2hex(random_bytes(4));
    $rutas = [];
    try {
        foreach ($tamanos as $clave => [$ancho, $alto, $modo]) {
            $img = redimensionar($origen, (int) $ancho, (int) $alto, (string) $modo);
            $nombre = $base . ($clave !== '' ? '-' . $clave : '') . '.webp';
            $ok = imagewebp($img, $dir . '/' . $nombre, CALIDAD_WEBP);
            if ($img !== $origen) {
                imagedestroy($img);
            }
            if (!$ok) {
                throw new ErrorPanel('No se pudo guardar la imagen.');
            }
            $rutas[$clave] = 'uploads/' . $carpeta . '/' . $nombre;
        }
    } catch (Throwable $ex) {
        foreach ($rutas as $r) {
            @unlink(RAIZ_WEB . '/' . $r);
        }
        throw $ex;
    } finally {
        imagedestroy($origen);
    }
    return $rutas;
}

/**
 * Escala una imagen sin agrandarla.
 * 'recortar' llena exactamente la proporción ancho:alto recortando el centro;
 * 'ajustar' la hace caber dentro de ancho × alto conservando su proporción.
 */
function redimensionar(GdImage $src, int $ancho, int $alto, string $modo): GdImage
{
    $w = imagesx($src);
    $h = imagesy($src);
    if ($modo === 'recortar') {
        $proporcion = $ancho / $alto;
        // Zona central con la proporción buscada
        if ($w / $h > $proporcion) {
            $sh = $h;
            $sw = (int) round($h * $proporcion);
        } else {
            $sw = $w;
            $sh = (int) round($w / $proporcion);
        }
        $sx = (int) (($w - $sw) / 2);
        $sy = (int) (($h - $sh) / 2);
        $escala = min(1, $ancho / $sw);
        $dw = max(1, (int) round($sw * $escala));
        $dh = max(1, (int) round($sh * $escala));
    } else {
        $sx = $sy = 0;
        $sw = $w;
        $sh = $h;
        $escala = min(1, $ancho / $w, $alto / $h);
        if ($escala >= 1) {
            return $src;
        }
        $dw = max(1, (int) round($w * $escala));
        $dh = max(1, (int) round($h * $escala));
    }
    $dst = imagecreatetruecolor($dw, $dh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
    return $dst;
}

/**
 * Lee la orientación EXIF de un JPEG sin depender de la extensión exif
 * (las fotos de celular suelen venir "acostadas" con esta marca).
 */
function orientacion_exif(string $ruta): int
{
    $fp = @fopen($ruta, 'rb');
    if (!$fp) {
        return 1;
    }
    $datos = (string) fread($fp, 131072);
    fclose($fp);
    $pos = strpos($datos, "Exif\0\0");
    if ($pos === false) {
        return 1;
    }
    $tiff = substr($datos, $pos + 6);
    $le = substr($tiff, 0, 2) === 'II';
    $u16 = fn(int $o) => strlen($tiff) >= $o + 2 ? unpack($le ? 'v' : 'n', substr($tiff, $o, 2))[1] : 0;
    $u32 = fn(int $o) => strlen($tiff) >= $o + 4 ? unpack($le ? 'V' : 'N', substr($tiff, $o, 4))[1] : 0;
    $ifd = $u32(4);
    $entradas = $u16($ifd);
    for ($i = 0; $i < $entradas && $i < 200; $i++) {
        $o = $ifd + 2 + $i * 12;
        if ($u16($o) === 0x0112) {
            $valor = $u16($o + 8);
            return $valor >= 1 && $valor <= 8 ? $valor : 1;
        }
    }
    return 1;
}

/** Gira o voltea la imagen según la orientación EXIF. */
function corregir_orientacion(GdImage $img, int $orientacion): GdImage
{
    if ($orientacion === 1) {
        return $img;
    }
    if (in_array($orientacion, [2, 4, 5, 7], true)) {
        imageflip($img, $orientacion === 4 ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);
    }
    // imagerotate gira en sentido antihorario
    $angulo = match ($orientacion) {
        3 => 180,
        6, 7 => 270,
        5, 8 => 90,
        default => 0,
    };
    if ($angulo) {
        $girada = imagerotate($img, $angulo, 0);
        if ($girada) {
            imagedestroy($img);
            return $girada;
        }
    }
    return $img;
}

/** Guarda un video MP4 subido en uploads/{carpeta}/. */
function subir_video(array $archivo, string $carpeta, string $prefijo): string
{
    if ($error = error_subida((int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE), VIDEO_MAX_MB)) {
        throw new ErrorPanel($error);
    }
    $tmp = (string) $archivo['tmp_name'];
    if (!is_uploaded_file($tmp)) {
        throw new ErrorPanel('Archivo no válido.');
    }
    if (filesize($tmp) > VIDEO_MAX_MB * 1024 * 1024) {
        throw new ErrorPanel('El video pesa más de ' . VIDEO_MAX_MB . ' MB. Comprímelo e intenta de nuevo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if ($mime !== 'video/mp4') {
        throw new ErrorPanel('El video debe ser MP4.');
    }
    $dir = DIR_UPLOADS . '/' . $carpeta;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new ErrorPanel('No se pudo crear la carpeta de videos.');
    }
    $nombre = slug($prefijo, 40) . '-' . bin2hex(random_bytes(4)) . '.mp4';
    if (!move_uploaded_file($tmp, $dir . '/' . $nombre)) {
        throw new ErrorPanel('No se pudo guardar el video.');
    }
    return 'uploads/' . $carpeta . '/' . $nombre;
}

/** true si la ruta es un archivo existente dentro de uploads/{carpeta}/. */
function ruta_upload_valida(string $ruta, string $carpeta, string $extension = 'webp|jpe?g|png|mp4'): bool
{
    $ruta = ltrim($ruta, '/');
    return (bool) preg_match('#^uploads/' . preg_quote($carpeta, '#') . '/[\w\-.]+\.(?:' . $extension . ')$#i', $ruta)
        && !str_contains($ruta, '..')
        && is_file(RAIZ_WEB . '/' . $ruta);
}
