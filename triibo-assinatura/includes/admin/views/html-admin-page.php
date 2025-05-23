<?php
/**
 * Admin options screen.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

if ( "yes" == $this->get_option( "enabled" ) )
{
	if ( ! $this->using_supported_currency() &&
			! class_exists( class: "woocommerce_wpml" ) ) include dirname( path: __FILE__ ) . "/html-notice-currency-not-supported.php";

	if ( $this->node->empty( "user" ) || $this->node->empty( "pass" ) )
		include dirname( __FILE__ ) . "/html-notice-account-missing.php";
}

?>

<h3><?php echo esc_html( text: $this->method_title ); ?></h3>
<?php echo wpautop( text: $this->method_description ); ?>

<table class="form-table">
	<?php $this->generate_settings_html(); ?>
</table>
