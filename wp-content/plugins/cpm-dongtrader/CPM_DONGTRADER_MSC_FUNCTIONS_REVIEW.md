# CPM Dongtrader MSC Functions - Complete Functionality Review

This document provides a comprehensive list of all functionality in `cpm-dongtrader-msc-functions.php`.

---

## 📋 TABLE OF CONTENTS

1. [Cron Job System](#1-cron-job-system)
2. [Glassfrog API Integration](#2-glassfrog-api-integration)
3. [Group Management System](#3-group-management-system)
4. [Leadership Rotation System](#4-leadership-rotation-system)
5. [Order Allocation & Commission Distribution](#5-order-allocation--commission-distribution)
6. [My Account Tabs System](#6-my-account-tabs-system)
7. [XP Transfer System](#7-xp-transfer-system)
8. [Utility Functions](#8-utility-functions)
9. [Pagination System](#9-pagination-system)

---

## 1. CRON JOB SYSTEM

### 1.1 Custom Cron Interval
**Function:** `dongtrader_one_minutes_interval($schedules)`
- **Purpose:** Adds custom 5-minute interval to WordPress cron schedules
- **Interval:** Every 5 minutes
- **Hook:** `cron_schedules` filter

### 1.2 Cron Job Scheduler
**Function:** `dongtrader_schedule_cron_job()`
- **Purpose:** Schedules the main cron job if not already scheduled
- **Hook:** `wp` action
- **Cron Hook:** `dongtrader_cron_job_hook`
- **Frequency:** Every 5 minutes

### 1.3 Main Cron Job Handler
**Function:** `dongtrader_cron_job()`
- **Purpose:** Main cron job that runs every 5 minutes
- **Executes:**
  1. `mega_rotate_leadership()` - Rotates group leaderships
  2. `glassfrog_api_get_persons_of_circles()` - Syncs with Glassfrog API
  3. `mega_save_price_allocation_to_group_members()` - Allocates orders and commissions

---

## 2. GLASSFROG API INTEGRATION

### 2.1 Get Persons of Circles
**Function:** `glassfrog_api_get_persons_of_circles()`
- **Purpose:** Communicates and syncs with Glassfrog API to get circle members
- **Process:**
  1. Gets users with `user_status = 0` from `wp_mega_mlm_customers` table (limit 5)
  2. Extracts Glassfrog person IDs and user IDs
  3. Calls Glassfrog API: `people/{gfid}/roles`
  4. Checks if circle has exactly 5 members
  5. Updates user status to 1 and assigns `circle_id`
  6. Collects circle data and calls `mega_insert_group_details_to_db()`

**Database Tables Used:**
- `wp_mega_mlm_customers` - User data with Glassfrog IDs
- `wp_mega_mlm_groups` - Group/circle data

**API Function Used:**
- `glassfrog_api_request()` - Makes API calls (defined elsewhere)

### 2.2 Insert Group Details to Database
**Function:** `mega_insert_group_details_to_db($members)`
- **Purpose:** Creates group records in database when circle has 5 members
- **Process:**
  1. Validates members array (must have 5 members)
  2. Sets group leader as first user in array
  3. Creates group with:
     - `circle_id` - From Glassfrog
     - `circle_name` - From Glassfrog API
     - `created_date` - Current date
     - `group_leader` - First user ID
     - `leader_since` - Current date
     - `leadership_expires` - 1 month from now
     - `distribution_status` - 0 (pending)
  4. Updates all 5 users with `customer_group_id`
  5. Prevents duplicate groups (checks if `circle_id` exists)

**Database Tables:**
- `wp_mega_mlm_groups` - Group details
- `wp_mega_mlm_customers` - User group assignments

---

## 3. GROUP MANAGEMENT SYSTEM

### 3.1 Rotate Leadership
**Function:** `mega_rotate_leadership()`
- **Purpose:** Automatically rotates group leadership when expiration date is reached
- **Process:**
  1. Finds groups where `leadership_expires <= current_time`
  2. Gets all members of each group
  3. Finds current leader's position in member array
  4. Sets next member as new leader (or first member if last)
  5. Updates:
     - `group_leader` - New leader ID
     - `leader_since` - Current date
     - `leadership_expires` - 1 month from now

**Rotation Logic:**
- If current leader is not last in array → next member becomes leader
- If current leader is last in array → first member becomes leader

**Database Tables:**
- `wp_mega_mlm_groups` - Group leadership data
- `wp_mega_mlm_customers` - Group members

---

## 4. LEADERSHIP ROTATION SYSTEM

**Status:** ✅ Fully implemented
**Frequency:** Runs every 5 minutes via cron job
**Duration:** Leadership expires after 1 month

---

## 5. ORDER ALLOCATION & COMMISSION DISTRIBUTION

### 5.1 Save Price Allocation to Group Members
**Function:** `mega_save_price_allocation_to_group_members()`
- **Purpose:** Main function that processes orders and distributes commissions/allocations
- **Process:**
  1. Gets groups with `distribution_status = 0` or `2` (pending or needs update)
  2. For each group:
     - Gets all group members
     - Gets all unallocated orders (`allocation_status = 0`) for each member
     - Finds sponsor/upline for each member
     - Gets sponsor's group members
     - Compares orders in database vs WooCommerce orders
     - Builds data structure with orders, users, upline info
  3. Calls multiple save functions:
     - `mega_save_commission_details()` - Saves commissions
     - `mega_save_group_details()` - Saves group profit details
     - `mega_update_allocation_status()` - Marks orders as allocated
     - `mega_update_group_distribution_status()` - Updates group status

**Database Tables:**
- `wp_mega_mlm_groups` - Group data
- `wp_mega_mlm_customers` - User data
- `wp_mega_mlm_purchases` - Order tracking

### 5.2 Get Orders by User
**Function:** `dongtrader_get_orders_by_user($user_id, $group_table_orders)`
- **Purpose:** Finds difference between WooCommerce orders and orders in custom table
- **Returns:** Array of order IDs that exist in WooCommerce but not in custom table
- **Used for:** Detecting new orders that need allocation

### 5.3 Save Order Details
**Function:** `mega_save_order_details($user_orders)`
- **Purpose:** Saves buyer order details to user meta
- **Stores in:** `_buyer_details` usermeta
- **Data Saved:**
  - Order ID
  - Buyer name
  - Membership name
  - Rebate amount
  - Total
  - XP awarded (hardcoded: 10,000,000)
- **Also Updates:** Sponsor's `_buyer_details` with sponsor rebate

**Note:** Currently commented out in main function (line 455)

### 5.4 Save Commission Details
**Function:** `mega_save_commission_details($order_users, $group, $group_leader)`
- **Purpose:** Saves commission details to affiliates and group leaders
- **Stores in:** `_commission_details` usermeta
- **Commission Types:**
  - `affiliate_com` - Affiliate commission
  - `individual_com` - Individual member commission
  - `group_com` - Group commission
  - `site_com` - SmallStreet commission
- **Logic:**
  - If sponsor ≠ group leader: Sponsor gets individual + affiliate + site commission
  - If group leader ≠ sponsor: Group leader gets group + site commission
  - If sponsor = group leader: Gets all commissions combined
- **Data Saved:**
  - Order ID
  - Customer name
  - Product title
  - All commission amounts
  - Total commission
  - XP awarded (hardcoded: 10,000,000)

### 5.5 Save Treasury Details
**Function:** `mega_save_treasury_details($orders_members, $allmems)`
- **Purpose:** Saves treasury/reward details to user meta
- **Stores in:** `_treasury_details` usermeta
- **Reward Distribution:**
  - 50% to seller (`member_reward_50_i`)
  - 40% to group (`member_reward_40_g`)
  - 10% to SmallStreet (`member_reward_10_c`)
- **Data Saved:**
  - Order ID
  - Customer name
  - Product title
  - Reward amounts
  - XP awarded (hardcoded: 10,000,000)

**Note:** Currently commented out in main function (line 461)

### 5.6 Save My Treasury
**Function:** `mega_save_my_treasury($orders_members)`
- **Purpose:** Alternative treasury saving function
- **Stores in:** `_treasury_details` and `_income_details` usermeta
- **Reward Distribution:**
  - 50% to seller
  - 40% to group
  - 10% to SmallStreet
- **Also Updates:** Sponsor's treasury and income details

### 5.7 Save Group Details
**Function:** `mega_save_group_details($order_members, $group_leader, $group_id)`
- **Purpose:** Saves group profit details to group leader and sponsor
- **Stores in:** `_group_details` usermeta
- **Data Saved:**
  - Order ID
  - Order code
  - Order date
  - Circle/group name
  - Profit amount (individual or group profit)
- **Logic:**
  - If sponsor ≠ group leader: Both get separate entries
  - If sponsor = group leader: Gets combined profit

### 5.8 Update Allocation Status
**Function:** `mega_update_allocation_status($orders_user)`
- **Purpose:** Marks orders as allocated in `wp_mega_mlm_purchases` table
- **Updates:** `allocation_status = 1` for processed orders

### 5.9 Update Group Distribution Status
**Function:** `mega_update_group_distribution_status($group_id)`
- **Purpose:** Updates group distribution status when all orders are allocated
- **Process:**
  1. Checks if any unallocated orders exist for group members
  2. If no unallocated orders → sets `distribution_status = 1` (completed)
- **Database Table:** `wp_mega_mlm_groups`

---

## 6. MY ACCOUNT TABS SYSTEM

### 6.1 Add Custom Tabs to My Account
**Function:** `add_custom_tab_to_my_account()`
- **Purpose:** Adds custom endpoints/tabs to WooCommerce My Account page
- **Hook:** `wp_loaded` action

**Tabs Added (in order):**
1. **My Orders** (`detente-orders`) - Position 1
2. **Wallet** (`detente-wallet`) - Position 2
3. **XP Transfers** (`xp-transfers`) - Position 3
4. **My Treasury** (`detente-treasury`) - Position 4
5. **Group** (`detente-group`) - Position 5
6. **Seller Income** (`detente-commission`) - Position 6
7. **POC Pooling** (`poc-pooling`) - Position 7

**Features:**
- Adds rewrite endpoints
- Adds query vars
- Adds menu items to WooCommerce account menu
- Loads template files from `template-parts/content-{slug}.php`
- Handles currency settings (VND rate if enabled)
- Flushes rewrite rules when endpoints change
- Adds XP Transfers JavaScript to footer

**Template Loading:**
- Looks for: `CPM_DONGTRADER_PLUGIN_DIR/template-parts/content-{slug}.php`
- Passes currency rate array to template

---

## 7. XP TRANSFER SYSTEM

### 7.1 Search XP Receiver (AJAX)
**Function:** `dongtrader_search_xp_receiver()`
- **Purpose:** AJAX handler to search for users to receive XP transfers
- **Hook:** `wp_ajax_search_xp_receiver`
- **Security:** Nonce verification (`search_receiver`)
- **Search Criteria:**
  - Email address
  - Username
  - Display name
  - FonePay ID (`mega-paypal` usermeta)
- **Returns:** JSON array of users with:
  - ID
  - Name
  - Email
  - Username
  - FonePay ID
- **Limits:**
  - Minimum 2 characters to search
  - Maximum 10 results
  - Excludes current user

### 7.2 Send XP Transfer (AJAX)
**Function:** `dongtrader_send_xp_transfer()`
- **Purpose:** AJAX handler to process XP transfers between users
- **Hook:** `wp_ajax_send_xp_transfer`
- **Security:** Nonce verification (`send_xp_transfer`)
- **Validation:**
  - Receiver must be selected
  - Cannot send to self
  - Amount must be > 0
  - Minimum: 0.000001 XP (1 YAM)
  - Maximum: 50% of available balance
  - Balance sufficiency check
- **Process:**
  1. Calculates sender's available XP (Total - Sent + Received)
  2. Validates transfer amount
  3. Inserts transaction into `wp_xp_transactions` table
  4. Calculates YAM equivalent (XP × 1,000,000)
  5. Stores memo if provided
- **Database Table:** `wp_xp_transactions`
  - Fields: `sender_id`, `receiver_id`, `xp_amount`, `yam_equivalent`, `memo`, `transaction_date`
- **Note:** Does NOT store transfers in `personal_scan` usermeta (prevents double-counting)

### 7.3 XP Transfers JavaScript
**Function:** `dongtrader_xp_transfers_script()`
- **Purpose:** Adds JavaScript for XP transfer functionality
- **Hook:** `wp_footer` action
- **Only loads on:** XP Transfers page (`xp-transfers` endpoint)
- **Features:**
  - Tab switching (Transactions / Send XP)
  - Receiver search with autocomplete
  - Real-time form validation
  - XP amount validation (min/max/balance)
  - YAM and USD conversion display
  - Transfer summary before confirmation
  - AJAX form submission
  - Transaction filtering (All/Sent/Received)
  - Pagination support

**JavaScript Functions:**
- `switchTab()` - Tab navigation
- `validateTransfer()` - Transfer validation
- `validateForm()` - Real-time form validation
- `clearReceiver()` - Clear selected receiver
- Transaction filter handlers
- Form submission handlers

---

## 8. UTILITY FUNCTIONS

### 8.1 Check User
**Function:** `dongtrader_check_user($uid, $check_only = true)`
- **Purpose:** Validates user exists or gets user display name
- **Parameters:**
  - `$uid` - User ID
  - `$check_only` - If true, returns boolean; if false, returns display name
- **Returns:**
  - Boolean (if `$check_only = true`)
  - Display name string (if `$check_only = false`)

### 8.2 Get Order Meta
**Function:** `dongtrader_get_order_meta($orderid, $key)`
- **Purpose:** Retrieves order meta value, returns 0 if empty
- **Parameters:**
  - `$orderid` - Order ID
  - `$key` - Meta key
- **Returns:** Meta value or 0 if empty

### 8.3 Get User Orders
**Function:** `get_user_orders($status)`
- **Purpose:** Gets all orders for current user by status
- **Parameters:**
  - `$status` - Order status (e.g., 'wc-completed')
- **Returns:** Array of WC_Order objects

### 8.4 Is Last Day of Month
**Function:** `isLastDayOfMonth()`
- **Purpose:** Checks if today is the last day of the month
- **Returns:** Boolean
- **Use Case:** Possibly for monthly snapshots or reports

### 8.5 Delete Data (Utility)
**Function:** `delete_data()`
- **Purpose:** Utility function to delete user meta for specific users
- **Users:** Hardcoded array [293, 294, 295]
- **Deletes:**
  - `_buyer_details`
  - `_commission_details`
  - `_treasury_details`
  - `_group_details`
- **Note:** Appears to be a cleanup/debug function

---

## 9. PAGINATION SYSTEM

### 9.1 Pagination Array
**Function:** `dongtrader_pagination_array($details, $items_per_page = 10, $items_array = false)`
- **Purpose:** Manages pagination for tables in My Account pages
- **Parameters:**
  - `$details` - Array of items to paginate
  - `$items_per_page` - Items per page (default: 10)
  - `$items_array` - If true, returns paginated array; if false, returns params
- **Features:**
  - Date range filtering (`filter=within-a-date-range`)
  - "All" filter option
  - Calculates start/end indices
  - Returns paginated array or filter parameters
- **URL Parameters:**
  - `listpaged` - Current page number
  - `filter` - Filter type (all/within-a-date-range)
  - `start-month` - Start date
  - `end-month` - End date

**Returns:**
- If `$items_array = true`: Paginated array slice
- If `$items_array = false`: Array with:
  - `startdate`
  - `enddate`
  - `date_selected`
  - `all_selected`

---

## 📊 DATABASE TABLES USED

1. **`wp_mega_mlm_customers`**
   - User data with Glassfrog IDs
   - Group assignments
   - Upline/sponsor relationships

2. **`wp_mega_mlm_groups`**
   - Group/circle details
   - Leadership information
   - Distribution status

3. **`wp_mega_mlm_purchases`**
   - Order tracking
   - Allocation status

4. **`wp_xp_transactions`**
   - XP transfer records
   - Transaction history

5. **`wp_users`**
   - User validation
   - User data retrieval

6. **`wp_usermeta`**
   - User meta storage:
     - `_buyer_details`
     - `_commission_details`
     - `_treasury_details`
     - `_group_details`
     - `_income_details`
     - `seller_scan`
     - `buyer_scan`
     - `personal_scan`
     - `mega-paypal` (FonePay ID)
     - `mega-glassfrog` (POC)

---

## 🔄 CRON JOB FLOW

```
Every 5 Minutes:
├── mega_rotate_leadership()
│   └── Rotates expired group leaderships
│
├── glassfrog_api_get_persons_of_circles()
│   ├── Gets users with status = 0 (limit 5)
│   ├── Calls Glassfrog API
│   ├── Checks for 5-member circles
│   └── Creates groups via mega_insert_group_details_to_db()
│
└── mega_save_price_allocation_to_group_members()
    ├── Gets groups with distribution_status = 0 or 2
    ├── Processes orders for each group
    ├── mega_save_commission_details()
    ├── mega_save_group_details()
    ├── mega_update_allocation_status()
    └── mega_update_group_distribution_status()
```

---

## 🎯 KEY FEATURES SUMMARY

### ✅ Implemented Features

1. **Automated Group Management**
   - Glassfrog API sync
   - 5-member circle detection
   - Group creation
   - Leadership rotation

2. **Commission Distribution**
   - Affiliate commissions
   - Group commissions
   - Individual commissions
   - Site commissions

3. **Order Processing**
   - Order allocation
   - Treasury distribution
   - Group profit sharing

4. **XP Transfer System**
   - User search
   - Transfer validation
   - Transaction recording
   - Balance calculation

5. **My Account Integration**
   - 7 custom tabs
   - Template loading
   - Currency handling

6. **Pagination & Filtering**
   - Date range filters
   - Pagination support
   - Transaction filtering

---

## 📝 NOTES

1. **XP Awarded Values:**
   - Many functions hardcode `xp_awarded` values (10,000,000 or 100,000,000)
   - These may need to be calculated dynamically

2. **Commented Out Functions:**
   - `mega_save_order_details()` - Line 455 (commented)
   - `mega_save_treasury_details()` - Line 461 (commented)

3. **Currency Support:**
   - VND (Vietnamese Dong) rate support
   - Currency symbol switching

4. **Performance:**
   - Cron job processes 5 users at a time (Glassfrog API)
   - Pagination limits data processing

5. **Security:**
   - Nonce verification for AJAX handlers
   - User authentication checks
   - SQL prepared statements

---

**Last Updated:** Based on code review of `cpm-dongtrader-msc-functions.php`
**Total Functions:** 25+
**Total Lines:** 2,218


