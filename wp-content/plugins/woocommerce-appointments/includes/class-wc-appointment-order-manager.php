<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles order status transitions and keeps appointments in sync
 */
class WC_Appointments_Order_Manager {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		// Add a "My Appointments" area to the My Account page.
		if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
			add_action( 'woocommerce_before_my_account', array( $this, 'endpoint_content' ) );
		} else {
			add_action( 'init', array( $this, 'add_endpoint' ) );
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'the_title', array( $this, 'endpoint_title' ) );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'my_account_menu_item' ) );
			add_action( 'woocommerce_account_' . $this->get_endpoint() . '_endpoint', array( $this, 'endpoint_content' ) );
			add_action( 'woocommerce_after_my_account', array( $this, 'legacy_account_page_content' ) );
		}

		// Complete appointment orders if virtual.
		add_action( 'woocommerce_payment_complete_order_status', array( $this, 'complete_order' ), 20, 2 );

		// When an order is processed or completed, we can mark publish the pending appointments.
		add_action( 'woocommerce_order_status_processing', array( $this, 'publish_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'publish_appointments' ), 10, 1 );

		// When an order is cancelled/fully refunded, cancel the appointments.
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'cancel_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'cancel_appointments_for_partial_refunds' ), 10, 1 );

		// Remove the appointment from the order when it's cancelled
		// Happens only if the appointment requires confirmation and the order contains multiple appointments
		// which require confirmation
		add_action( 'woocommerce_appointment_pending-confirmation_to_cancelled', array( $this, 'remove_cancelled_appointment' ) );

		// Status transitions
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );

		// Prevent pending being cancelled.
		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_cancel' ), 10, 2 );

		// Control the my orders actions.
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_actions' ), 10, 2 );

		// Sync order user with appointment user
		add_action( 'updated_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );
		add_action( 'added_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );
		add_action( 'woocommerce_appointment_in-cart_to_unpaid', array( $this, 'attach_new_user' ), 10, 1 );
		add_action( 'woocommerce_appointment_in-cart_to_pending-confirmation', array( $this, 'attach_new_user' ), 10, 1 );
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @since 2.1.4
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( $this->get_endpoint(), EP_ROOT | EP_PAGES );
	}

	/**
	 * Return the my-account page endpoint.
	 *
	 * @since 2.1.4
	 * @return string
	 */
	public function get_endpoint() {
		return apply_filters( 'woocommerce_appointments_account_endpoint', 'appointments' );
	}

	/**
	 * Add new query var.
	 *
	 * @since 2.1.4
	 * @param array $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = $this->get_endpoint();
		return $vars;
	}

	/**
	 * Change endpoint title.
	 *
	 * @since 2.1.4
	 * @param string $title
	 * @return string
	 */
	public function endpoint_title( $title ) {
		global $wp_query;
		$is_endpoint = isset( $wp_query->query_vars[ $this->get_endpoint() ] );

		if ( $is_endpoint && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
			$title = __( 'Appointment', 'woocommerce-appointments' );
			remove_filter( 'the_title', array( $this, 'endpoint_title' ) );
		}

		return $title;
	}

	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @since 2.1.4
	 * @param array $items
	 * @return array
	 */
	public function my_account_menu_item( $items ) {
		// Remove logout menu item.
		if ( array_key_exists( 'customer-logout', $items ) ) {
			$logout = $items['customer-logout'];
			unset( $items['customer-logout'] );
		}

		// Add appointments menu item.
		$items[ $this->get_endpoint() ] = __( 'Appointments', 'woocommerce-appointments' );

		// Add back the logout item.
		if ( isset( $logout ) ) {
			$items['customer-logout'] = $logout;
		}

		return $items;
	}

	/**
	 * Endpoint HTML content.
	 *
	 * @since 1.9.11
	 */
	public function endpoint_content() {
		$this->my_appointments();
	}

	/**
	 * Display the account page content for WooCommerce versions before 2.6
	 *
	 * @since 1.9.11
	 */
	public function legacy_account_page_content() {
		if ( version_compare( WC()->version, '2.6', '<' ) ) {
			$this->my_appointments();
		}
	}

	/**
	 * Show a customer appointments.
	 */
	public function my_appointments() {
		$current_time = current_time( 'YmdHis' );
		$user_id      = get_current_user_id();

		// Backwards Compatability for < WC 2.6
		$all_appointments_args = array(
			'post_status' => get_wc_appointment_statuses( 'user' ),
		);
		$all_appointments = WC_Appointments_Controller::get_appointments( $all_appointments_args );

		$upcoming_appointments_args = array(
			'orderby'       => 'start_date',
			'order'         => 'ASC',
			'meta_query'    => array(
				'relation' => 'AND',
				array(
					'key'     => '_appointment_customer_id',
					'value'   => absint( $user_id ),
					'compare' => 'IN',
				),
				'start_date'  => array(
					'key'     => '_appointment_start',
					'value'   => $current_time,
					'compare' => '>=',
				),
			),
			'post_status' => get_wc_appointment_statuses( 'user' ),
		);
		$upcoming_appointments = WC_Appointments_Controller::get_appointments( $upcoming_appointments_args );

		$past_appointments_args = array(
			'orderby'       => 'start_date',
			'order'         => 'ASC',
			'meta_query'    => array(
				'relation' => 'AND',
				array(
					'key'     => '_appointment_customer_id',
					'value'   => absint( $user_id ),
					'compare' => 'IN',
				),
				'start_date'  => array(
					'key'     => '_appointment_start',
					'value'   => $current_time,
					'compare' => '<=',
				),
			),
		);
		$past_appointments = WC_Appointments_Controller::get_appointments( $past_appointments_args );

		function _update_appointments_as_joinable($appointments_array) {
			// https://codex.wordpress.org/Function_Reference/current_time
			// The local time returned is based on the timezone set on the blog's General Settings page, which is UTC by default
			// current_time( 'timestamp' ) should be used in lieu of time() to return the blog's local time
			// In WordPress, PHP's time() will always return UTC and is the same as calling current_time( 'timestamp', true )
			$now = current_time( 'timestamp' );
			// Time offset in which the button to join an appointment appears
			$time_offset = 3600;
			foreach ($appointments_array as $appointment) {
				$start_time = get_object_vars($appointment)['start'];
				$appointment_joinable = ($now - $time_offset) < $start_time && $start_time < ($now + $time_offset);
				$appointment->joinable = $appointment_joinable;
			}
		}

		$tables = array();
		$user = wp_get_current_user();
		if ( in_array( 'shop_staff', (array) $user->roles ) ) {
			_update_appointments_as_joinable($all_appointments);
			if ( ! empty( $all_appointments ) ) {
				$tables['upcoming'] = array(
					'header'   => __( 'Your Appointments', 'woocommerce-appointments' ),
					'appointments' => $all_appointments,
				); 
			}
		}
		else {
			_update_appointments_as_joinable($upcoming_appointments);
			_update_appointments_as_joinable($past_appointments);
			if ( ! empty( $upcoming_appointments ) ) {
				$tables['upcoming'] = array(
					'header'   => __( 'Upcoming Appointments', 'woocommerce-appointments' ),
					'appointments' => $upcoming_appointments,
				); 
			}
			if ( ! empty( $past_appointments ) ) {
				$tables['past'] = array(
					'header'   => __( 'Past Appointments', 'woocommerce-appointments' ),
					'appointments' => $past_appointments,
				); 
			}
		}
		wc_get_template( 'myaccount/appointments.php', array( 'tables' => apply_filters( 'woocommerce_appointments_account_tables', $tables ) ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
	}

	/**
	 * Called when an order is paid
	 * @param  int $order_id
	 */
	public function publish_appointments( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );

		// Don't publish appointments for COD orders.
		if ( $order->has_status( 'processing' ) && 'cod' === $order->payment_method ) {
			return;
		}

		if ( class_exists( 'WC_Deposits' ) ) {
			// Is this a final payment?
			$parent_id = wp_get_post_parent_id( $order_id );
			if ( ! empty( $parent_id ) ) {
				$order = wc_get_order( $parent_id );
			}
		}

		$appointments = array();

		foreach ( $order->get_items() as $order_item_id => $item ) {
			if ( 'line_item' == $item['type'] ) {
				$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}
		}

		foreach ( $appointments as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );
			$appointment->paid();
		}
	}

	/**
	 * Complete virtual appointment orders
	 * @param $order_status
	 * @param $order_id
	 * @return string
	 */
	public function complete_order( $order_status, $order_id ) {
		$order = wc_get_order( $order_id );

		if ( 'processing' == $order_status && ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {

			$virtual_appointment_order = null;

			if ( count( $order->get_items() ) > 0 ) {

				foreach ( $order->get_items() as $item ) {

					if ( 'line_item' == $item['type'] ) {

						$_product = $order->get_product_from_item( $item );

						if ( ! $_product->is_virtual() || ! $_product->is_type( 'appointment' ) ) {
							// Once we've found one non-virtual product we know we're done, break out of the loop.
							$virtual_appointment_order = false;
							break;
						} else {
							$virtual_appointment_order = true;
						}
					}
				}
			}

			// Virtual order, mark as completed.
			if ( $virtual_appointment_order ) {
				return 'completed';
			}
		}

		// Deposits order status support.
		if ( class_exists( 'WC_Deposits' ) && 'partial-payment' === $order_status ) {
			global $wpdb;
			$appointments = array();
			foreach ( $order->get_items() as $order_item_id => $item ) {
				$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}

			foreach ( $appointments as $appointment_id ) {
				$appointment = new WC_Appointment( $appointment_id );
				$appointment->populated = true;
				$appointment->update_status( 'wc-partial-payment' );
			}
		}

		// Non-virtual order, return original status.
		return $order_status;
	}

	/**
	 * Cancel appointments with order
	 *
	 * @since 2.3.0 Introduced.
	 * @param $order_id
	 */
	public function cancel_appointments_for_partial_refunds( $order_id ) {
		global $wpdb;

		$order    = wc_get_order( $order_id );
		$appointments = array();

		// Prevents infinite loop during synchronization
		update_post_meta( $order_id, '_appointment_status_sync', true );

		foreach ( $order->get_items() as $order_item_id => $item ) {
			$refunded_qty = $order->get_qty_refunded_for_item( $order_item_id );
			if ( 'line_item' == $item['type'] && 0 !== $refunded_qty ) {
				$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}
		}

		foreach ( $appointments as $appointment_id ) {
			if ( get_post_meta( $appointment_id, '_appointment_status_sync', true ) ) {
				continue;
			}

			$appointment = get_wc_appointment( $appointment_id );
			$appointment->update_status( 'cancelled' );
		}

		WC_Cache_Helper::get_transient_version( 'appointments', true );
		delete_post_meta( $order_id, '_appointment_status_sync' );
	}

	/**
	 * Cancel appointments with order
	 * @param  int $order_id
	 */
	public function cancel_appointments( $order_id ) {
		global $wpdb;

		$order    = wc_get_order( $order_id );
		$appointments = array();

		// Prevents infinite loop during synchronization
		update_post_meta( $order_id, '_appointment_status_sync', true );

		foreach ( $order->get_items() as $order_item_id => $item ) {
			if ( 'line_item' == $item['type'] ) {
				$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
			}
		}

		foreach ( $appointments as $appointment_id ) {
			if ( get_post_meta( $appointment_id, '_appointment_status_sync', true ) ) {
				continue;
			}

			$appointment = get_wc_appointment( $appointment_id );
			$appointment->update_status( 'cancelled' );
		}

		WC_Cache_Helper::get_transient_version( 'appointments', true );
		delete_post_meta( $order_id, '_appointment_status_sync' );
	}

	/**
	 * Removes appointments related to the order being deleted.
	 *
	 * @param mixed $order_id ID of post being deleted
	 */
	public function delete_post( $order_id ) {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = wc_get_order( $order_id );
			$appointments = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_appointment_delete_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $appointments as $appointment_id ) {
				if ( get_post_meta( $appointment_id, '_appointment_delete_sync', true ) ) {
					continue;
				}

				wp_delete_post( $appointment_id, true );
			}

			delete_post_meta( $order_id, '_appointment_delete_sync' );
		}
	}

	/**
	 * Trash appointments with orders
	 *
	 * @param mixed $order_id
	 */
	public function trash_post( $order_id ) {
		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = wc_get_order( $order_id );
			$appointments = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_appointment_trash_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $appointments as $appointment_id ) {
				if ( get_post_meta( $appointment_id, '_appointment_trash_sync', true ) ) {
					continue;
				}

				wp_trash_post( $appointment_id );
			}

			delete_post_meta( $order_id, '_appointment_trash_sync' );
		}
	}

	/**
	 * Untrash appointments with orders
	 *
	 * @param mixed $order_id
	 */
	public function untrash_post( $order_id ) {
		if ( $order_id > 0 && 'shop_order' == get_post_type( $order_id ) ) {
			global $wpdb;

			$order    = wc_get_order( $order_id );
			$appointments = array();

			// Prevents infinite loop during synchronization
			update_post_meta( $order_id, '_appointment_untrash_sync', true );

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $appointments as $appointment_id ) {
				if ( get_post_meta( $appointment_id, '_appointment_untrash_sync', true ) ) {
					continue;
				}

				wp_untrash_post( $appointment_id );
			}

			delete_post_meta( $order_id, '_appointment_untrash_sync' );
		}
	}

	/**
	 * Stops WC cancelling unpaid appointments orders
	 * @param  bool $return
	 * @param  object $order
	 * @return bool
	 */
	public function prevent_cancel( $return, $order ) {
		if ( '1' === get_post_meta( $order->id, '_appointment_order', true ) ) {
			return false;
		}

		return $return;
	}

	/**
	 * My Orders custom actions.
	 * Remove the pay button when the appointment requires confirmation.
	 *
	 * @param  array $actions
	 * @param  WC_Order $order
	 * @return array
	 */
	public function my_orders_actions( $actions, $order ) {
		global $wpdb;

		if ( $order->has_status( 'pending' ) && 'wc-appointment-gateway' === $order->payment_method ) {
			$status = array();
			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$_status = $wpdb->get_col( $wpdb->prepare( "
						SELECT posts.post_status
						FROM {$wpdb->postmeta} AS postmeta
							LEFT JOIN {$wpdb->posts} AS posts ON (postmeta.post_id = posts.ID)
						WHERE postmeta.meta_key = '_appointment_order_item_id'
						AND postmeta.meta_value = %d
					", $order_item_id ) );

					$status = array_merge( $status, $_status );
				}
			}

			if ( in_array( 'pending-confirmation', $status ) && isset( $actions['pay'] ) ) {
				unset( $actions['pay'] );
			}
		}

		return $actions;
	}

	/**
	 * Sync customer between order + appointment
	 */
	public function updated_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		if ( '_customer_user' === $meta_key && 'shop_order' === get_post_type( $object_id ) ) {
			global $wpdb;

			$order = wc_get_order( $object_id );
			$appointments = array();

			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( 'line_item' == $item['type'] ) {
					$appointments = array_merge( $appointments, $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) ) );
				}
			}

			foreach ( $appointments as $appointment_id ) {
				update_post_meta( $appointment_id, '_appointment_customer_id', $_meta_value );
			}
		}
	}

	/**
	 * Attaches a newly created user (during checkout) to an appointment
	 */
	function attach_new_user( $appointment_id ) {
		if ( 0 === (int) get_post_meta( $appointment_id, '_appointment_customer_id', true ) && get_current_user_id() > 0 ) {
			update_post_meta( $appointment_id, '_appointment_customer_id', get_current_user_id() );
		}
	}

	/**
	 * Removes the appointment from an order
	 * when the order includes only appointments which require confirmation
	 *
	 * @param int $appointment_id
	 */
	public function remove_cancelled_appointment( $appointment_id ) {
		$appointment  = get_wc_appointment( $appointment_id );
		$order    	  = $appointment->get_order();

		if ( ! empty( $order ) && is_array( $order->get_items() ) ) {
			foreach ( $order->get_items() as $order_item_id => $item ) {
				if ( $item[ __( 'Appointment ID', 'woocommerce-appointments' ) ] == $appointment_id ) {
					wc_delete_order_item( $order_item_id );
					$order->calculate_totals();
					$order->add_order_note( sprintf( __( 'The product %1$s has been removed from the order because the appointment #%2$d cannot be confirmed.', 'woocommerce-appointments' ), $item['name'], $appointment_id ), true );
				}
			}
		}
	}
}

$GLOBALS['wc_appointments_order_manager'] = new WC_Appointments_Order_Manager();
