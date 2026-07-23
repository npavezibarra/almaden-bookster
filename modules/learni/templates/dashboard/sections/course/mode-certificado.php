<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$certificate = isset( $editor_state['certificate'] ) && is_array( $editor_state['certificate'] ) ? $editor_state['certificate'] : array();
$course_id = isset( $selected_course_id ) ? (int) $selected_course_id : 0;
$certificate_logo_id = (int) ( $certificate['logo_id'] ?? 0 );
$certificate_logo_url = $certificate_logo_id > 0 ? wp_get_attachment_image_url( $certificate_logo_id, 'medium' ) : '';
$certificate_signature_attachment_id = (int) ( $certificate['signature_attachment_id'] ?? 0 );
$certificate_signature_url = $certificate_signature_attachment_id > 0 ? wp_get_attachment_image_url( $certificate_signature_attachment_id, 'medium' ) : '';
$certificate_message = (string) ( $certificate['message'] ?? '' );
$certificate_word_count = preg_match_all( '/\S+/u', trim( $certificate_message ), $matches ) ? count( $matches[0] ) : 0;
?>
<div id="almaden-learni-tab-certificado" class="<?php echo 'certificado' !== $active_tab ? 'hidden' : ''; ?>">
	<form id="almaden-course-certificate-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="almaden_learni_save_course">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $course_id ); ?>">
		<input type="hidden" name="course_editor_tab" value="certificado">
		<?php wp_nonce_field( 'almaden_learni_save_course_' . $course_id ); ?>

		<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
			<section class="rounded-[2rem] border border-slate-200 bg-white shadow-sm">
				<div class="px-6 py-6">
					<div class="space-y-6">
						<label class="block">
							<span class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Title of certificate', 'almaden-bookster' ); ?></span>
							<input type="text" name="course_certificate_title" value="<?php echo esc_attr( (string) ( $certificate['title'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Certificado Finalización', 'almaden-bookster' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500">
						</label>

						<div>
							<p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-400"><?php esc_html_e( 'Congratulations paragraph (max 50 words)', 'almaden-bookster' ); ?></p>
							<div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
								<span><?php esc_html_e( 'Shortcodes:', 'almaden-bookster' ); ?></span>
								<code class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1">[display_full_name]</code>
								<code class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1">[first_name]</code>
								<code class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1">[course_name]</code>
								<code class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1">[date_start]</code>
								<code class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1">[date_end]</code>
							</div>
							<textarea
								name="course_certificate_message"
								rows="7"
								placeholder="<?php esc_attr_e( 'Write the certificate paragraph...', 'almaden-bookster' ); ?>"
								class="mt-4 min-h-[11rem] w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm leading-7 outline-none placeholder:text-slate-400 transition focus:border-amber-500"
								data-almaden-certificate-wordcount-source="certificate_message"
							><?php echo esc_textarea( $certificate_message ); ?></textarea>
							<p class="mt-3 text-right text-sm font-medium text-slate-400" data-almaden-certificate-wordcount-target="certificate_message">
								<?php echo esc_html( $certificate_word_count . ' / 50 palabras' ); ?>
							</p>
						</div>

						<div class="grid gap-5 lg:grid-cols-2">
							<div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
								<input id="almaden-course-certificate-logo-input" type="hidden" name="course_certificate_logo_id" value="<?php echo esc_attr( (string) $certificate_logo_id ); ?>" data-almaden-media-input="certificate_logo">
								<div class="flex items-center justify-between gap-3">
									<div>
										<p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400"><?php esc_html_e( 'Logo (png/jpg)', 'almaden-bookster' ); ?></p>
									</div>
									<button id="almaden-course-certificate-logo-picker" type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="certificate_logo" data-media-title="<?php esc_attr_e( 'Seleccionar logo', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar logo', 'almaden-bookster' ); ?>">
										<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
									</button>
								</div>

								<div id="almaden-course-certificate-logo-preview" class="mt-4 overflow-hidden rounded-[1.5rem] border border-dashed border-slate-200 bg-white p-4" data-almaden-media-preview="certificate_logo" <?php echo $certificate_logo_url ? '' : 'style="display:none;"'; ?>>
									<img src="<?php echo esc_url( $certificate_logo_url ? $certificate_logo_url : '' ); ?>" alt="" class="h-44 w-full rounded-2xl object-contain">
								</div>
								<div id="almaden-course-certificate-logo-empty" class="mt-4 rounded-[1.5rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="certificate_logo" <?php echo $certificate_logo_url ? 'style="display:none;"' : ''; ?>>
									<?php esc_html_e( 'Sin imagen', 'almaden-bookster' ); ?>
								</div>
								<button id="almaden-course-certificate-logo-remove" type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="certificate_logo" <?php echo $certificate_logo_id > 0 ? '' : 'style="display:none;"'; ?>>
									<?php esc_html_e( 'Quitar', 'almaden-bookster' ); ?>
								</button>
							</div>

							<div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5">
								<input id="almaden-course-certificate-signature-input" type="hidden" name="course_certificate_signature_attachment_id" value="<?php echo esc_attr( (string) $certificate_signature_attachment_id ); ?>" data-almaden-media-input="certificate_signature">
								<div class="flex items-center justify-between gap-3">
									<div>
										<p class="text-xs font-semibold uppercase tracking-[0.32em] text-slate-400"><?php esc_html_e( 'Signature scan (png/jpg)', 'almaden-bookster' ); ?></p>
									</div>
									<button id="almaden-course-certificate-signature-picker" type="button" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-amber-300 hover:text-slate-950" data-almaden-media-picker data-media-target="certificate_signature" data-media-title="<?php esc_attr_e( 'Seleccionar firma', 'almaden-bookster' ); ?>" data-media-button="<?php esc_attr_e( 'Usar firma', 'almaden-bookster' ); ?>">
										<?php esc_html_e( 'Elegir', 'almaden-bookster' ); ?>
									</button>
								</div>

								<div id="almaden-course-certificate-signature-preview" class="mt-4 overflow-hidden rounded-[1.5rem] border border-dashed border-slate-200 bg-white p-4" data-almaden-media-preview="certificate_signature" <?php echo $certificate_signature_url ? '' : 'style="display:none;"'; ?>>
									<img src="<?php echo esc_url( $certificate_signature_url ? $certificate_signature_url : '' ); ?>" alt="" class="h-44 w-full rounded-2xl object-contain">
								</div>
								<div id="almaden-course-certificate-signature-empty" class="mt-4 rounded-[1.5rem] border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500" data-almaden-media-empty="certificate_signature" <?php echo $certificate_signature_url ? 'style="display:none;"' : ''; ?>>
									<?php esc_html_e( 'Sin imagen', 'almaden-bookster' ); ?>
								</div>
								<button id="almaden-course-certificate-signature-remove" type="button" class="mt-3 text-xs font-semibold text-rose-600" data-almaden-media-remove data-media-target="certificate_signature" <?php echo $certificate_signature_attachment_id > 0 ? '' : 'style="display:none;"'; ?>>
									<?php esc_html_e( 'Quitar', 'almaden-bookster' ); ?>
								</button>

								<label class="mt-5 block">
									<span class="mb-2 block text-xs font-semibold uppercase tracking-[0.32em] text-slate-400"><?php esc_html_e( 'Signature label', 'almaden-bookster' ); ?></span>
									<input type="text" name="course_certificate_signature_label" value="<?php echo esc_attr( (string) ( $certificate['signature_label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Signature label...', 'almaden-bookster' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-amber-500">
								</label>
							</div>
						</div>
					</div>
				</div>
			</section>

			<?php include __DIR__ . '/sidebar.php'; ?>
		</div>
	</form>
</div>
