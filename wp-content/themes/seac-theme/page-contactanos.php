<?php
/**
 * Template Name: Contáctanos
 *
 * Diseño: design-reference/cont_ctanos_modo_claro_sala_de_esgrima_almedina/code.html
 * (Stitch, sistema "Kinetic Blade" modo claro). Breakpoints: 768px y 1024px.
 *
 * Campos ACF (grupo "Contáctanos — Datos", ver inc/acf-fields.php):
 *  - direccion [text]
 *  - telefono  [text]
 *  - email     [email]
 *  - mapa      [google_map] — se usa solo para el enlace "Abrir en Google Maps"
 *    y el panel decorativo (no se embebe un iframe real, así no depende de
 *    una API key de Google Maps).
 *
 * Los horarios que se muestran aquí reutilizan el repeater "horarios" de la
 * Home (mismo dato, sin duplicar contenido) leyendo el campo con el ID de
 * esa página.
 *
 * El formulario usa Contact Form 7 (formulario "Contacto SEAC", editable en
 * Contacto > Formularios). El destinatario del correo se lee del campo ACF
 * "email" de esta misma página (ver seac_cf7_dynamic_recipient en functions.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$direccion = get_field( 'direccion' );
$telefono  = get_field( 'telefono' );
$email     = get_field( 'email' );
$mapa      = get_field( 'mapa' );

$home_id  = seac_get_page_id_by_template( 'front-page.php' );
$horarios = $home_id ? get_field( 'horarios', $home_id ) : false;

$mapa_query = $mapa && ! empty( $mapa['address'] ) ? $mapa['address'] : ( $direccion ? $direccion : get_bloginfo( 'name' ) );
$mapa_url   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $mapa_query );
?>

<main>

	<!-- ===== CABECERA DE PÁGINA ===== -->
	<header class="page-hero">
		<div class="container page-hero__inner">
			<span class="page-hero__eyebrow">
				<span class="material-symbols-outlined" aria-hidden="true">forum</span>
				<?php esc_html_e( 'S.E.A.C. · Córdoba Base', 'seac' ); ?>
			</span>
			<h1 class="page-hero__title"><?php the_title(); ?></h1>
			<p class="page-hero__desc"><?php esc_html_e( 'Ven a conocer la Sala de Esgrima Almedina en Córdoba. Te prestamos todo el material homologado para tu primera sesión sin compromiso.', 'seac' ); ?></p>
		</div>
	</header>

	<!-- ===== INFO + MAPA ===== -->
	<section class="section">
		<div class="container contacto-info-grid">

			<div class="contacto-info">

				<?php if ( $direccion ) : ?>
					<div class="info-card">
						<div class="info-card__icon"><span class="material-symbols-outlined" aria-hidden="true">location_on</span></div>
						<div>
							<span class="info-card__label"><?php esc_html_e( 'Dirección de la sede', 'seac' ); ?></span>
							<p class="info-card__value"><?php echo esc_html( $direccion ); ?></p>
						</div>
					</div>
				<?php endif; ?>

				<div class="info-card-row">
					<?php if ( $telefono ) : ?>
						<div class="info-card">
							<div class="info-card__icon"><span class="material-symbols-outlined" aria-hidden="true">call</span></div>
							<div>
								<span class="info-card__label"><?php esc_html_e( 'Teléfono y WhatsApp', 'seac' ); ?></span>
								<p class="info-card__value"><a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $telefono ) ); ?>"><?php echo esc_html( $telefono ); ?></a></p>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $email ) : ?>
						<div class="info-card">
							<div class="info-card__icon"><span class="material-symbols-outlined" aria-hidden="true">alternate_email</span></div>
							<div>
								<span class="info-card__label"><?php esc_html_e( 'Secretaría y admisiones', 'seac' ); ?></span>
								<p class="info-card__value"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $horarios ) : ?>
					<div class="info-card horarios-mini" style="align-items:stretch;">
						<div style="width:100%;">
							<div class="horarios-mini__head">
								<span class="material-symbols-outlined" aria-hidden="true" style="color:var(--color-primary);">schedule</span>
								<h3><?php esc_html_e( 'Horarios de asaltos y pistas', 'seac' ); ?></h3>
							</div>
							<div class="horarios-mini__grid">
								<?php foreach ( $horarios as $slot ) : ?>
									<div class="horarios-mini__item">
										<span class="horarios-mini__categoria"><?php echo esc_html( $slot['categoria'] ); ?></span>
										<span class="horarios-mini__hora"><?php echo esc_html( trim( $slot['hora_inicio'] . ( $slot['hora_inicio'] && $slot['hora_fin'] ? ' – ' : '' ) . $slot['hora_fin'] ) ); ?></span>
										<span class="horarios-mini__dias"><?php echo esc_html( $slot['dias'] ); ?></span>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

			</div>

			<div class="mapa-tactico">
				<div class="mapa-tactico__head">
					<span class="mapa-tactico__chip"><span class="material-symbols-outlined" aria-hidden="true" style="font-size:16px;color:var(--color-primary);">satellite_alt</span> <?php esc_html_e( 'Vista táctica S.E.A.C.', 'seac' ); ?></span>
				</div>
				<div class="mapa-tactico__pin">
					<div class="mapa-tactico__callout">
						<span class="mapa-tactico__callout-eyebrow"><?php esc_html_e( 'Sala de Armas Almedina', 'seac' ); ?></span>
						<span class="mapa-tactico__callout-title">S.E.A.C. Córdoba</span>
						<?php if ( $direccion ) : ?><span class="mapa-tactico__callout-addr"><?php echo esc_html( $direccion ); ?></span><?php endif; ?>
					</div>
					<span class="mapa-tactico__dot"><span class="mapa-tactico__dot-core"></span></span>
				</div>
				<div class="mapa-tactico__foot">
					<span><?php esc_html_e( 'Córdoba, España', 'seac' ); ?></span>
					<a href="<?php echo esc_url( $mapa_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Abrir en Google Maps', 'seac' ); ?>
						<span class="material-symbols-outlined" aria-hidden="true" style="font-size:16px;">open_in_new</span>
					</a>
				</div>
			</div>

		</div>
	</section>

	<!-- ===== FORMULARIO ===== -->
	<section class="contacto-form-section">
		<div class="container">

			<div class="section__header">
				<div>
					<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Inscripciones abiertas', 'seac' ); ?></span>
					<h2 class="section__title"><?php esc_html_e( 'Solicita información o tu clase de prueba gratuita', 'seac' ); ?></h2>
					<p class="section__desc"><?php esc_html_e( 'Rellena el formulario y nuestro equipo se pondrá en contacto contigo en menos de 24 horas.', 'seac' ); ?></p>
				</div>
			</div>

			<div class="contacto-form-card">
				<?php echo do_shortcode( '[contact-form-7 title="Contacto SEAC"]' ); ?>
			</div>

			<div class="valueprops">
				<div class="valueprop">
					<div class="valueprop__icon"><span class="material-symbols-outlined" aria-hidden="true">checkroom</span></div>
					<div>
						<span class="valueprop__title"><?php esc_html_e( 'Material incluido', 'seac' ); ?></span>
						<p class="valueprop__desc"><?php esc_html_e( 'Careta, chaquetilla, guante y arma proporcionados por la sala durante tu iniciación.', 'seac' ); ?></p>
					</div>
				</div>
				<div class="valueprop">
					<div class="valueprop__icon"><span class="material-symbols-outlined" aria-hidden="true">verified_user</span></div>
					<div>
						<span class="valueprop__title"><?php esc_html_e( 'Maestros titulados', 'seac' ); ?></span>
						<p class="valueprop__desc"><?php esc_html_e( 'Entrenamiento guiado por maestros acreditados por la RFEE.', 'seac' ); ?></p>
					</div>
				</div>
				<div class="valueprop">
					<div class="valueprop__icon"><span class="material-symbols-outlined" aria-hidden="true">bolt</span></div>
					<div>
						<span class="valueprop__title"><?php esc_html_e( 'Sin compromiso', 'seac' ); ?></span>
						<p class="valueprop__desc"><?php esc_html_e( 'Tu primera clase introductoria es totalmente gratuita.', 'seac' ); ?></p>
					</div>
				</div>
			</div>

		</div>
	</section>

</main>

<?php get_footer(); ?>
