<?php
/**
 * HTML email instructions.
 *
 * @author Mateus Costa <mateus@costamateus.com.br>
 *
 * @package WooCommerce_Pagare/Templates
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

$upload     = wp_upload_dir();
$uploadPath = $upload[ "basedir" ] . "/woo-pagare/";
$uploadUrl  = $upload[ "baseurl" ] . "/woo-pagare/";
$uploaded   = false;

if ( !file_exists( $uploadPath ) ) wp_mkdir_p( $uploadPath );

if ( isset( $image ) && !empty( $image ) )
{
	$data        = substr( $image, strpos( $image, "," ) + 1 );
	$data        = str_replace( " ", "+", $data );
	$decodedData = base64_decode( $data );
	$imageName   = uniqid() . ".png";
	$file        = $uploadPath . $imageName;
	$uploaded    = file_put_contents( $file, $decodedData );
}
?>

<p>
	Caso tenha perdido o link para pagamento, ou fechado antes da conclusão, você pode encontrá-lo na sua conta,
	<a href="<?php echo esc_url( get_permalink( get_option( "woocommerce_myaccount_page_id" ) ) ); ?>" title="Minha conta" >clicando aqui</a>.
	<br>
	<?php echo $message; ?>
</p>

<div style="margin: 36px auto;">
	<?php if ( $uploaded ) : ?>
		<h3 style="font-size: 18px;">Pague com o código abaixo</h3>
		<img style="display:table; background-color:#FFF" src="<?php echo esc_url( $uploadUrl . $imageName ); ?>" alt="Code" />
		<br>
	<?php endif; ?>

	<h3 style="font-size: 18px;">Pague copiando e colando o código abaixo</h3>
	<p class="rppix-p" style="font-size: 14px;margin-bottom:0"><?php echo $link; ?></p>
</div>