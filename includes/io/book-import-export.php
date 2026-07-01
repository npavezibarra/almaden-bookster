<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register admin-post action handlers
add_action( 'admin_post_almaden_download_book', 'almaden_bookster_handle_download_book' );
add_action( 'admin_post_nopriv_almaden_download_book', 'almaden_bookster_handle_download_book' );

add_action( 'admin_post_almaden_upload_book', 'almaden_bookster_handle_upload_book' );
add_action( 'admin_post_nopriv_almaden_upload_book', 'almaden_bookster_handle_upload_book' );

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
		'author'  => get_post_meta( $book_id, 'book_author', true ),
		'formats' => get_post_meta( $book_id, '_almaden_formats', true ),
		'size'    => get_post_meta( $book_id, '_almaden_book_size', true ),
		'is_published' => get_post_meta( $book_id, '_almaden_is_published', true ),
		'wc_product_id' => (int) get_post_meta( $book_id, '_almaden_wc_product_id', true ),
		'cover_settings' => get_post_meta( $book_id, '_almaden_cover_settings', true ),
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
		'_parity_image', '_hide_title', '_hide_all_headers_footers', '_exclude_from_numbering', '_custom_running_header',
		'_subtitle_text', '_subtitle_font_family', '_subtitle_align', '_subtitle_font_size', '_subtitle_letter_spacing',
		'_subtitle_font_style', '_subtitle_text_transform', '_subtitle_font_weight', '_subtitle_margin_top', '_subtitle_margin_bottom',
		'_drop_cap_enabled', '_disable_hyphenation',
		// Legacy compatibility keys. They may exist in older exports but are no longer part of the active editor flow.
		'_page_one_vertical',
		'_start_parity', '_first_page_header_type',
		'_first_page_header_custom', '_first_page_footer_type', '_first_page_footer_custom', '_parity_image_mode',
		'_parity_image_width', '_parity_image_height', '_is_toc', '_is_credits', '_credits_font_family', '_credits_align',
		'_credits_font_size', '_credits_letter_spacing', '_credits_font_weight', '_credits_hide_page_number', '_toc_font_family', '_toc_font_size',
		'_toc_enumerate', '_toc_font_style', '_toc_font_weight', '_toc_text_transform', '_toc_letter_spacing', '_toc_line_height',
		'_toc_item_spacing', '_toc_leader_style', '_toc_leader_position', '_toc_title_align',
		'_toc_page_one_vertical',
		'_toc_title_font_family', '_toc_title_font_size', '_toc_title_font_style', '_toc_title_text_transform', '_toc_title_font_weight',
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
		// Resolve local path if possible
		$attach_id = attachment_url_to_postid( $url );
		$filepath = '';
		if ( $attach_id > 0 ) {
			$filepath = get_attached_file( $attach_id );
		} else {
			// Try matching path relative to uploads dir
			$uploads = wp_upload_dir();
			$baseurl = $uploads['baseurl'];
			if ( strpos( $url, $baseurl ) === 0 ) {
				$rel_path = str_replace( $baseurl, '', $url );
				$filepath = $uploads['basedir'] . $rel_path;
			}
		}

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
		'version'    => '1.0.0',
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
 * Handle Upload (Import) of a book
 */
function almaden_bookster_handle_upload_book() {
	if ( ! isset( $_POST['almaden_upload_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_upload_nonce'], 'almaden_upload_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	if ( empty( $_FILES['book_zip']['tmp_name'] ) ) {
		wp_safe_redirect( home_url( '/almaden-booklist/?book_imported_error=no_file' ) );
		exit;
	}

	$zip_file = $_FILES['book_zip']['tmp_name'];

	// 1. Extract ZIP
	$zip = new ZipArchive();
	if ( $zip->open( $zip_file ) !== true ) {
		wp_safe_redirect( home_url( '/almaden-booklist/?book_imported_error=invalid_zip' ) );
		exit;
	}

	$temp_dir = WP_CONTENT_DIR . '/uploads/almaden_tmp_' . uniqid();
	if ( ! file_exists( $temp_dir ) ) {
		wp_mkdir_p( $temp_dir );
	}

	$zip->extractTo( $temp_dir );
	$zip->close();

	$json_path = $temp_dir . '/book.json';
	if ( ! file_exists( $json_path ) ) {
		// Clean up
		almaden_bookster_rrmdir( $temp_dir );
		wp_safe_redirect( home_url( '/almaden-booklist/?book_imported_error=no_json' ) );
		exit;
	}

	$package = json_decode( file_get_contents( $json_path ), true );
	if ( ! is_array( $package ) || empty( $package['book'] ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		wp_safe_redirect( home_url( '/almaden-booklist/?book_imported_error=invalid_json' ) );
		exit;
	}

	// 2. Import Images
	$url_mapping = array();
	if ( ! empty( $package['images_map'] ) && is_array( $package['images_map'] ) ) {
		foreach ( $package['images_map'] as $old_url => $zip_filename ) {
			$local_img_path = $temp_dir . '/' . $zip_filename;
			if ( file_exists( $local_img_path ) ) {
				$filename = basename( $local_img_path );
				$upload = wp_upload_bits( $filename, null, file_get_contents( $local_img_path ) );
				if ( ! empty( $upload['url'] ) && empty( $upload['error'] ) ) {
					// Register attachment
					$attachment = array(
						'guid'           => $upload['url'],
						'post_mime_type' => $upload['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					$attach_id = wp_insert_attachment( $attachment, $upload['file'] );
					if ( ! is_wp_error( $attach_id ) ) {
						require_once( ABSPATH . 'wp-admin/includes/image.php' );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
						wp_update_attachment_metadata( $attach_id, $attach_data );
					}
					$url_mapping[$old_url] = $upload['url'];
				}
			}
		}
	}

	// Helper to replace old image URLs
	$replace_urls = function( $text ) use ( $url_mapping ) {
		if ( empty( $text ) ) return $text;
		foreach ( $url_mapping as $old => $new ) {
			$text = str_replace( $old, $new, $text );
		}
		return $text;
	};

	// 3. Create Book Post
	$book_info = $package['book'];
	$book_title = $book_info['title'] . ' (Importado)';
	$book_post_id = wp_insert_post( array(
		'post_title'   => $book_title,
		'post_content' => $book_info['content'],
		'post_status'  => 'publish',
		'post_type'    => 'almaden-books',
	) );

	if ( is_wp_error( $book_post_id ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		wp_safe_redirect( home_url( '/almaden-booklist/?book_imported_error=create_failed' ) );
		exit;
	}

	// Update meta
	update_post_meta( $book_post_id, 'book_author', $book_info['author'] );
	if ( ! empty( $book_info['formats'] ) ) {
		update_post_meta( $book_post_id, '_almaden_formats', $book_info['formats'] );
	}
	if ( ! empty( $book_info['size'] ) ) {
		update_post_meta( $book_post_id, '_almaden_book_size', $book_info['size'] );
	}
	if ( ! empty( $book_info['wc_product_id'] ) ) {
		update_post_meta( $book_post_id, '_almaden_wc_product_id', intval( $book_info['wc_product_id'] ) );
	}
	update_post_meta( $book_post_id, '_almaden_is_published', $book_info['is_published'] );
	update_post_meta( $book_post_id, '_almaden_credits_blank_before', intval( $book_info['credits_blank_before'] ?? 0 ) );
	update_post_meta( $book_post_id, '_almaden_credits_blank_after', intval( $book_info['credits_blank_after'] ?? 0 ) );
	
	// Map cover settings image URLs
	$cover_settings = $book_info['cover_settings'];
	if ( ! empty( $cover_settings ) && is_array( $cover_settings ) ) {
		$img_keys = array( 'front_image', 'back_image', 'spread_image', 'spine_image', 'front_flap_image', 'back_flap_image' );
		foreach ( $img_keys as $k ) {
			if ( ! empty( $cover_settings[$k] ) && isset( $url_mapping[$cover_settings[$k]] ) ) {
				$cover_settings[$k] = $url_mapping[$cover_settings[$k]];
			}
		}

		if ( ! empty( $cover_settings['text_layers'] ) && is_array( $cover_settings['text_layers'] ) ) {
			$cover_settings['text_layers'] = array_map(
				function( $layer ) use ( $url_mapping ) {
					if ( ! is_array( $layer ) ) {
						return $layer;
					}

					if ( ! empty( $layer['type'] ) && $layer['type'] === 'image' && ! empty( $layer['url'] ) && isset( $url_mapping[ $layer['url'] ] ) ) {
						$layer['url'] = $url_mapping[ $layer['url'] ];
					}

					if ( ! empty( $layer['children'] ) && is_array( $layer['children'] ) ) {
						$layer['children'] = array_map(
							function( $child ) use ( $url_mapping ) {
								if ( ! is_array( $child ) ) {
									return $child;
								}

								if ( ! empty( $child['type'] ) && $child['type'] === 'image' && ! empty( $child['url'] ) && isset( $url_mapping[ $child['url'] ] ) ) {
									$child['url'] = $url_mapping[ $child['url'] ];
								}

								return $child;
							},
							$layer['children']
						);
					}

					return $layer;
				},
				$cover_settings['text_layers']
			);
		}

		update_post_meta( $book_post_id, '_almaden_cover_settings', $cover_settings );
	}

	// 4. Save Table Settings
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	if ( ! empty( $package['settings'] ) && is_array( $package['settings'] ) && $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
		$db_row = $package['settings'];
		$db_row['book_id'] = $book_post_id;
		$wpdb->insert( $table_name, $db_row );
	}

	// 5. Import Chapters
	if ( ! empty( $package['chapters'] ) && is_array( $package['chapters'] ) ) {
		foreach ( $package['chapters'] as $ch ) {
			$ch_content = $replace_urls( $ch['content'] );
			
			$ch_id = wp_insert_post( array(
				'post_title'   => $ch['title'],
				'post_content' => $ch_content,
				'post_status'  => 'publish',
				'post_type'    => 'book_chapter',
				'post_parent'  => $book_post_id,
				'menu_order'   => $ch['menu_order'],
			) );

			if ( ! is_wp_error( $ch_id ) && ! empty( $ch['meta'] ) && is_array( $ch['meta'] ) ) {
				foreach ( $ch['meta'] as $meta_key => $meta_val ) {
					if ( $meta_key === '_parity_image' && ! empty( $meta_val ) && isset( $url_mapping[$meta_val] ) ) {
						$meta_val = $url_mapping[$meta_val];
					}
					update_post_meta( $ch_id, $meta_key, $meta_val );
				}
			}
		}
	}

	// Clean up
	almaden_bookster_rrmdir( $temp_dir );
	wp_safe_redirect( home_url( '/almaden-booklist/?book_imported=1' ) );
	exit;
}

/**
 * Helper: Recursively delete a directory
 */
function almaden_bookster_rrmdir( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != "." && $object != ".." ) {
				if ( is_dir( $dir . "/" . $object ) && ! is_link( $dir . "/" . $object ) ) {
					almaden_bookster_rrmdir( $dir . "/" . $object );
				} else {
					unlink( $dir . "/" . $object );
				}
			}
		}
		rmdir( $dir );
	}
}
