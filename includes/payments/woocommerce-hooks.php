<?php
/**
 * WooCommerce storefront and checkout hooks.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_render_payment_template( $template, $variables = array() ) {
	$template = sanitize_file_name( $template );
	$template_path = dirname( __DIR__, 2 ) . '/templates/payments/' . $template . '.php';
	if ( ! is_readable( $template_path ) ) {
		return;
	}

	extract( is_array( $variables ) ? $variables : array(), EXTR_SKIP );
	include $template_path;
}

function almaden_bookster_render_book_reader_cta() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$book_id = almaden_bookster_get_book_id_for_product( $product->get_id() );
	if ( ! $book_id ) {
		return;
	}

	almaden_bookster_render_payment_template(
		'reader-cta',
		array( 'reader_url' => almaden_bookster_get_book_reader_button_url( $book_id ) )
	);
}
add_action( 'woocommerce_after_add_to_cart_form', 'almaden_bookster_render_book_reader_cta', 20 );

function almaden_bookster_get_book_terms_url() {
	$terms_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'terms' ) : '';
	if ( empty( $terms_url ) && function_exists( 'get_privacy_policy_url' ) ) {
		$terms_url = get_privacy_policy_url();
	}

	return $terms_url ? $terms_url : home_url( '/' );
}

function almaden_bookster_render_terms_checkbox() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$book_id = almaden_bookster_get_book_id_for_product( $product->get_id() );
	if ( ! $book_id ) {
		return;
	}

	almaden_bookster_render_payment_template(
		'terms-checkbox',
		array(
			'book_id'    => $book_id,
			'product_id' => $product->get_id(),
			'terms_url'  => almaden_bookster_get_book_terms_url(),
		)
	);
}
add_action( 'woocommerce_before_add_to_cart_button', 'almaden_bookster_render_terms_checkbox' );

function almaden_bookster_validate_terms_before_add_to_cart( $passed, $product_id ) {
	if ( ! almaden_bookster_get_book_id_for_product( $product_id ) ) {
		return $passed;
	}

	$nonce = isset( $_POST['almaden_bookster_terms_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['almaden_bookster_terms_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'almaden_bookster_terms_' . absint( $product_id ) ) ) {
		wc_add_notice( __( 'Debes confirmar la aceptación de los términos para continuar.', 'almaden-bookster' ), 'error' );
		return false;
	}

	$accepted = isset( $_POST['almaden_bookster_terms_accepted'] ) ? sanitize_text_field( wp_unslash( $_POST['almaden_bookster_terms_accepted'] ) ) : '';
	if ( '1' !== $accepted ) {
		wc_add_notice( __( 'Debes aceptar los términos y condiciones antes de comprar este ebook.', 'almaden-bookster' ), 'error' );
		return false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'almaden_bookster_validate_terms_before_add_to_cart', 10, 2 );

function almaden_bookster_capture_terms_acceptance_in_cart( $cart_item_data, $product_id ) {
	$book_id = almaden_bookster_get_book_id_for_product( $product_id );
	if ( ! $book_id ) {
		return $cart_item_data;
	}

	$accepted = isset( $_POST['almaden_bookster_terms_accepted'] ) ? sanitize_text_field( wp_unslash( $_POST['almaden_bookster_terms_accepted'] ) ) : '';
	if ( '1' === $accepted ) {
		$cart_item_data['almaden_bookster_terms_accepted'] = 1;
		$cart_item_data['almaden_book_id'] = $book_id;
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'almaden_bookster_capture_terms_acceptance_in_cart', 10, 2 );

function almaden_bookster_validate_ebook_terms_at_checkout() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		if ( ! empty( $cart_item['almaden_book_id'] ) && empty( $cart_item['almaden_bookster_terms_accepted'] ) ) {
			wc_add_notice( __( 'Debes aceptar los términos y condiciones del ebook antes de finalizar la compra.', 'almaden-bookster' ), 'error' );
			break;
		}
	}
}
add_action( 'woocommerce_checkout_process', 'almaden_bookster_validate_ebook_terms_at_checkout' );

function almaden_bookster_store_book_link_on_order_item( $item, $cart_item_key, $values, $order ) {
	if ( empty( $values['almaden_book_id'] ) ) {
		return;
	}

	$book_id = absint( $values['almaden_book_id'] );
	$product_id = ! empty( $values['product_id'] ) ? absint( $values['product_id'] ) : 0;
	if ( $book_id ) {
		$item->add_meta_data( '_almaden_book_id', $book_id, true );
	}
	if ( $product_id ) {
		$item->add_meta_data( '_almaden_wc_product_id', $product_id, true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'almaden_bookster_store_book_link_on_order_item', 10, 4 );

function almaden_bookster_render_purchase_confirmation( $order_id ) {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( ! $order ) {
		return;
	}

	$books = array();
	foreach ( $order->get_items() as $item ) {
		$book_id = absint( $item->get_meta( '_almaden_book_id' ) );
		if ( $book_id ) {
			$books[] = array(
				'title' => get_the_title( $book_id ),
				'url'   => get_permalink( $book_id ),
			);
		}
	}
	if ( empty( $books ) ) {
		return;
	}

	almaden_bookster_render_payment_template(
		'purchase-confirmation',
		array(
			'books' => $books,
			'order' => $order,
		)
	);
}
add_action( 'woocommerce_thankyou', 'almaden_bookster_render_purchase_confirmation' );
