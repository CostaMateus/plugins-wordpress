<?php
/**
 * API class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Classes/Api
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class WC_SupremPay_API
{
	/**
	 * Endpoint homol.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	const API_HML    = "https://demoapi.suprem.cash"; // hardcoded for now

	/**
	 * Endpoint prod.
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	const API_PRD    = "https://api.suprem.cash"; // hardcoded for now

	/**
	 * Http codes.
	 *
	 * @var 	array
	 * @since 	1.0.0
	 */
	const HTTP_CODES = [
		// [Informational 1xx]
		100 => "Continue",
		101 => "Switching Protocols",

		// [Successful 2xx]
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",

		// [Redirection 3xx]
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		306 => "(Unused)",
		307 => "Temporary Redirect",

		// [Client Error 4xx]
		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",

		// [Server Error 5xx]
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported",
	];

	/**
	 * Gateway class.
	 *
	 * @var 	WC_SupremPay_Gateway
	 * @since 	1.0.0
	 */
	protected $gateway;

	/**
	 * Constructor.
	 *
	 * @since 	1.0.0
	 * @param 	WC_SupremPay_Gateway $gateway Gateway instance.
	 */
	public function __construct( $gateway = null )
    {
		$this->gateway = $gateway;
		$this->register_webhook();
	}

	/**
	 * Return api url.
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	private function get_api_url()
	{
		return ( $this->gateway->get_is_homol() === "yes" )
				? self::API_HML
				: self::API_PRD;
	}

	public function get_pagseguro_direct_payment_url()
	{
		$env = $this->gateway->get_is_homol() === "yes" ? ".sandbox." : ".";

		return "https://stc{$env}pagseguro.uol.com.br/pagseguro/api/v2/checkout/pagseguro.directpayment.js";
	}

	/**
	 * Exec the calls
	 *
	 * @since 	1.0.0
	 * @param 	string 	$api 			Api route to be call
	 * @param 	string 	$method 		Method of api route
	 * @param 	array 	$add_header 	Additional header to be sent in the header
	 * @param 	array 	$params 		Data to be sent in the body
	 * @return 	array
	 */
	private function exec( $api, $method, $add_header = [], $params = null )
    {
		$this->log( "INFO - running api : /{$api}" );

		$curl   = curl_init();

		if ( !$curl )
		{
			$result = [
				"code"    => "500.1",
				"success" => false,
				"message" => "Falha na comunicação com o meio de pagamento.",
				"error"   => "Couldn't initialize a cURL handle",
			];

			$this->log( json_encode( $result ), true );

			return $result;
		}

		$url    = $this->get_api_url() . "/{$api}";
		$tkn    = $this->gateway->get_token_integration();
		$header = [ "Content-Type: multipart/form-data", "token-integration: {$tkn}" ];

		if ( !empty( $add_header ) )
			foreach ( $add_header as $value )
				$header[] = $value;

		curl_setopt_array( $curl, [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => TRUE,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => TRUE,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_POSTFIELDS     => ( $params ) ? $params : null,
			CURLOPT_HTTPHEADER     => $header,
        ] );

        $resp = curl_exec( $curl );

		if ( empty( $resp ) )
		{
			// some kind of an error happened
			$err    = curl_error( $curl );
			curl_close($curl);

			$result = [
				"code"    => "500.2",
				"success" => false,
				"message" => "Falha na comunicação com o meio de pagamento.",
				"error"   => $err,
				"data"    => null,
			];

			$this->log( json_encode( $result ), true );

			return $result;
		}
		else
		{
			$info = curl_getinfo( $curl );

			curl_close( $curl );

			if ( empty( $info[ "http_code" ] ) )
			{
				$result = [
					"code"    => "500.2",
					"success" => false,
					"message" => "Falha na comunicação com o meio de pagamento.",
					"error"   => "No HTTP code was returned.",
					"data"    => json_decode( $resp, true ),
				];

				$this->log( json_encode( $result ), true );

				return $result;
			}
			else if ( $info[ "http_code" ] < 200 || $info[ "http_code" ] >= 300 )
			{
				$result = [
					"code"    => $info[ "http_code" ],
					"success" => false,
					"message" => "Falha na comunicação com o meio de pagamento.",
					"error"   => self::HTTP_CODES[ $info[ "http_code" ] ],
					"data"    => json_decode( $resp, true ),
				];

				$this->log( json_encode( $result ), true );

				return $result;
			}
			else
			{
				$result = [
					"code"    => $info[ "http_code" ],
					"success" => true,
					"message" => "ok",
					"error"   => null,
					"data"    => json_decode( $resp, true ),
				];

				return $result;
			}
		}
    }

	/**
	 * Get pagseguro session id
	 *
	 * @since 	1.0.0
	 * @return 	string|false
	 */
	public function get_session_id()
	{
		$params   = [ "token_api" => $this->gateway->get_token_user() ];

		$response = $this->exec( "pagseguro/session", "GET", [], $params );

		if ( !$response[ "success" ] || ( isset( $response[ "data" ][ "success" ] ) && !$response[ "data" ][ "success" ] ) )
			return false;

		return $response[ "data" ][ "info" ];
	}

	/**
	 * Process the payment
	 *
	 * @since 	1.0.0
	 * @param 	string 	$method 	Payment method (CC, Pix, Ticket)
	 * @param 	array 	$data 		Data of the payment
	 * @return 	array
	 */
	public function do_payment( $method, $data )
	{
		switch ( $method )
		{
			case "pix":
				return $this->do_payment_pix( $data );
			break;

			case "ticket":
				return $this->do_payment_ticket( $data );
			break;

			case "transfer":
				return $this->do_internal_transfer( $data );
			break;
		}
	}

	/**
	 * Process credit card payment
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	Data of the payment
	 * @return 	array
	 */
	private function do_payment_cc( $data )
	{
		/**
		 * $tokens = [
		 * 	"payments" => [
		 * 		"order_id_1" => "payment_id_1",
		 * 		"order_id_2" => "payment_id_2",
		 * 	],
		 * 	"cards"    => [
		 * 		"brand_1" => [
		 * 			[
		 * 				"digits"    => "1234",
		 * 				"cvc"       => "123",
		 * 				"card_code" => "token_gerado_anteriormente",
		 * 			], [
		 * 				"digits"    => "4321",
		 * 				"cvc"       => "321",
		 * 				"card_code" => "token_gerado_anteriormente",
		 * 			]
		 * 		]
		 * 	]
		 * ];
		 */

		$user         = $data[ "user" ];
		$tokens       = get_user_meta( $user->ID, "_suprempay_tokens", true );

		$empty        = empty( $tokens );
		$notSetBrand  = !$empty && isset( $tokens[ "cards" ] ) && !isset( $tokens[ "cards" ][ $data[ "brand" ] ] );

		$needNewToken = false;

		if ( $empty || $notSetBrand )
		{
			$needNewToken = true;
		}
		else
		{
			// retrieve token
			$lastDigits = substr( $data[ "card_number" ], -4 );
			$cards      = $tokens[ "cards" ][ $data[ "brand" ] ];
			$key        = array_search( $lastDigits, array_column( $cards, "digits" ) );

			if ( $key === false )
			{
				$needNewToken = true;
			}
			else
			{
				$card = $cards[ $key ];

				if ( $card[ "cvc" ] != $data[ "cvc" ] ) $needNewToken = true;
			}
		}

		// generate token
		if ( $needNewToken )
		{
			$response = $this->create_card_token( [
				"holder"      => $data[ "holder"      ],
				"card_number" => $data[ "card_number" ],
				"brand"       => $data[ "brand"       ],
				"expiration"  => $data[ "month" ] . "/" . $data[ "year" ]
			] );

			if ( $response[ "success" ] )
			{
				$card = [
					"digits"    => substr( $data[ "card_number" ], -4 ),
					"cvc"       => $data[ "cvc" ],
					"card_code" => $response[ "data" ][ "cardCode" ],
				];

				if ( empty( $tokens ) )
				{
					$tokens = [ "cards" => [ $data[ "brand" ] => [ $card ] ] ];
				}
				else
				{
					$tokens[ "cards" ][ $data[ "brand" ] ][] = $card;
				}

				update_user_meta( $user->ID, "_suprempay_tokens", $tokens );
			}
			else
			{
				return $response;
			}
		}

		$product  = ( $data[ "installment" ] == 1 && $this->gateway->get_cc_type() != "avista" ) ? "avista" : $this->gateway->get_cc_type();
		$header   = [ "ProductType: {$product}" ];

		$prefix   = $this->gateway->get_invoice_prefix();
		$orderId  = "#{$prefix}{$data[ "order_id" ]}";

		$value    = ( float ) $data[ "total" ];

		$params   = [
			"capture"                   => true,
			"orderNumber"               => $orderId,
			"installment"               => ( int ) $data[ "installment" ],
			"value"                     => $value, // reais
			"cardCode"                  => $card[ "card_code"  ],
			"SecurityCode"              => $card[ "cvc"        ],
			"customerFirstName"         => $data[ "first_name" ],
			"customerLastName"          => $data[ "last_name"  ],
			"customerDocument"          => $data[ "cpf"        ],
			"customerEmail"             => $data[ "email"      ],
			"customerPhoneNumber"       => $data[ "phone"      ],
			"customerAddress"           => $data[ "street"     ],
			"customerAddressComplement" => $data[ "complement" ],
			"customerState"             => $data[ "uf"         ],
			"customerZipCode"           => $data[ "zipcode"    ],
		];

		return $this->exec( "card", "POST", $header, $params );
	}

	/**
	 * Process pix payment
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	Data of the payment
	 * @return 	array
	 */
	private function do_payment_pix( $data )
	{
		$pixDate  = ( int ) $this->gateway->get_pix_due_date();

		$prefix   = $this->gateway->get_invoice_prefix();
		$order    = $data[ "order" ];
		$descrip  = "#{$prefix}{$order->get_id()} | {$data["name"]}";

		$amount   = number_format( $data[ "amount" ], 2, ",", "" );
		$email    = $data[ "email"    ];
		$name     = $data[ "name"     ];
		$document = $data[ "document" ];

		$params   = [
			"token_api"   => $this->gateway->get_token_user(),

			"description" => substr( $descrip, 0, 150 ),
			"name"        => $name,
			"document"    => $document,
			"expiration"  => $pixDate,
			"amount"      => $amount,
			"email"       => $email,
		];

		return $this->exec( "pix/collection/wallet", "POST", [], $params );
	}

	/**
	 * Process ticket payment
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	Data of the payment
	 * @return 	array
	 */
	private function do_payment_ticket( $data )
	{
		$tic_date = $this->gateway->get_ticket_due_date();
		$due_date = date( "Y-m-d" ) . " +{$tic_date} days";
		$due_date = date( "d/m/Y", strtotime( $due_date ) );

		$prefix   = $this->gateway->get_invoice_prefix();
		$order    = $data[ "order" ];
		$descrip  = "#{$prefix}{$order->get_id()} | {$data["name"]}";

		$amount   = number_format( $data[ "amount" ], 2, ",", "" );

		$params   = [
			"token_api"      => $this->gateway->get_token_user(),

			"reference"      => $order->get_id(),
			"guid_reference" => $descrip,
			"name"           => $data[ "name"         ],
			"email"          => $data[ "email"        ],
			"document"       => $data[ "document"     ],
			"area_code"      => $data[ "area_code"    ],
			"phone_number"   => $data[ "phone_number" ],
			"zip_code"       => $data[ "zip_code"     ],
			"address"        => $data[ "address"      ],
			"district"       => $data[ "district"     ],
			"city"           => $data[ "city"         ],
			"state"          => $data[ "state"        ],
			"due_date"       => $due_date,
			"amount"         => $amount,
		];

		return $this->exec( "v2/billet/set", "POST", [], $params );
	}

	/**
	 * Process transfer payment
	 *
	 * @since 	1.0.0
	 * @param 	array 	$data 	Data of the payment
	 * @return 	array
	 */
	private function do_internal_transfer( $data )
	{
		$check    = $this->check_wallet( $data[ "email" ] );

		if ( !$check[ "success" ] || ( isset( $check[ "data" ] ) && !$check[ "data" ][ "success" ] ) )
			return $check;

		$order    = $data[ "order" ];
		$email    = $data[ "email" ];
		$code     = $data[ "code"  ];

		$prefix   = $this->gateway->get_invoice_prefix();
		$descrip  = "#{$prefix}{$order->get_id()} | {$data["name"]}";

		$amount   = number_format( $data[ "amount" ], 2, ",", "" );

		$params   = [
			"token_api"            => $this->gateway->get_token_user(),

			"email"                => $email,
			"description"          => $descrip,
			"google_authenticator" => $code,
			"value"                => $amount,
		];

		return $this->exec( "finance/internal/transfer", "POST", [], $params );
	}

	/**
	 * Check if the $email is a wallet
	 *
	 * @since 	1.0.0
	 * @param 	string 	$email
	 * @return 	array
	 */
	private function check_wallet( $email )
	{
		$params = [
			"token_api" => $this->gateway->get_token_user(),
			"wallet"    => $email,
		];

		return $this->exec( "user/get/info/wallet", "POST", [], $params );
	}

	/**
	 * Register webhooks
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function register_webhook()
	{
		$v         = WC_SUPREMPAY_REST_API_VER;
		$options   = [
			"bil"  => "supremapay_bil_webhook_registered",
			"pix"  => "supremapay_pix_webhook_registered",

			// TODO habilitar WH /card
			// "card" => "supremapay_card_webhook_registered",
		];

		$endpoints = [
			"bil" => [
				"token_api" => $this->gateway->get_token_user(),
				"service"   => "collection-billet",
				"url"       => get_rest_url( null, "suprempay/{$v}/webhook/billet" ),
			],
			"pix" => [
				"token_api" => $this->gateway->get_token_user(),
				"service"   => "collection-pix",
				"url"       => get_rest_url( null, "suprempay/{$v}/webhook/pix"    ),
			],
			"card" => [
				"token_api" => $this->gateway->get_token_user(),
				"service"   => "payment-card",
				"url"       => get_rest_url( null, "suprempay/{$v}/webhook/card"   ),
			],
		];

		foreach ( $options as $key => $option )
		{
			if ( !get_option( $option ) )
			{
				$response = $this->exec( "webhook/set", "POST", [], $endpoints[ $key ] );

				if ( $response[ "success" ] && isset( $response[ "data" ] ) && $response[ "data" ][ "success" ] )
				{
					$this->log( "INFO - SupremPay webhook {$endpoints[ $key ][ "service" ]}: successfully registered." );
					add_option( $option, 1 );
				}
				else
				{
					$this->log( "INFO - SupremPay webhook {$endpoints[ $key ][ "service" ]}: failed to register, will attempt again soon!" );
				}
			}
		}
	}

	/**
	 * Log
	 *
	 * @since 	1.0.0
	 * @param 	string 		$message
	 * @param 	boolean 	$is_error
	 * @return 	void
	 */
	public function log( $message, $is_error = false )
	{
		$log = $this->gateway->get_log();

		if ( $this->gateway->get_debug() === "yes" )
		{
			$log->add( $this->gateway->id, $message );
		}
		else if ( $is_error )
		{
			$log->add( $this->gateway->id, $message );
		}
	}
}
