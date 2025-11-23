<?php
/**
 * XP Transfers Page Template
 * Redesigned according to XP_TRANSFER_SYSTEM_SPEC.md
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to view XP transfers.', 'cpm-dongtrader') . '</p>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Check for Discord verification
// _discord_invite can have multiple rows in usermeta table (one per invite)
// We check if any row exists with meta_key = '_discord_invite' for this user
global $wpdb;
$discord_invite_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s",
    $user_id,
    '_discord_invite'
));
$is_discord_verified = ($discord_invite_count > 0);


// Get scan data from usermeta tables
$seller_scan_raw = get_user_meta($user_id, 'seller_scan', true);
$buyer_scan_raw = get_user_meta($user_id, 'buyer_scan', true);
$personal_scan_raw = get_user_meta($user_id, 'personal_scan', true);

// Unserialize if needed
$seller_scan_data = maybe_unserialize($seller_scan_raw);
$buyer_scan_data = maybe_unserialize($buyer_scan_raw);
$personal_scan_data = maybe_unserialize($personal_scan_raw);

// Ensure arrays
if (!is_array($seller_scan_data)) {
    $seller_scan_data = array();
}
if (!is_array($buyer_scan_data)) {
    $buyer_scan_data = array();
}
if (!is_array($personal_scan_data)) {
    $personal_scan_data = array();
}

// Combine all scan entries into one array
$user_treasury_entries = array();

// Add seller scans
foreach ($seller_scan_data as $entry) {
    if (is_array($entry) && !empty($entry)) {
        $entry['source'] = 'seller_scan';
        $entry['role'] = isset($entry['role']) ? $entry['role'] : 'seller';
        $user_treasury_entries[] = $entry;
    }
}

// Add buyer scans
foreach ($buyer_scan_data as $entry) {
    if (is_array($entry) && !empty($entry)) {
        $entry['source'] = 'buyer_scan';
        $entry['role'] = isset($entry['role']) ? $entry['role'] : 'buyer';
        $user_treasury_entries[] = $entry;
    }
}

// Add personal scans
foreach ($personal_scan_data as $entry) {
    if (is_array($entry) && !empty($entry)) {
        $entry['source'] = 'personal_scan';
        $entry['role'] = isset($entry['role']) ? $entry['role'] : 'personal';
        $user_treasury_entries[] = $entry;
    }
}

// Get and add Discord invite entries
$discord_invite_raw = get_user_meta($user_id, '_discord_invite', false); // Get all rows
if (!empty($discord_invite_raw) && is_array($discord_invite_raw)) {
    foreach ($discord_invite_raw as $discord_entry_raw) {
        $discord_entry = maybe_unserialize($discord_entry_raw);
        
        // Handle JSON string format
        if (is_string($discord_entry)) {
            $decoded = json_decode($discord_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $discord_entry = $decoded;
            } else {
                continue;
            }
        }
        
        if (is_array($discord_entry) && !empty($discord_entry)) {
            // NEW CONVERSION: XP is stored directly, or convert from USD if needed
            if (isset($discord_entry['xp_units'])) {
                $xp_units = is_string($discord_entry['xp_units']) ? floatval($discord_entry['xp_units']) : floatval($discord_entry['xp_units']);
            } elseif (isset($discord_entry['xp_awarded'])) {
                // Legacy: xp_awarded might be in old YAM format, convert to new XP
                $xp_awarded_yam = intval($discord_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam / 1000000; // Legacy conversion
            } else {
                $xp_units = 0;
            }
            
            // Calculate USD from XP using new conversion: USD = XP / 10^23
            $trade_value_usd = dongtrader_xp_to_usd($xp_units);
            // NEW CONVERSION: YAM = XP / 10^23 (1 YAM = 1 USD = 10^23 XP)
            $yam_value = dongtrader_xp_to_yam($xp_units);
            
            $formatted_entry = array(
                'source' => 'discord_invite',
                'role' => 'Discord Verification',
                'timestamp' => isset($discord_entry['verification_date']) ? $discord_entry['verification_date'] : (isset($discord_entry['joined_at']) ? $discord_entry['joined_at'] : current_time('mysql')),
                'proof_id' => 'discord_' . (isset($discord_entry['discord_id']) ? $discord_entry['discord_id'] : 'invite'),
                'xp_units' => $xp_units,
                'xp_display_value' => isset($xp_awarded_yam) ? $xp_awarded_yam : 0,
                'yam_value' => $yam_value,
                'trade_value_usd' => $trade_value_usd,
                'trade_value' => $trade_value_usd,
                'status' => isset($discord_entry['status']) ? $discord_entry['status'] : 'completed',
                'scan_status' => isset($discord_entry['status']) ? $discord_entry['status'] : 'completed',
            );
            $user_treasury_entries[] = $formatted_entry;
        }
    }
}

// Get and add Talent Show entries
$talentshow_entry_raw = get_user_meta($user_id, '_talentshow_entry', false); // Get all rows
if (!empty($talentshow_entry_raw) && is_array($talentshow_entry_raw)) {
    foreach ($talentshow_entry_raw as $talent_entry_raw) {
        $talent_entry = maybe_unserialize($talent_entry_raw);
        
        // Handle JSON string format
        if (is_string($talent_entry)) {
            $decoded = json_decode($talent_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $talent_entry = $decoded;
            } else {
                continue;
            }
        }
        
        if (is_array($talent_entry) && !empty($talent_entry)) {
            // NEW CONVERSION: XP is stored directly
            if (isset($talent_entry['xp_units'])) {
                $xp_units = is_string($talent_entry['xp_units']) ? floatval($talent_entry['xp_units']) : floatval($talent_entry['xp_units']);
            } elseif (isset($talent_entry['xp_awarded'])) {
                // Legacy conversion from YAM
                $xp_awarded_yam = intval($talent_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam / 1000000;
            } else {
                $xp_units = 0;
            }
            
            // Calculate USD from XP using new conversion
            $trade_value_usd = dongtrader_xp_to_usd($xp_units);
            // NEW CONVERSION: YAM = XP / 10^23 (1 YAM = 1 USD = 10^23 XP)
            $yam_value = dongtrader_xp_to_yam($xp_units);
            
            $formatted_entry = array(
                'source' => 'talentshow_entry',
                'role' => 'Talent Show',
                'timestamp' => isset($talent_entry['submission_date']) ? $talent_entry['submission_date'] : current_time('mysql'),
                'proof_id' => 'talentshow_' . (isset($talent_entry['performance_type']) ? sanitize_title($talent_entry['performance_type']) : 'entry'),
                'xp_units' => $xp_units,
                'xp_display_value' => isset($xp_awarded_yam) ? $xp_awarded_yam : 0,
                'yam_value' => $yam_value,
                'trade_value_usd' => $trade_value_usd,
                'trade_value' => $trade_value_usd,
                'status' => isset($talent_entry['status']) ? $talent_entry['status'] : 'submitted',
                'scan_status' => isset($talent_entry['status']) ? $talent_entry['status'] : 'submitted',
            );
            $user_treasury_entries[] = $formatted_entry;
        }
    }
}

// Get and add Discord Poll entries
$discord_poll_raw = get_user_meta($user_id, '_discord_poll', false); // Get all rows
if (!empty($discord_poll_raw) && is_array($discord_poll_raw)) {
    foreach ($discord_poll_raw as $poll_entry_raw) {
        $poll_entry = maybe_unserialize($poll_entry_raw);
        
        // Handle JSON string format
        if (is_string($poll_entry)) {
            $decoded = json_decode($poll_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $poll_entry = $decoded;
            } else {
                continue;
            }
        }
        
        if (is_array($poll_entry) && !empty($poll_entry)) {
            // NEW CONVERSION: XP is stored directly
            if (isset($poll_entry['xp_units'])) {
                $xp_units = is_string($poll_entry['xp_units']) ? floatval($poll_entry['xp_units']) : floatval($poll_entry['xp_units']);
            } elseif (isset($poll_entry['xp_awarded'])) {
                // Legacy conversion from YAM
                $xp_awarded_yam = intval($poll_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam > 0 ? ($xp_awarded_yam / 1000000) : 0;
            } else {
                $xp_units = 0;
            }
            
            // Calculate USD from XP using new conversion
            $trade_value_usd = $xp_units > 0 ? dongtrader_xp_to_usd($xp_units) : 0;
            // NEW CONVERSION: YAM = XP / 10^23 (1 YAM = 1 USD = 10^23 XP)
            $yam_value = dongtrader_xp_to_yam($xp_units);
            
            $formatted_entry = array(
                'source' => 'discord_poll',
                'role' => 'Discord Poll',
                'timestamp' => isset($poll_entry['vote_date']) ? $poll_entry['vote_date'] : (isset($poll_entry['submission_date']) ? $poll_entry['submission_date'] : current_time('mysql')),
                'proof_id' => 'poll_' . (isset($poll_entry['poll_id']) ? $poll_entry['poll_id'] : 'entry'),
                'xp_units' => $xp_units,
                'xp_display_value' => isset($xp_awarded_yam) ? $xp_awarded_yam : 0,
                'yam_value' => $yam_value,
                'trade_value_usd' => $trade_value_usd,
                'trade_value' => $trade_value_usd,
                'status' => isset($poll_entry['status']) ? $poll_entry['status'] : 'completed',
                'scan_status' => isset($poll_entry['status']) ? $poll_entry['status'] : 'completed',
            );
            $user_treasury_entries[] = $formatted_entry;
        }
    }
}

// Calculate totals (use strings for BCMath precision)
$total_xp = '0';
$total_yam = '0';
$total_trade_value_usd = '0';

// Process each entry
foreach ($user_treasury_entries as $entry) {
    // Skip XP transfer entries - these are already accounted for in transactions table
    // XP transfers are stored in personal_scan with source='xp_transfer'
    if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
        continue;
    }
    
    // For seller_scan, buyer_scan, and personal_scan, only include confirmed entries
    $entry_source = isset($entry['source']) ? $entry['source'] : '';
    if (in_array($entry_source, array('seller_scan', 'buyer_scan', 'personal_scan'))) {
        $scan_status = isset($entry['scan_status']) ? $entry['scan_status'] : '';
        if ($scan_status !== 'confirmed') {
            continue; // Skip non-confirmed entries
        }
    }
    
    // Get values - NEW CONVERSION: XP is primary. Use strings and BCMath for precision.
    $xp = isset($entry['xp_units']) ? (is_string($entry['xp_units']) ? $entry['xp_units'] : (string)$entry['xp_units']) : '0';

    // Calculate trade_value_usd from XP using new conversion
    if (function_exists('bccomp') && bccomp($xp, '0', 20) === 1) {
      $trade_usd = dongtrader_xp_to_usd($xp);
    } elseif (isset($entry['trade_value_usd']) && (string)$entry['trade_value_usd'] !== '' && (function_exists('bccomp') ? bccomp((string)$entry['trade_value_usd'], '0', 20) === 1 : floatval($entry['trade_value_usd']) > 0)) {
      // Fallback: use stored trade_value_usd if XP not available
      $trade_usd = (string)$entry['trade_value_usd'];
    } else {
      // Legacy: calculate from YAM if available
      $stored_yam = isset($entry['yam_value']) ? (string)$entry['yam_value'] : '0';
      if (function_exists('bccomp') ? bccomp($stored_yam, '0', 20) === 1 : floatval($stored_yam) > 0) {
        // Convert YAM to XP (1 YAM = 10^23 XP)
        $xp_string = dongtrader_yam_to_xp($stored_yam);
        $xp = (string)$xp_string;
        $trade_usd = dongtrader_xp_to_usd($xp);
      } else {
        $trade_usd = '0';
      }
    }

    // Calculate YAM for display using new conversion (1 YAM = 1 USD = 10^23 XP)
    $yam = (function_exists('bccomp') && bccomp($xp, '0', 20) === 1) ? dongtrader_xp_to_yam($xp) : '0';

    // Add to totals using BCMath
    if (function_exists('bcadd')) {
      $total_xp = bcadd($total_xp, (string)$xp, 20);
      $total_yam = bcadd($total_yam, (string)$yam, 20);
      $total_trade_value_usd = bcadd($total_trade_value_usd, (string)$trade_usd, 20);
    } else {
      $total_xp += floatval($xp);
      $total_yam += floatval($yam);
      $total_trade_value_usd += floatval($trade_usd);
    }
}

// Calculate YAM equivalent (1 XP = 1,000,000 YAM) using BCMath when available
if (function_exists('bcmul')) {
  $yam_equivalent = bcmul((string)$total_xp, '1000000', 0);
  // Calculate max and min transfer amounts
  $max_transfer = bcmul((string)$total_xp, '0.5', 20);
  $min_transfer = '0.000001';
} else {
  $yam_equivalent = floatval($total_xp) * 1000000;
  $max_transfer = floatval($total_xp) * 0.5;
  $min_transfer = 0.000001;
}

// Constants
$laugh_end_date = '2026-08-31';
$pbtv_snapshot_date = '2026-08-11';

// Helper function to format numbers in scientific notation (e.g., "1.03 × 10²³")
function format_xp_scientific($num) {
    if ($num == 0 || $num === null) {
        return '0';
    }
    $scientific = sprintf('%.2e', $num);
    $parts = explode('e', $scientific);
    $mantissa_raw = $parts[0];
    
    // Remove only trailing zeros after decimal point, but preserve decimal places
    // e.g., "7.21" stays "7.21", "7.20" becomes "7.2", "7.00" becomes "7"
    if (strpos($mantissa_raw, '.') !== false) {
        // Has decimal point - remove trailing zeros but keep at least one digit after decimal if non-zero
        $mantissa = rtrim($mantissa_raw, '0');
        // If we removed all digits after decimal, remove the decimal point too
        if (substr($mantissa, -1) === '.') {
            $mantissa = rtrim($mantissa, '.');
        }
    } else {
        $mantissa = $mantissa_raw;
    }
    
    $exponent = isset($parts[1]) ? intval(ltrim($parts[1], '+')) : 0;
    
    // If exponent is 0, just return the integer value
    if ($exponent == 0) {
        $base_value = floatval($mantissa);
        return ($base_value == floor($base_value)) ? (string)intval($base_value) : (string)$base_value;
    }
    
    return $mantissa . ' × 10<sup>' . $exponent . '</sup>';
}

// Get currency symbol
$cs = get_woocommerce_currency_symbol();

// Pagination settings
$items_per_page = 10;
$current_page = isset($_GET['txn_page']) ? max(1, intval($_GET['txn_page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Fetch XP transactions (both sent and received) with pagination
global $wpdb;
$table_name = $wpdb->prefix . 'xp_transactions';

// Get total count
$total_transactions = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*)
    FROM {$table_name} t
    WHERE t.sender_id = %d OR t.receiver_id = %d
", $user_id, $user_id));

$total_transactions = intval($total_transactions);
$total_pages = ceil($total_transactions / $items_per_page);

// Get paginated transactions
$transactions = $wpdb->get_results($wpdb->prepare("
    SELECT 
        t.*,
        sender.display_name as sender_name,
        sender.user_email as sender_email,
        receiver.display_name as receiver_name,
        receiver.user_email as receiver_email
    FROM {$table_name} t
    LEFT JOIN {$wpdb->users} sender ON t.sender_id = sender.ID
    LEFT JOIN {$wpdb->users} receiver ON t.receiver_id = receiver.ID
    WHERE t.sender_id = %d OR t.receiver_id = %d
    ORDER BY t.transaction_date DESC
    LIMIT %d OFFSET %d
", $user_id, $user_id, $items_per_page, $offset), ARRAY_A);

if (!is_array($transactions)) {
    $transactions = array();
}

// Calculate totals from ALL transactions (not just current page)
$all_transactions = $wpdb->get_results($wpdb->prepare("
    SELECT xp_amount, sender_id, receiver_id
    FROM {$table_name}
    WHERE sender_id = %d OR receiver_id = %d
", $user_id, $user_id), ARRAY_A);

$total_xp_sent = '0';
$total_xp_received = '0';
$total_transactions_sent = 0;
$total_transactions_received = 0;

if (is_array($all_transactions)) {
  foreach ($all_transactions as $transaction) {
    $xp_amount = isset($transaction['xp_amount']) ? (string)$transaction['xp_amount'] : '0';
    $trans_sender_id = intval($transaction['sender_id']);
    $trans_receiver_id = intval($transaction['receiver_id']);

    if ($trans_sender_id === $user_id) {
      if (function_exists('bcadd')) {
        $total_xp_sent = bcadd($total_xp_sent, $xp_amount, 20);
      } else {
        $total_xp_sent += floatval($xp_amount);
      }
      $total_transactions_sent++;
    } elseif ($trans_receiver_id === $user_id) {
      if (function_exists('bcadd')) {
        $total_xp_received = bcadd($total_xp_received, $xp_amount, 20);
      } else {
        $total_xp_received += floatval($xp_amount);
      }
      $total_transactions_received++;
    }
  }
}

// Calculate available XP: (All sources + XP received) - XP transfer using BCMath
// Formula: XP Balance = (_discord_invite + _talentshow_entry + _discord_poll + seller_scan + buyer_scan + personal_scan + xp_received) - xp_transfer
if (function_exists('bcadd') && function_exists('bcsub')) {
  $available_xp = bcsub(bcadd((string)$total_xp, (string)$total_xp_received, 20), (string)$total_xp_sent, 20);
  if (function_exists('bccomp') && bccomp($available_xp, '0', 20) === -1) {
    $available_xp = '0';
  }
} else {
  $available_xp = ((float)$total_xp + (float)$total_xp_received) - (float)$total_xp_sent;
  if ($available_xp < 0) {
    $available_xp = 0;
  }
}

// Debug: Uncomment to check values
// error_log("Total XP: " . $total_xp);
// error_log("Total XP Sent: " . $total_xp_sent);
// error_log("Total XP Received: " . $total_xp_received);
// error_log("Available XP: " . $available_xp);

// Recalculate YAM equivalent and USD trade value based on available XP - NEW CONVERSION
// USD = XP / 10^23 (using new conversion function)
$available_xp_str = (string)$available_xp;
if ((function_exists('bccomp') && bccomp($available_xp_str, '0', 20) === 1) || (!function_exists('bccomp') && floatval($available_xp_str) > 0)) {
  $available_usd_trade_value_raw = dongtrader_xp_to_usd($available_xp_str);
  // Convert to float for proper formatting (function may return string for precision)
  $available_usd_trade_value = is_numeric($available_usd_trade_value_raw) ? floatval($available_usd_trade_value_raw) : 0;
  // NEW CONVERSION: YAM = XP / 10^23 (1 YAM = 1 USD = 10^23 XP)
  $available_yam_equivalent = dongtrader_xp_to_yam($available_xp_str);
} else {
  $available_usd_trade_value = 0;
  $available_yam_equivalent = '0';
}
?>

<style>
/* ============================================
   XP TRANSFERS PAGE - SPEC COMPLIANT DESIGN
   ============================================ */

.xp-transfers-container {
  background: #ffffff;
  border-radius: 20px;
  padding: 0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  font-family: "Inter", system-ui, -apple-system, sans-serif;
  color: #1f2937;
  overflow: hidden;
}

/* Header Section */
.xp-transfers-header {
  background: linear-gradient(135deg, #065f46 0%, #047857 100%);
  padding: 32px 40px;
  color: white;
  position: relative;
  overflow: hidden;
}

.xp-transfers-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -10%;
  width: 300px;
  height: 300px;
  background: rgba(255,255,255,0.05);
  border-radius: 50%;
}

.xp-transfers-header h2 {
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: white;
  position: relative;
  z-index: 1;
}

.xp-transfers-header .subtitle {
  font-size: 0.95rem;
  opacity: 0.9;
  position: relative;
  z-index: 1;
}

/* Tab Navigation */
.xp-tabs-nav {
  display: flex !important;
  flex-direction: row !important;
  align-items: center !important;
  justify-content: flex-start !important;
  flex-wrap: nowrap !important;
  padding: 12px 40px !important;
  background: #ffffff !important;
  border-bottom: 1px solid #e5e7eb !important;
  margin: 0 !important;
  list-style: none !important;
  width: 100% !important;
  box-sizing: border-box !important;
  gap: 12px !important;
}

.xp-tabs-nav .xp-tab-button {
  margin-right: 0 !important;
  margin-bottom: 0 !important;
  margin-top: 0 !important;
  margin-left: 0 !important;
}

.xp-tabs-nav .xp-tab-button:last-child {
  margin-right: 0 !important;
}

/* Discord Join Section */
.discord-join-section {
  display: flex;
  align-items: center;
  gap: 12px;
}

.discord-button {
  transition: all 0.3s ease;
}

.discord-button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(88, 101, 242, 0.3);
  opacity: 0.9;
}

.discord-send-button:hover {
  background: linear-gradient(135deg, #4752C4 0%, #3b45a3 100%) !important;
}

.discord-join-button:hover {
  background: linear-gradient(135deg, #4752C4 0%, #3b45a3 100%) !important;
}

/* Discord Join Wrapper */
.discord-join-wrapper {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
}

.discord-join-wrapper .discord-join-button {
  align-self: stretch;
  text-align: center;
  white-space: nowrap;
}

.xp-tab-button {
  background: #f9fafb !important;
  border: 2px solid #e5e7eb !important;
  padding: 12px 24px !important;
  font-size: 0.9375rem !important;
  font-weight: 600 !important;
  color: #6b7280 !important;
  cursor: pointer !important;
  border-radius: 10px !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  position: relative !important;
  display: block !important;
  flex-shrink: 0 !important;
  flex-grow: 0 !important;
  white-space: nowrap !important;
  box-sizing: border-box !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
}

.xp-tab-button:hover {
  color: #047857 !important;
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%) !important;
  border-color: #86efac !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15) !important;
}

.xp-tab-button.active {
  color: #ffffff !important;
  background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
  border-color: #047857 !important;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25), 0 2px 4px rgba(5, 150, 105, 0.15) !important;
  transform: translateY(-2px) !important;
}

/* Main Content Area */
.tab-content {
  padding: 40px;
  background: white;
  display: none !important;
  visibility: hidden;
  opacity: 0;
}

.tab-content.active {
  display: block !important;
  visibility: visible;
  opacity: 1;
}

/* LAUGH Mode Banner */
.laugh-mode-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: #ecfdf5;
  padding: 18px 24px;
  border-radius: 14px;
  margin-bottom: 32px;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
  border: 1px solid rgba(167, 243, 208, 0.2);
}

.laugh-mode-banner .status-indicator {
  height: 12px;
  width: 12px;
  border-radius: 50%;
  background: #a7f3d0;
  box-shadow: 0 0 10px rgba(167, 243, 208, 0.6);
  flex-shrink: 0;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.laugh-mode-banner strong {
  font-size: 1.05rem;
  display: block;
  margin-bottom: 4px;
}

.laugh-mode-banner p {
  margin: 0;
  font-size: 0.9rem;
  opacity: 0.95;
  line-height: 1.5;
}

/* Balance Display - Card Grid */
.balance-display {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.balance-item {
  background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 24px;
  text-align: center;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.balance-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #10b981, #059669);
  transform: scaleX(0);
  transition: transform 0.3s ease;
}

.balance-item:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15);
  border-color: #d1fae5;
}

.balance-item:hover::before {
  transform: scaleX(1);
}

.balance-item .label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 10px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.balance-item .value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #065f46;
  line-height: 1.2;
  font-family: 'Courier New', monospace;
}

/* Send XP Button */
.send-xp-button-container {
  padding: 20px 0;
  text-align: center;
}

.btn-send-xp {
  background: linear-gradient(135deg, #047857 0%, #059669 100%);
  color: white;
  border: none;
  padding: 18px 48px;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
  position: relative;
  overflow: hidden;
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.btn-send-xp::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  transform: translate(-50%, -50%);
  transition: width 0.6s, height 0.6s;
}

.btn-send-xp:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4);
}

.btn-send-xp:hover::before {
  width: 300px;
  height: 300px;
}

.btn-send-xp:active {
  transform: translateY(-1px);
}

.btn-send-xp svg {
  width: 20px;
  height: 20px;
  fill: currentColor;
}

/* Modal Styles - Hidden by default */
.xp-modal-overlay {
  display: none !important;
  visibility: hidden !important;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.85);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: 9998;
  opacity: 0 !important;
  transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), background 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none !important;
}

.xp-modal-overlay.active {
  display: block !important;
  visibility: visible !important;
  opacity: 1 !important;
  background: rgba(0, 0, 0, 0.9) !important;
  pointer-events: auto !important;
}

.xp-modal {
  display: none !important;
  visibility: hidden !important;
  position: fixed !important;
  top: 50% !important;
  left: 50% !important;
  transform: translate(-50%, -50%) scale(0.95) translateY(20px) !important;
  background: #ffffff;
  border-radius: 24px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(0, 0, 0, 0.05);
  z-index: 9999;
  max-width: 560px;
  width: 90%;
  max-height: 92vh;
  overflow: hidden;
  opacity: 0 !important;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  pointer-events: none !important;
  display: flex;
  flex-direction: column;
  margin: 0 !important;
}

.xp-modal.active {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  transform: translate(-50%, -50%) scale(1) translateY(0) !important;
  pointer-events: auto !important;
  position: fixed !important;
  top: 50% !important;
  left: 50% !important;
}

.xp-modal-header {
  padding: 28px 32px;
  border-bottom: 1px solid rgba(229, 231, 235, 0.8);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #065f46 0%, #047857 50%, #059669 100%);
  color: white;
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
}

.xp-modal-header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -20%;
  width: 200px;
  height: 200px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  pointer-events: none;
}

.xp-modal-header::after {
  content: '';
  position: absolute;
  bottom: -30%;
  left: -10%;
  width: 150px;
  height: 150px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 50%;
  pointer-events: none;
}

.xp-modal-header h3 {
  margin: 0;
  font-size: 1.625rem;
  font-weight: 700;
  color: white;
  position: relative;
  z-index: 1;
  letter-spacing: -0.02em;
}

.xp-modal-close {
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  line-height: 1;
  position: relative;
  z-index: 1;
  font-weight: 300;
}

.xp-modal-close:hover {
  background: rgba(255, 255, 255, 0.25);
  border-color: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg) scale(1.1);
}

.xp-modal-close:active {
  transform: rotate(90deg) scale(0.95);
}

.xp-modal-body {
  padding: 32px;
  overflow-y: auto;
  flex: 1;
  background: #ffffff;
}

/* Custom scrollbar for modal body */
.xp-modal-body::-webkit-scrollbar {
  width: 8px;
}

.xp-modal-body::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

.xp-modal-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.xp-modal-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Form Styles */
.send-xp-form {
  background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
  border: 2px solid #e5e7eb;
  border-radius: 20px;
  padding: 32px;
  max-width: 100%;
  margin: 0;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: all 0.3s ease;
}

.send-xp-form:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08), 0 2px 6px rgba(0, 0, 0, 0.06);
  border-color: #d1d5db;
}

.form-group {
  margin-bottom: 28px;
  position: relative;
}

.form-group:last-of-type {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-weight: 700;
  color: #111827;
  margin-bottom: 12px;
  font-size: 0.95rem;
  letter-spacing: 0.2px;
  text-transform: uppercase;
  font-size: 0.8rem;
}

.form-group label span {
  color: #dc2626;
  font-weight: 700;
  margin-left: 2px;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 16px 20px;
  border: 2px solid #e5e7eb;
  border-radius: 14px;
  font-size: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  background: #ffffff;
  font-family: inherit;
  box-sizing: border-box;
  color: #1e293b;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #059669;
  box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.12), 0 4px 12px rgba(5, 150, 105, 0.15);
  background: #ffffff;
  transform: translateY(-1px);
}

.form-group input:hover:not(:focus),
.form-group textarea:hover:not(:focus) {
  border-color: #cbd5e1;
  background: #fafbfc;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
  line-height: 1.6;
}

.form-group .help-text {
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 8px;
  font-style: italic;
}

.form-group .error-text {
  font-size: 0.8rem;
  color: #dc2626;
  margin-top: 8px;
  display: none;
  font-weight: 600;
  padding: 8px 12px;
  background: #fef2f2;
  border-radius: 8px;
  border-left: 3px solid #dc2626;
}

.form-group input.error,
.form-group textarea.error {
  border-color: #dc2626 !important;
  background: #fef2f2 !important;
  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1), 0 2px 6px rgba(220, 38, 38, 0.15) !important;
}

.form-group input.error:focus,
.form-group textarea.error:focus {
  border-color: #dc2626 !important;
  box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.15), 0 4px 12px rgba(220, 38, 38, 0.2) !important;
}

.receiver-search {
  position: relative;
}

.receiver-results {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  max-height: 280px;
  overflow-y: auto;
  z-index: 100;
  display: none;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12), 0 2px 8px rgba(0, 0, 0, 0.06);
  margin-top: 4px;
}

/* Custom scrollbar for dropdown */
.receiver-results::-webkit-scrollbar {
  width: 8px;
}

.receiver-results::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 10px;
}

.receiver-results::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #10b981, #059669);
  border-radius: 10px;
}

.receiver-results::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #059669, #047857);
}

.receiver-result-item {
  padding: 14px 18px;
  cursor: pointer;
  border-bottom: 1px solid #e2e8f0;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  background: #ffffff;
  border-left: 0 solid transparent;
}

.receiver-result-item:first-child {
  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}

.receiver-result-item:last-child {
  border-bottom: none;
  border-bottom-left-radius: 14px;
  border-bottom-right-radius: 14px;
}

.receiver-result-item:hover {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border-left: 4px solid #10b981;
  padding-left: 16px;
  transform: translateX(2px);
  box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
}

.receiver-result-item:active {
  transform: translateX(1px);
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  border-left-color: #059669;
}

.receiver-result-item .name {
  font-weight: 600;
  color: #111827;
  margin-bottom: 4px;
  transition: color 0.2s ease;
  font-size: 0.95rem;
}

.receiver-result-item:hover .name {
  color: #065f46;
}

.receiver-result-item .email {
  font-size: 0.85rem;
  color: #6b7280;
  transition: color 0.2s ease;
}

.receiver-result-item:hover .email {
  color: #047857;
}

/* Selected Receiver Card */
.selected-receiver-card {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 2px solid #86efac;
  border-radius: 16px;
  padding: 20px 24px;
  margin-bottom: 28px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.6);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.selected-receiver-card:hover {
  box-shadow: 0 8px 24px rgba(16, 185, 129, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.6);
  transform: translateY(-2px);
  border-color: #4ade80;
}

.selected-receiver-card .receiver-info {
  flex: 1;
}

.selected-receiver-card .receiver-name {
  font-weight: 700;
  color: #065f46;
  margin-bottom: 4px;
  font-size: 1rem;
}

.selected-receiver-card .receiver-email {
  font-size: 0.85rem;
  color: #6b7280;
}

.selected-receiver-card .remove-btn {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  font-size: 1.5rem;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.selected-receiver-card .remove-btn:hover {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
}

/* Transaction Filters */
.transaction-filters {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.transaction-filter-btn {
  background: #f9fafb;
  color: #6b7280;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
}

.transaction-filter-btn:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #374151;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.transaction-filter-btn.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff;
  border-color: #047857;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
}

.transaction-filter-btn.active:hover {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.transaction-row {
  display: table-row;
}

.transaction-row.hidden {
  display: none;
}

/* Pagination */
.transaction-pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-top: 24px;
  padding: 20px 0;
  flex-wrap: wrap;
}

.pagination-btn {
  background: #ffffff;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  white-space: nowrap;
}

.pagination-btn:hover:not(.disabled) {
  background: #f9fafb;
  border-color: #d1d5db;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-btn.disabled {
  background: #f9fafb;
  border-color: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  opacity: 0.5;
}

.pagination-numbers {
  display: flex;
  gap: 4px;
  align-items: center;
  flex-wrap: wrap;
}

.pagination-number {
  background: #ffffff;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  text-decoration: none;
  min-width: 40px;
  text-align: center;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.pagination-number:hover:not(.active) {
  background: #f9fafb;
  border-color: #d1d5db;
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-number.active {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: #ffffff;
  border-color: #047857;
  box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
  cursor: default;
}

.pagination-number.active:hover {
  background: linear-gradient(135deg, #047857 0%, #065f46 100%);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.pagination-info {
  text-align: center;
  margin-top: 12px;
  color: #6b7280;
  font-size: 0.875rem;
}

/* Transactions Table */
.transactions-section {
  margin-top: 32px;
}

.transactions-table-wrapper {
  overflow-x: auto;
}

.transactions-table {
  min-width: 800px;
}

.transactions-table tbody tr:hover {
  background: #f9fafb;
}

.transactions-table tbody tr:last-child {
  border-bottom: none;
}

/* Responsive Transactions Table */
@media (max-width: 768px) {
  .transactions-table {
    font-size: 0.875rem;
  }
  
  .transactions-table th,
  .transactions-table td {
    padding: 12px 8px;
  }
  
  .transactions-table th:nth-child(4),
  .transactions-table td:nth-child(4),
  .transactions-table th:nth-child(5),
  .transactions-table td:nth-child(5) {
    display: none;
  }
}

/* Amount Input Wrapper */
.amount-input-wrapper {
  position: relative;
}

.amount-input-wrapper::after {
  content: 'XP';
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  font-weight: 600;
  pointer-events: none;
}

.amount-input-wrapper input {
  padding-right: 50px;
}

.limit-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #6b7280;
  margin-top: 10px;
  padding: 10px 14px;
  background: #f9fafb;
  border-radius: 10px;
  border: 1px solid #e5e7eb;
  font-weight: 500;
}

.limit-info span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.limit-info span::before {
  content: '•';
  color: #059669;
  font-weight: 700;
  font-size: 1.2rem;
  line-height: 0;
}

/* Conversion Display */
.conversion-display {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 2px solid #86efac;
  border-radius: 16px;
  padding: 20px 24px;
  margin-top: 16px;
  font-size: 0.9rem;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.8);
  transition: all 0.3s ease;
}

.conversion-display:hover {
  border-color: #4ade80;
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.8);
  transform: translateY(-2px);
}

.conversion-display .conversion-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid #d1fae5;
}

.conversion-display .conversion-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.conversion-label {
  color: #065f46;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.conversion-value {
  color: #047857;
  font-weight: 700;
  font-size: 1.1rem;
  font-family: 'Courier New', monospace;
}

/* Transfer Summary */
.transfer-summary {
  background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 2px solid #86efac;
  border-radius: 16px;
  padding: 24px;
  margin-top: 24px;
  display: none;
  box-shadow: 0 4px 16px rgba(16, 185, 129, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.5);
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.transfer-summary h4 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #065f46;
  margin-bottom: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.transfer-summary h4::before {
  content: '✓';
  background: #10b981;
  color: white;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #d1fae5;
}

.summary-row:last-child {
  border-bottom: none;
  font-weight: 600;
  color: #065f46;
  margin-top: 5px;
  padding-top: 12px;
}

.summary-label {
  color: #6b7280;
}

.summary-value {
  color: #111827;
  font-weight: 600;
}

/* Buttons */
.btn-primary {
  background: linear-gradient(135deg, #047857 0%, #059669 50%, #10b981 100%);
  color: white;
  border: none;
  padding: 18px 36px;
  border-radius: 16px;
  font-weight: 700;
  font-size: 1.05rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  width: 100%;
  margin-top: 32px;
  box-shadow: 0 6px 20px rgba(5, 150, 105, 0.4), 0 2px 6px rgba(5, 150, 105, 0.25);
  position: relative;
  overflow: hidden;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  font-size: 0.9rem;
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  width: 0;
  height: 0;
  border-radius: 50%;
  background: rgba(255,255,255,0.3);
  transform: translate(-50%, -50%);
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1), height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(5, 150, 105, 0.45), 0 4px 8px rgba(5, 150, 105, 0.3);
  background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35), 0 2px 4px rgba(5, 150, 105, 0.2);
}

.btn-primary:disabled {
  background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
  cursor: not-allowed;
  opacity: 0.6;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover::before {
  width: 300px;
  height: 300px;
}

.btn-cancel {
  background: #ffffff;
  color: #475569;
  border: 2px solid #e2e8f0;
  padding: 16px 32px;
  border-radius: 14px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  width: 100%;
  margin-top: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.btn-cancel:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #334155;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
  transform: translateY(-1px);
}

.btn-cancel:active {
  transform: translateY(0);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* Responsive Design */
@media (max-width: 768px) {
  .xp-transfers-header {
    padding: 24px 20px;
  }
  
  .xp-transfers-header h2 {
    font-size: 1.5rem;
  }
  
  .xp-tabs-nav {
    padding: 8px 20px !important;
    display: flex !important;
    flex-direction: row !important;
    gap: 8px !important;
  }
  
  .xp-tabs-nav .xp-tab-button {
    margin-right: 0 !important;
  }
  
  .xp-tabs-nav .xp-tab-button:last-child {
    margin-right: 0 !important;
  }
  
  .xp-tab-button {
    padding: 10px 16px !important;
    font-size: 0.875rem !important;
  }
  
  .tab-content {
    padding: 24px 20px;
  }
  
  .balance-display {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}
/* --- BALANCE DISPLAY --- */
.balance-display {
  display: flex;
  justify-content: space-between;
    align-items: stretch;
    gap: 1rem;
    flex-wrap: wrap;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.05);
    margin-bottom: 1.5rem;
}

/* --- INDIVIDUAL BALANCE CARD --- */
.balance-item {
    flex: 1 1 30%;
    min-width: 180px;
    background: #ffffff;
    border-radius: 12px;
    padding: 16px 18px;
    text-align: center;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    transition: all 0.25s ease;
    border: 1px solid #e2e8f0;
}

.balance-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 18px rgba(37, 99, 235, 0.15);
    border-color: #cbd5e1;
}

/* --- LABEL TEXT --- */
.balance-item .label {
    font-size: 14px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

/* --- VALUE TEXT --- */
.balance-item .value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

/* --- ICON EFFECT (OPTIONAL, for more style) --- */
.balance-item::before {
    content: '';
    display: block;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #2563eb, #4f46e5);
    border-radius: 10px 10px 0 0;
    margin-bottom: 10px;
}

/* --- RESPONSIVE DESIGN --- */
@media (max-width: 768px) {
    .balance-display {
        flex-direction: column;
    }
    .balance-item {
        width: 100%;
    }
}

</style>

<div class="xp-transfers-container">
    <!-- Header Section -->
    <div class="xp-transfers-header">
        <h2><?php esc_html_e('XP Transfers', 'cpm-dongtrader'); ?></h2>
        <div class="subtitle"><?php esc_html_e('Send XP to other users', 'cpm-dongtrader'); ?></div>
    </div>
    
    <!-- Tab Navigation -->
    <div class="xp-tabs-nav" style="display:flex; gap:10px;">
        <button type="button" class="xp-tab-button active" data-tab="transactions">
            <?php esc_html_e('Transactions', 'cpm-dongtrader'); ?>
        </button>
        <?php if ($is_discord_verified): ?>
            <button type="button" class="xp-tab-button" data-tab="send-xp" id="open-send-xp-tab">
                <?php esc_html_e('Send XP', 'cpm-dongtrader'); ?>
            </button>
        <?php else: ?>
            <a href="https://discord.gg/g5jreAPbra" target="_blank" class="discord-join-button" style="text-decoration: none; background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%); color: white; border: 2px solid #4752C4; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 24px; line-height: 1.4; border-radius: 8px; font-size: 0.9375rem; font-weight: 600;">
                <span style="font-weight: 600;">
                    <?php esc_html_e('Join Gracebook Discord', 'cpm-dongtrader'); ?>
                </span>
                <span style="font-size: 0.7rem; font-weight: 400; opacity: 0.9; text-align: center;">
                    <?php esc_html_e('Join to Gracebook server and get verified by discord (earn 5 × 10⁶)', 'cpm-dongtrader'); ?>
                </span>
            </a>
        <?php endif; ?>
    </div>

    <!-- Transactions Tab (Default) -->
    <div class="tab-content active" id="transactions-tab">
        <!-- LAUGH Mode Banner -->
        <div class="laugh-mode-banner">
            <span class="status-indicator"></span>
            <div>
                <strong>LAUGH Mode Active</strong>
                <p>
                    XP transfers are trade credits only. No money moves until <?php echo esc_html($laugh_end_date); ?>.
                </p>
            </div>
    </div>

        <!-- Balance Display -->
        <div class="balance-display" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #bfdbfe; border-radius: 12px; padding: 16px;">
                <div style="font-size: 0.75rem; font-weight: 600; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                    <?php esc_html_e('Available XP', 'cpm-dongtrader'); ?>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #2563eb; font-family: 'Courier New', monospace;">
                    <?php 
                    // Display available XP in scientific notation (matches wallet page format)
                    if ($available_xp > 0) {
                        $scientific = sprintf('%.2e', $available_xp);
                        $parts = explode('e', $scientific);
                        $mantissa_raw = $parts[0];
                        
                        // Remove only trailing zeros after decimal point, but preserve decimal places
                        if (strpos($mantissa_raw, '.') !== false) {
                            $mantissa = rtrim($mantissa_raw, '0');
                            if (substr($mantissa, -1) === '.') {
                                $mantissa = rtrim($mantissa, '.');
                            }
                        } else {
                            $mantissa = $mantissa_raw;
                        }
                        
                        $exponent = isset($parts[1]) ? intval(ltrim($parts[1], '+')) : 0;
                        if ($exponent == 0) {
                            $base_value = floatval($mantissa);
                            echo ($base_value == floor($base_value)) ? (int)$base_value : $base_value;
                        } else {
                        echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                        }
                    } else {
                        echo '0';
                    }
                    ?> XP
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #bbf7d0; border-radius: 12px; padding: 16px;">
                <div style="font-size: 0.75rem; font-weight: 600; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                    <?php esc_html_e('YAM Equivalent', 'cpm-dongtrader'); ?>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #059669; font-family: 'Courier New', monospace; line-height: 1.4;">
                    <?php 
                    // Display YAM in scientific notation (matches wallet page format exactly)
                    if ($available_yam_equivalent > 0 && is_numeric($available_yam_equivalent)) {
                        $yam_scientific = sprintf('%.2e', (float)$available_yam_equivalent);
                        $parts = explode('e', $yam_scientific);
                        if (count($parts) == 2) {
                            $mantissa_raw = $parts[0];
                            
                            // Remove only trailing zeros after decimal point, but preserve decimal places
                            if (strpos($mantissa_raw, '.') !== false) {
                                $mantissa = rtrim($mantissa_raw, '0');
                                if (substr($mantissa, -1) === '.') {
                                    $mantissa = rtrim($mantissa, '.');
                                }
                            } else {
                                $mantissa = $mantissa_raw;
                            }
                            
                            $exponent = intval($parts[1]);
                            
                            // If exponent is 0, just display the integer
                            if ($exponent == 0) {
                                $base_value = floatval($mantissa);
                                echo ($base_value == floor($base_value)) ? esc_html((int)$base_value) : esc_html($base_value);
                            } else {
                                echo esc_html($mantissa) . ' × 10<sup>' . esc_html($exponent) . '</sup>';
                            }
                        } else {
                            echo esc_html(number_format($available_yam_equivalent, 18));
                        }
                    } else {
                        echo '0';
                    }
                    ?>
                </div>
            </div>
            
            <div style="background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); border: 2px solid #fde047; border-radius: 12px; padding: 16px;">
                <div style="font-size: 0.75rem; font-weight: 600; color: #854d0e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                    <?php esc_html_e('USD Trade Value', 'cpm-dongtrader'); ?>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #ca8a04; font-family: 'Courier New', monospace;">
                    <?php 
                    // USD Trade Value calculated using conversion rate: 1 USD = 10^23 XP
                    // Uses dongtrader_xp_to_usd() function with string precision (matches wallet page)
                    // Ensure value is numeric before formatting
                    $usd_value = is_numeric($available_usd_trade_value) ? floatval($available_usd_trade_value) : 0;
                    echo esc_html($cs) . number_format($usd_value, 2); 
                    ?>
                </div>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="transactions-section">
            <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; color: #111827;">
                <?php esc_html_e('Transfer History', 'cpm-dongtrader'); ?>
            </h3>
            
            <!-- Transaction Summary -->
            <?php if (!empty($transactions)): ?>
                <div class="transaction-summary" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <div style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); border: 2px solid #fecaca; border-radius: 12px; padding: 16px;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            <?php esc_html_e('Total XP Sent', 'cpm-dongtrader'); ?>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626; font-family: 'Courier New', monospace;">
                            <?php echo format_xp_scientific($total_xp_sent); ?> XP
                        </div>
                        <div style="font-size: 0.75rem; color: #991b1b; margin-top: 4px;">
                            <?php echo sprintf(_n('%d transaction', '%d transactions', $total_transactions_sent, 'cpm-dongtrader'), $total_transactions_sent); ?>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #bbf7d0; border-radius: 12px; padding: 16px;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            <?php esc_html_e('Total XP Received', 'cpm-dongtrader'); ?>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #059669; font-family: 'Courier New', monospace;">
                            <?php echo format_xp_scientific($total_xp_received); ?> XP
                        </div>
                        <div style="font-size: 0.75rem; color: #065f46; margin-top: 4px;">
                            <?php echo sprintf(_n('%d transaction', '%d transactions', $total_transactions_received, 'cpm-dongtrader'), $total_transactions_received); ?>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                        <div style="font-size: 0.75rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                            <?php esc_html_e('Net XP', 'cpm-dongtrader'); ?>
                        </div>
                        <div style="font-size: 1.5rem; font-weight: 700; color: #334155; font-family: 'Courier New', monospace;">
                            <?php 
                            $net_xp = $total_xp_received - $total_xp_sent;
                            $sign = ($net_xp >= 0 ? '+' : '');
                            $abs_net_xp = abs($net_xp);
                            echo $sign . format_xp_scientific($abs_net_xp); 
                            ?> XP
                        </div>
                        <div style="font-size: 0.75rem; color: #475569; margin-top: 4px;">
                            <?php esc_html_e('Received - Sent', 'cpm-dongtrader'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (empty($transactions)): ?>
                <div class="transactions-placeholder" style="text-align: center; padding: 60px 20px; color: #6b7280; background: #f9fafb; border-radius: 12px; border: 2px dashed #e5e7eb;">
                    <p style="font-size: 1rem; margin: 0;">
                        <?php esc_html_e('No transactions yet. Start by sending XP to another user.', 'cpm-dongtrader'); ?>
                    </p>
                </div>
            <?php else: ?>
                <!-- Transaction Filters -->
                <div class="transaction-filters" style="display: flex; gap: 12px; margin-bottom: 20px; align-items: center;">
                    <span style="font-size: 0.875rem; font-weight: 600; color: #374151; margin-right: 8px;"><?php esc_html_e('Filter:', 'cpm-dongtrader'); ?></span>
                    <button type="button" class="transaction-filter-btn active" data-filter="all" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); color: #ffffff; border: 2px solid #047857; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
            <?php esc_html_e('All', 'cpm-dongtrader'); ?>
        </button>
                    <button type="button" class="transaction-filter-btn" data-filter="sent" style="background: #f9fafb; color: #6b7280; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
            <?php esc_html_e('Sent', 'cpm-dongtrader'); ?>
        </button>
                    <button type="button" class="transaction-filter-btn" data-filter="received" style="background: #f9fafb; color: #6b7280; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">
            <?php esc_html_e('Received', 'cpm-dongtrader'); ?>
        </button>
    </div>

                <div class="transactions-table-wrapper" style="background: #ffffff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden;">
                    <table class="transactions-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                            <tr style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('Type', 'cpm-dongtrader'); ?></th>
                                <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('User', 'cpm-dongtrader'); ?></th>
                                <th style="padding: 16px; text-align: right; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?></th>
                                <th style="padding: 16px; text-align: right; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('YAM', 'cpm-dongtrader'); ?></th>
                                <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('Memo', 'cpm-dongtrader'); ?></th>
                                <th style="padding: 16px; text-align: left; font-weight: 600; color: #374151; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                </tr>
            </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): 
                                $is_sent = intval($transaction['sender_id']) === $user_id;
                                $other_user_name = $is_sent ? $transaction['receiver_name'] : $transaction['sender_name'];
                                $other_user_email = $is_sent ? $transaction['receiver_email'] : $transaction['sender_email'];
                                $xp_amount = floatval($transaction['xp_amount']);
                                $yam_equivalent = floatval($transaction['yam_equivalent']);
                                $memo = !empty($transaction['memo']) ? esc_html($transaction['memo']) : '—';
                                $transaction_date = !empty($transaction['transaction_date']) ? strtotime($transaction['transaction_date']) : time();
                                $transaction_type = $is_sent ? 'sent' : 'received';
                            ?>
                                <tr class="transaction-row" data-transaction-type="<?php echo esc_attr($transaction_type); ?>" style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s ease;">
                                    <td style="padding: 16px;">
                                        <span style="display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; <?php echo $is_sent ? 'background: #fef2f2; color: #dc2626;' : 'background: #f0fdf4; color: #059669;'; ?>">
                                            <?php echo $is_sent ? esc_html__('Sent', 'cpm-dongtrader') : esc_html__('Received', 'cpm-dongtrader'); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px;">
                                        <div style="font-weight: 600; color: #111827; margin-bottom: 2px;"><?php echo esc_html($other_user_name); ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280;"><?php echo esc_html($other_user_email); ?></div>
                                    </td>
                                    <td style="padding: 16px; text-align: right; font-family: 'Courier New', monospace; font-weight: 600; <?php echo $is_sent ? 'color: #dc2626;' : 'color: #059669;'; ?>">
                                        <?php echo $is_sent ? '-' : '+'; ?><?php echo format_xp_scientific($xp_amount); ?> XP
                                    </td>
                                    <td style="padding: 16px; text-align: right; font-family: 'Courier New', monospace; color: #6b7280; font-size: 0.875rem;">
                                        <?php echo number_format($yam_equivalent, 0); ?>
                                    </td>
                                    <td style="padding: 16px; color: #6b7280; font-size: 0.875rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($memo); ?>">
                                        <?php echo $memo; ?>
                                    </td>
                                    <td style="padding: 16px; color: #6b7280; font-size: 0.875rem;">
                                        <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $transaction_date); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="transaction-pagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 20px 0;">
                <?php
                // Previous button
                if ($current_page > 1):
                    $prev_url = add_query_arg('txn_page', $current_page - 1);
                ?>
                    <a href="<?php echo esc_url($prev_url); ?>" class="pagination-btn pagination-prev" style="background: #ffffff; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; color: #374151; text-decoration: none; transition: all 0.3s ease; cursor: pointer;">
                        <?php esc_html_e('← Previous', 'cpm-dongtrader'); ?>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-prev disabled" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; color: #9ca3af; cursor: not-allowed; opacity: 0.5;">
                        <?php esc_html_e('← Previous', 'cpm-dongtrader'); ?>
            </span>
                <?php endif; ?>
                
                <!-- Page numbers -->
                <div class="pagination-numbers" style="display: flex; gap: 4px; align-items: center;">
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);
                    
                    // Show first page if not in range
                    if ($start_page > 1):
                        $first_url = add_query_arg('txn_page', 1);
                    ?>
                        <a href="<?php echo esc_url($first_url); ?>" class="pagination-number" style="background: #ffffff; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; font-weight: 600; color: #374151; text-decoration: none; min-width: 40px; text-align: center; transition: all 0.3s ease;">
                            1
                        </a>
                        <?php if ($start_page > 2): ?>
                            <span style="color: #9ca3af; padding: 0 4px;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): 
                        $page_url = add_query_arg('txn_page', $i);
                        $is_active = ($i == $current_page);
                    ?>
                        <a href="<?php echo esc_url($page_url); ?>" class="pagination-number <?php echo $is_active ? 'active' : ''; ?>" style="background: <?php echo $is_active ? 'linear-gradient(135deg, #059669 0%, #047857 100%)' : '#ffffff'; ?>; border: 2px solid <?php echo $is_active ? '#047857' : '#e5e7eb'; ?>; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; font-weight: 600; color: <?php echo $is_active ? '#ffffff' : '#374151'; ?>; text-decoration: none; min-width: 40px; text-align: center; transition: all 0.3s ease; box-shadow: <?php echo $is_active ? '0 2px 8px rgba(5, 150, 105, 0.25)' : 'none'; ?>;">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <!-- Show last page if not in range -->
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span style="color: #9ca3af; padding: 0 4px;">...</span>
                        <?php endif; ?>
                        <?php
                        $last_url = add_query_arg('txn_page', $total_pages);
                        ?>
                        <a href="<?php echo esc_url($last_url); ?>" class="pagination-number" style="background: #ffffff; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 12px; font-size: 0.875rem; font-weight: 600; color: #374151; text-decoration: none; min-width: 40px; text-align: center; transition: all 0.3s ease;">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>
        </div>
                
                <?php
                // Next button
                if ($current_page < $total_pages):
                    $next_url = add_query_arg('txn_page', $current_page + 1);
                ?>
                    <a href="<?php echo esc_url($next_url); ?>" class="pagination-btn pagination-next" style="background: #ffffff; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; color: #374151; text-decoration: none; transition: all 0.3s ease; cursor: pointer;">
                        <?php esc_html_e('Next →', 'cpm-dongtrader'); ?>
                    </a>
                <?php else: ?>
                    <span class="pagination-btn pagination-next disabled" style="background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; padding: 8px 16px; font-size: 0.875rem; font-weight: 600; color: #9ca3af; cursor: not-allowed; opacity: 0.5;">
                        <?php esc_html_e('Next →', 'cpm-dongtrader'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Pagination Info -->
            <div class="pagination-info" style="text-align: center; margin-top: 12px; color: #6b7280; font-size: 0.875rem;">
                <?php 
                $start_item = $offset + 1;
                $end_item = min($offset + $items_per_page, $total_transactions);
                echo sprintf(
                    esc_html__('Showing %d-%d of %d transactions', 'cpm-dongtrader'),
                    $start_item,
                    $end_item,
                    $total_transactions
                );
                ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
        </div>
</div>

    <!-- Send XP Tab (Hidden by default) -->
    <div class="tab-content" id="send-xp-tab" style="display: none !important;">
        <!-- LAUGH Mode Banner -->
        <div class="laugh-mode-banner" style="margin-bottom: 24px;">
            <span class="status-indicator"></span>
            <div>
                <strong style="font-size: 14px;">Trade Credits Only</strong>
                <p style="margin: 2px 0 0 0; font-size: 12px; opacity: 0.95;">
                    No money moves until <?php echo esc_html($laugh_end_date); ?>
                </p>
            </div>
        </div>

        <!-- Send XP Form -->
        <form id="send-xp-form" class="send-xp-form">
            <?php wp_nonce_field('send_xp_transfer', 'xp_transfer_nonce'); ?>
            
            <!-- Receiver Search -->
            <div class="form-group">
                <label for="receiver_search"><?php esc_html_e('Send To', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div class="receiver-search">
                    <input 
                        type="text" 
                        id="receiver_search" 
                        name="receiver_search" 
                        placeholder="<?php esc_attr_e('Search by email, username, or FonePay ID', 'cpm-dongtrader'); ?>"
                        autocomplete="off"
                        value=""
                    />
                    <div class="receiver-results" id="receiver_results" style="display: none;"></div>
                </div>
                <input type="hidden" id="receiver_id" name="receiver_id" value="" />
                <div class="help-text"><?php esc_html_e('Start typing to search for a user', 'cpm-dongtrader'); ?></div>
                <div class="error-text" id="receiver_error" style="display: none;"></div>
            </div>

            <!-- Selected Receiver Display -->
            <div id="selected_receiver" class="selected-receiver-card" style="display: none;">
                <div class="receiver-info">
                    <div class="receiver-name" id="receiver_name"></div>
                    <div class="receiver-email" id="receiver_email"></div>
                </div>
                <button type="button" class="remove-btn" onclick="clearReceiver()" title="<?php esc_attr_e('Remove receiver', 'cpm-dongtrader'); ?>">×</button>
            </div>

            <!-- XP Amount -->
            <div class="form-group">
                <label for="xp_amount"><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?> <span style="color: #dc2626;">*</span></label>
                <div class="amount-input-wrapper">
                    <input 
                        type="number" 
                        id="xp_amount" 
                        name="xp_amount" 
                        step="0.000001" 
                        min="<?php echo esc_attr($min_transfer); ?>" 
                        max="<?php echo esc_attr($max_transfer); ?>"
                        placeholder="0.000000"
                        value=""
                        required
                    />
                </div>
                <div class="limit-info">
                    <span>Min: <?php echo number_format($min_transfer, 6); ?> XP</span>
                    <span>Max: <?php 
                        if ($max_transfer > 0) {
                            $scientific = sprintf('%.2e', $max_transfer);
                            $parts = explode('e', $scientific);
                            $mantissa_raw = $parts[0];
                            
                            // Remove only trailing zeros after decimal point, but preserve decimal places
                            if (strpos($mantissa_raw, '.') !== false) {
                                $mantissa = rtrim($mantissa_raw, '0');
                                if (substr($mantissa, -1) === '.') {
                                    $mantissa = rtrim($mantissa, '.');
                                }
                            } else {
                                $mantissa = $mantissa_raw;
                            }
                            
                            $exponent = isset($parts[1]) ? intval(ltrim($parts[1], '+')) : 0;
                            if ($exponent == 0) {
                                $base_value = floatval($mantissa);
                                echo ($base_value == floor($base_value)) ? (int)$base_value : $base_value;
                            } else {
                                echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                            }
                        } else {
                            echo '0';
                        }
                    ?> XP (50% of balance)</span>
                </div>
                <div class="error-text" id="amount_error" style="display: none;"></div>
                
                <!-- Conversion Display (YAM & USD) -->
                <div class="conversion-display" id="conversion_display" style="display: none;">
                    <div class="conversion-item">
                        <span class="conversion-label"><?php esc_html_e('YAM Equivalent:', 'cpm-dongtrader'); ?></span>
                        <span class="conversion-value" id="yam_equiv">0</span>
                    </div>
                    <div class="conversion-item">
                        <span class="conversion-label"><?php esc_html_e('USD Trade Value:', 'cpm-dongtrader'); ?></span>
                        <span class="conversion-value"><?php echo $cs; ?><span id="usd_value">0.00</span></span>
                    </div>
                </div>
            </div>

            <!-- Memo/Note -->
            <div class="form-group">
                <label for="memo"><?php esc_html_e('Memo (Optional)', 'cpm-dongtrader'); ?></label>
                <textarea 
                    id="memo" 
                    name="memo" 
                    placeholder="<?php esc_attr_e('Add a note about this transfer...', 'cpm-dongtrader'); ?>"
                    maxlength="500"
                ></textarea>
                <div class="help-text">
                    <span id="char_count">0</span> / 500 <?php esc_html_e('characters', 'cpm-dongtrader'); ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <button type="button" class="btn-primary" id="submit_btn" disabled>
                <?php esc_html_e('Send XP', 'cpm-dongtrader'); ?>
            </button>
        </form>

        <!-- Transfer Summary (Shown after form submission) -->
        <div class="transfer-summary" id="transfer_summary" style="display: none;">
            <h4><?php esc_html_e('Transfer Summary', 'cpm-dongtrader'); ?></h4>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('Receiver:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_receiver">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('XP Amount:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_amount">0.000000</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('YAM Equivalent:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_yam">0</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><?php esc_html_e('Your New Balance:', 'cpm-dongtrader'); ?></span>
                <span class="summary-value" id="summary_new_balance"><?php 
                    if ($total_xp > 0) {
                        $scientific = sprintf('%.2e', $total_xp);
                        $parts = explode('e', $scientific);
                        $mantissa_raw = $parts[0];
                        
                        // Remove only trailing zeros after decimal point, but preserve decimal places
                        if (strpos($mantissa_raw, '.') !== false) {
                            $mantissa = rtrim($mantissa_raw, '0');
                            if (substr($mantissa, -1) === '.') {
                                $mantissa = rtrim($mantissa, '.');
                            }
                        } else {
                            $mantissa = $mantissa_raw;
                        }
                        
                        $exponent = isset($parts[1]) ? intval(ltrim($parts[1], '+')) : 0;
                        if ($exponent == 0) {
                            $base_value = floatval($mantissa);
                            echo ($base_value == floor($base_value)) ? (int)$base_value : $base_value;
                        } else {
                            echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                        }
                    } else {
                        echo '0';
                    }
                ?></span>
            </div>
            
            <!-- Confirm Button -->
            <button type="button" class="btn-primary" id="confirm_btn" style="margin-top: 24px;">
                <?php esc_html_e('Confirm & Send XP', 'cpm-dongtrader'); ?>
            </button>
            <button type="button" class="btn-cancel" id="cancel_confirm_btn" style="margin-top: 12px;">
                <?php esc_html_e('Cancel', 'cpm-dongtrader'); ?>
            </button>
        </div>
    </div>
</div>

