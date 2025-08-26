<?php
/*
Plugin Name: CPM Twilio
Plugin URI: https://codepixelzmedia.com/
Description: Plugin Intented For the Integration of Twilio
Version: 1.0.0
Author: Codepixelzmedia
Author URI: https://codepixelzmedia.com/
Text Domain: cpm-twilio
*/


if (!defined('ABSPATH')) {
	exit;
}

define('CPM_TWILIO_VERSION', '1.0.0');

define('CPM_TWILIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CPM_TWILIO_PLUGIN_URL', plugins_url('', __FILE__));

function cpm_twilio_enqueue_scripts()
{
	wp_enqueue_script('cpm-twilio-script', CPM_TWILIO_PLUGIN_URL . '/assets/js/cpm-twilio-script.js', array('jquery'), time(), true);
	wp_localize_script('cpm-twilio-script', 'ct_ajax', [
		'ajax_url' => admin_url('admin-ajax.php'), 
		'nonce' => wp_create_nonce('ct_nonce')
	]);
	wp_enqueue_style('cpm-twilio-style', CPM_TWILIO_PLUGIN_URL . '/assets/css/cpm-twilio-style.css', array(), time());
	wp_enqueue_style('cpm-twilio-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '5.15.4');
}
add_action('wp_enqueue_scripts', 'cpm_twilio_enqueue_scripts');

require_once CPM_TWILIO_PLUGIN_DIR . 'inc/helper-functions.php';
require_once CPM_TWILIO_PLUGIN_DIR . 'twilio-main.php';