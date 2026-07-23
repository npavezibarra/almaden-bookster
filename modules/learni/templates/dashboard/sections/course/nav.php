<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_tab = isset( $active_tab ) ? sanitize_key( (string) $active_tab ) : 'curso';
?>
<div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-center lg:justify-between">
	<div class="flex items-center gap-3">
		<a href="<?php echo esc_url( $course_url ); ?>" class="inline-flex h-[38px] w-[38px] items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
			<span class="dashicons dashicons-arrow-left-alt2"></span>
		</a>
	<div>
		<h2 class="mt-1 text-xl font-semibold text-slate-950"><?php echo esc_html( $selected_course_card['title'] ?? '' ); ?></h2>
	</div>
</div>
	<div class="flex flex-wrap gap-2">
		<?php foreach ( array( 'curso' => __( 'Curso', 'almaden-bookster' ), 'lecciones' => __( 'Lecciones', 'almaden-bookster' ), 'evaluacion' => __( 'Evaluación', 'almaden-bookster' ), 'certificado' => __( 'Certificado', 'almaden-bookster' ), 'meta' => __( 'Meta', 'almaden-bookster' ) ) as $tab_key => $tab_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'course_id' => (int) $selected_course_id, 'tab' => $tab_key ), $course_url ) ); ?>" class="rounded-2xl px-4 py-2.5 text-sm font-semibold transition <?php echo $active_tab === $tab_key ? 'bg-slate-950 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-500 hover:border-amber-300 hover:text-slate-950'; ?>">
				<?php echo esc_html( $tab_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</div>
