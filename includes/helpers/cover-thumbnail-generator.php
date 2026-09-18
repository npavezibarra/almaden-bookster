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

    $payload['snapshot_renderer_version'] = 'trim-v2-font-ready-v1';

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

    // Snapshots represent the finished trim box, not the printable bleed area.
    $aspect_ratio = $page_height_px > 0 ? ( $page_width_px / $page_height_px ) : 0.7071;
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
	<?php
	$bundled_fonts_css_path = dirname( dirname( dirname( __FILE__ ) ) ) . '/assets/fonts/bundled/bundled-fonts.css';
	if ( file_exists( $bundled_fonts_css_path ) ) {
		$bundled_css = file_get_contents( $bundled_fonts_css_path );
		$bundled_dir_file_url = 'file://' . str_replace( ' ', '%20', dirname( dirname( dirname( __FILE__ ) ) ) ) . '/assets/fonts/bundled/';
		$bundled_css = preg_replace_callback( '/url\(\s*[\'"]?\.\/([^\'")]+)[\'"]?\s*\)/i', function( $matches ) use ( $bundled_dir_file_url ) {
			return "url('" . $bundled_dir_file_url . $matches[1] . "')";
		}, $bundled_css );
		echo '<style>' . $bundled_css . '</style>';
	} elseif ( function_exists( 'almaden_bookster_get_bundled_fonts_stylesheet_url' ) ) {
		echo '<link rel="stylesheet" href="' . esc_url( almaden_bookster_get_bundled_fonts_stylesheet_url() ) . '">';
	}
	?>
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
		#almaden-cover-snapshot-root .cover-thumbnail-wrapper[data-snapshot-pending="1"] {
			visibility: hidden;
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
			async function initSnapshot() {
				const wrapper = document.querySelector('.cover-thumbnail-wrapper');
				if (!wrapper) return;
				wrapper.setAttribute('data-snapshot-pending', '1');

				if (document.fonts) {
					const requests = Array.from(wrapper.querySelectorAll('[data-cover-text-layer="1"]')).map((layer) => {
						const computed = window.getComputedStyle(layer);
						return document.fonts.load(`${computed.fontStyle} ${computed.fontWeight} ${computed.fontSize} ${computed.fontFamily}`, layer.textContent || 'Ag');
					});
					try {
						await Promise.all(requests);
						await document.fonts.ready;
					} catch (error) {
						document.documentElement.setAttribute('data-snapshot-font-error', '1');
					}
				}

				scaleThumbnails();
				wrapper.removeAttribute('data-snapshot-pending');
				document.documentElement.setAttribute('data-snapshot-ready', '1');
			}

			function scaleThumbnails() {
				const wrapper = document.querySelector('.cover-thumbnail-wrapper');
				if (!wrapper) return;

				const targetWidth = wrapper.clientWidth;
				const frontCoverPx = parseFloat(wrapper.getAttribute('data-front-cover-px'));
				const startPx = parseFloat(wrapper.getAttribute('data-start-px'));
				const startYPx = parseFloat(wrapper.getAttribute('data-start-y-px')) || 0;
				if (frontCoverPx > 0) {
					const scale = targetWidth / frontCoverPx;
					const spread = wrapper.querySelector('.cover-spread-container');
					if (spread) {
						spread.style.transform = `scale(${scale}) translate(${-startPx}px, ${-startYPx}px)`;
					}
				}
			}

			window.addEventListener('resize', scaleThumbnails);
			window.addEventListener('load', initSnapshot);
			setTimeout(initSnapshot, 100);
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
		// Current Chrome builds on macOS can keep background processes alive
		// after reporting that the screenshot was written. The requested file is
		// the authoritative success signal for this one-shot renderer.
		if ( empty( $expected_file ) || ! file_exists( $expected_file ) || filesize( $expected_file ) <= 0 ) {
			return $result;
		}
	}

	if ( empty( $expected_file ) || ! file_exists( $expected_file ) ) {
		return new WP_Error( 'snapshot_output_missing', 'No se generó el archivo esperado.' );
	}

	return true;
}

function almaden_bookster_generate_cover_thumbnail_snapshot( $book_id, array $payload = array(), $force = false ) {
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
	if ( ! $force && ! empty( $existing_snapshot['version'] ) && $existing_snapshot['version'] === $version && ! empty( $existing_snapshot['attachment_id'] ) && ! empty( $existing_snapshot['url'] ) ) {
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
		'--disable-crash-reporter',
		'--disable-background-networking',
		'--disable-component-update',
		'--disable-default-apps',
		'--disable-sync',
		'--metrics-recording-only',
		'--no-first-run',
		'--user-data-dir=' . $temp_dir . '/user-data',
		'--allow-file-access-from-files',
		'--disable-web-security',
		'--allow-running-insecure-content',
		'--ignore-certificate-errors',
		'--allow-insecure-localhost',
		'--font-render-hinting=full',
		'--run-all-compositor-stages-before-draw',
		'--hide-scrollbars',
		'--force-device-scale-factor=1',
		'--timeout=5000',
		'--window-size=' . $viewport_width_px . ',' . $viewport_height_px,
		'--screenshot=' . $png_file,
		'file://' . $html_file,
	);

	$chrome_result = almaden_bookster_run_command_to_file( $chrome_command, $png_file, 15 );
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
