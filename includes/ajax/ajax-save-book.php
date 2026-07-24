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

	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
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
		$opening_page_mode     = isset( $chapter['opening_page_mode'] ) ? sanitize_text_field( $chapter['opening_page_mode'] ) : '';
		$opening_blank_intentional = isset( $chapter['opening_blank_intentional'] ) ? sanitize_text_field( $chapter['opening_blank_intentional'] ) : '0';
		$opening_block_enabled = isset( $chapter['opening_block_enabled'] ) ? sanitize_text_field( $chapter['opening_block_enabled'] ) : '1';
		$opening_block_horizontal_align = isset( $chapter['opening_block_horizontal_align'] ) ? sanitize_text_field( $chapter['opening_block_horizontal_align'] ) : 'center';
		$opening_block_vertical_align = isset( $chapter['opening_block_vertical_align'] ) ? sanitize_text_field( $chapter['opening_block_vertical_align'] ) : 'top';
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
		$start_parity          = isset( $chapter['start_parity'] ) ? sanitize_text_field( $chapter['start_parity'] ) : 'any';
		$first_page_header_type = isset( $chapter['first_page_header_type'] ) ? sanitize_text_field( $chapter['first_page_header_type'] ) : 'blank';
		$first_page_header_custom = isset( $chapter['first_page_header_custom'] ) ? sanitize_text_field( $chapter['first_page_header_custom'] ) : '';
		$first_page_footer_type = isset( $chapter['first_page_footer_type'] ) ? sanitize_text_field( $chapter['first_page_footer_type'] ) : 'page_number';
		$first_page_footer_custom = isset( $chapter['first_page_footer_custom'] ) ? sanitize_text_field( $chapter['first_page_footer_custom'] ) : '';
		$parity_image_mode     = isset( $chapter['parity_image_mode'] ) ? sanitize_text_field( $chapter['parity_image_mode'] ) : 'content';
		$parity_image_width    = isset( $chapter['parity_image_width'] ) ? sanitize_text_field( $chapter['parity_image_width'] ) : '';
		$parity_image_height   = isset( $chapter['parity_image_height'] ) ? sanitize_text_field( $chapter['parity_image_height'] ) : '';
		$is_toc                = isset( $chapter['is_toc'] ) ? sanitize_text_field( $chapter['is_toc'] ) : '0';
		$is_credits            = isset( $chapter['is_credits'] ) ? sanitize_text_field( $chapter['is_credits'] ) : '0';
		
		// Credits metadata
		$credits_font_family   = isset( $chapter['credits_font_family'] ) ? sanitize_text_field( $chapter['credits_font_family'] ) : '';
		$credits_align         = isset( $chapter['credits_align'] ) ? sanitize_text_field( $chapter['credits_align'] ) : '';
			$credits_font_size     = isset( $chapter['credits_font_size'] ) ? sanitize_text_field( $chapter['credits_font_size'] ) : '';
			$credits_letter_spacing = isset( $chapter['credits_letter_spacing'] ) ? sanitize_text_field( $chapter['credits_letter_spacing'] ) : '';
			$credits_font_weight   = isset( $chapter['credits_font_weight'] ) ? sanitize_text_field( $chapter['credits_font_weight'] ) : '';
			$credits_hide_page_number = isset( $chapter['credits_hide_page_number'] ) ? sanitize_text_field( $chapter['credits_hide_page_number'] ) : '0';
			$credits_margin_top    = isset( $chapter['credits_margin_top'] ) ? sanitize_text_field( $chapter['credits_margin_top'] ) : '';
			$credits_margin_bottom = isset( $chapter['credits_margin_bottom'] ) ? sanitize_text_field( $chapter['credits_margin_bottom'] ) : '';
		
		// TOC metadata
		$toc_font_family       = isset( $chapter['toc_font_family'] ) ? sanitize_text_field( $chapter['toc_font_family'] ) : '';
		$toc_font_size         = isset( $chapter['toc_font_size'] ) ? sanitize_text_field( $chapter['toc_font_size'] ) : '';
		$toc_enumerate         = isset( $chapter['toc_enumerate'] ) ? sanitize_text_field( $chapter['toc_enumerate'] ) : 'none';
		$toc_font_style        = isset( $chapter['toc_font_style'] ) ? sanitize_text_field( $chapter['toc_font_style'] ) : 'normal';
		$toc_font_weight       = isset( $chapter['toc_font_weight'] ) ? sanitize_text_field( $chapter['toc_font_weight'] ) : 'normal';
		$toc_text_transform    = isset( $chapter['toc_text_transform'] ) ? sanitize_text_field( $chapter['toc_text_transform'] ) : 'none';
		$toc_letter_spacing    = isset( $chapter['toc_letter_spacing'] ) ? sanitize_text_field( $chapter['toc_letter_spacing'] ) : '';
		$toc_line_height       = isset( $chapter['toc_line_height'] ) ? sanitize_text_field( $chapter['toc_line_height'] ) : '';
		$toc_item_spacing      = isset( $chapter['toc_item_spacing'] ) ? sanitize_text_field( $chapter['toc_item_spacing'] ) : '';
		$toc_default_hidden    = '1' === (string) $is_toc ? '1' : '0';
		$toc_hide_header       = isset( $chapter['toc_hide_header'] ) ? sanitize_text_field( $chapter['toc_hide_header'] ) : $toc_default_hidden;
		$toc_hide_page_numbers = isset( $chapter['toc_hide_page_numbers'] ) ? sanitize_text_field( $chapter['toc_hide_page_numbers'] ) : $toc_default_hidden;
		$toc_item_align        = isset( $chapter['toc_item_align'] ) ? sanitize_text_field( $chapter['toc_item_align'] ) : 'left';
		$toc_leader_style      = isset( $chapter['toc_leader_style'] ) ? sanitize_text_field( $chapter['toc_leader_style'] ) : 'dotted';
		$toc_leader_position   = isset( $chapter['toc_leader_position'] ) ? sanitize_text_field( $chapter['toc_leader_position'] ) : 'middle';
		
		$toc_title_align       = isset( $chapter['toc_title_align'] ) ? sanitize_text_field( $chapter['toc_title_align'] ) : '';
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
				'post_parent'  => $source_book_id,
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
			update_post_meta( $post_id, '_opening_page_mode', $opening_page_mode );
			update_post_meta( $post_id, '_opening_blank_intentional', $opening_blank_intentional );
			update_post_meta( $post_id, '_opening_block_enabled', $opening_block_enabled );
			update_post_meta( $post_id, '_opening_block_horizontal_align', $opening_block_horizontal_align );
			update_post_meta( $post_id, '_opening_block_vertical_align', $opening_block_vertical_align );
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
			update_post_meta( $post_id, '_start_parity', $start_parity );
			update_post_meta( $post_id, '_first_page_header_type', $first_page_header_type );
			update_post_meta( $post_id, '_first_page_header_custom', $first_page_header_custom );
			update_post_meta( $post_id, '_first_page_footer_type', $first_page_footer_type );
			update_post_meta( $post_id, '_first_page_footer_custom', $first_page_footer_custom );
			update_post_meta( $post_id, '_parity_image_mode', $parity_image_mode );
			update_post_meta( $post_id, '_parity_image_width', $parity_image_width );
			update_post_meta( $post_id, '_parity_image_height', $parity_image_height );
			update_post_meta( $post_id, '_is_toc', $is_toc );
			update_post_meta( $post_id, '_is_credits', $is_credits );
			update_post_meta( $post_id, '_credits_font_family', $credits_font_family );
			update_post_meta( $post_id, '_credits_align', $credits_align );
				update_post_meta( $post_id, '_credits_font_size', $credits_font_size );
				update_post_meta( $post_id, '_credits_letter_spacing', $credits_letter_spacing );
				update_post_meta( $post_id, '_credits_font_weight', $credits_font_weight );
				update_post_meta( $post_id, '_credits_hide_page_number', $credits_hide_page_number );
				update_post_meta( $post_id, '_credits_margin_top', $credits_margin_top );
				update_post_meta( $post_id, '_credits_margin_bottom', $credits_margin_bottom );
			
			// TOC metadata
			update_post_meta( $post_id, '_toc_font_family', $toc_font_family );
			update_post_meta( $post_id, '_toc_font_size', $toc_font_size );
			update_post_meta( $post_id, '_toc_enumerate', $toc_enumerate );
			update_post_meta( $post_id, '_toc_font_style', $toc_font_style );
			update_post_meta( $post_id, '_toc_font_weight', $toc_font_weight );
			update_post_meta( $post_id, '_toc_text_transform', $toc_text_transform );
			update_post_meta( $post_id, '_toc_letter_spacing', $toc_letter_spacing );
			update_post_meta( $post_id, '_toc_line_height', $toc_line_height );
			update_post_meta( $post_id, '_toc_item_spacing', $toc_item_spacing );
			update_post_meta( $post_id, '_toc_hide_header', $toc_hide_header );
			update_post_meta( $post_id, '_toc_hide_page_numbers', $toc_hide_page_numbers );
			update_post_meta( $post_id, '_toc_item_align', $toc_item_align );
			update_post_meta( $post_id, '_toc_leader_style', $toc_leader_style );
			update_post_meta( $post_id, '_toc_leader_position', $toc_leader_position );
			
			update_post_meta( $post_id, '_toc_title_align', $toc_title_align );
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
				'opening_page_mode'     => $opening_page_mode,
				'opening_blank_intentional' => $opening_blank_intentional,
				'opening_block_enabled' => $opening_block_enabled,
				'opening_block_horizontal_align' => $opening_block_horizontal_align,
				'opening_block_vertical_align' => $opening_block_vertical_align,
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
				'start_parity'          => $start_parity,
				'first_page_header_type'  => $first_page_header_type,
				'first_page_header_custom' => $first_page_header_custom,
				'first_page_footer_type'  => $first_page_footer_type,
				'first_page_footer_custom' => $first_page_footer_custom,
				'parity_image_mode'     => $parity_image_mode,
				'parity_image_width'    => $parity_image_width,
				'parity_image_height'   => $parity_image_height,
				'is_toc'                => $is_toc,
				'is_credits'            => $is_credits,
				'credits_font_family'   => $credits_font_family,
				'credits_align'         => $credits_align,
					'credits_font_size'     => $credits_font_size,
					'credits_letter_spacing' => $credits_letter_spacing,
					'credits_font_weight'   => $credits_font_weight,
					'credits_hide_page_number' => $credits_hide_page_number,
					'credits_margin_top'    => $credits_margin_top,
					'credits_margin_bottom' => $credits_margin_bottom,
					'toc_font_family'       => $toc_font_family,
				'toc_font_size'         => $toc_font_size,
				'toc_enumerate'         => $toc_enumerate,
				'toc_font_style'        => $toc_font_style,
				'toc_font_weight'       => $toc_font_weight,
				'toc_text_transform'    => $toc_text_transform,
				'toc_letter_spacing'    => $toc_letter_spacing,
				'toc_line_height'       => $toc_line_height,
				'toc_item_spacing'      => $toc_item_spacing,
				'toc_hide_header'       => $toc_hide_header,
				'toc_hide_page_numbers' => $toc_hide_page_numbers,
				'toc_item_align'        => $toc_item_align,
				'toc_leader_style'      => $toc_leader_style,
				'toc_leader_position'   => $toc_leader_position,
				'toc_title_align'       => $toc_title_align,
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
	$existing_chapters = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );

	foreach ( $existing_chapters as $existing_id ) {
		if ( ! in_array( $existing_id, $incoming_ids ) ) {
			wp_delete_post( $existing_id, true ); // Forzar borrado físico
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

function almaden_bookster_delete_book_chapter_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	$chapter_id = isset( $_POST['chapter_id'] ) ? intval( $_POST['chapter_id'] ) : 0;

	if ( ! $book_id || ! $chapter_id ) {
		wp_send_json_error( 'Faltan datos para eliminar el capítulo.' );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_book_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( 'No tienes permisos para editar este libro.' );
	}

	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}

	$chapter_post = get_post( $chapter_id );
	if ( ! $chapter_post || 'book_chapter' !== $chapter_post->post_type ) {
		wp_send_json_error( 'El capítulo no existe.' );
	}

	if ( intval( $chapter_post->post_parent ) !== intval( $source_book_id ) ) {
		wp_send_json_error( 'El capítulo no pertenece a este libro.' );
	}

	$result = wp_delete_post( $chapter_id, true );
	if ( ! $result ) {
		wp_send_json_error( 'No se pudo eliminar el capítulo.' );
	}

	wp_send_json_success( array(
		'message' => 'Capítulo eliminado correctamente.',
		'chapter_id' => $chapter_id,
	) );
}
add_action( 'wp_ajax_almaden_delete_book_chapter', 'almaden_bookster_delete_book_chapter_ajax' );
