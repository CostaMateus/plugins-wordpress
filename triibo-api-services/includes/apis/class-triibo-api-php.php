<?php

/**
 * The file that defines the Triibo PHP API.
 *
 * A class definition that includes attributes and functions used for access the Triibo PHP API.
 *
 * @author 	Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 2.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Api_Php extends Triibo_Api
{
	/**
	 * Endpoint for api /freightcalculation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FREIGHT_CALC = "/api/marketplace/wc/freightcalculation";

	/**
	 * Endpoint for api /updatesku/.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const UPDATE_SKU = "/api/marketplace/wc/updatesku/";

	/**
	 * Endpoint for api /ordercreation/.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const O_CREATION = "/api/marketplace/wc/ordercreation/";

	/**
	 * Endpoint for api /orderapproval/.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const O_APPROVAL = "/api/marketplace/wc/orderapproval/";

	/**
	 * Endpoint for api /ordercancellation/.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const O_CANCELLATION = "/api/marketplace/wc/ordercancellation/";

	/**
	 * Internal ID for log.
	 *
	 * @since 2.0.0 	Same visibility as the parent
	 * @since 1.11.0 	Updated string format
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected string $id = "triibo-api-php";

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
	 * @since 1.11.0
	 *
	 * @var null|array
	 */
    protected ?array $header = null;

	/**
	 * Construct.
	 *
	 * @since 2.0.0 	Added $seller parameter
	 * @since 1.11.0 	Added global header
	 * @since 1.10.0 	Removed $log class property
	 * @since 1.0.0
	 */
    public function __construct( string $seller = "fast_shop" )
    {
        $this->status = get_option( option: "triibo_api_services_php_status" );
		$this->env    = get_option( option: "triibo_api_services_php_env"    );

        if ( $this->status() )
        {
			$this->url    = ( get_home_url() === "http://shopkeeper.local" )
							? "http://127.0.0.1:8000"
							: "http://php" . ( $this->env() ? "-hml" : "" ) . ".triibo.com.br";

			$this->header = [
				"Accept: application/json",
				"Content-Type: application/json",
				"api-key: "   . get_option( option: ( $this->env() ? "triibo_api_services_php_hml_key" : "triibo_api_services_php_prd_key" ) ),
				"api-token: " . get_option( option: ( $this->env() ? "triibo_api_services_php_hml_tkn" : "triibo_api_services_php_prd_tkn" ) ),
				"seller: {$seller}"
			];
        }
    }

	/**
	 * Make request.
	 *
	 * @since 2.0.0 	Refactored and changed parameter to $query from $url_params
	 * @since 1.0.0
	 *
	 * @param string 	$endpoint 	Endpoint url
	 * @param array 	$params 	Body of request
	 * @param string 	$query 		Query params
	 *
	 * @return array
	 */
    private function call( string $endpoint, array $params = [], string $query = "" ) : array
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

		return $this->exec(
			endpoint: $this->url . $endpoint . $query,
			header  : $this->get_header(),
			params  : $params
		);
    }

	/**
	 * Call /api/marketplace/wc/freightcalculation endpoint.
	 *
	 * @since 2.0.0 	Removed $seller parameter, moved to __construct
	 * @since 1.11.0 	Added $seller param.
	 * @since 1.0.0
	 *
	 * @param array 	$params 	Params of request.
	 *
	 * @return array
	 */
	public function freight_calculation( array $params ) : array
	{
		return $this->call( endpoint: self::FREIGHT_CALC, params: $params );
	}

	/**
	 * Call /api/marketplace/wc/updatesku/{sku} endpoint.
	 *
	 * @since 2.0.0 	Removed $seller parameter, moved to __construct
	 * @since 	1.11.0 	Added $seller param.
	 * @since 	1.0.0
	 *
	 * @param 	string 	$sku 	SKU of product.
	 *
	 * @return 	array
	 */
	public function update_sku( string $sku ) : array
	{
		return $this->call( endpoint: self::UPDATE_SKU, query: $sku );
	}

	/**
	 * Call /api/marketplace/wc/ordercreation/{$order_id} endpoint.
	 *
	 * @since 2.0.0 	Removed $seller parameter, moved to __construct
	 * @since 	1.11.0 	Added $seller param.
	 * @since 	1.0.0
	 * @param 	string 	$order_id 	Id of order.
	 * @return 	array
	 */
	public function order_creation( string $order_id ) : array
	{
		return $this->call( endpoint: self::O_CREATION, query: $order_id );
	}

	/**
	 * Call /api/marketplace/wc/orderapproval/{$order_id} endpoint.
	 *
	 * @since 2.0.0 	Removed $seller parameter, moved to __construct
	 * @since 1.11.0 	Added $seller param.
	 * @since 1.0.0
	 *
	 * @param string 	$order_id 	Id of order.
	 *
	 * @return array
	 */
	public function order_approval( string $order_id ) : array
	{
		return $this->call( endpoint: self::O_APPROVAL, query: $order_id );
	}

	/**
	 * Call /api/marketplace/wc/ordercancellation/{$order_id} endpoint.
	 *
	 * @since 2.0.0 	Removed $seller parameter, moved to __construct
	 * @since 1.11.0 	Added $seller param.
	 * @since 1.0.0
	 *
	 * @param string 	$order_id 	Id of order.
	 *
	 * @return array
	 */
	public function order_cancellation( string $order_id ) : array
	{
		return $this->call( endpoint: self::O_CANCELLATION, query: $order_id );
	}
}
