<?php
/**
 * Plugin Name: AlmadenBookster
 * Description: Plugin personalizado AlmadenBookster.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: almaden-bookster
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// --- Módulos de Google Fonts (Admin) ---
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-fonts.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-fonts-page.php';

// Modulos CPT
require_once plugin_dir_path( __FILE__ ) . 'includes/cpt.php';

// --- Frontend Booklist y Creación Automática de Página ---
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend.php';

// --- AJAX Guardar Libro en Base de Datos ---

function almaden_bookster_save_book_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	// Validar nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_book_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$title    = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
	$chapters_raw = isset( $_POST['chapters'] ) ? wp_unslash( $_POST['chapters'] ) : '';
	$chapters = json_decode( $chapters_raw, true );

	if ( ! is_array( $chapters ) ) {
		wp_send_json_error( 'Datos de capítulos inválidos.' );
	}

	// Sanitizar y Guardar Capítulos en CPT
	$updated_chapters = array();
	$menu_order = 1;
	$incoming_ids = array();

	foreach ( $chapters as $chapter ) {
		$chapter_id = sanitize_text_field( $chapter['id'] );
		$chapter_title = sanitize_text_field( $chapter['title'] );
		$chapter_content = wp_kses_post( $chapter['content'] );
		
		// Meta-datos locales
		$parity_image          = isset( $chapter['parity_image'] ) ? sanitize_text_field( $chapter['parity_image'] ) : '';
		$hide_title            = isset( $chapter['hide_title'] ) ? sanitize_text_field( $chapter['hide_title'] ) : '0';
		$custom_running_header = isset( $chapter['custom_running_header'] ) ? sanitize_text_field( $chapter['custom_running_header'] ) : '';
		$drop_cap_enabled      = isset( $chapter['drop_cap_enabled'] ) ? sanitize_text_field( $chapter['drop_cap_enabled'] ) : '0';
		$disable_hyphenation   = isset( $chapter['disable_hyphenation'] ) ? sanitize_text_field( $chapter['disable_hyphenation'] ) : '0';
		$page_one_vertical     = isset( $chapter['page_one_vertical'] ) ? sanitize_text_field( $chapter['page_one_vertical'] ) : 'top';
		$start_parity          = isset( $chapter['start_parity'] ) ? sanitize_text_field( $chapter['start_parity'] ) : 'any';
		$first_page_header_type = isset( $chapter['first_page_header_type'] ) ? sanitize_text_field( $chapter['first_page_header_type'] ) : 'blank';
		$first_page_header_custom = isset( $chapter['first_page_header_custom'] ) ? sanitize_text_field( $chapter['first_page_header_custom'] ) : '';
		$first_page_footer_type = isset( $chapter['first_page_footer_type'] ) ? sanitize_text_field( $chapter['first_page_footer_type'] ) : 'page_number';
		$first_page_footer_custom = isset( $chapter['first_page_footer_custom'] ) ? sanitize_text_field( $chapter['first_page_footer_custom'] ) : '';
		$parity_image_mode     = isset( $chapter['parity_image_mode'] ) ? sanitize_text_field( $chapter['parity_image_mode'] ) : 'content';
		$parity_image_width    = isset( $chapter['parity_image_width'] ) ? sanitize_text_field( $chapter['parity_image_width'] ) : '';
		$parity_image_height   = isset( $chapter['parity_image_height'] ) ? sanitize_text_field( $chapter['parity_image_height'] ) : '';
		$is_toc                = isset( $chapter['is_toc'] ) ? sanitize_text_field( $chapter['is_toc'] ) : '0';
		
		// TOC metadata
		$toc_font_family       = isset( $chapter['toc_font_family'] ) ? sanitize_text_field( $chapter['toc_font_family'] ) : '';
		$toc_font_size         = isset( $chapter['toc_font_size'] ) ? sanitize_text_field( $chapter['toc_font_size'] ) : '';
		$toc_enumerate         = isset( $chapter['toc_enumerate'] ) ? sanitize_text_field( $chapter['toc_enumerate'] ) : 'none';
		$toc_font_style        = isset( $chapter['toc_font_style'] ) ? sanitize_text_field( $chapter['toc_font_style'] ) : 'normal';
		$toc_font_weight       = isset( $chapter['toc_font_weight'] ) ? sanitize_text_field( $chapter['toc_font_weight'] ) : 'normal';
		$toc_text_transform    = isset( $chapter['toc_text_transform'] ) ? sanitize_text_field( $chapter['toc_text_transform'] ) : 'none';
		$toc_letter_spacing    = isset( $chapter['toc_letter_spacing'] ) ? sanitize_text_field( $chapter['toc_letter_spacing'] ) : '';
		$toc_line_height       = isset( $chapter['toc_line_height'] ) ? sanitize_text_field( $chapter['toc_line_height'] ) : '';
		$toc_leader_style      = isset( $chapter['toc_leader_style'] ) ? sanitize_text_field( $chapter['toc_leader_style'] ) : 'dotted';
		$toc_leader_position   = isset( $chapter['toc_leader_position'] ) ? sanitize_text_field( $chapter['toc_leader_position'] ) : 'middle';
		
		$toc_title_align       = isset( $chapter['toc_title_align'] ) ? sanitize_text_field( $chapter['toc_title_align'] ) : '';
		$toc_page_one_vertical = isset( $chapter['toc_page_one_vertical'] ) ? sanitize_text_field( $chapter['toc_page_one_vertical'] ) : '';
		$toc_title_font_family = isset( $chapter['toc_title_font_family'] ) ? sanitize_text_field( $chapter['toc_title_font_family'] ) : '';
		$toc_title_font_size   = isset( $chapter['toc_title_font_size'] ) ? sanitize_text_field( $chapter['toc_title_font_size'] ) : '';
		$toc_title_font_style  = isset( $chapter['toc_title_font_style'] ) ? sanitize_text_field( $chapter['toc_title_font_style'] ) : '';
		$toc_title_text_transform = isset( $chapter['toc_title_text_transform'] ) ? sanitize_text_field( $chapter['toc_title_text_transform'] ) : '';
		$toc_title_font_weight = isset( $chapter['toc_title_font_weight'] ) ? sanitize_text_field( $chapter['toc_title_font_weight'] ) : '';
		$toc_title_padding_top = isset( $chapter['toc_title_padding_top'] ) ? sanitize_text_field( $chapter['toc_title_padding_top'] ) : '';
		$toc_title_padding_bottom = isset( $chapter['toc_title_padding_bottom'] ) ? sanitize_text_field( $chapter['toc_title_padding_bottom'] ) : '';
		$toc_title_line_height = isset( $chapter['toc_title_line_height'] ) ? sanitize_text_field( $chapter['toc_title_line_height'] ) : '';
		
		$post_id = 0;
		$is_new = false;

		// Si el ID no es numérico (ej. 'cap-3'), es un capítulo nuevo
		if ( ! is_numeric( $chapter_id ) ) {
			$post_id = wp_insert_post( array(
				'post_title'   => $chapter_title,
				'post_content' => $chapter_content,
				'post_status'  => 'publish',
				'post_type'    => 'book_chapter',
				'post_parent'  => $book_id,
				'menu_order'   => $menu_order++,
			) );
			$is_new = true;
		} else {
			// Es un capítulo existente, actualizarlo
			$post_id = intval( $chapter_id );
			wp_update_post( array(
				'ID'           => $post_id,
				'post_title'   => $chapter_title,
				'post_content' => $chapter_content,
				'menu_order'   => $menu_order++,
			) );
		}

		if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
			// Guardar meta-datos
			update_post_meta( $post_id, '_parity_image', $parity_image );
			update_post_meta( $post_id, '_hide_title', $hide_title );
			update_post_meta( $post_id, '_custom_running_header', $custom_running_header );
			update_post_meta( $post_id, '_drop_cap_enabled', $drop_cap_enabled );
			update_post_meta( $post_id, '_disable_hyphenation', $disable_hyphenation );
			update_post_meta( $post_id, '_page_one_vertical', $page_one_vertical );
			update_post_meta( $post_id, '_start_parity', $start_parity );
			update_post_meta( $post_id, '_first_page_header_type', $first_page_header_type );
			update_post_meta( $post_id, '_first_page_header_custom', $first_page_header_custom );
			update_post_meta( $post_id, '_first_page_footer_type', $first_page_footer_type );
			update_post_meta( $post_id, '_first_page_footer_custom', $first_page_footer_custom );
			update_post_meta( $post_id, '_parity_image_mode', $parity_image_mode );
			update_post_meta( $post_id, '_parity_image_width', $parity_image_width );
			update_post_meta( $post_id, '_parity_image_height', $parity_image_height );
			update_post_meta( $post_id, '_is_toc', $is_toc );
			
			// TOC metadata
			update_post_meta( $post_id, '_toc_font_family', $toc_font_family );
			update_post_meta( $post_id, '_toc_font_size', $toc_font_size );
			update_post_meta( $post_id, '_toc_enumerate', $toc_enumerate );
			update_post_meta( $post_id, '_toc_font_style', $toc_font_style );
			update_post_meta( $post_id, '_toc_font_weight', $toc_font_weight );
			update_post_meta( $post_id, '_toc_text_transform', $toc_text_transform );
			update_post_meta( $post_id, '_toc_letter_spacing', $toc_letter_spacing );
			update_post_meta( $post_id, '_toc_line_height', $toc_line_height );
			update_post_meta( $post_id, '_toc_leader_style', $toc_leader_style );
			update_post_meta( $post_id, '_toc_leader_position', $toc_leader_position );
			
			update_post_meta( $post_id, '_toc_title_align', $toc_title_align );
			update_post_meta( $post_id, '_toc_page_one_vertical', $toc_page_one_vertical );
			update_post_meta( $post_id, '_toc_title_font_family', $toc_title_font_family );
			update_post_meta( $post_id, '_toc_title_font_size', $toc_title_font_size );
			update_post_meta( $post_id, '_toc_title_font_style', $toc_title_font_style );
			update_post_meta( $post_id, '_toc_title_text_transform', $toc_title_text_transform );
			update_post_meta( $post_id, '_toc_title_font_weight', $toc_title_font_weight );
			update_post_meta( $post_id, '_toc_title_padding_top', $toc_title_padding_top );
			update_post_meta( $post_id, '_toc_title_padding_bottom', $toc_title_padding_bottom );
			update_post_meta( $post_id, '_toc_title_line_height', $toc_title_line_height );

			$incoming_ids[] = $post_id;
			
			$chapter_response = array(
				'id'                    => strval( $post_id ),
				'title'                 => $chapter_title,
				'content'               => $chapter_content,
				'parity_image'          => $parity_image,
				'hide_title'            => $hide_title,
				'custom_running_header' => $custom_running_header,
				'drop_cap_enabled'      => $drop_cap_enabled,
				'disable_hyphenation'   => $disable_hyphenation,
				'page_one_vertical'     => $page_one_vertical,
				'start_parity'          => $start_parity,
				'first_page_header_type'  => $first_page_header_type,
				'first_page_header_custom' => $first_page_header_custom,
				'first_page_footer_type'  => $first_page_footer_type,
				'first_page_footer_custom' => $first_page_footer_custom,
				'parity_image_mode'     => $parity_image_mode,
				'parity_image_width'    => $parity_image_width,
				'parity_image_height'   => $parity_image_height,
				'is_toc'                => $is_toc,
				'toc_font_family'       => $toc_font_family,
				'toc_font_size'         => $toc_font_size,
				'toc_enumerate'         => $toc_enumerate,
				'toc_font_style'        => $toc_font_style,
				'toc_font_weight'       => $toc_font_weight,
				'toc_text_transform'    => $toc_text_transform,
				'toc_letter_spacing'    => $toc_letter_spacing,
				'toc_line_height'       => $toc_line_height,
				'toc_leader_style'      => $toc_leader_style,
				'toc_leader_position'   => $toc_leader_position,
				'toc_title_align'       => $toc_title_align,
				'toc_page_one_vertical' => $toc_page_one_vertical,
				'toc_title_font_family' => $toc_title_font_family,
				'toc_title_font_size'   => $toc_title_font_size,
				'toc_title_font_style'  => $toc_title_font_style,
				'toc_title_text_transform' => $toc_title_text_transform,
				'toc_title_font_weight' => $toc_title_font_weight,
				'toc_title_padding_top' => $toc_title_padding_top,
				'toc_title_padding_bottom' => $toc_title_padding_bottom,
				'toc_title_line_height' => $toc_title_line_height,
			);
			
			if ( $is_new ) {
				$chapter_response['old_id'] = $chapter_id;
			}
			
			$updated_chapters[] = $chapter_response;
		}
	}

	// Eliminar capítulos que ya no están en el payload (fueron borrados en JS)
	if ( ! empty( $incoming_ids ) ) {
		$existing_chapters = get_posts( array(
			'post_type'      => 'book_chapter',
			'post_parent'    => $book_id,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		
		foreach ( $existing_chapters as $existing_id ) {
			if ( ! in_array( $existing_id, $incoming_ids ) ) {
				wp_delete_post( $existing_id, true ); // Forzar borrado físico
			}
		}
	}

	// Actualizar título del libro (post)
	if ( ! empty( $title ) ) {
		wp_update_post( array(
			'ID'         => $book_id,
			'post_title' => $title,
		) );
	}

	wp_send_json_success( array( 
		'message'  => 'Libro guardado con éxito.',
		'chapters' => $updated_chapters 
	) );
}
add_action( 'wp_ajax_almaden_save_book', 'almaden_bookster_save_book_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book', 'almaden_bookster_save_book_ajax' );

// --- Crear Tabla Especial de Ajustes de PDF ---

function almaden_bookster_create_settings_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	
	if ( get_option( 'almaden_bookster_db_version' ) !== '1.8.5' ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			book_id bigint(20) NOT NULL,
			unit varchar(10) DEFAULT 'cm' NOT NULL,
			page_size varchar(20) DEFAULT 'A4' NOT NULL,
			page_width float DEFAULT 21.0 NOT NULL,
			page_height float DEFAULT 29.7 NOT NULL,
			margin_top float DEFAULT 2.5 NOT NULL,
			margin_bottom float DEFAULT 2.5 NOT NULL,
			margin_left float DEFAULT 2.0 NOT NULL,
			margin_right float DEFAULT 2.0 NOT NULL,
			margin_left_odd float DEFAULT 2.0 NOT NULL,
			margin_right_odd float DEFAULT 2.0 NOT NULL,
			margin_left_even float DEFAULT 2.0 NOT NULL,
			margin_right_even float DEFAULT 2.0 NOT NULL,
			padding_top float DEFAULT 0.0 NOT NULL,
			padding_bottom float DEFAULT 0.0 NOT NULL,
			padding_left float DEFAULT 0.0 NOT NULL,
			padding_right float DEFAULT 0.0 NOT NULL,
			bleeding float DEFAULT 0.0 NOT NULL,
			font_family_content varchar(50) DEFAULT 'Merriweather' NOT NULL,
			font_size_content float DEFAULT 11.5 NOT NULL,
			line_height_content float DEFAULT 1.65 NOT NULL,
			content_text_align varchar(20) DEFAULT 'justify' NOT NULL,
			content_hyphenation tinyint(1) DEFAULT 1 NOT NULL,
			content_language varchar(10) DEFAULT 'es' NOT NULL,
			content_paragraph_indent float DEFAULT 0.0 NOT NULL,
			content_paragraph_spacing float DEFAULT 14.0 NOT NULL,
			font_family_headings varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h1 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h2 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h3 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_weight_h1 varchar(20) DEFAULT 'bold' NOT NULL,
			font_weight_h2 varchar(20) DEFAULT 'bold' NOT NULL,
			font_weight_h3 varchar(20) DEFAULT 'bold' NOT NULL,
			font_size_h1 float DEFAULT 24.0 NOT NULL,
			font_size_h2 float DEFAULT 16.0 NOT NULL,
			font_size_h3 float DEFAULT 13.0 NOT NULL,
			header_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			header_font_size float DEFAULT 8.5 NOT NULL,
			header_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			header_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			header_letter_spacing float DEFAULT 0.1 NOT NULL,
			header_even_type varchar(20) DEFAULT 'book_title' NOT NULL,
			header_even_custom varchar(255) DEFAULT '' NOT NULL,
			header_odd_type varchar(20) DEFAULT 'chapter_title' NOT NULL,
			header_odd_custom varchar(255) DEFAULT '' NOT NULL,
			footer_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			footer_font_size float DEFAULT 9.0 NOT NULL,
			footer_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			footer_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			footer_letter_spacing float DEFAULT 0.0 NOT NULL,
			footer_even_type varchar(20) DEFAULT 'page_number' NOT NULL,
			footer_odd_type varchar(20) DEFAULT 'page_number' NOT NULL,
			first_page_header_type varchar(20) DEFAULT 'blank' NOT NULL,
			first_page_header_custom varchar(255) DEFAULT '' NOT NULL,
			first_page_footer_type varchar(20) DEFAULT 'page_number' NOT NULL,
			first_page_footer_custom varchar(255) DEFAULT '' NOT NULL,
			chapter_start_parity varchar(10) DEFAULT 'any' NOT NULL,
			parity_image_mode varchar(20) DEFAULT 'content' NOT NULL,
			chapter_page_one_align varchar(10) DEFAULT 'center' NOT NULL,
			chapter_page_one_vertical varchar(10) DEFAULT 'top' NOT NULL,
			chapter_title_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			chapter_title_font_size float DEFAULT 24.0 NOT NULL,
			chapter_title_font_weight varchar(20) DEFAULT 'bold' NOT NULL,
			chapter_title_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_title_align varchar(20) DEFAULT 'center' NOT NULL,
			chapter_title_padding_top float DEFAULT 0.0 NOT NULL,
			chapter_title_padding_bottom float DEFAULT 1.5 NOT NULL,
			chapter_title_line_height float DEFAULT 1.2 NOT NULL,
			chapter_title_text_transform varchar(20) DEFAULT 'none' NOT NULL,
			chapter_prefix_show tinyint(1) DEFAULT 0 NOT NULL,
			chapter_prefix_template varchar(50) DEFAULT 'Capítulo {N}' NOT NULL,
			chapter_prefix_position varchar(20) DEFAULT 'above' NOT NULL,
			chapter_prefix_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			chapter_prefix_font_size float DEFAULT 16.0 NOT NULL,
			chapter_prefix_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_prefix_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_prefix_letter_spacing float DEFAULT 0.0 NOT NULL,
			chapter_prefix_ornament varchar(20) DEFAULT 'none' NOT NULL,
			header_margin_top float DEFAULT 1.0 NOT NULL,
			header_margin_bottom float DEFAULT 0.5 NOT NULL,
			header_align varchar(20) DEFAULT 'center' NOT NULL,
			footer_margin_top float DEFAULT 0.5 NOT NULL,
			footer_margin_bottom float DEFAULT 1.0 NOT NULL,
			footer_align varchar(20) DEFAULT 'center' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY book_id (book_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'almaden_bookster_db_version', '1.8.5' );
	}
}
add_action( 'init', 'almaden_bookster_create_settings_table' );

// --- AJAX Guardar Ajustes de Maquetación de PDF ---

function almaden_bookster_save_settings_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';

	$data = array(
		'book_id'                    => $book_id,
		'unit'                       => sanitize_text_field( $_POST['unit'] ),
		'page_size'                  => sanitize_text_field( $_POST['page_size'] ),
		'page_width'                 => floatval( str_replace( ',', '.', $_POST['page_width'] ) ),
		'page_height'                => floatval( str_replace( ',', '.', $_POST['page_height'] ) ),
		'margin_top'                 => floatval( str_replace( ',', '.', $_POST['margin_top'] ) ),
		'margin_bottom'              => floatval( str_replace( ',', '.', $_POST['margin_bottom'] ) ),
		'margin_left'                => floatval( str_replace( ',', '.', $_POST['margin_left'] ) ),
		'margin_right'               => floatval( str_replace( ',', '.', $_POST['margin_right'] ) ),
		'margin_left_odd'            => floatval( str_replace( ',', '.', $_POST['margin_left_odd'] ) ),
		'margin_right_odd'           => floatval( str_replace( ',', '.', $_POST['margin_right_odd'] ) ),
		'margin_left_even'           => floatval( str_replace( ',', '.', $_POST['margin_left_even'] ) ),
		'margin_right_even'          => floatval( str_replace( ',', '.', $_POST['margin_right_even'] ) ),
		'padding_top'                => floatval( str_replace( ',', '.', $_POST['padding_top'] ) ),
		'padding_bottom'             => floatval( str_replace( ',', '.', $_POST['padding_bottom'] ) ),
		'padding_left'               => floatval( str_replace( ',', '.', $_POST['padding_left'] ) ),
		'padding_right'              => floatval( str_replace( ',', '.', $_POST['padding_right'] ) ),
		'bleeding'                   => floatval( str_replace( ',', '.', $_POST['bleeding'] ) ),
		'font_family_content'        => sanitize_text_field( $_POST['font_family_content'] ),
		'font_size_content'          => floatval( str_replace( ',', '.', $_POST['font_size_content'] ) ),
		'line_height_content'        => floatval( str_replace( ',', '.', $_POST['line_height_content'] ) ),
		'content_text_align'         => sanitize_text_field( $_POST['content_text_align'] ),
		'content_hyphenation'        => intval( $_POST['content_hyphenation'] ),
		'content_language'           => sanitize_text_field( $_POST['content_language'] ),
		'content_paragraph_indent'   => floatval( str_replace( ',', '.', $_POST['content_paragraph_indent'] ) ),
		'content_paragraph_spacing'  => floatval( str_replace( ',', '.', $_POST['content_paragraph_spacing'] ) ),
		'font_family_headings'       => isset($_POST['font_family_headings']) ? sanitize_text_field( $_POST['font_family_headings'] ) : '',
		'font_family_h1'             => sanitize_text_field( $_POST['font_family_h1'] ),
		'font_family_h2'             => sanitize_text_field( $_POST['font_family_h2'] ),
		'font_family_h3'             => sanitize_text_field( $_POST['font_family_h3'] ),
		'font_weight_h1'             => sanitize_text_field( $_POST['font_weight_h1'] ),
		'font_weight_h2'             => sanitize_text_field( $_POST['font_weight_h2'] ),
		'font_weight_h3'             => sanitize_text_field( $_POST['font_weight_h3'] ),
		'font_size_h1'               => floatval( str_replace( ',', '.', $_POST['font_size_h1'] ) ),
		'font_size_h2'               => floatval( str_replace( ',', '.', $_POST['font_size_h2'] ) ),
		'font_size_h3'               => floatval( str_replace( ',', '.', $_POST['font_size_h3'] ) ),
		'header_font_family'         => sanitize_text_field( $_POST['header_font_family'] ),
		'header_font_size'           => floatval( str_replace( ',', '.', $_POST['header_font_size'] ) ),
		'header_font_weight'         => sanitize_text_field( $_POST['header_font_weight'] ),
		'header_font_style'          => sanitize_text_field( $_POST['header_font_style'] ),
		'header_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['header_letter_spacing'] ) ),
		'header_even_type'           => sanitize_text_field( $_POST['header_even_type'] ),
		'header_even_custom'         => sanitize_text_field( $_POST['header_even_custom'] ),
		'header_odd_type'            => sanitize_text_field( $_POST['header_odd_type'] ),
		'header_odd_custom'          => sanitize_text_field( $_POST['header_odd_custom'] ),
		'footer_font_family'         => sanitize_text_field( $_POST['footer_font_family'] ),
		'footer_font_size'           => floatval( str_replace( ',', '.', $_POST['footer_font_size'] ) ),
		'footer_font_weight'         => sanitize_text_field( $_POST['footer_font_weight'] ),
		'footer_font_style'          => sanitize_text_field( $_POST['footer_font_style'] ),
		'footer_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['footer_letter_spacing'] ) ),
		'footer_even_type'           => sanitize_text_field( $_POST['footer_even_type'] ),
		'footer_odd_type'            => sanitize_text_field( $_POST['footer_odd_type'] ),
		'first_page_header_type'     => sanitize_text_field( $_POST['first_page_header_type'] ),
		'first_page_header_custom'   => sanitize_text_field( $_POST['first_page_header_custom'] ),
		'first_page_footer_type'     => sanitize_text_field( $_POST['first_page_footer_type'] ),
		'first_page_footer_custom'   => sanitize_text_field( $_POST['first_page_footer_custom'] ),
		'chapter_start_parity'       => sanitize_text_field( $_POST['chapter_start_parity'] ),
		'parity_image_mode'          => sanitize_text_field( $_POST['parity_image_mode'] ),
		'chapter_page_one_align'     => sanitize_text_field( $_POST['chapter_page_one_align'] ),
		'chapter_page_one_vertical'  => sanitize_text_field( $_POST['chapter_page_one_vertical'] ),
		'chapter_title_font_family'  => sanitize_text_field( $_POST['chapter_title_font_family'] ),
		'chapter_title_font_size'    => floatval( str_replace( ',', '.', $_POST['chapter_title_font_size'] ) ),
		'chapter_title_font_weight'  => sanitize_text_field( $_POST['chapter_title_font_weight'] ),
		'chapter_title_font_style'   => sanitize_text_field( $_POST['chapter_title_font_style'] ),
		'chapter_title_align'        => sanitize_text_field( $_POST['chapter_title_align'] ),
		'chapter_title_padding_top'  => floatval( str_replace( ',', '.', $_POST['chapter_title_padding_top'] ) ),
		'chapter_title_padding_bottom'=> floatval( str_replace( ',', '.', $_POST['chapter_title_padding_bottom'] ) ),
		'chapter_title_line_height'  => isset($_POST['chapter_title_line_height']) ? floatval( str_replace( ',', '.', $_POST['chapter_title_line_height'] ) ) : 1.2,
		'chapter_title_text_transform'=> isset($_POST['chapter_title_text_transform']) ? sanitize_text_field( $_POST['chapter_title_text_transform'] ) : 'none',
		'chapter_prefix_show'        => isset($_POST['chapter_prefix_show']) ? intval($_POST['chapter_prefix_show']) : 0,
		'chapter_prefix_template'    => sanitize_text_field( $_POST['chapter_prefix_template'] ),
		'chapter_prefix_position'    => sanitize_text_field( $_POST['chapter_prefix_position'] ),
		'chapter_prefix_font_family' => sanitize_text_field( $_POST['chapter_prefix_font_family'] ),
		'chapter_prefix_font_size'   => floatval( str_replace( ',', '.', $_POST['chapter_prefix_font_size'] ) ),
		'chapter_prefix_font_weight' => sanitize_text_field( $_POST['chapter_prefix_font_weight'] ),
		'chapter_prefix_font_style'  => sanitize_text_field( $_POST['chapter_prefix_font_style'] ),
		'chapter_prefix_letter_spacing' => floatval( str_replace( ',', '.', $_POST['chapter_prefix_letter_spacing'] ) ),
		'chapter_prefix_ornament'    => sanitize_text_field( $_POST['chapter_prefix_ornament'] ),
		'header_margin_top'          => floatval( str_replace( ',', '.', $_POST['header_margin_top'] ) ),
		'header_margin_bottom'       => floatval( str_replace( ',', '.', $_POST['header_margin_bottom'] ) ),
		'header_align'               => sanitize_text_field( $_POST['header_align'] ),
		'footer_margin_top'          => floatval( str_replace( ',', '.', $_POST['footer_margin_top'] ) ),
		'footer_margin_bottom'       => floatval( str_replace( ',', '.', $_POST['footer_margin_bottom'] ) ),
		'footer_align'               => sanitize_text_field( $_POST['footer_align'] ),
	);

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE book_id = %d", $book_id ) );

	if ( $exists ) {
		$result = $wpdb->update( $table_name, $data, array( 'book_id' => $book_id ) );
	} else {
		$result = $wpdb->insert( $table_name, $data );
	}

	if ( false !== $result ) {
		wp_send_json_success( array( 'message' => 'Configuración de maquetación guardada con éxito.' ) );
	} else {
		wp_send_json_error( 'Error al guardar la configuración.' );
	}
}
add_action( 'wp_ajax_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );


