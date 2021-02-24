( function( $ ) {
	// Document ready.
	$(function() {
		function taxonomyChange() {
			var checkbox = $( this )
			console.log( checkbox )
			var taxonomy = checkbox.attr( 'name' )
				.replace( 'tax_input[', '' )
				.replace( '][]', '' )
			var selectboxes = $( '.js-primary-selector[data-taxonomy=' + taxonomy + ']' )

			var options = {}
			checkbox.closest( '.categorydiv' ).find( 'input:checked' ).each( function() {
				var input = $( this )
				options[ input.val() ] =  input.parent().text()
			} )

			var optionsHTML = '<option value="0">Select Primary Category</option>'
			$.each( options, function( id, value ) {
				optionsHTML += '<option value=' + id + '>' + value + '</option>'
			} )

			selectboxes.each( function() {
				var select = $( this )
				select.html( optionsHTML )
				select.val( select.data( 'selected' ) )
			})
		}

		$( '.js-primary-selector' ).each( function() {
			var selector = $( this ).data( 'taxonomy' )
			if ( '' !== selector ) {
				$( '#taxonomy-' + selector )
					.on( 'change', 'input:checkbox', taxonomyChange )

				$( 'input:checkbox:eq(0)', '#' + selector + '-all' ).trigger( 'change' )
			}
		} )
	})
} )(jQuery)
