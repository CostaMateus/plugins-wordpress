<?php
/**
 * Fast Shop Shipping.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

class Triibo_Fast_Shop_Shipping extends \WC_Shipping_Method
{
    /**
     * Shipping method ID.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const ID = "triibo-fast-shop-shipping";

    /**
     * Shipping method domain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const DOMAIN = Triibo_Fast_Shop::DOMAIN . "_shipping";

    /**
     * Seller ID.
     *
     * @since 2.0.0
     *
     * @var string
     */
    const SELLER = "fast_shop";

    /**
     * Instance ID.
     *
     * @since 2.0.0
     *
     * @var string
     */
    public $id;

    /**
     * API.
     *
     * @since 2.0.0
     *
     * @var Triibo_Api_Php
     */
    protected Triibo_Api_Php $api;

    /**
     * Additional time.
     *
     * @since 2.0.0
     *
     * @var string
     */
    protected string $additional_time;

    /**
     * Shipping classes ID.
     *
     * @since 2.0.0
     *
     * @var array
     */
    protected array $shipping_class_id;

    /**
     * Shows delivery time.
     *
     * @since 2.0.0
     *
     * @var string
     */
    protected string $show_delivery_time;

    /**
     * Initialize the Correios shipping method.
     *
     * @since 1.0.0
     *
     * @param int   $instance_id    Shipping zone instance ID.
     *
     * @return void
     */
    public function __construct( $instance_id = 0 )
    {
        $this->id                 = self::ID;
        $this->api                = new Triibo_Api_Php( seller: self::SELLER );

        $this->instance_id        = absint( maybeint: $instance_id );

        $this->method_title       = __( text: "Fast Shop",                    domain: self::DOMAIN );
        $this->method_description = __( text: "Entrega feita pela Fast Shop", domain: self::DOMAIN );

        $this->enabled            = "yes";
        $this->title              = "Fast Shop";

        $this->supports           = [
            "shipping-zones",
            "instance-settings",
        ];

        $this->init();
    }

    /**
     * Init your settings
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @return void
     */
    function init() : void
    {
        // Load the settings API
        // This is part of the settings API.
        // Override the method to add your own settings
        $this->init_form_fields();

        // Define user set variables.
        $this->enabled            = $this->get_option( key: "enabled"            );
        $this->title              = $this->get_option( key: "title"              );
        $this->additional_time    = $this->get_option( key: "additional_time"    );
        $this->show_delivery_time = $this->get_option( key: "show_delivery_time" );
        $this->shipping_class_id  = $this->get_option( key: "shipping_class_id", empty_value: [ "0" ] );

        // Save settings in admin if you have any defined
        add_action( hook_name: "woocommerce_update_options_shipping_" . $this->id, callback: [ $this, "process_admin_options" ] );
    }

    /**
     * Admin options fields.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function init_form_fields() : void
    {
        $this->instance_form_fields = [
            "enabled"            => [
                "type"        => "checkbox",
                "title"       => __( text: "Habilitar/Desabilitar",                                                   domain: self::DOMAIN ),
                "label"       => __( text: "Ativar este método de envio",                                             domain: self::DOMAIN ),
                "default"     => "yes",
            ],
            "title"              => [
                "type"        => "text",
                "title"       => __( text: "Título",                                                                  domain: self::DOMAIN ),
                "description" => __( text: "Isto controla o título que o usuário vê durante o checkout.",             domain: self::DOMAIN ),
                "desc_tip"    => true,
                "default"     => $this->method_title,
            ],
            "behavior_options"   => [
                "title"       => __( text: "Opções de comportamento",                                                 domain: self::DOMAIN ),
                "type"        => "title",
                "default"     => "",
            ],
            "shipping_class_id"  => [
                "type"        => "multiselect",
                "title"       => __( text: "Classe de entrega",                                                       domain: self::DOMAIN ),
                "description" => __( text: "Se necessário, selecione uma classe de envio para aplicar neste método.", domain: self::DOMAIN ),
                "desc_tip"    => true,
                "default"     => "",
                "class"       => "wc-enhanced-select",
                "options"     => $this->get_shipping_classes_options(),
            ],
            "show_delivery_time" => [
                "type"        => "checkbox",
                "title"       => __( text: "Tempo de entrega",                                                        domain: self::DOMAIN ),
                "label"       => __( text: "Exibir estimativa de entrega",                                            domain: self::DOMAIN ),
                "description" => __( text: "Exibir o tempo estimado de entrega em dias úteis.",                       domain: self::DOMAIN ),
                "desc_tip"    => true,
                "default"     => "no",
            ],
            "additional_time"    => [
                "type"        => "text",
                "title"       => __( text: "Dias adicionais",                                                         domain: self::DOMAIN ),
                "description" => __( text: "Dias úteis adicionais para a estimativa de entrega.",                     domain: self::DOMAIN ),
                "desc_tip"    => true,
                "default"     => "0",
                "placeholder" => "0",
            ],
        ];
    }

    /**
     * Get shipping classes options.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function get_shipping_classes_options() : array
    {
        $shipping_classes = WC()->shipping->get_shipping_classes();
        $options          = [
            "-1" => __( text: "Qualquer classe", domain: self::DOMAIN ),
            "0"  => __( text: "Nenhuma classe",  domain: self::DOMAIN ),
        ];

        if ( ! empty( $shipping_classes ) )
            $options += wp_list_pluck( input_list: $shipping_classes, field: "name", index_key: "term_id" );

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @since 1.0.0
     *
     * @param array     $package    Cart package.
     *
     * @return bool
     */
    protected function has_only_selected_shipping_class( array $package ) : bool
    {
        $only_selected = true;

        if ( in_array( needle: -1, haystack: $this->shipping_class_id ) )
            return $only_selected;

        foreach ( $package[ "contents" ] as $item_id => $values )
        {
            $product = $values[ "data"     ];
            $qty     = $values[ "quantity" ];

            if ( $qty > 0 && $product->needs_shipping() )
            {
                if ( ! in_array( needle: $product->get_shipping_class_id(), haystack: $this->shipping_class_id ) )
                {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }

    /**
     * calculate_shipping function.
     *
     * @since 1.0.0
     *
     * @access public
     *
     * @param mixed     $package
     *
     * @return void
     */
    public function calculate_shipping( mixed $package = [] ) : void
    {
        if ( "" === $package[ "destination" ][ "postcode" ] || "BR" !== $package[ "destination" ][ "country" ] )
            return;

        $postcode = strlen( string: $package[ "destination" ][ "postcode" ] );

        if ( $postcode < 8 || $postcode > 9 )
            return;

        if ( ! $this->has_only_selected_shipping_class( package: $package ) )
            return;

        $shipping = $this->get_rate( package: $package );

        if ( $shipping[ "error_code" ] !== null )
        {
			$this->log(
                is_error: true,
                level   : "error",
                message : "Error calculating shipping",
                context : [
                    "function" => "calculate_shipping",
                    "response" => $shipping
                ],
            );

            return;
        }

        $meta  = [];
        $costs = [];
        $time  = 0;

        foreach ( $shipping[ "data" ] as $data )
        {
            if ( $data[ "error_code" ] !== null )
                return;

            $item         = $data[ "data"       ];
            $id           = $item[ "product_id" ];

            $costs[ $id ] = ( float ) $item[ "delivery_price" ];
            $time         = ( $item[ "delivery_time" ] > $time ) ? $item[ "delivery_time" ] : $time;
        }

        if ( "yes" === $this->show_delivery_time )
            $meta = [ "_delivery_forecast" => intval( value: $time ) + intval( value: $this->additional_time ), ];

        $meta[ "price"   ] = $costs;
        $meta[ "company" ] = "Fast Shop";

        $rate = [
            "id"        => $this->id . $this->instance_id,
            "label"     => $this->title,
            "cost"      => $costs,
            "calc_tax"  => "per_item",
            "meta_data" => $meta,
            "package"   => $package
        ];

        $this->add_rate( args: $rate );
    }

	/**
	 * Get shipping rate.
	 *
     * @since 2.0.0     New api FS/Vtex
     * @since 1.6.2     Added more FS HUBs (83)
     * @since 1.6.0     Added more FS HUBs (97)
     * @since 1.5.0     Added more FS HUBs (3E)
     * @since 1.4.0     Added more FS HUBs (19, 74)
     * @since 1.3.0     Added more FS HUBs (4E, 25, 60, 61)
     * @since 1.0.0
     *
	 * @param array     $package    Cart package.
     *
	 * @return array
	 */
	protected function get_rate( array $package ) : mixed
    {
        $body     = $this->process_package( package: $package );
        $response = $this->api->freight_calculation( params: $body );

        if ( $response[ "code" ] !== 200 )
        {
            $this->log(
                is_error: true,
                level   : "critical",
                message : "Error getting shipping rate",
                context : [
                    "function" => "get_rate",
                    "response" => $response
                ]
            );

            $response[ "error_code" ] = $response[ "code" ];

            return $response;
        }

        $shipping = $response[ "data" ];

        foreach ( $shipping[ "data" ] as $sku => &$data )
        {
            $product_id = wc_get_product_id_by_sku( sku: $sku );
            $product    = wc_get_product( the_product: $product_id );

            $item       = $data[ "data" ];

            $fs_normal  = 4799; // hardcoded

            if ( get_home_url() === "http://shopkeeper.local" )
                $fs_normal = 155; // local

            $product->set_stock_status( status: ( $item[ "availability" ] ? "instock" : "outofstock" ) );
            $product->update_meta_data( key: "_fastshop_hub", value: "normal" );
            $product->set_shipping_class_id( id: $fs_normal );

            if ( isset( $item[ "regular_price" ] ) && isset( $item[ "sale_price" ] ) )
            {
                $product->set_regular_price( price: $item[ "regular_price" ] );
                $product->set_sale_price( price: $item[ "sale_price" ] );
            }

            $data[ "data" ][ "product_id" ] = $product_id;

            $product->save();
        }

		return $shipping;
	}

    /**
     * Process the package
     *
     * @since 2.0.0     New api FS/Vtex
     * @since 1.0.0
     *
     * @param array     $package
     *
     * @return array
     */
    private function process_package( array $package ) : array
    {
        $postalCode = $package[ "destination" ][ "postcode" ];
        $items      = [];

        foreach ( $package[ "contents" ] as $content )
        {
            $product       = wc_get_product( the_product: $content[ "product_id" ] );
            $fs_product_id = $product->get_meta( key: "_fs_product_id" );

            $items[]       = [
                "sku"        => $product->get_sku(),
                "product_id" => $fs_product_id,
                "quantity"   => $content[ "quantity" ]
            ];
        }

        return [
            "postalCode" => $postalCode,
            "items"      => $items
        ];
    }

    /**
     * Register log
     *
     * @since 2.1.0     Add context parameter.
     * @since 2.0.3     Fix env prod.
     * @since 2.0.0     Reworked log function.
     * @since 1.0.0
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
		$logger  = ( function_exists( function: "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$context = array_merge( $context, [ "source" => self::ID ] );

		if ( $is_error || $this->api->env() === false )
			$logger->$level( $message, $context );
	}
}
