<?php 

global $wpdb;

$user_table  = $wpdb->prefix . 'mega_mlm_customers';

$group_profit_release = $wpdb->prefix . 'release_groups_profit';

$group_id = $wpdb->get_var($wpdb->prepare("SELECT customer_group_id FROM $user_table WHERE user_id=%d" , get_current_user_id()));
var_dump($group_id);

$profit_releases = $wpdb->get_results($wpdb->prepare("SELECT * FROM $group_profit_release WHERE group_id=%d ORDER BY release_date DESC" , (int) $group_id), ARRAY_A);

$group_details            = get_user_meta(get_current_user_id(),'_group_details',true);

var_dump($group_details);


$cs                       = get_woocommerce_currency_symbol();

$filter_template_path     = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'filter-top.php';

$pagination_template_path = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'partials'. DIRECTORY_SEPARATOR.'pagination-buttom.php';

extract($args);


?>

<div class="detente-groups cpm-table-wrap">
    <h3><?php esc_html_e('Group ', 'cpm-dongtrader'); ?></h3>
    <br class="clear" />
    <div id="member-history-orders" class="widgets-holder-wrap">
    <?php 
    if(!empty($group_details)) 
        if(file_exists($filter_template_path) && !empty($group_details))  load_template($filter_template_path,true,$group_details); 

    ?>
        <table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0" border="0">
            <thead>
                <tr>
                    <th><?php esc_html_e('Order Id', 'cpm-dongtrader'); ?>
                    <th><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Group Name', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('For Amount', 'cpm-dongtrader'); ?></th>
                    <th><?php esc_html_e('Amount', 'cpm-dongtrader'); ?></th>
                </tr>
            </thead>
            <?php 
                echo '<tbody>';
                    if(!empty($group_details)):
                        $paginated_gd = dongtrader_pagination_array($group_details,10,true);
                        $i =1;
                        foreach($paginated_gd as $gd) : 

                           
                            echo '<tr>';
                            echo '<td>'.$gd['order_id'].'</td>' ;
                            echo '<td>'.$gd['order_date'].'</td>';
                            echo '<td>'.$gd['gf_name'].'</td>';
                            echo '<td> Group profit for order number:- <b>'.$gd['order_code'].'</b></td>';
                            echo '<td>'.$symbol.$gd['profit_amount']*$vnd_rate.'</td>';

                            echo '</tr>';

                        $i++;
                        endforeach;
                    else:
                        echo '<tr>';
                        echo '<td style="text-align:center;"colspan="6" >Details Not Found</td>';
                        echo '</tr>';
                    endif;
                echo '</tbody>';
            
    ?>
        </table>
    </div>
    <?php if(file_exists($pagination_template_path) && !empty($group_details))  load_template($pagination_template_path,true , $group_details ); ?>
</div>
<div class="group_profit_releases">

<h3><?php esc_html_e('Released funds for disaster relief', 'cpm-dongtrader'); ?></h3>

    <table class="wp-list-table widefat striped fixed trading-history" width="100%" cellpadding="0" cellspacing="0" border="0">
        <thead>
            <tr>
                <th>S.N.</th>
                <th>Released Date</th>
                <th>Released Amount</th>
                <th>Disaster Released Cause</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                if(!empty($profit_releases)){
                    $items_per_page = 5; // Number of items to display per page
                    $paged = isset($_GET['rfpaged']) ? (int) $_GET['rfpaged'] : 1;

                    $paginated_profit_releases = array_chunk($profit_releases, $items_per_page); 
                    $current_items = $paginated_profit_releases[$paged - 1]; 
                    $k=1;
                    foreach($current_items as $pf){
                        echo '<tr>';
                        echo '<td>'.$k.'</td>' ;
                        echo '<td>'.$pf['release_date'].'</td>' ;
                        echo '<td>'.$symbol.$pf['release_amount'] * $vnd_rate.'</td>';
                        echo '<td>'.$pf['release_note'].'</td>';
                        echo '</tr>';
                    $k++;
                    }
                }else{
                    echo '<tr>';
                    echo '<td style="text-align:center;"colspan="4" >Details Not Found</td>';
                    echo '</tr>';
                }
            ?>
        </tbody>
    </table>
    <div style="float:right">
    <?php
    // Pagination links
    if (!empty($profit_releases)) {
        echo '<div class="pagination">';
        echo paginate_links(array(
            'base' => add_query_arg('rfpaged', '%#%'), // Base URL with page number placeholder
            'format' => 'list', // Remove format parameter
            'prev_text' => __('&laquo; Previous', 'cpm-dongtrader'),
            'next_text' => __('Next &raquo;', 'cpm-dongtrader'),
            'total' => count($paginated_profit_releases), // Total number of pages
            'current' => $paged, // Current page number
        ));
        echo '</div>';
    }
    ?>
    </div>
</div>
