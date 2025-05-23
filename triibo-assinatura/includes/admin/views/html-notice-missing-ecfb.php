<?php
/**
 * Missing WooCommerce Extra Checkout Fields for Brazil notice
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

$is_installed = false;

if ( function_exists( function: "get_plugins" ) )
{
	$all_plugins  = get_plugins();
	$key          = "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php";
	$is_installed = array_key_exists( key: $key, array: $all_plugins );
}

?>

<div class="error">
	<p>
		<strong>
			<?php esc_html_e( text: "#Triibo_Assinatura", domain: "triibo_assinaturas" ); ?>
		</strong>
		<?php esc_html_e( text: "depende da última versão do Extra Checkout Fields for Brazil para funcionar!", domain: "triibo_assinaturas" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( capability: "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( url: wp_nonce_url( actionurl: self_admin_url( path: "plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active" ), action: "activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( text: "Ative Extra Checkout Fields for Brazil", domain: "triibo_assinaturas" ); ?>
			</a>
		</p>
	<?php else :
		if ( current_user_can( capability: "install_plugins" ) )
			$url = wp_nonce_url( actionurl: self_admin_url( path: "update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil" ), action: "install-plugin_woocommerce-extra-checkout-fields-for-brazil" );
		else
			$url = "http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/";
	?>
		<p>
			<a href="<?php echo esc_url( url: $url ); ?>" class="button button-primary">
				<?php esc_html_e( text: "Instale Extra Checkout Fields for Brazil", domain: "triibo_assinaturas" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
