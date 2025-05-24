<?php
/**
 * Checkout form.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_SupremPay/Templates
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;
?>

<fieldset id="suprempay-payment-form"
	class="<?php echo "storefront" === basename( get_template_directory() ) ? "suprempay-gateway-form-storefront" : ""; ?>" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, ".", "" ) ); ?>" >

	<ul id="suprempay-payment-methods" style="list-style:none; padding-left:0px;" >
		<?php if ( $cc     == "yes" ) : ?>
			<li>
				<label>
					<input id="suprempay-payment-method-cc" style="margin-right:0;"
						type="radio" name="suprempay_payment_method"
						value="cc" <?php checked( true, ( $cc == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_cc ); ?>" alt="<?php esc_attr_e( "Cartão de crédito", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Cartão de crédito", WC_SUPREMPAY_DOMAIN ); ?>
				</label>
			</li>
		<?php endif; ?>

		<?php if ( $pix    == "yes" ) : ?>
			<li>
				<label>
					<input id="suprempay-payment-method-pix" style="margin-right:0;"
						type="radio" name="suprempay_payment_method"
						value="pix" <?php checked( true, ( $cc == "no" && $pix == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_pix ); ?>" alt="<?php esc_attr_e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>
				</label>
			</li>
		<?php endif; ?>

		<?php if ( $ticket == "yes" ) : ?>
			<li>
				<label>
					<input id="suprempay-payment-method-ticket" style="margin-right:0;"
						type="radio" name="suprempay_payment_method"
						value="ticket" <?php checked( true, ( $pix == "no" && $ticket == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_ticket ); ?>" alt="<?php esc_attr_e( "Boleto", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Boleto", WC_SUPREMPAY_DOMAIN ); ?>
				</label>
			</li>
		<?php endif; ?>

		<?php if ( $transfer == "yes" ) : ?>
			<li>
				<label>
					<input id="suprempay-payment-method-transfer" style="margin-right:0;"
						type="radio" name="suprempay_payment_method"
						value="transfer" <?php checked( true, ( $pix == "no" && $ticket == "no" && $transfer == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_transfer ); ?>" alt="<?php esc_attr_e( "Transferência", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Transferência", WC_SUPREMPAY_DOMAIN ); ?>
				</label>
			</li>
		<?php endif; ?>
	</ul>

	<div class="clear" ></div>

	<?php if ( $cc  == "yes" ) : ?>
		<div id="suprempay-cc-form" class="suprempay-method-form" >
			<?php if ( $pix == "no" && $ticket == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_cc ); ?>" alt="<?php esc_attr_e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com cartão de Crédito.", WC_SUPREMPAY_DOMAIN ); ?>
				</h4>
			<?php endif; ?>

			<p id="suprempay-legal-person" class="form-row form-row-wide" style="margin-bottom:8px;" >
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="suprempay-legal-person-field" >
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
						name="suprempay_legal_person_field" id="suprempay-legal-person-field" >
					<span>Pessoa Jurídica?</span>
				</label>
			</p>

			<p id="suprempay-card-holder-name" class="form-row form-row-first" >
				<label for="suprempay-card-holder-name-field" >
					<?php _e( "Nome do Titular", WC_SUPREMPAY_DOMAIN ); ?>
					<small>(<?php _e( "como escrito no cartão", WC_SUPREMPAY_DOMAIN ); ?>) </small> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-holder-name-field"
					name="suprempay_card_holder_name" class="input-text" type="text"
					autocomplete="off" style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="suprempay-card-number" class="form-row form-row-last" >
				<label for="suprempay-card-number-field" >
					<?php _e( "Número do cartão", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-number-field"
					class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off"
					placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear" ></div>

			<p id="suprempay-card-expiry" class="form-row form-row-first" >
				<label for="suprempay-card-expiry-field" >
					<?php _e( "Validade (MM/AAAA)", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-expiry-field"
					class="input-text wc-credit-card-form-card-expiry"
					type="tel" autocomplete="off" placeholder="<?php _e( "MM/AAAA", WC_SUPREMPAY_DOMAIN ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="suprempay-card-cvc" class="form-row form-row-last" >
				<label for="suprempay-card-cvc-field" >
					<?php _e( "Código de segurança", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span></label>
				<input id="suprempay-card-cvc-field"
					class="input-text wc-credit-card-form-card-cvc"
					type="tel" autocomplete="off" placeholder="<?php _e( "CVC", WC_SUPREMPAY_DOMAIN ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear" ></div>

			<p id="suprempay-card-installment" class="form-row form-row-first" >
				<label for="suprempay-card-installment-field" >
					<?php _e( "Parcelas", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<select id="suprempay-card-installment-field" name="suprempay_card_installment"
					style="font-size:1.5em; padding:4px; width:100%; height:50px; color:#444; background-color:#FAFAFA;
					border:2px solid #E5E5E5; border-radius:3px; border-color:#C7C1C6; border-top-color:#BBB3B9;"
					<?php if ( $cc_type  == "avista" ) : ?> disabled="disabled" <?php endif; ?>
				>
					<?php if ( $cc_type  == "avista" ) : ?>
						<option value="1" >
							<?php echo "1x de R$ " . esc_attr( number_format( $cart_total, 2, ",", "." ) ); ?>
						</option>
					<?php else : ?>
						<?php foreach ( range( 1, $installment ) as $value ) { ?>
							<option value="<?= $value ?>" >
								<?php echo "{$value}x de R$ " . esc_attr( number_format( ( $cart_total / $value ), 2, ",", "." ) ) . " sem juros"; ?>
							</option>
						<?php } ?>
					<?php endif; ?>
				</select>
			</p>
			<p id="suprempay-card-holder-cpf" class="form-row form-row-last" >
				<label for="suprempay-card-holder-cpf-field" >
					<?php _e( "CPF do Titular", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-holder-cpf-field" name="suprempay_card_holder_cpf"
					class="input-text wecfb-cpf" type="tel" autocomplete="off" maxlength="18"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="suprempay-card-holder-cnpj" class="form-row form-row-last" style="display:none;" >
				<label for="suprempay-card-holder-cnpj-field" >
					<?php _e( "CNPJ do Titular", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-holder-cnpj-field" name="suprempay_card_holder_cnpj"
					class="input-text wecfb-cnpj" type="tel" autocomplete="off" maxlength="18"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear" ></div>

			<p id="suprempay-card-holder-birth-date" class="form-row form-row-first" >
				<label for="suprempay-card-holder-birth-date-field" >
					<?php _e( "Data de nascimento do Titular", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-holder-birth-date-field" name="suprempay_card_holder_birth_date"
					class="input-text" type="tel" autocomplete="off"
					placeholder="<?php _e( "DD / MM / YYYY", WC_SUPREMPAY_DOMAIN ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="suprempay-card-holder-phone" class="form-row form-row-last" >
				<label for="suprempay-card-holder-phone-field" >
					<?php _e( "Telefone do Titular", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-card-holder-phone-field" name="suprempay_card_holder_phone"
					class="input-text" type="tel" autocomplete="off"
					placeholder="<?php _e( "(xx) xxxx-xxxx", WC_SUPREMPAY_DOMAIN ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear" ></div>
		</div>
	<?php endif; ?>

	<?php if ( $pix == "yes" ) : ?>
		<div id="suprempay-pix-form" class="suprempay-method-form" >
			<?php if ( $cc == "no" && $ticket == "no" && $transfer == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_pix ); ?>" alt="<?php esc_attr_e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com PIX.", WC_SUPREMPAY_DOMAIN ); ?>
				</h4>
			<?php endif; ?>

			<p><?php _e( "O pedido será confirmado somente após a confirmação do pagamento.", WC_SUPREMPAY_DOMAIN ); ?></p>
			<p><?php _e( "* Ao finalizar a compra, você terá acesso ao código PIX para pagamento na próxima tela.", WC_SUPREMPAY_DOMAIN ); ?></p>
			<div class="clear" ></div>
		</div>
	<?php endif; ?>

	<?php if ( $ticket == "yes" ) : ?>
		<div id="suprempay-ticket-form" class="suprempay-method-form" >
			<?php if ( $cc == "no" && $pix == "no" && $transfer == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_ticket ); ?>" alt="<?php esc_attr_e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com boleto.", WC_SUPREMPAY_DOMAIN ); ?>
				</h4>
			<?php endif; ?>

			<p><?php _e( "O pedido será confirmado somente após a confirmação do pagamento.", WC_SUPREMPAY_DOMAIN ); ?></p>
			<p><?php _e( "* Ao finalizar a compra, você terá acesso ao código de barras do boleto bancário que poderá pagar no seu internet banking ou em uma lotérica.", WC_SUPREMPAY_DOMAIN ); ?></p>
			<div class="clear" ></div>
		</div>
	<?php endif; ?>

	<?php if ( $transfer == "yes" ) : ?>
		<div id="suprempay-transfer-form" class="suprempay-method-form" >
			<?php if ( $cc == "no" && $pix == "no" && $ticket == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_transfer ); ?>" alt="<?php esc_attr_e( "PIX", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento por transferência.", WC_SUPREMPAY_DOMAIN ); ?>
				</h4>
			<?php endif; ?>

			<p><?php _e( "Faça transferência diretamente da sua conta SupremCash.", WC_SUPREMPAY_DOMAIN ); ?></p>
			<p id="suprempay-auth-email" class="form-row form-row-first" >
				<label for="suprempay-auth-email-field" >
					<?php _e( "E-mail da conta", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-auth-email-field"
					name="suprempay_auth_email" class="input-text" type="email"
					autocomplete="off" style="font-size:1.5em; padding:8px; height:50px;"
					placeholder="<?php _e( "E-mail da conta SupremCash", WC_SUPREMPAY_DOMAIN ); ?>" />
			</p>
			<p id="suprempay-auth-code" class="form-row form-row-last" >
				<label for="suprempay-auth-code-field" >
					<?php _e( "Código de segurança", WC_SUPREMPAY_DOMAIN ); ?> <span class="required" >*</span>
				</label>
				<input id="suprempay-auth-code-field" class="input-text wc-transfer-auth-code"
					name="suprempay_auth_code" maxlenght="6" pattern="[0-9]{6}" title="O código tem seis (6) números"
					type="tel" autocomplete="off" style="font-size:1.5em; padding:8px; height:50px;"
					placeholder="<?php _e( "Autenticação de 2 fatores", WC_SUPREMPAY_DOMAIN ); ?>" />
			</p>
			<div class="clear" ></div>
		</div>
	<?php endif; ?>

	<p><?php esc_html_e( "Esta compra está sendo feita no Brasil", WC_SUPREMPAY_DOMAIN ); ?> <img src="<?php echo esc_url( $flag ); ?>" alt="<?php esc_attr_e( "Bandeira do Brasil", WC_SUPREMPAY_DOMAIN ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" /></p>

</fieldset>
