( function ( $ )
{
    /**
     * @author Mateus Costa <mateus@costamateus.com.br>
     *
     * @since 1.0.0
     */

    "use strict";

    var maskBehavior = function ( val ) {
        return val.replace( /\D/g, "" ).length === 13 ? "+55 (00) 00000-0000" : "+55 (00) 0000-00009";
    },
    options = { onKeyPress: function( val, e, field, options ) {
        field.mask( maskBehavior.apply( {}, arguments ), options );
    } };
    $( "#tal-cellphone"     ).mask( maskBehavior, options );
    $( "#tal-cellphone-ckt" ).mask( maskBehavior, options );

    let tal_genericError1  = "Ocorreu um erro, tente novamente.";
    let tal_genericError2  = "Algo deu errado, tente novamente.";

    // vars parametros ajax
    let tal_redirect       = tal_script_obj.redirect;
    let tal_ajaxurl        = tal_script_obj.ajaxurl;
    let tal_ckt            = false;
    let tal_code           = null;
    let tal_codeEmail      = null;
    let tal_email          = null;
    let tal_pass           = null;
    let tal_terms          = null;
    let tal_cellphone      = null;
    let tal_transactionId  = null;

    /**
     * @since 2.0.0
     */
    let tal_send_type      = "sms";

    // vars auth
    let tal_error_auth     = document.getElementById( "tal-error-auth"                );
    let tal_btn_auth       = document.getElementById( "tal-btn-form-auth"             );

    // vars auth-checkout
    let tal_modal_ckt      = document.getElementById( "tal-modal-form-auth-ckt"       );
    let tal_error_auth_ckt = document.getElementById( "tal-error-auth-ckt"            );
    let tal_btn_auth_ckt   = document.getElementById( "tal-btn-form-auth-ckt"         );

    // vars validate
    let tal_modal_valid    = document.getElementById( "tal-modal-form-validate"       );
    let tal_error_valid    = document.getElementById( "tal-error-valid"               );
    let tal_btn_valid      = document.getElementById( "tal-btn-form-validate"         );

    // vars register
    let tal_modal_regis    = document.getElementById( "tal-modal-form-register"       );
    let tal_error_regis    = document.getElementById( "tal-error-register"            );
    let tal_btn_regis      = document.getElementById( "tal-btn-form-register"         );

    let tal_cd             = 0;
    let tal_intervalId     = null;

    window.addEventListener( "click", function ( e ) {
        switch ( e.target.id )
        {
            case "tal-close-form-auth":
            case "tal-close-form-validate":
            case "tal-close-form-validate-email":
            case "tal-close-form-register":
                window.talCloseModal();
            break;

            case "tal-btn-form-auth":
                e.preventDefault();
                window.talSubmitAuth( "#tal-cellphone", tal_error_auth );
            break;

            case "tal-btn-form-auth-ckt":
                e.preventDefault();
                tal_ckt = true;
                window.talSubmitAuth( "#tal-cellphone-ckt", tal_error_auth_ckt );
            break;

            case "tal-btn-form-validate":
                e.preventDefault();
                window.talSubmitValidate();
            break;

            case "tal-btn-form-register":
                e.preventDefault();
                window.talSubmitRegister();
            break;
        }
    }, false);

    /**
     * @since 2.0.0 Add new ID #tal-resend-wpp.
     * @since 1.0.0
     */
    window.talCountdown           = function ()
    {
        if ( tal_cd == 0 )
        {
            clearInterval( tal_intervalId );
            $( "#tal-resend-count"       ).addClass( "ta-d-none" ).html( "Reenviar código em 30" );
            $( "#tal-resend"             ).removeClass( "ta-d-none" );
            $( "#tal-resend-wpp"         ).removeClass( "ta-d-none" );

            $( "#tal-resend-email-count" ).addClass( "ta-d-none" ).html( "Reenviar código em 30" );
            $( "#tal-resend-email"       ).removeClass( "ta-d-none" );
        }
        else
        {
            $( "#tal-resend-count"       ).html( `Reenviar código em ${tal_cd}` );
            $( "#tal-resend-email-count" ).html( `Reenviar código em ${tal_cd}` );
            tal_cd -= 1;
        }
    }

    /**
     * @since 2.0.0 Add param type. Add new ID #tal-resend-wpp.
     * @since 1.0.0
     */
    window.talResendCode          = function ( type )
    {
        tal_send_type = type;

        $( "#tal-resend"     ).addClass( "ta-d-none" );
        $( "#tal-resend-wpp" ).addClass( "ta-d-none" );

        if ( tal_ckt )
            $( tal_btn_auth_ckt ).click();
        else
            $( tal_btn_auth     ).click();

        talDisableBtns();

        $( tal_error_valid    ).addClass( "ta-d-none" );
        $( "#tal-code"        ).removeClass( "ta-error-border" );
        $( "#tal-code-resend" ).removeClass( "ta-d-none" );

        setTimeout( function() {
            talEnableBtns();
            $( "#tal-code-resend" ).addClass( "ta-d-none" );
        }, 3000 );
    }

    window.talCloseModal          = function ()
    {
        if ( !tal_ckt )
        {
            talEnableBtns();
            talHideModals();
            talHideErros();
        }
    }

    window.talSubmitAuth          = function ( id, err )
    {
        talDisableBtns();

        tal_cellphone = $( id ).val();

        if ( tal_cellphone != "" )
        {
            if ( tal_cellphone.length == 19 || tal_cellphone.length == 18 )
            {
                $( err ).addClass( "ta-d-none" );
                $( id  ).removeClass( "ta-error-border" );

                talCallAuthAjax( id, err ); // send code by sms
            }
            else
            {
                talEnableBtns();
                $( err ).removeClass( "ta-d-none" ).html( "Insira um número válido." );
            }
        }
        else
        {
            talEnableBtns();
            $( err ).removeClass( "ta-d-none" ).html( "Insira um número de celular." );
        }
    }

    /**
     * @since 2.0.0 Add param type (sms/wpp).
     * @since 1.0.0
     */
    window.talCallAuthAjax        = function ( id, err )
    {
        $.ajax( {
            url  : tal_ajaxurl,
            type : "POST",
            data : {
                action    : "tal_auth_sms",
                cellphone : tal_cellphone,
                type      : tal_send_type,
            },
            success: function( data ) {
                let result = JSON.parse( data );

                talEnableBtns();

                if ( result == null )
                {
                    $( tal_error_auth ).removeClass( "ta-d-none" ).html( "Serviço temporáriamente indisponível." );
                    return;
                }

                if ( !result.success )
                {
                    let errorMsg = "";

                    if ( result.error.errorCode == 429 )
                    {
                        errorMsg = "Quantidade de tentativas excedida, tente novamente em 15min.";
                        $( id ).addClass( "ta-error-border" );
                    }
                    else
                    {
                        errorMsg = tal_genericError2;
                    }

                    $( err ).removeClass( "ta-d-none" ).html( errorMsg );
                }
                else
                {
                    tal_transactionId = result.success.transactionId;

                    if ( tal_ckt )
                    {
                        $( tal_modal_valid ).addClass( "ta-bg-modal-white" );
                        $( tal_modal_ckt   ).addClass( "ta-d-none" );
                    }
                    else
                    {
                        $( tal_modal_valid ).removeClass( "ta-bg-modal-white" );
                    }

                    $( tal_modal_valid ).removeClass( "ta-d-none" );
                    $( "#tal-code"     ).focus();

                    tal_cd         = 30;
                    tal_intervalId = setInterval( talCountdown, 1000 );
                    $( "#tal-resend-count" ).removeClass( "ta-d-none" );
                    $( "#tal-resend"       ).addClass( "ta-d-none" );
                }
            },
            error: function( err ) {
                talAjaxError( err );
            }
        } );
    }

    window.talSubmitValidate      = function ()
    {
        talDisableBtns();

        tal_code = $( "#tal-code" ).val();

        if ( tal_code != "" && tal_code.length == 6 )
        {
            $( tal_error_valid ).addClass( "ta-d-none" );
            $( "#tal-code" ).removeClass( "ta-error-border" );

            talCallValidateSMS(); // validate code
        }
        else
        {
            talEnableBtns();
            let errorMsg = ( tal_code == "" ) ? "Insira código recebido." : "O código tem 6 dígitos." ;
            $( tal_error_valid ).removeClass( "ta-d-none" ).html( errorMsg );
        }
    }

    window.talCallValidateSMS     = function ()
    {
        let id  = ( tal_ckt ) ? "#tal-cellphone-ckt" : "#tal-cellphone";
        let err = ( tal_ckt ) ? tal_error_auth_ckt   : tal_error_auth;

        $.ajax( {
            url  : tal_ajaxurl,
            type : "POST",
            data : {
                action        : "tal_validate_sms",
                code          : tal_code,
                cellphone     : tal_cellphone,
                transactionId : tal_transactionId
            },
            success: function( data ) {
                let result = JSON.parse( data );

                if ( !result.success )
                {
                    talEnableBtns();

                    switch ( result.error.errorCode )
                    {
                        case 1006:
                            $( tal_error_valid ).removeClass( "ta-d-none" ).html( "Código inválido." );
                            $( "#tal-code" ).addClass( "ta-error-border" );
                        break;

                        case 1007:
                            $( tal_error_valid ).removeClass( "ta-d-none" ).html( "Código expirado." );
                            $( "#tal-code" ).addClass( "ta-error-border" );
                        break;

                        case 1009:
                            $( tal_modal_ckt      ).removeClass( "ta-d-none" );
                            $( tal_error_auth_ckt ).removeClass( "ta-d-none" ).html( "Celular inválido." );
                            $( id                 ).addClass( "ta-error-border" );
                            $( tal_modal_valid    ).addClass( "ta-d-none"       );

                            $( "#tal-code" ).val( '' );
                        break;

                        default:
                            $( tal_modal_ckt      ).removeClass( "ta-d-none" );
                            $( tal_error_auth_ckt ).removeClass( "ta-d-none" ).html( "Algo de errado. Atualize a página e tente novamente." );
                            $( tal_modal_valid    ).addClass( "ta-d-none"       );

                            $( "#tal-code" ).val( '' );
                        break;
                    }
                }
                else
                {
                    talDisableBtns();

                    $( "#tal-modal-form-load" ).removeClass( "ta-d-none" );

                    if ( result.logged_in )
                        talCallAddTriiboId(); // add cellphone to triiboId_phone meta
                    else
                        talCallUserInfo();    // search user
                }
            },
            error: function( err ) {
                talAjaxError( err );
            }
        } );
    }

    /**
     * @since 1.1.0 New flow.
     */
    window.talCallUserInfo        = function ()
    {
        $.ajax( {
            url  : tal_ajaxurl,
            type : "POST",
            data : {
                action    : "tal_user_info",
                cellphone : tal_cellphone,
                ckt       : tal_ckt,
            },
            success: function( data ) {
                let result = JSON.parse( data );

                if ( !result.success )
                {
                    talEnableBtns();

                    switch ( result.error.code )
                    {
                        // has triibo account, but no mkt account linked
                        case 401:
                            let t1 = "Complete seu cadastro";
                            let p1 = "Notamos que sua conta Triibo ainda não está associada a uma conta Marketplace.";
                            talSetRegistertexts( t1, p1 );

                            $( "#tal-modal-form-load" ).addClass( "ta-d-none" );
                            $( tal_modal_valid ).addClass( "ta-d-none" );
                            $( tal_modal_regis ).removeClass( "ta-d-none" );
                            if ( tal_ckt ) $( tal_modal_regis ).addClass( "ta-bg-modal-white" );
                        break;

                        // not founded triibo number
                        case 404:
                            let t4 = "Complete seu cadastro";
                            let p4 = "Notamos que seu número ainda não está associado a uma conta Triibo.";
                            talSetRegistertexts( t4, p4 );

                            $( "#tal-modal-form-load" ).addClass( "ta-d-none" );
                            $( tal_modal_valid ).addClass( "ta-d-none" );
                            $( tal_modal_regis ).removeClass( "ta-d-none" );
                            if ( tal_ckt ) $( tal_modal_regis ).addClass( "ta-bg-modal-white" );
                        break;

                        // triibo error
                        case 500:
                        default:
                            $( "#tal-modal-form-load" ).addClass( "ta-d-none" );
                            $( tal_modal_valid ).addClass( "ta-d-none" );

                            var err = ( tal_ckt ) ? tal_error_auth_ckt : tal_error_auth;

                            $( err ).removeClass( "ta-d-none" ).html( tal_genericError1 );
                        break;
                    }
                }
                else
                {
                    talDisableBtns();
                    talHideModals();

                    $( "#tal-modal-form-load" ).addClass( "ta-bg-modal-white" );

                    if ( tal_ckt )
                        window.location.href = tal_redirect;
                    else
                        window.location.reload();
                }
            },
            error: function( err ) {
                talAjaxError( err );
            }
        });
    }

    window.talCallAddTriiboId     = function ()
    {
        $.ajax( {
            url  : tal_ajaxurl,
            type : "POST",
            data : {
                action    : "tal_add_triibo_id",
                cellphone : tal_cellphone
            },
            success: function( data ) {
                let result = JSON.parse( data );

                if ( !result.success )
                {
                    talEnableBtns();

                    $( "#tal-modal-form-load" ).addClass( "ta-d-none" );
                }
                else
                {
                    talDisableBtns();
                    talHideModals();

                    $( "#tal-modal-form-load" ).addClass( "ta-bg-modal-white" ).removeClass( "ta-d-none" );

                    window.location.href = tal_redirect;
                }
            },
            error: function( err ) {
                talAjaxError( err );
            }
        });
    }

    /**
     * @since 1.1.0 New flow.
     */
    window.talSubmitRegister      = function ()
    {
        talDisableBtns();

        tal_email = $( "#tal-form-register-email"    ).val();
        tal_pass  = $( "#tal-form-register-password" ).val();
        tal_terms = $( "#tal-form-register-terms"    ).prop( "checked" );

        if ( tal_email == "" || tal_pass == "" )
        {
            talEnableBtns();
            $( tal_error_regis ).removeClass( "ta-d-none" ).html( "E-mail/Senha não podem ser em branco. A senha deve ter no mínimo 8 caracteres." );

            return;
        }

        else if ( !tal_terms )
        {
            talEnableBtns();
            $( tal_error_regis ).removeClass( "ta-d-none" ).html( "Vocé precisa aceitar os termos de uso." );

            return;
        }

        else
        {
            $( tal_error_regis ).addClass( "ta-d-none" );
            $( "#tal-form-register-email"    ).removeClass( "ta-error-border" );
            $( "#tal-form-register-password" ).removeClass( "ta-error-border" );
            $( "#tal-modal-form-load"        ).removeClass( "ta-d-none"       );

            talCallFindUser(); // search user
        }
    }

    /**
     * @since 1.1.0 New flow.
     */
    window.talCallFindUser        = function ()
    {
        $.ajax( {
            url  : tal_ajaxurl,
            type : "POST",
            data : {
                action    : "tal_find_user",
                cellphone : tal_cellphone,
                email     : tal_email,
                password  : tal_pass,
                terms     : tal_terms,
            },
            success: function( data ) {
                let result = JSON.parse( data );

                talEnableBtns();

                if ( !result.success )
                {
                    $( "#tal-modal-form-load" ).addClass( "ta-d-none" );

                    switch ( result.error.code )
                    {
                        // email/pass not ok
                        case 401: // 500 e 501
                        default:
                            $( tal_error_regis ).removeClass( "ta-d-none" ).html( "E-mail e/ou senha incorreta" );
                            $( "#tal-form-register-email"    ).addClass( "ta-error-border" );
                            $( "#tal-form-register-password" ).addClass( "ta-error-border" );
                        break;
                    }
                }
                else
                {
                    $( "#tal-modal-form-load" ).addClass( "ta-bg-modal-white" ).removeClass( "ta-d-none" );

                    talHideModals();
                    talDisableBtns();

                    if ( tal_ckt )
                        window.location.href = tal_redirect;
                    else
                        window.location.reload();
                }
            },
            error: function( err ) {
                talAjaxError( err );
            }
        });
    }

    window.talDisableBtns         = function ()
    {
        $( tal_btn_auth     ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );
        $( tal_btn_auth_ckt ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );

        $( tal_btn_valid    ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );
        $( tal_btn_regis    ).prop( "disabled", "disabled" ).find( "div" ).removeClass( "ta-d-none" );
    }

    window.talEnableBtns          = function ()
    {
        $( tal_btn_auth     ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );
        $( tal_btn_auth_ckt ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );

        $( tal_btn_valid    ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );
        $( tal_btn_regis    ).prop( "disabled", null ).find( "div" ).addClass( "ta-d-none" );
    }

    window.talHideModals          = function ()
    {
        $( tal_modal_ckt     ).addClass( "ta-d-none" );
        $( tal_modal_valid   ).addClass( "ta-d-none" );
        $( tal_modal_regis   ).addClass( "ta-d-none" );
    }

    window.talHideErros           = function ()
    {
        $( tal_error_auth     ).addClass( "ta-d-none" ).html( "" );
        $( tal_error_auth_ckt ).addClass( "ta-d-none" ).html( "" );
        $( tal_error_valid    ).addClass( "ta-d-none" ).html( "" );
        $( tal_error_regis    ).addClass( "ta-d-none" ).html( "" );
    }

    window.talAjaxError           = function ( err )
    {
        console.log( err );
        talHideModals();
        talEnableBtns();
        $( "#tal-modal-form-load" ).addClass( "ta-d-none" );

        if ( tal_ckt )
            $( tal_error_auth_ckt ).removeClass( "ta-d-none" ).html( tal_genericError1 );
        else
            $( tal_error_auth     ).removeClass( "ta-d-none" ).html( tal_genericError1 );
    }

    window.talSetRegistertexts    = function ( t, p )
    {
        $( "#tal-register-title" ).html( t );
        $( "#tal-register-p1"    ).html( p );
        $( "#tal-register-p2"    ).html( "Informe o e-mail e a senha da sua conta Marketplace para completarmos seu cadastro." );
        $( "#tal-register-p3"    ).html( "Caso não tenha uma conta no Marketplace, criaremos uma para você." );
    }

} )( jQuery );
