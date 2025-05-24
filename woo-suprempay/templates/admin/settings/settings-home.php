<?php
/**
 * Provide a admin area view for the home
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package 	WooCommerce_SupremPay
 * @version 	1.0.0
 */
defined( "ABSPATH" ) || exit;

$url  = esc_url( admin_url( "admin.php?page=wc-settings&tab=checkout&section=suprempay" ) );
$text = __( "Configurações do WooCommerce", WC_SUPREMPAY_DOMAIN );
?>

<section>
    <p>Aceite pagamentos por boleto, cartão de crédito e pix utilizando o SupremPay.</p>
    <a href="<?=$url;?>" target="_blak" ><?=$text;?></a>
</section>