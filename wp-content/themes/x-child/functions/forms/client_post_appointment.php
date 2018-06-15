<?php

add_action( 'gform_after_submission_12', 'client_post_appointment_feedback' ,10,2);

function client_post_appointment_feedback( $entry, $form ) {
    
    global $wpdb;

    $APPOINTMENT_FEEDBACK = $wpdb->prefix . 'appointment_feedback';

	$user = wp_get_current_user();
	$user_id = $user->id;

	$feedback = rgar( $entry, '4' );
	$appointment_rating = rgar( $entry, '5' );
	$counsellor_rating = rgar( $entry, '6' );
	$appointment_id = rgar( $entry, '8' );
	$counselor_id = rgar( $entry, '9' );

    $wpdb->insert($APPOINTMENT_FEEDBACK, array(
        'appointment_id'    => $appointment_id,
        'feedback'       	=> $feedback,
        'appointment_rating'=> $appointment_rating,
        'counsellor_rating' => $counsellor_rating,
        'client_id'      	=> $user_id,
        'counselor_id'      => $counselor_id,
    ));
}

add_filter( 'gform_field_value_cnote_counselor_id', 'cnote_counselor_id_func' );
function cnote_counselor_id_func( $value ) {

	$appointment_id = get_query_var('i');
	$appointment = get_wc_appointment($appointment_id);
	$appointment_staff_id = $appointment->custom_fields['_appointment_staff_id'][0];
    return $appointment_staff_id;
}