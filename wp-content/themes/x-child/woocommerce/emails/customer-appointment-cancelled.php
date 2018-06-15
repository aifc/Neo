<?php
/**
 * Customer appointment cancelled email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-appointment-cancelled.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @version     1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php
	$appointment_type = get_post_meta( $appointment->id, 'appointment_type', true );
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php _e( 'Your appointment with Neo has been cancelled. The details of the appointment can be found below.', 'woocommerce-appointments' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin:0 0 16px;" border="1" bordercolor="#eee">
	<tbody>
		<?php require( __DIR__ . '/client-appointment-information.php'); ?>
	</tbody>
</table>

<p><?php _e( 'Please contact OzHelp on (02) 6251 4166 to make another booking.', 'woocommerce-appointments' ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
