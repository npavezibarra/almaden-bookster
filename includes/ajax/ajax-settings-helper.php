<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		'footnote_separator_show'    => 0,
		'footnote_separator_align'   => 'left',
		'footnote_separator_width'   => '100',
		'footnote_separator_thickness' => 0.25,
		'footnote_separator_margin_bottom' => 0.15,
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
		'font_style_h1'              => 'normal',
		'font_style_h2'              => 'italic',
		'font_style_h3'              => 'normal',
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
