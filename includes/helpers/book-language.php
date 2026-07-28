<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_normalize_language_code( $language, $fallback = 'es' ) {
	$fallback = strtolower( trim( (string) $fallback ) );
	if ( '' === $fallback ) {
		$fallback = 'es';
	}

	$language = strtolower( trim( (string) $language ) );
	if ( '' === $language ) {
		return $fallback;
	}

	if ( preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/i', $language ) ) {
		return $language;
	}

	return $fallback;
}

function almaden_bookster_get_book_language_from_settings( $settings, $fallback = 'es' ) {
	$settings = is_array( $settings ) ? $settings : array();

	if ( isset( $settings['book_language'] ) ) {
		return almaden_bookster_normalize_language_code( $settings['book_language'], $fallback );
	}

	if ( isset( $settings['content_language'] ) ) {
		return almaden_bookster_normalize_language_code( $settings['content_language'], $fallback );
	}

	return almaden_bookster_normalize_language_code( '', $fallback );
}

function almaden_bookster_get_book_language( $book_id, $settings = array(), $fallback = 'es' ) {
	$normalized = almaden_bookster_get_book_language_from_settings( $settings, $fallback );
	if ( '' !== $normalized ) {
		return $normalized;
	}

	$book_id = absint( $book_id );
	if ( $book_id && function_exists( 'almaden_get_book_pdf_settings' ) ) {
		$loaded_settings = almaden_get_book_pdf_settings( $book_id );
		return almaden_bookster_get_book_language_from_settings( $loaded_settings, $fallback );
	}

	return almaden_bookster_normalize_language_code( '', $fallback );
}

function almaden_bookster_get_book_language_attr( $book_id, $settings = array(), $fallback = 'es' ) {
	return esc_attr( almaden_bookster_get_book_language( $book_id, $settings, $fallback ) );
}
