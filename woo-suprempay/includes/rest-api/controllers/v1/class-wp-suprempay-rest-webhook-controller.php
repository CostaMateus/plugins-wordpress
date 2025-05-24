<?php
/**
 * REST API Webhook controller
 * Handles requests to the /webhook endpoint.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Classes/Rest-Api/Controllers/v1
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * REST API Webhook controller class.
 */
class WP_SupremPay_Rest_Webhook_Controller extends WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $namespace  = "suprempay/v1";

	/**
	 * Route base.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $rest_base  = "webhook";

	/**
	 * Stores the request.
	 *
	 * @var 	array
	 * @since 	1.0.0
	 */
	protected $request    = [];

	/**
	 * SumprePay gateway
	 *
	 * @var 	WC_Payment_Gateway
	 * @since 	1.0.0
	 */
	protected $suprempay  = null;

	/**
	 * Status of billet webhook.
	 *
	 * @var 	array
	 * @since 	1.0.0
	 */
	protected $status_tkt = [
		1 => "recebido", // pago
		2 => "gerado",
		3 => "pendente",
		4 => "vencido",
		5 => "parcial",  // pago parcialmente
		6 => "cancelado",
		7 => "recebido por fora",
	];

	/**
	 * Status of pix webhook.
	 *
	 * @var 	array
	 * @since 	1.0.0
	 */
	protected $status_pix = [
		0 => "recebido",
		1 => "pendente",
		2 => "parcial",
		3 => "cancelado",
		4 => "estornado"
	];

	/**
	 * Logger
	 *
	 * @var 	WC_Logger
	 * @since 	1.0.0
	 */
	protected $log;

	/**
	 * Construct
	 *
	 * @since 	1.0.0
	 */
	public function __construct()
	{
		$this->log = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$gateways  = new WC_Payment_Gateways();
		$gateways  = $gateways->get_available_payment_gateways();

		foreach( $gateways as $id => $gateway )
			if ( $id == WC_SUPREMPAY_ID )
				$this->suprempay = $gateway;
	}

	/**
	 * Register the routes for users.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			"/$this->rest_base/billet",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE, // POST
                    "callback"            => [ $this, "ticket" ],
                    "permission_callback" => "__return_true",
                ],
			]
		);

		register_rest_route(
			$this->namespace,
			"/$this->rest_base/pix",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE, // POST
                    "callback"            => [ $this, "pix" ],
                    "permission_callback" => "__return_true",
                ],
			]
		);

		register_rest_route(
			$this->namespace,
			"/$this->rest_base/card",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE, // POST
                    "callback"            => [ $this, "card" ],
                    "permission_callback" => "__return_true",
                ],
			]
		);
	}

	/**
	 * Webhook billet
	 *
	 * @since 	1.0.0
	 * @param 	WP_REST_Request $data
	 * @return 	WP_REST_Response
	 */
	public function ticket( WP_REST_Request $request )
	{
		$params   = $request->get_params();
		$order_id = null;

		if ( isset( $params[ "reference" ] ) && isset( $params[ "code_status" ] ) && $this->suprempay )
		{
			$order_id = $params[ "reference" ];

			if ( $order_id )
			{
				$status = $params[ "code_status" ];
				$order  = wc_get_order( $order_id );

				$order->add_order_note( "SupremPay: Pagamento {$this->status_tkt[ $status ]}" );

				if ( $status == "1" ) $order->update_status( "processing" );

				if ( in_array( $status, [ "4", "6" ] ) ) $order->update_status( "cancelled" );
			}
		}

		$response = new WP_REST_Response( [
			"success" => true,
			"message" => "ok",
		], 200 );

		return $response;
	}

	/**
	 * Webhook pix
	 *
	 * @since 	1.0.0
	 * @param 	WP_REST_Request $data
	 * @return 	WP_REST_Response
	 */
	public function pix( WP_REST_Request $request )
	{
		$params   = $request->get_params();
		$order_id = null;

		if ( isset( $params[ "description" ] ) && $this->suprempay )
		{
			$prefix   = $this->suprempay->get_invoice_prefix();
			$order_id = reset( explode( " | ", $params[ "description" ] ) );
			$order_id = end( explode( "#{$prefix}", $order_id ) );
		}

		if ( isset( $params[ "status" ] ) && $order_id )
		{
			$status = $params[ "status" ];
			$order  = wc_get_order( $order_id );

			$order->add_order_note( "SupremPay: Pagamento {$this->status_pix[ $status ]}" );

			if ( $status == "0" ) $order->update_status( "processing" );

			if ( in_array( $status, [ "3", "4" ] ) ) $order->update_status( "cancelled" );
		}

		$response = new WP_REST_Response( [
			"success" => true,
			"message" => "ok",
		], 200 );

		return $response;
	}

	/**
	 * Webhook creditcard
	 *
	 * @since 	1.0.0
	 * @param 	WP_REST_Request $data
	 * @return 	WP_REST_Response
	 */
	public function card( WP_REST_Request $request )
	{
		/**
		 * TODO tratar os dados
		 */
		$param    = $request->get_params();

		$data     = [
			"success" => true,
			"message" => "ok",
		];

		$response = new WP_REST_Response( $data, 200 );

		return $response;
	}

}
