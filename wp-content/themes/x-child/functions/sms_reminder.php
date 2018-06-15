<?php

// for testing
$username = 'mikko1243';//smsbroadcast.com.au username
$password = 'Mikko.012';//smsbroadcast.com.au password

// $username = 'SIHQ';//smsbroadcast.com.au username
// $password = 'onlinepsychology';//smsbroadcast.com.au password

function schedule_sms_reminder($appointmentId) {

  $appointment = get_wc_appointment( $appointmentId );
  $start_time = $appointment->start;
  $order_status = $appointment->post->post_status;
  $now = strtotime( 'midnight', current_time( 'timestamp' ) );
  
  if ( ! in_array( $order_status, array( 'cancelled', 'refunded', 'pending', 'on-hold' ) ) && ( $start_time > $now ) )
  {  //NOTE subtract 11 hours to match the time with GMT
     wp_schedule_single_event(strtotime( '-1 day - 11 hours', $start_time ), 'sms_reminder', array($appointmentId) );
     wp_schedule_single_event(strtotime('-14 hours', $start_time ), 'sms_reminder_counselor', array($appointmentId) );
 
  }
  else //checks if status is cancelled 
  {
    wp_clear_scheduled_hook( 'sms_reminder', array( $appointmentId ) );
    wp_clear_scheduled_hook( 'sms_reminder_counselor', array( $appointmentId ) );
  }
}

add_action ('sms_reminder', 'send_sms_reminder'); // hook that function onto our scheduled event:
add_action ('sms_reminder_counselor', 'send_sms_reminder_counselor');
function send_sms_reminder_counselor($appointmentId)
{
	$appointment = get_wc_appointment($appointmentId);
  	$order_status = $appointment->post->post_status;
  	
  	if(! empty($appointment) && ! in_array( $order_status, array( 'cancelled', 'refunded', 'pending', 'on-hold','trash' ) ))
  	{
    	
    	global $username, $password;

	    $start_time = $appointment->start; 

	    $destination = xprofile_get_field_data(99,$appointment->staff_id[0]); //Multiple numbers can be entered, separated by a comma
	    $source    = 'Neo'; //Company Name
	    $text = 'Reminder: Your appointment with '. get_user_meta($appointment->customer_id, 'first_name', true).' '.get_user_meta($appointment->customer_id, 'last_name', true) .' is on '.gmdate("j F", $start_time).' at '.gmdate("g:i a", $start_time);
	        
	    $content =  'username='.rawurlencode($username).
	                '&password='.rawurlencode($password).
	                '&to='.rawurlencode($destination).
	                '&from='.rawurlencode($source).
	                '&message='.rawurlencode($text);
	    // write_log('https://api.smsbroadcast.com.au/api-adv.php?'.$content);
	    $smsbroadcast_response = sendSMS($content);
	    $response_lines = explode("\n", $smsbroadcast_response);
	    foreach( $response_lines as $data_line){
	      $message_data = "";
	      $message_data = explode(':',$data_line);
	      if($message_data[0] == "OK"){
	          //write_log( "Counselor: The message to ".$message_data[1]." was successful, with reference ".$message_data[2]."\n");
	      }elseif( $message_data[0] == "BAD" ){
	          //write_log("Counselor: The message to ".$message_data[1]." was NOT successful. Reason: ".$message_data[2]."\n");
	      }elseif( $message_data[0] == "ERROR" ){
	          //write_log("Counselor: There was an error with this request. Reason: ".$message_data[1]."\n");
	      }
	    }
  	} 
}
function send_sms_reminder($appointmentId) {

  $appointment = get_wc_appointment($appointmentId);
  $order_status = $appointment->post->post_status;
  
  if(! empty($appointment) && ! in_array( $order_status, array( 'cancelled', 'refunded', 'pending', 'on-hold','trash' ) ))
  {
    global $username, $password, $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

    $appointment_title = get_the_title($appointment->product_id);
    $start_time = $appointment->start; 
    $appointment_type = $appointment->custom_fields['appointment_type'][0];

    if($appointment_type=='Group Session')
    {
      $results = $wpdb->get_results( $wpdb->prepare("SELECT email FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE appointment_id = %d AND status = 'accepted'", $appointmentId) );
      $group_session_contact = array();
      foreach($results as $result)
      {
        $group_session_contact[] = get_user_meta(get_user_by( 'email', $result->email )->id, 'phone', true);
      }
      $group_session_contact[] = get_user_meta($appointment->customer_id, 'phone', true); //include the organizer
      $destination = implode(',', $group_session_contact);
    }
    else //not a group session
    {
      $destination = get_user_meta($appointment->customer_id, 'phone', true); //Multiple numbers can be entered, separated by a comma
    }

    
    $source    = 'Neo'; //Company Name
    $text = 'Reminder: Your '.$appointment_title.' is on '.gmdate("j F", $start_time).' at '.gmdate("g:i a", $start_time);
        
    $content =  'username='.rawurlencode($username).
                '&password='.rawurlencode($password).
                '&to='.rawurlencode($destination).
                '&from='.rawurlencode($source).
                '&message='.rawurlencode($text);
    // write_log('https://api.smsbroadcast.com.au/api-adv.php?'.$content);
    $smsbroadcast_response = sendSMS($content);
    $response_lines = explode("\n", $smsbroadcast_response);
    foreach( $response_lines as $data_line){
      $message_data = "";
      $message_data = explode(':',$data_line);
      if($message_data[0] == "OK"){
          //write_log( "Client: The message to ".$message_data[1]." was successful, with reference ".$message_data[2]."\n");
      }elseif( $message_data[0] == "BAD" ){
          //write_log("Client: The message to ".$message_data[1]." was NOT successful. Reason: ".$message_data[2]."\n");
      }elseif( $message_data[0] == "ERROR" ){
          //write_log("Client: There was an error with this request. Reason: ".$message_data[1]."\n");
      }
    }
  }  
}

function sendSMS($content) { //from the api documentation https://www.smsbroadcast.com.au/
        $ch = curl_init('https://www.smsbroadcast.com.au/api-adv.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec ($ch);
        curl_close ($ch);
        return $output;    
}
