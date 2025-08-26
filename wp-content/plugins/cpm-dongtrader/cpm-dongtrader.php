<?php
/*
Plugin Name: CPM Dongtrader
Plugin URI: https://codepixelzmedia.com/
Description: Plugin Intented For the Integration of API in Dongtrader
Version: 1.0.0
Author: Codepixelzmedia
Author URI: https://codepixelzmedia.com/
Text Domain: cpm-dongtrader
*/
if (!defined('ABSPATH')) {
	exit;
}
//Plugin Version
define('CPM_DONGTRADER_VERSION', '1.0.0');

define('CPM_DONGTRADER_PLUGIN_DIR', plugin_dir_path( __FILE__ ));


//Loads All the required files
require_once('inc/cpm-dongtrader-loader.php');



/* Enqueuing the scripts and styles for the plugin on admin settings */
function dongtrader_scripts()
{
	/* css for plugin settings */

	if(isset($_GET['page']) && $_GET['page'] == 'dongtrader_api_settings'){
	wp_enqueue_style('dongtrader-styles', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), false, 'all');
	wp_enqueue_style('dongtrader-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', array(), false, 'all');
	wp_enqueue_style('dongtrader-jquery-ui-custom-styles', plugin_dir_url(__FILE__) . 'assets/css/jquery-ui.custom.css', array(), false, 'all');
	wp_enqueue_style('dongtrader-select2.min-styles', plugin_dir_url(__FILE__) . 'assets/css/select2.min.css', array(), false, 'all');
	

	/* js for plugin settings */
	wp_enqueue_script('jquery'); 
	wp_enqueue_script('jquery-ui-accordion');
	wp_enqueue_script('jquery-ui-tabs');
	}
	
	if(is_admin()){
		wp_enqueue_script('dongtrader-scripts', plugin_dir_url(__FILE__) . 'assets/js/plugin-scripts.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('dongtrader-admin-scripts', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', array('jquery'), '1.0.0', true);
		wp_enqueue_script('dongtrader-admin-select-scripts', plugin_dir_url(__FILE__) . 'assets/js/select2.min.js', array('jquery'), '1.0.0', true);
		wp_add_inline_script('dongtrader-admin-scripts', 'const dongScript = ' . json_encode(array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
		)), 'before');
	}
	
	wp_enqueue_style('dongtrader-frontend-styles', plugin_dir_url(__FILE__) . 'assets/css/dongtraders-style.css', array(), false, 'all');
	wp_enqueue_script('dong-custom-order-exporter', plugin_dir_url(__FILE__) . 'assets/js/dong-custom-order-exporter.js', array('jquery'), '', true);
	wp_localize_script('dong-custom-order-exporter', 'exporterajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'dongtrader_scripts');

function dongtraders_enquee_frontend()
{

	wp_enqueue_style('dongtrader-frontend-style', plugin_dir_url(__FILE__) . 'assets/css/dongtraders-frontend.css', array(), false, 'all');

	wp_enqueue_script('dongtrader-public-scripts', plugin_dir_url(__FILE__) . 'assets/js/dongtraders-public.js', array('jquery'), '', true);
	
	$my_account_page_id = get_option('woocommerce_myaccount_page_id');

	if(is_page($my_account_page_id)){

		wp_enqueue_script('dongtrader-countries-scripts', plugin_dir_url(__FILE__) . 'assets/js/dong-get-countries.js', array('jquery'), '', false);

		wp_enqueue_script('dong-custom-order-exporter', plugin_dir_url(__FILE__) . 'assets/js/dong-custom-order-exporter.js', array('jquery'), '', true);
	}
		
	wp_localize_script('dong-custom-order-exporter', 'exporterajax', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'dongtraders_enquee_frontend');


add_action('admin_menu', 'register_dongtraders_setting_menu_page');
/* Adding a menu item to the admin menu. */
function register_dongtraders_setting_menu_page()
{
	add_menu_page(__('Detente Settings', 'dongtraders'), 'Detente Settings', 'manage_options', 'dongtrader_api_settings', 'dongtraders_add_setting_page', 'dashicons-welcome-widgets-menus', 90);
}

/* Adding a menu item to the admin menu. */
function dongtraders_add_setting_page()
{
	require plugin_dir_path(__FILE__) . 'inc/cpm-dongtrader-settings.php';
}


/* register setting to store value to options table fro setting page */
function dongtraders_api_register_settings()
{
	register_setting('dongtraders_api_setting_page', 'dongtraders_api_settings_fields', 'sd_callback_function');
}

add_action('admin_init', 'dongtraders_api_register_settings');

