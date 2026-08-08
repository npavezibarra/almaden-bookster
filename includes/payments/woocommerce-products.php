<?php
/**
 * WooCommerce product creation and purchase links.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_normalize_wc_product_status( $status ) {
	$allowed = array( 'publish', 'draft', 'pending', 'private' );

	return in_array( $status, $allowed, true ) ? $status : 'draft';
}

function almaden_bookster_set_wc_virtual_product_defaults( $product_id ) {
	$product_id = absint( $product_id );
	if ( ! $product_id ) {
		return;
	}

	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_downloadable', 'no' );
	update_post_meta( $product_id, '_regular_price', '' );
	update_post_meta( $product_id, '_sale_price', '' );
	update_post_meta( $product_id, '_price', '' );
	update_post_meta( $product_id, '_stock_status', 'instock' );
}

function almaden_bookster_get_wc_product_post_data( $book, $status ) {
	return array(
		'post_title'   => sprintf( 'Ebook: %s', $book->post_title ),
		'post_content' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 80 ),
		'post_excerpt' => $book->post_excerpt,
		'post_status'  => $status,
		'post_type'    => 'product',
	);
}

function almaden_bookster_ensure_variable_parent_attributes( $parent_product_id ) {
	$parent_product_id = absint( $parent_product_id );
	if ( ! $parent_product_id ) {
		return false;
	}

	$attributes = get_post_meta( $parent_product_id, '_product_attributes', true );
	if ( ! is_array( $attributes ) ) {
		$attributes = array();
	}

	if ( ! isset( $attributes['formato'] ) ) {
		$attributes['formato'] = array(
			'name'         => 'Formato',
			'value'        => 'ebook',
			'position'     => 0,
			'is_visible'   => 1,
			'is_variation' => 1,
			'is_taxonomy'  => 0,
		);
		update_post_meta( $parent_product_id, '_product_attributes', $attributes );
	}

	return true;
}

function almaden_bookster_create_wc_variation_for_book( $book_id, $parent_product_id, $status = 'draft' ) {
	if ( ! almaden_bookster_woocommerce_is_available() ) {
		return 0;
	}

	$book_id = absint( $book_id );
	$parent_product_id = absint( $parent_product_id );
	$book = get_post( $book_id );
	$parent = get_post( $parent_product_id );
	if ( ! $book || ! $parent || 'product' !== $parent->post_type ) {
		return 0;
	}

	almaden_bookster_ensure_variable_parent_attributes( $parent_product_id );
	$variation_id = wp_insert_post(
		array(
			'post_title'   => sprintf( '%s - Ebook', $book->post_title ),
			'post_status'  => almaden_bookster_normalize_wc_product_status( $status ),
			'post_type'    => 'product_variation',
			'post_parent'  => $parent_product_id,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 40 ),
		),
		true
	);

	if ( is_wp_error( $variation_id ) || ! $variation_id ) {
		return 0;
	}

	almaden_bookster_set_wc_virtual_product_defaults( $variation_id );
	update_post_meta( $variation_id, 'attribute_formato', 'ebook' );

	return (int) $variation_id;
}

function almaden_bookster_create_book_product( $book_id, $args = array() ) {
	if ( ! almaden_bookster_woocommerce_is_available() ) {
		return 0;
	}

	$args = wp_parse_args(
		is_array( $args ) ? $args : array(),
		array(
			'status'            => 'draft',
			'product_mode'      => 'simple',
			'parent_product_id' => 0,
			'create_variation'  => true,
		)
	);
	$book_id = absint( $book_id );
	$book = get_post( $book_id );
	if ( ! $book || 'almaden-books' !== $book->post_type ) {
		return 0;
	}

	$product_mode = almaden_bookster_normalize_wc_relation_mode( $args['product_mode'] );
	$status = almaden_bookster_normalize_wc_product_status( $args['status'] );
	if ( 'variation' === $product_mode ) {
		return almaden_bookster_create_and_link_wc_variation( $book_id, $args['parent_product_id'], $status );
	}

	$product_id = wp_insert_post( almaden_bookster_get_wc_product_post_data( $book, $status ), true );
	if ( is_wp_error( $product_id ) || ! $product_id ) {
		return 0;
	}

	if ( 'variable_parent' === $product_mode ) {
		wp_set_object_terms( $product_id, 'variable', 'product_type', false );
		almaden_bookster_set_wc_virtual_product_defaults( $product_id );

		return almaden_bookster_create_and_link_wc_variation( $book_id, $product_id, $status );
	}

	wp_set_object_terms( $product_id, 'simple', 'product_type', false );
	almaden_bookster_set_wc_virtual_product_defaults( $product_id );
	almaden_bookster_sync_book_product_link( $book_id, $product_id, array( 'product_mode' => 'simple' ) );

	return (int) $product_id;
}

function almaden_bookster_create_and_link_wc_variation( $book_id, $parent_product_id, $status ) {
	$parent_product_id = absint( $parent_product_id );
	if ( ! $parent_product_id ) {
		return 0;
	}

	$variation_id = almaden_bookster_create_wc_variation_for_book( $book_id, $parent_product_id, $status );
	if ( ! $variation_id ) {
		return 0;
	}

	almaden_bookster_sync_book_product_link(
		$book_id,
		$variation_id,
		array(
			'parent_product_id' => $parent_product_id,
			'product_mode'      => 'variation',
		)
	);

	return (int) $variation_id;
}

function almaden_bookster_get_or_create_book_product_id( $book_id, $create_if_missing = false, $status = 'publish', $args = array() ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_id = absint( $relation['product_id'] ?? 0 );
	if ( $product_id > 0 || ! $create_if_missing ) {
		return $product_id;
	}

	return almaden_bookster_create_book_product(
		$book_id,
		array_merge(
			array(
				'status'       => $status,
				'product_mode' => $args['product_mode'] ?? 'simple',
			),
			$args
		)
	);
}

function almaden_bookster_woocommerce_get_book_purchase_url( $book_id, $context = array() ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_id = absint( $relation['product_id'] ?? 0 );
	$parent_product_id = absint( $relation['parent_product_id'] ?? 0 );
	$purchase_target_id = $parent_product_id > 0 ? $parent_product_id : $product_id;

	if ( $purchase_target_id > 0 ) {
		$product = get_post( $purchase_target_id );
		if ( $product && 'publish' === $product->post_status ) {
			return get_permalink( $purchase_target_id );
		}
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( $shop_url ) {
			return $shop_url;
		}
	}

	return home_url( '/' );
}

function almaden_bookster_get_book_purchase_url( $book_id ) {
	$provider_result = function_exists( 'almaden_bookster_commerce_provider_call' ) ? almaden_bookster_commerce_provider_call( 'get_purchase_url', array( $book_id ) ) : null;
	if ( null !== $provider_result && '' !== (string) $provider_result ) {
		return (string) $provider_result;
	}

	return almaden_bookster_woocommerce_get_book_purchase_url( $book_id );
}
