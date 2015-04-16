<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class WCFC_Shipping
 *
 *	Main WCFC class initializes the plugin
 *
 *	@class		WCFC_Shipping
 *	@version	1.0.0
 *	@author		FreightCenter
 */
class WCFC_Shipping {


	/**
	 * Construct.
	 *
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add shipping method
		add_action( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );

		// Initialize shipping method class
		add_action( 'woocommerce_shipping_init', array( $this, 'init_shipping_file' ) );


		// Separate per warehouse shipping
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'warehouse_shipping_packages' ), 10 );


		// Add extra totals row for options
		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'freightcenter_shipping_options' ) );

		// Add Location type shipping field
		add_filter( 'woocommerce_checkout_fields', array( $this, 'location_type_shipping_field' ) );

		// Add extra shipping cache param
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'add_location_type_to_shipping_cache' ), 9 );

	}


	/**
	 * Shipping method.
	 *
	 * Include the WooCommerce shipping method class.
	 *
	 * @since 1.0.0
	 */
	public function init_shipping_file() {

		/**
		 * Shipping method class
		 */
		require_once plugin_dir_path( __FILE__ ) . '/class-wcfc-shipping-method.php';

	}


	/**
	 * Add shipping method.
	 *
	 * Add shipping method to WooCommerce.
	 *
	 * @since 1.0.0
	 */
	public function add_shipping_method( $methods ) {

		if ( class_exists( 'WCFC_Shipping_Method' ) ) :
			$methods[] = 'WCFC_Shipping_Method';
		endif;

		return $methods;

	}


	/**
	 * Separate shipping.
	 *
	 * Separate the shipping costs per warehouse.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $packages List of existing packages.
	 * @return 	array			List of modified packages.
	 */
	public function warehouse_shipping_packages( $packages ) {

		// Reset packages
		$packages = array();
		$warehouses = array();

		// Set cart products in array per warehouse
		foreach ( WC()->cart->get_cart() as $key => $item ) :

			$origin_warehouse 	= get_post_meta( $item['product_id'], '_origin_warehouse', true );
			$ship_via_fc		= get_post_meta( $item['product_id'], '_ship_via_freight', true );

			// Add cart products to array per warehouse
		    if ( ! empty( $origin_warehouse ) && 'yes' == $ship_via_fc ) :
		    	$warehouses[ $origin_warehouse ][] = $item;
		    else :
		    	$warehouses['others'][] = $item;
		    endif;

		endforeach;


		// Put inside packages
		foreach ( $warehouses as $warehouse ) :

			$packages[] = array(
				'contents' 			=> $warehouse,
				'contents_cost' 	=> array_sum( wp_list_pluck( $warehouse, 'line_total' ) ),
				'applied_coupons' 	=> WC()->cart->applied_coupons,
				'destination' 		=> array(
					'country' 			=> WC()->customer->get_shipping_country(),
					'state' 			=> WC()->customer->get_shipping_state(),
					'postcode' 			=> WC()->customer->get_shipping_postcode(),
					'city' 				=> WC()->customer->get_shipping_city(),
					'address' 			=> WC()->customer->get_shipping_address(),
					'address_2' 		=> WC()->customer->get_shipping_address_2(),
					'location_type' 	=> WC()->session->get( 'location_type' ),
					'location_options' 	=> WC()->session->get( 'location_options' ),
				)
			);

		endforeach;

		return $packages;

	}


	/**
	 * Needs Freight shipping.
	 *
	 * Check if the cart contains products that need
	 * Freight shipping.
	 *
	 * @since 1.0.0
	 *
	 * @return BOOL True if cart needs freight shipping, false if not.
	 */
	public function needs_freight_shipping() {

		// Check if cart needs freight shipping
		$needs_freight_shipping = false;

		foreach ( WC()->cart->get_cart() as $key => $item ) :
			if ( 'yes' == get_post_meta( $item['product_id'], '_ship_via_freight', true ) ) :
				$needs_freight_shipping = true;
			endif;
		endforeach;

		return $needs_freight_shipping;

	}


	/**
	 * Options totals row.
	 *
	 * Add an extra totals row to the checkout for options.
	 *
	 * @since 1.0.0
	 */
	public function freightcenter_shipping_options() {

		// Bail if freight shipping is not required
		if ( ! $this->needs_freight_shipping() ) :
			return;
		endif;

		$package 			= array( 'destination' => array( 'location_type' => 'residential' ) );
		$packages 			= WC()->shipping->get_packages();
		$post_data 			= isset( $_POST['post_data'] ) ? wp_parse_args( $_POST['post_data'] ) : null;
		$location_options	= isset( $post_data['_location_options'] ) ? $post_data['_location_options'] : array();

		// Get chosen method
		$chosen_method 	= '';
		foreach ( $packages as $i => $pack ) :
			$chosen_method = isset( WC()->session->chosen_shipping_methods[ $i ] ) ? WC()->session->chosen_shipping_methods[ $i ] : '';
			$package = $pack;
		endforeach;

		// Don't show options if shipping method is not FreightCenter
		if ( 'freight_center' != $chosen_method ) :
// 			return;
		endif;

		// Display extra FreightCenter options
		?><tr class="shipping shipping-options">

			<th>
				<?php _e( 'Options', 'woocommerce-freightcenter' ); ?>
			</th>

			<td><?php

				do_action( 'wcfc_before_extra_shipping_options' );

				$destination = $package['destination'];

				if ( ! isset( $destination['location_type'] ) || 'Residential' == $destination['location_type'] ) :

					?><input id='lift_gate' type='checkbox' name='_location_options[_destination_lift_gate]' value='1' <?php @checked( 1, $location_options['_destination_lift_gate'] ); ?>>
					<label for='lift_gate'><?php _e( 'Lift Gate at Delivery Point', 'woocommerce-freightcenter' ); ?></label><br/><?php // NOT WOKRING YET

				elseif ( 'BusinessWithDockOrForklift' == $destination['location_type'] ) :

					?><input id='call_before_delivery' type='checkbox' name='_location_options[_call_before_delivery]' value='1' <?php @checked( 1, $location_options['_call_before_delivery'] ); ?>>
					<label for='call_before_delivery'><?php _e( 'Call before Delivery', 'woocommerce-freightcenter' ); ?></label><br/>

					<input id='threshold_delivery' type='checkbox' name='_location_options[_threshold_delivery]' value='1' <?php @checked( 1, $location_options['_threshold_delivery'] ); ?>>
					<label for='threshold_delivery'><?php _e( 'Threshold Delivery (Inside Delivery)', 'woocommerce-freightcenter' ); ?></label><br/>

					<input id='limited_access_delivery' type='checkbox' name='_location_options[_limited_access_delivery]' value='1' <?php @checked( 1, $location_options['_limited_access_delivery'] ); ?>>
					<label for='limited_access_delivery'><?php _e( 'Limited Access Delivery', 'woocommerce-freightcenter' ); ?></label><?php

				elseif ( 'BusinessWithoutDockOrForklift' == $destination['location_type'] ) :

					?><input id='lift_gate' type='checkbox' name='_location_options[_destination_lift_gate]' value='1' <?php @checked( 1, $location_options['_destination_lift_gate'] ); ?>>
					<label for='lift_gate'><?php _e( 'Lift Gate at Delivery Point', 'woocommerce-freightcenter' ); ?></label><br/> <?php // NOT WOKRING YET ?>

					<input id='limited_access_delivery' type='checkbox' name='_location_options[_limited_access_delivery]' value='1' <?php @checked( 1, $location_options['_limited_access_delivery'] ); ?>
					<label for='limited_access_delivery'><?php _e( 'Limited Access Delivery', 'woocommerce-freightcenter' ); ?></label><?php

				elseif ( 'Terminal' == $destination['location_type'] ) :
					// None
				elseif ( 'ConstructionSite' == $destination['location_type'] ) :

					?><input id='lift_gate' type='checkbox' name='_location_options[_destination_lift_gate]' value='1' <?php @checked( 1, $location_options['_destination_lift_gate'] ); ?>
					<label for='lift_gate'><?php _e( 'Lift Gate at Delivery Point', 'woocommerce-freightcenter' ); ?></label><br/><?php // NOT WOKRING YET

				elseif ( 'ConventionCenterOrTradeshow' == $destination['location_type'] ) :

					?><input id='lift_gate' type='checkbox' name='_location_options[_destination_lift_gate]' value='1' <?php @checked( 1, $location_options['_destination_lift_gate'] ); ?>
					<label for='lift_gate'><?php _e( 'Lift Gate at Delivery Point', 'woocommerce-freightcenter' ); ?></label><br/><?php // NOT WOKRING YET

				endif;

				do_action( 'wcfc_after_extra_shipping_options' );

			?></td>

		</tr><?php

	}


	/**
	 * Location type.
	 *
	 * Add a location type dropdown to the shipping fields, used
	 * by FreightCenter to calculate shipping costs.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $fields 	List of existing shipping fields.
	 * @return 	array			List of modified shipping fields containing location type.
	 */
	public function location_type_shipping_field( $fields ) {

		// Bail if freight shipping is not required
		if ( ! $this->needs_freight_shipping() ) :
			return $fields;
		endif;

		$settings = get_option( 'woocommerce_freight_center_settings', array() );
		$options = array();

		// Set the field options
		if ( $location_types = $settings['destination_location_type'] ) :

			if ( in_array( 'Residential', $location_types ) ) :
				$options['Residential'] = __( 'Residential', 'woocommerce-freightcenter' );
			endif;
			if ( in_array( 'BusinessWithDockOrForklift', $location_types ) ) :
				$options['BusinessWithDockOrForklift'] = __( 'Business with dock or forklift', 'woocommerce-freightcenter' );
			endif;
			if ( in_array( 'BusinessWithoutDockOrForklift', $location_types ) ) :
				$options['BusinessWithoutDockOrForklift'] = __( 'Business without dock or forklift', 'woocommerce-freightcenter' );
			endif;
			if ( in_array( 'Terminal', $location_types ) ) :
				$options['Terminal'] = __( 'Terminal', 'woocommerce-freightcenter' );
			endif;
			if ( in_array( 'ConstructionSite', $location_types ) ) :
				$options['ConstructionSite'] = __( 'Construction site', 'woocommerce-freightcenter' );
			endif;
			if ( in_array( 'ConventionCenterOrTradeshow', $location_types ) ) :
				$options['ConventionCenterOrTradeshow'] = __( 'Convention center or Tradeshow', 'woocommerce-freightcenter' );
			endif;

		endif;

		// Add the field
		$fields['billing']['_location_type'] = array(
			'label' 	=> __( 'Location Type', 'woocommerce-freightcenter' ),
			'type' 		=> 'select',
			'options' 	=> $options,
			'class' 	=> array( 'update_totals_on_change' ),
			'required' 	=> true,
		);

		return $fields;

	}


	/**
	 * Location type cache.
	 *
	 * Adds location type to the shipping cache.
	 * Without this, and shipping caching enabled, the rates won't recalculate when
	 * location type changes.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $packages List of shipping packages.
	 * @return 	array			List of shipping packages including location type.
	 */
	public function add_location_type_to_shipping_cache( $packages ) {

		if ( ! isset( $_POST['post_data'] ) ) :
			return $packages;
		endif;

		$post_data = wp_parse_args( $_POST['post_data'] );

		// Bail if location type is not set
		if ( ! isset( $post_data['_location_type'] ) ) :
			return $packages;
		endif;

		WC()->session->set( 'location_type', $post_data['_location_type'] );
		WC()->session->set( 'location_options', isset( $post_data['_location_options'] ) ? $post_data['_location_options'] : '' );

		return $packages;

	}


}
