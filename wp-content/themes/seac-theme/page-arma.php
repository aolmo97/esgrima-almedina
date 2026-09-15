<?php
/**
 * Template Name: Arma (vídeos)
 *
 * Plantilla común para las 3 páginas de arma (Florete, Espada, Sable).
 * Cada tarjeta de "Modalidades de combate" en la home (template-parts/card-modalidad.php)
 * enlaza aquí automáticamente si existe una página con esta plantilla cuyo slug
 * coincide con el nombre de la modalidad (sanitize_title), p.ej. nombre "Florete" -> slug "florete".
 *
 * Campos ACF (grupo "Página de arma — Vídeos", ver inc/acf-fields.php):
 *  - subtitulo [textarea, opcional]
 *  - videos    [repeater: titulo (text), video_url (oembed), descripcion (textarea, opcional)]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$subtitulo = get_field( 'subtitulo' );
?>

<main>

	<!-- ===== CABECERA DE PÁGINA ===== -->
	<header class="page-hero">
		<div class="container page-hero__inner">
			<span class="page-hero__eyebrow">
				<span class="material-symbols-outlined" aria-hidden="true">sports_martial_arts</span>
				<?php esc_html_e( 'Modalidad de combate', 'seac' ); ?>
			</span>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<?php if ( $subtitulo ) : ?>
				<p class="page-hero__desc"><?php echo esc_html( $subtitulo ); ?></p>
			<?php endif; ?>
			<div class="page-hero__bar"><div class="page-hero__bar-fill"></div></div>
		</div>
	</header>

	<!-- ===== VÍDEOS ===== -->
	<?php if ( have_rows( 'videos' ) ) : ?>
		<section class="section section-videos-arma">
			<div class="container">

				<div class="section__header">
					<div>
						<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Técnica en movimiento', 'seac' ); ?></span>
						<h2 class="section__title"><?php esc_html_e( 'Vídeos', 'seac' ); ?></h2>
					</div>
				</div>

				<div class="grid grid-videos-arma">
					<?php while ( have_rows( 'videos' ) ) : the_row(); ?>
						<?php
						$video_titulo = get_sub_field( 'titulo' );
						$video_embed  = get_sub_field( 'video_url' );
						$video_desc   = get_sub_field( 'descripcion' );
						?>
						<?php if ( $video_embed ) : ?>
							<article class="card-video">
								<div class="card-video__media"><?php echo $video_embed; ?></div>
								<?php if ( $video_titulo ) : ?>
									<h3 class="card-video__title"><?php echo esc_html( $video_titulo ); ?></h3>
								<?php endif; ?>
								<?php if ( $video_desc ) : ?>
									<p class="card-video__desc"><?php echo esc_html( $video_desc ); ?></p>
								<?php endif; ?>
							</article>
						<?php endif; ?>
					<?php endwhile; ?>
				</div>

			</div>
		</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
