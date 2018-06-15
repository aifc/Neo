<div class="wrap woocommerce">
	<h2><?php _e( 'Calendar', 'woocommerce-appointments' ); ?> <a href="<?php echo admin_url( 'edit.php?post_type=wc_appointment&page=add_appointment' ); ?>" class="add-new-h2"><?php _e( 'Add Appointment', 'woocommerce-appointments' ); ?></a></h2>

	<form method="get" id="mainform" enctype="multipart/form-data" class="wc_appointments_calendar_form month_view">
		<input type="hidden" name="post_type" value="wc_appointment" />
		<input type="hidden" name="page" value="appointment_calendar" />
		<input type="hidden" name="calendar_month" value="<?php echo absint( $month ); ?>" />
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
								<option value="staff_<?php echo $filter_id; ?>" <?php selected( $staff_filter, $filter_id ); ?>><?php echo $filter_name; ?></option>
							<?php endforeach; ?>
						</optgroup>
					<?php endif; ?>
				</select>
			</div>
			<div class="date_selector">
				<a class="prev" href="<?php
				echo esc_url( add_query_arg( array(
					'calendar_year' => $year,
					'calendar_month' => $month - 1,
				) ) ); ?>">&larr;</a>
				<div>
					<select name="calendar_month" class="wc-enhanced-select" style="width:160px">
						<?php for ( $i = 1; $i <= 12; $i ++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( $month, $i ); ?>><?php echo ucfirst( date_i18n( 'M', strtotime( '2013-' . $i . '-01' ) ) ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div>
					<select name="calendar_year" class="wc-enhanced-select" style="width:160px">
						<?php $current_year = date( 'Y' ); ?>
						<?php for ( $i = ( $current_year - 1 ); $i <= ( $current_year + 5 ); $i ++ ) : ?>
							<option value="<?php echo $i; ?>" <?php selected( $year, $i ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<a class="next" href="<?php
				echo esc_url( add_query_arg( array(
					'calendar_year' => $year,
					'calendar_month' => $month + 1,
				) ) ); ?>">&rarr;</a>
			</div>
			<div class="views">
				<span><?php _e( 'Month', 'woocommerce-appointments' ); ?></span>
				<a class="week" href="<?php echo esc_url( add_query_arg( 'view', 'week' ) ); ?>" title="<?php _e( 'Week View', 'woocommerce-appointments' ); ?>"><?php _e( 'Week', 'woocommerce-appointments' ); ?></a>
				<a class="month" href="<?php echo esc_url( add_query_arg( 'view', 'day' ) ); ?>" title="<?php _e( 'Day View', 'woocommerce-appointments' ); ?>"><?php _e( 'Day', 'woocommerce-appointments' ); ?></a>
			</div>
			<script type="text/javascript">
				jQuery(function() {
					jQuery(".tablenav select").change(function() {
						jQuery('#mainform').submit();
					});
				});
			</script>
		</div>

		<table class="wc_appointments_calendar widefat">
			<thead>
				<tr>
					<?php $start_of_week = absint( get_option( 'start_of_week', 1 ) ); ?>
					<?php for ( $ii = $start_of_week; $ii < $start_of_week + 7; $ii ++ ) : ?>
						<th><?php echo date_i18n( _x( 'l', 'date format', 'woocommerce-appointments' ), strtotime( "next sunday +{$ii} day" ) ); ?></th>
					<?php endfor; ?>
				</tr>
			</thead>
			<tbody>
				<tr>
					<?php
					$timestamp = $start_time;
					$index     = 0;
					while ( $timestamp <= $end_time ) :
						?>
						<td width="14.285%" class="<?php
						if ( date( 'n', $timestamp ) != absint( $month ) ) {
							echo 'calendar-diff-month';
						}
						?>">
							<a href="<?php echo admin_url( 'edit.php?post_type=wc_appointment&page=appointment_calendar&view=day&tab=calendar&calendar_day=' . date( 'Y-m-d', $timestamp ) ); ?>" class="datenum">
								<?php echo date( 'd', $timestamp ); ?>
							</a>
							<div class="appointments">
								<ul>
									<?php
									$this->list_appointments(
										date( 'd', $timestamp ),
										date( 'm', $timestamp ),
										date( 'Y', $timestamp )
									);
									?>
								</ul>
							</div>
						</td>
						<?php
						$timestamp = strtotime( '+1 day', $timestamp );
						$index ++;

						if ( 0 === $index % 7 ) {
							echo '</tr><tr>';
						}
					endwhile;
					?>
				</tr>
			</tbody>
		</table>
	</form>
</div>
