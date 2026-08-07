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
			'shell_home_page_id' => isset( $_POST['shell_home_page_id'] ) ? absint( $_POST['shell_home_page_id'] ) : 0,
			'shell_home_slug'    => isset( $_POST['shell_home_slug'] ) ? wp_unslash( $_POST['shell_home_slug'] ) : '',
			'shell_home_title'   => isset( $_POST['shell_home_title'] ) ? wp_unslash( $_POST['shell_home_title'] ) : '',
			'shell_home_menu_enabled' => isset( $_POST['shell_home_menu_enabled'] ) ? 1 : 0,
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
	$sync_section = isset( $_POST['sync_section'] ) ? sanitize_key( (string) wp_unslash( $_POST['sync_section'] ) ) : '';

	update_option( 'almaden_bookster_pages_settings', $settings );
	update_option( 'almaden_bookster_author_page_settings', $author_settings );
	update_option( 'almaden_bookster_publisher_page_settings', $publisher_settings );
	update_option( 'almaden_bookster_publisher_onboarding_page_settings', $publisher_onboarding_settings );
	update_option( 'almaden_bookster_quiz_page_settings', $quiz_settings );
	if ( '' !== $sync_section ) {
		almaden_bookster_sync_pages_for_section( $sync_section );
	} else {
		almaden_bookster_sync_all_pages();
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

function almaden_bookster_sync_pages_for_section( $section_key ) {
	$section_key = sanitize_key( (string) $section_key );

	switch ( $section_key ) {
		case 'shell_home':
			almaden_bookster_sync_shell_home_page();
			break;
		case 'creator':
			almaden_bookster_sync_creator_page();
			break;
		case 'dashboard':
			almaden_bookster_sync_dashboard_page();
			break;
		case 'course_creator':
			almaden_bookster_sync_course_creator_page();
			break;
		case 'course_archive':
			almaden_bookster_sync_course_archive_page();
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
			almaden_bookster_sync_store_page();
			break;
	}
}

function almaden_bookster_sync_all_pages() {
	almaden_bookster_sync_creator_page();
	almaden_bookster_sync_shell_home_page();
	almaden_bookster_sync_dashboard_page();
	almaden_bookster_sync_course_creator_page();
	almaden_bookster_sync_course_archive_page();
	almaden_bookster_sync_authors_page();
	almaden_bookster_sync_author_page();
	almaden_bookster_sync_publisher_page();
	almaden_bookster_sync_publisher_onboarding_page();
	almaden_bookster_sync_quiz_page();
	almaden_bookster_sync_store_page();
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

	return array(
		array(
			'key'         => 'shell_home',
			'heading'     => 'Almaden App',
			'description' => 'Página de entrada al shell. Si activas el checkbox, esta URL entra al menú público de WordPress.',
			'page_id_name'=> 'shell_home_page_id',
			'title_name'  => 'shell_home_title',
			'slug_name'   => 'shell_home_slug',
			'page_id'     => isset( $shared_settings['shell_home_page_id'] ) ? absint( $shared_settings['shell_home_page_id'] ) : 0,
			'title'       => isset( $shared_settings['shell_home_title'] ) ? $shared_settings['shell_home_title'] : '',
			'slug'        => isset( $shared_settings['shell_home_slug'] ) ? $shared_settings['shell_home_slug'] : '',
			'url'         => function_exists( 'almaden_bookster_get_shell_home_page_url' ) ? almaden_bookster_get_shell_home_page_url() : '',
			'status'      => function_exists( 'almaden_bookster_get_page_sync_state' ) ? almaden_bookster_get_page_sync_state( isset( $shared_settings['shell_home_page_id'] ) ? $shared_settings['shell_home_page_id'] : 0, isset( $shared_settings['shell_home_slug'] ) ? $shared_settings['shell_home_slug'] : '', isset( $shared_settings['shell_home_title'] ) ? $shared_settings['shell_home_title'] : '' ) : array(),
			'extra_fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'shell_home_menu_enabled',
					'label' => 'Activar inserción automática del Home de Almaden App en el menú público de WordPress.',
					'checked' => ! empty( $shared_settings['shell_home_menu_enabled'] ),
				),
			),
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
	);
}
