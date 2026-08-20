<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/pdf-typst/page-templates/bootstrap.php';
require_once dirname( __DIR__ ) . '/pdf-typst/page-styles/bootstrap.php';

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
		'page_columns_enabled'       => 0,
		'page_columns_count'         => 2,
		'page_columns_gap'           => 0.8,
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
		'ebook_chapter_title_font_style' => 'normal',
		'ebook_line_height_headings' => 1.3,
		'ebook_text_align_justify'   => 0,
		'ebook_hyphenation'          => 0,
		'ebook_chapter_title_align'  => 'center',
		'ebook_chapter_title_text_transform' => 'none',
		'ebook_chapter_title_padding_top' => 2.0,
		'ebook_chapter_title_padding_bottom' => 2.0,
		'ebook_chapter_title_padding_left' => 0.0,
		'ebook_chapter_title_padding_right' => 0.0,
		'ebook_chapter_title_hyphenate' => 0,
		'ebook_chapter_prefix_show'  => 0,
		'ebook_chapter_prefix_template' => 'Capítulo {N}',
		'ebook_chapter_prefix_position' => 'above',
		'ebook_chapter_prefix_align' => 'center',
		'ebook_chapter_prefix_font_family' => 'Playfair Display',
		'ebook_chapter_prefix_font_size' => 16.0,
		'ebook_chapter_prefix_font_weight' => 'normal',
		'ebook_chapter_prefix_font_style' => 'normal',
		'ebook_chapter_prefix_letter_spacing' => 0.0,
		'ebook_chapter_prefix_ornament' => 'none',
		'footnote_mode'              => 'page',
		'footnote_chapter_new_page'  => 0,
		'footnote_chapter_title'     => 'Referencia',
		'footnote_book_title'        => 'Referencias',
		'footnote_font_family'       => 'Merriweather',
		'footnote_font_size'         => 8.5,
		'footnote_font_weight'       => 'normal',
		'footnote_align'             => 'left',
		'footnote_line_height'       => 11.5,
		'footnote_letter_spacing'    => 0.0,
		'footnote_entry_spacing'     => 6.0,
		'footnote_hyphenate'         => 0,
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
		'book_language'              => 'es',
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
		'header_hyphenate'           => 0,
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
		'first_page_header_show'     => 1,
		'first_page_header_type'     => 'blank',
		'first_page_header_custom'   => '',
		'first_page_footer_show'     => 1,
		'first_page_footer_type'     => 'page_number',
		'first_page_footer_custom'   => '',
		'book_separate_opening_content' => 1,
		'book_chapter_flow_mode'     => 'continuous',
		'chapter_start_parity'       => 'any',
		'chapter_image_default'     => '0',
		'chapter_transition_blank_mode' => 'full_blank',
		'chapter_transition_blank_text' => '...',
		'parity_image_mode'          => 'content',
		'chapter_image_mode'         => 'page_blank',
		'chapter_image_url'          => '',
		'chapter_image_inner_width'  => 100.0,
		'chapter_image_inner_header' => 0,
		'chapter_image_inner_footer' => 0,
		'chapter_page_one_align'     => 'center-top',
		'chapter_page_one_vertical'  => 'top',
		/*
		 * Preview-specific defaults. These values only define the data contract
		 * for the next phases; the current render path still composes the full
		 * PDF until the chapter-preview pipeline is implemented.
		 */
		'pdf_preview_mode'           => 'chapter',
		'pdf_preview_asset_mode'      => 'optimized',
		'pdf_preview_counter_mode'    => 'global',
		'chapter_title_font_family'  => 'Playfair Display',
		'chapter_title_font_size'    => 24.0,
		'chapter_title_font_weight'  => 'bold',
		'chapter_title_font_style'   => 'normal',
		'chapter_title_align'        => 'center',
		'chapter_title_letter_spacing' => 0.0,
		'chapter_title_padding_top'  => 0.0,
		'chapter_title_padding_bottom'=> 1.5,
		'chapter_title_padding_left' => 0.0,
		'chapter_title_padding_right'=> 0.0,
		'chapter_title_line_height'  => 1.2,
		'chapter_title_hyphenate'    => 0,
		'chapter_title_text_transform' => 'none',
		'chapter_prefix_show'        => 0,
		'chapter_prefix_template'    => 'Capítulo {N}',
		'chapter_prefix_position'    => 'above',
		'chapter_prefix_align'       => 'center',
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

	// A creation template is applied again on the first read as a safety net.
	// This covers installs where the settings table was unavailable during the
	// redirect immediately after book creation. The marker is removed as soon
	// as the editor saves its own settings.
	$template_seed_pending = get_post_meta( $book_id, '_almaden_book_template_seed_pending', true );
	$template_key = get_post_meta( $book_id, '_almaden_book_template', true );
	$template_label = get_post_meta( $book_id, '_almaden_book_template_label', true );
	$template_needs_bootstrap = $template_seed_pending;
	if ( ! $template_needs_bootstrap && $db_settings && 'Merriweather' === (string) ( $db_settings['font_family_content'] ?? '' ) ) {
		$template_needs_bootstrap = '' !== $template_key || '' !== $template_label;
	}

	if ( $template_needs_bootstrap ) {
		if ( function_exists( 'almaden_bookster_get_book_template_payload_for_seed' ) ) {
			$template = almaden_bookster_get_book_template_payload_for_seed( $template_key, $template_label );
			$template_settings = ( $template && isset( $template['settings'] ) && is_array( $template['settings'] ) )
				? almaden_bookster_flatten_book_template_settings( $template['settings'] )
				: array();
			foreach ( $template_settings as $key => $value ) {
				if ( array_key_exists( $key, $pdf_settings ) ) {
					$pdf_settings[ $key ] = $value;
				}
			}

			if ( isset( $template_settings['book_separate_opening_content'] ) ) {
				$pdf_settings['book_separate_opening_content'] = intval( $template_settings['book_separate_opening_content'] );
				update_post_meta( $book_id, '_almaden_book_separate_opening_content', $pdf_settings['book_separate_opening_content'] ? 1 : 0 );
			}
			if ( isset( $template_settings['book_chapter_flow_mode'] ) ) {
				$pdf_settings['book_chapter_flow_mode'] = sanitize_text_field( (string) $template_settings['book_chapter_flow_mode'] );
				update_post_meta( $book_id, '_almaden_book_chapter_flow_mode', $pdf_settings['book_chapter_flow_mode'] );
			}
			foreach ( array(
				'chapter_subtitle_show', 'chapter_subtitle_font_family', 'chapter_subtitle_font_size',
				'chapter_subtitle_align', 'chapter_subtitle_font_style', 'chapter_subtitle_text_transform',
				'chapter_subtitle_font_weight', 'chapter_subtitle_margin_top', 'chapter_subtitle_margin_bottom',
				'chapter_subtitle_letter_spacing', 'ebook_subtitle_show', 'ebook_subtitle_font_family',
				'ebook_subtitle_font_size', 'ebook_subtitle_align', 'ebook_subtitle_font_style',
				'ebook_subtitle_text_transform', 'ebook_subtitle_font_weight', 'ebook_subtitle_padding_top',
				'ebook_subtitle_padding_bottom', 'ebook_subtitle_letter_spacing',
			) as $meta_field ) {
				if ( array_key_exists( $meta_field, $template_settings ) ) {
					update_post_meta( $book_id, '_almaden_' . $meta_field, $template_settings[ $meta_field ] );
				}
			}

			if ( ! $template_seed_pending ) {
				update_post_meta( $book_id, '_almaden_book_template_seed_pending', 1 );
			}
		}
	}

	$credits_config_raw = get_post_meta( $book_id, '_almaden_credits_config', true );
	if ( ( '' === trim( (string) $credits_config_raw ) || '[]' === trim( (string) $credits_config_raw ) ) && function_exists( 'almaden_bookster_seed_default_credits_config_for_book' ) ) {
		$book_post = get_post( $book_id );
		$author_label = '';
		if ( $book_post && ! empty( $book_post->post_author ) ) {
			$author_user = get_userdata( (int) $book_post->post_author );
			if ( $author_user ) {
				$author_label = trim( (string) ( $author_user->display_name ?: $author_user->user_nicename ) );
			}
		}
		$created_month = $book_post ? mysql2date( 'Y-m', (string) $book_post->post_date, false ) : current_time( 'Y-m' );
		$seed_credits = almaden_bookster_seed_default_credits_config_for_book( $book_id, $author_label, $created_month );
		if ( ! is_wp_error( $seed_credits ) ) {
			$pdf_settings['credits_config'] = $seed_credits;
		}
	}

	if ( ! isset( $pdf_settings['margin_left_odd'] ) ) {
		$pdf_settings['margin_left_odd'] = $pdf_settings['margin_left'];
		$pdf_settings['margin_right_odd'] = $pdf_settings['margin_right'];
		$pdf_settings['margin_left_even'] = $pdf_settings['margin_left'];
		$pdf_settings['margin_right_even'] = $pdf_settings['margin_right'];
	}

	$pdf_settings['page_templates'] = almaden_bookster_typst_get_page_templates( $book_id );
	$pdf_settings['page_styles'] = function_exists( 'almaden_bookster_typst_get_page_styles' )
		? almaden_bookster_typst_get_page_styles( $book_id )
		: array();

	$pdf_settings['book_language'] = function_exists( 'almaden_bookster_get_book_language_from_settings' )
		? almaden_bookster_get_book_language_from_settings( $pdf_settings, 'es' )
		: ( isset( $pdf_settings['content_language'] ) ? $pdf_settings['content_language'] : 'es' );

	// Cargar créditos desde post_meta
	$legacy_credits = array(
		'credits_edition'      => get_post_meta( $book_id, '_almaden_credits_edition', true ),
		'credits_date'         => get_post_meta( $book_id, '_almaden_credits_date', true ),
		'credits_isbn'         => get_post_meta( $book_id, '_almaden_credits_isbn', true ),
		'credits_copyright'    => get_post_meta( $book_id, '_almaden_credits_copyright', true ),
		'credits_printer'      => get_post_meta( $book_id, '_almaden_credits_printer', true ),
		'credits_blank_before' => (int) get_post_meta( $book_id, '_almaden_credits_blank_before', true ),
		'credits_blank_after'  => (int) get_post_meta( $book_id, '_almaden_credits_blank_after', true ),
		'credits_license'      => get_post_meta( $book_id, '_almaden_credits_license', true ),
		'credits_custom'       => get_post_meta( $book_id, '_almaden_credits_custom', true ),
	);

	$credits_config_raw = get_post_meta( $book_id, '_almaden_credits_config', true );
	$pdf_settings['credits_config'] = function_exists( 'almaden_bookster_normalize_credits_config' )
		? almaden_bookster_normalize_credits_config( $credits_config_raw, $legacy_credits )
		: array();
	if ( function_exists( 'almaden_bookster_credits_debug_log' ) && function_exists( 'almaden_bookster_credits_debug_summary' ) ) {
		almaden_bookster_credits_debug_log(
			$book_id,
			'settings_loaded',
			almaden_bookster_credits_debug_summary( $pdf_settings['credits_config'] )
		);
	}

	if ( function_exists( 'almaden_bookster_credits_config_to_legacy' ) ) {
		$credits_legacy = almaden_bookster_credits_config_to_legacy( $pdf_settings['credits_config'] );
		$pdf_settings = array_merge( $pdf_settings, $credits_legacy );
	} else {
		$pdf_settings['credits_edition'] = $legacy_credits['credits_edition'];
		$pdf_settings['credits_date'] = $legacy_credits['credits_date'];
		$pdf_settings['credits_isbn'] = $legacy_credits['credits_isbn'];
		$pdf_settings['credits_copyright'] = $legacy_credits['credits_copyright'] ?: 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.';
		$pdf_settings['credits_printer'] = $legacy_credits['credits_printer'];
		$pdf_settings['credits_blank_before'] = (int) $legacy_credits['credits_blank_before'];
		$pdf_settings['credits_blank_after'] = (int) $legacy_credits['credits_blank_after'];
		$pdf_settings['credits_license'] = $legacy_credits['credits_license'];
		$pdf_settings['credits_custom'] = $legacy_credits['credits_custom'] ?: '[]';
	}

	// Ajustes de libro para flujo de capítulos
	$pdf_settings['book_separate_opening_content'] = get_post_meta( $book_id, '_almaden_book_separate_opening_content', true );
	if ( $pdf_settings['book_separate_opening_content'] === '' ) {
		$pdf_settings['book_separate_opening_content'] = 1;
	}
	$pdf_settings['book_chapter_flow_mode'] = get_post_meta( $book_id, '_almaden_book_chapter_flow_mode', true );
	if ( $pdf_settings['book_chapter_flow_mode'] === '' ) {
		$pdf_settings['book_chapter_flow_mode'] = 'continuous';
	}

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
