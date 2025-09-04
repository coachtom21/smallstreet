<?php
/**
 * Twilio Configuration Helper Script
 * Run this script to test and configure your Twilio credentials
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

// Check if user is logged in and has admin privileges
if (!current_user_can('manage_options')) {
    die('You need to be logged in as an administrator to run this script.');
}

echo "<h1>Twilio Configuration Helper</h1>";

// Handle form submission
if (isset($_POST['submit'])) {
    $account_sid = sanitize_text_field($_POST['account_sid']);
    $auth_token = sanitize_text_field($_POST['auth_token']);
    $app_sid = sanitize_text_field($_POST['app_sid']);
    
    // Save to WordPress options
    update_option('ct_twilio_account_sid', $account_sid);
    update_option('ct_twilio_auth_token', $auth_token);
    update_option('ct_twilio_app_sid', $app_sid);
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
    echo "✅ Credentials saved successfully!";
    echo "</div>";
}

// Get current credentials
$current_account_sid = get_option('ct_twilio_account_sid', '');
$current_auth_token = get_option('ct_twilio_auth_token', '');
$current_app_sid = get_option('ct_twilio_app_sid', '');

// Test credentials if they exist
$test_result = '';
if (!empty($current_account_sid) && !empty($current_auth_token)) {
    try {
        require_once __DIR__ . '/wp-content/plugins/cpm-twilio/twilio-php-master/src/Twilio/autoload.php';
        
        $twilio = new \Twilio\Rest\Client($current_account_sid, $current_auth_token);
        $account = $twilio->api->accounts($current_account_sid)->fetch();
        
        $test_result = "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; margin: 10px 0;'>";
        $test_result .= "✅ <strong>Credentials are VALID!</strong><br>";
        $test_result .= "Account Name: " . esc_html($account->friendlyName) . "<br>";
        $test_result .= "Account Status: " . esc_html($account->status);
        $test_result .= "</div>";
        
    } catch (Exception $e) {
        $test_result = "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; margin: 10px 0;'>";
        $test_result .= "❌ <strong>Credentials are INVALID!</strong><br>";
        $test_result .= "Error: " . esc_html($e->getMessage());
        $test_result .= "</div>";
    }
}

?>

<form method="post" style="max-width: 600px; margin: 20px 0;">
    <h2>Enter Your Twilio Credentials</h2>
    
    <p><strong>To get your credentials:</strong></p>
    <ol>
        <li>Go to <a href="https://console.twilio.com/" target="_blank">Twilio Console</a></li>
        <li>Copy your Account SID from the Dashboard</li>
        <li>Copy your Auth Token from the Dashboard</li>
        <li>Go to Verify → Services and copy your Verify Service SID</li>
    </ol>
    
    <div style="margin: 15px 0;">
        <label for="account_sid"><strong>Account SID:</strong></label><br>
        <input type="text" id="account_sid" name="account_sid" value="<?php echo esc_attr($current_account_sid); ?>" 
               style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd;" 
               placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
        <small>Starts with "AC"</small>
    </div>
    
    <div style="margin: 15px 0;">
        <label for="auth_token"><strong>Auth Token:</strong></label><br>
        <input type="password" id="auth_token" name="auth_token" value="<?php echo esc_attr($current_auth_token); ?>" 
               style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd;" 
               placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
    </div>
    
    <div style="margin: 15px 0;">
        <label for="app_sid"><strong>App SID (Verify Service):</strong></label><br>
        <input type="text" id="app_sid" name="app_sid" value="<?php echo esc_attr($current_app_sid); ?>" 
               style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd;" 
               placeholder="VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" />
        <small>Starts with "VA"</small>
    </div>
    
    <input type="submit" name="submit" value="Save & Test Credentials" 
           style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;" />
</form>

<?php if ($test_result): ?>
    <h2>Test Results</h2>
    <?php echo $test_result; ?>
<?php endif; ?>

<h2>Next Steps</h2>
<ol>
    <li>Enter your Twilio credentials above and click "Save & Test"</li>
    <li>If the test passes, your OTP functionality should work</li>
    <li>If the test fails, double-check your credentials</li>
    <li>You can also configure credentials in <strong>Settings → Twilio Settings</strong> in WordPress admin</li>
</ol>

<h2>Alternative Configuration Methods</h2>
<p><strong>Option 1:</strong> Use the form above (saves to WordPress options)</p>
<p><strong>Option 2:</strong> Edit wp-config.php and add these lines:</p>
<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd;">
define( 'TWILIO_ACCOUNT_SID', 'YOUR_ACCOUNT_SID_HERE' );
define( 'TWILIO_AUTH_TOKEN', 'YOUR_AUTH_TOKEN_HERE' );
define( 'TWILIO_APP_SID', 'YOUR_VERIFY_SERVICE_SID_HERE' );
</pre>

<p><strong>Option 3:</strong> Go to WordPress Admin → Settings → Twilio Settings</p>

<hr>
<p><small>This script will be automatically deleted after you're done configuring Twilio.</small></p>
