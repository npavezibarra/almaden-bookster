<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'almaden_bookster_get_page_visibility_settings_defaults' ) ) {
	function almaden_bookster_get_page_visibility_settings_defaults() {
		return array(
			'admin_only_pages' => array(),
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
		$admin_only_pages = array();
		$raw_admin_only_pages = isset( $raw_settings['admin_only_pages'] ) && is_array( $raw_settings['admin_only_pages'] ) ? $raw_settings['admin_only_pages'] : array();

		foreach ( $raw_admin_only_pages as $page_key => $is_enabled ) {
			$page_key = sanitize_key( (string) $page_key );
			if ( '' === $page_key || empty( $is_enabled ) ) {
				continue;
			}

			$admin_only_pages[ $page_key ] = 1;
		}

		return array(
			'admin_only_pages' => $admin_only_pages,
		);
	}
}

if ( ! function_exists( 'almaden_bookster_is_page_admin_only' ) ) {
	function almaden_bookster_is_page_admin_only( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );
		if ( '' === $page_key ) {
			return false;
		}

		$settings = almaden_bookster_get_page_visibility_settings();
		$admin_only_pages = isset( $settings['admin_only_pages'] ) && is_array( $settings['admin_only_pages'] ) ? $settings['admin_only_pages'] : array();

		return ! empty( $admin_only_pages[ $page_key ] );
	}
}

