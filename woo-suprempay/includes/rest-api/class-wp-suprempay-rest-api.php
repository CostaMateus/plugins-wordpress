<?php
/**
 * REST API.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Classes/Rest-Api
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class WP_SupremPay_Rest_Api
{
	/**
	 * Initialize the plugin public actions.
     *
     * @since   1.0.0
	 */
    public function __construct()
	{
        $v = WC_SUPREMPAY_REST_API_VER;

		include_once dirname( __FILE__ ) . "/controllers/{$v}/class-wp-suprempay-rest-cards-controller.php";
		include_once dirname( __FILE__ ) . "/controllers/{$v}/class-wp-suprempay-rest-webhook-controller.php";

        add_action( "rest_api_init", [ $this, "register_rest_routes" ] );
    }

    /**
     * Register REST API routes.
     *
     * @since   1.0.0
     * @return  void
     */
    public function register_rest_routes()
    {
        foreach ( self::get_rest_namespaces() as $controllers )
        {
            foreach ( $controllers as $controller_class )
            {
                $controller = new $controller_class();
                $controller->register_routes();
            }
        }
    }

    /**
     * Get API namespaces - new namespaces should be registered here.
     *
     * @since   1.0.0
     * @return  array List of Namespaces and Main controller classes.
     */
    private static function get_rest_namespaces()
    {
        return apply_filters(
            "rpg_rest_api_namespaces",
            [
                "suprempay/v1" => [
                    "cards"   => "WP_SupremPay_Rest_Cards_Controller",
                    "webhook" => "WP_SupremPay_Rest_Webhook_Controller",
                ]
            ]
        );
    }
}
