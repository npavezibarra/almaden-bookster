<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_registered_commerce_providers() {
	static $providers = null;

	if ( isset( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) && is_array( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) ) {
		$providers = $GLOBALS['almaden_bookster_registered_commerce_providers'];
	}

	if ( null === $providers ) {
		$providers = array();
	}

	return $providers;
}

function almaden_bookster_register_commerce_provider( $provider ) {
	if ( ! is_array( $provider ) ) {
		return false;
	}

	$key = isset( $provider['key'] ) ? sanitize_key( (string) $provider['key'] ) : '';
	if ( '' === $key ) {
		return false;
	}

	$providers = almaden_bookster_get_registered_commerce_providers();
	$providers[ $key ] = $provider;
	$GLOBALS['almaden_bookster_registered_commerce_providers'] = $providers;

	return true;
}

function almaden_bookster_get_registered_commerce_provider( $provider_key ) {
	$provider_key = sanitize_key( (string) $provider_key );
	if ( '' === $provider_key ) {
		return null;
	}

	$providers = isset( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) && is_array( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) ? $GLOBALS['almaden_bookster_registered_commerce_providers'] : almaden_bookster_get_registered_commerce_providers();

	return isset( $providers[ $provider_key ] ) && is_array( $providers[ $provider_key ] ) ? $providers[ $provider_key ] : null;
}

function almaden_bookster_get_default_commerce_provider_key() {
	$settings = function_exists( 'almaden_bookster_get_distribution_settings' ) ? almaden_bookster_get_distribution_settings() : array();
	$provider = isset( $settings['default_commerce_provider'] ) ? sanitize_key( (string) $settings['default_commerce_provider'] ) : 'manual';

	return '' !== $provider ? $provider : 'manual';
}

function almaden_bookster_get_effective_commerce_provider_key( $requested_provider_key = '' ) {
	$requested_provider_key = sanitize_key( (string) $requested_provider_key );
	$candidate = '' !== $requested_provider_key ? $requested_provider_key : almaden_bookster_get_default_commerce_provider_key();

	$provider = almaden_bookster_get_registered_commerce_provider( $candidate );
	if ( $provider ) {
		$available = true;
		if ( isset( $provider['available'] ) ) {
			$available = is_callable( $provider['available'] ) ? (bool) call_user_func( $provider['available'] ) : (bool) $provider['available'];
		}
		if ( $available ) {
			return $candidate;
		}
	}

	$providers = isset( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) && is_array( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) ? $GLOBALS['almaden_bookster_registered_commerce_providers'] : almaden_bookster_get_registered_commerce_providers();
	foreach ( $providers as $key => $provider_data ) {
		$available = true;
		if ( isset( $provider_data['available'] ) ) {
			$available = is_callable( $provider_data['available'] ) ? (bool) call_user_func( $provider_data['available'] ) : (bool) $provider_data['available'];
		}

		if ( $available ) {
			return sanitize_key( (string) $key );
		}
	}

	return 'manual';
}

function almaden_bookster_get_active_commerce_provider_key() {
	return almaden_bookster_get_effective_commerce_provider_key();
}

function almaden_bookster_get_active_commerce_provider() {
	$provider_key = almaden_bookster_get_active_commerce_provider_key();
	return almaden_bookster_get_registered_commerce_provider( $provider_key );
}

function almaden_bookster_get_commerce_provider_choices() {
	$providers = isset( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) && is_array( $GLOBALS['almaden_bookster_registered_commerce_providers'] ) ? $GLOBALS['almaden_bookster_registered_commerce_providers'] : almaden_bookster_get_registered_commerce_providers();
	$choices = array();

	foreach ( $providers as $key => $provider ) {
		$choices[] = array(
			'key'       => sanitize_key( (string) $key ),
			'label'     => isset( $provider['label'] ) ? (string) $provider['label'] : ucfirst( str_replace( array( '-', '_' ), ' ', sanitize_key( (string) $key ) ) ),
			'available' => isset( $provider['available'] ) ? ( is_callable( $provider['available'] ) ? (bool) call_user_func( $provider['available'] ) : (bool) $provider['available'] ) : true,
		);
	}

	return $choices;
}

function almaden_bookster_commerce_provider_supports( $feature, $provider_key = '' ) {
	$feature = sanitize_key( (string) $feature );
	$provider = almaden_bookster_get_registered_commerce_provider( '' !== sanitize_key( (string) $provider_key ) ? $provider_key : almaden_bookster_get_active_commerce_provider_key() );

	if ( ! $provider ) {
		return false;
	}

	if ( isset( $provider['supports'] ) ) {
		if ( is_callable( $provider['supports'] ) ) {
			return (bool) call_user_func( $provider['supports'], $feature, $provider );
		}

		if ( is_array( $provider['supports'] ) ) {
			return in_array( $feature, array_map( 'sanitize_key', $provider['supports'] ), true );
		}
	}

	return ! empty( $provider['available'] );
}

function almaden_bookster_commerce_provider_call( $method, $args = array(), $provider_key = '' ) {
	$method = sanitize_key( (string) $method );
	$provider = almaden_bookster_get_registered_commerce_provider( '' !== sanitize_key( (string) $provider_key ) ? $provider_key : almaden_bookster_get_active_commerce_provider_key() );

	if ( ! $provider || empty( $provider[ $method ] ) || ! is_callable( $provider[ $method ] ) ) {
		return null;
	}

	return call_user_func_array( $provider[ $method ], is_array( $args ) ? $args : array( $args ) );
}

function almaden_bookster_register_default_commerce_providers() {
	almaden_bookster_register_commerce_provider(
		array(
			'key'       => 'manual',
			'label'     => 'Manual',
			'available' => true,
			'supports'   => array( 'catalog', 'reader' ),
			'get_purchase_url' => static function( $book_id, $context = array() ) {
				return function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url( is_array( $context ) ? $context : array() ) : home_url( '/' );
			},
			'get_reader_url' => static function( $book_id, $context = array() ) {
				return function_exists( 'get_permalink' ) ? get_permalink( absint( $book_id ) ) : home_url( '/' );
			},
			'has_access' => static function() {
				return false;
			},
			'create_book_product' => static function() {
				return 0;
			},
			'sync_book_relation' => static function() {
				return false;
			},
			'save_relation_from_request' => static function() {
				return false;
			},
		)
	);

	do_action( 'almaden_bookster_register_commerce_providers' );
}
add_action( 'init', 'almaden_bookster_register_default_commerce_providers', 5 );
