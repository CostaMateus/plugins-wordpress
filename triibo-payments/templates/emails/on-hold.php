<?php
/**
 * HTML email instructions.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;

?>

<div style="margin: 36px auto;">

	<?php if ( $type == "BILLET" ) : ?>
		<p style="margin:.5em 0 .25em 0;" >
			Para visualizar o boleto de pagamento
			<a class="button" style="margin:1em auto;" href="<?php echo $link; ?>" target="_blank" >clique aqui</a>.
			Caso prefira, você pode copiar o código de barras abaixo:
		</p>
		<p style="margin:.25em 0 .5em 0; font-size:14px; word-break:break-all;" ><?php echo $code; ?></p>

	<?php else : ?>
		<p style="margin:.5em 0 .25em 0;" >QR Code para pagamento do seu pedido.</p>
		<div style="text-align:center;" >
			<img style="margin:.25em 0 .5em 0; width:50%; height:50%" src="data:image/gif;base64,<?php echo $link; ?>" />
		</div>

		<p style="margin:.5em 0 .25em 0;" >Ou se você preferir pode copiar o código PIX abaixo:</p>
		<p style="margin:.25em 0 .5em 0; font-size:14px; word-break:break-all;" ><?php echo $code; ?></p>
	<?php endif;?>

	<p>
		Para visualizar seus pedidos
		<a href="<?php echo esc_url( url: get_permalink( post: get_option( option: "woocommerce_myaccount_page_id" ) ) ); ?>" title="Minha conta" >clique aqui</a>.
	</p>
</div>
