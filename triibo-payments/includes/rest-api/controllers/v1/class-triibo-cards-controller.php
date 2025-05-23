<?php
/**
 * Triibo Cards Controller class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Cards_Controller extends WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = "triibo-payments/v1";

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = "cards";

	/**
	 * Stores the request.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $request = [];

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
			route: "/$this->rest_base/brand",
			args: [
				[
					"methods"             => WP_REST_Server::READABLE, // GET
                    "callback"            => [ $this, "get_brand" ],
                    "permission_callback" => "__return_true",
					"args"                => $this->get_collection_params(),
                ],
			]
		);
	}

	/**
	 * Validate the credit card brand.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request 	$request
	 *
	 * @return string
	 */
	public function get_brand( WP_REST_Request $request ) : string
	{
		$tpc     = Triibo_Payment_Card::get_instance();
		$service = $tpc->get_service();

		$bin     = $request->get_param( key: "bin"     );
		$user_id = $request->get_param( key: "user_id" );

		$default = json_encode( value: [
			"success"   => false,
			"brandInfo" => [
				"supported" => false,
				"brand"     => null
			],
		] );

		if ( ! in_array( needle: $service, haystack: [ "asaas", "cielo" ] ) )
			return $default;

		return match ( $service )
		{
			"asaas"  => $this->processBrandAsaas( tpc: $tpc, bin: $bin ),
			"cielo"  => $this->processBrandCielo( tpc: $tpc, user_id: $user_id, bin: $bin ),
			default  => $default
		};
	}

	/**
	 * Process the brand validation for Asaas.
	 *
	 * @since 1.5.0
	 *
	 * @param Triibo_Payment_Card 	$tpc
	 * @param string 				$bin
	 *
	 * @return string
	 */
	private function processBrandAsaas( Triibo_Payment_Card $tpc, string $bin ) : string
	{
		$bin = str_replace( search: "-", replace: "", subject: $bin );

		$arr = [
			"Visa"       => "/^4\d{12}(\d{3})?$/",
			"Mastercard" => "/^(5[1-5]\d{14}|2(22[1-9]\d{12}|2[3-9]\d{13}|[3-6]\d{14}|7[0-1]\d{13}|720\d{12})|6(0[0,3]\d{13}|3[7,9]\d{13}|7\d\d{13})|975230\d{10})$/",
			"Amex"       => "/^3[47]\d{13}$/",
			"Diners"     => "/^3(0[0-5]|[68]\d)\d{11}$/",
			"Elo"        => "/^((((438935)|(431274)|(457393)|(504175)|(451416)|(627780)|(636297)|(636368))\d{0,10})|((506[6,7])|(4576)|(4011)|(509\d)|(650\d)|(6516[5-7])|(65500[0-2,4-9])|(6550[1-2,4-9])|(65503[1-9]))\d{0,12})$/",
			"Discover"   => "/^6(?:011|5[0-9]{2})\d{12}$/",
			"Hipercard"  => "/^(606282\d{10}(\d{3})?)|(3841\d{15})$/",
		];

		foreach ( $arr as $key => $regex )
		{
			if ( preg_match_all( pattern: $regex, subject: $bin, matches: $matches ) )
			{
				return json_encode( value: [
					"success"   => true,
					"brandInfo" => [
						"supported" => true,
						"brand"     => $key
					],
				] );
			}
		}

		$tpc->log(
			is_error: true,
			level   : "error",
			message : "Brand validation error",
			context : [ "bin" => $bin ]
		);

		return json_encode( value: [
			"success"   => true,
			"brandInfo" => [
				"supported" => true,
				"brand"     => $key
			],
		] );
	}

	/**
	 * Process the brand validation for Cielo.
	 *
	 * @since 1.5.0
	 *
	 * @param Triibo_Payment_Card 	$tpc
	 * @param int 					$user_id
	 * @param string 				$bin
	 *
	 * @return string
	 */
	private function processBrandCielo( Triibo_Payment_Card $tpc, int $user_id, string $bin ) : string
	{
		$api      = $tpc->get_api();
		$node     = $tpc->get_node();
		$service  = $tpc->get_service();
		$token    = $api->validate_token( user_id: $user_id );

		$params   = [
			"payload" => [
				"gateway"    => $service,
				"cardNumber" => $bin,
			]
		];

		$response = $node->brand( token: $token, params: $params );

		if ( ! $response[ "success" ] )
		{
			$tpc->log(
				is_error: true,
				level   : "error",
				message : "Brand validation",
				context : [
					"user_id"  => $user_id,
					"params"   => $params,
					"response" => $response
				]
			);
		}

		return json_encode( value: $response[ "data" ] );
	}
}
