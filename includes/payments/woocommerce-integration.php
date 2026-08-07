<?php
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
			'book_id'            => 0,
			'product_id'         => 0,
			'parent_product_id'  => 0,
			'product_mode'       => 'none',
		);
	}

	$stored_relation = get_post_meta( $book_id, '_almaden_wc_relation', true );
	if ( is_array( $stored_relation ) ) {
		return array(
			'book_id'            => $book_id,
			'product_id'         => absint( $stored_relation['product_id'] ?? 0 ),
			'parent_product_id'  => absint( $stored_relation['parent_product_id'] ?? 0 ),
			'product_mode'       => almaden_bookster_normalize_wc_relation_mode( $stored_relation['product_mode'] ?? 'simple' ),
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
		'book_id'            => $book_id,
		'product_id'         => $product_id,
		'parent_product_id'  => $parent_product_id,
		'product_mode'       => $product_mode,
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
			'post_status'  => in_array( $status, array( 'publish', 'draft', 'pending', 'private' ), true ) ? $status : 'draft',
			'post_type'    => 'product_variation',
			'post_parent'  => $parent_product_id,
			'post_excerpt' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 40 ),
		),
		true
	);

	if ( is_wp_error( $variation_id ) || ! $variation_id ) {
		return 0;
	}

	update_post_meta( $variation_id, '_virtual', 'yes' );
	update_post_meta( $variation_id, '_downloadable', 'no' );
	update_post_meta( $variation_id, '_regular_price', '' );
	update_post_meta( $variation_id, '_sale_price', '' );
	update_post_meta( $variation_id, '_price', '' );
	update_post_meta( $variation_id, '_stock_status', 'instock' );
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
			'status'             => 'draft',
			'product_mode'       => 'simple',
			'parent_product_id'  => 0,
			'create_variation'   => true,
		)
	);

	$book_id = absint( $book_id );
	$book = get_post( $book_id );
	if ( ! $book || $book->post_type !== 'almaden-books' ) {
		return 0;
	}

	$product_mode = almaden_bookster_normalize_wc_relation_mode( $args['product_mode'] ?? 'simple' );
	$status = in_array( $args['status'] ?? 'draft', array( 'publish', 'draft', 'pending', 'private' ), true ) ? $args['status'] : 'draft';

	if ( 'variation' === $product_mode ) {
		$parent_product_id = absint( $args['parent_product_id'] ?? 0 );
		if ( $parent_product_id <= 0 ) {
			return 0;
		}

		$variation_id = almaden_bookster_create_wc_variation_for_book( $book_id, $parent_product_id, $status );
		if ( $variation_id <= 0 ) {
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

		return $variation_id;
	}

	if ( 'variable_parent' === $product_mode ) {
		$parent_product_id = wp_insert_post(
			array(
				'post_title'   => sprintf( 'Ebook: %s', $book->post_title ),
				'post_content' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 80 ),
				'post_excerpt' => $book->post_excerpt,
				'post_status'  => $status,
				'post_type'    => 'product',
			),
			true
		);

		if ( is_wp_error( $parent_product_id ) || ! $parent_product_id ) {
			return 0;
		}

		wp_set_object_terms( $parent_product_id, 'variable', 'product_type', false );
		update_post_meta( $parent_product_id, '_virtual', 'yes' );
		update_post_meta( $parent_product_id, '_downloadable', 'no' );
		update_post_meta( $parent_product_id, '_regular_price', '' );
		update_post_meta( $parent_product_id, '_sale_price', '' );
		update_post_meta( $parent_product_id, '_price', '' );
		update_post_meta( $parent_product_id, '_stock_status', 'instock' );

		$variation_id = almaden_bookster_create_wc_variation_for_book( $book_id, $parent_product_id, $status );
		if ( $variation_id <= 0 ) {
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

	$product_id = wp_insert_post(
		array(
			'post_title'   => sprintf( 'Ebook: %s', $book->post_title ),
			'post_content' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 80 ),
			'post_excerpt' => $book->post_excerpt,
			'post_status'  => $status,
			'post_type'    => 'product',
		),
		true
	);

	if ( is_wp_error( $product_id ) || ! $product_id ) {
		return 0;
	}

	wp_set_object_terms( $product_id, 'simple', 'product_type', false );
	update_post_meta( $product_id, '_virtual', 'yes' );
	update_post_meta( $product_id, '_downloadable', 'no' );
	update_post_meta( $product_id, '_regular_price', '' );
	update_post_meta( $product_id, '_sale_price', '' );
	update_post_meta( $product_id, '_price', '' );
	update_post_meta( $product_id, '_stock_status', 'instock' );

	almaden_bookster_sync_book_product_link(
		$book_id,
		$product_id,
		array(
			'product_mode' => 'simple',
		)
	);

	return (int) $product_id;
}

function almaden_bookster_get_or_create_book_product_id( $book_id, $create_if_missing = false, $status = 'publish', $args = array() ) {
	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_id = absint( $relation['product_id'] ?? 0 );
	if ( $product_id > 0 ) {
		return $product_id;
	}

	if ( ! $create_if_missing ) {
		return 0;
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

function almaden_bookster_woocommerce_get_book_purchase_url( $book_id ) {
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
	if ( empty( $url_host ) || empty( $site_host ) ) {
		return false;
	}

	return strtolower( $site_host ) === strtolower( $url_host );
}

function almaden_bookster_get_book_return_url( $book_id, $context = array() ) {
	$book_id = absint( $book_id );
	$context = is_array( $context ) ? $context : array();

	$explicit_return = '';
	foreach ( array( 'return_to', 'return_url', 'back_to' ) as $key ) {
		if ( ! empty( $_GET[ $key ] ) ) {
			$explicit_return = esc_url_raw( wp_unslash( $_GET[ $key ] ) );
			break;
		}
		if ( ! empty( $context[ $key ] ) ) {
			$explicit_return = esc_url_raw( $context[ $key ] );
			break;
		}
	}

	if ( $explicit_return && almaden_bookster_is_same_site_url( $explicit_return ) ) {
		return $explicit_return;
	}

	$settings = function_exists( 'almaden_bookster_get_distribution_settings' ) ? almaden_bookster_get_distribution_settings() : array();
	$policy = sanitize_key( (string) ( $settings['return_url_policy'] ?? 'product_or_fallback' ) );
	$relation = almaden_bookster_get_book_wc_relation( $book_id );
	$product_id = absint( $relation['parent_product_id'] ?? 0 );
	if ( $product_id <= 0 ) {
		$product_id = absint( $relation['product_id'] ?? 0 );
	}

	$product_url = $product_id > 0 ? get_permalink( $product_id ) : '';
	$store_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	if ( empty( $store_url ) && function_exists( 'almaden_bookster_get_store_page_url' ) ) {
		$store_url = almaden_bookster_get_store_page_url();
	}
	if ( empty( $store_url ) ) {
		$store_url = home_url( '/' );
	}

	switch ( $policy ) {
		case 'bookshelf_or_fallback':
			return ! empty( $store_url ) ? $store_url : home_url( '/' );
		case 'store_root':
			return ! empty( $store_url ) ? $store_url : home_url( '/' );
		case 'product_or_fallback':
		default:
			return ! empty( $product_url ) ? $product_url : $store_url;
	}
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
	return almaden_bookster_get_book_reader_url( $book_id, array(
		'return_to' => get_permalink( absint( $book_id ) ),
	) );
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

function almaden_bookster_render_book_reader_cta() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$book_id = almaden_bookster_get_book_id_for_product( $product->get_id() );
	if ( $book_id <= 0 ) {
		return;
	}

	$reader_url = almaden_bookster_get_book_reader_button_url( $book_id );
	?>
	<div class="almaden-bookster-reader-cta" style="margin: 16px 0 0; padding: 16px; border: 1px solid #e5e7eb; border-radius: 16px; background: #fafafa;">
		<div style="display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
			<div>
				<p style="margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.12em; color:#6b7280;">Bookster</p>
				<p style="margin:4px 0 0; font-size:16px; font-weight:700; color:#111827;">Lectura digital asociada a este producto</p>
			</div>
			<a class="button alt" href="<?php echo esc_url( $reader_url ); ?>" style="background:#111827; color:#fff; border-color:#111827;">
				<?php esc_html_e( 'LEER EBOOK', 'almaden-bookster' ); ?>
			</a>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_after_add_to_cart_form', 'almaden_bookster_render_book_reader_cta', 20 );

function almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request = array() ) {
	$book_id = absint( $book_id );
	if ( ! $book_id ) {
		return false;
	}

	if ( ! almaden_bookster_woocommerce_is_available() ) {
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

	if ( $product_id > 0 ) {
		return almaden_bookster_sync_book_product_link(
			$book_id,
			$product_id,
			array(
				'product_mode'      => $mode,
				'parent_product_id' => $parent_product_id,
			)
		);
	}

	return false;
}

function almaden_bookster_save_book_commerce_relation_from_request( $book_id, $request = array() ) {
	$provider_result = function_exists( 'almaden_bookster_commerce_provider_call' ) ? almaden_bookster_commerce_provider_call( 'save_relation_from_request', array( $book_id, $request ) ) : null;
	if ( null !== $provider_result ) {
		return (bool) $provider_result;
	}

	return almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request );
}

function almaden_bookster_get_book_terms_url() {
	$terms_url = '';
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$terms_url = wc_get_page_permalink( 'terms' );
	}
	if ( empty( $terms_url ) && function_exists( 'get_privacy_policy_url' ) ) {
		$terms_url = get_privacy_policy_url();
	}
	if ( empty( $terms_url ) ) {
		$terms_url = home_url( '/' );
	}

	return $terms_url;
}

function almaden_bookster_render_terms_checkbox() {
	if ( ! is_product() ) {
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

	$terms_url = almaden_bookster_get_book_terms_url();
	?>
	<div class="almaden-bookster-terms-box" style="margin: 0 0 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fafafa;">
		<label style="display:flex; gap:12px; align-items:flex-start; cursor:pointer;">
			<input type="checkbox" name="almaden_bookster_terms_accepted" value="1" style="margin-top:4px;" />
			<span>
				<?php esc_html_e( 'Acepto los términos y condiciones antes de agregar este ebook al carrito.', 'almaden-bookster' ); ?>
				<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver términos', 'almaden-bookster' ); ?></a>
			</span>
		</label>
		<input type="hidden" name="almaden_bookster_book_id" value="<?php echo esc_attr( $book_id ); ?>" />
		<?php wp_nonce_field( 'almaden_bookster_terms_' . $product->get_id(), 'almaden_bookster_terms_nonce' ); ?>
	</div>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'almaden_bookster_render_terms_checkbox' );

function almaden_bookster_validate_terms_before_add_to_cart( $passed, $product_id ) {
	$book_id = almaden_bookster_get_book_id_for_product( $product_id );
	if ( ! $book_id ) {
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
		if ( empty( $cart_item['almaden_book_id'] ) ) {
			continue;
		}

		if ( empty( $cart_item['almaden_bookster_terms_accepted'] ) ) {
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

	if ( $book_id > 0 ) {
		$item->add_meta_data( '_almaden_book_id', $book_id, true );
	}

	if ( $product_id > 0 ) {
		$item->add_meta_data( '_almaden_wc_product_id', $product_id, true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'almaden_bookster_store_book_link_on_order_item', 10, 4 );

function almaden_bookster_render_purchase_confirmation( $order_id ) {
	$order = function_exists( 'wc_get_order' ) ? wc_get_order( $order_id ) : null;
	if ( ! $order ) {
		return;
	}

	$book_links = array();
	foreach ( $order->get_items() as $item ) {
		$book_id = absint( $item->get_meta( '_almaden_book_id' ) );
		if ( ! $book_id ) {
			continue;
		}

		$book_links[] = sprintf(
			'<li><a href="%s">%s</a></li>',
			esc_url( get_permalink( $book_id ) ),
			esc_html( get_the_title( $book_id ) )
		);
	}

	if ( empty( $book_links ) ) {
		return;
	}

	?>
	<section class="almaden-bookster-thankyou" style="margin-top:24px; padding:20px; border:1px solid #e5e7eb; border-radius:16px; background:#fff;">
		<h2 style="margin:0 0 12px;"><?php esc_html_e( 'Tu ebook está vinculado a esta compra', 'almaden-bookster' ); ?></h2>
		<p style="margin:0 0 12px;">
			<?php echo esc_html( $order->is_paid() ? __( 'Tu pago fue confirmado. Ya puedes abrir la ficha del libro y acceder al contenido completo.', 'almaden-bookster' ) : __( 'Cuando el pedido quede pagado, podrás abrir la ficha del libro y acceder al contenido completo.', 'almaden-bookster' ) ); ?>
		</p>
		<ul style="margin:0; padding-left:18px;">
			<?php echo implode( '', $book_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</ul>
	</section>
	<?php
}
add_action( 'woocommerce_thankyou', 'almaden_bookster_render_purchase_confirmation' );

function almaden_bookster_register_woocommerce_commerce_provider() {
	if ( ! function_exists( 'almaden_bookster_register_commerce_provider' ) ) {
		return;
	}

	almaden_bookster_register_commerce_provider(
		array(
			'key'       => 'woocommerce',
			'label'     => 'WooCommerce',
			'available' => 'almaden_bookster_woocommerce_is_available',
			'supports'  => array( 'catalog', 'products', 'variations', 'purchase_link', 'access_checks', 'order_links' ),
			'create_book_product' => static function( $book_id, $args = array() ) {
				return almaden_bookster_create_book_product( $book_id, $args );
			},
			'get_or_create_book_product_id' => static function( $book_id, $create_if_missing = false, $status = 'publish', $args = array() ) {
				return almaden_bookster_get_or_create_book_product_id( $book_id, $create_if_missing, $status, $args );
			},
			'get_purchase_url' => static function( $book_id, $context = array() ) {
				return almaden_bookster_woocommerce_get_book_purchase_url( $book_id, $context );
			},
			'has_access' => static function( $book_id, $user_id = null ) {
				return almaden_bookster_woocommerce_user_has_wc_access_for_book( $book_id, $user_id );
			},
			'save_relation_from_request' => static function( $book_id, $request = array() ) {
				return almaden_bookster_woocommerce_save_book_commerce_relation_from_request( $book_id, $request );
			},
		)
	);
}
almaden_bookster_register_woocommerce_commerce_provider();
