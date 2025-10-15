<?php
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

add_action('wp_enqueue_scripts', 'enqueue_parent_styles');

function enqueue_parent_styles()
{

    wp_enqueue_style('parent-style', get_stylesheet_directory_uri() . '/style.css');
    wp_enqueue_style('dashicons');

    // Ensure jQuery is loaded
    wp_enqueue_script('jquery');

    if (wp_is_mobile()) {
        wp_enqueue_style('dong_mobile_styles', get_stylesheet_directory_uri() . '/mobile-style.css', array(), '1.0', 'all');
        wp_enqueue_script('cpm-dong-public-js', get_stylesheet_directory_uri() . '/cpm-dongtraders-public.js', array('jquery'), '1.0', true);
    }
}


if (wp_is_mobile()) {
    /* hide admin bar for mobile devices */
    // function dongtraders_nonloggedin_user_redirect()
    // {
    //     $my_account_page_url = get_permalink(wc_get_page_id('myaccount'));
    //     if (is_user_logged_in() && is_front_page()) {

    //         wp_redirect($my_account_page_url);
    //         exit;
    //     }
    // }
    // add_action('template_redirect',  'dongtraders_nonloggedin_user_redirect');
    add_filter('show_admin_bar', '__return_false');
    add_action('woocommerce_before_account_navigation', 'dongtraders_myaccount_back_section');
    function dongtraders_myaccount_back_section()
    {
        $my_account_page_url = get_permalink(wc_get_page_id('myaccount'));
        echo '<div class="dong-my-account-back"><a href="' . $my_account_page_url . '">Back</a></div>';
    }
    add_action('wp_head', 'dongtraders_hide_navigation');
    function dongtraders_hide_navigation()
    {
        $actual_link = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $my_account_page_url = wc_get_account_endpoint_url('dashboard');
        if ($actual_link == $my_account_page_url) {
            echo '<style>
            .woocommerce-MyAccount-navigation {
                display: block;
            }
             .dong-my-account-back{
                display: none;
            }
            .dong-dash-QR{
                 display: block !important;
            }
        </style>';
        } else {
            echo '<style>
            .woocommerce-MyAccount-navigation {
                display: none;
            }
            .dong-my-account-back{
                display: block;
            }
            .dong-dash-QR, .v-card{
                 display: none;
            }

        </style>';
        }


    }
}

add_action('wp_head', function () {

    if (is_account_page() && !wp_is_mobile()) {

        echo '<style>
        .elementor-widget-container>p>img{
            display:none;
        }
        </style>';
    }

});

// PEACE UNIFORM SHORTCODE

add_shortcode('dt_dong_peace_uniform', 'dt_dong_peace_uniform_shortcode');
function dt_dong_peace_uniform_shortcode($atts)
{

    $value = shortcode_atts(array(
        'product_id' => 2204,
    ), $atts);
    ob_start();

    $product_id = $value['product_id'];
    $att_colors = (wc_get_product_terms($product_id, 'pa_color'));
    $att_sizes = (wc_get_product_terms($product_id, 'pa_size'));
    $att_genders = (wc_get_product_terms($product_id, 'pa_gender'));

    if (isset($_POST["submit"])) {
        $gender = $_POST['gender'];
        $size = $_POST['size'];
        $council = $_POST['seat'];
        $dong_qty = $_POST['dong-qty'];


        $match_attributes = array(
            "attribute_pa_color" => $council,
            "attribute_pa_size" => $size,
            "attribute_pa_gender" => $gender
        );

        $data_store = WC_Data_Store::load('product');
        $variation_id = $data_store->find_matching_product_variation(
            new \WC_Product($product_id),
            $match_attributes
        );

        // echo $variation_id . '-varition cpm ';

        WC()->cart->add_to_cart($product_id, $dong_qty, $variation_id);
    }

    ?>
    <script src="https://kit.fontawesome.com/9aa1ea67c4.js" crossorigin="anonymous"></script>
    <script>
        jQuery(document).ready(function () {
            var buttonPlus = jQuery(".qtyplus");
            var buttonMinus = jQuery(".qtyminus");

            var incrementPlus = buttonPlus.click(function (e) {
                e.preventDefault();
                var $n = jQuery(this)
                    .parent(".dt-dong-quantity")
                    .find(".input-qty");
                $n.val(Number($n.val()) + 1);
            });

            var incrementMinus = buttonMinus.click(function (e) {
                e.preventDefault();
                var $n = jQuery(this)
                    .parent(".dt-dong-quantity")
                    .find(".input-qty");
                var amount = Number($n.val());
                if (amount > 0) {
                    $n.val(amount - 1);
                }
            });

            // checkbox for men and women

            jQuery('#checkbox1').change(function () {

                if (jQuery('#checkbox1').is(":checked")) {

                    var gwomen = jQuery('#get_gender-women').val();

                    jQuery("#checkbox1").val(gwomen);

                } else {
                    var gmen = jQuery('#get_gender-men').val();

                    jQuery("#checkbox1").val(gmen);
                }

            });

        });
    </script>



    <div class="dt-dong-peaceUniform">
        <form class="dt-dong-form" method="post">
            <h3>Peace Uniform</h3>
            <h4>Select Shirt Size</h4>
            <div class="dt-dong-switch">
                <?php
                foreach ($att_genders as $gender) {
                    # code...
                    echo '<input id="get_gender-' . $gender->slug . '" type="hidden" value="' . $gender->slug . '" >';
                }
                ?>
                <span id="dt-men">Men</span>
                <input id="checkbox1" type="checkbox" value="men" name="gender">
                <span id="dt-women">Women</span>
            </div>
            <div class="dt-dong-size">
                <?php
                foreach ($att_sizes as $att_size) {
                    echo '<input type="radio" id="' . $att_size->name . '" name="size" value="' . $att_size->slug . '">';
                }
                ?>
            </div>
            <div class="dt-dong-select">
                <h4>Select Council Seat<span>(required)</span></h4>
                <select name="seat" id="Seat">
                    <option value="council">Select Council Seat</option>

                    <?php
                    foreach ($att_colors as $att_color) {
                        # code...
                        echo ' <option value="' . $att_color->slug . '">' . $att_color->description . ' (' . $att_color->name . ')</option>';
                    }
                    ?>
                </select>
                <p>Your Voice is needed to allocate resources in our Détente 2.0 campaign fundraising</p>
            </div>

            <h4>Select Quantity</h4>
            <div class="dt-dong-quantity">
                <button class="qtyminus" aria-hidden="true"><i class="fa-solid fa-minus"></i></button>
                <input type="number" name="dong-qty" id="qty" min="1" step="1" value="1" class="input-qty">
                <button class="qtyplus" aria-hidden="true"><i class="fa-solid fa-plus"></i></button>
            </div>

            ​<?php
            echo get_post_field('post_content', $product_id);
            ?>
            <p class="dt-para-uniform">Each $30 campaign T-shirt earns $7.10 YAM political <span class="dong-tooltip"
                    data-tip="Cash back, also known as “cashback,” refers to two types of financial transactions related to credit and debit cards that have grown increasingly popular in the last two decades. Most commonly, it’s a credit card benefit that refunds the cardholder a small percentage of the amount spent on each purchase above a certain dollar threshold.">cash
                    back</span> rewards.</p>
            <input type="submit" value="Pre-order" name="submit">

        </form>
        <div class="dt-dong-colu">
            <img src="<?php echo wp_get_attachment_url(get_post_thumbnail_id($product_id)); ?>" alt="t-shirt">
        </div>
    </div>

    <?php
    $output = ob_get_contents();
    ob_get_clean();
    return $output;
}
// my account page show user v card qr

function dong_my_account_dashboard_vcard()
{
    ob_start();
    $user_id = get_current_user_id();
    $user_meta_qrs = get_user_meta($user_id, '_dongtraders_user_vcard', true);
    $my_account_page_url = get_permalink(wc_get_page_id('myaccount'));
    if ($my_account_page_url == 'myaccount') {
        if (!empty($user_meta_qrs)) { ?>
            <div class="v-card">
                <img src="<?php echo $user_meta_qrs; ?>" alt="" class="dong-dash-QR">
            </div>

            <?php
        }
    } else {
        if (!empty($user_meta_qrs)) { ?>
            <div class="v-card">
                <img src="<?php echo $user_meta_qrs; ?>" alt="" class="dong-dash-QR-landing-page">
            </div>

            <?php
        }
    }
    $output = ob_get_contents();
    ob_get_clean();
    return $output;
}
add_shortcode('show_vcard', 'dong_my_account_dashboard_vcard');


function add_pmpro_login_body_class($classes)
{
    // Check if it's the PMPro login page
    if (function_exists('pmpro_has_membership_access') && pmpro_has_membership_access(NULL, true)) {
        // Add the unique class to the body classes array
        $classes[] = 'pmpro-login-page';
    }
    return $classes;
}
add_filter('body_class', 'add_pmpro_login_body_class');


//===========================================
//===========================================
//===========================================

add_filter('woocommerce_available_payment_gateways', 'disable_payment_gateways_on_checkout');
function disable_payment_gateways_on_checkout($available_gateways)
{
    if (is_checkout()) {
        // Initialize a variable to check if the product is in the cart
        $product_in_cart = false;

        // Get the global WooCommerce object
        global $woocommerce;

        // Loop through the cart items
        foreach ($woocommerce->cart->get_cart() as $cart_item) {
            // Check if the product ID 2048 is in the cart
            if ($cart_item['product_id'] == 2481 || $cart_item['product_id'] == 1308) {
                $product_in_cart = true;
                break;
            }
        }

        if (!(isset($_GET['pay_for_order']) && $_GET['pay_for_order'])) {
            if ($product_in_cart) {
                unset($available_gateways['preorder']);
            } else {
                unset($available_gateways['paypal']);
                unset($available_gateways['venmo']);
                unset($available_gateways['venmo-pay']);
            }
        } else {
            unset($available_gateways['preorder']);
        }
    }
    return $available_gateways;
}
// add_action('wp_footer', 'jjj');



//rest api to return the uers and their membership level
add_action('rest_api_init', function () {
    register_rest_route('myapi/v1', '/api/', [
        'methods' => 'GET',
        'callback' => 'myapi_hello_endpoint',
    ]);
    
    // Add Discord user insert endpoint
    register_rest_route('myapi/v1', '/discord-user', array(
        'methods' => 'POST',
        'callback' => 'handle_discord_user_insert',
        'permission_callback' => 'check_api_permission'
    ));

    // Add Talent Show Entry endpoint
    register_rest_route('myapi/v1', '/talentshow-entry', array(
        'methods' => 'POST',
        'callback' => 'handle_talentshow_entry_insert',
        'permission_callback' => 'check_api_permission'
    ));

    // Add new endpoint to fetch usermeta with _discord_invite
    register_rest_route('myapi/v1', '/discord-invites', array(
        'methods' => 'GET',
        'callback' => 'get_discord_invite_users',
        'permission_callback' => '__return_true', // change if you need protection
    ));

     // Add new endpoint to fetch usermeta with _discord_poll
     register_rest_route('myapi/v1', '/get-discord-poll', array(
        'methods' => 'GET',
        'callback' => 'get_discord_poll_data',
        'permission_callback' => '__return_true', // change if you need protection
    ));

    // Add new endpoint for Discord Poll votes
    register_rest_route('myapi/v1', '/discord-poll', array(
        'methods' => 'POST',
        'callback' => 'handle_discord_poll_insert',
        'permission_callback' => 'check_api_permission'
    ));

    // Add new endpoint to retrieve user XP data
    register_rest_route('myapi/v1', '/user-xp-data', array(
        'methods' => 'GET',
        'callback' => 'get_user_xp_data',
        'permission_callback' => 'check_api_permission'
    ));
});

/**
 * Callback: Fetch users with _discord_invite meta
 */
function get_discord_invite_users(WP_REST_Request $request)
{
    global $wpdb;

    // Query usermeta table directly
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = %s", '_discord_invite'),
        ARRAY_A
    );

    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No users found with _discord_invite'], 200);
    }

    // Optionally map user_id to username/email
    $users = [];
    foreach ($results as $row) {
        $user_info = get_userdata($row['user_id']);
        $users[] = [
            'user_id' => $row['user_id'],
            'user_login' => $user_info ? $user_info->user_login : null,
            'email' => $user_info ? $user_info->user_email : null,
            'discord_invite' => $row['meta_value'],
        ];
    }

    return new WP_REST_Response($users, 200);
}
function get_discord_poll_data(WP_REST_Request $request)
{
    global $wpdb;

    // Query usermeta table for '_discord_poll'
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT user_id, meta_value FROM $wpdb->usermeta WHERE meta_key = %s",
            '_discord_poll'
        ),
        ARRAY_A
    );

    if (empty($results)) {
        return new WP_REST_Response(['message' => 'No users found with _discord_poll'], 200);
    }

    // Map user_id to username and email
    $users = [];
    foreach ($results as $row) {
        $user_info = get_userdata($row['user_id']);
        $users[] = [
            'user_id' => $row['user_id'],
            'user_login' => $user_info ? $user_info->user_login : null,
            'email' => $user_info ? $user_info->user_email : null,
            'discord_poll' => $row['meta_value'],
        ];
    }

    return new WP_REST_Response($users, 200);
}

function myapi_hello_endpoint($request)
{
    global $wpdb;

    // Join users and membership table
    $results = $wpdb->get_results("
       SELECT
    u.ID as user_id,
    u.user_login,
    u.user_email,
    m.membership_id,
    l.name as membership_name,
    m.startdate,
    m.enddate
FROM {$wpdb->users} u
LEFT JOIN {$wpdb->prefix}pmpro_memberships_users m ON u.ID = m.user_id
LEFT JOIN {$wpdb->prefix}pmpro_membership_levels l ON m.membership_id = l.id

    ");

    return new WP_REST_Response($results, 200);
}

/**
 * Handle Discord user data insertion
 */
function handle_discord_user_insert($request)
{
    $params = $request->get_json_params(); // Safer for JSON POST requests
    
    // Validate required fields
    if (empty($params['discord_id']) || empty($params['email'])) {
        return new WP_Error('missing_fields', 'Discord ID and email are required', ['status' => 400]);
    }

    // Get user by email
    $user = get_user_by('email', sanitize_email($params['email']));
    if (!$user) {
        return new WP_Error('user_not_found', 'User with this email not found', ['status' => 404]);
    }

    // Sanitize and prepare data
    $discord_entry = [
        'discord_id' => sanitize_text_field($params['discord_id']),
        'discord_username' => isset($params['discord_username']) ? sanitize_text_field($params['discord_username']) : '',
        'discord_display_name' => isset($params['discord_display_name']) ? sanitize_text_field($params['discord_display_name']) : '',
        'joined_at' => isset($params['joined_at']) ? sanitize_text_field($params['joined_at']) : current_time('mysql'),
        'guild_id' => isset($params['guild_id']) ? sanitize_text_field($params['guild_id']) : '',
        'joined_via_invite' => isset($params['joined_via_invite']) ? sanitize_text_field($params['joined_via_invite']) : '',
            'xp_type' => 'discord_invite',
            'xp_awarded' => isset($params['xp_awarded']) ? intval($params['xp_awarded']) : 5000000,
            'status' => 'completed',
            'verification_date' => current_time('mysql')
    ];
        
    // Encode entry as JSON
    $meta_value = wp_json_encode($discord_entry);
        
    // Insert as a new row in usermeta (one per invite)
    $insert_id = add_user_meta($user->ID, '_discord_invite', $meta_value);
        
    if ($insert_id) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Discord invite data saved successfully',
            'user_id' => $user->ID,
            'meta_key' => '_discord_invite',
            'meta_id' => $insert_id,
            'data' => $discord_entry
        ]);



    } else {
        return new WP_Error('insert_failed', 'Failed to save Discord data', ['status' => 500]);
    }

}


/**
 * Handle Talent Show entry data insertion
 */
function handle_talentshow_entry_insert($request)
{
    $params = $request->get_json_params();

    // Validate required fields
    if (empty($params['email']) || empty($params['performance_type'])) {
        return new WP_Error('missing_fields', 'Email and performance type are required', ['status' => 400]);
    }

    // Get user by email
    $user = get_user_by('email', sanitize_email($params['email']));
    if (!$user) {
        return new WP_Error('user_not_found', 'User with this email not found', ['status' => 404]);
    }

    // Prepare Talent Show entry data
    $talent_entry = [
        'performance_type' => sanitize_text_field($params['performance_type']),
        'video_url' => isset($params['video_url']) ? esc_url_raw($params['video_url']) : '',
        'description' => isset($params['description']) ? sanitize_textarea_field($params['description']) : '',
        'xp_awarded' => isset($params['xp_awarded']) ? intval($params['xp_awarded']) : 3000000,
        'status' => 'submitted',
        'submission_date' => current_time('mysql')
    ];

    // Encode data as JSON
    $meta_value = wp_json_encode($talent_entry);

    // Insert as a new usermeta entry
    $insert_id = add_user_meta($user->ID, '_talentshow_entry', $meta_value);

    if ($insert_id) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Talent show entry saved successfully',
            'user_id' => $user->ID,
            'meta_key' => '_talentshow_entry',
            'meta_id' => $insert_id,
            'data' => $talent_entry
        ]);
    } else {
        return new WP_Error('insert_failed', 'Failed to save talent show data', ['status' => 500]);
    }
}


/**
 * Handle Discord poll data insertion
 */
function handle_discord_poll_insert($request)
{
    $params = $request->get_json_params();

    // Validate required fields
    if (empty($params['poll_id']) || empty($params['email']) || empty($params['vote'])) {
        return new WP_Error('missing_fields', 'poll_id, email and vote are required', ['status' => 400]);
    }

    // Get user by email
    $user = get_user_by('email', sanitize_email($params['email']));
    if (!$user) {
        return new WP_Error('user_not_found', 'User with this email not found', ['status' => 404]);
    }

    // Prepare poll entry
    $poll_entry = [
        'poll_id' => sanitize_text_field($params['poll_id']),
        'vote' => sanitize_text_field($params['vote']),
        'vote_type' => sanitize_text_field($params['vote_type']),
        'status' => 'submitted',
        'submitted_at' => current_time('mysql'),

        // user info
        'discord_id' => intval($params['discord_id']),
        'username' => sanitize_text_field($params['username']),
        'display_name' => sanitize_text_field($params['display_name']),
        'membership' => sanitize_text_field($params['membership']),

        // XP & rewards
        'xp_awarded' => intval($params['xp_awarded']),
    ];

    // Encode entry as JSON
    $meta_value = wp_json_encode($poll_entry);

    // Insert as a new row in usermeta with key "_discord_poll"
    $insert_id = add_user_meta($user->ID, '_discord_poll', $meta_value);

    if ($insert_id) {
        return rest_ensure_response([
            'success' => true,
            'message' => 'Poll data saved successfully',
            'user_id' => $user->ID,
            'meta_key' => '_discord_poll',
            'meta_id' => $insert_id,
            'data' => $poll_entry
        ]);
    } else {
        return new WP_Error('insert_failed', 'Failed to save poll data', ['status' => 500]);
    }
}

/**
 * Get user XP data for specified meta keys
 */
function get_user_xp_data(WP_REST_Request $request)
{
    // Get parameters
    $user_id = $request->get_param('user_id');
    $email = $request->get_param('email');
    $meta_keys = $request->get_param('meta_keys'); // Comma-separated list
    
    // Default meta keys if not specified
    $default_meta_keys = ['_buyer_details', '_seller_details', '_talentshow_entry', '_discord_invite', '_discord_poll'];
    
    if ($meta_keys) {
        $requested_keys = array_map('trim', explode(',', $meta_keys));
        $meta_keys_to_fetch = array_intersect($requested_keys, $default_meta_keys);
    } else {
        $meta_keys_to_fetch = $default_meta_keys;
    }
    
    if (empty($meta_keys_to_fetch)) {
        return new WP_Error('invalid_meta_keys', 'No valid meta keys specified', ['status' => 400]);
    }
    
    // If no specific user parameters provided, return all users by default
    if (empty($user_id) && empty($email)) {
        return get_all_users_xp_data($meta_keys_to_fetch);
    }
    
    // Handle single user request
    $user = null;
    if ($user_id) {
        $user = get_user_by('id', intval($user_id));
    } elseif ($email) {
        $user = get_user_by('email', sanitize_email($email));
    }
    
    if (!$user) {
        return new WP_Error('user_not_found', 'User not found', ['status' => 404]);
    }
    
    // Get single user data
    $user_meta_data = get_single_user_xp_data($user, $meta_keys_to_fetch);
    
    return new WP_REST_Response($user_meta_data, 200);
}

/**
 * Get XP data for all users
 */
function get_all_users_xp_data($meta_keys_to_fetch)
{
    // Get all users
    $users = get_users([
        'fields' => ['ID', 'user_login', 'user_email', 'display_name'],
        'number' => -1 // Get all users
    ]);
    
    $all_users_data = [
        'total_users' => count($users),
        'users' => []
    ];
    
    foreach ($users as $user) {
        $user_data = get_single_user_xp_data($user, $meta_keys_to_fetch);
        $all_users_data['users'][] = $user_data;
    }
    
    return new WP_REST_Response($all_users_data, 200);
}

/**
 * Get XP data for a single user
 */
function get_single_user_xp_data($user, $meta_keys_to_fetch)
{
    // Organize the data
    $user_meta_data = [
        'user_id' => $user->ID,
        'user_login' => $user->user_login,
        'user_email' => $user->user_email,
        'display_name' => $user->display_name,
        'meta_data' => []
    ];
    
    // Fetch each meta key using get_user_meta()
    foreach ($meta_keys_to_fetch as $meta_key) {
        // For _discord_poll and _talentshow_entry, get all values (multiple entries)
        if ($meta_key === '_discord_poll' || $meta_key === '_talentshow_entry') {
            $meta_values = get_user_meta($user->ID, $meta_key, false);
            
            // Process multiple entries
            $processed_entries = [];
            if (is_array($meta_values) && !empty($meta_values)) {
                foreach ($meta_values as $entry_string) {
                    if (is_string($entry_string)) {
                        $decoded_data = json_decode($entry_string, true);
                        if (json_last_error() === JSON_ERROR_NONE && $decoded_data) {
                            $processed_entries[] = $decoded_data;
                        }
                    }
                }
            }
            
            $meta_value = $processed_entries;
        } else {
            // For other meta keys, get single value
            $meta_value = get_user_meta($user->ID, $meta_key, true);
            
            // Handle empty or false values
            if ($meta_value === false || $meta_value === '') {
                $meta_value = [];
            }
            
            // Try to decode JSON if it's a JSON string
            if (is_string($meta_value)) {
                $json_decoded = json_decode($meta_value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $meta_value = $json_decoded;
                }
            }
        }
        
        $user_meta_data['meta_data'][$meta_key] = $meta_value;
    }
    
    return $user_meta_data;
}

/**
 * Check API permission
 */
function check_api_permission($request)
{
    $auth_header = $request->get_header('Authorization');
    $api_key = str_replace('Bearer ', '', $auth_header);
    return $api_key === get_option('smallstreet_api_key');
}

/**
 * Get REST API URL
 */
// Show API URL and API Key as an admin notice
add_action('admin_notices', function () {
    $api_url = get_rest_url(null, 'myapi/v1/user-xp-data');
    $api_key = get_option('smallstreet_api_key', 'Not Set');

    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>Discord API URL:</strong> ' . esc_url($api_url) . '</p>';
    echo '<p><strong>API Key:</strong> ' . esc_html($api_key) . '</p>';
    echo '</div>';
});


add_action('wp_footer', 'make_quantity_readonly_for_YAM_is_on_product');
function make_quantity_readonly_for_YAM_is_on_product()
{
    if (is_product()) {
        global $product;

        if ($product->get_id() == 1308) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const qtyInput = document.querySelector('form.cart input.qty');
                    if (qtyInput) {
                        qtyInput.readOnly = true;
                        qtyInput.type = 'text'; // Change input type to text
                    }
                });
            </script>
            <?php
        }
    }
}

add_action('wp_footer', 'make_quantity_readonly_for_mordern_piggy_bank_product');
function make_quantity_readonly_for_mordern_piggy_bank_product()
{
    if (is_product()) {
        global $product;

        if ($product->get_id() == 4833) {
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const qtyInput = document.querySelector('form.cart input.qty');
                    if (qtyInput) {
                        qtyInput.readOnly = true;
                        qtyInput.type = 'text'; // Change input type to text
                    }
                });
            </script>
            <?php
        }
    }
}

/**
 * Add Redemption Requests admin menu
 */
add_action('admin_menu', 'dongtrader_add_redemption_admin_menu');

function dongtrader_add_redemption_admin_menu() {
    add_menu_page(
        'Redemption Requests',           // Page title
        'Redemption Requests',           // Menu title
        'manage_options',                // Capability
        'redemption-requests',           // Menu slug
        'dongtrader_redemption_admin_page', // Function to display page
        'dashicons-money-alt',          // Icon
        30                              // Position
    );
}

/**
 * Display redemption requests admin page
 */
function dongtrader_redemption_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dongtrader_redemptions';
    
    // Handle status updates
    if (isset($_POST['update_status']) && isset($_POST['redemption_id']) && isset($_POST['new_status'])) {
        $redemption_id = intval($_POST['redemption_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'admin_notes' => $admin_notes,
                'processed_date' => current_time('mysql')
            ),
            array('id' => $redemption_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Status updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to update status.</p></div>';
        }
    }
    
    // Get all redemption requests
    $redemptions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY redem_date DESC");
    
    ?>
    <div class="wrap">
        <h1>Redemption Requests</h1>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="status_filter" id="status_filter">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
                <button type="button" class="button" onclick="filterByStatus()">Filter</button>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>XP Amount</th>
                    <th>YAM Amount</th>
                    <th>USD Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Details</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($redemptions)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 20px;">No redemption requests found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($redemptions as $redemption): ?>
                        <?php 
                        $user = get_userdata($redemption->user_id);
                        $user_name = $user ? $user->display_name : 'Unknown User';
                        $user_email = $user ? $user->user_email : 'N/A';
                        ?>
                        <tr>
                            <td><?php echo $redemption->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($user_name); ?></strong><br>
                                <small><?php echo esc_html($user_email); ?></small>
                            </td>
                            <td><?php echo number_format($redemption->xp_redem); ?></td>
                            <td><?php echo number_format($redemption->yam_redem, 2); ?></td>
                            <td>$<?php echo number_format($redemption->usd_redem, 2); ?></td>
                            <td><?php echo esc_html($redemption->payment_method); ?></td>
                            <td><?php echo esc_html($redemption->payment_details); ?></td>
                            <td>
                                <span class="status-<?php echo esc_attr($redemption->status); ?>">
                                    <?php echo esc_html(ucfirst($redemption->status)); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($redemption->redem_date)); ?></td>
                            <td>
                                <button type="button" class="button button-small" onclick="showRedemptionDetails(<?php echo $redemption->id; ?>)">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Redemption Details Modal -->
    <div id="redemption-modal" class="redemption-modal" style="display: none;">
        <div class="redemption-modal-content">
            <span class="close" onclick="closeRedemptionModal()">&times;</span>
            <div id="redemption-details"></div>
        </div>
    </div>
    
    <style>
        .status-pending { color: #f56e28; font-weight: bold; }
        .status-processing { color: #0073aa; font-weight: bold; }
        .status-completed { color: #46b450; font-weight: bold; }
        .status-rejected { color: #dc3232; font-weight: bold; }
        
        .redemption-modal {
            position: absolute;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .redemption-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
            position: relative;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .redemption-details {
            margin-top: 20px;
        }
        
        .redemption-details h3 {
            margin-top: 0;
        }
        
        .redemption-details .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .redemption-details .detail-label {
            font-weight: bold;
            width: 150px;
        }
        
        .redemption-details .detail-value {
            flex: 1;
        }
        
        .status-update-form {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        
        .status-update-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .status-update-form select,
        .status-update-form textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .status-update-form textarea {
            height: 80px;
        }
    </style>
    
    <script>
        function filterByStatus() {
            var status = document.getElementById('status_filter').value;
            var rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                if (status === '') {
                    row.style.display = '';
                } else {
                    var statusCell = row.querySelector('.status-' + status);
                    row.style.display = statusCell ? '' : 'none';
                }
            });
        }
        
        function showRedemptionDetails(redemptionId) {
            // AJAX call to get redemption details
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        document.getElementById('redemption-details').innerHTML = response.data.html;
                        document.getElementById('redemption-modal').style.display = 'block';
                    } else {
                        alert('Error loading redemption details: ' + response.data);
                    }
                }
            };
            xhr.send('action=get_redemption_details&redemption_id=' + redemptionId);
        }
        
        function closeRedemptionModal() {
            document.getElementById('redemption-modal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('redemption-modal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <?php
}

/**
 * AJAX handler to get redemption details
 */
add_action('wp_ajax_get_redemption_details', 'dongtrader_get_redemption_details');

function dongtrader_get_redemption_details() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dongtrader_redemptions';
    
    $redemption_id = intval($_POST['redemption_id']);
    $redemption = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $redemption_id));
    
    if (!$redemption) {
        wp_send_json_error('Redemption request not found');
    }
    
    $user = get_userdata($redemption->user_id);
    $user_name = $user ? $user->display_name : 'Unknown User';
    $user_email = $user ? $user->user_email : 'N/A';
    
    $html = '<div class="redemption-details">';
    $html .= '<h3>Redemption Request #' . $redemption->id . '</h3>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">User:</div>';
    $html .= '<div class="detail-value">' . esc_html($user_name) . ' (' . esc_html($user_email) . ')</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">XP Amount:</div>';
    $html .= '<div class="detail-value">' . number_format($redemption->xp_redem) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">YAM Amount:</div>';
    $html .= '<div class="detail-value">' . number_format($redemption->yam_redem, 2) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">USD Amount:</div>';
    $html .= '<div class="detail-value">$' . number_format($redemption->usd_redem, 2) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Conversion Rate (XP/YAM):</div>';
    $html .= '<div class="detail-value">' . number_format($redemption->conversion_rate_xp_yam, 2) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Conversion Rate (YAM/USD):</div>';
    $html .= '<div class="detail-value">' . number_format($redemption->conversion_rate_yam_usd, 2) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Payment Method:</div>';
    $html .= '<div class="detail-value">' . esc_html($redemption->payment_method) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Payment Details:</div>';
    $html .= '<div class="detail-value">' . esc_html($redemption->payment_details) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Meta IDs:</div>';
    $html .= '<div class="detail-value">' . esc_html($redemption->meta_ids) . '</div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Status:</div>';
    $html .= '<div class="detail-value"><span class="status-' . esc_attr($redemption->status) . '">' . esc_html(ucfirst($redemption->status)) . '</span></div>';
    $html .= '</div>';
    
    $html .= '<div class="detail-row">';
    $html .= '<div class="detail-label">Submission Date:</div>';
    $html .= '<div class="detail-value">' . date('M j, Y g:i A', strtotime($redemption->redem_date)) . '</div>';
    $html .= '</div>';
    
    if ($redemption->processed_date) {
        $html .= '<div class="detail-row">';
        $html .= '<div class="detail-label">Processed Date:</div>';
        $html .= '<div class="detail-value">' . date('M j, Y g:i A', strtotime($redemption->processed_date)) . '</div>';
        $html .= '</div>';
    }
    
    if ($redemption->admin_notes) {
        $html .= '<div class="detail-row">';
        $html .= '<div class="detail-label">Admin Notes:</div>';
        $html .= '<div class="detail-value">' . esc_html($redemption->admin_notes) . '</div>';
        $html .= '</div>';
    }
    
    // Status update form
    $html .= '<div class="status-update-form">';
    $html .= '<h4>Update Status</h4>';
    $html .= '<form method="post" action="">';
    $html .= '<input type="hidden" name="redemption_id" value="' . $redemption->id . '">';
    
    $html .= '<label for="new_status">Status:</label>';
    $html .= '<select name="new_status" id="new_status" required>';
    $html .= '<option value="pending"' . ($redemption->status === 'pending' ? ' selected' : '') . '>Pending</option>';
    $html .= '<option value="processing"' . ($redemption->status === 'processing' ? ' selected' : '') . '>Processing</option>';
    $html .= '<option value="completed"' . ($redemption->status === 'completed' ? ' selected' : '') . '>Completed</option>';
    $html .= '<option value="rejected"' . ($redemption->status === 'rejected' ? ' selected' : '') . '>Rejected</option>';
    $html .= '</select>';
    
    $html .= '<label for="admin_notes">Admin Notes:</label>';
    $html .= '<textarea name="admin_notes" id="admin_notes" placeholder="Add notes about this redemption request...">' . esc_textarea($redemption->admin_notes) . '</textarea>';
    
    $html .= '<button type="submit" name="update_status" class="button button-primary">Processed</button>';
    $html .= '</form>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    wp_send_json_success(array('html' => $html));
}





