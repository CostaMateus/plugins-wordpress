/**
 * global triibo_payments_card_params, wc_checkout_params
 */

( function( $ )
{
    "use strict";

    $( function()
    {
        let tpc_submit = false;
        let tpc_data   = triibo_payments_card_params;

        /**
         * Initialize mask of the payment form.
         */
        function triibo_p_c_card_init_mask()
        {
            // cpf
            $( "#triibo_payments_card-card-holder-cpf"  ).mask( "000.000.000-00" );

            // validate
            $( "#triibo_payments_card-card-expiry"      ).parent().removeClass( "form-row-first" ).addClass( "form-row-wide" );

            // cvc
            $( "#triibo_payments_card-card-cvc"         ).css( "width", "" ).parent().removeClass( "form-row-last" ).addClass( "form-row-wide" );

            // phone
            let MaskBehavior = function( val ) {
                return val.replace( /\D/g, "" ).length === 11 ? "(00) 00000-0000" : "(00) 0000-00009";
            }, maskOptions = {
                onKeyPress: function( val, e, field, options ) { field.mask( MaskBehavior.apply( {}, arguments ), options ); }
            };
            $( "#triibo_payments_card-card-holder-phone" ).mask( MaskBehavior, maskOptions );

            // User without token
            if ( tpc_data.invalid_token ) triibo_p_c_add_error_message( tpc_data.messages.invalid_token );

			$( "#wc-triibo_payments_card-cc-form input"  ).css( "font-size", "1.5em" ).css( "padding", "8px" );
			$( "#wc-triibo_payments_card-cc-form select" ).css( "font-size", "1.5em" ).css( "padding", "8px" );
        }

		/**
		 * Set credit card brand.
		 *
		 * @param {string} brand
		 */
		function triibo_p_c_set_card_brand( brand )
		{
			$( "#wc-triibo_payments_card-cc-form" ).attr( "data-cc-brand", brand );
		}

		/**
		 * Add error message
		 *
		 * @param {string} error
		 */
		function triibo_p_c_add_error_message( error )
		{
			let wrapper = $( "#wc-triibo_payments_card-cc-form" );

			$( ".woocommerce-error", wrapper ).remove();

			wrapper.prepend( `<div class="woocommerce-error" style="margin-bottom: 0.5em !important;">${error}</div>` );
		}

		/**
		 * Format price.
		 *
		 * @param  {int} i
		 * @param  {int|float} total
		 *
		 * @return {string}
		 */
		function triibo_p_c_get_price_text( i, total )
		{
			let price = parseFloat( total / i, 10 ).toFixed( 2 ).replace( '.', ',' ).toString();
			let text  = `${i}x de R$ ${price}`;

			if ( i > 1 ) text += " sem juros";

			return text;
		}

		/**
		 * Get installment option.
		 *
		 * @return {string}
		 */
		function triibo_p_c_get_installment_options()
		{
			let instalmments = $( "body #triibo_payments_card-card-installment" );
			instalmments.empty();

			let installment  = tpc_data.installment;
			let html         = `<option value='0' hidden selected >${tpc_data.card_messages.empty_installments}</option>`;
			let text         = "";

			if ( installment.type == "avista" || installment.count == 1 )
			{
				text  = triibo_p_c_get_price_text( 1, installment.total);
				html += `<option value='1' selected style='color:#000;' >${text}</option>`;

				instalmments.attr( "disabled", "disabled" );
			}
			else
			{
				for ( let i = 0; i < installment.count; i++)
				{
					text  = triibo_p_c_get_price_text( i + 1, installment.total );
					html += `<option value='${i + 1}' style='color:#000;' >${text}</option>`;
				}

				instalmments.removeAttr( 'disabled' );
			}

			instalmments.append( html );
		}

		/**
		 * Change select css
		 *
		 * @param {int} value
		 */
		function triibo_p_c_options_css( value )
		{
			if ( value != 0 ) $( "#triibo_payments_card-card-installment" ).css( "color", "#000" );
		}

		/**
		 * Form Handler.
		 *
		 * @return {bool}
		 */
		function triibo_p_c_form_handle()
		{
            if ( tpc_submit )
            {
                tpc_submit = false;
                return true;
            }

            let form        = $( "form.checkout, form#order_review" );
            let error       = false;
            let error_html  = "";

            let h_name      = $( "#triibo_payments_card-card-holder-name",  form ).val();
            let h_cpf       = $( "#triibo_payments_card-card-holder-cpf",   form ).val().replace( /[^\d]/g, "" );
            let h_phone     = $( "#triibo_payments_card-card-holder-phone", form ).val().replace( /[^\d]/g, "" );

            let card_number = $( "#triibo_payments_card-card-number",       form ).val().replace( /[^\d]/g, "" );
            let exp_month   = $( "#triibo_payments_card-card-expiry",       form ).val().replace( /[^\d]/g, "" ).substr( 0, 2 );
            let exp_year    = $( "#triibo_payments_card-card-expiry",       form ).val().replace( /[^\d]/g, "" ).substr( 2 );
            let cvc         = $( "#triibo_payments_card-card-cvc",          form ).val();
            let installment = $( "#triibo_payments_card-card-installment",  form ).val();

            let cc_form     = $( "#wc-triibo_payments_card-cc-form",        form );
            let brand       = cc_form.attr( "data-cc-brand" );

            let today       = new Date();

			// Validate the credit card data.
			error_html += "<ul>";

			// Validate the holder name.
			if ( h_name.length == 0 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_holder_name}</li>`;
				error      = true;
			}

			// Validate the holder cpf.
			if ( h_cpf.length == 0 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_holder_cpf}</li>`;
				error      = true;
			}

			// Validate the holder phone.
			if ( h_phone.length == 0 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_holder_phone}</li>`;
				error      = true;
			}

			// Validate the card number.
			if ( card_number.length == 0 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_card}</li>`;
				error      = true;
			}

			// Validate the card brand.
			if ( brand === undefined || brand === "error" )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_brand}</li>`;
				error      = true;
			}
			else if ( brand === "unsupported" )
			{
				error_html += `<li>${tpc_data.card_messages.unsupported_brand}</li>`;
				error      = true;
			}

			// Validate the expiry date.
			if ( exp_month.length !== 2 || exp_year.length !== 2 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_expiry}</li>`;
				error      = true;
			}
			if ( exp_month.length === 2 && exp_year.length === 2 )
			{
				exp_year = 2000 + parseInt( exp_year );

				if ( exp_month > 12                           ||
					 exp_year <= ( today.getFullYear() - 1 )  ||
					 exp_year >= ( today.getFullYear() + 20 ) ||
					 ( exp_month < ( today.getMonth() + 2 ) && exp_year.toString() === today.getFullYear().toString() ) )
				{
					error_html += `<li>${tpc_data.card_messages.expired_date}</li>`;
					error      = true;
				}
			}

			// Validate the cvc.
			if ( cvc.length == 0 || cvc.lenght > 4 )
			{
				error_html += `<li>${tpc_data.card_messages.invalid_cvc}</li>`;
				error      = true;
			}

			// Installments.
			if ( installment === "0" )
			{
				error_html += `<li>${tpc_data.card_messages.empty_installments}</li>`;
				error      = true;
			}

			error_html += "</ul>";

			// Display the error messages.
			if ( error )
			{
				triibo_p_c_add_error_message( error_html );
			}
            else
            {
                form.append( $( "<input name='triibo_payments_card-card-brand'       type='hidden' />" ).val( brand       ) );
                form.append( $( "<input name='triibo_payments_card-card-installment' type='hidden' />" ).val( installment ) );

                // submit the form.
                tpc_submit = true;
                form.submit();
            }

			return false;
        }

		// Get the credit card brand.
		$( "body" ).on( "focusout", "#triibo_payments_card-card-number", function() {
			let bin = $( this ).val().replace( /[^\d]/g, '-' );

			if ( bin.length >= 14 && bin.length <= 20 )
			{
                $.ajax( {
                    url  : `${tpc_data.rest_url}?bin=${bin}&user_id=${tpc_data.user_id}`,
                    type : "GET",
                    success: function( data ) {
						let status = JSON.parse( data );

                        if ( !status.success || !status.brandInfo.supported )
                        {
                            $( "body" ).trigger( "triibo_p_c_card_brand", "error" );
                            triibo_p_c_set_card_brand( "error" );
						}
						else if ( status.brandInfo.supported )
						{
							$( "body" ).trigger( "triibo_p_c_card_brand", status.brandInfo.brand );
							triibo_p_c_set_card_brand( status.brandInfo.brand );
						}
                    },
                    error: function( err ) {
                        // console.log( err );
                        $( "body" ).trigger( "triibo_p_c_card_brand", "error" );
                        triibo_p_c_set_card_brand( "error" );
                    }
                } );
			}
		} );
		$( "body" ).on( "updated_checkout", function() {
			let field = $( "body #triibo_payments_card-card-number" );
			if ( field.length > 0 ) field.focusout();
		} );

		// Clear errors.
		$( "body" ).on( "focus", "#triibo_payments_card-card-number", function() {
			$( "#wc-triibo_payments_card-cc-form .woocommerce-error" ).remove();
		} );

		// Show brand error
		$( "body" ).on( "triibo_p_c_card_brand", function( e, brand ) {
            if ( brand === "unsupported" || brand === "error" )
				triibo_p_c_add_error_message( tpc_data.card_messages.unsupported_invalid );
		} );

        // Display the payment for and init the input masks.
        if ( typeof wc_checkout_params != "undefined" && wc_checkout_params.is_checkout === "1" )
        {
            $( "body" ).on( "updated_checkout", function() {
                triibo_p_c_card_init_mask();
				triibo_p_c_get_installment_options();
				triibo_p_c_options_css( $( "#triibo_payments_card-card-installment" ).val() );
            } );
        }
        else
        {
            triibo_p_c_card_init_mask();
			triibo_p_c_get_installment_options();
			triibo_p_c_options_css( $( "#triibo_payments_card-card-installment" ).val() );
		}

		// Validate select css
		$( "body" ).on( "change", "#triibo_payments_card-card-installment", function () {
			triibo_p_c_options_css( $( this ).val() );
		} );

		// Process the credit card data when submit the checkout form.
		$( "form.checkout" ).on( "checkout_place_order", function() {
            var payment_method = $( 'form.checkout input[name="payment_method"]:checked' ).val();

			if ( payment_method == tpc_data.id ) return triibo_p_c_form_handle();

			return true;
		});
		$( "form#order_review" ).submit( function() {
			return triibo_p_c_form_handle();
		});
    } );

}( jQuery ) );