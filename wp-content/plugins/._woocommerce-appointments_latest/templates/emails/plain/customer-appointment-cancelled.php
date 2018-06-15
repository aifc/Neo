<?php
/**
 * PLAIN Customer appointment confirmed email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-appointment-cancelled.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     	https://docs.woocommerce.com/document/template-structure/
 * @version 	2.1.0
 * @since   	3.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

echo '= ' . $email_heading . " =\n\n";

if ( $appointment->get_order() ) {
	/* translators: 1: billing first name */
	echo sprintf( __( 'Hello %s', 'woocommerce-appointments' ), ( is_callable( array( $appointment->get_order(), 'get_billing_first_name' ) ) ? $appointment->get_order()->get_billing_first_name() : $appointment->get_order()->billing_first_name ) ) . "\n\n";
}

echo __( 'We are sorry to say that your appointment could not be confirmed and has been cancelled. The details of the cancelled appointment can be found below.', 'woocommerce-appointments' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: 1: appointment product title */
echo sprintf( __( 'Scheduled Product: %s', 'woocommerce-appointments' ), $appointment->get_product()->get_title() ) . "\n";
/* translators: 1: appointment ID */
echo sprintf( __( 'Appointment ID: %s', 'woocommerce-appointments' ), $appointment->get_id() ) . "\n";
/* translators: 1: appointment start date */
echo sprintf( __( 'Appointment Date: %s', 'woocommerce-appointments' ), $appointment->get_start_date() ) . "\n";
/* translators: 1: appointment duration */
echo sprintf( __( 'Appointment Duration: %s', 'woocommerce-appointments' ), $appointment->get_duration() ) . "\n";

$staff = $appointment->get_staff_members( $names = true );
if ( $appointment->has_staff() && $staff ) {
	/* translators: 1: appointment staff names */
	echo sprintf( __( 'Appointment Providers: %s', 'woocommerce-appointments' ), $staff ) . "\n";
}

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo __( 'Please contact us if you have any questions or concerns.', 'woocommerce-appointments' ) . "\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
