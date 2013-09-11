<?php
/*
 *	Plugin Name: Safari Push Notifications
 *	Plugin URI: https://github.com/surrealroad/Safari-Push-Notifications
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

class SafariPush {

	//Version
	static $version ='1.0';

	//Options and defaults
	static $options = array(
		'websitePushID' => "",
		'webServiceURL' => ""
	);

	public function __construct() {
		register_activation_hook(__FILE__,array(__CLASS__, 'install' ));
		register_uninstall_hook(__FILE__,array( __CLASS__, 'uninstall' ));
		add_action('init', array( $this, 'init' ));
		add_action('admin_init', array( $this, 'admin_init' ));
		add_action('admin_init', array($this,'registerSettings'));
		add_action('admin_menu', array($this,'pluginSettings'));
	}

	static function install(){
		update_option("safaripush_version",self::$version);
		add_option("safaripush_webserviceurl", "");
		add_option("safaripush_websitepushid", "");
	}

	static function uninstall(){
		delete_option('safaripush_version');
		delete_option('safaripush_webserviceurl');
		delete_option('safaripush_websitepushid');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain('safaripush', false, basename(dirname(__FILE__)).'/languages');

		add_action('wp_enqueue_scripts', array($this, 'enqueuescripts'));
	}

	public function admin_init() {
	    add_settings_section('default-safaripush', 'Default Settings', array($this, 'initDefaultSettings'), 'safaripush');
	    add_settings_field('safaripush-web-service-url', 'Web Service URL', array($this, 'webServiceURLInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-website-push-id', 'Website Push ID', array($this, 'websitePushIDInput'), 'safaripush', 'default-safaripush');
    }

    function registerSettings() {
	    register_setting('safaripush', 'safaripush_webserviceurl');
	    register_setting('safaripush', 'safaripush_websitepushid');
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
			'userInfo' => ""
		);
		wp_localize_script( 'safaripush', 'SafariPushParams', $params );
	}


	// add [safaripush] shortcode

	function renderSafariPushShortcode() {
	   return '';
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
	<input id='<?php echo $option?>' name='<?php echo $option?>' type='text' value='<?php echo esc_attr( $value ); ?>' /> <?php echo $description ?>
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

}

$safaripush = new SafariPush();

// shortcodes (must be declared outside of class)
add_shortcode('safari-push', array('SafariPush', 'renderSafariPushShortcode'));
