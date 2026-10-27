<?php
/** Servicio: Administración (/servicios/administracion). Contenido de la ventana "ADMINISTRACIÓN+" de Wix. */
require dirname(__DIR__) . '/includes/config.php';

$pagina = [
    'titulo'      => "Administración de rentas en León, Gto. | Du'an Bienes Raíces",
    'descripcion' => 'Administración integral de sus rentas: cobro puntual, seguimiento a inquilinos y supervisión del estado de su propiedad, para que usted solo disfrute de sus ingresos.',
    'ruta'        => '/servicios/administracion',
];

$servicio = [
    'clave'      => 'administracion',
    'titulo'     => 'Administración',
    'lead'       => 'Nos encargamos de la administración integral de sus rentas: cobro puntual, seguimiento a inquilinos y supervisión del estado de su propiedad, para que usted solo disfrute de sus ingresos.',
    'imagen'     => '/assets/img/servicio-administracion.webp',
    'imagen_alt' => 'Propiedad administrada por Du\'an Bienes Raíces',
    'pregunta'   => '¿Cuál es el proceso para administrar mi propiedad?',
    'intro'      => [
        'El éxito de una administración satisfactoria comienza antes de rentar la propiedad.',
        'Desde la primera cita en la que se muestra el inmueble a los prospectos interesados, empezamos a calificar a cada posible inquilino para identificar si cumple o no con todos los requisitos establecidos.',
        'Una buena selección e investigación del arrendatario es la mejor garantía de una administración tranquila y exitosa.',
        'Nosotros nos encargamos de estar al pendiente de su propiedad:',
    ],
    'lista_final' => [
        'Verificamos que el inquilino cumpla con el pago puntual de la renta y demás obligaciones.',
        'Si la propiedad presenta algún detalle de mantenimiento, supervisamos que se repare adecuadamente y se le dé el seguimiento necesario.',
    ],
    'cierre'     => [
        'Al finalizar el contrato, gestionamos la entrega del inmueble, procurando que la propiedad se devuelva en las mismas condiciones en las que fue entregada, conforme a lo establecido en el contrato.',
    ],
    'cta_titulo'  => '¿Su inmueble cumple con los requisitos para ser administrado por nosotros?',
    'cta_texto'   => 'Rentas puntuales y su propiedad siempre cuidada.',
    'cta_boton'   => 'Quiero que ADMINISTREN mi propiedad',
    'cta_mensaje' => "Hola, vi en su página web el servicio de administración de rentas y me interesa que administren mi propiedad.\nEstos son los datos de mi inmueble:\n\n- Tipo de propiedad (casa, departamento, local, etc.):\n- Ubicación (colonia y ciudad):\n- ¿La propiedad está actualmente rentada? (sí/no):\n- Renta mensual actual o estimada:\n- ¿Tiene algún adeudo?:\n- Nombre y teléfono de contacto:\n\n¿Me pueden compartir información sobre el servicio de administración y los requisitos para que puedan administrar mi inmueble, por favor?",
];

require dirname(__DIR__) . '/includes/plantilla-servicio.php';
