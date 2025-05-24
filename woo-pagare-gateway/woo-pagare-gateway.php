<?php
/**
 * Plugin Name:          WooCommerce Pagare Gateway
 * Plugin URI:           https://www.roadpass.com.br/
 * Description:          Adiciona as opções de pagamento Pagare ao WooCommerce
 * Author:               Mateus Costa
 * Author URI:           https://costamateus.com.br/
 * Version:              1.2.2
 * Text Domain:          pagare-gateway
 * RequiresPHP:          7.4
 * RequiresWP:           6.0
 * WC requires at least: 6.3
 * WC tested up to:      6.9
 *
 * @package WooCommerce_Pagare
 */
defined( "ABSPATH" ) || exit;

// Plugin constants.
define( "WC_PAGARE_VERSION", "1.2.2" );
define( "WC_PAGARE_PLUGIN_FILE", __FILE__ );
define( "WC_PAGARE_PLUGIN_DOMAIN", "pagare-gateway" );

if ( ! class_exists( "WC_Pagare" ) )
{
	include_once dirname( WC_PAGARE_PLUGIN_FILE ) . "/includes/class-wc-pagare.php";

	add_action( "plugins_loaded", [ "WC_Pagare", "init" ] );

	add_filter( "cron_schedules", "pagare_cron_schedules" );
	function pagare_cron_schedules( $schedules )
	{
		$schedules[ "everythreeminute" ] = [
			"interval" => 180, // time in seconds
			"display"  => "Every Three Minute"
		];

		return $schedules;
	}

	/**
	 * Register activation hook.
	 * Register activation hook by invoking activate in WC_Pagare class.
	 *
	 * @param string   $file     path to the plugin file.
	 * @param callback $function The function to be run when the plugin is activated.
	 */
	register_activation_hook( WC_PAGARE_PLUGIN_FILE, [ "WC_Pagare", "activate" ] );

	/**
	 * Register deactivation hook.
	 * Register deactivation hook by invoking deactivate in WC_Pagare class.
	 *
	 * @param string   $file     path to the plugin file.
	 * @param callback $function The function to be run when the plugin is deactivated.
	 */
	register_deactivation_hook( WC_PAGARE_PLUGIN_FILE, [ "WC_Pagare", "deactivate" ] );
}
