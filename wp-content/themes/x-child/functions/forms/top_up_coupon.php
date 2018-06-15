<?php

add_filter('gform_pre_render_5', 'add_readonly_script_top_up');
function add_readonly_script_top_up($form) {
    ?>
    <script type='text/javascript'>
        jQuery(document).ready(function(){
            jQuery('.couponCode input').attr('readonly', 'readonly');
        });
    </script>
    <?php
    return $form;
}

add_action( 'gform_after_submission_5', 'top_up_coupon_code' );

function top_up_coupon_code( $entry, $form ) {
    global $wpdb;
    $coupon_code = rgar( $entry, '3' );
    $coupon_quantity = rgar( $entry, '2' );
    $expiry_period = rgar( $entry, '1' );
    $AGENCY_COUPON_BATCH_TABLE_NAME = $wpdb->prefix . 'agency_coupon_batch';

    $wpdb->insert($AGENCY_COUPON_BATCH_TABLE_NAME, array(
        'coupon_code'         => $coupon_code,
        'amount_available'    => $coupon_quantity,
        'initial_amount'      => $coupon_quantity,
        'days_valid'          => $expiry_period,
    ));
}