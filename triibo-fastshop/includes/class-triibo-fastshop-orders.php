<?php
/**
 * Fast Shop Orders
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;
class Triibo_Fast_Shop_Orders
{
    /**
     * @since 1.0.0
     *
     * @var string
     */
    const ID = "triibo-fast-shop-orders";

    /**
     * @since 1.0.0
     *
     * @var string
     */
    const SELLER = "fast_shop";

    /**
     * @since 1.0.0
     *
     * @var Triibo_Api_Php|null
     */
    private ?Triibo_Api_Php $api = null;

    /**
     * @since 1.0.0
     *
     * @var string|null
     */
    private ?string $type = null;

    /**
     * @since 1.0.0
     *
     * @var int
     */
    private ?int $order_id = null;

    /**
     * @since 1.0.0
     *
     * @var string|null
     */
    private ?string $old_status = null;

    /**
     * @since 1.0.0
     *
     * @var string|null
     */
    private ?string $new_status = null;

	/**
	 * Initialize webservice.
	 *
     * @since 1.0.0
     *
	 * @param int       $order_id
	 * @param string    $old_status
	 * @param string    $new_status
	 */
	public function __construct( ?int $order_id = null, ?string $old_status = null, ?string $new_status = null )
    {
        $this->id  = self::ID;

        $this->api = new Triibo_Api_Php( seller: self::SELLER );

        if ( $order_id && $old_status && $new_status )
        {
            $this->order_id   = $order_id;
            $this->old_status = $old_status;
            $this->new_status = $new_status;

            $this->process_order();
        }
	}

    /**
     * Get processing type
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_type() : string
    {
        return $this->type;
    }

    /**
     * Set process
     *
     * @since 1.0.0
     *
     * @param string    $old_status
     * @param string    $new_status
     *
     * @return void
     */
    public function set_process( string $old_status, string $new_status ) : void
    {
		$this->old_status = $old_status;
		$this->new_status = $new_status;

        $this->process_order();
    }

	/**
	 * Process the order
	 *
     * @since 1.0.0
     *
	 * @return void
	 */
	private function process_order() : void
    {
        /**
         * qlqr_status ( -concluido ) --> aguardando
         * pendente --> processando
         * pedido é CRIADO na FS
         */
        if (
            ( $this->old_status != "completed" && $this->new_status == "on-hold"    )
            ||
            ( $this->old_status == "pending"   && $this->new_status == "processing" )
        )
        {
            $this->type = "ordercreation";
        }

        /**
         * aguardando --> processando
         * pedido é APROVADO na FS
         */
        elseif ( $this->old_status == "on-hold" && $this->new_status == "processing" )
        {
            $this->type = "orderapproved";
        }

        /**
         * qlqr_status ( -pendente ) --> cancelado
         * pedido é CANCELADO na FS
         */
        elseif ( $this->old_status != "pending" && $this->new_status == "cancelled" )
        {
            $this->type = "ordercancelation";
        }
    }

    /**
     * Process the order, send to integration
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function send_notification() : array
    {
        $status = get_option( option: "triibo_fast_shop_fs_status" );

        if ( $status != "on" )
        {
            $url = esc_url( url: admin_url( path: "admin.php?page=" . Triibo_Fast_Shop::DOMAIN . "-settings" ) );
            $this->log( level: "info", message: "Order notification is disabled. Enable in this link: {$url}", is_error: true );

            return [];
        }

        if ( ! $this->type )
        {
            $this->log( level: "info", message: "#{$this->order_id}: This changing of status is not mapped, {$this->old_status} >> {$this->new_status}", is_error: true );

            return [];
        }

        switch ( $this->type )
        {
            case "ordercreation":
                $this->log( level: "info", message: "Creating order FS: #{$this->order_id}" );

                $response   = $this->api->order_creation( order_id: $this->order_id );
                $error_code = $response[ "data" ][ "error_code" ];
                $message    = ( $response[ "success" ] )
                                ? "Successfully created: #{$this->order_id}"
                                : "Error on creating #{$this->order_id} | " . "{$error_code} {$response[ "data" ][ "message" ]}";
            break;

            case "orderapproved":
                $this->log( level: "info", message: "Approving order FS: #{$this->order_id}" );

                $response   = $this->api->order_approval( order_id: $this->order_id );
                $error_code = $response[ "data" ][ "error_code" ];
                $message    = ( $response[ "success" ] )
                                ? "Successfully approved: #{$this->order_id}"
                                : "Error on approving #{$this->order_id} | " . "{$error_code} {$response[ "data" ][ "message" ]}";
            break;

            case "ordercancelation":
                $this->log( level: "info", message: "Canceling order FS: #{$this->order_id}" );

                $response   = $this->api->order_cancellation( order_id: $this->order_id );
                $error_code = $response[ "data" ][ "error_code" ];
                $message    = ( $response[ "success" ] )
                                ? "Successfully cancelled: #{$this->order_id}"
                                : "Error on canceling #{$this->order_id} | " . "{$error_code} {$response[ "data" ][ "message" ]}";
            break;
        }

        $level = ( $response[ "success" ] ) ? "info" : "error";

        $this->log( level: $level, message: $message, context: $response, is_error: $level === "error" );

        return [
            "error_code" => $error_code,
            "success"    => $response[ "success" ],
            "message"    => $message,
        ];
    }

    /**
     * Valid if it is necessary to create sub-orders for the order in question
     *
     * @since 1.0.0
     *
     * @param int           $parent_order_id
     * @param \WC_Order     $parent_order
     *
     * @return void
     */
    public function maybe_split_orders( int $parent_order_id, \WC_Order $parent_order ) : void
    {
        $this->log( level: "info", message: sprintf( format: "TRIIBO: New Order #%d created. Init sub order.", values: $parent_order_id ) );

        if ( $parent_order->get_meta( key: "has_sub_order" ) == true )
        {
            $args = [
                "post_parent" => $parent_order_id,
                "post_type"   => "shop_order",
                "numberposts" => -1,
                "post_status" => "any",
            ];

            $child_orders = get_children( args: $args );

            foreach ( $child_orders as $child )
                wp_delete_post( post_id: $child->ID, force_delete: true );
        }

        $hubs        = [];
        $seller_id   = null;
        $order_items = $parent_order->get_items();

        foreach ( $order_items as $item )
        {
            $product = $item->get_product();
            $hub     = $product->get_shipping_class();

            if ( strpos( haystack: $hub, needle: "fast-shop-" ) !== false )
            {
                $seller_id      = get_post_field( field: "post_author", post: $item[ "product_id" ] );
                $hubs[ $hub ][] = $item;
            }
        }

        if ( count( value: $hubs ) <= 1 )
        {
            $this->log( level: "info", message: "TRIIBO: 1 hub only, skipping sub order" );
            return;
        }

        $parent_order->update_meta_data( key: "has_sub_order", value: true );
        $parent_order->save();

        $this->log( level: "info", message: sprintf( format: "TRIIBO: Got %s hubs, starting sub order", values: count( value: $hubs ) ) );

        foreach ( $hubs as $hub => $hub_products )
            $this->create_sub_order( parent_order: $parent_order, seller_id: $seller_id, hub: $hub, hub_products: $hub_products );
    }

    /**
     * Creates the sub-order
     *
     * @since 1.0.0
     *
     * @param \WC_Order     $parent_order
     * @param int           $seller_id
     * @param string        $hub
     * @param array         $hub_products
     *
     * @return \WC_Order|\WP_Error
     */
    private function create_sub_order( \WC_Order $parent_order, int $seller_id, string $hub, array $hub_products ) : mixed
    {
        $this->log( level: "info", message: "TRIIBO: Creating sub order for hub: " . $hub );

        $bill_ship = [
            "billing_country",
            "billing_first_name",
            "billing_last_name",
            "billing_company",
            "billing_address_1",
            "billing_address_2",
            "billing_city",
            "billing_state",
            "billing_postcode",
            "billing_email",
            "billing_phone",
            "shipping_country",
            "shipping_first_name",
            "shipping_last_name",
            "shipping_company",
            "shipping_address_1",
            "shipping_address_2",
            "shipping_city",
            "shipping_state",
            "shipping_postcode",
        ];

        try
        {
            $order = new \WC_Order();

            // save billing and shipping address
            foreach ( $bill_ship as $key )
                if ( is_callable( [ $order, "set_{$key}" ] ) )
                    $order->{ "set_{$key}" }( $parent_order->{"get_{$key}"}() );

            // now insert line items
            $this->create_line_items( order: $order, products: $hub_products );

            // do shipping
            $this->create_shipping( order: $order, parent_order: $parent_order, products: $hub_products );

            // do tax
            $this->create_taxes( order: $order, parent_order: $parent_order, products: $hub_products );

            // add coupons if any
            $this->create_coupons( order: $order, parent_order: $parent_order, products: $hub_products );

            // save other details
            $order->set_created_via( value: "triibo-fastshop-orders" );
            $order->set_cart_hash( value: $parent_order->get_cart_hash() );
            $order->set_customer_id( value: $parent_order->get_customer_id() );
            $order->set_currency( value: $parent_order->get_currency() );
            $order->set_prices_include_tax( value: $parent_order->get_prices_include_tax() );
            $order->set_customer_ip_address( value: $parent_order->get_customer_ip_address() );
            $order->set_customer_user_agent( value: $parent_order->get_customer_user_agent() );
            $order->set_customer_note( value: $parent_order->get_customer_note() );
            $order->set_payment_method( payment_method: $parent_order->get_payment_method() );
            $order->set_payment_method_title( value: $parent_order->get_payment_method_title() );
            $order->update_meta_data( key: "_dokan_vendor_id", value: $seller_id );

            // finally, let the order re-calculate itself and save
            $order->calculate_totals();

            $order->set_status( new_status: $parent_order->get_status() );
            $order->set_parent_id( value: $parent_order->get_id() );

            $order_id = $order->save();

            $this->log( level: "info", message: "TRIIBO: Created sub order : #" . $order_id );

            // update total_sales count for sub-order
            wc_update_total_sales_counts( order_id: $order_id );
        }
        catch ( Exception $e )
        {
            return new WP_Error( code: "dokan-suborder-error", message: $e->getMessage() );
        }

        return $order;
    }

    /**
     * Creates line items for the sub-order
     *
     * @since 1.0.0
     *
     * @param \WC_Order     $order
     * @param array         $products
     *
     * @return void
     */
    private function create_line_items( \WC_Order $order, array $products ) : void
    {
        foreach ( $products as $item )
        {
            $product_item = new \WC_Order_Item_Product();

            $product_item->set_name         ( value: $item->get_name()         );
            $product_item->set_product_id   ( value: $item->get_product_id()   );
            $product_item->set_variation_id ( value: $item->get_variation_id() );
            $product_item->set_quantity     ( value: $item->get_quantity()     );
            $product_item->set_tax_class    ( value: $item->get_tax_class()    );
            $product_item->set_subtotal     ( value: $item->get_subtotal()     );
            $product_item->set_subtotal_tax ( value: $item->get_subtotal_tax() );
            $product_item->set_total_tax    ( value: $item->get_total_tax()    );
            $product_item->set_total        ( value: $item->get_total()        );
            $product_item->set_taxes        ( raw_tax_data: $item->get_taxes() );

            $metadata     = $item->get_meta_data();

            if ( $metadata )
                foreach ( $metadata as $meta )
                    $product_item->add_meta_data( key: $meta->key, value: $meta->value );

            $order->add_item( $product_item );
        }

        $order->save();
    }

    /**
     * Creates shipping for the sub-order
     *
     * @since 1.0.0
     *
     * @param \WC_Order     $order
     * @param \WC_Order     $parent_order
     * @param array         $products
     *
     * @return void
     */
    private function create_shipping( \WC_Order $order, \WC_Order $parent_order, array $products ) : void
    {
        $shipping_methods         = $parent_order->get_shipping_methods();

        if ( ! $shipping_methods )
        {
            $this->log( level: "info", message: "TRIIBO: No shipping method found : Aborting : #" . $order->get_id() );
            return;
        }

        foreach ( $shipping_methods as $key => $shipping_method )
        {
            if ( ! is_a( object_or_class: $shipping_method, class: "WC_Order_Item_Shipping" ) )
                continue;

            $total    = 0;

            $item     = new \WC_Order_Item_Shipping();
            $metadata = $shipping_method->get_meta_data();

            if ( $metadata )
            {
                foreach ( $metadata as $meta )
                {
                    if ( $meta->key != "price" )
                    {
                        $item->add_meta_data( key: $meta->key, value: $meta->value );
                        continue;
                    }

                    $data     = $meta->get_data();
                    $new_meta = [ "value" => [] ];

                    foreach ( $products as $product )
                    {
                        $product_id = $product->get_product_id();

                        if ( isset( $data[ "value" ][ $product_id ] ) )
                        {
                            $value  = $data[ "value" ][ $product_id ];
                            $total += $value;

                            $new_meta[ "value" ][ $product_id ] = $value;
                        }
                    }

                    $item->add_meta_data( key: $meta->key, value: $new_meta );
                }
            }

            $item->set_props(
                props: [
                    "method_title" => $shipping_method->get_name(),
                    "method_id"    => $shipping_method->get_method_id(),
                    "taxes"        => $shipping_method->get_taxes(),
                    "total"        => $total,
                ]
            );

            $order->add_item( item: $item );
            $order->set_shipping_total( value: $shipping_method->get_total() );
            $order->save();
        }
    }

    /**
     * Creates taxes for the sub-order
     *
     * @since 1.0.0
     *
     * @param \WC_Order     $order
     * @param \WC_Order     $parent_order
     * @param array         $products
     *
     * @return void
     */
    private function create_taxes( \WC_Order $order, \WC_Order $parent_order, array $products ) : void
    {
        $shipping  = $order->get_items( types: "shipping" );
        $tax_total = 0;

        foreach ( $products as $item )
            $tax_total += $item->get_total_tax();

        foreach ( $parent_order->get_taxes() as $tax )
        {
            $seller_shipping = reset( array: $shipping );

            $item = new \WC_Order_Item_Tax();
            $item->set_props(
                props: [
                    "rate_id"            => $tax->get_rate_id(),
                    "label"              => $tax->get_label(),
                    "compound"           => $tax->get_compound(),
                    "rate_code"          => \WC_Tax::get_rate_code( key_or_rate: $tax->get_rate_id() ),
                    "tax_total"          => $tax_total,
                    "shipping_tax_total" => is_bool( value: $seller_shipping ) ? "" : $seller_shipping->get_total_tax(),
                ]
            );

            $order->add_item( item: $item );
        }

        $order->save();
    }

    /**
     * Creates coupons for the sub-order
     *
     * @since 1.0.0
     *
     * @param \WC_Order     $order
     * @param \WC_Order     $parent_order
     * @param array         $products
     *
     * @return void
     */
    private function create_coupons( \WC_Order $order, \WC_Order $parent_order, array $products ) : void
    {
        $used_coupons = $parent_order->get_items( types: "coupon" );
        $product_ids  = array_map(
            callback: function( $item ) : mixed
            {
                return $item->get_product_id();
            },
            array: $products
        );

        if ( ! $used_coupons )
            return;

        $seller_id = dokan_get_seller_id_by_order( order: $order->get_id() );

        if ( ! $seller_id )
            return;

        foreach ( $used_coupons as $item )
        {
            $coupon = new \WC_Coupon( data: $item->get_code() );

            if (
                $coupon &&
                ! is_wp_error( thing: $coupon ) &&
                ( array_intersect( array: $product_ids, arrays: $coupon->get_product_ids() ) ||
                    apply_filters( "dokan_is_order_have_admin_coupon", false, $coupon, [ $seller_id ], $product_ids )
                )
            ) {
                $new_item = new \WC_Order_Item_Coupon();
                $new_item->set_props(
                    props: [
                        "code"         => $item->get_code(),
                        "discount"     => $item->get_discount(),
                        "discount_tax" => $item->get_discount_tax(),
                    ]
                );

                $new_item->add_meta_data( key: "coupon_data", value: $coupon->get_data() );

                $order->add_item( item: $new_item );
            }
        }

        $order->save();
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
