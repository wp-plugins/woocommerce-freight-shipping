<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class WCFC_Product_Data
 *
 *	Setup extra Freight Center settings tab.
 *
 *	@class       WCFC_Product_Data
 *	@version     1.0.0
 *	@author      FreightCenter
 */
class WCFC_Product_Data {


	/**
	 * Constructor.
	 *
	 * Add hooks and filters.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Add FreightCenter tab
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'product_data_tabs' ) );

		// Output FreightCenter tab
		add_action( 'woocommerce_product_data_panels', array( $this, 'freightcenter_product_data_panel' ) );

		add_action( 'woocommerce_process_product_meta_simple', array( $this, 'save_freightcenter_data' ) );
		add_action( 'woocommerce_process_product_meta_grouped', array( $this, 'save_freightcenter_data' ) );
		add_action( 'woocommerce_process_product_meta_external', array( $this, 'save_freightcenter_data' ) );
		add_action( 'woocommerce_process_product_meta_variable', array( $this, 'save_freightcenter_data' ) );

		// Notice when not all fields are filled in
		add_action( 'admin_notices', array( $this, 'required_fields_notice' ) );

		// Save as draft when freight shipping is not valid
		add_action( 'save_post', array( $this, 'save_product_check_freight_fields' ) );

	}


	/**
	 * FrightCenter product tab.
	 *
	 * Add 'Freight Center' to the product data tabs.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $tabs 	List of current settings tabs.
	 * @return 	array 			List of edited settings tabs.
	 */
	public function product_data_tabs( $tabs ) {

		$tabs['freightcenter'] = array(
			'label'  => __( 'FreightCenter', 'woocommerce-freightcenter' ),
			'target' => 'freightcenter_product_data',
			'class'  => array( '' ),
		);

		return $tabs;

	}


	/**
	 * Output FreightCenter tab.
	 *
	 * Output settings to the FreightCenter tab.
	 *
	 * @since 1.0.0
	 */
	public function freightcenter_product_data_panel() {

		$warehouses = get_posts( array( 'post_type' => 'warehouse', 'order' => 'ASC', 'orderby' => 'post_date' ) );
		$warehouse_options = array( '' => __('Select', 'woocommerce-freightcenter') );
		foreach ( $warehouses as $warehouse ) :
			$warehouse_options[ $warehouse->ID ] = $warehouse->post_title;
		endforeach;

		?><div id="freightcenter_product_data" class="panel woocommerce_options_panel"><?php

			woocommerce_wp_select( array(
				'id' 			=> '_ship_via_freight',
				'label' 		=> __( 'Ship this item via freight', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'no' 	=> __( 'No', 'woocommerce-freightcenter' ),
					'yes'	=> __( 'Yes', 'woocommerce-freightcenter' ),
				)
			) );

			woocommerce_wp_select( array(
				'id' 			=> '_freight_class',
				'label' 		=> __( 'Freight class', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'50'	=> '50',
					'55'	=> '55',
					'60'	=> '60',
					'65'	=> '65',
					'70'	=> '70',
					'77.5'	=> '77.5',
					'85'	=> '85',
					'92.5'	=> '92.5',
					'100'	=> '100',
					'110'	=> '110',
					'125'	=> '125',
					'150'	=> '150',
					'175'	=> '175',
					'200'	=> '200',
					'250'	=> '250',
					'300'	=> '300',
					'400'	=> '400',
					'500'	=> '500',
				)
			) );

			woocommerce_wp_text_input( array(
				'id' 		=> '_nmfc',
				'label' 	=> __( 'NMFC', 'woocommerce-freightcenter' ),
			) );

			woocommerce_wp_select( array(
				'id' 			=> '_hazardous',
				'label' 		=> __( 'Hazardous', 'woocommerce' ),
				'description' 	=> __( '', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'no' 	=> __( 'No', 'woocommerce-freightcenter' ),
					'yes'	=> __( 'Yes', 'woocommerce-freightcenter' ),
				)
			) );

			woocommerce_wp_select( array(
				'id' 			=> '_packaging_type',
				'label' 		=> __( 'Packaging type', 'woocommerce' ),
				'description' 	=> __( '', 'woocommerce-freightcenter' ),
				'options' 		=> array(
					'boxed'			=> __( 'Boxed', 'woocommerce-freightcenter' ),
					'crated' 		=> __( 'Crated', 'woocommerce-freightcenter' ),
					'drums_barrels' => __( 'Drums or barrels', 'woocommerce-freightcenter' ),
					'palletized' 	=> __( 'Palletized', 'woocommerce-freightcenter' ),
					'unpackaged' 	=> __( 'Un-Packaged', 'woocommerce-freightcenter' ),
				)
			) );

			woocommerce_wp_select( array(
				'id' 			=> '_origin_warehouse',
				'label' 		=> __( 'Origin warehouse', 'woocommerce' ),
				'description' 	=> __( '', 'woocommerce-freightcenter' ),
				'options' 		=> $warehouse_options,
			) );


		?></div><?php

	}


	/**
	 * Save meta.
	 *
	 * Save the product FreightCenter meta data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function save_freightcenter_data( $post_id ) {

		// Save all meta
		update_post_meta( $post_id, '_ship_via_freight', $_POST['_ship_via_freight'] );
		update_post_meta( $post_id, '_freight_class', $_POST['_freight_class'] );
		update_post_meta( $post_id, '_nmfc', $_POST['_nmfc'] );
		update_post_meta( $post_id, '_hazardous', $_POST['_hazardous'] );
		update_post_meta( $post_id, '_packaging_type', $_POST['_packaging_type'] );
		update_post_meta( $post_id, '_origin_warehouse', $_POST['_origin_warehouse'] );

	}


	/**
	 * Required fields check.
	 *
	 * Check if a notice should be shown. Applied when not all required fields are filled out.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post to check if all fields are filled in.
	 */
	public function show_required_fields_notice( $post_id ) {

		if ( 'product' != get_post_type( $post_id ) ) :
			return false;
		endif;


		if ( 'yes' != get_post_meta( $post_id, '_ship_via_freight', true ) ) :
			return false;
		endif;

		if ( '' == get_post_meta( $post_id, '_nmfc', true ) ) :
			return true;
		endif;

		if ( '' == get_post_meta( $post_id, '_origin_warehouse', true ) ) :
			return true;
		endif;

	}


	/**
	 * Required notice.
	 *
	 * Display a notice when not all required fields are filled out.
	 *
	 * @since 1.0.0
	 */
	public function required_fields_notice() {

		if ( ! $this->show_required_fields_notice( get_the_ID() ) ) :
			return;
		endif;

		?><div class='error'>
			<p><?php _e( 'Not all required FreightCenter fields are filled in. Make sure all fields have a valid value.', 'woocommerce-freightcenter' ); ?></p>
		</div><?php

	}


	/**
	 * Update to draft.
	 *
	 * Update the post to draft when it requires freight shipping,
	 * but not all required freight settings are set.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the post being saved.
	 */
	public function save_product_check_freight_fields( $post_id ) {

		remove_action( 'save_post', array( $this, 'save_product_check_freight_fields' ) );

		if ( $this->show_required_fields_notice( $post_id ) ) :
			wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
		endif;

		add_action( 'save_post', array( $this, 'save_product_check_freight_fields' ) );

	}


}
