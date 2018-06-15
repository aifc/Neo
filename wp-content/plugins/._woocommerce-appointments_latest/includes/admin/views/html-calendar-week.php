<div class="wrap woocommerce">
	<h2><?php _e( 'Calendar', 'woocommerce-appointments' ); ?> <a href="<?php echo admin_url( 'edit.php?post_type=wc_appointment&page=add_appointment' ); ?>" class="add-new-h2"><?php _e( 'Add Appointment', 'woocommerce-appointments' ); ?></a></h2>

	<form method="get" id="mainform" enctype="multipart/form-data" class="wc_appointments_calendar_form week_view">
		<input type="hidden" name="post_type" value="wc_appointment" />
		<input type="hidden" name="page" value="appointment_calendar" />
		<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>" />
		<input type="hidden" name="tab" value="calendar" />
		<div class="tablenav">
			<div class="filters">
				<select id="calendar-appointments-filter" name="filter_appointments" class="wc-enhanced-select" style="width:200px">
					<option value=""><?php _e( 'Filter Appointments', 'woocommerce-appointments' ); ?></option>
					<?php
					$product_filters = $this->product_filters();
					if ( $product_filters ) :
					?>
						<optgroup label="<?php _e( 'By appointable product', 'woocommerce-appointments' ); ?>">
							<?php foreach ( $product_filters as $filter_id => $filter_name ) : ?>
								<option value="product_<?php echo $filter_id; ?>" <?php selected( $product_filter, $filter_id ); ?>><?php echo $filter_name; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php
					$staff_filters = $this->staff_filters();
					if ( $staff_filters ) :
					?>
						<optgroup label="<?php _e( 'By staff', 'woocommerce-appointments' ); ?>">
							<?php foreach ( $staff_filters as $filter_id => $filter_name ) : ?>
								<option value="staff_<?php echo $filter_id; ?>" <?php selected( $product_filter, $filter_id ); ?>><?php echo $filter_name; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
				</select>
			</div>
			<div class="week_selector">
				<a class="prev" href="<?php echo esc_url( add_query_arg( 'calendar_day', $prev_week ) ); ?>">&larr;</a>
				<div class="week_picker">
					<input type="text" name="calendar_day" class="calendar_day date-picker" value="<?php echo esc_attr( $week_formatted ); ?>" placeholder="<?php echo wc_date_format(); ?>" autocomplete="off" readonly="readonly" />
				</div>
				<a class="next" href="<?php echo esc_url( add_query_arg( 'calendar_day', $next_week ) ); ?>">&rarr;</a>
			</div>
			<div class="views">
				<a class="month" href="<?php echo esc_url( add_query_arg( 'view', 'month' ) ); ?>" title="<?php _e( 'Month View', 'woocommerce-appointments' ); ?>"><?php _e( 'Month', 'woocommerce-appointments' ); ?></a>
				<span><?php _e( 'Week', 'woocommerce-appointments' ); ?></span>
				<a class="month" href="<?php echo esc_url( add_query_arg( 'view', 'day' ) ); ?>" title="<?php _e( 'Day View', 'woocommerce-appointments' ); ?>"><?php _e( 'Day', 'woocommerce-appointments' ); ?></a>
			</div>
			<?php
			wc_enqueue_js( "
				// -------------------------------------
				// Calendar filters
				// -------------------------------------
				$( '.tablenav select, .tablenav input' ).change(function() {
					$( '#mainform' ).submit();
				});

				// -------------------------------------
				// Calendar week picker
				// -------------------------------------
				$( '.calendar_day' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showOtherMonths: true,
					changeMonth: true,
					showButtonPanel: true,
					minDate: null
				}).attr('size', $( '.calendar_day' ).val().length );

				// -------------------------------------
				// Display current time on calendar
				// -------------------------------------
				var current_date = $( '.today' )[0];
				var d = new Date();
				var calendar_h = $( '.hours' ).height();

				if ( current_date ) {
					var current_time = d.getHours() * 60 + d.getMinutes();
					var current_time_locale = d.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}).toLowerCase();
					var indicator_top = Math.round( ( calendar_h / ( 60 * 24 ) * current_time ) + 60 );
					$( '.today' ).append( '<div class=\"time_indicator tips\" title=\"'+ current_time_locale +'\"></div>' );
					$( '.time_indicator' ).css( {top: indicator_top} );
					$( '.time_indicator' ).tipTip();
				}

				setInterval( set_indicator, 60000 );

				function set_indicator() {
					var dt = new Date();
					var current_time = dt.getHours() * 60 + dt.getMinutes();
					var current_time_locale_updated = dt.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}).toLowerCase();
					var indicator_top = Math.round( ( calendar_h / ( 60 * 24 ) * current_time ) + 60 );
					$( '.time_indicator' ).css( {top: indicator_top} );
					$( '.time_indicator' ).attr( 'title', current_time_locale_updated );
					$( '.time_indicator' ).tipTip();
				}

				// -------------------------------------
				// Scroll to clicked hours label
				// -------------------------------------
				$('.hours label').click(function(){
					var e = $(this);
					$('html,body').animate({
						scrollTop: e.position().top
					}, 300);
				});

				// -------------------------------------
				// Overlapping appointments algorythm.
				// Not working for allday appointments.
				// -------------------------------------
				var events = $('.bytime.appointments .single_appointment');
		        var eventArray = jQuery.map(events, function (element, index) {
		            var event  = $(element);
		            var id     = event.data('appointment-id');
					var start  = event.data('appointment-start');
					var end    = event.data('appointment-end');
		            var complexEvent = {
		                'id'   : id,
						'start': start,
						'end'  : end
		            };
		            return complexEvent;
		        }).sort(function (a, b) {
		            return a.start - b.start;
		        });

				// Get overlapping events
				var results = []; // list of all events
				var index = []; // array of overlapped events
				var skip = []; // array of overlapped events to skip
			    for (var i = 0, l = eventArray.length; i < l; i++) {
			        var oEvent    = eventArray[i];
			        var nOverlaps = 0;
					var xOverlaps = 0;
			        for (var j = 0; j < l; j++) {
			            var oCompareEvent = eventArray[j];
						/*
			            if ( (oCompareEvent.start < oEvent.end && oCompareEvent.end > oEvent.start) ||
						     (oCompareEvent.end < oEvent.start && oCompareEvent.start > oEvent.end)
						   ) {
							   	nOverlaps++;
								index.push( oCompareEvent.id );
			            }
						*/
						/*
						if ( (oEvent.start <= oCompareEvent.end) && (oEvent.end >= oCompareEvent.start) ) {
							nOverlaps++;
							index.push( oCompareEvent.id );
			            }
						*/
						if ( (oEvent.start <= oCompareEvent.end) && (oEvent.end >= oCompareEvent.start) ) {
							nOverlaps++;
							index.push( oCompareEvent.id );
							if ( (oEvent.start === oCompareEvent.end) || (oEvent.end === oCompareEvent.start) ) {
								xOverlaps++;
								skip.push( oCompareEvent.id );
				            }
			            }

			        }

					// Skip events that have all overlaps
					// with same start/end times.
					if ((nOverlaps-1) === xOverlaps && 1 < nOverlaps) {
						continue;
					}

					// Modify overlapped events.
			        if (1 < nOverlaps) {
						var event_id        = oEvent.id;
						var event_count     = nOverlaps;
						var event_index     = index.filter(i => i === event_id).length;
						var event_new_index = event_index - 1; // reduce by one to skip first event in index.

						var event           = $('.single_appointment[data-appointment-id='+event_id+']');
						var event_width     = event.width();
						var event_new_width = event_width / event_count;
						var event_left      = event.position().left;
						var event_new_left  = event_left + (event_new_width * event_new_index);

						event.css({
					        'width': event_new_width + 'px',
					        'left' : event_new_left + 'px'
					    });

						/*
						results.push({
			                id         : event_id,
			                eventCount : event_count,
							eventIndex : event_index,
							eventWidth : event_width,
							eventNWidth: event_new_width,
							eventLeft  : event_left,
							eventNLeft : event_new_left
			            });
						*/
			        }

			    }

		        //console.log(results);

			" );
			?>
		</div>
		<div class="calendar_wrapper">
			<?php
			// Calc calendar height.
			$calendar_scale = apply_filters( 'woocommerce_appointments_calendar_view_day_scale', 60 );
			$c_height = 'height: ' . ($calendar_scale * 24) . 'px;';

			// Calc day offset.
			$current_timestamp = current_time( 'timestamp' );
			$current_day_of_week = date( 'N', $current_timestamp );
			$day_offset = $current_day_of_week - $start_of_week;
			$column = $day_offset >= 0 ? $day_offset : 7 - abs( $day_offset );

			// Calc left position.
			$position_left = absint( 100 + ( 170 * $column ) );
			$c_left = 'left: ' . $position_left . 'px;';

			// Is today on calendar?
			$today_on_cal = ( $week_start <= $current_timestamp && $week_end >= $current_timestamp ) ? true : false;
			?>
			<div class="calendar_days calendar_view_by_day">
				<?php $index = 0; ?>
				<ul class="header_wrapper">
					<?php while ( $week_start <= $week_end ) {
					echo '<li class="header_column" data-time="' . date( 'Y-m-d', $week_start ) . '">';
						echo '<a href="' . admin_url( 'edit.php?post_type=wc_appointment&page=appointment_calendar&view=day&tab=calendar&calendar_day=' . date( 'Y-m-d', $week_start ) ) . '" title="' . date( wc_date_format(), $week_start ) . '">' . date( 'D', $week_start ) . ' <span class="daynum">' . date( 'j', $week_start ) . '</span></a>';
					echo '</li>';

					$week_start = strtotime( '+1 day', $week_start ); $index ++;
					} ?>
				</ul>
				<label class="allday_label"><?php _e( 'All Day', 'woocommerce-appointments' ); ?></label>
				<ul class="allday appointments"><?php $this->list_appointments_for_day( 'all_day', 'week' ); ?></ul>
				<div class="clear"></div>
				<?php if ( $today_on_cal ) : ?>
					<div class="today" style="<?php echo $c_left; ?>"></div>
				<?php endif; ?>
				<div class="grid" style="<?php echo $c_height; ?>"></div>
				<ul class="hours" style="<?php echo $c_height; ?>">
					<?php for ( $i = 0; $i < 24; $i ++ ) : ?>
						<li><label>
							<?php
							if ( 0 != $i && 24 != $i ) {
								echo date_i18n( wc_time_format(), strtotime( "midnight +{$i} hour" ) );
							}
							?>
						</label></li>
					<?php endfor; ?>
				</ul>
				<ul class="bytime appointments" style="<?php echo $c_height  ?>">
					<?php $this->list_appointments_for_day( 'by_time', 'week' ); ?>
				</ul>
			</div>
		</div>
	</form>
</div>
