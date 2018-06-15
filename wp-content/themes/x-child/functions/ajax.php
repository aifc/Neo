<?php

add_action( 'wp_ajax_search_directory', 'search_directory' );
add_action( 'wp_ajax_nopriv_search_directory', 'search_directory' );

function search_directory( ) {
    send_debugging_email( $_POST['data'] );
    wp_die();
}
