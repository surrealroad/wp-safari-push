<?php
/*
 *	Plugin Name: Safari Push Notifications
 *	Plugin URI:
 *	Description: Allows WordPress to publish updates to a push server for Safari browsers
 *	Version: 1.0
 *	Author: Surreal Road Limited
 *	Author URI: http://www.surrealroad.com
 *	License: MIT
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Enqueue Javascript

function surrealroad_safaripush_enqueuescripts() {

	wp_enqueue_script(
		'safari-push',
		plugins_url( '/js/safari-push.js' , __FILE__ ),
		array( 'jquery' )
	);

	// build settings to use in script http://ottopress.com/2010/passing-parameters-from-php-to-javascripts-in-plugins/
	$params = array(
		'token' => "",
		'id' => "",
		'websitePushID' => "",
		'webServiceURL' => "",
		'userInfo' => ""
	);
	wp_localize_script( 'safari-push', 'SafariPushParams', $params );
}

add_action( 'wp_enqueue_scripts', 'surrealroad_safaripush_enqueuescripts' );


// add [safari-push] shortcode

function surrealroad_safaripush_html() {
   return '';
}
function surrealroad_safaripush_registerShortcodes(){
   add_shortcode('safari-push', 'surrealroad_safaripush_registerShortcodes');
}
add_action( 'init', 'surrealroad_safaripush_registerShortcodes');


// add admin options page

function surrealroad_safaripush_pluginSettings() {
    add_menu_page('1stWD Slider Settings', '1stWD Slider Settings', 'administrator', 'fwds_settings', 'fwds_display_settings');
}
add_action('admin_menu', 'fwds_plugin_settings');