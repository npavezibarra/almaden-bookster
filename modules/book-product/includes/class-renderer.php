<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Renderer {
	public static function render( $book_id ) {
		$book_id = absint( $book_id );
		if ( ! $book_id ) {
			return;
		}

		$state = Product_Service::state( $book_id );
		$nonce = wp_create_nonce( 'almaden_book_product_' . $book_id );
		$template = ALMADEN_BOOK_PRODUCT_DIR . 'templates/product-panel.php';
		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}

