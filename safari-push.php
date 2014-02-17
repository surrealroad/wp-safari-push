<?php
/*
Plugin Name: Safari Push Notifications
Plugin URI: https://github.com/surrealroad/wp-safari-push
Description: Allows WordPress to publish updates to a push server for Safari browsers
Version: 0.8.2
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
	static $version ='0.8.2';
	static $apiversion = 'v1';

	//Options and defaults
	static $options = array(
		'websitePushID' => "",
		'webServiceURL' => ""
	);

	public function getVersion() {
		return $this::version;
	}

	public function __construct() {
		register_activation_hook(__FILE__,array(__CLASS__, 'install' ));
		register_uninstall_hook(__FILE__,array( __CLASS__, 'uninstall'));
		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_init', array($this,'registerSettings'));
		add_action('admin_menu', array($this,'pluginSettings'));
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

		$plugin = plugin_basename(__FILE__);
		add_filter("plugin_action_links_$plugin", array($this, 'settings_link') );
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
	    add_settings_field('safaripush-notification-title', __( 'Notification Title', 'safari-push' ), array($this, 'notificationTitleInput'), 'safaripush', 'safaripush-notifications');
	    add_settings_field('safaripush-notification-label', __( 'Notification Button Label', 'safari-push' ), array($this, 'notificationLabelInput'), 'safaripush', 'safaripush-notifications');

	    add_settings_section('safaripush-shortcode', __( 'Shortcode Settings', 'safari-push' ), array($this, 'initShortcodeSettings'), 'safaripush');
	    add_settings_field('safaripush-shortcode-default-msg', __( 'Default message', 'safari-push' ), array($this, 'shortcodeDefaultmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-unsupported-msg', __( 'Unsupported system message', 'safari-push' ), array($this, 'shortcodeUnsupportedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-error-msg', __( 'Error message', 'safari-push' ), array($this, 'shortcodeErrormsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-granted-msg', __( 'Permission granted message', 'safari-push' ), array($this, 'shortcodeGrantedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-denied-msg', __( 'Permission denied message', 'safari-push' ), array($this, 'shortcodeDeniedmsgInput'), 'safaripush', 'safaripush-shortcode');

	    add_settings_section('safaripush-behaviour', __( 'Behaviour Settings', 'safari-push' ), array($this, 'initBehaviourSettings'), 'safaripush');
	    add_settings_field('safaripush-behaviour-enabledposttypes', __( 'Enabled post types', 'safari-push' ), array($this, 'behaviourEnabledposttypesInput'), 'safaripush', 'safaripush-behaviour');
	    add_settings_field('safaripush-behaviour-enabledcategories', __( 'Enabled categories', 'safari-push' ), array($this, 'behaviourEnabledcategoriesInput'), 'safaripush', 'safaripush-behaviour');
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
		$settings_link = '<a href="options-general.php?page=onebox">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	// add admin options page

	function pluginSettings() {
	    add_options_page( 'Safari Push Notifications', 'Safari Push', 'manage_options', 'safaripush', array ( $this, 'optionsPage' ));
	}
	function optionsPage() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.', "safari-push" ) );
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
	            	var jqxhr = $.post(url, data, function() {
					  html = '<div class="updated"><p><?php _e('Notification sent', "safari-push"); ?></p></div>';
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
        <script type="text/javascript">
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
						html += ' </p><p><a href="<?php echo add_query_arg( 'show_users', true ); ?>"><?php _e('Show registered users', "safari-push"); ?></a>';
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
        </script>
        <?php } ?>
        <p><?php _e('To display this number somewhere, use the shortcode <code>[safari-push-count]</code>', "safari-push"); ?></p>
        <hr/>
        <p><a href="https://developer.apple.com/notifications/safari-push-notifications/"><?php _e( 'More information on Safari Push Notifications', 'safari-push' ) ?></a></p>
        <p><?php _e( 'Safari Push Notification Plugin for Wordpress by', 'safari-push' ) ?> <a href="http://www.surrealroad.com">Surreal Road</a>. <?php echo self::surrealTagline(); ?>.</p>
        <p><?php _e( 'Plugin version', 'safari-push' ) ?> <?php echo self::$version; ?></p>
    </div>
    <?php
	}

    function initWebServiceSettings() {
    	_e('<p>To allow visitors to subscribe to your site via Safari Push Notifications, you need a correctly-configured push service.<br/><small>For a PHP-based service that\'s compatibile with this plugin, see <a href="https://github.com/surrealroad/Safari-Push-Notifications">our reference service on GitHub</a>.</small></p>', "safari-push");
    }

    function initNotificationSettings() {

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
    	self::checkbox_group(self::valid_post_type_array(), 'safaripush_enabledposttypes', __('Notify subscribers whenever the checked post types are published for the first time', 'safari-push') );
    }
    function behaviourEnabledcategoriesInput(){
    	self::checkbox_group(self::valid_category_array(), 'safaripush_enabledcategories', __('Only notify subscribers for content in the checked categories (posts without categories are always included)', 'safari-push') );
    }
    function behaviourEnqueuefooterInput(){
    	self::checkbox_input('safaripush_enqueuefooter', __('Load Javascript in footer (requires <code>wp_footer()</code> in your theme)', 'safari-push') );
    }

    // send notification

    function newPushNotification($serviceURL, $endpoint, $title, $body, $action, $urlargs, $auth, $titleTag="title", $bodyTag="body", $actionTag="button", $urlargsTag="urlargs", $authTag="auth" ) {
		$params = array($titleTag => $title, $bodyTag => $body, $actionTag => $action, $urlargsTag => $urlargs, $authTag => $auth);
		$query = http_build_query ($params);
		$contextData = array (
        	'method' => 'POST',
        	'header' => "Connection: close\r\n".
        	"Content-Length: ".strlen($query)."\r\n",
        	'content'=> $query );
        $context = stream_context_create (array ( 'http' => $contextData ));
        $result =  file_get_contents (
        	$serviceURL.$endpoint,
			false,
			$context);
    }

    function notifyPost($newStatus, $oldStatus, $post) {
    	// only notify new of allowed post types
    	$post_types = array();
    	foreach(get_option('safaripush_enabledposttypes') as $key=>$value) {
	    	if($value) $post_types[] = $key;
    	}
    	$categories = array();
    	foreach(get_option('safaripush_enabledcategories') as $key=>$value) {
	    	if($value) $categories[] = $key;
    	}
    	if( 'publish' != $newStatus) return;
    	elseif( 'publish' === $oldStatus) return;
    	elseif(!in_array(get_post_type($post), $post_types)) return;
    	elseif(count(wp_get_post_categories($post->ID)) && !in_category($categories, $post)) return;
    	$serviceURL = get_option('safaripush_webserviceurl');
    	$endpoint = get_option('safaripush_pushendpoint');
    	$auth = get_option('safaripush_authcode');
    	$title = get_option('safaripush_pushtitle');
    	$body = $post->post_title;
    	$action = get_option('safaripush_pushlabel');;
    	$url = parse_url( home_url('?p=' . $post->ID ) );
    	$urlargs = ltrim($url["path"],'/');
    	if(isset($url["query"])) $urlargs.="?".$url["query"];
    	$titleTag = get_option('safaripush_titletag');
    	$bodyTag = get_option('safaripush_bodytag');
    	$actionTag = get_option('safaripush_actiontag');
    	$urlargsTag = get_option('safaripush_urlargstag');
    	$authTag = get_option('safaripush_authtag');
	    self::newPushNotification($serviceURL, $endpoint, $title, $body, $action, $urlargs, $auth, $titleTag, $bodyTag, $actionTag, $urlargsTag, $authTag);
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

	private function valid_category_array($defaults = false) {
		$arr = array();
		$categories = get_categories( array('hide_empty' => 0), "objects");
		foreach ($categories as $category) {
			if($defaults) $arr[] = array($category->slug => 1);
			else $arr[] = array("name" => $category->slug, "label" => $category->name);
		}
		return $arr;
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
