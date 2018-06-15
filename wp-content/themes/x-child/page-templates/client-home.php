<?php
/**
 * Template Name: Client Home Template
 *
 * @package WordPress
 */
?>
<!-- See ./wp-content/themes/x/framework/views/integrity/template-blank-1.php for reference to below HTML -->
<?php 

	global $current_user;
	get_header(); 
 	$user = wp_get_current_user(); 
 	$user_id = $user->ID;
 	if (in_array( 'shop_staff', (array)$user->roles)) {
 		$counselor = true;
 	}
?>
  <div class="x-container max width offset">
  	<?php //echo('<pre>'.var_dump($user_id).'<pre>'); ?>
  	<div  class="client-header">
  		<h3><?php echo "Welcome Back, ". xprofile_get_field_data( 'Name', $user_id ); ?></h3>
	</div>
    <div class="x-main full" role="main">
    	<div class="clientPage">
    		<div class="profile-top">
        		<div class="entry-wrap first-wrap">
			        <div class="profile-header">
			        	<a href="<?php echo bp_loggedin_user_domain() ?>profile/change-avatar/"> <!--make div clickable-->
				        	<div class="profile-picture">
				            	<?php echo bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'full' ) ) ?> 
				        	</div>
			        	</a>

			        	<?php if($counselor)://start of counselor profile-conetents?>
			        		<div class="profile-contents">
				            	<div>
					                <div class="bio-container" style="padding:0;">
					                    <p class ="biotext"><b>Gender: <?php echo xprofile_get_field_data( 'gender', $user_id ); ?></b></p>
					                    <p class ="biotext"><b>Qualifications: </b><?php echo xprofile_get_field_data( 'Qualifications', $user_id ); ?></p>
					                    <p class ="biotext"><b>Medicare Eligible:</b><?php echo xprofile_get_field_data( 'Medicare Eligible', $user_id ); ?></p>
					                    <p class ="biotext"><b>Delivery Mechanism: </b>
					                   <?php 
					                        echo implode(', ',array_filter(xprofile_get_field_data( 'Delivery Mechanism', $user_id )));
					                    ?>
					                    </p>
					                </div>
					            </div>
					            <div style="margin-top: 50px">
					                <p><b>About Me:</b></p>
					                <p><?php echo xprofile_get_field_data( 'About Me', $user_id ); ?></p>
					            </div>
					            <div id="specialization">
					                <p><b>Specialisations:</b></p>
					                <ul style="float:left;">
					                    <?php 
					                    foreach (xprofile_get_field_data( 'Counselling Specialities', $user_id ) as $specialty) {
					                        echo '<li class ="specialty">'.$specialty.'</li>';
					                    }
					                    ?>
					                </ul>
					                <a href="http://mychristiancounsellor.org.au/wp-admin/admin-ajax.php?action=csv_pull" class="button" style="float:right; font-size: 12px;">Export Logbook</a>
					            </div>
				        	</div>
			        	<?php else : //end of counselor profile-contents?>
				        	<div class="profile-contents">
				            	<ul>
				            		<?php if(!empty(get_user_meta($user_id,'assessment_result',true )) ) :?>
										<li><strong><?php echo "Gender: ".get_user_meta($user_id,'gender',true ); ?> </strong></li>
					            		<?php
						            		$from = new DateTime(get_user_meta($user_id,'date_of_birth',true ));
											$to   = new DateTime('today');
										?>
					            		<li><strong><?php echo "Age: ".$from->diff($to)->y; ?> </strong></li>
					            		<li><strong><?php echo "Self-Assessment Tool Result: "?></strong></li>
					            		<li><?php echo get_user_meta($user_id,'assessment_result',true ); ?> </li> <!-- modify this content-->
					            		<li><?php echo "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus at tincidunt erat. Morbi nec posuere ex, at suscipit justo. Donec ut tincidunt turpis. Mauris cursus, ante ut consequat tempus, quam risus porta ante, quis scelerisque mauris felis ac augue." ?> </li> 
					            		<!-- modify this content-->
					            		<li style="margin-top: 30px;"><a href="<?php echo get_site_url();?>/edit-user" class="specialty">Edit Profile</a></li>
				            		<?php else :?>
			            			 	<style type="text/css" scoped>
									        .first-wrap { width:80%; } 
								    	</style>
					            		<li><?php echo "" ?> </li> <!-- modify this content-->
					            		<li><strong><?php echo "Self-Assessment Tool Result: "?></strong></li>
					            		<li><?php echo "You haven't tried the assessment-tool. Click <a href='".home_url().'/self-assessment'."'>here</a> to get started" ?> </li> <!-- modify this content-->
				            		<?php endif; // show self-assessment tool resutls, only if theres any?>
				            	</ul>
				        	</div>
				        <?php endif; //end of client profile-contents ?>
			        </div>
			    </div>    
	        	<!--End profile Header-->
		        <div class="entry-wrap second-wrap">
			        <div class="profile-appointment center-content">
			        	<?php if(empty(get_next_appointment_id())) : ?>
			        		<p><strong>You dont have any upcoming appointments</strong></p>
			        	<?php else :?>
			            <p><strong>Your Next Appointment:</strong></p>
			            <?php 
			            		
			            	$appointment_id = get_next_appointment_id();
			            	$start_time = get_post_meta($appointment_id,'_appointment_start', true);
			            	$customer_id = get_post_meta($appointment_id, '_appointment_customer_id', true);
			            	if(!$counselor) //only get if user is not a counselor
			            		$counselor_name = get_user_meta(get_post_meta($appointment_id,'_appointment_staff_id',true),'nickname',true); //nested get meta
			            	else
			            		$counselor_name = xprofile_get_field_data('Name',$customer_id); //$variable should be customer_name but ceebs changing it
			            	$appointment_link = get_post_meta($appointment_id,'_join_url', true);
			            	$appointment_link_start = get_post_meta($appointment_id,'_start_url', true);
			            ?>
			            <p><strong><?php echo date('h:ia, jS \of F',strtotime($start_time)).'<br>'.$counselor_name; ?></strong></p>
			            	<?php if(!empty($appointment_link) && !empty($appointment_link_start)):?>
			            		<?php if(!$counselor) : ?>
			            			<a target="_blank" class = "button" style = "font-size: 13px;" href="<?php echo $appointment_link?>">Join Appointment</a>
			            		<?php else: ?>
			            			<a target="_blank" class = "button" style = "font-size: 13px;" href="<?php echo $appointment_link_start?>">Join Appointment</a>
			            		<?php endif?>
			            	<?php endif?>
			        	<?php endif?>
			        </div>
			    </div>
		    </div>  <!--end of profile-top-->

		    <?php while ( have_posts() ) : the_post(); ?>
	        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	            <div class="entry-wrap">
	              	<div class="entry-content content">
	              		<?php if(!$counselor) : ?>
							<div id="x-content-band-1" class="x-content-band vc" style="background-color: transparent; padding-top: 0px; padding-bottom: 0px;">
								<div class="x-container wpb_row">
									<div class="x-column x-sm vc rows-5" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/my_calendar/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/calendar.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/calendar.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-5" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/members/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/book_appointment.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/book_appointment.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-5" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/privacy-policy/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/privacy_policy.png'">
											<img class="x-img" src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/privacy_policy.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-5" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/test-call/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/test_call.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/test_call.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-5" style="">
										<a id="zoom_download_link" class="x-img x-img-link" href="https://www.zoom.us/download" target="_blank" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/zoom.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/zoom.png">
										</a>
									</div>
									<!-- <div class="x-column x-sm vc rows-6" style="">
										<a class="x-img x-img-link" href="<?php echo get_site_url();?>/edit-user" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/EditProfile_Button.jpg'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/EditProfile_Button.jpg">
										</a>
									</div> -->
								</div>
							</div>
						<?php else : ?> <!--counselor menu starts here-->
							<div id="x-content-band-1" class="x-content-band vc" style="background-color: transparent; padding-top: 0px; padding-bottom: 0px;">
								<div class="x-container wpb_row">
									<div class="x-column x-sm vc rows-6" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/my_calendar/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/calendar.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/calendar.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-6" style="">
										<?php 
											$post_parent = xprofile_get_field_data( 'Calendar ID', $current_user->ID);
											$post_child = get_children('post_type=product&post_parent='.$post_parent );
											reset($post_child);
											$first_key = key($post_child);
										?> 
										<a class="x-img x-img-link" href="/wp-admin/post.php?post=<?php echo $first_key; ?>&action=edit" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/ChangeAvalibility_Button.jpg'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/ChangeAvalibility_Button.jpg">
										</a>
									</div>
									<div class="x-column x-sm vc rows-6" style=""><!--View patient notes-->
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/patient-notes/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/ViewPatientNotes_Button.jpg'">
											<img class="x-img" src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/ViewPatientNotes_Button.jpg">
										</a>
									</div>
									<div class="x-column x-sm vc rows-6" style="">
										<a class="x-img x-img-link" href="http://mychristiancounsellor.org.au/test-call/" target="_blank" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/test_call.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/test_call.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-6" style="">
										<a id="zoom_download_link" class="x-img x-img-link" href="https://www.zoom.us/download" target="_blank" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/zoom.png'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/11/zoom.png">
										</a>
									</div>
									<div class="x-column x-sm vc rows-6" style=""> <!--edit profile-->
										<a class="x-img x-img-link" href="<?php echo bp_loggedin_user_domain();?>/profile/edit/group/2/" data-options="thumbnail: 'http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/EditProfile_Button.jpg'">
											<img src="http://mychristiancounsellor.org.au/wp-content/uploads/2017/12/EditProfile_Button.jpg">
										</a>
									</div>
								</div>
							</div>
						<?php endif?>	
  					</div>
				</div>
	        </article>
			<?php endwhile; ?>
	    </div><!--end of clientPage-->
    </div>
</div>
<?php get_footer(); ?>
<?php 
function get_next_appointment_id()
{
	$current_time = current_time( 'YmdHis' );
	$user = wp_get_current_user(); 
	$user_id = $user->ID;
	if (in_array( 'shop_staff', (array)$user->roles)) {
		$counselor = true;
	}
	if($counselor)
	{
		$appointment_args = array(
			'orderby'       => 'start_date',
      		'order'         => 'ASC',
		 	'meta_query'    => array(
		    'relation' => 'AND',
		        array(
		          	'key'     => '_appointment_staff_id',
		          	'value'   => absint( $user_id ),
		          	'compare' => 'IN',
		        ),
		        'start_date'  => array(
			        'key'     => '_appointment_start',
			        'value'   => $current_time,
			        'compare' => '>='
		      	),
	      	),
		  	'post_status' => get_wc_appointment_statuses(),
    	);
    	$appointments = WC_Appointments_Controller::get_appointments($appointment_args);
    	return $appointments[0]->id;
	}

	$upcoming_appointments_args = array(
      'orderby'       => 'start_date',
      'order'         => 'ASC',
      'meta_query'    => array(
        'relation' => 'AND',
        array(
          'key'     => '_appointment_customer_id',
          'value'   => absint( $user_id ),
          'compare' => 'IN',
        ),
        'start_date'  => array(
          'key'     => '_appointment_start',
          'value'   => $current_time,
          'compare' => '>=',
        ),
      ),
      'post_status' => get_wc_appointment_statuses( ),
    );
    $upcoming_appointments = WC_Appointments_Controller::get_appointments( $upcoming_appointments_args );
  	
    $id = $upcoming_appointments[0]->id;

    //check also in group sessions table
    global $wpdb;

	$CLIENT_GROUP_SESSIONS_TABLE = $wpdb->prefix . 'client_group_sessions';

  	$appointment_ids = $wpdb->get_results($wpdb->prepare(
    	"SELECT $CLIENT_GROUP_SESSIONS_TABLE.appointment_id
    	FROM $CLIENT_GROUP_SESSIONS_TABLE
    	INNER JOIN $wpdb->users ON ($CLIENT_GROUP_SESSIONS_TABLE.email = $wpdb->users.user_email)
    	WHERE $CLIENT_GROUP_SESSIONS_TABLE.email = %s
    	AND $CLIENT_GROUP_SESSIONS_TABLE.status = 'accepted'", wp_get_current_user()->user_email),ARRAY_A);
  
  	$filter = array();
  	foreach($appointment_ids as $appointment_id)
  	{
    	$filter[] = $appointment_id['appointment_id'];
  	}
	  
  	$group_id = $wpdb->get_row( "SELECT *
	    FROM $wpdb->posts AS posts
	    LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
	    WHERE meta.meta_key = '_appointment_start'
	    AND   meta.meta_value >= ".date('YmdHis')."
	    AND   posts.ID IN ( '" . implode( "','", $filter ) . "' )
	    AND NOT posts.post_status = 'cancelled'
	    ORDER BY posts.ID DESC" );

	if(!empty($group_id) && !empty($upcoming_appointments))
	{	
		if($group_id->meta_value < $upcoming_appointments[0]->start_time)
			$id = $group_id->ID;
	}
	else if(!empty($group_id))
	{
		$id = $group_id->ID;
	}
  return $id; //returns the first one only
}
