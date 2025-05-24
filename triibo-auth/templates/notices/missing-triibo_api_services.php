<?php
/**
 * Missing Triibo API Services notice.
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
	$key          = "triibo-api-services/triibo-api-services.php";
	$is_installed = array_key_exists( key: $key, array: $all_plugins );
}
?>

<div class="error">
	<p>
		<strong>
			<?php esc_html_e( text: "#Triibo_Auth", domain: TRIIBO_AUTH_DOMAIN ); ?>
		</strong>
		<?php esc_html_e( text: "depende da última versão do 'Triibo API Services' para funcionar!", domain: TRIIBO_AUTH_DOMAIN ); ?>
	</p>

	<?php if ( $is_installed && current_user_can( capability: "install_plugins" ) ) : ?>
		<p>
			<a href="<?php echo esc_url( url: wp_nonce_url( actionurl: self_admin_url( path: "plugins.php?action=activate&plugin=triibo-api-services/triibo-api-services.php&plugin_status=active" ), action: "activate-plugin_triibo-api-services/triibo-api-services.php" ) ); ?>" class="button button-primary" >
				<?php esc_html_e( text: "Ative 'Triibo API Services'", domain: TRIIBO_AUTH_DOMAIN ); ?>
			</a>
		</p>
	<?php else : ?>
		<p>
			<?php esc_html_e( text: "Instale 'Triibo API Services'", domain: TRIIBO_AUTH_DOMAIN ); ?>
		</p>
	<?php endif; ?>
</div>
