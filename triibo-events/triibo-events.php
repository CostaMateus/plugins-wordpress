<?php

/**
 * Plugin Name: 			#Triibo_Eventos
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Dispara eventos para a Triibo dada certas condições. Dependências: Triibo API Services, WooCommerce e WooCommerce Subscription.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				2.0.0
 * Text Domain: 			triibo-events
 * Requires Plugins:        triibo-api-services, woocommerce, woocommerce-subscriptions
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Events
 *
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * @since 2.0.0
 */
$__te_active_plugins   = apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) );
$__te_requires_plugins = [
	"triibo-api-services/triibo-api-services.php",
	"woocommerce/woocommerce.php",
	"woocommerce-subscriptions/woocommerce-subscriptions.php"
];

/**
 * @since 2.0.0
 */
foreach ( $__te_requires_plugins as $plugin )
	if ( ! in_array( needle: $plugin, haystack: $__te_active_plugins, strict: true ) )
		return;

/**
 * Load Triibo Events.
 *
 * @since 1.0.0
 */
add_action( hook_name: "plugins_loaded", callback: [ "Triibo_Events", "get_instance" ], priority: 222 );

/**
 * Filter to get valid events.
 *
 * @since 1.12.0
 *
 * @return array
 */
add_filter(
	hook_name: "triibo_events_valid_events",
	callback : function( $events ) : array
	{
		return Triibo_Events::get_valid_events();
	}
);

class Triibo_Events
{
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = "2.0.0";

	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const DOMAIN = "triibo-events";

	/**
	 * Subscription Triibo VIP.
	 *
	 * TODO - add admin panel
	 *
	 * @since 1.6.0 	Added new plans
	 * @since 1.5.0 	Added env
	 * @since 1.3.0
	 *
	 * @var array
	 */
	const PLANS = [
		"hml" => [
			// product_id => event_atv
			111111 => "Assinatura_111111", // hardcode - example
			222222 => "Assinatura_222222", // hardcode - example
		],
		"prd" => [
			// product_id => event_atv
			333333 => "Assinatura_333333", // hardcode - example
			444444 => "Assinatura_444444", // hardcode - example
		]
	];

	/**
	 * Orgs according to the env.
	 *
	 * TODO - add admin panel
	 *
	 * @since 1.5.2 	Added 'id' and 'name' indexes
	 * @since 1.5.0
	 *
	 * @var array
	 */
	const ORGS = [
		"hml" => [
			"add" => [
				// vip
				"id"   => "organization_id",   // hardcode - example
				"name" => "organization_name", // hardcode - example
			],
			"del" => [
				// normal
				"id"   => "organization_id",   // hardcode - example
				"name" => "organization_name", // hardcode - example
			]
		],
		"prd" => [
			"add" => [
				// vip
				"id"   => "organization_id",   // hardcode - example
				"name" => "organization_name", // hardcode - example
			],
			"del" => [
				// normal
				"id"   => "organization_id",   // hardcode - example
				"name" => "organization_name", // hardcode - example
			]
		]
	];

	/**
	 * Triibo Seu Clube.
	 *
	 * TODO - add admin panel
	 *
	 * @since 1.10.0 	Added product
	 * @since 1.8.0
	 *
	 * @var array
	 */
	const SEU_CLUBE = [
		"hml" => [
			111111, // Assinatura_111111
			222222, // Assinatura_222222
		],
		"prd" => [
			333333, // Assinatura_333333
			444444, // Assinatura_444444
		],
	];

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var null|Triibo_Events
	 */
	protected static ?Triibo_Events $instance = null;

	/**
	 * WC Logger.
	 *
	 * @since 1.0.0
	 *
	 * @var null|WC_Logger
	 */
	protected ?WC_Logger $log = null;

	/**
	 * Triibo Api Services - Gateway.
	 *
	 * @since 1.0.0
	 *
	 * @var null|Triibo_Api_Gateway
	 */
	protected ?Triibo_Api_Gateway $api_gate = null;

	/**
	 * Triibo Api Services - Node.
	 *
	 * @since 1.8.0
	 *
	 * @var null|Triibo_Api_Node
	 */
	protected ?Triibo_Api_Node $api_node = null;

	/**
	 * Construct.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.8.0 	Added Triibo_Api_Node
	 * @since 1.7.0 	Added HPOS compatibility declaration
	 * @since 1.5.0 	Added var $env
	 * @since 1.4.0 	Added plugin change history
	 * @since 1.0.0
	 */
	private function __construct()
    {
		$basename = plugin_basename( file: __FILE__ );

		$filters  = [
			"plugin_action_links_{$basename}" => [ "callback" => [ $this, "plugin_action_links" ] ],

			/**
			 * @since 1.4.0
			 */
			"plugin_row_meta"                 => [ "callback" => [ $this, "plugin_row_meta" ], "accepted_args" => 3 ]
		];

		$actions  = [
			/**
			 * @since 1.7.0
			 */
			"before_woocommerce_init"                           => [ "callback" => [ $this, "setup_hpos_compatibility" ] ],

			"admin_menu"                                        => [ "callback" => [ $this, "add_submenu_page"         ], "priority" => 11 ],
			"triibo_api_service_add_button"                     => [ "callback" => [ $this, "add_btn"                  ], "priority" => 11 ],
			"triibo_api_service_list_plugin_gate"               => [ "callback" => [ $this, "add_info_dependency"      ] ],
			"triibo_api_service_list_plugin_node"               => [ "callback" => [ $this, "add_info_dependency"      ] ],
			"woocommerce_subscription_status_cancelled"         => [ "callback" => [ $this, "subscription_ended"       ] ],
			"woocommerce_order_status_changed"                  => [ "callback" => [ $this, "order_status_change"      ], "accepted_args" => 4 ],
			"woocommerce_subscription_renewal_payment_complete" => [ "callback" => [ $this, "subscription_renewal"     ], "accepted_args" => 2 ],

			/**
			 * @since 1.11.0
			 */
			"woocommerce_subscription_status_expired"           => [ "callback" => [ $this, "subscription_ended"   ] ]
		];

		$this->add_hooks( type: "filter", hooks: $filters );
		$this->add_hooks( type: "action", hooks: $actions );

		$this->log      = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->api_gate = new Triibo_Api_Gateway();

		/**
		 * @since 1.8.0
		 */
		$this->api_node = new Triibo_Api_Node();
	}

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
	 * Triibo-Api-Service missing notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function triibo_api_services_missing_notice() : void
	{
		include_once dirname( path: __FILE__ ) . "/includes/admin/views/html-notice-missing-triibo_api_services.php";
	}

	/**
	 * Action links.
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$links
	 *
	 * @return array
	 */
	public static function plugin_action_links( array $links ) : array
    {
        $url            = esc_url( url: admin_url( path: "admin.php?page=" . self::DOMAIN . "-settings" ) );
        $text           = __( text: "Configurações", domain: self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Expose valid events via filter to other plugins.
	 *
	 * @since 1.12.0
	 *
	 * @return array
	 */
	public static function get_valid_events() : array
	{
		$events = [];

		foreach ( self::PLANS as $env )
			foreach ( $env as $event )
				$events[] = $event;

		return array_unique( array: $events );
	}

	/**
	 * Setup WooCommerce HPOS compatibility.
	 *
	 * @since 1.7.0
	 *
	 * @return void
	 */
	public function setup_hpos_compatibility() : void
	{
		if ( defined( constant_name: "WC_VERSION" ) && version_compare( version1: WC_VERSION, version2: "7.1", operator: "<" ) )
			return;

		if ( class_exists( class: \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
		{
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				feature_id            : "custom_order_tables",
				plugin_file           : __FILE__,
				positive_compatibility: true
			);
		}
	}

	/**
	 * Add link to changelog modal.
	 *
	 * @since 1.4.0
	 *
	 * @param array 	$plugin_meta
	 * @param string 	$plugin_file
	 * @param array 	$plugin_data
	 *
	 * @return array
	 */
	public function plugin_row_meta( array $plugin_meta, string $plugin_file, array $plugin_data ) : array
	{
		$path = path_join( base: WP_PLUGIN_DIR, path: $plugin_file );

		if ( DIRECTORY_SEPARATOR == "\\" )
			$path = str_replace( search: "/", replace: "\\", subject: $path );

        if ( __FILE__ === $path )
		{
            $url = plugins_url( path: "readme.txt", plugin: __FILE__ );

            $plugin_meta[] = sprintf(
                "<a href='%s' class='thickbox open-plugin-details-modal' aria-label='%s' data-title='%s'>%s</a>",
                add_query_arg( "TB_iframe", "true", $url ),
                esc_attr( text: sprintf( __( text: "More information about %s" ), $plugin_data[ "Name" ] ) ),
                esc_attr( text: $plugin_data[ "Name" ] ),
                __( text: "Histórico de alterações" )
            );
        }

        return $plugin_meta;
	}

	/**
	 * adds 'Eventos' submenu to Triibo Services main menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu_page() : void
	{
		add_submenu_page(
			parent_slug: Triibo_Api_Services::get_name(),
			page_title : "Triibo Eventos",
			menu_title : "Eventos",
			capability : "manage_options",
			menu_slug  : self::DOMAIN . "-settings",
			callback   : [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Template for submenu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu_settings() : void
	{
		include_once dirname( path: __FILE__ ) . "/templates/admin-page.php";
	}

	/**
	 * Link to plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_btn() : void
	{
        $url  = esc_url( url: admin_url( path: "admin.php?page=" . self::DOMAIN . "-settings" ) );
		$text = __( text: "Configurações", domain: self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Eventos | {$link}</p>";
	}

	/**
	 * Add this plugin's dependency in the Triibo Api Services settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_info_dependency() : void
	{
		echo "<p> - Triibo Eventos</p>";
	}

	/**
	 * Valid if is a sub-order and sends event for Triibo.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.1.0 	Rebuilt method to work with different statuses
	 * @since 1.0.0
	 *
	 * @param int 		$order_id
	 * @param string 	$old_status
	 * @param string 	$new_status
	 * @param object 	$order
	 *
	 * @return void
	 */
	public function order_status_change( int $order_id, string $old_status, string $new_status, object $order ) : void
	{
		if ( ! $this->is_original_order( order: $order ) )
			return;

		$user_id   = $order->get_user_id();
		$triibo_id = $this->get_user_phone( user_id: $user_id );

		if ( empty( $triibo_id ) )
		{
			$this->log(
				is_error: true,
				level   : "error",
				message : "User without Triibo account",
				context : [
					"order_id" => $order_id,
					"user_id"  => $user_id
				]
			);

			return;
		}

		// Subscription
		if ( $this->is_processing_subscription( order_id: $order_id, new_status: $new_status ) )
		{
			$this->process_vip_event(
				order    : $order,
				user_id  : $user_id,
				order_id : $order_id,
				triibo_id: $triibo_id,
				total    : $order->get_total()
			);

			$this->process_seu_clube_event(
				order    : $order,
				user_id  : $user_id,
				order_id : $order_id,
				triibo_id: $triibo_id
			);

			return;
		}

		// Normal purchase
		if ( $this->is_completed_purchase( order_id: $order_id, new_status: $new_status ) )
		{
			$this->process_normal_purchase(
				order    : $order,
				user_id  : $user_id,
				order_id : $order_id,
				triibo_id: $triibo_id,
				total    : $order->get_total()
			);

			return;
		}
	}

	/**
	 * Process subscription cancellation/expiration.
	 *
	 * @since 1.11.0 	Renamed to `subscription_ended` from `subscription_status_cancelled`
	 * @since 1.8.0
	 *
	 * @param object 	$subscription
	 *
	 * @return void
	 */
	public function subscription_ended( object $subscription ) : void
	{
		$subscription_id = $subscription->get_id();
		$user_id         = $subscription->get_user_id();

		foreach ( $subscription->get_items() as $item_id => $item )
		{
			$product_id = $item->get_product_id();

			$this->log(
				level  : "info",
				message: "Canceling subscription #{$subscription_id}, user_id #{$user_id}"
			);

			// Seu Clube.
			if ( in_array( needle: $product_id, haystack: self::SEU_CLUBE[ $this->get_env() ] ) )
			{
				$token = $this->api_node->get_token( user_id: $user_id );

				if ( is_null( value: $token ) )
				{
					$this->log(
						is_error: true,
						level   : "error",
						message : "Subscription: #{$subscription_id} | {$user_id}, unable to generate Node API token",
						context : [ "product_id" => $product_id, ]
					);
					break;
				}

				$order = wc_get_order( the_order: $subscription->get_parent_id() );

				$this->toggle_user_subscription(
					order     : $order,
					action    : "delete",
					user_id   : $user_id,
					product_id: $product_id,
					token     : $token
				);

				$order->save();
				break;
			}
		}
	}

	/**
	 * Handle subscription renewal event.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.2.0
	 *
	 * @param object 	$subscription
	 * @param object 	$order
	 *
	 * @return void
	 */
	public function subscription_renewal( object $subscription, object $order ) : void
	{
		$total     = $order->get_total();
		$order_id  = $order->get_id();

		if ( ! $this->is_original_order( order: $order ) )
			return;

		$user_id   = $order->get_user_id();
		$triibo_id = $this->get_user_phone( user_id: $user_id );

		if ( empty( $triibo_id ) )
		{
			$this->log(
				is_error: true,
				level   : "error",
				message : "User without Triibo account",
				context : [
					"order_id" => $order_id,
					"user_id"  => $user_id
				]
			);

			return;
		}

		$has_cp    = $this->has_already_executed_event( user_id: $user_id, meta_key: "has_cp_{$order_id}"  );
		$has_atv   = $this->has_already_executed_event( user_id: $user_id, meta_key: "has_atv_{$order_id}" );

		foreach ( $order->get_items() as $item )
		{
			$product_id = $item->get_product_id();

			if ( isset( self::PLANS[ $this->get_env() ][ $product_id ] ) )
			{
				$this->process_subscription_renewal_item(
					order     : $order,
					product_id: $product_id,
					user_id   : $user_id,
					triibo_id : $triibo_id,
					order_id  : $order_id,
					total     : $total,
					has_cp    : $has_cp,
					has_atv   : $has_atv
				);

				break;
			}
		}
	}

	/**
	 * Process VIP event.
	 *
	 * @since 2.0.0
	 *
	 * @param object 	$order
	 * @param int 		$user_id
	 * @param int 		$order_id
	 * @param string 	$triibo_id
	 * @param float 	$total
	 *
	 * @return void
	 */
	private function process_vip_event( object $order, int $user_id, int $order_id, string $triibo_id, float $total ) : void
	{
		foreach ( $order->get_items() as $item )
		{
			$product_id = $item->get_product_id();

			// Triibo VIP
			if ( isset( self::PLANS[ $this->get_env() ][ $product_id ] ) )
			{
				// Compra_Marketplace
				$this->process_event(
					args: [
						"meta_key"     => "has_cp_{$order_id}",
						"api_callback" => [ $this, "call_event" ],
						"api_args"     => [ $order, "Compra_Marketplace", $triibo_id, $total ],
						"log_context"  => [],
						"event_label"  => "Compra_Marketplace",
						"order"        => $order,
						"user_id"      => $user_id,
						"triibo_id"    => $triibo_id,
						"order_id"     => $order_id,
						"success_note" => null,
						"fail_note"    => null,
						"log_success"  => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
						"log_fail"     => "Order: #%d | User: %d | %s, failed to credit '%s'",
						"log_type"     => "notice",
					]
				);

				$event = self::PLANS[ $this->get_env() ][ $product_id ];

				// Assinatura_Triibo_Vip
				$this->process_event(
					args: [
						"meta_key"     => "has_atv_{$order_id}",
						"api_callback" => [ $this, "call_event" ],
						"api_args"     => [ $order, $event, $triibo_id, 1 ],
						"log_context"  => [],
						"event_label"  => $event,
						"order"        => $order,
						"user_id"      => $user_id,
						"triibo_id"    => $triibo_id,
						"order_id"     => $order_id,
						"success_note" => null,
						"fail_note"    => null,
						"log_success"  => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
						"log_fail"     => "Order: #%d | User: %d | %s, failed to credit '%s'",
						"log_type"     => "notice",
						"orgs_update"  => [ $this, "update_user_orgs" ],
						"orgs_args"    => [ $user_id, $order, $event ],
					]
				);

				/**
				 * Check if it is not 'Seu Clube' as well
				 * Triibo Vip, started to trigger the 'Seu_Clube' event
				 * @since 1.10.0
				 */
				if ( ! in_array( needle: $product_id, haystack: self::SEU_CLUBE[ $this->get_env() ] ) )
				{
					$order->save();
					break;
				}
			}
		}
	}

	/**
	 * Process Seu Clube event.
	 *
	 * @since 2.0.0
	 *
	 * @param object 	$order
	 * @param int 		$user_id
	 * @param int 		$order_id
	 * @param string 	$triibo_id
	 *
	 * @return void
	 */
	private function process_seu_clube_event( object $order, int $user_id, int $order_id, string $triibo_id ) : void
	{
		foreach ( $order->get_items() as $item )
		{
			$product_id = $item->get_product_id();

			if ( in_array( needle: $product_id, haystack: self::SEU_CLUBE[ $this->get_env() ] ) )
			{
				$this->process_event(
					args: [
						"meta_key"     => "has_seu_clube_{$order_id}",
						"api_callback" => function() use ( $user_id, $order, $product_id ) : array
						{
							$token = $this->api_node->get_token( user_id: $user_id );

							if ( is_null( value: $token ) )
								return [ "success" => false ];

							$this->toggle_user_subscription(
								order     : $order,
								action    : "add",
								user_id   : $user_id,
								product_id: $product_id,
								token     : $token
							);

							return [ "success" => true ];
						},
						"api_args"     => [],
						"log_context"  => [],
						"event_label"  => "Seu_Clube",
						"order"        => $order,
						"user_id"      => $user_id,
						"triibo_id"    => $triibo_id,
						"order_id"     => $order_id,
						"success_note" => null,
						"fail_note"    => null,
						"log_success"  => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
						"log_fail"     => "Order: #%d | User: %d | %s, failed to credit '%s'",
						"log_type"     => "notice",
					]
				);

				$order->save();
				break;
			}
		}
	}

	/**
	 * Process normal purchase events.
	 *
	 * #since 2.0.0
	 *
	 * @param object 	$order
	 * @param int 		$user_id
	 * @param int 		$order_id
	 * @param string 	$triibo_id
	 * @param float 	$total
	 *
	 * @return void
	 */
	private function process_normal_purchase( object $order, int $user_id, int $order_id, string $triibo_id, float $total ) : void
	{
		$this->process_event(
			args: [
				"meta_key"    => "has_cp_{$order_id}",
				"api_callback"=> [$this, "call_event"],
				"api_args"    => [$order, "Compra_Marketplace", $triibo_id, $total],
				"log_context" => [],
				"event_label" => "Compra_Marketplace",
				"order"       => $order,
				"user_id"     => $user_id,
				"triibo_id"   => $triibo_id,
				"order_id"    => $order_id,
				"success_note"=> null,
				"fail_note"   => null,
				"log_success" => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
				"log_fail"    => "Order: #%d | User: %d | %s, failed to credit '%s'",
				"log_type"    => "notice",
			]
		);

		$order->save();
	}

	/**
	 * Get organization IDs and names.
	 *
	 * @since 2.0.0
	 *
	 * @param object 	$order
	 * @param int 		$product_id
	 * @param int 		$user_id
	 * @param string 	$triibo_id
	 * @param int 		$order_id
	 * @param float 	$total
	 * @param bool 		$has_cp
	 * @param bool 		$has_atv
	 *
	 * @return void
	 */
	private function process_subscription_renewal_item( object $order, int $product_id, int $user_id, string $triibo_id, int $order_id, float $total, bool $has_cp, bool $has_atv ) : void
	{
		$label_cp  = "Compra_Marketplace";
		$label_atv = self::PLANS[ $this->get_env() ][ $product_id ];

		$event_cp  = [
			"meta_key"     => "has_cp_{$order_id}",
			"api_callback" => [ $this, "call_event" ],
			"api_args"     => [ $order, $label_cp, $triibo_id, $total ],
			"log_context"  => [],
			"event_label"  => $label_cp,
			"order"        => $order,
			"user_id"      => $user_id,
			"triibo_id"    => $triibo_id,
			"order_id"     => $order_id,
			"success_note" => null,
			"fail_note"    => null,
			"log_success"  => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
			"log_fail"     => "Order: #%d | User: %d | %s, failed to credit '%s'",
			"log_type"     => "notice",
		];

		$event_atv = [
			"meta_key"     => "has_atv_{$order_id}",
			"api_callback" => [ $this, "call_event" ],
			"api_args"     => [ $order, $label_atv, $triibo_id, 1 ],
			"log_context"  => [],
			"event_label"  => $label_atv,
			"order"        => $order,
			"user_id"      => $user_id,
			"triibo_id"    => $triibo_id,
			"order_id"     => $order_id,
			"success_note" => null,
			"fail_note"    => null,
			"log_success"  => "Order: #%d | User: %d | %s, user has already been credited with '%s'",
			"log_fail"     => "Order: #%d | User: %d | %s, failed to credit '%s'",
			"log_type"     => "notice",
		];

		$this->process_event( args: $event_cp  );
		$this->process_event( args: $event_atv );

		$order->save();
	}

	/**
	 * Generalized event processing helper.
	 * Check meta, call API, mark as executed, log result.
	 *
	 * @since 2.0.0
	 *
	 * @param array $args
	 *   - meta_key:     string
	 *   - api_callback: callable
	 *   - api_args:     array
	 *   - log_context:  array
	 *   - event_label:  string
	 *   - order:        object
	 *   - user_id:      int
	 *   - triibo_id:    string
	 *   - order_id:     int
	 *   - success_note: string
	 *   - fail_note:    string
	 *   - log_success:  string (template)
	 *   - log_fail:     string (template)
	 *   - log_type:     string
	 *
	 * @return void
	 */
	private function process_event( array $args ) : void
	{
		extract( array: $args );

		if ( $this->has_already_executed_event( user_id: $user_id, meta_key: $meta_key ) )
		{
			$this->log_user_event(
				template: $log_success,
				level   : $log_type,
				vars    : [
					$order_id,
					$user_id,
					$triibo_id,
					$event_label
				],
			);

			return;
		}

		$response = call_user_func_array( callback: $api_callback, args: $api_args );

		if ( ! isset( $response[ "success" ] ) || ! $response[ "success" ] )
		{
			$this->log_user_event(
				template: $log_fail,
				level   : "error",
				is_error: true,
				vars    : [
					$order_id,
					$user_id,
					$triibo_id,
					$event_label
				],
			);

			if ( isset( $order ) && isset( $fail_note ) )
				$order->add_order_note( $fail_note );

			return;
		}

		$this->mark_event_executed( user_id: $user_id, meta_key: $meta_key );

		if ( isset( $orgs_update ) )
			call_user_func_array( callback: $orgs_update, args: $orgs_args );

		if ( isset( $order ) && isset( $success_note ) )
			$order->add_order_note( $success_note );
	}

	/**
	 * Add actions or filters.
	 *
	 * @since 2.0.0
	 *
	 * @param string 	$type
	 * @param array 	$hooks
	 *
	 * @return void
	 */
	private function add_hooks( string $type, array $hooks ) : void
	{
		if ( ! in_array( needle: $type, haystack: [ "action", "filter" ] ) )
			return;

		foreach ( $hooks as $hook => $data )
		{
			$priority      = ( isset( $data[ "priority"      ] ) ) ? $data[ "priority"      ] : 10;
			$accepted_args = ( isset( $data[ "accepted_args" ] ) ) ? $data[ "accepted_args" ] : 1;

			if ( "action" === $type )
			{
				add_action(
					hook_name    : $hook,
					callback     : $data[ "callback" ],
					priority     : $priority,
					accepted_args: $accepted_args
				);
			}

			if ( "filter" === $type )
			{
				add_filter(
					hook_name    : $hook,
					callback     : $data[ "callback" ],
					priority     : $priority,
					accepted_args: $accepted_args
				);
			}
		}
	}

	/**
	 * Get environment.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	private function get_env() : string
	{
		return ( get_option( option: "home" ) === "https://marketplace.triibo.com.br" ) ? "prd" : "hml";
	}

	/**
	 * Call event.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.3.2 	Added return value.
	 * @since 1.1.0
	 *
	 * @param object 	$order
	 * @param string 	$event
	 * @param string 	$triibo_id
	 * @param float 	$total
	 *
	 * @return array
	 */
	private function call_event( object &$order, string $event, string $triibo_id, float $total ) : array
	{
		$response = $this->api_gate->credit_points( triibo_id: $triibo_id, value: $total, event: $event );

		/**
		 * Added event field to log.
		 * @since 	1.2.0
		 */
		$this->log(
			level  : "info",
			message: "Order: #{$order->get_id()} | {$triibo_id} | {$event}",
			context: [
				"event"     => $event,
				"triibo_id" => $triibo_id,
				"response"  => $response
			]
		);

		$this->add_note( order: $order, triibo_id: $triibo_id, event: $event, response: $response );

		return $response;
	}

	/**
	 * Check if the order is an original order.
	 *
	 * @since 2.0.0
	 *
	 * @param object 	$order
	 *
	 * @return boolean
	 */
	private function is_original_order( object $order ) : bool
	{
		return $order->get_parent_id() == 0;
	}

	/**
	 * Get user phone number.
	 *
	 * @since 2.0.0
	 *
	 * @param int 	$user_id
	 *
	 * @return string
	 */
	private function get_user_phone( int $user_id ) : string
	{
		$triibo_phone = get_user_meta( user_id: $user_id, key: "_triibo_phone", single: true );

		if ( empty( $triibo_phone ) )
			$triibo_phone = get_user_meta( user_id: $user_id, key: "triiboId_phone", single: true );

		return preg_replace( pattern: "/\D/", replacement: "", subject: $triibo_phone );
	}

	/**
	 * Check if the order is a processing subscription.
	 *
	 * @since 2.0.0
	 *
	 * @param int 		$order_id
	 * @param string 	$new_status
	 *
	 * @return boolean
	 */
	private function is_processing_subscription( int $order_id, string $new_status ) : bool
	{
		return $new_status === "processing" && wcs_order_contains_subscription( order: $order_id );
	}

	/**
	 * Check if the order is a completed purchase.
	 *
	 * @since 2.0.0
	 *
	 * @param int 		$order_id
	 * @param string 	$new_status
	 *
	 * @return boolean
	 */
	private function is_completed_purchase( int $order_id, string $new_status ) : bool
	{
		return $new_status === "completed" && ! wcs_order_contains_subscription( order: $order_id );
	}

	/**
	 * Check if the user has already executed the event.
	 *
	 * @since 2.0.0
	 *
	 * @param int 		$user_id
	 * @param string 	$meta_key
	 *
	 * @return boolean
	 */
	private function has_already_executed_event( int $user_id, string $meta_key ) : bool
	{
		return ! empty( get_user_meta( user_id: $user_id, key: $meta_key, single: true ) );
	}

	/**
	 * Mark the event as executed.
	 *
	 * @since 2.0.0
	 *
	 * @param int 		$user_id
	 * @param string 	$meta_key
	 *
	 * @return void
	 */
	private function mark_event_executed( int $user_id, string $meta_key ) : void
	{
		add_user_meta( user_id: $user_id, meta_key: $meta_key, meta_value: true, unique: true );
	}

	/**
	 * Get organization IDs and names.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	private function get_org_ids_and_names() : array
	{
		return self::ORGS[ $this->get_env() ];
	}

	/**
	 * Add or remove organization from user.
	 *
	 * @since 2.0.0
	 *
	 * @param object    $order
	 * @param string    $note
	 * @param string    $action 'add' or 'remove'
	 * @param string    $uid
	 * @param string    $org_id
	 * @param string    $org_name
	 *
	 * @return boolean
	 */
	private function toggle_org_on_user( object &$order, string &$note, string $action, string $uid, string $org_id, string $org_name ) : bool
	{
		if ( ! in_array( needle: $action, haystack: [ "add", "remove" ] ) )
		{
			$this->log(
				is_error: true,
				level   : "error",
				message : "Ação inválida fornecida para toggle_org_on_user: {$action}",
				context : [
					"order_id" => $order->get_id(),
					"uid"      => $uid,
					"org_id"   => $org_id,
					"org_name" => $org_name,
					"action"   => $action,
				]
			);

			return false;
		}

		$api_method      = "add_org";
		$success_message = "\n\nOrganização ($org_name) adicionada com sucesso";
		$failure_message = "\n\nFalha ao adicionar organização ({$org_name}) ao usuário";

		if ( "remove" === $action )
		{
			$api_method      = "del_org";
			$success_message = "\n\nOrganização ($org_name) removida com sucesso";
			$failure_message = "\n\nFalha ao remover organização ({$org_name}) do usuário";
		}

		$response = $this->api_gate->$api_method( uid: $uid, org_id: $org_id );

		if ( $response[ "success" ] )
		{
			$note .= $success_message;
			return true;
		}

		$this->log(
			is_error: true,
			level   : "error",
			message : trim( string: $failure_message ),
			context : [
				"order_id" => $order->get_id(),
				"uid"      => $uid,
				"org_id"   => $org_id,
				"org_name" => $org_name,
				"action"   => $action,
				"response" => $response
			]
		);

		$note .= $failure_message;
		$note .= "\n\nConta: {$uid}\nErro: API";

		$order->add_order_note( note: $note );

		return false;
	}

	/**
	 * Update user organizations (add VIP, remove normal org).
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.5.2 	Added $order and $event parameters
	 * @since 1.5.0
	 *
	 * @param object 	$order
	 * @param int 		$user_id
	 * @param string 	$event
	 *
	 * @return void
	 */
	private function update_user_orgs( object &$order, int $user_id, string $event ) : void
	{
		$note    = "Evento: {$event}";
		$org     = $this->get_org_ids_and_names();
		$uid     = get_user_meta( user_id: $user_id, key: "_triibo_id", single: true );

		$added   = $this->toggle_org_on_user(
			order   : $order,
			note    : $note,
			action  : "add",
			uid     : $uid,
			org_id  : $org[ "add" ][ "id"   ],
			org_name: $org[ "add" ][ "name" ],
		);

		$removed = $this->toggle_org_on_user(
			order   : $order,
			note    : $note,
			action  : "remove",
			uid     : $uid,
			org_id  : $org[ "del" ][ "id"   ],
			org_name: $org[ "del" ][ "name" ],
		);

		if ( ! $added )
			return;

		if ( ! $removed )
			return;

		$note .= "\n\nConta: {$uid}";

		$order->add_order_note( note: $note );
	}

	/**
	 * Add or delete user subscription Seu Clube.
	 *
	 * @since 2.0.0
	 *
	 * @param object   &$order
	 * @param string   $action 'add' or 'delete'
	 * @param int      $user_id
	 * @param int      $product_id
	 * @param string   $token
	 *
	 * @return void
	 */
	private function toggle_user_subscription( object &$order, string $action, int $user_id, int $product_id, string $token ) : void
	{
		if ( ! in_array( needle: $action, haystack: [ "add", "delete" ] ) )
		{
			$this->log(
				is_error: true,
				level   : "error",
				message : "Ação inválida fornecida para toggle_user_subscription: {$action}",
				context : [
					"order_id"  => $order->get_id(),
					"action"    => $action,
					"user_id"   => $user_id,
					"product_id"=> $product_id,
				]
			);

			return;
		}

		$event_name      = "Assinatura Seu Clube";
		$api_method      = "add_subscription";
		$success_message = "\n\n{$event_name} adicionada com sucesso";
		$failure_message = "\n\nFalha ao adicionar {$event_name} ao usuário";

		if ( "delete" === $action )
		{
			$api_method      = "del_subscription";
			$success_message = "\n\n{$event_name} cancelada com sucesso";
			$failure_message = "\n\nFalha ao cancelar {$event_name} do usuário";
		}

		$note     = "Evento: {$event_name}";
		$uid      = get_user_meta( user_id: $user_id, key: "_triibo_id", single: true );
		$params   = [
			"productId" => ( string ) $product_id,
			"uid"       => $uid
		];

		$response = $this->api_node->$api_method( token: $token, params: $params );

		if ( $response[ "success" ] )
		{
			$note .= $success_message;
			$note .= "\n\nConta: {$uid}";

			$order->add_order_note( note: $note );

			return;
		}

		$this->log(
			is_error: true,
			level   : "error",
			message : trim( string: $failure_message ),
			context : [
				"order_id"  => $order->get_id(),
				"action"    => $action,
				"user_id"   => $user_id,
				"product_id"=> $product_id,
				"response"  => $response
			]
		);

		$note .= $failure_message;
		$note .= "\n\nConta: {$uid}\nErro: API";

		$order->add_order_note( note: $note );
	}

	/**
	 * Add note to order.
	 *
	 * @since 1.0.4
	 *
	 * @param object 	$order
	 * @param string 	$triibo_id
	 * @param string 	$event
	 * @param array 	$response
	 *
	 * @return void
	 */
	private function add_note( object &$order, string $triibo_id, string $event, array $response ) : void
	{
		$note = "Evento: {$event}";

		if ( $response[ "success" ] && isset( $response[ "data" ] ) && $response[ "data" ][ "success" ] )
		{
			$movement_id  = $response[ "data" ][ "movementID" ];
			$note        .= "\nCréditos/cupons adicionados a conta Triibo\nConta: {$triibo_id}\nMovimentação ID: {$movement_id}";
		}
		elseif ( $response[ "success" ] && isset( $response[ "data" ] ) && ! $response[ "data" ][ "success" ] )
		{
			$note        .= "\nFalha ao adicionar créditos/cupons a conta Triibo\nConta: {$triibo_id}\nErro: API";
		}
		elseif ( !$response[ "success" ] && isset( $response[ "event" ] ) )
		{
			$note        .= "\nEvento não suportado\nConta: {$triibo_id}\nErro: MKT";
		}
		else
		{
			$note        .= "\nFalha ao adicionar créditos/cupons a conta Triibo\nConta: {$triibo_id}\nErro: MKT";
		}

		$order->add_order_note( note: $note );
	}

	/**
	 * Register logs.
	 *
	 * @since 2.0.0 	Refactored
	 * @since 1.10.1 	Fix log
	 * @since 1.9.0 	Changing to the new woocommerce standard
	 * 					Added params $level and $error
	 * 					Renamed param $data to $message
	 * @since 1.0.0
	 *
	 * @param string 	$level 		The log level (e.g., 'error', 'info')
	 * @param string 	$message 	The log message
	 * @param array 	$context 	Additional context for the log message
	 * @param bool 		$is_error 	Whether the log is an error
	 *
	 * @return void
	 */
	private function log( string $level = "info", string $message, array $context = [], bool $is_error = false ) : void
	{
		// Use WooCommerce logger if available
		$logger  = function_exists( "wc_get_logger" ) ? wc_get_logger() : new WC_Logger();

		$context = array_merge( $context, [ "source" => self::DOMAIN ] );

		// Log only errors or if the environment is set to "on" (homologation)
		if ( $is_error || $this->get_env() === "hml" )
			$logger->$level( $message, $context );
	}

	/**
	 * Wrapper for user event logging.
	 *
	 * @since 2.0.0
	 *
	 * @param string 	$template
	 * @param array 	$vars
	 * @param string 	$level
	 * @param boolean 	$is_error
	 *
	 * @return void
	 */
	private function log_user_event( string $template, array $vars, string $level = "info", bool $is_error = false ) : void
	{
		$this->log(
			level   : $level,
			message : vsprintf( format: $template, values: $vars ),
			context : $vars,
			is_error: $is_error
		);
	}
}
