<?php
/**
 * AlmadenBookster — Google Fonts Database & AJAX Handlers
 *
 * Manages the installed fonts table and provides AJAX endpoints
 * for searching, installing, and uninstalling Google Fonts.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create the installed fonts table if it doesn't exist.
 */
function almaden_bookster_create_fonts_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_installed_fonts';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		family varchar(100) NOT NULL,
		category varchar(50) DEFAULT 'serif' NOT NULL,
		variants text DEFAULT '' NOT NULL,
		subsets text DEFAULT '' NOT NULL,
		installed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY family (family)
	) $charset_collate;";

	almaden_bookster_maybe_install_table( $table_name, $sql, 'almaden_bookster_fonts_db_version', '1.0.0' );
}

/**
 * Built-in Google Fonts shipped with the plugin.
 *
 * These do not need to be installed by the admin. They are always available
 * in selectors and always loaded when the editor/export requests a font CDN.
 *
 * @return array<int, array<string, string>>
 */
function almaden_bookster_get_bundled_fonts_list() {
	return array(
		array(
			'family'   => 'Inter',
			'category' => 'sans-serif',
			'variants' => '100,200,300,regular,500,600,700,800,900,100italic,200italic,300italic,italic,500italic,600italic,700italic,800italic,900italic',
			'subsets'  => 'cyrillic,cyrillic-ext,greek,greek-ext,latin,latin-ext,vietnamese',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Merriweather',
			'category' => 'serif',
			'variants' => '300,regular,700,900,300italic,italic,700italic,900italic',
			'subsets'  => 'cyrillic,cyrillic-ext,latin,latin-ext,vietnamese',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Playfair Display',
			'category' => 'serif',
			'variants' => 'regular,500,600,700,800,900,italic,500italic,600italic,700italic,800italic,900italic',
			'subsets'  => 'cyrillic,cyrillic-ext,latin,latin-ext,vietnamese',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Lora',
			'category' => 'serif',
			'variants' => 'regular,500,600,700,italic,500italic,600italic,700italic',
			'subsets'  => 'cyrillic,cyrillic-ext,latin,latin-ext,vietnamese',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Cinzel',
			'category' => 'serif',
			'variants' => 'regular,500,600,700,800,900',
			'subsets'  => 'latin,latin-ext',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Cormorant Garamond',
			'category' => 'serif',
			'variants' => '300,regular,500,600,700,300italic,italic,500italic,600italic,700italic',
			'subsets'  => 'cyrillic,cyrillic-ext,latin,latin-ext,vietnamese',
			'source'   => 'bundled',
		),
		array(
			'family'   => 'Outfit',
			'category' => 'sans-serif',
			'variants' => '100,200,300,regular,500,600,700,800,900',
			'subsets'  => 'latin,latin-ext',
			'source'   => 'bundled',
		),
	);
}

/**
 * Build a stable map of available fonts by family.
 *
 * @param array<int, array<string, string>> $fonts Fonts to merge.
 * @return array<int, array<string, string>>
 */
function almaden_bookster_normalize_fonts_by_family( $fonts ) {
	$normalized = array();

	foreach ( (array) $fonts as $font ) {
		$family = isset( $font['family'] ) ? trim( (string) $font['family'] ) : '';
		if ( '' === $family ) {
			continue;
		}

		$key = strtolower( $family );
		if ( ! isset( $normalized[ $key ] ) ) {
			$normalized[ $key ] = array(
				'family'   => $family,
				'category' => isset( $font['category'] ) ? sanitize_text_field( (string) $font['category'] ) : 'serif',
				'variants' => isset( $font['variants'] ) ? sanitize_text_field( (string) $font['variants'] ) : '',
				'subsets'  => isset( $font['subsets'] ) ? sanitize_text_field( (string) $font['subsets'] ) : '',
				'source'   => isset( $font['source'] ) ? sanitize_text_field( (string) $font['source'] ) : 'installed',
			);
		}
	}

	return array_values( $normalized );
}

/**
 * Build a Google Fonts v2 URL from a list of font records.
 *
 * @param array<int, array<string, string>> $fonts Fonts to include.
 * @return string
 */
function almaden_bookster_build_google_fonts_url( $fonts ) {
	$font_families_for_cdn = array();

	foreach ( almaden_bookster_normalize_fonts_by_family( $fonts ) as $font ) {
		$family = isset( $font['family'] ) ? trim( (string) $font['family'] ) : '';
		if ( '' === $family ) {
			continue;
		}

		if ( isset( $font['source'] ) && 'bundled' === strtolower( (string) $font['source'] ) ) {
			continue;
		}

		$variants_str = isset( $font['variants'] ) ? (string) $font['variants'] : '';
		$family_slug  = str_replace( ' ', '+', $family );
		$tuples       = array();

		if ( '' === trim( $variants_str ) ) {
			$tuples[] = '0,400';
			$tuples[] = '0,700';
			$tuples[] = '1,400';
		} else {
			$variants_arr = array_filter( array_map( 'trim', explode( ',', $variants_str ) ) );
			foreach ( $variants_arr as $variant ) {
				$italic = 0;
				$weight = 400;

				if ( false !== strpos( $variant, 'italic' ) ) {
					$italic = 1;
					$variant = str_replace( 'italic', '', $variant );
				}

				$variant = '' === $variant || 'regular' === $variant ? '400' : $variant;
				if ( is_numeric( $variant ) ) {
					$weight = intval( $variant );
				}

				if ( $weight >= 100 && $weight <= 900 ) {
					$tuples[] = $italic . ',' . $weight;
				}
			}
		}

		$tuples = array_values( array_unique( $tuples ) );
		sort( $tuples, SORT_NATURAL );
		$font_families_for_cdn[] = $family_slug . ':ital,wght@' . implode( ';', $tuples );
	}

	$font_families_for_cdn = array_values( array_unique( $font_families_for_cdn ) );
	if ( empty( $font_families_for_cdn ) ) {
		return '';
	}

	return 'https://fonts.googleapis.com/css2?' . implode(
		'&',
		array_map(
			static function ( $font_family ) {
				return 'family=' . $font_family;
			},
			$font_families_for_cdn
		)
	) . '&display=swap';
}

/**
 * Get the bundled font stylesheet URL.
 *
 * @return string
 */
function almaden_bookster_get_bundled_fonts_stylesheet_url() {
	return plugins_url(
		'assets/fonts/bundled/bundled-fonts.css',
		dirname( dirname( dirname( __FILE__ ) ) ) . '/almaden-bookster.php'
	);
}

/**
 * Get all fonts available to the plugin, including bundled defaults.
 *
 * @return array<int, array<string, string>>
 */
function almaden_bookster_get_available_fonts_list() {
	$fonts = array();

	foreach ( almaden_bookster_get_bundled_fonts_list() as $font ) {
		$family = strtolower( trim( (string) ( $font['family'] ?? '' ) ) );
		if ( '' === $family ) {
			continue;
		}
		$fonts[ $family ] = $font;
	}

	foreach ( almaden_bookster_get_installed_fonts_list() as $font ) {
		$family = strtolower( trim( (string) ( $font['family'] ?? '' ) ) );
		if ( '' === $family ) {
			continue;
		}

		if ( isset( $fonts[ $family ] ) ) {
			$fonts[ $family ] = array_merge(
				$fonts[ $family ],
				array(
					'category' => isset( $font['category'] ) && '' !== trim( (string) $font['category'] ) ? sanitize_text_field( (string) $font['category'] ) : ( $fonts[ $family ]['category'] ?? 'serif' ),
					'variants' => isset( $font['variants'] ) && '' !== trim( (string) $font['variants'] ) ? sanitize_text_field( (string) $font['variants'] ) : ( $fonts[ $family ]['variants'] ?? '' ),
					'subsets'  => isset( $font['subsets'] ) && '' !== trim( (string) $font['subsets'] ) ? sanitize_text_field( (string) $font['subsets'] ) : ( $fonts[ $family ]['subsets'] ?? '' ),
					'source'   => 'installed',
				)
			);
		} else {
			$fonts[ $family ] = array_merge(
				$font,
				array(
					'source' => 'installed',
				)
			);
		}
	}

	return array_values( $fonts );
}

/**
 * Check whether a font family is bundled with the plugin.
 *
 * @param string $family Font family name.
 * @return bool
 */
function almaden_bookster_is_bundled_font( $family ) {
	$family = strtolower( trim( (string) $family ) );
	if ( '' === $family ) {
		return false;
	}

	foreach ( almaden_bookster_get_bundled_fonts_list() as $font ) {
		if ( strtolower( (string) ( $font['family'] ?? '' ) ) === $family ) {
			return true;
		}
	}

	return false;
}

/**
 * AJAX: Save or update the Google Fonts API key.
 */
function almaden_bookster_save_api_key() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}
	check_ajax_referer( 'almaden_fonts_nonce', 'nonce' );

	$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( $_POST['api_key'] ) : '';
	update_option( 'almaden_google_fonts_api_key', $api_key );

	wp_send_json_success( array( 'message' => 'API Key guardada correctamente.' ) );
}
add_action( 'wp_ajax_almaden_save_fonts_api_key', 'almaden_bookster_save_api_key' );

/**
 * AJAX: Search Google Fonts via the public API.
 */
function almaden_bookster_search_google_fonts() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}
	check_ajax_referer( 'almaden_fonts_nonce', 'nonce' );

	$api_key = get_option( 'almaden_google_fonts_api_key', '' );
	if ( empty( $api_key ) ) {
		wp_send_json_error( 'No se ha configurado la API Key de Google Fonts.' );
	}

	$sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'popularity';
	$url  = add_query_arg(
		array(
			'key'  => $api_key,
			'sort' => $sort,
		),
		'https://www.googleapis.com/webfonts/v1/webfonts'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
	if ( is_wp_error( $response ) ) {
		wp_send_json_error( 'Error al conectar con la API de Google Fonts.' );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['items'] ) ) {
		wp_send_json_error( 'No se encontraron fuentes o la API Key es inválida.' );
	}

	wp_send_json_success( $body['items'] );
}
add_action( 'wp_ajax_almaden_search_google_fonts', 'almaden_bookster_search_google_fonts' );

/**
 * AJAX: Install a font (save to DB).
 */
function almaden_bookster_install_font() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}
	check_ajax_referer( 'almaden_fonts_nonce', 'nonce' );

	global $wpdb;
	$table = $wpdb->prefix . 'almaden_installed_fonts';

	$family   = isset( $_POST['family'] ) ? sanitize_text_field( $_POST['family'] ) : '';
	$category = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : 'serif';
	$variants = isset( $_POST['variants'] ) ? sanitize_text_field( $_POST['variants'] ) : '';
	$subsets  = isset( $_POST['subsets'] ) ? sanitize_text_field( $_POST['subsets'] ) : '';

	if ( empty( $family ) ) {
		wp_send_json_error( 'El nombre de la fuente es obligatorio.' );
	}

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE family = %s", $family ) );
	if ( $exists ) {
		wp_send_json_error( 'Esta fuente ya está instalada.' );
	}

	$result = $wpdb->insert( $table, array(
		'family'   => $family,
		'category' => $category,
		'variants' => $variants,
		'subsets'  => $subsets,
	) );

	if ( false !== $result ) {
		wp_send_json_success( array( 'message' => "Fuente «{$family}» instalada correctamente." ) );
	} else {
		wp_send_json_error( 'Error al instalar la fuente.' );
	}
}
add_action( 'wp_ajax_almaden_install_font', 'almaden_bookster_install_font' );

/**
 * AJAX: Uninstall a font (remove from DB).
 */
function almaden_bookster_uninstall_font() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}
	check_ajax_referer( 'almaden_fonts_nonce', 'nonce' );

	global $wpdb;
	$table  = $wpdb->prefix . 'almaden_installed_fonts';
	$family = isset( $_POST['family'] ) ? sanitize_text_field( $_POST['family'] ) : '';

	if ( empty( $family ) ) {
		wp_send_json_error( 'El nombre de la fuente es obligatorio.' );
	}

	$result = $wpdb->delete( $table, array( 'family' => $family ) );

	if ( false !== $result ) {
		wp_send_json_success( array( 'message' => "Fuente «{$family}» desinstalada." ) );
	} else {
		wp_send_json_error( 'Error al desinstalar la fuente.' );
	}
}
add_action( 'wp_ajax_almaden_uninstall_font', 'almaden_bookster_uninstall_font' );

/**
 * AJAX: Return all installed fonts.
 */
function almaden_bookster_get_installed_fonts() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'Permisos insuficientes.' );
	}
	check_ajax_referer( 'almaden_fonts_nonce', 'nonce' );

	global $wpdb;
	$table = $wpdb->prefix . 'almaden_installed_fonts';
	$fonts = $wpdb->get_results( "SELECT * FROM $table ORDER BY family ASC", ARRAY_A );

	wp_send_json_success( $fonts ? $fonts : array() );
}
add_action( 'wp_ajax_almaden_get_installed_fonts', 'almaden_bookster_get_installed_fonts' );

/**
 * Helper: Get installed fonts as an array (for use in templates).
 */
function almaden_bookster_get_installed_fonts_list() {
	global $wpdb;
	$table = $wpdb->prefix . 'almaden_installed_fonts';

	$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table;
	if ( ! $table_exists ) {
		return array();
	}

	$fonts = $wpdb->get_results( "SELECT family, category, variants FROM $table ORDER BY family ASC", ARRAY_A );
	return $fonts ? $fonts : array();
}
