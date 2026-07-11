<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_action( 'admin_post_almaden_export_cover_pdf', 'almaden_bookster_handle_export_cover_pdf' );

/**
 * Resolve a local image URL into a data URI for self-contained PDF rendering.
 */
function almaden_bookster_cover_export_url_to_data_uri( $url ) {
	$file_path = almaden_bookster_resolve_image_file_path( $url );

	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		return '';
	}

	$filetype = wp_check_filetype( $file_path );
	$mime_type = ! empty( $filetype['type'] ) ? $filetype['type'] : mime_content_type( $file_path );
	if ( empty( $mime_type ) ) {
		$mime_type = 'application/octet-stream';
	}

	$contents = file_get_contents( $file_path );
	if ( false === $contents ) {
		return '';
	}

	return 'data:' . $mime_type . ';base64,' . base64_encode( $contents );
}

/**
 * Handle CMYK PDF export for the cover editor.
 */
function almaden_bookster_handle_export_cover_pdf() {
	@set_time_limit( 120 );
	if ( function_exists( 'ignore_user_abort' ) ) {
		ignore_user_abort( true );
	}
	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_send_json_error( array( 'message' => 'ID de libro inválido.' ), 400 );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_export_cover_pdf_' . $book_id ) ) {
		wp_send_json_error( array( 'message' => 'Validación de seguridad fallida.' ), 403 );
	}

	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_send_json_error( array( 'message' => 'No tienes permisos para exportar esta portada.' ), 403 );
	}

	$book_post = get_post( $book_id );
	if ( ! $book_post || 'almaden-books' !== $book_post->post_type ) {
		wp_send_json_error( array( 'message' => 'Libro no encontrado.' ), 404 );
	}

	$raw_payload = isset( $_POST['cover_payload'] ) ? wp_unslash( $_POST['cover_payload'] ) : '';
	$payload     = json_decode( $raw_payload, true );
	if ( ! is_array( $payload ) ) {
		$payload = array();
	}

	global $wpdb;
	$settings_table = $wpdb->prefix . 'almaden_book_settings';
	$db_settings    = array();
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$settings_table'" ) === $settings_table ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
		if ( $row ) {
			$db_settings = $row;
		}
	}

	$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
	if ( ! is_array( $cover_settings ) ) {
		$cover_settings = array();
	}

	$cover_settings = wp_parse_args( $payload, $cover_settings );
	$pages = isset( $payload['page_count'] ) ? absint( $payload['page_count'] ) : absint( get_post_meta( $book_id, '_almaden_total_pages', true ) );
	if ( $pages < 20 ) {
		$pages = 20;
	}

	$page_width  = isset( $db_settings['page_width'] ) ? floatval( $db_settings['page_width'] ) : 14.0;
	$page_height = isset( $db_settings['page_height'] ) ? floatval( $db_settings['page_height'] ) : 21.0;
	if ( $page_width <= 0 ) {
		$page_width = 14.0;
	}
	if ( $page_height <= 0 ) {
		$page_height = 21.0;
	}

	$page_width_mm = almaden_bookster_round_up_mm( $page_width * 10 );
	$page_height_mm = almaden_bookster_round_up_mm( $page_height * 10 );

	$bleed_mm = 5.0;
	$front_flap_mm = isset( $cover_settings['front_flap_width'] ) ? almaden_bookster_round_up_mm( $cover_settings['front_flap_width'] ) : ( isset( $cover_settings['front_flap'] ) ? almaden_bookster_round_up_mm( $cover_settings['front_flap'] ) : 0 );
	$back_flap_mm  = isset( $cover_settings['back_flap_width'] ) ? almaden_bookster_round_up_mm( $cover_settings['back_flap_width'] ) : ( isset( $cover_settings['back_flap'] ) ? almaden_bookster_round_up_mm( $cover_settings['back_flap'] ) : 0 );
	$fold_x_mm = function_exists( 'almaden_bookster_get_cover_fold_x_mm' )
		? almaden_bookster_get_cover_fold_x_mm( $cover_settings )
		: ( isset( $cover_settings['fold_x'] ) ? almaden_bookster_round_up_mm( $cover_settings['fold_x'] ) : ( isset( $cover_settings['fold_x_mm'] ) ? almaden_bookster_round_up_mm( $cover_settings['fold_x_mm'] ) : 0 ) );
	if ( $front_flap_mm < 0 ) {
		$front_flap_mm = 0;
	}
	if ( $back_flap_mm < 0 ) {
		$back_flap_mm = 0;
	}

	$spine_width_mm = function_exists( 'almaden_bookster_get_cover_spine_width_mm' )
		? almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages )
		: ( isset( $cover_settings['spine_width_mm'] ) ? almaden_bookster_round_up_mm( $cover_settings['spine_width_mm'] ) : 0 );

	if ( $spine_width_mm <= 0 ) {
		$spine_width_mm = max( 1, almaden_bookster_round_up_mm( ( isset( $cover_settings['paper_type'] ) ? floatval( $cover_settings['paper_type'] ) : 0.06 ) * $pages ) );
	}

	$bleed_mm = almaden_bookster_round_up_mm( $bleed_mm );

	$front_flap_render_mm = $front_flap_mm > 0 ? almaden_bookster_round_up_mm( $front_flap_mm + $bleed_mm ) : 0;
	$back_flap_render_mm  = $back_flap_mm > 0 ? almaden_bookster_round_up_mm( $back_flap_mm + $bleed_mm ) : 0;
	$front_cover_render_mm = almaden_bookster_round_up_mm( $page_width_mm + ( $front_flap_mm > 0 ? 0 : $bleed_mm ) );
	$back_cover_render_mm  = almaden_bookster_round_up_mm( $page_width_mm + ( $back_flap_mm > 0 ? 0 : $bleed_mm ) );
	if ( $fold_x_mm > 0 && ( $front_flap_mm > 0 || $back_flap_mm > 0 ) ) {
		$front_cover_render_mm = almaden_bookster_round_up_mm( $front_cover_render_mm + $fold_x_mm );
		$back_cover_render_mm = almaden_bookster_round_up_mm( $back_cover_render_mm + $fold_x_mm );
	}
	$total_width_mm  = almaden_bookster_round_up_mm( $back_flap_render_mm + $back_cover_render_mm + $spine_width_mm + $front_cover_render_mm + $front_flap_render_mm );
	$total_height_mm = almaden_bookster_round_up_mm( $page_height_mm + ( 2 * $bleed_mm ) );

	$spread_image = ! empty( $cover_settings['spread_image'] ) ? esc_url_raw( $cover_settings['spread_image'] ) : '';

	$part_styles = array(
		'back_flap'  => 'width:' . $back_flap_render_mm . 'mm;',
		'back_cover' => 'width:' . $back_cover_render_mm . 'mm;',
		'spine'      => 'width:' . $spine_width_mm . 'mm;',
		'front_cover'=> 'width:' . $front_cover_render_mm . 'mm;',
		'front_flap' => 'width:' . $front_flap_render_mm . 'mm;',
	);

	$spread_image_data = $spread_image ? almaden_bookster_cover_export_url_to_data_uri( $spread_image ) : '';
	$back_flap_image_data = ! empty( $cover_settings['back_flap_image'] ) ? almaden_bookster_cover_export_url_to_data_uri( $cover_settings['back_flap_image'] ) : '';
	$back_image_data = ! empty( $cover_settings['back_image'] ) ? almaden_bookster_cover_export_url_to_data_uri( $cover_settings['back_image'] ) : '';
	$spine_image_data = ! empty( $cover_settings['spine_image'] ) ? almaden_bookster_cover_export_url_to_data_uri( $cover_settings['spine_image'] ) : '';
	$front_image_data = ! empty( $cover_settings['front_image'] ) ? almaden_bookster_cover_export_url_to_data_uri( $cover_settings['front_image'] ) : '';
	$front_flap_image_data = ! empty( $cover_settings['front_flap_image'] ) ? almaden_bookster_cover_export_url_to_data_uri( $cover_settings['front_flap_image'] ) : '';

	if ( empty( $spread_image_data ) ) {
		$back_flap_style = $part_styles['back_flap'] . 'background:' . ( ! empty( $cover_settings['back_flap_image'] ) ? 'transparent' : ( ! empty( $cover_settings['back_flap_color'] ) ? sanitize_hex_color( $cover_settings['back_flap_color'] ) : '#ffffff' ) ) . ';';
		$back_cover_style = $part_styles['back_cover'] . 'background:' . ( ! empty( $cover_settings['back_image'] ) ? 'transparent' : '#ffffff' ) . ';';
		$spine_style = $part_styles['spine'] . 'background:';
		if ( ! empty( $cover_settings['spine_image'] ) ) {
			$spine_style .= 'transparent;';
		} elseif ( ! empty( $cover_settings['spine_color'] ) ) {
			$spine_style .= sanitize_hex_color( $cover_settings['spine_color'] ) . ';';
		} else {
			$spine_style .= '#f9fafb;';
		}
		$front_cover_style = $part_styles['front_cover'] . 'background:' . ( ! empty( $cover_settings['front_image'] ) ? 'transparent' : '#ffffff' ) . ';';
		$front_flap_style = $part_styles['front_flap'] . 'background:' . ( ! empty( $cover_settings['front_flap_image'] ) ? 'transparent' : ( ! empty( $cover_settings['front_flap_color'] ) ? sanitize_hex_color( $cover_settings['front_flap_color'] ) : '#ffffff' ) ) . ';';
	} else {
		$back_flap_style = $part_styles['back_flap'] . 'background:transparent;';
		$back_cover_style = $part_styles['back_cover'] . 'background:transparent;';
		$spine_style = $part_styles['spine'] . 'background:transparent;';
		$front_cover_style = $part_styles['front_cover'] . 'background:transparent;';
		$front_flap_style = $part_styles['front_flap'] . 'background:transparent;';
	}

	$layers = isset( $cover_settings['text_layers'] ) && is_array( $cover_settings['text_layers'] ) ? $cover_settings['text_layers'] : array();
	usort(
		$layers,
		function( $a, $b ) {
			$za = isset( $a['zIndex'] ) ? intval( $a['zIndex'] ) : 0;
			$zb = isset( $b['zIndex'] ) ? intval( $b['zIndex'] ) : 0;
			return $za <=> $zb;
		}
	);

	$book_title = get_the_title( $book_id );
	$filename_base = sanitize_title( $book_title );
	if ( empty( $filename_base ) ) {
		$filename_base = 'book-cover';
	}
	$filename = $filename_base . '-cover-cmyk.pdf';

	$used_fonts = array();
	foreach ( $layers as $layer ) {
		if ( ! empty( $layer['fontFamily'] ) ) {
			$used_fonts[] = str_replace( array( "\r", "\n", '"', "'" ), '', $layer['fontFamily'] );
		}
	}
	$used_fonts = array_unique( $used_fonts );
	$font_families_for_cdn = array();
	foreach ( $used_fonts as $f ) {
		$family_slug = str_replace( ' ', '+', $f );
		$font_families_for_cdn[] = 'family=' . $family_slug . ':ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900';
	}
	$google_fonts_url = '';
	if ( ! empty( $font_families_for_cdn ) ) {
		$google_fonts_url = 'https://fonts.googleapis.com/css2?' . implode( '&', $font_families_for_cdn ) . '&display=swap';
	}

	$html = "<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width,initial-scale=1\"><title>" . esc_html( $book_title ) . " - Cover Export</title>";
	if ( $google_fonts_url ) {
		$html .= '<link rel="preconnect" href="https://fonts.googleapis.com">';
		$html .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
		$html .= '<link href="' . esc_url( $google_fonts_url ) . '" rel="stylesheet">';
	}
	$html .= '<style>';
	$html .= 'html,body{margin:0;padding:0;width:100%;height:100%;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-family:serif;}';
	$html .= '@page{size:' . $total_width_mm . 'mm ' . $total_height_mm . 'mm;margin:0;}';
	$html .= '#export-page{width:' . $total_width_mm . 'mm;height:' . $total_height_mm . 'mm;overflow:hidden;position:relative;background:#fff;}';
	$html .= '#export-spread{position:relative;display:flex;width:100%;height:100%;overflow:hidden;' . ( $spread_image_data ? 'background-image:url(' . esc_attr( $spread_image_data ) . ');background-size:cover;background-position:center;background-repeat:no-repeat;' : '' ) . '}';
	$html .= '.export-part{position:relative;flex-shrink:0;height:100%;overflow:hidden;background-size:cover;background-position:center;background-repeat:no-repeat;}';
	$html .= '.export-layer{position:absolute;pointer-events:none;box-sizing:border-box;font-kerning:normal;font-variant-ligatures:common-ligatures;text-rendering:geometricPrecision;}';
	$html .= '.export-layer--image{overflow:hidden;}';
	$html .= '.export-layer--shape{overflow:hidden;}';
	$html .= '</style>';
	$html .= '</head><body><div id="export-page"><div id="export-spread">';

	if ( empty( $spread_image_data ) ) {
		$html .= '<div class="export-part" style="' . esc_attr( $back_flap_style ) . '">';
		if ( ! empty( $back_flap_image_data ) ) {
			$html .= '<img alt="" aria-hidden="true" src="' . esc_attr( $back_flap_image_data ) . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
		}
		$html .= '</div>';

		$html .= '<div class="export-part" style="' . esc_attr( $back_cover_style ) . '">';
		if ( ! empty( $back_image_data ) ) {
			$html .= '<img alt="" aria-hidden="true" src="' . esc_attr( $back_image_data ) . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
		}
		$html .= '</div>';

		$html .= '<div class="export-part" style="' . esc_attr( $spine_style ) . '">';
		if ( ! empty( $spine_image_data ) ) {
			$html .= '<img alt="" aria-hidden="true" src="' . esc_attr( $spine_image_data ) . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
		} elseif ( ! empty( $cover_settings['spine_color'] ) ) {
			// Background already set inline.
		}
		$html .= '</div>';

		$html .= '<div class="export-part" style="' . esc_attr( $front_cover_style ) . '">';
		if ( ! empty( $front_image_data ) ) {
			$html .= '<img alt="" aria-hidden="true" src="' . esc_attr( $front_image_data ) . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
		}
		$html .= '</div>';

		$html .= '<div class="export-part" style="' . esc_attr( $front_flap_style ) . '">';
		if ( ! empty( $front_flap_image_data ) ) {
			$html .= '<img alt="" aria-hidden="true" src="' . esc_attr( $front_flap_image_data ) . '" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">';
		}
		$html .= '</div>';
	}

	foreach ( $layers as $layer ) {
		if ( ! is_array( $layer ) || empty( $layer['id'] ) || ( isset( $layer['type'] ) && 'group' === $layer['type'] ) ) {
			continue;
		}

		$x = isset( $layer['x'] ) ? floatval( $layer['x'] ) : 0;
		$y = isset( $layer['y'] ) ? floatval( $layer['y'] ) : 0;
		$rot = isset( $layer['rotation'] ) ? floatval( $layer['rotation'] ) : 0;
		$z_index = isset( $layer['zIndex'] ) ? intval( $layer['zIndex'] ) : 30;
		$left = $x . '%';
		$top  = $y . '%';
		$common_style = 'left:' . esc_attr( $left ) . ';top:' . esc_attr( $top ) . ';transform:rotate(' . $rot . 'deg);z-index:' . $z_index . ';';

		if ( ! empty( $layer['type'] ) && 'image' === $layer['type'] && ! empty( $layer['url'] ) ) {
			$lw = isset( $layer['width'] ) && $layer['width'] ? floatval( $layer['width'] ) : 200;
			$lh = isset( $layer['height'] ) && $layer['height'] ? floatval( $layer['height'] ) : 200;
			$layer_image_data = almaden_bookster_cover_export_url_to_data_uri( $layer['url'] );
			if ( empty( $layer_image_data ) ) {
				continue;
			}
			$html .= '<div class="export-layer export-layer--image" style="' . esc_attr( $common_style . 'width:' . $lw . 'px;height:' . $lh . 'px;' ) . '"><img alt="" aria-hidden="true" src="' . esc_attr( $layer_image_data ) . '" style="width:100%;height:100%;object-fit:contain;display:block;"></div>';
			continue;
		}

		if ( ! empty( $layer['type'] ) && 'shape' === $layer['type'] ) {
			$lw = isset( $layer['width'] ) && $layer['width'] ? floatval( $layer['width'] ) : 150;
			$lh = isset( $layer['height'] ) && $layer['height'] ? floatval( $layer['height'] ) : 150;
			$opacity = isset( $layer['opacity'] ) ? max( 0, min( 100, floatval( $layer['opacity'] ) ) ) / 100 : 1;
			$shape_type = isset( $layer['shapeType'] ) && 'circle' === $layer['shapeType'] ? 'circle' : 'rectangle';
			$radius = 'circle' === $shape_type ? '50%' : '0';
			$color1 = ! empty( $layer['color1'] ) ? sanitize_hex_color( $layer['color1'] ) : '#000000';
			if ( empty( $color1 ) ) {
				$color1 = '#000000';
			}
			$color1_opacity = isset( $layer['color1Opacity'] ) ? max( 0, min( 100, floatval( $layer['color1Opacity'] ) ) ) / 100 : 1;
			$rgba1 = 'rgba(0,0,0,' . $color1_opacity . ')';
			if ( preg_match( '/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/', $color1, $m ) ) {
				$rgba1 = 'rgba(' . hexdec( $m[1] ) . ',' . hexdec( $m[2] ) . ',' . hexdec( $m[3] ) . ',' . $color1_opacity . ')';
			}
			$bg = $rgba1;
			if ( ! empty( $layer['isGradient'] ) ) {
				$color2 = ! empty( $layer['color2'] ) ? sanitize_hex_color( $layer['color2'] ) : '#ffffff';
				if ( empty( $color2 ) ) {
					$color2 = '#ffffff';
				}
				$color2_opacity = isset( $layer['color2Opacity'] ) ? max( 0, min( 100, floatval( $layer['color2Opacity'] ) ) ) / 100 : 1;
				$rgba2 = 'rgba(255,255,255,' . $color2_opacity . ')';
				if ( preg_match( '/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/', $color2, $m ) ) {
					$rgba2 = 'rgba(' . hexdec( $m[1] ) . ',' . hexdec( $m[2] ) . ',' . hexdec( $m[3] ) . ',' . $color2_opacity . ')';
				}
				$angle = isset( $layer['gradientAngle'] ) ? floatval( $layer['gradientAngle'] ) : 90;
				$bg = 'linear-gradient(' . $angle . 'deg,' . $rgba1 . ',' . $rgba2 . ')';
			}

			$html .= '<div class="export-layer export-layer--shape" style="' . esc_attr( $common_style . 'width:' . $lw . 'px;height:' . $lh . 'px;opacity:' . $opacity . ';border-radius:' . $radius . ';background:' . $bg . ';' ) . '"></div>';
			continue;
		}

		$font_family = ! empty( $layer['fontFamily'] ) ? sanitize_text_field( $layer['fontFamily'] ) : 'Inter';
		$font_family_css = str_replace( array( "\r", "\n", '"', "'" ), '', $font_family );
		$font_size   = isset( $layer['fontSize'] ) ? floatval( $layer['fontSize'] ) : 12;
		$font_weight = ! empty( $layer['fontWeight'] ) ? sanitize_text_field( $layer['fontWeight'] ) : '400';
		$font_style  = ! empty( $layer['fontStyle'] ) ? sanitize_text_field( $layer['fontStyle'] ) : 'normal';
		$line_height = isset( $layer['lineHeight'] ) && $layer['lineHeight'] !== '' ? floatval( $layer['lineHeight'] ) : 1.2;
		$letter_spacing = isset( $layer['letterSpacing'] ) && $layer['letterSpacing'] !== '' ? floatval( $layer['letterSpacing'] ) : 0;
		$color       = ! empty( $layer['color'] ) ? sanitize_hex_color( $layer['color'] ) : '#000000';
		if ( empty( $color ) ) {
			$color = '#000000';
		}
		$text_align = ! empty( $layer['textAlign'] ) ? sanitize_text_field( $layer['textAlign'] ) : 'center';
		if ( ! in_array( $text_align, array( 'left', 'center', 'right', 'justify' ), true ) ) {
			$text_align = 'center';
		}
		$width = isset( $layer['width'] ) && $layer['width'] ? floatval( $layer['width'] ) . 'px' : 'auto';
		$height = isset( $layer['height'] ) && $layer['height'] ? floatval( $layer['height'] ) . 'px' : 'auto';
		$text  = isset( $layer['text'] ) ? esc_html( $layer['text'] ) : '';
		$hyphens = ! empty( $layer['hyphens'] ) ? 'auto' : 'none';

		$html .= '<div class="export-layer" lang="es" style="' . esc_attr( $common_style . 'width:' . $width . ';height:' . $height . ';font-size:' . $font_size . 'px;font-weight:' . $font_weight . ';font-style:' . $font_style . ';color:' . $color . ';font-family:"' . esc_attr( $font_family_css ) . '",serif;text-align:' . $text_align . ';white-space:pre-wrap;line-height:' . $line_height . ';letter-spacing:' . $letter_spacing . 'px;font-synthesis:none;hyphens:' . $hyphens . ';-webkit-hyphens:' . $hyphens . ';' ) . '">' . $text . '</div>';
	}

	$html .= '</div></div></body></html>';

	$temp_dir = trailingslashit( sys_get_temp_dir() ) . 'almaden-cover-' . wp_generate_password( 8, false, false );
	if ( ! wp_mkdir_p( $temp_dir ) ) {
		wp_send_json_error( array( 'message' => 'No se pudo crear el directorio temporal.' ), 500 );
	}

	$html_file = $temp_dir . '/cover-export.html';
	$pdf_file  = $temp_dir . '/cover-export.pdf';
	$cmyk_file = $temp_dir . '/cover-export-cmyk.pdf';
	file_put_contents( $html_file, $html );

	$chrome = almaden_bookster_find_chrome_binary();
	if ( empty( $chrome ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		wp_send_json_error( array( 'message' => 'No se encontró Google Chrome para generar el PDF.' ), 500 );
	}

	$gs = almaden_bookster_find_ghostscript_binary();
	if ( empty( $gs ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		wp_send_json_error( array( 'message' => 'No se encontró Ghostscript para convertir el PDF a CMYK.' ), 500 );
	}

	$profile_dir = $temp_dir . '/chrome-profile';
	wp_mkdir_p( $profile_dir );

	$chrome_command = array(
		$chrome,
		'--headless=new',
		'--no-sandbox',
		'--disable-gpu',
		'--disable-dev-shm-usage',
		'--allow-file-access-from-files',
		'--no-first-run',
		'--no-default-browser-check',
		'--disable-crash-reporter',
		'--disable-background-networking',
		'--user-data-dir=' . $profile_dir,
		'--no-pdf-header-footer',
		'--print-to-pdf=' . $pdf_file,
		'--virtual-time-budget=8000',
		'file://' . $html_file,
	);

	$stdout = '';
	$stderr = '';
	$chrome_result = almaden_bookster_run_process( $chrome_command, $stdout, $stderr, 30 );

	$is_timeout = is_wp_error( $chrome_result ) && strpos( $chrome_result->get_error_message(), 'tiempo límite' ) !== false;
	$pdf_exists = file_exists( $pdf_file ) && filesize( $pdf_file ) > 1000;

	if ( ( is_wp_error( $chrome_result ) && ! ( $is_timeout && $pdf_exists ) ) || ! file_exists( $pdf_file ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		$message = is_wp_error( $chrome_result ) ? $chrome_result->get_error_message() : trim( $stderr );
		wp_send_json_error( array( 'message' => 'Chrome no pudo generar el PDF. ' . $message ), 500 );
	}

	$gs_command = array(
		$gs,
		'-o',
		$cmyk_file,
		'-sDEVICE=pdfwrite',
		'-dCompatibilityLevel=1.4',
		'-dPDFSETTINGS=/prepress',
		'-dNOPAUSE',
		'-dBATCH',
		'-dSAFER',
		'-sProcessColorModel=DeviceCMYK',
		'-sColorConversionStrategy=CMYK',
		'-sColorConversionStrategyForImages=CMYK',
		$pdf_file,
	);

	$gs_stdout = '';
	$gs_stderr = '';
	$gs_result = almaden_bookster_run_process( $gs_command, $gs_stdout, $gs_stderr, 60 );
	if ( is_wp_error( $gs_result ) || ! file_exists( $cmyk_file ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		$message = is_wp_error( $gs_result ) ? $gs_result->get_error_message() : trim( $gs_stderr );
		wp_send_json_error( array( 'message' => 'No se pudo convertir a CMYK. ' . $message ), 500 );
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . filesize( $cmyk_file ) );

	readfile( $cmyk_file );
	almaden_bookster_rrmdir( $temp_dir );
	exit;
}

