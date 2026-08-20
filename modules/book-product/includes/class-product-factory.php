<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Product_Factory {
	public static function create_parent( $book_id, $args ) {
		if ( ! class_exists( 'WC_Product_Variable' ) ) {
			return new \WP_Error( 'woocommerce_unavailable', 'WooCommerce no está disponible.' );
		}

		$product = new \WC_Product_Variable();
		$product->set_name( sanitize_text_field( $args['title'] ?? get_the_title( $book_id ) ) );
		$product->set_description( wp_kses_post( $args['description'] ?? '' ) );
		$product->set_status( 'draft' );
		$product->set_catalog_visibility( 'visible' );
		self::set_format_attribute( $product );
		$product_id = $product->save();

		return $product_id ? absint( $product_id ) : new \WP_Error( 'product_create_failed', 'No se pudo crear el producto.' );
	}

	public static function convert_simple_to_variable( $product_id ) {
		$product = wc_get_product( absint( $product_id ) );
		if ( ! $product ) {
			return new \WP_Error( 'product_not_found', 'No se encontró el producto seleccionado.' );
		}
		if ( $product->is_type( 'variable' ) ) {
			return array_merge( array( 'parent_id' => $product->get_id() ), Product_Catalog::find_format_variations( $product->get_id() ) );
		}
		if ( ! $product->is_type( 'simple' ) ) {
			return new \WP_Error( 'unsupported_product_type', 'Sólo se pueden convertir productos simples.' );
		}

		$physical_data = array(
			'price'             => $product->get_regular_price() ?: $product->get_price(),
			'sale_price'        => $product->get_sale_price(),
			'manage_stock'      => $product->get_manage_stock(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'stock_status'      => $product->get_stock_status(),
			'backorders'        => $product->get_backorders(),
			'weight'            => $product->get_weight(),
			'length'            => $product->get_length(),
			'width'             => $product->get_width(),
			'height'            => $product->get_height(),
			'shipping_class_id' => $product->get_shipping_class_id(),
			'tax_class'         => $product->get_tax_class(),
		);

		wp_set_object_terms( $product_id, 'variable', 'product_type', false );
		$parent = new \WC_Product_Variable( $product_id );
		$parent->set_regular_price( '' );
		$parent->set_sale_price( '' );
		$parent->set_price( '' );
		$parent->set_manage_stock( false );
		self::set_format_attribute( $parent );
		$parent->save();

		$physical_data['allow_empty_price'] = true;
		$physical_id = self::create_variation( $product_id, 'physical', $physical_data );
		if ( is_wp_error( $physical_id ) ) {
			return $physical_id;
		}

		self::sync_parent( $product_id );
		return array( 'parent_id' => absint( $product_id ), 'physical' => absint( $physical_id ), 'ebook' => 0 );
	}

	public static function create_variation( $parent_id, $format, $args = array() ) {
		$parent = wc_get_product( absint( $parent_id ) );
		if ( ! $parent || ! $parent->is_type( 'variable' ) ) {
			return new \WP_Error( 'variable_parent_required', 'El producto padre debe ser variable.' );
		}
		if ( ! in_array( $format, array( 'physical', 'ebook', 'both' ), true ) ) {
			return new \WP_Error( 'invalid_format', 'El formato solicitado no es válido.' );
		}

		$existing = Product_Catalog::find_format_variations( $parent_id );
		if ( ! empty( $existing[ $format ] ) ) {
			self::sync_parent( $parent_id );
			return absint( $existing[ $format ] );
		}

		$price = wc_format_decimal( $args['price'] ?? '' );
		if ( '' === $price && empty( $args['allow_empty_price'] ) ) {
			return new \WP_Error( 'price_required', 'Debes indicar un precio para este formato.' );
		}

		$attribute_key = self::set_format_attribute( $parent );
		$parent->save();
		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( absint( $parent_id ) );
		$variation->set_status( 'publish' );
		$variation->set_attributes( array( $attribute_key => self::format_slug( $format ) ) );
		if ( '' !== $price ) {
			$variation->set_regular_price( $price );
		}
		if ( '' !== (string) ( $args['sale_price'] ?? '' ) ) {
			$variation->set_sale_price( wc_format_decimal( $args['sale_price'] ) );
		}

		if ( 'ebook' === $format ) {
			$variation->set_virtual( true );
			$variation->set_downloadable( false );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( 'instock' );
		} else {
			self::apply_physical_data( $variation, $args );
		}

		$variation_id = $variation->save();
		if ( ! $variation_id ) {
			return new \WP_Error( 'variation_create_failed', 'No se pudo crear la variación.' );
		}

		self::sync_parent( $parent_id );
		return absint( $variation_id );
	}

	public static function update_variation( $variation_id, $format, $args = array() ) {
		$variation = wc_get_product( absint( $variation_id ) );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			return new \WP_Error( 'variation_not_found', 'No se encontró la variación solicitada.' );
		}

		$price = wc_format_decimal( $args['price'] ?? '' );
		if ( '' !== $price ) {
			$variation->set_regular_price( $price );
		}
		if ( '' !== (string) ( $args['sale_price'] ?? '' ) ) {
			$variation->set_sale_price( wc_format_decimal( $args['sale_price'] ) );
		}

		if ( 'ebook' === $format ) {
			$variation->set_virtual( true );
			$variation->set_downloadable( false );
			$variation->set_manage_stock( false );
			$variation->set_stock_status( 'instock' );
		} else {
			self::apply_physical_data( $variation, $args );
		}

		$variation_id = $variation->save();
		if ( ! $variation_id ) {
			return new \WP_Error( 'variation_update_failed', 'No se pudo actualizar la variación.' );
		}

		self::sync_parent( $variation->get_parent_id() );
		return absint( $variation_id );
	}

	private static function set_format_attribute( $product ) {
		$attributes = $product->get_attributes();
		if ( isset( $attributes['pa_formato'] ) && $attributes['pa_formato'] instanceof \WC_Product_Attribute ) {
			$attribute = $attributes['pa_formato'];
			$options = $attribute->get_options();
			foreach ( array( 'fisico', 'ebook', 'ambos' ) as $slug ) {
				$term = get_term_by( 'slug', $slug, 'pa_formato' );
				if ( ! $term ) {
					$created = wp_insert_term( ucfirst( $slug ), 'pa_formato', array( 'slug' => $slug ) );
					$term_id = is_wp_error( $created ) ? 0 : absint( $created['term_id'] ?? 0 );
				} else {
					$term_id = absint( $term->term_id );
				}
				if ( $term_id && ! in_array( $term_id, array_map( 'absint', $options ), true ) ) {
					$options[] = $term_id;
				}
			}
			$attribute->set_options( $options );
			$attribute->set_variation( true );
			$attributes['pa_formato'] = $attribute;
			$product->set_attributes( $attributes );
			return 'pa_formato';
		}

		if ( isset( $attributes['formato'] ) && $attributes['formato'] instanceof \WC_Product_Attribute ) {
			$attribute = $attributes['formato'];
			$options = array_values( array_unique( array_merge( $attribute->get_options(), array( 'fisico', 'ebook', 'ambos' ) ) ) );
			$attribute->set_options( $options );
			$attribute->set_variation( true );
			$attributes['formato'] = $attribute;
			$product->set_attributes( $attributes );
			return 'formato';
		}

		$format_attribute = new \WC_Product_Attribute();
		$format_attribute->set_id( 0 );
		$format_attribute->set_name( 'Formato' );
		$format_attribute->set_options( array( 'fisico', 'ebook', 'ambos' ) );
		$format_attribute->set_position( 0 );
		$format_attribute->set_visible( true );
		$format_attribute->set_variation( true );
		$attributes['formato'] = $format_attribute;
		$product->set_attributes( $attributes );
		return 'formato';
	}

	private static function apply_physical_data( $variation, $args ) {
		$manage_stock = ! empty( $args['manage_stock'] ) || '' !== (string) ( $args['stock_quantity'] ?? '' );
		$variation->set_virtual( false );
		$variation->set_manage_stock( $manage_stock );
		if ( $manage_stock ) {
			$stock_quantity = max( 0, intval( $args['stock_quantity'] ?? 0 ) );
			$variation->set_stock_quantity( $stock_quantity );
			$variation->set_stock_status( isset( $args['stock_status'] ) ? sanitize_key( $args['stock_status'] ) : ( $stock_quantity > 0 ? 'instock' : 'outofstock' ) );
		} else {
			$variation->set_stock_status( sanitize_key( $args['stock_status'] ?? 'instock' ) );
		}
		$variation->set_backorders( sanitize_key( $args['backorders'] ?? 'no' ) );
		foreach ( array( 'weight', 'length', 'width', 'height' ) as $dimension ) {
			if ( '' !== (string) ( $args[ $dimension ] ?? '' ) ) {
				$setter = 'set_' . $dimension;
				$variation->{$setter}( wc_format_decimal( $args[ $dimension ] ) );
			}
		}
		if ( ! empty( $args['shipping_class_id'] ) ) {
			$variation->set_shipping_class_id( absint( $args['shipping_class_id'] ) );
		}
		if ( isset( $args['tax_class'] ) ) {
			$variation->set_tax_class( sanitize_title( $args['tax_class'] ) );
		}
	}

	private static function format_slug( $format ) {
		if ( 'both' === $format ) {
			return 'ambos';
		}
		return 'physical' === $format ? 'fisico' : 'ebook';
	}

	private static function sync_parent( $parent_id ) {
		$parent_id = absint( $parent_id );
		if ( ! $parent_id ) {
			return;
		}

		\WC_Product_Variable::sync( $parent_id );

		$parent = wc_get_product( $parent_id );
		if ( $parent && $parent->is_type( 'variable' ) ) {
			self::set_format_attribute( $parent );
			$parent->save();
		}

		wc_delete_product_transients( $parent_id );
	}
}
