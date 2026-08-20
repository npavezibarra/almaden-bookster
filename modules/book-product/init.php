<?php
/**
 * Book Product module bootstrap.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOK_PRODUCT_DIR' ) ) {
	define( 'ALMADEN_BOOK_PRODUCT_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALMADEN_BOOK_PRODUCT_URL' ) ) {
	define( 'ALMADEN_BOOK_PRODUCT_URL', plugin_dir_url( __FILE__ ) );
}

require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-relation-repository.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-sample-repository.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-catalog.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-factory.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-service.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-access.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-ajax-controller.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-assets.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-renderer.php';

AlmadenBookster\BookProduct\Ajax_Controller::init();
AlmadenBookster\BookProduct\Assets::init();

if ( ! function_exists( 'almaden_bookster_create_book_sample_chapters_table' ) ) {
	function almaden_bookster_create_book_sample_chapters_table() {
		AlmadenBookster\BookProduct\Sample_Repository::install();
	}
}

if ( ! function_exists( 'almaden_bookster_book_product_render_panel' ) ) {
	function almaden_bookster_book_product_render_panel( $book_id ) {
		AlmadenBookster\BookProduct\Renderer::render( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_get_sample_chapter_ids' ) ) {
	function almaden_bookster_get_sample_chapter_ids( $book_id ) {
		return AlmadenBookster\BookProduct\Sample_Repository::get_ids( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_book_has_sample_chapters' ) ) {
	function almaden_bookster_book_has_sample_chapters( $book_id ) {
		return AlmadenBookster\BookProduct\Sample_Repository::has_samples( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_is_sample_chapter' ) ) {
	function almaden_bookster_is_sample_chapter( $book_id, $chapter_id ) {
		return AlmadenBookster\BookProduct\Sample_Repository::is_sample( $book_id, $chapter_id );
	}
}

if ( ! function_exists( 'almaden_bookster_book_product_access_decision' ) ) {
	function almaden_bookster_book_product_access_decision( $book_id, $user_id = null ) {
		return AlmadenBookster\BookProduct\Access::decision( $book_id, $user_id );
	}
}
