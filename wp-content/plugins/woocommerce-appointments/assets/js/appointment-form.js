/* globals wc_appointment_form_params, get_querystring */
jQuery(document).ready(function($) {
	'use strict';

	var xhr = [];

	// window.onpopstate = function(event) {
	// 	if ( event.state !== null ) {
	// 		var date = event.state.date,
	// 			time = event.state.time;

	// 		var $time_selector = $( 'li.slot:not(".slot_empty") a[data-value="' + time + '"]' ),
	// 			$day_selector = $( '.ui-datepicker-calendar a:contains(' + date.split('-')[2] + ')' );
			
	// 		if ( $day_selector.length > 0 ) {
	// 			$day_selector.click();
	// 		}
			
	// 		if ( $time_selector.length > 0 ) {
	// 			$time_selector.click();
	// 		}
	// 	}
	// };

	var wc_appointments_appointment_form = {
		init: function() {
			// if duration unit is day then there is no time picker present
			// bind cost calculation on the staff and date selection only
			if ( wc_appointment_form_params.duration_unit === 'day' ) {
				$( '.wc-appointments-appointment-form' ).parents('form')
					.on( 'staff-selected', this.calculate_costs)
					.on( 'date-selected', this.calculate_costs)
					.each( this.appointment_form_init );
			// if duration unit is hours or minutes then there is time picker present
			// bind cost calculation on the staff and time selection
			} else {
				$( '.wc-appointments-appointment-form' ).parents('form')
					.on( 'staff-selected', this.calculate_costs)
					.on( 'time-selected', this.calculate_costs)
					.each( this.appointment_form_init );
			}
			$( '.wc-appointments-appointment-hook' ).on( 'change', 'input, select', this.calculate_costs );
			$( '.quantity' ).on( 'change', 'input, select', this.calculate_costs );
			$( '.wc-appointments-appointment-form, .wc-appointments-appointment-form-button' ).show().removeAttr( 'disabled' );
			$( '.single_add_to_cart_button' ).on( 'click', this.add_to_cart_init );
		},
		appointment_form_init: function() {
			$(this).find('.single_add_to_cart_button').addClass('disabled');
		},
		add_to_cart_init: function( event ) {
			if ( $(this).hasClass( 'disabled' ) ) {
				window.alert( wc_appointment_form_params.i18n_choose_options );
				event.preventDefault();
				return false;
			}
		},
		calculate_costs: function() {
			var name             = $(this).attr( 'name' ),
				$form            = $(this).closest('form'),
				$required_fields = $form.find('input.required_for_calculation'),
				filled           = true,
				$fieldset        = $form.find( 'fieldset' ),
				$picker          = $fieldset.find( '.picker:eq(0)' ),
				index            = $form.index();

			// what does this line do? 
			// when is range picker present?
			// if it is future implementation it should be removed
			if ( $picker.data( 'is_range_picker_enabled' ) ) {
				if ( 'wc_appointments_field_duration' !== name ) {
					return;
				}
			}

			// abort all requests for this form
			if ( xhr[index] ) {
				xhr[index].abort();
			}

			$.each( $required_fields, function( index, field ) {
				var value = $(field).val();
				if ( ! value ) {
					filled = false;
				}
			});

			if ( ! filled ) {
				$form.find('.wc-appointments-appointment-cost').hide();
				$form.find('.wc-appointments-appointment-hook').hide();
				return;
			}

			$form.find('.wc-appointments-appointment-cost').block({message: null, overlayCSS: {background: '#fff', backgroundSize: '16px 16px', opacity: 0.6}}).show();

			xhr[index] = $.ajax({
				type: 'POST',
				url: wc_appointment_form_params.ajax_url,
				data: {
					action: 'wc_appointments_calculate_costs',
					form: $form.serialize()
				},
				success: function( code ) {
					if ( code.charAt(0) !== '{' ) {
						// console.log( code );
						code = '{' + code.split(/\{(.+)?/)[1];
					}

					var result = $.parseJSON( code );

					if ( result.result === 'ERROR' ) {
						$form.find('.wc-appointments-appointment-cost').html( result.html );
						$form.find('.wc-appointments-appointment-cost').unblock();
						$form.find('.single_add_to_cart_button').addClass('disabled');
					} else if ( result.result === 'SUCCESS' ) {
						$form.find('.wc-appointments-appointment-cost').html( result.html );
						$form.find('.wc-appointments-appointment-cost').unblock();
						$form.find('.wc-appointments-appointment-hook').show();
						$form.find('.single_add_to_cart_button').removeClass('disabled');
						if ( !wc_appointment_form_params.is_admin ) {
							wc_appointments_appointment_form.update_querystring_date_time( $form );
						}

					} else {
						$form.find('.wc-appointments-appointment-cost').hide();
						$form.find('.wc-appointments-appointment-hook').hide();
						$form.find('.single_add_to_cart_button').addClass('disabled');
						// console.log( code );
					}
				},
				error: function() {
					$form.find('.wc-appointments-appointment-cost').hide();
					$form.find('.single_add_to_cart_button').addClass('disabled');
				},
				dataType: 'html'
			});
		},
		// function which changes the url querystring parameters without reloading the page (if supported in browser)
		update_querystring_date_time: function( form ) {
			// if browser supports this feature, use it
			if ( window.history && window.history.pushState ) {
				var year    = form.find( 'input.appointment_date_year' ).val(),
					month   = form.find( 'input.appointment_date_month' ).val(),
					day     = form.find( 'input.appointment_date_day' ).val(),
					$time   = form.find('#wc_appointments_field_start_date'),
					date    = year + '-' + month + '-' + day,
					old_url = window.location.href,
					new_url = wc_appointments_appointment_form.replace_url_parameter( 'date', date, old_url );
				if ( $time.length > 0 && $time.val() !== '' ) {
					new_url = wc_appointments_appointment_form.replace_url_parameter( 'time', $time.val(), new_url );
					window.history.replaceState( { date: date, time: $time.val() }, null, new_url );
				} else {
					window.history.replaceState( { date: date, time: null }, null, new_url );
				}
			}
		},
		// function which checks passed url and adds or replaces querystring in it 
		replace_url_parameter: function(param, value, href) {
		    var matches_as_qs_param = href.match(/[a-z\d]+=[a-z\d]+/gi),
				count_qs_params 	= matches_as_qs_param ? matches_as_qs_param.length : 0;
		    if ( null === get_querystring( param ) ) {
		    	href += ( count_qs_params > 0 ? '&' : '?' ) + param + '=' + encodeURIComponent( value );
		    } else {
		    	var regex = new RegExp('('+param+'=)[^\&]+');
				href = href.replace( regex, '$1' + encodeURIComponent( value ) );
		    }
		    return href;
		}

	};

	wc_appointments_appointment_form.init();

});
