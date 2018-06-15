<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Deposits integration class.
 *
 * Last compatibility check: WooCommerce Deposits 1.3.4
 */
class WC_Appointments_Integration_Deposits {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'handle_partial_payment' ), 20, 2 );
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'handle_completed_payment' ), 40, 2 );
		add_action( 'init', array( $this, 'register_custom_post_status' ) );
		add_filter( 'woocommerce_appointments_get_wc_appointment_statuses', array( $this, 'add_custom_status' ) );
		add_filter( 'woocommerce_appointments_get_status_label', array( $this, 'add_custom_status' ) );
	}

	/**
	 * Process partial payments
	 *
	 * @since 3.5.6
	 *
	 * @param string  $order_status to be changed for filter.
	 * @param integer $order_id to state which order we're working with.
	 */
	public function handle_partial_payment( $order_status, $order_id ) {
		// Deposits order status support.
		if ( 'partial-payment' === $order_status ) {
			$this->set_status_for_appointments_in_order( $order_id, 'wc-partial-payment' );
		}

		return $order_status;
	}

	/**
	 * Go through all appointment for an order and update the status for each.
	 *
	 * @since 3.5.6
	 *
	 * @param integer $order_id To find appointments.
	 * @param string  $new_status To set to appointments of order.
	 */
	public function set_status_for_appointments_in_order( $order_id, $new_status ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order_id );

		foreach ( $appointment_ids as $appointment_id ) {
			$appointment = new WC_Appointment( $appointment_id );
			$appointment->set_status( $new_status );
			$appointment->save();
		}
	}

	/**
	 * Process partial payments.
	 *
	 * @since 3.5.6
	 *
	 * @param string  $order_status To filter/change.
	 * @param integer $order_id To which this applies.
	 */
	public function handle_completed_payment( $order_status, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return $order_status;
		}
		if ( 'processing' !== $order_status
			|| ! $order->has_status( 'pending-deposit' ) ) {
			return $order_status;
		}

		if ( count( $order->get_items() ) < 1 ) {
			return $order_status;
		}
		$virtual_appointment_order = false;

		foreach ( $order->get_items() as $item ) {
			if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
				if ( 'line_item' === $item['type'] ) {
					$product               = $order->get_product_from_item( $item );
					$virtual_appointment_order = $product && $product->is_virtual() && $product->is_type( 'appointment' );
				}
			} else {
				if ( $item->is_type( 'line_item' ) ) {
					$product               = $item->get_product();
					$virtual_appointment_order = $product && $product->is_virtual() && $product->is_type( 'appointment' );
				}
			}
			if ( ! $virtual_appointment_order ) {
				break;
			}
		}

		// Virtual order, mark as completed.
		if ( $virtual_appointment_order ) {
			return 'completed';
		}

		return $order_status;
	}

	/**
	 * Register the Deposits integration post status.
	 *
	 * @since 3.5.6
	 */
	public function register_custom_post_status() {
		if ( is_admin() && isset( $_GET['post_type'] ) && 'wc_appointment' === $_GET['post_type'] ) {
			register_post_status( 'wc-partial-payment', array(
				'label'                     => '<span class="status-partial-payment tips" data-tip="' . _x( 'Partially Paid', 'woocommerce-appointments', 'woocommerce-appointments' ) . '">' . _x( 'Partially Paid', 'woocommerce-appointments', 'woocommerce-appointments' ) . '</span>',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: 1: count, 2: count */
				'label_count'               => _n_noop( 'Partially Paid <span class="count">(%s)</span>', 'Partially Paid <span class="count">(%s)</span>', 'woocommerce-appointments' ),
			) );
		}
	}

	/**
	 * Add custom status to the list of standard appointments status.
	 *
	 * @since 3.5.6
	 *
	 * @param array $statuses to be changed in this function.
	 */
	public function add_custom_status( $statuses ) {
		$statuses['wc-partial-payment'] = __( 'Partially Paid','woocommerce-deposits' );
		return $statuses;
	}
}

new WC_Appointments_Integration_Deposits();
