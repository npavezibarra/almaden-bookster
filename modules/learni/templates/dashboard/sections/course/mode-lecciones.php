<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$lessons = $editor_state['lessons'] ?? array();
?>
<div id="almaden-learni-tab-lecciones" class="<?php echo 'lecciones' !== $active_tab ? 'hidden' : ''; ?>">
	<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
		<div class="space-y-4">
			<?php if ( ! empty( $lessons ) ) : ?>
				<?php foreach ( $lessons as $lesson ) : ?>
					<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
						<div class="flex items-start justify-between gap-4">
							<div>
								<h3 class="text-lg font-semibold text-slate-950"><?php echo esc_html( $lesson['title'] ); ?></h3>
								<p class="text-xs uppercase tracking-[0.2em] text-slate-400"><?php echo esc_html( $lesson['available_at'] ?: __( 'Sin fecha', 'almaden-bookster' ) ); ?></p>
							</div>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="almaden_learni_delete_lesson">
								<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
								<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson['id'] ); ?>">
								<?php wp_nonce_field( 'almaden_learni_delete_lesson_' . (int) $selected_course_id ); ?>
								<button type="submit" class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
									<?php esc_html_e( 'Eliminar', 'almaden-bookster' ); ?>
								</button>
							</form>
						</div>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-4 grid gap-4 lg:grid-cols-2">
							<input type="hidden" name="action" value="almaden_learni_save_lesson">
							<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
							<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson['id'] ); ?>">
							<?php wp_nonce_field( 'almaden_learni_save_lesson_' . (int) $selected_course_id ); ?>
							<div>
								<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Título', 'almaden-bookster' ); ?></label>
								<input type="text" name="lesson_title" value="<?php echo esc_attr( $lesson['title'] ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
							</div>
							<div>
								<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Orden', 'almaden-bookster' ); ?></label>
								<input type="number" name="lesson_menu_order" value="<?php echo esc_attr( (string) $lesson['menu_order'] ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
							</div>
							<div class="lg:col-span-2">
								<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Contenido', 'almaden-bookster' ); ?></label>
								<textarea name="lesson_content" rows="6" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500"><?php echo esc_textarea( $lesson['content'] ); ?></textarea>
							</div>
							<div>
								<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'URL de video', 'almaden-bookster' ); ?></label>
								<input type="url" name="lesson_video_url" value="<?php echo esc_attr( $lesson['video_url'] ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
							</div>
							<div>
								<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Disponible desde', 'almaden-bookster' ); ?></label>
								<input type="date" name="lesson_available_at" value="<?php echo esc_attr( $lesson['available_at'] ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
							</div>
							<div class="lg:col-span-2">
								<button type="submit" class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
									<?php esc_html_e( 'Guardar lección', 'almaden-bookster' ); ?>
								</button>
							</div>
						</form>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-8 text-sm text-slate-500">
					<?php esc_html_e( 'Aún no hay lecciones. Usa el formulario para crear la primera.', 'almaden-bookster' ); ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
			<h3 class="text-lg font-semibold text-slate-950"><?php esc_html_e( 'Nueva lección', 'almaden-bookster' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-4 space-y-4">
				<input type="hidden" name="action" value="almaden_learni_save_lesson">
				<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
				<input type="hidden" name="lesson_id" value="0">
				<?php wp_nonce_field( 'almaden_learni_save_lesson_' . (int) $selected_course_id ); ?>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Título', 'almaden-bookster' ); ?></label>
					<input type="text" name="lesson_title" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
				</div>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Contenido', 'almaden-bookster' ); ?></label>
					<textarea name="lesson_content" rows="8" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500"></textarea>
				</div>
				<div class="grid grid-cols-2 gap-3">
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Orden', 'almaden-bookster' ); ?></label>
						<input type="number" name="lesson_menu_order" value="0" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
					</div>
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Video', 'almaden-bookster' ); ?></label>
						<input type="url" name="lesson_video_url" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
					</div>
				</div>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Disponible desde', 'almaden-bookster' ); ?></label>
					<input type="date" name="lesson_available_at" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
				</div>
				<button type="submit" class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
					<?php esc_html_e( 'Agregar lección', 'almaden-bookster' ); ?>
				</button>
			</form>
		</div>
	</div>
</div>
