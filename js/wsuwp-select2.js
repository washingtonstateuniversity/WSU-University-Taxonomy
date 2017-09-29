( function( $, window ) {

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

	// Process an added term.
	$( ".term-add-submit" ).click( function() {
		var taxonomy = $( this ).attr( "id" ).split( "-" )[ 0 ],
			$term_input = $( "#new" + taxonomy ),
			$parent_select = $( "#new" + taxonomy + "_parent" ),
			data = {
				action: "add_term",
				nonce: window.wsuwp_taxonomies.nonce,
				taxonomy: taxonomy,
				parent: $parent_select.val(),
				term: $term_input.val()
			};

		$.post( window.wsuwp_taxonomies.ajax_url, data, function( response ) {
			var term = $.parseJSON( response );

			// Insert the new term into the markup if it was successfully added.
			if ( term.term_id ) {
				var option_class = "level-0",
					select_insertion_point = $( "#" + taxonomy + "-select" ).find( "option[value='" + term.wsuwp_insert_after + "']" ),
					parent_select_insertion_point = $parent_select.find( "option[value='" + term.wsuwp_insert_after + "']" ),
					parent_select_option_name_prefix = "";

				if ( "0" !== $parent_select.val() ) {
					var parent_level = $parent_select.find( ":selected" ).attr( "class" ).split( "-" )[ 1 ],
						term_level = parseInt( parent_level ) + 1;

					option_class = "level-" + term_level;
					parent_select_option_name_prefix = Array( term_level * 3 + 1 ).join( "\xa0" );
				}

				// Do the actual markup insertion.
				if ( 0 === term.wsuwp_insert_after ) {
					$( "#" + taxonomy + "-select" ).prepend( $( "<option>", {
						value: term.term_id,
						class: option_class,
						selected: true,
						text: term.name
					} ) );
				} else {
					select_insertion_point.after( $( "<option>", {
						value: term.term_id,
						class: option_class,
						selected: true,
						text: term.name
					} ) );
				}

				parent_select_insertion_point.after( $( "<option>", {
					value: term.term_id,
					class: option_class,
					text: parent_select_option_name_prefix + term.name
				} ) );

				// Reset the term input and parent select values,
				// focus on the term input (per default WP behavior).
				$parent_select.val( "0" );
				$term_input.val( "" ).focus();
			}
		} );
	} );
}( jQuery, window ) );
