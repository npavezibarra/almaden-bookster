<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$woocommerce_active = ! empty( $woocommerce_status['active'] );
$relation = is_array( $commerce_relation ?? null ) ? $commerce_relation : array();
$relation_mode = sanitize_key( $relation['product_mode'] ?? 'none' );
$product_id = absint( $relation['product_id'] ?? 0 );
$parent_product_id = absint( $relation['parent_product_id'] ?? 0 );
$product_link = $product_id > 0 ? get_edit_post_link( $product_id ) : '';
$parent_link = $parent_product_id > 0 ? get_edit_post_link( $parent_product_id ) : '';
?>
<div id="tab-ebook-commerce" class="ebook-setting-tab-content space-y-4 hidden">
	<div class="border border-[var(--border-color)] rounded-xl p-4 bg-[var(--bg-app)] space-y-4 shadow-sm">
		<h4 class="text-xs font-bold text-black dark:text-white uppercase tracking-wider flex items-center gap-1.5 border-b border-[var(--border-color)] pb-2 mb-1">
			<i class="fa-solid fa-cart-shopping text-[10px]"></i> Integración comercial
		</h4>

		<?php if ( ! $woocommerce_active ) : ?>
			<div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
				WooCommerce no está activo en este sitio. La configuración comercial de este libro se habilita cuando WooCommerce está instalado y activo.
			</div>
		<?php else : ?>
			<div class="grid gap-3 md:grid-cols-2">
				<div class="rounded-xl border border-[var(--border-color)] bg-white p-4">
					<p class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)] mb-2">Estado actual</p>
					<div class="space-y-1 text-sm">
						<p><strong>Modo:</strong> <span id="commerce-current-mode-label"><?php echo esc_html( strtoupper( str_replace( '_', ' ', $relation_mode ) ) ); ?></span></p>
						<p><strong>Producto:</strong> <span id="commerce-current-product-id"><?php echo esc_html( $product_id > 0 ? (string) $product_id : '-' ); ?></span></p>
						<p><strong>Padre:</strong> <span id="commerce-current-parent-id"><?php echo esc_html( $parent_product_id > 0 ? (string) $parent_product_id : '-' ); ?></span></p>
					</div>

					<div class="mt-4 flex flex-wrap gap-2">
						<?php if ( $product_link ) : ?>
							<a href="<?php echo esc_url( $product_link ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-[var(--border-color)] px-3 py-2 text-xs font-semibold text-[var(--text-main)] hover:bg-[var(--bg-sidebar)]">
								<i class="fa-solid fa-arrow-up-right-from-square"></i>
								Editar producto
							</a>
						<?php endif; ?>
						<?php if ( $parent_link ) : ?>
							<a href="<?php echo esc_url( $parent_link ); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-[var(--border-color)] px-3 py-2 text-xs font-semibold text-[var(--text-main)] hover:bg-[var(--bg-sidebar)]">
								<i class="fa-solid fa-arrow-up-right-from-square"></i>
								Editar padre
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="rounded-xl border border-[var(--border-color)] bg-white p-4 space-y-3">
					<p class="text-[10px] font-bold uppercase tracking-wider text-[var(--text-muted)]">Vínculo comercial</p>
					<div>
						<label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1" for="almaden-wc-relation-mode">Tipo de vínculo</label>
						<select id="almaden-wc-relation-mode" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm">
							<option value="simple" <?php selected( $relation_mode, 'simple' ); ?>>Producto simple</option>
							<option value="variable_parent" <?php selected( $relation_mode, 'variable_parent' ); ?>>Producto variable padre</option>
							<option value="variation" <?php selected( $relation_mode, 'variation' ); ?>>Variación ebook</option>
							<option value="none" <?php selected( $relation_mode, 'none' ); ?>>Sin vínculo</option>
						</select>
					</div>

					<div>
						<label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1" for="almaden-wc-product-id">ID del producto</label>
						<input id="almaden-wc-product-id" type="number" min="0" step="1" value="<?php echo esc_attr( $product_id ); ?>" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm" placeholder="123" />
					</div>

					<div>
						<label class="block text-[10px] font-semibold text-[var(--text-muted)] mb-1" for="almaden-wc-parent-product-id">ID del producto padre</label>
						<input id="almaden-wc-parent-product-id" type="number" min="0" step="1" value="<?php echo esc_attr( $parent_product_id ); ?>" class="w-full rounded-lg border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm" placeholder="456" />
					</div>

					<label class="flex items-start gap-2 rounded-lg border border-[var(--border-color)] bg-[var(--bg-sidebar)] px-3 py-2 text-sm">
						<input id="almaden-wc-create-product" type="checkbox" value="1" class="mt-1 h-4 w-4 rounded border-[var(--border-color)] text-black focus:ring-black" />
						<span>Crear el producto faltante al guardar si no existe vínculo todavía.</span>
					</label>

					<div class="flex flex-wrap gap-2 pt-1">
						<button type="button" onclick="saveStateToLocalStorage(true)" class="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2 text-xs font-semibold text-white hover:bg-neutral-800">
							<i class="fa-solid fa-floppy-disk"></i>
							Guardar comercio
						</button>
						<button type="button" onclick="document.getElementById('almaden-wc-relation-mode').value='none'; saveStateToLocalStorage(true);" class="inline-flex items-center gap-2 rounded-lg border border-[var(--border-color)] bg-white px-4 py-2 text-xs font-semibold text-[var(--text-main)] hover:bg-[var(--bg-sidebar)]">
							Desvincular
						</button>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
