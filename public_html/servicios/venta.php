<?php
/** Servicio: Venta (/servicios/venta). Contenido tomado de la ventana "VENTA+" del sitio en Wix. */
require dirname(__DIR__) . '/includes/config.php';

$pagina = [
    'titulo'      => "Venta de propiedades en León, Gto. | Du'an Bienes Raíces",
    'descripcion' => 'Le acompañamos de la mano durante todo el proceso de venta: valoración, preparación del inmueble, promoción y firma de escritura ante notario público.',
    'ruta'        => '/servicios/venta',
];

$servicio = [
    'clave'      => 'venta',
    'titulo'     => 'Venta',
    'lead'       => 'Le acompañamos de la mano durante todo el proceso de venta: desde la valoración y preparación del inmueble, hasta la firma de la escritura de compraventa ante notario público.',
    'imagen'     => '/assets/img/servicio-venta.webp',
    'imagen_alt' => 'Asesoría para la venta de una propiedad',
    'pregunta'   => '¿Cuál es el proceso para vender mi propiedad?',
    'pasos'      => [
        ['titulo' => 'Visita física y evaluación del inmueble', 'texto' => [
            'Realizamos una visita presencial a la propiedad para evaluar de manera detallada las condiciones físicas del inmueble. En caso de ser necesario, le sugeriremos adecuaciones, mejoras y/o trabajos de mantenimiento que contribuyan a aumentar su atractivo y valor comercial.',
        ]],
        ['titulo' => 'Revisión de la documentación', 'texto' => [
            'Usted nos facilita la documentación de la propiedad para verificar que todo se encuentre legalmente en orden y, en su caso, identificar si es necesario realizar algún trámite o proceso legal adicional.',
            'Con esto buscamos garantizar certeza jurídica y confianza al futuro comprador, asegurando que el inmueble está debidamente regularizado.',
        ]],
        ['titulo' => 'Determinar el valor comercial', 'texto' => [
            'Definimos el valor comercial de la propiedad con base en un avalúo comercial o una opinión de valor.',
            'Es fundamental fijar un precio adecuado al salir al mercado, ya que, en términos generales, para que un inmueble se venda intervienen 3 factores clave: precio, estatus legal y promoción.',
        ], 'nota' => [
            'titulo' => 'Opinión de valor',
            'texto'  => 'Es un estudio comparativo de mercado en el que analizamos propiedades con características similares a la suya, tanto las que están actualmente en venta o renta como las que recientemente se han vendido o rentado en la zona. Esta opinión de valor se ofrece GRATIS en caso de que contrate nuestros servicios.',
        ]],
        ['titulo' => 'Asesoría en el pago de impuestos', 'texto' => [
            'Le brindamos asesoría especializada en materia de ISR (Impuesto Sobre la Renta), revisando las opciones para optimizar el pago del impuesto o, cuando sea posible y conforme a la ley, aplicar una estrategia para exentarlo.',
        ]],
        ['titulo' => 'Estrategia de promoción y marketing', 'texto' => [
            'Iniciamos la promoción de la propiedad mediante la creación de contenido de alto valor, que puede incluir:',
        ], 'lista' => ['Tour virtual 360°', 'Video recorrido de la propiedad', 'Fotografías profesionales', 'Tomas aéreas con dron'],
           'despues' => 'Complementamos estos recursos con campañas de marketing digital en redes sociales y con la difusión en portales inmobiliarios especializados, con el objetivo de encontrar al comprador ideal y maximizar el alcance de la propiedad.'],
        ['titulo' => 'Gestión de citas y presentación de ofertas', 'texto' => [
            'Nos encargamos de agendar y coordinar las citas para mostrar la propiedad a posibles interesados. Cuando contamos con un prospecto comprador calificado, se presenta una oferta de compra por escrito ante el propietario, detallando las condiciones propuestas para la operación.',
        ]],
        ['titulo' => 'Cierre de operación y firma de escritura', 'texto' => [
            'Una vez aceptada la oferta, se coordina el proceso de cierre de la operación y la firma de la escritura ante Notario Público, acompañándole en cada etapa hasta la conclusión formal de la compraventa.',
        ]],
    ],
    'cta_titulo'  => '¿Listo para VENDER su inmueble?',
    'cta_texto'   => 'Solicite su opinión de valor GRATIS por WhatsApp.',
    'cta_boton'   => 'Quiero vender mi propiedad',
    'cta_mensaje' => "Hola, vi en su página web el proceso para vender mi propiedad y me interesa recibir una asesoría.\nEstos son los datos de mi inmueble:\n\n- Tipo de propiedad (casa, departamento, terreno, etc.):\n- Ubicación (colonia y ciudad):\n- ¿Precio aproximado que tengo en mente?:\n- ¿La propiedad tiene adeudos o hipoteca?:\n- Nombre y teléfono de contacto:\n\n¿Me pueden apoyar con una opinión de valor y los siguientes pasos para venderla, por favor?",
];

require dirname(__DIR__) . '/includes/plantilla-servicio.php';
