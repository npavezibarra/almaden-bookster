<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_post = $editor_state['post'] ?? null;
?>
<div id="almaden-learni-tab-curso" class="<?php echo 'curso' !== $active_tab ? 'hidden' : ''; ?>">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
		<input type="hidden" name="action" value="almaden_learni_save_course">
		<input type="hidden" name="course_id" value="<?php echo esc_attr( (string) $selected_course_id ); ?>">
		<?php wp_nonce_field( 'almaden_learni_save_course_' . (int) $selected_course_id ); ?>

		<div class="space-y-4">
			<div>
				<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Título', 'almaden-bookster' ); ?></label>
				<input name="course_title" type="text" value="<?php echo esc_attr( $course_post ? $course_post->post_title : '' ); ?>" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
			</div>
			<div>
				<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Descripción', 'almaden-bookster' ); ?></label>
				<textarea name="course_content" rows="10" class="w-full rounded-3xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500"><?php echo esc_textarea( $course_post ? $course_post->post_content : '' ); ?></textarea>
			</div>
		</div>

		<div class="space-y-4 rounded-3xl border border-slate-200 bg-slate-50 p-5">
			<div>
				<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Extracto', 'almaden-bookster' ); ?></label>
				<textarea name="course_excerpt" rows="4" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500"><?php echo esc_textarea( $course_post ? $course_post->post_excerpt : '' ); ?></textarea>
			</div>
			<div>
				<label class="mb-2 block text-sm font-medium text-slate-700"><?php esc_html_e( 'Estado', 'almaden-bookster' ); ?></label>
				<select name="course_status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
					<?php foreach ( array( 'draft' => __( 'Borrador', 'almaden-bookster' ), 'pending' => __( 'Pendiente', 'almaden-bookster' ), 'publish' => __( 'Publicado', 'almaden-bookster' ), 'private' => __( 'Privado', 'almaden-bookster' ) ) as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $course_post ? $course_post->post_status : 'draft', $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="space-y-3">
				<label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
					<input type="checkbox" name="course_linear_order" value="1" <?php checked( (int) get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_LINEAR_ORDER, true ), 1 ); ?>>
					<span><?php esc_html_e( 'Orden lineal', 'almaden-bookster' ); ?></span>
				</label>
				<label class="block text-sm font-medium text-slate-700">
					<?php esc_html_e( 'Modo de pago', 'almaden-bookster' ); ?>
					<select name="course_payment_mode" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-amber-500">
						<option value="woocommerce" <?php selected( get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PAYMENT_MODE, true ), 'woocommerce' ); ?>>WooCommerce</option>
						<option value="direct" <?php selected( get_post_meta( (int) $selected_course_id, \AlmadenBookster\Learni\PostTypes\Course::META_PAYMENT_MODE, true ), 'direct' ); ?>>Directo</option>
					</select>
				</label>
			</div>
			<div>
				<button type="submit" class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
					<?php esc_html_e( 'Guardar curso', 'almaden-bookster' ); ?>
				</button>
			</div>
		</div>
	</form>
</div>
