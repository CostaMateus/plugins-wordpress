<?php
/**
 * Missing WooCommerce notice.
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
	$key          = "woocommerce/woocommerce.php";
	$is_installed = array_key_exists( key: $key, array: $all_plugins );
}
?>

<div class="error" >
	<p>
		<strong>
			<?php esc_html_e( text: "#Triibo_Payments", domain: "triibo_payments" ); ?>
		</strong>
		<?php esc_html_e( text: "depende da última versão do 'WooCommerce' para funcionar!", domain: "triibo_payments" ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( capability: "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( url: wp_nonce_url( actionurl: self_admin_url( path: "plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active" ), action: "activate-plugin_woocommerce/woocommerce.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( text: "Ative WooCommerce", domain: "triibo_payments" ); ?>
			</a>
		</p>
	<?php else :
		$url = ( current_user_can( capability: "install_plugins" ) ) ? wp_nonce_url( actionurl: self_admin_url( path: "update.php?action=install-plugin&plugin=woocommerce" ), action: "install-plugin_woocommerce" ) : "http://wordpress.org/plugins/woocommerce";
	?>
		<p>
			<a href="<?php echo esc_url( url: $url ); ?>" class="button button-primary" >
				<?php esc_html_e( text: "Instale WooCommerce", domain: "triibo_payments" ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
