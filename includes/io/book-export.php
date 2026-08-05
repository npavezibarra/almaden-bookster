<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


// Register admin-post action handlers
add_action( 'admin_post_almaden_download_book', 'almaden_bookster_handle_download_book' );
add_action( 'admin_post_nopriv_almaden_download_book', 'almaden_bookster_handle_download_book' );

/**
 * Helper: Scan string for image URLs in wp-content/uploads/
 */
function almaden_bookster_scan_images( $content ) {
	$images = array();
	if ( empty( $content ) ) {
		return $images;
	}
	preg_match_all( '/src=["\']([^"\']+\.(?:png|jpe?g|gif|webp|svg))["\']/i', $content, $matches );
	if ( ! empty( $matches[1] ) ) {
		foreach ( $matches[1] as $url ) {
			$images[] = $url;
		}
	}
	return $images;
}

/**
 * Helper: Recursively scan cover layers for image URLs.
 */
function almaden_bookster_scan_cover_layer_images( $layers ) {
	$images = array();

	if ( empty( $layers ) || ! is_array( $layers ) ) {
		return $images;
	}

	foreach ( $layers as $layer ) {
		if ( ! is_array( $layer ) ) {
			continue;
		}

		if ( ! empty( $layer['type'] ) && $layer['type'] === 'image' && ! empty( $layer['url'] ) ) {
			$images[] = $layer['url'];
		}

		if ( ! empty( $layer['children'] ) && is_array( $layer['children'] ) ) {
			$images = array_merge( $images, almaden_bookster_scan_cover_layer_images( $layer['children'] ) );
		}
	}

	return $images;
}

/**
 * Handle Download (Export) of a book
 */
function almaden_bookster_handle_download_book() {
	if ( ! isset( $_POST['almaden_download_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_download_nonce'], 'almaden_download_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_die( 'ID de libro inválido.' );
	}

	$book_post = get_post( $book_id );
	if ( ! $book_post || $book_post->post_type !== 'almaden-books' ) {
		wp_die( 'Libro no encontrado.' );
	}

	// 1. Gather book information
	$book_data = array(
		'title'   => $book_post->post_title,
		'content' => $book_post->post_content,
		'author'  => function_exists( 'almaden_bookster_get_book_author_display_label' ) ? almaden_bookster_get_book_author_display_label( $book_id, get_post_meta( $book_id, 'book_author', true ) ) : get_post_meta( $book_id, 'book_author', true ),
		'authors' => function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $book_id ) : array(),
		'formats' => get_post_meta( $book_id, '_almaden_formats', true ),
		'size'    => get_post_meta( $book_id, '_almaden_book_size', true ),
		'is_published' => get_post_meta( $book_id, '_almaden_is_published', true ),
		'wc_product_id' => (int) get_post_meta( $book_id, '_almaden_wc_product_id', true ),
		'cover_settings' => get_post_meta( $book_id, '_almaden_cover_settings', true ),
		'credits_config' => function_exists( 'almaden_bookster_normalize_credits_config' )
			? almaden_bookster_normalize_credits_config(
				get_post_meta( $book_id, '_almaden_credits_config', true ),
				array(
					'credits_edition'      => get_post_meta( $book_id, '_almaden_credits_edition', true ),
					'credits_date'         => get_post_meta( $book_id, '_almaden_credits_date', true ),
					'credits_isbn'         => get_post_meta( $book_id, '_almaden_credits_isbn', true ),
					'credits_copyright'    => get_post_meta( $book_id, '_almaden_credits_copyright', true ),
					'credits_printer'      => get_post_meta( $book_id, '_almaden_credits_printer', true ),
					'credits_blank_before' => (int) get_post_meta( $book_id, '_almaden_credits_blank_before', true ),
					'credits_blank_after'  => (int) get_post_meta( $book_id, '_almaden_credits_blank_after', true ),
					'credits_license'      => get_post_meta( $book_id, '_almaden_credits_license', true ),
					'credits_custom'       => get_post_meta( $book_id, '_almaden_credits_custom', true ),
				)
			)
			: array(),
		'credits_blank_before' => (int) get_post_meta( $book_id, '_almaden_credits_blank_before', true ),
		'credits_blank_after' => (int) get_post_meta( $book_id, '_almaden_credits_blank_after', true ),
	);

	// 2. Fetch custom table settings
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	$db_settings = array();
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE book_id = %d", $book_id ), ARRAY_A );
		if ( $row ) {
			$db_settings = $row;
			unset( $db_settings['id'] );
			unset( $db_settings['book_id'] );
		}
	}

	// 3. Fetch chapters
	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}
	$chapters_query = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	) );

	$chapters = array();
	$all_meta_keys = array(
		'_parity_image', '_hide_title', '_hide_header', '_hide_footer', '_hide_all_headers_footers', '_exclude_from_numbering', '_custom_running_header',
		'_opening_block_horizontal_align', '_opening_block_vertical_align',
		'_subtitle_text', '_subtitle_font_family', '_subtitle_align', '_subtitle_font_size', '_subtitle_letter_spacing',
		'_subtitle_font_style', '_subtitle_text_transform', '_subtitle_font_weight', '_subtitle_margin_top', '_subtitle_margin_bottom',
		'_drop_cap_enabled', '_disable_hyphenation',
		// Legacy compatibility keys. They may exist in older exports but are no longer part of the active editor flow.
		'_page_one_vertical',
		'_start_parity', '_first_page_header_type',
			'_first_page_header_custom', '_first_page_footer_type', '_first_page_footer_custom', '_opening_separate_content', '_chapter_image_mode', '_chapter_image_url', '_chapter_image_inner_width', '_chapter_image_inner_header', '_chapter_image_inner_footer', '_parity_image_mode',
			'_chapter_image_enabled',
			'_parity_image_width', '_parity_image_height', '_is_toc', '_is_credits', '_credits_font_family', '_credits_align',
			'_credits_font_size', '_credits_letter_spacing', '_credits_font_weight', '_credits_hide_header', '_credits_hide_page_number', '_credits_margin_top', '_credits_margin_bottom', '_toc_font_family', '_toc_font_size',
			'_toc_enumerate', '_toc_font_style', '_toc_font_weight', '_toc_text_transform', '_toc_letter_spacing', '_toc_line_height',
		'_toc_item_spacing', '_toc_leader_style', '_toc_leader_position', '_toc_hide_title', '_toc_title_text', '_toc_title_align',
		'_toc_page_one_vertical',
		'_toc_separate_opening_content',
		'_toc_title_font_family', '_toc_title_font_size', '_toc_title_font_style', '_toc_title_text_transform', '_toc_title_font_weight', '_toc_title_letter_spacing',
		'_toc_title_padding_top', '_toc_title_padding_bottom', '_toc_title_line_height'
	);

	$image_urls = array();

	// Cover images
	if ( ! empty( $book_data['cover_settings'] ) && is_array( $book_data['cover_settings'] ) ) {
		$img_keys = array( 'front_image', 'back_image', 'spread_image', 'spine_image', 'front_flap_image', 'back_flap_image' );
		foreach ( $img_keys as $k ) {
			if ( ! empty( $book_data['cover_settings'][$k] ) ) {
				$image_urls[] = $book_data['cover_settings'][$k];
			}
		}

		if ( ! empty( $book_data['cover_settings']['text_layers'] ) && is_array( $book_data['cover_settings']['text_layers'] ) ) {
			$image_urls = array_merge(
				$image_urls,
				almaden_bookster_scan_cover_layer_images( $book_data['cover_settings']['text_layers'] )
			);
		}
	}

	foreach ( $chapters_query as $c ) {
		$c_meta = array();
		foreach ( $all_meta_keys as $mk ) {
			$val = get_post_meta( $c->ID, $mk, true );
			$c_meta[$mk] = $val;
			if ( $mk === '_parity_image' && ! empty( $val ) ) {
				$image_urls[] = $val;
			}
		}

		// Scan chapter content for images
		$scanned = almaden_bookster_scan_images( $c->post_content );
		if ( ! empty( $scanned ) ) {
			$image_urls = array_merge( $image_urls, $scanned );
		}

		$chapters[] = array(
			'title'      => $c->post_title,
			'content'    => $c->post_content,
			'menu_order' => $c->menu_order,
			'name'       => $c->post_name,
			'meta'       => $c_meta,
		);
	}

	$image_urls = array_unique( $image_urls );
	$images_map = array();

	// 4. Create Zip
	$zip = new ZipArchive();
	$temp_file = tempnam( sys_get_temp_dir(), 'book_zip' );
	if ( $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
		wp_die( 'No se pudo crear el archivo ZIP temporal.' );
	}

	$img_idx = 1;
	foreach ( $image_urls as $url ) {
		$filepath = almaden_bookster_resolve_image_file_path( $url );

		if ( ! empty( $filepath ) && file_exists( $filepath ) ) {
			$ext = pathinfo( $filepath, PATHINFO_EXTENSION );
			$zip_filename = 'images/img_' . $img_idx . '.' . $ext;
			if ( $zip->addFile( $filepath, $zip_filename ) ) {
				$images_map[$url] = $zip_filename;
				$img_idx++;
			}
		}
	}

	// Build JSON
	$package = array(
		'book'       => $book_data,
		'settings'   => $db_settings,
		'chapters'   => $chapters,
		'images_map' => $images_map,
	'version'    => '1.1.0',
	);

	$zip->addFromString( 'book.json', json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
	$zip->close();

	// Send file download headers
	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="book-' . sanitize_title( $book_post->post_title ) . '.zip"' );
	header( 'Content-Length: ' . filesize( $temp_file ) );
	header( 'Pragma: no-cache' );
	header( 'Expires: 0' );

	readfile( $temp_file );
	unlink( $temp_file );
	exit;
}

/**
 * Resolve a local image URL into the highest-resolution local file available.
 */
function almaden_bookster_resolve_image_file_path( $url ) {
	if ( empty( $url ) ) {
		return '';
	}

	$attachment_id = attachment_url_to_postid( $url );
	$file_path = '';
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

	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['baseurl'] ) && strpos( $url, $uploads['baseurl'] ) === 0 ) {
			$relative = str_replace( $uploads['baseurl'], '', $url );
			$file_path = $uploads['basedir'] . $relative;
		}
	}

	if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
		return '';
	}

	return $file_path;
}
