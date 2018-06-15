<?php
/**
 * Admin new appointment email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-appointment.php.
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
if ( wc_appointment_order_requires_confirmation( $appointment->get_order() ) && $appointment->get_status() == 'pending-confirmation' ) {
	$opening_paragraph = __( 'An appointment has been made by %s and is awaiting your approval. The details of this appointment are as follows:', 'woocommerce-appointments' );
} else {
	$opening_paragraph = __( 'An new appointment has been made by %s. The details of this appointment are as follows:', 'woocommerce-appointments' );
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php if ( $appointment->get_order() && $appointment->get_order()->billing_first_name && $appointment->get_order()->billing_last_name ) : ?>
	<p><?php printf( $opening_paragraph, $appointment->get_order()->billing_first_name . ' ' . $appointment->get_order()->billing_last_name ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin:0 0 16px;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th scope="row" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Scheduled Product', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_product()->get_title(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment ID', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_id(); ?></td>
		</tr>
		<?php if ( $appointment->has_staff() && ( $staff = $appointment->get_staff_members( $names = true ) ) ) : ?>
			<tr>
				<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Providers', 'woocommerce-appointments' ); ?></th>
				<td style="text-align:left; border: 1px solid #eee;"><?php echo $staff; ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Date', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_start_date( wc_date_format(), '' ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Time', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_start_date( '', wc_time_format() ) . ' &mdash; ' . $appointment->get_end_date( '', wc_time_format() ); ?></td>
		</tr>
	</tbody>
</table>

<?php if ( wc_appointment_order_requires_confirmation( $appointment->get_order() ) && $appointment->get_status() == 'pending-confirmation' ) : ?>
<p><?php _e( 'This appointment is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'woocommerce-appointments' ); ?></p>
<?php endif; ?>

<p><?php echo make_clickable( sprintf( __( 'You can view and edit this appointment in the dashboard here: %s', 'woocommerce-appointments' ), admin_url( 'post.php?post=' . $appointment->get_id() . '&action=edit' ) ) ); ?></p>

<?php do_action( 'woocommerce_email_footer' ); ?>
