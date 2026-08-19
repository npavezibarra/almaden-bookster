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

if ( ! function_exists( 'almaden_bookster_user_can_access_frontend_page' ) ) {
	function almaden_bookster_user_can_access_frontend_page( $page_key, $user_id = null ) {
		$page_key = sanitize_key( (string) $page_key );
		$mode     = almaden_bookster_get_frontend_page_access_mode( $page_key );
		$is_admin_only = function_exists( 'almaden_bookster_is_page_admin_only' ) && almaden_bookster_is_page_admin_only( $page_key );

		if ( $is_admin_only ) {
			$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
			if ( $user_id <= 0 ) {
				return false;
			}

			$user = get_user_by( 'id', $user_id );
			return $user && function_exists( 'user_can' ) ? user_can( $user, 'manage_options' ) : current_user_can( 'manage_options' );
		}

		if ( 'public' === $mode ) {
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

		$can_manage_options = function_exists( 'user_can' ) ? user_can( $user, 'manage_options' ) : current_user_can( 'manage_options' );

		switch ( $page_key ) {
			case 'creator':
				$can_manage_books = function_exists( 'user_can' ) ? user_can( $user, 'almaden_manage_books' ) : current_user_can( 'almaden_manage_books' );
				return $can_manage_books || $can_manage_options;
			case 'course_creator':
				$can_manage_courses = function_exists( 'user_can' ) ? ( user_can( $user, 'manage_almaden_learni' ) || user_can( $user, 'edit_posts' ) ) : ( current_user_can( 'manage_almaden_learni' ) || current_user_can( 'edit_posts' ) );
				return $can_manage_options || $can_manage_courses;
			case 'course_archive':
				return true;
			case 'blog_creator':
				$can_edit_posts = function_exists( 'user_can' ) ? user_can( $user, 'edit_posts' ) : current_user_can( 'edit_posts' );
				return $can_manage_options || $can_edit_posts;
			case 'reading_stats':
				return true;
			default:
				return $can_manage_options;
		}
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
