<?php
/**
 * Admin View: Notice - WooCommerce Extra Checkout Fields for Brazil missing.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 * 
 * @package Woo_Packet/Notices
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

$is_installed = false;

if ( function_exists( "get_plugins" ) )
{
	$all_plugins  = get_plugins();
	$key          = "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php";
	$is_installed = array_key_exists( $key, $all_plugins );
}
?>

<div class="error" >
	<p>
		<strong>
			<?php esc_html_e( "WooCommerce SupremPay", WC_SUPREMPAY_DOMAIN ); ?>
		</strong>
		<?php esc_html_e( "depende da última versão do Extra Checkout Fields for Brazil para funcionar!", WC_SUPREMPAY_DOMAIN ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( "plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active" ), "activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( "Ative Extra Checkout Fields for Brazil", WC_SUPREMPAY_DOMAIN ); ?>
			</a>
		</p>
	<?php else :
		$url = ( current_user_can( "install_plugins" ) )
				? wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil" ), "install-plugin_woocommerce-extra-checkout-fields-for-brazil" )
				: "http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/";
	?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" >
				<?php esc_html_e( "Instale Extra Checkout Fields for Brazil", WC_SUPREMPAY_DOMAIN ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
