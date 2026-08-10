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
