<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quiz_state = $editor_state['quiz'] ?? array();
$quiz_id = (int) ( $quiz_state['quiz_id'] ?? 0 );
$quiz_data = is_array( $quiz_state['quiz'] ?? null ) ? $quiz_state['quiz'] : null;
$quiz_questions_json = (string) ( $quiz_state['questions_json'] ?? '' );
$questions = is_array( $quiz_data['questions'] ?? null ) ? array_values( $quiz_data['questions'] ) : json_decode( $quiz_questions_json, true );

if ( ! is_array( $questions ) || empty( $questions ) ) {
	$questions = array(
		array(
			'title' => 'Pregunta 1',
			'question_text' => '',
			'answers' => array(
				array( 'text' => '', 'correct' => true ),
				array( 'text' => '', 'correct' => false ),
			),
		),
	);
}

$course_post = $editor_state['post'] ?? null;
$course_price_value = (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PRICE, true );
$quiz_title_value = (string) ( $quiz_data['title'] ?? ( $selected_course_card['title'] ?? '' ) );
if ( '' === $quiz_title_value && $course_post ) {
	$quiz_title_value = (string) $course_post->post_title;
}

$passing_score_value = (int) ( $quiz_data['passing_score'] ?? 80 );
$time_limit_value = (int) ( $quiz_data['time_limit_seconds'] ?? 0 );
$question_total = count( $questions );
$check_items = array(
	array( 'label' => __( 'Title', 'almaden-bookster' ), 'done' => '' !== trim( (string) $quiz_title_value ) ),
	array( 'label' => __( 'Price', 'almaden-bookster' ), 'done' => (float) $course_price_value > 0 ),
	array( 'label' => __( 'Description', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_content ) ),
	array( 'label' => __( 'Front Image', 'almaden-bookster' ), 'done' => ! empty( $selected_course_card['thumbnail_url'] ?? '' ) ),
	array( 'label' => __( 'Top Banner', 'almaden-bookster' ), 'done' => '' !== (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_BANNER_PHOTO_ID, true ) ),
	array( 'label' => __( 'Excerpt', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_excerpt ) ),
	array( 'label' => __( 'Instructors', 'almaden-bookster' ), 'done' => '' !== trim( (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_COLLABORATORS, true ) ) ),
	array( 'label' => __( 'Lessons', 'almaden-bookster' ), 'done' => ! empty( $editor_state['lessons'] ), 'count' => count( $editor_state['lessons'] ?? array() ) ),
	array( 'label' => __( 'Evaluación', 'almaden-bookster' ), 'done' => $quiz_id > 0, 'count' => $question_total ),
);
?>
<div id="almaden-learni-tab-evaluacion" class="<?php echo 'evaluacion' !== $active_tab ? 'hidden' : ''; ?>">
	<?php if ( ! empty( $_GET['quiz_saved'] ) ) : ?>
		<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
			<?php esc_html_e( 'La evaluación se guardó correctamente.', 'almaden-bookster' ); ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['quiz_deleted'] ) ) : ?>
		<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
			<?php esc_html_e( 'La evaluación se eliminó correctamente.', 'almaden-bookster' ); ?>
		</div>
	<?php endif; ?>

	<form id="almaden-learni-quiz-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-almaden-quiz-form>
		<input type="hidden" name="action" value="almaden_learni_save_course_quiz">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
		<input type="hidden" name="quiz_id" value="<?php echo esc_attr( (string) $quiz_id ); ?>">
		<input type="hidden" name="quiz_title" value="<?php echo esc_attr( $quiz_title_value ); ?>" data-quiz-meta-title>
		<input type="hidden" name="passing_score" value="<?php echo esc_attr( (string) $passing_score_value ); ?>" data-quiz-meta-passing>
		<input type="hidden" name="time_limit_seconds" value="<?php echo esc_attr( (string) $time_limit_value ); ?>" data-quiz-meta-time>
		<?php wp_nonce_field( 'almaden_learni_save_course_quiz_' . (int) $selected_course_id ); ?>

		<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
			<div class="space-y-6">
				<div class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-4 shadow-sm">
					<div class="flex flex-wrap items-center gap-3">
						<div class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold tracking-[0.18em] text-white" data-quiz-counter>
							QUESTION 1 / <?php echo esc_html( (string) max( 1, $question_total ) ); ?>
						</div>
						<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-quiz-prev-question aria-label="<?php esc_attr_e( 'Pregunta anterior', 'almaden-bookster' ); ?>">
							<span class="dashicons dashicons-arrow-left-alt2"></span>
						</button>
						<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-quiz-next-question aria-label="<?php esc_attr_e( 'Siguiente pregunta', 'almaden-bookster' ); ?>">
							<span class="dashicons dashicons-arrow-right-alt2"></span>
						</button>
						<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-quiz-add-question aria-label="<?php esc_attr_e( 'Agregar pregunta', 'almaden-bookster' ); ?>">
							<span class="dashicons dashicons-plus-alt2"></span>
						</button>
						<button type="button" class="inline-flex h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-quiz-ai-toggle aria-expanded="false" aria-controls="almaden-learni-quiz-ai-panel">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true" class="shrink-0">
								<path d="M12 2.5 14.6 8.9 21 11.5l-6.4 2.6L12 20.5l-2.6-6.4L3 11.5l6.4-2.6L12 2.5Z" fill="currentColor"/>
								<path d="M5 15.5l1 2.5 2.5 1-2.5 1-1 2.5-1-2.5-2.5-1 2.5-1 1-2.5Z" fill="currentColor" opacity=".85"/>
							</svg>
							<span>AI</span>
						</button>
						<div class="ml-auto">
							<button type="button" class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 transition hover:border-rose-300 hover:text-rose-700" data-quiz-delete-form-trigger>
								<span class="dashicons dashicons-trash"></span>
								<?php esc_html_e( 'DELETE QUIZ', 'almaden-bookster' ); ?>
							</button>
						</div>
					</div>
				</div>

				<div
					id="almaden-learni-quiz-ai-panel"
					class="hidden rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm"
					data-quiz-ai-panel
					style="overflow:hidden;max-height:0;opacity:0;transform:translateY(-10px);transition:max-height .28s ease,opacity .2s ease,transform .28s ease;"
				>
					<div class="space-y-2">
						<p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-400"><?php esc_html_e( 'AI Assisted', 'almaden-bookster' ); ?></p>
						<p class="text-sm leading-7 text-slate-500"><?php esc_html_e( 'Genera preguntas con un LLM y pega el JSON para importarlas al quiz.', 'almaden-bookster' ); ?></p>
					</div>

					<div class="mt-6 grid gap-4 md:grid-cols-2">
						<div>
							<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Número de preguntas', 'almaden-bookster' ); ?></label>
							<input type="number" min="1" max="100" placeholder="<?php esc_attr_e( 'Número de preguntas', 'almaden-bookster' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" data-quiz-ai-num-questions>
						</div>
						<div>
							<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Respuestas por pregunta', 'almaden-bookster' ); ?></label>
							<input type="number" min="2" max="8" placeholder="<?php esc_attr_e( 'Respuestas por pregunta', 'almaden-bookster' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" data-quiz-ai-answers-per-question>
						</div>
						<div class="md:col-span-2">
							<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Keywords', 'almaden-bookster' ); ?></label>
							<input type="text" placeholder="<?php esc_attr_e( 'Keywords (separadas por coma)', 'almaden-bookster' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" data-quiz-ai-keywords>
						</div>
						<label class="md:col-span-2 flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
							<input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-slate-950 focus:ring-amber-500" data-quiz-ai-upload-docs>
							<span><?php esc_html_e( 'Subiré mi propio material (PDF, texto, etc.) al LLM', 'almaden-bookster' ); ?></span>
						</label>
					</div>

					<div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
						<div class="space-y-4">
							<button type="button" class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-slate-950 px-4 py-4 text-base font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300" disabled data-quiz-ai-copy-prompt>
								<span class="dashicons dashicons-admin-page"></span>
								<span data-quiz-ai-copy-prompt-text><?php esc_html_e( 'Copiar prompt para ChatGPT/Claude', 'almaden-bookster' ); ?></span>
							</button>
							<p class="text-center text-sm leading-6 text-slate-500"><?php esc_html_e( 'Pega el prompt en tu LLM, genera el JSON y luego pégalo aquí para importarlo.', 'almaden-bookster' ); ?></p>
							<button type="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-4 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-quiz-ai-copy-context>
								<?php esc_html_e( 'Copiar contexto (lo que ya existe)', 'almaden-bookster' ); ?>
							</button>
						</div>
					</div>

					<div class="mt-6">
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Pega aquí el JSON resultante...', 'almaden-bookster' ); ?></label>
						<textarea rows="10" class="w-full rounded-[1.5rem] border border-slate-200 bg-white px-4 py-4 text-sm leading-7 outline-none placeholder:text-slate-300 focus:border-amber-500" placeholder="<?php esc_attr_e( 'Pega aquí el JSON resultante...', 'almaden-bookster' ); ?>" data-quiz-ai-json-paste></textarea>
					</div>

					<div class="mt-5 flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">
						<input type="checkbox" class="h-5 w-5 rounded border-slate-300 text-slate-950 focus:ring-amber-500" data-quiz-ai-replace-existing>
						<span><?php esc_html_e( 'Reemplazar preguntas existentes (borra lo actual)', 'almaden-bookster' ); ?></span>
					</div>

					<div class="mt-6 space-y-3">
						<button type="button" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-300 px-4 py-4 text-sm font-semibold uppercase tracking-[0.22em] text-slate-500 transition disabled:cursor-not-allowed" disabled data-quiz-ai-import>
							<?php esc_html_e( 'Importar JSON', 'almaden-bookster' ); ?>
						</button>
						<p class="text-sm leading-6 text-slate-500" data-quiz-ai-status></p>
						<p class="text-sm leading-6 text-slate-500"><?php esc_html_e( 'Por defecto, Importar agrega nuevas preguntas al final.', 'almaden-bookster' ); ?></p>
					</div>
				</div>

				<input type="hidden" name="quiz_questions_json" value="<?php echo esc_attr( $quiz_questions_json ); ?>" data-quiz-json>
				<div class="space-y-5" data-quiz-editor data-quiz-questions="<?php echo esc_attr( $quiz_questions_json ); ?>" data-active-question-index="0">
					<?php foreach ( $questions as $question_index => $question ) :
						$answers = isset( $question['answers'] ) && is_array( $question['answers'] ) ? array_values( $question['answers'] ) : array();
						if ( empty( $answers ) ) {
							$answers = array(
								array( 'text' => '', 'correct' => true ),
								array( 'text' => '', 'correct' => false ),
							);
						}
						?>
						<article class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm" data-quiz-question data-question-index="<?php echo esc_attr( (string) $question_index ); ?>" <?php echo 0 === $question_index ? '' : 'hidden'; ?>>
							<div class="flex flex-wrap items-start justify-between gap-4">
								<div>
									<p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-400"><?php esc_html_e( 'PREGUNTA', 'almaden-bookster' ); ?></p>
									<h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950" data-quiz-question-title><?php echo esc_html( 'Pregunta ' . ( $question_index + 1 ) ); ?></h3>
								</div>
								<div class="flex items-center gap-2">
									<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-quiz-question-preview aria-label="<?php esc_attr_e( 'Preview', 'almaden-bookster' ); ?>">
										<span class="dashicons dashicons-visibility"></span>
									</button>
									<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-quiz-question-settings aria-label="<?php esc_attr_e( 'Configuración', 'almaden-bookster' ); ?>">
										<span class="dashicons dashicons-admin-generic"></span>
									</button>
									<button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-rose-200 hover:text-rose-600" data-quiz-question-remove aria-label="<?php esc_attr_e( 'Eliminar pregunta', 'almaden-bookster' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</button>
								</div>
							</div>

							<div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
								<textarea class="min-h-[6rem] w-full resize-none border-0 bg-transparent p-0 text-3xl leading-[1.35] tracking-tight text-slate-700 outline-none placeholder:text-slate-300 focus:ring-0" rows="3" placeholder="<?php esc_attr_e( 'Texto de la pregunta', 'almaden-bookster' ); ?>" data-quiz-question-text><?php echo esc_textarea( (string) ( $question['question_text'] ?? '' ) ); ?></textarea>
							</div>

							<div class="mt-6 flex items-center justify-between gap-4">
								<p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400">
									<?php esc_html_e( 'Respuestas', 'almaden-bookster' ); ?>
									<span class="normal-case tracking-normal text-slate-500">(<?php esc_html_e( 'Marca la casilla para las respuestas correctas', 'almaden-bookster' ); ?>)</span>
								</p>
								<button type="button" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500 transition hover:text-slate-950" data-quiz-add-answer>
									+ ADD ANSWER
								</button>
							</div>

							<div class="mt-4 space-y-3" data-quiz-answer-list>
								<?php foreach ( $answers as $answer_index => $answer ) : ?>
									<div class="flex flex-col gap-3 rounded-[1.5rem] border <?php echo ! empty( $answer['correct'] ) ? 'border-amber-300 bg-amber-50/60' : 'border-slate-200 bg-white'; ?> px-4 py-4 md:flex-row md:items-center" data-quiz-answer>
										<label class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400">
											<input type="checkbox" class="sr-only" data-quiz-answer-correct <?php checked( ! empty( $answer['correct'] ) ); ?>>
											<span class="text-lg leading-none <?php echo ! empty( $answer['correct'] ) ? 'text-slate-950' : ''; ?>">✓</span>
										</label>
										<input type="text" class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-lg text-slate-900 outline-none placeholder:text-slate-300 focus:border-amber-500 focus:ring-0" placeholder="<?php esc_attr_e( 'Respuesta', 'almaden-bookster' ); ?>" data-quiz-answer-text value="<?php echo esc_attr( (string) ( $answer['text'] ?? '' ) ); ?>">
										<button type="button" class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-400 transition hover:border-amber-300 hover:text-amber-700" data-quiz-answer-image aria-label="<?php esc_attr_e( 'Imagen de respuesta', 'almaden-bookster' ); ?>">
											<span class="dashicons dashicons-format-image"></span>
										</button>
										<button type="button" class="inline-flex h-11 items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-rose-200 hover:text-rose-600" data-quiz-answer-remove>
											<?php esc_html_e( 'Eliminar', 'almaden-bookster' ); ?>
										</button>
									</div>
								<?php endforeach; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>

				<p class="text-sm text-slate-500">
					<?php esc_html_e( 'Puedes agregar preguntas y respuestas desde esta pantalla. El JSON se genera automáticamente al guardar.', 'almaden-bookster' ); ?>
				</p>
			</div>

			<aside class="space-y-4">
				<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<div class="grid gap-3">
						<button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
							<?php esc_html_e( 'Guardar cambios', 'almaden-bookster' ); ?>
						</button>
						<button type="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-500" disabled>
							<span class="mr-2 dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Vista previa', 'almaden-bookster' ); ?>
						</button>
						<button type="submit" name="quiz_status" value="publish" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-amber-700 to-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-amber-800 hover:to-amber-600">
							<?php echo $quiz_id > 0 ? esc_html__( 'Unpublish', 'almaden-bookster' ) : esc_html__( 'Publicar', 'almaden-bookster' ); ?>
						</button>
					</div>
				</div>

				<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<p class="text-xs font-semibold uppercase tracking-[0.42em] text-slate-400"><?php esc_html_e( 'PRECIO DEL CURSO', 'almaden-bookster' ); ?></p>
					<div class="mt-5 flex items-baseline gap-3">
						<span class="text-2xl font-semibold text-slate-400">$</span>
						<input
							type="text"
							value="<?php echo esc_attr( number_format_i18n( (float) $course_price_value, 0 ) ); ?>"
							readonly
							class="w-full border-0 border-b border-slate-200 bg-transparent px-0 py-2 text-4xl font-semibold tracking-tight text-slate-950 outline-none focus:ring-0"
							aria-label="<?php esc_attr_e( 'Precio del curso', 'almaden-bookster' ); ?>"
						>
					</div>
					<div class="mt-4 border-t border-slate-200 pt-3 text-sm font-semibold uppercase tracking-[0.32em] text-slate-300">
						<?php echo esc_html( (float) $course_price_value > 0 ? number_format_i18n( (float) $course_price_value, 0 ) : __( 'Gratis', 'almaden-bookster' ) ); ?>
					</div>
				</div>

				<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<p class="text-xs font-semibold uppercase tracking-[0.42em] text-slate-400"><?php esc_html_e( 'CHECKLIST: ITEMS TO CHECK', 'almaden-bookster' ); ?></p>
					<div class="mt-5 space-y-4">
						<?php foreach ( $check_items as $item ) : ?>
							<div class="flex items-center justify-between gap-4 text-sm <?php echo ! empty( $item['done'] ) ? 'text-slate-700' : 'text-slate-400'; ?>">
								<div class="flex items-center gap-3">
									<span class="h-2.5 w-2.5 rounded-full <?php echo ! empty( $item['done'] ) ? 'bg-emerald-500' : 'bg-slate-200'; ?>"></span>
									<span class="font-semibold"><?php echo esc_html( $item['label'] ); ?></span>
								</div>
								<?php if ( isset( $item['count'] ) ) : ?>
									<span class="font-semibold text-slate-900"><?php echo esc_html( (string) (int) $item['count'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</aside>
		</div>
	</form>

	<form id="almaden-learni-quiz-delete-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" hidden>
		<input type="hidden" name="action" value="almaden_learni_delete_course_quiz">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
		<input type="hidden" name="quiz_id" value="<?php echo esc_attr( (string) $quiz_id ); ?>">
		<?php wp_nonce_field( 'almaden_learni_delete_course_quiz_' . (int) $selected_course_id ); ?>
	</form>
</div>
