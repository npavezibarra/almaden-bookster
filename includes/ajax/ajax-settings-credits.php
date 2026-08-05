<?php
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
		'collaborators_visible' => 1,
		'collaborators_title' => 'Colaboradores',
		'collaborators_styles' => almaden_bookster_get_default_credits_collaborators_styles(),
		'logos' => array(),
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
	return 'cover_logo' === $value ? 'cover_logo' : 'image';
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

function almaden_bookster_normalize_credits_logo_author_font_weight_value( $value ) {
	$value = sanitize_text_field( (string) $value );
	$allowed = array( '', '300', '400', '500', '600', '700', '800' );
	return in_array( $value, $allowed, true ) ? $value : '';
}

function almaden_bookster_normalize_credits_logo_author_letter_spacing_value( $value ) {
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

function almaden_bookster_get_default_credits_section_order() {
	return array( 'editorial', 'people', 'collaborators', 'logos', 'legal' );
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
	$collaborators_visible_source = $raw_config['collaborators_visible'] ?? $raw_config['credits_collaborators_visible'] ?? $defaults['collaborators_visible'];
	$collaborators_title_source = sanitize_text_field( $raw_config['collaborators_title'] ?? $raw_config['credits_collaborators_title'] ?? $defaults['collaborators_title'] );
	$logos_source = array();
	if ( isset( $raw_config['logos'] ) && is_array( $raw_config['logos'] ) ) {
		$logos_source = $raw_config['logos'];
	} elseif ( isset( $raw_config['logo'] ) && is_array( $raw_config['logo'] ) ) {
		$logos_source = array( $raw_config['logo'] );
	} elseif (
		isset( $legacy_fields['credits_logo_source'] ) ||
		isset( $legacy_fields['credits_logo_url'] ) ||
		isset( $legacy_fields['credits_logo_show_author_name'] ) ||
		isset( $legacy_fields['credits_logo_author_font_family'] ) ||
		isset( $legacy_fields['credits_logo_author_font_size'] ) ||
		isset( $legacy_fields['credits_logo_author_font_weight'] ) ||
		isset( $legacy_fields['credits_logo_author_letter_spacing'] ) ||
		isset( $legacy_fields['credits_logo_author_gap_px'] ) ||
		isset( $legacy_fields['credits_logo_author_text_transform'] )
	) {
		$logos_source = array(
			array(
				'logo_source' => $legacy_fields['credits_logo_source'] ?? 'image',
				'logo_url' => $legacy_fields['credits_logo_url'] ?? '',
				'position' => $legacy_fields['credits_logo_position'] ?? 'center',
				'size_px' => $legacy_fields['credits_logo_size_px'] ?? 120,
				'show_author_name' => $legacy_fields['credits_logo_show_author_name'] ?? 0,
				'author_font_family' => $legacy_fields['credits_logo_author_font_family'] ?? '',
				'author_font_size' => $legacy_fields['credits_logo_author_font_size'] ?? 16,
				'author_font_weight' => $legacy_fields['credits_logo_author_font_weight'] ?? '',
				'author_letter_spacing' => $legacy_fields['credits_logo_author_letter_spacing'] ?? '',
				'author_gap_px' => $legacy_fields['credits_logo_author_gap_px'] ?? 10,
				'author_text_transform' => $legacy_fields['credits_logo_author_text_transform'] ?? 'none',
			),
		);
	}
	$section_order_source = array();
	if ( isset( $raw_config['section_order'] ) ) {
		if ( is_array( $raw_config['section_order'] ) ) {
			$section_order_source = $raw_config['section_order'];
		} elseif ( is_string( $raw_config['section_order'] ) && '' !== trim( $raw_config['section_order'] ) ) {
			$decoded_section_order = json_decode( wp_unslash( $raw_config['section_order'] ), true );
			if ( is_array( $decoded_section_order ) ) {
				$section_order_source = $decoded_section_order;
			}
		}
	}
	if ( empty( $section_order_source ) && isset( $raw_config['credits_section_order'] ) ) {
		$credits_section_order_raw = $raw_config['credits_section_order'];
		if ( is_string( $credits_section_order_raw ) && '' !== trim( $credits_section_order_raw ) ) {
			$decoded_section_order = json_decode( wp_unslash( $credits_section_order_raw ), true );
			if ( is_array( $decoded_section_order ) ) {
				$section_order_source = $decoded_section_order;
			}
		} elseif ( is_array( $credits_section_order_raw ) ) {
			$section_order_source = $credits_section_order_raw;
		}
	}
	$section_styles_source = array();
	if ( isset( $raw_config['section_styles'] ) ) {
		if ( is_array( $raw_config['section_styles'] ) ) {
			$section_styles_source = $raw_config['section_styles'];
		} elseif ( is_string( $raw_config['section_styles'] ) && '' !== trim( $raw_config['section_styles'] ) ) {
			$decoded_section_styles = json_decode( wp_unslash( $raw_config['section_styles'] ), true );
			if ( is_array( $decoded_section_styles ) ) {
				$section_styles_source = $decoded_section_styles;
			}
		}
	}
	if ( empty( $section_styles_source ) && isset( $raw_config['credits_section_styles'] ) ) {
		$credits_section_styles_raw = $raw_config['credits_section_styles'];
		if ( is_string( $credits_section_styles_raw ) && '' !== trim( $credits_section_styles_raw ) ) {
			$decoded_section_styles = json_decode( wp_unslash( $credits_section_styles_raw ), true );
			if ( is_array( $decoded_section_styles ) ) {
				$section_styles_source = $decoded_section_styles;
			}
		} elseif ( is_array( $credits_section_styles_raw ) ) {
			$section_styles_source = $credits_section_styles_raw;
		}
	}
	$collaborators_styles_source = array();
	if ( isset( $raw_config['collaborators_styles'] ) ) {
		if ( is_array( $raw_config['collaborators_styles'] ) ) {
			$collaborators_styles_source = $raw_config['collaborators_styles'];
		} elseif ( is_string( $raw_config['collaborators_styles'] ) && '' !== trim( $raw_config['collaborators_styles'] ) ) {
			$decoded_collaborators_styles = json_decode( wp_unslash( $raw_config['collaborators_styles'] ), true );
			if ( is_array( $decoded_collaborators_styles ) ) {
				$collaborators_styles_source = $decoded_collaborators_styles;
			}
		}
	}
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
			$custom_role_title = 'other' === $role ? sanitize_text_field( $row['custom_role_title'] ?? '' ) : '';
			$email = sanitize_email( $row['email'] ?? '' );
			$website = esc_url_raw( $row['website'] ?? '' );
			$show_contact = ! empty( $row['show_contact'] ) ? 1 : 0;
			if ( '' === trim( $name ) && '' === trim( $email ) && '' === trim( $website ) && '' === trim( $custom_role_title ) ) {
				continue;
			}
			$people[] = array(
				'name' => $name,
				'role' => $role,
				'custom_role_title' => $custom_role_title,
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
	$config['collaborators_visible'] = in_array( $collaborators_visible_source, array( 1, '1', true ), true ) ? 1 : 0;
	$config['collaborators_title'] = '' !== trim( $collaborators_title_source ) ? $collaborators_title_source : $defaults['collaborators_title'];

	$logos = array();
	foreach ( $logos_source as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$logo_url = esc_url_raw( $row['logo_url'] ?? $row['image_url'] ?? $row['url'] ?? '' );
		$logo_source = almaden_bookster_normalize_credits_logo_source_value( $row['logo_source'] ?? $row['source_type'] ?? $row['mode'] ?? 'image' );
		$position = almaden_bookster_normalize_credits_logo_position_value( $row['position'] ?? $row['align'] ?? 'center' );
		$size_px = almaden_bookster_normalize_credits_logo_size_value( $row['size_px'] ?? $row['size'] ?? 120 );
		$show_author_name = ! empty( $row['show_author_name'] ) ? 1 : 0;
		$author_font_family = sanitize_text_field( $row['author_font_family'] ?? '' );
		$author_font_size = almaden_bookster_normalize_credits_logo_author_font_size_value( $row['author_font_size'] ?? 16 );
		$author_font_weight = almaden_bookster_normalize_credits_logo_author_font_weight_value( $row['author_font_weight'] ?? '' );
		$author_letter_spacing = almaden_bookster_normalize_credits_logo_author_letter_spacing_value( $row['author_letter_spacing'] ?? '' );
		$author_gap_px = almaden_bookster_normalize_credits_logo_author_gap_value( $row['author_gap_px'] ?? 10 );
		$author_text_transform = almaden_bookster_normalize_credits_logo_author_text_transform_value( $row['author_text_transform'] ?? 'none' );
		$has_author_settings = $show_author_name || '' !== trim( $author_font_family ) || ( isset( $row['author_font_size'] ) && '' !== trim( (string) $row['author_font_size'] ) ) || '' !== $author_font_weight || '' !== $author_letter_spacing || ( isset( $row['author_gap_px'] ) && '' !== trim( (string) $row['author_gap_px'] ) ) || 'none' !== $author_text_transform;
		if ( 'cover_logo' !== $logo_source && '' === trim( $logo_url ) && ! $has_author_settings ) {
			continue;
		}
		$logos[] = array(
			'logo_source' => $logo_source,
			'logo_url' => $logo_url,
			'position' => $position,
			'size_px' => $size_px,
			'show_author_name' => $show_author_name,
			'author_font_family' => $author_font_family,
			'author_font_size' => $author_font_size,
			'author_font_weight' => $author_font_weight,
			'author_letter_spacing' => $author_letter_spacing,
			'author_gap_px' => $author_gap_px,
			'author_text_transform' => $author_text_transform,
		);
		break;
	}
	$config['logos'] = $logos;
	$config['section_order'] = almaden_bookster_normalize_credits_section_order_value( $section_order_source );
	$config['section_styles'] = almaden_bookster_normalize_credits_section_styles_value( $section_styles_source );
	$config['collaborators_styles'] = almaden_bookster_normalize_credits_collaborators_styles_value(
		$collaborators_styles_source,
		$config['section_styles']['collaborators'] ?? array()
	);

	$config['legal']['copyright_text'] = sanitize_textarea_field( $legal_source['copyright_text'] ?? $legacy_fields['credits_copyright'] ?? $defaults['legal']['copyright_text'] );
	$config['legal']['license'] = almaden_bookster_normalize_credits_license_value( $legal_source['license'] ?? $legacy_fields['credits_license'] ?? $defaults['legal']['license'] );

	return $config;
}

function almaden_bookster_credits_config_to_legacy( $credits_config ) {
	$config = almaden_bookster_normalize_credits_config( $credits_config );
	$first_logo = ! empty( $config['logos'] ) && is_array( $config['logos'] ) ? $config['logos'][0] : array();
	$people = array();
	foreach ( $config['people'] as $person ) {
		if ( empty( $person['name'] ) && empty( $person['role'] ) ) {
			continue;
		}
		$people[] = array(
			'role' => $person['role'],
			'name' => $person['name'],
			'custom_role_title' => isset( $person['custom_role_title'] ) ? $person['custom_role_title'] : '',
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
		'credits_collaborators_visible' => ! empty( $config['collaborators_visible'] ) ? 1 : 0,
		'credits_logo_source' => $first_logo['logo_source'] ?? 'image',
		'credits_logo_url' => $first_logo['logo_url'] ?? '',
		'credits_logo_position' => $first_logo['position'] ?? 'center',
		'credits_logo_size_px' => intval( $first_logo['size_px'] ?? 120 ),
		'credits_logo_show_author_name' => ! empty( $first_logo['show_author_name'] ) ? 1 : 0,
		'credits_logo_author_font_family' => $first_logo['author_font_family'] ?? '',
		'credits_logo_author_font_size' => intval( $first_logo['author_font_size'] ?? 16 ),
		'credits_logo_author_font_weight' => $first_logo['author_font_weight'] ?? '',
		'credits_logo_author_letter_spacing' => $first_logo['author_letter_spacing'] ?? '',
		'credits_logo_author_gap_px' => intval( $first_logo['author_gap_px'] ?? 10 ),
		'credits_logo_author_text_transform' => $first_logo['author_text_transform'] ?? 'none',
	);
}

/**
 * Build an in-memory chapter body for legacy/generic PDF render paths.
 *
 * The canonical source remains _almaden_credits_config. This fallback prevents
 * an older renderer from printing the historical placeholder stored in
 * book_chapter.post_content.
 */
function almaden_bookster_build_credits_chapter_content( $credits_config, $book_author_label = '' ) {
	$config = almaden_bookster_normalize_credits_config( $credits_config );
	$section_html = array(
		'editorial'     => '',
		'people'        => '',
		'collaborators' => '',
		'logos'         => '',
		'legal'         => '',
	);

	$format_date = static function ( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^(\d{4})-(\d{2})(?:-\d{2})?$/', $value, $matches ) ) {
			return $value;
		}

		$months = array(
			1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
			'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
		);
		$month = (int) $matches[2];
		return isset( $months[ $month ] ) ? $months[ $month ] . ' ' . $matches[1] : $value;
	};

	$role_labels = array(
		'author'       => 'Autor',
		'coauthor'     => 'Coautor',
		'editor'       => 'Editor',
		'translator'   => 'Traductor',
		'designer'     => 'Diseñador',
		'proofreader'  => 'Corrector',
		'photographer' => 'Fotógrafo',
		'other'        => 'Otro',
	);
	$license_labels = array(
		'all_rights_reserved' => 'Todos los derechos reservados',
		'creative_commons'    => 'Creative Commons',
	);
	$people_style = isset( $config['section_styles']['people'] ) && is_array( $config['section_styles']['people'] )
		? $config['section_styles']['people']
		: array();
	$people_text_align = in_array( $people_style['text_align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $people_style['text_align'] : '';
	$people_align_items = 'right' === $people_text_align ? 'flex-end' : ( 'center' === $people_text_align ? 'center' : ( 'left' === $people_text_align ? 'flex-start' : '' ) );
	$people_alignment_style = $people_text_align ? 'text-align:' . $people_text_align . ';text-align-last:' . $people_text_align . ';' : '';
	$people_align_items_style = $people_align_items ? 'align-items:' . $people_align_items . ';' : '';
	$people_align_self_style = $people_align_items ? 'align-self:' . $people_align_items . ';' : '';

	$editorial_rows = array();
	if ( '' !== trim( (string) $config['editorial']['edition_number'] ) ) {
		$edition = absint( $config['editorial']['edition_number'] );
		$editorial_rows[] = $edition > 0
			? sprintf( '<p><strong>%s edición</strong></p>', esc_html( $edition . '.ª' ) )
			: '';
	}
	if ( '' !== trim( (string) $config['editorial']['publication_date'] ) ) {
		$editorial_rows[] = '<p><strong>Fecha de publicación:</strong> ' . esc_html( $format_date( $config['editorial']['publication_date'] ) ) . '</p>';
	}
	if ( '' !== trim( (string) $config['editorial']['isbn'] ) ) {
		$editorial_rows[] = '<p><strong>ISBN:</strong> ' . esc_html( $config['editorial']['isbn'] ) . '</p>';
	}
	if ( '' !== trim( (string) $config['editorial']['printer'] ) ) {
		$editorial_rows[] = '<p><strong>Imprenta:</strong> ' . esc_html( $config['editorial']['printer'] ) . '</p>';
	}
	$editorial_rows = array_filter( $editorial_rows );
	if ( $editorial_rows ) {
		$section_html['editorial'] = '<div class="credits-editorial-section">' . implode( '', $editorial_rows ) . '</div>';
	}

	$people_rows = array();
	foreach ( $config['people'] as $person ) {
		$name = trim( (string) ( $person['name'] ?? '' ) );
		$role = trim( (string) ( $person['role'] ?? '' ) );
		$custom_role_title = trim( (string) ( $person['custom_role_title'] ?? '' ) );
		$email = trim( (string) ( $person['email'] ?? '' ) );
		$website = trim( (string) ( $person['website'] ?? '' ) );
		if ( '' === $name && '' === $role && '' === $custom_role_title && '' === $email && '' === $website ) {
			continue;
		}

		$row_parts = array();
		if ( '' !== $name ) {
			$row_parts[] = '<span class="credits-person-name" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-weight:700;">' . esc_html( $name ) . '</span>';
		}
		if ( '' !== $role ) {
			$role_label = 'other' === $role && '' !== $custom_role_title ? $custom_role_title : ( $role_labels[ $role ] ?? $role );
			$row_parts[] = '<span class="credits-person-role" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;"><em>' . esc_html( $role_label ) . '</em></span>';
		} elseif ( '' !== $custom_role_title ) {
			$row_parts[] = '<span class="credits-person-role" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;"><em>' . esc_html( $custom_role_title ) . '</em></span>';
		}
		if ( ! empty( $person['show_contact'] ) ) {
			if ( '' !== $email ) {
				$row_parts[] = '<span class="credits-person-email" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . 'font-style:italic;">' . esc_html( $email ) . '</span>';
			}
			if ( '' !== $website ) {
				$row_parts[] = '<span class="credits-person-website" style="display:block;width:fit-content;max-width:100%;' . esc_attr( $people_align_self_style ) . 'white-space:normal;overflow-wrap:anywhere;' . esc_attr( $people_alignment_style ) . '">' . esc_html( preg_replace( '#^https?://#i', '', $website ) ) . '</span>';
			}
		}
		if ( $row_parts ) {
			$people_rows[] = '<div class="credits-person-entry" style="display:flex;flex-direction:column;' . esc_attr( $people_align_items_style ) . 'width:100%;margin:0 0 0.9em 0;' . esc_attr( $people_alignment_style ) . '">' . implode( '<div style="height:2px;"></div>', $row_parts ) . '</div>';
		}
	}
	if ( $people_rows ) {
		$section_html['people'] = '<div class="credits-people-section" style="display:flex;flex-direction:column;' . esc_attr( $people_align_items_style ) . 'width:100%;' . esc_attr( $people_alignment_style ) . '">' . implode( '', $people_rows ) . '</div>';
	}

	if ( ! empty( $config['collaborators_visible'] ) ) {
		$collaborator_rows = array();
		foreach ( $config['collaborators'] as $collaborator ) {
			$name = trim( (string) ( $collaborator['name'] ?? '' ) );
			$website = trim( (string) ( $collaborator['website'] ?? '' ) );
			$text = trim( (string) ( $collaborator['text'] ?? '' ) );
			$logo_url = trim( (string) ( $collaborator['logo_url'] ?? '' ) );
			if ( '' === $name && '' === $website && '' === $text && '' === $logo_url ) {
				continue;
			}

			$row = '<div class="credits-collaborator-cell">';
			if ( '' !== $logo_url ) {
				$row .= '<img src="' . esc_url( $logo_url ) . '" alt="">';
			}
			if ( '' !== $name ) {
				$row .= '<p class="credits-collaborator-name">' . esc_html( $name ) . '</p>';
			}
			if ( '' !== $website ) {
				$row .= '<p class="credits-collaborator-website">' . esc_html( preg_replace( '#^https?://#i', '', $website ) ) . '</p>';
			}
			if ( '' !== $text ) {
				$row .= '<p class="credits-collaborator-text">' . esc_html( $text ) . '</p>';
			}
			$collaborator_rows[] = $row . '</div>';
		}
		if ( $collaborator_rows ) {
			$title = trim( (string) $config['collaborators_title'] );
			$title_html = '' !== $title ? '<div class="credits-section-title">' . esc_html( $title ) . '</div>' : '';
			$section_html['collaborators'] = '<div class="credits-collaborators-section">' . $title_html . implode( '', $collaborator_rows ) . '</div>';
		}
	}

	$logo_rows = array();
	foreach ( $config['logos'] as $logo ) {
		$logo_url = trim( (string) ( $logo['logo_url'] ?? '' ) );
		$show_author = ! empty( $logo['show_author_name'] ) && '' !== trim( (string) $book_author_label );
		if ( '' === $logo_url && ! $show_author ) {
			continue;
		}

		$position = in_array( $logo['position'] ?? '', array( 'left', 'center', 'right' ), true ) ? $logo['position'] : 'center';
		$size = max( 24, min( 400, absint( $logo['size_px'] ?? 120 ) ) );
		$row = '<div class="credits-logo-row" style="text-align:' . esc_attr( $position ) . '">';
		if ( '' !== $logo_url ) {
			$row .= '<img src="' . esc_url( $logo_url ) . '" alt="" style="width:' . $size . 'px;max-width:100%;height:auto">';
		}
		if ( $show_author ) {
			$row .= '<div class="credits-logo-author">' . esc_html( $book_author_label ) . '</div>';
		}
		$logo_rows[] = $row . '</div>';
	}
	if ( $logo_rows ) {
		$section_html['logos'] = '<div class="credits-logos-section">' . implode( '', $logo_rows ) . '</div>';
	}

	$legal_text = trim( (string) ( $config['legal']['copyright_text'] ?? '' ) );
	$license = trim( (string) ( $config['legal']['license'] ?? '' ) );
	if ( '' !== $legal_text || '' !== $license ) {
		$legal_html = '<div class="credits-legal-section">';
		if ( '' !== $legal_text ) {
			$legal_html .= '<div class="credits-copyright"><p>' . nl2br( esc_html( $legal_text ) ) . '</p></div>';
		}
		if ( '' !== $license ) {
			$legal_html .= '<div class="credits-license"><p>' . esc_html( $license_labels[ $license ] ?? $license ) . '</p></div>';
		}
		$section_html['legal'] = $legal_html . '</div>';
	}

	$html = '<div class="content-box credits-page-content"><div class="credits-sections-flow">';
	$rendered = 0;
	foreach ( $config['section_order'] as $section_id ) {
		if ( empty( $section_html[ $section_id ] ) ) {
			continue;
		}

		$style = $config['section_styles'][ $section_id ] ?? array();
		$inline = array();
		if ( ! empty( $style['font_family'] ) ) {
			$inline[] = 'font-family:' . esc_attr( $style['font_family'] );
		}
		if ( '' !== (string) ( $style['font_size'] ?? '' ) ) {
			$inline[] = 'font-size:' . (float) $style['font_size'] . 'px';
		}
		if ( '' !== (string) ( $style['letter_spacing'] ?? '' ) ) {
			$inline[] = 'letter-spacing:' . (float) $style['letter_spacing'] . 'px';
		}
		if ( '' !== (string) ( $style['line_height'] ?? '' ) ) {
			$inline[] = 'line-height:' . (float) $style['line_height'];
		}
		if ( in_array( $style['text_align'] ?? '', array( 'left', 'center', 'right' ), true ) ) {
			$inline[] = 'text-align:' . $style['text_align'];
		}
		if ( $rendered > 0 && ! empty( $style['show_separator'] ) ) {
			$html .= '<div class="credits-section-separator"></div>';
		}
		$html .= '<div class="credits-section-block credits-section-' . esc_attr( $section_id ) . '" style="' . esc_attr( implode( ';', $inline ) ) . '">' . $section_html[ $section_id ] . '</div>';
		++$rendered;
	}
	$html .= '</div></div>';

	if ( 0 === $rendered ) {
		return '';
	}

	// The generic chapter renderer accepts Markdown-like plain text only. Keep
	// this fallback readable even when the structured credits renderer is not
	// the code path selected by the preview.
	$plain_source = preg_replace( '#</(?:strong|span|em)>#i', ' ', $html );
	$plain_source = preg_replace( '#<(?:br\s*/?|/p|/div)>#i', "\n", $plain_source );
	$plain_text = html_entity_decode(
		wp_strip_all_tags( $plain_source ),
		ENT_QUOTES | ENT_HTML5,
		'UTF-8'
	);
	$plain_text = preg_replace( '/[ \t]{2,}/', ' ', $plain_text );
	$plain_text = preg_replace( "/[ \t]+\n/", "\n", $plain_text );
	$plain_text = preg_replace( "/\n{3,}/", "\n\n", $plain_text );

	return trim( $plain_text );
}
