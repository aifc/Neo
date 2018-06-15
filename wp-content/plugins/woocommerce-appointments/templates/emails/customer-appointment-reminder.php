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

<?php do_action( 'woocommerce_email_header', $email_heading ); ?>

<?php if ( $appointment->get_order() ) : ?>
	<p><?php printf( __( 'Hello %s', 'woocommerce-appointments' ), $appointment->get_order()->billing_first_name ); ?></p>
<?php endif; ?>

<?php if ( $appointment->get_start_date( wc_date_format(), '' ) == date( wc_date_format() ) ) : ?>
	<p><?php _e( 'This is a reminder that your appointment will take place today.', 'woocommerce-appointments' ); ?></p>
<?php else : ?>
	<p><?php _e( 'This is a reminder that your appointment will take place tomorrow.', 'woocommerce-appointments' ); ?></p>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
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

<?php do_action( 'woocommerce_email_footer' ); ?>
