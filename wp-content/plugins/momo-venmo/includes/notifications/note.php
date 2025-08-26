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
// $qr_code_url = $this->wc_venmo_qrcode_url($amount, $note);
// $qr_code = $this->wc_venmo_qrcode_html($amount, $note);
$note = ( $this->order_note ? wp_kses_post( str_replace( array(
    '**order_id**',
    '**order_total**',
    '**shop_name**',
    '**shop_email**',
    '**shop_url**'
), array(
    $order_id,
    $total,
    get_bloginfo( "name" ),
    get_bloginfo( "admin_email" ),
    get_site_url()
), $this->order_note ) ) : esc_html__( 'Your order was received!', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '<br><br>' . esc_html__( 'Please send', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ' <a style="color: #3396cd" href="' . $payment_url . '" target="_blank">' . wp_kses_post( $total ) . ' ' . esc_html__( 'to', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ' ' . esc_attr( wp_kses_post( $this->ReceiverVenmo ) ) . '</a> if you haven\'t already.<br><br>' . sprintf( __( 'We are checking our Venmo to confirm that we received the %s you sent so we can start processing your order.', WCVENMO_PLUGIN_TEXT_DOMAIN ), '<strong style="text-transform:uppercase;">' . wp_kses_post( $total ) . '</strong>' ) . '<br><br>' . esc_html__( 'Thank you for doing business with us', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '!<br> ' . esc_html__( 'You will be updated regarding your order details soon', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '<br><br>' . esc_html__( 'Kindest Regards', WCVENMO_PLUGIN_TEXT_DOMAIN ) . ',<br>' . wp_kses_post( get_bloginfo( "name" ) ) . '<br>' . wp_kses_post( get_bloginfo( "admin_email" ) ) . '<br>' . wp_kses_post( get_site_url() ) . '<br>' );
// some notes to customer (replace true with false to make it private)
$order->add_order_note( $note, true );