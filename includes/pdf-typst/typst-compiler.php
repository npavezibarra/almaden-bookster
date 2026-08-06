<?php
/**
 * Isolated Typst process and PDF integrity validation.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

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

function almaden_bookster_typst_is_subsequence( $expected, $actual, &$missing_near = '' ) {
	$expected_tokens = almaden_bookster_typst_tokens( $expected );
	$actual_tokens   = almaden_bookster_typst_tokens( $actual );
	$cursor          = 0;
	foreach ( $expected_tokens as $index => $token ) {
		while ( $cursor < count( $actual_tokens ) && $actual_tokens[ $cursor ] !== $token ) {
			++$cursor;
		}
		if ( $cursor >= count( $actual_tokens ) ) {
			$missing_near = implode( ' ', array_slice( $expected_tokens, max( 0, $index - 8 ), 18 ) );
			return false;
		}
		++$cursor;
	}
	return true;
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
	$stdout = '';
	$stderr = '';
	$GLOBALS['almaden_bookster_typst_page_flow_map'] = array();
	$GLOBALS['almaden_bookster_typst_page_template_results'] = array();
	if ( ! empty( $document['page_templates'] ) ) {
		$flow_context = $document['page_template_context'] ?? array( 'templates' => $document['page_templates'] ?? array(), 'columns_count' => 2, 'columns_gap' => 0.8, 'unit' => 'cm' );
		$template_assets = isset( $document['assets'] ) && is_array( $document['assets'] ) ? $document['assets'] : array();
		$read_query = static function ( $selector ) use ( $binary, $temp_dir, $font_path, $input, &$stdout, &$stderr ) {
			$flow_command = array( $binary, 'query', '--root', $temp_dir, '--diagnostic-format', 'short' );
			if ( '' !== $font_path ) {
				$flow_command[] = '--font-path';
				$flow_command[] = $font_path;
			}
			$flow_command[] = $input;
			$flow_command[] = $selector;
			$flow_result = almaden_bookster_run_process( $flow_command, $stdout, $stderr, 90 );
			$flow_report = json_decode( $stdout, true );
			return ! is_wp_error( $flow_result ) && isset( $flow_report[0]['value'] ) && is_array( $flow_report[0]['value'] )
				? $flow_report[0]['value']
				: array();
		};
		$read_flow_map = static function () use ( $read_query ) {
			return $read_query( '<almaden-flow-report>' );
		};

		foreach ( (array) ( $flow_context['templates'] ?? array() ) as $template ) {
			$flow_map = $read_flow_map();
			$target_page = (int) ( $template['page_number'] ?? 0 );
			$flow_rows = array_filter( $flow_map, static function ( $row ) use ( $target_page ) {
				return is_array( $row ) && (int) ( $row['page'] ?? 0 ) === $target_page;
			} );
			if ( empty( $flow_map ) || ! function_exists( 'almaden_bookster_typst_apply_page_template_flow' ) ) {
				$GLOBALS['almaden_bookster_typst_page_template_results'][] = array( 'page' => $target_page, 'flow_rows' => count( $flow_rows ), 'applied' => false );
				break;
			}
			$word_probe = array();
			if ( function_exists( 'almaden_bookster_typst_page_template_prepare_word_probe' ) ) {
				$word_probe = almaden_bookster_typst_page_template_prepare_word_probe( $document['source'], $flow_context, $flow_map, $template );
				if ( ! empty( $word_probe['source'] ) ) {
					file_put_contents( $input, $word_probe['source'], LOCK_EX );
					$cut = almaden_bookster_typst_page_template_probe_cut( $word_probe, $read_query( '<almaden-template-probe-report>' ) );
					if ( ! empty( $cut ) ) {
						$word_probe['cut'] = $cut;
					}
				}
			}
			$updated_source = almaden_bookster_typst_apply_page_template_flow( $document['source'], $flow_context, $flow_map, $template, $word_probe, $template_assets );
			$GLOBALS['almaden_bookster_typst_page_template_results'][] = array(
				'page'      => $target_page,
				'flow_rows' => count( $flow_rows ),
				'applied'   => $updated_source !== $document['source'],
				'debug'     => $GLOBALS['almaden_bookster_typst_page_template_debug'] ?? array(),
			);
			if ( $updated_source === $document['source'] ) {
				file_put_contents( $input, $document['source'], LOCK_EX );
				continue;
			}
			$document['source'] = $updated_source;
			file_put_contents( $input, $document['source'], LOCK_EX );
		}

		// Expose the final layout to the PDF viewer after all page templates have reflowed it.
		$GLOBALS['almaden_bookster_typst_page_flow_map'] = $read_flow_map();
		$document['assets'] = $template_assets;
	}
	if ( ! empty( $document['assets'] ) ) {
		$assets_dir = $temp_dir . '/assets';
		wp_mkdir_p( $assets_dir );
		foreach ( $document['assets'] as $name => $path ) {
			if ( preg_match( '/^[a-f0-9]{64}\.[a-z0-9]+$/', $name ) && is_file( $path ) ) {
				copy( $path, $assets_dir . '/' . $name );
			}
		}
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
		almaden_bookster_typst_remove_tree( $temp_dir );
		return is_wp_error( $result ) ? $result : new WP_Error( 'typst_no_pdf', 'Typst no produjo un archivo PDF.' );
	}

	$extractor = almaden_bookster_typst_find_pdftotext_binary();
	if ( '' !== $extractor ) {
		$txt_file = $temp_dir . '/book.txt';
		$check    = almaden_bookster_run_process( array( $extractor, '-raw', $output, $txt_file ), $stdout, $stderr, 30 );
		if ( is_wp_error( $check ) || ! is_file( $txt_file ) ) {
			$GLOBALS['almaden_bookster_typst_integrity_warning'] = 'No se pudo verificar el texto del PDF compilado.';
		} else {
			$missing_near = '';
			$actual_text  = file_get_contents( $txt_file );
			if ( ! almaden_bookster_typst_is_subsequence( $document['semantic_text'], $actual_text, $missing_near ) ) {
				$GLOBALS['almaden_bookster_typst_integrity_warning'] = 'La verificación detectó una posible diferencia cerca de: "' . $missing_near . '".';
			}
			foreach ( $document['semantic_extras'] ?? array() as $extra ) {
				if ( ! almaden_bookster_typst_is_subsequence( $extra, $actual_text, $missing_near ) ) {
					$GLOBALS['almaden_bookster_typst_integrity_warning'] = 'La verificación detectó una posible diferencia en una nota cerca de: "' . $missing_near . '".';
					break;
				}
			}
		}
	}

	$pdf = file_get_contents( $output );
	almaden_bookster_typst_remove_tree( $temp_dir );
	if ( false === $pdf || 0 !== strpos( $pdf, '%PDF-' ) ) {
		return new WP_Error( 'typst_invalid_pdf', 'El compilador devolvió un PDF inválido.' );
	}
	return $pdf;
}
