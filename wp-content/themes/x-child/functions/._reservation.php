<?php 

function get_coupon_code($user_id)
{
	global $wpdb;
 	$is_agency = user_can($user_id,'agency');
	$current_user = wp_get_current_user();
 	$username = $current_user->user_email;

	if($is_agency)
    {	
    	$AGENCY_COUPONS_TABLE = $wpdb->prefix . 'agency_coupons';
    	$coupon = $wpdb->get_var('SELECT coupon_code FROM '.$AGENCY_COUPONS_TABLE.' WHERE agency_id = '.$user_id);
    }
    else
    {
	 	$CLIENT_COUPON_INTERIM_TABLE = $wpdb->prefix . 'client_coupon_interim';
	    $interim_coupon = $wpdb->get_row( "SELECT * FROM $CLIENT_COUPON_INTERIM_TABLE
	                               WHERE username='$username'" );
	    $coupon = $interim_coupon->coupon_code;
	}
	return $coupon;
}
function filter_reservation_table($coupon_code)
{
	global $wpdb;

	$current_date = current_time('mysql');
	$AGENCY_RESERVATION_TABLE = $wpdb->prefix . 'agency_reservation';
	$rows = $wpdb->get_results( "SELECT * FROM $AGENCY_RESERVATION_TABLE WHERE coupon_code != '$coupon_code' AND expiration_date > '$current_date'");

	return $rows;
}

add_filter( 'wc_appointments_product_get_available_slots', 'reserve_slot' ,10,5);
function reserve_slot( $available_slots, $this, $slots, $intervals, $staff_id)
{		

	$user_id = get_current_user_id();

    $coupon = get_coupon_code($user_id);
    $rows = filter_reservation_table($coupon);

	
	foreach($rows as $row)
	{	
		if((($key = array_search(strtotime($row->start_date),$slots))!==false) && $staff_id == $row->staff_id)
		{
			unset($slots[$key]);
		}
	}

	return $slots;
}