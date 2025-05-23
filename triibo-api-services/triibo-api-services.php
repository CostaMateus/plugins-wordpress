<?php

/**
 * Plugin Name: 			#Triibo_API_Services
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Disponibiliza os serviços de API da Triibo (Gateway/Node/PHP) em um único plugin. Demais plugins dependerão deste.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				2.0.0
 * Text Domain: 			triibo-api-services
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package Triibo_Api_Services
 *
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

if ( !in_array( needle: "woocommerce/woocommerce.php", haystack: apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) ) ) )
	return;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( constant_name: "TRIIBO_API_SERVICES_VERSION", value: "2.0.0" );
define( constant_name: "TRIIBO_API_SERVICES_FILE",    value: __FILE__ );

/**
 * The core plugin class that is used to define admin-specific hooks.
 */
require plugin_dir_path( file: __FILE__ ) . "includes/class-triibo-api-services.php";

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 	1.8.0 	Added HPOS compatibility declaration
 * @since 	1.0.0
 */
function run_triibo_api_services()
{
	// Woo HPOS
	add_action( hook_name: "before_woocommerce_init", callback: function () : void
	{
		if ( defined( constant_name: "WC_VERSION" ) && version_compare( version1: WC_VERSION, version2: "7.1", operator: "<" ) )
			return;

		if ( class_exists( class: \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) )
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				feature_id            : "custom_order_tables",
				plugin_file           : TRIIBO_API_SERVICES_FILE,
				positive_compatibility: true
			);
	} );

	$plugin = new Triibo_Api_Services();
	$plugin->run();
}

run_triibo_api_services();
