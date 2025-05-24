/**
 * @author Mateus Costa <mateus@costamateus.com.br>
 * @since 1.0.0
 */

(function ( $ ) {
	"use strict";

	$( function () {

		// hml-prd
		function pagareSwitchUserData( checked )
		{
			let access_key_hml = $( "#woocommerce_pagare_access_key_hml" ).closest( "tr" ),
				access_key     = $( "#woocommerce_pagare_access_key"     ).closest( "tr" );

			if ( checked )
			{
				access_key_hml.show();
				access_key.hide();
			}
			else
			{
				access_key_hml.hide();
				access_key.show();
			}
		}

		// card
		function pagareSwitchCCData( checked )
		{
			let cc_type     = $( "#woocommerce_pagare_cc_type"     ),
				installment = $( "#woocommerce_pagare_installment" ).closest( "tr" );

			if ( checked )
			{
				cc_type.closest( "tr" ).show();

				if ( cc_type.val() == "avista" )
					installment.hide();
				else
					installment.show();
			}
			else
			{
				cc_type.closest( "tr" ).hide();
				installment.hide();
			}
		}
		function pagareSwitchTypeCC( value )
		{
			let installment = $( "#woocommerce_pagare_installment" ).closest( "tr" );

			if ( value == "avista" )
				installment.hide();
			else
				installment.show();
		}

		// pix / ticket
		function pagareSwitchDueDate( checked, field )
		{
			let due_date = $( `#woocommerce_pagare_${field}_due_date` ).closest( "tr" );

			if ( checked )
				due_date.show();
			else
				due_date.hide();
		}
		// pix
		function pagareSwitchPixData( checked )
		{
			let whatsapp = $( "#woocommerce_pagare_pix_whatsapp" ).closest( "tr" ),
				telegram = $( "#woocommerce_pagare_pix_telegram" ).closest( "tr" ),
				email    = $( "#woocommerce_pagare_pix_email"    ).closest( "tr" );

			if ( checked )
			{
				whatsapp.show();
				telegram.show();
				email.show();
			}
			else
			{
				whatsapp.hide();
				telegram.hide();
				email.hide();
			}
		}


		// hml/prd
		pagareSwitchUserData( $( "#woocommerce_pagare_is_homol" ).is( ":checked" ) );

		// card
		pagareSwitchCCData( $( "#woocommerce_pagare_cc" ).is( ":checked" ) );
		pagareSwitchTypeCC( $( "#woocommerce_pagare_cc_type" ).val() );

		// pix
		pagareSwitchDueDate( $( "#woocommerce_pagare_pix" ).is( ":checked" ), "pix" );
		pagareSwitchPixData( $( "#woocommerce_pagare_pix" ).is( ":checked" )        );

		// ticket
		pagareSwitchDueDate( $( "#woocommerce_pagare_ticket" ).is( ":checked" ), "ticket" );


		$( "body" ).on( "change", "#woocommerce_pagare_is_homol", function () {
			pagareSwitchUserData( $( this ).is( ":checked" ) );
		});

		// card
		$( "body" ).on( "change", "#woocommerce_pagare_cc", function () {
			pagareSwitchCCData( $( this ).is( ":checked" ) );
		});
		$( "body" ).on( "change", "#woocommerce_pagare_cc_type", function () {
			pagareSwitchTypeCC( $( this ).val() );
		});

		// pix
		$( "body" ).on( "change", "#woocommerce_pagare_pix", function () {
			pagareSwitchDueDate( $( this ).is( ":checked" ), "pix" );
			pagareSwitchPixData( $( this ).is( ":checked" ),       );
		});
		// ticket
		$( "body" ).on( "change", "#woocommerce_pagare_ticket", function () {
			pagareSwitchDueDate( $( this ).is( ":checked" ), "ticket" );
		});
	});

}( jQuery ));
