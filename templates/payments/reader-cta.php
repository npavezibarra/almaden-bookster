<?php
/**
 * Product reader call to action.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="almaden-bookster-reader-cta" class="almaden-bookster-reader-cta" style="margin: 16px 0 0; padding: 16px; border: 1px solid #e5e7eb; border-radius: 16px; background: #fafafa;">
	<div style="display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
		<div>
			<p style="margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.12em; color:#6b7280;">Bookster</p>
			<p style="margin:4px 0 0; font-size:16px; font-weight:700; color:#111827;">Lectura digital asociada a este producto</p>
		</div>
		<a class="button alt" href="<?php echo esc_url( $reader_url ); ?>" style="background:#111827; color:#fff; border-color:#111827;">
			<?php esc_html_e( 'LEER EBOOK', 'almaden-bookster' ); ?>
		</a>
	</div>
</div>
