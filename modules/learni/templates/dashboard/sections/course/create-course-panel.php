<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="almaden-create-course-panel" class="space-y-6" hidden>
	<form id="almaden-create-course-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-6">
		<input type="hidden" name="action" value="almaden_learni_create_course">
		<?php wp_nonce_field( 'almaden_learni_create_course' ); ?>
		<input type="hidden" name="course_linear_order" value="1">
		<input type="hidden" name="course_payment_mode" value="woocommerce">
		<input type="hidden" name="course_collaborators" value="" data-almaden-create-collaborators>

		<div class="rounded-[2rem] border border-slate-200 bg-white px-6 py-5 shadow-sm sm:px-8">
			<div class="flex flex-wrap items-start justify-between gap-4">
				<div class="max-w-4xl">
					<p class="text-xs uppercase tracking-[0.24em] text-slate-400"><?php esc_html_e( 'Editor principal', 'almaden-bookster' ); ?></p>
					<label class="sr-only" for="almaden-course-create-title"><?php esc_html_e( 'Título del curso', 'almaden-bookster' ); ?></label>
					<input
						id="almaden-course-create-title"
						name="course_title"
						type="text"
						placeholder="<?php esc_attr_e( 'Título del curso', 'almaden-bookster' ); ?>"
						autocomplete="off"
						data-wordcount-source="almaden-create-title-count"
						class="mt-3 block w-full border-0 bg-transparent p-0 text-center text-4xl font-semibold tracking-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0 sm:text-5xl lg:text-[3.6rem]"
					>
					<p class="mt-3 max-w-4xl text-lg leading-8 text-slate-500">
						<?php esc_html_e( 'Crea un nuevo curso con la misma superficie modular de Politeia, manteniendo la estética limpia de Almaden.', 'almaden-bookster' ); ?>
					</p>
					<div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
						<span class="rounded-full bg-slate-100 px-4 py-2 text-slate-600"><?php esc_html_e( '0 cursos', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-slate-100 px-4 py-2 text-slate-600"><?php esc_html_e( 'Privado', 'almaden-bookster' ); ?></span>
						<span class="rounded-full bg-slate-100 px-4 py-2 text-slate-600"><?php esc_html_e( 'Parciales pequeños', 'almaden-bookster' ); ?></span>
					</div>
				</div>
				<button
					id="almaden-course-creator-close-create"
					type="button"
					class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950"
					data-almaden-toggle-create-course
				>
					<?php esc_html_e( 'Cerrar', 'almaden-bookster' ); ?>
				</button>
			</div>
		</div>

		<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
			<div class="space-y-6">
				<div class="rounded-[2rem] border border-slate-200 bg-white shadow-sm">
					<div class="border-b border-slate-200 px-6 py-5">
						<div class="flex flex-wrap gap-2">
							<button type="button" class="almaden-learni-create-tab is-active rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition" data-almaden-create-tab="description">
								<?php esc_html_e( 'Descripción', 'almaden-bookster' ); ?>
							</button>
							<button type="button" class="almaden-learni-create-tab rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-almaden-create-tab="excerpt">
								<?php esc_html_e( 'Extracto', 'almaden-bookster' ); ?>
							</button>
							<button type="button" class="almaden-learni-create-tab rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-almaden-create-tab="image">
								<?php esc_html_e( 'Imagen', 'almaden-bookster' ); ?>
							</button>
							<button type="button" class="almaden-learni-create-tab rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition hover:border-amber-300 hover:text-slate-950" data-almaden-create-tab="teachers">
								<?php esc_html_e( 'Profesores', 'almaden-bookster' ); ?>
							</button>
						</div>
					</div>

					<div class="px-6 py-6">
						<div class="space-y-6" data-almaden-create-panel="description">
							<label class="block">
								<span class="sr-only"><?php esc_html_e( 'Descripción del curso', 'almaden-bookster' ); ?></span>
								<textarea
									name="course_description"
									rows="15"
									aria-label="<?php esc_attr_e( 'Descripción del curso', 'almaden-bookster' ); ?>"
									placeholder="<?php esc_attr_e( 'Escribe la descripción del curso aquí... (máx. 700 palabras)', 'almaden-bookster' ); ?>"
									data-wordcount-source="almaden-create-description-count"
									class="min-h-[28rem] w-full resize-none border-0 bg-transparent p-0 text-2xl leading-[1.65] text-slate-500 outline-none placeholder:text-slate-300 focus:ring-0"
								></textarea>
							</label>
							<p class="text-right text-xs uppercase tracking-[0.35em] text-slate-300" data-wordcount-target="almaden-create-description-count"></p>
						</div>

						<div class="space-y-6 hidden" data-almaden-create-panel="excerpt">
							<label class="block">
								<span class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Extracto', 'almaden-bookster' ); ?></span>
								<textarea
									name="course_excerpt"
									rows="10"
									aria-label="<?php esc_attr_e( 'Extracto del curso', 'almaden-bookster' ); ?>"
									placeholder="<?php esc_attr_e( 'Resume el curso en unas pocas líneas.', 'almaden-bookster' ); ?>"
									data-wordcount-source="almaden-create-excerpt-count"
									class="min-h-[18rem] w-full rounded-[1.75rem] border border-slate-200 bg-white px-5 py-4 text-lg leading-8 text-slate-600 outline-none placeholder:text-slate-300 focus:border-amber-500"
								></textarea>
							</label>
							<p class="text-right text-xs uppercase tracking-[0.35em] text-slate-300" data-wordcount-target="almaden-create-excerpt-count"></p>
						</div>

						<div class="space-y-6 hidden" data-almaden-create-panel="image">
							<div class="grid gap-5 lg:grid-cols-2">
								<div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
									<input type="hidden" name="course_cover_photo_id" value="0" data-almaden-media-input="create_cover">
									<div class="flex items-center justify-between gap-3">
										<div>
											<p class="text-sm font-medium text-slate-900"><?php esc_html_e( 'Portada', 'almaden-bookster' ); ?></p>
											<p class="text-xs text-slate-500"><?php esc_html_e( 'Imagen frontal del curso', 'almaden-bookster' ); ?></p>
										</div>
										<button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="create_cover" data-media-title="<?php esc_attr_e( 'Seleccionar portada', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar portada', 'almaden-bookster' ); ?>">
											<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
										</button>
									</div>
									<div class="mt-4 overflow-hidden rounded-[1.25rem] border border-dashed border-slate-200 bg-white p-3" data-almaden-media-preview="create_cover" style="display:none;">
										<img src="" alt="" class="h-44 w-full rounded-xl object-cover">
									</div>
									<div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="create_cover">
										<?php esc_html_e( 'Sin portada seleccionada', 'almaden-bookster' ); ?>
									</div>
									<button type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="create_cover" style="display:none;">
										<?php esc_html_e( 'Quitar portada', 'almaden-bookster' ); ?>
									</button>
								</div>

								<div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
									<input type="hidden" name="course_banner_photo_id" value="0" data-almaden-media-input="create_banner">
									<div class="flex items-center justify-between gap-3">
										<div>
											<p class="text-sm font-medium text-slate-900"><?php esc_html_e( 'Banner', 'almaden-bookster' ); ?></p>
											<p class="text-xs text-slate-500"><?php esc_html_e( 'Imagen superior del curso', 'almaden-bookster' ); ?></p>
										</div>
										<button type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="create_banner" data-media-title="<?php esc_attr_e( 'Seleccionar banner', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar banner', 'almaden-bookster' ); ?>">
											<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
										</button>
									</div>
									<div class="mt-4 overflow-hidden rounded-[1.25rem] border border-dashed border-slate-200 bg-white p-3" data-almaden-media-preview="create_banner" style="display:none;">
										<img src="" alt="" class="h-44 w-full rounded-xl object-cover">
									</div>
									<div class="mt-4 rounded-[1.25rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="create_banner">
										<?php esc_html_e( 'Sin banner seleccionado', 'almaden-bookster' ); ?>
									</div>
									<button type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="create_banner" style="display:none;">
										<?php esc_html_e( 'Quitar banner', 'almaden-bookster' ); ?>
									</button>
								</div>
							</div>
						</div>

						<div class="space-y-6 hidden" data-almaden-create-panel="teachers">
							<div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
								<div class="flex flex-wrap items-center justify-between gap-3">
									<div>
										<p class="text-sm font-semibold text-slate-950"><?php esc_html_e( 'Profesores', 'almaden-bookster' ); ?></p>
										<p class="text-sm text-slate-500"><?php esc_html_e( 'Agrega los nombres de quienes dictarán este curso.', 'almaden-bookster' ); ?></p>
									</div>
									<button
										id="almaden-btn-add-teacher"
										type="button"
										class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-amber-300 hover:text-slate-950"
										data-almaden-create-teacher-add
									>
										<?php esc_html_e( 'Agregar profesor', 'almaden-bookster' ); ?>
									</button>
								</div>
								<div id="almaden-create-teachers-list" class="mt-4 space-y-3" data-almaden-create-teachers-list>
									<div class="flex items-center gap-3 rounded-[1.25rem] border border-slate-200 bg-white p-3" data-almaden-create-teacher-row>
										<input type="text" class="min-w-0 flex-1 border-0 bg-transparent px-2 py-2 text-sm outline-none placeholder:text-slate-300" placeholder="<?php esc_attr_e( 'Nombre del profesor', 'almaden-bookster' ); ?>" data-almaden-create-teacher-name>
										<button type="button" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-rose-200 hover:text-rose-600" data-almaden-create-teacher-remove>
											<?php esc_html_e( 'Quitar', 'almaden-bookster' ); ?>
										</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<aside class="space-y-4">
				<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<div class="grid gap-3">
						<button type="submit" name="course_status" value="draft" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
							<?php esc_html_e( 'Guardar cambios', 'almaden-bookster' ); ?>
						</button>
						<button type="button" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-500" disabled>
							<span class="mr-2 dashicons dashicons-visibility"></span>
							<?php esc_html_e( 'Vista previa', 'almaden-bookster' ); ?>
						</button>
						<button type="submit" name="course_status" value="publish" class="inline-flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-amber-700 to-amber-500 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-amber-800 hover:to-amber-600">
							<?php esc_html_e( 'Publicar', 'almaden-bookster' ); ?>
						</button>
					</div>
				</div>

				<div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<p class="text-xs uppercase tracking-[0.28em] text-slate-400"><?php esc_html_e( 'Precio del curso', 'almaden-bookster' ); ?></p>
					<label class="mt-5 block text-sm font-medium text-slate-700">
						<span class="sr-only"><?php esc_html_e( 'Precio del curso', 'almaden-bookster' ); ?></span>
						<input
							id="almaden-course-create-price"
							type="number"
							min="0"
							step="0.01"
							name="course_price"
							value="0"
							class="w-full border-0 border-b border-slate-200 bg-transparent px-0 py-2 text-5xl font-semibold tracking-tight text-slate-950 outline-none focus:ring-0"
						>
					</label>
					<div class="mt-4 border-t border-slate-200 pt-3 text-sm font-semibold uppercase tracking-[0.32em] text-emerald-500">
						<?php esc_html_e( 'Gratis', 'almaden-bookster' ); ?>
					</div>
				</div>

				<div id="almaden-course-create-checklist" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm">
					<p class="text-xs uppercase tracking-[0.28em] text-slate-400"><?php esc_html_e( 'Checklist: items to check', 'almaden-bookster' ); ?></p>
					<div class="mt-4 space-y-3 text-sm">
						<?php foreach ( array( 'Title', 'Price', 'Description', 'Front Image', 'Top Banner', 'Excerpt', 'Instructors', 'Lessons', 'Evaluación' ) as $label ) : ?>
							<div class="flex items-center gap-3 text-slate-600">
								<span class="h-2.5 w-2.5 rounded-full bg-slate-200"></span>
								<span><?php echo esc_html( $label ); ?></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</aside>
		</div>
	</form>
</section>
