<?php
/**
 * AlmadenBookster - Learni integration settings and helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION', 'almaden_bookster_learni_integration_enabled' );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_QUIZ_META' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_QUIZ_META', '_almaden_learni_quiz_id' );
}

if ( ! defined( 'ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META' ) ) {
	define( 'ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META', '_almaden_learni_chapter_quiz_id' );
}

function almaden_bookster_learni_is_available() {
	return class_exists( '\\Learni\\QuizEditor\\QuizEditor' )
		|| class_exists( '\\Learni\\Frontend\\QuizEditorScreen' )
		|| class_exists( 'PL_Learni_Module' )
		|| defined( 'LEARNI_VERSION' );
}

function almaden_bookster_learni_integration_enabled() {
	return get_option( ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION, '0' ) === '1';
}

function almaden_bookster_learni_integration_active() {
	return almaden_bookster_learni_integration_enabled() && almaden_bookster_learni_is_available();
}

function almaden_bookster_learni_editor_url( $book_id, $quiz_id = 0 ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( class_exists( '\\Learni\\Frontend\\QuizEditorScreen' ) ) {
		return \Learni\Frontend\QuizEditorScreen::quiz_url( $book_id, $quiz_id );
	}

	$args = array();
	if ( $book_id > 0 ) {
		$args['book_id'] = $book_id;
	}
	if ( $quiz_id > 0 ) {
		$args['quiz_id'] = $quiz_id;
	}

	return add_query_arg( $args, home_url( '/almaden-book-quiz/' ) );
}

function almaden_bookster_learni_quiz_exists( $quiz_id ) {
	$quiz_id = absint( $quiz_id );
	if ( $quiz_id <= 0 ) {
		return false;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizRepository' ) ) {
		return \Learni\QuizEditor\QuizRepository::quiz_exists( $quiz_id );
	}

	return false;
}

function almaden_bookster_learni_get_quiz_id( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 ) {
		return 0;
	}

	$quiz_id = (int) get_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, true );
	if ( $quiz_id > 0 && almaden_bookster_learni_quiz_exists( $quiz_id ) ) {
		return $quiz_id;
	}

	if ( $quiz_id > 0 ) {
		delete_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META );
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizRepository' ) ) {
		$resolved = (int) \Learni\QuizEditor\QuizRepository::get_quiz_id_by_course( $book_id );
		if ( $resolved > 0 && almaden_bookster_learni_quiz_exists( $resolved ) ) {
			update_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, $resolved );
			return $resolved;
		}
	}

	return 0;
}

function almaden_bookster_learni_set_quiz_id( $book_id, $quiz_id ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( $book_id <= 0 ) {
		return false;
	}

	if ( $quiz_id > 0 ) {
		update_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META, $quiz_id );
		return true;
	}

	delete_post_meta( $book_id, ALMADEN_BOOKSTER_LEARNI_QUIZ_META );
	return true;
}

function almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id ) {
	$quiz_id = absint( $quiz_id );
	if ( $quiz_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return null;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		return \Learni\QuizEditor\QuizEditor::get_quiz_data( $quiz_id );
	}

	return null;
}

function almaden_bookster_learni_get_quiz_primary_chapter_key( $quiz_id ) {
	$quiz_data = almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id );
	if ( ! is_array( $quiz_data ) || empty( $quiz_data['questions'] ) || ! is_array( $quiz_data['questions'] ) ) {
		return '';
	}

	foreach ( $quiz_data['questions'] as $question_row ) {
		if ( ! is_array( $question_row ) ) {
			continue;
		}

		$chapter_key = '';
		if ( isset( $question_row['chapter_key'] ) && is_scalar( $question_row['chapter_key'] ) ) {
			$chapter_key = sanitize_title( (string) $question_row['chapter_key'] );
		}

		if ( $chapter_key !== '' ) {
			return $chapter_key;
		}
	}

	return '';
}

function almaden_bookster_learni_get_quiz_primary_chapter_id( $quiz_id ) {
	$quiz_data = almaden_bookster_learni_get_quiz_data_by_quiz_id( $quiz_id );
	if ( ! is_array( $quiz_data ) || empty( $quiz_data['questions'] ) || ! is_array( $quiz_data['questions'] ) ) {
		return 0;
	}

	foreach ( $quiz_data['questions'] as $question_row ) {
		if ( ! is_array( $question_row ) ) {
			continue;
		}

		if ( isset( $question_row['chapter_id'] ) && is_numeric( $question_row['chapter_id'] ) ) {
			$chapter_id = absint( $question_row['chapter_id'] );
			if ( $chapter_id > 0 ) {
				return $chapter_id;
			}
		}
	}

	return 0;
}

function almaden_bookster_learni_get_chapter_counter_key( $chapter_id ) {
	$chapter_id = absint( $chapter_id );
	if ( $chapter_id <= 0 ) {
		return '';
	}

	$chapter = get_post( $chapter_id );
	if ( ! $chapter || 'book_chapter' !== $chapter->post_type ) {
		return '';
	}

	$chapter_number = (int) $chapter->menu_order;
	if ( $chapter_number > 0 ) {
		return sanitize_title( 'chapter ' . $chapter_number );
	}

	if ( $chapter->post_parent > 0 ) {
		$chapter_posts = get_posts(
			array(
				'post_type'      => 'book_chapter',
				'post_parent'    => (int) $chapter->post_parent,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$filtered_index = 0;
		foreach ( $chapter_posts as $sibling ) {
			if ( '1' === (string) get_post_meta( $sibling->ID, '_is_toc', true ) || '1' === (string) get_post_meta( $sibling->ID, '_is_credits', true ) ) {
				continue;
			}

			$filtered_index++;
			if ( (int) $sibling->ID === $chapter_id ) {
				return sanitize_title( 'chapter ' . ( (int) $sibling->menu_order > 0 ? (int) $sibling->menu_order : $filtered_index ) );
			}
		}
	}

	return sanitize_title( 'chapter ' . $chapter_id );
}

function almaden_bookster_learni_get_quiz_id_for_chapter( $chapter_id ) {
	$chapter_id = absint( $chapter_id );
	if ( $chapter_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return 0;
	}

	$chapter = get_post( $chapter_id );
	if ( ! $chapter || 'book_chapter' !== $chapter->post_type ) {
		return 0;
	}

	$stored_quiz_id = (int) get_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META, true );
	if ( $stored_quiz_id > 0 && almaden_bookster_learni_quiz_exists( $stored_quiz_id ) ) {
		$stored_quiz_chapter_id = almaden_bookster_learni_get_quiz_primary_chapter_id( $stored_quiz_id );
		if ( $stored_quiz_chapter_id > 0 ) {
			if ( $stored_quiz_chapter_id === $chapter_id ) {
				return $stored_quiz_id;
			}
		} else {
			$stored_quiz_chapter_key = almaden_bookster_learni_get_quiz_primary_chapter_key( $stored_quiz_id );
			$current_chapter_key = almaden_bookster_learni_get_chapter_counter_key( $chapter_id );
			if ( $stored_quiz_chapter_key !== '' && $current_chapter_key !== '' && $stored_quiz_chapter_key === $current_chapter_key ) {
				return $stored_quiz_id;
			}
		}

		delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
		return 0;
	}

	if ( $stored_quiz_id > 0 ) {
		delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
	}

	return 0;
}

function almaden_bookster_learni_set_quiz_id_for_chapter( $chapter_id, $quiz_id ) {
	$chapter_id = absint( $chapter_id );
	$quiz_id = absint( $quiz_id );

	if ( $chapter_id <= 0 ) {
		return false;
	}

	if ( $quiz_id > 0 ) {
		update_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META, $quiz_id );
		return true;
	}

	delete_post_meta( $chapter_id, ALMADEN_BOOKSTER_LEARNI_CHAPTER_QUIZ_META );
	return true;
}

function almaden_bookster_learni_ensure_quiz_for_book( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return 0;
	}

	$existing_quiz_id = almaden_bookster_learni_get_quiz_id( $book_id );
	if ( $existing_quiz_id > 0 && almaden_bookster_learni_quiz_exists( $existing_quiz_id ) ) {
		return $existing_quiz_id;
	}

	if ( $existing_quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id( $book_id, 0 );
	}

	$book_title = (string) get_the_title( $book_id );
	$quiz_args = array(
		'course_id' => $book_id,
		'title' => $book_title !== '' ? $book_title : __( 'Quiz del libro', 'almaden-bookster' ),
		'settings' => array(
			'passing_score' => 80,
			'time_limit_seconds' => 0,
			'question_order' => 'in_order',
			'shuffle_answers' => 1,
			'show_points' => 0,
			'run_once' => 0,
			'force_solve' => 1,
			'restart_cooldown_days' => 0,
		),
		'questions' => array(
			array(
				'title' => __( 'Pregunta 1', 'almaden-bookster' ),
				'question_text' => '',
				'answers' => array(
					array( 'text' => __( 'Respuesta 1', 'almaden-bookster' ), 'correct' => true ),
					array( 'text' => __( 'Respuesta 2', 'almaden-bookster' ), 'correct' => false ),
				),
			),
		),
	);

	$result = null;
	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		$result = \Learni\QuizEditor\QuizEditor::create_quiz( $quiz_args );
	}

	if ( null === $result ) {
		return 0;
	}

	if ( is_wp_error( $result ) ) {
		return 0;
	}

	$quiz_id = is_array( $result )
		? (int) ( $result['quiz_post_id'] ?? 0 )
		: absint( $result );

	if ( $quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id( $book_id, $quiz_id );
	}

	return $quiz_id;
}

function almaden_bookster_learni_get_quiz_data( $book_id ) {
	$book_id = absint( $book_id );
	if ( $book_id <= 0 || ! almaden_bookster_learni_is_available() ) {
		return null;
	}

	$quiz_id = almaden_bookster_learni_get_quiz_id( $book_id );
	if ( $quiz_id <= 0 ) {
		return null;
	}

	if ( class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		return \Learni\QuizEditor\QuizEditor::get_quiz_data( $quiz_id );
	}

	return null;
}

function almaden_bookster_learni_normalize_book_quiz_payload( $payload, $book_id, $quiz_id ) {
	$book_id = absint( $book_id );
	$quiz_id = absint( $quiz_id );

	if ( $book_id <= 0 || ! is_array( $payload ) ) {
		return new WP_Error( 'invalid_payload', __( 'Payload inválido.', 'almaden-bookster' ) );
	}

	$source = isset( $payload['quiz'] ) && is_array( $payload['quiz'] ) ? $payload['quiz'] : $payload;
	$source_settings = isset( $source['settings'] ) && is_array( $source['settings'] ) ? $source['settings'] : array();
	$source_questions = array();
	if ( isset( $source['questions'] ) && is_array( $source['questions'] ) ) {
		$source_questions = $source['questions'];
	} elseif ( isset( $payload['questions'] ) && is_array( $payload['questions'] ) ) {
		$source_questions = $payload['questions'];
	}

	if ( empty( $source_questions ) ) {
		return new WP_Error( 'empty_questions', __( 'El quiz no contiene preguntas.', 'almaden-bookster' ) );
	}

	$book_title = (string) get_the_title( $book_id );
	$chapter_key = '';
	if ( isset( $payload['chapter_key'] ) && is_scalar( $payload['chapter_key'] ) ) {
		$chapter_key = sanitize_title( (string) $payload['chapter_key'] );
	} elseif ( isset( $source['chapter_key'] ) && is_scalar( $source['chapter_key'] ) ) {
		$chapter_key = sanitize_title( (string) $source['chapter_key'] );
	}

	$chapter_title = '';
	if ( isset( $payload['chapter_title'] ) && is_scalar( $payload['chapter_title'] ) ) {
		$chapter_title = sanitize_text_field( (string) $payload['chapter_title'] );
	} elseif ( isset( $source['chapter_title'] ) && is_scalar( $source['chapter_title'] ) ) {
		$chapter_title = sanitize_text_field( (string) $source['chapter_title'] );
	}

	$chapter_id = 0;
	if ( isset( $payload['chapter_id'] ) ) {
		$chapter_id = absint( $payload['chapter_id'] );
	} elseif ( isset( $source['chapter_id'] ) ) {
		$chapter_id = absint( $source['chapter_id'] );
	}

	$questions = array();
	foreach ( $source_questions as $index => $question ) {
		if ( ! is_array( $question ) ) {
			continue;
		}

		$question_chapter_key = '';
		if ( isset( $question['chapter_key'] ) && is_scalar( $question['chapter_key'] ) ) {
			$question_chapter_key = sanitize_title( (string) $question['chapter_key'] );
		} elseif ( $chapter_key !== '' ) {
			$question_chapter_key = $chapter_key;
		}

		$question_chapter_title = '';
		if ( isset( $question['chapter_title'] ) && is_scalar( $question['chapter_title'] ) ) {
			$question_chapter_title = sanitize_text_field( (string) $question['chapter_title'] );
		} elseif ( $chapter_title !== '' ) {
			$question_chapter_title = $chapter_title;
		}

		$question_chapter_id = 0;
		if ( isset( $question['chapter_id'] ) ) {
			$question_chapter_id = absint( $question['chapter_id'] );
		} elseif ( $chapter_id > 0 ) {
			$question_chapter_id = $chapter_id;
		}

		$answers = array();
		$source_answers = isset( $question['answers'] ) && is_array( $question['answers'] ) ? $question['answers'] : array();
		foreach ( $source_answers as $answer ) {
			if ( ! is_array( $answer ) ) {
				continue;
			}

			$answers[] = array(
				'text' => isset( $answer['text'] ) ? sanitize_text_field( (string) wp_unslash( $answer['text'] ) ) : '',
				'correct' => ! empty( $answer['correct'] ),
			);
		}

		$questions[] = array(
			'title' => isset( $question['title'] ) ? sanitize_text_field( (string) wp_unslash( $question['title'] ) ) : '',
			'question_text' => isset( $question['question_text'] ) ? wp_kses_post( (string) wp_unslash( $question['question_text'] ) ) : '',
			'chapter_key' => $question_chapter_key,
			'chapter_id' => $question_chapter_id,
			'chapter_title' => $question_chapter_title,
			'answers' => $answers,
		);
	}

	return array(
		'course_id' => $book_id,
		'quiz_id' => $quiz_id,
		'title' => isset( $payload['quiz_title'] ) ? sanitize_text_field( (string) $payload['quiz_title'] ) : ( isset( $source['title'] ) ? sanitize_text_field( (string) $source['title'] ) : $book_title ),
		'settings' => array(
			'passing_score' => isset( $source_settings['passing_score'] ) ? absint( $source_settings['passing_score'] ) : 80,
			'time_limit_seconds' => isset( $source_settings['time_limit_seconds'] ) ? absint( $source_settings['time_limit_seconds'] ) : 0,
			'question_order' => isset( $source_settings['question_order'] ) ? sanitize_key( (string) $source_settings['question_order'] ) : 'in_order',
			'shuffle_answers' => ! empty( $source_settings['shuffle_answers'] ) ? 1 : 0,
			'show_points' => ! empty( $source_settings['show_points'] ) ? 1 : 0,
			'run_once' => ! empty( $source_settings['run_once'] ) ? 1 : 0,
			'force_solve' => ! empty( $source_settings['force_solve'] ) ? 1 : 0,
			'restart_cooldown_days' => isset( $source_settings['restart_cooldown_days'] ) ? absint( $source_settings['restart_cooldown_days'] ) : 0,
			'scope' => isset( $payload['scope'] ) ? sanitize_key( (string ) $payload['scope'] ) : ( isset( $source['scope'] ) ? sanitize_key( (string ) $source['scope'] ) : 'chapter' ),
			'book_title' => isset( $payload['book_title'] ) ? sanitize_text_field( (string ) $payload['book_title'] ) : ( isset( $source['book_title'] ) ? sanitize_text_field( (string ) $source['book_title'] ) : $book_title ),
			'chapter_id' => $chapter_id,
			'chapter_title' => $chapter_title,
			'chapter_key' => $chapter_key,
			'chapter_ids_json' => isset( $source_settings['chapter_ids_json'] ) ? sanitize_textarea_field( (string ) $source_settings['chapter_ids_json'] ) : '',
			'chapter_titles_json' => isset( $source_settings['chapter_titles_json'] ) ? sanitize_textarea_field( (string ) $source_settings['chapter_titles_json'] ) : '',
		),
		'questions' => $questions,
	);
}

function almaden_bookster_handle_save_learni_quiz() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'Debes iniciar sesión para guardar quizzes.', 'almaden-bookster' ) );
	}

	$book_id = isset( $_POST['book_id'] ) ? absint( $_POST['book_id'] ) : 0;
	$quiz_id = isset( $_POST['quiz_id'] ) ? absint( $_POST['quiz_id'] ) : 0;
	if ( $book_id <= 0 ) {
		wp_die( esc_html__( 'Libro inválido.', 'almaden-bookster' ) );
	}

	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_post', $book_id ) ) {
		wp_die( esc_html__( 'No tienes permisos para guardar este quiz.', 'almaden-bookster' ) );
	}

	check_admin_referer( 'almaden_save_book_quiz_' . $book_id );

	$raw_payload = isset( $_POST['quiz_payload_json'] ) ? wp_unslash( (string) $_POST['quiz_payload_json'] ) : '';
	$decoded_payload = json_decode( $raw_payload, true );
	if ( ! is_array( $decoded_payload ) ) {
		wp_die( esc_html__( 'El payload del quiz no es JSON válido.', 'almaden-bookster' ) );
	}

	if ( ! class_exists( '\\Learni\\QuizEditor\\QuizEditor' ) ) {
		wp_die( esc_html__( 'Learni no está disponible para guardar el quiz.', 'almaden-bookster' ) );
	}

	$payload = almaden_bookster_learni_normalize_book_quiz_payload( $decoded_payload, $book_id, $quiz_id );
	if ( is_wp_error( $payload ) ) {
		wp_die( esc_html( $payload->get_error_message() ) );
	}

	$chapter_id = isset( $payload['settings']['chapter_id'] ) ? absint( $payload['settings']['chapter_id'] ) : 0;
	if ( $chapter_id > 0 ) {
		$resolved_quiz_id = almaden_bookster_learni_get_quiz_id_for_chapter( $chapter_id );
		if ( $resolved_quiz_id > 0 ) {
			$payload['quiz_id'] = $resolved_quiz_id;
			$result = \Learni\QuizEditor\QuizEditor::save_quiz( $payload );
		} else {
			$result = \Learni\QuizEditor\QuizEditor::create_quiz( $payload );
		}
	} elseif ( $quiz_id > 0 ) {
		$payload['quiz_id'] = $quiz_id;
		$result = \Learni\QuizEditor\QuizEditor::save_quiz( $payload );
	} else {
		$result = \Learni\QuizEditor\QuizEditor::create_quiz( $payload );
	}

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ) );
	}

	$final_quiz_id = is_numeric( $result ) ? (int) $result : $quiz_id;
	if ( $chapter_id > 0 && $final_quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id_for_chapter( $chapter_id, $final_quiz_id );
	}
	if ( $final_quiz_id > 0 ) {
		almaden_bookster_learni_set_quiz_id( $book_id, $final_quiz_id );
	}

	$redirect = add_query_arg(
		array(
			'book_id' => $book_id,
			'quiz_id' => $final_quiz_id > 0 ? $final_quiz_id : $quiz_id,
			'chapter_id' => $chapter_id > 0 ? $chapter_id : '',
			'saved'   => '1',
		),
		home_url( '/almaden-book-quiz/' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_almaden_save_book_quiz', 'almaden_bookster_handle_save_learni_quiz' );

function almaden_bookster_register_learni_menu() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'almaden-bookster',
		'Integración Learni',
		'Integración Learni',
		'manage_options',
		'almaden-bookster-learni',
		'almaden_bookster_render_learni_integration_page'
	);
}
add_action( 'admin_menu', 'almaden_bookster_register_learni_menu', 20 );

function almaden_bookster_handle_learni_integration_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Permisos insuficientes.', 'almaden-bookster' ) );
	}

	check_admin_referer( 'almaden_learni_integration_settings' );

	$enabled = isset( $_POST['almaden_learni_integration_enabled'] ) ? '1' : '0';
	update_option( ALMADEN_BOOKSTER_LEARNI_INTEGRATION_OPTION, $enabled );

	$redirect = add_query_arg(
		array(
			'page'    => 'almaden-bookster-learni',
			'saved'   => '1',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_almaden_save_learni_integration', 'almaden_bookster_handle_learni_integration_save' );

function almaden_bookster_render_learni_integration_page() {
	$is_enabled = almaden_bookster_learni_integration_enabled();
	$is_active   = almaden_bookster_learni_integration_active();
	$saved       = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
	?>
	<div class="wrap">
		<h1>Integración Learni</h1>
		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Configuración guardada correctamente.</p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width: 920px; padding: 24px;">
			<h2 style="margin-top:0;">Conectar quizzes con libros</h2>
			<p style="font-size: 14px; max-width: 72ch;">
				Activa esta integración para que Bookster muestre el acceso a la creación de quizzes cuando el plugin Learni esté disponible en el sitio.
			</p>

			<p>
				<strong>Estado de Learni:</strong>
				<?php if ( $is_active ) : ?>
					<span style="color:#1e7e34;">Detectado y listo</span>
				<?php elseif ( almaden_bookster_learni_is_available() ) : ?>
					<span style="color:#b45309;">Detectado, pero la integración está desactivada</span>
				<?php else : ?>
					<span style="color:#b91c1c;">No detectado</span>
				<?php endif; ?>
			</p>

			<?php if ( ! almaden_bookster_learni_is_available() ) : ?>
				<div class="notice notice-warning inline" style="margin: 16px 0 0;">
					<p>Learni no parece estar activo en este sitio. Puedes dejar la integración preparada y activarla más adelante.</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 20px;">
				<input type="hidden" name="action" value="almaden_save_learni_integration">
				<?php wp_nonce_field( 'almaden_learni_integration_settings' ); ?>

				<label style="display:flex; align-items:center; gap:10px; font-size: 14px; margin-bottom: 20px;">
					<input type="checkbox" name="almaden_learni_integration_enabled" value="1" <?php checked( $is_enabled ); ?>>
					<span>Activar integración con Learni</span>
				</label>

				<p class="submit" style="padding:0; margin:0;">
					<button type="submit" class="button button-primary">Guardar cambios</button>
				</p>
			</form>
		</div>
	</div>
	<?php
}
