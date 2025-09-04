# Twilio OTP SMS Setup Guide

## Current Issue
The Twilio plugin is currently experiencing an **HTTP 401 Authentication Error** when trying to send OTP SMS messages. This indicates that the hardcoded credentials in the plugin are invalid or expired.

## Solution Steps

### 1. Get Your Twilio Credentials
1. Log into your [Twilio Console](https://console.twilio.com/)
2. Go to **Dashboard** to find your Account SID and Auth Token
3. Go to **Verify** > **Services** to find your Verify Service SID

### 2. Configure Credentials (Choose One Method)

#### Method A: WordPress Admin Panel (Recommended)
1. Go to **WordPress Admin** > **Settings** > **Twilio Settings**
2. Enter your credentials:
   - **Account SID**: `ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - **Auth Token**: `xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - **App SID**: `VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
3. Click **Save Settings**
4. Verify the credentials show as "Valid"

#### Method B: Environment Variables
Add these to your server's environment configuration or `.htaccess`:

```apache
# Add to .htaccess (if using Apache)
SetEnv TWILIO_ACCOUNT_SID "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
SetEnv TWILIO_AUTH_TOKEN "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
SetEnv TWILIO_APP_SID "VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

Or set them in your server's environment variables.

#### Method C: wp-config.php
Add these lines to your `wp-config.php` file (before the "That's all, stop editing!" line):

```php
// Twilio Configuration
define('TWILIO_ACCOUNT_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_AUTH_TOKEN', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('TWILIO_APP_SID', 'VAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
```

### 3. Test the Configuration
1. Go to **Settings** > **Twilio Settings** in WordPress Admin
2. The page will automatically test your credentials
3. If valid, you'll see a green checkmark with your account name
4. If invalid, you'll see the specific error message

### 4. Verify OTP Functionality
1. Test the OTP form on your website
2. Check that SMS messages are being sent successfully
3. Monitor the WordPress error logs for any remaining issues

## Security Notes
- **Never commit credentials to version control**
- **Use environment variables in production**
- **Regularly rotate your Auth Token**
- **Monitor your Twilio usage and billing**

## Troubleshooting

### Common Issues:
1. **HTTP 401 Error**: Invalid credentials - check Account SID and Auth Token
2. **Service Not Found**: Invalid App SID - verify your Verify Service SID
3. **Phone Number Issues**: Ensure phone numbers include country code (+1 for US, +977 for Nepal)

### Debug Mode:
Enable WordPress debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Check Logs:
- WordPress error logs: `wp-content/debug.log`
- PHP error logs: Check your server's error log location

## Support
If you continue to experience issues:
1. Verify your Twilio account is active and not suspended
2. Check your Twilio Console for any error messages
3. Ensure your IP address is not blocked by Twilio
4. Contact Twilio Support if the issue persists


