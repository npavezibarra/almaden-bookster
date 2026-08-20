<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		$book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
		if ( ! $book_id || 'almaden-books' !== get_post_type( $book_id ) ) {
			return;
		}

		$version = defined( 'WP_DEBUG' ) && WP_DEBUG ? (string) time() : '1.0.0';
		wp_enqueue_style(
			'almaden-book-product',
			ALMADEN_BOOK_PRODUCT_URL . 'assets/css/book-product.css',
			array(),
			$version
		);
		wp_enqueue_script(
			'almaden-book-product-api',
			ALMADEN_BOOK_PRODUCT_URL . 'assets/js/book-product-api.js',
			array(),
			$version,
			true
		);
		wp_enqueue_script(
			'almaden-book-product-app',
			ALMADEN_BOOK_PRODUCT_URL . 'assets/js/book-product-app.js',
			array( 'almaden-book-product-api' ),
			$version,
			true
		);
	}
}

