# Complete Project Analysis - Smallstreet/Dongtrader System

## Executive Summary

This is a WordPress-based e-commerce platform with a sophisticated reward system that combines:
- **WooCommerce** for product sales and order management
- **2-Scan Proof of Delivery (PoD)** system for transaction verification
- **XP (Experience Points) & YAM Token** reward system
- **Multi-Level Marketing (MLM)** structure
- **Membership tiers** (YAMer, MEGAvoter, Patron)
- **Custom payment gateways** (Preorder, PayPal, Venmo)

---

## 1. Project Architecture

### 1.1 Core Plugins

#### **cpm-dongtrader** (Main Plugin)
**Location:** `wp-content/plugins/cpm-dongtrader/`

**Key Files:**
- `cpm-dongtrader.php` - Main plugin entry point
- `inc/cpm-dongtrader-loader.php` - Loads all required files
- `inc/cpm-dongtrader-functions.php` - Core business logic
- `inc/cpm-woocommerce-functions.php` - WooCommerce integration
- `inc/cpm-dongtrader-msc-functions.php` - Miscellaneous functions
- `inc/cpm-dongtrader-custom-tables.php` - Database table creation
- `inc/libs/payment-gateway.php` - Custom Preorder payment gateway
- `template-parts/` - Frontend display templates

**Responsibilities:**
- Order processing and membership assignment
- XP/YAM calculations
- MLM database management
- User role management
- Treasury tracking
- Wallet display

#### **cpm-twilio** (OTP & Scan System)
**Location:** `wp-content/plugins/cpm-twilio/`

**Key Files:**
- `twilio-main.php` - Backend AJAX handlers
- `assets/js/cpm-twilio-script.js` - Frontend JavaScript

**Responsibilities:**
- Phone number verification via OTP
- QR code scanning logic
- 2-scan system transaction matching
- Buyer/seller/personal scan processing
- Transaction code verification

### 1.2 Theme
**Location:** `wp-content/themes/hello-elementor-child/`

**Key Files:**
- `functions.php` - Payment gateway filtering
- `woocommerce/checkout/thankyou.php` - Order confirmation page

---

## 2. Core Systems

### 2.1 Order Placement & Payment Flow

#### **Flow Diagram:**
```
Customer Adds Product to Cart
         ↓
Customer Goes to Checkout
         ↓
Customer Fills Form & Submits
         ↓
Order Created (status: pending)
         ↓
├─ Hook 7: Set Membership Level (mega_set_membership_level)
├─ Hook 8: Calculate Order Meta (mega_custom_ordermeta_update)
└─ Hook 9: Update MLM Database (mega_update_mlm_database)
         ↓
Payment Processed (Preorder/PayPal/Venmo)
         ↓
Order Status Changes (pending → processing → completed)
         ↓
Save Buyer Details to User Meta (if completed)
         ↓
Order Appears in User Account
```

#### **Key Hooks:**
1. `woocommerce_checkout_order_created` (Priority 7)
   - Function: `mega_set_membership_level()`
   - Assigns membership: YAMer, Patron, or MEGAvoter

2. `woocommerce_checkout_order_created` (Priority 8)
   - Function: `mega_custom_ordermeta_update()`
   - Calculates: 7% buyer rebate, 3% seller cashback, treasury amounts

3. `woocommerce_checkout_order_created` (Priority 9)
   - Function: `mega_update_mlm_database()`
   - Updates MLM tables: `mega_mlm_customers`, `mega_mlm_purchases`

4. `woocommerce_order_status_completed`
   - Function: `dong_auto_set_user_role_on_checkout()`
   - Sets user role based on product purchased

5. `woocommerce_checkout_order_created` (Priority 10)
   - Function: `mega_set_preorder_status_to_pending()`
   - Ensures preorder orders start as "pending" status

6. `woocommerce_order_status_changed`
   - Function: `mega_prevent_preorder_wrong_status()`
   - Prevents preorders from wrong status transitions

#### **Payment Gateways:**
- **Preorder Gateway** (`WC_Preorder_Gateway`)
  - Custom gateway for deferred payment
  - Orders start as "pending"
  - After payment: status → "completed"
  
- **PayPal** (WooCommerce PayPal Gateway)
  - Standard PayPal integration
  - Sandbox mode available
  
- **Venmo** (`momo-venmo` plugin)
  - Venmo payment integration
  - Two variants: `venmo` and `venmo-pay`

#### **Payment Gateway Filtering:**
**Location:** `wp-content/themes/hello-elementor-child/functions.php` (lines 295-327)

**Logic:**
- If product ID 2481 or 1308 in cart → Hide Preorder, Show PayPal/Venmo
- Otherwise → Hide PayPal/Venmo, Show Preorder
- If `pay_for_order` parameter → Hide Preorder

---

### 2.2 2-Scan Proof of Delivery (PoD) System

#### **Overview:**
A dual-scan verification system where sellers and buyers scan the same QR code to verify product delivery.

#### **Base Trade Value:**
- **$10.30 USD** per transaction
- Distributed as:
  - Seller: 3% = $0.309
  - Buyer: 7% = $0.721
  - Personal: 10% = $1.03

#### **Scan Types:**

1. **Seller Scan (3%)**
   - Seller scans QR code when delivering product
   - Status: `pending` (waiting for buyer)
   - Stored in: `seller_scan` usermeta
   - Earns: 3% of trade value

2. **Buyer Scan (7%)**
   - Buyer scans same QR code to confirm receipt
   - System matches with seller's scan using `proof_id`
   - Updates seller's status: `pending` → `confirmed`
   - Status: `confirmed` (if seller matched)
   - Stored in: `buyer_scan` usermeta
   - Earns: 7% of trade value

3. **Personal Scan (10%)**
   - Personal user scans for their own transaction
   - Status: Always `confirmed`
   - Stored in: `personal_scan` usermeta
   - Earns: 10% of trade value

#### **Transaction Code System:**
- Each seller scan generates a unique `transaction_id`
- Buyer enters transaction code to match with seller
- System searches all `seller_scan` entries to find match
- After match: Buyer redirected to payment page if pending orders exist

#### **Data Storage:**
- **User Meta:** `seller_scan`, `buyer_scan`, `personal_scan`
- **Options:** `treasury_reminder` (centralized transaction log)

#### **Duplicate Prevention:**
- Checks if `proof_id` already exists for user
- Prevents duplicate scans
- Shows error: "Product qr is already scanned"

---

### 2.3 XP & YAM Token System

#### **Current Conversion Rates:**
- **1 USD = 10²³ XP** (1,000,000,000,000,000,000,000 XP)
- **21,000 YAM = 1 USD**
- **1 YAM = 47,619,047,619,047,619 XP**

#### **Proposed Changes (From Requirements):**
- **1 penny = 10²¹ XP** (new constant: `XP_PER_PENNY`)
- **1 dollar = 100 pennies = 10²³ XP**
- **Dust concept:** XP < 10²¹ cannot be redeemed
- **Redemption rules:** Only whole dollars (≥ $1.00)

#### **XP Earning Sources:**
1. **Order Purchases:**
   - YAMer: 0 XP
   - Paid memberships: 10,000,000 XP (default)

2. **2-Scan System:**
   - Seller: 0.006489 XP per transaction
   - Buyer: 0.015141 XP per transaction
   - Personal: 0.02163 XP per transaction

3. **XP Transfers:**
   - Users can send XP to other users
   - Stored in: `xp_transactions` table

#### **XP Status:**
- **Released (🟢):** Available immediately (Discord verified)
- **Pending (🟡):** Awaiting Discord verification
- **Completed:** Fully matured and accessible

---

### 2.4 Membership System

#### **Membership Levels (Paid Memberships Pro):**

1. **YAMer**
   - Free membership
   - 0 XP awarded
   - Entry-level participation

2. **MEGAvoter**
   - $12/year
   - Referral-based
   - Influences social impact fund

3. **Patron**
   - $360/year
   - 10,000,000 XP awarded
   - Full patronage access
   - POC (Point of Contact) privileges

#### **Membership Assignment:**
- Automatic on order creation
- Based on product IDs in order
- Stored in order meta: `_membership_name`, `_membership_type`

---

### 2.5 MLM (Multi-Level Marketing) System

#### **Database Tables:**

1. **`wp_mega_mlm_customers`**
   - Stores MLM customer records
   - Fields: `user_id`, `upline_id`, `customer_group_id`, `glassfrog_person_id`

2. **`wp_mega_mlm_purchases`**
   - Stores purchase records
   - Fields: `sponsor_id`, `customer_id`, `order_id`, `allocation_status`

3. **`wp_mega_mlm_groups`**
   - Stores group information
   - Fields: `group_id`, `group_members`, `circle_id`, `group_leader`

#### **MLM Logic:**
- Tracks sponsor/affiliate relationships
- Stores in order meta: `mega_affid` (sponsor user ID)
- Updates sponsor's `_buyer_details` when order completed

---

## 3. Database Structure

### 3.1 WordPress Core Tables
- `wp_users` - User accounts
- `wp_usermeta` - User metadata
- `wp_posts` - Orders (WooCommerce)
- `wp_postmeta` - Order metadata
- `wp_options` - System options

### 3.2 Custom Tables

#### **`wp_dong_order_export_table`**
- Customer export data
- Fields: customer info, membership, sector, affiliate

#### **`wp_mega_mlm_customers`**
- MLM customer records
- Links users to upline and groups

#### **`wp_mega_mlm_purchases`**
- Purchase tracking
- Links sponsors to customers

#### **`wp_mega_mlm_groups`**
- Group management
- Leader tracking

#### **`wp_release_groups_profit`**
- Profit distribution tracking

#### **`xp_transactions`** (if exists)
- XP transfer history

### 3.3 User Meta Keys

#### **Scan Data:**
- `seller_scan` - Array of seller scan transactions
- `buyer_scan` - Array of buyer scan transactions
- `personal_scan` - Array of personal scan transactions

#### **Order Data:**
- `_buyer_details` - Array of buyer order details (currently disabled)
- `_treasury_details` - Treasury entries
- `_seller_details` - Seller transaction details

#### **User Roles:**
- `dong_user_role` - Custom role (Planning, Budget, Media, Distribution, Membership)

#### **Other:**
- `_discord_invite` - Discord verification data

### 3.4 Order Meta Keys

#### **Membership:**
- `_membership_type` - Membership level ID
- `_membership_name` - Membership name (YAMer/Patron/MEGAvoter)

#### **Financial:**
- `mega_cashback_v` - 7% buyer rebate
- `mega_cashback_d` - 3% seller cashback
- `mega_treasury` - Treasury amount
- `mega_reserve` - Reserve amount
- `mega_affid` - Sponsor/affiliate user ID

#### **Status:**
- `is_preorder` - Boolean flag for preorder orders

---

## 4. Frontend Components

### 4.1 My Account Pages

#### **Orders Page** (`content-detente-orders.php`)
**Displays:**
1. **"My orders" Table**
   - All WooCommerce orders for user
   - Columns: Order, Date, Membership, Total, Status, Actions
   - Fetches directly from `wc_get_orders()`
   - Fixes preorder statuses before display

2. **"Unpaid Backorders" Table**
   - Unpaid orders (`is_paid() == false` AND `is_preorder == true`)
   - Columns: Order, Date, Total, 7% (unfunded), Status, Actions
   - Shows "Pay Now" button if payable

#### **Wallet Page** (`content-detente-wallet.php`)
**Displays:**
- Total XP Balance
- YAM Tokens equivalent
- USD Trade Value
- Confirmed Deliveries count
- Leaderboard Rank
- XP Breakdown (Buyer/Seller/Personal)
- Transaction History

#### **Redemption Page** (`content-redemption.php`)
**Displays:**
- Redeemable amount
- Redemption form
- Minimum $1.00 requirement

#### **Treasury Page** (`content-detente-treasury.php`)
**Displays:**
- Treasury totals
- Distribution breakdown

### 4.2 Checkout Page
- Custom fields: Mobile number, Social impact choice
- Payment method selection
- XP display (currently removed/commented out)

### 4.3 Thank You Page
- Order confirmation
- XP rewards display (currently removed/commented out)

---

## 5. Recent Changes & Current State

### 5.1 XP Removal from Checkout
**Status:** ✅ Completed
- Removed XP display from checkout page
- Removed XP calculation during order creation
- Removed XP from thank you page

### 5.2 Preorder Status Management
**Status:** ✅ Completed
- Added hooks to ensure preorder orders are "pending" if unpaid
- Added hooks to set preorder orders to "completed" after payment
- Prevents status from changing to "on-hold" incorrectly

### 5.3 Order Display Updates
**Status:** ✅ Completed
- "My orders" table now fetches all WooCommerce orders directly
- "Unpaid Backorders" table shows order status
- Fixed preorder status display issues

### 5.4 2-Scan System Buyer Flow
**Status:** ✅ Completed
- After transaction code match, buyer redirected to payment page
- Removed success popup with XP amount
- AJAX endpoint `ct_get_pending_orders` added

### 5.5 Buyer Details Storage
**Status:** ⚠️ Partially Disabled
- `_buyer_details` saving commented out in `mega_save_order_details()`
- Orders still saved to WooCommerce
- "My orders" table uses WooCommerce orders directly

---

## 6. Integration Points

### 6.1 Twilio Integration
- **Purpose:** OTP verification for phone numbers
- **Location:** `cpm-twilio` plugin
- **Flow:** User enters phone → OTP sent → User verifies → Login

### 6.2 WooCommerce Integration
- **Hooks Used:**
  - `woocommerce_before_checkout_form`
  - `woocommerce_after_checkout_validation`
  - `woocommerce_checkout_order_created`
  - `woocommerce_order_status_changed`
  - `woocommerce_order_status_completed`
  - `woocommerce_before_order_object_save`
  - `woocommerce_review_order_after_order_total`
  - `woocommerce_thankyou`
  - `woocommerce_available_payment_gateways`

### 6.3 Paid Memberships Pro (PMPro)
- **Purpose:** Membership level management
- **Functions Used:**
  - `pmpro_changeMembershipLevel()`
  - `create_yamer_membership_level_if_not_exists()`

### 6.4 Payment Gateways
- **Preorder:** Custom gateway (`WC_Preorder_Gateway`)
- **PayPal:** WooCommerce PayPal Gateway
- **Venmo:** `momo-venmo` plugin

---

## 7. Key Functions Reference

### 7.1 Order Processing
- `mega_set_membership_level($order)` - Assigns membership on order creation
- `mega_custom_ordermeta_update($order)` - Calculates order distribution
- `mega_update_mlm_database($order)` - Updates MLM tables
- `mega_set_preorder_status_to_pending($order)` - Sets preorder to pending
- `mega_prevent_preorder_wrong_status($order_id, $old_status, $new_status)` - Prevents wrong status
- `mega_fix_preorder_status_before_save($order)` - Fixes status before save

### 7.2 2-Scan System
- `ct_verify_transaction_code()` - Verifies buyer transaction code
- `ct_insert_scan_data()` - Inserts scan data for seller/buyer/personal
- `proceedWithBuyerDataInsertion()` - Frontend buyer scan handler
- `redirectToPaymentPageIfPendingOrders()` - Redirects buyer to payment

### 7.3 XP & Wallet
- `dongtrader_display_xp_dashboard()` - Displays wallet dashboard
- `dongtrader_usd_to_xp($usd)` - Converts USD to XP
- `dongtrader_xp_to_yam($xp)` - Converts XP to YAM
- `dongtrader_xp_to_usd($xp)` - Converts XP to USD

### 7.4 User Management
- `dong_set_user_role($user_id, $product_id)` - Sets user role
- `dong_auto_set_user_role_on_checkout($order_id)` - Auto-assigns role on checkout

---

## 8. Potential Issues & Improvements

### 8.1 Known Issues

1. **XP System Inconsistency**
   - Current system uses: 1 USD = 10²³ XP
   - Requirements specify: 1 penny = 10²¹ XP
   - **Status:** Not yet implemented

2. **Buyer Details Storage Disabled**
   - `_buyer_details` saving is commented out
   - May affect features that depend on this data
   - **Status:** Orders still work via WooCommerce

3. **Payment Gateway Logic**
   - Complex conditional logic for showing/hiding gateways
   - May need simplification
   - **Status:** Working but complex

4. **Preorder Status Management**
   - Multiple hooks to manage preorder status
   - May cause conflicts if not careful
   - **Status:** Working but fragile

### 8.2 Recommended Improvements

1. **Implement Dust Concept**
   - Add `XP_PER_PENNY` constant (10²¹)
   - Update wallet display to show three layers
   - Update redemption logic

2. **Simplify Status Management**
   - Consolidate preorder status hooks
   - Add unit tests for status transitions

3. **Database Optimization**
   - Index frequently queried usermeta keys
   - Consider caching for treasury calculations

4. **Error Handling**
   - Add better error messages for payment failures
   - Log payment gateway errors

5. **Documentation**
   - Add inline code comments
   - Create API documentation

---

## 9. File Structure Summary

```
wp-content/
├── plugins/
│   ├── cpm-dongtrader/
│   │   ├── cpm-dongtrader.php (main entry)
│   │   ├── inc/
│   │   │   ├── cpm-dongtrader-loader.php
│   │   │   ├── cpm-dongtrader-functions.php (core logic)
│   │   │   ├── cpm-woocommerce-functions.php (WooCommerce)
│   │   │   ├── cpm-dongtrader-msc-functions.php (misc)
│   │   │   ├── cpm-dongtrader-custom-tables.php (DB)
│   │   │   └── libs/
│   │   │       └── payment-gateway.php (Preorder gateway)
│   │   └── template-parts/
│   │       ├── content-detente-orders.php
│   │       ├── content-detente-wallet.php
│   │       ├── content-redemption.php
│   │       └── ...
│   └── cpm-twilio/
│       ├── twilio-main.php (backend)
│       └── assets/js/
│           └── cpm-twilio-script.js (frontend)
└── themes/
    └── hello-elementor-child/
        ├── functions.php
        └── woocommerce/
            └── checkout/
                └── thankyou.php
```

---

## 10. Configuration & Settings

### 10.1 Plugin Settings
**Location:** WordPress Admin → Detente Settings

**Settings Stored In:**
- `dongtraders_api_settings_fields` option
- Contains: YAMer product IDs, membership settings, etc.

### 10.2 WooCommerce Settings
- Payment gateways configured in WooCommerce → Settings → Payments
- Order statuses: pending, processing, on-hold, completed, cancelled

### 10.3 PMPro Settings
- Membership levels configured in Paid Memberships Pro
- Levels: YAMer, MEGAvoter, Patron

---

## 11. Security Considerations

1. **Nonce Verification**
   - All AJAX endpoints verify nonces
   - Multiple nonce types supported

2. **User Capability Checks**
   - Admin functions check `manage_options`
   - User data access restricted to own data

3. **Data Sanitization**
   - Input sanitized with `sanitize_text_field()`
   - Output escaped with `esc_html()`, `esc_attr()`

4. **SQL Injection Prevention**
   - Uses `$wpdb->prepare()` for queries
   - Parameterized queries

---

## 12. Testing Checklist

### 12.1 Order Flow
- [ ] Add product to cart
- [ ] Complete checkout with Preorder
- [ ] Complete checkout with PayPal
- [ ] Complete checkout with Venmo
- [ ] Verify order status transitions
- [ ] Verify membership assignment

### 12.2 2-Scan System
- [ ] Seller scan QR code
- [ ] Buyer scan same QR code
- [ ] Transaction code matching
- [ ] Duplicate prevention
- [ ] XP/YAM calculations

### 12.3 Payment
- [ ] Preorder payment flow
- [ ] PayPal sandbox payment
- [ ] Venmo payment
- [ ] Order status after payment

### 12.4 Display
- [ ] Orders page displays all orders
- [ ] Unpaid backorders shows correct orders
- [ ] Wallet displays correct balances
- [ ] Redemption page works

---

## 13. Future Development

### 13.1 Planned Features (From Requirements)
1. **Wallet/XP System Redesign**
   - Implement dust concept
   - Three-layer wallet display
   - Update redemption rules

2. **Landing Page (humanblockchain.info)**
   - Referral funnel
   - Discord integration
   - QRtiger integration
   - Membership selection flow

### 13.2 Technical Debt
1. Consolidate preorder status hooks
2. Refactor payment gateway filtering
3. Update XP conversion constants
4. Add comprehensive error logging
5. Improve code documentation

---

## Conclusion

This is a complex, multi-faceted system that combines e-commerce, reward systems, MLM tracking, and custom payment processing. The codebase is functional but has areas that need refactoring and updates to align with new requirements (dust concept, landing page).

**Key Strengths:**
- Comprehensive order management
- Flexible membership system
- Robust 2-scan verification
- Multiple payment options

**Areas for Improvement:**
- XP system needs update to new constants
- Status management could be simplified
- Better error handling needed
- More comprehensive testing

---

*Last Updated: Based on comprehensive codebase analysis*
*Analysis Date: Current*

