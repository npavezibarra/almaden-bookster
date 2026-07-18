<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


add_action( 'admin_post_almaden_upload_book', 'almaden_bookster_handle_upload_book' );
add_action( 'admin_post_nopriv_almaden_upload_book', 'almaden_bookster_handle_upload_book' );

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
	update_post_meta( $book_post_id, '_almaden_book_author', $book_info['author'] );
	if ( ! empty( $book_info['authors'] ) && function_exists( 'almaden_bookster_set_book_authors' ) ) {
		almaden_bookster_set_book_authors( $book_post_id, $book_info['authors'], $book_info['author'] );
	} elseif ( function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
		almaden_bookster_sync_book_authors_from_input( $book_post_id, $book_info['author'] );
	}
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
	if ( function_exists( 'almaden_bookster_mark_publisher_tour_completed' ) ) {
		almaden_bookster_mark_publisher_tour_completed();
	}
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
