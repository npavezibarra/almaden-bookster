<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$woocommerce_active = ! empty( $woocommerce_status['active'] );
$woocommerce_installed = ! empty( $woocommerce_status['installed'] );
$woocommerce_action_url = '';
$woocommerce_action_label = '';
$commerce_hardening_report = function_exists( 'almaden_bookster_get_commerce_hardening_report' ) ? almaden_bookster_get_commerce_hardening_report() : array();
$commerce_hardening_status = function_exists( 'almaden_bookster_get_commerce_hardening_action_status' ) ? almaden_bookster_get_commerce_hardening_action_status() : array();

if ( ! $woocommerce_active ) {
	if ( $woocommerce_installed && current_user_can( 'activate_plugins' ) ) {
		$woocommerce_action_url   = ! empty( $woocommerce_status['activate_url'] ) ? $woocommerce_status['activate_url'] : '';
		$woocommerce_action_label = 'Activar WooCommerce';
	} elseif ( current_user_can( 'install_plugins' ) ) {
		$woocommerce_action_url   = ! empty( $woocommerce_status['install_url'] ) ? $woocommerce_status['install_url'] : '';
		$woocommerce_action_label = 'Instalar WooCommerce';
	}
}

$status_items = $woocommerce_active ? array(
	array(
		'label' => 'Modo activo',
		'value' => almaden_bookster_distribution_mode_label( $settings['default_distribution_mode'] ?? '' ),
	),
	array(
		'label' => 'Proveedor comercial',
		'value' => almaden_bookster_commerce_provider_label( $settings['default_commerce_provider'] ?? '' ),
	),
	array(
		'label' => 'Entrada del lector',
		'value' => almaden_bookster_reader_entry_mode_label( $settings['default_reader_entry_mode'] ?? '' ),
	),
	array(
		'label' => 'Retorno',
		'value' => almaden_bookster_return_policy_label( $settings['return_url_policy'] ?? '' ),
	),
) : array();
?>
<div class="wrap" id="almaden-distribution-app">
	<h1>Distribución y acceso</h1>

	<?php if ( ! empty( $saved ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>La configuración global de distribución se guardó correctamente.</p>
		</div>
	<?php endif; ?>

	<p class="description" style="max-width: 980px;">
		Este panel define la arquitectura base que Bookster usará para distribuir ebooks en el sitio madre. La configuración es global por ahora y más adelante se sobreescribirá por libro.
	</p>

	<div class="card" style="max-width: 980px; padding: 24px; margin-top: 20px;">
		<div style="border: 1px solid #c7d2fe; background: #eef2ff; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
			<h2 style="margin: 0 0 8px; font-size: 18px;">Mantenimiento y migración de comercio</h2>
			<p style="margin: 0 0 12px; color: #3730a3;">
				Esta rutina normaliza vínculos libro-producto, elimina relaciones duplicadas en libros clonados y deja registro de la última pasada de hardening.
			</p>
			<?php if ( ! empty( $commerce_hardening_report['last_run'] ) ) : ?>
				<p style="margin: 0 0 12px; font-size: 13px; color: #4338ca;">
					Última ejecución: <strong><?php echo esc_html( $commerce_hardening_report['last_run'] ); ?></strong>
					· <?php echo esc_html( $commerce_hardening_report['message'] ?? '' ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $commerce_hardening_status['done'] ) ) : ?>
				<p style="margin: 0 0 12px; color: #047857;">Migración ejecutada correctamente.</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin: 0;">
				<input type="hidden" name="action" value="almaden_bookster_run_commerce_hardening" />
				<?php wp_nonce_field( 'almaden_bookster_run_commerce_hardening', 'almaden_commerce_hardening_nonce' ); ?>
				<button type="submit" class="button button-secondary">Ejecutar hardening comercial</button>
			</form>
		</div>

		<?php if ( ! $woocommerce_active ) : ?>
			<div style="border: 1px solid #f3c23c; background: #fffbeb; border-radius: 12px; padding: 18px;">
				<h2 style="margin: 0 0 8px; font-size: 18px;">WooCommerce no está activo</h2>
				<p style="margin: 0 0 14px; color: #92400e;">
					La integración comercial de Bookster depende de WooCommerce. Mientras no esté activo, no mostraremos controles de producto, variaciones ni acceso comercial.
				</p>
				<?php if ( ! empty( $woocommerce_action_url ) ) : ?>
					<p style="margin: 0;">
						<a class="button button-primary" href="<?php echo esc_url( $woocommerce_action_url ); ?>">
							<?php echo esc_html( $woocommerce_action_label ); ?>
						</a>
					</p>
				<?php else : ?>
					<p style="margin: 0; color: #92400e;">No tienes permisos para instalar o activar plugins en este sitio.</p>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px;">
				<?php foreach ( $status_items as $item ) : ?>
					<div style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; background: #fff;">
						<div style="font-size: 12px; text-transform: uppercase; letter-spacing: .08em; color: #6b7280; margin-bottom: 6px;"><?php echo esc_html( $item['label'] ); ?></div>
						<div style="font-size: 16px; font-weight: 700;"><?php echo esc_html( $item['value'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="almaden_bookster_save_distribution_settings" />
				<?php wp_nonce_field( 'almaden_bookster_distribution_settings', 'almaden_distribution_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="default_distribution_mode">Modo de distribución por defecto</label></th>
							<td>
								<select id="default_distribution_mode" name="default_distribution_mode">
									<option value="store_integrated" <?php selected( ( $settings['default_distribution_mode'] ?? '' ), 'store_integrated' ); ?>>Tienda integrada</option>
									<option value="bookshelf_managed" <?php selected( ( $settings['default_distribution_mode'] ?? '' ), 'bookshelf_managed' ); ?>>Bookshelf administrado</option>
								</select>
								<p class="description">Define el comportamiento base para libros nuevos. Se podrá sobreescribir por libro en una fase posterior.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="default_commerce_provider">Proveedor comercial</label></th>
							<td>
								<select id="default_commerce_provider" name="default_commerce_provider">
									<?php if ( function_exists( 'almaden_bookster_get_commerce_provider_choices' ) ) : ?>
										<?php foreach ( almaden_bookster_get_commerce_provider_choices() as $provider_choice ) : ?>
											<option value="<?php echo esc_attr( $provider_choice['key'] ); ?>" <?php selected( ( $settings['default_commerce_provider'] ?? '' ), $provider_choice['key'] ); ?>>
												<?php echo esc_html( $provider_choice['label'] ); ?><?php echo ! empty( $provider_choice['available'] ) ? '' : esc_html__( ' (no disponible)', 'almaden-bookster' ); ?>
											</option>
										<?php endforeach; ?>
									<?php else : ?>
										<option value="woocommerce" <?php selected( ( $settings['default_commerce_provider'] ?? '' ), 'woocommerce' ); ?>>WooCommerce</option>
										<option value="manual" <?php selected( ( $settings['default_commerce_provider'] ?? '' ), 'manual' ); ?>>Manual</option>
									<?php endif; ?>
								</select>
								<p class="description">WooCommerce es el proveedor inicial. Esta pantalla ya deja espacio para futuros adapters.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="default_reader_entry_mode">Entrada pública del lector</label></th>
							<td>
								<select id="default_reader_entry_mode" name="default_reader_entry_mode">
									<option value="product_cta" <?php selected( ( $settings['default_reader_entry_mode'] ?? '' ), 'product_cta' ); ?>>CTA en producto</option>
									<option value="bookshelf_page" <?php selected( ( $settings['default_reader_entry_mode'] ?? '' ), 'bookshelf_page' ); ?>>Página Bookshelf</option>
								</select>
								<p class="description">Controla desde dónde se accede al ebook cuando no se está dentro del editor de Bookster.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bookshelf_page_policy">Política de Bookshelf</label></th>
							<td>
								<select id="bookshelf_page_policy" name="bookshelf_page_policy">
									<option value="auto_create" <?php selected( ( $settings['bookshelf_page_policy'] ?? '' ), 'auto_create' ); ?>>Crear automáticamente</option>
									<option value="manual" <?php selected( ( $settings['bookshelf_page_policy'] ?? '' ), 'manual' ); ?>>Administrar manualmente</option>
									<option value="disabled" <?php selected( ( $settings['bookshelf_page_policy'] ?? '' ), 'disabled' ); ?>>Deshabilitado</option>
								</select>
								<p class="description">La creación automática se usará para la Fase 4, pero dejamos la política declarada desde ahora.</p>
							</td>
						</tr>
						<tr>
							<th scope="row">Automatización</th>
							<td>
								<label style="display:block; margin-bottom: 10px;">
									<input type="checkbox" name="auto_create_store_product" value="1" <?php checked( ! empty( $settings['auto_create_store_product'] ) ); ?> />
									Crear producto comercial al publicar un libro nuevo
								</label>
								<label style="display:block; margin-bottom: 10px;">
									<input type="checkbox" name="auto_create_bookshelf_page" value="1" <?php checked( ! empty( $settings['auto_create_bookshelf_page'] ) ); ?> />
									Crear página Bookshelf automáticamente cuando el modo lo requiera
								</label>
								<label style="display:block;">
									<input type="checkbox" name="menu_injection_enabled" value="1" <?php checked( ! empty( $settings['menu_injection_enabled'] ) ); ?> />
									Permitir inserción automática del ítem de menú administrado por Bookster
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="menu_location">Ubicación de menú</label></th>
							<td>
								<input type="text" class="regular-text" id="menu_location" name="menu_location" value="<?php echo esc_attr( $settings['menu_location'] ?? 'default' ); ?>" />
								<p class="description">Identificador lógico del menú WordPress donde se inyectará el item administrado. Por ahora queda en valor declarativo.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="return_url_policy">Política de retorno</label></th>
							<td>
								<select id="return_url_policy" name="return_url_policy">
									<option value="product_or_fallback" <?php selected( ( $settings['return_url_policy'] ?? '' ), 'product_or_fallback' ); ?>>Producto o fallback</option>
									<option value="bookshelf_or_fallback" <?php selected( ( $settings['return_url_policy'] ?? '' ), 'bookshelf_or_fallback' ); ?>>Bookshelf o fallback</option>
									<option value="store_root" <?php selected( ( $settings['return_url_policy'] ?? '' ), 'store_root' ); ?>>Raíz de la tienda</option>
								</select>
								<p class="description">Define el destino base del enlace “volver” del lector cuando no exista un contexto más preciso.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="valid_order_statuses">Estados de compra válidos</label></th>
							<td>
								<input type="text" class="regular-text" id="valid_order_statuses" name="valid_order_statuses" value="<?php echo esc_attr( implode( ',', (array) ( $settings['valid_order_statuses'] ?? array() ) ) ); ?>" />
								<p class="description">Lista separada por coma. Por defecto: <code>processing,completed</code>. Se usará luego en la verificación de acceso.</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit" style="padding-left: 0;">
					<button type="submit" class="button button-primary">Guardar configuración global</button>
				</p>
			</form>
		<?php endif; ?>
	</div>
</div>
