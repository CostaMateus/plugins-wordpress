<?php
/**
 * REST API.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Classes/Rest-Api
 * @since   1.0.0
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class WP_Pagare_Rest_Api
{
	/**
	 * Initialize the plugin public actions.
	 */
    public function __construct()
	{
        self::includes();

        add_action( "rest_api_init", array( $this, "register_rest_routes" ) );
    }

	/**
	 * Includes.
	 */
	private static function includes()
    {
		include_once dirname( __FILE__ ) . "/controllers/v1/class-wp-pagare-rest-cards-controller.php";
    }


    /**
     * Register REST API routes.
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
     * @return array List of Namespaces and Main controller classes.
     */
    private static function get_rest_namespaces()
    {
        return apply_filters(
            "rpg_rest_api_namespaces",
            [
                "pagare/v1" => [
                    "cards" => "WP_Pagare_Rest_Cards_Controller",
                ]
            ]
        );
    }

}

new WP_Pagare_Rest_Api();
