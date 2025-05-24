<?php
/**
 * Admin View: Notice - Access Key missing
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Admin/Notices
 */
defined( "ABSPATH" ) || exit;
?>

<div class="error inline">
	<p>
		<strong>
			<?php _e( "Pagare Gateway - Desativado", "pagare-gateway" ); ?>
		</strong>:
		<?php _e( "Você deve informar a chave (sandbox/produção) de autenticação.", "pagare-gateway" ); ?>
	</p>
</div>
