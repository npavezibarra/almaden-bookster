<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function almaden_bookster_parse_pdf_import_document( $path, $filename ) {
	$binary = almaden_bookster_find_pdftotext_binary();
	if ( empty( $binary ) ) {
		return new WP_Error( 'pdf_text_missing', 'No se encontró pdftotext en el servidor. Para importar PDF necesitas texto seleccionable o un DOCX/RTF.' );
	}

	$tmp_txt = wp_tempnam( $filename . '.txt' );
	if ( empty( $tmp_txt ) ) {
		return new WP_Error( 'tmp_failed', 'No se pudo crear un archivo temporal para procesar el PDF.' );
	}

	$cmd = escapeshellarg( $binary ) . ' -enc UTF-8 -layout ' . escapeshellarg( $path ) . ' ' . escapeshellarg( $tmp_txt );
	$stdout = '';
	$stderr = '';
	$result = almaden_bookster_run_process( $cmd, $stdout, $stderr, 45 );
	if ( is_wp_error( $result ) ) {
		@unlink( $tmp_txt );
		return new WP_Error( 'pdf_extract_failed', 'No se pudo extraer el texto del PDF: ' . $result->get_error_message() );
	}

	$text = file_get_contents( $tmp_txt );
	@unlink( $tmp_txt );
	if ( false === $text ) {
		return new WP_Error( 'pdf_read_failed', 'No se pudo leer el texto extraído del PDF.' );
	}

	return almaden_bookster_blocks_from_plain_text( $text, $filename, 'pdf' );
}

function almaden_bookster_find_pdftotext_binary() {
	$candidates = array( 'pdftotext', '/opt/homebrew/bin/pdftotext', '/usr/local/bin/pdftotext' );
	foreach ( $candidates as $candidate ) {
		if ( '/' === $candidate[0] && file_exists( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
		$found = trim( (string) shell_exec( 'command -v ' . escapeshellarg( $candidate ) . ' 2>/dev/null' ) );
		if ( ! empty( $found ) && file_exists( $found ) ) {
			return $found;
		}
	}
	return '';
}
