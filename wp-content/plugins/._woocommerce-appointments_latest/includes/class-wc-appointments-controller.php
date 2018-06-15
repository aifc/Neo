<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gets appointments
 */
class WC_Appointments_Controller {

	/**
	 * Return all appointments for a product and/or staff in a given range
	 * @param integer $start_date
	 * @param integer $end_date
	 * @param integer $product_id
	 * @param integer staff_id
	 * @param bool $check_in_cart
	 *
	 * @return array
	 */
	public static function get_appointments_in_date_range( $start_date, $end_date, $product_id = 0, $staff_id = 0, $check_in_cart = true, $filters = array() ) {
		// Synced appointments from GCal.
		if ( wc_appointments_gcal_synced_product_id() ) {
			if ( is_array( $product_id ) ) {
				$product_id[] = wc_appointments_gcal_synced_product_id();
			} elseif ( ! is_array( $product_id ) && ! empty( $product_id ) ) {
				$product_id = array( $product_id, wc_appointments_gcal_synced_product_id() );
			} elseif ( empty( $product_id ) ) {
				$product_id = $product_id;
			}
		}

		$transient_name = 'schedule_dr_' . md5( http_build_query( array( $start_date, $end_date, $product_id, $staff_id, $check_in_cart, WC_Cache_Helper::get_transient_version( 'appointments' ) ) ) );
		$appointment_ids = get_transient( $transient_name );

		if ( false === $appointment_ids ) {
			$appointment_ids = self::get_appointments_in_date_range_query( $start_date, $end_date, $product_id, $staff_id, $check_in_cart, $filters );
			set_transient( $transient_name, $appointment_ids, DAY_IN_SECONDS * 30 );
		}

		#var_dump( $appointment_ids );

		// Get objects
		return array_map( 'get_wc_appointment', wp_parse_id_list( $appointment_ids ) );
	}

	/**
	 * Return an array of un-appointable padding days
	 * @since 2.0.0
	 *
	 * @param  WC_Product_Appointment|int $appointable_product
	 * @return array Days that are padding days and therefor should be un-appointable
	 */
	public static function find_padding_day_slots( $appointable_product ) {
		if ( is_int( $appointable_product ) ) {
			$appointable_product = wc_get_product( $appointable_product );
		}
		if ( ! is_a( $appointable_product, 'WC_Product_Appointment' ) ) {
			return array();
		}

		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $appointable_product );

		return WC_Appointments_Controller::get_padding_day_slots_for_scheduled_days( $appointable_product, $scheduled['fully_scheduled_days'] );
	}

	/**
	 * Return an array of un-appointable padding days
	 * @since 3.3.0
	 *
	 * @param  WC_Product_Appointment|int $appointable_product
	 * @return array Days that are padding days and therefor should be un-appointable
	 */
	public static function get_padding_day_slots_for_scheduled_days( $appointable_product, $fully_scheduled_days ) {
		if ( is_int( $appointable_product ) ) {
			$appointable_product = wc_get_product( $appointable_product );
		}
		if ( ! is_a( $appointable_product, 'WC_Product_Appointment' ) ) {
			return array();
		}

		$padding_duration   = $appointable_product->get_padding_duration();
		$padding_days       = array();

		foreach ( $fully_scheduled_days as $date => $data ) {
			$next_day = strtotime( '+1 day', strtotime( $date ) );

			if ( array_key_exists( date( 'Y-n-j', $next_day ), $fully_scheduled_days ) ) {
				continue;
			}

			// x days after
			for ( $i = 1; $i < $padding_duration + 1; $i++ ) {
				$padding_day = date( 'Y-n-j', strtotime( "+{$i} day", strtotime( $date ) ) );
				$padding_days[ $padding_day ] = $padding_day;
			}
		}

		#if ( $appointable_product->get_apply_adjacent_padding() ) {
			foreach ( $fully_scheduled_days as $date => $data ) {
				$previous_day = strtotime( '-1 day', strtotime( $date ) );

				if ( array_key_exists( date( 'Y-n-j', $previous_day ), $fully_scheduled_days ) ) {
					continue;
				}

				// x days before
				for ( $i = 1; $i < $padding_duration + 1; $i++ ) {
					$padding_day = date( 'Y-n-j', strtotime( "-{$i} day", strtotime( $date ) ) );
					$padding_days[ $padding_day ] = $padding_day;
				}
			}
		#}
		return $padding_days;
	}

	/**
	 * Finds existing appointments for a product and its tied staff.
	 *
	 * @param  WC_Product_Appointment $appointable_product
	 * @param  int                $min_date
	 * @param  int                $max_date
	 * @return array
	 */
	public static function get_all_existing_appointments( $appointable_product, $min_date = 0, $max_date = 0 ) {
		$find_appointments_for_product = array( $appointable_product->get_id() );
		$find_appointments_for_staff   = array();

		if ( $appointable_product->has_staff() ) {
			foreach ( $appointable_product->get_staff_ids() as $staff_member_id ) {
				$find_appointments_for_staff[] = $staff_member_id;
			}
		}

		if ( empty( $min_date ) ) {
			// Determine a min and max date
			$min_date = $appointable_product->get_min_date_a();
			$min_date = empty( $min_date ) ? array(
				'unit'  => 'minute',
				'value' => 1,
			) : $min_date ;
			$min_date = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
		}

		if ( empty( $max_date ) ) {
			$max_date = $appointable_product->get_max_date_a();
			$max_date = empty( $max_date ) ? array(
				'unit'  => 'month',
				'value' => 12,
			) : $max_date;
			$max_date = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );
		}

		return self::get_appointments_for_objects(
			$find_appointments_for_product,
			$find_appointments_for_staff,
			get_wc_appointment_statuses( 'fully_scheduled' ),
			$min_date,
			$max_date
		);
	}

	/**
	 * For hour appointments types check that the appointment is past midnight and before start time.
	 * This only works for the very next day after appointment start.
	 *
	 * @since 1.10.7
	 *
	 * @param WC_Appointment $appointment
	 * @param WC_Bookable_Product $product
	 * @param string $check_date
	 * @return boolean;
	 */
	private static function is_appointment_past_midnight_and_before_start_time( $appointment, $product, $check_date ) {
		// This handles appointments overlapping midnight when slots only start
		// from a specific hour.
		return (
			'hour' === $product->get_duration_unit()
			&& date( 'md', $appointment['end'] ) === ( date( 'md', $check_date ) )
		);
	}

	/**
	 * Finds days which are partially scheduled & fully scheduled already.
	 *
	 * This function will get a general min/max Appointment date, which initially is [today, today + 1 year]
	 * Based on the Appointments retrieved from that date, it will shrink the range to the [Appointments_min, Appointments_max]
	 * For the newly generated range, it will determine availability of dates by calling `wc_appointments_get_time_slots` on it.
	 *
	 * Depending on the data returned from it we set:
	 * Fully scheduled days     - for those dates that there are no more slot available
	 * Partially scheduled days - for those dates that there are some slots available
	 *
	 * @param  WC_Product_Appointment|int $appointable_product
	 * @param  int                $min_date
	 * @param  int                $max_date
	 * @return array( 'partially_scheduled_days', 'remaining_scheduled_days', 'fully_scheduled_days', 'unavailable_days' )
	 */
	public static function find_scheduled_day_slots( $appointable_product, $min_date = 0, $max_date = 0 ) {
		$scheduled_day_slots = array(
			'partially_scheduled_days' => array(),
			'remaining_scheduled_days' => array(),
			'fully_scheduled_days'     => array(),
			'unavailable_days'         => array(),
		);

		if ( is_int( $appointable_product ) ) {
			$appointable_product = wc_get_product( $appointable_product );
		}

		if ( ! is_a( $appointable_product, 'WC_Product_Appointment' ) ) {
			return $scheduled_day_slots;
		}

		// Get existing appointments and go through them to set partial/fully scheduled days
		$existing_appointments = self::get_all_existing_appointments( $appointable_product, $min_date, $max_date );

		if ( empty( $existing_appointments ) ) {
			return $scheduled_day_slots;
		}

		$min_appointment_date = INF;
		$max_appointment_date = -INF;
		$appointments = array();

		// Find the minimum and maximum appointment dates and store the appointment data in an array for further processing.
		foreach ( $existing_appointments as $existing_appointment ) {
			if ( ! is_a( $existing_appointment, 'WC_Appointment' ) ) {
				continue;
			}

			#print '<pre>'; print_r( $existing_appointment->get_id() ); print '</pre>';

			// Check appointment start and end times.
			$check_date    = strtotime( 'midnight', $existing_appointment->get_start() );
			$check_date_to = strtotime( 'midnight', $existing_appointment->get_end() - 1 ); #make sure midnight drops to same day

			#print '<pre>'; print_r( date( 'Y-m-d H:i', $check_date ) ); print '</pre>';
			#print '<pre>'; print_r( date( 'Y-m-d H:i', $check_date_to ) ); print '</pre>';

			// Get staff IDs. If non exist, make it zero (applies to all).
			$existing_staff_ids = $existing_appointment->get_staff_ids();
			$existing_staff_ids = ! is_array( $existing_staff_ids ) ? array( $existing_staff_ids ) : $existing_staff_ids;
			$existing_staff_ids = empty( $existing_staff_ids ) ? array( 0 ) : $existing_staff_ids;

			// If it's a appointment on the same day, move it before the end of the current day
			if ( $check_date_to === $check_date ) {
				$check_date_to = strtotime( '+1 day', $check_date ) - 1;
			}

			$min_appointment_date = min( $min_appointment_date, $check_date );
			$max_appointment_date = max( $max_appointment_date, $check_date_to );

			// If the appointment duration is day, make sure we add the (duration) days to unavailable days.
			// This will mark them as white on the calendar, since they are not fully scheduled, but rather
			// unavailable. The difference is that an appointment extending to those days is allowed.
			if ( 1 < $appointable_product->get_duration() && 'day' === $appointable_product->get_duration_unit() ) {
				$check_new_date = strtotime( '-' . ( $appointable_product->get_duration() - 1 ) . ' days', $min_appointment_date );

				// Mark the days between the fake appointment and the actual appointment as unavailable.
				while ( $check_new_date < $min_appointment_date ) {
					$date_format    = date( 'Y-n-j', $check_new_date );
					foreach ( $existing_staff_ids as $existing_staff_id ) {
						$scheduled_day_slots['unavailable_days'][ $date_format ][ $existing_staff_id ] = 1;
					}
					$check_new_date = strtotime( '+1 day', $check_new_date );
				}
			}

			$appointments[]   = array(
				'start' => $check_date,
				'end'   => $check_date_to,
				'staff' => $existing_staff_ids,
			);
		}

		$max_appointment_date = strtotime( '+1 day', $max_appointment_date );

		// Call these for the whole chunk range for the appointments since they're expensive
		$slots             = $appointable_product->get_slots_in_range( $min_appointment_date, $max_appointment_date );
		$slots_a           = array();
		$available_slots   = wc_appointments_get_time_slots( $appointable_product, $slots, array(), 0, 0, $min_appointment_date, $max_appointment_date );
		$available_slots_a = array();

		// Available slots for the days.
		foreach ( $available_slots as $slot => $quantity ) {
			foreach ( $quantity['staff'] as $staff_id => $availability ) {
				if ( $availability > 0 ) {
					$available_slots_a[ $staff_id ][] = date( 'Y-n-j', $slot );
				}
			}
		}

		// All available slots for the days.
		foreach ( $slots as $a_slot ) {
			$slots_a[] = date( 'Y-n-j', $a_slot );
		}

		#print '<pre>'; print_r( $slots ); print '</pre>';
		#print '<pre>'; print_r( $slots_a ); print '</pre>';
		#print '<pre>'; print_r( $appointments ); print '</pre>';
		#print '<pre>'; print_r( $available_slots ); print '</pre>';
		#print '<pre>'; print_r( $available_slots_a ); print '</pre>';

		// Go through [start, end] of each of the appointments by chunking it in days: [start, start + 1d, start + 2d, ..., end]
		// For each of the chunk check the available slots. If there are no slots, it is fully scheduled, otherwise partially scheduled.
		foreach ( $appointments as $appointment ) {
			$check_date = $appointment['start'];

			#print '<pre>'; print_r( date( 'Y-m-d', $check_date ) ); print '</pre>';

			while ( $check_date <= $appointment['end'] ) {
				/*
				if ( self::is_appointment_past_midnight_and_before_start_time( $appointment, $appointable_product, $check_date ) ) {
					$check_date = strtotime( '+1 day', $check_date );
					continue;
				}
				*/

				$date_format = date( 'Y-n-j', $check_date );
				$count_all_slots = is_array( $slots_a ) ? count( array_keys( $slots_a, $date_format ) ) : 0;

				// Remainging scheduled, when no staff selected.
				if ( $appointable_product->has_staff() && isset( $available_slots_a[0] ) && in_array( $date_format, $available_slots_a[0] ) ) {
					$count_available_slots = absint( count( array_keys( $available_slots_a[0], $date_format ) ) );

					$count_s = absint( $count_all_slots );
					$count_a = isset( $count_s ) && 0 !== $count_s ? $count_s : 1;
					$count_r = absint( round( ( $count_available_slots / $count_a ) * 10 ) );
					$count_r = ( 10 === $count_r ) ? 9 : $count_r;
					$count_r = ( 0 === $count_r ) ? 1 : $count_r;

					$scheduled_day_slots['remaining_scheduled_days'][ $date_format ][0] = $count_r;
				}

				foreach ( $appointment['staff'] as $existing_staff_id ) {
					$appointment_type = isset( $available_slots_a[ $existing_staff_id ] ) && in_array( $date_format, $available_slots_a[ $existing_staff_id ] ) ? 'partially_scheduled_days' : 'fully_scheduled_days';
					#print '<pre>'; print_r( $date_format ); print '</pre>';
					#print '<pre>'; print_r( $existing_staff_id ); print '</pre>';
					#print '<pre>'; print_r( $appointment_type ); print '</pre>';
					#print '<pre>'; print_r( $available_slots_a ); print '</pre>';
					$scheduled_day_slots[ $appointment_type ][ $date_format ][ $existing_staff_id ] = 1;
					// Remainging scheduled, when staff is selected.
					if ( 'partially_scheduled_days' === $appointment_type ) {
						$count_available_slots = count( array_keys( $available_slots_a[ $existing_staff_id ], $date_format ) );

						$count_s = absint( $count_all_slots );
						$count_a = isset( $count_s ) && 0 !== $count_s ? $count_s : 1;
						$count_b = absint( $count_available_slots );
						$count_r = absint( round( ( $count_b / $count_a ) * 10 ) );
						$count_r = ( 10 === $count_r ) ? 9 : $count_r;
						$count_r = ( 0 === $count_r ) ? 1 : $count_r;

						$scheduled_day_slots['remaining_scheduled_days'][ $date_format ][ $existing_staff_id ] = $count_r;
					}
				}

				$check_date = strtotime( '+1 day', $check_date );
			}
		}

		#print '<pre>'; print_r( $scheduled_day_slots ); print '</pre>';

		/**
		 * Filter the scheduled day slots calculated per project.
		 * @since 3.3.0
		 *
		 * @param array $scheduled_day_slots {staff
		 *  @type array $partially_scheduled_days
		 *  @type array $fully_scheduled_days
		 * }
		 * @param WC_Product $appointable_product
		 */
		return apply_filters( 'woocommerce_appointments_scheduled_day_slots', $scheduled_day_slots, $appointable_product );
	}

	/**
	 * Loop through given appointments to find those that are on or over lap the given date.
	 *
	 * @since 2.3.1
	 * @param  array $appointments
	 * @param  string $date
	 *
	 * @return array of appointment ids
	 */
	 public static function filter_appointments_on_date( $appointments, $date, $product ) {
		$appointments_on_date = array();
 		foreach ( $appointments as $appointment ) {
 			// Does the date we want to check fall on one of the days in the appointment?
 			if ( $appointment->get_start() <= $date && $appointment->get_end() >= $date ) {
 				// Google Calendar sync.
 				if ( $appointment->get_product_id() == wc_appointments_gcal_synced_product_id() ) {
 					$appointments_on_date[] = $product->get_qty();
 				} else {
 					$appointments_on_date[] = $appointment->get_qty();
 				}
 			}
 		}

 		return $appointments_on_date;
 	}

	/**
	 * Gets appointments for product ids and staff ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_objects( $product_ids = array(), $staff_ids = array(), $status = array(), $date_from = 0, $date_to = 0 ) {
		$transient_name  = 'schedule_fo_' . md5( http_build_query( array( $product_ids, $staff_ids, $date_from, $date_to, WC_Cache_Helper::get_transient_version( 'appointments' ) ) ) );
		$status          = ( ! empty( $status ) ) ? $status : get_wc_appointment_statuses( 'fully_scheduled' );
		$date_from 	     = ! empty( $date_from ) ? $date_from : strtotime( 'midnight', current_time( 'timestamp' ) );
		$date_to 	     = ! empty( $date_to ) ? $date_to : strtotime( '+12 month', current_time( 'timestamp' ) );
		$appointment_ids = get_transient( $transient_name );

		// Synced appointments from GCal.
		if ( wc_appointments_gcal_synced_product_id() ) {
			$product_ids[] = wc_appointments_gcal_synced_product_id();
		}

		if ( false === $appointment_ids ) {
			$appointment_ids = self::get_appointments_for_objects_query( $product_ids, $staff_ids, $status, $date_from, $date_to );
			set_transient( $transient_name, $appointment_ids, DAY_IN_SECONDS * 30 );
		}

		#echo '<pre>' . var_export( $appointment_ids, true ) . '</pre>';

		// Get objects.
		if ( ! empty( $appointment_ids ) ) {
			return array_map( 'get_wc_appointment', wp_parse_id_list( $appointment_ids ) );
		}

		return array();
	}

	/**
	 * Gets appointments for product ids and staff ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @param  integer  $date_from
	 * @param  integer  $date_to
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_objects_query( $product_ids, $staff_ids, $status, $date_from = 0, $date_to = 0 ) {
		$status     = ( ! empty( $status ) ) ? $status   : get_wc_appointment_statuses( 'fully_scheduled' );
		$date_from 	= ! empty( $date_from ) ? $date_from : strtotime( 'midnight', current_time( 'timestamp' ) );
		$date_to 	= ! empty( $date_to ) ? $date_to : strtotime( '+12 month', current_time( 'timestamp' ) );

		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by( array(
			'status'       => $status,
			'product_id'   => $product_ids,
			'staff_id'     => $staff_ids,
			'object_type'  => 'product_and_staff',
			'date_between' => array(
				'start' => $date_from,
				'end'   => $date_to,
			),
		) );

		return $appointment_ids;
	}

	/**
	 * Gets appointments for a staff
	 *
	 * @param  int $staff_id ID
	 * @param  array  $status
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_staff( $staff_id, $status = array( 'confirmed', 'paid' ) ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by( array(
			'object_id'   => $staff_id,
			'object_type' => 'staff',
			'status'      => $status,
		) );
		return array_map( 'get_wc_appointment', $appointment_ids );
	}

	/**
	 * Gets appointments for a product by ID
	 *
	 * @param int $product_id The id of the product that we want appointments for
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_product( $product_id, $status = array( 'confirmed', 'paid' ) ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by( array(
			'object_id'   => $product_id,
			'object_type' => 'product',
			'status'      => $status,
		) );
		return array_map( 'get_wc_appointment', $appointment_ids );
	}

	/**
	 * Gets appointments for a user by ID
	 *
	 * @param  int   $user_id    The id of the user that we want appointments for
	 * @param  array $query_args The query arguments used to get appointment IDs
	 * @return array             Array of WC_Appointment objects
	 */
	public static function get_appointments_for_user( $user_id, $query_args = null ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by( array_merge( $query_args, array(
			'status'      => get_wc_appointment_statuses( 'user' ),
			'object_id'   => $user_id,
			'object_type' => 'customer',
		) ) );
		return array_map( 'get_wc_appointment', $appointment_ids );
	}

	/**
	 * Gets appointments for a customer by ID
	 *
	 * @deprecated 2.4.9
	 * @deprecated Use get_appointments()
	 * @see get_appointments()
	 *
	 * @param  int   $customer_id    The id of the customer that we want appointments for
	 * @param  array $query_args     The query arguments used to get appointment IDs
	 * @return array                 Array of WC_Appointment objects
	 */
	public static function get_appointments_for_customer( $customer_id, $query_args = null ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by( array(
			'status'      => get_wc_appointment_statuses( 'customer' ),
			'object_id'   => $customer_id,
			'object_type' => 'customer',
		) );
		return array_map( 'get_wc_appointment', $appointment_ids );
	}

	/**
	 * Return all appointments for a product in a given range - the query part (no cache)
	 *
	 * @param  int $product_id
	 * @param  integer $start_date
	 * @param  integer $end_date
	 * @param  int product_and_staff_id
	 * @return array of appointment ids
	 */
	private static function get_appointments_in_date_range_query( $start_date, $end_date, $product_id, $staff_id, $check_in_cart, $filters ) {
		$args = wp_parse_args( $filters, array(
			'status'       => get_wc_appointment_statuses(),
			'object_id'    => 0,
			'product_id'   => 0,
			'staff_id'     => 0,
			'object_type'  => 'product',
			'date_between' => array(
				'start' => $start_date,
				'end'   => $end_date,
			),
		) );

		if ( ! $check_in_cart ) {
			$args['status'] = array_diff( $args['status'], array( 'in-cart' ) );
		}

		if ( $product_id ) {
			$args['product_id']  = $product_id;
		}

		if ( $staff_id ) {
			$args['staff_id']  = $staff_id;
		}

		if ( ! $product_id && $staff_id ) {
			$args['object_type']  = 'staff';
		}

		if ( $product_id && $staff_id ) {
			$args['object_type']  = 'product_and_staff';
		}

		return apply_filters( 'woocommerce_appointments_in_date_range_query', WC_Appointment_Data_Store::get_appointment_ids_by( $args ) );
	}

	/**
	 * Get latest appointments
	 *
	 * @deprecated 2.4.9
	 * @deprecated Use get_appointments()
	 * @see get_appointments()
	 *
     * @param int $numberitems Number of objects returned (default to unlimited)
     * @param int $offset The number of objects to skip (as a query offset)
     * @param bool/array $status array of statuses for which to return appointments (defaults to all)
	 */
    public static function get_latest_appointments( $numberitems = -1, $offset = 0, $status = false ) {
		$query_args = array(
			'posts_per_page' => $numberitems,
            'offset' => $offset,
			'post_status' => $status,
		);
		return WC_Appointments_Controller::get_appointments( $query_args );
    }

	/**
	 * Get appointments
	 *
	 * @param array $arg array of arguments for the get_posts query
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments( $number_of_items = 10, $offset = 0 ) {
		$appointment_ids = get_posts( array(
			'numberposts' => $number_of_items,
			'offset'      => $offset,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'wc_appointment',
			'post_status' => get_wc_appointment_statuses(),
			'fields'      => 'ids',
		) );

		return array_map( 'get_wc_appointment', $appointment_ids );
	}
}
