/* globals get_querystring */
jQuery( function( $ ) {
	'use strict';

	var wc_appointments_staff_picker = {
		init: function() {
			var staff_select = $( '#wc_appointments_field_staff' ).select2({
				escapeMarkup: function( m ) {
					return m;
				},

				templateResult: wc_appointments_staff_picker.template_staff,
				templateSelection: wc_appointments_staff_picker.template_staff,
				minimumResultsForSearch: 6 // I only want the search box if there are enough results
			});

			// if staff is in querystring, then set it.
			if ( get_querystring( 'staff' ) !== null ) {
				staff_select.val( get_querystring( 'staff' ) ).trigger( 'change' );
			}

			$( 'body' ).on( 'change', '#wc_appointments_field_staff', this.select_staff );
		},
		template_staff: function( state ) {
			if ( ! state.id ) {
				return state.text;
			}

			var html5data = state.element;

			if ( $( html5data ).data('avatar') ) {
				return '<img class="staff-avatar" src="' + $( html5data ).data('avatar') + '" alt="'+ state.text + '" />' + state.text;
			}

			return state.text;

		},
		select_staff: function() {
			var form   = $(this).closest('form');

			form.find('.wc-appointments-appointment-form-button').addClass( 'disabled' );

			form.triggerHandler( 'staff-selected' );
		}
	};

	wc_appointments_staff_picker.init();
});
