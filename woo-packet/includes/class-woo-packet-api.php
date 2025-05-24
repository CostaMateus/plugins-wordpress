<?php
/**
 * Plugin's API class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package Woo_Packet/Classes
 * @since   1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Woo_Packet_Api class.
 */
class Woo_Packet_Api
{
	private $id         = WOO_PACKET_DOMAIN;

	private $api        = null;

    private $url        = "https://api.sunlogcargo.com/declaracao/"; // hardcoded
    private $header     = [
        "Content-Type: text/plain",
        "Cookie: PHPSESSID=0b73a14e5831d36f3c43daf6ff6cd6e8",
    ];

    private $user       = null;
    private $userId     = null;
    private $userPsw    = null;

    private $log        = null;
    private $status     = null;

    private $tagPrefix  = null;
    private $shopName   = null;
    private $shopEmail  = null;
    private $shopPhone  = null;
    private $shopAddr   = null;
    private $shopComplt = null;
    private $shopNumber = null;
    private $shopState  = null;
    private $shopCity   = null;
    private $shopCep    = null;

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

    public function __construct()
    {
		$this->log    = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
        $this->api    = new Woo_Packet_Webservice();

        $this->status = get_option( "woo_packet_api_status" );

        if ( $this->status == "on" )
        {
            $this->user       = get_option( "woo_packet_api_user"            );
            $this->userId     = get_option( "woo_packet_api_user_id"         );
            $this->userPsw    = get_option( "woo_packet_api_user_pass"       );

			$this->tagPrefix  = get_option( "woo_packet_tag_prefix"          );
			$this->shopName   = get_option( "woo_packet_shop_name"           );
			$this->shopEmail  = get_option( "woo_packet_shop_email"          );
			$this->shopPhone  = get_option( "woo_packet_shop_phone"          );
			$this->shopAddr   = get_option( "woo_packet_shop_address"        );
			$this->shopComplt = get_option( "woo_packet_shop_address_2"      );
			$this->shopNumber = get_option( "woo_packet_shop_address_number" );
			$this->shopState  = get_option( "woo_packet_shop_state"          );
			$this->shopCity   = get_option( "woo_packet_shop_city"           );
			$this->shopCep    = get_option( "woo_packet_shop_zipcode"        );
        }
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @param   integer|string    $order_id
     * @return  void
     */
    public function generate_tag( $order_id )
    {
        if ( $this->status != "on" )
        {
            $link     = esc_url( admin_url( "admin.php?page=" . WOO_PACKET_DOMAIN ) );
            $response = [
                "code"    => 400,
                "success" => false,
                "message" => "Falha ao gerar etiqueta do pedido #{$order_id}, o plugin está desativado. <a href='{$link}' >Clique aqui</a> para ativar.",
                "error"   => 4001,
                "data"    => null,
            ];

            $this->log( $response, "critical" );

            return $response;
        }

        try
        {
            $dollar  = $this->get_dollar();

            $order   = wc_get_order( $order_id );

            if ( !$order ) throw new Exception( "Pedido inválido #{$order_id}." );

            $this->get_shipping( $order );

            $items   = [];

            foreach ( $order->get_items() as $item )
            {
                $product = $item->get_product();

                if ( !$product->meta_exists( "_custom_product_ncm" ) || empty( $product->get_meta( "_custom_product_ncm", true ) ) )
                    throw new Exception( "Há produtos neste pedido (#{$order_id}) que não tem NCM vinculado. Por favor, edite o produto para gerar a etiqueta." );

                $value   = ( float ) $item->get_total() / $item->get_quantity();
                $value   = $value / $dollar;
                $value   = ( float ) number_format( $value, 2 );

                $items[] = [
                    "hsCode"      => $product->get_meta( "_custom_product_ncm", true ),
                    "description" => substr( $product->get_name(), 0, 30 ),
                    "quantity"    => $item->get_quantity(),
                    "value"       => $value,
                ];
            }

            $freight = $this->get_freight() / $dollar;
            $freight = ( float ) number_format( $freight, 2 );

            $i       = $order->get_meta( "_woo_packet_tag" );

            if ( is_null( $i ) )
            {
                $i = 0;
                $order->update_meta_data( "_woo_packet_tag", $i );
                $order->save_meta_data();
            }
            else
            {
                $i = 1 + ( int ) $i;
                $order->update_meta_data( "_woo_packet_tag", $i );
                $order->save_meta_data();
            }

            $i      = ".{$i}";

            $data   = [
                "packageList" => [
                    [
                        "idusuario"                  => $this->userId,
                        "username"                   => $this->user,
                        "password"                   => $this->userPsw,

                        "customerControlCode"        => $this->tagPrefix . $order_id . $i,

                        "senderName"                 => $this->shopName,
                        "senderEmail"                => $this->shopEmail,
                        "senderAddress"              => $this->shopAddr,
                        "senderAddressNumber"        => $this->shopNumber,
                        "senderAddressComplement"    => $this->shopComplt,
                        "senderState"                => $this->shopState,
                        "senderCityName"             => $this->shopCity,
                        "senderPhone"                => preg_replace( "/\D/", "", $this->shopPhone ),
                        "senderZipCode"              => preg_replace( "/\D/", "", $this->shopCep   ),

                        "recipientDocumentType"      => "CPF",
                        "recipientName"              => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
                        "recipientEmail"             => $order->get_billing_email(),
                        "recipientAddressNumber"     => $order->get_meta( "_billing_number", true ),
                        "recipientAddressComplement" => $order->get_billing_address_2(),
                        "recipientBairro"            => $order->get_meta( "_billing_neighborhood", true ),
                        "recipientState"             => $order->get_shipping_state(),
                        "recipientCityName"          => $order->get_shipping_city(),

                        "recipientDocumentNumber"    => preg_replace( "/\D/", "", $order->get_meta( "_billing_cpf", true ) ),
                        "recipientPhoneNumber"       => preg_replace( "/\D/", "", $order->get_billing_phone() ),
                        "recipientZipCode"           => preg_replace( "/\D/", "", $order->get_shipping_postcode() ),
                        "recipientAddress"           => explode( ",", $order->get_billing_address_1() )[ 0 ],

                        "totalWeight"                => ( float ) str_replace( ",", ".", $this->api->get_weight() ),

                        "packagingHeight"            => ( float ) str_replace( ",", ".", $this->api->get_height() ),
                        "packagingWidth"             => ( float ) str_replace( ",", ".", $this->api->get_width()  ),
                        "packagingLength"            => ( float ) str_replace( ",", ".", $this->api->get_length() ),

                        "freightPaidValue"           => $freight,

                        "modal"   => "S",
                        "battery" => "N",
                        "liquid"  => "N",
                        "cream"   => "N",
                        "items"   => $items
                    ]
                ]
            ];

            return $this->exec( $data );
        }
        catch ( Exception $e )
        {
            $response = [
                "code"    => 500,
                "success" => false,
                "message" => $e->getMessage(),
                "error"   => 5001,
                "data"    => null,
            ];

            $this->log( $response, "critical" );

            return $response;
        }
    }


    /**
     * Get current dollar quote
     *
	 * @since 	1.0.0
     * @return  float
     */
    private function get_dollar()
    {
        try
        {
            $response = $this->exec( [], "GET", "https://economia.awesomeapi.com.br/json/last/USD-BRL" ); // hardcoded

            if ( $response[ "success" ] )
            {
                $data     = $response[ "data" ][ "USDBRL" ];
                $high     = ( float ) $data[ "high" ];
                $low      = ( float ) $data[ "low" ];
                $avg      = ( $high + $low ) / 2;
                $response = ( float ) number_format( $avg, 2 );
            }
            else
            {
                $response = 5.0;
            }
        }
        catch ( Exception $e )
        {
            $this->log( [
                "code"    => 500,
                "success" => false,
                "message" => $e->getMessage(),
                "error"   => 5000,
                "data"    => [ null, "dolar usado R$5" ],
            ], "critical" );

            $response = 5.0;
        }

        return $response;
    }

	/**
	 * Make request to the API
	 *
	 * @since 	1.0.0
	 * @param   array|null  $params Body of request
	 * @param   string|null $method Method of request
	 * @param   string|null $url    URL of request
	 * @return  array
	 */
    private function exec( array $params, string $method = "POST", string $url = "" )
    {
        try
        {
            $curl     = curl_init();

            if ( !$curl )
            {
                $err = [
                    "code"    => "500.1",
                    "success" => false,
                    "message" => "Falha na comunicação com a API.",
                    "error"   => "Couldn't initialize a cURL handle",
                ];

                $this->log->add( $this->id, json_encode( $err ), "critical" );

                return $err;
            }

            $params   = ( !empty( $params ) ) ? json_encode( $params ) : null;

            curl_setopt_array( $curl, [
                CURLOPT_URL            => ( $method == "POST" ) ? $this->url : $url,
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 0,
                CURLOPT_FOLLOWLOCATION => TRUE,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_POSTFIELDS     => ( $method == "POST" ) ? $params       : null,
                CURLOPT_HTTPHEADER     => ( $method == "POST" ) ? $this->header : [],
            ] );

            $response = curl_exec( $curl );

            if ( empty( $response ) )
            {
                // some kind of an error happened
                $err      = curl_error( $curl );
                $response = [
                    "code"    => "500.2",
                    "success" => false,
                    "message" => "Falha na comunicação com a API.",
                    "error"   => $err,
                    "data"    => null,
                ];

                $this->log( $response, "critical" );
            }
            else
            {
                $info = curl_getinfo( $curl );

                if ( empty( $info[ "http_code" ] ) )
                {
                    $response = [
                        "code"    => "500.3",
                        "success" => false,
                        "message" => "Falha na comunicação com a API.",
                        "error"   => "No HTTP code was returned.",
                        "data"    => json_decode( $response, true ),
                    ];

                    $this->log( $response, "critical" );
                }
                else if ( $info[ "http_code" ] < 200 || $info[ "http_code" ] >= 300 )
                {
                    $response = [
                        "code"    => $info[ "http_code" ],
                        "success" => false,
                        "message" => "Falha na comunicação com a API.",
                        "error"   => self::HTTP_CODES[ $info[ "http_code" ] ],
                        "data"    => json_decode( $response, true ),
                    ];

                    $this->log( $response, "critical" );
                }
                else
                {
                    $response = [
                        "code"    => $info[ "http_code" ],
                        "success" => true,
                        "message" => null,
                        "error"   => null,
                        "data"    => json_decode( $response, true ),
                    ];
                }
            }

            curl_close( $curl );
        }
        catch ( Exception $e )
        {
            $response = [
                "code"    => 500,
                "success" => false,
                "message" => $e->getMessage(),
                "error"   => 5000,
                "data"    => null,
            ];

            $this->log( $response, "critical" );
        }

        return $response;
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @param   object  $order
     * @return  void
     */
    private function get_shipping( $order )
    {
        $items   = $order->get_items();

        $sCep    = preg_replace( "/\D/", "", $this->shopCep                  );
        $rCep    = preg_replace( "/\D/", "", $order->get_shipping_postcode() );

        $package = [
            "contents"    => [],
            "destination" => [
                "postcode" => $rCep,
            ]
        ];

        foreach ( $items as $item )
        {
            $package[ "contents" ][] = [
                "data"     => $item->get_product(),
                "quantity" => $item->get_quantity(),
            ];
        }

        $this->api->set_service( "33227" ); // hardcoded
        $this->api->set_package( $package );
        // $this->api->set_origin_postcode( $sCep );
        // $this->api->set_destination_postcode( $package[ "destination" ][ "postcode" ] );
        // $this->api->set_own_hands( "N" );
        // $this->api->set_receipt_notice( "N" );
        // $this->api->set_minimum_height();
        // $this->api->set_minimum_width();
        // $this->api->set_minimum_length();

        // return $api->get_shipping();
    }

    /**
     *
     *
	 * @since 	1.0.0
     * @return  float
     */
    private function get_freight()
    {
        $weight = str_replace( ",", ".", $this->api->get_weight() );
        $weight = 1000 * ( float ) $weight;

        if ( $weight >= 0 && $weight <= 200 )
        {
            return 19.30;
        }
        else if ( $weight >= 201 && $weight <= 500 )
        {
            return 28.60;
        }
        else if ( $weight >= 501 && $weight <= 1000 )
        {
            return 32.10;
        }
        else if ( $weight >= 1001 && $weight <= 1500 )
        {
            return 35.60;
        }
        else if ( $weight >= 1501 && $weight <= 2000 )
        {
            return 39.10;
        }
        else if ( $weight >= 2001 && $weight <= 2500 )
        {
            return 42.60;
        }
        else if ( $weight >= 2501 && $weight <= 3000 )
        {
            return 46.10;
        }
        else if ( $weight >= 3001 && $weight <= 3500 )
        {
            return 45.60;
        }
        else if ( $weight >= 3501 && $weight <= 4000 )
        {
            return 53.10;
        }
        else if ( $weight >= 4001 && $weight <= 4500 )
        {
            return 56.60;
        }
        else if ( $weight >= 4501 && $weight <= 5000 )
        {
            return 60.10;
        }
        else
        {
            // 1/2 adicional = 3,50

            $value = ( $weight - 5000 ) / 500;
            $value = ceil( $value ) * 3.5;

            return 60.10 + $value;
        }
    }

    /**
     * Register log
     *
	 * @since 	1.0.0
     * @param   array   $data
     * @param   string  $type
     * @return  void
     */
    public function log( array $data, string $type )
    {
        $this->log->add( $this->id, json_encode( $data ), $type );
    }
}
