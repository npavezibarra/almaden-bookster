<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
		'ebook_bg_type'              => isset($_POST['ebook_bg_type']) ? sanitize_text_field($_POST['ebook_bg_type']) : 'color',
		'ebook_bg_color'             => isset($_POST['ebook_bg_color']) ? sanitize_text_field($_POST['ebook_bg_color']) : '#ffffff',
		'ebook_bg_image'             => isset($_POST['ebook_bg_image']) ? esc_url_raw($_POST['ebook_bg_image']) : '',
		'ebook_bg_opacity'           => isset($_POST['ebook_bg_opacity']) ? floatval(str_replace(',', '.', $_POST['ebook_bg_opacity'])) : 1.0,
		'ebook_cover_panel_bg_type'  => isset($_POST['ebook_cover_panel_bg_type']) ? sanitize_text_field($_POST['ebook_cover_panel_bg_type']) : 'image',
		'ebook_cover_panel_bg_color' => isset($_POST['ebook_cover_panel_bg_color']) ? sanitize_text_field($_POST['ebook_cover_panel_bg_color']) : 'transparent',
		'ebook_cover_panel_bg_image' => isset($_POST['ebook_cover_panel_bg_image']) ? esc_url_raw($_POST['ebook_cover_panel_bg_image']) : '',
		'ebook_cover_panel_bg_opacity'=> isset($_POST['ebook_cover_panel_bg_opacity']) ? floatval(str_replace(',', '.', $_POST['ebook_cover_panel_bg_opacity'])) : 1.0,
		'ebook_font_family_content'  => isset($_POST['ebook_font_family_content']) ? sanitize_text_field($_POST['ebook_font_family_content']) : 'Merriweather',
		'ebook_font_size_content'    => isset($_POST['ebook_font_size_content']) ? floatval(str_replace(',', '.', $_POST['ebook_font_size_content'])) : 18.0,
		'ebook_font_weight_content'  => isset($_POST['ebook_font_weight_content']) ? sanitize_text_field($_POST['ebook_font_weight_content']) : 'normal',
		'ebook_line_height_content'  => isset($_POST['ebook_line_height_content']) ? floatval(str_replace(',', '.', $_POST['ebook_line_height_content'])) : 1.8,
		'ebook_font_family_headings' => isset($_POST['ebook_font_family_headings']) ? sanitize_text_field($_POST['ebook_font_family_headings']) : 'Playfair Display',
		'ebook_font_size_headings'   => isset($_POST['ebook_font_size_headings']) ? floatval(str_replace(',', '.', $_POST['ebook_font_size_headings'])) : 32.0,
		'ebook_font_weight_headings' => isset($_POST['ebook_font_weight_headings']) ? sanitize_text_field($_POST['ebook_font_weight_headings']) : 'bold',
		'ebook_line_height_headings' => isset($_POST['ebook_line_height_headings']) ? floatval(str_replace(',', '.', $_POST['ebook_line_height_headings'])) : 1.3,
		'ebook_text_align_justify'   => isset($_POST['ebook_text_align_justify']) ? intval($_POST['ebook_text_align_justify']) : 0,
		'ebook_hyphenation'          => isset($_POST['ebook_hyphenation']) ? intval($_POST['ebook_hyphenation']) : 0,
		'ebook_chapter_title_align'  => isset($_POST['ebook_chapter_title_align']) ? sanitize_text_field($_POST['ebook_chapter_title_align']) : 'center',
		'ebook_chapter_title_text_transform' => isset($_POST['ebook_chapter_title_text_transform']) ? sanitize_text_field($_POST['ebook_chapter_title_text_transform']) : 'none',
		'ebook_chapter_title_padding_top' => isset($_POST['ebook_chapter_title_padding_top']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_title_padding_top'])) : 2.0,
		'ebook_chapter_title_padding_bottom' => isset($_POST['ebook_chapter_title_padding_bottom']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_title_padding_bottom'])) : 2.0,
		'ebook_chapter_title_padding_left' => isset($_POST['ebook_chapter_title_padding_left']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_title_padding_left'])) : 0.0,
		'ebook_chapter_title_padding_right' => isset($_POST['ebook_chapter_title_padding_right']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_title_padding_right'])) : 0.0,
		'ebook_chapter_prefix_show'  => isset($_POST['ebook_chapter_prefix_show']) ? intval($_POST['ebook_chapter_prefix_show']) : 0,
		'ebook_chapter_prefix_template' => isset($_POST['ebook_chapter_prefix_template']) ? sanitize_text_field($_POST['ebook_chapter_prefix_template']) : 'Capítulo {N}',
		'ebook_chapter_prefix_position' => isset($_POST['ebook_chapter_prefix_position']) ? sanitize_text_field($_POST['ebook_chapter_prefix_position']) : 'above',
		'ebook_chapter_prefix_font_family' => isset($_POST['ebook_chapter_prefix_font_family']) ? sanitize_text_field($_POST['ebook_chapter_prefix_font_family']) : 'Playfair Display',
		'ebook_chapter_prefix_font_size' => isset($_POST['ebook_chapter_prefix_font_size']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_prefix_font_size'])) : 16.0,
		'ebook_chapter_prefix_font_weight' => isset($_POST['ebook_chapter_prefix_font_weight']) ? sanitize_text_field($_POST['ebook_chapter_prefix_font_weight']) : 'normal',
		'ebook_chapter_prefix_font_style' => isset($_POST['ebook_chapter_prefix_font_style']) ? sanitize_text_field($_POST['ebook_chapter_prefix_font_style']) : 'normal',
		'ebook_chapter_prefix_letter_spacing' => isset($_POST['ebook_chapter_prefix_letter_spacing']) ? floatval(str_replace(',', '.', $_POST['ebook_chapter_prefix_letter_spacing'])) : 0.0,
		'ebook_chapter_prefix_ornament' => isset($_POST['ebook_chapter_prefix_ornament']) ? sanitize_text_field($_POST['ebook_chapter_prefix_ornament']) : 'none',
		'font_family_content'        => sanitize_text_field( $_POST['font_family_content'] ),
		'font_size_content'          => floatval( str_replace( ',', '.', $_POST['font_size_content'] ) ),
		'font_weight_content'        => sanitize_text_field( $_POST['font_weight_content'] ),
		'line_height_content'        => floatval( str_replace( ',', '.', $_POST['line_height_content'] ) ),
		'content_text_align'         => sanitize_text_field( $_POST['content_text_align'] ),
		'content_text_align_last'    => isset( $_POST['content_text_align_last'] ) ? sanitize_text_field( $_POST['content_text_align_last'] ) : 'left',
		'content_hyphenation'        => intval( $_POST['content_hyphenation'] ),
		'content_language'           => sanitize_text_field( $_POST['content_language'] ),
		'content_hyphenation_exceptions' => isset( $_POST['content_hyphenation_exceptions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content_hyphenation_exceptions'] ) ) : '',
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
		'header_text_transform'      => isset($_POST['header_text_transform']) ? sanitize_text_field($_POST['header_text_transform']) : 'none',
		'header_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['header_letter_spacing'] ) ),
		'header_even_type'           => sanitize_text_field( $_POST['header_even_type'] ),
		'header_even_custom'         => sanitize_text_field( $_POST['header_even_custom'] ),
		'header_odd_type'            => sanitize_text_field( $_POST['header_odd_type'] ),
		'header_odd_custom'          => sanitize_text_field( $_POST['header_odd_custom'] ),
		'footer_font_family'         => sanitize_text_field( $_POST['footer_font_family'] ),
		'footer_font_size'           => floatval( str_replace( ',', '.', $_POST['footer_font_size'] ) ),
		'footer_font_weight'         => sanitize_text_field( $_POST['footer_font_weight'] ),
		'footer_font_style'          => sanitize_text_field( $_POST['footer_font_style'] ),
		'footer_text_transform'      => isset($_POST['footer_text_transform']) ? sanitize_text_field($_POST['footer_text_transform']) : 'none',
		'footer_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['footer_letter_spacing'] ) ),
		'footer_even_type'           => sanitize_text_field( $_POST['footer_even_type'] ),
		'footer_odd_type'            => sanitize_text_field( $_POST['footer_odd_type'] ),
		'first_page_header_type'     => sanitize_text_field( $_POST['first_page_header_type'] ),
		'first_page_header_custom'   => sanitize_text_field( $_POST['first_page_header_custom'] ),
		'first_page_footer_type'     => sanitize_text_field( $_POST['first_page_footer_type'] ),
		'first_page_footer_custom'   => sanitize_text_field( $_POST['first_page_footer_custom'] ),
		'footnote_font_family'       => isset( $_POST['footnote_font_family'] ) ? sanitize_text_field( $_POST['footnote_font_family'] ) : 'Merriweather',
		'footnote_font_size'         => isset( $_POST['footnote_font_size'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_font_size'] ) ) : 8.5,
		'footnote_font_weight'       => isset( $_POST['footnote_font_weight'] ) ? sanitize_text_field( $_POST['footnote_font_weight'] ) : 'normal',
		'footnote_align'             => ( isset( $_POST['footnote_align'] ) && in_array( $_POST['footnote_align'], array( 'left', 'center', 'right', 'justify' ), true ) ) ? sanitize_text_field( $_POST['footnote_align'] ) : 'justify',
		'footnote_call_scale'        => isset( $_POST['footnote_call_scale'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_call_scale'] ) ) : 0.65,
		'footnote_call_raise'        => isset( $_POST['footnote_call_raise'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_call_raise'] ) ) : 0.18,
		'footnote_padding_top'       => isset( $_POST['footnote_padding_top'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_top'] ) ) : 0.15,
		'footnote_padding_bottom'    => isset( $_POST['footnote_padding_bottom'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_bottom'] ) ) : 0.15,
		'footnote_padding_left'      => isset( $_POST['footnote_padding_left'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_left'] ) ) : 0.0,
		'footnote_padding_right'     => isset( $_POST['footnote_padding_right'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_right'] ) ) : 0.0,
		'chapter_start_parity'       => sanitize_text_field( $_POST['chapter_start_parity'] ),
		'parity_image_mode'          => sanitize_text_field( $_POST['parity_image_mode'] ),
		'chapter_page_one_vertical'  => ( isset( $_POST['chapter_page_one_vertical'] ) && $_POST['chapter_page_one_vertical'] === 'half' ) ? 'center' : sanitize_text_field( $_POST['chapter_page_one_vertical'] ),
		'chapter_title_font_family'  => sanitize_text_field( $_POST['chapter_title_font_family'] ),
		'chapter_title_font_size'    => floatval( str_replace( ',', '.', $_POST['chapter_title_font_size'] ) ),
		'chapter_title_font_weight'  => sanitize_text_field( $_POST['chapter_title_font_weight'] ),
		'chapter_title_font_style'   => sanitize_text_field( $_POST['chapter_title_font_style'] ),
		'chapter_title_align'        => ( isset( $_POST['chapter_title_align'] ) && in_array( $_POST['chapter_title_align'], array( 'left', 'center', 'right' ), true ) ) ? sanitize_text_field( $_POST['chapter_title_align'] ) : 'center',
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
		// Guardar campos de créditos en post_meta para no alterar el esquema de la tabla
		update_post_meta( $book_id, '_almaden_credits_edition', sanitize_text_field( $_POST['credits_edition'] ?? '' ) );
		update_post_meta( $book_id, '_almaden_credits_date', sanitize_text_field( $_POST['credits_date'] ?? '' ) );
		update_post_meta( $book_id, '_almaden_credits_copyright', sanitize_textarea_field( wp_unslash($_POST['credits_copyright'] ?? '') ) );
		update_post_meta( $book_id, '_almaden_credits_printer', sanitize_text_field( $_POST['credits_printer'] ?? '' ) );
		update_post_meta( $book_id, '_almaden_credits_blank_before', intval( $_POST['credits_blank_before'] ?? 0 ) );
		update_post_meta( $book_id, '_almaden_credits_blank_after', intval( $_POST['credits_blank_after'] ?? 0 ) );
		
		$custom_credits = isset($_POST['credits_custom']) ? json_decode(wp_unslash($_POST['credits_custom']), true) : [];
		update_post_meta( $book_id, '_almaden_credits_custom', wp_slash(wp_json_encode($custom_credits)) );

		// Guardar configuraciones globales de subtítulo en post_meta para PDF y Ebook
		$subtitle_fields = [
			'chapter_subtitle_show', 'chapter_subtitle_font_family', 'chapter_subtitle_font_size',
			'chapter_subtitle_align', 'chapter_subtitle_font_style', 'chapter_subtitle_text_transform',
			'chapter_subtitle_font_weight', 'chapter_subtitle_margin_top', 'chapter_subtitle_margin_bottom',
			'chapter_subtitle_letter_spacing',
			'ebook_subtitle_show', 'ebook_subtitle_font_family', 'ebook_subtitle_font_size',
			'ebook_subtitle_align', 'ebook_subtitle_font_style', 'ebook_subtitle_text_transform',
			'ebook_subtitle_font_weight', 'ebook_subtitle_padding_top', 'ebook_subtitle_padding_bottom',
			'ebook_subtitle_letter_spacing'
		];
			foreach ($subtitle_fields as $field) {
				if (isset($_POST[$field])) {
					$val = $_POST[$field];
					if (in_array($field, ['chapter_subtitle_show', 'ebook_subtitle_show'])) {
						$val = intval($val);
					} elseif (strpos($field, '_margin_') !== false || strpos($field, '_padding_') !== false || strpos($field, '_font_size') !== false || strpos($field, '_letter_spacing') !== false) {
						$val = floatval(str_replace(',', '.', $val));
					} elseif (in_array($field, ['chapter_subtitle_align', 'ebook_subtitle_align'], true)) {
						$val = in_array($val, ['left', 'center', 'right'], true) ? sanitize_text_field($val) : 'center';
					} else {
						$val = sanitize_text_field($val);
					}
					update_post_meta($book_id, '_almaden_' . $field, $val);
				}
		}

		wp_send_json_success( array( 'message' => 'Configuración de maquetación guardada con éxito.' ) );
	} else {
		error_log( "ALMADEN_DB_ERROR: " . $wpdb->last_error );
		wp_send_json_error( 'Error al guardar la configuración: ' . $wpdb->last_error );
	}
}
add_action( 'wp_ajax_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );


function almaden_get_book_pdf_settings( $book_id ) {
	global $wpdb;
	$settings_table = $wpdb->prefix . 'almaden_book_settings';
	$db_settings = array();
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$settings_table'" ) === $settings_table ) {
		$db_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
	}

	$pdf_settings = array(
		'unit'                       => 'cm',
		'page_size'                  => 'A4',
		'page_width'                 => 21.0,
		'page_height'                => 29.7,
		'margin_top'                 => 2.5,
		'margin_bottom'              => 2.5,
		'margin_left'                => 2.0,
		'margin_right'               => 2.0,
		'margin_left_odd'            => 2.0,
		'margin_right_odd'           => 2.0,
		'margin_left_even'           => 2.0,
		'margin_right_even'          => 2.0,
		'padding_top'                => 0.0,
		'padding_bottom'             => 0.0,
		'padding_left'               => 0.0,
		'padding_right'              => 0.0,
		'bleeding'                   => 0.0,
		'export_grayscale'           => 0,
		'ebook_bg_type'              => 'color',
		'ebook_bg_color'             => '#ffffff',
		'ebook_bg_image'             => '',
		'ebook_bg_opacity'           => 1.0,
		'ebook_cover_panel_bg_type'  => 'image',
		'ebook_cover_panel_bg_color' => 'transparent',
		'ebook_cover_panel_bg_image' => '',
		'ebook_cover_panel_bg_opacity'=> 1.0,
		'ebook_font_family_content'  => 'Merriweather',
		'ebook_font_size_content'    => 18.0,
		'ebook_font_weight_content'  => 'normal',
		'ebook_line_height_content'  => 1.8,
		'ebook_font_family_headings' => 'Playfair Display',
		'ebook_font_size_headings'   => 32.0,
		'ebook_font_weight_headings' => 'bold',
		'ebook_line_height_headings' => 1.3,
		'ebook_text_align_justify'   => 0,
		'ebook_hyphenation'          => 0,
		'ebook_chapter_title_align'  => 'center',
		'ebook_chapter_title_text_transform' => 'none',
		'ebook_chapter_title_padding_top' => 2.0,
		'ebook_chapter_title_padding_bottom' => 2.0,
		'ebook_chapter_title_padding_left' => 0.0,
		'ebook_chapter_title_padding_right' => 0.0,
		'ebook_chapter_prefix_show'  => 0,
		'ebook_chapter_prefix_template' => 'Capítulo {N}',
		'ebook_chapter_prefix_position' => 'above',
		'ebook_chapter_prefix_font_family' => 'Playfair Display',
		'ebook_chapter_prefix_font_size' => 16.0,
		'ebook_chapter_prefix_font_weight' => 'normal',
		'ebook_chapter_prefix_font_style' => 'normal',
		'ebook_chapter_prefix_letter_spacing' => 0.0,
		'ebook_chapter_prefix_ornament' => 'none',
		'footnote_font_family'       => 'Merriweather',
		'footnote_font_size'         => 8.5,
		'footnote_font_weight'       => 'normal',
		'footnote_align'             => 'justify',
		'footnote_call_scale'        => 0.65,
		'footnote_call_raise'        => 0.18,
		'footnote_padding_top'       => 0.15,
		'footnote_padding_bottom'    => 0.15,
		'footnote_padding_left'      => 0.0,
		'footnote_padding_right'     => 0.0,
		'font_family_content'        => 'Merriweather',
		'font_size_content'          => 11.5,
		'font_weight_content'        => 'normal',
		'line_height_content'        => 1.65,
		'content_text_align'         => 'justify',
		'content_text_align_last'    => 'left',
		'content_hyphenation'        => 1,
		'content_language'           => 'es',
		'content_hyphenation_exceptions' => '',
		'content_paragraph_indent'   => 0.0,
		'content_paragraph_spacing'  => 14.0,
		'font_family_headings'       => 'Playfair Display',
		'font_family_h1'             => 'Playfair Display',
		'font_family_h2'             => 'Playfair Display',
		'font_family_h3'             => 'Playfair Display',
		'font_weight_h1'             => 'bold',
		'font_weight_h2'             => 'bold',
		'font_weight_h3'             => 'bold',
		'font_size_h1'               => 24.0,
		'font_size_h2'               => 16.0,
		'font_size_h3'               => 13.0,
		'header_font_family'         => 'Merriweather',
		'header_font_size'           => 8.5,
		'header_font_weight'         => 'normal',
		'header_font_style'          => 'normal',
		'header_text_transform'      => 'none',
		'header_letter_spacing'      => 0.1,
		'header_even_type'           => 'book_title',
		'header_even_custom'         => '',
		'header_odd_type'            => 'chapter_title',
		'header_odd_custom'          => '',
		'footer_font_family'         => 'Merriweather',
		'footer_font_size'           => 9.0,
		'footer_font_weight'         => 'normal',
		'footer_font_style'          => 'normal',
		'footer_text_transform'      => 'none',
		'footer_letter_spacing'      => 0.0,
		'footer_even_type'           => 'page_number',
		'footer_odd_type'            => 'page_number',
		'first_page_header_type'     => 'blank',
		'first_page_header_custom'   => '',
		'first_page_footer_type'     => 'page_number',
		'first_page_footer_custom'   => '',
		'chapter_start_parity'       => 'any',
		'parity_image_mode'          => 'content',
			'chapter_page_one_vertical'  => 'top',
		'chapter_title_font_family'  => 'Playfair Display',
		'chapter_title_font_size'    => 24.0,
		'chapter_title_font_weight'  => 'bold',
		'chapter_title_font_style'   => 'normal',
		'chapter_title_align'        => 'center',
		'chapter_title_padding_top'  => 0.0,
		'chapter_title_padding_bottom'=> 1.5,
		'chapter_title_padding_left' => 0.0,
		'chapter_title_padding_right'=> 0.0,
		'chapter_title_line_height'  => 1.2,
		'chapter_title_text_transform' => 'none',
		'chapter_prefix_show'        => 0,
		'chapter_prefix_template'    => 'Capítulo {N}',
		'chapter_prefix_position'    => 'above',
		'chapter_prefix_font_family' => 'Playfair Display',
		'chapter_prefix_font_size'   => 16.0,
		'chapter_prefix_font_weight' => 'normal',
		'chapter_prefix_font_style'  => 'normal',
		'chapter_prefix_letter_spacing' => 0.0,
		'chapter_prefix_ornament'    => 'none',
		'header_margin_top'          => 1.0,
		'header_margin_bottom'       => 0.5,
		'header_align'               => 'center',
		'footer_margin_top'          => 0.5,
		'footer_margin_bottom'       => 1.0,
		'footer_align'               => 'center'
	);

	if ( $db_settings ) {
		foreach ( $pdf_settings as $key => $default ) {
			if ( isset( $db_settings[$key] ) ) {
				if ( is_float( $default ) ) {
					$pdf_settings[$key] = floatval( $db_settings[$key] );
				} elseif ( is_int( $default ) ) {
					$pdf_settings[$key] = intval( $db_settings[$key] );
				} else {
					$pdf_settings[$key] = $db_settings[$key];
				}
			}
		}
	}

	if ( ! isset( $pdf_settings['margin_left_odd'] ) ) {
		$pdf_settings['margin_left_odd'] = $pdf_settings['margin_left'];
		$pdf_settings['margin_right_odd'] = $pdf_settings['margin_right'];
		$pdf_settings['margin_left_even'] = $pdf_settings['margin_left'];
		$pdf_settings['margin_right_even'] = $pdf_settings['margin_right'];
	}

	// Cargar créditos desde post_meta
	$pdf_settings['credits_edition'] = get_post_meta( $book_id, '_almaden_credits_edition', true );
	$pdf_settings['credits_date'] = get_post_meta( $book_id, '_almaden_credits_date', true );
	$pdf_settings['credits_copyright'] = get_post_meta( $book_id, '_almaden_credits_copyright', true ) ?: 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.';
	$pdf_settings['credits_printer'] = get_post_meta( $book_id, '_almaden_credits_printer', true );
	$pdf_settings['credits_blank_before'] = (int) get_post_meta( $book_id, '_almaden_credits_blank_before', true );
	$pdf_settings['credits_blank_after'] = (int) get_post_meta( $book_id, '_almaden_credits_blank_after', true );
	$pdf_settings['credits_custom'] = get_post_meta( $book_id, '_almaden_credits_custom', true ) ?: '[]';

	// Cargar configuración de subtítulos
	$pdf_settings['chapter_subtitle_show'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_show', true );
	if ($pdf_settings['chapter_subtitle_show'] === '') $pdf_settings['chapter_subtitle_show'] = 1;
	$pdf_settings['chapter_subtitle_font_family'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_font_family', true );
	$pdf_settings['chapter_subtitle_font_size'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_font_size', true ) ?: 16;
		$pdf_settings['chapter_subtitle_align'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_align', true );
		if ( ! in_array( $pdf_settings['chapter_subtitle_align'], array( 'left', 'center', 'right' ), true ) ) {
			$pdf_settings['chapter_subtitle_align'] = 'center';
		}
	$pdf_settings['chapter_subtitle_font_style'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_font_style', true ) ?: 'normal';
	$pdf_settings['chapter_subtitle_text_transform'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_text_transform', true ) ?: 'none';
	$pdf_settings['chapter_subtitle_font_weight'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_font_weight', true ) ?: 'normal';
	$pdf_settings['chapter_subtitle_margin_top'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_margin_top', true );
	if ($pdf_settings['chapter_subtitle_margin_top'] === '') $pdf_settings['chapter_subtitle_margin_top'] = 0.5;
	$pdf_settings['chapter_subtitle_margin_bottom'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_margin_bottom', true );
	if ($pdf_settings['chapter_subtitle_margin_bottom'] === '') $pdf_settings['chapter_subtitle_margin_bottom'] = 0.5;
	$pdf_settings['chapter_subtitle_letter_spacing'] = get_post_meta( $book_id, '_almaden_chapter_subtitle_letter_spacing', true ) ?: 0;

	$pdf_settings['ebook_subtitle_show'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_show', true );
	if ($pdf_settings['ebook_subtitle_show'] === '') $pdf_settings['ebook_subtitle_show'] = 1;
	$pdf_settings['ebook_subtitle_font_family'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_font_family', true );
	$pdf_settings['ebook_subtitle_font_size'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_font_size', true ) ?: 18;
	$pdf_settings['ebook_subtitle_align'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_align', true ) ?: 'center';
	$pdf_settings['ebook_subtitle_font_style'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_font_style', true ) ?: 'normal';
	$pdf_settings['ebook_subtitle_text_transform'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_text_transform', true ) ?: 'none';
	$pdf_settings['ebook_subtitle_font_weight'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_font_weight', true ) ?: 'normal';
	$pdf_settings['ebook_subtitle_padding_top'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_padding_top', true );
	if ($pdf_settings['ebook_subtitle_padding_top'] === '') $pdf_settings['ebook_subtitle_padding_top'] = 0.5;
	$pdf_settings['ebook_subtitle_padding_bottom'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_padding_bottom', true );
	if ($pdf_settings['ebook_subtitle_padding_bottom'] === '') $pdf_settings['ebook_subtitle_padding_bottom'] = 0.5;
	$pdf_settings['ebook_subtitle_letter_spacing'] = get_post_meta( $book_id, '_almaden_ebook_subtitle_letter_spacing', true ) ?: 0;

	return $pdf_settings;
}

// --- AJAX Obtener Configuración PDF ---
function almaden_bookster_get_settings_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}
	$pdf_settings = almaden_get_book_pdf_settings( $book_id );
	wp_send_json_success( array( 'settings' => $pdf_settings ) );
}
add_action( 'wp_ajax_almaden_get_book_settings', 'almaden_bookster_get_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_book_settings', 'almaden_bookster_get_settings_ajax' );

// --- AJAX Obtener Plantillas de Ajustes ---
function almaden_get_settings_templates_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$templates_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/';
	$templates = array();

	if ( is_dir( $templates_dir ) ) {
		$files = glob( $templates_dir . '*.json' );
		foreach ( $files as $file ) {
			$content = file_get_contents( $file );
			if ( $content ) {
				$json = json_decode( $content, true );
				if ( json_last_error() === JSON_ERROR_NONE && isset( $json['name'], $json['settings'] ) ) {
					$json['id'] = basename( $file, '.json' );
					$templates[] = $json;
				}
			}
		}
	}

	wp_send_json_success( array( 'templates' => $templates ) );
}
add_action( 'wp_ajax_almaden_get_settings_templates', 'almaden_get_settings_templates_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_settings_templates', 'almaden_get_settings_templates_ajax' );

// --- AJAX Guardar Nueva Plantilla ---
function almaden_save_settings_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$template_name = sanitize_text_field( $_POST['template_name'] );
	if ( empty( $template_name ) ) {
		wp_send_json_error( 'El nombre de la plantilla es obligatorio.' );
	}

	$slug = sanitize_title( $template_name ) . '-' . time();
	$templates_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/';
	
	if ( ! file_exists( $templates_dir ) ) {
		mkdir( $templates_dir, 0755, true );
	}

	$file_path = $templates_dir . $slug . '.json';

	// Recopilar configuración de las variables de POST, excluyendo cosas como nonce, action, book_id
	$settings_data = array();
	$exclude_keys = array('action', 'nonce', 'book_id', 'template_name');
	
	foreach ( $_POST as $key => $value ) {
		if ( ! in_array( $key, $exclude_keys ) && strpos( $key, 'credits_' ) !== 0 ) {
			// Convertir a float si parece numérico (por márgenes), o dejar como string
			if ( is_numeric( str_replace(',', '.', $value) ) && strpos( $value, ',' ) !== false ) {
				$settings_data[$key] = floatval( str_replace( ',', '.', $value ) );
			} else if ( is_numeric( $value ) ) {
				// No todos los numéricos son int/float, algunos pueden ser '0' vs 0, pero para JSON está bien
				$settings_data[$key] = strpos($value, '.') !== false ? floatval($value) : intval($value);
			} else {
				$settings_data[$key] = sanitize_text_field( wp_unslash( $value ) );
			}
		}
	}

	$template_data = array(
		'name' => $template_name,
		'description' => 'Plantilla personalizada guardada desde el editor.',
		'settings' => $settings_data
	);

	$saved = file_put_contents( $file_path, wp_json_encode( $template_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

	if ( $saved !== false ) {
		wp_send_json_success( array( 'message' => 'Plantilla guardada con éxito.' ) );
	} else {
		wp_send_json_error( 'No se pudo escribir el archivo de la plantilla en el servidor.' );
	}
}
add_action( 'wp_ajax_almaden_save_settings_template', 'almaden_save_settings_template_ajax' );

// --- AJAX Eliminar Plantilla ---
function almaden_delete_settings_template_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$template_id = sanitize_text_field( $_POST['template_id'] );
	if ( empty( $template_id ) ) {
		wp_send_json_error( 'ID de plantilla inválido.' );
	}

	// Evitar directory traversal
	if ( strpos( $template_id, '/' ) !== false || strpos( $template_id, '\\' ) !== false || strpos( $template_id, '..' ) !== false ) {
		wp_send_json_error( 'ID de plantilla inválido.' );
	}

	$file_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/settings/' . $template_id . '.json';

	if ( file_exists( $file_path ) ) {
		if ( unlink( $file_path ) ) {
			wp_send_json_success( array( 'message' => 'Plantilla eliminada.' ) );
		} else {
			wp_send_json_error( 'No se pudo eliminar el archivo.' );
		}
	} else {
		wp_send_json_error( 'La plantilla no existe.' );
	}
}
add_action( 'wp_ajax_almaden_delete_settings_template', 'almaden_delete_settings_template_ajax' );
