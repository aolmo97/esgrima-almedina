<?php
/**
 * Partial: tarjeta de un post del CPT "miembro_equipo".
 * Campos ACF del CPT: nacionalidad [text], arma [select: espada|florete|sable],
 * mano [select: diestro|zurdo], biografia [textarea], destacado [true_false].
 *
 * Se invoca con get_template_part( 'template-parts/card-miembro-equipo', null, $args )
 * pasando $args = array( 'id' => <post ID>, 'featured' => true|false ).
 * Cuando 'featured' es true se pinta la tarjeta grande del maestro/a
 * (dentro de .equipo-destacado, ver page-quienes-somos.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$member_id = isset( $args['id'] ) ? (int) $args['id'] : 0;
$featured  = ! empty( $args['featured'] );

if ( ! $member_id ) {
	return;
}

$nacionalidad = get_field( 'nacionalidad', $member_id );
$arma         = get_field( 'arma', $member_id );
$mano         = get_field( 'mano', $member_id );
$biografia    = seac_member_bio( $member_id );

$nacionalidad_label = $nacionalidad ? seac_nacionalidad_label( $nacionalidad ) : '';
$arma_label         = $arma ? seac_arma_label( $arma ) : '';
$mano_label         = $mano ? seac_mano_label( $mano ) : '';
?>

<?php if ( $featured ) : ?>

	<div class="equipo-destacado__inner">
		<div class="card-miembro__foto-wrap">
			<div class="card-miembro__foto">
				<div class="card-miembro__foto-inner">
					<?php
					if ( has_post_thumbnail( $member_id ) ) {
						echo get_the_post_thumbnail( $member_id, 'medium_large' );
					}
					?>
				</div>
			</div>
			<span class="card-miembro__badge"><?php esc_html_e( 'Maestro / Destacado', 'seac' ); ?></span>
		</div>

		<div class="equipo-destacado__info">
			<div class="equipo-destacado__chips">
				<?php if ( $arma_label ) : ?>
					<span class="chip"><?php echo esc_html( $arma_label ); ?></span>
				<?php endif; ?>
				<?php if ( $mano_label ) : ?>
					<span class="chip"><?php echo esc_html( $mano_label ); ?></span>
				<?php endif; ?>
				<?php if ( $nacionalidad_label ) : ?>
					<span class="chip chip--accent"><?php echo esc_html( $nacionalidad_label ); ?></span>
				<?php endif; ?>
			</div>

			<h3 class="equipo-destacado__nombre"><?php echo esc_html( get_the_title( $member_id ) ); ?></h3>
			<span class="equipo-destacado__rol"><?php esc_html_e( 'Maestro de Armas · Director Técnico', 'seac' ); ?></span>

			<?php if ( $biografia ) : ?>
				<p class="equipo-destacado__bio"><?php echo esc_html( $biografia ); ?></p>
			<?php endif; ?>
		</div>
	</div>

<?php else : ?>

	<div class="card-miembro">

		<div class="card-miembro__foto">
			<?php
			if ( has_post_thumbnail( $member_id ) ) {
				echo get_the_post_thumbnail( $member_id, 'medium' );
			}
			?>
		</div>

		<h3 class="card-miembro__nombre"><?php echo esc_html( get_the_title( $member_id ) ); ?></h3>

		<?php if ( $nacionalidad_label || $arma_label ) : ?>
			<p class="card-miembro__nacionalidad"><?php echo esc_html( trim( $nacionalidad_label . ( $nacionalidad_label && $arma_label ? ' · ' : '' ) . ( $arma_label ? sprintf( __( 'Arma: %s', 'seac' ), $arma_label ) : '' ) ) ); ?></p>
		<?php endif; ?>

		<?php if ( $mano_label ) : ?>
			<p class="card-miembro__meta"><?php echo esc_html( sprintf( __( 'Mano: %s', 'seac' ), $mano_label ) ); ?></p>
		<?php endif; ?>

		<?php if ( $biografia ) : ?>
			<p class="card-miembro__bio"><?php echo esc_html( $biografia ); ?></p>
		<?php endif; ?>

	</div>

<?php endif; ?>
