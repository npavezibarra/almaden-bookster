<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = isset( $selected_course_id ) ? (int) $selected_course_id : 0;
$course_post = $editor_state['post'] ?? null;
$status_value = $course_post ? (string) $course_post->post_status : 'draft';
$course_price_value = (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PRICE, true );
$quiz_questions = array();
if ( ! empty( $editor_state['quiz']['questions_json'] ) ) {
	$decoded_quiz_questions = json_decode( (string) $editor_state['quiz']['questions_json'], true );
	if ( is_array( $decoded_quiz_questions ) ) {
		$quiz_questions = $decoded_quiz_questions;
	}
}

$check_items = array(
	array( 'label' => __( 'Title', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_title ) ),
	array( 'label' => __( 'Price', 'almaden-bookster' ), 'done' => (float) $course_price_value > 0 ),
	array( 'label' => __( 'Description', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_content ) ),
	array( 'label' => __( 'Front Image', 'almaden-bookster' ), 'done' => ! empty( $selected_course_card['thumbnail_url'] ?? '' ) ),
	array( 'label' => __( 'Top Banner', 'almaden-bookster' ), 'done' => '' !== (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_BANNER_PHOTO_ID, true ) ),
	array( 'label' => __( 'Excerpt', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_excerpt ) ),
	array( 'label' => __( 'Instructors', 'almaden-bookster' ), 'done' => '' !== trim( (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_COLLABORATORS, true ) ) ),
	array( 'label' => __( 'Lessons', 'almaden-bookster' ), 'done' => ! empty( $editor_state['lessons'] ), 'count' => count( $editor_state['lessons'] ?? array() ) ),
	array( 'label' => __( 'Evaluación', 'almaden-bookster' ), 'done' => ! empty( $editor_state['quiz']['quiz_id'] ), 'count' => count( $quiz_questions ) ),
);
?>
<aside class="space-y-4">
	<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
		<div class="grid gap-3">
			<button type="submit" form="almaden-course-certificate-form" name="course_status" value="draft" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:bg-slate-800">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'Guardar cambios', 'almaden-bookster' ); ?>
			</button>
			<div class="flex items-stretch gap-4">
				<?php $preview_url = function_exists( 'get_preview_post_link' ) ? get_preview_post_link( $course_id ) : get_permalink( $course_id ); ?>
				<a href="<?php echo esc_url( $preview_url ? $preview_url : get_permalink( $course_id ) ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex h-[92px] w-[92px] shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-amber-300 hover:text-slate-950" aria-label="<?php esc_attr_e( 'Vista previa', 'almaden-bookster' ); ?>">
					<span class="dashicons dashicons-visibility text-2xl"></span>
				</a>
				<button type="submit" form="almaden-course-certificate-form" name="course_status" value="publish" class="inline-flex min-h-[92px] flex-1 items-center justify-center rounded-2xl bg-gradient-to-r from-amber-700 to-amber-500 px-4 py-3 text-sm font-semibold uppercase tracking-[0.22em] text-white shadow-sm transition hover:from-amber-800 hover:to-amber-600">
					<?php echo esc_html( 'publish' === $status_value ? __( 'Unpublish', 'almaden-bookster' ) : __( 'Publicar', 'almaden-bookster' ) ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
		<p class="text-xs font-semibold uppercase tracking-[0.42em] text-slate-400"><?php esc_html_e( 'Precio del curso', 'almaden-bookster' ); ?></p>
		<div class="mt-5 flex items-end gap-3 border-b border-slate-200 pb-3">
			<span class="text-4xl font-semibold tracking-tight text-slate-500">$</span>
			<input
				id="almaden-course-sidebar-price"
				type="number"
				min="0"
				step="0.01"
				name="course_price"
				form="almaden-course-certificate-form"
				value="<?php echo esc_attr( '' !== $course_price_value ? $course_price_value : '0' ); ?>"
				class="w-full border-0 bg-transparent p-0 text-5xl font-semibold tracking-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0"
			>
		</div>
	</div>

	<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
		<p class="text-xs font-semibold uppercase tracking-[0.42em] text-slate-400"><?php esc_html_e( 'Checklist: Items to check', 'almaden-bookster' ); ?></p>
		<div class="mt-5 space-y-4">
			<?php foreach ( $check_items as $item ) : ?>
				<div class="flex items-center gap-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm <?php echo ! empty( $item['done'] ) ? 'text-slate-900' : 'text-slate-500'; ?>">
					<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full <?php echo ! empty( $item['done'] ) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'; ?>">
						<?php echo ! empty( $item['done'] ) ? '✓' : '•'; ?>
					</span>
					<span><?php echo esc_html( $item['label'] ); ?></span>
					<?php if ( isset( $item['count'] ) && $item['count'] !== '' ) : ?>
						<span class="ml-auto text-sm font-semibold text-slate-900"><?php echo esc_html( (string) $item['count'] ); ?></span>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</aside>
