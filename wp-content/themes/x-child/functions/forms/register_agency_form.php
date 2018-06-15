<?php

// The 1 corresponds to the ID of the Register Agency Form
add_filter( 'gform_validation_1', 'register_agency_custom_validation' );

function register_agency_custom_validation( $validation_result ) {
    // Fields from register form (* indicates required field)
    // input_1: Customer Name *
    // input_2: Customer Email *
    // input_4: Coupon quantity *
    // input_5: Coupon code

    $form = $validation_result['form'];

    if ( username_exists( rgpost( 'input_1' ) ) ) {
        // mark_field_invalid comes from forms.php
        mark_field_invalid('1', 'This customer name is already in use.', $form);
    }

    if ( email_exists( rgpost( 'input_2' ) ) ) {
        mark_field_invalid('2', 'This email address is already in use.', $form);
    }

    if ( rgpost( 'input_5' ) ) {
        $coupon_code = rgpost( 'input_5' );
        // Make sure Coupon code is at least four characters
        if ( strlen( $coupon_code ) < 4 ) {
            mark_field_invalid('5', 'Please enter a coupon code of at least four characters.', $form);
        }
        // Make sure Coupon code isn't already in use
        else {
            global $wpdb;
            $table_name = $wpdb->prefix . 'agency_coupons';
            $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE coupon_code='$coupon_code'" );
            if ($results) {
                mark_field_invalid('5', 'This coupon code is already in use.', $form);
            }
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

function generate_coupon_code() {
    $seed = str_split('abcdefghijklmnopqrstuvwxyz'
                     .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                     .'0123456789'); // and any other characters
    shuffle($seed); // probably optional since array_is randomized; this may be redundant
    $rand = '';
    foreach (array_rand($seed, 5) as $k) $rand .= $seed[$k];
    return $rand;
}

add_action( 'gform_after_submission_1', 'register_agency_create_user' );

function register_agency_create_user( $entry, $form ) {
    global $wpdb;
    $AGENCY_COUPONS_TABLE_NAME = $wpdb->prefix . 'agency_coupons';
    $AGENCY_COUPON_BATCH_TABLE_NAME = $wpdb->prefix . 'agency_coupon_batch';

    $customer_name = rgar( $entry, '1' );
    $customer_email = rgar( $entry, '2' );
    $coupon_quantity = rgar( $entry, '4' );
    $coupon_code = rgar( $entry, '5' );
    // One year          = 365 days
    // Half a year       = 183 days
    // Quarter of a year = 92 days
    $expiry_period = rgar( $entry, '7' );
    $password = wp_generate_password();

    // Available fields here: https://codex.wordpress.org/Function_Reference/wp_insert_user
    $userdata = array(
        'user_login'  =>  $customer_name,
        'user_email'  =>  $customer_email,
        'user_pass'   =>  $password,
        'role'        =>  'agency'
    );

    $agency_id = wp_insert_user( $userdata );

    if (is_wp_error($agency_id)) {
        send_debugging_email($agency_id);
        return;
    }

    // Generate coupon code
    if (!$coupon_code) {
        $coupon_code = generate_coupon_code();
    }

    // Enter coupon information for new agency
    $wpdb->insert($AGENCY_COUPONS_TABLE_NAME, array(
        'agency_id'           => $agency_id,
        'coupon_code'         => $coupon_code,
    ));

    $wpdb->insert($AGENCY_COUPON_BATCH_TABLE_NAME, array(
        'coupon_code'         => $coupon_code,
        'amount_available'    => $coupon_quantity,
        'initial_amount'      => $coupon_quantity,
        'days_valid'          => $expiry_period,
    ));

    wp_new_user_notification($agency_id, null, 'user');
}
