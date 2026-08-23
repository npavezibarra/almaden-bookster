<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_page_visibility_settings_defaults' ) ) {
	function almaden_bookster_get_page_visibility_settings_defaults() {
		return array(
			'allowed_roles_pages' => array(),
		);
	}
}

if ( ! function_exists( 'almaden_bookster_get_page_visibility_settings' ) ) {
	function almaden_bookster_get_page_visibility_settings() {
		$saved_settings = get_option( 'almaden_bookster_page_visibility_settings', array() );

		if ( ! is_array( $saved_settings ) ) {
			$saved_settings = array();
		}

		return wp_parse_args( $saved_settings, almaden_bookster_get_page_visibility_settings_defaults() );
	}
}

if ( ! function_exists( 'almaden_bookster_sanitize_page_visibility_settings' ) ) {
	function almaden_bookster_sanitize_page_visibility_settings( $raw_settings ) {
		$raw_settings = is_array( $raw_settings ) ? $raw_settings : array();
		$allowed_roles_pages = array();
		$role_choices = function_exists( 'almaden_bookster_get_access_preview_roles' ) ? array_keys( almaden_bookster_get_access_preview_roles() ) : array( 'administrator', 'editor', 'author', 'customer', 'subscriber' );
		$raw_allowed_roles_pages = isset( $raw_settings['allowed_roles_pages'] ) && is_array( $raw_settings['allowed_roles_pages'] ) ? $raw_settings['allowed_roles_pages'] : array();

		foreach ( $raw_allowed_roles_pages as $page_key => $raw_roles ) {
			$page_key = (string) $page_key;
			if ( 0 === strpos( $page_key, 'custom_page:' ) ) {
				$page_key = 'custom_page:' . sanitize_key( substr( $page_key, strlen( 'custom_page:' ) ) );
			} else {
				$page_key = sanitize_key( $page_key );
			}

			if ( '' === $page_key || ! is_array( $raw_roles ) ) {
				continue;
			}

			$selected_roles = array();
			foreach ( $raw_roles as $role_key => $is_enabled ) {
				$role_key = sanitize_key( (string) $role_key );
				if ( '' === $role_key || ! in_array( $role_key, $role_choices, true ) || empty( $is_enabled ) ) {
					continue;
				}

				$selected_roles[] = $role_key;
			}

			$allowed_roles_pages[ $page_key ] = array_values( array_unique( $selected_roles ) );
		}

		return array(
			'allowed_roles_pages' => $allowed_roles_pages,
		);
	}
}

if ( ! function_exists( 'almaden_bookster_get_access_preview_roles' ) ) {
	function almaden_bookster_get_access_preview_roles() {
		return array(
			'administrator' => 'Administrador',
			'editor'        => 'Editor',
			'author'        => 'Autor',
			'customer'      => 'Cliente',
			'subscriber'    => 'Suscriptor',
		);
	}
}

if ( ! function_exists( 'almaden_bookster_get_default_shell_page_allowed_roles' ) ) {
	function almaden_bookster_get_default_shell_page_allowed_roles( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );
		$all_roles = array_keys( function_exists( 'almaden_bookster_get_access_preview_roles' ) ? almaden_bookster_get_access_preview_roles() : array() );

		switch ( $page_key ) {
			case 'reading_stats':
				return $all_roles;
			case 'course_creator':
			case 'blog_creator':
				return array( 'administrator', 'editor', 'author' );
			case 'shell_home':
			case 'contractor':
			case 'user_access_manager':
			case 'creator':
			case 'dashboard':
			case 'quiz_builder':
				return array( 'administrator' );
			default:
				if ( 0 === strpos( $page_key, 'custom_page:' ) ) {
					return $all_roles;
				}
				return $all_roles;
		}
	}
}

if ( ! function_exists( 'almaden_bookster_get_page_allowed_roles' ) ) {
	function almaden_bookster_get_page_allowed_roles( $page_key ) {
		$page_key = (string) $page_key;
		if ( 0 === strpos( $page_key, 'custom_page:' ) ) {
			$page_key = 'custom_page:' . sanitize_key( substr( $page_key, strlen( 'custom_page:' ) ) );
		} else {
			$page_key = sanitize_key( $page_key );
		}

		if ( '' === $page_key ) {
			return array();
		}

		$settings = almaden_bookster_get_page_visibility_settings();
		$allowed_roles_pages = isset( $settings['allowed_roles_pages'] ) && is_array( $settings['allowed_roles_pages'] ) ? $settings['allowed_roles_pages'] : array();
		if ( isset( $allowed_roles_pages[ $page_key ] ) && is_array( $allowed_roles_pages[ $page_key ] ) ) {
			$roles = array_values(
				array_unique(
					array_filter(
						array_map(
							static function( $role_key ) {
								return sanitize_key( (string) $role_key );
							},
							$allowed_roles_pages[ $page_key ]
						)
					)
				)
			);
			return $roles;
		}

		return function_exists( 'almaden_bookster_get_default_shell_page_allowed_roles' )
			? almaden_bookster_get_default_shell_page_allowed_roles( $page_key )
			: array();
	}
}
