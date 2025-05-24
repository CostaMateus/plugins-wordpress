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

<style>
	.form-table tr:nth-child( 1 ) th, .form-table tr:nth-child( 1 ) td { padding-top:0!important; }
</style>

<div class="wrap">
	<h2>Triibo Auth</h2>

	<?php settings_errors(); ?>

	<form method="POST" action="options.php">
		<?php
			settings_fields( option_group: TRIIBO_AUTH_ID . "_settings" );
			do_settings_sections( page: TRIIBO_AUTH_ID . "_settings" );
			submit_button();
		?>
	</form>
</div>

<script>
	( function ( $ ) {
		"use strict";

		let prefix = "<?=TRIIBO_AUTH_ID;?>";

		$( document ).ready( function() {
			let el1 = $( `#${prefix}_checkout_status` ).parent().parent().parent().parent().prev().prev();
			let el2 = $( `#${prefix}_login_status`    ).parent().parent().parent().parent().prev().prev();
			el1.css( "border-top", "1px solid #CCC" ).css( "padding-top", "2rem" );
			el2.css( "border-top", "1px solid #CCC" ).css( "padding-top", "2rem" );

			<?php
			/**
			 * @since 2.0.0 Add config whatsapp
			 */
			?>
			let el3 = $( `#${prefix}_wpp_status` ).parent().parent().parent().parent().prev().prev();
			el3.css( "border-top", "1px solid #CCC" ).css( "padding-top", "2rem" );
		} );

	} )( jQuery );
</script>
