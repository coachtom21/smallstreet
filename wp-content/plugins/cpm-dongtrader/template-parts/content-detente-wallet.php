<?php

/**
 * Wallet Page Template
 * Displays XP Wallet based on 2-scan Proof of Delivery system
 * LAUGH Mode: Trade credits only until August 31, 2026
 */
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to view your wallet.', 'cpm-dongtrader') . '</p>';
    return;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$user_name = $user->display_name ? $user->display_name : $user->user_login;

// Get user meta
$user_phone = get_user_meta($user_id, 'mega-mobile', true);
$user_fonepay = get_user_meta($user_id, 'mega-paypal', true);  // Using paypal field for FonePay
$user_qrtiger = get_user_meta($user_id, 'mega-v-card', true);
$user_poc = get_user_meta($user_id, 'mega-glassfrog', true);  // Using glassfrog field for POC

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
$discord_invite_raw = get_user_meta($user_id, '_discord_invite', false);  // Get all rows
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
            // NEW CONVERSION: XP is stored directly
            // If xp_units exists, use it directly (may be string for large numbers)
            if (isset($discord_entry['xp_units'])) {
                $xp_units = is_string($discord_entry['xp_units']) ? ($discord_entry['xp_units']) : ($discord_entry['xp_units']);
            } elseif (isset($discord_entry['xp_awarded'])) {
                // xp_awarded is stored as XP directly (e.g., 5000000 = 5 × 10^6 XP)
                $xp_units = is_string($discord_entry['xp_awarded']) ? ($discord_entry['xp_awarded']) : (string)($discord_entry['xp_awarded']);
            } else {
                $xp_units = '0';
            }
            
            // Ensure xp_units is a string for BCMath compatibility
            if (!is_string($xp_units)) {
                $xp_units = (string)$xp_units;
            }
            
            // Store xp_awarded for display reference
            $xp_awarded_yam = isset($discord_entry['xp_awarded']) ? intval($discord_entry['xp_awarded']) : 0;

            // Calculate USD from XP using new conversion: USD = XP / 10^23
            $xp_units_float = floatval($xp_units);
            $trade_value_usd = $xp_units_float > 0 ? dongtrader_xp_to_usd($xp_units) : 0;
            // NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
            $yam_value = $xp_units_float > 0 ? dongtrader_xp_to_yam($xp_units) : 0;

            $formatted_entry = array(
                'source' => 'discord_invite',
                'role' => 'Discord Verification',
                'timestamp' => isset($discord_entry['verification_date']) ? $discord_entry['verification_date'] : (isset($discord_entry['joined_at']) ? $discord_entry['joined_at'] : current_time('mysql')),
                'proof_id' => 'discord_' . (isset($discord_entry['discord_id']) ? $discord_entry['discord_id'] : 'invite'),
                'xp_units' => $xp_units,
                'xp_display_value' => $xp_awarded_yam,  // Store YAM value for display in XP Minted column
                'yam_value' => $yam_value,
                'trade_value_usd' => $trade_value_usd,
                'trade_value' => $trade_value_usd,
                'status' => isset($discord_entry['status']) ? $discord_entry['status'] : 'completed',
                'scan_status' => isset($discord_entry['status']) ? $discord_entry['status'] : 'completed',
                'discord_username' => isset($discord_entry['discord_username']) ? $discord_entry['discord_username'] : '',
            );
            $user_treasury_entries[] = $formatted_entry;
        }
    }
}
// Use BCMath-safe formatter when available. Prefer a raw stored string if available to avoid float precision loss.
// Note: This variable will be used later in the HTML template, not echoed here
$available_xp_raw = get_user_meta($user_id, 'available_xp_raw', true);
if (!empty($available_xp_raw) && is_string($available_xp_raw)) {
    $available_xp_str = $available_xp_raw;
} else {
    $available_xp_str = isset($available_xp_str) ? $available_xp_str : (string) $available_xp;
}

// Get and add Talent Show entries
$talentshow_entry_raw = get_user_meta($user_id, '_talentshow_entry', false);  // Get all rows
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
                $xp_units = is_string($talent_entry['xp_units']) ? ($talent_entry['xp_units']) : (string)($talent_entry['xp_units']);
            } elseif (isset($talent_entry['xp_awarded'])) {
                // xp_awarded is stored as XP directly
                $xp_units = is_string($talent_entry['xp_awarded']) ? ($talent_entry['xp_awarded']) : (string)($talent_entry['xp_awarded']);
            } else {
                $xp_units = '0';
            }
            
            // Ensure xp_units is a string for BCMath compatibility
            if (!is_string($xp_units)) {
                $xp_units = (string)$xp_units;
            }
            
            // Store xp_awarded for display reference
            $xp_awarded_yam = isset($talent_entry['xp_awarded']) ? intval($talent_entry['xp_awarded']) : 0;

            // Calculate USD from XP using new conversion
            $xp_units_float = floatval($xp_units);
            $trade_value_usd = $xp_units_float > 0 ? dongtrader_xp_to_usd($xp_units) : 0;
            // Calculate YAM from XP (1 USD = 21,000 YAM = 10^23 XP)
            $yam_value = $xp_units_float > 0 ? dongtrader_xp_to_yam($xp_units) : 0;

            $formatted_entry = array(
                'source' => 'talentshow_entry',
                'role' => 'Talent Show',
                'timestamp' => isset($talent_entry['submission_date']) ? $talent_entry['submission_date'] : current_time('mysql'),
                'proof_id' => 'talentshow_' . (isset($talent_entry['performance_type']) ? sanitize_title($talent_entry['performance_type']) : 'entry'),
                'xp_units' => $xp_units,
                'xp_display_value' => $xp_awarded_yam,  // Store YAM value for display in XP Minted column
                'yam_value' => $yam_value,
                'trade_value_usd' => $trade_value_usd,
                'trade_value' => $trade_value_usd,
                'status' => isset($talent_entry['status']) ? $talent_entry['status'] : 'submitted',
                'scan_status' => isset($talent_entry['status']) ? $talent_entry['status'] : 'submitted',
                'performance_type' => isset($talent_entry['performance_type']) ? $talent_entry['performance_type'] : '',
            );
            $user_treasury_entries[] = $formatted_entry;
        }
    }
}

// Get and add Discord Poll entries
$discord_poll_raw = get_user_meta($user_id, '_discord_poll', false);  // Get all rows
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
                $xp_units = is_string($poll_entry['xp_units']) ? ($poll_entry['xp_units']) : (string)($poll_entry['xp_units']);
            } elseif (isset($poll_entry['xp_awarded'])) {
                // xp_awarded is stored as XP directly
                $xp_units = is_string($poll_entry['xp_awarded']) ? ($poll_entry['xp_awarded']) : (string)($poll_entry['xp_awarded']);
            } else {
                $xp_units = '0';
            }
            
            // Ensure xp_units is a string for BCMath compatibility
            if (!is_string($xp_units)) {
                $xp_units = (string)$xp_units;
            }
            
            // Store xp_awarded for display reference
            $xp_awarded_yam = isset($poll_entry['xp_awarded']) ? intval($poll_entry['xp_awarded']) : 0;

            // Calculate USD from XP using new conversion
            $xp_units_float = floatval($xp_units);
            $trade_value_usd = $xp_units_float > 0 ? dongtrader_xp_to_usd($xp_units) : 0;
            // Calculate YAM from XP (1 USD = 21,000 YAM = 10^23 XP)
            $yam_value = $xp_units_float > 0 ? dongtrader_xp_to_yam($xp_units) : 0;

            $formatted_entry = array(
                'source' => 'discord_poll',
                'role' => 'Discord Poll',
                'timestamp' => isset($poll_entry['vote_date']) ? $poll_entry['vote_date'] : (isset($poll_entry['submission_date']) ? $poll_entry['submission_date'] : current_time('mysql')),
                'proof_id' => 'poll_' . (isset($poll_entry['poll_id']) ? $poll_entry['poll_id'] : 'entry'),
                'xp_units' => $xp_units,
                'xp_display_value' => $xp_awarded_yam,  // Store YAM value for display in XP Minted column
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
$total_trade_value = 0;
$confirmed_deliveries = 0;
$confirmed_proof_ids = array();  // Track unique proof_ids with confirmed status

// Breakdown by role
$buyer_xp = 0;
$buyer_yam = 0;
$buyer_trade_value = 0;
$buyer_count = 0;

$seller_xp = 0;
$seller_yam = 0;
$seller_trade_value = 0;
$seller_count = 0;

$personal_xp = 0;
$personal_yam = 0;
$personal_trade_value = 0;
$personal_count = 0;

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
        // Check if scan_status is confirmed
        $scan_status = isset($entry['scan_status']) ? $entry['scan_status'] : '';
        if ($scan_status !== 'confirmed') {
            // Skip this entry - not confirmed
            continue;
        }
    }

    // Track unique proof_ids with confirmed status
    // Count confirmed deliveries: personal_scan, confirmed seller/buyer scans, and completed Discord/Talent/Poll entries
    $entry_source = isset($entry['source']) ? $entry['source'] : '';
    $scan_status = isset($entry['scan_status']) ? $entry['scan_status'] : '';
    
    // Count personal_scan entries as confirmed (xp_transfer already excluded above)
    if ($entry_source === 'personal_scan') {
        $proof_id = isset($entry['proof_id']) && !empty($entry['proof_id'])
            ? $entry['proof_id']
            : 'personal_' . (isset($entry['timestamp']) ? $entry['timestamp'] : md5(serialize($entry)));
        if (!in_array($proof_id, $confirmed_proof_ids)) {
            $confirmed_proof_ids[] = $proof_id;
            $confirmed_deliveries++;
        }
    }
    // Count seller_scan and buyer_scan entries with confirmed status
    elseif (in_array($entry_source, array('seller_scan', 'buyer_scan')) && $scan_status === 'confirmed') {
        $proof_id = isset($entry['proof_id']) && !empty($entry['proof_id']) ? $entry['proof_id'] : null;
        if ($proof_id && !in_array($proof_id, $confirmed_proof_ids)) {
            $confirmed_proof_ids[] = $proof_id;
            $confirmed_deliveries++;
        }
    }
    // Count Discord invite, Talent Show, and Discord Poll entries as confirmed (they represent completed actions)
    elseif (in_array($entry_source, array('discord_invite', 'talentshow_entry', 'discord_poll'))) {
        $proof_id = isset($entry['proof_id']) && !empty($entry['proof_id']) ? $entry['proof_id'] : null;
        if ($proof_id && !in_array($proof_id, $confirmed_proof_ids)) {
            $confirmed_proof_ids[] = $proof_id;
            $confirmed_deliveries++;
        }
    }

    // Get values - NEW CONVERSION: XP is primary, calculate USD from XP
    $xp = isset($entry['xp_units']) ? ($entry['xp_units']) : 0;
    // Calculate trade_value_usd from XP using new conversion
    if ($xp > 0) {
        $trade_usd = dongtrader_xp_to_usd($xp);
    } elseif (isset($entry['trade_value_usd']) && floatval($entry['trade_value_usd']) > 0) {
        // Fallback: use stored trade_value_usd if XP not available
        $trade_usd = floatval($entry['trade_value_usd']);
    } else {
        // Legacy: calculate from YAM if available
        $stored_yam = isset($entry['yam_value']) ? floatval($entry['yam_value']) : 0;
        if ($stored_yam > 0) {
            // Convert YAM to XP (1 YAM = 10^23 XP)
            $xp_string = dongtrader_yam_to_xp($stored_yam);
            $xp = floatval($xp_string);
            $trade_usd = dongtrader_xp_to_usd($xp);
        } else {
            $trade_usd = 0;
        }
    }

    // Calculate YAM for display using new conversion (1 USD = 21,000 YAM = 10^23 XP)
    $yam = $xp > 0 ? dongtrader_xp_to_yam($xp) : 0;

    // Base trade value is $10.00
    $trade_val = isset($entry['trade_value']) ? floatval($entry['trade_value']) : 10.0;

    $role = isset($entry['role']) ? strtolower($entry['role']) : '';
    // Add to totals (only confirmed entries reach here for seller_scan, buyer_scan, personal_scan)
    // Use bcmath-safe functions
    if (function_exists('dongtrader_num_add')) {
        $total_xp = dongtrader_num_add($total_xp, $xp, 30);
    } elseif (extension_loaded('bcmath')) {
        $total_xp = bcadd($total_xp, $xp, 30);
    } else {
        $total_xp = (string)(floatval($total_xp) + floatval($xp));
    }
    $total_yam += $yam;
    $total_trade_value_usd += $trade_usd;
    $total_trade_value += $trade_val;

    // Breakdown by role (only confirmed entries for seller_scan, buyer_scan, personal_scan)
    if (strpos($role, 'buyer') !== false || strpos($role, '7%') !== false) {
        if (function_exists('dongtrader_num_add')) {
            $buyer_xp = dongtrader_num_add($buyer_xp, $xp, 20);
            $buyer_yam = dongtrader_num_add($buyer_yam, $yam, 20);
        } elseif (extension_loaded('bcmath')) {
            $buyer_xp = bcadd($buyer_xp, $xp, 20);
            $buyer_yam = bcadd($buyer_yam, $yam, 20);
        } else {
            $buyer_xp = (string)(floatval($buyer_xp) + floatval($xp));
            $buyer_yam = (string)(floatval($buyer_yam) + floatval($yam));
        }
        $buyer_trade_value += $trade_usd;
        $buyer_count++;
    } elseif (strpos($role, 'seller') !== false || strpos($role, '3%') !== false) {
        if (function_exists('dongtrader_num_add')) {
            $seller_xp = dongtrader_num_add($seller_xp, $xp, 20);
            $seller_yam = dongtrader_num_add($seller_yam, $yam, 20);
        } elseif (extension_loaded('bcmath')) {
            $seller_xp = bcadd($seller_xp, $xp, 20);
            $seller_yam = bcadd($seller_yam, $yam, 20);
        } else {
            $seller_xp = (string)(floatval($seller_xp) + floatval($xp));
            $seller_yam = (string)(floatval($seller_yam) + floatval($yam));
        }
        $seller_trade_value += $trade_usd;
        $seller_count++;
    } elseif (strpos($role, 'personal') !== false || strpos($role, '10%') !== false) {
        if (function_exists('dongtrader_num_add')) {
            $personal_xp = dongtrader_num_add($personal_xp, $xp, 20);
            $personal_yam = dongtrader_num_add($personal_yam, $yam, 20);
            $personal_trade_value = dongtrader_num_add($personal_trade_value, $trade_usd, 20);
        } elseif (extension_loaded('bcmath')) {
            $personal_xp = bcadd($personal_xp, $xp, 20);
            $personal_yam = bcadd($personal_yam, $yam, 20);
            $personal_trade_value = bcadd($personal_trade_value, $trade_usd, 20);
        } else {
            $personal_xp = (string)(floatval($personal_xp) + floatval($xp));
            $personal_yam = (string)(floatval($personal_yam) + floatval($yam));
            $personal_trade_value = (string)(floatval($personal_trade_value) + floatval($trade_usd));
        }
        $personal_count++;
    }
}

// Fetch XP transactions to calculate available balance
global $wpdb;
$table_name = $wpdb->prefix . 'xp_transactions';
$user_transactions = $wpdb->get_results($wpdb->prepare("
    SELECT xp_amount, sender_id, receiver_id
    FROM {$table_name}
    WHERE sender_id = %d OR receiver_id = %d
", $user_id, $user_id), ARRAY_A);

$total_xp_sent = '0';
$total_xp_received = '0';

if (is_array($user_transactions)) {
    foreach ($user_transactions as $trans) {
        $xp_amt = (string) $trans['xp_amount'];
        $trans_sender_id = intval($trans['sender_id']);
        $trans_receiver_id = intval($trans['receiver_id']);

        if ($trans_sender_id === $user_id) {
            if (function_exists('dongtrader_num_add')) {
                $total_xp_sent = dongtrader_num_add($total_xp_sent, $xp_amt, 20);
            } elseif (extension_loaded('bcmath')) {
                $total_xp_sent = bcadd($total_xp_sent, $xp_amt, 20);
            } else {
                $total_xp_sent = (string)(floatval($total_xp_sent) + floatval($xp_amt));
            }
        } elseif ($trans_receiver_id === $user_id) {
            if (function_exists('dongtrader_num_add')) {
                $total_xp_received = dongtrader_num_add($total_xp_received, $xp_amt, 20);
            } elseif (extension_loaded('bcmath')) {
                $total_xp_received = bcadd($total_xp_received, $xp_amt, 20);
            } else {
                $total_xp_received = (string)(floatval($total_xp_received) + floatval($xp_amt));
            }
        }
    }
}

// Calculate available XP: (All sources + XP received) - XP transfer
// Formula: XP Balance = (_discord_invite + _talentshow_entry + _discord_poll + seller_scan + buyer_scan + personal_scan + xp_received) - xp_transfer
if (function_exists('dongtrader_num_add') && function_exists('dongtrader_num_sub')) {
    $available_xp = dongtrader_num_add($total_xp, $total_xp_received, 20);
    $available_xp = dongtrader_num_sub($available_xp, $total_xp_sent, 20);
} elseif (extension_loaded('bcmath')) {
    $available_xp = bcadd($total_xp, $total_xp_received, 20);
    $available_xp = bcsub($available_xp, $total_xp_sent, 20);
} else {
    $available_xp = (string)((floatval($total_xp) + floatval($total_xp_received)) - floatval($total_xp_sent));
}

if ($available_xp < 0) {
    $available_xp = 0;
}

// Helper function to format numbers in scientific notation (e.g., "1.03 × 10²³")
function format_xp_scientific_wallet($numStr)
{
    if ($numStr === null)
        return '0';

    // Force string
    $numStr = trim((string) $numStr);

    // Zero?
    if (preg_match('/^0+(\.0+)?$/', $numStr)) {
        return '0';
    }

    // Split integer/decimal parts
    if (strpos($numStr, '.') !== false) {
        list($intPart, $decPart) = explode('.', $numStr, 2);
    } else {
        $intPart = $numStr;
        $decPart = '';
    }

    // Remove leading zeros in integer part
    $intPartTrimmed = ltrim($intPart, '0');

    // Case 1: number >= 1
    if ($intPartTrimmed !== '') {
        // exponent = digit position
        $exponent = strlen($intPartTrimmed) - 1;

        $digits = $intPartTrimmed . $decPart;
        // Safety check: ensure we have at least one digit
        if (strlen($digits) === 0) {
            return '0';
        }
        $mantissa = substr($digits, 0, 1);
        $rest = substr($digits, 1);

        if ($rest !== '') {
            $mantissa .= '.' . $rest;
        }
    } else {
        // Number < 1 (e.g. 0.000002)

        // count leading zeros in decimals
        $zeroCount = strspn($decPart, '0');
        
        // Safety check: ensure decPart has enough characters
        if (strlen($decPart) === 0 || $zeroCount >= strlen($decPart)) {
            return '0';
        }

        $exponent = -($zeroCount + 1);
        
        // Safety check before array access
        if (!isset($decPart[$zeroCount])) {
            return '0';
        }

        $mantissa = $decPart[$zeroCount];
        $rest = substr($decPart, $zeroCount + 1);

        if ($rest !== '') {
            $mantissa .= '.' . $rest;
        }
    }

    // Cleanup trailing zeros and dot
    $mantissa = rtrim($mantissa, '0');
    $mantissa = rtrim($mantissa, '.');
    
    // Ensure mantissa is not empty
    if (empty($mantissa) || $mantissa === '') {
        return '0';
    }
    
    // Ensure exponent is set
    if (!isset($exponent)) {
        return '0';
    }

    $result = $mantissa . ' × 10<sup>' . $exponent . '</sup>';
    return is_string($result) && $result !== '' ? $result : '0';
}

// Recalculate YAM and USD based on available XP - NEW CONVERSION
// USD = XP / 10^23 (using new conversion function)
// Convert to string for large numbers to maintain precision
$available_xp_str = (string) $available_xp;
$available_usd_trade_value = $available_xp > 0 ? dongtrader_xp_to_usd($available_xp_str) : 0;
// NEW CONVERSION: 1 USD = 21,000 YAM = 10^23 XP
$available_yam = $available_xp > 0 ? dongtrader_xp_to_yam($available_xp_str) : 0;

// Calculate leaderboard position (get all users and rank from usermeta)
$all_users_xp = array();

// Get all seller_scan, buyer_scan, and personal_scan data
$all_scan_meta = $wpdb->get_results(
    "SELECT user_id, meta_key, meta_value FROM {$wpdb->usermeta} 
     WHERE meta_key IN ('seller_scan', 'buyer_scan', 'personal_scan')"
);

foreach ($all_scan_meta as $meta) {
    $uid = intval($meta->user_id);
    if ($uid > 0) {
        if (!isset($all_users_xp[$uid])) {
            $all_users_xp[$uid] = 0;
        }

        $scan_data = maybe_unserialize($meta->meta_value);
        if (is_array($scan_data)) {
            foreach ($scan_data as $entry) {
                if (is_array($entry)) {
                    $xp = isset($entry['xp_units']) ? floatval($entry['xp_units']) : 0;
                    if (isset($entry['scan_status']) && $entry['scan_status'] === 'confirmed') {
                        $all_users_xp[$uid] += $xp;
                    }
                }
            }
        }
    }
}

arsort($all_users_xp);
$user_rank = array_search($user_id, array_keys($all_users_xp)) + 1;
if ($user_rank === false || $user_rank === 0) {
    $user_rank = count($all_users_xp) + 1;
}

// Check PBTV eligibility (Top 30 on August 11, 2026)
$pbtv_eligible = ($user_rank <= 30) ? true : false;
$pbtv_snapshot_date = '2026-08-11';
$laugh_end_date = '2026-08-31';
$detente_2030_date = '2030-08-31';

extract($args);
$cs = get_woocommerce_currency_symbol();
?>

<style>
    /* --- XP WALLET UI --- */
    .detente-wallet {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        font-family: "Inter", system-ui, sans-serif;
        color: #1f2937;
    }

    /* Headings */
    .detente-wallet h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #111827;
    }

    /* LAUGH Mode Banner */
    .laugh-mode-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: linear-gradient(90deg, #047857, #059669);
        color: #ecfdf5;
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .laugh-mode-banner .status-indicator {
        height: 10px;
        width: 10px;
        border-radius: 50%;
        background: #a7f3d0;
        margin-top: 6px;
        box-shadow: 0 0 6px #bbf7d0;
    }

    /* Wallet Summary Grid */
    .wallet-summary-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 32px;
    }

    .wallet-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        transition: all 0.25s ease;
        flex: 1 1 200px;
        min-width: 200px;
        max-width: 100%;
    }

    .wallet-card:hover {
        transform: translateY(-3px);
        border-color: #d1fae5;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15);
    }

    .wallet-card h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .wallet-card .value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #065f46;
    }

    .wallet-card .sub-value {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 3px;
    }

    /* User Info */
    .user-info-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .user-info-section h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px 20px;
    }

    .info-item {
        font-size: 0.9rem;
    }

    .info-label {
        font-weight: 600;
        color: #6b7280;
    }

    .info-value {
        color: #111827;
    }

    .pbtv-badge {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 12px;
        display: inline-block;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    /* Role Breakdown */
    .role-breakdown {
        margin-bottom: 30px;
    }

    .role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .role-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: 0.25s;
    }

    .role-item:hover {
        border-color: #d1d5db;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }

    .role-label {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 5px;
    }

    .role-item.buyer .role-label {
        color: #2563eb;
    }

    .role-item.seller .role-label {
        color: #10b981;
    }

    .role-item.personal .role-label {
        color: #f59e0b;
    }

    .role-value {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }

    /* Transaction Table */
    .transaction-history {
        margin-bottom: 30px;
    }

    .transaction-history h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        font-size: 0.9rem;
    }

    .transaction-table th {
        background: #f3f4f6;
        color: #374151;
        text-align: left;
        padding: 10px 12px;
        font-weight: 600;
    }

    .transaction-table td {
        border-top: 1px solid #e5e7eb;
        padding: 10px 12px;
        color: #111827;
    }

    .transaction-table tr:hover {
        background: #f9fafb;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-badge.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.confirmed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.minted {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.locked {
        background: #e0e7ff;
        color: #3730a3;
    }

    /* Transaction Pagination */
    .transaction-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        margin-top: 32px;
        padding: 20px 0;
    }

    .transaction-pagination .pagination-link,
    .transaction-pagination .pagination-number,
    .transaction-pagination a.pagination-link,
    .transaction-pagination a.pagination-number {
        padding: 10px 18px;
        background: #ffffff;
        color: #4b5563;
        text-decoration: none;
        border-radius: 6px;
        border: 1.5px solid #e5e7eb;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        min-width: 42px;
        height: 42px;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: inherit;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
    }

    .transaction-pagination .pagination-link::before,
    .transaction-pagination a.pagination-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .transaction-pagination .pagination-link:hover::before,
    .transaction-pagination a.pagination-link:hover::before {
        left: 100%;
    }

    .transaction-pagination .pagination-link:hover:not(.disabled),
    .transaction-pagination .pagination-number:hover:not(.current),
    .transaction-pagination a.pagination-link:hover:not(.disabled),
    .transaction-pagination a.pagination-number:hover:not(.current) {
        background: #f9fafb;
        border-color: #d1d5db;
        color: #1f2937;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .transaction-pagination .pagination-link:active:not(.disabled),
    .transaction-pagination .pagination-number:active:not(.current),
    .transaction-pagination a.pagination-link:active:not(.disabled),
    .transaction-pagination a.pagination-number:active:not(.current) {
        transform: translateY(0);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .transaction-pagination .pagination-number.current {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border-color: #667eea;
        font-weight: 600;
        cursor: default;
        box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3), 0 2px 4px -1px rgba(102, 126, 234, 0.2);
        transform: scale(1.05);
    }

    .transaction-pagination .pagination-link.disabled {
        background: #f3f4f6;
        color: #9ca3af;
        border-color: #e5e7eb;
        cursor: not-allowed;
        opacity: 0.7;
        box-shadow: none;
        transform: none;
    }

    .transaction-pagination .pagination-link.disabled:hover {
        transform: none;
        box-shadow: none;
        background: #f3f4f6;
        border-color: #e5e7eb;
    }

    .transaction-pagination .pagination-pages {
        display: flex;
        gap: 6px;
        align-items: center;
        margin: 0 8px;
    }

    .transaction-pagination .pagination-ellipsis {
        padding: 10px 8px;
        color: #6b7280;
        font-size: 14px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 42px;
        height: 42px;
    }

    .pagination-info {
        margin-top: 12px;
        text-align: center;
        color: #6b7280;
        font-size: 14px;
        padding: 8px 0;
    }

    .transaction-table.loading {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }

    .transaction-table.loading tbody::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid #e5e7eb;
        border-top-color: #10b981;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    /* Disclaimer */
    .disclaimer-box {
        background: #f3f4f6;
        border-left: 4px solid #10b981;
        padding: 15px 20px;
        border-radius: 8px;
        color: #374151;
        font-size: 0.9rem;
    }

    .disclaimer-box strong {
        color: #065f46;
    }
</style>
<style>
    /* --- XP WALLET UI --- */
    .detente-wallet {
        background: linear-gradient(135deg, #f9fafb, #f3f4f6);
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        font-family: "Inter", system-ui, sans-serif;
        color: #1f2937;
    }

    /* Headings */
    .detente-wallet h2 {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #111827;
    }

    /* LAUGH Mode Banner */
    .laugh-mode-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        background: linear-gradient(90deg, #047857, #059669);
        color: #ecfdf5;
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }

    .laugh-mode-banner .status-indicator {
        height: 10px;
        width: 10px;
        border-radius: 50%;
        background: #a7f3d0;
        margin-top: 6px;
        box-shadow: 0 0 6px #bbf7d0;
    }

    /* Wallet Summary Grid */
    .wallet-summary-grid {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 16px;
        margin-bottom: 32px !important;
    }

    .wallet-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        transition: all 0.25s ease;
        flex: 1 1 200px;
        min-width: 200px;
        max-width: 100%;
    }

    .wallet-card:hover {
        transform: translateY(-3px);
        border-color: #d1fae5;
        box-shadow: 0 4px 10px rgba(16, 185, 129, 0.15);
    }

    .wallet-card h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .wallet-card .value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #065f46;
    }

    .wallet-card .sub-value {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 3px;
    }

    /* User Info */
    .user-info-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .user-info-section h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 10px 20px;
    }

    .info-item {
        font-size: 0.9rem;
    }

    .info-label {
        font-weight: 600;
        color: #6b7280;
    }

    .info-value {
        color: #111827;
    }

    .pbtv-badge {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
        padding: 8px 12px;
        margin-top: 12px;
        display: inline-block;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    /* Role Breakdown */
    .role-breakdown {
        margin-bottom: 30px;
    }

    .role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
    }

    .role-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        transition: 0.25s;
    }

    .role-item:hover {
        border-color: #d1d5db;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
    }

    .role-label {
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 5px;
    }

    .role-item.buyer .role-label {
        color: #2563eb;
    }

    .role-item.seller .role-label {
        color: #10b981;
    }

    .role-item.personal .role-label {
        color: #f59e0b;
    }

    .role-value {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
    }

    /* Transaction Table */
    .transaction-history {
        margin-bottom: 30px;
    }

    .transaction-history h3 {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .transaction-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        font-size: 0.9rem;
    }

    .transaction-table th {
        background: #f3f4f6;
        color: #374151;
        text-align: left;
        padding: 10px 12px;
        font-weight: 600;
    }

    .transaction-table td {
        border-top: 1px solid #e5e7eb;
        padding: 10px 12px;
        color: #111827;
    }

    .transaction-table tr:hover {
        background: #f9fafb;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-badge.pending {
        background: #fef3c7;
        color: #92400e;
    }

    .status-badge.confirmed {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.minted {
        background: #d1fae5;
        color: #065f46;
    }

    .status-badge.locked {
        background: #e0e7ff;
        color: #3730a3;
    }

    /* Disclaimer */
    .disclaimer-box {
        background: #f3f4f6;
        border-left: 4px solid #10b981;
        padding: 15px 20px;
        border-radius: 8px;
        color: #374151;
        font-size: 0.9rem;
    }

    .disclaimer-box strong {
        color: #065f46;
    }
</style>


<div class="detente-wallet cpm-table-wrap">
    <h2 style="margin-bottom: 20px; color: #1f2937;"><?php esc_html_e('XP Wallet', 'cpm-dongtrader'); ?></h2>

    <!-- LAUGH Mode Banner -->
    <div class="laugh-mode-banner">
        <span class="status-indicator"></span>
        <div>
            <strong style="font-size: 16px;">LAUGH Mode Active</strong>
            <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.95;">
                Trade credits only until <?php echo esc_html($laugh_end_date); ?> • No money moves — trade value accrues
                until <?php echo esc_html($detente_2030_date); ?>
            </p>
        </div>
    </div>

    <!-- Wallet Summary Cards -->
    <div class="wallet-summary-grid" style="display:flex; flex-wrap:wrap; gap:15px; margin-top:20px;">

        <!-- XP Balance -->
        <div class="wallet-card" style="flex:1 1 250px; background:#ffffff; border:1px solid #e5e7eb; 
        padding:18px; border-radius:10px; box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

            <h4 style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#6b7280;">
                <?php esc_html_e('XP Balance', 'cpm-dongtrader'); ?>
            </h4>

            <div class="value" style="font-size:22px; font-weight:700; color:#111827; margin-bottom:5px;">
                <?php echo format_xp_scientific_wallet($available_xp); ?>
            </div>

            <div class="sub-value" style="font-size:12px; color:#6b7280;">
                <?php esc_html_e('Available XP (after transfers)', 'cpm-dongtrader'); ?>
            </div>
        </div>


        <!-- YAM Equivalent -->
        <div class="wallet-card" style="flex:1 1 250px; background:#ffffff; border:1px solid #e5e7eb;
        padding:18px; border-radius:10px; box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

            <h4 style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#6b7280;">
                <?php esc_html_e('YAM Equivalent', 'cpm-dongtrader'); ?>
            </h4>

            <div class="value" style="font-size:22px; font-weight:700; color:#111827; margin-bottom:5px;">
                <?php
                // Display YAM in regular decimal notation (not scientific notation)
                if ($available_yam > 0 && is_numeric($available_yam)) {
                    // Check if it's a whole number
                    if ($available_yam == floor($available_yam)) {
                        // Whole number - display without decimals
                        echo esc_html(number_format($available_yam, 0));
                    } elseif ($available_yam >= 1) {
                        // For values >= 1 with decimals, show with 2 decimal places
                        echo esc_html(number_format($available_yam, 2));
                    } elseif ($available_yam >= 0.01) {
                        // For values >= 0.01, show with 4 decimal places
                        echo esc_html(number_format($available_yam, 4));
                    } else {
                        // For very small values, show with 6 decimal places
                        echo esc_html(number_format($available_yam, 6));
                    }
                } else {
                    echo '0';
                }
                ?>
            </div>

            <div class="sub-value" style="font-size:12px; color:#6b7280;">
                <?php esc_html_e('1 USD = 21,000 YAM = 10²³ XP', 'cpm-dongtrader'); ?>
            </div>
        </div>


        <!-- Confirmed Deliveries -->
        <div class="wallet-card" style="flex:1 1 250px; background:#ffffff; border:1px solid #e5e7eb;
        padding:18px; border-radius:10px; box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

            <h4 style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#6b7280;">
                <?php esc_html_e('Confirmed Deliveries', 'cpm-dongtrader'); ?>
            </h4>

            <div class="value" style="font-size:22px; font-weight:700; color:#111827; margin-bottom:5px;">
                <?php echo $confirmed_deliveries; ?>
            </div>

            <div class="sub-value" style="font-size:12px; color:#6b7280;">
                <?php esc_html_e('2-scan PoDs recorded', 'cpm-dongtrader'); ?>
            </div>
        </div>


        <!-- XP Sent -->
        <div class="wallet-card" style="flex:1 1 250px; background:linear-gradient(135deg,#fff5f5 0%,#ffe6e6 100%);
        border:1px solid #fcd4d4; padding:18px; border-radius:10px; 
        box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

            <h4 style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#b91c1c;">
                <?php esc_html_e('XP Sent', 'cpm-dongtrader'); ?>
            </h4>

            <div class="value" style="font-size:22px; font-weight:700; color:#dc2626; margin-bottom:5px;">
                <?php echo format_xp_scientific_wallet($total_xp_sent); ?>
            </div>

            <div class="sub-value" style="font-size:12px; color:#b91c1c;">
                <?php esc_html_e('Total transferred out', 'cpm-dongtrader'); ?>
            </div>
        </div>


        <!-- XP Received -->
        <div class="wallet-card" style="flex:1 1 250px; background:linear-gradient(135deg,#f0fff4 0%,#dcfce7 100%);
        border:1px solid #bbf7d0; padding:18px; border-radius:10px; 
        box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

            <h4 style="margin:0 0 8px 0; font-size:14px; font-weight:600; color:#065f46;">
                <?php esc_html_e('XP Received', 'cpm-dongtrader'); ?>
            </h4>

            <div class="value" style="font-size:22px; font-weight:700; color:#059669; margin-bottom:5px;">
                <?php echo format_xp_scientific_wallet($total_xp_received); ?>
            </div>

            <div class="sub-value" style="font-size:12px; color:#065f46;">
                <?php esc_html_e('Total received from others', 'cpm-dongtrader'); ?>
            </div>
        </div>

    </div>

    <!-- User Information -->
  <div class="user-info-section" 
    style="background:#ffffff; border:1px solid #e5e7eb; 
    padding:20px; border-radius:10px; margin-top:25px; 
    box-shadow:0px 2px 8px rgba(0,0,0,0.07);">

    <h3 style="margin:0 0 15px 0; font-size:18px; font-weight:700; color:#111827;">
        <?php esc_html_e('Wallet Information', 'cpm-dongtrader'); ?>
    </h3>

    <div class="info-grid" 
        style="display:flex; flex-wrap:wrap; gap:12px;">

        <!-- Holder -->
        <div class="info-item" 
            style="flex:1 1 180px; background:#f9fafb; padding:10px 14px; 
            border-radius:8px; border:1px solid #e5e7eb;">
            <div class="info-label" 
                style="font-size:12px; color:#6b7280; margin-bottom:3px;">
                <?php esc_html_e('Holder', 'cpm-dongtrader'); ?>
            </div>
            <div class="info-value" 
                style="font-size:15px; font-weight:600; color:#111827;">
                <?php echo esc_html($user_name); ?>
            </div>
        </div>

        <?php if ($user_phone): ?>
            <div class="info-item"
                style="flex:1 1 180px; background:#f9fafb; padding:10px 14px; 
                border-radius:8px; border:1px solid #e5e7eb;">
                <div class="info-label" 
                    style="font-size:12px; color:#6b7280; margin-bottom:3px;">
                    <?php esc_html_e('Phone', 'cpm-dongtrader'); ?>
                </div>
                <div class="info-value" 
                    style="font-size:15px; font-weight:600; color:#111827;">
                    <?php echo esc_html($user_phone); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_fonepay): ?>
            <div class="info-item"
                style="flex:1 1 180px; background:#f9fafb; padding:10px 14px; 
                border-radius:8px; border:1px solid #e5e7eb;">
                <div class="info-label" 
                    style="font-size:12px; color:#6b7280; margin-bottom:3px;">
                    <?php esc_html_e('FonePay ID', 'cpm-dongtrader'); ?>
                </div>
                <div class="info-value" 
                    style="font-size:15px; font-weight:600; color:#111827;">
                    <?php echo esc_html($user_fonepay); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_poc): ?>
            <div class="info-item"
                style="flex:1 1 180px; background:#f9fafb; padding:10px 14px; 
                border-radius:8px; border:1px solid #e5e7eb;">
                <div class="info-label" 
                    style="font-size:12px; color:#6b7280; margin-bottom:3px;">
                    <?php esc_html_e('POC', 'cpm-dongtrader'); ?>
                </div>
                <div class="info-value" 
                    style="font-size:15px; font-weight:600; color:#111827;">
                    <?php echo esc_html($user_poc); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Leaderboard Rank -->
        <div class="info-item"
            style="flex:1 1 180px; background:#f9fafb; padding:10px 14px; 
            border-radius:8px; border:1px solid #e5e7eb;">
            <div class="info-label" 
                style="font-size:12px; color:#6b7280; margin-bottom:3px;">
                <?php esc_html_e('Leaderboard Rank', 'cpm-dongtrader'); ?>
            </div>
            <div class="info-value" 
                style="font-size:15px; font-weight:700; color:#1d4ed8;">
                #<?php echo $user_rank; ?>
            </div>
        </div>
    </div>

    <?php if ($pbtv_eligible): ?>
        <div class="pbtv-badge"
            style="margin-top:18px; background:linear-gradient(135deg,#fff8e1,#ffefc4);
            border:1px solid #ffe29a; padding:10px 14px; border-radius:8px;
            font-size:14px; font-weight:600; color:#8a5800;">
            🏆 <?php esc_html_e('PBTV NFT Eligible', 'cpm-dongtrader'); ?> —
            <?php esc_html_e('Top 30 on', 'cpm-dongtrader'); ?>
            <?php echo esc_html($pbtv_snapshot_date); ?>
        </div>
    <?php endif; ?>

</div>


    <!-- XP Breakdown by Role -->
  <div class="role-breakdown" 
    style="background:#ffffff; border:1px solid #e5e7eb; 
    padding:20px; border-radius:10px; margin-top:25px;
    box-shadow:0 2px 8px rgba(0,0,0,0.06);">

    <h3 style="margin:0 0 18px 0; font-size:18px; font-weight:700; color:#111827;">
        <?php esc_html_e('XP Breakdown by Role', 'cpm-dongtrader'); ?>
    </h3>

    <div class="role-grid" style="display:flex; flex-wrap:wrap; gap:14px;">

        <!-- BUYER -->
        <div class="role-item buyer"
            style="flex:1 1 250px; background:#f0f9ff; padding:15px 18px; 
            border-radius:10px; border:1px solid #bae6fd;">            
            <div class="role-label"
                style="font-size:14px; font-weight:600; color:#0369a1; margin-bottom:6px;">
                <?php esc_html_e('Buyer (7%)', 'cpm-dongtrader'); ?>
            </div>

            <div class="role-value" style="font-size:17px; font-weight:700; color:#0c4a6e;">
                <?php
                $scientific = sprintf('%.2e', $buyer_xp);
                $parts = explode('e', $scientific);
                $mantissa_raw = $parts[0];

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
                    echo ($base_value == floor($base_value)) ? (int) $base_value : $base_value;
                } else {
                    echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                }
                ?> XP
            </div>

            <div style="font-size:12px; color:#475569; margin-top:6px;">
                <?php
                $buyer_trade_value_corrected = $buyer_count * (0.07 * 10.0);
                echo $cs . number_format($buyer_trade_value_corrected, 2);
                ?> • <?php echo $buyer_count; ?> <?php esc_html_e('deliveries', 'cpm-dongtrader'); ?>
            </div>
        </div>

        <!-- SELLER -->
        <div class="role-item seller"
            style="flex:1 1 250px; background:#fef3c7; padding:15px 18px; 
            border-radius:10px; border:1px solid #fde68a;">
            <div class="role-label"
                style="font-size:14px; font-weight:600; color:#b45309; margin-bottom:6px;">
                <?php esc_html_e('Seller (3%)', 'cpm-dongtrader'); ?>
            </div>

            <div class="role-value" style="font-size:17px; font-weight:700; color:#92400e;">
                <?php
                $scientific = sprintf('%.2e', $seller_xp);
                $parts = explode('e', $scientific);
                $mantissa_raw = $parts[0];

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
                    echo ($base_value == floor($base_value)) ? (int) $base_value : $base_value;
                } else {
                    echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                }
                ?> XP
            </div>

            <div style="font-size:12px; color:#6b7280; margin-top:6px;">
                <?php
                $seller_trade_value_corrected = $seller_count * (0.03 * 10.0);
                echo $cs . number_format($seller_trade_value_corrected, 2);
                ?> • <?php echo $seller_count; ?> <?php esc_html_e('deliveries', 'cpm-dongtrader'); ?>
            </div>
        </div>

        <!-- PERSONAL -->
        <div class="role-item personal"
            style="flex:1 1 250px; background:#ecfdf5; padding:15px 18px; 
            border-radius:10px; border:1px solid #bbf7d0;">
            <div class="role-label"
                style="font-size:14px; font-weight:600; color:#047857; margin-bottom:6px;">
                <?php esc_html_e('Personal (10%)', 'cpm-dongtrader'); ?>
            </div>

            <div class="role-value" style="font-size:17px; font-weight:700; color:#065f46;">
                <?php
                $scientific = sprintf('%.2e', $personal_xp);
                $parts = explode('e', $scientific);
                $mantissa_raw = $parts[0];

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
                    echo ($base_value == floor($base_value)) ? (int) $base_value : $base_value;
                } else {
                    echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                }
                ?> XP
            </div>

            <div style="font-size:12px; color:#475569; margin-top:6px;">
                <?php
                $personal_trade_value_corrected = $personal_count * (0.1 * 10.0);
                echo $cs . number_format($personal_trade_value_corrected, 2);
                ?> • <?php echo $personal_count; ?> <?php esc_html_e('deliveries', 'cpm-dongtrader'); ?>
            </div>
        </div>

    </div>
</div>


    <!-- Transaction History -->
    <div class="transaction-history">
        <h3><?php esc_html_e('Transaction History', 'cpm-dongtrader'); ?></h3>
        <?php if (!empty($user_treasury_entries)): ?>
            <div id="transaction-table-wrapper">
                <table class="transaction-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('S.No.', 'cpm-dongtrader'); ?></th>
                            <th><?php esc_html_e('Date', 'cpm-dongtrader'); ?></th>
                            <th><?php esc_html_e('Transaction ID', 'cpm-dongtrader'); ?></th>
                            <th><?php esc_html_e('Role', 'cpm-dongtrader'); ?></th>
                            <th><?php esc_html_e('XP Minted', 'cpm-dongtrader'); ?></th>
                            <th><?php esc_html_e('Status', 'cpm-dongtrader'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="transaction-table-body">
                        <?php
                        // Sort by timestamp (newest first)
                        usort($user_treasury_entries, function ($a, $b) {
                            $time_a = 0;
                            $time_b = 0;

                            // Try timestamp first
                            if (isset($a['timestamp']) && !empty($a['timestamp'])) {
                                $time_a = strtotime($a['timestamp']);
                            } elseif (isset($a['date']) && !empty($a['date'])) {
                                $time_a = strtotime($a['date']);
                            }

                            if (isset($b['timestamp']) && !empty($b['timestamp'])) {
                                $time_b = strtotime($b['timestamp']);
                            } elseif (isset($b['date']) && !empty($b['date'])) {
                                $time_b = strtotime($b['date']);
                            }

                            return $time_b - $time_a;
                        });
                        // Pagination setup
                        $per_page = 8;
                        $current_page = isset($_GET['txn_page']) ? max(1, intval($_GET['txn_page'])) : 1;
                        $total_entries = count($user_treasury_entries);
                        $total_pages = ceil($total_entries / $per_page);
                        $offset = ($current_page - 1) * $per_page;
                        $paginated_entries = array_slice($user_treasury_entries, $offset, $per_page);

                        $serial_number = $offset + 1;  // Start serial number from offset + 1
                        foreach ($paginated_entries as $entry):
                            // Get timestamp - try multiple possible field names
                            $timestamp = '';
                            if (isset($entry['timestamp']) && !empty($entry['timestamp'])) {
                                $timestamp = $entry['timestamp'];
                            } elseif (isset($entry['date']) && !empty($entry['date'])) {
                                $timestamp = $entry['date'];
                            }

                            // Format date
                            if ($timestamp) {
                                // Handle ISO format (with T) or MySQL format
                                $date_obj = strtotime($timestamp);
                                if ($date_obj !== false) {
                                    $date = date('Y-m-d H:i', $date_obj);
                                } else {
                                    $date = 'N/A';
                                }
                            } else {
                                $date = 'N/A';
                            }

                            // Get transaction_id, fallback to proof_id for backward compatibility
                            $transaction_id = '';
                            if (isset($entry['transaction_id']) && !empty($entry['transaction_id'])) {
                                $transaction_id = esc_html($entry['transaction_id']);
                            } elseif (isset($entry['proof_id']) && !empty($entry['proof_id'])) {
                                $transaction_id = esc_html($entry['proof_id']);
                            } else {
                                $transaction_id = 'N/A';
                            }

                            // Format role display
                            $role = isset($entry['role']) ? strtolower($entry['role']) : '';
                            $source = isset($entry['source']) ? strtolower($entry['source']) : '';
                            $role_display = 'N/A';

                            // Check source first for special entries
                            if ($source === 'discord_invite') {
                                $role_display = 'Discord Verification';
                            } elseif ($source === 'talentshow_entry') {
                                $role_display = 'Talent Show';
                            } elseif ($source === 'discord_poll') {
                                $role_display = 'Discord Poll';
                            } elseif (strpos($role, 'seller') !== false) {
                                $role_display = 'Seller (3%)';
                            } elseif (strpos($role, 'buyer') !== false) {
                                $role_display = 'Buyer (7%)';
                            } elseif (strpos($role, 'personal') !== false) {
                                $role_display = 'Personal (10%)';
                            } else {
                                $role_display = isset($entry['role']) ? esc_html(ucfirst($entry['role'])) : 'N/A';
                            }

                            // Get XP units first - preserve original value for precision
                            $xp_raw = isset($entry['xp_units']) ? $entry['xp_units'] : 0;
                            // Convert to string while preserving precision
                            // Handle both numeric and string inputs, including scientific notation
                            if (is_string($xp_raw)) {
                                $xp_str = $xp_raw;
                            } elseif (is_numeric($xp_raw)) {
                                // Convert to string, handling large numbers properly
                                $xp_str = (string) $xp_raw;
                            } else {
                                $xp_str = '0';
                            }
                            // Clean the string to ensure it's a valid number
                            $xp_str = preg_replace('/[^0-9+\-\.eE]/', '', $xp_str);
                            if (empty($xp_str) || !is_numeric($xp_str)) {
                                $xp_str = '0';
                            }
                            
                            // Convert scientific notation to decimal for BCMath (BCMath doesn't support scientific notation)
                            if (stripos($xp_str, 'e') !== false) {
                                $xp_float = floatval($xp_str);
                                // Convert to decimal string without scientific notation
                                // Use number_format with 0 decimals to get full number
                                $xp_str = number_format($xp_float, 0, '.', '');
                            }
                            
                            $xp = floatval($xp_str);
                            
                            // Recalculate YAM from XP using new conversion rate (1 USD = 21,000 YAM = 10^23 XP)
                            // Use string-based calculation for precision with very small values
                            $yam = 0;
                            if ($xp > 0 && $xp_str !== '0' && $xp_str !== '' && is_numeric($xp_str)) {
                                if (function_exists('dongtrader_xp_to_yam_string')) {
                                    try {
                                        // Use BCMath for precise calculation with very small numbers
                                        $yam_str = dongtrader_xp_to_yam_string($xp_str, 30);
                                        if (!empty($yam_str) && is_numeric($yam_str)) {
                                            $yam = floatval($yam_str);
                                        } else {
                                            // Fallback if result is invalid
                                            $yam = dongtrader_xp_to_yam($xp);
                                        }
                                    } catch (Throwable $e) {
                                        // Catch both Exception and Error (PHP 7+) if string calculation fails
                                        $yam = dongtrader_xp_to_yam($xp);
                                    }
                                } else {
                                    // Fallback to regular function
                                    $yam = dongtrader_xp_to_yam($xp);
                                }
                            }

                            // Calculate trade value - try multiple sources
                            $trade_val = 0;
                            if (isset($entry['trade_value_usd']) && floatval($entry['trade_value_usd']) > 0) {
                                $trade_val = floatval($entry['trade_value_usd']);
                            } elseif (isset($entry['trade_value']) && floatval($entry['trade_value']) > 0) {
                                $trade_val = floatval($entry['trade_value']);
                            } elseif ($yam > 0) {
                                // Calculate from YAM (1 USD = 21,000 YAM)
                                $trade_val = dongtrader_yam_to_usd($yam);
                            }

                            // Get status - prioritize scan_status over status
                            $status = 'pending';
                            if (isset($entry['scan_status']) && !empty($entry['scan_status'])) {
                                $status = strtolower($entry['scan_status']);
                            } elseif (isset($entry['status']) && !empty($entry['status'])) {
                                $status = strtolower($entry['status']);
                            }

                            // Format status display text
                            $status_display = '';
                            if ($status === 'pending') {
                                $status_display = 'Waiting for buyer scan';
                            } elseif ($status === 'confirmed') {
                                $status_display = 'Confirmed';
                            } elseif ($status === 'completed') {
                                $status_display = 'Completed';
                            } elseif ($status === 'submitted') {
                                $status_display = 'Submitted';
                            } elseif ($status === 'verified') {
                                $status_display = 'Verified';
                            } else {
                                $status_display = ucfirst($status);
                            }
                            ?>
                            <tr>
                                <td><?php echo $serial_number; ?></td>
                                <td><?php echo esc_html($date); ?></td>
                                <td style="font-family: monospace; font-size: 11px;"><?php echo $transaction_id; ?></td>
                                <td><?php echo esc_html($role_display); ?></td>
                                <td>
                                    <?php
                                    // Display XP in scientific notation
                                    $xp_value = 0;
                                    if (isset($entry['xp_display_value'])) {
                                        $xp_value = floatval($entry['xp_display_value']);
                                    } else {
                                        $xp_value = $xp;
                                    }

                                    if ($xp_value > 0) {
                                        $scientific = sprintf('%.2e', $xp_value);
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
                                            echo ($base_value == floor($base_value)) ? (int) $base_value : $base_value;
                                        } else {
                                            echo $mantissa . ' × 10<sup>' . $exponent . '</sup>';
                                        }
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($status); ?>">
                                        <?php echo esc_html($status_display); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php
                            $serial_number++;  // Increment serial number for next row
                        endforeach;
                        ?>
                    </tbody>
                </table>

                <?php
                // Pagination controls
                if ($total_pages > 1):
                    // Get current URL and build pagination URLs
                    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                    $url_parts = parse_url($current_url);
                    $query_params = array();
                    if (isset($url_parts['query'])) {
                        parse_str($url_parts['query'], $query_params);
                    }

                    // Function to build pagination URL
                    $build_pagination_url = function ($page) use ($query_params, $url_parts) {
                        $query_params['txn_page'] = $page;
                        $path = isset($url_parts['path']) ? $url_parts['path'] : '/';
                        return $path . '?' . http_build_query($query_params);
                    };
                    ?>
                    <div class="transaction-pagination"
                        style="display: flex; flex-direction: row; justify-content: center; align-items: center; gap: 6px; flex-wrap: nowrap; margin-top: 32px; padding: 20px 0;"
                        data-total-pages="<?php echo $total_pages; ?>" data-current-page="<?php echo $current_page; ?>"
                        data-total-entries="<?php echo $total_entries; ?>" data-per-page="<?php echo $per_page; ?>">
                        <?php if ($current_page > 1): ?>
                            <a href="<?php echo esc_url($build_pagination_url($current_page - 1)); ?>"
                                style="padding: 10px 18px; background: #ffffff; color: #4b5563; text-decoration: none; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); flex-shrink: 0;"
                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.color='#1f2937'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                                onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'; this.style.color='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';">
                                ← Previous
                            </a>
                        <?php else: ?>
                            <span
                                style="padding: 10px 18px; background: #f3f4f6; color: #9ca3af; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: not-allowed; opacity: 0.7; min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                ← Previous
                            </span>
                        <?php endif; ?>

                        <div
                            style="display: flex; flex-direction: row; gap: 6px; align-items: center; margin: 0 8px; flex-wrap: nowrap;">
                            <?php
                            // Show page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);

                            if ($start_page > 1):
                                ?>
                                <a href="<?php echo esc_url($build_pagination_url(1)); ?>"
                                    style="padding: 10px 18px; background: #ffffff; color: #4b5563; text-decoration: none; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); flex-shrink: 0;"
                                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.color='#1f2937'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                                    onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'; this.style.color='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                    onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                    onmouseup="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';">
                                    1
                                </a>
                                <?php if ($start_page > 2): ?>
                                    <span
                                        style="padding: 10px 8px; color: #6b7280; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; min-width: 42px; height: 42px; flex-shrink: 0;">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span
                                        style="padding: 10px 18px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; border-radius: 6px; border: 1.5px solid #667eea; font-weight: 600; font-size: 14px; cursor: default; min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3), 0 2px 4px -1px rgba(102, 126, 234, 0.2); transform: scale(1.05); flex-shrink: 0;">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($build_pagination_url($i)); ?>"
                                        style="padding: 10px 18px; background: #ffffff; color: #4b5563; text-decoration: none; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); flex-shrink: 0;"
                                        onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.color='#1f2937'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                                        onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'; this.style.color='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                        onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                        onmouseup="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span
                                        style="padding: 10px 8px; color: #6b7280; font-size: 14px; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; min-width: 42px; height: 42px; flex-shrink: 0;">...</span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($build_pagination_url($total_pages)); ?>"
                                    style="padding: 10px 18px; background: #ffffff; color: #4b5563; text-decoration: none; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); flex-shrink: 0;"
                                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.color='#1f2937'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                                    onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'; this.style.color='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                    onmousedown="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';"
                                    onmouseup="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';">
                                    <?php echo $total_pages; ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="<?php echo esc_url($build_pagination_url($current_page + 1)); ?>"
                                style="padding: 10px 18px; background: #ffffff; color: #4b5563; text-decoration: none; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); flex-shrink: 0;"
                                onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.color='#1f2937'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';"
                                onmouseout="this.style.background='#ffffff'; this.style.borderColor='#e5e7eb'; this.style.color='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 2px 0 rgba(0, 0, 0, 0.05)';">
                                Next →
                            </a>
                        <?php else: ?>
                            <span
                                style="padding: 10px 18px; background: #f3f4f6; color: #9ca3af; border-radius: 6px; border: 1.5px solid #e5e7eb; font-weight: 500; font-size: 14px; cursor: not-allowed; opacity: 0.7; min-width: 42px; height: 42px; text-align: center; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                Next →
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="pagination-info">
                        Showing <span class="pagination-start"><?php echo $offset + 1; ?></span> - <span
                            class="pagination-end"><?php echo min($offset + $per_page, $total_entries); ?></span> of <span
                            class="pagination-total"><?php echo $total_entries; ?></span> transactions
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p style="color: #6b7280; padding: 20px; text-align: center;">
                <?php esc_html_e('No transactions yet. Complete your first 2-scan Proof of Delivery to start earning XP.', 'cpm-dongtrader'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Disclaimer Box -->
    <div class="disclaimer-box">
        <p><strong><?php esc_html_e('Important:', 'cpm-dongtrader'); ?></strong></p>
        <p><?php esc_html_e('Your XP balance reflects action, not money.', 'cpm-dongtrader'); ?></p>
        <p><?php esc_html_e('XP represents your verified 2-scan Proofs of Delivery under the LAUGH Fund system.', 'cpm-dongtrader'); ?>
        </p>
        <p><?php esc_html_e('Until', 'cpm-dongtrader'); ?> <?php echo esc_html($laugh_end_date); ?>,
            <?php esc_html_e('XP remains trade credit only — no cash value, no redemption.', 'cpm-dongtrader'); ?>
        </p>
        <p style="margin-top: 10px;">
            <strong><?php esc_html_e('On', 'cpm-dongtrader'); ?> <?php echo esc_html($pbtv_snapshot_date); ?>:</strong>
            <?php esc_html_e('Top 30 XP wallets receive PBTV NFT minting authority for Detente 2030.', 'cpm-dongtrader'); ?>
        </p>
    </div>
</div>