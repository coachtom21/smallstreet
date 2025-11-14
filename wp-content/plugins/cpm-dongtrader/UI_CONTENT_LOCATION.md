# UI Content Location - XP Dashboard, Leaderboard, and Summary

This document clarifies where the UI content for XP dashboard, leaderboard display, and XP summary is located.

---

## 📍 WHERE UI CONTENT IS LOCATED

### ❌ NOT in `cpm-dongtrader-msc-functions.php`

The functions file (`cpm-dongtrader-msc-functions.php`) does **NOT** contain HTML/PHP markup for:
- XP Dashboard UI
- Leaderboard display HTML
- XP Summary cards HTML

### ✅ What IS in `cpm-dongtrader-msc-functions.php`

**1. JavaScript for UI Interactions** (Lines 1599-2216)
- **Function:** `dongtrader_xp_transfers_script()`
- **Purpose:** Adds JavaScript that controls/manipulates the UI
- **Contains:**
  - Tab switching logic
  - Form validation
  - AJAX handlers
  - Dynamic HTML generation (via JavaScript)
  - Event handlers
  - Real-time validation

**2. Template Loading Function** (Lines 1164-1171)
- **Function:** `add_custom_tab_to_my_account()`
- **Purpose:** Loads template files that contain the actual UI
- **Code:**
```php
$tem_path = CPM_DONGTRADER_PLUGIN_DIR.'template-parts'.DIRECTORY_SEPARATOR.'content-'.$v['slug'].'.php';
if (file_exists($tem_path)) {
    load_template($tem_path,true, $vnd_rate_array);
}
```

---

## ✅ WHERE THE ACTUAL UI CONTENT IS

### 1. XP Dashboard / Wallet Summary
**File:** `wp-content/plugins/cpm-dongtrader/template-parts/content-detente-wallet.php`

**Contains:**
- ✅ **XP Summary Cards** (Lines 820-842)
  - XP Balance card
  - YAM Equivalent card
  - Confirmed Deliveries card
  - XP Sent card
  - XP Received card

- ✅ **Leaderboard Rank Display** (Lines 878-881)
  - Shows user's current rank: `#<?php echo $user_rank; ?>`
  - PBTV eligibility badge

- ✅ **XP Breakdown by Role** (Lines 890-911)
  - Buyer (7%) breakdown
  - Seller (3%) breakdown
  - Personal (10%) breakdown

- ✅ **Transaction History Table** (Lines 914-1050+)
  - Date, Proof ID, Role, XP Minted, YAM, Status columns

**Calculation Logic:**
- Leaderboard calculation (Lines 336-369) - Calculates all users' XP and ranks them
- XP totals calculation (Lines 237-297) - Calculates user's total XP

---

### 2. XP Transfers Page
**File:** `wp-content/plugins/cpm-dongtrader/template-parts/content-xp-transfers.php`

**Contains:**
- ✅ **Balance Display Cards** (Lines 1544-1580+)
  - Available XP
  - YAM Equivalent
  - USD Trade Value

- ✅ **Tab Navigation** (Lines 1500-1528)
  - Transactions tab
  - Send XP tab

- ✅ **Transaction History Table** (Lines 1600+)
  - Filter buttons (All, Sent, Received)
  - Pagination controls
  - Transaction rows

- ✅ **Send XP Form** (Lines 1700+)
  - Receiver search input
  - XP amount input
  - Memo field
  - Transfer summary

---

### 3. Leaderboard Display (Current State)

**Currently:**
- ❌ **No dedicated leaderboard page exists**
- ✅ **Leaderboard rank shown in wallet page** (`content-detente-wallet.php` line 880)
- ✅ **Backend calculation exists** (`content-detente-wallet.php` lines 336-369)

**What's Missing:**
- Public leaderboard page showing all users
- Full leaderboard table/list view
- Leaderboard API endpoint

---

## 🔄 HOW IT WORKS TOGETHER

### Flow Diagram:

```
User visits My Account → XP Transfers tab
    ↓
add_custom_tab_to_my_account() (functions file)
    ↓
Loads: content-xp-transfers.php (template file)
    ↓
Template contains HTML/PHP for:
    - Balance cards
    - Transaction table
    - Send XP form
    ↓
dongtrader_xp_transfers_script() (functions file)
    ↓
Adds JavaScript that:
    - Handles tab switching
    - Validates forms
    - Makes AJAX calls
    - Updates UI dynamically
```

---

## 📋 SUMMARY

| UI Element | Location | File |
|------------|----------|------|
| **XP Summary Cards** | Template | `content-detente-wallet.php` |
| **Leaderboard Rank** | Template | `content-detente-wallet.php` (line 880) |
| **XP Breakdown** | Template | `content-detente-wallet.php` |
| **Transaction History** | Template | `content-detente-wallet.php` |
| **XP Transfers UI** | Template | `content-xp-transfers.php` |
| **Balance Display** | Template | `content-xp-transfers.php` |
| **JavaScript Controls** | Functions | `cpm-dongtrader-msc-functions.php` |
| **Template Loader** | Functions | `cpm-dongtrader-msc-functions.php` |
| **Backend Calculations** | Template | `content-detente-wallet.php` |

---

## 🎯 KEY POINTS

1. **HTML/PHP Markup:** In template files (`template-parts/content-*.php`)
2. **JavaScript Logic:** In functions file (`cpm-dongtrader-msc-functions.php`)
3. **Backend Calculations:** In template files (they calculate data before displaying)
4. **Template Loading:** Functions file loads templates via `load_template()`

---

## 📝 TO ADD NEW UI ELEMENTS

**For HTML/PHP Content:**
- Add to appropriate template file:
  - `content-detente-wallet.php` - For wallet/dashboard UI
  - `content-xp-transfers.php` - For transfers UI
  - `content-leaderboard.php` - For leaderboard UI (to be created)

**For JavaScript Functionality:**
- Add to `dongtrader_xp_transfers_script()` function in `cpm-dongtrader-msc-functions.php`
- Or create new JavaScript function if needed

**For Backend Logic:**
- Add calculation functions to template files
- Or create helper functions in functions file

---

**Last Updated:** Based on codebase analysis
**Conclusion:** UI content is in template files, not in the functions file. The functions file only contains JavaScript that controls the UI.


