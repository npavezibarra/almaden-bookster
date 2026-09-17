<?php
/**
 * AlmadenBookster — User-level Google Fonts Helper
 *
 * Provides functions to read and save per-user installed fonts in user_meta.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get user-installed fonts list from user_meta.
 *
 * @param int $user_id Optional user ID. Defaults to current user.
 * @return array<int, array<string, string>>
 */
function almaden_bookster_get_user_installed_fonts_list( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return array();
	}

	$user_fonts = get_user_meta( $user_id, '_almaden_user_installed_fonts', true );
	return is_array( $user_fonts ) ? array_values( $user_fonts ) : array();
}

/**
 * Add a font to user's personal installed fonts in user_meta.
 *
 * @param array<string, string> $font_data Font properties (family, category, variants, subsets).
 * @param int                   $user_id   Optional user ID. Defaults to current user.
 * @return bool|string True on success, error message string on failure.
 */
function almaden_bookster_add_user_installed_font( $font_data, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return 'Usuario no autenticado.';
	}

	$family = isset( $font_data['family'] ) ? sanitize_text_field( trim( (string) $font_data['family'] ) ) : '';
	if ( '' === $family ) {
		return 'El nombre de la fuente es obligatorio.';
	}

	$category = isset( $font_data['category'] ) ? sanitize_text_field( (string) $font_data['category'] ) : 'serif';
	$variants = isset( $font_data['variants'] ) ? sanitize_text_field( (string) $font_data['variants'] ) : '';
	$subsets  = isset( $font_data['subsets'] ) ? sanitize_text_field( (string) $font_data['subsets'] ) : '';

	$user_fonts = almaden_bookster_get_user_installed_fonts_list( $user_id );
	foreach ( $user_fonts as $uf ) {
		if ( strtolower( (string) ( $uf['family'] ?? '' ) ) === strtolower( $family ) ) {
			return 'Esta fuente ya está instalada en tu catálogo personal.';
		}
	}

	$user_fonts[] = array(
		'family'   => $family,
		'category' => $category,
		'variants' => $variants,
		'subsets'  => $subsets,
		'source'   => 'user',
	);

	update_user_meta( $user_id, '_almaden_user_installed_fonts', $user_fonts );
	return true;
}

/**
 * Remove a font from user's personal installed fonts in user_meta.
 *
 * @param string $family  Font family to remove.
 * @param int    $user_id Optional user ID. Defaults to current user.
 * @return bool True if updated.
 */
function almaden_bookster_remove_user_installed_font( $family, $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id ) {
		return false;
	}

	$family_lower = strtolower( trim( (string) $family ) );
	$user_fonts   = almaden_bookster_get_user_installed_fonts_list( $user_id );
	$filtered     = array();

	foreach ( $user_fonts as $uf ) {
		if ( strtolower( (string) ( $uf['family'] ?? '' ) ) !== $family_lower ) {
			$filtered[] = $uf;
		}
	}

	update_user_meta( $user_id, '_almaden_user_installed_fonts', array_values( $filtered ) );
	return true;
}
