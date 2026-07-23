<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$outline_items = isset( $editor_state['outline'] ) && is_array( $editor_state['outline'] ) ? $editor_state['outline'] : array();
$linear_value  = (int) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_LINEAR_ORDER, true );
$course_post = $editor_state['post'] ?? null;
$status_value = $course_post ? $course_post->post_status : 'draft';
$payment_mode = (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PAYMENT_MODE, true );
if ( '' === $payment_mode ) {
	$payment_mode = 'woocommerce';
}
$price_value = (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PRICE, true );
$cover_photo_id = (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_COVER_PHOTO_ID, true );
$banner_photo_id = (string) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_BANNER_PHOTO_ID, true );
$cover_photo_url = $cover_photo_id !== '' ? wp_get_attachment_image_url( (int) $cover_photo_id, 'medium_large' ) : '';
$banner_photo_url = $banner_photo_id !== '' ? wp_get_attachment_image_url( (int) $banner_photo_id, 'medium_large' ) : '';
$cover_preview = $selected_course_card['thumbnail_url'] ?? '';
$cover_has_preview = $cover_photo_url || $cover_preview;
$check_items = array(
	array( 'label' => __( 'Título definido', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_title ) ),
	array( 'label' => __( 'Descripción lista', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_content ) ),
	array( 'label' => __( 'Extracto listo', 'almaden-bookster' ), 'done' => ! empty( $course_post ) && '' !== trim( (string) $course_post->post_excerpt ) ),
	array( 'label' => __( 'Precio cargado', 'almaden-bookster' ), 'done' => (float) $price_value > 0 ),
	array( 'label' => __( 'Portada cargada', 'almaden-bookster' ), 'done' => '' !== $cover_photo_id || ! empty( $cover_preview ) ),
	array( 'label' => __( 'Lecciones creadas', 'almaden-bookster' ), 'done' => ! empty( $editor_state['lessons'] ) ),
	array( 'label' => __( 'Quiz conectado', 'almaden-bookster' ), 'done' => ! empty( $editor_state['quiz']['quiz_id'] ) ),
	array( 'label' => __( 'Certificado completo', 'almaden-bookster' ), 'done' => ! empty( $editor_state['certificate']['title'] ) && ! empty( $editor_state['certificate']['message'] ) ),
);
?>
<div id="almaden-learni-tab-lecciones" class="<?php echo 'lecciones' !== $active_tab ? 'hidden' : ''; ?>">
	<?php if ( ! empty( $_GET['outline_order_saved'] ) ) : ?>
		<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
			<?php esc_html_e( 'El orden del contenido se guardó correctamente.', 'almaden-bookster' ); ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['lesson_saved'] ) ) : ?>
		<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
			<?php esc_html_e( 'La lección se guardó correctamente.', 'almaden-bookster' ); ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['section_created'] ) || ! empty( $_GET['section_saved'] ) ) : ?>
		<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
			<?php esc_html_e( 'La sección se guardó correctamente.', 'almaden-bookster' ); ?>
		</div>
	<?php endif; ?>

	<div class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
		<div class="flex flex-wrap items-center justify-between gap-4">
			<div>
				<p class="text-xs uppercase tracking-[0.28em] text-slate-400"><?php esc_html_e( 'Editor de curso', 'almaden-bookster' ); ?></p>
				<h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl"><?php esc_html_e( 'Lecciones del curso', 'almaden-bookster' ); ?></h2>
			</div>

			<div class="flex flex-wrap items-center gap-3">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-3">
					<input type="hidden" name="action" value="almaden_learni_save_course">
					<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
					<?php wp_nonce_field( 'almaden_learni_save_course_' . (int) $selected_course_id ); ?>
					<label class="flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">
						<span><?php esc_html_e( 'Flujo libre', 'almaden-bookster' ); ?></span>
						<input type="checkbox" name="course_linear_order" value="1" <?php checked( $linear_value, 1 ); ?> onchange="this.form.submit()">
					</label>
				</form>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="almaden_learni_create_outline_section">
					<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
					<input type="hidden" name="section_label" value="<?php esc_attr_e( 'Sección', 'almaden-bookster' ); ?>">
					<?php wp_nonce_field( 'almaden_learni_create_outline_section_' . (int) $selected_course_id ); ?>
					<button type="submit" class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
						<?php esc_html_e( 'Añadir sección', 'almaden-bookster' ); ?>
					</button>
				</form>

				<button
					type="button"
					class="rounded-full bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
					data-almaden-toggle-lesson-creator
				>
					<?php esc_html_e( 'Añadir', 'almaden-bookster' ); ?>
				</button>
			</div>
		</div>

		<div id="almaden-lesson-creator-panel" class="mt-5 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5" hidden>
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div>
					<p class="text-sm font-semibold text-slate-950"><?php esc_html_e( 'Nueva lección', 'almaden-bookster' ); ?></p>
					<p class="mt-1 text-sm text-slate-500"><?php esc_html_e( 'Se guardará como borrador y quedará lista para ordenar dentro del curso.', 'almaden-bookster' ); ?></p>
				</div>
				<button type="button" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-toggle-lesson-creator>
					<?php esc_html_e( 'Cerrar', 'almaden-bookster' ); ?>
				</button>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mt-5 grid gap-4">
				<input type="hidden" name="action" value="almaden_learni_save_lesson">
				<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
				<input type="hidden" name="lesson_id" value="0">
				<?php wp_nonce_field( 'almaden_learni_save_lesson_' . (int) $selected_course_id ); ?>
				<div class="grid gap-4 lg:grid-cols-2">
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Título', 'almaden-bookster' ); ?></label>
						<input type="text" name="lesson_title" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'Título de la lección', 'almaden-bookster' ); ?>">
					</div>
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Disponible desde', 'almaden-bookster' ); ?></label>
						<input type="date" name="lesson_available_at" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500">
					</div>
				</div>
				<div>
					<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Contenido', 'almaden-bookster' ); ?></label>
					<textarea name="lesson_content" rows="6" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'Escribe el contenido de la lección...', 'almaden-bookster' ); ?>"></textarea>
				</div>
				<div class="grid gap-4 lg:grid-cols-2">
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'URL de video', 'almaden-bookster' ); ?></label>
						<input type="url" name="lesson_video_url" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'https://youtube.com/... o link de video', 'almaden-bookster' ); ?>">
					</div>
					<div>
						<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Orden inicial', 'almaden-bookster' ); ?></label>
						<input type="number" name="lesson_menu_order" value="0" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500">
					</div>
				</div>
				<div class="flex justify-end">
					<button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
						<?php esc_html_e( 'Crear lección', 'almaden-bookster' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
		<div class="space-y-4" data-almaden-outline-sortable data-course-id="<?php echo esc_attr( (string) $selected_course_id ); ?>" data-order-action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>" data-order-nonce="<?php echo esc_attr( wp_create_nonce( 'almaden_learni_save_outline_order_' . (int) $selected_course_id ) ); ?>">
				<?php if ( ! empty( $outline_items ) ) : ?>
					<?php foreach ( $outline_items as $item ) : ?>
						<?php if ( 'section' === ( $item['type'] ?? '' ) ) : ?>
							<div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm" data-almaden-outline-item data-outline-item-type="section" data-outline-item-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
								<div class="flex items-center gap-4">
									<button type="button" draggable="true" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-amber-300 hover:text-amber-700" aria-label="<?php esc_attr_e( 'Arrastrar sección', 'almaden-bookster' ); ?>" data-almaden-lesson-drag-handle>
										<span class="dashicons dashicons-menu"></span>
									</button>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex min-w-0 flex-1 items-center gap-3">
										<input type="hidden" name="action" value="almaden_learni_save_outline_section">
										<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
										<input type="hidden" name="outline_item_id" value="<?php echo esc_attr( (string) $item['id'] ); ?>">
										<?php wp_nonce_field( 'almaden_learni_save_outline_section_' . (int) $selected_course_id ); ?>
										<input
											type="text"
											name="section_label"
											value="<?php echo esc_attr( (string) ( $item['label'] ?? '' ) ); ?>"
											class="min-w-0 flex-1 border-0 bg-transparent px-0 py-0 text-2xl font-semibold tracking-tight text-slate-950 outline-none focus:ring-0"
										>
										<button type="submit" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950">
											<?php esc_html_e( 'Guardar', 'almaden-bookster' ); ?>
										</button>
									</form>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="almaden_learni_delete_outline_section">
										<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
										<input type="hidden" name="outline_item_id" value="<?php echo esc_attr( (string) $item['id'] ); ?>">
										<?php wp_nonce_field( 'almaden_learni_delete_outline_section_' . (int) $selected_course_id ); ?>
										<button type="submit" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-rose-200 hover:text-rose-600" aria-label="<?php esc_attr_e( 'Eliminar sección', 'almaden-bookster' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</form>
								</div>
							</div>
						<?php else : ?>
							<?php
							$lesson = isset( $item['lesson'] ) && is_array( $item['lesson'] ) ? $item['lesson'] : array();
							$lesson_id = (int) ( $lesson['id'] ?? $item['ref_id'] ?? 0 );
							$lesson_form_id = 'almaden-lesson-form-' . $lesson_id;
							$lesson_panel_id = 'almaden-lesson-panel-' . $lesson_id;
							?>
							<div class="rounded-[1.75rem] border border-slate-200 bg-white shadow-sm almaden-learni-lesson-card" data-almaden-outline-item data-almaden-lesson-card data-outline-item-type="lesson" data-outline-item-id="<?php echo esc_attr( (string) $lesson_id ); ?>">
								<div class="flex items-center gap-4 p-4 sm:p-5">
									<div class="flex min-w-0 flex-1 items-center gap-3">
										<button type="button" draggable="true" class="inline-flex shrink-0 items-center justify-center border-0 bg-transparent p-0 text-slate-400 transition hover:text-slate-700" aria-label="<?php esc_attr_e( 'Arrastrar lección', 'almaden-bookster' ); ?>" data-almaden-lesson-drag-handle>
											<span class="dashicons dashicons-menu"></span>
										</button>
										<div class="min-w-0 flex-1">
											<input
												type="text"
												name="lesson_title"
												form="<?php echo esc_attr( $lesson_form_id ); ?>"
												value="<?php echo esc_attr( (string) ( $lesson['title'] ?? $item['label'] ?? '' ) ); ?>"
												class="block w-full border-0 bg-transparent p-0 text-xl font-semibold tracking-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0"
											>
										</div>
									</div>
									<button
										type="button"
										class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-amber-300 hover:text-amber-700 shrink-0"
										aria-label="<?php esc_attr_e( 'Desplegar lección', 'almaden-bookster' ); ?>"
										aria-expanded="false"
										aria-controls="<?php echo esc_attr( $lesson_panel_id ); ?>"
										data-almaden-lesson-toggle
									>
										<span class="dashicons dashicons-arrow-right-alt2" data-almaden-lesson-toggle-icon></span>
									</button>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<input type="hidden" name="action" value="almaden_learni_delete_lesson">
										<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
										<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson_id ); ?>">
										<?php wp_nonce_field( 'almaden_learni_delete_lesson_' . (int) $selected_course_id ); ?>
										<button type="submit" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-slate-400 transition hover:border-rose-200 hover:text-rose-600" aria-label="<?php esc_attr_e( 'Eliminar lección', 'almaden-bookster' ); ?>">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</form>
								</div>

								<div id="<?php echo esc_attr( $lesson_panel_id ); ?>" class="border-t border-slate-100 p-5 sm:p-6" data-almaden-lesson-panel hidden>
									<form id="<?php echo esc_attr( $lesson_form_id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid gap-4 lg:grid-cols-2">
										<input type="hidden" name="action" value="almaden_learni_save_lesson">
										<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
										<input type="hidden" name="lesson_id" value="<?php echo esc_attr( (string) $lesson_id ); ?>">
										<?php wp_nonce_field( 'almaden_learni_save_lesson_' . (int) $selected_course_id ); ?>
										<div>
											<label class="mb-2 block text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'YouTube URL', 'almaden-bookster' ); ?></label>
											<input type="url" name="lesson_video_url" value="<?php echo esc_attr( (string) ( $lesson['video_url'] ?? '' ) ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500" placeholder="<?php esc_attr_e( 'https://youtube.com/watch?v=...', 'almaden-bookster' ); ?>">
										</div>
										<div>
											<label class="mb-2 block text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Disponible en', 'almaden-bookster' ); ?></label>
											<input type="date" name="lesson_available_at" value="<?php echo esc_attr( (string) ( $lesson['available_at'] ?? '' ) ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500">
										</div>
										<div class="lg:col-span-2">
											<label class="mb-2 block text-sm font-semibold uppercase tracking-[0.18em] text-slate-500"><?php esc_html_e( 'Contenido', 'almaden-bookster' ); ?></label>
											<textarea name="lesson_content" rows="7" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500"><?php echo esc_textarea( (string) ( $lesson['content'] ?? '' ) ); ?></textarea>
										</div>
										<div class="lg:col-span-2 flex items-center justify-end gap-3">
											<button type="submit" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
												<?php esc_html_e( 'Guardar lección', 'almaden-bookster' ); ?>
											</button>
										</div>
									</form>
								</div>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="rounded-[2rem] border border-dashed border-slate-300 bg-white/70 p-10 text-center shadow-sm">
						<div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
							<span class="dashicons dashicons-welcome-write-blog"></span>
						</div>
						<h3 class="mt-4 text-2xl font-semibold text-slate-950"><?php esc_html_e( 'Todavía no hay contenido', 'almaden-bookster' ); ?></h3>
						<p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500">
							<?php esc_html_e( 'Usa "Añadir sección" o "Añadir" para comenzar a estructurar el curso.', 'almaden-bookster' ); ?>
						</p>
					</div>
				<?php endif; ?>
			</div>

			<aside class="space-y-4">
				<form id="almaden-course-sidebar-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
				<input type="hidden" name="action" value="almaden_learni_save_course">
				<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
				<?php wp_nonce_field( 'almaden_learni_save_course_' . (int) $selected_course_id ); ?>
				<div class="grid gap-3">
					<button type="submit" name="course_status" value="draft" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
						<?php esc_html_e( 'Guardar cambios', 'almaden-bookster' ); ?>
					</button>
					<button type="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-500" disabled>
						<span class="mr-2 dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'Vista previa', 'almaden-bookster' ); ?>
					</button>
					<button type="submit" name="course_status" value="publish" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-amber-700 to-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-amber-800 hover:to-amber-600">
						<?php echo esc_html( 'publish' === $status_value ? __( 'Unpublish', 'almaden-bookster' ) : __( 'Publicar', 'almaden-bookster' ) ); ?>
					</button>
				</div>
			</form>

			<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
				<p class="text-xs font-semibold uppercase tracking-[0.42em] text-slate-400"><?php esc_html_e( 'Checklist: Items to check', 'almaden-bookster' ); ?></p>
				<div class="mt-5 space-y-4">
					<?php foreach ( $check_items as $item ) : ?>
						<div class="flex items-center gap-4 rounded-2xl bg-slate-50 px-4 py-3 text-sm <?php echo ! empty( $item['done'] ) ? 'text-slate-900' : 'text-slate-500'; ?>">
							<span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full <?php echo ! empty( $item['done'] ) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-500'; ?>">
								<?php echo ! empty( $item['done'] ) ? '✓' : '•'; ?>
							</span>
							<span><?php echo esc_html( $item['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			</aside>
		</div>
	</div>
</div>
