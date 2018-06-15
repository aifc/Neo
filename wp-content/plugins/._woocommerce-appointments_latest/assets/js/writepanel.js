/* global wc_appointments_writepanel_js_params, alert, confirm */
jQuery(document).ready(function($) {
	'use strict';

	/*
	if ( ! window.console ) {
		window.console = {
			log : function(str) {
				alert(str);
			}
		};
	}
	*/

	var wc_appointments_writepanel = {
		init: function() {
			$( '#appointments_availability, #appointments_pricing' ).on( 'change', '.wc_appointment_availability_type select, .wc_appointment_pricing_type select', this.wc_appointments_table_grid );
			$( 'body' ).on( 'row_added', this.wc_appointments_row_added );
			$( 'body' ).on( 'woocommerce-product-type-change', this.wc_appointments_trigger_change_events );
			$( 'input#_virtual' ).on( 'change', this.wc_appointments_trigger_change_events );
			$( 'input#_downloadable' ).on( 'change', this.wc_appointments_trigger_change_events );
			$( '#_wc_appointment_user_can_cancel' ).on( 'change', this.wc_appointments_user_cancel );
			$( '#product-type' ).on( 'change', this.wc_appointments_inventory_show );
			//$( '#_stock' ).on( 'change', this.wc_appointments_qty );
			$( '#_wc_appointment_qty' ).on( 'change', this.wc_appointments_qty );
			$( '#_wc_appointment_qty_min' ).on( 'change', this.wc_appointments_qty_min );
			$( '#_wc_appointment_qty_max' ).on( 'change', this.wc_appointments_qty_max );
			$( '#_wc_appointment_has_price_label' ).on( 'change', this.wc_appointments_price_label );
			$( '#_wc_appointment_has_pricing' ).on( 'change', this.wc_appointments_pricing );
			$( '#_wc_appointment_staff_assignment' ).on( 'change', this.wc_appointments_staff_assignment );
			$( '#_wc_appointment_duration_unit' ).on( 'change', this.wc_appointment_duration_unit );
			$( '#_wc_appointment_has_restricted_days').on( 'change', this.wc_appointment_restricted_days );
			$( 'body' ).on( 'click', '.add_new_addon', this.wc_appointments_addon_label );
			$( 'body' ).on( 'click', '.add_row', this.wc_appointments_table_grid_add_row );
			$( 'body' ).on( 'click', 'td.remove', this.wc_appointments_table_grid_remove_row );
			$( '#appointments_staff' ).on( 'click', 'button.add_staff', this.wc_appointments_add_staff );
			$( '#appointments_staff' ).on( 'click', 'button.remove_appointment_staff', this.wc_appointments_remove_staff );

			wc_appointments_writepanel.wc_appointments_trigger_change_events();
			wc_appointments_writepanel.wc_appointments_price_show();
			wc_appointments_writepanel.wc_appointments_inventory_show();

			$( '#availability_rows, #pricing_rows' ).sortable({
				items: 'tr',
				cursor: 'move',
				axis: 'y',
				handle: '.sort',
				scrollSensitivity:40,
				forcePlaceholderSize: true,
				helper: 'clone',
				opacity: 0.65,
				placeholder: {
					element: function() {
						return $( '<tr class="wc-metabox-sortable-placeholder"><td colspan=99>&nbsp;</td></tr>' )[0];
					},
					update: function() {}
				},
				start: function(event,ui){
					ui.item.css('background-color','#f6f6f6');
				},
				stop: function(event,ui){
					ui.item.removeAttr('style');
				}
			});

			$( '.date-picker' ).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showOtherMonths: true,
				changeMonth: true,
				showButtonPanel: true,
				showOn: 'button',
				minDate: 0,
				firstDay: wc_appointments_writepanel_js_params.firstday,
				buttonText: '<span class="dashicons dashicons-calendar-alt"></span>'
			});

			$( '.woocommerce_appointable_staff' ).sortable({
				items: '.woocommerce_appointment_staff',
				cursor: 'move',
				axis: 'y',
				handle: 'h3',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'wc-metabox-sortable-placeholder',
				start: function( event, ui ) {
					ui.item.css( 'background-color', '#f6f6f6' );
				},
				stop: function ( event, ui ) {
					ui.item.removeAttr( 'style' );
					wc_appointments_writepanel.staff_row_indexes();
				}
			});

			$('#_wc_appointment_cal_color').wpColorPicker();

		},
		wc_appointments_table_grid: function() {
			var value = $(this).val();
			var tr    = $(this).closest('tr');
			var row   = $(tr);

			row.find( '.from_date, .from_day_of_week, .from_month, .from_week, .from_time, .from' ).hide();
			row.find( '.to_date, .to_day_of_week, .to_month, .to_week, .to_time, .to, .on_date' ).hide();
			row.find( '.repeating-label' ).hide();
			row.find( '.appointments-datetime-select-to' ).removeClass( 'appointments-datetime-select-both' );
			row.find( '.appointments-datetime-select-from' ).removeClass( 'appointments-datetime-select-both' );

			if ( value === 'custom' ) {
				row.find('.from_date, .to_date').show();
			}
			if ( value === 'months' ) {
				row.find('.from_month, .to_month').show();
			}
			if ( value === 'weeks' ) {
				row.find('.from_week, .to_week').show();
			}
			if ( value === 'days' ) {
				row.find('.from_day_of_week, .to_day_of_week').show();
			}
			if ( value.match( '^time' ) ) {
				row.find('.from_time, .to_time').show();
				//* Show the date range as well if "time range for custom dates" is selected
				if ( 'time:range' === value ) {
					row.find('.from_date, .to_date').show();
					row.find( '.repeating-label' ).show();
					row.find( '.appointments-datetime-select-to' ).addClass( 'appointments-datetime-select-both' );
					row.find( '.appointments-datetime-select-from' ).addClass( 'appointments-datetime-select-both' );
				}
			}
			if ( value === 'duration' || value === 'slots' || value === 'quant' ) {
				row.find('.from, .to').show();
			}

			return false;
		},
		wc_appointments_row_added: function() {
			$( '.wc_appointment_availability_type select, .wc_appointment_pricing_type select' ).change();
			$( '.date-picker' ).datepicker({
				dateFormat: 'yy-mm-dd',
				numberOfMonths: 1,
				showOtherMonths: true,
				changeMonth: true,
				showButtonPanel: true,
				showOn: 'button',
				firstDay: wc_appointments_writepanel_js_params.firstday,
				buttonText: '<span class="dashicons dashicons-calendar-alt"></span>'
			});

			return false;
		},
		wc_appointments_trigger_change_events: function() {
			$( '.wc_appointment_availability_type select, .wc_appointment_pricing_type select, #_wc_appointment_user_can_cancel, #_wc_appointment_has_price_label, #_wc_appointment_has_pricing, #_wc_appointment_duration_unit, #_wc_appointment_staff_assignment, #_stock, #_wc_appointment_qty, #_wc_appointment_qty_min, #_wc_appointment_qty_max, #_wc_appointment_has_restricted_days' ).change();

			return false;
		},
		wc_appointments_user_cancel: function() {
			if ( $(this).is( ':checked' ) ) {
				$( '.form-field.appointment-cancel-limit' ).show();
			} else {
				$( '.form-field.appointment-cancel-limit' ).hide();
			}

			return false;
		},
		wc_appointments_qty: function() {
			var qty_this	= parseInt( $(this).val(), 10 );
			var qty_min		= parseInt( $( '#_wc_appointment_qty_min' ).val(), 10 );
			var qty_max		= parseInt( $( '#_wc_appointment_qty_max' ).val(), 10 );

			if ( qty_this > 1 ) {
				$( '.form-field._wc_appointment_customer_qty_wrap' ).show();
				$( '#_wc_appointment_qty_min' ).prop( 'max', qty_this );
				$( '#_wc_appointment_qty_max' ).prop( 'max', qty_this );
			} else {
				$( '.form-field._wc_appointment_customer_qty_wrap' ).hide();
			}

			// min.
			if ( qty_this < qty_min ) {
				$( '#_wc_appointment_qty_min' ).val( qty_this );
			}

			// max.
			if ( qty_this < qty_max ) {
				$( '#_wc_appointment_qty_max' ).val( qty_this );
			}

			return false;
		},
		wc_appointments_qty_min: function() {
			var qty_this	= parseInt( $(this).val(), 10 );
			var qty_max		= parseInt( $( '#_wc_appointment_qty_max' ).val(), 10 );

			if ( qty_this > qty_max ) {
				$( '#_wc_appointment_qty_max' ).val( qty_this );
			}

			return false;
		},
		wc_appointments_qty_max: function() {
			var qty_this	= parseInt( $(this).val(), 10 );
			var qty_min		= parseInt( $( '#_wc_appointment_qty_min' ).val(), 10 );

			if ( qty_this < qty_min ) {
				$( '#_wc_appointment_qty_min' ).val( qty_this );
			}

			return false;
		},
		wc_appointments_price_show: function() {
			var product_type = $( 'select#product-type' ).val();

			if ( 'appointment' === product_type ) {
				$( '.options_group.pricing' ).show();
			}

			$( '.options_group.pricing' ).addClass( 'show_if_appointment' );

			return false;
		},
		wc_appointments_inventory_show: function() {
			var product_type = $( 'select#product-type' ).val();

			if ( 'appointment' === product_type ) {
				$( '.stock_fields' ).hide();
				$( '.stock_fields' ).addClass( 'hide_if_appointment' );
				$( '._stock_status_field' ).hide();
				$( '._stock_status_field' ).addClass( 'hide_if_appointment' );
			}

			return false;
		},
		wc_appointments_price_label: function() {
			if ( $(this).is( ':checked' ) ) {
				$( '.form-field._wc_appointment_price_label_field' ).show();
			} else {
				$( '.form-field._wc_appointment_price_label_field' ).hide();
			}

			return false;
		},
		wc_appointments_addon_label: function() {
			var product_type = $( 'select#product-type' ).val();

			if ( 'appointment' === product_type ) {
				$( '.show_if_appointment' ).show();
			} else {
				$( '.show_if_appointment' ).hide();
			}

			return false;
		},
		wc_appointments_pricing: function() {
			if ( $(this).is( ':checked' ) ) {
				$( '#appointments_pricing' ).show();
			} else {
				$( '#appointments_pricing' ).hide();
			}

			return false;
		},
		wc_appointments_staff_assignment: function() {
			if ( $(this).val() === 'customer' ) {
				$( '.form-field._wc_appointment_staff_label_field' ).show();
			} else {
				$( '.form-field._wc_appointment_staff_label_field' ).hide();
			}

			return false;
		},
		wc_appointment_duration_unit: function() {
			switch ( $( this ).val() ) {
				case 'day':
					$( '.form-field._wc_appointment_interval_duration_wrap' ).hide();
					$( '#_wc_appointment_padding_duration_unit option[value="minute"]' ).hide();
					$( '#_wc_appointment_padding_duration_unit option[value="hour"]' ).hide();
					$( '#_wc_appointment_padding_duration_unit option[value="day"]' ).show();
					$( '#_wc_appointment_padding_duration_unit' ).val( 'day' );
				break;
				default: // all other.
					$( '.form-field._wc_appointment_interval_duration_wrap' ).show();
					$( '#_wc_appointment_padding_duration_unit option[value="minute"]' ).show();
					$( '#_wc_appointment_padding_duration_unit option[value="hour"]' ).show();
					$( '#_wc_appointment_padding_duration_unit option[value="day"]' ).hide();
				break;
			}

			return false;
		},
		wc_appointment_restricted_days: function() {
			if ( $(this).is( ':checked' ) ) {
				$( '.appointment-day-restriction' ).show();
			} else {
				$( '.appointment-day-restriction' ).hide();
			}

			return false;
		},
		wc_appointments_table_grid_add_row: function() {
			$(this).closest('table').find('tbody').append( $( this ).data( 'row' ) );
			$('body').trigger('row_added');

			return false;
		},
		wc_appointments_table_grid_remove_row: function() {
			$(this).closest('tr').remove();

			return false;
		},
		wc_appointments_add_staff: function() {
			var loop           = $( '.woocommerce_appointment_staff' ).length;
			var add_staff_id   = parseInt( jQuery( 'select.add_staff_id' ).val(), 10 );
			var add_staff_name = '';

			$( '.woocommerce_appointable_staff' ).block({ message: null });

			var data = {
				action:            'woocommerce_add_appointable_staff',
				post_id:           wc_appointments_writepanel_js_params.post,
				loop:              loop,
				add_staff_id:      add_staff_id,
				add_staff_name:    add_staff_name,
				security:          wc_appointments_writepanel_js_params.nonce_add_staff
			};

			$.post( wc_appointments_writepanel_js_params.ajax_url, data, function( response ) {
				if ( response.error ) {
					alert( response.error );
					$( '.woocommerce_appointable_staff' ).unblock();
				} else {
					$( '.woocommerce_appointable_staff' ).append( response.html ).unblock();
					$( '.woocommerce_appointable_staff' ).sortable( 'refresh' );
				}
			});

			return false;
		},
		wc_appointments_remove_staff: function( element ) {
			element.preventDefault();
			var answer = confirm( wc_appointments_writepanel_js_params.i18n_remove_staff );
			if ( answer ) {

				var el      = $(this).parent().parent();
				var staff 	= $(this).attr('rel');

				$( el ).block({ message: null });

				var data = {
					action:     'woocommerce_remove_appointable_staff',
					post_id:    wc_appointments_writepanel_js_params.post,
					staff_id: 	staff,
					security:   wc_appointments_writepanel_js_params.nonce_delete_staff
				};

				$.post( wc_appointments_writepanel_js_params.ajax_url, data, function() {
					$( el ).fadeOut( '300', function(){
						$( el ).remove();
					});
				});
			}

			return false;
		},
		staff_row_indexes: function() {
			$( '.woocommerce_appointable_staff .woocommerce_appointment_staff' ).each( function( index, el ) {
				$( '.staff_menu_order', el ).val( parseInt( $(el).index( '.woocommerce_appointable_staff .woocommerce_appointment_staff' ), 10 ) );
			});

			return false;
		}
	};

	wc_appointments_writepanel.init();
});
