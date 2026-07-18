<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_publisher_tour_meta_key() {
	return '_almaden_bookster_publisher_tour_status';
}

function almaden_bookster_get_publisher_tour_status( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $user_id <= 0 ) {
		return 'none';
	}

	$status = get_user_meta( $user_id, almaden_bookster_get_publisher_tour_meta_key(), true );
	return '' !== $status ? sanitize_key( $status ) : 'none';
}

function almaden_bookster_mark_publisher_tour_active( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	update_user_meta( $user_id, almaden_bookster_get_publisher_tour_meta_key(), 'active' );
	update_user_meta( $user_id, '_almaden_bookster_publisher_tour_started_at', current_time( 'mysql' ) );

	return true;
}

function almaden_bookster_mark_publisher_tour_completed( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	update_user_meta( $user_id, almaden_bookster_get_publisher_tour_meta_key(), 'done' );
	update_user_meta( $user_id, '_almaden_bookster_publisher_tour_completed_at', current_time( 'mysql' ) );

	return true;
}

function almaden_bookster_should_show_publisher_tour( $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	return 'active' === almaden_bookster_get_publisher_tour_status( $user_id );
}

function almaden_bookster_get_publisher_tour_checklist_items() {
	return array(
		array(
			'title' => __( 'Crear el primer libro', 'almaden-bookster' ),
			'description' => __( 'Abre el modal de creación y guarda tu primer título para activar el taller.', 'almaden-bookster' ),
		),
		array(
			'title' => __( 'Subir portada y contenido', 'almaden-bookster' ),
			'description' => __( 'Entra al editor, pega el texto base y adjunta portada si ya la tienes lista.', 'almaden-bookster' ),
		),
		array(
			'title' => __( 'Publicar o importar', 'almaden-bookster' ),
			'description' => __( 'Cuando el libro esté listo, publícalo o importa una versión previa para continuar.', 'almaden-bookster' ),
		),
	);
}

function almaden_bookster_get_publisher_tour_quick_actions() {
	return array(
		array(
			'label' => __( 'Crear mi primer libro', 'almaden-bookster' ),
			'target' => '#open-modal-btn',
			'variant' => 'primary',
		),
		array(
			'label' => __( 'Explorar el taller', 'almaden-bookster' ),
			'target' => '#booklist-empty-state',
			'variant' => 'ghost',
		),
	);
}

function almaden_bookster_handle_complete_publisher_tour() {
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	if ( ! isset( $_POST['almaden_publisher_tour_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_publisher_tour_nonce'], 'almaden_publisher_tour_complete' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	almaden_bookster_mark_publisher_tour_completed();

	$redirect_url = almaden_bookster_get_creator_page_url(
		array(
			'publisher_tour_completed' => '1',
		)
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_complete_publisher_tour', 'almaden_bookster_handle_complete_publisher_tour' );
