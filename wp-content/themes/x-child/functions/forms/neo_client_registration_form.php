<?php

add_filter( 'gform_validation_3', 'neo_registration_custom_validation' );

function neo_registration_custom_validation( $validation_result ) {
    $form = $validation_result['form'];

    $read_statement = rgpost( 'input_2_1' );
    $understand_rights = rgpost( 'input_2_2' );

    if ( !( $read_statement && $understand_rights ) ) {
        mark_field_invalid('2', 'You must check these boxes to continue.', $form);
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

add_action( 'gform_after_submission_3', 'client_neo_counselling_agreement' );

function client_neo_counselling_agreement( $entry, $form ) {

    $client_information = array(
        'read_statement'    => rgar( $entry, '2.1' ),
        'understand_rights' => rgar( $entry, '2.2' ),
        'date_of_birth'     => rgar( $entry, '6' ),
        'gender'            => rgar( $entry, '7' ),
        'street_address'    => rgar( $entry, '5.1' ),
        'city'              => rgar( $entry, '5.3' ),
        'state'             => rgar( $entry, '5.4' ),
        'postal_code'       => rgar( $entry, '5.5' ),
        'country'           => rgar( $entry, '5.6' ),
        'referral'          => rgar( $entry, '8' ),
        'phone'             => rgar( $entry, '12' )
    );

    $user_id = get_current_user_id();

    foreach ($client_information as $key => $value) {
        if ($value) {
            update_user_meta( $user_id, $key, $value ); // Will overwrite old meta values
        }
    }
}
