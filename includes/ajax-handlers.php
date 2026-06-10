<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
		if ( current_user_can( 'unfiltered_html' ) ) {
			$chapter_content = $chapter['content'];
		} else {
			$chapter_content = wp_kses_post( $chapter['content'] );
		}
		
		// Meta-datos locales
		$parity_image          = isset( $chapter['parity_image'] ) ? sanitize_text_field( $chapter['parity_image'] ) : '';
		$hide_title            = isset( $chapter['hide_title'] ) ? sanitize_text_field( $chapter['hide_title'] ) : '0';
		$hide_all_headers_footers = isset( $chapter['hide_all_headers_footers'] ) ? sanitize_text_field( $chapter['hide_all_headers_footers'] ) : '0';
		$exclude_from_numbering= isset( $chapter['exclude_from_numbering'] ) ? sanitize_text_field( $chapter['exclude_from_numbering'] ) : '0';
		$custom_running_header = isset( $chapter['custom_running_header'] ) ? sanitize_text_field( $chapter['custom_running_header'] ) : '';
		
		// Valores del subtítulo
		$subtitle_text          = isset( $chapter['subtitle_text'] ) ? sanitize_textarea_field( $chapter['subtitle_text'] ) : '';
		$subtitle_font_family   = isset( $chapter['subtitle_font_family'] ) ? sanitize_text_field( $chapter['subtitle_font_family'] ) : '';
		$subtitle_align         = isset( $chapter['subtitle_align'] ) ? sanitize_text_field( $chapter['subtitle_align'] ) : '';
		$subtitle_font_size     = isset( $chapter['subtitle_font_size'] ) ? sanitize_text_field( $chapter['subtitle_font_size'] ) : '';
		$subtitle_letter_spacing = isset( $chapter['subtitle_letter_spacing'] ) ? sanitize_text_field( $chapter['subtitle_letter_spacing'] ) : '';
		$subtitle_font_style    = isset( $chapter['subtitle_font_style'] ) ? sanitize_text_field( $chapter['subtitle_font_style'] ) : '';
		$subtitle_text_transform = isset( $chapter['subtitle_text_transform'] ) ? sanitize_text_field( $chapter['subtitle_text_transform'] ) : '';
		$subtitle_font_weight   = isset( $chapter['subtitle_font_weight'] ) ? sanitize_text_field( $chapter['subtitle_font_weight'] ) : '';
		$subtitle_margin_top    = isset( $chapter['subtitle_margin_top'] ) ? sanitize_text_field( $chapter['subtitle_margin_top'] ) : '';
		$subtitle_margin_bottom = isset( $chapter['subtitle_margin_bottom'] ) ? sanitize_text_field( $chapter['subtitle_margin_bottom'] ) : '';
		
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
			update_post_meta( $post_id, '_hide_all_headers_footers', $hide_all_headers_footers );
			update_post_meta( $post_id, '_exclude_from_numbering', $exclude_from_numbering );
			update_post_meta( $post_id, '_custom_running_header', $custom_running_header );
			
			// Guardar meta-datos del subtítulo
			update_post_meta( $post_id, '_subtitle_text', $subtitle_text );
			update_post_meta( $post_id, '_subtitle_font_family', $subtitle_font_family );
			update_post_meta( $post_id, '_subtitle_align', $subtitle_align );
			update_post_meta( $post_id, '_subtitle_font_size', $subtitle_font_size );
			update_post_meta( $post_id, '_subtitle_letter_spacing', $subtitle_letter_spacing );
			update_post_meta( $post_id, '_subtitle_font_style', $subtitle_font_style );
			update_post_meta( $post_id, '_subtitle_text_transform', $subtitle_text_transform );
			update_post_meta( $post_id, '_subtitle_font_weight', $subtitle_font_weight );
			update_post_meta( $post_id, '_subtitle_margin_top', $subtitle_margin_top );
			update_post_meta( $post_id, '_subtitle_margin_bottom', $subtitle_margin_bottom );
			
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
				'hide_all_headers_footers' => $hide_all_headers_footers,
				'exclude_from_numbering'=> $exclude_from_numbering,
				'custom_running_header' => $custom_running_header,
				
				'subtitle_text'          => $subtitle_text,
				'subtitle_font_family'   => $subtitle_font_family,
				'subtitle_align'         => $subtitle_align,
				'subtitle_font_size'     => $subtitle_font_size,
				'subtitle_letter_spacing' => $subtitle_letter_spacing,
				'subtitle_font_style'    => $subtitle_font_style,
				'subtitle_text_transform' => $subtitle_text_transform,
				'subtitle_font_weight'   => $subtitle_font_weight,
				'subtitle_margin_top'    => $subtitle_margin_top,
				'subtitle_margin_bottom' => $subtitle_margin_bottom,
				
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
	
	// Guardar total de páginas compiladas
	$total_pages = isset( $_POST['total_pages'] ) ? intval( $_POST['total_pages'] ) : 0;
	if ( $total_pages >= 0 ) {
		update_post_meta( $book_id, '_almaden_total_pages', $total_pages );
	}

	wp_send_json_success( array( 
		'message'  => 'Libro guardado con éxito.',
		'chapters' => $updated_chapters 
	) );
}
add_action( 'wp_ajax_almaden_save_book', 'almaden_bookster_save_book_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book', 'almaden_bookster_save_book_ajax' );

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
		'export_grayscale'           => isset($_POST['export_grayscale']) ? intval($_POST['export_grayscale']) : 0,
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
		'chapter_title_padding_left' => isset($_POST['chapter_title_padding_left']) ? floatval( str_replace( ',', '.', $_POST['chapter_title_padding_left'] ) ) : 0,
		'chapter_title_padding_right'=> isset($_POST['chapter_title_padding_right']) ? floatval( str_replace( ',', '.', $_POST['chapter_title_padding_right'] ) ) : 0,
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

// --- AJAX Guardar Ajustes de Portada ---
function almaden_bookster_save_cover_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_cover_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$cover_data = array(
		'paper_type'   => isset($_POST['paper_type']) ? sanitize_text_field($_POST['paper_type']) : '',
		'front_flap'   => isset($_POST['front_flap']) ? floatval($_POST['front_flap']) : 0,
		'back_flap'    => isset($_POST['back_flap']) ? floatval($_POST['back_flap']) : 0,
		'front_image'  => isset($_POST['front_image']) ? esc_url_raw($_POST['front_image']) : '',
		'back_image'   => isset($_POST['back_image']) ? esc_url_raw($_POST['back_image']) : '',
		'spread_image' => isset($_POST['spread_image']) ? esc_url_raw($_POST['spread_image']) : '',
		'spine_image'  => isset($_POST['spine_image']) ? esc_url_raw($_POST['spine_image']) : '',
		'spine_color'  => isset($_POST['spine_color']) ? sanitize_hex_color($_POST['spine_color']) : '',
		'front_flap_width' => isset($_POST['front_flap_width']) ? floatval($_POST['front_flap_width']) : 0,
		'back_flap_width'  => isset($_POST['back_flap_width']) ? floatval($_POST['back_flap_width']) : 0,
		'front_flap_image' => isset($_POST['front_flap_image']) ? esc_url_raw($_POST['front_flap_image']) : '',
		'front_flap_color' => isset($_POST['front_flap_color']) ? sanitize_hex_color($_POST['front_flap_color']) : '',
		'back_flap_image'  => isset($_POST['back_flap_image']) ? esc_url_raw($_POST['back_flap_image']) : '',
		'back_flap_color'  => isset($_POST['back_flap_color']) ? sanitize_hex_color($_POST['back_flap_color']) : '',
		'text_layers'  => isset($_POST['text_layers']) ? json_decode(stripslashes($_POST['text_layers']), true) : array(),
	);

	update_post_meta( $book_id, '_almaden_cover_settings', $cover_data );
	
	wp_send_json_success( array( 'message' => 'Configuración de portada guardada con éxito.' ) );
}
add_action( 'wp_ajax_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
