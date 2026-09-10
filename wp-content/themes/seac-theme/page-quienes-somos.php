<?php
/**
 * Template Name: Quiénes somos
 *
 * Diseño: design-reference/qui_nes_somos_modo_claro_sala_de_esgrima_almedina/code.html
 * (Stitch, sistema "Kinetic Blade" modo claro). Breakpoints: 768px y 1024px.
 *
 * Campos ACF (grupo "Quiénes somos — Historia", ver inc/acf-fields.php):
 *  - historia_bloques  [repeater: texto (wysiwyg), galeria (gallery, opcional)]
 *  - galeria_general   [gallery]
 *
 * Sección "Nuestro equipo": posts del CPT "miembro_equipo". El que tenga
 * destacado = true (el maestro/a) se pinta primero en tarjeta grande;
 * el resto en grid de hasta 4 columnas (ver template-parts/card-miembro-equipo.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );
?>

<main>

	<!-- ===== CABECERA DE PÁGINA ===== -->
	<header class="page-hero">
		<div class="container page-hero__inner">
			<span class="page-hero__eyebrow">
				<span class="material-symbols-outlined" aria-hidden="true">history_edu</span>
				<?php esc_html_e( 'Historia · Identidad · Maestría', 'seac' ); ?>
			</span>
			<h1 class="page-hero__title">
				<?php
				$titulo = get_the_title();
				echo wp_kses_post( preg_replace( '/(somos)$/i', '<mark>$1</mark>', esc_html( $titulo ) ) );
				?>
			</h1>
			<p class="page-hero__desc"><?php esc_html_e( 'Tradición andaluza, vanguardia táctica y pasión por la esgrima en Córdoba.', 'seac' ); ?></p>
			<div class="page-hero__bar"><div class="page-hero__bar-fill"></div></div>
		</div>
	</header>

	<!-- ===== HISTORIA ===== -->
	<?php if ( have_rows( 'historia_bloques' ) ) : ?>
		<div class="container">
			<div class="historia-bloques">
				<?php $bloque_index = 0; $bloque_con_galeria = 0; while ( have_rows( 'historia_bloques' ) ) : the_row(); $bloque_index++; ?>
					<?php
					$galeria_bloque = get_sub_field( 'galeria' );
					$es_reverse     = false;
					if ( $galeria_bloque ) {
						$es_reverse = ( $bloque_con_galeria % 2 === 1 ); // alterna el lado de la imagen entre bloques con galeria.
						$bloque_con_galeria++;
					}
					get_template_part( 'template-parts/card-historia-bloque', null, array(
						'index'           => $bloque_index,
						'etiqueta'        => get_sub_field( 'etiqueta' ),
						'texto'           => get_sub_field( 'texto' ),
						'galeria'         => $galeria_bloque,
						'reverse'         => $es_reverse,
						'destacado_icono'  => get_sub_field( 'destacado_icono' ),
						'destacado_titulo' => get_sub_field( 'destacado_titulo' ),
						'destacado_texto'  => get_sub_field( 'destacado_texto' ),
						'estadisticas'    => get_sub_field( 'estadisticas' ),
						'hashtags'        => get_sub_field( 'hashtags' ),
						'boton_texto'     => get_sub_field( 'boton_texto' ),
						'boton_url'       => get_sub_field( 'boton_url' ),
					) );
					?>
				<?php endwhile; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ===== GALERÍA GENERAL ===== -->
	<?php $galeria_general = get_field( 'galeria_general' ); ?>
	<?php if ( $galeria_general ) : ?>
		<section class="galeria-general">
			<div class="container">
				<div class="section__header">
					<div>
						<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Registro visual oficial', 'seac' ); ?></span>
						<h2 class="section__title"><?php esc_html_e( 'Momentos en pista', 'seac' ); ?></h2>
					</div>
					<span class="section__desc"><?php esc_html_e( 'Instantáneas de alta intensidad competitiva en S.E.A.C.', 'seac' ); ?></span>
				</div>
				<div class="grid-galeria">
					<?php foreach ( $galeria_general as $imagen ) : ?>
						<div class="grid-galeria__item">
							<?php echo wp_get_attachment_image( $imagen['ID'], 'medium_large' ); ?>
							<?php if ( ! empty( $imagen['caption'] ) || ! empty( $imagen['description'] ) ) : ?>
								<div class="grid-galeria__overlay">
									<?php if ( ! empty( $imagen['caption'] ) ) : ?>
										<span class="grid-galeria__caption"><?php echo esc_html( $imagen['caption'] ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $imagen['description'] ) ) : ?>
										<span class="grid-galeria__meta"><?php echo esc_html( $imagen['description'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- ===== NUESTRO EQUIPO ===== -->
	<?php
	$maestro_query = new WP_Query( array(
		'post_type'      => 'miembro_equipo',
		'posts_per_page' => 1,
		'meta_key'       => 'destacado',
		'meta_value'     => '1',
		'no_found_rows'  => true,
	) );

	$resto_query = new WP_Query( array(
		'post_type'      => 'miembro_equipo',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => 'destacado',
				'value'   => '1',
				'compare' => '!=',
			),
		),
		'orderby'        => 'title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );
	?>

	<?php if ( $maestro_query->have_posts() || $resto_query->have_posts() ) : ?>
		<section class="section-equipo">
			<div class="container">

				<div class="section__header" style="justify-content:center;text-align:center;">
					<div>
						<span class="eyebrow" style="justify-content:center;"><span class="eyebrow__dot"></span><?php esc_html_e( 'Estructura técnica y deportiva', 'seac' ); ?></span>
						<h2 class="section__title" style="text-align:center;"><?php esc_html_e( 'Maestros y cuerpo técnico', 'seac' ); ?></h2>
						<p class="section__desc" style="margin:0 auto;"><?php esc_html_e( 'Profesionales dedicados al desarrollo integral de cada esgrimista.', 'seac' ); ?></p>
					</div>
				</div>

				<?php if ( $maestro_query->have_posts() ) : ?>
					<div class="equipo-destacado">
						<?php
						while ( $maestro_query->have_posts() ) :
							$maestro_query->the_post();
							get_template_part( 'template-parts/card-miembro-equipo', null, array(
								'id'       => get_the_ID(),
								'featured' => true,
							) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php endif; ?>

				<?php if ( $resto_query->have_posts() ) : ?>
					<div class="grid grid-equipo">
						<?php
						while ( $resto_query->have_posts() ) :
							$resto_query->the_post();
							get_template_part( 'template-parts/card-miembro-equipo', null, array(
								'id'       => get_the_ID(),
								'featured' => false,
							) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				<?php endif; ?>

				<?php if ( $contacto_id ) : ?>
					<div class="equipo-cta">
						<div class="equipo-cta__icon"><span class="material-symbols-outlined" aria-hidden="true">sports_martial_arts</span></div>
						<div>
							<h3 class="equipo-cta__title"><?php esc_html_e( '¿Quieres entrenar con nuestro equipo?', 'seac' ); ?></h3>
							<p class="equipo-cta__desc"><?php esc_html_e( 'Sin compromiso. Te proporcionamos la careta, el traje y la espada para tu primer asalto.', 'seac' ); ?></p>
						</div>
						<a class="btn-primary" href="<?php echo esc_url( get_permalink( $contacto_id ) ); ?>">
							<?php esc_html_e( 'Contactar sala', 'seac' ); ?>
						</a>
					</div>
				<?php endif; ?>

			</div>
		</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
