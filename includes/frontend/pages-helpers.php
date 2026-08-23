<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_user_can_manage_courses' ) ) {
	function almaden_bookster_user_can_manage_courses() {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_almaden_learni' ) || current_user_can( 'edit_posts' );
	}
}

if ( ! function_exists( 'almaden_bookster_get_frontend_page_access_mode' ) ) {
	function almaden_bookster_get_frontend_page_access_mode( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );

		$access_map = array(
			'shell_home'     => 'private',
			'contractor'     => 'private',
			'dashboard'      => 'private',
			'reading_stats'  => 'private',
			'authors'        => 'public',
			'publisher'      => 'public',
			'store'          => 'public',
			'creator'        => 'private',
			'course_creator' => 'private',
			'course_archive' => 'private',
			'blog_creator'   => 'private',
			'quiz_builder'   => 'private',
		);

		return isset( $access_map[ $page_key ] ) ? $access_map[ $page_key ] : 'private';
	}
}

if ( ! function_exists( 'almaden_bookster_get_current_user_roles' ) ) {
	function almaden_bookster_get_current_user_roles() {
		if ( ! function_exists( 'wp_get_current_user' ) ) {
			return array();
		}

		$user = wp_get_current_user();
		if ( ! $user || empty( $user->ID ) ) {
			return array();
		}

		$roles = isset( $user->roles ) && is_array( $user->roles ) ? $user->roles : array();

		return array_values(
			array_filter(
				array_map(
					static function( $role_key ) {
						return sanitize_key( (string) $role_key );
					},
					$roles
				)
			)
		);
	}
}

if ( ! function_exists( 'almaden_bookster_user_can_use_access_preview' ) ) {
	function almaden_bookster_user_can_use_access_preview() {
		return function_exists( 'current_user_can' ) && current_user_can( 'manage_options' );
	}
}

if ( ! function_exists( 'almaden_bookster_get_access_preview_role' ) ) {
	function almaden_bookster_get_access_preview_role() {
		if ( ! function_exists( 'almaden_bookster_user_can_use_access_preview' ) || ! almaden_bookster_user_can_use_access_preview() ) {
			return '';
		}

		$allowed_roles = function_exists( 'almaden_bookster_get_access_preview_roles' ) ? array_keys( almaden_bookster_get_access_preview_roles() ) : array( 'administrator', 'editor', 'author', 'customer', 'subscriber' );
		$requested_role = '';

		if ( isset( $_COOKIE['almaden_bookster_preview_role'] ) ) {
			$requested_role = sanitize_key( (string) wp_unslash( $_COOKIE['almaden_bookster_preview_role'] ) );
		} elseif ( isset( $_GET['almaden_preview_role'] ) ) {
			$requested_role = sanitize_key( (string) wp_unslash( $_GET['almaden_preview_role'] ) );
		}

		if ( '' === $requested_role || ! in_array( $requested_role, $allowed_roles, true ) ) {
			return 'administrator';
		}

		return $requested_role;
	}
}

if ( ! function_exists( 'almaden_bookster_user_can_access_frontend_page_for_role' ) ) {
	function almaden_bookster_user_can_access_frontend_page_for_role( $page_key, $role_key ) {
		$page_key = sanitize_key( (string) $page_key );
		$role_key = sanitize_key( (string) $role_key );
		$mode     = function_exists( 'almaden_bookster_get_frontend_page_access_mode' ) ? almaden_bookster_get_frontend_page_access_mode( $page_key ) : 'private';
		$allowed_roles = function_exists( 'almaden_bookster_get_page_allowed_roles' ) ? almaden_bookster_get_page_allowed_roles( $page_key ) : array();

		if ( '' === $page_key ) {
			return true;
		}

		if ( '' === $role_key ) {
			return 'public' === $mode;
		}

		if ( ! empty( $allowed_roles ) ) {
			return in_array( $role_key, $allowed_roles, true );
		}

		switch ( $page_key ) {
			case 'course_archive':
			case 'reading_stats':
				return true;
			default:
				return false;
		}
	}
}

if ( ! function_exists( 'almaden_bookster_user_can_access_frontend_page' ) ) {
	function almaden_bookster_user_can_access_frontend_page( $page_key, $user_id = null ) {
		$page_key = sanitize_key( (string) $page_key );
		$mode     = almaden_bookster_get_frontend_page_access_mode( $page_key );

		if ( null === $user_id && function_exists( 'almaden_bookster_get_access_preview_role' ) ) {
			$preview_role = almaden_bookster_get_access_preview_role();
			if ( '' !== $preview_role ) {
				return function_exists( 'almaden_bookster_user_can_access_frontend_page_for_role' ) ? almaden_bookster_user_can_access_frontend_page_for_role( $page_key, $preview_role ) : true;
			}
		}

		if ( null === $user_id ) {
			$user_roles = function_exists( 'almaden_bookster_get_current_user_roles' ) ? almaden_bookster_get_current_user_roles() : array();
			if ( ! empty( $user_roles ) ) {
				foreach ( $user_roles as $role_key ) {
					if ( function_exists( 'almaden_bookster_user_can_access_frontend_page_for_role' ) && almaden_bookster_user_can_access_frontend_page_for_role( $page_key, $role_key ) ) {
						return true;
					}
				}
			}
		}

		if ( 'public' === $mode && ( null === $user_id || (int) $user_id <= 0 ) ) {
			return true;
		}

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		$user_roles = isset( $user->roles ) && is_array( $user->roles ) ? array_values( array_filter( array_map( 'sanitize_key', $user->roles ) ) ) : array();
		if ( empty( $user_roles ) ) {
			return 'public' === $mode;
		}

		foreach ( $user_roles as $role_key ) {
			if ( function_exists( 'almaden_bookster_user_can_access_frontend_page_for_role' ) && almaden_bookster_user_can_access_frontend_page_for_role( $page_key, $role_key ) ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'almaden_bookster_get_creator_page_url' ) ) {
	function almaden_bookster_get_creator_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_creator_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_get_shell_home_page_url' ) ) {
	function almaden_bookster_get_shell_home_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_shell_home_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_get_dashboard_page_url' ) ) {
	function almaden_bookster_get_dashboard_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_dashboard_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_get_course_creator_page_url' ) ) {
	function almaden_bookster_get_course_creator_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_course_creator_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_get_course_archive_page_url' ) ) {
	function almaden_bookster_get_course_archive_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_course_archive_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_get_store_page_url' ) ) {
	function almaden_bookster_get_store_page_url( $query_args = array() ) {
		$slug = trim( almaden_bookster_get_store_slug(), '/' );
		$url  = home_url( '/' . $slug . '/' );

		if ( empty( $query_args ) ) {
			return $url;
		}

		return add_query_arg( $query_args, $url );
	}
}

if ( ! function_exists( 'almaden_bookster_user_can_manage_books' ) ) {
	function almaden_bookster_user_can_manage_books() {
		return current_user_can( 'almaden_manage_books' ) || current_user_can( 'manage_options' );
	}
}
