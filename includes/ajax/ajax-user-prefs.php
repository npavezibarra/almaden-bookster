<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_save_user_prefs() {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Not logged in' ) );
	}

	$prefs_json = isset( $_POST['prefs'] ) ? wp_unslash( $_POST['prefs'] ) : '';
	if ( empty( $prefs_json ) ) {
		wp_send_json_error( array( 'message' => 'Empty preferences' ) );
	}

	$prefs = json_decode( $prefs_json, true );
	if ( ! is_array( $prefs ) ) {
		wp_send_json_error( array( 'message' => 'Invalid JSON' ) );
	}

	update_user_meta( get_current_user_id(), 'almaden_bookster_reader_prefs', $prefs );
	wp_send_json_success();
}
add_action( 'wp_ajax_almaden_save_user_prefs', 'almaden_save_user_prefs' );
