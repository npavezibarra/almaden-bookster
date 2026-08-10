<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Las páginas del shell se crean solo mediante la acción explícita en Bookster > Pages.

function almaden_bookster_load_shell_home() {
	if ( is_page( almaden_bookster_get_shell_home_slug() ) && is_main_query() ) {
		show_admin_bar( false );

		$template_path = dirname( __FILE__ ) . '/../templates/shell/shell-home-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( 'Plantilla del Home del shell no encontrada.' );
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_shell_home', 5 );

function almaden_bookster_load_quiz_builder() {
	$quiz_slug = function_exists( 'almaden_bookster_get_quiz_page_slug' ) ? almaden_bookster_get_quiz_page_slug() : 'almaden-book-quiz';
	if ( ! is_page( $quiz_slug ) || ! is_main_query() ) {
		return;
	}

	show_admin_bar( false );

	$template_path = dirname( __FILE__ ) . '/../templates/quiz-builder/quiz-builder-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla del creador de quizzes no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_quiz_builder', 5 );

function almaden_bookster_redirect_legacy_learni_routes() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );

	if ( '' === $request_path ) {
		return;
	}

	if ( 'learni-creator' === $request_path ) {
		$target = function_exists( 'almaden_bookster_get_course_creator_page_url' ) ? almaden_bookster_get_course_creator_page_url() : home_url( '/' );
		wp_safe_redirect( $target, 301 );
		exit;
	}

	if ( 'learni-quiz-editor' === $request_path ) {
		$query_args = array();
		if ( isset( $_GET['course_id'] ) ) {
			$query_args['course_id'] = absint( $_GET['course_id'] );
		}
		if ( isset( $_GET['quiz_id'] ) ) {
			$query_args['quiz_id'] = absint( $_GET['quiz_id'] );
		}
		if ( isset( $_GET['tab'] ) ) {
			$query_args['tab'] = sanitize_key( (string) wp_unslash( $_GET['tab'] ) );
		}

		if ( empty( $query_args['tab'] ) ) {
			$query_args['tab'] = 'evaluacion';
		}

		$target = function_exists( 'almaden_bookster_get_quiz_page_url' ) ? almaden_bookster_get_quiz_page_url() : home_url( '/' );
		if ( ! empty( $query_args ) ) {
			$target = add_query_arg( $query_args, $target );
		}
		wp_safe_redirect( $target, 301 );
		exit;
	}
}
add_action( 'template_redirect', 'almaden_bookster_redirect_legacy_learni_routes', 1 );

function almaden_bookster_load_dashboard() {
	if ( is_page( almaden_bookster_get_dashboard_slug() ) && is_main_query() ) {
		show_admin_bar( false );

		$template_path = dirname( __FILE__ ) . '/../templates/dashboard/dashboard-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( 'Plantilla del dashboard no encontrada.' );
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_dashboard', 5 );

// 2. Interceptar la página configurada para cargar nuestra app independiente
function almaden_bookster_load_booklist() {
	if ( is_page( almaden_bookster_get_creator_slug() ) && is_main_query() ) {
		$is_auth_modal_view = isset( $_GET['pl_auth_view'] ) && in_array( sanitize_key( (string) wp_unslash( $_GET['pl_auth_view'] ) ), array( 'login', 'register' ), true );
		if ( ! is_user_logged_in() && ! $is_auth_modal_view ) {
			auth_redirect();
		}

		if ( is_user_logged_in() && ! almaden_bookster_user_can_manage_books() ) {
			wp_die( 'No tienes permisos para acceder al taller de libros.' );
		}

		// Ocultar barra de administración de WordPress
		show_admin_bar( false );
		
		if ( ! defined( 'DOING_AJAX' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		
		wp_enqueue_media();

		$template_path = dirname( __FILE__ ) . '/../templates/admin/booklist-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla del booklist no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_booklist', 5 );

// 2b. Interceptar la página de cursos para cargar la app interna de Learni.
function almaden_bookster_load_course_creator() {
	if ( is_page( almaden_bookster_get_course_creator_slug() ) && is_main_query() ) {
		$is_auth_modal_view = isset( $_GET['pl_auth_view'] ) && in_array( sanitize_key( (string) wp_unslash( $_GET['pl_auth_view'] ) ), array( 'login', 'register' ), true );
		if ( ! is_user_logged_in() && ! $is_auth_modal_view ) {
			auth_redirect();
		}

		if ( is_user_logged_in() && ( function_exists( 'almaden_bookster_user_can_manage_courses' ) ? ! almaden_bookster_user_can_manage_courses() : ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_almaden_learni' ) && ! current_user_can( 'edit_posts' ) ) ) ) {
			wp_die( 'No tienes permisos para acceder al creador de cursos.' );
		}

		show_admin_bar( false );

		$template_path = dirname( __FILE__ ) . '/../modules/learni/templates/dashboard/course-dashboard-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( 'Plantilla del creador de cursos no encontrada.' );
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_course_creator', 5 );

// 2c. Interceptar la página de archivo de cursos para cargar la app pública de Learni.
function almaden_bookster_load_course_archive() {
	if ( is_page( almaden_bookster_get_course_archive_slug() ) && is_main_query() ) {
		show_admin_bar( false );
		$css_path = dirname( __FILE__ ) . '/../modules/learni/assets/dashboard/course-dashboard.css';
		$css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : ALMADEN_BOOKSTER_LEARNI_VERSION;
		wp_enqueue_style( 'almaden-bookster-learni-dashboard', ALMADEN_BOOKSTER_LEARNI_PLUGIN_URL . 'assets/dashboard/course-dashboard.css', array(), $css_ver );

		$template_path = dirname( __FILE__ ) . '/../modules/learni/templates/archive/course-archive-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		}

		wp_die( 'Plantilla del archivo de cursos no encontrada.' );
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_course_archive', 5 );

function almaden_bookster_load_blog_creator() {
	if ( ! is_page( function_exists( 'almaden_bookster_get_blog_creator_slug' ) ? almaden_bookster_get_blog_creator_slug() : 'blog-editor' ) || ! is_main_query() ) {
		return;
	}

	$is_auth_modal_view = isset( $_GET['pl_auth_view'] ) && in_array( sanitize_key( (string) wp_unslash( $_GET['pl_auth_view'] ) ), array( 'login', 'register' ), true );
	if ( ! is_user_logged_in() && ! $is_auth_modal_view ) {
		auth_redirect();
	}

	if ( is_user_logged_in() && ( function_exists( 'almaden_bookster_user_can_manage_blog_posts' ) ? ! almaden_bookster_user_can_manage_blog_posts() : ! current_user_can( 'edit_posts' ) ) ) {
		wp_die( 'No tienes permisos para acceder al editor de blog.' );
	}

	show_admin_bar( false );
	wp_enqueue_media();

	$template_path = dirname( __FILE__ ) . '/../modules/blog-post/templates/blog-post-app.php';
	if ( file_exists( $template_path ) ) {
		require_once $template_path;
		exit;
	}

	wp_die( 'Plantilla del editor de blog no encontrada.' );
}
add_action( 'template_redirect', 'almaden_bookster_load_blog_creator', 5 );

// 3. Interceptar la página del catálogo público para cargar nuestra app independiente
function almaden_bookster_load_bookshelf( $template ) {
	if ( is_page( almaden_bookster_get_store_slug() ) && is_main_query() ) {
		show_admin_bar( false );

		$template_path = dirname( __FILE__ ) . '/../templates/bookshelf/bookshelf-app.php';
		if ( file_exists( $template_path ) ) {
			return $template_path;
		}

		wp_die( 'Plantilla del bookshelf no encontrada.' );
	}

	return $template;
}
add_filter( 'template_include', 'almaden_bookster_load_bookshelf', 20 );

// 4. Interceptar la vista individual de un libro publicado para cargar el Reader App
function almaden_bookster_load_reader( $template ) {
	if ( is_singular( 'almaden-books' ) ) {
		// Ocultar barra de administración de WordPress
		show_admin_bar( false );
		$new_template = dirname( __FILE__ ) . '/../templates/ebook/ebook-single-app.php';
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
		'post_author'  => get_current_user_id(),
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => 'almaden-books',
		'meta_input'   => array(
			'book_author' => $author,
			'_almaden_book_author' => $author,
		),
	);

	// Procesar formatos seleccionados (Ebook, Impreso)
	if ( isset( $_POST['almaden_book_format'] ) && is_array( $_POST['almaden_book_format'] ) ) {
		$formats = array_map( 'sanitize_text_field', $_POST['almaden_book_format'] );
		$post_data['meta_input']['_almaden_formats'] = $formats;
	}

	// Procesar tamaño de impresión si fue especificado
	$page_width = null;
	$page_height = null;
	
	if ( isset( $_POST['almaden_book_size'] ) ) {
		$size = sanitize_text_field( $_POST['almaden_book_size'] );
		$post_data['meta_input']['_almaden_book_size'] = $size;
		
		if ( $size === '14x21' ) {
			$page_width = 14.0;
			$page_height = 21.0;
		} elseif ( $size === '15x23' ) {
			$page_width = 15.0;
			$page_height = 23.0;
		} elseif ( $size === 'custom' ) {
			if ( isset( $_POST['almaden_custom_width'] ) ) {
				$page_width = floatval( $_POST['almaden_custom_width'] );
			}
			if ( isset( $_POST['almaden_custom_height'] ) ) {
				$page_height = floatval( $_POST['almaden_custom_height'] );
			}
		}
	}

	$post_id = wp_insert_post( $post_data );

	if ( ! is_wp_error( $post_id ) ) {
		if ( function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
			almaden_bookster_sync_book_authors_from_input( $post_id, $author );
		}

		// Insertar tamaño inicial en almaden_book_settings si es impreso
		if ( $page_width && $page_height ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'almaden_book_settings';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
				$wpdb->insert(
					$table_name,
					array(
						'book_id'     => $post_id,
						'page_size'   => ( $size === 'custom' ) ? 'Custom' : $size,
						'page_width'  => $page_width,
						'page_height' => $page_height,
					)
				);
			}
		}

		if ( function_exists( 'almaden_bookster_mark_publisher_tour_completed' ) ) {
			almaden_bookster_mark_publisher_tour_completed();
		}

		// Redirigir de vuelta con mensaje de éxito
		$redirect_url = almaden_bookster_get_creator_page_url( array( 'book_created' => '1' ) );
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
	$redirect_url = almaden_bookster_get_creator_page_url( array( 'book_deleted' => '1' ) );
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
		'post_author'  => get_current_user_id(),
		'post_title'   => $new_title,
		'post_content' => $original_book->post_content,
		'post_status'  => 'publish',
		'post_type'    => 'almaden-books',
		'meta_input'   => array(
			'book_author' => get_post_meta( $book_id, 'book_author', true ),
			'_almaden_book_author' => get_post_meta( $book_id, '_almaden_book_author', true ),
			'_almaden_source_book_id' => $source_book_id,
		),
	);

	$new_book_id = wp_insert_post( $post_data );

	if ( ! is_wp_error( $new_book_id ) ) {
		if ( function_exists( 'almaden_bookster_set_book_authors' ) ) {
			$existing_authors = function_exists( 'almaden_bookster_get_book_authors' ) ? almaden_bookster_get_book_authors( $book_id ) : array();
			if ( ! empty( $existing_authors ) ) {
				almaden_bookster_set_book_authors( $new_book_id, $existing_authors, get_post_meta( $book_id, 'book_author', true ) );
			} else {
				$legacy_label = get_post_meta( $book_id, 'book_author', true );
				if ( '' === trim( (string) $legacy_label ) ) {
					$legacy_label = get_post_meta( $book_id, '_almaden_book_author', true );
				}
				almaden_bookster_sync_book_authors_from_input( $new_book_id, $legacy_label );
			}
		}

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

		if ( function_exists( 'almaden_bookster_clear_book_wc_relation' ) ) {
			almaden_bookster_clear_book_wc_relation( $new_book_id );
		}

		$redirect_url = almaden_bookster_get_creator_page_url( array( 'book_duplicated' => '1' ) );
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

		$template_path = dirname( __FILE__ ) . '/../templates/editor/editor-app.php';
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
		if ( function_exists( 'almaden_bookster_prepare_cover_settings_for_editor' ) ) {
			$cover_settings = almaden_bookster_prepare_cover_settings_for_editor( $cover_settings );
		}
		$cover_nonce = wp_create_nonce( 'almaden_save_cover_nonce_' . $book_id );
		$cover_export_nonce = wp_create_nonce( 'almaden_export_cover_pdf_' . $book_id );
		
		// Load installed fonts
		$installed_fonts = get_option( 'almaden_fonts_library', array() );
		
		$page_width = get_post_meta( $book_id, '_almaden_page_width', true );
		$page_height = get_post_meta( $book_id, '_almaden_page_height', true );
		if ( empty( $page_width ) ) $page_width = 14;
		if ( empty( $page_height ) ) $page_height = 21;
		
		$total_pages = get_post_meta( $book_id, '_almaden_total_pages', true );
		if ( empty( $total_pages ) ) $total_pages = 0;
		
		$template_path = dirname( __FILE__ ) . '/../templates/cover/cover-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla de portada no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_cover_editor', 5 );
