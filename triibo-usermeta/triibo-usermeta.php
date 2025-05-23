<?php
/**
 * Plugin Name: 			#Triibo_Usermeta
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Adiciona todo o meta_data do usuário na resposta da API de users do WP. Usado pelo Node no processo do Checkout.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				1.2.0
 * Text Domain: 			triibo-usermeta
 * Requires Plugins:        woocommerce
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Usermeta
 *
 * @version 1.2.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * @since 1.2.0
 */
if ( ! in_array( needle: "woocommerce/woocommerce.php", haystack: apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) ) ) )
    return;

add_action( hook_name: "plugins_loaded", callback: [ "Triibo_Usermeta", "get_instance" ], priority: 222 );

class Triibo_Usermeta
{
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
     *
	 * @var string
	 */
	const VERSION = "1.2.0";

	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
     *
	 * @var string
	 */
	const DOMAIN = "triibo-usermeta";

    /**
     * Allowed users to access the user metadata via the API.
     *
     * @since 1.0.0
     *
     * @var array
     */
    const ALLOWED_USERS = [
        "triibo.node@triibo.com.br",
		"fast.shop@triibo.com.br",
    ];

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
     *
	 * @var object
	 */
	protected static ?object $instance = null;

	/**
	 * Construct.
	 *
	 * @since 1.0.0
	 */
    public function __construct()
    {
        // Hook into the REST API initialization action.
        add_action( hook_name: "rest_api_init", callback: [ $this, "add_user_meta_to_rest_response" ] );

        // Filter to add user meta to the WooCommerce REST API response.
        add_filter( hook_name: "woocommerce_rest_prepare_customer", callback: [ $this, "add_user_meta_to_wc_response" ], priority: 10, accepted_args: 3 );
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
     *
	 * @return self     A single instance of this class
	 */
	public static function get_instance() : self
    {
		if ( null === self::$instance )
            self::$instance = new self;

		return self::$instance;
	}

    /**
     * Checks if the current user is allowed to access the user metadata.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function check_user() : bool
    {
        $current_user = wp_get_current_user();

        if ( $current_user->exists() && in_array( needle: $current_user->user_email, haystack: self::ALLOWED_USERS ) )
            return true;

        return false;
    }

    /**
     * Registers an additional field in the REST API response for the user type.
     *
	 * @since 1.0.0
     *
     * @return void
     */
    public function add_user_meta_to_rest_response() : void
    {
        register_rest_field(
            object_type: "user",
            attribute  : "user_meta",
            args       : [
                "get_callback" => [ $this, "get_user_meta_for_api" ],
                "schema"       => null,
            ]
        );
    }

    /**
     * Retrieves user metadata for the API response.
     *
     * @since 1.1.0     Using the check_user() method to verify if the user is allowed to access the metadata
	 * @since 1.0.0
     *
     * @param array     $object     The user object containing the data
     *
     * @return array    The user's metadata
     */
    public function get_user_meta_for_api( array $object ) : array
    {
        if ( $this->check_user() )
            return get_user_meta( user_id: $object[ "id" ] );

        return [];
    }

    /**
     * Adds user metadata to the WooCommerce REST API response.
     *
     * @since 1.1.0
     *
     * @param WP_REST_Response  $response
     * @param WP_User           $customer
     * @param WP_REST_Request   $request
     *
     * @return WP_REST_Response
     */
    public function add_user_meta_to_wc_response( WP_REST_Response $response, WP_User $customer, WP_REST_Request $request ) : WP_REST_Response
    {
        if ( ! $this->check_user() )
            return $response;

        $data                = $response->get_data();

        $data[ "meta_data" ] = array_map( callback: function( $a ) : mixed {
            return $a[ 0 ];
        }, array: get_user_meta( user_id: $customer->ID ) );

        $response->set_data( data: $data );

        return $response;
    }
}
