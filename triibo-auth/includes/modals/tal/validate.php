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

    <div id="tal-modal-form-validate" class="woocommerce ta-bg-modal-login ta-d-none" >
        <div class="ta-bg-modal-login-child" >
            <div class="ta-modal-header" >
                <h2>Entre com o código</h2>

                <?php
                    if ( class_exists( class: "Triibo_Auth_Checkout" ) && !Triibo_Auth_Checkout::has_query_param() )
                        echo "<span id=\"tal-close-form-validate\" aria-hidden=\"true\" >&times;</span>";
                ?>
            </div>
            <form id="tal-form-validate" method="post" >
                <p class="ta-text-left ta-mb-15" >Enviamos um SMS com seu código de acesso, informe-o abaixo.</p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0" >
                    <input type="hidden" name="form-validate" >
                    <input id="tal-code" type="text" class="woocommerce-Input woocommerce-Input--text input-text" maxlength="6"
                           name="code" placeholder="Código" pattern="[0-9]{6}" title="O código tem seis (6) números" required />

                    <!-- Error message -->
                    <p id="tal-error-valid"  class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8           ta-mb-0 ta-d-none" ></p>
                    <p id="tal-code-resend"  class="ta-bg-transp ta-float-left  ta-text-dark   ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none" >Código reenviado</p>

                    <?php
                        /**
                         * @since 2.0.0 Add resend btn wpp. Fix resend btn default sms.
                         */
                    ?>
                    <a id="tal-resend" class="ta-bg-transp ta-float-right ta-pointer ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none" onclick="talResendCode( 'sms' )" >Reenviar código via SMS</a>

                    <?php if ( get_option( option: TRIIBO_AUTH_ID . "_wpp_status" ) ) : ?>
                        <a id="tal-resend-wpp" class="ta-bg-transp ta-float-right ta-pointer ta-pb-20 ta-n-mt-20 ta-mb-0 ta-d-none" onclick="talResendCode( 'wpp' )" >Reenviar código via Whatsapp</a>
                    <?php endif; ?>

                    <p id="tal-resend-count" class="ta-bg-transp ta-float-right ta-pb-20 ta-mt-20 ta-mb-0" >Reenviar código em 30</p>
                </p>
                <button id="tal-btn-form-validate" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
