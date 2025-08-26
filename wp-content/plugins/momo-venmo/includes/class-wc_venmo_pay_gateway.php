<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( 'WC_Payment_Gateway' ) ) {
    class WC_Venmo_Pay_Gateway extends WC_Payment_Gateway
    {
        protected  $VENMO_Client_Id ;
        protected  $VENMO_Client_Username ;
        protected  $VENMO_Client_Secret ;
        protected  $VENMO_Refresh_Token ;
        protected  $VENMO_Access_Token ;
        public function __construct()
        {
            $this->id = 'venmo-pay';
            // payment gateway plugin ID
            $this->icon = WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo_35.png';
            // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true;
            // in case you need a custom form
            $this->method_title = 'Venmo';
            $this->method_description = '<p><a href="https://venmo.com/business/start/" target="_blank">Pay with Venmo</a> is the official integration for PayPal merchants.</p>
			<p>You need to connect an existing PayPal business account or create one to fully integrate this payment method.</p>
			<p>See how the <a href="' . admin_url( 'admin.php?page=wc_venmo_compared' ) . '">Venmo Pay payment method compares to the Venmo Link payment method</a></p>' . '<p><a href="' . admin_url( 'admin.php?page=wc_venmo_compared' ) . '"><img class="shadow" src="' . WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo_pay_checkout.jpg' . '" width="auto" height="200" alt="Venmo Link on the checkout page" /></a></p>';
            // will be displayed on the options page
            global  $venmo_fs ;
            $upgrade_url = venmo_fs()->get_upgrade_url();
            $this->init_settings();
            $this->enabled = $this->get_option( 'enabled' );
            $this->title = ( $this->get_option( 'checkout_title' ) ? $this->get_option( 'checkout_title' ) : $this->method_title );
            $this->description = ( $this->get_option( 'checkout_description' ) ? $this->get_option( 'checkout_description' ) : wp_kses_post( __( 'Click the button below and follow the instructions to pay with Venmo', WCVENMO_PLUGIN_TEXT_DOMAIN ) ) );
            $this->VENMO_Client_Username = $this->get_option( 'VENMO_Client_Username' );
            $this->VENMO_Client_Id = $this->get_option( 'VENMO_Client_Id' );
            $this->VENMO_Client_Secret = $this->get_option( 'VENMO_Client_Secret' );
            // $this->VENMO_Refresh_Token = $this->get_option( 'VENMO_Refresh_Token' );
            // $this->VENMO_Access_Token = $this->get_option( 'VENMO_Access_Token' );
            // pp_style: {
            // 	layout: "vertical", // "horizontal" // https://developer.paypal.com/sdk/js/reference/#link-layout
            // 	color: "blue", // blue or gold // https://developer.paypal.com/sdk/js/reference/#link-color
            // 	shape: "rect", // rect or pill // https://developer.paypal.com/sdk/js/reference/#link-shape
            // 	size: "responsive", // 25-55 // https://developer.paypal.com/sdk/js/reference/#link-size
            // 	label: "pay", // pay, checkout, buynow, installment // https://developer.paypal.com/sdk/js/reference/#link-label
            // 	// tagline: true, // Note: Set the style.layout to horizontal for taglines. // https://developer.paypal.com/sdk/js/reference/#link-tagline
            // },
            // $this->pp_style_layout = $this->get_option( 'pp_style_layout' ) ?? 'vertical';
            // $this->pp_style_color = $this->get_option( 'pp_style_color' ) ?? 'blue';
            $this->pp_style_shape = $this->get_option( 'pp_style_shape' ) ?? 'rect';
            $this->pp_style_label = $this->get_option( 'pp_style_label' ) ?? 'pay';
            // $this->pp_style_tagline = $this->get_option( 'pp_style_tagline' ) ?? 'false';
            $this->paypalDebug = $this->get_option( 'paypalDebug' ) ?? 'no';
            $this->disableMenu = $this->get_option( 'disableMenu' ) ?? 'no';
            $this->processOrder = $this->get_option( 'processOrder' ) ?? 'no';
            $this->toggleTutorial = $this->get_option( 'toggleTutorial' );
            $paypal = ' <a href="https://developer.paypal.com/dashboard/applications/live" target="_blank">Get it here</a>';
            $pro = ' <a style="text-decoration:none" href="' . $upgrade_url . ' target="_blank"><sup style="color:red">PRO</sup></a>';
            $edit_with_pro = ' <a style="text-decoration:none" href="' . $upgrade_url . ' target="_blank">APPLY CHANGES WITH PRO</a>';
            $this->form_fields = array(
                'enabled'               => array(
                'title'   => 'Enable Pay with Venmo',
                'label'   => 'Check to Enable / Uncheck to Disable',
                'type'    => 'checkbox',
                'default' => 'no',
            ),
                'checkout_title'        => array(
                'title'       => 'Checkout Title',
                'type'        => 'text',
                'description' => 'This is the title which the user sees on the checkout page.',
                'default'     => $this->title,
                'placeholder' => $this->title,
            ),
                'VENMO_Client_Username' => array(
                'title'       => 'PayPal REST API Client Username<br>' . $paypal,
                'type'        => 'text',
                'description' => 'This is your PayPal Client Username/Email.' . $paypal,
                'placeholder' => '***@***.***',
            ),
                'VENMO_Client_Id'       => array(
                'title'       => 'PayPal REST API Client ID<br>' . $paypal,
                'type'        => 'text',
                'description' => 'This is your PayPal Client ID.' . $paypal,
                'placeholder' => '**************',
            ),
                'VENMO_Client_Secret'   => array(
                'title'       => 'PayPal REST API Client Secret<br>' . $paypal,
                'type'        => 'password',
                'description' => 'This is your PayPal Client Secret.' . $paypal,
                'placeholder' => '**************',
            ),
                'pp_style_shape'        => array(
                'title'       => 'PayPal Button Shape' . $pro,
                'type'        => 'select',
                'description' => 'This is the shape of the PayPal button' . $edit_with_pro,
                'default'     => 'rect',
                'options'     => array(
                'rect' => 'Rectangle',
                'pill' => 'Pill',
            ),
                'css'         => 'pointer-events: none;',
                'class'       => 'disabled',
            ),
                'pp_style_label'        => array(
                'title'       => 'PayPal Button Label' . $pro,
                'type'        => 'select',
                'description' => 'This is the label of the PayPal button' . $edit_with_pro,
                'default'     => 'paypal',
                'options'     => array(
                'paypal'      => 'PayPal',
                'checkout'    => 'Checkout',
                'buynow'      => 'Buy Now',
                'pay'         => 'Pay',
                'installment' => 'Installment',
            ),
                'css'         => 'pointer-events: none;',
                'class'       => 'disabled',
            ),
                'paypalDebug'           => array(
                'title'       => 'Enable PayPal Console Debugging',
                'label'       => 'Check to Enable / Uncheck to Disable',
                'type'        => 'checkbox',
                'description' => 'When this is turned on, you will see more information in the browser console logs tab. Recommended ONLY when having issues with the PayPal button',
                'default'     => 'no',
            ),
                'checkout_description'  => array(
                'title'       => 'Checkout Notice' . $pro,
                'type'        => 'textarea',
                'description' => 'This is the text a customer sees in the payment gateway box on the checkout page.' . $edit_with_pro,
                'default'     => wp_kses_post( __( 'Click the button below and follow the instructions to pay with Venmo', WCVENMO_PLUGIN_TEXT_DOMAIN ) ),
                'placeholder' => wp_kses_post( __( 'Click the button below and follow the instructions to pay with Venmo', WCVENMO_PLUGIN_TEXT_DOMAIN ) ),
                'css'         => 'width:80%; pointer-events: none;',
                'class'       => 'disabled',
            ),
                'toggleTutorial'        => array(
                'title'       => 'Enable Tutorial on checkout',
                'label'       => 'Check to Enable / Uncheck to Disable',
                'type'        => 'checkbox',
                'description' => 'Help your customers checkout with ease',
                'default'     => 'no',
            ),
            );
            // Gateways can support subscriptions, refunds, saved payment methods
            $this->supports = array( 'products' );
            // This action hook saves the settings
            add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array( $this, 'process_admin_options' ) );
            // We need custom JavaScript to obtain a token
            add_action( 'wp_enqueue_scripts', array( $this, 'wcvp_enqueue_venmo_pay' ) );
            // // Thank you page
            // add_action( "woocommerce_thankyou_{$this->id}", array( $this, 'wc_venmo_pay_thankyou_page' ) );
            add_action(
                'woocommerce_checkout_order_processed',
                array( $this, 'wc_venmo_pay_processed' ),
                10,
                3
            );
        }
        
        // wc_add_notice & log
        // protected function wcvp_woo_notice( $message, $status = 'error', $level = 'info' ) {}
        protected function wcvp_log( $message, $level = 'info' )
        {
            // logs at admin.php?page=wc-status&tab=logs
            
            if ( !empty($message) && $this->enable_debug == 'yes' && venmo_fs()->is_plan__premium_only( 'pro' ) ) {
                $logger = wc_get_logger();
                // $logger->debug( 'Detailed debug information', $context );
                // $logger->info( 'Interesting events', $context );
                // $logger->notice( 'Normal but significant events', $context );
                // $logger->warning( 'Exceptional occurrences that are not errors', $context );
                // $logger->error( 'Runtime errors that do not require immediate', $context );
                // $logger->critical( 'Critical conditions', $context );
                // $logger->alert( 'Action must be taken immediately', $context );
                // $logger->emergency( 'System is unusable', $context );
                // // $context may hold arbitrary data.
                // // If you provide a "source", it will be used to group your logs
                $logger->log( $level, wp_strip_all_tags( wp_kses_post( $message ) ), array(
                    'source' => $this->id,
                ) );
            }
        
        }
        
        // /**
        //  * Check if this gateway is available in the user's country based on currency.
        //  * @return bool
        //  */
        // public function is_valid_for_use() {
        // 	return in_array(
        // 		get_woocommerce_currency(),
        // 		apply_filters(
        // 			"woocommerce_{$this->id}_supported_currencies",
        // 			array( 'USD' )
        // 		),
        // 		true
        // 	);
        // }
        // Payment Custom JS and CSS
        public function wcvp_enqueue_venmo_pay()
        {
            if ( 'no' === $this->enabled || empty($this->VENMO_Client_Id) ) {
                return;
            }
            require_once WCVENMO_PLUGIN_DIR . 'includes/functions/enqueue_venmo_pay.php';
        }
        
        // Checkout page
        public function payment_fields()
        {
            // https://developer.paypal.com/docs/checkout/pay-with-venmo/integrate/
            global  $woocommerce ;
            $total = $woocommerce->cart->get_total();
            // $1.00
            $amount = $woocommerce->cart->total;
            // 1.00
            echo  '<fieldset id="wc-', esc_attr( $this->id ), '-form" data-plugin="' . wp_kses_post( WCVENMO_PLUGIN_VERSION ) . '">' ;
            do_action( 'woocommerce_form_start', $this->id );
            
            if ( empty($this->VENMO_Client_Id) ) {
                echo  '<p>' . wp_kses_post( __( 'Please finish setting up this payment method or contact the admin to do so.', WCVENMO_PLUGIN_TEXT_DOMAIN ) ) . '</p>' ;
                do_action( 'woocommerce_form_end', $this->id );
                echo  '<input name="do_not_checkout" type="hidden" value="true"><div class="clear"></div></fieldset>' ;
                return;
            }
            
            
            if ( !empty($this->checkout_description) ) {
                echo  '<p>' . wp_kses_post( __( $this->checkout_description, WCVENMO_PLUGIN_TEXT_DOMAIN ) ) . '</p>' ;
            } else {
                echo  '<p>' . wp_kses_post( __( 'Click the button below and follow the instructions to pay with Venmo', WCVENMO_PLUGIN_TEXT_DOMAIN ) ) . '</p>' ;
            }
            
            // $debug = '
            // <input type="hidden" id="wc_venmo_orderID" name="wc_venmo_orderID" value="23G89967CW5479212">
            // <input type="hidden" id="wc_venmo_authorizationID" name="wc_venmo_authorizationID" value="88B04476EY242410S">
            // <input type="hidden" id="wc_venmo_paypal_data" name="wc_venmo_paypal_data" value="{&quot;accelerated&quot;:false,&quot;orderID&quot;:&quot;23G89967CW5479212&quot;,&quot;payerID&quot;:&quot;57RTLERG3SN8A&quot;,&quot;paymentID&quot;:null,&quot;billingToken&quot;:null,&quot;facilitatorAccessToken&quot;:&quot;A21AAPSy6iUhwA7xlKa332_P_dr7jMEA5nI9stHqsMkZvpIxxcFrzNTbUzD3cJ58PhY3AxlFeKYvbf7jGvHKJt8-gmP7CBKsQ&quot;,&quot;paymentSource&quot;:&quot;venmo&quot;}">
            // ';
            echo  '<div class="d-flex align-items-center">
					<div id="venmo-pay"></div>
					<div id="venmo-spinner" class="d-flex justify-content-center align-items-center">
					<span class="spinner-grow text-dark" role="status"></span>
					</div>

					<div class="d-flex justify-content-end" id="venmo-button-container"></div>

					<input type="hidden" id="wc_venmo_orderID" name="wc_venmo_orderID"></input>
					<input type="hidden" id="wc_venmo_authorizationID" name="wc_venmo_authorizationID"></input>
					<input type="hidden" id="wc_venmo_paypal_data" name="wc_venmo_paypal_data" value="">

					<div class="d-flex justify-content-center" id="venmo-status-container"></div>
				</div>' ;
            // toggleTutorial
            
            if ( 'yes' === $this->toggleTutorial ) {
                echo  '<p>If confused, watch the video below on how to checkout with Venmo</p>' ;
                echo  '<p><video width="320" height="240" controls><source src="//www.paypalobjects.com/venmo-assets/venmo-pp-sandbox-demo.MP4" type="video/mp4"></video></p>' ;
            }
            
            do_action( 'woocommerce_form_end', $this->id );
            echo  '<div class="clear"></div></fieldset>' ;
        }
        
        // validate payment token
        public function validate_fields()
        {
            $wc_venmo_paypal_data = ( isset( $_POST['wc_venmo_paypal_data'] ) ? sanitize_text_field( trim( $_POST['wc_venmo_paypal_data'] ) ) : null );
            if ( empty($wc_venmo_paypal_data) || strlen( $wc_venmo_paypal_data ) < 5 ) {
                wc_add_notice( esc_html( __( 'Invalid PayPal Data. Please try again with Venmo or pay with debit/credit', WCVENMO_PLUGIN_TEXT_DOMAIN ) ), 'error' );
            }
            $wc_venmo_orderID = ( isset( $_POST['wc_venmo_orderID'] ) ? sanitize_text_field( trim( $_POST['wc_venmo_orderID'] ) ) : null );
            if ( empty($wc_venmo_orderID) || strlen( $wc_venmo_orderID ) < 3 ) {
                wc_add_notice( esc_html( __( 'Invalid PayPal orderID. Please try again with Venmo or pay with debit/credit', WCVENMO_PLUGIN_TEXT_DOMAIN ) ), 'error' );
            }
            $wc_venmo_authorizationID = ( isset( $_POST['wc_venmo_authorizationID'] ) ? sanitize_text_field( trim( $_POST['wc_venmo_authorizationID'] ) ) : null );
            if ( empty($wc_venmo_authorizationID) || strlen( $wc_venmo_authorizationID ) < 3 ) {
                wc_add_notice( esc_html( __( 'Invalid PayPal authorizationID. Please try again with Venmo or pay with debit/credit', WCVENMO_PLUGIN_TEXT_DOMAIN ) ), 'error' );
            }
            if ( isset( $_POST['do_not_checkout'] ) ) {
                wc_add_notice( esc_html( __( 'Please try another payment method', WCVENMO_PLUGIN_TEXT_DOMAIN ) ), 'error' );
            }
        }
        
        // Thank you page
        public function wc_venmo_pay_thankyou_page( $order_id )
        {
            if ( !$order_id ) {
                return;
            }
            $order = wc_get_order( $order_id );
            if ( $order && $this->id === $order->get_payment_method() ) {
                // $pp_receipt = $order->get_meta('pp_receipt');
                // if ($pp_receipt) {
                // 	$note = wp_kses_post( "<p>" . sprintf( __('Here is your <a href="%s" target="blank">PayPal receipt</a>', WCVENMO_PLUGIN_TEXT_DOMAIN), $pp_receipt ) . "</p>" );
                // 	echo $note;
                // }
            }
        }
        
        public function wc_venmo_pay_processed( $order_id, $posted_data, $order )
        {
            if ( !$order_id || !$order ) {
                return;
            }
            if ( $this->id === $order->get_payment_method() ) {
                require_once WCVENMO_PLUGIN_DIR . 'includes/functions/order_processed.php';
            }
        }
        
        // Process Order
        public function process_payment( $order_id )
        {
            try {
                
                if ( !$order_id ) {
                    wc_add_notice( '<p>Something went terribly wrong.</p><p>Order information is missing</p>', 'error' );
                    return;
                }
                
                $order = wc_get_order( $order_id );
                
                if ( !$order instanceof WC_Order ) {
                    wc_add_notice( '<p>Something went terribly wrong.</p><p>Order information is missing</p>', 'error' );
                    $this->wcvp_log( "Checkout: Order information is missing for order id {$order_id}", 'error' );
                    return;
                }
                
                
                if ( !is_wp_error( $order ) && $this->id === $order->get_payment_method() ) {
                    $amount = $order->get_total();
                    $currency = $order->get_currency();
                    try {
                        $wc_venmo_paypal_data = $_POST['wc_venmo_paypal_data'];
                        $wc_venmo_paypal_data = ( $wc_venmo_paypal_data ? json_decode( $wc_venmo_paypal_data, true ) : null );
                        // $paymentid = sanitize_text_field(trim($wc_venmo_paypal_data["paymentID"])) ?? sanitize_text_field(trim($_POST['wc_venmo_orderID']));
                        // $payerid = sanitize_text_field(trim($wc_venmo_paypal_data["payerID"]));
                        $wc_venmo_orderID = ( $wc_venmo_paypal_data ? sanitize_text_field( trim( $wc_venmo_paypal_data["orderID"] ) ) : sanitize_text_field( trim( $_POST['wc_venmo_orderID'] ) ) );
                        $wc_venmo_authorizationID = ( $_POST['wc_venmo_authorizationID'] ? sanitize_text_field( trim( $_POST['wc_venmo_authorizationID'] ) ) : null );
                        
                        if ( !$wc_venmo_authorizationID ) {
                            wc_add_notice( " " . __( 'Invalid Pay with Venmo authorizationID. Please refresh and try again', WCVENMO_PLUGIN_TEXT_DOMAIN ), 'error' );
                            $this->wcvp_log( "Checkout: Invalid authorizationID " . print_r( $_POST ), 'error' );
                            return;
                        }
                        
                        $ppl_response = wp_remote_post( "https://api.paypal.com/v2/payments/authorizations/{$wc_venmo_authorizationID}/capture", array(
                            'headers' => array(
                            'Content-Type'  => 'application/json',
                            'Authorization' => 'Basic ' . base64_encode( $this->VENMO_Client_Id . ':' . $this->VENMO_Client_Secret ),
                        ),
                            'timeout' => '15',
                        ) );
                        
                        if ( !is_wp_error( $ppl_response ) && !empty($ppl_response) ) {
                            // $ppl_response = json_decode( wp_remote_retrieve_body( $ppl_response ), true );
                            $response = wp_remote_retrieve_body( $ppl_response );
                            // wc_add_notice( $response, 'success' );
                            $result = json_decode( $response, true );
                            $payment_status = $result['status'];
                            $payment_details = $result['details'];
                            $payment_links = $result['links'];
                            $payment_errors = $result['errors'];
                            
                            if ( !empty($payment_errors) && is_array( $payment_errors ) ) {
                                // {"errors":{"http_request_failed":["cURL error 28: Operation timed out after 1000 milliseconds with 0 bytes received"]},"error_data":[]}
                                // $errors_result = $payment_errors;
                                // if ( !empty($errors_result) ) {
                                // 	$error_list = "<ul>";
                                // 	foreach ($errors_result as $error) {
                                // 		$error_list .= '<li>' . $error['category'] . ' ' . $error['code'] . ': ' . $error['detail'] . ' - ' . $error['field'] . '</li>';
                                // 		// $error_list .= '<li>' . $error['code'] . ': ' . $error['detail'] . '</li>';
                                // 	}
                                // 	$error_list .= '</ul>';
                                // 	wc_add_notice( " $error_list", 'error' );
                                // 	$this->wccp_log( "Checkout: Square API errors: " . var_dump($errors_result), 'error' );
                                // } else {
                                // 	wc_add_notice( json_encode($result), 'error' );
                                // 	$this->wccp_log( "Checkout error due to " . json_encode($result), 'error' );
                                // }
                                wc_add_notice( " {$result['name']} Error. Please try again. Error Details: " . json_encode( $result ), 'error' );
                                $this->wcvp_log( "Checkout: {$result['name']} Error.\n" . json_encode( $result ), 'error' );
                                return;
                            } else {
                                
                                if ( $payment_status == 'COMPLETED' || $payment_status == 'CAPTURED' ) {
                                    // https://developer.paypal.com/docs/api/payments/v2/#authorizations_capture
                                    // pp_authorization_id
                                    $order->add_meta_data( 'pp_authorization_id', $wc_venmo_authorizationID );
                                    // pp_order_id
                                    $order->add_meta_data( 'pp_order_id', $wc_venmo_orderID );
                                    $pp_id = $result['id'];
                                    $order->add_meta_data( 'pp_id', $pp_id );
                                    $pp_status = $result['status'];
                                    $order->add_meta_data( 'pp_status', $payment_status );
                                    // https://www.paypal.com/activity/payment/***2X533851NC2084014
                                    $pp_receipt = "https://www.paypal.com/activity/payment/{$pp_id}";
                                    $order->add_meta_data( 'pp_receipt', $pp_receipt );
                                    $note = wp_kses_post( "<p>" . sprintf( __( 'Here is the <a href="%s" target="blank">PayPal receipt</a>', WCVENMO_PLUGIN_TEXT_DOMAIN ), $pp_receipt ) . "</p>" );
                                    $order->add_order_note( $note, true );
                                    // GET "https://api-m.paypal.com/v2/payments/captures/{$pp_id}";
                                    // 	$pp_endpoint = $payment_links[0]['href'];
                                    $pp_endpoint = "https://api-m.paypal.com/v2/payments/captures/{$pp_id}";
                                    $order->add_meta_data( 'pp_endpoint', $pp_endpoint );
                                    $ppr_issue_refund = "https://www.paypal.com/activity/actions/refund/edit/{$pp_id}";
                                    $order->add_meta_data( 'ppr_issue_refund', $ppr_issue_refund );
                                    $note = wp_kses_post( "<p>" . sprintf( __( 'To <a href="%s" target="blank">issue a refund, visit here</a>', WCVENMO_PLUGIN_TEXT_DOMAIN ), $ppr_issue_refund ) . "</p>" );
                                    $order->add_order_note( $note, true );
                                    // POST "https://api-m.paypal.com/v2/payments/captures/{$pp_id}/refund";
                                    // 	$ppr_endpoint = $payment_links[1]['href'];
                                    $ppr_endpoint = $pp_endpoint . "/refund";
                                    $order->add_meta_data( 'ppr_endpoint', $ppr_endpoint );
                                    $order->payment_complete();
                                    $order->reduce_order_stock();
                                } else {
                                    
                                    if ( is_array( $payment_details ) && $payment_details[0]['issue'] !== 'AUTHORIZATION_ALREADY_CAPTURED' ) {
                                        // $errors = $result->getErrors();
                                        // wc_add_notice( " " . $errors, 'error' );
                                        wc_add_notice( " {$result['name']} Error. Please try again. Error Details: " . json_encode( $result ), 'error' );
                                        $this->wcvp_log( "Checkout: {$result['name']} Error.\n" . json_encode( $result ), 'error' );
                                        return;
                                    } else {
                                        wc_add_notice( " " . __( 'Pay with Venmo payment failed. Please refresh and try again', WCVENMO_PLUGIN_TEXT_DOMAIN ), 'error' );
                                        $this->wcvp_log( "Checkout: Pay with Venmo payment failed.\n" . json_encode( $result ), 'error' );
                                        return;
                                    }
                                
                                }
                            
                            }
                            
                            global  $woocommerce ;
                            $woocommerce->cart->empty_cart();
                            return array(
                                'result'   => 'success',
                                'redirect' => $this->get_return_url( $order ),
                            );
                        } else {
                            $error_message = $ppl_response->get_error_message();
                            wc_add_notice( " Something went wrong {$error_message}", 'error' );
                            $this->wcvp_log( "Checkout: WP_Error Something went wrong {$error_message}", 'error' );
                            // throw new Exception( $error_message );
                            return;
                        }
                    
                    } catch ( \Throwable $th ) {
                        // print_r($th);
                        wc_add_notice( " " . $th, 'error' );
                        return;
                    }
                } else {
                    wc_add_notice( 'Connection error.', 'error' );
                    return;
                }
            
            } catch ( \Throwable $th ) {
                // print_r($th);
                wc_add_notice( " " . $th, 'error' );
                return;
            }
        }
        
        // https://developer.paypal.com/docs/api/payments/v2/#captures_refund
        // Webhook
        public function webhook()
        {
            return;
            // $order = wc_get_order( $_GET['id'] );
            // $order->payment_complete();
            // update_option('webhook_debug', $_GET);
        }
    
    }
} else {
    require_once WCVENMO_PLUGIN_DIR . 'includes/notifications/woocommerce.php';
}
