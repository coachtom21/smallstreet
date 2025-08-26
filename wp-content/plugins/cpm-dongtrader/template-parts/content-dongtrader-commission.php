<?php 
$commission_details = get_user_meta(get_current_user_id(),'_commission_details',true);
$cs                 = get_woocommerce_currency_symbol();
?>
<h3><?php esc_html_e('Seller Income ', 'cpm-dongtrader'); ?></h3>
<div id="member-history-orders" class="widgets-holder-wrap">
    <table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0" border="0">
        <thead>
            <tr>
                <th><?php esc_html_e('Order ID/Date', 'cpm-dongtrader'); ?>
                <th><?php esc_html_e('Buyer', 'cpm-dongtrader'); ?>
                <th><?php esc_html_e('Product', 'cpm-dongtrader'); ?></th>
                <th><?php esc_html_e('Individual Commission', 'cpm-dongtrader'); ?></th>
                <th><?php esc_html_e('Group Commission', 'cpm-dongtrader'); ?></th>
                <th><?php esc_html_e('Site Commission', 'cpm-dongtrader'); ?></th>
                <th><?php esc_html_e('Total', 'cpm-dongtrader'); ?></th>
            </tr>
        </thead>
            <?php 
            echo '<tbody>';
                if(!empty($commission_details)):
                    $seller_com_sum   = array_sum(array_column($commission_details, 'seller_com'));
                    $group_com_sum    = array_sum(array_column($commission_details, 'group_com'));
                    $site_com_sum     = array_sum(array_column($commission_details, 'site_com'));
                    $total_sum        = array_sum(array_column($commission_details, 'total'));
                    foreach($commission_details as $od) : 
                        echo '<tr>';
                        echo '<td>'.$od['order_id'].'</td>';
                        echo '<td>'.$od['name'].'</td>';
                        echo '<td>'.$od['product_title'].'</td>';
                        echo '<td>'.$cs.$od['seller_com'].'</td>';
                        echo '<td>'.$cs.$od['group_com'].'</td>';
                        echo '<td>'.$cs.$od['site_com'].'</td>';
                        echo '<td>'.$cs.$od['total'].'</td>';
                        echo '</tr>';
                    endforeach;
                    echo '<tfoot>';
                        echo '<tr>';
                        echo "<td colspan='3'>All Totals</td>";
                        echo "<td>$cs.$seller_com_sum</td>";
                        echo "<td>$cs.$group_com_sum</td>";
                        echo "<td>$cs.$site_com_sum</td>";
                        echo "<td>$cs.$total_sum</td>";
                        echo '</tr>';
                    echo '</tfoot>';
                else:
                    echo '<tr>';
                    echo '<td style="text-align:center;"colspan="7" >Details Not Found</td>';
                    echo '</tr>';
                endif;
            echo '</tbody>';
        
 ?>

        
    </table>
</div>
