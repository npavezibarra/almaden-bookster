<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/pdf-typst/page-templates/bootstrap.php';
require_once dirname( __DIR__ ) . '/pdf-typst/page-styles/bootstrap.php';

function almaden_bookster_normalize_footnote_leading_pt( $value, $font_size = 8.5, $fallback = 11.5 ) {
	$value = is_numeric( $value ) ? (float) $value : $fallback;
	return max( 0.1, min( 40, $value ) );
}

function almaden_bookster_normalize_page_one_alignment( $combined_value, $legacy_vertical = 'top', $legacy_horizontal = 'center' ) {
	$combined_value = strtolower( trim( str_replace( array( '/', ' ' ), '-', (string) $combined_value ) ) );
	$parts = array_values( array_filter( explode( '-', $combined_value ) ) );

	if ( count( $parts ) >= 2 ) {
		$horizontal = in_array( $parts[0], array( 'left', 'center', 'right' ), true ) ? $parts[0] : '';
		$vertical = in_array( $parts[1], array( 'top', 'center', 'bottom' ), true ) ? $parts[1] : '';
		if ( $horizontal && $vertical ) {
			return array(
				'horizontal' => $horizontal,
				'vertical'   => $vertical,
				'combined'   => $horizontal . '-' . $vertical,
			);
		}
	}

	$horizontal = in_array( strtolower( (string) $legacy_horizontal ), array( 'left', 'center', 'right' ), true ) ? strtolower( (string) $legacy_horizontal ) : 'center';
	$vertical = in_array( strtolower( (string) $legacy_vertical ), array( 'top', 'center', 'bottom' ), true ) ? strtolower( (string) $legacy_vertical ) : 'top';

	if ( 'half' === strtolower( (string) $legacy_vertical ) ) {
		$vertical = 'center';
	}

	return array(
		'horizontal' => $horizontal,
		'vertical'   => $vertical,
		'combined'   => $horizontal . '-' . $vertical,
	);
}

function almaden_bookster_save_settings_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	$book_language = isset( $_POST['book_language'] ) ? sanitize_text_field( wp_unslash( $_POST['book_language'] ) ) : ( isset( $_POST['content_language'] ) ? sanitize_text_field( wp_unslash( $_POST['content_language'] ) ) : 'es' );
	$footnote_font_size = isset( $_POST['footnote_font_size'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_font_size'] ) ) : 8.5;
	$footnote_line_height_raw = isset( $_POST['footnote_line_height'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_line_height'] ) ) : 11.5;
	$footnote_entry_spacing = isset( $_POST['footnote_entry_spacing'] ) ? max( 0, min( 40, floatval( str_replace( ',', '.', $_POST['footnote_entry_spacing'] ) ) ) ) : 6.0;
	$page_one_alignment = almaden_bookster_normalize_page_one_alignment(
		isset( $_POST['chapter_page_one_align'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter_page_one_align'] ) ) : '',
		isset( $_POST['chapter_page_one_vertical'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter_page_one_vertical'] ) ) : 'top',
		isset( $_POST['chapter_title_align'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter_title_align'] ) ) : 'center'
	);

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
		'page_columns_enabled'       => isset( $_POST['page_columns_enabled'] ) ? intval( $_POST['page_columns_enabled'] ) : 0,
		'page_columns_count'         => isset( $_POST['page_columns_count'] ) && '' !== trim( (string) $_POST['page_columns_count'] )
			? max( 1, min( 4, intval( $_POST['page_columns_count'] ) ) )
			: 2,
		'page_columns_gap'           => isset( $_POST['page_columns_gap'] ) && '' !== trim( (string) $_POST['page_columns_gap'] )
			? max( 0, min( 20, floatval( str_replace( ',', '.', $_POST['page_columns_gap'] ) ) ) )
			: 0.8,
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
		'content_language'           => $book_language,
		'content_hyphenation_exceptions' => isset( $_POST['content_hyphenation_exceptions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content_hyphenation_exceptions'] ) ) : '',
		'content_paragraph_indent'   => floatval( str_replace( ',', '.', $_POST['content_paragraph_indent'] ) ),
		'content_paragraph_spacing'  => floatval( str_replace( ',', '.', $_POST['content_paragraph_spacing'] ) ),
		'font_family_headings'       => isset($_POST['font_family_headings']) ? sanitize_text_field( $_POST['font_family_headings'] ) : '',
		'font_family_h1'             => sanitize_text_field( $_POST['font_family_h1'] ),
		'font_family_h2'             => sanitize_text_field( $_POST['font_family_h2'] ),
		'font_family_h3'             => sanitize_text_field( $_POST['font_family_h3'] ),
		'font_style_h1'              => isset( $_POST['font_style_h1'] ) ? sanitize_text_field( $_POST['font_style_h1'] ) : 'normal',
		'font_style_h2'              => isset( $_POST['font_style_h2'] ) ? sanitize_text_field( $_POST['font_style_h2'] ) : 'italic',
		'font_style_h3'              => isset( $_POST['font_style_h3'] ) ? sanitize_text_field( $_POST['font_style_h3'] ) : 'normal',
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
		'header_hyphenate'           => isset($_POST['header_hyphenate']) ? intval($_POST['header_hyphenate']) : 0,
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
		'first_page_header_show'     => isset( $_POST['first_page_header_show'] ) ? intval( $_POST['first_page_header_show'] ) : 1,
		'first_page_header_type'     => sanitize_text_field( $_POST['first_page_header_type'] ),
		'first_page_header_custom'   => sanitize_text_field( $_POST['first_page_header_custom'] ),
		'first_page_footer_show'     => isset( $_POST['first_page_footer_show'] ) ? intval( $_POST['first_page_footer_show'] ) : 1,
		'first_page_footer_type'     => sanitize_text_field( $_POST['first_page_footer_type'] ),
		'first_page_footer_custom'   => sanitize_text_field( $_POST['first_page_footer_custom'] ),
		'chapter_transition_blank_mode' => ( isset( $_POST['chapter_transition_blank_mode'] ) && in_array( $_POST['chapter_transition_blank_mode'], array( 'full_blank', 'blank_with_header_footer', 'intentional_text' ), true ) ) ? sanitize_text_field( $_POST['chapter_transition_blank_mode'] ) : 'full_blank',
		'chapter_transition_blank_text' => isset( $_POST['chapter_transition_blank_text'] ) ? sanitize_text_field( wp_unslash( $_POST['chapter_transition_blank_text'] ) ) : '...',
		'footnote_mode'              => ( isset( $_POST['footnote_mode'] ) && in_array( $_POST['footnote_mode'], array( 'page', 'chapter', 'book' ), true ) ) ? sanitize_text_field( $_POST['footnote_mode'] ) : 'page',
		'footnote_chapter_title'     => isset( $_POST['footnote_chapter_title'] ) ? sanitize_text_field( wp_unslash( $_POST['footnote_chapter_title'] ) ) : 'Referencia',
		'footnote_book_title'        => isset( $_POST['footnote_book_title'] ) ? sanitize_text_field( wp_unslash( $_POST['footnote_book_title'] ) ) : 'Referencias',
		'footnote_font_family'       => isset( $_POST['footnote_font_family'] ) ? sanitize_text_field( $_POST['footnote_font_family'] ) : 'Merriweather',
		'footnote_font_size'         => $footnote_font_size,
		'footnote_font_weight'       => isset( $_POST['footnote_font_weight'] ) ? sanitize_text_field( $_POST['footnote_font_weight'] ) : 'normal',
		'footnote_align'             => ( isset( $_POST['footnote_align'] ) && in_array( $_POST['footnote_align'], array( 'left', 'center', 'right', 'justify' ), true ) ) ? sanitize_text_field( $_POST['footnote_align'] ) : 'left',
		'footnote_line_height'       => almaden_bookster_normalize_footnote_leading_pt( $footnote_line_height_raw, $footnote_font_size ),
		'footnote_letter_spacing'    => isset( $_POST['footnote_letter_spacing'] ) ? max( -20, min( 20, floatval( str_replace( ',', '.', $_POST['footnote_letter_spacing'] ) ) ) ) : 0.0,
		'footnote_entry_spacing'     => $footnote_entry_spacing,
		'footnote_hyphenate'         => isset( $_POST['footnote_hyphenate'] ) ? intval( $_POST['footnote_hyphenate'] ) : 0,
		'footnote_call_scale'        => isset( $_POST['footnote_call_scale'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_call_scale'] ) ) : 0.65,
		'footnote_call_raise'        => isset( $_POST['footnote_call_raise'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_call_raise'] ) ) : 0.18,
		'footnote_padding_top'       => isset( $_POST['footnote_padding_top'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_top'] ) ) : 0.15,
		'footnote_padding_bottom'    => isset( $_POST['footnote_padding_bottom'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_bottom'] ) ) : 0.15,
		'footnote_padding_left'      => isset( $_POST['footnote_padding_left'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_left'] ) ) : 0.0,
		'footnote_padding_right'     => isset( $_POST['footnote_padding_right'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_padding_right'] ) ) : 0.0,
		'footnote_separator_show'    => isset( $_POST['footnote_separator_show'] ) ? intval( $_POST['footnote_separator_show'] ) : 0,
		'footnote_separator_align'   => ( isset( $_POST['footnote_separator_align'] ) && in_array( $_POST['footnote_separator_align'], array( 'left', 'center', 'right' ), true ) ) ? sanitize_text_field( $_POST['footnote_separator_align'] ) : 'left',
		'footnote_separator_width'   => ( isset( $_POST['footnote_separator_width'] ) && in_array( $_POST['footnote_separator_width'], array( '100', '75', '50', '25' ), true ) ) ? sanitize_text_field( $_POST['footnote_separator_width'] ) : '100',
		'footnote_separator_thickness' => isset( $_POST['footnote_separator_thickness'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_separator_thickness'] ) ) : 0.25,
		'footnote_separator_margin_bottom' => isset( $_POST['footnote_separator_margin_bottom'] ) ? floatval( str_replace( ',', '.', $_POST['footnote_separator_margin_bottom'] ) ) : 0.15,
		'chapter_start_parity'       => sanitize_text_field( $_POST['chapter_start_parity'] ),
		'parity_image_mode'          => sanitize_text_field( $_POST['parity_image_mode'] ),
		'chapter_page_one_align'     => $page_one_alignment['combined'],
		'chapter_page_one_vertical'  => $page_one_alignment['vertical'],
		'chapter_image_mode'         => ( isset( $_POST['chapter_image_mode'] ) && in_array( $_POST['chapter_image_mode'], array( 'page_blank', 'image_full_page', 'image_inner' ), true ) ) ? sanitize_text_field( $_POST['chapter_image_mode'] ) : 'page_blank',
		'chapter_image_url'          => isset( $_POST['chapter_image_url'] ) ? esc_url_raw( $_POST['chapter_image_url'] ) : '',
		'chapter_image_inner_width'   => isset( $_POST['chapter_image_inner_width'] ) ? max( 10.0, min( 100.0, floatval( str_replace( ',', '.', $_POST['chapter_image_inner_width'] ) ) ) ) : 100.0,
		'chapter_image_inner_header'  => isset( $_POST['chapter_image_inner_header'] ) ? intval( $_POST['chapter_image_inner_header'] ) : 0,
		'chapter_image_inner_footer'  => isset( $_POST['chapter_image_inner_footer'] ) ? intval( $_POST['chapter_image_inner_footer'] ) : 0,
		'chapter_title_font_family'  => sanitize_text_field( $_POST['chapter_title_font_family'] ),
		'chapter_title_font_size'    => floatval( str_replace( ',', '.', $_POST['chapter_title_font_size'] ) ),
		'chapter_title_font_weight'  => sanitize_text_field( $_POST['chapter_title_font_weight'] ),
		'chapter_title_font_style'   => sanitize_text_field( $_POST['chapter_title_font_style'] ),
		'chapter_title_align'        => ( isset( $_POST['chapter_title_align'] ) && in_array( $_POST['chapter_title_align'], array( 'left', 'center', 'right' ), true ) ) ? sanitize_text_field( $_POST['chapter_title_align'] ) : 'center',
		'chapter_title_letter_spacing' => isset( $_POST['chapter_title_letter_spacing'] ) ? floatval( str_replace( ',', '.', $_POST['chapter_title_letter_spacing'] ) ) : 0,
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

	$book_separate_opening_content = isset( $_POST['book_separate_opening_content'] ) ? intval( $_POST['book_separate_opening_content'] ) : 1;
	$book_chapter_flow_mode = isset( $_POST['book_chapter_flow_mode'] ) ? sanitize_text_field( $_POST['book_chapter_flow_mode'] ) : 'continuous';
	if ( ! in_array( $book_chapter_flow_mode, array( 'continuous', 'left' ), true ) ) {
		$book_chapter_flow_mode = 'continuous';
	}
	$data['chapter_start_parity'] = ( 'left' === $book_chapter_flow_mode ) ? 'even' : 'any';

	if ( $exists ) {
		$result = $wpdb->update( $table_name, $data, array( 'book_id' => $book_id ) );
	} else {
		$result = $wpdb->insert( $table_name, $data );
	}

	if ( false !== $result ) {
		update_post_meta( $book_id, '_almaden_book_separate_opening_content', $book_separate_opening_content ? 1 : 0 );
		update_post_meta( $book_id, '_almaden_book_chapter_flow_mode', $book_chapter_flow_mode );
		update_post_meta( $book_id, '_almaden_book_flow_legacy_parity', $data['chapter_start_parity'] );

		if ( isset( $_POST['credits_config'] ) ) {
			almaden_bookster_save_credits_from_request( $book_id, $_POST );
		}

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

		$book_authors = isset( $_POST['book_authors'] ) ? sanitize_textarea_field( wp_unslash( $_POST['book_authors'] ) ) : '';
		if ( '' !== trim( $book_authors ) ) {
			if ( function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
				almaden_bookster_sync_book_authors_from_input( $book_id, $book_authors );
			} else {
				update_post_meta( $book_id, 'book_author', $book_authors );
				update_post_meta( $book_id, '_almaden_book_author', $book_authors );
			}
		}

		if ( isset( $_POST['page_templates'] ) ) {
			almaden_bookster_typst_save_page_templates(
				$book_id,
				wp_unslash( $_POST['page_templates'] )
			);
		}

		if ( isset( $_POST['page_styles'] ) ) {
			almaden_bookster_typst_save_page_styles(
				$book_id,
				wp_unslash( $_POST['page_styles'] )
			);
		}

		wp_send_json_success( array( 'message' => 'Configuración de maquetación guardada con éxito.' ) );
	} else {
		error_log( "ALMADEN_DB_ERROR: " . $wpdb->last_error );
		wp_send_json_error( 'Error al guardar la configuración: ' . $wpdb->last_error );
	}
}
add_action( 'wp_ajax_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );

// --- AJAX Obtener Configuración PDF ---
function almaden_bookster_get_settings_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}
	$pdf_settings = almaden_get_book_pdf_settings( $book_id );
	$book_authors_input_value = get_post_meta( $book_id, 'book_author', true );
	if ( '' === trim( (string) $book_authors_input_value ) ) {
		$book_authors_input_value = get_post_meta( $book_id, '_almaden_book_author', true );
	}
	if ( '' === trim( (string) $book_authors_input_value ) && function_exists( 'almaden_bookster_get_book_author_edit_tokens' ) ) {
		$book_authors_input_value = almaden_bookster_get_book_author_edit_tokens( $book_id );
	}

	wp_send_json_success(
		array(
			'settings'                => $pdf_settings,
			'book_authors_input_value' => $book_authors_input_value,
		)
	);
}
add_action( 'wp_ajax_almaden_get_book_settings', 'almaden_bookster_get_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_book_settings', 'almaden_bookster_get_settings_ajax' );
