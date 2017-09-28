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

	// Toggle the term adding interface.
	$( ".taxonomy-add-new" ).click( function( e ) {
		e.preventDefault();

		var taxonomy = $( this ).attr( "id" ).split( "-" )[ 0 ];

		$( "#" + taxonomy + "-adder" ).toggleClass( "wp-hidden-children" );
		$( "#new" + taxonomy ).focus();
	} );
}( jQuery ) );
