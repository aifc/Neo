<div class="wrap woocommerce">
	<h2><?php _e( 'Appointments by day', 'woocommerce-appointments' ); ?> <a href="<?php echo admin_url( 'edit.php?post_type=wc_appointment&page=add_appointment' ); ?>" class="add-new-h2"><?php _e( 'Add Appointment', 'woocommerce-appointments' ); ?></a></h2>

	<form method="get" id="mainform" enctype="multipart/form-data" class="wc_appointments_calendar_form day_view">
		<input type="hidden" name="post_type" value="wc_appointment" />
		<input type="hidden" name="page" value="appointment_calendar" />
		<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>" />
		<input type="hidden" name="tab" value="calendar" />
		<div class="tablenav">
			<div class="filters">
				<select id="calendar-appointments-filter" name="filter_appointments" class="wc-enhanced-select" style="width:200px">
					<option value=""><?php _e( 'Filter Appointments', 'woocommerce-appointments' ); ?></option>
					<?php if ( $product_filters = $this->product_filters() ) : ?>
						<optgroup label="<?php _e( 'By appointable product', 'woocommerce-appointments' ); ?>">
							<?php foreach ( $product_filters as $filter_id => $filter_name ) : ?>
								<option value="product_<?php echo $filter_id; ?>" <?php selected( $product_filter, $filter_id ); ?>><?php echo $filter_name; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
					<?php if ( $staff_filters = $this->staff_filters() ) : ?>
						<optgroup label="<?php _e( 'By staff', 'woocommerce-appointments' ); ?>">
							<?php foreach ( $staff_filters as $filter_id => $filter_name ) : ?>
								<option value="staff_<?php echo $filter_id; ?>" <?php selected( $product_filter, $filter_id ); ?>><?php echo $filter_name; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
				</select>
			</div>
			<div class="date_selector">
				<a class="prev" href="<?php echo esc_url( add_query_arg( 'calendar_day', $prev_day ) ); ?>">&larr;</a>
				<div>
					<input type="text" name="calendar_day" class="calendar_day date-picker" value="<?php echo esc_attr( $day ); ?>" placeholder="<?php echo get_option( 'date_format' ); ?>" autocomplete="off" />
				</div>
				<a class="next" href="<?php echo esc_url( add_query_arg( 'calendar_day', $next_day ) ); ?>">&rarr;</a>
			</div>
			<div class="views">
				<a class="month" href="<?php echo esc_url( add_query_arg( 'view', 'month' ) ); ?>"><?php _e( 'Month View', 'woocommerce-appointments' ); ?></a>
			</div>
			<?php
			wc_enqueue_js( "
				$( '.tablenav select, .tablenav input' ).change(function() {
					$( '#mainform' ).submit();
				});

				$( '.calendar_day' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showOtherMonths: true,
					changeMonth: true,
					showButtonPanel: true,
					minDate: null
				});

				// Display current time on calendar
				var current_date = $( '.calendar_day' ).val();
				var d = new Date();
				var month = d.getMonth()+1;
				var day = d.getDate();
				var today = d.getFullYear() + '-' + ( month < 10 ? '0' : '' ) + month + '-' + ( day < 10 ? '0' : '' ) + day;
				var calendar_h = $( '.bytime.appointments' ).height();

				if ( current_date == today ) {
					var current_time = d.getHours() * 60 + d.getMinutes();
					var current_time_locale = d.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}).toLowerCase();
					var indicator_top = Math.round( calendar_h / ( 60 * 24 ) * current_time );
					$( '.bytime.appointments' ).append( '<div class=\"time_indicator tips\" title=\"'+ current_time_locale +'\"></div>' );
					$( '.time_indicator' ).css( {top: indicator_top} );
					$( '.time_indicator' ).tipTip();
				}

				setInterval( set_indicator, 60000 );

				function set_indicator() {
					var dt = new Date();
					var current_time = dt.getHours() * 60 + dt.getMinutes();
					var current_time_locale_updated = dt.toLocaleTimeString('en-US', {hour: '2-digit', minute:'2-digit'}).toLowerCase();
					var indicator_top = Math.round( calendar_h / ( 60 * 24 ) * current_time);
					$( '.time_indicator' ).css( {top: indicator_top} );
					$( '.time_indicator' ).attr( 'title', current_time_locale_updated );
					$( '.time_indicator' ).tipTip();
				}

				// Scroll to clicked hours label
				$('.hours label').click(function(){
					var e = $(this);
					$('.calendar_wrapper').animate({
						scrollTop: e.position().top
					}, 300);
				});
			" );
			?>
		</div>
		<div class="calendar_wrapper">
			<?php
			$calendar_scale = apply_filters( 'woocommerce_appointments_calendar_view_day_scale', 60 );
			$columns_by_staff = apply_filters( 'woocommerce_appointments_calendar_view_by_staff', false );
			$c_width = 'min-width:' . ( ( ( $this->staff_columns( $variation = 1 ) + 1 ) * 170 ) + 100 ) . 'px;';
			$c_height = 'height: ' . ($calendar_scale * 24) . 'px;';
			$c_class = ( $columns_by_staff ) ? 'class="calendar_days calendar_view_by_staff" style="' . $c_width . '"' : 'class="calendar_days" style="' . $c_width . '"';
			?>
			<div <?php echo $c_class; ?>>
				<?php if ( $columns_by_staff ) : ?>
				<ul class="staff">
					<?php $this->staff_columns(); ?>
				</ul>
				<?php endif; ?>
				<label class="allday_label"><?php _e( 'All Day', 'woocommerce-appointments' ); ?></label>
				<ul class="allday appointments">
					<?php $this->list_appointments_for_day( 'all_day' ); ?>
				</ul>
				<div class="clear"></div>
				<div class="grid"  style="<?php echo $c_height ?>"></div>
				<ul class="hours" style="<?php echo $c_height ?>">
					<?php for ( $i = 0; $i < 24; $i ++ ) : ?>
						<li><label><?php if ( 0 != $i && 24 != $i ) echo date_i18n( wc_time_format(), strtotime( "midnight +{$i} hour" ) ); ?></label></li>
					<?php endfor; ?>
				</ul>
				<ul class="bytime appointments" style="<?php echo $c_height  ?>">
					<?php $this->list_appointments_for_day( 'by_time' ); ?>
				</ul>
			</div>
		</div>
	</form>
</div>
