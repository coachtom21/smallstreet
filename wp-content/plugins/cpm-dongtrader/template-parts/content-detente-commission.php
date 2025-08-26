<?php 
$commission_details       = get_user_meta(get_current_user_id(),'_income_details',true);
$cs                       = get_woocommerce_currency_symbol();
$filter_template_path     = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'filter-top.php';
$pagination_template_path = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'pagination-buttom.php';
extract($args);
?>
<div class="detente-commission cpm-table-wrap">
    <h3><?php esc_html_e('Seller Income ', 'cpm-dongtrader'); ?></h3>
    <br class="clear" />
    <div id="member-history-orders" class="widgets-holder-wrap">
    <?php 
    if(!empty($commission_details)) 
        if(file_exists($filter_template_path) && !empty($commission_details))  load_template($filter_template_path,true,$commission_details); 

    ?>
        <table class="wp-list-table widefat striped fixed trading-history" id="dong-trading-history-table" width="100%" cellpadding="0" cellspacing="0" border="0">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order ID/Date', 'cpm-dongtrader'); ?>
                    <th><?php esc_html_e('Buyer', 'cpm-dongtrader'); ?>
                    <th><?php esc_html_e('Membership', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Refferals', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Seller P.O.C.', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Treasury', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Total 1099-K Form ', 'cpm-dongtrader'); ?></th>
                </tr>
            </thead>
                <?php 
                echo '<tbody>';
                    if(!empty($commission_details)):
                        $paginated_commission = dongtrader_pagination_array($commission_details,10,true);
                        $affiliate_com_sum   = array_sum(array_column($commission_details, 'seller_reward_c_i_50'));
                        $group_com_sum    = array_sum(array_column($commission_details, 'group_reward_c_g_40'));
                        $site_com_sum     = array_sum(array_column($commission_details, 'smallstreet_reward_c_10'));
                        $total_sum        = array_sum(array_column($commission_details, 'totals'));
                        foreach($paginated_commission as $od) : 

                            if(get_post_type($od['order_id']) != 'shop_order') continue;
                            $order = new WC_Order($od['order_id']);
                            $formatted_order_date = wc_format_datetime($order->get_date_created(), 'Y-m-d');
                            $membership_meta = get_post_meta($od['order_id'],'_membership_type' , true);
                            echo '<tr>';
                            echo '<td><h5>'.$od['order_id'].'</h5>'.$formatted_order_date.'</td>';
                            echo '<td>'.$od['name'].'</td>';
                            echo '<td>'.$od['membership'].'</td>';
                            echo '<td>'.$symbol.$od['seller_reward_c_i_50']*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$od['group_reward_c_g_40']*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$od['smallstreet_reward_c_10']*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$od['totals']*$vnd_rate.'</td>';
                            echo '</tr>';
                        
                        endforeach;
                        echo '<tfoot>';
                            echo '<tr>';
                            echo '<td colspan="3">All Totals</td>';
                            echo '<td>'.$symbol.$affiliate_com_sum*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$group_com_sum*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$site_com_sum*$vnd_rate.'</td>';
                            echo '<td>'.$symbol.$total_sum*$vnd_rate.'</td>';
                            echo '</tr>';
                        echo '</tfoot>';
                    else:
                        echo '<tr>';
                        echo '<td style="text-align:center;"colspan="8" >Details Not Found</td>';
                        echo '</tr>';
                    endif;
                echo '</tbody>';
                ?>
        </table>
    </div>

    <?php if(file_exists($pagination_template_path) && !empty($commission_details))  load_template($pagination_template_path,true , $commission_details); ?>
</div>
