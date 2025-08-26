<?php
/**
 * Side Cart Header
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/xoo-wsc-header.php.
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

extract( Xoo_Wsc_Template_Args::cart_header() );

?>

<div class="xoo-wsch-top">

	<?php if( $showNotifications ): ?>
		<?php xoo_wsc_cart()->print_notices_html( 'cart' ); ?>
	<?php endif; ?>

	<?php if( $showBasket ): ?>
		<div class="xoo-wsch-basket">
			<span class="xoo-wscb-icon xoo-wsc-icon-bag2"></span>
			<span class="xoo-wscb-count"><?php echo xoo_wsc_cart()->get_cart_count() ?></span>
		</div>
	<?php endif; ?>

	<?php if( $heading ): ?>
		<span class="xoo-wsch-text"><?php echo $heading ?></span>
	<?php endif; ?>

	<?php if( $showCloseIcon ): ?>
		<span class="xoo-wsch-close <?php echo  $close_icon ?>"></span>
	<?php endif; ?>

</div>
<div style="margin-top:10px;">
	<video controls width="100%"
		poster="https://legacytoliveby.org/wp-content/uploads/2025/05/Screenshot-2025-05-16-135409.png">
		<source
			src="https://legacytoliveby.org/wp-content/uploads/2025/05/invideo-ai-1080-Unlock-the-Cookie-Jar-Economy-with-YAM-Vouchers-2025-05-10T13_13_41-2.mp4"
			type="video/mp4">
		Your browser does not support the video tag.
	</video>
</div>

<?php xoo_wsc_helper()->get_template( 'global/header/shipping-bar.php' ) ?>