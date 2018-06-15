<?php

add_action( 'gform_after_submission_10', 'group_session_declined_submit' );

function group_session_declined_submit( $entry, $form ) {

	global $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

	$message = rgar( $entry, '15' );
	$key = rgar( $entry, '16' );

	$member = $wpdb->get_results($wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE key_code = %s", $key)); // field already validated from the response page so no need to

	$appointment = get_wc_appointment($member[0]->appointment_id);
	$client = $appointment->get_customer();
    $user_data = get_userdata($client->user_id);
    
	$data = array( 'status' => 'declined');

	$where = array('key_code'=>$key);

	$updated = $wpdb->update( $CLIENT_GROUP_SESSIONS_TABLE, $data, $where );

	if ( false === $updated ) {
	    write_debug(var_dump( $wpdb->last_query ) );
	} 

	if(!empty($message)) //send email to organizer
	{
		$subject = "[Neo] Invitation declined";
        $heading = "Invitation has been declined";
        $message = '<p>Your Inviation has been declined by '.$member[0]->email.' details are stated below:</p><br>'.$message;
        send_email_woocommerce_style($user_data->user_email, $subject, $heading, $message); //this function is defined inside functions/woocommerce.php
	}
	else
	{
		$subject = "[Neo] Invitation declined";
        $heading = "Invitation has been declined";
        $message = '<p>Your Inviation has been declined by '.$member[0]->email;
        send_email_woocommerce_style($user_data->user_email, $subject, $heading, $message); //this function is defined inside functions/woocommerce.php
	}
}
