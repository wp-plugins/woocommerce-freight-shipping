<?php
/*
 * Plugin Name: 	WooCommerce Freight Shipping
 * Plugin URI: 		http://www.freightcenter.com/
 * Description: 	Display freight shipping options and costs from multiple carriers at checkout.
 * Version: 		1.0.0
 * Author: 			freightcenter
 * Author URI: 		http://www.freightcenter.com/
 * Text Domain: 	woocommerce-freightcenter
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class WooCommerce_FreightCenter
 *
 *	Main WCFC class initializes the plugin
 *
 *	@class		WooCommerce_FreightCenter
 *	@version	1.0.0
 *	@author		Jeroen Sormani
 */
class WooCommerce_FreightCenter {


	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string $version Plugin version number.
	 */
	public $version = '1.0.0';


	/**
	 * Instance of WooCommerce_FreightCenter.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var object $instance The instance of WCFC.
	 */
	private static $instance;


	/**
	 * Construct.
	 *
	 * Initialize the class and plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) :
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		endif;

		// Check if WooCommerce is active
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) :
			if ( ! is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) :
				return;
			endif;
		endif;

		// Initialize plugin components
		$this->init();

		// Enqueue scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20  );

	}


	/**
	 * Instance.
	 *
	 * An global instance of the class. Used to retrieve the instance
	 * to use on other files/plugins/themes.
	 *
	 * @since 1.0.0
	 * @return object Instance of the class.
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}


	/**
	 * init.
	 *
	 * Initialize plugin parts.
	 *
	 * @since 1.0.0
	 */
	public function init() {

		/**
		 * API class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wcfc-api.php';

		$this->api = new WCFC_Api();


		/**
		 * Warehouse class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wcfc-warehouse.php';

		$this->warehouse = new WCFC_Warehouse();


		/**
		 * Product data class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/admin/class-wcfc-product-data.php';

		$this->product_data = new WCFC_Product_Data();


		/**
		 * Shipping class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wcfc-shipping.php';

		$this->shipping = new WCFC_Shipping();

		/**
		 * Order class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wcfc-order.php';

		$this->order = new WCFC_Order();

	}


	/**
	 * Enqueue scripts.
	 *
	 * Enqueue javascript and styesheets.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		if ( is_admin() ) :
			wp_enqueue_script( 'woocommerce-freightcenter-admin', plugins_url( '/assets/js/woocommerce-freightcenter-admin.js', __FILE__ ), array(
				'jquery',
			), $this->version );
		else :
			wp_enqueue_script( 'woocommerce-freightcenter', plugins_url( '/assets/js/woocommerce-freightcenter.js', __FILE__ ), array(
				'jquery',
				'wc-checkout'
			), $this->version );
		endif;

	}


	/**
	 * Logging.
	 *
	 * Enable logs to keep track of API calls and updates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message Message to put in the log.
	 */
	public function log( $message ) {

		if ( 'yes' == get_option( 'freightcenter_debug', 'yes' ) ) :

			$log_file = plugin_dir_path( __FILE__ ) . '/log.txt';
			if ( is_writeable( $log_file ) ) :
				$log 		= fopen( $log_file, 'a+' );
				$message 	= '[' . date( 'd-m-Y H:i:s' ) . '] ' . $message . PHP_EOL;
				fwrite( $log, $message );
				fclose( $log );
			else :
				error_log( 'log file not writable' );
				error_log( $message );
			endif;

		endif;

	}


}




/**
 * The main function responsible for returning the WooCommerce_FreightCenter object.
 *
 * Use this function like you would a global variable, except without needing to declare the global.
 *
 * Example: <?php WCFC()->method_name(); ?>
 *
 * @since 1.0.0
 *
 * @return object WooCommerce_FreightCenter class object.
 */
if ( ! function_exists( 'WCFC' ) ) :

 	function WCFC() {
		return WooCommerce_FreightCenter::instance();
	}

endif;

WCFC();
