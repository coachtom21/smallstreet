<?php 

$treasury_details         = get_user_meta(get_current_user_id(),'_treasury_details',true);

$cs                       = get_woocommerce_currency_symbol();
$filter_template_path     = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'filter-top.php';
$pagination_template_path = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'pagination-buttom.php';
extract($args);
?>
<div class="detente-treasury cpm-table-wrap">
    <h3><?php esc_html_e('Treasury', 'cpm-dongtrader'); ?></h3>
    <br class="clear" />
    <div id="member-history-orders" class="widgets-holder-wrap">
    <?php
    if(!empty($treasury_details)) 
        if(file_exists($filter_template_path) && !empty($treasury_details))  load_template($filter_template_path,true,$treasury_details); 
    ?>
        <table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order ID/Date', 'cpm-dongtrader'); ?>
                    <th><?php esc_html_e('Membership', 'cpm-dongtrader'); ?>
                    <th><?php esc_html_e('Receipts', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Detente', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Small Street', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Total 1099-PATR Form', 'cpm-dongtrader'); ?></th>
                </tr>
            </thead>
                <?php 
                       
                echo '<tbody>';
                    if(!empty($treasury_details)):
                        $paginated_treasury = dongtrader_pagination_array($treasury_details,10,true);
                        $seller_reward_sum  = array_sum(array_column($paginated_treasury, 'seller_reward'));
                        $group_reward_sum   = array_sum(array_column($paginated_treasury, 'group_reward'));
                        $site_reward_sum    = array_sum(array_column($paginated_treasury, 'smallstreet_reward'));
                        $total_sum          = array_sum(array_column($paginated_treasury, 'totals'));
                        foreach($paginated_treasury as $od) : 
                            if(get_post_type($od['order_id']) != 'shop_order') continue;
                            $order = new WC_Order($od['order_id']);
                            $formatted_order_date = wc_format_datetime($order->get_date_created(), 'Y-m-d');
                            echo '<tr>';
                            echo '<td>'.$od['order_id'].'/'.$formatted_order_date.'</td>';
                            // echo '<td>'.$od['name'].'</td>';
                            echo '<td>'.$od['membership'].'</td>';
                            echo '<td>'.$cs.$od['seller_reward'].'</td>';
                            echo '<td>'.$cs.$od['group_reward'].'</td>';
                            echo '<td>'.$cs.$od['smallstreet_reward'].'</td>';
                            echo '<td>'.$cs.$od['totals'].'</td>';
                            echo '</tr>';
                        endforeach;
                        echo '<tfoot>';
                            echo '<tr>';
                            echo '<td colspan="2">All Totals</td>';
                            echo '<td>'.$cs.$seller_reward_sum.'</td>';
                            echo '<td>'.$cs.$group_reward_sum.'</td>';
                            echo '<td>'.$cs.$site_reward_sum.'</td>';
                            echo '<td>'.$cs.$total_sum.'</td>';
                            echo '</tr>';
                        echo '</tfoot>';
                    else:
                        echo '<tr>';
                        echo '<td style="text-align:center;"colspan="6" >Details Not Found</td>';
                        echo '</tr>';
                    endif;
                echo '</tbody>';
            
    ?>

            
        </table>
    </div>
    <?php if(file_exists($pagination_template_path) && !empty($treasury_details))  load_template($pagination_template_path,true , $treasury_details); ?>
</div>
