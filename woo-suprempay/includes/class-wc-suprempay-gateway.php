<?php
/**
 * Gateway class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Classes/Gateway
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Gateway
 */
class WC_SupremPay_Gateway extends WC_Payment_Gateway
{
	/**
	 * Instance of this class
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Plugin domain
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $domain;

	/**
	 * Gateway enviroment
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $is_homol;

	/**
	 * Token user HOMOL
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $token_user_hml;

	/**
	 * Token user PROD
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $token_user;

	/**
	 * Token integration HOMOL
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $token_integr_hml;

	/**
	 * Token integration PROD
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $token_integr;

	/**
	 * PIX option
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $pix;

	/**
	 * PIX due date
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $pix_due_date;

	/**
	 * PIX message
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $pix_message;

	/**
	 * Ticket option
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $ticket;

	/**
	 * Ticket due date
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $ticket_due_date;

	/**
	 * Ticket message
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $ticket_message;

	/**
	 * Transfer option
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $transfer;

	/**
	 * CC title
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $cc;

	/**
	 * CC type
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $cc_type;

	/**
	 * Installment
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $installment;

	/**
	 * Invoice prefix
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $invoice_prefix;

	/**
	 * Debug
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $debug;

	/**
	 *
	 * @var 	string
	 * @since 	1.0.0
	 */
	protected $cc_hide_cpf_field;

	/**
	 * Route base
	 *
	 * @var 	WC_Logger
	 * @since 	1.0.0
	 */
	protected $log;

	/**
	 * Route base
	 *
	 * @var 	WC_SupremPay_API
	 * @since 	1.0.0
	 */
	protected $api;

	/**
	 * Constructor for the gateway
	 *
	 * @since 	1.0.0
	 */
	public function __construct()
    {
		$this->domain             = WC_SUPREMPAY_DOMAIN;
		$this->id                 = WC_SUPREMPAY_ID;
		$this->icon               = apply_filters( "woocommerce_suprempay_icon", plugins_url( "assets/images/suprempay-icon-25.png", plugin_dir_path( __FILE__ ) ) );

		$this->method_title       = __( "SupremPay",                                                                     $this->domain );
		$this->method_description = __( "Aceite pagamentos por boleto, cartão de crédito e pix utilizando o SupremPay.", $this->domain );
		$this->order_button_text  = __( "Realizar pagamento",                                                            $this->domain );

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

        // Define user set variables.
        $this->title            = $this->get_option( "title"                );
        $this->description      = $this->get_option( "description"          );

        $this->is_homol         = $this->get_option( "is_homol", "yes"      );
        $this->token_user_hml   = $this->get_option( "token_user_hml"       );
        $this->token_user       = $this->get_option( "token_user"           );
        $this->token_integr_hml = $this->get_option( "token_integr_hml"     );
        $this->token_integr     = $this->get_option( "token_integr"         );

		// TODO habilitar credit card
		// // Card
        // $this->cc               = $this->get_option( "cc"                   );
        // $this->cc_type          = $this->get_option( "cc_type", "avista"    );
        // $this->installment      = $this->get_option( "installment", "1"     );

		// Pix / ticket / transfer configs
		$this->pix              = $this->get_option( "pix"                  );
		$this->pix_due_date     = $this->get_option( "pix_due_date", "15"   );
		$this->ticket           = $this->get_option( "ticket"               );
		$this->ticket_due_date  = $this->get_option( "ticket_due_date", "2" );
		$this->transfer         = $this->get_option( "transfer"             );

		// Pix and ticket messages
		$this->pix_message      = $this->get_option( "pix_message",    "Utilize o seu aplicativo favorito do Pix para ler o QRCode ou copiar o código abaixo e efetuar o pagamento."    );
		$this->ticket_message   = $this->get_option( "ticket_message", "Utilize o aplicativo do seu banco para ler o código de barras ou copiar o código abaixo e efetuar o pagamento." );

		$this->invoice_prefix   = $this->get_option( "invoice_prefix",  "WC-" );
        $this->debug            = $this->get_option( "debug",           "yes" );

        // Active logs.
        if ( $this->debug === "yes" ) $this->log = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		// Set the API.
		$this->api = new WC_SupremPay_API( $this );

		// Main actions and Transparent checkout actions.
        $this->init_actions();
	}

	/**
	 * Return an instance of this class
	 *
	 * @since 	1.0.0
	 * @return 	object 	A single instance of this class.
	 */
	public static function init()
    {
		if ( null === self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Get enviroment
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_is_homol()
	{
		return $this->is_homol;
	}

	/**
	 * Get CC Type
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_cc_type()
	{
		return $this->cc_type;
	}

	/**
	 * Get PIX due date
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_pix_due_date()
	{
		return $this->pix_due_date;
	}

	/**
	 * Get Ticket due date
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_ticket_due_date()
	{
		return $this->ticket_due_date;
	}

	/**
	 * Get invocice prefix
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_invoice_prefix()
	{
		return $this->invoice_prefix;
	}

	/**
	 * Get debug
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_debug()
	{
		return $this->debug;
	}

	/**
	 * Get log
	 *
	 * @since 	1.0.0
	 * @return 	WC_Logger
	 */
	public function get_log()
	{
		return $this->log;
	}

	/**
	 * Get token-user
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_token_user()
	{
		return ( $this->is_homol === "yes" ) ? $this->token_user_hml   : $this->token_user;
	}

	/**
	 * Get token-integration
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	public function get_token_integration()
	{
		return ( $this->is_homol === "yes" ) ? $this->token_integr_hml : $this->token_integr;
	}

	/**
	 * Get api
	 *
	 * @since 	1.0.0
	 * @return 	WC_SupremPay_API
	 */
	public function get_api()
	{
		return $this->api;
	}

	/**
	 * Returns a value indicating the the Gateway is available or not.
	 * It's called automatically by WooCommerce before allowing
	 * customers to use the gateway for payment.
	 *
	 * @since 	1.0.0
	 * @return 	bool
	 */
	public function is_available()
	{
		// Test if is valid for use.
		$available = $this->get_option( "enabled" ) === "yes" &&
					 $this->get_token_user()        !== ""    &&
					 $this->get_token_integration() !== ""    &&
					 $this->using_supported_currency()        &&
					 $this->is_ecfb_active();

		if ( !class_exists( "Extra_Checkout_Fields_For_Brazil" ) ) $available = false;

		return $available;
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function init_form_fields()
	{
		$this->form_fields = [

			// Enable/Disable SupremPay Gateway
			"enabled"          => [
				"type"        => "checkbox",
				"title"       => __( "Ativar / Desativar",                      $this->domain ),
				"label"       => __( "Ativar SupremPay",                        $this->domain ),
				"default"     => "no",
			],

			"title"            => [
				"type"        => "text",
				"title"       => __( "Título",                                  $this->domain ),
				"description" => __( "Controla o título que o usuário vê
									  durante o checkout.",                     $this->domain ),
				"default"     => __( "SupremPay",                               $this->domain ),
				// "desc_tip"    => true,
			],
			"description"      => [
				"type"        => "textarea",
				"title"       => __( "Descrição",                               $this->domain ),
				"description" => __( "Controla a descrição que o usuário vê
									  durante o checkout.",                     $this->domain ),
				"default"     => __( "Pague com a SupremPay",                   $this->domain ),
				// "desc_tip"    => true,
			],

			// integração
			"integration"      => [
				"title"       => __( "Integração",                              $this->domain ),
				"type"        => "title",
				"description" => "",
			],
			"is_homol"         => [
				"type"        => "checkbox",
				"title"       => __( "API sandbox",                             $this->domain ),
				"label"       => __( "Ativar API sandbox (homologação)",        $this->domain ),
				"default"     => "yes",
			],
			"token_user_hml"   => [
				"type"        => "password",
				"title"       => __( "Chave de usuário (sandbox)",              $this->domain ),
				"description" => __( "Entre com sua chave de autenticação obtida
									  no painel do SupremCash (sandbox).",      $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],
            "token_user"       => [
				"type"        => "password",
				"title"       => __( "Chave de usuário (produção)",             $this->domain ),
				"description" => __( "Entre com sua chave de autenticação obtida
									  no painel do SupremCash (produção).",     $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],
			"token_integr_hml" => [
				"type"        => "password",
				"title"       => __( "Chave de integração (sandbox)",           $this->domain ),
				"description" => __( "Entre com sua chave de autenticação obtida
									  com o suporte do SupremCash (sandbox).",  $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],
            "token_integr"     => [
				"type"        => "password",
				"title"       => __( "Chave de integração (produção)",          $this->domain ),
				"description" => __( "Entre com sua chave de autenticação obtida
									  com o suporte do SupremCash (produção).", $this->domain ),
				"desc_tip"    => true,
				"default"     => "",
			],

			// TODO habilitar credit card
			// // card
			// "cc_title"         => [
			// 	"title"       => __( "Cartão de crédito",                                                 $this->domain ),
			// 	"type"        => "title",
			// 	"description" => "",
			// ],
			// "cc"               => [
			// 	"type"    => "checkbox",
			// 	"title"   => __( "Cartão de Crédito",                                                     $this->domain ),
			// 	"label"   => __( "Ativar cartão de crédito no checkout",                                  $this->domain ),
			// 	"default" => "yes",
            // ],
			// "cc_type"          => [
			// 	"type"        => "select",
			// 	"title"       => __( "Tipo de pagamento",                                                 $this->domain ),
			// 	"description" => __( "Selecione \"parcelado\" para oferecer a opção de
			// 						  parcelamento sem juros no cartão de crédito para o cliente.",       $this->domain ),
			// 	"desc_tip"    => true,
			// 	"default"     => "avista",
			// 	"class"       => "wc-enhanced-select",
			// 	"options"     => [
			// 		"avista"    => __(  "À vista",                                                        $this->domain ),
			// 		"parcelado" => __(  "Parcelado",                                                      $this->domain ),
			// 	],
            // ],
			// "installment"      => [
			// 	"type"        => "number",
			// 	"title"       => __( "Número de parcelas",                                                $this->domain ),
			// 	"description" => __( "Selecione o número máximo de parcelas disponíveis para o cliente.
			// 						  Parcela mínima de R$ 5,00.", $this->domain ),
			// 	"desc_tip"    => true,
			// 	"default"     => 1,
			// 	"custom_attributes" => [
			// 		"maxlength"   => 2,
			// 		"min"         => 1,
			// 		"max"         => 12,
			// 	],
            // ],

			// pix
			"pix_title"        => [
				"title"       => __( "PIX",                                     $this->domain ),
				"type"        => "title",
				"description" => "",
			],
            "pix"              => [
                "type"    => "checkbox",
                "title"   => __( "Pagamento por PIX",                           $this->domain ),
                "label"   => __( "Ativar PIX no checkout",                      $this->domain ),
                "default" => "yes",
            ],
			"pix_due_date"     => [
				"type"        => "number",
				"title"       => __( "Validade do QRCode do PIX",               $this->domain ),
				"description" => __( "Informe qual o prazo de validade do
									  QRCode do PIX, em minutos.<br>Mínimo 5.", $this->domain ),
				"default"     => 2,
				"custom_attributes" => [
					"maxlength" => 3,
					"min"       => 5,
				],
            ],
			"pix_message"      => [
				"type"        => "text",
				"title"       => __( "Mensagem PIX",                            $this->domain ),
				"description" => __( "Controla a mensagem de instrução para
									  uso do QRCode que o usuário vê na
									  finalização do pedido.",                  $this->domain ),
				"default"     => "Utilize o seu aplicativo favorito do Pix para ler o QRCode ou copiar o código abaixo e efetuar o pagamento.",
				// "desc_tip"    => true,
			],

			// boleto
			"ticket_title"     => [
				"title"       => __( "Boleto",                                  $this->domain ),
				"type"        => "title",
				"description" => "",
			],
			"ticket"           => [
				"type"    => "checkbox",
                "title"   => __( "Pagamento por boleto bancário",               $this->domain ),
				"label"   => __( "Ativar boleto bancário no checkout",          $this->domain ),
				"default" => "yes",
            ],
			"ticket_due_date"  => [
				"type"        => "number",
				"title"       => __( "Validade do boleto",                      $this->domain ),
				"description" => __( "Informe qual o prazo de validade do
									  boleto bancário, em dias.<br>
									  Mínimo 1, máximo 30.",                    $this->domain ),
				"default"     => 2,
				"custom_attributes" => [
					"maxlength" => 2,
					"min"       => 1,
					"max"       => 30,
				],
            ],
			"ticket_message"   => [
				"type"        => "text",
				"title"       => __( "Mensagem boleto bancário",                $this->domain ),
				"description" => __( "Controla a mensagem de instrução para
									  uso do código de barras que o usuário
									  vê na finalização do pedido.",            $this->domain ),
				"default"     => "Utilize o aplicativo do seu banco para ler o código de barras ou copiar o código abaixo e efetuar o pagamento.",
				// "desc_tip"    => true,
			],

			// transferencia bancaria supremcash
			"transfer_title"   => [
				"title"       => __( "Transferência SupremCash",                $this->domain ),
				"type"        => "title",
				"description" => "",
			],
			"transfer"         => [
				"type"    => "checkbox",
                "title"   => __( "Pagamento por transferência
								  entre contas SupremCash",                     $this->domain ),
				"label"   => __( "Ativar transferência SupremCash no checkout", $this->domain ),
				"default" => "yes",
            ],

			"behavior"        => [
				"title"       => __( "Comportamento da integração",             $this->domain ),
				"type"        => "title",
				"description" => "",
            ],
			"invoice_prefix"  => [
				"type"        => "text",
				"title"       => __( "Prefixo do pedido",                        $this->domain ),
				"description" => __( "Por favor informe um prefixo para utilizar
									  com os números de pedidos.",               $this->domain ),
				"desc_tip"    => true,
				"default"     => "WC-",
            ],

            "debug"           => [
				"type"        => "checkbox",
				"title"       => __( "Debug Log",                                $this->domain ),
				"label"       => __( "Ativar Logger",                            $this->domain ),
				"description" => sprintf( __( "Registrar log de eventos, %s", $this->domain ), $this->get_log_view() ),
				"default"     => "yes",
			],
		];
	}

    /**
     * Initialise main actions
	 *
	 * @since 	1.0.0
	 * @return 	void
     */
	public function init_actions()
	{
		add_action( "wp_enqueue_scripts",                                       [ $this, "checkout_scripts"      ] );
		add_action( "woocommerce_update_options_payment_gateways_" . $this->id, [ $this, "process_admin_options" ] );
		add_action( "woocommerce_thankyou_"                        . $this->id, [ $this, "thankyou_page"         ] );
		add_action( "woocommerce_email_before_order_table",                     [ $this, "email_instructions"    ], 99, 4 );

		if ( is_account_page() ) add_action( "woocommerce_order_details_after_order_table", [ $this, "order_page" ], 99 );
    }

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones
	 *
	 * @since 	1.0.0
	 * @return 	bool
	 */
	public function using_supported_currency()
    {
		return get_woocommerce_currency() === "BRL";
	}

	/**
	 * Returns a bool that indicates if the plugin ECFB is active
	 *
	 * @since 	1.0.0
	 * @return 	bool
	 */
	public function is_ecfb_active()
	{
		return is_plugin_active( "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" );
	}

	/**
	 * Admin page
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function admin_options()
	{
		include dirname( WC_SUPREMPAY_PLUGIN_FILE ) . "/templates/admin/woo-settings.php";
	}

	/**
	 * Payment fields
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function payment_fields()
	{
		wp_enqueue_script( "wc-credit-card-form" );

		$description = $this->get_description();

		if ( $this->is_homol === "yes" ) $description .= __( " - SANDBOX ativado.", $this->domain );

		if ( $description ) echo wpautop( wptexturize( trim( $description ) ) );

		if ( $this->cc_type == "parcelado" && $this->installment > 1 )
		{
			$new_installment = $this->installment;

			foreach ( range( 1, $this->installment ) as $i )
			{
				$parcel = $this->get_order_total() / $i;

				if ( $parcel < 5 )
				{
					$new_installment = $i - 1;
					break;
				}
			}

			$this->installment = $new_installment;
		}

		wc_get_template(
			"checkout-form.php",
			[
				// TODO remover hard code
				"cc"            => "no",

				"cart_total"    => $this->get_order_total(),
				// "cc"            => $this->cc          ?? "no",
				"cc_type"       => $this->cc_type     ?? "avista",
				"installment"   => $this->installment ?? "1",
				"pix"           => $this->pix         ?? "no",
				"ticket"        => $this->ticket      ?? "no",
				"transfer"      => $this->transfer    ?? "no",

				"flag"          => plugins_url( "assets/images/brazilian-flag.png", plugin_dir_path( __FILE__ ) ),
				"icon_cc"       => plugins_url( "assets/images/icon-cc.png",        plugin_dir_path( __FILE__ ) ),
				"icon_pix"      => plugins_url( "assets/images/icon-pix.png",       plugin_dir_path( __FILE__ ) ),
				"icon_ticket"   => plugins_url( "assets/images/icon-ticket.png",    plugin_dir_path( __FILE__ ) ),
				"icon_transfer" => plugins_url( "assets/images/icon-transfer.png",  plugin_dir_path( __FILE__ ) ),
			],
			"",
			WC_SupremPay::get_templates_path()
		);
	}

	/**
	 * Checkout scripts
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function checkout_scripts()
	{
		if ( is_checkout() && $this->is_available() )
		{
			if ( ! get_query_var( "order-received" ) )
			{
				wp_enqueue_script( "suprempay-gateway-library", $this->api->get_pagseguro_direct_payment_url(), [], WC_SUPREMPAY_VERSION, true );

				$suffix   = ""; // defined( "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";

				$css_path = "assets/css/frontend/sp-checkout{$suffix}.css";
				$css_ver  = date( "ymdHis", filemtime( plugin_dir_path( WC_SUPREMPAY_PLUGIN_FILE ) . $css_path ) );
				wp_enqueue_style( "suprempay-gateway-checkout",  plugins_url( $css_path, WC_SUPREMPAY_PLUGIN_FILE ), false, $css_ver, "all" );

				$js_path  = "assets/js/frontend/sp-checkout{$suffix}.js";
				$js_ver   = date( "ymdHis", filemtime( plugin_dir_path( WC_SUPREMPAY_PLUGIN_FILE ) . $js_path  ) );
				wp_enqueue_script( "suprempay-gateway-checkout", plugins_url( $js_path,  WC_SUPREMPAY_PLUGIN_FILE ), [ "json2", "jquery", "suprempay-gateway-library", "woocommerce-extra-checkout-fields-for-brazil-front", "jquery-mask" ], $js_ver, true );

				$user     = wp_get_current_user();
				$url      = get_rest_url( null, "suprempay/v1" );

				wp_localize_script(
					"suprempay-gateway-checkout",
					"wc_spg_params",
					[
						// TODO session_id não funciona
						"credit_card" => [
							"session_id" => $this->api->get_session_id(),
							// "enabled"    => $this->cc ?? "no",
							"enabled"    => "no",
						],
						"user_id"     => $user->ID,
						"base_url"    => $url,
						"messages"    => [
							"session_error"      => __( "Ocorreu um error. Por favor, atualize a página.",                         $this->domain ),

							"invalid_card"       => __( "O número do cartão de crédito é inválido.",                               $this->domain ),
							"invalid_expiry"     => __( "A data de expiração é inválida, por favor, utilize o formato MM / AAAA.", $this->domain ),
							"invalid_cvv"        => __( "O código do cartão é inválido.",                                          $this->domain ),

							"invalid_holder"     => __( "Informe o nome do titular do cartão.",                                    $this->domain ),
							"invalid_h_cpf"      => __( "Informe o CPF/CNPJ do titular do cartão.",                                $this->domain ),
							"invalid_h_phone"    => __( "Informe o telefone do titular do cartão.",                                $this->domain ),

							"expired_date"       => __( "Por favor, verifique a data de expiração e utilize o formato MM / AAAA.", $this->domain ),

							// "empty_installments" => __( "Selecione o número de parcelas.",                                         $this->domain ),
							// "interest_free"      => __( "sem juros",                                                               $this->domain ),

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
	 * @since 	1.0.0
	 * @param  	int 	$order_id
	 * @return 	array
	 */
	public function process_payment( $order_id )
	{
		$order    = wc_get_order( $order_id );

		$method   = WC()->checkout()->get_value( "suprempay_payment_method" );

		$data     = [];

		if ( $method == "cc"       ) $data = $this->process_data_cc( $order );
		if ( $method == "pix"      ) $data = $this->process_data_pix( $order );
		if ( $method == "ticket"   ) $data = $this->process_data_ticket( $order );
		if ( $method == "transfer" ) $data = $this->process_data_transfer( $order );

		$response = $this->api->do_payment( $method, $data );

		if ( !$response[ "success" ] )
		{
			$message = "SupremPay: " . $response[ "message" ];
			wc_add_notice( $message, "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		if ( !$response[ "data" ][ "success" ] )
		{
			$message = "SupremPay: " . $response[ "data" ][ "message" ];
			wc_add_notice( $message, "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		if ( $method == "cc"     && isset( $response[ "data" ][ "capture"   ] ) && $response[ "data" ][ "capture" ] == "CAPTURADO" )
		{
			$data = [
				"type" => $method,
				"data" => [ "payment_id" => $response[ "data" ][ "paymentId" ] ]
			];

			$order->update_meta_data( "_suprempay_code", $data );
			$order->update_status( "processing", __( "SupremPay: Pagamento aprovado", $this->domain ) );
		}

		if ( $method == "pix"    && isset( $response[ "data" ][ "info" ] ) )
		{
			$info = $response[ "data" ][ "info" ];

			$data = [
				"type" => $method,
				"link" => $info[ "pix_wallet" ],
				"code" => $info[ "pix_copy"   ],
				"data" => [
					"payment_id"          => $info[ "pix_path"            ],
					"transaction_id"      => $info[ "transaction_id"      ],
					"guid_user"           => $info[ "guid_user"           ],
					"guid_pix_collection" => $info[ "guid_pix_collection" ],
				]
			];

			$order->update_meta_data( "_suprempay_code", $data );
			$order->update_status( "on-hold", __( "SupremPay: Aguardando pagamento", $this->domain ) );
		}

		if ( $method == "ticket" && isset( $response[ "data" ] ) )
		{
			$info = $response[ "data" ];

			$data = [
				"type" => $method,
				"link" => $info[ "link"           ],
				"code" => $info[ "digitable_line" ],
				"data" => [
					"payment_id"     => $info[ "token_billet"   ],
					"digitable_line" => $info[ "digitable_line" ],
				]
			];

			$order->update_meta_data( "_suprempay_code", $data );
			$order->update_status( "on-hold", __( "SupremPay: Aguardando pagamento", $this->domain ) );
		}

		if ( $method == "transfer" && isset( $response[ "data" ] ) )
		{
			$data = [
				"type" => $method,
				"data" => [ "payment_id" => $data[ "data" ][ "token" ], ]
			];

			$order->update_meta_data( "_suprempay_code", $data );
			$order->update_status( "processing", __( "SupremPay: Pagamento aprovado", $this->domain ) );
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
	 * @since 	1.0.0
	 * @param 	object 	$order
	 * @return 	array
	 */
	private function process_data_cc( $order )
	{
		$holder      = WC()->checkout()->get_value( "suprempay_card_holder_name" );
		$name        = explode( " ", $holder );
		$firstName   = reset( $name );
		$lastName    = ( count( $name ) > 1 ) ? end( $name ) : "";

		$email       = WC()->checkout()->get_value( "billing_email" );

		$isCnpj      = WC()->checkout()->get_value( "suprempay_legal_person_field" );

		$doc         = ( $isCnpj == "on" ) ? "suprempay_card_holder_cnpj" : "suprempay_card_holder_cpf";

		$cpf         = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( $doc ) );
		$phone       = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "suprempay_card_holder_phone" ) );

		$data        = [
			"user"        => wp_get_current_user(),

			"total"       => $order->get_total(),
			"order_id"    => $order->get_id(),

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

			"installment" => WC()->checkout()->get_value( "suprempay_card_installment" ),
			"card_number" => WC()->checkout()->get_value( "suprempay_card_number"      ),
			"cvc"         => WC()->checkout()->get_value( "suprempay_card_cvc"         ),
			"month"       => WC()->checkout()->get_value( "suprempay_card_exp_month"   ),
			"year"        => WC()->checkout()->get_value( "suprempay_card_exp_year"    ),
			"brand"       => WC()->checkout()->get_value( "suprempay_card_brand"       ),
		];

		return $data;
	}

	/**
	 * Processes order and checkout data for payment via pix
	 *
	 * @since 	1.0.0
	 * @param 	object 	$order
	 * @return 	array
	 */
	private function process_data_pix( $order )
	{
		$email = WC()->checkout()->get_value( "billing_email"      );
		$first = WC()->checkout()->get_value( "billing_first_name" );
		$last  = WC()->checkout()->get_value( "billing_last_name"  );
		$name  = "{$first} {$last}";

		$cpf   = WC()->checkout()->get_value( "billing_cpf"  );
		$cnpj  = WC()->checkout()->get_value( "billing_cnpj" );

		$doc   = ( !empty( $cpf ) ) ? $cpf : $cnpj;
		$doc   = preg_replace( "/[^0-9]/", "", $doc );

		$data  = [
			"order"    => $order,
			"name"     => $name,
			"email"    => $email,
			"amount"   => $order->get_total(),
			"document" => $doc,
		];

		return $data;
	}

	/**
	 * Processes order and checkout data for payment via ticket
	 *
	 * @since 	1.0.0
	 * @param 	object 	$order
	 * @return 	array
	 */
	private function process_data_ticket( $order )
	{
		$document     = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_cpf" ) );
		$firstName    = WC()->checkout()->get_value( "billing_first_name" );
		$lastName     = WC()->checkout()->get_value( "billing_last_name"  );
		$name         = "{$firstName} {$lastName}";

		$fullPhone    = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_phone" ) );
		$area_code    = substr( $fullPhone, 0, 2 );
		$phone_number = substr( $fullPhone, 2 );

		$zip_code     = preg_replace( "/[^0-9]/", "", WC()->checkout()->get_value( "billing_postcode" ) );

		$data         = [
			"order"        => $order,
			"amount"       => $order->get_total(),

			"name"         => $name,
			"email"        => WC()->checkout()->get_value( "billing_email"        ),
			"document"     => $document,
			"area_code"    => $area_code,
			"phone_number" => $phone_number,
			"zip_code"     => $zip_code,

			"address"      => WC()->checkout()->get_value( "billing_address_1"    ),
			"district"     => WC()->checkout()->get_value( "billing_neighborhood" ),
			"city"         => WC()->checkout()->get_value( "billing_city"         ),
			"state"        => WC()->checkout()->get_value( "billing_state"        ),
		];

		return $data;
	}

	/**
	 * Processes order and checkout data for payment via internal transfer
	 *
	 * @since 	1.0.0
	 * @param 	object 	$order
	 * @return 	array
	 */
	private function process_data_transfer( $order )
	{
		$code      = WC()->checkout()->get_value( "suprempay_auth_code"  );
		$email     = WC()->checkout()->get_value( "suprempay_auth_email" );
		$firstName = WC()->checkout()->get_value( "billing_first_name"   );
		$lastName  = WC()->checkout()->get_value( "billing_last_name"    );
		$name      = "{$firstName} {$lastName}";

		$data      = [
			"order"  => $order,
			"amount" => $order->get_total(),
			"name"   => $name,
			"email"  => $email,
			"code"   => $code,
		];

		return $data;
	}

	/**
	 * Thank You page message
	 *
	 * @since 	1.0.0
	 * @param 	int 	$order_id
	 * @return 	void
	 */
	public function thankyou_page( $order_id )
	{
		$order = wc_get_order( $order_id );

		if ( $order->get_payment_method() != $this->id )
			return;

		$code  = $order->get_meta( "_suprempay_code" );

		// only pix/ticket
		if ( in_array( $code[ "type" ], [ "pix", "ticket" ] ) )
			return $this->render_code( $code );
	}

	/**
	 * Order Page message
	 *
	 * @since 	1.0.0
	 * @param 	object 	$order
	 * @return 	void
	 */
	public function order_page( $order )
	{
		if ( $order->get_payment_method() != $this->id )
			return;

		$code = $order->get_meta( "_suprempay_code" );

		// only pix/ticket
		if ( in_array( $code[ "type" ], [ "pix", "ticket" ] ) )
			return $this->render_code( $code );
	}

	/**
	 * Render Pix code on thank_you page
	 *
	 * @since 	1.0.0
	 * @param 	string 	$code
	 * @param 	int 	$order_id
	 * @return 	void
	 */
	public function render_code( $data )
	{
		$link = $data[ "link" ];
		$code = $data[ "code" ];

		$msg  = ( $data[ "type" ] == "pix" ) ? $this->pix_message : $this->ticket_message;

		if ( !empty( $link ) )
		{
			?>
			<div class="supremcash-pix-container" style="text-align: center;margin: 20px 0">
				<div class="supremcash-pix-instructions">
					<?php echo $msg; ?>
				</div>
				<input type="hidden" value="<?php echo $code; ?>" id="copiar" >

				<?php if ( $data[ "type" ] == "pix" ) { ?>
						<img style="cursor:pointer; display:initial; max-width:350px!important;" class="supremcash-pix-img-copy-code" onclick="copyCode()" src="<?php echo $link; ?>" alt="QR Code" />
				<?php }	else { ?>
						<a class="supremcash-pix-p" style="font-size:22px; margin-bottom:0;" href="<?php echo $link; ?>" target="_blank" >Acessar boleto</a>
				<?php } ?>
				<br>
				<p class="supremcash-pix-p" style="font-size:14px; margin-bottom:0; word-break:break-all;" ><?php echo $code; ?></p>
				<br>
				<button class="button supremcash-pix-button-copy-code" style="margin-bottom: 20px;margin-left: auto;margin-right: auto;" onclick="copyCode()">
					<?php echo __( "Clique aqui para copiar o código acima", $this->domain ); ?>
				</button>
				<br>
				<div class="supremcash-pix-response-output inactive" style="margin:2em 0.5em 1em; padding:0.2em 1em; border:2px solid #46b450; display:none;" aria-hidden="true" >
					<?php echo __( "O código foi copiado para a área de transferência.", $this->domain ); ?>
				</div>
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

					if ( jQuery( "div.supremcash-pix-response-output" ) )
						jQuery( "div.supremcash-pix-response-output" ).show();
					else
						alert( "O código foi copiado para a área de transferência." );

					return false;
				}
			</script>
			<?php
		}
	}

	/**
	 * Add content to the WC emails
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text, $email )
	{
		if ( $order->get_payment_method() === $this->id && get_class( $email ) === "WC_Email_Customer_On_Hold_Order" )
		{
			$code = $order->get_meta( "_suprempay_code" );

			$data = [
				"link"    => $code[ "link" ],
				"code"    => $code[ "code" ],
				"message" => ( $code[ "type" ] == "pix" ) ? $this->pix_message : $this->ticket_message,
			];

			wc_get_template(
				"email-on-hold.php",
				$data,
				"",
				WC_SupremPay::get_templates_path()
			);
		}
	}

	/**
	 * Get log
	 *
	 * @since 	1.0.0
	 * @return 	string
	 */
	protected function get_log_view()
	{
		$date      = date( "Y-m-d" );
		$esc_attr  = esc_attr( $this->id );
		$sanitize  = sanitize_file_name( wp_hash( $this->id ) );
		$admin_url = "admin.php?page=wc-status&tab=logs&log_file=$esc_attr-$date-$sanitize.log";
		$link      = esc_url( admin_url( $admin_url ) );
		$text      = __( "WooCommerce &gt; Status &gt; Logs", $this->domain );

		return "<a href='$link' target='_blank' >$text</a>";
	}
}
