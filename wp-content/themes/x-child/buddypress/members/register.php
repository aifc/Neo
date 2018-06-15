<?php 
	if(!isset( $_GET['role']))
	{
		$wp_session = WP_Session::get_instance();
		$assessment_results = ! empty( $wp_session['results'] ) ? $wp_session['results'] : false;
		// (var_dump($wp_session));
	}
?>
<div id="buddypress">

	<?php do_action( 'bp_before_register_page' ); ?>

	<div class="page" id="register-page">
		<div id="my-custom-home">
			<?php print_r(get_post(92)->post_content); ?>
		</div>
		<?php
		if(!empty($assessment_results))
		{
			echo "<div id='results_registration' style='width:40%; display: inline-block;'><h3 class='my-headings'>".$assessment_results."</h3>
			<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin ultricies arcu elit, nec sagittis sapien rhoncus sed. Cras sed orci porttitor, molestie ante quis, scelerisque metus. Nam purus lacus, cursus nec quam vel, elementum porta tortor. Morbi viverra, neque ut dignissim congue, neque mauris rutrum orci, non luctus urna nisl eget risus. Phasellus tincidunt pulvinar magna, sed cursus urna luctus id. Vivamus arcu augue, volutpat a est nec, sodales hendrerit leo. Praesent volutpat diam eu eros vulputate, quis convallis sapien facilisis. Maecenas eu mauris nisl. Integer id ligula vitae nulla aliquet condimentum placerat id nulla. Fusce vitae maximus massa. Curabitur rutrum diam lectus, ac viverra lacus sodales a. Nullam ac tortor non sem laoreet tincidunt a et nibh. Vivamus pellentesque et tortor ut consectetur. In eget dolor ut ex ultrices efficitur vel in lorem.</p>
			</div>";
			echo'<form action="" name="signup_form" id="signup_form" class="standard-form cf man" method="post" enctype="multipart/form-data" style ="width: 40%;
    			float: right;">';
		}
		else
		{
			echo '<style type="text/css">.entry-wrap{width: 70%; margin: auto;}</style>';
			echo'<form action="" name="signup_form" id="signup_form" class="standard-form cf man" method="post" enctype="multipart/form-data">';
		}
		?>
		<!-- <form action="" name="signup_form" id="signup_form" class="standard-form cf man" method="post" enctype="multipart/form-data"> -->

		<?php if ( 'registration-disabled' == bp_get_current_signup_step() ) : ?>
			<?php do_action( 'template_notices' ); ?>
			<?php do_action( 'bp_before_registration_disabled' ); ?>

				<p><?php _e( 'User registration is currently not allowed.', '__x__' ); ?></p>

			<?php do_action( 'bp_after_registration_disabled' ); ?>
		<?php endif; // registration-disabled signup setp ?>

		<?php if ( 'request-details' == bp_get_current_signup_step() ) : ?>

			<?php do_action( 'template_notices' ); ?>

			<?php do_action( 'bp_before_account_details_fields' ); ?>

			<div class="register-section" id="basic-details-section">

				<?php /***** Basic Account Details ******/ ?>
				<?php
				if ($_GET['role'] == "counselor")
				{
					?>
					<fieldset>
						<h5 class="my-headings"><?php _e( 'Account Details', '__x__' ); ?></h5>

						<label for="signup_email"><?php _e( 'Email Address', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
						<?php do_action( 'bp_signup_email_errors' ); ?>
						<input type="text" name="signup_email" id="signup_email" value="<?php bp_signup_email_value(); ?>" />

						<label for="signup_email_confirm"><?php _e( 'Confirm Email Address', '__x__' ); ?></label>
						<input type="text" name="signup_email_confirm" id="signup_email_confirm" value="" />

						<?php do_action( 'bp_account_details_fields' ); ?>

						<?php do_action( 'bp_after_account_details_fields' ); ?>

						<?php if ( bp_is_active( 'xprofile' ) ) : ?>

							<?php do_action( 'bp_before_signup_profile_fields' ); ?>

							<?php /* Use the profile field loop to render input fields for the 'base' profile field group */ ?>
							<?php if ( bp_is_active( 'xprofile' ) ) : if ( bp_has_profile( array( 'profile_group_id' => 1, 'fetch_field_data' => false ) ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

							<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

								<div class="editfield">

									<?php
									$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
									$field_type->edit_field_html();

									do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

									if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
										<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', '__x__' ); ?></a>
										</p>

										<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
											<fieldset>
												<legend><?php _e( 'Who can see this field?', '__x__' ) ?></legend>

												<?php bp_profile_visibility_radio_buttons() ?>

											</fieldset>
											<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', '__x__' ) ?></a>

										</div>
									<?php else : ?>
										<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?>
										</p>
									<?php endif ?>

									<?php do_action( 'bp_custom_profile_edit_fields' ); ?>

									<p class="description"><?php bp_the_profile_field_description(); ?></p>

								</div>

							<?php endwhile; ?>
							
							<?php $fields_ids[]= bp_get_the_profile_group_field_ids();?>
							<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php echo implode(",",$fields_ids); ?>" />

							<input type="hidden" name="signup_usertype" id="signup_usertype" value="counselor" />

							<?php endwhile; endif; endif; ?>

							<?php do_action( 'bp_signup_profile_fields' ); ?>

						<!-- #basic-details-section -->
						<?php do_action( 'bp_after_signup_profile_fields' ); ?>

						<?php endif; ?>

						<label for="signup_password"><?php _e( 'Choose a Password', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
						<?php do_action( 'bp_signup_password_errors' ); ?>
						<div data-tip="Please include at least one lowercase letter, one uppercase letter, and a number">
							<input type="password" name="signup_password" id="signup_password" value="" />
						</div>
						
						<label for="signup_password_confirm"><?php _e( 'Confirm Password', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
						<?php do_action( 'bp_signup_password_confirm_errors' ); ?>
						
						<input type="password" name="signup_password_confirm" id="signup_password_confirm" value="" />
						
						<span id="password-strength"></span>
						<input type="button" name="password" class="next button" value="Next" />
					</fieldset>
					<fieldset>
						<div id="counselor_fields">
							<h5 class="my-headings">Counselor Profile</h5>
							<!--render counselor profile fields group-->
							<?php if ( bp_is_active( 'xprofile' ) ) : if ( bp_has_profile( array( 'profile_group_id' => 2, 'fetch_field_data' => false ) ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

							<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

								<div class="editfield">
									<?php
									$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
									$field_type->edit_field_html();
									
									do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

									if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
										<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', '__x__' ); ?></a>
										</p>

										<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
											<fieldset>
												<legend><?php _e( 'Who can see this field?', '__x__' ) ?></legend>

												<?php bp_profile_visibility_radio_buttons() ?>

											</fieldset>
											<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', '__x__' ) ?></a>

										</div>
									<?php else : ?>
										<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?>
										</p>
									<?php endif ?>

									<?php do_action( 'bp_custom_profile_edit_fields' ); ?>
								
									<?php if(78 == bp_get_the_profile_field_id()): //checks if the field is the terms and conditions?> 
										<a class="modal-link" href="http://mychristiancounsellor.org.au/terms-and-conditions/">Terms and Conditions</a>
									<?php else : ?>
										<p class="description"><?php bp_the_profile_field_description(); ?></p>	
									<?php endif ?>
										

								</div>

							<?php endwhile; ?>
							<?php $fields_ids[]= bp_get_the_profile_group_field_ids();?>
							<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php echo implode(",",$fields_ids); ?>" />

							<?php endwhile; endif; endif; ?>
						</div>
						<input type="button" name="previous" class="previous button" value="Previous" />
    					<input type="button" name="next" class="next button" value="Next" />
					</fieldset>
					<fieldset>
						<h5 class="my-headings">Counselor Qualifications</h5>
							<!--render counselor profile fields group-->
							<?php if ( bp_is_active( 'xprofile' ) ) : if ( bp_has_profile( array( 'profile_group_id' => 3, 'fetch_field_data' => false ) ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

							<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

								<div class="editfield">
									<?php
									$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
									$field_type->edit_field_html();
									
									do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

									if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
										<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', '__x__' ); ?></a>
										</p>

										<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
											<fieldset>
												<legend><?php _e( 'Who can see this field?', '__x__' ) ?></legend>

												<?php bp_profile_visibility_radio_buttons() ?>

											</fieldset>
											<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', '__x__' ) ?></a>

										</div>
									<?php else : ?>
										<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
											<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?>
										</p>
									<?php endif ?>

									<?php do_action( 'bp_custom_profile_edit_fields' ); ?>
								
									<?php if(78 == bp_get_the_profile_field_id()): //checks if the field is the terms and conditions?> 
										<a class="modal-link" href="http://mychristiancounsellor.org.au/terms-and-conditions/">Terms and Conditions</a>
									<?php else : ?>
										<p class="description"><?php bp_the_profile_field_description(); ?></p>	
									<?php endif ?>
										

								</div>

							<?php endwhile; ?>
							<?php $fields_ids[]= bp_get_the_profile_group_field_ids();?>
							<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php echo implode(",",$fields_ids); ?>" />

							<?php endwhile; endif; endif; ?>
							<?php do_action( 'bp_before_registration_submit_buttons' ); ?>

							<div class="submit">
								<input type="submit" name="signup_submit" id="signup_submit" value="<?php esc_attr_e( 'Sign Up', '__x__' ); ?>" />
							</div>

							<?php do_action( 'bp_after_registration_submit_buttons' ); ?>
					<input type="button" name="previous" class="previous button" value="Previous" />
					</fieldset>
						</div>
			<?php
			} // if the user is a client
			else{
			?>
				<h5 class="my-headings"><?php _e( 'Account Details', '__x__' ); ?></h5>

					<label for="signup_email"><?php _e( 'Email Address', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
					<?php do_action( 'bp_signup_email_errors' ); ?>
					<input type="text" name="signup_email" id="signup_email" value="<?php bp_signup_email_value(); ?>" />

					<label for="signup_email_confirm"><?php _e( 'Confirm Email Address', '__x__' ); ?></label>
					<input type="text" name="signup_email_confirm" id="signup_email_confirm" value="" />

					<?php do_action( 'bp_account_details_fields' ); ?>

					<?php do_action( 'bp_after_account_details_fields' ); ?>

					<?php if ( bp_is_active( 'xprofile' ) ) : ?>

						<?php do_action( 'bp_before_signup_profile_fields' ); ?>

						<?php /* Use the profile field loop to render input fields for the 'base' profile field group */ ?>
						<?php if ( bp_is_active( 'xprofile' ) ) : if ( bp_has_profile( array( 'profile_group_id' => 1, 'fetch_field_data' => false ) ) ) : while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

						<?php while ( bp_profile_fields() ) : bp_the_profile_field(); ?>

							<div class="editfield">

								<?php
								$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
								$field_type->edit_field_html();

								do_action( 'bp_custom_profile_edit_fields_pre_visibility' );

								if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>
									<p class="field-visibility-settings-toggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
										<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?> <a href="#" class="visibility-toggle-link"><?php _ex( 'Change', 'Change profile field visibility level', '__x__' ); ?></a>
									</p>

									<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id() ?>">
										<fieldset>
											<legend><?php _e( 'Who can see this field?', '__x__' ) ?></legend>

											<?php bp_profile_visibility_radio_buttons() ?>

										</fieldset>
										<a class="field-visibility-settings-close" href="#"><?php _e( 'Close', '__x__' ) ?></a>

									</div>
								<?php else : ?>
									<p class="field-visibility-settings-notoggle" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id() ?>">
										<?php printf( __( 'This field can be seen by: <span class="current-visibility-level">%s</span>', '__x__' ), bp_get_the_profile_field_visibility_level_label() ) ?>
									</p>
								<?php endif ?>

								<?php do_action( 'bp_custom_profile_edit_fields' ); ?>

								<p class="description"><?php bp_the_profile_field_description(); ?></p>

							</div>

						<?php endwhile; ?>

						<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="<?php bp_the_profile_group_field_ids(); ?>" />

						<input type="hidden" name="signup_usertype" id="signup_usertype" value="client" />

						<?php endwhile; endif; endif; ?>

						<?php do_action( 'bp_signup_profile_fields' ); ?>

					</div><!-- #basic-details-section -->
					<?php do_action( 'bp_after_signup_profile_fields' ); ?>

				<?php endif; ?>

				<label for="signup_password"><?php _e( 'Choose a Password', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
				<?php do_action( 'bp_signup_password_errors' ); ?>
				<div data-tip="Please include at least one lowercase letter, one uppercase letter, and a number">
					<input type="password" name="signup_password" id="signup_password" value="" />
				</div>
				<label for="signup_password_confirm"><?php _e( 'Confirm Password', '__x__' ); ?> <?php _e( '(required)', '__x__' ); ?></label>
				<?php do_action( 'bp_signup_password_confirm_errors' ); ?>
				<input type="password" name="signup_password_confirm" id="signup_password_confirm" value="" />
				<span id="password-strength"></span>
				<?php do_action( 'bp_before_registration_submit_buttons' ); ?>

				<div class="submit">
					<input style="float:none; margin:auto;" type="submit" name="signup_submit" id="signup_submit" value="<?php esc_attr_e( 'Sign Up', '__x__' ); ?>" />
				</div>
				<div class="separator">
					<span>OR</span>
				</div>
				<p style="text-align: center;">Already have an account? Click <a href="/wp-login.php">here</a> to login</p>
				<?php do_action( 'bp_after_registration_submit_buttons' ); ?>
				
				<?php
				}
				?>

			<?php wp_nonce_field( 'bp_new_signup' ); ?>

		<?php endif; // request-details signup step ?>

		<?php if ( 'completed-confirmation' == bp_get_current_signup_step() ) : ?>

			<?php do_action( 'template_notices' ); ?>
			<?php do_action( 'bp_before_registration_confirmed' ); ?>

			<?php if ( bp_registration_needs_activation() ) : ?>
				<p><?php _e( 'You have successfully created your account! To begin using this site you will need to activate your account via the email we have just sent to your address.', '__x__' ); ?></p>
			<?php else : ?>
				<p><?php _e( 'You have successfully created your account! Please log in using the username and password you have just created.', '__x__' ); ?></p>
			<?php endif; ?>

			<?php do_action( 'bp_after_registration_confirmed' ); ?>

		<?php endif; // completed-confirmation signup step ?>

		<?php do_action( 'bp_custom_signup_steps' ); ?>

		</form>

	</div>

	<?php do_action( 'bp_after_register_page' ); ?>

</div><!-- #buddypress -->

<script>
	var $ = jQuery
	var current = 1,current_step,next_step,steps;
	steps = $("fieldset").length;
  	
  	$(".next").click(function(){
	    current_step = $(this).parent();
	    next_step = $(this).parent().next();
	    next_step.show();
	    current_step.hide();
	    setProgressBar(++current);
  	});
  	
  	$(".previous").click(function(){
	    current_step = $(this).parent();
	    next_step = $(this).parent().prev();
	    next_step.show();
	    current_step.hide();
	    setProgressBar(--current);
 	});
  	setProgressBar(current);
	  // Change progress bar action
  	function setProgressBar(curStep){
	    var percent = parseFloat(100 / steps) * curStep;
	    percent = percent.toFixed();

	    $(".progress-bar").css("width",percent+"%").html(percent+"%");   
	}
	$( document ).ready(function() {
    	if($( "div" ).hasClass( "error" ))
    	{
    		$('div .error').css("color", "red");
    		$('#signup_form fieldset').not(':first').hide();
    		alert('There were errors with your application');
    		// $('fieldset').hide();
    		// $('div .error:first').closest('fieldset').show();
    	}
    	else
    	{
    		$('#signup_form fieldset').not(':first').hide();
    	}
	});
	
</script>
<!-- <style type="text/css">
  #signup_form fieldset:not(:first-of-type) {
    display: none;
  }
  </style> -->