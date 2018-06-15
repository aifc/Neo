<?php
add_action( 'gform_after_submission_13', 'counselor_save_notes' ,10,2);

function counselor_save_notes( $entry, $form ) {
    
    global $wpdb;

    $NOTES_COUNSELOR_TABLE = $wpdb->prefix . 'notes_counselor';

	$user = wp_get_current_user();
	$user_id = $user->id;

	$note = rgar( $entry, '4' );
	$appointment_id = rgar( $entry, '8' );
	$client_id = rgar( $entry, '9' );

    $wpdb->insert($NOTES_COUNSELOR_TABLE, array(
        'appointment_id'    => $appointment_id,
        'note'       		=> $note,
        'note_date'     	=> time(),
        'client_id'         => $client_id,
        'counselor_id'      => $user_id,
    ));
}

add_filter( 'gform_field_value_cnote_client_id', 'cnote_client_id_func' );
function cnote_client_id_func( $value ) {

	$appointment_id = get_query_var('i');
	$appointment = get_wc_appointment($appointment_id);
	$customer_id = $appointment->customer_id;
    return $customer_id;
}