<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WC_Appointments_Admin_Customer_Meta_Box class.
 */
class WC_Appointments_Admin_Customer_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 * @var array
	 */
	public $post_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'woocommerce-customer-data';
		$this->title      = __( 'Customer details', 'woocommerce-appointments' );
		$this->context    = 'side';
		$this->priority   = 'default';
		$this->post_types = array( 'wc_appointment' );
	}

	/**
	 * Meta box content.
	 */
	 public function meta_box_inner( $post ) {
 		global $appointment;

 		if ( ! is_a( $appointment, 'WC_Appointment' ) || $appointment->get_id() !== $post->ID ) {
 			$appointment = new WC_Appointment( $post->ID );
 		}
 		$has_data = false;
 		?>
 		<table class="appointment-customer-details">
 			<?php
			$appointment_customer_id = $appointment->get_customer_id();
			$user                    = $appointment_customer_id ? get_user_by( 'id', $appointment_customer_id ) : false;

			if ( $appointment_customer_id && $user ) {
			?>
				<tr>
					<th><?php esc_html_e( 'Name:', 'woocommerce-appointments' ); ?></th>
					<td><?php echo esc_html( $user->last_name && $user->first_name ? $user->first_name . ' ' . $user->last_name : '&mdash;' ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Email:', 'woocommerce-appointments' ); ?></th>
					<td><?php echo make_clickable( sanitize_email( $user->user_email ) ); ?></td>
				</tr>
				<tr class="view">
					<th>&nbsp;</th>
					<td><a class="button button-small" target="_blank" href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . absint( $user->ID ) ) ); ?>"><?php echo esc_html( 'View User', 'woocommerce-appointments' ); ?></a></td>
				</tr>
				<?php
				$has_data = true;
			}

			$appointment_order_id = $appointment->get_order_id();
			$order                = $appointment_order_id ? wc_get_order( $appointment_order_id ) : false;

			if ( $appointment_order_id && $order ) {
			?>
				<tr>
					<th><?php esc_html_e( 'Address:', 'woocommerce-appointments' ); ?></th>
					<td><?php echo wp_kses( $order->get_formatted_billing_address() ? $order->get_formatted_billing_address() : __( 'No billing address set.', 'woocommerce-appointments' ), array( 'br' => array() ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Email:', 'woocommerce-appointments' ); ?></th>
					<td><?php echo make_clickable( sanitize_email( is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email ) ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Phone:', 'woocommerce-appointments' ); ?></th>
					<td><?php echo esc_html( is_callable( array( $order, 'get_billing_phone' ) ) ? $order->get_billing_phone() : $order->billing_phone ); ?></td>
				</tr>
				<tr class="view">
					<th>&nbsp;</th>
					<td><a class="button button-small" target="_blank" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $appointment->get_order_id() ) . '&action=edit' ) ); ?>"><?php echo esc_html( 'View Order', 'woocommerce-appointments' ); ?></a></td>
				</tr>
				<?php
				$has_data = true;
			}

			if ( has_action( 'woocommerce_admin_appointment_data_after_customer_details' ) ) {
				do_action( 'woocommerce_admin_appointment_data_after_customer_details', $post->ID );
				$has_data = true;
			}

			if ( ! $has_data ) {
				?>
				<tr>
					<td colspan="2"><?php esc_html_e( 'N/A', 'woocommerce-appointments' ); ?></td>
				</tr>
				<?php
			}
 			?>
 		</table>
 		<?php
 	}
}

return new WC_Appointments_Admin_Customer_Meta_Box();
