<?php
/**
 * Admin appointment cancelled email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-appointment-cancelled.php.
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
?>

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<p><?php _e( 'The following appointment has been cancelled. The details of the cancelled appointment can be found below.', 'woocommerce-appointments' ); ?></p>

<table style="border:1px solid #eee; margin:0 0 16px; width:100%;" cellspacing="0" cellpadding="6" border="1" bordercolor="#eee">
	<tbody>
		<?php if ( $appointment->get_product() ) : ?>
			<tr>
				<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Scheduled Product', 'woocommerce-appointments' ); ?></th>
				<td style="text-align:left; border:1px solid #eee;"><?php echo $appointment->get_product()->get_title(); ?></td>
			</tr>
		<?php endif; ?>
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

<p>
<?php
/* translators: 1: a href to appointment */
echo make_clickable( sprintf( __( 'You can view and edit this appointment in the dashboard here: %s', 'woocommerce-appointments' ), admin_url( 'post.php?post=' . $appointment->get_id() . '&action=edit' ) ) );
?>
</p>

<?php do_action( 'woocommerce_email_footer' ); ?>
