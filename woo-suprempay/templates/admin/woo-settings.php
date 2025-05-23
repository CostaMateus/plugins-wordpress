<?php
/**
 * Admin options screen.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Admin/Settings
 *
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;
?>

<h3><?php echo esc_html( $this->method_title ); ?></h3>

<?php
	if ( "yes" == $this->get_option( "enabled" ) )
	{
		if ( !$this->using_supported_currency() && !class_exists( "woocommerce_wpml" ) )
			include dirname( WC_SUPREMPAY_PLUGIN_FILE ) . "/templates/notices/currency-not-supported.php";

		if ( "" === $this->get_token_user() || "" === $this->get_token_integration() )
			include dirname( WC_SUPREMPAY_PLUGIN_FILE ) . "/templates/notices/token-missing.php";
	}
?>

<?php echo wpautop( $this->method_description ); ?>

<table class="form-table" >
	<?php $this->generate_settings_html(); ?>
</table>
