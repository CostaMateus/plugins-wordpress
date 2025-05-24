<?php
/**
 * Gateway class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Classes/Gateway
 * @version 1.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Gateway
 */
class WC_Pagare_Gateway extends WC_Payment_Gateway
{
	/**
	 * Instance of this class
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	protected $domain;
	protected $is_homol;
	protected $access_key_hml;
	protected $access_key;
	protected $cc;
	protected $cc_type;
	protected $installment;
	protected $pix;
	protected $pix_due_date;
	protected $pix_whatsapp;
	protected $pix_telegram;
	protected $pix_email;
	protected $pix_message;
	protected $ticket;
	protected $ticket_due_date;
	protected $ticket_message;
	protected $invoice_prefix;
	protected $debug;
	protected $cc_hide_cpf_field;
	protected $log;
	protected $api;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
    {
		$this->domain             = "pagare-gateway";
		$this->id                 = "pagare";
		$this->icon               = apply_filters( "woocommerce_pagare_icon", plugins_url( "assets/images/pagare-icon-25.png", plugin_dir_path( __FILE__ ) ) );

		$this->method_title       = __( "Pagare",                                                                     $this->domain );
		$this->method_description = __( "Aceite pagamentos por cartão de crédito, boleto e pix utilizando o Pagare.", $this->domain );
		$this->order_button_text  = __( "Realizar pagamento",                                                         $this->domain );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

        // Define user set variables.
        $this->title             = $this->get_option( "title"                      );
        $this->description       = $this->get_option( "description"                );

        $this->is_homol          = $this->get_option( "is_homol",        "yes"     );
        $this->access_key_hml    = $this->get_option( "access_key_hml"             );
        $this->access_key        = $this->get_option( "access_key"                 );

        $this->cc                = $this->get_option( "cc"                         );
        $this->cc_type           = $this->get_option( "cc_type",         "avista"  );
        $this->installment       = $this->get_option( "installment",     "1"       );

		$this->pix               = $this->get_option( "pix"                        );
		$this->pix_due_date      = $this->get_option( "pix_due_date",    "2"       );
		$this->pix_whatsapp      = $this->get_option( "pix_whatsapp",              );
		$this->pix_telegram      = $this->get_option( "pix_telegram",              );
		$this->pix_email         = $this->get_option( "pix_email",                 );
		$this->pix_message       = "Utilize o seu aplicativo favorito do Pix para ler o QR Code ou copiar o código abaixo e efetuar o pagamento.";

		$this->ticket            = $this->get_option( "ticket"                     );
		$this->ticket_due_date   = $this->get_option( "ticket_due_date", "2"       );
		$this->ticket_message    = "Utilize o aplicativo do seu banco para ler o código de barras ou copiar o código abaixo e efetuar o pagamento.";

		$this->invoice_prefix    = $this->get_option( "invoice_prefix",  "WC-"     );
        $this->debug             = $this->get_option( "debug",           "yes"     );
        $this->cc_hide_cpf_field = apply_filters( "pagare_hide_cpf_field", false );

        // Active logs.
        if ( $this->debug === "yes" ) $this->log = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		// Set the API.
		$this->api = new WC_Pagare_API( $this );

		// Main actions and Transparent checkout actions.
        $this->init_actions();
	}

	/**
	 * Return an instance of this class
	 *
	 * @since 	1.2.0
	 * @return 	object 	A single instance of this class.
	 */
	public static function init()
    {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Return WC_Pagare_API
	 *
	 * @since 	1.2.0
	 * @return 	WC_Pagare_API
	 */
	public function get_api()
	{
		return $this->api;
	}


	public function get_is_homol()
	{
		return $this->is_homol;
	}

	public function get_cc_type()
	{
		return $this->cc_type;
	}

	public function get_invoice_prefix()
	{
		return $this->invoice_prefix;
	}

	public function get_pix_due_date()
	{
		return $this->pix_due_date;
	}

	public function get_ticket_due_date()
	{
		return $this->ticket_due_date;
	}

	public function get_debug()
	{
		return $this->debug;
	}

	public function get_log()
	{
		return $this->log;
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = [

			// Enable/Disable Pagare Gateway
			"enabled"         => [
				"type"        => "checkbox",
				"title"       => __( "Ativar / Desativar",                                                  $this->domain ),
				"label"       => __( "Ativar Pagare",                                                       $this->domain ),
				"default"     => "no",
			],

			"title"           => [
				"type"        => "text",
				"title"       => __( "Título",                                                              $this->domain ),
				"description" => __( "Controla o título que o usuário vê durante o checkout.",              $this->domain ),
				"default"     => __( "Pagare",                                                              $this->domain ),
				// "desc_tip"    => true,
			],
			"description"     => [
				"type"        => "textarea",
				"title"       => __( "Descrição",                                                           $this->domain ),
				"description" => __( "Controla a descrição que o usuário vê durante o checkout.",           $this->domain ),
				"default"     => __( "Pague com a Pagare",                                                  $this->domain ),
				// "desc_tip"    => true,
			],

			// integração
			"integration"     => [
				"title"       => __( "Integração",                                                          $this->domain ),
				"type"        => "title",
				"description" => "",
			],
			"is_homol"        => [
				"type"        => "checkbox",
				"title"       => __( "API sandbox",                                                         $this->domain ),
				"label"       => __( "Ativar API sandbox (homologação)",                                    $this->domain ),
				"default"     => "yes",
			],
			"access_key_hml"  => [
				"type"        => "password",
				"title"       => __( "Chave (sandbox)",                                                     $this->domain ),
				"description" => __( "Entre com sua chave de autenticação do Pagare Gateway (sandbox).",    $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],
            "access_key"      => [
				"type"        => "password",
				"title"       => __( "Chave (produção)",                                                    $this->domain ),
				"description" => __( "Entre com sua chave de autenticação do Pagare Gateway (produção).",   $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],

			// card
			"cc_title"        => [ "type" => "title" ],
			"cc"              => [
				"type"    => "checkbox",
				"title"   => __( "Cartão de Crédito",                                                       $this->domain ),
				"label"   => __( "Ativar cartão de crédito no checkout",                                    $this->domain ),
				"default" => "yes",
            ],
			"cc_type"         => [
				"type"        => "select",
				"title"       => __( "Tipo de pagamento",                                                   $this->domain ),
				"description" => __( "Selecione \"parcelado\" para oferecer a opção de
									  parcelamento sem juros no cartão de crédito para o cliente.",         $this->domain ),
				"desc_tip"    => true,
				"default"     => "avista",
				"class"       => "wc-enhanced-select",
				"options"     => [
					"avista"  => __(  "À vista",                                                            $this->domain ),
					// opção fixa, v1.0.0
					"lojista" => __(  "Parcelado",                                                          $this->domain ),

					// opções desativadas devido a taxação não estar no backend da Pagare
					// "lojista" => __(  "Parcelado s/ juros",                                                 $this->domain ),
					// "emissor" => __(  "Parcelado c/ juros",                                                 $this->domain ),
				],
            ],
			"installment"     => [
				"type"        => "number",
				"title"       => __( "Número de parcelas",                                                  $this->domain ),
				"description" => __( "Selecione o número máximo de parcelas disponíveis para o cliente.",   $this->domain ),
				"desc_tip"    => true,
				"default"     => 1,
				"custom_attributes" => [
					"maxlength"   => 2,
					"min"         => 1,
					"max"         => 99,
				],
            ],

			// pix
			"pix_title"       => [ "type" => "title" ],
            "pix"             => [
                "type"    => "checkbox",
                "title"   => __( "PIX",                                                                     $this->domain ),
                "label"   => __( "Ativar pix no checkout",                                                  $this->domain ),
                "default" => "yes",
            ],
			"pix_due_date"    => [
				"type"        => "number",
				"title"       => __( "Validade do QRCode do pix",                                           $this->domain ),
				"description" => __( "Informe qual o prazo de validade do QRCode do PIX, em dias.<br>
									  Mínimo 1.",                                                           $this->domain ),
				"default"     => 2,
				"custom_attributes" => [
					"maxlength" => 2,
					"min"       => 1,
				],
            ],
			"pix_whatsapp"    => [
				"type"        => "text",
				"title"       => __( "WhatsApp para contato",                                               $this->domain ),
				"description" => __( "Seu número de WhatsApp será informado ao cliente para compartilhar o
									  comprovante de pagamento.<br>Modelo: 5511943214321",                  $this->domain ),
				"default"     => "",
			],
			"pix_telegram"    => [
				"type"        => "text",
				"title"       => __( "Telegram para contato",                                               $this->domain ),
				"description" => __( "Seu username do Telegram será informado ao cliente para compartilhar
									  o comprovante de pagamento.<br>
									  Informe o username sem @, exemplo: zesilva.",                         $this->domain ),
				"default"     => "",
			],
			"pix_email"       => [
				"type"        => "email",
				"title"       => __( "Email para contato",                                                  $this->domain ),
				"description" => __( "Seu email será informado ao cliente para compartilhar o comprovante
									  de pagamento.",                                                       $this->domain ),
				"default"     => get_option( "admin_email" ),
			],

			// boleto
			"ticket_title"    => [ "type" => "title" ],
			"ticket"          => [
				"type"    => "checkbox",
                "title"   => __( "Boleto bancário",                                                         $this->domain ),
				"label"   => __( "Ativar boleto bancário no checkoutt",                                     $this->domain ),
				"default" => "yes",
            ],
			"ticket_due_date" => [
				"type"        => "number",
				"title"       => __( "Validade do boleto",                                                  $this->domain ),
				"description" => __( "Informe qual o prazo de validade do boleto bancário, em dias.<br>
									  Mínimo 1.",                                                           $this->domain ),
				"default"     => 2,
				"custom_attributes" => [
					"maxlength" => 2,
					"min"       => 1,
				],
            ],

			"behavior"        => [
				"title"       => __( "Comportamento da integração",                                         $this->domain ),
				"type"        => "title",
				"description" => "",
            ],
			"invoice_prefix"  => [
				"type"        => "text",
				"title"       => __( "Prefixo do pedido",                                                                                                                                                                    $this->domain ),
				"description" => __( "Por favor informe um prefixo para utilizar com os números de pedidos.
									  Caso você utilize sua conta do Pagare em mais de uma loja,
									  procure utilizar um prefixo único para cada loja.",                   $this->domain ),
				"desc_tip"    => true,
				"default"     => "WC-",
            ],

            "debug"           => [
				"type"        => "checkbox",
				"title"       => __( "Debug Log",                                                           $this->domain ),
				"label"       => __( "Ativar Logger",                                                       $this->domain ),
				"description" => sprintf( __( "Registrar log de eventos, %s",                               $this->domain ), $this->get_log_view() ),
				"default"     => "yes",
			],
		];
	}

    /**
     * Initialise main actions
     */
	public function init_actions()
	{
		add_action( "wp_enqueue_scripts",                                       [ $this, "checkout_scripts"      ] );
		add_action( "woocommerce_update_options_payment_gateways_" . $this->id, [ $this, "process_admin_options" ] );
		add_action( "woocommerce_thankyou_"                        . $this->id, [ $this, "thankyou_page"         ] );
		add_action( "woocommerce_email_before_order_table",                     [ $this, "email_instructions"    ], 99, 4 );

		if ( is_account_page() ) add_action( "woocommerce_order_details_after_order_table", [ $this, "order_page" ] );
    }

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency()
    {
		return get_woocommerce_currency() === "BRL";
	}

	public function is_ecfb_active()
	{
		return is_plugin_active( "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" );
	}

	/**
	 * Get access key.
	 *
	 * @return string
	 */
	public function get_access_key()
	{
		return ( $this->is_homol === "yes" ) ? $this->access_key_hml : $this->access_key;
	}

	/**
	 * Returns a value indicating the the Gateway is available or not.
	 * It's called automatically by WooCommerce before allowing
	 * customers to use the gateway for payment.
	 *
	 * @return bool
	 */
	public function is_available()
	{
		// Test if is valid for use.
		$available = $this->get_option( "enabled" ) === "yes" &&
					 $this->get_access_key()        !== ""    &&
					 $this->using_supported_currency()        &&
					 $this->is_ecfb_active();

		if ( !class_exists( "Extra_Checkout_Fields_For_Brazil" ) ) $available = false;

		return $available;
	}

	/**
	 * Admin page.
	 */
	public function admin_options()
	{
		$suffix = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";
		$path   = "assets/js/admin/admin{$suffix}.js";

		$dir    = plugin_dir_path( __FILE__ );
		$url    = plugins_url( $path, $dir );

		wp_enqueue_script( "wc_pagare-gateway_admin", $url, [ "jquery" ], WC_PAGARE_VERSION, true );

		include dirname( __FILE__ ) . "/admin/views/html-admin-page.php";
	}

	/**
	 * Payment fields.
	 */
	public function payment_fields()
	{
		wp_enqueue_script( "wc-credit-card-form" );

		$description = $this->get_description();

		if ( $this->is_homol === "yes" ) $description .= __( " - SANDBOX ativado.", $this->domain );

		if ( $description ) echo wpautop( wptexturize( trim( $description ) ) );

		wc_get_template(
			"checkout-form.php",
			[
				"cart_total"  => $this->get_order_total(),
				"cc"          => $this->cc,
				"cc_type"     => $this->cc_type,
				"installment" => $this->installment,
				"pix"         => $this->pix,
				"ticket"      => $this->ticket,
				"flag"        => plugins_url( "assets/images/brazilian-flag.png", plugin_dir_path( __FILE__ ) ),
				"icon_cc"     => plugins_url( "assets/images/icon-cc.png",        plugin_dir_path( __FILE__ ) ),
				"icon_pix"    => plugins_url( "assets/images/icon-pix.png",       plugin_dir_path( __FILE__ ) ),
				"icon_ticket" => plugins_url( "assets/images/icon-ticket.png",    plugin_dir_path( __FILE__ ) ),
			],
			"",
			WC_Pagare::get_templates_path()
		);
	}

	/**
	 * Checkout scripts.
	 */
	public function checkout_scripts()
	{
		if ( is_checkout() && $this->is_available() )
		{
			if ( ! get_query_var( "order-received" ) )
			{
				$suffix = defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";
				wp_enqueue_style(  "pagare-gateway-checkout", plugins_url( "assets/css/frontend/pgr-checkout{$suffix}.css", plugin_dir_path( __FILE__ ) ), [], WC_PAGARE_VERSION );
				wp_enqueue_script( "pagare-gateway-checkout", plugins_url( "assets/js/frontend/pgr-checkout{$suffix}.js",   plugin_dir_path( __FILE__ ) ), [ "json2", "jquery", "woocommerce-extra-checkout-fields-for-brazil-front", "jquery-mask" ], WC_PAGARE_VERSION, true );

				$user = wp_get_current_user();
				$url  = get_rest_url( null, "pagare/v1" );

				wp_localize_script(
					"pagare-gateway-checkout",
					"wc_rpg_params",
					[
						"user_id"  => $user->ID,
						"base_url" => $url,
						"messages" => [
							"session_error"      => __( "Ocorreu um error. Por favor, atualize a página.",                         $this->domain ),

							"unsupported_brand"  => __( "Bandeira não suportada.",                                                 $this->domain ),
							"invalid_card"       => __( "O número do cartão de crédito é inválido.",                               $this->domain ),
							"invalid_expiry"     => __( "A data de expiração é inválida, por favor, utilize o formato MM / AAAA.", $this->domain ),
							"invalid_cvv"        => __( "O código do cartão é inválido.",                                          $this->domain ),

							"invalid_holder"     => __( "Informe o nome do titular do cartão.",                                    $this->domain ),
							"invalid_h_cpf"      => __( "Informe o CPF/CNPJ do titular do cartão.",                                $this->domain ),
							"invalid_h_phone"    => __( "Informe o telefone do titular do cartão.",                                $this->domain ),

							"expired_date"       => __( "Por favor, verifique a data de expiração e utilize o formato MM / AAAA.", $this->domain ),

							"empty_installments" => __( "Selecione o número de parcelas.",                                         $this->domain ),
							"interest_free"      => __( "sem juros",                                                               $this->domain ),

							"general_error"      => __( "Não foi possível processar os dados do seu cartão de crédito, por favor, tente novamente ou entre em contato para receber ajuda.", $this->domain ),
						]
					]
				);
			}
		}
	}

	/**
	 * Process the payment
	 * Function called by WooCommerce to process the payment
	 *
	 * @param  int $order_id
	 * @return array
	 */
	public function process_payment( $order_id )
	{
		$order    = wc_get_order( $order_id );

		$method   = WC()->checkout()->get_value( "pagare_payment_method" );

		$data     = [];

		$func     = "process_data_{$method}";
		$data     = $this->$func( $order );

		switch ( $method )
		{
			case "cc":
				$data = $this->process_data_cc( $order );
			break;

			case "pix":
				$data = $this->process_data_pix( $order );
			break;

			case "ticket":
				$data = $this->process_data_ticket( $order );
			break;
		}

		$response = $this->api->do_payment( $method, $data );

		if ( !$response[ "success" ] )
		{
			wc_add_notice( $response[ "message" ], "error" );

			return array(
				"result"   => "fail",
				"redirect" => ""
			);
		}

		if ( $method == "cc"     && isset( $response[ "data" ][ "capture"   ] ) && $response[ "data" ][ "capture" ] == "CAPTURADO" )
		{
			$data = [
				"type"      => $method,
				"paymentId" => $response[ "data" ][ "paymentId" ],
			];

			$order->update_meta_data( "_pagare_code", $data );
			$order->update_status( "processing", __( "Pagare: Pagamento aprovado", $this->domain ) );
		}

		if ( $method == "pix"    && isset( $response[ "data" ][ "paymentId" ] ) && isset( $response[ "data" ][ "qrCode" ] ) )
		{
			$data = [
				"type"      => $method,
				"qrcode"    => $response[ "data" ][ "qrCode"    ],
				"paymentId" => $response[ "data" ][ "paymentId" ],
			];

			// Mark as on-hold (we're awaiting the payment)
			$order->update_meta_data( "_pagare_code", $data );
			$order->update_status( "on-hold", __( "Pagare: Aguardando pagamento", $this->domain ) );
		}

		if ( $method == "ticket" && isset( $response[ "data" ] ) )
		{
			$ticket = reset( $response[ "data" ] );

			if ( isset( $ticket[ "paymentId" ] ) && isset( $ticket[ "barcode" ] ) )
			{
				$data = [
					"type"           => $method,
					"barcode"        => $ticket[ "barcode"       ],
					"digitable_line" => $ticket[ "digitableLine" ],
					"paymentId"      => $ticket[ "paymentId"     ],
				];

				// Mark as on-hold (we're awaiting the payment)
				$order->update_meta_data( "_pagare_code", $data );
				$order->update_status( "on-hold", __( "Pagare: Aguardando pagamento", $this->domain ) );
			}
		}

		$order->save();

		// Remove cart.
		WC()->cart->empty_cart();

		// Reduce stock for billets.
		if ( function_exists( "wc_reduce_stock_levels" ) ) wc_reduce_stock_levels( $order_id );

		return [
			"result"   => "success",
			"redirect" => $this->get_return_url( $order ),
		];
	}

	/**
	 * Processes order and checkout data for payment via credit card
	 *
	 * @param object $order
	 * @return array
	 */
	private function process_data_cc( $order )
	{
		$holder      = WC()->checkout()->get_value( "pagare_card_holder_name" );
		$name        = explode( " ", $holder );
		$firstName   = reset( $name );
		$lastName    = ( count( $name ) > 1 ) ? end( $name ) : "";

		$email       = WC()->checkout()->get_value( "billing_email" );

		$isCnpj      = WC()->checkout()->get_value( "pagare_legal_person_field" );

		$doc         = ( $isCnpj == "on" ) ? "pagare_card_holder_cnpj" : "pagare_card_holder_cpf";

		$cpf         = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( $doc ) );
		$phone       = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "pagare_card_holder_phone" ) );

		$data        = [
			"user"        => wp_get_current_user(),

			"total"       => $order->get_total(),
			"order_id"    => $order->ID,

			"holder"      => $holder,
			"first_name"  => $firstName,
			"last_name"   => $lastName,

			"cpf"         => $cpf,
			"email"       => $email,
			"phone"       => $phone,

			"street"      => WC()->checkout()->get_value( "billing_address_1"         ),
			"complement"  => WC()->checkout()->get_value( "billing_address_2"         ),
			"uf"          => WC()->checkout()->get_value( "billing_state"             ),
			"zipcode"     => WC()->checkout()->get_value( "billing_postcode"          ),

			"installment" => WC()->checkout()->get_value( "pagare_card_installment" ),
			"card_number" => WC()->checkout()->get_value( "pagare_card_number"      ),
			"cvc"         => WC()->checkout()->get_value( "pagare_card_cvc"         ),
			"month"       => WC()->checkout()->get_value( "pagare_card_exp_month"   ),
			"year"        => WC()->checkout()->get_value( "pagare_card_exp_year"    ),
			"brand"       => WC()->checkout()->get_value( "pagare_card_brand"       ),
		];

		return $data;
	}

	/**
	 * Processes order and checkout data for payment via pix
	 *
	 * @param object $order
	 * @return array
	 */
	private function process_data_pix( $order )
	{
		$cpf         = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_cpf" ) );
		$firstName   = WC()->checkout()->get_value( "billing_first_name" );
		$lastName    = WC()->checkout()->get_value( "billing_last_name"  );
		$name        = "{$firstName} {$lastName}";

		$data        = [
			"total"       => $order->get_total(),
			"order_id"    => $order->ID,
			"name"        => $name,
			"cpf"         => $cpf,
		];

		return $data;
	}

	/**
	 * Processes order and checkout data for payment via ticket
	 *
	 * @param object $order
	 * @return array
	 */
	private function process_data_ticket( $order )
	{
		$cpf         = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_cpf" ) );
		$firstName   = WC()->checkout()->get_value( "billing_first_name" );
		$lastName    = WC()->checkout()->get_value( "billing_last_name"  );
		$name        = "{$firstName} {$lastName}";

		$fullPhone   = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_phone" ) );
		$ddd         = substr( $fullPhone, 0, 2 );
		$phone       = substr( $fullPhone, 2 );

		$data        = [
			"total"        => $order->get_total(),
			"order_id"     => $order->ID,
			"name"         => $name,
			"cpf"          => $cpf,
			"ddd"          => $ddd,
			"phone"        => $phone,
			"street"       => WC()->checkout()->get_value( "billing_address_1"    ),
			"neighborhood" => WC()->checkout()->get_value( "billing_neighborhood" ),
			"city"         => WC()->checkout()->get_value( "billing_city"         ),
			"uf"           => WC()->checkout()->get_value( "billing_state"        ),
			"zipcode"      => WC()->checkout()->get_value( "billing_postcode"     ),
			"email"        => WC()->checkout()->get_value( "billing_email"        ),
		];

		return $data;
	}

	/**
	 * Thank You page message.
	 *
	 * @param int $order_id
	 */
	public function thankyou_page( $order_id )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != $this->id ) return;

		$code  = $order->get_meta( "_pagare_code" );

		// cc
		if ( !$code ) return;

		// only pix/ticket
		return $this->render_code( $code, $order_id );
	}

	/**
	 * Order Page message.
	 *
	 * @param object $order
	 */
	public function order_page( $order )
	{
		if ( $order->get_payment_method() != $this->id ) return;

		$code = $order->get_meta( "_pagare_code" );

		// cc
		if ( !$code ) return;

		// only pix/ticket
		return $this->render_code( $code, $order->get_id() );
	}

	/**
	 * Render Pix code on thnak_you page.
	 *
	 * @param string $code
	 * @param int $order_id
	 */
	public function render_code( $data, $order_id )
	{
		if ( $data[ "type" ] == "pix" )
		{
			$link = $data[ "qrcode" ];
			$code = $this->generate_code( $link, "pix" );
			$msg  = $this->pix_message;
		}
		else
		{
			$bc   = $data[ "barcode"        ];
			$link = $data[ "digitable_line" ];
			$code = $this->generate_code( $bc, "ticket" );
			$msg  = $this->ticket_message;
		}

		if ( !empty( $link ) )
		{
			?>
			<div class="rppix-container" style="text-align: center;margin: 20px 0">
				<div class="rppix-instructions">
					<?php echo $msg; ?>
				</div>
				<input type="hidden" value="<?php echo $link; ?>" id="copiar">
				<img style="cursor:pointer; display:initial;" class="rppix-img-copy-code" onclick="copyCode()" ]
					src="<?php echo $code; ?>" alt="QR Code" />
				<br>
				<p class="rppix-p" style="font-size:14px; margin-bottom:0; word-break:break-all;" ><?php echo $link; ?></p>
				<br>
				<button class="button rppix-button-copy-code" style="margin-bottom: 20px;margin-left: auto;margin-right: auto;" onclick="copyCode()">
					<?php echo __( "Clique aqui para copiar o código acima", $this->domain ); ?>
				</button>
				<br>
				<div class="rppix-response-output inactive" style="margin:2em 0.5em 1em; padding:0.2em 1em; border:2px solid #46b450; display:none;" aria-hidden="true" >
					<?php echo __( "O código foi copiado para a área de transferência.", $this->domain ); ?>
				</div>
				<?php
					if ( $this->pix_whatsapp || $this->pix_telegram || $this->pix_email )
					{
						echo "<br>" . __( "<span class='rppix-explain'>Você pode compartilhar conosco o comprovante via: </span>", $this->domain );

						if ( $this->pix_whatsapp ) echo " <a class='rppix-whatsapp' style='margin-right:15px;' target='_blank' href='https://wa.me/{$this->pix_whatsapp}?text=Segue%20meu%20comprovante%20para%20o%20pedido%20{$order_id}' >WhatsApp </a>";
						if ( $this->pix_telegram ) echo " <a class='rppix-telegram' style='margin-right:15px;' target='_blank' href='https://t.me/{$this->pix_telegram}?text=Segue%20meu%20comprovante%20para%20o%20pedido%20{$order_id}' >Telegram </a>";
						if ( $this->pix_email    ) echo " <a class='rppix-email'    style='margin-right:15px;' target='_blank' href='mailto:{$this->pix_email}?subject=Comprovante%20pedido%20#{$order_id}&body=Segue%20meu%20comprovante%20anexo%20para%20o%20pedido%20#{$order_id}' >Email</a>";
					}
				?>
			</div>
			<script>
				function copyCode()
				{
					let copyText = document.getElementById( "copiar" );
					copyText.type = "text";
					copyText.select();
					copyText.setSelectionRange( 0, 99999 );
					document.execCommand( "copy" );
					copyText.type = "hidden";

					if ( jQuery( "div.rppix-response-output" ) )
						jQuery( "div.rppix-response-output" ).show();
					else
						alert( "O código foi copiado para a área de transferência." );

					return false;
				}
			</script>
			<?php
		}
	}

	/**
	 * Renders the QRCode/BarCode image
	 *
     * @param string $data
     * @param string $type
	 * @return string
	 */
	public function generate_code( $data, $type )
	{
		if ( $type == "pix" )
		{
			include dirname( __FILE__ ) . "/../vendor/php-qrcode/qrcode.php";

			$generator = new ICPFW_Generate_QRCode( $data, [ "s" => null ] );
			$image     = $generator->render_image();

			ob_start();
			imagejpeg( $image );

			$contents  = ob_get_contents();

			ob_end_clean();
			imagedestroy( $image );
		}
		else
		{
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/Barcode.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/BarcodeBar.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/Types/TypeInterface.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/Types/TypeCode128.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/BarcodeGenerator.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/BarcodeGeneratorPNG.php";
			include dirname( __FILE__ ) . "/../vendor/picqer/php-barcode-generator/src/BarcodeGeneratorJPG.php";

			$generator = new Picqer\Barcode\BarcodeGeneratorJPG();
			$contents  = $generator->getBarcode( $data, $generator::TYPE_CODE_128, 2, 60 );
		}

        $img_data = "data:image/jpg;base64," . base64_encode( $contents );

        return $img_data;
	}

	/**
	 * Add content to the WC emails.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text, $email )
	{
		if ( $order->get_payment_method() === $this->id && get_class( $email ) === "WC_Email_Customer_On_Hold_Order" )
		{
			$code = $order->get_meta( "_pagare_code" );

			if ( $code[ "type" ] == "pix" )
			{
				$image = $this->generate_code( $code[ "qrcode" ], "pix" );
				$data  = [
					"link"    => $code[ "qrcode" ],
					"image"   => $image,
					"message" => $this->pix_message,
				];
			}
			else
			{
				$image = $this->generate_code( $code[ "barcode" ], "ticket" );
				$data  = [
					"link"    => $code[ "barcode" ],
					"image"   => $image,
					"message" => $this->ticket_message,
				];
			}

			wc_get_template(
				"email-on-hold.php",
				$data,
				"",
				WC_Pagare::get_templates_path()
			);
		}
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_view()
	{
		$date      = date( "Y-m-d" );
		$esc_attr  = esc_attr( $this->id );
		$sanitize  = sanitize_file_name( wp_hash( $this->id ) );
		$admin_url = "admin.php?page=wc-status&tab=logs&log_file=$esc_attr-$date-$sanitize.log";
		$link      = esc_url( admin_url( $admin_url ) );
		$text      = __( "System Status &gt; Logs", $this->domain );

		return "<a href='$link' target='_blank' >$text</a>";
	}
}
