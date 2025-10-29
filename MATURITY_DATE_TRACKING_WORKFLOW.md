# Maturity Date Tracking Implementation Workflow

## Overview
Implement 8-12 week maturity period tracking so users can only redeem XP/YAM after the maturity period has passed from when they earned it.

---

## Step-by-Step Workflow

### PHASE 1: Data Structure & Storage

#### 1.1 Identify Where XP is Earned/Created
**Location:** `cpm-dongtrader-functions.php` - `dongtrader_submit_redemption_request()` and XP awarding functions

**Current State:**
- XP entries stored in `usermeta` table with meta_keys like:
  - `_seller_details` (array of transactions)
  - `_buyer_details` (array of transactions)
  - `_discord_invite` (single transaction)
  - `_talentshow_entry` (array of entries)
  - `_discord_poll` (array of entries)

**Action Required:**
- Ensure each XP transaction record includes `delivery_date` or `earned_date` timestamp
- Store as: `date('Y-m-d H:i:s')` when XP is initially awarded

#### 1.2 Check Current XP Data Structure
**Need to verify:**
- Do existing XP entries have dates? (likely `date` or `timestamp` field in transaction arrays)
- If missing dates, how do we handle historical data?

**Location to check:**
- `cpm-dongtrader-functions.php` - where seller/buyer/discord XP is created
- Look for places that create usermeta entries

---

### PHASE 2: Calculate Maturity Date

#### 2.1 Define Maturity Configuration
**Settings needed:**
- Minimum maturity weeks: **8 weeks** (56 days)
- Maximum maturity weeks: **12 weeks** (84 days)
- Default maturity weeks: **10 weeks** (70 days) - middle of range

**Storage:**
- WordPress option: `dongtrader_maturity_weeks` (default: 10)
- Allow admin to configure 8-12 weeks

#### 2.2 Maturity Date Calculation Function
**Function:** `calculate_maturity_date($delivery_date, $weeks = null)`

**Logic:**
```php
function calculate_maturity_date($delivery_date, $weeks = null) {
    if ($weeks === null) {
        $weeks = get_option('dongtrader_maturity_weeks', 10);
    }
    
    // Ensure weeks is between 8-12
    $weeks = max(8, min(12, intval($weeks)));
    
    // Add weeks to delivery date
    $delivery = new DateTime($delivery_date);
    $delivery->modify("+{$weeks} weeks");
    
    return $delivery->format('Y-m-d H:i:s');
}
```

#### 2.3 Check if XP Entry is Mature
**Function:** `is_xp_entry_mature($delivery_date, $current_date = null)`

**Logic:**
```php
function is_xp_entry_mature($delivery_date, $current_date = null) {
    if ($current_date === null) {
        $current_date = current_time('mysql');
    }
    
    $maturity_date = calculate_maturity_date($delivery_date);
    
    // Check if current date is >= maturity date
    return (strtotime($current_date) >= strtotime($maturity_date));
}
```

---

### PHASE 3: Validate Before Redemption Submission

#### 3.1 Filter XP Entries for Redemption
**Location:** `cpm-dongtrader-functions.php` - `dongtrader_get_xp_umeta_ids()` AJAX handler

**Current behavior:**
- Returns all eligible XP usermeta IDs

**New behavior needed:**
- Only return XP entries that are mature (8-12 weeks old)
- Exclude immature XP entries from redemption selection

**Modification:**
```php
// For each XP entry found:
1. Extract delivery_date/earned_date from transaction data
2. Check: is_xp_entry_mature($delivery_date)
3. If mature: include in results
4. If not mature: exclude from results (add to separate "pending maturity" list)
```

#### 3.2 Pre-Submission Validation
**Location:** `cpm-dongtrader-functions.php` - `dongtrader_submit_redemption_request()`

**Add validation:**
- Before inserting redemption request
- Loop through all `meta_ids` being redeemed
- Verify each one is mature
- If any are immature, reject with message showing which ones and when they mature

---

### PHASE 4: Store Maturity Info in Redemption Record

#### 4.1 Add Database Fields
**Table:** `wp_dongtrader_redemptions`

**New columns needed:**
```sql
ALTER TABLE wp_dongtrader_redemptions ADD COLUMN:
- maturity_date DATETIME NULL
- oldest_delivery_date DATETIME NULL (earliest XP delivery in this redemption)
- youngest_delivery_date DATETIME NULL (latest XP delivery in this redemption)
```

**Purpose:**
- `maturity_date`: When this redemption becomes eligible (based on youngest delivery)
- `oldest_delivery_date`: For reporting/tracking
- `youngest_delivery_date`: Determines overall maturity (must wait for latest one)

#### 4.2 Store on Redemption Creation
**When:** User submits redemption request

**Logic:**
```php
1. Loop through all meta_ids in redemption
2. Find youngest delivery_date (latest XP entry)
3. Calculate maturity_date = delivery_date + maturity_weeks
4. Store in redemption record
5. Store oldest and youngest delivery dates for reference
```

---

### PHASE 5: UI/UX Changes

#### 5.1 Redemption Popup - Show Maturity Info
**Location:** `cpm-dongtrader-functions.php` - Redemption popup HTML

**Display:**
- Show countdown: "X days until maturity" for immature entries
- Show "✅ Mature" badge for mature entries
- Disable immature entries in selection (or gray them out)
- Show message: "Only mature XP (8-12 weeks old) can be redeemed"

#### 5.2 XP Dashboard - Maturity Status
**Location:** XP dashboard display

**Add to each XP entry display:**
- Delivery date
- Maturity date (calculated)
- Status: "Mature" or "Maturing (X days remaining)"
- Visual indicator (green = mature, yellow = maturing)

#### 5.3 Admin Redemption Page - Maturity Column
**Location:** `functions.php` - `dongtrader_redemption_admin_page()`

**Add column:**
- "Maturity Date" column showing when redemption became eligible
- "Oldest/Youngest Delivery" dates for reference

---

### PHASE 6: Handle Historical Data (Edge Case)

#### 6.1 XP Entries Without Dates
**Problem:** Existing XP entries might not have `delivery_date`

**Solutions:**
1. **Best case:** If XP entries have dates (check existing data structure)
2. **Fallback:** If no date, use `usermeta.umeta_id` creation timestamp or `meta_value` creation date
3. **Emergency:** Assign a "default" delivery date (e.g., account creation date, or mark as "unknown" and require admin review)

**Action:**
- Query sample of existing XP data
- Check if `date`/`timestamp` field exists in transaction arrays
- Determine best approach for historical entries

---

## Implementation Order

### Step 1: Analyze Current Data Structure
- Check where XP is created
- Verify if dates already exist
- Document current transaction array structure

### Step 2: Create Helper Functions
- `calculate_maturity_date()`
- `is_xp_entry_mature()`
- `get_delivery_date_from_xp_entry()`

### Step 3: Update Database Schema
- Add `maturity_date`, `oldest_delivery_date`, `youngest_delivery_date` to redemption table
- Run migration for existing records

### Step 4: Ensure Delivery Dates Are Stored
- Modify XP creation functions to always store `delivery_date`
- Backfill historical data if needed

### Step 5: Filter Mature Entries
- Update `dongtrader_get_xp_umeta_ids()` to only return mature entries
- Show immature entries separately with maturity countdown

### Step 6: Validate Before Submission
- Add maturity check in `dongtrader_submit_redemption_request()`
- Reject if any selected entries are not mature

### Step 7: Store Maturity Info
- Calculate and store maturity_date when redemption created
- Store oldest/youngest delivery dates

### Step 8: Update UI
- Show maturity status in redemption popup
- Display maturity info in dashboard
- Add maturity column in admin

---

## Testing Checklist

- [ ] XP entries created with delivery_date
- [ ] Maturity date calculated correctly (8-12 weeks)
- [ ] Immature XP excluded from redemption selection
- [ ] Redemption submission blocked for immature XP
- [ ] Mature XP can be redeemed successfully
- [ ] UI shows maturity countdown correctly
- [ ] Admin page displays maturity info
- [ ] Historical XP entries handled correctly

---

## Configuration

**Admin Settings Page:**
- Add setting: "Maturity Period (weeks)" - dropdown 8, 9, 10, 11, 12
- Default: 10 weeks
- Location: Settings → YAM JAM → Redemption Settings

---

## Edge Cases to Handle

1. **XP entry with no date:** Use fallback (creation date or account date)
2. **Mixed mature/immature selection:** Block entire redemption, show which ones are immature
3. **Maturity weeks changed:** How to handle existing pending redemptions? (Use maturity_date already calculated)
4. **Redemption with multiple delivery dates:** Use youngest date for overall maturity

---

## Questions to Resolve

1. **Does existing XP data have dates?** (Need to check codebase)
2. **Default maturity weeks?** (Suggested: 10 weeks - middle of 8-12 range)
3. **Allow partial redemption?** (If user has 100 mature + 50 immature, allow 100 only?)
4. **Show immature entries in UI?** (Display but grayed out, or hide completely?)

---

*Ready for implementation once approved*

