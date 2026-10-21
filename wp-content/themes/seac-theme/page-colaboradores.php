<?php
/**
 * Template Name: Colaboradores
 *
 * Patrocinadores y mecenas del club, con un CTA para conseguir nuevos patrocinios.
 *
 * Campos ACF (grupo "Colaboradores — Patrocinadores y mecenas", ver inc/acf-fields.php):
 *  - patrocinadores [repeater: nombre, logo, url]
 *  - mecenas        [repeater: nombre]
 *
 * El botón del CTA baja al formulario CF7 "Patrocinio SEAC" (ES) / "Patrocinio SEAC EN" (EN),
 * cuyo asunto y destinatario se configuran en Contacto > Formularios.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$patrocinadores = get_field( 'patrocinadores' );
$mecenas        = get_field( 'mecenas' );
?>

<main>

	<header class="page-hero">
		<div class="container page-hero__inner">
			<span class="page-hero__eyebrow">
				<span class="material-symbols-outlined" aria-hidden="true">handshake</span>
				<?php esc_html_e( 'Quienes nos apoyan', 'seac' ); ?>
			</span>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<p class="page-hero__desc"><?php esc_html_e( 'Gracias a las empresas, instituciones y personas que hacen posible el día a día de la Sala de Esgrima Almedina.', 'seac' ); ?></p>
			<div class="page-hero__bar"><div class="page-hero__bar-fill"></div></div>
		</div>
	</header>

	<?php if ( $patrocinadores ) : ?>
		<section class="section">
			<div class="container">
				<div class="section__header">
					<div>
						<h2 class="section__title"><?php esc_html_e( 'Patrocinadores', 'seac' ); ?></h2>
						<p class="section__desc"><?php esc_html_e( 'Empresas e instituciones que apoyan al club.', 'seac' ); ?></p>
					</div>
				</div>
				<div class="colab-grid">
					<?php foreach ( $patrocinadores as $p ) : ?>
						<?php $con_enlace = ! empty( $p['url'] ); ?>
						<?php if ( $con_enlace ) : ?>
							<a class="colab-card" href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php else : ?>
							<div class="colab-card">
						<?php endif; ?>
							<?php if ( ! empty( $p['logo'] ) ) : ?>
								<span class="colab-card__logo-box"><?php echo wp_get_attachment_image( (int) $p['logo'], 'medium', false, array( 'class' => 'colab-card__logo', 'alt' => $p['nombre'] ) ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $p['nombre'] ) ) : ?>
								<span class="colab-card__name"><?php echo esc_html( $p['nombre'] ); ?></span>
							<?php endif; ?>
						<?php echo $con_enlace ? '</a>' : '</div>'; ?>
					<?php endforeach; ?>
					<a class="colab-card colab-card--cta" href="#patrocinar">
						<span class="colab-card__placeholder"><span class="material-symbols-outlined" aria-hidden="true">add_photo_alternate</span></span>
						<span class="colab-card__name"><?php esc_html_e( 'Tu marca aquí', 'seac' ); ?></span>
						<span class="colab-card__more"><?php esc_html_e( 'Únete a la familia SEAC', 'seac' ); ?></span>
					</a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $mecenas ) : ?>
		<section class="section section-modalidades">
			<div class="container">
				<div class="section__header colab-header-center">
					<div>
						<h2 class="section__title"><?php esc_html_e( 'Mecenas', 'seac' ); ?></h2>
						<p class="section__desc"><?php esc_html_e( 'Personas que impulsan el club con su aportación.', 'seac' ); ?></p>
					</div>
				</div>
				<ul class="colab-mecenas">
					<?php foreach ( $mecenas as $m ) : ?>
						<?php if ( ! empty( $m['nombre'] ) ) : ?>
							<li><span class="material-symbols-outlined" aria-hidden="true">favorite</span><?php echo esc_html( $m['nombre'] ); ?></li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="section">
		<div class="container">
			<div class="horarios-cta">
				<div>
					<span class="horarios-cta__eyebrow">
						<span class="material-symbols-outlined" aria-hidden="true">volunteer_activism</span>
						<?php esc_html_e( 'Hazte patrocinador', 'seac' ); ?>
					</span>
					<h3 class="horarios-cta__title"><?php esc_html_e( 'Tu marca puede estar en cada asalto.', 'seac' ); ?></h3>
					<p class="horarios-cta__desc"><?php esc_html_e( 'Patrocinar al club es apoyar el deporte base en Córdoba: tu marca llega a deportistas, familias y aficionados, y tú ayudas a financiar material, desplazamientos y competiciones.', 'seac' ); ?></p>
				</div>
				<a class="btn-primary" href="#patrocinar">
					<span class="material-symbols-outlined" aria-hidden="true">mail</span>
					<?php esc_html_e( 'Quiero patrocinar al club', 'seac' ); ?>
				</a>
			</div>
		</div>
	</section>

	<!-- ===== FORMULARIO ===== -->
	<section class="contacto-form-section" id="patrocinar">
		<div class="container">
			<div class="section__header">
				<div>
					<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Colabora con nosotros', 'seac' ); ?></span>
					<h2 class="section__title"><?php esc_html_e( 'Cuéntanos cómo quieres apoyar al club', 'seac' ); ?></h2>
					<p class="section__desc"><?php esc_html_e( 'Rellena el formulario y nos pondremos en contacto contigo para explicarte las opciones de patrocinio y colaboración.', 'seac' ); ?></p>
				</div>
			</div>
			<div class="contacto-form-card">
				<?php echo do_shortcode( '[contact-form-7 title="' . esc_attr( seac_sponsor_form_title() ) . '"]' ); ?>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
