<div id="buddypress">

	<?php do_action( 'bp_before_activation_page' ); ?>

	<div class="page" id="activate-page">

		<?php do_action( 'template_notices' ); ?>

		<?php do_action( 'bp_before_activate_content' ); ?>

		<?php if ( bp_account_was_activated() ) : ?>

			<?php if ( isset( $_GET['e'] ) ) : ?>
				<p><?php _e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', '__x__' ); ?></p>
			<?php else : ?>
				<div id="activation-image" style="text-align: center" >
					<img src="//mychristiancounsellor.org.au/wp-content/uploads/2017/10/aifc-logo.png" alt="">
				</div>'.
     			<div id ="activation-approved" style="text-align: center">
     				<h5>Account Activation Successful</h5>
     				<p>Your account is pending approval.</p><p>You will be notified when your account is approved by an administrator.</p> 
     			</div>
			<?php endif; ?>

		<?php else : ?>
			
			<form action="" method="get" class="standard-form cf man" id="activation-form">

				<label for="key"><?php _e( 'Please provide a valid activation key.', '__x__' ); ?></label>
				<input type="text" name="key" id="key" value="" />

				<p class="submit">
					<input type="submit" name="submit" value="<?php esc_attr_e( 'Activate', '__x__' ); ?>" />
				</p>

			</form>

		<?php endif; ?>

		<?php do_action( 'bp_after_activate_content' ); ?>

	</div><!-- .page -->

	<?php do_action( 'bp_after_activation_page' ); ?>

</div><!-- #buddypress -->