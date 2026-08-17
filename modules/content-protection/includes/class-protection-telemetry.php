<?php
/**
 * Privacy-minimized content-protection telemetry and chapter rate limiting.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Protection_Telemetry {
	const ACTION          = 'almaden_bookster_record_protection_event';
	const DB_VERSION      = '1.0.0';
	const RETENTION_DAYS  = 30;
	const CLEANUP_HOOK    = 'almaden_bookster_content_protection_cleanup';
	const RATE_WINDOW     = 60;
	const RATE_THRESHOLD  = 24;
	const UNIQUE_THRESHOLD = 18;

	/**
	 * Register endpoints, schema initialization, and retention cleanup.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'reject_anonymous' ) );
		add_action( 'init', array( __CLASS__, 'maybe_install' ), 5 );
		add_action( 'init', array( __CLASS__, 'schedule_cleanup' ), 20 );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup' ) );
	}

	/**
	 * Create the aggregate-only telemetry table when its version changes.
	 *
	 * @return void
	 */
	public static function maybe_install() {
		if ( self::DB_VERSION === get_option( 'almaden_content_protection_db_version' ) ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::table_name();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			subject_hash char(32) NOT NULL,
			book_id bigint(20) unsigned NOT NULL,
			event_type varchar(32) NOT NULL,
			event_day date NOT NULL,
			event_count bigint(20) unsigned NOT NULL DEFAULT 1,
			last_chapter_id bigint(20) unsigned NOT NULL DEFAULT 0,
			last_event_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY aggregate_event (subject_hash, book_id, event_type, event_day),
			KEY event_day (event_day),
			KEY book_event (book_id, event_type)
		) {$wpdb->get_charset_collate()};";
		dbDelta( $sql );
		if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			update_option( 'almaden_content_protection_db_version', self::DB_VERSION, false );
		}
	}

	/**
	 * Reject telemetry writes without an authenticated Reader session.
	 *
	 * @return void
	 */
	public static function reject_anonymous() {
		nocache_headers();
		wp_send_json_error( array( 'message' => __( 'Evento no autorizado.', 'almaden-bookster' ) ), 403 );
	}

	/**
	 * Validate and aggregate a browser-side protection event.
	 *
	 * @return void
	 */
	public static function handle() {
		nocache_headers();
		$book_id    = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
		$chapter_id = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : 0;
		$event_type = isset( $_POST['event_type'] ) ? sanitize_key( wp_unslash( $_POST['event_type'] ) ) : '';
		$nonce      = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		$allowed    = array( 'copy', 'cut', 'drag', 'print', 'capture_shortcut', 'chapter_load_error' );

		if ( ! $book_id || ! in_array( $event_type, $allowed, true ) || ! wp_verify_nonce( $nonce, 'almaden_reader_telemetry_' . $book_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Evento inválido.', 'almaden-bookster' ) ), 403 );
		}
		if ( ! is_user_logged_in() || ! function_exists( 'almaden_bookster_user_can_access_book' ) || ! almaden_bookster_user_can_access_book( $book_id ) ) {
			self::reject_anonymous();
		}
		if ( ! self::allow_browser_event( get_current_user_id(), $book_id ) ) {
			wp_send_json_success( array( 'recorded' => false ) );
		}

		self::record_event( get_current_user_id(), $book_id, $event_type, $chapter_id );
		wp_send_json_success( array( 'recorded' => true ) );
	}

	/**
	 * Check delivery velocity without persisting normal reading history.
	 *
	 * @param int $book_id    Book post ID.
	 * @param int $chapter_id Chapter post ID.
	 * @return array{allowed:bool,retry_after:int}
	 */
	public static function check_chapter_rate( $book_id, $chapter_id ) {
		$user_id = get_current_user_id();
		$subject = Watermark_Token::subject_hash( $user_id );
		$key     = 'alm_cp_ch_' . substr( hash( 'sha256', $subject . '|' . absint( $book_id ) ), 0, 24 );
		$now     = time();
		$state   = get_transient( $key );
		$state   = is_array( $state ) ? $state : array();

		if ( ! empty( $state['blocked_until'] ) && $state['blocked_until'] > $now ) {
			return array( 'allowed' => false, 'retry_after' => max( 1, $state['blocked_until'] - $now ) );
		}
		if ( empty( $state['started_at'] ) || $now - $state['started_at'] >= self::RATE_WINDOW ) {
			$state = array( 'started_at' => $now, 'count' => 0, 'chapters' => array(), 'alerted' => false );
		}

		$state['count']++;
		$state['chapters'][] = absint( $chapter_id );
		$state['chapters']   = array_slice( array_values( array_unique( $state['chapters'] ) ), -self::RATE_THRESHOLD );
		$is_abnormal = $state['count'] > self::RATE_THRESHOLD && count( $state['chapters'] ) >= self::UNIQUE_THRESHOLD;
		if ( $is_abnormal ) {
			$state['blocked_until'] = $now + 120;
			if ( empty( $state['alerted'] ) ) {
				$state['alerted'] = true;
				self::record_event( $user_id, $book_id, 'chapter_rate_limited', $chapter_id );
				do_action( 'almaden_bookster_content_protection_alert', array( 'subject_hash' => $subject, 'book_id' => absint( $book_id ), 'event_type' => 'chapter_rate_limited' ) );
			}
		}
		set_transient( $key, $state, 5 * MINUTE_IN_SECONDS );

		return array( 'allowed' => ! $is_abnormal, 'retry_after' => $is_abnormal ? 120 : 0 );
	}

	/**
	 * Aggregate one event without storing selected text, IP, email, or raw user ID.
	 *
	 * @param int    $user_id    WordPress user ID used only to derive the hash.
	 * @param int    $book_id    Book post ID.
	 * @param string $event_type Allowlisted event type.
	 * @param int    $chapter_id Optional chapter post ID.
	 * @return void
	 */
	public static function record_event( $user_id, $book_id, $event_type, $chapter_id = 0 ) {
		global $wpdb;
		$subject = Watermark_Token::subject_hash( $user_id );
		$allowed = array( 'copy', 'cut', 'drag', 'print', 'capture_shortcut', 'chapter_load_error', 'chapter_rate_limited' );
		if ( ! $subject || ! in_array( $event_type, $allowed, true ) ) {
			return;
		}

		$table = self::table_name();
		$day   = gmdate( 'Y-m-d' );
		$now   = gmdate( 'Y-m-d H:i:s' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (subject_hash, book_id, event_type, event_day, event_count, last_chapter_id, last_event_at)
				VALUES (%s, %d, %s, %s, 1, %d, %s)
				ON DUPLICATE KEY UPDATE event_count = event_count + 1, last_chapter_id = VALUES(last_chapter_id), last_event_at = VALUES(last_event_at)",
				$subject,
				absint( $book_id ),
				$event_type,
				$day,
				absint( $chapter_id ),
				$now
			)
		);
		do_action( 'almaden_bookster_content_protection_event_recorded', $subject, absint( $book_id ), $event_type );
	}

	/** Schedule daily deletion of expired aggregate rows. */
	public static function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}

	/** Delete aggregate rows older than the documented retention window. */
	public static function cleanup() {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d', time() - ( self::RETENTION_DAYS * DAY_IN_SECONDS ) );
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table_name() . ' WHERE event_day < %s', $cutoff ) );
	}

	/** @return bool */
	private static function allow_browser_event( $user_id, $book_id ) {
		$key   = 'alm_cp_ev_' . substr( hash( 'sha256', Watermark_Token::subject_hash( $user_id ) . '|' . absint( $book_id ) ), 0, 24 );
		$count = absint( get_transient( $key ) );
		if ( $count >= 30 ) {
			return false;
		}
		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/** @return string */
	private static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'almaden_content_protection_events';
	}
}
