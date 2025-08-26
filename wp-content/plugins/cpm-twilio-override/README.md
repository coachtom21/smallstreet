# CPM Twilio Override - XP System

## Overview
This plugin overrides the original CPM Twilio plugin functions to add XP (Experience Points) earning functionality after OTP verification.

## Features
- **Automatic XP Awarding**: Users earn XP after successful OTP verification
- **Role-Based XP**: 
  - Sellers get 1,000,000 XP
  - Buyers get 10,000,000 XP
- **Database Storage**: XP data saved to user meta tables
- **Transaction Logging**: All XP awards are logged for audit

## Installation

### Method 1: Manual Installation
1. Upload the `cpm-twilio-override` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. The override will automatically take effect

### Method 2: FTP Upload
1. Upload via FTP to `/wp-content/plugins/cpm-twilio-override/`
2. Activate in WordPress admin

## How It Works

### 1. Override System
- Removes original Twilio OTP validation actions
- Adds custom OTP validation with XP functionality
- Runs after original plugin loads (`plugins_loaded` hook)

### 2. XP Calculation
- **Seller Roles**: Planning, Budget, Media, Distribution, Membership
- **Buyer Roles**: Any other role or no role set
- **XP Amounts**: 1M for sellers, 10M for buyers

### 3. Database Storage
- **Meta Keys**: 
  - `seller_details` (for sellers)
  - `buyer_details` (for buyers)
  - `_total_xp_balance` (total XP balance)
- **Transaction Record**: Includes amount, type, date, role, status

## Usage

### 1. OTP Verification
Users go through normal OTP verification process:
1. Enter phone number
2. Receive OTP via SMS
3. Enter OTP
4. **XP automatically awarded** ✅

### 2. View XP Dashboard
Use the shortcode: `[dongtrader_xp_dashboard]`
Or visit WooCommerce account page (auto-displays)

### 3. Test XP System
Add `?test_xp=1` to any page when logged in as admin

## File Structure
```
cpm-twilio-override/
├── cpm-twilio-override.php    # Main plugin file
├── README.md                  # This file
└── .gitignore                # Git ignore file
```

## Dependencies
- WordPress 5.0+
- CPM Twilio plugin (original)
- WooCommerce (for XP display)
- CPM Dongtrader plugin (for XP dashboard)

## Benefits of Override Approach

### ✅ Advantages
- **Preserves Original Plugin**: No modifications to vendor files
- **Update Safe**: Survives plugin updates
- **Easy Maintenance**: All XP logic in one place
- **Clean Separation**: XP functionality separate from core Twilio

### ⚠️ Considerations
- **Plugin Order**: Must load after original Twilio plugin
- **Function Names**: Uses different function names to avoid conflicts
- **Hook Priority**: Uses priority 20 to ensure original loads first

## Troubleshooting

### Common Issues

#### 1. XP Not Awarded
- Check if user has phone number in meta
- Verify user role is set correctly
- Check error logs for issues

#### 2. Plugin Conflicts
- Ensure CPM Twilio plugin is active
- Check plugin loading order
- Verify no other plugins override same functions

#### 3. Database Issues
- Check user meta tables exist
- Verify WordPress permissions
- Check for database errors

### Debug Mode
Enable WordPress debug mode to see detailed error logs:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support
For issues or questions:
1. Check error logs
2. Verify plugin activation
3. Test with default WordPress theme
4. Check browser console for JavaScript errors

## Version History
- **1.0.0**: Initial release with XP functionality

## License
Custom development - use at your own risk
