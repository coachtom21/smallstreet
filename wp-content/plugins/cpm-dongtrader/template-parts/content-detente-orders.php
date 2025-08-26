<?php

defined('ABSPATH') || exit;
$order_details = get_user_meta(get_current_user_id(), '_buyer_details', true);
$filter_template_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'filter-top.php';
$pagination_template_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'pagination-buttom.php';
extract($args);


$paid_orders = get_user_orders(['completed']);
$unpaid_backorders = get_user_orders(['on-hold', 'processing', 'pending']);

$can_user_pay = get_user_meta(get_current_user_id(), 'can_pay', true);
$can_user_pay = $can_user_pay == '1' ? true : false;
$is_order_payable = (isLastDayOfMonth() && $can_user_pay) ? true : false;

$pay_btn = '';

$seven_percent_total_unfunded = 0;
$seven_percent_total_funded = 0;


?>
<div class="detente-orders cpm-table-wrap">
	<h3><?php esc_html_e('My orders', 'cpm-dongtrader'); ?></h3>
	<br class="clear" />

	<div id="member-history-orders" class="widgets-holder-wrap">
		<?php if (file_exists($filter_template_path) && !empty($order_details))
			load_template($filter_template_path, true, $order_details); ?>
		<table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0"
			border="0">
			<thead>
				<tr>
					<th><?php esc_html_e('Order ID/Date', 'cpm-dongtrader'); ?>
					<th><?php esc_html_e('Membership', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('7% Buyer', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('3% Seller', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('Annual Refferals', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('Total 1099-Patr Form', 'cpm-dongtrader'); ?></th>
				</tr>
			</thead>
			<?php
			echo '<tbody>';
			if (!empty($order_details)):
				$paginated_orders = dongtrader_pagination_array($order_details, 10, true);
				$rebate_sum = array_sum(array_column($order_details, 'rebate'));
				$rebate_d_sum = array_sum(array_column($order_details, 'rebate_d'));
				$annual_refferal_sum = 0;
				$total_sum = array_sum(array_column($order_details, 'total'));

				foreach ($paginated_orders as $od):
					if (get_post_type($od['order_id']) != 'shop_order')
						continue;
					$order = new WC_Order($od['order_id']);
					$formatted_order_date = wc_format_datetime($order->get_date_created(), 'Y-m-d');
					echo '<tr>';
					echo '<td>' . $od['order_id'] . '/' . $formatted_order_date . '</td>';
					echo '<td>' . $od['membership'] . '</td>';
					echo '<td>' . $symbol . $od['rebate'] * $vnd_rate . '</td>';
					echo '<td>' . $symbol . $od['rebate_d'] * $vnd_rate . '</td>';
					echo '<td>' . $symbol . 0 * $vnd_rate . '</td>';
					echo '<td>' . $symbol . $od['total'] * $vnd_rate . '</td>';
					echo '</tr>';
				endforeach;
				echo '<tfoot>';
				echo '<tr>';
				echo '<td colspan="2">All Totals</td>';
				echo '<td>' . $symbol . $rebate_sum * $vnd_rate . '</td>';
				echo '<td>' . $symbol . $rebate_d_sum * $vnd_rate . '</td>';
				echo '<td>' . $symbol . $annual_refferal_sum * $vnd_rate . '</td>';
				echo '<td>' . $symbol . $total_sum * $vnd_rate . '</td>';
				echo '</tr>';
				echo '</tfoot>';
			else:
				echo '<tr>';
				echo '<td style="text-align:center;"colspan="6" >Details Not Found</td>';
				echo '</tr>';
			endif;
			echo '</tbody>'; ?>
		</table>
	</div>
	<?php if (file_exists($pagination_template_path) && !empty($order_details))
		load_template($pagination_template_path, true, $order_details); ?>



	<!-- ============================== -->
	<!-- ============================== -->
	<!-- ============================== -->

	<?php
	echo '<h5>Unpaid Backorders</h5>';
	if (!empty($unpaid_backorders)) { ?>
		<table
			class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span
							class="nobr">Order</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span
							class="nobr">Date</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">Total</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">7%(unfunded)s</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span
							class="nobr">Actions</span></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ($unpaid_backorders as $order) {
					$total_quantity = 0;
					$seven_percent = ($order->get_total() * 0.07);
					$seven_percent_total_unfunded += $seven_percent;

					foreach ($order->get_items() as $item_id => $item) {
						$total_quantity += $item->get_quantity();
					}

					if ($is_order_payable) {
						$pay_btn = '<a href="' . esc_url($order->get_checkout_payment_url()) . '" class="woocommerce-button wp-element-button button view">Pay Now</a>';
					}

					echo '
					<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">
							<a href="' . esc_url($order->get_view_order_url()) . '">#' . $order->get_id() . '</a>
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
							<time datetime="2024-06-17T05:17:35+00:00">' . date_i18n('F j, Y', strtotime($order->get_date_created())) . '</time>
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
							<span class="woocommerce-Price-amount amount">' . $order->get_formatted_order_total() . ' for ' . $total_quantity . ' item(s)
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
							<span class="woocommerce-Price-amount amount">' . wc_price($seven_percent) . '</span>
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
							' . $pay_btn . '
						</td>
					</tr>
					';

				} ?>
			</tbody>
		</table>
		<p>Total 7%(unfunded): <?php echo wc_price($seven_percent_total_unfunded); ?></p>
		<br class="clear" />
		<?php
	} else {
		echo '<p>No unpaid backorders found.</p>';
	}

	if (!empty($paid_orders)) { ?>
		<table
			class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span
							class="nobr">Order</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span
							class="nobr">Date</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">Total</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">7%(funded)</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">XP</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span
							class="nobr">Actions</span></th>
				</tr>
			</thead>

			<tbody>
				<?php
				$total_paid_amount = 0;
				$seven_percent_total_funded = 0;
				$grand_total_xp = 0; // <-- overall total XP
			
				foreach ($paid_orders as $order) {
					$total_quantity = 0;
					$total_paid_amount += $order->get_total();
					$seven_percent = ($order->get_total() * 0.07);
					$seven_percent_total_funded += $seven_percent;

					// Get XP earned from user meta _buyer_details
					$customer_id = $order->get_customer_id();
					$buyer_details = get_user_meta($customer_id, '_buyer_details', true);
					$xp_earned = 0;
					$total_xp = 0;

					if (!empty($buyer_details) && is_array($buyer_details)) {
						// XP for this order (first element in array)
						$xp_earned = $buyer_details[0]['xp_awarded'];

						// Total XP across all buyer orders
						$total_xp = array_sum(array_column($buyer_details, 'xp_awarded'));
						$grand_total_xp += $total_xp; // add to global XP
					}


					foreach ($order->get_items() as $item_id => $item) {
						$total_quantity += $item->get_quantity();
					}

					echo '
						<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-completed order">
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-number" data-title="Order">
								<a href="' . esc_url($order->get_view_order_url()) . '">#' . $order->get_id() . '</a>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-date" data-title="Date">
								<time datetime="' . esc_attr($order->get_date_created()) . '">' . date_i18n('F j, Y', strtotime($order->get_date_created())) . '</time>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
								<span class="woocommerce-Price-amount amount">' . $order->get_formatted_order_total() . ' for ' . $total_quantity . ' item(s)</span>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Total">
								<span class="woocommerce-Price-amount amount">' . wc_price($seven_percent) . '</span>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="XP">
								<span class="woocommerce-Price-amount amount">' . number_format($xp_earned) . ' XP</span>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
								<a href="' . esc_url($order->get_view_order_url()) . '" class="woocommerce-button wp-element-button button view">View</a>
							</td>
						</tr>
						';
				}

				//calculate yam
				$yam_total = 0;
				$usd_total = 0;

				$yam_total = (float) ($grand_total_xp) / 4.7619e16;
				$usd_total = $yam_total / 21000;
				
				?>
			</tbody>

		</table>
		<p>Total 7%(funded): <?php echo wc_price($seven_percent_total_funded); ?></p>
		<p>Total Paid Amount: <?php echo wc_price($total_paid_amount); ?></p>
		<p>Total XP Earned: <?php echo number_format($grand_total_xp) . " " . "XP"; ?></p>
		<p>Total YAM Earned: <?php echo number_format($yam_total, 10) . " " . "YAM"; ?></p>
		<p>Total USD Earned: <?php echo number_format($usd_total, 10) . " " . "USD"; ?></p>
		<br class="clear" />
		<?php
	} else {
		echo '<p>No paid orders found.</p>';
	} ?>
</div>