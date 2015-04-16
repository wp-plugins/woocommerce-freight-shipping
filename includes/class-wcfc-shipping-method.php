<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( class_exists( 'WCFC_Shipping_Method' ) ) return; // Exit if class already exists

/**
 *	Class WCFC_Shipping_Method
 *
 *	Shipping method class.
 *
 *	@class		WCFC_Shipping_Method
 *	@version	1.0.0
 *	@author		FreightCenter
 */
class WCFC_Shipping_Method extends WC_Shipping_Method {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->id                	= 'freight_center';
		$this->title  				= __( 'FreightCenter', 'woocommerce-freightcenter' );
		$this->method_title  		= __( 'FreightCenter', 'woocommerce-freightcenter' );
		$this->method_description 	= __( 'Configure WooCommerce FreightCenter Shipping', 'woocommerce-freightcenter' );

		$this->init();

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

		if ( version_compare( WC()->version, '2.1', '<' ) ) :
			add_filter( 'woocommerce_available_shipping_methods', array( $this, 'hide_invalid_shipping_methods_for_packages' ), 10, 2 );
		else :
			add_filter( 'woocommerce_package_rates', array( $this, 'hide_invalid_shipping_methods_for_packages' ), 10, 2 );
		endif;

	}


	/**
	 * Init settings.
	 *
	 * Initialize the settings for this shipping method.
	 *
	 * @since 1.0.0
	 */
	function init() {

		$this->init_form_fields();
		$this->init_settings();

		$this->enabled		 	= $this->get_option( 'enabled' );
		$this->shipping_title 	= $this->get_option( 'shipping_title' );
		$this->availability 	= $this->get_option( 'availability' );
		$this->countries 		= $this->get_option( 'countries' );

	}


	/**
	 * is_available function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return bool
	 */
	function is_available( $package ) {

		if ( $this->enabled == "no" ) :
			return false;
		endif;

		// Check if all required dimensions are set
		foreach ( $package['contents'] as $product ) :
			$_product = wc_get_product( ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'] );
			$ship_via_freight = get_post_meta( $_product->id, '_ship_via_freight', true );
			if ( 'yes' == $ship_via_freight && ( '' == $_product->weight || '' == $_product->length || '' == $_product->width || '' == $_product->length ) ) :
				wc_add_notice( __( 'There is a product in the cart without dimensions/weight. Please contact the shopowner.', 'woocommerce-freightcenter' ), 'error' );
				return false;
			endif;

		endforeach;

		$ship_to_countries = '';

		if ( $this->availability == 'specific' ) :
			$ship_to_countries = $this->countries;
		else :
			$ship_to_countries = array_keys( array( 'US' => __( 'United States (US)', 'woocommerce' ), 'CA' => __( 'Canada', 'woocommerce' ) ) ); // Only US + CA
		endif;

		if ( is_array( $ship_to_countries ) ) :
			if ( ! in_array( $package['destination']['country'], $ship_to_countries ) ) :
				return false;
			endif;
		endif;

		// Enabled logic
		$is_available = true;

		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package );

	}


	/**
	 * Init form fields.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		$api_carriers = WCFC()->api->get_active_carriers();

		// Set all carriers
		$carriers = array();

		if ( $api_carriers ) :
			foreach ( $api_carriers as $carrier ) :
				$carriers[ $carrier->CarrierCode ] = $carrier->CarrierName;
			endforeach;
		endif;


		$this->form_fields = array(

			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable FreightCenter', 'woocommerce-freightcenter' ),
				'default' 		=> 'yes'
			),

			'shipping_title' => array(
				'title' 		=> __( 'Method title', 'woocommerce-freightcenter' ),
				'type' 			=> 'text',
				'description'	=> __( 'Method title is shown to the user at checkout', 'woocommerce-freightcenter' ),
				'default'		=> 'Shipping',
				'desc_tip'		=> true,
				'placeholder'	=> '',

			),

			'mode' => array(
				'title' 		=> __( 'Mode', 'woocommerce-freightcenter' ),
				'type' 			=> 'select',
				'label' 		=> __( 'Is this a live or test site?', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'live' 		=> __( 'Live', 'woocommerce-freightcenter' ),
					'sandbox' 	=> __( 'Sandbox', 'woocommerce-freightcenter' ),
				),
			),

			'username' => array(
				'title' 		=> __( 'Username', 'woocommerce-freightcenter' ),
				'type' 			=> 'text',
				'description'	=> __( 'FreightCenter username', 'woocommerce-freightcenter' ),
				'default'		=> '',
				'desc_tip'		=> true,
				'placeholder'	=> '',

			),

			'password' => array(
				'title' 		=> __( 'Password', 'woocommerce-freightcenter' ),
				'type' 			=> 'password',
				'description'	=> __( 'FreightCenter password', 'woocommerce-freightcenter' ),
				'default'		=> '',
				'desc_tip'		=> true,
				'placeholder'	=> '',

			),

			'availability' => array(
				'title'			=> __( 'Method availability', 'woocommerce-freightcenter' ),
				'type'			=> 'select',
				'default'		=> 'all',
				'class'			=> 'availability',
				'options'		=> array(
					'all'		=> __( 'All allowed countries', 'woocommerce-freightcenter' ),
					'specific'	=> __( 'Specific Countries', 'woocommerce-freightcenter' )
				)
			),

			'countries' => array(
				'title'			=> __( 'Specific countries', 'woocommerce-freightcenter' ),
				'type'			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default'		=> '',
				'options'		=> array(
					'US' => __( 'United States (US)', 'woocommerce' ),
					'CA' => __( 'Canada', 'woocommerce' ),
				),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select some countries', 'woocommerce-freightcenter' )
				)
			),

			'enable_discount' => array(
				'title' 		=> __( 'Enable discount', 'woocommerce-freightcenter' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this option if you want to give a discount', 'woocommerce-freightcenter' ),
				'default' 		=> 'no'
			),

			'discount_threshold' => array(
				'title' 		=> __( 'Discount threshold', 'woocommerce-freightcenter' ),
				'type' 			=> 'text',
				'description'	=> __( 'Discount threshold.', 'woocommerce-freightcenter' ),
				'default'		=> '',
				'desc_tip'		=> true,
				'placeholder'	=> '',

			),

			'discount_type' => array(
				'title' 		=> __( 'Discount mode', 'woocommerce-freightcenter' ),
				'type' 			=> 'select',
				'label' 		=> __( '', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'amount'	=> __( 'Amount $', 'woocommerce-freightcenter' ),
					'percent' 	=> __( 'Percentage %', 'woocommerce-freightcenter' ),
				),
			),

			'discount_amount' => array(
				'title' 		=> __( 'Discount amount', 'woocommerce-freightcenter' ),
				'type' 			=> 'text',
				'description'	=> __( 'Set the discount amount', 'woocommerce-freightcenter' ),
				'default'		=> '',
				'desc_tip'		=> true,
				'placeholder'	=> '',

			),

			'destination_location_type' => array(
				'title'			=> __( 'Destination location type', 'woocommerce-freightcenter' ),
				'type'			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default'		=> '',
				'options'		=> array(
					'Residential' 						=> __( 'Residential', 'woocommerce-freightcenter' ),
					'BusinessWithDockOrForklift' 		=> __( 'Business with dock or forklift', 'woocommerce-freightcenter' ),
					'BusinessWithoutDockOrForklift' 	=> __( 'Business without dock or forklift', 'woocommerce-freightcenter' ),
					'Terminal' 							=> __( 'Terminal', 'woocommerce-freightcenter' ),
					'ConstructionSite' 					=> __( 'Construction site', 'woocommerce-freightcenter' ),
					'ConventionCenterOrTradeshow'		=> __( 'Convention center or Tradeshow', 'woocommerce-freightcenter' ),
				),
			),

		);

	}


	/**
	 * Calculate costs.
	 *
	 * Calculate the shipping costs and discounts.
	 *
	 * @since 1.0.0
	 *
	 * @param	double 	$costs 		Current un-edited shipping costs.
	 * @param	array	$package	Shipping package to calculate the costs for.
	 * @return	double				Modified shipping costs with possible discounts.
	 */
	public function calculate_shipping_costs( $costs, $package ) {

		$discount_amount 	= $this->get_option( 'discount_amount' );
		$enable_discount	= $this->get_option( 'enable_discount' );
		$discount_minimum 	= $this->get_option( 'discount_threshold' );

		if ( ! empty( $discount_amount ) && 'yes' == $enable_discount ) :

			// Return is threshold is not met
			if ( ! empty( $discount_minimum ) && WC()->cart->cart_contents_total <= $discount_minimum ) :
				return $costs;
			endif;

			// Get the count of freight shipping to calculate the discount correctly
			$count = 0;
			foreach ( WC()->cart->get_shipping_packages() as $package ) :
				if ( $is_freight = get_post_meta( $package['contents'][0]['product_id'], '_ship_via_freight', true ) ) :
					$count++;
				endif;
			endforeach;


			if ( 'amount' == $this->get_option( 'discount_type' ) ) :
				$costs -= $discount_amount / $count;
			elseif ( 'percent' == $this->get_option( 'discount_type' ) ) :
				$costs -= ( $costs / 100 ) * $discount_amount;
			endif;

		endif;

		return $costs;

	}


	/**
	 * API accessorials.
	 *
	 * Get the accessorials for the API call. These are based on what
	 * options are checked at the checkout and in the warehouse post type.
	 *
	 * Positioned in this file to use in shipping cache
	 *
	 * @since 1.0.0
	 *
	 * @param    array $post_data Array of data being send over $_POST.
	 * @param    int $warehouse_id Warehouse ID to get the accessorials for.
	 *
	 * @return array
	 */
	public function get_accessorials( $post_data, $warehouse_id ) {

		$accessorials = array();

		$location_options = isset( $post_data['_location_options'] ) ? $post_data['_location_options'] : array();

		// Call before delivery
		if ( isset( $location_options['_call_before_delivery'] ) && 1 == $location_options['_call_before_delivery'] ) :
			$accessorials[] = 'DEST_NOTIFY';
		endif;

		// Destination lift gate
		if ( isset( $location_options['_destination_lift_gate'] ) && 1 == $location_options['_destination_lift_gate'] ) :
			$accessorials[] = 'DEST_LIFT_GATE';
		endif;

		// Threshold delivery
		if ( isset( $location_options['_threshold_delivery'] ) && 1 == $location_options['_threshold_delivery'] ) :
			$accessorials[] = 'DEST_INSIDE_DEL';
		endif;

		// Limited access
		if ( isset( $location_options['_limited_access_delivery'] ) && 1 == $location_options['_limited_access_delivery'] ) :
			$accessorials[] = 'DEST_LIMITED_DEL';
		endif;


		/********************
		 * Origin
		 *******************/
		$location_options = get_post_meta( $warehouse_id, '_location_options', true );
		// Pickup lift gate
		if ( is_array( $location_options ) && in_array( 'include_lift_gate', $location_options ) ) :
			$accessorials[] = 'ORIGIN_LIFT_GATE';
		endif;

		// Pickup Limited access
		if ( is_array( $location_options ) && in_array( 'limited_access', $location_options ) ) :
			$accessorials[] = 'ORIGIN_LIMITED_PU';
		endif;

		// Pickup Inside pickup
		if ( is_array( $location_options ) && in_array( 'inside_pickup', $location_options ) ) :
			$accessorials[] = 'ORIGIN_INSIDE_PU';
		endif;

		return $accessorials;

	}


	/**
	 * Calculate shipping.
	 *
	 * Calculate shipping costs.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $package List containing info about the package.
	 */
	public function calculate_shipping( $package ) {

		// Stop when cart does not need freight shipping
		if ( false == WCFC()->shipping->needs_freight_shipping() ) :
			return $package;
		endif;

		// Stop if required $_POST data is not set
		if ( ! isset( $_POST['post_data'] ) ) :
			return $package;
		endif;

		// Get warehouse data
		$warehouse_id 		= get_post_meta( $package['contents'][0]['product_id'], '_origin_warehouse', true );
		$warehouse_postcode	= get_post_meta( $warehouse_id, '_postcode', true );
		$location_type		= get_post_meta( $warehouse_id, '_location_type', true );
		$post_data 			= wp_parse_args( $_POST['post_data'] );

		// Stop if no warehouse origin postcode
		if ( '' == $warehouse_postcode ) {
			return $package;
		}

		if ( '' == $package['destination']['postcode'] ) {
			return $package;
		}

		$args = array(
			'origin_postcode' 			=> $warehouse_postcode,
			'origin_location_type' 		=> $location_type,
			'destination_postcode' 		=> $package['destination']['postcode'],
			'destination_location_type' => $post_data['_location_type'],
			'items' 					=> WCFC()->api->prep_items_rate_request( $package['contents'] ),
			'accessorials' 				=> $this->get_accessorials( $post_data, $warehouse_id ),
		);
		// Get rates via FC API
		$freight_center_rates = WCFC()->api->get_rates( $args, $post_data );

		// Only get the cheapest one
		$freight_center_rates = $this->get_cheapest_rate( $freight_center_rates );

		// Stop if there are not FC rates returned
		if ( empty( $freight_center_rates ) ) :
			return;
		endif;

		// Loop if its an array
		if ( is_array( $freight_center_rates ) ) :

			// Add each rate to the checkout
			foreach ( $freight_center_rates as $fc_rate ) :

				$rate = array(
					'id'       => $fc_rate->ServiceCode,
					'label'    => $fc_rate->CarrierName . ' - ' . $fc_rate->RateId,
					'cost'     => $this->calculate_shipping_costs( $fc_rate->TotalCharge, $package ),
					'calc_tax' => true,
				);

				// Register the rate
				$this->add_rate( $rate );

			endforeach;

		// Don't loop if its not an array
		else :

			$rate = array(
				'id'       => $freight_center_rates->ServiceCode,
				'label'    => 'Freight Charges', /* $freight_center_rates->CarrierName . ' - ' . $freight_center_rates->RateId */
				'cost'     => $this->calculate_shipping_costs( $freight_center_rates->TotalCharge, $package ),
				'calc_tax' => true,
			);

			// Register the rate
			$this->add_rate( $rate );

		endif;

		// Save all rates in the session
		WC()->session->set( 'freightcenter_charge_' . $warehouse_id, $rate['cost'] );
		WC()->session->set( 'freightcenter_all_rates_' . $warehouse_id, $freight_center_rates );

	}


	/**
	 * Cheapest rate.
	 *
	 * Get the rate with the lowest total charge.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array		$rates	List of rates from FreightCenter.
	 * @return 	array|mixed			Array of the cheapest shipping rate.
	 */
	public function get_cheapest_rate( $rates ) {

		if ( ! is_array( $rates ) || ! isset( $rates[0] ) ) :
			return $rates;
		endif;

		$cheapest = reset( $rates );

		foreach ( $rates as $rate ) :

			if ( $rate->TotalCharge < $cheapest->TotalCharge ) :
				$cheapest = $rate;
			endif;

		endforeach;

		return $cheapest;

	}


	/**
	 * Hide sipping methods.
	 *
	 * Hide shipping methods that don't apply to this package.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $methods	List of shipping methods available.
	 * @param 	array $package	List with package details.
	 * @return 	array			Modified list of available shipping methods.
	 */
	public function hide_invalid_shipping_methods_for_packages( $methods, $package ) {

		$new_methods = array();

		// Validate freight shipping package or not
		foreach ( $package['contents'] as $key => $product ) :

			$ship_via_freight = get_post_meta( $product['product_id'], '_ship_via_freight', true );

			// If it is freight, unset all other shipping, but freight center's.
			if ( 'yes' == $ship_via_freight ) :

				$shipping_methods 	= WC()->shipping()->load_shipping_methods( $package );
				$shipping_methods 	= array( 'freight_center' => $shipping_methods['freight_center'] );
				$freight_methods 	= array();

				foreach ( $shipping_methods as $shipping_method ) {

					if ( $shipping_method->is_available( $package ) && ( empty( $package['ship_via'] ) || in_array( $shipping_method->id, $package['ship_via'] ) ) ) {

						// Reset Rates
						$shipping_method->rates = array();

						// Calculate Shipping for package
						$shipping_method->calculate_shipping( $package );

						// Place rates in package array
						if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) )
							foreach ( $shipping_method->rates as $rate )
								$freight_methods[ $rate->id ] = $rate;
					}
				}

				return $freight_methods;

			elseif ( 'no' == $ship_via_freight ) :

				$shipping_methods = WC()->shipping()->load_shipping_methods( $package );
				unset( $shipping_methods['freight_center'] );

				foreach ( $shipping_methods as $shipping_method ) {

					if ( $shipping_method->is_available( $package ) && ( empty( $package['ship_via'] ) || in_array( $shipping_method->id, $package['ship_via'] ) ) ) {

						// Reset Rates
						$shipping_method->rates = array();

						// Calculate Shipping for package
						$shipping_method->calculate_shipping( $package );

						// Place rates in package array
						if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) )
							foreach ( $shipping_method->rates as $rate )
								$new_methods[ $rate->id ] = $rate;
					}
				}

				return $new_methods;

			endif;

		endforeach;

		return $methods;

	}


}
