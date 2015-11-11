jQuery(document).ready(function($) {

	/* Settings scripts
	-------------------------------------------------------------- */
	
	// Provides a confirmation popup when clicking "Reset".
	$( '#reset' ).on( 'click', function(){
		// Need to have the "return" or won't work
	   	return confirm( 'Are you sure you want to reset these settings? This action cannot be undone.' );
	});
	
	// Move .updated and .error alert boxes. Don't move boxes designed to be inline.
	$( 'div.wrap h2:first' ).nextAll( 'div.updated, div.error' ).addClass( 'below-h2' );
	$( 'div.updated, div.error' ).not( '.below-h2, .inline' ).insertAfter( $( 'div.wrap h2:first' ) );
});