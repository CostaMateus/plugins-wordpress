<?php
/**
 * REST API Cards controller
 * Handles requests to the /cards endpoint.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Classes/Rest-Api/Controllers/v1
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * REST API Cards controller class.
 */
class WP_SupremPay_Rest_Cards_Controller extends WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $namespace = "suprempay/v1";

	/**
	 * Route base.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $rest_base = "cards";

	/**
	 * Stores the request.
	 *
	 * @var 	array
	 * @since 	1.0.0
	 */
	protected $request   = [];

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
	}

	/**
	 * Register the routes for users.
	 *
	 * @since 	1.0.0
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			"/$this->rest_base/brand",
			[
				[
					"methods"             => WP_REST_Server::CREATABLE, // POST
                    "callback"            => [ $this, "get_brand" ],
                    "permission_callback" => "__return_true",
                    "args"                => [
                        "bin"     => [
                            "required" => true,
                            "type"     => "string",
                        ],
                    ],
                ],
			]
		);
	}

	/**
	 * Validate the credit card brand
	 *
	 * @since 	1.0.0
	 * @param 	array $data
	 * @return 	array
	 */
	public function get_brand( $data )
	{
		$bin = $data[ "bin" ];
		$bin = str_replace( " ", "", $bin );

		$arr = [
            "Amex"          => "/^3[47][0-9]{13}$/",
            "Aura"          => "/^((?!504175))^((?!5067))(^50[0-9])/",
            "Banese Card"   => "/^636117/",
            "Cabal"         => "/(60420[1-9]|6042[1-9][0-9]|6043[0-9]{2}|604400)/",
            "Diners"        => "/(30[0-8][0-9]{3}|36[0-8][0-9]{3}|369[0-8][0-9]{2}|3699[0-8][0-9]|36999[0-9])/",
            "Discover"      => "/^6(?:011|5[0-9]{2})[0-9]{12}/",
            "Elo"           => "/^4011(78|79)|^43(1274|8935)|^45(1416|7393|763(1|2))|^50(4175|6699|67[0-6][0-9]|677[0-8]|9[0-8][0-9]{2}|99[0-8][0-9]|999[0-9])|^627780|^63(6297|6368|6369)|^65(0(0(3([1-3]|[5-9])|4([0-9])|5[0-1])|4(0[5-9]|[1-3][0-9]|8[5-9]|9[0-9])|5([0-2][0-9]|3[0-8]|4[1-9]|[5-8][0-9]|9[0-8])|7(0[0-9]|1[0-8]|2[0-7])|9(0[1-9]|[1-6][0-9]|7[0-8]))|16(5[2-9]|[6-7][0-9])|50(0[0-9]|1[0-9]|2[1-9]|[3-4][0-9]|5[0-8]))/",
            "Fort Brasil"   => "/^628167/",
            "GrandCard"     => "/^605032/",
            "Hipercard"     => "/^606282|^3841(?:[0|4|6]{1})0/",
            "JCB"           => "/^(?:2131|1800|35\d{3})\d{11}/",
            "Mastercard"    => "/^((5(([1-2]|[4-5])[0-9]{8}|0((1|6)([0-9]{7}))|3(0(4((0|[2-9])[0-9]{5})|([0-3]|[5-9])[0-9]{6})|[1-9][0-9]{7})))|((508116)\\d{4,10})|((502121)\\d{4,10})|((589916)\\d{4,10})|(2[0-9]{15})|(67[0-9]{14})|(506387)\\d{4,10})/",
            "Personal Card" => "/^636085/",
            "Sorocred"      => "/^627892|^636414/",
            "Valecard"      => "/^606444|^606458|^606482/",
            "Visa"          => "/^4[0-9]{15}$/",
        ];

		$data =
		[
			"error"     => true,
			"code"      => 404,
			"brandInfo" => [
				"supported" => false,
				"brand"     => null
			],
		];

        foreach ( $arr as $key => $regex )
		{
            if ( preg_match_all( $regex, $bin, $matches ) )
			{
				$data[ "error" ]     = false;
				$data[ "code"  ]     = 200;
				$data[ "brandInfo" ] = [
					"supported" => true,
					"brand"     => $key
				];
				break;
			}
		}

		return json_encode( $data );
	}

}
