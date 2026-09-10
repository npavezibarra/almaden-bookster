<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_uploaded_document_file( $book_id, $field_name ) {
	$file_validation = almaden_bookster_validate_uploaded_document_file( $field_name );
	if ( is_wp_error( $file_validation ) ) {
		return $file_validation;
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
	$parsed['table_count'] = count( array_filter( $parsed['blocks'], function( $block ) {
		return isset( $block['style_key'] ) && 'table' === (string) $block['style_key'];
	} ) );
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

function almaden_bookster_validate_uploaded_document_file( $field_name ) {
	$diagnostics = almaden_bookster_document_upload_diagnostics( $field_name );

	if ( ! empty( $diagnostics['post_exceeds_limit'] ) ) {
		return new WP_Error(
			'post_size_exceeded',
			sprintf(
				'El servidor recibió una solicitud de %1$s, pero post_max_size está configurado en %2$s. Sube ese límite en PHP y vuelve a intentar.',
				almaden_bookster_format_bytes_for_import( $diagnostics['content_length_bytes'] ),
				$diagnostics['post_max_size']
			),
			$diagnostics
		);
	}

	if ( isset( $_FILES[ $field_name ]['error'] ) && UPLOAD_ERR_OK !== intval( $_FILES[ $field_name ]['error'] ) ) {
		$error_code = intval( $_FILES[ $field_name ]['error'] );
		return new WP_Error(
			'upload_error',
			almaden_bookster_upload_error_message( $error_code, $diagnostics ),
			$diagnostics
		);
	}

	if ( empty( $_FILES[ $field_name ]['tmp_name'] ) || ! is_uploaded_file( $_FILES[ $field_name ]['tmp_name'] ) ) {
		return new WP_Error(
			'no_file',
			'No se recibió ningún archivo en el servidor. Revisa los límites de subida PHP, el formulario FormData y la pestaña Network para confirmar que document_file viaja en la solicitud.',
			$diagnostics
		);
	}

	return true;
}

function almaden_bookster_document_upload_diagnostics( $field_name ) {
	$content_length = isset( $_SERVER['CONTENT_LENGTH'] ) ? intval( $_SERVER['CONTENT_LENGTH'] ) : 0;
	$post_max_bytes = almaden_bookster_ini_size_to_bytes( ini_get( 'post_max_size' ) );
	$upload_max_bytes = almaden_bookster_ini_size_to_bytes( ini_get( 'upload_max_filesize' ) );
	$file = isset( $_FILES[ $field_name ] ) && is_array( $_FILES[ $field_name ] ) ? $_FILES[ $field_name ] : array();

	return array(
		'field_name'             => $field_name,
		'field_present'          => ! empty( $file ),
		'files_keys'             => array_keys( $_FILES ),
		'file_name'              => isset( $file['name'] ) ? sanitize_file_name( wp_unslash( $file['name'] ) ) : '',
		'file_size_bytes'        => isset( $file['size'] ) ? intval( $file['size'] ) : 0,
		'file_error'             => isset( $file['error'] ) ? intval( $file['error'] ) : null,
		'tmp_name_present'       => ! empty( $file['tmp_name'] ),
		'is_uploaded_file'       => ! empty( $file['tmp_name'] ) ? is_uploaded_file( $file['tmp_name'] ) : false,
		'content_length_bytes'   => $content_length,
		'post_max_size'          => ini_get( 'post_max_size' ),
		'post_max_size_bytes'    => $post_max_bytes,
		'upload_max_filesize'    => ini_get( 'upload_max_filesize' ),
		'upload_max_bytes'       => $upload_max_bytes,
		'max_file_uploads'       => ini_get( 'max_file_uploads' ),
		'post_exceeds_limit'     => $post_max_bytes > 0 && $content_length > $post_max_bytes,
		'request_method'         => isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '',
	);
}

function almaden_bookster_ini_size_to_bytes( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return 0;
	}

	$unit = strtolower( substr( $value, -1 ) );
	$number = (float) $value;
	switch ( $unit ) {
		case 'g':
			$number *= 1024;
			// fall through
		case 'm':
			$number *= 1024;
			// fall through
		case 'k':
			$number *= 1024;
	}

	return (int) $number;
}

function almaden_bookster_format_bytes_for_import( $bytes ) {
	$bytes = max( 0, intval( $bytes ) );
	if ( $bytes >= 1048576 ) {
		return round( $bytes / 1048576, 1 ) . ' MB';
	}
	if ( $bytes >= 1024 ) {
		return round( $bytes / 1024, 1 ) . ' KB';
	}
	return $bytes . ' bytes';
}

function almaden_bookster_upload_error_message( $error_code, array $diagnostics ) {
	switch ( $error_code ) {
		case UPLOAD_ERR_INI_SIZE:
			return sprintf( 'El archivo supera upload_max_filesize del servidor (%s).', $diagnostics['upload_max_filesize'] );
		case UPLOAD_ERR_FORM_SIZE:
			return 'El archivo supera el límite permitido por el formulario.';
		case UPLOAD_ERR_PARTIAL:
			return 'El archivo se subió solo parcialmente. Intenta nuevamente.';
		case UPLOAD_ERR_NO_FILE:
			return 'No se recibió ningún archivo. El navegador no envió document_file en la solicitud.';
		case UPLOAD_ERR_NO_TMP_DIR:
			return 'Falta la carpeta temporal de PHP para recibir subidas.';
		case UPLOAD_ERR_CANT_WRITE:
			return 'PHP no pudo escribir el archivo subido en disco.';
		case UPLOAD_ERR_EXTENSION:
			return 'Una extensión de PHP bloqueó la subida del archivo.';
		default:
			return 'No se pudo recibir el archivo subido. Código de error PHP: ' . intval( $error_code ) . '.';
	}
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
