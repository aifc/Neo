<?php
/**
 * Customer appointment confirmed email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-appointment-confirmed.php.
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

<?php if ( $appointment->get_order() ) : ?>
	<p>
	<?php
	/* translators: 1: billing first name */
	printf( __( 'Hello %s', 'woocommerce-appointments' ), ( is_callable( array( $appointment->get_order(), 'get_billing_first_name' ) ) ? $appointment->get_order()->get_billing_first_name() : $appointment->get_order()->billing_first_name ) );
	?>
	</p>
<?php endif; ?>

<p><?php _e( 'Your appointment has been confirmed. The details of your appointment are shown below.', 'woocommerce-appointments' ); ?></p>

<table style="border:1px solid #eee; margin:0 0 16px; width:100%;" cellspacing="0" cellpadding="6" border="1" bordercolor="#eee">
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

<?php $order = $appointment->get_order(); ?>
<?php if ( $order ) : ?>

	<?php if ( 'pending' === $order->get_status() ) : ?>
		<p>
		<?php
		/* translators: 1: checkout payment url */
		printf( __( 'To pay for this appointment please use the following link: %s', 'woocommerce-appointments' ), '<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . __( 'Pay for appointment', 'woocommerce-appointments' ) . '</a>' );
		?>
		</p>
	<?php endif; ?>

	<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text ); ?>

	<h2>
	<?php

	$pre_wc_30 = version_compare( WC_VERSION, '3.0', '<' );
	if ( $pre_wc_30 ) {
		$order_date = $order->order_date;
	} else {
		$order_date = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
	}

	echo __( 'Order', 'woocommerce-appointments' ) . ': ' . $order->get_order_number();
	?>
	(
	<?php
	printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order_date ) ), date_i18n( wc_date_format(), strtotime( $order_date ) ) );
	?>
	)</h2>

	<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
		<thead>
			<tr>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Product', 'woocommerce-appointments' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Quantity', 'woocommerce-appointments' ); ?></th>
				<th scope="col" style="text-align:left; border: 1px solid #eee;"><?php _e( 'Price', 'woocommerce-appointments' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			switch ( $order->get_status() ) {
				case 'completed':
					echo $pre_wc_30 ? $order->email_order_items_table( array( 'show_sku' => false ) ) : wc_get_email_order_items( $order, array( 'show_sku' => false ) );
					break;
				case 'processing':
				default:
					echo $pre_wc_30 ? $order->email_order_items_table( array( 'show_sku' => true ) ) : wc_get_email_order_items( $order, array( 'show_sku' => true ) );
					break;
			}
			?>
		</tbody>
		<tfoot>
			<?php
			$totals = $order->get_order_item_totals();
			if ( $totals ) {
				$i = 0;
				foreach ( $totals as $total ) {
					$i++;
					?>
					<tr>
						<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php
						if ( 1 == $i ) {
							echo 'border-top-width: 4px;';
						}
						?>"><?php echo $total['label']; ?></th>
						<td style="text-align:left; border: 1px solid #eee; <?php
						if ( 1 == $i ) {
							echo 'border-top-width: 4px;';
						}
						?>"><?php echo $total['value']; ?></td>
					</tr>
					<?php
				}
			}
			?>
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>

	<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php endif; ?>

<?php do_action( 'woocommerce_email_footer' ); ?>
