<?php

add_filter( 'gform_field_value_group_member_email', 'group_member_email_func' );
function group_member_email_func( $value ) {
    global $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';
    $key = $_GET["key"];

    $member = $wpdb->get_results($wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE key_code = %s", $key));
    return $member[0]->email;
}

add_filter('gform_pre_render_2', 'add_readonly_script');
function add_readonly_script($form) {
    
    ?>
        <script type='text/javascript'>
            jQuery(document).ready(function(){
                jQuery('.bookingEmail input').attr('readonly', 'readonly');
            });
        </script>
    <?php
  
    return $form;
}

add_filter( 'gform_validation_2', 'client_booking_custom_validation' );

function client_booking_custom_validation( $validation_result ) {
    // Fields from register form (* indicates required field)
    // input_2: Email Address *
    // input_4_3: First Name *
    // input_4_6: Last Name *
    // input_5: Choose a Password *
    // input_6: Confirm Password *

    global $wpdb;
    $form = $validation_result['form'];

    $email = rgpost( 'input_2' );
    $fname = rgpost( 'input_4_3' );
    $lname = rgpost( 'input_4_6' );
    $pass = rgpost( 'input_5' );
    $confirm_pass = rgpost( 'input_6' );

    if ($pass != $confirm_pass) {
        // this is the validation error for input_1
        $form['fields'][3]['failed_validation'] = true;
        $form['fields'][3]['validation_message'] = 'The passwords you entered do not match.';
        $validation_result['is_valid'] = false;
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

add_action( 'gform_after_submission_2', 'client_enter_coupon_code' ,10,2);

function client_enter_coupon_code( $entry, $form ) {
    
    global $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';
    $key = $_GET["key"];

    $member = $wpdb->get_results($wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE key_code = %s", $key));
    $appointment = get_wc_appointment($member[0]->appointment_id);
    $client = $appointment->get_customer();
    $user_data = get_userdata($client->user_id);
    
    $password = rgar( $entry, '5' );
    $client_email = rgpost( 'input_2' );
    $fname = rgpost( 'input_4_3' );
    $lname = rgpost( 'input_4_6' );

    $userdata = array(
        'user_login'  =>  $client_email,
        'user_email'  =>  $client_email,
        'user_pass'   =>  $password,
        'role'        =>  'client'
    );

    $client_id = wp_insert_user( $userdata );

    update_user_meta($client_id,'first_name',$fname);
    update_user_meta($client_id,'last_name',$lname);
    xprofile_set_field_data( 'Name', $client_id,$fname.' '.$lname);


    $data = array( 'status' => 'accepted');
    $where = array('key_code'=>$key);
    $updated = $wpdb->update( $CLIENT_GROUP_SESSIONS_TABLE, $data, $where ); //update the status to accepted

    $subject = "[Neo] Invitation Accepted";
    $heading = "Invitation has been declined";
    $message = '<p>Your Inviation has been Accepted by '.$member[0]->email;
    send_email_woocommerce_style($user_data->user_email, $subject, $heading, $message); //this function is defined inside functions/woocommerce.php

    //now login and redirect

    wp_set_auth_cookie($client_id, true, false );
    bp_core_redirect( get_site_url().'/counselling-agreement/');
}