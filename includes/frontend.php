<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// --- Frontend Booklist y Creación Automática de Página ---

// 1. Crear la página física automáticamente si no existe
function almaden_bookster_create_page() {
	$page_slug = 'almaden-booklist';
	$page = get_page_by_path( $page_slug );
	
	if ( ! $page ) {
		wp_insert_post( array(
			'post_title'     => 'Almaden Booklist',
			'post_name'      => $page_slug,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_content'   => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
		) );
	}
}
add_action( 'init', 'almaden_bookster_create_page' );

// 2. Interceptar la página almaden-booklist y cargar nuestra app independiente
function almaden_bookster_load_booklist() {
	if ( is_page( 'almaden-booklist' ) && is_main_query() ) {
		// Ocultar barra de administración de WordPress
		show_admin_bar( false );
		
		$template_path = dirname( __FILE__ ) . '/../templates/booklist-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla del booklist no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_booklist', 5 );


// --- Procesar Formulario de Creación ---

function almaden_bookster_handle_create_book() {
	// Validar nonce
	if ( ! isset( $_POST['almaden_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_nonce'], 'almaden_create_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$title   = isset( $_POST['book_title'] ) ? sanitize_text_field( $_POST['book_title'] ) : '';
	$author  = isset( $_POST['book_author'] ) ? sanitize_text_field( $_POST['book_author'] ) : '';
	$content = isset( $_POST['book_content'] ) ? wp_kses_post( $_POST['book_content'] ) : '';

	if ( empty( $title ) ) {
		wp_die( 'El título es obligatorio.' );
	}

	// Crear el post
	$post_data = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'almaden-books',
		'meta_input'   => array(
			'book_author' => $author,
		),
	);

	$post_id = wp_insert_post( $post_data );

	if ( ! is_wp_error( $post_id ) ) {
		// Redirigir de vuelta con mensaje de éxito
		$redirect_url = add_query_arg( 'book_created', '1', wp_get_referer() );
		// Redireccionar al listado con un flag de éxito
		$redirect_url = home_url( '/almaden-booklist/?book_created=1' );
		wp_safe_redirect( $redirect_url );
		exit;
	} else {
		wp_die( 'Hubo un error al crear el libro.' );
	}
}
add_action( 'admin_post_almaden_create_book', 'almaden_bookster_handle_create_book' );
add_action( 'admin_post_nopriv_almaden_create_book', 'almaden_bookster_handle_create_book' );

function almaden_bookster_handle_delete_book() {
	if ( ! isset( $_POST['almaden_delete_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_delete_nonce'], 'almaden_delete_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_die( 'ID de libro inválido.' );
	}

	// Eliminar capítulos asociados
	$chapters = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $book_id,
		'posts_per_page' => -1,
		'fields'         => 'ids',
	) );
	foreach ( $chapters as $chapter_id ) {
		wp_delete_post( $chapter_id, true );
	}

	// Eliminar configuración de la tabla
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	$wpdb->delete( $table_name, array( 'book_id' => $book_id ), array( '%d' ) );

	// Eliminar el libro (post)
	wp_delete_post( $book_id, true );

	// Redireccionar al listado
	$redirect_url = home_url( '/almaden-booklist/?book_deleted=1' );
	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_almaden_delete_book', 'almaden_bookster_handle_delete_book' );
add_action( 'admin_post_nopriv_almaden_delete_book', 'almaden_bookster_handle_delete_book' );

// --- Editor BookCraft a Pantalla Completa ---

function almaden_bookster_load_editor() {
	$request_uri = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	
	if ( strpos( $request_uri, 'almaden-book-editor' ) === 0 ) {
		$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;

		// Asegurar sesión autenticada de WordPress (necesario para wp.media y async-upload.php)
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		
		// Ocultar barra de administración de WordPress en el editor
		show_admin_bar( false );
		
		// Cargar funciones de admin para que wp_enqueue_media funcione completamente
		if ( ! defined( 'DOING_AJAX' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		
		add_action('wp_enqueue_scripts', function() use ($book_id) {
			// Limpiar estilos y scripts del tema, preservando el core
			global $wp_styles, $wp_scripts;
			if (isset($wp_styles->queue)) {
				foreach ($wp_styles->queue as $handle) {
					if (strpos($handle, 'wp-') === false && strpos($handle, 'dashicons') === false) {
						wp_dequeue_style($handle);
					}
				}
			}
			if (isset($wp_scripts->queue)) {
				foreach ($wp_scripts->queue as $handle) {
					if (strpos($handle, 'wp-') === false && strpos($handle, 'media-') === false && strpos($handle, 'jquery') === false) {
						wp_dequeue_script($handle);
					}
				}
			}
			wp_enqueue_media( array( 'post' => $book_id ) );
		}, 9999);

		$template_path = dirname( __FILE__ ) . '/../templates/editor-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla del editor no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_editor', 5 );
