<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decodifica JSON que puede provenir de post_meta (ya desescapado)
 * o de una petición WordPress (con slashes añadidos).
 */
function almaden_bookster_decode_json_array( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return null;
	}

	$decoded = json_decode( $value, true );
	if ( is_array( $decoded ) ) {
		return $decoded;
	}

	$unslashed = wp_unslash( $value );
	if ( $unslashed !== $value ) {
		$decoded = json_decode( $unslashed, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}

	return null;
}
