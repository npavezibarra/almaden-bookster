<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_publisher_settings_defaults() {
	return array(
		'legal' => array(
			'billing_name'      => '',
			'legal_address'     => '',
			'legal_representative' => '',
			'tax_id'            => '',
			'financial_terms'    => '',
		),
		'contact' => array(
			'contact_name'   => '',
			'contact_email'  => '',
			'contact_phone'  => '',
			'contact_notes'  => '',
		),
		'branding' => array(
			'primary_color'   => '#111827',
			'secondary_color' => '#8f4b2a',
			'support_email'   => '',
			'logo_alt'        => '',
			'brand_notes'     => '',
		),
		'preferences' => array(
			'language'          => 'es',
			'show_public_email' => 1,
			'show_public_phone' => 1,
			'allow_inquiries'   => 1,
			'default_status'    => 'active',
			'future_notes'      => '',
		),
	);
}

function almaden_bookster_publisher_settings_is_assoc( $value ) {
	return is_array( $value ) && array_keys( $value ) !== range( 0, count( $value ) - 1 );
}

function almaden_bookster_publisher_settings_merge_recursive( $defaults, $settings ) {
	foreach ( $defaults as $key => $default_value ) {
		if ( is_array( $default_value ) && isset( $settings[ $key ] ) && is_array( $settings[ $key ] ) ) {
			$defaults[ $key ] = almaden_bookster_publisher_settings_merge_recursive( $default_value, $settings[ $key ] );
			continue;
		}

		if ( isset( $settings[ $key ] ) ) {
			$defaults[ $key ] = $settings[ $key ];
		}
	}

	return $defaults;
}

function almaden_bookster_get_publisher_settings( $publisher_id ) {
	$publisher_id = absint( $publisher_id );
	if ( $publisher_id <= 0 ) {
		return almaden_bookster_get_publisher_settings_defaults();
	}

	$publisher = function_exists( 'almaden_bookster_get_publisher_by_id' ) ? almaden_bookster_get_publisher_by_id( $publisher_id ) : null;
	$raw_json  = is_array( $publisher ) && isset( $publisher['settings_json'] ) ? (string) $publisher['settings_json'] : '';
	$decoded   = array();

	if ( '' !== trim( $raw_json ) ) {
		$decoded = json_decode( $raw_json, true );
		if ( ! is_array( $decoded ) ) {
			$decoded = array();
		}
	}

	return almaden_bookster_publisher_settings_merge_recursive( almaden_bookster_get_publisher_settings_defaults(), $decoded );
}

function almaden_bookster_sanitize_publisher_settings( $raw_settings ) {
	$raw_settings = is_array( $raw_settings ) ? $raw_settings : array();
	$defaults     = almaden_bookster_get_publisher_settings_defaults();

	return array(
		'legal' => array(
			'billing_name'         => isset( $raw_settings['legal']['billing_name'] ) ? sanitize_text_field( wp_unslash( $raw_settings['legal']['billing_name'] ) ) : '',
			'legal_address'        => isset( $raw_settings['legal']['legal_address'] ) ? sanitize_textarea_field( wp_unslash( $raw_settings['legal']['legal_address'] ) ) : '',
			'legal_representative'  => isset( $raw_settings['legal']['legal_representative'] ) ? sanitize_text_field( wp_unslash( $raw_settings['legal']['legal_representative'] ) ) : '',
			'tax_id'               => isset( $raw_settings['legal']['tax_id'] ) ? sanitize_text_field( wp_unslash( $raw_settings['legal']['tax_id'] ) ) : '',
			'financial_terms'       => isset( $raw_settings['legal']['financial_terms'] ) ? wp_kses_post( wp_unslash( $raw_settings['legal']['financial_terms'] ) ) : '',
		),
		'contact' => array(
			'contact_name'  => isset( $raw_settings['contact']['contact_name'] ) ? sanitize_text_field( wp_unslash( $raw_settings['contact']['contact_name'] ) ) : '',
			'contact_email' => isset( $raw_settings['contact']['contact_email'] ) ? sanitize_email( wp_unslash( $raw_settings['contact']['contact_email'] ) ) : '',
			'contact_phone' => isset( $raw_settings['contact']['contact_phone'] ) ? sanitize_text_field( wp_unslash( $raw_settings['contact']['contact_phone'] ) ) : '',
			'contact_notes'  => isset( $raw_settings['contact']['contact_notes'] ) ? sanitize_textarea_field( wp_unslash( $raw_settings['contact']['contact_notes'] ) ) : '',
		),
		'branding' => array(
			'primary_color' => ( isset( $raw_settings['branding']['primary_color'] ) && sanitize_hex_color( wp_unslash( $raw_settings['branding']['primary_color'] ) ) ) ? sanitize_hex_color( wp_unslash( $raw_settings['branding']['primary_color'] ) ) : $defaults['branding']['primary_color'],
			'secondary_color' => ( isset( $raw_settings['branding']['secondary_color'] ) && sanitize_hex_color( wp_unslash( $raw_settings['branding']['secondary_color'] ) ) ) ? sanitize_hex_color( wp_unslash( $raw_settings['branding']['secondary_color'] ) ) : $defaults['branding']['secondary_color'],
			'support_email' => isset( $raw_settings['branding']['support_email'] ) ? sanitize_email( wp_unslash( $raw_settings['branding']['support_email'] ) ) : '',
			'logo_alt'      => isset( $raw_settings['branding']['logo_alt'] ) ? sanitize_text_field( wp_unslash( $raw_settings['branding']['logo_alt'] ) ) : '',
			'brand_notes'   => isset( $raw_settings['branding']['brand_notes'] ) ? sanitize_textarea_field( wp_unslash( $raw_settings['branding']['brand_notes'] ) ) : '',
		),
		'preferences' => array(
			'language'          => ( isset( $raw_settings['preferences']['language'] ) && '' !== sanitize_key( wp_unslash( $raw_settings['preferences']['language'] ) ) ) ? sanitize_key( wp_unslash( $raw_settings['preferences']['language'] ) ) : $defaults['preferences']['language'],
			'show_public_email' => ! empty( $raw_settings['preferences']['show_public_email'] ) ? 1 : 0,
			'show_public_phone' => ! empty( $raw_settings['preferences']['show_public_phone'] ) ? 1 : 0,
			'allow_inquiries'   => ! empty( $raw_settings['preferences']['allow_inquiries'] ) ? 1 : 0,
			'default_status'    => ( isset( $raw_settings['preferences']['default_status'] ) && '' !== sanitize_key( wp_unslash( $raw_settings['preferences']['default_status'] ) ) ) ? sanitize_key( wp_unslash( $raw_settings['preferences']['default_status'] ) ) : $defaults['preferences']['default_status'],
			'future_notes'      => isset( $raw_settings['preferences']['future_notes'] ) ? sanitize_textarea_field( wp_unslash( $raw_settings['preferences']['future_notes'] ) ) : '',
		),
	);
}

function almaden_bookster_save_publisher_settings( $publisher_id, $settings ) {
	global $wpdb;

	$publisher_id = absint( $publisher_id );
	if ( $publisher_id <= 0 ) {
		return new WP_Error( 'almaden_invalid_publisher', __( 'Editorial inválida.', 'almaden-bookster' ) );
	}

	$table_name = almaden_bookster_get_publishers_table_name();
	$existing   = function_exists( 'almaden_bookster_get_publisher_by_id' ) ? almaden_bookster_get_publisher_by_id( $publisher_id ) : null;
	if ( ! $existing ) {
		return new WP_Error( 'almaden_publisher_not_found', __( 'No se encontró la editorial.', 'almaden-bookster' ) );
	}

	$clean_settings = almaden_bookster_sanitize_publisher_settings( $settings );
	$result = $wpdb->update(
		$table_name,
		array(
			'settings_json' => wp_json_encode( $clean_settings ),
			'updated_at'    => current_time( 'mysql' ),
		),
		array( 'id' => $publisher_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	if ( false === $result ) {
		return new WP_Error( 'almaden_publisher_settings_update_failed', __( 'No se pudo guardar la configuración de la editorial.', 'almaden-bookster' ) );
	}

	return true;
}

function almaden_bookster_get_publisher_settings_url( $publisher_slug = '' ) {
	$base_url = almaden_bookster_get_publisher_page_url( $publisher_slug );
	return trailingslashit( $base_url . 'ajustes' );
}

function almaden_bookster_render_publisher_settings_page_content( $content ) {
	if ( ! is_page( almaden_bookster_get_publisher_page_slug() ) || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$publisher_slug = get_query_var( 'almaden_publisher_slug', '' );
	$publisher_view = get_query_var( 'almaden_publisher_view', '' );

	if ( 'settings' !== $publisher_view || '' === trim( (string) $publisher_slug ) ) {
		return $content;
	}

	$publisher = almaden_bookster_get_publisher_by_slug( $publisher_slug );
	if ( empty( $publisher ) ) {
		$publisher_settings = almaden_bookster_get_publisher_settings_defaults();
		$can_manage_publisher = false;
		ob_start();
		require plugin_dir_path( __FILE__ ) . '../../templates/publishers/publisher-settings-app.php';
		return ob_get_clean();
	}

	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	show_admin_bar( false );

	if ( ! almaden_bookster_user_can_manage_publisher( $publisher['id'] ) ) {
		status_header( 403 );
		$publisher_settings = almaden_bookster_get_publisher_settings( $publisher['id'] );
		$can_manage_publisher = false;
		ob_start();
		require plugin_dir_path( __FILE__ ) . '../../templates/publishers/publisher-settings-app.php';
		return ob_get_clean();
	}

	$publisher_settings = almaden_bookster_get_publisher_settings( $publisher['id'] );
	$can_manage_publisher = true;
	ob_start();
	require plugin_dir_path( __FILE__ ) . '../../templates/publishers/publisher-settings-app.php';
	return ob_get_clean();
}

function almaden_bookster_handle_publisher_settings_save() {
	if ( ! is_user_logged_in() ) {
		auth_redirect();
	}

	if ( ! isset( $_POST['almaden_publisher_settings_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_publisher_settings_nonce'], 'almaden_save_publisher_settings' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$publisher_id = isset( $_POST['publisher_id'] ) ? absint( $_POST['publisher_id'] ) : 0;
	$publisher    = $publisher_id > 0 ? almaden_bookster_get_publisher_by_id( $publisher_id ) : null;
	if ( empty( $publisher ) ) {
		wp_die( 'Editorial no encontrada.' );
	}

	if ( ! almaden_bookster_user_can_manage_publisher( $publisher_id ) ) {
		wp_die( 'Permisos insuficientes.' );
	}

	$slug = isset( $_POST['publisher_slug'] ) ? sanitize_title( wp_unslash( $_POST['publisher_slug'] ) ) : '';
	if ( '' === $slug ) {
		$slug = $publisher['slug'];
	}

	$slug_owner = almaden_bookster_get_publisher_by_slug( $slug );
	if ( $slug_owner && (int) $slug_owner['id'] !== $publisher_id ) {
		wp_die( 'Ese slug ya está en uso por otra editorial.' );
	}

	$publisher_data = array(
		'slug'        => $slug,
		'name'        => isset( $_POST['publisher_name'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_name'] ) ) : $publisher['name'],
		'legal_name'  => isset( $_POST['publisher_legal_name'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_legal_name'] ) ) : $publisher['legal_name'],
		'rut'         => isset( $_POST['publisher_rut'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_rut'] ) ) : $publisher['rut'],
		'description' => isset( $_POST['publisher_description'] ) ? wp_kses_post( wp_unslash( $_POST['publisher_description'] ) ) : $publisher['description'],
		'email'       => isset( $_POST['publisher_email'] ) ? sanitize_email( wp_unslash( $_POST['publisher_email'] ) ) : $publisher['email'],
		'phone'       => isset( $_POST['publisher_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_phone'] ) ) : $publisher['phone'],
		'website'     => isset( $_POST['publisher_website'] ) ? esc_url_raw( wp_unslash( $_POST['publisher_website'] ) ) : $publisher['website'],
		'logo'        => isset( $_POST['publisher_logo'] ) ? absint( $_POST['publisher_logo'] ) : absint( $publisher['logo'] ),
		'banner'      => isset( $_POST['publisher_banner'] ) ? absint( $_POST['publisher_banner'] ) : absint( $publisher['banner'] ),
		'keywords'    => isset( $_POST['publisher_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['publisher_keywords'] ) ) : $publisher['keywords'],
		'status'      => isset( $_POST['publisher_status'] ) ? sanitize_key( wp_unslash( $_POST['publisher_status'] ) ) : $publisher['status'],
	);

	$updated_id = almaden_bookster_save_publisher( $publisher_data, $publisher_id );
	if ( is_wp_error( $updated_id ) ) {
		wp_die( esc_html( $updated_id->get_error_message() ) );
	}

	$settings = array(
		'legal' => array(
			'billing_name'        => isset( $_POST['billing_name'] ) ? wp_unslash( $_POST['billing_name'] ) : '',
			'legal_address'       => isset( $_POST['legal_address'] ) ? wp_unslash( $_POST['legal_address'] ) : '',
			'legal_representative' => isset( $_POST['legal_representative'] ) ? wp_unslash( $_POST['legal_representative'] ) : '',
			'tax_id'              => isset( $_POST['tax_id'] ) ? wp_unslash( $_POST['tax_id'] ) : '',
			'financial_terms'     => isset( $_POST['financial_terms'] ) ? wp_unslash( $_POST['financial_terms'] ) : '',
		),
		'contact' => array(
			'contact_name'  => isset( $_POST['contact_name'] ) ? wp_unslash( $_POST['contact_name'] ) : '',
			'contact_email' => isset( $_POST['contact_email'] ) ? wp_unslash( $_POST['contact_email'] ) : '',
			'contact_phone' => isset( $_POST['contact_phone'] ) ? wp_unslash( $_POST['contact_phone'] ) : '',
			'contact_notes' => isset( $_POST['contact_notes'] ) ? wp_unslash( $_POST['contact_notes'] ) : '',
		),
		'branding' => array(
			'primary_color'   => isset( $_POST['primary_color'] ) ? wp_unslash( $_POST['primary_color'] ) : '#111827',
			'secondary_color' => isset( $_POST['secondary_color'] ) ? wp_unslash( $_POST['secondary_color'] ) : '#8f4b2a',
			'support_email'   => isset( $_POST['support_email'] ) ? wp_unslash( $_POST['support_email'] ) : '',
			'logo_alt'        => isset( $_POST['logo_alt'] ) ? wp_unslash( $_POST['logo_alt'] ) : '',
			'brand_notes'     => isset( $_POST['brand_notes'] ) ? wp_unslash( $_POST['brand_notes'] ) : '',
		),
		'preferences' => array(
			'language'          => isset( $_POST['language'] ) ? wp_unslash( $_POST['language'] ) : 'es',
			'show_public_email' => isset( $_POST['show_public_email'] ) ? 1 : 0,
			'show_public_phone' => isset( $_POST['show_public_phone'] ) ? 1 : 0,
			'allow_inquiries'   => isset( $_POST['allow_inquiries'] ) ? 1 : 0,
			'default_status'    => isset( $_POST['default_status'] ) ? wp_unslash( $_POST['default_status'] ) : 'active',
			'future_notes'      => isset( $_POST['future_notes'] ) ? wp_unslash( $_POST['future_notes'] ) : '',
		),
	);

	$settings_result = almaden_bookster_save_publisher_settings( $publisher_id, $settings );
	if ( is_wp_error( $settings_result ) ) {
		wp_die( esc_html( $settings_result->get_error_message() ) );
	}

	$redirect_url = add_query_arg(
		array(
			'settings-updated' => '1',
		),
		almaden_bookster_get_publisher_settings_url( $slug )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_save_publisher_settings', 'almaden_bookster_handle_publisher_settings_save' );

add_action( 'init', function () {
	$base_slug = trim( almaden_bookster_get_publisher_page_slug(), '/' );
	if ( '' === $base_slug ) {
		$base_slug = 'editorial';
	}

	add_rewrite_rule(
		'^' . preg_quote( $base_slug, '/' ) . '/([^/]+)/ajustes/?$',
		'index.php?pagename=' . $base_slug . '&almaden_publisher_slug=$matches[1]&almaden_publisher_view=settings',
		'top'
	);
}, 19 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'almaden_publisher_view';
	return $vars;
} );

function almaden_bookster_load_publisher_settings_app_page() {
	if ( ! is_page( almaden_bookster_get_publisher_page_slug() ) || ! is_main_query() ) {
		return;
	}

	if ( 'settings' !== get_query_var( 'almaden_publisher_view', '' ) ) {
		return;
	}

	show_admin_bar( false );

	$template_path = dirname( __FILE__ ) . '/../../templates/publishers/publisher-settings-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla de ajustes de editorial no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_publisher_settings_app_page', 5 );

add_filter( 'the_content', 'almaden_bookster_render_publisher_settings_page_content', 18 );
