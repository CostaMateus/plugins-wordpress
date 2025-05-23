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
	$key          = "woocommerce-subscriptions/woocommerce-subscriptions.php";
	$is_installed = array_key_exists( key: $key, array: $all_plugins );
}
?>

<div class="error">
	<p>
		<strong>
			<?php esc_html_e( text: "#Triibo_Assinatura", domain: WC_Triibo_Assinaturas::DOMAIN ); ?>
		</strong>
		<?php esc_html_e( text: "depende da última versão do WooCommerce Subscriptions para funcionar!", domain: WC_Triibo_Assinaturas::DOMAIN ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( capability: "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( url: wp_nonce_url( actionurl: self_admin_url( path: "plugins.php?action=activate&plugin=woocommerce-subscriptions/woocommerce-subscriptions.php&plugin_status=active" ), action: "activate-plugin_woocommerce-subscriptions/woocommerce-subscriptions.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( text: "Ative WooCommerce Subscriptions", domain: WC_Triibo_Assinaturas::DOMAIN ); ?>
			</a>
		</p>
	<?php else : ?>
		<p>
			<a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=10217&cid=1068958" target="_blank" class="button button-primary">
				<?php esc_html_e( text: "Purchase WooCommerce Subscriptions", domain: WC_Triibo_Assinaturas::DOMAIN ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
