<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main model class for all appointments.
 */
class WC_Appointment extends WC_Appointments_Data {

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'all_day'                  => false,
		'cost'                     => 0,
		'customer_id'              => 0,
		'date_created'             => '',
		'date_modified'            => '',
		'start'                    => '',
		'end'                      => '',
		'google_calendar_event_id' => 0,
		'order_id'                 => 0,
		'order_item_id'            => 0,
		'parent_id'                => 0,
		'product_id'               => 0,
		'staff_id'                 => 0,
		'staff_ids'                => '',
		'status'                   => 'unpaid',
		'customer_status'          => 'expected',
		'qty'		    		   => 1,
	);

	/**
	 * Stores meta in cache for future reads.
	 *
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = 'appointment';

	/**
	 * Which data store to load.
	 *
	 * @var string
	 */
	protected $data_store_name = 'appointment';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'appointment';


	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @since 3.0.0
	 * @version 3.0.0
	 *
	 * @var bool|array False if it's not transitioned. Otherwise an array containing
	 *                 transitioned status 'from' and 'to'.
	 */
	protected $status_transitioned = false;

	/**
	 * Constructor, possibly sets up with post or id belonging to existing appointment
	 * or supplied with an array to construct a new appointment.
	 *
	 * @version  3.3.0
	 * @param    int|array|obj $appointment
	 */
	public function __construct( $appointment = 0 ) {
		parent::__construct( $appointment );

		if ( is_array( $appointment ) ) {
			if ( isset( $appointment['user_id'] ) ) {
				$appointment['customer_id'] = $appointment['user_id'];
			}

			if ( isset( $appointment['start_date'] ) ) {
				$appointment['start'] = $appointment['start_date'];
			}

			if ( isset( $appointment['end_date'] ) ) {
				$appointment['end'] = $appointment['end_date'];
			}

			// Inherit data from parent.
			if ( ! empty( $appointment['parent_id'] ) ) {
				$parent_appointment = new WC_Appointment( $appointment['parent_id'] );

				if ( empty( $appointment['order_item_id'] ) ) {
					$appointment['order_item_id'] = $parent_appointment->data_store->get_appointment_order_item_id( $parent_appointment->get_id() );
				}
				if ( empty( $appointment['customer_id'] ) ) {
					$appointment['customer_id'] = $parent_appointment->data_store->get_appointment_customer_id( $parent_appointment->get_id() );
				}
			}

			// Get order ID from order item
			if ( ! empty( $appointment['order_item_id'] ) ) {
				if ( function_exists( 'wc_get_order_id_by_order_item_id' ) ) {
					$appointment['order_id'] = wc_get_order_id_by_order_item_id( $appointment['order_item_id'] );
				} else {
					global $wpdb;
					$appointment['order_id'] = (int) $wpdb->get_var( $wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
						$appointment['order_item_id']
					) );
				}
			}

			// Get user ID.
			if ( empty( $appointment['customer_id'] ) && is_user_logged_in() && ! is_admin() ) {
				$appointment['customer_id'] = get_current_user_id();
			}

			// Setup the required data for the current user
			if ( empty( $appointment['user_id'] ) ) {
				if ( is_user_logged_in() && ! is_admin() ) {
					$appointment['user_id'] = get_current_user_id();
				}
			}

			$this->set_props( $appointment );
			$this->set_object_read( true );
		} elseif ( is_numeric( $appointment ) && $appointment > 0 ) {
			$this->set_id( $appointment );
		} elseif ( $appointment instanceof self ) {
			$this->set_id( $appointment->get_id() );
		} elseif ( ! empty( $appointment->ID ) ) {
			$this->set_id( $appointment->ID );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
			// For existing appointment: avoid doing the transition(default unpaid to the actual state);
			$this->status_transitioned = false;
		}
	}

	/**
	 * Save data to the database.
	 *
	 * @return int appointment ID
	 */
	public function save() {
		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Allows you to adjust object props before save.
			do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
		}
		WC_Cache_Helper::get_transient_version( 'appointments', true );
		$this->status_transition();
		$this->schedule_events();
		return $this->get_id();
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		if ( $this->status_transitioned ) {
			$allowed_statuses = array(
				'was-in-cart' => __( 'Was In Cart', 'woocommerce-appointments' ),
			);

			$allowed_statuses = array_unique( array_merge(
				$allowed_statuses,
				get_wc_appointment_statuses( null, true ),
				get_wc_appointment_statuses( 'user', true ),
				get_wc_appointment_statuses( 'cancel', true )
			) );

			$from = ! empty( $allowed_statuses[ $this->status_transitioned['from'] ] )
				? $allowed_statuses[ $this->status_transitioned['from'] ]
				: false;

			$to = ! empty( $allowed_statuses[ $this->status_transitioned['to'] ] )
				? $allowed_statuses[ $this->status_transitioned['to'] ]
				: false;

			if ( $from && $to ) {
				$this->status_transitioned_handler( $from, $to );
			}

			// This has ran, so reset status transition variable.
			$this->status_transitioned = false;
		}
	}

	/**
	 * Skip status transition events.
	 *
	 * Allows self::status_transition to be bypassed before calling self::save().
	 *
	 * @since 3.0.0
	 * @version 3.0.0
	 */
	public function skip_status_transition_events() {
		$this->status_transitioned = false;
	}

	/**
	 * Handler when appointment status is transitioned.
	 *
	 * @since 3.0.0
	 *
	 * @param string $from Status from.
	 * @param string $to   Status to.
	 */
	protected function status_transitioned_handler( $from, $to ) {
		// Add note to related order.
		$order = $this->get_order();

		if ( $order ) {
			/* translators: 1: appointment id 2: old status 3: new status */
			$order->add_order_note( sprintf( __( 'Appointment #%1$d status changed from "%2$s" to "%3$s"', 'woocommerce-appointments' ), $this->get_id(), $from, $to ) );
		}

		// Fire the events of valid status has been transitioned.
		do_action( 'woocommerce_appointment_' . $this->status_transitioned['to'], $this->get_id(), $this );
		do_action( 'woocommerce_appointment_' . $this->status_transitioned['from'] . '_to_' . $this->status_transitioned['to'], $this->get_id(), $this );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters and setters.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all_day.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_all_day( $context = 'view' ) {
		return $this->get_prop( 'all_day', $context );
	}

	/**
	 * Set all_day.
	 *
	 * @param boolean $value
	 */
	public function set_all_day( $value ) {
		$this->set_prop( 'all_day', wc_appointments_string_to_bool( $value ) );
	}

	/**
	 * Get cost.
	 *
	 * @param  string $context
	 * @return float
	 */
	public function get_cost( $context = 'view' ) {
		return $this->get_prop( 'cost', $context );
	}

	/**
	 * Set cost.
	 *
	 * @param float $value
	 */
	public function set_cost( $value ) {
		$this->set_prop( 'cost', wc_format_decimal( $value ) );
	}

	/**
	 * Get customer_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_id', $context );
	}

	/**
	 * Set customer_id.
	 *
	 * @param integer $value
	 */
	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_id', absint( $value ) );
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set date_created.
	 *
	 * @param string $timestamp Timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_created( $timestamp ) {
		$this->set_prop( 'date_created', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get date_modified.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set date_modified.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_date_modified( $timestamp ) {
		$this->set_prop( 'date_modified', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get end_time.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_end( $context = 'view', $deprecated = '' ) {
		$end = $this->get_prop( 'end', $context );

		return $this->is_all_day() ? strtotime( 'midnight +1 day -1 second', $end ) : $end;
	}

	/**
	 * Set end_time.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_end( $timestamp ) {
		$this->set_prop( 'end', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Get google_calendar_event_id.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_google_calendar_event_id( $context = 'view' ) {
		return $this->get_prop( 'google_calendar_event_id', $context );
	}

	/**
	 * Set google_calendar_event_id
	 *
	 * @param string $value
	 */
	public function set_google_calendar_event_id( $value ) {
		$this->set_prop( 'google_calendar_event_id', $value );
	}

	/**
	 * Get order ID.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Set order_id
	 *
	 * @param  int $value
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Get order_item_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_order_item_id( $context = 'view' ) {
		return $this->get_prop( 'order_item_id', $context );
	}

	/**
	 * Set order_item_id.
	 *
	 * @param integer $value
	 */
	public function set_order_item_id( $value ) {
		$this->set_prop( 'order_item_id', absint( $value ) );
	}

	/**
	 * Get parent ID.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Set parent ID.
	 *
	 * @param  int $value
	 */
	public function set_parent_id( $value ) {
		$this->set_prop( 'parent_id', absint( $value ) );
	}

	/**
	 * Get product_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Set product_id.
	 *
	 * @param integer $value
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Return the staff_ids without wc- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_staff_ids( $context = 'view' ) {
		return $this->get_prop( 'staff_ids', $context );
	}

	/**
	 * Set staff_ids (from admin page).
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_staff_ids( $new_staff_ids ) {
		// Each 'staff_id' is saved in appointment data store
		$this->set_prop( 'staff_ids', $new_staff_ids );
	}

	/**
	 * Set staff_ids (from front page).
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_staff_id( $new_staff_id ) {
		// Each 'staff_id' is saved in appointment data store
		$this->set_prop( 'staff_ids', $new_staff_id );
	}

	/**
	 * Get start_time.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_start( $context = 'view', $deprecated = '' ) {
		$start = $this->get_prop( 'start', $context );

		return $this->is_all_day() ? strtotime( 'midnight', $start ) : $start;
	}

	/**
	 * Set start_time.
	 *
	 * @param string $timestamp
	 * @throws WC_Data_Exception
	 */
	public function set_start( $timestamp ) {
		$this->set_prop( 'start', is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp ) );
	}

	/**
	 * Return the status without wc- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Set status.
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_status( $new_status ) {
		$old_status = $this->get_status();

		$this->set_prop( 'status', $new_status );

		if ( $new_status !== $old_status ) {
			$this->status_transitioned = array(
				'from' => $old_status,
				'to'   => $new_status,
			);
		}

		return array(
			'from' => $old_status,
			'to'   => $new_status,
		);
	}

	/**
	 * Return the customer_status without wc- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_customer_status( $context = 'view' ) {
		return $this->get_prop( 'customer_status', $context );
	}

	/**
	 * Set customer_status.
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_customer_status( $new_customer_status ) {
		// Set status to "no-show", when appointment is past current time, is not paid and customer status is set to "expected".
		if (
			'expected' === $this->get_customer_status() &&
		    ! in_array( $this->get_status(), array( 'paid', 'complete' ) ) &&
			$this->get_start() < current_time( 'timestamp' ) &&
			$this->get_end() < current_time( 'timestamp' )
		) {
			$new_customer_status = 'no-show';
		}

		$this->set_prop( 'customer_status', $new_customer_status );
	}

	/**
	 * Return the qty without wc- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_qty( $context = 'view' ) {
		return $this->get_prop( 'qty', $context );
	}

	/**
	 * Set qty.
	 *
	 * @param string $new_status Status to change the order to. No internal wc- prefix is required.
	 * @return array details of change
	 */
	public function set_qty( $new_qty ) {
		$this->set_prop( 'qty', $new_qty );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditonals
	|--------------------------------------------------------------------------
	*/

	/**
	 * Checks the appointment status against a passed in status.
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'woocommerce_appointment_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * Return if all day event.
	 *
	 * @return boolean
	 */
	public function is_all_day() {
		return $this->get_all_day();
	}

	/**
	 * See if this appointment is within a slot.
	 *
	 * @return boolean
	 */
	public function is_within_slot( $slot_start, $slot_end ) {
		if ( ! $this->get_start() || ! $this->get_end() || $this->get_start() >= $slot_end || $this->get_end() <= $slot_start ) {
			return false;
		}
		return true;
	}

	/**
	 * See if this appointment is scheduled on a date.
	 *
	 * @return boolean
	 */
	public function is_scheduled_on_day( $slot_start, $slot_end ) {
		$is_scheduled        = false;
		$loop_date        = $this->get_start();
		$multiday_appointment = date( 'Y-m-d', $this->get_start() ) < date( 'Y-m-d', $this->get_end() );

		if ( $multiday_appointment ) {
			if ( date( 'YmdHi', $slot_end ) > date( 'YmdHi', $this->get_start() ) && date( 'YmdHi', $slot_start ) < date( 'YmdHi', $this->get_end() ) ) {
				$is_scheduled = true;
			} else {
				$is_scheduled = false;
			}
		} else {
			while ( $loop_date <= $this->get_end() ) {
				if ( date( 'Y-m-d', $loop_date ) === date( 'Y-m-d', $slot_start ) ) {
					$is_scheduled = true;
				}
				$loop_date = strtotime( '+1 day', $loop_date );
			}
		}

		/**
		 * Filter the appointment objects is_scheduled_on_day method return result.
		 *
		 * @since 1.9.13
		 *
		 * @param bool $is_scheduled
		 * @param WC_Appointment $appointment
		 * @param WC_Appointment $slot_start
		 * @param WC_Appointment $slot_end
		 */
		return apply_filters( 'woocommerce_appointment_is_scheduled_on_day', $is_scheduled, $this, $slot_start, $slot_end );
	}

	/**
	 * See if this appointment can still be cancelled by the user or not.
	 *
	 * @return boolean
	 */
	public function passed_cancel_day() {
		$appointment = $this->get_product();

		if ( ! $appointment || ! $appointment->can_be_cancelled() ) {
			return true;
		}

		if ( false !== $appointment ) {
			$cancel_limit      = $appointment->get_cancel_limit();
			$cancel_limit_unit = $cancel_limit > 1 ? $appointment->get_cancel_limit_unit() . 's' : $appointment->get_cancel_limit_unit();
			$cancel_string     = sprintf( '%s +%d %s', current_time( 'd F Y H:i:s' ), $cancel_limit, $cancel_limit_unit );

			if ( strtotime( $cancel_string ) >= $this->get_start() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns if staff are enabled/needed for the appointment product
	 * @return boolean
	 */
	public function has_staff() {
		return $this->get_product() ? $this->get_product()->has_staff() : false;
	}

	/**
	 * Get the staff/type for this appointment if applicable.
	 * @param  bool $names
	 * @param  bool $with_link
	 * @return bool|object WP_Post
	 */
	public function get_staff_members( $names = false, $with_link = false ) {
		$ids = $this->get_staff_ids();

		if ( ! $ids ) {
			return false;
		}

		return wc_appointments_get_staff_from_ids( $ids, $names, $with_link );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD getters/helpers.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns appointment start date.
	 *
	 * @return string Date formatted via date_i18n
	 */
	public function get_start_date( $date_format = null, $time_format = null ) {
		if ( $this->get_start() ) {
			if ( is_null( $date_format ) ) {
				$date_format = apply_filters( 'woocommerce_appointments_date_format', wc_date_format() );
			}
			if ( is_null( $time_format ) ) {
				$time_format = apply_filters( 'woocommerce_appointments_time_format', ', ' . wc_time_format() );
			}
			if ( $this->is_all_day() ) {
				return date_i18n( $date_format, $this->get_start() );
			} else {
				return apply_filters( 'woocommerce_appointments_get_start_date_with_time', date_i18n( $date_format . $time_format, $this->get_start() ), $this );
			}
		}
		return false;
	}

	/**
	 * Returns appointment end date.
	 *
	 * @return string Date formatted via date_i18n
	 */
	public function get_end_date( $date_format = null, $time_format = null ) {
		if ( $this->get_end() ) {
			if ( is_null( $date_format ) ) {
				$date_format = apply_filters( 'woocommerce_appointments_date_format', wc_date_format() );
			}
			if ( is_null( $time_format ) ) {
				$time_format = apply_filters( 'woocommerce_appointments_time_format', ', ' . wc_time_format() );
			}
			if ( $this->is_all_day() ) {
				return date_i18n( $date_format, $this->get_end() );
			} else {
				return apply_filters( 'woocommerce_appointments_get_end_date_with_time', date_i18n( $date_format . $time_format, $this->get_end() ), $this );
			}
		}
		return false;
	}

	/**
	 * Returns appointment duration.
	 *
	 * @return string duration formatted as pretty timestamp
	 */
	public function get_duration( $get_minutes = false ) {
		if ( $this->get_start() && $this->get_end() ) {
			$start_time = round( $this->get_start() / 60 ) * 60; #round to nearest minute
			$end_time = round( $this->get_end() / 60 ) * 60; #round to nearest minute
			$timeDiff = abs( $end_time - $start_time ); #calculate difference
			$time = intval( $timeDiff / 60 ); #force integer value

			// Return minutes.
			if ( $get_minutes ) {
				return $time;
			}

			return wc_appointment_pretty_timestamp( $time );
		}

		return 0;
	}

	/**
	 * Returns appointment addons.
	 *
	 * @return string html formatted addon fields
	 */
	public function get_addons( $args = array() ) {
		$order = $this->get_order();
		if ( $order && version_compare( WC_VERSION, '3.0', '>=' ) ) {
			$args    = wp_parse_args( $args, array(
				'before'    => '<ul class="wc-item-meta"><li>',
				'after'		=> '</li></ul>',
				'separator'	=> '</li><li>',
				'label'		=> true,
				'echo'		=> false,
				'autop'		=> true,
			) );

			$item_meta = '';

			if ( count( $order->get_items() ) > 0 ) {

				foreach ( $order->get_items() as $item ) {
					$product = false;
					$item_id = 0;

					if ( $item->is_type( 'line_item' ) ) {
						$product = $item->get_product();
						$item_id = $item->get_id();
						$appointment_product = $product && $product->is_type( 'appointment' );
					}

					$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_item_id( $item_id );

					if ( $product && $appointment_product && $this->get_product_id() === $product->get_id() && in_array( $this->get_id(), $appointment_ids ) ) {
						$hidden_order_itemmeta = array(
							__( 'Appointment ID', 'woocommerce-appointments' ),
							__( 'Appointment Date', 'woocommerce-appointments' ),
							__( 'Date', 'woocommerce-appointments' ),
							__( 'Appointment Time', 'woocommerce-appointments' ),
							__( 'Time', 'woocommerce-appointments' ),
							__( 'Duration', 'woocommerce-appointments' ),
						);
						$strings = array();
						$html    = '';
						foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
							if ( in_array( $meta->key, $hidden_order_itemmeta ) ) {
								continue;
							}
							$value = $args['autop'] ? wp_kses_post( $meta->display_value ) : wp_kses_post( make_clickable( trim( strip_tags( $meta->display_value ) ) ) );
							if ( $args['label'] ) {
								$strings[] = '<strong class="wc-item-meta-label">' . wp_kses_post( $meta->display_key ) . ':</strong> ' . $value;
							} else {
								$strings[] = wp_kses_post( $meta->display_key ) . ': ' . $value;
							}
						}
						if ( $strings ) {
							$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
						}

						$item_meta = $html;

						/*
						// In one of future versions replace the above with code below.
						$item_meta = wc_display_item_meta( $item, array(
							'before'    => '<ul class="wc-item-meta"><li>',
							'after'		=> '</li></ul>',
							'separator'	=> '</li><li>',
							'echo'      => false,
							'autop'     => false,
						) );
						*/
					}
				}

			}

			return $item_meta;
		}

		return false;
	}

	/**
	 * Returns the object of the order corresponding to this appointment.
	 *
	 * @return WC_Product|Boolean
	 */
	public function get_product() {
		try {
			if ( $this->get_product_id() ) {
				return new WC_Product_Appointment( $this->get_product_id() );
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns the object of the order corresponding to this appointment.
	 *
	 * @return WC_Order|Boolean
	 */
	public function get_order() {
		return $this->get_order_id() ? wc_get_order( $this->get_order_id() ) : false;
	}

	/**
	 * Returns information about the customer of this order.
	 *
	 * @return object containing customer information
	 */
	public function get_customer() {
		$name      = '';
		$full_name = '';
		$phone     = '';
		$email     = '';
		$user_id   = 0;
		$order     = $this->get_order();

		if ( $this->get_customer_id() ) {
			$user       = get_user_by( 'id', $this->get_customer_id() );
			$name       = $user->display_name;
			$full_name  = trim( $user->user_firstname . ' ' . $user->user_lastname );
			$email      = $user->user_email;
			$user_id    = $this->get_customer_id();
		} elseif ( $order ) {
			$first_name = is_callable( array( $order, 'get_billing_first_name' ) ) ? $order->get_billing_first_name() : $order->billing_first_name;
			$last_name  = is_callable( array( $order, 'get_billing_last_name' ) ) ? $order->get_billing_last_name() : $order->billing_last_name;
			$name       = trim( $first_name . ' ' . $last_name );
			$full_name  = trim( $first_name . ' ' . $last_name );
			$phone      = is_callable( array( $order, 'get_billing_phone' ) ) ? $order->get_billing_phone() : $order->billing_phone;
			$email      = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email;
			$user_id    = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : $order->customer_user;
			/* translators: 1: guest name */
			$name 		= 0 !== absint( $user_id ) ? $name : sprintf( _x( '%s (Guest)', 'Guest string with name from appointment order in brackets', 'woocommerce-appointments' ), $name );
		}

		return (object) array(
			'name'      => $name,
			'full_name' => $full_name,
			'phone'   	=> $phone ,
			'email'     => $email,
			'user_id'   => $user_id,
		);
	}

	/**
	 * Schedule events for this appointment.
	 */
	public function schedule_events() {
		$order = $this->get_order();

		if ( in_array( $this->get_status(), get_wc_appointment_statuses( 'scheduled' ) ) ) {
			$order_status = $order ? $order->get_status() : null;

			// If there is no order, or the order is not in one of the statuses then schedule events.
			if ( ! in_array( $order_status, array( 'cancelled', 'refunded', 'pending', 'on-hold' ) ) ) {
				$this->maybe_schedule_event( 'reminder' );
			}

			$this->maybe_schedule_event( 'complete' );
		} else {
			wp_clear_scheduled_hook( 'wc-appointment-reminder', array( $this->get_id() ) );
			wp_clear_scheduled_hook( 'wc-appointment-complete', array( $this->get_id() ) );
		}
	}

	/**
	 * Schedule event for this appointment.
	 *
	 * @param string $type
	 * @return bool Whether schedule was done or not.
	 */
	public function maybe_schedule_event( $type ) {
		$tz_addition = - wc_appointment_timezone_offset();
		switch ( $type ) {
			case 'reminder':
				if ( $this->get_start() ) {
					wp_clear_scheduled_hook( 'wc-appointment-reminder', array( $this->get_id() ) );
					return is_null( wp_schedule_single_event( $tz_addition + strtotime( '-' . apply_filters( 'woocommerce_appointments_remind_before_time', '1 day' ), $this->get_start() ), 'wc-appointment-reminder', array( $this->get_id() ) ) );
				}
				break;
			case 'complete':
				if ( $this->get_end() ) {
					wp_clear_scheduled_hook( 'wc-appointment-complete', array( $this->get_id() ) );
					return is_null( wp_schedule_single_event( $tz_addition + apply_filters( 'woocommerce_appointments_complete_time', $this->get_end() ), 'wc-appointment-complete', array( $this->get_id() ) ) );
				}
		}

		return false;
	}

	/**
	 * Returns the cancel URL for a appointment
	 *
	 * @param string $redirect
	 * @return string
	 */
	public function get_cancel_url( $redirect = '' ) {
		$cancel_page = get_permalink( wc_get_page_id( 'myaccount' ) );

		if ( ! $cancel_page ) {
			$cancel_page = home_url();
		}

		return apply_filters( 'appointments_cancel_appointment_url', wp_nonce_url( add_query_arg(
			array(
				'cancel_appointment' => 'true',
				'appointment_id'     => $this->get_id(),
				'redirect'           => $redirect,
			),
		$cancel_page ), 'woocommerce-appointments-cancel_appointment' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Legacy.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Actualy create for the new appointment belonging to an order.
	 *
	 * @param string Status for new order
	 */
	public function create( $status = 'unpaid' ) {
		$this->set_status( $status );
		$this->save();
	}

	/**
	 * Will change the appointment status once the order is paid for.
	 *
	 * @return bool
	 */
	public function paid() {
		if ( in_array( $this->get_status(), array( 'unpaid', 'confirmed', 'wc-partial-payment' ) ) ) {
			$this->set_status( 'paid' );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * Populate the data with the id of the appointment provided
	 * Will query for the post belonging to this appointment and store it
	 *
	 * @return boolean
	 * @param int $appointment_id
	 */
	public function populate_data( $appointment_id ) {
		$this->set_defaults();
		$this->set_id( $appointment_id );
		$this->data_store->read( $this );
		return 0 < $this->get_id();
	}

	/**
	 * Set the new status for this appointment.
	 *
	 * @param string $status
	 * @return bool
	 */
	public function update_status( $status ) {
		$current_status = $this->get_status( 'edit' );

		$allowed_statuses = array(
			'was-in-cart' => __( 'Was In Cart', 'woocommerce-appointments' ),
		);

		$allowed_statuses = array_unique( array_merge(
			$allowed_statuses,
			get_wc_appointment_statuses( null, true ),
			get_wc_appointment_statuses( 'user', true ),
			get_wc_appointment_statuses( 'cancel', true )
		) );

		$allowed_status_keys = array_keys( $allowed_statuses );

		if ( in_array( $status, $allowed_status_keys ) ) {
			$this->set_status( $status );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * Set the new customer status for this appointment
	 * @param string $status
	 * @return bool
	 */
	public function update_customer_status( $status ) {
		$current_status					= $this->get_customer_status( 'edit' );
		$allowed_statuses 				= get_wc_appointment_statuses( 'customer' );
		$allowed_statuses 				= array_keys( $allowed_statuses );

		if ( in_array( $status, $allowed_statuses ) ) {
			$this->set_customer_status( $status );
			$this->save();

			return true;
		}

		return false;
	}

	/**
	 * Magic __isset method for backwards compatibility.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_props = array( 'appointment_date', 'modified_date', 'populated', 'post', 'custom_fields' );
		return $this->get_id() ? ( in_array( $key, $legacy_props ) || is_callable( array( $this, "get_{$key}" ) ) ) : false;
	}

	/**
	 * Magic __get method for backwards compatibility.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		// wc_doing_it_wrong( $key, 'Appointment properties should not be accessed directly.', '3.0.0' ); @todo deprecated when 2.6.x dropped
		if ( 'appointment_date' === $key ) {
			return $this->get_date_created();
		} elseif ( 'modified_date' === $key ) {
			return $this->get_date_modified();
		} elseif ( 'populated' === $key ) {
			return $this->get_object_read();
		} elseif ( 'post' === $key ) {
			return get_post( $this->get_id() );
		} elseif ( 'custom_fields' === $key ) {
			return get_post_meta( $this->get_id() );
		} elseif ( is_callable( array( $this, "get_{$key}" ) ) ) {
			return $this->{"get_{$key}"}();
		} else {
			return get_post_meta( $this->get_id(), '_' . $key, true );
		}
	}
}
