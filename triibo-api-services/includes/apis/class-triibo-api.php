<?php

/**
 * The file that defines the Triibo API
 *
 * A class definition that includes attributes and functions used for access the Triibo API.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

abstract class Triibo_Api
{
	/**
	 * Http codes
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const HTTP_CODES = [
		// [Informational 1xx]
		100 => "Continue",
		101 => "Switching Protocols",

		// [Successful 2xx]
		200 => "OK",
		201 => "Created",
		202 => "Accepted",
		203 => "Non-Authoritative Information",
		204 => "No Content",
		205 => "Reset Content",
		206 => "Partial Content",

		// [Redirection 3xx]
		300 => "Multiple Choices",
		301 => "Moved Permanently",
		302 => "Found",
		303 => "See Other",
		304 => "Not Modified",
		305 => "Use Proxy",
		306 => "(Unused)",
		307 => "Temporary Redirect",

		// [Client Error 4xx]
		400 => "Bad Request",
		401 => "Unauthorized",
		402 => "Payment Required",
		403 => "Forbidden",
		404 => "Not Found",
		405 => "Method Not Allowed",
		406 => "Not Acceptable",
		407 => "Proxy Authentication Required",
		408 => "Request Timeout",
		409 => "Conflict",
		410 => "Gone",
		411 => "Length Required",
		412 => "Precondition Failed",
		413 => "Request Entity Too Large",
		414 => "Request-URI Too Long",
		415 => "Unsupported Media Type",
		416 => "Requested Range Not Satisfiable",
		417 => "Expectation Failed",

		// [Server Error 5xx]
		500 => "Internal Server Error",
		501 => "Not Implemented",
		502 => "Bad Gateway",
		503 => "Service Unavailable",
		504 => "Gateway Timeout",
		505 => "HTTP Version Not Supported",
	];

	/**
	 * @since 2.0.0
	 *
	 * @var string
	 */
    protected string $id = "triibo-api";

	/**
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
    protected ?string $env = null;

	/**
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $key = null;

	/**
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $status = null;

	/**
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $token = null;

	/**
	 * @since 2.0.0
	 *
	 * @var null|string
	 */
	protected ?string $url = null;

	/**
	 * @since 2.0.0
	 *
	 * @var null|array
	 */
    protected ?array $header = null;

	/**
	 * Provides basic header for all calls.
	 *
     * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_header() : array
	{
		return $this->header;
	}

    /**
     * Check if $attr is empty.
     *
	 * @since 2.0.0
	 *
     * @param string $attr
	 *
     * @return bool
     */
	public function empty( string $attr ) : bool
	{
		if ( ! property_exists( object_or_class: $this, property: $attr ) )
			return false;

		if ( ! isset( $this->{$attr} ) )
			return false;

		if ( is_array( value: $this->{$attr} ) )
			return empty( $this->{$attr} );

		if ( is_string( value: $this->{$attr} ) )
			return empty( trim( string: $this->{$attr} ) );

		if ( is_bool( value: $this->{$attr} ) )
			return empty( $this->{$attr} );

		return empty( $this->{$attr} );
	}

    /**
     * Returns if the php api is configured for homol or prod.
     * TRUE  for homol.
     * FALSE for prod.
     *
     * @since 2.0.0
	 *
     * @return boolean
     */
	public function env() : bool
	{
		return $this->env === "on";
	}

	/**
	 * Make request to the API
	 *
     * @since 2.0.0     Refactored to be a non-static method, utilizing the `$this->log()` method and the `$id` and `$env` properties.
	 * @since 1.6.0 	Added try-catch
	 * @since 1.4.0 	Added $method parameter
	 * @since 1.0.0
	 *
	 * @param string 	$endpoint 	Endpoint url
	 * @param array 	$header		Header of request
	 * @param array 	$params 	Body of request
	 * @param string 	$method 	Method of request
	 *
	 * @return array
	 */
    public function exec( string $endpoint, array $header, array $params = [], string $method = "POST" ) : array
    {
		$default_message = "Falha na comunicação com a API.";

		$data_log = [
			"method"   => $method,
			"endpoint" => $endpoint,
			"header"   => $header,
			"params"   => $params,
		];

		try
		{
			$curl = curl_init();

			if ( ! $curl )
			{
				$message = "cURL initialization failed";

				$this->log(
					is_error: true,
					level   : "error",
					message : $message,
					context : [ "data" => $data_log ]
				);

				return [
					"code"    => 500, // "500.1",
					"success" => false,
					"message" => $default_message,
					"error"   => $message,
				];
			}

			$params = ( ! empty( $params ) ) ? json_encode( value: $params ) : null;

			curl_setopt_array(
				handle : $curl,
				options: [
					CURLOPT_URL            => $endpoint,
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_ENCODING       => "",
					CURLOPT_MAXREDIRS      => 5,
					CURLOPT_TIMEOUT        => 60,
					CURLOPT_FOLLOWLOCATION => TRUE,
					CURLOPT_SSL_VERIFYPEER => TRUE,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => $method,
					CURLOPT_POSTFIELDS     => $params,
					CURLOPT_HTTPHEADER     => $header,
				]
			);

			$response = curl_exec( handle: $curl );
			$info     = curl_getinfo( handle: $curl );

			$data_log[ "info" ] = $info;

			if ( empty( $response ) )
			{
				$message = "cURL Error: " . curl_error( handle: $curl );

				$this->log(
					is_error: true,
					level   : "error",
					message : $message,
					context : [ "data" => $data_log ]
				);

				curl_close( handle: $curl );

				return [
					"code"    => 400, // "500.2",
					"success" => false,
					"message" => $default_message,
					"error"   => $message,
					"data"    => null,
				];
			}

			$decoded  = json_decode( json: $response, associative: true );

			$data_log[ "response" ] = $decoded;

			curl_close( handle: $curl );

			if ( empty( $info[ "http_code" ] ) )
			{
				$message = "cURL Error: No HTTP code was returned.";

				$this->log(
					is_error: true,
					level   : "error",
					message : $message,
					context : [ "data" => $data_log ]
				);

				return [
					"code"    => 400, // "500.3",
					"success" => false,
					"message" => $default_message,
					"error"   => $message,
					"data"    => $decoded,
				];
			}

			$code = $info[ "http_code" ];
			$url  = $info[ "url"       ];

			if ( ! isset( self::HTTP_CODES[ $code ] ) || $code < 200 || $code >= 300 )
			{
				$error   = self::HTTP_CODES[ $code ] ?? "Código HTTP desconhecido: {$code}";
				$message = "cURL Error: {$code} - {$url} - {$error}";

				$this->log(
					is_error: true,
					level   : "error",
					message : $message,
					context : [ "data" => $data_log ]
				);

				return [
					"code"    => $code,
					"success" => false,
					"message" => $default_message,
					"error"   => $message,
					"data"    => $decoded,
				];
			}

			$message = "cURL Success: {$code} - {$url}";

			$this->log(
				level  : "info",
				message: $message,
				context: [ "data" => $data_log ]
			);

			return [
				"code"    => $code,
				"success" => true,
				"message" => "OK - {$message}",
				"error"   => null,
				"data"    => $decoded,
			];
		}
		catch ( Exception $e )
		{
			$data_log[ "trace" ] = $e->getTrace();
			$message = "cURL Exception: {$e->getMessage()}";

			$this->log(
				is_error: true,
				level   : "error",
				message : $message,
				context : [ "data" => $data_log ]
			);

			return [
				"code"    => 500, // "500",
				"success" => false,
				"message" => "Falha na comunicação com a API. Exception.",
				"error"   => $message,
				"data"    => null,
			];
		}
    }

    /**
     * Returns the api status.
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since 2.0.0
	 *
     * @return boolean
     */
	public function status() : bool
	{
		return $this->status === "on";
	}

	/**
     * Log.
     *
     * @since 2.0.0
	 *
     * @param string 	$level 		The log level (e.g., 'error', 'info')
     * @param string 	$message 	The log message
	 * @param array 	$context 	Additional context for the log message
     * @param bool 		$is_error 	Whether the log is an error
	 *
     * @return void
     */
    public function log( string $level = "info", string $message, array $context = [], bool $is_error = false ) : void
    {
		// Use WooCommerce logger if available
        $logger  = function_exists( function: "wc_get_logger" )
			? wc_get_logger()
			: new WC_Logger();

        $context = array_merge( $context, [ "source" => $this->id ] );

        // Log only errors or if the environment is set to "on" (homologation)
        if ( $is_error || $this->env === "on" )
            $logger->$level( $message, $context );
    }
}