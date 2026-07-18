<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $active_tab ) ? sanitize_key( (string) $active_tab ) : 'curso';
?>
<div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-center lg:justify-between">
	<div class="flex items-center gap-3">
		<a href="<?php echo esc_url( $course_url ); ?>" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
			<?php esc_html_e( 'Volver', 'almaden-bookster' ); ?>
		</a>
		<div>
			<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Editor de curso', 'almaden-bookster' ); ?></p>
			<h2 class="text-xl font-semibold text-slate-950"><?php echo esc_html( $selected_course_card['title'] ?? '' ); ?></h2>
		</div>
	</div>
	<div class="flex flex-wrap gap-2">
		<?php foreach ( array( 'curso' => __( 'Curso', 'almaden-bookster' ), 'lecciones' => __( 'Lecciones', 'almaden-bookster' ), 'evaluacion' => __( 'Evaluación', 'almaden-bookster' ), 'meta' => __( 'Meta', 'almaden-bookster' ) ) as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'course_id' => (int) $selected_course_id, 'tab' => $tab_key ), $course_url ) ); ?>" class="rounded-full px-4 py-2 text-sm font-medium transition <?php echo $active_tab === $tab_key ? 'bg-slate-950 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-amber-300 hover:text-slate-950'; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
