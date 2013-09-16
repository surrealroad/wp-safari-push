<?php
/*
 *	Plugin Name: Safari Push Notifications
 *	Plugin URI: https://github.com/surrealroad/wp-safari-push
 *	Description: Allows WordPress to publish updates to a push server for Safari browsers
 *	Version: 1.0
 *	Author: Surreal Road Limited
 *	Author URI: http://www.surrealroad.com
 *	License: MIT
*/

/*
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
ini_set('display_errors',1);
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

class SafariPush {

	//Version
	static $version ='1.0';
	static $apiversion = 'v1';

	//Options and defaults
	static $options = array(
		'websitePushID' => "",
		'webServiceURL' => ""
	);

	public function __construct() {
		register_activation_hook(__FILE__,array(__CLASS__, 'install' ));
		register_uninstall_hook(__FILE__,array( __CLASS__, 'uninstall'));
		add_action('init', array($this, 'init'));
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_init', array($this,'registerSettings'));
		add_action('admin_menu', array($this,'pluginSettings'));
		add_action('admin_footer', array($this, 'ajaxSubmitPush'));
	}

	static function install(){
		update_option("safaripush_version",self::$version);
		update_option("safaripush_apiversion",self::$apiversion);
		add_option("safaripush_webserviceurl", "");
		add_option("safaripush_websitepushid", "");
		add_option("safaripush_pushendpoint", "/".self::$apiversion."/push");
		add_option("safaripush_authcode", "");
		add_option("safaripush_titletag", "title");
		add_option("safaripush_bodytag", "body");
		add_option("safaripush_actiontag", "button");
		add_option("safaripush_actionurlargstag", "urlargs");
		add_option("safaripush_authtag", "");
	}

	static function uninstall(){
		delete_option('safaripush_version');
		delete_option('safaripush_apiversion');
		delete_option('safaripush_webserviceurl');
		delete_option('safaripush_authcode');
		delete_option('safaripush_websitepushid');
		delete_option('safaripush_pushendpoint');
		delete_option('safaripush_titletag');
		delete_option('safaripush_bodytag');
		delete_option('safaripush_actiontag');
		delete_option('safaripush_actionurlargstag');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain('safaripush', false, basename(dirname(__FILE__)).'/languages');

		add_action('wp_enqueue_scripts', array($this, 'enqueuescripts'));
		add_action('transition_post_status', array($this, 'notifyPost'), 10, 3);
	}

	public function admin_init() {
	    add_settings_section('default-safaripush', 'Web Service Settings', array($this, 'initDefaultSettings'), 'safaripush');
	    add_settings_field('safaripush-web-service-url', 'Web Service URL', array($this, 'webServiceURLInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-website-push-id', 'Website Push ID', array($this, 'websitePushIDInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-push-endpoint', 'Web Service Push Endpoint', array($this, 'pushEndpointInput'), 'safaripush', 'default-safaripush');
   	    add_settings_field('safaripush-auth-code', 'Web Service Authentication Code', array($this, 'webServiceAuthInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-title-tag', 'Web Service Push Title Tag', array($this, 'pushTitleTagInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-body-tag', 'Web Service Push Body Tag', array($this, 'pushBodyTagInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-action-tag', 'Web Service Push Action Tag', array($this, 'pushActionTagInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-url-args-tag', 'Web Service Push URL Arguments Tag', array($this, 'pushURLArgsTagInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-auth-tag', 'Web Service Push Authentication Tag', array($this, 'pushAuthTagInput'), 'safaripush', 'default-safaripush');
    }

    function registerSettings() {
	    register_setting('safaripush', 'safaripush_webserviceurl');
	    register_setting('safaripush', 'safaripush_websitepushid');
	    register_setting('safaripush', 'safaripush_pushendpoint');
	    register_setting('safaripush', 'safaripush_authcode');
	    register_setting('safaripush', 'safaripush_titletag');
	    register_setting('safaripush', 'safaripush_bodytag');
	    register_setting('safaripush', 'safaripush_actiontag');
	    register_setting('safaripush', 'safaripush_urlargstag');
	    register_setting('safaripush', 'safaripush_authtag');
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
			'webServiceURL' => get_option('safaripush_webserviceurl'),
			'websitePushID' => get_option('safaripush_websitepushid'),
			'userInfo' => "",
			'apiVersion' => get_option('safaripush_apiversion')
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
        <h2>Safari Push Notification Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'safaripush' ); ?>
            <?php do_settings_sections('safaripush'); ?>
            <?php submit_button(); ?>
        </form>
        <h2>Send a push notification</h2>
        <form action="<?php echo get_option('safaripush_webserviceurl').get_option('safaripush_pushendpoint'); ?>" method="POST" ?>
        Use the form below to send a notification (note that this will be sent to all currently subscribed recipients!)
        <table class="form-table"><tbody>
        <tr valign="top"><th scope="row">Notification Title</th>
        <td><input type="text" name="<?php echo get_option('safaripush_titletag'); ?>" value="" /></td>
        </tr>
        <tr valign="top"><th scope="row">Notification Body</th>
        <td><input type="text" name="<?php echo get_option('safaripush_bodytag'); ?>" value="" /></td>
        </tr>
        </tbody></table>
        <input type="hidden" name="<?php echo get_option('safaripush_authtag'); ?>" value="<?php echo get_option('safaripush_authcode'); ?>" />
        <input type="hidden" name="<?php echo get_option('safaripush_urlargstag'); ?>" value="test" />
        <input type="hidden" name="<?php echo get_option('safaripush_actiontag'); ?>" value="View" />
        <?php submit_button("Push", "small"); ?>
        </form>
        <hr/>
        <p><a href="https://developer.apple.com/notifications/safari-push-notifications/">More information on Safari Push Notifications</a></p>
        <p>Safari Push Notification Plugin for Wordpress by <a href="http://www.surrealroad.com">Surreal Road</a>. <?php echo self::surrealTagline(); ?>.</p>
        <p>Plugin version <?php echo self::$version; ?></p>
    </div>
    <?php
	}

    function initDefaultSettings() {

    }

    function webServiceURLInput(){
    	self::text_input('safaripush_webserviceurl', 'Base URL to your push web service, e.g. https://mypushservice.com');
    }
    function websitePushIDInput(){
    	self::text_input('safaripush_websitepushid', 'Unique identifier for your Website Push ID, e.g. web.com.mysite');
    }
    function pushEndpointInput(){
    	self::text_input('safaripush_pushendpoint', 'Endpoint for your web service to receive new notifications, e.g. /v1/push');
    }
    function webServiceAuthInput(){
    	self::text_input('safaripush_authcode', 'Authentication code for your web service');
    }
    function pushTitleTagInput(){
    	self::text_input('safaripush_titletag', 'Endpoint tag for push notification title, e.g. title');
    }
    function pushBodyTagInput(){
    	self::text_input('safaripush_bodytag', 'Endpoint tag for push notification body, e.g. body');
    }
    function pushActionTagInput(){
    	self::text_input('safaripush_actiontag', 'Endpoint tag for push notification action button label, e.g. button');
    }
    function pushURLArgsTagInput(){
    	self::text_input('safaripush_urlargstag', 'Endpoint tag for push notification URL arguments, e.g. urlargs');
    }
    function pushAuthTagInput(){
    	self::text_input('safaripush_authtag', 'Endpoint tag for push notification authentication, e.g. auth');
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
    	if($newStatus === 'publish' && get_post_type($post) === 'post') { // only notify new of posts
    		//wp_mail("jack@ctrlcmdesc.com", "push", $newStatus.$oldStatus.$post);
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