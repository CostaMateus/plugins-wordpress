<?php
/**
 * Admin View: Notice - WooCommerce Extra Checkout Fields for Brazil notice
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Admin/Notices
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

<div class="notice notice-error error" >
	<p>
		<strong>
			<?php esc_html_e( "Pagare Gateway", "pagare-gateway" ); ?>
		</strong>
		<?php esc_html_e( "depende da última versão do Extra Checkout Fields for Brazil para funcionar.", "pagare-gateway" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( "plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active" ), "activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( "Ative Extra Checkout Fields for Brazil", "pagare-gateway" ); ?>
			</a>
		</p>
	<?php else :
		if ( current_user_can( "install_plugins" ) )
		{
			$url = wp_nonce_url( self_admin_url( "update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil" ), "install-plugin_woocommerce-extra-checkout-fields-for-brazil" );
		}
		else
		{
			$url = "http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/";
		}
	?>
		<p>
			<a href="<?php echo esc_url( $url ); ?>" class="button button-primary" >
				<?php esc_html_e( "Instale Extra Checkout Fields for Brazil", "pagare-gateway" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
