<?php
/**
 * Partial: tarjeta de una fila del repeater ACF "modalidades"
 * (sub-campos: nombre [text], icono [image], descripcion [textarea]).
 *
 * Se invoca con get_template_part( 'template-parts/card-modalidad', null, $args )
 * pasando $args = array( 'index' => 1, 'nombre' => ..., 'icono' => <array ACF image>, 'descripcion' => ... ).
 * "index" es solo el número de orden dentro del loop, para la etiqueta MOD-0x (decorativa).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$index       = isset( $args['index'] ) ? (int) $args['index'] : 0;
$nombre      = isset( $args['nombre'] ) ? $args['nombre'] : '';
$icono       = isset( $args['icono'] ) ? $args['icono'] : null;
$descripcion = isset( $args['descripcion'] ) ? $args['descripcion'] : '';
?>
<article class="card card-modalidad">
	<div class="card-modalidad__top">
		<?php if ( ! empty( $icono['ID'] ) ) : ?>
			<div class="card-modalidad__icon">
				<?php echo wp_get_attachment_image( $icono['ID'], 'thumbnail' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $index ) : ?>
			<span class="card-modalidad__index">MOD-<?php echo esc_html( str_pad( $index, 2, '0', STR_PAD_LEFT ) ); ?></span>
		<?php endif; ?>
	</div>

	<?php if ( $nombre ) : ?>
		<h3 class="card-modalidad__title"><?php echo esc_html( $nombre ); ?></h3>
	<?php endif; ?>

	<?php if ( $descripcion ) : ?>
		<p class="card-modalidad__desc"><?php echo esc_html( $descripcion ); ?></p>
	<?php endif; ?>
</article>
