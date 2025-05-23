<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
?>

<div class="wrap" >
	<h2>Triibo Fast Shop</h2>

	<?php
		$instance = null;
		$shipping = new WC_Shipping_Zones();
		$zones    = $shipping->get_zones();
		$index    = array_key_first( array: $zones );
		$methods  = $zones[ $index ][ "shipping_methods" ];

		foreach ( $methods as $key => $method )
			if ( $method && $method->id === Triibo_Fast_Shop_Shipping::ID )
				$instance = $method->instance_id;

        $url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=shipping&instance_id={$instance}" ) );
        $text = __( text: "WC Configurações", domain: Triibo_Fast_Shop_Shipping::DOMAIN );
        $link = "<a href='{$url}' >{$text}</a>";

        echo "<p>{$link}</p>";
    ?>

	<hr>

	<?php settings_errors(); ?>

	<form method="POST" action="options.php" >
		<?php
			settings_fields( option_group: "triibo_fast_shop_general_settings" );
			do_settings_sections( page: "triibo_fast_shop_general_settings" );
			submit_button();
		?>
	</form>
</div>