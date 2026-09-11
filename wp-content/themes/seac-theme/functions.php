<?php
/**
 * SEAC Theme functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function seac_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'script', 'style' ) );
    add_theme_support( 'responsive-embeds' );

    register_nav_menus( array(
        'primary' => __( 'Menú principal', 'seac' ),
        'footer'  => __( 'Menú footer', 'seac' ),
    ) );
}
add_action( 'after_setup_theme', 'seac_theme_setup' );

function seac_theme_scripts() {
    // Tipografías y set de iconos del sistema de diseño aprobado ("Kinetic Blade").
    wp_enqueue_style( 'seac-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap', array(), null );
    wp_enqueue_style( 'seac-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,500,0,0&display=swap', array(), null );
    wp_enqueue_style( 'seac-style', get_stylesheet_uri(), array( 'seac-fonts', 'seac-material-symbols' ), '1.2' );
    wp_enqueue_script( 'seac-main', get_stylesheet_directory_uri() . '/assets/js/main.js', array(), '0.1', true );
}
add_action( 'wp_enqueue_scripts', 'seac_theme_scripts' );

/**
 * Home, Quienes somos y Contactanos no usan el contenido de bloques
 * (the_content() no se llama en ninguna plantilla): todo el contenido sale
 * de campos ACF. El editor de bloques a veces colapsa el panel de metaboxes
 * clasicos (donde ACF pinta sus campos) dejandolo con display:none, lo que
 * hace parecer que "no hay nada que editar". Usamos el editor clasico para
 * paginas y para el CPT miembro_equipo, donde los metaboxes de ACF siempre
 * se muestran a ancho completo sin ese problema.
 */
function seac_disable_block_editor( $use_block_editor, $post_type ) {
    if ( in_array( $post_type, array( 'page', 'miembro_equipo' ), true ) ) {
        return false;
    }
    return $use_block_editor;
}
add_filter( 'use_block_editor_for_post_type', 'seac_disable_block_editor', 10, 2 );

/**
 * Devuelve el ID de la página que tiene asignada una plantilla concreta
 * (p.ej. 'page-contactanos.php'). Se usa para leer campos ACF de esa
 * página desde fuera de su Loop (menú, footer, handler de formulario...).
 */
function seac_get_page_id_by_template( $template_filename ) {
    static $cache = array();

    if ( isset( $cache[ $template_filename ] ) ) {
        return $cache[ $template_filename ];
    }

    $ids = get_posts( array(
        'post_type'      => 'page',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => $template_filename,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ) );

    $cache[ $template_filename ] = $ids ? (int) $ids[0] : 0;

    return $cache[ $template_filename ];
}

/**
 * Menú de respaldo si "primary" no está configurado en Apariencia > Menús.
 */
function seac_fallback_menu() {
    $contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );
    $quienes_id  = seac_get_page_id_by_template( 'page-quienes-somos.php' );
    echo '<ul class="primary-menu">';
    echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'seac' ) . '</a></li>';
    if ( $quienes_id ) {
        echo '<li><a href="' . esc_url( get_permalink( $quienes_id ) ) . '">' . esc_html__( 'Quiénes somos', 'seac' ) . '</a></li>';
    }
    if ( $contacto_id ) {
        echo '<li><a href="' . esc_url( get_permalink( $contacto_id ) ) . '">' . esc_html__( 'Contáctanos', 'seac' ) . '</a></li>';
    }
    echo '</ul>';
}

/**
 * Página de ajustes en el admin ("Ajustes del sitio"): logo, nombre y
 * eslogan de marca (cabecera + pie de página), color principal, textos del
 * footer y redes sociales. Un único sitio para todo eso, en vez de repartirlo
 * entre el Personalizador y varias pantallas de edición.
 * Requiere ACF PRO (las Options Page no existen en ACF gratuito).
 */
function seac_register_options_page() {
    if ( ! function_exists( 'acf_add_options_page' ) ) {
        return;
    }

    acf_add_options_page( array(
        'page_title' => __( 'Ajustes del sitio', 'seac' ),
        'menu_title' => __( 'Ajustes del sitio', 'seac' ),
        'menu_slug'  => 'seac-ajustes',
        'icon_url'   => 'dashicons-admin-customizer',
        'position'   => 60,
        'capability' => 'edit_theme_options',
    ) );
}
add_action( 'acf/init', 'seac_register_options_page' );

/**
 * Lee un campo de "Ajustes del sitio" (option) con valor de respaldo si el
 * campo aun no existe o esta vacio (p.ej. ACF PRO no activo todavia).
 */
function seac_option( $field_name, $fallback = '' ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $fallback;
    }
    $value = get_field( $field_name, 'option' );
    return ( $value || '0' === $value ) ? $value : $fallback;
}

/**
 * Inyecta el color principal elegido en "Ajustes del sitio" como variable
 * CSS, sobrescribiendo el valor por defecto de style.css sin tocar codigo.
 */
function seac_output_dynamic_colors() {
    $color = seac_option( 'color_primario', '' );
    if ( ! $color ) {
        return;
    }
    printf(
        '<style id="seac-dynamic-colors">:root{--color-primary:%1$s;--color-primary-dark:color-mix(in srgb, %1$s 80%%, black);--color-primary-hover:color-mix(in srgb, %1$s 80%%, black);--color-primary-bright:color-mix(in srgb, %1$s 85%%, white);}</style>',
        esc_html( $color )
    );
}
add_action( 'wp_head', 'seac_output_dynamic_colors', 20 );

/**
 * Formulario de contacto: Contact Form 7 (formulario "Contacto SEAC", creado
 * desde el admin en Contacto > Formularios). page-contactanos.php pinta el
 * shortcode [contact-form-7] dentro de la misma tarjeta con estilo propio;
 * el CSS de fábrica de CF7 se desactiva para no chocar con style.css.
 */
add_filter( 'wpcf7_load_css', '__return_false' );

/**
 * El destinatario del formulario no se fija a mano en CF7: se lee del campo
 * ACF "email" de la página Contáctanos, para que el club solo tenga que
 * cambiarlo en un sitio (Contáctanos > Email) y no también en el plugin.
 */
function seac_cf7_dynamic_recipient( $components, $contact_form ) {
    if ( 'Contacto SEAC' !== $contact_form->title() ) {
        return $components;
    }

    $contacto_id = seac_get_page_id_by_template( 'page-contactanos.php' );
    $email       = $contacto_id ? get_field( 'email', $contacto_id ) : '';

    if ( $email && is_email( $email ) ) {
        $components['recipient'] = $email;
    }

    return $components;
}
add_filter( 'wpcf7_mail_components', 'seac_cf7_dynamic_recipient', 10, 2 );

// Custom Post Type: Miembro del equipo
function seac_register_cpt_miembro_equipo() {
    register_post_type( 'miembro_equipo', array(
        'labels' => array(
            'name'          => __( 'Miembros del equipo' ),
            'singular_name' => __( 'Miembro del equipo' ),
        ),
        'public'       => true,
        'has_archive'  => false,
        'supports'     => array( 'title', 'thumbnail' ),
        'menu_icon'    => 'dashicons-groups',
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'seac_register_cpt_miembro_equipo' );

// Carga de definición de campos ACF (si el plugin está activo)
if ( function_exists( 'acf_add_local_field_group' ) ) {
    require_once get_stylesheet_directory() . '/inc/acf-fields.php';
}
