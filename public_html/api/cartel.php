<?php
/**
 * Documento de un banner de código propio (sección «vitrina» del panel, tipo «codigo»), para el <iframe>
 * de components/cartel.php. Se sirve con la CSP «sandbox»: aunque alguien abra esta dirección
 * directo, el código corre en un origen aislado, sin acceso a cookies ni al resto del sitio.
 *   /api/cartel.php?id={id}
 */
require dirname(__DIR__) . '/includes/config.php';

$id = (string) ($_GET['id'] ?? '');
$item = null;
foreach (obtener_seccion('vitrina') as $b) {
    if (($b['id'] ?? '') === $id && ($b['tipo'] ?? '') === 'codigo') {
        $item = $b;
        break;
    }
}

cabeceras_cartel_codigo();
if ($item === null) {
    http_response_code(404);
    echo '<!DOCTYPE html><title>Banner no disponible</title>';
    exit;
}
header('Cache-Control: public, max-age=300');
echo cartel_documento((string) $item['codigo']);
