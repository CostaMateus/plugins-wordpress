<?php
/**
 * Plugin Name: 			#Triibo_Payments
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Meios de pagamento Triibo para WooCommerce. Dependências: Triibo API Services e WooCommerce.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				1.5.0
 * Text Domain: 			triibo-payments
 * Requires Plugins:        triibo-api-services, woocommerce, woocommerce-extra-checkout-fields-for-brazil
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Payments
 *
 * @version 1.5.0
 */
defined( constant_name: "ABSPATH" ) || exit;

/**
 * @since 1.5.0
 */
$__tp_active_plugins   = apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) );
$__tp_requires_plugins = [
	"triibo-api-services/triibo-api-services.php",
	"woocommerce/woocommerce.php",
	"woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php"
];

/**
 * @since 1.5.0
 */
foreach ( $__tp_requires_plugins as $plugin )
	if ( ! in_array( needle: $plugin, haystack: $__tp_active_plugins, strict: true ) )
		return;

/**
 * Load Triibo Payments.
 *
 * @since 1.0.0
 */
add_action( hook_name: "plugins_loaded", callback: [ "Triibo_Payments", "get_instance" ], priority: 222 );

class Triibo_Payments
{
	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = "1.5.0";

	/**
	 * Plugin domain.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const DOMAIN = "triibo-payments";

	/**
	 * Plugin main file.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FILE = __FILE__;

	/**
	 * Instance of this class.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	protected static ?object $instance = null;

	/**
	 * Construct
	 *
	 * @since 1.3.0 	Added HPOS compatibility declaration
	 * @since 1.1.0 	Added plugin change history
	 * @since 1.0.0
	 */
	private function __construct()
    {
		require_once ( dirname( path: __FILE__ ) . "/includes/apis/class-triibo-payments-api.php"       );
		require_once ( dirname( path: __FILE__ ) . "/includes/rest-api/class-triibo-payments-rest.php"  );
		require_once ( dirname( path: __FILE__ ) . "/includes/gateways/class-triibo-payment-billet.php" );
		require_once ( dirname( path: __FILE__ ) . "/includes/gateways/class-triibo-payment-card.php"   );
		require_once ( dirname( path: __FILE__ ) . "/includes/gateways/class-triibo-payment-pix.php"    );

		$basename = plugin_basename( file: __FILE__ );

		add_filter( hook_name: "plugin_action_links_{$basename}", callback: [ $this, "plugin_action_links" ] );
		add_filter( hook_name: "woocommerce_payment_gateways",    callback: [ $this, "register_gateways"   ], priority: 99 );

		/**
		 * @since 1.1.0
		 */
		add_filter( hook_name: "plugin_row_meta", callback: [ $this, "plugin_row_meta" ], accepted_args: 3 );

		/**
		 * @since 1.3.0
		 */
		add_action( hook_name: "before_woocommerce_init", callback: [ $this, "setup_hpos_compatibility" ] );

		add_action( hook_name: "admin_menu",                          callback: [ $this, "add_submenu_page"    ], priority: 11 );
		add_action( hook_name: "triibo_api_service_add_button",       callback: [ $this, "add_btn"             ], priority: 11 );
		add_action( hook_name: "triibo_api_service_list_plugin_node", callback: [ $this, "add_info_dependency" ] );

		// Order Box Actions
		add_action( hook_name: "woocommerce_order_actions",                                         callback: [ $this, "add_order_box_action"   ] );
		add_action( hook_name: "woocommerce_order_action_wc_triibo_payments_billet_recover_status", callback: [ $this, "process_recover_status" ] );
		add_action( hook_name: "woocommerce_order_action_wc_triibo_payments_pix_recover_status",    callback: [ $this, "process_recover_status" ] );

		new Triibo_Payments_Rest();
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
        $url            = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout" ) );
        $text           = __( text: "Configurações", domain: self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Get templates path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_templates_path() : string
    {
		return plugin_dir_path( file: __FILE__ ) . "templates/";
	}

	/**
	 * Register the gateway for use.
	 *
	 * @since 1.0.0
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function register_gateways( array $methods ) : array
    {
		$methods[] = "Triibo_Payment_Billet";
		$methods[] = "Triibo_Payment_Card";
		$methods[] = "Triibo_Payment_Pix";

		return $methods;
	}

	/**
	 * Add link to changelog modal.
	 *
	 * @since 1.1.0
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
	 * Setup WooCommerce HPOS compatibility.
	 *
	 * @since 1.3.0
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
				plugin_file           :__FILE__,
				positive_compatibility: true
			);
		}
	}

	/**
	 * Add submenu to Triibo menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_submenu_page() : void
	{
		add_submenu_page(
			parent_slug: Triibo_Api_Services::get_name(),
			page_title : "Triibo Payments",
			menu_title : "Payments",
			capability : "manage_options",
			menu_slug  : self::DOMAIN . "-settings",
			callback   : [ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Display admin menu settings.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function display_admin_menu_settings() : void
	{
		require_once ( dirname( path: __FILE__ ) . "/templates/admin/settings.php" );
	}

	/**
	 * Add text to ecosystem description
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_btn() : void
	{
        $url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout" ) );
		$text = __( text: "WC Configurações", domain: self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Payments | {$link}</p>";
	}

	/**
	 * Add text to description which api it depends on
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_info_dependency() : void
	{
		echo "<p> - Triibo Payments</p>";
	}

	/**
	 * Add custom action to order box.
	 *
	 * @since 1.4.1 	Validating if the $theorder has the $code
	 * @since 1.0.0
	 *
	 * @param array 	$actions
	 *
	 * @return array
	 */
	public function add_order_box_action( $actions ) : array
	{
		global $theorder;

		$billet = Triibo_Payment_Billet::get_instance();
		$pix    = Triibo_Payment_Pix::get_instance();

		$method = $theorder->get_payment_method();

		if ( $method != $billet->get_id() && $method != $pix->get_id() )
			return $actions;

		$code   = $theorder->get_meta( key: "_triibo_payments_code" );

		if ( !$code )
			return $actions;

		$billet_id = $billet->get_id();
		$pix_id    = $pix->get_id();

		if ( !is_array( value: $code[ "invoice" ] ) && $theorder->has_status( status: "on-hold" ) )
		{
			if ( $method == $billet_id )
				$actions[ "wc_{$billet_id}_recover_status" ] = __( text: "Recuperar boleto - {$billet->get_method_title()}", domain: $billet_id );

			if ( $method == $pix_id )
				$actions[ "wc_{$pix_id}_recover_status"    ] = __( text: "Recuperar pix - {$pix->get_method_title()}", domain: $pix_id );
		}
		elseif ( $theorder->has_status( status: "on-hold" ) )
		{
			if ( $method == $billet_id )
				$actions[ "wc_{$billet_id}_recover_status" ] = __( text: "Atualizar status boleto - {$billet->get_method_title()}", domain: $billet_id );

			if ( $method == $pix_id )
				$actions[ "wc_{$pix_id}_recover_status"    ] = __( text: "Atualizar status pix - {$pix->get_method_title()}", domain: $pix_id );
		}

		return $actions;
	}

	/**
	 * Process custom action order to recover billet data/status.
	 *
	 * @since 1.4.1 	Validating if the $order has the $code
	 * @since 1.0.0
	 *
	 * @param object 	$order
	 *
	 * @return void
	 */
	public function process_recover_status( object $order ) : void
	{
		$code     = $order->get_meta( key: "_triibo_payments_code" );

		if ( !$code )
			return;

		$id       = self::DOMAIN . ( ( $code[ "type" ] == "BILLET" ) ? "_billet" : "_pix" );
		$method   = ( $code[ "type" ] == "BILLET" ) ? Triibo_Payment_Billet::get_instance() : Triibo_Payment_Pix::get_instance();
		$api      = $method->get_api();

		$token    = $api->validate_token( user_id: $order->user_id );
		$response = $api->payment_status( token: $token, gateway: $code[ "gateway" ], payment_id: $code[ "paymentId" ] );

		if ( ! $response[ "success" ] && ! isset( $response[ "data" ] ) )
		{
			$order->add_order_note( note: "Falha na consulta do dados/status do pagamento." );
			$order->save();
			return;
		}

		$payment_info = $response[ "data" ][ "paymentStatus" ];

		// se invoice retornou, atualiza metadata
		if ( is_array( value: $payment_info[ "invoice" ] ) )
		{
			$code[ "invoice" ] = $payment_info[ "invoice" ];
			$order->update_meta_data( key: "_triibo_payments_code", value: $code );
		}

		// se teve mudança de status e foi aprovado, muda pra processando
		if ( $payment_info[ "changed" ] && $payment_info[ "approved" ] )
		{
			$order->payment_complete();
			$order->update_status( new_status: "processing", note: __( text: "Triibo_Payments: Pagamento aprovado", domain: $id ) );
		}

		// se teve mudança de status e não foi aprovado, muda pra falha
		if ( $payment_info[ "changed" ] && !$payment_info[ "approved" ] )
			$order->update_status( new_status: "failed", note: __( text: "Triibo_Payments: Pagamento malsucedido", domain: $id ) );

		$payment_info[ "orderId" ] = $order->ID;
		unset( $payment_info[ "invoice" ] );

		$method->log( message: "INFO - payment_status: " . json_encode( value: $payment_info ) );

		$order->save();

		return;
	}
}