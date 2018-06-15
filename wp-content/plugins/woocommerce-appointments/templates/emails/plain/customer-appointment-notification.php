<?php
/**
 * PLAIN Customer appointment notification email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-appointment-notification.php.
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
echo "= " . $email_heading . " =\n\n";

echo strip_tags( $notification_message );

echo __( 'The details of your appointment are shown below.', 'woocommerce-appointments' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Scheduled: %s', 'woocommerce-appointments' ), $appointment->get_product()->get_title() ) . "\n";
echo sprintf( __( 'Appointment ID: %s', 'woocommerce-appointments' ), $appointment->get_id() ) . "\n";

if ( $appointment->has_staff() && ( $staff = $appointment->get_staff_members( $names = true ) ) ) {
	echo sprintf( __( 'Appointment Providers: %s', 'woocommerce-appointments' ), $staff ) . "\n";
}

echo sprintf( __( 'Appointment Date: %s', 'woocommerce-appointments' ), $appointment->get_start_date( wc_date_format(), '' ) ) . "\n";
echo sprintf( __( 'Appointment Time: %s', 'woocommerce-appointments' ), $appointment->get_start_date( '', wc_time_format() ) . ' &mdash; ' . $appointment->get_end_date( '', wc_time_format() ) ) . "\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
