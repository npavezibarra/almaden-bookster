<?php
/**
 * Persistent feature flags and gradual rollout for content protection.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Protection_Policy {
	const ENABLED_OPTION = 'almaden_bookster_content_protection_enabled';
	const ROLLOUT_OPTION = 'almaden_bookster_content_protection_rollout';
	const BOOK_META      = '_almaden_content_protection';

	/**
	 * Resolve the effective policy. Book overrides take precedence over rollout.
	 *
	 * @param int $book_id Book post ID.
	 * @return array<string, mixed>
	 */
	public static function resolve( $book_id ) {
		$book_id = absint( $book_id );
		$defaults = array(
			'enabled'              => true,
			'block_clipboard'      => true,
			'block_drag'           => true,
			'block_print'          => true,
			'chapter_delivery'     => 'on_demand',
			'watermark'            => false,
			'telemetry'            => true,
			'capture_deterrence'    => false,
			'accessibility_bypass' => false,
		);

		$global   = self::global_enabled();
		$enabled  = $global;
		$override = $book_id ? sanitize_key( (string) get_post_meta( $book_id, self::BOOK_META, true ) ) : 'inherit';
		if ( ! $global ) {
			$enabled = false;
		} elseif ( 'enabled' === $override ) {
			$enabled = true;
		} elseif ( 'disabled' === $override ) {
			$enabled = false;
		} else {
			$enabled = self::included_in_rollout( $book_id );
		}
		$defaults['enabled'] = $enabled;

		/**
		 * Filters the Reader content-protection policy after persistent flags.
		 *
		 * @param array<string, mixed> $defaults Effective policy.
		 * @param int                  $book_id  Book post ID.
		 */
		$filtered = apply_filters( 'almaden_bookster_content_protection_policy', $defaults, $book_id );
		return is_array( $filtered ) ? array_merge( $defaults, $filtered ) : $defaults;
	}

	/** Return whether the emergency/global switch is on. */
	public static function global_enabled() {
		if ( defined( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_ENABLED' ) ) {
			return (bool) ALMADEN_BOOKSTER_CONTENT_PROTECTION_ENABLED;
		}
		return '0' !== (string) get_option( self::ENABLED_OPTION, '1' );
	}

	/** Return the normalized rollout percentage. */
	public static function rollout_percentage() {
		return max( 0, min( 100, absint( get_option( self::ROLLOUT_OPTION, 100 ) ) ) );
	}

	/** Stable book cohort that does not depend on user identity. */
	private static function included_in_rollout( $book_id ) {
		$percentage = self::rollout_percentage();
		if ( 100 === $percentage ) {
			return true;
		}
		if ( 0 === $percentage || ! $book_id ) {
			return false;
		}
		$bucket = (int) hexdec( substr( hash_hmac( 'sha256', (string) $book_id, wp_salt( 'auth' ) ), 0, 8 ) ) % 100;
		return $bucket < $percentage;
	}
}
