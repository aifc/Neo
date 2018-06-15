<?php
//this
add_filter( 'wc_appointments_product_get_available_slots', 'reserve_slot' ,10,5);
function reserve_slot( $available_slots, $this, $slots, $intervals, $staff_id)
{		

	$appointment_ids = get_users_upcoming_appoinments();
	
	foreach($appointment_ids as $appointment_id)
	{	
		$start_time = get_post_meta($appointment_id,'_appointment_start', true);

		//Todo consider intervals
		// If an appointment is not equal to the slot but it'll be affected beause the slot is in the middle of the start and end time.
		if((($key = array_search(strtotime($start_time),$slots))!==false) )
		{
			unset($slots[$key]);
		}
	}
	
	return $slots;
}

function get_users_upcoming_appoinments() {

	global $wpdb;

	$CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

	$current_time = current_time( 'YmdHis' );
	$user = wp_get_current_user(); 
	$user_id = $user->ID;

	$upcoming_appointments_args = array(
	      'orderby'       => 'start_date',
	      'order'         => 'ASC',
	      'meta_query'    => array(
	        'relation' => 'AND',
	        array(
	          'key'     => '_appointment_customer_id',
	          'value'   => absint( $user_id ),
	          'compare' => 'IN',
	        ),
	        'start_date'  => array(
	          'key'     => '_appointment_start',
	          'value'   => $current_time,
	          'compare' => '>=',
	        ),
	      ),
	      'post_status' => get_wc_appointment_statuses( ),
	    );
	$upcoming_appointments = WC_Appointments_Controller::get_appointments( $upcoming_appointments_args );

	$group_appointment_ids = $wpdb->get_results($wpdb->prepare(
    	"SELECT $CLIENT_GROUP_SESSIONS_TABLE.appointment_id
    	FROM $CLIENT_GROUP_SESSIONS_TABLE
    	INNER JOIN $wpdb->users ON ($CLIENT_GROUP_SESSIONS_TABLE.email = $wpdb->users.user_email)
    	WHERE $CLIENT_GROUP_SESSIONS_TABLE.email = %s
    	AND $CLIENT_GROUP_SESSIONS_TABLE.status = 'accepted'", wp_get_current_user()->user_email),ARRAY_A);

	$group_appointment_ids = array_map(create_function('$o', 'return $o["appointment_id"];'), $group_appointment_ids);
	$group_appointment_ids = array_map('intval',$group_appointment_ids);
	$appointment_ids = array_map(create_function('$o', 'return $o->id;'), $upcoming_appointments);

	$appointment_ids = array_merge($group_appointment_ids,$appointment_ids);
	
	return $appointment_ids;

}

add_filter('woocommerce_appointments_time_slots_html', 'idl_woocommerce_appointments_time_slots_html', 10, 8);
function idl_woocommerce_appointments_time_slots_html($slot_html, $slots, $intervals, $time_to_check, $staff_id, $from, $timezone, $appointment) {
	if ( empty( $intervals ) ) {
		$default_interval = 'hour' === $appointment->get_duration_unit() ? $appointment->get_duration() * 60 : $appointment->get_duration();
		$custom_interval = 'hour' === $appointment->get_duration_unit() ? $appointment->get_duration() * 60 : $appointment->get_duration();
		if ( $appointment->get_interval_unit() && $appointment->get_interval() ) {
			$custom_interval = 'hour' === $appointment->get_interval_unit() ? $appointment->get_interval() * 60 : $appointment->get_interval();
		}
		$intervals        = array( $default_interval, $custom_interval );
	}
	list( $interval, $base_interval ) = $intervals;
	$start_date = current( $slots );
	$end_date = end( $slots );
	$slots		= $appointment->get_available_slots( $slots, $intervals, $staff_id, $from );
	$slot_html	= '';
	if ( $slots ) {
		// Timezones
		$timezone_datetime = new DateTime();
		$local_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), $timezone );
		$site_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), wc_timezone_string() );
		// Split day into three parts
		$times = apply_filters( 'woocommerce_appointments_times_split', array(
			"morning" => array(
				"name" => __( 'Morning', 'woocommerce-appointments' ),
				"from" => strtotime("00:00"),
				"to" => strtotime("12:00"),
			),
			"afternoon" => array(
				"name" => __( 'Afternoon', 'woocommerce-appointments' ),
				"from" => strtotime("12:00"),
				"to" => strtotime("17:00"),
			),
			"evening" => array(
				"name" => __( 'Evening', 'woocommerce-appointments' ),
				"from" => strtotime("17:00"),
				"to" => strtotime("24:00"),
			),
		));
		$slot_html .= "<div class=\"slot_row\">";
		foreach( $times as $k => $v ) {
			$slot_html .= "<ul class=\"slot_column $k\">";
			$slot_html .= '<li class="slot_heading">' . $v['name'] . '</li>';
			$count = 0;
			foreach ( $slots as $slot ) {
				if ( $v['from'] <= strtotime( date( 'G:i', $slot ) ) && $v['to'] > strtotime( date( 'G:i', $slot ) ) ) {
					$selected = date( 'G:i', $slot ) == date( 'G:i', $time_to_check ) ? ' selected' : '';
					// Test availability for each slot.
					$test_availability = $appointment->get_available_appointments( $slot, strtotime( "+{$interval} minutes", $slot ), $staff_id, 1 );
					//$test_availability = 1;
					//var_dump( date( 'G:i', $slot ) );
					// Return available qty for each slot.
					if ( ! is_wp_error( $test_availability ) ) {
						if ( is_array( $test_availability ) ) {
							$available_qty = max( $test_availability );
						} else {
							$available_qty = $test_availability;
						}
					} else {
						$available_qty = 0;
					}
					// Disply each slot HTML.
					if ( $available_qty > 0 ) {
						// $slot_left = " <small class=\"spaces-left\">(" . sprintf( _n( '%d left', '%d left', $available_qty, 'woocommerce-appointments' ), absint( $available_qty ) ) . ")</small>";
						$slot_left = "";
						$slot_locale = ( $local_time !== $site_time ) ? sprintf( __( ' data-locale="Your local time: %s"', 'woocommerce-appointments' ), wc_appointment_timezone_locale( 'site', 'user', $slot, wc_date_format() . ', ' . wc_time_format(), $timezone ) ) : '';
						$slot_html .= "<li class=\"slot$selected\"$slot_locale><a href=\"#\" data-value=\"" . date_i18n( 'G:i', $slot ) . "\">" . date_i18n( wc_time_format(), $slot ) . "$slot_left</a></li>";
					} else {
						continue;
					}
				} else {
					continue;
				}
				$count++;
			}
			if ( ! $count ) {
				$slot_html .= '<li class="slot slot_empty">' . __( '&#45;', 'woocommerce-appointments' ) . '</li>';
			}
			$slot_html .= "</ul>";
		}
		$slot_html .= "</div>";
	} 

	return $slot_html;
}


