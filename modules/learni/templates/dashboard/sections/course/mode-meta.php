<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="almaden-learni-tab-meta" class="<?php echo 'meta' !== $active_tab ? 'hidden' : ''; ?>">
	<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
		<div class="rounded-3xl border border-slate-200 bg-white p-5">
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'ID curso', 'almaden-bookster' ); ?></p>
			<p class="mt-2 text-2xl font-semibold text-slate-950"><?php echo esc_html( (string) $selected_course_id ); ?></p>
		</div>
		<div class="rounded-3xl border border-slate-200 bg-white p-5">
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Lecciones', 'almaden-bookster' ); ?></p>
			<p class="mt-2 text-2xl font-semibold text-slate-950"><?php echo esc_html( (string) count( $editor_state['lessons'] ?? array() ) ); ?></p>
		</div>
		<div class="rounded-3xl border border-slate-200 bg-white p-5">
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Quiz', 'almaden-bookster' ); ?></p>
			<p class="mt-2 text-2xl font-semibold text-slate-950"><?php echo ! empty( $editor_state['quiz']['quiz_id'] ) ? esc_html__( 'Activo', 'almaden-bookster' ) : esc_html__( 'Pendiente', 'almaden-bookster' ); ?></p>
		</div>
		<div class="rounded-3xl border border-slate-200 bg-white p-5">
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Autor', 'almaden-bookster' ); ?></p>
			<?php $course_author_id = $selected_course ? (int) $selected_course->post_author : 0; ?>
			<p class="mt-2 text-base font-semibold text-slate-950"><?php echo esc_html( get_the_author_meta( 'display_name', $course_author_id ) ); ?></p>
		</div>
	</div>
</div>
