<?php
namespace AlmadenBookster\BookProduct;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Ajax_Controller {
	const ACTIONS = array(
		'almaden_book_product_search'         => 'search',
		'almaden_book_product_state'          => 'state',
		'almaden_book_product_link'           => 'link',
		'almaden_book_product_create'         => 'create',
		'almaden_book_product_update'         => 'update',
		'almaden_book_product_update_status'  => 'update_status',
		'almaden_book_product_add_format'     => 'add_format',
		'almaden_book_product_update_format'  => 'update_format',
		'almaden_book_product_unlink_format'  => 'unlink_format',
		'almaden_book_product_unlink_product' => 'unlink_product',
		'almaden_book_product_save_samples'   => 'save_samples',
	);

	public static function init() {
		foreach ( self::ACTIONS as $action => $method ) {
			add_action( 'wp_ajax_' . $action, array( __CLASS__, $method ) );
		}
	}

	public static function search() {
		$book_id = self::authorize();
		$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
		self::success( array( 'results' => Product_Catalog::search( $term, $book_id ) ) );
	}

	public static function state() {
		$book_id = self::authorize();
		self::success( array( 'state' => Product_Service::state( $book_id ) ) );
	}

	public static function link() {
		$book_id = self::authorize();
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$ebook_price = isset( $_POST['ebook_price'] ) ? wc_clean( wp_unslash( $_POST['ebook_price'] ) ) : '';
		self::send_result( Product_Service::link( $book_id, $product_id, $ebook_price ) );
	}

	public static function create() {
		$book_id = self::authorize();
		$physical_stock = isset( $_POST['physical_stock'] ) ? trim( (string) wp_unslash( $_POST['physical_stock'] ) ) : '';
		$both_stock = isset( $_POST['both_stock'] ) ? trim( (string) wp_unslash( $_POST['both_stock'] ) ) : '';
		$args = array(
			'title'          => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : get_the_title( $book_id ),
			'description'    => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
			'physical'       => ! empty( $_POST['physical'] ),
			'physical_price' => isset( $_POST['physical_price'] ) ? wc_clean( wp_unslash( $_POST['physical_price'] ) ) : '',
			'physical_stock' => '' === $physical_stock ? '' : wc_stock_amount( $physical_stock ),
			'ebook'          => ! empty( $_POST['ebook'] ),
			'ebook_price'    => isset( $_POST['ebook_price'] ) ? wc_clean( wp_unslash( $_POST['ebook_price'] ) ) : '',
			'both'           => ! empty( $_POST['both'] ),
			'both_price'     => isset( $_POST['both_price'] ) ? wc_clean( wp_unslash( $_POST['both_price'] ) ) : '',
			'both_stock'     => '' === $both_stock ? '' : wc_stock_amount( $both_stock ),
		);
		self::send_result( Product_Service::create( $book_id, $args ) );
	}

	public static function update() {
		$book_id = self::authorize();
		$args = array(
			'title'       => isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : get_the_title( $book_id ),
			'description' => isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '',
		);
		self::send_result( Product_Service::update_product( $book_id, $args ) );
	}

	public static function update_status() {
		$book_id = self::authorize();
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		self::send_result( Product_Service::update_product_status( $book_id, $status ) );
	}

	public static function add_format() {
		$book_id = self::authorize();
		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : '';
		$stock = isset( $_POST['stock'] ) ? trim( (string) wp_unslash( $_POST['stock'] ) ) : '';
		$args = array(
			'price'          => isset( $_POST['price'] ) ? wc_clean( wp_unslash( $_POST['price'] ) ) : '',
			'stock_quantity' => '' === $stock ? '' : wc_stock_amount( $stock ),
		);
		self::send_result( Product_Service::add_format( $book_id, $format, $args ) );
	}

	public static function update_format() {
		$book_id = self::authorize();
		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : '';
		$stock = isset( $_POST['stock'] ) ? trim( (string) wp_unslash( $_POST['stock'] ) ) : '';
		$args = array(
			'price'          => isset( $_POST['price'] ) ? wc_clean( wp_unslash( $_POST['price'] ) ) : '',
			'stock_quantity' => '' === $stock ? '' : wc_stock_amount( $stock ),
		);
		self::send_result( Product_Service::update_format( $book_id, $format, $args ) );
	}

	public static function unlink_format() {
		$book_id = self::authorize();
		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : '';
		if ( ! in_array( $format, array( 'physical', 'ebook', 'both' ), true ) ) {
			wp_send_json_error( array( 'message' => 'El formato solicitado no es válido.' ), 400 );
		}
		self::send_result( Product_Service::unlink_format( $book_id, $format ) );
	}

	public static function unlink_product() {
		$book_id = self::authorize();
		self::send_result( Product_Service::unlink_product( $book_id ) );
	}

	public static function save_samples() {
		$book_id = self::authorize_book();
		$raw      = isset( $_POST['chapter_ids'] ) ? wp_unslash( $_POST['chapter_ids'] ) : '[]';
		$ids      = json_decode( $raw, true );
		if ( ! is_array( $ids ) ) {
			wp_send_json_error( array( 'message' => 'La selección de capítulos no es válida.' ), 400 );
		}

		self::success( array( 'chapters' => Sample_Repository::save( $book_id, $ids ) ) );
	}

	private static function authorize_book() {
		$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $book_id || ! wp_verify_nonce( $nonce, 'almaden_book_product_' . $book_id ) ) {
			wp_send_json_error( array( 'message' => 'La sesión de edición expiró.' ), 403 );
		}
		$can_manage_book = current_user_can( 'edit_post', $book_id )
			|| ( function_exists( 'almaden_bookster_user_can_manage_book' ) && almaden_bookster_user_can_manage_book( $book_id, get_current_user_id() ) );
		if ( ! $can_manage_book || 'almaden-books' !== get_post_type( $book_id ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para administrar las muestras de este libro.' ), 403 );
		}

		return $book_id;
	}

	private static function authorize() {
		$book_id = self::authorize_book();
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para administrar productos de este libro.' ), 403 );
		}
		if ( ! function_exists( 'wc_get_product' ) ) {
			wp_send_json_error( array( 'message' => 'WooCommerce o el libro no están disponibles.' ), 400 );
		}

		return $book_id;
	}

	private static function send_result( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
		}
		self::success( array( 'state' => $result ) );
	}

	private static function success( $data ) {
		wp_send_json_success( $data );
	}
}
