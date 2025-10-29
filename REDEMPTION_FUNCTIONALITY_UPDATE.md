# Redemption Functionality Implementation Update

## Overview

The redemption system allows users to convert their earned XP (Experience Points) into YAM tokens and subsequently into USD for withdrawal via supported payment gateways. The system includes comprehensive maturity period tracking, monthly redemption windows, and robust validation to ensure compliance with economic rules.

---

## 📋 Table of Contents

1. [Maturity Rules](#maturity-rules)
2. [Redemption Window Rules](#redemption-window-rules)
3. [Status Management](#status-management)
4. [Payment Gateway Integration](#payment-gateway-integration)
5. [Validation Rules](#validation-rules)
6. [User Interface](#user-interface)
7. [Database Schema](#database-schema)
8. [User Workflow](#user-workflow)
9. [Admin Workflow](#admin-workflow)
10. [Technical Implementation](#technical-implementation)

---

## 🔐 Maturity Rules

### **8-12 Week Maturity Period**

All XP entries must mature before becoming eligible for redemption. The maturity period is configurable between **8-12 weeks** (default: **10 weeks**).

#### Key Rules:
- **Maturity Calculation**: `Maturity Date = Delivery Date + Maturity Weeks`
- **Delivery Date**: The date when XP was earned/confirmed (stored in transaction data)
- **Default Maturity**: 10 weeks (70 days) from delivery date
- **Configurable Range**: 8-12 weeks (56-84 days)
- **Setting**: Stored in WordPress option `dongtrader_maturity_weeks`

#### Maturity Status Display:
- ✅ **Mature**: XP entry has passed the maturity period (≥ maturity date)
- ⏳ **Maturing (X days)**: XP entry is still within maturity period (X days remaining)
- ❓ **Unknown**: Delivery date not found (shows available transaction fields for debugging)

#### Delivery Date Extraction:
The system searches for delivery dates in the following order:
1. `delivery_date`
2. `earned_date`
3. `verification_date`
4. `order_date` / `order_datetime`
5. `date` / `created_at` / `timestamp`
6. `purchase_date` / `completed_date`
7. **Fallback**: Fetches order date from WooCommerce if `order_id` exists

#### Validation:
- **Pre-submission**: All selected XP entries must be mature (≥ maturity date)
- **Rejection**: If any entry is immature, redemption request is rejected with detailed error message
- **Storage**: Maturity dates are stored in redemption record (`maturity_date`, `oldest_delivery_date`, `youngest_delivery_date`)

---

## 📅 Redemption Window Rules

### **Monthly Redemption Windows**

Redemptions can only be submitted during specific monthly windows.

#### Default Window:
- **Start Date**: 1st of each month (00:00:00)
- **End Date**: 7th of each month (23:59:59)
- **Configurable**: Via WordPress options:
  - `dongtrader_redemption_window_start` (default: 1)
  - `dongtrader_redemption_window_end` (default: 7)

#### Special Rules:

1. **September 1st Block**:
   - ❌ **No redemptions allowed** on September 1st
   - System automatically blocks all redemption submissions on this date
   - Next available window: October 1-7

2. **Window Status**:
   - **Open**: Current date is within the monthly window period
   - **Closed**: Current date is outside the monthly window period

3. **Display**:
   - Shows next window date when closed
   - Shows days remaining until next window
   - Displays window date range (start - end)

#### Validation:
- **Pre-submission Check**: Validates current date is within redemption window
- **September 1st Check**: Blocks all submissions on September 1st
- **Storage**: `within_redemption_window` flag stored in redemption record

---

## 📊 Status Management

### **Redemption Status Flow**

#### Status Types:
- `pending` - Initial status when redemption is submitted
- `processing` - Admin has started processing the redemption
- `completed` - Payment has been confirmed and processed
- `rejected` - Redemption request has been rejected

### **Status Mapping (Admin to Usermeta)**

When admin updates redemption status, the corresponding XP entries in usermeta are updated:

| Admin Status | Usermeta Status | Description |
|-------------|----------------|-------------|
| `completed` | `redeemed` | XP has been redeemed and paid out |
| `rejected` | `released` | XP is released back to user (not redeemed) |
| `pending` | `requested` | XP is requested for redemption |
| `processing` | `processing` | XP redemption is being processed |

### **Usermeta Status Update Logic:**

1. **When Status = "completed"**:
   - Updates all selected XP entries to status `'redeemed'`
   - Updates `_discord_invite` and `_discord_poll` usermeta status
   - Triggers payment gateway (PayPal/Venmo)

2. **When Status = "rejected"**:
   - Updates all selected XP entries to status `'released'`
   - Releases XP back to user for future redemption

3. **When Status = "pending"**:
   - Updates all selected XP entries to status `'requested'`
   - Marks XP as requested (prevents duplicate requests)

4. **When Status = "processing"**:
   - Updates all selected XP entries to status `'processing'`
   - Indicates admin is processing the request

---

## 💳 Payment Gateway Integration

### **Supported Gateways:**
- **PayPal**: Send money with pre-filled amount and recipient
- **Venmo**: Send money with pre-filled amount and recipient

### **Payment Flow:**

1. **Admin selects "completed" status** in redemption details popup
2. **System validates payment method** and details
3. **Payment gateway URL is generated** with:
   - Pre-filled USD amount
   - Recipient information from payment details
4. **Modal opens** showing payment gateway option
5. **Payment gateway opens in new tab** (PayPal/Venmo website)
6. **Admin completes payment** in the new tab
7. **Admin confirms payment** in modal
8. **Database is updated** only after confirmation:
   - Redemption status → `completed`
   - Usermeta status → `redeemed`
   - `processed_date` is set to current timestamp

### **Security:**
- Database updates **only occur after payment confirmation**
- Payment gateway opens in **new tab** (prevents iframe blocking)
- Payment details are **validated before gateway generation**
- Transaction ID can be stored for tracking

---

## ✅ Validation Rules

### **Pre-Submission Validation:**

1. **Minimum USD Amount**:
   - ✅ User must have at least **$1.00 USD** to redeem
   - Validation: `total_usd >= 1.0`

2. **Active Redemption Check**:
   - ❌ User cannot have **pending** or **processing** redemption requests
   - Validation: No existing records with status `pending` or `processing`

3. **Maturity Validation**:
   - ✅ All selected XP entries must be **mature** (≥ maturity date)
   - Validation: Each entry's delivery date + maturity weeks ≤ current date
   - Rejection: Returns list of immature entries with days remaining

4. **Redemption Window Validation**:
   - ✅ Current date must be **within monthly redemption window**
   - Validation: Current date between window start and end
   - Rejection: Shows next window date and days until window

5. **September 1st Block**:
   - ❌ No redemptions allowed on **September 1st**
   - Validation: Current date ≠ September 1st

6. **Required Fields**:
   - ✅ Payment method must be selected
   - ✅ Payment details must be provided
   - ✅ XP/YAM/USD amounts must be > 0

### **Post-Submission Validation:**

1. **Database Integrity**:
   - Ensures user_id exists
   - Validates conversion rates
   - Checks meta_ids format

2. **XP Entry Validation**:
   - Verifies selected XP entries exist in usermeta
   - Ensures entries belong to the requesting user
   - Validates transaction status allows redemption

---

## 🎨 User Interface

### **XP Dashboard Display:**

#### Maturity Status Column:
Every XP transaction row shows:
- **Maturity Status Icon**: ✅ (Mature), ⏳ (Maturing), ❓ (Unknown)
- **Days Until Maturity**: Shows countdown if maturing
- **Debug Info**:
  - Earned date
  - Maturity date
  - Available fields (if unknown)

#### Transaction Types Displayed:
1. **Seller Transactions** (Green background)
2. **Buyer Transactions** (Blue background)
3. **Discord Invite** (Purple background)
4. **Discord Activity** (Indigo background)
5. **Discord Poll** (Light blue background)
6. **Talent Show Entry** (Orange background)

### **Redemption Popup:**

#### Redemption Window Banner:
- **Open Window** (Green):
  - "✅ Redemption Window Open"
  - "You can submit redemption requests now."
  
- **Closed Window** (Yellow):
  - "⏳ Redemption Window Closed"
  - "Next window: [Date] (X days)"
  - "Window: [Start Date] - [End Date]"

#### Redemption Summary:
- XP amount (`xp_redem`)
- YAM amount (`yam_redem`)
- USD amount (`usd_redem`)

#### Redemption Options:
- **Payment Method**: PayPal or Venmo dropdown
- **Payment Details**: Text input for recipient info
- **XP Selection**: Checkbox list of mature XP entries

#### Redeem Button Logic:
- **Shown When**:
  - No pending/processing redemption requests exist
  - User has ≥ $1.00 USD worth of XP
  - Currently within redemption window
  
- **Hidden When**:
  - Active redemption request exists
  - USD amount < $1.00
  - Outside redemption window

### **Admin Interface:**

#### Redemption Details Popup:
- **Redemption Info**: All stored redemption data
- **Status Update Form**:
  - Status dropdown (pending, processing, completed, rejected)
  - Admin notes field
  - Processed button
  
- **Payment Gateway Modal** (When "completed" selected):
  - Payment gateway selection
  - Opens gateway in new tab
  - Confirmation button
  - Cancel button

#### Hidden Debug Fields:
- `meta_ids` (hidden from admin view)
- `conversion_rate_xp_yam` (hidden)
- `conversion_rate_yam_usd` (hidden)

---

## 🗄️ Database Schema

### **Redemption Table: `wp_dongtrader_redemptions`**

```sql
CREATE TABLE wp_dongtrader_redemptions (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    xp_redem bigint(20) NOT NULL,
    yam_redem decimal(20,8) NOT NULL,
    usd_redem decimal(10,2) NOT NULL,
    conversion_rate_xp_yam decimal(20,8) NOT NULL,
    conversion_rate_yam_usd decimal(20,8) NOT NULL,
    meta_ids text,
    status varchar(20) DEFAULT 'pending',
    payment_method varchar(50) NOT NULL,
    payment_details text NOT NULL,
    redem_date datetime DEFAULT CURRENT_TIMESTAMP,
    processed_date datetime NULL,
    admin_notes text,
    transaction_id varchar(100) NULL,
    
    -- Maturity tracking fields
    maturity_date datetime NULL,
    oldest_delivery_date datetime NULL,
    youngest_delivery_date datetime NULL,
    maturity_weeks int(11) DEFAULT 10,
    
    -- Redemption window tracking
    within_redemption_window tinyint(1) DEFAULT 0,
    
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY status (status),
    KEY redem_date (redem_date),
    KEY maturity_date (maturity_date)
);
```

### **Key Fields:**

#### Maturity Tracking:
- `maturity_date`: Date when all selected XP entries will mature (based on youngest delivery date)
- `oldest_delivery_date`: Earliest delivery date among selected XP entries
- `youngest_delivery_date`: Latest delivery date among selected XP entries
- `maturity_weeks`: Number of weeks used for maturity calculation (8-12)

#### Redemption Window:
- `within_redemption_window`: Flag indicating if submission was within a redemption window (0/1)

---

## 🔄 User Workflow

### **Step 1: Check Maturity Status**
1. User views XP Dashboard
2. System displays maturity status for each XP entry:
   - ✅ Mature entries are eligible
   - ⏳ Maturing entries show days remaining
   - ❓ Unknown entries (requires delivery date fix)

### **Step 2: Check Redemption Window**
1. User views Redemption Window banner in popup
2. If window is **closed**, shows next window date
3. If window is **open**, proceeds to redemption

### **Step 3: Select XP for Redemption**
1. User clicks "Redeem" button (only shown if eligible)
2. Redemption popup opens
3. User selects mature XP entries via checkboxes
4. System calculates total XP, YAM, and USD amounts

### **Step 4: Submit Redemption Request**
1. User selects payment method (PayPal/Venmo)
2. User enters payment details (recipient info)
3. User reviews redemption summary
4. User clicks "Submit Redemption Request"
5. System validates:
   - Redemption window is open
   - All selected entries are mature
   - Minimum $1.00 USD
   - No active redemption requests
6. If valid → Request submitted → Status: `pending`
7. If invalid → Error message displayed

### **Step 5: Wait for Processing**
1. Redemption request status: `pending`
2. Admin reviews and processes request
3. Admin updates status:
   - `processing` → Admin is reviewing
   - `completed` → Payment confirmed
   - `rejected` → Request rejected (XP released back)

### **Step 6: Payment Confirmation**
1. Admin selects "completed" status
2. Payment gateway opens in new tab
3. Admin completes payment
4. Admin confirms payment in modal
5. System updates:
   - Redemption status → `completed`
   - XP entries status → `redeemed`
   - `processed_date` → Current timestamp

---

## 👨‍💼 Admin Workflow

### **Step 1: View Redemption Requests**
1. Navigate to Redemption Requests admin page
2. View list of all redemption requests
3. See status, user, amounts, and submission date

### **Step 2: View Redemption Details**
1. Click "View Details" button
2. Popup shows complete redemption information:
   - User details
   - XP/YAM/USD amounts
   - Conversion rates
   - Payment method and details
   - Maturity dates
   - Submission date

### **Step 3: Process Redemption**
1. Select status from dropdown:
   - `processing` → Mark as being processed
   - `completed` → Mark as completed (triggers payment gateway)
   - `rejected` → Reject and release XP back to user
   - `pending` → Keep as pending

2. Add admin notes (optional)

3. Click "Processed" button

### **Step 4: Complete Payment (if status = "completed")**
1. Payment gateway modal opens
2. Select payment gateway (PayPal/Venmo)
3. Click "Open Payment Gateway"
4. New tab opens with pre-filled:
   - USD amount
   - Recipient information
5. Complete payment in new tab
6. Return to admin modal
7. Click "Confirm Payment"
8. Database updated:
   - Status → `completed`
   - Usermeta → `redeemed`
   - `processed_date` → Current timestamp

### **Step 5: Post-Processing**
1. Status form is hidden for completed redemptions
2. "Processed" button is disabled
3. Redemption record shows as completed

---

## 🔧 Technical Implementation

### **Key Functions:**

#### Maturity Functions:
- `dongtrader_get_maturity_weeks()` - Gets configured maturity weeks (default: 10)
- `dongtrader_calculate_maturity_date($delivery_date, $weeks)` - Calculates maturity date
- `dongtrader_is_xp_entry_mature($delivery_date)` - Checks if XP entry is mature
- `dongtrader_get_delivery_date_from_xp_entry($transaction)` - Extracts delivery date from transaction
- `dongtrader_days_until_maturity($delivery_date)` - Calculates days until maturity

#### Redemption Window Functions:
- `dongtrader_get_monthly_redemption_window($month, $year)` - Gets window dates
- `dongtrader_is_within_redemption_window($current_date)` - Checks if within window
- `dongtrader_get_next_redemption_window()` - Gets next window date
- `dongtrader_days_until_next_redemption_window()` - Calculates days until next window

#### Validation Functions:
- `dongtrader_check_transaction_eligibility($transaction, $umeta_id)` - Checks if transaction is eligible
- Validates maturity, status, and delivery date for each XP entry

#### AJAX Handlers:
- `dongtrader_get_xp_umeta_ids()` - Returns mature XP entries for selection
- `dongtrader_submit_redemption_request()` - Processes redemption submission
- `dongtrader_ajax_get_payment_gateway_url()` - Returns payment gateway URL
- `dongtrader_ajax_update_redemption_status()` - Updates redemption status

### **WordPress Options:**
- `dongtrader_maturity_weeks` - Maturity period in weeks (default: 10, range: 8-12)
- `dongtrader_redemption_window_start` - Window start day (default: 1)
- `dongtrader_redemption_window_end` - Window end day (default: 7)

### **Database Queries:**
- Redemption table creation with maturity and window fields
- ALTER TABLE statements for existing installations
- Indexes on `user_id`, `status`, `redem_date`, `maturity_date`

---

## 📝 Summary

### **Implemented Features:**
✅ 8-12 week maturity period tracking and validation  
✅ Monthly redemption windows (1st-7th of each month)  
✅ September 1st redemption block  
✅ Status mapping (admin → usermeta)  
✅ Payment gateway integration (PayPal/Venmo)  
✅ Minimum $1.00 USD validation  
✅ Active redemption request prevention  
✅ Maturity status display for all transaction types  
✅ Redemption window status banner  
✅ Comprehensive pre-submission validation  
✅ Database schema with maturity and window tracking  
✅ Admin interface with payment gateway workflow  

### **Key Rules Enforced:**
1. XP entries must mature before redemption (8-12 weeks)
2. Redemptions only allowed during monthly windows (1st-7th)
3. No redemptions on September 1st
4. Minimum $1.00 USD required
5. No duplicate active redemption requests
6. All selected entries must be mature
7. Payment confirmation required before database update

### **User Experience:**
- Clear maturity status indicators
- Redemption window schedule display
- Detailed error messages
- Debug information for troubleshooting
- Seamless payment gateway integration

---

## 🔄 Future Enhancements (Not Yet Implemented)

- [ ] 96.5% face value calculation for monthly redemptions
- [ ] Annual reconciliation process (August 31)
- [ ] Settlement/rollover options (September 1)
- [ ] Tax statement generation (1099-PATR, 1099-K)
- [ ] Additional payment gateways (FonePay, Alipay, Cash App, Zelle)
- [ ] MSP transaction ID storage and verification
- [ ] 2030 trading cessation logic
- [ ] Debt carryover prevention
- [ ] Asset rollover mechanism

---

**Last Updated**: Current Date  
**Version**: 1.0  
**Status**: Production Ready ✅

