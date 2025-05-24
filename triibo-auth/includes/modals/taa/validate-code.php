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

    <div id="taa-modal-validate-code" class="woocommerce ta-bg-modal ta-d-none taa-animated" >
        <div class="ta-bg-modal-child" >
            <div class="ta-modal-header" >
                <h2>Entre com o código</h2>
                <span id="taa-close-validate-code" aria-hidden="true" onclick="taaCloseModal(this)" >&times;</span>
            </div>
            <form id="taa-form-validate-code" method="post" >
                <p class="ta-text-left ta-mb-15" >Enviamos um e-mail com seu código de confirmação, informe-o abaixo.</p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0" >
                    <input id="taa-email" type="hidden" name="email" />
                    <input id="taa-code" type="text" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-0" maxlength="6"
                           name="code" placeholder="Código" pattern="[0-9]{6}" title="O código tem seis (6) números" required />

                    <!-- Error message -->
                    <p id="taa-error-validate-code" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8           ta-mb-0 ta-d-none" ></p>
                    <p id="taa-resent-code"         class="ta-bg-transp ta-float-left  ta-text-dark   ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none" >Código reenviado</p>
                    <a id="taa-resend-code"         class="ta-bg-transp ta-float-right ta-pointer     ta-pb-20 ta-mt-20 ta-mb-0 ta-d-none" onclick="taaResendCodeEmail()" >Reenviar código</a>
                    <p id="taa-resend-count"        class="ta-bg-transp ta-float-right                ta-pb-20 ta-mt-20 ta-mb-0"                                          >Reenviar código em 30</p>
                </p>
                <button id="taa-btn-form-validate-code" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Confirmar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
