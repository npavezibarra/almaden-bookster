<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function almaden_bookster_get_publisher_onboarding_slug() {
	return function_exists( 'almaden_bookster_get_publisher_onboarding_page_slug' ) ? almaden_bookster_get_publisher_onboarding_page_slug() : 'crear-editorial';
}

function almaden_bookster_get_publisher_onboarding_title() {
	return function_exists( 'almaden_bookster_get_publisher_onboarding_page_title' ) ? almaden_bookster_get_publisher_onboarding_page_title() : __( 'Crear editorial', 'almaden-bookster' );
}

function almaden_bookster_get_publisher_onboarding_url() {
	return function_exists( 'almaden_bookster_get_publisher_onboarding_page_url' ) ? almaden_bookster_get_publisher_onboarding_page_url() : home_url( '/crear-editorial/' );
}

function almaden_bookster_sync_publisher_onboarding_page() {
	$settings = almaden_bookster_get_publisher_onboarding_page_settings();
	$slug    = almaden_bookster_get_publisher_onboarding_slug();
	$title   = almaden_bookster_get_publisher_onboarding_title();
	$page_id = isset( $settings['page_id'] ) ? absint( $settings['page_id'] ) : 0;
	$page    = $page_id > 0 ? get_post( $page_id ) : null;

	if ( $page && 'page' !== $page->post_type ) {
		$page = null;
	}

	if ( ! $page ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
	}

	if ( ! $page ) {
		$new_page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			)
		);

		if ( ! is_wp_error( $new_page_id ) && $new_page_id ) {
			$settings['page_id'] = absint( $new_page_id );
			$settings['slug']    = $slug;
			$settings['title']   = $title;
			update_option( 'almaden_bookster_publisher_onboarding_page_settings', $settings );
			return absint( $new_page_id );
		}

		return 0;
	}

	if ( 'page' !== $page->post_type ) {
		return 0;
	}

	$updates = array( 'ID' => $page->ID );

	if ( $page->post_name !== $slug ) {
		$updates['post_name'] = $slug;
	}

	if ( $page->post_title !== $title ) {
		$updates['post_title'] = $title;
	}

	if ( count( $updates ) > 1 ) {
		wp_update_post( $updates );
	}

	if ( $page_id !== (int) $page->ID ) {
		$settings['page_id'] = (int) $page->ID;
		$settings['slug']    = $slug;
		$settings['title']   = $title;
		update_option( 'almaden_bookster_publisher_onboarding_page_settings', $settings );
	}

	return absint( $page->ID );
}

function almaden_bookster_load_publisher_onboarding_page() {
	if ( ! is_page( almaden_bookster_get_publisher_onboarding_slug() ) || ! is_main_query() ) {
		return;
	}

	show_admin_bar( false );

	$template_path = dirname( __FILE__ ) . '/../../templates/publishers/publisher-onboarding-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla del onboarding de editoriales no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_publisher_onboarding_page', 5 );

function almaden_bookster_generate_unique_user_login( $email, $fallback = '' ) {
	$base = sanitize_user( current( explode( '@', sanitize_email( $email ) ) ), true );

	if ( '' === $base ) {
		$base = sanitize_user( $fallback, true );
	}

	if ( '' === $base ) {
		$base = 'editorial';
	}

	$login   = $base;
	$suffix  = 1;

	while ( username_exists( $login ) ) {
		$login = $base . '-' . $suffix;
		$suffix++;
	}

	return $login;
}

function almaden_bookster_resolve_unique_publisher_slug( $base_slug ) {
	$slug = sanitize_title( (string) $base_slug );

	if ( '' === $slug ) {
		$slug = 'editorial';
	}

	$original_slug = $slug;
	$suffix        = 2;

	while ( function_exists( 'almaden_bookster_get_publisher_by_slug' ) && almaden_bookster_get_publisher_by_slug( $slug ) ) {
		$slug = $original_slug . '-' . $suffix;
		$suffix++;
	}

	return $slug;
}

function almaden_bookster_handle_publisher_logo_upload( $file_key ) {
	if ( empty( $_FILES[ $file_key ] ) || empty( $_FILES[ $file_key ]['name'] ) ) {
		return 0;
	}

	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$upload = wp_handle_upload(
		$_FILES[ $file_key ],
		array(
			'test_form' => false,
		)
	);

	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'almaden_publisher_logo_upload_failed', $upload['error'] );
	}

	$file_path = isset( $upload['file'] ) ? $upload['file'] : '';
	$file_url  = isset( $upload['url'] ) ? $upload['url'] : '';
	$file_type = wp_check_filetype( basename( $file_path ), null );

	if ( '' === $file_path || '' === $file_url || empty( $file_type['type'] ) ) {
		return new WP_Error( 'almaden_publisher_logo_invalid', __( 'No se pudo procesar el logo subido.', 'almaden-bookster' ) );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'guid'           => $file_url,
			'post_mime_type'  => $file_type['type'],
			'post_title'      => preg_replace( '/\.[^.]+$/', '', basename( $file_path ) ),
			'post_content'    => '',
			'post_status'     => 'inherit',
		),
		$file_path
	);

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$metadata = wp_generate_attachment_metadata( $attachment_id, $file_path );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	return absint( $attachment_id );
}

function almaden_bookster_redirect_publisher_onboarding_error( $message ) {
	wp_safe_redirect(
		add_query_arg(
			array(
				'onboarding_status'  => 'error',
				'onboarding_message' => $message,
			),
			almaden_bookster_get_publisher_onboarding_url()
		)
	);
	exit;
}

function almaden_bookster_handle_publisher_onboarding() {
	if ( ! isset( $_POST['almaden_publisher_onboarding_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_publisher_onboarding_nonce'], 'almaden_publisher_onboarding' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$publisher_name       = isset( $_POST['publisher_name'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_name'] ) ) : '';
	$publisher_legal_name = isset( $_POST['publisher_legal_name'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_legal_name'] ) ) : '';
	$publisher_rut        = isset( $_POST['publisher_rut'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_rut'] ) ) : '';
	$publisher_description = isset( $_POST['publisher_description'] ) ? wp_kses_post( wp_unslash( $_POST['publisher_description'] ) ) : '';
	$publisher_keywords   = isset( $_POST['publisher_keywords'] ) ? sanitize_textarea_field( wp_unslash( $_POST['publisher_keywords'] ) ) : '';
	$publisher_email      = isset( $_POST['publisher_email'] ) ? sanitize_email( wp_unslash( $_POST['publisher_email'] ) ) : '';
	$publisher_phone      = isset( $_POST['publisher_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_phone'] ) ) : '';
	$publisher_website    = isset( $_POST['publisher_website'] ) ? esc_url_raw( wp_unslash( $_POST['publisher_website'] ) ) : '';
	$contact_name         = isset( $_POST['contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_name'] ) ) : '';
	$account_password     = isset( $_POST['account_password'] ) ? (string) wp_unslash( $_POST['account_password'] ) : '';
	$account_password_2   = isset( $_POST['account_password_confirm'] ) ? (string) wp_unslash( $_POST['account_password_confirm'] ) : '';

	if ( '' === $publisher_name ) {
		almaden_bookster_redirect_publisher_onboarding_error( __( 'El nombre de la editorial es obligatorio.', 'almaden-bookster' ) );
	}

	if ( '' === $publisher_email || ! is_email( $publisher_email ) ) {
		almaden_bookster_redirect_publisher_onboarding_error( __( 'Necesitamos un correo válido para crear la cuenta.', 'almaden-bookster' ) );
	}

	if ( strlen( $account_password ) < 8 ) {
		almaden_bookster_redirect_publisher_onboarding_error( __( 'La contraseña debe tener al menos 8 caracteres.', 'almaden-bookster' ) );
	}

	if ( $account_password !== $account_password_2 ) {
		almaden_bookster_redirect_publisher_onboarding_error( __( 'Las contraseñas no coinciden.', 'almaden-bookster' ) );
	}

	$existing_user_id = email_exists( $publisher_email );
	$current_user_id  = get_current_user_id();
	$created_user     = false;

	if ( $existing_user_id && (int) $existing_user_id !== $current_user_id ) {
		almaden_bookster_redirect_publisher_onboarding_error( __( 'Ese correo ya tiene una cuenta. Inicia sesión y vuelve a intentar.', 'almaden-bookster' ) );
	}

	if ( $current_user_id > 0 && ! $existing_user_id ) {
		$user_id = $current_user_id;
	} elseif ( $existing_user_id ) {
		$user_id = absint( $existing_user_id );
	} else {
		$user_login = almaden_bookster_generate_unique_user_login( $publisher_email, $publisher_name );
		$user_id    = wp_insert_user(
			array(
				'user_login'   => $user_login,
				'user_email'   => $publisher_email,
				'user_pass'    => $account_password,
				'display_name' => '' !== $contact_name ? $contact_name : $publisher_name,
				'first_name'   => $contact_name,
				'nickname'     => '' !== $contact_name ? $contact_name : $publisher_name,
				'role'         => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			almaden_bookster_redirect_publisher_onboarding_error( $user_id->get_error_message() );
		}

		$created_user = true;
		$user_id      = absint( $user_id );
	}

	$logo_id = 0;
	if ( ! empty( $_FILES['publisher_logo_file'] ) && ! empty( $_FILES['publisher_logo_file']['name'] ) ) {
		$logo_id = almaden_bookster_handle_publisher_logo_upload( 'publisher_logo_file' );
		if ( is_wp_error( $logo_id ) ) {
			if ( $created_user ) {
				wp_delete_user( $user_id );
			}
			almaden_bookster_redirect_publisher_onboarding_error( $logo_id->get_error_message() );
		}
	}

	$publisher_slug = almaden_bookster_resolve_unique_publisher_slug( $publisher_name );
	$publisher_id   = almaden_bookster_save_publisher(
		array(
			'slug'        => $publisher_slug,
			'name'        => $publisher_name,
			'legal_name'  => '' !== $publisher_legal_name ? $publisher_legal_name : $publisher_name,
			'rut'         => $publisher_rut,
			'description' => $publisher_description,
			'email'       => $publisher_email,
			'phone'       => $publisher_phone,
			'website'     => $publisher_website,
			'logo'        => absint( $logo_id ),
			'keywords'    => $publisher_keywords,
			'status'      => 'active',
		)
	);

	if ( is_wp_error( $publisher_id ) ) {
		if ( $created_user ) {
			wp_delete_user( $user_id );
		}
		almaden_bookster_redirect_publisher_onboarding_error( $publisher_id->get_error_message() );
	}

	$publisher_id = absint( $publisher_id );
	$member_id    = function_exists( 'almaden_bookster_upsert_publisher_member' ) ? almaden_bookster_upsert_publisher_member( $publisher_id, $user_id, 'owner', 'active' ) : false;
	if ( is_wp_error( $member_id ) ) {
		global $wpdb;
		$wpdb->delete( almaden_bookster_get_publishers_table_name(), array( 'id' => $publisher_id ), array( '%d' ) );
		if ( $created_user ) {
			wp_delete_user( $user_id );
		}
		almaden_bookster_redirect_publisher_onboarding_error( $member_id->get_error_message() );
	}

	update_user_meta( $user_id, '_almaden_primary_publisher_id', $publisher_id );

	$publisher_user = new WP_User( $user_id );
	$publisher_user->add_cap( 'almaden_manage_books' );
	$publisher_user->add_cap( 'upload_files' );

	if ( function_exists( 'almaden_bookster_mark_publisher_tour_active' ) ) {
		almaden_bookster_mark_publisher_tour_active( $user_id );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );

	wp_safe_redirect(
		almaden_bookster_get_creator_page_url(
			array(
				'publisher_created' => '1',
				'publisher_tour'    => '1',
				'publisher_id'      => $publisher_id,
			)
		)
	);
	exit;
}
add_action( 'admin_post_almaden_create_publisher', 'almaden_bookster_handle_publisher_onboarding' );
add_action( 'admin_post_nopriv_almaden_create_publisher', 'almaden_bookster_handle_publisher_onboarding' );
