<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class WCFC_Order
 *
 *	Order class.
 *
 *	@class		WCFC_Order
 *	@version	1.0.0
 *	@author		FreightCenter
 */
class WCFC_Order {


	/**
	 * Construct.
	 *
	 * Initialize the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add order meta
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_order_meta' ), 10, 2 );

		// Add shop order meta box
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_meta_boxes' ) );

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Check for bookrate action
		add_action( 'init', array( $this, 'check_bookrate_action' ) );

		// Check for cancellation action
		add_action( 'init', array( $this, 'check_cancel_shipment_action' ) );

		// Admin error notice
		add_action( 'admin_notices', array( $this, 'bookrate_notices' ) );

		// Check if BOL is ready to download
		add_action( 'admin_init', array( $this, 'check_is_bol_ready_for_download' ) );

		// Add data to thank-you page
		add_action( 'woocommerce_order_details_after_customer_details', array( $this, 'thank_you_page' ), 1, 10 );

	}


	/**
	 * Enqueue scripts.
	 *
	 * Enqueue jQuery datepicker script.
	 *
	 * @since 1.0.0
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'jquery-datepicker' );
	}


	/**
	 * Add order meta.
	 *
	 * Add extra meta to the order.
	 *
	 * @since 1.0.0
	 *
	 * @param int/string	$order_id 	ID of the order to add the meta to.
	 * @param array			$posted 	List of variables posted through $_POST.
	 */
	public function add_order_meta( $order_id, $posted ) {

		$rates = $this->get_session_order_rates( $order_id );
		$order = new WC_Order( $order_id );

		// Set all products, rates, chosen rate per warehouses in variable
		update_post_meta( $order_id, '_warehouses_order', $this->get_session_warehouses_order( $order_id ) );

		// Add location type value
		if ( isset( $_POST['_location_type'] ) ) :
			update_post_meta( $order_id, '_location_type', wc_clean( $_POST['_location_type'] ) );
		endif;


		// Add 'call before delivery' value
		if ( isset( $_POST['_location_options']['_call_before_delivery'] ) ) :
			update_post_meta( $order_id, '_call_before_delivery', 'yes' );
		else :
			update_post_meta( $order_id, '_call_before_delivery', 'no' );
		endif;


		// Add 'Threshold delivery' value
		if ( isset( $_POST['_location_options']['_threshold_delivery'] ) ) :
			update_post_meta( $order_id, '_threshold_delivery', 'yes' );
		else :
			update_post_meta( $order_id, '_threshold_delivery', 'no' );
		endif;


		// Add 'Limited access delivery' value
		if ( isset( $_POST['_location_options']['_limited_access_delivery'] ) ) :
			update_post_meta( $order_id, '_limited_access_delivery', 'yes' );
		else :
			update_post_meta( $order_id, '_limited_access_delivery', 'no' );
		endif;


		// Add 'Destination lift gate' value
		if ( isset( $_POST['_location_options']['_destination_lift_gate'] ) ) :
			update_post_meta( $order_id, '_destination_lift_gate', 'yes' );
		else :
			update_post_meta( $order_id, '_destination_lift_gate', 'no' );
		endif;

	}


	/**
	 * Get order rates.
	 *
	 * Get only the rates used by the order from all the rates.
	 * Can only be used during add_order_meta hook.
	 *
	 * @since 1.0.0
	 *
	 * @see $this->add_order_meta()
	 * Unused method?
	 *
	 * @param 	int/string $order_id	ID for the order to get the rates for.
	 * @return 	array 					List of rates related to the order.
	 */
	public function get_session_order_rates( $order_id ) {

		$rates 	= array();
		$warehouses = array();
		$order 	= new WC_Order( $order_id );

		// Loop through order items to get warehouses that are being shipping from
		foreach ( $order->get_items() as $item ) :

			$origin_warehouse = get_post_meta( $item['product_id'], '_origin_warehouse', true );
		    if ( ! empty( $origin_warehouse ) ) :
		    	$warehouses[ $origin_warehouse ] = $origin_warehouse;
		    endif;

		endforeach;


		// Loop through warehouses that are being shipped from
		foreach ( $warehouses as $warehouse_id ) :

			// Get rates by warehouse id
			$fc_rates = WC()->session->get( 'freightcenter_all_rates_' . $warehouse_id );

			if ( empty( $fc_rates ) ) :
				continue;
			endif;

			// Loop through rates to get the chosen rate ids
			foreach ( $fc_rates as $key => $rate ) :

				// Check if shipping method equals carrier code
				if ( isset( $rate->ServiceCode ) && in_array( $rate->ServiceCode, $_POST['shipping_method'] ) ) :
					$rates[ $rate->RateId ] = $rate;
				endif;

			endforeach;

		endforeach;

		return $rates;

	}


	/**
	 * Get Warehouse Order.
	 *
	 * Get a list of products ordered, all the rates and
	 * the chosen rate by warehouse for the entire order.
	 * Can only be used during add_order_meta hook.
	 *
	 * [Warehouse id]
	 *	|_ [products ordered]
	 *	|_ [All rates]
	 *	|_ [Chosen rate]
	 *	|_ [status]
	 *	|_ [origin]
	 *	|_ [destination]
	 *
	 * @since 1.0.0
	 *
	 * @param 	int/string $order_id 	ID or the order.
	 * @return 	array					List with all the values.
	 */
	public function get_session_warehouses_order( $order_id ) {

		$warehouses_order 	= array();
		$order 				= new WC_Order( $order_id );

		// Loop through order items to get warehouses that are being shipping from
		foreach ( $order->get_items() as $key => $item ) :

			$warehouse_id = get_post_meta( $item['product_id'], '_origin_warehouse', true );
		    if ( ! empty( $warehouse_id ) ) :
				$warehouses_order[ $warehouse_id ]['products_ordered'][ $key ] = $item;
		    endif;

		endforeach;


		// Set shipping methods in variable
		$shipping_methods = $_POST['shipping_method'];


		// Loop through warehouses that are being shipped from
		foreach ( $warehouses_order as $warehouse_id => $products ) :

			// Get rates by warehouse id - set in shipping method class
			$fc_rates = WC()->session->get( 'freightcenter_all_rates_' . $warehouse_id );
			$warehouses_order[ $warehouse_id ]['all_rates'] = $fc_rates;
			$warehouses_order[ $warehouse_id ]['charge'] 	= WC()->session->get( 'freightcenter_charge_' . $warehouse_id );

			if ( empty( $fc_rates ) ) :
				continue;
			endif;


			// Loop through shipping methods to get the shipping methods
			foreach ( $shipping_methods as $shipping_key => $shipping_method ) :

				// Loop through rates to get the chosen rate ids
				if ( ! is_object( $fc_rates ) ) : // $fc_rates is array of rates

					foreach ( $fc_rates as $key => $rate ) :

						// Check if shipping method equals carrier code
						if ( $rate->ServiceCode == $shipping_method && ! isset( $warehouses_order[ $warehouse_id ]['chosen_rate'] ) ) :

							// Unset shipping method, so when looping twice it won't get the same
							unset( $shipping_methods[ $shipping_key ] );
							$warehouses_order[ $warehouse_id ]['chosen_rate'] = $rate;

						endif;

					endforeach;


				else : // $fc_rates is no array (single rate)

					// Check if shipping method equals carrier code
					if ( $fc_rates->ServiceCode == $shipping_method && ! isset( $warehouses_order[ $warehouse_id ]['chosen_rate'] ) ) :

						// Unset shipping method, so when looping twice it won't get the same
						unset( $shipping_methods[ $shipping_key ] );
						$warehouses_order[ $warehouse_id ]['chosen_rate'] = $fc_rates;

					endif;

				endif;

			endforeach;


			// Set status
			$warehouses_order[ $warehouse_id ]['status'] = 'not_booked';

			// Origin
			$warehouses_order[ $warehouse_id ]['origin'] = get_post_meta( $warehouse_id );

			// Destination
			$warehouses_order[ $warehouse_id ]['destination'] = $order->get_shipping_address();

		endforeach;

		// Return value
		return $warehouses_order;

	}


	/**
	 * Meta boxes.
	 *
	 * Add meta boxes to the shop order post type.
	 * Meta boxes contain info and admin options for FC.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes( $post_id ) {

		$order = wc_get_order( $post_id );

		// Only show meta boxes for Freight orders
		foreach ( $order->get_items() as $item ) :
			if ( 'yes' == get_post_meta( $item['product_id'], '_ship_via_freight', true ) ) :

				// Add information meta box
				add_meta_box( 'fc_data', __( 'FreightCenter info', 'woocommerce-freightcenter' ), array( $this, 'fc_data_meta_box' ), 'shop_order', 'normal' );
				add_meta_box( 'fc_admin', __( 'Freight Admin', 'woocommerce-freightcenter' ), array( $this, 'fc_admin_meta_box' ), 'shop_order', 'normal' );
				return;

			endif;
		endforeach;

	}


	/**
	 * Output meta box.
	 *
	 * Output the info meta box contents.
	 *
	 * @since 1.0.0
	 */
	public function fc_data_meta_box() {

		require_once plugin_dir_path( __FILE__ ) . 'views/meta-box-shop-order-freightcenter-data.php';

	}


	/**
	 * Output meta box.
	 *
	 * Get only the rates used by the order from all the rates.
	 *
	 * @since 1.0.0
	 */
	public function fc_admin_meta_box() {

		require_once plugin_dir_path( __FILE__ ) . 'views/meta-box-shop-order-freight-admin.php';

	}


	/**
	 * FreightCenter actions.
	 *
	 * Check for FreightCenter options (such as
	 * 'book rate' action).
	 *
	 * @since 1.0.0
	 */
	public function check_bookrate_action() {

		// Check if this is the right action
		if ( ! isset( $_POST['action'] ) || 'freightcenter_bookrate' != $_POST['action'] ) :
			return;
		endif;

		// Check rate_ids
		if ( ! isset( $_POST['rate_ids'] ) || ! is_array( $_POST['rate_ids'] ) ) :
			return;
		endif;

		// Check nonce
		if ( ! wp_verify_nonce( $_POST['bookrate_nonce'], 'bookrate' ) ) :
			return;
		endif;

		// Set Rate_id
		$rate_ids 	= $_POST['rate_ids'];
		$order 		= new WC_Order( $_POST['post_id'] );

		// Yikes! Have to loop through all IDS
		foreach ( $rate_ids as $rate_id ) :

			// Check if warehouse is set
			if ( ! isset( $_POST[ $rate_id ]['warehouse_id'] ) ) :
				$error = true;
				continue;
			endif;

			// Check if terms are accepted
			if ( ! isset( $_POST[ $rate_id ]['term_conditions'] ) ) :
				$error = true;
				continue;
			endif;

			if( empty( $_POST[ $rate_id ]['pickup_date'] ) ) :
				$error = true;
				continue;
			endif;

			// Create API call to BookRate
			$warehouse_id 	= $_POST[ $rate_id ]['warehouse_id'];

			$args = array(
				'order_id' 				=> $order->id,
				'RateId' 				=> $rate_id,
				'PickupDateFrom' 		=> date( 'c', strtotime( $_POST[ $rate_id ]['pickup_date'] . ' 8:00 AM' ) ),
				'PickupDateTo' 			=> date( 'c', strtotime( $_POST[ $rate_id ]['pickup_date'] . ' 5:00 PM' ) ),
				'PurchaseOrderNumber' 	=> $order->id,
				'Origin' 				=> array( 		// Pickup location (warehouse)
					'Company' 				=> get_post_meta( $warehouse_id, '_company_name', true ),
					'Department' 			=> '',
					'FirstName' 			=> get_post_meta( $warehouse_id, '_first_name', true ),
					'LastName' 				=> get_post_meta( $warehouse_id, '_last_name', true ),
					'EmailAddress' 			=> get_post_meta( $warehouse_id, '_email', true ),
					'PhoneNumber' 			=> get_post_meta( $warehouse_id, '_phone', true ),
					'PhoneExt' 				=> '',
					'AlternatePhone' 		=> '',
					'AlternatePhoneExt' 	=> '',
					'FaxNumber' 			=> '',
					'FaxExt' 				=> '',
					'StreetAddress' 		=> get_post_meta( $warehouse_id, '_address', true ),
					'AptSuite' 				=> '',
					'City' 					=> get_post_meta( $warehouse_id, '_city', true ),
					'State' 				=> get_post_meta( $warehouse_id, '_state', true ),
					'PostalCode' 			=> get_post_meta( $warehouse_id, '_postcode', true ),
					'Country' 				=> get_post_meta( $warehouse_id, '_country', true ),
					'LocationComments'		=> '',
					'Contact'				=> '',
				),
			);
			$response = WCFC()->api->book_rate( $args );

			// Book rate was successful
			if ( isset( $response->BookRateResult->TransactionResponse->Type ) && 'SUCCESS' == $response->BookRateResult->TransactionResponse->Type ) :

				// Add informational order notes
				$order->add_order_note( $response->BookRateResult->TransactionResponse->Message );
				$order->add_order_note( 'Shipment ID: ' . $response->BookRateResult->ShipmentId . PHP_EOL . 'PreProNumber: ' . $response->BookRateResult->PreProNumber );

				// Update status
				$warehouse_order = get_post_meta( $order->id, '_warehouses_order', true );
				$warehouse_order[ $warehouse_id ]['status'] = 'rate_booked';

				// Add Shipment ID/PreProNumber
				$warehouse_order[ $warehouse_id ]['tracking']['shipment_id'] = $response->BookRateResult->ShipmentId;
				$warehouse_order[ $warehouse_id ]['tracking']['prepronumber'] = $response->BookRateResult->PreProNumber;

				// Save
				update_post_meta( $order->id, '_warehouses_order', $warehouse_order );

				$query_args['fc_error'] 	= 3;
				$query_args['fc_message'] 	= urlencode( 'Shipment successfully booked' );

			else :

				if ( isset( $response->BookRateResult->TransactionResponse->Type ) ) :
					$message = urlencode( $response->BookRateResult->TransactionResponse->Type );
				else :
					$message = __( 'The FreightCenter API is currentcly unreachable. Please check the logs and try again later.', 'woocommmerce-freightcenter' );
				endif;

				// Booking rate failed
				$query_args['fc_error'] 	= 2;
				$query_args['fc_message'] 	= $message;

			endif;

		endforeach;

		$query_args['post'] = $order->id;
		$query_args['action'] = 'edit';

		// Set error
		if ( isset( $error ) && 1 == $error ) :
			$query_args['fc_error'] = 1;
		endif;

		// Redirect to original order page
		wp_redirect( add_query_arg( $query_args, admin_url( 'post.php' ) ) );
		exit;

	}


	public function check_cancel_shipment_action() {

		if ( ! isset( $_GET['action'] ) || 'cancel_shipment' != $_GET['action'] ) :
			return;
		endif;

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'] ) ) :
			return;
		endif;

		if ( ! isset( $_GET['order_id'] ) || ! isset( $_GET['warehouse_id'] ) ) :
			return;
		endif;

		$order_id 		= $_GET['order_id'];
		$order			= new WC_Order( $order_id );
		$shipment_id 	= $_GET['shipment_id'];
		$warehouse_id 	= $_GET['warehouse_id'];

		$response = WCFC()->api->cancel_shipment( $order_id, $shipment_id );

		// Cancel was successful
		if ( isset( $response->CancelShipmentResult->TransactionResponse->Type ) && 'SUCCESS' == $response->CancelShipmentResult->TransactionResponse->Type ) :

			// Set order message
			$order->add_order_note( sprintf( __( 'Shipment #%s successfully cancelled.', 'woocommerce-freightcenter' ), $shipment_id ) );

			// Update status
			$warehouse_order = get_post_meta( $order->id, '_warehouses_order', true );
			$warehouse_order[ $warehouse_id ]['status'] = 'cancelled';
			update_post_meta( $order->id, '_warehouses_order', $warehouse_order );

			$query_args['fc_error'] 	= 2;
			$query_args['fc_message'] 	= urlencode( 'Shipment cancelled' );

		elseif ( isset( $response->CancelShipmentResult->TransactionResponse->Type ) && 'WARNING' == $response->CancelShipmentResult->TransactionResponse->Type ) :

			$query_args['fc_error'] 	= 2;
			$query_args['fc_message'] 	= urlencode( $response->CancelShipmentResult->TransactionResponse->Message );

		else :

			// Cancellation failed
			$query_args['fc_error'] 	= 2;
			$query_args['fc_message'] 	= urlencode( 'Shipment cancellation failed' );

			if ( isset( $response->CancelShipmentResult->TransactionResponse->Message ) ) :
				 $query_args['fc_message'] .= urlencode( ': ' . $response->CancelShipmentResult->TransactionResponse->Message );
			endif;

		endif;

		$query_args['post'] = $order_id;
		$query_args['action'] = 'edit';

		// Redirect
		wp_redirect( add_query_arg( $query_args, admin_url( 'post.php' ) ) );
		exit;

	}


	/**
	 * Failed notice.
	 *
	 * Add a failed notice when a API call fails.
	 *
	 * @since 1.0.0
	 */
	public function bookrate_notices() {

		if ( isset( $_GET['fc_error'] ) && 1 == $_GET['fc_error'] ) :
			?><div class="error">
		        <p><?php _e( 'The rate couldn\'t be booked. Please check if you agreed to the terms and selected a pickup date.', 'woocommerce-freightcenter' ); ?></p>
			</div><?php
		elseif ( isset( $_GET['fc_error'] ) && 2 == $_GET['fc_error'] && isset( $_GET['fc_message'] ) ) :
			?><div class="error">
		        <p><?php echo str_replace( '\\', '', urldecode( $_GET['fc_message'] ) ); ?></p>
			</div><?php
		elseif ( isset( $_GET['fc_error'] ) && 3 == $_GET['fc_error'] && isset( $_GET['fc_message'] ) ) :
			?><div class="updated">
		        <p><?php echo str_replace( '\\', '', urldecode( $_GET['fc_message'] ) ); ?></p>
			</div><?php
		endif;

	}


	/**
	 * Check for BOL.
	 *
	 * Check if the BOL is ready for download at order page load.
	 *
	 * @since 1.0.0
	 */
	public function check_is_bol_ready_for_download() {

		// Bail if not an edit screen
		if ( ! isset( $_GET['post'] ) || ! isset( $_GET['action'] ) || 'edit' != $_GET['action'] ) :
			return;
		endif;

		$post = get_post( $_GET['post'] );

		// Bail if edit screen is not for a order
		if ( ! isset( $post->ID ) || 'shop_order' != $post->post_type ) :
			return;
		endif;

		$warehouse_order = get_post_meta( $post->ID, '_warehouses_order', true );

		// Loop through warehouses
		foreach ( $warehouse_order as $warehouse_id => $values ) :

			if ( ! isset( $values['tracking']['shipment_id'] ) ) :
				continue;
			endif;

			$response 	= WCFC()->api->bol_ready( $values['tracking']['shipment_id'] );
			$ready		= $response->IsBolReadyForDownloadResult;
			$warehouse_order[ $warehouse_id ]['bol_ready'] = $ready;

		endforeach;

		update_post_meta( $post->ID, '_warehouses_order', $warehouse_order );

	}


	/**
	 * Thank you.
	 *
	 * Add the accessoirials to the thank you page.
	 *
	 * @since 1.0.0
	 *
	 * @param wc_order $order WooCommerce Order object.
	 */
	public function thank_you_page( $order ) {

		?></dl><h3><?php _e( 'Shipping details', 'woocommerce-freightcenter' ); ?></h3><?php
		$location_type 		= get_post_meta( $order->id, '_location_type', true );
		$call_delivery		= get_post_meta( $order->id, '_call_before_delivery', true );
		$threshold_delivery	= get_post_meta( $order->id, '_threshold_delivery', true );
		$limited_access 	= get_post_meta( $order->id, '_limited_access_delivery', true );
		$lift_gate	 		= get_post_meta( $order->id, '_destination_lift_gate', true );

		?><style>
			#fc_data .inside span { font-weight: bold; float: left; width: 170px; }
			#fc_data .inside p.option-group { border-bottom: 1px dotted #ECECEC; padding: 10px 0px; margin: 0; }
		</style>

		<p class='option-group'><?php
			if ( 'yes' == $call_delivery ) :
				?><span><?php _e( 'Call before delivery', 'woocommerce-freightcenter' ); ?></span><br/><?php
			endif;
			if ( 'yes' == $threshold_delivery ) :
				?><span><?php _e( 'Threshold delivery', 'woocommerce-freightcenter' ); ?></span><br/><?php
			endif;
			if ( 'yes' == $limited_access ) :
				?><span><?php _e( 'Limited access delivery', 'woocommerce-freightcenter' ); ?></span><br/><?php
			endif;
			if ( 'yes' == $lift_gate ) :
				?><span><?php _e( 'Lift Gate at Delivery Point', 'woocommerce-freightcenter' ); ?></span><br/><?php
			endif;
		?></p><dl><?php

	}


}
