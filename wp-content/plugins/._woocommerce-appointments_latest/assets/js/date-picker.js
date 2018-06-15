/* globals wc_appointment_form_params, get_querystring, is_valid_date */
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
				$( 'body' ).on( 'input', '.appointment_date_year, .appointment_date_month, .appointment_date_day', this.input_date_trigger );
				$( '.wc-appointments-date-picker' ).each( function() {
					var form     = $( this ).closest( 'form' ),
						picker   = form.find( '.picker' ),
						fieldset = $( this ).closest( 'fieldset' );

					wc_appointments_date_picker.date_picker_init( picker );

					//picker.hide();

					$( '.wc-appointments-date-picker-date-fields', fieldset ).hide();
					$( '.wc-appointments-date-picker-choose-date', fieldset ).hide();
				} );
			},
			date_picker_init: function( element ) {
				var picker = $( element );
				if ( $( element ).is( '.picker' ) ) {
					picker = $( element );
				} else {
					picker = $( this ).closest( 'form' ).find( '.picker' );
				}

				// if date is in querystring and it is valid, then set it as default date for datepicker
				if ( get_querystring( 'date' ) !== null ) {
					if ( is_valid_date( get_querystring( 'date' ) ) ) {
						picker.attr( 'data-default_date', get_querystring( 'date' ) );
					} else {
						window.alert( wc_appointment_form_params.i18n_date_invalid );
					}
				}

				picker.empty().removeClass( 'hasDatepicker' ).datepicker({
					dateFormat: $.datepicker.ISO_8601,
					showWeek: false,
					showOn: false,
					beforeShowDay: wc_appointments_date_picker.is_appointable,
					onSelect: wc_appointments_date_picker.select_date_trigger,
					minDate: picker.data( 'min_date' ),
					maxDate: picker.data( 'max_date' ),
					defaultDate: picker.data( 'default_date' ),
					numberOfMonths: 1,
					showButtonPanel: false,
					showOtherMonths: true,
					selectOtherMonths: true,
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
					gotoCurrent: true,
					isRTL: wc_appointment_form_params.isRTL
				});

				var curr_day;
				var next_selectable_day;
				var next_selectable_el;

				// Auto-select first available day.
				// If date is in querystring, select it instead of the first day
				// it overrides autoselect setting.
				// Note: don't autoselect on change events, like staff select box change
				if (
					'change' !== element.type && (
						// Auto-select, when NO date querystring is present.
						( null === get_querystring( 'date' ) && wc_appointment_form_params.is_autoselect ) ||
						// Auto-select, when date querystring is present.
						( null !== get_querystring( 'date' ) && is_valid_date( get_querystring( 'date' ) ) )
					)
				) {
					curr_day = picker.find( '.ui-datepicker-current-day' );
					if ( curr_day.hasClass( 'ui-datepicker-unselectable' ) ) {

						// Repeat for next 12 months max.
						for ( var i = 1; i < 12; i++ ) {
							next_selectable_day = wc_appointments_date_picker.find_available_date_within_month();
							next_selectable_el = wc_appointments_date_picker.filter_selectable_day( $('.ui-state-default'), next_selectable_day );
							//next_selectable_el = $('.ui-state-default').filter( function() {
							//	return ( Number( $(this).text() ) === next_selectable_day );
							//});

							// Found available day, break the loop.
							if ( next_selectable_el.length > 0 ) {
								next_selectable_el.click();
								break;
							} else {
								picker.find( '.ui-datepicker-next' ).click();
							}
						}

					} else {
						curr_day.click();
					}
				} else {
					$( '.ui-datepicker-current-day' ).removeClass( 'ui-datepicker-current-day' );
				}

				var form  = picker.closest( 'form' ),
					year  = parseInt( form.find( 'input.appointment_date_year' ).val(), 10 ),
					month = parseInt( form.find( 'input.appointment_date_month' ).val(), 10 ),
					day   = parseInt( form.find( 'input.appointment_date_day' ).val(), 10 );

				if ( year && month && day ) {
					var date = new Date( year, month - 1, day );
					picker.datepicker( 'setDate', date );
				}
			},
			filter_selectable_day: function( a, b ) {
				var next_selectable_el = a.filter( function() {
					return ( Number( $(this).text() ) === b );
				});
				return next_selectable_el;
			},
			input_date_trigger: function() {
				var $fieldset = $(this).closest('fieldset'),
					$picker   = $fieldset.find( '.picker:eq(0)' ),
					year      = parseInt( $fieldset.find( 'input.appointment_date_year' ).val(), 10 ),
					month     = parseInt( $fieldset.find( 'input.appointment_date_month' ).val(), 10 ),
					day       = parseInt( $fieldset.find( 'input.appointment_date_day' ).val(), 10 );

				if ( year && month && day ) {
					var date = new Date( year, month - 1, day );
					$picker.datepicker( 'setDate', date );
					$fieldset.triggerHandler( 'date-selected', date );
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

				form.find('.wc-appointments-appointment-form-button').addClass( 'disabled' );

				form.triggerHandler( 'date-selected', date );
			},
			is_appointable: function( date ) {
				var form                     = $( this ).closest( 'form' ),
					picker                   = form.find( '.picker:eq(0)' ),
					availability             = $( this ).data( 'availability' ),
					default_availability     = $( this ).data( 'default-availability' ),
					fully_scheduled_days     = $( this ).data( 'fully-scheduled-days' ),
					unavailable_days         = $( this ).data( 'unavailable-days' ),
					partially_scheduled_days = $( this ).data( 'partially-scheduled-days' ),
					remaining_scheduled_days = $( this ).data( 'remaining-scheduled-days' ),
					restricted_days          = $( this ).data( 'restricted-days' ),
					padding_days             = $( this ).data( 'padding-days' ),
					discounted_days          = $( this ).data( 'discounted-days' ),
					availability_span        = wc_appointment_form_params.availability_span,
					has_staff                = wc_appointment_form_params.has_staff,
					staff_assignment         = wc_appointment_form_params.staff_assignment,
					staff_id                 = 0,
					css_classes              = '',
					title                    = '',
					discounted_title         = '';

				// Get selected staff.
				if ( form.find( 'select#wc_appointments_field_staff' ).val() > 0 ) {
					staff_id = form.find( 'select#wc_appointments_field_staff' ).val();
				}

				// Get days needed for slot - this affects availability
				var the_date   	     = new Date( date ),
					curr_date  	     = new Date(),
					year       	     = the_date.getFullYear(),
					month            = the_date.getMonth() + 1,
					day              = the_date.getDate(),
					day_of_week      = the_date.getDay(),
					curr_year  	     = curr_date.getFullYear(),
					curr_month 	     = curr_date.getMonth() + 1,
					curr_day         = curr_date.getDate(),
					ymdIndex         = year + '-' + month + '-' + day,
					minDate          = picker.datepicker( 'option', 'minDate' ),
					dateMin    	     = wc_appointments_date_picker.get_relative_date( minDate );

				// Add day of week class.
				css_classes = css_classes + 'weekday-' + day_of_week + ' ';

				// Make sure minDate is accounted for.
				// Convert compared dates to format with leading zeroes.
				if ( wc_appointments_date_picker.get_format_date( the_date ) < wc_appointments_date_picker.get_format_date( dateMin ) && parseInt( minDate ) !== 0 ) {
					return [ false, 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				// Select all days, when duration is longer than 1 day
				if ( date >= startDate && date <= endDate ) {
					css_classes = css_classes + 'ui-datepicker-selected-day ';
				}

				// Fully scheduled?
				if ( fully_scheduled_days[ ymdIndex ] ) {
					if ( fully_scheduled_days[ ymdIndex ][0] || fully_scheduled_days[ ymdIndex ][ staff_id ] ) {
						return [ false, 'fully_scheduled', wc_appointment_form_params.i18n_date_fully_scheduled ];
					} else if ( 'automatic' === staff_assignment || ( has_staff && 0 === staff_id ) ) {
						css_classes = css_classes + 'partial_scheduled ';
					}
				}

				// Unavailable days?
				if ( unavailable_days && unavailable_days[ ymdIndex ] && unavailable_days[ ymdIndex][ staff_id ] ) {
					return [ false, css_classes + 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				// Padding days?
				if ( padding_days && padding_days[ ymdIndex ] ) {
					return [ false, css_classes + 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				if ( '' + year + month + day < wc_appointment_form_params.current_time ) {
					return [ false, css_classes + 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
				}

				// Restricted days?
				if ( restricted_days && undefined === restricted_days[ day_of_week ] ) {
	  				return [ false, css_classes + 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
	  			}

				// Apply Partially scheduled class.
				if ( partially_scheduled_days && partially_scheduled_days[ ymdIndex ] ) {
					if ( 'automatic' === staff_assignment || ( has_staff && 0 === staff_id ) || partially_scheduled_days[ ymdIndex ][0] || partially_scheduled_days[ ymdIndex ][ staff_id ] ) {
						css_classes = css_classes + 'partial_scheduled ';
					}
					// Percentage remaining for scheduling
					if ( remaining_scheduled_days[ ymdIndex ][0] ) {
						css_classes = css_classes + 'remaining_scheduled_' + remaining_scheduled_days[ ymdIndex ][0] + ' ';
					} else if ( remaining_scheduled_days[ ymdIndex ][ staff_id ] ) {
						css_classes = css_classes + 'remaining_scheduled_' + remaining_scheduled_days[ ymdIndex ][ staff_id ] + ' ';
					}
				}

				// Select all days, when duration is longer than 1 day
				if ( new Date( year, month, day ) < new Date( curr_year, curr_month, curr_day ) ) {
					css_classes = css_classes + 'past_day ';
				}

				// Discounted day?
				if ( 'undefined' !== typeof discounted_days && discounted_days[ ymdIndex ] ) {
					css_classes = css_classes + 'discounted_day ';
					discounted_title = discounted_title + discounted_days[ ymdIndex ];
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
					if ( $.inArray( ymdIndex, days_array ) > -1 ) {
						return [ appointable, css_classes + 'not_appointable in_range ', wc_appointment_form_params.i18n_date_available ];
					}
					return [ appointable, fully_scheduled_days[ ymdIndex ] ? 'fully_scheduled' : css_classes + 'not_appointable', wc_appointment_form_params.i18n_date_unavailable ];
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
					return [ appointable, css_classes + 'appointable ', title ];
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
						day_of_week = the_date.getDay(),
						ymdIndex    = year + '-' + month + '-' + day;

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
					if ( args.fully_scheduled_days[ ymdIndex ] ) {
						if ( args.fully_scheduled_days[ ymdIndex ][0] || args.fully_scheduled_days[ ymdIndex ][ args.staff_id ] ) {
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
			 * @returns boolean
			 */
			is_staff_available_on_date: function( args, rules ) {
				if ( 'object'!== typeof args || 'object' !== typeof rules ) {
					return false;
				}

				var defaultAvailability = args.default_availability,
					year                = args.date.getFullYear(),
					month               = args.date.getMonth() + 1, // months start at 0
					day                 = args.date.getDate(),
					day_of_week         = args.date.getDay(),
					ymdIndex            = year + '-' + month + '-' + day;

				var	firstOfJanuary = new Date( year, 0, 1 );
				var week =  Math.ceil( ( ( (args.date - firstOfJanuary ) / 86400000) + firstOfJanuary.getDay() + 1 ) / 7 );

				// Sunday is 0, Monday is 1, and so on.
				if ( day_of_week === 0 ) {
					day_of_week = 7;
				}

				// `args.fully_scheduled_days` and `args.staff_id` only available
				// when checking 'automatic' staff assignment.
				if ( args.fully_scheduled_days && args.fully_scheduled_days[ ymdIndex ] && args.fully_scheduled_days[ ymdIndex ][ args.staff_id ] ) {
					return false;
				}

				var minutesAvailableForDay    = [];
				var minutesForADay = _.range( 1, 1440 ,1 );
				// Ensure that the minutes are set when the all slots are available by default.
				if ( defaultAvailability ){
					minutesAvailableForDay = minutesForADay;
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
								if ( typeof range[ month ] !== 'undefined' ) {

									if ( range[ month ] ) {
										minutesAvailableForDay = minutesForADay;
									} else{
										minutesAvailableForDay = [];
									}
									return true; // go to the next rule
								}
								break;
							case 'weeks':
								if ( typeof range[ week ] !== 'undefined' ) {
									if( range[ week ] ){
										minutesAvailableForDay = minutesForADay;
									} else{
										minutesAvailableForDay = [];
									}
									return true; // go to the next rule
								}
								break;
							case 'days':
								if ( typeof range[ day_of_week ] !== 'undefined' ) {
									if( range[ day_of_week ] ){
										minutesAvailableForDay = minutesForADay;
									} else{
										minutesAvailableForDay = [];
									}
									return true; // go to the next rule
								}
								break;
							case 'custom':
								if ( typeof range[ year ][ month ][ day ] !== 'undefined' ) {
									if( range[ year ][ month ][ day ]){
										minutesAvailableForDay = minutesForADay;
									} else{
										minutesAvailableForDay = [];
									}
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
								var fromHour = parseInt( range.from.split(':')[0] );
								var fromMinute = parseInt( range.from.split(':')[1] );
								var fromMinuteNumber = fromMinute + ( fromHour * 60 );
								var toHour = parseInt( range.to.split(':')[0] );
								var toMinute = parseInt( range.to.split(':')[1] );
								var toMinuteNumber = toMinute + ( toHour * 60 );
								var toMidnight = ( toHour === 0 && toMinute === 0 ) ? true : false;
								var slotNextDay = false;

								// Enable next day on calendar, when toHour is less than fromHour and not midnight.
								// When overnight is sunday, make sure it goes to monday next day.
								var prev_day = ( day_of_week - 1 ) === 0 ? 7 : ( day_of_week - 1 );
								if ( ( ! toMidnight ) && ( toMinuteNumber <= fromMinuteNumber ) && ( range.day === prev_day ) ) {
									slotNextDay = range.day;
								}

								if ( day_of_week === range.day || 0 === range.day || slotNextDay === range.day ) {
									// Make sure next day toHour adds 24 hours.
									if ( toMinuteNumber <= fromMinuteNumber ) {
										toHour += 24;
										toMinuteNumber = toMinute + ( toHour * 60 );
									}

									// each minute in the day gets a number from 1 to 1440
									var minutesAvailableForTime = _.range(fromMinuteNumber, toMinuteNumber, 1);

									if ( range.rule ) {
										minutesAvailableForDay = _.union(minutesAvailableForDay, minutesAvailableForTime);
									} else {
										minutesAvailableForDay = _.difference(minutesAvailableForDay, minutesAvailableForTime);
									}

									return true;
								}
								break;
							case 'time:range':
									/*
									if ( typeof slotNextDay2[0] !== 'undefined' && slotNextDay2[0] === ymdIndex ) {
										range = slotNextDay2[1];
									} else {
										range = range[ year ][ month ][ day ];
									}
									*/
										range = range[ year ][ month ][ day ];
									var fromHour2 = parseInt( range.from.split(':')[0] );
									var fromMinute2 = parseInt( range.from.split(':')[1] );
									var toHour2 = parseInt( range.to.split(':')[0] );
									var toMinute2 = parseInt( range.to.split(':')[1] );
									// var toMidnight2 = ( toHour2 === 0 && toMinute2 === 0 ) ? true : false;

									// Enable next day on calendar, when toHour is less than fromHour and not midnight.
									/*
									if ( ! toMidnight2 && ! slotNextDay2 && toHour2 <= fromHour2 ) {
										slotNextDay2 = [
										    year + '-' + month + '-' + ( day + 1 ), // slotNextDay2[0]
										    range									// slotNextDay2[1]
										];
										// slotNextDay2 = range;
										// slotNextDay2['date'] = new Date( year, month - 1, day + 1 );
										// slotNextDay2 = new Date( year, month - 1, day + 1 );
										// slotNextDay2 = year + '-' + month + '-' + ( day + 1 );
									}
									*/

									// Make sure next day toHour adds 24 hours.
									if ( ( toHour2 <= fromHour2 ) && ( toMinute2 <= fromMinute2 ) ) {
										toHour2 += 24;
									}

									// each minute in the day gets a number from 1 to 1440
									var fromMinuteNumber2 = fromMinute2 + ( fromHour2 * 60 );
									var toMinuteNumber2 = toMinute2 + ( toHour2 * 60 );
									var minutesAvailableForTime2 = _.range(fromMinuteNumber2, toMinuteNumber2, 1);

									if ( range.rule ) {
										minutesAvailableForDay = _.union(minutesAvailableForDay, minutesAvailableForTime2);
									} else {
										minutesAvailableForDay = _.difference(minutesAvailableForDay, minutesAvailableForTime2);
									}

									//return true;
								//}
							break;
						}
					} catch( err ) {
						return true; // go to the next rule
					}
				});

				return ! _.isEmpty( minutesAvailableForDay );

			},
			get_week_number: function( date ){
				var January1 = new Date( date.getFullYear(), 0, 1 );
				var week     = Math.ceil( ( ( ( date - January1 ) / 86400000) + January1.getDay() + 1 ) / 7 );
				return week;
			},
			has_available_staff: function( args ) {
				for ( var staff_id in args.availability ) {
					if ( args.availability.hasOwnProperty( staff_id ) ) {
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
			},
			get_format_date: function( date ){
				// 1970, 1971, ... 2015, 2016, ...
				var yyyy = date.getFullYear();
				// 01, 02, 03, ... 10, 11, 12
				var MM = ((date.getMonth() + 1) < 10 ? '0' : '') + (date.getMonth() + 1);
				// 01, 02, 03, ... 29, 30, 31
				var dd = (date.getDate() < 10 ? '0' : '') + date.getDate();

				// create the format you want
				return ( yyyy + '-' + MM + '-' + dd );
			},
			get_relative_date: function( relDateAttr ) {
			    var minDate = new Date();
			    var pattern = /([+-]?[0-9]+)\s*(d|D|w|W|m|M|y|Y)?/g;
			    var matches = pattern.exec(relDateAttr);
			    while (matches) {
			        switch (matches[2] || 'd') {
			            case 'd' : case 'D' :
			                minDate.setDate(minDate.getDate() + parseInt(matches[1],10));
			                break;
			            case 'w' : case 'W' :
			                minDate.setDate(minDate.getDate() + parseInt(matches[1],10) * 7);
			                break;
			            case 'm' : case 'M' :
			                minDate.setMonth(minDate.getMonth() + parseInt(matches[1],10));
			                break;
			            case 'y': case 'Y' :
			                minDate.setYear(minDate.getFullYear() + parseInt(matches[1],10));
			                break;
			        }
			        matches = pattern.exec(relDateAttr);
			    }
			    return minDate;
			},
			find_available_date_within_month: function() {
			    var nextConsectiveDates = [];

			    $.each( $('.appointable:not(.ui-state-disabled)').find('.ui-state-default'), function ( i, value ) {
			        var numericDate = +$(value).text();
			        if ( numericDate ) {
			            nextConsectiveDates.push(numericDate);
			        }
			    } );

			    var nextAvailDate = nextConsectiveDates[0];

			    return nextAvailDate;
			}
		};

	wc_appointments_date_picker.init();
});
