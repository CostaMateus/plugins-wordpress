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
	<h2>Configurações | Triibo API Services</h2>

	<?php settings_errors(); ?>

	<form method="POST" action="options.php">
		<?php
			settings_fields( option_group: "triibo_api_services_general_settings" );
			do_settings_sections( page: "triibo_api_services_general_settings" );
			submit_button();
		?>
	</form>
</div>