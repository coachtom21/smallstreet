<?php

defined('ABSPATH') || exit;
$order_details              = get_user_meta(get_current_user_id(), '_buyer_details', true);
$filter_template_path       = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'filter-top.php';
$pagination_template_path   = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'pagination-buttom.php';
extract($args);
?>
<div class="detente-orders cpm-table-wrap">
    <h3><?php esc_html_e('My orders', 'cpm-dongtrader'); ?></h3>
    <br class="clear" />
    <div id="member-history-orders" class="widgets-holder-wrap">
        <?php if (file_exists($filter_template_path) && !empty($order_details))  load_template($filter_template_path, true, $order_details); ?>
        <table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0" border="0">
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
            if (!empty($order_details)) :
                $paginated_orders = dongtrader_pagination_array($order_details, 10, true);
                $rebate_sum  = array_sum(array_column($order_details, 'rebate'));
                $rebate_d_sum = array_sum(array_column($order_details, 'rebate_d'));
                $annual_refferal_sum = 0;
                $total_sum   = array_sum(array_column($order_details, 'total'));

                foreach ($paginated_orders as $od) :
                    if (get_post_type($od['order_id']) != 'shop_order') continue;
                    $order                = new WC_Order($od['order_id']);
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
            else :
                echo '<tr>';
                echo '<td style="text-align:center;"colspan="6" >Details Not Found</td>';
                echo '</tr>';
            endif;
            echo '</tbody>'; ?>
        </table>
    </div>
    <?php if (file_exists($pagination_template_path) && !empty($order_details))  load_template($pagination_template_path, true, $order_details); ?>





    <!-- ============================== -->
    <!-- ============================== -->
    <!-- ============================== -->

    <?php
    function get_user_orders($status)
    {
        $user_id = get_current_user_id();
    
        if (!$user_id) {
            return [];
        }
    
        $args = [
            'customer_id' => $user_id,
            'status'      => $status,
            'limit'       => -1,
        ];
    
        return wc_get_orders($args);
    }

    $paid_orders = get_user_orders(['completed']);
    $unpaid_backorders = get_user_orders(['on-hold','processing', 'pending']);

    echo '<h5>Unpaid Backorders</h5>';
	if (!empty($unpaid_backorders)) { ?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">Order</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">Date</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">Total</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr">Actions</span></th>
				</tr>
			</thead>

			<tbody>

				<?php foreach ($unpaid_backorders as $order) {
					$total_quantity = 0;
					foreach ($order->get_items() as $item_id => $item) {
						$total_quantity += $item->get_quantity();

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
							<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
								<a href="' . esc_url($order->get_checkout_payment_url()) . '" class="woocommerce-button wp-element-button button view">Pay Now</a>
							</td>
						</tr>
						';
					}
				} ?>
			</tbody>
		</table>
	<?php
	} else {
		echo '<p>No unpaid backorders found.</p>';
	}
    
    echo '<h5>Paid Orders</h5>';
	if (!empty($paid_orders)) { ?>
		<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
			<thead>
				<tr>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-number"><span class="nobr">Order</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-date"><span class="nobr">Date</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-total"><span class="nobr">Total</span></th>
					<th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span class="nobr">Actions</span></th>
				</tr>
			</thead>

			<tbody>
				<?php 
                $total_paid_amount = 0;
                foreach ($paid_orders as $order) {
					$total_quantity = 0;
					foreach ($order->get_items() as $item_id => $item) {
						$total_quantity += $item->get_quantity();
						$total_paid_amount += $order->get_total();
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
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
							<a href="' . esc_url($order->get_view_order_url()) . '" class="woocommerce-button wp-element-button button view">View</a>
						</td>
					</tr>
					';
				} ?>
			</tbody>
		</table>
		<?php echo '<h5>Total Paid Amount: ' . wc_price($total_paid_amount) . '</h5>';
	} else {
		echo '<p>No paid orders found.</p>';
	} ?>
</div>