<?php
/**
 * Triibo Orders Controller class
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Orders_Controller extends WP_REST_Controller
{
	/**
	 * Unique identifier for the controller.
	 *
	 * @since 1.5.0
	 *
	 * @var string
	 */
	protected $id = Triibo_Payments::DOMAIN . "-api-orders";

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = "triibo-payments/v1";

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = "orders";

	/**
	 * Stores the request.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $request = [];

	/**
	 * Register the routes for orders.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() : void
	{
		register_rest_route(
			route_namespace: $this->namespace,
			route: "/$this->rest_base/update-payment",
			args: [
				[
					"methods"             => WP_REST_Server::READABLE, // GET
					"callback"            => [ $this, "update_order" ],
					"permission_callback" => "__return_true",
					"args"                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * Update order status.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request 	$request
	 *
	 * @return WP_REST_Response
	 */
	public function update_order( WP_REST_Request $request ) : WP_REST_Response
	{
        /**
         * Asaas status
         *
         * 'PENDING'                      : 'Aguardando pagamento',
         * 'RECEIVED'                     : 'Recebida',                 // saldo já creditado na conta
         * 'CONFIRMED'                    : 'Pagamento confirmado',     // saldo ainda não creditado
         * 'OVERDUE'                      : 'Vencida',
         * 'REFUNDED'                     : 'Estornada',
         * 'RECEIVED_IN_CASH'             : 'Recebida em dinheiro',     // não gera saldo na conta
         * 'REFUND_REQUESTED'             : 'Estorno Solicitado',
         * 'REFUND_IN_PROGRESS'           : 'Estorno em processamento', // liquidação já está agendada, cobrança será estornada após executar a liquidação
         * 'CHARGEBACK_REQUESTED'         : 'Recebido chargeback',
         * 'CHARGEBACK_DISPUTE'           : 'Em disputa de chargeback', // caso sejam apresentados documentos para contestação
         * 'AWAITING_CHARGEBACK_REVERSAL' : 'Disputa vencida, aguardando repasse da adquirente',
         * 'DUNNING_REQUESTED'            : 'Em processo de negativação',
         * 'DUNNING_RECEIVED'             : 'Recuperada',
         * 'AWAITING_RISK_ANALYSIS'       : 'Pagamento em análise',
         */

		$key = $request->get_header( key: "x-api-key" );

		if ( $key !== "54723162306C6F6A7052646D4C6170693030" && $key !== "0303960716C4D6462507A6F6C60326132745" )
		{
			return new WP_REST_Response(
				status: 401,
				data  : [
					"success" => false,
					"message" => "Unauthorized",
				],
			);
		}

		$order_id = $request->get_param( key: "orderId"  );
		$status   = $request->get_param( key: "status"   );
		$approved = filter_var( value: $request->get_param( key: "approved" ), filter: FILTER_VALIDATE_BOOLEAN );

		$order    = wc_get_order( the_order: $order_id );

		$this->log(
			is_error: true,
			level   : "info",
			message : ( ! $order ) ? "Order not found" : ( $approved ? "Order approved" : "Order failed" ),
			context : [
				"order_id" => $order_id,
				"status"   => $status,
				"approved" => $approved ? "true" : "false",
			]
		);

		if ( !$order )
		{
			return new WP_REST_Response(
				status: 404,
				data  : [
					"success" => false,
					"message" => "Order not found",
				],
			);
		}

		if ( $approved )
		{
			$order->payment_complete();
			$order->update_status( new_status: "processing" );
			$order->save();

			return new WP_REST_Response( data: [
				"success" => true,
				"message" => "Order successfully updated, with status processing",
			], status: 200 );
		}

		$order->update_status( new_status: "failed" );
		$order->save();

		return new WP_REST_Response( data: [
			"success" => true,
			"message" => "Order successfully updated, with status failed",
		], status: 200 );
	}

	/**
	 * Register log.
	 *
	 * @since 1.5.0
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
