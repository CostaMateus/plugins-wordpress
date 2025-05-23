<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

?>

<div class="error inline">
	<p>
		<strong>
			<?php _e( text: "Triibo Desativado", domain: "triibo_assinaturas" ); ?>
		</strong>:
		<?php
			$curr = get_woocommerce_currency();
			_e ( text: "A moeda <code>{$curr}</code> não é suportada. Funciona apenas com Real Brasileiro.", domain: "triibo_assinaturas" );
		?>
	</p>
</div>
