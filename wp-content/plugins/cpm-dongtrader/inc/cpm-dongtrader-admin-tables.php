<?php

/**
 * Constructor will create the menu item
 * 
 */
global $wpdb;

function dongtraders_order_listing_menu()
{
    add_submenu_page(
        'woocommerce',
        __('Dongtraders Orders List', 'cpm-dongtrader'),
        __('Dongtraders Orders List', 'cpm-dongtrader'),
        'manage_woocommerce',
        'dongtrader_api_settings',
        'dongtraders_list_order_meta_table'
    );
}
//add_action('admin_menu',  'dongtraders_order_listing_menu');


function dongtraders_list_order_meta_table()
{

    if (isset($_REQUEST['s'])) {
        $s = sanitize_text_field($_REQUEST['s']);
        //echo $s;
    } else {
        $s = false;
    }


    if (isset($_REQUEST['filter'])) {
        $get_filter = sanitize_text_field($_REQUEST['filter']);

        if ($get_filter == "all") {

            /*   $start = '2019-12-20';
            $enddate = date("Y-m-d"); */
            $all_selected = "selected";
            $date_selected = "";

            /*  echo "filter all"; */
        } elseif ($get_filter == "within-a-date-range") {

            $start = sanitize_text_field($_REQUEST['start-month']);
            $enddate = sanitize_text_field($_REQUEST['end-month']);
            $date_selected = "selected";
            $all_selected = "";
            /* echo "filter with date"; */
        }
    } else {
        $start = "";
        $enddate = "";
        $date_selected = "";
        $all_selected = "";
    }

    /* echo $start . '<br>';
    echo $enddate . '<br>'; */

?>
    <form id="posts-filter" method="get" action="">

        <!-- <h1 class="wp-heading-inline">Dongtraders Product Orders Details</h1> -->

        <div class="tablenav top">
            <div class="post_filter">
                Show <select id="filter" name="filter">
                    <option value="all" <?php echo $all_selected; ?>>All</option>
                    <option value="within-a-date-range" <?php echo $date_selected; ?>>Within a Date Range</option>

                </select>


                <span id="from" style="display: none;">From</span>
                <input id="start-month" name="start-month" type="date" size="2" value="<?php echo $start; ?>" style="display: none;">
                <span id="to" style="display: none;">To</span>
                <input id="end-month" name="end-month" type="date" size="2" value="<?php echo $enddate; ?>" style="display: none;">
                <span id="filterby" style="display: none;">filter by </span>
                <input id="submit" class="button" type="submit" value="Filter">
                <br>
                <script>
                    //update month/year when period dropdown is changed
                    jQuery(document).ready(function() {
                        jQuery('#filter').change(function() {
                            pmpro_ShowMonthOrYear();
                        });
                    });

                    function pmpro_ShowMonthOrYear() {
                        var filter = jQuery('#filter').val();
                        if (filter == 'all') {
                            jQuery('#start-month').hide();
                            jQuery('#start-day').hide();
                            jQuery('#start-year').hide();
                            jQuery('#end-month').hide();
                            jQuery('#end-day').hide();
                            jQuery('#end-year').hide();
                            jQuery('#predefined-date').hide();
                            jQuery('#status').hide();
                            jQuery('#l').hide();
                            jQuery('#discount-code').hide();
                            jQuery('#from').hide();
                            jQuery('#to').hide();
                            jQuery('#submit').show();
                            jQuery('#filterby').hide();
                        } else if (filter == 'within-a-date-range') {
                            jQuery('#start-month').show();
                            jQuery('#start-day').show();
                            jQuery('#start-year').show();
                            jQuery('#end-month').show();
                            jQuery('#end-day').show();
                            jQuery('#end-year').show();
                            jQuery('#predefined-date').hide();
                            jQuery('#status').hide();
                            jQuery('#l').hide();
                            jQuery('#discount-code').hide();
                            jQuery('#submit').show();
                            jQuery('#from').show();
                            jQuery('#to').show();
                            jQuery('#filterby').hide();
                        }
                    }

                    pmpro_ShowMonthOrYear();
                </script>
            </div>

            <div class="tablenav-pages one-page dongsearch">

                <div class="search-box">
                    <input id="refresh_order" class="button" type="submit" value="Refresh Orders">
                    <input type="hidden" name="page" value="dongtrader_api_settings">
                    <input id="post-search-input" type="text" value="<?php echo esc_attr(wp_unslash($s)); ?>" name="s">
                    <input class="button" type="submit" value="Search Orders">
                </div>

            </div>
            <br class="clear">
        </div> <!-- end tablenav -->
        <div class="dong-table-wrap">
            <table class="wp-list-table widefat striped dong-order-table">
                <thead>
                    <tr>
                        <th colspan="7"></th>
                        <th colspan="3" class="top-col">Profit</th>
                        <th colspan="3" class="top-col">Commissions</th>
                    </tr>
                    <tr>
                        <td><strong>Order ID</strong></td>
                        <td><strong>Contact Details</strong></td>
                        <td><strong>Cost</strong></td>
                        <td><strong>Rebate</strong></td>
                        <td><strong>Process</strong></td>
                        <td><strong>Profit</strong></td>
                        <td><strong>Reserve</strong></td>
                        <!-- <td><strong>Cost</strong></td> -->
                        <td><strong>Group</strong></td>
                        <td><strong>Individual</strong></td>
                        <td><strong>Commission</strong></td>
                        <td><strong>Group</strong></td>
                        <td><strong>Individual</strong></td>
                        <td><strong>Earning</strong></td>
                    </tr>
                </thead>

                <tbody id="orders" class="list:order orders-list">
                    <?php
                    $post_per_page = 30;
                    $paged = isset($_GET['dongtrader_api_settings']) ? abs((int) $_GET['dongtrader_api_settings']) : 1;
                    if (!empty($start) && !empty($enddate)) {
                        $search_by_date = array(
                            'key' => '_paid_date',
                            'value' => array($start, $enddate),
                            'compare' => 'BETWEEN',
                            'type' => 'DATE'
                        );
                    } else {
                        $search_by_date = array();
                    }

                    $args = array(
                        'post_type'         => 'shop_order',
                        'posts_per_page'    => $post_per_page,
                        'post_status'    => 'wc-completed',
                        'paged' => $paged,


                        'meta_query' => array(
                            $search_by_date,
                            array(
                                // 'key'     => 'city',
                                'value'   => $s,
                                'compare' => 'LIKE',
                            ),

                        )
                    );
                  
                    //$dong_orders = get_posts($args);
                    $dong_orders = new WP_Query($args);

                    /*  var_dump($dong_orders);
                die; */
                    $price_symbol = get_woocommerce_currency_symbol();
                    /* foreach ($dong_orders as $dong_order) { */
                    while ($dong_orders->have_posts()) : $dong_orders->the_post();
                        $order_id = get_the_ID();
                        $order = new WC_Order($order_id);
                        $order_first_name = $order->get_billing_first_name() ?  $order->get_billing_first_name() : null;
                        $order_second_name = $order->get_billing_first_name() ? $order->get_billing_last_name() : null;
                        $order_full_name = $order_first_name . ' ' . $order_second_name;
                        $order_email = $order->get_billing_email() ? $order->get_billing_email() : null;
                        $order_date = $order->get_date_paid();
                        $order_total = $order->get_total();
                        $createDate = new DateTime($order_date);
                        $o_date = $createDate->format('Y-m-d');
                        /* echo $o_date . '<br>'; */

                        // cashback 7%
                        $rebate         = $order->get_meta('mega_cashback_v') ? $order->get_meta('mega_cashback_v') : 0;

                        // cash back to voter 3%
                        $dong_processamt = $order->get_meta('mega_cashback_d') ? $order->get_meta('mega_cashback_d') : 0;

                        $dong_profitamt = $order->get_meta('mega_members_r') ? $order->get_meta('mega_members_r') : 0;
                        $dong_profit_indivudual = $order->get_meta('mega_mr_di') ? $order->get_meta('mega_mr_di') : 0;
                        $profit_amt_group = $order->get_meta('mega_mr_dg') ? $order->get_meta('mega_mr_dg') : 0;
                        //$30 ko 
                        $order_reserve_amt = $order->get_meta('mega_reserve') ? $order->get_meta('mega_reserve') : 0;

                        $order_cost_amt = $order->get_meta('dong_cost') ? $order->get_meta('dong_cost') : 0;

                        $profit_commission_amt = $order->get_meta('mega_mr_dca') ? $order->get_meta('mega_mr_dca') : 0;
                        $commission_amt_to_individual = $order->get_meta('mega_mr_c_di') ? $order->get_meta('mega_mr_c_di') : 0;
                        
                        $commission_amt_to_group = $order->get_meta('mega_mr_c_dg') ? $order->get_meta('mega_mr_c_dg') : 0;
                        
                        $treasury_amount = $order->get_meta('dong_treasury') ? $order->get_meta('dong_treasury') : 0;
                        $dong_earnings = $order->get_meta('mega_comm_c_ds') ? $order->get_meta('mega_comm_c_ds') : 0;
                        $dong_discounts = $order->get_meta('dong_discounts') ? $order->get_meta('dong_discounts') : 0;
                        echo '
                <tr>
                   <td class="column-order-id " data-colname="Order ID">
                            <strong>#' . $order_id . ' </strong>
                           
                             <p>' . $o_date . '</p>
                            
                        </td>
                    <td class="column-contact-details" data-colname="Contact Details">
                            ' . $order_full_name . '
                            <br>
                            ' . $order_email . '
                        </td>
                    <td class="column-cost" data-colname="Cost">
                            <span class="pmpro_order-status pmpro_order-status-success">
                                ' .  $price_symbol . $order_total . ' </span>
                        </td>
                     <td class="column-rebate" data-colname="Rebate">' . $price_symbol . $rebate . '</td>
                    <td class="column-process" data-colname="Process">' . $price_symbol . $dong_processamt . '<br> </td>
                    <td class="column-profit" data-colname="Profit">' . $price_symbol . $dong_profitamt . ' </td>
                    <!--   $30 td -->
                        <td class="column-reserve" data-colname="Reserve">' . $price_symbol . $order_reserve_amt . ' </td>
                        <!--<td class="column-dcost" data-colname="Cost">' . $price_symbol . $order_cost_amt . ' </td> -->
                     <!--  Profit -->
                        <td class="column-dgroup" data-colname="Group">' . $price_symbol . $profit_amt_group . ' </td>
                        <td class="column-dndividual" data-colname="Individual">' . $price_symbol . $dong_profit_indivudual . ' </td>
                        <td class="column-dcommission" data-colname="Commission">' . $price_symbol . $profit_commission_amt . ' </td>
                         <!--   earning -->
                         <td class="column-degroup" data-colname="Group">' . $price_symbol . $commission_amt_to_group . ' </td>
                        <td class="column-dendividual" data-colname="Individual">' . $price_symbol . $commission_amt_to_individual . ' </td>
                        <td class="column-decommission" data-colname="Earning">' . $price_symbol . $dong_earnings . ' </td>
                </tr>
                ';
                    endwhile;
                    $total_order = $dong_orders->found_posts;
                    if (empty($total_order)) {
                        echo '<tr><td colspan="100%">' . esc_html__('No Dongtraders Order found.', 'pmpro-affiliates') . '</td></tr>';
                    }

                    $totalPage         = ceil($total_order / $post_per_page);

                    if ($totalPage > 1) {
                        $dongtraders_order_pagination     =  '<div class="dong-pagination"><span id="table-paging" >Page ' . $paged  . ' of ' . $totalPage . '</span>' . paginate_links(array(
                            'base' => add_query_arg('dongtrader_api_settings', '%#%'),
                            'format' => '',
                            'type' => 'plain',
                            'prev_text' => __('<'),
                            'next_text' => __('>'),
                            'total' => $totalPage,
                            'current' => $paged
                        )) . '</div>';
                    }

                    wp_reset_postdata();

                    ?>

                </tbody>


            </table>
        </div>

    </form>
<?php
    if (!empty($dongtraders_order_pagination)) {
        echo $dongtraders_order_pagination;
    }
}
