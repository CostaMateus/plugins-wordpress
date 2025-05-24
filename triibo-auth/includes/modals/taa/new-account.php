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

    <div id="taa-modal-new-account" class="woocommerce ta-bg-modal ta-d-none" >
        <div class="ta-bg-modal-child" >
            <div class="ta-modal-header" >
                <h2>Entre com sua conta</h2>
                <span id="taa-close-new-account" aria-hidden="true" onclick="taaCloseModal(this)" >&times;</span>
            </div>
            <form id="taa-form-new-account" method="post" >
                <p class="ta-text-left ta-mb-15" >
                    Informe o e-mail e a senha da sua conta:
                    <br>
                    <small>Caso não tenha uma, criaremos para você.</small>
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" required placeholder="Endereço de e-mail"
                        name="email" id="taa-new-acc-email" autocomplete="email" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" required placeholder="Senha"
                        name="password" id="taa-new-acc-pass" autocomplete="password" />
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <span class="ta-form-row__remember" >
                        <label for="taa-new-acc-terms" class="ta-form__label ta-form__label-for-checkbox ta-form-login__rememberme ta-mb-0" >
                            <input id="taa-new-acc-terms" name="terms" type="checkbox" class="ta-form__input ta-form__input-checkbox" >
                            <span for="taa-new-acc-terms" >
                                Li e aceito os
                                <a href="https://triibo.com.br/termos-de-uso-dos-servicos-triibo/" target="_blank" >
                                    termos de uso.
                                </a>
                            </span>
                        </label>
                    </span>
                </p>

                <!-- Error message -->
                <p id="taa-error-new-acc" class="ta-bg-transp ta-text-center ta-text-danger ta-mb-12 ta-d-none" ></p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-my-0" >
                    <a id="taa-new-lost-pass"   class="ta-bg-transp ta-float-right ta-pointer ta-pb-20 ta-mb-0" onclick="closeModal()"
                        href="<?= esc_url( url: home_url( path: "/minha-conta/lost-password/" ) ); ?>" target="_blank" >Esqueceu a senha?</a>
                </p>

                <button id="taa-btn-form-new-account" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
