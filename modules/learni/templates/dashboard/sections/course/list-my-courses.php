<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$courses = isset( $courses ) && is_array( $courses ) ? $courses : array();
$course_url = isset( $course_url ) ? (string) $course_url : ( function_exists( 'almaden_bookster_get_course_archive_page_url' ) ? almaden_bookster_get_course_archive_page_url() : home_url( '/' ) );
?>
<?php if ( ! empty( $courses ) ) : ?>
	<div class="grid gap-5 md:grid-cols-2 2xl:grid-cols-3">
		<?php foreach ( $courses as $course ) : ?>
			<?php
			$course_id = isset( $course['id'] ) ? (int) $course['id'] : 0;
			$is_selected = $selected_course_id && $course_id === (int) $selected_course_id;
			$card_url = add_query_arg( array( 'course_id' => $course_id, 'tab' => 'curso' ), $course_url );
			?>
			<a href="<?php echo esc_url( $card_url ); ?>" class="almaden-learni-course-card group overflow-hidden rounded-[1.6rem] border bg-white shadow-sm transition <?php echo $is_selected ? 'border-amber-300 ring-2 ring-amber-100' : 'border-slate-200'; ?>">
				<div class="relative aspect-[16/9] bg-slate-100">
					<span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600 shadow-sm">
						<?php echo esc_html( $course['lesson_count'] ); ?> <?php esc_html_e( 'lecciones', 'almaden-bookster' ); ?>
					</span>
					<?php if ( ! empty( $course['thumbnail_url'] ) ) : ?>
						<img src="<?php echo esc_url( $course['thumbnail_url'] ); ?>" alt="" class="h-full w-full object-cover">
					<?php else : ?>
						<div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-900 to-slate-700 text-white">
							<span class="text-sm font-semibold uppercase tracking-[0.3em] text-white/70"><?php esc_html_e( 'Curso', 'almaden-bookster' ); ?></span>
						</div>
					<?php endif; ?>
				</div>
				<div class="space-y-4 p-5">
					<div>
						<p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php echo esc_html( $course['status_label'] ); ?></p>
						<h3 class="mt-2 text-[1.35rem] font-semibold leading-tight text-slate-950 group-hover:text-amber-700">
							<?php echo esc_html( $course['title'] ); ?>
						</h3>
					</div>
					<p class="min-h-[4.5rem] text-sm leading-6 text-slate-500">
						<?php echo esc_html( $course['excerpt'] ); ?>
					</p>
					<div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-4">
						<div class="space-y-1">
							<p class="text-sm font-semibold text-slate-950"><?php echo esc_html( $course['price_label'] ); ?></p>
							<p class="text-xs uppercase tracking-[0.18em] text-slate-400"><?php echo esc_html( $course['updated'] ); ?></p>
						</div>
						<span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-900">
							<?php esc_html_e( 'Editar', 'almaden-bookster' ); ?>
						</span>
					</div>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/70 p-10 text-center">
		<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
			<span class="dashicons dashicons-welcome-add-page"></span>
		</div>
		<h3 class="mt-4 text-xl font-semibold text-slate-950"><?php esc_html_e( 'Todavía no tienes cursos', 'almaden-bookster' ); ?></h3>
		<p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">
			<?php esc_html_e( 'Usa el panel de la derecha para crear tu primer curso. Después aparecerá aquí como una tarjeta editable.', 'almaden-bookster' ); ?>
		</p>
	</div>
<?php endif; ?>
