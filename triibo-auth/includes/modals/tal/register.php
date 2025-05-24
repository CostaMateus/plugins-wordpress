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

    <div id="tal-modal-form-register" class="woocommerce ta-bg-modal-login ta-d-none" >
        <div class="ta-bg-modal-login-child" >
            <div class="ta-modal-header" >
                <h2 id="tal-register-title" ></h2>

                <?php
                    if ( class_exists( class: "Triibo_Auth_Checkout" ) && !Triibo_Auth_Checkout::has_query_param() )
                        echo "<span id=\"tal-close-form-register\" aria-hidden=\"true\" >&times;</span>";
                ?>
            </div>
            <form id="tal-form-register" method="post" >
                <p id="tal-register-p1" class="ta-text-left ta-mb-5"  ></p>
                <p id="tal-register-p2" class="ta-text-left ta-mb-5"  ></p>
                <p id="tal-register-p3" class="ta-text-left ta-mb-15" ></p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="hidden" name="form-register" >
                    <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" requered placeholder="Endereço de e-mail"
                        name="email" id="tal-form-register-email" autocomplete="email" >
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" requered placeholder="Senha"
                        name="password" id="tal-form-register-password" autocomplete="password" >
                </p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                    <span class="ta-form-row__remember" >
                        <label for="tal-form-register-terms" class="ta-form__label ta-form__label-for-checkbox ta-form-login__rememberme ta-mb-0" >
                            <input id="tal-form-register-terms" name="terms" type="checkbox" class="ta-form__input ta-form__input-checkbox" >
                            <span for="tal-form-register-terms" >
                                Li e aceito os
                                <a href="https://triibo.com.br/termos-de-uso-dos-servicos-triibo/" target="_blank" >
                                    termos de uso.
                                </a>
                            </span>
                        </label>
                    </span>
                </p>

                <!-- Error message -->
                <p id="tal-error-register" class="ta-bg-transp ta-text-center ta-text-danger ta-mb-12 ta-d-none" ></p>

                <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide ta-my-0" >
                    <a id="tal-lost-pass"   class="ta-bg-transp ta-float-right ta-pointer ta-pb-20 ta-mb-0" onclick="closeModal()"
                        href="<?= esc_url( url: home_url( path: "/minha-conta/lost-password/" ) ); ?>" target="_blank" >Esqueceu a senha?</a>
                </p>

                <button id="tal-btn-form-register" type="submit" class="woocommerce-Button button ta-button ta-set-spin" >Entrar <div class="ta-d-none ta-spin-load" ></div></button>
            </form>
        </div>
    </div>
