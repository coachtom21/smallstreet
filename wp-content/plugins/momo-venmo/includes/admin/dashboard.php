<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Dashboard Menu Button
function wcvenmo_admin_menu()
{
    $capability = 'manage_options';
    $contact_url = "mailto:info@theafricanboss.com?subject=WC%20Venmo%20Plugin%20Support&body=Hello%2C%0D%0A%0D%0A";
    $venmo_receipts = admin_url( 'edit.php?post_type=venmo-receipts' );
    global  $venmo_fs ;
    $account_url = venmo_fs()->get_account_url();
    $venmo_parent_slug = 'wc-settings&tab=checkout&section=venmo';
    $new = ' <sup style="color:#9f9">NEW</sup>';
    $improved = ' <sup style="color: #39b54a; font-weight: bold;">IMPROVED</sup>';
    add_submenu_page(
        'woocommerce',
        'Venmo for Woocommerce',
        'Venmo Link',
        'manage_woocommerce',
        $venmo_parent_slug,
        null
    );
    add_submenu_page(
        'woocommerce',
        'Setup Automated Order Updates',
        'Automated Venmo Order Updates' . $improved,
        $capability,
        'wc_venmo_automated_status',
        'wc_venmo_email_receipts_menu_page',
        null
    );
    $upgrade_url = venmo_fs()->get_upgrade_url();
    add_menu_page(
        null,
        'Venmo Link',
        $capability,
        $venmo_parent_slug,
        'wcvenmo_admin_menu',
        'dashicons-money-alt',
        56
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Setup Automated Order Updates',
        '<span style="color:#aaffaa">Automated Order Updates</span>',
        $capability,
        'wc_venmo_automated_status',
        'wc_venmo_email_receipts_menu_page',
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Venmo Receipts',
        'Receipts',
        $capability,
        $venmo_receipts,
        null,
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Compared',
        'Venmo Link vs Pay with Venmo',
        $capability,
        'wc_venmo_compared',
        'wc_venmo_compared_menu_page',
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Account',
        'Account',
        $capability,
        $account_url,
        null,
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Upgrade VENMO',
        '<span style="color:#99FFAA">Go Pro >> </span>',
        $capability,
        $upgrade_url,
        null,
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Feature my store',
        'Get Featured',
        $capability,
        'https://theafricanboss.com/featured',
        null,
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Review VENMO',
        'Review',
        $capability,
        'https://wordpress.org/support/plugin/momo-venmo/reviews/?filter=5',
        null,
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Recommended',
        'Recommended',
        $capability,
        'wc_venmo_recommended_menu_page',
        'wc_venmo_recommended_menu_page',
        null
    );
    add_submenu_page(
        $venmo_parent_slug,
        'Help',
        'Help',
        $capability,
        'wc_venmo_help_menu_page',
        'wc_venmo_help_menu_page',
        null
    );
    // add_submenu_page( $venmo_parent_slug, $page_title, $menu_title, $capability, $menu_slug, callable $function = '', int $position = null )
}

add_action( 'admin_menu', 'wcvenmo_admin_menu' );
function wc_venmo_pay_admin_menu()
{
    $capability = 'manage_options';
    $contact_url = "mailto:info@theafricanboss.com?subject=WC%20Venmo%20Plugin%20Support&body=Hello%2C%0D%0A%0D%0A";
    $venmo_receipts = admin_url( 'edit.php?post_type=venmo-receipts' );
    global  $venmo_fs ;
    $account_url = venmo_fs()->get_account_url();
    $new = ' <sup style="color:#9f9">NEW</sup>';
    $improved = ' <sup style="color: #39b54a; font-weight: bold;">IMPROVED</sup>';
    $paypal_parent_slug = 'wc-settings&tab=checkout&section=venmo-pay';
    add_submenu_page(
        'woocommerce',
        'Venmo Pay by PayPal',
        'Venmo Pay',
        'manage_woocommerce',
        $paypal_parent_slug,
        null
    );
    add_menu_page(
        null,
        'Venmo Pay',
        $capability,
        $paypal_parent_slug,
        'wc_venmo_pay_admin_menu',
        'dashicons-money-alt',
        56
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Compared',
        'Venmo Link vs Pay with Venmo',
        $capability,
        'wc_venmo_compared',
        'wc_venmo_compared_menu_page',
        null
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Review Venmo Pay',
        'Review',
        $capability,
        'https://wordpress.org/support/plugin/momo-venmo/reviews/?filter=5',
        null,
        null
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Account',
        'Account',
        $capability,
        $account_url,
        null,
        null
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Our Plugins',
        'Free Plugins',
        $capability,
        admin_url( "plugin-install.php?s=theafricanboss&tab=search&type=author" ),
        null,
        null
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Help',
        'Help',
        $capability,
        'wc_venmo_help',
        'wc_venmo_help_menu_page',
        null
    );
    add_submenu_page(
        $paypal_parent_slug,
        'Contact',
        'Support',
        $capability,
        $contact_url,
        null,
        null
    );
}

// VENMO = new Venmo();
// if ( !VENMO->hideMenu)
add_action( 'admin_menu', 'wc_venmo_pay_admin_menu' );
function wc_venmo_email_receipts_menu_page()
{
    require_once WCVENMO_PLUGIN_DIR . 'includes/admin/email-receipts.php';
}

function wc_venmo_recommended_menu_page()
{
    require_once WCVENMO_PLUGIN_DIR . 'includes/admin/recommended.php';
}

function wc_venmo_help_menu_page()
{
    require_once WCVENMO_PLUGIN_DIR . 'includes/admin/help.php';
}

function wc_venmo_compared_menu_page()
{
    require_once WCVENMO_PLUGIN_DIR . 'includes/admin/compared.php';
}
