<?php
/**
 * Formularios del panel: pintar cada tipo de campo y convertir lo enviado
 * (texto libre del editor) en un elemento limpio y validado.
 */

declare(strict_types=1);

// =============================================================================
// Recolectar y validar
// =============================================================================

/**
 * Arma el elemento a partir de $_POST.
 * Devuelve [elemento, errores por campo]. Con $validar = false solo limpia (para la vista previa).
 */
function recolectar(string $seccion, array $post, array $actual, bool $validar = true): array
{
    $campos = SECCIONES_ADMIN[$seccion]['campos'];
    $item = $actual;
    $errores = [];

    foreach ($campos as $nombre => $def) {
        $crudo = $post[$nombre] ?? null;
        [$valor, $error] = limpiar_campo($seccion, $nombre, $def, $crudo);
        $item[$nombre] = $valor;

        if (!$validar) {
            continue;
        }
        $vacio = $valor === '' || $valor === null || $valor === [];
        if ($error) {
            $errores[$nombre] = $error;
        } elseif (!empty($def['req']) && $vacio) {
            $errores[$nombre] = match ($def['tipo']) {
                'fotos'  => 'Sube al menos una foto.',
                'imagen' => 'Sube una imagen.',
                default  => 'Este campo es obligatorio.',
            };
        }
    }
    $item['activo'] = !empty($post['activo']);

    // Reglas cruzadas
    if ($validar && $seccion === 'hero' && ($item['texto_boton'] ?? '') !== '' && ($item['enlace'] ?? '') === '') {
        $errores['enlace'] = 'Si el botón tiene texto, necesita una liga.';
    }

    // Quita los opcionales vacíos para dejar el JSON limpio
    foreach ($campos as $nombre => $def) {
        if (empty($def['req']) && ($item[$nombre] ?? '') === '') {
            unset($item[$nombre]);
        }
    }
    return [$item, $errores];
}

/** Limpia un valor según su tipo. Devuelve [valor, mensaje de error o null]. */
function limpiar_campo(string $seccion, string $nombre, array $def, mixed $crudo): array
{
    $texto = is_string($crudo) ? texto_limpio($crudo, $def['tipo'] === 'area' || $def['tipo'] === 'markdown') : '';
    $max = $def['max'] ?? null;

    switch ($def['tipo']) {
        case 'texto':
        case 'area':
        case 'markdown':
            if ($max && mb_strlen($texto) > $max) {
                return [$texto, "Máximo {$max} caracteres (llevas " . mb_strlen($texto) . ').'];
            }
            return [$texto, null];

        case 'url':
            if ($texto === '') {
                return ['', null];
            }
            // Si pegan "www.algo.com" sin https, se completa.
            if (preg_match('#^www\.#i', $texto)) {
                $texto = 'https://' . $texto;
            }
            if (!es_url_segura($texto) || str_starts_with($texto, '#')) {
                return [$texto, 'Escribe una dirección completa (https://…) o una página del sitio que empiece con /.'];
            }
            return [$texto, null];

        case 'precio':
            $numero = preg_replace('/[\s$,]|mxn|usd|pesos?/i', '', $texto);
            if ($texto === '') {
                return ['', null];
            }
            if (!is_numeric($numero) || (float) $numero < 0) {
                return [$texto, 'Escribe solo números. Ej.: 3900000'];
            }
            $numero = (float) $numero;
            return [floor($numero) == $numero ? (int) $numero : round($numero, 2), null];

        case 'opciones':
            if (!array_key_exists($texto, $def['opciones'])) {
                return ['', $texto === '' ? null : 'Elige una opción de la lista.'];
            }
            return [$nombre === 'estrellas' ? (int) $texto : $texto, null];

        case 'youtube':
            if ($texto === '') {
                return ['', null];
            }
            $id = id_youtube($texto);
            return $id ? [$id, null] : [$texto, 'No reconozco esa dirección de YouTube. Copia la liga del video y pégala aquí.'];

        case 'fecha':
            if ($texto === '') {
                return ['', null];
            }
            $f = DateTime::createFromFormat('!Y-m-d', $texto);
            return ($f && $f->format('Y-m-d') === $texto) ? [$texto, null] : [$texto, 'Fecha no válida.'];

        case 'imagen':
            if ($texto === '') {
                return ['', null];
            }
            return ruta_upload_valida($texto, $seccion, 'webp|jpe?g|png')
                ? [ltrim($texto, '/'), null]
                : ['', 'La imagen ya no existe. Vuelve a subirla.'];

        case 'video':
            if ($texto === '') {
                return ['', null];
            }
            return ruta_upload_valida($texto, $seccion, 'mp4')
                ? [ltrim($texto, '/'), null]
                : ['', 'El video ya no existe. Vuelve a subirlo.'];

        case 'fotos':
            $card = is_array($crudo['card'] ?? null) ? array_values($crudo['card']) : [];
            $full = is_array($crudo['full'] ?? null) ? array_values($crudo['full']) : [];
            $fotos = [];
            foreach ($card as $i => $c) {
                $f = (string) ($full[$i] ?? '');
                if (is_string($c) && ruta_upload_valida($c, $seccion, 'webp|jpe?g|png') && ruta_upload_valida($f, $seccion, 'webp|jpe?g|png')) {
                    $fotos[] = ['card' => ltrim($c, '/'), 'full' => ltrim($f, '/')];
                }
            }
            $maxFotos = $def['max'] ?? 12;
            if (count($fotos) > $maxFotos) {
                return [array_slice($fotos, 0, $maxFotos), "Máximo {$maxFotos} fotos. Quita " . (count($fotos) - $maxFotos) . '.'];
            }
            return [$fotos, null];
    }
    return [$texto, null];
}

/** Quita espacios sobrantes y caracteres de control. Conserva saltos de línea si se pide. */
function texto_limpio(string $texto, bool $multilinea): string
{
    $texto = str_replace(["\r\n", "\r"], "\n", $texto);
    $texto = (string) preg_replace('/[^\P{C}\n\t]/u', '', $texto);   // control chars excepto \n y \t
    if (!$multilinea) {
        $texto = (string) preg_replace('/\s+/u', ' ', $texto);
    } else {
        $texto = (string) preg_replace("/\n{3,}/", "\n\n", $texto);
    }
    return trim($texto);
}

/** Extrae el id de 11 caracteres de cualquier liga de YouTube (o el id suelto). */
function id_youtube(string $texto): ?string
{
    $texto = trim($texto);
    if (preg_match('/^[\w-]{11}$/', $texto)) {
        return $texto;
    }
    if (preg_match('#(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([\w-]{11})#i', $texto, $m)) {
        return $m[1];
    }
    return null;
}

// =============================================================================
// Pintar campos
// =============================================================================

/** Pinta todos los campos de una sección. */
function pintar_campos(string $seccion, array $item, array $errores): void
{
    foreach (SECCIONES_ADMIN[$seccion]['campos'] as $nombre => $def) {
        pintar_campo($seccion, $nombre, $def, $item[$nombre] ?? '', $errores[$nombre] ?? null);
    }
}

function pintar_campo(string $seccion, string $nombre, array $def, mixed $valor, ?string $error): void
{
    $id = 'campo-' . $nombre;
    $req = !empty($def['req']);
    $describe = trim(($def['ayuda'] ?? '' ? "{$id}-ayuda " : '') . ($error ? "{$id}-error" : ''));
    $aria = ($describe ? ' aria-describedby="' . e($describe) . '"' : '') . ($error ? ' aria-invalid="true"' : '') . ($req ? ' required' : '');
    $max = isset($def['max']) ? ' maxlength="' . (int) $def['max'] . '"' : '';
    $tipo = $def['tipo'];
    $esArchivo = in_array($tipo, ['imagen', 'fotos', 'video'], true);
    ?>
    <div class="campo campo--<?= e($tipo) ?><?= $error ? ' campo--error' : '' ?>" data-campo="<?= e($nombre) ?>">
        <?php if ($esArchivo): ?>
            <p class="campo__etiqueta" id="<?= e($id) ?>-etiqueta"><?= e($def['etiqueta']) ?><?= $req ? ' <span class="obligatorio" aria-hidden="true">*</span>' : '' ?></p>
        <?php else: ?>
            <label class="campo__etiqueta" for="<?= e($id) ?>"><?= e($def['etiqueta']) ?><?= $req ? ' <span class="obligatorio" aria-hidden="true">*</span>' : '' ?></label>
        <?php endif; ?>
        <?php if (!empty($def['ayuda'])): ?>
            <p class="campo__ayuda" id="<?= e($id) ?>-ayuda"><?= e($def['ayuda']) ?></p>
        <?php endif; ?>

        <?php switch ($tipo):
            case 'texto': ?>
                <input class="campo__control" type="text" id="<?= e($id) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>"<?= $max . $aria ?>>
                <?php break;
            case 'url': ?>
                <input class="campo__control" type="text" inputmode="url" id="<?= e($id) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" placeholder="https://"<?= $aria ?>>
                <?php break;
            case 'precio': ?>
                <input class="campo__control campo__control--corto" type="text" inputmode="decimal" id="<?= e($id) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>"<?= $aria ?> data-precio>
                <p class="campo__eco" data-eco-precio aria-live="polite"><?= is_numeric($valor) ? e(formato_precio($valor)) : '' ?></p>
                <?php break;
            case 'youtube': ?>
                <input class="campo__control" type="text" inputmode="url" id="<?= e($id) ?>" name="<?= e($nombre) ?>"
                       value="<?= e($valor !== '' && preg_match('/^[\w-]{11}$/', (string) $valor) ? 'https://www.youtube.com/watch?v=' . $valor : $valor) ?>"<?= $aria ?>>
                <?php break;
            case 'fecha': ?>
                <input class="campo__control campo__control--corto" type="date" id="<?= e($id) ?>" name="<?= e($nombre) ?>" value="<?= e($valor) ?>"<?= $aria ?>>
                <?php break;
            case 'area': ?>
                <textarea class="campo__control" id="<?= e($id) ?>" name="<?= e($nombre) ?>" rows="<?= (int) ($def['filas'] ?? 4) ?>"<?= $max . $aria ?>><?= e($valor) ?></textarea>
                <?php break;
            case 'opciones': ?>
                <select class="campo__control campo__control--corto" id="<?= e($id) ?>" name="<?= e($nombre) ?>"<?= $aria ?>>
                    <?php foreach ($def['opciones'] as $clave => $texto): ?>
                        <option value="<?= e($clave) ?>"<?= (string) $valor === (string) $clave ? ' selected' : '' ?>><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php break;
            case 'imagen':
                pintar_imagen($nombre, (string) $valor, $id);
                break;
            case 'video':
                pintar_video($nombre, (string) $valor, $id);
                break;
            case 'fotos':
                pintar_fotos($nombre, is_array($valor) ? $valor : [], (int) ($def['max'] ?? 12), $id);
                break;
            case 'markdown':
                pintar_markdown($nombre, (string) $valor, $id, $aria);
                break;
        endswitch; ?>

        <?php if ($error): ?>
            <p class="campo__error" id="<?= e($id) ?>-error"><?= e($error) ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function pintar_imagen(string $nombre, string $valor, string $id): void
{
    ?>
    <div class="subida" data-imagen>
        <div class="subida__vista">
            <img src="<?= $valor !== '' ? e(ruta_upload($valor)) : '' ?>" alt="" data-vista<?= $valor === '' ? ' hidden' : '' ?>>
            <span class="subida__vacia" data-vacia<?= $valor !== '' ? ' hidden' : '' ?>>Sin imagen</span>
        </div>
        <input type="hidden" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" data-ruta>
        <div class="subida__acciones">
            <label class="boton boton--secundario">
                <span data-texto-elegir><?= $valor === '' ? 'Elegir imagen' : 'Cambiar imagen' ?></span>
                <input class="sr-only" type="file" id="<?= e($id) ?>" accept="image/jpeg,image/png,image/webp,image/gif"
                       data-subir="<?= e($nombre) ?>" aria-labelledby="<?= e($id) ?>-etiqueta">
            </label>
            <button class="boton boton--texto" type="button" data-quitar<?= $valor === '' ? ' hidden' : '' ?>>Quitar</button>
        </div>
        <p class="subida__estado" data-estado aria-live="polite"></p>
    </div>
    <?php
}

function pintar_video(string $nombre, string $valor, string $id): void
{
    ?>
    <div class="subida" data-video>
        <div class="subida__vista">
            <video src="<?= $valor !== '' ? e(ruta_upload($valor)) : '' ?>" muted controls preload="metadata" data-vista<?= $valor === '' ? ' hidden' : '' ?>></video>
            <span class="subida__vacia" data-vacia<?= $valor !== '' ? ' hidden' : '' ?>>Sin video</span>
        </div>
        <input type="hidden" name="<?= e($nombre) ?>" value="<?= e($valor) ?>" data-ruta>
        <div class="subida__acciones">
            <label class="boton boton--secundario">
                <span data-texto-elegir><?= $valor === '' ? 'Elegir video' : 'Cambiar video' ?></span>
                <input class="sr-only" type="file" id="<?= e($id) ?>" accept="video/mp4" data-subir="<?= e($nombre) ?>" aria-labelledby="<?= e($id) ?>-etiqueta">
            </label>
            <button class="boton boton--texto" type="button" data-quitar<?= $valor === '' ? ' hidden' : '' ?>>Quitar</button>
        </div>
        <p class="subida__estado" data-estado aria-live="polite"></p>
    </div>
    <?php
}

function pintar_fotos(string $nombre, array $fotos, int $max, string $id): void
{
    ?>
    <div class="fotos" data-fotos data-max="<?= $max ?>" data-nombre="<?= e($nombre) ?>">
        <ul class="fotos__lista" data-ordenable data-ordenable-local aria-labelledby="<?= e($id) ?>-etiqueta">
            <?php foreach ($fotos as $i => $foto): ?>
                <?php pintar_foto($nombre, $foto['card'], $foto['full']); ?>
            <?php endforeach; ?>
        </ul>
        <template data-plantilla-foto><?php pintar_foto($nombre, '', ''); ?></template>
        <div class="subida__acciones">
            <label class="boton boton--secundario">
                Agregar fotos
                <input class="sr-only" type="file" id="<?= e($id) ?>" accept="image/jpeg,image/png,image/webp,image/gif" multiple
                       data-subir="<?= e($nombre) ?>" aria-labelledby="<?= e($id) ?>-etiqueta">
            </label>
            <span class="fotos__cuenta" data-cuenta aria-live="polite"><?= count($fotos) ?> de <?= $max ?></span>
        </div>
        <p class="subida__estado" data-estado aria-live="polite"></p>
    </div>
    <?php
}

function pintar_foto(string $nombre, string $card, string $full): void
{
    ?>
    <li class="fotos__item" data-item>
        <img src="<?= $card !== '' ? e(ruta_upload($card)) : '' ?>" alt="" data-vista>
        <input type="hidden" name="<?= e($nombre) ?>[card][]" value="<?= e($card) ?>" data-ruta-card>
        <input type="hidden" name="<?= e($nombre) ?>[full][]" value="<?= e($full) ?>" data-ruta-full>
        <span class="fotos__portada">Portada</span>
        <div class="fotos__botones">
            <button type="button" class="mini" data-mover="-1" aria-label="Mover foto antes">◀</button>
            <button type="button" class="mini" data-mover="1" aria-label="Mover foto después">▶</button>
            <button type="button" class="mini mini--peligro" data-quitar-foto aria-label="Quitar foto">✕</button>
        </div>
    </li>
    <?php
}

function pintar_markdown(string $nombre, string $valor, string $id, string $aria): void
{
    $botones = [
        ['titulo',   'Subtítulo',  'Subtítulo (##)'],
        ['negritas', 'Negritas',   'Negritas'],
        ['cursiva',  'Cursiva',    'Cursiva'],
        ['lista',    '• Lista',    'Lista con viñetas'],
        ['numerada', '1. Lista',   'Lista numerada'],
        ['liga',     'Liga',       'Insertar liga'],
        ['cita',     'Cita',       'Cita'],
        ['linea',    'Línea',      'Línea divisoria'],
    ];
    ?>
    <div class="editor" data-editor>
        <div class="editor__barra" role="toolbar" aria-label="Formato del texto" aria-controls="<?= e($id) ?>">
            <?php foreach ($botones as [$accion, $texto, $titulo]): ?>
                <button type="button" class="mini" data-formato="<?= e($accion) ?>" title="<?= e($titulo) ?>"><?= e($texto) ?></button>
            <?php endforeach; ?>
            <label class="mini">Imagen…<input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp,image/gif" data-subir="<?= e($nombre) ?>" data-insertar></label>
            <button type="button" class="mini" data-formato="calculadora" title="Insertar la calculadora de gastos">+ Calculadora</button>
            <button type="button" class="mini" data-formato="asesoria" title="Insertar el bloque de asesoría">+ Bloque asesoría</button>
        </div>
        <textarea class="campo__control editor__texto" id="<?= e($id) ?>" name="<?= e($nombre) ?>" rows="22"<?= $aria ?>><?= e($valor) ?></textarea>
        <p class="subida__estado" data-estado aria-live="polite"></p>
        <details class="editor__ayuda">
            <summary>¿Cómo doy formato?</summary>
            <ul>
                <li>Deja una línea en blanco entre párrafos.</li>
                <li><code>## Subtítulo</code> crea un subtítulo.</li>
                <li><code>**texto**</code> se ve en <strong>negritas</strong>; <code>*texto*</code> en <em>cursiva</em>.</li>
                <li><code>- elemento</code> crea una lista; <code>1. elemento</code> una lista numerada.</li>
                <li><code>[texto](https://…)</code> crea una liga.</li>
                <li><code>[[calculadora-gastos]]</code> y <code>[[cta-asesoria]]</code>, solos en su línea, insertan esos bloques.</li>
            </ul>
        </details>
    </div>
    <?php
}
