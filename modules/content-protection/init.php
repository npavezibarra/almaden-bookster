<?php
/**
 * Module: Content Protection.
 *
 * Protects ebook text from conventional clipboard and drag operations.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR' ) ) {
	define( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_URL' ) ) {
	define( 'ALMADEN_BOOKSTER_CONTENT_PROTECTION_URL', plugin_dir_url( __FILE__ ) );
}

require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-protection-policy.php';
require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-content-protection.php';
require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-watermark-token.php';
require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-protection-telemetry.php';
require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-chapter-endpoint.php';
require_once ALMADEN_BOOKSTER_CONTENT_PROTECTION_DIR . 'includes/class-protection-admin.php';

AlmadenBookster\ContentProtection\Protection_Telemetry::init();
AlmadenBookster\ContentProtection\Chapter_Endpoint::init();
AlmadenBookster\ContentProtection\Protection_Admin::init();

if ( ! function_exists( 'almaden_bookster_content_protection_render_head' ) ) {
	function almaden_bookster_content_protection_render_head( $book_id ) {
		AlmadenBookster\ContentProtection\Content_Protection::render_head( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_content_protection_render_footer' ) ) {
	function almaden_bookster_content_protection_render_footer( $book_id ) {
		AlmadenBookster\ContentProtection\Content_Protection::render_footer( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_content_protection_prepare_chapters' ) ) {
	function almaden_bookster_content_protection_prepare_chapters( $chapters, $book_id ) {
		return AlmadenBookster\ContentProtection\Content_Protection::prepare_chapters( $chapters, $book_id );
	}
}
