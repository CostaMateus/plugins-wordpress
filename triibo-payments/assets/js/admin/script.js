( function ( $ )
{
	"use strict";

	$( function ()
    {
		function triiboSwitchTypeCC( checked )
		{
			let installment = $( "#woocommerce_triibo_payments_card_installments" ).closest( "tr" );

			if ( checked )
                installment.show();
			else
                installment.hide();
		}

        triiboSwitchTypeCC( $( "#woocommerce_triibo_payments_card_installment_type" ).is( ":checked" ) );

		$( "body" ).on( "change", "#woocommerce_triibo_payments_card_installment_type", function () {
            triiboSwitchTypeCC( $( this ).is( ":checked" ) );
		});

	});

}( jQuery ) );
