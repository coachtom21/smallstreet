<?php

add_filter('wp_mail_content_type', function ($content_type) {
    return 'text/html';
});

/**
 * It returns the API credentials for the API name passed to it
 *
 * @param [apiname] The name of the API you're using.
 *
 * @return [$api_utils] API credentials for the API name passed in.
 */

function dongtrader_get_api_cred($apiname)
{
    if (!$apiname) {
        return;
    }

    if ($apiname == 'qrtiger') {
        $api_utils = get_option('dongtrader_api_settings_qrtiger');
    } else if ($apiname == 'glassfrog') {
        $api_utils = get_option('dongtrader_api_settings_glassfrog');
    } else if ($apiname == 'crowdsignal') {
        $api_utils = get_option('dongtrader_api_settings_crowdsignal');
    }
    return $api_utils;
}

function qrtiger_upload_logo()
{
}

/**
 * YAM JAM XP Conversion Rate Helper Functions
 * Based on documentation: $1 = 21,000 YAM = 1,000 XP
 */

/**
 * Get XP per dollar (sextillionth precision)
 * @return int XP per USD
 */
function dongtrader_xp_per_dollar() {
    return 1000000000000000000000; // 1 USD = 1,000,000,000,000,000,000,000 XP (10^21)
}

/**
 * Get XP per YAM token
 * @return float XP per YAM
 */
function dongtrader_xp_per_yam() {
    return 1000000000000000000000 / 21000; // 47,619,047,619,047,619 XP per YAM (10^21 / 21,000)
}

/**
 * Convert USD to XP
 * @param float $usd_amount USD amount
 * @return int XP amount
 */
function dongtrader_usd_to_xp($usd_amount) {
    return intval($usd_amount * dongtrader_xp_per_dollar());
}

/**
 * Convert XP to USD
 * @param int $xp_amount XP amount
 * @return float USD amount
 */
function dongtrader_xp_to_usd($xp_amount) {
    return $xp_amount / dongtrader_xp_per_dollar();
}

/**
 * Convert XP to YAM
 * @param int $xp_amount XP amount
 * @return float YAM amount
 */
function dongtrader_xp_to_yam($xp_amount) {
    return $xp_amount / dongtrader_xp_per_yam();
}

/**
 * Convert YAM to XP
 * @param float $yam_amount YAM amount
 * @return int XP amount
 */
function dongtrader_yam_to_xp($yam_amount) {
    return intval($yam_amount * dongtrader_xp_per_yam());
}

/**
 * ========================================
 * MATURITY DATE TRACKING FUNCTIONS
 * ========================================
 */

/**
 * Get configured maturity weeks (default 10, range 8-12)
 * @return int Number of weeks for maturity
 */
function dongtrader_get_maturity_weeks() {
    $weeks = get_option('dongtrader_maturity_weeks', 10);
    // Ensure weeks is between 8-12
    return max(8, min(12, intval($weeks)));
}

/**
 * Calculate maturity date based on delivery date
 * @param string $delivery_date Delivery/earned date (Y-m-d H:i:s format)
 * @param int|null $weeks Optional weeks override (default uses option value)
 * @return string Maturity date in Y-m-d H:i:s format
 */
function dongtrader_calculate_maturity_date($delivery_date, $weeks = null) {
    if (empty($delivery_date)) {
        return null;
    }
    
    if ($weeks === null) {
        $weeks = dongtrader_get_maturity_weeks();
    }
    
    // Ensure weeks is between 8-12
    $weeks = max(8, min(12, intval($weeks)));
    
    try {
        $delivery = new DateTime($delivery_date);
        $delivery->modify("+{$weeks} weeks");
        return $delivery->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        error_log("Error calculating maturity date: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if XP entry is mature (8-12 weeks have passed)
 * @param string $delivery_date Delivery/earned date
 * @param string|null $current_date Optional current date (default: now)
 * @return bool True if mature, false if still maturing
 */
function dongtrader_is_xp_entry_mature($delivery_date, $current_date = null) {
    if (empty($delivery_date)) {
        return false;
    }
    
    if ($current_date === null) {
        $current_date = current_time('mysql');
    }
    
    $maturity_date = dongtrader_calculate_maturity_date($delivery_date);
    
    if (!$maturity_date) {
        return false;
    }
    
    // Check if current date is >= maturity date
    return (strtotime($current_date) >= strtotime($maturity_date));
}

/**
 * Get delivery date from XP transaction entry
 * Tries multiple date fields to find when XP was earned
 * @param array $transaction XP transaction array
 * @return string|null Delivery date or null if not found
 */
function dongtrader_get_delivery_date_from_xp_entry($transaction) {
    if (!is_array($transaction)) {
        error_log("dongtrader_get_delivery_date_from_xp_entry: Transaction is not an array");
        return null;
    }
    
    // Debug: Log all transaction keys
    error_log("dongtrader_get_delivery_date_from_xp_entry: Transaction keys: " . implode(', ', array_keys($transaction)));
    
    // Check various possible date fields (in order of priority)
    $date_fields = array(
        'delivery_date',
        'earned_date',
        'verification_date',
        'order_date',
        'order_datetime',
        'date',
        'created_at',
        'timestamp',
        'purchase_date', // Common in WooCommerce
        'completed_date' // WooCommerce order completed date
    );
    
    foreach ($date_fields as $field) {
        if (isset($transaction[$field]) && !empty($transaction[$field])) {
            $date_value = $transaction[$field];
            error_log("dongtrader_get_delivery_date_from_xp_entry: Found date field '$field' = '$date_value'");
            
            // If it's a timestamp, convert to datetime
            if (is_numeric($date_value)) {
                $formatted_date = date('Y-m-d H:i:s', $date_value);
                error_log("dongtrader_get_delivery_date_from_xp_entry: Converted timestamp to: $formatted_date");
                return $formatted_date;
            }
            
            // Try to parse as date string
            try {
                $date = new DateTime($date_value);
                $formatted_date = $date->format('Y-m-d H:i:s');
                error_log("dongtrader_get_delivery_date_from_xp_entry: Parsed date string to: $formatted_date");
                return $formatted_date;
            } catch (Exception $e) {
                error_log("dongtrader_get_delivery_date_from_xp_entry: Failed to parse '$field' as date: " . $e->getMessage());
                continue;
            }
        }
    }
    
    // Additional fallback: Check if there's an order_id and try to get date from WooCommerce order
    if (isset($transaction['order_id']) && !empty($transaction['order_id'])) {
        $order_id = intval($transaction['order_id']);
        if ($order_id > 0) {
            $woocommerce_order = wc_get_order($order_id);
            if ($woocommerce_order && is_a($woocommerce_order, 'WC_Order')) {
                // Try to get order date
                $order_date = $woocommerce_order->get_date_created();
                if ($order_date) {
                    $formatted_date = $order_date->format('Y-m-d H:i:s');
                    error_log("dongtrader_get_delivery_date_from_xp_entry: Retrieved date from WooCommerce order #$order_id: $formatted_date");
                    return $formatted_date;
                }
            }
        }
    }
    
    // Fallback: If no date found, return null
    error_log("dongtrader_get_delivery_date_from_xp_entry: No date field found in transaction. Available fields: " . implode(', ', array_keys($transaction)));
    return null;
}

/**
 * Calculate days until maturity
 * @param string $delivery_date Delivery/earned date
 * @return int|null Days until maturity (negative if already mature), null if error
 */
function dongtrader_days_until_maturity($delivery_date) {
    if (empty($delivery_date)) {
        return null;
    }
    
    $maturity_date = dongtrader_calculate_maturity_date($delivery_date);
    if (!$maturity_date) {
        return null;
    }
    
    $current_time = current_time('timestamp');
    $maturity_timestamp = strtotime($maturity_date);
    
    $diff_seconds = $maturity_timestamp - $current_time;
    $days = floor($diff_seconds / (60 * 60 * 24));
    
    return $days;
}

/**
 * ========================================
 * MONTHLY REDEMPTION WINDOW FUNCTIONS
 * ========================================
 */

/**
 * Get monthly redemption window dates
 * Default: 1st through 7th of each month
 * @param int|null $month Optional month (1-12), default current month
 * @param int|null $year Optional year, default current year
 * @return array Array with 'start' and 'end' dates
 */
function dongtrader_get_monthly_redemption_window($month = null, $year = null) {
    if ($month === null) {
        $month = (int)date('n');
    }
    if ($year === null) {
        $year = (int)date('Y');
    }
    
    // Get window start and end day from options (default: 1st to 7th)
    $window_start_day = get_option('dongtrader_redemption_window_start', 1);
    $window_end_day = get_option('dongtrader_redemption_window_end', 7);
    
    $start_date = new DateTime("{$year}-{$month}-{$window_start_day} 00:00:00");
    $end_date = new DateTime("{$year}-{$month}-{$window_end_day} 23:59:59");
    
    return array(
        'start' => $start_date->format('Y-m-d H:i:s'),
        'end' => $end_date->format('Y-m-d H:i:s')
    );
}

/**
 * Check if current date is within a monthly redemption window
 * @param string|null $current_date Optional date to check (default: now)
 * @return bool True if within window, false otherwise
 */
function dongtrader_is_within_redemption_window($current_date = null) {
    if ($current_date === null) {
        $current_date = current_time('mysql');
    }
    
    $current_timestamp = strtotime($current_date);
    $current_date_obj = new DateTime($current_date);
    $current_day = (int)$current_date_obj->format('d');
    
    // Check if it's September 1st (no redemptions allowed)
    if ($current_date_obj->format('m-d') === '09-01') {
        return false;
    }
    
    $window = dongtrader_get_monthly_redemption_window();
    $window_start = strtotime($window['start']);
    $window_end = strtotime($window['end']);
    
    // Check if current date is within the window
    return ($current_timestamp >= $window_start && $current_timestamp <= $window_end);
}

/**
 * Get next redemption window date
 * @return array Array with 'date', 'start', and 'end' for next window
 */
function dongtrader_get_next_redemption_window() {
    $current_date = new DateTime(current_time('mysql'));
    $current_month = (int)$current_date->format('n');
    $current_year = (int)$current_date->format('Y');
    
    // Get current month's window
    $current_window = dongtrader_get_monthly_redemption_window($current_month, $current_year);
    $window_end_timestamp = strtotime($current_window['end']);
    $current_timestamp = $current_date->getTimestamp();
    
    // If current time is past this month's window, get next month's window
    if ($current_timestamp > $window_end_timestamp || $current_date->format('m-d') === '09-01') {
        // Move to next month
        $next_month = $current_month + 1;
        $next_year = $current_year;
        
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }
        
        $window = dongtrader_get_monthly_redemption_window($next_month, $next_year);
    } else {
        // Current window is still active
        $window = $current_window;
    }
    
    return array(
        'date' => date('F Y', strtotime($window['start'])),
        'start' => $window['start'],
        'end' => $window['end']
    );
}

/**
 * Check if user is eligible to redeem XP
 * Validates all requirements: minimum amount, matured XP, redemption window, active requests
 * @param float $total_usd Total USD value of completed XP
 * @param int $total_completed_xp Total completed/matured XP
 * @param bool $has_active_redemption Whether user has pending/processing redemptions
 * @return array Array with 'eligible' bool and 'reason' string if not eligible
 */
function dongtrader_check_redemption_button_eligibility($total_usd, $total_completed_xp, $has_active_redemption) {
    $min_redemption_amount = 1.0; // Minimum $1.00 USD required
    
    // Validation 1: Check for active redemption requests
    if ($has_active_redemption) {
        return array(
            'eligible' => false,
            'reason' => 'active_redemption',
            'message' => 'You have a pending redemption request. Please wait for it to be processed.'
        );
    }
    
    // Validation 2: Check minimum USD amount (STRICT: Must be >= $1.00)
    $total_usd_numeric = floatval($total_usd);
    // Use strict comparison - button only shows if amount is >= $1.00 exactly
    if ($total_usd_numeric < $min_redemption_amount) {
        return array(
            'eligible' => false,
            'reason' => 'minimum_amount',
            'message' => 'You need at least $1.00 USD worth of matured XP to redeem. Current value: $' . number_format($total_usd_numeric, 2),
            'current_amount' => $total_usd_numeric,
            'required_amount' => $min_redemption_amount,
            'debug_info' => 'USD: ' . $total_usd_numeric . ' | Required: ' . $min_redemption_amount . ' | Check: ' . ($total_usd_numeric < $min_redemption_amount ? 'FAIL' : 'PASS')
        );
    }
    
    // Validation 3: Check if user has matured/completed XP (not just pending)
    if ($total_completed_xp <= 0) {
        return array(
            'eligible' => false,
            'reason' => 'no_matured_xp',
            'message' => 'You need matured XP credits (8-12 weeks old) to redeem. Your XP is still pending maturity.'
        );
    }
    
    // Validation 4: Check if within redemption window
    if (!dongtrader_is_within_redemption_window()) {
        $current_date_obj = new DateTime(current_time('mysql'));
        $is_september_1st = ($current_date_obj->format('m-d') === '09-01');
        $days_until = dongtrader_days_until_next_redemption_window();
        $next_window = dongtrader_get_next_redemption_window();
        
        if ($is_september_1st) {
            return array(
                'eligible' => false,
                'reason' => 'september_1st_block',
                'message' => 'No redemptions allowed on September 1st (Let It Ride Day).',
                'next_window_date' => date('F j, Y', strtotime($next_window['start'])),
                'days_until_window' => $days_until
            );
        } else {
            return array(
                'eligible' => false,
                'reason' => 'outside_window',
                'message' => 'Redemption window is currently closed.',
                'next_window_date' => date('F j, Y', strtotime($next_window['start'])),
                'days_until_window' => $days_until,
                'window_range' => date('F j', strtotime($next_window['start'])) . ' - ' . date('F j', strtotime($next_window['end']))
            );
        }
    }
    
    // All validations passed
    return array(
        'eligible' => true,
        'reason' => 'all_checks_passed'
    );
}

/**
 * Get days until next redemption window
 * @return int|null Days until next window (0 if currently in window)
 */
function dongtrader_days_until_next_redemption_window() {
    $current_date = current_time('mysql');
    
    // If currently in window, return 0
    if (dongtrader_is_within_redemption_window($current_date)) {
        return 0;
    }
    
    // Check if it's September 1st
    $current_date_obj = new DateTime($current_date);
    if ($current_date_obj->format('m-d') === '09-01') {
        // Next window is October 1-7
        $next_window = dongtrader_get_monthly_redemption_window(10, (int)$current_date_obj->format('Y'));
        $window_start_timestamp = strtotime($next_window['start']);
        $current_timestamp = $current_date_obj->getTimestamp();
        $diff_seconds = $window_start_timestamp - $current_timestamp;
        return max(0, floor($diff_seconds / (60 * 60 * 24)));
    }
    
    $next_window = dongtrader_get_next_redemption_window();
    $window_start_timestamp = strtotime($next_window['start']);
    $current_timestamp = strtotime($current_date);
    $diff_seconds = $window_start_timestamp - $current_timestamp;
    
    return max(0, floor($diff_seconds / (60 * 60 * 24)));
}

/*This function is used to make the Qrtiger API requests.
 *These are valid urls . Requests might be  costly so plz use mock url from stoplight api
 *GET URL : https://qrtiger.com/data/6BF7
 *POST URL  : https://qrtiger.com/api/campaign/
 *Function Call Process is Here with the endpoints
 *$POST = dongtrader_http_requests('/api/campaign/', array(), 'POST');
 *$GET = dongtrader_http_requests('/data/6BF7', array(), 'GET');
 */
function qrtiger_api_request($endpoint = '', $bodyParams = array(), $method = "GET")
{
    /* Get the API credentials from the database. */
    $qrtiger_creds = get_option('dongtraders_api_settings_fields');
    /* Check if the API credentials are empty or not. */
    $checkFields = !empty($qrtiger_creds['qrtiger-api-key']) && !empty($qrtiger_creds['qrtiger-api-url']) ? true : false;
    /* Check if the API credentials are empty or not. */
    if (!$checkFields) {
        return;
    }

    /* Get the API URL from the database. */
    $qrtiger_api_root_url = $qrtiger_creds['qrtiger-api-url'];
    /* Getting the API key from the database. */
    $qrtiger_api_key = $qrtiger_creds['qrtiger-api-key'];
    /* Concatenating the API root URL with the endpoint. */
    $build_url = $qrtiger_api_root_url . $endpoint;
    /* A default array. */

    /* Taking the default array and merging it with the  array. */
    //$body = wp_json_encode(wp_parse_args($qrtiger_defaults, $bodyParams));
    $body = wp_json_encode($bodyParams);
    /* Setting the options for the request. */
    $options = [
        'body' => $method == "POST" ? $body : '',
        'headers' => [
            'Authorization' => 'Bearer ' . $qrtiger_api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,

    ];

    // $build_url = 'https://stoplight.io/mocks/qrtiger/qrtiger-api/7801905';

    /* A ternary operator to check get or post parameter and use functions accordingly*/
    $response_received = $method == 'POST' ? wp_remote_post($build_url, $options) : wp_remote_get($build_url, $options);
    
    // Check for WP_Error
    if (is_wp_error($response_received)) {
        return false;
    }
    
    /* Get the response code from the response received. */
    $response_status = wp_remote_retrieve_response_code($response_received);
    /* Checking if the response status is 200 or not. If it is 200 then it will return the body of the response. */
    $response_body = $response_status == 200 ? wp_remote_retrieve_body($response_received) : false;
    /* Checking if the response body is not empty and then decoding the response body. */
    $response_object = $response_body ? json_decode($response_body) : false;

    $resp = $response_object->status != 403 ? $response_object : false;

    return $resp;
}

/* A function that is used to make the Glassfrog API requests. */
function glassfrog_api_request($endpoint = '', $str = '', $method = "GET")
{
    /* Get the API credentials from the database. */
    $api_creds = get_option('dongtraders_api_settings_fields');
    /*Get API Key*/
    $gf_api_key = $api_creds['glassfrog-api-key'];
    /*Get API Url*/
    $gf_api_url = $api_creds['glassfrog-api-url'];
    /* Check if the API credentials are empty or not. */
    $checkFields = !empty($gf_api_key) && !empty($gf_api_url) ? true : false;
    /* if not valid return false */
    if (!$checkFields) {
        return;
    }

    /* Build Url for api request */
    $build_url = $gf_api_url . $endpoint;

    /* Params For API requests */
    $options = [
        'body' => $method == "POST" ? $str : '',
        'headers' => [
            'X-Auth-Token' => $gf_api_key,
            'Content-Type' => 'application/json',

        ],

    ];

    // vdd($options);

    /* A ternary operator to check get or post parameter and use functions accordingly*/
    $response_received = $method == 'POST' ? wp_remote_post($build_url, $options) : wp_remote_get($build_url, $options);
    
    // Check for WP_Error
    if (is_wp_error($response_received)) {
        return false;
    }
    
    /* Get the response code from the response received. */
    $response_status = wp_remote_retrieve_response_code($response_received);
    /* Checking if the response status is 200 or not. If it is 200 then it will return the body of the response. */
    $response_body = $response_status == 200 ? wp_remote_retrieve_body($response_received) : false;
    /* Checking if the response body is not empty and then decoding the response body. */
    $response_object = $response_body ? json_decode($response_body) : false;



    return $response_object;
}

/*This function is used to make ajax call on woocommerce my account page.
 *It calls qrtiger api and fetches qrcodes data
 */
add_action('wp_ajax_dongtrader_generate_qr2', 'dongtrader_generate_qr2');

function dongtrader_generate_qr2()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_generate_qr2')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    $qr_size = sanitize_text_field($_POST['qrsize']);
    $qr_url = sanitize_url($_POST['qrurl']);
    $qr_color = sanitize_text_field($_POST['qrcolor']);
    $qr_logo_url = plugin_dir_url(__FILE__) . 'assets/img/currency.png';
    $dong_user_id = get_current_user_id();
    $response = !empty($qr_size) && !empty($qr_url) && !empty(trim($qr_color)) ? true : false;
    $notify_to_js = array(
        'dataStatus' => $response,
        'user' => $dong_user_id,
        'apistatus' => false,
    );
    if ($response) {
        //either background color or qr elements color can be set
        $qrtiger_array = [
            "qr" => [
                "size" => $qr_size,
                "logo" => 'currency.png',
                "colorDark" => $qr_color,
                "backgroundColor" => '',
                "transparentBkg" => false,
            ],
            "qrUrl" => $qr_url,
            "qrType" => "qr2",
            "qrCategory" => "url",
        ];
        $qrtiger_api_call = qrtiger_api_request('/api/campaign/', $qrtiger_array, 'POST');

        if ($qrtiger_api_call) {
            $notify_to_js['apistatus'] = true;
            $current_dong_qr_array = array(
                'created_by' => $dong_user_id,
                'qr_image_url' => $qrtiger_api_call->data->qrImage,
                'created_at' => $qrtiger_api_call->data->createdAt,
                'updated_at' => $qrtiger_api_call->data->updatedAt,
                'qr_id' => $qrtiger_api_call->data->qrId,
            );

            $old_dong_qr_array = get_option('dong_user_qr_values');
            $prev_value_check = !empty($old_dong_qr_array) ? true : false;
            if ($prev_value_check) {
                array_push($old_dong_qr_array, $current_dong_qr_array);
                // update_user_meta($dong_user_id, 'dong_user_qr_values', $old_dong_qr_array);
                update_option('dong_user_qr_values', array_reverse($old_dong_qr_array));
            } else {
                // update_user_meta($dong_user_id, 'dong_user_qr_values', [$current_dong_qr_array]);
                update_option('dong_user_qr_values', [$current_dong_qr_array]);
            }
        }
    }

    echo wp_json_encode($notify_to_js);

    wp_die();
}

//dongtrader_delete_qr

add_action('wp_ajax_dongtrader_delete_qr', 'dongtrader_delete_qr');

function dongtrader_delete_qr()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $index = esc_attr($_POST['qrIndex']);
    $dong_qr_array = get_option('dong_user_qr_values');
    $resp_array = array('success' => false, 'd' => $dong_qr_array, 'i' => $index);

    if ($dong_qr_array && !empty($index) || $index == '0') {

        unset($dong_qr_array[$index]);
        $new_arrray = $dong_qr_array;
        update_option('dong_user_qr_values', $new_arrray);
        $resp_array['success'] = true;
    }
    echo wp_json_encode($resp_array);
    wp_die();
}

//Simple function to make api calls with parameters as an array

function dongtrader_variable_color_to_rgb_color($color)
{
    switch ($color) {
        case 'orange':
            $color = 'rgb(255, 51, 0)';
            break;
        case 'purple':
            $color = 'rgb(245,66,245)';
            break;
        case 'red':
            $color = 'rgb(255, 0, 0)';
            break;

        case 'blue':
            $color = 'rgb(36, 36, 143)';
            break;

        case 'green':
            $color = 'rgb(0, 204, 0)';
            break;

        default:
            $color = 'rgb(38, 38, 38)';
    }

    return $color;
}

function dongtrader_ajax_test_helper($color = '', $url = '', $size = '')
{

    return array(
        'qr_image_url' => 'https://qrtiger.com/qr/OXX3.png',
        'created_at' => 'date',
        'updated_at' => 'date',
        'qr_id' => '0XX3',
    );
}

function dongtrader_ajax_helper($color, $url, $size = '500')
{
    $logo = "https://smallstreet.app/wp-content/uploads/2023/03/3D2-1-1.png";
    $current_dong_qr_array = true;
    $qrtiger_array = [
        "qr" => [
            "logo" => $logo,
            "size" => $size,
            "colorDark" => $color,
            "transparentBkg" => false,
        ],
        "qrUrl" => $url,
        "qrType" => "qr2",
        "qrCategory" => "url",
    ];
    $qrtiger_api_call = qrtiger_api_request('/api/campaign/', $qrtiger_array, 'POST');

    if ($qrtiger_api_call) {
        $current_dong_qr_array = array(
            "qr_image_url" => $qrtiger_api_call->data->qrImage,
            "created_at" => $qrtiger_api_call->data->createdAt,
            "updated_at" => $qrtiger_api_call->data->updatedAt,
            "qr_id" => $qrtiger_api_call->data->qrId,
        );
    } else {

        $current_dong_qr_array = false;
    }

    return $current_dong_qr_array;
}

add_action('wp_ajax_dongtrader_meta_qr_generator', 'dongtrader_meta_qr_generator');

function dongtrader_meta_qr_generator()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_meta_qr_generator')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $intiator = esc_attr($_POST['intiator']);
    $productnum = esc_attr($_POST['productnums']);
    $product = wc_get_product($productnum);
    $product_url = get_permalink($productnum);
    $check_out_Url = get_permalink($productnum) . '?add=1';

    //add your datas here
    $resp = array(
        'success' => false,
        'template' => '',
        'pid' => $productnum,
        'initiator' => $intiator,
        "purl" => $product_url,
    );

    //for products qr code
    if ($intiator == '_product_qr_codes') {

        $current_data = dongtrader_ajax_helper('rgb(87, 3, 48)', $product_url);
        if (!empty($current_data)) {
            $update_data = json_encode($current_data);
            $resp['success'] = true;
            $resp['template'] = '<div id="" class="dong-qr-components">
            <img src="' . $current_data['qr_image_url'] . '" alt="" width="200" height="200">
            <button data-url= "' . $current_data['qr_image_url'] . '" class="button button-primary button-large url-copy" >Copy QR URL</button>
            <input type= "hidden" data-id="' . esc_attr($productnum) . '"  name ="_product_qr_codes" value="' . esc_attr($update_data) . '">
            <button data-meta="_product_qr_codes" data-remove="' . $productnum . '" class="button-primary button-large qr-remover" style="" >Remove</button></div>';

            update_post_meta($productnum, '_product_qr_codes', $update_data);
        }
        //for direct checkout
    } elseif ($intiator == '_product-qr-direct-checkouts') {
        //call api helper function
        $current_data = dongtrader_ajax_helper('rgb(0, 102, 204)', $check_out_Url);
        if (!empty($current_data)) {
            $update_data = json_encode($current_data);
            update_post_meta($productnum, '_product-qr-direct-checkouts', $update_data);
            $resp['success'] = true;
            $resp['template'] = '<div id="" class="dong-qr-components">
            <img src="' . $current_data['qr_image_url'] . '" alt="" width="200" height="200">
            <button data-url= "' . $current_data['qr_image_url'] . '" class="button button-primary button-large url-copy" >Copy QR URL</button>
            <input type= "hidden" data-id="' . esc_attr($productnum) . '"  name ="_product-qr-direct-checkouts" value="' . esc_attr($update_data) . '">
            <button data-meta="_product_qr_codes" data-remove="' . $productnum . '" class="button-primary button-large qr-remover" style="" >Remove</button></div>';
        }
        //for variable products
    } elseif ($intiator == '_product-qr-variabled') {
        $variations = esc_attr($_POST['variations']);
        $loop = esc_attr($_POST['loop']);
        $html = '';
        if (!empty($variations)) {
            $get_url = get_permalink($variations);
            $html = '';
            $modfied_url = $get_url . '&varid=' . $variations;
            $attr_color = get_post_meta($variations, 'attribute_pa_sector', true);

            $resp['attr_color'] = $attr_color;
            //echo $attr_color . '-color';
            $current__array = dongtrader_ajax_helper(dongtrader_variable_color_to_rgb_color($attr_color), $modfied_url);
            if ($current__array) {
                $update_data = json_encode($current__array);
                update_post_meta($variations, 'variable_product_qr_data', esc_attr($update_data));
                $html .= '<div data-color="' . $attr_color . '" id="dong-qr-components' . $loop . '" class="dong-qr-components dong-qr-components-var">';
                $html .= '<div class="qr-img-container-var">';
                //qr image
                $html .= '<img src="' . $current__array['qr_image_url'] . '' . '" alt="" width="100" height="100">';
                $html .= '</div>';
                //url copp
                $html .= '<div class="qr-urlbtn-container-var">';
                $html .= '<button data-url="' . $current__array['qr_image_url'] . '" class="button-primary button-large url-copy" >Copy QR URL</button>';
                //remover
                $html .= '<button data-index="' . $loop . '" id="variable_product_qr_data' . $loop . '" data-meta="variable_product_qr_data" data-remove="' . $variations . '" class="button-primary button-large qr-remover"  style="margin-left:10px" >Remove</button>';
                $html .= '</div>';
                //hidden field
                $html .= '<input data-id="' . esc_attr($productnum) . '" type="hidden" name ="variable_product_qr_data" value="' . esc_attr($update_data) . '">';
                $html .= '</div>';
            }
            $resp['success'] = true;
            $resp['template'] = $html;
        }
    }

    echo json_encode($resp);
    wp_die();
}

// Remove functionality for qr codes
add_action('wp_ajax_dongtrader_delete_qr_fields', 'dongtrader_delete_qr_fields');
function dongtrader_delete_qr_fields()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr_fields')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    $variation_id = esc_attr($_POST['itemID']);
    $variation_meta_key = esc_attr($_POST['metakey']);
    $ajax_values = array('resp' => false, 'html' => false);
    if (!empty($variation_id) && !empty($variation_meta_key)) {
        $status = delete_post_meta($variation_id, $variation_meta_key);
        if ($status) {
            $ajax_values['status'] = true;
            $ajax_values['html'] = true;
        }
    }
    wp_send_json($ajax_values);
    wp_die();
}

//remove functionality for qr codes from settings page
// Remove functionality for qr codes
add_action('wp_ajax_dongtrader_delete_qr_items_settingspage', 'dongtrader_delete_qr_items_settingspage');
function dongtrader_delete_qr_items_settingspage()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr_items_settingspage')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $dong_qr_array = get_option('dong_user_qr_values');
    $index = (int) esc_attr($_POST['index']);
    $ajax_values = array('resp' => false, 'reload' => false);
    if ($index >= 0) {

        unset($dong_qr_array[$index]);
        array_values($dong_qr_array);
        update_option('dong_user_qr_values', $dong_qr_array);
        $new_qr_array = get_option('dong_user_qr_values');

        $reload = count($new_qr_array) == 0 ? true : false;

        $ajax_values = array('resp' => true, 'reload' => $reload);
    }

    wp_send_json($ajax_values);

    wp_die();
}


function dongtrader_user_registration_hook($customer_id, $p_id, $oid)
{

    //global database variable
    global $wpdb;

    //custom user table from our database
    $table_name = $wpdb->prefix . 'glassfrog_user_data';

    //custom group table
    $group_table_name = $wpdb->prefix . 'glassfrog_group_data';

    // Downline table 
    $downline_table = $wpdb->prefix . 'mega_mlm_downline';

    //check if a person already exists in a database
    $check_persons = $wpdb->get_row($wpdb->prepare("SELECT all_orders , group_id FROM $table_name WHERE user_id = %d", $customer_id), ARRAY_A);

    if (!empty($check_persons)) {

        if ($check_persons['group_id'] != 0) {
            //query to update group distribution status to update_required when the person has already brought new product after distribution from cron job
            $wpdb->query($wpdb->prepare("UPDATE $group_table_name SET distribution_status = 'update_required' WHERE group_id = %d", (int) $check_persons['group_id']));
        } elseif ($check_persons['group_id'] == 0) {

            $unserialized_orders = unserialize($check_persons['all_orders']);

            //check if order id is already on the list and append new order id to it
            if (!in_array($oid, $unserialized_orders)) {

                $unserialized_orders[] = $oid;

                //again serilize data to store in db
                $new_serilized_orders = serialize($unserialized_orders);

                //preapre and update serialized data
                $wpdb->query($wpdb->prepare("UPDATE $table_name SET all_orders = %s WHERE user_id = %d", $new_serilized_orders, $customer_id));
            }
        }
    } else {
        // Get the product object
        $product = wc_get_product($p_id);

        //get parent id if product is variable
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $p_id;

        //for this case orders datas doesnt exists on our custom database table so we need to call the glassfrog api and insert data accordingly
        $gf_checkbox = get_post_meta($parent_id, '_glassfrog_checkbox', true);

        //bool to check meta
        $gf_check = $gf_checkbox == 'on' ? true : false;

        if ($gf_check) {
            //get user object
            $user_info = get_userdata($customer_id);

            //order object
            $orderobj = new WC_Order($oid);

            // get sponsor id 
            $sponsor_id = $orderobj->get_meta('mega_affid');

            //refferal
            $refferal = !empty($sponsor_id) ? (int) $sponsor_id : null;

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

                //glassfrog id from the api
                $gf_id = $samp->people[0]->id;

                //glassfrog persons  name
                $gf_name = $samp->people[0]->name;

                $all_orders = serialize(array($oid));

                $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO $table_name
                        (
                            user_id,
                            gf_person_id,
                            gf_name,
                            in_glassfrog,
                            all_orders,
                            group_id,
                            upline_id

                        )
                        VALUES ( %d,%d,%s,%d,%s,%d,%d)",
                        $customer_id, // d
                        $gf_id, // d
                        $gf_name, // s
                        0, // d
                        $all_orders, // s
                        0, // d
                        $refferal //d

                    )
                );

                if ($refferal) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO $downline_table
                            (
                                user_id,
                                downline_user_id
                            )
                            VALUES ( %d,%d)",
                            $refferal,
                            $customer_id
                        )
                    );
                }

                $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

            endif;
        } else {

            //when order need not to be sent to glass frog where and how to manage it??

        }
    }
}

/* add custom field to save user role on user profile */
add_action('show_user_profile', 'dong_show_user_role');
add_action('edit_user_profile', 'dong_show_user_role');
function dong_show_user_role($user)
{

    $dong_user_role = get_user_meta($user->ID, 'dong_user_role', true);
    /*   echo $dong_user_role . '-dong user role'; */

    ?>
    <table class="form-table">
        <tr>
            <th><label for="city">Dong User Role</label></th>
            <td>
                <select name="dong_user_role" id="">
                    <option value="Planning" <?php if ($dong_user_role == "Planning") {
                        echo 'selected="selected"';
                    }
                    ?>>
                        Planning (Purple)</option>
                    <option value="Budget" <?php if ($dong_user_role == "Budget") {
                        echo 'selected="selected"';
                    }
                    ?>>Budget
                        (Orange)</option>
                    <option value="Media" <?php if ($dong_user_role == "Media") {
                        echo 'selected="selected"';
                    }
                    ?>>Media (Red)
                    </option>
                    <option value="Distribution" <?php if ($dong_user_role == "Distribution") {
                        echo 'selected="selected"';
                    }
                    ?>>Distribution (Green)</option>
                    <option value="Membership" <?php if ($dong_user_role == "Membership") {
                        echo 'selected="selected"';
                    }
                    ?>>
                        Membership (Blue)</option>
                </select>
                </select>
            </td>
        </tr>
    </table>
    <?php

}

add_action('personal_options_update', 'dong_user_role_save_profile_fields');
add_action('edit_user_profile_update', 'dong_user_role_save_profile_fields');

function dong_user_role_save_profile_fields($user_id)
{

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }

    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    update_user_meta($user_id, 'dong_user_role', sanitize_text_field($_POST['dong_user_role']));
}

/* Functions to set dong uer role */

function dong_set_user_role($user_id, $product_id)
{
    $get_varition_color = get_post_meta($product_id, 'attribute_pa_color', true);
    $get_color_role = dongtrader_get_product_color($get_varition_color);
    update_user_meta($user_id, 'dong_user_role', $get_color_role);
}

function dongtrader_get_product_color($role)
{
    switch ($role) {
        case 'orange':
            $role = 'Budget';
            break;
        case 'purple':
            $role = 'Planning';
            break;
        case 'red':
            $role = 'Media';
            break;
        case 'blue':
            $role = 'Membership';
            break;
        case 'green':
            $role = 'Distribution';
            break;
        default:
            $role = 'Membership';
    }

    return $role;
}

function dongtrader_convert_sector_to_slug($sector)
{
    switch ($sector) {
        case 'budget':
            $role = 'orange';
            break;
        case 'planning':
            $role = 'purple';
            break;
        case 'media':
            $role = 'red';
            break;
        case 'membership':
            $role = 'blue';
            break;
        case 'distribution':
            $role = 'green';
            break;
        default:
            $role = 'blue';
    }

    return $role;
}

/*Order Export Form NEw*/

function dongtraders_order_export_form_new()
{

    $settings = get_option('dongtraders_api_settings_fields');
    $patron_pro_val = $settings['dong_patron_mem'];

    $mega_pro_val = $settings['dong_mega_mem'];
    $sectors = get_terms(array(
        'taxonomy' => 'pa_sector',
        'hide_empty' => true,
    ));
    ?>

    <form action="" method="POST" class="order-export-form">
        <?php wp_nonce_field('order_export_form', 'order_export_nonce'); ?>
        <?php

        $validate = true;
        if (isset($_POST['customer-phone']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && phone_number_exists($_POST['customer-phone'])) {
            $validate = false;
            echo '<div class="error-box">This Phone number is<b>' . esc_html($_POST['customer-phone']) . '</b> already used. Please use unique phone number and try again</div>';
        }


        if (isset($_POST['customer-email']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && mega_check_email($_POST['customer-email'])) {
            echo '<div class="error-box">This email address <b>' . esc_html($_POST['customer-email']) . '</b> is already used .Please use unique email and try again</div>';
            // !mega_check_email( $_POST['customer-email'])
            $validate = false;
        }

        if (isset($_POST['set-order_export']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && $validate == true) {

            echo '<div class="success-box">Affiliate Order Data inserted Sucessfully</div>';
        }

        ?>
        <div class="form-group">
            <label for="">Customer Email</label>
            <div class="form-control-wrap">
                <input name="customer-email" class="form-control customer-email" type="email" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer Email'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer First Name</label>
            <div class="form-control-wrap">
                <input name="customer-first-name" class="form-control customer-first-name" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer First Name'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer Last Name</label>
            <div class="form-control-wrap">
                <input name="customer-last-name" class="form-control customer-last-name" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Last Name'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer Phone</label>
            <div class="form-control-wrap">
                <input name="customer-phone" class="form-control customer-phone" type="number" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer Phone'" required="" value="">
            </div>
        </div>


        <div class="form-group">
            <label for="">Customer Country</label>
            <div class="form-control-wrap">

                <select onchange="print_state('state',this.selectedIndex);" id="country" name="customer-country"
                    class="form-control"></select>
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer State</label>
            <div class="form-control-wrap">
                <select name="customer-state" id="state" class="form-control"></select>
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer Address</label>
            <div class="form-control-wrap">
                <input name="customer-address" class="form-control customer-address" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Address'" required="" value="">
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer Postcode</label>
            <div class="form-control-wrap">
                <input name="customer-postcode" class="form-control customer-postcode" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Postcode'" required="" value="">
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer City</label>
            <div class="form-control-wrap">
                <input name="customer-city" class="form-control customer-city" type="text" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer City'" required="" value="">
            </div>
        </div>

        <div class=" form-group">
            <label for="">Select Membership</label>
            <div class="form-control-wrap">
                <select name="select-product" id="form-field-name" class="form-control select-product" required="required"
                    aria-required="true">
                    <option value="">Select Membership</option>
                    <option value="megavoter">MEGAvoter</option>
                    <option value="patron">Patron</option>
                </select>

            </div>
        </div>
        <div class="form-group">
            <!--  <label for="">Select Variation Product</label> -->
            <div class="form-control-wrap">
                <?php if (!empty($sectors)): ?>
                    <label for="">Select Sectors</label>
                    <select name="membership-sectors" id="form-field-type" class="form-control variation-product"
                        required="required" aria-required="true">
                        <option value="">Select Sectors</option>
                        <?php foreach ($sectors as $s) { ?>
                            <option value="<?php echo $s->slug ?>"><?php echo $s->name; ?></option>
                        <?php } ?>
                    </select>

                <?php endif; ?>

            </div>
        </div>
        <div class="form-group">
            <?php $user_ID = get_current_user_id(); ?>
            <div class="form-control-wrap">
                <input type="hidden" name="affilate-user" class="form-control affilate-user"
                    value="<?php echo $user_ID; ?>">
            </div>
        </div>
        <div class="form-group">
            <input class="cpm-btn submit real-button" type="submit" value="Add Custom Order" name="set-order_export">
        </div>
    </form>

    <?php
    if (isset($_POST['set-order_export']) && phone_number_exists($_POST['customer-phone']) !== true && mega_check_email($_POST['customer-email']) !== true) {
        $customer_email = $_POST['customer-email'];
        $customer_first_name = $_POST['customer-first-name'];
        $customer_last_name = $_POST['customer-last-name'];
        $customer_phone = $_POST['customer-phone'];
        $customer_country = $_POST['customer-country'];
        $customer_state = $_POST['customer-state'];
        $customer_address = $_POST['customer-address'];
        $customer_postcode = $_POST['customer-postcode'];
        $customer_city = $_POST['customer-city'];
        $customer_mem = wc_clean($_POST['select-product']);
        $customer_sector = wc_clean($_POST['membership-sectors']);
        $role = wc_clean(dongtrader_get_product_color($customer_sector));
        $variation = dongtrader_convert_sector_to_slug(strtolower($customer_sector));

        $customer_affilate_user_final = $_POST['affilate-user'];

        $created_date = date("Y-m-d");


        global $wpdb;
        $order_table_name = $wpdb->prefix . 'dong_order_export_table';
        $wpdb->query(
            $order_insert = $wpdb->prepare(
                "INSERT INTO $order_table_name
               (
                customer_email,
                customer_first_name,
                customer_last_name,
                customer_phone,
                customer_country,
                customer_state,
                customer_address,
                customer_postcode,
                customer_city,
                customer_membership,
                customer_sector,
                affilate_user_id,
                created_at
               )
               VALUES ( %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %d, %s )",
                esc_attr($customer_email),
                esc_attr($customer_first_name),
                esc_attr($customer_last_name),
                esc_attr($customer_phone),
                esc_attr($customer_country),
                esc_attr($customer_state),
                esc_attr($customer_address),
                esc_attr($customer_postcode),
                esc_attr($customer_city),
                $customer_mem,
                strtolower($role),
                esc_attr($customer_affilate_user_final),
                $created_date

            )
        );
        if ($order_insert) {

            $cart_items = '';

            if (strtolower($customer_mem) == 'megavoter') {

                $mega_pro_val = $settings['dong_mega_mem'];

                $cart_items = mega_add_variation_to_cart($mega_pro_val, $variation);
            } elseif (strtolower($customer_mem) == 'patron') {

                $patron_pro_val = $settings['dong_patron_mem'];

                $cart_items = mega_add_variation_to_cart($patron_pro_val, $variation);
            }

            if (!email_exists($customer_email) && !empty($cart_items)) {
                $random_password = wp_generate_password();

                $display_name = $customer_first_name . ' ' . $customer_last_name;

                $user_id = wc_create_new_customer($customer_email, $display_name, $random_password);

                $affiliate_user_id = get_current_user_id();
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
                    'first_name' => $customer_first_name,
                    'last_name' => $customer_last_name,
                    'company' => '',
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'address_1' => $customer_address,
                    'address_2' => '',
                    'city' => $customer_city,
                    'state' => $customer_state,
                    'postcode' => $customer_postcode,
                    'country' => $customer_country
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
                    $order_obj = wc_get_order($order_id);

                    mega_set_membership_level($order_obj); //7
                    mega_custom_ordermeta_update($order_obj); //8
                    mega_update_mlm_database($order_obj); //9
                    use_email_as_username($order_id);
                }
            }
        } else {
            echo '<div class="error-box">Order Data could not inserted.Please use unique phone number or email address and try again</div>';
        }
    }
}


/* dongtraders custom export order list */

function dongtraders_custom_order_created_list()
{
    global $wpdb;
    $order_table_name = $wpdb->prefix . 'dong_order_export_table';
    ?>

    <div class=" cpm-table-wrap">
        <div class="export-section">

            <form action="" id="export-csv-order">
                <span id="from">From</span>
                <input id="start-month" name="start_month" type="date" size="2">
                <span id="to">To</span>
                <input id="end-month" name="end_month" type="date" size="2">
                <select name="affilate_id" id="affilate_id">
                    <?php
                    $get_all_users = get_users();
                    echo '<option value="">Select Affilate User</option>';
                    foreach ($get_all_users as $get_all_user) {
                        echo '<option value="' . $get_all_user->ID . '">' . $get_all_user->user_login . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="button button-primary buttonload">Export CSV<i
                        class="fa fa-spinner fa-spin export-loader"></i></button>
            </form>
        </div>
        <table id="qr-all-list">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Date</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone No.</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Postcode</th>
                    <th>Product Id</th>
                    <th>Variation Id</th>
                    <th>Affilate User</th>
                    <th>Remove</th>

                </tr>
            </thead>
            <tbody>
                <?php

                $get_order_results = $wpdb->get_results("SELECT *  FROM $order_table_name ORDER BY id DESC;");

                //$get_url = home_url() . '/wp-admin/admin.php?page=dongtrader_api_settings';
                /* $current_page = ''; */
                if (!empty($get_order_results)) {
                    $i = 1;
                    foreach ($get_order_results as $export_order) {

                        echo '
                     <tr>
                    <td>' . $i . '</td>
                     <td>' . $export_order->created_at . '</td>
                   <td>' . $export_order->customer_email . '</td>
                   <td>' . $export_order->customer_first_name . '</td>
                   <td>' . $export_order->customer_last_name . '</td>
                   <td>' . $export_order->customer_phone . '</td>
                   <td>' . $export_order->customer_address . '</td>
                   <td>' . $export_order->customer_country . '</td>
                   <td>' . $export_order->customer_state . '</td>
                   <td>' . $export_order->customer_city . '</td>
                   <td>' . $export_order->customer_postcode . '</td>
                   <td>' . $export_order->product_id . '</td>
                   <td>' . $export_order->product_varition_id . '</td>
                   <td>' . $export_order->affilate_user_id . '</td>
                 <td>
                 <form action="" method="post">
                        <input type="hidden" name="export-id" value="' . $export_order->id . '">
                       <button type="submit" name="delete-export" value="Delete" class="cpm-btn export-delete dashicons-before dashicons-trash"></button>
                        </form>
                        </td>
                </tr>
                ';
                        $i++;
                    }
                } else {
                    echo '<div class="error-box">No Records Found</div>';
                }

                // Handle delete
                if (isset($_POST['delete-export'])) {

                    $delete_id = (int) $_POST['export-id'];

                    // Delete data in mysql from row that has this id
                    $result = $wpdb->delete($order_table_name, array('id' => $delete_id));

                    // if successfully deleted
                    if ($result) {

                        echo '<div class="success-box">Deleted order ID-> ' . $delete_id . ' Successfully</div>';
                    } else {
                        echo '<div class="error-box">Order Data could not Deleted ! Please Try again</div>';
                    }
                    /*  wp_redirect($current_page); */
                }
                ?>


            </tbody>
        </table>
    </div>
    <?php
}

/*ajax function to run csv exporter*/

if (!function_exists('dong_custom_order_exporter_csv_files')) {
    function dong_custom_order_exporter_csv_files($post)
    {
        // Security check: Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dong_custom_order_exporter_csv_files')) {
            wp_die('Security check failed');
        }
        
        // Security check: Verify user is logged in
        if (!is_user_logged_in()) {
            wp_die('You must be logged in to perform this action');
        }
        
        // Security check: Verify user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $get_start_date = $_POST['start_date'];
        $get_end_date = $_POST['end_date'];
        $get_affilate_user_id = $_POST['user_id'];

        global $wpdb;
        $get_table_name = $wpdb->prefix . 'dong_order_export_table';

        if (!empty($get_start_date) && !empty($get_end_date) && !empty($get_affilate_user_id)) {
            $get_custom_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $get_table_name WHERE created_at BETWEEN %s AND %s AND affilate_user_id = %d", 
                $get_start_date, $get_end_date, $get_affilate_user_id
            ), ARRAY_A);
        } elseif (!empty($get_start_date) && !empty($get_end_date) && empty($get_affilate_user_id)) {
            $get_custom_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $get_table_name WHERE created_at BETWEEN %s AND %s", 
                $get_start_date, $get_end_date
            ), ARRAY_A);
        }

        if (DateTime::createFromFormat('Y-m-d', $get_start_date) == false && DateTime::createFromFormat('Y-m-d', $get_end_date) == false) {
            $get_custom_orders = $wpdb->get_results("SELECT * FROM $get_table_name ", ARRAY_A);
        }
        if (!empty($get_custom_orders)) {
            $cpm_order_exporter_generate_csv_filename = 'dongtraders-custom-orders' . date('Ymd_His') . '-export.csv';
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename={$cpm_comment_exporter_generate_csv_filename}');
            $output = fopen('php://output', 'w');

            fputcsv($output, ['csv_id', 'customer_email', 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_address_1', 'billing_postcode', 'billing_city', 'billing_state', 'billing_country', 'customer_membership', 'customer_sector', 'affilate_user_id']);

            foreach ($get_custom_orders as $get_custom_order) {
                $export_id = $get_custom_order['id'];
                $export_customer_email = $get_custom_order['customer_email'];
                $export_customer_first_name = $get_custom_order['customer_first_name'];
                $export_customer_last_name = $get_custom_order['customer_last_name'];
                $export_customer_phone = $get_custom_order['customer_phone'];
                $export_customer_address = $get_custom_order['customer_address'];
                $export_customer_country = $get_custom_order['customer_country'];
                $export_customer_state = $get_custom_order['customer_state'];
                $export_customer_postcode = $get_custom_order['customer_postcode'];
                $export_customer_city = $get_custom_order['customer_city'];
                $export_membership = $get_custom_order['customer_membership'];
                $export_sector = $get_custom_order['customer_sector'];
                $export_affilate_user_id = $get_custom_order['affilate_user_id'];

                fputcsv($output, [$export_id, $export_customer_email, $export_customer_first_name, $export_customer_last_name, $export_customer_phone, $export_customer_address, $export_customer_postcode, $export_customer_city, $export_customer_state, $export_customer_country, $export_membership, $export_sector, $export_affilate_user_id]);
            }
            fclose($output);
        }

        die();
    }

    add_action('wp_ajax_dong_custom_order_exporter_csv_files', 'dong_custom_order_exporter_csv_files');
}

/* show current user added affilate order and exporter */

function dongtraders_show_user_affilate_order()
{
    global $wpdb;
    $order_table_name = $wpdb->prefix . 'dong_order_export_table';
    $user_ID = get_current_user_id();

    ?>
    <div class="cpm-table-wrap">
        <div class="export-section">

            <form action="" id="export-csv-order">
                <div class=" export-date export-date-from">
                    <span id="from">From</span>
                    <input id="start-month" name="start_month" type="date" size="2">
                </div>
                <div class="export-date export-date-to">
                    <span id="to">To</span>
                    <input id="end-month" name="end_month" type="date" size="2">
                </div>

                <input id="end-month" name="affilate_id" type="hidden" size="2" value="<?php echo $user_ID; ?>">
                <button type="submit" class="button button-primary buttonload">Export CSV<i
                        class="fa fa-spinner fa-spin export-loader"></i></button>
            </form>
        </div>
        <table id="my-account-affilate-order">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Date</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone No.</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Postcode</th>
                    <th>Membership</th>
                    <th>Sector</th>
                    <th>Affilate User</th>
                    <th>Remove</th>

                </tr>
            </thead>
            <tbody>
                <?php

                $get_order_results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $order_table_name WHERE affilate_user_id = %d ORDER BY id DESC", 
                    $user_ID
                ), ARRAY_A);
                //$get_url = home_url() . '/wp-admin/admin.php?page=dongtrader_api_settings';
                $current_page = home_url($_SERVER['REQUEST_URI']);
                if (!empty($get_order_results)) {
                    $i = 1;
                    foreach ($get_order_results as $export_order) {

                        echo '
                     <tr>
                    <td>' . $i . '</td>
                     <td>' . $export_order->created_at . '</td>
                   <td>' . $export_order->customer_email . '</td>
                   <td>' . $export_order->customer_first_name . '</td>
                   <td>' . $export_order->customer_last_name . '</td>
                   <td>' . $export_order->customer_phone . '</td>
                   <td>' . $export_order->customer_address . '</td>
                   <td>' . $export_order->customer_country . '</td>
                   <td>' . $export_order->customer_state . '</td>
                   <td>' . $export_order->customer_city . '</td>
                   <td>' . $export_order->customer_postcode . '</td>
                   <td>' . $export_order->customer_membership . '</td>
                   <td>' . $export_order->customer_sector . '</td>
                   <td>' . $export_order->affilate_user_id . '</td>
                 <td>
                 <form action="" method="post">
                        <input type="hidden" name="export-id" value="' . $export_order->id . '">
                       <button type="submit" name="delete-export" value="Delete" class="cpm-btn export-delete dashicons-before dashicons-trash"></button>
                        </form>
                        </td>
                </tr>
                ';
                        $i++;
                    }
                } else {
                    echo '<div class="error-box">No Records Found</div>';
                }

                // Handle delete
                if (isset($_POST['delete-export'])) {

                    $delete_id = (int) $_POST['export-id'];

                    // Delete data in mysql from row that has this id
                    $result = $wpdb->delete($order_table_name, array('id' => $delete_id));

                    // if successfully deleted
                    if ($result) {

                        echo '<div class="success-box">Deleted order ID-> ' . $delete_id . ' Successfully</div>';
                    } else {
                        echo '<div class="error-box">Order Data could not Deleted ! Please Try again</div>';
                    }
                    wp_redirect($current_page);
                }
                ?>


            </tbody>
        </table>
    </div>

    <?php
}

// set quantity product for yam sticker as 10 quantity

// Displaying quantity setting fields on admin product pages
add_action('woocommerce_product_options_pricing', 'wc_qty_add_product_field');
function wc_qty_add_product_field()
{
    global $product_object;

    $values = $product_object->get_meta('_qty_args');

    echo '</div><div class="options_group quantity hide_if_grouped">
    <style>div.qty-args.hidden { display:none; }</style>';

    woocommerce_wp_checkbox(array( // Checkbox.
        'id' => 'qty_args',
        'label' => __('Quantity settings', 'woocommerce'),
        'value' => empty($values) ? 'no' : 'yes',
        'description' => __('Enable this to show and enable the additional quantity setting fields.', 'woocommerce'),
    ));

    echo '<div class="qty-args hidden">';

    woocommerce_wp_text_input(array(
        'id' => 'qty_min',
        'type' => 'number',
        'label' => __('Set Quantity', 'woocommerce-max-quantity'),
        'placeholder' => '',
        'desc_tip' => 'true',
        'description' => __('Set a minimum allowed quantity limit (a number greater than 0).', 'woocommerce'),
        'custom_attributes' => array('step' => 'any', 'min' => '0'),
        'value' => isset($values['qty_min']) && $values['qty_min'] > 0 ? (int) $values['qty_min'] : 10,
    ));

    echo '</div>';
}

// Show/hide setting fields (admin product pages)
add_action('admin_footer', 'product_type_selector_filter_callback');
function product_type_selector_filter_callback()
{
    global $pagenow, $post_type;

    if (in_array($pagenow, array('post-new.php', 'post.php')) && $post_type === 'product'):
        ?>
        <script>
            jQuery(function ($) {
                if ($('input#qty_args').is(':checked') && $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').removeClass('hidden')
                }
                $('input#qty_args').click(function () {
                    if ($(this).is(':checked') && $('div.qty-args').hasClass('hidden')) {
                        $('div.qty-args').removeClass('hidden');
                    } else if (!$(this).is(':checked') && !$('div.qty-args').hasClass('hidden')) {
                        $('div.qty-args').addClass('hidden');
                    }
                });
            });
        </script>
        <?php
    endif;
}

// Save quantity setting fields values
add_action('woocommerce_admin_process_product_object', 'wc_save_product_quantity_settings');
function wc_save_product_quantity_settings($product)
{
    if (isset($_POST['qty_args'])) {
        $values = $product->get_meta('_qty_args');

        $product->update_meta_data('_qty_args', array(
            'qty_min' => isset($_POST['qty_min']) && $_POST['qty_min'] > 0 ? (int) wc_clean($_POST['qty_min']) : 0,
            // 'qty_max' => isset($_POST['qty_max']) && $_POST['qty_max'] > 0 ? (int) wc_clean($_POST['qty_max']) : -1,
            // 'qty_step' => isset($_POST['qty_step']) && $_POST['qty_step'] > 1 ? (int) wc_clean($_POST['qty_step']) : 1,
        ));
    } else {
        $product->update_meta_data('_qty_args', array());
    }
}

// The quantity settings in action on front end
add_filter('woocommerce_quantity_input_args', 'filter_wc_quantity_input_args', 99, 2);
function filter_wc_quantity_input_args($args, $product)
{
    if ($product->is_type('variation')) {
        $parent_product = wc_get_product($product->get_parent_id());
        $values = $parent_product->get_meta('_qty_args');
    } else {
        $values = $product->get_meta('_qty_args');
    }

    if (!empty($values)) {
        // Min value
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $args['min_value'] = $values['qty_min'];

            if (!is_cart()) {
                $args['input_value'] = $values['qty_min']; // Starting value
            }
        }
    }
    return $args;
}

// Ajax add to cart, set "min quantity" as quantity on shop and archives pages
add_filter('woocommerce_loop_add_to_cart_args', 'filter_loop_add_to_cart_quantity_arg', 10, 2);
function filter_loop_add_to_cart_quantity_arg($args, $product)
{
    $values = $product->get_meta('_qty_args');

    if (!empty($values)) {
        // Min value
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $args['quantity'] = $values['qty_min'];
        }
    }
    return $args;
}

// The quantity settings in action on front end (For variable productsand their variations)
add_filter('woocommerce_available_variation', 'filter_wc_available_variation_price_html', 10, 3);
function filter_wc_available_variation_price_html($data, $product, $variation)
{
    $values = $product->get_meta('_qty_args');

    if (!empty($values)) {
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $data['min_qty'] = $values['qty_min'];
        }
    }

    return $data;
}
function dongtraders_set_product_quantity($product_id)
{
    $get_qty = get_post_meta($product_id, '_qty_args', true);
    if (!empty($get_qty)) {
        $get_qty_no = $get_qty['qty_min'];
        return $get_qty_no;
    }
    //return 0;
}

function dongtrader_release_funds_tablelist()
{
    global $wpdb;

    $release_fund = $wpdb->prefix . 'release_groups_profit';
    $group_table = $wpdb->prefix . 'mega_mlm_groups';

    // Filter variables
    $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
    $group_name = isset($_GET['group_name']) ? sanitize_text_field($_GET['group_name']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    // Build the SQL query with filter conditions
    $release_fund_prepared_query = "SELECT * FROM $release_fund";

    if ($filter_type === 'date-range' && $start_date && $end_date) {
        $release_fund_prepared_query .= $wpdb->prepare(" WHERE release_date BETWEEN %s AND %s", $start_date, $end_date);
    } elseif ($filter_type === 'group-name' && $group_name) {
        $release_fund_prepared_query .= $wpdb->prepare(" WHERE group_id IN (SELECT group_id FROM $group_table WHERE circle_name LIKE %s)", "%{$group_name}%");
    } elseif ($filter_type === 'all') {
        // No specific filter selected, return all data
    }

    $release_fund_prepared_query .= " ORDER BY release_date DESC";

    // Get results from the SQL query
    $release_fund_results = $wpdb->get_results($release_fund_prepared_query, ARRAY_A);

    $items_per_page = 10; // Number of items to display per page
    $paged = isset($_GET['rfapaged']) ? (int) $_GET['rfapaged'] : 1; // Get current page number


    ?>
    <h3>Disaster Relief Funds List</h3>
    <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="filter-form" id="posts-filter">
        <input type="hidden" name="page" value="dongtrader_api_settings">
        <div class="tablenav top">
            <div class="post_filter">
                <select name="filter_type" id="filter-type">
                    <option value="all" <?php selected($filter_type, 'all'); ?>>All</option>
                    <option value="group-name" <?php selected($filter_type, 'group-name'); ?>>By Group Name</option>
                    <option value="date-range" <?php selected($filter_type, 'date-range'); ?>>By Date Range</option>
                </select>
                <div id="group-name-filter" class="filter-field" <?php if ($filter_type !== 'group-name') { ?>style="display: none;" <?php } ?>>
                    <input type="text" name="group_name" id="group-name-input" placeholder="Enter group name"
                        value="<?php echo $group_name; ?>">
                </div>
                <div id="date-range-filter" class="filter-field" <?php if ($filter_type !== 'date-range') { ?>style="display: none;" <?php } ?>>
                    <input type="date" name="start_date" id="start-date-input" placeholder="Start date"
                        value="<?php echo $start_date; ?>">
                    <input type="date" name="end_date" id="end-date-input" placeholder="End date"
                        value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" id="apply-filter">Apply Filter</button>
                <button type="reset" id="reset-filter">Reset Filter</button>
            </div>
        </div>
    </form>
    <div class="cpm-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Released Date</th>
                    <th>Group Name</th>
                    <th>Released Amount</th>
                    <th>Release Cause</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($release_fund_results)) {
                    $symbol = get_woocommerce_currency_symbol();
                    // Split array into chunks based on items per page
                    $paginated_release_fund_results = array_chunk($release_fund_results, $items_per_page);

                    $current_items = $paginated_release_fund_results[$paged - 1]; // Get the items for the current page
                    $i = 1;
                    foreach ($current_items as $gr) {

                        $group_name = $wpdb->get_var($wpdb->prepare("SELECT circle_name FROM $group_table WHERE group_id = %d", (int) $gr['group_id']));
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $gr['release_date'] ?></td>
                            <td><?php echo $group_name ?></td>
                            <td><?php echo $symbol . $gr['release_amount'] ?></td>
                            <td><?php echo $gr['release_note'] ?></td>
                            <td><button class="rf-del" data-rfid="<?php echo $gr['id'] ?>">Delete</td>
                        </tr>
                        <?php $i++;
                    } ?>
                <?php } else { ?>
                    <tr>
                        <td style="text-align:center;" colspan="4">Details Not Found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php
    if (!empty($release_fund_results)) {
        // Pagination links
        echo '<div class="pagination" style="float:right">';
        echo paginate_links(array(
            'base' => add_query_arg('rfapaged', '%#%'), // Base URL with page number placeholder
            'format' => '&rfapaged=%#%', // Add the paged parameter to the URL
            'prev_text' => __('&laquo; Previous', 'text-domain'),
            'next_text' => __('Next &raquo;', 'text-domain'),
            'total' => count($paginated_release_fund_results), // Total number of pages
            'current' => $paged, // Current page number
        ));
        echo '</div>';
    }
    ?>
    <script>
        jQuery(document).ready(function ($) {
            // Show/hide filter fields based on selected filter type
            $('#filter-type').on('change', function () {
                var filterType = $(this).val();
                $('.filter-field').hide();
                $('#' + filterType + '-filter').val('');
                $('#' + filterType + '-filter').show();
            });

            // Reset filter form
            $('#reset-filter').on('click', function () {
                $('.filter-form')[0].reset();
                $('.filter-field').hide(); // Hide all filter fields
                $('#filter-type').trigger('change'); // Trigger change event to show/hide fields based on selected filter type

                var url = removeURLParameter(window.location.href, 'filter_type');
                url = removeURLParameter(url, 'group_name');
                url = removeURLParameter(url, 'start_date');
                url = removeURLParameter(url, 'end_date');

                window.history.replaceState({}, document.title, url);

                window.location.reload();
            });

            // On page load, trigger change event to show/hide fields based on selected filter type
            $('#filter-type').trigger('change');

            // Helper function to remove a parameter from the URL
            function removeURLParameter(url, parameter) {
                var urlParts = url.split('?');
                if (urlParts.length >= 2) {
                    var prefix = encodeURIComponent(parameter) + '=';
                    var params = urlParts[1].split(/[&;]/g);

                    for (var i = params.length - 1; i >= 0; i--) {
                        if (params[i].lastIndexOf(prefix, 0) !== -1) {
                            params.splice(i, 1);
                        }
                    }

                    url = urlParts[0] + (params.length > 0 ? '?' + params.join('&') : '');
                    url = url.replace(/[?&]$/, ''); // Remove trailing ? or & if present
                }
                return url;
            }
        });
    </script>
    <?php
}

add_action('wp_ajax_dongtrader_delete_funds', 'dongtrader_delete_funds');
function dongtrader_delete_funds()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_funds')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    // Security check: Verify user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $row_id = isset($_POST['rowid']) ? absint($_POST['rowid']) : 0;

    if ($row_id != 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'release_groups_profit';

        // Delete the row with the specified ID
        $result = $wpdb->delete($table_name, array('id' => $row_id), array('%d'));

        if ($result !== false) {
            wp_send_json_success('Row with ID ' . $row_id . ' has been deleted.');
        } else {
            wp_send_json_error('Failed to delete the row.');
        }
    } else {
        wp_send_json_error('Invalid row ID.');
    }
}


function dongtrader_compare_released_funds($group_id, $current_releasing_amount)
{

    global $wpdb;

    $customers_table = $wpdb->prefix . 'mega_mlm_customers';

    $rfund_table = $wpdb->prefix . 'release_groups_profit';


    $total_release_amount = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(release_amount) AS total_release_amount 
          FROM $rfund_table
          WHERE group_id = %d",
        (int) $group_id
    ));

    $current_amount = $total_release_amount + $current_releasing_amount;

    $all_gr_user = $wpdb->prepare("SELECT user_id FROM $customers_table WHERE customer_group_id=%d", (int) $group_id);

    $users = $wpdb->get_results($all_gr_user, ARRAY_A);

    $users_arrays = array_column($users, 'user_id');

    $total_group_profit_sum = NULL;

    foreach ($users_arrays as $ua) {

        $group_prof_metas = get_user_meta($ua, '_group_details', true);

        if (empty($group_prof_metas))
            continue;

        foreach ($group_prof_metas as $gpm) {

            $total_group_profit_sum += $gpm['profit_amount'];
        }
    }

    return array(
        'compare' => $total_group_profit_sum >= $current_amount,
        'total_profit' => $total_group_profit_sum,
        'total_released' => $total_release_amount,
        'total_releaseable' => $total_group_profit_sum - $total_release_amount
    );
}


add_action('wp_ajax_dongtrader_release_funds', 'dongtrader_release_funds');
function dongtrader_release_funds()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_release_funds')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    // Security check: Verify user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'release_groups_profit';

    $customers_table = $wpdb->prefix . 'mega_mlm_groups';

    $form_data = $_POST['formdatas'];

    parse_str($form_data, $form_array);

    $rfund_group = intval($form_array['rfund-group']);

    $rfund_note = sanitize_text_field($form_array['rfund-note']);

    $rfund_amount = intval($form_array['rfund-amount']);

    if (empty($rfund_group) || empty($rfund_note) || empty($rfund_amount)) {
        wp_send_json_error('Please fill in all fields');
        return;
    }

    $compare = dongtrader_compare_released_funds($rfund_group, $rfund_amount);

    if ($compare['compare']) {

        $data = array(
            'release_date' => date('Y-m-d H:i:s'),
            'release_amount' => $rfund_amount,
            'release_note' => $rfund_note,
            'group_id' => $rfund_group,
        );



        $result = $wpdb->insert($table_name, $data);

        // Check if data insertion was successful
        if ($result === false) {
            wp_send_json_error('Some error occured.Please Try again');
            return;
        }

        wp_send_json_success('Group profit has been released successfully');
    } else {

        wp_send_json_error('The released amount must not exceed ' . $compare['total_releaseable']);
        return;
    }
}


function dongtrader_patron_form($atts)
{

    // Attributes
    $atts = shortcode_atts(
        array(),
        $atts,
        'patron_form'
    );
    $error_message = ' <span class="error-message"></span>';
    ob_start(); // Start output buffering

    ?>
    <style>
        /* Basic form styling */
        #mega-patron-credentials {

            margin: 0 auto;
            padding: 50px 70px;

            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            border-radius: 5px;
            /* padding: 15px; */
            background-color: #f9f9f9;
        }

        .group-heading {
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .error-message::after {
            content: "\A";
            white-space: pre;
        }

        .error-message {
            color: #d9534f;
        }

        .error-message {
            color: #d9534f;
        }

        .server-message {
            color: #d9534f;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-password {
            position: relative;
            float: right;
            cursor: pointer;
            margin-right: 15px;
            margin-top: -40px;
            font-size: 15px;

        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            margin-top: 5px;
            margin-bottom: 15px;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .video-container video {
            width: 100%;
            max-width: 500px;
            height: auto;
        }


        @media (min-width: 991px) {
            .flex-container {
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .input-wrapper {
                flex: 0 0 49%;
            }
        }

        p.detente-mega-info {
            margin-top: 0;
        }

        p.detente-mega-info a {
            font-size: 14px;
        }

        .form-group.detente-poc-form-group {
            padding-top: 15px;
        }

        @media (max-width: 767px) {
            #mega-patron-credentials {
                padding: 40px;
            }

            .group-heading {
                text-align: left;
                font-size: 20px;
            }
        }
    </style>
    <form action="" method="post" id="mega-patron-credentials" autocomplete="off">
        <div class="form-group">
            <p class="group-heading"><b>Patron Complete Credentials</b></p>
            <div class="video-container">
                <video controls>
                    <source
                        src="https://www.smallstreet.app/wp-content/uploads/2025/06/invideo-ai-1080-Fronting-the-Future-with-Coach-Tom_-YAM-2025-05-31-1.mp4"
                        type="video/mp4">
                </video>
            </div>

            <p>
                Underlined links will require Patron applicants to submit these form fields with valid platform ID
                credentials or access. Individual accounts only.<b>MEGAvoters for organization and political fundraising as
                    industry, size of 1.</b>

            </p>
            <p>
                <b>All fields with * are mandatory.</b>
            </p>
            <div class="mega-notices"></div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="mega-email">Email*:</label>
                    <input type="text" id="mega-email" name="mega-email"
                        placeholder="<?php _e('Enter your email address', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="mega-name">User Name*:</label>
                    <input type="text" id="mega-name" name="mega-name"
                        placeholder="<?php _e('Enter your username', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
            </div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="user_pass">Password*:</label>
                    <input type="password" id="mega-password" name="mega-password" minlength="7"
                        placeholder="<?php _e('Enter your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="user_pass">Confirm Password*:</label>
                    <input type="password" id="mega-confirm-password" name="mega-confirm-password" minlength="7"
                        placeholder="<?php _e('Retype your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>

                </div>
            </div>
            <label for="mega-mobile">Mobile number*:</label>
            <input type="tel" id="mega-mobile" name="mega-mobile"
                placeholder="<?php _e('Enter your telephone number', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>

            <label for="mega-v-card">V Card*:</label>
            <input type="text" id="mega-v-card" name="mega-v-card"
                placeholder="<?php _e('Enter your V-card URL', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.qrcode-tiger.com/payment" target="_blank">QR Tiger v-card with
                    social media links (Free account)</a></p>


            <label for="mega-paypal">Paypal:</label>
            <input type="text" id="mega-paypal" name="mega-paypal"
                placeholder="<?php _e('Enter your paypal Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.paypal.com/us/welcome/signup/#/login_info"
                    target="_blank">PayPal with banking links</a></p>


            <label for="mega-venmo">Venmo:</label>
            <input type="text" id="mega-venmo" name="mega-venmo"
                placeholder="<?php _e('Enter your venmo Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://venmo.com/signup/" target="_blank">Venmo with banking links</a>
            </p>


            <!-- <label for="mega-glassfrog">Glassfrog Profile:</label>
            <input type="text" id="mega-glassfrog" name="mega-glassfrog"
                placeholder="<?php //_e('Enter your glassfrog user id', 'cpm-dongtrader'); ?>">
            <?php //echo $error_message ?>
            <p class="detente-mega-info"><a href="https://app.glassfrog.com/accounts/new" target="_blank">Glassfrog (Free
                    account)</a></p> -->


            <!-- <label for="mega-crowdsignal">Crowdsignal:</label>
            <input type="text" id="mega-crowdsignal" name="mega-crowdsignal"
                placeholder="<?php //_e('Enter your crowdsignal id', 'cpm-dongtrader'); ?>">
            <?php //echo $error_message ?>
            <p class="detente-mega-info"><a href="https://crowdsignal.com/pricing/" target="_blank">Crowdsignal (Free
                    account)</a></p> -->

        </div>
        <!-- Grouped Form: Patron Organizing Communities (POC) Leadership -->
        <!-- <div class="form-group detente-poc-form-group">
            <p class="group-heading"><b>Patron Organizing Communities (POC) Leadership</b></p>
            <label for="mega-precoro">Precoro.com:</label>
            <input type="text" id="mega-precoro" name="mega-precoro"
                placeholder="<?php //_e('Enter your precoro details', 'cpm-dongtrader'); ?>">
            <p class="detente-mega-info"><a href="https://precoro.com/get-a-trial" target="_blank">Precoro.com (Get a
                    Trial)</a></p>


            <label for="mega-amazon-business">Amazon Business:</label>
            <input type="text" id="mega-amazon-business" name="mega-amazon-business"
                placeholder="<?php //_e('Enter your amazon business id', 'cpm-dongtrader'); ?>">
            <p class="detente-mega-info"><a href="https://business.amazon.com/en/find-solutions/punchout"
                    target="_blank">Amazon Business (Punchout)</a></p>
        </div> -->
        <div class="form-group detente-submit-form-group">
            <input type="submit" value="Submit">
            <div id="loader" class="wp-admin-loading" style="display: none;">
                <img src="<?php echo admin_url('images/loading.gif') ?>" alt="loading">
            </div>
            <div class="server-message">
            </div>
        </div>

    </form>
    <?php do_action('after_pcc_registration_form'); ?>
    <script>
        jQuery(document).ready(function ($) {
            $(".toggle-password").click(function () {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $(this).parent().find("input");

                if (input.attr("type") == "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }

            });
            $('#mega-patron-credentials').submit(function (event) {
                event.preventDefault();

                var loader = $('#loader');
                var form = $('#mega-patron-credentials');
                loader.show();
                $(this).find('input').each(function () {
                    var inputId = $(this).attr('id');
                    $('#' + inputId).css('border-color', '');
                    $('#' + inputId).parent().find('span').empty();

                });
                var formData = $(this).serialize();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mega_credentials_save',
                        formdata: formData,
                    },
                    success: function (response) {
                        var parsed = JSON.parse(response);
                        if ('errorClient' in parsed && parsed['errorClient'].length > 0) {
                            parsed.errorClient.forEach(function (error) {
                                var $errorField = $('#' + error.field_name);
                                // console.log($errorField);
                                if ($errorField.length > 0) {
                                    $('html, body').animate({
                                        scrollTop: $errorField.offset().top - 100
                                    }, 1000);
                                    $errorField.focus();
                                    $errorField.css('border-color', '#d9534f');
                                    if (error.field_name.indexOf("password") != -1) {
                                        $errorField.parent().find('span').text(error.message);
                                    } else {
                                        $errorField.next('span').text(error.message);
                                    }

                                    console.log($errorField.parent().find('span'));


                                }
                            });

                        } else if ('errorServer' in parsed && parsed['errorServer'].length > 0) {
                            parsed.errorServer.forEach(function (error) {
                                var serverErrorfield = $('#server-message');
                                serverErrorfield.focus();
                                serverErrorfield.text(error.message);

                            });
                        } else if ('valid' in parsed && parsed['valid']) {
                            form.empty();
                            form.html('<p>Congratulations on successfully becoming a valued member of MEGAvoter! Kindly check your email for further details and exciting updates.</p>');
                        }

                        loader.hide();

                    },

                    error: function () {
                        console.error('Form submission failed');
                        loader.hide();

                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('patron_form', 'dongtrader_patron_form');



function dongtrader_user_registration_form($atts)
{

    // Attributes
    $atts = shortcode_atts(
        array(),
        $atts,
        'user_registration_form'
    );
    $error_message = ' <span class="error-message"></span>';
    ob_start(); // Start output buffering

    ?>
    <style>
        /* Basic form styling */
        #mega-patron-credentials {

            margin: 0 auto;
            padding: 50px 70px;

            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            border-radius: 5px;
            /* padding: 15px; */
            background-color: #f9f9f9;
        }

        .group-heading {
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .error-message::after {
            content: "\A";
            white-space: pre;
        }

        .error-message {
            color: #d9534f;
        }

        .error-message {
            color: #d9534f;
        }

        .server-message {
            color: #d9534f;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-password {
            position: relative;
            float: right;
            cursor: pointer;
            margin-right: 15px;
            margin-top: -40px;
            font-size: 15px;

        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            margin-top: 5px;
            margin-bottom: 15px;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .video-container video {
            width: 100%;
            max-width: 500px;
            height: auto;
        }

        @media (min-width: 991px) {
            .flex-container {
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .input-wrapper {
                flex: 0 0 49%;
            }
        }

        p.detente-mega-info {
            margin-top: 0;
        }

        p.detente-mega-info a {
            font-size: 14px;
        }

        .form-group.detente-poc-form-group {
            padding-top: 15px;
        }

        @media (max-width: 767px) {
            #mega-patron-credentials {
                padding: 40px;
            }

            .group-heading {
                text-align: left;
                font-size: 20px;
            }
        }

        /* Popup container */
        .popup {
            display: none;
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

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, .8);
            z-index: 999;
        }

        /* Button styles */
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
    <form action="" method="post" id="mega-patron-credentials" autocomplete="off">
        <div class="form-group">
            <p class="group-heading"><b>Complete YAM'ers Credentials</b></p>
            <div class="video-container">
                <video controls>
                    <source
                        src="https://www.smallstreet.app/wp-content/uploads/2025/06/invideo-ai-1080-Fronting-the-Future-with-Coach-Tom_-YAM-2025-05-31-1.mp4"
                        type="video/mp4">
                </video>
            </div>
            <p>
                Creating an NFT (non-fungible token) value is made possible by establishing a QRTiger v-card and
                PayPal/Venmo payment gateway for patronage and incentives allocations. Your email and mobile device lock in
                your digital identity and group status. To qualify as a buyer or YAM'er, please complete the following:</b>
            </p>
            <p>
                <b>All fields with * are mandatory.</b>
            </p>
            <div class="mega-notices"></div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="mega-email">Email*:</label>
                    <input type="text" id="mega-email" name="mega-email"
                        placeholder="<?php _e('Enter your email address', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="mega-name">User Name*:</label>
                    <input type="text" id="mega-name" name="mega-name"
                        placeholder="<?php _e('Enter your username', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
            </div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="user_pass">Password*:</label>
                    <input type="password" id="mega-password" name="mega-password" minlength="7"
                        placeholder="<?php _e('Enter your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="user_pass">Confirm Password*:</label>
                    <input type="password" id="mega-confirm-password" name="mega-confirm-password" minlength="7"
                        placeholder="<?php _e('Retype your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>

                </div>
            </div>
            <label for="mega-mobile">Mobile number*:</label>
            <input type="tel" id="mega-mobile" name="mega-mobile"
                placeholder="<?php _e('Enter your telephone number', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>

            <label for="mega-v-card">V Card*:</label>
            <input type="text" id="mega-v-card" name="mega-v-card"
                placeholder="<?php _e('Enter your V-card URL', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.qrcode-tiger.com/payment" target="_blank">QR Tiger v-card with
                    social media links (Free account)</a></p>


            <label for="mega-paypal">Paypal:</label>
            <input type="text" id="mega-paypal" name="mega-paypal"
                placeholder="<?php _e('Enter your paypal Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.paypal.com/us/welcome/signup/#/login_info"
                    target="_blank">PayPal with banking links</a></p>


            <label for="mega-venmo">Venmo:</label>
            <input type="text" id="mega-venmo" name="mega-venmo"
                placeholder="<?php _e('Enter your venmo Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://venmo.com/signup/" target="_blank">Venmo with banking links</a>
            </p>
            <input type="hidden" id="redirect" name="redirect" value="legacytoliveby" />

        </div>

        <div class="form-group detente-submit-form-group">
            <input type="submit" value="Submit">
            <div id="loader" class="wp-admin-loading" style="display: none;">
                <img src="<?php echo admin_url('images/loading.gif') ?>" alt="loading">
            </div>
            <div class="server-message">
            </div>
        </div>
    </form>
    <div class="overlay" id="popupOverlay"></div>
    <div class="popup" id="discordPopup">
        <h3>Join Our Discord Server!<br>Gracebook</h3>
        <p>Official Discord for the Legacy To Live By community, the Cookie Jar economy, and the YAM movement.</p>
        <a href="https://discord.gg/g5jreAPbra" target="_blank" class="popup-button">Join Now</a>
    </div>
    <?php do_action('after_pcc_registration_form'); ?>
    <script>
        jQuery(document).ready(function ($) {
            $('#popupOverlay').click(function () {
                $('#discordPopup').fadeOut();
                $('#popupOverlay').fadeOut();
            });

            $(".toggle-password").click(function () {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $(this).parent().find("input");

                if (input.attr("type") == "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }

            });
            $('#mega-patron-credentials').submit(function (event) {
                event.preventDefault();

                var loader = $('#loader');
                var form = $('#mega-patron-credentials');
                loader.show();
                $(this).find('input').each(function () {
                    var inputId = $(this).attr('id');
                    $('#' + inputId).css('border-color', '');
                    $('#' + inputId).parent().find('span').empty();

                });
                var formData = $(this).serialize();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mega_credentials_save',
                        formdata: formData,
                        redirect: 'legacytoliveby',
                    },
                    success: function (response) {



                        var parsed = JSON.parse(response);
                        if ('errorClient' in parsed && parsed['errorClient'].length > 0) {
                            parsed.errorClient.forEach(function (error) {
                                var $errorField = $('#' + error.field_name);
                                // console.log($errorField);
                                if ($errorField.length > 0) {
                                    $('html, body').animate({
                                        scrollTop: $errorField.offset().top - 100
                                    }, 1000);
                                    $errorField.focus();
                                    $errorField.css('border-color', '#d9534f');
                                    if (error.field_name.indexOf("password") != -1) {
                                        $errorField.parent().find('span').text(error.message);
                                    } else {
                                        $errorField.next('span').text(error.message);
                                    }

                                    console.log($errorField.parent().find('span'));


                                }
                            });

                        } else if ('errorServer' in parsed && parsed['errorServer'].length > 0) {
                            parsed.errorServer.forEach(function (error) {
                                var serverErrorfield = $('#server-message');
                                serverErrorfield.focus();
                                serverErrorfield.text(error.message);

                            });
                        } else if ('valid' in parsed && parsed['valid']) {
                            form.empty();
                            form.html('<p>Congratulations on successfully becoming a valued member of MEGAvoter! Kindly check your email for further details and exciting updates.</p>');
                        }

                        loader.hide();

                        setTimeout(function () {
                            $('#discordPopup').fadeIn();
                            $('#popupOverlay').fadeIn();
                        }, 3000);
                    },

                    error: function () {
                        console.error('Form submission failed');
                        loader.hide();

                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('user_registration_form', 'dongtrader_user_registration_form');


add_action('wp_ajax_mega_credentials_save', 'mega_credentials_save');

function mega_credentials_save()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mega_credentials_save')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    if (!isset($_POST['formdata']))
        return;

    parse_str($_POST['formdata'], $form_data_array);

    $sanitization_mapping = array(
        'mega_email' => 'sanitize_email',
        'mega_name' => 'sanitize_text_field',
        'mega_password' => 'sanitize_text_field',
        'mega-confirm-password' => 'sanitize_text_field',
        'mega_mobile' => 'sanitize_text_field',
        'mega_v-card' => 'sanitize_text_field',
        'mega_paypal' => 'sanitize_text_field',
        'mega_venmo' => 'sanitize_text_field',
        'redirect' => 'sanitize_text_field',
        'mega_glassfrog' => 'sanitize_text_field',
        'mega_crowdsignal' => 'sanitize_text_field',
        'mega_precoro' => 'sanitize_text_field',
        'mega_amazon-business' => 'sanitize_text_field'
    );

    $sanitized_data = array();

    foreach ($form_data_array as $field => $value) {

        if (isset($sanitization_mapping[$field]) && function_exists($sanitization_mapping[$field])) {
            $sanitized_data[$field] = call_user_func($sanitization_mapping[$field], $value);
        } else {
            $sanitized_data[$field] = $value;
        }
    }

    $client_validation_check = mega_validation_check($sanitized_data);

    if (empty($client_validation_check)) {
        $server_validation_check = [];
        $new_user_id = wp_create_user($sanitized_data['mega-name'], $sanitized_data['mega-password'], $sanitized_data['mega-email']);

        if (!is_wp_error($new_user_id)) {

            $u_data = get_userdata($new_user_id);

            $new_meta_array = array_slice($sanitized_data, 4, null, true);

            update_user_meta($new_user_id, 'patron_details', $new_meta_array);

            if ($u_data) {
                $name = $u_data->user_login;
                $to = $u_data->user_email;
                $subject = isset($form_data_array) && $form_data_array['redirect'] == 'legacytoliveby' ? __('🐳 The Whale Never Saw You Coming – Welcome, Organized Krill.') : __('Registration Confirmation', 'cpm-dongtrader');
                $tem_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'content-email-welcome.php';

                if (isset($form_data_array) && $form_data_array['redirect'] == 'legacytoliveby') {
                    $tem_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'content-email-welcome-legacytoliveby.php';
                }
                ob_start();
                if (file_exists($tem_path))
                    load_template($tem_path, true, ['username' => $name]);
                $message = ob_get_clean();
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($to, $subject, $message, $headers);
                //display the popup after the mail is sent while registration.


            }
            if (class_exists('PMPro_Membership_Level')) {

                $current_level = pmpro_getMembershipLevelForUser($new_user_id);
                $level_id = 16;

                // If the user doesn't have the desired level, update it.
                if ($current_level->ID != $level_id) {
                    // Update membership level.
                    pmpro_changeMembershipLevel($level_id, $new_user_id);
                }
            }
        } else {

            $server_validation_check[] = [
                'valid' => 'false',
                'message' => __('User Cannot be created', 'cpm-dongtrader')

            ];
        }
    }

    $validity = !empty($client_validation_check)
        ? ['errorClient' => $client_validation_check]
        : (!empty($server_validation_check)
            ? ['errorServer' => $server_validation_check]
            : ['valid' => true]);

    echo wp_json_encode($validity);

    wp_die();
}





function mega_validation_check($sanitized_data)
{
    $validation_check = [];

    foreach ($sanitized_data as $attr => $sv) {

        if ($attr == "mega-email") {
            if (email_exists($sv)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("This email address is already registered", "cpm-dongtrader")
                ];
                break;
            }
            if (!filter_var($sv, FILTER_VALIDATE_EMAIL)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Please Enter Valid Email Address", "cpm-dongtrader")
                ];
                break;
            }
        }

        if ($attr == "mega-name") {

            if (username_exists($sv)) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Username already exists", "cpm-dongtrader")
                ];

                break;
            }

            if (!preg_match('/^.{5,}$/', $sv)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Username must be at least 5 characters long.", "cpm-dongtrader")
                ];

                break;
            }
        }

        if ($attr == "mega-password") {
            if (!preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{7,}$/', $sv)) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Password must be at least 7 characters long and include at least one uppercase letter and one special character.", "cpm-dongtrader")
                ];
                break;
            }
        }

        if ($attr = "mega-confirm-password") {

            if ($sanitized_data['mega-password'] != $sanitized_data['mega-confirm-password']) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Passwords doesnt match ", "cpm-dongtrader")
                ];
                break;
            }
        }
    }

    return $validation_check;
}

// Add a custom text area field to user profiles
function add_custom_user_profile_field($user)
{


    // $metas =  get_user_meta($user->ID, 'patron_details', true);
    $metas = [
        'mega-mobile' => '',
        'mega-v-card' => '',
        'mega-paypal' => '',
        'mega-venmo' => '',
        'mega-glassfrog' => '',
        'mega-crowdsignal' => '',
        'mega-precoro' => '',
        'mega-amazon-business' => '',
    ];
    $metas = array_keys($metas);
    $can_user_pay = get_user_meta($user->ID, 'can_pay', true);
    $can_user_pay = $can_user_pay == '1' ? true : false;
    ?>

    <h3>
        <?php _e('Extra User Details', 'cpm-dongtrader'); ?>
    </h3>
    <table class="form-table" role="presentation">
        <tbody>
            <?php
            foreach ($metas as $k) {

                $label_text = str_replace("mega-", "", $k);
                $capital_label = ucwords($label_text);

                if (str_contains($label_text, 'password'))
                    continue;

                $curr_v = get_user_meta($user->ID, $k, true);

                ?>
                <tr class="user-user-login-wrap">
                    <th><label for="<?= $k ?>">
                            <?= $capital_label ?>
                        </label></th>
                    <td><input type="text" name="<?= $k ?>" id="<?= $k ?>" value="<?= $curr_v ?>" class="regular-text"></td>
                </tr>
            <?php } ?>
            <tr class="user-user-login-wrap">
                <th><label for="can_pay">Can user pay for orders?</label></th>
                <td>
                    <select name="can_pay" id="can_pay">
                        <option value="1" <?php echo $can_user_pay ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?php echo !$can_user_pay ? 'selected' : '' ?>>No</option>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_profile_field', 100);
add_action('edit_user_profile', 'add_custom_user_profile_field', 100);

// Save the custom field data
function save_custom_user_profile_field($user_id)
{

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    // $metas =  get_user_meta($user_id, 'patron_details', true);
    $metas = [
        'mega-mobile' => '',
        'mega-v-card' => '',
        'mega-paypal' => '',
        'mega-venmo' => '',
        'mega-glassfrog' => '',
        'mega-crowdsignal' => '',
        'mega-precoro' => '',
        'mega-amazon-business' => '',
        'can_pay' => ''
    ];
    if (is_array($metas)) {
        $array_keys = array_keys($metas);

        if (empty($array_keys))
            return;
        foreach ($array_keys as $k) {
            if (str_contains($k, 'password'))
                continue;
            update_user_meta($user_id, $k, sanitize_textarea_field($_POST[$k]));
        }
    }
}
add_action('personal_options_update', 'save_custom_user_profile_field');
add_action('edit_user_profile_update', 'save_custom_user_profile_field');

























function custom_proof_of_delivery_form()
{

    $vals = get_option('dongtraders_api_settings_fields');
    $subs = array($vals['monthly_subscription_product'], $vals['annual_subscription_product']);


    ob_start();
    ?>
    <style>
        .delivery-form {
            max-width: 350px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.6);
            padding: 20px 30px;
        }
    </style>
    <div class="delivery-form">
        <p>
        <h3>Please read below before answering</h3>
        </p>
        <form method="POST" action="" id="deliveryproof">
            <input type="hidden" name="delivery_form" value="true">

            <p>
            <h4>Is this proof of delivery?</h4>
            </p>
            <label for="yes">Yes:</label>
            <input type="radio" name="delivery_proof" value="yes" required>
            <label for="no">No:</label>
            <input type="radio" name="delivery_proof" value="no" required>

            <p>
            <h4>Have you taken the (4) Detente 2.0 surveys?</h4>
            </p>
            <label for="survey_yes">Yes:</label>
            <input type="radio" name="survey_taken" value="yes" required>
            <label for="survey_no">No:</label>
            <input type="radio" name="survey_taken" value="no" required>

            <div class='buyer-seller' style="display:none">
                <label for="buyer_seller">Buyer or Seller :</label>
                <select name="buyer_seller" id="buyerseller">
                    <option value="">Select</option>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
            </div>
            <div class='subscription-product' style="display:none">
                <label for="subs-product">Select Subscription:</label>
                <select name="subs-product" id="subs-product">
                    <option value="">Select Subscription</option>
                    <?php foreach ($subs as $s) { ?>
                        <option value="<?= $s ?>">
                            <?php echo get_the_title($s); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="variation-products" style="display:none">
                <label for="var-product">Select Sectors:</label>
                <select name="vars-product" id="vars-product">
                </select>
            </div>
            <br />
            <input id="test-submit" type="submit" value="Submit">
        </form>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            $('#deliveryproof').on('change', function (e) {
                // e.preventDefault();
                var dpval = $("input[name='delivery_proof']:checked").val();
                var spval = $("input[name='survey_taken']:checked").val();
                if (dpval == 'yes' && spval == 'yes') {
                    $('.buyer-seller').show();
                    $('.buyer-seller>select#buyerseller').prop('required', true);
                    var bsdropdownval = $('#buyerseller').val();
                    if (bsdropdownval === 'buyer') {
                        $('.subscription-product').show();
                        $('.subscription-product>select#subs-product').prop('required', true);



                    } else {
                        $('.subscription-product').hide();
                        $('.variation-products').hide();
                        $('.subscription-product>select#subs-product').prop('required', false);
                        $('.variation-products>select#vars-product').prop('required', false);
                    }
                } else {
                    $('.buyer-seller').hide();
                    $('.buyer-seller>select#buyerseller').prop('required', false);

                    $('.subscription-product').hide();
                    $('.subscription-product>select#subs-product').prop('required', false);
                    $(".buyer-seller>select#buyerseller").prop('selectedIndex', 0);
                    $(".subscription-product>select#subs-product").prop('selectedIndex', 0);

                    $('.variation-products').hide();
                    $('.variation-products>select#vars-product').prop('required', false);
                    $('.variation-products>select#vars-product').prop('selectedIndex', 0);

                }
            });
            $(document).on('change', '#subs-product', function () {
                var productid = $(this).find(":selected").val();
                var selector = $('#vars-product');
                var varselcontainer = $('.variation-products');
                if (productid) {
                    $('.variation-products>select#vars-product').prop('required', true);
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'mega_get_variations',
                            proid: productid,
                        },
                        success: function (response) {
                            selector.empty();
                            varselcontainer.show();
                            selector.append(response);
                        },
                    });

                } else {

                    $('.variation-products').hide();
                    $('.variation-products>select#vars-product').prop('required', false);
                }


            });

        });
    </script>

    <?php
    return ob_get_clean();
}




add_shortcode('delivery_form', 'custom_proof_of_delivery_form');

add_action('init', 'process_custom_form');
function process_custom_form()
{
    if (isset($_POST['delivery_form'])) {

        $options = get_option('dongtraders_api_settings_fields');
        $chase_page_id = $options['chase_page_selector'];
        $patron_page_id = $options['patron_page_selector'];
        $delivery_proof = isset($_POST['delivery_proof']) ? sanitize_text_field($_POST['delivery_proof']) : false;
        $survey_taken = isset($_POST['survey_taken']) ? sanitize_text_field($_POST['survey_taken']) : false;

        $redirect_url = '';
        if ($delivery_proof != false && $survey_taken != false) {

            if ($delivery_proof === 'yes' && $survey_taken === 'yes') {

                if ($_POST['buyer_seller'] == 'seller') {

                    $redirect_url = get_the_permalink($patron_page_id);
                } elseif ($_POST['buyer_seller'] == 'buyer') {


                    $product_id = isset($_POST['subs-product']) ? $_POST['subs-product'] : false;
                    $variation_id = isset($_POST['vars-product']) ? $_POST['vars-product'] : false;

                    if ($variation_id) {
                        $get_url = get_permalink($variation_id);

                        $modfied_url = $get_url . '&varid=' . $variation_id;

                        $redirect_url = $modfied_url;
                    } else {

                        $get_url = get_permalink($product_id);
                        $modfied_url = $get_url . '&add=1';
                        $redirect_url = $modfied_url;
                    }
                } else {
                    $redirect_url = home_url();
                }
            } elseif ($delivery_proof === 'no' && $survey_taken === 'no') {

                $redirect_url = get_the_permalink($chase_page_id);
            } elseif ($delivery_proof === 'yes' && $survey_taken === 'no') {

                $redirect_url = get_the_permalink($chase_page_id);
            } elseif ($delivery_proof === 'no' && $survey_taken === 'yes') {

                $redirect_url = home_url();
            }
            wp_redirect($redirect_url);
            exit();
        }
    }
}
add_action('wp_ajax_mega_get_variations', 'mega_get_variations');
function mega_get_variations()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mega_get_variations')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $postid = sanitize_text_field($_POST['proid']);
    $product = wc_get_product($postid);
    $html = "<option value= ''>Select Sectors</option>";
    if ($product->is_type('variable')) {
        $current_products = $product->get_children();
        foreach ($current_products as $current_product) {
            $variation = wc_get_product($current_product);
            $varition_name = $variation->get_name();
            $html .= '<option value="' . $current_product . '">' . $varition_name . '</option>';
        }
    }

    echo $html;
    wp_die();
}


function custom_login_form_toggle_content()
{
    // Add your custom content here
    echo '<p>Welcome back! If you have an account, please login.</p>';
}
add_action('woocommerce-form-login-toggle', 'custom_login_form_toggle_content', 999);


function action_woocommerce_admin_order_item_headers($order)
{
    // Set the column name
    $column_name = __('Packing Weight', 'woocommerce');

    // Display the column name
    echo '<th class="line_packing_weight sortable" data-sort="string-ins">' . $column_name . '</th>';
}
add_action('woocommerce_admin_order_item_headers', 'action_woocommerce_admin_order_item_headers', 10, 1);


// closing popup on button click

add_action('wp_footer', function () {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery(document).on("click", "#close-popup", function () {
                elementorProFrontend.modules.popup.closePopup({}, event);
            });
        });
    </script>
    <?php
});


add_action('wp_footer', function () {

    if (wp_is_mobile() && !is_page('login')):
        ?>
        <script>
            jQuery(document).ready(function () {
                jQuery(document).find('body').removeClass('pmpro-login-page')
            });
        </script>
        <?php
    endif;
});

/**
 * Display XP balance and transaction history for users
 */

function dongtrader_display_xp_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to view your XP dashboard.</p>';
    }
    
    $user_id = get_current_user_id();
    
    $dong_user_role = get_user_meta($user_id, 'dong_user_role', true);
    $is_seller = in_array($dong_user_role, array('Planning', 'Budget', 'Media', 'Distribution', 'Membership'));
    
    // Get seller and Discord details to calculate total XP
    $seller_details = get_user_meta($user_id, '_seller_details', true);
    $discord_details = get_user_meta($user_id, '_discord_details', true);
    $talentshow_entry = get_user_meta($user_id, '_talentshow_entry', false);
    // Get buyer details from usermeta for XP calculation
    $buyer_details = get_user_meta($user_id, '_buyer_details', true);
    // Log for debugging
    error_log("=== FETCHING BUYER DETAILS ===");
    error_log("Buyer details type: " . gettype($buyer_details));
    error_log("Buyer details is array: " . (is_array($buyer_details) ? 'yes' : 'no'));
    error_log("Buyer details count: " . (is_array($buyer_details) ? count($buyer_details) : '0'));
    error_log("Buyer details content: " . print_r($buyer_details, true));
    
    // Initialize XP counters with detailed breakdown
    $total_earned_xp = 0;
    $total_pending_xp = 0;
    $total_completed_xp = 0;
    
    // Separate XP counters for different sources
    $seller_xp_earned = 0;
    $seller_xp_pending = 0;
    $seller_xp_completed = 0;
    $buyer_xp_earned = 0;
    $buyer_xp_pending = 0;
    $buyer_xp_completed = 0;
    $discord_xp_earned = 0;
    $discord_xp_pending = 0;
    $discord_xp_completed = 0;
    $discord_details_xp_earned = 0;
    $discord_details_xp_pending = 0;
    $discord_details_xp_completed = 0;
    
    // Calculate XP from seller transactions (scanning/OTP verification)
    if (is_array($seller_details) && !empty($seller_details)) {
        foreach ($seller_details as $transaction) {
            if (isset($transaction['xp_awarded'])) {
                $xp_amount = intval($transaction['xp_awarded']);
                $seller_xp_earned += $xp_amount;
                $total_earned_xp += $xp_amount;
                
                // Check if XP is pending or completed based on Discord membership
                if (isset($transaction['discord_member']) && $transaction['discord_member']) {
                    $seller_xp_completed += $xp_amount;
                    $total_completed_xp += $xp_amount;
                } else {
                    $seller_xp_pending += $xp_amount;
                    $total_pending_xp += $xp_amount;
                }
            }
        }
    }
    
    // Calculate XP from buyer transactions (_buyer_details usermeta)
    if (is_array($buyer_details) && !empty($buyer_details)) {
        foreach ($buyer_details as $transaction) {
            $xp_amount = isset($transaction['xp_awarded']) ? intval($transaction['xp_awarded']) : 0;
            
            if ($xp_amount > 0) {
                $buyer_xp_earned += $xp_amount;
                $total_earned_xp += $xp_amount;
                
                // Check Discord membership status for this transaction
                $is_discord_member = get_user_meta($user_id, 'discord_user_id', true) ? true : false;
                
                if ($is_discord_member) {
                    $buyer_xp_completed += $xp_amount;
                    $total_completed_xp += $xp_amount;
                } else {
                    $buyer_xp_pending += $xp_amount;
                    $total_pending_xp += $xp_amount;
                }
            }
        }
    }
    
    // Calculate XP from Discord invite data
    $discord_invite_data = get_user_meta($user_id, '_discord_invite', true);
    $has_discord_invite = false;
    
    // Handle both JSON string and array formats
    if (!empty($discord_invite_data)) {
        // If it's a JSON string, decode it
        if (is_string($discord_invite_data)) {
            $decoded_data = json_decode($discord_invite_data, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                // Single entry format
                $discord_invite_data = array($decoded_data);
                $has_discord_invite = true;
            } else {
                $discord_invite_data = array();
            }
        } else {
            $has_discord_invite = true;
        }
        
        // Process the data
        if (is_array($discord_invite_data) && !empty($discord_invite_data)) {
            foreach ($discord_invite_data as $invite_entry) {
                if (isset($invite_entry['xp_awarded'])) {
                    $xp_amount = intval($invite_entry['xp_awarded']);
                    $discord_xp_earned += $xp_amount;
                    $total_earned_xp += $xp_amount;
                    
                    // If Discord invite exists, all XP is automatically released
                    $discord_xp_completed += $xp_amount;
                    $total_completed_xp += $xp_amount;
                }
            }
        }
    }
    
    // Calculate XP from Discord details (additional Discord-related activities)
    if (is_array($discord_details) && !empty($discord_details)) {
        foreach ($discord_details as $discord_activity) {
            if (isset($discord_activity['xp_awarded'])) {
                $xp_amount = intval($discord_activity['xp_awarded']);
                $discord_details_xp_earned += $xp_amount;
                $total_earned_xp += $xp_amount;
                
                // Check if XP is pending or completed
                $is_discord_member = isset($discord_activity['discord_member']) && $discord_activity['discord_member'];
                if ($is_discord_member) {
                    $discord_details_xp_completed += $xp_amount;
                    $total_completed_xp += $xp_amount;
                } else {
                    $discord_details_xp_pending += $xp_amount;
                    $total_pending_xp += $xp_amount;
                }
            }
        }
    }
    
    // Calculate XP from Talent Show entries
    $talentshow_xp_earned = 0;
    $talentshow_xp_pending = 0;
    $talentshow_xp_completed = 0;
    
    if (!empty($talentshow_entry)) {
        // Handle multiple talent show entries (array of JSON strings)
        $processed_entries = array();
        
        if (is_array($talentshow_entry)) {
            // Multiple entries from database
            foreach ($talentshow_entry as $entry_string) {
                if (is_string($entry_string)) {
                    $decoded_data = json_decode($entry_string, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                        $processed_entries[] = $decoded_data;
                    }
                }
            }
        } else if (is_string($talentshow_entry)) {
            // Single entry (fallback)
            $decoded_data = json_decode($talentshow_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                $processed_entries[] = $decoded_data;
            }
        }
        
        // Process the decoded entries
        foreach ($processed_entries as $talent_entry) {
            if (isset($talent_entry['xp_awarded'])) {
                $xp_amount = intval($talent_entry['xp_awarded']);
                $talentshow_xp_earned += $xp_amount;
                $total_earned_xp += $xp_amount;
                
                // Check if XP is pending or completed based on Discord membership
                $is_discord_member = get_user_meta($user_id, 'discord_user_id', true) ? true : false;
                if ($is_discord_member) {
                    $talentshow_xp_completed += $xp_amount;
                    $total_completed_xp += $xp_amount;
                } else {
                    $talentshow_xp_pending += $xp_amount;
                    $total_pending_xp += $xp_amount;
                }
            }
        }
    }
    
    // Calculate XP from Discord poll data
    $discord_poll_xp_earned = 0;
    $discord_poll_xp_pending = 0;
    $discord_poll_xp_completed = 0;
    
    $discord_poll_data = get_user_meta($user_id, '_discord_poll', false);
    if (!empty($discord_poll_data)) {
        // Handle multiple Discord poll entries (array of JSON strings)
        $processed_entries = array();
        
        if (is_array($discord_poll_data)) {
            // Multiple entries from database
            foreach ($discord_poll_data as $entry_string) {
                if (is_string($entry_string)) {
                    $decoded_data = json_decode($entry_string, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                        $processed_entries[] = $decoded_data;
                    }
                }
            }
        } else if (is_string($discord_poll_data)) {
            // Single entry (fallback)
            $decoded_data = json_decode($discord_poll_data, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                $processed_entries[] = $decoded_data;
            }
        }
        
        $discord_poll_data = $processed_entries;
        
        // Process the data
        if (is_array($discord_poll_data) && !empty($discord_poll_data)) {
            foreach ($discord_poll_data as $poll_entry) {
                if (isset($poll_entry['xp_awarded'])) {
                    $xp_amount = intval($poll_entry['xp_awarded']);
                    $discord_poll_xp_earned += $xp_amount;
                    $total_earned_xp += $xp_amount;
                    
                    // Check if XP is pending or completed based on Discord membership
                    $is_discord_member = get_user_meta($user_id, 'discord_user_id', true) ? true : false;
                    if ($is_discord_member) {
                        $discord_poll_xp_completed += $xp_amount;
                        $total_completed_xp += $xp_amount;
                    } else {
                        $discord_poll_xp_pending += $xp_amount;
                        $total_pending_xp += $xp_amount;
                    }
                }
            }
        }
    }
    
    // If Discord invite is available, release all pending XP from other sources
    if ($has_discord_invite) {
        // Move all pending XP to completed
        $total_completed_xp += $total_pending_xp;
        $total_pending_xp = 0;
        
        // Update individual counters
        $seller_xp_completed += $seller_xp_pending;
        $seller_xp_pending = 0;
        $buyer_xp_completed += $buyer_xp_pending;
        $buyer_xp_pending = 0;
        $discord_details_xp_completed += $discord_details_xp_pending;
        $discord_details_xp_pending = 0;
        $talentshow_xp_completed += $talentshow_xp_pending;
        $talentshow_xp_pending = 0;
        $discord_poll_xp_completed += $discord_poll_xp_pending;
        $discord_poll_xp_pending = 0;
    }
    
    $output = '<div class="dongtrader-xp-dashboard" style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;">';
    $output .= '<h3 style="color: #2c3e50; margin-bottom: 20px;">🎮 XP Dashboard</h3>';
    
    // Debug: Show AJAX URL
    $ajax_url = admin_url('admin-ajax.php');
    $user_id = get_current_user_id();
    $nonce = wp_create_nonce('get_xp_umeta_ids');
    
    // Leaderboard Display Section
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #e67e22;">';
    $output .= '<h4 style="color: #2c3e50; margin-top: 0;">🏆 Leaderboard Display</h4>';
    
    // Create leaderboard table
    $output .= '<div style="overflow-x: auto; margin-top: 15px;">';
    $output .= '<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; background: white;">';
    
    // Table header
    $output .= '<tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
    $output .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: left; font-weight: 600; color: #495057;">Order Details</th>';
    $output .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600; color: #495057;">XP Awarded</th>';
    $output .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600; color: #495057;">Status</th>';
    $output .= '<th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: 600; color: #495057;">Maturity Status</th>';
    $output .= '</tr>';
    
    // Display seller transactions (scanning/OTP)
    if (is_array($seller_details) && !empty($seller_details)) {
        foreach ($seller_details as $index => $transaction) {
            $xp_amount = isset($transaction['xp_awarded']) ? intval($transaction['xp_awarded']) : 0;
            
            // Skip transactions with no XP awarded
            if ($xp_amount <= 0) {
                continue;
            }
            
            $referral_xp = 0; // Placeholder for Discord invite XP
            $funding_xp = 0; // Placeholder for funding XP
            $total_xp = $xp_amount + $referral_xp + $funding_xp;
            
            // Check XP status - first check the transaction's actual status field
            $txn_status = isset($transaction['status']) ? $transaction['status'] : 'none';
            
            // If transaction has been marked for redemption, show that
            if ($txn_status === 'requested') {
                $status_text = 'Requested';
                $status_color = '#6f42c1';
            } elseif ($txn_status === 'redeemed') {
                $status_text = 'Redeemed';
                $status_color = '#28a745';
            } elseif ($txn_status === 'released') {
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($txn_status === 'completed') {
                // Legacy status for backward compatibility
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($txn_status === 'processing') {
                $status_text = 'Processing';
                $status_color = '#007cba';
            } else {
                // Fallback to old logic for backwards compatibility
                $is_discord_member = isset($transaction['discord_member']) && $transaction['discord_member'];
                if ($has_discord_invite) {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } else {
                    $status_text = $is_discord_member ? 'Completed' : 'Pending';
                    $status_color = $is_discord_member ? '#28a745' : '#ffc107';
                }
            }
            
            // Format order details for seller transactions
            $order_details = 'Scanning/OTP Verification';
            if (isset($transaction['transaction_type'])) {
                $order_details = ucfirst($transaction['transaction_type']);
            }
            if (isset($transaction['verification_date'])) {
                $order_details .= ' - ' . $transaction['verification_date'];
            }
            
            // Calculate maturity status for seller transaction
            $delivery_date = dongtrader_get_delivery_date_from_xp_entry($transaction);
            $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
            $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
            $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
            
            // Format maturity status display with debug info
            $maturity_status = '';
            $maturity_color = '#6c757d';
            $debug_info = '';
            
            if ($is_mature) {
                $maturity_status = '✅ Mature';
                $maturity_color = '#28a745';
                $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
            } elseif ($days_until_maturity !== null) {
                if ($days_until_maturity > 0) {
                    $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                    $maturity_color = '#ffc107';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                } else {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                }
            } else {
                $maturity_status = '❓ Unknown';
                $maturity_color = '#6c757d';
                // Show debug info for unknown status
                $available_fields = is_array($transaction) ? array_keys($transaction) : array();
                $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
            }
            
            $meta_id = 'seller_' . $index . '_' . $transaction['order_id'];
            $output .= '<tr style="background: #e8f5e8; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #2e7d32;">' . esc_html($order_details) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #2e7d32; font-weight: 600;">' . number_format($total_xp) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
            $output .= '</tr>';
        }
    }
    
    // Display buyer transactions from _buyer_details usermeta (not direct WooCommerce orders)
    $buyer_details = get_user_meta($user_id, '_buyer_details', true);
    
    error_log("=== DISPLAY BUYER DETAILS ===");
    error_log("Buyer details fetched: " . (is_array($buyer_details) ? 'yes' : 'no'));
    error_log("Buyer details empty: " . (empty($buyer_details) ? 'yes' : 'no'));
    error_log("Buyer details type: " . gettype($buyer_details));
    
    if (!empty($buyer_details)) {
        error_log("Buyer details content (first 500 chars): " . substr(print_r($buyer_details, true), 0, 500));
    }
    
    // Handle different data formats for _buyer_details
    // Could be: array of transactions, or serialized string, or JSON string
    $buyer_details_array = array();
    
    if (is_array($buyer_details)) {
        // Already an array
        $buyer_details_array = $buyer_details;
        error_log("Buyer details is already an array with " . count($buyer_details_array) . " items");
    } elseif (is_string($buyer_details)) {
        // Try to decode if it's a JSON string or serialized
        $decoded = json_decode($buyer_details, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $buyer_details_array = $decoded;
            error_log("Buyer details was JSON string, decoded to " . count($buyer_details_array) . " items");
        } else {
            // Try unserialize
            $decoded = @unserialize($buyer_details);
            if ($decoded !== false && is_array($decoded)) {
                $buyer_details_array = $decoded;
                error_log("Buyer details was serialized, unserialized to " . count($buyer_details_array) . " items");
            }
        }
    }
    
    if (!empty($buyer_details_array)) {
        error_log("Processing " . count($buyer_details_array) . " buyer transactions");
        
        foreach ($buyer_details_array as $index => $transaction) {
            $xp_amount = isset($transaction['xp_awarded']) ? intval($transaction['xp_awarded']) : 0;
            
            error_log("Buyer transaction $index: XP = $xp_amount");
            
            // Skip transactions with no XP awarded
            if ($xp_amount <= 0) {
                error_log("  Skipping transaction $index - no XP awarded");
                continue;
            }
            
            $referral_xp = 0; // Placeholder for Discord invite XP
            $funding_xp = 0; // Placeholder for funding XP
            $total_xp = $xp_amount + $referral_xp + $funding_xp;
            
            // Check XP status - first check the transaction's actual status field
            $txn_status = isset($transaction['status']) ? $transaction['status'] : 'none';
            
            if ($txn_status === 'requested') {
                $status_text = 'Requested';
                $status_color = '#6f42c1';
            } elseif ($txn_status === 'redeemed') {
                $status_text = 'Redeemed';
                $status_color = '#28a745';
            } elseif ($txn_status === 'released') {
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($txn_status === 'completed') {
                // Legacy status for backward compatibility
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($txn_status === 'processing') {
                $status_text = 'Processing';
                $status_color = '#007cba';
            } else {
                // Fallback to old logic
            $is_discord_member = get_user_meta($user_id, 'discord_user_id', true) ? true : false;
            if ($has_discord_invite) {
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } else {
                $status_text = $is_discord_member ? 'Completed' : 'Pending';
                $status_color = $is_discord_member ? '#28a745' : '#ffc107';
                }
            }
            
            // Format order details from _buyer_details transaction
            $order_id = isset($transaction['order_id']) ? $transaction['order_id'] : 'N/A';
            $membership_name = isset($transaction['membership']) ? $transaction['membership'] : '';
            
            $order_details = '';
            if (!empty($membership_name)) {
                $order_details = $membership_name . ' Membership';
            } else {
                $order_details = 'Order #' . $order_id;
            }
            $order_details .= ' (Order #' . $order_id . ') - Stored Transaction';
            
            // Calculate maturity status for buyer transaction
            $delivery_date = dongtrader_get_delivery_date_from_xp_entry($transaction);
            $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
            $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
            $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
            
            // Format maturity status display with debug info
            $maturity_status = '';
            $maturity_color = '#6c757d';
            $debug_info = '';
            
            if ($is_mature) {
                $maturity_status = '✅ Mature';
                $maturity_color = '#28a745';
                $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
            } elseif ($days_until_maturity !== null) {
                if ($days_until_maturity > 0) {
                    $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                    $maturity_color = '#ffc107';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                } else {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                }
            } else {
                $maturity_status = '❓ Unknown';
                $maturity_color = '#6c757d';
                // Show debug info for unknown status
                $available_fields = is_array($transaction) ? array_keys($transaction) : array();
                $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
            }
            
            $meta_id = 'buyer_' . $index . '_' . $order_id;
            $output .= '<tr style="background: #f0f8ff; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #1e3a8a;">' . esc_html($order_details) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #1e3a8a; font-weight: 600;">' . number_format($total_xp) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
            $output .= '</tr>';
        }
    }
    
    
    // Display Discord invite transactions
    $discord_invite_data_for_display = get_user_meta($user_id, '_discord_invite', true);
    if (!empty($discord_invite_data_for_display)) {
        // Handle both JSON string and array formats for display
        if (is_string($discord_invite_data_for_display)) {
            $decoded_data = json_decode($discord_invite_data_for_display, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                $discord_invite_data_for_display = array($decoded_data);
            } else {
                $discord_invite_data_for_display = array();
            }
        }
        
        if (is_array($discord_invite_data_for_display) && !empty($discord_invite_data_for_display)) {
            foreach ($discord_invite_data_for_display as $index => $invite_entry) {
                $xp_amount = isset($invite_entry['xp_awarded']) ? intval($invite_entry['xp_awarded']) : 0;
                
                // Check actual status field
                $invite_status = isset($invite_entry['status']) ? $invite_entry['status'] : 'completed';
                
                if ($invite_status === 'requested') {
                    $status_text = 'Requested';
                    $status_color = '#6f42c1';
                } elseif ($invite_status === 'redeemed') {
                    $status_text = 'Redeemed';
                    $status_color = '#28a745';
                } elseif ($invite_status === 'released') {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($invite_status === 'completed') {
                    // Legacy status for backward compatibility
                $status_text = 'Released';
                $status_color = '#17a2b8';
                } elseif ($invite_status === 'processing') {
                    $status_text = 'Processing';
                    $status_color = '#007cba';
                } else {
                    $status_text = 'Released'; // Discord invites are completed by default
                    $status_color = '#17a2b8';
                }
                
                // Format order details for Discord invite
                $order_details = 'Discord Join';
                if (isset($invite_entry['discord_username']) && !empty($invite_entry['discord_username'])) {
                    $order_details .= ' - @' . $invite_entry['discord_username'];
                }
                if (isset($invite_entry['verification_date'])) {
                    $order_details .= ' - ' . $invite_entry['verification_date'];
                }
                
                // Calculate maturity status for Discord invite transaction
                $delivery_date = dongtrader_get_delivery_date_from_xp_entry($invite_entry);
                $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
                $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
                $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
                
                // Format maturity status display with debug info
                $maturity_status = '';
                $maturity_color = '#6c757d';
                $debug_info = '';
                
                if ($is_mature) {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                } elseif ($days_until_maturity !== null) {
                    if ($days_until_maturity > 0) {
                        $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                        $maturity_color = '#ffc107';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                    } else {
                        $maturity_status = '✅ Mature';
                        $maturity_color = '#28a745';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                    }
                } else {
                    $maturity_status = '❓ Unknown';
                    $maturity_color = '#6c757d';
                    // Show debug info for unknown status
                    $available_fields = is_array($invite_entry) ? array_keys($invite_entry) : array();
                    $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
                }
                
                $meta_id = 'discord_invite_' . $index . '_' . $invite_entry['verification_date'];
                $output .= '<tr style="background: #f3e5f5; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #7b1fa2;">' . esc_html($order_details) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #7b1fa2; font-weight: 600;">' . number_format($xp_amount) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
                $output .= '</tr>';
            }
        }
    }
    
    // Display Discord details transactions (additional Discord activities)
    if (is_array($discord_details) && !empty($discord_details)) {
        foreach ($discord_details as $index => $discord_activity) {
            $xp_amount = isset($discord_activity['xp_awarded']) ? intval($discord_activity['xp_awarded']) : 0;
            
            // Skip transactions with no XP awarded
            if ($xp_amount <= 0) {
                continue;
            }
            
            // Check XP status - first check the transaction's actual status field
            $discord_activity_status = isset($discord_activity['status']) ? $discord_activity['status'] : 'none';
            
            if ($discord_activity_status === 'requested') {
                $status_text = 'Requested';
                $status_color = '#6f42c1';
            } elseif ($discord_activity_status === 'redeemed') {
                $status_text = 'Redeemed';
                $status_color = '#28a745';
            } elseif ($discord_activity_status === 'released') {
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($discord_activity_status === 'completed') {
                // Legacy status for backward compatibility
                $status_text = 'Released';
                $status_color = '#17a2b8';
            } elseif ($discord_activity_status === 'processing') {
                $status_text = 'Processing';
                $status_color = '#007cba';
            } else {
                // Fallback to old logic
                $is_discord_member = isset($discord_activity['discord_member']) && $discord_activity['discord_member'];
                if ($has_discord_invite) {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } else {
                    $status_text = $is_discord_member ? 'Completed' : 'Pending';
                    $status_color = $is_discord_member ? '#28a745' : '#ffc107';
                }
            }
            
            // Format order details for Discord activities
            $order_details = 'Discord Activity';
            if (isset($discord_activity['activity_type'])) {
                $order_details = ucfirst($discord_activity['activity_type']);
            }
            if (isset($discord_activity['discord_username']) && !empty($discord_activity['discord_username'])) {
                $order_details .= ' - @' . $discord_activity['discord_username'];
            }
            if (isset($discord_activity['verification_date'])) {
                $order_details .= ' - ' . $discord_activity['verification_date'];
            }
            
            // Calculate maturity status for Discord activity transaction
            $delivery_date = dongtrader_get_delivery_date_from_xp_entry($discord_activity);
            $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
            $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
            $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
            
            // Format maturity status display with debug info
            $maturity_status = '';
            $maturity_color = '#6c757d';
            $debug_info = '';
            
            if ($is_mature) {
                $maturity_status = '✅ Mature';
                $maturity_color = '#28a745';
                $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
            } elseif ($days_until_maturity !== null) {
                if ($days_until_maturity > 0) {
                    $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                    $maturity_color = '#ffc107';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                } else {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                }
            } else {
                $maturity_status = '❓ Unknown';
                $maturity_color = '#6c757d';
                // Show debug info for unknown status
                $available_fields = is_array($discord_activity) ? array_keys($discord_activity) : array();
                $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
            }
            
            $meta_id = 'discord_details_' . $index . '_' . $discord_activity['verification_date'];
            $output .= '<tr style="background: #e8eaf6; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #3f51b5;">' . esc_html($order_details) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #3f51b5; font-weight: 600;">' . number_format($xp_amount) . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
            $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
            $output .= '</tr>';
        }
    }
    
    // Display Discord Poll transactions
    $discord_poll_data_for_display = get_user_meta($user_id, '_discord_poll', false);
    if (!empty($discord_poll_data_for_display)) {
        // Handle multiple Discord poll entries (array of JSON strings)
        $processed_entries = array();
        
        if (is_array($discord_poll_data_for_display)) {
            // Multiple entries from database
            foreach ($discord_poll_data_for_display as $entry_string) {
                if (is_string($entry_string)) {
                    $decoded_data = json_decode($entry_string, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                        $processed_entries[] = $decoded_data;
                    }
                }
            }
        } else if (is_string($discord_poll_data_for_display)) {
            // Single entry (fallback)
            $decoded_data = json_decode($discord_poll_data_for_display, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                $processed_entries[] = $decoded_data;
            }
        }
        
        $discord_poll_data_for_display = $processed_entries;
        
        if (is_array($discord_poll_data_for_display) && !empty($discord_poll_data_for_display)) {
            foreach ($discord_poll_data_for_display as $index => $poll_entry) {
                $xp_amount = isset($poll_entry['xp_awarded']) ? intval($poll_entry['xp_awarded']) : 0;
                
                // Skip transactions with no XP awarded
                if ($xp_amount <= 0) {
                    continue;
                }
                
                // Check XP status - first check the transaction's actual status field
                $poll_txn_status = isset($poll_entry['status']) ? $poll_entry['status'] : 'none';
                
                if ($poll_txn_status === 'requested') {
                    $status_text = 'Requested';
                    $status_color = '#6f42c1';
                } elseif ($poll_txn_status === 'redeemed') {
                    $status_text = 'Redeemed';
                    $status_color = '#28a745';
                } elseif ($poll_txn_status === 'released') {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($poll_txn_status === 'completed') {
                    // Legacy status for backward compatibility
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($poll_txn_status === 'processing') {
                    $status_text = 'Processing';
                    $status_color = '#007cba';
                } else {
                    // Fallback to old logic
                $is_discord_member = get_user_meta($user_id, 'discord_user_id', true) ? true : false;
                if ($has_discord_invite) {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } else {
                    $status_text = $is_discord_member ? 'Completed' : 'Pending';
                    $status_color = $is_discord_member ? '#28a745' : '#ffc107';
                    }
                }
                
                // Format order details for Discord poll
                $order_details = 'Discord Poll Participation';
                if (isset($poll_entry['vote_type']) && !empty($poll_entry['vote_type'])) {
                    $order_details = ucfirst(str_replace('_', ' ', $poll_entry['vote_type']));
                }
                if (isset($poll_entry['username']) && !empty($poll_entry['username'])) {
                    $order_details .= ' - @' . $poll_entry['username'];
                }
                if (isset($poll_entry['vote']) && !empty($poll_entry['vote'])) {
                    $order_details .= ' (' . $poll_entry['vote'] . ')';
                }
                
                // Check for submitted_at field (the actual date field)
                if (isset($poll_entry['submitted_at']) && !empty($poll_entry['submitted_at'])) {
                    $order_details .= ' - ' . $poll_entry['submitted_at'];
                }
                
                // Calculate maturity status for Discord poll transaction
                $delivery_date = dongtrader_get_delivery_date_from_xp_entry($poll_entry);
                $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
                $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
                $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
                
                // Format maturity status display with debug info
                $maturity_status = '';
                $maturity_color = '#6c757d';
                $debug_info = '';
                
                if ($is_mature) {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                } elseif ($days_until_maturity !== null) {
                    if ($days_until_maturity > 0) {
                        $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                        $maturity_color = '#ffc107';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                    } else {
                        $maturity_status = '✅ Mature';
                        $maturity_color = '#28a745';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                    }
                } else {
                    $maturity_status = '❓ Unknown';
                    $maturity_color = '#6c757d';
                    // Show debug info for unknown status
                    $available_fields = is_array($poll_entry) ? array_keys($poll_entry) : array();
                    $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
                }
                
                $meta_id = 'discord_poll_' . $index . '_' . $poll_entry['submitted_at'];
                $output .= '<tr style="background: #e1f5fe; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #0277bd;">' . esc_html($order_details) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #0277bd; font-weight: 600;">' . number_format($xp_amount) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
                $output .= '</tr>';
            }
        }
    }
    
    // Display Talent Show Entry transactions
    $talentshow_entry_data_for_display = get_user_meta($user_id, '_talentshow_entry', false);
    if (!empty($talentshow_entry_data_for_display)) {
        // Handle multiple talent show entries (array of JSON strings)
        $processed_entries = array();
        
        if (is_array($talentshow_entry_data_for_display)) {
            // Multiple entries from database
            foreach ($talentshow_entry_data_for_display as $entry_string) {
                if (is_string($entry_string)) {
                    $decoded_data = json_decode($entry_string, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                        $processed_entries[] = $decoded_data;
                    }
                }
            }
        } else if (is_string($talentshow_entry_data_for_display)) {
            // Single entry (fallback)
            $decoded_data = json_decode($talentshow_entry_data_for_display, true);
            if (json_last_error() === JSON_ERROR_NONE && $decoded_data && isset($decoded_data['xp_awarded'])) {
                $processed_entries[] = $decoded_data;
            }
        }
        
        $talentshow_entry_data_for_display = $processed_entries;
        
        if (is_array($talentshow_entry_data_for_display) && !empty($talentshow_entry_data_for_display)) {
            foreach ($talentshow_entry_data_for_display as $index => $talent_entry) {
                $xp_amount = isset($talent_entry['xp_awarded']) ? intval($talent_entry['xp_awarded']) : 0;
                
                // Skip transactions with no XP awarded
                if ($xp_amount <= 0) {
                    continue;
                }
                
                // Check XP status for talent show entries - use status field
                $status = isset($talent_entry['status']) ? $talent_entry['status'] : 'pending';
                
                if ($status === 'requested') {
                    $status_text = 'Requested';
                    $status_color = '#6f42c1';
                } elseif ($status === 'redeemed') {
                    $status_text = 'Redeemed';
                    $status_color = '#28a745';
                } elseif ($status === 'released') {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($status === 'completed') {
                    // Legacy status for backward compatibility
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($status === 'submitted') {
                    $status_text = 'Released';
                    $status_color = '#17a2b8';
                } elseif ($status === 'processing') {
                    $status_text = 'Processing';
                    $status_color = '#007cba';
                } else {
                    $status_text = 'Pending';
                    $status_color = '#ffc107';
                }
                
                // Format order details for talent show entry
                $order_details = 'Talent Show Entry';
                if (isset($talent_entry['performance_type'])) {
                    $order_details = ucfirst(str_replace('_', ' ', $talent_entry['performance_type']));
                }
                if (isset($talent_entry['submission_date'])) {
                    $order_details .= ' - ' . $talent_entry['submission_date'];
                }
                
                // Calculate maturity status for Talent Show entry transaction
                $delivery_date = dongtrader_get_delivery_date_from_xp_entry($talent_entry);
                $is_mature = !empty($delivery_date) ? dongtrader_is_xp_entry_mature($delivery_date) : false;
                $days_until_maturity = !empty($delivery_date) ? dongtrader_days_until_maturity($delivery_date) : null;
                $maturity_date = !empty($delivery_date) ? dongtrader_calculate_maturity_date($delivery_date) : null;
                
                // Format maturity status display with debug info
                $maturity_status = '';
                $maturity_color = '#6c757d';
                $debug_info = '';
                
                if ($is_mature) {
                    $maturity_status = '✅ Mature';
                    $maturity_color = '#28a745';
                    $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                } elseif ($days_until_maturity !== null) {
                    if ($days_until_maturity > 0) {
                        $maturity_status = '⏳ Maturing (' . abs($days_until_maturity) . ' days)';
                        $maturity_color = '#ffc107';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matures: ' . esc_html($maturity_date) . '</small>';
                    } else {
                        $maturity_status = '✅ Mature';
                        $maturity_color = '#28a745';
                        $debug_info = '<br><small style="font-size: 10px; color: #6c757d;">Earned: ' . esc_html($delivery_date) . '<br>Matured: ' . esc_html($maturity_date) . '</small>';
                    }
                } else {
                    $maturity_status = '❓ Unknown';
                    $maturity_color = '#6c757d';
                    // Show debug info for unknown status
                    $available_fields = is_array($talent_entry) ? array_keys($talent_entry) : array();
                    $debug_info = '<br><small style="font-size: 10px; color: #dc3545;">No delivery date found<br>Available fields: ' . esc_html(implode(', ', $available_fields)) . '</small>';
                }
                
                $meta_id = 'talentshow_' . $index . '_' . $talent_entry['submission_date'];
                $output .= '<tr style="background: #fff3e0; border-bottom: 1px solid #dee2e6;" data-meta-id="' . $meta_id . '">';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; font-weight: 600; color: #f57c00;">' . esc_html($order_details) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: #f57c00; font-weight: 600;">' . number_format($xp_amount) . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
                $output .= '<td style="padding: 12px; border: 1px solid #ddd; text-align: center; color: ' . $maturity_color . '; font-weight: bold; font-size: 12px;">' . $maturity_status . $debug_info . '</td>';
                $output .= '</tr>';
            }
        }
    }
    
    // Show message if no transactions
    $has_discord_data = false;
    $discord_check_data = get_user_meta($user_id, '_discord_invite', true);
    if (!empty($discord_check_data)) {
        if (is_string($discord_check_data)) {
            $decoded_check = json_decode($discord_check_data, true);
            $has_discord_data = (json_last_error() === JSON_ERROR_NONE && $decoded_check && isset($decoded_check['xp_awarded']));
        } else {
            $has_discord_data = is_array($discord_check_data) && !empty($discord_check_data);
        }
    }
    
    // Check if talent show entry data exists
    $has_talentshow_data = false;
    $talentshow_check_data = get_user_meta($user_id, '_talentshow_entry', false);
    if (!empty($talentshow_check_data) && is_array($talentshow_check_data)) {
        // Check if any of the entries have valid XP data
        foreach ($talentshow_check_data as $entry_string) {
            if (is_string($entry_string)) {
                $decoded_talentshow = json_decode($entry_string, true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded_talentshow && isset($decoded_talentshow['xp_awarded'])) {
                    $has_talentshow_data = true;
                    break;
                }
            }
        }
    }
    
    // Check if buyer details exist (including YAMer with 0 XP)
    $has_buyer_data = false;
    $buyer_check_data = get_user_meta($user_id, '_buyer_details', true);
    if (is_array($buyer_check_data) && !empty($buyer_check_data)) {
        // Check if any buyer transactions exist (regardless of XP amount)
        foreach ($buyer_check_data as $transaction) {
            if (isset($transaction['xp_awarded'])) {
                $has_buyer_data = true;
                break;
            }
        }
    }
    
    // Check if Discord poll data exists
    $has_discord_poll_data = false;
    $discord_poll_check_data = get_user_meta($user_id, '_discord_poll', false);
    if (!empty($discord_poll_check_data) && is_array($discord_poll_check_data)) {
        // Check if any of the entries have valid XP data
        foreach ($discord_poll_check_data as $entry_string) {
            if (is_string($entry_string)) {
                $decoded_poll = json_decode($entry_string, true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded_poll && isset($decoded_poll['xp_awarded'])) {
                    $has_discord_poll_data = true;
                    break;
                }
            }
        }
    }
    
    if ((!is_array($seller_details) || empty($seller_details)) && empty($paid_orders) && !$has_discord_data && (!is_array($discord_details) || empty($discord_details)) && !$has_talentshow_data && !$has_buyer_data && !$has_discord_poll_data) {
        $output .= '<tr style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">';
        $output .= '<td colspan="3" style="padding: 20px; border: 1px solid #ddd; text-align: center; color: #6c757d; font-style: italic;">No transactions found. Complete your first order, scanning activity, Discord activity, Discord poll participation, or talent show entry to see XP here.</td>';
        $output .= '</tr>';
    }
    
    $output .= '</table>';
    $output .= '</div>';
    
    // XP Summary Section
    $output .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #dee2e6;">';
    $output .= '<h4 style="color: #2c3e50; margin-top: 0; margin-bottom: 15px;">📊 XP Summary</h4>';
    
    // Calculate XP totals
    $total_xp_earned = $total_earned_xp;
    $total_completed_xp = $total_xp_earned - $total_pending_xp;
    
    // XP to YAM conversion using helper functions
    $total_yam = dongtrader_xp_to_yam($total_completed_xp);
    
    // YAM to USD conversion: YAM ÷ 21,000
    $total_usd = $total_yam / 21000;
    
    // Calculate conversion rates for display using helper functions
    $xp_per_usd = dongtrader_xp_per_dollar(); // 1 USD = 1,000,000,000,000,000,000,000 XP (10^21)
    $xp_per_yam = dongtrader_xp_per_yam(); // 47,619,047,619,047,619 XP per YAM
    $yam_per_usd = 21000; // 21,000 YAM = 1 USD
    
    $output .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">';
    
    // Total XP Earned
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border-left: 4px solid #28a745;">';
    $output .= '<h5 style="margin: 0 0 8px 0; color: #28a745; font-size: 14px;">Total XP Earned</h5>';
    $output .= '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #2c3e50;">' . number_format($total_xp_earned) . '</p>';
    $output .= '</div>';
    
    // Pending XP
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border-left: 4px solid #ffc107;">';
    $output .= '<h5 style="margin: 0 0 8px 0; color: #ffc107; font-size: 14px;">Pending XP</h5>';
    $output .= '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #2c3e50;">' . number_format($total_pending_xp) . '</p>';
    $output .= '</div>';
    
    // Redeemable XP
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; text-align: center; border-left: 4px solid #17a2b8;">';
    $output .= '<h5 style="margin: 0 0 8px 0; color: #17a2b8; font-size: 14px;">Redeemable XP</h5>';
    $output .= '<p style="margin: 0; font-size: 24px; font-weight: bold; color: #2c3e50;">' . number_format($total_completed_xp) . '</p>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    // Conversion Rates Section
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; border: 1px solid #dee2e6;">';
    $output .= '<h5 style="margin: 0 0 15px 0; color: #2c3e50;">💱 Conversion Rates</h5>';
    
    $output .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';
    
    // XP to YAM Conversion
    $output .= '<div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 14px; color: #6c757d;">XP to YAM</p>';
    $output .= '<p style="margin: 0; font-size: 18px; font-weight: bold; color: #2c3e50;">' . number_format($total_completed_xp) . ' XP = ' . number_format($total_yam, 0) . ' YAM</p>';
    $output .= '<p style="margin: 5px 0 0 0; font-size: 12px; color: #6c757d;">Rate: 1 YAM = ' . number_format($xp_per_yam, 0) . ' XP</p>';
    $output .= '</div>';
    
    // YAM to USD Conversion
    $output .= '<div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 14px; color: #6c757d;">YAM to USD</p>';
    $output .= '<p style="margin: 0; font-size: 18px; font-weight: bold; color: #2c3e50;">' . number_format($total_yam, 0) . ' YAM = $' . number_format($total_usd, 0) . '</p>';
    $output .= '<p style="margin: 5px 0 0 0; font-size: 12px; color: #6c757d;">Rate: 1 USD = ' . number_format($yam_per_usd, 0) . ' YAM</p>';
    $output .= '</div>';
    
    // XP to USD Conversion
    $output .= '<div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 14px; color: #6c757d;">XP to USD</p>';
    $output .= '<p style="margin: 0; font-size: 18px; font-weight: bold; color: #2c3e50;">' . number_format($total_completed_xp) . ' XP = $' . number_format($total_usd, 2) . '</p>';
    $output .= '<p style="margin: 5px 0 0 0; font-size: 12px; color: #6c757d;">Rate: 1 USD = ' . number_format($xp_per_usd, 0) . ' XP</p>';
    // Debug: Show minimum requirement
    $output .= '<p style="margin: 5px 0 0 0; font-size: 11px; color: ' . ($total_usd >= 1.0 ? '#28a745' : '#dc3545') . '; font-weight: bold;">Minimum for redemption: $1.00 ' . ($total_usd >= 1.0 ? '✅' : '❌') . '</p>';
    $output .= '</div>';
    
    // Check if user has any pending or processing redemption requests
    global $wpdb;
    $table_name = $wpdb->prefix . 'dongtrader_redemptions';
    $user_id = get_current_user_id();
    
    $existing_requests = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE user_id = %d 
         AND status IN ('pending', 'processing')",
        $user_id
    ));
    
    $has_active_redemption = ($existing_requests > 0);
    
    // Ensure USD amount is numeric and properly rounded for comparison
    $total_usd_numeric = floatval($total_usd);
    
    // STRICT VALIDATION: Button ONLY displays if USD value >= $1.00
    // Validate redemption button eligibility using comprehensive validation function
    $eligibility_check = dongtrader_check_redemption_button_eligibility($total_usd_numeric, $total_completed_xp, $has_active_redemption);
    
    // Display redeem button ONLY if eligible (requires >= $1.00 USD worth of matured XP)
    if ($eligibility_check['eligible']) {
        $output .= '<div style="text-align: center; padding: 10px; background: #f8f9fa; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center;">';
        $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #6c757d;">Redeem Rewards</p>';
        $output .= '<button type="button" class="redeem-button" id="redeem-rewards-btn" onclick="showRedemptionPopup(' . $total_completed_xp . ', ' . $total_yam . ', ' . $total_usd . ', ' . $xp_per_yam . ', ' . $yam_per_usd . ')" style="background: #6F42C1; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; width: 100%; max-width: 150px;" onmouseover="this.style.background=\'#5a32a3\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.background=\'#6F42C1\'; this.style.transform=\'translateY(0)\';">';
        $output .= 'Redeem';
        $output .= '</button>';
        $output .= '</div>';
    } else {
        // Show appropriate message based on reason
        $reason = $eligibility_check['reason'];
        $message = isset($eligibility_check['message']) ? $eligibility_check['message'] : 'Redemption not available.';
        
        if ($reason === 'active_redemption') {
            // Active redemption request
            $output .= '<div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #ffc107;">';
            $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #856404; font-weight: bold;">🔄 Redemption in Progress</p>';
            $output .= '<p style="margin: 0; font-size: 12px; color: #856404;">' . $message . '</p>';
            $output .= '</div>';
        } elseif ($reason === 'minimum_amount') {
            // Minimum $1 USD not met
            $current_amount = isset($eligibility_check['current_amount']) ? $eligibility_check['current_amount'] : 0;
            $output .= '<div style="text-align: center; padding: 10px; background: #e9ecef; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #6c757d;">';
            $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #495057; font-weight: bold;">💰 Minimum Redemption Required</p>';
            $output .= '<p style="margin: 0; font-size: 12px; color: #6c757d;">' . $message . '</p>';
            $output .= '</div>';
        } elseif ($reason === 'no_matured_xp') {
            // No matured XP available
            $output .= '<div style="text-align: center; padding: 10px; background: #e9ecef; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #6c757d;">';
            $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #495057; font-weight: bold;">⏳ XP Still Maturing</p>';
            $output .= '<p style="margin: 0; font-size: 12px; color: #6c757d;">' . $message . '</p>';
            $output .= '</div>';
        } elseif ($reason === 'september_1st_block') {
            // September 1st block
            $next_window = isset($eligibility_check['next_window_date']) ? $eligibility_check['next_window_date'] : '';
            $output .= '<div style="text-align: center; padding: 10px; background: #f8d7da; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #dc3545;">';
            $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #721c24; font-weight: bold;">🚫 September 1st - Let It Ride Day</p>';
            $output .= '<p style="margin: 0; font-size: 12px; color: #721c24;">' . $message . '</p>';
            if ($next_window) {
                $output .= '<p style="margin: 8px 0 0 0; font-size: 11px; color: #856404;">Next window: ' . $next_window . '</p>';
            }
            $output .= '</div>';
        } elseif ($reason === 'outside_window') {
            // Outside redemption window
            $next_window_date = isset($eligibility_check['next_window_date']) ? $eligibility_check['next_window_date'] : '';
            $days_until = isset($eligibility_check['days_until_window']) ? $eligibility_check['days_until_window'] : 0;
            $window_range = isset($eligibility_check['window_range']) ? $eligibility_check['window_range'] : '';
            $output .= '<div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #ffc107;">';
            $output .= '<p style="margin: 0 0 10px 0; font-size: 14px; color: #856404; font-weight: bold;">⏳ Redemption Window Closed</p>';
            $output .= '<p style="margin: 0; font-size: 12px; color: #856404;">' . $message . '</p>';
            if ($next_window_date && $days_until !== null) {
                $output .= '<p style="margin: 8px 0 0 0; font-size: 11px; color: #856404;">Next window: ' . $next_window_date . ' (' . $days_until . ' days)</p>';
                if ($window_range) {
                    $output .= '<p style="margin: 4px 0 0 0; font-size: 11px; color: #856404;">Window: ' . $window_range . '</p>';
                }
            }
            $output .= '</div>';
        } else {
            // Generic not eligible message
            $output .= '<div style="text-align: center; padding: 10px; background: #e9ecef; border-radius: 4px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-left: 4px solid #6c757d;">';
            $output .= '<p style="margin: 0; font-size: 12px; color: #6c757d;">' . $message . '</p>';
            $output .= '</div>';
        }
    }
    
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Redemption Popup HTML
    $output .= '<div id="redemption-popup" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; min-width: 100%; min-height: 100%; background: rgba(0, 0, 0, 0.8); z-index: 10000; justify-content: center; align-items: center; margin: 0; padding: 0; overflow: hidden;">';
    $output .= '<div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);">';
    
    // Popup Header
    $output .= '<div style="text-align: center; margin-bottom: 25px;">';
    $output .= '<h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 24px;">💰 Redemption Details</h3>';
    $output .= '<p style="margin: 0; color: #6c757d; font-size: 14px;">Review your redemption request before submitting</p>';
    $output .= '</div>';
    
    // Redemption Window Status and Rules - Enhanced Display
    $is_within_window = dongtrader_is_within_redemption_window();
    $next_window = dongtrader_get_next_redemption_window();
    $days_until_window = dongtrader_days_until_next_redemption_window();
    
    // Get current window dates
    $current_window = dongtrader_get_monthly_redemption_window();
    $current_window_start = date('F j, Y', strtotime($current_window['start']));
    $current_window_end = date('F j, Y', strtotime($current_window['end']));
    
    // Format next window dates
    $next_window_start = date('F j, Y', strtotime($next_window['start']));
    $next_window_end = date('F j, Y', strtotime($next_window['end']));
    
    // Check if it's September 1st
    $current_date_obj = new DateTime(current_time('mysql'));
    $is_september_1st = ($current_date_obj->format('m-d') === '09-01');
    
    $window_status_color = $is_within_window ? '#28a745' : ($is_september_1st ? '#dc3545' : '#ffc107');
    $window_status_text = $is_within_window 
        ? '✅ Redemption Window Open' 
        : ($is_september_1st ? '🚫 No Redemptions Allowed (September 1st)' : '⏳ Redemption Window Closed');
    
    $output .= '<div style="background: ' . ($is_within_window ? '#d4edda' : ($is_september_1st ? '#f8d7da' : '#fff3cd')) . '; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid ' . $window_status_color . ';">';
    
    // Status Header
    $output .= '<h4 style="margin: 0 0 15px 0; font-size: 16px; font-weight: bold; color: ' . ($is_within_window ? '#155724' : ($is_september_1st ? '#721c24' : '#856404')) . ';">📅 Redemption Window Status</h4>';
    $output .= '<p style="margin: 0 0 15px 0; font-size: 14px; font-weight: bold; color: ' . ($is_within_window ? '#155724' : ($is_september_1st ? '#721c24' : '#856404')) . ';">' . $window_status_text . '</p>';
    
    // Current Status Details
    if ($is_within_window) {
        $output .= '<div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: 15px;">';
        $output .= '<p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; color: #2c3e50;">Current Window:</p>';
        $output .= '<p style="margin: 0; font-size: 13px; color: #155724;"><strong>' . $current_window_start . '</strong> to <strong>' . $current_window_end . '</strong></p>';
        $output .= '<p style="margin: 8px 0 0 0; font-size: 12px; color: #6c757d;">You can submit redemption requests during this period.</p>';
        $output .= '</div>';
    } else if ($is_september_1st) {
        $output .= '<div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: 15px;">';
        $output .= '<p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; color: #721c24;">September 1st - Let It Ride Day</p>';
        $output .= '<p style="margin: 0; font-size: 12px; color: #721c24;">No redemptions are allowed on September 1st. This is the annual "Let It Ride Day" for reconciliation.</p>';
        $output .= '<p style="margin: 8px 0 0 0; font-size: 12px; color: #856404;"><strong>Next available window:</strong> ' . $next_window_start . ' - ' . $next_window_end . ' (' . $days_until_window . ' days)</p>';
        $output .= '</div>';
    } else {
        $output .= '<div style="background: white; padding: 12px; border-radius: 6px; margin-bottom: 15px;">';
        $output .= '<p style="margin: 0 0 8px 0; font-size: 13px; font-weight: bold; color: #2c3e50;">Next Window:</p>';
        $output .= '<p style="margin: 0; font-size: 13px; color: #856404;"><strong>' . $next_window_start . '</strong> to <strong>' . $next_window_end . '</strong></p>';
        $output .= '<p style="margin: 8px 0 0 0; font-size: 12px; color: #6c757d;">Days remaining: <strong>' . $days_until_window . ' days</strong></p>';
        $output .= '</div>';
    }
    
    // Redemption Window Rules
    $output .= '<div style="background: white; padding: 12px; border-radius: 6px; margin-top: 15px;">';
    $output .= '<p style="margin: 0 0 10px 0; font-size: 13px; font-weight: bold; color: #2c3e50;">📋 Redemption Window Rules:</p>';
    $output .= '<ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #495057; line-height: 1.8;">';
    $output .= '<li><strong>Window Period:</strong> 1st through 7th of each month (00:00:00 to 23:59:59)</li>';
    $output .= '<li><strong>September 1st:</strong> No redemptions allowed (Annual "Let It Ride Day")</li>';
    $output .= '<li><strong>Next Window After Sept 1:</strong> October 1-7</li>';
    $output .= '<li><strong>Request Timing:</strong> Redemption requests can only be submitted during open windows</li>';
    $output .= '</ul>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    // Redemption Summary
    $output .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
    $output .= '<h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 18px;">📊 Redemption Info</h4>';
    
    $output .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">';
    $output .= '<div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 12px; color: #6c757d;">xp_redem</p>';
    $output .= '<p id="popup-xp-amount" style="margin: 0; font-size: 20px; font-weight: bold; color: #2c3e50;">0</p>';
    $output .= '</div>';
    
    $output .= '<div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 12px; color: #6c757d;">yam_redem</p>';
    $output .= '<p id="popup-yam-amount" style="margin: 0; font-size: 20px; font-weight: bold; color: #2c3e50;">0</p>';
    $output .= '</div>';
    
    $output .= '<div style="text-align: center; padding: 15px; background: white; border-radius: 6px;">';
    $output .= '<p style="margin: 0 0 5px 0; font-size: 12px; color: #6c757d;">usd_redem</p>';
    $output .= '<p id="popup-usd-amount" style="margin: 0; font-size: 20px; font-weight: bold; color: #28a745;">$0</p>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Additional Database Fields Display - HIDDEN FOR PRODUCTION (Debug info only)
    /*
    $output .= '<div style="margin-top: 20px; padding: 15px; background: #e8f4fd; border-radius: 6px;">';
    $output .= '<h5 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 14px;">📋 Additional Database Fields</h5>';
    $output .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px;">';
    $output .= '<div><strong>id:</strong> <span style="color: #6c757d;">Auto-generated</span></div>';
    $output .= '<div><strong>meta_ids:</strong> <span id="popup-meta-ids" style="color: #6c757d;">Loading...</span></div>';
    $output .= '<div><strong>status:</strong> <span style="color: #28a745;">pending</span></div>';
    $output .= '<div><strong>payment_method:</strong> <span id="popup-payment-method-display" style="color: #6c757d;">Not selected</span></div>';
    $output .= '<div><strong>payment_details:</strong> <span id="popup-payment-details-display" style="color: #6c757d;">Not provided</span></div>';
    $output .= '<div><strong>redem_date:</strong> <span style="color: #6c757d;">Current timestamp</span></div>';
    $output .= '<div><strong>processed_date:</strong> <span style="color: #6c757d;">NULL</span></div>';
    $output .= '<div><strong>admin_notes:</strong> <span style="color: #6c757d;">NULL</span></div>';
    $output .= '<div><strong>transaction_id:</strong> <span style="color: #6c757d;">NULL</span></div>';
    $output .= '</div>';
    $output .= '</div>';
    */
    $output .= '</div>';
    
    // Payment Method Selection
    $output .= '<div style="margin-bottom: 20px;">';
    $output .= '<h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;">💳 Payment Method</h4>';
    $output .= '<select id="payment-method" style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px; background: white;">';
    $output .= '<option value="">Select Payment Method</option>';
    $output .= '<option value="paypal">PayPal</option>';
    $output .= '<option value="venmo">Venmo</option>';
    $output .= '<option value="bank_transfer">Bank Transfer</option>';
    $output .= '<option value="crypto">Cryptocurrency</option>';
    $output .= '</select>';
    $output .= '</div>';
    
    // Payment Details
    $output .= '<div style="margin-bottom: 25px;">';
    $output .= '<h4 style="margin: 0 0 15px 0; color: #2c3e50; font-size: 16px;">📝 Payment Details</h4>';
    $output .= '<textarea id="payment-details" placeholder="Enter your payment details (e.g., PayPal email, Venmo username, etc.)" style="width: 100%; padding: 12px; border: 2px solid #dee2e6; border-radius: 6px; font-size: 14px; min-height: 80px; resize: vertical;"></textarea>';
    $output .= '</div>';
    
    // Action Buttons
    $output .= '<div style="display: flex; gap: 15px; justify-content: center;">';
    $output .= '<button onclick="closeRedemptionPopup()" style="background: #6c757d; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">Cancel</button>';
    $output .= '<button onclick="submitRedemptionRequest()" style="background: #6F42C1; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: all 0.3s ease;">Submit Request</button>';
    $output .= '</div>';
    
    $output .= '</div>';
    $output .= '</div>';
    
    // Add JavaScript directly to the output
    $output .= '<script type="text/javascript">
    console.log("Redemption popup script loaded - inline");
    </script>';
    
    // User Information Section
    $output .= '<div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #e74c3c;">';
    $output .= '<h4 style="color: #2c3e50; margin-top: 0;">👤 Account Information</h4>';
    $output .= '<p style="margin: 8px 0;"><strong>User Role:</strong> <span style="color: #8e44ad; font-weight: bold;">' . ($dong_user_role ? $dong_user_role : 'Not Set') . '</span></p>';
    $output .= '<p style="margin: 8px 0;"><strong>Account Type:</strong> <span style="color: #8e44ad; font-weight: bold;">' . ($is_seller ? 'Seller' : 'Buyer') . '</span></p>';
    
    // Display membership information for buyers
    if (!$is_seller) {
        $buyer_details = get_user_meta($user_id, '_buyer_details', true);
        if (is_array($buyer_details) && !empty($buyer_details)) {
            // Get the latest membership from recent transactions
            $latest_membership = '';
            foreach (array_reverse($buyer_details) as $transaction) {
                if (isset($transaction['membership']) && !empty($transaction['membership'])) {
                    $latest_membership = $transaction['membership'];
                    break;
                }
            }
            if ($latest_membership) {
                $output .= '<p style="margin: 8px 0;"><strong>Membership:</strong> <span style="color: #e67e22; font-weight: bold;">' . esc_html($latest_membership) . '</span></p>';
            } else {
                $output .= '<p style="margin: 8px 0;"><strong>Membership:</strong> <span style="color: #95a5a6; font-style: italic;">No Membership</span></p>';
            }
        }
    }
    $output .= '</div>';
    
    // Discord Connection Section
    $discord_user_id = get_user_meta($user_id, 'discord_user_id', true);
    
    // Check if there's any Discord invite data
    $discord_invite_data = get_user_meta($user_id, '_discord_invite', true);
    $has_discord_invite_data = !empty($discord_invite_data);
    
    if ($discord_user_id) {
        $output .= '<div style="background: white; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #9b59b6;">';
        $output .= '<h4 style="color: #8e44ad; margin-top: 0;">Discord Connected</h4>';
        $output .= '<p style="margin: 8px 0;"><strong>Discord ID:</strong> <span style="color: #9b59b6; font-weight: bold;">' . esc_html($discord_user_id) . '</span></p>';
        
        if ($total_pending_xp > 0) {
            $output .= '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 12px; border-radius: 4px; margin-top: 10px;">';
            $output .= '<p style="margin: 5px 0; color: #856404;"><strong>⚠️ XP Pending - Discord Membership Verification Required</strong></p>';
            $output .= '<p style="margin: 5px 0; color: #856404;">You have <strong>' . number_format($total_pending_xp) . ' XP</strong> pending. Our Discord bot will verify your server membership to release these rewards.</p>';
            $output .= '<p style="margin: 5px 0;"><a href="https://discord.gg/g5jreAPbra" target="_blank" style="background: #8e44ad; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; display: inline-block;">🔗 Join Discord Server</a></p>';
            $output .= '</div>';
        } else {
            $output .= '<p style="margin: 8px 0; color: #27ae60;"><strong>✅ All XP has been verified and completed!</strong></p>';
        }
        $output .= '</div>';
    } elseif (!$has_discord_invite_data) {
        // Show "Connect Discord Account" only if user is not connected to Discord AND has no _discord_invite data
        $output .= '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #e74c3c;">';
        $output .= '<h4 style="color: #721c24; margin-top: 0;">🔗 Connect Discord Account</h4>';
        $output .= '<p style="margin: 8px 0; color: #721c24;"><strong>Action Required:</strong> To receive your XP rewards, you must connect your Discord account.</p>';

        
        // Add Join Gracebook button
        $output .= '<div style="margin-top: 15px; text-align: center;">';
        $output .= '<a href="https://discord.gg/g5jreAPbra" target="_blank" style="background: #8e44ad; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: all 0.3s ease;">Join Gracebook</a>';
        $output .= '</div>';
        
        $output .= '</div>';
    }
    
    
    $output .= '</div>';
    
    return $output;
}

/**
 * Check if a transaction is eligible for redemption
 * Validates both status and maturity period
 * @param array $transaction Transaction array
 * @param int $umeta_id Umeta ID for tracking
 * @return array Array with 'eligible' bool and additional info
 */
function dongtrader_check_transaction_eligibility($transaction, $umeta_id) {
    if (!is_array($transaction)) {
        return array(
            'eligible' => false,
            'reason' => 'Invalid transaction data',
            'umeta_id' => $umeta_id
        );
    }
    
    // Check the status field - exclude if already in redemption process
    $status = isset($transaction['status']) ? $transaction['status'] : 'none';
    
    if ($status === 'requested' || $status === 'processing') {
        return array(
            'eligible' => false,
            'reason' => 'Already in redemption process',
            'umeta_id' => $umeta_id,
            'status' => $status
        );
    }
    
    // Get delivery date from transaction
    $delivery_date = dongtrader_get_delivery_date_from_xp_entry($transaction);
    
    // If no delivery date found, check if we can use fallback
    if (empty($delivery_date)) {
        // Fallback: Try to use umeta_id creation timestamp or current date
        // This handles legacy entries without dates
        global $wpdb;
        $umeta_row = $wpdb->get_row($wpdb->prepare(
            "SELECT umeta_id FROM {$wpdb->usermeta} WHERE umeta_id = %d",
            $umeta_id
        ));
        
        // For legacy entries without dates, we'll mark them as "unknown maturity"
        // Admin can review these manually
        return array(
            'eligible' => false,
            'reason' => 'No delivery date found (legacy entry)',
            'umeta_id' => $umeta_id,
            'immature' => true,
            'needs_review' => true
        );
    }
    
    // Check if entry is mature
    $is_mature = dongtrader_is_xp_entry_mature($delivery_date);
    
    if (!$is_mature) {
        $days_remaining = dongtrader_days_until_maturity($delivery_date);
        $maturity_date = dongtrader_calculate_maturity_date($delivery_date);
        
        return array(
            'eligible' => false,
            'reason' => 'XP entry not yet mature',
            'umeta_id' => $umeta_id,
            'immature' => true,
            'delivery_date' => $delivery_date,
            'maturity_date' => $maturity_date,
            'days_remaining' => $days_remaining
        );
    }
    
    // Entry is mature and status is eligible
    return array(
        'eligible' => true,
        'umeta_id' => $umeta_id,
        'delivery_date' => $delivery_date,
        'maturity_date' => dongtrader_calculate_maturity_date($delivery_date),
        'status' => $status
    );
}

/**
 * AJAX handler to get XP umeta_id values
 */
add_action('wp_ajax_get_xp_umeta_ids', 'dongtrader_get_xp_umeta_ids');
add_action('wp_ajax_submit_redemption_request', 'dongtrader_submit_redemption_request');

// Test AJAX handler
add_action('wp_ajax_test_ajax', 'dongtrader_test_ajax');

function dongtrader_test_ajax() {
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'test_ajax')) {
        wp_die('Security check failed');
}

    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    wp_send_json_success('Test AJAX working');
}

function dongtrader_get_xp_umeta_ids() {
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // Validate and sanitize user ID
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $current_user_id = get_current_user_id();
    
    if ($user_id !== $current_user_id || $user_id <= 0) {
        wp_send_json_error('Invalid user ID');
    }
    
    global $wpdb;
    
    // Define meta keys to search for
    $meta_keys = array('_buyer_details', '_seller_details', '_discord_invite', '_discord_poll', '_talentshow_entry');
    
    // Create placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    
    // Single optimized query to get all umeta_ids with their meta_values
    $query = $wpdb->prepare(
        "SELECT umeta_id, meta_value FROM {$wpdb->usermeta} 
         WHERE user_id = %d AND meta_key IN ($placeholders)",
        array_merge(array($user_id), $meta_keys)
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    // Check for database errors
    if ($wpdb->last_error) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
    
    $filtered_umeta_ids = array();
    $filtered_count = 0;
    $immature_entries = array(); // Track immature entries for UI display
    
    error_log("=== Umeta ID Filtering (with maturity check) ===");
    error_log("Total records found: " . count($results));
    
    // Filter out transactions with status 'requested' or already processed
    // AND filter out immature entries (not yet 8-12 weeks old)
    foreach ($results as $row) {
        $umeta_id = intval($row['umeta_id']);
        $meta_value = $row['meta_value'];
        
        // Skip if empty
        if (empty($meta_value)) {
            error_log("Skipping umeta_id $umeta_id: Empty meta_value");
            continue;
        }
        
        // Try to decode as JSON first
        $meta_data = json_decode($meta_value, true);
        $is_json = (json_last_error() === JSON_ERROR_NONE);
        
        // If not JSON, try PHP serialized data
        if (!$is_json) {
            $meta_data = @unserialize($meta_value);
            if ($meta_data !== false && is_array($meta_data)) {
                // Handle nested array structure - check if it's an array of transactions
                if (isset($meta_data[0]) && is_array($meta_data[0])) {
                    // It's an array of transactions, process each one
                    foreach ($meta_data as $transaction) {
                        $should_include = dongtrader_check_transaction_eligibility($transaction, $umeta_id);
                        if ($should_include['eligible']) {
                            $filtered_umeta_ids[] = $umeta_id;
                            $filtered_count++;
                        } elseif (isset($should_include['immature'])) {
                            $immature_entries[] = $should_include;
                        }
                    }
                    continue; // Continue to next row
                } else {
                    // Single transaction
                $is_json = true;
                }
            }
        }
        
        if ($is_json && is_array($meta_data)) {
            $should_include = dongtrader_check_transaction_eligibility($meta_data, $umeta_id);
            
            if ($should_include['eligible']) {
                $filtered_umeta_ids[] = $umeta_id;
                $filtered_count++;
                error_log("Including umeta_id $umeta_id (mature and eligible)");
            } elseif (isset($should_include['immature'])) {
                $immature_entries[] = $should_include;
                error_log("Excluding umeta_id $umeta_id - " . $should_include['reason']);
            } else {
                error_log("Excluding umeta_id $umeta_id - " . $should_include['reason']);
            }
        } else {
            // If we can't parse it, check if it's safe to include (fallback for old data)
            // For backwards compatibility, we'll include it but log a warning
            error_log("Warning: Including umeta_id $umeta_id (unparseable data - legacy entry)");
            $filtered_umeta_ids[] = $umeta_id;
            $filtered_count++;
        }
    }
    
    error_log("Filtered count: $filtered_count mature entries out of " . count($results));
    error_log("Immature entries: " . count($immature_entries));
    
    // Return success response with filtered data
    wp_send_json_success(array(
        'umeta_ids' => $filtered_umeta_ids,
        'count' => $filtered_count,
        'total_found' => count($results),
        'meta_keys_searched' => $meta_keys,
        'immature_entries' => $immature_entries // Include immature entries info for UI
    ));
}

/**
 * AJAX handler to submit redemption request
 */
function dongtrader_submit_redemption_request() {
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // Get and validate form data
    $user_id = get_current_user_id();
    
    error_log('=== REDEMPTION REQUEST RECEIVED ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    $xp_amount = isset($_POST['xp_amount']) ? intval($_POST['xp_amount']) : 0;
    $yam_amount = isset($_POST['yam_amount']) ? floatval($_POST['yam_amount']) : 0;
    $usd_amount = isset($_POST['usd_amount']) ? floatval($_POST['usd_amount']) : 0;
    $xp_per_yam = isset($_POST['xp_per_yam']) ? floatval($_POST['xp_per_yam']) : 0;
    $yam_per_usd = isset($_POST['yam_per_usd']) ? floatval($_POST['yam_per_usd']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $payment_details = isset($_POST['payment_details']) ? sanitize_textarea_field($_POST['payment_details']) : '';
    // Don't sanitize JSON - just get it as-is
    $meta_ids = isset($_POST['meta_ids']) ? $_POST['meta_ids'] : '';
    
    error_log("Extracted meta_ids: " . $meta_ids);
    error_log("Meta IDs type: " . gettype($meta_ids));
    error_log("Meta IDs length: " . strlen($meta_ids));
    error_log("Meta IDs JSON decode test: " . print_r(json_decode($meta_ids, true), true));
    
    // Validate required fields
    if (empty($payment_method)) {
        wp_send_json_error('Payment method is required');
    }
    
    if (empty($payment_details)) {
        wp_send_json_error('Payment details are required');
    }
    
    if ($xp_amount <= 0 || $yam_amount <= 0 || $usd_amount <= 0) {
        wp_send_json_error('Invalid redemption amounts');
    }
    
    global $wpdb;
    
    // ===== MATURITY AND REDEMPTION WINDOW VALIDATION =====
    
    // Check if currently within a redemption window
    if (!dongtrader_is_within_redemption_window()) {
        $days_until = dongtrader_days_until_next_redemption_window();
        $next_window = dongtrader_get_next_redemption_window();
        wp_send_json_error(array(
            'message' => 'Redemption can only be submitted during monthly redemption windows.',
            'days_until_window' => $days_until,
            'next_window_date' => $next_window['date'],
            'next_window_start' => $next_window['start']
        ));
    }
    
    // Validate maturity for all selected XP entries
    if (!empty($meta_ids)) {
        $meta_ids_array = json_decode($meta_ids, true);
        if (is_array($meta_ids_array) && !empty($meta_ids_array)) {
            $immature_entries = array();
            $delivery_dates = array();
            
            foreach ($meta_ids_array as $umeta_id) {
                $umeta_id = intval($umeta_id);
                
                // Get the usermeta record
                $meta_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE umeta_id = %d AND user_id = %d",
                    $umeta_id, $user_id
                ));
                
                if (!$meta_row) {
                    continue;
                }
                
                // Parse transaction data
                $meta_data = json_decode($meta_row->meta_value, true);
                if (!$meta_data || !is_array($meta_data)) {
                    $meta_data = @unserialize($meta_row->meta_value);
                }
                
                if (is_array($meta_data)) {
                    // Handle array of transactions
                    if (isset($meta_data[0]) && is_array($meta_data[0])) {
                        foreach ($meta_data as $transaction) {
                            $check_result = dongtrader_check_transaction_eligibility($transaction, $umeta_id);
                            if (isset($check_result['immature']) && $check_result['immature']) {
                                $immature_entries[] = $check_result;
                            }
                            if (isset($check_result['delivery_date'])) {
                                $delivery_dates[] = $check_result['delivery_date'];
                            }
                        }
                    } else {
                        // Single transaction
                        $check_result = dongtrader_check_transaction_eligibility($meta_data, $umeta_id);
                        if (isset($check_result['immature']) && $check_result['immature']) {
                            $immature_entries[] = $check_result;
                        }
                        if (isset($check_result['delivery_date'])) {
                            $delivery_dates[] = $check_result['delivery_date'];
                        }
                    }
                }
            }
            
            // If any entries are immature, reject the redemption
            if (!empty($immature_entries)) {
                $immature_list = array();
                foreach ($immature_entries as $entry) {
                    $days = isset($entry['days_remaining']) ? $entry['days_remaining'] : 'unknown';
                    $immature_list[] = "Entry #{$entry['umeta_id']} - {$days} days until mature";
                }
                wp_send_json_error(array(
                    'message' => 'Some selected XP entries are not yet mature (8-12 weeks required).',
                    'immature_entries' => $immature_entries,
                    'details' => implode(', ', $immature_list)
                ));
            }
            
            // Calculate oldest and youngest delivery dates for storage
            if (!empty($delivery_dates)) {
                $oldest_delivery = min($delivery_dates);
                $youngest_delivery = max($delivery_dates);
                $maturity_date = dongtrader_calculate_maturity_date($youngest_delivery);
            } else {
                $oldest_delivery = null;
                $youngest_delivery = null;
                $maturity_date = null;
            }
        } else {
            $oldest_delivery = null;
            $youngest_delivery = null;
            $maturity_date = null;
        }
    } else {
        $oldest_delivery = null;
        $youngest_delivery = null;
        $maturity_date = null;
    }
    
    // ===== END VALIDATION =====
    
    // Get maturity weeks setting
    $maturity_weeks = dongtrader_get_maturity_weeks();
    $is_within_window = dongtrader_is_within_redemption_window();
    
    // Create redemption table if it doesn't exist
    $table_name = $wpdb->prefix . 'dongtrader_redemptions';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        xp_redem bigint(20) NOT NULL,
        yam_redem decimal(20,8) NOT NULL,
        usd_redem decimal(10,2) NOT NULL,
        conversion_rate_xp_yam decimal(20,8) NOT NULL,
        conversion_rate_yam_usd decimal(20,8) NOT NULL,
        meta_ids text,
        status varchar(20) DEFAULT 'pending',
        payment_method varchar(50) NOT NULL,
        payment_details text NOT NULL,
        redem_date datetime DEFAULT CURRENT_TIMESTAMP,
        processed_date datetime NULL,
        admin_notes text,
        transaction_id varchar(100) NULL,
        maturity_date datetime NULL,
        oldest_delivery_date datetime NULL,
        youngest_delivery_date datetime NULL,
        maturity_weeks int(11) DEFAULT 10,
        within_redemption_window tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY redem_date (redem_date),
        KEY maturity_date (maturity_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add new columns to existing table if they don't exist (for existing installations)
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'maturity_date'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN maturity_date datetime NULL AFTER transaction_id");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN oldest_delivery_date datetime NULL AFTER maturity_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN youngest_delivery_date datetime NULL AFTER oldest_delivery_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN maturity_weeks int(11) DEFAULT 10 AFTER youngest_delivery_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN within_redemption_window tinyint(1) DEFAULT 0 AFTER maturity_weeks");
        $wpdb->query("ALTER TABLE $table_name ADD KEY maturity_date (maturity_date)");
    }
    
    // Insert redemption request with maturity data
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'xp_redem' => $xp_amount,
            'yam_redem' => $yam_amount,
            'usd_redem' => $usd_amount,
            'conversion_rate_xp_yam' => $xp_per_yam,
            'conversion_rate_yam_usd' => $yam_per_usd,
            'meta_ids' => $meta_ids,
            'status' => 'pending',
            'payment_method' => $payment_method,
            'payment_details' => $payment_details,
            'redem_date' => current_time('mysql'),
            'maturity_date' => $maturity_date,
            'oldest_delivery_date' => $oldest_delivery,
            'youngest_delivery_date' => $youngest_delivery,
            'maturity_weeks' => $maturity_weeks,
            'within_redemption_window' => $is_within_window ? 1 : 0
        ),
        array(
            '%d', // user_id
            '%d', // xp_redem
            '%f', // yam_redem
            '%f', // usd_redem
            '%f', // conversion_rate_xp_yam
            '%f', // conversion_rate_yam_usd
            '%s', // meta_ids
            '%s', // status
            '%s', // payment_method
            '%s', // payment_details
            '%s', // redem_date
            '%s', // maturity_date
            '%s', // oldest_delivery_date
            '%s', // youngest_delivery_date
            '%d', // maturity_weeks
            '%d'  // within_redemption_window
        )
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to submit redemption request: ' . $wpdb->last_error);
    }
    
    $redemption_id = $wpdb->insert_id;
    
    // Update user meta records to mark them as redeemed (pending)
    $updated_meta_details = array();
    $failed_meta_ids = array();
    
    if (!empty($meta_ids)) {
        $meta_ids_array = json_decode($meta_ids, true);
        if (is_array($meta_ids_array) && !empty($meta_ids_array)) {
            $updated_count = 0;
            $error_count = 0;
            
            error_log("=== REDEMPTION DEBUG START #$redemption_id ===");
            error_log("Attempting to update " . count($meta_ids_array) . " usermeta records");
            error_log("Meta IDs array: " . print_r($meta_ids_array, true));
            
            foreach ($meta_ids_array as $umeta_id) {
                $umeta_id = intval($umeta_id);
                error_log("Processing umeta_id: $umeta_id");
                
                // Get the full usermeta row for debugging
                $meta_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT umeta_id, user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE umeta_id = %d",
                    $umeta_id
                ));
                
                if (!$meta_row) {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Record not found'
                    );
                    error_log("  ERROR: No record found for umeta_id: $umeta_id");
                    continue;
                }
                
                error_log("  Found record: user_id={$meta_row->user_id}, meta_key={$meta_row->meta_key}");
                
                // Verify ownership
                if ($meta_row->user_id != $user_id) {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Ownership mismatch'
                    );
                    error_log("  ERROR: Ownership violation - belongs to user {$meta_row->user_id}, not $user_id");
                    continue;
                }
                
                if ($meta_row->meta_value) {
                    // Try to decode as JSON first
                    $meta_data = json_decode($meta_row->meta_value, true);
                    $is_json = (json_last_error() === JSON_ERROR_NONE);
                    
                    // If not JSON, try PHP serialized data
                    if (!$is_json) {
                        error_log("  Detected PHP serialized data, attempting to unserialize");
                        $meta_data = @unserialize($meta_row->meta_value);
                        $is_serialized = ($meta_data !== false);
                        
                        if ($is_serialized && is_array($meta_data)) {
                            error_log("  Successfully unserialized PHP data");
                            error_log("  Data structure: " . print_r(array_keys($meta_data), true));
                            
                            // Handle different array structures
                            // Case 1: Array of transactions like [0 => ['order_id' => 123, ...], 1 => [...], ...]
                            // This is the case for _buyer_details, _seller_details, etc.
                            if (isset($meta_data[0]) && is_array($meta_data[0])) {
                                // It's a nested array with multiple transactions
                                error_log("  Detected array of transactions with " . count($meta_data) . " items");
                                
                                // We need to update ALL transactions in this array, not just the first one
                                $modified = false;
                                foreach ($meta_data as $index => &$transaction) {
                                    if (is_array($transaction)) {
                                        // Get the current status
                                        $current_status = isset($transaction['status']) ? $transaction['status'] : 'none';
                                        
                                        // Only update if not already requested
                                        if ($current_status !== 'requested' && $current_status !== 'processing' && $current_status !== 'completed') {
                                            $transaction['status'] = 'requested';
                                            $transaction['redemption_id'] = $redemption_id;
                                            $transaction['redemption_date'] = current_time('mysql');
                                            $modified = true;
                                            error_log("  Updated transaction #$index (order_id: " . (isset($transaction['order_id']) ? $transaction['order_id'] : 'N/A') . ")");
                                        } else {
                                            error_log("  Skipped transaction #$index (already $current_status)");
                                        }
                                    }
                                }
                                unset($transaction); // Break reference
                                
                                if ($modified) {
                                    // Keep the full array structure for saving
                                    $meta_data_to_save = $meta_data; // This is now the full array with updated transactions
                                    $meta_data = $meta_data; // Keep original structure for processing
                                    $is_json = true; // Mark as processable
                                } else {
                                    error_log("  No transactions modified - all already have status");
                                }
                            } else {
                                // Single transaction (not in an array of transactions)
                                if (!isset($meta_data['status'])) {
                                    $meta_data['status'] = 'requested';
                                }
                                $meta_data['redemption_id'] = $redemption_id;
                                $meta_data['redemption_date'] = current_time('mysql');
                                $meta_data_to_save = $meta_data;
                                $is_json = true; // Mark as processable
                            }
                        }
                    }
                    
                    if ($is_json && is_array($meta_data)) {
                        // Check if this is an array of transactions (not a single transaction)
                        $is_array_of_transactions = isset($meta_data[0]) && is_array($meta_data[0]) && !isset($meta_data['status']);
                        
                        if (!$is_array_of_transactions) {
                            // This is a single transaction, check status
                            $current_status = isset($meta_data['status']) ? $meta_data['status'] : 'none';
                            error_log("  Current status: $current_status");
                            
                            // Only skip if already in a redemption process (requested or processing)
                            // Don't skip 'completed', 'released', 'redeemed', or other statuses
                            if ($current_status === 'requested' || $current_status === 'processing') {
                                error_log("  SKIPPED: Already marked as $current_status (previous redemption)");
                                $updated_count++;
                                $updated_meta_details[] = array(
                                    'umeta_id' => $umeta_id,
                                    'meta_key' => $meta_row->meta_key,
                                    'user_id' => $meta_row->user_id,
                                    'previous_status' => $current_status,
                                    'new_status' => $current_status,
                                    'xp_awarded' => isset($meta_data['xp_awarded']) ? $meta_data['xp_awarded'] : 0,
                                    'transaction_type' => isset($meta_data['transaction_type']) ? $meta_data['transaction_type'] : 'unknown',
                                    'note' => 'Already redeemed in previous request'
                                );
                                continue; // Skip updating, already marked
                            }
                            
                            // Update the status for all other statuses
                            $meta_data['status'] = 'requested';
                            $meta_data['redemption_id'] = $redemption_id;
                            $meta_data['redemption_date'] = current_time('mysql');
                            $meta_data_to_save = $meta_data;
                        } else {
                            error_log("  Processing array of " . count($meta_data) . " transactions");
                        }
                        
                        // Determine what data to save
                        $data_to_save = isset($meta_data_to_save) ? $meta_data_to_save : $meta_data;
                        
                        // Check if original data was serialized PHP format
                        $was_serialized = (strpos($meta_row->meta_value, 'a:') === 0);
                        
                        // Update the meta value 
                        if ($was_serialized) {
                            // Save as serialized PHP to maintain compatibility
                            $updated_value = serialize($data_to_save);
                            error_log("  Saving as PHP serialized format");
                        } else {
                            // Save as JSON
                            $updated_value = json_encode($data_to_save);
                            error_log("  Saving as JSON format");
                        }
                        
                        $update_result = $wpdb->update(
                            $wpdb->usermeta,
                            array('meta_value' => $updated_value),
                            array('umeta_id' => $umeta_id),
                            array('%s'),
                            array('%d')
                        );
                        
                        if ($update_result !== false) {
                            $updated_count++;
                            // Extract data from various possible structures
                            $xp_awarded = 0;
                            $transaction_count = 0;
                            
                            if (isset($meta_data['xp_awarded'])) {
                                // Single transaction
                                $xp_awarded = $meta_data['xp_awarded'];
                                $transaction_count = 1;
                            } elseif (isset($meta_data[0]) && is_array($meta_data[0])) {
                                // Array of transactions
                                $xp_awarded = 0;
                                $transaction_count = count($meta_data);
                                foreach ($meta_data as $trans) {
                                    if (isset($trans['xp_awarded'])) {
                                        $xp_awarded += intval($trans['xp_awarded']);
                                    }
                                }
                            }
                            
                            $transaction_type = 'unknown';
                            if (isset($meta_data['transaction_type'])) {
                                $transaction_type = $meta_data['transaction_type'];
                            } elseif (isset($meta_data['order_id'])) {
                                $transaction_type = 'order';
                            } elseif (isset($meta_data[0]['transaction_type'])) {
                                $transaction_type = $meta_data[0]['transaction_type'];
                            }
                            
                            $current_status_for_log = isset($current_status) ? $current_status : 'none';
                            
                            $updated_meta_details[] = array(
                                'umeta_id' => $umeta_id,
                                'meta_key' => $meta_row->meta_key,
                                'user_id' => $meta_row->user_id,
                                'previous_status' => $current_status_for_log,
                                'new_status' => 'requested',
                                'xp_awarded' => $xp_awarded,
                                'transaction_type' => $transaction_type,
                                'transaction_count' => $transaction_count
                            );
                            
                            if ($transaction_count > 1) {
                                error_log("  SUCCESS: Updated umeta_id $umeta_id with $transaction_count transactions");
                            } else {
                                error_log("  SUCCESS: Updated umeta_id $umeta_id");
                            }
                        } else {
                            $error_count++;
                            $failed_meta_ids[] = array(
                                'umeta_id' => $umeta_id,
                                'error' => $wpdb->last_error
                            );
                            error_log("  ERROR: Failed to update - " . $wpdb->last_error);
                        }
                    } else {
                        $error_count++;
                        $failed_meta_ids[] = array(
                            'umeta_id' => $umeta_id,
                            'error' => 'Invalid JSON data',
                            'raw_data' => substr($meta_row->meta_value, 0, 100) // First 100 chars for debugging
                        );
                        error_log("  ERROR: Invalid JSON data for umeta_id $umeta_id");
                        error_log("  RAW DATA: " . substr($meta_row->meta_value, 0, 200));
                        error_log("  JSON ERROR: " . json_last_error_msg());
                    }
                } else {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Empty meta_value'
                    );
                    error_log("  ERROR: Empty meta_value");
                }

            }
            
            // Log the update results
            error_log("=== REDEMPTION DEBUG END #$redemption_id ===");
            error_log("SUMMARY: Updated $updated_count records, $error_count errors");
        } else {
            error_log("Redemption #$redemption_id: Invalid meta_ids format - " . $meta_ids);
        }
    } else {
        error_log("Redemption #$redemption_id: No meta_ids provided");
    }
    
    // Send notification email to admin (optional)
    $admin_email = get_option('admin_email');
    $user = get_userdata($user_id);
    $subject = 'New Redemption Request #' . $redemption_id;
    $message = "A new redemption request has been submitted:\n\n";
    $message .= "Redemption ID: #" . $redemption_id . "\n";
    $message .= "User: " . $user->display_name . " (" . $user->user_email . ")\n";
    $message .= "XP Amount: " . number_format($xp_amount) . "\n";
    $message .= "YAM Amount: " . number_format($yam_amount, 2) . "\n";
    $message .= "USD Amount: $" . number_format($usd_amount, 2) . "\n";
    $message .= "Payment Method: " . $payment_method . "\n";
    $message .= "Payment Details: " . $payment_details . "\n";
    $message .= "Date: " . current_time('Y-m-d H:i:s') . "\n\n";
    $message .= "Please review and process this request.";
    
    wp_mail($admin_email, $subject, $message);
    
    // Return success response with debug information
    wp_send_json_success(array(
        'message' => 'Redemption request submitted successfully!',
        'redemption_id' => $redemption_id,
        'status' => 'pending',
        'debug' => array(
            'updated_count' => count($updated_meta_details),
            'failed_count' => count($failed_meta_ids),
            'updated_meta_details' => $updated_meta_details,
            'failed_meta_ids' => $failed_meta_ids
        )
    ));
}

/**
 * Shortcode to display XP dashboard
 */
function dongtrader_xp_dashboard_shortcode($atts) {
    return dongtrader_display_xp_dashboard();
}
add_shortcode('dongtrader_xp_dashboard', 'dongtrader_xp_dashboard_shortcode');

/**
 * Add XP dashboard to WooCommerce account page
 */
function dongtrader_add_xp_to_account_page() {
    if (is_account_page() && is_user_logged_in()) {
        echo dongtrader_display_xp_dashboard();
    }
}
add_action('woocommerce_account_dashboard', 'dongtrader_add_xp_to_account_page', 20);

/**
 * Test function to manually award XP (for testing purposes)
 * Usage: Add ?test_xp=1 to any page URL when logged in as admin
 */
function dongtrader_test_xp_award() {
    // Only allow admins to test
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['test_xp']) && $_GET['test_xp'] == '1') {
        $user_id = get_current_user_id();
        $dong_user_role = get_user_meta($user_id, 'dong_user_role', true);
        
        // Determine if user is buyer or seller
        $is_seller = in_array($dong_user_role, array('Planning', 'Budget', 'Media', 'Distribution', 'Membership'));
        
        $seller_xp = dongtrader_usd_to_xp(1); // 1 USD worth of XP for sellers
        $buyer_xp = dongtrader_usd_to_xp(10); // 10 USD worth of XP for buyers
        
        $xp_to_award = $is_seller ? $seller_xp : $buyer_xp;
        
        // Create test transaction
        $xp_transaction = array(
            'xp_awarded' => $xp_to_award,
            'transaction_type' => 'test_transaction',
            'phone_number' => 'test',
            'verification_date' => current_time('mysql'),
            'user_role' => $dong_user_role,
            'status' => 'completed'
        );
        
        if ($is_seller) {
            // Save to seller_details
            $seller_details = get_user_meta($user_id, 'seller_details', true);
            if (!is_array($seller_details)) {
                $seller_details = array();
            }
            $seller_details[] = $xp_transaction;
            update_user_meta($user_id, 'seller_details', $seller_details);
            
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Test XP Awarded!</strong> ' . number_format($seller_xp) . ' XP added to seller_details for user ID: ' . $user_id;
            echo '</div>';
        } else {
            // Save to buyer_details
            $buyer_details = get_user_meta($user_id, 'buyer_details', true);
            if (!is_array($buyer_details)) {
                $buyer_details = array();
            }
            $buyer_details[] = $xp_transaction;
            update_user_meta($user_id, 'buyer_details', $buyer_details);
            
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Test XP Awarded!</strong> ' . number_format($buyer_xp) . ' XP added to buyer_details for user ID: ' . $user_id;
            echo '</div>';
        }
        
        // Update total XP balance
        $total_xp = get_user_meta($user_id, '_total_xp_balance', true);
        $total_xp = $total_xp ? intval($total_xp) : 0;
        $total_xp += $xp_to_award;
        update_user_meta($user_id, '_total_xp_balance', $total_xp);
    }
}
add_action('wp_head', 'dongtrader_test_xp_award');


/**
 * Add redemption popup JavaScript to footer
 */
function dongtrader_redemption_popup_script() {
    // Only load on account pages
    if (!is_account_page() || !is_user_logged_in()) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    console.log('Redemption popup script loaded');
    
    // Generate nonce for AJAX calls
    var ajaxNonce = '<?php echo wp_create_nonce('get_xp_umeta_ids'); ?>';
    var ajaxUserId = '<?php echo get_current_user_id(); ?>';
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Store redemption data globally
    window.currentRedemptionData = {};
    
    // Redemption popup functions
    window.showRedemptionPopup = function(xpAmount, yamAmount, usdAmount, xpPerYam, yamPerUsd) {
        console.log("showRedemptionPopup called");
        
        // Store the original values globally
        window.currentRedemptionData = {
            xpAmount: xpAmount,
            yamAmount: yamAmount,
            usdAmount: usdAmount,
            xpPerYam: xpPerYam,
            yamPerUsd: yamPerUsd
        };
        
        console.log("Stored redemption data:", window.currentRedemptionData);
        
        // Update main redemption data
        var popupXpAmount = document.getElementById("popup-xp-amount");
        var popupYamAmount = document.getElementById("popup-yam-amount");
        var popupUsdAmount = document.getElementById("popup-usd-amount");
        var popupXpYamRate = document.getElementById("popup-xp-yam-rate");
        var popupYamUsdRate = document.getElementById("popup-yam-usd-rate");
        
        if (popupXpAmount) popupXpAmount.textContent = xpAmount.toLocaleString();
        if (popupYamAmount) popupYamAmount.textContent = yamAmount.toLocaleString();
        if (popupUsdAmount) popupUsdAmount.textContent = "$" + usdAmount.toLocaleString();
        if (popupXpYamRate) popupXpYamRate.textContent = xpPerYam.toLocaleString();
        if (popupYamUsdRate) popupYamUsdRate.textContent = yamPerUsd.toLocaleString();
        
        // Get umeta_id values via AJAX
        console.log("Making AJAX call to get umeta_ids");
        
        var ajaxData = {
            action: "get_xp_umeta_ids",
            user_id: ajaxUserId,
            nonce: ajaxNonce
        };
        
        jQuery.ajax({
            url: ajaxUrl,
            type: "POST",
            data: ajaxData,
            success: function(response) {
                    var metaIdsDisplay = document.getElementById("popup-meta-ids");
                if (!metaIdsDisplay) return;
                
                if (response && response.success === true && response.data) {
                    var umetaIds = response.data.umeta_ids || [];
                    var count = response.data.count || 0;
                    
                    // Store meta IDs globally for form submission
                    window.currentRedemptionData.metaIds = umetaIds;
                    
                    console.log('Meta IDs retrieved:', umetaIds);
                    
                    if (count > 0) {
                        metaIdsDisplay.textContent = "Found " + count + " meta IDs: " + JSON.stringify(umetaIds);
                            metaIdsDisplay.style.color = "#28a745";
                        } else {
                            metaIdsDisplay.textContent = "No meta IDs found for this user";
                            metaIdsDisplay.style.color = "#ffc107";
                    }
                } else {
                    metaIdsDisplay.textContent = "Error: " + (response.data || "Unknown error");
                        metaIdsDisplay.style.color = "#dc3545";
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", xhr, status, error);
                var metaIdsDisplay = document.getElementById("popup-meta-ids");
                if (metaIdsDisplay) {
                    metaIdsDisplay.textContent = "Error loading meta IDs: " + error;
                    metaIdsDisplay.style.color = "#dc3545";
                }
            }
        });
        
        // Show popup
        document.getElementById("redemption-popup").style.display = "flex";
        document.body.style.overflow = "hidden";
        
        // Add event listeners for real-time updates
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (paymentMethodEl) paymentMethodEl.addEventListener("change", updatePaymentDisplay);
        if (paymentDetailsEl) paymentDetailsEl.addEventListener("input", updatePaymentDisplay);
    };
    
    window.closeRedemptionPopup = function() {
        document.getElementById("redemption-popup").style.display = "none";
        document.body.style.overflow = "auto";
        
        // Clear form fields
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (paymentMethodEl) paymentMethodEl.value = "";
        if (paymentDetailsEl) paymentDetailsEl.value = "";
    };
    
    window.updatePaymentDisplay = function() {
        var paymentMethod = document.getElementById("payment-method").value;
        var paymentDetails = document.getElementById("payment-details").value.trim();
        
        var methodDisplay = document.getElementById("popup-payment-method-display");
        var detailsDisplay = document.getElementById("popup-payment-details-display");
        if (methodDisplay) methodDisplay.textContent = paymentMethod || "Not selected";
        if (detailsDisplay) detailsDisplay.textContent = paymentDetails || "Not provided";
    };
    
    // Wrap everything in jQuery ready
    jQuery(document).ready(function($) {
    
    // Simple test function
    window.testFunction = function() {
        alert('Test function works!');
    };
    
    // Test nonce function
    window.testNonce = function() {
        console.log('Testing nonce...');
        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_xp_umeta_ids',
                user_id: ajaxUserId,
                nonce: ajaxNonce
            },
            success: function(response) {
                console.log('Nonce test response:', response);
                alert('Nonce test: ' + JSON.stringify(response));
            },
            error: function(xhr, status, error) {
                console.log('Nonce test error:', xhr, status, error);
                alert('Nonce test error: ' + error);
            }
        });
    };
    
    // Make functions globally available immediately
    window.testAjax = function() {
        console.log('Testing basic AJAX...');
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'test_ajax'
            },
            success: function(response) {
                console.log('Test AJAX response:', response);
                document.getElementById('ajax-test-result').style.display = 'block';
                document.getElementById('ajax-test-result').innerHTML = 'SUCCESS: ' + JSON.stringify(response);
                document.getElementById('ajax-test-result').style.background = '#d4edda';
                document.getElementById('ajax-test-result').style.color = '#155724';
            },
            error: function(xhr, status, error) {
                console.log('Test AJAX error:', xhr, status, error);
                document.getElementById('ajax-test-result').style.display = 'block';
                document.getElementById('ajax-test-result').innerHTML = 'ERROR: ' + error;
                document.getElementById('ajax-test-result').style.background = '#f8d7da';
                document.getElementById('ajax-test-result').style.color = '#721c24';
            }
        });
    };
    
    
    window.updatePaymentDisplay = function() {
        var paymentMethod = document.getElementById("payment-method").value;
        var paymentDetails = document.getElementById("payment-details").value.trim();
        
        var methodDisplay = document.getElementById("popup-payment-method-display");
        var detailsDisplay = document.getElementById("popup-payment-details-display");
        if (methodDisplay) methodDisplay.textContent = paymentMethod || "Not selected";
        if (detailsDisplay) detailsDisplay.textContent = paymentDetails || "Not provided";
    }
    
    window.closeRedemptionPopup = function() {
        document.getElementById("redemption-popup").style.display = "none";
        document.body.style.overflow = "auto";
        // Clear form data
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (paymentMethodEl) paymentMethodEl.value = "";
        if (paymentDetailsEl) paymentDetailsEl.value = "";
    }
    
    window.submitRedemptionRequest = function() {
        var paymentMethod = document.getElementById("payment-method").value;
        var paymentDetails = document.getElementById("payment-details").value.trim();
        
        if (!paymentMethod) {
            alert("Please select a payment method.");
            return;
        }
        
        if (!paymentDetails) {
            alert("Please enter your payment details.");
            return;
        }
        
        // Get the redemption data from stored values
        if (!window.currentRedemptionData) {
            alert('Error: Redemption data not found. Please close and reopen the popup.');
            console.error('currentRedemptionData is undefined');
            return;
        }
        
        var xpAmount = window.currentRedemptionData.xpAmount;
        var yamAmount = window.currentRedemptionData.yamAmount;
        var usdAmount = window.currentRedemptionData.usdAmount;
        var xpPerYam = window.currentRedemptionData.xpPerYam;
        var yamPerUsd = window.currentRedemptionData.yamPerUsd;
        // Get meta IDs from stored data
        var metaIds = window.currentRedemptionData.metaIds || [];
        
        console.log('Using stored redemption data:', {
            xpAmount: xpAmount,
            yamAmount: yamAmount,
            usdAmount: usdAmount,
            xpPerYam: xpPerYam,
            yamPerUsd: yamPerUsd,
            metaIds: metaIds,
            metaIdsCount: metaIds.length
        });
        
        // Validate meta IDs
        if (!metaIds || metaIds.length === 0) {
            alert('Warning: No usermeta records found to redeem. Please refresh and try again.');
            console.error('No meta IDs available for redemption');
            return;
        }
        
        // Show redemption amounts in alert
        alert('Redemption Request Details:\\n\\n' +
              'XP Amount: ' + xpAmount.toLocaleString() + '\\n' +
              'YAM Amount: ' + yamAmount.toLocaleString() + '\\n' +
              'USD Amount: $' + usdAmount.toLocaleString() + '\\n\\n' +
              'Payment Method: ' + paymentMethod + '\\n' +
              'Payment Details: ' + paymentDetails + '\\n\\n' +
              'Submitting request...');
        
        // Validate stored values
        if (!xpAmount || !yamAmount || !usdAmount || xpAmount <= 0 || yamAmount <= 0 || usdAmount <= 0) {
            alert('Error: Invalid redemption amounts detected. Please try again.');
            console.error('Invalid amounts:', { xpAmount, yamAmount, usdAmount });
            return;
        }
        
        // Prepare AJAX data
        var ajaxData = {
            action: 'submit_redemption_request',
            xp_amount: xpAmount,
            yam_amount: yamAmount,
            usd_amount: usdAmount,
            xp_per_yam: xpPerYam,
            yam_per_usd: yamPerUsd,
            payment_method: paymentMethod,
            payment_details: paymentDetails,
            meta_ids: JSON.stringify(metaIds), // Convert array to JSON string
            nonce: ajaxNonce
        };
        
        console.log('Submitting redemption request:', ajaxData);
        console.log('Meta IDs being sent:', metaIds);
        
        // Show loading state
        var submitBtn = document.querySelector('button[onclick="submitRedemptionRequest()"]');
        var originalText = submitBtn.textContent;
        submitBtn.textContent = 'Submitting...';
        submitBtn.disabled = true;
        
        // Make AJAX call
        jQuery.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('Redemption submission response:', response);
                
                if (response && response.success) {
                    // Build success message
                    var msg = 'Redemption request submitted successfully!\\n\\n';
                    msg += 'Request ID: #' + response.data.redemption_id + '\\n';
                    msg += 'Status: ' + response.data.status + '\\n\\n';
                    
                    // Add debug information if available
                    if (response.data.debug) {
                        console.log('=== USQMETA UPDATE DEBUG ===');
                        console.log('Updated Records:', response.data.debug.updated_count);
                        console.log('Failed Records:', response.data.debug.failed_count);
                        console.log('\\nUpdated Usermeta Details:');
                        if (response.data.debug.updated_meta_details && response.data.debug.updated_meta_details.length > 0) {
                            response.data.debug.updated_meta_details.forEach(function(meta) {
                                console.log('  - Umeta ID: ' + meta.umeta_id);
                                console.log('    Meta Key: ' + meta.meta_key);
                                console.log('    User ID: ' + meta.user_id);
                                console.log('    Status: ' + meta.previous_status + ' → ' + meta.new_status);
                                console.log('    XP Awarded: ' + meta.xp_awarded);
                                console.log('    Transaction Type: ' + meta.transaction_type);
                                console.log('    ---');
                            });
                        } else {
                            console.log('  No usermeta records were updated.');
                        }
                        
                        if (response.data.debug.failed_meta_ids && response.data.debug.failed_meta_ids.length > 0) {
                            console.log('\\nFailed Usermeta IDs:');
                            response.data.debug.failed_meta_ids.forEach(function(failed) {
                                console.log('  - Umeta ID: ' + failed.umeta_id + ' - Error: ' + failed.error);
                            });
                        }
                        
                        msg += 'Debug Info:\\n';
                        msg += 'Updated: ' + response.data.debug.updated_count + ' records\\n';
                        msg += 'Failed: ' + response.data.debug.failed_count + ' records\\n\\n';
                        msg += 'See browser console for detailed usermeta update information.';
                    }
                    
                    msg += '\\n\\nYou will be contacted within 24-48 hours.';
                    
                    alert(msg);
        closeRedemptionPopup();
                } else {
                    alert('Error submitting redemption request: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', xhr, status, error);
                alert('Error submitting redemption request: ' + error);
            },
            complete: function() {
                // Restore button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });
    }
    
    // Close popup when clicking outside
    $(document).on("click", "#redemption-popup", function(event) {
        if (event.target === this) {
            closeRedemptionPopup();
        }
    });
    
    }); // End of jQuery ready
    </script>
    <?php
}
add_action('wp_footer', 'dongtrader_redemption_popup_script');

