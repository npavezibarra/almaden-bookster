<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_uploaded_document_file( $book_id, $field_name ) {
	if ( empty( $_FILES[ $field_name ]['tmp_name'] ) || ! is_uploaded_file( $_FILES[ $field_name ]['tmp_name'] ) ) {
		return new WP_Error( 'no_file', 'No se recibió ningún archivo.' );
	}

	$tmp_path = $_FILES[ $field_name ]['tmp_name'];
	$filename = isset( $_FILES[ $field_name ]['name'] ) ? sanitize_file_name( wp_unslash( $_FILES[ $field_name ]['name'] ) ) : 'document';
	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$format = almaden_bookster_detect_import_format( $filename, $ext );

	if ( empty( $format ) ) {
		return new WP_Error( 'unsupported_format', 'Formato no soportado. Usa .docx, .rtf, .txt o un PDF con texto seleccionable.' );
	}

	switch ( $format ) {
		case 'docx':
			$parsed = almaden_bookster_parse_docx_import_document( $tmp_path, $filename );
			break;
		case 'rtf':
			$parsed = almaden_bookster_parse_rtf_import_document( $tmp_path, $filename );
			break;
		case 'pdf':
			$parsed = almaden_bookster_parse_pdf_import_document( $tmp_path, $filename );
			break;
		default:
			$parsed = almaden_bookster_parse_txt_import_document( $tmp_path, $filename );
			break;
	}

	if ( is_wp_error( $parsed ) ) {
		return $parsed;
	}

	$parsed['original_name'] = $filename;
	$parsed['format'] = $format;
	$parsed['format_label'] = almaden_bookster_import_format_label( $format );
	$parsed['hint'] = almaden_bookster_import_format_hint( $format );
	$parsed['confidence_label'] = almaden_bookster_import_confidence_label( $format );
	$parsed['separator_options'] = almaden_bookster_build_separator_candidates( $parsed['blocks'] );
	$parsed['style_counts'] = almaden_bookster_build_import_style_lookup( $parsed['blocks'] );
	$parsed['status_label'] = 'Analizado';
	$parsed['recommended_separator'] = ! empty( $parsed['separator_options'] ) ? $parsed['separator_options'][0]['key'] : 'heading-1';
	$parsed['mapping_defaults'] = almaden_bookster_normalize_import_mapping( array( 'chapter_separator' => $parsed['recommended_separator'] ), $parsed['separator_options'] );
	$parsed['mapping_validation'] = almaden_bookster_validate_import_mapping( $parsed['mapping_defaults'], $parsed['separator_options'] );
	$parsed['chapter_preview'] = almaden_bookster_build_chapter_preview( $parsed['blocks'], $parsed['mapping_defaults'] );
	$parsed['chapter_count'] = count( $parsed['chapter_preview'] );

	return $parsed;
}

function almaden_bookster_detect_import_format( $filename, $ext ) {
	$ext = strtolower( (string) $ext );
	$map = array(
		'docx' => 'docx',
		'rtf'  => 'rtf',
		'txt'  => 'txt',
		'pdf'  => 'pdf',
	);
	if ( isset( $map[ $ext ] ) ) {
		return $map[ $ext ];
	}
	if ( preg_match( '/\.docx$/i', $filename ) ) {
		return 'docx';
	}
	if ( preg_match( '/\.rtf$/i', $filename ) ) {
		return 'rtf';
	}
	if ( preg_match( '/\.pdf$/i', $filename ) ) {
		return 'pdf';
	}
	if ( preg_match( '/\.txt$/i', $filename ) ) {
		return 'txt';
	}
	return '';
}

function almaden_bookster_import_format_label( $format ) {
	$labels = array(
		'docx' => 'Word (.docx)',
		'rtf'  => 'Rich Text (.rtf)',
		'txt'  => 'Texto plano (.txt)',
		'pdf'  => 'PDF',
	);
	return isset( $labels[ $format ] ) ? $labels[ $format ] : strtoupper( $format );
}

function almaden_bookster_import_format_hint( $format ) {
	$hints = array(
		'docx' => 'DOCX conserva estilos reales y es el formato más confiable para importar jerarquías.',
		'rtf'  => 'RTF conserva parte del formato, pero puede perder precisión en documentos complejos.',
		'txt'  => 'TXT no conserva estilos; se aplican heurísticas sobre líneas y separación por párrafos.',
		'pdf'  => 'PDF requiere texto seleccionable; si no se puede extraer texto, la importación no continuará.',
	);
	return isset( $hints[ $format ] ) ? $hints[ $format ] : '';
}

function almaden_bookster_import_confidence_label( $format ) {
	switch ( $format ) {
		case 'docx':
			return 'Alta';
		case 'rtf':
			return 'Media';
		case 'pdf':
			return 'Media / variable';
		default:
			return 'Baja';
	}
}

function almaden_bookster_separator_label_from_key( $key ) {
	$labels = array(
		'title'     => 'Title',
		'subtitle'  => 'Subtitle',
		'heading-1' => 'Heading 1',
		'heading-2' => 'Heading 2',
		'heading-3' => 'Heading 3',
		'heading-4' => 'Heading 4',
		'heading-5' => 'Heading 5',
		'heading-6' => 'Heading 6',
	);
	return isset( $labels[ $key ] ) ? $labels[ $key ] : ucfirst( str_replace( '-', ' ', $key ) );
}

function almaden_bookster_build_style_counts( array $separator_options ) {
	$items = array();
	foreach ( $separator_options as $option ) {
		$items[] = array(
			'key'   => $option['key'],
			'label' => $option['label'],
			'count' => intval( $option['count'] ),
		);
	}
	return $items;
}
