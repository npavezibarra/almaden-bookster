<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_save_cover_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_cover_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$cover_data = array(
		'paper_type'   => isset($_POST['paper_type']) ? sanitize_text_field($_POST['paper_type']) : '',
		'front_flap'   => isset($_POST['front_flap']) ? floatval($_POST['front_flap']) : 0,
		'back_flap'    => isset($_POST['back_flap']) ? floatval($_POST['back_flap']) : 0,
		'front_image'  => isset($_POST['front_image']) ? esc_url_raw($_POST['front_image']) : '',
		'back_image'   => isset($_POST['back_image']) ? esc_url_raw($_POST['back_image']) : '',
		'spread_image' => isset($_POST['spread_image']) ? esc_url_raw($_POST['spread_image']) : '',
		'spine_image'  => isset($_POST['spine_image']) ? esc_url_raw($_POST['spine_image']) : '',
		'spine_color'  => isset($_POST['spine_color']) ? sanitize_hex_color($_POST['spine_color']) : '',
		'front_flap_width' => isset($_POST['front_flap_width']) ? floatval($_POST['front_flap_width']) : 0,
		'back_flap_width'  => isset($_POST['back_flap_width']) ? floatval($_POST['back_flap_width']) : 0,
		'front_flap_image' => isset($_POST['front_flap_image']) ? esc_url_raw($_POST['front_flap_image']) : '',
		'front_flap_color' => isset($_POST['front_flap_color']) ? sanitize_hex_color($_POST['front_flap_color']) : '',
		'back_flap_image'  => isset($_POST['back_flap_image']) ? esc_url_raw($_POST['back_flap_image']) : '',
		'back_flap_color'  => isset($_POST['back_flap_color']) ? sanitize_hex_color($_POST['back_flap_color']) : '',
		'text_layers'  => isset($_POST['text_layers']) ? json_decode(stripslashes($_POST['text_layers']), true) : array(),
	);

	update_post_meta( $book_id, '_almaden_cover_settings', $cover_data );
	
	wp_send_json_success( array( 'message' => 'Configuración de portada guardada con éxito.' ) );
}
add_action( 'wp_ajax_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_cover_settings', 'almaden_bookster_save_cover_ajax' );

