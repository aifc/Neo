<?php

/* Override to allow the username to have underscores */
function custom_bp_core_validate_user_signup( $user_name, $user_email ) {

  // Make sure illegal names include BuddyPress slugs and values.
  bp_core_flush_illegal_names();

  // WordPress Multisite has its own validation. Use it, so that we
  // properly mirror restrictions on username, etc.
  if ( function_exists( 'wpmu_validate_user_signup' ) ) {
    $result = wpmu_validate_user_signup( $user_name, $user_email );

  // When not running Multisite, we perform our own validation. What
  // follows reproduces much of the logic of wpmu_validate_user_signup(),
  // minus the multisite-specific restrictions on user_login.
  } else {
    $errors = new WP_Error();

    /**
     * Filters the username before being validated.
     *
     * @since 1.5.5
     *
     * @param string $user_name Username to validate.
     */
    $user_name = apply_filters( 'pre_user_login', $user_name );

    // User name can't be empty.
    if ( empty( $user_name ) ) {
      $errors->add( 'user_name', __( 'Please enter a username', 'buddypress' ) );
    }

    // User name can't be on the blacklist.
    $illegal_names = get_site_option( 'illegal_names' );
    if ( in_array( $user_name, (array) $illegal_names ) ) {
      $errors->add( 'user_name', __( 'That username is not allowed', 'buddypress' ) );
    }

    // User name must pass WP's validity check.
    if ( ! validate_username( $user_name ) ) {
      $errors->add( 'user_name', __( 'Usernames can contain only letters, numbers, ., -, and @', 'buddypress' ) );
    }

    // Minimum of 4 characters.
    if ( strlen( $user_name ) < 4 ) {
      $errors->add( 'user_name',  __( 'Username must be at least 4 characters', 'buddypress' ) );
    }

    // No underscores. @todo Why not?
    /*
    if ( false !== strpos( ' ' . $user_name, '_' ) ) {
      $errors->add( 'user_name', __( 'Sorry, usernames may not contain the character "_"!', 'buddypress' ) );
    }
    */

    // No usernames that are all numeric. @todo Why?
    $match = array();
    preg_match( '/[0-9]*/', $user_name, $match );
    if ( $match[0] == $user_name ) {
      $errors->add( 'user_name', __( 'Sorry, usernames must have letters too!', 'buddypress' ) );
    }

    // Check into signups.
    $signups = BP_Signup::get( array(
      'user_login' => $user_name,
    ) );

    $signup = isset( $signups['signups'] ) && ! empty( $signups['signups'][0] ) ? $signups['signups'][0] : false;

    // Check if the username has been used already.
    if ( username_exists( $user_name ) || ! empty( $signup ) ) {
      $errors->add( 'user_name', __( 'Sorry, that username already exists!', 'buddypress' ) );
    }

    // Validate the email address and process the validation results into
    // error messages.
    $validate_email = bp_core_validate_email_address( $user_email );
    bp_core_add_validation_error_messages( $errors, $validate_email );

    // Assemble the return array.
    $result = array(
      'user_name'  => $user_name,
      'user_email' => $user_email,
      'errors'     => $errors,
    );

    // Apply WPMU legacy filter.
    $result = apply_filters( 'wpmu_validate_user_signup', $result );
  }

  /**
   * Filters the result of the user signup validation.
   * @since 1.2.2
   *
   * @param array $result Results of user validation including errors, if any.
   */
  return apply_filters( 'bp_core_validate_user_signup', $result );
}

function registration_check_email_confirm(){ 
global $bp;

//if first email field is not empty we check the second one
if (!empty( $_POST['signup_email'] ) ){
  //first field not empty and second field empty
  if(empty( $_POST['signup_email_confirm'] ))
    $bp->signup->errors['signup_email'] = 'Please make sure you enter your email twice';
  //both fields not empty but differents
  elseif($_POST['signup_email'] != $_POST['signup_email_confirm'] )
    $bp->signup->errors['signup_email'] = 'The emails you entered do not match.';
} } add_action('bp_signup_validate', 'registration_check_email_confirm');

/**
   * Store registration type in user meta 
*/

remove_action( 'bp_screens', 'bp_core_screen_signup' ,99); //make sure this hook is fired after bp-core function is added
function custom_bp_core_screen_signup() {
  $bp = buddypress();

  if ( ! bp_is_current_component( 'register' ) || bp_current_action() )
    return;

  // Not a directory.
  bp_update_is_directory( false, 'register' );

  // If the user is logged in, redirect away from here.
  if ( is_user_logged_in() ) {

    $redirect_to = bp_is_component_front_page( 'register' )
      ? bp_get_members_directory_permalink()
      : bp_get_root_domain();

    /**
     * Filters the URL to redirect logged in users to when visiting registration page.
     *
     * @since 1.5.1
     *
     * @param string $redirect_to URL to redirect user to.
     */
    bp_core_redirect( apply_filters( 'bp_loggedin_register_page_redirect_to', $redirect_to ) );

    return;
  }

  $bp->signup->step = 'request-details';

  if ( !bp_get_signup_allowed() ) {
    $bp->signup->step = 'registration-disabled';

    // If the signup page is submitted, validate and save.
  } elseif ( isset( $_POST['signup_submit'] ) && bp_verify_nonce_request( 'bp_new_signup' ) ) {

    // Set username to email 
    if (!empty($_POST['signup_email']))
    {
      $_POST['signup_username'] = $_POST['signup_email'];
    }

    /**
     * Fires before the validation of a new signup.
     *
     * @since 2.0.0
     */
    do_action( 'bp_signup_pre_validate' );

    // Check the base account details for problems.
    $account_details = custom_bp_core_validate_user_signup( $_POST['signup_username'], $_POST['signup_email'] );
    
    // If there are errors with account details, set them for display.
    if ( !empty( $account_details['errors']->errors['user_name'] ) )
      $bp->signup->errors['signup_username'] = $account_details['errors']->errors['user_name'][0];

    if ( !empty( $account_details['errors']->errors['user_email'] ) )
      $bp->signup->errors['signup_email'] = $account_details['errors']->errors['user_email'][0];
   
    // Check that both password fields are filled in.
    if ( empty( $_POST['signup_password'] ) || empty( $_POST['signup_password_confirm'] ) )
      $bp->signup->errors['signup_password'] = __( 'Please make sure you enter your password twice', 'buddypress' );

    // Check that the passwords match.
    if ( ( !empty( $_POST['signup_password'] ) && !empty( $_POST['signup_password_confirm'] ) ) && $_POST['signup_password'] != $_POST['signup_password_confirm'] )
      $bp->signup->errors['signup_password'] = __( 'The passwords you entered do not match.', 'buddypress' );

    $bp->signup->username = $_POST['signup_username'];
    $bp->signup->email = $_POST['signup_email'];

    // Now we've checked account details, we can check profile information.
    if ( bp_is_active( 'xprofile' ) ) {

      // Make sure hidden field is passed and populated.
      if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {

        // Let's compact any profile field info into an array.
        $profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

        // Loop through the posted fields formatting any datebox values then validate the field.
        foreach ( (array) $profile_field_ids as $field_id ) {
          if ( !isset( $_POST['field_' . $field_id] ) ) {
            if ( !empty( $_POST['field_' . $field_id . '_day'] ) && !empty( $_POST['field_' . $field_id . '_month'] ) && !empty( $_POST['field_' . $field_id . '_year'] ) )
              $_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] ) );
          }

          // Create errors for required fields without values.
          if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST[ 'field_' . $field_id ] ) && ! bp_current_user_can( 'bp_moderate' ) )
            $bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
        }

        // This situation doesn't naturally occur so bounce to website root.
      } else {
        bp_core_redirect( bp_get_root_domain() );
      }
    }

    // Finally, let's check the blog details, if the user wants a blog and blog creation is enabled.
    if ( isset( $_POST['signup_with_blog'] ) ) {
      $active_signup = bp_core_get_root_option( 'registration' );

      if ( 'blog' == $active_signup || 'all' == $active_signup ) {
        $blog_details = bp_core_validate_blog_signup( $_POST['signup_blog_url'], $_POST['signup_blog_title'] );

        // If there are errors with blog details, set them for display.
        if ( !empty( $blog_details['errors']->errors['blogname'] ) )
          $bp->signup->errors['signup_blog_url'] = $blog_details['errors']->errors['blogname'][0];

        if ( !empty( $blog_details['errors']->errors['blog_title'] ) )
          $bp->signup->errors['signup_blog_title'] = $blog_details['errors']->errors['blog_title'][0];
      }
    }

    /**
     * Fires after the validation of a new signup.
     *
     * @since 1.1.0
     */
    do_action( 'bp_signup_validate' );
    
    // Add any errors to the action for the field in the template for display.
    if ( !empty( $bp->signup->errors ) ) {
      foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
        /*
         * The addslashes() and stripslashes() used to avoid create_function()
         * syntax errors when the $error_message contains quotes.
         */

        /**
         * Filters the error message in the loop.
         *
         * @since 1.5.0
         *
         * @param string $value Error message wrapped in html.
         */
        add_action( 'bp_' . $fieldname . '_errors', create_function( '', 'echo apply_filters(\'bp_members_signup_error_message\', "<div class=\"error\">" . stripslashes( \'' . addslashes( $error_message ) . '\' ) . "</div>" );' ) );
      }
    } else {
      $bp->signup->step = 'save-details';

      // No errors! Let's register those deets.
      $active_signup = bp_core_get_root_option( 'registration' );

      if ( 'none' != $active_signup ) {

        // Make sure the extended profiles module is enabled.
        if ( bp_is_active( 'xprofile' ) ) {
          // Let's compact any profile field info into usermeta.
          $profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

          // Loop through the posted fields formatting any datebox values then add to usermeta - @todo This logic should be shared with the same in xprofile_screen_edit_profile().
          foreach ( (array) $profile_field_ids as $field_id ) {
            if ( ! isset( $_POST['field_' . $field_id] ) ) {

              if ( ! empty( $_POST['field_' . $field_id . '_day'] ) && ! empty( $_POST['field_' . $field_id . '_month'] ) && ! empty( $_POST['field_' . $field_id . '_year'] ) ) {
                // Concatenate the values.
                $date_value = $_POST['field_' . $field_id . '_day'] . ' ' . $_POST['field_' . $field_id . '_month'] . ' ' . $_POST['field_' . $field_id . '_year'];

                // Turn the concatenated value into a timestamp.
                $_POST['field_' . $field_id] = date( 'Y-m-d H:i:s', strtotime( $date_value ) );
              }
            }

            if ( !empty( $_POST['field_' . $field_id] ) )
              $usermeta['field_' . $field_id] = $_POST['field_' . $field_id];

            if ( !empty( $_POST['field_' . $field_id . '_visibility'] ) )
              $usermeta['field_' . $field_id . '_visibility'] = $_POST['field_' . $field_id . '_visibility'];
          }

          // Store the profile field ID's in usermeta.
          $usermeta['profile_field_ids'] = $_POST['signup_profile_field_ids'];
        }

        // Hash and store the password.
        $usermeta['password'] = wp_hash_password( $_POST['signup_password'] );

        // If the user decided to create a blog, save those details to usermeta.
        if ( 'blog' == $active_signup || 'all' == $active_signup )
          $usermeta['public'] = ( isset( $_POST['signup_blog_privacy'] ) && 'public' == $_POST['signup_blog_privacy'] ) ? true : false;

        /**
         * Filters the user meta used for signup.
         *
         * @since 1.1.0
         *
         * @param array $usermeta Array of user meta to add to signup.
         */
        $usermeta = apply_filters( 'bp_signup_usermeta', $usermeta );

        // Finally, sign up the user and/or blog.
        if ( isset( $_POST['signup_with_blog'] ) && is_multisite() )
          $wp_user_id = bp_core_signup_blog( $blog_details['domain'], $blog_details['path'], $blog_details['blog_title'], $_POST['signup_username'], $_POST['signup_email'], $usermeta );
        else
          $wp_user_id = bp_core_signup_user( $_POST['signup_username'], $_POST['signup_password'], $_POST['signup_email'], $usermeta );

        if ( is_wp_error( $wp_user_id ) ) {

          $bp->signup->step = 'request-details';
          bp_core_add_message( $wp_user_id->get_error_message(), 'error' );
        } else {
          $bp->signup->step = 'completed-confirmation';
        }
      }

      add_user_meta($wp_user_id, 'registration_type', $_POST['signup_usertype']);
      $wp_session = WP_Session::get_instance();
      $assessment_results = ! empty( $wp_session['results'] ) ? $wp_session['results'] : false; //get assessment_results if any

      if(!empty($assessment_results))
        update_user_meta($wp_user_id, 'assessment_result', $_POST['signup_usertype']);
      /**
       * Fires after the completion of a new signup.
       *
       * @since 1.1.0
       */
      do_action( 'bp_complete_signup', $wp_user_id );
    }

  }

  /**
   * Fires right before the loading of the Member registration screen template file.
   *
   * @since 1.5.0
   */
  do_action( 'custom_bp_core_screen_signup' );

  /**
   * Filters the template to load for the Member registration page screen.
   *
   * @since 1.5.0
   *
   * @param string $value Path to the Member registration template to load.
   */
  bp_core_load_template( apply_filters( 'bp_core_template_register', array( 'register', 'registration/register' ) ) );
}
add_action( 'bp_screens', 'custom_bp_core_screen_signup');


/**
   * Give suppliers supplier role after account activation
 */
function add_user_role($user_id)
{ 
  global $wpdb;

  $all_meta_for_user = get_user_meta($user_id);
  $registraton_type = get_user_meta( $user_id, 'registration_type', true); 

  if ($registraton_type == "client")
  {
    // Give user cleint role
    $u = new WP_User($user_id);
    $u->remove_role('subscriber');
    $u->add_role('client');
  }
  else if ($registraton_type == "counselor")
  {
    // Give user shop staff role
    $u = new WP_User($user_id);
    $u->remove_role('subscriber');
    $u->add_role('shop_staff');
    custom_bp_registration_options_notify_pending_user($user_id);
  }
  //else if ($registraton_type == "student") coming soon
}
add_action('bp_core_activated_user', 'add_user_role', 10, 3); // On account activation instead of approval
add_action('bp_core_activated_user','send_client_account_details');
function send_client_account_details($user_id) 
{
  $user = get_userdata($user_id);
  //email client with login details
  $subject = "[Neo] Account Details";

  $heading = "Account Details";
  
  $message = '<h3>Thank you for signing up</h3><p>Here are your account details: </p><p>Username: '.$user->user_email.'</p><p>If you lose your password go <a href="'.wp_lostpassword_url().'">here</a></p>';

  send_email_woocommerce_style($user->user_email, $subject, $heading, $message);  //this function is declared in functions/woocommerce.php
  
}
/**
 * Emails user about pending status upon activation.
 *
 * @since 4.3.0
 *
 * @param int    $user_id ID of the user being checked.
 * @param string $key     Activation key.
 * @param array  $user    Array of user data.
 */
function custom_bp_registration_options_notify_pending_user( $user_id) {

  $user_info = get_userdata( $user_id );
  $pending_message = get_option( 'bprwg_user_pending_message' );
  $filtered_message = str_replace( '[username]', $user_info->data->user_login, $pending_message );
  $filtered_message = str_replace( '[user_email]', $user_info->data->user_email, $filtered_message );
  
  /**
   * Filters the message to be sent to user upon activation.
   *
   * @since 4.3.0
   *
   * @param string  $filtered_message Message to be sent with placeholders changed.
   * @param string  $pending_message  Original message before placeholders filtered.
   * @param WP_User $user_info        WP_User object for the newly activated user.
   */
  $filtered_message = apply_filters( 'bprwg_pending_user_activation_email_message', $filtered_message, $pending_message, $user_info );
  bp_registration_options_send_pending_user_email(
    array(
      'user_login' => $user_info->data->user_login,
      'user_email' => $user_info->data->user_email,
      'message'    => $filtered_message,
    )
  );
}
remove_action( 'bp_core_activated_user', 'bp_registration_options_notify_pending_user', 11, 3 );


// Only require admin approval for certain accounts 

function requires_moderation($user_id, $moderate)
{
  // If not supplier and government email then the account does not require moderation
  $user = get_userdata( $user_id );
  $usertype = get_user_meta( $user_id, 'registration_type', true);
  if ((string)$usertype == "client")
  {
    $moderate = 0;
  }
  return $moderate;
}

function custom_bp_registration_options_bp_core_register_account($user_id) {
  
  $moderate = get_option( 'bprwg_moderate' );
  $moderate = requires_moderation($user_id, $moderate);
  if ( $moderate && $user_id > 0 ) {

    bp_registration_set_moderation_status( $user_id );

    $user = get_userdata( $user_id );

    /** This filter is documented in includes/core.php */
    //$admin_email = apply_filters( 'bprwg_admin_email_addresses', array( get_bloginfo( 'admin_email' ) ) );
    // Used for BP Notifications.
    $admins = get_users( 'role=administrator' );

    // Add HTML capabilities temporarily.
    add_filter( 'wp_mail_content_type', 'bp_registration_options_set_content_type' );

    // Set them as in moderation.
    bp_registration_set_moderation_status( $user_id );

    /**
     * Filters the SERVER global reported remote address.
     *
     * @since 4.3.0
     *
     * @param string $value IP Address of the user being registered.
     */
    update_user_meta( $user_id, '_bprwg_ip_address', apply_filters( '_bprwg_ip_address', $_SERVER['REMOTE_ADDR'] ) );

    // Admin email.
    $message = get_option( 'bprwg_admin_pending_message' );
    $message = str_replace( '[username]', $user->data->user_login, $message );
    $message = str_replace( '[user_email]', $user->data->user_email, $message );

    bp_registration_options_send_admin_email(
      array(
        'user_login' => $user->data->user_login,
        'user_email' => $user->data->user_email,
        'message'    => $message,
      )
    );

    bp_registration_options_delete_user_count_transient();

    // Set admin notification for new member.
    $enable_notifications = (bool) get_option( 'bprwg_enable_notifications' );
    if ( bp_is_active( 'notifications' ) && $enable_notifications ) {
      foreach ( $admins as $admin ) {
        bp_notifications_add_notification( array(
          'user_id'          => $admin->ID,
          'component_name'   => 'bp_registration_options',
          'component_action' => 'bp_registration_options',
          'allow_duplicate'  => true,
        ) );
      }
    }
  }
}
remove_action( 'user_register', 'bp_registration_options_bp_core_register_account' );
add_action( 'bp_complete_signup', 'custom_bp_registration_options_bp_core_register_account', 10, 1 );

function restrict_nonapproved_login($user, $username, $password)
{
  $user_data = $user->data;
  $approved = get_user_meta( $user_data->ID, '_bprwg_is_moderated', true );
  if ($approved == 'true')
  {

    $user = new WP_Error('error', 'Your account is pending approval. You will be notified when your account is approved by an administrator.');
    return $user;
  }
  return $user;
}
add_filter('authenticate', 'restrict_nonapproved_login', 100, 3);


function bp_autologin_on_activation( $user_id, $key, $user ) {
  
  $role = get_user_meta($user_id,'registration_type', true );
  if ( is_admin() ||!($role === 'client')) {
    return;
  }

  $bp = buddypress();
  $hashed_key = wp_hash( $user_id );
  bp_core_add_message( __( 'Your account is now active!', 'buddypress' ) );
  $bp->activation_complete = true;

    //now login and redirect
  wp_set_auth_cookie( $user_id, true, false );
  bp_core_redirect( get_site_url().'/counselling-agreement/');
}
add_action( 'bp_core_activated_user', 'bp_autologin_on_activation', 40, 3 );

add_action('bpro_hook_approved_user','create_counsellor_product_woocommerce');
function create_counsellor_product_woocommerce($user_id)
{
  global $wpdb;

  $APPOINTMENT_RELATIONSHIP_TABLE = $wpdb->prefix . 'wc_appointment_relationships';

  $post_id = wp_insert_post( array(
    'post_title' => 'Appointment with '.bp_core_get_user_displayname($user_id),
    'post_content' => '',
    'post_status' => 'publish',
    'post_type' => "product",
  ) );
  wp_set_object_terms( $post_id, 'appointment', 'product_type' );
  update_post_meta( $post_id, '_visibility', 'visible' );
  update_post_meta( $post_id, '_stock_status', 'instock');
  update_post_meta( $post_id, '_product_addons', array());
  update_post_meta( $post_id, '_product_addons_exclude_global', '0');
  update_post_meta( $post_id, 'total_sales', '0' );
  update_post_meta( $post_id, '_downloadable', 'no' );
  update_post_meta( $post_id, '_virtual', 'yes' );
  update_post_meta( $post_id, '_regular_price', 30 );
  update_post_meta( $post_id, '_sale_price', '' );
  update_post_meta( $post_id, '_purchase_note', '' );
  update_post_meta( $post_id, '_featured', 'no' );
  update_post_meta( $post_id, '_weight', '' );
  update_post_meta( $post_id, '_length', '' );
  update_post_meta( $post_id, '_width', '' );
  update_post_meta( $post_id, '_height', '' );
  update_post_meta( $post_id, '_sku', '' );
  update_post_meta( $post_id, '_product_attributes', array() );
  update_post_meta( $post_id, '_sale_price_dates_from', '' );
  update_post_meta( $post_id, '_sale_price_dates_to', '' );
  update_post_meta( $post_id, '_price', 30 );
  update_post_meta( $post_id, '_sold_individually', '' );
  update_post_meta( $post_id, '_manage_stock', 'no' );
  update_post_meta( $post_id, '_backorders', 'no' );
  update_post_meta( $post_id, '_stock', '' );
  update_post_meta( $post_id, '_wc_appointment_qty', '1' );
  update_post_meta( $post_id, '_wc_appointment_qty_min', '1' );
  update_post_meta( $post_id, '_wc_appointment_qty_max', '1' );
  update_post_meta( $post_id, '_wc_appointment_staff_assignment', 'automatic' );
  update_post_meta( $post_id, '_wc_appointment_duration', '1' );
  update_post_meta( $post_id, '_wc_appointment_duration_unit', 'hour' );
  update_post_meta( $post_id, '_wc_appointment_interval', '1' );
  update_post_meta( $post_id, '_wc_appointment_interval_unit', 'hour' );
  update_post_meta( $post_id, '_wc_appointment_padding_duration', '' );
  update_post_meta( $post_id, '_wc_appointment_padding_duration_unit', 'minute' );
  update_post_meta( $post_id, '_wc_appointment_min_date', '5' );
  update_post_meta( $post_id, '_wc_appointment_min_date_unit', 'day' );
  update_post_meta( $post_id, '_wc_appointment_max_date', '12' );
  update_post_meta( $post_id, '_wc_appointment_max_date_unit', 'month' );
  update_post_meta( $post_id, '_wc_appointment_user_can_cancel', 'yes' );
  update_post_meta( $post_id, '_wc_appointment_cancel_limit', '1' );
  update_post_meta( $post_id, '_wc_appointment_cancel_limit_unit', 'day' );
  update_post_meta( $post_id, '_wc_appointment_cal_color', '#0073aa' );
  update_post_meta( $post_id, '_wc_appointment_requires_confirmation', 'no' );
  update_post_meta( $post_id, '_wc_appointment_availability_span', '' );
  update_post_meta( $post_id, '_wc_appointment_availability_autoselect', 'no' );
  update_post_meta( $post_id, '_wc_appointment_staff_label', '' );
  update_post_meta( $post_id, '_wc_appointment_availability', array() );
  update_post_meta( $post_id, '_wc_appointment_pricing', array());
  update_post_meta( $post_id, '_has_additional_costs', 'no' );
  update_post_meta( $post_id, '_product_image_gallery', '' );
  update_post_meta( $post_id, '_wc_review_count', '0' );
  update_post_meta( $post_id, '_wc_rating_count', array());
  update_post_meta( $post_id, '_staff_base_costs', array($user_id =>''));
  update_post_meta( $post_id, '_wc_average_rating', '0' );
  update_post_meta( $post_id, '_product_version', '2.6.14' );
  update_post_meta( $post_id, '_tax_status', 'taxable');
  update_post_meta( $post_id, '_tax_class', '');
  update_post_meta( $post_id, '_upsell_ids', array());
  update_post_meta( $post_id, '_crosssell_ids', array());
  update_post_meta( $post_id, '_default_attributes', array());
  update_post_meta( $post_id, '_download_limit', '-1');
  update_post_meta( $post_id, '_download_expiry', '-1');
  update_post_meta( $post_id, '_wc_appointment_has_price_label', '');
  update_post_meta( $post_id, '_wc_appointment_price_label', '');
  update_post_meta( $post_id, '_wc_appointment_has_pricing', '');

  xprofile_set_field_data(43, $user_id ,$post_id); //sets the calendar ID for the User

  $wpdb->insert($APPOINTMENT_RELATIONSHIP_TABLE, array(
            'product_id'     => $post_id,
            'staff_id'        => $user_id,
            'sort_order'        => 0,
        ));

  $addon_fields = array();
  $fields = xprofile_get_field_data(89,$user_id);

    foreach ($fields as $field) {
      if($field == 'Group Sessions')
        $addon_fields[] = array('label'=>$field, 'price'=>10,'min'=>'','max'=>'','duration'=>30);
      else
        $addon_fields[] = array('label'=>$field, 'price'=>'','min'=>'','max'=>'','duration'=>'');
    }
  $addons = array ( 
            array( 'name' => 'Session Type', 'description' =>'', 'type' => 'radiobutton', 'position' => '0', 'options' => $addon_fields, 
            'required' => 1, 'wc_appointment_hide_duration_label' => 0, 'wc_appointment_hide_price_label' => 0 ) 
          );

  update_post_meta($post_id,'_product_addons', $addons);
  
  //create the same post as a draft for counselors to edit on, 
  make_post_revision($post_id);  
  
}

function make_post_revision($post_id) 
{
  global $wpdb;
  $post = get_post( $post_id );

  $args = array(
      'comment_status' => $post->comment_status,
      'ping_status'    => $post->ping_status,
      'post_content'   => $post->post_content,
      'post_excerpt'   => $post->post_excerpt,
      'post_name'      => $post->post_name,
      'post_parent'    => $post_id,
      'post_password'  => $post->post_password,
      'post_status'    => 'draft',
      'post_title'     => $post->post_title,
      'post_type'      => $post->post_type,
      'to_ping'        => $post->to_ping,
      'menu_order'     => $post->menu_order
    );
 
  $new_post_id = wp_insert_post($args);
 
  $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
  foreach ($taxonomies as $taxonomy) {
    $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
  }

  $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
  if (count($post_meta_infos)!=0) {
    $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
    foreach ($post_meta_infos as $meta_info) {
      $meta_key = $meta_info->meta_key;
      if( $meta_key == '_wp_old_slug' ) continue;
      $meta_value = addslashes($meta_info->meta_value);
      $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
    }
    $sql_query.= implode(" UNION ALL ", $sql_query_sel);
    $wpdb->query($sql_query);
  }
  update_post_meta( $new_post_id, '_post_revision', '1');
  update_post_meta( $new_post_id, '_post_revision_of', $post_id);

}

add_action( 'xprofile_updated_profile','change_woocommerce_product_on_profile_update',1 ,5); //use this to control price if aifc wants counsellors to manage pricing
function change_woocommerce_product_on_profile_update($user_id, $posted_field_ids, $errors, $old_values, $new_values)
{
  if ( empty( $errors ) ) {

    $delivery_mechanisms = $new_values[89]['value'];
    $woocommerce_product = xprofile_get_field_data(43, $user_id);
    write_debug($woocommerce_product);
    $addon_fields = array();
    foreach ($delivery_mechanisms as $delivery_mechanism) {
      if($delivery_mechanism == 'Group Sessions')
        $addon_fields[] = array('label'=>$delivery_mechanism, 'price'=>10,'min'=>'','max'=>'','duration'=>30);
      else
        $addon_fields[] = array('label'=>$delivery_mechanism, 'price'=>'','min'=>'','max'=>'','duration'=>'');
    }
    
    $addons = array ( 
            array( 'name' => 'Session Type', 'description' =>'', 'type' => 'radiobutton', 'position' => '0', 'options' => $addon_fields, 
            'required' => 1, 'wc_appointment_hide_duration_label' => 0, 'wc_appointment_hide_price_label' => 0 ) 
          );

    update_post_meta($woocommerce_product,'_product_addons', $addons);
  }
}

//change default avatar
define ( 'BP_AVATAR_DEFAULT', "http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/PastedGraphic-1_ColourUpdated.png" );
