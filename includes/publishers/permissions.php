<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_publisher_member_row( $publisher_id, $user_id ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	$user_id      = absint( $user_id );

	if ( $publisher_id <= 0 || $user_id <= 0 ) {
		return null;
	}

	$table_name = almaden_bookster_get_publisher_members_table_name();
	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE publisher_id = %d AND user_id = %d LIMIT 1",
			$publisher_id,
			$user_id
		),
		ARRAY_A
	);
}

function almaden_bookster_get_user_publisher_memberships( $user_id = null ) {
	global $wpdb;

	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( $user_id <= 0 ) {
		return array();
	}

	$table_name = almaden_bookster_get_publisher_members_table_name();
	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d AND status = %s ORDER BY joined_at ASC, id ASC",
			$user_id,
			'active'
		),
		ARRAY_A
	);
}

function almaden_bookster_user_is_publisher_member( $user_id, $publisher_id, $roles = array() ) {
	$user_id = absint( $user_id );
	$publisher_id = absint( $publisher_id );

	if ( $user_id <= 0 || $publisher_id <= 0 ) {
		return false;
	}

	$member = almaden_bookster_get_publisher_member_row( $publisher_id, $user_id );
	if ( empty( $member ) ) {
		return false;
	}

	if ( empty( $roles ) ) {
		return true;
	}

	$roles = array_map( 'sanitize_key', (array) $roles );
	return in_array( sanitize_key( $member['role'] ), $roles, true );
}

function almaden_bookster_user_can_manage_publisher( $publisher_id, $user_id = null ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $user_id <= 0 ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return almaden_bookster_user_is_publisher_member( $user_id, $publisher_id, array( 'owner', 'editor' ) );
}

function almaden_bookster_user_can_manage_book( $book_id, $user_id = null ) {
	$book_id = absint( $book_id );
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

	if ( $book_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_post', $book_id ) ) {
		return true;
	}

	$publisher_id = function_exists( 'almaden_bookster_get_book_publisher_id' ) ? almaden_bookster_get_book_publisher_id( $book_id ) : 0;
	if ( $publisher_id <= 0 ) {
		return function_exists( 'almaden_bookster_user_is_book_author' ) ? almaden_bookster_user_is_book_author( $book_id, $user_id ) : false;
	}

	if ( almaden_bookster_user_can_manage_publisher( $publisher_id, $user_id ) ) {
		return true;
	}

	return function_exists( 'almaden_bookster_user_is_book_author' ) ? almaden_bookster_user_is_book_author( $book_id, $user_id ) : false;
}

function almaden_bookster_user_can_edit_book_chapters( $book_id, $user_id = null ) {
	return almaden_bookster_user_can_manage_book( $book_id, $user_id );
}

function almaden_bookster_upsert_publisher_member( $publisher_id, $user_id, $role = 'author', $status = 'active' ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	$user_id      = absint( $user_id );
	$role         = sanitize_key( $role );
	$status       = sanitize_key( $status );

	if ( $publisher_id <= 0 || $user_id <= 0 ) {
		return new WP_Error( 'almaden_invalid_member', __( 'La membresía requiere editorial y usuario válidos.', 'almaden-bookster' ) );
	}

	$table_name = almaden_bookster_get_publisher_members_table_name();
	$existing   = almaden_bookster_get_publisher_member_row( $publisher_id, $user_id );
	$payload    = array(
		'publisher_id' => $publisher_id,
		'user_id'      => $user_id,
		'role'         => '' !== $role ? $role : 'author',
		'status'       => '' !== $status ? $status : 'active',
		'updated_at'   => current_time( 'mysql' ),
	);

	if ( $existing ) {
		$result = $wpdb->update( $table_name, $payload, array( 'id' => absint( $existing['id'] ) ) );
		if ( false === $result ) {
			return new WP_Error( 'almaden_member_update_failed', __( 'No se pudo actualizar la membresía.', 'almaden-bookster' ) );
		}

		return absint( $existing['id'] );
	}

	$payload['joined_at'] = current_time( 'mysql' );
	$result = $wpdb->insert( $table_name, $payload );
	if ( false === $result ) {
		return new WP_Error( 'almaden_member_insert_failed', __( 'No se pudo guardar la membresía.', 'almaden-bookster' ) );
	}

	return absint( $wpdb->insert_id );
}

function almaden_bookster_remove_publisher_member( $publisher_id, $user_id ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	$user_id      = absint( $user_id );

	if ( $publisher_id <= 0 || $user_id <= 0 ) {
		return false;
	}

	$table_name = almaden_bookster_get_publisher_members_table_name();
	return false !== $wpdb->delete(
		$table_name,
		array(
			'publisher_id' => $publisher_id,
			'user_id'      => $user_id,
		),
		array( '%d', '%d' )
	);
}

function almaden_bookster_get_publisher_member_role_options() {
	return array(
		'owner'    => __( 'Propietario', 'almaden-bookster' ),
		'editor'   => __( 'Editor', 'almaden-bookster' ),
		'author'   => __( 'Autor', 'almaden-bookster' ),
		'corrector'=> __( 'Corrector', 'almaden-bookster' ),
		'designer' => __( 'Diseñador', 'almaden-bookster' ),
	);
}
