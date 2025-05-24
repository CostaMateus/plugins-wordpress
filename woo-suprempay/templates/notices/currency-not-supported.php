<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 * 
 * @package WooCommerce_SupremPay/Notices
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;
?>

<div class="error inline" >
	<p>
		<strong>
			<?php _e( "WooCommerce SupremPay - Desativado", WC_SUPREMPAY_DOMAIN ); ?>
		</strong>:
		<?php printf( __( "A moeda <code>%s</code> não é suportada. Funciona apenas com Real Brasileiro.", WC_SUPREMPAY_DOMAIN ), get_woocommerce_currency() ); ?>
	</p>
</div>
