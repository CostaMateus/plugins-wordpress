<?php
/**
 * Provide a admin area view for the plugin
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package 	WooCommerce_SupremPay
 * @version 	1.0.0
 */
defined( "ABSPATH" ) || exit;

$default_tab = null;
$tab         = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : $default_tab;
?>

<div class="wrap" >
    <!-- Print the page title -->
    <h1><?= esc_html( get_admin_page_title() ); ?></h1>

    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper" >
		<a href="?page=<?= WC_SUPREMPAY_DOMAIN; ?>"              class="nav-tab <?php if( $tab === null       ):?> nav-tab-active <?php endif; ?>" >
			Início
		</a>
		<a href="?page=<?= WC_SUPREMPAY_DOMAIN; ?>&tab=transfer" class="nav-tab <?php if( $tab === 'transfer' ):?> nav-tab-active <?php endif; ?>" >
			Transferência entre contas
		</a>
		<!-- <a href="?page=<?php //echo WC_SUPREMPAY_DOMAIN; ?>&tab=tools"    class="nav-tab <?php // if( $tab === 'tools'    ):?> nav-tab-active <?php // endif; ?>" >
			Tools
		</a> -->
    </nav>

    <div class="tab-content" style="margin-top:15px;" >
		<?php
			switch( $tab ) :
				case "transfer":
					require_once ( dirname( WC_SUPREMPAY_PLUGIN_FILE ) . "/templates/admin/settings/settings-transfer.php" );
				break;
				default:
					require_once ( dirname( WC_SUPREMPAY_PLUGIN_FILE ) . "/templates/admin/settings/settings-home.php"     );
				break;
			endswitch;
		?>
    </div>
</div>