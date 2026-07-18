<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function almaden_bookster_cleanup_orphan_cover_thumbnail_files( $force = false ) {
	$cleanup_option = 'almaden_bookster_cover_thumbnail_last_cleanup';
	$last_cleanup = absint( get_option( $cleanup_option, 0 ) );
	$now = time();

	if ( ! $force && $last_cleanup > 0 && ( $now - $last_cleanup ) < DAY_IN_SECONDS ) {
		return array( 'skipped' => true );
	}

	$upload_dir = wp_upload_dir();
	$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'almaden-cover-thumbnails';
	if ( ! is_dir( $target_dir ) ) {
		update_option( $cleanup_option, $now, false );
		return array( 'skipped' => true, 'reason' => 'missing_dir' );
	}

	$removed = array();
	$entries = @scandir( $target_dir );
	if ( is_array( $entries ) ) {
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$file_path = trailingslashit( $target_dir ) . $entry;
			if ( ! is_file( $file_path ) ) {
				continue;
			}

			if ( ! preg_match( '/\.jpe?g$/i', $entry ) ) {
				continue;
			}

			$file_url = trailingslashit( $upload_dir['baseurl'] ) . 'almaden-cover-thumbnails/' . $entry;
			$attachment_id = attachment_url_to_postid( $file_url );
			if ( $attachment_id > 0 ) {
				$attachment_post = get_post( $attachment_id );
				$parent_book_id = $attachment_post ? absint( $attachment_post->post_parent ) : 0;
				$parent_book = $parent_book_id > 0 ? get_post( $parent_book_id ) : null;
				$book_snapshot_id = $parent_book_id > 0 ? absint( get_post_meta( $parent_book_id, '_almaden_cover_thumbnail_snapshot', true ) ) : 0;

				if ( $parent_book && 'almaden-books' === $parent_book->post_type && $book_snapshot_id === (int) $attachment_id ) {
					continue;
				}

				wp_delete_attachment( (int) $attachment_id, true );
				if ( file_exists( $file_path ) ) {
					@unlink( $file_path );
				}
				$removed[] = $entry;
				continue;
			}

			if ( @unlink( $file_path ) ) {
				$removed[] = $entry;
			}
		}
	}

	update_option( $cleanup_option, $now, false );

	return array(
		'removed' => $removed,
		'count'   => count( $removed ),
	);
}

function almaden_bookster_maybe_cleanup_orphan_cover_thumbnail_files() {
	almaden_bookster_cleanup_orphan_cover_thumbnail_files( false );
}

function almaden_bookster_get_cover_thumbnail_backfill_state_option() {
	return 'almaden_bookster_cover_thumbnail_backfill_state';
}

function almaden_bookster_get_cover_thumbnail_backfill_state() {
	$state = get_option( almaden_bookster_get_cover_thumbnail_backfill_state_option(), array() );
	return is_array( $state ) ? $state : array();
}

function almaden_bookster_update_cover_thumbnail_backfill_state( array $state ) {
	update_option( almaden_bookster_get_cover_thumbnail_backfill_state_option(), $state, false );
}

function almaden_bookster_cover_thumbnail_backfill_cron_schedules( $schedules ) {
	if ( ! isset( $schedules['almaden_bookster_every_ten_minutes'] ) ) {
		$schedules['almaden_bookster_every_ten_minutes'] = array(
			'interval' => 10 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 10 minutes', 'almaden-bookster' ),
		);
	}

	return $schedules;
}

function almaden_bookster_schedule_cover_thumbnail_backfill_cron() {
	if ( wp_next_scheduled( 'almaden_bookster_cover_thumbnail_backfill_event' ) ) {
		return;
	}

	wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'almaden_bookster_every_ten_minutes', 'almaden_bookster_cover_thumbnail_backfill_event' );
}

function almaden_bookster_run_cover_thumbnail_backfill_batch( $force = false ) {
	global $wpdb;

	$state = almaden_bookster_get_cover_thumbnail_backfill_state();
	$last_run = ! empty( $state['last_run'] ) ? absint( $state['last_run'] ) : 0;
	if ( ! $force && $last_run > 0 && ( time() - $last_run ) < 10 * MINUTE_IN_SECONDS ) {
		return array( 'skipped' => true, 'reason' => 'throttled' );
	}

	if ( ! function_exists( 'post_type_exists' ) || ! post_type_exists( 'almaden-books' ) ) {
		return array( 'skipped' => true, 'reason' => 'missing_post_type' );
	}

	$cursor = ! empty( $state['cursor'] ) ? absint( $state['cursor'] ) : 0;
	$batch_size = apply_filters( 'almaden_bookster_cover_thumbnail_backfill_batch_size', 3 );
	$batch_size = max( 1, absint( $batch_size ) );
	$posts_table = $wpdb->posts;

	$query = $wpdb->prepare(
		"SELECT ID
		FROM {$posts_table}
		WHERE post_type = %s
			AND post_status NOT IN ( 'trash', 'auto-draft' )
			AND ID > %d
		ORDER BY ID ASC
		LIMIT %d",
		'almaden-books',
		$cursor,
		$batch_size
	);

	$book_ids = $wpdb->get_col( $query );
	if ( empty( $book_ids ) ) {
		$state['last_run'] = time();
		$state['last_batch_ids'] = array();
		almaden_bookster_update_cover_thumbnail_backfill_state( $state );
		return array(
			'processed' => 0,
			'cursor'    => $cursor,
		);
	}

	$processed_ids = array();
	$error_codes = array();
	foreach ( $book_ids as $book_id ) {
		$book_id = absint( $book_id );
		if ( $book_id <= 0 ) {
			continue;
		}

		$result = almaden_bookster_generate_cover_thumbnail_snapshot( $book_id );
		$processed_ids[] = $book_id;
		if ( is_wp_error( $result ) ) {
			$error_codes[ $book_id ] = $result->get_error_code();
		}
	}

	$state['cursor'] = max( array_map( 'absint', $book_ids ) );
	$state['last_run'] = time();
	$state['last_batch_ids'] = $processed_ids;
	$state['last_error_codes'] = $error_codes;
	almaden_bookster_update_cover_thumbnail_backfill_state( $state );

	return array(
		'processed' => count( $processed_ids ),
		'cursor'    => $state['cursor'],
		'errors'    => $error_codes,
	);
}

function almaden_bookster_maybe_process_cover_thumbnail_backfill() {
	if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
		return;
	}

	if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
		return;
	}

	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	almaden_bookster_run_cover_thumbnail_backfill_batch( false );
}

function almaden_bookster_cleanup_book_cover_thumbnail_on_delete( $post_id ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return;
	}

	$post = get_post( $post_id );
	if ( ! $post || 'almaden-books' !== $post->post_type ) {
		return;
	}

	almaden_bookster_delete_cover_thumbnail_snapshot( $post_id );
}

function almaden_bookster_get_bookshelf_cache_version_option() {
	return 'almaden_bookster_bookshelf_cache_version';
}

function almaden_bookster_get_bookshelf_cache_version() {
	return max( 1, absint( get_option( almaden_bookster_get_bookshelf_cache_version_option(), 1 ) ) );
}

function almaden_bookster_bump_bookshelf_cache_version() {
	$current_version = almaden_bookster_get_bookshelf_cache_version();
	update_option( almaden_bookster_get_bookshelf_cache_version_option(), $current_version + 1, false );
}

function almaden_bookster_maybe_bump_bookshelf_cache_on_post( $post_id, $post = null ) {
	$post_id = absint( $post_id );
	if ( $post_id <= 0 ) {
		return;
	}

	if ( ! $post || ! isset( $post->post_type ) ) {
		$post = get_post( $post_id );
	}

	if ( ! $post || 'almaden-books' !== $post->post_type ) {
		return;
	}

	almaden_bookster_bump_bookshelf_cache_version();
}

function almaden_bookster_maybe_bump_bookshelf_cache_on_status_transition( $new_status, $old_status, $post ) {
	if ( ! $post || ! isset( $post->ID ) ) {
		return;
	}

	if ( $new_status === $old_status ) {
		return;
	}

	almaden_bookster_maybe_bump_bookshelf_cache_on_post( $post->ID, $post );
}

function almaden_bookster_maybe_bump_bookshelf_cache_on_terms( $object_id ) {
	$object_id = absint( $object_id );
	if ( $object_id <= 0 ) {
		return;
	}

	$post = get_post( $object_id );
	if ( ! $post || 'almaden-books' !== $post->post_type ) {
		return;
	}

	almaden_bookster_bump_bookshelf_cache_version();
}

add_action( 'before_delete_post', 'almaden_bookster_cleanup_book_cover_thumbnail_on_delete', 10, 1 );
add_action( 'init', 'almaden_bookster_maybe_cleanup_orphan_cover_thumbnail_files', 40 );
add_action( 'save_post_almaden-books', 'almaden_bookster_maybe_bump_bookshelf_cache_on_post', 20, 2 );
add_action( 'transition_post_status', 'almaden_bookster_maybe_bump_bookshelf_cache_on_status_transition', 20, 3 );
add_action( 'set_object_terms', 'almaden_bookster_maybe_bump_bookshelf_cache_on_terms', 20, 6 );
add_filter( 'cron_schedules', 'almaden_bookster_cover_thumbnail_backfill_cron_schedules' );
add_action( 'init', 'almaden_bookster_schedule_cover_thumbnail_backfill_cron', 41 );
add_action( 'init', 'almaden_bookster_maybe_process_cover_thumbnail_backfill', 42 );
add_action( 'almaden_bookster_cover_thumbnail_backfill_event', 'almaden_bookster_run_cover_thumbnail_backfill_batch', 10, 0 );

