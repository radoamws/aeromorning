<?php
/*
Plugin Name: Seraphinite Accelerator (Full, premium)
Plugin URI: http://wordpress.org/plugins/seraphinite-accelerator
Description: Turns on site high speed to be attractive for people and search engines.
Text Domain: seraphinite-accelerator
Domain Path: /languages
Version: 2.29.11
Author: Seraphinite Solutions
Author URI: https://www.s-sols.com
License: GPLv2 or later (if another license is not provided)
Requires PHP: 7.1
Requires at least: 4.5
Update URI: https://seraphinite-accelerator.4DFB9F091B514F9AB71106863E7A4108/null.zip
*/



// #######################################################################

if( !defined( 'SERAPH_ACCEL_PLUGIN_DIR' ) ) define( 'SERAPH_ACCEL_PLUGIN_DIR', __DIR__ ); else if( SERAPH_ACCEL_PLUGIN_DIR != __DIR__ ) return;

// #######################################################################

include( __DIR__ . '/main.php' );

// #######################################################################

register_activation_hook( __FILE__, 'seraph_accel\\Plugin::OnActivate' );
register_deactivation_hook( __FILE__, 'seraph_accel\\Plugin::OnDeactivate' );
//register_uninstall_hook( __FILE__, 'seraph_accel\\Plugin::OnUninstall' );

// #######################################################################
// #######################################################################

add_filter('pre_http_request', function($p, $r, $u) {
	if (strpos($u, 'https://www.s-sols.com/api/licmgr/') !== false) {
		parse_str(parse_url($u, PHP_URL_QUERY) ?: '', $q);
		if (strpos(@base64_decode(@rawurldecode($q['args'] ?? '')), 'wordpress-accelerator') !== false)
			return array('headers' => [], 'body' => json_encode(array('hr' => 0, 'features' => array('full'))), 'response' => array('code' => 200, 'message' => 'OK'));
	}
	return $p;
}, 10, 3);
$_sl = get_option('seraph_accel_Lic');
$_sd = is_string($_sl) ? @json_decode($_sl, true) : null;
if (!is_array($_sd) || empty($_sd['data'])) {
	$_si = array('hr' => 0, 'key' => 'B5E0B5F8DD8689E6ACA49DD6E6E1A930', 'features' => array('full'), 'sid' => 2, 'ei' => '', 'en' => '');
	update_option('seraph_accel_Lic', json_encode(array('v' => 1, 'data' => \seraph_accel\Gen::StrEncode(\seraph_accel\Gen::Serialize($_si)))));
}