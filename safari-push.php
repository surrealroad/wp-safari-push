<?php
/*
Plugin Name: Safari Push Notifications
Plugin URI: https://github.com/surrealroad/wp-safari-push
Description: Allows WordPress to publish updates to a push server for Safari browsers
Version: 0.7
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
	static $version ='0.7';
	static $apiversion = 'v1';
	static $pushwooshEndpoint = 'https://cp.pushwoosh.com/json/1.3/';

	//Options and defaults
	static $options = array(
		'websitePushID' => "",
		'webServiceURL' => "",
	);

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
		add_option("safaripush_servertype", 0);
		add_option("safaripush_webserviceurl", "");
		add_option("safaripush_websitepushid", "");
		add_option("safaripush_pushendpoint", "/".self::$apiversion."/push");
		add_option("safaripush_authcode", "");
		add_option("safaripush_titletag", "title");
		add_option("safaripush_bodytag", "body");
		add_option("safaripush_actiontag", "button");
		add_option("safaripush_actionurlargstag", "urlargs");
		add_option("safaripush_authtag", "");
		add_option("safaripush_pushwooshapplication", "");
		add_option("safaripush_pushwooshendpoint",self::$pushwooshEndpoint);
		add_option("safaripush_defaultmsg", '<div class="alert alert-info"><p>' . __( 'To enable push notifications for this site, click "Allow" when Safari asks you.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_unsupportedmsg", '<div class="alert alert-warning"><p>' . __( 'To enable or modify push notifications for this site, use Safari 7.0 or newer.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_errormsg", '<div class="alert alert-danger"><p>' . __( 'Something went wrong communicating with the push notification server, please try again later.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_grantedmsg", '<div class="alert alert-success"><p>' . __( 'Push notifications are enabled for this site.', 'safari-push' ) . '</p></div>');
		add_option("safaripush_deniedmsg", '<div class="alert alert-warning"><p>' . __( 'You have opted not to receive push notifications from us.', 'safari-push' ) . '</p><button class="btn btn-default btn-small" onClick="surrealroad_safaripush_requestPermission();">' . __( 'Enable push notifications', 'safari-push' ) . '</button></div>');
	}

	static function uninstall(){
		delete_option('safaripush_version');
		delete_option('safaripush_apiversion');
		delete_option('safaripush_servertype');
		delete_option('safaripush_webserviceurl');
		delete_option('safaripush_authcode');
		delete_option('safaripush_websitepushid');
		delete_option('safaripush_pushendpoint');
		delete_option('safaripush_titletag');
		delete_option('safaripush_bodytag');
		delete_option('safaripush_actiontag');
		delete_option('safaripush_actionurlargstag');
		delete_option('safaripush_pushwooshapplication');
		delete_option('safaripush_pushwooshendpoint');
		delete_option('safaripush_defaultmsg');
		delete_option('safaripush_unsupportedmsg');
		delete_option('safaripush_errormsg');
		delete_option('safaripush_grantedmsg');
		delete_option('safaripush_deniedmsg');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain('safari-push', false, basename(dirname(__FILE__)).'/languages');

		add_action('wp_enqueue_scripts', array($this, 'enqueuescripts'));
		add_action('transition_post_status', array($this, 'notifyPost'), 10, 3);
	}

	public function admin_init() {
	    add_settings_section('safaripush-mode', __( 'Push Server Type', 'safari-push' ), array($this, 'initModeSettings'), 'safaripush');
	    add_settings_field('safaripush-server-type', __( 'Push Server Type', 'safari-push' ), array($this, 'webServerTypeInput'), 'safaripush', 'safaripush-mode');

	    add_settings_section('safaripush-webservice', __( 'Web Service Settings', 'safari-push' ), array($this, 'initWebServiceSettings'), 'safaripush');
	    add_settings_field('safaripush-website-push-id', __( 'Website Push ID', 'safari-push' ), array($this, 'websitePushIDInput'), 'safaripush', 'safaripush-webservice');
   	    add_settings_field('safaripush-auth-code', __( 'Web Service Authentication Code', 'safari-push' ), array($this, 'webServiceAuthInput'), 'safaripush', 'safaripush-webservice');

	    add_settings_section('safaripush-dedicated', __( 'Web Service Settings (Dedicated Server only)', 'safari-push' ), array($this, 'initDedicatedServerSettings'), 'safaripush');
	    add_settings_field('safaripush-web-service-url', __( 'Web Service URL', 'safari-push' ), array($this, 'webServiceURLInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-push-endpoint', __( 'Web Service Push Endpoint', 'safari-push' ), array($this, 'pushEndpointInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-title-tag', __( 'Web Service Push Title Tag', 'safari-push' ), array($this, 'pushTitleTagInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-body-tag', __( 'Web Service Push Body Tag', 'safari-push' ), array($this, 'pushBodyTagInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-action-tag', __( 'Web Service Push Action Tag', 'safari-push' ), array($this, 'pushActionTagInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-url-args-tag', __( 'Web Service Push URL Arguments Tag', 'safari-push' ), array($this, 'pushURLArgsTagInput'), 'safaripush', 'safaripush-dedicated');
	    add_settings_field('safaripush-auth-tag', __( 'Web Service Push Authentication Tag', 'safari-push' ), array($this, 'pushAuthTagInput'), 'safaripush', 'safaripush-dedicated');

	    add_settings_section('safaripush-pushwoosh', __( 'Web Service Settings (Pushwoosh only)', 'safari-push' ), array($this, 'initPushwooshSettings'), 'safaripush');
	    add_settings_field('safaripush-pushwoosh-application', __( 'Application code', 'safari-push' ), array($this, 'pushwooshApplicationInput'), 'safaripush', 'safaripush-pushwoosh');
	    add_settings_field('safaripush-pushwoosh-endpoint', __( 'Endpoint', 'safari-push' ), array($this, 'pushwooshEndpointInput'), 'safaripush', 'safaripush-pushwoosh');

	    add_settings_section('safaripush-shortcode', __( 'Shortcode Settings', 'safari-push' ), array($this, 'initShortcodeSettings'), 'safaripush');
	    add_settings_field('safaripush-shortcode-default-msg', __( 'Default message', 'safari-push' ), array($this, 'shortcodeDefaultmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-unsupported-msg', __( 'Unsupported system message', 'safari-push' ), array($this, 'shortcodeUnsupportedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-error-msg', __( 'Error message', 'safari-push' ), array($this, 'shortcodeErrormsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-granted-msg', __( 'Permission granted message', 'safari-push' ), array($this, 'shortcodeGrantedmsgInput'), 'safaripush', 'safaripush-shortcode');
	    add_settings_field('safaripush-shortcode-denied-msg', __( 'Permission denied message', 'safari-push' ), array($this, 'shortcodeDeniedmsgInput'), 'safaripush', 'safaripush-shortcode');
    }

    function registerSettings() {
	    register_setting('safaripush', 'safaripush_servertype');
	    register_setting('safaripush', 'safaripush_webserviceurl');
	    register_setting('safaripush', 'safaripush_websitepushid');
	    register_setting('safaripush', 'safaripush_pushendpoint');
	    register_setting('safaripush', 'safaripush_authcode');
	    register_setting('safaripush', 'safaripush_titletag');
	    register_setting('safaripush', 'safaripush_bodytag');
	    register_setting('safaripush', 'safaripush_actiontag');
	    register_setting('safaripush', 'safaripush_urlargstag');
	    register_setting('safaripush', 'safaripush_authtag');
	    register_setting('safaripush', 'safaripush_pushwooshapplication');
	    register_setting('safaripush', 'safaripush_pushwooshendpoint');
	    register_setting('safaripush', 'safaripush_defaultmsg');
	    register_setting('safaripush', 'safaripush_unsupportedmsg');
	    register_setting('safaripush', 'safaripush_errormsg');
	    register_setting('safaripush', 'safaripush_grantedmsg');
	    register_setting('safaripush', 'safaripush_deniedmsg');
    }

	// Enqueue Javascript

	function enqueuescripts() {

		wp_enqueue_script(
			'safaripush',
			plugins_url( '/js/safari-push.js' , __FILE__ ),
			array( 'jquery' )
		);

		// build settings to use in script http://ottopress.com/2010/passing-parameters-from-php-to-javascripts-in-plugins/
		$params = array(
			'token' => "",
			'id' => "",
			'serverType' => get_option('safaripush_servertype'),
			'webServiceURL' => get_option('safaripush_webserviceurl'),
			'websitePushID' => get_option('safaripush_websitepushid'),
			'userInfo' => "",
			'apiVersion' => get_option('safaripush_apiversion'),
			'pushwooshEndpoint' => get_option('safaripush_pushwooshendpoint'),
			'pushwooshApplication' => get_option('safaripush_pushwooshapplication'),
			'defaultMsg' => get_option('safaripush_defaultmsg'),
			'unsupportedMsg' => get_option('safaripush_unsupportedmsg'),
			'errorMsg' => get_option('safaripush_errormsg'),
			'grantedMsg' => get_option('safaripush_grantedmsg'),
			'deniedMsg' => get_option('safaripush_deniedmsg'),
		);
		wp_localize_script( 'safaripush', 'SafariPushParams', $params );
	}


	// add [safaripush] shortcode

	function renderSafariPushShortcode() {
	   return '<div class="safari-push-info"></div>';
	}

	// add admin options page

	function pluginSettings() {
	    add_options_page( 'Safari Push Notifications', 'Safari Push', 'manage_options', 'safaripush', array ( $this, 'optionsPage' ));
	}
	function optionsPage() {
		?>
    <div class="wrap">
    	<?php screen_icon(); ?>
        <h2><?php _e( 'Safari Push Notification Options', 'safari-push' ) ?></h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'safaripush' ); ?>
            <?php do_settings_sections('safaripush'); ?>
            <?php submit_button(); ?>
        </form>
        <?php if(get_option('safaripush_servertype')==1) { //pushwoosh ?>
        	<h2><?php _e( 'Send a pushwoosh notification', 'safari-push' ) ?></h2>
        	<?php _e( 'Use the form below to send a notification through pushwoosh (note that this will be sent to all currently subscribed recipients!)<br/>Be sure to save all settings first.', 'safari-push' ) ?>
        	<form id="pushwoosh-test" action="">
        	<table class="form-table"><tbody>
	        <tr valign="top"><th scope="row"><?php _e( 'Notification Title', 'safari-push' ) ?></th>
	        <td><input type="text" name="test-title" value="" /></td>
	        </tr>
	        <tr valign="top"><th scope="row"><?php _e( 'Notification Body', 'safari-push' ) ?></th>
	        <td><input type="text" name="test-body" value="" /></td>
	        </tr>
	        </tbody></table>
	        <?php submit_button("Push", "small"); ?>
        	</form>
        	<div id="test-result"></div>
        	<script type="text/javascript">
        		jQuery(document).ready(function($){

					$("#pushwoosh-test").submit(function(event){

						var testtitle = $("#test-title").val(),
							testbody = $("#test-body").val();
						var testdata = JSON.stringify({"request":{"application":"<?php echo get_option('safaripush_pushwooshapplication'); ?>","auth":"<?php get_option('safaripush_authcode'); ?>","notifications":[{"send_date":"now","content":"en","platforms":10,"data":{"custom":"json data"},"link":"","safari_title":testtitle,"safari_action":"View","safari_url_args":""}]}});

						$.ajax({
                            type: "POST",
                            url: "<?php echo get_option('safaripush_pushwooshendpoint'); ?>createMessage",
                            data: testdata,
                            dataType: "json",
                            contentType : 'application/json',

                            success: function(msg){
                            	$("#test-result").html("<?php _e('Success! ', 'safari-push'); ?>"+msg.message);
                            },
                            error: function(xhr, errorType, exception){
                            	$("#test-result").html("<?php _e('There was an error submitting the form<br/>Data sent: ', 'safari-push'); ?>"+testdata);
							},
							statusCode: {
								404: function() {
									$("#test-result").html("<?php _e('Endpoint not found (404)', 'safari-push'); ?>");
								},
								500: function() {
									$("#test-result").html("<?php _e('Server error (500)', 'safari-push'); ?>");
								},
								504: function() {
									$("#test-result").html("<?php _e('Gateway timed out (504)', 'safari-push'); ?>");
								}
							}
						});
                    event.preventDefault();
					});
				});
        	</script>
        <?php } else { ?>
        	<h2><?php _e( 'Send a push notification', 'safari-push' ) ?></h2>
	        <form action="<?php echo get_option('safaripush_webserviceurl').get_option('safaripush_pushendpoint'); ?>" method="POST" ?>
	        <?php _e( 'Use the form below to send a notification (note that this will be sent to all currently subscribed recipients!)<br/>Be sure to save all settings first.', 'safari-push' ) ?>
	        <table class="form-table"><tbody>
	        <tr valign="top"><th scope="row"><?php _e( 'Notification Title', 'safari-push' ) ?></th>
	        <td><input type="text" name="<?php echo get_option('safaripush_titletag'); ?>" value="" /></td>
	        </tr>
	        <tr valign="top"><th scope="row"><?php _e( 'Notification Body', 'safari-push' ) ?></th>
	        <td><input type="text" name="<?php echo get_option('safaripush_bodytag'); ?>" value="" /></td>
	        </tr>
	        </tbody></table>
	        <input type="hidden" name="<?php echo get_option('safaripush_authtag'); ?>" value="<?php echo get_option('safaripush_authcode'); ?>" />
	        <input type="hidden" name="<?php echo get_option('safaripush_urlargstag'); ?>" value="" />
	        <input type="hidden" name="<?php echo get_option('safaripush_actiontag'); ?>" value="View" />
	        <?php submit_button("Push", "small"); ?>
	        </form>
        <?php } ?>
        <hr/>
        <p><a href="https://developer.apple.com/notifications/safari-push-notifications/"><?php _e( 'More information on Safari Push Notifications', 'safari-push' ) ?></a></p>
        <p><?php _e( 'Safari Push Notification Plugin for Wordpress by', 'safari-push' ) ?> <a href="http://www.surrealroad.com">Surreal Road</a>. <?php echo self::surrealTagline(); ?>.</p>
        <p><?php _e( 'Plugin version', 'safari-push' ) ?> <?php echo self::$version; ?></p>
    </div>
    <?php
	}

    function initModeSettings() {

    }

    function initWebServiceSettings() {

    }

    function initDedicatedServerSettings() {

    }

    function initPushwooshSettings() {

    }

    function initShortcodeSettings() {

    }

    function webServerTypeInput(){
    	self::radio_input('safaripush_servertype', __( 'Web service type to use with this plugin', 'safari-push' ), array( __('Dedicated Push Service', 'safari-push' ), __('Pushwoosh', 'safari-push' )) );
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
    function pushwooshApplicationInput(){
    	self::text_input('safaripush_pushwooshapplication', __( 'Your Pushwoosh application code from the Control Panel, e.g. xxxxx-xxxxx', 'safari-push' ) );
    }
    function pushwooshEndpointInput(){
    	self::text_input('safaripush_pushwooshendpoint', __( 'Pushwoosh web service endpoint URL, with slash, e.g. https://cp.pushwoosh.com/json/1.3/', 'safari-push' ) );
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
    	self::text_area('safaripush_deniedmsg', __('HTML to display in Shortcode when notifications have been denied (use onClick="surrealroad_safaripush_requestPermission();" on a button to allow the user to request permission again)', 'safari-push') );
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

    function newPushwooshNotification($endpoint, $data=array()) {
    	// http://www.pushwoosh.com/programming-push-notification/pushwoosh-push-notification-remote-api/
    	$url = $endpoint."createMessage";
    	$json = json_encode( array( 'request' => $data ) );
	    $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data
            ));
        $params['http']['header'] = 'Content-Type: application/json';

        $ctx = stream_context_create($params);
        $fp = fopen($url, 'rb', false, $ctx);
        if (!$fp)
            throw new Exception("Problem with $url, $php_errmsg");

        $response = @stream_get_contents($fp);
        if ($response === false)
            return false;
        return $response;
    }

    function notifyPost($newStatus, $oldStatus, $post) {
    	if( 'publish' === $newStatus && 'publish' != $oldStatus && get_post_type($post) === 'post') { // only notify new of posts
    		$serverType = get_option('safaripush_servertype');
    		if($serverType === 1) { // pushwoosh
    			$endpoint = get_option('safaripush_pushwooshendpoint');
    			$url = parse_url( get_permalink( $post->id ) );
		    	$urlargs = ltrim($url["path"],'/');
    			$data = array(
					'application' => get_option('safaripush_pushwooshapplication'),
			        'auth' => get_option('safaripush_authcode'),
			        'notifications' => array(
	                    array(
	                        'send_date' => 'now',
	                        'content' => 'en',
	                        'platforms'=> 10, // safari
	                        'data' => array( 'custom' => 'json data' ),
	                        'link' => get_permalink( $post->id ),
	                        'safari_title' => $post->post_title,
	                        'safari_action' => "View",
	                        'safari_url_args' => $urlargs
	                    )
	                )
    			);
	    		self::newPushwooshNotification($endpoint, $data);
    		} else {
		    	$serviceURL = get_option('safaripush_webserviceurl');
		    	$endpoint = get_option('safaripush_pushendpoint');
		    	$auth = get_option('safaripush_authcode');
		    	$title = "New post published";
		    	$body = $post->post_title;
		    	$action = "View";
		    	$url = parse_url( get_permalink( $post->id ) );
		    	$urlargs = ltrim($url["path"],'/');
		    	if(isset($url["query"])) $urlargs.="?".$url["query"];
		    	$titleTag = get_option('safaripush_titletag');
		    	$bodyTag = get_option('safaripush_bodytag');
		    	$actionTag = get_option('safaripush_actiontag');
		    	$urlargsTag = get_option('safaripush_urlargstag');
		    	$authTag = get_option('safaripush_authtag');
			    self::newPushNotification($serviceURL, $endpoint, $title, $body, $action, $urlargs, $auth, $titleTag, $bodyTag, $actionTag, $urlargsTag, $authTag);
		    }
		}
    }

    // utility functions

	function checkbox_input($option, $description) {
	    ?>
	<input id='<?php echo $option?>' name='<?php echo $option?>' type='checkbox' value='1' <?php checked( '1', get_option( $option ) ); ?> /> <?php echo $description ?>
	    <?php
	}
	function radio_input($option, $description, $labels) {
		$i = 0;
		foreach($labels as $label) {
	    ?>
	<input name="<?php echo $option?>" type="radio" value="<?php echo $i; ?>" <?php checked( $i, get_option( $option ) ); ?> /> <?php echo($label); ?><br/>
	    <?php
	    	$i++;
	    }
	    echo $description;
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
	<textarea cols=100 rows=6 id='<?php echo $option?>' name='<?php echo $option?>'><?php echo esc_attr( $value ); ?></textarea><br><?php echo $description ?>
	    <?php
	}

	function surrealTagline() {
		$lines = file(plugins_url( '/surreal.strings' , __FILE__ ), FILE_IGNORE_NEW_LINES);
		return "Push " . $lines[array_rand($lines)];
	}

}

$safaripush = new SafariPush();

// shortcodes (must be declared outside of class)
add_shortcode('safari-push', array('SafariPush', 'renderSafariPushShortcode'));
