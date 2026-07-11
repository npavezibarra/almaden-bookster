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

	$image_url = isset( $_POST['image_url'] ) ? almaden_bookster_normalize_cover_image_url( $_POST['image_url'] ) : '';
	$target_width_cm = isset( $_POST['target_width_cm'] ) ? floatval( $_POST['target_width_cm'] ) : 0;
	$target_height_cm = isset( $_POST['target_height_cm'] ) ? floatval( $_POST['target_height_cm'] ) : 0;
	$required_dpi = 300;

	if ( empty( $image_url ) ) {
		wp_send_json_success( array(
			'has_image' => false,
		) );
	}

	$file_path = almaden_bookster_resolve_cover_image_path_from_url( $image_url );
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
		'back_image'   => isset($_POST['back_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['back_image'] ) : '',
		'spread_image' => isset($_POST['spread_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['spread_image'] ) : '',
		'spine_image'  => isset($_POST['spine_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['spine_image'] ) : '',
		'spine_color'  => isset($_POST['spine_color']) ? sanitize_hex_color($_POST['spine_color']) : '',
		'front_flap_width' => isset($_POST['front_flap_width']) ? max( 0, (int) ceil( floatval($_POST['front_flap_width']) ) ) : 0,
		'back_flap_width'  => isset($_POST['back_flap_width']) ? max( 0, (int) ceil( floatval($_POST['back_flap_width']) ) ) : 0,
		'fold_x'       => isset($_POST['fold_x']) ? max( 0, (int) ceil( floatval($_POST['fold_x']) ) ) : ( isset( $_POST['fold_x_mm'] ) ? max( 0, (int) ceil( floatval( $_POST['fold_x_mm'] ) ) ) : 0 ),
		'front_flap_image' => isset($_POST['front_flap_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['front_flap_image'] ) : '',
		'front_flap_color' => isset($_POST['front_flap_color']) ? sanitize_hex_color($_POST['front_flap_color']) : '',
		'back_flap_image'  => isset($_POST['back_flap_image']) ? almaden_bookster_normalize_cover_image_url( $_POST['back_flap_image'] ) : '',
		'back_flap_color'  => isset($_POST['back_flap_color']) ? sanitize_hex_color($_POST['back_flap_color']) : '',
		'text_layers'  => isset($_POST['text_layers']) ? json_decode(stripslashes($_POST['text_layers']), true) : array(),
	);

	update_post_meta( $book_id, '_almaden_cover_settings', $cover_data );

	wp_send_json_success( array( 'message' => 'Configuración de portada guardada con éxito.' ) );
}
add_action( 'wp_ajax_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_almaden_get_cover_image_diagnostics', 'almaden_bookster_get_cover_image_diagnostics' );
add_action( 'wp_ajax_nopriv_almaden_get_cover_image_diagnostics', 'almaden_bookster_get_cover_image_diagnostics' );
