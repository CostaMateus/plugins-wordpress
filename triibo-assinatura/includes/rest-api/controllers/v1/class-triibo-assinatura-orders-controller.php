<?php
/**
 * Triibo Assinaturas Orders Controller
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Assinaturas_Orders_Controller extends \WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = "triibo-assinatura/v1";

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = "orders";

	/**
	 * Stores the request.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected array $request = [];

	/**
	 * Construct
	 *
	 * @since 1.0.0
	 */
	public function __construct() {}

	/**
	 * Register the routes for users.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() : void
	{
		register_rest_route(
			route_namespace: $this->namespace,
			route          : "/$this->rest_base/update-payment",
			args           : [
				[
					"methods"             => WP_REST_Server::READABLE, // GET
					"callback"            => [ $this, "update_subscription" ],
					"permission_callback" => "__return_true",
					"args"                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 *
	 *
	 * @since 3.0.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_subscription( WP_REST_Request $request ) : WP_REST_Response
	{
		$settings = get_option( option: "woocommerce_" . WC_Triibo_Assinaturas::DOMAIN . "_settings" );
		$key_prd  = $settings[ "key_prd" ];
		$key_hml  = $settings[ "key_hml" ];

		$key      = $request->get_header( key: "x-api-key" );

		if ( $key !== $key_prd || $key !== $key_hml )
			return new WP_REST_Response( data: [ "error" => "Unauthorized" ], status: 401 );

		$order_id = $request->get_param( key: "orderId"  );
		$status   = $request->get_param( key: "status"   );
		$approved = $request->get_param( key: "approved" );

		$order    = wc_get_order( the_order: $order_id );

		if ( $approved )
		{
			$data    = [
				"id"     => null,
				"status" => $status,
			];

			$gateway = Triibo_Assinaturas_Gateway::get_instance();
			$gateway->finalize_order( order: $order, data: $data );
		}
		else
		{
			$order->update_status( new_status: "pending" );
		}

		return new WP_REST_Response();
	}
}
