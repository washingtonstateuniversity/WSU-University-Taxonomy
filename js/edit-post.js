( function( $, window ) {

	// Initialize Select2.
	$( ".taxonomy-select2" ).select2( {
		placeholder: "+ Add",
		closeOnSelect: false,
		templateResult: function( data, container ) {
			if ( data.element ) {
				$( container ).addClass( $( data.element ).attr( "class" ) );
			}
			return data.text;
		}
	} );

	// Re-render the Select2 dropdown position when a new selection is made.
	$( ".taxonomy-select2" ).on( "change", function() {
		$( window ).scroll();
	} );
}( jQuery, window ) );
