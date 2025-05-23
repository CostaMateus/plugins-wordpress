<?php
/**
 * Triibo Assinaturas Rest class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: 'ABSPATH' ) || exit;

class Triibo_Assinaturas_Rest
{
	/**
	 * Initialize the plugin public actions.
     *
	 * @since 1.0.0
	 */
    public function __construct()
	{
		require_once dirname( path: __FILE__ ) . "/controllers/v1/class-triibo-assinatura-orders-controller.php";

        add_action( hook_name: "rest_api_init", callback: [ $this, "register_rest_routes" ] );
    }

    /**
     * Register REST API routes.
     *
	 * @since 1.0.0
     *
	 * @return void
     */
    public function register_rest_routes() : void
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
	 * @since 1.0.0
     *
     * @return array    List of Namespaces and Main controller classes.
     */
    private static function get_rest_namespaces() : mixed
    {
        return apply_filters(
            hook_name: "triibo_assinaturas_rest_namespaces",
            value    : [
                "triibo-assinatura/v1" => [
                    "orders" => "Triibo_Assinaturas_Orders_Controller",
                ]
            ]
        );
    }
}
