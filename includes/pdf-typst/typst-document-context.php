<?php
/**
 * Build a complete Typst book document from editor state.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-document-helpers.php';

function almaden_bookster_typst_build_document_context( $payload ) {
	$settings = isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : array();
	$chapters = isset( $payload['chapters'] ) && is_array( $payload['chapters'] ) ? $payload['chapters'] : array();
	$preview = isset( $payload['preview'] ) && is_array( $payload['preview'] ) ? $payload['preview'] : array();
	$page_template_context = almaden_bookster_typst_page_template_context( $settings );
	$page_styles = function_exists( 'almaden_bookster_typst_page_styles_from_settings' )
		? almaden_bookster_typst_page_styles_from_settings( $settings )
		: array();
	$unit     = isset( $settings['unit'] ) && in_array( $settings['unit'], array( 'mm', 'cm', 'in', 'pt' ), true )
		? $settings['unit'] : 'cm';

	$width       = almaden_bookster_typst_number( $settings, 'page_width', 21, 5, 100 );
	$height      = almaden_bookster_typst_number( $settings, 'page_height', 29.7, 5, 100 );
	$margin_top  = almaden_bookster_typst_number( $settings, 'margin_top', 2.5, 0, 30 );
	$margin_bot  = almaden_bookster_typst_number( $settings, 'margin_bottom', 2.5, 0, 30 );
	$margin_inside_odd = almaden_bookster_typst_number(
		$settings,
		'margin_left_odd',
		almaden_bookster_typst_number( $settings, 'margin_left', 2, 0, 30 ),
		0,
		30
	);
	$margin_outside_odd = almaden_bookster_typst_number(
		$settings,
		'margin_right_odd',
		almaden_bookster_typst_number( $settings, 'margin_right', 2, 0, 30 ),
		0,
		30
	);
	$margin_inside_even = almaden_bookster_typst_number( $settings, 'margin_right_even', $margin_inside_odd, 0, 30 );
	$margin_outside_even = almaden_bookster_typst_number( $settings, 'margin_left_even', $margin_outside_odd, 0, 30 );
	$padding_top    = almaden_bookster_typst_number( $settings, 'padding_top', 0, 0, 30 );
	$padding_bottom = almaden_bookster_typst_number( $settings, 'padding_bottom', 0, 0, 30 );
	$padding_left   = almaden_bookster_typst_number( $settings, 'padding_left', 0, 0, 30 );
	$padding_right  = almaden_bookster_typst_number( $settings, 'padding_right', 0, 0, 30 );
	$bleed          = almaden_bookster_typst_number( $settings, 'bleeding', 0, 0, 10 );
	$margin_top    += $padding_top;
	$margin_bot    += $padding_bottom;
	$margin_inside  = round( ( $margin_inside_odd + $padding_left + $margin_inside_even + $padding_right ) / 2, 4 );
	$margin_outside = round( ( $margin_outside_odd + $padding_right + $margin_outside_even + $padding_left ) / 2, 4 );
	$font_size     = almaden_bookster_typst_number( $settings, 'font_size_content', 11.5, 5, 72 );
	$font_weight   = almaden_bookster_typst_font_weight( $settings['font_weight_content'] ?? 'normal' );
	$font_family   = almaden_bookster_typst_font_family( $settings['font_family_content'] ?? 'Merriweather' );
	$line_height   = almaden_bookster_typst_number( $settings, 'line_height_content', 1.65, 0.8, 4 );
	$paragraph_gap = almaden_bookster_typst_number( $settings, 'content_paragraph_spacing', 14, 0, 200 );
	$paragraph_indent = almaden_bookster_typst_number( $settings, 'content_paragraph_indent', 0, 0, 200 );
	$title_size  = almaden_bookster_typst_number( $settings, 'chapter_title_font_size', 24, 8, 100 );
	$title_gap   = almaden_bookster_typst_number( $settings, 'chapter_title_padding_bottom', 1.5, 0, 20 );
	$title_font_family = almaden_bookster_typst_font_family( $settings['chapter_title_font_family'] ?? $font_family );
	$title_font_weight = almaden_bookster_typst_font_weight( $settings['chapter_title_font_weight'] ?? 'bold' );
	$title_font_style   = isset( $settings['chapter_title_font_style'] ) ? strtolower( trim( (string) $settings['chapter_title_font_style'] ) ) : 'normal';
	if ( ! in_array( $title_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$title_font_style = 'normal';
	}
	$title_letter_spacing = almaden_bookster_typst_number( $settings, 'chapter_title_letter_spacing', 0, -20, 20 );
	$lang        = isset( $settings['book_language'] ) ? preg_replace( '/[^a-zA-Z-]/', '', $settings['book_language'] ) : 'es';
	$text_align  = isset( $settings['content_text_align'] ) &&
		in_array( $settings['content_text_align'], array( 'left', 'center', 'right', 'justify' ), true )
		? $settings['content_text_align'] : 'justify';
	$last_align  = isset( $settings['content_text_align_last'] ) &&
		in_array( $settings['content_text_align_last'], array( 'left', 'center', 'right' ), true )
		? $settings['content_text_align_last'] : ( 'justify' === $text_align ? 'left' : $text_align );
	$justify     = 'justify' === $text_align;
	$hyphenate   = ! isset( $settings['content_hyphenation'] ) || '0' !== (string) $settings['content_hyphenation'];
	$hyphenation_exceptions = isset( $settings['content_hyphenation_exceptions'] )
		? preg_split( '/[,;\n]+/u', (string) $settings['content_hyphenation_exceptions'], -1, PREG_SPLIT_NO_EMPTY )
		: array();
	$title_align = isset( $settings['chapter_title_align'] ) &&
		in_array( $settings['chapter_title_align'], array( 'left', 'center', 'right' ), true )
		? $settings['chapter_title_align'] : 'center';
	$title_transform = isset( $settings['chapter_title_text_transform'] ) ? $settings['chapter_title_text_transform'] : 'none';
	$leading_em = max( 0, round( $line_height - 1, 4 ) );
	$font = almaden_bookster_typst_resolve_font( $font_family, $font_weight );
	$font_error = function_exists( 'is_wp_error' ) && is_wp_error( $font ) ? $font : null;
	if ( ! $font_error ) {
		$font_family = $font['family'];
	}
	$title_font = almaden_bookster_typst_resolve_font( $title_font_family, $title_font_weight );
	$title_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $title_font ) ? $title_font : null;
	if ( ! $title_font_error ) {
		$title_font_family = $title_font['family'];
	}
	$prefix_size = almaden_bookster_typst_number( $settings, 'chapter_prefix_font_size', 16, 6, 100 );
	$prefix_font_family = almaden_bookster_typst_font_family( $settings['chapter_prefix_font_family'] ?? $font_family );
	$prefix_font_weight = almaden_bookster_typst_font_weight( $settings['chapter_prefix_font_weight'] ?? 'normal' );
	$prefix_font_style = isset( $settings['chapter_prefix_font_style'] ) ? strtolower( trim( (string) $settings['chapter_prefix_font_style'] ) ) : 'normal';
	if ( ! in_array( $prefix_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$prefix_font_style = 'normal';
	}
	$prefix_letter_spacing = almaden_bookster_typst_number( $settings, 'chapter_prefix_letter_spacing', 0, -20, 20 );
	$prefix_ornament = almaden_bookster_typst_chapter_prefix_ornament( $settings['chapter_prefix_ornament'] ?? 'none' );
	$prefix_font = almaden_bookster_typst_resolve_font( $prefix_font_family, $prefix_font_weight );
	$prefix_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $prefix_font ) ? $prefix_font : null;
	if ( ! $prefix_font_error ) {
		$prefix_font_family = $prefix_font['family'];
	}
	$prefix_style = array(
		'font_family'    => $prefix_font_family,
		'font_size'      => $prefix_size,
		'font_weight'    => $prefix_font_weight,
		'font_style'     => $prefix_font_style,
		'letter_spacing' => $prefix_letter_spacing,
	);
	$heading1_font_family = almaden_bookster_typst_font_family( $settings['font_family_h1'] ?? $font_family );
	$heading2_font_family = almaden_bookster_typst_font_family( $settings['font_family_h2'] ?? $font_family );
	$heading3_font_family = almaden_bookster_typst_font_family( $settings['font_family_h3'] ?? $font_family );
	$heading1_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h1', 24, 5, 100 );
	$heading2_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h2', 18, 5, 100 );
	$heading3_font_size   = almaden_bookster_typst_number( $settings, 'font_size_h3', 14, 5, 100 );
	$heading1_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h1'] ?? 'bold' );
	$heading2_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h2'] ?? 'bold' );
	$heading3_font_weight = almaden_bookster_typst_font_weight( $settings['font_weight_h3'] ?? 'bold' );
	$heading1_font_style   = isset( $settings['font_style_h1'] ) ? strtolower( trim( (string) $settings['font_style_h1'] ) ) : 'normal';
	$heading2_font_style   = isset( $settings['font_style_h2'] ) ? strtolower( trim( (string) $settings['font_style_h2'] ) ) : 'normal';
	$heading3_font_style   = isset( $settings['font_style_h3'] ) ? strtolower( trim( (string) $settings['font_style_h3'] ) ) : 'normal';
	if ( ! in_array( $heading1_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading1_font_style = 'normal';
	}
	if ( ! in_array( $heading2_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading2_font_style = 'normal';
	}
	if ( ! in_array( $heading3_font_style, array( 'normal', 'italic', 'oblique' ), true ) ) {
		$heading3_font_style = 'normal';
	}
	$heading1_font = almaden_bookster_typst_resolve_font( $heading1_font_family, $heading1_font_weight );
	$heading2_font = almaden_bookster_typst_resolve_font( $heading2_font_family, $heading2_font_weight );
	$heading3_font = almaden_bookster_typst_resolve_font( $heading3_font_family, $heading3_font_weight );
	$heading1_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading1_font ) ? $heading1_font : null;
	$heading2_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading2_font ) ? $heading2_font : null;
	$heading3_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $heading3_font ) ? $heading3_font : null;
	if ( ! $heading1_font_error ) {
		$heading1_font_family = $heading1_font['family'];
	}
	if ( ! $heading2_font_error ) {
		$heading2_font_family = $heading2_font['family'];
	}
	if ( ! $heading3_font_error ) {
		$heading3_font_family = $heading3_font['family'];
	}

	$book_title = isset( $payload['title'] ) ? (string) $payload['title'] : '';
	$header_font_family = almaden_bookster_typst_font_family( $settings['header_font_family'] ?? 'Merriweather' );
	$footer_font_family = almaden_bookster_typst_font_family( $settings['footer_font_family'] ?? 'Merriweather' );
	$header_font_size = almaden_bookster_typst_number( $settings, 'header_font_size', 8.5, 4, 48 );
	$footer_font_size = almaden_bookster_typst_number( $settings, 'footer_font_size', 9, 4, 48 );
	$header_font_weight = almaden_bookster_typst_font_weight( $settings['header_font_weight'] ?? 'normal' );
	$footer_font_weight = almaden_bookster_typst_font_weight( $settings['footer_font_weight'] ?? 'normal' );
	$header_font_style = isset( $settings['header_font_style'] ) ? $settings['header_font_style'] : 'normal';
	$footer_font_style = isset( $settings['footer_font_style'] ) ? $settings['footer_font_style'] : 'normal';
	$header_text_transform = isset( $settings['header_text_transform'] ) ? $settings['header_text_transform'] : 'none';
	$footer_text_transform = isset( $settings['footer_text_transform'] ) ? $settings['footer_text_transform'] : 'none';
	$header_hyphenate = almaden_bookster_typst_bool( $settings['header_hyphenate'] ?? false );
	$header_letter_spacing = almaden_bookster_typst_number( $settings, 'header_letter_spacing', 0.1, -20, 20 );
	$footer_letter_spacing = almaden_bookster_typst_number( $settings, 'footer_letter_spacing', 0, -20, 20 );
	$header_align = isset( $settings['header_align'] ) ? $settings['header_align'] : 'center';
	$footer_align = isset( $settings['footer_align'] ) ? $settings['footer_align'] : 'center';
	$header_margin_top = almaden_bookster_typst_number( $settings, 'header_margin_top', 1.0, 0, 20 );
	$header_margin_bottom = almaden_bookster_typst_number( $settings, 'header_margin_bottom', 0.5, 0, 20 );
	$footer_margin_top = almaden_bookster_typst_number( $settings, 'footer_margin_top', 0.5, 0, 20 );
	$footer_margin_bottom = almaden_bookster_typst_number( $settings, 'footer_margin_bottom', 1.0, 0, 20 );
	$header_font = almaden_bookster_typst_resolve_font( $header_font_family, $header_font_weight );
	$footer_font = almaden_bookster_typst_resolve_font( $footer_font_family, $footer_font_weight );
	$header_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $header_font ) ? $header_font : null;
	$footer_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $footer_font ) ? $footer_font : null;
	if ( ! $header_font_error ) {
		$header_font_family = $header_font['family'];
	}
	if ( ! $footer_font_error ) {
		$footer_font_family = $footer_font['family'];
	}
	$footnote_font_family = almaden_bookster_typst_font_family( $settings['footnote_font_family'] ?? $font_family, $font_family );
	$footnote_font_size = almaden_bookster_typst_number( $settings, 'footnote_font_size', 8.5, 4, 48 );
	$footnote_font_weight = almaden_bookster_typst_font_weight( $settings['footnote_font_weight'] ?? 'normal' );
	$footnote_align = almaden_bookster_typst_footnote_alignment( $settings['footnote_align'] ?? 'left' );
	$footnote_line_height = almaden_bookster_typst_footnote_leading_pt( $settings['footnote_line_height'] ?? null, $footnote_font_size, 11.5 );
	$footnote_entry_spacing = almaden_bookster_typst_footnote_spacing_pt( $settings['footnote_entry_spacing'] ?? null, 6 );
	$footnote_letter_spacing = almaden_bookster_typst_number( $settings, 'footnote_letter_spacing', 0, -20, 20 );
	$footnote_hyphenate = almaden_bookster_typst_bool( $settings['footnote_hyphenate'] ?? false );
	$footnote_call_scale = isset( $settings['footnote_call_scale'] ) && is_numeric( $settings['footnote_call_scale'] ) ? max( 0.1, min( 2.0, (float) $settings['footnote_call_scale'] ) ) : 0.65;
	$footnote_call_raise = isset( $settings['footnote_call_raise'] ) && is_numeric( $settings['footnote_call_raise'] ) ? max( 0, min( 2.0, (float) $settings['footnote_call_raise'] ) ) : 0.18;
	$footnote_padding_top = isset( $settings['footnote_padding_top'] ) && is_numeric( $settings['footnote_padding_top'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_top'] ) ) : 0.15;
	$footnote_padding_bottom = isset( $settings['footnote_padding_bottom'] ) && is_numeric( $settings['footnote_padding_bottom'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_bottom'] ) ) : 0.15;
	$footnote_padding_left = isset( $settings['footnote_padding_left'] ) && is_numeric( $settings['footnote_padding_left'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_left'] ) ) : 0;
	$footnote_padding_right = isset( $settings['footnote_padding_right'] ) && is_numeric( $settings['footnote_padding_right'] ) ? max( 0, min( 10, (float) $settings['footnote_padding_right'] ) ) : 0;
	$footnote_separator_show = almaden_bookster_typst_bool( $settings['footnote_separator_show'] ?? false );
	$footnote_separator_align = isset( $settings['footnote_separator_align'] ) && in_array( $settings['footnote_separator_align'], array( 'left', 'center', 'right' ), true ) ? $settings['footnote_separator_align'] : 'left';
	$footnote_separator_width = isset( $settings['footnote_separator_width'] ) && in_array( (string) $settings['footnote_separator_width'], array( '100', '75', '50', '25' ), true ) ? (string) $settings['footnote_separator_width'] : '100';
	$footnote_separator_thickness = isset( $settings['footnote_separator_thickness'] ) && is_numeric( $settings['footnote_separator_thickness'] ) ? max( 0.05, min( 5, (float) $settings['footnote_separator_thickness'] ) ) : 0.25;
	$footnote_separator_margin_bottom = isset( $settings['footnote_separator_margin_bottom'] ) && is_numeric( $settings['footnote_separator_margin_bottom'] ) ? max( 0, min( 10, (float) $settings['footnote_separator_margin_bottom'] ) ) : 0.15;
	$page_columns_enabled = almaden_bookster_typst_bool( $settings['page_columns_enabled'] ?? false );
	$page_columns_count = isset( $settings['page_columns_count'] ) && is_numeric( $settings['page_columns_count'] )
		? max( 1, min( 4, (int) $settings['page_columns_count'] ) )
		: 2;
	$page_columns_gap = isset( $settings['page_columns_gap'] ) && is_numeric( $settings['page_columns_gap'] )
		? max( 0, min( 20, (float) $settings['page_columns_gap'] ) )
		: 0.8;
	$footnote_font = almaden_bookster_typst_resolve_font( $footnote_font_family, $footnote_font_weight );
	$footnote_font_error = function_exists( 'is_wp_error' ) && is_wp_error( $footnote_font ) ? $footnote_font : null;
	$footnote_font_assets = array();
	if ( ! $footnote_font_error ) {
		$footnote_font_family = $footnote_font['family'];
		$footnote_font_assets = array_values( array_unique( (array) ( $footnote_font['files'] ?? array() ) ) );
	} else {
		$footnote_font_family = $font_family;
	}
	$footnote_mode = almaden_bookster_typst_footnote_mode( $settings );
	$footnote_chapter_title = almaden_bookster_typst_footnote_title( $settings, 'chapter' );
	$footnote_book_title = almaden_bookster_typst_footnote_title( $settings, 'book' );
	$first_page_header_show = almaden_bookster_typst_bool( $settings['first_page_header_show'] ?? true );
	$first_page_footer_show = almaden_bookster_typst_bool( $settings['first_page_footer_show'] ?? true );
	$first_page_header_type = almaden_bookster_typst_normalize_header_footer_type( $settings['first_page_header_type'] ?? 'blank' );
	$first_page_footer_type = almaden_bookster_typst_normalize_header_footer_type( $settings['first_page_footer_type'] ?? 'page_number' );
	$first_page_header_custom = isset( $settings['first_page_header_custom'] ) ? (string) $settings['first_page_header_custom'] : '';
	$first_page_footer_custom = isset( $settings['first_page_footer_custom'] ) ? (string) $settings['first_page_footer_custom'] : '';
	$header_even_type = almaden_bookster_typst_normalize_header_footer_type( $settings['header_even_type'] ?? 'book_title' );
	$header_odd_type = almaden_bookster_typst_normalize_header_footer_type( $settings['header_odd_type'] ?? 'chapter_title' );
	$footer_even_type = almaden_bookster_typst_normalize_header_footer_type( $settings['footer_even_type'] ?? 'page_number' );
	$footer_odd_type = almaden_bookster_typst_normalize_header_footer_type( $settings['footer_odd_type'] ?? 'page_number' );
	$header_even_custom = isset( $settings['header_even_custom'] ) ? (string) $settings['header_even_custom'] : '';
	$header_odd_custom = isset( $settings['header_odd_custom'] ) ? (string) $settings['header_odd_custom'] : '';
	$footer_even_custom = isset( $settings['footer_even_custom'] ) ? (string) $settings['footer_even_custom'] : '';
	$footer_odd_custom = isset( $settings['footer_odd_custom'] ) ? (string) $settings['footer_odd_custom'] : '';

	$header_has_content = (
		( $first_page_header_show && almaden_bookster_typst_running_element_has_content( $first_page_header_type, $first_page_header_custom ) ) ||
		almaden_bookster_typst_running_element_has_content( $header_even_type, $header_even_custom ) ||
		almaden_bookster_typst_running_element_has_content( $header_odd_type, $header_odd_custom )
	);
	$footer_has_content = (
		( $first_page_footer_show && almaden_bookster_typst_running_element_has_content( $first_page_footer_type, $first_page_footer_custom ) ) ||
		almaden_bookster_typst_running_element_has_content( $footer_even_type, $footer_even_custom ) ||
		almaden_bookster_typst_running_element_has_content( $footer_odd_type, $footer_odd_custom )
	);
	$asset_mode = function_exists( 'almaden_bookster_typst_normalize_asset_mode' )
		? almaden_bookster_typst_normalize_asset_mode( $preview['assetMode'] ?? ( $settings['pdf_preview_asset_mode'] ?? 'original' ) )
		: ( 'original' === (string) ( $preview['assetMode'] ?? '' ) ? 'original' : 'optimized' );

	$header_reserve = $header_has_content
		? round( $header_margin_top + almaden_bookster_typst_pt_to_unit( $header_font_size, $unit ) + $header_margin_bottom, 4 )
		: 0;
	$footer_reserve = $footer_has_content
		? round( $footer_margin_top + almaden_bookster_typst_pt_to_unit( $footer_font_size, $unit ) + $footer_margin_bottom, 4 )
		: 0;
	$page_template_context['asset_mode'] = $asset_mode;
	$page_template_context['preview_asset_mode'] = $asset_mode;
	return get_defined_vars();
}
