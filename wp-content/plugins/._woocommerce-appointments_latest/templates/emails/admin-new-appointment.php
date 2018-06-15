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
 * @see     	https://docs.woocommerce.com/document/template-structure/
 * @version 	3.1.0
 * @since   	3.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( wc_appointment_order_requires_confirmation( $appointment->get_order() ) && $appointment->get_status() == 'pending-confirmation' ) {
	/* translators: 1: billing first and last name */
	$opening_paragraph = __( 'An appointment has been made by %s and is awaiting your approval. The details of this appointment are as follows:', 'woocommerce-appointments' );
} else {
	/* translators: 1: billing first and last name */
	$opening_paragraph = __( 'An new appointment has been made by %s. The details of this appointment are as follows:', 'woocommerce-appointments' );
}

do_action( 'woocommerce_email_header', $email_heading );

$order = $appointment->get_order();

if ( $order ) {
	if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
		$first_name = $order->billing_first_name;
		$last_name = $order->billing_last_name;
	} else {
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
	}
}
?>

<?php if ( $appointment->get_order() && ! empty( $first_name ) && ! empty( $last_name ) ) : ?>
	<p><?php printf( $opening_paragraph, $first_name . ' ' . $last_name ); ?></p>
<?php endif; ?>

<table style="border:1px solid #eee; margin:0 0 16px; width:100%;" cellspacing="0" cellpadding="6" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th style="text-align:left; border:1px solid #eee;" scope="row" ><?php _e( 'Scheduled Product', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border:1px solid #eee;"><?php echo $appointment->get_product()->get_title(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Appointment ID', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border:1px solid #eee;"><?php echo $appointment->get_id(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Appointment Date', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border:1px solid #eee;"><?php echo $appointment->get_start_date(); ?></td>
		</tr>
		<tr>
			<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Appointment Duration', 'woocommerce-appointments' ); ?></th>
			<td style="text-align:left; border:1px solid #eee;"><?php echo $appointment->get_duration(); ?></td>
		</tr>
		<?php $staff = $appointment->get_staff_members( $names = true ); ?>
		<?php if ( $appointment->has_staff() && $staff ) : ?>
			<tr>
				<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Appointment Providers', 'woocommerce-appointments' ); ?></th>
				<td style="text-align:left; border:1px solid #eee;"><?php echo $staff; ?></td>
			</tr>
		<?php endif; ?>
	</tbody>
</table>

<?php if ( wc_appointment_order_requires_confirmation( $appointment->get_order() ) && $appointment->get_status() == 'pending-confirmation' ) : ?>
<p><?php _e( 'This appointment is awaiting your approval. Please check it and inform the customer if the date is available or not.', 'woocommerce-appointments' ); ?></p>
<?php endif; ?>

<p>
<?php
/* translators: 1: a href to appointment */
echo make_clickable( sprintf( __( 'You can view and edit this appointment in the dashboard here: %s', 'woocommerce-appointments' ), admin_url( 'post.php?post=' . $appointment->get_id() . '&action=edit' ) ) );
?>
</p>

<?php do_action( 'woocommerce_email_footer' ); ?>
