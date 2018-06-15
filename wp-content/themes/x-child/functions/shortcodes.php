<?php
function shortcode_agency_list( $atts ) {
    ?>
    <hr class="thickLine">
    <?php
    // Display list of all agencies, or a single agency if a single agency is logged in.
    global $wpdb;
    $current_user = wp_get_current_user();
    $coupons = $wpdb->get_results('SELECT * FROM wp_agency_coupons');
    $args = array('role' => 'agency');
    $agencies = get_users( $args );
    foreach ($coupons as $coupon) {
        if (in_array( 'agency', (array)$current_user->roles)) {
            if ($coupon->agency_id != $current_user->ID) continue;
            $agency_user = $current_user;
        }
        else {
            foreach($agencies as $agency) {
                if ($agency->ID == $coupon->agency_id) {
                    $agency_user = $agency;
                    break;
                }
            }
        }
        ?>

        <!-- MAIN COUPON HEADINGS -->
        <div class="headingsContainer"><div class="quarterGrid"><span class="dataHeading"><span class="mainHeading"><?php echo $agency_user->user_nicename ?></span></span></div>
        <div class="quarterGrid"><span class="dataHeading">Code: <span class="dataPoint"><?php echo $coupon->coupon_code; ?></span></span></div>
        <div class="quarterGrid"><span class="dataHeading">Client limit: <span class="dataPoint"><?php echo $coupon->max_per_client; ?></span> </span></div>
        <?php
        $valid_batches = $wpdb->get_results(
            $wpdb->prepare("
                    SELECT * FROM wp_agency_coupon_batch
                    WHERE coupon_code = '" . $coupon->coupon_code . "'
                    AND CURRENT_TIMESTAMP < DATE_ADD(purchase_date, INTERVAL days_valid DAY)
                    ORDER BY purchase_date ASC;")
        );
        $amount_available = 0;
        foreach ($valid_batches as $batch) {
            $amount_available += $batch->amount_available;
        }
        echo '<div class="quarterGrid"><span class="dataHeading">Total coupons available: <span class="dataPoint">' . $amount_available . '</span></span></div></div>';

        /* END MAIN COUPON HEADINGS */

        /* INDIVIDUAL BATCHES */

        if (!$valid_batches) {
            echo '<p style="color:red;"><strong>All coupons have expired for this customer.</strong></p>';
        }
        else {
            ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Batch name</th>
                        <th scope="col">Amount available</th>
                        <th scope="col">Date purchased</th>
                        <th scope="col">Valid for</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            foreach ($valid_batches as $index => $batch) {
                ?>
                <tr>
                    <td><?php echo range('A', 'Z')[$index]; ?></td>
                    <td><?php echo $batch->amount_available; ?></td>
                    <td><?php echo date('F jS, Y', strtotime($batch->purchase_date)); ?></td>
                    <td><?php echo $batch->days_valid; ?> days</td>
                </tr>
                <?php
            }
            ?>
                </tbody>
            </table>
            <?php
        }
        if (in_array( 'agency', (array)$current_user->roles)) {
            ?>
            <p>Please contact AIFC if you wish to top up your coupon.</p>
            <?php
        }
        else {
            ?>
            <div class="floatContainer">
                <a class="x-btn alignright" href="/top-up-coupon/?coupon_code=<?php echo $coupon->coupon_code; ?>">Top Up Coupon</a>    
            </div>
            <?php
        }

        /* END INDIVIDUAL BATCHES */

        if (!in_array( 'agency', (array)$current_user->roles)) {

            $coupon_users = $wpdb->get_results( 
                $wpdb->prepare("SELECT * FROM wp_client_coupon_counts WHERE coupon_code = '" . $coupon->coupon_code . "'") 
            );

            $coupon_interim_users = $wpdb->get_results( 
                $wpdb->prepare("SELECT * FROM wp_client_coupon_interim WHERE coupon_code = '" . $coupon->coupon_code . "'")
            );

            foreach ($coupon_interim_users as $coupon_interim_user) {
                $coupon_interim_user->interim = 1;
            }

            $users = array_merge($coupon_users, $coupon_interim_users);
            if ($users) {
            ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Client Email</th>
                        <th scope="col">Number of uses</th>
                        <th scope="col">Currently booking</th>
                    </tr>
                </thead>
                <?php
                foreach ($users as $user) {  
                    ?>
                    <tbody>
                        <tr>
                            <td><?php echo $user->username; ?></td>
                            <td><?php echo !empty($user->uses) ? $user->uses : "N/A"; ?></td>
                            <td><?php echo !empty($user->interim) ? "Yes" : "No"; ?></td>
                        </tr>
                    </tbody>
                    <?php 
                }
                ?>
            </table>
            <?php
            }
        }
            ?>
        <hr class="thickLine">
        <?php
    }
}
add_shortcode( 'agency_list', 'shortcode_agency_list' );

function shortcode_query_parameter_prompt( $atts ) {
    $atts = shortcode_atts( array(
        'parameter_name' => '', // The name of the query parameter
        'message' => '', // The message to render after rendering the query parameter.
        'display_parameter' => false, // Whether or not to print the parameter name in the message
        'error_message' => false, // Whether or not this is an error message
    ), $atts );
    parse_str($_SERVER['QUERY_STRING'], $output);
    if (isset($output[$atts['parameter_name']])) {
        ?>
        <div class="<?php echo ($atts['error_message']) ? 'errorPrompt' : 'successPrompt'; ?>">
            <?php
            if ($atts['display_parameter']) {
                echo $output[$atts['parameter_name']] . ' ' . $atts['message'];
            }
            else {
                echo $atts['message'];
            }
            ?>
        </div>
        <?php
    }
}

add_shortcode( 'query_parameter_prompt', 'shortcode_query_parameter_prompt' );

function my_login_form_shortcode() {

    if ( is_user_logged_in() )
        return '';

    return wp_login_form( array( 
        'echo' => false,
        'remember' => false, 
        'label_username' => __( 'Email' ),
        'form_id'        => 'clientLoginForm'
        )
    );
}

add_shortcode( 'my_login_form', 'my_login_form_shortcode' );

function availability_button_shortcode() {
    $current_user = wp_get_current_user();
    ?>
        <a class="x-btn" href="/wp-admin/post.php?post=<?php echo xprofile_get_field_data( 'Calendar ID', $current_user->ID); ?>&action=edit">Edit your Availability</a>      
    <?php
}

add_shortcode( 'edit_availability', 'availability_button_shortcode' );

function cancel_appointment_shortcode()
{
   $current_user = wp_get_current_user(); 
    ?>
        <a class="x-btn" href="/wp-admin/edit.php?s&post_type=wc_appointment&filter_staff=<?php echo $current_user->ID ?>">Cancel Appointments</a>
    <?php 
    //wp-admin/edit.php?s&post_type=wc_appointment&filter_staff=94
}

add_shortcode( 'cancel_appointment', 'cancel_appointment_shortcode' );

function view_profile_shortcode() {
    ?>
        <a class="x-btn" href="<?php echo bp_loggedin_user_domain(); ?>">View your profile</a>
    <?php
}

add_shortcode( 'view_profile', 'view_profile_shortcode' );

function your_appointments_shortcode() {
    ?>
        <a class="x-btn" href="http://mychristiancounsellor.org.au/woocommerce-account-page/appointments/">Your Appointments</a>
    <?php
}

add_shortcode( 'your_appointments', 'your_appointments_shortcode' );

function make_booking_shortcode() {
    ?>
        <a class="x-btn" href="http://mychristiancounsellor.org.au/make-booking/">Make a Booking</a>
    <?php
}

add_shortcode( 'make_booking', 'make_booking_shortcode' );

function reserve_slot_shortcode() {
    ?>
        <a class="x-btn" href="http://mychristiancounsellor.org.au/members/">Reserve a Slot</a>
    <?php
}

add_shortcode( 'reserve_slot', 'reserve_slot_shortcode' );

