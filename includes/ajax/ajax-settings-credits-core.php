<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_default_credits_config() {
	return array(
		'vertical_align' => 'bottom',
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
		'collaborators_visible' => 1,
		'collaborators_title' => 'Colaboradores',
		'collaborators_styles' => almaden_bookster_get_default_credits_collaborators_styles(),
		'logos' => array(
			array(
				'logo_source' => 'text',
				'logo_url' => '',
				'position' => 'center',
				'size_px' => 120,
				'show_author_name' => 0,
				'author_font_family' => '',
				'author_font_size' => 16,
				'author_font_weight' => '',
				'author_letter_spacing' => '',
				'author_gap_px' => 10,
				'author_text_transform' => 'none',
				'title_font_family' => '',
				'title_font_size' => 24,
				'title_font_weight' => '700',
				'title_letter_spacing' => '',
				'title_line_height' => '',
				'title_text_transform' => 'none',
			),
		),
		'section_order' => almaden_bookster_get_default_credits_section_order(),
		'section_styles' => almaden_bookster_get_default_credits_section_styles(),
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

function almaden_bookster_normalize_credits_logo_source_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	return in_array( $value, array( 'image', 'cover_logo', 'text' ), true ) ? $value : 'image';
}

function almaden_bookster_normalize_credits_logo_author_font_size_value( $value ) {
	$size = absint( $value );
	if ( $size < 8 ) {
		return 16;
	}
	if ( $size > 48 ) {
		return 48;
	}
	return $size ?: 16;
}

function almaden_bookster_normalize_credits_logo_title_font_size_value( $value ) {
	$size = absint( $value );
	if ( $size < 8 ) {
		return 24;
	}
	if ( $size > 72 ) {
		return 72;
	}
	return $size ?: 24;
}

function almaden_bookster_normalize_credits_logo_author_font_weight_value( $value ) {
	$value = sanitize_text_field( (string) $value );
	$allowed = array( '', '300', '400', '500', '600', '700', '800' );
	return in_array( $value, $allowed, true ) ? $value : '';
}

function almaden_bookster_normalize_credits_logo_title_font_weight_value( $value ) {
	$value = sanitize_text_field( (string) $value );
	$allowed = array( '', '300', '400', '500', '600', '700', '800' );
	return in_array( $value, $allowed, true ) ? $value : '';
}

function almaden_bookster_normalize_credits_logo_author_letter_spacing_value( $value ) {
	return almaden_bookster_normalize_credits_optional_decimal_value( $value, -10, 20 );
}

function almaden_bookster_normalize_credits_logo_title_letter_spacing_value( $value ) {
	return almaden_bookster_normalize_credits_optional_decimal_value( $value, -10, 20 );
}

function almaden_bookster_normalize_credits_logo_author_gap_value( $value ) {
	$gap = absint( $value );
	if ( $gap < 0 ) {
		return 10;
	}
	if ( $gap > 100 ) {
		return 100;
	}
	return '' === trim( (string) $value ) ? 10 : $gap;
}

function almaden_bookster_normalize_credits_logo_author_text_transform_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'none', 'uppercase', 'lowercase', 'capitalize' );
	return in_array( $value, $allowed, true ) ? $value : 'none';
}

function almaden_bookster_normalize_credits_logo_title_text_transform_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'none', 'uppercase', 'lowercase', 'capitalize' );
	return in_array( $value, $allowed, true ) ? $value : 'none';
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

function almaden_bookster_normalize_credits_vertical_align_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	return in_array( $value, array( 'top', 'center', 'bottom' ), true ) ? $value : 'top';
}

function almaden_bookster_get_default_credits_section_order() {
	return array( 'logos', 'people', 'editorial', 'legal', 'collaborators' );
}

function almaden_bookster_build_default_credits_seed_config( $author_label = '', $publication_date = '' ) {
	$config = almaden_bookster_get_default_credits_config();
	$author_label = sanitize_text_field( (string) $author_label );
	$publication_date = sanitize_text_field( (string) $publication_date );

	$config['section_order'] = almaden_bookster_get_default_credits_section_order();
	$config['editorial']['publication_date'] = '' !== trim( $publication_date )
		? almaden_bookster_normalize_credits_publication_date_value( $publication_date )
		: current_time( 'Y-m' );

	if ( '' !== trim( $author_label ) ) {
		$config['people'] = array(
			array(
				'name'         => $author_label,
				'role'         => 'author',
				'show_contact' => 0,
			),
		);
	}

	return almaden_bookster_normalize_credits_config( $config );
}

function almaden_bookster_normalize_credits_section_order_value( $value ) {
	$defaults = almaden_bookster_get_default_credits_section_order();
	$value = is_array( $value ) ? $value : array();
	$normalized = array();

	foreach ( $value as $item ) {
		$item = sanitize_key( (string) $item );
		if ( in_array( $item, $defaults, true ) && ! in_array( $item, $normalized, true ) ) {
			$normalized[] = $item;
		}
	}

	foreach ( $defaults as $item ) {
		if ( ! in_array( $item, $normalized, true ) ) {
			$normalized[] = $item;
		}
	}

	return $normalized;
}

function almaden_bookster_get_default_credits_section_style() {
	return array(
		'show_separator' => 0,
		'font_family' => '',
		'font_size' => '',
		'letter_spacing' => '',
		'line_height' => '',
		'text_align' => '',
		'item_gap_px' => '',
	);
}

function almaden_bookster_get_default_credits_section_styles() {
	$styles = array();
	foreach ( almaden_bookster_get_default_credits_section_order() as $section_id ) {
		$styles[ $section_id ] = almaden_bookster_get_default_credits_section_style();
	}
	return $styles;
}

function almaden_bookster_get_default_credits_collaborators_styles() {
	return array(
		'title' => array(
			'font_family' => '',
			'font_size' => 12,
			'font_weight' => '700',
			'line_height' => 1.2,
		),
		'item' => array(
			'font_family' => '',
			'font_size' => 10,
			'font_weight' => '400',
			'line_height' => 1.3,
		),
		'image_max_width' => 96,
	);
}

function almaden_bookster_normalize_credits_collaborators_text_style_value( $value, $defaults ) {
	$value = is_array( $value ) ? $value : array();
	$font_size_source = $value['font_size'] ?? $defaults['font_size'];
	$font_size = '' === trim( (string) $font_size_source ) ? intval( $defaults['font_size'] ) : intval( $font_size_source );
	$font_size = max( 8, min( 36, $font_size ) );
	$font_weight = sanitize_text_field( (string) ( $value['font_weight'] ?? $defaults['font_weight'] ) );
	if ( ! in_array( $font_weight, array( '300', '400', '500', '600', '700', '800' ), true ) ) {
		$font_weight = $defaults['font_weight'];
	}
	$line_height = almaden_bookster_normalize_credits_optional_decimal_value(
		$value['line_height'] ?? $defaults['line_height'],
		0.5,
		3
	);
	if ( '' === $line_height ) {
		$line_height = $defaults['line_height'];
	}

	return array(
		'font_family' => sanitize_text_field( $value['font_family'] ?? $defaults['font_family'] ),
		'font_size' => $font_size,
		'font_weight' => $font_weight,
		'line_height' => $line_height,
	);
}

function almaden_bookster_normalize_credits_collaborators_styles_value( $value, $fallback_section_style = array() ) {
	$defaults = almaden_bookster_get_default_credits_collaborators_styles();
	$value = is_array( $value ) ? $value : array();
	$fallback_section_style = is_array( $fallback_section_style ) ? $fallback_section_style : array();
	$legacy_text = array(
		'font_family' => $fallback_section_style['font_family'] ?? '',
		'font_size' => $fallback_section_style['font_size'] ?? '',
		'line_height' => $fallback_section_style['line_height'] ?? '',
	);
	$title_source = isset( $value['title'] ) && is_array( $value['title'] ) ? $value['title'] : $legacy_text;
	$item_source = isset( $value['item'] ) && is_array( $value['item'] ) ? $value['item'] : $legacy_text;
	$image_max_width = intval( $value['image_max_width'] ?? $defaults['image_max_width'] );
	$image_max_width = max( 60, min( 140, $image_max_width ) );

	return array(
		'title' => almaden_bookster_normalize_credits_collaborators_text_style_value( $title_source, $defaults['title'] ),
		'item' => almaden_bookster_normalize_credits_collaborators_text_style_value( $item_source, $defaults['item'] ),
		'image_max_width' => $image_max_width,
	);
}

function almaden_bookster_normalize_credits_optional_integer_value( $value, $min, $max ) {
	if ( null === $value || '' === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
		return '';
	}
	$number = intval( $value );
	if ( $number < $min ) {
		$number = $min;
	}
	if ( $number > $max ) {
		$number = $max;
	}
	return $number;
}

function almaden_bookster_normalize_credits_optional_decimal_value( $value, $min, $max ) {
	if ( null === $value || '' === $value || ( is_string( $value ) && '' === trim( $value ) ) ) {
		return '';
	}
	$normalized = str_replace( ',', '.', (string) $value );
	if ( ! is_numeric( $normalized ) ) {
		return '';
	}
	$number = floatval( $normalized );
	if ( $number < $min ) {
		$number = $min;
	}
	if ( $number > $max ) {
		$number = $max;
	}
	return round( $number, 2 );
}

function almaden_bookster_normalize_credits_section_style_value( $value ) {
	$defaults = almaden_bookster_get_default_credits_section_style();
	$value = is_array( $value ) ? $value : array();
	$text_align = sanitize_key( (string) ( $value['text_align'] ?? '' ) );
	if ( ! in_array( $text_align, array( 'left', 'center', 'right' ), true ) ) {
		$text_align = '';
	}

	return array(
		'show_separator' => ! empty( $value['show_separator'] ) ? 1 : 0,
		'font_family' => sanitize_text_field( $value['font_family'] ?? $defaults['font_family'] ),
		'font_size' => almaden_bookster_normalize_credits_optional_integer_value( $value['font_size'] ?? $defaults['font_size'], 8, 72 ),
		'letter_spacing' => almaden_bookster_normalize_credits_optional_decimal_value( $value['letter_spacing'] ?? $defaults['letter_spacing'], -10, 20 ),
		'line_height' => almaden_bookster_normalize_credits_optional_decimal_value( $value['line_height'] ?? $defaults['line_height'], 0.5, 3 ),
		'text_align' => $text_align,
		'item_gap_px' => almaden_bookster_normalize_credits_optional_integer_value( $value['item_gap_px'] ?? $defaults['item_gap_px'], 0, 80 ),
	);
}

function almaden_bookster_normalize_credits_section_styles_value( $value ) {
	$defaults = almaden_bookster_get_default_credits_section_styles();
	$value = is_array( $value ) ? $value : array();
	$normalized = array();

	foreach ( almaden_bookster_get_default_credits_section_order() as $section_id ) {
		$normalized[ $section_id ] = almaden_bookster_normalize_credits_section_style_value( $value[ $section_id ] ?? $defaults[ $section_id ] );
	}

	return $normalized;
}

function almaden_bookster_normalize_credits_license_value( $value ) {
	$value = strtolower( sanitize_text_field( (string) $value ) );
	$allowed = array( 'all_rights_reserved', 'creative_commons' );
	return in_array( $value, $allowed, true ) ? $value : 'all_rights_reserved';
}
