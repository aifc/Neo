<?php

class WC_Appointments_Admin_Calendar {

	private $appointments;

	/**
	 * Output the calendar view
	 */
	public function output() {
		$product_filter = '';
		$staff_filter = '';

		if ( isset( $_REQUEST['filter_appointments'] ) ) {
			// is product the selected filter?
			if ( strpos( $_REQUEST['filter_appointments'], 'product_' ) !== false ) {
				$product_filter = absint( str_replace( 'product_', '', $_REQUEST['filter_appointments'] ) );

			}
			// is staff the selected filter?
			if ( strpos( $_REQUEST['filter_appointments'], 'staff_' ) !== false ) {
				$staff_filter = absint( str_replace( 'staff_', '', $_REQUEST['filter_appointments'] ) );
			}
		}

		// Override to only show appointments for current staff member.
		if ( ! current_user_can( 'manage_others_appointments' ) ) {
			$staff_filter = get_current_user_id();
		}

		$default_view = apply_filters( 'woocommerce_appointments_calendar_view', 'month' );

		$view = isset( $_REQUEST['view'] ) ? $_REQUEST['view'] : $default_view;

		if ( 'day' == $view ) {
			$day      = isset( $_REQUEST['calendar_day'] ) ? wc_clean( $_REQUEST['calendar_day'] ) : date( get_option( 'date_format' ) );
			$prev_day = date( get_option( 'date_format' ), strtotime( '-1 day', strtotime( date( 'Y-m-d' ) ) ) );
			$next_day = date( get_option( 'date_format' ), strtotime( '+1 day', strtotime( date( 'Y-m-d' ) ) ) );

			#$prev_day = date_i18n( get_option('date_format'), strtotime( '-1 day', strtotime( $day ) ) );

			$this->appointments = WC_Appointments_Controller::get_appointments_in_date_range(
				strtotime( 'midnight', strtotime( $day ) ),
				strtotime( 'midnight +1 day', strtotime( $day ) ),
				$product_filter,
				$staff_filter,
				false
			);
		} else {
			$month          = isset( $_REQUEST['calendar_month'] ) ? absint( $_REQUEST['calendar_month'] ) : date( 'n' );
			$year           = isset( $_REQUEST['calendar_year'] ) ? absint( $_REQUEST['calendar_year'] ) : date( 'Y' );

			if ( $year < ( date( 'Y' ) - 10 ) || $year > 2100 )
				$year = date( 'Y' );

			if ( $month > 12 ) {
				$month = 1;
				$year ++;
			}

			if ( $month < 1 ) {
				$month = 12;
				$year --;
			}

			$start_of_week = absint( get_option( 'start_of_week', 1 ) );
			$last_day      = date( 't', strtotime( "$year-$month-01" ) );
			$start_date_w  = absint( date( 'w', strtotime( "$year-$month-01" ) ) );
			$end_date_w    = absint( date( 'w', strtotime( "$year-$month-$last_day" ) ) );

			// Calc day offset
			$day_offset = $start_date_w - $start_of_week;
			$day_offset = $day_offset >= 0 ? $day_offset : 7 - abs( $day_offset );

			// Calc end day offset
			$end_day_offset = 7 - ( $last_day % 7 ) - $day_offset;
			$end_day_offset = $end_day_offset >= 0 && $end_day_offset < 7 ? $end_day_offset : 7 - abs( $end_day_offset );

			// We want to get the last minute of the day, so we will go forward one day to midnight and subtract a min
			$end_day_offset = $end_day_offset + 1;

			$start_timestamp   = strtotime( "-{$day_offset} day", strtotime( "$year-$month-01" ) );
			$end_timestamp     = strtotime( "+{$end_day_offset} day midnight -1 min", strtotime( "$year-$month-$last_day" ) );

			$this->appointments = WC_Appointments_Controller::get_appointments_in_date_range(
				$start_timestamp,
				$end_timestamp,
				$product_filter,
				$staff_filter,
				false
			);
		}

		include( 'views/html-calendar-' . $view . '.php' );
		include( 'views/html-calendar-dialog.php' );
	}

	/**
	 * List appointments for a day
	 *
	 * @param  [type] $day
	 * @param  [type] $month
	 * @param  [type] $year
	 * @return [type]
	 */
	public function list_appointments( $day, $month, $year ) {
		$date_start = strtotime( "$year-$month-$day 00:00" );
		$date_end   = strtotime( "$year-$month-$day 23:59" );

		foreach ( $this->appointments as $appointment ) {
			if (
				( $appointment->start >= $date_start && $appointment->start <= $date_end ) ||
				( $appointment->start < $date_start && $appointment->end > $date_end ) ||
				( $appointment->end > $date_start && $appointment->end <= $date_end )
				) {
					$this->single_appointment_card( $appointment );
			}
		}
	}

	/**
	 * List appointments on a day
	 */
	public function list_appointments_for_day( $view = 'by_time' ) {

		$appointments_by_time = array();
		$all_day_appointments = array();

		foreach ( $this->appointments as $appointment ) {
			if ( $appointment->is_all_day() ) {
				$all_day_appointments[] = $appointment;
			} else {
				$start_time = $appointment->get_start_date( '', 'Hi' );

				if ( ! isset( $appointments_by_time[ $start_time ] ) ) {
					$appointments_by_time[ $start_time ] = array();
				}

				$appointments_by_time[ $start_time ][] = $appointment;
			}
		}

		ksort( $appointments_by_time );

		$column = 0;

		if ( 'all_day' == $view ) {

			// All day appointments.
			foreach ( $all_day_appointments as $appointment ) {

				$this->single_appointment_card( $appointment, $column, $view );

				$column++;
			}
		} elseif ( 'by_time' == $view ) {

			$start_column = $column;
			$last_end     = 0;
			$calendar_scale = apply_filters( 'woocommerce_appointments_calendar_view_day_scale', 60 );

			foreach ( $appointments_by_time as $appointments ) {
				foreach ( $appointments as $appointment ) {
					$start_time = $appointment->get_start_date( '', 'Hi' );
					$end_time   = $appointment->get_end_date( '', 'Hi' );
					$height = ( ( ( substr( $end_time, 0, 2 ) * 60 ) + substr( $end_time, -2 ) ) - ( ( substr( $start_time, 0, 2 ) * 60 ) + substr( $start_time, -2 ) ) ) / 60 * $calendar_scale ;

					if ( $height < 30 ) {
						$height = 30;
					}

					if ( $last_end > $start_time ) {
						$column++;
					} else {
						$column = $start_column;
					}

					$this->single_appointment_card( $appointment, $column, $view, $height );

					if ( $end_time > $last_end ) {
						$last_end = $end_time;
					}
				}
			}
		}
	}

	/**
	 * Single appointments card
	 */
	public function single_appointment_card( $appointment, $column = 0, $view = 'month', $height = 0 ) {
		// Array
		$datarray = array();
		// Data
		if ( 'all_day' == $view ) {
			$datarray['start_time'] = $appointment->get_start_date( 'Y-m-d', '' );
			$datarray['end_time']   = $appointment->get_end_date( 'Y-m-d', '' );
		} else {
			$datarray['start_time'] = $appointment->get_start_date( '', 'Hi' );
			$datarray['end_time']   = $appointment->get_end_date( '', 'Hi' );
		}
		$datarray['order_id'] = wp_get_post_parent_id( $appointment->id );
		$datarray['staff_id'] = get_post_meta( $appointment->id, '_appointment_staff_id', false );
		if ( ! is_array( $datarray['staff_id'] ) ) {
			$datarray['staff_id'] = array( $datarray['staff_id'] );
		}
		$datarray['staff_name'] = $appointment->get_staff_members( $names = true );
		$datarray['appointment_date'] = $appointment->get_start_date( wc_date_format(), '' );
		$datarray['appointment_time'] = $appointment->get_start_date( '', wc_time_format() ) . ' &mdash; ' . $appointment->get_end_date( '', wc_time_format() );
		$datarray['appointment_qty'] = get_post_meta( $appointment->id, '_appointment_qty', true );
		$datarray['appointment_cost'] = '';
		$datarray['order_status'] = '';
		if ( $datarray['order_id'] = wp_get_post_parent_id( $appointment->id ) ) {
			$order = wc_get_order( $datarray['order_id'] );
			$datarray['appointment_cost'] = is_object( $order ) ? esc_html( $order->get_formatted_order_total() ) : '';
			$datarray['order_status'] = $order->get_status();
		}
		$datarray['appointment_status'] = $appointment->status;
		$datarray['customer_status'] = get_post_meta( $appointment->id, '_appointment_customer_status', true );
		$datarray['customer_status'] = $datarray['customer_status'] ? $datarray['customer_status'] : 'expected';
		$customer = $appointment->get_customer();
		$datarray['customer_id'] = '';
		$datarray['customer_name'] = __( 'Guest', 'woocommerce-appointments' );
		$datarray['customer_phone'] = '';
		$datarray['customer_email'] = '';
		$datarray['customer_url'] = '';
		$datarray['customer_avatar'] = get_avatar_url( '', array(
			'size' => 100,
			'default' => 'mm',
		));
		if ( $customer && $customer->user_id ) {
			$user = get_user_by( 'id', $customer->user_id );
			$datarray['customer_id'] = $customer->user_id;
			if ( '' != $user->first_name || '' != $user->last_name ) {
				$datarray['customer_name'] = $user->first_name . ' ' . $user->last_name;
			} else {
				$datarray['customer_name'] = $user->display_name;
			}
			$datarray['customer_phone'] = preg_replace( '/\s+/', '', $customer->phone );
			$datarray['customer_email'] = $customer->email;
			$datarray['customer_url'] = get_edit_user_link( $datarray['customer_id'] );
			$datarray['customer_avatar'] = get_avatar_url( $datarray['customer_id'], array(
				'size' => 110,
				'default' => 'mm',
			));
		}
		$appointment_product = $appointment->get_product();
		$datarray['product_id'] = $appointment->get_product_id();
		$datarray['product_title'] = is_object( $appointment_product ) ? $appointment_product->get_title() : '';
		if ( wc_appointments_gcal_synced_product_id() == $datarray['product_id'] ) {
			$datarray['product_title'] = __( '[ Google Calendar ]', 'woocommerce-appointments' );
		}
		$appointment_color = is_object( $appointment_product ) ? $appointment_product->get_cal_color() : '#0073aa';
		$calendar_scale = apply_filters( 'woocommerce_appointments_calendar_view_day_scale', 60 );
		$appointment_top = ( ( intval( substr( $datarray['start_time'], 0, 2 ) ) * 60) + intval( substr( $datarray['start_time'], -2 ) ) ) / 60 * $calendar_scale;

		if ( $appointment->is_all_day() ) {
			$datarray['appointment_datetime'] = '';
		} else {
			$datarray['appointment_datetime'] = $appointment->get_start_date( '', wc_time_format() ) . '&mdash;' . $appointment->get_end_date( '', wc_time_format() );
		}

		if ( ( $customer = $appointment->get_customer() ) && ! empty( $customer->name ) ) {
			$datarray['appointment_customer'] = $customer->name;
		} else {
			$datarray['appointment_customer'] = __( 'Guest', 'woocommerce-appointments' );
		}

		// Alternative View: Staff Columns
		$multiple_staff = '';
		$columns_by_staff = apply_filters( 'woocommerce_appointments_calendar_view_by_staff', false );
		if ( $columns_by_staff ) {
			$staff = WC_Appointments_Admin::get_appointment_staff();
			$staff_count = count( $staff );
			// Assign column to match staff by index of array
			for ( $i = 0; $i < $staff_count; $i++ ) {
				// If no provider is assigned
				if ( '' === $datarray['staff_name'] ) {
					$column = $staff_count;
				// Check by id if staff exisits in array
				} elseif ( in_array( $staff[ $i ]->ID, $datarray['staff_id'] ) ) {
					$column = $i;
					// If out of range
					if ( $column < 0 || $column > $staff_count ) {
						$column = $staff_count;
					}
					// Display blocked time for other staff linked to this appointment.
					if ( count( $datarray['staff_id'] ) > 1 && $datarray['staff_id'][0] != $staff[ $i ]->ID ) {
						if ( 'all_day' == $view ) {
							$multiple_staff .= '<li parent-appointment-id="' . $appointment->id . '" class="multiple-staff-appointment" style="background: ' . $appointment_color . '; left:' . ( ( 170 * $column ) + 100 ) . 'px;"><a></a></li>';
						} else {
							$multiple_staff .= '<li parent-appointment-id="' . $appointment->id . '" class="multiple-staff-appointment" style="background: ' . $appointment_color . '; left:' . ( ( 170 * $column ) + 100 ) . 'px; top: ' . $appointment_top . 'px; height: ' . $height . 'px;"><a></a></li>';
						}
					}
				}
			}
		}

		if ( 'all_day' == $view ) {
			if ( $columns_by_staff ) {
				$style = 'background: ' . $appointment_color . '; left:' . (( 170 * $column ) + 100) . 'px;';
			} else {
				$style = 'background: ' . $appointment_color . '';
			}
		} elseif ( 'by_time' == $view ) {
			$style = 'background: ' . $appointment_color . '; left:' . (( 170 * $column ) + 100) . 'px; top: ' . $appointment_top . 'px; height: ' . $height . 'px;';
		} else {
			$style = 'background: ' . $appointment_color . '';
		}
		$singlecard = '<li title="' . __( 'View / Edit', 'woocommerce-appointments' ) . '"
		data-appointment-id="' . $appointment->id . '"
		data-product-id="' . $datarray['product_id'] . '"
		data-product-title="' . $datarray['product_title'] . '"
		data-order-id="' . $datarray['order_id'] . '"
		data-order-status="' . $datarray['order_status'] . '"
		data-appointment-cost="' . $datarray['appointment_cost'] . '"
		data-appointment-start="' . $datarray['start_time'] . '"
		data-appointment-end="' . $datarray['end_time'] . '"
		data-appointment-date="' . $datarray['appointment_date'] . '"
		data-appointment-time="' . $datarray['appointment_time'] . '"
		data-appointment-qty="' . $datarray['appointment_qty'] . '"
		data-appointment-status="' . $datarray['appointment_status'] . '"
		data-appointment-staff="' . ( is_array( $datarray['staff_name'] ) && ! empty( $datarray['staff_name'] ) ? implode( ', ', $datarray['staff_name'] ) : ( ! empty( $datarray['staff_name'] ) ? $datarray['staff_name'] : '' ) ) . '"
		data-customer-status="' . $datarray['customer_status'] . '"
		data-customer-id="' . $datarray['customer_id'] . '"
		data-customer-url="' . $datarray['customer_url'] . '"
		data-customer-name="' . $datarray['customer_name'] . '"
		data-customer-phone="' . $datarray['customer_phone'] . '"
		data-customer-email="' . $datarray['customer_email'] . '"
		data-customer-avatar="' . $datarray['customer_avatar'] . '"
		class="status_' . $datarray['appointment_status'] . ' customer_status_' . $datarray['customer_status'] . '"
		style="' . $style . '">
		<a href="' . admin_url( 'post.php?post=' . $appointment->id . '&action=edit' ) . '">
			<strong class="appointment_datetime">' . $datarray['appointment_datetime'] . '</strong>
			<ul>
				<li class="appointment_customer status-' . $datarray['customer_status'] . '">' . $datarray['appointment_customer'] . '</li>
				<li class="appointment_status status-' . $datarray['appointment_status'] . '" data-tip="' . $datarray['appointment_status'] . '"></li>
			</ul>
		</a>
		</li>' . $multiple_staff;

		echo apply_filters( 'woocommerce_appointments_calendar_view_single_card', $singlecard, $datarray, $appointment );
	}

	/**
	 * Filters products for narrowing search
	 */
	public function product_filters() {
		$filters = array();

		foreach ( get_wc_appointment_products() as $product ) {
			$filters[ $product->ID ] = $product->post_title;
		}

		return $filters;
	}

	/**
	 * Filters staff for narrowing search
	 */
	public function staff_filters() {
		$filters = array();

		// Only show staff filter if current user can see other staff's appointments.
		if ( ! current_user_can( 'manage_others_appointments' ) ) {
			return $filters;
		}

		$staff = WC_Appointments_Admin::get_appointment_staff();

		foreach ( $staff as $staff ) {
			$filters[ $staff->ID ] = $staff->display_name;
		}

		return $filters;
	}

	/**
	 * Calendar Head: Create columns for staff
	 */
	public function staff_columns( $count = 0 ) {

		$current_user = wp_get_current_user();
		$user_name = $current_user->user_login;

		$staff = WC_Appointments_Admin::get_appointment_staff();

		switch ( $count ) {
			case 1:
			$staff_count = count( $staff );
			return $staff_count;

			case 0:
			foreach ( $staff as $user ) {
				$staff_name = esc_html( $user->display_name );
				$staff_url = get_edit_user_link( $user->ID );
				$staff_id = $user->ID;
				echo '<li class="staff_column" data-staff-id="' . $staff_id . '"';
				if ( $user_name == $staff_name ) {
					echo 'id="current_user"';
				}
				echo '><a href="' . $staff_url . '#staff-details" title="' . __( 'Edit User and Availability', 'woocommerce-appointments' ) . '">' . get_avatar( $staff_id, 20, 'mm' ) . '<span>' . $staff_name . '</span></a></li>';
			}
			// Unassigned Appointments
			echo '<li id="unassigned_staff" class="secondary">' . __( 'Unassigned', 'woocommerce-appointments' ) . '</li>';
		}
	}

}
