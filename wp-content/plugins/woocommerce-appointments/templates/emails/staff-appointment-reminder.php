<?php
/**
 * Customer appointment reminder email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-appointment-reminder.php.
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

<?php if ( $appointment->get_start_date( wc_date_format(), '' ) == date( wc_date_format() ) ) : ?>
	<p><?php _e( 'This is a reminder that your appointment will take place today.', 'woocommerce-appointments' ); ?></p>
<?php else : ?>
	<p><?php _e( 'This is a reminder that your appointment will take place tomorrow.', 'woocommerce-appointments' ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
	<tbody>
		<?php require( __DIR__ . '/client-appointment-information.php'); ?>
		<?php require( __DIR__ . '/information-about-client.php'); ?>
	</tbody>
</table>

<?php do_action( 'woocommerce_email_footer' ); ?>
