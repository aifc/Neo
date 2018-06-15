<?php
/**
 * Customer appointment notification
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-appointment-notification.php.
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

<?php echo wpautop( wptexturize( $notification_message ) ); ?>

<table style="border:1px solid #eee; width:100%;" cellspacing="0" cellpadding="6" border="1" bordercolor="#eee">
	<tbody>
		<tr>
			<th style="text-align:left; border:1px solid #eee;" scope="row"><?php _e( 'Scheduled Product', 'woocommerce-appointments' ); ?></th>
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

<?php do_action( 'woocommerce_email_footer' ); ?>
