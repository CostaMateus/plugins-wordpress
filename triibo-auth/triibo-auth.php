<?php
/**
 * Plugin Name: 			#Triibo_Auth
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Disponibiliza o login pela Triibo, força o login Triibo antes do checkout, login oriundo do Triibo App. Dependências: Triibo API Services e WooCommerce.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				2.3.0
 * Text Domain: 			triibo-auth
 * Requires Plugins:        triibo-api-services, woocommerce
 * Requires at least: 		6.6
 * Requires PHP: 			8.0
 * WC requires at least: 	8.7.1
 * WC tested up to: 		9.8.0
 *
 * @package 	Triibo_Auth
 * @version 	2.3.0
 */
defined( constant_name: "ABSPATH" ) || exit;

if ( !in_array( needle: "woocommerce/woocommerce.php", haystack: apply_filters( hook_name: "active_plugins", value: get_option( option: "active_plugins" ) ) ) )
	return;

// Plugin constants.
define( constant_name: "TRIIBO_AUTH_ID",           value: "triibo-auth"          );
define( constant_name: "TRIIBO_AUTH_APP",          value: "triibo-auth-app"      );
define( constant_name: "TRIIBO_AUTH_CHECKOUT",     value: "triibo-auth-checkout" );
define( constant_name: "TRIIBO_AUTH_LOGIN",        value: "triibo-auth-login"    );
define( constant_name: "TRIIBO_AUTH_DOMAIN",       value: "triibo-auth"          );
define( constant_name: "TRIIBO_AUTH_VERSION",      value: "2.3.0"                );
define( constant_name: "TRIIBO_AUTH_PLUGIN_FILE",  value: __FILE__               );

if ( !class_exists( class: "Triibo_Auth" ) )
{
	include_once dirname( path: __FILE__ ) . "/includes/class-triibo-auth.php";
	add_action( hook_name: "plugins_loaded", callback: [ "Triibo_Auth", "get_instance" ] );
}

/**
 * @since 1.0.0
 *
 * @param mixed $user
 *
 * @return mixed
 */
function triibo_auth_json_basic_auth_handler( mixed $user ) : mixed
{
	global $wp_json_basic_auth_error;

	$wp_json_basic_auth_error = null;

	// Don't authenticate twice
	if ( !empty( $user ) )
		return $user;

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER[ "PHP_AUTH_USER" ] ) )
		return $user;

	$username = $_SERVER[ "PHP_AUTH_USER" ];
	$password = $_SERVER[ "PHP_AUTH_PW"   ];

	remove_filter( hook_name: "determine_current_user", callback: "triibo_auth_json_basic_auth_handler", priority: 20 );

	$user     = wp_authenticate( username: $username, password: $password );

	add_filter( hook_name: "determine_current_user", callback: "triibo_auth_json_basic_auth_handler", priority: 20 );

	if ( is_wp_error( thing: $user ) )
	{
		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;

	return $user->ID;
}

/**
 * @since 1.0.0
 *
 * @param mixed $error
 *
 * @return mixed
 */
function triibo_auth_json_basic_auth_error( mixed $error ) : mixed
{
	if ( !empty( $error ) )
		return $error;

	global $wp_json_basic_auth_error;
	return $wp_json_basic_auth_error;
}

add_filter( hook_name: "determine_current_user", callback: "triibo_auth_json_basic_auth_handler", priority: 20 );
add_filter( hook_name: "rest_authentication_errors", callback: "triibo_auth_json_basic_auth_error" );
