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

if ( ! function_exists( 'almaden_bookster_typst_perf_now' ) ) {
	function almaden_bookster_typst_perf_now() {
		return function_exists( 'hrtime' ) ? hrtime( true ) : (int) round( microtime( true ) * 1000000000 );
	}
}

if ( ! function_exists( 'almaden_bookster_typst_perf_ms_since' ) ) {
	function almaden_bookster_typst_perf_ms_since( $started_at ) {
		return max( 0, round( ( almaden_bookster_typst_perf_now() - (int) $started_at ) / 1000000, 3 ) );
	}
}

if ( ! function_exists( 'almaden_bookster_typst_perf_set' ) ) {
	function almaden_bookster_typst_perf_set( $section, $value ) {
		if ( ! isset( $GLOBALS['almaden_bookster_typst_preview_performance'] ) || ! is_array( $GLOBALS['almaden_bookster_typst_preview_performance'] ) ) {
			$GLOBALS['almaden_bookster_typst_preview_performance'] = array( 'version' => 1 );
		}
		$GLOBALS['almaden_bookster_typst_preview_performance'][ $section ] = $value;
	}
}

if ( ! function_exists( 'almaden_bookster_typst_perf_add_timing' ) ) {
	function almaden_bookster_typst_perf_add_timing( $section, $started_at ) {
		almaden_bookster_typst_perf_set( $section . '_ms', almaden_bookster_typst_perf_ms_since( $started_at ) );
	}
}

function almaden_bookster_typst_perf_increment( $section, $amount = 1 ) {
	if ( ! isset( $GLOBALS['almaden_bookster_typst_preview_performance'] ) || ! is_array( $GLOBALS['almaden_bookster_typst_preview_performance'] ) ) {
		$GLOBALS['almaden_bookster_typst_preview_performance'] = array( 'version' => 1 );
	}
	$current = isset( $GLOBALS['almaden_bookster_typst_preview_performance'][ $section ] )
		? (int) $GLOBALS['almaden_bookster_typst_preview_performance'][ $section ]
		: 0;
	$GLOBALS['almaden_bookster_typst_preview_performance'][ $section ] = $current + (int) $amount;
}

function almaden_bookster_typst_perf_add_duration( $section, $started_at ) {
	if ( ! isset( $GLOBALS['almaden_bookster_typst_preview_performance'] ) || ! is_array( $GLOBALS['almaden_bookster_typst_preview_performance'] ) ) {
		$GLOBALS['almaden_bookster_typst_preview_performance'] = array( 'version' => 1 );
	}
	$key = $section . '_ms';
	$current = isset( $GLOBALS['almaden_bookster_typst_preview_performance'][ $key ] )
		? (float) $GLOBALS['almaden_bookster_typst_preview_performance'][ $key ]
		: 0.0;
	$GLOBALS['almaden_bookster_typst_preview_performance'][ $key ] = round( $current + almaden_bookster_typst_perf_ms_since( $started_at ), 3 );
}

function almaden_bookster_typst_runtime_key() {
	$family = strtolower( (string) PHP_OS_FAMILY );
	$machine = strtolower( (string) php_uname( 'm' ) );

	if ( in_array( $machine, array( 'arm64', 'aarch64' ), true ) ) {
		$arch = 'arm64';
	} elseif ( in_array( $machine, array( 'x86_64', 'amd64' ), true ) ) {
		$arch = 'x64';
	} else {
		$arch = preg_replace( '/[^a-z0-9_-]/', '', $machine );
	}

	if ( 'darwin' === $family ) {
		$os = 'darwin';
	} elseif ( 'linux' === $family ) {
		$os = 'linux';
	} elseif ( 'windows' === $family ) {
		$os = 'windows';
	} else {
		$os = preg_replace( '/[^a-z0-9_-]/', '', $family );
	}

	return trim( $os . '-' . $arch, '-' );
}

function almaden_bookster_typst_binary_candidates() {
	$runtime_dir = dirname( __DIR__, 2 ) . '/runtime/typst';
	$binary_name = 'windows' === strtolower( (string) PHP_OS_FAMILY ) ? 'typst.exe' : 'typst';
	$candidates = array();

	if ( defined( 'ALMADEN_BOOKSTER_TYPST_BINARY' ) ) {
		$candidates[] = ALMADEN_BOOKSTER_TYPST_BINARY;
	}

	$env_binary = getenv( 'ALMADEN_BOOKSTER_TYPST_BINARY' );
	if ( is_string( $env_binary ) && '' !== trim( $env_binary ) ) {
		$candidates[] = $env_binary;
	}

	$runtime_key = almaden_bookster_typst_runtime_key();
	if ( '' !== $runtime_key ) {
		$candidates[] = $runtime_dir . '/' . $runtime_key . '/' . $binary_name;
	}

	$candidates[] = $runtime_dir . '/' . $binary_name;

	return array_values( array_unique( array_filter( $candidates, 'is_string' ) ) );
}

function almaden_bookster_typst_missing_message( $candidates ) {
	$existing = array_values( array_filter(
		$candidates,
		static function ( $candidate ) {
			return is_string( $candidate ) && is_file( $candidate );
		}
	) );

	if ( ! empty( $existing ) ) {
		return 'Typst existe en el runtime de Almaden Bookster, pero PHP no puede ejecutarlo o no corresponde a esta plataforma (' . almaden_bookster_typst_runtime_key() . '). Revisa permisos de ejecución o instala el binario correcto en: ' . implode( ', ', $existing ) . '.';
	}

	return 'Typst no está instalado en el runtime de Almaden Bookster para esta plataforma (' . almaden_bookster_typst_runtime_key() . '). Incluye el binario en runtime/typst/' . almaden_bookster_typst_runtime_key() . '/typst o define ALMADEN_BOOKSTER_TYPST_BINARY con la ruta absoluta al ejecutable.';
}

function almaden_bookster_typst_binary_is_usable( $candidate ) {
	if ( ! is_string( $candidate ) || ! is_file( $candidate ) || ! is_executable( $candidate ) ) {
		return false;
	}

	if ( ! function_exists( 'almaden_bookster_run_process' ) ) {
		return true;
	}

	$stdout = '';
	$stderr = '';
	$result = almaden_bookster_run_process( array( $candidate, '--version' ), $stdout, $stderr, 5 );
	if ( function_exists( 'is_wp_error' ) && is_wp_error( $result ) ) {
		return false;
	}

	return false !== stripos( $stdout . $stderr, 'typst' );
}

function almaden_bookster_find_typst_binary() {
	$candidates = almaden_bookster_typst_binary_candidates();

	foreach ( $candidates as $candidate ) {
		if ( almaden_bookster_typst_binary_is_usable( $candidate ) ) {
			return $candidate;
		}
	}
	$found = trim( (string) shell_exec( 'command -v typst 2>/dev/null' ) );
	return almaden_bookster_typst_binary_is_usable( $found ) ? $found : '';
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
	$compiler_started_at = almaden_bookster_typst_perf_now();
	$GLOBALS['almaden_bookster_typst_integrity_warning'] = '';
	$preview_scope = isset( $document['preview_scope'] ) && 'chapter-fragment' === (string) $document['preview_scope']
		? 'chapter-fragment'
		: 'full-book';
	$is_chapter_fragment = 'chapter-fragment' === $preview_scope;
	almaden_bookster_typst_perf_set( 'compiler_preview_scope', $preview_scope );
	if ( ! empty( $document['build_error'] ) && is_wp_error( $document['build_error'] ) ) {
		return $document['build_error'];
	}
	$binary_started_at = almaden_bookster_typst_perf_now();
	$binary = almaden_bookster_find_typst_binary();
	almaden_bookster_typst_perf_add_timing( 'typst_binary_lookup', $binary_started_at );
	if ( '' === $binary ) {
		return new WP_Error( 'typst_missing', almaden_bookster_typst_missing_message( almaden_bookster_typst_binary_candidates() ) );
	}

	$temp_started_at = almaden_bookster_typst_perf_now();
	$temp_dir = trailingslashit( sys_get_temp_dir() ) . 'almaden-typst-' . wp_generate_uuid4();
	if ( ! wp_mkdir_p( $temp_dir ) ) {
		return new WP_Error( 'typst_temp_failed', 'No se pudo crear el directorio temporal de compilación.' );
	}
	almaden_bookster_typst_perf_add_timing( 'temp_dir_create', $temp_started_at );

	$input  = $temp_dir . '/book.typ';
	$output = $temp_dir . '/book.pdf';
	$font_path = '';
	if ( ! empty( $document['font_assets'] ) ) {
		$font_stage_started_at = almaden_bookster_typst_perf_now();
		$font_copy_count = 0;
		$fonts_dir = $temp_dir . '/fonts';
		wp_mkdir_p( $fonts_dir );
		foreach ( $document['font_assets'] as $index => $path ) {
			if ( is_file( $path ) && filesize( $path ) <= 8 * MB_IN_BYTES ) {
				$extension = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
				if ( in_array( $extension, array( 'ttf', 'otf', 'woff', 'woff2' ), true ) ) {
					copy( $path, $fonts_dir . '/font-' . (int) $index . '.' . $extension );
					++$font_copy_count;
				}
			}
		}
		$font_path = $fonts_dir;
		almaden_bookster_typst_perf_add_timing( 'font_stage', $font_stage_started_at );
		almaden_bookster_typst_perf_set( 'font_copy_count', $font_copy_count );
	}
	$input_write_started_at = almaden_bookster_typst_perf_now();
	file_put_contents( $input, $document['source'], LOCK_EX );
	almaden_bookster_typst_perf_add_timing( 'typst_input_write', $input_write_started_at );
	$asset_stage_started_at = almaden_bookster_typst_perf_now();
	$asset_stage = almaden_bookster_typst_stage_assets( $document['assets'] ?? array(), $temp_dir );
	almaden_bookster_typst_perf_add_timing( 'asset_stage_initial', $asset_stage_started_at );
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
		$query_started_at = almaden_bookster_typst_perf_now();
		$command = array( $binary, 'query', '--root', $temp_dir, '--diagnostic-format', 'short' );
		if ( '' !== $font_path ) {
			$command[] = '--font-path';
			$command[] = $font_path;
		}
		$command[] = $input;
		$command[] = $selector;
		$result = almaden_bookster_run_process( $command, $stdout, $stderr, 90 );
		almaden_bookster_typst_perf_increment( 'typst_query_count' );
		almaden_bookster_typst_perf_add_duration( 'typst_query_total', $query_started_at );
		$report = json_decode( $stdout, true );
		if ( is_wp_error( $result ) || ! is_array( $report ) ) {
			return array();
		}
		$values = array_values( array_filter( array_column( $report, 'value' ), 'is_array' ) );
		return $all ? $values : ( $values[0] ?? array() );
	};
	$page_template_started_at = almaden_bookster_typst_perf_now();
	$template_stage = almaden_bookster_typst_compile_page_templates( $document, $input, $temp_dir, $query_document );
	almaden_bookster_typst_perf_add_timing( 'page_template_compile', $page_template_started_at );
	if ( is_wp_error( $template_stage ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $template_stage;
	}
	$asset_stage_started_at = almaden_bookster_typst_perf_now();
	$asset_stage = almaden_bookster_typst_stage_assets( $document['assets'] ?? array(), $temp_dir );
	almaden_bookster_typst_perf_add_timing( 'asset_stage_final', $asset_stage_started_at );
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
	$typst_compile_started_at = almaden_bookster_typst_perf_now();
	$result = almaden_bookster_run_process(
		$command,
		$stdout,
		$stderr,
		90
	);
	almaden_bookster_typst_perf_add_timing( 'typst_compile_process', $typst_compile_started_at );
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
	$print_boxes_started_at = almaden_bookster_typst_perf_now();
	$print_boxes = almaden_bookster_typst_apply_print_boxes( $output, $document['geometry'] ?? array() );
	almaden_bookster_typst_perf_add_timing( 'print_boxes', $print_boxes_started_at );
	if ( is_wp_error( $print_boxes ) ) {
		almaden_bookster_typst_remove_tree( $temp_dir );
		return $print_boxes;
	}

	if ( $is_chapter_fragment ) {
		$GLOBALS['almaden_bookster_typst_image_blocks'] = array();
		$GLOBALS['almaden_bookster_typst_universal_counter'] = array(
			'version'  => 1,
			'source'   => 'chapter-fragment',
			'chapters' => array(),
		);
		almaden_bookster_typst_perf_set(
			'fast_preview_skipped',
			array( 'image_report_query', 'chapter_counter_query', 'pdftotext_integrity_check' )
		);
		$pdf_read_started_at = almaden_bookster_typst_perf_now();
		$pdf = file_get_contents( $output );
		almaden_bookster_typst_perf_add_timing( 'pdf_read', $pdf_read_started_at );
		if ( false === $pdf || 0 !== strpos( $pdf, '%PDF-' ) ) {
			$failed_source = trailingslashit( sys_get_temp_dir() ) . 'almaden-typst-invalid-' . wp_generate_uuid4() . '.typ';
			@file_put_contents( $failed_source, $document['source'] ?? '', LOCK_EX );
			almaden_bookster_typst_log_debug(
				'Typst produced an invalid fast-preview PDF payload.',
				array(
					'source_hash'   => $document['source_hash'] ?? '',
					'failed_source' => $failed_source,
				)
			);
			almaden_bookster_typst_remove_tree( $temp_dir );
			return new WP_Error( 'typst_invalid_pdf', 'El compilador devolvió un PDF inválido.' );
		}
		$cleanup_started_at = almaden_bookster_typst_perf_now();
		almaden_bookster_typst_remove_tree( $temp_dir );
		almaden_bookster_typst_perf_add_timing( 'temp_cleanup', $cleanup_started_at );
		almaden_bookster_typst_perf_add_timing( 'compiler_total', $compiler_started_at );
		return $pdf;
	}

	$image_report_started_at = almaden_bookster_typst_perf_now();
	$GLOBALS['almaden_bookster_typst_image_blocks'] = array_values( array_filter(
		$query_document( '<almaden-image-report>', true ),
		static function ( $entry ) { return ! empty( $entry['id'] ) && ! empty( $entry['page'] ); }
	) );
	almaden_bookster_typst_perf_add_timing( 'image_report_query', $image_report_started_at );
	$counter_report_started_at = almaden_bookster_typst_perf_now();
	$universal_counter = array_values( array_filter( (array) $query_document( '<almaden-chapter-counter-report>' ), static function ( $entry ) {
		return is_array( $entry ) && '' !== trim( (string) ( $entry['id'] ?? '' ) );
	} ) );
	almaden_bookster_typst_perf_add_timing( 'chapter_counter_query', $counter_report_started_at );
	$GLOBALS['almaden_bookster_typst_universal_counter'] = array(
		'version'  => 1,
		'source'   => 'full-book',
		'chapters' => $universal_counter,
	);
	$pdftotext_lookup_started_at = almaden_bookster_typst_perf_now();
	$extractor = almaden_bookster_typst_find_pdftotext_binary();
	almaden_bookster_typst_perf_add_timing( 'pdftotext_lookup', $pdftotext_lookup_started_at );
	if ( '' !== $extractor ) {
		$extract_text = static function ( $mode ) use ( $extractor, $output, $temp_dir, &$stdout, &$stderr ) {
			$extract_started_at = almaden_bookster_typst_perf_now();
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
			almaden_bookster_typst_perf_increment( 'pdftotext_count' );
			almaden_bookster_typst_perf_add_duration( 'pdftotext_total', $extract_started_at );
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
		$integrity_started_at = almaden_bookster_typst_perf_now();
		$actual_text = $extract_text( 'raw' );
		if ( ! $verify_text( $actual_text, $missing_near ) ) {
			$layout_missing_near = '';
			$layout_text = $extract_text( 'layout' );
			if ( '' === $layout_text || ! $verify_text( $layout_text, $layout_missing_near ) ) {
				$GLOBALS['almaden_bookster_typst_integrity_warning'] = 'La verificación detectó una posible diferencia cerca de: "' . ( '' !== $layout_missing_near ? $layout_missing_near : $missing_near ) . '".';
			}
		}
		almaden_bookster_typst_perf_add_timing( 'integrity_check', $integrity_started_at );
	}

	$pdf_read_started_at = almaden_bookster_typst_perf_now();
	$pdf = file_get_contents( $output );
	almaden_bookster_typst_perf_add_timing( 'pdf_read', $pdf_read_started_at );
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
	$cleanup_started_at = almaden_bookster_typst_perf_now();
	almaden_bookster_typst_remove_tree( $temp_dir );
	almaden_bookster_typst_perf_add_timing( 'temp_cleanup', $cleanup_started_at );
	almaden_bookster_typst_perf_add_timing( 'compiler_total', $compiler_started_at );
	return $pdf;
}
