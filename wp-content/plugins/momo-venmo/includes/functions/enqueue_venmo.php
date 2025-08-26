<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( is_checkout() ) {
    wp_register_style( 'wc_venmo_checkout', WCVENMO_PLUGIN_DIR_URL . 'assets/css/checkout.css' );
    wp_enqueue_style('wc_venmo_checkout');

    $qrcode_styling = 'qr-code-styling.min.js';
    if (! wp_script_is( $qrcode_styling, 'enqueued' )) {
        wp_register_script( $qrcode_styling, WCVENMO_PLUGIN_DIR_URL . 'assets/js/'. $qrcode_styling);
        wp_enqueue_script( $qrcode_styling );
        // wp_enqueue_script( 'wc_venmo_qrcode_styling', WCVENMO_PLUGIN_DIR_URL . 'assets/js/qr-code-styling.min.js' );
    }
    $qrcode_generator = 'qr-code-generator.js';
    if (! wp_script_is( $qrcode_generator, 'enqueued' )) {
        wp_register_script( $qrcode_generator, WCVENMO_PLUGIN_DIR_URL . 'assets/js/'. $qrcode_generator);
        wp_enqueue_script( $qrcode_generator );
        // wp_enqueue_script( 'wc_venmo_qrcode_generator', WCVENMO_PLUGIN_DIR_URL . 'assets/js/qr-code-generator.js' );
    }

    wp_enqueue_script( 'wc_venmo_qrcode', WCVENMO_PLUGIN_DIR_URL . 'assets/js/qrcode.js', array( 'jquery', $qrcode_styling, $qrcode_generator ), null, true );

    $copy_js = 'copy.js';
    if (! wp_script_is( $copy_js, 'enqueued' )) {
        wp_register_script( $copy_js, WCVENMO_PLUGIN_DIR_URL . 'assets/js/'. $copy_js, array('jquery', 'wc_venmo_qrcode'), null, true);
        wp_enqueue_script( $copy_js );
        // wp_enqueue_script( 'wc_venmo_copy', WCVENMO_PLUGIN_DIR_URL . 'assets/js/copy.js' );
    }

    global $woocommerce;
    $amount = $woocommerce->cart->total;
    $domain = !empty(parse_url(get_bloginfo('url'))) ? parse_url(get_bloginfo('url'))['host'] : null;
    $venmo_note = !empty($this->venmo_note) ? esc_html__( $this->venmo_note, WCVENMO_PLUGIN_TEXT_DOMAIN ) : sprintf( esc_html__( 'Order from %s', WCVENMO_PLUGIN_TEXT_DOMAIN ), $domain );

    $wc_venmo_qrcode = array(
        "url" => 'https://venmo.com/' . esc_attr( wp_kses_post( $this->ReceiverVenmo )) . "?txn=pay&amount=" . $amount . "&note=" . urlencode(esc_attr(wp_kses_post( $venmo_note ))),
        "width" => $this->QRwidth ?? 150,
        "height" => $this->QRheight ?? 150,
        "darkcolor" => $this->QRdarkcolor ?? '#000000',
        "lightcolor" => $this->QRlightcolor ?? '#ffffff',
        "logo" => WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo.png',
        "dotsType" => $this->QRdotsType ?? 'dots',
        "cornersSquareType" => $this->QRcornersSquareType ?? 'extra-rounded',
        "cornersDotType" => $this->QRcornersDotType ?? 'square',
        "backgroundcolor" => $this-> QRbackgroundcolor ?? '#ffffff',
    );
    wp_localize_script( 'wc_venmo_qrcode', 'wc_venmo_qrcode', $wc_venmo_qrcode );

    // // jquery-dialog on checkout/thankyou with countdown https://jqueryui.com/demos/dialog/
    // wp_enqueue_script( 'jquery-ui-dialog' );
}

// if ( ! is_cart() || ! is_checkout() || ! isset( $_GET['pay_for_order'] ) ) { }

?>