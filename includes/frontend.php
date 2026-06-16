<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// --- Frontend Booklist y Creación Automática de Página ---

// 1. Crear la página física automáticamente si no existe
function almaden_bookster_create_page() {
	$pages_to_create = [
		'almaden-booklist' => 'Almaden Booklist',
		'bookshelf'        => 'Bookshelf',
	];

	foreach ( $pages_to_create as $page_slug => $page_title ) {
		$page = get_page_by_path( $page_slug );
		if ( ! $page ) {
			wp_insert_post( array(
				'post_title'     => $page_title,
				'post_name'      => $page_slug,
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_content'   => '<!-- El contenido de esta página es generado dinámicamente por el plugin AlmadenBookster -->',
			) );
		}
	}
}
add_action( 'init', 'almaden_bookster_create_page' );

// 2. Interceptar la página almaden-booklist para cargar nuestra app independiente
function almaden_bookster_load_booklist() {
	if ( is_page( 'almaden-booklist' ) && is_main_query() ) {
		// Ocultar barra de administración de WordPress
		show_admin_bar( false );
		
		if ( ! defined( 'DOING_AJAX' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		
		wp_enqueue_media();

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

// 3. Renderizar el Bookshelf dentro del contenido de la página para respetar el Theme
function almaden_bookster_render_bookshelf( $content ) {
	if ( is_page( 'bookshelf' ) && in_the_loop() && is_main_query() ) {
		ob_start();
		$template_path = dirname( __FILE__ ) . '/../templates/bookshelf-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
		}
		return ob_get_clean();
	}
	return $content;
}
add_filter( 'the_content', 'almaden_bookster_render_bookshelf' );

// 4. Interceptar la vista individual de un libro publicado para cargar el Reader App
function almaden_bookster_load_reader( $template ) {
	if ( is_singular( 'almaden-books' ) ) {
		// Ocultar barra de administración de WordPress
		show_admin_bar( false );
		$new_template = dirname( __FILE__ ) . '/../templates/reader-app.php';
		if ( file_exists( $new_template ) ) {
			return $new_template;
		}
	}
	return $template;
}
add_filter( 'single_template', 'almaden_bookster_load_reader' );


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

// --- Duplicar Libro ---
function almaden_bookster_handle_duplicate_book() {
	if ( ! isset( $_POST['almaden_duplicate_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_duplicate_nonce'], 'almaden_duplicate_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_die( 'ID de libro inválido.' );
	}

	$original_book = get_post( $book_id );
	if ( ! $original_book || $original_book->post_type !== 'almaden-books' ) {
		wp_die( 'Libro no encontrado.' );
	}

	// Determine the real source book ID. If the original book is already a duplicate, use its source.
	$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
	if ( empty( $source_book_id ) ) {
		$source_book_id = $book_id;
	}

	// Crear el nuevo post
	$new_title = $original_book->post_title . ' (Copia)';
	$post_data = array(
		'post_title'   => $new_title,
		'post_content' => $original_book->post_content,
		'post_status'  => 'publish',
		'post_type'    => 'almaden-books',
		'meta_input'   => array(
			'book_author' => get_post_meta( $book_id, 'book_author', true ),
			'_almaden_source_book_id' => $source_book_id,
		),
	);

	$new_book_id = wp_insert_post( $post_data );

	if ( ! is_wp_error( $new_book_id ) ) {
		// Copiar portada
		$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
		if ( $cover_settings ) {
			update_post_meta( $new_book_id, '_almaden_cover_settings', $cover_settings );
		}
		
		// Copiar ajustes PDF
		global $wpdb;
		$settings_table = $wpdb->prefix . 'almaden_book_settings';
		$db_settings = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $settings_table WHERE book_id = %d", $book_id ), ARRAY_A );
		if ( $db_settings ) {
			$db_settings['book_id'] = $new_book_id;
			unset( $db_settings['id'] ); // Remueve primary key para insertar nuevo
			$wpdb->insert( $settings_table, $db_settings );
		}

		$redirect_url = home_url( '/almaden-booklist/?book_duplicated=1' );
		wp_safe_redirect( $redirect_url );
		exit;
	} else {
		wp_die( 'Hubo un error al duplicar el libro.' );
	}
}
add_action( 'admin_post_almaden_duplicate_book', 'almaden_bookster_handle_duplicate_book' );
add_action( 'admin_post_nopriv_almaden_duplicate_book', 'almaden_bookster_handle_duplicate_book' );

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
		
		wp_enqueue_media( array( 'post' => $book_id ) );

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

// --- Editor de Portadas (Cover) ---

function almaden_bookster_load_cover_editor() {
	$request_uri = trim( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), '/' );
	
	if ( strpos( $request_uri, 'almaden-book-cover' ) === 0 ) {
		$book_id = isset( $_GET['book_id'] ) ? intval( $_GET['book_id'] ) : 0;

		// Asegurar sesión autenticada de WordPress
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		
		// Ocultar barra de administración de WordPress en el editor
		show_admin_bar( false );
		
		// Cargar wp.media para el selector de imágenes de la portada
		if ( ! defined( 'DOING_AJAX' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		
		wp_enqueue_media( array( 'post' => $book_id ) );

		$cover_settings = get_post_meta( $book_id, '_almaden_cover_settings', true );
		if ( ! is_array( $cover_settings ) ) {
			$cover_settings = array();
		}
		$cover_nonce = wp_create_nonce( 'almaden_save_cover_nonce_' . $book_id );
		
		// Load installed fonts
		$installed_fonts = get_option( 'almaden_fonts_library', array() );
		
		$page_width = get_post_meta( $book_id, '_almaden_page_width', true );
		$page_height = get_post_meta( $book_id, '_almaden_page_height', true );
		if ( empty( $page_width ) ) $page_width = 14;
		if ( empty( $page_height ) ) $page_height = 21;
		
		$total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
		if ( empty( $total_pages ) ) $total_pages = 0;
		
		$template_path = dirname( __FILE__ ) . '/../templates/cover-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla de portada no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_cover_editor', 5 );
