# Complete List of User Roles Used in the Site

## 1. Dong User Roles (Custom User Meta Field: `dong_user_role`)
These roles are stored in the user meta field `dong_user_role` and can be set manually by admins or automatically based on product purchases.

### Available Dong User Roles:
1. **Planning** (Purple)
   - Color code: `purple`
   - Sector slug: `planning`

2. **Budget** (Orange)
   - Color code: `orange`
   - Sector slug: `budget`

3. **Media** (Red)
   - Color code: `red`
   - Sector slug: `media`

4. **Distribution** (Green)
   - Color code: `green`
   - Sector slug: `distribution`

5. **Membership** (Blue)
   - Color code: `blue`
   - Sector slug: `membership`
   - Default role if no color is specified

**Location:** 
- Admin UI: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php` (lines 1130-1154)
- Role mapping: `dongtrader_get_product_color()` function (lines 1190-1213)
- Sector conversion: `dongtrader_convert_sector_to_slug()` function (lines 1215-1238)

**Note:** These roles were previously used to determine seller status, but that logic has been removed. They may still be used for organizational/display purposes.

---

## 2. Membership Levels (Paid Memberships Pro - PMPro)
These are membership levels managed through the PMPro plugin system.

### Known Membership Types:
1. **YAMer**
   - Free membership (0 XP awarded)
   - Stored in order meta: `_membership_name` = 'YAMer'
   - Created via: `create_yamer_membership_level_if_not_exists()` function
   - Location: `wp-content/plugins/cpm-dongtrader/inc/cpm-woocommerce-functions.php` (lines 1240-1279)

2. **Patron**
   - Paid membership (10,000,000 XP awarded)
   - Stored in order meta: `_membership_name` = 'Patron'
   - Has special form: `dongtrader_patron_form()` shortcode
   - Location: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php` (lines 2325-2689)

3. **MEGAvoter** / **MegaVoter**
   - Referral-based membership
   - Used in registration/export forms
   - Location: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php` (line 1353)

**Location:** 
- Membership assignment: `mega_set_membership_level()` in `cpm-woocommerce-functions.php`
- Buyer details storage: `mega_save_details_for_non_gf_members()` (lines 1858-1908)

---

## 3. Activity-Based User Types
These are determined by user activity/transactions, not by assigned roles.

### User Types:
1. **Seller**
   - Determined by: Having transactions in `_seller_details` user meta
   - Activities: QR code scanning, OTP verification
   - XP per transaction: 1,000,000 XP
   - Location: Determined dynamically in `dongtrader_display_xp_dashboard()` (line 3699)

2. **Buyer**
   - Determined by: Having transactions in `_buyer_details` user meta OR not having seller details
   - Activities: Membership purchases
   - XP per transaction: 10,000,000 XP (paid memberships) or 0 XP (YAMer)
   - Location: Determined dynamically in `dongtrader_display_xp_dashboard()` (line 3703)

---

## 4. WordPress Standard Roles
Standard WordPress user roles that may be used in the system:
- **Administrator** - Full access
- **Editor** - Can publish and manage posts
- **Author** - Can publish own posts
- **Contributor** - Can write but not publish posts
- **Subscriber** - Basic user role (most common for regular users)

---

## 5. Role Assignment Logic

### Automatic Assignment:
- **Dong User Roles**: Automatically assigned based on product color attribute when order is completed
  - Hook: `woocommerce_order_status_completed`
  - Function: `dong_auto_set_user_role_on_checkout()` → `dong_set_user_role()` → `dongtrader_get_product_color()`
  - Location: `wp-content/plugins/cpm-dongtrader/cpm-dongtrader.php` (lines 102-115)

### Manual Assignment:
- **Dong User Roles**: Can be manually set by admins in WordPress user profile page
  - Function: `dong_show_user_role()` and `dong_user_role_save_profile_fields()`
  - Location: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php` (lines 1119-1179)

### Membership Levels:
- Assigned automatically when orders are completed
- Function: `mega_set_membership_level()` in `cpm-woocommerce-functions.php`
- Uses PMPro functions: `pmpro_changeMembershipLevel()`

---

## 6. Role Storage Locations

| Role Type | Storage Method | Meta Key / Field |
|-----------|---------------|------------------|
| Dong User Role | User Meta | `dong_user_role` |
| Membership Level | PMPro System | PMPro membership levels table |
| Membership Name | Order Meta | `_membership_name` |
| Seller Status | Derived from transactions | `_seller_details` (user meta) |
| Buyer Status | Derived from transactions | `_buyer_details` (user meta) |

---

## Summary

**Total Role Types Found:**
- **5 Dong User Roles**: Planning, Budget, Media, Distribution, Membership
- **3 Membership Levels**: YAMer, Patron, MEGAvoter
- **2 Activity-Based Types**: Seller, Buyer
- **5 WordPress Standard Roles**: Administrator, Editor, Author, Contributor, Subscriber

**Total: 15 different role classifications**

---

*Last Updated: Based on code analysis of wp-content/plugins/cpm-dongtrader and related files*









