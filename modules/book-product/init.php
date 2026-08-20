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
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-catalog.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-factory.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-product-service.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-access.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-ajax-controller.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-assets.php';
require_once ALMADEN_BOOK_PRODUCT_DIR . 'includes/class-renderer.php';

AlmadenBookster\BookProduct\Ajax_Controller::init();
AlmadenBookster\BookProduct\Assets::init();

if ( ! function_exists( 'almaden_bookster_book_product_render_panel' ) ) {
	function almaden_bookster_book_product_render_panel( $book_id ) {
		AlmadenBookster\BookProduct\Renderer::render( $book_id );
	}
}

if ( ! function_exists( 'almaden_bookster_book_product_access_decision' ) ) {
	function almaden_bookster_book_product_access_decision( $book_id, $user_id = null ) {
		return AlmadenBookster\BookProduct\Access::decision( $book_id, $user_id );
	}
}

