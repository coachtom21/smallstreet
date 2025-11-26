<?php

add_filter('wp_mail_content_type', function ($content_type) {
    return 'text/html';
});

/**
 * It returns the API credentials for the API name passed to it
 *
 * @param [apiname] The name of the API you're using.
 *
 * @return [$api_utils] API credentials for the API name passed in.
 */

function dongtrader_get_api_cred($apiname)
{
    if (!$apiname) {
        return;
    }

    if ($apiname == 'qrtiger') {
        $api_utils = get_option('dongtrader_api_settings_qrtiger');
    } else if ($apiname == 'glassfrog') {
        $api_utils = get_option('dongtrader_api_settings_glassfrog');
    } else if ($apiname == 'crowdsignal') {
        $api_utils = get_option('dongtrader_api_settings_crowdsignal');
    }
    return $api_utils;
}

/**
 * BCMath-safe addition wrapper.
 */
/**
 * Numeric backend detection and unified arithmetic wrappers.
 * Prefer backends in this order: BCMath (decimals), GMP (big integers for integer ops),
 * Brick\Math BigDecimal (if installed), Decimal extension (if installed), then PHP float fallback.
 */
function dongtrader_num_backend() {
    static $backend = null;
    if ($backend !== null) {
        return $backend;
    }
    if (extension_loaded('bcmath')) {
        $backend = 'bcmath';
    } elseif (extension_loaded('gmp')) {
        $backend = 'gmp';
    } elseif (class_exists('\\Brick\\Math\\BigDecimal')) {
        $backend = 'bigdecimal';
    } elseif (class_exists('Decimal')) {
        $backend = 'decimal';
    } else {
        $backend = 'float';
    }
    return $backend;
}

function dongtrader_is_int_string($s) {
    // allow optional leading + or - and digits only
    return is_string($s) && preg_match('/^[+-]?\d+$/', $s);
}

function dongtrader_num_add($a, $b, $scale = 18) {
    $backend = dongtrader_num_backend();
    $a_s = (string)$a;
    $b_s = (string)$b;

    if ($backend === 'gmp' && dongtrader_is_int_string($a_s) && dongtrader_is_int_string($b_s)) {
        return gmp_strval(gmp_add($a_s, $b_s));
    }

    if ($backend === 'bcmath') {
        return bcadd($a_s, $b_s, $scale);
    }

    if ($backend === 'bigdecimal') {
        try {
            $A = \Brick\Math\BigDecimal::of($a_s);
            $B = \Brick\Math\BigDecimal::of($b_s);
            $R = $A->plus($B);
            return (string)$R;
        } catch (Exception $e) {
            error_log('bigdecimal add failed: ' . $e->getMessage());
        }
    }

    if ($backend === 'decimal') {
        try {
            $da = new Decimal($a_s);
            $db = new Decimal($b_s);
            $res = $da->add($db);
            return (string)$res;
        } catch (Exception $e) {
            error_log('Decimal add failed: ' . $e->getMessage());
        }
    }

    // Fallback to float arithmetic (last resort)
    return (string)(((float)$a_s) + ((float)$b_s));
}

function dongtrader_num_sub($a, $b, $scale = 18) {
    $backend = dongtrader_num_backend();
    $a_s = (string)$a;
    $b_s = (string)$b;

    if ($backend === 'gmp' && dongtrader_is_int_string($a_s) && dongtrader_is_int_string($b_s)) {
        return gmp_strval(gmp_sub($a_s, $b_s));
    }

    if ($backend === 'bcmath') {
        return bcsub($a_s, $b_s, $scale);
    }

    if ($backend === 'bigdecimal') {
        try {
            $A = \Brick\Math\BigDecimal::of($a_s);
            $B = \Brick\Math\BigDecimal::of($b_s);
            $R = $A->minus($B);
            return (string)$R;
        } catch (Exception $e) {
            error_log('bigdecimal sub failed: ' . $e->getMessage());
        }
    }

    if ($backend === 'decimal') {
        try {
            $da = new Decimal($a_s);
            $db = new Decimal($b_s);
            $res = $da->sub($db);
            return (string)$res;
        } catch (Exception $e) {
            error_log('Decimal sub failed: ' . $e->getMessage());
        }
    }

    return (string)(((float)$a_s) - ((float)$b_s));
}

function dongtrader_num_mul($a, $b, $scale = 18) {
    $backend = dongtrader_num_backend();
    $a_s = (string)$a;
    $b_s = (string)$b;

    if ($backend === 'gmp' && dongtrader_is_int_string($a_s) && dongtrader_is_int_string($b_s)) {
        return gmp_strval(gmp_mul($a_s, $b_s));
    }

    if ($backend === 'bcmath') {
        return bcmul($a_s, $b_s, $scale);
    }

    if ($backend === 'bigdecimal') {
        try {
            $A = \Brick\Math\BigDecimal::of($a_s);
            $B = \Brick\Math\BigDecimal::of($b_s);
            $R = $A->multipliedBy($B);
            return (string)$R;
        } catch (Exception $e) {
            error_log('bigdecimal mul failed: ' . $e->getMessage());
        }
    }

    if ($backend === 'decimal') {
        try {
            $da = new Decimal($a_s);
            $db = new Decimal($b_s);
            $res = $da->mul($db);
            return (string)$res;
        } catch (Exception $e) {
            error_log('Decimal mul failed: ' . $e->getMessage());
        }
    }

    return (string)(((float)$a_s) * ((float)$b_s));
}

function dongtrader_num_div($a, $b, $scale = 18) {
    $backend = dongtrader_num_backend();
    $a_s = (string)$a;
    $b_s = (string)$b;

    if ($b_s === '0' || $b_s === 0 || $b_s === '0.0') {
        return '0';
    }

    if ($backend === 'gmp' && dongtrader_is_int_string($a_s) && dongtrader_is_int_string($b_s)) {
        // integer division with remainder dropped
        try {
            $q = gmp_div_q($a_s, $b_s);
            return gmp_strval($q);
        } catch (Exception $e) {
            // fallback
        }
    }

    if ($backend === 'bcmath') {
        return bcdiv($a_s, $b_s, $scale);
    }

    if ($backend === 'bigdecimal') {
        try {
            $A = \Brick\Math\BigDecimal::of($a_s);
            $B = \Brick\Math\BigDecimal::of($b_s);
            $R = $A->dividedBy($B, $scale, \Brick\Math\RoundingMode::DOWN);
            return (string)$R;
        } catch (Exception $e) {
            error_log('bigdecimal div failed: ' . $e->getMessage());
        }
    }

    if ($backend === 'decimal') {
        try {
            $da = new Decimal($a_s);
            $db = new Decimal($b_s);
            $res = $da->div($db);
            return (string)$res;
        } catch (Exception $e) {
            error_log('Decimal div failed: ' . $e->getMessage());
        }
    }

    return (string)(((float)$a_s) / ((float)$b_s));
}

// Backwards-compatible thin wrappers named with 'bc' prefix used in templates
function dongtrader_bc_add($a, $b, $scale = 18) { return dongtrader_num_add($a, $b, $scale); }
function dongtrader_bc_sub($a, $b, $scale = 18) { return dongtrader_num_sub($a, $b, $scale); }
function dongtrader_bc_mul($a, $b, $scale = 18) { return dongtrader_num_mul($a, $b, $scale); }
function dongtrader_bc_div($a, $b, $scale = 18) { return dongtrader_num_div($a, $b, $scale); }




/**
 * Convert XP (as integer-string) to USD decimal string using BCMath.
 */
function dongtrader_xp_to_usd_string($xp_string, $scale = 30) {
    $xp_per_usd = (string)dongtrader_xp_per_dollar();
    return dongtrader_bc_div($xp_string, $xp_per_usd, $scale);
}

/**
 * Convert USD decimal string to XP integer-string using BCMath (round down).
 */
function dongtrader_usd_to_xp_string($usd_string) {
    $xp_per_usd = (string)dongtrader_xp_per_dollar();
    // Use wrapper function for safe multiplication
    if (function_exists('dongtrader_num_mul')) {
        $raw = dongtrader_num_mul((string)$usd_string, $xp_per_usd, 0);
    } elseif (extension_loaded('bcmath')) {
        $raw = bcmul((string)$usd_string, $xp_per_usd, 0);
    } else {
        return (string)intval((float)$usd_string * (float)$xp_per_usd);
    }
    // Remove decimal fraction by removing non-numeric characters except minus sign
    return preg_replace('/[^0-9-]/', '', $raw);
}

/**
 * Convert XP integer-string to YAM decimal string using new conversion (1 USD = 21,000 YAM = 10^23 XP)
 * YAM = XP × 21,000 / 10^23
 */
function dongtrader_xp_to_yam_string($xp_string, $scale = 30) {
    // Ensure input is a string and valid
    $xp_string = (string)$xp_string;
    if (empty($xp_string) || !is_numeric($xp_string) || floatval($xp_string) <= 0) {
        return '0';
    }
    
    $xp_per_usd = (string)dongtrader_xp_per_dollar(); // 10^23
    $yam_per_usd = (string)dongtrader_yam_per_usd(); // 21,000
    
    // First convert XP to USD: USD = XP / 10^23
    $usd_string = dongtrader_bc_div($xp_string, $xp_per_usd, $scale);
    
    // Validate USD result
    if (empty($usd_string) || !is_numeric($usd_string)) {
        return '0';
    }
    
    // Then convert USD to YAM: YAM = USD × 21,000
    if (function_exists('dongtrader_num_mul')) {
        $result = dongtrader_num_mul($usd_string, $yam_per_usd, $scale);
    } elseif (extension_loaded('bcmath')) {
        $result = bcmul($usd_string, $yam_per_usd, $scale);
    } else {
        $result = (string)(floatval($usd_string) * floatval($yam_per_usd));
    }
    
    // Ensure we return a valid string
    return !empty($result) ? (string)$result : '0';
}


/**
 * Format a decimal numeric string into scientific notation WITHOUT using floats.
 * Preserves arbitrary-precision digits from the input string and displays
 * a mantissa with the requested number of fractional digits.
 *
 * @param string $decimal_string Numeric value as string (may contain '.' or 'e')
 * @param int $frac_digits Number of digits to show after the decimal point in the mantissa
 * @return string HTML-safe scientific string like "1.03000... × 10<sup>23</sup>"
 */
function dongtrader_format_decimal_scientific($decimal_string, $frac_digits = 18) {
    $s = trim((string)$decimal_string);
    if ($s === '' || $s === '0' || $s === '0.0') return '0';
    
    // Safety check: ensure string is not empty after trim
    if (strlen($s) === 0) return '0';

    $sign = '';
    if (strlen($s) > 0 && ($s[0] === '+' || $s[0] === '-')) {
        if ($s[0] === '-') $sign = '-';
        $s = substr($s, 1);
        // Safety check after removing sign
        if (strlen($s) === 0) return '0';
    }

    // If scientific notation already provided (e.g. 1.23e45)
    if (stripos($s, 'e') !== false) {
        $parts = preg_split('/e/i', $s);
        $mant = $parts[0];
        $exp = intval($parts[1]);
        // Normalize mantissa (remove decimal point)
        if (strpos($mant, '.') !== false) {
            list($intp, $fracp) = explode('.', $mant, 2);
        } else {
            $intp = $mant;
            $fracp = '';
        }
        $digits = preg_replace('/[^0-9]/', '', $intp . $fracp);
        // remove leading zeros
        $digits = ltrim($digits, '0');
        if ($digits === '') return '0';
        $exponent = strlen($intp) - 1 + $exp;
    } else {
        // Plain decimal representation
        if (strpos($s, '.') !== false) {
            list($intp, $fracp) = explode('.', $s, 2);
        } else {
            $intp = $s;
            $fracp = '';
        }
        // remove non-digits
        $intp = preg_replace('/[^0-9]/', '', $intp);
        $fracp = preg_replace('/[^0-9]/', '', $fracp);

        // remove leading zeros from integer part
        $intp_nz = ltrim($intp, '0');
        if ($intp_nz !== '') {
            // value >= 1
            $digits = $intp_nz . $fracp;
            $exponent = strlen($intp_nz) - 1;
        } else {
            // value < 1 -> find first non-zero in fraction
            $first_nonzero = null;
            $len_frac = strlen($fracp);
            for ($i = 0; $i < $len_frac; $i++) {
                if ($fracp[$i] !== '0') { $first_nonzero = $i; break; }
            }
            if ($first_nonzero === null) {
                return '0';
            }
            $digits = substr($fracp, $first_nonzero);
            $exponent = -($first_nonzero + 1);
        }
    }

    // Ensure we have only digits
    $digits = preg_replace('/[^0-9]/', '', $digits);
    if ($digits === '' || strlen($digits) === 0) return '0';

    // Prepare mantissa: one digit, dot, then $frac_digits digits
    $total_needed = 1 + $frac_digits; // total digits to take
    if (strlen($digits) < $total_needed) {
        $digits = str_pad($digits, $total_needed, '0');
    }
    
    // Safety check: ensure we have at least one digit
    if (strlen($digits) === 0) return '0';

    $first = $digits[0];
    $rest = substr($digits, 1, $frac_digits);

    // Build mantissa string without trimming trailing zeros (preserve precision)
    if ($frac_digits > 0) {
        $mantissa = $first . '.' . $rest;
    } else {
        $mantissa = $first;
    }

    // Build exponent display with superscript for readability
    $exp_display = $exponent;
    return $sign . $mantissa . ' × 10<sup>' . $exp_display . '</sup>';
}


function qrtiger_upload_logo()
{
}

/**
 * YAM JAM XP Conversion Rate Helper Functions
 * New conversion rate: 1 USD = 21,000 YAM = 10^23 XP
 */

/**
 * Get XP per dollar (sextillionth precision)
 * @return int XP per USD
 */
function dongtrader_xp_per_dollar() {
    return 100000000000000000000000; // 1 USD = 100,000,000,000,000,000,000,000 XP (10^23)
}

/**
 * Get YAM per USD
 * @return int YAM per USD
 */
function dongtrader_yam_per_usd() {
    return 21000; // 1 USD = 21,000 YAM
}

/**
 * Get XP per YAM token
 * @return float XP per YAM (10^23 / 21,000)
 */
function dongtrader_xp_per_yam() {
    // 1 USD = 21,000 YAM = 10^23 XP
    // Therefore: 1 YAM = 10^23 / 21,000 XP
    return 100000000000000000000000 / 21000; // Approximately 4,761,904,761,904,761,904,761.904... XP per YAM
}

/**
 * Convert USD to XP
 * @param float $usd_amount USD amount
 * @return int XP amount
 */
function dongtrader_usd_to_xp($usd_amount) {
    return intval($usd_amount * dongtrader_xp_per_dollar());
}

/**
 * Convert XP to USD
 * @param int $xp_amount XP amount
 * @return float USD amount
 */
function dongtrader_xp_to_usd($xp_amount) {
    return $xp_amount / dongtrader_xp_per_dollar();
}

/**
 * Convert XP to YAM
 * @param int $xp_amount XP amount
 * @return float YAM amount
 */
function dongtrader_xp_to_yam($xp_amount) {
    return $xp_amount / dongtrader_xp_per_yam();
}

/**
 * Convert YAM to XP
 * @param float $yam_amount YAM amount
 * @return int XP amount
 */
function dongtrader_yam_to_xp($yam_amount) {
    return intval($yam_amount * dongtrader_xp_per_yam());
}

/**
 * Convert USD to YAM
 * @param float $usd_amount USD amount
 * @return float YAM amount
 */
function dongtrader_usd_to_yam($usd_amount) {
    return $usd_amount * dongtrader_yam_per_usd();
}

/**
 * Convert YAM to USD
 * @param float $yam_amount YAM amount
 * @return float USD amount
 */
function dongtrader_yam_to_usd($yam_amount) {
    return $yam_amount / dongtrader_yam_per_usd();
}

/**
 * ========================================
 * MATURITY DATE TRACKING FUNCTIONS
 * ========================================
 */

/**
 * Get configured maturity weeks (default 10, range 8-12)
 * @return int Number of weeks for maturity
 */
function dongtrader_get_maturity_weeks() {
    $weeks = get_option('dongtrader_maturity_weeks', 10);
    // Ensure weeks is between 8-12
    return max(8, min(12, intval($weeks)));
}

/**
 * Calculate maturity date based on delivery date
 * @param string $delivery_date Delivery/earned date (Y-m-d H:i:s format)
 * @param int|null $weeks Optional weeks override (default uses option value)
 * @return string Maturity date in Y-m-d H:i:s format
 */
function dongtrader_calculate_maturity_date($delivery_date, $weeks = null) {
    if (empty($delivery_date)) {
        return null;
    }
    
    if ($weeks === null) {
        $weeks = dongtrader_get_maturity_weeks();
    }
    
    // Ensure weeks is between 8-12
    $weeks = max(8, min(12, intval($weeks)));
    
    try {
        $delivery = new DateTime($delivery_date);
        $delivery->modify("+{$weeks} weeks");
        return $delivery->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        error_log("Error calculating maturity date: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if XP entry is mature (8-12 weeks have passed)
 * @param string $delivery_date Delivery/earned date
 * @param string|null $current_date Optional current date (default: now)
 * @return bool True if mature, false if still maturing
 */
function dongtrader_is_xp_entry_mature($delivery_date, $current_date = null) {
    if (empty($delivery_date)) {
        return false;
    }
    
    if ($current_date === null) {
        $current_date = current_time('mysql');
    }
    
    $maturity_date = dongtrader_calculate_maturity_date($delivery_date);
    
    if (!$maturity_date) {
        return false;
    }
    
    // Check if current date is >= maturity date
    return (strtotime($current_date) >= strtotime($maturity_date));
}

/**
 * Get delivery date from XP transaction entry
 * Tries multiple date fields to find when XP was earned
 * @param array $transaction XP transaction array
 * @return string|null Delivery date or null if not found
 */
function dongtrader_get_delivery_date_from_xp_entry($transaction) {
    if (!is_array($transaction)) {
        error_log("dongtrader_get_delivery_date_from_xp_entry: Transaction is not an array");
        return null;
    }
    
    // Debug: Log all transaction keys
    error_log("dongtrader_get_delivery_date_from_xp_entry: Transaction keys: " . implode(', ', array_keys($transaction)));
    
    // Check various possible date fields (in order of priority)
    $date_fields = array(
        'delivery_date',
        'earned_date',
        'verification_date',
        'order_date',
        'order_datetime',
        'date',
        'created_at',
        'timestamp',
        'purchase_date', // Common in WooCommerce
        'completed_date' // WooCommerce order completed date
    );
    
    foreach ($date_fields as $field) {
        if (isset($transaction[$field]) && !empty($transaction[$field])) {
            $date_value = $transaction[$field];
            error_log("dongtrader_get_delivery_date_from_xp_entry: Found date field '$field' = '$date_value'");
            
            // If it's a timestamp, convert to datetime
            if (is_numeric($date_value)) {
                $formatted_date = date('Y-m-d H:i:s', $date_value);
                error_log("dongtrader_get_delivery_date_from_xp_entry: Converted timestamp to: $formatted_date");
                return $formatted_date;
            }
            
            // Try to parse as date string
            try {
                $date = new DateTime($date_value);
                $formatted_date = $date->format('Y-m-d H:i:s');
                error_log("dongtrader_get_delivery_date_from_xp_entry: Parsed date string to: $formatted_date");
                return $formatted_date;
            } catch (Exception $e) {
                error_log("dongtrader_get_delivery_date_from_xp_entry: Failed to parse '$field' as date: " . $e->getMessage());
                continue;
            }
        }
    }
    
    // Additional fallback: Check if there's an order_id and try to get date from WooCommerce order
    if (isset($transaction['order_id']) && !empty($transaction['order_id'])) {
        $order_id = intval($transaction['order_id']);
        if ($order_id > 0) {
            $woocommerce_order = wc_get_order($order_id);
            if ($woocommerce_order && is_a($woocommerce_order, 'WC_Order')) {
                // Try to get order date
                $order_date = $woocommerce_order->get_date_created();
                if ($order_date) {
                    $formatted_date = $order_date->format('Y-m-d H:i:s');
                    error_log("dongtrader_get_delivery_date_from_xp_entry: Retrieved date from WooCommerce order #$order_id: $formatted_date");
                    return $formatted_date;
                }
            }
        }
    }
    
    // Fallback: If no date found, return null
    error_log("dongtrader_get_delivery_date_from_xp_entry: No date field found in transaction. Available fields: " . implode(', ', array_keys($transaction)));
    return null;
}

/**
 * Calculate days until maturity
 * @param string $delivery_date Delivery/earned date
 * @return int|null Days until maturity (negative if already mature), null if error
 */
function dongtrader_days_until_maturity($delivery_date) {
    if (empty($delivery_date)) {
        return null;
    }
    
    $maturity_date = dongtrader_calculate_maturity_date($delivery_date);
    if (!$maturity_date) {
        return null;
    }
    
    $current_time = current_time('timestamp');
    $maturity_timestamp = strtotime($maturity_date);
    
    $diff_seconds = $maturity_timestamp - $current_time;
    $days = floor($diff_seconds / (60 * 60 * 24));
    
    return $days;
}

/**
 * ========================================
 * MONTHLY REDEMPTION WINDOW FUNCTIONS
 * ========================================
 */

/**
 * Get monthly redemption window dates
 * Redemption windows: Last day of month (request submission) and First day of month (processing)
 * Total 24 days per year: first and last day of each month
 * @param int|null $month Optional month (1-12), default current month
 * @param int|null $year Optional year, default current year
 * @return array Array with 'start' (last day) and 'end' (first day) dates, and 'request_day' and 'processing_day'
 */
function dongtrader_get_monthly_redemption_window($month = null, $year = null) {
    if ($month === null) {
        $month = (int)date('n');
    }
    if ($year === null) {
        $year = (int)date('Y');
    }
    
    // Last day of month (for request submission)
    $last_day = new DateTime("{$year}-{$month}-01");
    $last_day->modify('last day of this month');
    $last_day->setTime(0, 0, 0);
    
    // First day of month (for processing/disbursement)
    $first_day = new DateTime("{$year}-{$month}-01 23:59:59");
    
    return array(
        'start' => $last_day->format('Y-m-d H:i:s'),  // Last day: Request submission
        'end' => $first_day->format('Y-m-d H:i:s'),   // First day: Processing
        'request_day' => $last_day->format('Y-m-d'),  // Last day for requests
        'processing_day' => $first_day->format('Y-m-d') // First day for processing
    );
}

/**
 * Check if current date is within a monthly redemption window
 * Windows are: Last day of month (request submission) and First day of month (processing)
 * September 1st is Redemption Day - special annual redemption at 100%
 * @param string|null $current_date Optional date to check (default: now)
 * @return bool True if within window, false otherwise
 */
function dongtrader_is_within_redemption_window($current_date = null) {
    if ($current_date === null) {
        $current_date = current_time('mysql');
    }
    
    $current_timestamp = strtotime($current_date);
    $current_date_obj = new DateTime($current_date);
    $current_day = (int)$current_date_obj->format('d');
    $current_month = (int)$current_date_obj->format('n');
    $current_year = (int)$current_date_obj->format('Y');
    
    // Check if it's the first day of the month (processing day)
    $is_first_day = ($current_day === 1);
    
    // Check if it's the last day of the month (request submission day)
    $last_day_obj = new DateTime("{$current_year}-{$current_month}-01");
    $last_day_obj->modify('last day of this month');
    $is_last_day = ($current_day === (int)$last_day_obj->format('d'));
    
    // Redemption windows: First day OR last day of any month
    return ($is_first_day || $is_last_day);
}

/**
 * Get next redemption window date
 * Returns the next available window (last day or first day of month)
 * @return array Array with 'date', 'start', and 'end' for next window
 */
function dongtrader_get_next_redemption_window() {
    $current_date = new DateTime(current_time('mysql'));
    $current_month = (int)$current_date->format('n');
    $current_year = (int)$current_date->format('Y');
    $current_day = (int)$current_date->format('d');
    
    // Get last day of current month
    $last_day_obj = new DateTime("{$current_year}-{$current_month}-01");
    $last_day_obj->modify('last day of this month');
    $last_day = (int)$last_day_obj->format('d');
    
    // Check if we're past the first day of current month
    // If so, next window is the last day of current month (if not past) or first day of next month
    if ($current_day > 1) {
        // If past first day but before last day, next window is last day of current month
        if ($current_day < $last_day) {
            $window = dongtrader_get_monthly_redemption_window($current_month, $current_year);
            return array(
                'date' => date('F j, Y', strtotime($window['start'])),
                'start' => $window['start'],
                'end' => $window['end']
            );
        }
        // If past last day, next window is first day of next month
        $next_month = $current_month + 1;
        $next_year = $current_year;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }
        $window = dongtrader_get_monthly_redemption_window($next_month, $next_year);
        return array(
            'date' => date('F j, Y', strtotime($window['end'])),
            'start' => $window['end'], // Next window is first day of next month
            'end' => $window['end']
        );
    }
    
    // If it's the first day, check if we're past processing time
    // For now, assume first day is still valid window
    $window = dongtrader_get_monthly_redemption_window($current_month, $current_year);
    return array(
        'date' => date('F j, Y', strtotime($window['end'])),
        'start' => $window['end'],
        'end' => $window['end']
    );
}

/**
 * Check if user is eligible to redeem XP
 * Validates all requirements: minimum amount, matured XP, redemption window, active requests
 * @param float $total_usd Total USD value of completed XP
 * @param int $total_completed_xp Total completed/matured XP
 * @param bool $has_active_redemption Whether user has pending/processing redemptions
 * @return array Array with 'eligible' bool and 'reason' string if not eligible
 */
function dongtrader_check_redemption_button_eligibility($total_usd, $total_completed_xp, $has_active_redemption) {
    $min_redemption_amount = 1.0; // Minimum $1.00 USD required
    
    // Validation 1: Check for active redemption requests
    if ($has_active_redemption) {
        return array(
            'eligible' => false,
            'reason' => 'active_redemption',
            'message' => 'You have a pending redemption request. Please wait for it to be processed.'
        );
    }
    
    // Validation 2: Check minimum USD amount (STRICT: Must be >= $1.00)
    $total_usd_numeric = floatval($total_usd);
    // Use strict comparison - button only shows if amount is >= $1.00 exactly
    if ($total_usd_numeric < $min_redemption_amount) {
        return array(
            'eligible' => false,
            'reason' => 'minimum_amount',
            'message' => 'You need at least $1.00 USD worth of matured XP to redeem. Current value: $' . number_format($total_usd_numeric, 2),
            'current_amount' => $total_usd_numeric,
            'required_amount' => $min_redemption_amount,
            'debug_info' => 'USD: ' . $total_usd_numeric . ' | Required: ' . $min_redemption_amount . ' | Check: ' . ($total_usd_numeric < $min_redemption_amount ? 'FAIL' : 'PASS')
        );
    }
    
    // Validation 3: Check if user has matured/completed XP (not just pending)
    if ($total_completed_xp <= 0) {
        return array(
            'eligible' => false,
            'reason' => 'no_matured_xp',
            'message' => 'You need matured XP credits (8-12 weeks old) to redeem. Your XP is still pending maturity.'
        );
    }
    
    // Validation 4: Check if within redemption window
    if (!dongtrader_is_within_redemption_window()) {
        $current_date_obj = new DateTime(current_time('mysql'));
        $days_until = dongtrader_days_until_next_redemption_window();
        $next_window = dongtrader_get_next_redemption_window();
        
        return array(
            'eligible' => false,
            'reason' => 'outside_window',
            'message' => 'Redemption window is currently closed. Requests can be submitted on the last day of each month, and processing occurs on the first day of each month.',
            'next_window_date' => date('F j, Y', strtotime($next_window['start'])),
            'days_until_window' => $days_until,
            'window_range' => date('F j', strtotime($next_window['start'])) . ' - ' . date('F j', strtotime($next_window['end']))
        );
    }
    
    // All validations passed
    return array(
        'eligible' => true,
        'reason' => 'all_checks_passed'
    );
}

/**
 * Get days until next redemption window
 * @return int|null Days until next window (0 if currently in window)
 */
function dongtrader_days_until_next_redemption_window() {
    $current_date = current_time('mysql');
    
    // If currently in window, return 0
    if (dongtrader_is_within_redemption_window($current_date)) {
        return 0;
    }
    
    $current_date_obj = new DateTime($current_date);
    $current_month = (int)$current_date_obj->format('n');
    $current_year = (int)$current_date_obj->format('Y');
    $current_day = (int)$current_date_obj->format('d');
    
    // Get last day of current month
    $last_day_obj = new DateTime("{$current_year}-{$current_month}-01");
    $last_day_obj->modify('last day of this month');
    $last_day = (int)$last_day_obj->format('d');
    
    // Calculate days until next window
    if ($current_day === 1) {
        // If it's the first day, next window is last day of this month
        $next_window_date = $last_day_obj;
    } elseif ($current_day < $last_day) {
        // If before last day, next window is last day of this month
        $next_window_date = $last_day_obj;
    } else {
        // If past last day, next window is first day of next month
        $next_month = $current_month + 1;
        $next_year = $current_year;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }
        $next_window_date = new DateTime("{$next_year}-{$next_month}-01");
    }
    
    $current_timestamp = $current_date_obj->getTimestamp();
    $next_timestamp = $next_window_date->getTimestamp();
    $diff_seconds = $next_timestamp - $current_timestamp;
    
    return max(0, floor($diff_seconds / (60 * 60 * 24)));
}

/**
 * Get settlement rate for redemption
 * September 1st: 100% (Redemption Day - annual full redemption)
 * Other months: 96.5% (standard monthly settlement)
 * @param string|null $date Optional date to check (default: now)
 * @return float Settlement rate as decimal (1.0 for 100%, 0.965 for 96.5%)
 */
function dongtrader_get_settlement_rate($date = null) {
    if ($date === null) {
        $date = current_time('mysql');
    }
    
    $date_obj = new DateTime($date);
    $is_september_1st = ($date_obj->format('m-d') === '09-01');
    
    // September 1st is Redemption Day at 100%
    if ($is_september_1st) {
        return 1.0; // 100%
    }
    
    // All other months settle at 96.5%
    return 0.965; // 96.5%
}

/*This function is used to make the Qrtiger API requests.
 *These are valid urls . Requests might be  costly so plz use mock url from stoplight api
 *GET URL : https://qrtiger.com/data/6BF7
 *POST URL  : https://qrtiger.com/api/campaign/
 *Function Call Process is Here with the endpoints
 *$POST = dongtrader_http_requests('/api/campaign/', array(), 'POST');
 *$GET = dongtrader_http_requests('/data/6BF7', array(), 'GET');
 */
function qrtiger_api_request($endpoint = '', $bodyParams = array(), $method = "GET")
{
    /* Get the API credentials from the database. */
    $qrtiger_creds = get_option('dongtraders_api_settings_fields');
    /* Check if the API credentials are empty or not. */
    $checkFields = !empty($qrtiger_creds['qrtiger-api-key']) && !empty($qrtiger_creds['qrtiger-api-url']) ? true : false;
    /* Check if the API credentials are empty or not. */
    if (!$checkFields) {
        return;
    }

    /* Get the API URL from the database. */
    $qrtiger_api_root_url = $qrtiger_creds['qrtiger-api-url'];
    /* Getting the API key from the database. */
    $qrtiger_api_key = $qrtiger_creds['qrtiger-api-key'];
    /* Concatenating the API root URL with the endpoint. */
    $build_url = $qrtiger_api_root_url . $endpoint;
    /* A default array. */

    /* Taking the default array and merging it with the  array. */
    //$body = wp_json_encode(wp_parse_args($qrtiger_defaults, $bodyParams));
    $body = wp_json_encode($bodyParams);
    /* Setting the options for the request. */
    $options = [
        'body' => $method == "POST" ? $body : '',
        'headers' => [
            'Authorization' => 'Bearer ' . $qrtiger_api_key,
            'Content-Type' => 'application/json',
        ],
        'timeout' => 30,

    ];

    // $build_url = 'https://stoplight.io/mocks/qrtiger/qrtiger-api/7801905';

    /* A ternary operator to check get or post parameter and use functions accordingly*/
    $response_received = $method == 'POST' ? wp_remote_post($build_url, $options) : wp_remote_get($build_url, $options);
    
    // Check for WP_Error
    if (is_wp_error($response_received)) {
        return false;
    }
    
    /* Get the response code from the response received. */
    $response_status = wp_remote_retrieve_response_code($response_received);
    /* Checking if the response status is 200 or not. If it is 200 then it will return the body of the response. */
    $response_body = $response_status == 200 ? wp_remote_retrieve_body($response_received) : false;
    /* Checking if the response body is not empty and then decoding the response body. */
    $response_object = $response_body ? json_decode($response_body) : false;

    $resp = $response_object->status != 403 ? $response_object : false;

    return $resp;
}

/* A function that is used to make the Glassfrog API requests. */
function glassfrog_api_request($endpoint = '', $str = '', $method = "GET")
{
    /* Get the API credentials from the database. */
    $api_creds = get_option('dongtraders_api_settings_fields');
    /*Get API Key*/
    $gf_api_key = $api_creds['glassfrog-api-key'];
    /*Get API Url*/
    $gf_api_url = $api_creds['glassfrog-api-url'];
    /* Check if the API credentials are empty or not. */
    $checkFields = !empty($gf_api_key) && !empty($gf_api_url) ? true : false;
    /* if not valid return false */
    if (!$checkFields) {
        return;
    }

    /* Build Url for api request */
    $build_url = $gf_api_url . $endpoint;

    /* Params For API requests */
    $options = [
        'body' => $method == "POST" ? $str : '',
        'headers' => [
            'X-Auth-Token' => $gf_api_key,
            'Content-Type' => 'application/json',

        ],

    ];

    // vdd($options);

    /* A ternary operator to check get or post parameter and use functions accordingly*/
    $response_received = $method == 'POST' ? wp_remote_post($build_url, $options) : wp_remote_get($build_url, $options);
    
    // Check for WP_Error
    if (is_wp_error($response_received)) {
        return false;
    }
    
    /* Get the response code from the response received. */
    $response_status = wp_remote_retrieve_response_code($response_received);
    /* Checking if the response status is 200 or not. If it is 200 then it will return the body of the response. */
    $response_body = $response_status == 200 ? wp_remote_retrieve_body($response_received) : false;
    /* Checking if the response body is not empty and then decoding the response body. */
    $response_object = $response_body ? json_decode($response_body) : false;



    return $response_object;
}

/*This function is used to make ajax call on woocommerce my account page.
 *It calls qrtiger api and fetches qrcodes data
 */
add_action('wp_ajax_dongtrader_generate_qr2', 'dongtrader_generate_qr2');

function dongtrader_generate_qr2()
{
    // Security check: Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dongtrader_generate_qr2')) {
        $notify_to_js = array(
            'dataStatus' => false,
            'user' => 0,
            'apistatus' => false,
            'error' => 'Security check failed'
        );
        echo wp_json_encode($notify_to_js);
        wp_die();
        return;
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        $notify_to_js = array(
            'dataStatus' => false,
            'user' => 0,
            'apistatus' => false,
            'error' => 'You must be logged in to perform this action'
        );
        echo wp_json_encode($notify_to_js);
        wp_die();
        return;
    }
    
    $qr_size = isset($_POST['qrsize']) ? sanitize_text_field($_POST['qrsize']) : '';
    $qr_url = isset($_POST['qrurl']) ? sanitize_url($_POST['qrurl']) : '';
    $qr_color = isset($_POST['qrcolor']) ? sanitize_text_field($_POST['qrcolor']) : '';
    $qr_logo_url = plugin_dir_url(__FILE__) . 'assets/img/currency.png';
    $dong_user_id = get_current_user_id();
    $response = !empty($qr_size) && !empty($qr_url) && !empty(trim($qr_color)) ? true : false;
    $notify_to_js = array(
        'dataStatus' => $response,
        'user' => $dong_user_id,
        'apistatus' => false,
    );
    if ($response) {
        //either background color or qr elements color can be set
        $qrtiger_array = [
            "qr" => [
                "size" => $qr_size,
                "logo" => 'currency.png',
                "colorDark" => $qr_color,
                "backgroundColor" => '',
                "transparentBkg" => false,
            ],
            "qrUrl" => $qr_url,
            "qrType" => "qr2",
            "qrCategory" => "url",
        ];
        $qrtiger_api_call = qrtiger_api_request('/api/campaign/', $qrtiger_array, 'POST');

        if ($qrtiger_api_call && isset($qrtiger_api_call->data)) {
            $notify_to_js['apistatus'] = true;
            $current_dong_qr_array = array(
                'created_by' => $dong_user_id,
                'qr_image_url' => isset($qrtiger_api_call->data->qrImage) ? $qrtiger_api_call->data->qrImage : '',
                'created_at' => isset($qrtiger_api_call->data->createdAt) ? $qrtiger_api_call->data->createdAt : '',
                'updated_at' => isset($qrtiger_api_call->data->updatedAt) ? $qrtiger_api_call->data->updatedAt : '',
                'qr_id' => isset($qrtiger_api_call->data->qrId) ? $qrtiger_api_call->data->qrId : '',
            );

            $old_dong_qr_array = get_option('dong_user_qr_values');
            $prev_value_check = !empty($old_dong_qr_array) ? true : false;
            if ($prev_value_check) {
                array_push($old_dong_qr_array, $current_dong_qr_array);
                // update_user_meta($dong_user_id, 'dong_user_qr_values', $old_dong_qr_array);
                update_option('dong_user_qr_values', array_reverse($old_dong_qr_array));
            } else {
                // update_user_meta($dong_user_id, 'dong_user_qr_values', [$current_dong_qr_array]);
                update_option('dong_user_qr_values', [$current_dong_qr_array]);
            }
        }
    }

    echo wp_json_encode($notify_to_js);

    wp_die();
}

//dongtrader_delete_qr

add_action('wp_ajax_dongtrader_delete_qr', 'dongtrader_delete_qr');

function dongtrader_delete_qr()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $index = esc_attr($_POST['qrIndex']);
    $dong_qr_array = get_option('dong_user_qr_values');
    $resp_array = array('success' => false, 'd' => $dong_qr_array, 'i' => $index);

    if ($dong_qr_array && !empty($index) || $index == '0') {

        unset($dong_qr_array[$index]);
        $new_arrray = $dong_qr_array;
        update_option('dong_user_qr_values', $new_arrray);
        $resp_array['success'] = true;
    }
    echo wp_json_encode($resp_array);
    wp_die();
}

//Simple function to make api calls with parameters as an array

function dongtrader_variable_color_to_rgb_color($color)
{
    switch ($color) {
        case 'orange':
            $color = 'rgb(255, 51, 0)';
            break;
        case 'purple':
            $color = 'rgb(245,66,245)';
            break;
        case 'red':
            $color = 'rgb(255, 0, 0)';
            break;

        case 'blue':
            $color = 'rgb(36, 36, 143)';
            break;

        case 'green':
            $color = 'rgb(0, 204, 0)';
            break;

        default:
            $color = 'rgb(38, 38, 38)';
    }

    return $color;
}

function dongtrader_ajax_test_helper($color = '', $url = '', $size = '')
{

    return array(
        'qr_image_url' => 'https://qrtiger.com/qr/OXX3.png',
        'created_at' => 'date',
        'updated_at' => 'date',
        'qr_id' => '0XX3',
    );
}

function dongtrader_ajax_helper($color, $url, $size = '500')
{
    // Debug: Log the URL being embedded in QR code
    error_log('  - QR Helper: URL to embed in QR code: ' . $url);
    error_log('  - QR Helper: Color: ' . $color . ', Size: ' . $size);
    
    $logo = "https://smallstreet.app/wp-content/uploads/2023/03/3D2-1-1.png";
    $current_dong_qr_array = true;
    $qrtiger_array = [
        "qr" => [
            "logo" => $logo,
            "size" => $size,
            "colorDark" => $color,
            "transparentBkg" => false,
        ],
        "qrUrl" => $url,
        "qrType" => "qr2",
        "qrCategory" => "url",
    ];
    
    // Debug: Log the full array being sent to QR Tiger API
    error_log('  - QR Helper: Data sent to QR Tiger API: ' . json_encode($qrtiger_array));
    
    $qrtiger_api_call = qrtiger_api_request('/api/campaign/', $qrtiger_array, 'POST');
    
    // Debug: Log API response
    if ($qrtiger_api_call) {
        error_log('  - QR Helper: API Response Success');
        error_log('  - QR Helper: QR ID: ' . (isset($qrtiger_api_call->data->qrId) ? $qrtiger_api_call->data->qrId : 'N/A'));
    } else {
        error_log('  - QR Helper: API Response Failed');
    }

    if ($qrtiger_api_call && isset($qrtiger_api_call->data)) {
        $current_dong_qr_array = array(
            "qr_image_url" => isset($qrtiger_api_call->data->qrImage) ? $qrtiger_api_call->data->qrImage : '',
            "created_at" => isset($qrtiger_api_call->data->createdAt) ? $qrtiger_api_call->data->createdAt : '',
            "updated_at" => isset($qrtiger_api_call->data->updatedAt) ? $qrtiger_api_call->data->updatedAt : '',
            "qr_id" => isset($qrtiger_api_call->data->qrId) ? $qrtiger_api_call->data->qrId : '',
        );
    } else {

        $current_dong_qr_array = false;
    }

    return $current_dong_qr_array;
}

add_action('wp_ajax_dongtrader_meta_qr_generator', 'dongtrader_meta_qr_generator');

function dongtrader_meta_qr_generator()
{
    // Check if required POST data is present
    if (!isset($_POST['intiator'])) {
        error_log('QR Generation Error: Missing required POST data (intiator)');
        echo json_encode(array('success' => false, 'error' => 'Missing required data: intiator'));
        wp_die();
        return;
    }

    $intiator = isset($_POST['intiator']) ? esc_attr($_POST['intiator']) : '';

    // For variable products, productnums is optional (we use variations instead)
    // For other types, productnums is required
    if ($intiator != '_product-qr-variabled') {
        if (!isset($_POST['productnums'])) {
            error_log('QR Generation Error: Missing required POST data (productnums)');
            echo json_encode(array('success' => false, 'error' => 'Missing required data: productnums'));
            wp_die();
            return;
        }

        $productnum = isset($_POST['productnums']) ? esc_attr($_POST['productnums']) : '';

        // Validate product ID
        if (empty($productnum) || !is_numeric($productnum)) {
            error_log('QR Generation Error: Invalid product ID');
            echo json_encode(array('success' => false, 'error' => 'Invalid product ID'));
            wp_die();
            return;
        }

        $product = wc_get_product($productnum);
        if (!$product) {
            error_log('QR Generation Error: Product not found');
            echo json_encode(array('success' => false, 'error' => 'Product not found'));
            wp_die();
            return;
        }

        // 🔄 FIXED: Custom redirect URL with proof_id format (productnum + timestamp for uniqueness)
        $timestamp = time();
        $unique_proof_id = $productnum . '_' . $timestamp;
        $product_url   = home_url('/?proof_id=' . $unique_proof_id . '&scan_type=proof');
        $check_out_Url = home_url('/?proof_id=' . $unique_proof_id . '&scan_type=checkout');

        // Debug: Log initial URLs
        error_log('QR Generation Details:');
        error_log('  - Initiator: ' . $intiator);
        error_log('  - Product ID: ' . $productnum);
        error_log('  - Unique Proof ID: ' . $unique_proof_id);
        error_log('  - Product URL: ' . $product_url);
        error_log('  - Checkout URL: ' . $check_out_Url);

        $pid  = $productnum;
        $purl = $product_url;
    } else {
        $pid  = '';
        $purl = '';
    }

    // Debug: Log QR generation start
    error_log('=== QR CODE GENERATION START ===');
    if (isset($_POST) && is_array($_POST)) {
        $post_data = array();
        foreach ($_POST as $key => $value) {
            $post_data[$key] = is_string($value) ? substr($value, 0, 100) : $value;
        }
        error_log('POST data: ' . json_encode($post_data));
    }

    $resp = array(
        'success'   => false,
        'template'  => '',
        'pid'       => $pid,
        'initiator' => $intiator,
        "purl"      => $purl,
    );

    // =========================
    // PRODUCT PAGE QR
    // =========================
    if ($intiator == '_product_qr_codes') {
        error_log('  - QR Type: Product QR Code');
        error_log('  - Redirect URL: ' . $product_url);

        $current_data = dongtrader_ajax_helper('rgb(87, 3, 48)', $product_url);
        if (!empty($current_data)) {
            $update_data      = json_encode($current_data);
            $resp['success']  = true;
            $resp['template'] = '<div class="dong-qr-components">
                <img src="' . $current_data['qr_image_url'] . '" alt="" width="200" height="200">
                <button data-url="' . $current_data['qr_image_url'] . '" class="button button-primary button-large url-copy">Copy QR URL</button>
                <input type="hidden" data-id="' . esc_attr($productnum) . '" name="_product_qr_codes" value="' . esc_attr($update_data) . '">
                <button data-meta="_product_qr_codes" data-remove="' . $productnum . '" class="button-primary button-large qr-remover">Remove</button>
            </div>';
            update_post_meta($productnum, '_product_qr_codes', $update_data);
        }

    // =========================
    // DIRECT CHECKOUT QR
    // =========================
    } elseif ($intiator == '_product-qr-direct-checkouts') {
        error_log('  - QR Type: Direct Checkout QR Code');
        error_log('  - Redirect URL: ' . $check_out_Url);

        $current_data = dongtrader_ajax_helper('rgb(0, 102, 204)', $check_out_Url);
        if (!empty($current_data)) {
            $update_data = json_encode($current_data);
            update_post_meta($productnum, '_product-qr-direct-checkouts', $update_data);
            $resp['success']  = true;
            $resp['template'] = '<div class="dong-qr-components">
                <img src="' . $current_data['qr_image_url'] . '" alt="" width="200" height="200">
                <button data-url="' . $current_data['qr_image_url'] . '" class="button button-primary button-large url-copy">Copy QR URL</button>
                <input type="hidden" data-id="' . esc_attr($productnum) . '" name="_product-qr-direct-checkouts" value="' . esc_attr($update_data) . '">
                <button data-meta="_product-qr-direct-checkouts" data-remove="' . $productnum . '" class="button-primary button-large qr-remover">Remove</button>
            </div>';
        }

    // =========================
    // VARIABLE PRODUCT QR
    // =========================
    } elseif ($intiator == '_product-qr-variabled') {
        if (!isset($_POST['variations']) || !isset($_POST['loop'])) {
            echo json_encode(array('success' => false, 'error' => 'Missing variation data'));
            wp_die();
            return;
        }

        $variations = esc_attr($_POST['variations']);
        $loop       = esc_attr($_POST['loop']);

        if (empty($variations) || !is_numeric($variations)) {
            echo json_encode(array('success' => false, 'error' => 'Invalid variation ID'));
            wp_die();
            return;
        }

        $variation_product = wc_get_product($variations);
        if (!$variation_product) {
            echo json_encode(array('success' => false, 'error' => 'Variation not found'));
            wp_die();
            return;
        }

        // 🔄 FIXED: Custom redirect URL with proof_id format for variations (variation_id + timestamp for uniqueness)
        $timestamp = time();
        $unique_proof_id = $variations . '_' . $timestamp;
        $get_url    = home_url('/?proof_id=' . $unique_proof_id . '&scan_type=proof');
        $attr_color = get_post_meta($variations, 'attribute_pa_sector', true);
        $resp['attr_color'] = $attr_color;

        $current__array = dongtrader_ajax_helper(dongtrader_variable_color_to_rgb_color($attr_color), $get_url);
        if ($current__array) {
            $update_data = json_encode($current__array);
            update_post_meta($variations, 'variable_product_qr_data', esc_attr($update_data));
            $html  = '<div data-color="' . $attr_color . '" id="dong-qr-components' . $loop . '" class="dong-qr-components dong-qr-components-var">';
            $html .= '<div class="qr-img-container-var">';
            $html .= '<img src="' . $current__array['qr_image_url'] . '" alt="" width="100" height="100">';
            $html .= '</div>';
            $html .= '<div class="qr-urlbtn-container-var">';
            $html .= '<button data-url="' . $current__array['qr_image_url'] . '" class="button-primary button-large url-copy">Copy QR URL</button>';
            $html .= '<button data-index="' . $loop . '" id="variable_product_qr_data' . $loop . '" data-meta="variable_product_qr_data" data-remove="' . $variations . '" class="button-primary button-large qr-remover" style="margin-left:10px">Remove</button>';
            $html .= '</div>';
            $html .= '<input data-id="' . esc_attr($variations) . '" type="hidden" name="variable_product_qr_data" value="' . esc_attr($update_data) . '">';
            $html .= '</div>';
            $resp['success']  = true;
            $resp['template'] = $html;
        }
    } else {
        error_log('  - ERROR: Unknown initiator type: ' . $intiator);
    }

    error_log('=== QR CODE GENERATION END ===');
    error_log('Response: ' . json_encode($resp));
    error_log('');

    echo json_encode($resp);
    wp_die();
}

// Remove functionality for qr codes
add_action('wp_ajax_dongtrader_delete_qr_fields', 'dongtrader_delete_qr_fields');
function dongtrader_delete_qr_fields()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr_fields')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    $variation_id = esc_attr($_POST['itemID']);
    $variation_meta_key = esc_attr($_POST['metakey']);
    $ajax_values = array('resp' => false, 'html' => false);
    if (!empty($variation_id) && !empty($variation_meta_key)) {
        $status = delete_post_meta($variation_id, $variation_meta_key);
        if ($status) {
            $ajax_values['status'] = true;
            $ajax_values['html'] = true;
        }
    }
    wp_send_json($ajax_values);
    wp_die();
}

//remove functionality for qr codes from settings page
// Remove functionality for qr codes
add_action('wp_ajax_dongtrader_delete_qr_items_settingspage', 'dongtrader_delete_qr_items_settingspage');
function dongtrader_delete_qr_items_settingspage()
{
    // Security check: Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_qr_items_settingspage')) {
        wp_send_json(array('resp' => false, 'reload' => false, 'error' => 'Security check failed'));
        wp_die();
        return;
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json(array('resp' => false, 'reload' => false, 'error' => 'You must be logged in to perform this action'));
        wp_die();
        return;
    }

    $dong_qr_array = get_option('dong_user_qr_values');
    $index = isset($_POST['index']) ? (int) esc_attr($_POST['index']) : -1;
    $ajax_values = array('resp' => false, 'reload' => false);

    if ($index >= 0 && is_array($dong_qr_array) && isset($dong_qr_array[$index])) {
        unset($dong_qr_array[$index]);
        $dong_qr_array = array_values($dong_qr_array); // Re-index array
        update_option('dong_user_qr_values', $dong_qr_array);
        $new_qr_array = get_option('dong_user_qr_values');

        $reload = empty($new_qr_array) ? true : false;

        $ajax_values = array('resp' => true, 'reload' => $reload);
    }

    wp_send_json($ajax_values);

    wp_die();
}


function dongtrader_user_registration_hook($customer_id, $p_id, $oid)
{

    //global database variable
    global $wpdb;

    //custom user table from our database
    $table_name = $wpdb->prefix . 'glassfrog_user_data';

    //custom group table
    $group_table_name = $wpdb->prefix . 'glassfrog_group_data';

    // Downline table 
    $downline_table = $wpdb->prefix . 'mega_mlm_downline';

    //check if a person already exists in a database
    $check_persons = $wpdb->get_row($wpdb->prepare("SELECT all_orders , group_id FROM $table_name WHERE user_id = %d", $customer_id), ARRAY_A);

    if (!empty($check_persons)) {

        if ($check_persons['group_id'] != 0) {
            //query to update group distribution status to update_required when the person has already brought new product after distribution from cron job
            $wpdb->query($wpdb->prepare("UPDATE $group_table_name SET distribution_status = 'update_required' WHERE group_id = %d", (int) $check_persons['group_id']));
        } elseif ($check_persons['group_id'] == 0) {

            $unserialized_orders = unserialize($check_persons['all_orders']);

            //check if order id is already on the list and append new order id to it
            if (!in_array($oid, $unserialized_orders)) {

                $unserialized_orders[] = $oid;

                //again serilize data to store in db
                $new_serilized_orders = serialize($unserialized_orders);

                //preapre and update serialized data
                $wpdb->query($wpdb->prepare("UPDATE $table_name SET all_orders = %s WHERE user_id = %d", $new_serilized_orders, $customer_id));
            }
        }
    } else {
        // Get the product object
        $product = wc_get_product($p_id);

        //get parent id if product is variable
        $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $p_id;

        //for this case orders datas doesnt exists on our custom database table so we need to call the glassfrog api and insert data accordingly
        $gf_checkbox = get_post_meta($parent_id, '_glassfrog_checkbox', true);

        //bool to check meta
        $gf_check = $gf_checkbox == 'on' ? true : false;

        if ($gf_check) {
            //get user object
            $user_info = get_userdata($customer_id);

            //order object
            $orderobj = new WC_Order($oid);

            // get sponsor id 
            $sponsor_id = $orderobj->get_meta('mega_affid');

            //refferal
            $refferal = !empty($sponsor_id) ? (int) $sponsor_id : null;

            //get user email
            $email = $user_info->user_email;

            //api request string
            $str = '{"people": [{
                "name": "' . $user_info->display_name . '",
                "email": "' . $email . '",
                "external_id": "' . $customer_id . '",
                "tag_names": ["tag 1", "tag 2"]
                }]
                }';

            //api call
            $samp = glassfrog_api_request('people', $str, "POST");

            if ($samp && isset($samp)):

                //glassfrog id from the api
                $gf_id = $samp->people[0]->id;

                //glassfrog persons  name
                $gf_name = $samp->people[0]->name;

                $all_orders = serialize(array($oid));

                $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");

                $wpdb->query(
                    $wpdb->prepare(
                        "INSERT INTO $table_name
                        (
                            user_id,
                            gf_person_id,
                            gf_name,
                            in_glassfrog,
                            all_orders,
                            group_id,
                            upline_id

                        )
                        VALUES ( %d,%d,%s,%d,%s,%d,%d)",
                        $customer_id, // d
                        $gf_id, // d
                        $gf_name, // s
                        0, // d
                        $all_orders, // s
                        0, // d
                        $refferal //d

                    )
                );

                if ($refferal) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO $downline_table
                            (
                                user_id,
                                downline_user_id
                            )
                            VALUES ( %d,%d)",
                            $refferal,
                            $customer_id
                        )
                    );
                }

                $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");

            endif;
        } else {

            //when order need not to be sent to glass frog where and how to manage it??

        }
    }
}

/* add custom field to save user role on user profile */
add_action('show_user_profile', 'dong_show_user_role');
add_action('edit_user_profile', 'dong_show_user_role');
function dong_show_user_role($user)
{

    $dong_user_role = get_user_meta($user->ID, 'dong_user_role', true);
    /*   echo $dong_user_role . '-dong user role'; */

    ?>
    <table class="form-table">
        <tr>
            <th><label for="city">Dong User Role</label></th>
            <td>
                <select name="dong_user_role" id="">
                    <option value="Planning" <?php if ($dong_user_role == "Planning") {
                        echo 'selected="selected"';
                    }
                    ?>>
                        Planning (Purple)</option>
                    <option value="Budget" <?php if ($dong_user_role == "Budget") {
                        echo 'selected="selected"';
                    }
                    ?>>Budget
                        (Orange)</option>
                    <option value="Media" <?php if ($dong_user_role == "Media") {
                        echo 'selected="selected"';
                    }
                    ?>>Media (Red)
                    </option>
                    <option value="Distribution" <?php if ($dong_user_role == "Distribution") {
                        echo 'selected="selected"';
                    }
                    ?>>Distribution (Green)</option>
                    <option value="Membership" <?php if ($dong_user_role == "Membership") {
                        echo 'selected="selected"';
                    }
                    ?>>
                        Membership (Blue)</option>
                </select>
                </select>
            </td>
        </tr>
    </table>
    <?php

}

add_action('personal_options_update', 'dong_user_role_save_profile_fields');
add_action('edit_user_profile_update', 'dong_user_role_save_profile_fields');

function dong_user_role_save_profile_fields($user_id)
{

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }

    if (!current_user_can('edit_user', $user_id)) {
        return;
    }

    update_user_meta($user_id, 'dong_user_role', sanitize_text_field($_POST['dong_user_role']));
}

/* Functions to set dong uer role */

function dong_set_user_role($user_id, $product_id)
{
    $get_varition_color = get_post_meta($product_id, 'attribute_pa_color', true);
    $get_color_role = dongtrader_get_product_color($get_varition_color);
    update_user_meta($user_id, 'dong_user_role', $get_color_role);
}

function dongtrader_get_product_color($role)
{
    switch ($role) {
        case 'orange':
            $role = 'Budget';
            break;
        case 'purple':
            $role = 'Planning';
            break;
        case 'red':
            $role = 'Media';
            break;
        case 'blue':
            $role = 'Membership';
            break;
        case 'green':
            $role = 'Distribution';
            break;
        default:
            $role = 'Membership';
    }

    return $role;
}

function dongtrader_convert_sector_to_slug($sector)
{
    switch ($sector) {
        case 'budget':
            $role = 'orange';
            break;
        case 'planning':
            $role = 'purple';
            break;
        case 'media':
            $role = 'red';
            break;
        case 'membership':
            $role = 'blue';
            break;
        case 'distribution':
            $role = 'green';
            break;
        default:
            $role = 'blue';
    }

    return $role;
}

/*Order Export Form NEw*/

function dongtraders_order_export_form_new()
{

    $settings = get_option('dongtraders_api_settings_fields');
    $patron_pro_val = $settings['dong_patron_mem'];

    $mega_pro_val = $settings['dong_mega_mem'];
    $sectors = get_terms(array(
        'taxonomy' => 'pa_sector',
        'hide_empty' => true,
    ));
    ?>

    <form action="" method="POST" class="order-export-form">
        <?php wp_nonce_field('order_export_form', 'order_export_nonce'); ?>
        <?php

        $validate = true;
        if (isset($_POST['customer-phone']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && phone_number_exists($_POST['customer-phone'])) {
            $validate = false;
            echo '<div class="error-box">This Phone number is<b>' . esc_html($_POST['customer-phone']) . '</b> already used. Please use unique phone number and try again</div>';
        }


        if (isset($_POST['customer-email']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && mega_check_email($_POST['customer-email'])) {
            echo '<div class="error-box">This email address <b>' . esc_html($_POST['customer-email']) . '</b> is already used .Please use unique email and try again</div>';
            // !mega_check_email( $_POST['customer-email'])
            $validate = false;
        }

        if (isset($_POST['set-order_export']) && wp_verify_nonce($_POST['order_export_nonce'], 'order_export_form') && $validate == true) {

            echo '<div class="success-box">Affiliate Order Data inserted Sucessfully</div>';
        }

        ?>
        <div class="form-group">
            <label for="">Customer Email</label>
            <div class="form-control-wrap">
                <input name="customer-email" class="form-control customer-email" type="email" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer Email'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer First Name</label>
            <div class="form-control-wrap">
                <input name="customer-first-name" class="form-control customer-first-name" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer First Name'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer Last Name</label>
            <div class="form-control-wrap">
                <input name="customer-last-name" class="form-control customer-last-name" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Last Name'" required="" value="">
            </div>
        </div>

        <div class="form-group">
            <label for="">Customer Phone</label>
            <div class="form-control-wrap">
                <input name="customer-phone" class="form-control customer-phone" type="number" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer Phone'" required="" value="">
            </div>
        </div>


        <div class="form-group">
            <label for="">Customer Country</label>
            <div class="form-control-wrap">

                <select onchange="print_state('state',this.selectedIndex);" id="country" name="customer-country"
                    class="form-control"></select>
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer State</label>
            <div class="form-control-wrap">
                <select name="customer-state" id="state" class="form-control"></select>
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer Address</label>
            <div class="form-control-wrap">
                <input name="customer-address" class="form-control customer-address" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Address'" required="" value="">
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer Postcode</label>
            <div class="form-control-wrap">
                <input name="customer-postcode" class="form-control customer-postcode" type="text"
                    onfocus="this.placeholder=''" onblur="this.placeholder='Customer Postcode'" required="" value="">
            </div>
        </div>
        <div class="form-group">
            <label for="">Customer City</label>
            <div class="form-control-wrap">
                <input name="customer-city" class="form-control customer-city" type="text" onfocus="this.placeholder=''"
                    onblur="this.placeholder='Customer City'" required="" value="">
            </div>
        </div>

        <div class=" form-group">
            <label for="">Select Membership</label>
            <div class="form-control-wrap">
                <select name="select-product" id="form-field-name" class="form-control select-product" required="required"
                    aria-required="true">
                    <option value="">Select Membership</option>
                    <option value="megavoter">MEGAvoter</option>
                    <option value="patron">Patron</option>
                </select>

            </div>
        </div>
        <div class="form-group">
            <!--  <label for="">Select Variation Product</label> -->
            <div class="form-control-wrap">
                <?php if (!empty($sectors)): ?>
                    <label for="">Select Sectors</label>
                    <select name="membership-sectors" id="form-field-type" class="form-control variation-product"
                        required="required" aria-required="true">
                        <option value="">Select Sectors</option>
                        <?php foreach ($sectors as $s) { ?>
                            <option value="<?php echo $s->slug ?>"><?php echo $s->name; ?></option>
                        <?php } ?>
                    </select>

                <?php endif; ?>

            </div>
        </div>
        <div class="form-group">
            <?php $user_ID = get_current_user_id(); ?>
            <div class="form-control-wrap">
                <input type="hidden" name="affilate-user" class="form-control affilate-user"
                    value="<?php echo $user_ID; ?>">
            </div>
        </div>
        <div class="form-group">
            <input class="cpm-btn submit real-button" type="submit" value="Add Custom Order" name="set-order_export">
        </div>
    </form>

    <?php
    if (isset($_POST['set-order_export']) && phone_number_exists($_POST['customer-phone']) !== true && mega_check_email($_POST['customer-email']) !== true) {
        $customer_email = $_POST['customer-email'];
        $customer_first_name = $_POST['customer-first-name'];
        $customer_last_name = $_POST['customer-last-name'];
        $customer_phone = $_POST['customer-phone'];
        $customer_country = $_POST['customer-country'];
        $customer_state = $_POST['customer-state'];
        $customer_address = $_POST['customer-address'];
        $customer_postcode = $_POST['customer-postcode'];
        $customer_city = $_POST['customer-city'];
        $customer_mem = wc_clean($_POST['select-product']);
        $customer_sector = wc_clean($_POST['membership-sectors']);
        $role = wc_clean(dongtrader_get_product_color($customer_sector));
        $variation = dongtrader_convert_sector_to_slug(strtolower($customer_sector));

        $customer_affilate_user_final = $_POST['affilate-user'];

        $created_date = date("Y-m-d");


        global $wpdb;
        $order_table_name = $wpdb->prefix . 'dong_order_export_table';
        $wpdb->query(
            $order_insert = $wpdb->prepare(
                "INSERT INTO $order_table_name
               (
                customer_email,
                customer_first_name,
                customer_last_name,
                customer_phone,
                customer_country,
                customer_state,
                customer_address,
                customer_postcode,
                customer_city,
                customer_membership,
                customer_sector,
                affilate_user_id,
                created_at
               )
               VALUES ( %s, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %d, %s )",
                esc_attr($customer_email),
                esc_attr($customer_first_name),
                esc_attr($customer_last_name),
                esc_attr($customer_phone),
                esc_attr($customer_country),
                esc_attr($customer_state),
                esc_attr($customer_address),
                esc_attr($customer_postcode),
                esc_attr($customer_city),
                $customer_mem,
                strtolower($role),
                esc_attr($customer_affilate_user_final),
                $created_date

            )
        );
        if ($order_insert) {

            $cart_items = '';

            if (strtolower($customer_mem) == 'megavoter') {

                $mega_pro_val = $settings['dong_mega_mem'];

                $cart_items = mega_add_variation_to_cart($mega_pro_val, $variation);
            } elseif (strtolower($customer_mem) == 'patron') {

                $patron_pro_val = $settings['dong_patron_mem'];

                $cart_items = mega_add_variation_to_cart($patron_pro_val, $variation);
            }

            if (!email_exists($customer_email) && !empty($cart_items)) {
                $random_password = wp_generate_password();

                $display_name = $customer_first_name . ' ' . $customer_last_name;

                $user_id = wc_create_new_customer($customer_email, $display_name, $random_password);

                $affiliate_user_id = get_current_user_id();
                // Step 4: Create the order
                $order = wc_create_order();
                $order->set_customer_id($user_id);
                $order->update_meta_data('mega_affid', $affiliate_user_id);

                foreach ($cart_items as $pid) {

                    $pp = wp_get_post_parent_id($pid);

                    if ($pp == 0) {

                        $id = $pid;
                    } else {

                        $id = $pp;
                    }

                    $get_quantity_yam = get_post_meta($id, '_qty_args', true);

                    if (is_array($get_quantity_yam) && !empty($get_quantity_yam)) {
                        $order->add_product(wc_get_product($id), $get_quantity_yam['qty_min']);
                    } else {
                        $order->add_product(wc_get_product($id), 1);
                    }
                }

                // Step 6: Add shipping
                $shipping = new WC_Order_Item_Shipping();
                $shipping->set_method_title('Free shipping');
                $shipping->set_method_id('free_shipping:1'); // set an existing Shipping method ID
                $shipping->set_total(0); // optional
                $order->add_item($shipping);

                // Step 7: Set billing and shipping addresses
                $address = array(
                    'first_name' => $customer_first_name,
                    'last_name' => $customer_last_name,
                    'company' => '',
                    'email' => $customer_email,
                    'phone' => $customer_phone,
                    'address_1' => $customer_address,
                    'address_2' => '',
                    'city' => $customer_city,
                    'state' => $customer_state,
                    'postcode' => $customer_postcode,
                    'country' => $customer_country
                );
                $order->set_address($address, 'billing');
                $order->set_address($address, 'shipping');

                // Step 8: Add payment method and set order status
                $order->set_payment_method('preorder');
                $order->set_status('wc-completed', 'Order is created From Importer');

                // Step 9: Calculate and save the order
                $order->calculate_totals();
                $order->save();

                // Step 11: Update the order status message
                $order_id = $order->get_id();
                if ($order_id) {
                    $order_obj = wc_get_order($order_id);

                    mega_set_membership_level($order_obj); //7
                    mega_custom_ordermeta_update($order_obj); //8
                    mega_update_mlm_database($order_obj); //9
                    use_email_as_username($order_id);
                }
            }
        } else {
            echo '<div class="error-box">Order Data could not inserted.Please use unique phone number or email address and try again</div>';
        }
    }
}


/* dongtraders custom export order list */

function dongtraders_custom_order_created_list()
{
    global $wpdb;
    $order_table_name = $wpdb->prefix . 'dong_order_export_table';
    ?>

    <div class=" cpm-table-wrap">
        <div class="export-section">

            <form action="" id="export-csv-order">
                <span id="from">From</span>
                <input id="start-month" name="start_month" type="date" size="2">
                <span id="to">To</span>
                <input id="end-month" name="end_month" type="date" size="2">
                <select name="affilate_id" id="affilate_id">
                    <?php
                    $get_all_users = get_users();
                    echo '<option value="">Select Affilate User</option>';
                    foreach ($get_all_users as $get_all_user) {
                        echo '<option value="' . $get_all_user->ID . '">' . $get_all_user->user_login . '</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="button button-primary buttonload">Export CSV<i
                        class="fa fa-spinner fa-spin export-loader"></i></button>
            </form>
        </div>
        <table id="qr-all-list">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Date</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone No.</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Postcode</th>
                    <th>Product Id</th>
                    <th>Variation Id</th>
                    <th>Affilate User</th>
                    <th>Remove</th>

                </tr>
            </thead>
            <tbody>
                <?php

                $get_order_results = $wpdb->get_results("SELECT *  FROM $order_table_name ORDER BY id DESC;");

                //$get_url = home_url() . '/wp-admin/admin.php?page=dongtrader_api_settings';
                /* $current_page = ''; */
                if (!empty($get_order_results)) {
                    $i = 1;
                    foreach ($get_order_results as $export_order) {

                        echo '
                     <tr>
                    <td>' . $i . '</td>
                     <td>' . $export_order->created_at . '</td>
                   <td>' . $export_order->customer_email . '</td>
                   <td>' . $export_order->customer_first_name . '</td>
                   <td>' . $export_order->customer_last_name . '</td>
                   <td>' . $export_order->customer_phone . '</td>
                   <td>' . $export_order->customer_address . '</td>
                   <td>' . $export_order->customer_country . '</td>
                   <td>' . $export_order->customer_state . '</td>
                   <td>' . $export_order->customer_city . '</td>
                   <td>' . $export_order->customer_postcode . '</td>
                   <td>' . $export_order->product_id . '</td>
                   <td>' . $export_order->product_varition_id . '</td>
                   <td>' . $export_order->affilate_user_id . '</td>
                 <td>
                 <form action="" method="post">
                        <input type="hidden" name="export-id" value="' . $export_order->id . '">
                       <button type="submit" name="delete-export" value="Delete" class="cpm-btn export-delete dashicons-before dashicons-trash"></button>
                        </form>
                        </td>
                </tr>
                ';
                        $i++;
                    }
                } else {
                    echo '<div class="error-box">No Records Found</div>';
                }

                // Handle delete
                if (isset($_POST['delete-export'])) {

                    $delete_id = (int) $_POST['export-id'];

                    // Delete data in mysql from row that has this id
                    $result = $wpdb->delete($order_table_name, array('id' => $delete_id));

                    // if successfully deleted
                    if ($result) {

                        echo '<div class="success-box">Deleted order ID-> ' . $delete_id . ' Successfully</div>';
                    } else {
                        echo '<div class="error-box">Order Data could not Deleted ! Please Try again</div>';
                    }
                    /*  wp_redirect($current_page); */
                }
                ?>


            </tbody>
        </table>
    </div>
    <?php
}

/*ajax function to run csv exporter*/

if (!function_exists('dong_custom_order_exporter_csv_files')) {
    function dong_custom_order_exporter_csv_files($post)
    {
        // Security check: Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dong_custom_order_exporter_csv_files')) {
            wp_die('Security check failed');
        }
        
        // Security check: Verify user is logged in
        if (!is_user_logged_in()) {
            wp_die('You must be logged in to perform this action');
        }
        
        // Security check: Verify user has admin capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $get_start_date = $_POST['start_date'];
        $get_end_date = $_POST['end_date'];
        $get_affilate_user_id = $_POST['user_id'];

        global $wpdb;
        $get_table_name = $wpdb->prefix . 'dong_order_export_table';

        if (!empty($get_start_date) && !empty($get_end_date) && !empty($get_affilate_user_id)) {
            $get_custom_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $get_table_name WHERE created_at BETWEEN %s AND %s AND affilate_user_id = %d", 
                $get_start_date, $get_end_date, $get_affilate_user_id
            ), ARRAY_A);
        } elseif (!empty($get_start_date) && !empty($get_end_date) && empty($get_affilate_user_id)) {
            $get_custom_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $get_table_name WHERE created_at BETWEEN %s AND %s", 
                $get_start_date, $get_end_date
            ), ARRAY_A);
        }

        if (DateTime::createFromFormat('Y-m-d', $get_start_date) == false && DateTime::createFromFormat('Y-m-d', $get_end_date) == false) {
            $get_custom_orders = $wpdb->get_results("SELECT * FROM $get_table_name ", ARRAY_A);
        }
        if (!empty($get_custom_orders)) {
            $cpm_order_exporter_generate_csv_filename = 'dongtraders-custom-orders' . date('Ymd_His') . '-export.csv';
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename={$cpm_comment_exporter_generate_csv_filename}');
            $output = fopen('php://output', 'w');

            fputcsv($output, ['csv_id', 'customer_email', 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_address_1', 'billing_postcode', 'billing_city', 'billing_state', 'billing_country', 'customer_membership', 'customer_sector', 'affilate_user_id']);

            foreach ($get_custom_orders as $get_custom_order) {
                $export_id = $get_custom_order['id'];
                $export_customer_email = $get_custom_order['customer_email'];
                $export_customer_first_name = $get_custom_order['customer_first_name'];
                $export_customer_last_name = $get_custom_order['customer_last_name'];
                $export_customer_phone = $get_custom_order['customer_phone'];
                $export_customer_address = $get_custom_order['customer_address'];
                $export_customer_country = $get_custom_order['customer_country'];
                $export_customer_state = $get_custom_order['customer_state'];
                $export_customer_postcode = $get_custom_order['customer_postcode'];
                $export_customer_city = $get_custom_order['customer_city'];
                $export_membership = $get_custom_order['customer_membership'];
                $export_sector = $get_custom_order['customer_sector'];
                $export_affilate_user_id = $get_custom_order['affilate_user_id'];

                fputcsv($output, [$export_id, $export_customer_email, $export_customer_first_name, $export_customer_last_name, $export_customer_phone, $export_customer_address, $export_customer_postcode, $export_customer_city, $export_customer_state, $export_customer_country, $export_membership, $export_sector, $export_affilate_user_id]);
            }
            fclose($output);
        }

        die();
    }

    add_action('wp_ajax_dong_custom_order_exporter_csv_files', 'dong_custom_order_exporter_csv_files');
}

/* show current user added affilate order and exporter */

function dongtraders_show_user_affilate_order()
{
    global $wpdb;
    $order_table_name = $wpdb->prefix . 'dong_order_export_table';
    $user_ID = get_current_user_id();

    ?>
    <div class="cpm-table-wrap">
        <div class="export-section">

            <form action="" id="export-csv-order">
                <div class=" export-date export-date-from">
                    <span id="from">From</span>
                    <input id="start-month" name="start_month" type="date" size="2">
                </div>
                <div class="export-date export-date-to">
                    <span id="to">To</span>
                    <input id="end-month" name="end_month" type="date" size="2">
                </div>

                <input id="end-month" name="affilate_id" type="hidden" size="2" value="<?php echo $user_ID; ?>">
                <button type="submit" class="button button-primary buttonload">Export CSV<i
                        class="fa fa-spinner fa-spin export-loader"></i></button>
            </form>
        </div>
        <table id="my-account-affilate-order">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Date</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Phone No.</th>
                    <th>Country</th>
                    <th>State</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>Postcode</th>
                    <th>Membership</th>
                    <th>Sector</th>
                    <th>Affilate User</th>
                    <th>Remove</th>

                </tr>
            </thead>
            <tbody>
                <?php

                $get_order_results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $order_table_name WHERE affilate_user_id = %d ORDER BY id DESC", 
                    $user_ID
                ), ARRAY_A);
                //$get_url = home_url() . '/wp-admin/admin.php?page=dongtrader_api_settings';
                $current_page = home_url($_SERVER['REQUEST_URI']);
                if (!empty($get_order_results)) {
                    $i = 1;
                    foreach ($get_order_results as $export_order) {

                        echo '
                     <tr>
                    <td>' . $i . '</td>
                     <td>' . $export_order->created_at . '</td>
                   <td>' . $export_order->customer_email . '</td>
                   <td>' . $export_order->customer_first_name . '</td>
                   <td>' . $export_order->customer_last_name . '</td>
                   <td>' . $export_order->customer_phone . '</td>
                   <td>' . $export_order->customer_address . '</td>
                   <td>' . $export_order->customer_country . '</td>
                   <td>' . $export_order->customer_state . '</td>
                   <td>' . $export_order->customer_city . '</td>
                   <td>' . $export_order->customer_postcode . '</td>
                   <td>' . $export_order->customer_membership . '</td>
                   <td>' . $export_order->customer_sector . '</td>
                   <td>' . $export_order->affilate_user_id . '</td>
                 <td>
                 <form action="" method="post">
                        <input type="hidden" name="export-id" value="' . $export_order->id . '">
                       <button type="submit" name="delete-export" value="Delete" class="cpm-btn export-delete dashicons-before dashicons-trash"></button>
                        </form>
                        </td>
                </tr>
                ';
                        $i++;
                    }
                } else {
                    echo '<div class="error-box">No Records Found</div>';
                }

                // Handle delete
                if (isset($_POST['delete-export'])) {

                    $delete_id = (int) $_POST['export-id'];

                    // Delete data in mysql from row that has this id
                    $result = $wpdb->delete($order_table_name, array('id' => $delete_id));

                    // if successfully deleted
                    if ($result) {

                        echo '<div class="success-box">Deleted order ID-> ' . $delete_id . ' Successfully</div>';
                    } else {
                        echo '<div class="error-box">Order Data could not Deleted ! Please Try again</div>';
                    }
                    wp_redirect($current_page);
                }
                ?>


            </tbody>
        </table>
    </div>

    <?php
}

// set quantity product for yam sticker as 10 quantity

// Displaying quantity setting fields on admin product pages
add_action('woocommerce_product_options_pricing', 'wc_qty_add_product_field');
function wc_qty_add_product_field()
{
    global $product_object;

    $values = $product_object->get_meta('_qty_args');

    echo '</div><div class="options_group quantity hide_if_grouped">
    <style>div.qty-args.hidden { display:none; }</style>';

    woocommerce_wp_checkbox(array( // Checkbox.
        'id' => 'qty_args',
        'label' => __('Quantity settings', 'woocommerce'),
        'value' => empty($values) ? 'no' : 'yes',
        'description' => __('Enable this to show and enable the additional quantity setting fields.', 'woocommerce'),
    ));

    echo '<div class="qty-args hidden">';

    woocommerce_wp_text_input(array(
        'id' => 'qty_min',
        'type' => 'number',
        'label' => __('Set Quantity', 'woocommerce-max-quantity'),
        'placeholder' => '',
        'desc_tip' => 'true',
        'description' => __('Set a minimum allowed quantity limit (a number greater than 0).', 'woocommerce'),
        'custom_attributes' => array('step' => 'any', 'min' => '0'),
        'value' => isset($values['qty_min']) && $values['qty_min'] > 0 ? (int) $values['qty_min'] : 10,
    ));

    echo '</div>';
}

// Show/hide setting fields (admin product pages)
add_action('admin_footer', 'product_type_selector_filter_callback');
function product_type_selector_filter_callback()
{
    global $pagenow, $post_type;

    if (in_array($pagenow, array('post-new.php', 'post.php')) && $post_type === 'product'):
        ?>
        <script>
            jQuery(function ($) {
                if ($('input#qty_args').is(':checked') && $('div.qty-args').hasClass('hidden')) {
                    $('div.qty-args').removeClass('hidden')
                }
                $('input#qty_args').click(function () {
                    if ($(this).is(':checked') && $('div.qty-args').hasClass('hidden')) {
                        $('div.qty-args').removeClass('hidden');
                    } else if (!$(this).is(':checked') && !$('div.qty-args').hasClass('hidden')) {
                        $('div.qty-args').addClass('hidden');
                    }
                });
            });
        </script>
        <?php
    endif;
}

// Save quantity setting fields values
add_action('woocommerce_admin_process_product_object', 'wc_save_product_quantity_settings');
function wc_save_product_quantity_settings($product)
{
    if (isset($_POST['qty_args'])) {
        $values = $product->get_meta('_qty_args');

        $product->update_meta_data('_qty_args', array(
            'qty_min' => isset($_POST['qty_min']) && $_POST['qty_min'] > 0 ? (int) wc_clean($_POST['qty_min']) : 0,
            // 'qty_max' => isset($_POST['qty_max']) && $_POST['qty_max'] > 0 ? (int) wc_clean($_POST['qty_max']) : -1,
            // 'qty_step' => isset($_POST['qty_step']) && $_POST['qty_step'] > 1 ? (int) wc_clean($_POST['qty_step']) : 1,
        ));
    } else {
        $product->update_meta_data('_qty_args', array());
    }
}

// The quantity settings in action on front end
add_filter('woocommerce_quantity_input_args', 'filter_wc_quantity_input_args', 99, 2);
function filter_wc_quantity_input_args($args, $product)
{
    if ($product->is_type('variation')) {
        $parent_product = wc_get_product($product->get_parent_id());
        $values = $parent_product->get_meta('_qty_args');
    } else {
        $values = $product->get_meta('_qty_args');
    }

    if (!empty($values)) {
        // Min value
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $args['min_value'] = $values['qty_min'];

            if (!is_cart()) {
                $args['input_value'] = $values['qty_min']; // Starting value
            }
        }
    }
    return $args;
}

// Ajax add to cart, set "min quantity" as quantity on shop and archives pages
add_filter('woocommerce_loop_add_to_cart_args', 'filter_loop_add_to_cart_quantity_arg', 10, 2);
function filter_loop_add_to_cart_quantity_arg($args, $product)
{
    $values = $product->get_meta('_qty_args');

    if (!empty($values)) {
        // Min value
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $args['quantity'] = $values['qty_min'];
        }
    }
    return $args;
}

// The quantity settings in action on front end (For variable productsand their variations)
add_filter('woocommerce_available_variation', 'filter_wc_available_variation_price_html', 10, 3);
function filter_wc_available_variation_price_html($data, $product, $variation)
{
    $values = $product->get_meta('_qty_args');

    if (!empty($values)) {
        if (isset($values['qty_min']) && $values['qty_min'] > 1) {
            $data['min_qty'] = $values['qty_min'];
        }
    }

    return $data;
}
function dongtraders_set_product_quantity($product_id)
{
    $get_qty = get_post_meta($product_id, '_qty_args', true);
    if (!empty($get_qty)) {
        $get_qty_no = $get_qty['qty_min'];
        return $get_qty_no;
    }
    //return 0;
}

function dongtrader_release_funds_tablelist()
{
    global $wpdb;

    $release_fund = $wpdb->prefix . 'release_groups_profit';
    $group_table = $wpdb->prefix . 'mega_mlm_groups';

    // Filter variables
    $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
    $group_name = isset($_GET['group_name']) ? sanitize_text_field($_GET['group_name']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

    // Build the SQL query with filter conditions
    $release_fund_prepared_query = "SELECT * FROM $release_fund";

    if ($filter_type === 'date-range' && $start_date && $end_date) {
        $release_fund_prepared_query .= $wpdb->prepare(" WHERE release_date BETWEEN %s AND %s", $start_date, $end_date);
    } elseif ($filter_type === 'group-name' && $group_name) {
        $release_fund_prepared_query .= $wpdb->prepare(" WHERE group_id IN (SELECT group_id FROM $group_table WHERE circle_name LIKE %s)", "%{$group_name}%");
    } elseif ($filter_type === 'all') {
        // No specific filter selected, return all data
    }

    $release_fund_prepared_query .= " ORDER BY release_date DESC";

    // Get results from the SQL query
    $release_fund_results = $wpdb->get_results($release_fund_prepared_query, ARRAY_A);

    $items_per_page = 10; // Number of items to display per page
    $paged = isset($_GET['rfapaged']) ? (int) $_GET['rfapaged'] : 1; // Get current page number


    ?>
    <h3>Disaster Relief Funds List</h3>
    <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="filter-form" id="posts-filter">
        <input type="hidden" name="page" value="dongtrader_api_settings">
        <div class="tablenav top">
            <div class="post_filter">
                <select name="filter_type" id="filter-type">
                    <option value="all" <?php selected($filter_type, 'all'); ?>>All</option>
                    <option value="group-name" <?php selected($filter_type, 'group-name'); ?>>By Group Name</option>
                    <option value="date-range" <?php selected($filter_type, 'date-range'); ?>>By Date Range</option>
                </select>
                <div id="group-name-filter" class="filter-field" <?php if ($filter_type !== 'group-name') { ?>style="display: none;" <?php } ?>>
                    <input type="text" name="group_name" id="group-name-input" placeholder="Enter group name"
                        value="<?php echo $group_name; ?>">
                </div>
                <div id="date-range-filter" class="filter-field" <?php if ($filter_type !== 'date-range') { ?>style="display: none;" <?php } ?>>
                    <input type="date" name="start_date" id="start-date-input" placeholder="Start date"
                        value="<?php echo $start_date; ?>">
                    <input type="date" name="end_date" id="end-date-input" placeholder="End date"
                        value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" id="apply-filter">Apply Filter</button>
                <button type="reset" id="reset-filter">Reset Filter</button>
            </div>
        </div>
    </form>
    <div class="cpm-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Released Date</th>
                    <th>Group Name</th>
                    <th>Released Amount</th>
                    <th>Release Cause</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($release_fund_results)) {
                    $symbol = get_woocommerce_currency_symbol();
                    // Split array into chunks based on items per page
                    $paginated_release_fund_results = array_chunk($release_fund_results, $items_per_page);

                    $current_items = $paginated_release_fund_results[$paged - 1]; // Get the items for the current page
                    $i = 1;
                    foreach ($current_items as $gr) {

                        $group_name = $wpdb->get_var($wpdb->prepare("SELECT circle_name FROM $group_table WHERE group_id = %d", (int) $gr['group_id']));
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $gr['release_date'] ?></td>
                            <td><?php echo $group_name ?></td>
                            <td><?php echo $symbol . $gr['release_amount'] ?></td>
                            <td><?php echo $gr['release_note'] ?></td>
                            <td><button class="rf-del" data-rfid="<?php echo $gr['id'] ?>">Delete</td>
                        </tr>
                        <?php $i++;
                    } ?>
                <?php } else { ?>
                    <tr>
                        <td style="text-align:center;" colspan="4">Details Not Found</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <?php
    if (!empty($release_fund_results)) {
        // Pagination links
        echo '<div class="pagination" style="float:right">';
        echo paginate_links(array(
            'base' => add_query_arg('rfapaged', '%#%'), // Base URL with page number placeholder
            'format' => '&rfapaged=%#%', // Add the paged parameter to the URL
            'prev_text' => __('&laquo; Previous', 'text-domain'),
            'next_text' => __('Next &raquo;', 'text-domain'),
            'total' => count($paginated_release_fund_results), // Total number of pages
            'current' => $paged, // Current page number
        ));
        echo '</div>';
    }
    ?>
    <script>
        jQuery(document).ready(function ($) {
            // Show/hide filter fields based on selected filter type
            $('#filter-type').on('change', function () {
                var filterType = $(this).val();
                $('.filter-field').hide();
                $('#' + filterType + '-filter').val('');
                $('#' + filterType + '-filter').show();
            });

            // Reset filter form
            $('#reset-filter').on('click', function () {
                $('.filter-form')[0].reset();
                $('.filter-field').hide(); // Hide all filter fields
                $('#filter-type').trigger('change'); // Trigger change event to show/hide fields based on selected filter type

                var url = removeURLParameter(window.location.href, 'filter_type');
                url = removeURLParameter(url, 'group_name');
                url = removeURLParameter(url, 'start_date');
                url = removeURLParameter(url, 'end_date');

                window.history.replaceState({}, document.title, url);

                window.location.reload();
            });

            // On page load, trigger change event to show/hide fields based on selected filter type
            $('#filter-type').trigger('change');

            // Helper function to remove a parameter from the URL
            function removeURLParameter(url, parameter) {
                var urlParts = url.split('?');
                if (urlParts.length >= 2) {
                    var prefix = encodeURIComponent(parameter) + '=';
                    var params = urlParts[1].split(/[&;]/g);

                    for (var i = params.length - 1; i >= 0; i--) {
                        if (params[i].lastIndexOf(prefix, 0) !== -1) {
                            params.splice(i, 1);
                        }
                    }

                    url = urlParts[0] + (params.length > 0 ? '?' + params.join('&') : '');
                    url = url.replace(/[?&]$/, ''); // Remove trailing ? or & if present
                }
                return url;
            }
        });
    </script>
    <?php
}

add_action('wp_ajax_dongtrader_delete_funds', 'dongtrader_delete_funds');
function dongtrader_delete_funds()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_delete_funds')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    // Security check: Verify user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    $row_id = isset($_POST['rowid']) ? absint($_POST['rowid']) : 0;

    if ($row_id != 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'release_groups_profit';

        // Delete the row with the specified ID
        $result = $wpdb->delete($table_name, array('id' => $row_id), array('%d'));

        if ($result !== false) {
            wp_send_json_success('Row with ID ' . $row_id . ' has been deleted.');
        } else {
            wp_send_json_error('Failed to delete the row.');
        }
    } else {
        wp_send_json_error('Invalid row ID.');
    }
}


function dongtrader_compare_released_funds($group_id, $current_releasing_amount)
{

    global $wpdb;

    $customers_table = $wpdb->prefix . 'mega_mlm_customers';

    $rfund_table = $wpdb->prefix . 'release_groups_profit';


    $total_release_amount = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(release_amount) AS total_release_amount 
          FROM $rfund_table
          WHERE group_id = %d",
        (int) $group_id
    ));

    $current_amount = $total_release_amount + $current_releasing_amount;

    $all_gr_user = $wpdb->prepare("SELECT user_id FROM $customers_table WHERE customer_group_id=%d", (int) $group_id);

    $users = $wpdb->get_results($all_gr_user, ARRAY_A);

    $users_arrays = array_column($users, 'user_id');

    $total_group_profit_sum = NULL;

    foreach ($users_arrays as $ua) {

        $group_prof_metas = get_user_meta($ua, '_group_details', true);

        if (empty($group_prof_metas))
            continue;

        foreach ($group_prof_metas as $gpm) {

            $total_group_profit_sum += $gpm['profit_amount'];
        }
    }

    return array(
        'compare' => $total_group_profit_sum >= $current_amount,
        'total_profit' => $total_group_profit_sum,
        'total_released' => $total_release_amount,
        'total_releaseable' => $total_group_profit_sum - $total_release_amount
    );
}


add_action('wp_ajax_dongtrader_release_funds', 'dongtrader_release_funds');
function dongtrader_release_funds()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'dongtrader_release_funds')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    // Security check: Verify user has admin capabilities
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }

    global $wpdb;

    $table_name = $wpdb->prefix . 'release_groups_profit';

    $customers_table = $wpdb->prefix . 'mega_mlm_groups';

    $form_data = $_POST['formdatas'];

    parse_str($form_data, $form_array);

    $rfund_group = intval($form_array['rfund-group']);

    $rfund_note = sanitize_text_field($form_array['rfund-note']);

    $rfund_amount = intval($form_array['rfund-amount']);

    if (empty($rfund_group) || empty($rfund_note) || empty($rfund_amount)) {
        wp_send_json_error('Please fill in all fields');
        return;
    }

    $compare = dongtrader_compare_released_funds($rfund_group, $rfund_amount);

    if ($compare['compare']) {

        $data = array(
            'release_date' => date('Y-m-d H:i:s'),
            'release_amount' => $rfund_amount,
            'release_note' => $rfund_note,
            'group_id' => $rfund_group,
        );



        $result = $wpdb->insert($table_name, $data);

        // Check if data insertion was successful
        if ($result === false) {
            wp_send_json_error('Some error occured.Please Try again');
            return;
        }

        wp_send_json_success('Group profit has been released successfully');
    } else {

        wp_send_json_error('The released amount must not exceed ' . $compare['total_releaseable']);
        return;
    }
}


function dongtrader_patron_form($atts)
{

    // Attributes
    $atts = shortcode_atts(
        array(),
        $atts,
        'patron_form'
    );
    $error_message = ' <span class="error-message"></span>';
    ob_start(); // Start output buffering

    ?>
    <style>
        /* Basic form styling */
        #mega-patron-credentials {

            margin: 0 auto;
            padding: 50px 70px;

            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            border-radius: 5px;
            /* padding: 15px; */
            background-color: #f9f9f9;
        }

        .group-heading {
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .error-message::after {
            content: "\A";
            white-space: pre;
        }

        .error-message {
            color: #d9534f;
        }

        .error-message {
            color: #d9534f;
        }

        .server-message {
            color: #d9534f;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-password {
            position: relative;
            float: right;
            cursor: pointer;
            margin-right: 15px;
            margin-top: -40px;
            font-size: 15px;

        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            margin-top: 5px;
            margin-bottom: 15px;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .video-container video {
            width: 100%;
            max-width: 500px;
            height: auto;
        }


        @media (min-width: 991px) {
            .flex-container {
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .input-wrapper {
                flex: 0 0 49%;
            }
        }

        p.detente-mega-info {
            margin-top: 0;
        }

        p.detente-mega-info a {
            font-size: 14px;
        }

        .form-group.detente-poc-form-group {
            padding-top: 15px;
        }

        @media (max-width: 767px) {
            #mega-patron-credentials {
                padding: 40px;
            }

            .group-heading {
                text-align: left;
                font-size: 20px;
            }
        }
    </style>
    <form action="" method="post" id="mega-patron-credentials" autocomplete="off">
        <div class="form-group">
            <p class="group-heading"><b>Patron Complete Credentials</b></p>
            <div class="video-container">
                <video controls>
                    <source
                        src="https://www.smallstreet.app/wp-content/uploads/2025/06/invideo-ai-1080-Fronting-the-Future-with-Coach-Tom_-YAM-2025-05-31-1.mp4"
                        type="video/mp4">
                </video>
            </div>

            <p>
                Underlined links will require Patron applicants to submit these form fields with valid platform ID
                credentials or access. Individual accounts only.<b>MEGAvoters for organization and political fundraising as
                    industry, size of 1.</b>

            </p>
            <p>
                <b>All fields with * are mandatory.</b>
            </p>
            <div class="mega-notices"></div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="mega-email">Email*:</label>
                    <input type="text" id="mega-email" name="mega-email"
                        placeholder="<?php _e('Enter your email address', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="mega-name">User Name*:</label>
                    <input type="text" id="mega-name" name="mega-name"
                        placeholder="<?php _e('Enter your username', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
            </div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="user_pass">Password*:</label>
                    <input type="password" id="mega-password" name="mega-password" minlength="7"
                        placeholder="<?php _e('Enter your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="user_pass">Confirm Password*:</label>
                    <input type="password" id="mega-confirm-password" name="mega-confirm-password" minlength="7"
                        placeholder="<?php _e('Retype your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>

                </div>
            </div>
            <label for="mega-mobile">Mobile number*:</label>
            <input type="tel" id="mega-mobile" name="mega-mobile"
                placeholder="<?php _e('Enter your telephone number', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>

            <label for="mega-v-card">V Card*:</label>
            <input type="text" id="mega-v-card" name="mega-v-card"
                placeholder="<?php _e('Enter your V-card URL', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.qrcode-tiger.com/payment" target="_blank">QR Tiger v-card with
                    social media links (Free account)</a></p>


            <label for="mega-paypal">Paypal:</label>
            <input type="text" id="mega-paypal" name="mega-paypal"
                placeholder="<?php _e('Enter your paypal Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.paypal.com/us/welcome/signup/#/login_info"
                    target="_blank">PayPal with banking links</a></p>


            <label for="mega-venmo">Venmo:</label>
            <input type="text" id="mega-venmo" name="mega-venmo"
                placeholder="<?php _e('Enter your venmo Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://venmo.com/signup/" target="_blank">Venmo with banking links</a>
            </p>


            <!-- <label for="mega-glassfrog">Glassfrog Profile:</label>
            <input type="text" id="mega-glassfrog" name="mega-glassfrog"
                placeholder="<?php //_e('Enter your glassfrog user id', 'cpm-dongtrader'); ?>">
            <?php //echo $error_message ?>
            <p class="detente-mega-info"><a href="https://app.glassfrog.com/accounts/new" target="_blank">Glassfrog (Free
                    account)</a></p> -->


            <!-- <label for="mega-crowdsignal">Crowdsignal:</label>
            <input type="text" id="mega-crowdsignal" name="mega-crowdsignal"
                placeholder="<?php //_e('Enter your crowdsignal id', 'cpm-dongtrader'); ?>">
            <?php //echo $error_message ?>
            <p class="detente-mega-info"><a href="https://crowdsignal.com/pricing/" target="_blank">Crowdsignal (Free
                    account)</a></p> -->

        </div>
        <!-- Grouped Form: Patron Organizing Communities (POC) Leadership -->
        <!-- <div class="form-group detente-poc-form-group">
            <p class="group-heading"><b>Patron Organizing Communities (POC) Leadership</b></p>
            <label for="mega-precoro">Precoro.com:</label>
            <input type="text" id="mega-precoro" name="mega-precoro"
                placeholder="<?php //_e('Enter your precoro details', 'cpm-dongtrader'); ?>">
            <p class="detente-mega-info"><a href="https://precoro.com/get-a-trial" target="_blank">Precoro.com (Get a
                    Trial)</a></p>


            <label for="mega-amazon-business">Amazon Business:</label>
            <input type="text" id="mega-amazon-business" name="mega-amazon-business"
                placeholder="<?php //_e('Enter your amazon business id', 'cpm-dongtrader'); ?>">
            <p class="detente-mega-info"><a href="https://business.amazon.com/en/find-solutions/punchout"
                    target="_blank">Amazon Business (Punchout)</a></p>
        </div> -->
        <div class="form-group detente-submit-form-group">
            <input type="submit" value="Submit">
            <div id="loader" class="wp-admin-loading" style="display: none;">
                <img src="<?php echo admin_url('images/loading.gif') ?>" alt="loading">
            </div>
            <div class="server-message">
            </div>
        </div>

    </form>
    <?php do_action('after_pcc_registration_form'); ?>
    <script>
        jQuery(document).ready(function ($) {
            $(".toggle-password").click(function () {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $(this).parent().find("input");

                if (input.attr("type") == "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }

            });
            $('#mega-patron-credentials').submit(function (event) {
                event.preventDefault();

                var loader = $('#loader');
                var form = $('#mega-patron-credentials');
                loader.show();
                $(this).find('input').each(function () {
                    var inputId = $(this).attr('id');
                    $('#' + inputId).css('border-color', '');
                    $('#' + inputId).parent().find('span').empty();

                });
                var formData = $(this).serialize();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mega_credentials_save',
                        formdata: formData,
                    },
                    success: function (response) {
                        var parsed = JSON.parse(response);
                        if ('errorClient' in parsed && parsed['errorClient'].length > 0) {
                            parsed.errorClient.forEach(function (error) {
                                var $errorField = $('#' + error.field_name);
                                // console.log($errorField);
                                if ($errorField.length > 0) {
                                    $('html, body').animate({
                                        scrollTop: $errorField.offset().top - 100
                                    }, 1000);
                                    $errorField.focus();
                                    $errorField.css('border-color', '#d9534f');
                                    if (error.field_name.indexOf("password") != -1) {
                                        $errorField.parent().find('span').text(error.message);
                                    } else {
                                        $errorField.next('span').text(error.message);
                                    }

                                    console.log($errorField.parent().find('span'));


                                }
                            });

                        } else if ('errorServer' in parsed && parsed['errorServer'].length > 0) {
                            parsed.errorServer.forEach(function (error) {
                                var serverErrorfield = $('#server-message');
                                serverErrorfield.focus();
                                serverErrorfield.text(error.message);

                            });
                        } else if ('valid' in parsed && parsed['valid']) {
                            form.empty();
                            form.html('<p>Congratulations on successfully becoming a valued member of MEGAvoter! Kindly check your email for further details and exciting updates.</p>');
                        }

                        loader.hide();

                    },

                    error: function () {
                        console.error('Form submission failed');
                        loader.hide();

                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('patron_form', 'dongtrader_patron_form');



function dongtrader_user_registration_form($atts)
{

    // Attributes
    $atts = shortcode_atts(
        array(),
        $atts,
        'user_registration_form'
    );
    $error_message = ' <span class="error-message"></span>';
    ob_start(); // Start output buffering

    ?>
    <style>
        /* Basic form styling */
        #mega-patron-credentials {

            margin: 0 auto;
            padding: 50px 70px;

            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .form-group {
            border-radius: 5px;
            /* padding: 15px; */
            background-color: #f9f9f9;
        }

        .group-heading {
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }

        .error-message::after {
            content: "\A";
            white-space: pre;
        }

        .error-message {
            color: #d9534f;
        }

        .error-message {
            color: #d9534f;
        }

        .server-message {
            color: #d9534f;
        }

        label {
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 3px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .toggle-password {
            position: relative;
            float: right;
            cursor: pointer;
            margin-right: 15px;
            margin-top: -40px;
            font-size: 15px;

        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            margin-top: 5px;
            margin-bottom: 15px;
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .video-container {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .video-container video {
            width: 100%;
            max-width: 500px;
            height: auto;
        }

        @media (min-width: 991px) {
            .flex-container {
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }

            .input-wrapper {
                flex: 0 0 49%;
            }
        }

        p.detente-mega-info {
            margin-top: 0;
        }

        p.detente-mega-info a {
            font-size: 14px;
        }

        .form-group.detente-poc-form-group {
            padding-top: 15px;
        }

        @media (max-width: 767px) {
            #mega-patron-credentials {
                padding: 40px;
            }

            .group-heading {
                text-align: left;
                font-size: 20px;
            }
        }

        /* Popup container */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            text-align: center;
            max-width: 600px;
        }

        /* Overlay */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, .8);
            z-index: 999;
        }

        /* Button styles */
        .popup .popup-button {
            background-color: #6F42C1;
            color: white;
            font-size: 18px;
            padding: 15px 30px;
            border: none;
            cursor: pointer;
            margin: 10px;
            text-decoration: none;
            display: inline-block;
        }

        .popup .popup-button:hover {
            background-color: transparent;
            color: #6F42C1;
            border: 1px solid #6F42C1;

        }
    </style>
    <form action="" method="post" id="mega-patron-credentials" autocomplete="off">
        <div class="form-group">
            <p class="group-heading"><b>Complete YAM'ers Credentials</b></p>
            <div class="video-container">
                <video controls>
                    <source
                        src="https://www.smallstreet.app/wp-content/uploads/2025/06/invideo-ai-1080-Fronting-the-Future-with-Coach-Tom_-YAM-2025-05-31-1.mp4"
                        type="video/mp4">
                </video>
            </div>
            <p>
                Creating an NFT (non-fungible token) value is made possible by establishing a QRTiger v-card and
                PayPal/Venmo payment gateway for patronage and incentives allocations. Your email and mobile device lock in
                your digital identity and group status. To qualify as a buyer or YAM'er, please complete the following:</b>
            </p>
            <p>
                <b>All fields with * are mandatory.</b>
            </p>
            <div class="mega-notices"></div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="mega-email">Email*:</label>
                    <input type="text" id="mega-email" name="mega-email"
                        placeholder="<?php _e('Enter your email address', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="mega-name">User Name*:</label>
                    <input type="text" id="mega-name" name="mega-name"
                        placeholder="<?php _e('Enter your username', 'cpm-dongtrader') ?>" required>
                    <?php echo $error_message ?>
                </div>
            </div>
            <div class="flex-container">
                <div class="input-wrapper">
                    <label for="user_pass">Password*:</label>
                    <input type="password" id="mega-password" name="mega-password" minlength="7"
                        placeholder="<?php _e('Enter your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>
                </div>
                <div class="input-wrapper">
                    <label for="user_pass">Confirm Password*:</label>
                    <input type="password" id="mega-confirm-password" name="mega-confirm-password" minlength="7"
                        placeholder="<?php _e('Retype your password', 'cpm-dongtrader') ?>" required>
                    <i class="toggle-password fa fa-fw fa-eye-slash"></i>
                    <?php echo $error_message ?>

                </div>
            </div>
            <label for="mega-mobile">Mobile number*:</label>
            <input type="tel" id="mega-mobile" name="mega-mobile"
                placeholder="<?php _e('Enter your telephone number', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>

            <label for="mega-v-card">V Card*:</label>
            <input type="text" id="mega-v-card" name="mega-v-card"
                placeholder="<?php _e('Enter your V-card URL', 'cpm-dongtrader') ?>" required>
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.qrcode-tiger.com/payment" target="_blank">QR Tiger v-card with
                    social media links (Free account)</a></p>


            <label for="mega-paypal">Paypal:</label>
            <input type="text" id="mega-paypal" name="mega-paypal"
                placeholder="<?php _e('Enter your paypal Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://www.paypal.com/us/welcome/signup/#/login_info"
                    target="_blank">PayPal with banking links</a></p>


            <label for="mega-venmo">Venmo:</label>
            <input type="text" id="mega-venmo" name="mega-venmo"
                placeholder="<?php _e('Enter your venmo Id', 'cpm-dongtrader'); ?>">
            <?php echo $error_message ?>
            <p class="detente-mega-info"><a href="https://venmo.com/signup/" target="_blank">Venmo with banking links</a>
            </p>
            <input type="hidden" id="redirect" name="redirect" value="legacytoliveby" />

        </div>

        <div class="form-group detente-submit-form-group">
            <input type="submit" value="Submit">
            <div id="loader" class="wp-admin-loading" style="display: none;">
                <img src="<?php echo admin_url('images/loading.gif') ?>" alt="loading">
            </div>
            <div class="server-message">
            </div>
        </div>
    </form>
    <div class="overlay" id="popupOverlay"></div>
    <div class="popup" id="discordPopup">
        <h3>Join Our Discord Server!<br>Gracebook</h3>
        <p>Official Discord for the Legacy To Live By community, the Cookie Jar economy, and the YAM movement.</p>
        <a href="https://discord.gg/g5jreAPbra" target="_blank" class="popup-button">Join Now</a>
    </div>
    <?php do_action('after_pcc_registration_form'); ?>
    <script>
        jQuery(document).ready(function ($) {
            $('#popupOverlay').click(function () {
                $('#discordPopup').fadeOut();
                $('#popupOverlay').fadeOut();
            });

            $(".toggle-password").click(function () {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $(this).parent().find("input");

                if (input.attr("type") == "password") {
                    input.attr("type", "text");
                } else {
                    input.attr("type", "password");
                }

            });
            $('#mega-patron-credentials').submit(function (event) {
                event.preventDefault();

                var loader = $('#loader');
                var form = $('#mega-patron-credentials');
                loader.show();
                $(this).find('input').each(function () {
                    var inputId = $(this).attr('id');
                    $('#' + inputId).css('border-color', '');
                    $('#' + inputId).parent().find('span').empty();

                });
                var formData = $(this).serialize();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'mega_credentials_save',
                        formdata: formData,
                        redirect: 'legacytoliveby',
                    },
                    success: function (response) {



                        var parsed = JSON.parse(response);
                        if ('errorClient' in parsed && parsed['errorClient'].length > 0) {
                            parsed.errorClient.forEach(function (error) {
                                var $errorField = $('#' + error.field_name);
                                // console.log($errorField);
                                if ($errorField.length > 0) {
                                    $('html, body').animate({
                                        scrollTop: $errorField.offset().top - 100
                                    }, 1000);
                                    $errorField.focus();
                                    $errorField.css('border-color', '#d9534f');
                                    if (error.field_name.indexOf("password") != -1) {
                                        $errorField.parent().find('span').text(error.message);
                                    } else {
                                        $errorField.next('span').text(error.message);
                                    }

                                    console.log($errorField.parent().find('span'));


                                }
                            });

                        } else if ('errorServer' in parsed && parsed['errorServer'].length > 0) {
                            parsed.errorServer.forEach(function (error) {
                                var serverErrorfield = $('#server-message');
                                serverErrorfield.focus();
                                serverErrorfield.text(error.message);

                            });
                        } else if ('valid' in parsed && parsed['valid']) {
                            form.empty();
                            form.html('<p>Congratulations on successfully becoming a valued member of MEGAvoter! Kindly check your email for further details and exciting updates.</p>');
                        }

                        loader.hide();

                        setTimeout(function () {
                            $('#discordPopup').fadeIn();
                            $('#popupOverlay').fadeIn();
                        }, 3000);
                    },

                    error: function () {
                        console.error('Form submission failed');
                        loader.hide();

                    }
                });
            });
        });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('user_registration_form', 'dongtrader_user_registration_form');


add_action('wp_ajax_mega_credentials_save', 'mega_credentials_save');

function mega_credentials_save()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mega_credentials_save')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    if (!isset($_POST['formdata']))
        return;

    parse_str($_POST['formdata'], $form_data_array);

    $sanitization_mapping = array(
        'mega_email' => 'sanitize_email',
        'mega_name' => 'sanitize_text_field',
        'mega_password' => 'sanitize_text_field',
        'mega-confirm-password' => 'sanitize_text_field',
        'mega_mobile' => 'sanitize_text_field',
        'mega_v-card' => 'sanitize_text_field',
        'mega_paypal' => 'sanitize_text_field',
        'mega_venmo' => 'sanitize_text_field',
        'redirect' => 'sanitize_text_field',
        'mega_glassfrog' => 'sanitize_text_field',
        'mega_crowdsignal' => 'sanitize_text_field',
        'mega_precoro' => 'sanitize_text_field',
        'mega_amazon-business' => 'sanitize_text_field'
    );

    $sanitized_data = array();

    foreach ($form_data_array as $field => $value) {

        if (isset($sanitization_mapping[$field]) && function_exists($sanitization_mapping[$field])) {
            $sanitized_data[$field] = call_user_func($sanitization_mapping[$field], $value);
        } else {
            $sanitized_data[$field] = $value;
        }
    }

    $client_validation_check = mega_validation_check($sanitized_data);

    if (empty($client_validation_check)) {
        $server_validation_check = [];
        $new_user_id = wp_create_user($sanitized_data['mega-name'], $sanitized_data['mega-password'], $sanitized_data['mega-email']);

        if (!is_wp_error($new_user_id)) {

            $u_data = get_userdata($new_user_id);

            $new_meta_array = array_slice($sanitized_data, 4, null, true);

            update_user_meta($new_user_id, 'patron_details', $new_meta_array);

            if ($u_data) {
                $name = $u_data->user_login;
                $to = $u_data->user_email;
                $subject = isset($form_data_array) && $form_data_array['redirect'] == 'legacytoliveby' ? __('🐳 The Whale Never Saw You Coming – Welcome, Organized Krill.') : __('Registration Confirmation', 'cpm-dongtrader');
                $tem_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'content-email-welcome.php';

                if (isset($form_data_array) && $form_data_array['redirect'] == 'legacytoliveby') {
                    $tem_path = CPM_DONGTRADER_PLUGIN_DIR . 'template-parts' . DIRECTORY_SEPARATOR . 'content-email-welcome-legacytoliveby.php';
                }
                ob_start();
                if (file_exists($tem_path))
                    load_template($tem_path, true, ['username' => $name]);
                $message = ob_get_clean();
                $headers = array('Content-Type: text/html; charset=UTF-8');

                wp_mail($to, $subject, $message, $headers);
                //display the popup after the mail is sent while registration.


            }
            if (class_exists('PMPro_Membership_Level')) {

                $current_level = pmpro_getMembershipLevelForUser($new_user_id);
                $level_id = 16;

                // If the user doesn't have the desired level, update it.
                if ($current_level->ID != $level_id) {
                    // Update membership level.
                    pmpro_changeMembershipLevel($level_id, $new_user_id);
                }
            }
        } else {

            $server_validation_check[] = [
                'valid' => 'false',
                'message' => __('User Cannot be created', 'cpm-dongtrader')

            ];
        }
    }

    $validity = !empty($client_validation_check)
        ? ['errorClient' => $client_validation_check]
        : (!empty($server_validation_check)
            ? ['errorServer' => $server_validation_check]
            : ['valid' => true]);

    echo wp_json_encode($validity);

    wp_die();
}





function mega_validation_check($sanitized_data)
{
    $validation_check = [];

    foreach ($sanitized_data as $attr => $sv) {

        if ($attr == "mega-email") {
            if (email_exists($sv)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("This email address is already registered", "cpm-dongtrader")
                ];
                break;
            }
            if (!filter_var($sv, FILTER_VALIDATE_EMAIL)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Please Enter Valid Email Address", "cpm-dongtrader")
                ];
                break;
            }
        }

        if ($attr == "mega-name") {

            if (username_exists($sv)) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Username already exists", "cpm-dongtrader")
                ];

                break;
            }

            if (!preg_match('/^.{5,}$/', $sv)) {

                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Username must be at least 5 characters long.", "cpm-dongtrader")
                ];

                break;
            }
        }

        if ($attr == "mega-password") {
            if (!preg_match('/^(?=.*[A-Z])(?=.*[!@#$%^&*()\-_=+{};:,<.>]).{7,}$/', $sv)) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Password must be at least 7 characters long and include at least one uppercase letter and one special character.", "cpm-dongtrader")
                ];
                break;
            }
        }

        if ($attr = "mega-confirm-password") {

            if ($sanitized_data['mega-password'] != $sanitized_data['mega-confirm-password']) {
                $validation_check[] = [
                    'valid' => 'false',
                    'field_name' => $attr,
                    'message' => __("Passwords doesnt match ", "cpm-dongtrader")
                ];
                break;
            }
        }
    }

    return $validation_check;
}

// Add a custom text area field to user profiles
function add_custom_user_profile_field($user)
{


    // $metas =  get_user_meta($user->ID, 'patron_details', true);
    $metas = [
        'mega-mobile' => '',
        'mega-v-card' => '',
        'mega-paypal' => '',
        'mega-venmo' => '',
        'mega-glassfrog' => '',
        'mega-crowdsignal' => '',
        'mega-precoro' => '',
        'mega-amazon-business' => '',
    ];
    $metas = array_keys($metas);
    $can_user_pay = get_user_meta($user->ID, 'can_pay', true);
    $can_user_pay = $can_user_pay == '1' ? true : false;
    ?>

    <h3>
        <?php _e('Extra User Details', 'cpm-dongtrader'); ?>
    </h3>
    <table class="form-table" role="presentation">
        <tbody>
            <?php
            foreach ($metas as $k) {

                $label_text = str_replace("mega-", "", $k);
                $capital_label = ucwords($label_text);

                if (str_contains($label_text, 'password'))
                    continue;

                $curr_v = get_user_meta($user->ID, $k, true);

                ?>
                <tr class="user-user-login-wrap">
                    <th><label for="<?= $k ?>">
                            <?= $capital_label ?>
                        </label></th>
                    <td><input type="text" name="<?= $k ?>" id="<?= $k ?>" value="<?= $curr_v ?>" class="regular-text"></td>
                </tr>
            <?php } ?>
            <tr class="user-user-login-wrap">
                <th><label for="can_pay">Can user pay for orders?</label></th>
                <td>
                    <select name="can_pay" id="can_pay">
                        <option value="1" <?php echo $can_user_pay ? 'selected' : '' ?>>Yes</option>
                        <option value="0" <?php echo !$can_user_pay ? 'selected' : '' ?>>No</option>
                    </select>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}
add_action('show_user_profile', 'add_custom_user_profile_field', 100);
add_action('edit_user_profile', 'add_custom_user_profile_field', 100);

// Save the custom field data
function save_custom_user_profile_field($user_id)
{

    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    // $metas =  get_user_meta($user_id, 'patron_details', true);
    $metas = [
        'mega-mobile' => '',
        'mega-v-card' => '',
        'mega-paypal' => '',
        'mega-venmo' => '',
        'mega-glassfrog' => '',
        'mega-crowdsignal' => '',
        'mega-precoro' => '',
        'mega-amazon-business' => '',
        'can_pay' => ''
    ];
    if (is_array($metas)) {
        $array_keys = array_keys($metas);

        if (empty($array_keys))
            return;
        foreach ($array_keys as $k) {
            if (str_contains($k, 'password'))
                continue;
            update_user_meta($user_id, $k, sanitize_textarea_field($_POST[$k]));
        }
    }
}
add_action('personal_options_update', 'save_custom_user_profile_field');
add_action('edit_user_profile_update', 'save_custom_user_profile_field');

























function custom_proof_of_delivery_form()
{

    $vals = get_option('dongtraders_api_settings_fields');
    $subs = array($vals['monthly_subscription_product'], $vals['annual_subscription_product']);


    ob_start();
    ?>
    <style>
        .delivery-form {
            max-width: 350px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.6);
            padding: 20px 30px;
        }
    </style>
    <div class="delivery-form">
        <p>
        <h3>Please read below before answering</h3>
        </p>
        <form method="POST" action="" id="deliveryproof">
            <input type="hidden" name="delivery_form" value="true">

            <p>
            <h4>Is this proof of delivery?</h4>
            </p>
            <label for="yes">Yes:</label>
            <input type="radio" name="delivery_proof" value="yes" required>
            <label for="no">No:</label>
            <input type="radio" name="delivery_proof" value="no" required>

            <p>
            <h4>Have you taken the (4) Detente 2.0 surveys?</h4>
            </p>
            <label for="survey_yes">Yes:</label>
            <input type="radio" name="survey_taken" value="yes" required>
            <label for="survey_no">No:</label>
            <input type="radio" name="survey_taken" value="no" required>

            <div class='buyer-seller' style="display:none">
                <label for="buyer_seller">Buyer or Seller :</label>
                <select name="buyer_seller" id="buyerseller">
                    <option value="">Select</option>
                    <option value="buyer">Buyer</option>
                    <option value="seller">Seller</option>
                </select>
            </div>
            <div class='subscription-product' style="display:none">
                <label for="subs-product">Select Subscription:</label>
                <select name="subs-product" id="subs-product">
                    <option value="">Select Subscription</option>
                    <?php foreach ($subs as $s) { ?>
                        <option value="<?= $s ?>">
                            <?php echo get_the_title($s); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="variation-products" style="display:none">
                <label for="var-product">Select Sectors:</label>
                <select name="vars-product" id="vars-product">
                </select>
            </div>
            <br />
            <input id="test-submit" type="submit" value="Submit">
        </form>
    </div>
    <script>
        jQuery(document).ready(function ($) {
            $('#deliveryproof').on('change', function (e) {
                // e.preventDefault();
                var dpval = $("input[name='delivery_proof']:checked").val();
                var spval = $("input[name='survey_taken']:checked").val();
                if (dpval == 'yes' && spval == 'yes') {
                    $('.buyer-seller').show();
                    $('.buyer-seller>select#buyerseller').prop('required', true);
                    var bsdropdownval = $('#buyerseller').val();
                    if (bsdropdownval === 'buyer') {
                        $('.subscription-product').show();
                        $('.subscription-product>select#subs-product').prop('required', true);



                    } else {
                        $('.subscription-product').hide();
                        $('.variation-products').hide();
                        $('.subscription-product>select#subs-product').prop('required', false);
                        $('.variation-products>select#vars-product').prop('required', false);
                    }
                } else {
                    $('.buyer-seller').hide();
                    $('.buyer-seller>select#buyerseller').prop('required', false);

                    $('.subscription-product').hide();
                    $('.subscription-product>select#subs-product').prop('required', false);
                    $(".buyer-seller>select#buyerseller").prop('selectedIndex', 0);
                    $(".subscription-product>select#subs-product").prop('selectedIndex', 0);

                    $('.variation-products').hide();
                    $('.variation-products>select#vars-product').prop('required', false);
                    $('.variation-products>select#vars-product').prop('selectedIndex', 0);

                }
            });
            $(document).on('change', '#subs-product', function () {
                var productid = $(this).find(":selected").val();
                var selector = $('#vars-product');
                var varselcontainer = $('.variation-products');
                if (productid) {
                    $('.variation-products>select#vars-product').prop('required', true);
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'mega_get_variations',
                            proid: productid,
                        },
                        success: function (response) {
                            selector.empty();
                            varselcontainer.show();
                            selector.append(response);
                        },
                    });

                } else {

                    $('.variation-products').hide();
                    $('.variation-products>select#vars-product').prop('required', false);
                }


            });

        });
    </script>

    <?php
    return ob_get_clean();
}




add_shortcode('delivery_form', 'custom_proof_of_delivery_form');

add_action('init', 'process_custom_form');
function process_custom_form()
{
    if (isset($_POST['delivery_form'])) {

        $options = get_option('dongtraders_api_settings_fields');
        $chase_page_id = $options['chase_page_selector'];
        $patron_page_id = $options['patron_page_selector'];
        $delivery_proof = isset($_POST['delivery_proof']) ? sanitize_text_field($_POST['delivery_proof']) : false;
        $survey_taken = isset($_POST['survey_taken']) ? sanitize_text_field($_POST['survey_taken']) : false;

        $redirect_url = '';
        if ($delivery_proof != false && $survey_taken != false) {

            if ($delivery_proof === 'yes' && $survey_taken === 'yes') {

                if ($_POST['buyer_seller'] == 'seller') {

                    $redirect_url = get_the_permalink($patron_page_id);
                } elseif ($_POST['buyer_seller'] == 'buyer') {


                    $product_id = isset($_POST['subs-product']) ? $_POST['subs-product'] : false;
                    $variation_id = isset($_POST['vars-product']) ? $_POST['vars-product'] : false;

                    if ($variation_id) {
                        $get_url = get_permalink($variation_id);

                        $modfied_url = $get_url . '&varid=' . $variation_id;

                        $redirect_url = $modfied_url;
                    } else {

                        $get_url = get_permalink($product_id);
                        $modfied_url = $get_url . '&add=1';
                        $redirect_url = $modfied_url;
                    }
                } else {
                    $redirect_url = home_url();
                }
            } elseif ($delivery_proof === 'no' && $survey_taken === 'no') {

                $redirect_url = get_the_permalink($chase_page_id);
            } elseif ($delivery_proof === 'yes' && $survey_taken === 'no') {

                $redirect_url = get_the_permalink($chase_page_id);
            } elseif ($delivery_proof === 'no' && $survey_taken === 'yes') {

                $redirect_url = home_url();
            }
            wp_redirect($redirect_url);
            exit();
        }
    }
}
add_action('wp_ajax_mega_get_variations', 'mega_get_variations');
function mega_get_variations()
{
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mega_get_variations')) {
        wp_die('Security check failed');
    }
    
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }

    $postid = sanitize_text_field($_POST['proid']);
    $product = wc_get_product($postid);
    $html = "<option value= ''>Select Sectors</option>";
    if ($product->is_type('variable')) {
        $current_products = $product->get_children();
        foreach ($current_products as $current_product) {
            $variation = wc_get_product($current_product);
            $varition_name = $variation->get_name();
            $html .= '<option value="' . $current_product . '">' . $varition_name . '</option>';
        }
    }

    echo $html;
    wp_die();
}


function custom_login_form_toggle_content()
{
    // Add your custom content here
    echo '<p>Welcome back! If you have an account, please login.</p>';
}
add_action('woocommerce-form-login-toggle', 'custom_login_form_toggle_content', 999);


function action_woocommerce_admin_order_item_headers($order)
{
    // Set the column name
    $column_name = __('Packing Weight', 'woocommerce');

    // Display the column name
    echo '<th class="line_packing_weight sortable" data-sort="string-ins">' . $column_name . '</th>';
}
add_action('woocommerce_admin_order_item_headers', 'action_woocommerce_admin_order_item_headers', 10, 1);


// closing popup on button click

add_action('wp_footer', function () {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery(document).on("click", "#close-popup", function () {
                elementorProFrontend.modules.popup.closePopup({}, event);
            });
        });
    </script>
    <?php
});


add_action('wp_footer', function () {

    if (wp_is_mobile() && !is_page('login')):
        ?>
        <script>
            jQuery(document).ready(function () {
                jQuery(document).find('body').removeClass('pmpro-login-page')
            });
        </script>
        <?php
    endif;
});

/**
 * Display XP balance and transaction history for users
 */

function dongtrader_display_xp_dashboard() {
    // Replace XP dashboard with leaderboard
    // Use same parameters as public leaderboard
    $atts = array(
        'per_page' => 50,
        'show_search' => 'yes',
        'show_filters' => 'yes',
    );
    
    return dongtrader_display_public_leaderboard($atts);
}

/**
 * Check if a transaction is eligible for redemption
 * Validates both status and maturity period
 * @param array $transaction Transaction array
 * @param int $umeta_id Umeta ID for tracking
 * @return array Array with 'eligible' bool and additional info
 */
function dongtrader_check_transaction_eligibility($transaction, $umeta_id) {
    if (!is_array($transaction)) {
        return array(
            'eligible' => false,
            'reason' => 'Invalid transaction data',
            'umeta_id' => $umeta_id
        );
    }
    
    // Check the status field - exclude if already in redemption process
    $status = isset($transaction['status']) ? $transaction['status'] : 'none';
    
    if ($status === 'requested' || $status === 'processing') {
        return array(
            'eligible' => false,
            'reason' => 'Already in redemption process',
            'umeta_id' => $umeta_id,
            'status' => $status
        );
    }
    
    // Get delivery date from transaction
    $delivery_date = dongtrader_get_delivery_date_from_xp_entry($transaction);
    
    // If no delivery date found, check if we can use fallback
    if (empty($delivery_date)) {
        // Fallback: Try to use umeta_id creation timestamp or current date
        // This handles legacy entries without dates
        global $wpdb;
        $umeta_row = $wpdb->get_row($wpdb->prepare(
            "SELECT umeta_id FROM {$wpdb->usermeta} WHERE umeta_id = %d",
            $umeta_id
        ));
        
        // For legacy entries without dates, we'll mark them as "unknown maturity"
        // Admin can review these manually
        return array(
            'eligible' => false,
            'reason' => 'No delivery date found (legacy entry)',
            'umeta_id' => $umeta_id,
            'immature' => true,
            'needs_review' => true
        );
    }
    
    // Check if entry is mature
    $is_mature = dongtrader_is_xp_entry_mature($delivery_date);
    
    if (!$is_mature) {
        $days_remaining = dongtrader_days_until_maturity($delivery_date);
        return array(
            'eligible' => false,
            'reason' => 'Entry not yet mature',
            'umeta_id' => $umeta_id,
            'immature' => true,
            'days_remaining' => $days_remaining,
            'delivery_date' => $delivery_date
        );
    }
    
    // Entry is eligible
    return array(
        'eligible' => true,
        'umeta_id' => $umeta_id,
        'delivery_date' => $delivery_date,
        'mature' => true
    );
}

/**
 * AJAX handler to get XP umeta_id values
 */
add_action('wp_ajax_get_xp_umeta_ids', 'dongtrader_get_xp_umeta_ids');
add_action('wp_ajax_submit_redemption_request', 'dongtrader_submit_redemption_request');

// Test AJAX handler
add_action('wp_ajax_test_ajax', 'dongtrader_test_ajax');

function dongtrader_test_ajax() {
    // Security check: Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'test_ajax')) {
        wp_die('Security check failed');
    }

    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to perform this action');
    }
    
    wp_send_json_success('Test AJAX working');
}

function dongtrader_get_xp_umeta_ids() {
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // Validate and sanitize user ID
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $current_user_id = get_current_user_id();
    
    if ($user_id !== $current_user_id || $user_id <= 0) {
        wp_send_json_error('Invalid user ID');
    }
    
    global $wpdb;
    
    // Define meta keys to search for
    $meta_keys = array('buyer_scan', 'seller_scan', 'personal_scan', '_discord_invite', '_discord_poll', '_talentshow_entry');
    
    // Create placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    
    // Single optimized query to get all umeta_ids with their meta_values
    $query = $wpdb->prepare(
        "SELECT umeta_id, meta_value FROM {$wpdb->usermeta} 
         WHERE user_id = %d AND meta_key IN ($placeholders)",
        array_merge(array($user_id), $meta_keys)
    );
    
    $results = $wpdb->get_results($query, ARRAY_A);
    
    // Check for database errors
    if ($wpdb->last_error) {
        wp_send_json_error('Database error: ' . $wpdb->last_error);
    }
    
    $filtered_umeta_ids = array();
    $filtered_count = 0;
    $immature_entries = array(); // Track immature entries for UI display
    $transaction_ids_map = array(); // Map umeta_id to array of transaction IDs (the 'id' field, not 'transaction_id')
    
    error_log("=== Umeta ID Filtering (with maturity check) ===");
    error_log("Total records found: " . count($results));
    
    // Helper function to extract the 'id' field (unique random code) from a transaction array
    // Note: This extracts 'id', NOT 'transaction_id'
    $extract_id = function($transaction) {
        if (isset($transaction['id']) && !empty($transaction['id'])) {
            return $transaction['id'];
        }
        return null;
    };
    
    // Filter out transactions with status 'requested' or already processed
    // AND filter out immature entries (not yet 8-12 weeks old)
    foreach ($results as $row) {
        $umeta_id = intval($row['umeta_id']);
        $meta_value = $row['meta_value'];
        
        // Skip if empty
        if (empty($meta_value)) {
            error_log("Skipping umeta_id $umeta_id: Empty meta_value");
            continue;
        }
        
        // Try to decode as JSON first
        $meta_data = json_decode($meta_value, true);
        $is_json = (json_last_error() === JSON_ERROR_NONE);
        
        // If not JSON, try PHP serialized data
        if (!$is_json) {
            $meta_data = @unserialize($meta_value);
            if ($meta_data !== false && is_array($meta_data)) {
                // Handle nested array structure - check if it's an array of transactions
                if (isset($meta_data[0]) && is_array($meta_data[0])) {
                    // It's an array of transactions, process each one
                    $umeta_id_added = false;
                    foreach ($meta_data as $transaction) {
                        $should_include = dongtrader_check_transaction_eligibility($transaction, $umeta_id);
                        if ($should_include['eligible']) {
                            if (!$umeta_id_added) {
                                $filtered_umeta_ids[] = $umeta_id;
                                $filtered_count++;
                                $umeta_id_added = true;
                            }
                            // Extract the 'id' field (unique random code) from transaction
                            $entry_id = $extract_id($transaction);
                            if ($entry_id) {
                                if (!isset($transaction_ids_map[$umeta_id])) {
                                    $transaction_ids_map[$umeta_id] = array();
                                }
                                $transaction_ids_map[$umeta_id][] = $entry_id;
                            }
                        } elseif (isset($should_include['immature'])) {
                            $immature_entries[] = $should_include;
                        }
                    }
                    continue; // Continue to next row
                } else {
                    // Single transaction
                $is_json = true;
                }
            }
        }
        
        if ($is_json && is_array($meta_data)) {
            $should_include = dongtrader_check_transaction_eligibility($meta_data, $umeta_id);
            
            if ($should_include['eligible']) {
                $filtered_umeta_ids[] = $umeta_id;
                $filtered_count++;
                error_log("Including umeta_id $umeta_id (mature and eligible)");
                
                // Extract the 'id' field (unique random code) from transaction
                $entry_id = $extract_id($meta_data);
                if ($entry_id) {
                    if (!isset($transaction_ids_map[$umeta_id])) {
                        $transaction_ids_map[$umeta_id] = array();
                    }
                    $transaction_ids_map[$umeta_id][] = $entry_id;
                }
            } elseif (isset($should_include['immature'])) {
                $immature_entries[] = $should_include;
                error_log("Excluding umeta_id $umeta_id - " . $should_include['reason']);
            } else {
                error_log("Excluding umeta_id $umeta_id - " . $should_include['reason']);
            }
        } else {
            // If we can't parse it, check if it's safe to include (fallback for old data)
            // For backwards compatibility, we'll include it but log a warning
            error_log("Warning: Including umeta_id $umeta_id (unparseable data - legacy entry)");
            $filtered_umeta_ids[] = $umeta_id;
            $filtered_count++;
        }
    }
    
    error_log("Filtered count: $filtered_count mature entries out of " . count($results));
    error_log("Immature entries: " . count($immature_entries));
    
    // Flatten transaction IDs array for easier access
    $all_transaction_ids = array();
    foreach ($transaction_ids_map as $umeta_id => $ids) {
        $all_transaction_ids = array_merge($all_transaction_ids, $ids);
    }
    
    // Return success response with filtered data
    wp_send_json_success(array(
        'umeta_ids' => $filtered_umeta_ids,
        'transaction_ids' => $all_transaction_ids, // Flat array of all transaction IDs
        'transaction_ids_map' => $transaction_ids_map, // Map of umeta_id => array of transaction IDs
        'count' => $filtered_count,
        'total_found' => count($results),
        'meta_keys_searched' => $meta_keys,
        'immature_entries' => $immature_entries // Include immature entries info for UI
    ));
}

/**
 * AJAX handler to submit redemption request
 */
function dongtrader_submit_redemption_request() {
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }
    
    // Get and validate form data
    $user_id = get_current_user_id();
    
    error_log('=== REDEMPTION REQUEST RECEIVED ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    $xp_amount = isset($_POST['xp_amount']) ? intval($_POST['xp_amount']) : 0;
    $yam_amount = isset($_POST['yam_amount']) ? floatval($_POST['yam_amount']) : 0;
    $usd_amount = isset($_POST['usd_amount']) ? floatval($_POST['usd_amount']) : 0;
    $xp_per_yam = isset($_POST['xp_per_yam']) ? floatval($_POST['xp_per_yam']) : 0;
    $yam_per_usd = isset($_POST['yam_per_usd']) ? floatval($_POST['yam_per_usd']) : 0;
    $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';
    $payment_details = isset($_POST['payment_details']) ? sanitize_textarea_field($_POST['payment_details']) : '';
    // Don't sanitize JSON - just get it as-is
    $meta_ids = isset($_POST['meta_ids']) ? $_POST['meta_ids'] : '';
    
    error_log("Extracted meta_ids: " . $meta_ids);
    error_log("Meta IDs type: " . gettype($meta_ids));
    error_log("Meta IDs length: " . strlen($meta_ids));
    error_log("Meta IDs JSON decode test: " . print_r(json_decode($meta_ids, true), true));
    
    // Validate required fields
    if (empty($payment_method)) {
        wp_send_json_error('Payment method is required');
    }
    
    // Validate payment method is PayPal or Venmo
    $valid_payment_methods = array('PayPal', 'Venmo');
    if (!in_array($payment_method, $valid_payment_methods)) {
        wp_send_json_error('Invalid payment method. Please select PayPal or Venmo.');
    }
    
    if (empty($payment_details)) {
        wp_send_json_error('Payment details are required');
    }
    
    // Validate payment details length
    if (strlen($payment_details) < 3) {
        wp_send_json_error('Payment details must be at least 3 characters long');
    }
    
    if ($xp_amount <= 0 || $yam_amount <= 0 || $usd_amount <= 0) {
        wp_send_json_error('Invalid redemption amounts');
    }
    
    // Validate minimum USD amount ($1.00)
    if ($usd_amount < 1.00) {
        wp_send_json_error('Minimum redemption amount is $1.00 USD');
    }
    
    // Validate meta_ids exists and is valid JSON
    if (empty($meta_ids)) {
        wp_send_json_error('No XP entries selected for redemption');
    }
    
    $meta_ids_array = json_decode($meta_ids, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($meta_ids_array) || empty($meta_ids_array)) {
        wp_send_json_error('Invalid meta IDs format. Please try again.');
    }
    
    global $wpdb;
    
    // ===== MATURITY AND REDEMPTION WINDOW VALIDATION =====
    
    // Check if currently within a redemption window
    if (!dongtrader_is_within_redemption_window()) {
        $days_until = dongtrader_days_until_next_redemption_window();
        $next_window = dongtrader_get_next_redemption_window();
        wp_send_json_error(array(
            'message' => 'Redemption can only be submitted during monthly redemption windows.',
            'days_until_window' => $days_until,
            'next_window_date' => $next_window['date'],
            'next_window_start' => $next_window['start']
        ));
    }
    
    // Validate maturity for all selected XP entries
    // Note: $meta_ids_array is already decoded and validated above
    if (!empty($meta_ids_array) && is_array($meta_ids_array)) {
            $immature_entries = array();
            $delivery_dates = array();
            
            foreach ($meta_ids_array as $umeta_id) {
                $umeta_id = intval($umeta_id);
                
                // Get the usermeta record
                $meta_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT meta_key, meta_value FROM {$wpdb->usermeta} WHERE umeta_id = %d AND user_id = %d",
                    $umeta_id, $user_id
                ));
                
                if (!$meta_row) {
                    continue;
                }
                
                // Parse transaction data
                $meta_data = json_decode($meta_row->meta_value, true);
                if (!$meta_data || !is_array($meta_data)) {
                    $meta_data = @unserialize($meta_row->meta_value);
                }
                
                if (is_array($meta_data)) {
                    // Handle array of transactions
                    if (isset($meta_data[0]) && is_array($meta_data[0])) {
                        foreach ($meta_data as $transaction) {
                            $check_result = dongtrader_check_transaction_eligibility($transaction, $umeta_id);
                            if (isset($check_result['immature']) && $check_result['immature']) {
                                $immature_entries[] = $check_result;
                            }
                            if (isset($check_result['delivery_date'])) {
                                $delivery_dates[] = $check_result['delivery_date'];
                            }
                        }
                } else {
                        // Single transaction
                        $check_result = dongtrader_check_transaction_eligibility($meta_data, $umeta_id);
                        if (isset($check_result['immature']) && $check_result['immature']) {
                            $immature_entries[] = $check_result;
                        }
                        if (isset($check_result['delivery_date'])) {
                            $delivery_dates[] = $check_result['delivery_date'];
                        }
                    }
                }
            }
            
            // If any entries are immature, reject the redemption
            if (!empty($immature_entries)) {
                $immature_list = array();
                foreach ($immature_entries as $entry) {
                    $days = isset($entry['days_remaining']) ? $entry['days_remaining'] : 'unknown';
                    $immature_list[] = "Entry #{$entry['umeta_id']} - {$days} days until mature";
                }
                wp_send_json_error(array(
                    'message' => 'Some selected XP entries are not yet mature (8-12 weeks required).',
                    'immature_entries' => $immature_entries,
                    'details' => implode(', ', $immature_list)
                ));
            }
            
            // Calculate oldest and youngest delivery dates for storage
            if (!empty($delivery_dates)) {
                $oldest_delivery = min($delivery_dates);
                $youngest_delivery = max($delivery_dates);
                $maturity_date = dongtrader_calculate_maturity_date($youngest_delivery);
            } else {
                $oldest_delivery = null;
                $youngest_delivery = null;
                $maturity_date = null;
            }
    } else {
        // If no meta_ids_array (should not happen due to earlier validation, but handle gracefully)
        $oldest_delivery = null;
        $youngest_delivery = null;
        $maturity_date = null;
    }
    
    // ===== END VALIDATION =====
    
    // Get maturity weeks setting
    $maturity_weeks = dongtrader_get_maturity_weeks();
    $is_within_window = dongtrader_is_within_redemption_window();
    
    // Apply settlement rate based on redemption date
    // September 1st: 100% settlement, Other months: 96.5% settlement
    $settlement_rate = dongtrader_get_settlement_rate();
    $usd_amount_original = $usd_amount;
    $usd_amount_settled = $usd_amount * $settlement_rate; // Final amount after settlement rate
    
    // Round settled amount to 2 decimal places
    $usd_amount_settled = round($usd_amount_settled, 2);
    
    // Create redemption table if it doesn't exist
    $table_name = $wpdb->prefix . 'dongtrader_redemptions';
    
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
    
    // Add new columns to existing table if they don't exist (for existing installations)
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'maturity_date'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN maturity_date datetime NULL AFTER transaction_id");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN oldest_delivery_date datetime NULL AFTER maturity_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN youngest_delivery_date datetime NULL AFTER oldest_delivery_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN maturity_weeks int(11) DEFAULT 10 AFTER youngest_delivery_date");
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN within_redemption_window tinyint(1) DEFAULT 0 AFTER maturity_weeks");
        $wpdb->query("ALTER TABLE $table_name ADD KEY maturity_date (maturity_date)");
    }
    
    // Insert redemption request with maturity data
    // Note: usd_redem stores the settled amount (after applying settlement rate)
    $result = $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'xp_redem' => $xp_amount,
            'yam_redem' => $yam_amount,
            'usd_redem' => $usd_amount_settled, // Store settled amount (after settlement rate)
            'conversion_rate_xp_yam' => $xp_per_yam,
            'conversion_rate_yam_usd' => $yam_per_usd,
            'meta_ids' => $meta_ids,
            'status' => 'pending',
            'payment_method' => $payment_method,
            'payment_details' => $payment_details,
            'redem_date' => current_time('mysql'),
            'maturity_date' => $maturity_date,
            'oldest_delivery_date' => $oldest_delivery,
            'youngest_delivery_date' => $youngest_delivery,
            'maturity_weeks' => $maturity_weeks,
            'within_redemption_window' => $is_within_window ? 1 : 0
        ),
        array(
            '%d', // user_id
            '%d', // xp_redem
            '%f', // yam_redem
            '%f', // usd_redem
            '%f', // conversion_rate_xp_yam
            '%f', // conversion_rate_yam_usd
            '%s', // meta_ids
            '%s', // status
            '%s', // payment_method
            '%s', // payment_details
            '%s', // redem_date
            '%s', // maturity_date
            '%s', // oldest_delivery_date
            '%s', // youngest_delivery_date
            '%d', // maturity_weeks
            '%d'  // within_redemption_window
        )
    );
    
    if ($result === false) {
        wp_send_json_error('Failed to submit redemption request: ' . $wpdb->last_error);
    }
    
    $redemption_id = $wpdb->insert_id;
    
    // Update user meta records to mark them as redeemed (pending)
    $updated_meta_details = array();
    $failed_meta_ids = array();
    
    if (!empty($meta_ids)) {
        $meta_ids_array = json_decode($meta_ids, true);
        if (is_array($meta_ids_array) && !empty($meta_ids_array)) {
            $updated_count = 0;
            $error_count = 0;
            
            error_log("=== REDEMPTION DEBUG START #$redemption_id ===");
            error_log("Attempting to update " . count($meta_ids_array) . " usermeta records");
            error_log("Meta IDs array: " . print_r($meta_ids_array, true));
            
            foreach ($meta_ids_array as $umeta_id) {
                $umeta_id = intval($umeta_id);
                error_log("Processing umeta_id: $umeta_id");
                
                // Get the full usermeta row for debugging
                $meta_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT umeta_id, user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE umeta_id = %d",
                    $umeta_id
                ));
                
                if (!$meta_row) {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Record not found'
                    );
                    error_log("  ERROR: No record found for umeta_id: $umeta_id");
                    continue;
                }
                
                error_log("  Found record: user_id={$meta_row->user_id}, meta_key={$meta_row->meta_key}");
                
                // Verify ownership
                if ($meta_row->user_id != $user_id) {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Ownership mismatch'
                    );
                    error_log("  ERROR: Ownership violation - belongs to user {$meta_row->user_id}, not $user_id");
                    continue;
                }
                
                if ($meta_row->meta_value) {
                    // Try to decode as JSON first
                    $meta_data = json_decode($meta_row->meta_value, true);
                    $is_json = (json_last_error() === JSON_ERROR_NONE);
                    
                    // If not JSON, try PHP serialized data
                    if (!$is_json) {
                        error_log("  Detected PHP serialized data, attempting to unserialize");
                        $meta_data = @unserialize($meta_row->meta_value);
                        $is_serialized = ($meta_data !== false);
                        
                        if ($is_serialized && is_array($meta_data)) {
                            error_log("  Successfully unserialized PHP data");
                            error_log("  Data structure: " . print_r(array_keys($meta_data), true));
                            
                            // Handle different array structures
                            // Case 1: Array of transactions like [0 => ['order_id' => 123, ...], 1 => [...], ...]
                            // This is the case for _buyer_details, _seller_details, etc.
                            if (isset($meta_data[0]) && is_array($meta_data[0])) {
                                // It's a nested array with multiple transactions
                                error_log("  Detected array of transactions with " . count($meta_data) . " items");
                                
                                // We need to update ALL transactions in this array, not just the first one
                                $modified = false;
                                foreach ($meta_data as $index => &$transaction) {
                                    if (is_array($transaction)) {
                                        // Get the current status
                                        $current_status = isset($transaction['status']) ? $transaction['status'] : 'none';
                                        
                                        // Only update if not already requested
                                        if ($current_status !== 'requested' && $current_status !== 'processing' && $current_status !== 'completed') {
                                            $transaction['status'] = 'requested';
                                            $transaction['redemption_id'] = $redemption_id;
                                            $transaction['redemption_date'] = current_time('mysql');
                                            $modified = true;
                                            error_log("  Updated transaction #$index (order_id: " . (isset($transaction['order_id']) ? $transaction['order_id'] : 'N/A') . ")");
                } else {
                                            error_log("  Skipped transaction #$index (already $current_status)");
                                        }
                                    }
                                }
                                unset($transaction); // Break reference
                                
                                if ($modified) {
                                    // Keep the full array structure for saving
                                    $meta_data_to_save = $meta_data; // This is now the full array with updated transactions
                                    // Keep original structure for processing
                                    $is_json = true; // Mark as processable
                                } else {
                                    error_log("  No transactions modified - all already have status");
                                }
                            } else {
                                // Single transaction (not in an array of transactions)
                                if (!isset($meta_data['status'])) {
                                    $meta_data['status'] = 'requested';
                                }
                                $meta_data['redemption_id'] = $redemption_id;
                                $meta_data['redemption_date'] = current_time('mysql');
                                $meta_data_to_save = $meta_data;
                                $is_json = true; // Mark as processable
                            }
                        }
                    }
                    
                    if ($is_json && is_array($meta_data)) {
                        // Check if this is an array of transactions (not a single transaction)
                        $is_array_of_transactions = isset($meta_data[0]) && is_array($meta_data[0]) && !isset($meta_data['status']);
                        
                        if (!$is_array_of_transactions) {
                            // This is a single transaction, check status
                            $current_status = isset($meta_data['status']) ? $meta_data['status'] : 'none';
                            error_log("  Current status: $current_status");
                            
                            // Only skip if already in a redemption process (requested or processing)
                            // Don't skip 'completed', 'released', 'redeemed', or other statuses
                            if ($current_status === 'requested' || $current_status === 'processing') {
                                error_log("  SKIPPED: Already marked as $current_status (previous redemption)");
                                $updated_count++;
                                $updated_meta_details[] = array(
                                    'umeta_id' => $umeta_id,
                                    'meta_key' => $meta_row->meta_key,
                                    'user_id' => $meta_row->user_id,
                                    'previous_status' => $current_status,
                                    'new_status' => $current_status,
                                    'xp_awarded' => isset($meta_data['xp_awarded']) ? $meta_data['xp_awarded'] : 0,
                                    'transaction_type' => isset($meta_data['transaction_type']) ? $meta_data['transaction_type'] : 'unknown',
                                    'note' => 'Already redeemed in previous request'
                                );
                                continue; // Skip updating, already marked
                            }
                            
                            // Update the status for all other statuses
                            $meta_data['status'] = 'requested';
                            $meta_data['redemption_id'] = $redemption_id;
                            $meta_data['redemption_date'] = current_time('mysql');
                            $meta_data_to_save = $meta_data;
                        } else {
                            error_log("  Processing array of " . count($meta_data) . " transactions");
                        }
                        
                        // Determine what data to save
                        $data_to_save = isset($meta_data_to_save) ? $meta_data_to_save : $meta_data;
                        
                        // Check if original data was serialized PHP format
                        $was_serialized = (strpos($meta_row->meta_value, 'a:') === 0);
                        
                        // Update the meta value 
                        if ($was_serialized) {
                            // Save as serialized PHP to maintain compatibility
                            $updated_value = serialize($data_to_save);
                            error_log("  Saving as PHP serialized format");
                        } else {
                            // Save as JSON
                            $updated_value = json_encode($data_to_save);
                            error_log("  Saving as JSON format");
                        }
                        
                        $update_result = $wpdb->update(
                            $wpdb->usermeta,
                            array('meta_value' => $updated_value),
                            array('umeta_id' => $umeta_id),
                            array('%s'),
                            array('%d')
                        );
                        
                        if ($update_result !== false) {
                            $updated_count++;
                            // Extract data from various possible structures
                            $xp_awarded = 0;
                            $transaction_count = 0;
                            
                            if (isset($meta_data['xp_awarded'])) {
                                // Single transaction
                                $xp_awarded = $meta_data['xp_awarded'];
                                $transaction_count = 1;
                            } elseif (isset($meta_data[0]) && is_array($meta_data[0])) {
                                // Array of transactions
                                $xp_awarded = 0;
                                $transaction_count = count($meta_data);
                                foreach ($meta_data as $trans) {
                                    if (isset($trans['xp_awarded'])) {
                                        $xp_awarded += intval($trans['xp_awarded']);
                                    }
                                }
                            }
                            
                            $transaction_type = 'unknown';
                            if (isset($meta_data['transaction_type'])) {
                                $transaction_type = $meta_data['transaction_type'];
                            } elseif (isset($meta_data['order_id'])) {
                                $transaction_type = 'order';
                            } elseif (isset($meta_data[0]['transaction_type'])) {
                                $transaction_type = $meta_data[0]['transaction_type'];
                            }
                            
                            $current_status_for_log = isset($current_status) ? $current_status : 'none';
                            
                            $updated_meta_details[] = array(
                                'umeta_id' => $umeta_id,
                                'meta_key' => $meta_row->meta_key,
                                'user_id' => $meta_row->user_id,
                                'previous_status' => $current_status_for_log,
                                'new_status' => 'requested',
                                'xp_awarded' => $xp_awarded,
                                'transaction_type' => $transaction_type,
                                'transaction_count' => $transaction_count
                            );
                            
                            if ($transaction_count > 1) {
                                error_log("  SUCCESS: Updated umeta_id $umeta_id with $transaction_count transactions");
                            } else {
                                error_log("  SUCCESS: Updated umeta_id $umeta_id");
                            }
                        } else {
                            $error_count++;
                            $failed_meta_ids[] = array(
                                'umeta_id' => $umeta_id,
                                'error' => $wpdb->last_error
                            );
                            error_log("  ERROR: Failed to update - " . $wpdb->last_error);
                        }
                    } else {
                        $error_count++;
                        $failed_meta_ids[] = array(
                            'umeta_id' => $umeta_id,
                            'error' => 'Invalid JSON data',
                            'raw_data' => substr($meta_row->meta_value, 0, 100) // First 100 chars for debugging
                        );
                        error_log("  ERROR: Invalid JSON data for umeta_id $umeta_id");
                        error_log("  RAW DATA: " . substr($meta_row->meta_value, 0, 200));
                        error_log("  JSON ERROR: " . json_last_error_msg());
                    }
                } else {
                    $error_count++;
                    $failed_meta_ids[] = array(
                        'umeta_id' => $umeta_id,
                        'error' => 'Empty meta_value'
                    );
                    error_log("  ERROR: Empty meta_value");
                }

            }
            
            // Log the update results
            error_log("=== REDEMPTION DEBUG END #$redemption_id ===");
            error_log("SUMMARY: Updated $updated_count records, $error_count errors");
        } else {
            error_log("Redemption #$redemption_id: Invalid meta_ids format - " . $meta_ids);
        }
    } else {
        error_log("Redemption #$redemption_id: No meta_ids provided");
    }
    
    // Send notification email to admin (optional)
    $admin_email = get_option('admin_email');
    $user = get_userdata($user_id);
    $subject = 'New Redemption Request #' . $redemption_id;
    $message = "A new redemption request has been submitted:\n\n";
    $message .= "Redemption ID: #" . $redemption_id . "\n";
    $message .= "User: " . $user->display_name . " (" . $user->user_email . ")\n";
    $message .= "XP Amount: " . number_format($xp_amount) . "\n";
    $message .= "YAM Amount: " . number_format($yam_amount, 2) . "\n";
    $message .= "USD Amount: $" . number_format($usd_amount, 2) . "\n";
    $message .= "Payment Method: " . $payment_method . "\n";
    $message .= "Payment Details: " . $payment_details . "\n";
    $message .= "Date: " . current_time('Y-m-d H:i:s') . "\n\n";
    $message .= "Please review and process this request.";
    
    wp_mail($admin_email, $subject, $message);
    
    // Return success response with debug information
    wp_send_json_success(array(
        'message' => 'Redemption request submitted successfully!',
        'redemption_id' => $redemption_id,
        'status' => 'pending',
        'debug' => array(
            'updated_count' => count($updated_meta_details),
            'failed_count' => count($failed_meta_ids),
            'updated_meta_details' => $updated_meta_details,
            'failed_meta_ids' => $failed_meta_ids
        )
    ));
}

/**
 * Shortcode to display XP dashboard
 */
function dongtrader_xp_dashboard_shortcode($atts) {
    return dongtrader_display_xp_dashboard();
}
add_shortcode('dongtrader_xp_dashboard', 'dongtrader_xp_dashboard_shortcode');

/**
 * Add XP dashboard to WooCommerce account page
 */
function dongtrader_add_xp_to_account_page() {
    if (is_account_page() && is_user_logged_in()) {
        echo dongtrader_display_xp_dashboard();
    }
}
add_action('woocommerce_account_dashboard', 'dongtrader_add_xp_to_account_page', 20);

/**
 * Test function to manually award XP (for testing purposes)
 * Usage: Add ?test_xp=1 to any page URL when logged in as admin
 */
function dongtrader_test_xp_award() {
    // Only allow admins to test
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_GET['test_xp']) && $_GET['test_xp'] == '1') {
        $user_id = get_current_user_id();
        $dong_user_role = get_user_meta($user_id, 'dong_user_role', true);
        
        // QR Code URL: https://smallstreet.app/proof?product_id=123&order_id=456
        // Determine if user is buyer or seller based on having seller transactions
        $seller_details = get_user_meta($user_id, '_seller_details', true);
        $is_seller = is_array($seller_details) && !empty($seller_details);
        
        $seller_xp = dongtrader_usd_to_xp(1); // 1 USD worth of XP for sellers
        $buyer_xp = dongtrader_usd_to_xp(10); // 10 USD worth of XP for buyers
        
        $xp_to_award = $is_seller ? $seller_xp : $buyer_xp;
        
        // Create test transaction
        $xp_transaction = array(
            'xp_awarded' => $xp_to_award,
            'transaction_type' => 'test_transaction',
            'phone_number' => 'test',
            'verification_date' => current_time('mysql'),
            'user_role' => $dong_user_role,
            'status' => 'completed'
        );
        
        if ($is_seller) {
            // Save to seller_details
            $seller_details = get_user_meta($user_id, 'seller_details', true);
            if (!is_array($seller_details)) {
                $seller_details = array();
            }
            $seller_details[] = $xp_transaction;
            update_user_meta($user_id, 'seller_details', $seller_details);
            
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Test XP Awarded!</strong> ' . number_format($seller_xp) . ' XP added to seller_details for user ID: ' . $user_id;
            echo '</div>';
        } else {
            // Save to buyer_details
            $buyer_details = get_user_meta($user_id, 'buyer_details', true);
            if (!is_array($buyer_details)) {
                $buyer_details = array();
            }
            $buyer_details[] = $xp_transaction;
            update_user_meta($user_id, 'buyer_details', $buyer_details);
            
            echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 4px;">';
            echo '<strong>Test XP Awarded!</strong> ' . number_format($buyer_xp) . ' XP added to buyer_details for user ID: ' . $user_id;
            echo '</div>';
        }
        
        // Update total XP balance
        $total_xp = get_user_meta($user_id, '_total_xp_balance', true);
        $total_xp = $total_xp ? intval($total_xp) : 0;
        $total_xp += $xp_to_award;
        update_user_meta($user_id, '_total_xp_balance', $total_xp);
    }
}
add_action('wp_head', 'dongtrader_test_xp_award');


/**
 * Add redemption popup JavaScript to footer
 */
add_action('wp_footer', 'dongtrader_redemption_popup_script');
function dongtrader_redemption_popup_script() {
    // Only load on account pages
    if (!is_account_page() || !is_user_logged_in()) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    console.log('Redemption popup script loaded');
    
    // Generate nonce for AJAX calls
    var ajaxNonce = '<?php echo wp_create_nonce('get_xp_umeta_ids'); ?>';
    var ajaxUserId = '<?php echo get_current_user_id(); ?>';
    var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Store redemption data globally
    window.currentRedemptionData = {};
    
    // Function to format numbers in scientific notation (matching PHP format)
    function formatScientificNotation(num, fracDigits) {
        if (num == 0 || num === null || num === undefined) {
            return '0';
        }
        
        // Default to 30 fractional digits for XP amounts
        fracDigits = fracDigits || 30;
        
        // Convert to number if string
        var numValue = typeof num === 'string' ? parseFloat(num) : num;
        
        if (isNaN(numValue)) {
            return '0';
        }
        
        // Use toExponential with specified fractional digits
        var scientific = numValue.toExponential(fracDigits);
        var parts = scientific.split('e');
        var mantissa = parts[0];
        var exponent = parts.length > 1 ? parseInt(parts[1].replace('+', '')) : 0;
        
        // For XP amounts, preserve trailing zeros to match PHP format
        // Don't remove trailing zeros when fracDigits is 30
        if (fracDigits < 30 && mantissa.indexOf('.') !== -1) {
            mantissa = mantissa.replace(/\.?0+$/, '');
        }
        
        // If exponent is 0, return as regular number
        if (exponent == 0) {
            var baseValue = parseFloat(mantissa);
            if (baseValue == Math.floor(baseValue)) {
                return baseValue.toString();
            }
            return baseValue.toString();
        }
        
        // Return in format: mantissa × 10^exponent
        return mantissa + ' × 10<sup>' + exponent + '</sup>';
    }
    
    // Redemption popup functions
    window.showRedemptionPopup = function(xpAmount, yamAmount, usdAmount, xpPerYam, yamPerUsd, formattedXp, formattedXpPerYam) {
        console.log("showRedemptionPopup called");
        
        // Store the original values globally
        window.currentRedemptionData = {
            xpAmount: xpAmount,
            yamAmount: yamAmount,
            usdAmount: usdAmount,
            xpPerYam: xpPerYam,
            yamPerUsd: yamPerUsd
        };
        
        console.log("Stored redemption data:", window.currentRedemptionData);
        
        // Update main redemption data
        var popupXpAmount = document.getElementById("popup-xp-amount");
        var popupYamAmount = document.getElementById("popup-yam-amount");
        var popupUsdAmount = document.getElementById("popup-usd-amount");
        var popupXpYamRate = document.getElementById("popup-xp-yam-rate");
        var popupYamUsdRate = document.getElementById("popup-yam-usd-rate");
        
        // Use formatted XP string from PHP if provided (matches the display format exactly)
        if (popupXpAmount) {
            if (formattedXp && formattedXp !== '') {
                popupXpAmount.innerHTML = formattedXp;
            } else {
                popupXpAmount.innerHTML = formatScientificNotation(xpAmount, 30);
            }
        }
        if (popupYamAmount) popupYamAmount.textContent = yamAmount.toLocaleString();
        if (popupUsdAmount) popupUsdAmount.textContent = "$" + usdAmount.toLocaleString();
        // Use formatted XP per YAM from PHP if provided
        if (popupXpYamRate) {
            if (formattedXpPerYam && formattedXpPerYam !== '') {
                popupXpYamRate.innerHTML = formattedXpPerYam;
            } else {
                popupXpYamRate.innerHTML = formatScientificNotation(xpPerYam, 30);
            }
        }
        if (popupYamUsdRate) popupYamUsdRate.textContent = yamPerUsd.toLocaleString();
        
        // Get umeta_id values via AJAX
        console.log("Making AJAX call to get umeta_ids");
        
        var ajaxData = {
            action: "get_xp_umeta_ids",
            user_id: ajaxUserId,
            nonce: ajaxNonce
        };
        
        jQuery.ajax({
            url: ajaxUrl,
            type: "POST",
            data: ajaxData,
            success: function(response) {
                var metaIdsDisplay = document.getElementById("popup-meta-ids");
                if (!metaIdsDisplay) return;
                
                if (response && response.success === true && response.data) {
                    var umetaIds = response.data.umeta_ids || [];
                    // These are the 'id' fields (unique random codes), NOT 'transaction_id'
                    var entryIds = response.data.transaction_ids || [];
                    var entryIdsMap = response.data.transaction_ids_map || {};
                    var count = response.data.count || 0;
                    
                    // Store meta IDs and entry IDs (the 'id' field) globally for form submission
                    window.currentRedemptionData.metaIds = umetaIds;
                    window.currentRedemptionData.entryIds = entryIds; // The 'id' field from each transaction
                    window.currentRedemptionData.entryIdsMap = entryIdsMap; // Map of umeta_id => array of 'id' values
                    
                    console.log('Meta IDs retrieved:', umetaIds);
                    console.log('Entry IDs (id field) retrieved:', entryIds);
                    console.log('Entry IDs Map:', entryIdsMap);
                    
                    if (count > 0) {
                        var displayText = "Found " + count + " eligible entries\n";
                        displayText += "Meta IDs: " + JSON.stringify(umetaIds) + "\n";
                        displayText += "Entry IDs (id field): " + JSON.stringify(entryIds);
                        metaIdsDisplay.textContent = displayText;
                        metaIdsDisplay.style.color = "#28a745";
                    } else {
                        metaIdsDisplay.textContent = "No meta IDs found for this user";
                        metaIdsDisplay.style.color = "#ffc107";
                    }
                } else {
                    metaIdsDisplay.textContent = "Error: " + (response.data || "Unknown error");
                    metaIdsDisplay.style.color = "#dc3545";
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", xhr, status, error);
                var metaIdsDisplay = document.getElementById("popup-meta-ids");
                if (metaIdsDisplay) {
                    metaIdsDisplay.textContent = "Error loading meta IDs: " + error;
                    metaIdsDisplay.style.color = "#dc3545";
                }
            }
        });
        
        // Show popup
        var popup = document.getElementById("redemption-popup");
        if (popup) {
            popup.style.display = "flex";
            document.body.style.overflow = "hidden";
        } else {
            console.error("Redemption popup element not found");
        }
        
        // Add event listeners for real-time updates
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (paymentMethodEl) paymentMethodEl.addEventListener("change", window.updatePaymentDisplay);
        if (paymentDetailsEl) paymentDetailsEl.addEventListener("input", window.updatePaymentDisplay);
    };
    
    window.closeRedemptionPopup = function() {
        var popup = document.getElementById("redemption-popup");
        if (popup) {
            popup.style.display = "none";
            document.body.style.overflow = "auto";
        }
        
        // Clear form fields
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (paymentMethodEl) paymentMethodEl.value = "";
        if (paymentDetailsEl) paymentDetailsEl.value = "";
    };
    
    window.updatePaymentDisplay = function() {
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        if (!paymentMethodEl || !paymentDetailsEl) return;
        
        var paymentMethod = paymentMethodEl.value;
        var paymentDetails = paymentDetailsEl.value.trim();
        
        var methodDisplay = document.getElementById("popup-payment-method-display");
        var detailsDisplay = document.getElementById("popup-payment-details-display");
        if (methodDisplay) methodDisplay.textContent = paymentMethod || "Not selected";
        if (detailsDisplay) detailsDisplay.textContent = paymentDetails || "Not provided";
    };
    
    window.submitRedemptionRequest = function() {
        // Get form elements
        var submitBtn = document.getElementById("submit-redemption");
        var paymentMethodEl = document.getElementById("payment-method");
        var paymentDetailsEl = document.getElementById("payment-details");
        
        if (!paymentMethodEl || !paymentDetailsEl || !submitBtn) {
            alert("Form elements not found. Please refresh the page.");
            return;
        }
        
        var paymentMethod = paymentMethodEl.value.trim();
        var paymentDetails = paymentDetailsEl.value.trim();
        
        // Validation 1: Payment method required
        if (!paymentMethod) {
            alert("Please select a payment method");
            paymentMethodEl.focus();
            return false;
        }
        
        // Validation 2: Payment method must be PayPal or Venmo
        if (paymentMethod !== 'PayPal' && paymentMethod !== 'Venmo') {
            alert("Please select a valid payment method (PayPal or Venmo)");
            paymentMethodEl.focus();
            return false;
        }
        
        // Validation 3: Payment details required
        if (!paymentDetails) {
            alert("Please enter payment details");
            paymentDetailsEl.focus();
            return false;
        }
        
        // Validation 4: Check if redemption data exists
        if (!window.currentRedemptionData || !window.currentRedemptionData.xpAmount) {
            alert("Redemption data not found. Please close and reopen the redemption form.");
            return false;
        }
        
        // Validation 5: Minimum USD amount ($1.00)
        var usdAmount = window.currentRedemptionData.usdAmount || 0;
        if (usdAmount < 1.00) {
            alert("Minimum redemption amount is $1.00 USD. Your current amount is $" + usdAmount.toFixed(2));
            return false;
        }
        
        // Validation 6: Check if meta_ids exist
        var metaIds = window.currentRedemptionData.metaIds || [];
        if (!Array.isArray(metaIds) || metaIds.length === 0) {
            alert("No eligible XP entries found for redemption. Please ensure you have mature XP entries.");
            return false;
        }
        
        // Validation 7: Check XP amount is valid
        var xpAmount = window.currentRedemptionData.xpAmount || 0;
        if (xpAmount <= 0) {
            alert("Invalid XP amount. Please try again.");
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";
        
        // Prepare AJAX data with JSON stringified meta_ids
        var ajaxData = {
            action: "submit_redemption_request",
            user_id: ajaxUserId,
            nonce: ajaxNonce,
            xp_amount: xpAmount,
            yam_amount: window.currentRedemptionData.yamAmount || 0,
            usd_amount: usdAmount,
            xp_per_yam: window.currentRedemptionData.xpPerYam || 0,
            yam_per_usd: window.currentRedemptionData.yamPerUsd || 0,
            payment_method: paymentMethod,
            payment_details: paymentDetails,
            meta_ids: JSON.stringify(metaIds) // Ensure meta_ids is JSON string
        };
        
        console.log("Submitting redemption request:", ajaxData);
        
        jQuery.ajax({
            url: ajaxUrl,
            type: "POST",
            data: ajaxData,
            success: function(response) {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = "Submit";
                
                if (response && response.success) {
                    alert("Redemption request submitted successfully!");
                    window.closeRedemptionPopup();
                    // Reload page after short delay to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    // Handle error response
                    var errorMessage = "Error submitting redemption request.";
                    if (response && response.data) {
                        if (typeof response.data === 'string') {
                            errorMessage = response.data;
                        } else if (response.data.message) {
                            errorMessage = response.data.message;
                            if (response.data.days_until_window) {
                                errorMessage += "\n\nNext redemption window in " + response.data.days_until_window + " days.";
                            }
                        } else {
                            errorMessage = JSON.stringify(response.data);
                        }
                    }
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.textContent = "Submit";
                
                console.error("AJAX error:", xhr, status, error);
                
                var errorMessage = "Error submitting redemption request.";
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (xhr.responseText) {
                    try {
                        var errorData = JSON.parse(xhr.responseText);
                        if (errorData.data) {
                            errorMessage = errorData.data;
                        }
                    } catch (e) {
                        errorMessage = "Network error. Please check your connection and try again.";
                    }
                } else {
                    errorMessage = "Network error: " + error;
                }
                
                alert(errorMessage);
            }
        });
        
        return false; // Prevent form submission
    };
    
    </script>
    <?php
}

/**
 * Calculate XP for all users (reusable function for leaderboard)
 * @return array Array of user_id => total_xp
 */
function cpm_calculate_all_users_xp() {
    global $wpdb;
    
    $all_users_xp = array();
    
    // Get all users
    $users = get_users(array('fields' => array('ID')));
    
    foreach ($users as $user) {
        $user_id = $user->ID;
        $total_xp = 0;
        
        // Get seller_scan, buyer_scan, personal_scan
        $seller_scan = get_user_meta($user_id, 'seller_scan', true);
        $buyer_scan = get_user_meta($user_id, 'buyer_scan', true);
        $personal_scan = get_user_meta($user_id, 'personal_scan', true);
        
        // Process each scan type
        foreach (array($seller_scan, $buyer_scan, $personal_scan) as $scan_data) {
            if (!empty($scan_data)) {
                $data = maybe_unserialize($scan_data);
                if (is_array($data)) {
                    foreach ($data as $entry) {
                        if (is_array($entry) && isset($entry['xp_units'])) {
                            $total_xp += floatval($entry['xp_units']);
                        }
                    }
                }
            }
        }
        
        if ($total_xp > 0) {
            $all_users_xp[$user_id] = $total_xp;
        }
    }
    
    return $all_users_xp;
}

/**
 * Get user badges based on rank and achievements
 * @param int $user_id User ID
 * @param int $rank User's current rank
 * @return array Array of badge names
 */
function cpm_get_user_badges($user_id, $rank) {
    $badges = array();
    
    // PBTV Badge (Top 30)
    if ($rank <= 30) {
        $badges[] = 'pbtv';
    }
    
    // Top Seller Badge (check seller transactions)
    $seller_scan = get_user_meta($user_id, 'seller_scan', true);
    $seller_data = maybe_unserialize($seller_scan);
    if (is_array($seller_data) && count($seller_data) > 0) {
        $seller_count = 0;
        foreach ($seller_data as $entry) {
            if (is_array($entry) && isset($entry['scan_status']) && $entry['scan_status'] === 'confirmed') {
                $seller_count++;
            }
        }
        if ($seller_count >= 10) {
            $badges[] = 'top_seller';
        }
    }
    
    // Buyer Champion Badge (check buyer transactions)
    $buyer_scan = get_user_meta($user_id, 'buyer_scan', true);
    $buyer_data = maybe_unserialize($buyer_scan);
    if (is_array($buyer_data) && count($buyer_data) > 0) {
        $buyer_count = 0;
        foreach ($buyer_data as $entry) {
            if (is_array($entry) && isset($entry['scan_status']) && $entry['scan_status'] === 'confirmed') {
                $buyer_count++;
            }
        }
        if ($buyer_count >= 10) {
            $badges[] = 'buyer_champion';
        }
    }
    
    return $badges;
}

/**
 * Get user POC (Proof of Concept / Location)
 * @param int $user_id User ID
 * @return string POC value or empty string
 */
function cpm_get_user_poc($user_id) {
    $poc = get_user_meta($user_id, 'mega-glassfrog', true);
    return $poc ? $poc : '';
}

/**
 * Count confirmed deliveries for a user
 * @param int $user_id User ID
 * @return int Number of confirmed deliveries
 */
function cpm_count_confirmed_deliveries($user_id) {
    $count = 0;
    $meta_keys = array('seller_scan', 'buyer_scan', 'personal_scan');
    
    foreach ($meta_keys as $meta_key) {
        $scan_data = get_user_meta($user_id, $meta_key, true);
        $data = maybe_unserialize($scan_data);
        
        if (is_array($data)) {
            foreach ($data as $entry) {
                if (is_array($entry) && isset($entry['scan_status']) && $entry['scan_status'] === 'confirmed') {
                    $count++;
                }
            }
        }
    }
    
    return $count;
}

/**
 * Format XP value in scientific notation
 * @param float $xp XP value
 * @return string Formatted XP string
 */
function cpm_format_xp_scientific($xp) {
    if ($xp == 0 || $xp === null) {
        return '0';
    }
    // Format in scientific notation (e.g., "1.03 × 10²³")
    $scientific = sprintf('%.2e', $xp);
    $parts = explode('e', $scientific);
    $mantissa = rtrim(rtrim($parts[0], '0'), '.');
    $exponent = isset($parts[1]) ? ltrim($parts[1], '+') : '0';
    
    return $mantissa . ' × 10' . $exponent;
}

/**
 * Filter users by branch
 * @param array $users_xp Array of user_id => total_xp
 * @param string $branch Branch name to filter by
 * @return array Filtered array
 */
function cpm_filter_by_branch($users_xp, $branch) {
    if (empty($branch)) {
        return $users_xp;
    }
    
    $filtered = array();
    foreach ($users_xp as $user_id => $xp) {
        $user_poc = cpm_get_user_poc($user_id);
        if (stripos($user_poc, $branch) !== false) {
            $filtered[$user_id] = $xp;
        }
    }
    
    return $filtered;
}

/**
 * Search users by name or email
 * @param array $users_xp Array of user_id => total_xp
 * @param string $search_term Search term
 * @return array Filtered array
 */
function cpm_search_users($users_xp, $search_term) {
    if (empty($search_term)) {
        return $users_xp;
    }
    
    $filtered = array();
    $search_lower = strtolower($search_term);
    
    foreach ($users_xp as $user_id => $xp) {
        $user = get_userdata($user_id);
        if ($user) {
            $display_name = strtolower($user->display_name);
            $user_email = strtolower($user->user_email);
            
            if (stripos($display_name, $search_lower) !== false || 
                stripos($user_email, $search_lower) !== false) {
                $filtered[$user_id] = $xp;
            }
        }
    }
    
    return $filtered;
}

/**
 * ========================================
 * PUBLIC LEADERBOARD FUNCTIONS
 * ========================================
 */

/**
 * Display public leaderboard
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function dongtrader_display_public_leaderboard($atts = array()) {
    // Parse shortcode attributes
    $atts = shortcode_atts(array(
        'per_page' => 50,
        'show_search' => 'yes',
        'show_filters' => 'yes',
    ), $atts);
    
    $per_page = intval($atts['per_page']);
    if ($per_page < 1 || $per_page > 100) {
        $per_page = 50;
    }
    
    // Get filter parameters
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $branch_filter = isset($_GET['branch']) ? sanitize_text_field($_GET['branch']) : '';
    $badge_filter = isset($_GET['badge']) ? sanitize_text_field($_GET['badge']) : '';
    
    // Calculate all users XP
    $all_users_xp = cpm_calculate_all_users_xp();
    
    // Apply filters
    if (!empty($search_term)) {
        $all_users_xp = cpm_search_users($all_users_xp, $search_term);
    }
    
    if (!empty($branch_filter)) {
        $all_users_xp = cpm_filter_by_branch($all_users_xp, $branch_filter);
    }
    
    // Sort by XP (descending)
    arsort($all_users_xp);
    
    // Apply badge filter after sorting
    if (!empty($badge_filter)) {
        $filtered = array();
        $rank = 1;
        foreach ($all_users_xp as $user_id => $xp) {
            $badges = cpm_get_user_badges($user_id, $rank);
            if (in_array($badge_filter, $badges)) {
                $filtered[$user_id] = $xp;
            }
            $rank++;
        }
        $all_users_xp = $filtered;
    }
    
    // Pagination
    $total_users = count($all_users_xp);
    $total_pages = ceil($total_users / $per_page);
    $offset = ($current_page - 1) * $per_page;
    $page_users = array_slice($all_users_xp, $offset, $per_page, true);
    
    // Get current date for header
    $current_date = date('M j, Y');
    
    // Build output with white background
    $output = '<div class="dongtrader-public-leaderboard" style="background: white; min-height: 100vh; padding: 40px 20px; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;">';
    $output .= '<div style="max-width: 1200px; margin: 0 auto;">';
    
    // Header Section
    $output .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding: 0 10px;">';
    $output .= '<h1 style="color: #1a1a2e; margin: 0; font-size: 32px; font-weight: 700;">XP Leaderboard</h1>';
    $output .= '<div style="color: #6b7280; font-size: 14px; font-weight: 500;">LIVE • ' . esc_html($current_date) . '</div>';
    $output .= '</div>';
    
    // Leaderboard Cards
    $output .= '<div style="display: flex; flex-direction: column; gap: 16px;">';
    
    // Display users
    $rank = $offset + 1;
    foreach ($page_users as $user_id => $total_xp) {
        $user = get_userdata($user_id);
        if (!$user) continue;
        
        $user_name = $user->display_name ? $user->display_name : $user->user_login;
        $user_badges = cpm_get_user_badges($user_id, $rank);
        $user_poc = cpm_get_user_poc($user_id);
        
        // Calculate YAM and USD
        $yam_equivalent = dongtrader_xp_to_yam($total_xp);
        $usd_value = dongtrader_yam_to_usd($yam_equivalent); // 1 USD = 21,000 YAM (new conversion rate)
        
        // Format XP in scientific notation
        $xp_formatted = cpm_format_xp_scientific($total_xp);
        
        // Determine card styling based on rank
        $card_bg = '';
        $rank_color = '';
        $xp_color = '';
        $name_color = '';
        
        if ($rank === 1) {
            // First place - light gold background
            $card_bg = 'background: #fef3c7; border: 2px solid #fbbf24;';
            $rank_color = '#d97706'; // Dark orange
            $xp_color = '#d97706'; // Dark orange
            $name_color = '#1a1a2e'; // Dark text
        } elseif ($rank === 2) {
            // Second place - light gray background
            $card_bg = 'background: #f3f4f6; border: 2px solid #9ca3af;';
            $rank_color = '#374151'; // Dark gray
            $xp_color = '#059669'; // Green
            $name_color = '#1a1a2e'; // Dark text
        } elseif ($rank === 3) {
            // Third place - light gray background
            $card_bg = 'background: #f3f4f6; border: 2px solid #9ca3af;';
            $rank_color = '#374151'; // Dark gray
            $xp_color = '#0891b2'; // Blue
            $name_color = '#1a1a2e'; // Dark text
        } else {
            // Other ranks - white background with border
            $card_bg = 'background: white; border: 1px solid #e5e7eb;';
            $rank_color = '#6b7280'; // Gray
            $xp_color = '#6b7280'; // Gray
            $name_color = '#1a1a2e'; // Dark text
        }
        
        // Badge colors
        $badge_colors = array(
            'pbtv' => '#d97706', // Dark orange/gold for visibility on white background
            'top_seller' => '#059669', // Green
            'buyer_champion' => '#0891b2' // Blue
        );
        
        $output .= '<div style="' . $card_bg . ' border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; transition: transform 0.2s;" onmouseover="this.style.transform=\'scale(1.02)\';" onmouseout="this.style.transform=\'scale(1)\';">';
        
        // Left side - Rank and Name
        $output .= '<div style="flex: 1;">';
        $output .= '<div style="display: flex; align-items: center; gap: 16px;">';
        
        // Rank
        $output .= '<div style="color: ' . $rank_color . '; font-size: 24px; font-weight: 700; min-width: 60px;">#' . $rank . '</div>';
        
        // Name and Info
        $output .= '<div>';
        $output .= '<div style="color: ' . $name_color . '; font-size: 18px; font-weight: 600; margin-bottom: 6px;">' . esc_html($user_name) . '</div>';
        $output .= '<div style="color: #6b7280; font-size: 14px;">';
        $output .= 'POC: ' . ($user_poc ? esc_html($user_poc) : '—');
        
        // Badges
        if (!empty($user_badges)) {
            $badge_labels = array(
                'pbtv' => 'PBTV Candidate',
                'top_seller' => 'Top Seller',
                'buyer_champion' => 'Buyer Champion'
            );
            
            foreach ($user_badges as $badge) {
                $label = isset($badge_labels[$badge]) ? $badge_labels[$badge] : ucfirst($badge);
                $color = isset($badge_colors[$badge]) ? $badge_colors[$badge] : '#9ca3af';
                $output .= ' • <span style="color: ' . $color . '; font-weight: 600;">' . esc_html($label) . '</span>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        // Right side - XP and Trade Value
        $output .= '<div style="text-align: right;">';
        $output .= '<div style="color: ' . $xp_color . '; font-size: 18px; font-weight: 700; margin-bottom: 4px;">' . $xp_formatted . ' XP</div>';
        $output .= '<div style="color: #6b7280; font-size: 14px;">≈ $' . number_format($usd_value, 0) . ' trade</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        $rank++;
    }
    
    // Show message if no users found
    if (empty($page_users)) {
        $output .= '<div style="background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 12px; padding: 40px; text-align: center; color: #6b7280;">';
        $output .= '<p style="font-size: 16px; margin: 0;">No users found matching your criteria.</p>';
        if (!empty($search_term) || !empty($branch_filter) || !empty($badge_filter)) {
            $output .= '<p style="font-size: 14px; margin-top: 10px;"><a href="' . esc_url(remove_query_arg(array('search', 'branch', 'badge', 'page'))) . '" style="color: #6F42C1; text-decoration: none; font-weight: 600;">Clear filters to see all users</a></p>';
        }
        $output .= '</div>';
    }
    
    $output .= '</div>'; // End leaderboard cards
    
    // Announcement Bar
    $output .= '<div style="background: #fef3c7; border: 2px solid #fbbf24; border-radius: 12px; padding: 20px; margin-top: 40px; text-align: center;">';
    $output .= '<p style="color: #d97706; font-size: 14px; margin: 0; font-weight: 500;">August 11, 2026: Top 30 XP earners receive PBTV NFT Minting Authority under Detente 2030.</p>';
    $output .= '</div>';
    
    // Pagination Section
    if ($total_pages > 1) {
        $output .= '<div style="margin-top: 40px; text-align: center;">';
        $output .= '<div style="display: inline-flex; gap: 8px; align-items: center; flex-wrap: wrap; justify-content: center; background: #f3f4f6; border: 1px solid #e5e7eb; padding: 15px; border-radius: 10px;">';
        
        // Build base URL (current page URL without page parameter)
        $base_url = remove_query_arg('page');
        
        // Build query args preserving filters
        $query_args = array();
        if (!empty($search_term)) {
            $query_args['search'] = $search_term;
        }
        if (!empty($branch_filter)) {
            $query_args['branch'] = $branch_filter;
        }
        if (!empty($badge_filter)) {
            $query_args['badge'] = $badge_filter;
        }
        
        // Previous button
        if ($current_page > 1) {
            $query_args['page'] = $current_page - 1;
            $prev_url = add_query_arg($query_args, $base_url);
            $output .= '<a href="' . esc_url($prev_url) . '" style="padding: 12px 18px; background: #6F42C1; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#5a32a3\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.background=\'#6F42C1\'; this.style.transform=\'translateY(0)\';">← Previous</a>';
        }
        
        // Page numbers
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        if ($start_page > 1) {
            $query_args['page'] = 1;
            $first_url = add_query_arg($query_args, $base_url);
            $output .= '<a href="' . esc_url($first_url) . '" style="padding: 12px 18px; background: white; color: #6b7280; text-decoration: none; border-radius: 6px; border: 1px solid #d1d5db; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#f3f4f6\'; this.style.borderColor=\'#6F42C1\';" onmouseout="this.style.background=\'white\'; this.style.borderColor=\'#d1d5db\';">1</a>';
            if ($start_page > 2) {
                $output .= '<span style="padding: 12px 8px; color: #6b7280; font-weight: 600;">...</span>';
            }
        }
        
        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i === $current_page) {
                $output .= '<span style="padding: 12px 18px; background: #6F42C1; color: white; border-radius: 6px; font-weight: 700;">' . $i . '</span>';
            } else {
                $query_args['page'] = $i;
                $page_url = add_query_arg($query_args, $base_url);
                $output .= '<a href="' . esc_url($page_url) . '" style="padding: 12px 18px; background: white; color: #6b7280; text-decoration: none; border-radius: 6px; border: 1px solid #d1d5db; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#f3f4f6\'; this.style.borderColor=\'#6F42C1\';" onmouseout="this.style.background=\'white\'; this.style.borderColor=\'#d1d5db\';">' . $i . '</a>';
            }
        }
        
        if ($end_page < $total_pages) {
            if ($end_page < $total_pages - 1) {
                $output .= '<span style="padding: 12px 8px; color: #6b7280; font-weight: 600;">...</span>';
            }
            $query_args['page'] = $total_pages;
            $last_url = add_query_arg($query_args, $base_url);
            $output .= '<a href="' . esc_url($last_url) . '" style="padding: 12px 18px; background: white; color: #6b7280; text-decoration: none; border-radius: 6px; border: 1px solid #d1d5db; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#f3f4f6\'; this.style.borderColor=\'#6F42C1\';" onmouseout="this.style.background=\'white\'; this.style.borderColor=\'#d1d5db\';">' . $total_pages . '</a>';
        }
        
        // Next button
        if ($current_page < $total_pages) {
            $query_args['page'] = $current_page + 1;
            $next_url = add_query_arg($query_args, $base_url);
            $output .= '<a href="' . esc_url($next_url) . '" style="padding: 12px 18px; background: #6F42C1; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background=\'#5a32a3\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.background=\'#6F42C1\'; this.style.transform=\'translateY(0)\';">Next →</a>';
        }
        
        $output .= '</div>';
        $output .= '<p style="margin-top: 20px; color: #6b7280; font-size: 14px; font-weight: 500;">Showing <strong style="color: #1a1a2e;">' . number_format($offset + 1) . '</strong> - <strong style="color: #1a1a2e;">' . number_format(min($offset + $per_page, $total_users)) . '</strong> of <strong style="color: #1a1a2e;">' . number_format($total_users) . '</strong> users</p>';
        $output .= '</div>';
    }
    
    $output .= '</div>'; // End max-width container
    $output .= '</div>'; // End main container
    
    return $output;
}

/**
 * Shortcode to display public leaderboard
 */
function dongtrader_public_leaderboard_shortcode($atts) {
    return dongtrader_display_public_leaderboard($atts);
}
add_shortcode('dongtrader_leaderboard', 'dongtrader_public_leaderboard_shortcode');

/**
 * Register leaderboard page endpoint
 */
function dongtrader_register_leaderboard_endpoint() {
    add_rewrite_rule('^leaderboard/?$', 'index.php?dongtrader_leaderboard=1', 'top');
    add_rewrite_tag('%dongtrader_leaderboard%', '([^&]+)');
}
add_action('init', 'dongtrader_register_leaderboard_endpoint');

/**
 * Flush rewrite rules when needed (call manually or on plugin activation)
 * Note: This should be called from the main plugin file, not this functions file
 */
function dongtrader_flush_leaderboard_rewrite_rules() {
    dongtrader_register_leaderboard_endpoint();
    flush_rewrite_rules();
}

/**
 * Display leaderboard on custom endpoint
 */
function dongtrader_display_leaderboard_page() {
    if (get_query_var('dongtrader_leaderboard')) {
        echo dongtrader_display_public_leaderboard();
        exit;
    }
}
add_action('template_redirect', 'dongtrader_display_leaderboard_page');

/**
 * AJAX handler to get paginated transaction history
 */
add_action('wp_ajax_dongtrader_get_transaction_page', 'dongtrader_get_transaction_page');
function dongtrader_get_transaction_page() {
    // Security check: Verify user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in']);
        wp_die();
    }
    
    // Verify nonce
    $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'dongtrader_transaction_pagination')) {
        wp_send_json_error(['message' => 'Security check failed']);
        wp_die();
    }
    
    $user_id = get_current_user_id();
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $per_page = 8;
    
    // Get scan data from usermeta tables
    $seller_scan_raw = get_user_meta($user_id, 'seller_scan', true);
    $buyer_scan_raw = get_user_meta($user_id, 'buyer_scan', true);
    $personal_scan_raw = get_user_meta($user_id, 'personal_scan', true);
    
    // Unserialize if needed
    $seller_scan_data = maybe_unserialize($seller_scan_raw);
    $buyer_scan_data = maybe_unserialize($buyer_scan_raw);
    $personal_scan_data = maybe_unserialize($personal_scan_raw);
    
    // Ensure arrays
    if (!is_array($seller_scan_data)) $seller_scan_data = array();
    if (!is_array($buyer_scan_data)) $buyer_scan_data = array();
    if (!is_array($personal_scan_data)) $personal_scan_data = array();
    
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
                $xp_units = isset($discord_entry['xp_units']) ? floatval($discord_entry['xp_units']) : 0;
                if (isset($discord_entry['xp_awarded'])) {
                    $xp_awarded_yam = intval($discord_entry['xp_awarded']);
                    $xp_units = $xp_awarded_yam / 1000000;
                }
                $trade_value_usd = dongtrader_xp_to_usd($xp_units);
                $yam_value = dongtrader_xp_to_yam($xp_units);
                $formatted_entry = array(
                    'source' => 'discord_invite',
                    'role' => 'Discord Verification',
                    'timestamp' => isset($discord_entry['verification_date']) ? $discord_entry['verification_date'] : (isset($discord_entry['joined_at']) ? $discord_entry['joined_at'] : current_time('mysql')),
                    'proof_id' => 'discord_' . (isset($discord_entry['discord_id']) ? $discord_entry['discord_id'] : 'invite'),
                    'xp_units' => $xp_units,
                    'xp_display_value' => $xp_awarded_yam,
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
                $yam_value = $trade_value_usd * 21000;
                $formatted_entry = array(
                    'source' => 'talentshow_entry',
                    'role' => 'Talent Show',
                    'timestamp' => isset($talent_entry['submission_date']) ? $talent_entry['submission_date'] : current_time('mysql'),
                    'proof_id' => 'talentshow_' . (isset($talent_entry['performance_type']) ? sanitize_title($talent_entry['performance_type']) : 'entry'),
                    'xp_units' => $xp_units,
                    'xp_display_value' => $xp_awarded_yam,
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
                    $xp_units = $xp_awarded_yam > 0 ? ($xp_awarded_yam / 1000000) : 0;
                }
                $trade_value_usd = $xp_units > 0 ? dongtrader_xp_to_usd($xp_units) : 0;
                $yam_value = $trade_value_usd * 21000;
                $formatted_entry = array(
                    'source' => 'discord_poll',
                    'role' => 'Discord Poll',
                    'timestamp' => isset($poll_entry['vote_date']) ? $poll_entry['vote_date'] : (isset($poll_entry['submission_date']) ? $poll_entry['submission_date'] : current_time('mysql')),
                    'proof_id' => 'poll_' . (isset($poll_entry['poll_id']) ? $poll_entry['poll_id'] : 'entry'),
                    'xp_units' => $xp_units,
                    'xp_display_value' => $xp_awarded_yam,
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
    
    // Filter entries - only include confirmed for seller_scan, buyer_scan, personal_scan
    $filtered_entries = array();
    foreach ($user_treasury_entries as $entry) {
        if (isset($entry['source']) && $entry['source'] === 'xp_transfer') {
            continue;
        }
        
        $entry_source = isset($entry['source']) ? $entry['source'] : '';
        if (in_array($entry_source, array('seller_scan', 'buyer_scan', 'personal_scan'))) {
            $scan_status = isset($entry['scan_status']) ? $entry['scan_status'] : '';
            if ($scan_status !== 'confirmed') {
                continue;
            }
        }
        
        $filtered_entries[] = $entry;
    }
    
    // Sort by timestamp (newest first)
    usort($filtered_entries, function($a, $b) {
        $time_a = 0;
        $time_b = 0;
        
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
    
    // Pagination
    $total_entries = count($filtered_entries);
    $total_pages = ceil($total_entries / $per_page);
    $offset = ($page - 1) * $per_page;
    $paginated_entries = array_slice($filtered_entries, $offset, $per_page);
    
    // Generate HTML rows
    $html_rows = '';
    foreach ($paginated_entries as $entry) {
        // Get timestamp
        $timestamp = '';
        if (isset($entry['timestamp']) && !empty($entry['timestamp'])) {
            $timestamp = $entry['timestamp'];
        } elseif (isset($entry['date']) && !empty($entry['date'])) {
            $timestamp = $entry['date'];
        }
        
        // Format date
        $date = 'N/A';
        if ($timestamp) {
            $date_obj = strtotime($timestamp);
            if ($date_obj !== false) {
                $date = date('Y-m-d H:i', $date_obj);
            }
        }
        
        // Get transaction_id
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
        
        // Get YAM value
        $yam = isset($entry['yam_value']) ? floatval($entry['yam_value']) : 0;
        
        // Get XP units
        $xp = isset($entry['xp_units']) ? floatval($entry['xp_units']) : 0;
        $xp_value = isset($entry['xp_display_value']) ? floatval($entry['xp_display_value']) : $xp;
        
        // Format XP in scientific notation
        $xp_formatted = '0';
        if ($xp_value > 0) {
            $scientific = sprintf('%.2e', $xp_value);
            $parts = explode('e', $scientific);
            $mantissa = rtrim(rtrim($parts[0], '0'), '.');
            $exponent = isset($parts[1]) ? ltrim($parts[1], '+') : '0';
            $xp_formatted = $mantissa . ' × 10<sup>' . $exponent . '</sup>';
        }
        
        // Format YAM in scientific notation
        $yam_formatted = '0';
        if ($yam > 0) {
            $scientific = sprintf('%.2e', $yam);
            $parts = explode('e', $scientific);
            $mantissa = rtrim(rtrim($parts[0], '0'), '.');
            $exponent = isset($parts[1]) ? ltrim($parts[1], '+') : '0';
            $yam_formatted = $mantissa . ' × 10<sup>' . $exponent . '</sup>';
        }
        
        // Get status
        $status = 'pending';
        if (isset($entry['scan_status']) && !empty($entry['scan_status'])) {
            $status = strtolower($entry['scan_status']);
        } elseif (isset($entry['status']) && !empty($entry['status'])) {
            $status = strtolower($entry['status']);
        }
        
        // Format status display
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
        
        $html_rows .= '<tr>';
        $html_rows .= '<td>' . esc_html($date) . '</td>';
        $html_rows .= '<td style="font-family: monospace; font-size: 11px;">' . $transaction_id . '</td>';
        $html_rows .= '<td>' . esc_html($role_display) . '</td>';
        $html_rows .= '<td>' . $xp_formatted . '</td>';
        $html_rows .= '<td>' . $yam_formatted . '</td>';
        $html_rows .= '<td><span class="status-badge ' . esc_attr($status) . '">' . esc_html($status_display) . '</span></td>';
        $html_rows .= '</tr>';
    }
    
    wp_send_json_success(array(
        'html' => $html_rows,
        'page' => $page,
        'total_pages' => $total_pages,
        'total_entries' => $total_entries,
        'start' => $offset + 1,
        'end' => min($offset + $per_page, $total_entries)
    ));
    
    wp_die();
}


