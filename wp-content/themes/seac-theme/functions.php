<?php
/**
 * SEAC Theme functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * El sitio corre detrás de un proxy (Traefik/Coolify) que termina el HTTPS;
 * wp-config.php ya marca $_SERVER['HTTPS']='on' cuando llega por ahí, pero
 * las opciones "siteurl"/"home" siguen guardadas como http://. Cuando
 * WordPress necesita emitir una redirección canónica (p.ej. Polylang
 * resolviendo /en/ a la home en inglés), a veces construye esa URL en
 * http:// aunque la visita sea https, y el proxy la vuelve a mandar a
 * https, creando un bucle infinito. Forzamos aquí el esquema real de la
 * visita en cualquier redirección canónica.
 */
add_filter( 'redirect_canonical', function ( $redirect_url ) {
    if ( $redirect_url && is_ssl() ) {
        $redirect_url = set_url_scheme( $redirect_url, 'https' );
    }
    return $redirect_url;
} );

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

/**
 * Carga a mano el .mo del tema en $l10n['seac'] con la clase MO clásica.
 * No se usa load_theme_textdomain(): en WP 6.5+ pasa por
 * WP_Translation_Controller, que en este sitio (con Polylang cambiando el
 * idioma más tarde que "after_setup_theme") se queda con un registro
 * negativo para el dominio "seac" y ya no lo vuelve a cargar el resto de
 * la petición, aunque load_theme_textdomain() siga devolviendo true.
 * Rellenar $l10n directamente evita ese problema por completo.
 */
function seac_load_textdomain() {
    global $l10n;

    $locale = determine_locale();
    $mofile = get_template_directory() . "/languages/seac-{$locale}.mo";

    if ( isset( $l10n['seac'] ) && $l10n['seac'] instanceof MO && $l10n['seac']->get_filename() === $mofile ) {
        return; // Ya cargado para este idioma.
    }

    if ( ! is_readable( $mofile ) ) {
        unset( $l10n['seac'] ); // Sin traducciones para este idioma (p.ej. es_ES): usar los msgid tal cual.
        return;
    }

    $mo = new MO();
    if ( $mo->import_from_file( $mofile ) ) {
        $l10n['seac'] = $mo;
    }
}
add_action( 'wp', 'seac_load_textdomain', 20 );

/**
 * Multi-idioma (Polylang): el equipo (CPT miembro_equipo), los adjuntos y el
 * formulario de contacto (CF7) NO se gestionan como contenido "traducible"
 * por Polylang. Motivo:
 *  - miembro_equipo: la plantilla de bio en inglés se resuelve con el campo
 *    ACF "biografia_en" (con fallback al español si se deja vacío), así el
 *    club solo mantiene un post por deportista en vez de duplicarlo.
 *  - attachment: no tiene sentido duplicar cada foto solo para poder
 *    "asignarle idioma"; las imágenes son universales.
 *  - wpcf7_contact_form: se elige el formulario ES/EN "a mano" en
 *    page-contactanos.php según el idioma actual (ver más abajo).
 */
function seac_pll_exclude_post_types( $post_types ) {
    unset( $post_types['miembro_equipo'], $post_types['attachment'], $post_types['wpcf7_contact_form'] );
    return $post_types;
}
add_filter( 'pll_get_post_types', 'seac_pll_exclude_post_types' );

/**
 * Etiquetas traducibles para los valores de los campos select ACF del CPT
 * miembro_equipo (guardados en español internamente: espada/florete/sable,
 * diestro/zurdo) y para la nacionalidad (texto libre en español).
 */
function seac_arma_label( $arma ) {
    $labels = array(
        'espada'  => __( 'Espada', 'seac' ),
        'florete' => __( 'Florete', 'seac' ),
        'sable'   => __( 'Sable', 'seac' ),
    );
    return isset( $labels[ $arma ] ) ? $labels[ $arma ] : ucfirst( $arma );
}

function seac_mano_label( $mano ) {
    $labels = array(
        'diestro' => __( 'Diestro', 'seac' ),
        'zurdo'   => __( 'Zurdo', 'seac' ),
    );
    return isset( $labels[ $mano ] ) ? $labels[ $mano ] : ucfirst( $mano );
}

function seac_nacionalidad_label( $nacionalidad ) {
    $labels = array(
        'España'  => __( 'España', 'seac' ),
        'Italia'  => __( 'Italia', 'seac' ),
        'Polonia' => __( 'Polonia', 'seac' ),
    );
    return isset( $labels[ $nacionalidad ] ) ? $labels[ $nacionalidad ] : $nacionalidad;
}

/**
 * Biografía del miembro del equipo en el idioma actual: usa el campo
 * "biografia_en" si Polylang está en inglés y ese campo tiene contenido;
 * si no, cae siempre a la biografía en español.
 */
function seac_member_bio( $member_id ) {
    if ( function_exists( 'pll_current_language' ) && 'en' === pll_current_language() ) {
        $bio_en = get_field( 'biografia_en', $member_id );
        if ( $bio_en ) {
            return $bio_en;
        }
    }
    return get_field( 'biografia', $member_id );
}

function seac_theme_scripts() {
    // Tipografías y set de iconos del sistema de diseño aprobado ("Kinetic Blade").
    wp_enqueue_style( 'seac-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap', array(), null );
    wp_enqueue_style( 'seac-material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,500,0,0&display=swap', array(), null );
    wp_enqueue_style( 'seac-style', get_stylesheet_uri(), array( 'seac-fonts', 'seac-material-symbols' ), '1.7' );
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

    // Cacheado tambien por idioma: con Polylang activo, "Contáctanos" y
    // "Quiénes somos" tienen una pagina distinta por idioma con la misma
    // plantilla, y hay que devolver la del idioma que se esta viendo.
    $lang      = function_exists( 'pll_current_language' ) ? pll_current_language() : '';
    $cache_key = $template_filename . '|' . $lang;

    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $args = array(
        'post_type'      => 'page',
        'meta_key'       => '_wp_page_template',
        'meta_value'     => $template_filename,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    );
    if ( $lang ) {
        $args['lang'] = $lang;
    }

    $ids = get_posts( $args );

    $cache[ $cache_key ] = $ids ? (int) $ids[0] : 0;

    return $cache[ $cache_key ];
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
    if ( ! in_array( $contact_form->title(), array( 'Contacto SEAC', 'Contacto SEAC EN' ), true ) ) {
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

/**
 * Título del formulario CF7 a usar segun el idioma actual (ver
 * page-contactanos.php). El formulario en inglés se llama "Contacto SEAC EN".
 */
function seac_contact_form_title() {
    if ( function_exists( 'pll_current_language' ) && 'en' === pll_current_language() ) {
        return 'Contacto SEAC EN';
    }
    return 'Contacto SEAC';
}

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
