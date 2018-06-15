<?php
/**
 * Template Name: Reservation Template
 *
 * @package WordPress
 */
?>

<?php
function flatten($in) {
    $result = array();
    foreach ($in as $item) {
        if (is_array($item)) {
            $result[] = array_filter($item, 'notArray');
            $result = array_merge($result, flatten($item));
        } 
    }
    return $result;
}

function notArray($in) {
    return ! is_array($in);
}
	global $wpdb;
	$user_id = get_current_user_id();
	$is_agency = user_can($user_id,'agency');

    $AGENCY_COUPONS_TABLE = $wpdb->prefix . 'agency_coupons';
    $AGENCY_RESERVATION_TABLE = $wpdb->prefix . 'agency_reservation';
    $message = 'Your reservation has been unsuccessful. Contact support for help';

    if($is_agency)
    {
		$item = get_user_meta($user_id, '_woocommerce_persistent_cart', true); 
		$result = flatten($item);
        $array_merged=array();
        foreach($result as $child){
            $array_merged += $child;
        }
		$start_date =  $array_merged['_start_date'];
        $staff_id =  $array_merged['_staff_id'];
        $product_id =  $array_merged['product_id'];
        $expiration_date = strtotime('-1 day', $start_date);
    	$coupon = $wpdb->get_var('SELECT coupon_code FROM '.$AGENCY_COUPONS_TABLE.' WHERE agency_id = '.$user_id);
        
        $wpdb->insert( $AGENCY_RESERVATION_TABLE, array(
                'coupon_code' => $coupon,
                'agency_id'=> $user_id,
                'staff_id' => $staff_id,
                'start_date' => date('Y-m-d H:i:s ',$start_date),
                'expiration_date' => date('Y-m-d H:i:s ',$expiration_date),
            ));
    
        wp_delete_post($array_merged['_appointment_id']); //removes the cart
        $message = 'Thank you. Your reservation has been confirmed.';
    }
    else
    {
        wp_redirect('http://mychristiancounsellor.org.au/');
    	exit();
    }
?>

<?php get_header(); ?>

  	<div class="x-container max width offset">
    	<div class="x-main full" role="main">
    		<div class="entry-wrap">
                <h4 class="appointment-confirmation-header">Reservation</h4>
                <p class="woocommerce-thankyou-order-received"><?php echo $message?></p>
                <div class="x-container max width columnCentre">
                    <a class="x-btn" href="<?php echo wp_logout_url( home_url() ); ?>">Log Out</a>
                </div>
                <div class="clear"></div>
          	</div>
    	</div>
	</div>


<?php get_footer(); ?>
