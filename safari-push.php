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
		add_action('admin_menu', array($this,'pluginSettings'));
	}

	static function install(){
		update_option("safaripush_version",self::$version);
		add_option('safaripush',self::$options);
	}

	static function uninstall(){
		delete_option('safaripush_version');
		delete_option('safaripush');
	}


	public function init() {
		//Allow translations
		load_plugin_textdomain('safaripush', false, basename(dirname(__FILE__)).'/languages');

		add_action('wp_enqueue_scripts', array($this, 'enqueuescripts'));
		add_action('init', array($this, 'registerShortcodes'));
	}

	public function admin_init(){
	    register_setting('safaripush', 'safaripush', array($this, 'validateOptions'));
	    add_settings_section('default-safaripush', 'Default Settings', array($this, 'initDefaultSettings'), 'safaripush');
	    add_settings_field('safaripush-web-service-url', 'Web Service URL', array($this, 'webServiceURLInput'), 'safaripush', 'default-safaripush');
	    add_settings_field('safaripush-website-push-id', 'Website Push ID', array($this, 'websitePushIDInput'), 'safaripush', 'default-safaripush');
    }

	// Enqueue Javascript

	function enqueuescripts() {

		$options = get_option('safaripush');

		wp_enqueue_script(
			'safaripush',
			plugins_url( '/js/safari-push.js' , __FILE__ ),
			array( 'jquery' )
		);

		// build settings to use in script http://ottopress.com/2010/passing-parameters-from-php-to-javascripts-in-plugins/
		$params = array(
			'token' => "",
			'id' => "",
			'webServiceURL' => $options['web-service-url'],
			'websitePushID' => $options['website-push-id'],
			'userInfo' => ""
		);
		wp_localize_script( 'safaripush', 'SafariPushParams', $params );
	}


	// add [safaripush] shortcode

	function renderSafariPushShortcode() {
	   return '';
	}
	function registerShortcodes(){
	   add_shortcode('safaripush', array($this, 'renderSafariPushShortcode'));
	}


	// add admin options page

	function pluginSettings() {
	    add_options_page( 'Safari Push Notifications', 'Safari Push', 'manage_options', 'safaripush', array ( $this, 'optionsPage' ));
	}
	function optionsPage() {
		?>
    <div class="wrap">
        <h2>Safari Push Notification Options</h2>
        <form action="options.php" method="POST">
            <?php settings_fields( 'safaripush' ); ?>
            <?php do_settings_sections('safaripush'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
	}

	function validateOptions($input) {
    	return $input;
    }
    function initDefaultSettings() {

    }

    function webServiceURLInput(){
    	self::text_input('web-service-url', 'Base URL to your push web service, e.g. https://mypushservice.com');
    }
    function websitePushIDInput(){
    	self::text_input('website-push-id', 'Unique identifier for your Website Push ID, e.g. web.com.mysite');
    }

	function checkbox_input($option, $description) {
	    $options = get_option( 'safaripush' );
	    if (array_key_exists($option, $options) and $options[$option] == 1) {
	      $value = 'checked="checked"';
	    } else {
	      $value = '';
	    }
	    ?>
	<input id='safaripush-<?php echo $option?>' name='safaripush[<?php echo $option?>]' type='checkbox' value='1' <?php echo $value?> /> <?php echo $description ?>
	    <?php
	}
	function text_input($option, $description) {
	    $options = get_option( 'safaripush' );
	    if (array_key_exists($option, $options)) {
	      $value = $options[$option];
	    } else {
	      $value = '';
	    }
	    ?>
	<input id='safaripush-<?php echo $option?>' name='safaripush[<?php echo $option?>]' type='text' value='<?php echo esc_attr( $value ); ?>' /> <?php echo $description ?>
	    <?php
	}
	function text_area($option, $description) {
	    $options = get_option( 'safaripush' );
	    if (array_key_exists($option, $options)) {
	      $value = $options[$option];
	    } else {
	      $value = '';
	    }
	    ?>
	<textarea cols=100 rows=6 id='safaripush-<?php echo $option?>' name='safaripush[<?php echo $option?>]'><?php echo esc_attr( $value ); ?></textarea><br><?php echo $description ?>
	    <?php
	}

}

$safaripush = new SafariPush();
