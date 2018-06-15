<?php

add_filter( 'gform_validation_4', 'client_registration_custom_validation' );

function client_registration_custom_validation( $validation_result ) {
    global $wpdb;
    $form = $validation_result['form'];
    $CLIENT_INFORMATION_TABLE = $wpdb->prefix . 'client_information';

    $email = rgpost( 'input_1' );

    if ( !email_exists( $email ) ) {
        mark_field_invalid('1', 'No bookings have been made with this email.
            Please <a class="bold" href="/make-booking/">make a booking</a> with this email to continue.', $form);
    }
    else {
        $user = get_user_by('email', $email);
        if ( !in_array( 'client', (array)$user->roles ) ) {
            mark_field_invalid('1', 'Your account is not of the right type. Please make a booking with a different email to continue.
                <a class="bold" href="/make-booking/">Make booking.</a>', $form);
        }
        else {
            $result = $wpdb->get_row( "SELECT * FROM $CLIENT_INFORMATION_TABLE WHERE username='$email'" );
            if ( !wp_check_password( $result->initial_password, $user->data->user_pass, $user->ID) )
                mark_field_invalid('1', 'You have already setup your account. Please login with the form below', $form);
        }
    }

    foreach( $form['fields'] as $field ) {
        if ($field->failed_validation == true) {
            $validation_result['is_valid'] = false;
            break;
        }
    }

    $validation_result['form'] = $form;
    return $validation_result;
}

add_action( 'gform_after_submission_4', 'client_registration_form' );

function client_registration_form( $entry, $form ) {
    $email = rgar( $entry, '1' );
    $user = get_user_by('email', $email);

    wp_new_user_notification( $user->ID, null, 'user' );
}
