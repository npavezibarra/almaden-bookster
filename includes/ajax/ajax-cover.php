<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_normalize_cover_image_url( $url ) {
	$url = esc_url_raw( $url );
	if ( empty( $url ) ) {
		return '';
	}

	$attachment_id = attachment_url_to_postid( $url );
	if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', $url ) ) {
		$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', $url ) );
	}
	if ( $attachment_id > 0 && function_exists( 'wp_get_original_image_url' ) ) {
		$original_url = wp_get_original_image_url( $attachment_id );
		if ( ! empty( $original_url ) ) {
			return esc_url_raw( $original_url );
		}
	}

	return $url;
}

function almaden_bookster_resolve_cover_image_path_from_url( $url ) {
	$url = esc_url_raw( $url );
	if ( empty( $url ) ) {
		return '';
	}

	$attachment_id = attachment_url_to_postid( $url );
	if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', $url ) ) {
		$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', $url ) );
	}

	if ( $attachment_id > 0 ) {
		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$original_path = wp_get_original_image_path( $attachment_id );
			if ( ! empty( $original_path ) && file_exists( $original_path ) ) {
				return $original_path;
			}
		}

		$file_path = get_attached_file( $attachment_id );
		if ( ! empty( $file_path ) && file_exists( $file_path ) ) {
			return $file_path;
		}
	}

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['baseurl'] ) && strpos( $url, $uploads['baseurl'] ) === 0 ) {
		$relative = str_replace( $uploads['baseurl'], '', $url );
		$file_path = $uploads['basedir'] . $relative;
		if ( file_exists( $file_path ) ) {
			return $file_path;
		}
	}

	return '';
}

function almaden_bookster_is_cmyk_jpeg_path( $file_path ) {
	$file_path = is_string( $file_path ) ? $file_path : '';
	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		return false;
	}

	$image_size = @getimagesize( $file_path );
	return ! empty( $image_size['mime'] ) && 'image/jpeg' === $image_size['mime'] && ! empty( $image_size['channels'] ) && 4 === (int) $image_size['channels'];
}

function almaden_bookster_get_cover_image_original_url_from_attachment( $attachment_id ) {
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

function almaden_bookster_get_cover_image_attachment_id_from_settings( $cover_settings, $key ) {
	$cover_settings = is_array( $cover_settings ) ? $cover_settings : array();
	$attachment_key = $key . '_attachment_id';

	if ( ! empty( $cover_settings[ $attachment_key ] ) ) {
		return absint( $cover_settings[ $attachment_key ] );
	}

	if ( empty( $cover_settings[ $key ] ) ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( esc_url_raw( $cover_settings[ $key ] ) );
	if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', (string) $cover_settings[ $key ] ) ) {
		$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', (string) $cover_settings[ $key ] ) );
	}

	return absint( $attachment_id );
}

function almaden_bookster_get_cover_image_upload_url_from_path( $file_path ) {
	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) || strpos( $file_path, $uploads['basedir'] ) !== 0 ) {
		return '';
	}

	return esc_url_raw( str_replace( $uploads['basedir'], $uploads['baseurl'], $file_path ) );
}

function almaden_bookster_create_cover_srgb_preview( $source_path, $preview_path ) {
	return false;
}

function almaden_bookster_get_cover_image_srgb_preview_url( $source_path ) {
	if ( empty( $source_path ) || ! file_exists( $source_path ) ) {
		return '';
	}

	$is_cmyk_jpeg = almaden_bookster_is_cmyk_jpeg_path( $source_path );
	if ( ! $is_cmyk_jpeg ) {
		return '';
	}

	$preview_path = trailingslashit( dirname( $source_path ) ) . pathinfo( $source_path, PATHINFO_FILENAME ) . '-almaden-screen-preview-rgb-v2.jpg';
	if ( file_exists( $preview_path ) ) {
		$preview_mtime = @filemtime( $preview_path );
		$source_mtime  = @filemtime( $source_path );
		if ( $preview_mtime && $source_mtime && $preview_mtime >= $source_mtime ) {
			return almaden_bookster_get_cover_image_upload_url_from_path( $preview_path );
		}
	}

	return file_exists( $preview_path ) ? almaden_bookster_get_cover_image_upload_url_from_path( $preview_path ) : '';
}

function almaden_bookster_get_cover_image_preview_url_from_attachment( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return '';
	}

	// The browser must never decode the print original. This rendition is made
	// with ColorSync, preserving CMYK appearance while converting only the
	// screen asset to sRGB.
	$preview_url = function_exists( 'almaden_bookster_get_cover_screen_preview_url' )
		? almaden_bookster_get_cover_screen_preview_url( $attachment_id )
		: '';

	if ( empty( $preview_url ) && function_exists( 'almaden_bookster_generate_cover_screen_preview' ) ) {
		$preview_url = almaden_bookster_generate_cover_screen_preview( $attachment_id );
	}

	return esc_url_raw( $preview_url );
}

function almaden_bookster_get_cover_image_preview_url_from_url( $image_url ) {
	$image_url = esc_url_raw( $image_url );
	if ( empty( $image_url ) ) {
		return '';
	}

	$attachment_id = attachment_url_to_postid( $image_url );
	if ( $attachment_id > 0 ) {
		$preview_url = almaden_bookster_get_cover_image_preview_url_from_attachment( $attachment_id );
		if ( ! empty( $preview_url ) ) {
			return $preview_url;
		}
	}

	// Older covers can lack an attachment ID. Reuse a WordPress derivative beside
	// the original instead of making the browser download the full-size upload.
	$file_path = almaden_bookster_resolve_cover_image_path_from_url( $image_url );
	if ( empty( $file_path ) ) {
		return '';
	}

	$is_cmyk_jpeg = almaden_bookster_is_cmyk_jpeg_path( $file_path );
	if ( $is_cmyk_jpeg ) {
		$preview_path = trailingslashit( dirname( $file_path ) ) . pathinfo( $file_path, PATHINFO_FILENAME ) . '-almaden-screen-preview-v3.jpg';
		if ( file_exists( $preview_path ) ) {
			return almaden_bookster_get_cover_image_upload_url_from_path( $preview_path );
		}

		return '';
	}

	$extension = pathinfo( $file_path, PATHINFO_EXTENSION );
	$base_name = pathinfo( $file_path, PATHINFO_FILENAME );
	$candidates = glob( trailingslashit( dirname( $file_path ) ) . $base_name . '-*x*.' . $extension );
		$best_path = '';
		$best_area = 0;

		if ( is_array( $candidates ) ) {
			foreach ( $candidates as $candidate ) {
				$size = @getimagesize( $candidate );
				if ( empty( $size[0] ) || empty( $size[1] ) || max( $size[0], $size[1] ) > 1600 ) {
					continue;
				}

				if ( almaden_bookster_is_cmyk_jpeg_path( $candidate ) ) {
					continue;
				}

				$area = $size[0] * $size[1];
				if ( $area > $best_area ) {
					$best_area = $area;
					$best_path = $candidate;
			}
		}
	}

	if ( empty( $best_path ) ) {
		return '';
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) || strpos( $best_path, $uploads['basedir'] ) !== 0 ) {
		return '';
	}

	return esc_url_raw( str_replace( $uploads['basedir'], $uploads['baseurl'], $best_path ) );
}

function almaden_bookster_get_cover_image_preview_payload( $attachment_id, $image_url = '' ) {
	$attachment_id = absint( $attachment_id );
	$image_url = esc_url_raw( $image_url );
	$source_path = '';
	$preview_safe = true;

	if ( $attachment_id > 0 && get_post_type( $attachment_id ) === 'attachment' ) {
		$image_url = almaden_bookster_get_cover_image_original_url_from_attachment( $attachment_id );
		$source_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : get_attached_file( $attachment_id );
	}

	$preview_url = '';
	if ( $attachment_id > 0 ) {
		$preview_url = almaden_bookster_get_cover_image_preview_url_from_attachment( $attachment_id );
	}

	if ( empty( $preview_url ) && ! empty( $image_url ) ) {
		$preview_url = almaden_bookster_get_cover_image_preview_url_from_url( $image_url );
	}


	// A missing rendition is never permission to load the print original.
	$preview_safe = ! empty( $preview_url );

	return array(
		'attachmentId' => $attachment_id,
		'originalUrl'   => $image_url,
		'previewUrl'    => $preview_url,
		'previewSafe'   => $preview_safe,
	);
}

function almaden_bookster_get_cover_image_preview_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'ID de libro inválido.' ), 400 );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_cover_nonce_' . $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Validación de seguridad fallida.' ), 403 );
	}

	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para procesar esta imagen.' ), 403 );
	}

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$image_url = isset( $_POST['image_url'] ) ? almaden_bookster_normalize_cover_image_url( $_POST['image_url'] ) : '';

	if ( $attachment_id <= 0 && empty( $image_url ) ) {
		wp_send_json_error( array( 'message' => 'No se recibió una imagen válida.' ), 400 );
	}

	$payload = almaden_bookster_get_cover_image_preview_payload( $attachment_id, $image_url );
	wp_send_json_success( $payload );
}

function almaden_bookster_prepare_cover_settings_for_editor( $cover_settings ) {
	$cover_settings = is_array( $cover_settings ) ? $cover_settings : array();
	$image_keys = array( 'front_image', 'back_image', 'spread_image', 'spine_image', 'front_flap_image', 'back_flap_image' );

	foreach ( $image_keys as $key ) {
		$original_key = $key . '_original_url';
		$preview_key = $key . '_preview_url';
		$preview_safe_key = $key . '_preview_safe';
		$attachment_key = $key . '_attachment_id';

		$original_url = ! empty( $cover_settings[ $key ] ) ? esc_url_raw( $cover_settings[ $key ] ) : '';
		$attachment_id = almaden_bookster_get_cover_image_attachment_id_from_settings( $cover_settings, $key );
		$source_path = '';

		if ( $attachment_id > 0 ) {
			$resolved_original = '';
			if ( function_exists( 'wp_get_original_image_url' ) ) {
				$resolved_original = wp_get_original_image_url( $attachment_id );
			}
			if ( empty( $resolved_original ) && function_exists( 'wp_get_attachment_url' ) ) {
				$resolved_original = wp_get_attachment_url( $attachment_id );
			}
			if ( ! empty( $resolved_original ) ) {
				$original_url = esc_url_raw( $resolved_original );
			}
			$source_path = function_exists( 'wp_get_original_image_path' ) ? wp_get_original_image_path( $attachment_id ) : get_attached_file( $attachment_id );
		} elseif ( ! empty( $original_url ) ) {
			$source_path = almaden_bookster_resolve_cover_image_path_from_url( $original_url );
		}

		$preview_url = $attachment_id > 0 ? almaden_bookster_get_cover_image_preview_url_from_attachment( $attachment_id ) : '';
		if ( empty( $preview_url ) ) {
			$preview_url = almaden_bookster_get_cover_image_preview_url_from_url( $original_url );
		}

		// The original is retained in settings for print export only. The canvas
		// receives a dedicated, lightweight sRGB preview or no image at all.
		$preview_safe = ! empty( $preview_url );
		$cover_settings[ $attachment_key ] = $attachment_id;
		$cover_settings[ $original_key ] = $original_url;
		$cover_settings[ $preview_key ] = $preview_url;
		$cover_settings[ $preview_safe_key ] = $preview_safe;
	}

	return $cover_settings;
}

function almaden_bookster_get_cover_image_diagnostics() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'ID de libro inválido.' ), 400 );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_cover_nonce_' . $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Validación de seguridad fallida.' ), 403 );
	}

	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para evaluar esta imagen.' ), 403 );
	}

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
	$image_url = isset( $_POST['image_url'] ) ? almaden_bookster_normalize_cover_image_url( $_POST['image_url'] ) : '';
	$target_width_cm = isset( $_POST['target_width_cm'] ) ? floatval( $_POST['target_width_cm'] ) : 0;
	$target_height_cm = isset( $_POST['target_height_cm'] ) ? floatval( $_POST['target_height_cm'] ) : 0;
	$required_dpi = 300;

	if ( $attachment_id > 0 && get_post_type( $attachment_id ) === 'attachment' ) {
		if ( function_exists( 'wp_get_original_image_url' ) ) {
			$image_url = wp_get_original_image_url( $attachment_id );
		}
		if ( empty( $image_url ) ) {
			$image_url = wp_get_attachment_url( $attachment_id );
		}
	}

	if ( empty( $image_url ) ) {
		wp_send_json_success( array(
			'has_image' => false,
		) );
	}

	$file_path = $attachment_id > 0 && function_exists( 'wp_get_original_image_path' )
		? wp_get_original_image_path( $attachment_id )
		: '';
	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		$file_path = almaden_bookster_resolve_cover_image_path_from_url( $image_url );
	}
	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		wp_send_json_error( array( 'message' => 'No se pudo resolver el archivo de imagen.' ), 404 );
	}

	$image_size = @getimagesize( $file_path );
	if ( empty( $image_size[0] ) || empty( $image_size[1] ) ) {
		wp_send_json_error( array( 'message' => 'No se pudo leer el tamaño de la imagen.' ), 500 );
	}

	$width_px = intval( $image_size[0] );
	$height_px = intval( $image_size[1] );
	$image_mime = ! empty( $image_size['mime'] ) ? $image_size['mime'] : '';
	$image_ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
	$file_bytes = @filesize( $file_path );
	$file_mb = $file_bytes ? round( $file_bytes / 1048576, 2 ) : 0;

	$min_width_px = $target_width_cm > 0 ? (int) ceil( ( $target_width_cm / 2.54 ) * $required_dpi ) : 0;
	$min_height_px = $target_height_cm > 0 ? (int) ceil( ( $target_height_cm / 2.54 ) * $required_dpi ) : 0;

	$effective_dpi_x = $target_width_cm > 0 ? round( $width_px / ( $target_width_cm / 2.54 ), 2 ) : 0;
	$effective_dpi_y = $target_height_cm > 0 ? round( $height_px / ( $target_height_cm / 2.54 ), 2 ) : 0;

	$physical_width_cm_at_300 = round( ( $width_px / $required_dpi ) * 2.54, 2 );
	$physical_height_cm_at_300 = round( ( $height_px / $required_dpi ) * 2.54, 2 );
	$target_aspect_ratio = $target_height_cm > 0 ? round( $target_width_cm / $target_height_cm, 4 ) : 0;
	$image_aspect_ratio = $height_px > 0 ? round( $width_px / $height_px, 4 ) : 0;
	$aspect_ratio_diff_pct = ( $target_aspect_ratio > 0 && $image_aspect_ratio > 0 )
		? round( abs( $image_aspect_ratio - $target_aspect_ratio ) / $target_aspect_ratio * 100, 2 )
		: 0;

	$is_original_source = true;
	$normalized_basename = basename( $file_path );
	if ( strpos( $normalized_basename, '-scaled.' ) !== false ) {
		$is_original_source = false;
	}

	$format_is_print_friendly = in_array( $image_ext, array( 'jpg', 'jpeg', 'png', 'tif', 'tiff' ), true );

	$issues = array();
	if ( $min_width_px > 0 && $width_px < $min_width_px ) {
		$issues[] = 'ancho insuficiente';
	}
	if ( $min_height_px > 0 && $height_px < $min_height_px ) {
		$issues[] = 'alto insuficiente';
	}
	if ( $aspect_ratio_diff_pct > 2 ) {
		$issues[] = 'relación de aspecto distinta al formato';
	}
	if ( ! $is_original_source ) {
		$issues[] = 'se está usando una versión escalada';
	}
	if ( ! $format_is_print_friendly ) {
		$issues[] = 'formato de archivo no ideal para imprenta';
	}

	$meets = empty( $issues );

	wp_send_json_success( array(
		'has_image' => true,
		'image_url' => $image_url,
		'file_path' => $file_path,
		'file_name' => basename( $file_path ),
		'width_px' => $width_px,
		'height_px' => $height_px,
		'file_size_mb' => $file_mb,
		'image_mime' => $image_mime,
		'image_ext' => $image_ext,
		'min_width_px' => $min_width_px,
		'min_height_px' => $min_height_px,
		'effective_dpi_x' => $effective_dpi_x,
		'effective_dpi_y' => $effective_dpi_y,
		'required_dpi' => $required_dpi,
		'physical_width_cm_at_300' => $physical_width_cm_at_300,
		'physical_height_cm_at_300' => $physical_height_cm_at_300,
		'target_width_cm' => $target_width_cm,
		'target_height_cm' => $target_height_cm,
		'target_aspect_ratio' => $target_aspect_ratio,
		'image_aspect_ratio' => $image_aspect_ratio,
		'aspect_ratio_diff_pct' => $aspect_ratio_diff_pct,
		'is_original_source' => $is_original_source,
		'format_is_print_friendly' => $format_is_print_friendly,
		'meets_requirements' => $meets,
		'issues' => $issues,
	) );
}

function almaden_bookster_save_cover_ajax() {
	@set_time_limit( 120 );
	if ( function_exists( 'ignore_user_abort' ) ) {
		ignore_user_abort( true );
	}
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_cover_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$cover_data = array(
		'paper_type'   => isset($_POST['paper_type']) ? sanitize_text_field($_POST['paper_type']) : '',
		'spine_width_mode' => (isset($_POST['spine_width_mode']) && $_POST['spine_width_mode'] === 'manual') ? 'manual' : 'auto',
		'spine_width_mm' => isset($_POST['spine_width_mm']) ? max( 0, (int) ceil( floatval($_POST['spine_width_mm']) ) ) : 0,
		'front_flap'   => isset($_POST['front_flap']) ? max( 0, (int) ceil( floatval($_POST['front_flap']) ) ) : 0,
		'back_flap'    => isset($_POST['back_flap']) ? max( 0, (int) ceil( floatval($_POST['back_flap']) ) ) : 0,
		'front_image'  => isset($_POST['front_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['front_image'] ) : '',
		'front_image_attachment_id' => isset($_POST['front_image_attachment_id']) ? absint( $_POST['front_image_attachment_id'] ) : 0,
		'back_image'   => isset($_POST['back_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['back_image'] ) : '',
		'back_image_attachment_id' => isset($_POST['back_image_attachment_id']) ? absint( $_POST['back_image_attachment_id'] ) : 0,
		'spread_image' => isset($_POST['spread_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['spread_image'] ) : '',
		'spread_image_attachment_id' => isset($_POST['spread_image_attachment_id']) ? absint( $_POST['spread_image_attachment_id'] ) : 0,
		'spine_image'  => isset($_POST['spine_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['spine_image'] ) : '',
		'spine_image_attachment_id' => isset($_POST['spine_image_attachment_id']) ? absint( $_POST['spine_image_attachment_id'] ) : 0,
		'spine_color'  => isset($_POST['spine_color']) ? sanitize_hex_color($_POST['spine_color']) : '',
		'front_flap_width' => isset($_POST['front_flap_width']) ? max( 0, (int) ceil( floatval($_POST['front_flap_width']) ) ) : 0,
		'back_flap_width'  => isset($_POST['back_flap_width']) ? max( 0, (int) ceil( floatval($_POST['back_flap_width']) ) ) : 0,
		'fold_x'       => isset($_POST['fold_x']) ? max( 0, (int) ceil( floatval($_POST['fold_x']) ) ) : ( isset( $_POST['fold_x_mm'] ) ? max( 0, (int) ceil( floatval( $_POST['fold_x_mm'] ) ) ) : 0 ),
		'front_flap_image' => isset($_POST['front_flap_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['front_flap_image'] ) : '',
		'front_flap_image_attachment_id' => isset($_POST['front_flap_image_attachment_id']) ? absint( $_POST['front_flap_image_attachment_id'] ) : 0,
		'front_flap_color' => isset($_POST['front_flap_color']) ? sanitize_hex_color($_POST['front_flap_color']) : '',
		'back_flap_image'  => isset($_POST['back_flap_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['back_flap_image'] ) : '',
		'back_flap_image_attachment_id' => isset($_POST['back_flap_image_attachment_id']) ? absint( $_POST['back_flap_image_attachment_id'] ) : 0,
		'back_flap_color'  => isset($_POST['back_flap_color']) ? sanitize_hex_color($_POST['back_flap_color']) : '',
		'text_layers'  => isset($_POST['text_layers']) ? json_decode(stripslashes($_POST['text_layers']), true) : array(),
	);

	update_post_meta( $book_id, '_almaden_cover_settings', $cover_data );

	$snapshot_result = null;
	if ( function_exists( 'almaden_bookster_generate_cover_thumbnail_snapshot' ) ) {
		$snapshot_result = almaden_bookster_generate_cover_thumbnail_snapshot( $book_id );
	}

	$response = array(
		'message' => 'Configuración de portada guardada con éxito.',
	);

	if ( is_array( $snapshot_result ) ) {
		$response['snapshot'] = $snapshot_result;
	} elseif ( is_wp_error( $snapshot_result ) ) {
		$response['snapshot_error'] = $snapshot_result->get_error_message();
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_almaden_get_cover_image_diagnostics', 'almaden_bookster_get_cover_image_diagnostics' );
add_action( 'wp_ajax_nopriv_almaden_get_cover_image_diagnostics', 'almaden_bookster_get_cover_image_diagnostics' );
add_action( 'wp_ajax_almaden_get_cover_image_preview', 'almaden_bookster_get_cover_image_preview_ajax' );
add_action( 'wp_ajax_nopriv_almaden_get_cover_image_preview', 'almaden_bookster_get_cover_image_preview_ajax' );
