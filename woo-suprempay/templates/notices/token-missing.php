<?php
/**
 * Admin View: Notice - Tokens missing
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
		<?php _e( "Você deve informar as chaves de usuário e de integração (sandbox/produção), para começar a usar o plugin.", WC_SUPREMPAY_DOMAIN ); ?>
	</p>
</div>
