# Order Placement Flow - Product Purchase

## Complete Flow: Customer Buys a Product

### STEP 1: Customer Adds Product to Cart
- Customer browses products
- Clicks "Add to Cart" or uses direct checkout link
- Product added to WooCommerce cart

### STEP 2: Customer Goes to Checkout Page
- Customer clicks checkout
- URL: `/checkout` or redirect with parameters
- WooCommerce checkout form loads

**Checkout Page Actions:**
- `woocommerce_before_checkout_form` hook fires
- `dongtraders_check_user_bought_product_already_bought()` checks if user already bought
- Custom fields added:
  - Mobile number field (`mega-mobile`)
  - Social impact choice field

### STEP 3: Customer Fills Checkout Form
- Billing details
- Shipping details (if needed)
- Mobile number (custom field)
- Payment method selection

**Validation:**
- `woocommerce_after_checkout_validation` hook fires
- `mega_validate_checkout()` validates custom fields
- Phone number duplicate check (`check_duplicate_phone_number`)

### STEP 4: Customer Submits Order
- Customer clicks "Place Order"
- **Order is created with status: `pending`** (waiting for payment)
- Payment method: `preorder` (Preorder Gateway)
- WooCommerce processes payment

**Order Creation Hooks (in order):**

#### Hook 1: `woocommerce_checkout_order_created` (Priority 7)
**Function:** `mega_set_membership_level($order)`
- Determines membership type based on products:
  - **YAMer** - If order contains YAMer products (free)
  - **Patron** - If order contains Patron products (Level 18)
  - **Pioneer/MEGAvoter** - If order contains MEGA products (Level 17)
- Assigns membership level to user via `pmpro_changeMembershipLevel()`
- Saves membership type to order meta:
  - `_membership_type` - Membership level ID
  - `_membership_name` - Membership name (YAMer/Patron/Pioneer)

#### Hook 2: `woocommerce_checkout_order_created` (Priority 8)
**Function:** `mega_custom_ordermeta_update($orderobj)`
- Calculates order distribution amounts:
  - `mega_cashback_v` - 7% buyer rebate
  - `mega_cashback_d` - 3% seller/distributor cashback
  - `mega_reserve` - Reserve amount
  - `mega_treasury` - Treasury amount (remaining after cashback)
  - Various profit distribution amounts
- Saves all calculated amounts to order meta
- Uses membership level settings to calculate percentages

#### Hook 3: `woocommerce_checkout_order_created` (Priority 9)
**Function:** `mega_update_mlm_database($orderObj)`
- Updates MLM (Multi-Level Marketing) database:
  - Inserts/updates `mega_mlm_customers` table
  - Inserts into `mega_mlm_purchases` table
  - Links sponsor/affiliate if exists (`mega_affid`)
- If Patron membership:
  - Calls `mega_save_order_details()` - Saves order to user
  - Calls `mega_save_my_treasury()` - Saves to treasury

### STEP 5: Payment Processing (Preorder Gateway)
- **Initial Order Status:** `pending` (order created, waiting for payment)
- Preorder Gateway processes payment
- **After Payment:** Order status changes to `processing`
  - Code: `$order->update_status('processing', __('Payment received via Preorder .', 'woocommerce'));`
- Order status progression:
  - **Pending** - Order created, payment not yet received
  - **Processing** - Payment received via Preorder Gateway
  - **On-hold** - Payment on hold (if needed)
  - **Completed** - Order fulfilled (manual or automatic)

### STEP 6: Order Status = Completed
**Hook:** `woocommerce_order_status_completed`

#### Function 1: `dong_auto_set_user_role_on_checkout($order_id)`
- Sets user role based on product purchased
- Calls `dong_set_user_role($user_id, $product_id)`

#### Function 2: `sumValues_update_ordermeta($orderobj)` (Called manually)
- This function saves buyer details to user meta
- **Location:** Called from:
  - `cpm-dongtrader-functions.php` line 2099 (manual import)
  - `cpm-woocommerce-functions.php` line 1030 (manual import)

**What it does:**
- Gets customer ID from order
- Retrieves existing `_buyer_details` from user meta
- Creates new buyer detail entry:
  ```php
  [
      'order_id' => $order_id,
      'name' => $buyer_name,
      'membership' => $membership_name,
      'rebate' => $rebate,           // 7% buyer rebate
      'rebate_d' => $process_amt,    // 3% seller rebate
      'total' => $distributed_total_amt,
      'xp_awarded' => $xp_amount     // XP earned (0 for YAMer, 10000000 for paid)
  ]
  ```
- Updates user meta `_buyer_details` with new entry
- Also updates `_treasury_details` user meta
- If sponsor exists, updates sponsor's `_buyer_details` too

### STEP 7: Order Appears in User Account

**Display Locations:**

1. **My Orders Page** (`content-detente-orders.php`)
   - Shows all orders from `_buyer_details` user meta
   - Displays: Order ID, Date, Membership, 7% Buyer, 3% Seller, Total
   - Paginated (10 per page)

2. **Unpaid Backorders Section**
   - Shows orders with status: `on-hold`, `processing`, `pending`
   - Displays: Order, Date, Total, 7% (unfunded), Actions
   - "Pay Now" button (only on last day of month if `can_pay` is true)

3. **Paid Orders Section**
   - Shows orders with status: `completed`
   - Displays: Order, Date, Total, 7% (funded), XP, Status, Actions
   - Shows XP summary:
     - Total XP Earned
     - XP Status (Pending/Completed/Released)
     - YAM Tokens conversion
     - USD Value conversion
     - Patronage Split (7% buyer, 3% seller)

## Key Data Storage

### Order Meta (stored in `wp_postmeta`):
- `_membership_type` - Membership level ID
- `_membership_name` - Membership name
- `mega_cashback_v` - 7% buyer rebate
- `mega_cashback_d` - 3% seller cashback
- `mega_affid` - Sponsor/affiliate user ID
- `mega_treasury` - Treasury amount
- Various other distribution amounts

### User Meta (stored in `wp_usermeta`):
- `_buyer_details` - Array of all buyer order details
- `_treasury_details` - Array of treasury entries
- `_seller_details` - Seller scan details
- `_discord_invite` - Discord verification data

### Database Tables:
- `mega_mlm_customers` - MLM customer records
- `mega_mlm_purchases` - MLM purchase records
- `xp_transactions` - XP transfer transactions

## Important Notes

1. **XP Awarded:**
   - YAMer (free): 0 XP
   - Paid memberships (MEGAvoter/Patron): 10,000,000 XP (default)

2. **Payment Timing:**
   - Orders can be created before payment
   - Buyer details saved when order status = `completed`
   - Unpaid orders show in "Unpaid Backorders" section

3. **Membership Assignment:**
   - Happens immediately on order creation
   - Based on products in order
   - Uses Paid Memberships Pro levels

4. **Rebate Calculation:**
   - 7% buyer rebate (`mega_cashback_v`)
   - 3% seller/distributor (`mega_cashback_d`)
   - Calculated from membership level settings

5. **Sponsor/Affiliate:**
   - Stored in order meta as `mega_affid`
   - Sponsor also gets entry in their `_buyer_details`
   - Used for MLM tracking

## Flow Diagram

```
Customer Adds Product to Cart
         ↓
Customer Goes to Checkout
         ↓
Customer Fills Form & Submits
         ↓
Order Created (woocommerce_checkout_order_created)
         ↓
├─ Hook 7: Set Membership Level
├─ Hook 8: Calculate Order Meta (rebates, treasury)
└─ Hook 9: Update MLM Database
         ↓
Payment Processed
         ↓
Order Status = Completed
         ↓
Save Buyer Details to User Meta
         ↓
Order Appears in User Account
```

