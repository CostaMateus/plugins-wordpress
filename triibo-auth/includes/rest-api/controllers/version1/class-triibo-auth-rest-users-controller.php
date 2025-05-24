<?php

/**
 * REST API Users controller
 * Handles requests to the /users endpoint.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Auth_Rest_Users_Controller extends WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = "triibo-rest/v1";

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = "users";

	/**
	 * Stores the request.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_REST_Request
	 */
	protected $request = [];

	/**
	 * ID of resource.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $id = TRIIBO_AUTH_APP;

	/**
	 * API Gateway.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Gateway
	 */
	protected $gate;

	/**
	 * API Node.
	 *
	 * @since 1.0.0
	 *
	 * @var Triibo_Api_Node
	 */
	protected $node;

	/**
	 * Construct
	 *
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->gate = new Triibo_Api_Gateway();
		$this->node = new Triibo_Api_Node();
	}

	/**
	 * Register the routes for users.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() : void
	{
		register_rest_route(
			route_namespace: $this->namespace,
			route          : "/$this->rest_base/login",
			args           : [
				[
					"methods"             => WP_REST_Server::READABLE, // WP_REST_Server::EDITABLE, // POST, PUT, PATCH
					"callback"            => [ $this, "process_login" ],
					"permission_callback" => "__return_true",
					"args"                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * Process login for user
	 *
	 * @since 1.1.1 	New param $code == uid
	 * @since 1.1.0 	New flow
     * @since 1.0.1 	Log improvement
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request 	$request
	 *
	 * @return void
	 */
	public function process_login( WP_REST_Request $request ) : void
	{
		$url = $request->get_param( key: "url" ) ?: null;

		$this->log(
			is_error: true,
			level   : "info",
			message : "Request to process_login",
			context : [ "params" => $request->get_params(), ]
		);

		if ( ! $this->status() )
		{
			wp_clear_auth_cookie();

			$this->log(
				is_error: true,
				level   : "info",
				message : "Redirecting only, disabled auth-app module.",
				context : []
			);

			$this->redirect( url: $url );
			return;
		}

		/**
		 * Gate: false === prod
		 * Node: false === prod
		 */
		if ( $this->gate->env() === false && $this->node->env() === false )
		{
			$token  = $request->get_param( key: "mkt" ) ?: null;
			$bearer = $request->get_header( key: "authorization" );

			/**
			 * ios     - manda apenas bearer
			 * android - manda apenas token
			 *
			 * ausencia dos dois é problema
			 */
			if ( ! $bearer && ! $token )
			{
				$this->redirect( url: $url );
				return;
			}

			if ( ! $this->validate_token( bearer: $bearer, tkn: $token ) )
			{
				$this->redirect( url: $url );
				return;
			}
		}

		$user      = null;
		$uid       = $request->get_param( key: "code"      ) ?: null;
		$data      = $request->get_param( key: "data"      ) ?: null;
		$cellphone = $request->get_param( key: "cellphone" ) ?: null;

        $cellphone = "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );
		$cellphone = $cellphone === "+" ? null : $cellphone;

		if ( ! $uid  && ! $cellphone && ! $data )
		{
			$this->redirect( url: $url );
			return;
		}

		if ( $uid )
		{
			set_transient( transient: "_taa_triibo_uid", value: $uid, expiration: 1800 );

			$user = get_users(
				args: [
					"meta_key"   => "_triibo_id",
					"meta_value" => $uid
				]
			);

			if ( ! empty( $user ) )
			{
				$user = reset( array: $user );

				$this->make_login( user_id: $user->ID, url: $url );
				return;
			}

			setcookie(
				name              : "taa_code",
				value             : $uid,
				expires_or_options: time() + 60 * 30,
				path              : "/",
				domain            : parse_url( url: get_site_url(), component: PHP_URL_HOST )
			);
		}

		if ( $cellphone )
		{
			set_transient( transient: "_taa_triibo_cellphone", value: $cellphone, expiration: 1800 );

			$user = get_users(
				args: [
					"meta_key"   => "triiboId_phone",
					"meta_value" => $cellphone
				]
			);

			if ( ! empty( $user ) )
			{
				$user = reset( array: $user );

				delete_user_meta( user_id: $user->ID, meta_key: "triiboId_phone", meta_value: $cellphone );
				update_user_meta( user_id: $user->ID, meta_key: "_triibo_phone",  meta_value: $cellphone );
				update_user_meta( user_id: $user->ID, meta_key: "_triibo_id",     meta_value: $uid       );

				$this->make_login( user_id: $user->ID, url: $url );
				return;
			}
		}

		if ( $data )
		{
			$arr     = explode( separator: "|", string: $data );
			$email   = $arr[ 1 ];
			$user_id = email_exists( email: $email );

			if ( $user_id )
			{
				$this->redirect( url: $url, params: "taac=acc&email={$email}" );
				return;
			}
		}

		wp_clear_auth_cookie();
		$this->redirect( url: $url );
		return;
	}

    /**
     * Returns the Triibo_Auth_App status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since 1.0.0
	 *
     * @return boolean
     */
	private function status() : bool
	{
		return Triibo_Auth_App::status();
	}

	/**
	 * Redirect user
	 *
	 * @since 1.0.0
	 *
	 * @param null|string 	$url
	 * @param null|string 	$params
	 *
	 * @return void
	 */
	private function redirect( ?string $url = null, ?string $params = null ) : void
	{
		if ( $url )
		{
			if ( ( strpos( haystack: $url, needle: "?" ) ) !== false && $params )
				$url .= "&$params";
			elseif ( $params )
				$url .= "?$params";
		}
		else
		{
			$url = home_url();
		}

		wp_safe_redirect( location: esc_url_raw( url: $url ) );
		exit();
	}

	/**
	 * Make login for user
	 *
	 * @since 1.0.0
	 *
	 * @param int 		$user_id
	 * @param string 	$url
	 *
	 * @return void
	 */
	private function make_login( int $user_id, string $url ) : void
	{
		clean_user_cache( user: $user_id );
		wp_clear_auth_cookie();
		wp_set_current_user( id: $user_id );
		wp_set_auth_cookie( user_id: $user_id, remember: true );

		$this->redirect( url: $url );
	}

	/**
	 * Validates the token
	 *
     * @since 1.0.1 	Log improvement
	 * @since 1.0.0
	 *
	 * @param string 	$bearer
	 * @param string 	$tkn
	 *
	 * @return bool
	 */
	private function validate_token( string $bearer, ?string $tkn = null ) : bool
	{
		$bearer   = ( ! $bearer && $tkn ) ? base64_decode( string: $tkn ) : $bearer;

		$response = $this->node->validate_token_auth_app( token: $bearer );

		if ( ! $response[ "success" ] || ! isset( $response[ "data" ] ) || ! $response[ "data" ][ "isValid" ] )
		{
			$response[ "data_sent" ] = [ "function" => "validate_token" ];

			$this->log(
				is_error: true,
				level   : "error",
				message : "validate_token : Error validating token",
				context : [
					"bearer"   => $bearer,
					"token"    => $tkn,
					"response" => $response
				]
			);

			return false;
		}

		return true;
	}

	/**
	 * Register log.
	 *
	 * @since 2.3.0 	Refactored
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
		$logger  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$context = array_merge( $context, [ "source" => $this->id ] );

		if ( $is_error )
			$logger->$level( $message, $context );
	}
}
