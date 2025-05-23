<?php
/**
 * Plugin Name: 			#Triibo_Fast_Shop
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Comunicação entre MKT, Integração e FastShop. Dependências: Triibo API Services e WooCommerce.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				2.1.0
 * Text Domain: 			triibo-fast-shop
 * Requires Plugins:        triibo-api-services, woocommerce
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Fast_Shop
 *
 * @version 2.1.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * @since 2.1.0
 */
$__tfs_active_plugins   = apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) );
$__tfs_requires_plugins = [
	"triibo-api-services/triibo-api-services.php",
	"woocommerce/woocommerce.php"
];

/**
 * @since 2.1.0
 */
foreach ( $__tfs_requires_plugins as $plugin )
	if ( ! in_array( needle: $plugin, haystack: $__tfs_active_plugins, strict: true ) )
		return;

class Triibo_Fast_Shop
{
	/**
	 * Plugin version.
	 *
     * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = "2.1.0";

	/**
	 * Plugin domain.
	 *
     * @since 1.0.0
	 *
	 * @var string
	 */
	const DOMAIN  = "triibo-fast-shop";

	/**
	 * Seller name ID.
	 *
     * @since 2.0.0
	 *
	 * @var string
	 */
    const SELLER = "fast_shop";

	/**
	 * Seller ID.
	 *
     * @since 2.0.2
	 *
	 * @var string
	 */
    const SELLER_ID = 4265;

	/**
	 * Instance of this class.
	 *
     * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

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
	 * Constructor
	 *
	 * @since 1.7.0 	Added HPOS compatibility declaration
	 * @since 1.2.0 	Added plugin change history
	 * @since 1.0.0
	 */
	private function __construct()
    {
		if ( ! class_exists( class: "Triibo_Api_Services" ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "triibo_api_services_missing_notice" ] );
			return;
		}

		if ( ! class_exists( class: "WooCommerce" ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "woocommerce_missing_notice" ] );
			return;
		}

		/**
		 * @since 1.7.0
		 */
		add_action( hook_name: "before_woocommerce_init", callback: [ $this, "setup_hpos_compatibility" ] );

		// Order Class
		include_once dirname( path: __FILE__ ) . "/includes/class-triibo-fastshop-orders.php";
		// Shipping Class
		include_once dirname( path: __FILE__ ) . "/includes/class-triibo-fastshop-shipping.php";

		add_filter( hook_name: "plugin_action_links_" . plugin_basename( file: __FILE__ ), callback: [ $this, "plugin_action_links" ] );
		add_filter( hook_name: "woocommerce_rest_suppress_image_upload_error", callback: [ $this, "image_upload_error" ], accepted_args: 4 );

		/**
		 * @since 1.2.0
		 */
		add_filter( hook_name: "plugin_row_meta", callback: [ $this, "plugin_row_meta" ], accepted_args: 3 );

		/**
		 * @since 2.0.0
		 */
		add_action( hook_name: "woocommerce_rest_prepare_shop_order_object", callback: [ $this, "add_vtex_meta_data" ], accepted_args: 3 );

		add_action( hook_name: "admin_menu", callback: [ $this, "add_submenu_page" ], priority: 11 );
		add_action( hook_name: "triibo_api_service_add_button", callback: [ $this, "add_btn" ], priority: 11 );
		add_action( hook_name: "triibo_api_service_list_plugin_php", callback: [ $this, "add_info_dependency" ] );

		// Update stock when added to cart
		add_action( hook_name: "woocommerce_add_to_cart", callback: [ $this, "add_to_cart_update_stock" ], accepted_args: 6 );

		// Order Actions
		add_action( hook_name: "woocommerce_order_status_changed", callback: [ $this, "order_split" ], priority: 7, accepted_args: 4 );
		add_action( hook_name: "woocommerce_order_status_changed", callback: [ $this, "order_status_change" ], priority: 9, accepted_args: 4 );
		add_action( hook_name: "admin_init", callback: [ $this, "order_notification" ] );
		// Shipping Actions
		add_filter( hook_name: "woocommerce_shipping_methods", callback: [ $this, "shipping_method" ] );

		// Order Box Actions
		add_action( hook_name: "woocommerce_order_actions", callback: [ $this, "add_order_box_action" ] );
		add_action( hook_name: "woocommerce_order_action_wc_fastshop_order_action", callback: [ $this, "process_order_box_action" ] );
	}

	/**
	 * WooCommerce missing notice.
	 *
     * @since 1.0.0
	 *
     * @return void
	 */
	public static function woocommerce_missing_notice() : void
	{
		include_once dirname( path: __FILE__ ) . "/includes/admin/views/html-notice-missing-woocommerce.php";
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
	 * Action links.
	 *
     * @since 1.0.0
	 *
	 * @param array $links
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) : array
    {
        $url            = esc_url( url: admin_url( path: "admin.php?page=" . self::DOMAIN . "-settings" ) );
        $text           = __( text: "Configurações", domain: self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add submenu page
	 *
     * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu_page() : void
	{
		add_submenu_page(
			parent_slug: Triibo_Api_Services::get_name(),
			page_title : "Triibo Fast Shop",
			menu_title : "Fast Shop",
			capability : "manage_options",
			menu_slug  : self::DOMAIN . "-settings",
			callback   : [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Display erros settings
	 *
     * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu_settings() : void
	{
		include_once dirname( path: __FILE__ ) . "/templates/" . self::DOMAIN . "-settings.php";
	}

	/**
	 * Add btn link
	 *
     * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_btn() : void
	{
		$instance = null;
		$shipping = new WC_Shipping_Zones();
		$zones    = $shipping->get_zones();
		$index    = array_key_first( array: $zones );
		$methods  = $zones[ $index ][ "shipping_methods" ];

		foreach ( $methods as $method )
			if ( $method && $method->id === Triibo_Fast_Shop_Shipping::ID )
				$instance = $method->instance_id;

		$url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=shipping&instance_id={$instance}" ) );
		$text = __( text: "WC Configurações", domain: self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

        $url2  = esc_url( url: admin_url( path: "admin.php?page=" . self::DOMAIN . "-settings" ) );
        $text2 = __( text: "Configurações", domain: self::DOMAIN );
		$link2 = "<a href='{$url2}' >{$text2}</a>";

		echo "<p>Triibo Fast Shop | {$link} | {$link2}</p>";
	}

	/**
	 * Add info dependency
	 *
     * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_info_dependency() : void
	{
		echo "<p> - Triibo Fast Shop</p>";
	}

    /**
     * Update stock when added to cart
	 *
	 * @since 2.0.1 	Fix to use '_fs_product_id' metadata instead of 'SKU'.
     * @since 1.0.0
	 *
	 * @param string 	$cart_item_key
	 * @param int    	$product_id
	 * @param int    	$quantity
	 * @param int    	$variation_id
	 * @param array  	$variation
	 * @param array  	$cart_item_data
	 *
     * @return void
     */
	public function add_to_cart_update_stock( string $cart_item_key, int $product_id, int $quantity, int $variation_id, array $variation, array $cart_item_data ) : void
	{
        $product = wc_get_product( the_product: $product_id );
        $sku     = $product->get_meta( key: "_fs_product_id" );

        if ( $sku )
        {
			try
			{
				$api = new Triibo_Api_Php( seller: self::SELLER );
				$api->update_sku( sku: "{$product_id}_{$sku}" );
			}
			catch ( Exception $e )
			{
				$log = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
				$log->add( handle: "triibo-fast-shop-shipping", message: $e->getMessage() );
			}
        }

        return;
	}

	/**
	 * Validates if the order is only from FastShop
	 *
     * @since 1.0.0
	 *
	 * @param \WC_Order 	$order
	 *
	 * @return bool
	 */
	private function is_only_fs( \WC_Order $order ) : bool
	{
		$items      = $order->get_items();
		$countItems = count( value: $items );
		$auxCount   = 0;
		$hasFS      = false;
		$onlyFS     = true;

		foreach ( $items as $item )
		{
			$product = $item->get_product();

			if ( is_a( object_or_class: $product, class: "WC_Product" ) )
			{
				if ( class_exists( class: "WC_Subscriptions_Product" ) &&
					WC_Subscriptions_Product::is_subscription( product: $product ) )
				{
					$hasFS  = false;
					$onlyFS = false;
					break;
				}

				$hub     = $product->get_shipping_class();

				if ( strpos( haystack: $hub, needle: "fast-shop" ) !== false )
				{
					$auxCount++;
					$hasFS = true;
				}
			}
		}

		if ( $auxCount != $countItems ) $onlyFS = false;

		return ( $hasFS == true && $onlyFS == true );
	}

	/**
	 * Validates if the order is a suborder from FastShop
	 *
     * @since 2.0.2 	Fixed product seller validation.
     * @since 1.0.0
	 *
	 * @param \WC_Order 	$order
	 *
	 * @return bool
	 */
	private function is_fs_order( \WC_Order $order ) : bool
	{
		$subOrder = empty( $order->get_meta( key: "has_sub_order" ) ) ? false : true;

		if ( $subOrder )
			return true;

		if ( !empty( $order->get_meta( key: "_fastshop_order_id" ) ) )
			return true;

		$items = $order->get_items();

		foreach ( $items as $item )
		{
			$product_id = $item->get_product_id();
			$product    = $item->get_product();

			if ( !$product_id || !$product )
				return false;

			$store_id   = ( int ) dokan_get_vendor_by_product( product: $product, get_vendor_id: true );

			if ( !$store_id || $store_id != self::SELLER_ID )
				return false;

			$hub     = $product->get_shipping_class();

			if ( strpos( haystack: $hub, needle: "fast-shop" ) !== false )
				return true;
		}

		return false;
	}

	/**
	 * Valid if order needs to be split, based on Fast Shop HUBs
	 *
     * @since 1.0.0
	 *
	 * @param int          $order_id
	 * @param string       $old_status
	 * @param string       $new_status
	 * @param \WC_Order    $order
	 *
     * @return void
	 */
	public function order_split( int $order_id, string $old_status, string $new_status, \WC_Order $order ) : void
	{
		if ( $this->is_only_fs( order: $order ) )
		{
			if ( $old_status == "pending" )
			{
				// Remove the hook to prevent recursive callas.
				remove_action( hook_name: "woocommerce_order_status_changed", callback: [ $this, "order_split" ], priority: 7 );

				// Split the order.
				$triibo = new Triibo_Fast_Shop_Orders();
				$triibo->maybe_split_orders( parent_order_id: $order_id, parent_order: $order );

				// Add the hook back.
				add_action( hook_name: "woocommerce_order_status_changed", callback: [ $this, "order_split" ], priority: 7, accepted_args: 4 );
			}
		}
	}

	/**
	 * Valid if it is a Fast Shop order and sends notification for integration
	 *
     * @since 1.0.0
	 *
	 * @param int          $order_id
	 * @param string       $old_status
	 * @param string       $new_status
	 * @param \WC_Order 	$order
	 *
     * @return void
	 */
	public function order_status_change( int $order_id, string $old_status, string $new_status, \WC_Order $order ) : void
	{
		if ( ! $this->is_fs_order( order: $order ) )
			return;

		if ( $old_status == "on-hold" && $new_status == "processing" )
			return;

		$api      = new Triibo_Fast_Shop_Orders( order_id: $order_id, old_status: $old_status, new_status: $new_status );
		$response = $api->send_notification();

		/**
		 * Add note when order cancelation fails.
		 *
		 * @since 1.1.0
		 */
		if ( $api->get_type() == "ordercancelation" && !$response[ "success" ] )
		{
			switch ( $response[ "errorCode" ] )
			{
				case "4001":
					$message = "FALHA ao cancelar pedido na FASTSHOP.";
				break;

				case "4002":
					$message = "Pedido sem ID FASTSHOP, não encontrado!";
				break;
			}

			$order->add_order_note( note: $message );
		}
	}

	/**
	 * Register and build the plugin fields
	 *
	 * @since 1.0.0
	 *
     * @return void
	 */
	public function order_notification() : void
	{
		$prefix    = self::DOMAIN . "_";
		$sufix     = "_section";

		$page      = $prefix . "general_settings";

		$fs_fields = [
			[
				"key"   => "fs_status",
				"title" => "Ativar / Desativar",
				"label" => "Ativar Notificação de pedido para Fast Shop",
				"value" => false,
			],
		];

		register_setting( option_group: $page, option_name: $prefix . "options" );

		$this->add_allowed_options( prefix: $prefix, page: $page, data: [ $fs_fields ] );

		add_settings_section( id: $prefix . "fs" . $sufix, title: "Notificação de pedidos", callback: [ $this, "message_section" ], page: $page );

		foreach ( $fs_fields as $index => $field )
		{
			$this->add_settings_field( prefix: $prefix, sufix: $sufix, page: $page, section: "fs", index: $index, field: $field );
			$this->register_settings( prefix: $prefix, page: $page, field: $field );
		}
	}

	/**
	 * Adds plugin fields to allowed options
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$prefix
	 * @param string 	$page
	 * @param array 	$data
	 *
	 * @return void
	 */
	public function add_allowed_options( string $prefix, string $page, array $data ) : void
	{
		$new_options = [ $page => [] ];

		foreach ( $data as $api )
			foreach ( $api as $field )
				$new_options[ $page ][] = $prefix . $field[ "key" ];

		add_allowed_options( new_options: $new_options );
	}

	/**
	 * Echos out any content at the top of the section (between heading and fields).
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$args
	 *
	 * @return void
	 */
	public function message_section( array $args ) : void
	{
		echo "<p>Configurações</p>";
	}

	/**
	 * Configure each plugin field
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$prefix
	 * @param string 	$sufix
	 * @param string 	$page
	 * @param string 	$section
	 * @param string 	$index
	 * @param array  	$field
	 *
	 * @return void
	 */
	public function add_settings_field( string $prefix, string $sufix, string $page, string $section, string $index, array $field ) : void
	{
		$id      = $prefix . $field[ "key" ];
		$title   = $field[ "title" ];
		$section = $prefix . $section . $sufix;

		add_settings_field(
            id      : $id,
			title   : $title,
			callback: [ $this, "render_settings_field" ],
			page    : $page,
			section : $section,
			args    : [
				"type"             => "input",
				"subtype"          => ( in_array( needle: $index, haystack: [ 0, 1 ] ) ) ? "checkbox"        : "text",
				"label"            => ( in_array( needle: $index, haystack: [ 0, 1 ] ) ) ? $field[ "label" ] : "",
				"value"            => ( $index == 1 ) ? $field[ "value" ] : "",
				"id"               => $id,
				"name"             => $id,
				"required"         => true,
				"get_options_list" => "",
				"value_type"       => "normal",
				"wp_data"          => "option"
			]
		);
	}

	/**
	 * Renders each field according to the configured args
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$args
	 *
	 * @return void
	 */
	public function render_settings_field( array $args ) : void
	{
		if ( $args[ "wp_data" ] == "option" )
		{
			$wp_data_value = get_option( option: $args[ "name" ] );
		}
		elseif ( $args[ "wp_data" ] == "post_meta" )
		{
			$wp_data_value = get_post_meta( post_id: $args[ "post_id" ], key: $args[ "name" ], single: true );
		}

		switch ( $args[ "type" ] )
		{
			case "input":
				$status = 0;

				if ( strpos( haystack: $args[ "id" ], needle: "fs" ) !== false ) $status = get_option( option: self::DOMAIN . "_fs_status" );

				$value = ( $args[ "value_type" ] == "serialized" ) ? serialize( value: $wp_data_value ) : $wp_data_value;

				if ( $args[ "subtype" ] != "checkbox")
				{
					$prependStart = ( isset( $args[ "prepend_value" ] ) ) ? "<div class='input-prepend'> <span class='add-on'>{$args[ "prepend_value" ]}</span>" : "";
					$prependEnd   = ( isset( $args[ "prepend_value" ] ) ) ? "</div>"                   : "";
					$step         = ( isset( $args[ "step"          ] ) ) ? "step='{$args[ "step" ]}'" : "";
					$min          = ( isset( $args[ "min"           ] ) ) ? "min='{$args[ "min" ]}'"   : "";
					$max          = ( isset( $args[ "max"           ] ) ) ? "max='{$args[ "max" ]}'"   : "";
					$required     = ( $args[ "required" ] && $status    ) ? "required='required'"      : "";

					if ( isset( $args[ "disabled" ] ) )
					{
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}_disabled' {$step} {$max} {$min} name='{$args[ "name" ]}_disabled' disabled value='" . esc_attr( text: $value ) . "' />
							<input type='hidden' id='{$args[ "id" ]}' {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( text: $value ) . "' />" . $prependEnd;
					}
					else
					{
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' {$required} {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( text: $value ) . "' />" . $prependEnd;
					}
				}
				else
				{
					$checked = ( $value ) ? "checked" : "";
					echo "<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' name='{$args[ "name" ]}' {$checked} />";
					echo "<label for='{$args[ "id" ]}' >{$args[ "label" ]}</label>";
				}
			break;
		}
	}

	/**
	 * Register each plugin field
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$prefix
	 * @param string 	$page
	 * @param array 	$field
	 *
	 * @return void
	 */
	public function register_settings( string $prefix, string $page, array $field ) : void
	{
		$name = $prefix . $field[ "key" ];

		register_setting( option_group: $page, option_name: $name );
	}

	/**
	 * Ignore image upload with error
	 *
     * @since 1.0.0
	 *
	 * @param bool   			$false
	 * @param array|WP_Error 	$upload
	 * @param int    			$product_get_id
	 * @param array  			$images
	 *
	 * @return true
	 */
	public function image_upload_error( bool $false, array|WP_Error $upload, int $product_get_id, array $images ) : true
	{
		return true;
	}

	/**
	 * Add link to changelog modal
	 *
	 * @since 1.2.0
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
	 * Include Fastshop shipping to WooCommerce.
	 *
     * @since 1.0.0
	 *
	 * @param array 	$methods 	Default shipping methods.
	 *
	 * @return array
	 */
	public function shipping_method( array $methods ) : array
	{
        $methods[ "fastshop-shipping" ] = "Triibo_Fast_Shop_Shipping";

		return $methods;
	}

	/**
	 * Add custom action to order box.
	 *
     * @since 1.0.0
	 *
	 * @param array 	$actions
	 *
     * @return array
	 */
	public function add_order_box_action( array $actions ) : array
	{
		global $theorder;

		if ( $this->is_only_fs( order: $theorder ) && $theorder->has_status( "processing" ) && !$theorder->get_meta( "_fastshop_order_approved", true ) )
		{
			$actions[ "wc_fastshop_order_action" ] = __( text: "Aprovar na Fast Shop", domain: self::DOMAIN );

			return $actions;
		}

		return $actions;
	}

	/**
	 * Process custom action order box.
	 *
     * @since 1.0.0
	 *
	 * @param \WC_Order 	$order
	 *
     * @return void
	 */
	public function process_order_box_action( \WC_Order $order ) : void
	{
		$api      = new Triibo_Fast_Shop_Orders( order_id: $order->get_id(), old_status: "on-hold", new_status: "processing" );
		$response = $api->send_notification();

		if ( ! $response[ "success" ] )
		{
			$order->add_order_note( note: $response[ "message" ] );

			return;
		}

		$user_name =  wp_get_current_user()->display_name;

		/**
		 * log when manual approval happens
		 *
		 * @since 	1.6.1
		 */
		$api->log( level: "info", message: "Manually approved by: {$user_name}" );

		$order->add_order_note( note: "Pedido aprovado manualmente na FS por: {$user_name}" );
	}

	/**
	 * Added FS product id to the API response data.
	 *
     * @since 2.0.3 	Check if line_items is set.
     * @since 2.0.0
	 *
	 * @param object 	$response
	 * @param object 	$post
	 * @param object 	$request
	 *
	 * @return object
	 */
	public function add_vtex_meta_data( object $response, object $post, object $request ) : object
	{
		if ( ! isset( $response->data[ "line_items" ] ) )
			return $response;

		foreach ( $response->data[ "line_items" ] as &$item )
		{
			$product = wc_get_product( the_product: $item[ "product_id" ] );

			if ( $product )
			{
				$meta = $product->get_meta( key: "_fs_product_id" );

				$item[ "meta_data" ][] = [
					"key"   => "_fs_product_id",
					"value" => $meta
				];
			}
		}

		return $response;
	}
}

add_action( hook_name: "plugins_loaded", callback: array( "Triibo_Fast_Shop", "get_instance" ), priority: 222 );
