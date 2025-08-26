<?php if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( is_checkout() ) {
    $CLIENT_ID = $this->VENMO_Client_Id;
    $CLIENT_SECRET = $this->VENMO_Client_Secret;
    if ($this->paypalDebug == 'yes') {
        $debug = 'true';
    } else {
        $debug = 'false';
    }
    $paypal_web = "https://www.paypal.com/sdk/js?currency=USD&integration-date=2022-04-13&components=buttons,funding-eligibility&vault=false&commit=false&intent=authorize&disable-funding=card,credit,paylater&enable-funding=venmo&debug=$debug&client-id=$CLIENT_ID";
    wp_enqueue_script( 'wc_venmo_pay_paypal_web', $paypal_web, array(), null, false );

    wp_enqueue_script( 'wc_venmo_pay_paypal', WCVENMO_PLUGIN_DIR_URL . 'assets/js/paypal.js', array( 'jquery', 'wc_venmo_pay_paypal_web' ), null, true );

    global $woocommerce;
    $cart = $woocommerce->cart;
    $cart_items = $woocommerce->cart->get_cart();
    $amount = $woocommerce->cart->total;

    // Initialize Venmo, Render the Venmo button into #venmo-button-container
    // https://developer.paypal.com/sdk/js/reference/#buttons
    // create the following array for the items named purchase_units
    // {
    // 	reference_id: "1",
    // 	amount: {
    // 		currency_code: "USD",
    // 		value: "1",
    // 	},
    // 	item: {
    // 		name: "Test Item",
    // 		description: "Test Item Description",
    // 		sku: "Test Item SKU",
    // 		unit_amount: {
    // 			currency_code: "USD",
    // 			value: "1",
    // 		},
    // 		quantity: "1",
    // 	},
    // },
    $purchase_units = array();
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        $purchase_units[] = array(
            'reference_id' => $cart_item['product_id'],
            'amount' => array(
                'currency_code' => get_woocommerce_currency(),
                'value' => $cart_item['line_total'],
            ),
            'item' => array(
                'name' => $cart_item['data']->get_title(),
                'description' => $cart_item['data']->get_description(),
                'sku' => $cart_item['data']->get_sku(),
                'unit_amount' => array(
                    'currency_code' => get_woocommerce_currency(),
                    'value' => $cart_item['data']->get_price(),
                ),
                'quantity' => $cart_item['quantity'],
            ),
        );
    }

    global $wp;
    wp_localize_script( 'wc_venmo_pay_paypal', 'wc_venmo_pay_object',
        array(
            'isPro' => venmo_fs()->is_plan__premium_only('pro') ? true : false,
            'checkout_url' => get_permalink( get_the_ID() ),
            'checkout_url2' => home_url( $wp->request ),
            'amount' => $amount,
            'cart' => $cart,
            'cart_items' => $cart_items,
            'purchase_units' => $purchase_units,
            'brand_name' => get_bloginfo('name'),
            'site_url' => get_bloginfo('url'),
        )
    );
    wp_localize_script( 'wc_venmo_pay_paypal', 'wc_venmo_pay_button_style',
        array(
            'shape' => $this->pp_style_shape,
            'label' => $this->pp_style_label,
        )
    );

    $spinner_css = 'spinner.css';
    if (! wp_script_is( $spinner_css, 'enqueued' )) {
        wp_register_style( $spinner_css, WCVENMO_PLUGIN_DIR_URL . 'assets/css/' . $spinner_css );
        wp_enqueue_style ( $spinner_css );
        // $spinner_ver = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/spinner.css' ));
        // wp_register_style( 'wc_venmo_pay_spinner', WCVENMO_PLUGIN_DIR_URL . 'assets/css/spinner.css' );
        // wp_enqueue_style ( 'wc_venmo_pay_spinner' );
    }
}

// if ( ! is_cart() || ! is_checkout() || ! isset( $_GET['pay_for_order'] ) ) { }

?>