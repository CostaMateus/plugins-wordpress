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

    <div id="tal-modal-form-validate-email" class="woocommerce ta-bg-modal-login ta-d-none" >
        <div class="ta-bg-modal-login-child" >
            <div class="ta-modal-header" >
                <h2>Entre com o código</h2>

                <?php
                    if ( class_exists( class: "Triibo_Auth_Checkout" ) && !Triibo_Auth_Checkout::has_query_param() )
                        echo "<span id=\"tal-close-form-validate-email\" aria-hidden=\"true\" >&times;</span>";
                ?>
            </div>
            <form id="tal-form-validate-email" method="post" >
                <p class="ta-text-left ta-mb-15" >Enviamos um e-mail com seu código de acesso, informe-o abaixo.</p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0" >
                    <input type="hidden" name="form-validate-email" >
                    <input id="tal-code-email" type="text" class="woocommerce-Input woocommerce-Input--text input-text" maxlength="6"
                           name="code" placeholder="Código" pattern="[0-9]{6}" title="O código tem seis (6) números" required />

                    <!-- Error message -->
                    <p id="tal-error-valid-email"  class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8           ta-mb-0 ta-d-none" ></p>
                    <p id="tal-code-resend-email"  class="ta-bg-transp ta-float-left  ta-text-dark   ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none"                                >Código reenviado</p>
                    <a id="tal-resend-email"       class="ta-bg-transp ta-float-right ta-pointer     ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none" onclick="talResendCodeEmail()" >Reenviar código</a>
                    <p id="tal-resend-email-count" class="ta-bg-transp ta-float-right                ta-pb-20 ta-mt-20 ta-mb-0"                                          >Reenviar código em 30</p>
                </p>
                <button id="tal-btn-form-validate-email" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
