<?php
/**
 * Partial: fila de una tabla/lista del repeater ACF "horarios"
 * (sub-campos: categoria [text], dias [text], hora_inicio / hora_fin [time_picker]).
 *
 * Se invoca con get_template_part( 'template-parts/card-horario', null, $args )
 * pasando $args = array( 'categoria' => ..., 'dias' => ..., 'hora_inicio' => ..., 'hora_fin' => ... ).
 * Las horas llegan ya formateadas por ACF según el "return_format" del campo.
 *
 * En móvil se muestra como tarjeta apilada (la hora aparece junto a la categoría);
 * a partir de 768px pasa a fila de tabla de 3 columnas (ver .card-horario en style.css).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$categoria = isset( $args['categoria'] ) ? $args['categoria'] : '';
$dias      = isset( $args['dias'] ) ? $args['dias'] : '';
$inicio    = isset( $args['hora_inicio'] ) ? $args['hora_inicio'] : '';
$fin       = isset( $args['hora_fin'] ) ? $args['hora_fin'] : '';

$hora = trim( $inicio . ( $inicio && $fin ? ' – ' : '' ) . $fin );
?>
<div class="card card-horario">
	<div class="card-horario__categoria-row">
		<span class="card-horario__icon" aria-hidden="true">
			<span class="material-symbols-outlined">calendar_month</span>
		</span>
		<div>
			<?php if ( $categoria ) : ?>
				<h3 class="card-horario__categoria"><?php echo esc_html( $categoria ); ?></h3>
			<?php endif; ?>
			<?php if ( $hora ) : ?>
				<span class="card-horario__hora--inline"><?php echo esc_html( $hora ); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $dias ) : ?>
		<p class="card-horario__dias">
			<span class="material-symbols-outlined" aria-hidden="true">calendar_today</span>
			<?php echo esc_html( $dias ); ?>
		</p>
	<?php endif; ?>

	<?php if ( $hora ) : ?>
		<div class="card-horario__hora--col">
			<span class="card-horario__hora-badge"><?php echo esc_html( $hora ); ?></span>
		</div>
	<?php endif; ?>
</div>
