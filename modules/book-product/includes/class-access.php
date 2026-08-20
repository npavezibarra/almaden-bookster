<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Access {
	public static function decision( $book_id, $user_id = null ) {
		$book_id = absint( $book_id );
		if ( ! Relation_Repository::has_current_relation( $book_id ) ) {
			return null;
		}

		$user_id = null === $user_id ? get_current_user_id() : absint( $user_id );

		$relation = Relation_Repository::get( $book_id );
		$ebook_id = absint( $relation['ebook_product_id'] ?? 0 );
		$both_id = absint( $relation['both_product_id'] ?? 0 );
		$access_ids = array_filter( array( $ebook_id, $both_id ) );
		if ( ! $access_ids || ! $user_id || ! function_exists( 'wc_customer_bought_product' ) ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		foreach ( $access_ids as $product_id ) {
			if ( wc_customer_bought_product( $user->user_email, $user_id, $product_id ) ) {
				return true;
			}
		}

		return false;
	}
}
