<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_is_cover_editor_upload_request() {
	if ( ! function_exists( 'wp_doing_ajax' ) || ! wp_doing_ajax() ) {
		return false;
	}

	$action = isset( $_REQUEST['action'] ) ? sanitize_key( (string) wp_unslash( $_REQUEST['action'] ) ) : '';
	if ( ! in_array( $action, array( 'upload-attachment', 'async-upload' ), true ) ) {
		return false;
	}

	$post_id = isset( $_REQUEST['post_id'] ) ? absint( $_REQUEST['post_id'] ) : 0;
	if ( $post_id <= 0 ) {
		return false;
	}

	return 'almaden-books' === get_post_type( $post_id );
}

function almaden_bookster_disable_big_image_scaling_for_cover_uploads( $threshold ) {
	if ( almaden_bookster_is_cover_editor_upload_request() ) {
		return false;
	}

	return $threshold;
}

add_filter( 'big_image_size_threshold', 'almaden_bookster_disable_big_image_scaling_for_cover_uploads', 20 );

/**
 * Bookster creates its own colour-managed screen rendition. Avoid WordPress/GD
 * derivatives for these uploads: they are expensive for print files and can
 * misinterpret CMYK JPEGs.
 */
function almaden_bookster_skip_wordpress_image_sizes_for_cover_uploads( $sizes ) {
	return almaden_bookster_is_cover_editor_upload_request() ? array() : $sizes;
}

add_filter( 'intermediate_image_sizes_advanced', 'almaden_bookster_skip_wordpress_image_sizes_for_cover_uploads', 20 );

function almaden_bookster_get_cover_screen_preview_path( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$source_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : get_attached_file( $attachment_id );
	if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
		return '';
	}

	return trailingslashit( dirname( $source_path ) ) . pathinfo( $source_path, PATHINFO_FILENAME ) . '-almaden-screen-preview-v3.jpg';
}

function almaden_bookster_get_cover_screen_preview_url( $attachment_id ) {
	$preview_path = almaden_bookster_get_cover_screen_preview_path( $attachment_id );
	if ( empty( $preview_path ) || ! file_exists( $preview_path ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) || 0 !== strpos( $preview_path, $uploads['basedir'] ) ) {
		return '';
	}

	return esc_url_raw( str_replace( $uploads['basedir'], $uploads['baseurl'], $preview_path ) );
}

function almaden_bookster_generate_cover_screen_preview( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	$source_path = $attachment_id > 0 && function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : get_attached_file( $attachment_id );
	$preview_path = almaden_bookster_get_cover_screen_preview_path( $attachment_id );

	if ( empty( $source_path ) || empty( $preview_path ) || ! file_exists( $source_path ) || ! is_executable( '/usr/bin/sips' ) ) {
		return '';
	}

	$preview_mtime = file_exists( $preview_path ) ? @filemtime( $preview_path ) : 0;
	$source_mtime  = @filemtime( $source_path );
	if ( $preview_mtime && $source_mtime && $preview_mtime >= $source_mtime ) {
		return almaden_bookster_get_cover_screen_preview_url( $attachment_id );
	}

	// ColorSync reads the embedded CMYK profile and writes an sRGB rendition.
	$command = sprintf(
		'%s --matchTo %s --resampleHeightWidthMax 1800 --setProperty format jpeg --setProperty formatOptions 78 --out %s %s 2>&1',
		escapeshellcmd( '/usr/bin/sips' ),
		escapeshellarg( '/System/Library/ColorSync/Profiles/sRGB Profile.icc' ),
		escapeshellarg( $preview_path ),
		escapeshellarg( $source_path )
	);
	$status = 1;
	@exec( $command, $output, $status );

	if ( 0 !== $status || ! file_exists( $preview_path ) ) {
		return '';
	}

	$size = @getimagesize( $preview_path );
	if ( empty( $size[0] ) || empty( $size[1] ) || empty( $size['mime'] ) || 'image/jpeg' !== $size['mime'] ) {
		return '';
	}

	update_post_meta( $attachment_id, '_almaden_cover_screen_preview', wp_basename( $preview_path ) );
	return almaden_bookster_get_cover_screen_preview_url( $attachment_id );
}

/**
 * Generate once during upload so selecting a freshly uploaded cover is instant.
 */
function almaden_bookster_create_screen_preview_for_cover_upload( $metadata, $attachment_id ) {
	if ( ! almaden_bookster_is_cover_editor_upload_request() ) {
		return $metadata;
	}

	almaden_bookster_generate_cover_screen_preview( $attachment_id );
	return $metadata;
}

add_filter( 'wp_generate_attachment_metadata', 'almaden_bookster_create_screen_preview_for_cover_upload', 20, 2 );
