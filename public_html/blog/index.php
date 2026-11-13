<?php
/**
 * Blog: listado de artículos (data/blog/*.json).
 */
require dirname(__DIR__) . '/includes/config.php';

$posts = obtener_posts();

$portada = portada_de('blog', ['imagen' => '/assets/img/blog-hero.webp', 'posicion' => 'center 28%', 'antetitulo' => "Du'an Bienes Raíces", 'titulo' => 'Blog', 'texto' => 'Guías y consejos para comprar, vender o rentar con certeza en León, Guanajuato.']);

$pagina = [
    'titulo'      => "Blog inmobiliario | Du'an Bienes Raíces, León, Gto.",
    'descripcion' => 'Consejos para comprar, vender y rentar propiedades en León, Guanajuato: créditos, trámites, precios y más, por los asesores de Du\'an Bienes Raíces.',
    'ruta'        => '/blog',
    'activo'      => 'blog',
    'imagen'      => $portada['imagen'],
    'precarga'    => $portada['imagen'],
    'jsonld'      => [[
        '@context' => 'https://schema.org',
        '@type'    => 'Blog',
        'name'     => "Blog de Du'an Bienes Raíces",
        'url'      => URL_SITIO . '/blog',
        'blogPost' => array_map(fn($p) => [
            '@type'         => 'BlogPosting',
            'headline'      => $p['titulo'],
            'url'           => URL_SITIO . '/blog/' . $p['slug'],
            'datePublished' => $p['fecha'],
        ], $posts),
    ]],
];

require dirname(__DIR__) . '/includes/head.php';
require dirname(__DIR__) . '/includes/header.php';
?>

<?php componente('portada', ['portada' => $portada, 'baja' => true]); ?>

<section class="seccion blog">
    <div class="contenedor">

        <?php if ($posts): ?>
            <div class="blog__grid">
                <?php foreach ($posts as $post) {
                    componente('card-blog', ['post' => $post]);
                } ?>
            </div>
        <?php else: ?>
            <p class="centrado">Pronto publicaremos nuevos artículos.</p>
        <?php endif; ?>
    </div>
</section>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
