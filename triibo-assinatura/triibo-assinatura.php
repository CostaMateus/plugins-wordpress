<?php
/**
 * Plugin Name: 			#Triibo_Assinaturas
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Triibo pagamento recorrente para WooCommerce. Dependências: Triibo API Services e WooCommerce Subscription.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				3.5.0
 * Text Domain: 			triibo-assinaturas
 * Requires Plugins:        triibo-api-services, woocommerce, woocommerce-subscriptions
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Assinaturas
 *
 * @version 3.5.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * @since 3.5.0
 */
$__ta_active_plugins   = apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) );
$__ta_requires_plugins = [
	"triibo-api-services/triibo-api-services.php",
	"woocommerce/woocommerce.php",
	"woocommerce-subscriptions/woocommerce-subscriptions.php"
];

/**
 * @since 3.5.0
 */
foreach ( $__ta_requires_plugins as $plugin )
	if ( ! in_array( needle: $plugin, haystack: $__ta_active_plugins, strict: true ) )
		return;

class WC_Triibo_Assinaturas
{
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = "3.5.0";

	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const DOMAIN  = "triibo-assinaturas";

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Construct
	 *
	 * @since 3.3.0 	Added HPOS compatibility declaration
	 * @since 1.0.0
	 */
	private function __construct()
    {
		if ( ! is_plugin_active( plugin: "triibo-api-services/triibo-api-services.php" ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "triibo_api_services_missing_notice" ] );
			return;
		}

		if ( ! is_plugin_active( plugin: "woocommerce-subscriptions/woocommerce-subscriptions.php" ) )
		{
			add_action( hook_name: "admin_notices", callback: [ $this, "woocommerce_missing_notice" ] );
			return;
		}

		/**
		 * @since 3.3.0
		 */
		if ( class_exists( class: "WooCommerce" ) )
			add_action( hook_name: "before_woocommerce_init", callback: [ $this, "setup_hpos_compatibility" ] );

		add_filter( hook_name: "woocommerce_payment_gateways", callback: [ $this, "register_gateway" ], priority: 99, accepted_args: 1 );

		include_once dirname( path: __FILE__ ) . "/includes/class-triibo-assinatura-api.php";
		include_once dirname( path: __FILE__ ) . "/includes/class-triibo-assinatura-gateway.php";
		require_once dirname( path: __FILE__ ) . "/includes/rest-api/class-triibo-assinatura-rest.php";

		add_filter( hook_name: "plugin_action_links_" . plugin_basename( file: __FILE__ ), callback: [ $this, "plugin_action_links" ] );
		add_filter( hook_name: "plugin_row_meta",                                    callback: [ $this, "plugin_row_meta"     ], priority: 10, accepted_args: 3 );

		if ( is_admin() && !is_plugin_active( plugin: "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) )
			add_action( hook_name: "admin_notices", callback: [ $this, "ecfb_missing_notice" ] );

		add_action( hook_name: "admin_menu",                          callback: [ $this, "add_submenu_page"    ], priority: 11 );
		add_action( hook_name: "triibo_api_service_add_button",       callback: [ $this, "add_btn"             ], priority: 11 );
		add_action( hook_name: "triibo_api_service_list_plugin_node", callback: [ $this, "add_info_dependency" ],    );

		new Triibo_Assinaturas_Rest();
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
	 * Setup WooCommerce HPOS compatibility.
	 *
	 * @since 3.3.0
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
	 * Register the gateway for use
	 *
	 * @since 1.0.0
	 *
	 * @param array 	$methods
	 *
	 * @return array
	 */
	public function register_gateway( array $methods ) : array
    {
		$methods[] = "Triibo_Assinaturas_Gateway";

		return $methods;
	}

	/**
	 * WooCommerce Extra Checkout Fields for Brazil notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function ecfb_missing_notice() : void
	{
		include_once dirname( path: __FILE__ ) . "/includes/admin/views/html-notice-missing-ecfb.php";
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
        $url            = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout&section=" . self::DOMAIN ) );
        $text           = __( text: "Configurações", domain: self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add link to changelog modal
	 *
	 * @since 3.1.0
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
	 * Add submenu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu_page() : void
	{
		add_submenu_page(
			parent_slug: Triibo_Api_Services::get_name(),
			page_title: "Triibo Assinaturas",
			menu_title: "Assinaturas",
			capability: "manage_options",
			menu_slug: self::DOMAIN . "-settings",
			callback: [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Display the admin menu settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu_settings() : void
	{
		include_once dirname( path: __FILE__ ) . "/includes/admin/views/" . self::DOMAIN . "-settings.php";
	}

	/**
	 * Add button to settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_btn() : void
	{
        $url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout&section=" . self::DOMAIN ) );
		$text = __( text: "WC Configurações", domain: self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Assinaturas | {$link}</p>";
	}

	/**
	 * Add information about plugin dependencies.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_info_dependency() : void
	{
		echo "<p> - Triibo Assinaturas</p>";
	}

}

add_action( hook_name: "plugins_loaded", callback: array( "WC_Triibo_Assinaturas", "get_instance" ), priority: 222 );
