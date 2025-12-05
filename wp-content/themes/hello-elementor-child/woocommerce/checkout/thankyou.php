<?php
/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php wc_get_template( 'checkout/order-received.php', array( 'order' => $order ) ); ?>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Order number:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Date:', 'woocommerce' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email:', 'woocommerce' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Total:', 'woocommerce' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
		
		<?php
		// Show success popup if order is on-hold (payment received but funds held)
		if ($order->has_status('on-hold') && $order->is_paid()) {
			// Get 7% rebate amount
			$rebate_amount = get_post_meta($order->get_id(), 'mega_cashback_v', true);
			$rebate_amount = !empty($rebate_amount) ? floatval($rebate_amount) : 0;
			
			// Calculate XP amount (7% of trade value)
			$trade_value = 10.30; // Base trade value
			$buyer_percentage = 0.07; // 7%
			$buyer_reward = $trade_value * $buyer_percentage; // $0.721
			$xp_amount = $buyer_reward * pow(10, 23); // Convert to XP
			$xp_amount_formatted = number_format($xp_amount, 0, '.', ',');
			?>
			<div id="payment-success-popup" class="payment-success-popup" style="display: none;">
				<div class="payment-success-popup-overlay"></div>
				<div class="payment-success-popup-content">
					<div class="payment-success-popup-header">
						<h2>Order Successful!</h2>
						<button class="payment-success-popup-close" onclick="closePaymentSuccessPopup()">&times;</button>
					</div>
					<div class="payment-success-popup-body">
						<div class="success-icon">✓</div>
						<p class="success-message">Your order has been received successfully!</p>
						<div class="reward-info">
							<p class="reward-amount">You have received <strong>7% of trade value</strong></p>
							<p class="reward-value"><?php echo wc_price($rebate_amount); ?></p>
							<p class="xp-info">Equivalent XP Amount:</p>
							<p class="xp-amount"><?php echo esc_html($xp_amount_formatted); ?> XP</p>
							<p class="xp-status-note"><em>Note: XP is currently unfunded and cannot be redeemed until funds are released on August 31st.</em></p>
						</div>
					</div>
					<div class="payment-success-popup-footer">
						<button class="payment-success-popup-button" onclick="closePaymentSuccessPopup()">Close</button>
					</div>
				</div>
			</div>
			<style>
				.payment-success-popup {
					position: fixed;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					z-index: 99999;
					display: flex;
					align-items: center;
					justify-content: center;
				}
				.payment-success-popup-overlay {
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
					background: rgba(0, 0, 0, 0.7);
				}
				.payment-success-popup-content {
					position: relative;
					background: #fff;
					border-radius: 10px;
					padding: 30px;
					max-width: 500px;
					width: 90%;
					box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
					z-index: 100000;
				}
				.payment-success-popup-header {
					display: flex;
					justify-content: space-between;
					align-items: center;
					margin-bottom: 20px;
					padding-bottom: 15px;
					border-bottom: 2px solid #f0f0f0;
				}
				.payment-success-popup-header h2 {
					margin: 0;
					color: #333;
					font-size: 24px;
				}
				.payment-success-popup-close {
					background: none;
					border: none;
					font-size: 30px;
					cursor: pointer;
					color: #999;
					line-height: 1;
				}
				.payment-success-popup-close:hover {
					color: #333;
				}
				.payment-success-popup-body {
					text-align: center;
				}
				.success-icon {
					width: 80px;
					height: 80px;
					border-radius: 50%;
					background: #4CAF50;
					color: #fff;
					display: flex;
					align-items: center;
					justify-content: center;
					font-size: 40px;
					margin: 0 auto 20px;
				}
				.success-message {
					font-size: 18px;
					color: #333;
					margin-bottom: 20px;
				}
				.reward-info {
					background: #f9f9f9;
					padding: 20px;
					border-radius: 8px;
					margin-top: 20px;
				}
				.reward-amount {
					font-size: 16px;
					color: #666;
					margin-bottom: 10px;
				}
				.reward-value {
					font-size: 28px;
					font-weight: bold;
					color: #4CAF50;
					margin-bottom: 20px;
				}
				.xp-info {
					font-size: 14px;
					color: #666;
					margin-top: 15px;
					margin-bottom: 5px;
				}
				.xp-amount {
					font-size: 22px;
					font-weight: bold;
					color: #2196F3;
					margin-bottom: 15px;
				}
				.xp-status-note {
					font-size: 12px;
					color: #999;
					margin-top: 15px;
					padding-top: 15px;
					border-top: 1px solid #e0e0e0;
				}
				.payment-success-popup-footer {
					margin-top: 25px;
					text-align: center;
				}
				.payment-success-popup-button {
					background: #4CAF50;
					color: #fff;
					border: none;
					padding: 12px 30px;
					border-radius: 5px;
					font-size: 16px;
					cursor: pointer;
					transition: background 0.3s;
				}
				.payment-success-popup-button:hover {
					background: #45a049;
				}
			</style>
			<script>
				function closePaymentSuccessPopup() {
					document.getElementById('payment-success-popup').style.display = 'none';
				}
				
				// Show popup when page loads
				document.addEventListener('DOMContentLoaded', function() {
					var popup = document.getElementById('payment-success-popup');
					if (popup) {
						popup.style.display = 'flex';
					}
				});
			</script>
			<?php
		}
		?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', array( 'order' => false ) ); ?>

	<?php endif; ?>

</div>
