<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		isset( $legacy_fields['credits_logo_author_text_transform'] ) ||
		isset( $legacy_fields['credits_logo_title_font_family'] ) ||
		isset( $legacy_fields['credits_logo_title_font_size'] ) ||
		isset( $legacy_fields['credits_logo_title_font_weight'] ) ||
		isset( $legacy_fields['credits_logo_title_letter_spacing'] ) ||
		isset( $legacy_fields['credits_logo_title_line_height'] ) ||
		isset( $legacy_fields['credits_logo_title_text_transform'] )
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
				'title_font_family' => $legacy_fields['credits_logo_title_font_family'] ?? '',
				'title_font_size' => $legacy_fields['credits_logo_title_font_size'] ?? '',
				'title_font_weight' => $legacy_fields['credits_logo_title_font_weight'] ?? '',
				'title_letter_spacing' => $legacy_fields['credits_logo_title_letter_spacing'] ?? '',
				'title_line_height' => $legacy_fields['credits_logo_title_line_height'] ?? '',
				'title_text_transform' => $legacy_fields['credits_logo_title_text_transform'] ?? 'none',
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
	$vertical_align_source = $raw_config['vertical_align'] ?? $raw_config['credits_vertical_align'] ?? $defaults['vertical_align'];
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
	$config['vertical_align'] = almaden_bookster_normalize_credits_vertical_align_value( $vertical_align_source );

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
		$title_font_family = sanitize_text_field( $row['title_font_family'] ?? '' );
		$title_font_size = almaden_bookster_normalize_credits_logo_title_font_size_value( $row['title_font_size'] ?? 16 );
		$title_font_weight = almaden_bookster_normalize_credits_logo_title_font_weight_value( $row['title_font_weight'] ?? '' );
		$title_letter_spacing = almaden_bookster_normalize_credits_logo_title_letter_spacing_value( $row['title_letter_spacing'] ?? '' );
		$title_line_height = almaden_bookster_normalize_credits_optional_decimal_value( $row['title_line_height'] ?? '', 0.5, 3 );
		$title_text_transform = almaden_bookster_normalize_credits_logo_title_text_transform_value( $row['title_text_transform'] ?? 'none' );
		$has_author_settings = $show_author_name || '' !== trim( $author_font_family ) || ( isset( $row['author_font_size'] ) && '' !== trim( (string) $row['author_font_size'] ) ) || '' !== $author_font_weight || '' !== $author_letter_spacing || ( isset( $row['author_gap_px'] ) && '' !== trim( (string) $row['author_gap_px'] ) ) || 'none' !== $author_text_transform;
		$has_title_settings = '' !== trim( $title_font_family ) || ( isset( $row['title_font_size'] ) && '' !== trim( (string) $row['title_font_size'] ) ) || '' !== $title_font_weight || '' !== $title_letter_spacing || ( isset( $row['title_line_height'] ) && '' !== trim( (string) $row['title_line_height'] ) ) || 'none' !== $title_text_transform;
		if ( 'cover_logo' !== $logo_source && 'text' !== $logo_source && '' === trim( $logo_url ) && ! $has_author_settings && ! $has_title_settings ) {
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
			'title_font_family' => $title_font_family,
			'title_font_size' => $title_font_size,
			'title_font_weight' => $title_font_weight,
			'title_letter_spacing' => $title_letter_spacing,
			'title_line_height' => $title_line_height,
			'title_text_transform' => $title_text_transform,
		);
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
		'credits_vertical_align' => $config['vertical_align'],
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
		'credits_logo_title_font_family' => $first_logo['title_font_family'] ?? '',
		'credits_logo_title_font_size' => intval( $first_logo['title_font_size'] ?? 16 ),
		'credits_logo_title_font_weight' => $first_logo['title_font_weight'] ?? '',
		'credits_logo_title_letter_spacing' => $first_logo['title_letter_spacing'] ?? '',
		'credits_logo_title_line_height' => $first_logo['title_line_height'] ?? '',
		'credits_logo_title_text_transform' => $first_logo['title_text_transform'] ?? 'none',
	);
}
