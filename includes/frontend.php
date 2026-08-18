<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Las páginas del shell se crean solo mediante la acción explícita en Bookster > Pages.

function almaden_bookster_load_shell_home() {
	if ( is_page( almaden_bookster_get_shell_home_slug() ) && is_main_query() ) {
		show_admin_bar( false );
		if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'shell_home' ) ) {
			return;
		}

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
	if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'quiz_builder' ) ) {
		return;
	}

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
		if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'dashboard' ) ) {
			return;
		}

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
		if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'creator' ) ) {
			return;
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
		if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'course_creator' ) ) {
			return;
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
		if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'course_archive' ) ) {
			return;
		}
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

	if ( function_exists( 'almaden_bookster_maybe_render_shell_page_access' ) && ! almaden_bookster_maybe_render_shell_page_access( 'blog_creator' ) ) {
		return;
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

// 4. Interceptar la vista individual de un libro publicado para cargar la ficha pública del ebook
function almaden_bookster_load_ebook_single( $template ) {
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
add_filter( 'single_template', 'almaden_bookster_load_ebook_single' );

function almaden_bookster_seed_chapter_content( $content ) {
	$content = trim( (string) $content );
	if ( '' === $content ) {
		return '';
	}

	// The editor stores markdown-ish raw text, not HTML. Strip accidental
	// tags from template seeds while keeping the plain text and markdown
	// markers such as **bold** or ## headings intact.
	if ( false !== strpos( $content, '<' ) ) {
		$content = preg_replace( '/<\s*br\s*\/?\s*>/i', "\n", $content );
		$content = preg_replace( '/<\/\s*(p|div|h[1-6]|li|blockquote)\s*>/i', "\n", $content );
		$content = wp_strip_all_tags( $content, true );
	}

	$content = preg_replace( "/\r\n?/", "\n", $content );
	$content = preg_replace( "/\n{3,}/", "\n\n", $content );

	return trim( $content );
}

function almaden_bookster_get_book_template_payload_for_seed( $template_key = '', $template_label = '' ) {
	if ( ! function_exists( 'almaden_bookster_collect_book_template_files' ) || ! function_exists( 'almaden_bookster_normalize_book_template_payload' ) ) {
		return null;
	}

	$requested_keys = array_filter(
		array_unique(
			array(
				sanitize_title( $template_key ),
				sanitize_title( $template_label ),
				sanitize_title( str_replace( array( '-', '_' ), ' ', $template_key ) ),
				sanitize_title( str_replace( array( '-', '_' ), ' ', $template_label ) ),
			)
		)
	);

	$templates = array();
	foreach ( almaden_bookster_collect_book_template_files() as $entry ) {
		if ( empty( $entry['path'] ) || ! file_exists( $entry['path'] ) ) {
			continue;
		}

		$content = file_get_contents( $entry['path'] );
		if ( false === $content || '' === trim( $content ) ) {
			continue;
		}

		$json = json_decode( $content, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			continue;
		}

		$normalized = almaden_bookster_normalize_book_template_payload(
			$json,
			$entry['source'] ?? 'builtin',
			basename( $entry['path'], '.json' )
		);
		if ( $normalized ) {
			$templates[] = $normalized;
		}
	}

	if ( empty( $templates ) ) {
		return null;
	}

	if ( empty( $requested_keys ) ) {
		return $templates[0];
	}

	foreach ( $templates as $template ) {
		$candidate_keys = array_filter(
			array_unique(
				array(
					sanitize_title( (string) ( $template['id'] ?? '' ) ),
					sanitize_title( (string) ( $template['name'] ?? '' ) ),
					sanitize_title( (string) ( $template['description'] ?? '' ) ),
				)
			)
		);

		foreach ( $requested_keys as $requested_key ) {
			if ( in_array( $requested_key, $candidate_keys, true ) ) {
				return $template;
			}
		}
	}

	foreach ( $templates as $template ) {
		$candidate_haystacks = array_filter(
			array(
				strtolower( (string) ( $template['id'] ?? '' ) ),
				strtolower( (string) ( $template['name'] ?? '' ) ),
				strtolower( (string) ( $template['description'] ?? '' ) ),
			)
		);

		foreach ( $requested_keys as $requested_key ) {
			foreach ( $candidate_haystacks as $haystack ) {
				if ( '' !== $requested_key && false !== strpos( $haystack, $requested_key ) ) {
					return $template;
				}
			}
		}
	}

	return null;
}

function almaden_bookster_get_default_book_seed_chapters( $template_key = '', $template_label = '', $author_label = '' ) {
	$template = almaden_bookster_get_book_template_payload_for_seed( $template_key, $template_label );
	$sample_chapters = array();
	if ( $template && isset( $template['sample_chapters'] ) && is_array( $template['sample_chapters'] ) ) {
		$sample_chapters = $template['sample_chapters'];
	}

	if ( ! empty( $sample_chapters ) ) {
		$seed_chapters = array();
		foreach ( $sample_chapters as $sample_chapter ) {
			if ( ! is_array( $sample_chapter ) ) {
				continue;
			}

			$type = sanitize_key( $sample_chapter['type'] ?? 'chapter' );
			$title = sanitize_text_field( $sample_chapter['title'] ?? '' );
			$raw_content = '';
			if ( isset( $sample_chapter['content'] ) ) {
				$raw_content = (string) $sample_chapter['content'];
			} elseif ( isset( $sample_chapter['raw_text'] ) ) {
				$raw_content = (string) $sample_chapter['raw_text'];
			}

			if ( '' === $title ) {
				if ( 'credits' === $type ) {
					$title = 'Créditos';
				} elseif ( 'toc' === $type ) {
					$title = 'Índice';
				} else {
					$title = 'Introducción';
				}
			}

			$meta = array(
				'is_toc'                  => 'toc' === $type ? '1' : '0',
				'is_credits'              => 'credits' === $type ? '1' : '0',
				'exclude_from_numbering'   => ( 'toc' === $type || 'credits' === $type ) ? '1' : '0',
				'hide_header'             => ( 'toc' === $type || 'credits' === $type ) ? '1' : '0',
				'hide_footer'             => ( 'toc' === $type || 'credits' === $type ) ? '1' : '0',
				'hide_all_headers_footers' => ( 'toc' === $type || 'credits' === $type ) ? '1' : '0',
			);

			if ( 'toc' === $type ) {
				$meta['toc_hide_header'] = '1';
				$meta['toc_hide_page_numbers'] = '1';
				$meta['toc_page_number_offset'] = '-0.8';
				$meta['toc_leader_position'] = 'bottom';
				$meta['toc_leader_thickness'] = '0.35';
				$meta['toc_leader_min_width'] = '4';
			}

			if ( 'credits' === $type ) {
				$meta['credits_hide_header'] = '1';
				$meta['credits_hide_page_number'] = '0';
				$meta['hide_footer'] = '0';
				$meta['hide_all_headers_footers'] = '0';
			}

			if ( 'credits' === $type ) {
				$meta['credits_author_label'] = sanitize_text_field( (string) $author_label );
			}

			$seed_chapters[] = array(
				'title'   => $title,
				'content' => almaden_bookster_seed_chapter_content( $raw_content ),
				'meta'    => $meta,
			);
		}

		if ( ! empty( $seed_chapters ) ) {
			return $seed_chapters;
		}
	}

	return array(
		array(
			'title'   => 'Créditos',
			'content' => '',
			'meta'    => array(
				'is_toc'                  => '0',
				'is_credits'              => '1',
				'exclude_from_numbering'   => '1',
				'hide_header'             => '1',
				'hide_footer'             => '0',
				'hide_all_headers_footers' => '0',
				'credits_hide_header'      => '1',
				'credits_hide_page_number' => '0',
				'credits_author_label'     => sanitize_text_field( (string) $author_label ),
			),
		),
		array(
			'title'   => 'Índice',
			'content' => '',
			'meta'    => array(
				'is_toc'                  => '1',
				'is_credits'              => '0',
				'exclude_from_numbering'   => '1',
				'hide_header'             => '1',
				'hide_footer'             => '1',
				'hide_all_headers_footers' => '1',
				'toc_hide_header'         => '1',
				'toc_hide_page_numbers'   => '1',
				'toc_page_number_offset'  => '-0.8',
				'toc_leader_position'     => 'bottom',
				'toc_leader_thickness'    => '0.35',
				'toc_leader_min_width'    => '4',
			),
		),
		array(
			'title'   => 'Introducción',
			'content' => almaden_bookster_seed_chapter_content(
				'Este es el **capítulo de muestra** del documento. Su propósito es servir como un espacio de prueba para visualizar cómo aparecerá el contenido en la versión final.'
				. "\n\n"
				. 'Puedes **editar, agregar o eliminar texto directamente aquí**, y los cambios que realices se verán reflejados en el **PDF**. De esta manera, puedes experimentar con el contenido y comprobar fácilmente cómo quedará antes de generar la versión definitiva del documento.'
				. "\n\n"
				. '## Prueba de edición'
				. "\n\n"
				. 'Modifica este párrafo, cambia algunas palabras o agrega nuevas ideas. Una vez realizados los cambios, podrás ver cómo el contenido actualizado aparece reflejado en el PDF.'
			),
			'meta'    => array(
				'is_toc'                  => '0',
				'is_credits'              => '0',
				'exclude_from_numbering'   => '0',
				'hide_header'             => '0',
				'hide_footer'             => '0',
				'hide_all_headers_footers' => '0',
			),
		),
	);
}

function almaden_bookster_seed_default_credits_config_for_book( $book_id, $author_label = '', $publication_date = '' ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return new WP_Error( 'invalid_book', 'ID de libro inválido.' );
	}

	if ( ! function_exists( 'almaden_bookster_build_default_credits_seed_config' ) || ! function_exists( 'almaden_bookster_store_credits_config' ) ) {
		return new WP_Error( 'missing_credits_dependencies', 'No están disponibles las funciones de créditos para inicializar el libro.' );
	}

	$config = almaden_bookster_build_default_credits_seed_config( $author_label, $publication_date );
	return almaden_bookster_store_credits_config( $book_id, $config );
}

function almaden_bookster_create_seed_chapters_for_book( $book_id, $template_key = '', $template_label = '', $author_label = '' ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return new WP_Error( 'invalid_book', 'ID de libro inválido.' );
	}

	$seeds = almaden_bookster_get_default_book_seed_chapters( $template_key, $template_label, $author_label );
	if ( empty( $seeds ) ) {
		return new WP_Error( 'empty_seed', 'No hay capítulos base para crear.' );
	}

	$existing = get_posts( array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $book_id,
		'posts_per_page' => 1,
		'orderby'        => 'menu_order',
		'order'          => 'DESC',
		'fields'         => 'ids',
	) );
	$menu_order = ! empty( $existing ) ? ( intval( get_post_field( 'menu_order', $existing[0] ) ) + 1 ) : 1;
	$created = array();

	foreach ( $seeds as $seed ) {
		if ( ! is_array( $seed ) ) {
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'   => sanitize_text_field( $seed['title'] ?? 'Capítulo' ),
				'post_content' => isset( $seed['content'] ) ? (string) $seed['content'] : '',
				'post_status'  => 'publish',
				'post_type'    => 'book_chapter',
				'post_parent'  => $book_id,
				'menu_order'   => $menu_order++,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			continue;
		}

		$created[] = $post_id;
		$meta = isset( $seed['meta'] ) && is_array( $seed['meta'] ) ? $seed['meta'] : array();
		foreach ( $meta as $meta_key => $meta_value ) {
			update_post_meta( $post_id, '_' . ltrim( sanitize_key( $meta_key ), '_' ), sanitize_text_field( $meta_value ) );
		}
	}

	if ( empty( $created ) ) {
		return new WP_Error( 'seed_chapters_failed', 'No se pudieron crear los capítulos base del libro.' );
	}

	return $created;
}

function almaden_bookster_seed_book_settings_for_book( $book_id, $template_key = '', $template_label = '', $size = '', array $size_map = array(), $page_width = null, $page_height = null, $template_payload = null ) {
	global $wpdb;

	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return new WP_Error( 'invalid_book', 'ID de libro inválido.' );
	}

	$table_name = $wpdb->prefix . 'almaden_book_settings';
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
		return new WP_Error( 'missing_table', 'La tabla de ajustes del libro no existe.' );
	}

	$template = is_array( $template_payload )
		? $template_payload
		: almaden_bookster_get_book_template_payload_for_seed( $template_key, $template_label );
	$template_settings = ( $template && isset( $template['settings'] ) && is_array( $template['settings'] ) ) ? $template['settings'] : array();
	$defaults = function_exists( 'almaden_get_book_pdf_settings' ) ? almaden_get_book_pdf_settings( $book_id ) : array();

	$page_size_value = '';
	if ( '' !== $size && isset( $size_map[ $size ] ) ) {
		// Wizard formats use explicit dimensions. Keep the database value short and
		// canonical; display labels such as "Novela (13.3 x 20.3 cm)" exceed the
		// legacy VARCHAR(20) column and make the complete settings insert fail.
		$page_size_value = 'Custom';
	}
	if ( '' === $page_size_value && isset( $template_settings['page_size'] ) ) {
		$page_size_value = (string) $template_settings['page_size'];
	}
	if ( '' === $page_size_value ) {
		$page_size_value = isset( $defaults['page_size'] ) ? (string) $defaults['page_size'] : 'A4';
	}

	$page_width_value = null;
	$page_height_value = null;
	if ( null !== $page_width && null !== $page_height ) {
		$page_width_value = (float) $page_width;
		$page_height_value = (float) $page_height;
	} elseif ( isset( $template_settings['page_width'] ) && isset( $template_settings['page_height'] ) ) {
		$page_width_value = (float) $template_settings['page_width'];
		$page_height_value = (float) $template_settings['page_height'];
	} else {
		$page_width_value = isset( $defaults['page_width'] ) ? (float) $defaults['page_width'] : 21.0;
		$page_height_value = isset( $defaults['page_height'] ) ? (float) $defaults['page_height'] : 29.7;
	}

	$settings_row = array();
	$columns = $wpdb->get_col( "SHOW COLUMNS FROM $table_name", 0 );
	if ( empty( $columns ) ) {
		return new WP_Error( 'missing_columns', 'No se pudieron leer las columnas de ajustes del libro.' );
	}

	foreach ( $columns as $column ) {
		if ( 'id' === $column ) {
			continue;
		}

		if ( 'book_id' === $column ) {
			$settings_row['book_id'] = $book_id;
			continue;
		}

		if ( 'page_size' === $column ) {
			$settings_row['page_size'] = $page_size_value;
			continue;
		}

		if ( 'page_width' === $column ) {
			$settings_row['page_width'] = $page_width_value;
			continue;
		}

		if ( 'page_height' === $column ) {
			$settings_row['page_height'] = $page_height_value;
			continue;
		}

		if ( array_key_exists( $column, $template_settings ) ) {
			$value = $template_settings[ $column ];
		} elseif ( array_key_exists( $column, $defaults ) ) {
			$value = $defaults[ $column ];
		} else {
			continue;
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$settings_row[ $column ] = is_string( $value ) ? wp_unslash( $value ) : $value;
	}

	$existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE book_id = %d", $book_id ) );
	if ( $existing_id ) {
		$result = $wpdb->update( $table_name, $settings_row, array( 'book_id' => $book_id ) );
	} else {
		$result = $wpdb->insert( $table_name, $settings_row );
	}

	if ( false === $result ) {
		$sql_error = trim( (string) $wpdb->last_error );
		error_log(
			sprintf(
				'[AlmadenBookster] Error guardando ajustes iniciales del libro %d: %s',
				$book_id,
				'' !== $sql_error ? $sql_error : 'Error SQL desconocido.'
			)
		);

		return new WP_Error(
			'seed_settings_failed',
			'' !== $sql_error
				? 'No se pudieron guardar los ajustes iniciales del libro: ' . $sql_error
				: 'No se pudieron guardar los ajustes iniciales del libro.'
		);
	}

	$flow_mode = isset( $template_settings['book_chapter_flow_mode'] )
		? sanitize_text_field( (string) $template_settings['book_chapter_flow_mode'] )
		: 'continuous';
	if ( ! in_array( $flow_mode, array( 'continuous', 'left' ), true ) ) {
		$flow_mode = 'continuous';
	}
	$legacy_parity = isset( $settings_row['chapter_start_parity'] ) ? sanitize_text_field( (string) $settings_row['chapter_start_parity'] ) : 'any';
	$book_separate_opening_content = isset( $template_settings['book_separate_opening_content'] )
		? intval( $template_settings['book_separate_opening_content'] )
		: 1;

	update_post_meta( $book_id, '_almaden_book_separate_opening_content', $book_separate_opening_content ? 1 : 0 );
	update_post_meta( $book_id, '_almaden_book_chapter_flow_mode', $flow_mode );
	update_post_meta( $book_id, '_almaden_book_flow_legacy_parity', $legacy_parity );
	update_post_meta( $book_id, '_almaden_book_template_seed_pending', 1 );

	// These settings are read from post meta by almaden_get_book_pdf_settings().
	// Seed them here as well so a newly created book behaves like one saved from
	// the editor, instead of falling back to the editor defaults.
	$template_meta_fields = array(
		'chapter_subtitle_show',
		'chapter_subtitle_font_family',
		'chapter_subtitle_font_size',
		'chapter_subtitle_align',
		'chapter_subtitle_font_style',
		'chapter_subtitle_text_transform',
		'chapter_subtitle_font_weight',
		'chapter_subtitle_margin_top',
		'chapter_subtitle_margin_bottom',
		'chapter_subtitle_letter_spacing',
		'ebook_subtitle_show',
		'ebook_subtitle_font_family',
		'ebook_subtitle_font_size',
		'ebook_subtitle_align',
		'ebook_subtitle_font_style',
		'ebook_subtitle_text_transform',
		'ebook_subtitle_font_weight',
		'ebook_subtitle_padding_top',
		'ebook_subtitle_padding_bottom',
		'ebook_subtitle_letter_spacing',
	);

	foreach ( $template_meta_fields as $field ) {
		if ( ! array_key_exists( $field, $template_settings ) ) {
			continue;
		}

		$value = $template_settings[ $field ];
		if ( is_array( $value ) || is_object( $value ) ) {
			$value = wp_json_encode( $value );
		}

		if ( is_numeric( $value ) ) {
			$value = ( false !== strpos( (string) $value, '.' ) ) ? (float) $value : (int) $value;
		} else {
			$value = sanitize_text_field( (string) $value );
		}

		update_post_meta( $book_id, '_almaden_' . $field, $value );
	}

	return $settings_row;
}


// --- Procesar Formulario de Creación ---

function almaden_bookster_handle_create_book() {
	// Validar nonce
	if ( ! isset( $_POST['almaden_nonce'] ) || ! wp_verify_nonce( $_POST['almaden_nonce'], 'almaden_create_book_nonce' ) ) {
		wp_die( 'Validación de seguridad fallida.' );
	}

	$title   = isset( $_POST['book_title'] ) ? sanitize_text_field( wp_unslash( $_POST['book_title'] ) ) : '';
	$author  = isset( $_POST['book_author'] ) ? sanitize_text_field( wp_unslash( $_POST['book_author'] ) ) : '';
	$content = isset( $_POST['book_content'] ) ? wp_kses_post( wp_unslash( $_POST['book_content'] ) ) : '';

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

	$book_template = isset( $_POST['book_template'] ) ? sanitize_key( wp_unslash( $_POST['book_template'] ) ) : '';
	$book_template_label = isset( $_POST['book_template_label'] ) ? sanitize_text_field( wp_unslash( $_POST['book_template_label'] ) ) : '';
	$selected_template = almaden_bookster_get_book_template_payload_for_seed( $book_template, $book_template_label );
	if ( '' !== $book_template && ! $selected_template ) {
		wp_die( 'La plantilla seleccionada no existe o no se pudo cargar. El libro no fue creado.' );
	}
	if ( $selected_template ) {
		$book_template = sanitize_key( (string) $selected_template['id'] );
		$book_template_label = sanitize_text_field( (string) $selected_template['name'] );
	}
	if ( '' !== $book_template ) {
		$post_data['meta_input']['_almaden_book_template'] = $book_template;
	}
	if ( '' !== $book_template_label ) {
		$post_data['meta_input']['_almaden_book_template_label'] = $book_template_label;
	}

	// Procesar formatos seleccionados (Ebook, Impreso)
	if ( isset( $_POST['almaden_book_format'] ) && is_array( $_POST['almaden_book_format'] ) ) {
		$formats = array_map( 'sanitize_text_field', $_POST['almaden_book_format'] );
		$post_data['meta_input']['_almaden_formats'] = $formats;
	}

	// Procesar tamaño de impresión si fue especificado
	$page_width = null;
	$page_height = null;
	$size = isset( $_POST['almaden_book_size'] ) ? sanitize_key( wp_unslash( $_POST['almaden_book_size'] ) ) : '';
	$size_map = array(
		'novela'    => array(
			'label'  => 'Novela (13.3 x 20.3 cm)',
			'width'  => 13.3,
			'height' => 20.3,
		),
		'digest'    => array(
			'label'  => 'Digest (14 x 21.6 cm)',
			'width'  => 14.0,
			'height' => 21.6,
		),
		'trade'     => array(
			'label'  => 'Trade (15.2 x 22.9 cm)',
			'width'  => 15.2,
			'height' => 22.9,
		),
		'custom'    => array(
			'label'  => 'Custom',
			'width'  => 0,
			'height' => 0,
		),
		'14x21'     => array(
			'label'  => '14 x 21 cm',
			'width'  => 14.0,
			'height' => 21.0,
		),
		'15x23'     => array(
			'label'  => '15 x 23 cm',
			'width'  => 15.0,
			'height' => 23.0,
		),
	);

	if ( '' !== $size ) {
		$post_data['meta_input']['_almaden_book_size'] = $size;

		if ( isset( $size_map[ $size ] ) && 'custom' !== $size ) {
			$page_width  = (float) $size_map[ $size ]['width'];
			$page_height = (float) $size_map[ $size ]['height'];
		} elseif ( 'custom' === $size ) {
			if ( isset( $_POST['almaden_custom_width'] ) ) {
				$page_width = floatval( wp_unslash( $_POST['almaden_custom_width'] ) );
			}
			if ( isset( $_POST['almaden_custom_height'] ) ) {
				$page_height = floatval( wp_unslash( $_POST['almaden_custom_height'] ) );
			}

			$custom_margins = array(
				'almaden_custom_margin_top'    => '_almaden_custom_margin_top',
				'almaden_custom_margin_bottom' => '_almaden_custom_margin_bottom',
				'almaden_custom_margin_outer'  => '_almaden_custom_margin_outer',
				'almaden_custom_margin_inner'  => '_almaden_custom_margin_inner',
			);
			foreach ( $custom_margins as $request_key => $meta_key ) {
				if ( isset( $_POST[ $request_key ] ) ) {
					$post_data['meta_input'][ $meta_key ] = floatval( wp_unslash( $_POST[ $request_key ] ) );
				}
			}
		}
	}

	$post_id = wp_insert_post( $post_data );

	if ( ! is_wp_error( $post_id ) ) {
		if ( function_exists( 'almaden_bookster_sync_book_authors_from_input' ) ) {
			almaden_bookster_sync_book_authors_from_input( $post_id, $author );
		}

		$seed_settings = almaden_bookster_seed_book_settings_for_book( $post_id, $book_template, $book_template_label, $size, $size_map, $page_width, $page_height, $selected_template );
		if ( is_wp_error( $seed_settings ) ) {
			wp_delete_post( $post_id, true );
			wp_die( 'No se pudo aplicar la plantilla seleccionada: ' . esc_html( $seed_settings->get_error_message() ) );
		}

		$template_font = isset( $selected_template['settings']['font_family_content'] )
			? (string) $selected_template['settings']['font_family_content']
			: '';
		if ( '' !== $template_font && $template_font !== (string) ( $seed_settings['font_family_content'] ?? '' ) ) {
			wp_delete_post( $post_id, true );
			wp_die( 'La plantilla seleccionada no se pudo inicializar correctamente. El libro no fue creado.' );
		}

		$created_at_month = current_time( 'Y-m' );
		$seed_credits = almaden_bookster_seed_default_credits_config_for_book( $post_id, $author, $created_at_month );
		if ( is_wp_error( $seed_credits ) ) {
			wp_delete_post( $post_id, true );
			wp_die( 'No se pudieron guardar los ajustes iniciales de créditos del libro: ' . esc_html( $seed_credits->get_error_message() ) );
		}

		$seed_chapters = almaden_bookster_create_seed_chapters_for_book( $post_id, $book_template, $book_template_label, $author );
		if ( is_wp_error( $seed_chapters ) ) {
			error_log( '[AlmadenBookster] No se pudieron crear los capítulos base del libro ' . $post_id . ': ' . $seed_chapters->get_error_message() );
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
		
		// Load available fonts, including bundled defaults.
		$installed_fonts = function_exists( 'almaden_bookster_get_available_fonts_list' )
			? almaden_bookster_get_available_fonts_list()
			: get_option( 'almaden_fonts_library', array() );
		
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
