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
