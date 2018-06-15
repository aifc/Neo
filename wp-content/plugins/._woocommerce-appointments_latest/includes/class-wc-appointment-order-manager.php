<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles order status transitions and keeps appointments in sync
 */
class WC_Appointments_Order_Manager {

	/**
	 * ID being synced.
	 *
	 * @var array
	 */
	private static $syncing_ids = array();

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'appointment_display' ), 10, 3 );

		// Order again button.
		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'order_again_button' ) );

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
		add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'complete_order' ), 20, 2 );

		// When an order is processed or completed, we can mark publish the pending appointments.
		add_action( 'woocommerce_order_status_processing', array( $this, 'publish_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'publish_appointments' ), 10, 1 );

		// When an order is cancelled/fully refunded, cancel the appointments.
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'cancel_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_refunded', array( $this, 'cancel_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_partially_refunded', array( $this, 'cancel_appointments_for_partial_refunds' ), 10, 1 );

		// When an order is cancelled/fully refunded, cancel the appointments.
		add_action( 'woocommerce_order_status_completed_to_pending', array( $this, 'unpaid_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed_to_on-hold', array( $this, 'unpaid_appointments' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed_to_failed', array( $this, 'unpaid_appointments' ), 10, 1 );

		// Status transitions
		add_action( 'before_delete_post', array( $this, 'delete_post' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_post' ) );
		add_action( 'untrash_post', array( $this, 'untrash_post' ) );
		add_action( 'woocommerce_appointment_cancelled', array( $this, 'maybe_cancel_order' ) );
		add_action( 'woocommerce_appointment_paid', array( $this, 'maybe_process_order' ) );
		add_action( 'woocommerce_appointment_unpaid', array( $this, 'maybe_pending_order' ) );

		// Prevent pending being cancelled.
		add_filter( 'woocommerce_cancel_unpaid_order', array( $this, 'prevent_cancel' ), 10, 2 );

		// Control the my orders actions.
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_orders_actions' ), 10, 2 );

		// Sync order user with appointment user
		add_action( 'woocommerce_appointment_in-cart_to_unpaid', array( $this, 'attach_new_user' ), 10, 2 );
		add_action( 'woocommerce_appointment_in-cart_to_pending-confirmation', array( $this, 'attach_new_user' ), 10, 2 );

		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			add_action( 'updated_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );
			add_action( 'added_post_meta', array( $this, 'updated_post_meta' ), 10, 4 );
		} else {
			add_action( 'woocommerce_order_object_updated_props', array( $this, 'sync_appointment_customer_id' ), 10, 2 );
		}

		// Failed Order Management.
		add_action( 'woocommerce_order_status_failed', array( $this, 'schedule_failed_order_event' ) );
		add_action( 'woocommerce_appointments_failed_order_expired', array( $this, 'handle_failed_order_scheduled_event' ), 20, 2 );
	}

	/**
	 * Show appointment data if a line item is linked to a appointment ID.
	 */
	public function appointment_display( $item_id, $item, $order ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_item_id( $item_id );

		wc_get_template( 'order/appointment-display.php', array(
			'appointment_ids' => $appointment_ids,
			'endpoint'        => $this->get_endpoint(),
		), '', WC_APPOINTMENTS_TEMPLATE_PATH );
	}

	/**
	 * Remove order again button for appointment orders.
	 */
	public function order_again_button( $order ) {
		if ( ! $order || ! $order->has_status( 'completed' ) || ! is_user_logged_in() ) {
			return;
		}

		if ( count( $order->get_items() ) > 0 ) {
			$appointment_order = 0;
			foreach ( $order->get_items() as $item ) {
				if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
					if ( 'line_item' === $item['type'] ) {
						$product           = $order->get_product_from_item( $item );
						$appointment_order = $product && $product->is_type( 'appointment' );
					}
				} else {
					if ( $item->is_type( 'line_item' ) ) {
						$product           = $item->get_product();
						$appointment_order = $product && $product->is_type( 'appointment' );
					}
				}
			}
		}

		if ( $appointment_order ) {
			return;
		}

		wc_get_template( 'order/order-again.php', array(
			'order' => $order,
		) );
	}

	/**
	 * Is ID being synced?
	 */
	private static function is_syncing( $id ) {
		return in_array( $id, self::$syncing_ids );
	}

	/**
	 * Store ID on sync.
	 */
	private static function syncing_start( $id ) {
		self::$syncing_ids[] = $id;
	}

	/**
	 * Remove ID on sync completion.
	 */
	private static function syncing_stop( $id ) {
		self::$syncing_ids = array_diff( self::$syncing_ids, array( $id ) );
	}

	/**
	 * Register new endpoint to use inside My Account page.
	 *
	 * @since 2.1.4
	 * @see https://developer.wordpress.org/reference/functions/add_rewrite_endpoint/
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( $this->get_endpoint(), EP_PAGES );
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
	 * @param int $current_page
	 *
	 * @since    1.9.11
	 * @version  3.4.0
	 */
	public function endpoint_content( $current_page ) {
		$current_page = empty( $current_page ) ? 1 : absint( $current_page );
		$this->my_appointments( $current_page );
	}

	/**
	 * Display the account page content for WooCommerce versions before 2.6
	 *
	 * @since   1.9.11
	 */
	public function legacy_account_page_content() {
		if ( version_compare( WC()->version, '2.6', '<' ) ) {
			$this->my_appointments();
		}
	}

	/**
	* Show a users appointments in My Account > Appointments.
	*
	* @param int $current_page
	* @since    2.0.0
	* @version  3.4.0
	 */
	public function my_appointments( $current_page = 0 ) {
		$user_id      = get_current_user_id();

		if ( version_compare( WC()->version, '2.6.0', '>=' ) ) {
			$appointments_per_page = apply_filters( 'woocommerce_appointments_my_appointments_per_page', 10 );

			$past_appointments = WC_Appointments_Controller::get_appointments_for_user( $user_id, apply_filters( 'woocommerce_appointments_my_appointments_past_query_args', array(
				'order_by'       => apply_filters( 'woocommerce_appointments_my_appointments_past_order_by', 'start_date' ),
				'order'          => 'ASC',
				'date_before'    => current_time( 'timestamp' ),
				'offset'         => ( $current_page - 1 ) * $appointments_per_page,
				'limit'          => $appointments_per_page,
			) ) );

			$upcoming_appointments = WC_Appointments_Controller::get_appointments_for_user( $user_id, apply_filters( 'woocommerce_appointments_my_appointments_upcoming_query_args', array(
				'order_by'       => apply_filters( 'woocommerce_appointments_my_appointments_upcoming_order_by', 'start_date' ),
				'order'          => 'ASC',
				'date_after'     => current_time( 'timestamp' ),
				'offset'         => ( $current_page - 1 ) * $appointments_per_page,
				'limit'          => $appointments_per_page,
			) ) );

			#var_dump( $upcoming_appointments );

			$tables = array();
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

			wc_get_template(
				'myaccount/appointments.php',
				apply_filters( 'woocommerce_appointments_my_appointments_template_args', array(
					'tables'                => apply_filters( 'woocommerce_appointments_account_tables', $tables ),
					'page'                  => $current_page,
					'endpoint'              => $this->get_endpoint(),
					'appointments_per_page' => $appointments_per_page,
				) ),
			'', WC_APPOINTMENTS_TEMPLATE_PATH );
		} else {
			$all_appointments = WC_Appointments_Controller::get_appointments_for_user( $user_id );

			if ( ! empty( $all_appointments ) ) {
				wc_get_template( 'myaccount/appointments.php', array( 'appointments' => $all_appointments ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
			}
		}
	}

	/**
	 * Called when an order is paid
	 * @param  int $order_id
	 */
	public function publish_appointments( $order_id ) {
		global $wpdb;

		$order          = wc_get_order( $order_id );
		$payment_method = is_callable( array( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : $order->payment_method;

		$order_id = apply_filters( 'woocommerce_appointments_publish_appointments_order_id', $order_id );

		if ( class_exists( 'WC_Deposits' ) ) {
			// Is this a final payment?
			$parent_id = wp_get_post_parent_id( $order_id );
			if ( ! empty( $parent_id ) ) {
				$order_id = $parent_id;
			}
		}

		$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order_id );

		// Don't publish appointments for COD orders, but still schedule their events
		$no_publish = $order->has_status( 'processing' ) && 'cod' === $payment_method;

		foreach ( $appointments as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );

			if ( $no_publish ) {
				$appointment->maybe_schedule_event( 'reminder' );
				$appointment->maybe_schedule_event( 'complete' );
			} else {
				$appointment->paid();
			}
		}
	}

	/**
	 * Complete virtual appointment orders.
	 *
	 * $order_id = apply_filters( 'woocommerce_appointments_publish_appointments_order_id', $order_id );
	 *
	 * @param $order_status
	 * @param $order_id
	 * @return string
	 */
	 public function complete_order( $order_status, $order_id ) {
 		$order = wc_get_order( $order_id );
 		if ( 'processing' === $order_status
 			&& $order->has_status( array( 'on-hold', 'pending', 'failed' ) ) ) {
 			$virtual_appointment_order = false;

 			if ( count( $order->get_items() ) < 1 ) {
 				return $order_status;
 			}

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
 			// virtual order, mark as completed
 			if ( $virtual_appointment_order ) {
 				return 'completed';
 			}
 		}

 		// non-virtual order, return original status
 		return $order_status;
 	}

	/**
	 * Cancel appointments with order.
	 * @param  int $order_id
	 */
	public function cancel_appointments( $order_id ) {
		$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order_id );

		foreach ( $appointments as $appointment_id ) {
			if ( self::is_syncing( $appointment_id ) ) {
				continue;
			}
			$appointment = get_wc_appointment( $appointment_id );
			$appointment->update_status( 'cancelled' );
		}

		self::syncing_stop( $order_id );
	}

	/**
	 * Cancel appointments when an order refunded partially.
	 *
	 * @since 2.7.0
	 * @version 2.7.0
	 * @param int $order_id Order ID.
	 */
	public function cancel_appointments_for_partial_refunds( $order_id ) {
		global $wpdb;

		$order              	= wc_get_order( $order_id );
		$cancelled_appointments = array();

		// Prevents infinite loop during synch.
		update_post_meta( $order_id, '_appointment_status_sync', true );

		// Collect appointment IDs where refunded qty matches with its order item
		// being refunded.
		foreach ( $order->get_items() as $order_item_id => $item ) {
			$refunded_qty = $order->get_qty_refunded_for_item( $order_item_id );
			if ( 'line_item' === $item['type'] && 0 !== $refunded_qty ) {
				$appointment_id = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d", $order_item_id ) );

				if ( $appointment_id ) {
					$cancelled_appointments = array_merge(
						$cancelled_appointments,
						$appointment_id
					);
				}
			}
		}

		// Update all cancelled appointments.
		foreach ( $cancelled_appointments as $appointment_id ) {
			// Skip appointment that still in synch state.
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
	 * Unpaid appointments with order.
	 * @param  int $order_id
	 */
	public function unpaid_appointments( $order_id ) {
		$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order_id );

		foreach ( $appointments as $appointment_id ) {
			if ( self::is_syncing( $appointment_id ) ) {
				continue;
			}
			$appointment = get_wc_appointment( $appointment_id );
			$appointment->update_status( 'unpaid' );
		}

		self::syncing_stop( $order_id );
	}

	/**
	 * Removes appointments related to the order being deleted.
	 *
	 * @param mixed $post_id ID of post being deleted
	 */
	public function delete_post( $post_id ) {
		if ( ! current_user_can( 'delete_posts' ) || ! $post_id ) {
			return;
		}

		if ( 'wc_appointment' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$order_id = WC_Appointment_Data_Store::get_appointment_order_id( $post_id );
			$order = wc_get_order( $order_id );
			$item_count = is_a( $order, 'WC_Order' ) ? count( $order->get_items() ) : 0;

			if ( 1 === $item_count && $order_id && ! self::is_syncing( $order_id ) ) {
				wp_delete_post( $order_id, true );
			}

			$this->clear_cron_hooks( (int) $post_id );

			self::syncing_stop( $post_id );
		}

		if ( 'shop_order' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $post_id );

			foreach ( $appointments as $appointment_id ) {
				if ( self::is_syncing( $appointment_id ) ) {
					continue;
				}
				wp_delete_post( $appointment_id, true );
			}

			self::syncing_stop( $post_id );
		}
	}

	/**
	 * Trash appointments with orders
	 *
	 * @param mixed $post_id
	 */
	public function trash_post( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		if ( 'wc_appointment' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$order_id = WC_Appointment_Data_Store::get_appointment_order_id( $post_id );
			$order = wc_get_order( $order_id );
			$item_count = is_a( $order, 'WC_Order' ) ? count( $order->get_items() ) : 0;

			// only delete this order if this appointment is the only item in it
			if ( 1 === $item_count && $order_id && ! self::is_syncing( $order_id ) ) {
				wp_trash_post( $order_id );
			}

			self::syncing_stop( $post_id );
		}

		if ( 'shop_order' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $post_id );

			foreach ( $appointments as $appointment_id ) {
				if ( self::is_syncing( $appointment_id ) ) {
					continue;
				}
				wp_trash_post( $appointment_id );
			}

			self::syncing_stop( $post_id );
		}
	}

	/**
	 * Untrash appointments with orders
	 *
	 * @param mixed $post_id
	 */
	public function untrash_post( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		if ( 'wc_appointment' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$order_id = WC_Appointment_Data_Store::get_appointment_order_id( $post_id );

			if ( $order_id && ! self::is_syncing( $order_id ) ) {
				wp_untrash_post( $order_id );
			}

			self::syncing_stop( $post_id );
		}

		if ( 'shop_order' === get_post_type( $post_id ) ) {
			self::syncing_start( $post_id );

			$appointments = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $post_id );

			foreach ( $appointments as $appointment_id ) {
				if ( self::is_syncing( $appointment_id ) ) {
					continue;
				}
				wp_untrash_post( $appointment_id );
			}

			self::syncing_stop( $post_id );
		}
	}

	/**
	 * Clear cron hooks for appointment
	 *
	 * @param mixed $post_id
	 */
	function clear_cron_hooks( $post_id ) {
		wp_clear_scheduled_hook( 'wc-appointment-reminder', array( $post_id ) );
		wp_clear_scheduled_hook( 'wc-appointment-complete', array( $post_id ) );
		wp_clear_scheduled_hook( 'wc-appointment-remove-inactive-cart', array( $post_id ) );
	}

	/**
	 * Stops WC cancelling unpaid appointments orders.
	 *
	 * @param  bool $return
	 * @param  object $order
	 * @return bool
	 */
	public function prevent_cancel( $return, $order ) {
		$created_via    = is_callable( array( $order, 'get_created_via' ) ) ? $order->get_created_via() : $order->created_via;
		$payment_method = is_callable( array( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : $order->payment_method;

		if ( 'appointments' === $created_via || 'wc-appointment-gateway' === $payment_method ) {
			return false;
		}
		return $return;
	}

	/**
	 * My Orders custom actions.
	 *
	 * Remove the pay button when the appointment requires confirmation.
	 * @hooked woocommerce_my_account_my_orders_actions
	 * @param  array $actions
	 * @param  WC_Order $order
	 * @return array
	 */
	public function my_orders_actions( $actions, $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return $actions;
		}
		$payment_method = is_callable( array( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : $order->payment_method;

		if ( $order->has_status( 'pending' ) && 'wc-appointment-gateway' === $payment_method ) {
			$status = array();

			foreach ( $order->get_items() as $order_item_id => $item ) {
				$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_item_id( $order_item_id );
				if ( $appointment_ids ) {
					foreach ( $appointment_ids as $appointment_id ) {
						$appointment  = new WC_Appointment( $appointment_id );
						$status[] = $appointment->get_status();
					}
				}
			}

			if ( in_array( 'pending-confirmation', $status ) && isset( $actions['pay'] ) ) {
				unset( $actions['pay'] );
			}
		}

		return $actions;
	}

	/**
	 * For 3.0, triggered after an order is updated.
	 * @param  WC_Order $order
	 * @param  array $props
	 */
	public function sync_appointment_customer_id( $order, $props ) {
		if ( in_array( 'customer_id', $props ) ) {
			$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( ( is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id ) );

			foreach ( $appointment_ids as $appointment_id ) {
				$appointment = new WC_Appointment( $appointment_id );

				if ( $appointment->get_customer_id() !== $order->get_customer_id() ) {
					$appointment->set_customer_id( $order->get_customer_id() );
					$appointment->save();
				}
			}
		}

	}

	/**
	 * Sync customer between order + appointment. 2.6 and below.
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
	 * Attaches a newly created user (during checkout) to a appointment
	 */
	public function attach_new_user( $appointment_id, $appointment ) {
		if ( 0 === $appointment->get_customer_id() && get_current_user_id() > 0 ) {
			$appointment->set_customer_id( get_current_user_id() );
			$appointment->save();
		}
	}

	/**
	 * Cancel order with appointments.
	 *
	 * @param  int $appointment_id
	 */
	public function maybe_cancel_order( $appointment_id ) {
		$this->maybe_update_order( $appointment_id, 'cancelled' );
	}

	/**
	 * Sync order with appointments (paid) only in case where the appointment is the only item in the order.
	 *
	 * @param  int $appointment_id
	 */
	public function maybe_process_order( $appointment_id ) {
		$this->maybe_update_order( $appointment_id, 'processing' );
	}

	/**
	 * Sync order with appointments (unpaid) only in case where the appointment is the only item in the order.
	 *
	 * @param  int $appointment_id
	 */
	public function maybe_pending_order( $appointment_id ) {
		$this->maybe_update_order( $appointment_id, 'pending' );
	}

	/**
	 * Update appointment's related order
	 * This only applies if the order has only the appointment as an item.
	 *
	 *
	 * @param  int $appointment_id
	 * @param  string $status
	 */
	 private function maybe_update_order( $appointment_id, $status ) {
 		// Prevents infinite loop during synchronization
 		self::syncing_start( $appointment_id );

 		$order_id  = WC_Appointment_Data_Store::get_appointment_order_id( $appointment_id );
 		$order     = wc_get_order( $order_id );

 		if ( ! is_a( $order, 'WC_Order' ) ) {
 			self::syncing_stop( $appointment_id );
 			return;
 		}

 		$completed = 'processing' === $status && 'completed' === $order->get_status();
 		$refunded  = 'cancelled' === $status && 'refunded' === $order->get_status();

 		// Do not update status of completed or refunded orders.
 		if ( self::is_syncing( $order_id ) || $completed || $refunded ) {
 			self::syncing_stop( $appointment_id );
 			return;
 		}

 		// Only update status if the order has 1 appointment
 		if ( 1 === count( $order->get_items() ) ) {
 			$order->update_status( $status );
 		} elseif ( 'cancelled' === $status ) {
 			$appointment_ids = WC_Appointment_Data_Store::get_order_contains_only_appointments( $order );

 			if ( empty( $appointment_ids ) ) {
 				self::syncing_stop( $appointment_id );
 				return;
 			}

 			$appointment_statuses = array_map( function( $appointment_id ) {
 				return get_wc_appointment( $appointment_id )->get_status();
 			}, $appointment_ids );

 			// Cancel the order only if all Appointments are cancelled
 			if ( array( $status ) === array_unique( $appointment_statuses ) ) {
 				$order->update_status( $status );
 			}
 		}

 		self::syncing_stop( $appointment_id );
 	}

	/**
	 * Scheduling an event for a failed order if it has appointments.
	 *
	 * @since 3.5.6
	 *
	 * @param integer $order_id The order that failed.
	 */
	public function schedule_failed_order_event( $order_id ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order_id );
		if ( empty( $appointment_ids ) ) {
			return;
		}
		$args = array( 'order_id' => $order_id, 'appointment_ids' => $appointment_ids );
		$timestamp = apply_filters( 'woocommerce_appointments_failed_order_expire_scheduled_time_stamp', current_time( 'timestamp' ) + ( 7 * DAY_IN_SECONDS ) );
		wp_schedule_single_event( $timestamp, 'woocommerce_appointments_failed_order_expired', $args );
	}

	/**
	 * Responding to scheduled event for a failed appointment.
	 *
	 * @since 3.5.6
	 *
	 * @param integer $order_id The order that failed.
	 * @param array   $appointment_ids Attached to failed order.
	 */
	public function handle_failed_order_scheduled_event( $order_id = 0, $appointment_ids = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! is_a( $order, 'WC_Order' )
			|| 'failed' !== $order->get_status()
			|| empty( $appointment_ids ) ) {
			return;
		}

		foreach ( $appointment_ids as $appointment_id ) {
			$appointment = get_wc_appointment( $appointment_id );
			$appointment->set_status( 'cancelled' );
			$appointment->save();
		}
	}
}

$GLOBALS['wc_appointments_order_manager'] = new WC_Appointments_Order_Manager();
