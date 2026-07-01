<?php
/**
 * AlmadenBookster - Learni integration admin hooks, menu, and handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
