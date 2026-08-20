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
>
	<div id="almaden-book-product-status" class="abp-status" role="status" aria-live="polite"></div>
	<div id="almaden-book-product-content"></div>
	<script id="almaden-book-product-initial-state" type="application/json"><?php echo wp_json_encode( $state ); ?></script>
</section>

