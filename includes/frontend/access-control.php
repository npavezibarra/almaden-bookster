<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_user_can_access_book( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( ! $book_id ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $book_id ) ) {
		return true;
	}

	$product_id = function_exists( 'almaden_bookster_get_book_wc_product_id' ) ? almaden_bookster_get_book_wc_product_id( $book_id ) : 0;
	if ( $product_id <= 0 ) {
		return true;
	}

	if ( $user_id <= 0 || ! function_exists( 'wc_customer_bought_product' ) ) {
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return false;
	}

	return wc_customer_bought_product( $user->user_email, $user_id, $product_id );
}
