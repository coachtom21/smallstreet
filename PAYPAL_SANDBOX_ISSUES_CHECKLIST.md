# PayPal Sandbox Issues - Checklist

## Configuration Issues

### 1. **Sandbox Mode Not Enabled**
- **Check:** WooCommerce → Settings → Payments → PayPal → "Enable PayPal sandbox" checkbox
- **Location:** `wp-content/plugins/woocommerce/includes/gateways/paypal/includes/settings-paypal.php` (line 44-50)
- **Issue:** If unchecked, system uses live PayPal instead of sandbox

### 2. **Missing or Incorrect PayPal Email** ⚠️ **MOST COMMON ISSUE**
- **Check:** WooCommerce → Settings → Payments → PayPal → "PayPal email" field
- **Location:** `class-wc-gateway-paypal.php` (line 98)
- **Issue:** Must be a valid PayPal sandbox business account email when sandbox is enabled
- **Validation:** System checks `is_email()` - invalid email prevents gateway from working
- **IMPORTANT:** When sandbox mode is enabled, the PayPal email MUST be your sandbox business account email (format: `sb-xxxxx@business.example.com`), NOT your live PayPal email
- **How it's used:** This email is sent to PayPal as the `business` parameter - telling PayPal where to send the payment

### 3. **Missing Sandbox API Credentials**
- **Check:** WooCommerce → Settings → Payments → PayPal → API credentials section
- **Required Fields:**
  - Sandbox API username
  - Sandbox API password
  - Sandbox API signature
- **Location:** `class-wc-gateway-paypal.php` (lines 395-397)
- **Issue:** Required for refunds and API operations (not required for basic payments)

### 4. **Incorrect Receiver Email**
- **Check:** WooCommerce → Settings → Payments → PayPal → "Receiver email" field
- **Location:** `class-wc-gateway-paypal.php` (line 99)
- **Issue:** Must match the PayPal account receiving payments
- **Note:** Used for IPN validation (verifies that IPN notifications are from the correct account)
- **Default Behavior:** If left empty, defaults to the "PayPal email" value
- **Sandbox:** Should match your sandbox business account email when sandbox mode is enabled

### 5. **Missing Identity Token (PDT)**
- **Check:** WooCommerce → Settings → Payments → PayPal → "PayPal identity token"
- **Location:** `class-wc-gateway-paypal.php` (line 100, 123)
- **Issue:** Optional but recommended for payment verification without IPN
- **Setup:** Enable "Payment Data Transfer" in PayPal account settings

---

## PayPal Account Issues

### 6. **Invalid Sandbox Business Account**
- **Check:** PayPal Developer Dashboard → Sandbox → Accounts
- **Issue:** Business account not created or not verified
- **Solution:** Create business account in PayPal Developer Dashboard

### 7. **Sandbox Account Not Activated**
- **Check:** PayPal Developer Dashboard → Sandbox → Accounts → Account status
- **Issue:** Account may be suspended or not activated
- **Solution:** Activate account in PayPal Developer Dashboard

### 8. **Wrong Sandbox Account Email**
- **Check:** PayPal email in WooCommerce settings matches sandbox business account email
- **Issue:** Email mismatch causes payment failures
- **Solution:** Use exact email from PayPal sandbox business account

### 9. **Sandbox Account Restrictions**
- **Check:** PayPal Developer Dashboard → Sandbox → Accounts → Account details
- **Issue:** Account may have restrictions or limitations
- **Solution:** Check account status and restrictions in PayPal dashboard

---

## Payment Gateway Filtering Issues

### 10. **PayPal Gateway Hidden by Custom Filter**
- **Check:** `wp-content/themes/hello-elementor-child/functions.php` (lines 295-327)
- **Issue:** Custom filter `disable_payment_gateways_on_checkout` may hide PayPal
- **Logic:** 
  - If product ID 2481 or 1308 in cart → PayPal shown, Preorder hidden
  - Otherwise → PayPal hidden, Preorder shown
- **Solution:** Check if correct products are in cart

### 11. **PayPal Not Enabled in WooCommerce**
- **Check:** WooCommerce → Settings → Payments → PayPal → "Enable PayPal Standard" checkbox
- **Location:** `settings-paypal.php` (line 11-16)
- **Issue:** Gateway disabled entirely
- **Solution:** Enable the gateway

---

## Currency & Store Settings Issues

### 12. **Unsupported Currency**
- **Check:** WooCommerce → Settings → General → Currency
- **Location:** `class-wc-gateway-paypal.php` (lines 300-309)
- **Supported Currencies:** AUD, BRL, CAD, MXN, NZD, HKD, SGD, USD, EUR, JPY, TRY, NOK, CZK, DKK, HUF, ILS, MYR, PHP, PLN, SEK, CHF, TWD, THB, GBP, RMB, RUB, INR
- **Issue:** If currency not in list, gateway is disabled
- **Solution:** Change store currency to supported one

### 13. **Missing Base Country**
- **Check:** WooCommerce → Settings → General → Base location
- **Location:** `class-wc-gateway-paypal.php` (line 189)
- **Issue:** Empty base country prevents gateway icon/URL generation
- **Solution:** Set base country in WooCommerce settings

---

## Network & URL Issues

### 14. **IPN Notification URL Not Accessible**
- **Check:** PayPal → Account Settings → Website Preferences → IPN URL
- **Location:** `class-wc-gateway-paypal-request.php` (line 55, 139)
- **Issue:** PayPal cannot reach your site's IPN endpoint
- **URL Format:** `https://yoursite.com/wc-api/WC_Gateway_Paypal`
- **Solution:** Ensure site is publicly accessible, SSL enabled

### 15. **Return URL Issues**
- **Check:** PayPal redirects back to site after payment
- **Location:** `class-wc-gateway-paypal-request.php` (line 128)
- **Issue:** Return URL may be blocked or incorrect
- **Solution:** Check site URL settings in WordPress

### 16. **SSL Certificate Issues**
- **Check:** Site has valid SSL certificate
- **Location:** `class-wc-gateway-paypal-request.php` (line 126)
- **Issue:** PayPal requires HTTPS for secure transactions
- **Solution:** Install valid SSL certificate

---

## PayPal API Issues

### 17. **API Credentials Mismatch**
- **Check:** Sandbox API credentials match sandbox account
- **Location:** `class-wc-gateway-paypal.php` (lines 395-397)
- **Issue:** Using live credentials with sandbox or vice versa
- **Solution:** Use sandbox credentials when sandbox mode enabled

### 18. **API Signature Incorrect**
- **Check:** Sandbox API signature is correct
- **Location:** `class-wc-gateway-paypal.php` (line 397)
- **Issue:** Incorrect signature causes API calls to fail
- **Solution:** Regenerate API signature in PayPal Developer Dashboard

---

## Order Processing Issues

### 19. **Order Status Not Updating**
- **Check:** WooCommerce → Orders → Order status after payment
- **Location:** `class-wc-gateway-paypal-ipn-handler.php`
- **Issue:** IPN not received or not processed
- **Solution:** Check IPN logs, verify IPN URL is accessible

### 20. **Payment Action Mismatch**
- **Check:** WooCommerce → Settings → Payments → PayPal → "Payment action"
- **Location:** `settings-paypal.php` (lines 104-115)
- **Options:** Capture (sale) or Authorize
- **Issue:** Wrong payment action may cause issues
- **Solution:** Use "Capture" for immediate payment

---

## Debugging & Logging

### 21. **Debug Logging Not Enabled**
- **Check:** WooCommerce → Settings → Payments → PayPal → "Enable logging"
- **Location:** `class-wc-gateway-paypal.php` (line 96, 101)
- **Issue:** Cannot see error messages
- **Solution:** Enable debug logging, check logs at `wp-content/uploads/wc-logs/paypal-*.log`

### 22. **Error Messages Not Visible**
- **Check:** WordPress debug mode and error display
- **Location:** `wp-config.php`
- **Issue:** Errors hidden from user
- **Solution:** Enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`

---

## Common PayPal Sandbox Errors

### 23. **"Something doesn't look right" Error**
- **Possible Causes:**
  - PayPal email not verified
  - Sandbox account restrictions
  - Account country mismatch
  - Payment amount too high/low for test account

### 24. **"Your payment to this merchant can't be completed"**
- **Possible Causes:**
  - Sandbox business account not properly set up
  - Account limitations
  - Test account restrictions
  - Currency mismatch

### 25. **Payment Redirects But Doesn't Complete**
- **Possible Causes:**
  - IPN not received
  - Return URL incorrect
  - Order status hooks interfering
  - Custom code blocking completion

---

## Code-Specific Issues

### 26. **Custom Hooks Interfering**
- **Check:** `cpm-woocommerce-functions.php` hooks
- **Location:** Various hooks in `cpm-woocommerce-functions.php`
- **Issue:** Custom hooks may change order status incorrectly
- **Solution:** Check hooks: `woocommerce_order_status_changed`, `woocommerce_checkout_order_created`

### 27. **Preorder Status Hooks Conflict**
- **Check:** Preorder status management hooks
- **Location:** `cpm-woocommerce-functions.php` (lines 1625-1703)
- **Issue:** Hooks may interfere with PayPal order status
- **Solution:** Ensure hooks only affect preorder payment method

---

## Quick Verification Steps

1. ✅ **Sandbox Mode Enabled?** → WooCommerce → Payments → PayPal → "Enable PayPal sandbox"
2. ✅ **PayPal Email Set?** → Must be sandbox business account email
3. ✅ **Gateway Enabled?** → "Enable PayPal Standard" checkbox checked
4. ✅ **Currency Supported?** → Check currency is in supported list
5. ✅ **Correct Products in Cart?** → Product ID 2481 or 1308 for PayPal to show
6. ✅ **Debug Logging Enabled?** → Check logs for specific error messages
7. ✅ **Sandbox Account Active?** → Verify in PayPal Developer Dashboard
8. ✅ **IPN URL Accessible?** → Test IPN endpoint is reachable
9. ✅ **SSL Certificate Valid?** → Site must have HTTPS
10. ✅ **No Custom Code Conflicts?** → Check payment gateway filter logic

---

## Where to Check Settings

**WooCommerce Settings:**
- WooCommerce → Settings → Payments → PayPal

**PayPal Developer Dashboard:**
- https://developer.paypal.com/dashboard/accounts/sandbox

**WordPress Logs:**
- `wp-content/uploads/wc-logs/paypal-*.log`

**WordPress Debug Log:**
- `wp-content/debug.log`

---

*Last Updated: Based on codebase analysis*

