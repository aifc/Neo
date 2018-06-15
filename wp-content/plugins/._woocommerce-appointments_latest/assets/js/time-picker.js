/* globals wc_appointment_form_params, get_querystring, jstz */
jQuery( document ).ready( function( $ ) {
	'use strict';

	var xhr;

	var wc_appointments_time_picker = {
		init: function() {
			// $( '.slot-picker' ).on( 'click', 'a', this.time_picker_init );
			$( '.slot-picker' ).on( 'click', 'a', function() {
				var form   = $(this).closest( 'form' ),
					value  = $(this).parent().data( 'slot' );

				wc_appointments_time_picker.set_selected_time( form, value );

				return false;
			});
			$( 'body' ).on( 'change', '#wc_appointments_field_staff', function() {
				wc_appointments_time_picker.show_available_time_slots( this );
			});
			$( '.wc-appointments-appointment-form' ).parents('form').on( 'date-selected', function() {
				wc_appointments_time_picker.show_available_time_slots( this );
			});
		},
		time_picker_init: function() {
			var value  = $(this).data( 'value' ),
				target = $(this).parents( '.form-field' ).find( 'input' ),
				form   = $(this).closest( 'form' );

			// $target.val( value ).change();
			target.val( value );

			$(this).parents('.form-field').find('li').removeClass('selected');
			$(this).parents('li').addClass('selected');

			form.triggerHandler( 'time-selected' );

			return false;
		},
		set_selected_time: function( form, value ) {
			var submit_button = form.find( '.wc-appointments-appointment-form-button' );
			var slot_picker   = form.find( '.slot-picker' );

			submit_button.addClass( 'disabled' );

			if ( undefined === value ) {
				//submit_button.addClass( 'disabled' );
				form.triggerHandler( 'time-selected' );
				return;
			}

			var selected_slot = slot_picker.find( '[data-slot="' + value + '"]' ),
			    selected_slot_value = selected_slot.find( 'a' ).data( 'value' );

			if ( undefined === selected_slot.data( 'slot' ) ) {
				//submit_button.addClass( 'disabled' );
				form.triggerHandler( 'time-selected' );
				return;
			}

			var target = form.find( '.slot-picker' ).parent( 'div' ).find( 'input' );

			// Add selected time value to input.
			// target.val( value ).change();
			target.val( selected_slot_value ); // timeslot in local format
			target.attr( 'data-value', value ); // timeslot in Hi format

			form.triggerHandler( 'time-selected' );

			slot_picker.find( 'li' ).removeClass( 'selected' );
			selected_slot.addClass( 'selected' );
		},
		show_available_time_slots: function( element ) {
			var form            = $( element ).closest( 'form' ),
				slot_picker     = form.find( '.slot-picker' ),
				fieldset        = form.find( 'fieldset' ),
				year            = parseInt( fieldset.find( 'input.appointment_date_year' ).val(), 10 ),
				month           = parseInt( fieldset.find( 'input.appointment_date_month' ).val(), 10 ),
				day             = parseInt( fieldset.find( 'input.appointment_date_day' ).val(), 10 ),
				selected_slot   = slot_picker.find( '.selected' );

			if ( ! year || ! month || ! day ) {
				return;
			}

			// clear slots
			slot_picker.closest('div').find('input').val( '' ).change();
			slot_picker.closest('div').block({message: null, overlayCSS: {background: '#fff', backgroundSize: '16px 16px', opacity: 0.6}}).show();

			// Prevent multiple requests at once.
			// if ( xhr ) xhr.abort();

			xhr = $.ajax({
				type: 'POST',
				url: wc_appointment_form_params.ajax_url,
				data: {
					action: 'wc_appointments_get_slots',
					form: form.serialize(),
					timezone: jstz.determine().name()
				},
				success: function( code ) {
					slot_picker.html( code );
					slot_picker.closest('div').unblock();
					wc_appointments_time_picker.set_selected_time( form, selected_slot.data( 'slot' ) );

					// if time is in querystring, select it instead of the first time
					// it overrides autoselect setting
					if ( get_querystring('time') !== null ) {
						var selected_time = slot_picker.find( 'li.slot[data-slot="' + get_querystring('time') + '"]' ).not( '.slot_empty' );

						if ( selected_time.length > 0 ) {
							selected_time.find('a').click();
						} else {
							// window.alert( wc_appointment_form_params.i18n_time_unavailable );
							wc_appointments_time_picker.autoselect_first_available_time( form );
						}
					// Auto select first available time
					} else if ( wc_appointment_form_params.is_autoselect ) {
						wc_appointments_time_picker.autoselect_first_available_time( form );
					}
				},
				dataType: 'html'
			});
		},
		autoselect_first_available_time: function( form ) {
			var slot_picker = form.find('.slot-picker'),
				first_time  = slot_picker.find( 'li.slot:not(".slot_empty"):first' );

			if ( first_time.length > 0 && first_time.has( 'a' ) ) {
				first_time.find( 'a' ).click();
			}
		}
	};

	wc_appointments_time_picker.init();
});
