<?php
/**
 * Template Name: Post-Appointment Template
 *
 * @package WordPress
 */
?>
<?php

$role = get_query_var('r');
$appointment_id = get_query_var('i');
$appointment = get_wc_appointment($appointment_id);
if(empty($role) || empty($appointment_id) || empty($appointment)) // if one of the fields are empty
{
	wp_redirect(home_url());
    exit();
}


$counselor_id = $appointment->custom_fields['_appointment_staff_id'][0];
$customer_id = $appointment->customer_id;
$appointment_type = $appointment->custom_fields['appointment_type'][0];
$appointment_staff_id = $appointment->custom_fields['_appointment_staff_id'][0];
$client_email = get_userdata($customer_id)->user_email;
$couselor_email = get_userdata($appointment_staff_id)->user_email;
get_header(); 
?>
<div class="x-container max width offset">
  	<h3 class="my-headings">Post Appointment Feedback</h3>
  	<p class = "paragraph-uncontained" style="margin: 2px 0 2.313em;">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas at faucibus neque.</p>
	<div class="x-main full" role="main">
		<div id="post-appointment-page">
			<?php if($role == "client"):?>
				<div class="profile-top">
		    		<div class="entry-wrap first-wrap">
				        <div class="profile-header">
					        <div>
					           <?php echo do_shortcode('[gravityform id=12 title=false description=false ajax=true]');?>
					           <div class="buddydrive" role="main">

									<?php do_action( 'buddydrive_directory_content' ); ?>

								</div><!-- .buddydrive -->
					        </div>
				        </div>
				    </div>    
		        	<!--End profile Header-->
			        <div class="entry-wrap second-wrap">
				        <div class="woocommerce-block">
				        	<h4 itemprop="name" class="product_title">
					        <?php
					            echo 'Book another appointment with'; 
					        ?>
					        <?php echo xprofile_get_field_data( 'Name', $counselor_id ); ?></h4>
					        <p style="margin: 0;"><strong>Please note all times are in AEST</strong></p>
					        <?php // WooCommerce shortcode documentation found at https://bizzthemes.com/help/setup/wc-appointments/tutorials/appointment-form-shortcode/
						        echo do_shortcode( '[appointment_form id="'
	                                   .
	                                   xprofile_get_field_data( 'Calendar ID', $counselor_id)
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
							<a class="button" href="<?php echo get_permalink(get_page_by_title('members'))?>">Return to the Counselor Catalogue</a>
				        </div>
				    </div>
			    </div>
			<?php elseif($role == "counselor"):?>
				<div class="profile-top">
		    		<div class="entry-wrap first-wrap" style="width: 100%;">
				        <div class="profile-header">
					        <div>
					           <?php echo do_shortcode('[gravityform id=13 title=false description=false ajax=true]');?>
					        </div>
				        </div>
				    </div>    
		        	<!--End profile Header-->
			        <div class="entry-wrap second-wrap">
				        <div class="woocommerce-block center-content">
				        	<h4 itemprop="name" class="product_title" style="padding:20px">Appointment Details</h4>
				        	<div class="">
					        <?php 
					        	echo "<p><strong>Session Type: </strong></p><p>$appointment_type</p>";
					        	echo "<p><strong>Client: </strong></p><p>".xprofile_get_field_data( 'Name', $customer_id )."</p>";
					        	echo "<p><strong>Email: </strong></p><p>$client_email</p>";
					        	echo "<p><strong>Appointment Start: </strong></p><p>". date('h:ia, jS \of F',$appointment->start)."</p>";
					        	echo "<p><strong>Appointment End: </strong></p><p>". date('h:ia, jS \of F',$appointment->end)."</p>";
					        ?>
					    </div>
				        </div>
				    </div>
			    </div>
			<?php endif;?>
		</div><!--end post-appointment-page-->
	</div>
</div>
<?php get_footer(); ?>