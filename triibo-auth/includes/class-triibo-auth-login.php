<?php
/**
 * The file that defines the plugin class Triibo-Auth-Login
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Login extends Triibo_Auth_Ajax
{
    /**
     * Construct
     *
     * @since 2.2.1     Add set_id()
     * @since 2.0.0     Add instance of Triibo_Api_Node
     * @since 1.0.0
     */
	public function __construct ()
    {
		$this->log  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate = new Triibo_Api_Gateway();
		$this->node = new Triibo_Api_Node();

        $this->set_id( id: TRIIBO_AUTH_LOGIN );

        add_filter( hook_name: "gettext",                         callback: [ $this, "change_text_form_login" ], priority: 20 );
        add_action( hook_name: "woocommerce_login_form_start",    callback: [ $this, "add_text_form_login"    ] );
        add_action( hook_name: "woocommerce_login_form_end",      callback: [ $this, "add_form_login"         ] );
        add_action( hook_name: "wp_ajax_tal_auth_sms",            callback: [ $this, "auth_sms"               ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_auth_sms",     callback: [ $this, "auth_sms"               ] );
        add_action( hook_name: "wp_ajax_tal_validate_sms",        callback: [ $this, "validate_sms"           ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_validate_sms", callback: [ $this, "validate_sms"           ] );
        add_action( hook_name: "wp_ajax_tal_user_info",           callback: [ $this, "user_info"              ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_user_info",    callback: [ $this, "user_info"              ] );
        add_action( hook_name: "wp_ajax_tal_find_user",           callback: [ $this, "find_user"              ] );
        add_action( hook_name: "wp_ajax_nopriv_tal_find_user",    callback: [ $this, "find_user"              ] );
    }

    /**
     * Returns the Triibo_Auth_Login status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since 1.0.0
     *
     * @return boolean
     */
    public static function status() : bool
	{
        $status = get_option( option: TRIIBO_AUTH_ID . "_login_status" );

		return $status == "on" ? true : false;
	}

    /**
     * Change the title of the login form card
     * From "Log In Your Account | Entrar em Sua Conta"
     * To "Choose how to access your account | Escolha como acessar sua conta"
     *
     * @since 1.0.0
     *
     * @param string    $translated_text
     *
     * @return string
     */
    public function change_text_form_login( string $translated_text ) : string
    {
        switch ( $translated_text )
        {
            case "Log In Your Account":
            case "Entrar em Sua Conta" :
                $translated_text = __( text: "Escolha como acessar sua conta", domain: "woocommerce" );
            break;
        }

        return $translated_text;
    }

    /**
     * Add a title before the email and password fields in the login form
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_text_form_login() : void
    {
        echo "<hr><p class='tal-title-optin' >Entrar com Login e Senha</p>";
    }

    /**
     * Adds to login form, cell field
     * Separates the login snippet by social network
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function add_form_login() : void
    {
        require_once( dirname( path: TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/login.php"  );
    }
}
