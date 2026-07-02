<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_woocommerce_is_available() {
	return function_exists( 'wc_get_product' ) && function_exists( 'wc_get_page_permalink' );
}

function almaden_bookster_get_book_id_for_product( $product_id ) {
	return absint( get_post_meta( absint( $product_id ), '_almaden_book_id', true ) );
}

function almaden_bookster_get_book_wc_product_id( $book_id ) {
	return absint( get_post_meta( absint( $book_id ), '_almaden_wc_product_id', true ) );
}

function almaden_bookster_sync_book_product_link( $book_id, $product_id ) {
	$book_id = absint( $book_id );
	$product_id = absint( $product_id );

	if ( ! $book_id || ! $product_id ) {
		return false;
	}

	update_post_meta( $book_id, '_almaden_wc_product_id', $product_id );
	update_post_meta( $product_id, '_almaden_book_id', $book_id );

	return true;
}

function almaden_bookster_create_book_product( $book_id, $status = 'publish' ) {
	if ( ! almaden_bookster_woocommerce_is_available() ) {
		return 0;
	}

	$book_id = absint( $book_id );
	$book = get_post( $book_id );
	if ( ! $book || $book->post_type !== 'almaden-books' ) {
		return 0;
	}

	$product_id = wp_insert_post(
		array(
			'post_title'   => sprintf( 'Ebook: %s', $book->post_title ),
			'post_content' => wp_trim_words( wp_strip_all_tags( $book->post_content ), 80 ),
			'post_excerpt' => $book->post_excerpt,
			'post_status'  => in_array( $status, array( 'publish', 'draft', 'pending' ), true ) ? $status : 'draft',
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

	almaden_bookster_sync_book_product_link( $book_id, $product_id );

	return (int) $product_id;
}

function almaden_bookster_get_or_create_book_product_id( $book_id, $create_if_missing = false, $status = 'publish' ) {
	$product_id = almaden_bookster_get_book_wc_product_id( $book_id );
	if ( $product_id > 0 ) {
		return $product_id;
	}

	if ( ! $create_if_missing ) {
		return 0;
	}

	return almaden_bookster_create_book_product( $book_id, $status );
}

function almaden_bookster_get_book_purchase_url( $book_id ) {
	$product_id = almaden_bookster_get_book_wc_product_id( $book_id );
	if ( $product_id > 0 ) {
		$product = get_post( $product_id );
		if ( $product && 'publish' === $product->post_status ) {
			return get_permalink( $product_id );
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
