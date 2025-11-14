<?php
/**
 * Redemption Page Template
 * Allows users to view available XP and submit redemption requests
 */

if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to view redemptions.', 'cpm-dongtrader') . '</p>';
    return;
}

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
    padding: 0;
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
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    text-align: center;
    color: white;
    box-shadow: 0 8px 24px rgba(124, 58, 237, 0.3);
    position: relative;
    overflow: hidden;
}

.available-xp-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.available-xp-label {
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    margin-bottom: 12px;
    position: relative;
    z-index: 1;
}

.available-xp-value {
    font-size: 3rem;
    font-weight: 700;
    font-family: 'Courier New', monospace;
    margin-bottom: 16px;
    position: relative;
    z-index: 1;
    line-height: 1.2;
}

.available-xp-conversion {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.redeem-button {
    background: white;
    color: #7c3aed;
    border: none;
    padding: 16px 48px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 1;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.redeem-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #f9fafb;
}

.redeem-button:active {
    transform: translateY(0);
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
        <!-- Available XP Card -->
        <div class="available-xp-card">
            <div class="available-xp-label"><?php esc_html_e('Available XP', 'cpm-dongtrader'); ?></div>
            <div class="available-xp-value">
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
            <div class="available-xp-conversion">
                <?php 
                echo esc_html($cs) . number_format($available_usd_trade_value, 2); 
                ?> USD • 
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
            <button type="button" class="redeem-button" id="redeem-button">
                <?php esc_html_e('Redeem XP', 'cpm-dongtrader'); ?>
            </button>
        </div>

        <!-- Info Banner -->
        <div class="redemption-info-banner">
            <h3><?php esc_html_e('Redemption Information', 'cpm-dongtrader'); ?></h3>
            <p>
                <?php esc_html_e('You can redeem your matured XP for USD. Redemptions are processed during specific redemption windows. Minimum redemption amount is $1.00 USD. Please ensure your payment details are up to date.', 'cpm-dongtrader'); ?>
            </p>
        </div>

        <!-- Tab Navigation Script (must be before buttons) -->
        <script type="text/javascript">
        // Define function immediately in global scope
        window.switchRedemptionTab = function(tabName) {
            console.log('🔄 Switching to tab:', tabName);
            
            // Use jQuery if available, otherwise use vanilla JS
            if (typeof jQuery !== 'undefined') {
                var $ = jQuery;
                
                // Remove active class from all tabs and buttons
                $('.redemption-tab-button').removeClass('active');
                $('.redemption-tab-content').removeClass('active');
                
                // Reset all tab buttons to inactive style
                $('.redemption-tab-button').each(function() {
                    var $btn = $(this);
                    var dataTab = $btn.attr('data-tab');
                    if (dataTab !== tabName) {
                        $btn.attr('style', 'background: #f9fafb; border: 2px solid #e5e7eb; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #6b7280; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);');
                    }
                });
                
                // Hide all tab contents explicitly with inline styles
                $('.redemption-tab-content').css({
                    'display': 'none',
                    'visibility': 'hidden',
                    'opacity': '0'
                });
                
                // Add active class and active style to selected tab button
                var $tabButton = $('.redemption-tab-button[data-tab="' + tabName + '"]');
                $tabButton.addClass('active');
                $tabButton.attr('style', 'background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: 2px solid #6d28d9; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #ffffff; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15); transform: translateY(-2px);');
                
                // Show selected tab content with inline styles
                var $tabContent = $('#' + tabName + '-tab');
                $tabContent.addClass('active');
                $tabContent.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'padding': '24px 0',
                    'background': 'white'
                });
                
                console.log('✅ Tab switched to:', tabName);
            } else {
                // Fallback vanilla JavaScript
                console.log('⚠️ jQuery not available, using vanilla JS');
                
                // Remove active class from all buttons
                var buttons = document.querySelectorAll('.redemption-tab-button');
                buttons.forEach(function(btn) {
                    btn.classList.remove('active');
                    var btnTab = btn.getAttribute('data-tab');
                    if (btnTab !== tabName) {
                        btn.setAttribute('style', 'background: #f9fafb; border: 2px solid #e5e7eb; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #6b7280; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);');
                    }
                });
                
                // Hide all tab contents
                var contents = document.querySelectorAll('.redemption-tab-content');
                contents.forEach(function(content) {
                    content.classList.remove('active');
                    content.style.display = 'none';
                    content.style.visibility = 'hidden';
                    content.style.opacity = '0';
                });
                
                // Show selected tab button
                var tabButton = document.querySelector('.redemption-tab-button[data-tab="' + tabName + '"]');
                if (tabButton) {
                    tabButton.classList.add('active');
                    tabButton.setAttribute('style', 'background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: 2px solid #6d28d9; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #ffffff; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15); transform: translateY(-2px);');
                }
                
                // Show selected tab content
                var tabContent = document.getElementById(tabName + '-tab');
                if (tabContent) {
                    tabContent.classList.add('active');
                    tabContent.style.display = 'block';
                    tabContent.style.visibility = 'visible';
                    tabContent.style.opacity = '1';
                    tabContent.style.padding = '24px 0';
                    tabContent.style.background = 'white';
                }
                
                console.log('✅ Tab switched (vanilla JS)');
            }
        };
        console.log('✅ switchRedemptionTab function defined');
        </script>

        <!-- Tab Navigation -->
        <div class="redemption-tabs-nav" style="display: flex; flex-direction: row; align-items: center; justify-content: flex-start; flex-wrap: nowrap; padding: 12px 0; background: transparent; border-bottom: 2px solid #e5e7eb; margin: 32px 0 0 0; list-style: none; width: 100%; box-sizing: border-box; gap: 12px; flex-shrink: 0;">
            <button type="button" class="redemption-tab-button active" data-tab="redemption-history" onclick="window.switchRedemptionTab('redemption-history'); return false;" style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border: 2px solid #6d28d9; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #ffffff; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15); transform: translateY(-2px);" onmouseover="this.style.background='linear-gradient(135deg, #6d28d9 0%, #5b21b6 100%)'; this.style.boxShadow='0 6px 16px rgba(124, 58, 237, 0.3), 0 3px 6px rgba(124, 58, 237, 0.2)';" onmouseout="this.style.background='linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)'; this.style.boxShadow='0 4px 12px rgba(124, 58, 237, 0.25), 0 2px 4px rgba(124, 58, 237, 0.15)';">
                <?php esc_html_e('Redemption History', 'cpm-dongtrader'); ?>
            </button>
            <button type="button" class="redemption-tab-button" data-tab="xp-maturity" onclick="window.switchRedemptionTab('xp-maturity'); return false;" style="background: #f9fafb; border: 2px solid #e5e7eb; padding: 12px 24px; font-size: 0.9375rem; font-weight: 600; color: #6b7280; cursor: pointer; border-radius: 10px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; display: block; flex-shrink: 0; flex-grow: 0; white-space: nowrap; box-sizing: border-box; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);" onmouseover="this.style.color='#7c3aed'; this.style.background='linear-gradient(135deg, #f3e8ff 0%, #ede9fe 100%)'; this.style.borderColor='#c4b5fd'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(124, 58, 237, 0.15)';" onmouseout="this.style.color='#6b7280'; this.style.background='#f9fafb'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0, 0, 0, 0.05)';">
                <?php esc_html_e('XP Maturity', 'cpm-dongtrader'); ?>
            </button>
        </div>

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
                // Get all XP transactions from usermeta (seller_scan, buyer_scan, personal_scan)
                // Sort by timestamp
                $xp_maturity_entries = array();
                
                foreach ($user_treasury_entries as $entry) {
                    // Only include seller_scan, buyer_scan, and personal_scan
                    $entry_source = isset($entry['source']) ? $entry['source'] : '';
                    if (in_array($entry_source, array('seller_scan', 'buyer_scan', 'personal_scan'))) {
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
                    <div class="empty-state">
                        <p><?php esc_html_e('No XP transactions found.', 'cpm-dongtrader'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="redemptions-table-wrapper">
                        <table class="redemptions-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Transaction ID', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Role', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('XP Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('YAM Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('USD Amount', 'cpm-dongtrader'); ?></th>
                                    <th><?php esc_html_e('Status', 'cpm-dongtrader'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($xp_maturity_entries as $entry): 
                                    $entry_timestamp = isset($entry['timestamp']) ? strtotime($entry['timestamp']) : time();
                                    $transaction_id = isset($entry['transaction_id']) ? esc_html($entry['transaction_id']) : (isset($entry['proof_id']) ? esc_html($entry['proof_id']) : '—');
                                    $role = isset($entry['role']) ? esc_html(ucfirst($entry['role'])) : '—';
                                    $xp_value = isset($entry['xp_units']) ? floatval($entry['xp_units']) : 0;
                                    $yam_value = isset($entry['yam_value']) ? floatval($entry['yam_value']) : 0;
                                    $usd_value = isset($entry['trade_value_usd']) ? floatval($entry['trade_value_usd']) : 0;
                                    $scan_status = isset($entry['scan_status']) ? $entry['scan_status'] : 'pending';
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $entry_timestamp); ?>
                                        </td>
                                        <td class="value-monospace" style="font-size: 0.85rem;">
                                            <?php echo $transaction_id; ?>
                                        </td>
                                        <td>
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; 
                                                <?php 
                                                if ($role === 'Seller') {
                                                    echo 'background: #dbeafe; color: #1e40af;';
                                                } elseif ($role === 'Buyer') {
                                                    echo 'background: #fef3c7; color: #92400e;';
                                                } else {
                                                    echo 'background: #d1fae5; color: #065f46;';
                                                }
                                                ?>">
                                                <?php echo $role; ?>
                                            </span>
                                        </td>
                                        <td class="value-monospace">
                                            <?php echo format_xp_scientific_redemption($xp_value); ?> XP
                                        </td>
                                        <td class="value-monospace">
                                            <?php 
                                            if ($yam_value > 0) {
                                                $yam_scientific = sprintf('%.2e', (float)$yam_value);
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
                                                    echo number_format($yam_value, 2);
                                                }
                                            } else {
                                                echo '0';
                                            }
                                            ?> YAM
                                        </td>
                                        <td class="value-monospace" style="color: #059669; font-weight: 700;">
                                            <?php echo $cs . number_format($usd_value, 2); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo esc_attr(get_status_badge_class($scan_status)); ?>">
                                                <?php echo esc_html(get_status_display($scan_status)); ?>
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
    </div>
</div>

<script type="text/javascript">
// Initialize on page load
(function() {
    console.log('🔵 Redemption tabs initialization script STARTING...');
    console.log('- jQuery available:', typeof jQuery !== 'undefined');
    console.log('- switchRedemptionTab function defined:', typeof window.switchRedemptionTab !== 'undefined');
    
    function initTabs() {
        console.log('🔵 Initializing tabs...');
        // Ensure Redemption History tab is active on page load
        if (typeof window.switchRedemptionTab === 'function') {
            window.switchRedemptionTab('redemption-history');
            console.log('✅ Redemption tabs initialized');
        } else {
            console.error('❌ switchRedemptionTab function not found!');
        }
    }
    
    // Wait for DOM and jQuery
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            console.log('📄 jQuery ready');
            initTabs();
        });
    } else {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('📄 DOMContentLoaded (no jQuery)');
                initTabs();
            });
        } else {
            console.log('📄 Document ready (no jQuery)');
            initTabs();
        }
    }
})();
</script>

