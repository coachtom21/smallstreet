<?php
/**
 * Redemption Page Template
 * Allows users to view available XP and submit redemption requests
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to view redemptions.', 'cpm-dongtrader') . '</p>';
    return;
}

// Define switchRedemptionTab function immediately in the head/early in the page
?>
<script type="text/javascript">
// Tab switching function - simple and reliable
window.switchRedemptionTab = function(tabName) {
    // Hide all tab contents
    var allContents = document.querySelectorAll('.redemption-tab-content');
    var i;
    for (i = 0; i < allContents.length; i++) {
        allContents[i].style.display = 'none';
        allContents[i].style.visibility = 'hidden';
        allContents[i].classList.remove('active');
    }
    
    // Remove active class from all buttons and reset their styles
    var allButtons = document.querySelectorAll('.redemption-tab-button');
    var inactiveStyle = 'background: #f9fafb; border: 2px solid #e5e7eb; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #6b7280; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);';
    var activeStyle = 'background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: 2px solid #6d28d9; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #ffffff; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15); transform: translateY(-2px);';
    
    for (i = 0; i < allButtons.length; i++) {
        allButtons[i].classList.remove('active');
        var btnTab = allButtons[i].getAttribute('data-tab');
        if (btnTab !== tabName) {
            allButtons[i].setAttribute('style', inactiveStyle);
        }
    }
    
    // Show selected tab content
    var selectedContent = document.getElementById(tabName + '-tab');
    if (selectedContent) {
        selectedContent.style.display = 'block';
        selectedContent.style.visibility = 'visible';
        selectedContent.classList.add('active');
    }
    
    // Activate selected tab button
    var selectedButton = document.querySelector('.redemption-tab-button[data-tab="' + tabName + '"]');
    if (selectedButton) {
        selectedButton.classList.add('active');
        selectedButton.setAttribute('style', activeStyle);
    }
};
</script>
<?php

$user_id = get_current_user_id();
$user = wp_get_current_user();
$user_name = $user->display_name ? $user->display_name : $user->user_login;

// Get currency symbol
$cs = get_woocommerce_currency_symbol();

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
$discord_invite_raw = get_user_meta($user_id, '_discord_invite', false);
if (!empty($discord_invite_raw) && is_array($discord_invite_raw)) {
    foreach ($discord_invite_raw as $discord_entry_raw) {
        $discord_entry = maybe_unserialize($discord_entry_raw);
        
        if (is_string($discord_entry)) {
            $decoded = json_decode($discord_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $discord_entry = $decoded;
            } else {
                continue;
            }
        }
        
        if (is_array($discord_entry) && !empty($discord_entry)) {
            if (isset($discord_entry['xp_units'])) {
                $xp_units = is_string($discord_entry['xp_units']) ? floatval($discord_entry['xp_units']) : floatval($discord_entry['xp_units']);
            } elseif (isset($discord_entry['xp_awarded'])) {
                $xp_awarded_yam = intval($discord_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam / 1000000;
            } else {
                $xp_units = 0;
            }
            
            $trade_value_usd = dongtrader_xp_to_usd($xp_units);
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
$talentshow_entry_raw = get_user_meta($user_id, '_talentshow_entry', false);
if (!empty($talentshow_entry_raw) && is_array($talentshow_entry_raw)) {
    foreach ($talentshow_entry_raw as $talent_entry_raw) {
        $talent_entry = maybe_unserialize($talent_entry_raw);
        if (is_string($talent_entry)) {
            $decoded = json_decode($talent_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $talent_entry = $decoded;
            } else {
                continue;
            }
        }
        if (is_array($talent_entry) && !empty($talent_entry)) {
            $xp_units = isset($talent_entry['xp_units']) ? floatval($talent_entry['xp_units']) : 0;
            if (isset($talent_entry['xp_awarded'])) {
                $xp_awarded_yam = intval($talent_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam / 1000000;
            }
            $trade_value_usd = dongtrader_xp_to_usd($xp_units);
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
$discord_poll_raw = get_user_meta($user_id, '_discord_poll', false);
if (!empty($discord_poll_raw) && is_array($discord_poll_raw)) {
    foreach ($discord_poll_raw as $poll_entry_raw) {
        $poll_entry = maybe_unserialize($poll_entry_raw);
        if (is_string($poll_entry)) {
            $decoded = json_decode($poll_entry, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $poll_entry = $decoded;
            } else {
                continue;
            }
        }
        if (is_array($poll_entry) && !empty($poll_entry)) {
            $xp_units = isset($poll_entry['xp_units']) ? floatval($poll_entry['xp_units']) : 0;
            if (isset($poll_entry['xp_awarded'])) {
                $xp_awarded_yam = intval($poll_entry['xp_awarded']);
                $xp_units = $xp_awarded_yam / 1000000;
            }
            $trade_value_usd = dongtrader_xp_to_usd($xp_units);
            $yam_value = dongtrader_xp_to_yam($xp_units);
            $formatted_entry = array(
                'source' => 'discord_poll',
                'role' => 'Discord Poll',
                'timestamp' => isset($poll_entry['poll_date']) ? $poll_entry['poll_date'] : (isset($poll_entry['created_at']) ? $poll_entry['created_at'] : current_time('mysql')),
                'proof_id' => 'poll_' . (isset($poll_entry['poll_id']) ? $poll_entry['poll_id'] : 'discord'),
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

// Calculate totals
$total_xp = 0;
$total_yam = 0;
$total_trade_value_usd = 0;

// Process each entry
foreach ($user_treasury_entries as $entry) {
    // Skip XP transfer entries
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
    
    // Get values
    $xp = isset($entry['xp_units']) ? (is_string($entry['xp_units']) ? floatval($entry['xp_units']) : floatval($entry['xp_units'])) : 0;
    
    // Calculate trade_value_usd from XP
    if ($xp > 0) {
        $trade_usd = dongtrader_xp_to_usd($xp);
    } elseif (isset($entry['trade_value_usd']) && floatval($entry['trade_value_usd']) > 0) {
        $trade_usd = floatval($entry['trade_value_usd']);
    } else {
        $stored_yam = isset($entry['yam_value']) ? floatval($entry['yam_value']) : 0;
        if ($stored_yam > 0) {
            $xp_string = dongtrader_yam_to_xp($stored_yam);
            $xp = floatval($xp_string);
            $trade_usd = dongtrader_xp_to_usd($xp);
        } else {
            $trade_usd = 0;
        }
    }
    
    // Calculate YAM for display
    $yam = $xp > 0 ? dongtrader_xp_to_yam($xp) : 0;
    
    // Add to totals
    $total_xp += $xp;
    $total_yam += $yam;
    $total_trade_value_usd += $trade_usd;
}

// Get XP transactions to calculate sent/received
global $wpdb;
$xp_transactions_table = $wpdb->prefix . 'xp_transactions';

// Calculate totals from transactions
$all_transactions = $wpdb->get_results($wpdb->prepare("
    SELECT xp_amount, sender_id, receiver_id
    FROM {$xp_transactions_table}
    WHERE sender_id = %d OR receiver_id = %d
", $user_id, $user_id), ARRAY_A);

$total_xp_sent = 0;
$total_xp_received = 0;

if (is_array($all_transactions)) {
    foreach ($all_transactions as $transaction) {
        $xp_amount = floatval($transaction['xp_amount']);
        $trans_sender_id = intval($transaction['sender_id']);
        $trans_receiver_id = intval($transaction['receiver_id']);
        
        if ($trans_sender_id === $user_id) {
            $total_xp_sent += $xp_amount;
        } elseif ($trans_receiver_id === $user_id) {
            $total_xp_received += $xp_amount;
        }
    }
}

// Calculate available XP: (All sources + XP received) - XP sent
$available_xp = ($total_xp + $total_xp_received) - $total_xp_sent;
if ($available_xp < 0) {
    $available_xp = 0;
}

// Calculate USD and YAM equivalent
$available_xp_str = (string)$available_xp;
$available_usd_trade_value_raw = $available_xp > 0 ? dongtrader_xp_to_usd($available_xp_str) : 0;
$available_usd_trade_value = is_numeric($available_usd_trade_value_raw) ? floatval($available_usd_trade_value_raw) : 0;
$available_yam_equivalent = $available_xp > 0 ? dongtrader_xp_to_yam($available_xp_str) : 0;

// Get user's redemption history
global $wpdb;
$table_name = $wpdb->prefix . 'dongtrader_redemptions';

// Check if table exists, if not create it
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
if (!$table_exists) {
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
        maturity_date datetime NULL,
        oldest_delivery_date datetime NULL,
        youngest_delivery_date datetime NULL,
        maturity_weeks int(11) DEFAULT 10,
        within_redemption_window tinyint(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY redem_date (redem_date),
        KEY maturity_date (maturity_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Get redemption history for current user
$redemptions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE user_id = %d ORDER BY redem_date DESC",
    $user_id
), ARRAY_A);

if (!is_array($redemptions)) {
    $redemptions = array();
}

// Helper function to format numbers in scientific notation
function format_xp_scientific_redemption($num) {
    if ($num == 0 || $num === null) {
        return '0';
    }
    $scientific = sprintf('%.2e', $num);
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
    
    // If exponent is 0, just return the integer value
    if ($exponent == 0) {
        $base_value = floatval($mantissa);
        return ($base_value == floor($base_value)) ? (string)intval($base_value) : (string)$base_value;
    }
    
    return $mantissa . ' × 10<sup>' . $exponent . '</sup>';
}

// Get status badge class
function get_status_badge_class($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'completed':
            return 'status-completed';
        case 'rejected':
            return 'status-rejected';
        default:
            return 'status-pending';
    }
}

// Get status display text
function get_status_display($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'completed':
            return 'Completed';
        case 'rejected':
            return 'Rejected';
        default:
            return ucfirst($status);
    }
}
?>

<style>
/* Redemption Page Styles */
.redemption-container {
    background: #ffffff;
    border-radius: 20px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    font-family: "Inter", system-ui, -apple-system, sans-serif;
    color: #1f2937;
    overflow: hidden;
}

.redemption-header {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    padding: 32px 40px;
    color: white;
    position: relative;
    overflow: hidden;
}

.redemption-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.redemption-header h2 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: white;
    position: relative;
    z-index: 1;
}

.redemption-header .subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    position: relative;
    z-index: 1;
}

.redemption-content {
    padding: 40px;
}

.available-xp-card {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
    border-radius: 20px;
    padding: 32px 24px;
    margin-bottom: 32px;
    text-align: center;
    color: white;
    box-shadow: 0 10px 40px rgba(79, 70, 229, 0.3), 0 4px 16px rgba(124, 58, 237, 0.2);
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.available-xp-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.available-xp-card::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -15%;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.available-xp-label {
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    opacity: 0.9;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}

.available-xp-value {
    font-size: 2.75rem;
    font-weight: 700;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    margin-bottom: 16px;
    position: relative;
    z-index: 1;
    line-height: 1.2;
    color: #ffffff;
}

.available-xp-conversion {
    font-size: 0.9rem;
    opacity: 0.85;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
    font-weight: 500;
    line-height: 1.6;
}

.available-xp-conversion::before,
.available-xp-conversion::after {
    display: none;
}

.redeem-button {
    background: #ffffff;
    color: #4f46e5;
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 1;
    text-transform: none;
    letter-spacing: 0;
    display: inline-block;
    margin-top: 8px;
}

.redeem-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #f8f9fa;
}

.redeem-button:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.redeem-button:disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
    opacity: 0.6;
}

.redemption-info-banner {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid #fbbf24;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 32px;
    box-shadow: 0 4px 12px rgba(251, 191, 36, 0.2);
}

.redemption-info-banner h3 {
    margin: 0 0 12px 0;
    color: #92400e;
    font-size: 1.1rem;
    font-weight: 700;
}

.redemption-info-banner p {
    margin: 0;
    color: #78350f;
    font-size: 0.9rem;
    line-height: 1.6;
}

/* Tab Navigation - Styles moved to inline CSS */

.redemption-history-section {
    margin-top: 0;
}

.redemptions-table-wrapper {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.redemptions-table {
    width: 100%;
    border-collapse: collapse;
}

.redemptions-table thead {
    background: #f9fafb;
    border-bottom: 2px solid #e5e7eb;
}

.redemptions-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.redemptions-table td {
    padding: 16px;
    border-bottom: 1px solid #f3f4f6;
    color: #6b7280;
    font-size: 0.9rem;
}

.redemptions-table tbody tr:hover {
    background: #f9fafb;
}

.redemptions-table tbody tr:last-child td {
    border-bottom: none;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
    background: #f9fafb;
    border-radius: 12px;
    border: 2px dashed #e5e7eb;
}

.empty-state p {
    font-size: 1rem;
    margin: 0;
}

.value-monospace {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}

@media (max-width: 768px) {
    .redemption-header {
        padding: 24px 20px;
    }
    
    .redemption-header h2 {
        font-size: 1.5rem;
    }
    
    .redemption-content {
        padding: 24px 20px;
    }
    
    .redemptions-table {
        font-size: 0.875rem;
    }
    
    .redemptions-table th,
    .redemptions-table td {
        padding: 12px 8px;
    }
}
</style>

<div class="redemption-container">
    <!-- Header Section -->
    <div class="redemption-header">
        <h2><?php esc_html_e('Redemption', 'cpm-dongtrader'); ?></h2>
        <div class="subtitle"><?php esc_html_e('View and manage your XP redemption requests', 'cpm-dongtrader'); ?></div>
    </div>
    
    <!-- Content Section -->
    <div class="redemption-content">
        <!-- Cards Container -->
        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <!-- Available XP Card -->
            <div class="available-xp-card" style="background: linear-gradient(135deg, #f3f4f6 0%, #f5f3ff 50%, #faf5ff 100%); border-radius: 16px; padding: 20px 16px; text-align: center; color: #6b21a8; box-shadow: 0 8px 24px rgba(243, 244, 246, 0.4), 0 2px 8px rgba(245, 243, 255, 0.3); position: relative; overflow: hidden; border: 1px solid rgba(196, 181, 253, 0.3); flex: 1; min-width: 200px;">
                <div class="available-xp-label" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 8px; position: relative; z-index: 1; color: #7c3aed;"><?php esc_html_e('Available XP', 'cpm-dongtrader'); ?></div>
                <div class="available-xp-value" style="font-size: 1.875rem; font-weight: 700; font-family: 'Inter', system-ui, -apple-system, sans-serif; margin-bottom: 10px; position: relative; z-index: 1; line-height: 1.2; color: #6b21a8;">
                <?php 
                // Display available XP in scientific notation
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

            <!-- YAM Equivalent Card -->
            <div class="yam-equivalent-card" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%); border-radius: 16px; padding: 20px 16px; text-align: center; color: #065f46; box-shadow: 0 8px 24px rgba(240, 253, 244, 0.4), 0 2px 8px rgba(236, 253, 245, 0.3); position: relative; overflow: hidden; border: 1px solid rgba(110, 231, 183, 0.3); flex: 1; min-width: 200px;">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 8px; position: relative; z-index: 1; color: #059669;"><?php esc_html_e('YAM Equivalent', 'cpm-dongtrader'); ?></div>
                <div style="font-size: 1.875rem; font-weight: 700; font-family: 'Inter', system-ui, -apple-system, sans-serif; margin-bottom: 10px; position: relative; z-index: 1; line-height: 1.2; color: #047857;">
                <?php 
                if ($available_yam_equivalent > 0 && is_numeric($available_yam_equivalent)) {
                    $yam_scientific = sprintf('%.2e', (float)$available_yam_equivalent);
                    $parts = explode('e', $yam_scientific);
                    if (count($parts) == 2) {
                        $mantissa_raw = $parts[0];
                        if (strpos($mantissa_raw, '.') !== false) {
                            $mantissa = rtrim($mantissa_raw, '0');
                            if (substr($mantissa, -1) === '.') {
                                $mantissa = rtrim($mantissa, '.');
                            }
                        } else {
                            $mantissa = $mantissa_raw;
                        }
                        $exponent = intval($parts[1]);
                        if ($exponent == 0) {
                            $base_value = floatval($mantissa);
                            echo ($base_value == floor($base_value)) ? (int)$base_value : $base_value;
                        } else {
                            echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                        }
                    } else {
                        echo number_format($available_yam_equivalent, 2);
                    }
                } else {
                    echo '0';
                }
                ?> YAM
            </div>
            </div>

            <!-- USD Equivalent Card -->
            <div class="usd-equivalent-card" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 50%, #fecaca 100%); border-radius: 16px; padding: 20px 16px; text-align: center; color: #991b1b; box-shadow: 0 8px 24px rgba(254, 242, 242, 0.4), 0 2px 8px rgba(254, 226, 226, 0.3); position: relative; overflow: hidden; border: 1px solid rgba(252, 165, 165, 0.3); flex: 1; min-width: 200px;">
                <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.7; margin-bottom: 8px; position: relative; z-index: 1; color: #dc2626;"><?php esc_html_e('USD Equivalent', 'cpm-dongtrader'); ?></div>
                <div style="font-size: 1.875rem; font-weight: 700; font-family: 'Inter', system-ui, -apple-system, sans-serif; margin-bottom: 10px; position: relative; z-index: 1; line-height: 1.2; color: #b91c1c;">
                    <?php echo esc_html($cs) . number_format($available_usd_trade_value, 2); ?> USD
                </div>
            </div>
        </div>

        <!-- Redeem Button / Join Gracebook Button -->
        <div style="text-align: center; margin-bottom: 24px;">
            <?php
            // Check if user has _discord_invite meta
            $discord_invite_check = get_user_meta($user_id, '_discord_invite', false);
            $has_discord_invite = !empty($discord_invite_check) && is_array($discord_invite_check);
            
            // Discord invite link
            $discord_invite_link = 'https://discord.gg/g5jreAPbra';
            
            if (!$has_discord_invite) {
                // Display "Join Gracebook" button if no Discord invite exists
                ?>
                <a href="<?php echo esc_url($discord_invite_link); ?>" target="_blank" class="join-gracebook-button" style="background: linear-gradient(135deg, #5865F2 0%, #4752C4 100%); color: #ffffff; border: 2px solid #4752C4; padding: 16px 32px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(88, 101, 242, 0.3); position: relative; z-index: 1; text-transform: none; letter-spacing: 0; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; text-align: center; line-height: 1.4;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(88, 101, 242, 0.4)'; this.style.background='linear-gradient(135deg, #4752C4 0%, #3a45b8 100%)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(88, 101, 242, 0.3)'; this.style.background='linear-gradient(135deg, #5865F2 0%, #4752C4 100%)';" onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(88, 101, 242, 0.25)';" onmouseup="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(88, 101, 242, 0.4)';">
                    <span style="font-size: 1rem; font-weight: 700; margin-bottom: 4px; display: block;"><?php esc_html_e('Join Gracebook Discord', 'cpm-dongtrader'); ?></span>
                    <span style="font-size: 0.875rem; font-weight: 400; opacity: 0.95; display: block;"><?php echo esc_html__('Join to Gracebook server and get verified by discord (earn 5 × 10', 'cpm-dongtrader'); ?><sup>6</sup><?php echo esc_html__(')', 'cpm-dongtrader'); ?></span>
                </a>
                <?php
            } elseif ($available_usd_trade_value >= 1.00) {
                // Display "Redeem XP" button if Discord invite exists and USD >= $1.00
                ?>
                <button type="button" class="redeem-button" id="redeem-button" style="background: #c4b5fd; color: #5b21b6; border: none; padding: 12px 32px; border-radius: 10px; font-weight: 600; font-size: 0.9375rem; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 3px 8px rgba(196, 181, 253, 0.3); position: relative; z-index: 1; text-transform: none; letter-spacing: 0; display: inline-block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(196, 181, 253, 0.4)'; this.style.background='#a78bfa';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 3px 8px rgba(196, 181, 253, 0.3)'; this.style.background='#c4b5fd';" onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 6px rgba(196, 181, 253, 0.25)';" onmouseup="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(196, 181, 253, 0.4)';"><?php esc_html_e('Redeem', 'cpm-dongtrader'); ?></button>
                <?php
            }
            // If Discord invite exists but USD < $1.00, no button is displayed
            ?>
        </div>

        <!-- Info Banner -->
        <div class="redemption-info-banner">
            <h3><?php esc_html_e('Redemption Information', 'cpm-dongtrader'); ?></h3>
            <p>
                <?php esc_html_e('You can redeem your matured XP for USD. Redemptions are processed during specific redemption windows. Minimum redemption amount is $1.00 USD. Please ensure your payment details are up to date.', 'cpm-dongtrader'); ?>
            </p>
        </div>

        <!-- Tab Navigation Script (function already defined at top of page) -->

        <!-- Tab Navigation -->
        <div class="redemption-tabs-nav" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; flex-wrap: nowrap; padding: 12px 0; background: transparent; border-bottom: 2px solid #e5e7eb; margin: 32px 0 0 0; list-style: none; width: 100%; box-sizing: border-box; gap: 12px; flex-shrink: 0;">
            <button type="button" class="redemption-tab-button active" data-tab="redemption-history" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: 2px solid #6d28d9; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #ffffff; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15); transform: translateY(-2px);" onmouseover="this.style.background='linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%)'; this.style.boxShadow='0 6px 16px rgba(124, 58, 237, 0.3), 0 3px 6px rgba(124, 58, 237, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)'; this.style.boxShadow='0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15)';">
                <?php esc_html_e('Redemption History', 'cpm-dongtrader'); ?>
            </button>
            <button type="button" class="redemption-tab-button" data-tab="xp-maturity" style="background: #f9fafb; border: 2px solid #e5e7eb; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #6b7280; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);" onmouseover="this.style.color='#7c3aed'; this.style.background='linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%)'; this.style.borderColor='#c4b5fd'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(124, 58, 237, 0.15)';" onmouseout="this.style.color='#6b7280'; this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.05)';">
                <?php esc_html_e('XP Maturity', 'cpm-dongtrader'); ?>
            </button>
        </div>
        
        <!-- Tab Navigation Event Handlers -->
        <script type="text/javascript">
        // Attach click handlers to tab buttons
        (function() {
            function initTabs() {
                var tabButtons = document.querySelectorAll('.redemption-tab-button');
                var i;
                for (i = 0; i < tabButtons.length; i++) {
                    tabButtons[i].addEventListener('click', function(e) {
                        e.preventDefault();
                        var tabName = this.getAttribute('data-tab');
                        if (tabName) {
                            if (typeof window.switchRedemptionTab === 'function') {
                                window.switchRedemptionTab(tabName);
                            }
                        }
                    });
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTabs);
            } else {
                initTabs();
            }
        })();
        </script>

        <!-- Redemption History Tab -->
        <div class="redemption-tab-content active" id="redemption-history-tab" style="padding: 24px 0; background: white; display: block; visibility: visible;">
            <div class="redemption-history-section">
                <?php if (empty($redemptions)): ?>
                    <div class="empty-state">
                        <p><?php esc_html_e('No redemption requests yet.', 'cpm-dongtrader'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="redemptions-table-wrapper">
                        <table class="redemptions-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('YAM Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('USD Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Payment Method', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Status', 'cpm-dongtrader'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($redemptions as $redemption): 
                                    $redem_date = !empty($redemption['redem_date']) ? strtotime($redemption['redem_date']) : time();
                                    $xp_amount = isset($redemption['xp_redem']) ? floatval($redemption['xp_redem']) : 0;
                                    $yam_amount = isset($redemption['yam_redem']) ? floatval($redemption['yam_redem']) : 0;
                                    $usd_amount = isset($redemption['usd_redem']) ? floatval($redemption['usd_redem']) : 0;
                                    $payment_method = isset($redemption['payment_method']) ? esc_html($redemption['payment_method']) : '—';
                                    $status = isset($redemption['status']) ? $redemption['status'] : 'pending';
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $redem_date); ?>
                                        </td>
                                        <td class="value-monospace">
                                            <?php echo format_xp_scientific_redemption($xp_amount); ?> XP
                                        </td>
                                        <td class="value-monospace">
                                            <?php echo number_format($yam_amount, 2); ?> YAM
                                        </td>
                                        <td class="value-monospace" style="color: #059669; font-weight: 700;">
                                            <?php echo $cs . number_format($usd_amount, 2); ?>
                                        </td>
                                        <td>
                                            <?php echo $payment_method; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo esc_attr(get_status_badge_class($status)); ?>">
                                                <?php echo esc_html(get_status_display($status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- XP Maturity Tab -->
        <div class="redemption-tab-content" id="xp-maturity-tab" style="padding: 24px 0; background: white; display: none; visibility: hidden;">
            <div class="redemption-history-section">
                <?php 
                // Get all XP transactions from all usermeta sources
                // Include: seller_scan, buyer_scan, personal_scan, discord_invite, talentshow_entry, discord_poll
                $xp_maturity_entries = array();
                
                foreach ($user_treasury_entries as $entry) {
                    // Include all transaction sources
                    $entry_source = isset($entry['source']) ? $entry['source'] : '';
                    if (in_array($entry_source, array('seller_scan', 'buyer_scan', 'personal_scan', 'discord_invite', 'talentshow_entry', 'discord_poll'))) {
                        // Ensure entry has required fields for display
                        if (!isset($entry['xp_units']) || $entry['xp_units'] == 0) {
                            // Try to get XP from other fields
                            if (isset($entry['xp_awarded'])) {
                                $entry['xp_units'] = floatval($entry['xp_awarded']) / 1000000;
                            } elseif (isset($entry['xp_display_value'])) {
                                $entry['xp_units'] = floatval($entry['xp_display_value']) / 1000000;
                            }
                        }
                        // Ensure yam_value is set
                        if (!isset($entry['yam_value']) || $entry['yam_value'] == 0) {
                            if (isset($entry['xp_units']) && $entry['xp_units'] > 0) {
                                $entry['yam_value'] = dongtrader_xp_to_yam($entry['xp_units']);
                            }
                        }
                        // Ensure trade_value_usd is set
                        if (!isset($entry['trade_value_usd']) || $entry['trade_value_usd'] == 0) {
                            if (isset($entry['xp_units']) && $entry['xp_units'] > 0) {
                                $entry['trade_value_usd'] = dongtrader_xp_to_usd($entry['xp_units']);
                            }
                        }
                        // Ensure timestamp is set
                        if (!isset($entry['timestamp']) || empty($entry['timestamp'])) {
                            $entry['timestamp'] = current_time('mysql');
                        }
                        // Ensure proof_id or transaction_id is set
                        if (!isset($entry['proof_id']) && !isset($entry['transaction_id'])) {
                            $entry['proof_id'] = $entry_source . '_' . time();
                        }
                        $xp_maturity_entries[] = $entry;
                    }
                }
                
                // Sort by timestamp (newest first)
                usort($xp_maturity_entries, function($a, $b) {
                    $time_a = isset($a['timestamp']) ? strtotime($a['timestamp']) : 0;
                    $time_b = isset($b['timestamp']) ? strtotime($b['timestamp']) : 0;
                    return $time_b - $time_a;
                });
                
                if (empty($xp_maturity_entries)): ?>
                    <div class="empty-state" style="padding: 40px; text-align: center; color: #6b7280;">
                        <p style="font-size: 1.1rem; margin-bottom: 10px;"><?php esc_html_e('No XP transactions found.', 'cpm-dongtrader'); ?></p>
                        <p style="font-size: 0.9rem;"><?php esc_html_e('Your transaction history will appear here once you have XP entries.', 'cpm-dongtrader'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="redemptions-table-wrapper">
                        <table class="redemptions-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Source', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Transaction ID', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Maturity', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Status', 'cpm-dongtrader'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Get redemption table name and fetch all redemptions for this user once
                                global $wpdb;
                                $redemptions_table = $wpdb->prefix . 'dongtrader_redemptions';
                                
                                // Fetch all redemptions for this user once (more efficient)
                                $user_redemptions = $wpdb->get_results($wpdb->prepare(
                                    "SELECT status, meta_ids, transaction_id FROM {$redemptions_table} WHERE user_id = %d",
                                    $user_id
                                ), ARRAY_A);
                                
                                foreach ($xp_maturity_entries as $entry): 
                                    $entry_timestamp = isset($entry['timestamp']) ? strtotime($entry['timestamp']) : time();
                                    $entry_source = isset($entry['source']) ? $entry['source'] : 'unknown';
                                    $source_display = ucfirst(str_replace('_', ' ', $entry_source));
                                    $transaction_id_raw = isset($entry['transaction_id']) ? $entry['transaction_id'] : (isset($entry['proof_id']) ? $entry['proof_id'] : '');
                                    $transaction_id = esc_html($transaction_id_raw);
                                    $xp_value = isset($entry['xp_units']) ? floatval($entry['xp_units']) : 0;
                                    
                                    // Get umeta_id if available
                                    $umeta_id = isset($entry['umeta_id']) ? intval($entry['umeta_id']) : 0;
                                    
                                    // Calculate maturity
                                    $delivery_date = null;
                                    if (isset($entry['delivery_date']) && !empty($entry['delivery_date'])) {
                                        $delivery_date = $entry['delivery_date'];
                                    } elseif (isset($entry['timestamp'])) {
                                        // Convert timestamp to MySQL datetime format
                                        if (is_numeric($entry['timestamp'])) {
                                            $delivery_date = date('Y-m-d H:i:s', $entry_timestamp);
                                                } else {
                                            $delivery_date = $entry['timestamp'];
                                        }
                                    } else {
                                        $delivery_date = date('Y-m-d H:i:s', $entry_timestamp);
                                    }
                                    
                                    // Get maturity weeks (8-12 weeks, default 10)
                                    $maturity_weeks = dongtrader_get_maturity_weeks();
                                    if (isset($entry['maturity_weeks']) && $entry['maturity_weeks'] >= 8 && $entry['maturity_weeks'] <= 12) {
                                        $maturity_weeks = intval($entry['maturity_weeks']);
                                    }
                                    
                                    // Calculate maturity date
                                    $maturity_date = dongtrader_calculate_maturity_date($delivery_date, $maturity_weeks);
                                    $current_time = current_time('timestamp');
                                    $maturity_timestamp = $maturity_date ? strtotime($maturity_date) : null;
                                    $is_mature = $maturity_timestamp && ($current_time >= $maturity_timestamp);
                                    
                                    // Calculate remaining time for display
                                    $maturity_display = '—';
                                    $maturity_class = '';
                                    if ($maturity_timestamp) {
                                        $diff_seconds = $maturity_timestamp - $current_time;
                                        if ($diff_seconds <= 0) {
                                            // Already mature
                                            $maturity_display = '<span style="color: #059669; font-weight: 600;">' . esc_html__('Matured', 'cpm-dongtrader') . '</span>';
                                            $maturity_class = 'matured';
                                                    } else {
                                            // Calculate days, hours, minutes
                                            $days = floor($diff_seconds / (60 * 60 * 24));
                                            $hours = floor(($diff_seconds % (60 * 60 * 24)) / (60 * 60));
                                            $minutes = floor(($diff_seconds % (60 * 60)) / 60);
                                            
                                            if ($days > 0) {
                                                $maturity_display = sprintf(
                                                    '<span style="color: #dc2626; font-weight: 600;">%d %s, %d %s</span>',
                                                    $days,
                                                    $days == 1 ? esc_html__('day', 'cpm-dongtrader') : esc_html__('days', 'cpm-dongtrader'),
                                                    $hours,
                                                    $hours == 1 ? esc_html__('hour', 'cpm-dongtrader') : esc_html__('hours', 'cpm-dongtrader')
                                                );
                                            } elseif ($hours > 0) {
                                                $maturity_display = sprintf(
                                                    '<span style="color: #dc2626; font-weight: 600;">%d %s, %d %s</span>',
                                                    $hours,
                                                    $hours == 1 ? esc_html__('hour', 'cpm-dongtrader') : esc_html__('hours', 'cpm-dongtrader'),
                                                    $minutes,
                                                    $minutes == 1 ? esc_html__('minute', 'cpm-dongtrader') : esc_html__('minutes', 'cpm-dongtrader')
                                                );
                                            } else {
                                                $maturity_display = sprintf(
                                                    '<span style="color: #dc2626; font-weight: 600;">%d %s</span>',
                                                    $minutes,
                                                    $minutes == 1 ? esc_html__('minute', 'cpm-dongtrader') : esc_html__('minutes', 'cpm-dongtrader')
                                                );
                                            }
                                            $maturity_class = 'maturing';
                                        }
                                    }
                                    
                                    // Check redemption table for this entry
                                    $redemption_status = null;
                                    $redemption_match = false;
                                    
                                    if (($umeta_id > 0 || !empty($transaction_id_raw)) && !empty($user_redemptions)) {
                                        foreach ($user_redemptions as $redemption) {
                                            $match_found = false;
                                            
                                            // Check by umeta_id in meta_ids
                                            if ($umeta_id > 0 && !empty($redemption['meta_ids'])) {
                                                $meta_ids = $redemption['meta_ids'];
                                                // meta_ids might be comma-separated, JSON, or serialized
                                                $meta_ids_array = array();
                                                
                                                // Try to decode as JSON
                                                $decoded = json_decode($meta_ids, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $meta_ids_array = $decoded;
                                                    } else {
                                                    // Try comma-separated
                                                    $meta_ids_array = array_map('trim', explode(',', $meta_ids));
                                                }
                                                
                                                // Check if umeta_id is in the array
                                                if (in_array($umeta_id, $meta_ids_array) || in_array((string)$umeta_id, $meta_ids_array)) {
                                                    $match_found = true;
                                                }
                                            }
                                            
                                            // Check by transaction_id
                                            if (!$match_found && !empty($transaction_id_raw) && !empty($redemption['transaction_id'])) {
                                                if ($redemption['transaction_id'] === $transaction_id_raw || $redemption['transaction_id'] === $transaction_id) {
                                                    $match_found = true;
                                                }
                                            }
                                            
                                            if ($match_found) {
                                                $redemption_status = $redemption['status'];
                                                $redemption_match = true;
                                                break;
                                            }
                                        }
                                    }
                                    
                                    // Determine status to display
                                    if ($redemption_match && $redemption_status) {
                                        // Display redemption status
                                        $status_display = esc_html(ucfirst($redemption_status));
                                        $status_class = get_status_badge_class($redemption_status);
                                            } else {
                                        // Check maturity and display redeemable/non-redeemable
                                        if ($is_mature) {
                                            $status_display = esc_html__('Redeemable', 'cpm-dongtrader');
                                            $status_class = 'status-completed';
                                        } else {
                                            $status_display = esc_html__('Non Redeemable', 'cpm-dongtrader');
                                            $status_class = 'status-pending';
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry_timestamp); ?></td>
                                        <td><span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: capitalize; background: #f3f4f6; color: #4b5563;"><?php echo esc_html($source_display); ?></span></td>
                                        <td class="value-monospace" style="font-size: 0.85rem;"><?php echo $transaction_id; ?></td>
                                        <td class="value-monospace"><?php echo format_xp_scientific_redemption($xp_value); ?> XP</td>
                                        <td class="maturity-cell <?php echo esc_attr($maturity_class); ?>"><?php echo $maturity_display; ?></td>
                                        <td><span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo $status_display; ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
// Output script directly to prevent WordPress content filtering
echo '<script type="text/javascript">';
echo '// Ensure Redemption History tab is active on page load';
echo '(function() {';
echo '    function setInitialTab() {';
echo '        if (typeof window.switchRedemptionTab === "function") {';
echo '            window.switchRedemptionTab("redemption-history");';
echo '        }';
echo '    }';
echo '    if (document.readyState === "loading") {';
echo '        document.addEventListener("DOMContentLoaded", setInitialTab);';
echo '    } else {';
echo '        setInitialTab();';
echo '    }';
echo '})();';
echo '</script>';
?>

<!-- Redemption Popup -->
<div id="redemption-popup" style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0, 0, 0, 0.5); align-items: center; justify-content: center;">
    <div style="background-color: #fefefe; margin: auto; padding: 30px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); position: relative;">
        <span id="close-redemption-popup" style="color: #aaa; position: absolute; top: 15px; right: 20px; font-size: 32px; font-weight: bold; cursor: pointer; line-height: 1;">&times;</span>
        
        <h2 style="margin-top: 0; margin-bottom: 20px; color: #333;"><?php esc_html_e('Redeem XP', 'cpm-dongtrader'); ?></h2>
        
        <div style="margin-bottom: 20px;">
            <p style="margin: 10px 0;"><strong><?php esc_html_e('XP Amount:', 'cpm-dongtrader'); ?></strong> <span id="popup-xp-amount">0</span></p>
            <p style="margin: 10px 0;"><strong><?php esc_html_e('YAM Amount:', 'cpm-dongtrader'); ?></strong> <span id="popup-yam-amount">0</span></p>
            <p style="margin: 10px 0;"><strong><?php esc_html_e('USD Amount:', 'cpm-dongtrader'); ?></strong> <span id="popup-usd-amount">$0.00</span></p>
            <p style="margin: 10px 0; font-size: 0.9em; color: #666;"><strong><?php esc_html_e('XP per YAM:', 'cpm-dongtrader'); ?></strong> <span id="popup-xp-yam-rate">0</span></p>
            <p style="margin: 10px 0; font-size: 0.9em; color: #666;"><strong><?php esc_html_e('YAM per USD:', 'cpm-dongtrader'); ?></strong> <span id="popup-yam-usd-rate">0</span></p>
            <p id="popup-meta-ids" style="margin: 10px 0; font-size: 0.85em; color: #666; word-break: break-all;"></p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="payment-method" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e('Payment Method:', 'cpm-dongtrader'); ?></label>
            <select id="payment-method" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                <option value=""><?php esc_html_e('Select Payment Method', 'cpm-dongtrader'); ?></option>
                <option value="PayPal"><?php esc_html_e('PayPal', 'cpm-dongtrader'); ?></option>
                <option value="Venmo"><?php esc_html_e('Venmo', 'cpm-dongtrader'); ?></option>
            </select>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label for="payment-details" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e('Payment Details:', 'cpm-dongtrader'); ?></label>
            <textarea id="payment-details" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;" placeholder="<?php esc_attr_e('Enter your payment details (e.g., PayPal email or Venmo username)', 'cpm-dongtrader'); ?>"></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" id="cancel-redemption" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem;"><?php esc_html_e('Cancel', 'cpm-dongtrader'); ?></button>
            <button type="button" id="submit-redemption" style="background: #7c3aed; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 1rem;"><?php esc_html_e('Submit', 'cpm-dongtrader'); ?></button>
        </div>
    </div>
</div>
<?php
// Add script to footer to avoid WordPress content filtering
add_action('wp_footer', function() use ($available_xp, $available_yam_equivalent, $available_usd_trade_value) {
    if (!is_account_page()) return;
    ?>
<script type="text/javascript">
(function() {
    // Wait for DOM to be ready
    function initRedemptionButton() {
        var redeemButton = document.getElementById("redeem-button");
        if (!redeemButton) return;
        
        // Get values from PHP
        var availableXp = <?php echo json_encode($available_xp); ?>;
        var availableYam = <?php echo json_encode($available_yam_equivalent); ?>;
        var availableUsd = <?php echo json_encode($available_usd_trade_value); ?>;
        
        // Calculate rates
        var xpPerYam = 0;
        if (availableXp > 0) {
            if (availableYam > 0) {
                xpPerYam = availableXp / availableYam;
            }
        }
        var yamPerUsd = 0;
        if (availableYam > 0) {
            if (availableUsd > 0) {
                yamPerUsd = availableYam / availableUsd;
            }
        }
        
        // Add click handler to redeem button
        redeemButton.addEventListener("click", function() {
            if (typeof window.showRedemptionPopup === "function") {
                window.showRedemptionPopup(availableXp, availableYam, availableUsd, xpPerYam, yamPerUsd);
            } else {
                console.error("showRedemptionPopup function not found");
            }
        });
        
        // Close popup handlers
        var closeBtn = document.getElementById("close-redemption-popup");
        var cancelBtn = document.getElementById("cancel-redemption");
        var popup = document.getElementById("redemption-popup");
        
        if (closeBtn) {
            closeBtn.addEventListener("click", function() {
                if (typeof window.closeRedemptionPopup === "function") {
                    window.closeRedemptionPopup();
                } else {
                    if (popup) popup.style.display = "none";
                }
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener("click", function() {
                if (typeof window.closeRedemptionPopup === "function") {
                    window.closeRedemptionPopup();
                } else {
                    if (popup) popup.style.display = "none";
                }
            });
        }
        
        // Submit button handler
        var submitBtn = document.getElementById("submit-redemption");
        if (submitBtn) {
            submitBtn.addEventListener("click", function() {
                if (typeof window.submitRedemptionRequest === "function") {
                    window.submitRedemptionRequest();
                } else {
                    console.error("submitRedemptionRequest function not found");
                }
            });
        }
        
        // Close popup when clicking outside
        if (popup) {
            popup.addEventListener("click", function(e) {
                if (e.target === popup) {
                    if (typeof window.closeRedemptionPopup === "function") {
                        window.closeRedemptionPopup();
                    } else {
                        popup.style.display = "none";
                    }
                }
            });
        }
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initRedemptionButton);
    } else {
        initRedemptionButton();
    }
})();
</script>
    <?php
}, 999);
?>

