<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quiz_state = $editor_state['quiz'] ?? array();
$quiz_id = (int) ( $quiz_state['quiz_id'] ?? 0 );
$quiz_data = $quiz_state['quiz'] ?? null;
$quiz_questions_json = (string) ( $quiz_state['questions_json'] ?? '' );
?>
<div id="almaden-learni-tab-evaluacion" class="<?php echo 'evaluacion' !== $active_tab ? 'hidden' : ''; ?>">
	<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-5" data-almaden-quiz-form>
			<input type="hidden" name="action" value="almaden_learni_save_course_quiz">
			<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
			<input type="hidden" name="quiz_id" value="<?php echo esc_attr( (string) $quiz_id ); ?>">
			<?php wp_nonce_field( 'almaden_learni_save_course_quiz_' . (int) $selected_course_id ); ?>
			<div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
				<div class="flex items-start justify-between gap-4">
					<div>
						<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Editor visual', 'almaden-bookster' ); ?></p>
						<h3 class="mt-1 text-xl font-semibold text-slate-950"><?php echo $quiz_id > 0 ? esc_html__( 'Actualizar evaluación', 'almaden-bookster' ) : esc_html__( 'Crear evaluación', 'almaden-bookster' ); ?></h3>
					</div>
					<button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-quiz-add-question>
						<?php esc_html_e( 'Agregar pregunta', 'almaden-bookster' ); ?>
					</button>
				</div>
				<div class="mt-5 grid gap-4 md:grid-cols-3">
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Título del quiz', 'almaden-bookster' ); ?></label>
					<input type="text" name="quiz_title" value="<?php echo esc_attr( $quiz_data['title'] ?? '' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
				</div>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Passing score', 'almaden-bookster' ); ?></label>
					<input type="number" name="passing_score" min="0" max="100" value="<?php echo esc_attr( (string) ( $quiz_data['passing_score'] ?? 80 ) ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
				</div>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Tiempo límite (seg)', 'almaden-bookster' ); ?></label>
					<input type="number" name="time_limit_seconds" min="0" value="<?php echo esc_attr( (string) ( $quiz_data['time_limit_seconds'] ?? 0 ) ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
				</div>
				</div>
			</div>
			<div>
				<input type="hidden" name="quiz_questions_json" value="<?php echo esc_attr( $quiz_questions_json ); ?>" data-quiz-json>
				<div class="space-y-4" data-quiz-editor data-quiz-questions="<?php echo esc_attr( $quiz_questions_json ); ?>">
					<?php
					$questions = json_decode( $quiz_questions_json, true );
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
					foreach ( $questions as $question_index => $question ) :
						$answers = isset( $question['answers'] ) && is_array( $question['answers'] ) ? $question['answers'] : array();
						if ( empty( $answers ) ) {
							$answers = array(
								array( 'text' => '', 'correct' => true ),
								array( 'text' => '', 'correct' => false ),
							);
						}
						?>
						<article class="almaden-learni-question rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
							<div class="almaden-learni-question__head flex items-start justify-between gap-4">
								<div>
									<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Pregunta', 'almaden-bookster' ); ?></p>
									<h4 class="mt-1 text-lg font-semibold text-slate-950"><?php echo esc_html( 'Pregunta ' . ( $question_index + 1 ) ); ?></h4>
								</div>
								<button type="button" class="almaden-learni-mini-btn almaden-learni-question__remove rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
									<?php esc_html_e( 'Eliminar', 'almaden-bookster' ); ?>
								</button>
							</div>
							<div class="almaden-learni-question__body mt-4 space-y-4">
								<input type="text" class="almaden-learni-input almaden-learni-question__name w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" placeholder="<?php esc_attr_e( 'Título de la pregunta', 'almaden-bookster' ); ?>" value="<?php echo esc_attr( (string) ( $question['title'] ?? '' ) ); ?>">
								<textarea class="almaden-learni-textarea almaden-learni-question__text w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" rows="4" placeholder="<?php esc_attr_e( 'Texto de la pregunta', 'almaden-bookster' ); ?>"><?php echo esc_textarea( (string) ( $question['question_text'] ?? '' ) ); ?></textarea>
								<div class="almaden-learni-answer-list space-y-3">
									<?php foreach ( $answers as $answer ) : ?>
										<div class="almaden-learni-answer flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 md:flex-row md:items-center">
											<input type="text" class="almaden-learni-answer__text min-w-0 flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500" placeholder="<?php esc_attr_e( 'Respuesta', 'almaden-bookster' ); ?>" value="<?php echo esc_attr( (string) ( $answer['text'] ?? '' ) ); ?>">
											<label class="almaden-learni-answer__correct inline-flex items-center gap-2 text-sm font-medium text-slate-700">
												<input type="checkbox" class="almaden-learni-answer__check" <?php checked( ! empty( $answer['correct'] ) ); ?>>
												<span><?php esc_html_e( 'Correcta', 'almaden-bookster' ); ?></span>
											</label>
											<button type="button" class="almaden-learni-mini-btn almaden-learni-answer__remove rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
												<?php esc_html_e( 'Eliminar', 'almaden-bookster' ); ?>
											</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" class="almaden-learni-mini-btn almaden-learni-question__add-answer rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
									<?php esc_html_e( 'Agregar respuesta', 'almaden-bookster' ); ?>
								</button>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="mt-3 text-xs text-slate-500"><?php esc_html_e( 'Puedes agregar preguntas y respuestas desde esta pantalla. El JSON se genera automáticamente al guardar.', 'almaden-bookster' ); ?></p>
			</div>
			<button type="submit" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
				<?php echo $quiz_id > 0 ? esc_html__( 'Actualizar quiz', 'almaden-bookster' ) : esc_html__( 'Crear quiz', 'almaden-bookster' ); ?>
			</button>
		</form>

		<div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Estado', 'almaden-bookster' ); ?></p>
			<h3 class="mt-1 text-lg font-semibold text-slate-950"><?php echo $quiz_id > 0 ? esc_html__( 'Quiz conectado', 'almaden-bookster' ) : esc_html__( 'Sin quiz', 'almaden-bookster' ); ?></h3>
			<p class="mt-3 text-sm leading-6 text-slate-500"><?php esc_html_e( 'Esta pantalla usa el repositorio nativo para crear o actualizar la evaluación del curso. En la siguiente iteración podremos reemplazar el textarea por un editor visual.', 'almaden-bookster' ); ?></p>
		</div>
	</div>
</div>
