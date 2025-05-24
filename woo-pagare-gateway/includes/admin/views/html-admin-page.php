<?php
/**
 * Admin options screen.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Admin/Settings
 */
defined( "ABSPATH" ) || exit;
?>

<h3><?php echo esc_html( $this->method_title ); ?></h3>

<?php
	if ( "yes" == $this->get_option( "enabled" ) )
	{
		if ( !$this->using_supported_currency() && !class_exists( "woocommerce_wpml" ) )
			include dirname( __FILE__ ) . "/html-notice-currency-not-supported.php";

		if ( "" === $this->get_access_key() )
			include dirname( __FILE__ ) . "/html-notice-access_key-missing.php";
	}
?>

<?php echo wpautop( $this->method_description ); ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
