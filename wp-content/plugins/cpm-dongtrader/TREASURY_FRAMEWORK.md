# Treasury Framework Documentation

## Overview

The Treasury Framework is a comprehensive system for tracking Proof of Delivery (PoD) transactions, XP (Experience Points) minting, YAM token calculations, and trade value distribution across three user roles: Seller, Buyer, and Personal.

## System Architecture

### 1. Data Storage

#### 1.1 WordPress Usermeta Tables (`wp_usermeta`)

The system stores scan data in three separate usermeta keys per user:

- **`seller_scan`**: Stores seller scan transactions
- **`buyer_scan`**: Stores buyer scan transactions  
- **`personal_scan`**: Stores personal scan transactions

**Table Structure:**
```
- umeta_id (Primary Key, Auto Increment)
- user_id (Indexed, BigInt)
- meta_key (Indexed, Varchar 255)
- meta_value (LongText - stores serialized array data)
```

**Data Format:**
Each scan entry is stored as an array with the following fields:
```php
array(
    'delivery_proof' => 'yes',
    'discord_join' => false,
    'mega-mobile' => 'phone_number',
    'percentage' => 3|7|10,  // 3% for seller, 7% for buyer, 10% for personal
    'proof_id' => 'unique_proof_id',  // Format: product_id_timestamp
    'role' => 'seller|buyer|personal',
    'scan_status' => 'pending|confirmed',
    'scan_type' => 'proof|checkout',
    'status' => 'pending',
    'timestamp' => 'ISO_8601_timestamp',
    'trade_value' => 10.30,  // Base trade value in USD
    'trade_value_usd' => 0,  // Calculated trade value
    'treasury_distributed' => 0.309,  // Percentage of trade value
    'treasury_reminder' => 0,
    'user_id' => 813,
    'xp_reminder' => 0,
    'xp_units' => 0.006489,  // XP earned
    'yam_reminder' => 0,
    'yam_value' => 6489,  // YAM equivalent
    'buyer_id' => 813,  // For buyer entries
    'seller_id' => 812   // For buyer entries (if matched)
)
```

#### 1.2 WordPress Options Table (`wp_options`)

The system maintains a centralized treasury reminder data in the `wp_options` table:

- **Option Name**: `treasury_reminder`
- **Option Value**: Serialized array of all treasury entries across all users

**Purpose:**
- Centralized tracking of all transactions
- Cross-user matching (seller-buyer pairs)
- Treasury distribution calculations
- Leaderboard rankings

**Data Structure:**
```php
array(
    array(
        // Entry 1: Seller entry
        'user_id' => 812,
        'proof_id' => '5665_1762405744',
        'role' => 'seller',
        'scan_status' => 'pending|confirmed',
        // ... all other fields
    ),
    array(
        // Entry 2: Buyer entry
        'user_id' => 813,
        'proof_id' => '5665_1762405744',
        'role' => 'buyer',
        'scan_status' => 'confirmed',
        'buyer_id' => 813,
        'seller_id' => 812,
        // ... all other fields
    ),
    // ... more entries
)
```

## 2. Transaction Flow

### 2.1 Seller Scan Flow

1. **QR Code Generation**
   - Unique `proof_id` created: `{product_id}_{timestamp}`
   - QR code contains URL with `proof_id` and `scan_type=proof`

2. **Seller Scans QR Code**
   - User enters phone number
   - OTP verification via Twilio
   - Duplicate check: Verifies if `proof_id` already exists in current user's `seller_scan` usermeta
   - If duplicate: Shows error popup "Product qr is already scanned", prevents data insertion

3. **Data Insertion (if not duplicate)**
   - Creates entry in `seller_scan` usermeta
   - Creates entry in `treasury_reminder` option
   - Initial status: `scan_status = 'pending'`
   - Percentage: 3% of trade value

4. **Data Fields Saved:**
   ```php
   'role' => 'seller',
   'percentage' => 3,
   'scan_status' => 'pending',
   'xp_units' => calculated_xp,
   'yam_value' => calculated_yam,
   'trade_value' => 10.30
   ```

### 2.2 Buyer Scan Flow

1. **Buyer Scans Same QR Code**
   - Uses same `proof_id` as seller scan
   - OTP verification
   - Duplicate check: Verifies if `proof_id` exists in current user's `buyer_scan` usermeta AND `scan_status = 'confirmed'`
   - If duplicate: Shows error popup, prevents data insertion

2. **Matching Logic**
   - System searches all `seller_scan` entries across all users
   - Matches by `proof_id`
   - If match found:
     - Updates seller's `seller_scan` entry: `scan_status = 'confirmed'`, adds `buyer_id`
     - Updates seller's entry in `treasury_reminder`: `scan_status = 'confirmed'`, adds `buyer_id`

3. **Buyer Data Insertion**
   - Creates entry in `buyer_scan` usermeta
   - Creates entry in `treasury_reminder` option
   - Status: `scan_status = 'confirmed'` (if seller matched)
   - Percentage: 7% of trade value
   - Includes `buyer_id` (own user_id) and `seller_id` (matched seller's user_id)

4. **Data Fields Saved:**
   ```php
   'role' => 'buyer',
   'percentage' => 7,
   'scan_status' => 'confirmed',
   'buyer_id' => current_user_id,
   'seller_id' => matched_seller_id,
   'xp_units' => calculated_xp,
   'yam_value' => calculated_yam
   ```

### 2.3 Personal Scan Flow

1. **Personal User Scans QR Code**
   - OTP verification
   - Duplicate check: Verifies if `proof_id` exists in current user's `personal_scan` usermeta
   - If duplicate: Shows error popup, prevents data insertion

2. **Data Insertion**
   - Creates entry in `personal_scan` usermeta
   - Creates entry in `treasury_reminder` option
   - Status: `scan_status = 'confirmed'` (always confirmed for personal)
   - Percentage: 10% of trade value

3. **Data Fields Saved:**
   ```php
   'role' => 'personal',
   'percentage' => 10,
   'scan_status' => 'confirmed',
   'xp_units' => calculated_xp,
   'yam_value' => calculated_yam
   ```

## 3. Calculation Formulas

### 3.1 Trade Value Calculation

**Base Trade Value:** $10.30 USD per transaction

**Trade Value Distribution:**
- Seller: 3% of $10.30 = $0.309
- Buyer: 7% of $10.30 = $0.721
- Personal: 10% of $10.30 = $1.03

### 3.2 YAM Token Calculation

**Conversion Rate:** 21,000 YAM = $1 USD

**Formula:**
```
YAM Value = (Trade Value USD × Percentage) × 21,000
```

**Examples:**
- Seller: ($0.309) × 21,000 = 6,489 YAM
- Buyer: ($0.721) × 21,000 = 15,141 YAM
- Personal: ($1.03) × 21,000 = 21,630 YAM

### 3.3 XP (Experience Points) Calculation

**Conversion Rate:** 1 YAM = 0.000001 XP (1 million YAM = 1 XP)

**Formula:**
```
XP Units = YAM Value × 0.000001
```

**Examples:**
- Seller: 6,489 YAM × 0.000001 = 0.006489 XP
- Buyer: 15,141 YAM × 0.000001 = 0.015141 XP
- Personal: 21,630 YAM × 0.000001 = 0.02163 XP

### 3.4 Reverse Calculations

**From YAM to USD:**
```
USD = YAM / 21,000
```

**From XP to YAM:**
```
YAM = XP / 0.000001
```

**From XP to USD:**
```
USD = (XP / 0.000001) / 21,000
```

## 4. Status Management

### 4.1 Scan Status Values

- **`pending`**: Initial status for seller scans, waiting for buyer confirmation
- **`confirmed`**: Status after buyer scan matches seller, or for personal scans

### 4.2 Status Transitions

**Seller:**
- Initial: `pending`
- After buyer match: `confirmed`

**Buyer:**
- If seller matched: `confirmed`
- If no seller match: `pending` (rare case)

**Personal:**
- Always: `confirmed`

## 5. Duplicate Prevention

### 5.1 Seller Scan Duplicate Check

**Location:** `ct_validate_twilio_otp()` function

**Logic:**
```php
// Check current user's seller_scan usermeta
$seller_scan_data = get_user_meta($user_id, 'seller_scan', true);
foreach ($seller_scan_data as $entry) {
    if (isset($entry['proof_id']) && $entry['proof_id'] == $proof_id) {
        // Duplicate found - show error, prevent insertion
    }
}
```

**Response:** Returns success with "warning" flag, shows error popup, allows login but prevents data insertion

### 5.2 Buyer Scan Duplicate Check

**Logic:**
```php
// Check current user's buyer_scan usermeta
// Must have scan_status = 'confirmed'
$buyer_scan_data = get_user_meta($user_id, 'buyer_scan', true);
foreach ($buyer_scan_data as $entry) {
    if (isset($entry['proof_id']) && $entry['proof_id'] == $proof_id 
        && isset($entry['scan_status']) && $entry['scan_status'] == 'confirmed') {
        // Duplicate found
    }
}
```

### 5.3 Personal Scan Duplicate Check

**Logic:**
```php
// Check current user's personal_scan usermeta
$personal_scan_data = get_user_meta($user_id, 'personal_scan', true);
foreach ($personal_scan_data as $entry) {
    if (isset($entry['proof_id']) && $entry['proof_id'] == $proof_id) {
        // Duplicate found
    }
}
```

## 6. Treasury Reminder Update Logic

### 6.1 Duplicate Prevention in Treasury Reminder

**Location:** `ct_insert_scan_data()` function

**Logic:**
```php
// Check for existing entry by proof_id and role
foreach ($existing_treasury_data as $treasury_index => $treasury_entry) {
    if (isset($treasury_entry['proof_id']) && $treasury_entry['proof_id'] == $proof_id 
        && isset($treasury_entry['role']) && $treasury_entry['role'] == $role) {
        // Update existing entry instead of creating duplicate
        $existing_treasury_data[$treasury_index] = $new_entry;
        $entry_updated = true;
        break;
    }
}

// Only append if not updated
if (!$entry_updated) {
    $existing_treasury_data[] = $new_entry;
}
```

### 6.2 Seller-Buyer Matching in Treasury Reminder

**When Buyer Scans:**
1. Search `treasury_reminder` for seller entry with matching `proof_id` and `role='seller'`
2. Update seller entry: `scan_status = 'confirmed'`, add `buyer_id`
3. Create/update buyer entry with `seller_id` reference

## 7. Wallet Display System

### 7.1 Data Retrieval

**Location:** `content-detente-wallet.php` template

**Process:**
1. Retrieve data from three usermeta keys:
   ```php
   $seller_scan_data = get_user_meta($user_id, 'seller_scan', true);
   $buyer_scan_data = get_user_meta($user_id, 'buyer_scan', true);
   $personal_scan_data = get_user_meta($user_id, 'personal_scan', true);
   ```

2. Unserialize data:
   ```php
   $seller_scan_data = maybe_unserialize($seller_scan_data);
   ```

3. Combine all entries into single array:
   ```php
   $user_treasury_entries = array();
   // Merge seller, buyer, and personal scans
   ```

### 7.2 Calculations

**Total XP:**
```php
$total_xp = sum of all xp_units from confirmed scans
```

**Total YAM:**
```php
$total_yam = sum of all yam_value from confirmed scans
```

**Total Trade Value USD:**
```php
$total_trade_value_usd = sum of all trade_value_usd
// Or calculate from YAM: total_yam / 21000
```

**Confirmed Deliveries:**
```php
$confirmed_deliveries = count of entries where scan_status = 'confirmed'
```

### 7.3 Role Breakdown

**Buyer (7%):**
- Sum XP from all entries where `role` contains "buyer"
- Sum YAM from buyer entries
- Count buyer entries

**Seller (3%):**
- Sum XP from all entries where `role` contains "seller"
- Sum YAM from seller entries
- Count seller entries

**Personal (10%):**
- Sum XP from all entries where `role` contains "personal"
- Sum YAM from personal entries
- Count personal entries

### 7.4 Leaderboard Calculation

**Process:**
1. Query all usermeta entries for `seller_scan`, `buyer_scan`, `personal_scan`
2. For each user, sum XP from confirmed scans
3. Sort users by total XP (descending)
4. Calculate user's rank position

**Query:**
```sql
SELECT user_id, meta_key, meta_value 
FROM wp_usermeta 
WHERE meta_key IN ('seller_scan', 'buyer_scan', 'personal_scan')
```

## 8. Transaction History Table

### 8.1 Display Fields

1. **Date**: Formatted from `timestamp` or `date` field
2. **Proof ID**: Unique identifier (`product_id_timestamp`)
3. **Role**: Displayed as "Seller (3%)", "Buyer (7%)", or "Personal (10%)"
4. **Trade Value**: USD amount (from `trade_value_usd` or calculated from YAM)
5. **XP Minted**: XP units earned
6. **YAM**: YAM token equivalent
7. **Status**: Badge showing `pending` or `confirmed`

### 8.2 Sorting

- Sorted by timestamp (newest first)
- Handles both ISO format (`2025-11-06T05:11:03.506Z`) and MySQL format

## 9. Key Functions

### 9.1 OTP Validation
**Function:** `ct_validate_twilio_otp()`
- Validates OTP code
- Performs duplicate checks
- Returns nonce for login
- Returns warning flag if duplicate found

### 9.2 Scan Data Insertion
**Function:** `ct_insert_scan_data()`
- Inserts data into usermeta
- Updates treasury_reminder option
- Handles seller-buyer matching
- Prevents duplicates

### 9.3 QR Code Generation
**Function:** `ct_generate_qr_code()` (in `cpm-dongtrader-functions.php`)
- Creates unique `proof_id` with timestamp
- Generates QR code via QR Tiger API
- Embeds URL with `proof_id` and `scan_type`

## 10. Data Flow Diagram

```
QR Code Scan
    ↓
OTP Verification
    ↓
Duplicate Check (usermeta)
    ↓
[If Duplicate] → Error Popup → Login (no data insertion)
    ↓
[If Not Duplicate]
    ↓
Insert to Usermeta (seller_scan/buyer_scan/personal_scan)
    ↓
Update/Create Treasury Reminder Entry (wp_options)
    ↓
[If Buyer Scan] → Match Seller → Update Seller Entry
    ↓
Display Transaction in Wallet
```

## 11. Important Notes

### 11.1 LAUGH Mode
- Trade credits only until August 31, 2026
- No money moves — trade value accrues until August 31, 2030
- XP represents verified 2-scan Proofs of Delivery

### 11.2 PBTV Eligibility
- Top 30 XP wallets on August 11, 2026
- Receive PBTV NFT minting authority for Detente 2030

### 11.3 Proof ID Format
- Format: `{product_id}_{timestamp}`
- Example: `5665_1762405744`
- Ensures uniqueness for each QR code generation

### 11.4 Data Consistency
- Usermeta stores user-specific scan data
- Treasury reminder stores centralized transaction data
- Both are updated simultaneously to maintain consistency
- Duplicate prevention ensures data integrity

## 12. Error Handling

### 12.1 Duplicate Scan Error
- **Message**: "Product qr is already scanned"
- **Action**: Show error popup, allow login, prevent data insertion
- **Flag**: `window.skipScanDataInsertion = true`

### 12.2 Missing Data
- Default values used for missing fields
- Trade value defaults to $10.30
- Status defaults to 'pending'

## 13. Database Queries

### 13.1 Retrieve User Scan Data
```php
get_user_meta($user_id, 'seller_scan', true);
get_user_meta($user_id, 'buyer_scan', true);
get_user_meta($user_id, 'personal_scan', true);
```

### 13.2 Retrieve Treasury Reminder
```php
get_option('treasury_reminder', array());
```

### 13.3 Update Treasury Reminder
```php
update_option('treasury_reminder', $treasury_data);
```

### 13.4 Leaderboard Query
```sql
SELECT user_id, meta_key, meta_value 
FROM wp_usermeta 
WHERE meta_key IN ('seller_scan', 'buyer_scan', 'personal_scan')
```

## 14. File Locations

### 14.1 Core Files
- **OTP & Scan Logic**: `wp-content/plugins/cpm-twilio/twilio-main.php`
- **QR Generation**: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php`
- **Wallet Template**: `wp-content/plugins/cpm-dongtrader/template-parts/content-detente-wallet.php`
- **Frontend JS**: `wp-content/plugins/cpm-twilio/assets/js/cpm-twilio-script.js`

### 14.2 Database Tables
- **Usermeta**: `wp_usermeta` (WordPress core table)
- **Options**: `wp_options` (WordPress core table)

## 15. Future Enhancements

### 15.1 Potential Improvements
- Export transaction history to CSV
- Real-time XP balance updates
- Transaction filtering by role/date/status
- Detailed transaction view modal
- XP transfer between users (if needed)
- Treasury analytics dashboard

### 15.2 Performance Optimization
- Cache treasury reminder data
- Index optimization for usermeta queries
- Batch processing for leaderboard calculations

---

**Document Version:** 1.0  
**Last Updated:** 2025-01-06  
**Author:** Treasury Framework Documentation

