/**
 * @author Mateus Costa <mateus@costamateus.com.br>
 * @since 1.0.0
 */

/*global wc_spg_params, wc_checkout_params, PagSeguroDirectPayment */
(function( $ ) {
	"use strict";

	$( function() {

		let suprempay_submit = false;

		/**
		 * Set credit card brand.
		 *
		 * @param {string} brand
		 */
		// function supremPaySetCreditCardBrand( brand )
		// {
		// 	$( "#suprempay-cc-form" ).attr( "data-cc-brand", brand );
		// }

		/**
		 * Format price.
		 *
		 * @param  {int|float} price
		 *
		 * @return {string}
		 */
		// function supremPayGetPriceText( installment )
		// {
		// 	const installmentParsed = "R$ " + parseFloat( installment.installmentAmount, 10 ).toFixed( 2 ).replace( ".", "," ).toString();
		// 	const totalParsed       = "R$ " + parseFloat( installment.totalAmount,       10 ).toFixed( 2 ).replace( ".", "," ).toString();
		// 	const interestFree      = ( installment.interestFree === true ) ? wc_spg_params.interest_free : "";
		// 	const interestText      = interestFree ? interestFree : `(${totalParsed})`;
		//
		// 	return `${installment.quantity}x ${installmentParsed} ${interestText}`;
		// }

		/**
		 * Get installment option.
		 *
		 * @param  {object} installment
		 *
		 * @return {string}
		 */
		// function supremPayGetInstallmentOption( installment )
		// {
		// 	const text   = supremPayGetPriceText( installment );
		// 	const qntity = installment.quantity;
		// 	const amount = installment.installmentAmount;
		//
		// 	return `<option value="${qntity}" data-installment-value="${amount}">${text}</option>`;
		// }

		/**
		 * Add error message
		 *
		 * @param {string} error
		 */
		function supremPayAddErrorMessage( error, id )
		{
			let wrapper = $( `#${id}` );
			$( ".woocommerce-error", wrapper ).remove();
			wrapper.prepend( `<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">${error}</div>` );
		}

		/**
		 * Hide payment methods if have only one.
		 */
		function supremPayHidePaymentMethods()
		{
			let paymentMethods = $( "#suprempay-payment-methods" );
			if ( $( "input[type=radio]", paymentMethods ).length === 1 ) paymentMethods.hide();
		}

		/**
		 * Show/hide the method form.
		 *
		 * @param {string} method
		 */
		function supremPayShowHideMethodForm( method )
		{
			$( ".suprempay-method-form"              ).hide();
			$( "#suprempay-payment-methods li"       ).removeClass( "active" );
			$( `#suprempay-${method}-form`           ).show();
			$( `#suprempay-payment-method-${method}` ).parent( "label" ).parent( "li" ).addClass( "active" );
		}

		function supremPayShowHideCpfCnpj( checked )
		{
			if ( checked )
			{
				$( "#suprempay-card-holder-cpf" ).hide();
				$( "#suprempay-card-holder-cnpj" ).show();
			}
			else
			{
				$( "#suprempay-card-holder-cpf" ).show();
				$( "#suprempay-card-holder-cnpj" ).hide();
			}
		}

		/**
		 * Initialize the payment form.
		 */
		function supremPayInitPaymentForm()
		{
			supremPayHidePaymentMethods();

			$( "#suprempay-payment-form" ).show();

			supremPayShowHideMethodForm( $( "#suprempay-payment-methods input[type=radio]:checked" ).val() );

			if ( wc_spg_params.credit_card.enabled === "yes" )
			{
				// CPF.
				$( "#suprempay-card-holder-cpf-field" ).mask( "000.000.000-00" );
				// CNPJ
				$( "#suprempay-card-holder-cnpj-field" ).mask( "00.000.000/0000-00" );
				$( "#suprempay-card-holder-cnpj" ).hide();

				// Birth Date.
				$( "#suprempay-card-holder-birth-date-field" ).mask( "00/00/0000" );

				// Phone.
				let MaskBehavior = function( val ) {
					return val.replace( /\D/g, "" ).length === 11 ? "(00) 00000-0000" : "(00) 0000-00009";
				}, maskOptions = {
					onKeyPress: function( val, e, field, options ) { field.mask( MaskBehavior.apply( {}, arguments ), options ); }
				};
				$( "#suprempay-card-holder-phone-field" ).mask( MaskBehavior, maskOptions );
			}

			$( "#suprempay-pix-form input[type=radio]:checked" ).parent( "label" ).parent( "li" ).addClass( "active" );
		}

		/**
		 * Form Handler.
		 *
		 * @return {bool}
		 */
		function supremPayformHandler()
		{
			if ( suprempay_submit )
			{
				suprempay_submit = false;
				return true;
			}

			// if SupremPay is not payment checked
			if ( !$( "#payment_method_suprempay" ).is( ":checked" ) ) return true;

			// if SupremPay Transfer is checked
			if ( $( "#suprempay-payment-method-transfer" ).is( ":checked" ) )
			{
				let form      = $( "form.checkout, form #order_review" );

				let email     = $( "#suprempay-auth-email-field" ).val();
				let code      = $( "#suprempay-auth-code-field" ).val();
				let error     = false;
				let errorHtml = "<ul>";

				if ( code.length != 6 )
				{
					errorHtml += `<li>${wc_spg_params.messages.invalid_2auth_number}</li>`;
					error      = true;
				}

				if ( isNaN( code ) )
				{
					errorHtml += `<li>${wc_spg_params.messages.invalid_2auth}</li>`;
					error      = true;
				}

				errorHtml += "</ul>";

				if ( error )
				{
					supremPayAddErrorMessage( errorHtml, "suprempay-transfer-form" );
				}
				else
				{
					// Submit the form.
					suprempay_submit = true;
					form.submit();
				}

				return false;
			}

			// if SupremPay Credit Card is not checked
			if ( !$( "#suprempay-payment-method-cc" ).is( ":checked" ) ) return true;

			return true;

			// let form            = $( "form.checkout, form#order_review" );
			// let error           = false;
			// let errorHtml       = "";

			// let cpf             = $( "#suprempay-card-holder-cpf-field",   form ).val();
			// let cnpj            = $( "#suprempay-card-holder-cnpj-field",  form ).val();
			// let phone           = $( "#suprempay-card-holder-phone-field", form ).val();
			// let holder          = $( "#suprempay-card-holder-name-field",  form ).val();

			// let cardNumber      = $( "#suprempay-card-number-field",       form ).val().replace( /[^\d]/g, "" );
			// let cvc             = $( "#suprempay-card-cvc-field",          form ).val();
			// let expirationMonth = $( "#suprempay-card-expiry-field",       form ).val().replace( /[^\d]/g, "" ).substr( 0, 2 );
			// let expirationYear  = $( "#suprempay-card-expiry-field",       form ).val().replace( /[^\d]/g, "" ).substr( 2 );
			// let installment     = $( "#suprempay-card-installment-field",  form ).val();

			// let creditCardForm  = $( "#suprempay-cc-form", form );
			// let brand           = creditCardForm.attr( "data-cc-brand" );
			// let today           = new Date();

			// // Validate the credit card data.
			// errorHtml += "<ul>";

			// // Validate the holder name.
			// if ( holder.length == 0 )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_holder}</li>`;
			// 	error      = true;
			// }

			// // Validate the card brand.
			// if ( typeof brand === "undefined" || brand === "error" )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_card}</li>`;
			// 	error      = true;
			// }
			// else if ( brand === "unsupported" )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.unsupported_card}</li>`;
			// 	error      = true;
			// }

			// // Validate the expiry date.
			// if ( expirationMonth.length !== 2 || expirationYear.length !== 4 )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_expiry}</li>`;
			// 	error      = true;
			// }
			// if ( ( expirationMonth.length === 2  && expirationYear.length === 4 ) &&
			// 	 ( expirationMonth > 12                           ||
			// 	   expirationYear <= ( today.getFullYear() - 1 )  ||
			// 	   expirationYear >= ( today.getFullYear() + 20 ) ||
			// 	   ( expirationMonth < ( today.getMonth() + 2 ) && expirationYear.toString() === today.getFullYear().toString() ) ) )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.expired_date}</li>`;
			// 	error      = true;
			// }

			// // Validate the cvc.
			// if ( cvc.length == 0 )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_cvv}</li>`;
			// 	error      = true;
			// }

			// // Installments.
			// if ( installment === "0" )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.empty_installment}</li>`;
			// 	error      = true;
			// }

			// // Validate the cpf/cnpj.
			// if ( cpf.length == 0 && cnpj.length == 0)
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_h_cpf}</li>`;
			// 	error      = true;
			// }

			// // Validate the phone.
			// if ( phone.length == 0 )
			// {
			// 	errorHtml += `<li>${wc_spg_params.messages.invalid_h_phone}</li>`;
			// 	error      = true;
			// }

			// errorHtml += "</ul>";

			// // Display the error messages.
			// if ( error )
			// {
			// 	supremPayAddErrorMessage( errorHtml );
			// }
			// else
			// {
            //     form.append( $( "<input name='suprempay_card_number'      type='hidden' />" ).val( cardNumber                    ) );
            //     form.append( $( "<input name='suprempay_card_cvc'         type='hidden' />" ).val( cvc                           ) );
            //     form.append( $( "<input name='suprempay_card_exp_month'   type='hidden' />" ).val( expirationMonth               ) );
            //     form.append( $( "<input name='suprempay_card_exp_year'    type='hidden' />" ).val( expirationYear.substring( 2 ) ) );
            //     form.append( $( "<input name='suprempay_card_brand'       type='hidden' />" ).val( brand                         ) );
            //     form.append( $( "<input name='suprempay_card_installment' type='hidden' />" ).val( installment                   ) );

            //     // Submit the form.
            //     suprempay_submit = true;
            //     form.submit();
			// }

			// return false;
		}


		if ( wc_spg_params.credit_card.enabled === "yes" && wc_spg_params.credit_card.session_id )
		{
			// TODO session_id não funciona
			PagSeguroDirectPayment.setSessionId( wc_spg_params.credit_card.session_id );

			PagSeguroDirectPayment.getPaymentMethods({
				amount: 500.00,
				success: function( response ) {
					// Retorna os meios de pagamento disponíveis.
					console.log( "success", response );
				},
				error: function( response ) {
					// Callback para chamadas que falharam.
					console.log( "error", response );
				}
			});

			// Switch CPF / CNPJ
			$( "body" ).on( "click", "#suprempay-legal-person-field", function() {
				supremPayShowHideCpfCnpj( $( this ).is( ":checked" ) );
			});

			// Get the credit card brand.
			$( "body" ).on( "focusout", "#suprempay-card-number-field", function() {
				let bin = $( this ).val().replace( /[^\d]/g, '' ).substr( 0, 6 );

				// if ( bin.length === 6 )
				// {
				// 	PagSeguroDirectPayment.getBrand( {
				// 		cardBin: bin,
				// 		success: function( data ) {
				// 			console.log( data );
				//
				// 			$( "body" ).trigger( "suprempay_credit_card_brand", data.brand.name );
				// 			supremPaySetCreditCardBrand( data.brand.name );
				// 		},
				// 		error: function() {
				// 			$( "body" ).trigger( "suprempay_credit_card_brand", "error" );
				// 			supremPaySetCreditCardBrand( "error" );
				// 		}
				// 	} );
				// }
			} );
			$( "body" ).on( "updated_checkout", function() {
				let field = $( "body #suprempay-card-number-field" );
				if ( field.length > 0 ) field.focusout();
			} );

			// Set the errors.
			$( "body" ).on( "focus", "#suprempay-card-number-field, #suprempay-card-expiry-field", function() {
				$( "#suprempay-cc-form .woocommerce-error" ).remove();
			} );

			$( "body" ).on( "suprempay_credit_card_brand", function( e, brand ) {
				if ( brand === "error" )
					supremPayAddErrorMessage( wc_spg_params.messages.invalid_card );
			} );
		}
		else
		{
			$( "body" ).on( "updated_checkout", function() {
				$( "#suprempay-cc-form" ).remove();
				$( "#suprempay-payment-method-cc" ).parent().parent().remove();
			});
		}

		// Display the payment for and init the input masks.
		if ( wc_checkout_params.is_checkout === "1" )
		{
			$( "body" ).on( "updated_checkout", function() {
				supremPayInitPaymentForm();
			});
		}
		else
		{
			supremPayInitPaymentForm();
		}

		// Switch the payment method form.
		$( "body" ).on( "click", "#suprempay-payment-methods input[type=radio]", function() {
			supremPayShowHideMethodForm( $( this ).val() );
		});

		// Process the credit card data when submit the checkout form.
		$( "form.checkout" ).on( "checkout_place_order", function() {
			return supremPayformHandler();
		});

		$( "form#order_review" ).submit( function() {
			return supremPayformHandler();
		});
	});

}( jQuery ));
