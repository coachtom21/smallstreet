<?php
require 'wp-load.php';
require 'wp-content/plugins/cpm-dongtrader/inc/cpm-dongtrader-functions.php';

$xp_awarded = 5000000;
echo "Input: xp_awarded = " . $xp_awarded . PHP_EOL;

if ($xp_awarded >= 1000000000000000000) {
    echo "Format: New XP (>= 10^18)" . PHP_EOL;
    $xp_units = floatval($xp_awarded);
} elseif ($xp_awarded >= 1000) {
    echo "Format: YAM (1,000 to 10^18)" . PHP_EOL;
    $trade_value_usd = $xp_awarded / 21000;
    echo "YAM to USD: " . $xp_awarded . " / 21,000 = $" . number_format($trade_value_usd, 2) . PHP_EOL;
    $xp_string = dongtrader_usd_to_xp($trade_value_usd);
    $xp_units = floatval($xp_string);
    echo "USD to XP: $" . number_format($trade_value_usd, 2) . " = " . $xp_string . " XP" . PHP_EOL;
} else {
    echo "Format: Old XP (< 1,000)" . PHP_EOL;
    $xp_units = $xp_awarded / 1000000;
}

echo "Result XP: " . number_format($xp_units, 0) . PHP_EOL;
$yam_equiv = ($xp_units / 100000000000000000000000) * 21000;
echo "YAM Equivalent: " . number_format($yam_equiv, 0) . PHP_EOL;

// Test the balance calculation
$total_xp = $xp_units;
$total_xp_sent = 0;
$total_xp_received = 0;
$available_xp = ($total_xp + $total_xp_received) - $total_xp_sent;
echo PHP_EOL . "Balance Calculation:" . PHP_EOL;
echo "total_xp = " . number_format($total_xp, 0) . PHP_EOL;
echo "total_xp_sent = " . $total_xp_sent . PHP_EOL;
echo "total_xp_received = " . $total_xp_received . PHP_EOL;
echo "available_xp = (" . number_format($total_xp, 0) . " + " . $total_xp_received . ") - " . $total_xp_sent . " = " . number_format($available_xp, 0) . PHP_EOL;
