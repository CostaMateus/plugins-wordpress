<?php
/**
 * Triibo Payment Card class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Payment_Card extends WC_Payment_Gateway_CC
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
	protected $service = null;

	/**
	 * Type of payment in installments, in cash or in installments.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $installment_type = null;

	/**
	 * Number of installments.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	protected $installments = null;

	/**
	 * Instance of Api Node.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Node
	 */
	protected $node = null;

	/**
	 * Instance of Triibo_Payments_Api.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Payments_Api
	 */
	protected $api = null;

	/**
	 * Debug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $debug = null;

	/**
	 * Logger.
	 *
	 * @since 1.0.0
	 *
	 * @var WC_Logger|null
	 */
	protected $log = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		self::$instance = $this;

        $this->id                 = Triibo_Payments::DOMAIN . "-card";
        $this->icon               = apply_filters(
			hook_name: "woocommerce_" . $this->id . "_icon",
			value    : plugins_url(
				path  : "assets/images/icon-cc.png",
				plugin: Triibo_Payments::FILE
			)
		);

        $this->method_title       = __( text: "Triibo Cartão de Crédito",                                  domain: $this->id );
        $this->method_description = __( text: "Pagamentos com cartão de crédito usando a Triibo Gateway.", domain: $this->id );
        $this->order_button_text  = __( text: "Efetuar pagamento",                                         domain: $this->id );

        $this->supports           = apply_filters( hook_name: $this->id . "_supports_array", value: [
            "default_credit_card_form",
            "products",
        ] );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title              = $this->get_option( key: "title"            );
        $this->description        = $this->get_option( key: "description"      );
        $this->service            = $this->get_option( key: "service"          );
        $this->installment_type   = $this->get_option( key: "installment_type" );
        $this->installments       = $this->get_option( key: "installments"     );
        $this->debug              = $this->get_option( key: "debug"            );

        $this->node               = new Triibo_Api_Node();
        $this->api                = new Triibo_Payments_Api( gateway: $this, node: $this->node );

        $this->init_actions_filters();
	}

	/**
	 * Get id.
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
	 * Get service.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_service() : string
	{
		return $this->service;
	}

	/**
	 * Get node.
	 *
	 * @since 1.0.0
	 *
	 * @return Triibo_Api_Node
	 */
	public function get_node() : Triibo_Api_Node
	{
		return $this->node;
	}

	/**
	 * Get api.
	 *
	 * @since 1.0.0
	 *
	 * @return Triibo_Payments_Api
	 */
	public function get_api() : Triibo_Payments_Api
	{
		return $this->api;
	}

	/**
	 * Get debug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_debug() : string
	{
		return $this->debug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @return self 	A single instance of this class
	 */
	public static function get_instance() : self
	{
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance )
			self::$instance = new self;

		return self::$instance;
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
		$admin_url = "admin.php?page=wc-status&tab=logs&log_file=$esc_attr-$date-$sanitize.log";
		$link      = esc_url( url: admin_url( path: $admin_url ) );
		$text      = __( text: "System Status &gt; Logs", domain: $this->id );

		return "<a href='$link' target='_blank' >$text</a>";
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
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function using_supported_currency() : bool
	{
		return "BRL" === get_woocommerce_currency();
	}

	/**
	 * Returns a value indicating the the Gateway is available or not.
	 * It's called automatically by WooCommerce before allowing
	 * customers to use the gateway for payment.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_available() : bool
	{
		$available = $this->get_option( key: "enabled" ) === "yes" &&
					 $this->node->status() && $this->using_supported_currency();

		if ( ! is_plugin_active( plugin: "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) )
			$available = false;

		return $available;
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
			"enabled"           => [
				"type"        => "checkbox",
				"title"       => __( text: "Ativar / Desativar",                                        domain: $this->id ),
				"label"       => __( text: "Ativar Triibo Cartão de Crédito",                           domain: $this->id ),
				"default"     => "no",
			],

			"title"             => [
				"type"        => "text",
				"title"       => __( text: "Título",                                                    domain: $this->id ),
				"description" => __( text: "Controla o título que o usuário vê durante o checkout.",    domain: $this->id ),
				"default"     => __( text: "Triibo Cartão de Crédito",                                  domain: $this->id ),
			],
			"description"       => [
				"type"        => "textarea",
				"title"       => __( text: "Descrição",                                                 domain: $this->id ),
				"description" => __( text: "Controla a descrição que o usuário vê durante o checkout.", domain: $this->id ),
				"default"     => __( text: "Pague com a Triibo Cartão",                                 domain: $this->id ),
			],

			"service"           => [
				"type"        => "select",
				"title"       => __( text: "Serviço de pagamento",                                      domain: $this->id ),
				"description" => __( text: "Escolha qual será o serviço de pagamento padrão.",          domain: $this->id ),
				"default"     => "asaas",
				"class"       => "wc-enhanced-select",
				"options"     => [
					"asaas" => __( text: "Asaas",                                                       domain: $this->id ),
					"cielo" => __( text: "Cielo",                                                       domain: $this->id ),
				],
			],

			"installment_type"  => [
				"type"        => "checkbox",
				"title"       => __( text: "Opção de parcelamento",                                     domain: $this->id ),
				"label"       => __( text: "Ativar opção de parcelamento no checkout",                  domain: $this->id ),
				"description" => __( text: "Ao desativar essa opção, o cliente só terá a
									  opção de pagamento à vista!",                                     domain: $this->id ),
				"desc_tip"    => true,
				"default"     => "yes",
            ],
			"installments"      => [
				"type"        => "select",
				"title"       => __( text: "Número de parcelas",                                        domain: $this->id ),
				"description" => __( text: "Selecione o número máximo de parcelas
									  disponíveis para o cliente.",                                     domain: $this->id ),
				"desc_tip"    => true,
				"default"     => 1,
				"class"       => "wc-enhanced-select",
				"options"     => [
					1  => __( text:  "1", domain: $this->id ),
					2  => __( text:  "2", domain: $this->id ),
					3  => __( text:  "3", domain: $this->id ),
					4  => __( text:  "4", domain: $this->id ),
					5  => __( text:  "5", domain: $this->id ),
					6  => __( text:  "6", domain: $this->id ),
					7  => __( text:  "7", domain: $this->id ),
					8  => __( text:  "8", domain: $this->id ),
					9  => __( text:  "9", domain: $this->id ),
					10 => __( text: "10", domain: $this->id ),
					11 => __( text: "11", domain: $this->id ),
					12 => __( text: "12", domain: $this->id ),
				],
            ],

			"debug"             => [
				"type"        => "checkbox",
				"title"       => __( text: "Debug Log",                                                 domain: $this->id ),
				"label"       => __( text: "Ativar logger",                                             domain: $this->id ),
				"description" => sprintf( __( text: "Registrar log de eventos, %s", domain: $this->id ), $this->get_log_view() ),
				"default"     => "yes",
			],

			"configs"           => [
				"type"        => "title",
				"title"       => __( text: "Chaves de API",                                             domain: $this->id ),
				"description" => sprintf( __( text: "Acessar configurações globais %s.", domain: $this->id ), $this->get_global_config() ),
			],
		];
	}

	/**
	 * Initialise Gateway Actions and Filters.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init_actions_filters() : void
	{
		add_action( hook_name: "wp_enqueue_scripts",                                       callback: [ $this, "checkout_scripts"      ] );
		add_action( hook_name: "woocommerce_update_options_payment_gateways_" . $this->id, callback: [ $this, "process_admin_options" ] );
		add_filter( hook_name: "woocommerce_credit_card_form_start",                       callback: [ $this, "form_fields_start"     ] );
		add_filter( hook_name: "woocommerce_credit_card_form_end",                         callback: [ $this, "form_fields_end"       ] );
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
		$suffix  = defined( constant_name: "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";
		$js_path = "assets/js/admin/script{$suffix}.js";
		$js_ver  = date( format: "ymdHis", timestamp: filemtime( filename: plugin_dir_path( file: Triibo_Payments::FILE ) . $js_path  ) );

		wp_enqueue_script(
			handle: "{$this->id}_admin",
			src   : plugins_url(
				path  : $js_path,
				plugin: Triibo_Payments::FILE
			),
			deps  : [ "json2", "jquery" ],
			ver   : $js_ver,
			args  : true );

		require_once ( dirname( path: Triibo_Payments::FILE ) . "/templates/admin/wc-settings.php" );
	}

	/**
	 * Checkout scripts.
	 *
	 * @return void
	 */
	public function checkout_scripts() : void
	{
		$load_scripts = false;

		if ( is_checkout() )         $load_scripts = true;
		if ( $this->is_available() ) $load_scripts = true;
		if ( false === $load_scripts ) return;

		$suffix       = defined( constant_name: "SCRIPT_DEBUG" ) && SCRIPT_DEBUG ? "" : ".min";

		$js_path      = "assets/js/frontend/card.script{$suffix}.js";
		$js_ver       = date( format: "ymdHis", timestamp: filemtime( filename: plugin_dir_path( file: Triibo_Payments::FILE ) . $js_path  ) );
		wp_enqueue_script( handle: "{$this->id}_checkout", src: plugins_url( path: $js_path,  plugin: Triibo_Payments::FILE ), deps: [ "json2", "jquery", "woocommerce-extra-checkout-fields-for-brazil-front", "jquery-mask" ], ver: $js_ver, args: true );

		$user         = wp_get_current_user();
		$token        = $this->api->validate_token( user_id: $user->ID );

		$url          = get_rest_url( blog_id: null, path: "triibo-payments/v1/cards/brand" );

		wp_localize_script(
			handle: "{$this->id}_checkout",
			object_name: "{$this->id}_params",
			l10n: [
				"id"            => $this->id,
				"user_id"       => $user->ID,
				"rest_url"      => $url,
				"invalid_token" => $token ? false : true,
				"installment"   => [
					"type"  => $this->installment_type == "yes" ? "parcelado" : "avista",
					"count" => $this->installments,
					"total" => $this->get_order_total(),
				],
				"messages"      => [
					"session_error"        => __( text: "Ocorreu um error. Por favor, atualize a página.",                         domain: $this->id ),
					"invalid_token"        => __( text: "Por favor, faça login pela Triibo para finalizar a compra.",              domain: $this->id ),
				],
				"card_messages" => [
					"invalid_holder_name"  => __( text: "Informe o nome do titular do cartão.",                                    domain: $this->id ),
					"invalid_holder_cpf"   => __( text: "Informe o CPF do titular do cartão.",                                     domain: $this->id ),
					"invalid_holder_phone" => __( text: "Informe o celular do titular do cartão.",                                 domain: $this->id ),
					"invalid_card"         => __( text: "O número do cartão de crédito é inválido.",                               domain: $this->id ),
					"invalid_expiry"       => __( text: "A data de expiração é inválida, por favor, utilize o formato MM / AA.",   domain: $this->id ),
					"invalid_cvc"          => __( text: "O código do cartão é inválido.",                                          domain: $this->id ),
					"expired_date"         => __( text: "Por favor, verifique a data de expiração e utilize o formato MM / AA.",   domain: $this->id ),
					"invalid_brand"        => __( text: "A bandeira do cartão é inválida.",                                        domain: $this->id ),
					"unsupported_brand"    => __( text: "Essa bandeira não é suportada pelo Triibo Cartão de Crédito atualmente.", domain: $this->id ),
					"unsupported_invalid"  => __( text: "Essa bandeira não é suportada ou o número do cartão é inválido.",         domain: $this->id ),
					"empty_installments"   => __( text: "Selecione o número de parcelas.",                                         domain: $this->id ),
					"interest_free"        => __( text: "sem juros",                                                               domain: $this->id ),
					"general_error"        => __( text: "Não foi possível processar os dados do seu cartão de crédito, por favor, tente novamente ou entre em contato para receber ajuda.", domain: $this->id ),
				]
			]
		);
	}

	/**
	 * Checkout form fields start.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id
	 *
	 * @return void
	 */
	public function form_fields_start( string $id ) : void
	{
		if ( $id != $this->id )
			return;

		$id = esc_attr( text: $this->id );

		echo "<p class='form-row form-row-wide' >
			<label for='{$id}-card-holder-name' >" . esc_html__( text: "Nome do titular", domain: $this->id ) . "&nbsp;<span class='required' >*</span></label>
			<input id='{$id}-card-holder-name' class='input-text' type='text' placeholder='" . esc_attr__( text: "Informe o nome do titular", domain: $this->id ) . "' " . $this->field_name( name: "card-holder-name" ) . " />
		</p>
		<p class='form-row form-row-wide' >
			<label for='{$id}-card-holder-cpf' >" . esc_html__( text: "CPF do titular", domain: $this->id ) . "&nbsp;<span class='required'>*</span></label>
			<input id='{$id}-card-holder-cpf' class='input-text' type='tel' placeholder='" . esc_attr__( text: "000.000.000-00", domain: $this->id ) . "' " . $this->field_name( name: "card-holder-cpf" ) . "/>
		</p>
		<p class='form-row form-row-wide' >
			<label for='{$id}-card-holder-phone' >" . esc_html__( text: "Celular do titular", domain: $this->id ) . "&nbsp;<span class='required'>*</span></label>
			<input id='{$id}-card-holder-phone' class='input-text' type='tel' placeholder='" . esc_attr__( text: "(00) 00000-0000", domain: $this->id ) . "' " . $this->field_name( name: "card-holder-phone" ) . "/>
		</p>";
	}

	/**
	 * Checkout form fields start.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id
	 *
	 * @return void
	 */
	public function form_fields_end( string $id ) : void
	{
		if ( $id != $this->id )
			return;

		$id = esc_attr( text: $this->id );

		echo "<p class='form-row form-row-wide' >
			<label for='{$id}-card-installment' >" . esc_html__( text: "Parcelas", domain: $this->id ) . "&nbsp;<span class='required' >*</span></label>
			<select id='{$id}-card-installment' class='input-text' style='width:100%; color:#999' " . $this->field_name( name: "card-installment" ) . " ></select>
		</p>";
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
		if (
			( class_exists( class: "WC_Subscriptions_Cart"                   ) && WC_Subscriptions_Cart::cart_contains_subscription() )
			||
			( class_exists( class: "WC_Subscriptions_Change_Payment_Gateway" ) && WC_Subscriptions_Change_Payment_Gateway::$is_request_to_change_payment )
			||
			( function_exists( function: "wcs_cart_contains_resubscribe" ) && wcs_cart_contains_resubscribe() )
			||
			( function_exists( function: "wcs_cart_contains_renewal"     ) && wcs_cart_contains_renewal() )
		)
		{
			$this->availability = false;
		}
		else
		{
			$description = ( $this->node->env() ) ? $this->get_description() . __( text: " - SANDBOX ativado.", domain: $this->id ) : $this->get_description();

			if ( $description ) echo wpautop( text: wptexturize( text: trim( string: $description ) ) );

			parent::payment_fields();
		}
	}

	/**
	 * Get User IP.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_user_ip() : string
	{
		if ( ! empty( $_SERVER[ "HTTP_CLIENT_IP" ] ) )
			$ip = $_SERVER[ "HTTP_CLIENT_IP" ];

		elseif ( ! empty( $_SERVER[ "HTTP_X_FORWARDED_FOR" ] ) )
			$ip = $_SERVER[ "HTTP_X_FORWARDED_FOR" ];

		else
			$ip = $_SERVER[ "REMOTE_ADDR" ];

		return $ip;
	}

	/**
	 * Process the payment.
	 * Function called by WooCommerce to process the payment.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed 	$order_id
	 *
	 * @return array
	 */
	public function process_payment( mixed $order_id ) : array
	{
		$notice        = "<b>" . $this->title . "</b>:";

		$order         = wc_get_order( the_order: $order_id );
        $user          = wp_get_current_user();
		$user_ip       = $this->get_user_ip();
		$token         = $this->api->validate_token( user_id: $user->ID );

		$cpf           = WC()->checkout()->get_value( input: $this->id . "-card-holder-cpf" );
		$email         = WC()->checkout()->get_value( input: "billing_email"    );
		$zipcode       = WC()->checkout()->get_value( input: "billing_postcode" );
		$addrNumber    = WC()->checkout()->get_value( input: "billing_number"   );

		$holder_name   = WC()->checkout()->get_value( input: $this->id . "-card-holder-name"  );
		$holder_cpf    = preg_replace( pattern: "/[^0-9]/", replacement: "", subject: $cpf );
		$holder_phone  = preg_replace( pattern: "/[^0-9]/", replacement: "", subject: WC()->checkout()->get_value( input: $this->id . "-card-holder-phone" ) );

		$card_number   = WC()->checkout()->get_value( input: $this->id . "-card-number" );
		$card_exp      = preg_replace( pattern: "/[^0-9]/", replacement: "", subject: WC()->checkout()->get_value( input: $this->id . "-card-expiry"       ) );
		$card_cvc      = preg_replace( pattern: "/[^0-9]/", replacement: "", subject: WC()->checkout()->get_value( input: $this->id . "-card-cvc"          ) );
		$card_brand    = WC()->checkout()->get_value( input: $this->id . "-card-brand"        );
		$installments  = WC()->checkout()->get_value( input: $this->id . "-card-installment"  );

		$card_exp      = substr( string: $card_exp, offset: 0, length: 2 ) . "/20" . substr( string: $card_exp, offset: 2, length: 2 );

		$tokenInfo     = [
			"token"       => $token,
			"card_number" => str_replace( search: " ", replace: "-", subject: $card_number ),
			"brand"       => $card_brand,

			"holder"      => $holder_name,
			"month"       => substr( string: $card_exp, offset: 0, length: 2 ),
			"year"        => substr( string: $card_exp, offset: 3    ),
			"cvv"         => $card_cvc,
		];

		if ( $this->service == "asaas" )
		{
			$tokenInfo[ "infoAsaas" ] = [
				"email"         => $email,
				"document"      => $cpf,
				"zipCode"       => $zipcode,
				"addressNumber" => $addrNumber,
				"cellPhone"     => $holder_phone,
				"userIp"        => $user_ip,
				"userId"        => $user->ID
			];
		}

		/**
		 * check if card hsa token
		 */
		$resp_card_tkn = $this->api->status_card( posted: $tokenInfo );

		if ( ! $resp_card_tkn[ "success" ] )
		{
			$message = "{$notice} " . $resp_card_tkn[ "error" ];

			$this->log(
				is_error: true,
				level   : "error",
				message : $message,
				context : $resp_card_tkn
			);

			wc_add_notice( message: $message, notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		$this->log(
			message: "Token generated",
			context: $resp_card_tkn
		);

		$tokenIndex    = $resp_card_tkn[ "tokenIndex" ];

		$paymentInfo   = [
			"orderId"     => $order_id,
			"totalAmount" => ( int ) str_replace( search: ".", replace: "", subject: $order->get_total() ),
			"description" => "Marketplace Triibo: #{$order_id}",
			"tokenIndex"  => $tokenIndex,
			"name"        => $holder_name,
			"document"    => $holder_cpf,
			"phone"       => $holder_phone,
		];

		if ( $this->service == "asaas" )
		{
			$paymentInfo[ "infoAsaas" ] = [
				"email"        => $email,
				"userId"       => $user->ID,
				"userIp"       => $user_ip,
				"type"         => "CREDIT_CARD",
				"installments" => ( int ) $installments,
				"dueDate"      => date( format: "Y-m-d" )
			];
		}

		/**
		 * make the payment
		 */
		$resp_pay      = $this->api->do_payment( method: "card", token: $token, data: $paymentInfo );

		if ( ! $resp_pay[ "success" ] )
		{
			$message = "{$notice} " . $resp_pay[ "error" ];

			$this->log(
				is_error: true,
				level   : "error",
				message : $message,
				context : $resp_pay
			);

			wc_add_notice( message: $message, notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		$this->log(
			message : "Payment successful",
			context : $resp_pay
		);

		$order->update_meta_data( key: "_triibo_payments_code", value: [
			"type"      => "CREDIT_CARD",
			"gateway"   => $this->service,
			"paymentId" => $resp_pay[ "data" ][ "paymentInfo" ][ "id" ],
		] );

		$pay_id        = $resp_pay[ "data" ][ "paymentInfo" ][ "id"  ];
		$note          = "Payment ID: {$pay_id}\n";

		/**
		 * Add the cielo TID code to the order note
		 *
		 * @since 	1.2.1
		 */
		if ( $this->service == "cielo" )
		{
			if ( isset( $resp_pay[ "data" ][ "paymentInfo" ][ "tid" ] ) )
			{
				$pay_tid  = $resp_pay[ "data" ][ "paymentInfo" ][ "tid" ];
				$note    .= "TID: {$pay_tid}\n";
			}
		}

		$note         .= "Gateway: " . $this->service;
		$order->add_order_note( note: $note );

		$order->payment_complete();
		$order->update_status( new_status: "processing", note: __( text: "Triibo_Payments: Pagamento aprovado", domain: $this->id ) );
		$order->save();

		// Remove cart.
		WC()->cart->empty_cart();

		// Reduce stock for billets.
		if ( function_exists( function: "wc_reduce_stock_levels" ) ) wc_reduce_stock_levels( order_id: $order_id );

		return [
			"result"   => "success",
			"redirect" => $this->get_return_url( order: $order ),
		];
	}

	/**
	 * Register log.
	 *
	 * @since 1.5.0 	Refactored
     * @since 1.0.0
     *
	 * @param string 	$level 		The log level (e.g., 'error', 'info')
	 * @param string 	$message 	The log message
	 * @param array 	$context 	Additional context for the log message
	 * @param bool 		$is_error 	Whether the log is an error
     *
     * @return void
	 */
	public function log( string $level = "info", string $message, array $context = [], bool $is_error = false ) : void
	{
		$logger  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$context = array_merge( $context, [ "source" => $this->id ] );

		if ( $is_error || $this->debug == "yes" )
			$logger->$level( $message, $context );
	}
}
