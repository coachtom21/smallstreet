<?php
/**
 * Test script to check current Twilio plugin state
 */

// Load WordPress
require_once __DIR__ . '/wp-load.php';

echo "<h1>Twilio Plugin Current State Test</h1>";

// Check if the plugin functions exist
echo "<h2>1. Plugin Function Availability</h2>";
if (function_exists('ct_get_twilio_credentials')) {
    echo "✅ <strong>ct_get_twilio_credentials()</strong> function exists<br>";
} else {
    echo "❌ <strong>ct_get_twilio_credentials()</strong> function NOT found<br>";
}

if (function_exists('ct_validate_twilio_credentials')) {
    echo "✅ <strong>ct_validate_twilio_credentials()</strong> function exists<br>";
} else {
    echo "❌ <strong>ct_validate_twilio_credentials()</strong> function NOT found<br>";
}

// Check current credentials
echo "<h2>2. Current Credential Sources</h2>";
$credentials = ct_get_twilio_credentials();

echo "<strong>Account SID:</strong> " . substr($credentials['account_sid'], 0, 10) . "...<br>";
echo "<strong>Auth Token:</strong> " . (strlen($credentials['auth_token']) > 0 ? 'SET (' . strlen($credentials['auth_token']) . ' chars)' : 'NOT SET') . "<br>";
echo "<strong>App SID:</strong> " . substr($credentials['app_sid'], 0, 10) . "...<br>";

// Check WordPress options
echo "<h2>3. WordPress Options</h2>";
$wp_account_sid = get_option('ct_twilio_account_sid', 'NOT SET');
$wp_auth_token = get_option('ct_twilio_auth_token', 'NOT SET');
$wp_app_sid = get_option('ct_twilio_app_sid', 'NOT SET');

echo "<strong>WP Option - Account SID:</strong> " . ($wp_account_sid !== 'NOT SET' ? substr($wp_account_sid, 0, 10) . '...' : 'NOT SET') . "<br>";
echo "<strong>WP Option - Auth Token:</strong> " . ($wp_auth_token !== 'NOT SET' ? 'SET (' . strlen($wp_auth_token) . ' chars)' : 'NOT SET') . "<br>";
echo "<strong>WP Option - App SID:</strong> " . ($wp_app_sid !== 'NOT SET' ? substr($wp_app_sid, 0, 10) . '...' : 'NOT SET') . "<br>";

// Check environment variables
echo "<h2>4. Environment Variables</h2>";
$env_account_sid = getenv('TWILIO_ACCOUNT_SID');
$env_auth_token = getenv('TWILIO_AUTH_TOKEN');
$env_app_sid = getenv('TWILIO_APP_SID');

echo "<strong>ENV - TWILIO_ACCOUNT_SID:</strong> " . ($env_account_sid ? substr($env_account_sid, 0, 10) . '...' : 'NOT SET') . "<br>";
echo "<strong>ENV - TWILIO_AUTH_TOKEN:</strong> " . ($env_auth_token ? 'SET (' . strlen($env_auth_token) . ' chars)' : 'NOT SET') . "<br>";
echo "<strong>ENV - TWILIO_APP_SID:</strong> " . ($env_app_sid ? substr($env_app_sid, 0, 10) . '...' : 'NOT SET') . "<br>";

// Check wp-config constants
echo "<h2>5. wp-config.php Constants</h2>";
if (defined('TWILIO_ACCOUNT_SID')) {
    echo "✅ <strong>TWILIO_ACCOUNT_SID</strong> constant defined: " . substr(TWILIO_ACCOUNT_SID, 0, 10) . "...<br>";
} else {
    echo "❌ <strong>TWILIO_ACCOUNT_SID</strong> constant NOT defined<br>";
}

if (defined('TWILIO_AUTH_TOKEN')) {
    echo "✅ <strong>TWILIO_AUTH_TOKEN</strong> constant defined: " . substr(TWILIO_AUTH_TOKEN, 0, 10) . "...<br>";
} else {
    echo "❌ <strong>TWILIO_AUTH_TOKEN</strong> constant NOT defined<br>";
}

if (defined('TWILIO_APP_SID')) {
    echo "✅ <strong>TWILIO_APP_SID</strong> constant defined: " . substr(TWILIO_APP_SID, 0, 10) . "...<br>";
} else {
    echo "❌ <strong>TWILIO_APP_SID</strong> constant NOT defined<br>";
}

// Test credential validation
echo "<h2>6. Credential Validation Test</h2>";
if (function_exists('ct_validate_twilio_credentials')) {
    $validation = ct_validate_twilio_credentials($credentials['account_sid'], $credentials['auth_token']);
    
    if ($validation['valid']) {
        echo "✅ <strong>Credentials are VALID!</strong><br>";
        echo "Account Name: " . esc_html($validation['account_name']) . "<br>";
    } else {
        echo "❌ <strong>Credentials are INVALID!</strong><br>";
        echo "Error: " . esc_html($validation['error']) . "<br>";
    }
} else {
    echo "❌ Cannot test credentials - validation function not found<br>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If credentials are invalid, update them in <strong>Settings → Twilio Settings</strong></li>";
echo "<li>Or add them to your <strong>wp-config.php</strong> file</li>";
echo "<li>Or set them as <strong>environment variables</strong></li>";
echo "</ol>";

echo "<p><strong>Current Status:</strong> The plugin is now properly configured to use multiple credential sources.</p>";
?>


