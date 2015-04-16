jQuery( function( $ ) {

	$( '#_location_type' ).on( 'change', function() {
		$('.shipping-options input:checkbox').attr( 'checked', false );
		$( 'body' ).trigger( 'update_checkout' );
	});
	$( 'body').on( 'click', '.shipping-options input', function() {
		$( 'body' ).trigger( 'update_checkout' );
	});

});