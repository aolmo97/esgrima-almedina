<?php
/**
 * Footer del tema: marca (mismo logo/nombre que la cabecera) + descripción,
 * navegación y redes sociales, más barra de copyright. Todo editable en el
 * admin en "Ajustes del sitio" (ver seac_option() en functions.php).
 *
 * Diseño: design-reference .../home_sala_de_esgrima_almedina/code.html
 * (footer de 3 columnas + barra inferior).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$quienes_id  = seac_get_page_id_by_template( 'page-quienes-somos.php' );
$contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );

$logo_id       = seac_option( 'logo_sitio' );
$nombre_marca  = seac_option( 'nombre_marca', get_bloginfo( 'name' ) );
$footer_desc   = seac_footer_descripcion();
$footer_anio   = seac_option( 'footer_anio_fundacion', '' );
$footer_ubic   = seac_option( 'footer_ubicacion', '' );

$redes_sociales = array(
	'instagram' => array( 'label' => 'Instagram', 'url' => seac_option( 'red_instagram', '' ) ),
	'facebook'  => array( 'label' => 'Facebook', 'url' => seac_option( 'red_facebook', '' ) ),
	'youtube'   => array( 'label' => 'YouTube', 'url' => seac_option( 'red_youtube', '' ) ),
	'whatsapp'  => array( 'label' => 'WhatsApp', 'url' => seac_option( 'red_whatsapp', '' ) ),
);

$redes_iconos = array(
	'instagram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2.2c3.2 0 3.6 0 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.25.07 1.6.07 4.81 0 3.22-.01 3.56-.07 4.81-.15 3.23-1.66 4.77-4.92 4.92-1.25.06-1.6.07-4.85.07-3.2 0-3.6 0-4.85-.07-3.26-.15-4.77-1.7-4.92-4.92-.06-1.25-.07-1.59-.07-4.81 0-3.21.01-3.56.07-4.81.15-3.23 1.66-4.77 4.92-4.92C8.4 2.2 8.8 2.2 12 2.2zm0 3.55a6.25 6.25 0 1 0 0 12.5 6.25 6.25 0 0 0 0-12.5zm0 10.31a4.06 4.06 0 1 1 0-8.12 4.06 4.06 0 0 1 0 8.12zm6.5-10.56a1.46 1.46 0 1 1-2.92 0 1.46 1.46 0 0 1 2.92 0z"/></svg>',
	'facebook'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7.6h2.6l.4-3h-3v-1.9c0-.87.24-1.46 1.5-1.46h1.6V4.35C15.86 4.24 14.9 4.15 13.8 4.15c-2.3 0-3.9 1.4-3.9 4v2.25H7.3v3h2.6V21h3.6z"/></svg>',
	'youtube'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2s-.2-1.5-.85-2.15c-.8-.85-1.7-.85-2.1-.9C15.9 4 12 4 12 4s-3.9 0-6.65.15c-.4.05-1.3.05-2.1.9C2.6 5.7 2.4 7.2 2.4 7.2S2.2 9 2.2 10.75v1.5C2.2 14 2.4 15.8 2.4 15.8s.2 1.5.85 2.15c.8.85 1.85.82 2.3.91C7.1 19 12 19 12 19s3.9 0 6.65-.15c.4-.05 1.3-.05 2.1-.9.65-.65.85-2.15.85-2.15s.2-1.8.2-3.55v-1.5c0-1.75-.2-3.55-.2-3.55zM9.95 14.1V8.4l5.5 2.85-5.5 2.85z"/></svg>',
	'whatsapp'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.05-1.35A10 10 0 1 0 12 2zm5.7 14.3c-.25.7-1.25 1.3-2.05 1.45-.55.1-1.25.2-3.65-.8-3.05-1.25-5-4.3-5.15-4.5-.15-.2-1.2-1.6-1.2-3.05s.75-2.15 1-2.45c.25-.3.55-.35.75-.35h.55c.2 0 .45-.05.7.55.25.6.85 2.05.9 2.2.05.15.1.3 0 .5-.1.2-.15.3-.3.45-.15.2-.3.4-.45.55-.15.15-.3.3-.15.6.15.3.7 1.15 1.5 1.85 1.05.9 1.9 1.2 2.2 1.35.3.15.45.1.6-.1.15-.2.7-.8.9-1.1.2-.3.4-.25.65-.15.25.1 1.65.8 1.95.9.3.15.5.2.55.35.05.15.05.85-.2 1.55z"/></svg>',
);

$footer_nav = array(
	array( 'label' => __( 'Home', 'seac' ), 'url' => home_url( '/' ) ),
);
if ( $quienes_id ) {
	$footer_nav[] = array( 'label' => __( 'Quiénes somos', 'seac' ), 'url' => get_permalink( $quienes_id ) );
}
$footer_nav[] = array( 'label' => __( 'Modalidades', 'seac' ), 'url' => home_url( '/#modalidades' ) );
$footer_nav[] = array( 'label' => __( 'Horarios', 'seac' ), 'url' => home_url( '/#horarios' ) );
if ( $contacto_id ) {
	$footer_nav[] = array( 'label' => __( 'Contáctanos', 'seac' ), 'url' => get_permalink( $contacto_id ) );
}
?>
	<footer class="site-footer">
		<div class="site-footer__beam"></div>
		<div class="container">

			<div class="site-footer__grid">

				<div class="site-footer__brand">
					<div class="site-footer__brand-row">
						<?php if ( $logo_id ) : ?>
							<?php echo wp_get_attachment_image( $logo_id, 'thumbnail', false, array( 'class' => 'site-footer__logo' ) ); ?>
						<?php endif; ?>
						<span class="site-footer__brand-name"><?php echo esc_html( $nombre_marca ); ?></span>
						<?php if ( $footer_anio ) : ?>
							<span class="site-footer__est"><?php echo esc_html( sprintf( __( 'EST. %s', 'seac' ), $footer_anio ) ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( $footer_desc ) : ?>
						<p class="site-footer__desc"><?php echo esc_html( $footer_desc ); ?></p>
					<?php endif; ?>
					<?php if ( $footer_ubic ) : ?>
						<p class="site-footer__location">
							<span class="material-symbols-outlined" aria-hidden="true">location_on</span>
							<?php echo esc_html( $footer_ubic ); ?>
						</p>
					<?php endif; ?>
				</div>

				<div class="site-footer__col">
					<span class="site-footer__col-title"><?php esc_html_e( 'Navegación', 'seac' ); ?></span>
					<nav class="footer-menu-wrap" aria-label="<?php esc_attr_e( 'Menú footer', 'seac' ); ?>">
						<?php if ( has_nav_menu( 'footer' ) ) : ?>
							<?php
							wp_nav_menu( array(
								'theme_location' => 'footer',
								'container'      => false,
								'menu_class'     => 'footer-menu',
								'depth'          => 1,
							) );
							?>
						<?php else : ?>
							<ul class="footer-menu">
								<?php foreach ( $footer_nav as $item ) : ?>
									<li><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</nav>
				</div>

				<?php $redes_activas = array_filter( $redes_sociales, function ( $red ) { return ! empty( $red['url'] ); } ); ?>
				<?php if ( $redes_activas ) : ?>
					<div class="site-footer__col">
						<span class="site-footer__col-title"><?php esc_html_e( 'Comunidad & Redes', 'seac' ); ?></span>
						<div class="site-footer__social">
							<?php foreach ( $redes_activas as $key => $red ) : ?>
								<a href="<?php echo esc_url( $red['url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $red['label'] ); ?>">
									<?php echo $redes_iconos[ $key ]; // phpcs:ignore -- SVG estático definido arriba, no viene de input de usuario ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

			</div>

			<div class="site-footer__bottom">
				<p class="site-footer__copy">
					&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?>
					<?php esc_html_e( 'Sala de Esgrima Almedina de Córdoba (España). Todos los derechos reservados.', 'seac' ); ?>
				</p>
				<span class="site-footer__tag"><?php esc_html_e( 'Club de Esgrima Oficial · Fencing Academy', 'seac' ); ?></span>
			</div>

		</div>
	</footer>

<?php wp_footer(); ?>
</body>
</html>
