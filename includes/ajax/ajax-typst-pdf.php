<?php
/**
 * Authenticated binary PDF endpoint for the Typst editor preview.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __DIR__ ) . '/pdf-typst/typst-document.php';
require_once dirname( __DIR__ ) . '/pdf-typst/typst-compiler.php';

add_action( 'wp_ajax_almaden_compile_typst_pdf', 'almaden_bookster_ajax_compile_typst_pdf' );

if ( ! defined( 'ALMADEN_BOOKSTER_TYPST_PREVIEW_RENDERER_VERSION' ) ) {
	define( 'ALMADEN_BOOKSTER_TYPST_PREVIEW_RENDERER_VERSION', '16' );
}

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

/**
 * Replace layout collections from the editor with their persisted versions.
 *
 * Page templates and page styles are saved before preview compilation. Reading
 * both collections here keeps the compiler on one authoritative snapshot and
 * prevents stale browser state from producing a different PDF.
 */
function almaden_bookster_typst_hydrate_persisted_layout_settings( $book_id, $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	if ( function_exists( 'almaden_bookster_typst_get_page_templates' ) ) {
		$settings['page_templates'] = almaden_bookster_typst_get_page_templates( $book_id );
	}
	if ( function_exists( 'almaden_bookster_typst_get_page_styles' ) ) {
		$settings['page_styles'] = almaden_bookster_typst_get_page_styles( $book_id );
	}

	return $settings;
}

/**
 * Cache compiled previews by their complete Typst input and asset freshness.
 * The system temp directory keeps these private binary accelerators outside the
 * public uploads tree; a cache miss always falls back to normal compilation.
 */
function almaden_bookster_typst_preview_cache_key( $document ) {
	$parts = array(
		'almaden-typst-preview-v' . ALMADEN_BOOKSTER_TYPST_PREVIEW_RENDERER_VERSION,
		(string) ( $document['source'] ?? '' ),
		wp_json_encode( $document['page_templates'] ?? array() ),
		wp_json_encode( $document['page_styles'] ?? array() ),
	);
	$paths = array_merge(
		array_values( (array) ( $document['assets'] ?? array() ) ),
		array_values( (array) ( $document['font_assets'] ?? array() ) )
	);
	sort( $paths, SORT_STRING );
	foreach ( $paths as $path ) {
		$parts[] = is_file( $path )
			? $path . ':' . (string) filesize( $path ) . ':' . (string) filemtime( $path )
			: $path . ':missing';
	}

	return hash( 'sha256', implode( "\n", $parts ) );
}

function almaden_bookster_typst_preview_cache_dir( $book_id ) {
	return trailingslashit( get_temp_dir() ) . 'almaden-bookster-typst-preview/' . absint( $book_id );
}

function almaden_bookster_typst_preview_cache_scope( $meta ) {
	return isset( $meta['preview_scope'] ) && 'chapter-fragment' === (string) $meta['preview_scope']
		? 'chapter-fragment'
		: 'full-book';
}

function almaden_bookster_typst_preview_cache_scope_limit( $scope ) {
	return 'chapter-fragment' === (string) $scope ? 48 : 6;
}

function almaden_bookster_typst_preview_cache_read( $book_id, $cache_key ) {
	$dir       = almaden_bookster_typst_preview_cache_dir( $book_id );
	$pdf_path  = $dir . '/' . $cache_key . '.pdf';
	$meta_path = $dir . '/' . $cache_key . '.json';
	if ( ! is_file( $pdf_path ) || ! is_file( $meta_path ) ) {
		return null;
	}
	if ( filemtime( $pdf_path ) < time() - 7 * DAY_IN_SECONDS ) {
		@unlink( $pdf_path );
		@unlink( $meta_path );
		return null;
	}
	$pdf  = file_get_contents( $pdf_path );
	$meta = json_decode( (string) file_get_contents( $meta_path ), true );
	if ( false === $pdf || 0 !== strpos( $pdf, '%PDF-' ) || ! is_array( $meta ) ) {
		@unlink( $pdf_path );
		@unlink( $meta_path );
		return null;
	}
	@touch( $pdf_path );
	@touch( $meta_path );

	return array( 'pdf' => $pdf, 'meta' => $meta );
}

function almaden_bookster_typst_preview_cache_write( $book_id, $cache_key, $pdf, $meta ) {
	$dir = almaden_bookster_typst_preview_cache_dir( $book_id );
	if ( ! wp_mkdir_p( $dir ) ) {
		return false;
	}
	$pdf_path  = $dir . '/' . $cache_key . '.pdf';
	$meta_path = $dir . '/' . $cache_key . '.json';
	$tmp_pdf   = $pdf_path . '.' . wp_generate_uuid4() . '.tmp';
	$tmp_meta  = $meta_path . '.' . wp_generate_uuid4() . '.tmp';
	$pdf_written = false;
	$meta_written = false;
	if ( false !== file_put_contents( $tmp_pdf, $pdf, LOCK_EX ) ) {
		$pdf_written = @rename( $tmp_pdf, $pdf_path );
	}
	if ( false !== file_put_contents( $tmp_meta, wp_json_encode( $meta ), LOCK_EX ) ) {
		$meta_written = @rename( $tmp_meta, $meta_path );
	}
	@unlink( $tmp_pdf );
	@unlink( $tmp_meta );

	$entries = glob( $dir . '/*.pdf' );
	if ( ! is_array( $entries ) || count( $entries ) <= 54 ) {
		return $pdf_written && $meta_written;
	}
	$groups = array();
	foreach ( $entries as $entry ) {
		$entry_meta_path = substr( $entry, 0, -4 ) . '.json';
		$entry_meta = is_file( $entry_meta_path )
			? json_decode( (string) file_get_contents( $entry_meta_path ), true )
			: array();
		$scope = almaden_bookster_typst_preview_cache_scope( is_array( $entry_meta ) ? $entry_meta : array() );
		if ( ! isset( $groups[ $scope ] ) ) {
			$groups[ $scope ] = array();
		}
		$groups[ $scope ][] = $entry;
	}
	foreach ( $groups as $scope => $scope_entries ) {
		usort( $scope_entries, static function ( $left, $right ) {
			return ( is_file( $right ) ? (int) @filemtime( $right ) : 0 ) <=> ( is_file( $left ) ? (int) @filemtime( $left ) : 0 );
		} );
		foreach ( array_slice( $scope_entries, almaden_bookster_typst_preview_cache_scope_limit( $scope ) ) as $stale_pdf ) {
			@unlink( $stale_pdf );
			@unlink( substr( $stale_pdf, 0, -4 ) . '.json' );
		}
	}

	return $pdf_written && $meta_written;
}

function almaden_bookster_typst_preview_metadata() {
	return array(
		'renderer_version'      => ALMADEN_BOOKSTER_TYPST_PREVIEW_RENDERER_VERSION,
		'page_flow'             => $GLOBALS['almaden_bookster_typst_page_flow_map'] ?? array(),
		'page_template_results' => $GLOBALS['almaden_bookster_typst_page_template_results'] ?? array(),
		'page_template_asset_diagnostics' => $GLOBALS['almaden_bookster_typst_page_template_asset_diagnostics'] ?? array(),
		'page_template_asset_audit' => $GLOBALS['almaden_bookster_typst_page_template_asset_audit'] ?? array(),
		'universal_counter'     => $GLOBALS['almaden_bookster_typst_universal_counter'] ?? null,
		'image_blocks'          => $GLOBALS['almaden_bookster_typst_image_blocks'] ?? array(),
		'opening_debug'         => $GLOBALS['almaden_bookster_typst_opening_debug'] ?? null,
		'integrity_warning'     => (string) ( $GLOBALS['almaden_bookster_typst_integrity_warning'] ?? '' ),
		'preview_scope'         => (string) ( $GLOBALS['almaden_bookster_typst_preview_scope'] ?? 'full-book' ),
		'preview_fidelity'      => (string) ( $GLOBALS['almaden_bookster_typst_preview_fidelity'] ?? 'full-book' ),
		'performance'           => $GLOBALS['almaden_bookster_typst_preview_performance'] ?? array(),
	);
}

function almaden_bookster_typst_restore_preview_metadata( $meta ) {
	$GLOBALS['almaden_bookster_typst_page_flow_map']         = $meta['page_flow'] ?? array();
	$GLOBALS['almaden_bookster_typst_page_template_results'] = $meta['page_template_results'] ?? array();
	$GLOBALS['almaden_bookster_typst_page_template_asset_diagnostics'] = $meta['page_template_asset_diagnostics'] ?? array();
	$GLOBALS['almaden_bookster_typst_page_template_asset_audit'] = $meta['page_template_asset_audit'] ?? array();
	$GLOBALS['almaden_bookster_typst_universal_counter']     = $meta['universal_counter'] ?? null;
	$GLOBALS['almaden_bookster_typst_image_blocks']          = $meta['image_blocks'] ?? array();
	$GLOBALS['almaden_bookster_typst_opening_debug']         = $meta['opening_debug'] ?? null;
	$GLOBALS['almaden_bookster_typst_integrity_warning']     = (string) ( $meta['integrity_warning'] ?? '' );
	$GLOBALS['almaden_bookster_typst_preview_scope']         = (string) ( $meta['preview_scope'] ?? 'full-book' );
	$GLOBALS['almaden_bookster_typst_preview_fidelity']      = (string) ( $meta['preview_fidelity'] ?? 'full-book' );
}

function almaden_bookster_send_typst_preview_pdf( $book_id, $document, $pdf, $cache_status ) {
	$metadata = almaden_bookster_typst_preview_metadata();
	$metadata['geometry'] = $document['geometry'] ?? array();
	$metadata['typography'] = $document['typography'] ?? array();
	$metadata_json = wp_json_encode( $metadata );
	if ( false === $metadata_json ) {
		$metadata_json = '{}';
	}
	nocache_headers();
	header( 'Content-Type: application/vnd.almaden.typst-preview' );
	header( 'Content-Disposition: inline; filename="almaden-book-' . absint( $book_id ) . '.pdf"' );
	header( 'Content-Length: ' . ( strlen( $metadata_json ) + strlen( $pdf ) ) );
	header( 'X-Almaden-Typst-Cache: ' . $cache_status );
	header( 'X-Almaden-Source-Hash: ' . $document['source_hash'] );
	header( 'X-Almaden-Metadata-Length: ' . strlen( $metadata_json ) );
	echo $metadata_json . $pdf;
	exit;
}

function almaden_bookster_ajax_compile_typst_pdf() {
	$request_started_at = almaden_bookster_typst_perf_now();
	$GLOBALS['almaden_bookster_typst_preview_performance'] = array(
		'version' => 1,
	);
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( ! $book_id || ! check_ajax_referer( 'almaden_save_book_nonce_' . $book_id, 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'La sesión de edición expiró.' ), 403 );
	}
	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para compilar este libro.' ), 403 );
	}

	$json = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	almaden_bookster_typst_perf_set( 'payload_bytes', strlen( $json ) );
	if ( strlen( $json ) > 8 * MB_IN_BYTES ) {
		wp_send_json_error( array( 'message' => 'El manuscrito excede el límite de compilación.' ), 413 );
	}
	$decode_started_at = almaden_bookster_typst_perf_now();
	$payload = json_decode( $json, true );
	almaden_bookster_typst_perf_add_timing( 'payload_decode', $decode_started_at );
	if ( ! is_array( $payload ) || empty( $payload['chapters'] ) || ! is_array( $payload['chapters'] ) ) {
		wp_send_json_error( array( 'message' => 'El manuscrito enviado no es válido.' ), 400 );
	}
	$preview = isset( $payload['preview'] ) && is_array( $payload['preview'] )
		? $payload['preview']
		: array();
	$preview_scope = isset( $preview['scope'] ) ? sanitize_key( (string) $preview['scope'] ) : 'full-book';
	$is_chapter_fragment = 'chapter-fragment' === $preview_scope;
	$GLOBALS['almaden_bookster_typst_preview_scope'] = $is_chapter_fragment ? 'chapter-fragment' : 'full-book';
	$GLOBALS['almaden_bookster_typst_preview_fidelity'] = $is_chapter_fragment ? 'fast-preview' : 'full-book';
	almaden_bookster_typst_perf_set( 'preview_scope', $GLOBALS['almaden_bookster_typst_preview_scope'] );
	almaden_bookster_typst_perf_set( 'chapter_count_received', count( $payload['chapters'] ) );
	$payload['settings'] = isset( $payload['settings'] ) && is_array( $payload['settings'] )
		? $payload['settings']
		: array();
	$hydrate_started_at = almaden_bookster_typst_perf_now();
	$payload['settings'] = almaden_bookster_typst_hydrate_persisted_layout_settings(
		$book_id,
		$payload['settings']
	);
	almaden_bookster_typst_perf_add_timing( 'settings_hydrate', $hydrate_started_at );
	if ( $is_chapter_fragment ) {
		$payload['settings']['page_templates'] = array();
		$payload['settings']['page_styles'] = array();
		almaden_bookster_typst_perf_set( 'layout_hydration_scope', 'skipped-for-chapter-fragment' );
	}

	$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
	if ( ! is_array( $cover_settings ) && '' === trim( (string) $cover_settings ) ) {
		$cover_settings = array();
	}
	$payload['coverSettings'] = $cover_settings;
	$payload['cover_settings'] = $cover_settings;

	$payload['title'] = isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : '';
	$payload['chapters'] = array_slice( $payload['chapters'], 0, 500 );
	$sanitize_started_at = almaden_bookster_typst_perf_now();
	$content_bytes = 0;
	foreach ( $payload['chapters'] as &$chapter ) {
		if ( ! is_array( $chapter ) ) {
			$chapter = array();
			continue;
		}
		$chapter['title']   = isset( $chapter['title'] ) ? sanitize_text_field( $chapter['title'] ) : '';
		$chapter['content'] = isset( $chapter['content'] )
			? str_replace( "\0", '', substr( (string) $chapter['content'], 0, 2 * MB_IN_BYTES ) )
			: '';
		$content_bytes += strlen( (string) $chapter['content'] );
	}
	unset( $chapter );
	almaden_bookster_typst_perf_add_timing( 'chapters_sanitize', $sanitize_started_at );
	almaden_bookster_typst_perf_set( 'chapter_count_compiled', count( $payload['chapters'] ) );
	almaden_bookster_typst_perf_set( 'chapter_content_bytes', $content_bytes );

	$build_started_at = almaden_bookster_typst_perf_now();
	$document  = almaden_bookster_build_typst_document( $payload );
	almaden_bookster_typst_perf_add_timing( 'document_build', $build_started_at );
	almaden_bookster_typst_perf_set(
		'document',
		array(
			'source_bytes'          => strlen( (string) ( $document['source'] ?? '' ) ),
			'semantic_text_bytes'   => strlen( (string) ( $document['semantic_text'] ?? '' ) ),
			'semantic_extra_count'  => count( (array) ( $document['semantic_extras'] ?? array() ) ),
			'asset_count'           => count( (array) ( $document['assets'] ?? array() ) ),
			'font_asset_count'      => count( (array) ( $document['font_assets'] ?? array() ) ),
			'page_template_count'   => count( (array) ( $document['page_templates'] ?? array() ) ),
			'page_style_count'      => count( (array) ( $document['page_styles'] ?? array() ) ),
		)
	);
	$cache_key_started_at = almaden_bookster_typst_perf_now();
	$cache_key = almaden_bookster_typst_preview_cache_key( $document );
	almaden_bookster_typst_perf_add_timing( 'server_cache_key', $cache_key_started_at );
	$cache_read_started_at = almaden_bookster_typst_perf_now();
	$cached    = almaden_bookster_typst_preview_cache_read( $book_id, $cache_key );
	almaden_bookster_typst_perf_add_timing( 'server_cache_read', $cache_read_started_at );
	if ( is_array( $cached ) ) {
		almaden_bookster_typst_restore_preview_metadata( $cached['meta'] );
		almaden_bookster_typst_perf_set( 'server_cache_status', 'HIT' );
		almaden_bookster_typst_perf_set( 'pdf_bytes', strlen( (string) $cached['pdf'] ) );
		if ( function_exists( 'almaden_bookster_typst_reconcile_page_template_results' ) ) {
			$reconcile_started_at = almaden_bookster_typst_perf_now();
			almaden_bookster_typst_reconcile_page_template_results(
				$book_id,
				$GLOBALS['almaden_bookster_typst_page_template_results'] ?? array()
			);
			almaden_bookster_typst_perf_add_timing( 'page_template_reconcile', $reconcile_started_at );
		}
		almaden_bookster_typst_perf_add_timing( 'request_total', $request_started_at );
		almaden_bookster_send_typst_preview_pdf( $book_id, $document, $cached['pdf'], 'HIT' );
	}
	almaden_bookster_typst_perf_set( 'server_cache_status', 'MISS' );
	$compile_started_at = almaden_bookster_typst_perf_now();
	$pdf      = almaden_bookster_compile_typst_pdf( $document );
	almaden_bookster_typst_perf_add_timing( 'compile_total', $compile_started_at );
	if ( is_wp_error( $pdf ) ) {
		wp_send_json_error(
			array(
				'message' => $pdf->get_error_message(),
				'code'    => $pdf->get_error_code(),
			),
			500
		);
	}
	if ( function_exists( 'almaden_bookster_typst_reconcile_page_template_results' ) ) {
		$reconcile_started_at = almaden_bookster_typst_perf_now();
		almaden_bookster_typst_reconcile_page_template_results(
			$book_id,
			$GLOBALS['almaden_bookster_typst_page_template_results'] ?? array()
		);
		almaden_bookster_typst_perf_add_timing( 'page_template_reconcile', $reconcile_started_at );
	}

	$cache_write_started_at = almaden_bookster_typst_perf_now();
	$cache_written = almaden_bookster_typst_preview_cache_write(
		$book_id,
		$cache_key,
		$pdf,
		almaden_bookster_typst_preview_metadata()
	);
	almaden_bookster_typst_perf_add_timing( 'server_cache_write', $cache_write_started_at );
	almaden_bookster_typst_perf_set( 'server_cache_status', $cache_written ? 'MISS-STORED' : 'MISS-NOSTORE' );
	almaden_bookster_typst_perf_set( 'pdf_bytes', strlen( (string) $pdf ) );
	almaden_bookster_typst_perf_add_timing( 'request_total', $request_started_at );
	almaden_bookster_send_typst_preview_pdf( $book_id, $document, $pdf, $cache_written ? 'MISS-STORED' : 'MISS-NOSTORE' );
}
