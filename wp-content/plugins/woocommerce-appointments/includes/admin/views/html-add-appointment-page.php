<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap woocommerce">
	<h2><?php _e( 'Add Appointment', 'woocommerce-appointments' ); ?></h2>

	<p><?php _e( 'You can add a new appointment for a customer here. This form will create an appointment for the user, and optionally an associated order. Created orders will be marked as pending payment.', 'woocommerce-appointments' ); ?></p>

	<?php $this->show_errors(); ?>

	<form method="POST">
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="customer_id"><?php _e( 'Customer', 'woocommerce-appointments' ); ?></label>
					</th>
					<td>
						<input type="hidden" class="wc-customer-search" id="customer_id" name="customer_id" data-placeholder="<?php _e( 'Guest', 'woocommerce-appointments' ); ?>" data-allow_clear="true" style="width: 300px" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="appointable_product_id"><?php _e( 'Appointable Product', 'woocommerce-appointments' ); ?></label>
					</th>
					<td>
						<select id="appointable_product_id" name="appointable_product_id" class="chosen_select" style="width: 300px">
							<option value=""><?php _e( 'Select an appointable product...', 'woocommerce-appointments' ); ?></option>
							<?php foreach ( get_wc_appointment_products() as $product ) : ?>
								<option value="<?php echo $product->ID; ?>"><?php echo $product->post_title; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="create_order"><?php _e( 'Create Order', 'woocommerce-appointments' ); ?></label>
					</th>
					<td>
						<?php
						$always_create_order = apply_filters( 'woocommerce_appointments_always_create_order', false );
						if ( true == $always_create_order ) {
							?>
							<p>
								<label class="disabled">
									<input type="radio" name="appointment_order" value="new" class="checkbox disabled" checked="checked" readonly />
									<?php _e( 'Create a new corresponding order for this new appointment. Please note - the appointment will not be active until the order is processed/completed.', 'woocommerce-appointments' ); ?>
								</label>
							</p>
							<?php
						} else {
						?>
							<p>
								<label>
									<input type="radio" name="appointment_order" value="new" class="checkbox" />
									<?php _e( 'Create a new corresponding order for this new appointment. Please note - the appointment will not be active until the order is processed/completed.', 'woocommerce-appointments' ); ?>
								</label>
							</p>
							<p>
								<label>
									<input type="radio" name="appointment_order" value="existing" class="checkbox" />
									<?php _e( 'Assign this appointment to an existing order with this ID:', 'woocommerce-appointments' ); ?>
									<input type="number" name="appointment_order_id" value="" class="text" size="3" style="width: 80px;" />
								</label>
							</p>
							<p>
								<label>
									<input type="radio" name="appointment_order" value="" class="checkbox" checked="checked" />
									<?php _e( 'Don\'t create an order for this appointment.', 'woocommerce-appointments' ); ?>
								</label>
							</p>
						<?php } ?>
					</td>
				</tr>
				<?php do_action( 'woocommerce_appointments_after_create_appointment_page' ); ?>
				<tr valign="top">
					<th scope="row">&nbsp;</th>
					<td>
						<input type="submit" name="add_appointment" class="button-primary" value="<?php _e( 'Next', 'woocommerce-appointments' ); ?>" />
						<?php wp_nonce_field( 'add_appointment_notification' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>
