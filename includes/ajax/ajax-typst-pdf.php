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
	define( 'ALMADEN_BOOKSTER_TYPST_PREVIEW_RENDERER_VERSION', '11' );
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
	if ( ! is_array( $entries ) || count( $entries ) <= 12 ) {
		return $pdf_written && $meta_written;
	}
	usort( $entries, static function ( $left, $right ) {
		return ( is_file( $right ) ? (int) @filemtime( $right ) : 0 ) <=> ( is_file( $left ) ? (int) @filemtime( $left ) : 0 );
	} );
	foreach ( array_slice( $entries, 12 ) as $stale_pdf ) {
		@unlink( $stale_pdf );
		@unlink( substr( $stale_pdf, 0, -4 ) . '.json' );
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
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( ! $book_id || ! check_ajax_referer( 'almaden_save_book_nonce_' . $book_id, 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'La sesión de edición expiró.' ), 403 );
	}
	if ( ! current_user_can( 'edit_post', $book_id ) ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para compilar este libro.' ), 403 );
	}

	$json = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : '';
	if ( strlen( $json ) > 8 * MB_IN_BYTES ) {
		wp_send_json_error( array( 'message' => 'El manuscrito excede el límite de compilación.' ), 413 );
	}
	$payload = json_decode( $json, true );
	if ( ! is_array( $payload ) || empty( $payload['chapters'] ) || ! is_array( $payload['chapters'] ) ) {
		wp_send_json_error( array( 'message' => 'El manuscrito enviado no es válido.' ), 400 );
	}
	$payload['settings'] = isset( $payload['settings'] ) && is_array( $payload['settings'] )
		? $payload['settings']
		: array();
	$payload['settings'] = almaden_bookster_typst_hydrate_persisted_layout_settings(
		$book_id,
		$payload['settings']
	);

	$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
	if ( ! is_array( $cover_settings ) && '' === trim( (string) $cover_settings ) ) {
		$cover_settings = array();
	}
	$payload['coverSettings'] = $cover_settings;
	$payload['cover_settings'] = $cover_settings;

	$payload['title'] = isset( $payload['title'] ) ? sanitize_text_field( $payload['title'] ) : '';
	$payload['chapters'] = array_slice( $payload['chapters'], 0, 500 );
	foreach ( $payload['chapters'] as &$chapter ) {
		if ( ! is_array( $chapter ) ) {
			$chapter = array();
			continue;
		}
		$chapter['title']   = isset( $chapter['title'] ) ? sanitize_text_field( $chapter['title'] ) : '';
		$chapter['content'] = isset( $chapter['content'] )
			? str_replace( "\0", '', substr( (string) $chapter['content'], 0, 2 * MB_IN_BYTES ) )
			: '';
	}
	unset( $chapter );

	$document  = almaden_bookster_build_typst_document( $payload );
	$cache_key = almaden_bookster_typst_preview_cache_key( $document );
	$cached    = almaden_bookster_typst_preview_cache_read( $book_id, $cache_key );
	if ( is_array( $cached ) ) {
		almaden_bookster_typst_restore_preview_metadata( $cached['meta'] );
		if ( function_exists( 'almaden_bookster_typst_reconcile_page_template_results' ) ) {
			almaden_bookster_typst_reconcile_page_template_results(
				$book_id,
				$GLOBALS['almaden_bookster_typst_page_template_results'] ?? array()
			);
		}
		almaden_bookster_send_typst_preview_pdf( $book_id, $document, $cached['pdf'], 'HIT' );
	}
	$pdf      = almaden_bookster_compile_typst_pdf( $document );
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
		almaden_bookster_typst_reconcile_page_template_results(
			$book_id,
			$GLOBALS['almaden_bookster_typst_page_template_results'] ?? array()
		);
	}

	$cache_written = almaden_bookster_typst_preview_cache_write(
		$book_id,
		$cache_key,
		$pdf,
		almaden_bookster_typst_preview_metadata()
	);
	almaden_bookster_send_typst_preview_pdf( $book_id, $document, $pdf, $cache_written ? 'MISS-STORED' : 'MISS-NOSTORE' );
}
