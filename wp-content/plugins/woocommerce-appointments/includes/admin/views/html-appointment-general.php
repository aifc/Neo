<div class="options_group show_if_appointment">
	<?php
		woocommerce_wp_checkbox( array(
			'id'          => '_wc_appointment_has_price_label',
			'label'       => __( 'Label instead of price?', 'woocommerce-appointments' ),
			'description' => __( 'Check this box if the appointment should display text label instead of fixed price amount.', 'woocommerce-appointments' ),
		) );

		woocommerce_wp_text_input( array(
			'id'          => '_wc_appointment_price_label',
			'label'       => __( 'Price Label', 'woocommerce-appointments' ),
			'placeholder' => __( 'Price Varies', 'woocommerce-appointments' ),
			'desc_tip'    => true,
			'description' => __( 'Show this label instead of fixed price amount. Payment will not be taken during checkout.', 'woocommerce-appointments' ),
		) );

		woocommerce_wp_checkbox( array(
			'id'          => '_wc_appointment_has_pricing',
			'label'       => __( 'Custom pricing rules?', 'woocommerce-appointments' ),
			'description' => __( 'Check this box if the appointment has custom pricing rules.', 'woocommerce-appointments' ),
		) );
	?>
	<div id="appointments_pricing">
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th class="range_type"><?php _e( 'Range type', 'woocommerce-appointments' ); ?></th>
						<th class="range_name"><?php _e( 'Range', 'woocommerce-appointments' ); ?></th>
						<th class="range_name2"></th>
						<th class="range_cost"><?php _e( 'Base cost', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'Applied to the appointment as a whole. Must be inside range rules to be applied.', 'woocommerce-appointments' ) ); ?></th>
						<th class="range_cost"><?php _e( 'Slot cost', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'Applied to each appointment slot separately. When appointment lasts for 2 days or more, this cost applies to each day in range separately.', 'woocommerce-appointments' ) ); ?></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tbody id="pricing_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_appointment_pricing', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $pricing ) {
								include( 'html-appointment-pricing-fields.php' );
								do_action( 'woocommerce_appointments_pricing_fields', $pricing );
							}
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="7">
							<a href="#" class="button add_row" data-row="<?php
								ob_start();
								include( 'html-appointment-pricing-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php _e( 'Add Rule', 'woocommerce-appointments' ); ?></a>
							<span class="description"><?php _e( 'All matching rules will be applied to the appointment.', 'woocommerce-appointments' ); ?></span>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php do_action( 'woocommerce_appointments_after_appointments_pricing', $post_id ); ?>
	</div>
</div>
<div class="options_group show_if_appointment">
	<?php
		$capacity		= max( absint( get_post_meta( $post_id, '_wc_appointment_qty', true ) ), 1 );
		$capacity_min	= max( absint( get_post_meta( $post_id, '_wc_appointment_qty_min', true ) ), 1 );
		$capacity_max	= max( absint( get_post_meta( $post_id, '_wc_appointment_qty_max', true ) ), 1 );
	?>
	<p class="form-field">
		<label for="_wc_appointment_qty"><?php _e( 'Capacity', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_qty" id="_wc_appointment_qty" value="<?php echo $capacity; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<?php echo wc_help_tip( __( 'The maximum number of appointments per slot.', 'woocommerce-appointments' ) ); ?>
	</p>
	<p class="form-field _wc_appointment_customer_qty_wrap">
		<input type="number" name="_wc_appointment_qty_min" id="_wc_appointment_qty_min" value="<?php echo $capacity_min; ?>" step="1" min="1" max="<?php echo $capacity; ?>" style="margin-right: 7px; width: 4em;">
		<span class="description"><?php _e( 'Min', 'woocommerce-appointments' ); ?></span>
		<?php echo wc_help_tip( __( 'The minimum number of customers required per slot.', 'woocommerce-appointments' ) ); ?>
	</p>
	<p class="form-field _wc_appointment_customer_qty_wrap">
		<input type="number" name="_wc_appointment_qty_max" id="_wc_appointment_qty_max" value="<?php echo $capacity_max; ?>" step="1" min="1" max="<?php echo $capacity; ?>" style="margin-right: 7px; width: 4em;">
		<span class="description"><?php _e( 'Max', 'woocommerce-appointments' ); ?></span>
		<?php echo wc_help_tip( __( 'The maximum number of customers allowed per slot.', 'woocommerce-appointments' ) ); ?>
	</p>
	<?php
		$duration      = max( absint( get_post_meta( $post_id, '_wc_appointment_duration', true ) ), 1 );
		$duration_unit = get_post_meta( $post_id, '_wc_appointment_duration_unit', true );
		if ( '' == $duration_unit ) {
			$duration_unit = 'hour';
		}
	?>
	<p class="form-field">
		<label for="_wc_appointment_duration"><?php _e( 'Duration', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_duration" id="_wc_appointment_duration" value="<?php echo $duration; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_duration_unit" id="_wc_appointment_duration_unit" class="short" style="width: auto; margin-right: 7px;">
			<option value="minute" <?php selected( $duration_unit, 'minute' ); ?>><?php _e( 'Minute(s)', 'woocommerce-appointments' ); ?></option>
			<option value="hour" <?php selected( $duration_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
			<option value="day" <?php selected( $duration_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-appointments' ); ?></option>
		</select>
		<?php echo wc_help_tip( __( 'How long do you plan this appointment to last?', 'woocommerce-appointments' ) ); ?>
	</p>
	<?php
		$interval_s    = get_post_meta( $post_id, '_wc_appointment_interval', true );
		if ( '' == $interval_s ) {
			$interval = $duration;
		} else {
			$interval = max( absint( $interval_s ), 1 );
		}
		$interval_unit = get_post_meta( $post_id, '_wc_appointment_interval_unit', true );
		if ( '' == $interval_unit ) {
			$interval_unit = $duration_unit;
		} elseif ( 'day' == $interval_unit ) {
			$interval_unit = 'hour';
		}
	?>
	<p class="form-field _wc_appointment_interval_duration_wrap">
		<label for="_wc_appointment_interval"><?php _e( 'Interval', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_interval" id="_wc_appointment_interval" value="<?php echo $interval; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_interval_unit" id="_wc_appointment_interval_unit" class="short" style="width: auto; margin-right: 7px;">
			<option value="minute" <?php selected( $interval_unit, 'minute' ); ?>><?php _e( 'Minute(s)', 'woocommerce-appointments' ); ?></option>
			<option value="hour" <?php selected( $interval_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
		</select>
		<?php echo wc_help_tip( __( 'Select intervals when each appointment slot is available for scheduling?', 'woocommerce-appointments' ) ); ?>
	</p>
	<?php
		$padding_duration      = absint( get_post_meta( $post_id, '_wc_appointment_padding_duration', true ) );
		$padding_duration_unit = get_post_meta( $post_id, '_wc_appointment_padding_duration_unit', true );
		if ( '' == $padding_duration_unit ) {
			$padding_duration_unit = 'minute';
		}
	?>
	<p class="form-field _wc_appointment_padding_duration_wrap">
		<label for="_wc_appointment_padding_duration"><?php _e( 'Padding Time', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_padding_duration" id="_wc_appointment_padding_duration" value="<?php echo $padding_duration; ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_padding_duration_unit" id="_wc_appointment_padding_duration_unit" class="short" style="width: auto; margin-right: 7px;">
			<option value="minute" <?php selected( $padding_duration_unit, 'minute' ); ?>><?php _e( 'Minute(s)', 'woocommerce-appointments' ); ?></option>
			<option value="hour" <?php selected( $padding_duration_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
			<option value="day" <?php selected( $padding_duration_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-appointments' ); ?></option>
		</select>
		<?php echo wc_help_tip( __( 'Specify the padding time you need between appointments.', 'woocommerce-appointments' ) ); ?>
	</p>
	<?php
		$min_date      = absint( get_post_meta( $post_id, '_wc_appointment_min_date', true ) );
		$min_date_unit = get_post_meta( $post_id, '_wc_appointment_min_date_unit', true );
		if ( '' == $min_date_unit ) {
			$min_date_unit = 'month';
		}
	?>
	<p class="form-field">
		<label for="_wc_appointment_min_date"><?php _e( 'Lead Time', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_min_date" id="_wc_appointment_min_date" value="<?php echo $min_date; ?>" step="1" min="0" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_min_date_unit" id="_wc_appointment_min_date_unit" class="short" style="margin-right: 7px; width: auto;">
			<option value="hour" <?php selected( $min_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
			<option value="day" <?php selected( $min_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-appointments' ); ?></option>
			<option value="week" <?php selected( $min_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-appointments' ); ?></option>
			<option value="month" <?php selected( $min_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-appointments' ); ?></option>
		</select> <?php echo wc_help_tip( __( 'How much in advance do you need before a client schedules an appointment?', 'woocommerce-appointments' ) ); ?>
	</p>
	<?php
		$max_date = get_post_meta( $post_id, '_wc_appointment_max_date', true );
		if ( '' == $max_date ) {
			$max_date = 12;
		}
		$max_date      = max( absint( $max_date ), 1 );
		$max_date_unit = get_post_meta( $post_id, '_wc_appointment_max_date_unit', true );
		if ( '' == $max_date_unit ) {
			$max_date_unit = 'month';
		}
	?>
	<p class="form-field">
		<label for="_wc_appointment_max_date"><?php _e( 'Scheduling Window', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_max_date" id="_wc_appointment_max_date" value="<?php echo $max_date; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_max_date_unit" id="_wc_appointment_max_date_unit" class="short" style="margin-right: 7px; width: auto;">
			<option value="hour" <?php selected( $max_date_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
			<option value="day" <?php selected( $max_date_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-appointments' ); ?></option>
			<option value="week" <?php selected( $max_date_unit, 'week' ); ?>><?php _e( 'Week(s)', 'woocommerce-appointments' ); ?></option>
			<option value="month" <?php selected( $max_date_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-appointments' ); ?></option>
		</select>
		<?php echo wc_help_tip( __( 'How far in advance are customers allowed to schedule an appointment?', 'woocommerce-appointments' ) ); ?>
	</p>
</div>
<div class="options_group show_if_appointment">
	<?php
		woocommerce_wp_checkbox( array(
			'id'          => '_wc_appointment_requires_confirmation',
			'label'       => __( 'Requires confirmation?', 'woocommerce-appointments' ),
			'description' => __( 'Check this box if the appointment requires approval/confirmation. Payment will not be taken during checkout.', 'woocommerce-appointments' ),
		) );

		woocommerce_wp_checkbox( array(
			'id'          => '_wc_appointment_user_can_cancel',
			'label'       => __( 'Can be cancelled?', 'woocommerce-appointments' ),
			'description' => __( 'Check this box if the appointment can be cancelled by the customer. A refund will not be sent automatically.', 'woocommerce-appointments' ),
		) );

		$cancel_limit      = max( absint( get_post_meta( $post_id, '_wc_appointment_cancel_limit', true ) ), 1 );
		$cancel_limit_unit = get_post_meta( $post_id, '_wc_appointment_cancel_limit_unit', true );
		if ( '' == $cancel_limit_unit ) {
			$cancel_limit_unit = 'day';
		}
	?>
	<p class="form-field appointment-cancel-limit">
		<label for="_wc_appointment_cancel_limit"><?php _e( 'Cancelled at least', 'woocommerce-appointments' ); ?></label>
		<input type="number" name="_wc_appointment_cancel_limit" id="_wc_appointment_cancel_limit" value="<?php echo $cancel_limit; ?>" step="1" min="1" style="margin-right: 7px; width: 4em;">
		<select name="_wc_appointment_cancel_limit_unit" id="_wc_appointment_cancel_limit_unit" class="short" style="width: auto; margin-right: 7px;">
			<option value="month" <?php selected( $cancel_limit_unit, 'month' ); ?>><?php _e( 'Month(s)', 'woocommerce-appointments' ); ?></option>
			<option value="day" <?php selected( $cancel_limit_unit, 'day' ); ?>><?php _e( 'Day(s)', 'woocommerce-appointments' ); ?></option>
			<option value="hour" <?php selected( $cancel_limit_unit, 'hour' ); ?>><?php _e( 'Hour(s)', 'woocommerce-appointments' ); ?></option>
			<option value="minute" <?php selected( $cancel_limit_unit, 'minute' ); ?>><?php _e( 'Minute(s)', 'woocommerce-appointments' ); ?></option>
		</select>
		<span class="description"><?php _e( 'before the start date.', 'woocommerce-appointments' ); ?></span>
	</p>

	<script type="text/javascript">
		jQuery( '._tax_status_field' ).closest( '.show_if_simple' ).addClass( 'show_if_appointment' );
		jQuery( 'select#_wc_appointment_duration_unit, select#_wc_appointment_duration_type, input#_wc_appointment_duration' ).change(function(){
			if ( 'day' === jQuery('select#_wc_appointment_duration_unit').val() && '1' == jQuery('input#_wc_appointment_duration').val() && 'customer' === jQuery('select#_wc_appointment_duration_type').val() ) {
				jQuery('p._wc_appointment_enable_range_picker_field').show();
			} else {
				jQuery('p._wc_appointment_enable_range_picker_field').hide();
			}
		});
		jQuery( '#_wc_appointment_duration_unit' ).change();
	</script>
</div>
<div class="options_group show_if_appointment">
	<?php
		$cal_color_val = get_post_meta( $post_id, '_wc_appointment_cal_color', true );
		if ( '' == $cal_color_val ) {
			$cal_color_val = '#0073aa'; // default color
		}
		woocommerce_wp_text_input( array(
			'id' => '_wc_appointment_cal_color',
			'label' => __( 'Calendar color', 'woocommerce-appointments' ),
			'value' => $cal_color_val,
			'description' => __( 'Pick a color that will represent this appointable product inside admin calendar.', 'woocommerce-appointments' ),
		) );
	?>
</div>
