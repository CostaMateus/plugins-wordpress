<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Admin/Notices
 */
defined( "ABSPATH" ) || exit;

?>

<div class="error inline">
	<p>
		<strong>
			<?php _e( "Pagare Gateway - Desativado", "pagare-gateway" ); ?>
		</strong>:
		<?php printf( __( "A moeda <code>%s</code> não é suportada. Funciona apenas com Real Brasileiro.", "pagare-gateway" ), get_woocommerce_currency() ); ?>
	</p>
</div>
