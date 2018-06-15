<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WC_Appointments_Admin_Staff_Profile {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'show_user_profile', array( $this, 'add_staff_meta_fields' ), 20 );
		add_action( 'edit_user_profile', array( $this, 'add_staff_meta_fields' ), 20 );

		add_action( 'personal_options_update', array( $this, 'save_staff_meta_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_staff_meta_fields' ) );

		add_action( 'profile_update', array( $this, 'validate_user_data' ), 11, 2 );
		add_action( 'delete_user', array( $this, 'delete_staff' ), 11 );
	}

	/**
	 * Show meta box
	 */
	public function add_staff_meta_fields( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		wp_enqueue_script( 'wc_appointments_writepanel_js' );
		?>
		<style type="text/css">
			#minor-publishing-actions, #visibility { display:none }
		</style>
		<h3 id="staff-details"><?php _e( 'Staff details', 'woocommerce-appointments' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="_wc_appointment_staff_qty"><?php _e( 'Capacity', 'woocommerce-appointments' ); ?></label></th>
				<td>
					<?php
						$staff_capacity = ( $staff_capacity = get_user_meta( $user->ID, '_wc_appointment_staff_qty', true ) ) ? $staff_capacity : '';
					?>
					<input type="number" name="_wc_appointment_staff_qty" id="_wc_appointment_staff_qty" value="<?php echo $staff_capacity; ?>" step="1" min="1">
					<?php echo wc_help_tip( __( 'The maximum number of appointments per slot at any given time for any product assigned. Overrides product capacity.', 'woocommerce-appointments' ) ); ?>
				</td>
			</tr>
			<tr>
				<th><label><?php _e( 'Custom Availability', 'woocommerce-appointments' ); ?></label></th>
				<td>
					<div class="woocommerce">
						<div class="panel-wrap" id="appointments_availability">
							<div class="table_grid">
								<table class="widefat">
									<thead>
										<tr>												
											<th class="sort" width="1%">&nbsp;</th>
											<th class="range_type"><?php esc_html_e( 'Range type', 'woocommerce-appointments' ); ?></th>
											<th class="range_name"><?php esc_html_e( 'Range', 'woocommerce-appointments' ); ?></th>
											<th class="range_name2"></th>
											<th class="range_capacity"><?php esc_html_e( 'Capacity', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'The maximum number of appointments per slot. Overrides general product capacity.', 'woocommerce-appointments' ) ); ?></th>
											<th class="range_appointable"><?php esc_html_e( 'Appointable', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'If not appointable, users won\'t be able to choose slots in this range for their appointment.', 'woocommerce-appointments' ) ); ?></th>
											<th class="remove" width="1%">&nbsp;</th>
										</tr>
									</thead>
									<tbody id="availability_rows">
										<?php
											$values = get_user_meta( $user->ID, '_wc_appointment_availability', true );
											if ( ! empty( $values ) && is_array( $values ) ) {
												foreach ( $values as $availability ) {
													include( 'views/html-appointment-availability-fields.php' );
												}
											}
										?>
									</tbody>
									<tfoot>
										<tr>
											<th colspan="8">
												<a href="#" class="button add_row" data-row="<?php
													ob_start();
													include( 'views/html-appointment-availability-fields.php' );
													$html = ob_get_clean();
													echo esc_attr( $html );
												?>"><?php esc_html_e( 'Add Rule', 'woocommerce-appointments' ); ?></a>
												<span class="description"><?php esc_html_e( get_wc_appointment_rules_explanation() ); ?></span>
											</th>
										</tr>
									</tfoot>
								</table>
							</div>
							<div class="clear"></div>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save handler
	 */
	public function save_staff_meta_fields( $user_id ) {
		// Availability.
		$availability = array();
		$row_size     = isset( $_POST['wc_appointment_availability_type'] ) ? count( $_POST['wc_appointment_availability_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST['wc_appointment_availability_type'][ $i ] );
			$availability[ $i ]['appointable'] = wc_clean( $_POST['wc_appointment_availability_appointable'][ $i ] );
			$availability[ $i ]['qty'] = wc_clean( $_POST['wc_appointment_availability_qty'][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
				break;
				case 'months' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_month'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_month'][ $i ] );
				break;
				case 'weeks' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_week'][ $i ] );
				break;
				case 'days' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_day_of_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_day_of_week'][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );
				break;
				case 'time:range' :
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );

					$availability[ $i ]['from_date'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to_date']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
				break;
			}
		}
		update_user_meta( $user_id, '_wc_appointment_availability', $availability );

		// Capacity.
		$staff_capacity = isset( $_POST['_wc_appointment_staff_qty'] ) ? wc_clean( $_POST['_wc_appointment_staff_qty'] ) : '';
		update_user_meta( $user_id, '_wc_appointment_staff_qty', $staff_capacity );
	}

	/**
	 * Save validation check (if staff, validate their appointments)
	 */
	function validate_user_data( $user_id, $old_user_data ) {
		$user_meta = get_userdata( $user_id );

		// Check roles if user is shop staff.
		if ( in_array( 'shop_staff', (array) $user_meta->roles ) ) {
			// If user is shop staff get all appointments of him/her and validate the rules include all statuses but complete
			$current_date = date( 'YmdHis' );
			$appointments_args = array(
				'meta_query' => array(
					array(
						'key' => '_appointment_staff_id',
						'value' => absint( $user_id ),
					),
				),
				'post_status' => get_wc_appointment_statuses( 'validate' ),
				'meta_query' => array(
					'start_date'  => array(
						'key'     => '_appointment_start',
						'value'   => $current_date,
						'compare' => '>=',
					),
				),
			);
			$latest_staff_appointments = WC_Appointments_Controller::get_appointments( $appointments_args );
			foreach ( $latest_staff_appointments as $staff_appointment ) {
				WC_Appointments_Controller::validate_appointment( $staff_appointment );
			}
		}
	}

	/**
	 * Actions to be done when staff is deleted
	 */
	public function delete_staff( $user_id ) {
		$user_meta = get_userdata( $user_id );

		// Check roles if user is shop staff.
		if ( in_array( 'shop_staff', (array) $user_meta->roles ) ) {
			// Get all staff appointments and remove staff from them.
			$appointments_args = array(
				'meta_query' => array(
					array(
						'key' => '_appointment_staff_id',
						'value' => absint( $user_id ),
					),
				),
				'post_status' => get_wc_appointment_statuses( 'validate' ),
			);
			$staff_appointments = WC_Appointments_Controller::get_appointments( $appointments_args );
			if ( ! empty( $staff_appointments ) ) {
				foreach ( $staff_appointments as $staff_appointment ) {
					delete_post_meta( $staff_appointment->id, '_appointment_staff_id' );
				}
			}

			// Get all products that current staff is assigned to and remove him/her from product (revert the relational db table and post meta logic in class-wc-appointments-admin.php on line 559-593)
			$staff_products = wc_appointment_get_appointable_products_for_staff( $user_id );
			if ( ! empty( $staff_products ) ) {
				foreach ( $staff_products as $staff_product ) {
					wc_appointment_remove_staff_from_product( $user_id, $staff_product->id );
				}
			}

		// Check roles if user is shop staff.
		} elseif ( in_array( 'customer', (array) $user_meta->roles ) ) {
			$customer_appointments_args = array(
				'meta_query' => array(
					array(
						'key' => '_appointment_customer_id',
						'value' => absint( $user_id ),
						'compare' => 'IN',
					),
				),
				'post_status' => get_wc_appointment_statuses( 'user' ),
			);
			$customer_appointments = WC_Appointments_Controller::get_appointments( $customer_appointments_args );
			if ( ! empty( $customer_appointments ) ) {
				foreach ( $customer_appointments as $customer_appointment ) {
					delete_post_meta( $customer_appointment->id, '_appointment_customer_id' );
				}
			}
		}
	}
}

return new WC_Appointments_Admin_Staff_Profile();
