<?php
/**
 * Plugin Name:     Admin Coalition Login
 * Plugin URI:      https://www.expresstechsoftwares.com/
 * Description:     Integrates WooCommerce with Discord and Steam, requiring users to connect both accounts before checkout.
 * Author:          ExpressTech Software Solutions
 * Author URI:      https://www.expresstechsoftwares.com/
 * Text Domain:     'admin-coalition'
 * Domain Path:     /languages
 * Version:         1.1.0
 *
 * @package         Woo_Discord_Steam_Integration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'ADMIN_COALITION', '1.0.0' );

/**
 * Woo_Discord_Steam_Integration
 *
 * The main instance of the plugin.
 *
 * @since 1.0.0
 */
class Woo_Discord_Steam_Integration {

	/**
	 * The Single instance of the class.
	 *
	 * @var obj Woo_Discord_Steam_Integration
	 */
	protected static $instance;

	/**
	 * Plugin Version.
	 *
	 * @var String
	 */
	public $plugin_version;

	/**
	 * Plugin Name
	 *
	 * @var String
	 */
	public $plugin_name;



	public function __construct() {

		if ( ! defined( 'ADMIN_COALITION' ) ) {
			$this->plugin_version = '1.0.0';
		} else {
			$this->plugin_version = ADMIN_COALITION;
		}

		$this->plugin_name = 'admin-coalition';

		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );

		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action( 'woocommerce_order_status_completed', array( $this, 'test_order' ) );
		add_action( 'woocommerce_payment_complete', array( $this, 'test_order' ) );
		add_action( 'woocommerce_payment_complete_order_status_completed', array( $this, 'test_order' ) );
	}
	public function test_order( $order_id ) {

		$current_action = current_action();

		// error_log( print_r( " Current Action Hook : $current_action", true ) );
	}

	/**
	 * Gets the main Woo_Discord_Steam_Integration instance.
	 *
	 * Ensures only one instance of Woo_Discord_Steam_Integration is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return Woo_Discord_Steam_Integration instance
	 */
	public static function start() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		$cloning_message = sprintf(
			esc_html__( 'You cannot clone instances of %s.', 'admin-coalition' ),
			get_class( $this )
		);
		_doing_it_wrong( __FUNCTION__, $cloning_message, $this->version );
	}

	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		$unserializing_message = sprintf(
			esc_html__( 'You cannot clone instances of %s.', 'admin-coalition' ),
			get_class( $this )
		);
		_doing_it_wrong( __FUNCTION__, $unserializing_message, $this->version );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	public function activation_check() {

		// Maybe set some default options
		$discord_bot_redirect_url  = Woo_Discord_Steam_Integration_Utils::get_current_screen_url();
		$discord_auth_redirect_rul = wc_get_checkout_url() . '?via=discord';
		update_option( 'discord_bot_redirect_url', $discord_bot_redirect_url );
		update_option( 'discord_auth_redirect_url', $discord_auth_redirect_rul );
	}


	/**
	 * Deactivate the plugin
	 *
	 * @since 1.0.0
	 */
	protected function deactivate_plugin() {

		deactivate_plugins( plugin_basename( __FILE__ ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0.0
	 */
	public function init_plugin() {

		if ( is_admin() ) {

			new Woo_Discord_Steam_Integration_Admin();

		}
		$this->frontend_includes();
	}

	/**
	 * Include frontend template functions and hooks.
	 */
	public function frontend_includes() {

		new Woo_Discord_Steam_Integration_Front( new Woo_Discord_Steam_Integration_Discord_Handler() );
	}


	/** -----------------------------------------------------------------------------------*/
	/** Helper Functions                                                                  */
	/*-----------------------------------------------------------------------------------*/

	/**
	 * Get the plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {

		return $this->plugin_name;
	}

	/**
	 * Get the plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version() {

		return $this->plugin_version;
	}

	/**
	 * Get the plugin url.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function plugin_url() {

		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function plugin_path() {

		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the plugin base path name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function plugin_basename() {

		return plugin_basename( __FILE__ );
	}

	/**
	 * Register the built-in autoloader
	 *
	 * @codeCoverageIgnore
	 */
	public static function register_autoloader() {
		spl_autoload_register( array( 'Woo_Discord_Steam_Integration', 'autoloader' ) );
	}

	/**
	 * Register autoloader.
	 *
	 * @param string $class_name Class name to load.
	 */
	public static function autoloader( $class_name ) {

		$class = strtolower( str_replace( '_', '-', $class_name ) );
		$file  = plugin_dir_path( __FILE__ ) . '/includes/class-' . $class . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Load the plugin text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'admin-coalition', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

require_once plugin_dir_path( __FILE__ ) . 'wp-cli/get-discord-rules-command.php';

/**
 * Returns the main instance of Woo_Discord_Steam_Integration.
 */
function Woo_Discord_Steam_Integration() {

	Woo_Discord_Steam_Integration::register_autoloader();
	return Woo_Discord_Steam_Integration::start();
}

Woo_Discord_Steam_Integration();
