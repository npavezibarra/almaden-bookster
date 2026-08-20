<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section
	id="almaden-book-product-panel"
	class="abp-panel"
	data-book-id="<?php echo esc_attr( $book_id ); ?>"
	data-nonce="<?php echo esc_attr( $nonce ); ?>"
	data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
	data-woocommerce-active="<?php echo $woocommerce_active ? '1' : '0'; ?>"
>
	<nav class="abp-subtabs" role="tablist" aria-label="Producto y capítulos de muestra">
		<button type="button" class="abp-subtab is-active" data-abp-tab="commerce" role="tab" aria-selected="true">Producto WooCommerce</button>
		<button type="button" class="abp-subtab" data-abp-tab="samples" role="tab" aria-selected="false">Muestra</button>
	</nav>
	<div id="almaden-book-product-status" class="abp-status" role="status" aria-live="polite"></div>
	<div id="almaden-book-product-content" data-abp-panel="commerce"></div>
	<div id="almaden-book-sample-content" data-abp-panel="samples" hidden>
		<div class="abp-sample-heading">
			<div>
				<h4>Capítulos de muestra gratis</h4>
				<p>Selecciona los capítulos que cualquier visitante podrá leer sin comprar el ebook.</p>
			</div>
			<button type="button" class="abp-primary-button" data-action="save-samples">Guardar muestra</button>
		</div>
		<div class="abp-sample-list">
			<?php if ( $sample_chapters ) : ?>
				<?php foreach ( $sample_chapters as $index => $chapter ) : ?>
					<label class="abp-sample-row">
						<input type="checkbox" value="<?php echo esc_attr( $chapter['id'] ); ?>" <?php checked( ! empty( $chapter['selected'] ) ); ?>>
						<span class="abp-sample-number"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
						<span class="abp-sample-title"><?php echo esc_html( $chapter['title'] ); ?></span>
						<span class="abp-sample-badge"><?php echo ! empty( $chapter['selected'] ) ? 'Muestra gratis' : 'Bloqueado'; ?></span>
					</label>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="abp-sample-empty">Este libro todavía no tiene capítulos disponibles.</p>
			<?php endif; ?>
		</div>
	</div>
	<script id="almaden-book-product-initial-state" type="application/json"><?php echo wp_json_encode( $state ); ?></script>
</section>
