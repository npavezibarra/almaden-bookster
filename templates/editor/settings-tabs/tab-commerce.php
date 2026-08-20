<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="tab-global-commerce">
	<?php if ( function_exists( 'almaden_bookster_book_product_render_panel' ) ) : ?>
		<?php almaden_bookster_book_product_render_panel( $book_id ); ?>
	<?php else : ?>
		<p class="text-sm text-[var(--text-muted)]">El módulo de productos no está disponible.</p>
	<?php endif; ?>
</div>
