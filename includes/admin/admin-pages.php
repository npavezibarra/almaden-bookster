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

if ( ! function_exists( 'almaden_bookster_build_page_roles_field' ) ) {
	function almaden_bookster_build_page_roles_field( $section_key ) {
		$section_key = sanitize_key( (string) $section_key );

		$roles = function_exists( 'almaden_bookster_get_access_preview_roles' ) ? almaden_bookster_get_access_preview_roles() : array(
			'administrator' => 'Administrador',
			'editor'        => 'Editor',
			'author'        => 'Autor',
			'customer'      => 'Cliente',
			'subscriber'    => 'Suscriptor',
		);

		$checked_roles = function_exists( 'almaden_bookster_get_page_allowed_roles' ) ? almaden_bookster_get_page_allowed_roles( $section_key ) : array_keys( $roles );
		if ( empty( $checked_roles ) ) {
			$checked_roles = array_keys( $roles );
		}

		return array(
			'type'          => 'role_checkboxes',
			'name'          => 'page_visibility_allowed_roles[' . $section_key . ']',
			'label_heading'  => 'Acceso por rol',
			'label'         => 'Define qué roles pueden ver esta página.',
			'roles'         => $roles,
			'checked_roles' => $checked_roles,
		);
	}
}

if ( ! function_exists( 'almaden_bookster_build_custom_page_section' ) ) {
	function almaden_bookster_build_custom_page_section( $item ) {
		$slot_key = isset( $item['slot_key'] ) ? sanitize_key( (string) $item['slot_key'] ) : '';
		if ( '' === $slot_key ) {
			return array();
		}

		$page_id = isset( $item['page_id'] ) ? absint( $item['page_id'] ) : 0;
		$title   = isset( $item['title'] ) ? (string) $item['title'] : '';
		$slug    = isset( $item['slug'] ) ? (string) $item['slug'] : '';
		$page_type = isset( $item['page_type'] ) && 'regular' === (string) $item['page_type'] ? 'regular' : 'shell';
		$heading = '' !== $title ? $title : 'Nueva página';
		$status  = function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( $page_id, $slug, $title ) : array();
		$type_label = 'regular' === $page_type ? 'WordPress regular' : 'Almaden Shell';

		return array(
			'key'          => 'custom_page:' . $slot_key,
			'heading'      => $heading,
			'description'  => 'Página personalizada creada desde el panel de Pages.',
			'page_id_name' => 'custom_pages[' . $slot_key . '][page_id]',
			'title_name'   => 'custom_pages[' . $slot_key . '][title]',
			'slug_name'    => 'custom_pages[' . $slot_key . '][slug]',
			'page_id'      => $page_id,
			'title'        => $title,
			'slug'         => $slug,
			'page_type'    => $page_type,
			'type_label'   => $type_label,
			'url'          => function_exists( 'almaden_bookster_get_custom_page_url' ) ? almaden_bookster_get_custom_page_url( $slug ) : '',
			'status'       => $status,
				'extra_fields' => array(
					almaden_bookster_build_page_roles_field( 'custom_page:' . $slot_key ),
				),
			'is_custom'    => true,
			'slot_key'     => $slot_key,
			'sync_label'   => $page_id > 0 ? 'Sincronizar ahora' : 'CREAR',
		);
	}
}

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
			'shell_home_page_id' => isset( $_POST['shell_home_page_id'] ) ? absint( $_POST['shell_home_page_id'] ) : 0,
			'shell_home_slug'    => isset( $_POST['shell_home_slug'] ) ? wp_unslash( $_POST['shell_home_slug'] ) : '',
			'shell_home_title'   => isset( $_POST['shell_home_title'] ) ? wp_unslash( $_POST['shell_home_title'] ) : '',
				'shell_home_menu_enabled' => isset( $_POST['shell_home_menu_enabled'] ) ? 1 : 0,
				'contractor_page_id' => isset( $_POST['contractor_page_id'] ) ? absint( $_POST['contractor_page_id'] ) : 0,
				'contractor_slug'    => isset( $_POST['contractor_slug'] ) ? wp_unslash( $_POST['contractor_slug'] ) : '',
				'contractor_title'   => isset( $_POST['contractor_title'] ) ? wp_unslash( $_POST['contractor_title'] ) : '',
				'user_access_manager_page_id' => isset( $_POST['user_access_manager_page_id'] ) ? absint( $_POST['user_access_manager_page_id'] ) : 0,
				'user_access_manager_slug'    => isset( $_POST['user_access_manager_slug'] ) ? wp_unslash( $_POST['user_access_manager_slug'] ) : '',
				'user_access_manager_title'   => isset( $_POST['user_access_manager_title'] ) ? wp_unslash( $_POST['user_access_manager_title'] ) : '',
				'reading_stats_page_id' => isset( $_POST['reading_stats_page_id'] ) ? absint( $_POST['reading_stats_page_id'] ) : 0,
				'reading_stats_slug'    => isset( $_POST['reading_stats_slug'] ) ? wp_unslash( $_POST['reading_stats_slug'] ) : '',
				'reading_stats_title'   => isset( $_POST['reading_stats_title'] ) ? wp_unslash( $_POST['reading_stats_title'] ) : '',
				'authors_page_id' => isset( $_POST['authors_page_id'] ) ? absint( $_POST['authors_page_id'] ) : 0,
			'authors_slug'    => isset( $_POST['authors_slug'] ) ? wp_unslash( $_POST['authors_slug'] ) : '',
			'authors_title'   => isset( $_POST['authors_title'] ) ? wp_unslash( $_POST['authors_title'] ) : '',
			'store_page_id'   => isset( $_POST['store_page_id'] ) ? absint( $_POST['store_page_id'] ) : 0,
			'store_slug'      => isset( $_POST['store_slug'] ) ? wp_unslash( $_POST['store_slug'] ) : '',
			'store_title'     => isset( $_POST['store_title'] ) ? wp_unslash( $_POST['store_title'] ) : '',
			'store_menu_label'=> isset( $_POST['store_menu_label'] ) ? wp_unslash( $_POST['store_menu_label'] ) : '',
			'course_archive_page_id' => isset( $_POST['course_archive_page_id'] ) ? absint( $_POST['course_archive_page_id'] ) : 0,
			'course_archive_slug'    => isset( $_POST['course_archive_slug'] ) ? wp_unslash( $_POST['course_archive_slug'] ) : '',
			'course_archive_title'   => isset( $_POST['course_archive_title'] ) ? wp_unslash( $_POST['course_archive_title'] ) : '',
			'store_menu_enabled' => isset( $_POST['store_menu_enabled'] ) ? 1 : 0,
		)
	);

	$author_settings = function_exists( 'almaden_bookster_get_author_page_settings' ) ? almaden_bookster_get_author_page_settings() : array();
	if ( ! is_array( $author_settings ) ) {
		$author_settings = array();
	}
	$author_settings = array_merge(
		$author_settings,
		array(
			'page_id' => isset( $_POST['author_page_id'] ) ? absint( $_POST['author_page_id'] ) : 0,
			'slug'    => isset( $_POST['author_slug'] ) ? sanitize_title( wp_unslash( $_POST['author_slug'] ) ) : '',
			'title'   => isset( $_POST['author_title'] ) ? sanitize_text_field( wp_unslash( $_POST['author_title'] ) ) : '',
		)
	);

	$publisher_settings = function_exists( 'almaden_bookster_get_publisher_page_settings' ) ? almaden_bookster_get_publisher_page_settings() : array();
	if ( ! is_array( $publisher_settings ) ) {
		$publisher_settings = array();
	}
	$publisher_settings = array_merge(
		$publisher_settings,
		array(
			'page_id' => isset( $_POST['publisher_page_id'] ) ? absint( $_POST['publisher_page_id'] ) : 0,
			'slug'    => isset( $_POST['publisher_slug'] ) ? sanitize_title( wp_unslash( $_POST['publisher_slug'] ) ) : '',
			'title'   => isset( $_POST['publisher_title'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_title'] ) ) : '',
		)
	);

	$publisher_onboarding_settings = function_exists( 'almaden_bookster_get_publisher_onboarding_page_settings' ) ? almaden_bookster_get_publisher_onboarding_page_settings() : array();
	if ( ! is_array( $publisher_onboarding_settings ) ) {
		$publisher_onboarding_settings = array();
	}
	$publisher_onboarding_settings = array_merge(
		$publisher_onboarding_settings,
		array(
			'page_id' => isset( $_POST['publisher_onboarding_page_id'] ) ? absint( $_POST['publisher_onboarding_page_id'] ) : 0,
			'slug'    => isset( $_POST['publisher_onboarding_slug'] ) ? sanitize_title( wp_unslash( $_POST['publisher_onboarding_slug'] ) ) : '',
			'title'   => isset( $_POST['publisher_onboarding_title'] ) ? sanitize_text_field( wp_unslash( $_POST['publisher_onboarding_title'] ) ) : '',
		)
	);

	$quiz_settings = function_exists( 'almaden_bookster_get_quiz_page_settings' ) ? almaden_bookster_get_quiz_page_settings() : array();
	if ( ! is_array( $quiz_settings ) ) {
		$quiz_settings = array();
	}
	$quiz_settings = array_merge(
		$quiz_settings,
		array(
			'page_id' => isset( $_POST['quiz_page_id'] ) ? absint( $_POST['quiz_page_id'] ) : 0,
			'slug'    => isset( $_POST['quiz_slug'] ) ? sanitize_title( wp_unslash( $_POST['quiz_slug'] ) ) : '',
			'title'   => isset( $_POST['quiz_title'] ) ? sanitize_text_field( wp_unslash( $_POST['quiz_title'] ) ) : '',
		)
	);
	$custom_pages = function_exists( 'almaden_bookster_sanitize_custom_pages_settings' ) ? almaden_bookster_sanitize_custom_pages_settings( isset( $_POST['custom_pages'] ) ? (array) wp_unslash( $_POST['custom_pages'] ) : array() ) : array();
	$raw_allowed_roles_pages = isset( $_POST['page_visibility_allowed_roles'] ) ? (array) wp_unslash( $_POST['page_visibility_allowed_roles'] ) : array();
	if ( ! empty( $custom_pages ) && is_array( $raw_allowed_roles_pages ) ) {
		foreach ( $custom_pages as $custom_page ) {
			if ( ! is_array( $custom_page ) ) {
				continue;
			}

			$slot_key = isset( $custom_page['slot_key'] ) ? sanitize_key( (string) $custom_page['slot_key'] ) : '';
			$page_type = isset( $custom_page['page_type'] ) && 'regular' === (string) $custom_page['page_type'] ? 'regular' : 'shell';
			if ( '' !== $slot_key && 'regular' === $page_type ) {
				unset( $raw_allowed_roles_pages[ 'custom_page:' . $slot_key ] );
			}
		}
	}
	$page_visibility_settings = function_exists( 'almaden_bookster_sanitize_page_visibility_settings' ) ? almaden_bookster_sanitize_page_visibility_settings( array(
		'allowed_roles_pages' => $raw_allowed_roles_pages,
	) ) : array();
	$sync_section = isset( $_POST['sync_section'] ) ? trim( (string) wp_unslash( $_POST['sync_section'] ) ) : '';

	update_option( 'almaden_bookster_pages_settings', $settings );
	update_option( 'almaden_bookster_author_page_settings', $author_settings );
	update_option( 'almaden_bookster_publisher_page_settings', $publisher_settings );
	update_option( 'almaden_bookster_publisher_onboarding_page_settings', $publisher_onboarding_settings );
	update_option( 'almaden_bookster_quiz_page_settings', $quiz_settings );
	update_option( 'almaden_bookster_custom_pages_settings', $custom_pages );
	update_option( 'almaden_bookster_page_visibility_settings', $page_visibility_settings );
	if ( '' !== $sync_section ) {
		almaden_bookster_sync_pages_for_section( $sync_section );
		almaden_bookster_mark_shell_page( $sync_section );
		flush_rewrite_rules( false );
	}

	$redirect_args = array(
		'page'            => 'almaden-bookster-pages',
		'settings-updated'=> '1',
	);

	if ( '' !== $sync_section ) {
		$redirect_args['sync_section'] = $sync_section;
	}

	$redirect_url = add_query_arg( $redirect_args, admin_url( 'admin.php' ) );

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_bookster_save_pages_settings', 'almaden_bookster_handle_pages_settings_save' );

function almaden_bookster_get_page_id_for_section( $section_key ) {
	$section_key = trim( (string) $section_key );
	if ( 0 === strpos( $section_key, 'custom_page:' ) ) {
		$slot_key = sanitize_key( substr( $section_key, strlen( 'custom_page:' ) ) );
		$custom_page = function_exists( 'almaden_bookster_get_custom_page_settings_by_slot' ) ? almaden_bookster_get_custom_page_settings_by_slot( $slot_key ) : array();

		return isset( $custom_page['page_id'] ) ? absint( $custom_page['page_id'] ) : 0;
	}

	$getters = array(
		'shell_home'          => 'almaden_bookster_get_shell_home_page_id',
		'contractor'          => 'almaden_bookster_get_contractor_page_id',
		'user_access_manager' => 'almaden_bookster_get_user_access_manager_page_id',
		'creator'             => 'almaden_bookster_get_creator_page_id',
		'dashboard'           => 'almaden_bookster_get_dashboard_page_id',
		'reading_stats'       => 'almaden_bookster_get_reading_stats_page_id',
		'course_creator'      => 'almaden_bookster_get_course_creator_page_id',
		'course_archive'      => 'almaden_bookster_get_course_archive_page_id',
		'authors'             => 'almaden_bookster_get_authors_page_id',
		'author'              => 'almaden_bookster_get_author_page_id',
		'publisher'           => 'almaden_bookster_get_publisher_page_id',
		'publisher_onboarding' => 'almaden_bookster_get_publisher_onboarding_page_id',
		'quiz'                => 'almaden_bookster_get_quiz_page_id',
		'store'               => 'almaden_bookster_get_store_page_id',
	);

	if ( empty( $getters[ $section_key ] ) || ! is_callable( $getters[ $section_key ] ) ) {
		return 0;
	}

	return absint( call_user_func( $getters[ $section_key ] ) );
}

function almaden_bookster_mark_shell_page( $section_key ) {
	$section_key = trim( (string) $section_key );
	$page_id = almaden_bookster_get_page_id_for_section( $section_key );

	if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
		return;
	}

	update_post_meta( $page_id, '_almaden_bookster_shell_page', $section_key );
}

function almaden_bookster_sync_custom_page( $slot_key ) {
	$slot_key = sanitize_key( (string) $slot_key );
	if ( '' === $slot_key || ! function_exists( 'almaden_bookster_get_custom_page_settings_by_slot' ) ) {
		return;
	}

	$item = almaden_bookster_get_custom_page_settings_by_slot( $slot_key );
	if ( empty( $item ) || ! is_array( $item ) ) {
		return;
	}

	$page_id = isset( $item['page_id'] ) ? absint( $item['page_id'] ) : 0;
	$title   = isset( $item['title'] ) && '' !== trim( (string) $item['title'] ) ? (string) $item['title'] : 'Nueva página';
	$slug    = isset( $item['slug'] ) && '' !== trim( (string) $item['slug'] ) ? (string) $item['slug'] : sanitize_title( $title );
	$page_type = isset( $item['page_type'] ) && 'regular' === (string) $item['page_type'] ? 'regular' : 'shell';
	if ( '' === $slug ) {
		$slug = 'custom-page-' . $slot_key;
	}

	$page = $page_id > 0 ? get_post( $page_id ) : null;
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
				'post_content' => '',
			)
		);

		if ( is_wp_error( $new_page_id ) || ! $new_page_id ) {
			return;
		}

		$page_id = absint( $new_page_id );
	} else {
		$updates = array( 'ID' => $page->ID );
		if ( $page->post_title !== $title ) {
			$updates['post_title'] = $title;
		}
		if ( $page->post_name !== $slug ) {
			$updates['post_name'] = $slug;
		}
		if ( count( $updates ) > 1 ) {
			wp_update_post( $updates );
		}
		$page_id = (int) $page->ID;
	}

	$custom_pages = function_exists( 'almaden_bookster_get_custom_pages_settings' ) ? almaden_bookster_get_custom_pages_settings() : array();
	$updated = array();
	foreach ( $custom_pages as $existing_item ) {
		if ( ! is_array( $existing_item ) || empty( $existing_item['slot_key'] ) ) {
			continue;
		}

		if ( $slot_key === (string) $existing_item['slot_key'] ) {
			$existing_item['page_id'] = $page_id;
			$existing_item['title'] = $title;
			$existing_item['slug'] = $slug;
			$existing_item['page_type'] = $page_type;
		}

		$updated[] = $existing_item;
	}

	update_option( 'almaden_bookster_custom_pages_settings', $updated );
	if ( 'shell' === $page_type ) {
		update_post_meta( $page_id, '_almaden_bookster_shell_page', 'custom_page:' . $slot_key );
	} else {
		delete_post_meta( $page_id, '_almaden_bookster_shell_page' );
	}
}

function almaden_bookster_sync_pages_for_section( $section_key ) {
	$section_key = trim( (string) $section_key );

	switch ( $section_key ) {
		case 'shell_home':
			almaden_bookster_sync_shell_home_page();
			break;
		case 'contractor':
			almaden_bookster_sync_contractor_page();
			break;
		case 'user_access_manager':
			almaden_bookster_sync_user_access_manager_page();
			break;
		case 'creator':
			almaden_bookster_sync_creator_page();
			break;
			case 'dashboard':
				almaden_bookster_sync_dashboard_page();
				break;
			case 'reading_stats':
				almaden_bookster_sync_reading_stats_page();
				break;
		case 'course_creator':
			almaden_bookster_sync_course_creator_page();
			break;
		case 'course_archive':
			almaden_bookster_sync_course_archive_page();
			break;
		case 'blog_creator':
			almaden_bookster_sync_blog_creator_page();
			break;
		case 'authors':
			almaden_bookster_sync_authors_page();
			break;
		case 'author':
			almaden_bookster_sync_author_page();
			break;
		case 'publisher':
			almaden_bookster_sync_publisher_page();
			break;
		case 'publisher_onboarding':
			almaden_bookster_sync_publisher_onboarding_page();
			break;
		case 'quiz':
			almaden_bookster_sync_quiz_page();
			break;
		case 'store':
			almaden_bookster_sync_store_page( true );
			break;
		default:
			if ( 0 === strpos( $section_key, 'custom_page:' ) ) {
				almaden_bookster_sync_custom_page( substr( $section_key, strlen( 'custom_page:' ) ) );
			}
			break;
	}
}

function almaden_bookster_pages_page_render() {
	if ( ! almaden_bookster_user_can_manage_books() ) {
		wp_die( 'Permisos insuficientes.' );
	}

	$settings     = almaden_bookster_get_pages_settings();
	$creator_url  = almaden_bookster_get_creator_page_url();
	$course_creator_url = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : '';
	$course_archive_url  = function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : '';
	$success_flag = isset( $_GET['settings-updated'] ) && '1' === $_GET['settings-updated'];
	$page_sections = function_exists( 'almaden_bookster_get_pages_admin_sections' ) ? almaden_bookster_get_pages_admin_sections() : array();
	$sync_section  = isset( $_GET['sync_section'] ) ? sanitize_key( (string) $_GET['sync_section'] ) : '';
	$book_media_migration_report = function_exists( 'almaden_bookster_get_book_media_migration_report' ) ? almaden_bookster_get_book_media_migration_report() : array();
	$book_media_migration_status = function_exists( 'almaden_bookster_get_book_media_migration_action_status' ) ? almaden_bookster_get_book_media_migration_action_status() : array();

	require dirname( __DIR__, 2 ) . '/templates/admin/pages-app.php';
}

function almaden_bookster_get_pages_admin_sections() {
	$shared_settings = almaden_bookster_get_pages_settings();
	$author_settings = function_exists( 'almaden_bookster_get_author_page_settings' ) ? almaden_bookster_get_author_page_settings() : array();
	$publisher_settings = function_exists( 'almaden_bookster_get_publisher_page_settings' ) ? almaden_bookster_get_publisher_page_settings() : array();
	$publisher_onboarding_settings = function_exists( 'almaden_bookster_get_publisher_onboarding_page_settings' ) ? almaden_bookster_get_publisher_onboarding_page_settings() : array();
	$quiz_settings = function_exists( 'almaden_bookster_get_quiz_page_settings' ) ? almaden_bookster_get_quiz_page_settings() : array();
	$course_creator_url = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : '';
	$course_archive_url  = function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : '';
	$custom_page_settings = function_exists( 'almaden_bookster_get_custom_pages_settings' ) ? almaden_bookster_get_custom_pages_settings() : array();

	$sections = array();

	foreach ( $custom_page_settings as $custom_page ) {
		$custom_section = function_exists( 'almaden_bookster_build_custom_page_section' ) ? almaden_bookster_build_custom_page_section( $custom_page ) : array();
		if ( ! empty( $custom_section ) ) {
			$sections[] = $custom_section;
		}
	}

	$sections = array_merge( $sections, array(
		array(
			'key'         => 'shell_home',
			'heading'     => 'Almaden App',
			'description' => 'Página de entrada al shell. No se crea ni entra al menú público hasta que lo solicites expresamente.',
			'page_id_name'=> 'shell_home_page_id',
			'title_name'  => 'shell_home_title',
			'slug_name'   => 'shell_home_slug',
			'page_id'     => isset( $shared_settings['shell_home_page_id'] ) ? absint( $shared_settings['shell_home_page_id'] ) : 0,
			'title'       => isset( $shared_settings['shell_home_title'] ) ? $shared_settings['shell_home_title'] : '',
			'slug'        => isset( $shared_settings['shell_home_slug'] ) ? $shared_settings['shell_home_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['shell_home_page_id'] ) ? $shared_settings['shell_home_page_id'] : 0, isset( $shared_settings['shell_home_slug'] ) ? $shared_settings['shell_home_slug'] : '', isset( $shared_settings['shell_home_title'] ) ? $shared_settings['shell_home_title'] : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'shell_home' ),
				array(
					'type'  => 'checkbox',
					'name'  => 'shell_home_menu_enabled',
					'label' => 'Activar inserción automática del Home de Almaden App en el menú público de WordPress.',
					'checked' => ! empty( $shared_settings['shell_home_menu_enabled'] ),
				),
			) ),
		),
		array(
			'key'         => 'contractor',
			'heading'     => 'Contractor',
			'description' => 'Página de configuración de marca del instalador del plugin. Desde aquí se gestiona el nombre de la empresa y el logo del shell.',
			'page_id_name'=> 'contractor_page_id',
			'title_name'  => 'contractor_title',
			'slug_name'   => 'contractor_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_contractor_page_id' ) ? almaden_bookster_get_contractor_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_contractor_title' ) ? almaden_bookster_get_contractor_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_contractor_slug' ) ? almaden_bookster_get_contractor_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_contractor_page_url' ) ? almaden_bookster_get_contractor_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_contractor_page_id' ) ? almaden_bookster_get_contractor_page_id() : 0, function_exists( 'almaden_bookster_get_contractor_slug' ) ? almaden_bookster_get_contractor_slug() : '', function_exists( 'almaden_bookster_get_contractor_title' ) ? almaden_bookster_get_contractor_title() : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'contractor' ),
			) ),
		),
		array(
			'key'         => 'user_access_manager',
			'heading'     => function_exists( 'almaden_bookster_get_user_access_manager_title' ) ? almaden_bookster_get_user_access_manager_title() : 'User Access',
			'description' => 'Página Shell para que los administradores asignen o retiren cursos y ebooks a usuarios de WordPress.',
			'page_id_name'=> 'user_access_manager_page_id',
			'title_name'  => 'user_access_manager_title',
			'slug_name'   => 'user_access_manager_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_user_access_manager_page_id' ) ? almaden_bookster_get_user_access_manager_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_user_access_manager_title' ) ? almaden_bookster_get_user_access_manager_title() : 'User Access',
			'slug'        => function_exists( 'almaden_bookster_get_user_access_manager_slug' ) ? almaden_bookster_get_user_access_manager_slug() : 'user-access',
			'url'         => function_exists( 'almaden_bookster_get_user_access_manager_page_url' ) ? almaden_bookster_get_user_access_manager_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_user_access_manager_page_id' ) ? almaden_bookster_get_user_access_manager_page_id() : 0, function_exists( 'almaden_bookster_get_user_access_manager_slug' ) ? almaden_bookster_get_user_access_manager_slug() : 'user-access', function_exists( 'almaden_bookster_get_user_access_manager_title' ) ? almaden_bookster_get_user_access_manager_title() : 'User Access' ) : array(),
			'extra_fields' => array( almaden_bookster_build_page_roles_field( 'user_access_manager' ) ),
		),
		array(
			'key'         => 'creator',
			'heading'     => 'Taller',
			'description' => 'Página interna para crear y editar libros.',
			'page_id_name'=> 'creator_page_id',
			'title_name'  => 'creator_title',
			'slug_name'   => 'creator_slug',
			'page_id'     => isset( $shared_settings['creator_page_id'] ) ? absint( $shared_settings['creator_page_id'] ) : 0,
			'title'       => isset( $shared_settings['creator_title'] ) ? $shared_settings['creator_title'] : '',
			'slug'        => isset( $shared_settings['creator_slug'] ) ? $shared_settings['creator_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_creator_page_url' ) ? almaden_bookster_get_creator_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['creator_page_id'] ) ? $shared_settings['creator_page_id'] : 0, isset( $shared_settings['creator_slug'] ) ? $shared_settings['creator_slug'] : '', isset( $shared_settings['creator_title'] ) ? $shared_settings['creator_title'] : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'creator' ),
			) ),
		),
		array(
			'key'         => 'blog_creator',
			'heading'     => 'Blog',
			'description' => 'Página interna para crear y editar entradas del blog.',
			'page_id_name'=> 'blog_creator_page_id',
			'title_name'  => 'blog_creator_title',
			'slug_name'   => 'blog_creator_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_blog_creator_page_id' ) ? almaden_bookster_get_blog_creator_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_blog_creator_page_url' ) ? almaden_bookster_get_blog_creator_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_blog_creator_page_id' ) ? almaden_bookster_get_blog_creator_page_id() : 0, function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : '', function_exists( 'almaden_bookster_get_blog_creator_title' ) ? almaden_bookster_get_blog_creator_title() : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'blog_creator' ),
			) ),
		),
		array(
			'key'         => 'dashboard',
			'heading'     => 'Dashboard',
			'description' => 'Panel principal del shell para usuarios con acceso.',
			'page_id_name'=> 'dashboard_page_id',
			'title_name'  => 'dashboard_title',
			'slug_name'   => 'dashboard_slug',
			'page_id'     => isset( $shared_settings['dashboard_page_id'] ) ? absint( $shared_settings['dashboard_page_id'] ) : 0,
			'title'       => isset( $shared_settings['dashboard_title'] ) ? $shared_settings['dashboard_title'] : '',
			'slug'        => isset( $shared_settings['dashboard_slug'] ) ? $shared_settings['dashboard_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_dashboard_page_url' ) ? almaden_bookster_get_dashboard_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['dashboard_page_id'] ) ? $shared_settings['dashboard_page_id'] : 0, isset( $shared_settings['dashboard_slug'] ) ? $shared_settings['dashboard_slug'] : '', isset( $shared_settings['dashboard_title'] ) ? $shared_settings['dashboard_title'] : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'dashboard' ),
			) ),
		),
		array(
			'key'         => 'reading_stats',
			'heading'     => 'My Reading Stats',
			'description' => 'Panel personal de highlights, quizzes y actividad de lectura.',
			'page_id_name'=> 'reading_stats_page_id',
			'title_name'  => 'reading_stats_title',
			'slug_name'   => 'reading_stats_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_reading_stats_page_id' ) ? almaden_bookster_get_reading_stats_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_reading_stats_slug' ) ? almaden_bookster_get_reading_stats_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_reading_stats_page_url' ) ? almaden_bookster_get_reading_stats_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_reading_stats_page_id' ) ? almaden_bookster_get_reading_stats_page_id() : 0, function_exists( 'almaden_bookster_get_reading_stats_slug' ) ? almaden_bookster_get_reading_stats_slug() : '', function_exists( 'almaden_bookster_get_reading_stats_title' ) ? almaden_bookster_get_reading_stats_title() : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'reading_stats' ),
			) ),
		),
		array(
			'key'         => 'course_creator',
			'heading'     => 'Sala de clases',
			'description' => 'Página interna para crear cursos.',
			'page_id_name'=> 'course_creator_page_id',
			'title_name'  => 'course_creator_title',
			'slug_name'   => 'course_creator_slug',
			'page_id'     => isset( $shared_settings['course_creator_page_id'] ) ? absint( $shared_settings['course_creator_page_id'] ) : 0,
			'title'       => isset( $shared_settings['course_creator_title'] ) ? $shared_settings['course_creator_title'] : '',
			'slug'        => isset( $shared_settings['course_creator_slug'] ) ? $shared_settings['course_creator_slug'] : '',
			'url'         => $course_creator_url,
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['course_creator_page_id'] ) ? $shared_settings['course_creator_page_id'] : 0, isset( $shared_settings['course_creator_slug'] ) ? $shared_settings['course_creator_slug'] : '', isset( $shared_settings['course_creator_title'] ) ? $shared_settings['course_creator_title'] : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'course_creator' ),
			) ),
		),
		array(
			'key'         => 'course_archive',
			'heading'     => 'Cursos',
			'description' => 'Archivo público de cursos.',
			'page_id_name'=> 'course_archive_page_id',
			'title_name'  => 'course_archive_title',
			'slug_name'   => 'course_archive_slug',
			'page_id'     => isset( $shared_settings['course_archive_page_id'] ) ? absint( $shared_settings['course_archive_page_id'] ) : 0,
			'title'       => isset( $shared_settings['course_archive_title'] ) ? $shared_settings['course_archive_title'] : '',
			'slug'        => isset( $shared_settings['course_archive_slug'] ) ? $shared_settings['course_archive_slug'] : '',
			'url'         => $course_archive_url,
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['course_archive_page_id'] ) ? $shared_settings['course_archive_page_id'] : 0, isset( $shared_settings['course_archive_slug'] ) ? $shared_settings['course_archive_slug'] : '', isset( $shared_settings['course_archive_title'] ) ? $shared_settings['course_archive_title'] : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'course_archive' ),
			),
		),
		array(
			'key'         => 'authors',
			'heading'     => 'Autores',
			'description' => 'Directorio de autores.',
			'page_id_name'=> 'authors_page_id',
			'title_name'  => 'authors_title',
			'slug_name'   => 'authors_slug',
			'page_id'     => isset( $shared_settings['authors_page_id'] ) ? absint( $shared_settings['authors_page_id'] ) : 0,
			'title'       => isset( $shared_settings['authors_title'] ) ? $shared_settings['authors_title'] : '',
			'slug'        => isset( $shared_settings['authors_slug'] ) ? $shared_settings['authors_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_authors_page_url' ) ? almaden_bookster_get_authors_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['authors_page_id'] ) ? $shared_settings['authors_page_id'] : 0, isset( $shared_settings['authors_slug'] ) ? $shared_settings['authors_slug'] : '', isset( $shared_settings['authors_title'] ) ? $shared_settings['authors_title'] : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'authors' ),
			),
		),
		array(
			'key'         => 'author',
			'heading'     => 'Autor',
			'description' => 'Página individual de autor.',
			'page_id_name'=> 'author_page_id',
			'title_name'  => 'author_title',
			'slug_name'   => 'author_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_author_page_id' ) ? almaden_bookster_get_author_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_author_page_title' ) ? almaden_bookster_get_author_page_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_author_page_slug' ) ? almaden_bookster_get_author_page_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_author_page_url' ) ? almaden_bookster_get_author_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_author_page_id' ) ? almaden_bookster_get_author_page_id() : 0, function_exists( 'almaden_bookster_get_author_page_slug' ) ? almaden_bookster_get_author_page_slug() : '', function_exists( 'almaden_bookster_get_author_page_title' ) ? almaden_bookster_get_author_page_title() : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'author' ),
			),
		),
		array(
			'key'         => 'publisher',
			'heading'     => 'Editorial',
			'description' => 'Página pública de perfil de editorial.',
			'page_id_name'=> 'publisher_page_id',
			'title_name'  => 'publisher_title',
			'slug_name'   => 'publisher_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_publisher_page_id' ) ? almaden_bookster_get_publisher_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_publisher_page_title' ) ? almaden_bookster_get_publisher_page_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_publisher_page_slug' ) ? almaden_bookster_get_publisher_page_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_publisher_page_url' ) ? almaden_bookster_get_publisher_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_publisher_page_id' ) ? almaden_bookster_get_publisher_page_id() : 0, function_exists( 'almaden_bookster_get_publisher_page_slug' ) ? almaden_bookster_get_publisher_page_slug() : '', function_exists( 'almaden_bookster_get_publisher_page_title' ) ? almaden_bookster_get_publisher_page_title() : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'publisher' ),
			),
		),
		array(
			'key'         => 'publisher_onboarding',
			'heading'     => 'Crear editorial',
			'description' => 'Página pública para registro de editoriales.',
			'page_id_name'=> 'publisher_onboarding_page_id',
			'title_name'  => 'publisher_onboarding_title',
			'slug_name'   => 'publisher_onboarding_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_publisher_onboarding_page_id' ) ? almaden_bookster_get_publisher_onboarding_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_publisher_onboarding_page_title' ) ? almaden_bookster_get_publisher_onboarding_page_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_publisher_onboarding_page_slug' ) ? almaden_bookster_get_publisher_onboarding_page_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_publisher_onboarding_page_url' ) ? almaden_bookster_get_publisher_onboarding_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_publisher_onboarding_page_id' ) ? almaden_bookster_get_publisher_onboarding_page_id() : 0, function_exists( 'almaden_bookster_get_publisher_onboarding_page_slug' ) ? almaden_bookster_get_publisher_onboarding_page_slug() : '', function_exists( 'almaden_bookster_get_publisher_onboarding_page_title' ) ? almaden_bookster_get_publisher_onboarding_page_title() : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'publisher_onboarding' ),
			),
		),
		array(
			'key'         => 'quiz',
			'heading'     => 'Book Quiz',
			'description' => 'Página del creador de quizzes.',
			'page_id_name'=> 'quiz_page_id',
			'title_name'  => 'quiz_title',
			'slug_name'   => 'quiz_slug',
			'page_id'     => function_exists( 'almaden_bookster_get_quiz_page_id' ) ? almaden_bookster_get_quiz_page_id() : 0,
			'title'       => function_exists( 'almaden_bookster_get_quiz_page_title' ) ? almaden_bookster_get_quiz_page_title() : '',
			'slug'        => function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : '',
			'url'         => function_exists( 'almaden_bookster_get_quiz_page_url' ) ? almaden_bookster_get_quiz_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( function_exists( 'almaden_bookster_get_quiz_page_id' ) ? almaden_bookster_get_quiz_page_id() : 0, function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : '', function_exists( 'almaden_bookster_get_quiz_page_title' ) ? almaden_bookster_get_quiz_page_title() : '' ) : array(),
			'extra_fields' => array_filter( array(
				almaden_bookster_build_page_roles_field( 'quiz' ),
			) ),
		),
		array(
			'key'         => 'store',
			'heading'     => 'Bookshelf',
			'description' => 'Página pública del catálogo de ebooks administrada por Bookster. Puede sincronizarse como una página real de WordPress y exponerse en el menú principal.',
			'page_id_name'=> 'store_page_id',
			'title_name'  => 'store_title',
			'slug_name'   => 'store_slug',
			'page_id'     => isset( $shared_settings['store_page_id'] ) ? absint( $shared_settings['store_page_id'] ) : 0,
			'title'       => isset( $shared_settings['store_title'] ) ? $shared_settings['store_title'] : '',
			'slug'        => isset( $shared_settings['store_slug'] ) ? $shared_settings['store_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_store_page_url' ) ? almaden_bookster_get_store_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['store_page_id'] ) ? $shared_settings['store_page_id'] : 0, isset( $shared_settings['store_slug'] ) ? $shared_settings['store_slug'] : '', isset( $shared_settings['store_title'] ) ? $shared_settings['store_title'] : '' ) : array(),
			'extra_fields' => array(
				almaden_bookster_build_page_roles_field( 'store' ),
				array(
					'type'  => 'text',
					'name'  => 'store_menu_label',
					'label' => 'Etiqueta del menú',
					'value' => isset( $shared_settings['store_menu_label'] ) ? $shared_settings['store_menu_label'] : '',
					'description' => 'Texto del enlace que se inyecta en el menú público del sitio.',
				),
				array(
					'type'  => 'checkbox',
					'name'  => 'store_menu_enabled',
					'label' => 'Activar inserción automática de Ebook Store en el menú del sitio.',
					'checked' => ! empty( $shared_settings['store_menu_enabled'] ),
				),
			),
		),
	) );

	return $sections;
}
