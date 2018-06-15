<?php

# A way to remove stuff from woocommerce account menu
add_filter( 'woocommerce_account_menu_items', 'custom_woocommerce_account_menu_items' );

function custom_woocommerce_account_menu_items( $items ) {
    if ( (isset( $items['orders'] )) && (isset( $items['downloads'])) ) 
    {
        unset( $items['orders']); 
        unset( $items['downloads']);
        unset( $items['dashboard']);
        unset( $items['edit-address']);
        unset( $items['customer-logout']);
        return $items;
    }
}

add_action('init', 'avf_move_product_output');

function avf_move_product_output() {
    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
    add_action( 'woocommerce_product_thumbnails', 'woocommerce_template_single_excerpt', 5 );  
}

// Remove fields from woocommerce checkout {CHECKOUT}
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );
 
function custom_override_checkout_fields( $fields ) {
    unset($fields['billing']['billing_first_name']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_1']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_phone']);
    unset($fields['order']['order_comments']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_email']);
    unset($fields['billing']['billing_city']);
    unset($fields['account']['account_username']);
    unset($fields['account']['account_email']);

    $fields['extra_fields'] = array(
            'members_attending' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter number of other attendees' ),
                'placeholder' => 'Enter a number from 1 to 5'
                ),
            'email_field_1' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter attendee email address' ),
                'class' => array('group_sesh_field')
                ),
            'email_field_2' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter attendee email address' ),
                'class' => array('group_sesh_field')
                ),
            'email_field_3' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter attendee email address' ),
                'class' => array('group_sesh_field')
                ),
            'email_field_4' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter attendee email address' ),
                'class' => array('group_sesh_field')
                ),
            'email_field_5' => array(
                'type' => 'text',
                'required'      => false,
                'label' => __( 'Enter attendee email address' ),
                'class' => array('group_sesh_field')
                )
            );
    return $fields;
}

// display the extra field on the checkout form {CHECKOUT}
function display_group_session_field(){ 
    $checkout = WC()->checkout(); ?>

    <div class="extra-fields">
    <p><strong><?php _e( 'Group session fields' ); ?></strong></p>

    <?php 
    // because of this foreach, everything added to the array in the previous function will display automagically
    foreach ( $checkout->checkout_fields['extra_fields'] as $key => $field ) : ?>

            <?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>

        <?php endforeach; ?>
    </div>

<?php }
add_action( 'woocommerce_checkout_after_customer_details' ,'display_group_session_field' );

add_action('woocommerce_checkout_process', 'validate_group_session_email'); //validate if email provided on checkout is valid

function validate_group_session_email() { 
    $emails = array();
    $number = (int)$_POST['members_attending'];
    for($count = 1; $count <= $number; $count++)
    {   
        if(isset($_POST['email_field_'.$count]) && !empty($_POST['email_field_'.$count]))
            $emails[] = $_POST['email_field_'.$count];
    }
    foreach($emails as $email )
    {
        if(!is_email($email))
        {
            wc_add_notice( __( 'One of the email address you entered is in incorrect format' ), 'error' );
            break;
        }
    }
}
//save the extra field when checkout is processed {CHECKOUT}
function save_extra_checkout_fields( $order, $data ){
    
    if(isset($data['members_attending']) && !empty($data['members_attending']))
    {   
        $emails = array();
        $number = (int)$data['members_attending'];
        for($count = 1; $count <= $number; $count++)
        {
            if(isset($_POST['email_field_'.$count]) && !empty($_POST['email_field_'.$count]))
                $emails[] = sanitize_email($_POST['email_field_'.$count]);
        }
        
        $order->update_meta_data( '_group_session_emails', $emails );
    }
}
add_action( 'woocommerce_checkout_create_order', 'save_extra_checkout_fields', 10, 2 );

// Customise 'Place Order' button text on checkout page {CHECKOUT}
add_filter( 'woocommerce_order_button_text', 'woo_custom_order_button_text' ); 

function woo_custom_order_button_text() {
    
    return __( 'Book Appointment', 'woocommerce' ); 
}

// Customise checkout page {CHECKOUT}
function customise_checkout_page() {
    echo '<h4 class="appointment-confirmation-header">Please confirm the details of your appointment:</h4>';
    echo '<script type="text/javascript" src="/wp-content/themes/x-child/functions/checkout.js"></script>';
}
 
add_action( 'woocommerce_before_checkout_form', 'customise_checkout_page');

// Remove all previous items from cart when adding new item to cart
add_filter( 'woocommerce_add_cart_item_data', 'woo_custom_add_to_cart' );

function woo_custom_add_to_cart( $cart_item_data ) {

    global $woocommerce;
    // Clear the cart
    $cart_contents = $woocommerce->cart->get_cart();
    foreach ($cart_contents as $key => $value) { // Assign the current element's key to the $key variable on each iteration.
        $woocommerce->cart->remove_cart_item($key);
    }

    // Do nothing with the data and return
    return $cart_item_data;
}

function checkout_process($order_id) {
    
    global $wpdb;
    
    $WOOCOMMERCE_ORDER_ITEMMETA = $wpdb->prefix . 'woocommerce_order_itemmeta';

    $order = new WC_Order( $order_id );
    $order_items = $order->get_items();
    
    //$order->payment_complete();
    foreach ( $order_items as $item ) {
        //write_debug($item);
        $item_id = get_post_meta($item['Appointment ID'],'_appointment_order_item_id',true);
        $session_type = $wpdb->get_var( "SELECT meta_value FROM $WOOCOMMERCE_ORDER_ITEMMETA  WHERE meta_key LIKE 'Session Type%' AND order_item_id = $item_id");
        
        update_post_meta( $item['Appointment ID'], 'appointment_type', $session_type);
        $staff_id = get_post_meta($item['Appointment ID'],'_appointment_staff_id',true);
        // Create Zoom appointment
        $appointment = get_wc_appointment( $item['Appointment ID'] );
        $start_time = $appointment->start;
        $UTCDate = gmdate("Y-m-d\TH:i:s\Z", $start_time);
        $ZoomAPI = new ZoomAPI();
        ob_start();
        $return = $ZoomAPI->create_scheduled_meeting($UTCDate);
        $output = ob_get_clean();
        $return = json_decode($return, true);
        
        // Store the start_url and join_url in the appointment post metadata
        update_post_meta($item['Appointment ID'], '_meeting_id', $return['id']);
        update_post_meta($item['Appointment ID'], '_start_url', $return['start_url']);
        update_post_meta($item['Appointment ID'], '_join_url', $return['join_url']);

        //this hooks for some reason stopped working
        do_action( 'woocommerce_appointment_paid_to_confirmed', $item['Appointment ID'] ); // Should trigger the appointment confirmation email
        do_action( 'woocommerce_appointment_confirmed', $item['Appointment ID'] );
        
        //email group sessions members
        if($session_type == 'Group Session')
        {
            $group_members = insert_data_to_group_session_table($item['Appointment ID']);

            foreach ($group_members as $member) {

                $subject = "[Neo] New Group Session";

                $heading = "New Group Session";

                ob_start();
                    wc_get_template( 'emails/client-new-group-session.php', array(
                        'appointment'   => $appointment,
                        'member'        => $member,
                        'email_heading' => 'New Group Session',
                        'sent_to_admin' => false,
                        'plain_text'    => false,
                    ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
                $string = ob_get_clean();
                
                $message = $string;

                send_email_woocommerce_style($member->email, $subject, $heading, $message); 
            } 
        }
        schedule_sms_reminder($item['Appointment ID']);
    }
}

add_action( 'woocommerce_checkout_order_processed', 'checkout_process' ); // This hook also runs in AJAX process

function insert_data_to_group_session_table($post_id)
{
    global $wpdb;

    $CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';
    
    $parent_id = wp_get_post_parent_id($post_id);
    $emails = get_post_meta($parent_id,'_group_session_emails',false);
    foreach($emails[0] as $email){ // for some reason the array is nested when it is stored in the database thats why we need to add the index 0

        $res = true;
        while($res)
        {
            $salt = wp_generate_password(10); // 20 character "random" string
            $key = sha1($salt . $email . uniqid(time(), true));
            
            $res = $wpdb->get_col($wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE key_code = %s", $key));
            if(empty($res))//make sure the key is unique
                $res = false;
        }
        
        $successful = $wpdb->insert($CLIENT_GROUP_SESSIONS_TABLE, array(
            'appointment_id'     => $post_id,
            'email'              => $email,
            'status'             => 'undefined',
            'key_code'           => $key
        ));
    }
    
    //we need to get the results from the table and pass the array
    if($successful)$results = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $CLIENT_GROUP_SESSIONS_TABLE WHERE appointment_id = %d", $post_id) );
    
    return $results; //return array of datas from table details
}
function send_email_woocommerce_style($email, $subject, $heading, $message) {
      // Get woocommerce mailer from instance
      $mailer = WC()->mailer();
      // Wrap message using woocommerce html email template
      $wrapped_message = $mailer->wrap_message($heading, $message);
      // Create new WC_Email instance
      $wc_email = new WC_Email;
      // Style the wrapped message with woocommerce inline styles
      $html_message = $wc_email->style_inline($wrapped_message);
      // Send the email using wordpress mail function
    wp_mail( $email, $subject, $html_message, array('Content-Type: text/html; charset=UTF-8') );
}
// Order received page {THANKYOU}
function order_received_text( $text, $order ) {
    return 'Thank you. Your appointment has been confirmed. The details of your appointment have been sent to your email.';
}
add_filter('woocommerce_thankyou_order_received_text', 'order_received_text', 10, 2 );

add_action( 'woocommerce_thankyou', 'custom_woocommerce_auto_complete_order' );
function custom_woocommerce_auto_complete_order( $order_id ) { 
    if ( ! $order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    $order->update_status( 'completed' );
}
