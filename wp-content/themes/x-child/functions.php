<?php

// =============================================================================
// FUNCTIONS.PHP
// -----------------------------------------------------------------------------
// Overwrite or add your own custom functions to X in this file.
// =============================================================================

// =============================================================================
// TABLE OF CONTENTS
// -----------------------------------------------------------------------------
//   01. Enqueue Parent Stylesheet
//   02. Additional Functions
// =============================================================================

// Enqueue Parent Stylesheet
// =============================================================================

// print_r('hello world');

add_filter( 'x_enqueue_parent_stylesheet', '__return_true' );

// Additional Functions
// More functions inside wp-content/plugins/bp-custom.php
// =============================================================================

require_once( __DIR__ . '/functions/zoom.php');
require_once( __DIR__ . '/functions/shortcodes.php');
require_once( __DIR__ . '/functions/ajax.php');
require_once( __DIR__ . '/functions/woocommerce.php');
//require_once( __DIR__ . '/functions/reservation.php');
require_once( __DIR__ . '/functions/forms/forms.php');
//require_once( __DIR__ . '/functions/forms/register_agency_form.php');
require_once( __DIR__ . '/functions/forms/group_session_registration.php');
require_once( __DIR__ . '/functions/forms/neo_client_registration_form.php');
require_once( __DIR__ . '/functions/forms/counselor_registration_form.php');
require_once( __DIR__ . '/functions/forms/client_registration_form.php');
//require_once( __DIR__ . '/functions/forms/top_up_coupon.php');
require_once( __DIR__ . '/functions/forms/assessment-tool_form.php');
require_once( __DIR__ . '/functions/forms/group_session_declined.php');
require_once( __DIR__ . '/functions/forms/reschedule_form.php');
require_once( __DIR__ . '/functions/forms/test_call_form.php');
require_once( __DIR__ . '/functions/forms/counselor_notes.php');
require_once( __DIR__ . '/functions/forms/client_post_appointment.php');
require_once( __DIR__ . '/functions/sms_reminder.php');
require_once( __DIR__ . '/functions/forms/client_edit_profile.php');
require_once( __DIR__ . '/functions/slot_blocking.php');

$wp_session = WP_Session::get_instance(); //don't remove this it keeps session alive for assessment results
add_filter( 'auto_update_plugin', '__return_false' );

add_action( 'admin_enqueue_scripts', 'load_psychologist_backend_styles' );

function load_psychologist_backend_styles() {
    $user = wp_get_current_user();
    if ( in_array( 'shop_staff', (array) $user->roles ) ) {
        wp_enqueue_style( 'hide-backend', '/wp-content/themes/x-child/shop-staff.css' );
    }
}

function send_debugging_email( $var ) {
    wp_mail( 'mikko@pursuittechnology.com.au', 'Debugging', json_encode($var) );
}

add_action('admin_enqueue_scripts', 'supplier_backend_styles');
function supplier_backend_styles() {
  $user = wp_get_current_user();
  if (in_array( 'shop_staff', (array)$user->roles)) {
    wp_enqueue_style( 'shop_staff_styles', '/wp-content/themes/x-child/styles/shop_staff_styles.css' );
  }  
}

add_filter('gettext', 'translate_text_product'); 
add_filter('ngettext', 'translate_text_product');

// Translate the word `Product` to `Availability`
function translate_text_product($translated) { 
    $translated = str_ireplace('Product', 'Availability', $translated);
    return $translated;
}

//add script for modal popup
add_action('admin_head', 'modal_script');
add_action('wp_head', 'modal_script');
function modal_script() {
  if(!is_admin())
  {
    echo '<script type="text/javascript" src="/wp-content/themes/x-child/functions/custom_sticky_footer.js"></script>';
  }
  
  if(is_front_page())
    echo '<script type="text/javascript" src="/wp-content/themes/x-child/functions/home_page_script.js"></script>';
  ?>
    <script type="text/javascript">
      function resizeIframe(obj){
         obj.style.height = 0;
         obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
      }
    </script>
  <?php
}


function write_debug ( $log )  {
  if ( is_array( $log ) || is_object( $log ) ) {
     error_log( print_r( $log, true ) ,3,'wp-content/errors.log');
  } else {
     error_log( $log ,3,'wp-content/errors.log');
  }
}

//////////////////////////////////////////////

function custom_is_counselor()
{
  $current_user = wp_get_current_user();
  return in_array("shop_staff", $current_user->roles);
}

function custom_is_client()
{
  $current_user = wp_get_current_user();
  return in_array("client", $current_user->roles);
}

function my_login_redirect( $redirect_to, $request, $user ) {
  return home_url().'/home/';
}

add_filter( 'login_redirect', 'my_login_redirect', 10, 3 );

add_filter('bxcft_images_ext_allowed', 'my_images_ext_allowed');
function my_images_ext_allowed($array_images_ext_allowed) {
   return array('png', 'gif','jpg','jpeg');
}

function sessions_functions() {
  $wp_session = WP_Session::get_instance();
  $assessment_results = ! empty( $wp_session['results'] ) ? $wp_session['results'] : false; //get assessment_results if any
  if(!empty($assessment_results))
    add_user_meta($wp_user_id, 'assessment_result', $_POST['signup_usertype']);
}
add_action('wp_login', 'sessions_functions');

add_action('page-blurb-top','custom_headings');
function custom_headings()
{
  if(is_page('self-assessment'))
  {
    echo '<h3 class = "header-uncontained">HELP US FIND YOUR PERFECT COUNSELLOR</h3>';
    echo '<p class = "paragraph-uncontained">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>';
  }
  else if(is_page('members'))
  {
    echo '<h3 class="my-headings">SEARCH FOR A COUNSELOR</h3>';
    echo '<p class = "paragraph-uncontained">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>';
  }
  else if(is_page('test-call'))
  {
    echo '<h3 class="my-headings">Zoom Test Call</h3>';
    echo '<p class = "paragraph-uncontained">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>';
  }
}

add_action('bp_before_members_loop','counselor_filter');
function counselor_filter() {
  do_shortcode('[bps_display form=258]');
}

add_action('pre_post_update','notify_the_admin'); //email admins if counselor request to change their availability
function notify_the_admin($post_ID){
  $user = wp_get_current_user();
  if ( in_array( 'shop_staff', (array) $user->roles ) ) {
    $blogusers = get_users('role=Administrator');
  
    foreach ($blogusers as $admin) {
        $subject = "[Neo] Counselor Availability Request";

        $heading = "Counselor avialability request";

        $message = '<p>'.$user->user_firstname.' '.$user->user_lastname.' requested to change their availability</p>';
        $message .='<p>Click <a href="'.get_edit_post_link( $post_ID, 'showampasnormal' ).'">here</a> to view request</p>';
        send_email_woocommerce_style($admin->user_email, $subject, $heading, $message); 
    }  
  }
} 

function get_post_id_by_meta_key_and_value($key, $value) { //used in page-templates/zoom-webhook.php
  global $wpdb;
  $meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
  if (is_array($meta) && !empty($meta) && isset($meta[0])) {
    $meta = $meta[0];
  }   
  if (is_object($meta)) {
    return $meta->post_id;
  }
  else {
    return false;
  }
}

function add_custom_query_var( $vars )
{
    $vars[] = "r";
    $vars[] = "i";
    return $vars;
}
add_filter( 'query_vars', 'add_custom_query_var' );

add_action('wp_ajax_csv_pull','export_log_book_csv');
//$ajax_url = admin_url('admin-ajax.php?action=csv_pull');
//http://mychristiancounsellor.org.au/wp-admin/admin-ajax.php?action=csv_pull
function export_log_book_csv()
{
    global $wpdb;

    $user = wp_get_current_user(); 
    $user_id = $user->ID;
    $COUNSELOR_LOGBOOK_TABLE = $wpdb->prefix . 'counselor_logbook';

    if (in_array( 'shop_staff', (array)$user->roles)) {
      $counselor = true;
    }

    if(!$counselor) return false;

    $results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $COUNSELOR_LOGBOOK_TABLE WHERE counselor_id = %d", $user_id),ARRAY_A );

    if (empty($results)) {
      return;
    }

    ob_start();
    $file = 'log_book';
    $filename = $file."_".date("Y-m-d_H-i",time()).".csv";
    
    $header_row = array(
        'Session Type',
        'Client Name',
        'Date/Time Start',
        'Date/Time End',
    );
    $data_rows = array();
    
    foreach ( $results as $result ) {

      $row = array(
          $result['session_type'],
          $result['client_name'],
          date('H:i, j-m-Y',$result['date_start']),
          date('H:i, j-m-Y',$result['date_end'])
      );
      $data_rows[] = $row;
    }
  
    $fh = @fopen( 'php://output', 'w' );
    fprintf( $fh, chr(0xEF) . chr(0xBB) . chr(0xBF) );
    header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
    header( 'Content-Description: File Transfer' );
    header( 'Content-type: text/csv' );
    header( "Content-Disposition: attachment; filename={$filename}" );
    header( 'Expires: 0' );
    header( 'Pragma: public' );
    fputcsv( $fh, $header_row );
    foreach ( $data_rows as $data_row ) {
        fputcsv( $fh, $data_row );
    }
    fclose( $fh );
    
    ob_end_flush();
    
    die();
}

add_filter( 'woocommerce_appointments_emails_ics', 'attach_ics_to_email_types' );
function attach_ics_to_email_types( $email_types ) {

     $email_types[] = 'new_appointment';
     $email_types[] = 'staff_appointment_reminder';
     return $email_types;
}

add_filter('if_menu_conditions', 'if_menu_extended_conditions');
 
function if_menu_extended_conditions( $conditions ) {
  $conditions[] = array(
    'name'    =>  'Is home page',
    'condition' =>  function($item) {         
      return is_page('home');
    }
  );
 
  return $conditions;
}

add_action('template_redirect','redirect_home');

function redirect_home() {
  if(!is_user_logged_in() && is_page('home'))
  {
    wp_redirect('/');
    exit;
  }
}
add_filter( 'gform_disable_notification', 'disable_notification', 10, 4 );
function disable_notification( $is_disabled, $notification, $form, $entry ) {
    return true;
}

add_action( 'login_head', 'add_button_user_login' );

function add_button_user_login() {
  ?>
  <script>
    jQuery(function(){
      if(jQuery("p.submit #wp-submit").val() =="Log In" ) {
        jQuery( "p.submit" ).after( '<p id="orman">OR</p><button onclick="window.location.href=\'wp-counselor-login.php\'" type="button" id="signin-counselor" class="button button-primary button-large">Click here to go to Counselor log in</button></form>' );
      }      
    });
    
  </script>
  <style>
    p#orman {
        display: block;
        font-size: 20px;
        margin-top: 40px;
        text-align: center;
        font-weight: 300;
        color: #72777c;
    }
    button#signin-counselor {
        width: 100%;
        height: 40px;
        margin: 10px 0 0 0;
    }
  </style>
  <?
}
add_action( 'counselor_login_head', 'add_button_counselor_login' );

function add_button_counselor_login() {
  ?>
  <script>
    jQuery(function(){
        jQuery( "p.submit" ).after( '<p id="orman">OR</p><button onclick="window.location.href=\'wp-login.php\'" type="button" id="signin-counselor" class="button button-primary button-large">Click here to go to Client log in</button></form>' );
    });
  </script>
  <style type="text/css">                                                                                   
      body.login div#login h1 a {
        background-image: url("http://mychristiancounsellor.org.au/wp-content/uploads/2017/10/aifc-logo.png");
        height: 90px;
        width: 100%;
        background-size: 240px;
      }
      p#orman {
          display: block;
          font-size: 20px;
          margin-top: 40px;
          text-align: center;
          font-weight: 300;
          color: #72777c;
      }
      button#signin-counselor {
          width: 100%;
          height: 40px;
          margin: 10px 0 0 0;
      }
      body {
          background: linear-gradient(to right, #0f0c29, #bebae4,#0f0c29) !important;
      }
     
    </style>
    <?
}

add_filter( 'counselor_login_message', 'counselor_login_message');

function counselor_login_message($message) {
  return "<p style='text-align:center'>LOG IN TO YOUR COUNSELOR ACCOUNT</p>";
}
add_filter( 'login_message', 'login_message');

function login_message($message) {
  return "<p style='text-align:center'>LOG IN TO YOUR ACCOUNT</p>";
}

function wpb_sender_email( $original_email_address ) {
    return 'noreply@mychristiancounsellor.org.au';
}
 
function wpb_sender_name( $original_email_from ) {
    return 'My Christian Counsellor';
}
 
add_filter( 'wp_mail_from', 'wpb_sender_email' );
add_filter( 'wp_mail_from_name', 'wpb_sender_name' );

add_action('bp_before_register_page','password_strength_js');

function password_strength_js() {
  wp_enqueue_script( 'password-strength-meter' );
  wp_enqueue_script( 'password-strength-meter-mediator', get_stylesheet_directory_uri() . '/functions/password-strength-meter-mediator.js', array('password-strength-meter'));  
}

// Allow Non logged in users to access the members page but not individual profiles


add_action('template_redirect','redirect_access_to_individual_profile');

function redirect_access_to_individual_profile() {
    
  $path = explode('/', $_SERVER['REQUEST_URI'] );

  if(!is_user_logged_in() && $path[1]=='members' && !empty($path[2]) ) {

    auth_redirect();
    exit;
  
  }

}


//var_dump($wp_session);
// $order = new WC_Order( 1446 );
// add_action('init','do_this');
// function do_this()
// {
//   //$order = wc_get_order( 1446 );
//   $appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id(1446 );
//     //$order_items = $order->get_items();
//   foreach ( $appointments as $item) {
//     var_dump( get_wc_appointment($item)->id);
//   }
   
//     //write_debug(var_dump($order));
    
// }


// function bpfr_hide_profile_edit( $retval ) {  
//   // remove field from edit tab
//   if(  bp_is_profile_edit() ) {   
//     $retval['exclude_fields'] = '54'; // ID's separated by comma
//   } 
//   // allow field on registration page     
//   if ( bp_is_register_page() ) {
//     $retval['include_fields'] = '54'; // ID's separated by comma
//     }   
  
//   // hide the filed on profile view tab
//   if ( $data = bp_get_profile_field_data( 'field=54' ) ) : 
//     $retval['exclude_fields'] = '54'; // ID's separated by comma  
//   endif;  
  
//   return $retval; 
// }
// add_filter( 'bp_after_has_profile_parse_args', 'bpfr_hide_profile_edit' ); //this snippet sets field to non editable 