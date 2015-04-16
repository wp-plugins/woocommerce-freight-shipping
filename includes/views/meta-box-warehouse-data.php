<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;
wp_nonce_field( 'warehouse_data_meta_box', 'warehouse_data_meta_box_nonce' );

$short_name 		= get_post_meta( $post->ID, '_short_name', true );
$company_name 		= get_post_meta( $post->ID, '_company_name', true );
$address 			= get_post_meta( $post->ID, '_address', true );
$street 			= get_post_meta( $post->ID, '_street', true );
$city 				= get_post_meta( $post->ID, '_city', true );
$postcode 			= get_post_meta( $post->ID, '_postcode', true );
$state 				= get_post_meta( $post->ID, '_state', true );
$country 			= get_post_meta( $post->ID, '_country', true );
$first_name 		= get_post_meta( $post->ID, '_first_name', true );
$last_name 			= get_post_meta( $post->ID, '_last_name', true );
$phone 				= get_post_meta( $post->ID, '_phone', true );
$email 				= get_post_meta( $post->ID, '_email', true );
$location_type	 	= get_post_meta( $post->ID, '_location_type', true );
$hours_of_operation = get_post_meta( $post->ID, '_hours_of_operation', true );
?>
<style>
	#warehouse_data .wcfc-option label { display: inline-block; width: 150px }
</style>

<p class='wcfc-option'>
	<label for='short_name'><?php _e( 'Short name', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='short_name' name='_short_name' value='<?php echo @esc_attr( $short_name ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='comapny_name'><?php _e( 'Company name', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='company_name' name='_company_name' value='<?php echo @esc_attr( $company_name ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='address'><?php _e( 'Full address', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='address' name='_address' value='<?php echo @esc_attr( $address ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='street'><?php _e( 'Full street', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='street' name='_street' value='<?php echo @esc_attr( $street ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='city'><?php _e( 'Full city', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='city' name='_city' value='<?php echo @esc_attr( $city ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='postcode'><?php _e( 'Postcode', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='postcode' name='_postcode' value='<?php echo @esc_attr( $postcode ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='state'><?php _e( 'Full state', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='state' name='_state' value='<?php echo @esc_attr( $state ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='country'><?php _e( 'Full country', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='country' name='_country' value='<?php echo @esc_attr( $country ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='first_name'><?php _e( 'First name', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='first_name' name='_first_name' value='<?php echo @esc_attr( $first_name ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='last_name'><?php _e( 'Last name', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='last_name' name='_last_name' value='<?php echo @esc_attr( $last_name ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='phone'><?php _e( 'Phone', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='phone' name='_phone' value='<?php echo @esc_attr( $phone ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='email'><?php _e( 'Email', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='email' name='_email' value='<?php echo @esc_attr( $email ); ?>' size='25' />
</p>

<p class='wcfc-option'>
	<label for='location-type'><?php _e( 'Location type', 'woocommerce-freightcenter' ); ?></label>
	<select id='location-type' name='_location_type'>
		<option <?php selected( $location_type, 'residential' ); ?> value='Residential'>
			<?php _e( 'Residential', 'woocommerce-freightcenter' ); ?>
		</option>
		<option <?php selected( $location_type, 'businesswithdockorforklift' ); ?> value='BusinessWithDockOrForklift'>
			<?php _e( 'Business With Dock or Forklift', 'woocommerce-freightcenter' ); ?>
		</option>
		<option <?php selected( $location_type, 'businesswithoutdockorforklift' ); ?> value='BusinessWithoutDockOrForklift'>
			<?php _e( 'Business Without Dock or Forklift', 'woocommerce-freightcenter' ); ?>
		</option>
		<option <?php selected( $location_type, 'terminal' ); ?> value='Terminal'>
			<?php _e( 'Terminal', 'woocommerce-freightcenter' ); ?>
		</option>
		<option <?php selected( $location_type, 'constructionsite' ); ?> value='ConstructionSite'>
			<?php _e( 'Construction site', 'woocommerce-freightcenter' ); ?>
		</option>
		<option <?php selected( $location_type, 'conventioncenterortradeshow' ); ?> value='ConventionCenterOrTradeshow'>
			<?php _e( 'Convention Center or Trade Show', 'woocommerce-freightcenter' ); ?>
		</option>
	</select>
</p>


<div class='wcfc-option' id='location-type-wrap'>
	<?php WCFC()->warehouse->warehouse_data_location_options( $post->ID ); ?>
</div>

<p class='wcfc-option'>
	<label for='hours_of_operation'><?php _e( 'Hours of operation', 'woocommerce-freightcenter' ); ?></label>
	<input type='text' id='hours_of_operation' name='_hours_of_operation' value='<?php echo @esc_attr( $hours_of_operation ); ?>' size='25' />
</p>
