<?php
/**
 * Side Cart Footer
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/xoo-wsc-footer.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen.
 * @see     https://docs.xootix.com/side-cart-woocommerce/
 * @version 3.0
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

extract( Xoo_Wsc_Template_Args::cart_footer() );

ob_start();
xoo_wsc_helper()->get_template( 'global/footer/suggested-products.php' );
$suggestedProducts  = ob_get_clean();

?>


<?php if( $spPosition === 'before' ) echo $suggestedProducts; ?>

<?php xoo_wsc_helper()->get_template( 'global/footer/extras.php' ) ?>

<?php xoo_wsc_helper()->get_template( 'global/footer/totals.php' ) ?>

<?php xoo_wsc_helper()->get_template( 'global/footer/buttons.php' ); ?>


<?php if( $spPosition === 'after' ) echo $suggestedProducts; ?>