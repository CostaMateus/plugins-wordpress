(function ( $ ) {
	"use strict";

	$( function () {

		/**
		 * Awitch user data for sandbox and production.
		 *
		 * @param {String} checked
		 */
		function triiboAssinaturasSwitchUserData( checked )
		{
			var homol_account  = $( "#woocommerce_triibo_assinaturas_homol_account" ).closest( "tr" ),
				homol_password = $( "#woocommerce_triibo_assinaturas_homol_password" ).closest( "tr" ),
				account        = $( "#woocommerce_triibo_assinaturas_account" ).closest( "tr" ),
				password       = $( "#woocommerce_triibo_assinaturas_password" ).closest( "tr" );

			if ( checked )
			{
				account.hide();
				password.hide();
				homol_account.show();
				homol_password.show();
			}
			else
			{
				account.show();
				password.show();
				homol_account.hide();
				homol_password.hide();
			}
		}

		triiboAssinaturasSwitchUserData( $( "#woocommerce_triibo_assinaturas_homol" ).is( ":checked" ) );

		$( "body" ).on( "change", "#woocommerce_triibo_assinaturas_homol", function () {
			triiboAssinaturasSwitchUserData( $( this ).is( ":checked" ) );
		});
	});

}( jQuery ));
