<?php
/**
 * My Appointments
 *
 * Shows customer appointments on the My Account > Appointments page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/appointments.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     	https://docs.woocommerce.com/document/template-structure/
 * @version 	3.0.0
 * @since   	3.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$count = 0;

if ( ! empty( $tables ) ) : ?>

	<?php foreach ( $tables as $table_id => $table ) : ?>

		<h2><?php echo esc_html( $table['header'] ); ?></h2>

		<table class="shop_table my_account_appointments <?php echo $table_id . '_appointments'; ?>">
			<thead>
				<tr>
					<th scope="col" class="appointment-id"><?php _e( 'ID', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="scheduled-product"><?php _e( 'Scheduled', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="order-number"><?php _e( 'Order', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="appointment-when"><?php _e( 'When', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="appointment-duration"><?php _e( 'Duration', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="appointment-status"><?php _e( 'Status', 'woocommerce-appointments' ); ?></th>
					<th scope="col" class="appointment-cancel"></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $table['appointments'] as $appointment ) : ?>
					<?php $count++; ?>
					<tr>
						<td class="appointment-id"><?php echo $appointment->get_id(); ?></td>
						<td class="scheduled-product">
							<?php if ( $appointment->get_product() && $appointment->get_product()->is_type( 'appointment' ) ) : ?>
							<a href="<?php echo esc_url( get_permalink( $appointment->get_product()->get_id() ) ); ?>">
								<?php echo $appointment->get_product()->get_title(); ?>
							</a>
							<?php endif; ?>
						</td>
						<td class="order-number">
							<?php if ( $appointment->get_order() ) : ?>
								<?php if ( 'pending-confirmation' === $appointment->get_status() ) : ?>
									<?php echo $appointment->get_order()->get_order_number(); ?>
								<?php else : ?>
									<a href="<?php echo $appointment->get_order()->get_view_order_url(); ?>">
										<?php echo $appointment->get_order()->get_order_number(); ?>
									</a>
								<?php endif; ?>
							<?php endif; ?>
						</td>
						<td class="appointment-when"><?php echo $appointment->get_start_date(); ?></td>
						<td class="appointment-duration"><?php echo $appointment->get_duration(); ?></td>
						<td class="appointment-status"><?php echo esc_html( wc_appointments_get_status_label( $appointment->get_status() ) ); ?></td>
						<td class="appointment-cancel">
							<?php if ( $appointment->get_status() != 'cancelled' && $appointment->get_status() != 'completed' && ! $appointment->passed_cancel_day() ) : ?>
							<a href="<?php echo $appointment->get_cancel_url(); ?>" class="button cancel"><?php _e( 'Cancel', 'woocommerce-appointments' ); ?></a>
							<?php endif ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php do_action( 'woocommerce_before_account_appointments_pagination' ); ?>

		<div class="woocommerce-pagination woocommerce-pagination--without-numbers woocommerce-Pagination">
			<?php if ( 1 !== $page ) : ?>
				<a class="woocommerce-button woocommerce-button--previous woocommerce-Button woocommerce-Button--previous button" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $page - 1 ) );  ?>"><?php _e( 'Previous', 'woocommerce-appointments' ); ?></a>
			<?php endif; ?>

			<?php if ( $count >= $appointments_per_page ) : ?>
				<a class="woocommerce-button woocommerce-button--next woocommerce-Button woocommerce-Button--next button" href="<?php echo esc_url( wc_get_endpoint_url( $endpoint, $page + 1 ) ); ?>"><?php _e( 'Next', 'woocommerce-appointments' ); ?></a>
			<?php endif; ?>
		</div>

		<?php do_action( 'woocommerce_after_account_appointments_pagination' ); ?>

	<?php endforeach; ?>

<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php _e( 'Book', 'woocommerce-appointments' ); ?>
		</a>
		<?php _e( 'No appointments scheduled yet.', 'woocommerce-appointments' ); ?>
	</div>
<?php endif; ?>
