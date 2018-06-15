<?php

/**
 * Get an appointment object
 * @param  int $id
 * @return WC_Appointment
 */
function get_wc_appointment( $id ) {
	return new WC_Appointment( $id );
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
 * Returns true if the product is an appointment product, false if not
 * @return bool
 */
function is_wc_appointment_product( $product ) {
	if ( empty( $product->product_type ) ) {
		return false;
	}

	$appointment_product_types = apply_filters( 'woocommerce_appointments_product_types', array( 'appointment' ) );
	if ( in_array( $product->product_type, $appointment_product_types ) ) {
		return true;
	}

	return false;
}

/**
 * Convert key to a nice readable label
 * @param  string $key
 * @return string
 */
function get_wc_appointment_data_label( $key, $product ) {
	$labels = apply_filters( 'woocommerce_appointments_data_labels', array(
		'staff'    => ( $product->wc_appointment_staff_label ? $product->wc_appointment_staff_label : __( 'Providers', 'woocommerce-appointments' ) ),
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
			'paid'                 => __( 'Paid &amp; Confirmed','woocommerce-appointments' ),
			'cancelled'            => __( 'Cancelled','woocommerce-appointments' ),
			'complete'             => __( 'Complete','woocommerce-appointments' ),
		) );
	} elseif ( 'validate' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_validation', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid &amp; Confirmed','woocommerce-appointments' ),
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
			'paid'                 => __( 'Paid &amp; Confirmed','woocommerce-appointments' ),
		) );
	} elseif ( 'scheduled' === $context ) {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_scheduled', array(
			'paid'                 => __( 'Paid &amp; Confirmed','woocommerce-appointments' ),
		) );
	} else {
		$statuses = apply_filters( 'woocommerce_appointment_statuses_for_fully_scheduled', array(
			'unpaid'               => __( 'Unpaid','woocommerce-appointments' ),
			'pending-confirmation' => __( 'Pending Confirmation','woocommerce-appointments' ),
			'confirmed'            => __( 'Confirmed','woocommerce-appointments' ),
			'paid'                 => __( 'Paid &amp; Confirmed','woocommerce-appointments' ),
			'complete'             => __( 'Complete','woocommerce-appointments' ),
			'in-cart'              => __( 'In Cart','woocommerce-appointments' ),
		) );
	}

	if ( class_exists( 'WC_Deposits' ) ) {
		$statuses['wc-partial-payment'] = __( 'Partially Paid', 'woocommerce-deposits' );
	}

	// backwards compatibility
	return $include_translation_strings ? $statuses : array_keys( $statuses );
}

/**
 * Validate and create a new appointment manually.
 *
 * @see WC_Appointment::new_appointment() for available $new_appointment_data args
 * @param  int $product_id you are appointment
 * @param  array $new_appointment_data
 * @param  string $status
 * @param  boolean $exact If false, the function will look for the next available slot after your start date if the date is unavailable.
 * @return mixed WC_Appointment object on success or false on fail
 */
function create_wc_appointment( $product_id, $new_appointment_data = array(), $status = 'confirmed', $exact = false ) {
	// Merge appointment data
	$defaults = array(
		'product_id'  => $product_id, // Appointment ID
		'start_date'  => '',
		'end_date'    => '',
		'staff_id' => '',
	);

	$new_appointment_data = wp_parse_args( $new_appointment_data, $defaults );
	$product          = wc_get_product( $product_id );
	$start_date       = $new_appointment_data['start_date'];
	$end_date         = $new_appointment_data['end_date'];
	$max_date         = $product->get_max_date();
	$qty 			  = 1;

	// If not set, use next available
	if ( ! $start_date ) {
		$min_date   = $product->get_min_date();
		$start_date = strtotime( "+{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
	}

	// If not set, use next available + slot duration
	if ( ! $end_date ) {
		$end_date = strtotime( "+{$product->get_duration()} {$product->get_duration_unit()}", $start_date );
	}

	$searching = true;
	$date_diff = $end_date - $start_date;

	while ( $searching ) {

		$available_appointments = $product->get_available_appointments( $start_date, $end_date, $new_appointment_data['staff_id'], $qty );

		if ( $available_appointments && ! is_wp_error( $available_appointments ) ) {

			if ( ! $new_appointment_data['staff_id'] && is_array( $available_appointments ) ) {
				$new_appointment_data['staff_id'] = current( array_keys( $available_appointments ) );
			}

			$searching = false;

		} else {
			if ( $exact )
				return false;

			$start_date += $date_diff;
			$end_date   += $date_diff;

			if ( $end_date > strtotime( "+{$max_date['value']} {$max_date['unit']}" ) )
				return false;
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
 * Get appointable product staff.
 *
 * @param int $product_id product ID.
 *
 * @return array Staff objects list.
 */
function wc_appointment_get_product_staff( $product_id ) {
	$transient_name = 'staff_ps_' . md5( http_build_query( array( $product_id, WC_Cache_Helper::get_transient_version( 'appointments' ) ) ) );

	if ( false === ( $users = get_transient( $transient_name ) ) ) {
		global $wpdb;

		$users = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT users.ID, users.display_name, users.user_email
				FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
					LEFT JOIN $wpdb->users AS users
					ON users.ID = relationships.staff_id
				WHERE relationships.product_id = %d
				ORDER BY sort_order ASC
			", $product_id )
		);

		set_transient( $transient_name, $users, DAY_IN_SECONDS * 30 );
	}

	if ( $users ) {
		foreach ( $users as $staff_member ) {
			$staff[] = new WC_Product_Appointment_Staff( $staff_member, $product_id );
		}
	} else {
		$staff = array();
	}

	return $staff;
}

/**
 * Get appointable product staff member by ID.
 *
 * @param int $product_id product ID.
 * @param int $staff_id staff ID
 *
 * @return array Staff object.
 */
function wc_appointment_get_product_staff_member( $product_id, $staff_ids ) {
	global $wpdb;

	$users = $wpdb->get_results(
		$wpdb->prepare( "
			SELECT users.ID, users.display_name, users.user_email
			FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
				LEFT JOIN $wpdb->users AS users
				ON users.ID = relationships.staff_id		   
			WHERE relationships.product_id = %d
			ORDER BY sort_order ASC
		", $product_id )
	);

	$found = array();
	if ( $users ) {

		// If $staff_ids is a string, convert to an array.
		if ( ! is_array( $staff_ids ) ) {
			$staff_ids = array( $staff_ids );
		}

		foreach ( $users as $staff_member ) {
			if ( in_array( $staff_member->ID, $staff_ids ) ) {
				$found[] = new WC_Product_Appointment_Staff( $staff_member, $product_id );
			}
		}
	}

	return $found;
}

/**
 * Get staff products by staff ID.
 *
 * @param int $staff_id staff ID (user ID).
 * @return array Product object.
 */
function wc_appointment_get_appointable_products_for_staff( $staff_id ) {
	global $wpdb;

	$products = $wpdb->get_results(
		$wpdb->prepare( "
			SELECT product_id 	
			FROM {$wpdb->prefix}wc_appointment_relationships AS relationships		   
			WHERE relationships.staff_id = %d
			ORDER BY sort_order ASC
		", $staff_id )
	);

	$found = array();
	if ( $products ) {
		foreach ( $products as $product ) {
			$found[] = new WC_Product_Appointment( $product->product_id );
		}
	}

	return $found;
}

/**
 * Remove staff from product by staff ID and product ID.
 *
 * @param int $staff_id staff ID (user ID).
 * @param int $product_id product ID (post ID).
 * @return void.
 */
function wc_appointment_remove_staff_from_product( $staff_id, $product_id ) {
	global $wpdb;

	// Remove from the relationships table.
	$wpdb->delete(
		"{$wpdb->prefix}wc_appointment_relationships",
		array(
			'product_id' => $product_id,
			'staff_id' => $staff_id,
		)
	);

	// Get any staff left from the relationships table for the product and update its data and revert the relational db table and post meta logic that is set in class-wc-appointments-admin.php on line 559-593.
	$product_staff_left = $wpdb->get_results(
		$wpdb->prepare( "
			SELECT staff_id
			FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
			WHERE relationships.product_id = %d
			ORDER BY sort_order ASC
		", $product_id )
	);

	if ( ! empty( $product_staff_left ) ) {
		$max_loop = max( array_keys( $product_staff_left ) );
		$staff_base_costs  = array();
		$has_additional_costs = false;

		for ( $i = 0; $i <= $max_loop; $i ++ ) {

			$staff_id = absint( $product_staff_left[ $i ]->staff_id );
			// Update the sort order after the delete of the rows.
			$wpdb->update(
				"{$wpdb->prefix}wc_appointment_relationships",
				array(
					'sort_order'  => $i,
				),
				array(
					'product_id'  => $product_id,
					'staff_id' => $staff_id,
				)
			);
		}
	}
}

/**
 * Convert time in minutes to hours and minutes
 *
 * @return string
 */
function wc_appointment_convert_to_hours_and_minutes( $time ) {
	global $product;

	if ( ( is_cart() || is_checkout() ) && null !== $cart_item ) {
		$product = wc_get_product( $cart_item->id );
	}

	if ( is_object( $product ) ) {
		$duration_unit = $product->get_duration_unit() ? $product->get_duration_unit() : 'minute';
	} else {
		$duration_unit = 'minute';
	}

	if ( 'day' === $duration_unit ) {
		$return = sprintf( _n( '%s day', '%s days', $time, 'woocommerce-appointments' ), $time );
	} else {
		$return = sprintf( _n( '%s minute', '%s minutes', $time, 'woocommerce-appointments' ), $time );
	}

	// Duration longer than 120 minutes.
	if ( $time > apply_filters( 'woocommerce_appointments_duration_break', 120 ) ) {
		$hours = floor( $time / 60 );
		$return = sprintf( _n( '%s hour', '%s hours', $hours, 'woocommerce-appointments' ), $hours );
		$minutes = ( $time % 60 );
		if ( $minutes > 0 ) {
			$return .= '&nbsp;'; #empty space
			$return .= sprintf( _n( '%s minute', '%s minutes', $minutes, 'woocommerce-appointments' ), $minutes );
		}
	}

	return apply_filters( 'woocommerce_appointments_convert_to_hours', $return, $time, $product );
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
	if ( ! is_int( $time ) || $time > PHP_INT_MAX || $time < ~PHP_INT_MAX ) {
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
 * Get appointment products
 * @return array
 */
function get_wc_appointment_products( $show_all = false ) {
	$args = apply_filters( 'get_appointment_products_args', array(
		'post_status'    => 'publish',
		'post_type'      => 'product',
		'posts_per_page' => -1,
		'no_found_rows'  => true,
		'update_post_meta_cache' => false,
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'appointment',
			),
		),
		'suppress_filters' => true,
	) );

	// Only show products from current staff member, staff can't see other staff's products.
	if ( ! current_user_can( 'manage_others_appointments' ) && ! $show_all ) {
		$args['author'] = get_current_user_id();
	}

	$posts_query = new WP_Query();
    $appointment_products = $posts_query->query( $args );

	return $appointment_products;
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
function get_wc_appointments_count_per_staff( $staff_id, $post_status = '' ) {
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
