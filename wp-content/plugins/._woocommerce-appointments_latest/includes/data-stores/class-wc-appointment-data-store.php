<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WC Appointment Data Store: Stored in CPT.
 *
 * @todo When 2.6 support is dropped, implement WC_Object_Data_Store_Interface
 */
class WC_Appointment_Data_Store extends WC_Data_Store_WP {

	/**
	 * Meta keys and how they transfer to CRUD props.
	 *
	 * @var array
	 */
	private $appointment_meta_key_to_props = array(
		'_appointment_all_day'           => 'all_day',
		'_appointment_cost'              => 'cost',
		'_appointment_customer_id'       => 'customer_id',
		'_appointment_order_item_id'     => 'order_item_id',
		'_appointment_parent_id'         => 'parent_id',
		'_appointment_product_id'        => 'product_id',
		'_appointment_staff_id'          => 'staff_ids',
		'_appointment_start'             => 'start',
		'_appointment_end'               => 'end',
		'_wc_appointments_gcal_event_id' => 'google_calendar_event_id',
		'_appointment_customer_status'   => 'customer_status',
		'_appointment_qty'               => 'qty',
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new appointment in the database.
	 *
	 * @param WC_Appointment $appointment
	 */
	public function create( &$appointment ) {
		if ( ! $appointment->get_date_created( 'edit' ) ) {
			$appointment->set_date_created( current_time( 'timestamp' ) );
		}

		// @codingStandardsIgnoreStart
		$id = wp_insert_post( apply_filters( 'woocommerce_new_appointment_data', array(
			'post_date'     => date( 'Y-m-d H:i:s', $appointment->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $appointment->get_date_created( 'edit' ) ) ),
			'post_type'     => 'wc_appointment',
			'post_status'   => $appointment->get_status( 'edit' ),
			'post_author'   => $appointment->get_customer_id( 'edit' ),
			'post_title'    => sprintf( __( 'Appointment &ndash; %s', 'woocommerce-appointments' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Appointment date parsed by strftime', 'woocommerce-appointments' ) ) ),
			'post_parent'   => $appointment->get_order_id( 'edit' ),
			'ping_status'   => 'closed',
		) ), true );
		// @codingStandardsIgnoreEnd

		if ( $id && ! is_wp_error( $id ) ) {
			$appointment->set_id( $id );
			$this->update_post_meta( $appointment );
			$appointment->save_meta_data();
			$appointment->apply_changes();
			WC_Cache_Helper::get_transient_version( 'appointments', true );

			do_action( 'woocommerce_new_appointment', $appointment->get_id() );
		}
	}

	/**
	 * Method to read an order from the database.
	 *
	 * @param WC_Appointment
	 */
	public function read( &$appointment ) {
		$appointment->set_defaults();
		$appointment_id  = $appointment->get_id();
		$post_object = $appointment_id ? get_post( $appointment_id ) : false;

		if ( ! $appointment_id || ! $post_object || 'wc_appointment' !== $post_object->post_type ) {
			throw new Exception( __( 'Invalid appointment.', 'woocommerce-appointments' ) );
		}

		$set_props = array();

		// Read post data.
		$set_props['date_created']  = $post_object->post_date;
		$set_props['date_modified'] = $post_object->post_modified;
		$set_props['status']        = $post_object->post_status;
		$set_props['order_id']      = $post_object->post_parent;

		// Read meta data.
		foreach ( $this->appointment_meta_key_to_props as $key => $prop ) {
			$value = get_post_meta( $appointment->get_id(), $key, true );

			switch ( $prop ) {
				case 'end':
				case 'start':
					$set_props[ $prop ] = $value ? strtotime( $value ) : '';
					break;
				case 'all_day':
					$set_props[ $prop ] = wc_appointments_string_to_bool( $value );
					break;
				case 'staff_ids':
					// Staff can be saved multiple times to same meta key.
					$value = get_post_meta( $appointment->get_id(), $key, false );
					$set_props[ $prop ] = $value;
					break;
				default:
					$set_props[ $prop ] = $value;
					break;
			}
		}

		$appointment->set_props( $set_props );
		$appointment->set_object_read( true );
	}

	/**
	 * Method to update an order in the database.
	 *
	 * @param WC_Appointment $appointment
	 */
	public function update( &$appointment ) {
		wp_update_post( array(
			'ID'            => $appointment->get_id(),
			'post_date'     => date( 'Y-m-d H:i:s', $appointment->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $appointment->get_date_created( 'edit' ) ) ),
			'post_status'   => $appointment->get_status( 'edit' ),
			'post_author'   => $appointment->get_customer_id( 'edit' ),
			'post_parent'   => $appointment->get_order_id( 'edit' ),
		) );
		$this->update_post_meta( $appointment );
		$appointment->save_meta_data();
		$appointment->apply_changes();
		WC_Cache_Helper::get_transient_version( 'appointments', true );
	}

	/**
	 * Method to delete an order from the database.
	 * @param WC_Appointment
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$appointment, $args = array() ) {
		$id   = $appointment->get_id();
		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$appointment->set_id( 0 );
			do_action( 'woocommerce_delete_appointment', $id );
		} else {
			wp_trash_post( $id );
			$appointment->set_status( 'trash' );
			do_action( 'woocommerce_trash_appointment', $id );
		}
	}

	/**
	 * Helper method that updates all the post meta for an appointment based on it's settings in the WC_Appointment class.
	 *
	 * @param WC_Appointment
	 */
	protected function update_post_meta( &$appointment ) {
		foreach ( $this->appointment_meta_key_to_props as $key => $prop ) {
			if ( is_callable( array( $appointment, "get_$prop" ) ) ) {
				$value = $appointment->{ "get_$prop" }( 'edit' );

				switch ( $prop ) {
					case 'all_day':
						update_post_meta( $appointment->get_id(), $key, $value ? 1 : 0 );
						break;
					case 'end':
					case 'start':
						update_post_meta( $appointment->get_id(), $key, $value ? date( 'YmdHis', $value ) : '' );
						break;
					case 'staff_ids':
						delete_post_meta( $appointment->get_id(), $key );
						if ( is_array( $value ) ) {
							foreach ( $value as $staff_id ) {
								add_post_meta( $appointment->get_id(), '_appointment_staff_id', $staff_id );
							}
						} elseif ( is_numeric( $value ) ) {
							add_post_meta( $appointment->get_id(), '_appointment_staff_id', $value );
						}
						break;
					default:
						update_post_meta( $appointment->get_id(), $key, $value );
						break;
				}
			}
		}
	}

	/**
	 * For a given order ID, get all appointments that belong to it.
	 *
	 * @param  int|array $order_id
	 * @return int
	 */
	public static function get_appointment_ids_from_order_id( $order_id ) {
		global $wpdb;

		$order_ids = wp_parse_id_list( is_array( $order_id ) ? $order_id : array( $order_id ) );

		return wp_parse_id_list( $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wc_appointment' AND post_parent IN (" . implode( ',', array_map( 'esc_sql', $order_ids ) ) . ');' ) );
	}

	/**
	 * For a given order item ID, get all appointments that belong to it.
	 *
	 * @param  int $order_item_id
	 * @return array
	 */
	public static function get_appointment_ids_from_order_item_id( $order_item_id ) {
		global $wpdb;
		return wp_parse_id_list(
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d;",
					$order_item_id
				)
			)
		);
	}

	/**
	 * Check if a given order contains only Appointments items.
	 * If the order contains non-appointment items, it will return false.
	 * Otherwise, it will return an array of Appointments.
	 *
	 * @param  WC_Order $order
	 * @return bool|array
	 */
	public static function get_order_contains_only_appointments( $order ) {
		$all_appointment_ids = array();

		foreach ( array_keys( $order->get_items() ) as $order_item_id ) {
			$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_item_id( $order_item_id );

			if ( empty( $appointment_ids ) ) {
				return false;
			}

			$all_appointment_ids = array_merge( $all_appointment_ids, $appointment_ids );
		}

		return $all_appointment_ids;
	}

	/**
	 * Get appointment ids for an object  by ID. e.g. product.
	 *
	 * @param  array
	 * @return array
	 */
	public static function get_appointment_ids_by( $filters = array() ) {
		global $wpdb;

		$filters = wp_parse_args( $filters, array(
			'object_id'    => 0,
			'product_id'   => 0,
			'staff_id'     => 0,
			'object_type'  => 'product',
			'status'       => false,
			'limit'        => -1,
			'offset'       => 0,
			'order_by'     => 'date_created',
			'order'        => 'DESC',
			'date_before'  => false,
			'date_after'   => false,
			'date_between' => array(
				'start' => false,
				'end'   => false,
			),
		) );

		// Product and staff.
		$filters['product_id'] = $filters['product_id'] ? $filters['product_id'] : $filters['object_id'];
		$filters['staff_id']   = $filters['staff_id'] ? $filters['staff_id'] : $filters['object_id'];

		$meta_keys             = array();
		$query_where           = array( 'WHERE 1=1', "p.post_type = 'wc_appointment'" );
		$filters['object_id']  = array_filter( wp_parse_id_list( is_array( $filters['object_id'] ) ? $filters['object_id'] : array( $filters['object_id'] ) ) );
		$filters['product_id'] = array_filter( wp_parse_id_list( is_array( $filters['product_id'] ) ? $filters['product_id'] : array( $filters['product_id'] ) ) );
		$filters['staff_id']   = array_filter( wp_parse_id_list( is_array( $filters['staff_id'] ) ? $filters['staff_id'] : array( $filters['staff_id'] ) ) );

		#echo '<pre>' . var_export( $filters['product_id'], true ) . '</pre>';
		#echo '<pre>' . var_export( $filters['staff_id'], true ) . '</pre>';
		#echo '<pre>' . var_export( $filters, true ) . '</pre>';

		switch ( $filters['object_type'] ) {
			case 'product':
			case 'product_and_staff':
				if ( ! empty( $filters['product_id'] ) ) {
					$meta_keys[]   = '_appointment_product_id';
					$meta_keys[]   = '_appointment_staff_id';
					$query_where[] = "(
						_appointment_product_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['product_id'] ) ) . "') OR _appointment_staff_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['staff_id'] ) ) . "')
					)";
				}
				break;
			case 'staff':
				if ( ! empty( $filters['staff_id'] ) ) {
					$meta_keys[]   = '_appointment_staff_id';
					$query_where[] = "_appointment_staff_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['staff_id'] ) ) . "')";
				}
				break;
			case 'customer':
				if ( ! empty( $filters['object_id'] ) ) {
					$meta_keys[]   = '_appointment_customer_id';
					$query_where[] = "_appointment_customer_id.meta_value IN ('" . implode( "','", array_map( 'esc_sql', $filters['object_id'] ) ) . "')";
				}
				break;
		}

		// Status.
		if ( ! empty( $filters['status'] ) ) {
			$query_where[] = "p.post_status IN ('" . implode( "','", $filters['status'] ) . "')";
		}

		// Date between.
		if ( ! empty( $filters['date_between']['start'] ) && ! empty( $filters['date_between']['end'] ) ) {
			$meta_keys[]   = '_appointment_start';
			$meta_keys[]   = '_appointment_end';
			$meta_keys[]   = '_appointment_all_day';
			$query_where[] = "( (
				_appointment_start.meta_value < '" . esc_sql( date( 'YmdHis', $filters['date_between']['end'] ) ) . "' AND
				_appointment_end.meta_value > '" . esc_sql( date( 'YmdHis', $filters['date_between']['start'] ) ) . "' AND
				_appointment_all_day.meta_value = '0'
			) OR (
				_appointment_start.meta_value < '" . esc_sql( date( 'Ymd000000', $filters['date_between']['end'] ) ) . "' AND
				_appointment_end.meta_value > '" . esc_sql( date( 'Ymd000000', $filters['date_between']['start'] ) ) . "' AND
				_appointment_all_day.meta_value = '1'
			) )";
		}

		if ( ! empty( $filters['date_after'] ) ) {
			$meta_keys[]   = '_appointment_start';
			$query_where[] = "_appointment_start.meta_value > '" . esc_sql( date( 'YmdHis', $filters['date_after'] ) ) . "'";
		}

		if ( ! empty( $filters['date_before'] ) ) {
			$meta_keys[]   = '_appointment_end';
			$query_where[] = "_appointment_end.meta_value < '" . esc_sql( date( 'YmdHis', $filters['date_before'] ) ) . "'";
		}

		if ( ! empty( $filters['order_by'] ) ) {
			switch ( $filters['order_by'] ) {
				case 'date_created':
					$filters['order_by'] = 'p.post_date';
					break;
				case 'start_date':
					$meta_keys[]   = '_appointment_start';
					$filters['order_by'] = '_appointment_start.meta_value';
					break;
			}
			$query_order = ' ORDER BY ' . esc_sql( $filters['order_by'] ) . ' ' . esc_sql( $filters['order'] );
		} else {
			$query_order = '';
		}

		if ( $filters['limit'] > 0 ) {
			$query_limit = ' LIMIT ' . absint( $filters['offset'] ) . ',' . absint( $filters['limit'] );
		} else {
			$query_limit = '';
		}

		$query_select = "SELECT p.ID FROM {$wpdb->posts} p";
		$meta_keys    = array_unique( $meta_keys );
		$query_where  = implode( ' AND ', $query_where );

		foreach ( $meta_keys as $index => $meta_key ) {
			$key           = esc_sql( $meta_key );
			$query_select .= " LEFT JOIN {$wpdb->postmeta} {$key} ON p.ID = {$key}.post_id AND {$key}.meta_key = '{$key}'";
		}

		return array_filter( wp_parse_id_list( $wpdb->get_col( "{$query_select} {$query_where} {$query_order} {$query_limit};" ) ) );
	}

	/**
	 * For a given appointment ID, get it's linked order ID if set.
	 *
	 * @param  int $appointment_id
	 * @return int
	 */
	public static function get_appointment_order_id( $appointment_id ) {
		return absint( wp_get_post_parent_id( $appointment_id ) );
	}

	/**
	 * For a given appointment ID, get it's linked order item ID if set.
	 *
	 * @param  int $appointment_id
	 * @return int
	 */
	public static function get_appointment_order_item_id( $appointment_id ) {
		return absint( get_post_meta( $appointment_id, '_appointment_order_item_id', true ) );
	}

	/**
	 * For a given appointment ID, get it's linked order item ID if set.
	 *
	 * @param  int $appointment_id
	 * @return int
	 */
	public static function get_appointment_customer_id( $appointment_id ) {
		return absint( get_post_meta( $appointment_id, '_appointment_customer_id', true ) );
	}
}
