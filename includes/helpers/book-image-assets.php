<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_is_book_image_upload_request() {
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

function almaden_bookster_is_book_image_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 || get_post_type( $attachment_id ) !== 'attachment' ) {
		return false;
	}

	$parent_id = wp_get_post_parent_id( $attachment_id );
	return $parent_id > 0 && 'almaden-books' === get_post_type( $parent_id );
}

function almaden_bookster_disable_big_image_scaling_for_book_uploads( $threshold ) {
	return almaden_bookster_is_book_image_upload_request() ? false : $threshold;
}

add_filter( 'big_image_size_threshold', 'almaden_bookster_disable_big_image_scaling_for_book_uploads', 20 );

function almaden_bookster_skip_wordpress_image_sizes_for_book_uploads( $sizes ) {
	return almaden_bookster_is_book_image_upload_request() ? array() : $sizes;
}

add_filter( 'intermediate_image_sizes_advanced', 'almaden_bookster_skip_wordpress_image_sizes_for_book_uploads', 20 );

function almaden_bookster_get_book_image_original_url_from_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	if ( function_exists( 'wp_get_original_image_url' ) ) {
		$original_url = wp_get_original_image_url( $attachment_id );
		if ( ! empty( $original_url ) ) {
			return esc_url_raw( $original_url );
		}
	}

	if ( function_exists( 'wp_get_attachment_url' ) ) {
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( ! empty( $attachment_url ) ) {
			return esc_url_raw( $attachment_url );
		}
	}

	return '';
}

function almaden_bookster_get_book_image_original_path_from_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$source_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : get_attached_file( $attachment_id );
	return ! empty( $source_path ) && file_exists( $source_path ) ? $source_path : '';
}

function almaden_bookster_get_book_image_preview_path( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$source_path = almaden_bookster_get_book_image_original_path_from_attachment( $attachment_id );
	if ( empty( $source_path ) ) {
		return '';
	}

	return trailingslashit( dirname( $source_path ) ) . pathinfo( $source_path, PATHINFO_FILENAME ) . '-almaden-screen-preview-v3.jpg';
}

function almaden_bookster_get_book_image_preview_url_from_path( $preview_path ) {
	$preview_path = is_string( $preview_path ) ? $preview_path : '';
	if ( empty( $preview_path ) || ! file_exists( $preview_path ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) || 0 !== strpos( $preview_path, $uploads['basedir'] ) ) {
		return '';
	}

	return esc_url_raw( str_replace( $uploads['basedir'], $uploads['baseurl'], $preview_path ) );
}

function almaden_bookster_generate_book_image_preview( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	$source_path = almaden_bookster_get_book_image_original_path_from_attachment( $attachment_id );
	$preview_path = almaden_bookster_get_book_image_preview_path( $attachment_id );

	if ( empty( $source_path ) || empty( $preview_path ) || ! is_executable( '/usr/bin/sips' ) ) {
		return '';
	}

	$preview_mtime = file_exists( $preview_path ) ? @filemtime( $preview_path ) : 0;
	$source_mtime = @filemtime( $source_path );
	if ( $preview_mtime && $source_mtime && $preview_mtime >= $source_mtime ) {
		return almaden_bookster_get_book_image_preview_url_from_path( $preview_path );
	}

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

	return almaden_bookster_get_book_image_preview_url_from_path( $preview_path );
}

function almaden_bookster_get_book_image_preview_url_from_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$preview_path = almaden_bookster_get_book_image_preview_path( $attachment_id );
	if ( file_exists( $preview_path ) ) {
		$preview_url = almaden_bookster_get_book_image_preview_url_from_path( $preview_path );
		if ( ! empty( $preview_url ) ) {
			return $preview_url;
		}
	}

	return almaden_bookster_generate_book_image_preview( $attachment_id );
}

function almaden_bookster_prepare_book_image_attachment_for_js( $response, $attachment, $meta ) {
	if ( ! is_object( $attachment ) || ! isset( $attachment->ID ) ) {
		return $response;
	}

	if ( ! almaden_bookster_is_book_image_attachment( $attachment->ID ) ) {
		return $response;
	}

	$original_url = almaden_bookster_get_book_image_original_url_from_attachment( $attachment->ID );
	$preview_url = almaden_bookster_get_book_image_preview_url_from_attachment( $attachment->ID );

	$response['originalImageURL'] = $original_url;
	$response['previewUrl'] = $preview_url;
	$response['previewSafe'] = ! empty( $preview_url );
	$response['almadenBookImage'] = true;

	return $response;
}

add_filter( 'wp_prepare_attachment_for_js', 'almaden_bookster_prepare_book_image_attachment_for_js', 20, 3 );

function almaden_bookster_generate_book_image_preview_on_upload( $metadata, $attachment_id ) {
	if ( almaden_bookster_is_book_image_upload_request() || almaden_bookster_is_book_image_attachment( $attachment_id ) ) {
		almaden_bookster_generate_book_image_preview( $attachment_id );
	}

	return $metadata;
}

add_filter( 'wp_generate_attachment_metadata', 'almaden_bookster_generate_book_image_preview_on_upload', 20, 2 );
