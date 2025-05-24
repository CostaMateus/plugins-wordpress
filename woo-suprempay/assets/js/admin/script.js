/**
 * @author Mateus Costa <mateus@costamateus.com.br>
 * @since 1.0.0 
 */

( function ( $ ) {
	"use strict";

	$( function () {

		$( document ).ready( function() {
			var open  = "open";
			var close = "close";

			$( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( "#woocommerce_suprempay_token_user_hml"   ) );
			$( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( "#woocommerce_suprempay_token_user"       ) );
			$( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( "#woocommerce_suprempay_token_integr_hml" ) );
			$( "<button class='close show-hide' style='line-height:24px;' type='button'>Ver</button>" ).insertAfter( $( "#woocommerce_suprempay_token_integr"     ) );

			$( ".show-hide" ).on( "click", function() {
				if ( $( this ).hasClass( close ) )
				{
					$( this ).prev( "input[type='password']" ).prop( "type", "text"     );
					$( this ).removeClass( close );
					$( this ).addClass( open );
				}
				else
				{
					$( this ).prev( "input[type='text']"     ).prop( "type", "password" );
					$( this ).removeClass( open );
					$( this ).addClass( close );
				}
			} );
		} );

		// ========================================
		// hml / prd
		function suprempaySwitchUserData( checked )
		{
			let token_user_hml   = $( "#woocommerce_suprempay_token_user_hml"   ).closest( "tr" ),
				token_user       = $( "#woocommerce_suprempay_token_user"       ).closest( "tr" ),
				token_integr_hml = $( "#woocommerce_suprempay_token_integr_hml" ).closest( "tr" ),
				token_integr     = $( "#woocommerce_suprempay_token_integr"     ).closest( "tr" );

			if ( checked )
			{
				token_user_hml.show();
				token_user.hide();
				token_integr_hml.show();
				token_integr.hide();
			}
			else
			{
				token_user_hml.hide();
				token_user.show();
				token_integr_hml.hide();
				token_integr.show();
			}
		}

		suprempaySwitchUserData( $( "#woocommerce_suprempay_is_homol" ).is( ":checked" ) );

		$( "body" ).on( "change", "#woocommerce_suprempay_is_homol", function () {
			suprempaySwitchUserData( $( this ).is( ":checked" ) );
		});


		// ======================================
		// card
		function suprempaySwitchCCData( checked )
		{
			let cc_type     = $( "#woocommerce_suprempay_cc_type"     ),
				installment = $( "#woocommerce_suprempay_installment" ).closest( "tr" );

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
		function suprempaySwitchTypeCC( value )
		{
			let installment = $( "#woocommerce_suprempay_installment" ).closest( "tr" );

			if ( value == "avista" )
				installment.hide();
			else
				installment.show();
		}

		suprempaySwitchCCData( $( "#woocommerce_suprempay_cc" ).is( ":checked" ) );
		suprempaySwitchTypeCC( $( "#woocommerce_suprempay_cc_type" ).val() );

		$( "body" ).on( "change", "#woocommerce_suprempay_cc", function () {
			suprempaySwitchCCData( $( this ).is( ":checked" ) );
		});
		$( "body" ).on( "change", "#woocommerce_suprempay_cc_type", function () {
			suprempaySwitchTypeCC( $( this ).val() );
		});


		// ====================================================
		// pix / ticket
		function suprempaySwitchPixTicketData( checked, field )
		{
			let message  = $( `#woocommerce_suprempay_${field}_message`  ).closest( "tr" );
			let due_date = $( `#woocommerce_suprempay_${field}_due_date` ).closest( "tr" );

			if ( checked )
			{
				message.show();
				due_date.show();
			}
			else
			{
				message.hide();
				due_date.hide();
			}
		}

		suprempaySwitchPixTicketData( $( "#woocommerce_suprempay_pix"    ).is( ":checked" ), "pix"    );
		suprempaySwitchPixTicketData( $( "#woocommerce_suprempay_ticket" ).is( ":checked" ), "ticket" );

		$( "body" ).on( "change", "#woocommerce_suprempay_pix", function () {
			suprempaySwitchPixTicketData( $( this ).is( ":checked" ), "pix" );
		});
		$( "body" ).on( "change", "#woocommerce_suprempay_ticket", function () {
			suprempaySwitchPixTicketData( $( this ).is( ":checked" ), "ticket" );
		});
	} );

}( jQuery ) );
