<?php

    add_filter('gform_validation_6', 'matching_field_validation', 10, 2);
    function matching_field_validation($validation_result){
     
        $form = $validation_result['form'];
        $email = rgpost('input_10');
        if(email_exists($email))
        {
        	$form['fields'][0]['failed_validation'] = true;
            $form['fields'][0]['validation_message'] = 'Sorry, that email address is already used!';
            $validation_result['is_valid'] = false;
        }
        // Check into signups.
        $signups = BP_Signup::get( array('user_login' => $email,) );
        $signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

        // Check if the username has been used already.
        if ( ! empty( $signup ) ) {
            $form['fields'][0]['failed_validation'] = true;
            $form['fields'][0]['validation_message'] = 'Sorry, that username already exists!';
            $validation_result['is_valid'] = false;
        }

        if (rgpost('input_5') != rgpost('input_6')) {
            // this is the validation error for input_1
            $form['fields'][2]['failed_validation'] = true;
            $form['fields'][2]['validation_message'] = 'The passwords you entered do not match.';
            $validation_result['is_valid'] = false;
        }
   
        // update the form in the validation result with the form object you modified
        $validation_result['form'] = $form;
        return $validation_result;
    }

    add_action('gform_after_submission_6', 'counselor_signup', 10, 2);
    function counselor_signup($entry, $form ){
        $bp = buddypress();
        $email = rgpost('input_10');
        if(!empty($email))
        {
            $user_name = $email;
        }
       
    }