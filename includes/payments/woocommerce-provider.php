<?php
/**
 * WooCommerce commerce-provider registration.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_register_woocommerce_commerce_provider() {
	if ( ! function_exists( 'almaden_bookster_register_commerce_provider' ) ) {
		return;
	}

	almaden_bookster_register_commerce_provider(
		array(
			'key'       => 'woocommerce',
			'label'     => 'WooCommerce',
			'available' => 'almaden_bookster_woocommerce_is_available',
			'supports'  => array( 'catalog', 'products', 'variations', 'purchase_link', 'access_checks', 'order_links' ),
			'create_book_product' => static function ( $book_id, $args = array() ) {
				return almaden_bookster_create_book_product( $book_id, $args );
			},
			'get_or_create_book_product_id' => static function ( $book_id, $create_if_missing = false, $status = 'publish', $args = array() ) {
				return almaden_bookster_get_or_create_book_product_id( $book_id, $create_if_missing, $status, $args );
			},
			'get_purchase_url' => static function ( $book_id, $context = array() ) {
				return almaden_bookster_woocommerce_get_book_purchase_url( $book_id, $context );
			},
			'has_access' => static function ( $book_id, $user_id = null ) {
				return almaden_bookster_woocommerce_user_has_wc_access_for_book( $book_id, $user_id );
			},
			'save_relation_from_request' => static function ( $book_id, $request = array() ) {
				return almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request );
			},
		)
	);
}
almaden_bookster_register_woocommerce_commerce_provider();
