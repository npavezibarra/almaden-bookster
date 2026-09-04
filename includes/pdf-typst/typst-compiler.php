<?php
/**
 * Isolated Typst process and PDF integrity validation.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

require_once __DIR__ . '/typst-compiler-assets.php';
require_once __DIR__ . '/typst-pdf-boxes.php';
require_once __DIR__ . '/page-templates/bootstrap.php';

function almaden_bookster_find_typst_binary() {
	$candidates = array();
	if ( defined( 'ALMADEN_BOOKSTER_TYPST_BINARY' ) ) {
		$candidates[] = ALMADEN_BOOKSTER_TYPST_BINARY;
	}
	$candidates[] = dirname( __DIR__, 2 ) . '/runtime/typst/typst';

	foreach ( $candidates as $candidate ) {
		if ( is_string( $candidate ) && is_file( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}
	$found = trim( (string) shell_exec( 'command -v typst 2>/dev/null' ) );
	return is_file( $found ) && is_executable( $found ) ? $found : '';
}

function almaden_bookster_typst_find_pdftotext_binary() {
	$candidates = array(
		'/usr/local/bin/pdftotext',
		'/opt/homebrew/bin/pdftotext',
		'/Users/nicolaspavez/.cache/codex-runtimes/codex-primary-runtime/dependencies/native/poppler/poppler/bin/pdftotext',
	);
	foreach ( $candidates as $candidate ) {
		if ( is_file( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}
	$found = trim( (string) shell_exec( 'command -v pdftotext 2>/dev/null' ) );
	return is_file( $found ) && is_executable( $found ) ? $found : '';
}

function almaden_bookster_typst_remove_tree( $path ) {
	if ( ! is_dir( $path ) ) {
		return;
	}
	$items = scandir( $path );
	foreach ( $items as $item ) {
		if ( '.' === $item || '..' === $item ) {
			continue;
		}
		$target = $path . DIRECTORY_SEPARATOR . $item;
		is_dir( $target ) ? almaden_bookster_typst_remove_tree( $target ) : @unlink( $target );
	}
	@rmdir( $path );
}

function almaden_bookster_typst_tokens( $text ) {
	$text = preg_replace( '/(?:\x{00AD}|\p{Cf})\s*/u', '', (string) $text );
	$text = preg_replace( '/-\s+/u', '', (string) $text );
	preg_match_all( '/\p{L}+|\p{N}+/u', function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text ), $matches );
	return $matches[0];
}

function almaden_bookster_typst_is_subsequence( $expected, $actual, &$missing_near = '', &$match_ratio = null ) {
	$expected_tokens = almaden_bookster_typst_tokens( $expected );
	$actual_tokens   = almaden_bookster_typst_tokens( $actual );
	$expected_count  = count( $expected_tokens );
	$cursor          = 0;
	$matched         = 0;
	foreach ( $expected_tokens as $index => $token ) {
		while ( $cursor < count( $actual_tokens ) && $actual_tokens[ $cursor ] !== $token ) {
			++$cursor;
		}
		if ( $cursor >= count( $actual_tokens ) ) {
			$missing_near = implode( ' ', array_slice( $expected_tokens, max( 0, $index - 8 ), 18 ) );
			if ( null !== $match_ratio ) {
				$match_ratio = $expected_count > 0 ? $matched / $expected_count : 1;
			}
			return false;
		}
		++$matched;
		++$cursor;
	}
	if ( null !== $match_ratio ) {
		$match_ratio = $expected_count > 0 ? $matched / $expected_count : 1;
	}
	return true;
}

function almaden_bookster_typst_debug_enabled() {
	if ( defined( 'ALMADEN_BOOKSTER_TYPST_DEBUG' ) ) {
		return (bool) ALMADEN_BOOKSTER_TYPST_DEBUG;
	}
	if ( function_exists( 'wp_get_environment_type' ) && in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
		return true;
	}
	return defined( 'WP_DEBUG' ) && WP_DEBUG;
}

function almaden_bookster_typst_log_debug( $message, $context = array() ) {
	if ( ! almaden_bookster_typst_debug_enabled() ) {
		return;
	}

	$payload = array( 'message' => (string) $message ) + (array) $context;
	$encoded = function_exists( 'wp_json_encode' )
		? wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		: json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	error_log( '[Almaden Typst] ' . ( false === $encoded ? $message : $encoded ) );
}

/**
 * Compile source to PDF and reject output if semantic source tokens are absent.
 */
function almaden_bookster_compile_typst_pdf( $document ) {
	$GLOBALS['almaden_bookster_typst_integrity_warning'] = '';
	if ( ! empty( $document['build_error'] ) && is_wp_error( $document['build_error'] ) ) {
		return $document['build_error'];
	}
	$binary = almaden_bookster_find_typst_binary();
	if ( '' === $binary ) {
		return new WP_Error( 'typst_missing', 'Typst no está instalado en el runtime de Almaden Bookster.' );
	}

	$temp_dir = trailingslashit( sys_get_temp_dir() ) . 'almaden-typst-' . wp_generate_uuid4();
	if ( ! wp_mkdir_p( $temp_dir ) ) {
		return new WP_Error( 'typst_temp_failed', 'No se pudo crear el directorio temporal de compilación.' );
	}

	$input  = $temp_dir . '/book.typ';
	$output = $temp_dir . '/book.pdf';
	$font_path = '';
	if ( ! empty( $document['font_assets'] ) ) {
		$fonts_dir = $temp_dir . '/fonts';
		wp_mkdir_p( $fonts_dir );
		foreach ( $document['font_assets'] as $index => $path ) {
			if ( is_file( $path ) && filesize( $path ) <= 8 * MB_IN_BYTES ) {
				$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
				if ( in_array( $extension, array( 'ttf', 'otf', 'woff', 'woff2' ), true ) ) {
					copy( $path, $fonts_dir . '/font-' . (int) $index . '.' . $extension );
				}
			}
		}
		$font_path = $fonts_dir;
	}
	file_put_contents( $input, $document['source'], LOCK_EX );
	$asset_stage = almaden_bookster_typst_stage_assets( $document['assets'] ?? array(), $temp_dir );
	if ( is_wp_error( $asset_stage ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $asset_stage;
	}
	$stdout = '';
	$stderr = '';
	$GLOBALS['almaden_bookster_typst_page_flow_map'] = array();
	$GLOBALS['almaden_bookster_typst_page_template_results'] = array();
	$GLOBALS['almaden_bookster_typst_page_template_asset_diagnostics'] = array();
	$GLOBALS['almaden_bookster_typst_page_template_asset_audit'] = array();
	$GLOBALS['almaden_bookster_typst_image_blocks'] = array();
	$query_document = static function ( $selector, $all = false ) use ( $binary, $temp_dir, $font_path, $input, &$stdout, &$stderr ) {
		$command = array( $binary, 'query', '--root', $temp_dir, '--diagnostic-format', 'short' );
		if ( '' !== $font_path ) {
			$command[] = '--font-path';
			$command[] = $font_path;
		}
		$command[] = $input;
		$command[] = $selector;
		$result = almaden_bookster_run_process( $command, $stdout, $stderr, 90 );
		$report = json_decode( $stdout, true );
		if ( is_wp_error( $result ) || ! is_array( $report ) ) {
			return array();
		}
		$values = array_values( array_filter( array_column( $report, 'value' ), 'is_array' ) );
		return $all ? $values : ( $values[0] ?? array() );
	};
	$template_stage = almaden_bookster_typst_compile_page_templates( $document, $input, $temp_dir, $query_document );
	if ( is_wp_error( $template_stage ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $template_stage;
	}
	$asset_stage = almaden_bookster_typst_stage_assets( $document['assets'] ?? array(), $temp_dir );
	if ( is_wp_error( $asset_stage ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $asset_stage;
	}
	$command = array( $binary, 'compile', '--root', $temp_dir, '--diagnostic-format', 'short' );
	if ( '' !== $font_path ) {
		$command[] = '--font-path';
		$command[] = $font_path;
	}
	$command[] = $input;
	$command[] = $output;
	$result = almaden_bookster_run_process(
		$command,
		$stdout,
		$stderr,
		90
	);
	if ( is_wp_error( $result ) || ! is_file( $output ) ) {
		$failed_source = trailingslashit( sys_get_temp_dir() ) . 'almaden-typst-failed-' . wp_generate_uuid4() . '.typ';
		@file_put_contents( $failed_source, $document['source'] ?? '', LOCK_EX );
		almaden_bookster_typst_log_debug(
			'Typst compile failed.',
			array(
				'source_hash'       => $document['source_hash'] ?? '',
				'page_templates'     => $document['page_templates'] ?? array(),
				'page_template_results' => $GLOBALS['almaden_bookster_typst_page_template_results'] ?? array(),
				'stdout'            => trim( (string) $stdout ),
				'stderr'            => trim( (string) $stderr ),
				'command'           => $command,
				'failed_source'     => $failed_source,
				'build_error'       => is_wp_error( $result ) ? $result->get_error_code() . ': ' . $result->get_error_message() : 'typst_no_pdf',
			)
		);
		almaden_bookster_typst_remove_tree( $temp_dir );
		return is_wp_error( $result ) ? $result : new WP_Error( 'typst_no_pdf', 'Typst no produjo un archivo PDF.' );
	}
	$print_boxes = almaden_bookster_typst_apply_print_boxes( $output, $document['geometry'] ?? array() );
	if ( is_wp_error( $print_boxes ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $print_boxes;
	}

	$GLOBALS['almaden_bookster_typst_image_blocks'] = array_values( array_filter(
		$query_document( '<almaden-image-report>', true ),
		static function ( $entry ) { return ! empty( $entry['id'] ) && ! empty( $entry['page'] ); }
	) );
	$universal_counter = array_values( array_filter( (array) $query_document( '<almaden-chapter-counter-report>' ), static function ( $entry ) {
		return is_array( $entry ) && '' !== trim( (string) ( $entry['id'] ?? '' ) );
	} ) );
	$GLOBALS['almaden_bookster_typst_universal_counter'] = array(
		'version'  => 1,
		'source'   => 'full-book',
		'chapters' => $universal_counter,
	);
	$extractor = almaden_bookster_typst_find_pdftotext_binary();
	if ( '' !== $extractor ) {
		$extract_text = static function ( $mode ) use ( $extractor, $output, $temp_dir, &$stdout, &$stderr ) {
			$txt_file = $temp_dir . '/book-' . $mode . '.txt';
			$command = array( $extractor );
			if ( 'layout' === $mode ) {
				$command[] = '-layout';
			} else {
				$command[] = '-raw';
			}
			$command[] = $output;
			$command[] = $txt_file;
			$check = almaden_bookster_run_process( $command, $stdout, $stderr, 30 );
			if ( is_wp_error( $check ) || ! is_file( $txt_file ) ) {
				return '';
			}
			return (string) file_get_contents( $txt_file );
		};
		$verify_text = static function ( $actual_text, &$missing_near ) use ( $document ) {
			$missing_near = '';
			if ( '' === $actual_text ) {
				return false;
			}
			$main_ratio = 0;
			if ( ! almaden_bookster_typst_is_subsequence( $document['semantic_text'], $actual_text, $missing_near, $main_ratio ) && $main_ratio < 0.9 ) {
				return false;
			}
			foreach ( $document['semantic_extras'] ?? array() as $extra ) {
				$extra_ratio = 0;
				if ( ! almaden_bookster_typst_is_subsequence( $extra, $actual_text, $missing_near, $extra_ratio ) && $extra_ratio < 0.75 ) {
					return false;
				}
			}
			return true;
		};
		$missing_near = '';
		$actual_text = $extract_text( 'raw' );
		if ( ! $verify_text( $actual_text, $missing_near ) ) {
			$layout_missing_near = '';
			$layout_text = $extract_text( 'layout' );
			if ( '' === $layout_text || ! $verify_text( $layout_text, $layout_missing_near ) ) {
				$GLOBALS['almaden_bookster_typst_integrity_warning'] = 'La verificación detectó una posible diferencia cerca de: "' . ( '' !== $layout_missing_near ? $layout_missing_near : $missing_near ) . '".';
			}
		}
	}

	$pdf = file_get_contents( $output );
	if ( false === $pdf || 0 !== strpos( $pdf, '%PDF-' ) ) {
		$failed_source = trailingslashit( sys_get_temp_dir() ) . 'almaden-typst-invalid-' . wp_generate_uuid4() . '.typ';
		@file_put_contents( $failed_source, $document['source'] ?? '', LOCK_EX );
		almaden_bookster_typst_log_debug(
			'Typst produced an invalid PDF payload.',
			array(
				'source_hash'   => $document['source_hash'] ?? '',
				'page_templates' => $document['page_templates'] ?? array(),
				'page_template_results' => $GLOBALS['almaden_bookster_typst_page_template_results'] ?? array(),
				'failed_source' => $failed_source,
			)
		);
		almaden_bookster_typst_remove_tree( $temp_dir );
		return new WP_Error( 'typst_invalid_pdf', 'El compilador devolvió un PDF inválido.' );
	}
	almaden_bookster_typst_remove_tree( $temp_dir );
	return $pdf;
}
