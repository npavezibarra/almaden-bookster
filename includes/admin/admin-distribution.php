<?php
/**
 * AlmadenBookster - Distribution and access settings.
 *
 * This module introduces the global distribution contract used by books,
 * products, and future commerce providers. The per-book panel will consume the
 * same option keys and defaults.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_distribution_settings_defaults() {
	return array(
		'default_distribution_mode'   => 'store_integrated',
		'default_commerce_provider'   => 'woocommerce',
		'default_reader_entry_mode'   => 'product_cta',
		'bookshelf_page_policy'       => 'auto_create',
		'auto_create_store_product'   => 1,
		'auto_create_bookshelf_page'  => 1,
		'menu_injection_enabled'      => 1,
		'menu_location'               => 'default',
		'return_url_policy'           => 'product_or_fallback',
		'valid_order_statuses'        => array( 'processing', 'completed' ),
	);
}

function almaden_bookster_get_woocommerce_status() {
	$plugin_file = 'woocommerce/woocommerce.php';
	$installed   = defined( 'WP_PLUGIN_DIR' ) && is_file( trailingslashit( WP_PLUGIN_DIR ) . $plugin_file );
	$active      = $installed && function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin_file ) : false;

	return array(
		'plugin_file'  => $plugin_file,
		'installed'    => $installed,
		'active'       => $active,
		'install_url'  => wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ),
			'install-plugin_woocommerce'
		),
		'activate_url' => wp_nonce_url(
			self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ),
			'activate-plugin_' . $plugin_file
		),
	);
}

function almaden_bookster_get_distribution_settings() {
	$defaults = almaden_bookster_get_distribution_settings_defaults();
	$saved    = get_option( 'almaden_bookster_distribution_settings', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$settings = wp_parse_args( $saved, $defaults );
	$settings['auto_create_store_product']  = ! empty( $settings['auto_create_store_product'] ) ? 1 : 0;
	$settings['auto_create_bookshelf_page'] = ! empty( $settings['auto_create_bookshelf_page'] ) ? 1 : 0;
	$settings['menu_injection_enabled']     = ! empty( $settings['menu_injection_enabled'] ) ? 1 : 0;
	$settings['valid_order_statuses']       = array_values( array_filter(
		array_map(
			'sanitize_key',
			is_array( $settings['valid_order_statuses'] ) ? $settings['valid_order_statuses'] : array()
		)
	) );

	if ( empty( $settings['valid_order_statuses'] ) ) {
		$settings['valid_order_statuses'] = $defaults['valid_order_statuses'];
	}

	if ( function_exists( 'almaden_bookster_get_effective_commerce_provider_key' ) ) {
		$settings['default_commerce_provider'] = almaden_bookster_get_effective_commerce_provider_key( $settings['default_commerce_provider'] ?? '' );
	} elseif ( ! almaden_bookster_get_woocommerce_status()['active'] ) {
		$settings['default_commerce_provider'] = 'manual';
	}

	return $settings;
}

function almaden_bookster_get_bookshelf_page_policy() {
	$settings = almaden_bookster_get_distribution_settings();
	return isset( $settings['bookshelf_page_policy'] ) ? sanitize_key( (string) $settings['bookshelf_page_policy'] ) : 'auto_create';
}

function almaden_bookster_should_auto_create_bookshelf_page() {
	return 'auto_create' === almaden_bookster_get_bookshelf_page_policy() && ! empty( almaden_bookster_get_distribution_settings()['auto_create_bookshelf_page'] );
}

function almaden_bookster_sanitize_distribution_settings( $raw ) {
	$defaults = almaden_bookster_get_distribution_settings_defaults();
	$raw = is_array( $raw ) ? $raw : array();

	$mode = isset( $raw['default_distribution_mode'] ) ? sanitize_key( (string) $raw['default_distribution_mode'] ) : $defaults['default_distribution_mode'];
	if ( ! in_array( $mode, array( 'store_integrated', 'bookshelf_managed' ), true ) ) {
		$mode = $defaults['default_distribution_mode'];
	}

	$provider = isset( $raw['default_commerce_provider'] ) ? sanitize_key( (string) $raw['default_commerce_provider'] ) : $defaults['default_commerce_provider'];
	if ( '' === $provider ) {
		$provider = $defaults['default_commerce_provider'];
	}
	if ( function_exists( 'almaden_bookster_get_registered_commerce_provider' ) && ! almaden_bookster_get_registered_commerce_provider( $provider ) ) {
		$provider = $defaults['default_commerce_provider'];
	}

	$entry_mode = isset( $raw['default_reader_entry_mode'] ) ? sanitize_key( (string) $raw['default_reader_entry_mode'] ) : $defaults['default_reader_entry_mode'];
	if ( ! in_array( $entry_mode, array( 'product_cta', 'bookshelf_page' ), true ) ) {
		$entry_mode = $defaults['default_reader_entry_mode'];
	}

	$bookshelf_page_policy = isset( $raw['bookshelf_page_policy'] ) ? sanitize_key( (string) $raw['bookshelf_page_policy'] ) : $defaults['bookshelf_page_policy'];
	if ( ! in_array( $bookshelf_page_policy, array( 'auto_create', 'manual', 'disabled' ), true ) ) {
		$bookshelf_page_policy = $defaults['bookshelf_page_policy'];
	}

	$menu_location = isset( $raw['menu_location'] ) ? sanitize_key( (string) $raw['menu_location'] ) : $defaults['menu_location'];
	if ( '' === $menu_location ) {
		$menu_location = $defaults['menu_location'];
	}

	$return_url_policy = isset( $raw['return_url_policy'] ) ? sanitize_key( (string) $raw['return_url_policy'] ) : $defaults['return_url_policy'];
	if ( ! in_array( $return_url_policy, array( 'product_or_fallback', 'bookshelf_or_fallback', 'store_root' ), true ) ) {
		$return_url_policy = $defaults['return_url_policy'];
	}

	$valid_order_statuses = array();
	if ( isset( $raw['valid_order_statuses'] ) ) {
		$incoming_statuses = $raw['valid_order_statuses'];
		if ( is_string( $incoming_statuses ) ) {
			$incoming_statuses = explode( ',', $incoming_statuses );
		}
		if ( is_array( $incoming_statuses ) ) {
			foreach ( $incoming_statuses as $status ) {
				$status = sanitize_key( (string) $status );
				if ( '' !== $status ) {
					$valid_order_statuses[] = $status;
				}
			}
		}
	}
	$valid_order_statuses = array_values( array_unique( $valid_order_statuses ) );
	if ( empty( $valid_order_statuses ) ) {
		$valid_order_statuses = $defaults['valid_order_statuses'];
	}

	return array(
		'default_distribution_mode'  => $mode,
		'default_commerce_provider'  => $provider,
		'default_reader_entry_mode'  => $entry_mode,
		'bookshelf_page_policy'      => $bookshelf_page_policy,
		'auto_create_store_product'  => isset( $raw['auto_create_store_product'] ) ? 1 : 0,
		'auto_create_bookshelf_page' => isset( $raw['auto_create_bookshelf_page'] ) ? 1 : 0,
		'menu_injection_enabled'     => isset( $raw['menu_injection_enabled'] ) ? 1 : 0,
		'menu_location'              => $menu_location,
		'return_url_policy'          => $return_url_policy,
		'valid_order_statuses'       => $valid_order_statuses,
	);
}

function almaden_bookster_distribution_mode_label( $mode ) {
	switch ( sanitize_key( (string) $mode ) ) {
		case 'bookshelf_managed':
			return 'Bookshelf administrado';
		case 'store_integrated':
		default:
			return 'Tienda integrada';
	}
}

function almaden_bookster_commerce_provider_label( $provider ) {
	switch ( sanitize_key( (string) $provider ) ) {
		case 'woocommerce':
			return 'WooCommerce';
		case 'manual':
			return 'Manual';
		default:
			return ucfirst( str_replace( array( '-', '_' ), ' ', sanitize_key( (string) $provider ) ) );
	}
}

function almaden_bookster_reader_entry_mode_label( $mode ) {
	switch ( sanitize_key( (string) $mode ) ) {
		case 'bookshelf_page':
			return 'Página Bookshelf';
		case 'product_cta':
		default:
			return 'CTA en producto';
	}
}

function almaden_bookster_return_policy_label( $policy ) {
	switch ( sanitize_key( (string) $policy ) ) {
		case 'bookshelf_or_fallback':
			return 'Bookshelf o fallback';
		case 'store_root':
			return 'Raíz de la tienda';
		case 'product_or_fallback':
		default:
			return 'Producto o fallback';
	}
}

function almaden_bookster_handle_distribution_settings_save() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_distribution_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_distribution_nonce'], 'almaden_bookster_distribution_settings' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$raw = array(
		'default_distribution_mode'   => isset( $_POST['default_distribution_mode'] ) ? wp_unslash( $_POST['default_distribution_mode'] ) : '',
		'default_commerce_provider'   => isset( $_POST['default_commerce_provider'] ) ? wp_unslash( $_POST['default_commerce_provider'] ) : '',
		'default_reader_entry_mode'   => isset( $_POST['default_reader_entry_mode'] ) ? wp_unslash( $_POST['default_reader_entry_mode'] ) : '',
		'bookshelf_page_policy'       => isset( $_POST['bookshelf_page_policy'] ) ? wp_unslash( $_POST['bookshelf_page_policy'] ) : '',
		'auto_create_store_product'   => isset( $_POST['auto_create_store_product'] ),
		'auto_create_bookshelf_page'  => isset( $_POST['auto_create_bookshelf_page'] ),
		'menu_injection_enabled'      => isset( $_POST['menu_injection_enabled'] ),
		'menu_location'               => isset( $_POST['menu_location'] ) ? wp_unslash( $_POST['menu_location'] ) : '',
		'return_url_policy'           => isset( $_POST['return_url_policy'] ) ? wp_unslash( $_POST['return_url_policy'] ) : '',
		'valid_order_statuses'        => isset( $_POST['valid_order_statuses'] ) ? wp_unslash( $_POST['valid_order_statuses'] ) : '',
	);

	$woocommerce_status = almaden_bookster_get_woocommerce_status();
	if ( ! $woocommerce_status['active'] && ( ! isset( $raw['default_commerce_provider'] ) || 'woocommerce' === sanitize_key( (string) $raw['default_commerce_provider'] ) ) ) {
		$raw['default_commerce_provider'] = 'manual';
	}

	update_option( 'almaden_bookster_distribution_settings', almaden_bookster_sanitize_distribution_settings( $raw ) );

	$redirect_url = add_query_arg(
		array(
			'page'     => 'almaden-bookster-distribution',
			'saved'    => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_save_distribution_settings', 'almaden_bookster_handle_distribution_settings_save' );

function almaden_bookster_get_commerce_hardening_action_status() {
	return array(
		'done'  => isset( $_GET['commerce_hardening_done'] ) && '1' === (string) $_GET['commerce_hardening_done'],
		'scan'  => isset( $_GET['commerce_hardening_scan'] ) ? absint( $_GET['commerce_hardening_scan'] ) : 0,
	);
}

function almaden_bookster_register_distribution_menu() {
	add_submenu_page(
		'almaden-bookster',
		'Distribución y acceso',
		'Distribución',
		'almaden_manage_books',
		'almaden-bookster-distribution',
		'almaden_bookster_render_distribution_page'
	);
}
add_action( 'admin_menu', 'almaden_bookster_register_distribution_menu', 19 );

function almaden_bookster_render_distribution_page() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	$settings = almaden_bookster_get_distribution_settings();
	$woocommerce_status = almaden_bookster_get_woocommerce_status();
	$saved    = isset( $_GET['saved'] ) && '1' === (string) $_GET['saved'];
	require dirname( __DIR__, 2 ) . '/templates/admin/distribution-access-app.php';
}
