<?php do_action( 'bp_before_profile_loop_content' ); ?>

<?php if ( bp_has_profile() ) : ?>
<?php 
    global $wpdb;
    $WOOCOMMERCE_APPOINTMENT_RELATIONSHIPS_TABLE = $wpdb->prefix . 'wc_appointment_relationships';
    $user_id = bp_displayed_user_id(); 
    $product_id = $wpdb->get_row( "SELECT * FROM $WOOCOMMERCE_APPOINTMENT_RELATIONSHIPS_TABLE WHERE staff_id='$user_id'" );
    $price = get_post_meta($product_id->product_id,'_price', true);
?>
    
    <div class="profilePage"> 
        <div class="counselor-block leftCollapsableColumn profile-wrap" style="width: 70%">
            <h2 class="blurbHeading center-text h6"><span><?php echo xprofile_get_field_data( 'Name', $user_id ); ?></span><span class="right-float"><?php echo '$'.$price.'/hr'; ?></span></h2>
            <div>
                <?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full' ) ) ?>
                <div class="bio-container">
                    <p class ="biotext"><b>Gender: <?php echo xprofile_get_field_data( 'gender', $user_id ); ?></b></p>
                    <p class ="biotext"><b>Qualifications: </b><?php echo xprofile_get_field_data( 'Qualifications', $user_id ); ?></p>
                    <!-- <p class ="biotext"><b>Medicare Eligible:</b><?php echo xprofile_get_field_data( 'Medicare Eligible', $user_id ); ?></p> -->
                    <p class ="biotext"><b>Delivery Mechanism: </b>
                   <?php 
                        echo implode(', ',array_filter(xprofile_get_field_data( 'Delivery Mechanism', $user_id )));
                    ?>
                    </p>
                     <p class ="biotext">&nbsp</p>
                </div>
            </div>
            <div style="margin-top: 70px">
                <p><b>About Me:</b></p>
                <p><?php echo xprofile_get_field_data( 'About Me', $user_id ); ?></p>
            </div>
            <div id="specialization">
                <p><b>Specialisations:</b></p>
                <ul>
                    <?php 
                    foreach (xprofile_get_field_data( 'Counselling Specialities', $user_id ) as $specialty) {
                        echo '<li class ="specialty">'.$specialty.'</li>';
                    }
                    ?>
                </ul>
            </div>
            <?php if( current_user_can('administrator') ) : // only show this if current user is admin?>
                <div id="admin_dashboard" style ="padding: 10px; margin:10px; background-color: #eee"><!--this contains information about counsellors on signup-->
                    <p><b>Counselor experience: </b><?php echo xprofile_get_field_data( 'Experience', $user_id ); ?></p>
                    <div>
                        <p><b>Qualifications box: </b></p>
                        <?php if(xprofile_get_field_data( '45', $user_id )) :?>
                            <p><b>Support document : </b><?php echo xprofile_get_field_data( '45', $user_id ); ?></p>
                        <?php endif?>
                        <?php if(xprofile_get_field_data( '83', $user_id )) :?>
                            <p><b>Support document : </b><?php echo xprofile_get_field_data( '83', $user_id ); ?></p>
                        <?php endif?>
                        <?php if(xprofile_get_field_data( '84', $user_id )) :?>
                            <p><b>Support document : </b><?php echo xprofile_get_field_data( '84', $user_id ); ?></p>
                        <?php endif?>
                    </div>
                </div><!--end of admin dashboard-->
            <?php endif?>
            <input class="button" type="button" onclick="history.back();" value="Back to Search">
        </div> 
        <div class="woocommerce-block rightCollapsableColumn profile-wrap" style="width: 28%">
            <h4 itemprop="name" class="product_title">
            <?php
                echo 'Book an appointment with'; 
            ?>
            <?php echo xprofile_get_field_data( 'Name', $user_id ); ?></h4>
            <p style="margin: 0;"><strong>Please note all times are in AEST</strong></p>
            <?php // WooCommerce shortcode documentation found at https://bizzthemes.com/help/setup/wc-appointments/tutorials/appointment-form-shortcode/
            echo do_shortcode( '[appointment_form id="'
                                       .
                                       xprofile_get_field_data( 'Calendar ID', $user_id)
                                       .
                                       '" show_title="0"
                                       show_rating="0"
                                       show_excerpt="0"
                                       show_meta="0"
                                       show_sharing="0"
                                       show_price="0"]' ); ?>
            <!-- The woocommerce appointments shortcode [appointment_form] has malformed HTML and requires two extra closing </div> -->
            </div>
            </div>
        </div> 
    </div><!--end profilePage-->
<style>
   .entry-wrap{
        padding: 0; 
        background-color: #f2f2f2;
        box-shadow: none
   }  
</style>
<?php endif; ?>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
