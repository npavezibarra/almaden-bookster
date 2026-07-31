<?php
/**
 * Resolve editor font families to local files that Typst can embed.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'ALMADEN_TYPST_TESTING' ) ) {
	exit;
}

function almaden_bookster_typst_font_weight( $value, $fallback = 400 ) {
	$names = array(
		'thin'       => 100,
		'extralight' => 200,
		'light'      => 300,
		'normal'     => 400,
		'regular'    => 400,
		'medium'     => 500,
		'semibold'   => 600,
		'bold'       => 700,
		'extrabold'  => 800,
		'black'      => 900,
	);
	$key = strtolower( trim( (string) $value ) );
	if ( isset( $names[ $key ] ) ) {
		return $names[ $key ];
	}
	$weight = is_numeric( $value ) ? (int) $value : (int) $fallback;
	return max( 100, min( 900, (int) ( round( $weight / 100 ) * 100 ) ) );
}

function almaden_bookster_typst_font_family( $value, $fallback = 'Merriweather' ) {
	$family = trim( (string) $value );
	$family = preg_replace( '/[^\p{L}\p{N} ._-]/u', '', $family );
	return '' !== $family ? substr( $family, 0, 100 ) : $fallback;
}

function almaden_bookster_typst_installed_font_variants( $family ) {
	if ( ! function_exists( 'almaden_bookster_get_installed_fonts_list' ) ) {
		return array();
	}
	foreach ( almaden_bookster_get_installed_fonts_list() as $font ) {
		if ( 0 === strcasecmp( (string) ( $font['family'] ?? '' ), $family ) ) {
			return array_filter( array_map( 'trim', explode( ',', (string) ( $font['variants'] ?? '' ) ) ) );
		}
	}
	return array();
}

function almaden_bookster_typst_variant_tuple( $variant ) {
	$variant = strtolower( trim( (string) $variant ) );
	$italic  = false !== strpos( $variant, 'italic' );
	$weight  = str_replace( 'italic', '', $variant );
	$weight  = '' === $weight || 'regular' === $weight ? 400 : almaden_bookster_typst_font_weight( $weight );
	return array( $italic ? 1 : 0, $weight );
}

function almaden_bookster_typst_nearest_variant( $variants, $italic, $weight ) {
	$available = array();
	foreach ( $variants as $variant ) {
		list( $variant_italic, $variant_weight ) = almaden_bookster_typst_variant_tuple( $variant );
		if ( (int) $italic === $variant_italic ) {
			$available[] = $variant_weight;
		}
	}
	if ( empty( $available ) ) {
		return null;
	}
	usort(
		$available,
		function ( $a, $b ) use ( $weight ) {
			return abs( $a - $weight ) <=> abs( $b - $weight );
		}
	);
	return array( (int) $italic, $available[0] );
}

function almaden_bookster_typst_google_font_tuples( $family, $weight ) {
	$variants = almaden_bookster_typst_installed_font_variants( $family );
	if ( empty( $variants ) ) {
		$variants = array( 'regular', 'italic', '700', '700italic' );
	}
	$tuples = array();
	foreach ( array( $weight, 700 ) as $desired_weight ) {
		foreach ( array( 0, 1 ) as $italic ) {
			$tuple = almaden_bookster_typst_nearest_variant( $variants, $italic, $desired_weight );
			if ( null !== $tuple ) {
				$tuples[ implode( ',', $tuple ) ] = $tuple;
			}
		}
	}
	if ( empty( $tuples ) ) {
		$tuples['0,400'] = array( 0, 400 );
	}
	ksort( $tuples, SORT_NATURAL );
	return array_values( $tuples );
}

function almaden_bookster_typst_local_font_families() {
	return array(
		'Arial',
		'Baskerville',
		'Garamond',
		'Georgia',
		'Helvetica',
		'Times New Roman',
	);
}

/**
 * Download Google Fonts TTF files once and return cache paths for --font-path.
 */
function almaden_bookster_typst_resolve_font( $family, $weight ) {
	$family = almaden_bookster_typst_font_family( $family );
	$bundled = array( 'Libertinus Serif', 'New Computer Modern', 'DejaVu Sans Mono' );
	if ( in_array( $family, $bundled, true ) || in_array( $family, almaden_bookster_typst_local_font_families(), true ) || defined( 'ALMADEN_TYPST_TESTING' ) ) {
		return array( 'family' => $family, 'files' => array() );
	}
	if ( ! function_exists( 'wp_upload_dir' ) || ! function_exists( 'wp_remote_get' ) ) {
		return new WP_Error( 'typst_font_runtime', 'No se pudo inicializar el sistema de fuentes del PDF.' );
	}

	$tuples = almaden_bookster_typst_google_font_tuples( $family, $weight );
	$axes   = array_map(
		function ( $tuple ) {
			return implode( ',', $tuple );
		},
		$tuples
	);
	$query = str_replace( '%20', '+', rawurlencode( $family ) ) . ':ital,wght@' . implode( ';', $axes );
	$url   = 'https://fonts.googleapis.com/css2?family=' . $query . '&display=swap';
	$key   = hash( 'sha256', $family . '|' . implode( ';', $axes ) );

	$uploads = wp_upload_dir();
	if ( ! empty( $uploads['error'] ) ) {
		return new WP_Error( 'typst_font_cache', 'No se pudo acceder al caché de fuentes del PDF.' );
	}
	$cache_dir = trailingslashit( $uploads['basedir'] ) . 'almaden-bookster/typst-fonts/' . $key;
	$manifest  = $cache_dir . '/manifest.json';
	if ( is_file( $manifest ) ) {
		$cached = json_decode( (string) file_get_contents( $manifest ), true );
		$files  = array();
		foreach ( (array) ( $cached['files'] ?? array() ) as $filename ) {
			$path = $cache_dir . '/' . basename( $filename );
			if ( is_file( $path ) ) {
				$files[] = $path;
			}
		}
		if ( ! empty( $files ) ) {
			return array( 'family' => $family, 'files' => $files );
		}
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => 20,
			'user-agent' => 'Mozilla/5.0 AlmadenBookster/Typst',
		)
	);
	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'typst_font_download', 'No se pudo descargar la fuente «' . $family . '» para el PDF.' );
	}
	$css = (string) wp_remote_retrieve_body( $response );
	preg_match_all( '/src:\s*url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)\s*format\([^)]+\)/i', $css, $matches );
	$font_urls = array_values( array_unique( $matches[1] ?? array() ) );
	if ( empty( $font_urls ) || ! wp_mkdir_p( $cache_dir ) ) {
		return new WP_Error( 'typst_font_invalid', 'Google Fonts no devolvió archivos válidos para «' . $family . '».' );
	}

	$files = array();
	foreach ( $font_urls as $index => $font_url ) {
		$font_response = wp_remote_get( $font_url, array( 'timeout' => 30, 'limit_response_size' => 8 * MB_IN_BYTES ) );
		$font_failed   = is_wp_error( $font_response );
		$body          = $font_failed ? '' : (string) wp_remote_retrieve_body( $font_response );
		$signature     = substr( $body, 0, 4 );
		if ( $font_failed || 200 !== (int) wp_remote_retrieve_response_code( $font_response ) ||
			! in_array( $signature, array( "\x00\x01\x00\x00", 'OTTO', 'wOFF', 'wOF2' ), true ) ) {
			return new WP_Error( 'typst_font_invalid', 'La fuente «' . $family . '» devolvió un archivo inválido.' );
		}
		$extension = pathinfo( wp_parse_url( $font_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
		$extension = in_array( strtolower( $extension ), array( 'ttf', 'otf', 'woff', 'woff2' ), true ) ? strtolower( $extension ) : 'ttf';
		$filename  = sprintf( 'font-%02d.%s', $index, $extension );
		if ( false === file_put_contents( $cache_dir . '/' . $filename, $body, LOCK_EX ) ) {
			return new WP_Error( 'typst_font_cache', 'No se pudo guardar la fuente «' . $family . '».' );
		}
		$files[] = $cache_dir . '/' . $filename;
	}
	file_put_contents( $manifest, wp_json_encode( array( 'family' => $family, 'files' => array_map( 'basename', $files ) ) ), LOCK_EX );
	return array( 'family' => $family, 'files' => $files );
}
