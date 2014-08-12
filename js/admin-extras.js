/**
 * Admin extras backend JS.
 */
//window.wp = window.wp || {};

( function( window, $, undefined ) {
	var editorOuter   = $( '#wporg_editor_outer' ),
		ticketNumber  = $( '#wporg_parsed_ticket' ),
		attachButton  = $( '#wporg_ticket_attach' ),
		detachButton  = $( '#wporg_ticket_detach' ),
		ticketInfo    = $( '#wporg_parsed_ticket_info' ),
		spinner       = $( '#ticket_status .spinner' );

	var handleTicket = function( event ) {
		event.preventDefault();

		var $this        = $(this),
			attachAction = 'attach' == event.data.action;

		spinner.css( 'display', 'inline-block' );

		if ( attachAction ) {
			ticketInfo.text( wporg.searchText );
		}

		var data = {
			action:  attachAction ? 'wporg_attach_ticket' : 'wporg_detach_ticket',
			ticket:  ticketNumber.val(),
			nonce:   $this.data( 'nonce' ),
			post_id: editorOuter.data( 'id' )
		};

		$.post( wporg.ajaxURL, data, function( resp ) {
			// Refresh the nonce.
			$this.data( 'nonce', resp.new_nonce );

			spinner.hide();
			ticketInfo.text( resp.message ).show();

			// Handle the response.
			if ( resp.type && 'success' == resp.type ) {
				spinner.hide();

				var otherButton = attachAction ? detachButton : attachButton;

				// Toggle the buttons.
				$this.hide();
				otherButton.css( 'display', 'inline-block' );

				// Hide or show the editor.
				attachAction ? editorOuter.slideDown() : editorOuter.slideUp().delay( 400 );

				// Clear the ticket number when detaching.
				if ( ! attachAction ) {
					ticketNumber.val( '' );
				}

				// Set or unset the ticket link icon.
				$( '.ticket_info_icon' ).toggleClass( 'dashicons dashicons-external', attachAction );

				// Set the ticket number to readonly when a ticket is attached.
				attachAction ? ticketNumber.prop( 'readonly', 'readonly' ) : ticketNumber.removeAttr( 'readonly' );
			} else {
				ticketInfo.text( wporg.retryText );
			}
		}, 'json' );
	};

	attachButton.on( 'click', { action: 'attach' }, handleTicket );
	detachButton.on( 'click', { action: 'detach' }, handleTicket );

} )( this, jQuery );