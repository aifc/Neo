<?php
   /*
   Plugin Name: front-end-calendar
   Description: Custom plugin to display a calendar that integrates with woocommerce appointment
   Version: 1.0
   Author: Pursuit Technology
   */

//[front_end_calendar]
//require_once( WP_PLUGIN_DIR.'/woocommerce-appointments/includes/admin/class-wc-appointments-admin-calendar.php');

if ( !class_exists( "FrontEndCalendar" ) )
{
	add_action('wp_enqueue_scripts','enqueue_calendar_scripts');

	function enqueue_calendar_scripts() {
	    wp_enqueue_style( 'wc_frontendcalendar_styles', plugin_dir_url(__FILE__).'/css/admin.css' );
	    wp_register_script('tiptipscript', plugin_dir_url(__FILE__).'/lib/jquery.tipTip.min.js', array('jquery'));
	    wp_enqueue_script('tiptipscript');
	}

	class FrontEndCalendar
	{
		function FrontEndCalendar() // Constructor
		{	
			//echo plugin_dir_url(__FILE__).'/lib/front-end-calendar.js';
			register_activation_hook( __FILE__, array($this, 'run_on_activate') );
			add_action('admin_init', array($this, 'init_admin'));


			// This adds support for a "simplenote" shortcode
            add_shortcode( 'front_end_calendar', array( $this, 'front_end_calendar_shortcode' ) );
            

		}

		public function front_end_calendar_shortcode()
		{
			global $wpdb;

			$CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

			$user = wp_get_current_user(); 
		 	$user_id = $user->ID;
		 	if (in_array( 'shop_staff', (array)$user->roles)) {
		 		$counselor = true;
		 	}
			
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

			$current_time = current_time( 'YmdHis' );
  			
  			if($counselor)
  			{
  				$appointment_args = array(
		    	 	'meta_query'    => array(
		    	    'relation' => 'AND',
				        array(
				          	'key'     => '_appointment_staff_id',
				          	'value'   => absint( $user_id ),
				          	'compare' => 'IN',
				        ),
			      	),
		    	  	'post_status' => get_wc_appointment_statuses(),
			    );
  			}
  			else
  			{
  				$appointment_args = array(
		    	 	'meta_query'    => array(
		    	    'relation' => 'AND',
				        array(
				          	'key'     => '_appointment_customer_id',
				          	'value'   => absint( $user_id ),
				          	'compare' => 'IN',
				        ),
			      	),
		    	  	'post_status' => get_wc_appointment_statuses(),
			    );
  			}
			$this->appointments = WC_Appointments_Controller::get_appointments($appointment_args);

			$fetched_ids = array();
			foreach($this->appointments as $appointment_id)
			{
				$fetched_ids[] = $appointment_id->id;
			}
  			//check also in group sessions table
		   	$appointment_ids = $wpdb->get_results($wpdb->prepare(
		    	"SELECT $CLIENT_GROUP_SESSIONS_TABLE.appointment_id
		    	FROM $CLIENT_GROUP_SESSIONS_TABLE
		    	WHERE $CLIENT_GROUP_SESSIONS_TABLE.email = %s
		    	AND $CLIENT_GROUP_SESSIONS_TABLE.status = 'accepted'", wp_get_current_user()->user_email),ARRAY_A);
		  
		  	$filter = array();
		  	foreach($appointment_ids as $appointment_id)
		  	{
		    	$filter[] = $appointment_id['appointment_id'];
		  	}
		 
			$results = array_diff($filter,$fetched_ids);

		  	if ( !empty( $results ) ) {
				foreach ( $results as $result ) {
					if(get_wc_appointment($result)->status != 'cancelled')
						$this->appointments[] =  get_wc_appointment( $result );
				}
			}
			 // print_r(time());
			  //print_r($this->appointments);
			$calendar =  '';
			$calendar .= '<form method="get" id="mainform" enctype="multipart/form-data" class="wc_appointments_calendar_form month_view">';
			//start filters

			$calendar .='<div class="date_selector"><a class="prev" href="'.esc_url( add_query_arg( array( 'calendar_year' => $year, 'calendar_month' => $month - 1 ) ) ).'">&larr;</a><div>';
			$calendar .='<select name="calendar_month">';
				
			for ( $i = 1; $i <= 12; $i ++ ){
				$calendar .='<option value="'.$i.'"'.selected( $month, $i , false).'>'.ucfirst( date_i18n( 'M', strtotime( '2013-' . $i . '-01' ) ) ).'</option>';
			}
			
			$calendar .= '</select></div><div><select name="calendar_year">';

			$current_year = date( 'Y' );

			for ( $i = ( $current_year - 1 ); $i <= ( $current_year + 5 ); $i ++ )
			{
				$calendar .= '<option value="'.$i.'"'.selected( $year, $i,false ).'>'.$i.'</option>';
			}
				
			$calendar .='</select></div>';

			$calendar .='<a class="next" href="'.esc_url( add_query_arg( array( 'calendar_year' => $year, 'calendar_month' => $month + 1 ) ) ).'">&rarr;</a></div>';
			
			//end of filter

			//start of Calendar header

			$calendar .= '<h3>'.date('F', mktime(0, 0, 0, $month, 10)).' '.$year.'</h3>';
			
			//end of Calendar table
			//start of calendar table

			$calendar .= '<table class="wc_appointments_calendar widefat"><thead><tr>';

			$start_of_week = get_option( 'start_of_week', 1 ); 

			for ( $ii = $start_of_week; $ii < $start_of_week + 7; $ii ++ )
			{
				$calendar .= '<th>'.date_i18n( _x( 'l', 'date format', 'woocommerce-appointments' ), strtotime( "next sunday +{$ii} day" ) ).'</th>';
			}

			$calendar .= '</tr></thead><tbody><tr>';

			$timestamp = $start_timestamp;
		    $index     = 0;

		    while ( $timestamp <= $end_timestamp )
		    {
		    	$calendar .= '<td width="14.285%" class="';
		    	if ( date( 'n', $timestamp ) != absint( $month ) ) {
		                $calendar .= 'calendar-diff-month';
		      	}
		      	$calendar .= '"<a href="#">'.date( 'd', $timestamp ).'</a>';
		   		
		   		$calendar .= '<div class="appointments"><ul>';
		   		$calendar .= $this->list_appointments(
								date( 'd', $timestamp ),
								date( 'm', $timestamp ),
								date( 'Y', $timestamp )
							);
		   		$calendar .= '</ul></div></td>';//insert appointment code here

		   		$timestamp = strtotime( '+1 day', $timestamp );
		      	$index ++;
		      	if ( 0 === $index % 7 ) {
		            $calendar .= '</tr><tr>';
		      	}
		    }
		    $calendar .= '</tr></tbody></table></form>';

		    ob_start();
		    	include( 'lib/html-calendar-dialog.php' );			
			$calendar .= ob_get_clean();	
			return $calendar;
		}

		public function list_appointments( $day, $month, $year ) {
			$date_start = strtotime( "$year-$month-$day 00:00" );
			$date_end   = strtotime( "$year-$month-$day 23:59" );

			foreach ( $this->appointments as $appointment ) {
				if (
					( $appointment->start >= $date_start && $appointment->start < $date_end ) ||
					( $appointment->start < $date_start && $appointment->end > $date_end ) ||
					( $appointment->end > $date_start && $appointment->end <= $date_end )
					) {

						 $multiplecard.= $this->single_appointment_card( $appointment );
				}
			}
			return $multiplecard;
		}

		public function single_appointment_card( $appointment, $column = 0, $view = 'month', $height = 0 ) {
			$user = wp_get_current_user(); 
		 	$user_id = $user->ID;
		 	if (in_array( 'shop_staff', (array)$user->roles)) {
		 		$counselor = true;
		 	}

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
			$appointment_color = '#2893CB';
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
				<ul>';
			if($counselor)
				$singlecard .='<li class="appointment_customer status-' . $datarray['customer_status'] . '">' . $datarray['appointment_customer'] . '</li>';
			else
				$singlecard .='<li class="appointment_customer status-' . $datarray['customer_status'] . '">' . $datarray['staff_name'] . '</li>';
			$singlecard .='<li class="appointment_status status-' . $datarray['appointment_status'] . '" data-tip="' . $datarray['appointment_status'] . '"></li>
				</ul>
			</a>
			</li>' . $multiple_staff;

			//echo apply_filters( 'front_end_appointments_calendar_view_single_card', $singlecard, $datarray, $appointment );
			// $singelcard;
			return $singlecard;
		}
	}
}

// Instantiating the Class
if (class_exists("FrontEndCalendar")) {
	$FrontEndCalendar = new FrontEndCalendar();
}


			