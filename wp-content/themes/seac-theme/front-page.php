<?php
/**
 * Template Name: Home
 *
 * Home del club. Requiere:
 *  1) Ajustes > Lectura > "Tu página de inicio muestra" = Una página estática,
 *     con la página de inicio apuntando a esta plantilla.
 *  2) O bien, en Atributos de página de esa página, seleccionar "Home" manualmente.
 *
 * Diseño: design-reference/home_sala_de_esgrima_almedina/code.html (Stitch,
 * sistema "Kinetic Blade" modo oscuro). Breakpoints: 768px y 1024px.
 *
 * Campos ACF (grupo "Home — Hero y secciones", ver inc/acf-fields.php):
 *  - hero_titulo    [text]
 *  - hero_texto     [wysiwyg]
 *  - hero_imagen    [image]
 *  - modalidades    [repeater: nombre, icono, descripcion]
 *  - horarios       [repeater: categoria, dias, hora_inicio, hora_fin]
 */
if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$hero_titulo = get_field( 'hero_titulo' );
$hero_texto  = get_field( 'hero_texto' );
$hero_imagen = get_field( 'hero_imagen' );

$quienes_id  = seac_get_page_id_by_template( 'page-quienes-somos.php' );
$contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );
?>

<main>

	<!-- ===== HERO ===== -->
	<section class="hero">
		<div class="container hero__inner">

			<div class="hero__text">
				<span class="hero__badge">
					<span class="material-symbols-outlined" aria-hidden="true">swords</span>
					<?php esc_html_e( 'Esgrima olímpica e histórica en Andalucía', 'seac' ); ?>
				</span>

				<?php if ( $hero_titulo ) : ?>
					<h1 class="hero__title"><?php echo esc_html( $hero_titulo ); ?></h1>
				<?php endif; ?>

				<?php if ( $hero_texto ) : ?>
					<div class="hero__desc"><?php echo wp_kses_post( $hero_texto ); ?></div>
				<?php endif; ?>

				<div class="hero__actions">
					<?php if ( $contacto_id ) : ?>
						<a class="btn-primary" href="<?php echo esc_url( get_permalink( $contacto_id ) ); ?>">
							<span class="material-symbols-outlined" aria-hidden="true">bolt</span>
							<?php esc_html_e( 'Contáctanos', 'seac' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( $quienes_id ) : ?>
						<a class="btn-secondary" href="<?php echo esc_url( get_permalink( $quienes_id ) ); ?>">
							<?php esc_html_e( 'Conócenos', 'seac' ); ?>
							<span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
						</a>
					<?php endif; ?>
				</div>

				<div class="hero__stats">
					<div>
						<span class="hero__stat-value">2012</span>
						<span class="hero__stat-label"><?php esc_html_e( 'Fundación club', 'seac' ); ?></span>
					</div>
					<div>
						<span class="hero__stat-value hero__stat-value--neutral">3</span>
						<span class="hero__stat-label"><?php esc_html_e( 'Armas oficiales', 'seac' ); ?></span>
					</div>
					<div>
						<span class="hero__stat-value hero__stat-value--neutral">100%</span>
						<span class="hero__stat-label"><?php esc_html_e( 'Pista homologada', 'seac' ); ?></span>
					</div>
				</div>
			</div>

			<?php if ( ! empty( $hero_imagen['ID'] ) ) : ?>
				<div class="hero__frame">
					<span class="hero__frame-corner hero__frame-corner--tl"></span>
					<span class="hero__frame-corner hero__frame-corner--tr"></span>
					<span class="hero__frame-corner hero__frame-corner--bl"></span>
					<span class="hero__frame-corner hero__frame-corner--br"></span>
					<div class="hero__image">
						<?php echo wp_get_attachment_image( $hero_imagen['ID'], 'large' ); ?>
						<span class="hero__image-tag"><?php esc_html_e( 'Registro activo', 'seac' ); ?></span>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</section>

	<!-- ===== MODALIDADES ===== -->
	<?php if ( have_rows( 'modalidades' ) ) : ?>
		<section id="modalidades" class="section section-modalidades">
			<div class="container">

				<div class="section__header">
					<div>
						<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Disciplinas oficiales', 'seac' ); ?></span>
						<h2 class="section__title"><?php esc_html_e( 'Modalidades de combate', 'seac' ); ?></h2>
						<p class="section__desc"><?php esc_html_e( 'Domina las tres armas de la esgrima moderna con maestros titulados por la RFEE.', 'seac' ); ?></p>
					</div>
				</div>

				<div class="grid grid-modalidades">
					<?php $mod_index = 0; while ( have_rows( 'modalidades' ) ) : the_row(); $mod_index++; ?>
						<?php
						get_template_part( 'template-parts/card-modalidad', null, array(
							'index'       => $mod_index,
							'nombre'      => get_sub_field( 'nombre' ),
							'icono'       => get_sub_field( 'icono' ),
							'descripcion' => get_sub_field( 'descripcion' ),
						) );
						?>
					<?php endwhile; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- ===== HORARIOS ===== -->
	<?php if ( have_rows( 'horarios' ) ) : ?>
		<section id="horarios" class="section section-horarios">
			<div class="container">

				<div class="section__header">
					<div>
						<span class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Calendario de pista', 'seac' ); ?></span>
						<h2 class="section__title"><?php esc_html_e( 'Horarios y grupos', 'seac' ); ?></h2>
						<p class="section__desc"><?php esc_html_e( 'Entrenamientos estructurados por nivel y grupo de edad en pista homologada.', 'seac' ); ?></p>
					</div>
				</div>

				<div class="section-horarios__table">
					<div class="horarios__head">
						<span><?php esc_html_e( 'Categoría y grupo', 'seac' ); ?></span>
						<span><?php esc_html_e( 'Días de entrenamiento', 'seac' ); ?></span>
						<span style="text-align:right;"><?php esc_html_e( 'Horario', 'seac' ); ?></span>
					</div>

					<?php while ( have_rows( 'horarios' ) ) : the_row(); ?>
						<?php
						get_template_part( 'template-parts/card-horario', null, array(
							'categoria'   => get_sub_field( 'categoria' ),
							'dias'        => get_sub_field( 'dias' ),
							'hora_inicio' => get_sub_field( 'hora_inicio' ),
							'hora_fin'    => get_sub_field( 'hora_fin' ),
						) );
						?>
					<?php endwhile; ?>
				</div>

				<div class="horarios-cta">
					<div>
						<span class="horarios-cta__eyebrow">
							<span class="material-symbols-outlined" aria-hidden="true">task_alt</span>
							<?php esc_html_e( 'Equipación completa proporcionada por el club para principiantes', 'seac' ); ?>
						</span>
						<h3 class="horarios-cta__title"><?php esc_html_e( 'Primera clase de prueba sin compromiso de equipación.', 'seac' ); ?></h3>
						<p class="horarios-cta__desc"><?php esc_html_e( 'Ven con ropa deportiva cómoda. Nosotros te facilitamos careta, chaquetilla, guante y espada reglamentaria.', 'seac' ); ?></p>
					</div>
					<?php if ( $contacto_id ) : ?>
						<a class="btn-primary" href="<?php echo esc_url( get_permalink( $contacto_id ) ); ?>">
							<span class="material-symbols-outlined" aria-hidden="true">sports</span>
							<?php esc_html_e( 'Solicita tu clase de prueba', 'seac' ); ?>
						</a>
					<?php endif; ?>
				</div>

			</div>
		</section>
	<?php endif; ?>

</main>

<?php get_footer(); ?>
