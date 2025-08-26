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

    $country_code = ct_get_user_country_code();
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
    //check nonce
    if (!(isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'ct_validate_twilio_otp'))) {
        wp_send_json_success(["nonce_fail", '']);
        wp_die();
    }

    $country_code = ct_get_user_country_code();
    if (!empty($country_code) && $country_code == 'NP') {
        $country_code = '+977';
    } else {
        $country_code = '+1';
    }

    $phoneNumber = sanitize_text_field($_POST['phone_number']);
    $otp = sanitize_text_field($_POST['otp']);

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
        // Check if current page URL contains ?redirect=qr
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        
        if (strpos($current_url, '?redirect=qr') !== false || 
            strpos($current_url, 'redirect=qr') !== false) {
            
            // OTP verified successfully AND URL contains redirect=qr - now save XP earning data
            ct_save_xp_after_otp_verification($phoneNumber);
            error_log("XP DEBUG: OTP verified with redirect=qr - XP awarded");
        } else {
            // OTP verified but no redirect=qr - no XP awarded
            error_log("XP DEBUG: OTP verified without redirect=qr - no XP awarded");
        }

        wp_send_json_success(["valid_otp", wp_create_nonce('ct_user_signin')]);
    } else {
        wp_send_json_success(["invalid_otp"]);
    }

    wp_die();
}
/**
 * Save XP earning data after successful OTP verification
 * @param string $phone_number The verified phone number
 */
function ct_save_xp_after_otp_verification($phone_number)
{
    // Find user by phone number
    $user = get_user_by('meta_value', $phone_number, 'phone_number');

    if (!$user) {
        // Try to find by user meta
        $users = get_users(array(
            'meta_key' => 'phone_number',
            'meta_value' => $phone_number,
            'number' => 1
        ));

        if (!empty($users)) {
            $user = $users[0];
        }
    }

    if (!$user) {
        error_log("XP ERROR: User not found for phone number: {$phone_number}");
        return false; // User not found
    }

    $user_id = $user->ID;

    // Get user role to determine if buyer or seller
    $dong_user_role = get_user_meta($user_id, 'dong_user_role', true);

    // Determine if user is buyer or seller based on role
    $is_seller = in_array($dong_user_role, array('Planning', 'Budget', 'Media', 'Distribution', 'Membership'));
    $is_buyer = !$is_seller; // If not seller, assume buyer

    // XP calculation constants (corrected amounts)
    $seller_xp = 1000000;   // 1,000,000 XP for sellers
    $buyer_xp = 10000000;   // 10,000,000 XP for buyers

    // Get Discord user ID from user meta
    $discord_user_id = get_user_meta($user_id, 'discord_user_id', true);

    // Check Discord membership
    $is_discord_member = ct_check_discord_membership($discord_user_id);

    // XP amount based on user type
    $xp_amount = $is_seller ? $seller_xp : $buyer_xp;

    // DEBUG: Log transaction details
    error_log("XP DEBUG: Processing XP for user {$user_id} ({$dong_user_role}): {$xp_amount} XP");

    if ($is_seller) {
        // Use the exact same pattern as existing code in cpm-woocommerce-functions.php
        // Get existing meta
        $seller_meta = get_user_meta($user_id, '_seller_details', true);

        // Assign empty array if meta is empty
        $seller_metas = !empty($seller_meta) ? $seller_meta : [];

        // Add new transaction following the exact same structure
        $seller_metas[] = [
            'xp_awarded' => $xp_amount, // XP awarded for this verification
            'transaction_type' => 'otp_verification',
            'phone_number' => $phone_number,
            'verification_date' => current_time('mysql'),
            'user_role' => $dong_user_role,
            'discord_member' => $is_discord_member,
            'discord_user_id' => $discord_user_id,
            'discord_check_date' => current_time('mysql')
        ];

        // Update the meta using the same pattern as existing code
        if (!empty($seller_metas)) {
            $update_result = update_user_meta($user_id, '_seller_details', $seller_metas);

            // DEBUG: Confirm meta update
            error_log("XP DEBUG: Seller meta update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
            error_log("XP DEBUG: Total seller transactions: " . count($seller_metas));

            // Add success alert for seller
            add_action('wp_footer', function () use ($user_id, $dong_user_role, $xp_amount, $phone_number, $is_discord_member) {
                $status = $is_discord_member ? 'COMPLETED (Discord Member)' : 'PENDING (Not Discord Member)';
                $message = $is_discord_member ?
                    "�� XP AWARDED SUCCESSFULLY!" :
                    "⏳ XP PENDING - DISCORD MEMBERSHIP REQUIRED!";

                echo '<script>
                if (typeof jQuery !== "undefined") {
                    jQuery(document).ready(function($) {
                        alert("' . $message . '\\n\\nUser ID: ' . $user_id . '\\nRole: ' . $dong_user_role . '\\nXP Amount: ' . number_format($xp_amount) . '\\nPhone: ' . $phone_number . '\\nStatus: ' . $status . '\\n\\nData saved to: _seller_details");
                    });
                }
                </script>';
            });
        }

    } else {
        // Use the exact same pattern as existing code for buyers
        // Get existing meta
        $buyer_meta = get_user_meta($user_id, '_buyer_details', true);

        // Assign empty array if meta is empty
        $buyer_metas = !empty($buyer_meta) ? $buyer_meta : [];

        // Add new transaction following the exact same structure
        $buyer_metas[] = [
            'xp_awarded' => $xp_amount, // XP awarded for this verification
            'transaction_type' => 'otp_verification',
            'phone_number' => $phone_number,
            'verification_date' => current_time('mysql'),
            'user_role' => $dong_user_role,
            'discord_member' => $is_discord_member,
            'discord_user_id' => $discord_user_id,
            'discord_check_date' => current_time('mysql')
        ];

        // Update the meta using the same pattern as existing code
        if (!empty($buyer_metas)) {
            $update_result = update_user_meta($user_id, '_buyer_details', $buyer_metas);

            // DEBUG: Confirm meta update
            error_log("XP DEBUG: Buyer meta update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
            error_log("XP DEBUG: Total buyer transactions: " . count($buyer_metas));

            // Add success alert for buyer
            add_action('wp_footer', function () use ($user_id, $xp_amount, $phone_number, $is_discord_member) {
                $status = $is_discord_member ? 'COMPLETED (Discord Member)' : 'PENDING (Not Discord Member)';
                $message = $is_discord_member ?
                    "�� XP AWARDED SUCCESSFULLY!" :
                    "⏳ XP PENDING - DISCORD MEMBERSHIP REQUIRED!";

                echo '<script>
                if (typeof jQuery !== "undefined") {
                    jQuery(document).ready(function($) {
                        alert("' . $message . '\\n\\nUser ID: ' . $user_id . '\\nRole: Buyer\\nXP Amount: ' . number_format($xp_amount) . '\\nPhone: ' . $phone_number . '\\nStatus: ' . $status . '\\n\\nData saved to: _buyer_details");
                    });
                }
                </script>';
            });
        }
    }

    // DEBUG: Final confirmation
    error_log("XP DEBUG: Complete transaction saved successfully for user {$user_id}");

    return true;
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
