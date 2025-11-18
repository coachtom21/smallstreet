<?php
require_once plugin_dir_path(__FILE__) . 'twilio-php-master/src/Twilio/autoload.php';

use Twilio\Rest\Client;

//client account
define('ACCOUNT_SID', 'ACc6980bcb6f2b6c2616e29c9bb382fc25');
define('AUTH_TOKEN', 'b07162f3a65194b5bfe5cb9182103a5d');
define('APP_SID', 'VA597048a004f28f441a60e510b72c0c0d');


add_shortcode('cpm_twilio_otp', 'ct_twilio_otp_fields');
function ct_twilio_otp_fields($atts)
{

    $atts = shortcode_atts(
        array(
            'shadow' => 'no',
        ),
        $atts,
        'bartag'
    );

    if (is_user_logged_in()) {
        return '[user_already_logged_in]';
    }

    $shadow_class = '';
    if ($atts['shadow'] == 'yes') {
        $shadow_class = 'twilio-otp-box-shadow';
    }

    $nonce_verify_phone_num = wp_create_nonce('ct_verify_user_phone_number');

    return '
    <form method="post" class="twilio-otp-form ' . $shadow_class . '">
        
        <input type="hidden" value="' . $nonce_verify_phone_num . '" id="phone_num_verification_nonce">

        <div class="form-msg"> </div>

        <div class="cpm_otp_field_group cpm_phone_group">
            <img src=" ' . esc_url(CPM_TWILIO_PLUGIN_URL . '/assets/images/lock.svg') . '" alt="Lock Icon">
            <label for="phone">Your Phone Number</label>
            <span id="phone_numberr">
            <i class="fa-solid fa-phone"></i>

            <input type="text" id="otp_phone_num" name="otp_phone_num" required pattern="\d*" inputmode="numeric"></span>
            
            <div class="verify_otp_btn_container">
                <button class="btn" id="send_otp">Send OTP</button>
                <button class="btn" id="phone_retry">Try Again<i class="fa-solid fa-rotate-right"></i></button>
            </div>
        </div>
        
        <div class="cpm_otp_field_group cpm_otp_group">
            <img src=" ' . esc_url(CPM_TWILIO_PLUGIN_URL . '/assets/images/lock-open-fill.svg') . '" alt="Lock Icon">
            <label for="phone">Enter OTP</label>
            <div id="otp">
                <input type="text" id="otp1" maxlength="1" class="otp-input" required>
                <input type="text" id="otp2" maxlength="1" class="otp-input" required>
                <input type="text" id="otp3" maxlength="1" class="otp-input" required>
                <input type="text" id="otp4" maxlength="1" class="otp-input" required>
                <input type="text" id="otp5" maxlength="1" class="otp-input" required>
                <input type="text" id="otp6" maxlength="1" class="otp-input" required>
            </div>

            <div class="verify_otp_btn_container">
                <button class="btn" id="validate_otp">Validate OTP</button>
                <button class="btn" id="otp_retry">Try Again<i class="fa-solid fa-rotate-right"></i></button>
            </div>
        </div>
    </form>
    ';
}


add_action('wp_ajax_ct_verify_user_phone_number', 'ct_verify_user_phone_number'); // For logged-in users
add_action('wp_ajax_nopriv_ct_verify_user_phone_number', 'ct_verify_user_phone_number'); // For non-logged-in users
function ct_verify_user_phone_number()
{
    //check nonce
    if (!(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ct_verify_user_phone_number'))) {
        wp_send_json_success(["nonce_fail", '']);
        wp_die();
    }

    $phone_number = sanitize_text_field($_POST['phone_number']);

    //check if phone number contains all digits with a length of 10
    if (!(preg_match('/^\d+$/', $phone_number)) || (strlen($phone_number) != 10)) {
        wp_send_json_success(["invalid_phone", '0']);
        wp_die();
    }

    $args = array(
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'mega-mobile',
                'value' => $phone_number,
                'compare' => 'LIKE'
            )
        )
    );

    $exists = get_users($args);

    if (!empty($exists)) {
        //create nonce for sending OTP action
        wp_send_json_success(['valid_phone', $exists[0]->ID, wp_create_nonce('ct_send_twilio_otp')]);
    } else {
        wp_send_json_success(["invalid_phone", '0']);
    }

    wp_die();
}


add_action('wp_ajax_ct_send_twilio_otp', 'ct_send_twilio_otp');
add_action('wp_ajax_nopriv_ct_send_twilio_otp', 'ct_send_twilio_otp');
function ct_send_twilio_otp()
{
    // wp_send_json_success(["otp_sent", '', wp_create_nonce('ct_validate_twilio_otp')]);
    //check nonce
    if (!(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ct_send_twilio_otp'))) {
        wp_send_json_success(["nonce_fail", '']);
        wp_die();
    }

    $phone_number = $_POST['phone_number'];

    $country_code = ct_get_user_country_code($phone_number);


    // var_dump($country_code);
    // die();
    if (!empty($country_code) && $country_code == 'NP') {
        $country_code = '+977';
    } else {
        $country_code = '+1';
    }
    // $country_code = '+977';

    try {
        $twilio = new Client(ACCOUNT_SID, AUTH_TOKEN);

        $verification = $twilio->verify->v2->services(APP_SID)
            ->verifications
            ->create($country_code . $phone_number, "sms");

        if ($verification->status == "pending") {
            wp_send_json_success(["otp_sent", '', wp_create_nonce('ct_validate_twilio_otp')]);
        } else {
            wp_send_json_error(["otp_failed", serialize($verification)]);
        }
    } catch (\Twilio\Exceptions\TwilioException $e) {
        wp_send_json_error(["otp_failed", $e->getMessage()]);
    }

    wp_die();
}

add_action('wp_ajax_ct_validate_twilio_otp', 'ct_validate_twilio_otp'); // For logged-in users
add_action('wp_ajax_nopriv_ct_validate_twilio_otp', 'ct_validate_twilio_otp'); // For non-logged-in users
function ct_validate_twilio_otp()
{
    // wp_send_json_success(["valid_otp", wp_create_nonce('ct_user_signin')]);
    //check nonce
    if (!(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ct_validate_twilio_otp'))) {
        wp_send_json_success(["nonce_fail", '']);
        wp_die();
    }

    $phoneNumber = sanitize_text_field($_POST['phone_number']);
    $otp = sanitize_text_field($_POST['otp']);

    $country_code = ct_get_user_country_code($phoneNumber);
    if (!empty($country_code) && $country_code == 'NP') {
        $country_code = '+977';
    } else {
        $country_code = '+1';
    }
    // $country_code = '+977';



    $twilio = new Client(ACCOUNT_SID, AUTH_TOKEN);

    $verification_check = $twilio->verify->v2->services(APP_SID)
        ->verificationChecks
        ->create(
            [
                "to" => $country_code . $phoneNumber,
                "code" => $otp
            ]
        );

    if ($verification_check->status == "approved") {
        // Check URL for debugging - support both old format (redirect=qr) and new format (scan_type)
        $current_url = $_SERVER['HTTP_REFERER'] ?? '';
        $redirect_qr = strpos($current_url, 'redirect=qr') !== false;
        
        // Check for new QR code format with scan_type
        $has_scan_type = isset($_GET['scan_type']) || strpos($current_url, 'scan_type=') !== false;
        $is_qr_scan = ($has_scan_type && isset($_GET['scan_type']) && $_GET['scan_type'] === 'proof') || $redirect_qr;

        // Get user role for debugging
        $user_role = 'Not found';
        $args = array(
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'mega-mobile',
                    'value' => $phoneNumber,
                    'compare' => 'LIKE'
                )
            )
        );

        $users = get_users($args);
        if (!empty($users)) {
            $user_id = $users[0]->ID;
            $dong_user_role = get_user_meta($user_id, 'dong_user_role', true);
            $user_role = $dong_user_role ?: 'No role set';

            // Check if user is seller or buyer based on having seller transactions
            $seller_details = get_user_meta($user_id, '_seller_details', true);
            $is_seller = is_array($seller_details) && !empty($seller_details);
            $user_type = $is_seller ? 'SELLER' : 'BUYER';
        } else {
            $user_type = 'USER NOT FOUND';
        }

        // Handle new QR scan flow with scan_type=proof
        // Note: This is legacy code - actual scan data insertion now happens via ct_insert_scan_data AJAX
        // This section is kept for backward compatibility but scan data is now inserted after OTP via AJAX
        $product_already_scanned = false; // Initialize variable
        if ($is_qr_scan) {
            try {
                // Get scan_type if available
                $scan_type = isset($_GET['scan_type']) ? sanitize_text_field($_GET['scan_type']) : null;
                
                // If not in GET, try to extract from referrer URL
                if (!$scan_type && preg_match('/scan_type=(\w+)/', $current_url, $matches)) {
                    $scan_type = sanitize_text_field($matches[1]);
                }

                // Get role and proof_poc from POST data (from popup selections)
                $proof_poc = isset($_POST['proof_poc']) && $_POST['proof_poc'] === 'yes' ? true : false;
                $role = isset($_POST['user_role']) ? sanitize_text_field($_POST['user_role']) : '';
                
                // Note: Duplicate checking and data insertion now handled in ct_insert_scan_data AJAX function
                // This section is kept minimal for backward compatibility
                if ($proof_poc && !empty($role) && $scan_type === 'proof') {
                    error_log('QR Scan: OTP verified for user ' . $user_id . ' with role ' . $role . ', scan_type: ' . $scan_type);
                    // Actual data insertion happens via ct_insert_scan_data AJAX after OTP verification
                }
            } catch (Exception $e) {
                // Log error but don't break the OTP verification
                error_log('QR Scan error: ' . $e->getMessage());
            }
        }

        // Return success with warning message if product is already scanned, but still allow login
        if ($product_already_scanned) {
            wp_send_json_success(["valid_otp", wp_create_nonce('ct_user_signin'), "warning", "Product qr is already scanned"]);
        } else {
        wp_send_json_success(["valid_otp", wp_create_nonce('ct_user_signin')]);
        }
    } else {
        wp_send_json_success(["invalid_otp"]);
    }

    wp_die();
}


add_action('wp_ajax_ct_user_signin', 'ct_user_signin'); // For logged-in users
add_action('wp_ajax_nopriv_ct_user_signin', 'ct_user_signin'); // For non-logged-in users
function ct_user_signin()
{
    // wp_send_json_success(["logged_in"]);

    //check nonce
    if (!(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ct_user_signin'))) {
        wp_send_json_success(["nonce_fail", '']);
        wp_die();
    }

    $user_id = $_POST['userId'];

    if (is_wp_error($user_id)) {
        wp_send_json_error([
            'success' => false,
            'message' => is_wp_error($user_id)
        ]);
    } else {
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_send_json_success(["logged_in"]);
    }

    wp_die();
}

// AJAX handler to check Discord membership
add_action('wp_ajax_ct_check_discord_membership', 'ct_check_discord_membership');
add_action('wp_ajax_nopriv_ct_check_discord_membership', 'ct_check_discord_membership');
function ct_check_discord_membership() {
    // Nonce verification is optional for this endpoint since user_id is required
    // and we're only reading data, not modifying
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User ID required']);
        wp_die();
    }
    
    // Check if user has Discord user ID in meta
    $discord_user_id = get_user_meta($user_id, 'discord_user_id', true);
    $discord_join = !empty($discord_user_id);
    
    wp_send_json_success([
        'discord_join' => $discord_join,
        'user_id' => $user_id,
        'discord_user_id' => $discord_user_id ?: null
    ]);
    
    wp_die();
}

// AJAX handler to check if proof_id exists in seller_scan
add_action('wp_ajax_ct_check_proof_id', 'ct_check_proof_id');
add_action('wp_ajax_nopriv_ct_check_proof_id', 'ct_check_proof_id');
function ct_check_proof_id() {
    $proof_id = isset($_POST['proof_id']) ? sanitize_text_field($_POST['proof_id']) : '';
    
    if (empty($proof_id)) {
        wp_send_json_error(['message' => 'Proof ID required']);
        wp_die();
    }
    
    // Search for proof_id in seller_scan across all users
    global $wpdb;
    $meta_key = 'seller_scan';
    
    // Get all users with seller_scan meta
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $meta_key
    ));
    
    $matched_seller_id = null;
    $matched_entry_index = null;
    $matched_data = null;
    
    foreach ($results as $result) {
        $scan_data = maybe_unserialize($result->meta_value);
        if (is_array($scan_data)) {
            foreach ($scan_data as $index => $entry) {
                if (isset($entry['proof_id']) && $entry['proof_id'] == $proof_id) {
                    $matched_seller_id = $result->user_id;
                    $matched_entry_index = $index;
                    $matched_data = $entry;
                    break 2; // Break out of both loops
                }
            }
        }
    }
    
    if ($matched_seller_id) {
        wp_send_json_success([
            'found' => true,
            'seller_id' => $matched_seller_id,
            'entry_index' => $matched_entry_index,
            'seller_data' => $matched_data
        ]);
    } else {
        wp_send_json_success([
            'found' => false,
            'message' => 'No order found with this proof ID'
        ]);
    }
    
    wp_die();
}

// AJAX handler to verify transaction code for buyers
add_action('wp_ajax_ct_verify_transaction_code', 'ct_verify_transaction_code');
add_action('wp_ajax_nopriv_ct_verify_transaction_code', 'ct_verify_transaction_code');
function ct_verify_transaction_code() {
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'ct_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }
    
    $transaction_code = isset($_POST['transaction_code']) ? sanitize_text_field($_POST['transaction_code']) : '';
    
    if (empty($transaction_code)) {
        wp_send_json_error(['message' => 'Transaction code is required']);
        wp_die();
    }
    
    // Search for transaction_id in seller_scan across all users
    global $wpdb;
    $meta_key = 'seller_scan';
    
    // Get all users with seller_scan meta
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
        $meta_key
    ));
    
    $matched_seller_id = null;
    $matched_entry_index = null;
    $matched_data = null;
    
    foreach ($results as $result) {
        $scan_data = maybe_unserialize($result->meta_value);
        if (is_array($scan_data)) {
            foreach ($scan_data as $index => $entry) {
                if (isset($entry['transaction_id']) && $entry['transaction_id'] == $transaction_code) {
                    $matched_seller_id = $result->user_id;
                    $matched_entry_index = $index;
                    $matched_data = $entry;
                    break 2; // Break out of both loops
                }
            }
        }
    }
    
    if ($matched_seller_id) {
        error_log('Transaction code verified: ' . $transaction_code . ' found in seller_scan for user ' . $matched_seller_id);
        wp_send_json_success([
            'verified' => true,
            'seller_id' => $matched_seller_id,
            'entry_index' => $matched_entry_index,
            'seller_data' => $matched_data
        ]);
    } else {
        error_log('Transaction code not found: ' . $transaction_code);
        wp_send_json_error([
            'verified' => false,
            'message' => 'Invalid transaction code. Please check the code and try again.'
        ]);
    }
    
    wp_die();
}

// AJAX handler to insert scan data to usermeta
add_action('wp_ajax_ct_insert_scan_data', 'ct_insert_scan_data');
add_action('wp_ajax_nopriv_ct_insert_scan_data', 'ct_insert_scan_data');
function ct_insert_scan_data() {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User ID required']);
        wp_die();
    }
    
    // Get scan data from POST
    $scan_data_json = isset($_POST['scan_data']) ? $_POST['scan_data'] : '';
    $scan_data = json_decode(stripslashes($scan_data_json), true);
    
    if (!$scan_data || !is_array($scan_data)) {
        wp_send_json_error(['message' => 'Invalid scan data']);
        wp_die();
    }
    
    // Determine meta key based on role
    $role = isset($scan_data['role']) ? strtolower(trim($scan_data['role'])) : '';
    $meta_key = '';
    
    error_log('=== Role Determination ===');
    error_log('Raw role from scan_data: ' . (isset($scan_data['role']) ? $scan_data['role'] : 'NOT SET'));
    error_log('Lowercased role: ' . $role);
    
    if (strpos($role, 'seller') !== false) {
        $meta_key = 'seller_scan';
        error_log('Role matched: SELLER -> seller_scan');
    } elseif (strpos($role, 'buyer') !== false) {
        $meta_key = 'buyer_scan';
        error_log('Role matched: BUYER -> buyer_scan');
    } elseif (strpos($role, 'personal') !== false) {
        $meta_key = 'personal_scan';
        error_log('Role matched: PERSONAL -> personal_scan');
    }
    
    if (empty($meta_key)) {
        error_log('ERROR: No meta_key determined. Role was: "' . $role . '"');
        wp_send_json_error(['message' => 'Invalid role: ' . $role]);
        wp_die();
    }
    
    error_log('Final meta_key: ' . $meta_key);
    error_log('========================');
    
    // Get seller info from POST (for buyer role to update seller's entry)
    $seller_id = isset($_POST['seller_id']) ? intval($_POST['seller_id']) : null;
    $seller_entry_index = isset($_POST['seller_entry_index']) ? intval($_POST['seller_entry_index']) : null;
    
    // Check if transaction_id already exists for this user (duplicate prevention)
    $transaction_id = isset($scan_data['transaction_id']) ? $scan_data['transaction_id'] : '';
    $matched_seller_id = null;
    
    if (!empty($transaction_id)) {
        // Get existing scan data for this user
        $existing_scan_data = get_user_meta($user_id, $meta_key, true);
        if (is_array($existing_scan_data)) {
            foreach ($existing_scan_data as $entry) {
                if (isset($entry['transaction_id']) && $entry['transaction_id'] == $transaction_id) {
                    // Transaction ID already exists for this user, return error
                    wp_send_json_error(['message' => 'This transaction has already been recorded']);
                        wp_die();
                        return;
                }
            }
        }
    }
    
    // If buyer role and seller info provided, update seller's entry
    if (strpos($role, 'buyer') !== false && $seller_id && $seller_entry_index !== null) {
        $matched_seller_id = $seller_id;
        error_log('Buyer scan detected - will update seller entry. Seller ID: ' . $seller_id . ', Entry Index: ' . $seller_entry_index);
    }
    
    // Get existing scan data or create new array
    $existing_scan_data = get_user_meta($user_id, $meta_key, true);
    if (!is_array($existing_scan_data)) {
        $existing_scan_data = array();
    }
    
    // Determine scan_status
    // - Personal role: always 'confirmed'
    // - Buyer role: 'confirmed' if matched seller found, otherwise use provided value or 'pending'
    // - Seller role: use provided value or 'pending'
    $scan_status_value = 'pending';
    if (strpos($role, 'personal') !== false) {
        $scan_status_value = 'confirmed';
    } elseif (strpos($role, 'buyer') !== false && $matched_seller_id) {
        $scan_status_value = 'confirmed';
    } elseif (isset($scan_data['scan_status'])) {
        $scan_status_value = $scan_data['scan_status'];
    }
    
    // Update seller's entry if buyer is being inserted
    if (strpos($role, 'buyer') !== false && $matched_seller_id && $seller_entry_index !== null) {
        // Get seller's scan data
        $seller_scan_data = get_user_meta($matched_seller_id, 'seller_scan', true);
        if (is_array($seller_scan_data) && isset($seller_scan_data[$seller_entry_index])) {
            // Update seller's entry
            $seller_scan_data[$seller_entry_index]['scan_status'] = 'confirmed';
            $seller_scan_data[$seller_entry_index]['buyer_id'] = intval($user_id);
            
            // Update seller's usermeta
            update_user_meta($matched_seller_id, 'seller_scan', $seller_scan_data);
            error_log('Updated seller entry: User ID ' . $matched_seller_id . ', Entry Index ' . $seller_entry_index . ' - Status: confirmed, Buyer ID: ' . $user_id);
        }
    }
    
    // Prepare the data to insert (only include the fields specified by user)
    $new_scan_entry = array(
        'delivery_proof' => isset($scan_data['delivery_proof']) ? $scan_data['delivery_proof'] : 'yes',
        'discord_join' => isset($scan_data['discord_join']) ? (bool)$scan_data['discord_join'] : false,
        'mega-mobile' => isset($scan_data['mega-mobile']) ? $scan_data['mega-mobile'] : '',
        'percentage' => isset($scan_data['percentage']) ? intval($scan_data['percentage']) : 0,
        'transaction_id' => isset($scan_data['transaction_id']) ? $scan_data['transaction_id'] : '',
        'role' => isset($scan_data['role']) ? $scan_data['role'] : '',
        'scan_status' => $scan_status_value,
        'scan_type' => isset($scan_data['scan_type']) ? $scan_data['scan_type'] : 'proof',
        'status' => isset($scan_data['status']) ? $scan_data['status'] : 'pending',
        'timestamp' => isset($scan_data['timestamp']) ? $scan_data['timestamp'] : current_time('mysql'),
        'treasury_distributed' => isset($scan_data['treasury_distributed']) ? floatval($scan_data['treasury_distributed']) : 0,
        // Store xp_units as string to preserve large integer values (avoid scientific notation)
        'xp_units' => isset($scan_data['xp_units']) ? (string)$scan_data['xp_units'] : '0',
        'yam_value' => isset($scan_data['yam_value']) ? floatval($scan_data['yam_value']) : 0
    );
    
    // Add seller_id to buyer entry if matched seller found
    if (strpos($role, 'buyer') !== false && isset($matched_seller_id) && $matched_seller_id) {
        $new_scan_entry['seller_id'] = intval($matched_seller_id);
    }
    
    // Add buyer_id to buyer entry (buyer's own user_id)
    if (strpos($role, 'buyer') !== false) {
        $new_scan_entry['buyer_id'] = intval($user_id);
    }
    
    // Add geolocation data if available
    if (isset($scan_data['geolocation']) && is_array($scan_data['geolocation'])) {
        $new_scan_entry['geolocation'] = array(
            'latitude' => isset($scan_data['geolocation']['latitude']) ? floatval($scan_data['geolocation']['latitude']) : null,
            'longitude' => isset($scan_data['geolocation']['longitude']) ? floatval($scan_data['geolocation']['longitude']) : null,
            'accuracy' => isset($scan_data['geolocation']['accuracy']) ? floatval($scan_data['geolocation']['accuracy']) : null,
            'timestamp' => isset($scan_data['geolocation']['timestamp']) ? $scan_data['geolocation']['timestamp'] : current_time('mysql'),
            'error' => isset($scan_data['geolocation']['error']) ? $scan_data['geolocation']['error'] : null
        );
    }
    
    // Note: Seller-buyer matching removed since we're using transaction_id instead of proof_id
    // Each scan now has its own unique transaction_id
    
    // Add new scan data to existing array
    $existing_scan_data[] = $new_scan_entry;
    
    // Update user meta (WordPress will serialize the array automatically)
    $result = update_user_meta($user_id, $meta_key, $existing_scan_data);
    
    if ($result !== false) {
        error_log('Scan Data Inserted: User ID ' . $user_id . ', Meta Key: ' . $meta_key . ', Role: ' . $role);
        
        // Save treasury reminder data to wp_options table - update if exists, create if not
        $treasury_reminder_data = array(
            'delivery_proof' => $new_scan_entry['delivery_proof'],
            'discord_join' => $new_scan_entry['discord_join'],
            'mega-mobile' => $new_scan_entry['mega-mobile'],
            'percentage' => $new_scan_entry['percentage'],
            'transaction_id' => $new_scan_entry['transaction_id'],
            'role' => $new_scan_entry['role'],
            'scan_status' => $new_scan_entry['scan_status'],
            'scan_type' => $new_scan_entry['scan_type'],
            'status' => $new_scan_entry['status'],
            'timestamp' => $new_scan_entry['timestamp'],
            'trade_value' => isset($scan_data['trade_value']) ? floatval($scan_data['trade_value']) : 10.30,
            'trade_value_usd' => isset($scan_data['trade_value_usd']) ? floatval($scan_data['trade_value_usd']) : 0,
            'treasury_distributed' => $new_scan_entry['treasury_distributed'],
            'treasury_reminder' => isset($scan_data['treasury_reminder']) ? floatval($scan_data['treasury_reminder']) : 0,
            'user_id' => $user_id,
            // Store xp_reminder as string to preserve large integer values (avoid scientific notation)
            'xp_reminder' => isset($scan_data['xp_reminder']) ? (string)$scan_data['xp_reminder'] : '0',
            // Store xp_units as string to preserve large integer values
            'xp_units' => is_string($new_scan_entry['xp_units']) ? $new_scan_entry['xp_units'] : (string)$new_scan_entry['xp_units'],
            'yam_reminder' => isset($scan_data['yam_reminder']) ? floatval($scan_data['yam_reminder']) : 0,
            'yam_value' => $new_scan_entry['yam_value']
        );
        
        // Add geolocation data to treasury reminder if available
        if (isset($new_scan_entry['geolocation']) && is_array($new_scan_entry['geolocation'])) {
            $treasury_reminder_data['geolocation'] = $new_scan_entry['geolocation'];
        }
        
        // Add buyer_id to buyer entry (buyer's own user_id)
        if (strpos($role, 'buyer') !== false) {
            $treasury_reminder_data['buyer_id'] = intval($user_id); // Ensure it's an integer
        }
        
        // Add seller_id to buyer entry if matched seller found
        if (strpos($role, 'buyer') !== false && isset($matched_seller_id) && $matched_seller_id) {
            $treasury_reminder_data['seller_id'] = intval($matched_seller_id); // Ensure it's an integer
        }
        
        // Get existing treasury reminder data or create new array
        $existing_treasury_data = get_option('treasury_reminder', array());
        if (!is_array($existing_treasury_data)) {
            $existing_treasury_data = array();
        }
        
        // Add buyer_id to seller entry in treasury_reminder if buyer is being inserted
                    if (strpos($role, 'buyer') !== false && isset($matched_seller_id) && $matched_seller_id) {
            // Find and update seller's entry in treasury_reminder
            foreach ($existing_treasury_data as $treasury_index => $treasury_entry) {
                if (isset($treasury_entry['transaction_id']) && 
                    isset($treasury_entry['user_id']) && 
                    $treasury_entry['user_id'] == $matched_seller_id &&
                    $treasury_entry['transaction_id'] == $transaction_id &&
                    isset($treasury_entry['role']) && 
                    strpos(strtolower($treasury_entry['role']), 'seller') !== false) {
                    // Update seller's entry in treasury_reminder
                    $existing_treasury_data[$treasury_index]['scan_status'] = 'confirmed';
                        $existing_treasury_data[$treasury_index]['buyer_id'] = intval($user_id);
                    error_log('Updated seller entry in treasury_reminder: Transaction ID ' . $transaction_id . ' - Status: confirmed, Buyer ID: ' . $user_id);
                        break;
                    }
            }
        }
        
        // Check if entry already exists (same transaction_id) and update it instead of appending
        $entry_updated = false;
        if (!empty($treasury_reminder_data['transaction_id'])) {
            foreach ($existing_treasury_data as $treasury_index => $treasury_entry) {
                // Match by transaction_id
                if (isset($treasury_entry['transaction_id']) && $treasury_entry['transaction_id'] == $treasury_reminder_data['transaction_id']) {
                    // Update all fields of existing entry
                        $existing_treasury_data[$treasury_index] = $treasury_reminder_data;
                        $entry_updated = true;
                    error_log('Treasury reminder: Updated existing entry at index ' . $treasury_index . ' - Transaction ID: ' . $treasury_reminder_data['transaction_id']);
                        break;
                }
            }
        }
        
        // Only append new entry if entry wasn't updated
        if (!$entry_updated) {
            $existing_treasury_data[] = $treasury_reminder_data;
            error_log('Treasury reminder: Added new entry - Transaction ID: ' . $treasury_reminder_data['transaction_id'] . ', Role: ' . $treasury_reminder_data['role']);
        }
        
        // Update the option (will create if doesn't exist)
        $treasury_update_result = update_option('treasury_reminder', $existing_treasury_data);
        
        if ($treasury_update_result !== false) {
            error_log('Treasury reminder data saved to wp_options: User ID ' . $user_id . ', Transaction ID: ' . $new_scan_entry['transaction_id'] . ', Total entries: ' . count($existing_treasury_data));
        } else {
            error_log('Failed to save treasury reminder data to wp_options');
        }
        
        wp_send_json_success([
            'message' => 'Scan data inserted successfully',
            'meta_key' => $meta_key,
            'user_id' => $user_id,
            'seller_updated' => isset($matched_seller_id) ? true : false,
            'treasury_reminder_saved' => $treasury_update_result
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to insert scan data']);
    }
    
    wp_die();
}

// Add calculation results popup HTML and CSS
add_action('wp_footer', 'ct_add_calculation_popup');
function ct_add_calculation_popup() {
    // Only show on frontend pages
    if (is_admin()) {
        return;
    }
    ?>
    <!-- Calculation Results Popup -->
    <div id="cpp-popup-calculation" class="cpp-popup" style="display: none;">
        <div class="cpp-popup-content" style="max-width: 600px;">
            <span class="cpp-close-calculation">&times;</span>
            <div id="calculation-results" style="text-align: center;">
                <p><strong>Loading data...</strong></p>
            </div>
            <button id="cpp-close-calculation-btn" style="margin-top: 20px; padding: 10px 30px; cursor: pointer;">Close & Continue</button>
        </div>
    </div>

    <style>
        #cpp-popup-calculation {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        #cpp-popup-calculation .cpp-popup-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            position: relative;
            border-radius: 8px;
        }

        .cpp-close-calculation {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .cpp-close-calculation:hover,
        .cpp-close-calculation:focus {
            color: black;
            text-decoration: none;
        }

        #calculation-results {
            background: #f8f9fa;
            padding: 30px 20px;
            border-radius: 5px;
            margin: 15px 0;
        }

        #calculation-results p {
            margin: 10px 0;
            font-size: 14px;
        }

        #calculation-results h2 {
            margin: 0 0 20px 0;
        }

        #calculation-results strong {
            color: #2c3e50;
        }

        #cpp-close-calculation-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        #cpp-close-calculation-btn:hover {
            background-color: #2980b9;
        }
    </style>
    <?php
}
