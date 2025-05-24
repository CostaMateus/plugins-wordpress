<?php
/**
 * Provide a admin area view for the transfer between users
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package 	WooCommerce_SupremPay
 * @version 	1.0.0
 */
defined( "ABSPATH" ) || exit;
?>

<section>
    <h3>Transferir para conta SupremCash</h3>

    <form id="suprempay-tbu" >
        <table class="form-table" >
            <tbody>
                <tr valign="top" >
                    <th scope="row" class="titledesc" >
                        <label for="sp_tbu_email" >E-mail da carteira</label>
                    </th>
                    <td class="forminp forminp-text" >
                        <input name="sp_tbu_email" id="sp_tbu_email" type="email" required >
                    </td>
                </tr>
                <tr valign="top" >
                    <th scope="row" class="titledesc" >
                        <label for="sp_tbu_pin" >Código PIN</label>
                    </th>
                    <td class="forminp forminp-text" >
                        <input name="sp_tbu_pin" id="sp_tbu_pin" type="text" maxlenght="4" pattern="[0-9]{4}" title="O código PIN tem quatro (4) números" required >
                    </td>
                </tr>
                <tr valign="top" >
                    <th scope="row" class="titledesc" >
                        <label for="sp_tbu_value" >Valor a transferir</label>
                    </th>
                    <td class="forminp forminp-text" >
                        <input name="sp_tbu_value" id="sp_tbu_value" type="text" required >
                    </td>
                </tr>

                <div id="sp-notice-error"   class="sp-d-none notice notice-error"   ><p></p></div>
                <div id="sp-notice-warning" class="sp-d-none notice notice-warning" ><p></p></div>
                <div id="sp-notice-success" class="sp-d-none notice notice-success" ><p></p></div>
            </tbody>
        </table>
        <p class="submit" >
            <button id="sp-tbu-btn" name="save" class="button-primary woocommerce-save-button sp-set-spin" type="submit" value="Salvar alterações" >
                Transferir <div class="sp-d-none sp-spin-load" ></div>
            </button>
            <?php wp_nonce_field( "sp_tbu_nonce", "sp_tbu_nonce", false ); ?>
        </p>
    </form>


</section>