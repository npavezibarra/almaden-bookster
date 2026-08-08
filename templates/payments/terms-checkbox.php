<?php
/**
 * Ebook terms acceptance field.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="almaden-bookster-terms-box" class="almaden-bookster-terms-box" style="margin: 0 0 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 14px; background: #fafafa;">
	<label style="display:flex; gap:12px; align-items:flex-start; cursor:pointer;">
		<input type="checkbox" name="almaden_bookster_terms_accepted" value="1" style="margin-top:4px;" />
		<span>
			<?php esc_html_e( 'Acepto los términos y condiciones antes de agregar este ebook al carrito.', 'almaden-bookster' ); ?>
			<a href="<?php echo esc_url( $terms_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver términos', 'almaden-bookster' ); ?></a>
		</span>
	</label>
	<input type="hidden" name="almaden_bookster_book_id" value="<?php echo esc_attr( $book_id ); ?>" />
	<?php wp_nonce_field( 'almaden_bookster_terms_' . $product_id, 'almaden_bookster_terms_nonce' ); ?>
</div>
