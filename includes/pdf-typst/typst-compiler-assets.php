<?php
/**
 * Safely stage document assets inside a Typst compilation directory.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_stage_assets( $assets, $temp_dir ) {
	if ( empty( $assets ) ) {
		return true;
	}
	$assets_dir = trailingslashit( $temp_dir ) . 'assets';
	if ( ! is_dir( $assets_dir ) && ! wp_mkdir_p( $assets_dir ) ) {
		return new WP_Error( 'typst_asset_directory', 'No se pudo preparar la carpeta temporal de imágenes.' );
	}
	foreach ( (array) $assets as $name => $source ) {
		if ( ! preg_match( '/^[a-f0-9]{64}\.[a-z0-9]+$/i', (string) $name ) ) {
			continue;
		}
		$source_size = is_string( $source ) && is_file( $source ) && is_readable( $source ) ? filesize( $source ) : false;
		if ( false === $source_size || $source_size < 1 ) {
			return new WP_Error( 'typst_asset_missing', 'La imagen requerida por el PDF ya no existe o no se puede leer: ' . basename( (string) $source ) );
		}
		$target = $assets_dir . '/' . $name;
		if ( is_file( $target ) && (int) filesize( $target ) === (int) $source_size ) {
			continue;
		}
		$partial = $target . '.part-' . wp_generate_uuid4();
		$copied  = copy( $source, $partial );
		if ( ! $copied || ! is_file( $partial ) || (int) filesize( $partial ) !== (int) $source_size || ! rename( $partial, $target ) ) {
			@unlink( $partial );
			return new WP_Error( 'typst_asset_copy_failed', 'No se pudo preparar la imagen para el PDF: ' . basename( (string) $source ) );
		}
	}
	return true;
}
