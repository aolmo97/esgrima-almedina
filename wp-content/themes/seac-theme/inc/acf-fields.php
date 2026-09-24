<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ---------- HOME ----------
acf_add_local_field_group( array(
    'key' => 'group_home',
    'title' => 'Home — Hero y secciones',
    'fields' => array(
        array( 'key' => 'field_hero_titulo', 'label' => 'Título Hero', 'name' => 'hero_titulo', 'type' => 'text' ),
        array( 'key' => 'field_hero_texto', 'label' => 'Texto Hero', 'name' => 'hero_texto', 'type' => 'wysiwyg' ),
        array( 'key' => 'field_hero_imagen', 'label' => 'Imagen Hero', 'name' => 'hero_imagen', 'type' => 'image' ),
        array(
            'key' => 'field_modalidades', 'label' => 'Modalidades', 'name' => 'modalidades', 'type' => 'repeater',
            'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_mod_nombre', 'label' => 'Nombre', 'name' => 'nombre', 'type' => 'text' ),
                array( 'key' => 'field_mod_icono', 'label' => 'Icono', 'name' => 'icono', 'type' => 'image' ),
                array( 'key' => 'field_mod_desc', 'label' => 'Descripción', 'name' => 'descripcion', 'type' => 'textarea' ),
            ),
        ),
        array(
            'key' => 'field_horarios', 'label' => 'Horarios', 'name' => 'horarios', 'type' => 'repeater',
            'layout' => 'table',
            'sub_fields' => array(
                array( 'key' => 'field_hor_categoria', 'label' => 'Categoría de edad', 'name' => 'categoria', 'type' => 'text' ),
                array( 'key' => 'field_hor_dias', 'label' => 'Días', 'name' => 'dias', 'type' => 'text' ),
                array( 'key' => 'field_hor_inicio', 'label' => 'Hora inicio', 'name' => 'hora_inicio', 'type' => 'time_picker', 'display_format' => 'H:i', 'return_format' => 'H:i' ),
                array( 'key' => 'field_hor_fin', 'label' => 'Hora fin', 'name' => 'hora_fin', 'type' => 'time_picker', 'display_format' => 'H:i', 'return_format' => 'H:i' ),
            ),
        ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'front-page.php' ) ) ),
) );

// ---------- QUIÉNES SOMOS ----------
acf_add_local_field_group( array(
    'key' => 'group_quienes_somos',
    'title' => 'Quiénes somos — Historia',
    'fields' => array(
        array(
            'key' => 'field_historia_bloques', 'label' => 'Bloques de historia', 'name' => 'historia_bloques', 'type' => 'repeater',
            'layout' => 'block',
            'sub_fields' => array(
                array( 'key' => 'field_bloque_etiqueta', 'label' => 'Etiqueta (opcional)', 'name' => 'etiqueta', 'type' => 'text',
                    'instructions' => 'Texto pequeño junto al número de bloque. Ej: "Génesis y Raíces Califales".' ),
                array( 'key' => 'field_bloque_texto', 'label' => 'Texto', 'name' => 'texto', 'type' => 'wysiwyg',
                    'instructions' => 'Incluye aquí el título (como Encabezado) y el párrafo del bloque.' ),
                array( 'key' => 'field_bloque_galeria', 'label' => 'Galería (opcional)', 'name' => 'galeria', 'type' => 'gallery',
                    'instructions' => 'La leyenda de cada foto (opcional) se edita en la biblioteca de medios, campo "Leyenda".' ),

                array( 'key' => 'field_bloque_tab_destacado', 'label' => 'Caja destacada (opcional)', 'type' => 'tab' ),
                array( 'key' => 'field_bloque_destacado_icono', 'label' => 'Icono (Material Symbols)', 'name' => 'destacado_icono', 'type' => 'text',
                    'instructions' => 'Nombre de icono de Google Material Symbols, ej: "token". https://fonts.google.com/icons' ),
                array( 'key' => 'field_bloque_destacado_titulo', 'label' => 'Título de la caja', 'name' => 'destacado_titulo', 'type' => 'text' ),
                array( 'key' => 'field_bloque_destacado_texto', 'label' => 'Texto de la caja', 'name' => 'destacado_texto', 'type' => 'text' ),

                array( 'key' => 'field_bloque_tab_stats', 'label' => 'Estadísticas destacadas (opcional)', 'type' => 'tab' ),
                array(
                    'key' => 'field_bloque_estadisticas', 'label' => 'Estadísticas', 'name' => 'estadisticas', 'type' => 'repeater',
                    'layout' => 'table', 'button_label' => 'Añadir estadística',
                    'sub_fields' => array(
                        array( 'key' => 'field_stat_icono', 'label' => 'Icono', 'name' => 'icono', 'type' => 'text', 'instructions' => 'Nombre de Material Symbols, ej: "speed".' ),
                        array( 'key' => 'field_stat_valor', 'label' => 'Valor', 'name' => 'valor', 'type' => 'text', 'instructions' => 'Ej: "0.18s"' ),
                        array( 'key' => 'field_stat_etiqueta', 'label' => 'Etiqueta', 'name' => 'etiqueta', 'type' => 'text', 'instructions' => 'Ej: "Tiempo de Reacción"' ),
                    ),
                ),

                array( 'key' => 'field_bloque_tab_hashtags', 'label' => 'Hashtags (opcional)', 'type' => 'tab' ),
                array( 'key' => 'field_bloque_hashtags', 'label' => 'Hashtags', 'name' => 'hashtags', 'type' => 'text',
                    'instructions' => 'Separados por comas. Ej: #FIE_HOMOLOGATED, #ESPADA_Y_FLORETE' ),

                array( 'key' => 'field_bloque_tab_boton', 'label' => 'Botón (opcional)', 'type' => 'tab' ),
                array( 'key' => 'field_bloque_boton_texto', 'label' => 'Texto del botón', 'name' => 'boton_texto', 'type' => 'text',
                    'instructions' => 'Deja vacío si este bloque no lleva botón.' ),
                array( 'key' => 'field_bloque_boton_url', 'label' => 'Enlace del botón', 'name' => 'boton_url', 'type' => 'url',
                    'conditional_logic' => array( array( array( 'field' => 'field_bloque_boton_texto', 'operator' => '!=empty' ) ) ) ),
            ),
        ),
        array( 'key' => 'field_galeria_general', 'label' => 'Galería general', 'name' => 'galeria_general', 'type' => 'gallery' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-quienes-somos.php' ) ) ),
) );

// ---------- CPT MIEMBRO DEL EQUIPO ----------
acf_add_local_field_group( array(
    'key' => 'group_miembro_equipo',
    'title' => 'Datos del miembro del equipo',
    'fields' => array(
        array( 'key' => 'field_me_nacionalidad', 'label' => 'Nacionalidad', 'name' => 'nacionalidad', 'type' => 'text' ),
        array(
            'key' => 'field_me_arma', 'label' => 'Arma', 'name' => 'arma', 'type' => 'select',
            'choices' => array( 'espada' => 'Espada', 'florete' => 'Florete', 'sable' => 'Sable' ),
        ),
        array( 'key' => 'field_me_mano', 'label' => 'Mano', 'name' => 'mano', 'type' => 'select',
            'choices' => array( 'diestro' => 'Diestro', 'zurdo' => 'Zurdo' ) ),
        array( 'key' => 'field_me_bio', 'label' => 'Biografía', 'name' => 'biografia', 'type' => 'textarea' ),
        array( 'key' => 'field_me_bio_en', 'label' => 'Biografía (inglés)', 'name' => 'biografia_en', 'type' => 'textarea',
            'instructions' => 'Opcional. Si se deja vacío, la web en inglés muestra la biografía en español.' ),
        array( 'key' => 'field_me_destacado', 'label' => '¿Es el maestro / destacado?', 'name' => 'destacado', 'type' => 'true_false' ),
    ),
    'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'miembro_equipo' ) ) ),
) );

// ---------- PÁGINA DE ARMA (Florete / Espada / Sable) ----------
acf_add_local_field_group( array(
    'key' => 'group_pagina_arma',
    'title' => 'Página de arma — Vídeos',
    'fields' => array(
        array( 'key' => 'field_arma_subtitulo', 'label' => 'Subtítulo / introducción', 'name' => 'subtitulo', 'type' => 'textarea',
            'instructions' => 'Texto breve bajo el título de la página. Opcional.', 'rows' => 2 ),
        array(
            'key' => 'field_arma_videos', 'label' => 'Vídeos', 'name' => 'videos', 'type' => 'repeater',
            'layout' => 'block', 'button_label' => 'Añadir vídeo',
            'sub_fields' => array(
                array( 'key' => 'field_arma_video_titulo', 'label' => 'Título', 'name' => 'titulo', 'type' => 'text' ),
                array( 'key' => 'field_arma_video_url', 'label' => 'Vídeo (YouTube / Vimeo)', 'name' => 'video_url', 'type' => 'oembed' ),
                array( 'key' => 'field_arma_video_desc', 'label' => 'Descripción (opcional)', 'name' => 'descripcion', 'type' => 'textarea', 'rows' => 2 ),
            ),
        ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-arma.php' ) ) ),
) );

// ---------- CONTÁCTANOS ----------
acf_add_local_field_group( array(
    'key' => 'group_contactanos',
    'title' => 'Contáctanos — Datos',
    'fields' => array(
        array( 'key' => 'field_contacto_direccion', 'label' => 'Dirección', 'name' => 'direccion', 'type' => 'text' ),
        array( 'key' => 'field_contacto_telefono', 'label' => 'Teléfono', 'name' => 'telefono', 'type' => 'text' ),
        array( 'key' => 'field_contacto_email', 'label' => 'Email', 'name' => 'email', 'type' => 'email' ),
        array( 'key' => 'field_contacto_mapa', 'label' => 'Mapa (Google Map)', 'name' => 'mapa', 'type' => 'google_map' ),
    ),
    'location' => array( array( array( 'param' => 'page_template', 'operator' => '==', 'value' => 'page-contactanos.php' ) ) ),
) );

// ---------- AJUSTES DEL SITIO (Options Page: cabecera, pie de página, color, redes) ----------
if ( function_exists( 'acf_add_options_page' ) ) {
    acf_add_local_field_group( array(
        'key' => 'group_ajustes_sitio',
        'title' => 'Cabecera, pie de página y marca',
        'fields' => array(

            array( 'key' => 'field_ajustes_tab_marca', 'label' => 'Marca', 'type' => 'tab' ),
            array( 'key' => 'field_logo_sitio', 'label' => 'Logotipo', 'name' => 'logo_sitio', 'type' => 'image',
                'return_format' => 'id', 'instructions' => 'Se usa en la cabecera y en el pie de página.' ),
            array( 'key' => 'field_nombre_marca', 'label' => 'Nombre de la marca', 'name' => 'nombre_marca', 'type' => 'text',
                'default_value' => 'Sala de Esgrima Almedina', 'instructions' => 'Texto junto al logo en la cabecera.' ),
            array( 'key' => 'field_eslogan_marca', 'label' => 'Eslogan / línea pequeña', 'name' => 'eslogan_marca', 'type' => 'text',
                'default_value' => 'Córdoba · S.E.A.C.' ),
            array( 'key' => 'field_color_primario', 'label' => 'Color principal', 'name' => 'color_primario', 'type' => 'color_picker',
                'default_value' => '#16a34a', 'instructions' => 'Botones, enlaces y acentos en toda la web.' ),

            array( 'key' => 'field_ajustes_tab_footer', 'label' => 'Pie de página', 'type' => 'tab' ),
            array( 'key' => 'field_footer_descripcion', 'label' => 'Descripción del club', 'name' => 'footer_descripcion', 'type' => 'textarea',
                'default_value' => 'Forjando esgrimistas y pasión deportiva en Córdoba desde 2012. Precisión táctica, disciplina atlética y rendimiento de élite.', 'rows' => 3 ),
            array( 'key' => 'field_footer_anio', 'label' => 'Año de fundación (badge "EST.")', 'name' => 'footer_anio_fundacion', 'type' => 'text',
                'default_value' => '2012' ),
            array( 'key' => 'field_footer_ubicacion', 'label' => 'Ubicación (texto corto)', 'name' => 'footer_ubicacion', 'type' => 'text',
                'default_value' => '37.8882° N, 4.7794° W · Córdoba' ),

            array( 'key' => 'field_ajustes_tab_redes', 'label' => 'Redes sociales', 'type' => 'tab' ),
            array( 'key' => 'field_red_instagram', 'label' => 'Instagram', 'name' => 'red_instagram', 'type' => 'url' ),
            array( 'key' => 'field_red_facebook', 'label' => 'Facebook', 'name' => 'red_facebook', 'type' => 'url' ),
            array( 'key' => 'field_red_youtube', 'label' => 'YouTube', 'name' => 'red_youtube', 'type' => 'url' ),
            array( 'key' => 'field_red_whatsapp', 'label' => 'WhatsApp (enlace wa.me)', 'name' => 'red_whatsapp', 'type' => 'url' ),

        ),
        'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => 'seac-ajustes' ) ) ),
    ) );
}
