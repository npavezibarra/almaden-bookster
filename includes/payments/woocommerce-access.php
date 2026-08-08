<?php
/**
 * WooCommerce reader navigation and access checks.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_is_same_site_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return false;
	}

	$validated = wp_validate_redirect( $url, '' );
	if ( empty( $validated ) ) {
		return false;
	}

	$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$url_host = wp_parse_url( $validated, PHP_URL_HOST );
	return ! empty( $url_host ) && ! empty( $site_host ) && strtolower( $site_host ) === strtolower( $url_host );
}

function almaden_bookster_get_book_return_url( $book_id, $context = array() ) {
	$book_id = absint( $book_id );
	$context = is_array( $context ) ? $context : array();
	$explicit_return = almaden_bookster_get_explicit_return_url( $context );
	if ( $explicit_return && almaden_bookster_is_same_site_url( $explicit_return ) ) {
		return $explicit_return;
	}

	$settings = function_exists( 'almaden_bookster_get_distribution_settings' ) ? almaden_bookster_get_distribution_settings() : array();
	$policy = sanitize_key( (string) ( $settings['return_url_policy'] ?? 'product_or_fallback' ) );
	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_id = absint( $relation['parent_product_id'] ?? 0 );
	if ( ! $product_id ) {
		$product_id = absint( $relation['product_id'] ?? 0 );
	}

	$product_url = $product_id > 0 ? get_permalink( $product_id ) : '';
	$store_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	if ( empty( $store_url ) && function_exists( 'almaden_bookster_get_store_page_url' ) ) {
		$store_url = almaden_bookster_get_store_page_url();
	}
	$store_url = $store_url ? $store_url : home_url( '/' );

	if ( ! in_array( $policy, array( 'bookshelf_or_fallback', 'store_root' ), true ) && $product_url ) {
		return $product_url;
	}

	return $store_url;
}

function almaden_bookster_get_explicit_return_url( $context ) {
	foreach ( array( 'return_to', 'return_url', 'back_to' ) as $key ) {
		if ( ! empty( $_GET[ $key ] ) ) {
			return esc_url_raw( wp_unslash( $_GET[ $key ] ) );
		}
		if ( ! empty( $context[ $key ] ) ) {
			return esc_url_raw( $context[ $key ] );
		}
	}

	return '';
}

function almaden_bookster_get_book_reader_url( $book_id, $context = array() ) {
	$book_id = absint( $book_id );
	$book_url = get_permalink( $book_id );
	if ( ! $book_url ) {
		return home_url( '/' );
	}

	$context = is_array( $context ) ? $context : array();
	if ( empty( $context['return_to'] ) && empty( $_GET['return_to'] ) ) {
		$context['return_to'] = almaden_bookster_get_book_return_url( $book_id );
	}
	if ( ! empty( $context['return_to'] ) && almaden_bookster_is_same_site_url( $context['return_to'] ) ) {
		$book_url = add_query_arg( 'return_to', rawurlencode( esc_url_raw( $context['return_to'] ) ), $book_url );
	}

	return $book_url;
}

function almaden_bookster_get_book_reader_button_url( $book_id ) {
	return almaden_bookster_get_book_reader_url(
		$book_id,
		array( 'return_to' => get_permalink( absint( $book_id ) ) )
	);
}

function almaden_bookster_woocommerce_user_has_wc_access_for_book( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $book_id ) {
		return false;
	}
	if ( function_exists( 'almaden_bookster_user_can_manage_book' ) && almaden_bookster_user_can_manage_book( $book_id, $user_id ) ) {
		return true;
	}

	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_ids = array_filter(
		array(
			absint( $relation['product_id'] ?? 0 ),
			absint( $relation['parent_product_id'] ?? 0 ),
		)
	);
	if ( empty( $product_ids ) ) {
		return true;
	}
	if ( $user_id <= 0 || ! function_exists( 'wc_customer_bought_product' ) ) {
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return false;
	}
	foreach ( $product_ids as $product_id ) {
		if ( wc_customer_bought_product( $user->user_email, $user_id, $product_id ) ) {
			return true;
		}
	}

	return false;
}

function almaden_bookster_user_has_wc_access_for_book( $book_id, $user_id = null ) {
	$provider_result = function_exists( 'almaden_bookster_commerce_provider_call' ) ? almaden_bookster_commerce_provider_call( 'has_access', array( $book_id, $user_id ) ) : null;
	if ( null !== $provider_result ) {
		return (bool) $provider_result;
	}

	return almaden_bookster_woocommerce_user_has_wc_access_for_book( $book_id, $user_id );
}
