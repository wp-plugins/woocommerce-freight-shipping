<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Class WCFC_Warehouse.
 *
 *	Initialize and set up Warehouse (post type and more).
 *
 *	@class       WCFC_Warehouse
 *	@version     1.0.0
 *	@author      FreightCenter
 */
class WCFC_Warehouse {

	/**
	 * Constructor.
	 *
	 * Initialize this class including hooks.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Register post type
		add_action( 'init', array( $this, 'register_post_type_warehouse' ) );

		// Update post type messages
		add_filter( 'post_updated_messages', array( $this, 'warehouse_messages' ) );


		// Add custom columns
		add_action( 'manage_edit-warehouse_columns', array( $this, 'custom_columns' ), 11 );
		// Add contents to the new columns
		add_action( 'manage_warehouse_posts_custom_column', array( $this, 'custom_column_contents' ), 10, 2 );


		// Add meta box
		add_action( 'add_meta_boxes', array( $this, 'warehouse_data_meta_box' ) );

		// Save meta box
		add_action( 'save_post', array( $this, 'save_warehouse_data_meta_box' ) );

		// Ajax refresh for location options at warehouse meta box
		add_action( 'wp_ajax_freightcenter_location_options', array( $this, 'ajax_warehouse_data_location_options' ) );

	}


	/**
	 * Post type.
	 *
	 * Register post type.
	 *
	 * @since 1.0.0
	 */
	public function register_post_type_warehouse() {

		$labels = array(
			'name'               => __( 'Warehouse ', 'woocommerce-freightcenter' ),
			'singular_name'      => __( 'Warehouse', 'woocommerce-freightcenter' ),
			'menu_name'          => __( 'Warehouses', 'woocommerce-freightcenter' ),
			'name_admin_bar'     => __( 'Warehouse', 'woocommerce-freightcenter' ),
			'add_new'            => __( 'Add New', 'woocommerce-freightcenter' ),
			'add_new_item'       => __( 'Add New Warehouse', 'woocommerce-freightcenter' ),
			'new_item'           => __( 'New Warehouse', 'woocommerce-freightcenter' ),
			'edit_item'          => __( 'Edit Warehouse', 'woocommerce-freightcenter' ),
			'view_item'          => __( 'View Warehouse', 'woocommerce-freightcenter' ),
			'all_items'          => __( 'All Warehouses', 'woocommerce-freightcenter' ),
			'search_items'       => __( 'Search Warehouses', 'woocommerce-freightcenter' ),
			'parent_item_colon'  => __( 'Parent Warehouses:', 'woocommerce-freightcenter' ),
			'not_found'          => __( 'No Warehouses found.', 'woocommerce-freightcenter' ),
			'not_found_in_trash' => __( 'No Warehouses found in Trash.', 'woocommerce-freightcenter' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'warehouse' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'warehouse', $args );

	}


	/**
	 * Admin messages.
	 *
	 * Custom admin messages when using warehouse post type.
	 *
	 * @since 1.0.0
	 * @param array $messages
	 * @return array Full list of all messages.
	 */
	public function warehouse_messages( $messages ) {

		$post             = get_post();
		$post_type        = 'warehouse';
		$post_type_object = get_post_type_object( $post_type );

		$messages[ $post_type ] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => __( 'Warehouse updated.', 'woocommerce-freightcenter' ),
			2  => __( 'Custom field updated.', 'woocommerce-freightcenter' ),
			3  => __( 'Custom field deleted.', 'woocommerce-freightcenter' ),
			4  => __( 'Warehouse updated.', 'woocommerce-freightcenter' ),
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Warehouse restored to revision from %s', 'woocommerce-freightcenter' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => __( 'Warehouse published.', 'woocommerce-freightcenter' ),
			7  => __( 'Warehouse saved.', 'woocommerce-freightcenter' ),
			8  => __( 'Warehouse submitted.', 'woocommerce-freightcenter' ),
			9  => sprintf(
				__( 'Warehouse scheduled for: <strong>%1$s</strong>.', 'woocommerce-freightcenter' ),
				date_i18n( __( 'M j, Y @ G:i', 'woocommerce-freightcenter' ), strtotime( $post->post_date ) )
			),
			10 => __( 'Warehouse draft updated.', 'woocommerce-freightcenter' )
		);

		return $messages;

	}


	/**
	 * Post columns.
	 *
	 * Set custom columns for the Warehouse post type.
	 *
	 * @since 1.0.0
	 *
	 * @param 	array $columns 	List of existing post columns.
	 * @return 	array 			List of edited columns.
	 */
	public function custom_columns( $columns ) {

		$columns['address'] 	= __( 'Address', 'woocommerce-freightcenter' );
		$columns['postcode'] 	= __( 'Postcode', 'woocommerce-freightcenter' );
		$columns['hours'] 		= __( 'Hours of Operation', 'woocommerce-freightcenter' );

		unset( $columns['date'] );
		unset( $columns['date'] );

		return $columns;

	}


	/**
	 * Columns contents.
	 *
	 * Output the custom columns contents.
	 *
	 * @since 1.0.0
	 *
	 * @param string 	$column Slug of the current columns to output data for.
	 * @param int 		$post_id ID of the current post.
	 */
	public function custom_column_contents( $column, $post_id ) {

		switch( $column ) :

			case 'address' :

				$address = get_post_meta( $post_id, '_address', true );
				if ( $address ) :
					echo $address;
				endif;

			break;
			case 'postcode' :

				$postcode = get_post_meta( $post_id, '_postcode', true );
				if ( $postcode ) :
					echo $postcode;
				endif;

			break;
			case 'hours' :

				$hours = get_post_meta( $post_id, '_hours_of_operation', true );
				if ( $hours ) :
					echo $hours;
				endif;

			break;

		endswitch;

	}



	/**
	 * Meta box.
	 *
	 * Add an meta box with all the warehouse data.
	 *
	 * @since 1.0.0
	 */
	public function warehouse_data_meta_box() {
		add_meta_box( 'warehouse_data', __( 'Warehouse data', 'woocommerce-freightcenter' ), array( $this, 'warehouse_data_meta_box_contents' ), 'warehouse', 'normal' );
	}


	/**
	 * Meta box content.
	 *
	 * Get contents from file and put them in the meta box.
	 *
	 * @since 1.0.0
	 */
	public function warehouse_data_meta_box_contents() {
		require_once plugin_dir_path( __FILE__ ) . 'views/meta-box-warehouse-data.php';
	}


	public function ajax_warehouse_data_location_options() {

		$post_id 		= $_POST['post_id'];
		$location_type 	= $_POST['location_type'];

		$this->warehouse_data_location_options( $post_id, $location_type );
		exit;

	}


	/**
	 * Location options.
	 *
	 * The location options. This gets loaded in a separate function
	 * to also be able to use it as a ajax function for live reload.
	 *
	 * @since 1.0.0
	 *
	 * @param int|string $post_id ID of the post to get the option for.
	 * @param string $location_type
	 */
	public function warehouse_data_location_options( $post_id = '', $location_type = '' ) {

		// Set location type
		if ( empty( $location_type ) ) :
			$location_type = get_post_meta( $post_id, '_location_type', true );
		endif;

		$location_option = (array) get_post_meta( $post_id, '_location_options', true );

		?><p class='wcfc-option'><?php

			if ( 'residential' == $location_type || '' == $location_type ) :

				?><label for='location-options'><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input id='lift-gate' name='_location_options[]' type='checkbox' <?php checked( in_array( 'include_lift_gate', $location_option ) ); ?> value='include_lift_gate' />
				<label for='lift-gate'><?php _e( 'Include A Lift Gate At Pickup', 'woocommerce-freightcenter' ); ?></label><br/><?php


			elseif ( 'businesswithdockorforklift' == $location_type ) :

				?><label for='location-options'><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input id='limited-access' name='_location_options[]' type='checkbox' <?php checked( in_array( 'limited_access', $location_option ) ); ?> value='limited_access' />
				<label for='limited-access'><?php _e( 'Is Limited Access Pickup Area', 'woocommerce-freightcenter' ); ?></label><br/><?php

				?><label></label>
				<input id='inside-pickup' name='_location_options[]' type='checkbox' <?php checked( in_array( 'inside_pickup', $location_option ) ); ?> value='inside_pickup' />
				<label for='inside-pickup'><?php _e( 'Is Inside Pickup', 'woocommerce-freightcenter' ); ?></label><br/><?php

			elseif ( 'businesswithoutdockorforklift' == $location_type ) :

				?><label><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input id='lift-gate' name='_location_options[]' type='checkbox' <?php checked( in_array( 'include_lift_gate', $location_option ) ); ?> value='include_lift_gate' />
				<label for='lift-gate'><?php _e( 'Include A Lift Gate At Pickup', 'woocommerce-freightcenter' ); ?></label><br/><?php

				?><label></label>
				<input id='limited-access' name='_location_options[]' type='checkbox' <?php checked( in_array( 'limited_access', $location_option ) ); ?> value='limited_access' />
				<label for='limited-access'><?php _e( 'Is Limited Access Pickup Area', 'woocommerce-freightcenter' ); ?></label><br/><?php

			elseif ( 'terminal' == $location_type ) :

				?><label for='location-options'><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input name='_location_options[]' type='hidden' value='' />
				<?php _e( 'There are no options available for this location type', 'woocommerce-freightcenter' );

			elseif ( 'constructionsite' == $location_type ) :

				?><label><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input id='lift-gate' name='_location_options[]' type='checkbox' <?php checked( in_array( 'include_lift_gate', $location_option ) ); ?> value='include_lift_gate' />
				<label for='lift-gate'><?php _e( 'Include A Lift Gate At Pickup', 'woocommerce-freightcenter' ); ?></label><br/><?php


			elseif ( 'conventioncenterortradeshow' == $location_type ) :

				?><label><?php _e( 'Options', 'woocommerce-freightcenter' ); ?></label>
				<input id='lift-gate' name='_location_options[]' type='checkbox' <?php checked( in_array( 'include_lift_gate', $location_option ) ); ?> value='include_lift_gate' />
				<label for='lift-gate'><?php _e( 'Include A Lift Gate At Pickup', 'woocommerce-freightcenter' ); ?></label><br/><?php

			endif;

		?></p><?php

	}


	/**
	 * Save Meta box.
	 *
	 * Save the given contents from the meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id ID of the current post.
	 *
	 * @return int $post_id
	 */
	public function save_warehouse_data_meta_box( $post_id ) {

		if ( ! isset( $_POST['warehouse_data_meta_box_nonce'] ) ) :
			return $post_id;
		endif;

		$nonce = $_POST['warehouse_data_meta_box_nonce'];

		if ( ! wp_verify_nonce( $nonce, 'warehouse_data_meta_box' ) ) :
			return $post_id;
		endif;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) :
			return $post_id;
		endif;

		if ( ! current_user_can( 'manage_woocommerce' ) ) :
			return $post_id;
		endif;

		// Update post meta
		update_post_meta( $post_id, '_short_name', 			sanitize_key( $_POST['_short_name'] ) );
		update_post_meta( $post_id, '_company_name', 		sanitize_text_field( $_POST['_company_name'] ) );
		update_post_meta( $post_id, '_address', 			sanitize_text_field( $_POST['_address'] ) );
		update_post_meta( $post_id, '_street', 				sanitize_text_field( $_POST['_street'] ) );
		update_post_meta( $post_id, '_city', 				sanitize_text_field( $_POST['_city'] ) );
		update_post_meta( $post_id, '_postcode', 			sanitize_text_field( $_POST['_postcode'] ) );
		update_post_meta( $post_id, '_state', 				sanitize_text_field( $_POST['_state'] ) );
		update_post_meta( $post_id, '_country', 			sanitize_text_field( $_POST['_country'] ) );
		update_post_meta( $post_id, '_first_name', 			sanitize_text_field( $_POST['_first_name'] ) );
		update_post_meta( $post_id, '_last_name', 			sanitize_text_field( $_POST['_last_name'] ) );
		update_post_meta( $post_id, '_phone', 				sanitize_text_field( $_POST['_phone'] ) );
		update_post_meta( $post_id, '_email', 				sanitize_text_field( $_POST['_email'] ) );
		update_post_meta( $post_id, '_location_type', 		sanitize_key( $_POST['_location_type'] ) );
		update_post_meta( $post_id, '_location_options', 	isset( $_POST['_location_options'] ) ? $_POST['_location_options'] : '' );
		update_post_meta( $post_id, '_hours_of_operation', 	sanitize_text_field( $_POST['_hours_of_operation'] ) );

	}


}
