<?php

defined('ABSPATH') || exit;

// Get all orders for the current user from WooCommerce (instead of relying on disabled _buyer_details)
$user_id = get_current_user_id();
$all_user_orders = [];
if ($user_id) {
    // Get all WooCommerce order statuses to include all orders
    $all_statuses = array_keys(wc_get_order_statuses());
    // Remove 'wc-' prefix if present
    $all_statuses = array_map(function($status) {
        return str_replace('wc-', '', $status);
    }, $all_statuses);
    
    $args = [
        'customer_id' => $user_id,
        'status' => $all_statuses, // Include all order statuses
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    $wc_orders = wc_get_orders($args);
    
    // Convert WooCommerce orders to the format expected by the table
    foreach ($wc_orders as $order) {
        if (!is_a($order, 'WC_Order')) {
            continue;
        }
        
        $order_id = $order->get_id();
        $membership_name = get_post_meta($order_id, '_membership_name', true);
        $rebate = get_post_meta($order_id, 'mega_cashback_v', true);
        $rebate_d = get_post_meta($order_id, 'mega_cashback_d', true);
        $total = $order->get_total();
        
        $all_user_orders[] = [
            'order_id' => $order_id,
            'membership' => !empty($membership_name) ? $membership_name : 'N/A',
            'rebate' => !empty($rebate) ? floatval($rebate) : 0,
            'rebate_d' => !empty($rebate_d) ? floatval($rebate_d) : 0,
            'total' => !empty($total) ? floatval($total) : 0,
        ];
    }
}

$order_details = $all_user_orders; // Use WooCommerce orders instead of usermeta
$filter_template_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'filter-top.php';
$pagination_template_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'pagination-buttom.php';
extract($args);


// ============================================
// UNPAID BACKORDERS - Get ALL unpaid orders for current user
// ============================================
$unpaid_backorders = [];

if ($user_id) {
    // Get all WooCommerce order statuses to ensure we check ALL orders
    $all_statuses = array_keys(wc_get_order_statuses());
    // Remove 'wc-' prefix if present
    $all_statuses = array_map(function($status) {
        return str_replace('wc-', '', $status);
    }, $all_statuses);
    
    // Fetch ALL orders for the current user (all statuses)
    $args_unpaid = [
        'customer_id' => $user_id,
        'status' => $all_statuses, // Include all order statuses
        'limit' => -1, // Get all orders, no limit
        'orderby' => 'date',
        'order' => 'DESC', // Newest first
    ];
    
    $all_user_orders = wc_get_orders($args_unpaid);
    
    // Debug: Show all orders found
    $debug_info = [];
    $debug_info['total_orders_found'] = count($all_user_orders);
    
    // Filter to get ONLY unpaid orders
    foreach ($all_user_orders as $order) {
        if (!is_a($order, 'WC_Order')) {
            continue;
        }
        
        $order_id = $order->get_id();
        $order_status = $order->get_status();
        $payment_method = $order->get_payment_method();
        $order_date_obj = $order->get_date_created();
        $order_date = $order_date_obj ? $order_date_obj->format('Y-m-d H:i:s') : 'N/A';
        
        // Check if it's a preorder (preorders need special handling)
        $is_preorder = ($payment_method === 'preorder');
        
        // Fix preorder orders that are incorrectly set to on-hold or completed (only if unpaid)
        $is_paid_check = $order->is_paid();
        if ($is_preorder && !$is_paid_check && ($order_status === 'on-hold' || $order_status === 'completed')) {
            // Unpaid preorder should be pending
            $order->set_status('pending', __('Preorder order corrected to pending status', 'cpm-dongtrader'));
            $order->save();
            $order_status = 'pending'; // Update status after fix
        } elseif ($is_preorder && $is_paid_check && $order_status === 'on-hold') {
            // Paid preorder should be completed, not on-hold
            $order->set_status('completed', __('Paid preorder order corrected to completed status', 'cpm-dongtrader'));
            $order->save();
            $order_status = 'completed'; // Update status after fix
        }
        
        // Check if order is actually paid using WooCommerce's is_paid() method
        $is_paid = $order->is_paid();
        
        // Also check payment status directly
        $payment_status = $order->get_meta('_payment_status', true);
        // get_total_paid() might not exist in all WooCommerce versions, use safe method
        $total_paid = method_exists($order, 'get_total_paid') ? $order->get_total_paid() : 0;
        $order_total = $order->get_total();
        
        // Debug info for each order
        $debug_info['orders'][] = [
            'id' => $order_id,
            'status' => $order_status,
            'payment_method' => $payment_method,
            'is_preorder' => $is_preorder,
            'is_paid' => $is_paid,
            'total' => $order_total,
            'total_paid' => $total_paid,
            'date' => $order_date,
        ];
        
        // For preorders, always consider them unpaid regardless of is_paid()
        // For other orders, check if they're actually paid
        if ($is_preorder) {
            // Preorders are always unpaid until payment is completed
            $unpaid_backorders[] = $order;
        } elseif (!$is_paid) {
            // For non-preorders, use WooCommerce's is_paid() check
            $unpaid_backorders[] = $order;
        }
    }
    
    // Debug output (remove after testing)
    if (current_user_can('administrator')) {
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; font-size: 11px;">';
        echo '<strong>Debug: Unpaid Backorders</strong><br>';
        echo 'Total Orders Found: ' . $debug_info['total_orders_found'] . '<br>';
        echo 'Unpaid Orders: ' . count($unpaid_backorders) . '<br>';
        if (!empty($debug_info['orders'])) {
            echo '<table style="width:100%; margin-top:10px; border-collapse:collapse;">';
            echo '<tr style="background:#ddd;"><th style="padding:5px; text-align:left;">Order ID</th><th style="padding:5px; text-align:left;">Status</th><th style="padding:5px; text-align:left;">Payment Method</th><th style="padding:5px; text-align:left;">Is Paid</th><th style="padding:5px; text-align:left;">Date</th><th style="padding:5px; text-align:left;">Included?</th></tr>';
            foreach ($debug_info['orders'] as $debug_order) {
                $included = false;
                foreach ($unpaid_backorders as $unpaid) {
                    if ($unpaid->get_id() == $debug_order['id']) {
                        $included = true;
                        break;
                    }
                }
                echo '<tr>';
                echo '<td style="padding:5px;">#' . $debug_order['id'] . '</td>';
                echo '<td style="padding:5px;">' . $debug_order['status'] . '</td>';
                echo '<td style="padding:5px;">' . $debug_order['payment_method'] . '</td>';
                echo '<td style="padding:5px;">' . ($debug_order['is_paid'] ? 'Yes' : 'No') . '</td>';
                echo '<td style="padding:5px;">' . $debug_order['date'] . '</td>';
                echo '<td style="padding:5px; color:' . ($included ? 'green' : 'red') . ';">' . ($included ? 'YES' : 'NO') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }
}

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
					<th><?php esc_html_e('Order ID/Date', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('Membership', 'cpm-dongtrader'); ?></th>
					<th><?php esc_html_e('7% Buyer', 'cpm-dongtrader'); ?></th>
				</tr>
			</thead>
			<?php
			echo '<tbody>';
			if (!empty($order_details)):
				$paginated_orders = dongtrader_pagination_array($order_details, 10, true);
				$rebate_sum = array_sum(array_column($order_details, 'rebate'));

				foreach ($paginated_orders as $od):
					if (get_post_type($od['order_id']) != 'shop_order')
						continue;
					$order = new WC_Order($od['order_id']);
					$formatted_order_date = wc_format_datetime($order->get_date_created(), 'Y-m-d');
					echo '<tr>';
					echo '<td>' . $od['order_id'] . '/' . $formatted_order_date . '</td>';
					echo '<td>' . $od['membership'] . '</td>';
					echo '<td>' . $symbol . $od['rebate'] * $vnd_rate . '</td>';
					echo '</tr>';
				endforeach;
				echo '<tfoot>';
				echo '<tr>';
				echo '<td colspan="2">All Totals</td>';
				echo '<td>' . $symbol . $rebate_sum * $vnd_rate . '</td>';
				echo '</tr>';
				echo '</tfoot>';
			else:
				echo '<tr>';
				echo '<td style="text-align:center;" colspan="3">Details Not Found</td>';
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
	// Display header with count of unpaid orders
	$unpaid_count = count($unpaid_backorders);
	echo '<h5>Unpaid Backorders';
	if ($unpaid_count > 0) {
		echo ' <span style="color: #666; font-weight: normal;">(' . $unpaid_count . ' ' . ($unpaid_count == 1 ? 'order' : 'orders') . ')</span>';
	}
	echo '</h5>';
	
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
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span
							class="nobr">Status</span></th>
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

					// Get order status
					$order_status = $order->get_status();
					$status_label = wc_get_order_status_name($order_status);

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
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-total" data-title="Status">
							<span class="woocommerce-Price-amount amount">' . esc_html(ucfirst($status_label)) . '</span>
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
?>
</div>