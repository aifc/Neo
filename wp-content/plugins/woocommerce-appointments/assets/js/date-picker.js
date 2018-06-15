// function which gets parameter value from the querystring using its key
function get_querystring( key ) {
	'use strict';
	
    key = key.replace(/[*+?^$.\[\]{}()|\\\/]/g, '\\$&'); // escape RegEx meta chars
    var match = location.search.match(new RegExp('[?&]'+key+'=([^&]+)(&|$)'));
    return match && decodeURIComponent( match[1].replace(/\+/g, ' ') );
}

// function which checks if date string is valid
function is_valid_date( string ) {
	'use strict';

	var comp = string.split('-');
	var y = parseInt( comp[0], 10 );
	var m = parseInt( comp[1], 10 );
	var d = parseInt( comp[2], 10 );
	var date = new Date( y, m-1, d );
	if ( y === date.getFullYear() && m === date.getMonth() + 1 && d === date.getDate() ) {
	  return true;
	} else {
	  return false;
	}
}

/* globals wc_appointment_form_params */
jQuery( document ).ready( function( $ ) {
	'use strict';
	
	var startDate,
		endDate,
		duration = wc_appointment_form_params.appointment_duration,
		days_needed = ( duration < 1 ) ? 1 : duration,
		days_highlighted = days_needed,
		days_array = [],
		wc_appointments_date_picker = {
			init: function() {
				$( 'body' ).on( 'change', '#wc_appointments_field_staff', this.date_picker_init );
				$( '.wc-appointments-date-picker legend small.wc-appointments-date-picker-choose-date' ).show();
				$( '.wc-appointments-date-picker' ).each( function() {
					var $form     = $( this ).closest( 'form' ),
						$picker   = $form.find( '.picker' ),
						$fieldset = $( this ).closest( 'fieldset' );

					wc_appointments_date_picker.date_picker_init( $picker );

					$( '.wc-appointments-date-picker-date-fields', $fieldset ).hide();
					$( '.wc-appointments-date-picker-choose-date', $fieldset ).hide();
				} );
			},
			date_picker_init: function( element ) {
				var $picker = $( element );
				if ( $( element ).is( '.picker' ) ) {
					$picker = $( element );
				} else {
					$picker = $( this ).closest( 'form' ).find( '.picker' );
				}

				// if date is in querystring and it is valid, then set it as default date for datepicker
				if ( get_querystring( 'date' ) !== null ) {
					if ( is_valid_date( get_querystring( 'date' ) ) ) {
						$picker.attr( 'data-default_date', get_querystring( 'date' ) );
					} else {
						window.alert( wc_appointment_form_params.i18n_date_invalid );
					}
				}

				$picker.empty().removeClass( 'hasDatepicker' ).datepicker({
					dateFormat: $.datepicker.ISO_8601,
					showWeek: false,
					showOn: false,
					beforeShowDay: wc_appointments_date_picker.is_appointable,
					onSelect: wc_appointments_date_picker.select_date_trigger,
					minDate: $picker.data( 'min_date' ),
					maxDate: $picker.data( 'max_date' ),
					defaultDate: $picker.data( 'default_date'),
					numberOfMonths: 1,
					showButtonPanel: false,
					showOtherMonths: false,
					selectOtherMonths: false,
					closeText: wc_appointment_form_params.closeText,
					currentText: wc_appointment_form_params.currentText,
					prevText: wc_appointment_form_params.prevText,
					nextText: wc_appointment_form_params.nextText,
					monthNames: wc_appointment_form_params.monthNames,
					monthNamesShort: wc_appointment_form_params.monthNamesShort,
					dayNames: wc_appointment_form_params.dayNames,
					/* dayNamesShort: wc_appointment_form_params.dayNamesShort, */
					dayNamesMin: wc_appointment_form_params.dayNamesShort,
					firstDay: wc_appointment_form_params.firstDay,
					gotoCurrent: true
				});
				
				// if date is in querystring, select it instead of the first day
				// it overrides autoselect setting
				// Note: don't autoselect on change events, like staff select box change
				var $curr_day;

				if ( null !== get_querystring( 'date' ) && 'change' !== element.type && is_valid_date( get_querystring( 'date' ) ) ) {
					$curr_day = $picker.find( '.ui-datepicker-current-day' );
					if ( $curr_day.length > 0 ) {
						$curr_day.click();
					} else {
						// window.alert( wc_appointment_form_params.i18n_date_unavailable );
						$curr_day.next().click();
					}
				// Auto select first available day if not in querystring
				} else if ( 'yes' === wc_appointment_form_params.is_autoselect && 'change' !== element.type && null === get_querystring( 'date' ) ) {
					$curr_day = $picker.find( '.ui-datepicker-current-day' );
					if ( $curr_day.hasClass( 'ui-datepicker-unselectable' ) ) {
						$curr_day.next().click();
					} else {
						$curr_day.click();
					}
				} else {
					$( '.ui-datepicker-current-day' ).removeClass( 'ui-datepicker-current-day' );
				}

				var form  = $picker.closest( 'form' ),
					year  = parseInt( form.find( 'input.appointment_date_year' ).val(), 10 ),
					month = parseInt( form.find( 'input.appointment_date_month' ).val(), 10 ),
					day   = parseInt( form.find( 'input.appointment_date_day' ).val(), 10 );

				if ( year && month && day ) {
					var date = new Date( year, month - 1, day );
					$picker.datepicker( 'setDate', date );
				}
			},
			select_date_trigger: function( date ) {
				var fieldset    = $( this ).closest( 'fieldset' ),
					form        = fieldset.closest( 'form' ),
					parsed_date = date.split( '-' ),
					year        = parseInt( parsed_date[0], 10 ),
					month       = parseInt( parsed_date[1], 10 ),
					day         = parseInt( parsed_date[2], 10 );
				
				//* Full appointment duration length
				startDate = new Date( year, month - 1, day );
				endDate = new Date( year, month - 1, day + ( parseInt( days_highlighted, 10 ) - 1 ) );
				
				// Set fields
				fieldset.find( 'input.appointment_to_date_year' ).val( '' );
				fieldset.find( 'input.appointment_to_date_month' ).val( '' );
				fieldset.find( 'input.appointment_to_date_day' ).val( '' );

				fieldset.find( 'input.appointment_date_year' ).val( parsed_date[0] );
				fieldset.find( 'input.appointment_date_month' ).val( parsed_date[1] );
				fieldset.find( 'input.appointment_date_day' ).val( parsed_date[2] ).change();

				form.triggerHandler( 'date-selected', date );
			},
			is_appointable: function( date ) {
				var $form                    = $( this ).closest( 'form' ),
					availability             = $( this ).data( 'availability' ),
					default_availability     = $( this ).data( 'default-availability' ),
					fully_scheduled_days     = $( this ).data( 'fully-scheduled-days' ),
					partially_scheduled_days = $( this ).data( 'partially-scheduled-days' ),
					remaining_scheduled_days = $( this ).data( 'remaining-scheduled-days' ),
					padding_days             = $( this ).data( 'padding-days' ),
					discounted_days          = $( this ).data( 'discounted-days' ),
					availability_span        = wc_appointment_form_params.availability_span,
					has_staff                = wc_appointment_form_params.has_staff,
					staff_assignment         = wc_appointment_form_params.staff_assignment,
					staff_id                 = 0,
					css_classes              = '',
					title                    = '',
					discounted_title         = '';

				// Get selected staff
				if ( $form.find( 'select#wc_appointments_field_staff' ).val() > 0 ) {
					staff_id = $form.find( 'select#wc_appointments_field_staff' ).val();
				}

				// Get days needed for slot - this affects availability
				var the_date   = new Date( date ),
					curr_date  = new Date(),
					year       = the_date.getFullYear(),
					month      = the_date.getMonth() + 1,
					day        = the_date.getDate(),
					curr_year  = curr_date.getFullYear(),
					curr_month = curr_date.getMonth() + 1,
					curr_day   = curr_date.getDate();

				// Fully scheduled?
				if ( fully_scheduled_days[ year + '-' + month + '-' + day ] ) {
					if ( fully_scheduled_days[ year + '-' + month + '-' + day ][0] || fully_scheduled_days[ year + '-' + month + '-' + day ][ staff_id ] ) {
						return [ false, 'fully_scheduled', wc_appointment_form_params.i18n_date_fully_scheduled ];
					}
				}
				
				// Padding days?
				if ( padding_days && padding_days[ year + '-' + month + '-' + day ] ) {
					return [ false, 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				if ( '' + year + month + day < wc_appointment_form_params.current_time ) {
					return [ false, 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				// Apply Partially scheduled class.
				if ( partially_scheduled_days && partially_scheduled_days[ year + '-' + month + '-' + day ] ) {
					if ( partially_scheduled_days[ year + '-' + month + '-' + day ][0] || partially_scheduled_days[ year + '-' + month + '-' + day ][ staff_id ] ) {
						css_classes = css_classes + 'partial_scheduled ';
					}
					// Percentage remaining for scheduling
					if ( remaining_scheduled_days[ year + '-' + month + '-' + day ][0] ) {
						css_classes = css_classes + 'remaining_scheduled_' + remaining_scheduled_days[ year + '-' + month + '-' + day ][0] + ' ';
					}
					else if ( remaining_scheduled_days[ year + '-' + month + '-' + day ][ staff_id ] ) {
						css_classes = css_classes + 'remaining_scheduled_' + remaining_scheduled_days[ year + '-' + month + '-' + day ][ staff_id ] + ' ';
					}
				}
				
				//* Select all days, when duration is longer than 1 day
				if ( date >= startDate && date <= endDate ) {
					css_classes = 'ui-datepicker-selected-day';
				}
				
				//* Select all days, when duration is longer than 1 day
				if ( new Date( year, month, day ) < new Date( curr_year, curr_month, curr_day ) ) {
					css_classes = css_classes + ' past_day';
				}
				
				//* Discounted day?
				if ( 'undefined' !== typeof discounted_days && discounted_days[ year + '-' + month + '-' + day ] ) {
					css_classes = css_classes + ' discounted_day';
					discounted_title = discounted_title + discounted_days[ year + '-' + month + '-' + day ];
				}
				
				if ( 'start' === availability_span ) {
					days_needed = 1;
				}
				
				var slot_args = {
					start_date           : date,
					number_of_days       : days_needed,
					fully_scheduled_days : fully_scheduled_days,
					availability         : availability,
					default_availability : default_availability,
					has_staff            : has_staff,
					staff_id             : staff_id,
					staff_assignment     : staff_assignment
				};
				
				var appointable = wc_appointments_date_picker.is_slot_appointable( slot_args );
				
				// Loop through all available days to see which ones are schedulable.
				// Note: build array of dates to check in with next date. Next date will override previous array and so on.
				if ( appointable && days_needed > 1 && ( -1 === css_classes.indexOf( 'past_day' ) ) ) {
					for ( var i = 0; i < days_needed; i++ ) {
						var next_date 	= new Date( date );
						next_date.setDate(the_date.getDate() + i);
						var n_year  = next_date.getFullYear(),
							n_month = next_date.getMonth() + 1,
							n_day   = next_date.getDate();
						
						if ( next_date.getDate() !== the_date.getDate() ) {
							days_array[i] = n_year + '-' + n_month + '-' + n_day;
						}
					}
				}
				
				if ( ! appointable ) {
					// Add .in_range class to available dates that are not clickable.
					if ( $.inArray( year + '-' + month + '-' + day, days_array ) > -1 ) {
						return [ appointable, css_classes + ' not_appointable in_range', wc_appointment_form_params.i18n_date_available ];
					}
					return [ appointable, css_classes + ' not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				} else {
					if ( css_classes.indexOf( 'partial_scheduled' ) > -1 ) {
						title = wc_appointment_form_params.i18n_date_partially_scheduled;
					} else if ( css_classes.indexOf( 'discounted_day' ) > -1 ) {
						title = discounted_title;
					} else if ( css_classes.indexOf( 'past_day' ) > -1 ) {
						title = wc_appointment_form_params.i18n_date_unavailable;
					} else {
						title = wc_appointment_form_params.i18n_date_available;
					}
					return [ appointable, css_classes + ' appointable', title ];
				}
			},
			is_slot_appointable: function( args ) {
				var appointable = args.default_availability;

				// Loop all the days we need to check for this slot.
				for ( var i = 0; i < args.number_of_days; i++ ) {
					var the_date     = new Date( args.start_date );
					the_date.setDate( the_date.getDate() + i );

					var year        = the_date.getFullYear(),
						month       = the_date.getMonth() + 1,
						day         = the_date.getDate(),
						day_of_week = the_date.getDay();

					// Sunday is 0, Monday is 1, and so on.
					if ( day_of_week === 0 ) {
						day_of_week = 7;
					}

					// Is staff available in current date?
					// Note: staff_id = 0 is product's availability rules.
					// Each staff rules also contains product's rules.
					var staff_args = {
						date: the_date,
						default_availability: args.default_availability
					};
					var staff_rules = args.availability[ args.staff_id ];
					appointable = wc_appointments_date_picker.is_staff_available_on_date( staff_args, staff_rules );


					// In case of automatic assignment we want to make sure at least one staff is available
					if ( ( 'automatic' === args.staff_assignment && args.has_staff ) || ( 0 === args.staff_id && args.has_staff ) ) {
						var automatic_staff_args = $.extend(
							{
								availability: args.availability,
								fully_scheduled_days: args.fully_scheduled_days
							},
							staff_args
						);

						appointable = wc_appointments_date_picker.has_available_staff( automatic_staff_args );
					}

					// Fully scheduled in entire slot?
					if ( args.fully_scheduled_days[ year + '-' + month + '-' + day ] ) {
						if ( args.fully_scheduled_days[ year + '-' + month + '-' + day ][0] || args.fully_scheduled_days[ year + '-' + month + '-' + day ][ args.staff_id ] ) {
							appointable = false;
						}
					}

					if ( ! appointable ) {
						break;
					}
				}

				return appointable;
			},
			/**
			 * Goes through all the rules and applies then to them to see if appointment is available
			 * for the given date.
			 *
			 * Rules are recursively applied. Rules later array will override rules earlier in the array if
			 * applicable to the block being checked.
			 *
			 * @param args
			 * @param rules array of rules in order from lowest override power to highest.
			 *
			 * @returns boolean
			 */
			is_staff_available_on_date: function( args, rules ) {
				var availability    = args.default_availability,
					availability_d  = false,
					availability_de = false,
					availability_t  = false,
					availability_te = false,
					year            = args.date.getFullYear(),
					month           = args.date.getMonth() + 1,
					day             = args.date.getDate(),
					day_of_week     = args.date.getDay(),
					week            = $.datepicker.iso8601Week( args.date );

				// Sunday is 0, Monday is 1, and so on.
				if ( day_of_week === 0 ) {
					day_of_week = 7;
				}

				// `args.fully_scheduled_days` and `args.staff_id` only available when checking 'automatic' staff assignment.
				if ( args.fully_scheduled_days && args.fully_scheduled_days[ year + '-' + month + '-' + day ] && args.fully_scheduled_days[ year + '-' + month + '-' + day ][ args.staff_id ] ) {
					return false;
				}

				$.each( rules, function( index, rule ) {
					var type  = rule.type; // rule['type']
					var range = rule.range; // rule['range']
					
					// must be Object and not array.
					if ( $.isArray( range ) ) {
						return true;
					}

					try {
						switch ( type ) {
							case 'months':
								if ( 'undefined' !== typeof range[ month ] ) {
									availability_d = range[ month ]; // rule
									availability_de = true; // exists
									return true; // go to the next rule
								}
							break;
							case 'weeks':
								if ( 'undefined' !== typeof range[ week ] ) {
									availability_d = range[ week ]; // rule
									availability_de = true; // exists
									return true; // go to the next rule
								}
							break;
							case 'days':
								if ( 'undefined' !== typeof range[ day_of_week ] ) {
									availability_d = range[ day_of_week ]; // rule
									availability_de = true; // exists
									return true; // go to the next rule
								}
							break;
							case 'custom':
								if ( 'undefined' !== typeof range[ year ][ month ][ day ] ) {
									availability_d = range[ year ][ month ][ day ]; // rule
									availability_de = true; // exists
									return true; // go to the next rule
								}
							break;
							case 'time':
							case 'time:1':
							case 'time:2':
							case 'time:3':
							case 'time:4':
							case 'time:5':
							case 'time:6':
							case 'time:7':
								if ( false === args.default_availability && ( day_of_week === range.day || 0 === range.day ) ) {
									availability_t = range.rule; // rule
									availability_te = true;
									return true; // go to the next rule
								}
							break;
							case 'time:range':
								if ( false === args.default_availability && ( typeof range[ year ][ month ][ day ] !== 'undefined' ) ) {
									// This function only checks to see if a date is available and this rule
									// only covers a few hours in a given date so as far as this rule is concerned a given
									// date may always be available as there are hours outside of the scope of this rule.
									availability_d = true; // rule
									availability_de = true; // exists
								}
							break;
							
						}

					} catch( err ) {
						return true; // go to the next rule
					}
				});
				
				// If rules for date exist and date is available
				if ( availability_de && availability_d ) {
					return availability_d;
				// If rules for date exist and date is not available
				} else if ( availability_de && ! availability_d ) {
					return availability_d;
				// If rules for date don't exist and time rule exists and is true
				} else if ( ! availability_de && availability_te && availability_t ) {
					return availability_t;
				// Default availability
				} else {
					return availability;
				}

			},
			has_available_staff: function( args ) {
				for ( var staff_id in args.availability ) {
					if ( args.availability.hasOwnProperty(staff_id) ) {
						staff_id = parseInt( staff_id, 10 );

						// Skip staff_id '0' that has been performed before.
						if ( 0 === staff_id ) {
							continue;
						}
						
						var staff_rules = args.availability[ staff_id ];
						args.staff_id = staff_id;
						if ( wc_appointments_date_picker.is_staff_available_on_date( args, staff_rules ) ) {
							return true;
						}
					}
				}

				return false;
			}
		};

	wc_appointments_date_picker.init();
});
