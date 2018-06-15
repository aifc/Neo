<?php

add_action( 'gform_after_submission_14', 'update_user_profile' );

function update_user_profile( $entry, $form ) {

    $client_information = array(
        'name'              => rgar( $entry, '1' ),
        'date_of_birth'     => rgar( $entry, '2' ),
        'gender'            => rgar( $entry, '3' ),
        'phone'             => rgar( $entry, '5' ),
        'street_address'    => rgar( $entry, '6.1' ),
        'city'              => rgar( $entry, '6.3' ),
        'state'             => rgar( $entry, '6.4' ),
        'postal_code'       => rgar( $entry, '6.5' ),
        'country'           => rgar( $entry, '6.6' )
    );

    $user_id = get_current_user_id();

    foreach ($client_information as $key => $value) {
        if ($value) {
            update_user_meta( $user_id, $key, $value ); // Will overwrite old meta values
        }
    }
    xprofile_set_field_data('Name',  $user_id,  $client_information['name']); 
}
add_filter('gform_field_value_profile_edit_name', 'value_profile_edit_name');
add_filter('gform_field_value_profile_edit_dob', 'value_profile_edit_dob');
add_filter('gform_field_value_profile_edit_gender', 'value_profile_edit_gender');
add_filter('gform_field_value_profile_edit_phonenumber', 'value_profile_edit_phonenumber');

add_filter('gform_field_value_profile_edit_streetaddress', 'value_profile_edit_streetaddress');
add_filter('gform_field_value_profile_edit_city', 'value_profile_edit_city');
add_filter('gform_field_value_profile_edit_province', 'value_profile_edit_province');
add_filter('gform_field_value_profile_edit_zip', 'value_profile_edit_zip');
add_filter('gform_field_value_profile_edit_country', 'value_profile_edit_country');

function value_profile_edit_name($value) {
    $user_id = get_current_user_id();
    return xprofile_get_field_data('Name',$user_id );
}
function value_profile_edit_dob($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'date_of_birth',true);
}
function value_profile_edit_gender($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'gender',true);
}
function value_profile_edit_phonenumber($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'phone',true);
}
function value_profile_edit_streetaddress($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'street_address',true);
}
function value_profile_edit_city($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'city',true);
}
function value_profile_edit_province($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'state',true);
}
function value_profile_edit_zip($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'postal_code',true);
}
function value_profile_edit_country($value) {
    $user_id = get_current_user_id();
    return get_user_meta($user_id,'country',true);
}