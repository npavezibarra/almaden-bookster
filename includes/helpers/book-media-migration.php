<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_book_media_migration_report_defaults() {
	return array(
		'version'             => 1,
		'last_run'            => '',
		'books_scanned'       => 0,
		'books_updated'       => 0,
		'attachments_found'   => 0,
		'attachments_moved'   => 0,
		'attachment_files_moved' => 0,
		'content_rewrites'    => 0,
		'meta_rewrites'       => 0,
		'conflicts'           => 0,
		'errors'              => 0,
		'message'             => '',
	);
}

function almaden_bookster_get_book_media_migration_report() {
	$report = get_option( 'almaden_bookster_book_media_migration_report', array() );

	if ( ! is_array( $report ) ) {
		$report = array();
	}

	return wp_parse_args( $report, almaden_bookster_get_book_media_migration_report_defaults() );
}

function almaden_bookster_store_book_media_migration_report( $report ) {
	$report = is_array( $report ) ? $report : array();
	update_option( 'almaden_bookster_book_media_migration_report', wp_parse_args( $report, almaden_bookster_get_book_media_migration_report_defaults() ) );
}

function almaden_bookster_collect_book_media_urls_from_value( $value, &$urls = array() ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $item ) {
			almaden_bookster_collect_book_media_urls_from_value( $item, $urls );
		}
		return;
	}

	if ( ! is_string( $value ) ) {
		return;
	}

	if ( preg_match_all( '#https?://[^\s"\'<>]+#i', $value, $matches ) ) {
		foreach ( $matches[0] as $url ) {
			$url = esc_url_raw( trim( rtrim( $url, ".,;)" ) ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}
	}
}

function almaden_bookster_resolve_attachment_id_from_book_media_url( $url ) {
	$url = esc_url_raw( (string) $url );
	if ( '' === $url ) {
		return 0;
	}

	$attachment_id = attachment_url_to_postid( $url );
	if ( $attachment_id <= 0 && preg_match( '/-scaled(?=\.[a-zA-Z0-9]+$)/', $url ) ) {
		$attachment_id = attachment_url_to_postid( preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+$)/', '', $url ) );
	}

	return absint( $attachment_id );
}

function almaden_bookster_get_book_media_attachment_ids_for_book( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return array();
	}

	$attachment_ids = array();
	$chapter_ids = get_posts(
		array(
			'post_type'              => 'book_chapter',
			'post_parent'            => $book_id,
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	$post_ids = array_merge( array( $book_id ), is_array( $chapter_ids ) ? $chapter_ids : array() );

	foreach ( $post_ids as $post_id ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 ) {
			continue;
		}

		$urls = array();
		$post = get_post( $post_id );
		if ( $post instanceof WP_Post ) {
			almaden_bookster_collect_book_media_urls_from_value( $post->post_content, $urls );
		}

		$meta = get_post_meta( $post_id );
		if ( is_array( $meta ) ) {
			foreach ( $meta as $meta_values ) {
				almaden_bookster_collect_book_media_urls_from_value( $meta_values, $urls );
			}
		}

		foreach ( $urls as $url ) {
			$attachment_id = almaden_bookster_resolve_attachment_id_from_book_media_url( $url );
			if ( $attachment_id > 0 ) {
				$attachment_ids[] = $attachment_id;
			}
		}
	}

	$attachment_posts = get_posts(
		array(
			'post_type'              => 'attachment',
			'post_parent__in'        => $post_ids,
			'post_status'            => 'inherit',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	if ( is_array( $attachment_posts ) ) {
		$attachment_ids = array_merge( $attachment_ids, $attachment_posts );
	}

	$attachment_ids = array_values( array_unique( array_filter( array_map( 'absint', $attachment_ids ) ) ) );
	return $attachment_ids;
}

function almaden_bookster_get_book_media_attachment_source_files( $attachment_id ) {
	$attachment_id = absint( $attachment_id );
	if ( $attachment_id <= 0 ) {
		return array();
	}

	$seeds = array();
	$attached_file = get_attached_file( $attachment_id );
	if ( ! empty( $attached_file ) ) {
		$seeds[] = $attached_file;
	}

	if ( function_exists( 'wp_get_original_image_path' ) ) {
		$original_file = wp_get_original_image_path( $attachment_id );
		if ( ! empty( $original_file ) ) {
			$seeds[] = $original_file;
		}
	}

	$uploads = wp_upload_dir();
	$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
	$files = array();

	foreach ( array_unique( array_filter( $seeds ) ) as $seed ) {
		$seed = (string) $seed;
		if ( '' === $seed || ! file_exists( $seed ) ) {
			continue;
		}
		if ( '' !== $basedir && 0 !== strpos( $seed, $basedir ) ) {
			continue;
		}

		$pattern = trailingslashit( dirname( $seed ) ) . pathinfo( $seed, PATHINFO_FILENAME ) . '*';
		$matches = glob( $pattern );
		if ( is_array( $matches ) ) {
			foreach ( $matches as $match ) {
				if ( is_string( $match ) && file_exists( $match ) ) {
					$files[] = $match;
				}
			}
		}
	}

	return array_values( array_unique( $files ) );
}

function almaden_bookster_book_media_path_to_url( $path ) {
	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
		return '';
	}

	$basedir = trailingslashit( wp_normalize_path( $uploads['basedir'] ) );
	$path    = wp_normalize_path( $path );
	if ( 0 !== strpos( $path, $basedir ) ) {
		return '';
	}

	return esc_url_raw( str_replace( $basedir, trailingslashit( $uploads['baseurl'] ), $path ) );
}

function almaden_bookster_rewrite_book_media_value( $value, $replacements, &$changed = false ) {
	$changed = false;

	if ( is_array( $value ) ) {
		$result = array();
		foreach ( $value as $key => $item ) {
			$item_changed = false;
			$result[ $key ] = almaden_bookster_rewrite_book_media_value( $item, $replacements, $item_changed );
			if ( $item_changed ) {
				$changed = true;
			}
		}
		return $result;
	}

	if ( ! is_string( $value ) || empty( $replacements ) ) {
		return $value;
	}

	$updated = str_replace( array_keys( $replacements ), array_values( $replacements ), $value );
	if ( $updated !== $value ) {
		$changed = true;
	}

	return $updated;
}

function almaden_bookster_update_book_media_post_meta( $post_id, $replacements, &$stats = array() ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 || empty( $replacements ) ) {
		return 0;
	}

	if ( ! is_array( $stats ) ) {
		$stats = array();
	}

	$meta = get_post_meta( $post_id );
	if ( ! is_array( $meta ) ) {
		return 0;
	}

	$updated = 0;
	foreach ( $meta as $meta_key => $values ) {
		if ( ! is_array( $values ) ) {
			continue;
		}

		$rewritten = array();
		$key_changed = false;
		foreach ( $values as $value ) {
			$value_changed = false;
			$rewritten[] = almaden_bookster_rewrite_book_media_value( $value, $replacements, $value_changed );
			if ( $value_changed ) {
				$key_changed = true;
			}
		}

		if ( ! $key_changed ) {
			continue;
		}

		delete_post_meta( $post_id, $meta_key );
		foreach ( $rewritten as $value ) {
			add_post_meta( $post_id, $meta_key, $value );
		}
		++$updated;
	}

	if ( $updated > 0 ) {
		$stats['meta_rewrites'] = isset( $stats['meta_rewrites'] ) ? absint( $stats['meta_rewrites'] ) + $updated : $updated;
	}

	return $updated;
}

function almaden_bookster_update_book_media_post_content( $post_id, $replacements, &$stats = array() ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 || empty( $replacements ) ) {
		return false;
	}

	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	$updated_content = str_replace( array_keys( $replacements ), array_values( $replacements ), (string) $post->post_content );
	if ( $updated_content === (string) $post->post_content ) {
		return false;
	}

	wp_update_post(
		array(
			'ID'           => $post_id,
			'post_content' => $updated_content,
		)
	);

	$stats['content_rewrites'] = isset( $stats['content_rewrites'] ) ? absint( $stats['content_rewrites'] ) + 1 : 1;
	return true;
}

function almaden_bookster_move_attachment_into_book_media_folder( $attachment_id, $book_id, &$stats = array() ) {
	$attachment_id = absint( $attachment_id );
	$book_id = absint( $book_id );

	if ( $attachment_id <= 0 || $book_id <= 0 ) {
		return array();
	}

	$attachment = get_post( $attachment_id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
		return array();
	}

	$target_subdir = function_exists( 'almaden_bookster_ensure_book_media_directory' ) ? almaden_bookster_ensure_book_media_directory( $book_id ) : '';
	if ( '' === $target_subdir ) {
		return array();
	}

	$uploads = wp_upload_dir();
	if ( empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
		return array();
	}

	$target_dir = trailingslashit( $uploads['basedir'] ) . ltrim( $target_subdir, '/' );
	wp_mkdir_p( $target_dir );

	$source_files = almaden_bookster_get_book_media_attachment_source_files( $attachment_id );
	if ( empty( $source_files ) ) {
		return array();
	}

	$main_source_file = get_attached_file( $attachment_id );
	if ( empty( $main_source_file ) || ! file_exists( $main_source_file ) ) {
		$main_source_file = $source_files[0];
	}

	$replacements = array();
	$files_moved = 0;
	$file_changed = false;
	$main_target_file = trailingslashit( $target_dir ) . wp_basename( $main_source_file );

	foreach ( $source_files as $source_file ) {
		$source_file = (string) $source_file;
		if ( '' === $source_file || ! file_exists( $source_file ) ) {
			continue;
		}

		$target_file = trailingslashit( $target_dir ) . wp_basename( $source_file );
		$source_url  = almaden_bookster_book_media_path_to_url( $source_file );
		$target_url  = almaden_bookster_book_media_path_to_url( $target_file );
		if ( '' !== $source_url && '' !== $target_url ) {
			$replacements[ $source_url ] = $target_url;
		}

		if ( wp_normalize_path( $source_file ) === wp_normalize_path( $target_file ) ) {
			continue;
		}

		if ( file_exists( $target_file ) ) {
			@unlink( $source_file );
			$file_changed = true;
			++$files_moved;
			continue;
		}

		$moved = @rename( $source_file, $target_file );
		if ( ! $moved ) {
			$moved = @copy( $source_file, $target_file );
			if ( $moved ) {
				@unlink( $source_file );
			}
		}

		if ( $moved ) {
			$file_changed = true;
			++$files_moved;
		}
	}

	if ( ! empty( $main_source_file ) ) {
		$relative_target = ltrim( str_replace( wp_normalize_path( $uploads['basedir'] ), '', wp_normalize_path( $main_target_file ) ), '/' );
		update_attached_file( $attachment_id, $main_target_file );

		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( is_array( $metadata ) ) {
			$metadata['file'] = str_replace( array( '/', '\\' ), '/', $relative_target );
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
	}

	if ( $attachment->post_parent !== $book_id ) {
		wp_update_post(
			array(
				'ID'          => $attachment_id,
				'post_parent' => $book_id,
			)
		);
	}

	$subdir = function_exists( 'almaden_bookster_get_book_media_subdir' ) ? almaden_bookster_get_book_media_subdir( $book_id ) : '';
	if ( '' !== $subdir ) {
		update_post_meta( $attachment_id, '_almaden_book_media_subdir', $subdir );
	}

	if ( $file_changed ) {
		$stats['attachments_moved'] = isset( $stats['attachments_moved'] ) ? absint( $stats['attachments_moved'] ) + 1 : 1;
		$stats['attachment_files_moved'] = isset( $stats['attachment_files_moved'] ) ? absint( $stats['attachment_files_moved'] ) + $files_moved : $files_moved;
	}

	return $replacements;
}

function almaden_bookster_run_book_media_migration() {
	$books = get_posts(
		array(
			'post_type'              => 'almaden-books',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	$stats = array(
		'books_scanned'         => 0,
		'books_updated'         => 0,
		'attachments_found'     => 0,
		'attachments_moved'     => 0,
		'attachment_files_moved' => 0,
		'content_rewrites'      => 0,
		'meta_rewrites'         => 0,
		'conflicts'             => 0,
		'errors'                => 0,
	);
	$claimed_attachments = array();

	foreach ( (array) $books as $book_id ) {
		$book_id = absint( $book_id );
		if ( $book_id <= 0 ) {
			continue;
		}

		++$stats['books_scanned'];

		$attachment_ids = almaden_bookster_get_book_media_attachment_ids_for_book( $book_id );
		$stats['attachments_found'] += count( $attachment_ids );

		$book_replacements = array();
		$book_changed = false;

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = absint( $attachment_id );
			if ( $attachment_id <= 0 ) {
				continue;
			}

			if ( isset( $claimed_attachments[ $attachment_id ] ) && absint( $claimed_attachments[ $attachment_id ] ) !== $book_id ) {
				++$stats['conflicts'];
				continue;
			}

			$claimed_attachments[ $attachment_id ] = $book_id;

			$replacements = almaden_bookster_move_attachment_into_book_media_folder( $attachment_id, $book_id, $stats );
			if ( ! empty( $replacements ) ) {
				$book_replacements = array_merge( $book_replacements, $replacements );
				$book_changed = true;
			}
		}

		if ( ! empty( $book_replacements ) ) {
			$book = get_post( $book_id );
			if ( $book instanceof WP_Post ) {
				$book_changed = almaden_bookster_update_book_media_post_content( $book_id, $book_replacements, $stats ) || $book_changed;
			}

			almaden_bookster_update_book_media_post_meta( $book_id, $book_replacements, $stats );

			$chapter_ids = get_posts(
				array(
					'post_type'              => 'book_chapter',
					'post_parent'            => $book_id,
					'posts_per_page'         => -1,
					'fields'                 => 'ids',
					'orderby'                => 'menu_order',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'suppress_filters'       => true,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
				)
			);

			foreach ( (array) $chapter_ids as $chapter_id ) {
				$chapter_id = absint( $chapter_id );
				if ( $chapter_id <= 0 ) {
					continue;
				}

				$chapter_content_changed = almaden_bookster_update_book_media_post_content( $chapter_id, $book_replacements, $stats );
				$chapter_meta_changed = almaden_bookster_update_book_media_post_meta( $chapter_id, $book_replacements, $stats );
				if ( $chapter_content_changed || $chapter_meta_changed ) {
					$book_changed = true;
				}
			}
		}

		if ( $book_changed ) {
			++$stats['books_updated'];
		}
	}

	$report = array_merge(
		almaden_bookster_get_book_media_migration_report_defaults(),
		$stats,
		array(
			'version'  => 1,
			'last_run' => current_time( 'mysql' ),
			'message'  => sprintf(
				'Libros: %d · actualizados: %d · archivos movidos: %d · URLs reescritas: %d',
				absint( $stats['books_scanned'] ),
				absint( $stats['books_updated'] ),
				absint( $stats['attachment_files_moved'] ),
				absint( $stats['content_rewrites'] + $stats['meta_rewrites'] )
			),
		)
	);

	almaden_bookster_store_book_media_migration_report( $report );

	return $report;
}

function almaden_bookster_maybe_run_book_media_migration() {
	$stored = almaden_bookster_get_book_media_migration_report();
	if ( absint( $stored['version'] ?? 0 ) >= 1 && ! empty( $stored['last_run'] ) ) {
		return;
	}

	almaden_bookster_run_book_media_migration();
}
add_action( 'init', 'almaden_bookster_maybe_run_book_media_migration', 36 );

function almaden_bookster_get_book_media_migration_action_status() {
	return array(
		'done'   => isset( $_GET['book_media_migration_done'] ) && '1' === (string) $_GET['book_media_migration_done'],
		'scanned' => isset( $_GET['book_media_migration_scan'] ) ? absint( $_GET['book_media_migration_scan'] ) : 0,
	);
}

function almaden_bookster_handle_run_book_media_migration() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_book_media_migration_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_book_media_migration_nonce'], 'almaden_bookster_run_book_media_migration' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$report = almaden_bookster_run_book_media_migration();

	$redirect_url = add_query_arg(
		array(
			'page'                    => 'almaden-bookster-pages',
			'book_media_migration_done' => '1',
			'book_media_migration_scan' => absint( $report['books_scanned'] ?? 0 ),
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_run_book_media_migration', 'almaden_bookster_handle_run_book_media_migration' );
