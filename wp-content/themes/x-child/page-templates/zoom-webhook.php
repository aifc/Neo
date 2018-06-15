<?php
/**
 * Template Name: Zoom-webhook Template
 *
 * @package WordPress
 */
?>
<?php
global $wpdb;
$COUNSELOR_LOGBOOK_TABLE = $wpdb->prefix . 'counselor_logbook';
if( isset($_POST["status"]) )
{
	switch( $_POST["status"] )
	{
		case "JBH":
			//write_webhook_deets($_POST["id"]."JBH meeting id");
		break;

		case "JOIN":
			//write_webhook_deets($_POST["id"]."JOIN meeting id");
		break;

		case "STARTED":

		break;

		case "ENDED":
			
			$id = $_POST["id"];
			$appointment_id = get_post_id_by_meta_key_and_value('_meeting_id',$id); //function defined in //functions.php

			$appointment = get_wc_appointment($appointment_id);
			if(empty($appointment))
			{
				break; //exit if appointment is empty
			}
			$customer_id = $appointment->customer_id;
			$appointment_type = $appointment->custom_fields['appointment_type'][0];
			$appointment_staff_id = $appointment->custom_fields['_appointment_staff_id'][0];
			$client_email = get_userdata($customer_id)->user_email;
			$couselor_email = get_userdata($appointment_staff_id)->user_email;

			$link = generate_post_appointment_link('client',$appointment_id);
		 	$subject = "[Neo] Post Appointment";

            $heading = "Post Appointment feedback";

            $message = '<p style="text-align:center;">Please rate your experience with the meeting and provide any further feedback <a href="'.$link.'">here</a></p>';
            $message .= '<p style="text-align:center;>You can also share files with your counsellor on through the link</p>';
        	
			send_email_woocommerce_style($client_email, $subject, $heading, $message); //defined in functions/woocommerce.php // client email
			
			///change values for counselor email
			$link = generate_post_appointment_link('counselor',$appointment_id);
//debugging write_webhook_deets($link);
			$message = '<p style="text-align:center;">Click <a href="'.$link.'">here</a> to save notes on the meeting</p>';
            $message .= '<p style="text-align:center;>You can also share files with your client on through the link</p>';
			send_email_woocommerce_style($couselor_email, $subject, $heading, $message); //defined in functions/woocommerce.php // counsellor email
		

			$successful = $wpdb->insert($COUNSELOR_LOGBOOK_TABLE, array(
	            'session_type'     => $appointment_type,
	            'client_name'      => xprofile_get_field_data( 'Name', $customer_id ),
	            'date_start'       => $appointment->start,
	            'date_end'         => $appointment->end,
	            'counselor_id'     => $appointment_staff_id,
	            'meeting_id'       => $id

        	));
		break;

		case "RECORDING_MEETING_COMPLETED":
			//write_webhook_deets($_POST["id"]."RECORDING_MEETING_COMPLETED meeting id");
		break;
	}
}
function generate_post_appointment_link($role, $appointment_id)
{
	$url = site_url('/post-appointment/');
	$data = array('r' => $role, 'i' => $appointment_id);

	$result = esc_url(add_query_arg( $data, $url ));
	return $result;
}

function write_webhook_deets($deets)
{
	$myfile = fopen("webhooktest.txt", "a") or die("Unable to open file!");
	$txt = print_r( $deets, true )."\n";
	fwrite($myfile, $txt);
	fclose($myfile);
}
