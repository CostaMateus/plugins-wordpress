<?php
/**
 * The file that defines the plugin class Triibo-Auth-Checkout
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Auth_Checkout extends Triibo_Auth_Ajax
{
    /**
     * Redirect URL
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $_ckt_url      = null;

    /**
     * Option 1 of disclaimer
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $_disclaimer_1 = "Antes de avançar para o Checkout, informe seu celular para acessar sua conta.<br><br>Caso não tenha uma conta, você será guiado pelo processo de criação.";

    /**
     * Option 2 of disclaimer
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected static $_disclaimer_2 = "Antes de avançar para o Checkout, informe seu celular para receber o código SMS e validar sua conta.";

    /**
     * Construct
     *
     * @since 2.2.1     Add set_id()
     * @since 2.0.0     Add instance of Triibo_Api_Node
     * @since 1.0.0
     */
	public function __construct ()
    {
		$this->log      = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate     = new Triibo_Api_Gateway();
		$this->node     = new Triibo_Api_Node();

        $this->set_id( id: TRIIBO_AUTH_CHECKOUT );

        self::$_ckt_url = md5( string: "redirect_to_checkout" );

        add_action( hook_name: "template_redirect",                      callback: [ $this, "redirect_to_account_page" ], priority: 100 );
        add_filter( hook_name: "woocommerce_registration_redirect",      callback: [ $this, "redirect_to_checkout"     ], priority: 100 );
        add_filter( hook_name: "woocommerce_login_redirect",             callback: [ $this, "redirect_to_checkout"     ], priority: 100 );

        add_action( hook_name: "wp_ajax_tal_auth_sms",                   callback: [ $this, "auth_sms"            ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_auth_sms",            callback: [ $this, "auth_sms"            ] );
        add_action( hook_name: "wp_ajax_tal_validate_sms",               callback: [ $this, "validate_sms"        ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_validate_sms",        callback: [ $this, "validate_sms"        ] );
        add_action( hook_name: "wp_ajax_tal_validate_code_email",        callback: [ $this, "validate_code_email" ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_validate_code_email", callback: [ $this, "validate_code_email" ] );
        add_action( hook_name: "wp_ajax_tal_user_info",                  callback: [ $this, "user_info"           ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_user_info",           callback: [ $this, "user_info"           ] );
        add_action( hook_name: "wp_ajax_tal_resend_email_code",          callback: [ $this, "resend_email_code"   ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_resend_email_code",   callback: [ $this, "resend_email_code"   ] );
        add_action( hook_name: "wp_ajax_tal_find_user",                  callback: [ $this, "find_user"           ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_find_user",           callback: [ $this, "find_user"           ] );
        add_action( hook_name: "wp_ajax_tal_add_triibo_id",              callback: [ $this, "add_triibo_id"       ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_add_triibo_id",       callback: [ $this, "add_triibo_id"       ] );
    }

    /**
     * Returns the Triibo_Auth_Checkout status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since 1.0.0
     *
     * @return boolean
     */
    public static function status() : bool
	{
        $status = get_option( option: TRIIBO_AUTH_ID . "_checkout_status" );

		return $status == "on" ? true : false;
	}

    /**
     * Get disclaimer of logged user or not
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_disclaimer() : string
    {
        return ( !is_user_logged_in() ) ? self::$_disclaimer_1 : self::$_disclaimer_2;
    }

    /**
     * Checkout URL
     *
     * @return string
     */
    public static function get_checkout_url() : string
    {
        global $woocommerce;

        return $woocommerce->cart ? wc_get_checkout_url() : "";
    }

    /**
     * Check if URL has ckt param
     *
     * @since 1.0.0
     *
     * @return boolean
     */
    public static function has_query_param() : bool
    {
		return isset( $_GET[ "ckt" ] ) && $_GET[ "ckt" ] === self::$_ckt_url;
	}

    /**
     * Login page URL
     *
     * @since 1.0.0
     *
     * @return string
     */
    private function get_login_page_url() : string
    {
		return apply_filters(
            hook_name: "wc_login_before_checkout_login_page_url",
			value    : get_permalink( post: get_option( option: "woocommerce_myaccount_page_id" ) )
		);
	}

    /**
     * Redirect to account page if:
     * - redirect is to checkout
     * - user is logged in and dont have triibo_id
     * - user isnt logged in
     *
     * @since 1.0.0
     *
     * @return void
     */
	public function redirect_to_account_page() : void
    {
        if ( is_checkout() )
        {
            if ( is_user_logged_in() )
            {
                $user_id      = get_current_user_id();
                $triibo_phone = get_user_meta( user_id: $user_id, key: "_triibo_phone", single: true );
                $triibo_phone = $triibo_phone ?: get_user_meta( user_id: $user_id, key: "triiboId_phone", single: true );

                if ( empty( $triibo_phone ) )
                {
                    $this->safe_redirect();
                }
            }
            else
            {
                $this->safe_redirect();
            }
        }
	}

    /**
     * Redirect to checkout page
     *
     * @since 1.0.0
     *
     * @param string    $redirect
     *
     * @return string
     */
	public function redirect_to_checkout( string $redirect ) : string
    {
		return ( self::has_query_param() ) ? wc_get_checkout_url() : $redirect;
	}

    /**
     * Make redirection
     *
     * @since 1.0.0
     *
     * @return never
     */
    private function safe_redirect() : never
    {
        wp_safe_redirect( location: add_query_arg( "ckt", self::$_ckt_url, $this->get_login_page_url() ) );
        die();
    }
}
