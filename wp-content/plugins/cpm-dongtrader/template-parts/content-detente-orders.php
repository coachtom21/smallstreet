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
				echo '<td style="text-align:center;"colspan="7" >Details Not Found</td>';
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
					} else {
						$pay_btn = '<span style="color: #95a5a6; font-style: italic;">Not Payable</span>';
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
							<span class="woocommerce-Price-amount amount">' . $order->get_formatted_order_total() . ' for ' . $total_quantity . ' item(s)</span>
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
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">Status</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span
							class="nobr">Actions</span></th>
				</tr>
			</thead>

			<tbody>
				<?php
				$total_paid_amount = 0;
				$seven_percent_total_funded = 0;
				$grand_total_xp = 0; // <-- overall total XP
				$total_pending_xp = 0; // <-- overall pending XP
				$total_completed_xp = 0; // <-- overall completed XP
			
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
					$pending_xp = 0;
					$completed_xp = 0;

					if (!empty($buyer_details) && is_array($buyer_details)) {
						// XP for this order (first element in array)
						$xp_earned = $buyer_details[0]['xp_awarded'];

						// Calculate pending vs completed XP across all buyer orders
						foreach ($buyer_details as $transaction) {
							if (isset($transaction['xp_awarded'])) {
								$total_xp += intval($transaction['xp_awarded']);
								
								// Check if XP is pending or completed based on Discord membership
								if (isset($transaction['discord_member']) && $transaction['discord_member']) {
									$completed_xp += intval($transaction['xp_awarded']);
								} else {
									$pending_xp += intval($transaction['xp_awarded']);
								}
							}
						}
						$grand_total_xp += $total_xp; // add to global XP
						$total_pending_xp += $pending_xp; // add to global pending XP
						$total_completed_xp += $completed_xp; // add to global completed XP
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
								<span class="woocommerce-Price-amount amount">' . number_format($total_xp) . ' XP</span>
							</td>
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Status">
								<span class="woocommerce-Price-amount amount">' . ($pending_xp > 0 ? 'Pending' : 'Completed') . '</span>
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
		
		<!-- XP Summary Section -->
		<div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 15px 0;">
			<h4 style="color: #2c3e50; margin: 0 0 15px 0; border-bottom: 2px solid #7f8c8d; padding-bottom: 8px;">XP Rewards Summary</h4>
			
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
				<div>
					<p style="margin: 8px 0; font-size: 16px;">
						<strong style="color: #2c3e50;">Total XP Earned:</strong><br>
						<span style="color: #2c3e50; font-size: 20px; font-weight: bold;"><?php echo number_format($grand_total_xp); ?> XP</span>
					</p>
					
					<p style="margin: 8px 0;">
						<strong style="color: #2c3e50;">XP Status:</strong><br>
						<span style="color: #f39c12; font-weight: bold;"><?php echo number_format($total_pending_xp); ?> Pending</span> 
						<small style="color: #7f8c8d;">(Awaiting Discord verification)</small><br>
					</p>
				</div>
				
				<div>
					<p style="margin: 8px 0;">
						<strong style="color: #2c3e50;">Conversion Rates:</strong><br>
						<small style="color: #2c3e50;">1 USD = 21,000 YAM</small><br>
						<small style="color: #2c3e50;">1 YAM = $0.0000476 USD</small><br>
						<small style="color: #2c3e50;">1 YAM = 47,619,047,619,047,619 XP</small><br>
						<small style="color: #2c3e50;">1 USD = 1,000,000,000,000,000,000,000 XP</small>
					</p>
				</div>
			</div>
		</div>
		
		<!-- Currency Conversion Section -->
		<div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 15px 0;">
			<h4 style="color: #2c3e50; margin: 0 0 15px 0; border-bottom: 2px solid #7f8c8d; padding-bottom: 8px;">Currency Conversions</h4>
			
			<?php
			// Calculate correct XP to YAM conversion using atomic units
			$xp_per_yam = 47619047619047619; // 10^21 / 21,000
			$xp_to_yam = $grand_total_xp / $xp_per_yam;
			
			// Calculate patronage split (10% total of gross)
			$total_patronage_xp = $usd_total * 0.10 * pow(10, 21); // 10% in XP
			$buyer_patronage_xp = $usd_total * 0.07 * pow(10, 21); // 7% in XP
			$seller_patronage_xp = $usd_total * 0.03 * pow(10, 21); // 3% in XP
			?>
			
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
				<div>
					<p style="margin: 8px 0;">
						<strong style="color: #2c3e50;">YAM Tokens:</strong><br>
						<span style="color: #e67e22; font-size: 18px; font-weight: bold;"><?php echo number_format($yam_total, 10); ?> YAM</span><br>
						<small style="color: #2c3e50;">Value: $<?php echo number_format($yam_total / 21000, 2); ?> USD</small><br>
						<small style="color: #2c3e50;">XP Value: <?php echo number_format($yam_total * $xp_per_yam, 0); ?> XP</small>
					</p>
				</div>
				
				<div>
					<p style="margin: 8px 0;">
						<strong style="color: #2c3e50;">USD Value:</strong><br>
						<span style="color: #2c3e50; font-size: 18px; font-weight: bold;">$<?php echo number_format($usd_total, 2); ?> USD</span><br>
						<small style="color: #2c3e50;">Equivalent: <?php echo number_format($usd_total * 21000, 0); ?> YAM</small><br>
						<small style="color: #2c3e50;">XP Value: <?php echo number_format($usd_total * pow(10, 21), 0); ?> XP</small>
					</p>
				</div>
			</div>
			
			<!-- Patronage Split Section -->
			<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
				<h5 style="color: #2c3e50; margin: 0 0 10px 0;">Patronage Split (10% of Gross Value)</h5>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
					<div>
						<p style="margin: 5px 0;">
							<strong style="color: #2c3e50;">Buyer Reward (7%):</strong><br>
							<span style="color: #2c3e50; font-weight: bold;"><?php echo number_format($buyer_patronage_xp, 0); ?> XP</span><br>
							<small style="color: #2c3e50;">$<?php echo number_format($usd_total * 0.07, 2); ?> USD</small>
						</p>
					</div>
					<div>
						<p style="margin: 5px 0;">
							<strong style="color: #2c3e50;">Seller Reward (3%):</strong><br>
							<span style="color: #e67e22; font-weight: bold;"><?php echo number_format($seller_patronage_xp, 0); ?> XP</span><br>
							<small style="color: #2c3e50;">$<?php echo number_format($usd_total * 0.03, 2); ?> USD</small>
						</p>
					</div>
				</div>
			</div>
		</div>
		<br class="clear" />
		<?php
	} else {
		echo '<p>No paid orders found.</p>';
	} ?>
</div>