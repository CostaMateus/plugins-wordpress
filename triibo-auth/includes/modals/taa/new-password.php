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

    <div id="taa-modal-new-password" class="woocommerce ta-bg-modal ta-d-none" >
        <div class="ta-bg-modal-child" >
            <div class="ta-modal-header" >
                <h2>Crie uma senha</h2>
                <span id="taa-close-new-password" aria-hidden="true" onclick="taaCloseModal(this)" >&times;</span>
            </div>
            <form id="taa-form-new-password" method="post" >
                <p class="ta-text-left ta-mb-15" >Informe uma senha e a confirmação de senha, para concluir a criação da conta:</p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-mb-0" >
                    <!-- <input type="hidden" name="form-new-password" > -->
                    <input id="taa-new-pass1" type="password" name="taa-pass" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-15"
                           placeholder="Senha" pattern=".{8,}" title="A senha deve ter no mínimo 8 dígitos" required />
                    <input id="taa-new-pass2" type="password" name="taa-pass-c" class="woocommerce-Input woocommerce-Input--text input-text ta-mb-0"
                           placeholder="Confirmação de senha" pattern=".{8,}" title="A senha deve ter no mínimo 8 dígitos" required />

                    <!-- Error message -->
                    <p id="taa-error-new-pass" class="ta-bg-transp ta-text-center ta-text-danger ta-pt-8 ta-mb-0 ta-d-none" ></p>
                </p>
                <button id="taa-btn-form-new-password" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Criar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
