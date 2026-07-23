<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_import_mapping_defaults() {
	return array(
		'chapter_separator' => '',
		'title_style'       => '',
		'subtitle_style'    => '',
		'heading_1_style'   => '',
		'heading_2_style'   => '',
		'heading_3_style'   => '',
	);
}

function almaden_bookster_normalize_import_style_selection( $value, array $allowed_keys ) {
	$value = almaden_bookster_normalize_import_style_key( sanitize_text_field( (string) $value ) );
	return in_array( $value, $allowed_keys, true ) ? $value : '';
}

function almaden_bookster_build_import_style_lookup( array $blocks ) {
	$lookup = array();
	foreach ( $blocks as $block ) {
		if ( 'heading' !== $block['type'] ) {
			continue;
		}
		if ( empty( $block['style_key'] ) ) {
			continue;
		}
		$key = (string) $block['style_key'];
		if ( ! isset( $lookup[ $key ] ) ) {
			$lookup[ $key ] = array(
				'key'   => $key,
				'label' => ! empty( $block['style_label'] ) ? (string) $block['style_label'] : almaden_bookster_separator_label_from_key( $key ),
				'count' => 0,
			);
		}
		$lookup[ $key ]['count']++;
	}

	uasort( $lookup, function( $a, $b ) {
		return $b['count'] <=> $a['count'];
	} );

	return array_values( $lookup );
}

function almaden_bookster_normalize_import_mapping( $raw_mapping, array $available_styles ) {
	$defaults = almaden_bookster_import_mapping_defaults();
	$allowed_keys = array();
	foreach ( $available_styles as $style ) {
		if ( ! empty( $style['key'] ) ) {
			$allowed_keys[] = (string) $style['key'];
		}
	}

	$mapping = wp_parse_args( is_array( $raw_mapping ) ? $raw_mapping : array(), $defaults );
	$normalized = array();
	foreach ( $defaults as $field => $default_value ) {
		$normalized[ $field ] = '';
		if ( isset( $mapping[ $field ] ) ) {
			$normalized[ $field ] = almaden_bookster_normalize_import_style_selection( $mapping[ $field ], $allowed_keys );
		}
	}

	if ( '' === $normalized['chapter_separator'] ) {
		foreach ( array( 'title_style', 'heading_1_style', 'subtitle_style', 'heading_2_style', 'heading_3_style' ) as $field ) {
			if ( ! empty( $normalized[ $field ] ) ) {
				$normalized['chapter_separator'] = $normalized[ $field ];
				break;
			}
		}
	}

	if ( '' === $normalized['chapter_separator'] ) {
		foreach ( array( 'title', 'subtitle', 'heading-1', 'heading-2', 'heading-3' ) as $fallback ) {
			if ( in_array( $fallback, $allowed_keys, true ) ) {
				$normalized['chapter_separator'] = $fallback;
				break;
			}
		}
	}

	if ( '' === $normalized['title_style'] && in_array( 'title', $allowed_keys, true ) ) {
		$normalized['title_style'] = 'title';
	}
	if ( '' === $normalized['subtitle_style'] && in_array( 'subtitle', $allowed_keys, true ) ) {
		$normalized['subtitle_style'] = 'subtitle';
	}
	if ( '' === $normalized['heading_1_style'] && in_array( 'heading-1', $allowed_keys, true ) ) {
		$normalized['heading_1_style'] = 'heading-1';
	}
	if ( '' === $normalized['heading_2_style'] && in_array( 'heading-2', $allowed_keys, true ) ) {
		$normalized['heading_2_style'] = 'heading-2';
	}
	if ( '' === $normalized['heading_3_style'] && in_array( 'heading-3', $allowed_keys, true ) ) {
		$normalized['heading_3_style'] = 'heading-3';
	}

	return $normalized;
}

function almaden_bookster_get_import_semantic_level( $style_key, array $mapping ) {
	$style_key = (string) $style_key;
	if ( '' === $style_key ) {
		return 0;
	}
	if ( isset( $mapping['chapter_separator'] ) && $mapping['chapter_separator'] === $style_key ) {
		return 1;
	}
	if ( isset( $mapping['title_style'] ) && $mapping['title_style'] === $style_key ) {
		return 1;
	}
	if ( isset( $mapping['heading_1_style'] ) && $mapping['heading_1_style'] === $style_key ) {
		return 1;
	}
	if ( isset( $mapping['subtitle_style'] ) && $mapping['subtitle_style'] === $style_key ) {
		return 2;
	}
	if ( isset( $mapping['heading_2_style'] ) && $mapping['heading_2_style'] === $style_key ) {
		return 2;
	}
	if ( isset( $mapping['heading_3_style'] ) && $mapping['heading_3_style'] === $style_key ) {
		return 3;
	}

	return 0;
}

function almaden_bookster_import_mapping_field_labels() {
	return array(
		'chapter_separator' => 'Separador de capítulo',
		'title_style'       => 'Title / Título',
		'subtitle_style'    => 'Subtitle / Subtítulo',
		'heading_1_style'   => 'Heading 1',
		'heading_2_style'   => 'Heading 2',
		'heading_3_style'   => 'Heading 3',
	);
}

function almaden_bookster_validate_import_mapping( array $mapping, array $available_styles ) {
	$errors = array();
	$warnings = array();
	$field_labels = almaden_bookster_import_mapping_field_labels();
	$style_labels = array();
	foreach ( $available_styles as $style ) {
		if ( ! empty( $style['key'] ) ) {
			$style_labels[ (string) $style['key'] ] = ! empty( $style['label'] ) ? (string) $style['label'] : (string) $style['key'];
		}
	}

	$usage = array();
	foreach ( array( 'title_style', 'subtitle_style', 'heading_1_style', 'heading_2_style', 'heading_3_style' ) as $field ) {
		$style_key = isset( $mapping[ $field ] ) ? (string) $mapping[ $field ] : '';
		if ( '' === $style_key ) {
			$warnings[] = sprintf( 'No se asignó %s.', $field_labels[ $field ] );
			continue;
		}
		if ( ! isset( $usage[ $style_key ] ) ) {
			$usage[ $style_key ] = array();
		}
		$usage[ $style_key ][] = $field;
	}

	if ( empty( $mapping['chapter_separator'] ) ) {
		$errors[] = 'Debes elegir un separador de capítulo.';
	}

	foreach ( $usage as $style_key => $fields ) {
		if ( count( $fields ) <= 1 ) {
			continue;
		}
		$readable_fields = array();
		foreach ( $fields as $field ) {
			$readable_fields[] = isset( $field_labels[ $field ] ) ? $field_labels[ $field ] : $field;
		}
		$label = isset( $style_labels[ $style_key ] ) ? $style_labels[ $style_key ] : $style_key;
		$warnings[] = sprintf( 'El estilo %s está asignado a más de una función: %s.', $label, implode( ', ', $readable_fields ) );
	}

	$semantic_count = 0;
	foreach ( array( 'title_style', 'subtitle_style', 'heading_1_style', 'heading_2_style', 'heading_3_style' ) as $field ) {
		if ( ! empty( $mapping[ $field ] ) ) {
			$semantic_count++;
		}
	}
	if ( $semantic_count < 2 ) {
		$warnings[] = 'Solo hay un nivel interno asignado; la jerarquía será muy plana.';
	}
	if ( count( $available_styles ) < 2 ) {
		$warnings[] = 'Se detectaron pocos estilos de encabezado; revisa la importación antes de confirmarla.';
	}

	return array(
		'errors'   => array_values( array_unique( $errors ) ),
		'warnings' => array_values( array_unique( $warnings ) ),
	);
}
