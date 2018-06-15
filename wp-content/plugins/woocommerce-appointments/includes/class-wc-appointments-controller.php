<?php
/**
 * Gets appointments
 */
class WC_Appointments_Controller {

	/**
	 * Return all appointments for a product and/or staff in a given range
	 * @param timestamp $start_date
	 * @param timestamp $end_date
	 * @param int  $product_or_staff_id
	 * @param bool $check_in_cart
	 *
	 * @return array
	 */
	public static function get_appointments_in_date_range( $start_date, $end_date, $product_id = 0, $staff_id = 0, $check_in_cart = true ) {
		$transient_name = 'schedule_dr_' . md5( http_build_query( array( $start_date, $end_date, $product_id, $staff_id, $check_in_cart, WC_Cache_Helper::get_transient_version( 'appointments' ) ) ) );

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

		if ( false === ( $appointment_ids = get_transient( $transient_name ) ) ) {
			$appointment_ids = self::get_appointments_in_date_range_query( $start_date, $end_date, $product_id, $staff_id, $check_in_cart );
			set_transient( $transient_name, $appointment_ids, DAY_IN_SECONDS * 30 );
		}

		// Get objects
		$appointments = array();

		foreach ( $appointment_ids as $appointment_id ) {
			$appointments[] = get_wc_appointment( $appointment_id );
		}

		return $appointments;
	}

	/**
	 * Return an array of unschedulable padding days
	 * @param  int $product_id
	 * @return array Days that are padding days and therefor should be unappointable
	 */
	public static function find_padding_day_slots( $product_id ) {
		$product = wc_get_product( $product_id );
		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $product_id );
		$fully_scheduled_days = $scheduled['fully_scheduled_days'];
		$padding_days = array();
		$padding_duration = $product->get_padding_duration();

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

		return $padding_days;
	}

	/**
	 * Finds days which are partially scheduled & fully scheduled already
	 * @param  int $product_id
	 * @return array( 'partially_scheduled_days', 'fully_scheduled_days' )
	 */
	public static function find_scheduled_day_slots( $product_id ) {
		$product = wc_get_product( $product_id );

		// Bare existing appointments into consideration for datepicker
		$fully_scheduled_days		= array();
		$partially_scheduled_days	= array();
		$remaining_scheduled_days	= array();
		$product_staff				= array();
		$find_appointments_for		= array( $product->id );
		$staff_count				= 0;
		$current_time 				= current_time( 'timestamp' );

		if ( $product->has_staff() ) {
			foreach ( $product->get_staff() as $staff_member ) {
				$find_appointments_for[]	= $staff_member->ID;
				$product_staff[]			= $staff_member->ID;
				$staff_count++;
			}
		}

		// Today Fully / Partially scheduled?
		$slots_in_range  = $product->get_slots_in_range( strtotime( 'midnight', $current_time ), strtotime( 'midnight +1 day', $current_time ) );
		$available_slots = $product->get_available_slots( $slots_in_range );

		if ( ! $available_slots ) {
			$fully_scheduled_days[ date( 'Y-n-j', $current_time ) ][0] = true;
		}

		if ( count( $available_slots ) < count( $slots_in_range ) ) {
			$partially_scheduled_days[ date( 'Y-n-j' ) ][0] = true;
			$remaining_scheduled_days[ date( 'Y-n-j' ) ][0] = round( ( count( $available_slots ) / count( $slots_in_range ) ) * 10 );
		}

		$existing_appointments = self::get_appointments_for_objects( $find_appointments_for );
		$today    = strtotime( 'today midnight', $current_time );
		$appointments_today = self::get_appointments_on_date( $existing_appointments, $today, $product );
		if ( ! empty( $appointments_today ) && array_sum( $appointments_today ) >= $product->get_qty() ) {
			if ( count( $available_slots ) < count( $slots_in_range ) ) {
				$partially_scheduled_days[ date( 'Y-n-j', $current_time ) ][0] = true;
			}
			if ( ! $available_slots ) {
				$fully_scheduled_days[ date( 'Y-n-j', $current_time ) ][0] = true;
			}
		}

		// Use the existing appointments to find days which are partially or fully scheduled
		if ( $existing_appointments ) {
			foreach ( $existing_appointments as $existing_appointment ) {
				if ( null === $existing_appointment->id ) {
					continue;
				}

				$staff_ids = $existing_appointment->get_staff_id();
				$staff_ids = ! is_array( $staff_ids ) ? array( $staff_ids ) : $staff_ids;

				$start_date	= $existing_appointment->start;
				$end_date	= $existing_appointment->end;
				$check_date	= $start_date; // Take it from the top

				// Existing appointment lasts all day, force end day time.
				if ( $existing_appointment->is_all_day() ) {
					$end_date = strtotime( 'tomorrow midnight  -1 min', $end_date );
				}

				// Loop over all scheduled days in this appointment
				while ( $check_date < $end_date ) {
					$js_date = date( 'Y-n-j', $check_date );

					if ( $check_date < current_time( 'timestamp' ) ) {
						$check_date = strtotime( '+1 day', $check_date );
						continue;
					}

					$midnight = strtotime( 'midnight', $check_date );
					$midnight_tomorrow = strtotime( 'midnight +1 day', $check_date );
					$slots_in_range  = $product->get_slots_in_range( $midnight, $midnight_tomorrow, array(), $staff_ids );
					$available_slots = $product->get_available_slots( $slots_in_range, array(), $staff_ids, '' );
					$appointments_on_check_date = self::get_appointments_on_date( $existing_appointments, $check_date, $product );

					#var_dump( $js_date .' = '. count( $available_slots ) .'__ < __'. count( $slots_in_range ) );

					// Check available qty for daily appointments.
					if ( ! in_array( $product->get_duration_unit(), array( 'minute', 'hour' ) ) && array_sum( $appointments_on_check_date ) >= $product->get_qty() ) {
						$fully_scheduled_days[ $js_date ][0] = true;
					}

					foreach ( $staff_ids as $staff_id ) {

						// Skip if we've already found this staff is unavailable
						if ( ! empty( $fully_scheduled_days[ $js_date ][ $staff_id ] ) ) {
							$check_date = strtotime( "+1 day", $check_date );
							continue;
						}

						/*
						// Test
						if ( $js_date == '2017-1-25' ) {
							var_dump( array_sum( $appointments_on_check_date ) );
							var_dump( $product->get_qty() );

							var_dump( $staff_id );
							var_dump( $js_date .' = '. (count( $available_slots ) .'__'. count( $slots_in_range ) ) );
							var_dump( $available_slots );
							var_dump( $slots_in_range );
						}
						*/

						// Check fo fully scheduled.
						if ( ! $available_slots || ! $slots_in_range ) {
							$fully_scheduled_days[ $js_date ][ $staff_id ] = true;

							// Staff affects product in the next check so product also set.
							if ( 1 === $staff_count || count( $fully_scheduled_days[ $js_date ] ) === $staff_count ) {
								$fully_scheduled_days[ $js_date ][0] = true;
							}

						// Partially scheduled days cases.
						} elseif ( count( $available_slots ) < count( $slots_in_range ) ) {
							$partially_scheduled_days[ $js_date ][ $staff_id ] = true;
							$remaining_scheduled_days[ $js_date ][ $staff_id ] = round( ( count( $available_slots ) / count( $slots_in_range ) ) * 10 );

							// Staff affects product in the next check so product also set.
							if ( 1 === $staff_count || count( $partially_scheduled_days[ $js_date ] ) === $staff_count ) {
								$partially_scheduled_days[ $js_date ][0] = true;
								$remaining_scheduled_days[ $js_date ][0] = round( ( count( $available_slots ) / count( $slots_in_range ) ) * 10 );
							}
						}
					}

					/*
					// Test
					if ( $js_date == '2017-1-26' ) {
						var_dump( $staff_id );
						var_dump( $js_date .' = '. (count( $available_slots ) .'__'. count( $slots_in_range ) ) );
						var_dump( $available_slots );
						var_dump( $slots_in_range );
					}
					*/

					// Show minimum occupancy.
					if ( count( $available_slots ) === count( $slots_in_range ) ) {
						$partially_scheduled_days[ $js_date ][0] = true;
						$remaining_scheduled_days[ $js_date ][0] = 9;
					}

					$check_date = strtotime( "+1 day", $check_date );
				}
			}
		}

		$scheduled_day_slots = array(
			'partially_scheduled_days' => $partially_scheduled_days,
			'remaining_scheduled_days' => $remaining_scheduled_days,
			'fully_scheduled_days'     => $fully_scheduled_days,
		);

		/**
		 * Filter the scheduled day slots calculated per project.
		 * @since 2.3.0
		 *
		 * @param array $scheduled_day_slots {
		 *  @type array $partially_scheduled_days
		 *  @type array $remaining_scheduled_days
		 *  @type array $fully_scheduled_days
		 * }
		 * @param WC_Product $product
		 */
		return apply_filters( 'woocommerce_appointments_scheduled_day_slots', $scheduled_day_slots, $product );
	}

	/**
	 * Return an array of unschedulable padding days
	 * @param  int $product_id
	 * @return Days that are padding days and therefor should be unschedulable
	 */
	public static function find_discounted_day_slots( $product_id ) {
		$product = wc_get_product( $product_id );
		$appointment_form = new WC_Appointment_Form( $product );
		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $product_id );
		$fully_scheduled_days = $scheduled['fully_scheduled_days'];
		$costs = $product->get_costs();
		$discounted_days = array();

		// Duration.
		$product_duration = $product->get_duration();
		$product_duration_unit = $product->get_duration_unit();

		// Base price.
		$base_cost = max( 0, $product->price );
		$base_slot_cost = 0;
		$slot_cost = $base_slot_cost;
		$adjusted_slot_cost = $base_slot_cost;
		$total_slot_cost = array();

		// Get staff cost.
		if ( isset( $data['_staff_id'] ) ) {
			$staff        = $product->get_staff_member( absint( $data['_staff_id'] ) );
			$base_cost   += $staff->get_base_cost();
		}

		$override_slots = array();

		foreach ( $costs as $rule_key => $rule ) {
			$type  = $rule[0];
			$rules = $rule[1];

			switch ( $type ) {
				/*
				// Currently not needed.
				case 'months' :
				case 'weeks' :
				case 'days' :
					$check_date = $slot_start_time['timestamp'];

					while ( $check_date < $slot_end_time['timestamp'] ) {
						$checking_date = $appointment_form->get_formatted_times( $check_date );
						$date_key      = $type == 'days' ? 'day_of_week' : substr( $type, 0, -1 );

						if ( isset( $rules[ $checking_date[ $date_key ] ] ) ) {
							$rule       = $rules[ $checking_date[ $date_key ] ];
							$slot_cost = $appointment_form->apply_cost( $slot_cost, $rule['slot'][0], $rule['slot'][1] );
							$base_cost  = $appointment_form->apply_base_cost( $base_cost, $rule['base'][0], $rule['base'][1], $rule_key );
							if ( $rule['override'] && empty( $override_slots[ $check_date ] ) ) {
								$override_slots[ $check_date ] = $rule['override'];
							}
						}
						$check_date = strtotime( "+1 {$type}", $check_date );
					}
				break;
				*/
				case 'custom' :
					foreach ( (array) $rules as $rule_year => $rule_years ) {
						foreach ( (array) $rule_years as $rule_month => $rule_months ) {
							foreach ( (array) $rule_months as $rule_day => $rule_days ) {
								// print_r( $rule_days );
								$check_date = $rule_year . '-' . $rule_month . '-' . $rule_day;
								$adjusted_base_cost = $appointment_form->apply_cost( $base_cost, $rule_days['base'][0], $rule_days['base'][1] );
								$adjusted_base_cost = $adjusted_base_cost / $product_duration;
								$adjusted_slot_cost = $appointment_form->apply_cost( $base_cost, $rule_days['slot'][0], $rule_days['slot'][1] );
								$adjusted_slot_cost_qty = $base_cost - ( $base_cost - $adjusted_slot_cost );
								$adjusted_combined_cost = $adjusted_base_cost + $adjusted_slot_cost_qty - $base_cost;
								$daily_base_cost = $base_cost / $product_duration;
								if ( $daily_base_cost > $adjusted_combined_cost ) {
									$override_slots[ $check_date ] = $adjusted_combined_cost;
								}
							}
						}
					}
				break;
			}
		}

		#print_r( $override_slots );

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		$wc_price_args = array(
			'ex_tax_label'       => true,
			'decimals'           => 0,
		);

		foreach ( $override_slots as $over_date => $over_cost ) {
			#$total_slot_cost[ $over_date ] = $total_slot_cost - $base_slot_cost;
			$display_price = 'incl' == $tax_display_mode ? $product->get_price_including_tax( 1, $over_cost ) : $product->get_price_excluding_tax( 1, $over_cost );
			if ( version_compare( WC_VERSION, '2.4.0', '>=' ) ) {
				$price_suffix = $product->get_price_suffix( $over_cost, 1 );
			} else {
				$price_suffix = $product->get_price_suffix();
			}
			$total_slot_cost[ $over_date ] = strip_tags( wc_price( $display_price, $wc_price_args ) ) . $price_suffix;
		}

		#print_r( $total_slot_cost );

		// Calculate costs.
		return $total_slot_cost;
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
	public static function get_appointments_on_date( $appointments, $date, $product ) {
		$appointments_on_date = array();
		foreach ( $appointments as $appointment ) {
			// Does the date we want to check fall on one of the days in the appointment?
			if ( $appointment->start <= $date && $appointment->end >= $date ) {
				// Google Calendar sync.
				if ( $appointment->get_product_id() == wc_appointments_gcal_synced_product_id() ) {
					$appointments_on_date[] = $product->get_qty();
				} else {
					$appointments_on_date[] = $appointment->qty;
				}
			}
		}

		return $appointments_on_date;
	}

	/**
	 * Return all appointments for a product in a given range - the query part (no cache)
	 * @param  int $product_id
	 * @param  timestamp $start_date
	 * @param  timestamp $end_date
	 * @param  int product_or_staff_id
	 * @return array of appointment ids
	 */
	private static function get_appointments_in_date_range_query( $start_date, $end_date, $product_id = '', $staff_id = '', $check_in_cart = true ) {
		global $wpdb;

		$product_meta_key_join = '';
		$staff_meta_key_join = '';
		$product_meta_key_q    = '';
		$staff_meta_key_q 	   = '';
		$product_and_staff_meta_key_q = '';

		if ( $product_id ) {
			$product_meta_key_join = " LEFT JOIN {$wpdb->postmeta} as productidmeta ON {$wpdb->posts}.ID = productidmeta.post_id ";
			// Product IDs as Array.
			if ( is_array( $product_id ) ) {
				$product_meta_key_q    = " productidmeta.meta_key = '_appointment_product_id' AND productidmeta.meta_value IN ('" . implode( "','", array_map( 'absint', $product_id ) ) . "') ";
			// Single Product ID.
			} else {
				$product_meta_key_q    = ' productidmeta.meta_key = "_appointment_product_id" AND productidmeta.meta_value = "' . absint( $product_id ) . '" ';
			}
		}

		if ( $staff_id ) {
			$staff_meta_key_join = " LEFT JOIN {$wpdb->postmeta} as staffidmeta ON {$wpdb->posts}.ID = staffidmeta.post_id ";
			// Staff IDs as Array.
			if ( is_array( $staff_id ) ) {
				$staff_meta_key_q    = " staffidmeta.meta_key = '_appointment_staff_id' AND staffidmeta.meta_value IN ('" . implode( "','", array_map( 'absint', $staff_id ) ) . "') ";
			// Single Staff ID.
			} else {
				$staff_meta_key_q    = ' staffidmeta.meta_key = "_appointment_staff_id" AND staffidmeta.meta_value = "' . absint( $staff_id ) . '" ';
			}
		}

		if ( $product_id && $staff_id ) {
			$product_and_staff_meta_key_q = ' AND ( (' . $product_meta_key_q . ') OR (' . $staff_meta_key_q . ' ) ) ';
		} elseif ( $staff_id ) {
			$product_and_staff_meta_key_q = ' AND ' . $staff_meta_key_q;
			$product_meta_key_join = '';
			$product_meta_key_q = '';
		} elseif ( $product_id ) {
			$product_and_staff_meta_key_q = ' AND ' . $product_meta_key_q;
			$staff_meta_key_join = '';
			$staff_meta_key_q = '';
		}

		$appointment_statuses = get_wc_appointment_statuses();

		if ( ! $check_in_cart ) {
			$appointment_statuses = array_diff( $appointment_statuses, array( 'in-cart' ) );
		}

		$dates_meta_q = '';

		if ( $product_id && ! is_array( $product_id ) ) {
			$product = get_product( $product_id );
			if ( $product->has_padding() ) {
				$dates_meta_q = '(
					startmeta.meta_value <= %s
					AND endmeta.meta_value >= %s
					AND daymeta.meta_value = "0"
				)';
			} else {
				$dates_meta_q = '(
					startmeta.meta_value < %s
					AND endmeta.meta_value > %s
					AND daymeta.meta_value = "0"
				)';
			}
		} else {
			$dates_meta_q = '(
				startmeta.meta_value < %s
				AND endmeta.meta_value > %s
				AND daymeta.meta_value = "0"
			)';
		}
		$query = $wpdb->prepare( "
			SELECT DISTINCT ID FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->postmeta} as startmeta ON {$wpdb->posts}.ID = startmeta.post_id
			LEFT JOIN {$wpdb->postmeta} as endmeta ON {$wpdb->posts}.ID = endmeta.post_id
			LEFT JOIN {$wpdb->postmeta} as daymeta ON {$wpdb->posts}.ID = daymeta.post_id
			" . $product_meta_key_join . "
			" . $staff_meta_key_join . "
			WHERE post_type = 'wc_appointment'
			AND post_status IN ( '" . implode( "','", array_map( 'esc_sql', $appointment_statuses ) ) . "' )
			AND startmeta.meta_key = '_appointment_start'
			AND endmeta.meta_key   = '_appointment_end'
			AND daymeta.meta_key   = '_appointment_all_day'
			" . $product_and_staff_meta_key_q . "
			AND (
				" . $dates_meta_q . "
				OR
				(
					startmeta.meta_value <= %s
					AND endmeta.meta_value >= %s
					AND daymeta.meta_value = '1'
				)
			)
		", date( 'YmdHis', $end_date ), date( 'YmdHis', $start_date ), date( 'Ymd000000', $end_date ), date( 'Ymd000000', $start_date ) );

		$appointment_ids = $wpdb->get_col( $query );

		return apply_filters( 'woocommerce_appointments_in_date_range_query', $appointment_ids );
	}

	/**
	 * Gets appointments for product ids and staff ids
	 * @param  array  $ids
	 * @param  array  $status
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_objects( $ids = array() ) {
		$transient_name = 'schedule_fo_' . md5( http_build_query( array( $ids, WC_Cache_Helper::get_transient_version( 'appointments' ) ) ) );
		$appointments = array();

		// Synced appointments from GCal.
		if ( wc_appointments_gcal_synced_product_id() ) {
			$ids[] = wc_appointments_gcal_synced_product_id();
		}

		if ( false === ( $appointment_ids = get_transient( $transient_name ) ) ) {
			$appointment_ids = self::get_appointments_for_objects_query( $ids );
			set_transient( $transient_name, $appointment_ids, DAY_IN_SECONDS * 30 );
		}

		if ( empty( $appointment_ids ) ) {
			return $appointments;
		}

		// Get objects.
		foreach ( $appointment_ids as $appointment_id ) {
			$appointments[] = get_wc_appointment( $appointment_id );
		}

		return $appointments;
	}

	/**
	 * Gets appointments for product ids and staff ids
	 * @param  array  $ids
	 * @param  array  $date
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_objects_query( $ids, $check_in_cart = true ) {
		global $wpdb;

		$appointment_statuses = get_wc_appointment_statuses();

		if ( class_exists( 'WC_Deposits' ) ) {
			$appointment_statuses[] = 'wc-partial-payment';
		}

		$appointment_ids = $wpdb->get_col( "
			SELECT ID FROM {$wpdb->posts}
			LEFT JOIN {$wpdb->postmeta} as idmeta ON {$wpdb->posts}.ID = idmeta.post_id
			WHERE post_type = 'wc_appointment'
			AND post_status IN ( '" . implode( "','", array_map( 'esc_sql', $appointment_statuses ) ) . "' )
			AND (
				( idmeta.meta_key = '_appointment_product_id' AND idmeta.meta_value IN ('" . implode( "','", array_map( 'absint', $ids ) ) . "') )
				OR
				( idmeta.meta_key = '_appointment_staff_id' AND idmeta.meta_value IN ('" . implode( "','", array_map( 'absint', $ids ) ) . "') )
			)
		" );

		return $appointment_ids;
	}

	/**
	 * Gets appointments for a staff
	 *
	 * @deprecated 2.4.9
	 * @deprecated Use get_appointments()
	 * @see get_appointments()
	 *
	 * @param  int $staff_id ID
	 * @param  array  $status
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_staff( $staff_id, $status = array( 'confirmed', 'paid' ) ) {
		_deprecated_function( __FUNCTION__, '2.4.9', 'get_appointments_for_staff()' );
		$query_args = array(
			'post_status'   => $status,
			'meta_query' 	=> array(
				array(
					'key'     => '_appointment_staff_id',
					'value'   => absint( $staff_id ),
				),
			),
		);
		return WC_Appointments_Controller::get_appointments( $query_args );
	}

	/**
	 * Gets appointments for a product by ID
	 *
	 * @deprecated 2.4.9
	 * @deprecated Use get_appointments()
	 * @see get_appointments()
	 *
	 * @param int $product_id The id of the product that we want appointments for
	 * @return array of WC_Appointment objects
	 */
	public static function get_appointments_for_product( $product_id, $status = array( 'confirmed', 'paid' ) ) {
		_deprecated_function( __FUNCTION__, '2.4.9', 'get_appointments_for_product()' );
		$query_args = array(
			'post_status'   => $status,
			'meta_query' 	=> array(
				array(
					'key'     => '_appointment_product_id',
					'value'   => absint( $product_id ),
				),
			),
		);
		return WC_Appointments_Controller::get_appointments( $query_args );
	}

	/**
	 * Gets appointments for a user by ID
	 *
	 * @deprecated 2.4.9
	 * @deprecated Use get_appointments()
	 * @see get_appointments()
	 *
	 * @param  int   $user_id    The id of the user that we want appointments for
	 * @param  array $query_args The query arguments used to get appointment IDs
	 * @return array             Array of WC_Appointment objects
	 */
	function get_appointments_for_user( $user_id, $query_args = null ) {
		_deprecated_function( __FUNCTION__, '2.4.9', 'get_appointments_for_user()' );
		$appointments_query_args = wp_parse_args(
			$query_args,
			array(
				'post_status'   => get_wc_appointment_statuses( 'user' ),
				'meta_query' 	=> array(
					array(
						'key'     => '_appointment_customer_id',
						'value'   => absint( $user_id ),
					),
				),
			)
		);
		return WC_Appointments_Controller::get_appointments( $appointments_query_args );
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
		_deprecated_function( __FUNCTION__, '2.4.9', 'get_appointments_for_customer()' );
		$appointments_query_args = wp_parse_args(
			$query_args,
			array(
				'post_status'   => get_wc_appointment_statuses( 'user' ),
				'meta_query' 	=> array(
					array(
						'key'     => '_appointment_customer_id',
						'value'   => absint( $customer_id ),
					),
				),
			)
		);
		return WC_Appointments_Controller::get_appointments( $appointments_query_args );
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
    	_deprecated_function( __FUNCTION__, '2.4.9', 'get_latest_appointments()' );
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
	public static function get_appointments( $args = array() ) {
		$default_args = array(
			'posts_per_page' => -1,
			'offset'      => 0,
			'orderby'     => 'post_date',
			'order'       => 'DESC',
			'post_type'   => 'wc_appointment',
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'post_status' => get_wc_appointment_statuses(),
			'fields'      => 'ids',
		);

		$get_appointments_args = wp_parse_args( $args, $default_args );

		$posts_query = new WP_Query();
		$appointment_ids = $posts_query->query( $get_appointments_args );

		$appointments = array();

		foreach ( $appointment_ids as $appointment_id ) {
			$appointments[] = get_wc_appointment( $appointment_id );
		}

		return $appointments;
	}

	/**
	 * Validates appointment against global, product and staff rules (if not flags for each state otherwise), outputs notices on $output_notice = true flag
	 *
	 * @param  int|WC_Appointment $appointment               The instance of the WC_Appointment or id of the appointment we want to validate
	 * @param  bool               $validate_global_rules     The flag which controls validation of global rules
	 * @param  bool               $validate_product_rules    The flag which controls validation of product rules
	 * @param  bool               $validate_staff_rules      The flag which controls validation of staff rules
	 * @param  bool               $output_notice             The flag which controls output of notices
	 * @return bool                                          Is appointment valid or not against rules
	 */
	public static function validate_appointment( $appointment, $validate_global_rules = true, $validate_product_rules = true, $validate_staff_rules = true, $output_notice = true ) {
		if ( is_numeric( $appointment ) ) { // id is passed
			$appointment_id = $appointment;
			$appointment_object = get_wc_appointment( $appointment );
		} else {
			$appointment_id = $appointment->id;
			$appointment_object = $appointment;
		}

		$valid = true;

		// Cleanup any previous notices.
		WC_Admin_Notices::remove_notice( 'validate_appointments_notice' );

		if ( $validate_global_rules ) {
			// Is appointment valid against global rules?
			if ( ! self::is_appointment_valid_against_global_rules( $appointment_object ) ) {
				if ( $output_notice ) {
					$message = sprintf( 'Appointment ID: %s mismatches global availability rules ', $appointment_id );
					wc_write_appointment_log( 'appointments-validation', $message );
				}
				$valid = false;
			}
		}

		if ( $validate_product_rules ) {
			// Is appointment valid against product rules (if it has product assigned)?
			if ( ! empty( $appointment_object->product_id ) ) {
				if ( ! self::is_appointment_valid_against_product_rules( $appointment_object, $appointment_object->product_id ) ) {
					if ( $output_notice ) {
						$message = sprintf( 'Appointment ID: %s mismatches product #%s (%s) availability rules ', $appointment_id, $appointment_object->product_id, get_the_title( $appointment_object->product_id ) );
						wc_write_appointment_log( 'appointments-validation', $message );
					}
					$valid = false;
				}
			}
		}

		if ( $validate_staff_rules ) {
			// Is appointment valid against staff rules (if it has any assigned)?
			if ( ! empty( $appointment_object->staff_id ) ) {
				$valid = true;
				foreach ( $appointment_object->staff_id as $appointment_staff_id ) {
					if ( ! self::is_appointment_valid_against_staff_rules( $appointment_object, $appointment_staff_id ) ) {
						if ( $output_notice ) {
							$staff_user = get_user_by( 'id', $appointment_staff_id );
							$message = sprintf( 'Appointment ID: %s mismatches staff #%s (%s) availability rules ', $appointment_id, $appointment_staff_id, $staff_user->first_name . ' ' . $staff_user->last_name );
							wc_write_appointment_log( 'appointments-validation', $message );
						}
						$valid = false;
					}
				}
			}
		}

		if ( ! $valid && $output_notice ) {
			WC_Admin_Notices::add_custom_notice( 'validate_appointments_notice', sprintf( __( 'Some appointments mismatch against availability rules. Check <a href="%s">"appointments-validation" Log</a> for details.', 'woocommerce-appointments' ), admin_url( 'admin.php?page=wc-status&tab=logs' ) ) );
		}

		return $valid;
	}

	/**
	 * Validates if global rules apply to the appointment data or not (start date, end date)
	 *
	 * @param  object  $appointment   Instance of WC_Appointment object we want to validate
	 * @return bool                   Is appointment valid or not against global rules
	 */
	public static function is_appointment_valid_against_global_rules( $appointment ) {
		$formatted_global_rules = WC_Product_Appointment_Rule_Manager::get_global_availability_rules();
		$is_start_datetime_valid = self::check_rules_against_datetime( $formatted_global_rules, strtotime( $appointment->custom_fields['_appointment_start'][0] ) );
		$is_end_datetime_valid = self::check_rules_against_datetime( $formatted_global_rules, strtotime( $appointment->custom_fields['_appointment_end'][0] ) );

		if ( $is_start_datetime_valid && $is_end_datetime_valid ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates if product rules apply to the appointment data or not (start date, end date)
	 *
	 * @param  object  $appointment   Instance of WC_Appointment object we want to validate
	 * @param  int     $product_id    Id of the product for which availability rules we want to check
	 * @return bool                   Is appointment valid or not against product rules
	 */
	public static function is_appointment_valid_against_product_rules( $appointment, $product_id ) {
		$formatted_product_rules = WC_Product_Appointment_Rule_Manager::get_product_availability_rules( $product_id );
		$is_start_datetime_valid = self::check_rules_against_datetime( $formatted_product_rules, strtotime( $appointment->custom_fields['_appointment_start'][0] ) );
		$is_end_datetime_valid = self::check_rules_against_datetime( $formatted_product_rules, strtotime( $appointment->custom_fields['_appointment_end'][0] ) );

		if ( $is_start_datetime_valid && $is_end_datetime_valid ) {
			return true;
		}

		return false;
	}

	/**
	 * Validates if staff rules apply to the appointment data or not (start date, end date)
	 *
	 * @param  object  $appointment   Instance of WC_Appointment object we want to validate
	 * @param  int     $product_id    Id of the staff for which availability rules we want to check
	 * @return bool                   Is appointment valid or not against staff rules
	 */
	public static function is_appointment_valid_against_staff_rules( $appointment, $staff_id ) {
		$formatted_staff_rules = WC_Product_Appointment_Rule_Manager::get_staff_availability_rules( $staff_id );
		$is_start_datetime_valid = self::check_rules_against_datetime( $formatted_staff_rules, strtotime( $appointment->custom_fields['_appointment_start'][0] ) );
		$is_end_datetime_valid = self::check_rules_against_datetime( $formatted_staff_rules, strtotime( $appointment->custom_fields['_appointment_end'][0] ) );

		if ( $is_start_datetime_valid && $is_end_datetime_valid ) {
			return true;
		}

		return false;
	}

	/**
	 * Checks rules array against passed datetime, returns true/false
	 *
	 * @param  array     $rules      Array of rules which we want to check
	 * @param  datetime  $datetime   Datetime we want to check against rules
	 * @return bool                  Is datetime valid for passed rules array
	 */
	public static function check_rules_against_datetime( $rules, $datetime ) {
		$year        = date( 'Y', $datetime );
		$month       = absint( date( 'm', $datetime ) );
		$day         = absint( date( 'j', $datetime ) );
		$day_of_week = absint( date( 'N', $datetime ) );
		$week        = absint( date( 'W', $datetime ) );
		$time        = date( 'H:i', $datetime );

		$valid = false;
		// Check if any rules are set, otherwise do not validate and set that it is valid.
		if ( ! empty( $rules ) ) {
			// Loop and search through the formatted rules and set valid if match ANY rules.
			foreach ( $rules as $rule ) {
				$type  = $rule['type'];
				$range = $rule['range'];

				switch ( $type ) {
					case 'months' : #Check "Range of months" rule
					// If month is defined in the range of the month rule and appointable is set to true then it is valid.
					if ( isset( $range[ $month ] ) && $range[ $month ] ) {
						$valid = true;
					}
					break;
					case 'weeks': #Check "Range of weeks" rule
					// If week is defined in the range of the week rule and appointable is set to true then it is valid.
					if ( isset( $range[ $week ] ) && $range[ $week ] ) {
						$valid = true;
					}
					break;
					case 'days' : #Check "Range of days" rule
					// If day is defined in the range of the day rule and appointable is set to true then it is valid.
					if ( isset( $range[ $day ] ) && $range[ $day ] ) {
						$valid = true;
					}
					break;
					case 'custom' : #Check "Date range" rule
					// If date is defined in the range of the date rule and appointable is set to true then it is valid.
					if ( isset( $range[ $year ][ $month ][ $day ] ) && $range[ $year ][ $month ][ $day ] ) {
						$valid = true;
					}
					break;
					case 'time': #Check "Time range (all week)" rule
						$time_from = date( 'H:i', strtotime( $range['from'] ) );
						$time_to = date( 'H:i', strtotime( $range['to'] ) );

						// If time is between the range of the time rule and appointable is set to true then it is valid.
						if ( $time >= $time_from && $time <= $time_to && $range['rule'] ) {
							$valid = true;
						}
					break;
					case 'time:1': #Check "Time range (Monday)" rule
					case 'time:2': #Check "Time range (Tuesday)" rule
					case 'time:3': #Check "Time range (Wednesday)" rule
					case 'time:4': #Check "Time range (Thursday)" rule
					case 'time:5': #Check "Time range (Friday)" rule
					case 'time:6': #Check "Time range (Saturday)" rule
					case 'time:7': #Check "Time range (Sunday)" rule
						$time_from = date( 'H:i', strtotime( $range['from'] ) );
						$time_to = date( 'H:i', strtotime( $range['to'] ) );

						// If time is between the range of the time rule and day is the same as the range of the day rule and appointable is set to true then it is valid.
						if ( $time >= $time_from && $time <= $time_to && $range['day'] === $day_of_week && $range['rule'] ) {
							$valid = true;
						}
					break;
					case 'time:range': #Check "Time range (date range)" rule
						// If date is defined in the range of the date rule continue with check for valid.
						if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
							$time_from = date( 'H:i', strtotime( $range[ $year ][ $month ][ $day ]['from'] ) );
							$time_to = date( 'H:i', strtotime( $range[ $year ][ $month ][ $day ]['to'] ) );

							// If time is between the range of the time rule and day is the same as the range of the day rule and appointable is set to true then it is valid.
							if ( $time >= $time_from && $time <= $time_to && $range[ $year ][ $month ][ $day ]['day'] === $day_of_week && $range[ $year ][ $month ][ $day ]['rule'] ) {
								$valid = true;
							}
						}
					break;
				}
			}
		} else { #No rules, set as valid.
			$valid = true;
		}

		return $valid;
	}
}
