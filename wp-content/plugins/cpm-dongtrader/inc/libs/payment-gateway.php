<?php


// Make sure WooCommerce is active
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'init_preorder_gateway');

    function init_preorder_gateway() {
        class WC_Preorder_Gateway extends WC_Payment_Gateway {
            public function __construct() {
                $this->id = 'preorder'; // Payment gateway ID
                $this->icon = ''; // URL of the payment gateway's logo
                $this->has_fields = false; // Does the payment gateway have fields on the checkout page?

                // Define your settings here
                $this->init_form_fields();
                $this->init_settings();

                // Define gateway settings
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->enabled = $this->get_option('enabled');

                // Actions
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            }

            public function init_form_fields() {
                // Define your gateway settings fields here
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => 'Enable/Disable',
                        'type' => 'checkbox',
                        'label' => 'Enable Preorder Gateway',
                        'default' => 'no',
                    ),
                    'title' => array(
                        'title' => 'Title',
                        'type' => 'text',
                        'description' => 'This controls the title that the user sees during checkout.',
                        'default' => 'Preorder Payment',
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => 'Description',
                        'type' => 'textarea',
                        'description' => 'This controls the description that the user sees during checkout.',
                        'default' => 'Pay for your preorder.',
                    ),
                );
            }

            public function process_payment($order_id) {
                // You can implement your payment processing logic here
                // Mark the order as "processing" or "completed" and return the result
                $order = wc_get_order($order_id);
                $order->update_status('processing', __('Payment received via Preorder .', 'woocommerce'));

                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            }

            public function receipt_page($order_id) {
                // Output any content you want to display on the receipt page
                echo '<p>Thank you for your preorder. Please proceed to make the payment.</p>';
            }
        }

        // Register the gateway with WooCommerce
        function add_preorder_gateway($methods) {
            $methods[] = 'WC_Preorder_Gateway';
            return $methods;
        }
        add_filter('woocommerce_payment_gateways', 'add_preorder_gateway');
    }
}
