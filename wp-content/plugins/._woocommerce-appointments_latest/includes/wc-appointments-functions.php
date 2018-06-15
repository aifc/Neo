<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get an appointment object
 * @param  int $id
 * @return WC_Appointment|false
 */
function get_wc_appointment( $id ) {
	try {
		return new WC_Appointment( $id );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Santiize and format a string into a valid 24 hour time
 * @return string
 */
function wc_appointment_sanitize_time( $raw_time ) {
	$time = wc_clean( $raw_time );
	$time = date( 'H:i', strtotime( $time ) );

	return $time;
}

/**
 * Get timezone offset in seconds.
 *
 * @since  3.1.8
 * @return float
 */
function wc_appointment_timezone_offset() {
	$timezone = get_option( 'timezone_string' );
	if ( $timezone ) {
		$timezone_object = new DateTimeZone( $timezone );
		return $timezone_object->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}

/**
 * Returns true if the product is an appointment product, false if not
 * @return bool
 */
 function is_wc_appointment_product( $product ) {
 	$appointment_product_types = apply_filters( 'woocommerce_appointments_product_types', array( 'appointment' ) );
 	return isset( $product ) && $product->is_type( $appointment_product_types );
 }

/**
 * Convert key to a nice readable label
 * @param  string $key
 * @return string
 */
function get_wc_appointment_data_label( $key, $product ) {
	$labels = apply_filters( 'woocommerce_appointments_data_labels', array(
		'staff'	   => ( $product->get_staff_label() ? $product->get_staff_label() : __( 'Providers', 'woocommerce-appointments' ) ),
		'date'     => __( 'Date', 'woocommerce-appointments' ),
		'time'     => __( 'Time', 'woocommerce-appointments' ),
		'duration' => __( 'Duration', 'woocommerce-appointments' ),
	) );

	if ( ! array_key_exists( $key, $labels ) ) {
		return $key;
	}

	return $labels[ $key ];
}

/**
 * Convert status to human readable label.
 *
 * @since  3.0.0
 * @param  string $status
 * @return string
 */
function wc_appointments_get_status_label( $status ) {
	$statuses = array(
		'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
		'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
		'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
		'paid'                 => __( 'Paid','woocommerce-appointments' ),
		'cancelled'            => __( 'Cancelled','woocommerce-appointments' ),
		'complete'             => __( 'Complete','woocommerce-appointments' ),
		'in-cart'              => __( 'In Cart','woocommerce-appointments' ),
	);

	/**
	 * Filter the return value of wc_appointments_get_status_label.
	 *
	 * @since 3.5.6
	 */
	$statuses = apply_filters( 'woocommerce_appointments_get_status_label', $statuses );

	return array_key_exists( $status, $statuses ) ? $statuses[ $status ] : $status;
}

/**
 * Returns a list of appointment statuses.
 *
 * @since 2.3.0 Add new parameter that allows globalised status strings as part of the array.
 * @param  string $context An optional context (filters) for user or cancel statuses
 * @param boolean $include_translation_strings. Defaults to false. This introduces status translations text string. In future (2.0) should default to true.
 * @return array $statuses
 */
function get_wc_appointment_statuses( $context = 'fully_scheduled', $include_translation_strings = false ) {
	if ( 'user' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_user', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid','woocommerce-appointments' ),
			'cancelled'            => __( 'Cancelled','woocommerce-appointments' ),
			'complete'             => __( 'Complete','woocommerce-appointments' ),
		) );
	} elseif ( 'validate' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_validation', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid','woocommerce-appointments' ),
		) );
	} elseif ( 'customer' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_customer', array(
			'expected'              => __( 'Expected','woocommerce-appointments' ),
			'arrived' 				=> __( 'Arrived','woocommerce-appointments' ),
			'no-show'            	=> __( 'No-show','woocommerce-appointments' ),
		) );
	} elseif ( 'cancel' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_cancel', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid','woocommerce-appointments' ),
		) );
	} elseif ( 'scheduled' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_scheduled', array(
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid','woocommerce-appointments' ),
		) );
	} else {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_fully_scheduled', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid','woocommerce-appointments' ),
			'complete'             => __( 'Complete','woocommerce-appointments' ),
			'in-cart'              => __( 'In Cart','woocommerce-appointments' ),
		) );
	}

	/**
 	 * Filter the return value of get_wc_appointment_statuses.
 	 *
 	 * @since 3.5.6
 	 */
	$statuses = apply_filters( 'woocommerce_appointments_get_wc_appointment_statuses', $statuses );

	// backwards compatibility
	return $include_translation_strings ? $statuses : array_keys( $statuses );
}

/**
 * Validate and create a new appointment manually.
 *
 * @version  1.10.7
 * @see      WC_Appointment::new_appointment() for available $new_appointment_data args
 * @param    int    $product_id you are appointment
 * @param    array  $new_appointment_data
 * @param    string $status
 * @param    bool   $exact If false, the function will look for the next available slot after your start date if the date is unavailable.
 * @return   mixed  WC_Appointment object on success or false on fail
 */
function create_wc_appointment( $product_id, $new_appointment_data = array(), $status = 'confirmed', $exact = false ) {
	// Merge appointment data
	$defaults = array(
		'product_id'  => $product_id, // Appointment ID
		'start_date'  => '',
		'end_date'    => '',
		'staff_id'    => '',
		'staff_ids'   => '',
	);

	$new_appointment_data = wp_parse_args( $new_appointment_data, $defaults );
	$product          = wc_get_product( $product_id );
	$start_date       = $new_appointment_data['start_date'];
	$end_date         = $new_appointment_data['end_date'];
	$max_date         = $product->get_max_date_a();
	$all_day          = isset( $new_appointment_data['all_day'] ) && $new_appointment_data['all_day'] ? true : false;
	$qty = 1;

	// If not set, use next available
	if ( ! $start_date ) {
		$min_date   = $product->get_min_date_a();
		$start_date = strtotime( "+{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
	}

	// If not set, use next available + slot duration
	if ( ! $end_date ) {
		$end_date = strtotime( '+' . $product->get_duration() . ' ' . $product->get_duration_unit(), $start_date );
	}

	$searching = true;
	$date_diff = $all_day ? DAY_IN_SECONDS : $end_date - $start_date;

	while ( $searching ) {

		$available_appointments = wc_appointments_get_total_available_appointments_for_range(
			$product,
			$start_date, #start_date
			$end_date, #end_date
			$new_appointment_data['staff_id'],
			$qty
		);

		if ( $available_appointments && ! is_wp_error( $available_appointments ) ) {

			if ( ! $new_appointment_data['staff_id'] && is_array( $available_appointments ) ) {
				$new_appointment_data['staff_id'] = current( array_keys( $available_appointments ) );
			}

			$searching = false;

		} else {
			if ( $exact ) {
				return false;
			}

			$start_date += $date_diff;
			$end_date   += $date_diff;

			if ( $end_date > strtotime( "+{$max_date['value']} {$max_date['unit']}" ) ) {
				return false;
			}
		}
	}

	// Set dates
	$new_appointment_data['start_date'] = $start_date;
	$new_appointment_data['end_date']   = $end_date;

	// Create it
	$new_appointment = get_wc_appointment( $new_appointment_data );
	$new_appointment->create( $status );

	return $new_appointment;
}

/**
 * Check if product/appointment requires confirmation.
 *
 * @param  int $id Product ID.
 *
 * @return bool
 */
function wc_appointment_requires_confirmation( $id ) {
	$product = wc_get_product( $id );

	if (
		is_object( $product )
		&& is_wc_appointment_product( $product )
		&& $product->requires_confirmation()
	) {
		return true;
	}

	return false;
}

/**
 * Check if the cart has appointment that requires confirmation.
 *
 * @return bool
 */
function wc_appointment_cart_requires_confirmation() {
	$requires = false;

	if ( ! empty( WC()->cart->cart_contents ) ) {
		foreach ( WC()->cart->cart_contents as $item ) {
			if ( wc_appointment_requires_confirmation( $item['product_id'] ) ) {
				$requires = true;
				break;
			}
		}
	}

	return $requires;
}

/**
 * Check if the order has appointment that requires confirmation.
 *
 * @param  WC_Order $order
 *
 * @return bool
 */
function wc_appointment_order_requires_confirmation( $order ) {
	$requires = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( wc_appointment_requires_confirmation( $item['product_id'] ) ) {
				$requires = true;
				break;
			}
		}
	}

	return $requires;
}

/**
 * Get timezone string.
 *
 * inspired by https://wordpress.org/plugins/event-organiser/
 *
 * @return string
 */
function wc_appointment_get_timezone_string() {
	$timezone = wp_cache_get( 'wc_appointments_timezone_string' );

	if ( false === $timezone ) {
		$timezone   = get_option( 'timezone_string' );
		$gmt_offset = get_option( 'gmt_offset' );

		// Remove old Etc mappings. Fallback to gmt_offset.
		if ( ! empty( $timezone ) && false !== strpos( $timezone, 'Etc/GMT' ) ) {
			$timezone = '';
		}

		if ( empty( $timezone ) && 0 != $gmt_offset ) {
			// Use gmt_offset
			$gmt_offset   *= 3600; // convert hour offset to seconds
			$allowed_zones = timezone_abbreviations_list();

			foreach ( $allowed_zones as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( $city['offset'] == $gmt_offset ) {
						$timezone = $city['timezone_id'];
						break 2;
					}
				}
			}
		}

		// Issue with the timezone selected, set to 'UTC'
		if ( empty( $timezone ) ) {
			$timezone = 'UTC';
		}

		// Cache the timezone string.
		wp_cache_set( 'wc_appointments_timezone_string', $timezone );
	}

	return $timezone;
}

/**
 * Convert time in minutes to hours and minutes
 *
 * @return string
 */
function wc_appointment_pretty_addon_duration( $time ) {
	global $product;

	if ( ( is_cart() || is_checkout() ) && isset( $cart_item ) && null !== $cart_item ) {
		// Support new WooCommerce 3.0 WC_Product->get_id().
		if ( method_exists( $cart_item, 'get_id' ) ) {
			$product = wc_get_product( $cart_item->get_id() );
		} else {
			$product = wc_get_product( $cart_item->id );
		}
	}

	if ( is_object( $product ) ) {
		$duration_unit = $product->get_duration_unit() ? $product->get_duration_unit() : 'minute';
	} else {
		$duration_unit = 'minute';
	}

	if ( 'day' === $duration_unit ) {
		$return = sprintf( _n( '%s day', '%s days', $time, 'woocommerce-appointments' ), $time );
	} else {
		$return = wc_appointment_pretty_timestamp( $time );
	}

	return apply_filters( 'wc_appointment_pretty_addon_duration', $return, $time, $product );
}

/**
 * Convert duration in minutes to pretty time display
 *
 * @return string
 */
function wc_appointment_pretty_timestamp( $time ) {
	$minsPerDay = apply_filters( 'woocommerce_appointments_day_duration_break', 60*24 ); #1 day
	$minsPerHour = apply_filters( 'woocommerce_appointments_hour_duration_break', 60*2 ); #2 hours

	// Days.
	if ( $time >= $minsPerDay ) {
		$days = floor( $time / 1440 );
		$return = sprintf( _n( '%s day', '%s days', $days, 'woocommerce-appointments' ), $days );
		$hours = ( $time % 24 );
		if ( $hours > 0 ) {
			$return .= '&nbsp;'; #empty space
			$return .= sprintf( _n( '%s hour', '%s hours', $hours, 'woocommerce-appointments' ), $hours );
		}
		$minutes = ( $time % 60 );
		if ( $minutes > 0 ) {
			$return .= '&nbsp;'; #empty space
			$return .= sprintf( _n( '%s minute', '%s minutes', $minutes, 'woocommerce-appointments' ), $minutes );
		}
	// Hours.
	} elseif ( $time >= $minsPerHour ) {
		$hours = floor( $time / 60 );
		$return = sprintf( _n( '%s hour', '%s hours', $hours, 'woocommerce-appointments' ), $hours );
		$minutes = ( $time % 60 );
		if ( $minutes > 0 ) {
			$return .= '&nbsp;'; #empty space
			$return .= sprintf( _n( '%s minute', '%s minutes', $minutes, 'woocommerce-appointments' ), $minutes );
		}
	// Minutes.
	} else {
		$return = sprintf( _n( '%s minute', '%s minutes', $time, 'woocommerce-appointments' ), $time );
	}

	return apply_filters( 'wc_appointment_pretty_timestamp', $return, $time );
}

/**
 * Get the offset in seconds between a timezone and UTC
 *
 * @param string $timezone
 *
 * @return int
 */
function wc_appointment_get_timezone_offset( $timezone ) {
	$utc_tz   = new DateTimeZone( 'UTC' );
	$other_tz = new DateTimeZone( $timezone );

	$utc_date   = new DateTime( 'now', $utc_tz );
	$other_date = new DateTime( 'now', $other_tz );

	$offset = $other_tz->getOffset( $other_date ) - $utc_tz->getOffset( $utc_date );

	return (int) $offset;
}

/**
 * Convert Unix timestamps to/from various locales
 *
 * @param string $from
 * @param string $to
 * @param int    $time
 * @param string $format (optional)
 *
 * @return string
 */
function wc_appointment_timezone_locale( $from = '', $to = '', $time = '', $format = 'U', $user_timezone = '' ) {
	// Validate Unix timestamp
	if ( ! is_numeric( $time ) || $time > PHP_INT_MAX || $time < ~PHP_INT_MAX ) {
		return;
	}

	// Calc "from" offset
	$from = ( 'site' === $from ) ? wc_timezone_string() : ( ( 'user' === $from ) ? $user_timezone : 'GMT' );
	$from = wc_appointment_get_timezone_offset( $from );

	// Calc "to" offset
	$to = ( 'site' === $to ) ? wc_timezone_string() : ( ( 'user' === $to ) ? $user_timezone : 'GMT' );
	$to = wc_appointment_get_timezone_offset( $to );

	// Calc GMT time using "from" offset
	$gmt = $time - $from;

	// Calc final date string using "to" offset
	$date = date( $format, $gmt + $to );

	return (string) $date;
}

/**
 * @sine 1.9.13
 * @return string
 */
function get_wc_appointment_rules_explanation() {
	return __( 'Rules further down the table will override those at the top.', 'woocommerce-appointments' );
}

function get_wc_appointment_priority_explanation() {
	return __( 'Rules with lower priority numbers will override rules with a higher priority (e.g. 9 overrides 10 ). Global rules take priority over product rules which take priority over staff rules. By using priority numbers you can execute rules in different orders.', 'woocommerce-appointments' );
}

/**
 * Write to woocommerce log files
 * @return void
 */
function wc_write_appointment_log( $log_id, $message ) {
	if ( class_exists( 'WC_Logger' ) ) {
		$log = new WC_Logger();
		$log->add( $log_id, $message );
	}
}

/**
 * Get appointments count for staff, can be filtered by appointments post_status
 *
 * @param int $staff_id
 * @param string $post_status (optional)
 *
 * @return int
 */
function wc_appointments_get_count_per_staff( $staff_id, $post_status = '' ) {
	global $wpdb;

	$count = $wpdb->get_var( $wpdb->prepare( "
		SELECT COUNT(ID)
			FROM $wpdb->posts AS posts
				LEFT JOIN $wpdb->postmeta AS postmeta
				ON posts.ID = postmeta.post_id
			WHERE postmeta.meta_key = '_appointment_staff_id'
				AND postmeta.meta_value = %s " . ( '' != $post_status ? " AND posts.post_status = %s " : ""),
		$staff_id, $post_status )
	);

	return $count;
}

/**
 * Get staff from provided IDs.
 *
 * @param int $staff_ids
 * @param string $post_status (optional)
 *
 * @return int
 */
function wc_appointments_get_staff_from_ids( $ids = array(), $names = false, $with_link = false ) {
	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}

	$staff_members = array();

	if ( ! empty( $ids ) ) {
		foreach ( $ids as $id ) {
			$staff_member = new WC_Product_Appointment_Staff( $id );

			if ( $with_link ) {
				$staff_members[] = '<a href="' . get_edit_user_link( $staff_member->get_id() ) . '">' . $staff_member->get_display_name() . '</a>';
			} elseif ( $names ) {
				$staff_members[] = $staff_member->get_display_name();
			} else {
				$staff_members[] = $staff_member;
			}
		}
	}

	if ( $names && ! empty( $staff_members ) ) {
		$staff_members = implode( ', ', $staff_members );
	}

	return $staff_members;
}

/**
 * Get the min timestamp that is appointable based on settings.
 *
 * If $today is the current day, offset starts from NOW rather than midnight.
 *
 * @param int $today Current timestamp, defaults to now.
 * @param int $offset
 * @param string $unit
 * @return int
 */
function wc_appointments_get_min_timestamp_for_day( $date, $offset, $unit ) {
	$timestamp = $date;

	$now = current_time( 'timestamp' );
	$is_today     = date( 'y-m-d', $date ) === date( 'y-m-d', $now );

	if ( $is_today || empty( $date ) ) {
		$timestamp = strtotime( "midnight +{$offset} {$unit}", $now );
	}
	return $timestamp;
}

/**
 * Give this function a appointment or staff ID, and a range of dates and get back
 * how many places are available for the requested quantity of appointments for all slots within those dates.
 *
 * Replaces the WC_Product_Appointment::get_available_appointments method.
 *
 * @param  WC_Product_Appointment | integer $appointable_product Can be a product object or a appointment prouct ID.
 * @param  integer $start_date
 * @param  integer $end_date
 * @param  integer|null optional $staff_id
 * @param  integer $qty
 * @return array|int|boolean|WP_Error False if no places/slots are available or the dates are invalid.
 */
function wc_appointments_get_total_available_appointments_for_range( $appointable_product, $start_date, $end_date, $staff_id = null, $qty = 1 ) {
	// alter the end date to limit it to go up to one slot if the setting is enabled
	if ( $appointable_product->get_availability_span() ) {
		$end_date = strtotime( '+ ' . $appointable_product->get_duration() . ' ' . $appointable_product->get_duration_unit(), $start_date );
	}

	// Check the date is not in the past
	if ( date( 'Ymd', $start_date ) < date( 'Ymd', current_time( 'timestamp' ) ) ) {
		return false;
	}

	// Check we have a staff if needed
	if ( $appointable_product->has_staff() && ! is_numeric( $staff_id ) && ! $appointable_product->is_staff_assignment_type( 'all' ) ) {
		return false;
	}

	$min_date   = $appointable_product->get_min_date_a();
	$max_date   = $appointable_product->get_max_date_a();
	$check_from = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
	$check_to   = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );

	// Min max checks
	if ( 'month' === $appointable_product->get_duration_unit() ) {
		$check_to = strtotime( 'midnight', strtotime( date( 'Y-m-t', $check_to ) ) );
	}
	if ( $end_date < $check_from || $start_date > $check_to ) {
		return false;
	}

	// Get availability of each staff - no staff has been chosen yet.
	if ( $appointable_product->has_staff() && ! $staff_id ) {
		return $appointable_product->get_all_staff_availability( $start_date, $end_date, $qty );
	} else {
		// If we are checking for appointments for a specific staff, or have none.
		$check_date = $start_date;

		if ( in_array( $appointable_product->get_duration_unit(), array( 'minute', 'hour' ) ) ) {
			if ( ! WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $start_date, $end_date, $staff_id ) ) {
				return false;
			}
		} else {
			while ( $check_date < $end_date ) {
				if ( ! WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $appointable_product, $check_date, $staff_id ) ) {
					return false;
				}
				if ( $appointable_product->get_availability_span() ) {
					break; // Only need to check first day
				}
				$check_date = strtotime( '+1 day', $check_date );
			}
		}

		// Get slots availability
		return $appointable_product->get_slots_availability( $start_date, $end_date, $qty, $staff_id );
	}
}

/**
 * Summary of appointment data for admin and checkout.
 *
 * @version 3.3.0
 *
 * @param  WC_Appointment $appointment
 * @param  bool       $is_admin To determine if this is being called in admin or not.
 */
function wc_appointments_get_summary_list( $appointment, $is_admin = false ) {
	$product   			= $appointment->get_product();
	$providers 			= $appointment->get_staff_members( $names = true );
	$label     			= $product && is_callable( array( $product, 'get_staff_label' ) ) && $product->get_staff_label() ? $product->get_staff_label() : __( 'Providers:', 'woocommerce-appointments' );
	$date 				= sprintf( '%1$s', $appointment->get_start_date() );
	$duration 			= sprintf( '%1$s', $appointment->get_duration() );

	$template_args = apply_filters( 'wc_appointments_get_summary_list', array(
		'appointment'  		=> $appointment,
		'product'      		=> $product,
		'providers'     	=> $providers,
		'label'        		=> $label,
		'date' 				=> $date,
		'duration' 			=> $duration,
		'is_admin'     		=> $is_admin,
	) );

	wc_get_template( 'order/appointment-summary-list.php', $template_args, '', WC_APPOINTMENTS_TEMPLATE_PATH );
}

/**
 * Converts a string (e.g. yes or no) to a bool.
 * @param  string $string
 * @return boolean
 */
function wc_appointments_string_to_bool( $string ) {
	if ( function_exists( 'wc_string_to_bool' ) ) {
		return wc_string_to_bool( $string );
	}
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * @since 3.0.0
 * @param $minute
 * @param $check_date
 *
 * @return int
 */
function wc_appointment_minute_to_time_stamp( $minute, $check_date ) {
	return strtotime( "+ $minute minutes", $check_date );
}

/**
 * Convert a timestamp into the minutes after 0:00
 *
 * @since 3.0.0
 * @param integer $timestamp
 * @return integer $minutes_after_midnight
 */
function wc_appointment_time_stamp_to_minutes_after_midnight( $timestamp ) {
	$hour    = absint( date( 'H', $timestamp ) );
	$min     = absint( date( 'i', $timestamp ) );
	return  $min + ( $hour * 60 );
}

/**
 * Find available and scheduled slots for specific staff (if any) and return them as array.
 *
 * @param \WC_Product_Appointment $appointable_product
 * @param  array  $slots
 * @param  array  $intervals
 * @param  integer $staff_id
 * @param  integer $from The starting date for the set of slots
 * @param  integer $to
 * @return array
 *
 * @version  1.10.5
 */
function wc_appointments_get_time_slots( $appointable_product, $slots, $intervals = array(), $time_to_check = 0, $staff_id = 0, $from = '', $to = 0, $timezone = 'UTC' ) {
	$intervals = empty( $intervals ) ? $appointable_product->get_intervals() : $intervals;

	list( $interval, $base_interval ) = $intervals;
	$interval = 'start' === $appointable_product->get_availability_span() ? $base_interval : $interval;

	$appointment_staff     = $staff_id ? $staff_id : 0;
	$appointment_staff     = $appointable_product->has_staff() && ! $appointment_staff ? $appointable_product->get_staff_ids() : $appointment_staff;

	#print '<pre>'; print_r( $slots ); print '</pre>';

	$slots                 = $appointable_product->get_available_slots( $slots, $intervals, $staff_id, $from, $to );
	$existing_appointments = WC_Appointments_Controller::get_all_existing_appointments( $appointable_product, $from, $to );

	#print '<pre>'; print_r( $slots ); print '</pre>';

	$available_slots       = array();

	foreach ( $slots as $slot ) {
		$staff = array();
		$available_qty = 0;

		// Make sure default staff qty is set.
		// Used for google calendar events in most cases.
		$staff[0] = $appointable_product->get_available_qty();

		#print '<pre>'; print_r( date( 'G:i', $slot ) ); print '</pre>';

		// Figure out how much qty have, either based on combined staff quantity,
		// single staff, or just product.
		if ( $appointable_product->has_staff() && $appointment_staff && is_array( $appointment_staff ) ) {
			foreach ( $appointment_staff as $appointment_staff_id ) {
				// Only include if it is available for this selection.
				if ( ! WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $appointable_product, $slot, $appointment_staff_id ) ) {
					continue;
				}

				if ( in_array( $appointable_product->get_duration_unit(), array( 'minute', 'hour' ) )
					&& ! WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $slot, strtotime( "+{$interval} minutes", $slot ), $appointment_staff_id ) ) {
					continue;
				}

				$get_available_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $slot, strtotime( "+{$interval} minutes", $slot ), $appointment_staff_id, true );
				$staff[ $appointment_staff_id ] = $get_available_qty;

				$available_qty += $get_available_qty;
			}
		} elseif ( $appointable_product->has_staff() && $appointment_staff && is_int( $appointment_staff ) ) {
			// Only include if it is available for this selection. We set this slot to be appointable by default, unless some of the rules apply.
			if ( ! WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $slot, strtotime( "+{$interval} minutes", $slot ), $appointment_staff ) ) {
				continue;
			}

			$get_available_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $slot, strtotime( "+{$interval} minutes", $slot ), $appointment_staff, true );
			$staff[ $appointment_staff ] = $get_available_qty;

			$available_qty += $get_available_qty;
		} else {
			$get_available_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $appointable_product, $slot, strtotime( "+{$interval} minutes", $slot ), $appointment_staff, true );
			$staff[0] = $get_available_qty;

			$available_qty += $get_available_qty;
		}

		$qty_scheduled_in_slot = 0;
		$qty_scheduled_for_staff = array();

		#print '<pre>'; print_r( date( 'G:i', $slot ) ); print '</pre>';
		#print '<pre>'; print_r( $staff ); print '</pre>';
		#print '<pre>'; print_r( $available_qty ); print '</pre>';

		foreach ( $existing_appointments as $existing_appointment ) {
			#print '<pre>'; print_r( $existing_appointment->get_product_id() ); print '</pre>';
			if ( $existing_appointment->is_within_slot( $slot, strtotime( "+{$interval} minutes", $slot ) ) ) {
				$qty_to_add = $existing_appointment->get_qty() ? $existing_appointment->get_qty() : 1;
				if ( wc_appointments_gcal_synced_product_id() === $existing_appointment->get_product_id() ) {
					$qty_to_add = $appointable_product->get_available_qty();
				}
				if ( $appointable_product->has_staff() ) {
					// Get staff IDs. If non exist, make it zero (applies to all).
					$existing_staff_ids = $existing_appointment->get_staff_ids();
					$existing_staff_ids = ! is_array( $existing_staff_ids ) ? array( $existing_staff_ids ) : $existing_staff_ids;
					$existing_staff_ids = empty( $existing_staff_ids ) ? array(0) : $existing_staff_ids;

					if ( $appointment_staff
						 && ! is_array ( $appointment_staff )
						 && $existing_staff_ids
						 && is_array( $existing_staff_ids )
						 && in_array( $appointment_staff, $existing_staff_ids ) ) {

						foreach ( $existing_staff_ids as $existing_staff_id ) {
							if ( $existing_appointment->get_product_id() !== $appointable_product->get_id() && apply_filters( 'wc_apointments_check_appointment_product', true, $existing_appointment->get_product_id() ) ) {
								$qty_to_add = $appointable_product->get_available_qty( $existing_staff_id );
							}
							$qty_scheduled_for_staff[] = $qty_to_add;
							$staff[ $existing_staff_id ] = ( isset( $staff[ $existing_staff_id ] ) ? $staff[ $existing_staff_id ] : 0 ) - $qty_to_add;
						}
						$qty_scheduled_in_slot += max( $qty_scheduled_for_staff );

					} elseif ( $appointment_staff
						 && is_array ( $appointment_staff )
						 && $existing_staff_ids
						 && is_array( $existing_staff_ids )
						 && array_intersect( $appointment_staff, $existing_staff_ids ) ) {

						foreach ( $existing_staff_ids as $existing_staff_id ) {
							if ( $existing_appointment->get_product_id() !== $appointable_product->get_id() && apply_filters( 'wc_apointments_check_appointment_product', true, $existing_appointment->get_product_id() ) ) {
								$qty_to_add = $appointable_product->get_available_qty( $existing_staff_id );
							}
							$qty_scheduled_for_staff[] = $qty_to_add;
							$staff[ $existing_staff_id ] = ( isset( $staff[ $existing_staff_id ] ) ? $staff[ $existing_staff_id ] : 0 ) - $qty_to_add;
						}
						$qty_scheduled_in_slot += max( $qty_scheduled_for_staff );

					} else {
						$staff[0] = ( isset( $staff[0] ) ? $staff[0] : 0 ) - $qty_to_add;
					}
				} else {
					$qty_scheduled_in_slot += $qty_to_add;
					$staff[0] = ( isset( $staff[0] ) ? $staff[0] : 0 ) - $qty_to_add;
				}
			}
		}

		$available_slots[ $slot ] = array(
			'scheduled' => $qty_scheduled_in_slot,
			'available' => $available_qty - $qty_scheduled_in_slot,
			'staff'     => $staff,
		);

		#print '<pre>'; print_r( date( 'G:i', $slot ) ); print '</pre>';
		#print '<pre>'; print_r( $available_qty ); print '</pre>';
		#print '<pre>'; print_r( $available_slots ); print '</pre>';
	}

	return $available_slots;
}

/**
 * Find available slots and return HTML for the user to choose a slot. Used in class-wc-appointments-ajax.php.
 *
 * @param \WC_Product_Appointment $appointable_product
 * @param  array   $slots
 * @param  array   $intervals
 * @param  integer $time_to_check
 * @param  integer $staff_id
 * @param  integer $from The starting date for the set of slots
 * @param  integer $to
 * @return string
 *
 * @version  3.3.0
 */
 function wc_appointments_get_time_slots_html( $appointable_product, $slots, $intervals = array(), $time_to_check = 0, $staff_id = 0, $from = '', $to = 0, $timezone = 'UTC' ) {
 	$available_slots = wc_appointments_get_time_slots( $appointable_product, $slots, $intervals, $time_to_check, $staff_id, $from, $to, $timezone );
 	$slots_html      = '';

	#print '<pre>'; print_r( $available_slots ); print '</pre>';

	if ( $available_slots ) {

		// Timezones.
		$timezone_datetime = new DateTime();
		$local_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), $timezone );
		$site_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), wc_timezone_string() );

		// Split day into three parts
		$times = apply_filters( 'woocommerce_appointments_times_split', array(
			'morning' => array(
				'name' => __( 'Morning', 'woocommerce-appointments' ),
				'from' => strtotime( '00:00' ),
				'to' => strtotime( '12:00' ),
			),
			'afternoon' => array(
				'name' => __( 'Afternoon', 'woocommerce-appointments' ),
				'from' => strtotime( '12:00' ),
				'to' => strtotime( '17:00' ),
			),
			'evening' => array(
				'name' => __( 'Evening', 'woocommerce-appointments' ),
				'from' => strtotime( '17:00' ),
				'to' => strtotime( '24:00' ),
			),
		));

		$slots_html .= "<div class=\"slot_row\">";
		foreach ( $times as $k => $v ) {
			$slots_html .= "<ul class=\"slot_column $k\">";
			$slots_html .= '<li class="slot_heading">' . $v['name'] . '</li>';
			$count = 0;

		 	foreach ( $available_slots as $slot => $quantity ) {
				if ( $v['from'] <= strtotime( date( 'G:i', $slot ) ) && $v['to'] > strtotime( date( 'G:i', $slot ) ) ) {
					$selected = $time_to_check && date( 'G:i', $slot ) === date( 'G:i', $time_to_check ) ? ' selected' : '';
					$slot_locale = ( $local_time !== $site_time ) ? sprintf( __( ' data-locale="Your local time: %s"', 'woocommerce-appointments' ), wc_appointment_timezone_locale( 'site', 'user', $slot, wc_date_format() . ', ' . wc_time_format(), $timezone ) ) : '';

					#print '<pre>'; print_r( date( 'Hi', $slot ) ); print '</pre>';
					#print '<pre>'; print_r( $quantity ); print '</pre>';

					// Available quantity should be max per staff and not max overall.
					if ( is_array( $quantity['staff'] ) && 1 < count( $quantity['staff'] ) ) {
						unset( $quantity['staff'][0] );
						$quantity_available = absint( max( $quantity['staff'] ) );
						$quantity_all_available = absint( array_sum( $quantity['staff'] ) );
						if ( 0 === $staff_id ) {
							$quantity_available = ( $appointable_product->get_qty_max() < $quantity_available ) ? $appointable_product->get_qty_max() : $quantity_available;
							$spaces_left = sprintf( _n( '%d max', '%d max', $quantity_available, 'woocommerce-appointments' ), $quantity_available );
							$spaces_left .= ', ' . sprintf( _n( '%d left', '%d left', $quantity_all_available, 'woocommerce-appointments' ), $quantity_all_available );
						} else {
							$spaces_left = sprintf( _n( '%d left', '%d left', $quantity_available, 'woocommerce-appointments' ), $quantity_available );
						}
					} else {
						$quantity_available = absint( $quantity['available'] );
						$spaces_left = sprintf( _n( '%d left', '%d left', $quantity['available'], 'woocommerce-appointments' ), $quantity['available'] );
					}

					#print '<pre>'; print_r( date( 'Hi', $slot ) ); print '</pre>';
					#print '<pre>'; print_r( $quantity_available ); print '</pre>';
					#print '<pre>'; print_r( $quantity_all_available ); print '</pre>';
					#print '<pre>'; print_r( $staff_id ); print '</pre>';

					if ( $quantity['available'] > 0 ) {
						if ( $quantity['scheduled'] ) {
							/* translators: 1: quantity available */
			 				$slot_html = "<li class=\"slot$selected\"$slot_locale data-slot=\"" . esc_attr( date( 'Hi', $slot ) ) . "\"><a href=\"#\" data-value=\"" . date_i18n( 'G:i', $slot ) . "\">" . date_i18n( wc_time_format(), $slot ) . " <small class=\"spaces-left\">" . $spaces_left . "</small></a></li>";
			 			} else {
			 				$slot_html = "<li class=\"slot$selected\"$slot_locale data-slot=\"" . esc_attr( date( 'Hi', $slot ) ) . "\"><a href=\"#\" data-value=\"" . date_i18n( 'G:i', $slot ) . "\">" . date_i18n( wc_time_format(), $slot ) . "</a></li>";
			 			}
						$slots_html .= apply_filters( 'woocommerce_appointments_time_slot_html', $slot_html, $slot, $quantity, $time_to_check, $staff_id, $timezone, $appointable_product );
			 		} else {
						continue;
					}
				} else {
					continue;
				}

				$count++;
		 	}

			if ( ! $count ) {
				$slots_html .= '<li class="slot slot_empty">' . __( '&#45;', 'woocommerce-appointments' ) . '</li>';
			}

			$slots_html .= "</ul>";
		}

		$slots_html .= "</div>";
	}

 	return apply_filters( 'woocommerce_appointments_time_slots_html', $slots_html, $slots, $intervals, $time_to_check, $staff_id, $from, $to, $timezone, $appointable_product );
}
