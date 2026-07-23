<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap" id="almaden-pages-app">
	<h1>Pages - AlmadenBookster</h1>
	<?php if ( ! empty( $success_flag ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php if ( ! empty( $sync_section ) ) : ?>
					Se sincronizó la sección <strong><?php echo esc_html( $sync_section ); ?></strong> correctamente.
				<?php else : ?>
					Las rutas del plugin se guardaron correctamente.
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<p class="description" style="max-width: 960px;">
		Aquí puedes revisar todas las páginas que el plugin crea o sincroniza. Cada bloque te muestra si WordPress ya encontró una página con ese slug o si todavía no existe.
	</p>

	<div class="card" style="max-width: 960px; padding: 24px; margin-top: 20px;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="almaden_bookster_save_pages_settings" />
			<?php wp_nonce_field( 'almaden_bookster_pages_settings', 'almaden_pages_nonce' ); ?>

			<?php foreach ( $page_sections as $section_index => $section ) : ?>
				<?php
				$status = isset( $section['status'] ) && is_array( $section['status'] ) ? $section['status'] : array();
				$status_label = isset( $status['label'] ) ? (string) $status['label'] : 'No encontrada';
				$status_class = isset( $status['status'] ) && false !== strpos( (string) $status['status'], 'found' ) ? 'notice-success' : 'notice-warning';
				$section_id = isset( $section['key'] ) ? sanitize_key( (string) $section['key'] ) : 'page-section-' . (string) $section_index;
				$title_name = isset( $section['title_name'] ) ? (string) $section['title_name'] : '';
				$slug_name = isset( $section['slug_name'] ) ? (string) $section['slug_name'] : '';
				$page_id_name = isset( $section['page_id_name'] ) ? (string) $section['page_id_name'] : '';
				$input_title_id = $title_name;
				$input_slug_id = $slug_name;
				$extra_fields = isset( $section['extra_fields'] ) && is_array( $section['extra_fields'] ) ? $section['extra_fields'] : array();
				?>
				<section id="<?php echo esc_attr( $section_id ); ?>" style="padding: 8px 0 28px; <?php echo $section_index + 1 < count( $page_sections ) ? 'border-bottom: 1px solid #e5e7eb;' : ''; ?>">
					<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px;">
						<div>
							<h2 style="margin: 0 0 8px; font-size: 20px;"><?php echo esc_html( isset( $section['heading'] ) ? $section['heading'] : '' ); ?></h2>
							<p class="description" style="margin: 0; max-width: 760px;"><?php echo esc_html( isset( $section['description'] ) ? $section['description'] : '' ); ?></p>
						</div>
						<div class="notice <?php echo esc_attr( $status_class ); ?>" style="margin: 0; padding: 10px 14px; min-width: 180px;">
							<p style="margin: 0;"><strong><?php echo esc_html( $status_label ); ?></strong></p>
						</div>
					</div>

					<input type="hidden" name="<?php echo esc_attr( $page_id_name ); ?>" value="<?php echo esc_attr( isset( $section['page_id'] ) ? absint( $section['page_id'] ) : 0 ); ?>" />

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( $input_title_id ); ?>">Título</label></th>
								<td>
									<input type="text" class="regular-text" id="<?php echo esc_attr( $input_title_id ); ?>" name="<?php echo esc_attr( $title_name ); ?>" value="<?php echo esc_attr( isset( $section['title'] ) ? $section['title'] : '' ); ?>" />
									<p class="description">Título visible de esta página del plugin.</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( $input_slug_id ); ?>">Slug</label></th>
								<td>
									<input type="text" class="regular-text" id="<?php echo esc_attr( $input_slug_id ); ?>" name="<?php echo esc_attr( $slug_name ); ?>" value="<?php echo esc_attr( isset( $section['slug'] ) ? $section['slug'] : '' ); ?>" />
									<p class="description">Solo el slug, sin barras.</p>
								</td>
							</tr>
							<tr>
								<th scope="row">URL</th>
								<td>
									<code><?php echo esc_html( isset( $section['url'] ) ? $section['url'] : '' ); ?></code>
								</td>
							</tr>
							<tr>
								<th scope="row">Estado detectado</th>
								<td>
									<?php if ( ! empty( $status['exists'] ) ) : ?>
										<p style="margin: 0 0 8px;"><strong>Encontrada.</strong> El plugin la detectó por <strong><?php echo esc_html( isset( $status['source'] ) ? $status['source'] : 'slug' ); ?></strong>.</p>
										<p class="description" style="margin: 0;">ID: <code><?php echo esc_html( isset( $status['page_id'] ) ? (string) absint( $status['page_id'] ) : '0' ); ?></code> · Slug actual: <code><?php echo esc_html( isset( $status['page_slug'] ) ? $status['page_slug'] : '' ); ?></code> · Título actual: <code><?php echo esc_html( isset( $status['page_title'] ) ? $status['page_title'] : '' ); ?></code></p>
									<?php else : ?>
										<p style="margin: 0 0 8px;"><strong>No encontrada.</strong> Si guardas con este título y slug, el plugin intentará crearla de nuevo.</p>
										<p class="description" style="margin: 0;">Puedes cambiar el slug o el título y volver a guardar para reintentar la creación.</p>
									<?php endif; ?>
								</td>
							</tr>
							<?php foreach ( $extra_fields as $field ) : ?>
								<?php if ( ! is_array( $field ) || empty( $field['type'] ) ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<?php if ( 'text' === $field['type'] ) : ?>
									<tr>
										<th scope="row"><label for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
										<td>
											<input type="text" class="regular-text" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( isset( $field['value'] ) ? $field['value'] : '' ); ?>" />
											<?php if ( ! empty( $field['description'] ) ) : ?>
												<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
											<?php endif; ?>
										</td>
									</tr>
								<?php elseif ( 'checkbox' === $field['type'] ) : ?>
									<tr>
										<th scope="row"><?php echo esc_html( isset( $field['label_heading'] ) ? $field['label_heading'] : 'Mostrar en el menú' ); ?></th>
										<td>
											<label>
												<input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>" value="1" <?php checked( ! empty( $field['checked'] ) ); ?> />
												<?php echo esc_html( $field['label'] ); ?>
											</label>
										</td>
									</tr>
								<?php endif; ?>
							<?php endforeach; ?>
						</tbody>
					</table>

					<div style="margin-top: 14px; display: flex; gap: 10px; align-items: center;">
						<button type="submit" name="sync_section" value="<?php echo esc_attr( isset( $section['key'] ) ? $section['key'] : '' ); ?>" class="button button-secondary">Sincronizar ahora</button>
						<span class="description">Recrea solo esta página con el título y slug actuales.</span>
					</div>
				</section>
			<?php endforeach; ?>

			<p class="submit" style="padding-left: 0;">
				<button type="submit" class="button button-primary">Guardar cambios y sincronizar páginas</button>
			</p>
		</form>
	</div>
</div>
