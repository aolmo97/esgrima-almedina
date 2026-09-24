<?php
/**
 * Partial: tarjeta de una fila del repeater ACF "modalidades"
 * (sub-campos: nombre [text], icono [image], descripcion [textarea]).
 *
 * Se invoca con get_template_part( 'template-parts/card-modalidad', null, $args )
 * pasando $args = array( 'index' => 1, 'nombre' => ..., 'icono' => <array ACF image>, 'descripcion' => ... ).
 * "index" es solo el número de orden dentro del loop: controla si se pinta
 * el icono decorativo de la esquina (no se usa como número visible).
 *
 * Si existe una página con plantilla "page-arma.php" cuyo slug coincide con el
 * nombre de la modalidad (p.ej. nombre "Florete" -> slug "florete"), la tarjeta
 * enlaza a esa página (vídeos de esa arma).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$index       = isset( $args['index'] ) ? (int) $args['index'] : 0;
$nombre      = isset( $args['nombre'] ) ? $args['nombre'] : '';
$icono       = isset( $args['icono'] ) ? $args['icono'] : null;
$descripcion = isset( $args['descripcion'] ) ? $args['descripcion'] : '';

$enlace = '';
if ( $nombre ) {
	$pagina_arma = get_page_by_path( sanitize_title( $nombre ) );
	if ( $pagina_arma && 'page-arma.php' === get_page_template_slug( $pagina_arma ) ) {
		$enlace = get_permalink( $pagina_arma );
	}
}

$tag = $enlace ? 'a' : 'article';
?>
<<?php echo esc_html( $tag ); ?> class="card card-modalidad" <?php echo $enlace ? 'href="' . esc_url( $enlace ) . '"' : ''; ?>>
	<div class="card-modalidad__top">
		<?php if ( ! empty( $icono['ID'] ) ) : ?>
			<div class="card-modalidad__icon">
				<?php echo wp_get_attachment_image( $icono['ID'], 'thumbnail' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $index ) : ?>
			<span class="card-modalidad__index">
				<span class="material-symbols-outlined" aria-hidden="true">swords</span>
			</span>
		<?php endif; ?>
	</div>

	<?php if ( $nombre ) : ?>
		<h3 class="card-modalidad__title"><?php echo esc_html( $nombre ); ?></h3>
	<?php endif; ?>

	<?php if ( $descripcion ) : ?>
		<p class="card-modalidad__desc"><?php echo esc_html( $descripcion ); ?></p>
	<?php endif; ?>

	<?php if ( $enlace ) : ?>
		<span class="card-modalidad__cta">
			<?php esc_html_e( 'Ver vídeos', 'seac' ); ?>
			<span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
		</span>
	<?php endif; ?>
</<?php echo esc_html( $tag ); ?>>
