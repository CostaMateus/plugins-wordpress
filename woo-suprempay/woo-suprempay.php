<?php
/**
 * Plugin Name:          WooCommerce SupremPay
 * Plugin URI:           https://www.suprem.cash/
 * Description:          Adiciona as opções de pagamento SupremCash ao WooCommerce
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.0.0
 * Text Domain:          suprempay_gateway
 * RequiresPHP:          7.4
 * RequiresWP:           6.0
 * WC requires at least: 6.3
 * WC tested up to:      6.9
 *
 * @author  Mateus_Costa
 * @package WooCommerce_SupremPay
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

// Plugin constants.
define( "WC_SUPREMPAY_ID",           "suprempay"         );
define( "WC_SUPREMPAY_DOMAIN",       "suprempay_gateway" );
define( "WC_SUPREMPAY_VERSION",      "1.0.0"             );
define( "WC_SUPREMPAY_REST_API_VER", "v1"                );
define( "WC_SUPREMPAY_PLUGIN_FILE",  __FILE__            );

if ( !class_exists( "WC_SupremPay" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-wc-suprempay.php";
	add_action( "plugins_loaded", [ "WC_SupremPay", "init" ] );

	register_deactivation_hook( WC_SUPREMPAY_PLUGIN_FILE, [ "WC_SupremPay", "deactivate" ] );
}
