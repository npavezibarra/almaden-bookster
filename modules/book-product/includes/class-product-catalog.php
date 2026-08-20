<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product_Catalog {
	public static function search( $term, $book_id, $limit = 8 ) {
		$term = sanitize_text_field( (string) $term );
		if ( strlen( $term ) < 2 || ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => min( 12, max( 1, absint( $limit ) ) ),
			'orderby'        => 'relevance title',
			'order'          => 'ASC',
			's'              => $term,
			'fields'         => 'ids',
		);

		if ( ctype_digit( $term ) ) {
			$args['post__in'] = array( absint( $term ) );
			unset( $args['s'] );
		}

		$ids = get_posts( $args );
		if ( ! ctype_digit( $term ) ) {
			$sku_ids = get_posts( array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'meta_query'     => array(
					array(
						'key'     => '_sku',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			) );
			$ids = array_unique( array_merge( $ids, array_map( 'absint', $sku_ids ) ) );
		}

		$results = array();
		foreach ( array_slice( $ids, 0, $limit ) as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || $product->is_type( 'variation' ) ) {
				continue;
			}
			$results[] = self::summary( $product, $book_id );
		}

		return $results;
	}

	public static function summary( $product, $book_id = 0 ) {
		if ( ! $product ) {
			return null;
		}

		$product_id = absint( $product->get_id() );
		$owner = self::owner_id( $product_id );
		return array(
			'id'             => $product_id,
			'name'           => $product->get_name(),
			'description'    => $product->get_description(),
			'sku'            => $product->get_sku(),
			'type'           => $product->get_type(),
			'status'         => $product->get_status(),
			'price'          => $product->get_price(),
			'edit_url'       => get_edit_post_link( $product_id, 'raw' ) ?: '',
			'claimed'        => $owner > 0 && $owner !== absint( $book_id ),
			'claimed_book_id'=> $owner,
		);
	}

	public static function owner_id( $product_id ) {
		$product_id = absint( $product_id );
		return absint( get_post_meta( $product_id, '_almaden_book_product_book_id', true ) )
			?: absint( get_post_meta( $product_id, '_almaden_book_id', true ) );
	}

	public static function find_format_variations( $parent_product_id ) {
		$slots = array( 'physical' => 0, 'ebook' => 0, 'both' => 0 );
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( $parent_product_id ) ) : null;
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return $slots;
		}

		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( ! $variation ) {
				continue;
			}
			$format = self::variation_format( $variation );
			if ( $format && ! $slots[ $format ] ) {
				$slots[ $format ] = absint( $variation_id );
			}
		}

		return $slots;
	}

	public static function variation_format( $variation ) {
		$attributes = $variation->get_attributes();
		$value = $attributes['formato']
			?? $attributes['pa_formato']
			?? $attributes['attribute_formato']
			?? $attributes['attribute_pa_formato']
			?? get_post_meta( $variation->get_id(), 'attribute_formato', true )
			?? get_post_meta( $variation->get_id(), 'attribute_pa_formato', true );
		$value = sanitize_title( remove_accents( (string) $value ) );
		if ( in_array( $value, array( 'fisico', 'physical', 'impreso', 'print' ), true ) ) {
			return 'physical';
		}
		if ( in_array( $value, array( 'ebook', 'digital', 'e-book' ), true ) ) {
			return 'ebook';
		}
		if ( in_array( $value, array( 'ambos', 'both', 'bundle', 'combo' ), true ) ) {
			return 'both';
		}

		return '';
	}

	public static function format_summary( $product_id, $format ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( $product_id ) ) : null;
		if ( ! $product ) {
			return array( 'linked' => false, 'product_id' => 0, 'format' => $format );
		}

		return array(
			'linked'      => true,
			'product_id'  => absint( $product->get_id() ),
			'format'      => $format,
			'price'       => $product->get_regular_price(),
			'stock'       => in_array( $format, array( 'physical', 'both' ), true ) && $product->managing_stock() ? $product->get_stock_quantity() : null,
			'sku'         => $product->get_sku(),
			'edit_url'    => get_edit_post_link( $product->get_id(), 'raw' ) ?: '',
		);
	}
}
