# Complete Site Flow Documentation

## Table of Contents
1. [User Authentication Flow](#1-user-authentication-flow)
2. [Order Placement & Payment Flow](#2-order-placement--payment-flow)
3. [2-Scan Proof of Delivery Flow](#3-2-scan-proof-of-delivery-flow)
4. [XP/YAM Token System Flow](#4-xpyam-token-system-flow)
5. [Membership Assignment Flow](#5-membership-assignment-flow)
6. [MLM (Multi-Level Marketing) Flow](#6-mlm-multi-level-marketing-flow)
7. [Redemption Flow](#7-redemption-flow)
8. [QR Code Generation & Scanning Flow](#8-qr-code-generation--scanning-flow)

---

## 1. User Authentication Flow

### 1.1 Phone-Based Login (OTP Verification)

**Entry Point:** User visits site and sees OTP login form (shortcode: `[cpm_twilio_otp]`)

#### Step-by-Step Flow:

```
User Enters Phone Number
         ↓
System Validates Phone Format (10 digits)
         ↓
AJAX: ct_verify_user_phone_number
         ↓
System Checks if Phone Exists in User Meta (mega-mobile)
         ↓
├─ Phone NOT Found → Error: "Phone number does not belong to any user"
└─ Phone Found → Returns User ID + Nonce
         ↓
User Clicks "Send OTP"
         ↓
AJAX: ct_send_twilio_otp
         ↓
System Determines Country Code:
├─ Nepal (NP) → +977
└─ Other → +1
         ↓
Twilio API Sends OTP via SMS
         ↓
User Receives 6-Digit OTP
         ↓
User Enters OTP (6 individual input fields)
         ↓
AJAX: ct_validate_twilio_otp
         ↓
Twilio API Verifies OTP
         ↓
├─ OTP Invalid → Error: "Invalid OTP"
└─ OTP Valid → Returns Success + Login Nonce
         ↓
AJAX: ct_user_signin
         ↓
System Logs User In:
├─ wp_set_current_user($user_id)
├─ wp_set_auth_cookie($user_id)
└─ Redirects to Wallet Page or Pending Orders
```

**Key Files:**
- Frontend: `wp-content/plugins/cpm-twilio/assets/js/cpm-twilio-script.js`
- Backend: `wp-content/plugins/cpm-twilio/twilio-main.php`
- Shortcode: `ct_twilio_otp_fields()` (line 12)

**AJAX Endpoints:**
1. `ct_verify_user_phone_number` - Validates phone number exists
2. `ct_send_twilio_otp` - Sends OTP via Twilio
3. `ct_validate_twilio_otp` - Verifies OTP code
4. `ct_user_signin` - Logs user in after OTP verification

---

## 2. Order Placement & Payment Flow

### 2.1 Complete Order Flow

```
Customer Browses Products
         ↓
Customer Adds Product to Cart
         ↓
Customer Goes to Checkout Page (/checkout)
         ↓
Checkout Form Loads:
├─ Billing Details
├─ Shipping Details
├─ Mobile Number (mega-mobile) - Custom Field
├─ Social Impact Choice - Custom Field
└─ Payment Method Selection
         ↓
Validation: woocommerce_after_checkout_validation
├─ Validates Mobile Number Format
├─ Checks for Duplicate Phone Numbers
└─ Validates Other Required Fields
         ↓
Customer Clicks "Place Order"
         ↓
Order Created (Status: pending)
         ↓
┌─────────────────────────────────────────┐
│  HOOK 1: woocommerce_checkout_order_created (Priority 7) │
│  Function: mega_set_membership_level()                  │
│  - Determines membership based on products:              │
│    • YAMer (free) - If YAMer products in cart          │
│    • Patron ($360/year) - If Patron products           │
│    • MEGAvoter ($12/year) - If MEGA products           │
│  - Assigns membership via PMPro                        │
│  - Saves to order meta: _membership_type, _membership_name │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  HOOK 2: woocommerce_checkout_order_created (Priority 8) │
│  Function: mega_custom_ordermeta_update()                │
│  - Calculates financial distribution:                    │
│    • mega_cashback_v = 7% buyer rebate                   │
│    • mega_cashback_d = 3% seller cashback                 │
│    • mega_treasury = Remaining amount                    │
│    • mega_reserve = Reserve amount                       │
│  - Saves all amounts to order meta                       │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  HOOK 3: woocommerce_checkout_order_created (Priority 9) │
│  Function: mega_update_mlm_database()                     │
│  - Updates MLM tables:                                   │
│    • wp_mega_mlm_customers                               │
│    • wp_mega_mlm_purchases                               │
│  - Links sponsor/affiliate (mega_affid)                  │
│  - If Patron: Saves order details & treasury             │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  HOOK 4: woocommerce_checkout_order_created (Priority 10) │
│  Function: mega_set_preorder_status_to_pending()          │
│  - Ensures preorder orders start as "pending"             │
└─────────────────────────────────────────┘
         ↓
Payment Processing
         ↓
┌─────────────────────────────────────────┐
│  PAYMENT GATEWAY SELECTION              │
│  Based on cart contents:                │
│  - Product ID 2481 or 1308 → PayPal/Venmo only          │
│  - Other products → Preorder only                        │
│  - pay_for_order parameter → Hide Preorder               │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  PREORDER GATEWAY                      │
│  - Order stays "pending"                │
│  - User can pay later                  │
│  - Appears in "Unpaid Backorders"      │
└─────────────────────────────────────────┘
         ↓
OR
         ↓
┌─────────────────────────────────────────┐
│  PAYPAL/VENMO GATEWAY                  │
│  - Payment processed immediately        │
│  - Order status → "processing"          │
│  - Then → "completed"                   │
└─────────────────────────────────────────┘
         ↓
Order Status Changes
         ↓
┌─────────────────────────────────────────┐
│  HOOK: woocommerce_order_status_changed │
│  Function: mega_prevent_preorder_wrong_status()            │
│  - Prevents preorder from wrong status transitions       │
│  - Unpaid preorder → Always "pending"                     │
│  - Paid preorder → "completed"                            │
└─────────────────────────────────────────┘
         ↓
┌─────────────────────────────────────────┐
│  HOOK: woocommerce_order_status_completed │
│  Function: dong_auto_set_user_role_on_checkout()          │
│  - Sets user role based on product purchased              │
│  - Calls: dong_set_user_role($user_id, $product_id)       │
└─────────────────────────────────────────┘
         ↓
Order Appears in User Account
├─ "My Orders" Table (all orders)
└─ "Unpaid Backorders" Table (if unpaid)
```

### 2.2 Payment Gateway Details

#### Preorder Gateway
- **Class:** `WC_Preorder_Gateway`
- **Location:** `wp-content/plugins/cpm-dongtrader/inc/libs/payment-gateway.php`
- **Behavior:**
  - Orders created with status: `pending`
  - User can pay later via "Pay Now" button
  - After payment: `payment_complete()` → status `completed`

#### PayPal Gateway
- **Type:** WooCommerce PayPal Gateway
- **Sandbox Mode:** Available
- **Configuration:**
  - Sandbox enabled → Use sandbox business email
  - Sandbox disabled → Use live PayPal email
- **Flow:** Standard PayPal checkout → Redirect → Payment confirmation

#### Venmo Gateway
- **Plugin:** `momo-venmo`
- **Variants:** `venmo`, `venmo-pay`
- **Flow:** Venmo payment processing

**Key Files:**
- Order Processing: `wp-content/plugins/cpm-dongtrader/inc/cpm-woocommerce-functions.php`
- Payment Gateway: `wp-content/plugins/cpm-dongtrader/inc/libs/payment-gateway.php`
- Gateway Filtering: `wp-content/themes/hello-elementor-child/functions.php` (lines 295-327)

---

## 3. 2-Scan Proof of Delivery Flow

### 3.1 Overview
A dual-scan verification system where sellers and buyers scan the same QR code to verify product delivery.

**Base Trade Value:** $10.30 USD per transaction

**Distribution:**
- Seller: 3% = $0.309
- Buyer: 7% = $0.721
- Personal: 10% = $1.03

### 3.2 Seller Scan Flow (3%)

```
Seller Receives Product with QR Code
         ↓
Seller Opens Site & Scans QR Code
         ↓
URL Contains: ?scan_type=proof&proof_id=PRODUCT_ID_TIMESTAMP
         ↓
System Checks if User is Logged In
         ↓
├─ Not Logged In → OTP Login Required
└─ Logged In → Continue
         ↓
System Checks if proof_id Already Scanned
         ↓
├─ Already Scanned → Error: "Product qr is already scanned"
└─ Not Scanned → Continue
         ↓
System Shows Role Selection Popup:
├─ Seller (3%)
├─ Buyer (7%)
└─ Personal (10%)
         ↓
Seller Selects "Seller (3%)"
         ↓
System Generates Transaction ID (unique)
         ↓
System Saves to User Meta: seller_scan
├─ proof_id
├─ transaction_id (unique)
├─ scan_type: "seller"
├─ status: "pending" (waiting for buyer)
├─ timestamp
├─ trade_value: $10.30
└─ reward: 3% = $0.309
         ↓
System Shows Success Message
         ↓
Seller Receives Transaction Code
         ↓
Seller Shares Transaction Code with Buyer
```

### 3.3 Buyer Scan Flow (7%)

```
Buyer Receives Product with QR Code
         ↓
Buyer Opens Site & Scans QR Code
         ↓
URL Contains: ?scan_type=proof&proof_id=PRODUCT_ID_TIMESTAMP
         ↓
System Checks if User is Logged In
         ↓
├─ Not Logged In → OTP Login Required
└─ Logged In → Continue
         ↓
System Shows Role Selection Popup
         ↓
Buyer Selects "Buyer (7%)"
         ↓
System Shows Transaction Code Input Popup
         ↓
Buyer Enters Transaction Code (from Seller)
         ↓
AJAX: ct_verify_transaction_code
         ↓
System Searches All seller_scan Entries:
├─ Searches by transaction_id
├─ Finds Matching Seller Entry
└─ Returns: seller_id, entry_index
         ↓
├─ Transaction Code NOT Found → Error: "Invalid transaction code"
└─ Transaction Code Found → Continue
         ↓
System Saves to User Meta: buyer_scan
├─ proof_id
├─ transaction_id (from seller)
├─ scan_type: "buyer"
├─ status: "confirmed" (matched with seller)
├─ seller_id
├─ seller_entry_index
├─ timestamp
├─ trade_value: $10.30
└─ reward: 7% = $0.721
         ↓
System Updates Seller's Entry:
├─ Changes status: "pending" → "confirmed"
└─ Links buyer_id to seller entry
         ↓
System Checks for Pending Orders
         ↓
AJAX: ct_get_pending_orders
         ↓
├─ Pending Orders Exist → Redirect to Payment Page (Latest Order)
└─ No Pending Orders → Redirect to Orders Page
```

### 3.4 Personal Scan Flow (10%)

```
User Scans QR Code for Personal Transaction
         ↓
System Shows Role Selection Popup
         ↓
User Selects "Personal (10%)"
         ↓
System Checks if proof_id Already Scanned
         ↓
├─ Already Scanned → Error
└─ Not Scanned → Continue
         ↓
System Saves to User Meta: personal_scan
├─ proof_id
├─ scan_type: "personal"
├─ status: "confirmed" (always confirmed)
├─ timestamp
├─ trade_value: $10.30
└─ reward: 10% = $1.03
         ↓
System Shows Success Message
```

**Key Files:**
- Backend: `wp-content/plugins/cpm-twilio/twilio-main.php`
  - `ct_insert_scan_data()` - Saves scan data
  - `ct_verify_transaction_code()` - Verifies buyer transaction code
- Frontend: `wp-content/plugins/cpm-twilio/assets/js/cpm-twilio-script.js`
  - `proceedWithBuyerDataInsertion()` - Buyer scan handler
  - `redirectToPaymentPageIfPendingOrders()` - Redirects to payment

**Data Storage:**
- User Meta Keys: `seller_scan`, `buyer_scan`, `personal_scan`
- Format: Serialized arrays containing transaction objects

---

## 4. XP/YAM Token System Flow

### 4.1 XP Earning Sources

#### Source 1: Order Purchases
```
User Places Order
         ↓
Order Status → Completed
         ↓
System Checks Membership Type:
├─ YAMer → 0 XP
├─ Patron → 10,000,000 XP
└─ MEGAvoter → 0 XP (default)
         ↓
XP Added to User Balance
```

#### Source 2: 2-Scan System
```
User Completes Scan Transaction
         ↓
System Calculates XP Based on Reward:
├─ Seller (3% = $0.309) → 0.006489 XP
├─ Buyer (7% = $0.721) → 0.015141 XP
└─ Personal (10% = $1.03) → 0.02163 XP
         ↓
XP Added to User Balance
```

**Current Conversion Rates:**
- 1 USD = 10²³ XP (1,000,000,000,000,000,000,000 XP)
- 21,000 YAM = 1 USD
- 1 YAM = 47,619,047,619,047,619 XP

### 4.2 XP Display Flow

```
User Visits Wallet Page (/my-account/detente-wallet/)
         ↓
System Loads User Data:
├─ seller_scan entries
├─ buyer_scan entries
├─ personal_scan entries
└─ Order history
         ↓
System Calculates Total XP:
├─ Buyer XP (from orders)
├─ Seller XP (from seller_scan)
├─ Buyer Scan XP (from buyer_scan)
└─ Personal XP (from personal_scan)
         ↓
System Displays:
├─ Total XP Balance
├─ YAM Tokens Equivalent
├─ USD Trade Value
├─ Confirmed Deliveries Count
├─ Leaderboard Rank
└─ Transaction History
```

### 4.3 XP Transfer Flow

```
User Visits XP Transfers Page
         ↓
User Enters Recipient (Phone/Email/Username)
         ↓
User Enters XP Amount
         ↓
System Validates:
├─ Recipient exists
├─ XP amount is valid
└─ User has sufficient balance
         ↓
System Processes Transfer:
├─ Deducts XP from Sender
├─ Adds XP to Recipient
└─ Records in xp_transactions table
         ↓
System Shows Success Message
```

**Key Files:**
- Wallet Display: `wp-content/plugins/cpm-dongtrader/template-parts/content-detente-wallet.php`
- XP Transfers: `wp-content/plugins/cpm-dongtrader/template-parts/content-xp-transfers.php`
- Functions: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php`

---

## 5. Membership Assignment Flow

### 5.1 Automatic Membership Assignment

```
Order Created
         ↓
Hook: woocommerce_checkout_order_created (Priority 7)
         ↓
Function: mega_set_membership_level($order)
         ↓
System Checks Products in Order:
├─ YAMer Products → Assign YAMer (Level 0, Free)
├─ Patron Products → Assign Patron (Level 18, $360/year)
└─ MEGAvoter Products → Assign MEGAvoter (Level 17, $12/year)
         ↓
System Calls: pmpro_changeMembershipLevel()
         ↓
Membership Assigned to User
         ↓
System Saves to Order Meta:
├─ _membership_type (Level ID)
└─ _membership_name (YAMer/Patron/MEGAvoter)
```

### 5.2 Membership Levels

| Level | Name | Price | XP Awarded | Description |
|-------|------|-------|------------|-------------|
| 0 | YAMer | Free | 0 XP | Entry-level membership |
| 17 | MEGAvoter | $12/year | 0 XP | Referral-based membership |
| 18 | Patron | $360/year | 10,000,000 XP | Full patronage access |

**Key Files:**
- Assignment: `wp-content/plugins/cpm-dongtrader/inc/cpm-woocommerce-functions.php`
- Function: `mega_set_membership_level()` (line ~1240)

---

## 6. MLM (Multi-Level Marketing) Flow

### 6.1 MLM Database Update Flow

```
Order Created
         ↓
Hook: woocommerce_checkout_order_created (Priority 9)
         ↓
Function: mega_update_mlm_database($orderObj)
         ↓
System Checks for Sponsor/Affiliate:
├─ Checks order meta: mega_affid (sponsor user ID)
└─ If exists, links customer to sponsor
         ↓
System Updates Tables:
├─ wp_mega_mlm_customers
│  - user_id
│  - upline_id (sponsor)
│  - customer_group_id
│  - glassfrog_person_id
│
└─ wp_mega_mlm_purchases
   - sponsor_id
   - customer_id
   - order_id
   - allocation_status
         ↓
If Patron Membership:
├─ Calls: mega_save_order_details()
└─ Calls: mega_save_my_treasury()
```

### 6.2 MLM Database Structure

**Table: wp_mega_mlm_customers**
- Stores customer records
- Links users to upline (sponsor)
- Links users to groups

**Table: wp_mega_mlm_purchases**
- Stores purchase records
- Links sponsors to customers
- Tracks allocation status

**Table: wp_mega_mlm_groups**
- Stores group information
- Tracks group leaders
- Manages profit distribution

**Key Files:**
- Database Creation: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-custom-tables.php`
- MLM Logic: `wp-content/plugins/cpm-dongtrader/inc/cpm-woocommerce-functions.php`
- Function: `mega_update_mlm_database()` (line ~1030)

---

## 7. Redemption Flow

### 7.1 Redemption Process

```
User Visits Redemption Page (/my-account/redemption/)
         ↓
System Calculates Redeemable Amount:
├─ Total XP Balance
├─ Converts to USD
└─ Applies Minimum $1.00 Requirement
         ↓
User Enters Redemption Amount
         ↓
System Validates:
├─ Amount ≥ $1.00
├─ Amount ≤ Available Balance
└─ User has confirmed deliveries
         ↓
User Submits Redemption Request
         ↓
System Creates Redemption Record:
├─ Saves to user meta or database
├─ Status: "pending"
└─ Timestamp
         ↓
Admin Reviews Request
         ↓
Admin Approves/Rejects
         ↓
├─ Approved → XP Deducted, Payment Processed
└─ Rejected → Request Cancelled
```

**Key Files:**
- Redemption Page: `wp-content/plugins/cpm-dongtrader/template-parts/content-redemption.php`
- Admin Interface: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-admin-tables.php`

---

## 8. QR Code Generation & Scanning Flow

### 8.1 QR Code Generation

```
Admin/User Generates QR Code for Product
         ↓
System Creates Unique proof_id:
├─ Format: PRODUCT_ID_TIMESTAMP
└─ Example: 1234_1699123456
         ↓
System Generates QR Code URL:
├─ Base URL: Site URL
├─ Parameters:
│  - scan_type: "proof" or "checkout"
│  - proof_id: PRODUCT_ID_TIMESTAMP
└─ Example: https://site.com/?scan_type=proof&proof_id=1234_1699123456
         ↓
System Saves QR Code Data:
├─ Product ID
├─ proof_id
├─ Timestamp
└─ QR Code Image URL
         ↓
QR Code Displayed/Downloaded
```

### 8.2 QR Code Scanning

```
User Scans QR Code with Mobile Device
         ↓
QR Code Contains URL with Parameters
         ↓
Browser Opens URL:
├─ ?scan_type=proof → Proof of Delivery Flow
└─ ?scan_type=checkout → Checkout Flow
         ↓
System Detects scan_type Parameter
         ↓
System Checks User Authentication:
├─ Logged In → Continue
└─ Not Logged In → OTP Login Required
         ↓
System Processes Based on scan_type:
├─ proof → 2-Scan System Flow (Seller/Buyer/Personal)
└─ checkout → Direct to Checkout with Product
```

**Key Files:**
- QR Generation: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-qrmetas.php`
- QR Meta: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-product-meta.php`

---

## 9. Complete User Journey Examples

### 9.1 New User - First Purchase

```
1. User Visits Site
2. User Sees OTP Login Form
3. User Enters Phone Number → OTP Sent
4. User Enters OTP → Logged In
5. User Browses Products
6. User Adds Product to Cart
7. User Goes to Checkout
8. User Fills Form (including mobile number)
9. User Selects Payment Method (Preorder/PayPal/Venmo)
10. User Places Order
11. Order Created (Status: pending)
12. Membership Assigned (YAMer/Patron/MEGAvoter)
13. Financial Calculations Done (7% rebate, 3% cashback)
14. MLM Database Updated
15. Payment Processed:
    - Preorder → Order stays pending, can pay later
    - PayPal/Venmo → Payment processed, order completed
16. Order Appears in "My Orders"
17. If Unpaid → Appears in "Unpaid Backorders"
```

### 9.2 Seller - Product Delivery

```
1. Seller Receives Product with QR Code
2. Seller Scans QR Code
3. Seller Logs In (if not logged in)
4. Seller Selects "Seller (3%)"
5. System Generates Transaction Code
6. System Saves Seller Scan (Status: pending)
7. Seller Shares Transaction Code with Buyer
8. Seller Waits for Buyer to Scan
9. Buyer Scans & Enters Transaction Code
10. System Matches Transaction Code
11. Seller's Status Changes: pending → confirmed
12. Seller Earns 3% Reward ($0.309)
13. XP Added to Seller's Wallet
```

### 9.3 Buyer - Product Receipt

```
1. Buyer Receives Product with QR Code
2. Buyer Scans QR Code
3. Buyer Logs In (if not logged in)
4. Buyer Selects "Buyer (7%)"
5. System Shows Transaction Code Input
6. Buyer Enters Transaction Code (from Seller)
7. System Verifies Transaction Code
8. System Matches with Seller's Scan
9. System Saves Buyer Scan (Status: confirmed)
10. System Updates Seller's Status: pending → confirmed
11. Buyer Earns 7% Reward ($0.721)
12. XP Added to Buyer's Wallet
13. System Checks for Pending Orders
14. If Pending Orders Exist → Redirects to Payment Page
15. If No Pending Orders → Redirects to Orders Page
```

---

## 10. Key System Integrations

### 10.1 Twilio Integration
- **Purpose:** OTP verification for phone numbers
- **Flow:** Phone → OTP Sent → OTP Verified → Login
- **Location:** `wp-content/plugins/cpm-twilio/`

### 10.2 WooCommerce Integration
- **Purpose:** E-commerce functionality
- **Hooks Used:** 9+ WooCommerce hooks
- **Location:** `wp-content/plugins/cpm-dongtrader/inc/cpm-woocommerce-functions.php`

### 10.3 Paid Memberships Pro (PMPro)
- **Purpose:** Membership level management
- **Function:** `pmpro_changeMembershipLevel()`
- **Integration:** Automatic membership assignment on order

### 10.4 Payment Gateways
- **Preorder:** Custom gateway for deferred payment
- **PayPal:** Standard WooCommerce PayPal integration
- **Venmo:** momo-venmo plugin integration

---

## 11. Data Flow Summary

### 11.1 Order Data Flow
```
Checkout Form
    ↓
Order Created (wp_posts)
    ↓
Order Meta Saved (wp_postmeta):
├─ _membership_type
├─ _membership_name
├─ mega_cashback_v (7%)
├─ mega_cashback_d (3%)
├─ mega_treasury
└─ mega_affid
    ↓
MLM Tables Updated:
├─ wp_mega_mlm_customers
└─ wp_mega_mlm_purchases
    ↓
User Meta Updated (if applicable):
├─ _buyer_details (currently disabled)
└─ _treasury_details
```

### 11.2 Scan Data Flow
```
QR Code Scanned
    ↓
Scan Data Saved to User Meta:
├─ seller_scan (array)
├─ buyer_scan (array)
└─ personal_scan (array)
    ↓
Transaction Matching (for buyer scans)
    ↓
Status Updates:
├─ Seller: pending → confirmed
└─ Buyer: confirmed (immediately)
    ↓
XP Calculated & Added
    ↓
Wallet Updated
```

---

## 12. Status Transitions

### 12.1 Order Status Flow
```
pending (Order Created)
    ↓
├─ Preorder (Unpaid) → Stays "pending"
├─ Preorder (Paid) → "completed"
├─ PayPal/Venmo → "processing" → "completed"
└─ On-hold → Corrected to "pending" (if preorder unpaid)
```

### 12.2 Scan Status Flow
```
Seller Scan:
pending (Waiting for buyer) → confirmed (Buyer matched)

Buyer Scan:
confirmed (Immediately after matching)

Personal Scan:
confirmed (Always confirmed)
```

---

## Conclusion

This document covers all major flows in the SmallStreet/Dongtrader system. The system integrates:
- **E-commerce** (WooCommerce)
- **Authentication** (Twilio OTP)
- **Proof of Delivery** (2-Scan System)
- **Rewards** (XP/YAM Tokens)
- **Memberships** (PMPro)
- **MLM Tracking** (Custom Database Tables)
- **Payment Processing** (Multiple Gateways)

All flows are interconnected and work together to create a complete marketplace with verification, rewards, and membership management.

---

*Last Updated: Based on comprehensive codebase analysis*
*Documentation Date: Current*

