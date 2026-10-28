<?php
/**
 * Acciones del panel (siempre POST + CSRF):
 *   activar · eliminar · ordenar · mover · subir · restaurar
 *
 * Desde JavaScript (cabecera X-Requested-With: fetch) responde JSON;
 * sin JavaScript redirige de vuelta con un aviso.
 */
require dirname(__DIR__) . '/includes/admin/nucleo.php';
requerir_login();
verificar_csrf();

$accion = (string) ($_POST['accion'] ?? '');
$s = seccion_solicitada();
$sec = SECCIONES_ADMIN[$s];
$id = (string) ($_POST['id'] ?? '');
$version = (string) ($_POST['version'] ?? '');
$volver = '/admin/seccion.php?s=' . rawurlencode($s);

/** Aplica $fn sobre el elemento $id de una sección de lista y guarda. */
function cambiar_lista(string $s, string $version, callable $fn): string
{
    return modificar_json(archivo_de($s), $version, function (?array $datos) use ($fn) {
        $datos ??= ['items' => []];
        $datos['items'] = $fn(array_values($datos['items'] ?? []));
        $datos['actualizado'] = ahora_iso();
        return $datos;
    });
}

/** Posición de un id en la lista o error. */
function posicion(array $items, string $id): int
{
    foreach ($items as $i => $item) {
        if (($item['id'] ?? null) === $id) {
            return $i;
        }
    }
    throw new ErrorPanel('Ese elemento ya no existe. Recarga la página.');
}

try {
    $respuesta = ['ok' => true];

    switch ($accion) {
        case 'activar':
            if ($s === 'blog') {
                $nueva = modificar_json(archivo_de('blog', $id), $version, function (?array $post) {
                    if (!$post) {
                        throw new ErrorPanel('Ese artículo ya no existe.');
                    }
                    $post['activo'] = ($post['activo'] ?? true) === false;
                    $post['actualizado'] = date('Y-m-d');
                    return $post;
                });
                $activo = elemento('blog', $id)['activo'];
            } else {
                $activo = null;
                $nueva = cambiar_lista($s, $version, function (array $items) use ($id, &$activo) {
                    $i = posicion($items, $id);
                    $items[$i]['activo'] = $activo = ($items[$i]['activo'] ?? true) === false;
                    return $items;
                });
            }
            $respuesta += ['activo' => $activo, 'version' => $nueva];
            $mensaje = $activo ? 'Ahora es visible en el sitio.' : 'Se ocultó del sitio (no se borró).';
            break;

        case 'eliminar':
            if ($s === 'blog') {
                $nueva = modificar_json(archivo_de('blog', $id), $version, fn() => null);
            } else {
                $nueva = cambiar_lista($s, $version, function (array $items) use ($id) {
                    array_splice($items, posicion($items, $id), 1);
                    return $items;
                });
            }
            limpiar_uploads($s);
            $respuesta += ['version' => $nueva];
            $mensaje = 'Se eliminó. Si fue un error, usa «Restaurar versión anterior».';
            break;

        case 'ordenar':   // nuevo orden completo desde arrastrar y soltar
            $ids = array_map('strval', (array) ($_POST['ids'] ?? []));
            $nueva = cambiar_lista($s, $version, function (array $items) use ($ids) {
                $porId = array_column($items, null, 'id');
                if (count($ids) !== count($items) || array_diff(array_keys($porId), $ids)) {
                    throw new ErrorPanel('La lista cambió mientras la ordenabas. Recarga la página.');
                }
                return array_map(fn($id) => $porId[$id], $ids);
            });
            $respuesta += ['version' => $nueva];
            $mensaje = 'Orden guardado.';
            break;

        case 'mover':     // flechas ▲▼ (también funcionan sin JavaScript)
            $dir = (int) ($_POST['dir'] ?? 0) < 0 ? -1 : 1;
            $nueva = cambiar_lista($s, $version, function (array $items) use ($id, $dir) {
                $i = posicion($items, $id);
                $j = $i + $dir;
                if ($j >= 0 && $j < count($items)) {
                    [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
                }
                return $items;
            });
            $respuesta += ['version' => $nueva];
            $mensaje = 'Orden guardado.';
            break;

        case 'subir':     // imagen o video; se guarda en uploads/ y el formulario recibe la ruta
            $campo = (string) ($_POST['campo'] ?? '');
            $def = $sec['campos'][$campo] ?? null;
            if (!$def || !in_array($def['tipo'], ['imagen', 'fotos', 'video', 'markdown'], true) || empty($_FILES['archivo'])) {
                throw new ErrorPanel('Subida no válida.');
            }
            $prefijo = trim((string) ($_POST['prefijo'] ?? '')) ?: $s;
            if ($def['tipo'] === 'video') {
                $respuesta['rutas'] = ['' => subir_video($_FILES['archivo'], $s, $prefijo)];
            } else {
                $respuesta['rutas'] = subir_imagen($_FILES['archivo'], $s, $prefijo, $def['tamanos']);
            }
            $respuesta['urls'] = array_map('ruta_upload', $respuesta['rutas']);
            $mensaje = 'Archivo subido.';
            break;

        case 'restaurar':
            $archivo = archivo_de($s, $s === 'blog' ? $id : null);
            restaurar_respaldo($archivo, (string) ($_POST['respaldo'] ?? ''));
            $mensaje = 'Se restauró la versión elegida. La que había antes quedó guardada como respaldo.';
            if ($s === 'blog') {
                $volver = '/admin/seccion.php?s=blog';
            }
            break;

        default:
            throw new ErrorPanel('Acción desconocida.');
    }

    if (es_peticion_fetch()) {
        responder_json($respuesta + ['mensaje' => $mensaje]);
    }
    avisar($mensaje);
} catch (ErrorPanel $ex) {
    if (es_peticion_fetch()) {
        responder_json(['ok' => false, 'error' => $ex->getMessage()], 409);
    }
    avisar($ex->getMessage(), 'error');
} catch (Throwable $ex) {
    error_log('[duan] admin/accion ' . $accion . ': ' . $ex);
    $error = 'Ocurrió un error inesperado. No se guardaron cambios.';
    if (es_peticion_fetch()) {
        responder_json(['ok' => false, 'error' => $error], 500);
    }
    avisar($error, 'error');
}
redirigir($volver);
