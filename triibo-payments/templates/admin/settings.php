<?php
/**
 * Admin area settings view
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
defined( constant_name: "ABSPATH" ) || exit;
?>

<div class="wrap">
	<h2>Triibo Payments</h2>
    <?php
        $url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout" ) );
        $text = __( text: "WC Configurações", domain: "triibo_payments" );
        $link = "<a href='{$url}' >{$text}</a>";

        echo "<p>{$link}</p>";
    ?>
</div>