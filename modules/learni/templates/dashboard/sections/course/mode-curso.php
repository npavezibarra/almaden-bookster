<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_post = $editor_state['post'] ?? null;
$course_id = isset( $selected_course_id ) ? (int) $selected_course_id : 0;
$status_value = $course_post ? $course_post->post_status : 'draft';
$linear_value = (int) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_LINEAR_ORDER, true );
$payment_mode = (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PAYMENT_MODE, true );
if ( '' === $payment_mode ) {
	$payment_mode = 'woocommerce';
}
$price_value = (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PRICE, true );
$cover_photo_id = (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_COVER_PHOTO_ID, true );
$banner_photo_id = (string) get_post_meta( $course_id, \AlmadenBookster\Learni\PostTypes\Course::META_BANNER_PHOTO_ID, true );
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
<div id="almaden-learni-tab-curso" class="<?php echo 'curso' !== $active_tab ? 'hidden' : ''; ?>">
	<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
		<section class="rounded-[2rem] border border-slate-200 bg-white shadow-sm">
			<div class="border-b border-slate-200 px-6 py-5">
				<div class="flex flex-wrap gap-2">
					<button type="button" class="almaden-learni-course-tab is-active rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition" data-almaden-course-tab="description">
						<?php esc_html_e( 'Descripción', 'almaden-bookster' ); ?>
					</button>
					<button type="button" class="almaden-learni-course-tab rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-almaden-course-tab="excerpt">
						<?php esc_html_e( 'Extracto', 'almaden-bookster' ); ?>
					</button>
					<button type="button" class="almaden-learni-course-tab rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-almaden-course-tab="image">
						<?php esc_html_e( 'Imagen', 'almaden-bookster' ); ?>
					</button>
				</div>
			</div>

			<div class="px-6 py-6">
				<div class="space-y-6" data-almaden-course-panel="description">
					<label class="block">
						<textarea
							name="course_content"
							form="almaden-course-sidebar-form"
							rows="15"
							aria-label="<?php esc_attr_e( 'Descripción', 'almaden-bookster' ); ?>"
							data-wordcount-source="course_content_count"
							placeholder="<?php esc_attr_e( 'Describe el curso (max 700 palabras)...', 'almaden-bookster' ); ?>"
							class="min-h-[26rem] w-full resize-none rounded-[1.75rem] border border-slate-200 bg-white px-5 py-4 text-2xl leading-[1.65] text-slate-600 outline-none placeholder:text-slate-300 focus:border-amber-500 focus:ring-0"
						><?php echo esc_textarea( $course_post ? $course_post->post_content : '' ); ?></textarea>
					</label>
					<p class="text-right text-xs uppercase tracking-[0.35em] text-slate-300" data-wordcount-target="course_content_count"></p>
				</div>

				<div class="space-y-6 hidden" data-almaden-course-panel="excerpt">
					<label class="block">
						<span class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Extracto', 'almaden-bookster' ); ?></span>
						<textarea
							name="course_excerpt"
							form="almaden-course-sidebar-form"
							rows="10"
							aria-label="<?php esc_attr_e( 'Extracto', 'almaden-bookster' ); ?>"
							data-wordcount-source="course_excerpt_count"
							class="min-h-[18rem] w-full rounded-[1.75rem] border border-slate-200 bg-white px-5 py-4 text-lg leading-8 text-slate-600 outline-none placeholder:text-slate-300 focus:border-amber-500 focus:ring-0"
						><?php echo esc_textarea( $course_post ? $course_post->post_excerpt : '' ); ?></textarea>
					</label>
					<p class="text-right text-xs uppercase tracking-[0.35em] text-slate-300" data-wordcount-target="course_excerpt_count"></p>
				</div>

				<div class="space-y-6 hidden" data-almaden-course-panel="image">
					<p class="text-sm leading-7 text-slate-500">
						<?php esc_html_e( 'La portada y el banner del curso se editan aquí. El logo del certificado permanece en la barra lateral.', 'almaden-bookster' ); ?>
					</p>
					<div class="grid gap-5 lg:grid-cols-2">
						<div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
							<input type="hidden" name="course_cover_photo_id" value="<?php echo esc_attr( $cover_photo_id ); ?>" form="almaden-course-sidebar-form" data-almaden-media-input="cover">
							<div class="flex items-center justify-between gap-3">
								<div>
									<p class="text-sm font-medium text-slate-900"><?php esc_html_e( 'Portada', 'almaden-bookster' ); ?></p>
									<p class="text-xs text-slate-500"><?php esc_html_e( 'Imagen frontal del curso', 'almaden-bookster' ); ?></p>
								</div>
								<button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="cover" data-media-title="<?php esc_attr_e( 'Seleccionar portada', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar portada', 'almaden-bookster' ); ?>">
									<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
								</button>
							</div>
							<div class="mt-4 overflow-hidden rounded-[1.25rem] border border-dashed border-slate-200 bg-white p-3" data-almaden-media-preview="cover" <?php echo $cover_has_preview ? '' : 'style="display:none;"'; ?>>
								<img src="<?php echo esc_url( $cover_photo_url ? $cover_photo_url : $cover_preview ); ?>" alt="" class="h-44 w-full rounded-xl object-cover">
							</div>
							<div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="cover" <?php echo $cover_has_preview ? 'style="display:none;"' : ''; ?>>
								<?php esc_html_e( 'Sin portada seleccionada', 'almaden-bookster' ); ?>
							</div>
							<button type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="cover" <?php echo $cover_photo_id !== '' ? '' : 'style="display:none;"'; ?>>
								<?php esc_html_e( 'Quitar portada', 'almaden-bookster' ); ?>
							</button>
						</div>

						<div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
							<input type="hidden" name="course_banner_photo_id" value="<?php echo esc_attr( $banner_photo_id ); ?>" form="almaden-course-sidebar-form" data-almaden-media-input="banner">
							<div class="flex items-center justify-between gap-3">
								<div>
									<p class="text-sm font-medium text-slate-900"><?php esc_html_e( 'Banner', 'almaden-bookster' ); ?></p>
									<p class="text-xs text-slate-500"><?php esc_html_e( 'Imagen superior del curso', 'almaden-bookster' ); ?></p>
								</div>
								<button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="banner" data-media-title="<?php esc_attr_e( 'Seleccionar banner', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar banner', 'almaden-bookster' ); ?>">
									<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
								</button>
							</div>
							<div class="mt-4 overflow-hidden rounded-[1.25rem] border border-dashed border-slate-200 bg-white p-3" data-almaden-media-preview="banner" <?php echo $banner_photo_url ? '' : 'style="display:none;"'; ?>>
								<img src="<?php echo esc_url( $banner_photo_url ); ?>" alt="" class="h-44 w-full rounded-xl object-cover">
							</div>
							<div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="banner" <?php echo $banner_photo_url ? 'style="display:none;"' : ''; ?>>
								<?php esc_html_e( 'Sin banner seleccionado', 'almaden-bookster' ); ?>
							</div>
							<button type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="banner" <?php echo $banner_photo_id !== '' ? '' : 'style="display:none;"'; ?>>
								<?php esc_html_e( 'Quitar banner', 'almaden-bookster' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</section>

		<?php if ( 'curso' === $active_tab ) : ?>
			<aside class="space-y-4">
				<form id="almaden-course-sidebar-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<input type="hidden" name="action" value="almaden_learni_save_course">
					<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $course_id ); ?>">
					<?php wp_nonce_field( 'almaden_learni_save_course_' . $course_id ); ?>
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
					<p class="text-xs uppercase tracking-[0.28em] text-slate-400"><?php esc_html_e( 'Precio del curso', 'almaden-bookster' ); ?></p>
					<label class="mt-5 block text-sm font-medium text-slate-700">
						<span class="sr-only"><?php esc_html_e( 'Precio del curso', 'almaden-bookster' ); ?></span>
						<input
							id="almaden-course-sidebar-price"
							type="number"
							min="0"
							step="0.01"
							name="course_price"
							form="almaden-course-sidebar-form"
							value="<?php echo esc_attr( '' !== $price_value ? $price_value : '0' ); ?>"
							class="w-full border-0 border-b border-slate-200 bg-transparent px-0 py-2 text-5xl font-semibold tracking-tight text-slate-950 outline-none focus:ring-0"
						>
					</label>
					<div class="mt-4 border-t border-slate-200 pt-3 text-sm font-semibold uppercase tracking-[0.32em] text-emerald-500">
						<?php echo esc_html( (float) $price_value > 0 ? number_format_i18n( (float) $price_value, 0 ) : __( 'Gratis', 'almaden-bookster' ) ); ?>
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
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</aside>
		<?php endif; ?>
	</div>
</div>
