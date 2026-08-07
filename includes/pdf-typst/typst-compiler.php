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
	$stdout = '';
	$stderr = '';
	$GLOBALS['almaden_bookster_typst_page_flow_map'] = array();
	$GLOBALS['almaden_bookster_typst_page_template_results'] = array();
	if ( ! empty( $document['page_templates'] ) ) {
		$flow_context = $document['page_template_context'] ?? array( 'templates' => $document['page_templates'] ?? array(), 'columns_count' => 2, 'columns_gap' => 0.8, 'unit' => 'cm' );
		$template_assets = isset( $document['assets'] ) && is_array( $document['assets'] ) ? $document['assets'] : array();
		$sync_template_assets = static function () use ( $temp_dir, &$template_assets ) {
			$assets_dir = $temp_dir . '/assets';
			if ( ! is_dir( $assets_dir ) ) {
				wp_mkdir_p( $assets_dir );
			}
			foreach ( $template_assets as $name => $path ) {
				$target = $assets_dir . '/' . $name;
				if ( preg_match( '/^[a-f0-9]{64}\.[a-z0-9]+$/i', $name ) && is_file( $path ) && ! is_file( $target ) ) {
					copy( $path, $target );
				}
			}
		};
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

		$templates = array_values( (array) ( $flow_context['templates'] ?? array() ) );
		$sync_template_assets();
		$initial_flow_map = $read_flow_map();
		foreach ( $templates as &$candidate_template ) {
			if ( '' !== (string) ( $candidate_template['anchor']['flow_id'] ?? '' ) ) {
				continue;
			}
			$initial_resolution = almaden_bookster_typst_resolve_page_template( $candidate_template, $initial_flow_map );
			if ( ! empty( $initial_resolution['applied'] ) ) {
				$candidate_template = $initial_resolution['template'];
				$candidate_template['_legacy_migrated'] = true;
			}
		}
		unset( $candidate_template );
		usort( $templates, static function ( $left, $right ) {
			$left_order = almaden_bookster_typst_page_template_flow_order( $left['anchor']['flow_id'] ?? '' );
			$right_order = almaden_bookster_typst_page_template_flow_order( $right['anchor']['flow_id'] ?? '' );
			return $left_order === $right_order
				? (int) ( $left['resolved_page'] ?? $left['page_number'] ?? 0 ) <=> (int) ( $right['resolved_page'] ?? $right['page_number'] ?? 0 )
				: $left_order <=> $right_order;
		} );

		foreach ( $templates as $template_index => $stored_template ) {
			$sync_template_assets();
			$flow_map = $read_flow_map();
			$resolution = almaden_bookster_typst_resolve_page_template( $stored_template, $flow_map );
			$instance_id = (string) ( $stored_template['instance_id'] ?? $stored_template['id'] ?? '' );
			if ( empty( $resolution['applied'] ) ) {
				$GLOBALS['almaden_bookster_typst_page_template_results'][] = array(
					'instance_id'   => $instance_id,
					'requested_page' => (int) ( $stored_template['page_number'] ?? 0 ),
					'resolved_page' => 0,
					'page'          => 0,
					'flow_rows'     => 0,
					'applied'       => false,
					'anchor'        => $stored_template['anchor'] ?? array(),
					'debug'         => array( 'reason' => $resolution['reason'] ?? 'anchor_not_resolved' ),
				);
				continue;
			}
			$template = $resolution['template'];
			$target_page = (int) ( $template['page_number'] ?? 0 );
			$next_anchor_order = PHP_INT_MAX;
			for ( $next_index = $template_index + 1, $template_count = count( $templates ); $next_index < $template_count; ++$next_index ) {
				$candidate_order = almaden_bookster_typst_page_template_flow_order( $templates[ $next_index ]['anchor']['flow_id'] ?? '' );
				if ( PHP_INT_MAX !== $candidate_order ) {
					$next_anchor_order = $candidate_order;
					break;
				}
			}
			$template_flow_map = almaden_bookster_typst_page_template_rows_before_anchor(
				$flow_map,
				PHP_INT_MAX === $next_anchor_order ? '' : 'almaden-flow-' . $next_anchor_order
			);
			$flow_rows = almaden_bookster_typst_page_template_target_rows( $template_flow_map, $template );
			if ( empty( $flow_map ) || ! function_exists( 'almaden_bookster_typst_apply_page_template_flow' ) ) {
				$GLOBALS['almaden_bookster_typst_page_template_results'][] = array(
					'instance_id' => $instance_id,
					'resolved_page' => $target_page,
					'page' => $target_page,
					'flow_rows' => count( $flow_rows ),
					'applied' => false,
					'anchor' => $template['anchor'],
					'debug' => array( 'reason' => 'flow_map_unavailable' ),
				);
				break;
			}
			$word_probe = array();
			if ( function_exists( 'almaden_bookster_typst_page_template_prepare_word_probe' ) ) {
				$word_probe = almaden_bookster_typst_page_template_prepare_word_probe( $document['source'], $flow_context, $template_flow_map, $template );
				if ( ! empty( $word_probe['source'] ) ) {
					file_put_contents( $input, $word_probe['source'], LOCK_EX );
					$cut = almaden_bookster_typst_page_template_probe_cut( $word_probe, $read_query( '<almaden-template-probe-report>' ) );
					if ( ! empty( $cut ) ) {
						$word_probe['cut'] = $cut;
					}
				}
			}
			$updated_source = almaden_bookster_typst_apply_page_template_flow( $document['source'], $flow_context, $template_flow_map, $template, $word_probe, $template_assets );
			$GLOBALS['almaden_bookster_typst_page_template_results'][] = array(
				'instance_id'    => $instance_id,
				'requested_page' => (int) ( $stored_template['page_number'] ?? 0 ),
				'resolved_page'  => $target_page,
				'page'           => $target_page,
				'flow_rows'      => count( $flow_rows ),
				'applied'        => $updated_source !== $document['source'],
				'anchor'         => $template['anchor'],
				'legacy_migrated' => ! empty( $resolution['legacy_migrated'] ) || ! empty( $stored_template['_legacy_migrated'] ),
				'debug'          => $GLOBALS['almaden_bookster_typst_page_template_debug'] ?? array(),
			);
			if ( $updated_source === $document['source'] ) {
				file_put_contents( $input, $document['source'], LOCK_EX );
				continue;
			}
			$document['source'] = $updated_source;
			file_put_contents( $input, $document['source'], LOCK_EX );
		}

		// Expose the final layout to the PDF viewer after all page templates have reflowed it.
		$sync_template_assets();
		$GLOBALS['almaden_bookster_typst_page_flow_map'] = $read_flow_map();
		$document['assets'] = $template_assets;
	}
	if ( ! empty( $document['assets'] ) ) {
		$assets_dir = $temp_dir . '/assets';
		wp_mkdir_p( $assets_dir );
		foreach ( $document['assets'] as $name => $path ) {
			if ( preg_match( '/^[a-f0-9]{64}\.[a-z0-9]+$/i', $name ) && is_file( $path ) ) {
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
