# Leaderboard Features - Detailed Specification

This document provides detailed specifications for implementing the missing leaderboard features based on ChatGPT documentation and current codebase analysis.

---

## 1. Public Leaderboard Page

### Overview
A public-facing page that displays all users ranked by their total XP, allowing anyone to see the top performers in the SmallStreet ecosystem.

### Current State
- ✅ Backend calculation exists (ranks all users)
- ✅ User's own rank shown in wallet page
- ❌ No public page showing all users

### Requirements

#### 1.1 Page Structure
- **URL:** `/leaderboard/` or `/my-account/leaderboard/`
- **Access:** Public (no login required) OR logged-in users only
- **Layout:** Full-width page with table/list view

#### 1.2 Display Elements

**For Each User Row:**
- **Rank Number** (#1, #2, #3, etc.)
- **User Name** (display name or username)
- **Total XP** (formatted: e.g., "0.021630 XP" or "5000000" for Discord entries)
- **YAM Equivalent** (calculated: XP × 1,000,000)
- **USD Trade Value** (calculated: YAM ÷ 21,000)
- **POC** (Proof of Concept / Location - from `mega-glassfrog` usermeta)
- **Badges** (PBTV Candidate, Top Seller, etc.)
- **Confirmed Deliveries** (count of completed 2-scan PoDs)

**Table Headers:**
```
| Rank | User | XP | YAM | USD Value | POC | Badges | Deliveries |
```

#### 1.3 Features

**Pagination:**
- Show 50 users per page (configurable)
- Previous/Next buttons
- Page numbers
- "Showing 1-50 of 250 users"

**Sorting:**
- Default: By XP (descending)
- Optional: By name, by POC, by deliveries

**Search/Filter:**
- Search by username
- Filter by POC
- Filter by badges

**Real-time Updates:**
- Auto-refresh every 30 seconds (optional)
- "Last updated: [timestamp]" display
- Manual refresh button

#### 1.4 UI/UX Design

**Visual Hierarchy:**
- Top 3 users: Highlighted with gold/silver/bronze styling
- Top 10: Slightly highlighted
- Top 30: PBTV badge visible
- Current user (if logged in): Highlighted row

**Styling:**
- Match wallet page design
- Responsive table (mobile-friendly)
- Card view option for mobile
- Loading state while calculating

**Example Layout:**
```
┌─────────────────────────────────────────────────────────┐
│  XP Leaderboard                                         │
│  Last updated: 2025-11-10 15:30:00                     │
├─────────────────────────────────────────────────────────┤
│  Rank │ User        │ XP        │ YAM      │ POC      │
├─────────────────────────────────────────────────────────┤
│  🥇 1 │ Coach Tom   │ 0.021630  │ 21,630   │ Nepal    │
│  🥈 2 │ Asha Lama   │ 0.015141  │ 15,141   │ Kathmandu│
│  🥉 3 │ Ravi KC     │ 0.012489  │ 12,489   │ Pokhara  │
│    4  │ John Doe    │ 0.010000  │ 10,000   │ Global   │
└─────────────────────────────────────────────────────────┘
```

#### 1.5 Implementation Details

**File Location:**
- Create: `wp-content/plugins/cpm-dongtrader/template-parts/content-leaderboard.php`
- Add to My Account menu in `cpm-dongtrader-msc-functions.php`

**Data Source:**
- Reuse calculation from `content-detente-wallet.php` (lines 336-369)
- Query all users' scan data from usermeta
- Calculate total XP per user
- Sort by XP descending

**Performance Considerations:**
- Cache leaderboard data (transient API)
- Refresh cache every 5 minutes
- Consider pagination to limit query size

**Code Structure:**
```php
// Get all users and calculate XP
$all_users_xp = array();
// ... calculation logic ...

// Sort by XP
arsort($all_users_xp);

// Pagination
$per_page = 50;
$current_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($current_page - 1) * $per_page;
$total_users = count($all_users_xp);
$total_pages = ceil($total_users / $per_page);

// Get page slice
$page_users = array_slice($all_users_xp, $offset, $per_page, true);

// Display table
foreach ($page_users as $user_id => $total_xp) {
    // Get user data
    $user = get_userdata($user_id);
    $user_name = $user->display_name ? $user->display_name : $user->user_login;
    $user_poc = get_user_meta($user_id, 'mega-glassfrog', true);
    
    // Calculate YAM and USD
    $yam_equivalent = $total_xp * 1000000;
    $usd_value = $yam_equivalent / 21000;
    
    // Get badges
    $badges = get_user_badges($user_id, $rank);
    
    // Display row
}
```

---

## 2. Leaderboard API Endpoint

### Overview
REST API endpoint that returns leaderboard data in JSON format, enabling integration with mobile apps, third-party services, or React components.

### Current State
- ❌ No API endpoint exists
- ✅ Backend calculation exists (can be reused)

### Requirements

#### 2.1 Endpoint Specification

**Route:** `GET /wp-json/cpm-dongtrader/v1/leaderboard`

**Query Parameters:**
- `limit` (optional, default: 50) - Number of results to return
- `offset` (optional, default: 0) - Pagination offset
- `branch` (optional) - Filter by Peace Pentagon branch/POC
- `badge` (optional) - Filter by badge type (e.g., "pbtv", "top_seller")
- `search` (optional) - Search by username

**Example Requests:**
```
GET /wp-json/cpm-dongtrader/v1/leaderboard
GET /wp-json/cpm-dongtrader/v1/leaderboard?limit=100
GET /wp-json/cpm-dongtrader/v1/leaderboard?branch=media&limit=50
GET /wp-json/cpm-dongtrader/v1/leaderboard?offset=50&limit=50
GET /wp-json/cpm-dongtrader/v1/leaderboard?search=coach
```

#### 2.2 Response Format

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "as_of": "2025-11-10T15:30:00Z",
    "total_users": 250,
    "limit": 50,
    "offset": 0,
    "has_more": true,
    "branch": null,
    "leaders": [
      {
        "rank": 1,
        "user_id": 123,
        "user": "Coach Tom",
        "username": "coachtom",
        "xp": "0.021630",
        "yam_equivalent": 21630,
        "usd_trade_value": 1.03,
        "poc": "Nepal",
        "badges": ["PBTV Candidate", "Top Seller"],
        "confirmed_deliveries": 30,
        "xp_breakdown": {
          "buyer": "0.010584",
          "seller": "0.006489",
          "personal": "0.004557"
        }
      },
      {
        "rank": 2,
        "user_id": 456,
        "user": "Asha Lama",
        "username": "ashalama",
        "xp": "0.015141",
        "yam_equivalent": 15141,
        "usd_trade_value": 0.72,
        "poc": "Kathmandu",
        "badges": ["Buyer Champion"],
        "confirmed_deliveries": 21,
        "xp_breakdown": {
          "buyer": "0.015141",
          "seller": "0.000000",
          "personal": "0.000000"
        }
      }
    ]
  }
}
```

**Error Response (400/500):**
```json
{
  "success": false,
  "error": {
    "code": "invalid_limit",
    "message": "Limit must be between 1 and 100"
  }
}
```

#### 2.3 Implementation Details

**File Location:**
- Add to: `wp-content/themes/hello-elementor-child/functions.php`
- Or create: `wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-api.php`

**Code Structure:**
```php
// Register REST route
add_action('rest_api_init', function() {
    register_rest_route('cpm-dongtrader/v1', '/leaderboard', array(
        'methods' => 'GET',
        'callback' => 'cpm_get_leaderboard',
        'permission_callback' => '__return_true', // Public endpoint
        'args' => array(
            'limit' => array(
                'default' => 50,
                'sanitize_callback' => 'absint',
                'validate_callback' => function($param) {
                    return $param >= 1 && $param <= 100;
                }
            ),
            'offset' => array(
                'default' => 0,
                'sanitize_callback' => 'absint'
            ),
            'branch' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'badge' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'search' => array(
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            )
        )
    ));
});

function cpm_get_leaderboard($request) {
    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');
    $branch = $request->get_param('branch');
    $badge_filter = $request->get_param('badge');
    $search = $request->get_param('search');
    
    // Get all users XP (reuse existing calculation)
    $all_users_xp = cpm_calculate_all_users_xp();
    
    // Apply filters
    if ($branch) {
        $all_users_xp = cpm_filter_by_branch($all_users_xp, $branch);
    }
    
    if ($search) {
        $all_users_xp = cpm_search_users($all_users_xp, $search);
    }
    
    // Sort by XP
    arsort($all_users_xp);
    
    // Pagination
    $total_users = count($all_users_xp);
    $page_users = array_slice($all_users_xp, $offset, $limit, true);
    
    // Build response
    $leaders = array();
    $rank = $offset + 1;
    
    foreach ($page_users as $user_id => $total_xp) {
        $user = get_userdata($user_id);
        $badges = cpm_get_user_badges($user_id, $rank);
        
        // Apply badge filter
        if ($badge_filter && !in_array($badge_filter, $badges)) {
            continue;
        }
        
        $leaders[] = array(
            'rank' => $rank++,
            'user_id' => $user_id,
            'user' => $user->display_name ? $user->display_name : $user->user_login,
            'username' => $user->user_login,
            'xp' => number_format($total_xp, 6),
            'yam_equivalent' => intval($total_xp * 1000000),
            'usd_trade_value' => round(($total_xp * 1000000) / 21000, 2),
            'poc' => get_user_meta($user_id, 'mega-glassfrog', true),
            'badges' => $badges,
            'confirmed_deliveries' => cpm_count_confirmed_deliveries($user_id),
            'xp_breakdown' => cpm_get_xp_breakdown($user_id)
        );
    }
    
    return rest_ensure_response(array(
        'success' => true,
        'data' => array(
            'as_of' => current_time('c'),
            'total_users' => $total_users,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_users,
            'branch' => $branch ? $branch : null,
            'leaders' => $leaders
        )
    ));
}
```

**Caching:**
```php
// Cache leaderboard for 5 minutes
$cache_key = 'cpm_leaderboard_' . md5(serialize($request->get_params()));
$cached = get_transient($cache_key);

if ($cached !== false) {
    return rest_ensure_response($cached);
}

// Calculate leaderboard...
$response = array(/* ... */);

set_transient($cache_key, $response, 5 * MINUTE_IN_SECONDS);
return rest_ensure_response($response);
```

---

## 3. Branch Filtering (Peace Pentagon Branches)

### Overview
Filter leaderboard by Peace Pentagon branches/POCs, allowing users to see rankings within their specific community or branch.

### Current State
- ✅ POC field exists in user meta (`mega-glassfrog`)
- ❌ No branch filtering implemented
- ❌ No branch selector in UI

### Requirements

#### 3.1 Branch/POC Data Structure

**Current Storage:**
- User meta key: `mega-glassfrog`
- Stores POC name as text (e.g., "Nepal", "Kathmandu", "Global")

**Peace Pentagon Branches:**
Based on documentation, branches may include:
- Media
- Distribution
- Technology
- Finance
- Education
- Health
- Agriculture
- Energy
- Transportation
- Governance

**Note:** Need to clarify if branches are:
1. Same as POC field (`mega-glassfrog`)
2. Separate field
3. Derived from POC values

#### 3.2 Filtering Implementation

**In Public Leaderboard Page:**
```php
// Branch filter dropdown
<select name="branch" id="branch-filter">
    <option value="">All Branches</option>
    <option value="media">Media</option>
    <option value="distribution">Distribution</option>
    <option value="technology">Technology</option>
    <!-- ... more branches ... -->
</select>
```

**Filter Logic:**
```php
function cpm_filter_leaderboard_by_branch($users_xp, $branch) {
    $filtered = array();
    
    foreach ($users_xp as $user_id => $xp) {
        $user_poc = get_user_meta($user_id, 'mega-glassfrog', true);
        
        // Match branch (case-insensitive, partial match)
        if (empty($branch) || 
            stripos($user_poc, $branch) !== false ||
            $user_poc === $branch) {
            $filtered[$user_id] = $xp;
        }
    }
    
    return $filtered;
}
```

**In API Endpoint:**
- Already included in API spec (see section 2)
- Query parameter: `?branch=media`

#### 3.3 UI/UX

**Filter Options:**
- Dropdown selector
- "All Branches" option (default)
- Show count per branch: "Media (25 users)"
- Active filter indicator

**Display:**
```
┌─────────────────────────────────────────┐
│  Filter by Branch: [All Branches ▼]     │
│  Showing: Media branch (25 users)      │
└─────────────────────────────────────────┘
```

#### 3.4 Implementation Steps

1. **Clarify Branch Structure:**
   - Determine if branches = POC values
   - Or if separate branch field needed
   - Get list of official Peace Pentagon branches

2. **Add Filter to Leaderboard Page:**
   - Add dropdown/select element
   - Apply filter on selection
   - Update URL with query parameter
   - Maintain filter state on pagination

3. **Update API Endpoint:**
   - Already supports `branch` parameter
   - Implement filtering logic

4. **Add Branch Statistics:**
   - Show user count per branch
   - Show top user per branch
   - Branch-specific leaderboard page

---

## 4. Badge System

### Overview
Visual indicators/badges that recognize user achievements and roles, displayed next to usernames in the leaderboard.

### Current State
- ✅ PBTV eligibility badge exists (Top 30)
- ❌ No other badges implemented
- ❌ Badge calculation not centralized

### Requirements

#### 4.1 Badge Types

**From ChatGPT Documentation:**
- **PBTV Candidate** - Top 30 users (already exists)
- **Top Seller** - Highest seller XP
- **Buyer Champion** - Highest buyer XP
- **Personal Leader** - Highest personal XP

**Additional Badges (Suggested):**
- **XP Milestone** - Reached 0.1, 0.5, 1.0 XP
- **Delivery Master** - 50+, 100+, 200+ confirmed deliveries
- **Early Adopter** - Joined before certain date
- **Discord Verified** - Has `_discord_invite` meta

#### 4.2 Badge Criteria

**PBTV Candidate:**
- Criteria: Rank ≤ 30
- Badge: "PBTV Candidate" or "🏆 PBTV"
- Color: Gold

**Top Seller:**
- Criteria: Highest total XP from `seller_scan`
- Badge: "Top Seller" or "💼 Top Seller"
- Color: Blue
- Note: Only one user can have this

**Buyer Champion:**
- Criteria: Highest total XP from `buyer_scan`
- Badge: "Buyer Champion" or "🛍️ Buyer Champion"
- Color: Green
- Note: Only one user can have this

**Personal Leader:**
- Criteria: Highest total XP from `personal_scan`
- Badge: "Personal Leader" or "🙋 Personal Leader"
- Color: Purple
- Note: Only one user can have this

**XP Milestone:**
- Criteria: Total XP ≥ threshold
- Badges:
  - "0.1 XP Club" (≥ 0.1 XP)
  - "0.5 XP Club" (≥ 0.5 XP)
  - "1.0 XP Club" (≥ 1.0 XP)
- Color: Silver/Bronze/Gold

**Delivery Master:**
- Criteria: Confirmed deliveries count
- Badges:
  - "50 Deliveries" (≥ 50)
  - "100 Deliveries" (≥ 100)
  - "200 Deliveries" (≥ 200)
- Color: Bronze/Silver/Gold

#### 4.3 Implementation

**Badge Calculation Function:**
```php
function cpm_get_user_badges($user_id, $rank = null) {
    $badges = array();
    
    // Calculate rank if not provided
    if ($rank === null) {
        $rank = cpm_get_user_rank($user_id);
    }
    
    // PBTV Candidate (Top 30)
    if ($rank <= 30) {
        $badges[] = 'PBTV Candidate';
    }
    
    // Get role-specific XP
    $seller_xp = cpm_get_user_xp_by_role($user_id, 'seller');
    $buyer_xp = cpm_get_user_xp_by_role($user_id, 'buyer');
    $personal_xp = cpm_get_user_xp_by_role($user_id, 'personal');
    $total_xp = $seller_xp + $buyer_xp + $personal_xp;
    
    // Top Seller (check if highest)
    $top_seller_id = cpm_get_top_user_by_role('seller');
    if ($top_seller_id == $user_id) {
        $badges[] = 'Top Seller';
    }
    
    // Buyer Champion (check if highest)
    $top_buyer_id = cpm_get_top_user_by_role('buyer');
    if ($top_buyer_id == $user_id) {
        $badges[] = 'Buyer Champion';
    }
    
    // Personal Leader (check if highest)
    $top_personal_id = cpm_get_top_user_by_role('personal');
    if ($top_personal_id == $user_id) {
        $badges[] = 'Personal Leader';
    }
    
    // XP Milestones
    if ($total_xp >= 1.0) {
        $badges[] = '1.0 XP Club';
    } elseif ($total_xp >= 0.5) {
        $badges[] = '0.5 XP Club';
    } elseif ($total_xp >= 0.1) {
        $badges[] = '0.1 XP Club';
    }
    
    // Delivery Master
    $deliveries = cpm_count_confirmed_deliveries($user_id);
    if ($deliveries >= 200) {
        $badges[] = '200 Deliveries';
    } elseif ($deliveries >= 100) {
        $badges[] = '100 Deliveries';
    } elseif ($deliveries >= 50) {
        $badges[] = '50 Deliveries';
    }
    
    // Discord Verified
    global $wpdb;
    $discord_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->usermeta} 
         WHERE user_id = %d AND meta_key = %s",
        $user_id,
        '_discord_invite'
    ));
    if ($discord_count > 0) {
        $badges[] = 'Discord Verified';
    }
    
    return $badges;
}

function cpm_get_top_user_by_role($role) {
    global $wpdb;
    
    $meta_key = $role . '_scan';
    $all_users_xp = array();
    
    // Get all users' XP for this role
    $scan_meta = $wpdb->get_results(
        "SELECT user_id, meta_value FROM {$wpdb->usermeta} 
         WHERE meta_key = %s",
        $meta_key
    );
    
    foreach ($scan_meta as $meta) {
        $user_id = intval($meta->user_id);
        $scan_data = maybe_unserialize($meta->meta_value);
        
        if (is_array($scan_data)) {
            $role_xp = 0;
            foreach ($scan_data as $entry) {
                if (is_array($entry) && 
                    isset($entry['scan_status']) && 
                    $entry['scan_status'] === 'confirmed') {
                    $role_xp += floatval($entry['xp_units']);
                }
            }
            $all_users_xp[$user_id] = $role_xp;
        }
    }
    
    if (empty($all_users_xp)) {
        return null;
    }
    
    arsort($all_users_xp);
    return array_key_first($all_users_xp);
}
```

**Badge Display:**
```php
function cpm_display_badges($badges) {
    if (empty($badges)) {
        return '';
    }
    
    $badge_icons = array(
        'PBTV Candidate' => '🏆',
        'Top Seller' => '💼',
        'Buyer Champion' => '🛍️',
        'Personal Leader' => '🙋',
        'Discord Verified' => '✅'
    );
    
    $badge_colors = array(
        'PBTV Candidate' => '#fbbf24', // Gold
        'Top Seller' => '#3b82f6',    // Blue
        'Buyer Champion' => '#10b981', // Green
        'Personal Leader' => '#8b5cf6', // Purple
        'Discord Verified' => '#6366f1' // Indigo
    );
    
    $output = '<div class="user-badges">';
    foreach ($badges as $badge) {
        $icon = isset($badge_icons[$badge]) ? $badge_icons[$badge] . ' ' : '';
        $color = isset($badge_colors[$badge]) ? $badge_colors[$badge] : '#6b7280';
        $output .= sprintf(
            '<span class="badge" style="background: %s; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-right: 4px;">%s%s</span>',
            $color,
            $icon,
            esc_html($badge)
        );
    }
    $output .= '</div>';
    
    return $output;
}
```

**CSS Styling:**
```css
.user-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 4px;
}

.badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 4px;
    white-space: nowrap;
}
```

#### 4.4 Badge Display Locations

1. **Leaderboard Table:**
   - Badge column or inline with username
   - Tooltip on hover showing badge description

2. **User Wallet Page:**
   - Badge section showing user's badges
   - Achievement progress indicators

3. **API Response:**
   - `badges` array in leaderboard endpoint
   - Filter by badge type

---

## 5. Leaderboard in Landing Page (React Component)

### Overview
A React component that displays a leaderboard sidebar on the landing page, showing top users to encourage engagement and competition.

### Current State
- ❌ No landing page exists
- ❌ No React components
- ✅ System is PHP-based

### Requirements

#### 5.1 Component Structure

**From ChatGPT Documentation:**
- Sidebar component on landing page
- Shows top 5-10 users
- Compact display
- Real-time or cached data

**Component Props:**
```typescript
interface LeaderboardProps {
  limit?: number;        // Default: 5
  showBadges?: boolean;  // Default: true
  showPOC?: boolean;     // Default: true
  branch?: string;       // Optional branch filter
}
```

#### 5.2 Display Format

**Compact Sidebar View:**
```
┌─────────────────────────┐
│  XP Leaderboard         │
├─────────────────────────┤
│  #1 Coach Tom           │
│     0.021630 XP         │
│     🏆 PBTV Candidate   │
│                         │
│  #2 Asha Lama           │
│     0.015141 XP         │
│     🛍️ Buyer Champion   │
│                         │
│  #3 Ravi KC             │
│     0.012489 XP         │
│                         │
│  View Full Leaderboard →│
└─────────────────────────┘
```

#### 5.3 Implementation Options

**Option 1: PHP Template (Recommended for Current System)**
- Create PHP template for leaderboard sidebar
- Include in landing page template
- Fetch data via existing calculation
- No React needed

**Option 2: React Component (If Migrating to React)**
- Create React component
- Fetch data from API endpoint (section 2)
- Use WordPress REST API
- Requires React setup

**Option 3: Hybrid Approach**
- PHP backend generates data
- JavaScript fetches and renders
- No React, just vanilla JS

#### 5.4 PHP Implementation (Recommended)

**File:** `template-parts/content-leaderboard-sidebar.php`

```php
<?php
// Get top N users
$limit = isset($args['limit']) ? intval($args['limit']) : 5;
$all_users_xp = cpm_calculate_all_users_xp();
arsort($all_users_xp);
$top_users = array_slice($all_users_xp, 0, $limit, true);
?>

<div class="leaderboard-sidebar">
    <h3>XP Leaderboard</h3>
    <ul class="leaderboard-list">
        <?php 
        $rank = 1;
        foreach ($top_users as $user_id => $xp): 
            $user = get_userdata($user_id);
            $user_name = $user->display_name ? $user->display_name : $user->user_login;
            $badges = cpm_get_user_badges($user_id, $rank);
        ?>
        <li class="leaderboard-item">
            <div class="rank">#<?php echo $rank; ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo esc_html($user_name); ?></div>
                <div class="user-xp"><?php echo number_format($xp, 6); ?> XP</div>
                <?php if (!empty($badges)): ?>
                <div class="user-badges">
                    <?php echo cpm_display_badges(array_slice($badges, 0, 1)); ?>
                </div>
                <?php endif; ?>
            </div>
        </li>
        <?php 
        $rank++;
        endforeach; 
        ?>
    </li>
    <li class="view-all">
        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount') . 'leaderboard'); ?>">
            View Full Leaderboard →
        </a>
    </li>
</div>
```

**CSS:**
```css
.leaderboard-sidebar {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
}

.leaderboard-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.leaderboard-item {
    display: flex;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.leaderboard-item:last-child {
    border-bottom: none;
}

.rank {
    font-weight: 700;
    color: #6b7280;
    min-width: 30px;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: #111827;
}

.user-xp {
    font-size: 12px;
    color: #6b7280;
    font-family: monospace;
}
```

#### 5.5 React Component (If Using React)

**Component:**
```jsx
import React, { useState, useEffect } from 'react';

function LeaderboardSidebar({ limit = 5, showBadges = true, branch = null }) {
    const [leaders, setLeaders] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchLeaderboard();
    }, [limit, branch]);

    const fetchLeaderboard = async () => {
        setLoading(true);
        try {
            const url = `/wp-json/cpm-dongtrader/v1/leaderboard?limit=${limit}${branch ? `&branch=${branch}` : ''}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                setLeaders(data.data.leaders);
            }
        } catch (error) {
            console.error('Error fetching leaderboard:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div>Loading leaderboard...</div>;
    }

    return (
        <div className="leaderboard-sidebar">
            <h3>XP Leaderboard</h3>
            <ul className="leaderboard-list">
                {leaders.map((leader) => (
                    <li key={leader.user_id} className="leaderboard-item">
                        <div className="rank">#{leader.rank}</div>
                        <div className="user-info">
                            <div className="user-name">{leader.user}</div>
                            <div className="user-xp">{leader.xp} XP</div>
                            {showBadges && leader.badges.length > 0 && (
                                <div className="user-badges">
                                    {leader.badges[0]}
                                </div>
                            )}
                        </div>
                    </li>
                ))}
            </ul>
            <a href="/my-account/leaderboard">View Full Leaderboard →</a>
        </div>
    );
}

export default LeaderboardSidebar;
```

---

## Implementation Priority

1. **Public Leaderboard Page** - Highest priority (enables all other features)
2. **Badge System** - High priority (adds gamification)
3. **Leaderboard API Endpoint** - Medium priority (enables integrations)
4. **Branch Filtering** - Medium priority (depends on branch structure)
5. **Landing Page Sidebar** - Lower priority (depends on landing page)

---

## Testing Checklist

- [ ] Leaderboard page displays all users correctly
- [ ] Pagination works (next/previous/page numbers)
- [ ] Search/filter functions properly
- [ ] API endpoint returns correct JSON
- [ ] API supports all query parameters
- [ ] Branch filtering works correctly
- [ ] Badges calculate and display properly
- [ ] Badge criteria are accurate
- [ ] Sidebar component displays top users
- [ ] Performance is acceptable (caching works)
- [ ] Mobile responsive design
- [ ] Real-time updates (if implemented)

---

**Last Updated:** Based on ChatGPT documentation and codebase analysis
**Next Steps:** Start with Public Leaderboard Page implementation


