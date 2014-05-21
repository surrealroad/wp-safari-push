<?php
/*
Plugin Name: Safari Push Notifications
Plugin URI: https://github.com/surrealroad/wp-safari-push
Description: Allows WordPress to publish updates to a push server for Safari browsers
Version: 0.9.1
Author: Surreal Road Limited
Author URI: http://www.surrealroad.com
Text Domain: safari-push
Domain Path: /languages
License: MIT
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

class SafariPush {

	//Version
	static $version ='0.9.1';
	static $apiversion = 'v1';

	//Options and defaults
	static $options = array(
		'websitePushID' => "",
		'webServiceURL' => ""
	);

	public function getVersion() {
		return $this->version;
	}

	public function __construct() {
		register_activation_hook(__FILE__,array(__CLASS__, 'install' ));
		register_uninstall_hook(__FILE__,array( __CLASS__, 'uninstall'));
		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_init', array($this,'registerSettings'));
		add_action('admin_menu', array($this,'pluginSettings'));
		add_action('wp_dashboard_setup', array($this, 'dashboardWidgets'));
	}

	static function install(){
		update_option("safaripush_version",self::$version);
		update_option("safaripush_apiversion",self::$apiversion);
		add_option("safaripush_webserviceurl", "");
		add_option("safaripush_websitepushid", "");
		add_option("safaripush_pushendpoint", "/".self::$apiversion."/push");
		add_option("safaripush_listendpoint", "/".self::$apiversion."/list");
		add_option("safaripush_countendpoint", "/".self::$apiversion."/count");
		add_option("safaripush_authcode", "");
		add_option("safaripush_titletag", "title");
		add_option("safaripush_bodytag", "body");
		add_option("safaripush_actiontag", "button");
		add_option("safaripush_actionurlargstag", "urlargs");
		add_option("safaripush_authtag", "auth");
		add_option("safaripush_pushtitle",  __( 'New post published', 'safari-push' ));
		add_option("safaripush_pushbody", '{post-title}');
		add_option("safaripush_pushlabel",  __( 'View', 'safari-push' ));
		add_option("safaripush_defaultmsg", '<div class="alert alert-info"><p>' . __( 'To enable push notifications for this site, click "Allow" when Safari asks you.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_unsupportedmsg", '<div class="alert alert-warning"><p>' . __( 'To enable or modify push notifications for this site, use Safari 7.0 or newer.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_errormsg", '<div class="alert alert-danger"><p>' . __( 'Something went wrong communicating with the push notification server, please try again later.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_grantedmsg", '<div class="alert alert-success"><p>' . __( 'Push notifications are enabled for this site.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_deniedmsg", '<div class="alert alert-warning"><p>' . __( 'You have opted not to receive push notifications from us. If you changed your mind, open Safari\'s preferences, and change the permission in the notifications tab.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_enabledposttypes", self::valid_post_type_array(true));
		add_option("safaripush_enabledcategories", self::valid_category_array(true));
		add_option("safaripush_enqueuefooter", false);
	}

	static function uninstall(){
		delete_option('safaripush_version');
		delete_option('safaripush_apiversion');
		delete_option('safaripush_webserviceurl');
		delete_option('safaripush_authcode');
		delete_option('safaripush_websitepushid');
		delete_option('safaripush_pushendpoint');
		delete_option('safaripush_listendpoint');
		delete_option('safaripush_countendpoint');
		delete_option('safaripush_titletag');
		delete_option('safaripush_bodytag');
		delete_option('safaripush_actiontag');
		delete_option('safaripush_actionurlargstag');
		delete_option('safaripush_authtag');
		delete_option('safaripush_pushtitle');
		delete_option('safaripush_pushbody');
		delete_option('safaripush_pushlabel');
		delete_option('safaripush_defaultmsg');
		delete_option('safaripush_unsupportedmsg');
		delete_option('safaripush_errormsg');
		delete_option('safaripush_grantedmsg');
		delete_option('safaripush_deniedmsg');
		delete_option('safaripush_enabledposttypes');
		delete_option('safaripush_enabledcategories');
		delete_option('safaripush_enqueuefooter');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain('safari-push', false, basename(dirname(__FILE__)).'/languages');

		add_action('wp_enqueue_scripts', array($this, 'enqueuescripts'));
		add_action('transition_post_status', array($this, 'notifyPost'), 10, 3);
		add_action( 'post_submitbox_misc_actions', array($this, 'post_page_metabox'));
		add_action( 'save_post', array($this, 'meta_box_save' ));

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'settings_link'));
	}

	public function admin_init() {
	    add_settings_section('safaripush-webservice', __( 'Web Service Settings', 'safari-push' ), array($this, 'initWebServiceSettings'), 'safaripush');
	    add_settings_field('safaripush-web-service-url', __( 'Web Service URL', 'safari-push' ), array($this, 'webServiceURLInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-website-push-id', __( 'Website Push ID', 'safari-push' ), array($this, 'websitePushIDInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-push-endpoint', __( 'Web Service Push Endpoint', 'safari-push' ), array($this, 'pushEndpointInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-list-endpoint', __( 'Web Service List Endpoint', 'safari-push' ), array($this, 'listEndpointInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-count-endpoint', __( 'Web Service Count Endpoint', 'safari-push' ), array($this, 'countEndpointInput'), 'safaripush', 'safaripush-webservice');
   	    add_settings_field('safaripush-auth-code', __( 'Web Service Authentication Code', 'safari-push' ), array($this, 'webServiceAuthInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-title-tag', __( 'Web Service Push Title Tag', 'safari-push' ), array($this, 'pushTitleTagInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-body-tag', __( 'Web Service Push Body Tag', 'safari-push' ), array($this, 'pushBodyTagInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-action-tag', __( 'Web Service Push Action Tag', 'safari-push' ), array($this, 'pushActionTagInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-url-args-tag', __( 'Web Service Push URL Arguments Tag', 'safari-push' ), array($this, 'pushURLArgsTagInput'), 'safaripush', 'safaripush-webservice');
	    add_settings_field('safaripush-auth-tag', __( 'Web Service Push Authentication Tag', 'safari-push' ), array($this, 'pushAuthTagInput'), 'safaripush', 'safaripush-webservice');

	    add_settings_section('safaripush-notifications', __( 'Notification Settings', 'safari-push' ), array($this, 'initNotificationSettings'), 'safaripush');
	    add_settings_field('safaripush-notification-title', __( 'Default Notification Title', 'safari-push' ), array($this, 'notificationTitleInput'), 'safaripush', 'safaripush-notifications');
	    add_settings_field('safaripush-notification-body', __( 'Default Notification Body', 'safari-push' ), array($this, 'notificationBodyInput'), 'safaripush', 'safaripush-notifications');
	    add_settings_field('safaripush-notification-label', __( 'Default Notification Button Label', 'safari-push' ), array($this, 'notificationLabelInput'), 'safaripush', 'safaripush-notifications');

	    add_settings_section('safaripush-shortcode', __( 'Shortcode Settings', 'safari-push' ), array($this, 'initShortcodeSettings'), 'safaripush');
	    add_settings_field('safaripush-shortcode-default-msg', __( 'Default message', 'safari-push' ), array($this, 'shortcodeDefaultmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-unsupported-msg', __( 'Unsupported system message', 'safari-push' ), array($this, 'shortcodeUnsupportedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-error-msg', __( 'Error message', 'safari-push' ), array($this, 'shortcodeErrormsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-granted-msg', __( 'Permission granted message', 'safari-push' ), array($this, 'shortcodeGrantedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-denied-msg', __( 'Permission denied message', 'safari-push' ), array($this, 'shortcodeDeniedmsgInput'), 'safaripush', 'safaripush-shortcode');

	    add_settings_section('safaripush-behaviour', __( 'Behaviour Settings', 'safari-push' ), array($this, 'initBehaviourSettings'), 'safaripush');
	    add_settings_field('safaripush-behaviour-enabledposttypes', __( 'Enabled post types', 'safari-push' ), array($this, 'behaviourEnabledposttypesInput'), 'safaripush', 'safaripush-behaviour');
	    // add_settings_field('safaripush-behaviour-enabledcategories', __( 'Enabled categories', 'safari-push' ), array($this, 'behaviourEnabledcategoriesInput'), 'safaripush', 'safaripush-behaviour'); // removed because categories are too liquid
	    add_settings_field('safaripush-behaviour-enqueuefooter', __( 'Load Javascript in footer', 'safari-push' ), array($this, 'behaviourEnqueuefooterInput'), 'safaripush', 'safaripush-behaviour');
    }

    function registerSettings() {
	    register_setting('safaripush', 'safaripush_webserviceurl');
	    register_setting('safaripush', 'safaripush_websitepushid');
	    register_setting('safaripush', 'safaripush_pushendpoint');
	    register_setting('safaripush', 'safaripush_listendpoint');
	    register_setting('safaripush', 'safaripush_countendpoint');
	    register_setting('safaripush', 'safaripush_authcode');
	    register_setting('safaripush', 'safaripush_titletag');
	    register_setting('safaripush', 'safaripush_bodytag');
	    register_setting('safaripush', 'safaripush_actiontag');
	    register_setting('safaripush', 'safaripush_urlargstag');
	    register_setting('safaripush', 'safaripush_authtag');
	    register_setting('safaripush', 'safaripush_pushtitle');
	    register_setting('safaripush', 'safaripush_pushbody');
	    register_setting('safaripush', 'safaripush_pushlabel');
	    register_setting('safaripush', 'safaripush_defaultmsg');
	    register_setting('safaripush', 'safaripush_unsupportedmsg');
	    register_setting('safaripush', 'safaripush_errormsg');
	    register_setting('safaripush', 'safaripush_grantedmsg');
	    register_setting('safaripush', 'safaripush_deniedmsg');
	    register_setting('safaripush', 'safaripush_enabledposttypes');
	    register_setting('safaripush', 'safaripush_enabledcategories');
	    register_setting('safaripush', 'safaripush_enqueuefooter');
    }

	// Enqueue Javascript

	function enqueuescripts() {

		wp_enqueue_script(
			'safaripush',
			plugins_url( '/js/safari-push.min.js' , __FILE__ ),
			array( 'jquery' ),
			get_option('safaripush_version'),
			get_option('safaripush_enqueuefooter')
		);

		// build settings to use in script http://ottopress.com/2010/passing-parameters-from-php-to-javascripts-in-plugins/
		$params = array(
			'token' => "",
			'userID' => get_current_user_id(),
			'webServiceURL' => get_option('safaripush_webserviceurl'),
			'websitePushID' => get_option('safaripush_websitepushid'),
			'countEndpoint' => get_option('safaripush_countendpoint'),
			'userInfo' => "",
			'apiVersion' => get_option('safaripush_apiversion'),
			'defaultMsg' => get_option('safaripush_defaultmsg'),
			'unsupportedMsg' => get_option('safaripush_unsupportedmsg'),
			'errorMsg' => get_option('safaripush_errormsg'),
			'grantedMsg' => get_option('safaripush_grantedmsg'),
			'deniedMsg' => get_option('safaripush_deniedmsg'),
		);
		wp_localize_script( 'safaripush', 'SafariPushParams', $params );
	}


	// add [safari-push] shortcode

	function renderSafariPushShortcode() {
	   return '<div class="safari-push-info"></div>';
	}

	// add [safari-push-count] shortcode

	function renderSafariPushCountShortcode() {
	   return '<span class="safari-push-count">&hellip;</span>';
	}

	// add settings link
	static function settings_link($links) {
		$settings_link = '<a href="options-general.php?page=safaripush">'.__('Settings', "safaripush").'</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	// add admin options page

	function pluginSettings() {
	    add_options_page( __('Safari Push Notifications', "safaripush"), __('Safari Push', "safaripush"), 'manage_options', 'safaripush', array ( $this, 'optionsPage' ));
	}
	function optionsPage() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', "safaripush" ) );
		}
		?>
    <div class="wrap">
    	<?php screen_icon(); ?>
        <h2><?php _e( 'Safari Push Notification Options', 'safari-push' ) ?></h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'safaripush' ); ?>
            <?php do_settings_sections('safaripush'); ?>
            <?php submit_button(); ?>
        </form>
        <h2><?php _e( 'Send a push notification', 'safari-push' ) ?></h2>
        <form id="safaripush-test-form" action="" method="POST" ?>
        <?php _e( 'Use the form below to send a notification', 'safari-push' ) ?>
        <table class="form-table"><tbody>
        <tr valign="top"><th scope="row"><?php _e( 'Notification Title', 'safari-push' ) ?></th>
        <td><input type="text" id="safaripush-test-title" name="<?php echo get_option('safaripush_titletag'); ?>" value="" /></td>
        </tr>
        <tr valign="top"><th scope="row"><?php _e( 'Notification Body', 'safari-push' ); ?></th>
        <td><input type="text" id="safaripush-test-body" name="<?php echo get_option('safaripush_bodytag'); ?>" value="" /></td>
        </tr>
        <tr valign="top"><th scope="row"><?php _e( 'Device token', 'safari-push' ); ?></th>
        <td><input type="text" id="safaripush-test-devicetoken" name="devicetoken" value="" /><br/><?php _e('Specify the device token to send the notification to, or leave blank to notify all devices (defaults to your current device if available).', 'safari-push'); ?></td>
        </tr>
        </tbody></table>
        <input type="hidden" id="safaripush-test-auth" name="<?php echo get_option('safaripush_authtag'); ?>" value="<?php echo get_option('safaripush_authcode'); ?>" />
        <input type="hidden" id="safaripush-test-urlargs" name="<?php echo get_option('safaripush_urlargstag'); ?>" value="" />
        <input type="hidden" id="safaripush-test-action" name="<?php echo get_option('safaripush_actiontag'); ?>" value="View" />
        <?php submit_button("Push", "small"); ?>
        </form>
        <div id="test-result"></div>
        <script type="text/javascript">
        jQuery(document).ready(function($){
        	if(window.safari) {
	        	var pResult = window.safari.pushNotification.permission($("#safaripush_websitepushid").val());
				if(pResult.permission === 'granted') {
					$("#safaripush-test-devicetoken").val(pResult.deviceToken);
				}
			}

			$("#safaripush-test-form").submit(function(event){
	            event.preventDefault();
	            var html ="",
	            	url = $("#safaripush_webserviceurl").val()+$("#safaripush_pushendpoint").val(),
	            	data = $(this).serialize();
	            if($("#safaripush-test-devicetoken").val()) url += "/"+$("#safaripush-test-devicetoken").val();

	            if(
	            	!$("#safaripush_webserviceurl").val()
	            	|| !$("#safaripush_pushendpoint").val()
	            	|| !$("#safaripush_titletag").val()
	            	|| !$("#safaripush_bodytag").val()
	            	|| !$("#safaripush_authtag").val()
	            	|| !$("#safaripush_urlargstag").val()
	            	|| !$("#safaripush_actiontag").val()
	            	|| !$("#safaripush-test-title").val()
	            	|| !$("#safaripush-test-body").val()
	            	|| !$("#safaripush-test-auth").val()
	            	|| !$("#safaripush-test-action").val()
	            ) {
		            html = '<div class="error"><p><?php _e('A required field is not filled in', "safari-push"); ?></p></div>';
		            $("#test-result").html(html);
	            } else {
	            	var jqxhr = $.post(url, data, function(response) {
					  html = '<div class="updated"><p><?php _e('Notification sent', "safari-push"); ?> ('+response+')</p></div>';
					})
					.fail(function() { html = '<div class="error"><p><?php _e('Error communicating with push service', "safari-push"); ?></p></div>'; })
					.always(function() { $("#test-result").html(html); });
	            }
			});
        });
        </script>
        <h2><?php _e( 'Push Subscribers', 'safari-push' ) ?></h2>
        <?php if($_GET['show_users']) { //must use a query var because we are fetching data from another server which could hang the page; for some reason get_query_var won't work ?>
			<table class="widefat">
			<thead>
			<tr>
			<th><?php _e( 'Username', 'safari-push' );?></th>
			<th><?php _e( 'Device Token', 'safari-push' );?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
			<th><?php _e( 'Username', 'safari-push' );?></th>
			<th><?php _e( 'Device Token', 'safari-push' );?></th>
			</tr>
			</tfoot>
			<tbody>
			<?php
			$jsonurl = get_option('safaripush_webserviceurl').get_option('safaripush_listendpoint')."?".get_option('safaripush_authtag')."=".get_option('safaripush_authcode');
			$json = file_get_contents($jsonurl,0,null,null);
			$devices = json_decode($json, true);
			$regCount = 0;

			foreach ( $devices as $device ) {
			    if($device['userid']>0) {
			    	$regCount++;
			    	$userdata = get_userdata($device['userid']);
			    	echo '<tr><td><a href="'.get_edit_user_link($device['userid']).'">'.esc_attr( $userdata->user_nicename ).'</a></td><td>'.$device['token'].'</td></tr>';
			    }
			} ?>
			</tbody>
			</table>
			<p><?php echo __('There are ', "safari-push").(count($devices) - $regCount).__(' additional subscribed devices belonging to unregistered users', "safari-push"); ?></p>
        <?php } else { ?>
        <div id="safari-push-subscribers"><?php _e( 'Retrieving information', 'safari-push' ) ?>&hellip;</div>
        <?php self::printCountJS(); ?>
        <?php } ?>
        <p><?php _e('To display this number somewhere, use the shortcode <code>[safari-push-count]</code>', "safari-push"); ?></p>
        <?php
		$logs = self::getLogs();
		$logcount = count($logs);
		if ($logcount>0) { ?>
        <h2><?php _e( 'Notification Logs', 'safari-push' ) ?></h2>
        <?php

        ?>
        	<table class="widefat">
			<thead>
			<tr>
			<th><?php _e( 'Date', 'safari-push' );?></th>
			<th><?php _e( 'Post', 'safari-push' );?></th>
			<th><?php _e( 'Response', 'safari-push' );?></th>
			</tr>
			</thead>
			<tfoot>
			<tr>
			<th><?php _e( 'Date', 'safari-push' );?></th>
			<th><?php _e( 'Post', 'safari-push' );?></th>
			<th><?php _e( 'Response', 'safari-push' );?></th>
			</tr>
			</tfoot>
			<tbody>
			<?php foreach($logs as $log) {
				echo '<tr><td>'.self::safaripush_display_timestamp($log['time']).'</td><td><a href="'.get_permalink($log['postid']).'">'.get_the_title($log['postid']).'</a></td><td>'.$log['response'].'</td></tr>';
			} ?>
			</tbody>
        	</table>
        	<?php
        	// http://stackoverflow.com/questions/5322266/add-pagination-in-wordpress-admin-in-my-own-customized-plugin
        	$pagenum = isset( $_GET['pagenum'] ) ? absint( $_GET['pagenum'] ) : 1;
        	$limit = 10; // number of rows in page
			$offset = ( $pagenum - 1 ) * $limit;
			$num_of_pages = ceil( $logcount / $limit );
        	$page_links = paginate_links( array(
			    'base' => add_query_arg( 'pagenum', '%#%' ),
			    'format' => '',
			    'prev_text' => __( '&laquo;', 'text-domain' ),
			    'next_text' => __( '&raquo;', 'text-domain' ),
			    'total' => $num_of_pages,
			    'current' => $pagenum
			) );

			if ( $page_links ) {
			    echo '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0">' . $page_links . '</div></div>';
			}
        } ?>
        <hr/>
        <p><a href="https://developer.apple.com/notifications/safari-push-notifications/"><?php _e( 'More information on Safari Push Notifications', 'safari-push' ) ?></a></p>
        <p><?php _e( 'Safari Push Notification Plugin for Wordpress by', 'safari-push' ) ?> <a href="http://www.surrealroad.com">Surreal Road</a>. <?php echo self::surrealTagline(); ?>.</p>
        <p><?php _e( 'Plugin version', 'safari-push' ) ?> <?php echo self::$version; ?></p>
    </div>
    <?php
	}


	function dashboardWidgets() {
		wp_add_dashboard_widget("safaripush_dashboard_widget", __('Safari Push Subscribers', "safaripush"), array($this, "dashboardSubscribers" ));
	}

	function dashboardSubscribers() {
		echo '<div id="safari-push-subscribers">'.__( 'Retrieving information', 'safari-push' ).'&hellip;</div>';
        self::printCountJS();
	}

	// show a metabox on post pages to control sending push notifications
	function post_page_metabox() {
		global $post;
		if (!$this->post_type_is_pushable($post)) return;
		//elseif (!$this->post_category_is_pushable($post)) return; // removed because categories are too liquid
		$disabled = '';
		$willNotifyMsg = __('On', "safaripush");
		$wontNotifyMsg = __('Off', "safaripush");
		$msg = '';
		$editLabel = __( 'Edit', 'safari-push' );
		$complete = false;

		$meta = $this->get_pushdata_for_post($post->ID);
		$title = $meta['title'];
		$body = $meta['body'];
		$action = $meta['action'];
		$time = $meta['time'];
		$response = $meta['response'];
		$submit = $meta['submit'];
		$checked = $meta['submit'];

		if($submit && !$time) {
			$msg = $willNotifyMsg;
		} elseif($submit && $time) {
			$complete = true;
			$msg = __('Sent', "safaripush");
			if($response) $msg.=' ('.$response.')';
			$editLabel = __( 'View', "safaripush");
			$disabled = ' disabled="disabled"';
		} else {
			$msg = $wontNotifyMsg;
		}

		?><div id="safaripush" class="misc-pub-section misc-pub-section-last">
			<?php _e( 'Safari Push Notification:', 'safari-push' ); ?> <span id="safaripush-status"><strong><?php echo $msg; ?></strong></span>
			<a href="#" id="safaripush-form-edit"><?php echo $editLabel; ?></a>
			&nbsp;<a href="<?php echo admin_url('options-general.php?page=safaripush'); ?>" target="_blank"><?php _e( 'Settings', 'safari-push' ); ?></a><br>
			<div id="safaripush-form" class="hide-if-js">
				<ul>
					<?php //check if push notification already sent
					if($complete) {
						echo '<li>'.__('Sent: ', "safaripush").self::safaripush_display_timestamp($time).'</li>';
						echo '<li>'.__('Response: ', "safaripush").$response.'</li>';
					} else { ?>
					<li>
						<label for="safaripush-submit">
							<input type="checkbox" name="safaripush[submit]" id="safaripush-submit" class="safaripush-submit" value="1" <?php
								checked( true, $checked );
								echo $disabled;
								?>/>
							<?php _e('Send notification', "safaripush"); ?>
						</label>
					</li>
					<?php } ?>
				</ul>
				<label for="safaripush-title"><?php _e('Title:', "safaripush"); ?></label>
				<input name="safaripush[title]" id="safaripush-title" type="text" value="<?php echo esc_attr($title); ?>"<?php echo $disabled;?>/>
				<label for="safaripush-body"><?php _e('Body:', "safaripush"); ?></label>
				<textarea name="safaripush[body]" id="safaripush-body"<?php echo $disabled;?>><?php echo esc_attr($body); ?></textarea>
				<label for="safaripush-button"><?php _e('Button label:', "safaripush"); ?></label>
				<input name="safaripush[action]" id="safaripush-action" type="text" value="<?php echo esc_attr($action); ?>"<?php echo $disabled;?>/>
				<a href="#" class="hide-if-no-js" id="safaripush-form-hide"><?php _e('Hide', "safaripush"); ?></a>
			</div>
		</div>
		<script type="text/javascript">
		jQuery(function($) {
			$('#safaripush-form-edit').click( function(e) {
				e.preventDefault();
				$('#safaripush-form').slideDown( 'fast', function() {
					var titleInput = $("#safaripush-title"),
						bodyInput = $("#safaripush-body"),
						postTitle = $("#title").val();
					if(postTitle) {
						titleInput.val(function(i,v){return v.replace("{post-title}", postTitle)});
						bodyInput.val(function(i,v){return v.replace("{post-title}", postTitle)});
					}
				});
				$('#safaripush-status').hide();
				$(this).hide();
			});
			$('#safaripush-form-hide').click( function(e) {
				e.preventDefault();
				var status = '';
				if($("#safaripush-submit").is(':checked')) status = '<?php echo $willNotifyMsg; ?>';
				else if($("#safaripush-submit").is(':not(:checked)')) status = '<?php echo $wontNotifyMsg; ?>';
				var titleInput = $("#safaripush-title"),
					bodyInput = $("#safaripush-body"),
					postTitle = $("#title").val();
				if(postTitle) {
					titleInput.val(function(i,v){return v.replace(postTitle, "{post-title}")});
					bodyInput.val(function(i,v){return v.replace(postTitle, "{post-title}")});
				}
				$('#safaripush-form').slideUp( 'fast' , function() {
				});
				$('#safaripush-status').show();
				if(status) $('#safaripush-status').html( '<strong>' + status + '</strong>' );
				$('#safaripush-form-edit').show();
			});
		});
		</script>
		<style type="text/css">
		#safaripush input[type=text], #safaripush textarea {
			width:100%;
			margin: 4px 0 0;
		}
		#safaripush ul {
			margin: 4px 0 4px 6px;
		}
		#safaripush li {
			margin: 0;
		}
		</style><?php
	}

	function meta_box_save($post_id) {
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return $post_id;

		if ( isset( $_POST['post_type'] ) && ( 'post' == $_POST['post_type'] || 'page' == $_POST['post_type'] ) ) {
			if ( current_user_can( 'edit_post', $post_id ) ) {
				if($_POST['post_title']) {
					$count = 1;
					$_POST['safaripush']['title'] = str_replace($_POST['post_title'], "{post-title}", $_POST['safaripush']['title'],$count);
					$_POST['safaripush']['body'] = str_replace($_POST['post_title'], "{post-title}", $_POST['safaripush']['body'], $count);
				}
				// https://trepmal.com/action_hook/post_submitbox_misc_actions/
				update_post_meta( $post_id, '_safaripush', $_POST['safaripush'], get_post_meta( $post_id, '_safaripush', true ) );
			}
		}
	  	return $post_id;
	}

    function initWebServiceSettings() {
    	_e('<p>To allow visitors to subscribe to your site via Safari Push Notifications, you need a correctly-configured push service.<br/><small>For a PHP-based service that\'s compatibile with this plugin, see <a href="https://github.com/surrealroad/Safari-Push-Notifications">our reference service on GitHub</a>.</small></p>', "safari-push");
    }

    function initNotificationSettings() {
		_e('<p>The following settings can be changed individually per post, but you can set the default values here. Use <code>{post-title}</code> to use the WordPress post title in the title or body fields.</p>', "safari-push");
    }

    function initShortcodeSettings() {
    	_e('<p>To display feedback to visitors about their push notification status, use the shortcode <code>[safari-push]</code> in combination with the templates below, wherever you\'d like the message to appear.</p>', "safari-push");
    }

	function initBehaviourSettings() {

    }

    function webServiceURLInput(){
    	self::text_input('safaripush_webserviceurl', __( 'Base URL to your push web service, e.g. https://mypushservice.com', 'safari-push' ) );
    }
    function websitePushIDInput(){
    	self::text_input('safaripush_websitepushid', __( 'Unique identifier for your Website Push ID, e.g. web.com.mysite', 'safari-push' ) );
    }
    function pushEndpointInput(){
    	self::text_input('safaripush_pushendpoint', __( 'Endpoint for your web service to receive new notifications, e.g. /v1/push', 'safari-push' ) );
    }
    function listEndpointInput(){
    	self::text_input('safaripush_listendpoint', __( 'Endpoint for your web service to list registered devices, e.g. /v1/list', 'safari-push' ) );
    }
    function countEndpointInput(){
    	self::text_input('safaripush_countendpoint', __( 'Endpoint for your web service to count registered devices, e.g. /v1/count', 'safari-push' ) );
    }
    function webServiceAuthInput(){
    	self::text_input('safaripush_authcode', __( 'Authentication code for your web service', 'safari-push' ) );
    }
    function pushTitleTagInput(){
    	self::text_input('safaripush_titletag', __( 'Endpoint tag for push notification title, e.g. title', 'safari-push' ) );
    }
    function pushBodyTagInput(){
    	self::text_input('safaripush_bodytag', __( 'Endpoint tag for push notification body, e.g. body', 'safari-push' ) );
    }
    function pushActionTagInput(){
    	self::text_input('safaripush_actiontag', __( 'Endpoint tag for push notification action button label, e.g. button', 'safari-push' ) );
    }
    function pushURLArgsTagInput(){
    	self::text_input('safaripush_urlargstag', __( 'Endpoint tag for push notification URL arguments, e.g. urlargs', 'safari-push' ) );
    }
    function pushAuthTagInput(){
    	self::text_input('safaripush_authtag', __( 'Endpoint tag for push notification authentication, e.g. auth', 'safari-push' ) );
    }
    function notificationTitleInput(){
    	self::text_input('safaripush_pushtitle', __( 'Title for notifications displayed, e.g. New Post', 'safari-push' ) );
    }
    function notificationBodyInput(){
    	self::text_input('safaripush_pushbody', __( 'Body for notifications displayed, e.g. {post-title}', 'safari-push' ) );
    }
    function notificationLabelInput(){
    	self::text_input('safaripush_pushlabel', __( 'Button label for notifications displayed, e.g. View', 'safari-push' ) );
    }
    function shortcodeDefaultmsgInput(){
    	self::text_area('safaripush_defaultmsg', __( 'Default HTML to display in Shortcode', 'safari-push' ) );
    }
    function shortcodeUnsupportedmsgInput(){
    	self::text_area('safaripush_unsupportedmsg', __( 'HTML to display in Shortcode on unsupported systems', 'safari-push' ) );
    }
    function shortcodeErrormsgInput(){
    	self::text_area('safaripush_errormsg', __( 'HTML to display in Shortcode in case of error', 'safari-push') );
    }
    function shortcodeGrantedmsgInput(){
    	self::text_area('safaripush_grantedmsg', __( 'HTML to display in Shortcode when notifications have been granted', 'safari-push' ) );
    }
    function shortcodeDeniedmsgInput(){
    	self::text_area('safaripush_deniedmsg', __('HTML to display in Shortcode when notifications have been denied', 'safari-push') );
    }
    function behaviourEnabledposttypesInput(){
    	self::checkbox_group(self::valid_post_type_array(), 'safaripush_enabledposttypes', __('Enable push notifications for the checked post types only', 'safari-push') );
    }
    function behaviourEnabledcategoriesInput(){
    	self::checkbox_group(self::valid_category_array(), 'safaripush_enabledcategories', __('Enable push notifications for content in the checked categories only (posts without categories are always enabled)', 'safari-push') );
    }
    function behaviourEnqueuefooterInput(){
    	self::checkbox_input('safaripush_enqueuefooter', __('Load Javascript in footer (requires <code>wp_footer()</code> in your theme)', 'safari-push') );
    }

    // send notification

    function newPushNotification($serviceURL, $endpoint, $title, $body, $action, $urlargs, $auth, $titleTag="title", $bodyTag="body", $actionTag="button", $urlargsTag="urlargs", $authTag="auth", $post_id=0 ) {
		$params = array($titleTag => $title, $bodyTag => $body, $actionTag => $action, $urlargsTag => $urlargs, $authTag => $auth);
		$query = http_build_query ($params);
		$contextData = array (
        	'method' => 'POST',
        	'header' => "Connection: close\r\n".
        	"Content-Length: ".strlen($query)."\r\n",
        	'content'=> $query );
        $context = stream_context_create (array ( 'http' => $contextData ));
        do_action('safaripush_pre_notification');
        $response =  file_get_contents (
        	$serviceURL.$endpoint,
			false,
			$context);
		self::addLog($post_id, $response);
		// bake submitted parameters
    	if($post_id) update_post_meta( $post_id, '_safaripush', array("title" => $title, "body" => $body, "action" => $action, "submit" => 1), get_post_meta( $post_id, '_safaripush', true ) );
		do_action('safaripush_post_notification', $post_id, $response);
    }

    function notifyPost($newStatus, $oldStatus, $post) {
    	// only notify new posts
    	if( 'publish' != $newStatus) return;
    	elseif( 'publish' === $oldStatus) return;
    	// only notify if notification enabled for post
    	$meta = $this->get_pushdata_for_post($post->ID);
    	$submit = $meta['submit'];
    	if(isset($submit) && !$submit) return;
		$title = $meta['title'];
		$body = $meta['body'];
		$action = $meta['action'];
    	$title = str_replace("{post-title}", $post->post_title, $title);
    	$body = str_replace("{post-title}", $post->post_title, $body);
    	$url = parse_url( home_url('?p=' . $post->ID ) );
    	$urlargs = ltrim($url["path"],'/');
    	if(isset($url["query"])) $urlargs.="?".$url["query"];
    	$titleTag = get_option('safaripush_titletag');
    	$bodyTag = get_option('safaripush_bodytag');
    	$actionTag = get_option('safaripush_actiontag');
    	$urlargsTag = get_option('safaripush_urlargstag');
    	$authTag = get_option('safaripush_authtag');
    	$serviceURL = get_option('safaripush_webserviceurl');
    	$endpoint = get_option('safaripush_pushendpoint');
    	$auth = get_option('safaripush_authcode');
    	self::newPushNotification($serviceURL, $endpoint, $title, $body, $action, $urlargs, $auth, $titleTag, $bodyTag, $actionTag, $urlargsTag, $authTag, $post->ID);
    }

    function get_pushdata_for_post($post_id) { // get relevant data for the specified post
	    $meta = get_post_meta( $post_id, "_safaripush", true );
	    if (!$title = $meta['title']) $title = get_option('safaripush_pushtitle');
    	if (!$body = $meta['body']) $body = get_option('safaripush_pushbody');
    	if (!$action = $meta['action']) $action = get_option('safaripush_pushlabel');
    	if (!$submit = $meta['submit']) $submit = 1; // enable push by default
    	$time = get_post_meta( $post_id, 'safaripush_time', true );
    	$response = get_post_meta( $post_id, 'safaripush_response', true );
    	return array("title" => $title, "body" => $body, "action" => $action, "submit" => $submit, "time" => $time, "response" => $response);
    }

    // logging

    public function addLog($post_id, $response) {
	    update_post_meta($post_id, 'safaripush_response', esc_sql($response));
    	update_post_meta($post_id, 'safaripush_time', time());
    }

    public function getLogs() {
		$logs = array();
		$query_args = array(
			'meta_key' => 'safaripush_time',
			'meta_compare' => 'EXISTS',
			'order' => 'DESC',
			'orderby' => 'meta_value_num'
		);
		$query = new WP_Query($query_args);
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();
				$logs[] = array(
					'postid' =>$post_id,
					'time' => get_post_meta( $post_id, 'safaripush_time', true ),
					'response' =>get_post_meta( $post_id, 'safaripush_response', true )
				);
			}
        }
		wp_reset_postdata();
		return $logs;
    }

    function printCountJS() {
	    ?><script type="text/javascript">
        jQuery(document).ready(function($) {
        	$.post("<?php echo get_option('safaripush_webserviceurl').get_option('safaripush_listendpoint'); ?>?<?php echo get_option('safaripush_authtag'); ?>=<?php echo get_option('safaripush_authcode'); ?>", function(data) {
				if(data) {
					var html = '<p><strong>'+data.length+' <?php _e('devices subscribed via push'); ?></strong>',
						registeredUsers = new Array;

					$.each(data, function(i, device) {
						if(device.userid>0) registeredUsers.push(device.userid);
					});

					if(registeredUsers.length>0) {
						html += ' (' + registeredUsers.length + ' <?php _e('registered user(s)', "safari-push"); ?>)' ;
						html += ' </p><p><a href="<?php echo add_query_arg( 'show_users', true, "options-general.php?page=safaripush" ); ?>"><?php _e('Show registered users', "safari-push"); ?></a>';
					}
					html += '</p>';
				} else {
					var html = '<p><?php _e('Error retrieving data from push service', "safari-push"); ?></p>';
				}
				$("#safari-push-subscribers").html(html);
			}, "json").fail(function() {
					var html = '<p><?php _e('Error retrieving data from push service', "safari-push"); ?></p>';
					$("#safari-push-subscribers").html(html);
			});
        });
        </script><?php
    }

    // utility functions

	function checkbox_input($option, $description) {
	    if (get_option($option)) {
	      $value = 'checked="checked"';
	    } else {
	      $value = '';
	    }
	    ?>
	<input id='<?php echo $option?>' name='<?php echo $option?>' type='checkbox' value='1' <?php echo $value?> /> <?php echo $description ?>
	    <?php
	}
	function checkbox_group($checkboxes, $option, $description) {
		foreach($checkboxes as $checkbox) {
			$name = $checkbox['name'];
			$label = $checkbox['label'];
			$checkedOptions = get_option($option);
			if (isset($checkedOptions[$name]) && $checkedOptions[$name]) {
		      $value = 'checked="checked"';
		    } else {
		      $value = '';
		    }
		    ?>
	<input type="checkbox" name="<?php echo $option; ?>[<?php echo $name; ?>]" value="1" <?php echo $value?> /><?php echo $label; ?><br />
		    <?php
		}
		echo '<p>'.$description.'</p>';
	}
	function text_input($option, $description) {
	    if (get_option($option)) {
	      $value = get_option($option);
	    } else {
	      $value = '';
	    }
	    ?>
	<input id='<?php echo $option?>' name='<?php echo $option?>' type='text' value='<?php echo esc_attr( $value ); ?>' />
	<br/><?php echo $description ?>
	    <?php
	}
	function text_area($option, $description) {
	    if (get_option($option)) {
	      $value = get_option($option);
	    } else {
	      $value = '';
	    }
	    ?>
	<textarea cols=100 rows=6 id='<?php echo $option?>' name='<?php echo $option?>'><?php echo esc_textarea( $value ); ?></textarea><br><?php echo $description ?>
	    <?php
	}

	private function valid_post_type_array($defaults = false) {
		$arr = array();
		$post_types = get_post_types( array('public'=> true), "objects");
		foreach ($post_types as $post_type) {
			if ('attachment' != $post_type->name) {
				if($defaults) $arr[] = array($post_type->name => 1);
				else $arr[] = array("name" => $post_type->name, "label" => $post_type->labels->name);
			}
		}
		return $arr;
	}

	function post_type_is_pushable($post) {
		$post_types = array();
    	foreach(get_option('safaripush_enabledposttypes') as $key=>$value) {
	    	if($value) $post_types[] = $key;
    	}
    	return in_array(get_post_type($post), $post_types);
    }

	private function valid_category_array($defaults = false) {
		$arr = array();
		$categories = get_categories( array('hide_empty' => 0), "objects");
		foreach ($categories as $category) {
			if($defaults) $arr[] = array($category->slug => 1);
			else $arr[] = array("name" => $category->slug, "label" => $category->name);
		}
		return $arr;
	}

	function post_category_is_pushable($post) {
		$categories = array();
    	foreach(get_option('safaripush_enabledcategories') as $key=>$value) {
	    	if($value) $categories[] = $key;
    	}
    	return (count(wp_get_post_categories($post->ID))==0 || in_category($categories, $post));
    }

    public function safaripush_display_timestamp($date) {
		if(get_option('date_format')) $d = date(get_option('date_format'), $date);
		else $d = date('Y-m-d', $date);
		if(get_option('time_format')) $t = date(get_option('time_format'), $date);
		else $t = date('H:i', $date);
		return $d." ".$t;
	}

	function surrealTagline() {
		$lines = file(plugins_url( '/surreal.strings' , __FILE__ ), FILE_IGNORE_NEW_LINES);
		return "Push " . $lines[array_rand($lines)];
	}

}

$safaripush = new SafariPush();

// shortcodes (must be declared outside of class)
add_shortcode('safari-push', array('SafariPush', 'renderSafariPushShortcode'));
add_shortcode('safari-push-count', array('SafariPush', 'renderSafariPushCountShortcode'));
