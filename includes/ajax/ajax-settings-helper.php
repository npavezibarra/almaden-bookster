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
		'book_start_page_footer_type' => 'blank',
		'book_separate_opening_content' => 1,
		'book_chapter_flow_mode'     => 'continuous',
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

function almaden_bookster_get_default_credits_config() {
	return array(
		'editorial' => array(
			'edition_number' => '',
			'publication_date' => '',
			'isbn' => '',
			'printer' => '',
			'blank_before' => 0,
			'blank_after' => 0,
		),
		'people' => array(),
		'collaborators' => array(),
		'logos' => array(),
		'legal' => array(
			'copyright_text' => 'Queda rigurosamente prohibida, sin la autorización escrita de los titulares del "copyright", bajo las sanciones establecidas en las leyes, la reproducción parcial o total de esta obra por cualquier medio o procedimiento.',
			'license' => 'all_rights_reserved',
		),
	);
}

function almaden_bookster_normalize_credits_role_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'author', 'coauthor', 'editor', 'translator', 'designer', 'proofreader', 'photographer', 'other' );
	return in_array( $value, $allowed, true ) ? $value : 'author';
}

function almaden_bookster_normalize_credits_company_type_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'company', 'foundation', 'patron', 'university' );
	return in_array( $value, $allowed, true ) ? $value : 'company';
}

function almaden_bookster_normalize_credits_logo_position_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	if ( in_array( $value, array( 'top', 'bottom' ), true ) ) {
		$value = 'center';
	}
	$allowed = array( 'left', 'center', 'right' );
	return in_array( $value, $allowed, true ) ? $value : 'center';
}

function almaden_bookster_normalize_credits_logo_size_value( $value ) {
	$size = absint( $value );
	if ( $size < 24 ) {
		return 120;
	}
	if ( $size > 400 ) {
		return 400;
	}
	return $size ?: 120;
}

function almaden_bookster_normalize_credits_publication_date_value( $value ) {
	$value = sanitize_text_field( (string) $value );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return substr( $value, 0, 7 );
	}
	if ( preg_match( '/^\d{4}-\d{2}$/', $value ) ) {
		return $value;
	}
	return $value;
}

function almaden_bookster_normalize_credits_license_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'all_rights_reserved', 'creative_commons' );
	return in_array( $value, $allowed, true ) ? $value : 'all_rights_reserved';
}

function almaden_bookster_normalize_credits_config( $raw_config = array(), $legacy_fields = array() ) {
	$defaults = almaden_bookster_get_default_credits_config();

	if ( is_string( $raw_config ) && '' !== trim( $raw_config ) ) {
		$decoded = almaden_bookster_decode_json_array( $raw_config );
		if ( is_array( $decoded ) ) {
			$raw_config = $decoded;
		} else {
			$raw_config = array();
		}
	}

	if ( ! is_array( $raw_config ) ) {
		$raw_config = array();
	}

	if ( ! is_array( $legacy_fields ) ) {
		$legacy_fields = array();
	}

	$editorial_source = array();
	if ( isset( $raw_config['editorial'] ) && is_array( $raw_config['editorial'] ) ) {
		$editorial_source = $raw_config['editorial'];
	} else {
		$editorial_source = $raw_config;
	}

	$people_source = array();
	if ( isset( $raw_config['people'] ) && is_array( $raw_config['people'] ) ) {
		$people_source = $raw_config['people'];
	} elseif ( isset( $raw_config['credits_custom'] ) ) {
		$people_source = $raw_config['credits_custom'];
	} elseif ( isset( $legacy_fields['credits_custom'] ) ) {
		$people_source = $legacy_fields['credits_custom'];
	}

	$collaborators_source = isset( $raw_config['collaborators'] ) && is_array( $raw_config['collaborators'] ) ? $raw_config['collaborators'] : array();
	$logos_source = isset( $raw_config['logos'] ) && is_array( $raw_config['logos'] ) ? $raw_config['logos'] : array();
	$legal_source = isset( $raw_config['legal'] ) && is_array( $raw_config['legal'] ) ? $raw_config['legal'] : array();

	$config = $defaults;
	$config['editorial']['edition_number'] = sanitize_text_field( $editorial_source['edition_number'] ?? $legacy_fields['credits_edition'] ?? '' );
	$config['editorial']['publication_date'] = almaden_bookster_normalize_credits_publication_date_value( $editorial_source['publication_date'] ?? $legacy_fields['credits_date'] ?? '' );
	$config['editorial']['isbn'] = sanitize_text_field( $editorial_source['isbn'] ?? $legacy_fields['credits_isbn'] ?? '' );
	$config['editorial']['printer'] = sanitize_text_field( $editorial_source['printer'] ?? $legacy_fields['credits_printer'] ?? '' );
	$config['editorial']['blank_before'] = isset( $editorial_source['blank_before'] ) ? absint( $editorial_source['blank_before'] ) : absint( $legacy_fields['credits_blank_before'] ?? 0 );
	$config['editorial']['blank_after'] = isset( $editorial_source['blank_after'] ) ? absint( $editorial_source['blank_after'] ) : absint( $legacy_fields['credits_blank_after'] ?? 0 );

	$people = array();
	if ( is_string( $people_source ) && '' !== trim( $people_source ) ) {
		$decoded_people = almaden_bookster_decode_json_array( $people_source );
		if ( is_array( $decoded_people ) ) {
			$people_source = $decoded_people;
		} else {
			$people_source = array();
		}
	}
	if ( is_array( $people_source ) ) {
		foreach ( $people_source as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$name = sanitize_text_field( $row['name'] ?? '' );
			$role = almaden_bookster_normalize_credits_role_value( $row['role'] ?? 'author' );
			$email = sanitize_email( $row['email'] ?? '' );
			$website = esc_url_raw( $row['website'] ?? '' );
			$show_contact = ! empty( $row['show_contact'] ) ? 1 : 0;
			if ( '' === trim( $name ) && '' === trim( $email ) && '' === trim( $website ) ) {
				continue;
			}
			$people[] = array(
				'name' => $name,
				'role' => $role,
				'email' => $email,
				'website' => $website,
				'show_contact' => $show_contact,
			);
		}
	}
	$config['people'] = $people;

	$collaborators = array();
	foreach ( $collaborators_source as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$logo_url = esc_url_raw( $row['logo_url'] ?? $row['image_url'] ?? '' );
		$name = sanitize_text_field( $row['name'] ?? $row['company_name'] ?? '' );
		$type = almaden_bookster_normalize_credits_company_type_value( $row['type'] ?? '' );
		$website = esc_url_raw( $row['website'] ?? '' );
		$text = sanitize_textarea_field( $row['text'] ?? $row['optional_text'] ?? '' );
		if ( '' === trim( $logo_url ) && '' === trim( $name ) && '' === trim( $website ) && '' === trim( $text ) ) {
			continue;
		}
		$collaborators[] = array(
			'logo_url' => $logo_url,
			'name' => $name,
			'type' => $type,
			'website' => $website,
			'text' => $text,
		);
	}
	$config['collaborators'] = $collaborators;

	$logos = array();
	foreach ( $logos_source as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$logo_url = esc_url_raw( $row['logo_url'] ?? $row['image_url'] ?? $row['url'] ?? '' );
		$name = sanitize_text_field( $row['name'] ?? '' );
		$position = almaden_bookster_normalize_credits_logo_position_value( $row['position'] ?? $row['align'] ?? 'center' );
		$size_px = almaden_bookster_normalize_credits_logo_size_value( $row['size_px'] ?? $row['size'] ?? 120 );
		$url = esc_url_raw( $row['url'] ?? $row['website'] ?? '' );
		if ( '' === trim( $logo_url ) && '' === trim( $name ) && '' === trim( $url ) ) {
			continue;
		}
		$logos[] = array(
			'logo_url' => $logo_url,
			'name' => $name,
			'position' => $position,
			'size_px' => $size_px,
			'url' => $url,
		);
	}
	$config['logos'] = $logos;

	$config['legal']['copyright_text'] = sanitize_textarea_field( $legal_source['copyright_text'] ?? $legacy_fields['credits_copyright'] ?? $defaults['legal']['copyright_text'] );
	$config['legal']['license'] = almaden_bookster_normalize_credits_license_value( $legal_source['license'] ?? $legacy_fields['credits_license'] ?? $defaults['legal']['license'] );

	return $config;
}

function almaden_bookster_credits_config_to_legacy( $credits_config ) {
	$config = almaden_bookster_normalize_credits_config( $credits_config );
	$people = array();
	foreach ( $config['people'] as $person ) {
		if ( empty( $person['name'] ) && empty( $person['role'] ) ) {
			continue;
		}
		$people[] = array(
			'role' => $person['role'],
			'name' => $person['name'],
		);
	}

	return array(
		'credits_edition' => $config['editorial']['edition_number'],
		'credits_date' => $config['editorial']['publication_date'],
		'credits_isbn' => $config['editorial']['isbn'],
		'credits_copyright' => $config['legal']['copyright_text'],
		'credits_printer' => $config['editorial']['printer'],
		'credits_blank_before' => (int) $config['editorial']['blank_before'],
		'credits_blank_after' => (int) $config['editorial']['blank_after'],
		'credits_license' => $config['legal']['license'],
		'credits_custom' => wp_json_encode( $people, JSON_UNESCAPED_UNICODE ),
	);
}
