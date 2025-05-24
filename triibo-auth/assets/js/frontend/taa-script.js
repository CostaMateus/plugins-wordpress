( function ( $ )
{
    /**
     * @author Mateus Costa <mateus@costamateus.com.br>
     *
     * @since 1.0.0
     */

    "use strict";

    // vars new acc
    let taa_mna = document.getElementById( "taa-modal-new-account"         );
    let taa_bna = document.getElementById( "taa-btn-form-new-account"      );
    let taa_ena = document.getElementById( "taa-error-new-acc"             );

    // vars validate acc
    let taa_mva = document.getElementById( "taa-modal-validate-account"    );
    let taa_bva = document.getElementById( "taa-btn-form-validate-account" );
    let taa_eva = document.getElementById( "taa-error-validate-acc"        );

    let taa_cd         = 0;
    let taa_intervalId = null;
    let taa_ajaxurl    = taa_script_obj.ajaxurl;

    $( document ).ready( function() {

        let queryString = window.location.search;
        let urlParams   = new URLSearchParams( queryString );
        let code        = urlParams.get( "taac"  );
        let email       = urlParams.get( "email" );

        if ( code !== null )
        {
            switch ( code )
            {
                case "acc":
                    $( taa_mva ).removeClass( "ta-d-none" );
                    $( "#taa-validate-acc-email" ).val( email );
                break;

                case "newacc":
                    $( taa_mna ).removeClass( "ta-d-none" );
                    if ( email ) $( "#taa-new-acc-email" ).val( email );
                break;
            }
        }
    } );

    window.addEventListener( "click", function ( e ) {
        switch ( e.target.id )
        {
            case "taa-btn-form-new-account":
                e.preventDefault();
                taaSubmitNewAccount();
            break;

            case "taa-btn-form-validate-account":
                e.preventDefault();
                taaSubmitValAccount();
            break;
        }

    }, false );

    window.taaSubmitNewAccount = function ()
    {
        taaDisableBtns();

        $( taa_ena ).addClass( "ta-d-none" );
        $( "#taa-new-acc-email" ).removeClass( "ta-error-border" );
        $( "#taa-new-acc-pass1" ).removeClass( "ta-error-border" );

        let email = $( "#taa-new-acc-email" ).val();
        let pass  = $( "#taa-new-acc-pass"  ).val();
        let terms = $( "#taa-new-acc-terms" ).prop( "checked" );

        if ( !terms )
        {
            taaEnableBtns();
            $( taa_ena ).removeClass( "ta-d-none" ).html( "Vocé precisa aceitar os termos de uso." );
            return;
        }

        if ( email == "" )
        {
            taaEnableBtns();
            $( taa_ena ).removeClass( "ta-d-none" ).html( "Informe um e-mail válido" );
            $( "#taa-new-acc-email" ).addClass( "ta-error-border" );
            return;
        }

        if ( pass == "" )
        {
            taaEnableBtns();
            $( taa_ena ).removeClass( "ta-d-none" ).html( "Informe uma senha" );
            $( "#taa-new-acc-pass1" ).addClass( "ta-error-border" );
            return;
        }

        $.ajax( {
            url : taa_script_obj.ajaxurl,
            type: "POST",
            data: {
                action : "taa_new_account",
                email  : email,
                pass   : pass,
                terms  : terms,
            },
            success: function( data ) {
                let result = JSON.parse( data );

                if ( !result.success )
                {
                    taaEnableBtns();

                    let message = "";

                    switch ( result.error.code )
                    {
                        case 500:
                            message = "Falha na criação da conta. Tente novamente mais tarde.";
                        break;

                        case 401:
                            // email/password incorrect
                            $( "#taa-new-acc-pass" ).addClass( "ta-error-border" );
                            message = "E-mail/senha incorretos.";
                        break;
                    }

                    $( "#taa-new-acc-email" ).addClass( "ta-error-border" ).focus();
                    $( taa_ena ).removeClass( "ta-d-none" ).html( message );
                }
                else
                {
                    taaRefresh();
                }
            },
            error: function( err ) {
                taaAjaxError( err );
            }
        } );
    }

    window.taaSubmitValAccount = function ()
    {
        taaDisableBtns();

        $( taa_eva ).addClass( "ta-d-none" );
        $( "#taa-validate-acc-email" ).removeClass( "ta-error-border" );
        $( "#taa-validate-acc-pass"  ).removeClass( "ta-error-border" );

        let email = $( "#taa-validate-acc-email" ).val();
        let pass  = $( "#taa-validate-acc-pass"  ).val();
        let terms = $( "#taa-validate-acc-terms" ).prop( "checked" );

        if ( !terms )
        {
            taaEnableBtns();
            $( taa_eva ).removeClass( "ta-d-none" ).html( "Vocé precisa aceitar os termos de uso." );
            return;
        }

        if ( email == "" )
        {
            taaEnableBtns();
            $( taa_eva ).removeClass( "ta-d-none" ).html( "Informe um e-mail válido" );
            $( "#taa-validate-acc-email" ).addClass( "ta-error-border" );
            return;
        }

        if ( pass == "" )
        {
            taaEnableBtns();
            $( taa_eva ).removeClass( "ta-d-none" ).html( "Informe uma senha" );
            $( "#taa-validate-acc-pass" ).addClass( "ta-error-border" );
            return;
        }

        $.ajax( {
            url : taa_script_obj.ajaxurl,
            type: "POST",
            data: {
                action : "taa_validate_account",
                email  : email,
                pass   : pass,
                terms  : terms,
            },
            success: function( data ) {
                let result = JSON.parse( data );

                if ( !result.success )
                {
                    taaEnableBtns();

                    let message = "";

                    switch ( result.error.code )
                    {
                        case 404:
                            // user not found
                            message = "Usuário não encontrado.";
                        break;

                        case 401:
                            // email/password incorrect
                            $( "#taa-validate-acc-pass" ).addClass( "ta-error-border" );
                            message = "E-mail/senha incorretos.";
                        break;
                    }

                    $( "#taa-validate-acc-email" ).addClass( "ta-error-border" ).focus();
                    $( taa_eva ).removeClass( "ta-d-none" ).html( message );
                }
                else
                {
                    taaRefresh();
                }
            },
            error: function( err ) {
                taaAjaxError( err );
            }
        } );
    }

    window.taaCloseModal       = function ( e )
    {
        let id = e.id.replace( "close", "modal" );
        $( `#${id}` ).addClass( "ta-d-none" );
    }

    window.taaDisableBtns      = function ()
    {
        [ taa_bna, taa_bva, taa_bvc ].forEach( ( e ) => {
            $( e ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );
        } );
    }

    window.taaEnableBtns       = function ()
    {
        [ taa_bna, taa_bva, taa_bvc ].forEach( ( e ) => {
            $( e ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );
        } );
    }

    window.taaAjaxError        = function ( err )
    {
        console.log( err );
        taaRefresh();
    }

    window.taaRefresh          = function ()
    {
        let url = new URL( window.location.href );
        url.searchParams.delete( "taac"  );
        url.searchParams.delete( "email" );
        window.location.href = url.toString();
    }

} )( jQuery );
