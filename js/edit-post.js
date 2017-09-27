( function( $ ) {

	// Initialize Select2.
	$( ".taxonomy-select2" ).select2( {
		placeholder: "+ Add",
		templateResult: function( data, container ) {
			if ( data.element ) {
				$( container ).addClass( $( data.element ).attr( "class" ) );
			}
			return data.text;
		}
	} );
}( jQuery ) );
