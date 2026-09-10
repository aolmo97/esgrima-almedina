<?php
/**
 * Cabecera del tema: logo, nombre y eslogan de marca (editables en el admin
 * en "Ajustes del sitio", ver seac_option() en functions.php), menú "primary"
 * registrado en functions.php y botón CTA hacia la página de Contáctanos.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="container site-header__inner">

		<?php
		// El logo se guarda como imagen en "Ajustes del sitio" (no con
		// the_custom_logo(): esa función envuelve la imagen en su propio <a>,
		// y anidarlo dentro de otro <a> produce HTML invalido).
		$logo_id     = seac_option( 'logo_sitio' );
		$logo_img    = $logo_id ? wp_get_attachment_image( $logo_id, 'full', false, array( 'class' => 'custom-logo' ) ) : '';
		$nombre_marca = seac_option( 'nombre_marca', get_bloginfo( 'name' ) );
		$eslogan_marca = seac_option( 'eslogan_marca', '' );
		?>
		<a class="site-header__branding" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo $logo_img; // phpcs:ignore -- wp_get_attachment_image ya escapa los atributos. ?>
			<span class="site-header__brand-text">
				<span class="site-header__brand-name"><?php echo esc_html( $nombre_marca ); ?></span>
				<?php if ( $eslogan_marca ) : ?>
					<span class="site-header__brand-sub"><?php echo esc_html( $eslogan_marca ); ?></span>
				<?php endif; ?>
			</span>
		</a>

		<button class="site-header__toggle" id="site-header-toggle" aria-expanded="false" aria-controls="site-header-nav">
			<span class="sr-only"><?php esc_html_e( 'Abrir menú', 'seac' ); ?></span>
			<span class="site-header__toggle-bar"></span>
			<span class="site-header__toggle-bar"></span>
			<span class="site-header__toggle-bar"></span>
		</button>

		<nav class="site-header__nav" id="site-header-nav" aria-label="<?php esc_attr_e( 'Menú principal', 'seac' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'primary-menu',
					'depth'          => 1,
				) );
			} else {
				seac_fallback_menu();
			}

			$contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );
			$cta_url     = $contacto_id ? get_permalink( $contacto_id ) : home_url( '/' );
			?>
			<a class="btn-primary site-header__cta" href="<?php echo esc_url( $cta_url ); ?>">
				<span class="material-symbols-outlined" aria-hidden="true">bolt</span>
				<?php esc_html_e( 'Prueba gratis / Contacto', 'seac' ); ?>
			</a>
		</nav>

	</div>
</header>
