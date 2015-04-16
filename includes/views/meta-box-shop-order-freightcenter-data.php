<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post;
$location_type 		= get_post_meta( $post->ID, '_location_type', true );
$rate_ids			= get_post_meta( $post->ID, '_fc_rates', true );
$rate_ids			= ! empty( $rate_ids ) ? implode( ', ', wp_list_pluck( $rate_ids, 'RateId' ) ) : '';
$call_delivery		= get_post_meta( $post->ID, '_call_before_delivery', true );
$threshold_delivery	= get_post_meta( $post->ID, '_threshold_delivery', true );
$limited_access 	= get_post_meta( $post->ID, '_limited_access_delivery', true );
$lift_gate	 		= get_post_meta( $post->ID, '_destination_lift_gate', true );

?><style>
	#fc_data .inside span { font-weight: bold; float: left; width: 170px; }
	#fc_data .inside p.option-group { border-bottom: 1px dotted #ECECEC; padding: 10px 0px; margin: 0; }
</style>

<p class='option-group'>
	<span><?php _e( 'Location type:', 'woocommerce-freightcenter' ); ?></span>&nbsp;<?php echo $location_type; ?>
</p>

<p class='option-group'>
	<span><?php _e( 'Call before delivery:', 'woocommerce-freightcenter' ); ?></span>&nbsp;<?php echo $call_delivery; ?>
</p>

<p class='option-group'>
	<span><?php _e( 'Threshold delivery:', 'woocommerce-freightcenter' ); ?></span>&nbsp;<?php echo $threshold_delivery; ?>
</p>

<p class='option-group'>
	<span><?php _e( 'Limited access delivery:', 'woocommerce-freightcenter' ); ?></span>&nbsp;<?php echo $limited_access ?>
</p>

<p class='option-group'>
	<span><?php _e( 'Lift Gate at Delivery Point:', 'woocommerce-freightcenter' ); ?></span>&nbsp;<?php echo $lift_gate ?>
</p><?php
