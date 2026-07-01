<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$book_id = isset( $_GET['book_id'] ) ? absint( $_GET['book_id'] ) : 0;
$book    = $book_id > 0 ? get_post( $book_id ) : null;

if ( ! $book || 'almaden-books' !== $book->post_type ) {
	wp_die( 'Libro no encontrado.' );
}

if ( ! is_user_logged_in() || ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_post', $book_id ) ) ) {
	wp_die( 'No tienes permisos para crear o editar quizzes para este libro.' );
}

if ( ! function_exists( 'almaden_bookster_learni_integration_active' ) || ! almaden_bookster_learni_integration_active() ) {
	wp_die( 'La integración con Learni está desactivada o no está disponible.' );
}

$source_book_id = get_post_meta( $book_id, '_almaden_source_book_id', true );
if ( empty( $source_book_id ) ) {
	$source_book_id = $book_id;
}

$requested_chapter_id = isset( $_GET['chapter_id'] ) ? absint( $_GET['chapter_id'] ) : 0;
$book_title           = get_the_title( $book_id );
$book_editor_url      = home_url( '/almaden-book-editor/?book_id=' . $book_id );
$booklist_url         = home_url( '/almaden-booklist/' );
$plugin_file          = dirname( __DIR__, 2 ) . '/almaden-bookster.php';
$quiz_builder_css     = plugins_url( 'assets/css/quiz-builder/quiz-builder-app.css', $plugin_file );
$quiz_builder_parser_js = plugins_url( 'assets/js/quiz-builder/quiz-builder-parser.js', $plugin_file );
$quiz_builder_editor_js = plugins_url( 'assets/js/quiz-builder/quiz-builder-editor.js', $plugin_file );
$quiz_builder_preview_js = plugins_url( 'assets/js/quiz-builder/quiz-builder-preview.js', $plugin_file );
$quiz_builder_js      = plugins_url( 'assets/js/quiz-builder/quiz-builder-app.js', $plugin_file );

$quiz_settings = array(
	'question_count'       => 5,
	'alternatives_count'   => 4,
	'difficulty'           => 'medium',
	'target_language'      => 'es',
	'style'                => 'clear',
	'include_explanations' => 0,
);

$chapter_posts = get_posts(
	array(
		'post_type'      => 'book_chapter',
		'post_parent'    => $source_book_id,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order',
		'order'          => 'ASC',
	)
);

$chapter_items = array();
$content_chapter_counter = 1;
foreach ( $chapter_posts as $index => $chapter ) {
	if ( '1' === (string) get_post_meta( $chapter->ID, '_is_toc', true ) || '1' === (string) get_post_meta( $chapter->ID, '_is_credits', true ) ) {
		continue;
	}

	$chapter_number = $content_chapter_counter;
	$content_chapter_counter++;
	$chapter_key    = sanitize_title( 'chapter ' . $chapter_number );
	$chapter_item   = array(
		'id'             => (int) $chapter->ID,
		'key'            => $chapter_key !== '' ? $chapter_key : 'chapter-' . (string) $chapter_number,
		'title'          => (string) $chapter->post_title,
		'order'          => $chapter_number,
		'quiz_id'        => 0,
		'quiz_data'      => null,
		'content'        => wp_strip_all_tags( (string) $chapter->post_content ),
		'question_count' => 0,
	);

	if ( function_exists( 'almaden_bookster_learni_get_quiz_id_for_chapter' ) ) {
		$chapter_item['quiz_id'] = (int) almaden_bookster_learni_get_quiz_id_for_chapter( $chapter->ID );
	}

	if ( $chapter_item['quiz_id'] > 0 && class_exists( '\\LearniStandalone\\QuizEditor\\QuizEditor' ) ) {
		$chapter_quiz_data = \LearniStandalone\QuizEditor\QuizEditor::get_quiz_data( $chapter_item['quiz_id'] );
		if ( is_array( $chapter_quiz_data ) ) {
			$chapter_item['quiz_data'] = $chapter_quiz_data;
			if ( ! empty( $chapter_quiz_data['questions'] ) && is_array( $chapter_quiz_data['questions'] ) ) {
				$chapter_item['question_count'] = count( $chapter_quiz_data['questions'] );
			}
		}
	}

	$chapter_items[] = $chapter_item;
}

if ( empty( $chapter_items ) ) {
	$chapter_items[] = array(
		'id'             => 0,
		'key'            => 'chapter-1',
		'title'          => 'Chapter 1',
		'order'          => 1,
		'quiz_id'        => 0,
		'quiz_data'      => null,
		'content'        => 'Este libro todavía no tiene capítulos para mostrar.',
		'question_count' => 0,
	);
}

$active_chapter_index = 0;
$active_chapter       = $chapter_items[0];
if ( $requested_chapter_id > 0 ) {
	foreach ( $chapter_items as $item_index => $chapter_item ) {
		if ( (int) $chapter_item['id'] === $requested_chapter_id ) {
			$active_chapter       = $chapter_item;
			$active_chapter_index = (int) $item_index;
			break;
		}
	}
}

$active_quiz_id   = isset( $active_chapter['quiz_id'] ) ? (int) $active_chapter['quiz_id'] : 0;
$active_quiz_data = isset( $active_chapter['quiz_data'] ) && is_array( $active_chapter['quiz_data'] ) ? $active_chapter['quiz_data'] : null;
$saved_notice     = isset( $_GET['saved'] ) && '1' === (string) $_GET['saved'];
?>
<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Book Quiz - <?php echo esc_html( $book_title ); ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Urbanist:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo esc_url( $quiz_builder_css ); ?>?v=<?php echo esc_attr( (string) filemtime( dirname( __DIR__, 2 ) . '/assets/css/quiz-builder/quiz-builder-app.css' ) ); ?>">
	<?php if ( defined( 'PL_LEARNI_URL' ) ) : ?>
		<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block" rel="stylesheet">
		<link rel="stylesheet" href="<?php echo esc_url( PL_LEARNI_URL . 'assets/learner.css' ); ?>">
	<?php endif; ?>
	<script>
		window.ALMADEN_QUIZ_BUILDER_DATA = <?php echo wp_json_encode( array(
			'bookId' => $book_id,
			'bookTitle' => $book_title,
			'bookEditorUrl' => $book_editor_url,
			'booklistUrl' => $booklist_url,
			'homeUrl' => home_url( '/' ),
			'chapters' => $chapter_items,
			'initialActiveChapterIndex' => $active_chapter_index,
			'initialQuizData' => $active_quiz_data,
			'quizFlowSettings' => almaden_bookster_learni_get_quiz_flow_settings( $book_id ),
		) ); ?>;
	</script>
	<script src="<?php echo esc_url( $quiz_builder_parser_js ); ?>?v=<?php echo esc_attr( (string) filemtime( dirname( __DIR__, 2 ) . '/assets/js/quiz-builder/quiz-builder-parser.js' ) ); ?>" defer></script>
	<script src="<?php echo esc_url( $quiz_builder_editor_js ); ?>?v=<?php echo esc_attr( (string) filemtime( dirname( __DIR__, 2 ) . '/assets/js/quiz-builder/quiz-builder-editor.js' ) ); ?>" defer></script>
	<script src="<?php echo esc_url( $quiz_builder_preview_js ); ?>?v=<?php echo esc_attr( (string) filemtime( dirname( __DIR__, 2 ) . '/assets/js/quiz-builder/quiz-builder-preview.js' ) ); ?>" defer></script>
	<script src="<?php echo esc_url( $quiz_builder_js ); ?>?v=<?php echo esc_attr( (string) filemtime( dirname( __DIR__, 2 ) . '/assets/js/quiz-builder/quiz-builder-app.js' ) ); ?>" defer></script>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'almaden-quiz-page' ); ?>>
<?php wp_body_open(); ?>
<div class="almaden-quiz-page">
	<header class="almaden-quiz-header">
		<div>
			<p class="almaden-quiz-kicker">Create Quiz</p>
			<h1><?php echo esc_html( $book_title ); ?></h1>
			<p class="almaden-quiz-subtitle">Diseño base del nuevo editor de quizzes para este libro.</p>
		</div>
		<div class="almaden-quiz-header-actions">
			<a href="<?php echo esc_url( $booklist_url ); ?>" class="almaden-btn almaden-btn--ghost">Volver al taller</a>
			<a href="<?php echo esc_url( $book_editor_url ); ?>" class="almaden-btn almaden-btn--ghost">Editar contenido</a>
			<a href="#" class="almaden-btn almaden-btn--ghost" id="almaden-preview-quiz-btn">Preview Quiz</a>
			<?php if ( $saved_notice ) : ?>
				<span class="almaden-chip almaden-chip--success">Saved</span>
			<?php endif; ?>
		</div>
	</header>

	<main class="almaden-quiz-workspace">
		<aside class="almaden-panel almaden-sidebar">
			<div class="almaden-sidebar-card">
				<div class="almaden-sidebar-card-head">
					<div>
						<p class="almaden-sidebar-label">Chapter list</p>
						<h2 class="almaden-sidebar-title">Chapters</h2>
					</div>
					<div class="almaden-sidebar-meta">
						<span class="almaden-chip"><?php echo esc_html( (string) count( $chapter_items ) ); ?> chapters</span>
					</div>
				</div>
				<div class="almaden-sidebar-list" id="almaden-chapter-list">
					<?php foreach ( $chapter_items as $index => $chapter_item ) : ?>
						<div
							role="button"
							tabindex="0"
							class="almaden-chapter-item<?php echo (int) $active_chapter_index === (int) $index ? ' is-active' : ''; ?>"
							data-chapter-index="<?php echo esc_attr( (string) $index ); ?>"
							data-chapter-key="<?php echo esc_attr( $chapter_item['key'] ); ?>"
							data-chapter-title="<?php echo esc_attr( $chapter_item['title'] ); ?>"
							data-chapter-order="<?php echo esc_attr( (string) $chapter_item['order'] ); ?>"
						>
							<div class="almaden-chapter-content">
								<div>
									<h3 class="almaden-chapter-title"><?php echo esc_html( $chapter_item['title'] !== '' ? $chapter_item['title'] : sprintf( 'Chapter %d', $chapter_item['order'] ) ); ?></h3>
									<p class="almaden-chapter-subtitle">Chapter <?php echo esc_html( (string) $chapter_item['order'] ); ?></p>
								</div>
								<div class="almaden-chapter-meta">
									<span class="almaden-question-count<?php echo (int) $chapter_item['question_count'] > 0 ? '' : ' is-empty'; ?>" data-chapter-count-label="<?php echo esc_attr( (string) $chapter_item['question_count'] ); ?>">
										<?php echo (int) $chapter_item['question_count'] > 0 ? esc_html( (string) $chapter_item['question_count'] ) . ' ✓' : ''; ?>
									</span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</aside>

		<section class="almaden-panel almaden-workspace">
			<div class="almaden-workspace-card">
				<div class="almaden-workspace-head">
					<div>
						<p class="almaden-workspace-label">Quiz workspace</p>
						<h2 class="almaden-workspace-title" id="almaden-active-chapter-title"><?php echo esc_html( $active_chapter['title'] ); ?></h2>
						<p class="almaden-quiz-subtitle" id="almaden-active-chapter-caption">Chapter <?php echo esc_html( (string) $active_chapter['order'] ); ?></p>
					</div>
					<div class="almaden-workspace-actions">
						<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-save-quiz">Save Quiz</button>
					</div>
				</div>

				<div class="almaden-tabbar" role="tablist" aria-label="Quiz editor sections">
					<button type="button" class="almaden-tab is-active" role="tab" aria-selected="true" data-tab-target="prompt-settings">Prompt Settings</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="enter-prompt">Enter Prompt</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="quiz-preview">Quiz Preview</button>
					<button type="button" class="almaden-tab" role="tab" aria-selected="false" data-tab-target="chapter-content">Chapter Content</button>
				</div>

				<div class="almaden-tabpanels">
					<section class="almaden-tabpanel is-active" data-tab-panel="prompt-settings" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-body">
								<div class="almaden-grid almaden-grid--settings">
									<div class="almaden-field">
										<label for="almaden-setting-question-count">Cantidad de preguntas</label>
										<input id="almaden-setting-question-count" type="number" min="1" max="25" value="<?php echo esc_attr( (string) $quiz_settings['question_count'] ); ?>">
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-alternatives-count">Alternativas por pregunta</label>
										<input id="almaden-setting-alternatives-count" type="number" min="2" max="8" value="<?php echo esc_attr( (string) $quiz_settings['alternatives_count'] ); ?>">
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-difficulty">Nivel de dificultad</label>
										<select id="almaden-setting-difficulty">
											<option value="easy">Fácil</option>
											<option value="medium" selected>Media</option>
											<option value="hard">Difícil</option>
										</select>
									</div>
									<div class="almaden-field">
										<label for="almaden-setting-style">Estilo</label>
										<select id="almaden-setting-style">
											<option value="clear" selected>Clara y directa</option>
											<option value="analytical">Analítica</option>
											<option value="narrative">Narrativa</option>
										</select>
									</div>
								</div>
								<div class="almaden-prompt-actions" style="margin-top: 20px; display: flex; align-items: center; gap: 16px;">
									<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-copy-active-prompt">Copy Prompt</button>
									<p class="almaden-settings-note" style="margin: 0;">Estos controles ya se usan para construir el prompt al copiarlo (no se guardan aún en el backend).</p>
								</div>
								
								<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 28px 0 20px;">
								<h3 style="margin: 0 0 16px; font-size: 16px; font-weight: 600; color: #1e293b; font-family: 'Urbanist', sans-serif;">Reglas del Quiz en el Ebook</h3>
								
								<div class="almaden-grid almaden-grid--settings">
									<div class="almaden-field">
										<label for="almaden-flow-mode">Modo de distribución</label>
										<select id="almaden-flow-mode">
											<option value="every_chapter">Cada capítulo</option>
											<option value="interval">Por intervalos de capítulos</option>
											<option value="custom">Personalizado (Solo donde se asocie)</option>
										</select>
									</div>
									<div class="almaden-field" id="almaden-flow-interval-field" style="display: none;">
										<label for="almaden-flow-interval">Intervalo de capítulos</label>
										<input id="almaden-flow-interval" type="number" min="1" max="50" value="3">
									</div>
									<div class="almaden-field">
										<label for="almaden-flow-mandatory">Obligatoriedad</label>
										<select id="almaden-flow-mandatory">
											<option value="0">Opcional (No bloquea lectura)</option>
											<option value="1">Requerido (Bloquea siguiente capítulo)</option>
										</select>
									</div>
									<div class="almaden-field">
										<label for="almaden-flow-passing-score">Aprobación mínima (%)</label>
										<input id="almaden-flow-passing-score" type="number" min="0" max="100" value="80">
									</div>
								</div>
								
								<div class="almaden-prompt-actions" style="margin-top: 20px; display: flex; align-items: center; gap: 16px;">
									<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-save-flow-settings">Guardar Reglas de Flujo</button>
									<span id="almaden-flow-settings-status" style="font-size: 14px; font-weight: 500; font-family: 'Urbanist', sans-serif;"></span>
								</div>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" data-tab-panel="enter-prompt" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-body">
								<div class="almaden-field">
									<label for="almaden-prompt-input">Pega aquí el JSON o el resultado bruto del LLM</label>
									<textarea id="almaden-prompt-input" placeholder="Pega aquí el prompt con JSON devuelto por el LLM"></textarea>
								</div>
								<div class="almaden-prompt-toolbar">
									<p class="almaden-helper">El sistema intentará extraer y validar el JSON aunque venga acompañado por texto adicional.</p>
									<button type="button" class="almaden-btn almaden-btn--dark" id="almaden-load-prompt">Load</button>
								</div>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" data-tab-panel="quiz-preview" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-body">
								<div class="almaden-slide-empty-state" id="almaden-preview-empty">Aquí se mostrará la vista previa del quiz cargado.</div>
								<div class="almaden-preview-list" id="almaden-preview-list" hidden></div>
								<div class="almaden-preview-toolbar">
									<p class="almaden-helper" id="almaden-preview-summary">No hay un quiz cargado todavía.</p>
									<button type="button" class="almaden-btn almaden-btn--soft" id="almaden-preview-focus">Ir al preview</button>
								</div>
							</div>
						</div>
					</section>

					<section class="almaden-tabpanel" data-tab-panel="chapter-content" role="tabpanel">
						<div class="almaden-card">
							<div class="almaden-card-body">
								<div class="almaden-raw" id="almaden-chapter-raw"><?php echo esc_html( $active_chapter['content'] ); ?></div>
							</div>
						</div>
					</section>
				</div>
			</div>
		</section>
	</main>

	<form id="almaden-book-quiz-save-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<input type="hidden" name="action" value="almaden_save_book_quiz">
		<input type="hidden" name="book_id" value="<?php echo esc_attr( (string) $book_id ); ?>">
		<input type="hidden" name="quiz_id" id="almaden-quiz-id" value="<?php echo esc_attr( (string) $active_quiz_id ); ?>">
		<input type="hidden" name="quiz_payload_json" id="almaden-quiz-payload-json" value="">
		<?php wp_nonce_field( 'almaden_save_book_quiz_' . $book_id ); ?>
	</form>
	<!-- Overlay para Previsualización de Quiz -->
	<div id="almaden-quiz-preview-overlay" class="almaden-quiz-overlay" style="display: none;">
		<div class="almaden-quiz-overlay-backdrop" id="almaden-quiz-preview-close-backdrop"></div>
		<div class="almaden-quiz-overlay-panel">
			<div class="almaden-quiz-overlay-head">
				<h3 class="almaden-quiz-overlay-title">Preview Quiz</h3>
				<button type="button" class="almaden-quiz-overlay-close" id="almaden-quiz-preview-close-btn">&times;</button>
			</div>
			<div class="almaden-quiz-overlay-body learni-learner" id="almaden-quiz-preview-body">
			</div>
		</div>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
