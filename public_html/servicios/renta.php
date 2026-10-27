<?php
/** Servicio: Renta (/servicios/renta). Contenido tomado de la ventana "RENTA+" del sitio en Wix. */
require dirname(__DIR__) . '/includes/config.php';

$pagina = [
    'titulo'      => "Renta de propiedades en León, Gto. | Du'an Bienes Raíces",
    'descripcion' => 'Perfilamos al mejor inquilino para su propiedad, verificando historial, referencias y capacidad de pago. Conozca nuestro proceso para lograr una renta exitosa.',
    'ruta'        => '/servicios/renta',
];

$servicio = [
    'clave'      => 'renta',
    'titulo'     => 'Renta',
    'lead'       => 'Perfilamos al mejor inquilino para su propiedad, verificando historial, referencias y capacidad de pago, para asegurar siempre una persona o empresa confiable y solvente.',
    'imagen'     => '/assets/img/asesor-llaves.webp',
    'imagen_alt' => 'Asesor inmobiliario entregando las llaves de una propiedad',
    'pregunta'   => '¿Cuál es el proceso para rentar mi propiedad?',
    'pasos'      => [
        ['titulo' => 'Visita física y evaluación del inmueble', 'texto' => [
            'Hacemos una visita física a la propiedad que se desea rentar, para revisar las condiciones en las que se encuentra el inmueble.',
            'De ser necesario, se le darán recomendaciones de adecuaciones y/o mantenimiento que se consideren convenientes para hacerla más atractiva al inquilino ideal.',
        ]],
        ['titulo' => 'Revisión de la documentación', 'texto' => [
            'Usted nos facilita la documentación de la propiedad para revisar que todo se encuentre legalmente en regla o, en su caso, determinar si es necesario realizar algún trámite o proceso legal adicional.',
            'Así podemos brindar seguridad jurídica y confianza al futuro inquilino, asegurando que el inmueble está debidamente regularizado.',
        ]],
        ['titulo' => 'Determinación del valor de renta', 'texto' => [
            'Establecemos el valor de renta de la propiedad con base en un estudio de mercado o una opinión de valor.',
            'Es importante fijar una renta adecuada al momento de salir al mercado, ya que, generalmente, para que un inmueble se rente intervienen dos puntos clave: precio y promoción.',
        ], 'nota' => [
            'titulo' => 'Opinión de valor',
            'texto'  => 'Es un estudio comparativo de mercado en el que analizamos propiedades con características similares a la suya que se estén ofreciendo actualmente en renta o que recientemente se hayan rentado en la zona. Esta opinión de valor es GRATIS en caso de contratar nuestros servicios.',
        ]],
        ['titulo' => 'Promoción y marketing de la propiedad', 'texto' => [
            'Iniciamos la promoción de la propiedad creando contenido de alto valor referente al inmueble, como:',
        ], 'lista' => ['Tour virtual 360°', 'Video recorrido de la propiedad', 'Fotografías profesionales', 'Tomas aéreas con dron'],
           'despues' => 'Además, desarrollamos campañas de marketing digital en redes sociales y en portales especializados para encontrar al inquilino ideal para su propiedad.'],
        ['titulo' => 'Citas y oferta de arrendamiento', 'texto' => [
            'Nos encargamos de agendar citas y mostrar la propiedad a los interesados. En cuanto tenemos un prospecto de inquilino, se presenta una oferta de arrendamiento por escrito ante el propietario, con un perfil previamente calificado.',
        ]],
        ['titulo' => 'Investigación del inquilino y firma de contrato', 'texto' => [
            'Realizamos una investigación minuciosa del prospecto para verificar que se trate de una persona (o empresa) confiable y solvente.',
            'Preparamos los contratos de arrendamiento, se recaban las firmas y se lleva a cabo la entrega del inmueble.',
        ]],
    ],
    'nota'        => 'Si lo desea, nosotros podemos hacernos cargo de la administración del inmueble, estando al pendiente de la propiedad y del cobro de las rentas. Conozca el servicio de <a href="/servicios/administracion">Administración</a>.',
    'cta_titulo'  => '¿Listo para poner en renta su inmueble?',
    'cta_texto'   => 'Reciba una asesoría personalizada por WhatsApp para encontrar al inquilino ideal.',
    'cta_boton'   => 'Quiero RENTAR mi propiedad',
    'cta_mensaje' => "Hola, vi en su página web el proceso para rentar mi propiedad y me interesa recibir una asesoría.\nEstos son los datos de mi inmueble:\n\n- Tipo de propiedad (casa, departamento, local, etc.):\n- Ubicación (colonia y ciudad):\n- ¿Renta aproximada que tengo en mente?:\n- ¿La propiedad está amueblada?:\n- ¿La propiedad tiene adeudos?:\n- Nombre y teléfono de contacto:\n\n¿Me pueden apoyar para encontrar un inquilino confiable y solvente, por favor?",
];

require dirname(__DIR__) . '/includes/plantilla-servicio.php';
