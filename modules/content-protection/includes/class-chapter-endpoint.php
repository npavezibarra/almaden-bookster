<?php
/**
 * Authorized on-demand chapter delivery.
 *
 * @package AlmadenBookster
 */

namespace AlmadenBookster\ContentProtection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Chapter_Endpoint {
	const ACTION = 'almaden_bookster_load_reader_chapter';

	/**
	 * Register authenticated and anonymous AJAX handlers.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( __CLASS__, 'reject_anonymous' ) );
	}

	/**
	 * Return a uniform denial for anonymous requests.
	 *
	 * @return void
	 */
	public static function reject_anonymous() {
		self::send_no_store_headers();
		wp_send_json_error( array( 'message' => __( 'No tienes acceso a este capítulo.', 'almaden-bookster' ) ), 403 );
	}

	/**
	 * Validate the license and return one chapter body.
	 *
	 * @return void
	 */
	public static function handle() {
		self::send_no_store_headers();

		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			wp_send_json_error( array( 'message' => __( 'Método no permitido.', 'almaden-bookster' ) ), 405 );
		}

		$book_id    = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
		$chapter_id = isset( $_POST['chapter_id'] ) ? absint( $_POST['chapter_id'] ) : 0;
		$nonce      = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $book_id || ! $chapter_id || ! wp_verify_nonce( $nonce, 'almaden_reader_content_' . $book_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Solicitud de capítulo inválida.', 'almaden-bookster' ) ), 403 );
		}

		if ( ! is_user_logged_in() || ! function_exists( 'almaden_bookster_user_can_access_book' ) || ! almaden_bookster_user_can_access_book( $book_id ) ) {
			wp_send_json_error( array( 'message' => __( 'No tienes acceso a este capítulo.', 'almaden-bookster' ) ), 403 );
		}
		$policy = Content_Protection::get_policy( $book_id );
		if ( empty( $policy['enabled'] ) || 'on_demand' !== ( $policy['chapter_delivery'] ?? '' ) ) {
			wp_send_json_error( array( 'message' => __( 'La entrega protegida no está activa para este libro.', 'almaden-bookster' ) ), 404 );
		}
		$rate = Protection_Telemetry::check_chapter_rate( $book_id, $chapter_id );
		if ( empty( $rate['allowed'] ) ) {
			header( 'Retry-After: ' . absint( $rate['retry_after'] ), true );
			wp_send_json_error( array( 'message' => __( 'Demasiadas solicitudes de capítulos. Intenta nuevamente en unos minutos.', 'almaden-bookster' ), 'retryAfter' => absint( $rate['retry_after'] ) ), 429 );
		}

		$source_book_id = absint( get_post_meta( $book_id, '_almaden_source_book_id', true ) );
		$source_book_id = $source_book_id ? $source_book_id : $book_id;
		$chapter        = get_post( $chapter_id );
		if ( ! $chapter || 'book_chapter' !== $chapter->post_type || $source_book_id !== absint( $chapter->post_parent ) ) {
			wp_send_json_error( array( 'message' => __( 'Capítulo no encontrado.', 'almaden-bookster' ) ), 404 );
		}

		wp_send_json_success(
			array(
				'chapterId' => $chapter_id,
				'content'   => (string) $chapter->post_content,
			)
		);
	}

	/**
	 * Prevent browsers, proxies, and shared caches from storing chapter bodies.
	 *
	 * @return void
	 */
	private static function send_no_store_headers() {
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
		header( 'Pragma: no-cache', true );
		header( 'Vary: Cookie', false );
	}
}
