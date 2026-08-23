<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page_sections = isset( $page_sections ) && is_array( $page_sections ) ? $page_sections : array();
$selected_section_key = '';

if ( ! empty( $sync_section ) ) {
	foreach ( $page_sections as $section ) {
		if ( isset( $section['key'] ) && (string) $section['key'] === (string) $sync_section ) {
			$selected_section_key = (string) $sync_section;
			break;
		}
	}
}

if ( '' === $selected_section_key && ! empty( $page_sections ) ) {
	$first_section = reset( $page_sections );
	$selected_section_key = isset( $first_section['key'] ) ? (string) $first_section['key'] : '';
}
?>
<div class="wrap" id="almaden-pages-app">
	<h1>Pages - AlmadenBookster</h1>
	<?php if ( ! empty( $success_flag ) ) : ?>
		<div style="margin: 16px 0 20px; padding: 14px 16px; border: 1px solid #e5e7eb; border-left: 4px solid #9ca3af; border-radius: 14px; background: #fafafa; color: #374151;">
			<p style="margin: 0;">
				<?php if ( ! empty( $sync_section ) ) : ?>
					Se sincronizó la sección <strong><?php echo esc_html( $sync_section ); ?></strong> correctamente.
				<?php else : ?>
					Las rutas del plugin se guardaron correctamente.
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<p class="description" style="max-width: 960px;">
		Aquí decides qué rutas pertenecen al shell Almaden. A la izquierda verás un listado compacto de páginas; al seleccionar una, su configuración se abre en el panel grande de la derecha.
	</p>

	<style>
		#almaden-pages-app .almaden-pages-layout {
			display: grid;
			grid-template-columns: minmax(240px, 280px) minmax(0, 1fr);
			gap: 24px;
			align-items: start;
			margin-top: 20px;
		}

		#almaden-pages-app .almaden-pages-sidebar {
			position: sticky;
			top: 32px;
			align-self: start;
			background: #fff;
			border: 1px solid #e5e7eb;
			border-radius: 18px;
			padding: 18px;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
		}

		#almaden-pages-app .almaden-pages-sidebar-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			margin-bottom: 14px;
		}

		#almaden-pages-app .almaden-pages-sidebar-header h2 {
			margin: 0;
			font-size: 18px;
		}

		#almaden-pages-app .almaden-pages-list {
			display: grid;
			gap: 10px;
		}

		#almaden-pages-app .almaden-pages-item {
			width: 100%;
			border: 1px solid #d1d5db;
			background: #f8fafc;
			border-radius: 14px;
			padding: 14px 15px;
			text-align: left;
			display: grid;
			gap: 6px;
			cursor: pointer;
			transition: background-color 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
		}

		#almaden-pages-app .almaden-pages-item[data-page-type="shell"] {
			background: #f0fdf4;
			border-color: #bbf7d0;
		}

		#almaden-pages-app .almaden-pages-item[data-page-type="regular"] {
			background: #f8fbff;
			border-color: #dbeafe;
		}

		#almaden-pages-app .almaden-pages-item:hover {
			border-color: #94a3b8;
			background: #f1f5f9;
			transform: translateY(-1px);
		}

		#almaden-pages-app .almaden-pages-item[data-page-type="shell"]:hover {
			background: #e8fbea;
			border-color: #86efac;
		}

		#almaden-pages-app .almaden-pages-item[data-page-type="regular"]:hover {
			background: #eff6ff;
			border-color: #93c5fd;
		}

		#almaden-pages-app .almaden-pages-item.is-active {
			background: #111827;
			border-color: #111827;
			color: #fff;
		}

		#almaden-pages-app .almaden-pages-item-title {
			font-size: 16px;
			font-weight: 600;
			line-height: 1.2;
		}

		#almaden-pages-app .almaden-pages-item-meta {
			font-size: 12px;
			opacity: 0.78;
		}

		#almaden-pages-app .almaden-pages-item-badge {
			display: inline-flex;
			align-items: center;
			width: fit-content;
			padding: 3px 8px;
			border-radius: 999px;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.08em;
			text-transform: uppercase;
			line-height: 1;
		}

		#almaden-pages-app .almaden-pages-item-badge.shell {
			background: #dcfce7;
			color: #166534;
		}

		#almaden-pages-app .almaden-pages-item-badge.regular {
			background: #dbeafe;
			color: #1d4ed8;
		}

		#almaden-pages-app .almaden-pages-item.is-active .almaden-pages-item-badge.shell {
			background: rgba(255, 255, 255, 0.16);
			color: #d1fae5;
		}

		#almaden-pages-app .almaden-pages-item.is-active .almaden-pages-item-badge.regular {
			background: rgba(255, 255, 255, 0.16);
			color: #dbeafe;
		}

		#almaden-pages-app .almaden-pages-detail {
			min-width: 0;
			max-width: 980px;
			width: 100%;
			justify-self: start;
		}

		#almaden-pages-app .almaden-page-panel {
			display: none;
			padding: 24px;
			border: 1px solid #e5e7eb;
			border-radius: 18px;
			background: #ffffff;
			box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
		}

		#almaden-pages-app .almaden-page-panel.is-active {
			display: block;
		}

		#almaden-pages-app .almaden-page-panel-head {
			display: flex;
			align-items: flex-start;
			justify-content: space-between;
			gap: 16px;
			margin-bottom: 18px;
		}

		#almaden-pages-app .almaden-page-panel-head h2 {
			margin: 0 0 8px;
			font-size: 22px;
		}

		#almaden-pages-app .almaden-page-panel-head p {
			margin: 0;
			max-width: 720px;
		}

		#almaden-pages-app .almaden-page-status {
			margin: 0;
			padding: 10px 14px;
			min-width: 180px;
			border: 1px solid #e5e7eb;
			border-left: 4px solid #9ca3af;
			border-radius: 12px;
			background: #fafafa;
			color: #374151;
			line-height: 1.35;
		}

		#almaden-pages-app .almaden-page-fields {
			display: grid;
			gap: 16px;
		}

		#almaden-pages-app .almaden-page-field {
			display: grid;
			grid-template-columns: 170px minmax(0, 1fr);
			gap: 16px;
			align-items: start;
		}

		#almaden-pages-app .almaden-page-field-label {
			font-weight: 600;
			padding-top: 8px;
		}

		#almaden-pages-app .almaden-page-field-control .description {
			margin-top: 8px;
		}

		#almaden-pages-app .almaden-page-actions {
			margin-top: 20px;
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			align-items: center;
		}

		#almaden-pages-app .almaden-page-actions .description {
			max-width: 520px;
		}

		#almaden-pages-app .almaden-page-footer {
			margin-top: 24px;
			padding-top: 18px;
			border-top: 1px solid #e5e7eb;
		}

		#almaden-pages-app .almaden-page-url {
			word-break: break-word;
		}

		@media (max-width: 1100px) {
			#almaden-pages-app .almaden-pages-layout {
				grid-template-columns: 1fr;
			}

			#almaden-pages-app .almaden-pages-sidebar {
				position: static;
			}

			#almaden-pages-app .almaden-pages-detail {
				max-width: none;
			}
		}

		@media (max-width: 782px) {
			#almaden-pages-app .almaden-page-field {
				grid-template-columns: 1fr;
			}

			#almaden-pages-app .almaden-page-panel {
				padding: 18px;
			}
		}
	</style>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="almaden-pages-form">
		<input type="hidden" name="action" value="almaden_bookster_save_pages_settings" />
		<?php wp_nonce_field( 'almaden_bookster_pages_settings', 'almaden_pages_nonce' ); ?>

		<div class="almaden-pages-layout">
			<aside class="almaden-pages-sidebar" aria-label="Listado de páginas">
				<div class="almaden-pages-sidebar-header">
					<h2>Pages</h2>
					<button type="button" class="button button-primary" id="almaden-pages-add-page">+ ADD PAGE</button>
				</div>
				<div class="almaden-pages-list" id="almaden-pages-list">
					<?php foreach ( $page_sections as $section_index => $section ) : ?>
						<?php
						$section_key = isset( $section['key'] ) ? (string) $section['key'] : 'page-section-' . (string) $section_index;
						$status = isset( $section['status'] ) && is_array( $section['status'] ) ? $section['status'] : array();
						$status_label = isset( $status['label'] ) ? (string) $status['label'] : 'No encontrada';
						$is_custom = ! empty( $section['is_custom'] );
						$page_type = isset( $section['page_type'] ) && 'regular' === (string) $section['page_type'] ? 'regular' : 'shell';
						$type_label = 'regular' === $page_type ? 'WordPress' : 'Shell';
						$title_label = isset( $section['title'] ) && '' !== trim( (string) $section['title'] )
							? (string) $section['title']
							: ( isset( $section['heading'] ) && '' !== (string) $section['heading'] ? (string) $section['heading'] : 'Nueva página' );
						?>
						<button type="button" class="almaden-pages-item<?php echo $selected_section_key === $section_key ? ' is-active' : ''; ?>" data-section-key="<?php echo esc_attr( $section_key ); ?>" data-section-title="<?php echo esc_attr( $title_label ); ?>" data-section-status="<?php echo esc_attr( $status_label ); ?>" data-section-custom="<?php echo $is_custom ? '1' : '0'; ?>" data-page-type="<?php echo esc_attr( $page_type ); ?>">
							<span class="almaden-pages-item-badge <?php echo esc_attr( $page_type ); ?>"><?php echo esc_html( $type_label ); ?></span>
							<span class="almaden-pages-item-title"><?php echo esc_html( $title_label ); ?></span>
							<span class="almaden-pages-item-meta"><?php echo esc_html( $status_label ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</aside>

			<section class="almaden-pages-detail" aria-label="Detalles de la página">
				<?php foreach ( $page_sections as $section_index => $section ) : ?>
					<?php
					$section_key = isset( $section['key'] ) ? (string) $section['key'] : 'page-section-' . (string) $section_index;
					$status = isset( $section['status'] ) && is_array( $section['status'] ) ? $section['status'] : array();
					$status_label = isset( $status['label'] ) ? (string) $status['label'] : 'No encontrada';
					$title_name = isset( $section['title_name'] ) ? (string) $section['title_name'] : '';
					$slug_name = isset( $section['slug_name'] ) ? (string) $section['slug_name'] : '';
					$page_id_name = isset( $section['page_id_name'] ) ? (string) $section['page_id_name'] : '';
					$extra_fields = isset( $section['extra_fields'] ) && is_array( $section['extra_fields'] ) ? $section['extra_fields'] : array();
					$is_custom = ! empty( $section['is_custom'] );
					$page_type = isset( $section['page_type'] ) && 'regular' === (string) $section['page_type'] ? 'regular' : 'shell';
					$sync_label = isset( $section['sync_label'] ) && '' !== (string) $section['sync_label'] ? (string) $section['sync_label'] : 'Sincronizar ahora';
					$title_value = isset( $section['title'] ) ? (string) $section['title'] : '';
					$heading = '' !== trim( $title_value ) ? $title_value : ( isset( $section['heading'] ) ? (string) $section['heading'] : '' );
					if ( '' === $heading ) {
						$heading = 'Nueva página';
					}
					?>
					<div class="almaden-page-panel<?php echo $selected_section_key === $section_key ? ' is-active' : ''; ?>" data-section-panel="<?php echo esc_attr( $section_key ); ?>" data-section-custom="<?php echo $is_custom ? '1' : '0'; ?>">
						<div class="almaden-page-panel-head">
							<div>
								<h2><?php echo esc_html( $heading ); ?></h2>
								<p class="description"><?php echo esc_html( isset( $section['description'] ) ? $section['description'] : '' ); ?></p>
							</div>
							<div class="almaden-page-status" data-status-pill>
								<p style="margin: 0;"><strong><?php echo esc_html( $status_label ); ?></strong></p>
							</div>
						</div>

						<input type="hidden" name="<?php echo esc_attr( $page_id_name ); ?>" value="<?php echo esc_attr( isset( $section['page_id'] ) ? absint( $section['page_id'] ) : 0 ); ?>" />
						<?php if ( $is_custom && isset( $section['slot_key'] ) ) : ?>
							<input type="hidden" name="custom_pages[<?php echo esc_attr( $section['slot_key'] ); ?>][slot_key]" value="<?php echo esc_attr( $section['slot_key'] ); ?>" />
							<input type="hidden" name="custom_pages[<?php echo esc_attr( $section['slot_key'] ); ?>][page_id]" value="<?php echo esc_attr( isset( $section['page_id'] ) ? absint( $section['page_id'] ) : 0 ); ?>" />
						<?php endif; ?>

						<div class="almaden-page-fields">
							<div class="almaden-page-field">
								<div class="almaden-page-field-label"><label for="<?php echo esc_attr( $title_name ); ?>">Título</label></div>
								<div class="almaden-page-field-control">
									<input type="text" class="regular-text" id="<?php echo esc_attr( $title_name ); ?>" name="<?php echo esc_attr( $title_name ); ?>" value="<?php echo esc_attr( $title_value ); ?>" data-auto-slug-target="<?php echo esc_attr( $slug_name ); ?>" />
									<p class="description">Título visible de esta página del plugin.</p>
								</div>
							</div>

							<div class="almaden-page-field">
								<div class="almaden-page-field-label"><label for="<?php echo esc_attr( $slug_name ); ?>">Slug</label></div>
								<div class="almaden-page-field-control">
									<input type="text" class="regular-text" id="<?php echo esc_attr( $slug_name ); ?>" name="<?php echo esc_attr( $slug_name ); ?>" value="<?php echo esc_attr( isset( $section['slug'] ) ? (string) $section['slug'] : '' ); ?>" />
									<p class="description">Solo el slug, sin barras.</p>
								</div>
							</div>

							<?php if ( $is_custom ) : ?>
								<div class="almaden-page-field">
									<div class="almaden-page-field-label">Tipo de página</div>
									<div class="almaden-page-field-control">
										<label style="display: block; margin-bottom: 8px;">
											<input type="radio" name="custom_pages[<?php echo esc_attr( $section['slot_key'] ); ?>][page_type]" value="shell" <?php checked( 'shell' === $page_type ); ?> />
											Almaden Shell Page
										</label>
										<label style="display: block;">
											<input type="radio" name="custom_pages[<?php echo esc_attr( $section['slot_key'] ); ?>][page_type]" value="regular" <?php checked( 'regular' === $page_type ); ?> />
											WordPress Regular Page
										</label>
										<p class="description">El tipo controla si esta página entra en el shell Almaden o si se comporta como una página normal de WordPress.</p>
									</div>
								</div>
							<?php endif; ?>

							<div class="almaden-page-field">
								<div class="almaden-page-field-label">URL</div>
								<div class="almaden-page-field-control">
									<code class="almaden-page-url"><?php echo esc_html( isset( $section['url'] ) ? (string) $section['url'] : '' ); ?></code>
								</div>
							</div>

							<div class="almaden-page-field">
								<div class="almaden-page-field-label">Estado detectado</div>
								<div class="almaden-page-field-control">
									<?php if ( ! empty( $status['exists'] ) ) : ?>
										<p style="margin: 0 0 8px;"><strong>Encontrada.</strong> El plugin la detectó por <strong><?php echo esc_html( isset( $status['source'] ) ? $status['source'] : 'slug' ); ?></strong>.</p>
										<p class="description" style="margin: 0;">ID: <code><?php echo esc_html( isset( $status['page_id'] ) ? (string) absint( $status['page_id'] ) : '0' ); ?></code> · Slug actual: <code><?php echo esc_html( isset( $status['page_slug'] ) ? $status['page_slug'] : '' ); ?></code> · Título actual: <code><?php echo esc_html( isset( $status['page_title'] ) ? $status['page_title'] : '' ); ?></code></p>
									<?php else : ?>
										<p style="margin: 0 0 8px;"><strong>No encontrada.</strong> Guardar los ajustes no creará ninguna página.</p>
										<p class="description" style="margin: 0;">Usa el botón <?php echo $is_custom ? '<strong>' . esc_html( $sync_label ) . '</strong>' : '<strong>Sincronizar ahora</strong>'; ?> para crear o vincular esta ruta.</p>
									<?php endif; ?>
								</div>
							</div>

							<?php foreach ( $extra_fields as $field ) : ?>
								<?php if ( ! is_array( $field ) || empty( $field['type'] ) ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<?php if ( 'text' === $field['type'] ) : ?>
									<div class="almaden-page-field">
										<div class="almaden-page-field-label"><label for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_html( $field['label'] ); ?></label></div>
										<div class="almaden-page-field-control">
											<input type="text" class="regular-text" id="<?php echo esc_attr( $field['name'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( isset( $field['value'] ) ? $field['value'] : '' ); ?>" />
											<?php if ( ! empty( $field['description'] ) ) : ?>
												<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
											<?php endif; ?>
										</div>
									</div>
								<?php elseif ( 'checkbox' === $field['type'] ) : ?>
									<div class="almaden-page-field">
										<div class="almaden-page-field-label"><?php echo esc_html( isset( $field['label_heading'] ) ? $field['label_heading'] : 'Mostrar en el menú' ); ?></div>
										<div class="almaden-page-field-control">
											<label>
												<input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>" value="1" <?php checked( ! empty( $field['checked'] ) ); ?> />
												<?php echo esc_html( $field['label'] ); ?>
											</label>
										</div>
									</div>
								<?php elseif ( 'role_checkboxes' === $field['type'] ) : ?>
									<div class="almaden-page-field" data-shell-role-settings>
										<div class="almaden-page-field-label"><?php echo esc_html( isset( $field['label_heading'] ) ? $field['label_heading'] : 'Acceso por rol' ); ?></div>
										<div class="almaden-page-field-control">
											<p class="description" style="margin-top: 0;"><?php echo esc_html( isset( $field['label'] ) ? $field['label'] : 'Define qué roles pueden ver esta página.' ); ?></p>
											<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 12px;">
												<?php foreach ( isset( $field['roles'] ) && is_array( $field['roles'] ) ? $field['roles'] : array() as $role_key => $role_label ) : ?>
													<label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafafa;">
														<input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>[<?php echo esc_attr( $role_key ); ?>]" value="1" <?php checked( in_array( $role_key, isset( $field['checked_roles'] ) && is_array( $field['checked_roles'] ) ? $field['checked_roles'] : array(), true ) ); ?> />
														<span><?php echo esc_html( $role_label ); ?></span>
													</label>
												<?php endforeach; ?>
											</div>
										</div>
									</div>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>

						<div class="almaden-page-actions">
							<button type="submit" name="sync_section" value="<?php echo esc_attr( $section_key ); ?>" class="button button-secondary"><?php echo esc_html( $sync_label ); ?></button>
							<span class="description"><?php echo $is_custom ? 'Crea o actualiza esta página personalizada y la deja lista para usar.' : 'Crea o actualiza solo esta ruta y la marca como parte del shell Almaden.'; ?></span>
						</div>
					</div>
				<?php endforeach; ?>

				<div class="almaden-page-footer">
					<p class="submit" style="padding-left: 0; margin-bottom: 0;">
						<button type="submit" class="button button-primary">Guardar cambios</button>
					</p>
				</div>
			</section>
		</div>
	</form>

	<template id="almaden-custom-page-template">
		<button type="button" class="almaden-pages-item" data-section-key="__SECTION_KEY__" data-section-title="Nueva página" data-section-status="Borrador" data-section-custom="1" data-page-type="shell">
			<span class="almaden-pages-item-badge shell">Shell</span>
			<span class="almaden-pages-item-title">Nueva página</span>
			<span class="almaden-pages-item-meta">Borrador · personalizada</span>
		</button>
		<div class="almaden-page-panel" data-section-panel="__SECTION_KEY__" data-section-custom="1">
			<div class="almaden-page-panel-head">
				<div>
					<h2>Nueva página</h2>
					<p class="description">Página personalizada creada desde el panel de Pages.</p>
				</div>
				<div class="almaden-page-status" data-status-pill>
					<p style="margin: 0;"><strong>Borrador</strong></p>
				</div>
			</div>

			<input type="hidden" name="custom_pages[__SLOT_KEY__][slot_key]" value="__SLOT_KEY__" />
			<input type="hidden" name="custom_pages[__SLOT_KEY__][page_id]" value="0" />

			<div class="almaden-page-fields">
				<div class="almaden-page-field">
					<div class="almaden-page-field-label"><label for="custom_pages___SLOT_KEY___title">Título</label></div>
					<div class="almaden-page-field-control">
						<input type="text" class="regular-text" id="custom_pages___SLOT_KEY___title" name="custom_pages[__SLOT_KEY__][title]" value="" data-auto-slug-target="custom_pages___SLOT_KEY___slug" />
						<p class="description">Título visible de esta página del plugin.</p>
					</div>
				</div>

				<div class="almaden-page-field">
					<div class="almaden-page-field-label"><label for="custom_pages___SLOT_KEY___slug">Slug</label></div>
					<div class="almaden-page-field-control">
						<input type="text" class="regular-text" id="custom_pages___SLOT_KEY___slug" name="custom_pages[__SLOT_KEY__][slug]" value="" />
						<p class="description">Solo el slug, sin barras.</p>
					</div>
				</div>

				<div class="almaden-page-field">
					<div class="almaden-page-field-label">URL</div>
					<div class="almaden-page-field-control">
						<code class="almaden-page-url">Pendiente de crear</code>
					</div>
				</div>

				<div class="almaden-page-field">
					<div class="almaden-page-field-label">Estado detectado</div>
					<div class="almaden-page-field-control">
						<p style="margin: 0 0 8px;"><strong>No creada.</strong> Este slot todavía no existe como página real de WordPress.</p>
						<p class="description" style="margin: 0;">Completa el título y pulsa <strong>CREAR</strong> para generar la página y guardarla en esta lista.</p>
					</div>
				</div>

				<div class="almaden-page-field" data-shell-role-settings>
					<div class="almaden-page-field-label">Acceso por rol</div>
					<div class="almaden-page-field-control">
						<p class="description" style="margin-top: 0;">Define qué roles pueden ver esta página.</p>
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 12px;">
							<?php foreach ( function_exists( 'almaden_bookster_get_access_preview_roles' ) ? almaden_bookster_get_access_preview_roles() : array( 'administrator' => 'Administrador', 'editor' => 'Editor', 'author' => 'Autor', 'customer' => 'Cliente', 'subscriber' => 'Suscriptor' ) as $role_key => $role_label ) : ?>
								<label style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fafafa;">
									<input type="checkbox" name="page_visibility_allowed_roles[custom_page:__SLOT_KEY__][<?php echo esc_attr( $role_key ); ?>]" value="1" checked />
									<span><?php echo esc_html( $role_label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<div class="almaden-page-field">
					<div class="almaden-page-field-label">Tipo de página</div>
					<div class="almaden-page-field-control">
						<label style="display: block; margin-bottom: 8px;">
							<input type="radio" name="custom_pages[__SLOT_KEY__][page_type]" value="shell" checked />
							Almaden Shell Page
						</label>
						<label style="display: block;">
							<input type="radio" name="custom_pages[__SLOT_KEY__][page_type]" value="regular" />
							WordPress Regular Page
						</label>
						<p class="description">Elige si esta página formará parte del shell Almaden o si será una página normal de WordPress.</p>
					</div>
				</div>
			</div>

			<div class="almaden-page-actions">
				<button type="submit" name="sync_section" value="__SECTION_KEY__" class="button button-secondary">CREAR</button>
				<span class="description">Creará la página real de WordPress y la dejará lista para seguir editándose en este panel.</span>
			</div>
		</div>
	</template>

	<div class="card" style="max-width: 960px; padding: 24px; margin-top: 20px;">
		<div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px;">
			<div>
				<h2 style="margin: 0 0 8px; font-size: 20px;">Migración de media de libros</h2>
				<p class="description" style="margin: 0; max-width: 760px;">Esta fase mueve las imágenes existentes de cada libro a su carpeta propia en Media y reescribe URLs antiguas dentro de contenido y metadatos.</p>
			</div>
			<?php if ( ! empty( $book_media_migration_report['last_run'] ) ) : ?>
				<div style="margin: 0; padding: 10px 14px; min-width: 180px; border: 1px solid #e5e7eb; border-left: 4px solid #9ca3af; border-radius: 12px; background: #fafafa; color: #374151;">
					<p style="margin: 0;"><strong>Ejecutada</strong></p>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $book_media_migration_status['done'] ) ) : ?>
			<div style="margin: 0 0 16px; padding: 12px 14px; border: 1px solid #e5e7eb; border-left: 4px solid #9ca3af; border-radius: 12px; background: #fafafa; color: #374151;">
				<p style="margin: 0;">La migración de media se ejecutó correctamente.</p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $book_media_migration_report['last_run'] ) ) : ?>
			<p style="margin: 0 0 12px;">Última ejecución: <strong><?php echo esc_html( $book_media_migration_report['last_run'] ); ?></strong> · <?php echo esc_html( $book_media_migration_report['message'] ?? '' ); ?></p>
			<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
				<div><strong>Libros escaneados</strong><br /><?php echo esc_html( (string) absint( $book_media_migration_report['books_scanned'] ?? 0 ) ); ?></div>
				<div><strong>Libros actualizados</strong><br /><?php echo esc_html( (string) absint( $book_media_migration_report['books_updated'] ?? 0 ) ); ?></div>
				<div><strong>Archivos movidos</strong><br /><?php echo esc_html( (string) absint( $book_media_migration_report['attachment_files_moved'] ?? 0 ) ); ?></div>
				<div><strong>URLs reescritas</strong><br /><?php echo esc_html( (string) absint( ( $book_media_migration_report['content_rewrites'] ?? 0 ) + ( $book_media_migration_report['meta_rewrites'] ?? 0 ) ) ); ?></div>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="almaden_bookster_run_book_media_migration" />
			<?php wp_nonce_field( 'almaden_bookster_run_book_media_migration', 'almaden_book_media_migration_nonce' ); ?>
			<p class="submit" style="padding-left: 0; margin-bottom: 0;">
				<button type="submit" class="button button-secondary">Migrar media existente</button>
				<span class="description" style="display: inline-block; margin-left: 10px;">Úsalo para libros ya creados antes de esta fase.</span>
			</p>
		</form>
	</div>
</div>

<script>
(function() {
	const app = document.getElementById('almaden-pages-app');
	if (!app) {
		return;
	}

	const list = document.getElementById('almaden-pages-list');
	const addButton = document.getElementById('almaden-pages-add-page');
	const template = document.getElementById('almaden-custom-page-template');
	const form = document.getElementById('almaden-pages-form');
	const detail = app.querySelector('.almaden-pages-detail');
	let draftCount = 0;

	function slugify(value) {
		return String(value || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '')
			.replace(/[^a-z0-9]+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function activateSection(sectionKey) {
		app.querySelectorAll('[data-section-panel]').forEach((panel) => {
			panel.classList.toggle('is-active', panel.getAttribute('data-section-panel') === sectionKey);
		});

		app.querySelectorAll('[data-section-key]').forEach((item) => {
			item.classList.toggle('is-active', item.getAttribute('data-section-key') === sectionKey);
		});
	}

	function bindAutoSlug(panel) {
		const titleInput = panel.querySelector('[data-auto-slug-target]');
		if (!titleInput) {
			return;
		}

		const slugTargetId = titleInput.getAttribute('data-auto-slug-target');
		const slugInput = slugTargetId ? document.getElementById(slugTargetId) : null;
		if (!slugInput) {
			return;
		}

		let lastAutoSlug = slugInput.value || '';

		titleInput.addEventListener('input', () => {
			const currentSlug = slugInput.value.trim();
			const nextSlug = slugify(titleInput.value);

			if ('' === currentSlug || currentSlug === lastAutoSlug) {
				slugInput.value = nextSlug;
				lastAutoSlug = nextSlug;
			}
		});

		slugInput.addEventListener('input', () => {
			lastAutoSlug = slugInput.value.trim();
		});
	}

	function registerPanel(panel) {
		if (!panel) {
			return;
		}

		const sectionKey = panel.getAttribute('data-section-panel');
		const item = app.querySelector('[data-section-key="' + CSS.escape(sectionKey) + '"]');

		if (item) {
			item.addEventListener('click', () => activateSection(sectionKey));
		}

		bindAutoSlug(panel);
		bindPageTypeVisibility(panel);
	}

	function bindPageTypeVisibility(panel) {
		const roleField = panel.querySelector('[data-shell-role-settings]');
		if (!roleField) {
			return;
		}

		roleField.style.display = '';
		roleField.querySelectorAll('input, select, textarea').forEach((input) => {
			input.disabled = false;
		});
	}

	function createDraftPage() {
		if (!template || !form) {
			return;
		}

		const draftKey = 'custom_page:draft-' + Date.now() + '-' + (++draftCount);
		const slotKey = draftKey.replace('custom_page:', '');
		const html = template.innerHTML
			.replaceAll('__SECTION_KEY__', draftKey)
			.replaceAll('__SLOT_KEY__', slotKey);
		const fragment = document.createRange().createContextualFragment(html);
		const nodes = Array.from(fragment.children);

		nodes.forEach((node) => {
			if (node.matches && node.matches('[data-section-key]')) {
				node.addEventListener('click', () => activateSection(draftKey));
				list.prepend(node);
				return;
			}

			if (node.matches && node.matches('[data-section-panel]')) {
				if (detail) {
					detail.insertBefore(node, detail.querySelector('.almaden-page-footer'));
				}
				registerPanel(node);
			}
		});

		activateSection(draftKey);
		const titleInput = app.querySelector('[data-section-panel="' + CSS.escape(draftKey) + '"] input[id$="_title"]');
		if (titleInput) {
			titleInput.focus();
			titleInput.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	}

	if (addButton) {
		addButton.addEventListener('click', createDraftPage);
	}

	app.querySelectorAll('[data-section-panel]').forEach(registerPanel);

	const initial = app.querySelector('.almaden-page-panel.is-active');
	if (initial) {
		activateSection(initial.getAttribute('data-section-panel'));
	}

	window.addEventListener('pageshow', () => {
		const activePanel = app.querySelector('.almaden-page-panel.is-active');
		if (activePanel) {
			activateSection(activePanel.getAttribute('data-section-panel'));
		}
	});
})();
</script>
