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
        // Check URL for debugging
        $current_url = $_SERVER['HTTP_REFERER'] ?? '';
        $redirect_qr = strpos($current_url, 'redirect=qr') !== false;

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

            // Check if user is seller or buyer
            $is_seller = in_array($dong_user_role, array('Planning', 'Budget', 'Media', 'Distribution', 'Membership'));
            $user_type = $is_seller ? 'SELLER' : 'BUYER';
        } else {
            $user_type = 'USER NOT FOUND';
        }

        // If URL contains redirect=qr and user is a seller, insert data
        if ($redirect_qr && $user_type === 'BUYER') {
            try {
                // Get existing seller details or create new array
                $existing_seller_details = get_user_meta($user_id, '_seller_details', true);
                if (!is_array($existing_seller_details)) {
                    $existing_seller_details = array();
                }

                // Create new seller transaction data
                $new_transaction = array(
                    'xp_awarded' => 1000000, // 1 million XP for scanning/OTP verification
                    'transaction_type' => 'scanning_verification',
                    'phone_number' => $phoneNumber,
                    'verification_date' => current_time('mysql'),
                    'user_role' => $dong_user_role,
                    'status' => 'completed',
                    'discord_member' => false // Will be updated when Discord is verified
                );

                // Add new transaction to existing array
                $existing_seller_details[] = $new_transaction;

                // Update user meta with new seller details
                update_user_meta($user_id, '_seller_details', $existing_seller_details);

                // Log successful insertion
                error_log('QR Redirect: Seller data inserted for user ' . $user_id . ' with role ' . $dong_user_role);
            } catch (Exception $e) {
                // Log error but don't break the OTP verification
                error_log('QR Redirect error: ' . $e->getMessage());
            }

        } else if ($redirect_qr && $user_type === 'BUYER') {
            // Log that user is not a seller
            error_log('QR Redirect: User ' . $user_id . ' is a BUYER (role: ' . $user_role . ') - no seller data inserted');
        }

        wp_send_json_success(["valid_otp", wp_create_nonce('ct_user_signin')]);
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
