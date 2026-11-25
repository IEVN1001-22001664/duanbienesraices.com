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
/**
 * Instrucciones que el editor copia y pega en una IA (ChatGPT, Claude, Gemini…) para que le arme el
 * código de un banner que funcione en la sección «vitrina». Se muestran debajo de «Código del banner».
 * No atan el diseño a la marca Du'an (un banner puede ser de otro proyecto con su propia identidad):
 * solo fijan las reglas técnicas; los colores y tipografías de Du'an quedan como valores por defecto,
 * y la IA debe preguntarle primero al editor los datos que falten.
 */
const INSTRUCCIONES_CARTEL_IA = <<<'TXT'
Vas a diseñar el código de un banner promocional que se mostrará en la página de inicio de un sitio web inmobiliario. El banner puede ser de cualquier proyecto, desarrollo, propiedad o marca, así que su diseño (colores, tipografías y estilo) lo decide la persona que te lo pide.

ORDEN DE PRIORIDAD
1. Lo que te pida la persona (su marca, colores, tipografías, estilo, textos) manda sobre todo lo demás.
2. Las REGLAS TÉCNICAS de abajo son obligatorias siempre: sin ellas el banner no funciona en el sitio.
3. Los VALORES POR DEFECTO solo se usan si la persona no indica nada sobre ese punto.

PASO 1: ANTES DE ESCRIBIR CÓDIGO, PREGUNTA
No generes el banner todavía. Primero hazle a la persona, en un solo mensaje y con una lista numerada, las preguntas que no estén ya respondidas en «MI BANNER»:
1. ¿De qué proyecto, propiedad o marca es el banner y cuál es su objetivo? (vender, rentar, agendar visitas, dar a conocer una preventa, etc.)
2. Textos: etiqueta corta opcional (ej.: «Últimas unidades»), título y una o dos líneas de descripción.
3. Precio o condiciones (ej.: «Desde $2,320,000 MXN»), si los hay.
4. Fecha límite de la promoción, si la hay (para mostrar una cuenta regresiva).
5. Imágenes: ligas completas https://… de la foto principal y del logotipo, si los hay.
6. Identidad visual: colores (en código #hex si los tiene), tipografías y estilo (elegante, moderno, minimalista, juvenil…). Si no tiene, dile que se usarán los valores por defecto.
7. Botones (máximo dos): texto de cada uno y a dónde lleva (página del sitio, sitio externo, WhatsApp con su número, agenda en línea).
8. ¿Quiere alguna animación o efecto? ¿Tiene un ejemplo o referencia del estilo que busca?
Espera sus respuestas. Si responde «no sé» o deja algo en blanco, usa los valores por defecto o déjalo fuera; nunca inventes precios, cifras, fechas, direcciones ni datos de contacto (si falta un dato indispensable, escribe [PENDIENTE] en su lugar).

PASO 2: REGLAS TÉCNICAS (OBLIGATORIAS)
Formato
- Entrega un solo bloque de código: HTML con su <style> y, solo si hace falta, un <script> al final.
- No incluyas <html>, <head> ni <body>: el código se pega dentro de una página que ya existe.
- JavaScript sencillo, sin librerías ni frameworks (nada de jQuery, React, Tailwind ni Bootstrap) y sin scripts de otros sitios. Las tipografías sí pueden venir de Google Fonts con display=swap.
Tamaño y adaptación a pantallas
- El banner ocupa todo el ancho de un recuadro que mide unos 1150 px en computadora y unos 340 px en celular. Debe verse bien en todo ese rango: diseña primero para celular y usa @media (min-width: …) para pantallas medianas (≈ 600 px) y grandes (≈ 860 px en adelante).
- Medidas fluidas: porcentajes, fr, rem y clamp() para tamaños de texto (ej.: font-size: clamp(1.6rem, 4vw, 2.6rem)). Nada de anchos fijos en px que desborden; nada de scroll horizontal.
- La altura la da el contenido: no uses height: 100vh ni height: 100% en el contenedor principal. Recomendado: 220–500 px de alto en computadora; en celular puede crecer hacia abajo (foto arriba y texto abajo, botones a lo ancho).
- El contenedor principal lleva su propio fondo (no transparente) y box-sizing: border-box en todo.
- Textos reales en HTML (no imágenes con texto), con contraste suficiente para leerse sobre el fondo o la foto (usa un velo oscuro sobre la foto si hace falta).
- Si hay animaciones, que sean suaves y se desactiven con @media (prefers-reduced-motion: reduce).
Imágenes
- Solo imágenes publicadas en internet con dirección completa https://… (no se pueden subir archivos junto con el código).
- Usa object-fit: cover para que no se deformen, width/height o aspect-ratio para que no salten al cargar, loading="lazy" y un alt que describa la imagen.
Ligas
- Páginas del propio sitio con ruta corta, sin dominio (ej.: /propiedades, /contacto): abren en la misma ventana.
- Ligas a otros sitios, agendas en línea y WhatsApp: con target="_blank" rel="noopener".
- WhatsApp: https://wa.me/52XXXXXXXXXX?text= seguido del mensaje codificado para URL (usa el número que te dé la persona).
- Los botones son ligas <a>, de al menos 44 px de alto para poder tocarlos en celular.
Prohibido
- Formularios que pidan datos personales, cookies, localStorage, alert(), ventanas emergentes, sonidos y videos que se reproduzcan con sonido.
- Scripts de rastreo o publicidad (Google Tag Manager, Analytics, píxeles).

PASO 3: VALORES POR DEFECTO (SOLO SI LA PERSONA NO INDICA OTRA COSA)
- Colores: rojo #e21c21, rojo oscuro #b40000, carbón #282d35, carbón claro #424a56 y blanco #ffffff.
- Tipografías: títulos en Montserrat (700 u 800) y textos en Nunito Sans (400 o 600).
- Esquinas redondeadas de 16 px; botones con forma de píldora (border-radius: 999px); el principal en el color más fuerte con texto blanco y el secundario solo con borde.
- Distribución en computadora: texto a la izquierda y foto a la derecha; en celular: foto arriba y texto abajo.
- Idioma: español de México, tratando al visitante de usted.

PASO 4: ENTREGA
- Entrega el código completo en un solo bloque, listo para copiar y pegar.
- Después del código, en una o dos líneas, menciona solo lo que quedó como [PENDIENTE], si hay algo.
- Si la persona pide cambios, vuelve a entregar el código completo, no solo la parte cambiada.

MI BANNER:
(Escribe aquí lo que ya sepas de tu banner. Lo que falte, la IA te lo preguntará.)
TXT;

const SECCIONES_ADMIN = [
    'destacados' => [
        'titulo'    => 'Propiedades destacadas',
        'singular'  => 'propiedad',
        'nuevo'     => 'Agregar propiedad',
        'descripcion' => 'Las tarjetas de propiedades del inicio. Eliges cuáles y en qué orden; fotos, precio y textos se toman de Tokko.',
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
                            'ayuda' => 'Copia la dirección de la ficha. Ej.: https://propiedades.duanbienesraices.com/p/8448414-prop'],
            'fotos'     => ['tipo' => 'fotos', 'etiqueta' => 'Fotos', 'req' => true, 'max' => 12,
                            'tamanos' => ['card' => [720, 540, 'recortar'], 'full' => [1400, 1050, 'ajustar']],
                            'ayuda' => 'Hasta 12 fotos. La primera es la portada. Arrástralas para cambiar el orden.'],
        ],
    ],
    // Banners de Propiedades destacadas (inicio). 'si' => el campo solo aplica a ese tipo de banner.
    'vitrina' => [
        'titulo'    => 'Banners promocionales',
        'singular'  => 'banner',
        'nuevo'     => 'Agregar banner',
        'descripcion' => 'Los banners grandes de Propiedades destacadas en el inicio. Si hay varios visibles, rotan en un carrusel.',
        'campo_nombre' => 'nombre',
        'campo_miniatura' => 'imagen',
        'campos' => [
            'tipo'      => ['tipo' => 'opciones', 'etiqueta' => 'Tipo de banner', 'req' => true,
                            'opciones' => ['diseno' => 'Diseño Du\'an (textos, foto y botones)', 'codigo' => 'Código propio (pegar HTML)'],
                            'ayuda' => 'El diseño Du\'an es el del banner de Torre Arbide. Con código propio pegas un diseño ya hecho.'],
            'nombre'    => ['tipo' => 'texto', 'etiqueta' => 'Nombre en el panel', 'req' => true, 'max' => 80,
                            'ayuda' => 'Solo para identificarlo en esta lista. Ej.: Torre Arbide, Promoción diciembre'],
            'etiqueta'  => ['tipo' => 'texto', 'etiqueta' => 'Etiqueta roja (opcional)', 'max' => 40, 'si' => 'diseno',
                            'ayuda' => 'Texto corto arriba del título. Ej.: Últimas 7 unidades'],
            'operacion' => ['tipo' => 'opciones', 'etiqueta' => 'Etiqueta de operación', 'si' => 'diseno',
                            'opciones' => ['' => 'Sin etiqueta', 'venta' => 'Venta', 'renta' => 'Renta'],
                            'ayuda' => 'La misma etiqueta de las tarjetas de destacados, en la esquina de la foto (Venta en rojo, Renta en azul).'],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 80, 'si' => 'diseno'],
            'texto'     => ['tipo' => 'area', 'etiqueta' => 'Descripción', 'max' => 220, 'filas' => 3, 'si' => 'diseno',
                            'ayuda' => 'Una o dos líneas.'],
            'texto_celular' => ['tipo' => 'texto', 'etiqueta' => 'Descripción corta para celular (opcional)', 'max' => 120, 'si' => 'diseno',
                            'ayuda' => 'Si se deja vacía, en celular se usa la descripción completa.'],
            'precio_antes' => ['tipo' => 'texto', 'etiqueta' => 'Texto antes del precio (opcional)', 'max' => 20, 'si' => 'diseno',
                            'ayuda' => 'Ej.: Desde'],
            'precio'    => ['tipo' => 'texto', 'etiqueta' => 'Precio (opcional)', 'max' => 40, 'si' => 'diseno',
                            'ayuda' => 'Tal como se debe ver. Ej.: $2,320,000 MXN'],
            'imagen'    => ['tipo' => 'imagen', 'etiqueta' => 'Foto', 'req' => true, 'si' => 'diseno',
                            'tamanos' => ['' => [1600, 1200, 'ajustar']],
                            'ayuda' => 'Horizontal y de al menos 1200 px de ancho. En escritorio va a la derecha; en celular, arriba.'],
            'encuadre'  => ['tipo' => 'opciones', 'etiqueta' => 'Parte de la foto que se ve', 'si' => 'diseno',
                            'opciones' => ['centro' => 'El centro', 'arriba' => 'La parte de arriba', 'abajo' => 'La parte de abajo']],
            'alt'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la foto', 'max' => 150, 'si' => 'diseno',
                            'ayuda' => 'Qué se ve (lo leen Google y los lectores de pantalla).'],
            'firma'     => ['tipo' => 'texto', 'etiqueta' => 'Nombre sobre la foto (opcional)', 'max' => 30, 'si' => 'diseno',
                            'ayuda' => 'Aparece junto al logo de Du\'an sobre la foto. Ej.: Torre Arbide. Vacío = sin firma.'],
            'boton1_texto' => ['tipo' => 'texto', 'etiqueta' => 'Botón rojo: texto (opcional)', 'max' => 30, 'si' => 'diseno',
                            'ayuda' => 'Ej.: Agendar visita'],
            'boton1_url' => ['tipo' => 'url', 'etiqueta' => 'Botón rojo: liga', 'si' => 'diseno',
                            'ayuda' => 'Una liga de Cal.com se abre en la ventana de la agenda sin salir del sitio. También sirve WhatsApp (https://wa.me/…) o cualquier página.'],
            'boton2_texto' => ['tipo' => 'texto', 'etiqueta' => 'Botón blanco: texto (opcional)', 'max' => 30, 'si' => 'diseno',
                            'ayuda' => 'Ej.: Ver departamentos'],
            'boton2_url' => ['tipo' => 'url', 'etiqueta' => 'Botón blanco: liga', 'si' => 'diseno'],
            'codigo'    => ['tipo' => 'codigo', 'etiqueta' => 'Código del banner', 'req' => true, 'max' => 100000, 'si' => 'codigo',
                            'instrucciones' => INSTRUCCIONES_CARTEL_IA,
                            'ayuda' => 'Pega el HTML (puede llevar <style> y <script>). Se muestra en un marco aislado del resto de la página '
                                     . 'a todo lo ancho de la sección, y su alto se ajusta solo. Las imágenes deben estar en internet (https://…).'],
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
            'video'     => ['tipo' => 'video', 'etiqueta' => 'Video de fondo horizontal (opcional)',
                            'ayuda' => 'MP4 horizontal (1920 × 1080), máximo 40 MB. Se reproduce en computadoras y tabletas; '
                                     . 'en celular solo si pesa menos de 1 MB y no hay video vertical.'],
            'video_movil' => ['tipo' => 'video', 'etiqueta' => 'Video de fondo vertical para celular (opcional)',
                            'ayuda' => 'MP4 vertical (por ejemplo 1080 × 1920). Reemplaza al horizontal en celulares. '
                                     . 'Para que se reproduzca debe pesar menos de 1 MB (cuida los datos de quien visita); '
                                     . 'en celular se ve recortado arriba y abajo, así que deja el logo y los textos al centro.'],
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
    'resultados' => [
        'titulo'    => 'Resultados comprobados',
        'singular'  => 'resultado',
        'nuevo'     => 'Agregar resultado',
        'descripcion' => 'Propiedades vendidas y rentadas que se muestran en «Tengo una propiedad».',
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'foto',
        'campos' => [
            'estado'    => ['tipo' => 'opciones', 'etiqueta' => 'Resultado', 'req' => true,
                            'opciones' => ['vendido' => 'Vendido', 'rentado' => 'Rentado']],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 90,
                            'ayuda' => 'Ej.: Casa en Venta Hacienda Valbuena'],
            'subtitulo' => ['tipo' => 'texto', 'etiqueta' => 'Detalle (opcional)', 'max' => 60,
                            'ayuda' => 'Ej.: Privada Corralejo'],
            'foto'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto', 'req' => true,
                            'tamanos' => ['' => [800, 800, 'recortar']],
                            'ayuda' => 'Se recorta cuadrada. La cinta «Vendido» o «Rentado» se pone sola en la esquina.'],
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
    // 'unico' => true: la sección tiene un solo elemento fijo (sin agregar, eliminar ni ordenar);
    // la lista lleva directo a editarlo.
    'fundador' => [
        'titulo'    => 'Carta del fundador',
        'singular'  => 'carta del fundador',
        'nuevo'     => 'Editar carta del fundador',
        'descripcion' => 'Publicación de Instagram, frase y redes del fundador en la página Nosotros.',
        'unico'     => true,
        'campo_nombre' => 'nombre',
        'campo_miniatura' => 'foto',
        'campos' => [
            'publicacion' => ['tipo' => 'url', 'etiqueta' => 'Publicación de Instagram', 'instagram' => true,
                            'ayuda' => 'Liga de la publicación que se muestra en Nosotros (ej.: https://www.instagram.com/p/…). '
                                     . 'Si se deja vacía, se muestra la carta con la foto y el mensaje.'],
            'foto'      => ['tipo' => 'imagen', 'etiqueta' => 'Foto (para la carta)', 'req' => true,
                            'tamanos' => ['' => [900, 1194, 'recortar']],
                            'ayuda' => 'Vertical, de preferencia con fondo oscuro. Se recorta a 900 × 1194.'],
            'alt'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la foto', 'max' => 150,
                            'ayuda' => 'Ej.: Arq. Alejandro Jáquez, director y fundador de Du\'an Bienes Raíces'],
            'cita'      => ['tipo' => 'texto', 'etiqueta' => 'Frase principal', 'req' => true, 'max' => 160,
                            'ayuda' => 'La frase grande. Sin comillas: se ponen solas.'],
            'mensaje'   => ['tipo' => 'area', 'etiqueta' => 'Mensaje (para la carta)', 'req' => true, 'max' => 2000, 'filas' => 10,
                            'ayuda' => 'Solo se ve cuando no hay publicación de Instagram. Deja una línea en blanco entre párrafos.'],
            'nombre'    => ['tipo' => 'texto', 'etiqueta' => 'Nombre', 'req' => true, 'max' => 80],
            'cargo'     => ['tipo' => 'texto', 'etiqueta' => 'Cargo', 'max' => 60,
                            'ayuda' => 'Ej.: Director y fundador'],
            'facebook'  => ['tipo' => 'url', 'etiqueta' => 'Facebook del fundador (opcional)',
                            'ayuda' => 'Liga completa al perfil. Si se deja vacía, el ícono no aparece.'],
            'instagram' => ['tipo' => 'url', 'etiqueta' => 'Instagram del fundador (opcional)'],
            'youtube'   => ['tipo' => 'url', 'etiqueta' => 'YouTube del fundador (opcional)'],
        ],
    ],
    // 'fijo' => true: lista de elementos fijos (uno por página); solo se editan, no se agregan,
    // eliminan, ocultan ni reordenan. El nombre de cada uno sale de PORTADAS (funciones.php).
    'portadas' => [
        'titulo'    => 'Portadas de las páginas',
        'singular'  => 'portada',
        'nuevo'     => 'Editar portada',
        'descripcion' => 'La foto y los textos con que abre cada página: Nosotros, Propiedades, Tengo una propiedad, Blog y Contacto.',
        'fijo'      => true,
        'campo_nombre' => 'titulo',
        'campo_miniatura' => 'imagen',
        'campos' => [
            'imagen'    => ['tipo' => 'imagen', 'etiqueta' => 'Foto de fondo', 'req' => true,
                            'tamanos' => ['' => [1920, 1280, 'ajustar']],
                            'ayuda' => 'Horizontal y de al menos 1600 px de ancho. Se muestra recortada a una franja ancha y oscurecida para que el texto se lea.'],
            'encuadre'  => ['tipo' => 'opciones', 'etiqueta' => 'Parte de la foto que se ve', 'req' => true,
                            'opciones' => ['centro' => 'El centro', 'arriba' => 'La parte de arriba', 'abajo' => 'La parte de abajo']],
            'antetitulo' => ['tipo' => 'texto', 'etiqueta' => 'Texto pequeño arriba del título', 'max' => 60,
                            'ayuda' => 'Opcional. Ej.: Desde 2013 en León, Guanajuato'],
            'titulo'    => ['tipo' => 'texto', 'etiqueta' => 'Título', 'req' => true, 'max' => 50],
            'texto'     => ['tipo' => 'texto', 'etiqueta' => 'Texto debajo del título', 'max' => 160,
                            'ayuda' => 'Opcional. Una frase corta.'],
        ],
    ],
    'galeria' => [
        'titulo'    => 'Galería de Nosotros',
        'singular'  => 'foto',
        'nuevo'     => 'Agregar foto',
        'descripcion' => 'Las fotos del carrusel al final de la página Nosotros.',
        'campo_nombre' => 'alt',
        'campo_miniatura' => 'imagen',
        'campos' => [
            'imagen'    => ['tipo' => 'imagen', 'etiqueta' => 'Foto', 'req' => true,
                            'tamanos' => ['' => [1500, 1000, 'recortar']],
                            'ayuda' => 'Horizontal. Se recorta a 1500 × 1000.'],
            'alt'       => ['tipo' => 'texto', 'etiqueta' => 'Descripción de la foto', 'req' => true, 'max' => 150,
                            'ayuda' => 'Qué se ve (lo leen Google y los lectores de pantalla). Ej.: Fachada de casa en Sacromonte'],
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
