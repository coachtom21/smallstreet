<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// $order = wc_get_order( $order_id );
$amount = $order->get_total();
$currency = $order->get_currency();
// $total = "$amount $currency";
// $total = $order->get_total();
$total = $order->get_formatted_order_total();
$note = sprintf( esc_html__( 'Order %1s checkout at %2s', WCVENMO_PLUGIN_TEXT_DOMAIN ), $order_id, get_site_url() );
$payment_url = $this->wc_venmo_payment_url( $amount, $note );
$qr_code_url = $this->wc_venmo_qrcode_url( $amount, $note );
$qr_code = $this->wc_venmo_qrcode_html( $amount, $note );
echo  '<div id="wc-' . esc_attr( $this->id ) . '-form" data-plugin="' . wp_kses_post( WCVENMO_PLUGIN_VERSION ) . '">' ;
echo  '<h2>' . esc_html__( 'Venmo Notice', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '</h2>' ;
echo  '<p><strong style="font-size:large;">' . sprintf( esc_html__( 'Please use your Order Number: %s as the payment reference', WCVENMO_PLUGIN_TEXT_DOMAIN ), $order_id ) . '.</strong></p>' ;
// $default_qrcode = '<img width="150" height="150" class="logo-qr" alt="Venmo link" src="https://chart.googleapis.com/chart?cht=qr&chld=L|0&chs=150x150&chl=https://venmo.com/'. esc_attr( wp_kses_post( $this->ReceiverVenmo ) ) . "?txn=pay&amount=" . $amount . "&note=" . urlencode(esc_attr(wp_kses_post( $note ))) . '">';
// echo '<p class="momo-venmo">' . esc_html__( 'Click', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ' > ';
// echo '<a class="paym_link" href="' . $payment_url . '" target="_blank"><img width="150" height="150" class="logo-qr" alt="Venmo link" src="' . esc_url( WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo.png' ) . '"></a>';
// echo ' ' . esc_html__('or Scan', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ' > <a class="paym_link" href="' . $payment_url . '" target="_blank">' . $default_qrcode . '</a></p>';
echo  $qr_code ;
echo  '<p><strong>' . esc_html__( 'Disclaimer', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ': </strong>' . esc_html__( 'Your order will not be processed until funds have cleared in our Venmo account', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '.</p>' ;
echo  '</div><br><hr><br>' ;