<?php
/**
 * Persistence and normalization for book-product relations.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_woocommerce_is_available() {
	return function_exists( 'wc_get_product' ) && function_exists( 'wc_get_page_permalink' );
}

function almaden_bookster_normalize_wc_relation_mode( $mode ) {
	$mode = sanitize_key( (string) $mode );
	$allowed = array( 'simple', 'variable_parent', 'variation', 'none' );

	return in_array( $mode, $allowed, true ) ? $mode : 'simple';
}

function almaden_bookster_get_book_wc_relation( $book_id ) {
	$book_id = absint( $book_id );
	if ( ! $book_id ) {
		return array(
			'book_id'           => 0,
			'product_id'        => 0,
			'parent_product_id' => 0,
			'product_mode'      => 'none',
		);
	}

	$stored_relation = get_post_meta( $book_id, '_almaden_wc_relation', true );
	if ( is_array( $stored_relation ) ) {
		return array(
			'book_id'           => $book_id,
			'product_id'        => absint( $stored_relation['product_id'] ?? 0 ),
			'parent_product_id' => absint( $stored_relation['parent_product_id'] ?? 0 ),
			'product_mode'      => almaden_bookster_normalize_wc_relation_mode( $stored_relation['product_mode'] ?? 'simple' ),
		);
	}

	$product_id = absint( get_post_meta( $book_id, '_almaden_wc_product_id', true ) );
	$parent_product_id = absint( get_post_meta( $book_id, '_almaden_wc_parent_product_id', true ) );
	$product_mode = almaden_bookster_normalize_wc_relation_mode( get_post_meta( $book_id, '_almaden_wc_product_mode', true ) );

	if ( 'none' === $product_mode ) {
		$product_mode = $product_id > 0 && $parent_product_id > 0 ? 'variation' : ( $parent_product_id > 0 ? 'variable_parent' : ( $product_id > 0 ? 'simple' : 'none' ) );
	}

	if ( 0 === $parent_product_id && $product_id > 0 ) {
		$maybe_parent_id = absint( get_post_field( 'post_parent', $product_id ) );
		if ( $maybe_parent_id > 0 ) {
			$parent_product_id = $maybe_parent_id;
			if ( 'simple' === $product_mode ) {
				$product_mode = 'variation';
			}
		}
	}

	return array(
		'book_id'           => $book_id,
		'product_id'        => $product_id,
		'parent_product_id' => $parent_product_id,
		'product_mode'      => $product_mode,
	);
}

function almaden_bookster_get_book_id_for_product( $product_id ) {
	return absint( get_post_meta( absint( $product_id ), '_almaden_book_id', true ) );
}

function almaden_bookster_get_book_wc_product_id( $book_id ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );

	return absint( $relation['product_id'] ?? 0 );
}

function almaden_bookster_get_book_product_id( $book_id ) {
	return almaden_bookster_get_book_wc_product_id( $book_id );
}

function almaden_bookster_get_book_wc_parent_product_id( $book_id ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );

	return absint( $relation['parent_product_id'] ?? 0 );
}

function almaden_bookster_get_book_wc_product_mode( $book_id ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );

	return almaden_bookster_normalize_wc_relation_mode( $relation['product_mode'] ?? 'none' );
}

function almaden_bookster_clear_book_wc_relation( $book_id ) {
	$book_id = absint( $book_id );
	if ( ! $book_id ) {
		return false;
	}

	delete_post_meta( $book_id, '_almaden_wc_relation' );
	delete_post_meta( $book_id, '_almaden_wc_product_id' );
	delete_post_meta( $book_id, '_almaden_wc_parent_product_id' );
	delete_post_meta( $book_id, '_almaden_wc_product_mode' );

	return true;
}

function almaden_bookster_sync_book_product_link( $book_id, $product_id, $relation = array() ) {
	$book_id = absint( $book_id );
	$product_id = absint( $product_id );
	if ( ! $book_id || ! $product_id ) {
		return false;
	}

	$parent_product_id = absint( $relation['parent_product_id'] ?? 0 );
	$product_mode = almaden_bookster_normalize_wc_relation_mode( $relation['product_mode'] ?? '' );
	if ( 0 === $parent_product_id ) {
		$maybe_parent_id = absint( get_post_field( 'post_parent', $product_id ) );
		if ( $maybe_parent_id > 0 ) {
			$parent_product_id = $maybe_parent_id;
			if ( 'simple' === $product_mode ) {
				$product_mode = 'variation';
			}
		}
	}
	if ( 'none' === $product_mode ) {
		$product_mode = $parent_product_id > 0 ? 'variation' : 'simple';
	}

	$relation_data = array(
		'product_id'        => $product_id,
		'parent_product_id' => $parent_product_id,
		'product_mode'      => $product_mode,
	);
	update_post_meta( $book_id, '_almaden_wc_relation', $relation_data );
	update_post_meta( $book_id, '_almaden_wc_product_id', $product_id );
	update_post_meta( $book_id, '_almaden_wc_product_mode', $product_mode );
	if ( $parent_product_id > 0 ) {
		update_post_meta( $book_id, '_almaden_wc_parent_product_id', $parent_product_id );
	} else {
		delete_post_meta( $book_id, '_almaden_wc_parent_product_id' );
	}

	update_post_meta( $product_id, '_almaden_book_id', $book_id );
	if ( $parent_product_id > 0 ) {
		update_post_meta( $parent_product_id, '_almaden_book_id', $book_id );
	}

	return true;
}

function almaden_bookster_sync_book_wc_relation_from_product( $book_id, $product_id ) {
	$book_id = absint( $book_id );
	$product_id = absint( $product_id );
	if ( ! $book_id || ! $product_id ) {
		return false;
	}

	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
	$relation = array(
		'product_id'        => $product_id,
		'parent_product_id' => 0,
		'product_mode'      => 'simple',
	);
	if ( $product ) {
		if ( $product->is_type( 'variation' ) ) {
			$relation['product_mode'] = 'variation';
			$relation['parent_product_id'] = absint( $product->get_parent_id() );
		} elseif ( $product->is_type( 'variable' ) ) {
			$relation['product_mode'] = 'variable_parent';
		}
	}

	return almaden_bookster_sync_book_product_link( $book_id, $product_id, $relation );
}

function almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request = array() ) {
	$book_id = absint( $book_id );
	if ( ! $book_id || ! almaden_bookster_woocommerce_is_available() ) {
		return false;
	}

	$request = is_array( $request ) ? $request : array();
	$mode = isset( $request['almaden_wc_relation_mode'] ) ? sanitize_key( wp_unslash( $request['almaden_wc_relation_mode'] ) ) : 'simple';
	$mode = almaden_bookster_normalize_wc_relation_mode( $mode );
	$product_id = isset( $request['almaden_wc_product_id'] ) ? absint( $request['almaden_wc_product_id'] ) : 0;
	$parent_product_id = isset( $request['almaden_wc_parent_product_id'] ) ? absint( $request['almaden_wc_parent_product_id'] ) : 0;
	$create_wc_product = ! empty( $request['almaden_create_wc_product'] );

	if ( 'none' === $mode ) {
		return almaden_bookster_clear_book_wc_relation( $book_id );
	}
	if ( $create_wc_product && $product_id <= 0 ) {
		$product_id = almaden_bookster_create_book_product(
			$book_id,
			array(
				'status'            => 'draft',
				'product_mode'      => $mode,
				'parent_product_id' => $parent_product_id,
			)
		);
	}
	if ( $product_id <= 0 ) {
		return false;
	}

	return almaden_bookster_sync_book_product_link(
		$book_id,
		$product_id,
		array(
			'product_mode'      => $mode,
			'parent_product_id' => $parent_product_id,
		)
	);
}

function almaden_bookster_save_book_commerce_relation_from_request( $book_id, $request = array() ) {
	$provider_result = function_exists( 'almaden_bookster_commerce_provider_call' ) ? almaden_bookster_commerce_provider_call( 'save_relation_from_request', array( $book_id, $request ) ) : null;
	if ( null !== $provider_result ) {
		return (bool) $provider_result;
	}

	return almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request );
}
