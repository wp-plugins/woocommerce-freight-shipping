<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;

$order 			= new WC_Order( $post->ID );
$warehouses 	= get_post_meta( $order->id, '_warehouses_order', true );
$wcfc_settings 	= get_option( 'woocommerce_freight_center_settings', array() );

?><script>
	jQuery( function( $ ) {
		$( '.datepicker' ).datepicker( { minDate: 1 } );
	});
</script>
<style>
	#fc_admin .inside span { font-weight: bold; float: left; width: 150px; }
	#fc_admin .inside .option-group { border-bottom: 1px dotted #ECECEC; padding: 10px 0px; margin: 0; }

	#fc_admin .line { border-bottom: 1px dotted #ECECEC; padding: 8px 0px; margin: 0; clear: both; }
	#fc_admin .line img { width: 25px; height: auto; }
	#fc_admin .inside { padding: 0; margin: 0; }
	#fc_admin .one-half { width: 50%; float: left; }
	#fc_admin .one-half~.one-half { clear: right; }
	.rate { padding: 10px 12px 0px; }
	.rate.rate_booked { background: #DFF0D8; }
	.rate.cancelled { background: #F2DEDE; }

</style>
<?php

if ( ! $warehouses ) :
	?><div class='rate'><?php _e( 'There is no data found for this order', 'woocommerce-freightceter' ); ?></div><br/><?php
	return;
endif;

// Make a form for each rate
foreach ( $warehouses as $warehouse_id => $warehouse ) :

	$chosen_rate	= $warehouse['chosen_rate'];
	$status 		= $warehouse['status'];
	$disabled 		= $status != 'not_booked' ? 'disabled' : '';
	$product_ids 	= wp_list_pluck( $warehouse['products_ordered'], 'product_id' );
	$warehouse_post = get_post( $warehouse_id );
	$shipment_id 	= isset( $warehouse['tracking']['shipment_id'] ) ? $warehouse['tracking']['shipment_id'] : '';
	$chosen_rate	= $warehouse['chosen_rate'];

	// Get warehouse (origin) accessorials
	$location_type 		= get_post_meta( $warehouse_post->ID, '_location_type', true );
	$location_option 	= (array) get_post_meta( $warehouse_post->ID, '_location_options', true );

	$accessorials = array();
	if ( in_array( 'include_lift_gate', $location_option ) ) :
		$accessorials[] = __( 'Include A Lift Gate At Pickup', 'woocommerce-freightcenter' );
	endif;

	if ( in_array( 'limited_access', $location_option ) ) :
		$accessorials[] = __( 'Is Limited Access Pickup Area', 'woocommerce-freightcenter' );
	endif;

	if ( in_array( 'inside_pickup', $location_option ) ) :
		$accessorials[] = __( 'Is Inside Pickup', 'woocommerce-freightcenter' );
	endif;

	// Formatted Addresses
	$address = array(
		'address'		=> $warehouse['origin']['_address'][0],
		'street'		=> $warehouse['origin']['_street'][0],
		'city'			=> $warehouse['origin']['_city'][0],
		'state'			=> $warehouse['origin']['_state'][0],
		'postcode'		=> $warehouse['origin']['_postcode'][0],
		'country'		=> $warehouse['origin']['_country'][0],
	);

	$joined_address = array();

	foreach ( $address as $part ) {

		if ( ! empty( $part ) ) {
			$joined_address[] = $part;
		}
	}

	?><div class='rate <?php echo $status; ?>' >

		<form method='post' action='<?php echo admin_url( "post.php?post={$post->ID}&action=edit" ); ?>'>

			<div class='one-half'>
				<div class='line'>
					<span><?php _e( 'Status:', 'woocommerce-freightcenter' ); ?></span> <?php echo $status; ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Warehouse:', 'woocommerce-freightcenter' ); ?></span> <?php echo $warehouse_post->post_title; ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Carrier:', 'woocommerce-freightcenter' ); ?></span> <?php echo $chosen_rate->CarrierName; ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Rate ID:', 'woocommerce-freightcenter' ); ?></span> <?php echo $chosen_rate->RateId; ?><br/>
				</div>
				<?php if ( ! empty( $shipment_id ) ) : ?>
					<div class='line'>
						<span><?php _e( 'Shipment ID:', 'woocommerce-freightcenter' ); ?></span> <?php echo $shipment_id; ?><br/>
					</div>
				<?php endif; ?>
				<div class='line'>
					<span><?php _e( 'Transit days:', 'woocommerce-freightcenter' ); ?></span> <?php echo $chosen_rate->ServiceDays; ?><br/>
				</div>
			</div>
			<div class='one-half'>

				<div class='line'>
					<span><?php _e( 'Cost', 'woocommerce-freightcenter' ); ?></span> <?php echo wc_price( $chosen_rate->TotalCharge ); ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Charge', 'woocommerce-freightcenter' ); ?></span> <?php echo wc_price( $warehouse['charge'] ); ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'From', 'woocommerce-freightcenter' ); ?></span> <?php echo implode( ', ', $joined_address ); ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'To', 'woocommerce-freightcenter' ); ?></span> <?php echo $warehouse['destination']; ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Location type:', 'woocommerce-freightcenter' ); ?></span> <?php echo $location_type; ?><br/>
				</div>
				<div class='line'>
					<span><?php _e( 'Accessorials:', 'woocommerce-freightcenter' ); ?></span> <?php echo implode( ', ', $accessorials ); ?><br/>
				</div>

			</div>

			<div class='line'>
				<span><?php _e( 'Products:', 'woocommerce-freightcenter' ); ?></span>
				<div class='products' style='clear: both;'>
					<table><?php

					 foreach ( $product_ids as $product_id ) :
						$product = wc_get_product( $product_id );

						?><tr>
							<td><?php echo $product->get_image( '25,25', array( 'title' => '' ) ); ?></td>
							<td>
								<a href='<?php echo admin_url( "post.php?post={$product->id}&action=edit" ); ?>'><?php echo $product->get_formatted_name(); ?></a>
							</td>
						</tr><?php
					endforeach;

					?></table>
				</div>
			</div>


			<div style="display: block" class='option-group book-rate-wrapper'>

				<span><?php echo sprintf( __( 'Date pickup by %s:', 'woocommerce-freightcenter' ), $chosen_rate->CarrierName ); ?></span>
				<input type='text' class='datepicker' placeholder='YYYY/MM/DD' name='<?php echo $chosen_rate->RateId; ?>[pickup_date]' <?php echo $disabled; ?>>

				<div style='text-align: right; clear: both;'>

					<label for='' style='vertical-align:bottom;'><?php _e( 'I agree to the terms and conditions of FreightCenter and the carrier', 'woocommerce-freightcenter' ); ?></label>
					<input type='checkbox' name='<?php echo $chosen_rate->RateId; ?>[term_conditions]' class='terms' value='1' <?php echo $disabled; ?> style='vertical-align: bottom;'>

					<!-- Hidden fields -->
					<input type='hidden' name='rate_ids[]' value='<?php echo $chosen_rate->RateId; ?>'>
					<input type='hidden' name='<?php echo $chosen_rate->RateId; ?>[rate_id]' value='<?php echo $chosen_rate->RateId; ?>'>
					<input type='hidden' name='<?php echo $chosen_rate->RateId; ?>[warehouse_id]' value='<?php echo $warehouse_id; ?>'>
					<input type='hidden' name='action' value='freightcenter_bookrate'>
					<input type='hidden' name='post_id' value='<?php echo $post->ID; ?>'>
					<?php wp_nonce_field( 'bookrate', 'bookrate_nonce' ); ?>

					<input type='submit' class='button-primary button submit-rate' disabled value='<?php _e( 'Book rate!', 'woocommerce-freightcenter' ); ?>' <?php echo $disabled; ?>  style='vertical-align: bottom;'><?php


					// Cancel shipment button
					if ( 'rate_booked' == $status ) :
						$href = wp_nonce_url( add_query_arg( array(
							'action' 			=> 'cancel_shipment',
							'order_id' 			=> $post->ID,
							'shipment_id' 		=> $shipment_id,
							'warehouse_id' 		=> $warehouse_id
						) ) );

						?><a class='button-primary button' onclick="return confirm('<?php _e( 'Are you sure?', 'woocommerce-freightcenter' ); ?>')" href='<?php echo $href; ?>' style='margin-top: 10px;'>
							<?php _e( 'Cancel shipment', 'woocommerce-freightcenter' ); ?>
						</a><?php
					endif;

				?></div>

			</div><!-- option-group -->

		<div style="display: block" class='option-group bol-wrapper'>

			<strong><?php _e( 'Your BOL:', 'woocommerce-freightcenter' ); ?>&nbsp;</strong><?php

			if ( isset( $warehouse['bol_ready'] ) && 1 == $warehouse['bol_ready'] && 'cancelled' != $status ) :
				if ( isset( $wcfc_settings['mode'] ) && 'sandbox' == $wcfc_settings['mode'] ) :
					?><a href='<?php echo 'http://stagcenter.com/DownloadBOL.aspx?qid=' . $shipment_id; ?>' target='_blank'><?php
						echo 'http://stagcenter.com/DownloadBOL.aspx?qid=' . $shipment_id;
					?></a><?php
				else :
					?><a href='<?php echo 'http://www.freightcenter.com/DownloadBOL.aspx?qid=' . $shipment_id; ?>' target='_blank'><?php
						echo 'http://www.freightcenter.com/DownloadBOL.aspx?qid=' . $shipment_id;
					?></a><?php
				endif;
			endif;

		?></div>

		</form>

	</div><?php

endforeach;