<?php


add_action( 'gform_after_submission_9', 'submit_test_call', 10, 2 );

function submit_test_call($entry, $form )
{
	global $wpdb;

    $TEST_CALL_TABLE = $wpdb->prefix . 'test_call';

	$user = wp_get_current_user();
	$user_id = $user->id;

	$video_quality = rgar( $entry, '5' );
	$audio_quality = rgar( $entry, '6' );
	$feedback = rgar( $entry, '4' );

    $wpdb->insert($TEST_CALL_TABLE, array(
        'video_quality'       => $video_quality,
        'audio_quality'       => $audio_quality,
        'feedback'      	  => $feedback,
        'test_date'           => time(),
        'user_id'             => $user_id,
    ));
}
