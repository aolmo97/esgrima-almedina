<?php
/**
 * Plantilla de respaldo genérica (requerida por WordPress).
 * El sitio usa front-page.php / page-*.php para las páginas reales;
 * este archivo solo cubre casos no contemplados (búsquedas, 404, etc.).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();
?>

<main class="container" style="padding: 60px 20px;">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p><?php esc_html_e( 'No se ha encontrado contenido.', 'seac' ); ?></p>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
