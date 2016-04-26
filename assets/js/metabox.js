jQuery(document).ready(function($){

	/* General Metabox scripts
	-------------------------------------------------------------- */
	
	// Show the selected content type
	function show_selected_content() {
		$('.blox-content-type').each( function() {
			var content_type = $(this).val();
		
			// All content sections start as hidden, so show the one selected
			$( this ).parents( '.blox-content-type-container' ).siblings( '.blox-content-' + content_type ).removeClass( 'blox-hidden' );
		});
	};
	
	// Run on page load so selected content is visible
	show_selected_content();
	
	// Shows and hides each content type on selection 
	$( document ).on( 'change', '.blox-content-type', function(){
		var content_type = $(this).val();
		
		$( this ).parents( '.blox-content-type-container' ).siblings().addClass( 'blox-hidden' );
		$( this ).parents( '.blox-content-type-container' ).siblings( '.blox-content-' + content_type ).removeClass( 'blox-hidden' );
	});

	
	
	/* Modal scripts
	-------------------------------------------------------------- */
	
	// Close the modal if you click on the overlay
	$(document).on( 'click', '#blox_overlay', function() { 
		$( '#blox_overlay' ).fadeOut(200);
		$( '.blox-modal' ).css({ 'display' : 'none' });
	});

	// Close the modal if you click on close button
	$(document).on( 'click', '.blox-modal-close', function() { 
		$( '#blox_overlay' ).fadeOut(200);
		$( '.blox-modal' ).css({ 'display' : 'none' }); 
	});
	
	
	
	/* Content - Image scripts
	-------------------------------------------------------------- */
	
	// Show the custom image type sections if custom or featured-custom
	$('.blox-image-type').each( function() {
		var image_type = $( this ).val();
		
		if ( image_type == 'custom' || image_type == 'featured-custom' ) {
			// All sections start as hidden, so show the custom image uploader if selected
			$( this ).parents( '.blox-content-image' ).find( '.blox-content-image-custom' ).show();
		} else {
			$( this ).parents( '.blox-content-image' ).find( '.blox-content-image-custom' ).hide();
		}
		
		if ( image_type == 'custom' ) {
			$( this ).siblings( '.blox-featured-singular-only' ).hide();
		} else {
			$( this ).siblings( '.blox-featured-singular-only' ).show();
		}
	});
	
	// Shows and hides custom image uploader on selection
	$( document ).on( 'change', '.blox-image-type', function() {  
		var image_type = $(this).val();
		
		if ( image_type == 'custom' || image_type == 'featured-custom' ) {
			// Show the custom image uploader if selected, otherwise hide it
			$( this ).parents( '.blox-content-image' ).find( '.blox-content-image-custom' ).show();
			$( this ).siblings().find( '.blox-featured-singular-only' ).hide();
		} else {
			$( this ).parents( '.blox-content-image' ).find( '.blox-content-image-custom' ).hide();
		}
		
		if ( image_type == 'custom' ) {
			$( this ).siblings( '.blox-featured-singular-only' ).hide();
		} else {
			$( this ).siblings( '.blox-featured-singular-only' ).show();
		}
		
	});


	// Image Uploader function                  
	blox_staticImageUpload = {

		/* Call this from the upload button to initiate the upload frame.
		 *
		 * @param int id The content block id so we can target the correct block
		 */
		uploader : function( id ) {
			var block_id = id;

			var frame = wp.media({
				title : blox_localize_metabox_scripts.image_media_title,
				multiple : false,
				library : { type : 'image' }, //only can upload images
				button : { text : blox_localize_metabox_scripts.image_media_button }
			});

			// Handle results from media manager
			frame.on( 'select', function() {
				var attachments = frame.state().get( 'selection' ).toJSON();
				blox_staticImageUpload.render( attachments[0], id );
			});

			frame.open();
			return false;
		},

		/* Output Image preview and populate widget form
		 *
		 * @param object attachment All of the images that were selected 
		 * @param int id            The content block id so we can target the correct block
		 */
		render : function( attachment, id) {	
			
			$( '#' + id + ' .blox-image-preview' ).attr( 'src', attachment.url );
			$( '#' + id + ' .blox-custom-image-id' ).val( attachment.id );
			$( '#' + id + ' .blox-custom-image-url' ).val( attachment.url );
			$( '#' + id + ' .blox-custom-image-alt' ).val( attachment.alt );
			$( '#' + id + ' .blox-custom-image-title' ).val( attachment.title );
			$( '#' + id + ' .blox-image-default' ).addClass( 'hidden' );
			$( '#' + id + ' .blox-image-preview' ).removeClass( 'hidden' );
			
			// Show the image atts input fields
			$( '#' + id + ' .blox-image-atts' ).show();				
		},
	};
    
    // Remove the image
    $( document ).on( 'click', '.blox-remove-image', function() {
    	var empty = '';
    	
    	// Need to use .find() because we are transversing two levels of the DOM
    	$( this ).siblings( '.blox-image-preview-wrapper' ).find( '.blox-image-preview' ).attr( 'src', empty ).addClass( 'hidden' );
  		$( this ).siblings( '.blox-image-preview-wrapper' ).find( '.blox-image-default' ).removeClass( 'hidden' );
    	$( this ).siblings( '.blox-custom-image-id' ).val( empty );
    	$( this ).siblings( '.blox-custom-image-url' ).val( empty );
    	
    	// Hide the image atts input fields and empty them
    	$( this ).siblings( '.blox-image-atts' ).hide();
 	  	$( this ).siblings( '.blox-image-atts' ).find( '.blox-custom-image-alt' ).val( empty );
 	  	$( this ).siblings( '.blox-image-atts' ).find( '.blox-custom-image-title' ).val( empty );
    });

	// Toggle image custom sizing options 
	/* NOT CURRENTLY BEING USED
	$(document).on( 'change', '.genesis-image-size-selector', function(){
		if ( $(this).val() == 'custom' ) {
			$(this).siblings( '.blox-image-size-custom' ).removeClass( 'blox-hidden' );
		} else {
			$(this).siblings( '.blox-image-size-custom' ).addClass( 'blox-hidden' );
		}	
	});
	*/
	
	// Show the image link settings if enabled on page load
	$( '.blox-image-link-enable input' ).each( function() {
		if ( $(this).is( ':checked' ) ) {
		  $(this).parents( '.blox-image-link-enable' ).siblings( '.blox-image-link' ).show();
		}
	});
	
	// Show the image link settings if checked
	$(document).on( 'change', '.blox-image-link-enable input', function(){
		if ( $(this).is( ':checked' ) ) {
		  $(this).parents( '.blox-image-link-enable' ).siblings( '.blox-image-link' ).show();
		} else {
		  $(this).parents( '.blox-image-link-enable' ).siblings( '.blox-image-link' ).hide();
	  	}
	});
	
	

	/* Position scripts
	-------------------------------------------------------------- */
	
	// Shows and hides each content type on selection 
	$(document).on( 'change', '.blox-position-type select', function(){
		if ( $(this).val() == 'default' ) {
			$(this).siblings( '.blox-position-default' ).removeClass( 'blox-hidden' );
			$(this).parents( '.blox-position-type' ).siblings( '.blox-position-custom' ).addClass( 'blox-hidden' );
		} else if ( $(this).val() == 'custom' ) {
			$(this).siblings( '.blox-position-default' ).addClass( 'blox-hidden' );
			$(this).parents( '.blox-position-type' ).siblings( '.blox-position-custom').removeClass( 'blox-hidden' );
		} else {
			$(this).siblings( '.blox-position-default' ).removeClass( 'blox-hidden' );
			$(this).parents( '.blox-position-type' ).siblings( '.blox-position-custom').addClass( 'blox-hidden' );
		}	
	});
	
	
	
	/* Visibility scripts
	-------------------------------------------------------------- */
	
	// Shows and hides visibility restrictions
	$(document).on( 'change', '.blox-visibility-role_type select', function(){
		if ( $(this).val() == 'restrict' ) {
			$(this).parents( '.blox-visibility-role_type' ).siblings( '.blox-visibility-role-restrictions' ).removeClass( 'blox-hidden' );
		} else {
			$(this).parents( '.blox-visibility-role_type' ).siblings( '.blox-visibility-role-restrictions' ).addClass( 'blox-hidden' );
		}	
	});
	
	
	
	/* Location scripts
	-------------------------------------------------------------- */
	
	// Shows and hides each content type on selection 
	$(document).on( 'change', '#blox_location_type', function(){
		if ( $(this).val() == 'hide_selected' ) {
			$( '.blox-location-container').removeClass( 'blox-hidden' );
			$( '.blox-test-description' ).html( blox_localize_metabox_scripts.location_test_hide );
			$( 'tr#blox_location_manual_hide' ).addClass( 'blox-hidden' );
			$( 'tr#blox_location_manual_show' ).removeClass( 'blox-hidden' );
		} else if ( $(this).val() == 'show_selected' ) {
			$( '.blox-location-container').removeClass( 'blox-hidden' );
			$( '.blox-test-description' ).html( blox_localize_metabox_scripts.location_test_show );
			$( 'tr#blox_location_manual_hide' ).removeClass( 'blox-hidden' );
			$( 'tr#blox_location_manual_show' ).addClass( 'blox-hidden' );
		} else {
			$( '.blox-location-container').addClass( 'blox-hidden' );
		}	
	});
	
	// 
	$(document).on( 'change', '.blox-location-selection input', function(){
	
		// Get the input id and change underscores to dashes to match class styles
		var selection = $(this).val();
		
		// Show and hide location option advanced settings based on check
	  	if ( $(this).is( ':checked' ) ) {
			$( 'tr#blox_location_' + selection ).removeClass( 'blox-hidden' );
	  	} else {
			$( 'tr#blox_location_' + selection ).addClass( 'blox-hidden' );
		}
	});

	// Show/Hide singles selection
	$(document).on( 'change', '.blox-location-singles-selection input', function(){
		var inputClass = 'blox-location-singles-' + $(this).val();

	  	if ( $(this).is( ':checked' ) ) {
			$( '.' + inputClass ).removeClass( 'blox-hidden' );
	  	} else {
			$( '.' + inputClass ).addClass( 'blox-hidden' );
		}
	});
	
	// Show/Hide archive selection
	$(document).on( 'change', '.blox-location-archive-selection input', function(){
		var inputClass = 'blox-location-archive-' + $(this).val();

	  	if ( $(this).is( ':checked' ) ) {
			$( '.' + inputClass ).removeClass( 'blox-hidden' );
	  	} else {
			$( '.' + inputClass ).addClass( 'blox-hidden' );
		}
	});
	
	// 
	$(document).on( 'change', '.blox-location-select_type', function(){
		if ( $(this).val() == 'selected' ) {
			$(this).siblings( '.blox-location-selected-container' ).removeClass( 'blox-hidden' );
		} else {
			$(this).siblings( '.blox-location-selected-container' ).addClass( 'blox-hidden' );
		}
	});
	
	// 
	$(document).on( 'change', '.blox-singles-select_type', function(){
		if ( $(this).val() == 'selected_posts' ) {
			$(this).siblings( '.blox-singles-container-inner' ).show();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-post-container' ).show();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-taxonomy-container-wrapper' ).hide();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-authors-container-wrapper' ).hide();
		} else if ( $(this).val() == 'selected_taxonomies' ) {
			$(this).siblings( '.blox-singles-container-inner' ).show();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-post-container' ).hide();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-taxonomy-container-wrapper' ).show();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-authors-container-wrapper' ).hide();
		} else if ( $(this).val() == 'selected_authors' ) {
			$(this).siblings( '.blox-singles-container-inner' ).show();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-post-container' ).hide();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-taxonomy-container-wrapper' ).hide();
			$(this).siblings( '.blox-singles-container-inner' ).children( '.blox-singles-authors-container-wrapper' ).show();
		} else {
			$(this).siblings( '.blox-singles-container-inner' ).hide();
		}
	});
	
	// 
	$(document).on( 'change', '.blox-taxonomy-select_type', function(){	
		if ( $(this).val() == 'selected_taxonomies' ) {
			$(this).siblings( '.blox-singles-taxonomy-container-inner' ).show();
		} else {
			$(this).siblings( '.blox-singles-taxonomy-container-inner' ).hide();
		}
	});
	
	

	/* Multi Checkbox Select All/None
	-------------------------------------------------------------- */
	
	// Select all options
	$( '.blox-checkbox-select-all' ).click( function(e) {
		e.preventDefault();
		
		$(this).parent().siblings( '.blox-checkbox-container' ).find( 'input' ).prop('checked', true).trigger("change");
	});
	
	// Deselect all options
	$( '.blox-checkbox-select-none' ).click( function(e) {
		e.preventDefault();
		
		$(this).parent().siblings( '.blox-checkbox-container' ).find( 'input' ).prop('checked', false).trigger("change");
	});
	
	
	
	/* Helper Text scripts
	-------------------------------------------------------------- */
	
	// Show/Hide help text when (?) is clicked
	var helpIcon = window.helpIcon = {
		
		toggleHelp : function( el ) {
			$( el ).parent().siblings( '.blox-help-text' ).slideToggle( 'fast' );
			return false;
		}
	}



	/* Local Blocks metabox scripts
	-------------------------------------------------------------- */
	
	// Remove Content Blocks (Need to '.on' because we are working with dynamically generated content)
	$(document).on( 'click', '#blox_content_blocks_container .blox-remove-block', function() {
		
		var message = confirm( blox_localize_metabox_scripts.confirm_remove );
		
		if ( message == true ) {
			$(this).parents( '.blox-content-block' ).remove();
			return false;
		} else {
			// Makes the browser not shoot to the top of the page on "cancel"
			return false;
		}
	});

	// Edit Content Blocks (Need to '.on' because we are working with dynamically generated content)
	$(document).on( 'click', '.blox-content-block-header', function() {
		//$( this ).siblings( '.blox-settings-tabs' ).toggle( 0 );
		$( this ).parents( '.blox-content-block' ).toggleClass( 'editing' );
	
		var editing = $( this ).siblings( '.blox-content-block-editing' );
		editing.prop( 'checked', !editing.prop( 'checked' ) );
	
		return false;

	}); 
	
	// Prevent content container from closing when you click on the title input
	$(document).on( 'click', '.blox-content-block-title-input', function(e) {
		e.stopPropagation();
	});
	
	// Make local blocks sortable
	$( '#blox_content_blocks_container' ).sortable({
		items: '.blox-content-block',
		cursor: 'move',
		handle: '.blox-content-block-header',
		forcePlaceholderSize: true,
		placeholder: 'placeholder'
	});
	
	// Updates our content block title field in real time
	$(document).on( 'keyup', '.blox-content-block-title-input input', function(e) {
		titleText = e.target.value;
		
		if ( titleText != '' ) {
			// If a new title has been added, update the title div
			$(this).parents( '.blox-content-block-title-input' ).siblings( '.blox-content-block-title' ).text( titleText );
		} else { 
			// If the title has been removed, add our "No Title" text
			$(this).parents( '.blox-content-block-title-input' ).siblings( '.blox-content-block-title' ).html( '<span class="no-title">No Title</span>' );
		}
	});
	
	// On global blocks, preserve current tab on save an on page refresh
	if ( $( 'body' ).hasClass( 'post-type-blox' ) ) {
		var blox_tabs_hash 	    = window.location.hash,
			blox_tabs_hash_sani = window.location.hash.replace('!', '');

		// If we have a hash and it begins with "soliloquy-tab", set the proper tab to be opened.
		if ( blox_tabs_hash && blox_tabs_hash.indexOf( 'blox_tab_' ) >= 0 ) {
			$( '.blox-tab-navigation li' ).removeClass( 'current' );
			$( '.blox-tab-navigation' ).find( 'li a[href="' + blox_tabs_hash_sani + '"]' ).parent().addClass( 'current' );
			$( '.blox-tabs-container' ).children().hide();
			$( '.blox-tabs-container' ).children( blox_tabs_hash_sani ).show();

			// Update the post action to contain our hash so the proper tab can be loaded on save.
			var post_action = $( '#post' ).attr( 'action' );
			if ( post_action ) {
				post_action = post_action.split( '#' )[0];
				$( '#post' ).attr( 'action', post_action + blox_tabs_hash );
			}
		}
	}

	// Show desired tab on click
  	$(document).on( 'click', '.blox-tab-navigation a', function(e) {
		e.preventDefault();
		
		if ( $( this ).parent().hasClass( 'current' ) ) {
			return;
		} else {
			// Adds current class to active tab heading
			$( this ).parent().addClass( 'current' );
			$( this ).parent().siblings().removeClass( 'current' );
			
			var tab = $( this ).attr( 'href' );
			
			if ( $( this ).parents( '.blox-settings-tabs' ).hasClass( 'global' ) ) {
				
				// We add the ! so the addition of the hash does not cause the page to jump
				window.location.hash = $( this ).attr( 'href' ).split( '#' ).join( '#!' );
		
				// Update the post action to contain our hash so the proper tab can be loaded on save.
                var post_action = $( '#post' ).attr( 'action' );
                if ( post_action ) {
                    post_action = post_action.split('#')[0];
                    $( '#post' ).attr( 'action', post_action + window.location.hash );
                }
				
			}
		
			// Show the correct tab
			$(this).parents( '.blox-tab-navigation' ).siblings( '.blox-tabs-container' ).children( '.blox-tab-content' ).not( tab ).hide();
			$(this).parents( '.blox-tab-navigation' ).siblings( '.blox-tabs-container' ).children( tab ).show();
		}
        
    });
    
    // Script to add empty block when button is clicked
	$(document).on( 'click', '#blox_add_block', function(e) {

		e.preventDefault();
		
		// Get the block id for targeting purposes
		var block_id = null;
		
		// Get the post id for saving purposes
		var post_id = $( '#post_ID' ).attr( 'value' );
		
		// Store callback method name and nonce field value in an array.
		var data = {
			action: 'blox_add_block', // AJAX callback
			post_id: post_id,
			block_id: block_id,
			type: 'new',
			blox_add_block_nonce: blox_localize_metabox_scripts.blox_add_block_nonce
		};
		
		// AJAX call.
		$.post( blox_localize_metabox_scripts.ajax_url, data, function( response ) {		
			$('#blox_content_blocks_container').prepend(response);
			$('#blox_content_blocks_container .blox-content-block').first().addClass( 'editing new' );
			$('#blox_content_blocks_container .blox-content-block').first().children( '.blox-content-block-editing' ).prop( 'checked', true );
			
			// Run when new block is added so default content is visible
			show_selected_content();
			
			// Hide the Add Block button description if it is there. 
			$( '#blox_add_block_description' ).hide();
		});

	});
	
	// Script to replicate an existing block when button is clicked
	$(document).on( 'click', '.blox-replicate-block', function(e) {

		e.preventDefault();
		
		// Get the block id for targeting purposes
		var block_id = $( this ).parents( '.blox-content-block' ).attr( 'id' );
		
		// Get the post id for saving purposes
		var post_id = $( '#post_ID' ).attr( 'value' );
		
		//alert ( block_id );

		// Store callback method name and nonce field value in an array.
		var data = {
			action: 'blox_add_block', // AJAX callback
			post_id: post_id,
			block_id: block_id,
			type: 'copy',
			blox_add_block_nonce: blox_localize_metabox_scripts.blox_add_block_nonce
		};
		
		// AJAX call.
		$.post( blox_localize_metabox_scripts.ajax_url, data, function( response ) {		
			$('#blox_content_blocks_container').prepend(response);
			$('#blox_content_blocks_container .blox-content-block').first().addClass( 'editing new' );
			$('#blox_content_blocks_container .blox-content-block').first().children( '.blox-content-block-editing' ).prop( 'checked', true );
			
			// Run when new block is added so default content is visible
			show_selected_content();
		});
		
		// Stop any additional js from firing after replication
		e.stopPropagation();

	});
	
});