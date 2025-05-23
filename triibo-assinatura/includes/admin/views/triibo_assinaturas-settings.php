<?php
/**
 * Provide a admin area view for the plugin
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.2.0
 */
defined( constant_name: "ABSPATH" ) || exit;
?>

<div class="wrap">
	<h2>Triibo Assinaturas</h2>
    <?php
        $url  = esc_url( url: admin_url( path: "admin.php?page=wc-settings&tab=checkout&section=" . "triibo_assinaturas" ) );
        $text = __( text: "WC Configurações", domain: "triibo_assinaturas" );
        $link = "<a href='{$url}' >{$text}</a>";

        echo "<p>{$link}</p>";
    ?>
</div>