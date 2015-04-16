jQuery( function( $ ) {

	// Location type - warehouse meta box
	$( '#location-type' ).on( 'change', function() {

		// Ajax update location options for warehouse
		var data = {
			action:			'freightcenter_location_options',
			post_id: 		$( '#post_ID' ).val(),
			location_type:  $( this ).val().toLowerCase(),
		};

		$.post( ajaxurl, data, function( response ) {
			$( '#location-type-wrap' ).html( response );
		})

	});


	// Book rate - Order meta box
	$( 'body' ).on( 'change', '.book-rate-wrapper input', function() {

		var terms = $( this ).parents( '.book-rate-wrapper' ).find( '.terms' );
		var date = $( this ).parents( '.book-rate-wrapper' ).find( '.datepicker' );

		if ( date == '' || ! terms.is( ':checked' ) ) {
			$( this ).parents( '.book-rate-wrapper' ).find( '.submit-rate' ).prop( 'disabled', true );
		} else {
			$( this ).parents( '.book-rate-wrapper' ).find( '.submit-rate' ).prop( 'disabled', false );
		}

	});

});