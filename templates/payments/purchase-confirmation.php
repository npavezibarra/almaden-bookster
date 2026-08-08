<?php
/**
 * Thank-you page ebook links.
 *
 * @package AlmadenBookster
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="almaden-bookster-purchase-confirmation" class="almaden-bookster-thankyou" style="margin-top:24px; padding:20px; border:1px solid #e5e7eb; border-radius:16px; background:#fff;">
	<h2 style="margin:0 0 12px;"><?php esc_html_e( 'Tu ebook está vinculado a esta compra', 'almaden-bookster' ); ?></h2>
	<p style="margin:0 0 12px;">
		<?php echo esc_html( $order->is_paid() ? __( 'Tu pago fue confirmado. Ya puedes abrir la ficha del libro y acceder al contenido completo.', 'almaden-bookster' ) : __( 'Cuando el pedido quede pagado, podrás abrir la ficha del libro y acceder al contenido completo.', 'almaden-bookster' ) ); ?>
	</p>
	<ul style="margin:0; padding-left:18px;">
		<?php foreach ( $books as $book ) : ?>
			<li><a href="<?php echo esc_url( $book['url'] ); ?>"><?php echo esc_html( $book['title'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</section>
