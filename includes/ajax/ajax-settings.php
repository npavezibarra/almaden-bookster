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
		'ebook_cover_panel_bg_type'  => isset($_POST['ebook_cover_panel_bg_type']) ? sanitize_text_field($_POST['ebook_cover_panel_bg_type']) : 'image',
		'ebook_cover_panel_bg_color' => isset($_POST['ebook_cover_panel_bg_color']) ? sanitize_text_field($_POST['ebook_cover_panel_bg_color']) : 'transparent',
		'ebook_cover_panel_bg_image' => isset($_POST['ebook_cover_panel_bg_image']) ? esc_url_raw($_POST['ebook_cover_panel_bg_image']) : '',
		'ebook_font_family_content'  => isset($_POST['ebook_font_family_content']) ? sanitize_text_field($_POST['ebook_font_family_content']) : 'Merriweather',
		'ebook_font_size_content'    => isset($_POST['ebook_font_size_content']) ? floatval(str_replace(',', '.', $_POST['ebook_font_size_content'])) : 18.0,
		'ebook_font_weight_content'  => isset($_POST['ebook_font_weight_content']) ? sanitize_text_field($_POST['ebook_font_weight_content']) : 'normal',
		'ebook_line_height_content'  => isset($_POST['ebook_line_height_content']) ? floatval(str_replace(',', '.', $_POST['ebook_line_height_content'])) : 1.8,
		'ebook_font_family_headings' => isset($_POST['ebook_font_family_headings']) ? sanitize_text_field($_POST['ebook_font_family_headings']) : 'Playfair Display',
		'ebook_font_size_headings'   => isset($_POST['ebook_font_size_headings']) ? floatval(str_replace(',', '.', $_POST['ebook_font_size_headings'])) : 32.0,
		'ebook_font_weight_headings' => isset($_POST['ebook_font_weight_headings']) ? sanitize_text_field($_POST['ebook_font_weight_headings']) : 'bold',
		'ebook_line_height_headings' => isset($_POST['ebook_line_height_headings']) ? floatval(str_replace(',', '.', $_POST['ebook_line_height_headings'])) : 1.3,
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

	error_log( "ALMADEN_DEBUG_POST: " . print_r( $_POST, true ) );
	error_log( "ALMADEN_DEBUG_DATA: " . print_r( $data, true ) );

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
		'ebook_cover_panel_bg_type'  => 'image',
		'ebook_cover_panel_bg_color' => 'transparent',
		'ebook_cover_panel_bg_image' => '',
		'ebook_font_family_content'  => 'Merriweather',
		'ebook_font_size_content'    => 18.0,
		'ebook_font_weight_content'  => 'normal',
		'ebook_line_height_content'  => 1.8,
		'ebook_font_family_headings' => 'Playfair Display',
		'ebook_font_size_headings'   => 32.0,
		'ebook_font_weight_headings' => 'bold',
		'ebook_line_height_headings' => 1.3,
		'font_family_content'        => 'Merriweather',
		'font_size_content'          => 11.5,
		'line_height_content'        => 1.65,
		'content_text_align'         => 'justify',
		'content_hyphenation'        => 1,
		'content_language'           => 'es',
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
		'header_letter_spacing'      => 0.1,
		'header_even_type'           => 'book_title',
		'header_even_custom'         => '',
		'header_odd_type'            => 'chapter_title',
		'header_odd_custom'          => '',
		'footer_font_family'         => 'Merriweather',
		'footer_font_size'           => 9.0,
		'footer_font_weight'         => 'normal',
		'footer_font_style'          => 'normal',
		'footer_letter_spacing'      => 0.0,
		'footer_even_type'           => 'page_number',
		'footer_odd_type'            => 'page_number',
		'first_page_header_type'     => 'blank',
		'first_page_header_custom'   => '',
		'first_page_footer_type'     => 'page_number',
		'first_page_footer_custom'   => '',
		'chapter_start_parity'       => 'any',
		'parity_image_mode'          => 'content',
		'chapter_page_one_align'     => 'center',
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
