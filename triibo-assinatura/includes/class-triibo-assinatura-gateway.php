<?php
/**
 * Triibo Assinaturas Gateway class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 3.4.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Assinaturas_Gateway extends WC_Payment_Gateway_CC
{
	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Payment gateway (ex Cielo).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected ?string $service = null;

	/**
	 * Cpf field.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private ?string $cc_hide_cpf_field = null;

	/**
	 * Instance of Api Node.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Node
	 */
	protected ?Triibo_Api_Node $node = null;

	/**
	 * Instance of Assinaturas_API.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Assinaturas_Api
	 */
	protected ?Triibo_Assinaturas_Api $api = null;

	/**
	 * Debug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected ?string $debug = null;

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return self 	A single instance of this class.
	 */
	public static function get_instance() : self
	{
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 3.4.0 	Removed $log class property.
	 * @since 1.0.0
	 */
	public function __construct()
	{
		self::$instance = $this;

		if ( class_exists( class: "WC_Subscriptions_Order" ) )
		{
			$this->id                 = WC_Triibo_Assinaturas::DOMAIN;
			$this->icon               = apply_filters( hook_name: "woocommerce_{$this->id}_icon", value: plugins_url( path: "assets/images/icon-cc.png", plugin: plugin_dir_path( file: __FILE__ ) ) );

			$this->method_title       = __( text: "Triibo Recorrente",                                         domain: $this->id );
			$this->method_description = __( text: "Pagamentos com cartão de crédito usando a Triibo Gateway.", domain: $this->id );
			$this->order_button_text  = __( text: "Prossiga para o pagamento",                                 domain: $this->id );

			// Subscriptions
			$this->supports           = apply_filters(
				hook_name: "{$this->id}_supports_array",
				value    : [
					"subscriptions",
					"subscription_cancellation",
					"subscription_suspension",
					"subscription_reactivation",
					"subscription_amount_changes",
					"subscription_date_changes",
					"subscription_payment_method_change",
					"subscription_payment_method_change_admin",
					"subscription_payment_method_change_customer",
					"default_credit_card_form"
				]
			);

			// Load the form fields.
			$this->init_form_fields();

			// Load the settings.
			$this->init_settings();

			// Define user set variables.
			$this->title              = $this->get_option( key: "title"       );
			$this->description        = $this->get_option( key: "description" );
			$this->service            = $this->get_option( key: "service"     );
			$this->debug              = $this->get_option( key: "debug"       );

			$this->cc_hide_cpf_field  = apply_filters( hook_name: $this->id . "_hide_cpf_field", value: false );

			$this->node               = new Triibo_Api_Node();
			$this->api                = new Triibo_Assinaturas_Api( gateway: $this, node: $this->node );

			$this->init_actions();
		}
	}

	/**
	 * Get api
	 *
	 * @since 1.0.0
	 *
	 * @return ?Triibo_Assinaturas_Api
	 */
	public function get_api() : ?Triibo_Assinaturas_Api
	{
		return $this->api;
	}

	/**
	 * Get service
	 *
	 * @since 1.0.0
	 *
	 * @return ?string
	 */
	public function get_service() : ?string
	{
		return $this->service;
	}

	/**
	 * Get id
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_id() : string
	{
		return $this->id;
	}

	/**
	 * Get debug
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_debug() : ?string
	{
		return $this->debug;
	}

	/**
	 * Admin page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_options() : void
	{
		wp_enqueue_script(
			handle: "{$this->id}_admin",
			src   : plugins_url(
				path  : "assets/js/admin/admin.js",
				plugin: plugin_dir_path( file: __FILE__ )
			),
			deps  : [ "jquery" ],
			ver   : WC_Triibo_Assinaturas::VERSION,
			args  : true
		);

		include dirname( path: __FILE__ ) . "/admin/views/html-admin-page.php";
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_actions() : void
	{
		add_action( hook_name: "wp_enqueue_scripts",                                                  callback: [ $this, "checkout_scripts"                                  ]        );
		add_action( hook_name: "woocommerce_scheduled_subscription_payment_{$this->id}",              callback: [ $this, "scheduled_subscription_payment"                    ], priority: 10, accepted_args: 2 );
		add_action( hook_name: "woocommerce_update_options_payment_gateways_{$this->id}",             callback: [ $this, "process_admin_options"                             ]        );
		add_action( hook_name: "woocommerce_subscription_failing_payment_method_updated_{$this->id}", callback: [ $this, "update_failing_payment_method"                     ], priority: 10, accepted_args: 2 );

		add_filter( hook_name: "woocommerce_credit_card_form_fields",                                 callback: [ $this, "filter_cc_fields"                                  ], priority: 10, accepted_args: 2 );
		add_filter( hook_name: "woocommerce_can_subscription_be_updated_to_new-payment-method",       callback: [ $this, "can_subscription_be_updated_to_new_payment_method" ], priority: 20, accepted_args: 2 );
		add_filter( hook_name: "woocommerce_subscriptions_update_payment_via_pay_shortcode",          callback: [ $this, "can_update_all_subscriptions"                      ], priority: 20, accepted_args: 3 );
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @since 	1.0.0
	 * @return 	bool
	 */
	public function using_supported_currency() : bool
	{
		return "BRL" === get_woocommerce_currency();
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_available() : bool
	{
		// Test if is valid for use.
		$available = "yes" === $this->get_option( key: "enabled" ) &&
					 $this->node->status() &&
					 $this->using_supported_currency() &&
					 (
						wcs_cart_contains_renewal() ||
						WC_Subscriptions_Cart::cart_contains_subscription() ||
						WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment
					 );

		if ( !class_exists( class: "Extra_Checkout_Fields_For_Brazil" ) )
			$available = false;

		return $available;
	}

	/**
	 * Get log.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_log_view() : string
	{
		$date      = date( format: "Y-m-d" );
		$esc_attr  = esc_attr( text: $this->id );
		$sanitize  = sanitize_file_name( filename: wp_hash( data: $this->id ) );
		$admin_url = "admin.php?page=wc-status&tab=logs&log_file={$esc_attr}-{$date}-{$sanitize}.log";
		$link      = esc_url( url: admin_url( path: $admin_url ) );
		$text      = __( text: "System Status &gt; Logs", domain: $this->id );

		return "<a href='{$link}' target='_blank' >{$text}</a>";
	}

	/**
	 * Get log.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_global_config() : string
	{
		$triibo = Triibo_Api_Services::get_name( config: true );
        $url    = esc_url( url: admin_url( path: "admin.php?page={$triibo}" ) );
		$text   = __( text: "clicando aqui", domain: $this->id );

		return "<a href='{$url}' target='_blank' >{$text}</a>";
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_form_fields() : void
	{
		$this->form_fields = [

			// ativar / desativar meio de pagamento
			"enabled"         => [
				"type"        => "checkbox",
				"title"       => __( text: "Ativar / Desativar",                                        domain: $this->id ),
				"label"       => __( text: "Ativar Triibo Recorrente",                                  domain: $this->id ),
				"default"     => "no",
			],

			"title"           => [
				"type"        => "text",
				"title"       => __( text: "Título",                                                    domain: $this->id ),
				"description" => __( text: "Controla o título que o usuário vê durante o checkout.",    domain: $this->id ),
				"default"     => __( text: "Triibo Recorrente",                                         domain: $this->id ),
			],
			"description"     => [
				"type"        => "textarea",
				"title"       => __( text: "Descrição",                                                 domain: $this->id ),
				"description" => __( text: "Controla a descrição que o usuário vê durante o checkout.", domain: $this->id ),
				"default"     => __( text: "Pague com a Triibo",                                        domain: $this->id ),
			],

			"service"         => [
				"type"        => "select",
				"title"       => __( text: "Serviço de pagamento",                                      domain: $this->id ),
				"description" => __( text: "Escolha qual será o serviço de pagamento padrão.",          domain: $this->id ),
				"default"     => "cielo",
				"class"       => "wc-enhanced-select",
				"options"     => [
					"cielo"     => __( text: "Cielo", domain: $this->id ),
					// "pagseguro" => __( "PagSeguro", $this->id ),
					// "value"     => __( "Name",      $this->id ),
				],
			],

			"key_prd"         => [
				"type"        => "text",
				"title"       => __( text: "Chave PRD",                                                 domain: $this->id ),
				"description" => __( text: "Chave de verificação da API PRD",                           domain: $this->id ),
				"default"     => __( text: "",                                                          domain: $this->id ),
			],
			"key_hml"         => [
				"type"        => "text",
				"title"       => __( text: "Chave HML",                                                 domain: $this->id ),
				"description" => __( text: "Chave de verificação da API HML",                           domain: $this->id ),
				"default"     => __( text: "",                                                          domain: $this->id ),
			],

			"debug"           => [
				"type"        => "checkbox",
				"title"       => __( text: "Debug Log",                                                 domain: $this->id ),
				"label"       => __( text: "Ativar logger",                                             domain: $this->id ),
				"description" => sprintf( __( text: "Registrar log de eventos, %s", domain: $this->id ), $this->get_log_view() ),
				"default"     => "yes",
			],

			"configs" => [
				"type"        => "title",
				"title"       => __( text: "Chaves de API",                                             domain: $this->id ),
				"description" => sprintf( __( text: "Acessar configurações globais %s.", domain: $this->id ), $this->get_global_config() ),
			],
		];
	}

	/**
	 * Checkout scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkout_scripts() : void
	{
		$load_scripts = false;

		if ( is_checkout() )
			$load_scripts = true;

		if ( $this->is_available() )
			$load_scripts = true;

		if ( false === $load_scripts )
			return;

		wp_enqueue_style(
			handle: "{$this->id}_checkout",
			src   : plugins_url(
				path  : "assets/css/frontend/transparent-checkout.css",
				plugin: plugin_dir_path( file: __FILE__ )
			),
			deps  : [],
			ver   : WC_Triibo_Assinaturas::VERSION
		);

		wp_enqueue_script(
			handle: "{$this->id}_checkout",
			src   : plugins_url(
				path  : "assets/js/frontend/transparent-checkout.js",
				plugin: plugin_dir_path( file: __FILE__ )
			),
			deps  : [ "json2", "jquery", "woocommerce-extra-checkout-fields-for-brazil-front" ],
			ver   : WC_Triibo_Assinaturas::VERSION,
			args  : true
		);

        $user = wp_get_current_user();

		$this->api->validate_token( user_id: $user->ID );

		$url = ( $this->node->env() )
				? "https://dev-marketplace.triibo.com.br/wp-json/{$this->id}/v1"
				: "https://marketplace.triibo.com.br/wp-json/{$this->id}/v1";

		wp_localize_script(
			handle     : "{$this->id}_checkout",
			object_name: "wc_{$this->id}_params",
			l10n       : [
				"user_id"  => $user->ID,
				"base_url" => $url,
				"messages" => [
					"session_error"      => __( text: "Ocorreu um error. Por favor, atualize a página.",                       domain: $this->id ),

					"unsupported_card"   => __( text: "Essa bandeira não é suportada pelo Triibo Recorrente atualmente.",      domain: $this->id ),

					"invalid_card"       => __( text: "O número do cartão de crédito é inválido.",                             domain: $this->id ),
					"invalid_expiry"     => __( text: "A data de expiração é inválida, por favor, utilize o formato MM / AA.", domain: $this->id ),
					"invalid_cvv"        => __( text: "O código do cartão é inválido.",                                        domain: $this->id ),

					"invalid_holder"     => __( text: "Informe o nome do titular do cartão.",                                  domain: $this->id ),
					"invalid_h_cpf"      => __( text: "Informe o CPF do titular do cartão.",                                   domain: $this->id ),

					"expired_date"       => __( text: "Por favor, verifique a data de expiração e utilize o formato MM / AA.", domain: $this->id ),

					"empty_installments" => __( text: "Selecione o número de parcelas.",                                       domain: $this->id ),
					"interest_free"      => __( text: "sem juros",                                                             domain: $this->id ),

					"general_error"      => __( text: "Não foi possível processar os dados do seu cartão de crédito, por favor, tente novamente ou entre em contato para receber ajuda.", domain: $this->id ),
				]
			]
		);
	}

	/**
	 * Filter Credit card form fields
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$fields
	 * @param string 	$id
	 *
	 * @return array
	 */
	function filter_cc_fields( array $fields, string $id ) : array
	{
		if ( $id != $this->id )
			return $fields;

		$id = esc_attr( text: $this->id );

		$fields[ "card-expiry-field" ] =
		"<p class='form-row form-row-first'>
			<label for='{$id}-card-expiry' >"      . esc_html__( text: "Validade (MM/YY)", domain: $this->id ) . "&nbsp;<span class='required' >*</span></label>
			<input id='{$id}-card-expiry' class='input-text wc-credit-card-form-card-expiry' inputmode='numeric' autocomplete='cc-exp' autocorrect='no' autocapitalize='no' spellcheck='no' type='tel' placeholder='" . esc_attr__( text: "MM / YY", domain: $this->id ) . "' " . $this->field_name( name: "card-expiry" ) . " />
		</p>";

		$fields[ "card-name" ] =
		"<p class='form-row form-row-wide'>
			<label for='{$id}-card-holder-name' >" . esc_html__( text: "Nome do titular", domain: $this->id ) . "&nbsp;<span class='required' >*</span></label>
			<input id='{$id}-card-holder-name' class='input-text wc-credit-card-form_card_holder_name' type='text' placeholder='" . esc_attr__( text: "Informe o nome do titular", domain: $this->id ) . "' " . $this->field_name( name: "card-holder-name" ) . " />
		</p>";

		if ( false === $this->cc_hide_cpf_field || is_wc_endpoint_url( endpoint: "order-pay" ) )
		{
			$fields[ "card-cpf" ] =
			"<p class='form-row form-row-wide'>
				<label for='{$id}-card-holder-cpf' >" . esc_html__( text: "CPF do titular", domain: $this->id ) . "&nbsp;<span class='required'>*</span></label>
				<input id='{$id}-card-holder-cpf' class='input-text wc-credit-card-form_card_holder_cpf' type='tel' placeholder='" . esc_attr__( text: "000.000.000-00", domain: $this->id ) . "' " . $this->field_name( name: "card-holder-cpf" ) . "/>
			</p>";
		}

		return $fields;
	}

	/**
	 * Payment Fields Hook.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function payment_fields() : void
	{
		if ( ( class_exists( class: "WC_Subscriptions_Cart" ) && WC_Subscriptions_Cart::cart_contains_subscription() )
			|| wcs_cart_contains_resubscribe()
			|| wcs_cart_contains_renewal()
			|| WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment )
		{
			$description = ( $this->node->env() ) ? $this->get_description() . __( text: " - HOMOL ativado.", domain: $this->id ) : $this->get_description();

			if ( $description )
				echo wpautop( text: wptexturize( text: trim( string: $description ) ) );

			parent::payment_fields();
		}
	}

	/**
	 * Process the payment
	 * Function called by WooCommerce to process the payment
	 *
	 * @since 1.0.0
	 *
	 * @param mixed 	$order_id
	 *
	 * @return array
	 */
	public function process_payment( mixed $order_id ) : array
	{
		// Processing subscription
		if ( function_exists( function: "wcs_order_contains_subscription" ) )
			return $this->process_subscription( order_id: $order_id );

		return [];
	}

	/**
	 * Process the subscription payment
	 *
	 * @since 1.0.0
	 *
	 * @param int 	$order_id
	 *
	 * @return array
	 */
	public function process_subscription( int $order_id ) : array
	{
		$msg       = "<b>" . $this->title . "</b>:";
		$user      = wp_get_current_user();
		$token     = $this->api->validate_token( user_id: $user->ID );

		if ( !$token )
		{
			$msg  = "{$msg} Sua conta não está vinculado a uma conta Triibo!<br>";
			$msg .= "Por favor, vincule sua conta Triibo, refazendo o login através do 'Entrar com Triibo'.";

			wc_add_notice( message: $msg, notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		$number    = WC()->checkout()->get_value( input: $this->id . "-card_number" );
		$response  = $this->get_brand( token: $token, card_number: $number );

		if ( !$response[ "success" ] )
		{
			wc_add_notice( message: "{$msg} Erro ao consultar bandeira do cartão!", notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		if ( !$response[ "data" ][ "brandInfo" ][ "supported" ] )
		{
			wc_add_notice( message: "{$msg} Bandeira do cartão não suportada!", notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		$brand     = $response[ "data" ][ "brandInfo" ][ "brand" ];
		$order     = wc_get_order( the_order: $order_id );

		$t_phone   = get_user_meta( user_id: $user->ID, key: "_triibo_phone", single: true );
		$t_phone   = $t_phone ?: get_user_meta( user_id: $user->ID, key: "triiboId_phone", single: true );

		$cardInfo  = [
			"token"       => $token,

			"holder"      => WC()->checkout()->get_value( input: $this->id . "-holder"    ),
			"cpf"         => WC()->checkout()->get_value( input: $this->id . "-h_cpf"     ),
			"phone"       => $t_phone,

			"card_number" => $number,
			"cvv"         => WC()->checkout()->get_value( input: $this->id . "-cvv"       ),
			"month"       => WC()->checkout()->get_value( input: $this->id . "-exp_month" ),
			"year"        => WC()->checkout()->get_value( input: $this->id . "-exp_year"  ),
			"brand"       => $brand,
		];

		$response  = $this->api->do_subscription_request( order: $order, posted: $cardInfo );

		if ( !$response[ "success" ] )
		{
			wc_add_notice( message: "{$msg} " . $response[ "error" ], notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		if ( !isset( $response[ "payment_change" ] ) || !$response[ "payment_change" ] )
		{
			$this->finalize_order( order: $order, data: $response[ "data" ] );

			// Remove cart.
			WC()->cart->empty_cart();
		}

		return [
			"result"   => "success",
			"redirect" => $this->get_return_url( order: $order ),
		];
	}

	/**
	 * Validates the flag given the card number
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$token
	 * @param string 	$card_number
	 *
	 * @return array
	 */
	public function get_brand( string $token, string $card_number ) : array
	{
		$params   = [
			"payload" => [
				"gateway"    => $this->get_service(),
				"cardNumber" => $card_number,
			]
		];

		$response = $this->node->brand( token: $token, params: $params );

		$params[ "payload" ][ "cardNumber" ] = "****-****-****-" . substr( string: $card_number, offset: -4 );
		$this->log( level: "info", message: "get_brand : " . print_r( value: [ $params, $response ], return: true ) );

		return $response;
	}

	/**
	 * Update order status.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order 	$order 	Order data
	 * @param array 		$data 	Response data
	 *
	 * @return void
	 */
	public function finalize_order( \WC_Order $order, array $data ) : void
	{
		if ( $order )
		{
			if ( $data[ "id" ] )
			{
				$this->save_triibo_payment_data( order_id: $order->get_id(), data: $data );

				$pay_id  = $data[ "id"      ];
				$message = $data[ "message" ];
				$note    = "Payment ID: {$pay_id}\n";

				/**
				 * Add the cielo TID code to the order note
				 *
				 * @since 	3.2.1
				 */
				if ( $this->service == "cielo" )
				{
					if ( isset( $data[ "tid" ] ) )
					{
						$pay_tid  = $data[ "tid" ];
						$note    .= "TID: {$pay_tid}\n";
					}
				}

				$note .= "Gateway: {$this->service}\n";
				$note .= "Message: {$message}\n";
				$note .= "Code: 0";
				$order->add_order_note( note: $note );
				$order->save();
			}

			$this->log( level: "info", message: "finalize_order : ID " . $order->get_id() . " : " . print_r( value: $data, return: true ) );

			/**
			 * waiting
			 * 0  : 'NotFinished',      // Aguardando atualização de status.
			 * 12 : 'Pending',          // Aguardandoretorno da instituição xfinanceira.
			 *
			 * success
			 * 1  : 'Authorized',       // Pagamento apto a ser capturado ou definido como pago.
			 * 2  : 'PaymentConfirmed', // Pagamento confirmado e finalizado.
			 *
			 * fail
			 * 	3 : 'Denied',           // Pagamento negado por Autorizador.
			 * 10 : 'Voided',           // Pagamento cancelado.
			 * 11 : 'Refunded',         // Pagamento cancelado após 23h59 do dia de autorização.
			 * 13 : 'Aborted',          // Pagamento cancelado por falha no processamento ou por ação do Antifraude.
			 */

			switch ( $data[ "status" ] )
			{
				case 1:
				case 2:
					$order->payment_complete();
					$order->update_status( new_status: "processing" );
				break;

				case 0:
				case 12:
					$order->update_status( new_status: "on-hold" );
				break;

				case 3:
				case 10:
				case 11:
				case 13:
					$order->update_status( new_status: "failed" );
				break;
			}
		}
	}

	/**
	 * Saves the payment_id on the main subscription.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id
	 * @param array $data Data from payment
	 *
	 * @return void
	 */
	protected function save_triibo_payment_data( int $order_id, array $data ) : void
	{
		update_post_meta( post_id: $order_id, meta_key: "_triibo_assinatura_payment_id",    meta_value: $data[ "id"    ] );
		update_post_meta( post_id: $order_id, meta_key: "_triibo_assinatura_payment_token", meta_value: $data[ "token" ] );

		$subscriptions = [];

		// Also store it on the subscriptions being purchased or paid for in the order
		if ( wcs_order_contains_subscription( order: $order_id ) )
		{
			$subscriptions = wcs_get_subscriptions_for_order( order: $order_id );
		}
		else if ( wcs_order_contains_renewal( order: $order_id ) )
		{
			$subscriptions = wcs_get_subscriptions_for_renewal_order( order: $order_id );
		}

		foreach( $subscriptions as $subscription )
		{
			update_post_meta( post_id: $subscription->get_id(), meta_key: "_triibo_assinatura_payment_id",    meta_value: $data[ "id"    ] );
			update_post_meta( post_id: $subscription->get_id(), meta_key: "_triibo_assinatura_payment_token", meta_value: $data[ "token" ] );
		}

		return;
	}

	/**
	 * Process the renewal payment.
	 *
	 * @since 1.0.0
	 * @param float 		$renewal_total 	The amount to charge.
	 * @param \WC_Order 	$renewal_order 	A WC_Order object created to record the renewal payment.
	 *
	 * @return void
	 */
	public function scheduled_subscription_payment( float $renewal_total, \WC_Order $renewal_order ) : void
	{
		$response = $this->api->do_subscription_renewal_payment( order: $renewal_order, amount: $renewal_total );

		if ( !$response[ "success" ] )
		{
			$this->log( level: "critical", message: "scheduled_subscription_payment : order_id {$renewal_order->ID} | " . print_r( value: $response, return: true ), is_error: true );

			$renewal_order->update_status( new_status: "pending" );

			return;
		}

		$this->finalize_order( order: $renewal_order, data: $response[ "data" ] );

		return;
	}

	/**
	 * Update the _pagseguro_assinatura for a subscription after using Pagseguro Recorrente to complete a payment to make up for.
	 * an automatic renewal payment which previously failed.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Subscription  $subscription 	The subscription for which the failing payment method relates.
	 * @param \WC_Order 		$renewal_order 	The order which recorded the successful payment (to make up for the failed automatic payment).
	 *
	 * @return void
	 */
	public function update_failing_payment_method( \WC_Subscription $subscription, \WC_Order $renewal_order ) : void
	{
		$id  = $renewal_order->get_meta( key: "_triibo_assinatura_payment_id",    single: true );
		$tk  = $renewal_order->get_meta( key: "_triibo_assinatura_payment_token", single: true );

		$sid = $subscription->get_id();

		$this->log( level: "critical", message: "update_failing_payment_method : SUB - {$sid} | id - {$id} | tk - {$tk}", is_error: true );

		$subscription->update_meta_data( key: "_triibo_assinatura_payment_id",    value: $id );
		$subscription->update_meta_data( key: "_triibo_assinatura_payment_token", value: $tk );
	}

	/**
	 *
	 * For the recurring payment method to be changeable, the subscription must be active, have future (automatic) payments
	 * and use a payment gateway which allows the subscription to be cancelled.
	 *
	 * @since 1.0.0
	 *
	 * @param bool 				$subscription_can_be_changed 	Flag of whether the subscription can be changed.
	 * @param \WC_Subscription 	$subscription 					The subscription to check.
	 *
	 * @return bool 	Flag indicating whether the subscription payment method can be updated.
	 */
	function can_subscription_be_updated_to_new_payment_method( bool $subscription_can_be_changed, \WC_Subscription $subscription ) : bool
	{
		if ( $this->id == $subscription->get_payment_method() )
			if ( $subscription->has_status( status: [ "active", "on-hold" ] ) )
				return true;

		return $subscription_can_be_changed;
	}

	/**
	 *
	 * Return false because pagseguro does not support multiple subscriptions
	 *
	 * @since 1.0.0
	 *
	 * @param bool 				$subscription_can_be_changed 	Flag of whether the subscription can be changed.
	 * @param string 			$gateway_id 					The gateway id.
	 * @param \WC_Subscription 	$subscription 					The subscription to check.
	 *
	 * @return bool 	Flag indicating whether the other subscriptions can be updated.
	 */
	function can_update_all_subscriptions( bool $can_update, string $gateway_id, \WC_Subscription $subscription ) : bool
	{
		if ( $this->id == $gateway_id )
			return false;

		return $can_update;
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
		$logger  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$context = [ "source" => $this->id ];

		if ( $is_error || $this->debug === "yes" )
			$logger->$level( $message, $context );
	}
}
