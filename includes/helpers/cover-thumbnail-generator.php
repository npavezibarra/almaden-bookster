<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_bookster_get_cover_thumbnail_snapshot_source_payload( $book_id ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return array();
    }

    $cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
    if ( ! is_array( $cover_settings ) ) {
        $cover_settings = array();
    }

    $db_settings = almaden_bookster_get_cover_settings_row( $book_id );

    return array(
        'book_id'       => $book_id,
        'cover_settings' => $cover_settings,
        'db_settings'   => array(
            'page_width'  => isset( $db_settings['page_width'] ) ? floatval( $db_settings['page_width'] ) : 0,
            'page_height' => isset( $db_settings['page_height'] ) ? floatval( $db_settings['page_height'] ) : 0,
        ),
        'total_pages'   => absint( get_post_meta( $book_id, '_almaden_total_pages', true ) ),
    );
}

function almaden_bookster_get_cover_thumbnail_snapshot_version( $book_id, array $payload = array() ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return '';
    }

    if ( empty( $payload ) ) {
        $payload = almaden_bookster_get_cover_thumbnail_snapshot_source_payload( $book_id );
    }

    if ( empty( $payload ) ) {
        return '';
    }

    return sha1( wp_json_encode( $payload ) );
}

function almaden_bookster_get_cover_thumbnail_snapshot_dimensions( $book_id ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return array(
            'width' => 1200,
            'height' => 1697,
            'aspect_ratio' => 0.7071,
        );
    }

    $db_settings = almaden_bookster_get_cover_settings_row( $book_id );
    $cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
    if ( ! is_array( $cover_settings ) ) {
        $cover_settings = array();
    }

    $page_width = isset( $db_settings['page_width'] ) ? floatval( $db_settings['page_width'] ) : 21.0;
    $page_height = isset( $db_settings['page_height'] ) ? floatval( $db_settings['page_height'] ) : 29.7;

    $front_flap_mm = isset( $cover_settings['front_flap_width'] ) ? almaden_bookster_round_up_mm( $cover_settings['front_flap_width'] ) : 0;
    $back_flap_mm  = isset( $cover_settings['back_flap_width'] ) ? almaden_bookster_round_up_mm( $cover_settings['back_flap_width'] ) : 0;
    $fold_x_mm     = function_exists( 'almaden_bookster_get_cover_fold_x_mm' ) ? almaden_bookster_get_cover_fold_x_mm( $cover_settings ) : 0;
    $pages         = absint( get_post_meta( $book_id, '_almaden_total_pages', true ) );
    if ( $pages < 20 ) {
        $pages = 20;
    }

    $spine_width_mm = function_exists( 'almaden_bookster_get_cover_spine_width_mm' )
        ? almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages )
        : max( 1, almaden_bookster_round_up_mm( ( isset( $cover_settings['paper_type'] ) ? floatval( $cover_settings['paper_type'] ) : 0.06 ) * $pages ) );

    $px_per_cm = 37.7952755906;
    $bleed_px = ( 5 / 10 ) * $px_per_cm;
    $page_width_px  = $page_width * $px_per_cm;
    $page_height_px = $page_height * $px_per_cm;
    $actual_height_px = $page_height_px + ( 2 * $bleed_px );

    $front_cover_px = $page_width_px;
    if ( $fold_x_mm > 0 && ( $front_flap_mm > 0 || $back_flap_mm > 0 ) ) {
        $front_cover_px += ( $fold_x_mm / 10 ) * $px_per_cm;
    }
    if ( $front_flap_mm <= 0 ) {
        $front_cover_px += $bleed_px;
    }
    if ( $front_cover_px <= 0 ) {
        $front_cover_px = $page_width_px;
    }

    $aspect_ratio = $actual_height_px > 0 ? ( $front_cover_px / $actual_height_px ) : 0.7071;
    if ( $aspect_ratio <= 0 ) {
        $aspect_ratio = 0.7071;
    }

    $capture_width_px = 1200;
    $capture_height_px = (int) ceil( $capture_width_px / $aspect_ratio );

    return array(
        'capture_width_px'  => $capture_width_px,
        'capture_height_px' => $capture_height_px,
        'front_cover_px'    => $front_cover_px,
        'actual_height_px'  => $actual_height_px,
        'aspect_ratio'      => $aspect_ratio,
        'page_width_px'     => $page_width_px,
        'page_height_px'    => $page_height_px,
        'spine_width_mm'    => $spine_width_mm,
    );
}

function almaden_bookster_build_cover_thumbnail_snapshot_html_doc( $book_id, $viewport_width_px, $viewport_height_px ) {
    $book_id = absint( $book_id );
    if ( $book_id <= 0 ) {
        return '';
    }

    $previous_flag = isset( $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] ) ? $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] : false;
    $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] = true;
    $thumbnail_html = almaden_get_cover_thumbnail_html( $book_id );
    $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] = $previous_flag;

    if ( '' === trim( (string) $thumbnail_html ) ) {
        return '';
    }

    $fonts_url = function_exists( 'almaden_get_thumbnail_fonts_url' ) ? almaden_get_thumbnail_fonts_url() : '';
    ob_start();
	?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( function_exists( 'almaden_bookster_get_bundled_fonts_stylesheet_url' ) ) : ?>
		<link rel="stylesheet" href="<?php echo esc_url( almaden_bookster_get_bundled_fonts_stylesheet_url() ); ?>">
	<?php endif; ?>
	<?php if ( ! empty( $fonts_url ) ) : ?>
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="<?php echo esc_url( $fonts_url ); ?>" rel="stylesheet">
	<?php endif; ?>
	<style>
		html, body {
			margin: 0;
			padding: 0;
			width: <?php echo esc_attr( (int) $viewport_width_px ); ?>px;
			height: <?php echo esc_attr( (int) $viewport_height_px ); ?>px;
			overflow: hidden;
			background: #ffffff;
		}
		body {
			display: block;
		}
		#almaden-cover-snapshot-root {
			width: <?php echo esc_attr( (int) $viewport_width_px ); ?>px;
			height: auto;
			background: #ffffff;
		}
		#almaden-cover-snapshot-root .cover-thumbnail-wrapper,
		#almaden-cover-snapshot-root .cover-thumbnail-wrapper * {
			box-sizing: border-box;
		}
		#almaden-cover-snapshot-root .cover-thumbnail-wrapper {
			display: block;
		}
		#almaden-cover-snapshot-root .absolute { position: absolute; }
		#almaden-cover-snapshot-root .relative { position: relative; }
		#almaden-cover-snapshot-root .inset-0 { top: 0; right: 0; bottom: 0; left: 0; }
		#almaden-cover-snapshot-root .top-0 { top: 0; }
		#almaden-cover-snapshot-root .bottom-0 { bottom: 0; }
		#almaden-cover-snapshot-root .bg-cover { background-size: cover; }
		#almaden-cover-snapshot-root .bg-center { background-position: center; }
		#almaden-cover-snapshot-root .block { display: block; }
		#almaden-cover-snapshot-root .h-full { height: 100%; }
		#almaden-cover-snapshot-root .w-full { width: 100%; }
		#almaden-cover-snapshot-root .object-cover { object-fit: cover; }
		#almaden-cover-snapshot-root .overflow-hidden { overflow: hidden; }
		#almaden-cover-snapshot-root .border-b { border-bottom: 1px solid #e5e7eb; }
		#almaden-cover-snapshot-root .border-gray-200 { border-color: #e5e7eb; }
	</style>
	<script>
		(function () {
			function scaleThumbnails() {
				const wrapper = document.querySelector('.cover-thumbnail-wrapper');
				if (!wrapper) return;

				const targetWidth = wrapper.clientWidth;
				const frontCoverPx = parseFloat(wrapper.getAttribute('data-front-cover-px'));
				const startPx = parseFloat(wrapper.getAttribute('data-start-px'));
				if (frontCoverPx > 0) {
					const scale = targetWidth / frontCoverPx;
					const spread = wrapper.querySelector('.cover-spread-container');
					if (spread) {
						spread.style.transform = `scale(${scale}) translateX(${-startPx}px)`;
					}
				}
			}

			window.addEventListener('resize', scaleThumbnails);
			window.addEventListener('load', scaleThumbnails);
			setTimeout(scaleThumbnails, 100);
			setTimeout(scaleThumbnails, 400);
		})();
	</script>
</head>
<body>
	<div id="almaden-cover-snapshot-root">
		<?php echo $thumbnail_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</body>
</html>
	<?php
	return ob_get_clean();
}

function almaden_bookster_run_command_to_file( array $command, $expected_file, $timeout_seconds = 60 ) {
	$stdout = '';
	$stderr = '';
	$result = almaden_bookster_run_process( $command, $stdout, $stderr, $timeout_seconds );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( empty( $expected_file ) || ! file_exists( $expected_file ) ) {
		return new WP_Error( 'snapshot_output_missing', 'No se generó el archivo esperado.' );
	}

	return true;
}

function almaden_bookster_generate_cover_thumbnail_snapshot( $book_id, array $payload = array() ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return new WP_Error( 'invalid_book', 'ID de libro inválido.' );
	}

	if ( empty( $payload ) ) {
		$payload = almaden_bookster_get_cover_thumbnail_snapshot_source_payload( $book_id );
	}

	if ( empty( $payload['cover_settings'] ) || ! is_array( $payload['cover_settings'] ) ) {
		$existing_snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
		if ( ! empty( $existing_snapshot['attachment_id'] ) ) {
			wp_delete_attachment( (int) $existing_snapshot['attachment_id'], true );
		}
		almaden_bookster_clear_cover_thumbnail_snapshot_metadata( $book_id );
		return array( 'removed' => true );
	}

	$version = almaden_bookster_get_cover_thumbnail_snapshot_version( $book_id, $payload );
	if ( '' === $version ) {
		return new WP_Error( 'snapshot_version_failed', 'No se pudo calcular la versión del snapshot.' );
	}

	$existing_snapshot = almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
	if ( ! empty( $existing_snapshot['version'] ) && $existing_snapshot['version'] === $version && ! empty( $existing_snapshot['attachment_id'] ) && ! empty( $existing_snapshot['url'] ) ) {
		return array(
			'attachment_id' => (int) $existing_snapshot['attachment_id'],
			'url'           => $existing_snapshot['url'],
			'version'       => $version,
			'skipped'       => true,
		);
	}

	$dimensions = almaden_bookster_get_cover_thumbnail_snapshot_dimensions( $book_id );
	$viewport_width_px  = ! empty( $dimensions['capture_width_px'] ) ? absint( $dimensions['capture_width_px'] ) : 1200;
	$viewport_height_px = ! empty( $dimensions['capture_height_px'] ) ? absint( $dimensions['capture_height_px'] ) : 1697;

	$html_doc = almaden_bookster_build_cover_thumbnail_snapshot_html_doc( $book_id, $viewport_width_px, $viewport_height_px );
	if ( '' === trim( (string) $html_doc ) ) {
		return new WP_Error( 'snapshot_html_failed', 'No se pudo generar el HTML del snapshot.' );
	}

	$temp_dir = trailingslashit( sys_get_temp_dir() ) . 'almaden-cover-snapshot-' . wp_generate_password( 10, false, false );
	if ( ! wp_mkdir_p( $temp_dir ) ) {
		return new WP_Error( 'snapshot_temp_dir_failed', 'No se pudo crear el directorio temporal del snapshot.' );
	}

	$html_file = $temp_dir . '/snapshot.html';
	$png_file  = $temp_dir . '/snapshot.png';
	$jpg_file  = $temp_dir . '/snapshot.jpg';

	if ( false === file_put_contents( $html_file, $html_doc ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return new WP_Error( 'snapshot_html_write_failed', 'No se pudo escribir el HTML temporal.' );
	}

	$chrome = almaden_bookster_find_chrome_binary();
	if ( empty( $chrome ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return new WP_Error( 'snapshot_chrome_missing', 'No se encontró Chrome para generar el snapshot.' );
	}

	$chrome_command = array(
		$chrome,
		'--headless=new',
		'--no-sandbox',
		'--disable-gpu',
		'--disable-dev-shm-usage',
		'--allow-file-access-from-files',
		'--hide-scrollbars',
		'--force-device-scale-factor=1',
		'--window-size=' . $viewport_width_px . ',' . $viewport_height_px,
		'--virtual-time-budget=4000',
		'--screenshot=' . $png_file,
		'file://' . $html_file,
	);

	$chrome_result = almaden_bookster_run_command_to_file( $chrome_command, $png_file, 45 );
	if ( is_wp_error( $chrome_result ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return $chrome_result;
	}

	$sips_command = array(
		'/usr/bin/sips',
		'-s',
		'format',
		'jpeg',
		'-s',
		'formatOptions',
		'82',
		$png_file,
		'--out',
		$jpg_file,
	);
	$sips_result = almaden_bookster_run_command_to_file( $sips_command, $jpg_file, 30 );
	if ( is_wp_error( $sips_result ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return $sips_result;
	}

	$upload_dir = wp_upload_dir();
	$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'almaden-cover-thumbnails';
	if ( ! wp_mkdir_p( $target_dir ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return new WP_Error( 'snapshot_upload_dir_failed', 'No se pudo crear el directorio de snapshots.' );
	}

	$file_name = 'book-' . $book_id . '-cover-' . $version . '.jpg';
	$target_path = trailingslashit( $target_dir ) . $file_name;

	if ( ! copy( $jpg_file, $target_path ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		return new WP_Error( 'snapshot_copy_failed', 'No se pudo mover el JPG generado.' );
	}

	$attachment = array(
		'guid'           => trailingslashit( $upload_dir['baseurl'] ) . 'almaden-cover-thumbnails/' . $file_name,
		'post_mime_type' => 'image/jpeg',
		'post_title'     => get_the_title( $book_id ) . ' cover snapshot',
		'post_content'   => '',
		'post_status'    => 'inherit',
		'post_parent'    => $book_id,
	);

	$attachment_id = wp_insert_attachment( $attachment, $target_path, $book_id );
	if ( is_wp_error( $attachment_id ) || $attachment_id <= 0 ) {
		@unlink( $target_path );
		almaden_bookster_rrmdir( $temp_dir );
		return new WP_Error( 'snapshot_attachment_failed', 'No se pudo registrar el attachment del snapshot.' );
	}

	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	$metadata = wp_generate_attachment_metadata( $attachment_id, $target_path );
	if ( ! is_wp_error( $metadata ) && ! empty( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	$old_attachment_id = ! empty( $existing_snapshot['attachment_id'] ) ? absint( $existing_snapshot['attachment_id'] ) : 0;
	almaden_bookster_set_cover_thumbnail_snapshot_metadata( $book_id, array(
		'attachment_id' => $attachment_id,
		'version'       => $version,
		'width'         => $viewport_width_px,
		'height'        => $viewport_height_px,
		'mime'          => 'image/jpeg',
		'generated_at'  => gmdate( 'c' ),
	) );

	if ( $old_attachment_id > 0 && $old_attachment_id !== (int) $attachment_id ) {
		wp_delete_attachment( $old_attachment_id, true );
	}

	almaden_bookster_bump_bookshelf_cache_version();

	$result_url = wp_get_attachment_url( $attachment_id );
	almaden_bookster_rrmdir( $temp_dir );

	return array(
		'attachment_id' => (int) $attachment_id,
		'url'           => $result_url ? $result_url : trailingslashit( $upload_dir['baseurl'] ) . 'almaden-cover-thumbnails/' . $file_name,
		'version'       => $version,
		'width'         => $viewport_width_px,
		'height'        => $viewport_height_px,
	);
}

function almaden_get_cover_thumbnail_html( $book_id ) {
    $snapshot_disabled = ! empty( $GLOBALS['almaden_bookster_disable_cover_snapshot_resolve'] );
    $snapshot = $snapshot_disabled ? array() : almaden_bookster_get_cover_thumbnail_snapshot_metadata( $book_id );
    if ( ! empty( $snapshot ) ) {
        $snapshot_html = almaden_bookster_render_cover_thumbnail_snapshot_html( $book_id, $snapshot );
        if ( '' !== $snapshot_html ) {
            return $snapshot_html;
        }
    }

    $db_settings = almaden_bookster_get_cover_settings_row( $book_id );

    $page_width = isset($db_settings['page_width']) ? floatval($db_settings['page_width']) : 21.0;
    $page_height = isset($db_settings['page_height']) ? floatval($db_settings['page_height']) : 29.7;
    
    $cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
    if ( empty($cover_settings) || !is_array($cover_settings) ) {
        return '';
    }

    // Card thumbnails must use the same screen-safe assets as the cover editor.
    // This keeps CMYK originals for print while avoiding broken or color-shifted
    // images in the browser.
    if ( function_exists( 'almaden_bookster_prepare_cover_settings_for_editor' ) ) {
        $cover_settings = almaden_bookster_prepare_cover_settings_for_editor( $cover_settings );
    }
    $spread_preview_url = ! empty( $cover_settings['spread_image_preview_url'] ) ? $cover_settings['spread_image_preview_url'] : '';
    $front_preview_url = ! empty( $cover_settings['front_image_preview_url'] ) ? $cover_settings['front_image_preview_url'] : '';
    $back_preview_url = ! empty( $cover_settings['back_image_preview_url'] ) ? $cover_settings['back_image_preview_url'] : '';
    $spine_preview_url = ! empty( $cover_settings['spine_image_preview_url'] ) ? $cover_settings['spine_image_preview_url'] : '';

    // Check if there are any layers or front image
    if ( empty($cover_settings['text_layers']) && empty($cover_settings['front_image']) && empty($cover_settings['spread_image']) ) {
        return '';
    }

    $total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
    $pages = $total_pages ? intval( $total_pages ) : 20;
    if ($pages < 20) $pages = 20;

    $spineWidthMm = almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages );
    
    $frontFlapMm = isset($cover_settings['front_flap_width']) ? almaden_bookster_round_up_mm( $cover_settings['front_flap_width'] ) : 0;
    $backFlapMm = isset($cover_settings['back_flap_width']) ? almaden_bookster_round_up_mm( $cover_settings['back_flap_width'] ) : 0;
    $foldXMm = function_exists( 'almaden_bookster_get_cover_fold_x_mm' ) ? almaden_bookster_get_cover_fold_x_mm( $cover_settings ) : 0;
    
    $pxPerCm = 37.7952755906;
    $bleedPx = (5 / 10) * $pxPerCm; 

    $spineWidthPx = ($spineWidthMm / 10) * $pxPerCm;
    
    $frontFlapPx = ($frontFlapMm / 10) * $pxPerCm;
    $backFlapPx = ($backFlapMm / 10) * $pxPerCm;
    
    $pageWidthPx = $page_width * $pxPerCm;
    $pageHeightPx = $page_height * $pxPerCm;
    $actualHeightPx = $pageHeightPx + (2 * $bleedPx);

    $frontCoverPx = $pageWidthPx;
    $backCoverPx = $pageWidthPx;

    if ( $foldXMm > 0 && ( $frontFlapMm > 0 || $backFlapMm > 0 ) ) {
        $foldXPx = ( $foldXMm / 10 ) * $pxPerCm;
        $frontCoverPx += $foldXPx;
        $backCoverPx += $foldXPx;
    }

    if ($frontFlapMm > 0) $frontFlapPx += $bleedPx; else $frontCoverPx += $bleedPx;
    if ($backFlapMm > 0) $backFlapPx += $bleedPx; else $backCoverPx += $bleedPx;

    $totalSpreadWidthPx = $frontCoverPx + $backCoverPx + $spineWidthPx + $frontFlapPx + $backFlapPx;
    $frontCoverStartPx = $backFlapPx + $backCoverPx + $spineWidthPx;

    $aspectRatio = $frontCoverPx / $actualHeightPx;

    ob_start();
    ?>
    <div class="cover-thumbnail-wrapper w-full bg-white overflow-hidden relative border-b border-gray-200" 
         data-front-cover-px="<?php echo esc_attr($frontCoverPx); ?>"
         data-start-px="<?php echo esc_attr($frontCoverStartPx); ?>"
         style="aspect-ratio: <?php echo round($aspectRatio, 4); ?>;">
        
        <div class="cover-spread-container absolute top-0 left-0" 
             style="width: <?php echo $totalSpreadWidthPx; ?>px; height: <?php echo $actualHeightPx; ?>px; transform-origin: top left; pointer-events: none;">
            
            <?php if ( !empty($spread_preview_url) ) : ?>
                <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?php echo esc_url($spread_preview_url); ?>');"></div>
            <?php else : ?>
                <?php if ( !empty($front_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $frontCoverStartPx; ?>px; width: <?php echo $frontCoverPx; ?>px; background-image: url('<?php echo esc_url($front_preview_url); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($back_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo $backFlapPx; ?>px; width: <?php echo $backCoverPx; ?>px; background-image: url('<?php echo esc_url($back_preview_url); ?>');"></div>
                <?php endif; ?>
                <?php if ( !empty($spine_preview_url) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo ($backFlapPx + $backCoverPx); ?>px; width: <?php echo $spineWidthPx; ?>px; background-image: url('<?php echo esc_url($spine_preview_url); ?>');"></div>
                <?php elseif ( !empty($cover_settings['spine_color']) ) : ?>
                    <div class="absolute top-0 bottom-0 bg-cover bg-center" style="left: <?php echo ($backFlapPx + $backCoverPx); ?>px; width: <?php echo $spineWidthPx; ?>px; background-color: <?php echo esc_attr($cover_settings['spine_color']); ?>;"></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php
            if ( !empty($cover_settings['text_layers']) && is_array($cover_settings['text_layers']) ) {
                $layers = $cover_settings['text_layers'];
                usort($layers, function($a, $b) {
                    $za = isset($a['zIndex']) ? intval($a['zIndex']) : 0;
                    $zb = isset($b['zIndex']) ? intval($b['zIndex']) : 0;
                    return $za - $zb;
                });

                foreach ( $layers as $layer ) {
                    if ( empty($layer['id']) ) continue;
                    
                    $x = isset($layer['x']) ? floatval($layer['x']) : 0;
                    $y = isset($layer['y']) ? floatval($layer['y']) : 0;
                    $rot = isset($layer['rotation']) ? floatval($layer['rotation']) : 0;
                    $zIndex = isset($layer['zIndex']) ? intval($layer['zIndex']) : 30;
                    $type = isset($layer['type']) ? $layer['type'] : 'text';

                    $style = "position: absolute; left: {$x}%; top: {$y}%; transform: rotate({$rot}deg); z-index: {$zIndex}; ";

                    if ($type === 'image' && !empty($layer['url'])) {
                        $lw = isset($layer['width']) ? floatval($layer['width']) : 200;
                        $lh = isset($layer['height']) ? floatval($layer['height']) : 200;
                        $style .= "width: {$lw}px; height: {$lh}px; background-image: url('".esc_url($layer['url'])."'); background-size: contain; background-repeat: no-repeat; background-position: center;";
                        echo "<div style=\"{$style}\"></div>";
                    } elseif ($type === 'shape') {
                        $lw = isset($layer['width']) ? floatval($layer['width']) : 150;
                        $lh = isset($layer['height']) ? floatval($layer['height']) : 150;
                        $opacity = isset($layer['opacity']) ? floatval($layer['opacity'])/100 : 1;
                        $shapeType = isset($layer['shapeType']) ? $layer['shapeType'] : 'rectangle';
                        $br = ($shapeType === 'circle') ? '50%' : '0';
                        
                        $hex1 = isset($layer['color1']) ? $layer['color1'] : '#000000';
                        $op1 = isset($layer['color1Opacity']) ? floatval($layer['color1Opacity']) / 100 : 1;
                        if (preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/i', $hex1, $m)) {
                            $c1 = "rgba(" . hexdec($m[1]) . ", " . hexdec($m[2]) . ", " . hexdec($m[3]) . ", {$op1})";
                        } else {
                            $c1 = $hex1;
                        }

                        $isGradient = isset($layer['isGradient']) && $layer['isGradient'] === 'true';
                        if ($isGradient) {
                            $hex2 = isset($layer['color2']) ? $layer['color2'] : '#ffffff';
                            $op2 = isset($layer['color2Opacity']) ? floatval($layer['color2Opacity']) / 100 : 1;
                            if (preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/i', $hex2, $m)) {
                                $c2 = "rgba(" . hexdec($m[1]) . ", " . hexdec($m[2]) . ", " . hexdec($m[3]) . ", {$op2})";
                            } else {
                                $c2 = $hex2;
                            }
                            $angle = isset($layer['gradientAngle']) ? $layer['gradientAngle'] : '90';
                            $bg = "linear-gradient({$angle}deg, {$c1}, {$c2})";
                        } else {
                            $bg = $c1;
                        }
                        
                        $style .= "width: {$lw}px; height: {$lh}px; opacity: {$opacity}; border-radius: {$br}; background: {$bg};";
                        echo "<div style=\"{$style}\"></div>";
                    } else {
                        // text
                        $fontSize = isset($layer['fontSize']) ? floatval($layer['fontSize']) : 12;
                        $fontWeight = isset($layer['fontWeight']) ? sanitize_text_field($layer['fontWeight']) : '400';
                        $fontStyle = isset($layer['fontStyle']) ? sanitize_text_field($layer['fontStyle']) : 'normal';
                        $lineHeight = isset($layer['lineHeight']) && $layer['lineHeight'] !== '' ? floatval($layer['lineHeight']) : 1.2;
                        $letterSpacing = isset($layer['letterSpacing']) && $layer['letterSpacing'] !== '' ? floatval($layer['letterSpacing']) : 0;
                        $color = isset($layer['color']) ? esc_attr($layer['color']) : '#000000';
                        $fontFamily = isset($layer['fontFamily']) ? esc_attr($layer['fontFamily']) : 'Inter';
                        $textAlign = isset($layer['textAlign']) ? esc_attr($layer['textAlign']) : 'center';
                        $w = isset($layer['width']) && $layer['width'] ? floatval($layer['width']).'px' : 'auto';
                        $h = isset($layer['height']) && $layer['height'] ? floatval($layer['height']).'px' : 'auto';
                        $text = isset($layer['text']) ? esc_html($layer['text']) : '';
                        $hyphens = !empty($layer['hyphens']) ? 'auto' : 'none';

                        $style .= "width: {$w}; height: {$h}; font-size: {$fontSize}px; font-weight: {$fontWeight}; font-style: {$fontStyle}; color: {$color}; font-family: '{$fontFamily}', sans-serif; text-align: {$textAlign}; white-space: pre-wrap; line-height: {$lineHeight}; letter-spacing: {$letterSpacing}px; font-synthesis: none; hyphens: {$hyphens}; -webkit-hyphens: {$hyphens};";
                        echo "<div style=\"{$style}\">{$text}</div>";
                    }
                }
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
