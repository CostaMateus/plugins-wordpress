<?php
/**
 * Checkout form.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Templates
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;
?>

<fieldset id="pagare-payment-form"
	class="<?php echo "storefront" === basename( get_template_directory() ) ? "pagare-gateway-form-storefront" : ""; ?>" data-cart_total="<?php echo esc_attr( number_format( $cart_total, 2, ".", "" ) ); ?>">

	<ul id="pagare-payment-methods" style="list-style:none; padding-left:0px;" >
		<?php if ( $cc     == "yes" ) : ?>
			<li>
				<label>
					<input id="pagare-payment-method-cc" style="margin-right:0;"
						type="radio" name="pagare_payment_method"
						value="cc" <?php checked( true, ( $cc == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_cc ); ?>" alt="<?php esc_attr_e( "Cartão de crédito", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Cartão de crédito", "pagare-gateway" ); ?>
				</label>
			</li>
		<?php endif; ?>

		<?php if ( $pix    == "yes" ) : ?>
			<li>
				<label>
					<input id="pagare-payment-method-pix" style="margin-right:0;"
						type="radio" name="pagare_payment_method"
						value="pix" <?php checked( true, ( $cc == "no" && $pix == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_pix ); ?>" alt="<?php esc_attr_e( "PIX", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "PIX", "pagare-gateway" ); ?>
				</label>
			</li>
		<?php endif; ?>

		<?php if ( $ticket == "yes" ) : ?>
			<li>
				<label>
					<input id="pagare-payment-method-ticket" style="margin-right:0;"
						type="radio" name="pagare_payment_method"
						value="ticket" <?php checked( true, ( $cc == "no" && $pix == "no" && $ticket == "yes" ), true ); ?> />
					<img src="<?php echo esc_url( $icon_ticket ); ?>" alt="<?php esc_attr_e( "Boleto", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Boleto", "pagare-gateway" ); ?>
				</label>
			</li>
		<?php endif; ?>
	</ul>

	<div class="clear"></div>

	<?php if ( $cc  == "yes" ) : ?>
		<div id="pagare-cc-form" class="pagare-method-form">
			<?php if ( $pix == "no" && $ticket == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_cc ); ?>" alt="<?php esc_attr_e( "PIX", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com cartão de Crédito.", "pagare-gateway" ); ?>
				</h4>
			<?php endif; ?>

			<p id="pagare-legal-person" class="form-row form-row-wide" style="margin-bottom:8px;" >
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox"
					for="pagare-legal-person-field" >
					<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
						name="pagare_legal_person_field" id="pagare-legal-person-field" >
					<span>Pessoa Jurídica?</span>
				</label>
			</p>

			<p id="pagare-card-holder-name" class="form-row form-row-first">
				<label for="pagare-card-holder-name-field">
					<?php _e( "Nome do Titular", "pagare-gateway" ); ?>
					<small>(<?php _e( "como escrito no cartão", "pagare-gateway" ); ?>) </small> <span class="required">*</span>
				</label>
				<input id="pagare-card-holder-name-field"
					name="pagare_card_holder_name" class="input-text" type="text"
					autocomplete="off" style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="pagare-card-number" class="form-row form-row-last">
				<label for="pagare-card-number-field">
					<?php _e( "Número do cartão", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-number-field"
					class="input-text wc-credit-card-form-card-number" type="tel" maxlength="20" autocomplete="off"
					placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear"></div>

			<p id="pagare-card-expiry" class="form-row form-row-first">
				<label for="pagare-card-expiry-field">
					<?php _e( "Validade (MM/AAAA)", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-expiry-field"
					class="input-text wc-credit-card-form-card-expiry"
					type="tel" autocomplete="off" placeholder="<?php _e( "MM/AAAA", "pagare-gateway" ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="pagare-card-cvc" class="form-row form-row-last">
				<label for="pagare-card-cvc-field">
					<?php _e( "Código de segurança", "pagare-gateway" ); ?> <span class="required">*</span></label>
				<input id="pagare-card-cvc-field"
					class="input-text wc-credit-card-form-card-cvc"
					type="tel" autocomplete="off" placeholder="<?php _e( "CVC", "pagare-gateway" ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear"></div>

			<p id="pagare-card-installment" class="form-row form-row-first">
				<label for="pagare-card-installment-field">
					<?php _e( "Parcelas", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<select id="pagare-card-installment-field" name="pagare_card_installment"
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
			<p id="pagare-card-holder-cpf" class="form-row form-row-last">
				<label for="pagare-card-holder-cpf-field">
					<?php _e( "CPF do Titular", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-holder-cpf-field" name="pagare_card_holder_cpf"
					class="input-text wecfb-cpf" type="tel" autocomplete="off" maxlength="18"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="pagare-card-holder-cnpj" class="form-row form-row-last">
				<label for="pagare-card-holder-cnpj-field">
					<?php _e( "CNPJ do Titular", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-holder-cnpj-field" name="pagare_card_holder_cnpj"
					class="input-text wecfb-cnpj" type="tel" autocomplete="off" maxlength="18"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear"></div>

			<p id="pagare-card-holder-birth-date" class="form-row form-row-first">
				<label for="pagare-card-holder-birth-date-field">
					<?php _e( "Data de nascimento do Titular", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-holder-birth-date-field" name="pagare_card_holder_birth_date"
					class="input-text" type="tel" autocomplete="off"
					placeholder="<?php _e( "DD / MM / YYYY", "pagare-gateway" ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>
			<p id="pagare-card-holder-phone" class="form-row form-row-last">
				<label for="pagare-card-holder-phone-field">
					<?php _e( "Telefone do Titular", "pagare-gateway" ); ?> <span class="required">*</span>
				</label>
				<input id="pagare-card-holder-phone-field" name="pagare_card_holder_phone"
					class="input-text" type="tel" autocomplete="off"
					placeholder="<?php _e( "(xx) xxxx-xxxx", "pagare-gateway" ); ?>"
					style="font-size:1.5em; padding:8px; height:50px;" />
			</p>

			<div class="clear"></div>
		</div>
	<?php endif; ?>

	<?php if ( $pix == "yes" ) : ?>
		<div id="pagare-pix-form" class="pagare-method-form">
			<?php if ( $cc == "no" && $ticket == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_pix ); ?>" alt="<?php esc_attr_e( "PIX", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com PIX.", "pagare-gateway" ); ?>
				</h4>
			<?php endif; ?>

			<p><?php _e( "O pedido será confirmado somente após a confirmação do pagamento.", "pagare-gateway" ); ?></p>
			<p><?php _e( "* Ao finalizar a compra, você terá acesso ao código Pix para pagamento na próxima tela.", "pagare-gateway" ); ?></p>
			<div class="clear"></div>
		</div>
	<?php endif; ?>

	<?php if ( $ticket == "yes" ) : ?>
		<div id="pagare-ticket-form" class="pagare-method-form">
			<?php if ( $cc == "no" && $pix == "no" ) : ?>
				<h4>
					<img src="<?php echo esc_url( $icon_ticket ); ?>" alt="<?php esc_attr_e( "PIX", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" />
					<?php _e( "Pagamento com boleto.", "pagare-gateway" ); ?>
				</h4>
			<?php endif; ?>

			<p><?php _e( "O pedido será confirmado somente após a confirmação do pagamento.", "pagare-gateway" ); ?></p>
			<p><?php _e( "* Ao finalizar a compra, você terá acesso ao código de barras do boleto bancário que poderá pagar no seu internet banking ou em uma lotérica.", "pagare-gateway" ); ?></p>
			<div class="clear"></div>
		</div>
	<?php endif; ?>

	<p><?php esc_html_e( "Esta compra está sendo feita no Brasil", "pagare-gateway" ); ?> <img src="<?php echo esc_url( $flag ); ?>" alt="<?php esc_attr_e( "Bandeira do Brasil", "pagare-gateway" ); ?>" style="display:inline; float:none; vertical-align:middle; border:none; margin-left:0;" /></p>

</fieldset>
