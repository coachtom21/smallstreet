<?php
/**
 * Quantity Input
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/global/body/qty-input.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen.
 * @see     https://docs.xootix.com/side-cart-woocommerce/
 * @version 3.0
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

?>

<div class="xoo-wsc-qty-box xoo-wsc-qtb-<?php echo $qtyDesign ?>">

	<?php do_action('xoo_wsc_before_quantity_input_field');
	$min = $min_value;
	$max = (0 < $max_value) ? $max_value : '';

	if ($product_id == 1308) { // Example: YAM is on
		$min = 10;
		$max = 10;
	} elseif ($product_id == 4833) { // Example: Modern Piggy Bank
		$min = 30;
		$max = 30;
	}

	$product_id = isset($product_id) ? $product_id : (isset($args['parent_id']) ? $args['parent_id'] : 0);

	if ($product_id != 4833 && $product_id != 1308): ?>
		<span class="xoo-wsc-minus xoo-wsc-chng" id="qty_sub_cart">-</span>
	<?php endif; ?>

	<input type="<?php echo $product_id != 4833 && $product_id != 1308?'number':'text';?>" class="<?php echo esc_attr(join(' ', (array) $wsc_classes)); ?>"
		step="<?php echo esc_attr($step); ?>" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>"
		value="<?php echo esc_attr($input_value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>"
		inputmode="<?php echo esc_attr($inputmode); ?>" data-product_id="<?php echo esc_attr($product_id); ?>"
		<?php echo $product_id != 4833 && $product_id != 1308?'':'readonly';?> />

	<?php do_action('xoo_wsc_after_quantity_input_field'); ?>

	<?php
	if ($product_id != 4833 && $product_id != 1308): ?>
		<span class="xoo-wsc-plus xoo-wsc-chng" id="qty_add_cart">+</span>
	<?php endif; ?>


</div>