<?php
/**
 * Triibo Payments Api class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: 'ABSPATH' ) || exit;

class Triibo_Payments_Api
{
	/**
	 * Gateway class.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Payment_Billet|Triibo_Payment_Card|Triibo_Payment_Pix
	 */
	protected $gateway;

	/**
	 * API class.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Node
	 */
	protected $node;

	/**
	 * Construct
	 *
	 * @since 1.0.0
	 *
	 * @param null|mixed 			$gateway
	 * @param null|Triibo_Api_Node 	$node
	 */
	public function __construct( mixed $gateway = null, ?Triibo_Api_Node $node = null )
	{
		$this->gateway = $gateway;
		$this->node    = $node;
	}

	/**
	 * Generate token for use in API calls
	 *
	 * @since 1.5.0 	Refactored
	 * @since 1.0.0
	 *
	 * @param string 	$uid
	 * @param int 		$user_id
	 *
	 * @return string
	 */
	public function generate_token( string $uid, int $user_id ) : string
	{
		if ( ! $this->node->status() )
		{
			$this->gateway->log(
				is_error: true,
				level   : "error",
				message : "generate_token : Node is not available.",
				context : [
					"uid"     => $uid,
					"user_id" => $user_id,
				]
			);

			return "";
		}

		$resp = $this->node->auth( token: "uid: {$uid}" );

		if ( ! $resp[ "success" ] )
		{
			$this->gateway->log(
				message : "generate_token : Failed to generate token.",
				context : [ "response" => $resp, ]
			);

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		$token    = $resp[ "data"  ][ "token" ];
		$validity = ( new DateTime() )->format( format: "Y-m-d H:i:s" );

		update_user_meta( user_id: $user_id, meta_key: "_triibo_auth_token",    meta_value: $token    );
		update_user_meta( user_id: $user_id, meta_key: "_triibo_auth_validity", meta_value: $validity );

		return $token;
	}

	/**
	 * Checks token validity, if expired, generates a new one
	 *
	 * @since 1.5.0 	Refactored
	 * @since 1.0.0
	 *
     * @param int 		$user_id
	 *
	 * @return string
	 */
	public function validate_token( int $user_id ) : ?string
	{
		$uid      = get_user_meta( user_id: $user_id, key: "_triibo_id", single: true );

        if ( !$uid )
			return null;

		$token    = get_user_meta( user_id: $user_id, key: "_triibo_auth_token", single: true );

		if ( !$token )
		{
			$this->gateway->log(
				message : "validate_token : user without token {$user_id}",
				context : [
					"uid"     => $uid,
					"user_id" => $user_id,
				]
			);

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		$validity = get_user_meta( user_id: $user_id, key: "_triibo_auth_validity", single: true );

		$dtval    = DateTime::createFromFormat( format: "Y-m-d H:i:s", datetime: $validity );
		$diff     = ( new DateTime() )->diff( targetObject: $dtval );

		if ( $diff->d > 0 || $diff->h > 10 )
		{
			$this->gateway->log(
				message : "validate_token : expired token {$user_id}",
				context : [
					"uid"     => $uid,
					"user_id" => $user_id,
				]
			);

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		return $token;
	}

	/**
	 * Checks if card has already been tokenized.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$token
	 * @param string 	$number
	 * @param string 	$brand
	 *
	 * @return array
	 */
	private function check_card_token( string $token, string $number, string $brand ) : array
	{
		$params   = [
			"payload" => [
				"gateway"  => $this->gateway->get_service(),
				"cardInfo" => [
					"number" => $number,
					"brand"  => $brand,
				]
			]
		];

		$response = $this->node->check_token( token: $token, params: $params );

		$this->gateway->log(
			message : "check_card_token",
			context : [
				"params"   => $params,
				"response" => $response,
			]
		);

		return $response;
	}

	/**
	 * Tokeniza um cartão.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$token
	 * @param array 	$data
	 *
	 * @return array
	 */
	private function create_card_token( string $token, array $data ) : array
	{
		$params   = [
			"payload" => [
				"gateway"  => $this->gateway->get_service(),
				"cardInfo" => [
					"holder"         => $data[ "holder"         ],
					"cardNumber"     => $data[ "cardNumber"     ],
					"expirationDate" => $data[ "expirationDate" ],
					"securityCode"   => $data[ "securityCode"   ],
					"brand"          => $data[ "brand"          ]
				],
			]
		];

        if ( $this->gateway->get_service() == "asaas" )
            $params[ "payload" ][ "cardAsaas" ] = $data[ "infoAsaas" ];

		$response = $this->node->create_token( token: $token, params: $params );

		$this->gateway->log(
			message : "create_card_token",
			context : [ "response" => $response, ]
		);

		return $response;
	}

	/**
	 * Deletes a tokenized card
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$token
	 * @param string 	$cardIndex
	 *
	 * @return array
	 */
	private function delete_card_token( string $token, string $cardIndex ) : array
	{
		$params = [
			"payload" => [
				"gateway"    => $this->gateway->get_service(),
				"tokenIndex" => $cardIndex
			]
		];

		$response = $this->node->delete_token( token: $token, params: $params );

		$this->gateway->log(
			message : "delete_card_token",
			context : [
				"params"   => $params,
				"response" => $response,
			]
		);

		return $response;
	}

	/**
	 * Returns the card's token, if it doesn't have it, tokenizes it.
	 *
	 * @since 1.5.0 	Refactored
	 * @since 1.0.0
	 *
	 * @param array 	$posted
	 *
	 * @return array
	 */
	public function status_card( array $posted ) : array
	{
		$number   = substr( string: $posted[ "card_number" ], offset: -4 );
		$response = $this->check_card_token( token: $posted[ "token" ], number: $number, brand: $posted[ "brand" ] );

		// CARD ALREADY TOKENIZED
		if ( $response[ "success" ] )
		{
			return [
				"success"    => true,
				"created"    => false,
				"tokenIndex" => $response[ "data" ][ "tokenInfo" ][ "index" ],
			];
		}

		// CARD NOT TOKENIZED
		$data     = [
			"holder"         => $posted[ "holder"      ],
			"cardNumber"     => $posted[ "card_number" ],
			"expirationDate" => $posted[ "month"       ] . "/" . $posted[ "year" ],
			"securityCode"   => $posted[ "cvv"         ],
			"brand"          => $posted[ "brand"       ],
		];

		if ( $this->gateway->get_service() == "asaas" )
			$data[ "infoAsaas" ] = $posted[ "infoAsaas" ];

		// CREATING CARD TOKEN
		$response = $this->create_card_token( token: $posted[ "token" ], data: $data );

		// CARD TOKEN CREATION SUCCESS
		if ( $response[ "success" ] )
		{
			return [
				"success"    => true,
				"created"    => true,
				"tokenIndex" => $response[ "data" ][ "tokenInfo" ][ "index" ],
			];
		}

		// CARD TOKEN CREATION FAILURE
		$error = "Falha ao consultar token.";

		$this->gateway->log(
			is_error: true,
			message : "create_card_token : Failed to create card token.",
			context : [
				"error"    => $error,
				"response" => $response,
			]
		);

		return [
			"success" => false,
			"error"   => "$error (cod EP2)"
		];
	}

	/**
	 * Process payment.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$method
	 * @param string 	$token
	 * @param array 	$data
	 *
	 * @return array
	 */
	public function do_payment( string $method, string $token, array $data ) : array
	{
		$descrip  = $this->treatDescription( text: $data[ "description" ] );

		$params   = [
			"payload" => [
				"gateway" => $this->gateway->get_service(),
				"order"   => [
					"orderId"     => $data[ "orderId"     ],
					"totalAmount" => $data[ "totalAmount" ], // em centavos
					"description" => $descrip,
					// "tokenIndex"  => $data[ "tokenIndex"  ],
					"name"        => $data[ "name"        ],
					"document"    => $data[ "document"    ],
					"cellPhone"   => $data[ "phone"       ],
				],
			]
		];

		if ( $method == "card" )
			$params[ "payload" ][ "order" ][ "tokenIndex" ] = $data[ "tokenIndex" ];

        if ( $this->gateway->get_service() == "asaas" )
            $params[ "payload" ][ "orderAsaas" ] = $data[ "infoAsaas" ];

		$response = $this->node->payment( token: $token, params: $params );

		$this->gateway->log(
			message : "do_payment",
			context : [
				"params"   => $params,
				"response" => $response,
			]
		);

		return $response;
	}

	/**
	 * Check payment status.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$token
	 * @param string 	$gateway
	 * @param string 	$payment_id
	 *
	 * @return array
	 */
	public function payment_status( string $token, string $gateway, string $payment_id ) : array
	{
		$params   = [
			"payload" => [
				"gateway"     => $gateway,
				"paymentInfo" => [
					"id" => $payment_id,
				],
			]
		];

		$response = $this->node->payment_status( token: $token, params: $params );

		$this->gateway->log(
			message : "payment_status",
			context : [
				"params"   => $params,
				"response" => $response,
			]
		);

		return $response;
	}

	/**
	 * Treat description.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$text
	 *
	 * @return string
	 */
	private function treatDescription( string $text ) : string
	{
		$divider = "-";
        $text    = preg_replace( pattern: "~[^\pL\d]+~u", replacement: $divider, subject: $text );
        $text    = iconv( from_encoding: "utf-8", to_encoding: "us-ascii//TRANSLIT", string: $text );
        $text    = preg_replace( pattern: "~[^-\w]+~", replacement: "", subject: $text );
        $text    = trim( string: $text, characters: $divider );
        $text    = preg_replace( pattern: "~-+~", replacement: $divider, subject: $text );
		$text    = str_replace( search: $divider, replace: " ", subject: $text );

		return $text;
	}
}
