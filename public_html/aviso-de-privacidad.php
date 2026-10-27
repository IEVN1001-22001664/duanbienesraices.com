<?php
/**
 * Aviso de privacidad (/aviso-de-privacidad). Texto tomado del sitio en Wix (revisión enero 2024).
 * Se escribe en Markdown y se pinta con markdown() para mantenerlo legible aquí.
 */
require __DIR__ . '/includes/config.php';

$pagina = [
    'titulo'      => "Aviso de Privacidad | Du'an Bienes Raíces",
    'descripcion' => "Aviso de privacidad de Du'an Bienes Raíces conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.",
    'ruta'        => '/aviso-de-privacidad',
];

$texto = <<<'MD'
La protección de sus datos personales es muy importante para **Du’an Bienes Raíces**, razón por la cual este AVISO DE PRIVACIDAD, elaborado para dar cumplimiento a la LEY FEDERAL DE PROTECCIÓN DE DATOS PERSONALES EN POSESIÓN DE LOS PARTICULARES, tiene como fin informarle el tipo de datos personales que recabamos de usted, cómo los usamos, manejamos, aprovechamos y archivamos a través de medios electrónicos y físicos, y con quién los compartimos.

## ¿Qué datos personales recabamos de usted?

Como cliente de alguno de nuestros servicios le podemos solicitar información personal, que varía según el caso, relativa a la identificación del cliente:

- Su nombre, dirección tanto personal como de trabajo, fecha de nacimiento.
- Su correo electrónico y número telefónico.
- Sus datos patrimoniales como cuentas bancarias, créditos, bienes muebles e inmuebles, activos, pasivos, entre otros.
- Información sobre su cónyuge, herederos, legatarios y beneficiarios.
- Comprobantes oficiales que acrediten su identidad y la información que usted declara, así como su CURP.

## ¿Para qué usamos sus datos personales?

**Du’an Bienes Raíces** recaba y usa sus datos personales para proveer los servicios que ha solicitado y dar cumplimiento a las siguientes finalidades:

- Dar cumplimiento a la legislación aplicable.
- Confirmar su identidad con el objetivo de determinar la factibilidad de iniciar una relación comercial.
- Dar cumplimiento a las obligaciones que deriven del Contrato de Prestación de Servicios, entre otros, que celebramos con nuestros clientes.
- Entender y atender sus necesidades de carácter inmobiliario o de negocio, ya sea como propietario de un inmueble en promoción, cliente de consultoría o proveedor de Du’an Bienes Raíces.
- Otorgar seguridad y certeza jurídica a los hechos y actos que celebra, como pueden ser operaciones traslativas de dominio relativas a compraventas, donaciones, permutas, adjudicaciones por herencia, adjudicaciones por remate, fideicomisos, constituciones de regímenes de propiedad en condominio, testamentos, trámites sucesorios, hipotecas, cancelaciones de hipotecas, ratificaciones de firmas, fes de hechos, cotejo de documentos, entre otros.
- Promover algún inmueble de su propiedad.
- Brindarle asesoría legal.
- Cumplir con los requerimientos legales que le son aplicables.
- Verificar la información que nos proporciona.

## ¿Con quién compartimos su información y para qué fines?

**Du’an Bienes Raíces**, sus empresas afiliadas y/o proveedores pueden tratar sus datos personales con fines financieros, jurídicos, administrativos, publicitarios y/o estadísticos.

**Du’an Bienes Raíces** hace de su conocimiento que cuenta con mecanismos y medidas de seguridad que pretenden dar el correcto tratamiento a todos sus datos, previniendo daño, pérdida, alteración, destrucción, acceso o tratamiento no autorizado. Todas las personas involucradas en el proceso se comprometen a tratar sus datos personales de acuerdo con el alcance de este Aviso de Privacidad.

Si usted no manifiesta su oposición para que sus datos personales sean transferidos, se entenderá que ha otorgado su consentimiento para ello, en el entendido de que en cualquier momento podrá manifestar su oposición.

## ¿Cómo puede limitar el uso o divulgación de su información personal?

Usted puede limitar el uso y divulgación de su información personal a través de los siguientes medios que hemos instrumentado:

- Presentando su solicitud personalmente en nuestro domicilio, dirigida al departamento de atención a clientes, encargado de la privacidad en esta oficina.
- Enviando correo electrónico a la siguiente dirección: [info@duanbienesraices.com](mailto:info@duanbienesraices.com).
- Llamando al número telefónico 477 688 6298 o 477 551 4226.

## ¿Cómo acceder, rectificar, cancelar u oponerse al tratamiento de sus datos personales?

El ejercicio de los derechos de acceso, rectificación, cancelación y oposición, o la revocación del consentimiento, podrán efectuarse presentando solicitud por escrito en nuestro domicilio dirigida a la persona o departamento de privacidad, vía correo electrónico a [info@duanbienesraices.com](mailto:info@duanbienesraices.com) o llamando al número 477 688 6298 o 477 551 4226, en el entendido de que, una vez plasmados en un instrumento notarial, no podrá ejercer sobre ellos ninguno de los referidos derechos, pudiendo hacerlo solamente respecto de los que se conservan en la base de datos de la empresa.

## ¿Cómo conocer los cambios al presente aviso de privacidad?

El presente aviso de privacidad puede sufrir modificaciones, cambios o actualizaciones, por lo cual nos comprometemos a mantenerlo informado de tal situación a través de alguno de los siguientes medios:

- Nuestra página de internet www.duanbienesraices.com.
- Notificación a su correo electrónico.
- En la primera comunicación que tengamos con usted después del cambio.

## ¿Cómo contactarnos?

Si usted tiene alguna duda sobre el presente aviso de privacidad, puede dirigirla a:

- La dirección electrónica: [info@duanbienesraices.com](mailto:info@duanbienesraices.com)
- La dirección de correo postal dirigida al departamento de atención a clientes.
- Los teléfonos 477 688 6298 o 477 551 4226.

Asimismo, ponemos a su entera disposición copias del presente aviso de privacidad en nuestro domicilio y en nuestra página de internet www.duanbienesraices.com, sección “Aviso de Privacidad”.

*Última revisión: enero 2024.*
MD;

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<article class="seccion">
    <div class="contenedor contenedor--texto prosa">
        <h1 class="titulo-seccion titulo-seccion--izq">Aviso de Privacidad</h1>
        <?= markdown($texto) ?>
    </div>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
