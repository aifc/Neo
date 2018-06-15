<?php

add_filter( 'gform_validation_11', 'cancel_reschedule_for_non_customers' );
function cancel_reschedule_for_non_customers($validation_result)
{

	$form = $validation_result['form'];
 	$appointment_id = rgpost( 'input_3' );
 	$customer_id = get_post_meta($appointment_id, '_appointment_customer_id', true );
 	
    //if the customer is not the one requesting the reschedule send an error
    if ( $customer_id != get_current_user_id() ) {
 
        $validation_result['is_valid'] = false;

    }
 
    //Assign modified $form object back to the validation result
    $validation_result['form'] = $form;
    return $validation_result;
}

add_filter( 'gform_validation_message_11', 'not_allowed_to_resched', 10, 2 );

function not_allowed_to_resched( $message, $form ) {
    return "<div class='validation_error'>" . esc_html__( 'You can only reschedule appointments you have booked.', 'gravityforms' ) . '</div>';
}

add_action( 'gform_after_submission_11', 'cancel_appointment', 10, 2 );

function cancel_appointment($entry, $form )
{

	//parameter name appointment_id
	$client_message = rgar( $entry, '2' );
	$appointment_id = rgar( $entry, '3' );
	$appointment = get_wc_appointment( $appointment_id);
	$woocommerce_product = wp_get_post_parent_id( $post_ID );
	$counselor_id = $appointment->staff_id[0];
	$counselor_email =get_userdata( $counselor_id )->user_email;

    wp_update_post(array('ID'=>  $appointment_id,'post_status'=>  'cancelled'));
    wp_update_post(array('ID'=>  $woocommerce_product,'post_status'=>  'wc-cancelled'));

    $subject = "[Neo] Your appointment has been cancelled";

    $heading = "Appointment Cancelled";

    ob_start();
        wc_get_template( 'emails/admin-appointment-cancelled.php', array(
            'appointment'   => $appointment,
            'email_heading' => 'Appointment Cancelled',
            'sent_to_admin' => false,
            'plain_text'    => false,
        ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
    $string = ob_get_clean();
    
    $message = $string;



    if(!empty($client_message))
    {
    	$message .= 'More details on this cancellation: '.$client_message;
    }
    wp_mail($counselor_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8') );
    
}

add_filter( 'gform_confirmation_11', 'confirm_reschedule', 10, 4); //dont need to inform the counsellor cause a cancellation email will be sent to them once cancelled

function confirm_reschedule($confirmation, $form, $entry, $ajax)
{
	$coupon = create_new_reschedule_coupon();

	$subject = "[Neo] Reschedule Session";

	$heading = "Reschedule Session";
    ob_start();
        wc_get_template( 'emails/client-reschedule-session.php', array(
            'coupon'        => $coupon,
            'email_heading' => 'Reschedule Session',
            'sent_to_admin' => false,
            'plain_text'    => false,
        ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
    $string = ob_get_clean();
    
    $message = $string;

    send_email_woocommerce_style(wp_get_current_user()->user_email, $subject, $heading, $message); //defined in functions/woocommerce.php
	$confirmation = "<h3>Appointment successfully cancelled</h3>";
	$confirmation .= "<h5>".$coupon['coupon_code']."</h5>";
	$confirmation .= "<p>You can use the coupon above to book another session. Enter the code during checkout</p>";
	$confirmation .= "<p>Coupon expiration date is set to expire: ".$coupon['coupon_expiry']."</p>";
	$confirmation .= "<p>Please make sure to book a session using the coupon code before the expiration date otherwise you won't be able to request another reschedule</p>";

	return $confirmation;
}

function create_new_reschedule_coupon()
{
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$res = "";
	for ($i = 0; $i < 10; $i++) {
	    $res .= $chars[mt_rand(0, strlen($chars)-1)];
	}
	$coupon_code = $res; // Code
	$amount = '100'; // Amount
	$discount_type = 'percent'; // Type: fixed_cart, percent, fixed_product, percent_product
	            
	$coupon = array(
	   	'post_title' => $coupon_code,
	   	'post_content' => '',
	   	'post_status' => 'publish',
    	'post_author' => 1,
	    'post_type'   => 'shop_coupon'
	);
	            
	$new_coupon_id = wp_insert_post( $coupon );
	       
	$expiry_date = date('Y-m-d', strtotime('+5 days')); 

	// Add meta
	update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
	update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
	update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
	update_post_meta( $new_coupon_id, 'product_ids', '' );
	update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
	update_post_meta( $new_coupon_id, 'usage_limit', 1 );
	update_post_meta( $new_coupon_id, 'usage_limit_per_user', 1 );
	update_post_meta( $new_coupon_id, 'expiry_date', $expiry_date);
	update_post_meta( $new_coupon_id, 'free_shipping', 'no' );

	$coupon = array('coupon_id' =>$new_coupon_id, 'coupon_code'=>$coupon_code,'coupon_expiry'=>$expiry_date );
	return $coupon;
}

add_action( 'wp_footer', '_custom_popup_scripts', 1000 ); //sets the appointment id to gravity forms field
function _custom_popup_scripts() {
	?>
	<script type="text/javascript">
		(function ($) {
			$(".appointments ul li a").click(function() {
		        $('#input_11_3').val($(this).parent().attr("data-appointment-id"));
		    });
		}(jQuery));
	</script>
	<?php
}