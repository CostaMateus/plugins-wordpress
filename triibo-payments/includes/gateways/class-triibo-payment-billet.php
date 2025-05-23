<?php
/**
 * Triibo Payment Billet class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Payment_Billet extends WC_Payment_Gateway
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
	 * Message of checkout page.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $message_pre = "";

	/**
	 * Message of thank_you and user order page.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $message_pos = "";

	/**
	 * Billet expiration date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $due_date = null;

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
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		self::$instance = $this;

        $this->id                 = Triibo_Payments::DOMAIN . "-billet";
        $this->icon               = apply_filters(
			hook_name: "woocommerce_" . $this->id . "_icon",
			value    : plugins_url(
				path  : "assets/images/icon-billet.png",
				plugin: Triibo_Payments::FILE
			)
		);

        $this->method_title       = __( text: "Triibo Boleto",                                  domain: $this->id );
        $this->method_description = __( text: "Pagamentos com boleto usando a Triibo Gateway.", domain: $this->id );
        $this->order_button_text  = __( text: "Efetuar pagamento",                              domain: $this->id );

        $this->supports           = apply_filters( hook_name: $this->id . "_supports_array", value: [
            "products",
        ] );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables.
        $this->title              = $this->get_option( key: "title"         );
        $this->description        = $this->get_option( key: "description"   );
		$this->due_date           = $this->get_option( key: "due_date", empty_value: "2" );
		$this->message_pre        = $this->get_option( key: "message_pre",  );
		$this->message_pos        = $this->get_option( key: "message_pos",  );
        $this->service            = $this->get_option( key: "service"       );
        $this->debug              = $this->get_option( key: "debug"         );

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
	 * Get log view.
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

		if ( !is_plugin_active( plugin: "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) )
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
			"enabled"     => [
				"type"        => "checkbox",
				"title"       => __( text: "Ativar / Desativar",                                        domain: $this->id ),
				"label"       => __( text: "Ativar Triibo Boleto",                                      domain: $this->id ),
				"default"     => "no",
			],

			"title"       => [
				"type"        => "text",
				"title"       => __( text: "Título",                                                    domain: $this->id ),
				"description" => __( text: "Controla o título que o usuário vê durante o checkout.",    domain: $this->id ),
				"default"     => __( text: "Triibo Boleto",                                             domain: $this->id ),
			],
			"description" => [
				"type"        => "textarea",
				"title"       => __( text: "Descrição",                                                 domain: $this->id ),
				"description" => __( text: "Controla a descrição que o usuário vê durante o checkout.", domain: $this->id ),
				"default"     => __( text: "Pague com a Triibo Boleto",                                 domain: $this->id ),
			],
			"due_date"    => [
				"type"        => "number",
				"title"       => __( text: "Validade do boleto",                                        domain: $this->id ),
				"description" => __( text: "Informe qual o prazo de validade do boleto bancário,
									  em dias.<br> Mínimo 1.",                                          domain: $this->id ),
				"default"     => 2,
				"custom_attributes" => [
					"maxlength" => 2,
					"min"       => 1,
				],
            ],
			"message_pre"     => [
				"type"        => "textarea",
				"title"       => __( text: "Mensagem de instrução 1",                                   domain: $this->id ),
				"description" => __( text: "Controla a mensagem de instrução que o usuário
									  vê na tela de finalização do pedido.",                            domain: $this->id ),
				"default"     => __( text: "Ao finalizar a compra, você terá acesso ao código de barras do boleto bancário que poderá pagar no seu internet banking ou em uma lotérica.<br><br> * O pedido será confirmado somente após o pagamento ser confirmado.", domain: $this->id ),
			],
			"message_pos"     => [
				"type"        => "textarea",
				"title"       => __( text: "Mensagem de instrução 2",                                   domain: $this->id ),
				"description" => __( text: "Controla a mensagem de instrução que o usuário
									  vê na tela de obrigado e na tela de meus pedidos.",               domain: $this->id ),
				"default"     => __( text: "Acesse o link abaixo para abrir o boleto.<br> Utilize o aplicativo do seu banco para ler o código de barras e efetuar o pagamento.", domain: $this->id ),
			],

			"service"     => [
				"type"        => "select",
				"title"       => __( text: "Serviço de pagamento",                                      domain: $this->id ),
				"description" => __( text: "Escolha qual será o serviço de pagamento padrão.",          domain: $this->id ),
				"default"     => "asaas",
				"class"       => "wc-enhanced-select",
				"options"     => [
					"asaas" => __( text: "Asaas", domain: $this->id ),
				],
			],

			"debug"       => [
				"type"        => "checkbox",
				"title"       => __( text: "Debug Log",                                                 domain: $this->id ),
				"label"       => __( text: "Ativar logger",                                             domain: $this->id ),
				"description" => sprintf( __( text: "Registrar log de eventos, %s", domain: $this->id ), $this->get_log_view() ),
				"default"     => "yes",
			],

			"configs"     => [
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
		add_action( hook_name: "woocommerce_update_options_payment_gateways_" . $this->id, callback: [ $this, "process_admin_options" ] );
		add_action( hook_name: "woocommerce_thankyou_"                        . $this->id, callback: [ $this, "thankyou_page"         ] );
		add_action( hook_name: "woocommerce_email_after_order_table",                      callback: [ $this, "email_instructions"    ] );

		if ( is_account_page() )
			add_action( hook_name: "woocommerce_order_details_after_order_table", callback: [ $this, "order_page" ] );
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
			args  : true
		);

		require_once ( dirname( path: Triibo_Payments::FILE ) . "/templates/admin/wc-settings.php" );
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

			echo "<p>{$this->message_pre}</p>";
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
		if ( !empty( $_SERVER[ "HTTP_CLIENT_IP" ] ) )
		{
			$ip = $_SERVER[ "HTTP_CLIENT_IP" ];
		}
		elseif ( !empty( $_SERVER[ "HTTP_X_FORWARDED_FOR" ] ) )
		{
			$ip = $_SERVER[ "HTTP_X_FORWARDED_FOR" ];
		}
		else
		{
			$ip = $_SERVER[ "REMOTE_ADDR" ];
		}

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
		$notice      = "<b>" . $this->title . "</b>:";

		$order       = wc_get_order( the_order: $order_id );
        $user        = wp_get_current_user();
		$user_ip     = $this->get_user_ip();
		$token       = $this->api->validate_token( user_id: $user->ID );

		$f_name      = WC()->checkout()->get_value( input: "billing_first_name" );
		$l_name      = WC()->checkout()->get_value( input: "billing_last_name"  );
		$cpf         = WC()->checkout()->get_value( input: "billing_cpf"        );
		$email       = WC()->checkout()->get_value( input: "billing_email"      );
		$phone       = WC()->checkout()->get_value( input: "billing_phone"      );

		$paymentInfo = [
			"orderId"     => $order_id,
			"totalAmount" => ( int ) str_replace( search: ".", replace: "", subject: $order->get_total() ),
			"description" => "Marketplace Triibo: #{$order_id}",
			"name"        => "{$f_name} {$l_name}",
			"document"    => preg_replace( pattern: "/[^0-9]/", replacement: "", subject: $cpf   ),
			"phone"       => preg_replace( pattern: "/[^0-9]/", replacement: "", subject: $phone ),
		];

		if ( $this->service == "asaas" )
		{
			$due_date = date( format: "Y-m-d" ) . " +{$this->due_date} days";
			$due_date = date( format: "Y-m-d", timestamp: strtotime( datetime: $due_date ) );

			$paymentInfo[ "infoAsaas" ] = [
				"email"        => $email,
				"userId"       => $user->ID,
				"userIp"       => $user_ip,
				"type"         => "BOLETO",
				"installments" => 1,
				"dueDate"      => $due_date
			];
		}

		$resp_pay      = $this->api->do_payment( method: "billet", token: $token, data: $paymentInfo );

		if ( !$resp_pay[ "success" ] )
		{
			$message = "{$notice} " . $resp_pay[ "error" ];

			$this->log(
				is_error: true,
				level   : "error",
				message : "ERROR - process_payment: {$message}",
				context : $resp_pay[ "data" ]
			);

			wc_add_notice( message: $message, notice_type: "error" );

			return [
				"result"   => "fail",
				"redirect" => ""
			];
		}

		$this->log(
			message: "INFO - process_payment",
			context: $resp_pay,
		);

		$order->update_meta_data(
			key: "_triibo_payments_code",
			value: [
				"type"      => "BILLET",
				"gateway"   => $this->service,
				"paymentId" => $resp_pay[ "data" ][ "paymentInfo" ][ "id"      ],
				"invoice"   => $resp_pay[ "data" ][ "paymentInfo" ][ "invoice" ]
			]
		);

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

		$order->update_status( new_status: "on-hold", note: __( text: "Triibo_Payments: Aguardando pagamento", domain: $this->id ) );
		$order->save();

		// Remove cart.
		WC()->cart->empty_cart();

		// Reduce stock for billets.
		if ( function_exists( function: "wc_reduce_stock_levels" ) )
			wc_reduce_stock_levels( order_id: $order_id );

		return [
			"result"   => "success",
			"redirect" => $this->get_return_url( order: $order ),
		];
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @since 1.4.1 	Validating if the $order has the $code
	 * @since 1.0.0
	 *
	 * @param object 	$order
	 *
	 * @return void
	 */
	public function email_instructions( object $order ) : void
	{
		if ( $order->get_payment_method() != $this->id ) return;

		$code = $order->get_meta( "_triibo_payments_code" );

		if ( $code && !empty( $code[ "invoice" ] ) )
		{
			wc_get_template(
				template_name: "on-hold.php",
				args: [
					"type" => $code[ "type"    ],
					"link" => $code[ "invoice" ][ "url"           ],
					"code" => $code[ "invoice" ][ "digitableLine" ],
				],
				template_path: "",
				default_path: Triibo_Payments::get_templates_path() . "emails/"
			);
		}
	}

	/**
	 * Thank You page message.
	 *
	 * @since 1.4.1 	Validating if the $order has the $code
	 * @since 1.0.0
	 *
	 * @param int 		$order_id
	 *
	 * @return void
	 */
	public function thankyou_page( int $order_id ) : void
	{
		$order = wc_get_order( the_order: $order_id );

		if ( $order->get_payment_method() != $this->id )
			return;

		if ( $code = $order->get_meta( key: "_triibo_payments_code" ) )
			$this->render_code( invoice: $code[ "invoice" ] );
	}

	/**
	 * Order Page message.
	 *
	 * @since 1.4.1 	Validating if the $order has the $code
	 * @since 1.0.0
	 *
	 * @param object 	$order
	 *
	 * @return void
	 */
	public function order_page( object $order ) : void
	{
		if ( $order->get_payment_method() != $this->id ) return;

		if ( $code = $order->get_meta( "_triibo_payments_code" ) )
			$this->render_code( invoice: $code[ "invoice" ] );
	}

	/**
	 * Render billet code on thank_you/order page.
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$invoice
	 *
	 * @return void
	 */
	private function render_code( array $invoice ) : void
	{
		if ( empty( $invoice ) )
			return;

		$link = $invoice[ "url"           ];
		$code = $invoice[ "digitableLine" ];

		?>
		<div class="tpb-billet-container" style="text-align: center;margin: 20px 0" >
			<div class="tpb-billet-instructions" >
				<?php echo $this->message_pos; ?>
			</div>
			<input type="hidden" value="<?php echo $code; ?>" id="copiar" >

			<a class="tpb-billet-p button tpb-billet-button-copy-code" style="margin:1em auto;" href="<?php echo $link; ?>" target="_blank" >Acessar boleto</a>

			<p class="tpb-billet-p" style="font-size:14px; margin:.5em 0; word-break:break-all;" >Linha digitável do boleto</p>
			<p class="tpb-billet-p" style="font-size:14px; margin:.5em 0; word-break:break-all;" ><?php echo $code; ?></p>

			<button class="button tpb-billet-button-copy-code" style="margin:1em auto;" onclick="copyCode()" >
				<?php echo __( text: "Copiar o código acima", domain: $this->id ); ?>
			</button>
			<div class="tpb-billet-response-output inactive" style="margin:1em .5em; padding:.2em 1em; border:2px solid #46b450; display:none;" aria-hidden="true" >
				<?php echo __( text: "Código copiado para a área de transferência.", domain: $this->id ); ?>
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

				if ( jQuery( "div.tpb-billet-response-output" ) )
					jQuery( "div.tpb-billet-response-output" ).show();
				else
					alert( "Código copiado para a área de transferência." );

				return false;
			}
		</script>
		<?php
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
