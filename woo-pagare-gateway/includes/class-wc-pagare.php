<?php
/**
 * Plugin's main class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare
 * @version 1.0
 */

/**
 * WooCommerce bootstrap class.
 */
class WC_Pagare
{
	/**
	 * Initialize the plugin public actions.
	 */
	public static function init()
    {
		// Checks with WooCommerce is installed.
		if ( class_exists( "WC_Payment_Gateway" ) )
        {
			self::includes();

			add_filter( "woocommerce_payment_gateways",           [ __CLASS__, "add_gateway"                  ]       );
			add_filter( "woocommerce_available_payment_gateways", [ __CLASS__, "hides_when_is_outside_brazil" ]       );
			add_filter( "woocommerce_billing_fields",             [ __CLASS__, "checkout_billing_fields"      ], 9999 );
			add_filter( "woocommerce_shipping_fields",            [ __CLASS__, "checkout_shipping_fields"     ], 9999 );

			add_filter( "plugin_action_links_" . plugin_basename( WC_PAGARE_PLUGIN_FILE ), [ __CLASS__, "plugin_action_links" ] );

			if ( is_admin() ) add_action( "admin_notices", [ __CLASS__, "ecfb_missing_notice" ] );

			add_action( "pagare_check_ticket_payment_status",     [ __CLASS__, "check_ticket_payment"         ], 10   );
			add_action( "pagare_check_pix_payment_status",        [ __CLASS__, "check_pix_payment"            ], 10   );
		}
        else
        {
			add_action( "admin_notices", array( __CLASS__, "woocommerce_missing_notice" ) );
		}
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path()
    {
		return plugin_dir_path( WC_PAGARE_PLUGIN_FILE ) . "templates/";
	}

	/**
	 * Action links.
	 *
	 * @param 	array $links
	 * @return 	array
	 */
	public static function plugin_action_links( $links )
    {
        $url            = esc_url( admin_url( "admin.php?page=wc-settings&tab=checkout&section=pagare" ) );
        $text           = __( "Configurações", "pagare-gateway" );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Includes.
	 */
	private static function includes()
    {
		include_once dirname( __FILE__ ) . "/class-wc-pagare-api.php";
		include_once dirname( __FILE__ ) . "/class-wc-pagare-gateway.php";
		include_once dirname( __FILE__ ) . "/rest-api/class-wp-pagare-rest-api.php";
	}

	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param array $methods 	WooCommerce payment methods.
	 * @return array 			Payment methods with Pagare.
	 */
	public static function add_gateway( $methods )
    {
		$methods[] = "WC_Pagare_Gateway";

		return $methods;
	}

	/**
	 * Hides the Pagare with payment method with the customer lives outside Brazil.
	 *
	 * @param array $available_gateways 	Default Available Gateways.
	 * @return array 						New Available Gateways.
	 */
	public static function hides_when_is_outside_brazil( $available_gateways )
    {
		// Remove Pagare gateway.
		if ( isset( $_REQUEST[ "country" ] ) && $_REQUEST[ "country" ] !== "BR" )
			unset( $available_gateways[ "pagare" ] );

		return $available_gateways;
	}

	/**
	 * Checkout billing fields.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function checkout_billing_fields( $fields )
	{
		if ( class_exists( "Extra_Checkout_Fields_For_Brazil" ) )
		{
			if ( isset( $fields[ "billing_neighborhood" ] ) )
				$fields["billing_neighborhood"]["required"] = true;

			if ( isset( $fields[ "billing_number" ] ) )
				$fields[ "billing_number" ][ "required" ] = true;
		}

		return $fields;
	}

	/**
	 * Checkout shipping fields.
	 *
	 * @param array $fields
	 * @return array
	 */
	public static function checkout_shipping_fields( $fields )
	{
		if ( class_exists( "Extra_Checkout_Fields_For_Brazil" ) )
		{
			if ( isset( $fields[ "shipping_neighborhood" ] ) )
				$fields[ "shipping_neighborhood" ][ "required" ] = true;

			if ( isset( $fields[ "shipping_number" ] ) )
				$fields[ "shipping_number" ][ "required" ] = true;
		}

		return $fields;
	}

	/**
	 * WooCommerce Extra Checkout Fields for Brazil notice.
	 */
	public static function ecfb_missing_notice()
    {
		if ( !is_plugin_active( "woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php" ) )
			include dirname( __FILE__ ) . "/admin/views/html-notice-missing-ecfb.php";
	}

	/**
	 * WooCommerce missing notice.
	 */
	public static function woocommerce_missing_notice()
    {
		include dirname( __FILE__ ) . "/admin/views/html-notice-missing-woocommerce.php";
	}

	/**
	 * Register the scheduled hook for pagare payments
	 *
	 * @return void
	 */
	public static function activate()
	{
        $log = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$log->add( "pagare", "Plugin activated" );

		// hourly / everythreeminute

		if ( !wp_next_scheduled( "pagare_check_ticket_payment_status" ) )
			wp_schedule_event( time(), "hourly", "pagare_check_ticket_payment_status" );

		if ( !wp_next_scheduled( "pagare_check_pix_payment_status" ) )
			wp_schedule_event( time(), "everythreeminute", "pagare_check_pix_payment_status" );
	}

	/**
	 * Clear the scheduled hook for pagare payments
	 *
	 * @return void
	 */
	public static function deactivate()
	{
        $log = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$log->add( "pagare", "Plugin deactivated" );

		wp_clear_scheduled_hook( "pagare_check_ticket_payment_status" );
		wp_clear_scheduled_hook( "pagare_check_pix_payment_status"    );
	}

	/**
	 * Check orders paid with Ticket
	 *
	 * @since 	1.2.0
	 * @return 	void
	 */
	public static function check_ticket_payment()
	{
		self::check_payment( "ticket" );
	}

	/**
	 * Check orders paid with PIX
	 *
	 * @since 	1.2.0
	 * @return 	void
	 */
	public static function check_pix_payment()
	{
        self::check_payment( "pix" );
	}

	/**
	 * Check orders paid
	 *
	 * @param  string $type
	 * @return void
	 */
	private static function check_payment( $type )
	{
        $log     = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$gateway = WC_Pagare_Gateway::init();
		$api     = $gateway->get_api();

		$orders  = self::get_pagare_payments( $type );

		if ( empty( $orders ) ) return;

		foreach ( $orders as $order )
		{
			$code     = $order->get_meta( "_pagare_code", true );

			if ( empty( $code ) ) continue;

			$id       = $code[ "paymentId" ];

			$log->add( $gateway->id, "INFO - checking payment status, order #{$order->ID}" );

			$response = $api->get_payment_status( $id, $type );

			if ( $response[ "success" ] && isset( $response[ "data" ] ) )
			{
				if ( $response[ "data" ][ "paied" ] )
				{
					$order->update_status( "processing", __( "Pagare: Pagamento recebido", WC_PAGARE_PLUGIN_DOMAIN ) );
					$order->save();

					$log->add( $gateway->id, "Order #{$order->ID}: status changed to processing" );
				}
			}
		}
	}

	/**
	 * Get orders on-hold
	 *
	 * @since 	1.2.0
	 * @param 	string $type
	 * @return 	array
	 */
	private static function get_pagare_payments( string $type )
	{
		$arr    = [];
		$orders = wc_get_orders( [
			"limit"          => -1,
			"status"         => [ "wc-on-hold" ],
			"payment_method" => "pagare",
		] );

		foreach ( $orders as $order )
		{
			$payment_code = $order->get_meta( "_pagare_code" );

			if ( $payment_code[ "type" ] == $type )
				$arr[] = $order;
		}

		return $arr;
	}
}
