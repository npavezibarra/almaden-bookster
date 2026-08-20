<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Relation_Repository {
	const META_KEY = '_almaden_book_product_relation';
	const SCHEMA_VERSION = 3;

	public static function has_current_relation( $book_id ) {
		return metadata_exists( 'post', absint( $book_id ), self::META_KEY );
	}

	public static function empty_state( $book_id = 0 ) {
		return array(
			'schema_version'      => self::SCHEMA_VERSION,
			'book_id'             => absint( $book_id ),
			'parent_product_id'   => 0,
			'physical_product_id' => 0,
			'ebook_product_id'    => 0,
			'both_product_id'     => 0,
			'managed_physical'    => false,
			'managed_ebook'       => false,
			'managed_both'        => false,
			'legacy'              => false,
		);
	}

	public static function get( $book_id ) {
		$book_id = absint( $book_id );
		$stored = get_post_meta( $book_id, self::META_KEY, true );
		if ( is_array( $stored ) && self::SCHEMA_VERSION === absint( $stored['schema_version'] ?? 0 ) ) {
			return self::normalize( $stored, $book_id );
		}

		return self::from_legacy( $book_id );
	}

	public static function save( $book_id, $state ) {
		$book_id = absint( $book_id );
		if ( ! $book_id ) {
			return false;
		}

		$previous = self::get( $book_id );
		$state = self::normalize( $state, $book_id );
		$state['legacy'] = false;
		update_post_meta( $book_id, self::META_KEY, $state );
		self::sync_reverse_links( $book_id, $previous, $state );
		self::sync_legacy_meta( $book_id, $state );

		return $state;
	}

	public static function clear( $book_id ) {
		$book_id = absint( $book_id );
		$previous = self::get( $book_id );
		delete_post_meta( $book_id, self::META_KEY );
		self::sync_reverse_links( $book_id, $previous, self::empty_state( $book_id ) );
		self::delete_legacy_meta( $book_id );

		return self::empty_state( $book_id );
	}

	private static function normalize( $state, $book_id ) {
		$normalized = self::empty_state( $book_id );
		foreach ( array( 'parent_product_id', 'physical_product_id', 'ebook_product_id', 'both_product_id' ) as $key ) {
			$normalized[ $key ] = absint( $state[ $key ] ?? 0 );
		}
		$normalized['managed_physical'] = ! empty( $state['managed_physical'] );
		$normalized['managed_ebook'] = ! empty( $state['managed_ebook'] );
		$normalized['managed_both'] = ! empty( $state['managed_both'] );
		$normalized['legacy'] = ! empty( $state['legacy'] );

		return $normalized;
	}

	private static function from_legacy( $book_id ) {
		$state = self::empty_state( $book_id );
		$product_id = absint( get_post_meta( $book_id, '_almaden_wc_product_id', true ) );
		$parent_id = absint( get_post_meta( $book_id, '_almaden_wc_parent_product_id', true ) );
		$mode = sanitize_key( (string) get_post_meta( $book_id, '_almaden_wc_product_mode', true ) );

		if ( ! $product_id && ! $parent_id ) {
			return $state;
		}

		$state['legacy'] = true;
		if ( 'variation' === $mode ) {
			$state['parent_product_id'] = $parent_id;
			$state['ebook_product_id'] = $product_id;
		} elseif ( 'variable_parent' === $mode ) {
			$state['parent_product_id'] = $parent_id ?: $product_id;
		} else {
			$state['parent_product_id'] = $product_id;
			$state['ebook_product_id'] = $product_id;
		}

		return $state;
	}

	private static function sync_reverse_links( $book_id, $previous, $state ) {
		$old_ids = array_filter( array(
			absint( $previous['parent_product_id'] ?? 0 ),
			absint( $previous['physical_product_id'] ?? 0 ),
			absint( $previous['ebook_product_id'] ?? 0 ),
			absint( $previous['both_product_id'] ?? 0 ),
		) );
		$new_ids = array_filter( array(
			absint( $state['parent_product_id'] ?? 0 ),
			absint( $state['physical_product_id'] ?? 0 ),
			absint( $state['ebook_product_id'] ?? 0 ),
			absint( $state['both_product_id'] ?? 0 ),
		) );

		foreach ( array_diff( $old_ids, $new_ids ) as $product_id ) {
			if ( absint( get_post_meta( $product_id, '_almaden_book_product_book_id', true ) ) === $book_id ) {
				delete_post_meta( $product_id, '_almaden_book_product_book_id' );
			}
			if ( absint( get_post_meta( $product_id, '_almaden_book_id', true ) ) === $book_id ) {
				delete_post_meta( $product_id, '_almaden_book_id' );
			}
		}

		foreach ( $new_ids as $product_id ) {
			update_post_meta( $product_id, '_almaden_book_product_book_id', $book_id );
		}

		$ebook_id = absint( $state['ebook_product_id'] ?? 0 );
		$both_id = absint( $state['both_product_id'] ?? 0 );
		$parent_id = absint( $state['parent_product_id'] ?? 0 );
		if ( $ebook_id || $both_id ) {
			if ( $ebook_id ) {
				update_post_meta( $ebook_id, '_almaden_book_id', $book_id );
			}
			if ( $both_id ) {
				update_post_meta( $both_id, '_almaden_book_id', $book_id );
			}
			if ( $parent_id ) {
				update_post_meta( $parent_id, '_almaden_book_id', $book_id );
			}
		} else {
			foreach ( array_unique( array_filter( array( $parent_id, absint( $state['physical_product_id'] ?? 0 ) ) ) ) as $product_id ) {
				if ( absint( get_post_meta( $product_id, '_almaden_book_id', true ) ) === $book_id ) {
					delete_post_meta( $product_id, '_almaden_book_id' );
				}
			}
		}
	}

	private static function sync_legacy_meta( $book_id, $state ) {
		$parent_id = absint( $state['parent_product_id'] ?? 0 );
		$product_id = absint( $state['ebook_product_id'] ?? 0 ) ?: absint( $state['both_product_id'] ?? 0 ) ?: absint( $state['physical_product_id'] ?? 0 ) ?: $parent_id;
		if ( ! $product_id ) {
			self::delete_legacy_meta( $book_id );
			return;
		}

		$mode = $parent_id && $product_id !== $parent_id ? 'variation' : 'variable_parent';
		$legacy = array(
			'product_id'        => $product_id,
			'parent_product_id' => $parent_id && $parent_id !== $product_id ? $parent_id : 0,
			'product_mode'      => $mode,
		);
		update_post_meta( $book_id, '_almaden_wc_relation', $legacy );
		update_post_meta( $book_id, '_almaden_wc_product_id', $product_id );
		update_post_meta( $book_id, '_almaden_wc_product_mode', $mode );
		if ( $legacy['parent_product_id'] ) {
			update_post_meta( $book_id, '_almaden_wc_parent_product_id', $legacy['parent_product_id'] );
		} else {
			delete_post_meta( $book_id, '_almaden_wc_parent_product_id' );
		}
	}

	private static function delete_legacy_meta( $book_id ) {
		foreach ( array( '_almaden_wc_relation', '_almaden_wc_product_id', '_almaden_wc_parent_product_id', '_almaden_wc_product_mode' ) as $key ) {
			delete_post_meta( $book_id, $key );
		}
	}
}
