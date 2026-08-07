<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_commerce_hardening_report_defaults() {
	return array(
		'version'      => 1,
		'last_run'     => '',
		'books_scanned' => 0,
		'books_fixed'  => 0,
		'books_cleared' => 0,
		'conflicts'    => 0,
		'legacy_migrated' => 0,
		'message'      => '',
	);
}

function almaden_bookster_get_commerce_hardening_report() {
	$report = get_option( 'almaden_bookster_commerce_hardening_report', array() );

	if ( ! is_array( $report ) ) {
		$report = array();
	}

	return wp_parse_args( $report, almaden_bookster_get_commerce_hardening_report_defaults() );
}

function almaden_bookster_store_commerce_hardening_report( $report ) {
	$report = is_array( $report ) ? $report : array();
	update_option( 'almaden_bookster_commerce_hardening_report', wp_parse_args( $report, almaden_bookster_get_commerce_hardening_report_defaults() ) );
}

function almaden_bookster_normalize_book_commerce_relation_state( $book_id, &$stats = array(), &$claimed_products = array() ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return false;
	}

	if ( ! is_array( $stats ) ) {
		$stats = array();
	}
	if ( ! is_array( $claimed_products ) ) {
		$claimed_products = array();
	}

	$stats['books_scanned'] = isset( $stats['books_scanned'] ) ? absint( $stats['books_scanned'] ) + 1 : 1;

	$relation = function_exists( 'almaden_bookster_get_book_wc_relation' ) ? almaden_bookster_get_book_wc_relation( $book_id ) : array();
	$product_id = absint( $relation['product_id'] ?? 0 );
	$parent_product_id = absint( $relation['parent_product_id'] ?? 0 );
	$product_mode = function_exists( 'almaden_bookster_normalize_wc_relation_mode' ) ? almaden_bookster_normalize_wc_relation_mode( $relation['product_mode'] ?? 'none' ) : 'none';

	$legacy_relation = get_post_meta( $book_id, '_almaden_wc_relation', true );
	$legacy_payload = is_array( $legacy_relation ) ? $legacy_relation : array();
	$legacy_keys_present = ! empty( $legacy_payload ) || metadata_exists( 'post', $book_id, '_almaden_wc_product_id' ) || metadata_exists( 'post', $book_id, '_almaden_wc_parent_product_id' ) || metadata_exists( 'post', $book_id, '_almaden_wc_product_mode' );

	if ( $product_id <= 0 && $parent_product_id <= 0 ) {
		if ( $legacy_keys_present ) {
			if ( function_exists( 'almaden_bookster_clear_book_wc_relation' ) ) {
				almaden_bookster_clear_book_wc_relation( $book_id );
			}
			$stats['books_cleared'] = isset( $stats['books_cleared'] ) ? absint( $stats['books_cleared'] ) + 1 : 1;
		}
		return false;
	}

	$product = $product_id > 0 ? get_post( $product_id ) : null;
	$parent  = $parent_product_id > 0 ? get_post( $parent_product_id ) : null;

	if ( $product_id > 0 && ! $product ) {
		if ( function_exists( 'almaden_bookster_clear_book_wc_relation' ) ) {
			almaden_bookster_clear_book_wc_relation( $book_id );
		}
		$stats['books_cleared'] = isset( $stats['books_cleared'] ) ? absint( $stats['books_cleared'] ) + 1 : 1;
		return false;
	}

	if ( $parent_product_id > 0 && ! $parent ) {
		$parent_product_id = 0;
		if ( 'variation' === $product_mode ) {
			$product_mode = 'simple';
		}
	}

	if ( $product instanceof WP_Post && 'product_variation' === $product->post_type ) {
		$product_mode = 'variation';
		if ( $parent_product_id <= 0 ) {
			$parent_product_id = absint( $product->post_parent );
		}
	} elseif ( $product instanceof WP_Post && 'product' === $product->post_type && $parent_product_id > 0 ) {
		$product_mode = 'variation';
	}

	if ( $parent_product_id > 0 && isset( $claimed_products[ $parent_product_id ] ) && absint( $claimed_products[ $parent_product_id ] ) !== $book_id ) {
		if ( function_exists( 'almaden_bookster_clear_book_wc_relation' ) ) {
			almaden_bookster_clear_book_wc_relation( $book_id );
		}
		$stats['conflicts'] = isset( $stats['conflicts'] ) ? absint( $stats['conflicts'] ) + 1 : 1;
		return false;
	}

	if ( $product_id > 0 && isset( $claimed_products[ $product_id ] ) && absint( $claimed_products[ $product_id ] ) !== $book_id ) {
		if ( function_exists( 'almaden_bookster_clear_book_wc_relation' ) ) {
			almaden_bookster_clear_book_wc_relation( $book_id );
		}
		$stats['conflicts'] = isset( $stats['conflicts'] ) ? absint( $stats['conflicts'] ) + 1 : 1;
		return false;
	}

	if ( $product_id > 0 ) {
		$claimed_products[ $product_id ] = $book_id;
	}
	if ( $parent_product_id > 0 ) {
		$claimed_products[ $parent_product_id ] = $book_id;
	}

	if ( function_exists( 'almaden_bookster_sync_book_product_link' ) ) {
		almaden_bookster_sync_book_product_link(
			$book_id,
			$product_id,
			array(
				'parent_product_id' => $parent_product_id,
				'product_mode'      => $product_mode,
			)
		);
	}

	if ( $legacy_keys_present ) {
		$stats['legacy_migrated'] = isset( $stats['legacy_migrated'] ) ? absint( $stats['legacy_migrated'] ) + 1 : 1;
	}

	$stats['books_fixed'] = isset( $stats['books_fixed'] ) ? absint( $stats['books_fixed'] ) + 1 : 1;

	return true;
}

function almaden_bookster_run_commerce_hardening_migration() {
	$books = get_posts(
		array(
			'post_type'              => 'almaden-books',
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'suppress_filters'       => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	$stats = array(
		'books_scanned'   => 0,
		'books_fixed'     => 0,
		'books_cleared'   => 0,
		'conflicts'       => 0,
		'legacy_migrated' => 0,
	);
	$claimed_products = array();

	foreach ( $books as $book_id ) {
		almaden_bookster_normalize_book_commerce_relation_state( $book_id, $stats, $claimed_products );
	}

	$report = array_merge(
		almaden_bookster_get_commerce_hardening_report_defaults(),
		$stats,
		array(
			'version'  => 1,
			'last_run' => current_time( 'mysql' ),
			'message'  => sprintf(
				'Escaneados: %d · actualizados: %d · limpiados: %d · conflictos: %d',
				absint( $stats['books_scanned'] ),
				absint( $stats['books_fixed'] ),
				absint( $stats['books_cleared'] ),
				absint( $stats['conflicts'] )
			),
		)
	);

	almaden_bookster_store_commerce_hardening_report( $report );

	return $report;
}

function almaden_bookster_maybe_run_commerce_hardening_migration() {
	$stored = almaden_bookster_get_commerce_hardening_report();
	if ( absint( $stored['version'] ?? 0 ) >= 1 && ! empty( $stored['last_run'] ) ) {
		return;
	}

	almaden_bookster_run_commerce_hardening_migration();
}
add_action( 'init', 'almaden_bookster_maybe_run_commerce_hardening_migration', 35 );

function almaden_bookster_handle_run_commerce_hardening() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_commerce_hardening_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_commerce_hardening_nonce'], 'almaden_bookster_run_commerce_hardening' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$report = almaden_bookster_run_commerce_hardening_migration();

	$redirect_url = add_query_arg(
		array(
			'page'                     => 'almaden-bookster-distribution',
			'commerce_hardening_done'  => '1',
			'commerce_hardening_scan'  => absint( $report['books_scanned'] ?? 0 ),
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_run_commerce_hardening', 'almaden_bookster_handle_run_commerce_hardening' );

