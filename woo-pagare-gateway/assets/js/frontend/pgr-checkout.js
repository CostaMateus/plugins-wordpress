/**
 * @author Mateus Costa <mateus@costamateus.com.br>
 * @since 1.0.0
 */

/*global wc_rpg_params, wc_checkout_params */
(function( $ ) {
	"use strict";

	$( function() {

		let pagare_submit = false;

		/**
		 * Set credit card brand.
		 *
		 * @param {string} brand
		 */
		function pagareSetCreditCardBrand( brand )
		{
			$( "#pagare-cc-form" ).attr( "data-cc-brand", brand );
		}

		/**
		 * Format price.
		 *
		 * @param  {int|float} price
		 *
		 * @return {string}
		 */
		function pagareGetPriceText( installment )
		{
			const installmentParsed = "R$ " + parseFloat( installment.installmentAmount, 10 ).toFixed( 2 ).replace( ".", "," ).toString();
			const totalParsed       = "R$ " + parseFloat( installment.totalAmount,       10 ).toFixed( 2 ).replace( ".", "," ).toString();
			const interestFree      = ( installment.interestFree === true ) ? wc_rpg_params.interest_free : "";
			const interestText      = interestFree ? interestFree : `(${totalParsed})`;

			return `${installment.quantity}x ${installmentParsed} ${interestText}`;
		}

		/**
		 * Get installment option.
		 *
		 * @param  {object} installment
		 *
		 * @return {string}
		 */
		function pagareGetInstallmentOption( installment )
		{
			const text   = pagareGetPriceText( installment );
			const qntity = installment.quantity;
			const amount = installment.installmentAmount;

			return `<option value="${qntity}" data-installment-value="${amount}">${text}</option>`;
		}

		/**
		 * Add error message
		 *
		 * @param {string} error
		 */
		function pagareAddErrorMessage( error )
		{
			let wrapper = $( "#pagare-cc-form" );
			$( ".woocommerce-error", wrapper ).remove();
			wrapper.prepend( `<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">${error}</div>` );
		}

		/**
		 * Hide payment methods if have only one.
		 */
		function pagareHidePaymentMethods()
		{
			let paymentMethods = $( "#pagare-payment-methods" );
			if ( $( "input[type=radio]", paymentMethods ).length === 1 ) paymentMethods.hide();
		}

		/**
		 * Show/hide the method form.
		 *
		 * @param {string} method
		 */
		function pagareShowHideMethodForm( method )
		{
			$( ".pagare-method-form"              ).hide();
			$( "#pagare-payment-methods li"       ).removeClass( "active" );
			$( `#pagare-${method}-form`           ).show();
			$( `#pagare-payment-method-${method}` ).parent( "label" ).parent( "li" ).addClass( "active" );
		}

		function pagareShowHideCpfCnpj( checked )
		{
			if ( checked )
			{
				$( "#pagare-card-holder-cpf" ).hide();
				$( "#pagare-card-holder-cnpj" ).show();
			}
			else
			{
				$( "#pagare-card-holder-cpf" ).show();
				$( "#pagare-card-holder-cnpj" ).hide();
			}
		}

		/**
		 * Initialize the payment form.
		 */
		function pagareInitPaymentForm()
		{
			pagareHidePaymentMethods();

			$( "#pagare-payment-form" ).show();

			pagareShowHideMethodForm( $( "#pagare-payment-methods input[type=radio]:checked" ).val() );

			// CPF.
			$( "#pagare-card-holder-cpf-field" ).mask( "000.000.000-00" );
			// CNPJ
			$( "#pagare-card-holder-cnpj-field" ).mask( "00.000.000/0000-00" );
			$( "#pagare-card-holder-cnpj" ).hide();

			// Birth Date.
			$( "#pagare-card-holder-birth-date-field" ).mask( "00/00/0000" );

			// Phone.
			let MaskBehavior = function( val ) {
				return val.replace( /\D/g, "" ).length === 11 ? "(00) 00000-0000" : "(00) 0000-00009";
			}, maskOptions = {
				onKeyPress: function( val, e, field, options ) { field.mask( MaskBehavior.apply( {}, arguments ), options ); }
			};
			$( "#pagare-card-holder-phone-field" ).mask( MaskBehavior, maskOptions );

			// $( "#pagare-pix-form input[type=radio]:checked" ).parent( "label" ).parent( "li" ).addClass( "active" );
		}

		/**
		 * Form Handler.
		 *
		 * @return {bool}
		 */
		function pagareformHandler()
		{
			if ( pagare_submit )
			{
				pagare_submit = false;
				return true;
			}

			if ( !$( "#payment_method_pagare"    ).is( ":checked" ) ) return true;

			if ( !$( "#pagare-payment-method-cc" ).is( ":checked" ) ) return true;

			let form            = $( "form.checkout, form#order_review" );
			let error           = false;
			let errorHtml       = "";

			let cpf             = $( "#pagare-card-holder-cpf-field",   form ).val();
			let cnpj            = $( "#pagare-card-holder-cnpj-field",  form ).val();
			let phone           = $( "#pagare-card-holder-phone-field", form ).val();
			let holder          = $( "#pagare-card-holder-name-field",  form ).val();

			let cardNumber      = $( "#pagare-card-number-field",       form ).val().replace( /[^\d]/g, "" );
			let cvc             = $( "#pagare-card-cvc-field",          form ).val();
			let expirationMonth = $( "#pagare-card-expiry-field",       form ).val().replace( /[^\d]/g, "" ).substr( 0, 2 );
			let expirationYear  = $( "#pagare-card-expiry-field",       form ).val().replace( /[^\d]/g, "" ).substr( 2 );
			let installment     = $( "#pagare-card-installment-field",  form ).val();

			let creditCardForm  = $( "#pagare-cc-form", form );
			let brand           = creditCardForm.attr( "data-cc-brand" );
			let today           = new Date();

			// Validate the credit card data.
			errorHtml += "<ul>";

			// Validate the holder name.
			if ( holder.length == 0 )
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_holder}</li>`;
				error      = true;
			}

			// Validate the card brand.
			if ( typeof brand === "undefined" || brand === "error" )
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_card}</li>`;
				error      = true;
			}
			else if ( brand === "unsupported" )
			{
				errorHtml += `<li>${wc_rpg_params.messages.unsupported_brand}</li>`;
				error      = true;
			}

			// Validate the expiry date.
			if ( expirationMonth.length !== 2 || ( expirationYear.length !== 2 && expirationYear.length !== 4 ) )
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_expiry}</li>`;
				error      = true;
			}
			if ( expirationMonth.length === 2 && expirationYear.length === 2 )
			{
				expirationYear += 2000;

				if ( expirationMonth > 12                           ||
					 expirationYear <= ( today.getFullYear() - 1 )  ||
					 expirationYear >= ( today.getFullYear() + 20 ) ||
					 ( expirationMonth < ( today.getMonth() + 2 ) && expirationYear.toString() === today.getFullYear().toString() ) )
				{
					errorHtml += `<li>${wc_rpg_params.messages.expired_date}</li>`;
					error      = true;
				}
			}

			// Validate the cvc.
			if ( cvc.length == 0 )
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_cvv}</li>`;
				error      = true;
			}

			// Installments.
			if ( installment === "0" )
			{
				errorHtml += `<li>${wc_rpg_params.messages.empty_installment}</li>`;
				error      = true;
			}

			// Validate the cpf/cnpj.
			if ( cpf.length == 0 && cnpj.length == 0)
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_h_cpf}</li>`;
				error      = true;
			}

			// Validate the phone.
			if ( phone.length == 0 )
			{
				errorHtml += `<li>${wc_rpg_params.messages.invalid_h_phone}</li>`;
				error      = true;
			}

			errorHtml += "</ul>";

			// Display the error messages.
			if ( error )
			{
				pagareAddErrorMessage( errorHtml );
			}
			else
			{
                form.append( $( "<input name='pagare_card_number'      type='hidden' />" ).val( cardNumber                    ) );
                form.append( $( "<input name='pagare_card_cvc'         type='hidden' />" ).val( cvc                           ) );
                form.append( $( "<input name='pagare_card_exp_month'   type='hidden' />" ).val( expirationMonth               ) );
                form.append( $( "<input name='pagare_card_exp_year'    type='hidden' />" ).val( expirationYear.substring( 2 ) ) );
                form.append( $( "<input name='pagare_card_brand'       type='hidden' />" ).val( brand                         ) );
                form.append( $( "<input name='pagare_card_installment' type='hidden' />" ).val( installment                   ) );

                // Submit the form.
                pagare_submit = true;
                form.submit();
			}

			return false;
		}

		// Display the payment for and init the input masks.
		if ( wc_checkout_params.is_checkout === "1" )
		{
			$( "body" ).on( "updated_checkout", function() {
				pagareInitPaymentForm();
			});
		}
		else
		{
			pagareInitPaymentForm();
		}

		// Switch CPF / CNPJ
		$( "body" ).on( "click", "#pagare-legal-person-field", function() {
			pagareShowHideCpfCnpj( $( this ).is( ":checked" ) );
		});

		// Switch the payment method form.
		$( "body" ).on( "click", "#pagare-payment-methods input[type=radio]", function() {
			pagareShowHideMethodForm( $( this ).val() );
		});

		// Get the credit card brand.
		$( "body" ).on( "focusout", "#pagare-card-number-field", function() {
			let bin = $( this ).val();

			if ( bin.length >= 16 && bin.length <= 20 )
			{
                $.ajax( {
                    url  : `${wc_rpg_params.base_url}/cards/brand`,
                    type : "POST",
                    data : { bin },
                    success: function( data ) {
						let status = JSON.parse( data );
						// console.log( status );

                        if ( status.error )
                        {
                            $( "body" ).trigger( "pagare_credit_card_brand", "error" );
                            pagareSetCreditCardBrand( "error" );
						}
						else if ( status.brandInfo.supported )
						{
							$( "body" ).trigger( "pagare_credit_card_brand", status.brandInfo.brand );
							pagareSetCreditCardBrand( status.brandInfo.brand );
						}
						else
						{
							$( "body" ).trigger( "pagare_credit_card_brand", "unsupported" );
							pagareSetCreditCardBrand( "unsupported" );
						}
                    },
                    error: function( err ) {
                        // console.log( err );
                        $( "body" ).trigger( "pagare_credit_card_brand", "error" );
                        pagareSetCreditCardBrand( "error" );
                    }
                } );
			}
		} );
		$( "body" ).on( "updated_checkout", function() {
			let field = $( "body #pagare-card-number-field" );
			if ( field.length > 0 ) field.focusout();
		} );

		// Set the errors.
		$( "body" ).on( "focus", "#pagare-card-number-field, #pagare-card-expiry-field", function() {
			$( "#pagare-cc-form .woocommerce-error" ).remove();
		} );

		$( "body" ).on( "pagare_credit_card_brand", function( e, brand ) {
            if ( brand === "unsupported" )
            {
                pagareAddErrorMessage( wc_rpg_params.messages.unsupported_brand );
            }
            else if ( brand === "error" )
            {
                pagareAddErrorMessage( wc_rpg_params.messages.invalid_card );
            }
			else
			{
				// get installments via API, v2
			}
		} );

		// Process the credit card data when submit the checkout form.
		$( "form.checkout" ).on( "checkout_place_order_pagare", function() {
			return pagareformHandler();
		});

		$( "form#order_review" ).submit( function() {
			return pagareformHandler();
		});
	});

}( jQuery ));
