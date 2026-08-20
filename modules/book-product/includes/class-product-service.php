<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product_Service {
	public static function state( $book_id ) {
		$relation = Relation_Repository::get( $book_id );
		$parent_id = absint( $relation['parent_product_id'] ?? 0 );
		$available_slots = $parent_id ? Product_Catalog::find_format_variations( $parent_id ) : array( 'physical' => 0, 'ebook' => 0, 'both' => 0 );

		if ( $parent_id && ! empty( $relation['legacy'] ) ) {
			if ( ! $relation['physical_product_id'] && $available_slots['physical'] ) {
				$relation['physical_product_id'] = $available_slots['physical'];
			}
			if ( ! $relation['ebook_product_id'] && $available_slots['ebook'] ) {
				$relation['ebook_product_id'] = $available_slots['ebook'];
			}
			if ( ! $relation['both_product_id'] && $available_slots['both'] ) {
				$relation['both_product_id'] = $available_slots['both'];
			}
		}

		$parent = $parent_id && function_exists( 'wc_get_product' ) ? wc_get_product( $parent_id ) : null;
		$physical = Product_Catalog::format_summary( $relation['physical_product_id'], 'physical' );
		$ebook = Product_Catalog::format_summary( $relation['ebook_product_id'], 'ebook' );
		$both = Product_Catalog::format_summary( $relation['both_product_id'], 'both' );
		if ( ! $physical['linked'] && $available_slots['physical'] ) {
			$physical['available'] = true;
			$physical['available_product_id'] = $available_slots['physical'];
		}
		if ( ! $ebook['linked'] && $available_slots['ebook'] ) {
			$ebook['available'] = true;
			$ebook['available_product_id'] = $available_slots['ebook'];
		}
		if ( ! $both['linked'] && $available_slots['both'] ) {
			$both['available'] = true;
			$both['available_product_id'] = $available_slots['both'];
		}
		return array(
			'linked'   => (bool) $parent,
			'relation' => $relation,
			'product'  => $parent ? Product_Catalog::summary( $parent, $book_id ) : null,
			'formats'  => array(
				'both'     => $both,
				'physical' => $physical,
				'ebook'    => $ebook,
			),
		);
	}

	public static function link( $book_id, $product_id, $ebook_price = '' ) {
		$product = wc_get_product( absint( $product_id ) );
		if ( ! $product || $product->is_type( 'variation' ) ) {
			return new \WP_Error( 'invalid_product', 'Selecciona un producto padre válido.' );
		}
		$claim_error = self::claim_error( $product->get_id(), $book_id );
		if ( $claim_error ) {
			return $claim_error;
		}

		if ( $product->is_type( 'simple' ) ) {
			if ( '' === trim( (string) $ebook_price ) ) {
				return new \WP_Error( 'ebook_price_required', 'Indica el precio del Ebook antes de convertir el producto.' );
			}
			$conversion = Product_Factory::convert_simple_to_variable( $product->get_id() );
			if ( is_wp_error( $conversion ) ) {
				return $conversion;
			}
			$parent_id = $conversion['parent_id'];
			$physical_id = $conversion['physical'];
			$ebook_id = 0;
			$both_id = 0;
		} elseif ( $product->is_type( 'variable' ) ) {
			$parent_id = $product->get_id();
			$slots = Product_Catalog::find_format_variations( $parent_id );
			$physical_id = $slots['physical'];
			$ebook_id = $slots['ebook'];
			$both_id = $slots['both'];
			foreach ( array_filter( $slots ) as $variation_id ) {
				$variation_claim = self::claim_error( $variation_id, $book_id );
				if ( $variation_claim ) {
					return $variation_claim;
				}
			}
		} else {
			return new \WP_Error( 'unsupported_product_type', 'Este tipo de producto no se puede vincular.' );
		}

		if ( ! $ebook_id && ! $both_id && '' === trim( (string) $ebook_price ) ) {
			return new \WP_Error( 'ebook_price_required', 'Indica el precio del Ebook para crear esa variación.' );
		}
		if ( ! $ebook_id && '' !== trim( (string) $ebook_price ) ) {
			$ebook_id = Product_Factory::create_variation( $parent_id, 'ebook', array( 'price' => $ebook_price ) );
			if ( is_wp_error( $ebook_id ) ) {
				return $ebook_id;
			}
		}

		Relation_Repository::save( $book_id, array(
			'parent_product_id'   => $parent_id,
			'physical_product_id' => $physical_id,
			'ebook_product_id'    => $ebook_id,
			'both_product_id'     => $both_id,
			'managed_physical'    => false,
			'managed_ebook'       => false,
			'managed_both'        => false,
		) );

		return self::state( $book_id );
	}

	public static function create( $book_id, $args ) {
		$physical = ! empty( $args['physical'] );
		$ebook = ! empty( $args['ebook'] );
		$both = ! empty( $args['both'] );
		if ( ! $physical && ! $ebook && ! $both ) {
			return new \WP_Error( 'format_required', 'Selecciona al menos un formato.' );
		}

		$parent_id = Product_Factory::create_parent( $book_id, $args );
		if ( is_wp_error( $parent_id ) ) {
			return $parent_id;
		}

		$created_ids = array();
		$physical_id = 0;
		$ebook_id = 0;
		$both_id = 0;
		if ( $physical ) {
			$physical_id = Product_Factory::create_variation( $parent_id, 'physical', array(
				'price'          => $args['physical_price'] ?? '',
				'stock_quantity' => $args['physical_stock'] ?? '',
			) );
			if ( is_wp_error( $physical_id ) ) {
				self::rollback_created_products( $parent_id, $created_ids );
				return $physical_id;
			}
			$created_ids[] = $physical_id;
		}
		if ( $ebook ) {
			$ebook_id = Product_Factory::create_variation( $parent_id, 'ebook', array( 'price' => $args['ebook_price'] ?? '' ) );
			if ( is_wp_error( $ebook_id ) ) {
				self::rollback_created_products( $parent_id, $created_ids );
				return $ebook_id;
			}
			$created_ids[] = $ebook_id;
		}
		if ( $both ) {
			$both_id = Product_Factory::create_variation( $parent_id, 'both', array(
				'price'          => $args['both_price'] ?? '',
				'stock_quantity' => $args['both_stock'] ?? '',
			) );
			if ( is_wp_error( $both_id ) ) {
				self::rollback_created_products( $parent_id, $created_ids );
				return $both_id;
			}
			$created_ids[] = $both_id;
		}

		Relation_Repository::save( $book_id, array(
			'parent_product_id'   => $parent_id,
			'physical_product_id' => $physical_id,
			'ebook_product_id'    => $ebook_id,
			'both_product_id'     => $both_id,
			'managed_physical'    => $physical,
			'managed_ebook'       => $ebook,
			'managed_both'        => $both,
		) );

		return self::state( $book_id );
	}

	public static function update_product( $book_id, $args ) {
		$state = Relation_Repository::get( $book_id );
		$parent_id = absint( $state['parent_product_id'] ?? 0 );
		if ( ! $parent_id || ! function_exists( 'wc_get_product' ) ) {
			return new \WP_Error( 'product_required', 'Primero vincula o crea un producto.' );
		}

		$product = wc_get_product( $parent_id );
		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', 'No se encontró el producto padre.' );
		}

		$title = sanitize_text_field( $args['title'] ?? $product->get_name() );
		$product->set_name( '' !== trim( $title ) ? $title : $product->get_name() );
		$product->set_description( wp_kses_post( $args['description'] ?? $product->get_description() ) );
		$product->save();

		return self::state( $book_id );
	}

	public static function add_format( $book_id, $format, $args ) {
		$state = Relation_Repository::get( $book_id );
		$parent_id = absint( $state['parent_product_id'] ?? 0 );
		if ( ! $parent_id ) {
			return new \WP_Error( 'product_required', 'Primero vincula o crea un producto.' );
		}
		$format = sanitize_key( $format );
		if ( ! in_array( $format, array( 'physical', 'ebook', 'both' ), true ) ) {
			return new \WP_Error( 'invalid_format', 'El formato solicitado no es válido.' );
		}
		$product_id = Product_Factory::create_variation( $parent_id, $format, $args );
		if ( is_wp_error( $product_id ) ) {
			return $product_id;
		}

		$key = self::slot_key( $format );
		$managed_key = self::managed_key( $format );
		$state[ $key ] = $product_id;
		$state[ $managed_key ] = true;
		Relation_Repository::save( $book_id, $state );

		return self::state( $book_id );
	}

	public static function update_format( $book_id, $format, $args ) {
		$state = Relation_Repository::get( $book_id );
		$parent_id = absint( $state['parent_product_id'] ?? 0 );
		if ( ! $parent_id ) {
			return new \WP_Error( 'product_required', 'Primero vincula o crea un producto.' );
		}

		$format = sanitize_key( $format );
		if ( ! in_array( $format, array( 'physical', 'ebook', 'both' ), true ) ) {
			return new \WP_Error( 'invalid_format', 'El formato solicitado no es válido.' );
		}
		$key = self::slot_key( $format );
		$managed_key = self::managed_key( $format );
		$product_id = absint( $state[ $key ] ?? 0 );
		if ( ! $product_id ) {
			$slots = Product_Catalog::find_format_variations( $parent_id );
			$product_id = absint( $slots[ $format ] ?? 0 );
		}

		$result = $product_id
			? Product_Factory::update_variation( $product_id, $format, $args )
			: Product_Factory::create_variation( $parent_id, $format, $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$state[ $key ] = absint( $result );
		$state[ $managed_key ] = true;
		Relation_Repository::save( $book_id, $state );

		return self::state( $book_id );
	}

	public static function unlink_format( $book_id, $format ) {
		$state = Relation_Repository::get( $book_id );
		$format = sanitize_key( $format );
		if ( ! in_array( $format, array( 'physical', 'ebook', 'both' ), true ) ) {
			return new \WP_Error( 'invalid_format', 'El formato solicitado no es válido.' );
		}
		$key = self::slot_key( $format );
		$managed_key = self::managed_key( $format );
		$state[ $key ] = 0;
		$state[ $managed_key ] = false;
		Relation_Repository::save( $book_id, $state );

		return self::state( $book_id );
	}

	public static function unlink_product( $book_id ) {
		Relation_Repository::clear( $book_id );
		return self::state( $book_id );
	}

	private static function claim_error( $product_id, $book_id ) {
		$owner = Product_Catalog::owner_id( $product_id );
		if ( $owner && $owner !== absint( $book_id ) ) {
			return new \WP_Error( 'product_claimed', 'Este producto ya está vinculado a otro libro.' );
		}

		return null;
	}

	private static function slot_key( $format ) {
		return array(
			'physical' => 'physical_product_id',
			'ebook'    => 'ebook_product_id',
			'both'     => 'both_product_id',
		)[ $format ] ?? 'ebook_product_id';
	}

	private static function managed_key( $format ) {
		return array(
			'physical' => 'managed_physical',
			'ebook'    => 'managed_ebook',
			'both'     => 'managed_both',
		)[ $format ] ?? 'managed_ebook';
	}

	private static function rollback_created_products( $parent_id, $variation_ids ) {
		foreach ( array_filter( array_map( 'absint', (array) $variation_ids ) ) as $variation_id ) {
			wp_delete_post( $variation_id, true );
		}
		if ( $parent_id ) {
			wp_delete_post( $parent_id, true );
		}
	}
}
