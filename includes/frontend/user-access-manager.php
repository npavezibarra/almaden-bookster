<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_manual_access_meta_key( $type ) {
	return 'almaden_bookster_manual_' . ( 'course' === $type ? 'course' : 'book' ) . '_access';
}

function almaden_bookster_get_manual_access_ids( $user_id, $type ) {
	$values = get_user_meta( absint( $user_id ), almaden_bookster_get_manual_access_meta_key( $type ), true );
	return array_values( array_unique( array_filter( array_map( 'absint', is_array( $values ) ? $values : array() ) ) ) );
}

function almaden_bookster_user_has_manual_access( $user_id, $type, $resource_id ) {
	return in_array( absint( $resource_id ), almaden_bookster_get_manual_access_ids( $user_id, $type ), true );
}

function almaden_bookster_set_manual_access( $user_id, $type, $resource_id, $enabled ) {
	$user_id = absint( $user_id );
	$resource_id = absint( $resource_id );
	if ( $user_id <= 0 || $resource_id <= 0 ) {
		return false;
	}
	$ids = almaden_bookster_get_manual_access_ids( $user_id, $type );
	if ( $enabled ) {
		$ids[] = $resource_id;
		$ids = array_values( array_unique( array_map( 'absint', $ids ) ) );
	} else {
		$ids = array_values( array_diff( $ids, array( $resource_id ) ) );
	}
	return update_user_meta( $user_id, almaden_bookster_get_manual_access_meta_key( $type ), $ids );
}

function almaden_bookster_user_has_manual_course_access( $course_id, $user_id = null ) {
	return almaden_bookster_user_has_manual_access( null === $user_id ? get_current_user_id() : $user_id, 'course', $course_id );
}

function almaden_bookster_user_has_manual_book_access( $book_id, $user_id = null ) {
	return almaden_bookster_user_has_manual_access( null === $user_id ? get_current_user_id() : $user_id, 'book', $book_id );
}

function almaden_bookster_get_user_access_manager_payload( $user_id, $type ) {
	$user_id = absint( $user_id );
	$type = 'ebook' === $type ? 'ebook' : 'course';
	$manual_ids = almaden_bookster_get_manual_access_ids( $user_id, 'ebook' === $type ? 'book' : 'course' );
	$items = array();
	if ( 'course' === $type ) {
		$posts = get_posts( array( 'post_type' => 'almdn_learni_course', 'post_status' => array( 'publish', 'private', 'draft', 'pending' ), 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
		foreach ( $posts as $post ) {
			$items[] = array( 'id' => (int) $post->ID, 'title' => get_the_title( $post->ID ), 'has_access' => in_array( (int) $post->ID, $manual_ids, true ) );
		}
	} else {
		$posts = get_posts( array( 'post_type' => 'almaden-books', 'post_status' => array( 'publish', 'private', 'draft', 'pending' ), 'posts_per_page' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
		foreach ( $posts as $post ) {
			$has_access = in_array( (int) $post->ID, $manual_ids, true ) || ( function_exists( 'almaden_bookster_user_can_access_book' ) && almaden_bookster_user_can_access_book( $post->ID, $user_id ) );
			$items[] = array( 'id' => (int) $post->ID, 'title' => get_the_title( $post->ID ), 'has_access' => (bool) $has_access );
		}
	}
	return $items;
}

function almaden_bookster_user_access_manager_ajax() {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'almaden_user_access_manager', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Permisos insuficientes.' ), 403 );
	}
	$action = sanitize_key( isset( $_POST['manager_action'] ) ? wp_unslash( $_POST['manager_action'] ) : '' );
	if ( 'search_users' === $action ) {
		$term = sanitize_text_field( isset( $_POST['term'] ) ? wp_unslash( $_POST['term'] ) : '' );
		$users = $term ? get_users( array( 'search' => '*' . esc_attr( $term ) . '*', 'search_columns' => array( 'user_login', 'user_email', 'display_name', 'first_name', 'last_name' ), 'number' => 8 ) ) : array();
		$results = array();
		foreach ( $users as $user ) {
			$results[] = array( 'id' => (int) $user->ID, 'name' => $user->display_name ?: $user->user_login, 'email' => $user->user_email, 'avatar' => get_avatar_url( $user->ID, array( 'size' => 96 ) ) );
		}
		wp_send_json_success( $results );
	}
	$user_id = absint( isset( $_POST['user_id'] ) ? $_POST['user_id'] : 0 );
	if ( ! get_user_by( 'id', $user_id ) ) {
		wp_send_json_error( array( 'message' => 'Usuario no encontrado.' ), 404 );
	}
	if ( 'load_resources' === $action ) {
		wp_send_json_success( almaden_bookster_get_user_access_manager_payload( $user_id, sanitize_key( isset( $_POST['type'] ) ? wp_unslash( $_POST['type'] ) : 'course' ) ) );
	}
	if ( 'toggle_access' === $action ) {
		$type = sanitize_key( isset( $_POST['type'] ) ? wp_unslash( $_POST['type'] ) : '' );
		$resource_id = absint( isset( $_POST['resource_id'] ) ? $_POST['resource_id'] : 0 );
		$enabled = ! empty( $_POST['enabled'] );
		if ( ! in_array( $type, array( 'course', 'ebook' ), true ) || ! $resource_id ) {
			wp_send_json_error( array( 'message' => 'Recurso inválido.' ), 400 );
		}
		almaden_bookster_set_manual_access( $user_id, 'ebook' === $type ? 'book' : 'course', $resource_id, $enabled );
		wp_send_json_success( almaden_bookster_get_user_access_manager_payload( $user_id, $type ) );
	}
	wp_send_json_error( array( 'message' => 'Acción no reconocida.' ), 400 );
}
add_action( 'wp_ajax_almaden_user_access_manager', 'almaden_bookster_user_access_manager_ajax' );
