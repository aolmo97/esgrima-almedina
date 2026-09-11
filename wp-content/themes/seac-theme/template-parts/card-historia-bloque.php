<?php
/**
 * Partial: una fila del repeater ACF "historia_bloques". Sub-campos:
 *  - etiqueta            [text, opcional]
 *  - texto               [wysiwyg]
 *  - galeria             [gallery, opcional] (leyenda de cada foto = campo
 *    "Leyenda" del adjunto en la Biblioteca de medios)
 *  - destacado_icono / destacado_titulo / destacado_texto  [opcional]
 *  - estadisticas        [repeater: icono, valor, etiqueta — opcional]
 *  - hashtags            [text, separados por comas — opcional]
 *  - boton_texto / boton_url  [opcional]
 *
 * Se invoca con get_template_part( 'template-parts/card-historia-bloque', null, $args ).
 *
 * Tres variantes, igual que en el diseño (design-reference/qui_nes_somos_.../code.html):
 *  - Con galería: texto + fotos en columnas, alternando el lado de la imagen.
 *  - Sin galería pero con botón: franja horizontal, texto a la izquierda y
 *    CTA a la derecha (Bloque 04 del diseño).
 *  - Sin galería ni botón: bloque editorial centrado (Bloque 02 del diseño).
 * La caja destacada, las estadísticas y los hashtags son independientes de
 * la variante: se muestran en cualquiera de las tres si están rellenos.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$etiqueta    = isset( $args['etiqueta'] ) ? $args['etiqueta'] : '';
$texto       = isset( $args['texto'] ) ? $args['texto'] : '';
$galeria     = isset( $args['galeria'] ) ? $args['galeria'] : false;
$dest_icono  = isset( $args['destacado_icono'] ) ? $args['destacado_icono'] : '';
$dest_titulo = isset( $args['destacado_titulo'] ) ? $args['destacado_titulo'] : '';
$dest_texto  = isset( $args['destacado_texto'] ) ? $args['destacado_texto'] : '';
$estadisticas = isset( $args['estadisticas'] ) ? $args['estadisticas'] : false;
$hashtags    = isset( $args['hashtags'] ) ? $args['hashtags'] : '';
$boton_texto = isset( $args['boton_texto'] ) ? $args['boton_texto'] : '';
$boton_url   = isset( $args['boton_url'] ) ? $args['boton_url'] : '';
$index       = isset( $args['index'] ) ? (int) $args['index'] : 0;

$tiene_galeria = ! empty( $galeria );
$tiene_boton   = $boton_texto && $boton_url;
$tiene_dest    = $dest_titulo && $dest_texto;
$es_reverse    = $tiene_galeria && ! empty( $args['reverse'] );

if ( $tiene_galeria ) {
	$variante = 'split';
} elseif ( $tiene_boton ) {
	$variante = 'cta';
} else {
	$variante = 'editorial';
}

$hashtag_list = $hashtags ? array_filter( array_map( 'trim', explode( ',', $hashtags ) ) ) : array();

$wrapper_class = 'card-bloque card-bloque--' . $variante;
$wrapper_class .= $es_reverse ? ' card-bloque--reverse' : '';
?>
<article class="<?php echo esc_attr( $wrapper_class ); ?>">

	<div class="card-bloque__badge-row">
		<span class="card-bloque__badge"><?php echo esc_html( sprintf( 'BLOQUE %s', str_pad( $index, 2, '0', STR_PAD_LEFT ) ) ); ?></span>
		<?php if ( $etiqueta ) : ?>
			<span class="card-bloque__etiqueta"><?php echo esc_html( $etiqueta ); ?></span>
		<?php endif; ?>
	</div>

	<div class="<?php echo ( $tiene_galeria || $tiene_boton ) ? 'card-bloque__split' : ''; ?>">

		<div class="card-bloque__text">
			<?php echo wp_kses_post( $texto ); ?>

			<?php if ( $tiene_dest ) : ?>
				<div class="card-bloque__destacado">
					<?php if ( $dest_icono ) : ?>
						<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $dest_icono ); ?></span>
					<?php endif; ?>
					<div>
						<span class="card-bloque__destacado-titulo"><?php echo esc_html( $dest_titulo ); ?></span>
						<span class="card-bloque__destacado-texto"><?php echo esc_html( $dest_texto ); ?></span>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $estadisticas ) : ?>
				<div class="card-bloque__stats">
					<?php foreach ( $estadisticas as $stat ) : ?>
						<div class="card-bloque__stat">
							<?php if ( ! empty( $stat['icono'] ) ) : ?>
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $stat['icono'] ); ?></span>
							<?php endif; ?>
							<span class="card-bloque__stat-valor"><?php echo esc_html( $stat['valor'] ); ?></span>
							<span class="card-bloque__stat-etiqueta"><?php echo esc_html( $stat['etiqueta'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $hashtag_list ) : ?>
				<div class="card-bloque__hashtags">
					<?php foreach ( $hashtag_list as $tag ) : ?>
						<span class="chip"><?php echo esc_html( $tag ); ?></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $tiene_galeria ) : ?>
			<div class="card-bloque__gallery">
				<?php foreach ( $galeria as $imagen ) : ?>
					<div class="card-bloque__gallery-item">
						<?php echo wp_get_attachment_image( $imagen['ID'], 'medium_large' ); ?>
						<?php if ( ! empty( $imagen['caption'] ) ) : ?>
							<span class="card-bloque__gallery-caption"><?php echo esc_html( $imagen['caption'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php elseif ( $tiene_boton ) : ?>
			<a class="btn-primary card-bloque__cta" href="<?php echo esc_url( $boton_url ); ?>">
				<?php echo esc_html( $boton_texto ); ?>
			</a>
		<?php endif; ?>

	</div>

</article>
