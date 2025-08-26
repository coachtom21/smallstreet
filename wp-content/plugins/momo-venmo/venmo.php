<?php

/*
Plugin Name: Checkout with Venmo on Woocommerce
Plugin URI: https://theafricanboss.com/venmo
Description: The top finance app in the App Store now on WordPress. Receive Venmo payments on your website with WooCommerce + Venmo
Author: The African Boss
Author URI: https://theafricanboss.com
Version: 4.2
Requires PHP: 5.0
Requires at least: 4.0
Tested up to: 6.3
WC requires at least: 5.0.0
WC tested up to: 8.0.2
Text Domain: momo-venmo
Domain Path: languages
Created: 2021
Copyright 2023 theafricanboss.com All rights reserved
*/
// Reach out to The African Boss for website and mobile app development services at theafricanboss@gmail.com
// or at www.TheAfricanBoss.com or download our app at www.TheAfricanBoss.com/app
// If you are using this version, please send us some feedback
// via email at theafricanboss@gmail.com on your thoughts and what you would like improved
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
include_once ABSPATH . 'wp-admin/includes/plugin.php';
$plugin_data = get_plugin_data( __FILE__ );
define( 'WCVENMO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCVENMO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WCVENMO_PLUGIN_DIR_URL', plugins_url( '/', __FILE__ ) );
define( 'WCVENMO_PLUGIN_SLUG', explode( "/", WCVENMO_PLUGIN_BASENAME )[0] );
define( 'WCVENMO_PLUGIN_VERSION', WCVENMO_PLUGIN_SLUG . '-' . $plugin_data['Version'] );
define( 'WCVENMO_PLUGIN_TEXT_DOMAIN', $plugin_data['TextDomain'] );
define( 'WCVENMO_UPGRADE_URL', 'https://theafricanboss.com/freemius/wc-venmo' );

if ( function_exists( 'venmo_fs' ) ) {
    venmo_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'venmo_fs' ) ) {
        // Create a helper function for easy SDK access.
        function venmo_fs()
        {
            global  $venmo_fs ;
            
            if ( !isset( $venmo_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_9195_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_9195_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $venmo_fs = fs_dynamic_init( array(
                    'id'             => '9195',
                    'slug'           => 'momo-venmo',
                    'premium_slug'   => 'wc-venmo-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_79e927d48d5115660a41449980c4b',
                    'is_premium'     => false,
                    'premium_suffix' => 'PRO',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'trial'          => array(
                    'days'               => 3,
                    'is_require_payment' => true,
                ),
                    'menu'           => array(
                    'slug'           => 'wc-settings',
                    'override_exact' => true,
                    'first-path'     => 'admin.php?page=wc-settings&tab=checkout&section=venmo',
                    'support'        => false,
                    'parent'         => array(
                    'slug' => 'wc-settings',
                ),
                ),
                    'is_live'        => true,
                ) );
            }
            
            return $venmo_fs;
        }
        
        // Init Freemius.
        venmo_fs();
        // Signal that SDK was initiated.
        do_action( 'venmo_fs_loaded' );
        function venmo_fs_settings_url()
        {
            return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=venmo' );
        }
        
        venmo_fs()->add_filter( 'connect_url', 'venmo_fs_settings_url' );
        venmo_fs()->add_filter( 'after_skip_url', 'venmo_fs_settings_url' );
        venmo_fs()->add_filter( 'after_connect_url', 'venmo_fs_settings_url' );
        venmo_fs()->add_filter( 'after_pending_connect_url', 'venmo_fs_settings_url' );
    }
    
    // translations
    add_action( 'plugins_loaded', function () {
        load_plugin_textdomain( WCVENMO_PLUGIN_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    } );
    
    if ( is_admin() ) {
        add_action( 'plugin_action_links_' . WCVENMO_PLUGIN_BASENAME, function ( $links ) {
            $settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=venmo' ) . '">Venmo Link</a> | <a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=venmo-pay' ) . '">Venmo Pay <sup style="color: #39b54a; font-weight: bold;">NEW</sup></a> | <a href="' . admin_url( 'admin.php?page=wc_venmo_compared' ) . '">Compare Both</a>';
            array_unshift( $links, $settings_link );
            global  $venmo_fs ;
            $upgrade_url = venmo_fs()->get_upgrade_url();
            $links['wc_venmo_pro'] = sprintf( '<a href="' . $upgrade_url . '" style="color: #39b54a; font-weight: bold;">' . esc_html__( 'Upgrade', WCVENMO_PLUGIN_TEXT_DOMAIN ) . '</a>' );
            return $links;
        } );
        add_action( 'admin_enqueue_scripts', function () {
            $currentScreen = get_current_screen();
            // var_dump($currentScreen);
            
            if ( strpos( $currentScreen->id, 'momo_venmo' ) !== false || strpos( $currentScreen->id, 'momo-venmo' ) !== false || strpos( $currentScreen->id, 'wc_venmo' ) !== false || strpos( $currentScreen->id, 'wc-venmo' ) !== false ) {
                wp_register_style( 'wcvenmo_bootstrap', WCVENMO_PLUGIN_DIR_URL . 'assets/css/bootstrap.min.css' );
                wp_enqueue_style( 'wcvenmo_bootstrap' );
            } else {
                return;
            }
        
        } );
        require_once WCVENMO_PLUGIN_DIR . 'includes/admin/dashboard.php';
    }
    
    add_filter( 'woocommerce_payment_gateways', 'wcvenmo_add_gateway_class' );
    //This action hook registers our PHP class as a WooCommerce payment gateway
    function wcvenmo_add_gateway_class( $gateways )
    {
        $gateways[] = 'WC_Venmo_Gateway';
        // your class name is here
        $gateways[] .= 'WC_Venmo_Pay_Gateway';
        // your class name is here
        return $gateways;
    }
    
    add_action( 'plugins_loaded', 'wcvenmo_init_gateway_class' );
    //The class itself, please note that it is inside plugins_loaded action hook
    function wcvenmo_init_gateway_class()
    {
        include_once ABSPATH . 'wp-includes/pluggable.php';
        
        if ( class_exists( 'WC_Payment_Gateway' ) ) {
            require_once WCVENMO_PLUGIN_DIR . 'includes/class-wc_venmo_gateway.php';
            require_once WCVENMO_PLUGIN_DIR . 'includes/class-wc_venmo_update_order.php';
            // require_once plugin_dir_path( __FILE__ ) . 'paypal/vendor/autoload.php';
            require_once WCVENMO_PLUGIN_DIR . 'includes/class-wc_venmo_pay_gateway.php';
            // require_once WCVENMO_PLUGIN_DIR . 'includes/class-wc_venmo_paypal.php';
        } else {
            require_once WCVENMO_PLUGIN_DIR . 'includes/notifications/woocommerce.php';
        }
    
    }

}
