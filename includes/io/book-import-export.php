<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once dirname( __FILE__ ) . '/../helpers/cover-thumbnail.php';

// Register admin-post action handlers
add_action( 'admin_post_almaden_download_book', 'almaden_bookster_handle_download_book' );
add_action( 'admin_post_nopriv_almaden_download_book', 'almaden_bookster_handle_download_book' );

add_action( 'admin_post_almaden_upload_book', 'almaden_bookster_handle_upload_book' );
add_action( 'admin_post_nopriv_almaden_upload_book', 'almaden_bookster_handle_upload_book' );
add_action( 'admin_post_almaden_export_cover_pdf', 'almaden_bookster_handle_export_cover_pdf' );

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
		wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported_error' => 'no_file' ) ) );
		exit;
	}

	$zip_file = $_FILES['book_zip']['tmp_name'];

	// 1. Extract ZIP
	$zip = new ZipArchive();
	if ( $zip->open( $zip_file ) !== true ) {
		wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported_error' => 'invalid_zip' ) ) );
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
		wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported_error' => 'no_json' ) ) );
		exit;
	}

	$package = json_decode( file_get_contents( $json_path ), true );
	if ( ! is_array( $package ) || empty( $package['book'] ) ) {
		almaden_bookster_rrmdir( $temp_dir );
		wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported_error' => 'invalid_json' ) ) );
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
		wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported_error' => 'create_failed' ) ) );
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
	wp_safe_redirect( almaden_bookster_get_creator_page_url( array( 'book_imported' => '1' ) ) );
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

/**
 * Resolve a local image URL into a data URI for self-contained PDF rendering.
 */
function almaden_bookster_cover_export_url_to_data_uri( $url ) {
	if ( empty( $url ) ) {
		return '';
	}

	$attachment_id = attachment_url_to_postid( $url );
	$file_path = '';
	if ( $attachment_id > 0 ) {
		$file_path = get_attached_file( $attachment_id );
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

	$page_width_mm = $page_width * 10;
	$page_height_mm = $page_height * 10;

	$bleed_mm = 5.0;
	$front_flap_mm = isset( $cover_settings['front_flap_width'] ) ? floatval( $cover_settings['front_flap_width'] ) : ( isset( $cover_settings['front_flap'] ) ? floatval( $cover_settings['front_flap'] ) : 0 );
	$back_flap_mm  = isset( $cover_settings['back_flap_width'] ) ? floatval( $cover_settings['back_flap_width'] ) : ( isset( $cover_settings['back_flap'] ) ? floatval( $cover_settings['back_flap'] ) : 0 );
	if ( $front_flap_mm < 0 ) {
		$front_flap_mm = 0;
	}
	if ( $back_flap_mm < 0 ) {
		$back_flap_mm = 0;
	}

	$spine_width_mm = function_exists( 'almaden_bookster_get_cover_spine_width_mm' )
		? almaden_bookster_get_cover_spine_width_mm( $cover_settings, $pages )
		: ( isset( $cover_settings['spine_width_mm'] ) ? floatval( $cover_settings['spine_width_mm'] ) : 0 );

	if ( $spine_width_mm <= 0 ) {
		$spine_width_mm = max( 0.01, ( isset( $cover_settings['paper_type'] ) ? floatval( $cover_settings['paper_type'] ) : 0.06 ) * $pages );
	}

	$front_flap_render_mm = $front_flap_mm > 0 ? $front_flap_mm + $bleed_mm : 0;
	$back_flap_render_mm  = $back_flap_mm > 0 ? $back_flap_mm + $bleed_mm : 0;
	$front_cover_render_mm = $page_width_mm + ( $front_flap_mm > 0 ? 0 : $bleed_mm );
	$back_cover_render_mm  = $page_width_mm + ( $back_flap_mm > 0 ? 0 : $bleed_mm );
	$total_width_mm  = $back_flap_render_mm + $back_cover_render_mm + $spine_width_mm + $front_cover_render_mm + $front_flap_render_mm;
	$total_height_mm = $page_height_mm + ( 2 * $bleed_mm );

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
		$html .= '<link href="' . esc_url( $google_fonts_url ) . '" rel="stylesheet">';
	}
	$html .= '<style>';
	$html .= 'html,body{margin:0;padding:0;width:100%;height:100%;background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact;font-family:serif;}';
	$html .= '@page{size:' . $total_width_mm . 'mm ' . $total_height_mm . 'mm;margin:0;}';
	$html .= '#export-page{width:' . $total_width_mm . 'mm;height:' . $total_height_mm . 'mm;overflow:hidden;position:relative;background:#fff;}';
	$html .= '#export-spread{position:relative;display:flex;width:100%;height:100%;overflow:hidden;' . ( $spread_image_data ? 'background-image:url(' . esc_attr( $spread_image_data ) . ');background-size:cover;background-position:center;background-repeat:no-repeat;' : '' ) . '}';
	$html .= '.export-part{position:relative;flex-shrink:0;height:100%;overflow:hidden;background-size:cover;background-position:center;background-repeat:no-repeat;}';
	$html .= '.export-layer{position:absolute;pointer-events:none;box-sizing:border-box;}';
	$html .= '.export-layer--image{overflow:hidden;}';
	$html .= '.export-layer--shape{overflow:hidden;}';
	$html .= '</style></head><body><div id="export-page"><div id="export-spread">';

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

		$html .= '<div class="export-layer" style="' . esc_attr( $common_style . 'width:' . $width . ';height:' . $height . ';font-size:' . $font_size . 'px;color:' . $color . ';font-family:"' . esc_attr( $font_family_css ) . '",serif;text-align:' . $text_align . ';white-space:pre-wrap;line-height:1.2;hyphens:' . $hyphens . ';-webkit-hyphens:' . $hyphens . ';' ) . '">' . $text . '</div>';
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

/**
 * Find the Chrome binary used for headless PDF generation.
 */
function almaden_bookster_find_chrome_binary() {
	$candidates = array(
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/Applications/Chromium.app/Contents/MacOS/Chromium',
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}

	$names = array( 'google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser' );
	foreach ( $names as $name ) {
		$found = trim( (string) shell_exec( 'command -v ' . escapeshellarg( $name ) . ' 2>/dev/null' ) );
		if ( ! empty( $found ) && file_exists( $found ) ) {
			return $found;
		}
	}

	return '';
}

/**
 * Find the Ghostscript binary used to convert the PDF to CMYK.
 */
function almaden_bookster_find_ghostscript_binary() {
	$candidates = array(
		'/Applications/Local.app/Contents/Resources/extraResources/lightning-services/php-8.2.29+0/bin/darwin-arm64/ghostscript/bin/gs',
		'/Applications/Local.app/Contents/Resources/extraResources/lightning-services/php-8.4.6+0/bin/darwin-arm64/ghostscript/bin/gs',
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) && is_executable( $candidate ) ) {
			return $candidate;
		}
	}

	$found = trim( (string) shell_exec( 'command -v gs 2>/dev/null' ) );
	if ( ! empty( $found ) && file_exists( $found ) ) {
		return $found;
	}

	return '';
}

/**
 * Execute a shell command and capture stdout/stderr.
 */
function almaden_bookster_run_process( $command, &$stdout = '', &$stderr = '', $timeout_seconds = 60 ) {
	$stdout = '';
	$stderr = '';

	if ( ! function_exists( 'proc_open' ) ) {
		return new WP_Error( 'process_unavailable', 'La función proc_open no está disponible en este servidor.' );
	}

	$descriptors = array(
		1 => array( 'pipe', 'w' ),
		2 => array( 'pipe', 'w' ),
	);

	$process = proc_open( $command, $descriptors, $pipes );
	if ( ! is_resource( $process ) ) {
		return new WP_Error( 'process_open_failed', 'No se pudo iniciar el proceso externo.' );
	}

	stream_set_blocking( $pipes[1], false );
	stream_set_blocking( $pipes[2], false );

	$start = microtime( true );
	$exit_code = null;

	while ( true ) {
		$status = proc_get_status( $process );
		if ( false === $status ) {
			break;
		}

		$stdout .= stream_get_contents( $pipes[1] );
		$stderr .= stream_get_contents( $pipes[2] );

		if ( ! $status['running'] ) {
			$exit_code = $status['exitcode'];
			break;
		}

		if ( ( microtime( true ) - $start ) > $timeout_seconds ) {
			proc_terminate( $process, 9 );
			$stderr .= "\nProceso cancelado por exceder el tiempo límite.";
			$exit_code = 124;
			break;
		}

		usleep( 250000 );
	}

	$stdout .= stream_get_contents( $pipes[1] );
	$stderr .= stream_get_contents( $pipes[2] );
	fclose( $pipes[1] );
	fclose( $pipes[2] );
	if ( null === $exit_code ) {
		$exit_code = proc_close( $process );
	} elseif ( 0 === $exit_code ) {
		proc_close( $process );
	} else {
		// When a timeout or failure occurs, avoid blocking on proc_close().
		@proc_terminate( $process, 9 );
	}

	if ( 0 !== $exit_code ) {
		return new WP_Error( 'process_failed', trim( $stderr ) !== '' ? trim( $stderr ) : 'El proceso terminó con código ' . intval( $exit_code ) . '.' );
	}

	return true;
}
