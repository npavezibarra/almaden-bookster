<?php
/**
 * Pseudonymous signed license tokens for visible watermarks and telemetry.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Watermark_Token {
	/**
	 * Build a stable pseudonymous subject hash without exposing a WordPress ID.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return string
	 */
	public static function subject_hash( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return '';
		}

		return substr( hash_hmac( 'sha256', 'almaden-reader-user|' . $user_id, wp_salt( 'auth' ) ), 0, 32 );
	}

	/**
	 * Create a short token attributable on the server during the nonce time window.
	 *
	 * @param int $book_id Book post ID.
	 * @param int $user_id Optional WordPress user ID.
	 * @return string
	 */
	public static function for_book( $book_id, $user_id = 0 ) {
		$book_id = absint( $book_id );
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( ! $book_id || ! $user_id ) {
			return 'ALM-PUBLICA';
		}

		$subject = self::subject_hash( $user_id );
		$license_context = apply_filters(
			'almaden_bookster_content_protection_license_subject',
			array( 'book_id' => $book_id ),
			$book_id,
			$user_id
		);
		$payload = $subject . '|' . $book_id . '|' . wp_nonce_tick() . '|' . wp_json_encode( $license_context );
		$digest  = strtoupper( substr( hash_hmac( 'sha256', $payload, wp_salt( 'secure_auth' ) ), 0, 12 ) );

		return 'ALM-' . implode( '-', str_split( $digest, 4 ) );
	}
}

