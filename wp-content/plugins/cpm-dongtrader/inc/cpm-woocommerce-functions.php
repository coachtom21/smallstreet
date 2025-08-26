<?php


// Add 7% Cash Back Row to the Checkout Table
add_action('woocommerce_review_order_after_order_total', 'add_cashback_row_to_checkout');

function add_cashback_row_to_checkout()
{
    $dongtraders_setting_data = get_option('dongtraders_api_settings_fields');
    $currency_rate_check = !empty($dongtraders_setting_data['dong_enable_currency']) ? $dongtraders_setting_data['dong_enable_currency'] : false;
    $actual_vnd_rate = $currency_rate_check == 'on' ? $dongtraders_setting_data['vnd_rate'] : 1;
    $currency_symbol = $currency_rate_check == 'on' ? '₫' : get_woocommerce_currency_symbol();

    // Get the total order amount
    $total = WC()->cart->get_cart_contents_total();

    // Calculate 7% cashback
    $cashback = ($total * 0.07) * $actual_vnd_rate;

    ?>
    <tr class="cashback-row">
        <th><?php _e('Cash Back 7% (YAM rewards)', 'your-text-domain'); ?></th>
        <td><?php echo 'M' . number_format($cashback, 2); ?></td>
    </tr>
    <tr class="xp-rewards-row">
        <th><?php _e('Experience Points (XP)', 'your-text-domain'); ?></th>
        <td><?php echo number_format(10000000); ?> XP</td>
    </tr>
    <?php
}

/* add v card option for my-account page */

add_filter('woocommerce_account_menu_items', 'cpm_dong_show_membership_vcard', 40);
function cpm_dong_show_membership_vcard($menu_links)
{

    $menu_links = array_slice($menu_links, 0, 5, true)
        + array('show-membership-v-card' => 'My VCard')
        + array_slice($menu_links, 5, NULL, true);

    return $menu_links;
}
// register permalink endpoint
add_action('init', 'cpm_dong_my_membership_vcard_endpoint');
function cpm_dong_my_membership_vcard_endpoint()
{

    add_rewrite_endpoint('show-membership-v-card', EP_PAGES);
}
// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action('woocommerce_account_show-membership-v-card_endpoint', 'cpm_dong_my_membership_vcard_endpoint_content');
function cpm_dong_my_membership_vcard_endpoint_content()
{
    $user_id = get_current_user_id();

    ?>
    <div class='qr-tiger-vcard-code-generator'>
        <h2><?php _e('My Vcard ', 'cpm-dongtrader') ?></h2>
        <form action="" method="post">
            <label for="vcard-url"><?php _e('Enter VCard', 'cpm-dongtrader') ?></label>
            <div class="input-form-dong">
                <input type="url" class="vcard-url" name="vcard-url"
                    placeholder="<?php _e('Enter Vcard Url', 'cpm-dongtrader') ?>" required><br />
                <input type="submit" value="Update Vcard" name="add_vcard">
            </div>
        </form>
    </div>


    <?php


    if (isset($_POST['add_vcard'])) {
        $get_vcard_url = $_POST['vcard-url'];

        update_user_meta($user_id, '_dongtraders_user_vcard', $get_vcard_url);
    }
    $user_meta_qrs = get_user_meta($user_id, '_dongtraders_user_vcard', true);

    //echo $user_meta_qrs;
    // <img style="height:auto; width:50%;" src="' . $user_meta_qrs . '" alt="Invalid QR Image">copy_url_id https://qrtiger.com/media/qr/CP2X?format=png&resolution=256
    if (!empty($user_meta_qrs)) {

        $url = $user_meta_qrs;
        $path = parse_url($url, PHP_URL_PATH);
        $lastPart = basename($path);


        $image_url = 'https://qrtiger.com/media/qr/' . $lastPart . '?format=png&resolution=256';

        echo '<div class="qr_vcard_section">
        <div class="card card-1">
           <img style="height:auto; width:50%;" src="' . $image_url . '" alt="Invalid QR Image">
        </div>
        <p id="copy_url_id" style="display:none;">' . $image_url . '</p>
        </div>
        <style>
            .qr-tiger-vcard-code-generator{
                display: none;
            }
        </style>';
        ?>
        <div class="vcards_buttons">
            <a href="JavaScript:Void(0);" class="copy_qr_image_url" onclick="dong_traders_url_copy('#copy_url_id')">Copy QR
                URL</a> <a class="update_card" href="JavaScript:Void(0);">Update Vcard</a>
        </div>
        <?php
    } else {
        echo '<style>
	.qr-tiger-vcard-code-generator{
		display: block;
	}
</style>';
    }
}

/* show memebership data on woocommerce tab */

add_filter('woocommerce_account_menu_items', 'cpm_dong_show_membership_data', 40);
function cpm_dong_show_membership_data($menu_links)
{

    $menu_links = array_slice($menu_links, 0, 5, true)
        + array('show-membership-data' => 'My Memberships')
        + array_slice($menu_links, 5, NULL, true);

    return $menu_links;
}
// register permalink endpoint
add_action('init', 'cpm_dong_my_membership_endpoint');
function cpm_dong_my_membership_endpoint()
{

    add_rewrite_endpoint('show-membership-data', EP_PAGES);
}
// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action('woocommerce_account_show-membership-data_endpoint', 'cpm_dong_my_membership_endpoint_content');
function cpm_dong_my_membership_endpoint_content()
{

    // of course you can print dynamic content here, one of the most useful functions here is get_current_user_id()
    ?>
    <div class='qr-tiger-code-generator'>
        <p><?php _e('My Memberships ', 'cpm-dongtrader') ?></p>
        <?php echo do_shortcode('[pmpro_account]');
        ?>
    </div>

    <?php
}
/* show memebership data on woocommerce tab  ends*/
/* show custom order export on woocommerce tab */

add_filter('woocommerce_account_menu_items', 'cpm_dong_show_custom_order_form', 40);
function cpm_dong_show_custom_order_form($menu_links)
{

    $menu_links = array_slice($menu_links, 0, 5, true)
        + array('show-order-form' => 'Add Your Referral Order')
        + array_slice($menu_links, 5, NULL, true);

    return $menu_links;
}
// register permalink endpoint
add_action('init', 'cpm_dong_custom_order_form_endpoint');
function cpm_dong_custom_order_form_endpoint()
{

    add_rewrite_endpoint('show-order-form', EP_PAGES);
}
// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action('woocommerce_account_show-order-form_endpoint', 'cpm_dong_custom_order_form_endpoint_content');
function cpm_dong_custom_order_form_endpoint_content()
{

    // of course you can print dynamic content here, one of the most useful functions here is get_current_user_id()
    ?>
    <div class='dongtraders_order_export'>

        <?php dongtraders_order_export_form_new();
        ?>
    </div>

    <?php
}



/* show custom order exporter on woocommerce tab */

add_filter('woocommerce_account_menu_items', 'cpm_dong_show_custom_order_csv_exporter', 60);
function cpm_dong_show_custom_order_csv_exporter($menu_links)
{

    $menu_links = array_slice($menu_links, 0, 5, true)
        + array('export-affiliate-order' => 'Referral Orders')
        + array_slice($menu_links, 5, NULL, true);

    return $menu_links;
}
// register permalink endpoint
add_action('init', 'dong_exporter_order_csv_endpoint');
function dong_exporter_order_csv_endpoint()
{

    add_rewrite_endpoint('export-affiliate-order', EP_PAGES);
}
// content for the new page in My Account, woocommerce_account_{ENDPOINT NAME}_endpoint
add_action('woocommerce_account_export-affiliate-order_endpoint', 'dong_exporter_order_csv_endpoint_content');
function dong_exporter_order_csv_endpoint_content()
{
    ?>
    <div class='dongtraders_order_export'>

        <?php dongtraders_show_user_affilate_order();
        ?>
    </div>
    <?php
}
function dong_exporter_order_csv_endpoint_content_old()
{

    // of course you can print dynamic content here, one of the most useful functions here is get_current_user_id()
    ?>
    <div class='dongtraders_order_export'>

        <?php dongtraders_show_user_affilate_order();
        ?>
    </div>


    <?php

    $paid_orders = get_user_orders(['completed']);
    $unpaid_backorders = get_user_orders(['on-hold', 'processing', 'pending']);

    $can_user_pay = get_user_meta(get_current_user_id(), 'can_pay', true);
    $can_user_pay = $can_user_pay == '1' ? true : false;
    $is_order_payable = (isLastDayOfMonth() && $can_user_pay) ? true : false;

    $pay_btn = '';

    $three_percent_total_unfunded = 0;
    $three_percent_total_funded = 0;


    ?>
    <div class="detente-orders cpm-table-wrap">

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
                                class="nobr">3%(unfunded)</span></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span
                                class="nobr">Actions</span></th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($unpaid_backorders as $order) {
                        $total_quantity = 0;
                        $three_percent = ($order->get_total() * 0.03);
                        $three_percent_total_unfunded += $three_percent;

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
							<span class="woocommerce-Price-amount amount">' . wc_price($three_percent) . '</span>
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
							' . $pay_btn . '
						</td>
					</tr>
					';
                    } ?>
                </tbody>
            </table>
            <p>Total 3%(unfunded): <?php echo wc_price($three_percent_total_unfunded); ?></p>
            <br class="clear" />
            <?php
        } else {
            echo '<p>No unpaid backorders found.</p>';
        }

        echo '<h5>Paid Orders</h5>';
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
                                class="nobr">3%(funded)</span></th>
                        <th class="woocommerce-orders-table__header woocommerce-orders-table__header-order-actions"><span
                                class="nobr">Actions</span></th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $total_paid_amount = 0;
                    foreach ($paid_orders as $order) {
                        $total_quantity = 0;
                        $total_paid_amount += $order->get_total();
                        $three_percent = ($order->get_total() * 0.03);
                        $three_percent_total_funded += $three_percent;
                        foreach ($order->get_items() as $item_id => $item) {
                            $total_quantity += $item->get_quantity();
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
							<span class="woocommerce-Price-amount amount">' . wc_price($three_percent) . '</span>
						</td>
						<td class="woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions" data-title="Actions">
							<a href="' . esc_url($order->get_view_order_url()) . '" class="woocommerce-button wp-element-button button view">View</a>
						</td>
					</tr>
					';
                    } ?>
                </tbody>
            </table>
            <p>Total 3%(funded): <?php echo wc_price($three_percent_total_funded); ?></p>
            <p>Total Paid Amount: <?php echo wc_price($total_paid_amount); ?></p>
            <br class="clear" />
            <?php
        } else {
            echo '<p>No paid orders found.</p>';
        } ?>
    </div>

    <?php
}


/**
 * Automatically add product to cart on visit
 */

add_action('template_redirect', 'dongtraders_product_link_with_membership_goes_checkoutpage');
function dongtraders_product_link_with_membership_goes_checkoutpage()
{
    if (class_exists('WooCommerce')) {
        if (is_product()) {
            WC()->cart->empty_cart();
            global $product;
            $checkout_url = wc_get_checkout_url();
            $product_id = $product->get_id();
            if (!empty(($_GET['add']))) {
                $check_add_product = $_GET['add'];
                if ($check_add_product == '1') {
                    $has_already_bought_product = dongtraders_has_bought_product_items($product->get_id());

                    if ($has_already_bought_product) {
                        $product_page = get_permalink($product_id);
                        wp_redirect($product_page);
                        exit();
                    } else {
                        WC()->cart->add_to_cart($product_id);
                        wp_redirect($checkout_url);
                        exit();
                    }
                }
            }
            //For variations direct check out

            if (isset($_GET['varid'])) {
                $check_add_varition_product = $_GET['varid'];
                $variation = wc_get_product($check_add_varition_product);

                if ($variation) {
                    // Use proper method to get parent ID
                    $parent_id = $variation->get_parent_id();
                    $product = wc_get_product($parent_id);

                    $get_quantity_yam = dongtraders_set_product_quantity($parent_id);

                    if (!empty($get_quantity_yam)) {
                        WC()->cart->add_to_cart($check_add_varition_product, $get_quantity_yam);
                    } else {
                        WC()->cart->add_to_cart($check_add_varition_product);
                    }


                    wp_redirect($checkout_url);
                    exit();
                } else {

                    exit();
                }
            }
        }
        if (isset($_GET['multiple-items'])) {

            // if(is_user_logged_in()){

            //     $user_id = get_current_user_id();
            //     $membership_level = pmpro_getMembershipLevelForUser($user_id);
            //     $memberhip_name = strtolower($memberships_level->name);

            //     if($memberhip_name == 'patron' || $memberhip_name == 'megavoter'){


            //     }
            // }

            $checkout_url = wc_get_checkout_url();

            // if(isset($_GET['membershipType'])){

            //     $checkout_url =  $checkout_url .'?membership_type='.$_GET['membershipType'];
            // }

            WC()->cart->empty_cart();

            $multiple_products = $_GET['multiple-items'];

            $product_ids = explode(',', sanitize_text_field($_GET['multiple-items']));

            foreach ($product_ids as $product_id) {

                $product_id = absint($product_id);

                $parent_product = wp_get_post_parent_id($product_id);

                $quantity = $parent_product == 1308 ? 10 : 1;

                if ($product_id > 0) {

                    WC()->cart->add_to_cart($product_id, $quantity);
                }
            }

            wp_redirect($checkout_url);
            exit();
        }
    }
}


add_action('woocommerce_before_checkout_form', 'dongtraders_check_user_bought_product_already_bought', 12);
add_action('woocommerce_after_shop_loop_item', 'dongtraders_check_user_bought_product_already_bought', 30);
add_action('woocommerce_before_add_to_cart_quantity', 'dongtraders_check_user_bought_product_already_bought', 30);

function dongtraders_check_user_bought_product_already_bought()
{
    global $product;
    if (!is_user_logged_in())
        return;
    $user_id = get_current_user_id();
    // $membership_level = pmpro_getMembershipLevelForUser($user_id);
    // $memberhip_name = strtolower($membership_level->name);

    if (pmdong_check_level($user_id, 'patron') || pmdong_check_level($user_id, 'pioneer')) {

        $accountpage = get_permalink(get_option('woocommerce_myaccount_page_id'));
        echo '<div>You are already registed as a member. Please Visit <a href="' . $accountpage . '">Account page</a></div>';
        echo '<style>
            .quantity, .cart .single_add_to_cart_button, .ajax_add_to_cart {
                display: none !important;
            }
            table.variations {
            display: none;
            }
        
            </style>';
    }


    // if($memberhip_name == 'patron' || $memberhip_name == 'megavoter'){

    //    $accountpage = get_permalink(wc_get_page_id('my-account'));
    //     echo '<div>Your Account is already register As '.$memberhip_name.' Membership. Please Visit <a href="' . $accountpage . '">Account page</a></div>';
    //     echo '<style>
    //     .quantity, .cart .single_add_to_cart_button, .ajax_add_to_cart {
    //         display: none !important;
    //     }
    //     table.variations {
    //     display: none;
    //     }

    //     </style>';
    // }

}


/* Checking if the user is logged in and has a membership level. */
function dongtraders_check_user_membership_level()
{
    if (is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel()) {
        global $current_user;
        $current_user->membership_level = pmpro_getMembershipLevelForUser($current_user->ID);
        $get_membership_level = $current_user->membership_level;
        return $get_membership_level->name;
    }
}



/* Getting the membership level of the product. */
function dongtraders_get_membership_link_product($product_id)
{
    $get_product_member_level = get_post_meta($product_id, '_membership_product_level', true);
    return $get_product_member_level;
}


function dongtraders_has_bought_product_items($product_id)
{
    global $woocommerce;
    $user_id = get_current_user_id();
    if (0 == $user_id)
        return false;

    // $current_user = wp_get_current_user();
    // $customer_email = $current_user->email;

    $customer_orders = get_posts(array(
        'numberposts' => -1,
        'meta_key' => '_customer_user',
        'meta_value' => $user_id,
        'post_type' => wc_get_order_types(),
        'post_status' => array_keys(wc_get_is_paid_statuses())
    ));

    $get_product_member_level = get_post_meta($product_id, '_membership_product_level', true);

    if (count($customer_orders) > 1 && (bool) $get_product_member_level) {
        return true;
    } else {
        return false;
    }
}



// Dongtrader fields used in both pmpro settings and order meta

add_filter('membership_level_fields', 'dongtrader_membership_level_fields', 10, 2);

function dongtrader_membership_level_fields($f, $type)
{
    //$type must be true or false if pmpro true else if order false
    $display_text = $type ? __('Rate (%):', 'cpm-dongtrader') : __('Total Amount:', 'cpm-dongtrader');
    $fields = array(
        'mega_cashback_v' => sprintf(__('Cashback To Voter  %s', 'cpm-dongtrader'), $display_text), //rebate
        'mega_cashback_d' => sprintf(__('Cashback To Distributor  %s', 'cpm-dongtrader'), $display_text), //process
        // 'mega_profit'      => sprintf(__('Profit %s', 'cpm-dongtrader'), $display_text),//process
        'mega_reserve' => sprintf(__('Reserve Savings %s ', 'cpm-dongtrader'), $display_text),
        'mega_cashout_e' => sprintf(__('Early Cashout %s ', 'cpm-dongtrader'), $display_text),
        'mega_platform_c' => sprintf(__('Platform Costs %s', 'cpm-dongtrader'), $display_text),
        'mega_members_r' => sprintf(__('Members Reward(Profit) %s', 'cpm-dongtrader'), $display_text), //profit
        'mega_mr_di' => sprintf(__('Reward To Individual %s', 'cpm-dongtrader'), $display_text),
        'mega_mr_dg' => sprintf(__('Reward To Group  %s', 'cpm-dongtrader'), $display_text),
        'mega_mr_dca' => sprintf(__('Commision From Reward %s', 'cpm-dongtrader'), $display_text),
        'mega_mr_c_di' => sprintf(__('Commision To Individual %s', 'cpm-dongtrader'), $display_text),
        'mega_mr_c_dg' => sprintf(__('Commision To Group %s', 'cpm-dongtrader'), $display_text),
        'mega_comm_c_ds' => sprintf(__('Commission To Smallstreet  %s', 'cpm-dongtrader'), $display_text),
        'mega_affid' => sprintf(__('Affiliate Id :', 'cpm-dongtrader')),
        'mega_treasury' => sprintf(__('Treasury :', 'cpm-dongtrader'))


    );

    //fields not to display on pmpro backend
    if ($type)
        unset($fields['mega_affid']);

    return $fields;
}


// Adds new field for order metas backend
add_action('woocommerce_admin_order_data_after_order_details', 'dong_editable_order_meta_general');

function dong_editable_order_meta_general($order)
{

    ?>
    <br class="clear" />
    <h3>
        <?php _e('Display Order Trading Details', 'cpm-dongtrader') ?>
        <a href="#" class="edit_address"><?php _e('Edit More Trading Details', 'cpm-dongtrader') ?></a>
    </h3>
    <?php
    $fields = apply_filters('membership_level_fields', array(), false);
    ?>
    <div class="address">
        <!-- Items Here will appear on blur -->
        <?php
        foreach ($fields as $key => $value) {
            $meta_val = !empty($order->get_meta($key)) ? $order->get_meta($key) : 0;
            $printables = $key != 'mega_affid' ? '<p>' . $value . ' $' . $meta_val . '</p>' : '<p>' . $value . ' ' . $meta_val . '</p>';
            echo $printables;
        }
        ?>
    </div>
    <div class="edit_address">
        <?php
        foreach ($fields as $key => $value) {
            $meta_vals = $order->get_meta($key);
            woocommerce_wp_text_input(array(
                'id' => $key,
                'label' => $value,
                'value' => $meta_vals,
                'wrapper_class' => 'form-field-wide'
            ));
        }
        ?>
    </div>
    <?php
}

// Save meta fields values for order
add_action('woocommerce_process_shop_order_meta', 'dong_save_general_details');

function dong_save_general_details($order_id)
{

    $fields = apply_filters('membership_level_fields', array(), false);

    foreach ($fields as $k => $v) {

        update_post_meta($order_id, $k, wc_clean($_POST[$k]));
    }
}

add_action('pmpro_membership_level_before_billing_information', 'dongtrader_pmpro_memberships_custom_fields', 10, 1);

function dongtrader_pmpro_memberships_custom_fields($lv)
{
    $fields = apply_filters('membership_level_fields', array(), true);

    ?>
    <div id="membreship-extra-details" class="pmpro_section" data-visibility="shown" data-activated="true">
        <div class="pmpro_section_toggle">
            <button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
                <?php esc_html_e('Allocation Percentage', 'cpm-dongtrader'); ?>
            </button>
        </div>
        <div class="pmpro_section_inside">
            <table class="form-table">
                <tbody>
                    <?php foreach ($fields as $value => $key) {
                        $current_saved_val = get_pmpro_membership_level_meta($lv->id, $value, true);

                        ?>
                        <tr>
                            <th scope="row" valign="top">
                                <label for="<?php echo $value ?>"><?php echo $key; ?></label>
                            </th>
                            <td>
                                <input name="<?php echo $value ?>" type="number" value="<?php echo $current_saved_val; ?>"
                                    class="regular-text" autocomplete="off" step="0.01" />
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

add_action("pmpro_save_membership_level", 'dongtrader_save_membership_level_custom_fields', 10, 1);

function dongtrader_save_membership_level_custom_fields($id)
{

    $fields = apply_filters('membership_level_fields', array(), true);
    foreach ($fields as $value => $key) {
        $prev_value = get_pmpro_membership_level_meta($id, $value);
        $posted_vals = wc_clean($_POST[$value]);
        update_pmpro_membership_level_meta($id, $value, $posted_vals);
    }
}

//adding fileds for checkbox to membership levels and products for paid membership pro  
add_action('pmpro_membership_level_before_billing_information', function ($lv) {
    $checkbox_value = get_pmpro_membership_level_meta($lv->id, 'gf_val_check', true);
    $products_metas = get_pmpro_membership_level_meta($lv->id, 'membership_level_products', true);

    ?>
    <div id="membreship-extra-details-glassfrog" class="pmpro_section" data-visibility="shown" data-activated="true">
        <div class="pmpro_section_toggle">
            <button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
                <span class="dashicons dashicons-arrow-up-alt2"></span>
                <?php esc_html_e('Membership Products and Glassfrog Settings ', 'cpm-dongtrader'); ?>
            </button>
        </div>
        <div class="pmpro_section_inside">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row" valign="top">
                            <label for="gf_val_check"><?php _e('Send to glassfrog', 'cpm-dongtrader'); ?></label>
                        <td>
                            <input name="gf_val_check" type="checkbox" value="true" class="regular-text" <?php checked($checkbox_value, 'true'); ?> />
                        </td>
                        </th>
                    </tr>
                    <tr>
                        <th scope="row" valign="top">
                            <label
                                for="membership_level_products"><?php _e('Membership Level Products', 'cpm-dongtrader'); ?></label>
                        <td>
                            <select class="cpm-multiselect" name="membership_level_products[]" multiple>

                                <?php
                                $args = array(
                                    'post_type' => 'product',
                                    'posts_per_page' => -1, // Show all products
                                );
                                $products = new WP_Query($args);

                                // Loop through products and populate the dropdown
                                if ($products->have_posts()):
                                    while ($products->have_posts()):
                                        $products->the_post();
                                        $product_id = get_the_ID();
                                        $product_title = get_the_title();
                                        $selected = !empty($products_metas) ? (in_array($product_id, $products_metas) ? 'selected' : '') : '';

                                        ?>
                                        <option <?= $selected; ?> value="<?php echo esc_attr($product_id); ?>">
                                            <?php echo esc_html($product_title . '(' . $product_id . ')'); ?>
                                        </option>
                                        <?php
                                    endwhile;
                                    wp_reset_postdata();
                                endif;
                                ?>
                            </select>
                        </td>
                        </th>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php

}, 9, 1);


//Save values to the paid memberships meta
add_action('pmpro_save_membership_level', function ($id) {
    $check_box_val = isset($_POST['gf_val_check']) ? wc_clean($_POST['gf_val_check']) : 'false';
    $products_arry = isset($_POST['membership_level_products']) ? wc_clean($_POST['membership_level_products']) : [];
    update_pmpro_membership_level_meta($id, 'gf_val_check', $check_box_val);
    update_pmpro_membership_level_meta($id, 'membership_level_products', $products_arry);
});

function get_pmpro_extrafields_meta($memId)
{
    $pm_fields = apply_filters('membership_level_fields', array(), true);
    $new_pm_vals = [];
    foreach (array_keys($pm_fields) as $p) {
        $meta = get_pmpro_membership_level_meta($memId, $p, true);
        $new_pm_vals[$p] = !empty($meta) ? strval($meta) : '0';
    }

    return $new_pm_vals;
}

function round_decimal_ifneeded($number)
{
    $integerPart = floor($number);
    $decimalPart = $number - $integerPart;

    if ($decimalPart > 0.5) {
        $roundedNumber = ceil($number);
    } else {
        $roundedNumber = $integerPart + $decimalPart;
    }

    return $roundedNumber;
}

function get_numeric_price($price)
{
    $formatted_price = wc_price($price); // Format the price

    // Remove any HTML tags from the formatted price
    $plain_text_price = strip_tags($formatted_price);

    $currency_symbol = get_woocommerce_currency_symbol(); // Get the currency symbol

    // Remove the currency symbol from the plain text price
    $numeric_value = str_replace($currency_symbol, '', $plain_text_price);

    return $numeric_value;
}


function vdd($val)
{

    echo '<pre>';
    var_dump($val);
    echo '</pre>';
}

function pdd($val)
{

    echo '<pre>';
    print_r($val);
    echo '</pre>';
}



/* check if provided product id from csv is on product */
if (!function_exists('is_product_check')) {
    function is_product_check($product_id)
    {
        return 'product' === get_post_type($product_id);
    }
}

// Step 1: Import CSV and parse the data
function dongtraders_csv_order_importer()
{
    if (isset($_POST['import_csv'])) {
        $upload_dir = wp_upload_dir();
        $file = $upload_dir['basedir'] . '/' . $_FILES['get_file']['name'];
        $fileurl = $upload_dir['baseurl'] . '/' . $_FILES['get_file']['name'];
        if (!move_uploaded_file($_FILES['get_file']['tmp_name'], $file)) {
            print_r('Failed to move the uploaded file.');
        }

        if (($open = fopen($fileurl, "r")) !== FALSE) {
            $first_row = true;
            $get_orders = array();
            $headers = array();

            while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                if ($first_row) {
                    $headers = $data;
                    $first_row = false;
                } else {
                    $get_orders[] = array_combine($headers, array_values($data));
                }
            }
            fclose($open);

            // Step 2: Process each order from CSV data
            $order_status_msg = '<div class="error-box">Order Data could not be imported! Please try again.</div>';
            foreach ($get_orders as $get_order) {


                $billing_first_name = $get_order['billing_first_name'];
                $billing_last_name = $get_order['billing_last_name'];
                $billing_phone = $get_order['billing_phone'];
                $billing_address_1 = $get_order['billing_address_1'];
                $billing_postcode = $get_order['billing_postcode'];
                $billing_city = $get_order['billing_city'];
                $billing_state = $get_order['billing_state'];
                $billing_country = $get_order['billing_country'];
                $memberships = strtolower($get_order['customer_membership']);
                $variation = dongtrader_convert_sector_to_slug(strtolower($get_order['customer_sector']));
                $customer_email = $get_order['customer_email'];
                $affiliate_user_id = $get_order['affilate_user_id'];

                $settings = get_option('dongtraders_api_settings_fields');

                $cart_items = '';

                if ($memberships == 'megavoter') {

                    $mega_pro_val = $settings['dong_mega_mem'];

                    $cart_items = mega_add_variation_to_cart($mega_pro_val, $variation);
                } elseif ($memberships == 'patron') {

                    $patron_pro_val = $settings['dong_patron_mem'];

                    $cart_items = mega_add_variation_to_cart($patron_pro_val, $variation);
                }

                if (!email_exists($customer_email) && !empty($cart_items)) {
                    $random_password = wp_generate_password();

                    $display_name = $billing_first_name . ' ' . $billing_last_name;

                    $user_id = wc_create_new_customer($customer_email, $display_name, $random_password);


                    // Step 4: Create the order
                    $order = wc_create_order();
                    $order->set_customer_id($user_id);
                    $order->update_meta_data('mega_affid', $affiliate_user_id);

                    foreach ($cart_items as $pid) {

                        $pp = wp_get_post_parent_id($pid);

                        if ($pp == 0) {

                            $id = $pid;
                        } else {

                            $id = $pp;
                        }

                        $get_quantity_yam = get_post_meta($id, '_qty_args', true);

                        if (is_array($get_quantity_yam) && !empty($get_quantity_yam)) {
                            $order->add_product(wc_get_product($id), $get_quantity_yam['qty_min']);
                        } else {
                            $order->add_product(wc_get_product($id), 1);
                        }
                    }

                    // Step 6: Add shipping
                    $shipping = new WC_Order_Item_Shipping();
                    $shipping->set_method_title('Free shipping');
                    $shipping->set_method_id('free_shipping:1'); // set an existing Shipping method ID
                    $shipping->set_total(0); // optional
                    $order->add_item($shipping);

                    // Step 7: Set billing and shipping addresses
                    $address = array(
                        'first_name' => $billing_first_name,
                        'last_name' => $billing_last_name,
                        'company' => '',
                        'email' => $customer_email,
                        'phone' => $billing_phone,
                        'address_1' => $billing_address_1,
                        'address_2' => '',
                        'city' => $billing_city,
                        'state' => $billing_state,
                        'postcode' => $billing_postcode,
                        'country' => $billing_country
                    );
                    $order->set_address($address, 'billing');
                    $order->set_address($address, 'shipping');

                    // Step 8: Add payment method and set order status
                    $order->set_payment_method('preorder');
                    $order->set_status('wc-completed', 'Order is created From Importer');

                    // Step 9: Calculate and save the order
                    $order->calculate_totals();
                    $order->save();

                    // Step 11: Update the order status message
                    $order_id = $order->get_id();
                    if ($order_id) {
                        $order_status_msg = '<div class="success-box">Order Data Imported Successfully. Please Refresh the Order Table.</div>';
                        $order_obj = wc_get_order($order_id);
                        mega_set_membership_level($order_obj); //7
                        mega_custom_ordermeta_update($order_obj); //8
                        mega_update_mlm_database($order_obj); //9


                    }
                }
            }
            echo $order_status_msg;
        }
    }
}






add_action('init', 'change_max_variations_linked');
function change_max_variations_linked()
{

    define('WC_MAX_LINKED_VARIATIONS', 150);
}




add_filter('mega_my_account_details_fields', 'mega_custom_account_sections');
function mega_custom_account_sections($fields)
{
    $fields = [
        'mega-mobile' => ['type' => 'tel', 'label' => __('Mobile', 'cpm-dongtrader')],
        'mega-v-card' => ['type' => 'text', 'label' => __('V Card', 'cpm-dongtrader')],
        'mega-paypal' => ['type' => 'text', 'label' => __('Paypal', 'cpm-dongtrader')],
        'mega_venmo' => ['type' => 'text', 'label' => __('Venmo', 'cpm-dongtrader')],
        'mega-glassfrog' => ['type' => 'text', 'label' => __('Glassfrog', 'cpm-dongtrader')],
        'mega-crowdsignal' => ['type' => 'text', 'label' => __('Crowdsignal', 'cpm-dongtrader')],
        'mega-precoro' => ['type' => 'text', 'label' => __('Precoro', 'cpm-dongtrader')],
        'mega-amazon-business' => ['type' => 'text', 'label' => __('Amazon Business', 'cpm-dongtrader')],
    ];

    return $fields;
}

add_action('woocommerce_checkout_order_created', 'mega_set_membership_level', 7, 1);
function mega_set_membership_level($order)
{

    $customer_id = $order->get_user_id();

    $settings = get_option('dongtraders_api_settings_fields');

    $order_items = $order->get_items();

    $ordered_pid = [];

    foreach ($order_items as $oi) {

        $ordered_pid[] = $oi->get_product_id();
    }

    if (!empty($settings['dong_patron_mem'])) {

        $patron_items_int = array_map('intval', $settings['dong_patron_mem']);

        $diff_patrons_x = array_diff($ordered_pid, $patron_items_int);

        if (empty($diff_patrons_x)) {
            pmpro_changeMembershipLevel(18, $customer_id);
            update_post_meta($order->get_id(), '_membership_type', 18);
            update_post_meta($order->get_id(), '_membership_name', esc_attr('Patron'));
        }
    }

    if (!empty($settings['dong_mega_mem'])) {

        $mega_items = array_map('intval', $settings['dong_mega_mem']);

        $diff_mega_x = array_diff($ordered_pid, $mega_items);

        if (empty($diff_mega_x)) {
            pmpro_changeMembershipLevel(17, $customer_id);
            update_post_meta($order->get_id(), '_membership_type', 17);
            update_post_meta($order->get_id(), '_membership_name', esc_attr('Pioneer'));
        }
    }
}

function get_membership_levels($return = 'id')
{

    if (!is_user_logged_in())
        return [];

    $lvls = pmpro_getMembershipLevelsForUser(get_current_user_id());

    $all_lvls = [];

    $all_name = [];

    foreach ($lvls as $lv) {

        $all_lvls[] = $lv->ID;

        $all_name[] = $lv->name;
    }

    $values = $return == 'id' ? $all_lvls : $all_name;

    return $values;
}

add_action('woocommerce_edit_account_form', 'mega_add_field_edit_account_form');
function mega_add_field_edit_account_form()
{

    global $current_user;

    $ffs = apply_filters('mega_my_account_details_fields', []);

    $lvls = get_membership_levels('name');

    $uu_ii_dd = get_current_user_id();

    if (!in_array("Patron", $lvls)) {

        unset($ffs['mega-precoro']);
        unset($ffs['mega-amazon-business']);
    }

    if (!is_array($ffs))
        return;

    foreach ($ffs as $key => $value) {

        woocommerce_form_field(
            $key,
            array(
                'type' => $value['type'],
                'required' => true,
                'label' => $value['label']
            ),

            get_user_meta($uu_ii_dd, $key, true) // get the data
        );
    }
}
// Save field value
add_action('woocommerce_save_account_details', 'mega_save_account_details');
function mega_save_account_details($user_id)
{

    $ffs = apply_filters('mega_my_account_details_fields', []);

    $keys_ffs = array_keys($ffs);

    foreach ($keys_ffs as $k) {
        update_user_meta($user_id, $k, wc_clean($_POST[$k]));
    }
}

add_action('woocommerce_after_checkout_validation', 'mega_validate_checkout', 0, 2);

function mega_validate_checkout($fields, $errors)
{

    if (is_user_logged_in()) {

        $user_id = get_current_user_id();

        if (pmdong_check_level($user_id, 'patron') || pmdong_check_level($user_id, 'pioneer')) {

            $errors->add('validation', 'You have already preordered these products.');
        }
    }

    $posted_number = $fields['billing_phone'];

    // if (phone_number_exists($posted_number)) {

    //     $errors->add('validation', 'This phone number is already in use.Please use another one.');
    // }
}

function update_username_as_email_address($customer_id, $data)
{
    // Get the user email from the submitted data
    $user_email = isset($data['billing_email']) ? sanitize_email($data['billing_email']) : '';

    // Check if the email is valid
    if (is_email($user_email)) {
        // Update the user's username with the email address
        $user = get_userdata($customer_id);
        if ($user && is_object($user)) {
            wp_update_user(array('ID' => $customer_id, 'user_login' => $user_email));
        }
    }
}

// Hook the custom function to the woocommerce_checkout_update_user_meta action
add_action('woocommerce_checkout_update_user_meta', 'update_username_as_email_address', 10, 2);


// // add_action('')
// do_action( 'woocommerce_checkout_update_user_meta', $customer_id, $data );
// Add this code to your theme's functions.php file or a custom plugin

// add_action('woocommerce_checkout_process', 'custom_phone_number_validation');
// function custom_phone_number_validation($fields , $errors) {
//     // Get the submitted phone number
//     $submitted_phone_number = sanitize_text_field($_POST['billing_phone']); // Adjust the field name as per your WooCommerce setup

//     // Check if the submitted phone number already exists
//     if (phone_number_exists($submitted_phone_number)) {
//         // Phone number already exists, prevent order processing and display an error notice
//         $errors->add( 'validation', 'The provided phone number is already used.' );
//     }
// }

// Function to check if the phone number already exists
function phone_number_exists($phone_number)
{

    $user_query = new WP_User_Query(array(
        'meta_query' => array(
            array(
                'key' => 'billing_phone',
                'value' => $phone_number,
                'compare' => 'LIKE'
            ),
        ),
    ));

    return !empty($user_query->get_results());
}

// Add mobile number field to checkout form
add_action('woocommerce_after_checkout_billing_form', 'add_custom_checkout_fields');
function add_custom_checkout_fields($checkout)
{
    if (!is_user_logged_in()) {

        // Mobile field
        woocommerce_form_field('mega-mobile', array(
            'type' => 'text',
            'class' => array('form-row-wide'),
            'label' => __('Personal Mobile Number'),
            'placeholder' => __(''),
            'required' => true,
        ), $checkout->get_value('mega-mobile'));

        // Social Impact Choice (select dropdown)
        woocommerce_form_field('social_impact_choice', array(
            'type' => 'select',
            'class' => array('form-row-wide'),
            'label' => __('Social Impact Choice ($4 donation)'),
            'required' => true,
            'options' => array(
                '' => __('Select one', 'woocommerce'),
                'peace' => __('Peace ($4)', 'woocommerce'),
                'voting_rights' => __('Voting Rights ($4)', 'woocommerce'),
                'disaster_relief' => __('Disaster Relief ($4)', 'woocommerce'),
            ),
        ), $checkout->get_value('social_impact_choice'));
    }
}


//validate 'mega-mobile' field
add_action('woocommerce_checkout_process', 'check_duplicate_phone_number');
function check_duplicate_phone_number($posted)
{
    if (!is_user_logged_in()) {
        // Validate mobile
        if (empty($_POST['mega-mobile'])) {
            wc_add_notice(__('Please enter a valid personal mobile number.'), 'error');
        } else {
            $phone_number = sanitize_text_field($_POST['mega-mobile']);
            global $wpdb;
            $query = $wpdb->prepare("
                SELECT user_id 
                FROM {$wpdb->usermeta} 
                WHERE meta_key = 'mega-mobile' 
                AND meta_value LIKE '%s'
            ", $phone_number);
            $user_id = $wpdb->get_var($query);
            if ($user_id) {
                wc_add_notice(__('An account with this personal phone number already exists. Please try a different number.'), 'error');
            }
        }

        // Validate social impact choice
        if (empty($_POST['social_impact_choice'])) {
            wc_add_notice(__('Please select a social impact cause.'), 'error');
        }
    }
}

// Save both fields to order meta
add_action('woocommerce_created_customer', 'save_custom_fields_to_user_meta');
function save_custom_fields_to_user_meta($user_id)
{
    if (!empty($_POST['mega-mobile'])) {
        update_user_meta($user_id, 'mega-mobile', sanitize_text_field($_POST['mega-mobile']));
    }

    if (!empty($_POST['social_impact_choice'])) {
        update_user_meta($user_id, 'social_impact_choice', sanitize_text_field($_POST['social_impact_choice']));
    }
}




// For removing paid membership pro user membership level cache in checkout page
add_action('init', function () {

    if (is_user_logged_in() && is_checkout()) {

        $uid = get_current_user_id();
        wp_cache_delete('user_' . $uid . '_levels_all');
    }
});



function pmdong_check_level($user_id, $name)
{

    $membership_level = pmpro_getMembershipLevelsForUser($user_id, true);

    $array_check = false;

    $convertedArray = [];

    array_map(function ($object) use (&$convertedArray) {
        $arrayObject = strtolower($object->name);

        if (!in_array($arrayObject, $convertedArray, true)) {
            $convertedArray[] = $arrayObject;
        }
    }, $membership_level);


    if (in_array($name, $convertedArray))
        $array_check = true;

    return $array_check;
}



function mega_custom_ordermeta_update($orderobj)
{

    $customer_id = $orderobj->get_customer_id();

    //all items of an order
    $items = $orderobj->get_items();

    $membership_lvl = get_post_meta($orderobj->get_id(), '_membership_type', true);

    $tdd = [];

    foreach ($items as $item) {

        $total = $item->get_total();

        $pm_meta_vals = get_pmpro_extrafields_meta($membership_lvl);

        $item_id = $item->get_id();

        // Rebate 
        $tdd[$item_id]['mega_cashback_v'] = $pm_meta_vals['mega_cashback_v'] / 100 * $total;

        // cashback to seller or distributor
        $tdd[$item_id]['mega_cashback_d'] = $pm_meta_vals['mega_cashback_d'] / 100 * $total;

        $tdd[$item_id]['mega_reserve'] = $pm_meta_vals['mega_reserve'] / 100 * $total;

        $tdd[$item_id]['mega_cashout_e'] = $pm_meta_vals['mega_cashout_e'] / 100 * $total;

        $tdd[$item_id]['mega_platform_c'] = $pm_meta_vals['mega_platform_c'] / 100 * $total;

        // Actual profit (Actual Profit column table)
        $tdd[$item_id]['mega_members_r'] = $pm_meta_vals['mega_members_r'] / 100 * $total;

        // Profit distributed to individual (50% seller)
        $tdd[$item_id]['mega_mr_di'] = $pm_meta_vals['mega_mr_di'] / 100 * $tdd[$item_id]['mega_members_r'];

        // Profit distributed to the group (40% group)
        $tdd[$item_id]['mega_mr_dg'] = $pm_meta_vals['mega_mr_dg'] / 100 * $tdd[$item_id]['mega_members_r'];

        // Commission amount from profit (10% comm)
        $tdd[$item_id]['mega_mr_dca'] = $pm_meta_vals['mega_mr_dca'] / 100 * $tdd[$item_id]['mega_members_r'];

        // Commission distributed to individual (50% seller 2)
        $tdd[$item_id]['mega_mr_c_di'] = $pm_meta_vals['mega_mr_c_di'] / 100 * $tdd[$item_id]['mega_mr_dca'];

        // Commission distributed to group (40% group)
        $tdd[$item_id]['mega_mr_c_dg'] = $pm_meta_vals['mega_mr_c_dg'] / 100 * $tdd[$item_id]['mega_mr_dca'];

        // Commission distributed to smallstreet( 10% )
        $tdd[$item_id]['mega_comm_c_ds'] = $pm_meta_vals['mega_comm_c_ds'] / 100 * $tdd[$item_id]['mega_mr_dca'];

        $distributed_amount = $tdd[$item_id]['mega_cashback_v'] + $tdd[$item_id]['mega_cashback_d'];

        // Remaining amount after casback and process charge
        $tdd[$item_id]['mega_treasury'] = $total - $distributed_amount;
    }


    sumValues_update_ordermeta($tdd, $orderobj->get_id());
}

add_action('woocommerce_checkout_order_created', 'mega_custom_ordermeta_update', 8, 1);
function sumValues_update_ordermeta($arrays, $oid)
{
    $sumArray = [];

    foreach ($arrays as $array) {
        foreach ($array as $key => $value) {
            if (!isset($sumArray[$key])) {
                $sumArray[$key] = $value;
            } else {
                $sumArray[$key] += $value;
            }
        }
    }

    if (!empty($sumArray)):
        foreach ($sumArray as $k => $v) {
            $p = wc_format_decimal($v, 2);
            update_post_meta($oid, $k, $p);
        }
    endif;
}

add_action('woocommerce_checkout_order_created', 'mega_update_mlm_database', 9, 1);

function mega_update_mlm_database($orderObj)
{


    //global database variable
    global $wpdb;

    // $orderObj =  wc_get_order($order_id);

    //mlm customer user table from our database
    $mlm_users_table = $wpdb->prefix . 'mega_mlm_customers';

    //mlm customer sales custom table
    $mlm_sales_table = $wpdb->prefix . 'mega_mlm_purchases';

    // mlm downline table
    // $mlm_downline_table = $wpdb->prefix . 'mega_mlm_downline';

    // mlm groups table
    $mlm_group_table = $wpdb->prefix . 'mega_mlm_groups';

    // Customer id
    $customer_id = $orderObj->get_customer_id();

    // Get User Object
    $user_data = get_userdata($customer_id);

    // get sponsor id 
    $sponsor_id = $orderObj->get_meta('mega_affid');

    //set booleans to check
    $refferal = !empty($sponsor_id) ? (int) $sponsor_id : null;

    $check_customer = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT user_id FROM $mlm_users_table WHERE user_id = %d",
            $customer_id
        )
    );

    //if $check_customer == null customer doesnot exists and vice versa 
    $customer_exists = is_null($check_customer) ? false : true;

    //insertable data on mlm_sales_table
    $mlm_purchase_data = array(
        'customer_id' => (int) $check_customer,
        'order_id' => (int) $orderObj->get_id(),
        'allocation_status' => 0
    );

    $membership_metas = get_post_meta($orderObj->get_id(), '_membership_name', true);

    if ($membership_metas == 'Patron') {

        mega_save_order_details([$customer_id => [$orderObj->get_id()]]);
        mega_save_my_treasury([$orderObj->get_id() => $customer_id]);
        // mega_save_treasury_details();

        // mega_save_commission_details([$orderObj->get_id() => $customer_id] , false);
    }

    if (!is_null($refferal))
        $mlm_purchase_data['sponsor_id'] = $refferal;

    //if customer already in mlm database
    if ($customer_exists) {

        // Get customer group_id from 'mega_mlm_customers' table
        $group_id = $wpdb->get_var($wpdb->prepare("SELECT customer_group_id FROM $mlm_users_table WHERE user_id=%d", (int) $check_customer));

        // Add data to mega_mlm_purchases table
        $wpdb->insert($mlm_sales_table, $mlm_purchase_data);

        // check if group id is set
        if (isset($group_id)) {

            // prepare for update to group table distribution status is 2 because we need to reupdate allocation because he brought new item
            $group_update = $wpdb->prepare("UPDATE $mlm_group_table SET distribution_status = 2 WHERE group_id = %d", (int) $group_id);

            $wpdb->query($group_update);
        }
    }

    if (!$customer_exists) {

        $lvl_id = get_post_meta($orderObj->get_id(), "_membership_type", true);

        $gf_meta = get_pmpro_membership_level_meta($lvl_id, 'gf_val_check', true);

        $check_gf_status = $gf_meta == 'true' ? true : false;

        if ($check_gf_status) {

            //get user object
            $user_info = get_userdata($customer_id);

            //get user email
            $email = $user_info->user_email;

            //api request string
            $str = '{"people": [{
                "name": "' . $user_info->display_name . '",
                "email": "' . $email . '",
                "external_id": "' . $customer_id . '",
                "tag_names": ["tag 1", "tag 2"]
                }]
                }';

            //api call
            $samp = glassfrog_api_request('people', $str, "POST");

            if ($samp && isset($samp)):

                // glassfrog id from the api
                $gf_id = $samp->people[0]->id;

                // glassfrog persons  name
                $gf_name = $samp->people[0]->name;

                // inserting data to the customers
                $customer_data = array(
                    'user_id' => (int) $customer_id,
                    'upline_id' => $refferal,
                    'glassfrog_person_id' => (int) $gf_id,
                    'person_name' => $gf_name,
                    'user_status' => 0,
                );

                if (is_null($refferal))
                    unset($customer_data['upline_id']);


                // insert data to mega_mlm_customers tables
                $wpdb->insert($mlm_users_table, $customer_data);

                $downline_data = array(
                    'user_id' => $refferal,
                    'downline_user_id' => $customer_id,
                );

                // $wpdb->insert($mlm_downline_table, $downline_data);

                $mlm_purchase_data['customer_id'] = (int) $customer_id;

                $mlm_purchase_data['allocation_status'] = 0;

                // insert data mega_mlm_purchases
                $wpdb->insert($mlm_sales_table, $mlm_purchase_data);

            endif;
        } else {

            // might need to do something later
            $customer_data = array(
                'user_id' => (int) $customer_id,
                'upline_id' => $refferal,
                'person_name' => $user_data->display_name,
                'user_status' => 2,
            );

            if (is_null($refferal))
                unset($customer_data['upline_id']);

            // insert data to mega_mlm_customers tables
            $wpdb->insert($mlm_users_table, $customer_data);

            // $downline_data = array(
            //     'user_id' => $refferal,
            //     'downline_user_id' => $customer_id,
            // );

            // $wpdb->insert($mlm_downline_table, $downline_data);

            $mlm_purchase_data['customer_id'] = (int) $customer_id;

            $mlm_purchase_data['allocation_status'] = 2;

            // insert data mega_mlm_purchases
            $wpdb->insert($mlm_sales_table, $mlm_purchase_data);

            mega_save_details_for_non_gf_members($orderObj);
        }
    }
}
function mega_save_details_for_non_gf_members($oobj)
{

    // $oobj = wc_get_order($oid);

    $customer_id = $oobj->get_customer_id();

    //update $users_order array format 
    $buyer_meta = get_user_meta($customer_id, '_buyer_details', true);

    //assign empty array if $buyer_meta is empty 
    $buyer_metas = !empty($buyer_meta) ? $buyer_meta : [];

    $buyer_name = dongtrader_check_user($customer_id, false);

    //cashback amount from order meta distributeable to voter
    $rebate = get_post_meta($oobj->get_id(), 'mega_cashback_v', true);

    $rebate = !empty($rebate) ? $rebate : 0;

    //get actual process amount for distributor
    $process_amt = get_post_meta($oobj->get_id(), 'mega_cashback_d', true);

    $process_amt = !empty($process_amt) ? $process_amt : 0;

    // Get membership 
    $membership_name = get_post_meta($oobj->get_id(), '_membership_name', true);

    $order_total = $oobj->get_total();

    // members reward
    $distributed_total_amt = $rebate + $process_amt;

    //  Amount remaining after distribution 
    $remaining_total_amt = $order_total - $distributed_total_amt;

    //new array for new order which we will append to 
    $buyer_metas[] = [
        'order_id' => $oobj->get_id(),
        'name' => $buyer_name,
        'membership' => $membership_name,
        'rebate' => $rebate,
        'process' => $process_amt,
        'total' => $distributed_total_amt,
        'xp_awarded'    => 10000000, // XP awarded for this order
    ];



    if (!empty($buyer_metas)) {
        update_user_meta($customer_id, '_buyer_details', $buyer_metas);
    }
    //get previous seller trading details saved in user meta
    $treasury_meta = get_user_meta($customer_id, '_treasury_details', true);

    //assign empty array if $treasury_meta is empty 
    $treasury_metas = !empty($treasury_meta) ? $treasury_meta : [];

    $treasury_metas[] = [
        'order_id' => $oobj->get_id(),
        'name' => $buyer_name,
        'product_title' => $membership_name,
        'total_amt' => $order_total,
        'distrb_amt' => $distributed_total_amt,
        'rem_amt' => $remaining_total_amt,
        'xp_awarded'    => 10000000, // XP awarded for this order
    ];

    if (!empty($treasury_metas)) {
        update_user_meta($customer_id, '_treasury_details', $treasury_metas);
    }

    // For sposnor 
    $sponsor_id = get_post_meta($oobj->get_id(), 'mega_affid', true);

    $sponsor_check = dongtrader_check_user($sponsor_id);

    if (!empty($seller_id) && $sponsor_check) {

        $sponsor_meta = get_user_meta($sponsor_id, '_buyer_details', true);

        //assign empty array if $buyer_meta is empty 
        $sponsor_metas = !empty($sponsor_meta) ? $sponsor_meta : [];

        $sponsor_metas[] = [
            'order_id' => $oobj->get_id(),
            'name' => $buyer_name,
            'membership' => $membership_name,
            'rebate' => 0,
            'process' => $process_amt,
            'total' => $process_amt,
            'xp_awarded'    => 10000000, // XP awarded for this order
        ];

        if (!empty($sponsor_metas)) {
            update_user_meta($sponsor_id, '_buyer_details', $sponsor_metas);
        }
    }
}

/**
 * Use email address as the username during checkout.
 */
function use_email_as_username($order_id)
{
    $order = wc_get_order($order_id);
    $user_id = $order->get_user_id();

    global $wpdb;

    if ($user_id) {
        $user_data = get_userdata($user_id);
        $email = $user_data->user_email;

        $tablename = $wpdb->prefix . "users";

        $wpdb->update(
            $tablename,
            array(
                'user_login' => $email,
                'display_name' => $email,
            ),
            array(
                'ID' => $user_id
            )
        );

        // Output popup directly on thank you page
        ?>
        <style>
            .popup {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background-color: #fff;
                padding: 20px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                text-align: center;
                max-width: 600px;
            }

            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, .8);
                z-index: 999;
            }

            .popup .popup-button {
                background-color: #6F42C1;
                color: white;
                font-size: 18px;
                padding: 15px 30px;
                border: none;
                cursor: pointer;
                margin: 10px;
                text-decoration: none;
                display: inline-block;
            }

            .popup .popup-button:hover {
                background-color: transparent;
                color: #6F42C1;
                border: 1px solid #6F42C1;
            }
        </style>

        <div class="overlay" id="popupOverlay"></div>
        <div class="popup" id="discordPopup">
            <h3>Join Our Discord Server!<br>Gracebook</h3>
            <p>Official Discord for the Legacy To Live By community, the Cookie Jar economy, and the YAM movement.</p>
            <a href="https://discord.gg/tY6mxRft" target="_blank" class="popup-button">Join Now</a>
        </div>
        <script>
            jQuery(document).ready(function ($) {
                // Show popup and overlay as soon as page loads
                $('#discordPopup').fadeIn();
                $('#popupOverlay').fadeIn();

                // Hide popup when overlay is clicked
                $('#popupOverlay').click(function () {
                    $('#discordPopup').fadeOut();
                    $('#popupOverlay').fadeOut();
                });
            });
        </script>
        <?php
    }
}
add_action('woocommerce_thankyou', 'use_email_as_username');

function mega_check_email($email)
{
    global $wpdb;

    // Query to check if the email exists
    $email_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(ID) FROM {$wpdb->users} WHERE user_email = %s",
            $email
        )
    );

    return $email_exists > 0 ? true : false;
}
