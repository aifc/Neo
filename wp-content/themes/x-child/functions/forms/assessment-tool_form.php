<?php

add_action( 'gform_after_submission_8', 'assessment_result' ,10,2);

function assessment_result($entry, $form )
{
    $page2 = array();
    $page3 = array();
    $page4 = array();

    $gender = rgar( $entry, '4' );
    
    // //page 2
    $page2[] = rgar( $entry, '6' );
    $page2[] = rgar( $entry, '9' );
    $page2[] = rgar( $entry, '10' );
    $page2[] = rgar( $entry, '12' );

    //page 3
    $page3[] = rgar( $entry, '7' );
    $page3[] = rgar( $entry, '14' );
    $page3[] = rgar( $entry, '16' );
    $page3[] = rgar( $entry, '17' );

    //page 4
    $page4[] = rgar( $entry, '8' );
    $page4[] = rgar( $entry, '19' );
    $page4[] = rgar( $entry, '20' );
    $page4[] = rgar( $entry, '21' );

    
    
    $total_score = array_sum($page2) + array_sum($page3) + array_sum($page4);
    if($total_score > 32)
        $results = 'Your Result is High';
    else if ($total_score > 16)
        $results = 'Your Result is Medium';
    else
        $results = 'Your Result is Low';
    
    $wp_session = WP_Session::get_instance(); 
    $wp_session["results"] = $results;
    //if(user is not logged in redirect to signup and add details into sessions)
    if(!is_user_logged_in())
    {   
        wp_redirect('/register/');
        exit();
    }
    else
    {
        update_user_meta(get_current_user_id(), 'assessment_result', $results);
    }
}

add_filter( 'gform_confirmation_8', 'assessment_result_confirmation', 10, 4 );

function assessment_result_confirmation( $confirmation, $form, $entry, $ajax ) {

    $gender = rgar( $entry, '4' );
    
    // //page 2
    $page2[] = rgar( $entry, '6' );
    $page2[] = rgar( $entry, '9' );
    $page2[] = rgar( $entry, '10' );
    $page2[] = rgar( $entry, '12' );

    //page 3
    $page3[] = rgar( $entry, '7' );
    $page3[] = rgar( $entry, '14' );
    $page3[] = rgar( $entry, '16' );
    $page3[] = rgar( $entry, '17' );

    //page 4
    $page4[] = rgar( $entry, '8' );
    $page4[] = rgar( $entry, '19' );
    $page4[] = rgar( $entry, '20' );
    $page4[] = rgar( $entry, '21' );

    $total_score = array_sum($page2) + array_sum($page3) + array_sum($page4);

    if($total_score > 32)
        $results = 'Your Result is High';
    else if ($total_score > 16)
        $results = 'Your Result is Medium';
    else
        $results = 'Your Result is Low';

    $confirmation = '<h3 class="my-headings">'.$results.'</h3>';
    $confirmation .= '<p class = "paragraph-uncontained">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus at tincidunt erat. Morbi nec posuere ex, at suscipit justo. Donec ut tincidunt turpis. Mauris cursus, ante ut consequat tempus, quam risus porta ante, quis scelerisque mauris felis ac augue.</p>';
    $confirmation .= '<p class = "paragraph-uncontained">Click <a href="'.site_url('/members/').'">here</a> to start booking an appointment with a counselor</p>';
    return $confirmation;
}

