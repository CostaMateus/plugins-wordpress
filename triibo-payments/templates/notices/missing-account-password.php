<?php
/**
 * Admin View: Notice - Email missing
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

?>

<div class="error inline" >
	<p>
		<strong>
			<?php _e( text: $this->method_title . " - desativado", domain: "triibo_payments" ); ?>
		</strong>:
		<?php _e( text: "Você deve informar os dados de acesso da API. Atualize as credenciais {$this->get_global_config()}.", domain: "triibo_payments" ); ?>
	</p>
</div>
