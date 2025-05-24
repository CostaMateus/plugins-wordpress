<?php
/**
 * HTML email instructions.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Templates
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

?>

<p>
	Caso tenha perdido o link para pagamento, ou fechado antes da conclusão, você pode encontrá-lo na sua conta,
	<a href="<?php echo esc_url( get_permalink( get_option( "woocommerce_myaccount_page_id" ) ) ); ?>" title="Minha conta" >clicando aqui</a>.
	<br>
	<?php echo $message; ?>
</p>

<div style="margin: 36px auto;">
	<h3 style="font-size: 18px;">Pague com o código abaixo</h3>
	<img style="display:table; background-color:#FFF; max-width:350px!important;" src="<?php echo $link; ?>" alt="Code" />
	<br>

	<h3 style="font-size: 18px;">Pague copiando e colando o código abaixo</h3>
	<p class="rppix-p" style="font-size:14px; margin-bottom:0"><?php echo $code; ?></p>
</div>