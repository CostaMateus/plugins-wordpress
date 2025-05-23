<?php

/**
 * The file that defines the Triibo Node API
 *
 * A class definition that includes attributes and functions used for access the Triibo Node API.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Api_Node extends Triibo_Api
{
	/**
	 * Endpoint for api /auth.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AUTH           = "/auth";

	/**
	 * Endpoint for api /auth/marketplace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AUTH_MKT       = "/auth/marketplace";

	/**
	 * Endpoint for api /authcode.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	const AUTH_CODE      = "/authCode";

	/**
	 * Endpoint for api /login.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
    const LOGIN          = "/login";

	/**
	 * Endpoint for api /payments/brand.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAY_BRAND      = "/payments/brand";

	/**
	 * Endpoint for api /payments/doPayment.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAYMENT        = "/payments/doPayment";

	/**
	 * Endpoint for api /payments/status.
	 *
	 * @since 1.1.4
	 *
	 * @var string
	 */
	const PAYMENT_STATUS = "/payments/status";

	/**
	 * Endpoint for api /payments/checkToken.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAY_TKN_CHECK  = "/payments/checkToken";

	/**
	 * Endpoint for api /payments/createToken.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAY_TKN_CREATE = "/payments/createToken";

	/**
	 * Endpoint for api /payments/deleteToken.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAY_TKN_DELETE = "/payments/deleteToken";

	/**
	 * Endpoint for api /sms/validateSMS.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	const SMS_VALIDATE   = "/sms/validateSMS";

	/**
	 * Endpoint for api /users/findUIDsByPhones.
	 *
	 * @since 1.1.3
	 *
	 * @var string
	 */
	const USER_FIND_UID  = "/users/findUIDsByPhones";

	/**
	 * Endpoint for api /checkout/users/addingUserSignature.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
    const ADD_USER_SUBS  = "/checkout/users/addingUserSignature";

	/**
     * Endpoint for api /checkout/users/removingUserSignature.
	 *
	 * @since 1.9.0
	 *
	 * @var string
	 */
    const DEL_USER_SUBS  = "/checkout/users/removeUserSignature";

	/**
	 * Internal ID for log
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.11.0 	Updated string format
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $id = "triibo-api-node";

	/**
	 * Environment hml/prod.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.0.0
	 *
	 * @var null|string
	 */
	protected ?string $env = null;

	/**
	 * Status of this api.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.0.0
	 *
	 * @var null|string
	 */
	protected ?string $status = null;

	/**
	 * Endpoint hml/prod.
	 *
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $url = null;

	/**
	 * Header for api calls.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.4.0
	 *
	 * @var null|array
	 */
    protected ?array $header = null;

    /**
     * Construct.
     *
     * @since 2.0.0 	Refactored
	 * @since 1.10.0 	Removed $log class property
	 * @since 1.9.1 	Fixed homologation API URL
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->status = get_option( option: "triibo_api_services_node_status" );
		$this->env    = get_option( option: "triibo_api_services_node_env"    );

        if ( $this->status() )
        {
			$this->url    = ( get_home_url() === "http://localhost:8000" )
							? "http://localhost:3000"
							: "https://api" . ( $this->env() ? "-hml-node" : "" ) . ".triibo.com.br";

			$this->header = [
				"origin: https://" . ( $this->env() ? "dev-" : "" ) . "marketplace.triibo.com.br",
				"content-type: application/json",
			];
        }
    }

	/**
	 * Make request.
	 *
     * @since 2.0.0     Refactored
     * @since 1.4.0 	Added $method param
     * @since 1.0.0
	 *
	 * @param string 	$endpoint 	Endpoint url
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Body of request
	 * @param string 	$method 	Method of request
	 *
	 * @return array
	 */
    private function call( string $endpoint, array $params = [], string $token = "", string $method = "POST" ) : array
    {
		if ( ! $this->status() )
		{
			$message = "API is disabled.";

			$this->log(
				is_error: true,
				level   : "critical",
				message : $message,
			);

			return [
				"code"    => 401, // "500.4",
				"success" => false,
				"message" => "API está desabilitada.",
				"error"   => $message,
				"data"    => null,
			];
		}

		if ( $endpoint === self::AUTH )
		{
			$username = ( $this->env() )
				? get_option( option: "triibo_api_services_node_hml_user" )
				: get_option( option: "triibo_api_services_node_prd_user" );

			$password = ( $this->env() )
				? get_option( option: "triibo_api_services_node_hml_pass" )
				: get_option( option: "triibo_api_services_node_prd_pass" );

			$basic    = base64_encode( string: "{$username}:{$password}" );

			$aux      = [ "Authorization: Basic {$basic}" ];

			if ( !empty( $token ) )
				$aux[] = $token;

			return $this->exec(
				endpoint: $this->url . $endpoint,
				header  : array_merge( $this->get_header(), $aux ),
				method  : $method,
			);
		}

		if ( $endpoint === self::AUTH_MKT )
		{
			$key = ( $this->env() )
				? get_option( option: "triibo_api_services_node_hml_auth_key" )
				: get_option( option: "triibo_api_services_node_prd_auth_key" );

			return $this->exec(
				endpoint: $this->url . $endpoint,
				header  : array_merge( $this->get_header(), [
					"Authorization: {$token}",
					"x-api-key: {$key}"
				] )
			);
		}

		return $this->exec(
			endpoint: $this->url . $endpoint,
			header  : array_merge( $this->get_header(), [ "Authorization: Bearer {$token}" ] ),
			params  : $params,
			method  : $method
		);
    }

	/**
	 * Encrypt a string.
	 *
	 * @since 1.4.0
	 *
	 * @param string 	$str
	 * @param string 	$key
	 *
	 * @return null|string
	 */
    private function encrypt( string $str, string $key ) : ?string
    {
		if ( ! $str )
			return null;

		$iv   = hex2bin( string: "00000000000000000000000000000000" );
		$encr = openssl_encrypt(
			data       : $str,
			cipher_algo: "aes-256-cbc",
			passphrase : $key,
			options    : OPENSSL_RAW_DATA,
			iv         : $iv
		);

		return urlencode( string: base64_encode( string: $encr ) );
    }

	/**
	 * Decrypt a string.
	 *
	 * @since 1.4.0
	 *
	 * @param string 	$str
	 * @param string 	$key
	 *
	 * @return null|string
	 */
    private function decrypt( string $str, string $key ) : ?string
    {
		if ( ! $str )
			return null;

		$str = base64_decode( string: urldecode( string: $str ) );
		$iv  = hex2bin( string: "00000000000000000000000000000000" );

		return openssl_decrypt(
			data       : $str,
			cipher_algo: "aes-256-cbc",
			passphrase : $key,
			options    : OPENSSL_RAW_DATA,
			iv         : $iv
		);
    }

	/**
	 * Set security code.
	 *
	 * @since 1.5.0
	 *
	 * @param null|string 	$code
	 *
	 * @return void
	 */
	private function set_security_code( ?string $code = null ) : void
	{
		if ( $code )
			$this->header = array_merge( $this->header, [ "securitycode: {$code}" ] );
	}

	/**
	 * Format cellphone for +55 DDD number.
	 *
	 * @since 1.5.0
	 *
	 * @param string 	$cellphone
	 *
	 * @return string
	 */
	private function format_cellphone( string $cellphone ) : string
	{
        return "+" . preg_replace( pattern: "/\D/", replacement: "", subject: $cellphone );
	}

	/**
	 * Generates token for use in API calls.
	 *
     * @since 2.0.0     Refactored
	 * @since 1.9.0
	 *
     * @param string 	$uid
     * @param int 		$user_id
	 *
	 * @return null|string
	 */
	private function generate_token( string $uid, int $user_id ) : ?string
	{
		$response = $this->auth( token: "uid: {$uid}" );

        if ( ! $response[ "success" ] )
		{
			$this->log(
				is_error: true,
				level   : "error",
				message : "generate_token : Token not generated.",
				context : [
					"code"    => $response[ "code"    ],
					"message" => $response[ "message" ],
					"error"   => $response[ "error"   ]
				]
			);

			return null;
		}

		$token    = $response[ "data" ][ "token" ];

		$validity = ( new DateTime() )->format( format: "Y-m-d H:i:s" );

		update_user_meta(
			user_id   : $user_id,
			meta_key  : "_triibo_auth_token",
			meta_value: $token
		);

		update_user_meta(
			user_id   : $user_id,
			meta_key  : "_triibo_auth_validity",
			meta_value: $validity
		);

		$this->log(
			level  : "info",
			message: "Token generated successfully.",
			context: $response
		);

		return $token;
	}

	/**
	 * Add subscription to user.
	 *
	 * @since 1.9.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of subscription, add
	 *
	 * @return array
	 */
	public function add_subscription( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::ADD_USER_SUBS, params: $params, token: $token );
	}

	/**
	 * Delete subscription to user.
	 *
	 * @since 1.9.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of subscription, del
	 *
	 * @return array
	 */
	public function del_subscription( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::DEL_USER_SUBS, params: $params, token: $token, method: "DELETE" );
	}

	/**
	 * Call /auth endpoint.
	 *
	 * @since 1.5.0 	Added func to set security code
     * @since 1.4.0 	Added param $code
     * @since 1.0.0
	 *
	 * @param string 		$token 	Authorization of request
	 * @param null|string 	$code 	Security code
	 *
	 * @return array
	 */
	public function auth( string $token = "", ?string $code = null ) : array
	{
		$this->set_security_code( code: $code );

		return $this->call( endpoint: self::AUTH, token: $token );
	}

    /**
     * Call /authcode
     *
	 * @since 1.5.0 	Added func to format cellphone
     * @since 1.2.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$data 		Data to pass to parameters for send auth code
	 *
     * @return array
     */
    public function auth_code( string $token, array $data ) : array
    {
		$sms    = ( $data[ "type" ] === "sms" ) ? true : false;
		$wpp    = ( $data[ "type" ] === "wpp" ) ? true : false;

		$params = [
			"cellphone"    => $this->format_cellphone( cellphone: $data[ "cellphone" ] ),
			"platform"     => "web",
			"languageCode" => "pt_BR",
			"sendType"     => [
				"sms"      => $sms,
				"whatsapp" => $wpp,
				"email"    => false,
			]
		];

		return $this->call( endpoint: self::AUTH_CODE, params: $params, token: $token );
    }

	/**
	 * Call /payments/brand endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of brand
	 *
	 * @return array
	 */
	public function brand( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAY_BRAND, params: $params, token: $token );
	}

	/**
	 * Call /payments/checkToken endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of token, check
	 *
	 * @return array
	 */
	public function check_token( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAY_TKN_CHECK, params: $params, token: $token );
	}

	/**
	 * Call /payments/createToken endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of token, create
	 *
	 * @return array
	 */
	public function create_token( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAY_TKN_CREATE, params: $params, token: $token );
	}

	/**
	 * Call /payments/deleteToken endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of token, delete
	 *
	 * @return array
	 */
	public function delete_token( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAY_TKN_DELETE, params: $params, token: $token );
	}

	/**
	 * Call /users/findUIDsByPhones
	 *
	 * @since 1.1.3
	 *
	 * @param string 	$token 	Authorization of request
	 * @param string 	$phone 	Phone number to search
	 *
	 * @return array
	 */
	public function find_uid( string $token, string $phone ) : array
	{
		return $this->call( endpoint: self::USER_FIND_UID, params: [ "phones" => [ $phone ] ], token: $token );
	}

	/**
	 * Call /auth endpoint, to get security code for validation.
	 *
	 * @since 1.4.0
	 *
	 * @return array
	 */
	public function get_security_code() : array
	{
		$response = $this->call( endpoint: self::AUTH, method: "GET" );

		if ( ! $response[ "success" ] )
			return $response;

		$key64    = get_option( option: "_triibo_security_code" ); // inserted manually in DB.
		$key      = base64_decode( string: $key64 );

		$code     = $response[ "data" ][ "securityCode" ];

		$decrypt  = $this->decrypt( str: $code, key: $key );
		$decrypt  = json_decode( json: $decrypt );

		$decrypt->code *= 3;

		$encrypt  = $this->encrypt( str: json_encode( value: $decrypt ), key: $key );

		$response[ "data" ][ "securityCode" ] = $encrypt;

		return $response;
	}

	/**
	 * Get valid token.
	 *
	 * @since 1.9.0
	 *
     * @param int 	$user_id
	 *
	 * @return null|string
	 */
	public function get_token( int $user_id ) : ?string
	{
		$phone    = get_user_meta( user_id: $user_id, key: "_triibo_phone", single: true );
		$phone    = $phone ?: get_user_meta( user_id: $user_id, key: "triiboId_phone", single: true );

		$uid      = get_user_meta( user_id: $user_id, key: "_triibo_id",            single: true );
		$token    = get_user_meta( user_id: $user_id, key: "_triibo_auth_token",    single: true );
		$validity = get_user_meta( user_id: $user_id, key: "_triibo_auth_validity", single: true );

		if ( ! $uid )
		{
			$success       = false;
			$response_auth = $this->auth();

			if ( $response_auth[ "success" ] && $phone )
			{
				$this->log(
					level  : "info",
					message: "find_uid : search user by phone '{$phone}'",
					context: $response_auth
				);

				$response_find = $this->find_uid( token: $response_auth[ "data"  ][ "token" ], phone: $phone );

				if ( $response_find[ "success" ] )
				{
					$uid = $response_find[ "data" ][ "usersUid" ][ $phone ];

					$this->log(
						level  : "info",
						message: "find_uid : user found '{$uid}'",
						context: $response_find
					);

					if ( $uid )
					{
						$success = true;
						$token   = null;

						update_user_meta( user_id: $user_id, meta_key: "_triibo_id", meta_value: $uid );
					}
				}
			}

			if ( ! $success )
				return null;
		}

		if ( ! $token )
		{
			$this->log(
				level  : "info",
				message: "validate_token : user without token {$user_id}",
				context: [
					"uid"   => $uid,
					"phone" => $phone,
				]
			);

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		$dtval = DateTime::createFromFormat( format: "Y-m-d H:i:s", datetime: $validity );
		$now   = new DateTime();
		$diff  = $now->diff( targetObject: $dtval );

		if ( $diff->d > 0 || $diff->h > 10 )
		{
			$this->log(
				level  : "info",
				message: "validate_token : expired token {$user_id}",
				context: [
					"uid"   => $uid,
					"phone" => $phone,
				]
			);

			return $this->generate_token( uid: $uid, user_id: $user_id );
		}

		return $token;
	}

	/**
	 * Get user triibo, create if not exists.
	 *
     * @since 2.0.0     Refactored
     * @since 1.5.0
	 *
	 * @param string 	$cellphone 	Cellphone of user
	 *
	 * @return array
	 */
	public function get_user( string $cellphone ) : array
	{
        $response = $this->get_security_code();

        if ( ! $response[ "success" ] )
			return $response;

		$response = $this->auth( code: $response[ "data" ][ "securityCode" ] );

        if ( ! $response[ "success" ] )
			return $response;

		$params   = [ "cellphone" => $this->format_cellphone( cellphone: $cellphone ) ];
		$token    = $response[ "data" ][ "token" ];

		return $this->call( endpoint: self::LOGIN, params: $params, token: $token );
	}

	/**
	 * Call /payments/doPayment endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params 	Parameters of payment
	 *
	 * @return array
	 */
	public function payment( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAYMENT, params: $params, token: $token );
	}

	/**
	 * Call /payments/status endpoint.
	 *
	 * @since 1.1.4
	 *
	 * @param string 	$token 		Authorization of request
	 * @param array 	$params
	 *
	 * @return array
	 */
	public function payment_status( string $token, array $params ) : array
	{
		return $this->call( endpoint: self::PAYMENT_STATUS, params: $params, token: $token );
	}

	/**
	 * Validates SMS code.
	 *
     * @since 2.0.0     Refactored
	 * @since 1.5.0 	Removed param $token
	 *
	 * @param array 	$data 	Data to pass to parameters for validation of SMS code
	 *
	 * @return array
	 */
	public function validate_sms( array $data ) : array
	{
        $response = $this->get_security_code();

        if ( ! $response[ "success" ] )
			return $response;

		$response = $this->auth( code: $response[ "data" ][ "securityCode" ] );

        if ( ! $response[ "success" ] )
			return $response;

		$params = [
			"code"          => $data[ "code"     ],
			"transactionId" => $data[ "trans_id" ],
			"cellphone"     => $this->format_cellphone( cellphone: $data[ "cellphone" ] ),
		];
		$token  = $response[ "data"  ][ "token" ];

		return $this->call( endpoint: self::SMS_VALIDATE, params: $params, token: $token );
	}

	/**
	 * Call /auth/marketplace endpoint.
	 *
     * @since 1.0.0
	 *
	 * @param string 	$token 		Authorization of request
	 *
	 * @return array
	 */
    public function validate_token_auth_app( string $token ) : array
    {
		return $this->call( endpoint: self::AUTH_MKT, token: $token );
    }
}
