<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_register_pages_menu() {
	add_submenu_page(
		'almaden-bookster',
		'Pages',
		'Pages',
		'almaden_manage_books',
		'almaden-bookster-pages',
		'almaden_bookster_pages_page_render'
	);
}
add_action( 'admin_menu', 'almaden_bookster_register_pages_menu', 20 );

function almaden_bookster_handle_pages_settings_save() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	if ( ! isset( $_POST['almaden_pages_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_pages_nonce'], 'almaden_bookster_pages_settings' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$settings = almaden_bookster_sanitize_pages_settings(
		array(
			'creator_page_id' => isset( $_POST['creator_page_id'] ) ? absint( $_POST['creator_page_id'] ) : 0,
			'creator_slug'    => isset( $_POST['creator_slug'] ) ? wp_unslash( $_POST['creator_slug'] ) : '',
			'creator_title'   => isset( $_POST['creator_title'] ) ? wp_unslash( $_POST['creator_title'] ) : '',
			'store_page_id'   => isset( $_POST['store_page_id'] ) ? absint( $_POST['store_page_id'] ) : 0,
			'store_slug'      => isset( $_POST['store_slug'] ) ? wp_unslash( $_POST['store_slug'] ) : '',
			'store_title'     => isset( $_POST['store_title'] ) ? wp_unslash( $_POST['store_title'] ) : '',
			'store_menu_label'=> isset( $_POST['store_menu_label'] ) ? wp_unslash( $_POST['store_menu_label'] ) : '',
			'store_menu_enabled' => isset( $_POST['store_menu_enabled'] ) ? 1 : 0,
		)
	);

	update_option( 'almaden_bookster_pages_settings', $settings );
	almaden_bookster_sync_creator_page();
	almaden_bookster_sync_store_page();

	$redirect_url = add_query_arg(
		array(
			'page'             => 'almaden-bookster-pages',
			'settings-updated'  => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_save_pages_settings', 'almaden_bookster_handle_pages_settings_save' );

function almaden_bookster_pages_page_render() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	$settings     = almaden_bookster_get_pages_settings();
	$creator_url  = almaden_bookster_get_creator_page_url();
	$success_flag = isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'];

	require dirname( __DIR__, 2 ) . '/templates/admin/pages-app.php';
}
