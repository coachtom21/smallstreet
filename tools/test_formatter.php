<?php
// Lightweight test harness for dongtrader_format_decimal_scientific
function dongtrader_format_decimal_scientific($decimal_string, $frac_digits = 18) {
    $s = trim((string)$decimal_string);
    if ($s === '' || $s === '0' || $s === '0.0') return '0';

    $sign = '';
    if ($s[0] === '+' || $s[0] === '-') {
        if ($s[0] === '-') $sign = '-';
        $s = substr($s, 1);
    }

    if (stripos($s, 'e') !== false) {
        $parts = preg_split('/e/i', $s);
        $mant = $parts[0];
        $exp = intval($parts[1]);
        if (strpos($mant, '.') !== false) {
            list($intp, $fracp) = explode('.', $mant, 2);
        } else {
            $intp = $mant;
            $fracp = '';
        }
        $digits = preg_replace('/[^0-9]/', '', $intp . $fracp);
        $digits = ltrim($digits, '0');
        if ($digits === '') return '0';
        $exponent = strlen($intp) - 1 + $exp;
    } else {
        if (strpos($s, '.') !== false) {
            list($intp, $fracp) = explode('.', $s, 2);
        } else {
            $intp = $s;
            $fracp = '';
        }
        $intp = preg_replace('/[^0-9]/', '', $intp);
        $fracp = preg_replace('/[^0-9]/', '', $fracp);

        $intp_nz = ltrim($intp, '0');
        if ($intp_nz !== '') {
            $digits = $intp_nz . $fracp;
            $exponent = strlen($intp_nz) - 1;
        } else {
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

    $digits = preg_replace('/[^0-9]/', '', $digits);
    if ($digits === '') return '0';

    $total_needed = 1 + $frac_digits;
    if (strlen($digits) < $total_needed) {
        $digits = str_pad($digits, $total_needed, '0');
    }

    $first = $digits[0];
    $rest = substr($digits, 1, $frac_digits);

    if ($frac_digits > 0) {
        $mantissa = $first . '.' . $rest;
    } else {
        $mantissa = $first;
    }

    $exp_display = $exponent;
    return $sign . $mantissa . ' × 10<sup>' . $exp_display . '</sup>';
}

$test = '1.030000000000000049999999999998e23';
echo dongtrader_format_decimal_scientific($test, 30) . PHP_EOL;

echo dongtrader_format_decimal_scientific('1030000000000000049999999999998', 30) . PHP_EOL; // interpret as integer

echo dongtrader_format_decimal_scientific('1030000000000000049999999999998.0', 30) . PHP_EOL;
