<?php
/**
 * Plugin Name: AlmadenBookster
 * Description: Plugin personalizado AlmadenBookster.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Text Domain: almaden-bookster
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// --- Módulos de Google Fonts (Admin) ---
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-fonts.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-fonts-page.php';

// Registrar el Custom Post Type: Libros (almaden-books)
function almaden_bookster_register_cpt_books() {
	$labels = array(
		'name'                  => _x( 'Libros', 'Post Type General Name', 'almaden-bookster' ),
		'singular_name'         => _x( 'Libro', 'Post Type Singular Name', 'almaden-bookster' ),
		'menu_name'             => __( 'Libros', 'almaden-bookster' ),
		'name_admin_bar'        => __( 'Libro', 'almaden-bookster' ),
		'archives'              => __( 'Archivos de Libros', 'almaden-bookster' ),
		'attributes'            => __( 'Atributos de Libro', 'almaden-bookster' ),
		'parent_item_colon'     => __( 'Libro Padre:', 'almaden-bookster' ),
		'all_items'             => __( 'Todos los Libros', 'almaden-bookster' ),
		'add_new_item'          => __( 'Añadir Nuevo Libro', 'almaden-bookster' ),
		'add_new'               => __( 'Añadir Nuevo', 'almaden-bookster' ),
		'new_item'              => __( 'Nuevo Libro', 'almaden-bookster' ),
		'edit_item'             => __( 'Editar Libro', 'almaden-bookster' ),
		'update_item'           => __( 'Actualizar Libro', 'almaden-bookster' ),
		'view_item'             => __( 'Ver Libro', 'almaden-bookster' ),
		'view_items'            => __( 'Ver Libros', 'almaden-bookster' ),
		'search_items'          => __( 'Buscar Libro', 'almaden-bookster' ),
		'not_found'             => __( 'No encontrado', 'almaden-bookster' ),
		'not_found_in_trash'    => __( 'No encontrado en la Papelera', 'almaden-bookster' ),
		'featured_image'        => __( 'Imagen Destacada', 'almaden-bookster' ),
		'set_featured_image'    => __( 'Establecer imagen destacada', 'almaden-bookster' ),
		'remove_featured_image' => __( 'Quitar imagen destacada', 'almaden-bookster' ),
		'use_featured_image'    => __( 'Usar como imagen destacada', 'almaden-bookster' ),
		'insert_into_item'      => __( 'Insertar en el libro', 'almaden-bookster' ),
		'uploaded_to_this_item' => __( 'Subido a este libro', 'almaden-bookster' ),
		'items_list'            => __( 'Lista de libros', 'almaden-bookster' ),
		'items_list_navigation' => __( 'Navegación de lista de libros', 'almaden-bookster' ),
		'filter_items_list'     => __( 'Filtrar lista de libros', 'almaden-bookster' ),
	);
	$args = array(
		'label'                 => __( 'Libro', 'almaden-bookster' ),
		'description'           => __( 'Libros físicos y digitales', 'almaden-bookster' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes' ),
		'taxonomies'            => array( 'category', 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-book-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => true,
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		'capability_type'       => 'post',
		'show_in_rest'          => true, // Habilita Gutenberg
	);
	register_post_type( 'almaden-books', $args );
}
add_action( 'init', 'almaden_bookster_register_cpt_books', 0 );

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

// 2. Sobreescribir el contenido de la página para mostrar el listado y formulario
function almaden_bookster_page_content( $content ) {
	if ( is_page( 'almaden-booklist' ) && in_the_loop() && is_main_query() ) {
		ob_start();

		// Mostrar mensajes de éxito/error si existen
		if ( isset( $_GET['book_created'] ) && $_GET['book_created'] == '1' ) {
			echo '<div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">Libro creado con éxito.</div>';
		}

		// Botón y Formulario de creación (Oculto por defecto)
		?>
		<div class="almaden-book-creation" style="margin-bottom: 30px;">
			<button type="button" id="almaden-toggle-book-form" style="padding: 10px 20px; background: #0073aa; color: #fff; border: none; cursor: pointer; font-weight: bold; border-radius: 4px;">CREATE BOOK</button>
			
			<div id="almaden-book-form-container" style="display: none; margin-top: 15px; background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
				<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
					<input type="hidden" name="action" value="almaden_create_book">
					<?php wp_nonce_field( 'almaden_create_book_nonce', 'almaden_nonce' ); ?>
					
					<div style="margin-bottom: 10px;">
						<label style="display: block; font-weight: bold; margin-bottom: 5px;">Título del Libro</label>
						<input type="text" name="book_title" required style="width: 100%; padding: 8px;">
					</div>
					
					<div style="margin-bottom: 10px;">
						<label style="display: block; font-weight: bold; margin-bottom: 5px;">Autor</label>
						<input type="text" name="book_author" required style="width: 100%; padding: 8px;">
					</div>
					
					<div style="margin-bottom: 10px;">
						<label style="display: block; font-weight: bold; margin-bottom: 5px;">Descripción</label>
						<textarea name="book_content" rows="4" style="width: 100%; padding: 8px;"></textarea>
					</div>
					
					<button type="submit" style="padding: 10px 20px; background: #28a745; color: #fff; border: none; cursor: pointer; border-radius: 4px;">Guardar Libro</button>
				</form>
			</div>
		</div>

		<script>
			document.getElementById('almaden-toggle-book-form').addEventListener('click', function() {
				var formContainer = document.getElementById('almaden-book-form-container');
				if (formContainer.style.display === 'none') {
					formContainer.style.display = 'block';
				} else {
					formContainer.style.display = 'none';
				}
			});
		</script>
		<?php

		// Listado de libros
		$args = array(
			'post_type'      => 'almaden-books',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$books_query = new WP_Query( $args );

		if ( $books_query->have_posts() ) {
			echo '<div class="almaden-book-list" style="display: grid; gap: 20px;">';
			while ( $books_query->have_posts() ) {
				$books_query->the_post();
				echo '<div style="padding: 15px; border: 1px solid #eee; border-radius: 4px; background: #fff;">';
				echo '<h3 style="margin-top: 0; margin-bottom: 10px;">' . esc_html( get_the_title() ) . '</h3>';
				
				$author = get_post_meta( get_the_ID(), 'book_author', true );
				if ( ! empty( $author ) ) {
					echo '<p style="margin-top: 0; margin-bottom: 10px; font-style: italic; color: #555;">Autor: ' . esc_html( $author ) . '</p>';
				}
				
				echo '<div>' . wp_kses_post( wp_trim_words( get_the_content(), 20 ) ) . '</div>';
				$editor_url = home_url( '/almaden-book-editor/?book_id=' . get_the_ID() );
				echo '<a href="' . esc_url( $editor_url ) . '" style="display: inline-block; margin-top: 10px; color: #0073aa; font-weight: bold; text-decoration: none;">Editar en BookCraft &rarr;</a>';
				echo '</div>';
			}
			echo '</div>';
			wp_reset_postdata();
		} else {
			echo '<p>No hay libros todavía.</p>';
		}

		return ob_get_clean();
	}
	
	return $content;
}
add_filter( 'the_content', 'almaden_bookster_page_content' );


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
		wp_safe_redirect( $redirect_url );
		exit;
	} else {
		wp_die( 'Hubo un error al crear el libro.' );
	}
}
// Permitir a todos procesar la petición (ajustar permisos si es necesario)
add_action( 'admin_post_almaden_create_book', 'almaden_bookster_handle_create_book' );
add_action( 'admin_post_nopriv_almaden_create_book', 'almaden_bookster_handle_create_book' );

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

		$template_path = plugin_dir_path( __FILE__ ) . 'templates/editor-app.php';
		if ( file_exists( $template_path ) ) {
			require_once $template_path;
			exit;
		} else {
			wp_die( 'Plantilla del editor no encontrada.' );
		}
	}
}
add_action( 'template_redirect', 'almaden_bookster_load_editor', 5 );

// --- AJAX Guardar Libro en Base de Datos ---

function almaden_bookster_save_book_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	// Validar nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_book_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	$title    = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
	$chapters_raw = isset( $_POST['chapters'] ) ? wp_unslash( $_POST['chapters'] ) : '';
	$chapters = json_decode( $chapters_raw, true );

	if ( ! is_array( $chapters ) ) {
		wp_send_json_error( 'Datos de capítulos inválidos.' );
	}

	// Sanitizar capítulos
	$sanitized_chapters = array();
	foreach ( $chapters as $chapter ) {
		$sanitized_chapters[] = array(
			'id'           => sanitize_text_field( $chapter['id'] ),
			'title'        => sanitize_text_field( $chapter['title'] ),
			'content'      => wp_kses_post( $chapter['content'] ),
			'parity_image' => isset( $chapter['parity_image'] ) ? sanitize_text_field( $chapter['parity_image'] ) : '',
		);
	}

	// Actualizar título del libro (post)
	if ( ! empty( $title ) ) {
		wp_update_post( array(
			'ID'         => $book_id,
			'post_title' => $title,
		) );
	}

	// Guardar los capítulos en post meta
	update_post_meta( $book_id, '_almaden_chapters', $sanitized_chapters );

	wp_send_json_success( array( 'message' => 'Libro guardado con éxito.' ) );
}
add_action( 'wp_ajax_almaden_save_book', 'almaden_bookster_save_book_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book', 'almaden_bookster_save_book_ajax' );

// --- Crear Tabla Especial de Ajustes de PDF ---

function almaden_bookster_create_settings_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';
	
	if ( get_option( 'almaden_bookster_db_version' ) !== '1.8.1' ) {
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			book_id bigint(20) NOT NULL,
			unit varchar(10) DEFAULT 'cm' NOT NULL,
			page_size varchar(20) DEFAULT 'A4' NOT NULL,
			page_width float DEFAULT 21.0 NOT NULL,
			page_height float DEFAULT 29.7 NOT NULL,
			margin_top float DEFAULT 2.5 NOT NULL,
			margin_bottom float DEFAULT 2.5 NOT NULL,
			margin_left float DEFAULT 2.0 NOT NULL,
			margin_right float DEFAULT 2.0 NOT NULL,
			margin_left_odd float DEFAULT 2.0 NOT NULL,
			margin_right_odd float DEFAULT 2.0 NOT NULL,
			margin_left_even float DEFAULT 2.0 NOT NULL,
			margin_right_even float DEFAULT 2.0 NOT NULL,
			padding_top float DEFAULT 0.0 NOT NULL,
			padding_bottom float DEFAULT 0.0 NOT NULL,
			padding_left float DEFAULT 0.0 NOT NULL,
			padding_right float DEFAULT 0.0 NOT NULL,
			bleeding float DEFAULT 0.0 NOT NULL,
			font_family_content varchar(50) DEFAULT 'Merriweather' NOT NULL,
			font_size_content float DEFAULT 11.5 NOT NULL,
			line_height_content float DEFAULT 1.65 NOT NULL,
			content_text_align varchar(20) DEFAULT 'justify' NOT NULL,
			content_hyphenation tinyint(1) DEFAULT 1 NOT NULL,
			content_language varchar(10) DEFAULT 'es' NOT NULL,
			content_paragraph_indent float DEFAULT 0.0 NOT NULL,
			content_paragraph_spacing float DEFAULT 14.0 NOT NULL,
			font_family_headings varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h1 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h2 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_family_h3 varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			font_weight_h1 varchar(20) DEFAULT 'bold' NOT NULL,
			font_weight_h2 varchar(20) DEFAULT 'bold' NOT NULL,
			font_weight_h3 varchar(20) DEFAULT 'bold' NOT NULL,
			font_size_h1 float DEFAULT 24.0 NOT NULL,
			font_size_h2 float DEFAULT 16.0 NOT NULL,
			font_size_h3 float DEFAULT 13.0 NOT NULL,
			header_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			header_font_size float DEFAULT 8.5 NOT NULL,
			header_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			header_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			header_letter_spacing float DEFAULT 0.1 NOT NULL,
			header_even_type varchar(20) DEFAULT 'book_title' NOT NULL,
			header_even_custom varchar(255) DEFAULT '' NOT NULL,
			header_odd_type varchar(20) DEFAULT 'chapter_title' NOT NULL,
			header_odd_custom varchar(255) DEFAULT '' NOT NULL,
			footer_font_family varchar(50) DEFAULT 'Merriweather' NOT NULL,
			footer_font_size float DEFAULT 9.0 NOT NULL,
			footer_font_weight varchar(20) DEFAULT 'normal' NOT NULL,
			footer_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			footer_letter_spacing float DEFAULT 0.0 NOT NULL,
			footer_even_type varchar(20) DEFAULT 'page_number' NOT NULL,
			footer_odd_type varchar(20) DEFAULT 'page_number' NOT NULL,
			show_header_page_one tinyint(1) DEFAULT 0 NOT NULL,
			chapter_start_parity varchar(10) DEFAULT 'any' NOT NULL,
			parity_image_mode varchar(20) DEFAULT 'content' NOT NULL,
			chapter_page_one_align varchar(10) DEFAULT 'center' NOT NULL,
			chapter_page_one_vertical varchar(10) DEFAULT 'top' NOT NULL,
			chapter_title_font_family varchar(50) DEFAULT 'Playfair Display' NOT NULL,
			chapter_title_font_size float DEFAULT 24.0 NOT NULL,
			chapter_title_font_weight varchar(20) DEFAULT 'bold' NOT NULL,
			chapter_title_font_style varchar(20) DEFAULT 'normal' NOT NULL,
			chapter_title_align varchar(20) DEFAULT 'center' NOT NULL,
			chapter_title_padding_top float DEFAULT 0.0 NOT NULL,
			chapter_title_padding_bottom float DEFAULT 1.5 NOT NULL,
			header_margin_top float DEFAULT 1.0 NOT NULL,
			header_margin_bottom float DEFAULT 0.5 NOT NULL,
			header_align varchar(20) DEFAULT 'center' NOT NULL,
			footer_margin_top float DEFAULT 0.5 NOT NULL,
			footer_margin_bottom float DEFAULT 1.0 NOT NULL,
			footer_align varchar(20) DEFAULT 'center' NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY book_id (book_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'almaden_bookster_db_version', '1.8.1' );
	}
}
add_action( 'init', 'almaden_bookster_create_settings_table' );

// --- AJAX Guardar Ajustes de Maquetación de PDF ---

function almaden_bookster_save_settings_ajax() {
	$book_id = isset( $_POST['book_id'] ) ? intval( $_POST['book_id'] ) : 0;
	
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'almaden_save_settings_nonce_' . $book_id ) ) {
		wp_send_json_error( 'Validación de seguridad fallida.' );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'almaden_book_settings';

	$data = array(
		'book_id'                    => $book_id,
		'unit'                       => sanitize_text_field( $_POST['unit'] ),
		'page_size'                  => sanitize_text_field( $_POST['page_size'] ),
		'page_width'                 => floatval( str_replace( ',', '.', $_POST['page_width'] ) ),
		'page_height'                => floatval( str_replace( ',', '.', $_POST['page_height'] ) ),
		'margin_top'                 => floatval( str_replace( ',', '.', $_POST['margin_top'] ) ),
		'margin_bottom'              => floatval( str_replace( ',', '.', $_POST['margin_bottom'] ) ),
		'margin_left'                => floatval( str_replace( ',', '.', $_POST['margin_left'] ) ),
		'margin_right'               => floatval( str_replace( ',', '.', $_POST['margin_right'] ) ),
		'margin_left_odd'            => floatval( str_replace( ',', '.', $_POST['margin_left_odd'] ) ),
		'margin_right_odd'           => floatval( str_replace( ',', '.', $_POST['margin_right_odd'] ) ),
		'margin_left_even'           => floatval( str_replace( ',', '.', $_POST['margin_left_even'] ) ),
		'margin_right_even'          => floatval( str_replace( ',', '.', $_POST['margin_right_even'] ) ),
		'padding_top'                => floatval( str_replace( ',', '.', $_POST['padding_top'] ) ),
		'padding_bottom'             => floatval( str_replace( ',', '.', $_POST['padding_bottom'] ) ),
		'padding_left'               => floatval( str_replace( ',', '.', $_POST['padding_left'] ) ),
		'padding_right'              => floatval( str_replace( ',', '.', $_POST['padding_right'] ) ),
		'bleeding'                   => floatval( str_replace( ',', '.', $_POST['bleeding'] ) ),
		'font_family_content'        => sanitize_text_field( $_POST['font_family_content'] ),
		'font_size_content'          => floatval( str_replace( ',', '.', $_POST['font_size_content'] ) ),
		'line_height_content'        => floatval( str_replace( ',', '.', $_POST['line_height_content'] ) ),
		'content_text_align'         => sanitize_text_field( $_POST['content_text_align'] ),
		'content_hyphenation'        => intval( $_POST['content_hyphenation'] ),
		'content_language'           => sanitize_text_field( $_POST['content_language'] ),
		'content_paragraph_indent'   => floatval( str_replace( ',', '.', $_POST['content_paragraph_indent'] ) ),
		'content_paragraph_spacing'  => floatval( str_replace( ',', '.', $_POST['content_paragraph_spacing'] ) ),
		'font_family_headings'       => sanitize_text_field( $_POST['font_family_headings'] ),
		'font_family_h1'             => sanitize_text_field( $_POST['font_family_h1'] ),
		'font_family_h2'             => sanitize_text_field( $_POST['font_family_h2'] ),
		'font_family_h3'             => sanitize_text_field( $_POST['font_family_h3'] ),
		'font_weight_h1'             => sanitize_text_field( $_POST['font_weight_h1'] ),
		'font_weight_h2'             => sanitize_text_field( $_POST['font_weight_h2'] ),
		'font_weight_h3'             => sanitize_text_field( $_POST['font_weight_h3'] ),
		'font_size_h1'               => floatval( str_replace( ',', '.', $_POST['font_size_h1'] ) ),
		'font_size_h2'               => floatval( str_replace( ',', '.', $_POST['font_size_h2'] ) ),
		'font_size_h3'               => floatval( str_replace( ',', '.', $_POST['font_size_h3'] ) ),
		'header_font_family'         => sanitize_text_field( $_POST['header_font_family'] ),
		'header_font_size'           => floatval( str_replace( ',', '.', $_POST['header_font_size'] ) ),
		'header_font_weight'         => sanitize_text_field( $_POST['header_font_weight'] ),
		'header_font_style'          => sanitize_text_field( $_POST['header_font_style'] ),
		'header_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['header_letter_spacing'] ) ),
		'header_even_type'           => sanitize_text_field( $_POST['header_even_type'] ),
		'header_even_custom'         => sanitize_text_field( $_POST['header_even_custom'] ),
		'header_odd_type'            => sanitize_text_field( $_POST['header_odd_type'] ),
		'header_odd_custom'          => sanitize_text_field( $_POST['header_odd_custom'] ),
		'footer_font_family'         => sanitize_text_field( $_POST['footer_font_family'] ),
		'footer_font_size'           => floatval( str_replace( ',', '.', $_POST['footer_font_size'] ) ),
		'footer_font_weight'         => sanitize_text_field( $_POST['footer_font_weight'] ),
		'footer_font_style'          => sanitize_text_field( $_POST['footer_font_style'] ),
		'footer_letter_spacing'      => floatval( str_replace( ',', '.', $_POST['footer_letter_spacing'] ) ),
		'footer_even_type'           => sanitize_text_field( $_POST['footer_even_type'] ),
		'footer_odd_type'            => sanitize_text_field( $_POST['footer_odd_type'] ),
		'show_header_page_one'       => intval( $_POST['show_header_page_one'] ),
		'chapter_start_parity'       => sanitize_text_field( $_POST['chapter_start_parity'] ),
		'parity_image_mode'          => sanitize_text_field( $_POST['parity_image_mode'] ),
		'chapter_page_one_align'     => sanitize_text_field( $_POST['chapter_page_one_align'] ),
		'chapter_page_one_vertical'  => sanitize_text_field( $_POST['chapter_page_one_vertical'] ),
		'chapter_title_font_family'  => sanitize_text_field( $_POST['chapter_title_font_family'] ),
		'chapter_title_font_size'    => floatval( str_replace( ',', '.', $_POST['chapter_title_font_size'] ) ),
		'chapter_title_font_weight'  => sanitize_text_field( $_POST['chapter_title_font_weight'] ),
		'chapter_title_font_style'   => sanitize_text_field( $_POST['chapter_title_font_style'] ),
		'chapter_title_align'        => sanitize_text_field( $_POST['chapter_title_align'] ),
		'chapter_title_padding_top'  => floatval( str_replace( ',', '.', $_POST['chapter_title_padding_top'] ) ),
		'chapter_title_padding_bottom'=> floatval( str_replace( ',', '.', $_POST['chapter_title_padding_bottom'] ) ),
		'header_margin_top'          => floatval( str_replace( ',', '.', $_POST['header_margin_top'] ) ),
		'header_margin_bottom'       => floatval( str_replace( ',', '.', $_POST['header_margin_bottom'] ) ),
		'header_align'               => sanitize_text_field( $_POST['header_align'] ),
		'footer_margin_top'          => floatval( str_replace( ',', '.', $_POST['footer_margin_top'] ) ),
		'footer_margin_bottom'       => floatval( str_replace( ',', '.', $_POST['footer_margin_bottom'] ) ),
		'footer_align'               => sanitize_text_field( $_POST['footer_align'] ),
	);

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE book_id = %d", $book_id ) );

	if ( $exists ) {
		$result = $wpdb->update( $table_name, $data, array( 'book_id' => $book_id ) );
	} else {
		$result = $wpdb->insert( $table_name, $data );
	}

	if ( false !== $result ) {
		wp_send_json_success( array( 'message' => 'Configuración de maquetación guardada con éxito.' ) );
	} else {
		wp_send_json_error( 'Error al guardar la configuración.' );
	}
}
add_action( 'wp_ajax_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );
add_action( 'wp_ajax_nopriv_almaden_save_book_settings', 'almaden_bookster_save_settings_ajax' );


