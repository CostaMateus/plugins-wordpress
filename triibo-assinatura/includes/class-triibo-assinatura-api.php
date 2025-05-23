<?php
/**
 * Triibo Assinaturas Api class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 3.4.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Assinaturas_Api extends \WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = WC_Triibo_Assinaturas::DOMAIN . "/v1";

	/**
	 * Stores the request.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected array $request = [];

	/**
	 * Gateway class.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Assinaturas_Gateway
	 */
	protected Triibo_Assinaturas_Gateway $gateway;

	/**
	 * API class.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Node
	 */
	protected Triibo_Api_Node $node;

	/**
	 * Construct
	 *
	 * @since 1.0.0
	 *
	 * @param null|Triibo_Assinaturas_Gateway 	$gateway
	 * @param null|Triibo_Api_Node 				$node
	 */
	public function __construct( ?Triibo_Assinaturas_Gateway $gateway = null, ?Triibo_Api_Node $node = null )
	{
		$this->node    = $node;
		$this->gateway = $gateway;
	}

	/**
	 * Registra rotas api rest
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() : void
	{
		register_rest_route(
			route_namespace: $this->namespace,
			route          : "/payment/(?P<order_id>[0-9]+)/update/(?P<approved>[a-zA-Z]+)",
			args           : [
				"methods"             => WP_REST_Server::READABLE, // GET
				"callback"            => [ $this, "update_subscription" ],
				"permission_callback" => "__return_true",
				"args"                => [
					"order_id" => [
						"required" => true,
						"type"     => "string",
					],
					"approved" => [
						"required" => true,
						"type"     => "string",
					],
				],
			]
		);
	}

	/**
	 * Consulta gateway para gerar token para uso nas chamadas das APIs
	 *
	 * @since 3.0.2 	Return the token
	 * @since 1.0.0
	 *
     * @param string 	$uid
     * @param int 		$user_id
	 *
	 * @return string
	 */
	public function generate_token( string $uid, int $user_id ) : mixed
	{
		if ( $this->node->status() )
		{
			$resp = $this->node->auth( token: "uid: {$uid}" );

			if ( $resp[ "success" ] )
			{
				$token    = $resp[ "data"  ][ "token" ];
				$validity = ( new DateTime() )->format( format: "Y-m-d H:i:s" );

				update_user_meta( user_id: $user_id, meta_key: "_triibo_auth_token",    meta_value: $token    );
				update_user_meta( user_id: $user_id, meta_key: "_triibo_auth_validity", meta_value: $validity );

				return $token;
			}
			else
			{
				$message = print_r(
					return: true,
					value : [
						"code"    => $resp[ "code"    ],
						"message" => $resp[ "message" ],
						"error"   => $resp[ "error"   ]
					],
				);

				$this->log(
					level  : "info",
					message: "generate_token : {$message}"
				);

				return $this->generate_token( uid: $uid, user_id: $user_id );
			}
		}

		return "";
	}

	/**
	 * Verifica validade do token, se vencido, gera um novo
	 *
	 * @since 3.0.2 	Added uid validation with phone search
	 * @since 1.0.0
	 *
     * @param int 	$user_id
	 *
	 * @return string
	 */
	public function validate_token( int $user_id ) : mixed
	{
		$phone    = get_user_meta( user_id: $user_id, key: "_triibo_phone", single: true );
		$phone    = $phone ?: get_user_meta( user_id: $user_id, key: "triiboId_phone", single: true );

		if ( !$phone )
			return null;

		$uid      = get_user_meta( user_id: $user_id, key: "_triibo_id", single: true );
		$token    = get_user_meta( user_id: $user_id, key: "_triibo_auth_token", single: true );
		$validity = get_user_meta( user_id: $user_id, key: "_triibo_auth_validity", single: true );

		if ( !$uid )
		{
			$success = false;
			$resp    = $this->node->auth();

			if ( $resp[ "success" ] )
			{
				$_token = $resp[ "data"  ][ "token" ];
				$resp   = $this->node->find_uid( token: $_token, phone: $phone );
				$this->log( level: "info", message: "find_uid : search user by phone '{$phone}'" );

				if ( $resp[ "success" ] )
				{
					$uid = $resp[ "data" ][ "usersUid" ][ $phone ];
					$this->log( level: "info", message: "find_uid : user found '{$uid}'" );

					if ( $uid )
					{
						$success = true;
						$token   = null;
						update_user_meta( user_id: $user_id, meta_key: "_triibo_id", meta_value: $uid );
					}
				}
			}

			if ( !$success )
				return null;
		}

		if ( !$token )
		{
			$this->log( level: "info", message: "validate_token : user without token {$user_id}" );

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		$dtval = DateTime::createFromFormat( format: "Y-m-d H:i:s", datetime: $validity );
		$now   = new DateTime();
		$diff  = $now->diff( targetObject: $dtval );

		if ( $diff->d > 0 || $diff->h > 10 )
		{
			$this->log( level: "info", message: "validate_token : experied token {$user_id}" );

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		return $token;
	}

	/**
	 * Verifica se cartão já foi tokenizado
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$number
	 * @param string 	$brand
	 * @param string 	$token
	 *
	 * @return array
	 */
	private function check_card_token( string $number, string $brand, string $token ) : array
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

		$this->log( level: "info", message: "check_card_token : " . print_r( value: [ $params, $response ], return: true ) );

		return $response;
	}

	/**
	 * Tokeniza um cartão
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$data
	 * @param string  	$token
	 *
	 * @return array
	 */
	private function create_card_token( array $data, string $token ): array
	{
		$params = [
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

		$response = $this->node->create_token( token: $token, params: $params );

		$this->log( level: "info", message: "create_card_token : " . print_r( value: $response, return: true ) );

		return $response;
	}

	/**
	 * Exclui um cartão tokenizado
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$tokenIndex
	 * @param string 	$token
	 *
	 * @return array
	 */
	private function delete_card_token( string $tokenIndex, string $token ) : array
	{
		$params = [
			"payload" => [
				"gateway"    => $this->gateway->get_service(),
				"tokenIndex" => $tokenIndex
			]
		];

		$response = $this->node->delete_token( token: $token, params: $params );

		$this->log( level: "info", message: "delete_card_token : " . print_r( value: [ $params, $response ], return: true ) );

		return $response;
	}

	/**
	 * Valida dados do cartão.
	 * Retorna token do cartão, se não tiver, tokeniza.
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$posted
	 *
	 * @return array
	 */
	private function status_card( array $posted ) : array
	{
		$number   = substr( string: $posted[ "card_number" ], offset: -4 );
		$response = $this->check_card_token( number: $number, brand: $posted[ "brand" ], token: $posted[ "token" ] );

		// CARTAO NAO TOKENIZADO
		if ( !$response[ "success" ] )
		{
			$data     = [
				"holder"         => $posted[ "holder"      ],
				"cardNumber"     => $posted[ "card_number" ],
				"expirationDate" => $posted[ "month"       ] . "/" . $posted[ "year" ],
				"securityCode"   => $posted[ "cvv"         ],
				"brand"          => $posted[ "brand"       ],
			];

			// CRIANDO TOKEN DO CARTAO
			$response = $this->create_card_token( data: $data, token: $posted[ "token" ] );

			// FALHA NA CRIACAO DO TOKEN DO CARTAO
			if ( !$response[ "success" ] )
			{
				$error = "Falha ao consultar token.";
				$this->log( level: "error", message: "(cod EP2) create_card_token : " . print_r( value: [ $error, $response ], return: true ), is_error: true );

				return [
					"success" => false,
					"error"   => "$error (cod EP2)"
				];
			}

			$created    = true;
			$tokenIndex = $response[ "data" ][ "tokenInfo" ][ "index" ];
		}
		// CARTAO JA TOKENIZADO
		else
		{
			$created    = false;
			$tokenIndex = $response[ "data" ][ "tokenInfo" ][ "index" ];
		}

		return [
			"success"    => true,
			"cretead"    => $created,
			"tokenIndex" => $tokenIndex,
		];
	}

	/**
	 * Processa pagamento
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$data
	 *
	 * @return array
	 */
	private function do_payment( array $data, string $token, string $test ) : array
	{
		/**
		 * Added treatment of description string
		 * @version 3.0.1
		 */
		$descrip  = $this->treatDescription( text: $data[ "description" ] );

		$params   = [
			"payload" => [
				"gateway" => $this->gateway->get_service(),
				"order"   => [
					"orderId"     => $data[ "orderId"     ],
					"totalAmount" => $data[ "totalAmount" ], // em centavos
					"description" => $descrip,
					"tokenIndex"  => $data[ "tokenIndex"  ],
					"name"        => $data[ "name"        ],
					"cellPhone"   => $data[ "phone"       ],
				],
			]
		];

		if ( $data[ "document" ] )
			$params[ "payload" ][ "order" ][ "document" ] = $data[ "document" ];

		$response = $this->node->payment( token: $token, params: $params );

		$this->log( level: "info", message: "do_payment {$test}: " . print_r( value: [ $params, $response ], return: true ) );

		return $response;
	}

	/**
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function update_subscription( WP_REST_Request $request ) : WP_REST_Response
	{
		$settings = get_option( option: "woocommerce_" . WC_Triibo_Assinaturas::DOMAIN . "_settings" );
		$key_prd  = $settings[ "key_prd" ];
		$key_hml  = $settings[ "key_hml" ];

		if (
			(  $this->node->env() && $request->get_header( key: "x-api-key" ) !== $key_hml )
			||
			( !$this->node->env() && $request->get_header( key: "x-api-key" ) !== $key_prd )
		)
		{
			return new WP_REST_Response( data: [ "error" => "Unauthorized" ], status: 401 );
		}

		$order_id = $request->get_param( key: "order_id" );

		$order    = wc_get_order( the_order: $order_id );

		if ( $request->get_param( key: "approved" ) )
		{
			$data = [
				"id"     => null,
				"status" => 2,
			];

			$this->gateway->finalize_order( order: $order, data: $data );
		}
		else
		{
			$order->update_status( new_status: "pending" );
		}

		return new WP_REST_Response();
	}

	/**
	 * Process the subscription request from checkout
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order 	$order 		Order data.
	 * @param array 		$posted 	Posted data.
	 *
	 * @return 	array
	 */
	public function do_subscription_request( \WC_Order $order, array $posted ) : array
	{
		$is_payment_change             = WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment;
		$order_contains_failed_renewal = false;

		// Payment method changes act on the subscription not the original order
		if ( $is_payment_change )
		{
			$subscription = wcs_get_subscription( the_subscription: $order->get_id() );
			$status_card  = $this->status_card( posted: $posted );

			if ( !$status_card[ "success" ] )
			{
				$this->log( level: "critical", message: "(cod EP4) update_payment_method : SUBS " . $subscription->get_id(), is_error: true );

				return [
					"success" => false,
					"error"   => "Falha ao alterar método de pagamento. Tente novamente. (cod EP4)"
				];
			}

			$paymentToken = $subscription->get_meta( key: "_triibo_assinatura_payment_token", single: true );

			$subscription->update_meta_data( key: "_triibo_assinatura_payment_token", value: $status_card[ "tokenIndex" ] );

			$this->log( level: "info", message: "update_payment_method : SUBS " . $subscription->get_id() . " | old ID " . $paymentToken . " | new ID " . $status_card[ "tokenIndex" ] );

			return [
				"success"        => true,
				"payment_change" => true,
			];
		}
		else
		{
			// Otherwise the order is the $order
			if ( $cart_item = wcs_cart_contains_failed_renewal_order_payment() ||
				 false !== WC_Subscriptions_Renewal_Order::get_failed_order_replaced_by( renewal_order_id: $order->get_id() ) )
			{
				$subscriptions                 = wcs_get_subscriptions_for_renewal_order( order: $order );
				$order_contains_failed_renewal = true;
			}
			else
			{
				$subscriptions                 = wcs_get_subscriptions_for_order( order: $order, args: [ "order_type" => [ "switch", "parent", "renewal" ] ] );
			}

			// Only one subscription allowed per order with PayPal
			$subscription = array_pop( array: $subscriptions );
		}

		if ( $order_contains_failed_renewal || !empty( $subscription ) )
		{
			$status_card = $this->status_card( posted: $posted );

			if ( !$status_card[ "success" ] ) return $status_card;

			// PROCESSAMENTO DO PAGAMENTO
			if ( $order->get_total() > 0 )
			{
				$item     = current( array: $order->get_items() );
				$desc     = $item->get_name();

				$total    = intval( value: strval( value: $order->get_total() * 100 ) ); // em centavos
				$name     = $order->get_user()->get( key: "display_name" ) ?: $posted[ "holder" ];
				$doc      = $order->get_meta( key: "_billing_cpf", single: true  ) ?: $posted[ "cpf"    ];
				$phone    = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $posted[ "phone" ] );

				$data     = [
					"orderId"     => $order->get_id(),
					"totalAmount" => $total, // em centavos
					"description" => $desc,
					"tokenIndex"  => $status_card[ "tokenIndex" ],
					"name"        => $name,
					"document"    => $doc,
					"phone"       => $phone,
				];

				// EXECUTANDO PAGAMENTO
				$response = $this->do_payment( data: $data, token: $posted[ "token" ], test: "new" );
				$data     = $status_card[ "tokenIndex" ];

				$response[ "data" ][ "paymentInfo" ][ "token" ] = $data;
			}
			else
			{
				// TESTE GRATUITO
				$response = [
					"success" => true,
					"data"    => [
						"paymentInfo" => [
							"id"      => null,
							"status"  => 2,
							"message" => "Free Trial"
						]
					]
				];
			}

			$pay_id  = $response[ "data" ][ "paymentInfo" ][ "id"      ];
			$message = $response[ "data" ][ "paymentInfo" ][ "message" ];
			$note    = "Payment ID: {$pay_id}\n";

			/**
			 * Add the cielo TID code to the order note
			 *
			 * @since 	3.2.1
			 */
			if ( $this->gateway->get_service() == "cielo" )
			{
				if ( isset( $response[ "data" ][ "paymentInfo" ][ "tid" ] ) )
				{
					$pay_tid  = $response[ "data" ][ "paymentInfo" ][ "tid" ];
					$note    .= "TID: {$pay_tid}\n";
				}
			}

			$note .= "Gateway: {$this->gateway->get_service()}\n";
			$note .= "Message: {$message}\n";

			// FALHA NO PAGAMENTO
			if ( !$response[ "success" ] )
			{
				$error = "Ocorreu um erro ao processar seu pagamento, tente novamente.";
				$this->log( level: "critical", message: "(cod EP3) do_payment : " . print_r( value: [ $error, $response, $data ], return: true ), is_error: true );

				// SE TOKEN DO CARTAO FOI CRIADO NESSE MOMENTO, EXCLUI
				if ( $status_card[ "created" ] )
				{
					$response = $this->delete_card_token( tokenIndex: $status_card[ "tokenIndex" ], token: $posted[ "token" ] );
					$this->log( level: "error", message: "(cod EP3) delete_card_token : " . print_r( value: [ $response ], return: true ) );
				}

				$note .= "Code: cod EP3";
				$order->add_order_note( note: $note );
				$order->save();

				return [
					"success" => false,
					"error"   => "$error (cod EP3)"
				];
			}
			else if ( in_array( needle: $response[ "data" ][ "paymentInfo" ][ "status" ], haystack: [ 3, 10, 11, 13 ] ) )
			{
				// failure
				// 3  : 'Denied',           // Pagamento negado por Autorizador.
				// 10 : 'Voided',           // Pagamento cancelado.
				// 11 : 'Refunded',         // Pagamento cancelado após 23h59 do dia de autorização.
				// 13 : 'Aborted',          // Pagamento cancelado por falha no processamento ou por ação do Antifraude.

				$error = "Cobrança recusada pela operadora do cartão.";
				$this->log( level: "critical", message: "(cod EP6) do_payment : " . print_r( value: [ $error, $response, $data ], return: true ), is_error: true );

				// SE TOKEN DO CARTAO FOI CRIADO NESSE MOMENTO, EXCLUI
				if ( $status_card[ "created" ] )
				{
					$response = $this->delete_card_token( tokenIndex: $status_card[ "tokenIndex" ], token: $posted[ "token" ] );
					$this->log( level: "error", message: "(cod EP6) delete_card_token : " . print_r( value: [ $response ], return: true ) );
				}

				$note .= "Code: cod EP6";
				$order->add_order_note( note: $note );
				$order->save();

				return [
					"success" => false,
					"error"   => "$error (cod EP6)"
				];
			}

			$subscription->set_requires_manual_renewal( value: false );
			$subscription->save();

			return [
				"success" => true,
				"message" => "Pagamento processado com sucesso.",
				"data"    => $response[ "data" ][ "paymentInfo" ]
			];
		}

		$this->log( level: "error", message: "(cod EP99) - Could not process subscription on do_subscription_request()", is_error: true );

		$note  = "Payment ID: \n";
		$note .= "Gateway: {$this->gateway->get_service()}\n";
		$note .= "Message: Could not process subscription on do_subscription_request()\n";
		$note .= "Code: cod EP99";
		$order->add_order_note( note: $note );
		$order->save();

		// Return error message.
		return [
			"success" => false,
			"error"   => "<strong>Triibo Recorrente</strong>: " . __( text: "Ocorreu um erro ao processar seu pagamento, tente novamente. Ou entre em contato conosco para obter assistência. (cod EP99)", domain: "triibo_assinaturas" ),
		];
	}

	/**
	 * Process payment on a subscription renewal
	 *
	 * @since 	1.0.0
	 *
	 * @param \WC_Order 	$order 		Order data.
	 * @param float 		$amount 	The amount to charge.
	 *
	 * @return array
	 */
	public function do_subscription_renewal_payment( \WC_Order $order, float $amount ) : array
	{
		$user_id      = $order->get_user_id();
		$token        = $this->validate_token( user_id: $user_id );

		if ( !$token )
		{
			$error = "Ocorreu um erro ao processar seu pagamento, tente novamente. (cod EP8)";
			$this->log( level: "critical", message: "(cod EP8) validate_token in do_payment : invalid token", is_error: true );

			$order->add_order_note( note: $error );
			$order->save();

			return [
				"success"    => false,
				"error"      => "$error (cod EP8)",
				"full_error" => null,
			];
		}

		$item         = current( array: $order->get_items() );
		$desc         = $item->get_name();

		$total        = intval( value: strval( value: $amount * 100 ) ); // em centavos
		$name         = $order->get_user()->get( key: "display_name" );
		$doc          = $order->get_meta( key: "_billing_cpf", single: true );
		$phone        = get_user_meta( user_id: $user_id, key: "_triibo_phone", single: true );
		$phone        = $phone ?: get_user_meta( user_id: $user_id, key: "triiboId_phone", single: true );

		if ( wcs_order_contains_subscription( order: $order->get_id() ) )
			$subscriptions = wcs_get_subscriptions_for_order( order: $order->get_id() );
		elseif ( wcs_order_contains_renewal( order: $order->get_id() ) )
			$subscriptions = wcs_get_subscriptions_for_renewal_order( order: $order->get_id() );

		$subscription = array_pop( array: $subscriptions );

		$paymentToken = $subscription->get_meta( key: "_triibo_assinatura_payment_token", single: true );

		$data         = [
			"orderId"     => $order->get_id(),
			"totalAmount" => $total, // em centavos
			"description" => $desc,
			"tokenIndex"  => $paymentToken,
			"name"        => $name,
			"document"    => $doc,
			"phone"       => $phone,
		];

		// EXECUTANDO PAGAMENTO
		$response = $this->do_payment( data: $data, token: $token, test: "renewal" );
		$response[ "data" ][ "paymentInfo" ][ "token" ] = $paymentToken;

		$note  = "Payment ID: {$response[ "data" ][ "paymentInfo" ][ "id" ]}\n";
		$note .= "Gateway: {$this->gateway->get_service()}\n";
		$note .= "Message: {$response[ "data" ][ "paymentInfo" ][ "message" ]}\n";

		// FALHA NO PAGAMENTO
		if ( !$response[ "success" ] )
		{
			if ( $response[ "code" ] == 401 && $response[ "data" ][ "error" ] == "Invalid token." )
			{
				$error = "Token inválido, retentativa.";
				$this->log( level: "critical", message: "(cod EP5) do_payment renewal : " . print_r( value: [ $error, $response, $data ], return: true ), is_error: true );

				$uid   = get_user_meta( user_id: $user_id, key: "_triibo_id", single: true );

				$this->generate_token( uid: $uid, user_id: $user_id );

				return $this->do_subscription_renewal_payment( order: $order, amount: $amount );
			}

			$error = "Ocorreu um erro ao processar seu pagamento, tente novamente.";
			$this->log( level: "critical", message: "(cod EP5) do_payment renewal : " . print_r( value: [ $error, $response, $data ], return: true ), is_error: true );

			$note .= "Code: cod EP5 2";
			$order->add_order_note( note: $note );
			$order->save();

			return [
				"success"    => false,
				"error"      => "$error (cod EP5)",
				"full_error" => $response,
			];
		}

		if ( in_array( needle: $response[ "data" ][ "paymentInfo" ][ "status" ], haystack: [ 3, 10, 11, 13 ] ) )
		{
			// failure
			// 3  : 'Denied',           // Pagamento negado por Autorizador.
			// 10 : 'Voided',           // Pagamento cancelado.
			// 11 : 'Refunded',         // Pagamento cancelado após 23h59 do dia de autorização.
			// 13 : 'Aborted',          // Pagamento cancelado por falha no processamento ou por ação do Antifraude.

			$error = "Cobrança recusada pela operadora do cartão.";
			$this->log( level: "critical", message: "(cod EP7) do_payment renewal : " . print_r( value: [ $error, $response, $data ], return: true ), is_error: true );

			$note .= "Code: cod EP7";
			$order->add_order_note( note: $note );
			$order->save();

			return [
				"success"    => false,
				"error"      => "$error (cod EP7)",
				"full_error" => $response,
			];
		}

		$subscription->set_requires_manual_renewal( value: false );
		$subscription->save();

		return [
			"success" => true,
			"message" => "Pagamento processado com sucesso.",
			"data"    => $response[ "data" ][ "paymentInfo" ]
		];
	}

	/**
	 * Treat description
	 *
	 * @since 3.0.1
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	private function treatDescription( $text ) : string
	{
		$divider = "-";

        $text = preg_replace( pattern: "~[^\pL\d]+~u", replacement: $divider, subject: $text );
        $text = iconv( from_encoding: "utf-8", to_encoding: "us-ascii//TRANSLIT", string: $text );
        $text = preg_replace( pattern: "~[^-\w]+~", replacement: "", subject: $text );
        $text = trim( string: $text, characters: $divider );
        $text = preg_replace( pattern: "~-+~", replacement: $divider, subject: $text );
		$text = str_replace( search: $divider, replace: " ", subject: $text );

		return $text;
	}

	/**
	 * Register logs.
	 *
	 * @since 3.4.0 	Changing to the new woocommerce standard.
	 * 					Added param $level.
	 * @since 1.0.0
	 *
	 * @param string 	$level
	 * @param string 	$message
	 * @param bool 		$is_error
	 *
	 * @return void
	 */
	private function log( string $level, string $message, bool $is_error = false ) : void
	{
		$id      = $this->gateway->get_id();
		$debug   = $this->gateway->get_debug();

		$logger  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$context = [ "source" => $id ];

		if ( $is_error || $debug === "yes" )
			$logger->$level( $message, $context );
	}
}
