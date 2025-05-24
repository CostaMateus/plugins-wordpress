<?php
/**
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @since 1.0.0
 * @version 1.0.0
 */
?>
<!-- @author Mateus Costa <mateus@costamateus.com.br> -->
<!-- @since 1.0.0 -->

<div id="tal-modal-form-auth-ckt" class="woocommerce ta-bg-modal-ckt <?php echo ( Triibo_Auth_Checkout::has_query_param() ) ? "" : "ta-d-none"; ?>" >
    <div class="ta-bg-modal-login-child" >
        <img id="tal-logo" style="width:40%;vertical-align:middle;" alt="" loading="lazy" class="ta-mx-auto" src="<?php echo apply_filters( hook_name: "martfury_site_logo", value: martfury_get_option( name: "logo" )); ?>" >
        <div class="ta-modal-header" >
            <h5 style="font-weight:400 !important; font-size: 15px !important;" >
                <?php echo Triibo_Auth_Checkout::get_disclaimer(); ?>
            </h5>
        </div>
        <form id="tal-form-auth-ckt" method="post" >
            <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0" >
                <input type="hidden" name="form-auth-ckt" >
                <input id="tal-cellphone-ckt" type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                    name="cellphone" placeholder="+55 (XX) XXXXX-XXXX" maxlength="19" >
                <!-- Error message -->
                <p id="tal-error-auth-ckt" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8 ta-mb-0 ta-d-none" ></p>
            </p>
            <p></p>
            <button id="tal-btn-form-auth-ckt" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Solicitar código <div class="ta-d-none ta-spin-load" ></div></button>
        </form>

        <a class="ta-float-left ta-mt-20" href="javascript:history.go(-1)" >Retornar para o site</a>
    </div>
</div>
