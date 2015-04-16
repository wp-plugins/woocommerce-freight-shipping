<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( class_exists( 'WCFC_Api' ) ) return; // Exit if class already exists


/**
 *	Class WCFC_Api
 *
 *	Main WCFC API Class
 *
 *	@class		WCFC_Api
 *	@version	1.0.0
 *	@author		FreightCenter
 */
class WCFC_Api {

	private $username;

	private $password;

	private $licensekey = '82621705-616A-424F-8725-F9F8C199BE7F';

	private $mode = 'live';

	private $url = 'http://api.freightcenter.com';


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->settings 	= get_option( 'woocommerce_freight_center_settings' );
		$this->username		= $this->settings['username'];
		$this->password		= $this->settings['password'];

		$wcfc_settings = get_option( 'woocommerce_freight_center_settings', array() );

		if ( isset( $wcfc_settings['mode'] ) && 'sandbox' == $wcfc_settings['mode'] ) :
			$this->mode = 'sandbox';
			$this->url	= 'http://apistag.freightcenter.com';
		endif;

	}


	/**
	 * Active Carriers.
	 *
	 * Get a list of all the active carriers via the API.
	 *
	 * @since 1.0.0
	 */
	public function get_active_carriers() {
return array();
		// Stop if credentials are incorrect
		if ( empty ( $this->username ) || empty( $this->password ) || empty( $this->licensekey ) ) :
			return array();
		endif;

		// Check if there is a transient available
		if ( false !== $response = get_transient( 'freightcenter_carrier_list' ) && ! empty( $response ) ) :
			return $response;
		endif;

		// Make Soap Client
		try {

			$client = new SoapClient( $this->url . '/v04/carriers.asmx?wsdl', array(
				'trace' 			=> 1,
				'exceptions' 		=> 1,
				'encoding' 			=> 'UTF-8',
			));

		} catch ( Exception $e ) {
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed init SoapClient for get_active_carriers(). Message: %s', $e->getMessage() ) );
			return array();
		}

		// Do request
		try {

			$request = array(
				'Username' 		=> $this->username,
				'Password' 		=> $this->password,
				'LicenseKey' 	=> $this->licensekey,
			);

			// Log request
			WCFC()->log( sprintf( 'GetActiveCarriers REQUEST:\n %s', print_r( $request, true ) ) );

			$response = $client->GetActiveCarriers( array( 'request' => $request ) );
			WCFC()->log( sprintf( 'GetActiveCarriers REQUEST XML:\n %s', $client->__getLastRequest() ) );
			WCFC()->log( sprintf( 'GetActiveCarriers RESPONSE XML:\n %s', $client->__getLastResponse() ) );

			// Log response
			WCFC()->log( sprintf( 'GetActiveCarriers RESPONSE: \n %s', print_r( $response, true ) ) );

			$response = $response->GetActiveCarriersResult->Carriers->Carrier;

		} catch( Exception $e ) {
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed API call on get_active_carriers(). Message: %s', $e->getMessage() ) );
			return array();
		}

		// Set transient to save loading time. Stores for 7 days
		set_transient( 'freightcenter_carrier_list', $response, (7 * 60 * 60 * 24) );

		return $response;

	}


	/**
	 * Item preparation.
	 *
	 * Get all the items, and put them in a list as preparation
	 * for the get_rates() API call.
	 *
	 * @since 1.0.0
	 *
	 * @param array $cart_items
	 * @return array List of items that are in the cart.
	 */
	public function prep_items_rate_request( $cart_items ) {

		$items = array();

		foreach ( $cart_items as $item ) :

			$product = wc_get_product( $item['product_id'] );

			$length = wc_get_dimension( get_post_meta( $product->id, '_length', true ), 'in' );
			$width 	= wc_get_dimension( get_post_meta( $product->id, '_width', true ), 'in' );
			$height	= wc_get_dimension( get_post_meta( $product->id, '_height', true ), 'in' );
			$weight	= wc_get_weight( get_post_meta( $product->id, '_weight', true ), 'lbs' );
			$weight = $weight * $item['quantity'];

			$items['RatesRequest_Item'][] = array(
				'Description' 	=> $product->get_title(),	// Required
				'PackagingCode' => get_post_meta( $product->id, '_packaging_type', true ), // Required
				'Quantity' 		=> $item['quantity'],
				'LinearFeet' 	=> '',
				'FreightClass' 	=> get_post_meta( $product->id, '_freight_class', true ),
				'Nmfc' 			=> get_post_meta( $product->id, '_nmfc', true ),
				'Dimensions' 	=> array(
					'Length' 		=> $length,
					'Width' 		=> $width,
					'Height' 		=> $height,
					'UnitOfMeasure' => 'in', // IN, FT, CM, or M
				),
				'Weight' 		=> array(
					'WeightAmt' 	=> $weight,
					'UnitOfMeasure' => 'lbs', // LBS or KGS
				),
			);

		endforeach;

		return $items;

	}


	/**
	 * Get Rates.
	 *
	 * Make an API call to FreightCenter to get different shipping rates.
	 *
	 * @since 1.0.0
	 *
	 * @param   array $args         List of arguments needed, see inside method for default args.
	 * @param   array $post_data
	 * @return  array		        List of rates given responded by the API.
	 */
	public function get_rates( $args = array(), $post_data ) {

		// The default args should not be used, and are only
		// here for demo purposes. (items are the exception).
		$default_args = array(
			'origin_postcode' 			=> '',
			'origin_location_type' 		=> 'Residential',
			'destination_postcode' 		=> '',
			'destination_location_type' => 'Residential',
			'items' 					=> '', // @see $this->prep_items_rate_request()
			'carriers' 					=> '',
			'accessorials' 				=> '',
		);
		extract( $args = wp_parse_args( $args, $default_args ) );

		// Stop if there are not cart items.
		if ( empty( $items ) ) :
			return;
		endif;

		// Try to get stored FC rates
		$status_options = get_option( 'woocommerce_status_options', array() );
		$package_hash   = 'fc_shipping_' . md5( json_encode( $args ) );
		if ( false !== ( $stored_fc_rates = get_transient( $package_hash ) ) && ( empty( $status_options['shipping_debug_mode'] ) ) ) :
			return $stored_fc_rates;
		endif;


		// Make Soapclient
		try {

			$client = new SoapClient( $this->url . '/v04/rates.asmx?wsdl', array(
				'trace' 			=> 1,
				'exceptions' 		=> 1,
				'encoding' 			=> 'UTF-8',
			));

		} catch ( Exception $e ) {
			WCFC()->log( sprintf( 'Failed init of SoapClient for get_rates(). Message: %s', $e->getMessage() ) );
			return;
		}

		// Do request
		try {

			$request = array(
				'Username' 					=> $this->username,
				'Password' 					=> $this->password,
				'LicenseKey' 				=> $this->licensekey,
				'OriginPostalCode' 			=> $origin_postcode, 			// Required
				'OriginLocationType' 		=> $origin_location_type,
				'DestinationPostalCode'		=> $destination_postcode, 		// Required
				'DestinationLocationType'	=> $destination_location_type, 	// Required
				'Items' 					=> $items,
				'Accessorials' 				=> $accessorials, // List of Accessorial Codes required for the shipment. See the list of valid Accessorial Codes
			);

			// Set filters only when carriers list is correct
			if ( $carriers ) :
				$request['Filters']			= array(
					'Mode' 						=> 'LTL', // required
					'IncludeWhiteGlove' 		=> false,
					'IncludeMotorcycleRates' 	=> false,
					'CarrierFilter' 			=> array( // required
						'CarrierFilterType' => 'INCLUDE', // required
						'Carrier' 			=> array(
							'RatesRequest_Carrier' => $carriers,
						),
					),
				);
			endif;

			// Log request
			WCFC()->log( sprintf( 'GetRates REQUEST:\n %s', print_r( $request, true ) ) );

			$response = $client->GetRates( array( 'request' => $request ) );
			WCFC()->log( sprintf( 'GetRates REQUEST XML:\n %s', $client->__getLastRequest() ) );
			WCFC()->log( sprintf( 'GetRates RESPONSE XML:\n %s', $client->__getLastResponse() ) );

			// Log response
			WCFC()->log( sprintf( 'GetRates RESPONSE: \n %s', print_r( $response, true ) ) );

		} catch( Exception $e ) {
			WCFC()->log( sprintf( 'Failed API call on get_rates(). Message: %s', $e->getMessage() ) );
			return;
		}

		// Check for errors
		if ( 'ERROR' == $response->GetRatesResult->TransactionResponse->Type || 'ERROR:0' == $response->GetRatesResult->TransactionResponse->Type ) :
			wc_add_notice( $response->GetRatesResult->TransactionResponse->Message, 'notice' );
			return;
		endif;

		// Check for warnings
		if ( 'WARNING' == $response->GetRatesResult->TransactionResponse->Type ) :
			wc_add_notice( $response->GetRatesResult->TransactionResponse->Message, 'notice' );
			return;
		endif;

		// Set only the important results as response
		$response = $response->GetRatesResult->Rates->RatesResponse_CarrierRate;

		// Set transient for 1 hour
		set_transient( $package_hash, $response, 60 * 60 );

		return $response;

	}


	/**
	 * Book Rate.
	 *
	 * Send an API call to book an carrier/rate that was
	 * earlier received by the API.
	 *
	 * @see WCFC()->api->get_rates().
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $args List of arguments needed, see inside method for default args.
	 * @return 	array		List of rates given responded by the API.
	 */
	public function book_rate( $args = array() ) {

		// set timezone to NYC for FC's API
		date_default_timezone_set('America/New_York');

		// The default args should not be used, and are only
		// here for demo purposes.
		$default_args = array(
			'order_id' 				=> '', // Required
			'RateId' 				=> '', // Required
			'PickupDateFrom' 		=> date( 'c' ),
			'PickupDateTo' 			=> date( 'c' ),
			'PurchaseOrderNumber' 	=> '',
			'PickupNumber' 			=> '',
			'ProNumber' 			=> '',
			'BillOfLadingNumber' 	=> '',
			'Note' 					=> '',
			'Origin' 				=> array(
				'Company' 				=> '',
				'Department' 			=> '',
				'FirstName' 			=> '',
				'LastName' 				=> '',
				'EmailAddress' 			=> '',
				'PhoneNumber' 			=> '',
				'PhoneExt' 				=> '',
				'AlternatePhone' 		=> '',
				'AlternatePhoneExt' 	=> '',
				'FaxNumber' 			=> '',
				'FaxExt' 				=> '',
				'StreetAddress' 		=> '',
				'AptSuite' 				=> '',
				'City' 					=> '',
				'State' 				=> '',
				'PostalCode' 			=> '',
				'Country' 				=> '',
				'LocationComments'		=> '',
				'Contact'				=> '',
			),
			'Destination' 			=> '',
			'Agent' 				=> '',
		);
		extract( $args = wp_parse_args( $args, $default_args ) );

		// Check if all required variables are set
		if ( empty( $order_id ) || empty( $RateId ) ) :
			return;
		endif;

		// Set order
		$order = new WC_Order( $order_id );

		// Make Soapclient
		try {

			$client = new SoapClient( $this->url . '/v04/Rates.asmx?wsdl', array(
				'trace' 			=> 1,
				'exceptions' 		=> 1,
				'encoding' 			=> 'UTF-8',
			));

		} catch ( Exception $e ) {
			// Add order note
			$order->add_order_note( __( 'Failed to book: ' . $e->getMessage(), 'woocommerce-freightcenter' ) );
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed init of SoapClient for book_rate(). Message: %s', $e->getMessage() ) );
			return;
		}

		// Do request
		try {

			$destination_location = array(
				'Company' 			=> $order->billing_company,
				'Department' 		=> '',
				'FirstName' 		=> $order->shipping_first_name,
				'LastName' 			=> $order->shipping_last_name,
				'EmailAddress' 		=> $order->billing_email,
				'PhoneNumber' 		=> $order->billing_phone,
				'PhoneExt' 			=> '',
				'AlternatePhone' 	=> '',
				'AlternatePhoneExt' => '',
				'FaxNumber' 		=> '',
				'FaxExt' 			=> '',
				'StreetAddress' 	=> $order->shipping_address_1,
				'AptSuite' 			=> $order->shipping_address_2,
				'City' 				=> $order->shipping_city,
				'State' 			=> $order->shipping_state,
				'PostalCode' 		=> $order->shipping_postcode,
				'Country' 			=> $order->shipping_country, // US OR CA
				'LocationComments'	=> '',
				'Contact'			=> '',
			);

			$current_user = wp_get_current_user();
			$agent = array(
				'Name' 	=> $current_user ? $current_user->user_login : '',
				'Phone' => '',
				'Email' => '',
				'Fax' 	=> '',
			);

			$request = array(
				'Username' 				=> $this->username,
				'Password' 				=> $this->password,
				'LicenseKey' 			=> $this->licensekey,
				'RateId' 				=> $RateId,
				'PickupDateFrom' 		=> $PickupDateFrom,
				'PickupDateTo' 			=> $PickupDateTo,
				'PurchaseOrderNumber' 	=> '',
				'PickupNumber' 			=> '',
				'ProNumber' 			=> '',
				'BillOfLadingNumber' 	=> '',
				'Note' 					=> '',
				'Origin' 				=> (object) $args['Origin'],
				'Destination' 			=> (object) $destination_location,
				'Agent' 				=> (object) $agent,
			);

			// Log request
			WCFC()->log( sprintf( 'BookRate REQUEST:\n %s', print_r( $request, true ) ) );

			$response = $client->BookRate( array( 'request' => $request ) );
			WCFC()->log( sprintf( 'BookRate REQUEST XML:\n %s', $client->__getLastRequest() ) );
			WCFC()->log( sprintf( 'BookRate RESPONSE XML:\n %s', $client->__getLastResponse() ) );

			// Log response
			WCFC()->log( sprintf( 'BookRate RESPONSE: \n %s', print_r( $response, true ) ) );


		} catch( Exception $e ) {
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed API call on book_rate(). Message: %s', $e->getMessage() ) );
			return;
		}

		return $response;

	}


	/**
	 * Cancel shipment.
	 *
	 * Cancel a previously created shipment.
	 *
	 * @since 1.0.0
	 *
	 * @param 	int/string 	$order_id	 	ID of the order where the booking is being cancelled.
	 * @param 	int/string 	$shipment_id 	Shipment ID received by WCFC()->api->book_rate().
	 * @param 	string		$reason			Reason for the cancellation (optional).
	 * @return 	array						List of rates given responded by the API.
	 */
	public function cancel_shipment( $order_id, $shipment_id, $reason = '' ) {

		$order = new WC_Order( $order_id );

		// Make Soapclient
		try {

			$client = new SoapClient( $this->url . '/v04/Shipments.asmx?wsdl', array(
				'trace' 			=> 1,
				'exceptions' 		=> 1,
				'encoding' 			=> 'UTF-8',
			));

		} catch ( Exception $e ) {
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed init SoapClient for cancel_shipment(). Message: %s', $e->getMessage() ) );
			return;
		}

		// Do request
		try {

			$request = array(
				'Username' 		=> $this->username,
				'Password' 		=> $this->password,
				'LicenseKey' 	=> $this->licensekey,
				'ShipmentId' 	=> $shipment_id,
				'Reason' 		=> $reason,
			);

			// Log request
			WCFC()->log( sprintf( 'CancelShipment REQUEST:\n %s', print_r( $request, true ) ) );

			$response = $client->CancelShipment( array( 'request' => $request ) );
			WCFC()->log( sprintf( 'CancelShipment REQUEST XML:\n %s', $client->__getLastRequest() ) );
			WCFC()->log( sprintf( 'CancelShipment RESPONSE XML:\n %s', $client->__getLastResponse() ) );

			// Log response
			WCFC()->log( sprintf( 'CancelShipment RESPONSE: \n %s', print_r( $response, true ) ) );

		} catch( Exception $e ) {
			add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
			WCFC()->log( sprintf( 'Failed API call on cancel_shipment(). Message: %s', $e->getMessage() ) );
			return;
		}

		return $response;

	}


	/**
	 * Check for BOL.
	 *
	 * Check if the BOL is ready to download.
	 *
	 * @since 1.0.0
	 *
	 * @param 	int/string 	$shipment_id 	Shipment ID received by WCFC()->api->book_rate().
	 * @return 	array						List of rates given responded by the API.
	 */
	public function bol_ready( $shipment_id ) {

		$hash = md5( 'fc_api_bol_' . $shipment_id );
		if ( ! $response = get_transient( $hash ) ) :

			// Make Soapclient
			try {

				$client = new SoapClient( $this->url . '/v04/Shipments.asmx?wsdl', array(
					'trace' 			=> 1,
					'exceptions' 		=> 1,
					'encoding' 			=> 'UTF-8',
				));

			} catch ( Exception $e ) {
				WCFC()->log( sprintf( 'Failed init SoapClient for bol_ready(). Message: %s', $e->getMessage() ) );
				return;
			}

			// Do request
			try {

				$request = array(
					'Username' 		=> $this->username,
					'Password' 		=> $this->password,
					'LicenseKey' 	=> $this->licensekey,
				);

				// Log request
				WCFC()->log( sprintf( 'IsBolReadyForDownload REQUEST:\n %s', print_r( $request, true ) ) );

				$response = $client->IsBolReadyForDownload( array( 'request' => $request, 'ShipmentId' => $shipment_id ) );
				WCFC()->log( sprintf( 'IsBolReadyForDownload REQUEST XML:\n %s', $client->__getLastRequest() ) );
				WCFC()->log( sprintf( 'IsBolReadyForDownload RESPONSE XML:\n %s', $client->__getLastResponse() ) );

				// Log response
				WCFC()->log( sprintf( 'IsBolReadyForDownload RESPONSE: \n %s', print_r( $response, true ) ) );

			} catch( Exception $e ) {
				add_action( 'admin_notices', array( $this, 'api_call_failed_notice' ) );
				WCFC()->log( sprintf( 'Failed API call on bol_ready(). Message: %s', $e->getMessage() ) );
				return;
			}

			if ( isset( $response->IsBolReadyForDownloadResult ) && 1 == $response->IsBolReadyForDownloadResult ) :
				set_transient( $hash, $response, 60 * 60 * 12 ); // Transient for 12 hour
			endif;

		endif;

		return $response;

	}




	/**
	 * Failed notice.
	 *
	 * Add a failed notice when a API call fails.
	 *
	 * @since 1.0.0
	 */
	public function api_call_failed_notice() {

		?><div class="error">
	        <p><?php _e( 'Something went wrong with a FreightCenter call. Please check the debug logs for more information', 'woocommerce-freightcenter' ); ?></p>
		</div><?php

	}

}
