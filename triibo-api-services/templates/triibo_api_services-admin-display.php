<?php

/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @version 1.0.0
 */
?>

<div class="wrap">
	<h2>Triibo API Services</h2>

	<h3>Plugins do ecossistema</h3>
	<?php do_action( hook_name: "triibo_api_service_add_button" ); ?>

</div>
