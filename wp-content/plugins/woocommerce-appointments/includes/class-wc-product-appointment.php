<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for the appointment product type
 */
class WC_Product_Appointment extends WC_Product {

	/**
	 * Get product availability rules.
	 * @var array
	 */
	private $availability_rules = array();

	/**
	 * Constructor
	 */
	public function __construct( $product ) {
		if ( empty( $this->product_type ) ) {
			$this->product_type = 'appointment';
		}
		parent::__construct( $product );
	}

	/**
	 * If this product class is a skelton/place holder class (used for appointment addons)
	 * @return boolean
	 */
	public function is_skeleton() {
		return false;
	}

	/**
	 * If this product class is an addon for appointments
	 * @return boolean
	 */
	public function is_appointments_addon() {
		return false;
	}

	/**
	 * Extension/plugin/add-on name for the appointment addon this product refers to
	 * @return string
	 */
	public function appointments_addon_title() {
		return '';
	}

	/**
	 * Appointments can always be purchased regardless of price.
	 * @return boolean
	 */
	public function is_purchasable() {
		$purchasable = true;

		// Products must exist of course
		if ( ! $this->exists() ) {
			$purchasable = false;
		} elseif ( 'publish' !== $this->post->post_status && ! current_user_can( 'edit_post', $this->id ) ) {
			$purchasable = false;
		}

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * Get the qty available to schedule per slot.
	 * @return int
	 */
	public function get_qty() {
		return $this->wc_appointment_qty ? absint( $this->wc_appointment_qty ) : 1;
	}

	/**
	 * Get the minimum qty required to schedule per slot.
	 * @return int
	 */
	public function get_qty_min() {
		return $this->wc_appointment_qty_min ? absint( $this->wc_appointment_qty_min ) : 1;
	}

	/**
	 * Get the maximum qty allowed to schedule per slot.
	 * @return int
	 */
	public function get_qty_max() {
		return $this->wc_appointment_qty_max ? absint( $this->wc_appointment_qty_max ) : 1;
	}

	/**
	 * Get calendar color for the appointment
	 * @return hexadecimal string
	 */
	public function get_cal_color() {
		return $this->wc_appointment_cal_color ? $this->wc_appointment_cal_color : '#0073aa';
	}

	/**
	 * get duration
	 * @return int
	 */
	public function get_duration() {
		return apply_filters( 'woocommerce_appointments_get_duration', absint( $this->wc_appointment_duration ), $this );
	}

	/**
	 * get duration unit
	 * @return int
	 */
	public function get_duration_unit() {
		return apply_filters( 'woocommerce_appointments_get_duration_unit', $this->wc_appointment_duration_unit, $this );
	}

	/**
	 * get interval
	 * @return int
	 */
	public function get_interval() {
		return apply_filters( 'woocommerce_appointments_get_interval', absint( $this->wc_appointment_interval ), $this );
	}

	/**
	 * get interval unit
	 * @return string
	 */
	public function get_interval_unit() {
		return apply_filters( 'woocommerce_appointments_get_interval_unit', $this->wc_appointment_interval_unit, $this );
	}

	/**
	 * get padding duration
	 * @return int
	 */
	public function get_padding_duration() {
		return apply_filters( 'woocommerce_appointments_get_padding_duration', absint( $this->wc_appointment_padding_duration ), $this );
	}

	/**
	 * get padding duration unit
	 * @return string
	 */
	public function get_padding_duration_unit() {
		return apply_filters( 'woocommerce_appointments_get_padding_duration_unit', $this->wc_appointment_padding_duration_unit, $this );
	}

	/**
	 * get padding
	 * @return int
	 */
	public function get_padding() {
		$padding_duration = 'hour' === $this->get_padding_duration_unit() ? $this->get_padding_duration() * 60 : $this->get_padding_duration();

		// Slot end time with current product padding added.
		if ( ! empty( $padding_duration ) && in_array( $this->get_padding_duration_unit(), array( 'minute', 'hour' ) ) ) { #with padding
			return $padding_duration;
		} else {
			return 0;
		}
	}

	/**
	 * get flag if it has padding or not
	 * @return bool
	 */
	public function has_padding() {
		return $this->get_padding() > 0;
	}

	/**
	 * The base cost will either be the 'base' cost or the base cost + cheapest staff
	 * @return string
	 */
	public function get_base_cost() {

		#$base = $this->price;
		$base = $this->price;

		if ( $this->has_staff() ) {
			$staff = $this->get_staff();
			$cheapest  = null;

			foreach ( $staff as $staff_member ) {
				if ( is_null( $cheapest ) || $staff_member->get_base_cost() < $cheapest ) {
					$cheapest = $staff_member->get_base_cost();
				}
			}
			$base += $cheapest;
		}

		return $base;
	}

	/**
	 * Return if appointment has extra costs
	 * @return bool
	 */
	public function has_additional_costs() {
		$has_additional_costs = 'yes' === $this->has_additional_costs;

		return $has_additional_costs;
	}

	/**
	 * Return if appointment has label
	 * @return bool
	 */
	public function has_price_label() {
		$has_price_label = false;

		// Products must exist of course
		if ( get_post_meta( $this->id, '_wc_appointment_has_price_label', true ) ) {
			$price_label = get_post_meta( $this->id, '_wc_appointment_price_label', true );
			$has_price_label = $price_label ? $price_label : __( 'Price Varies', 'woocommerce-appointments' );
		}

		return $has_price_label;
	}

	/**
	 * Get price HTML
	 *
	 * @param string $price
	 * @return string
	 */
	public function get_price_html( $price = '' ) {
		$display_price          = $this->get_display_price( $this->get_base_cost() );
		$display_regular_price  = $this->get_display_price( $this->regular_price );

		// Price label.
		if ( $this->has_price_label() ) {
			$price_html = $this->has_price_label();
		} elseif ( $display_price ) {
			if ( $this->has_additional_costs() ) {
				if ( $this->is_on_sale() && $this->get_regular_price() ) {
					$price_html = $this->get_price_html_from_to( $display_regular_price, $this->get_display_price( $this->get_base_cost() ) ) . $this->get_price_suffix();
				} else {
					$price_html = sprintf( __( '<small class="from">From </small>%s', 'woocommerce-appointments' ), wc_price( $display_price ) ) . $this->get_price_suffix();
				}
			} else {
				if ( $this->is_on_sale() && $this->get_regular_price() ) {
					$price_html = $this->get_price_html_from_to( $display_regular_price, $display_price ) . $this->get_price_suffix();
				} else {
					$price_html = wc_price( $display_price ) . $this->get_price_suffix();
				}
			}
		} elseif ( $this->get_price() == 0 ) {
			if ( $this->has_additional_costs() ) {
				if ( $this->is_on_sale() && $this->get_regular_price() ) {
					$price_html = $this->get_price_html_from_to( $display_regular_price, $this->get_display_price( $this->get_base_cost() ) ) . $this->get_price_suffix();
				} else {
					$price_html = sprintf( __( '<small class="from">From </small>%s', 'woocommerce-appointments' ), wc_price( $display_price ) ) . $this->get_price_suffix();
				}
			} else {
				if ( $this->is_on_sale() && $this->get_regular_price() ) {
					$price = $this->get_price_html_from_to( $display_regular_price, __( 'Free!', 'woocommerce-appointments' ) );
					$price_html = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );
				} else {
					$price = '<span class="amount">' . __( 'Free!', 'woocommerce-appointments' ) . '</span>';
					$price_html = apply_filters( 'woocommerce_free_price_html', $price, $this );
				}
			}
		} else {
			$price_html = apply_filters( 'woocommerce_empty_price_html', '', $this );
		}

		$price_html = apply_filters( 'woocommerce_return_price_html', $price_html, $this );

		// Duration label.
		if ( 'day' === $this->get_duration_unit() && $this->get_duration() || 'minute' === $this->get_duration_unit() && $this->get_duration() ) {
			$duration_full = wc_appointment_convert_to_hours_and_minutes( $this->get_duration() );
			$duration_html = ' <small class="duration">' . $duration_full . '</small>';
		} else {
			$duration_html = ' <small class="duration">' . sprintf( _n( '%s hour', '%s hours', $this->get_duration(), 'woocommerce-appointments' ), $this->get_duration() ) . '</small>';
		}

		$duration_html = apply_filters( 'woocommerce_return_duration_html', $duration_html, $this );

		return apply_filters( 'woocommerce_get_price_html', $price_html . $duration_html, $this );
	}

	/**
	 * Find the minimum slot's timestamp based on settings
	 * @return int
	 */
	public function get_min_timestamp_for_date( $start_date ) {
		$current_timestamp = current_time( 'timestamp' );
		$timestamp = $start_date;
		$today     = date( 'y-m-d', $start_date ) === date( 'y-m-d', $current_timestamp );
		$min = $this->get_min_date();

		if ( $min  && $today || empty( $start_date ) ) {
			$timestamp = strtotime( "midnight +{$min['value']} {$min['unit']}", $current_timestamp );
		}

		return $timestamp;
	}

	/**
	 * Get Min date
	 * @return array|bool
	 */
	public function get_min_date() {
		$min_date['value'] = ! empty( $this->wc_appointment_min_date ) ? apply_filters( 'woocommerce_appointments_min_date_value', absint( $this->wc_appointment_min_date ), $this->id ) : 0;
		$min_date['unit']  = ! empty( $this->wc_appointment_min_date_unit ) ? apply_filters( 'woocommerce_appointments_min_date_unit', $this->wc_appointment_min_date_unit, $this->id ) : 'month';

		if ( $min_date['value'] ) {
			return $min_date;
		}

		return false;
	}

	/**
	 * Get max date
	 * @return array
	 */
	public function get_max_date() {
		$max_date['value'] = ! empty( $this->wc_appointment_max_date ) ? apply_filters( 'woocommerce_appointments_max_date_value', absint( $this->wc_appointment_max_date ), $this->id ) : 1;
		$max_date['unit']  = ! empty( $this->wc_appointment_max_date_unit ) ? apply_filters( 'woocommerce_appointments_max_date_unit', $this->wc_appointment_max_date_unit, $this->id ) : 'month';

		if ( $max_date['value'] ) {
			return $max_date;
		}

		return false;
	}

	/**
	 * Get max year
	 * @return string
	 */
	private function get_max_year() {
		// Find max to get first
		$max_date = $this->get_max_date();
		$max_date_timestamp = strtotime( "+{$max_date['value']} {$max_date['unit']}" );
		$max_year = date( 'Y', $max_date_timestamp );
		if ( ! $max_year ) {
			$max_year = date( 'Y' );
		}

		return $max_year;
	}

	/**
	 * get staff assginment type
	 * @return string
	 */
	public function get_staff_assignment_type() {
		return apply_filters( 'woocommerce_appointments_get_staff_assignment', $this->wc_appointment_staff_assignment, $this );
	}

	/**
	 * How staff is assigned
	 * @return string customer or automatic
	 */
	public function is_staff_assignment_type( $type ) {
		return $this->get_staff_assignment_type() === $type;
	}

	/**
	 * Get staff member by ID
	 * @param  int $id
	 * @return WC_Product_Appointment_Staff object
	 */
	public function get_staff_member( $id ) {
		if ( $this->has_staff() && ! empty( $id ) ) {
			foreach ( (array) $this->get_staff() as $staff_member ) {
				if ( $staff_member->ID == $id ) {
					return $staff_member;
					break;
				}
			}
		}

		return false;
	}

	/**
	 * Get staff members by IDs
	 * @param  int $id
	 * @param  bool $names
	 * @param  bool $with_link
	 * @return WC_Product_Appointment_Staff object
	 */
	public function get_staff_members( $id = array(), $names = false, $with_link = false ) {
		if ( ! is_array( $id ) ) {
			$id = array( $id );
		}

		$staff_members = array();

		if ( $this->has_staff() && ! empty( $id ) ) {
			foreach ( (array) $this->get_staff() as $staff_member ) {
				if ( in_array( $staff_member->ID, $id ) ) {
					if ( $with_link ) {
						$staff_members[] = '<a href="' . get_edit_user_link( $staff_member->ID ) . '">' . $staff_member->display_name . '</a>';
					} elseif ( $names ) {
						$staff_members[] = $staff_member->display_name;
					} else {
						$staff_members[] = $staff_member;
					}
				}
			}
		}

		if ( $names && ! empty( $staff_members ) ) {
			$staff_members = implode( ', ', $staff_members );
		}

		return $staff_members;
	}

	/**
	 * See if this appointment product has any staff.
	 * @return boolean
	 */
	public function has_staff() {
		$count_staff = count( $this->get_staff() );
		return $count_staff ? $count_staff : false;
	}

	/**
	 * Get all staff
	 * @return array of WP_Post objects
	 */
	public function get_staff() {
		return wc_appointment_get_product_staff( $this->id );
	}

	/**
	 * Get array of costs
	 *
	 * @return array
	 */
	public function get_costs() {
		return WC_Product_Appointment_Rule_Manager::process_pricing_rules( $this->wc_appointment_pricing );
	}

	/**
	 * See if dates are by default appointable
	 * @return bool
	 */
	public function get_default_availability() {
		return apply_filters( 'woocommerce_appointment_default_availability', false, $this );
	}

	/**
	 * Checks if a product requires confirmation.
	 *
	 * @return bool
	 */
	public function requires_confirmation() {
		return apply_filters( 'woocommerce_appointment_requires_confirmation', 'yes' === $this->wc_appointment_requires_confirmation, $this );
	}

	/**
	 * See if the appointment can be cancelled.
	 *
	 * @return boolean
	 */
	public function can_be_cancelled() {
		return apply_filters( 'woocommerce_appointment_user_can_canel', 'yes' === $this->wc_appointment_user_can_cancel, $this );
	}

	/**
	 * Get the add to cart button text
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_appointment_add_to_cart_text', __( 'Book', 'woocommerce-appointments' ), $this );
	}

	/**
	 * Get the add to cart button text for the single page
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return 'yes' === $this->wc_appointment_requires_confirmation ? apply_filters( 'woocommerce_appointment_single_check_availability_text', __( 'Check Availability', 'woocommerce-appointments' ), $this ) : apply_filters( 'woocommerce_appointment_single_add_to_cart_text', __( 'Book Now', 'woocommerce-appointments' ), $this );
	}

	/**
	 * Return an array of staff which can be scheduled for a defined start/end date
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int $staff_id
	 * @param  integer $qty being scheduled
	 * @return bool|WP_ERROR if no slots available, or int count of appointments that can be made, or array of available staff
	 */
	public function get_available_appointments( $start_date, $end_date, $staff_id = '', $qty = 1 ) {
		// Check the date is not in the past
		if ( date( 'Ymd', $start_date ) < date( 'Ymd', current_time( 'timestamp' ) ) ) {
			return false;
		}

		if ( $this->has_staff() && ! is_numeric( $staff_id ) ) {
			return false;
		}

		$min_date   = $this->get_min_date();
		$max_date   = $this->get_max_date();
		$check_from = strtotime( "midnight +{$min_date['value']} {$min_date['unit']}", current_time( 'timestamp' ) );
		$check_to   = strtotime( "+{$max_date['value']} {$max_date['unit']}", current_time( 'timestamp' ) );

		// Min max checks
		if ( $end_date < $check_from || $start_date > $check_to ) {
			return false;
		}

		// Get availability of each staff - no staff has been chosen yet
		if ( $this->has_staff() && ! $staff_id ) {
			return $this->get_all_staff_availability( $start_date, $end_date, $qty );
		} else {
			$check_date = $start_date;

			while ( $check_date < $end_date ) {
				if ( ! $this->check_availability_rules_against_date( $check_date, $staff_id ) ) {
					return false;
				}
				if ( 'start' === $this->wc_appointment_availability_span ) {
					break; // Only need to check first day
				}
				$check_date = strtotime( '+1 day', $check_date );
			}

			if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) && ! $this->check_availability_rules_against_time( $start_date, $end_date, $staff_id ) ) {
				return false;
			}

			// Get slots availability
			return $this->get_slots_availability( $start_date, $end_date, $qty, $staff_id );
		}
	}

	/**
	 * Get the availability of all staff
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @return array|WP_Error
	 */
	public function get_all_staff_availability( $start_date, $end_date, $qty ) {
		$staff           = $this->get_staff();
		$available_staff = array();

		foreach ( $staff as $staff_member ) {
			$availability = $this->get_available_appointments( $start_date, $end_date, $staff_member->ID, $qty );

			if ( $availability && ! is_wp_error( $availability ) ) {
				$available_staff[ $staff_member->ID ] = $availability;
			}
		}

		if ( $this->is_staff_assignment_type( 'all' ) && count( $staff ) != count( $available_staff ) ) {
			return new WP_Error( 'Error', __( 'This slot cannot be scheduled.', 'woocommerce-appointments' ) );
		}

		if ( empty( $available_staff ) ) {
			return new WP_Error( 'Error', __( 'This slot cannot be scheduled.', 'woocommerce-appointments' ) );
		}

		return $available_staff;
	}

	/**
	 * Check the staff availability against all the slots.
	 *
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int    $qty
	 * @param  int    $staff_id
	 * @return string|WP_Error
	 */
	public function get_slots_availability( $start_date, $end_date, $qty, $staff_id ) {
		$slots    = $this->get_slots_in_range( $start_date, $end_date, '', $staff_id );
		$interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
		$interval = 'day' === $this->get_duration_unit() ? $this->get_duration() * 60 * 24 : $interval;

		if ( ! $slots ) {
			return false;
		}

		// Current product padding.
		$padding_duration = 'hour' === $this->get_padding_duration_unit() ? $this->get_padding_duration() * 60 : $this->get_padding_duration();

		// Slot end time with current product padding added.
		if ( ! empty( $padding_duration ) && in_array( $this->get_padding_duration_unit(), array( 'minute', 'hour' ) ) ) { #with padding
			$end_date = strtotime( "+{$padding_duration} minutes", $end_date );
		}

		#var_dump( date( 'Ymd H:i', $start_date ) . ' ======= ' . date( 'Ymd H:i', $end_date ) );

		/**
		 * Grab all existing appointments for the date range
		 * @var Array mixed with Object
		 */
		$existing_appointments_merged = $this->get_appointments_in_date_range(
			$start_date,
			$end_date,
			$staff_id
		);

		// Remove duplicates in existing appointments, generated with merging.
		$existing_appointments = array();
		foreach ( $existing_appointments_merged as $existing_appointment_merged ) {
			$existing_appointments[ $existing_appointment_merged->id ] = $existing_appointment_merged;
		}

		$available_qtys        = array();

		// Check all slots availability.
		foreach ( $slots as $slot ) {
			$qty_scheduled_in_slot = 0;

			// Check capacity based on duration unit.
			if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
				$slot_qty = $this->check_availability_rules_against_time( $slot, $slot, $staff_id, true );
			} else {
				$slot_qty = $this->check_availability_rules_against_date( $slot, $staff_id, true );
			}

			$existing_for_current_staff = 0;

			foreach ( $existing_appointments as $existing_appointment ) {
				if ( null === $existing_appointment->id ) {
					continue;
				}

				$appointment_product_id = $existing_appointment->get_product_id();

				// Padding.
				$padding_duration_length = get_post_meta( $appointment_product_id, '_wc_appointment_padding_duration', true );
				$padding_duration_unit = get_post_meta( $appointment_product_id, '_wc_appointment_padding_duration_unit', true );
				$padding_duration_length_min = 'hour' === $padding_duration_unit ? $padding_duration_length * 60 : $padding_duration_length;

				// Slot start/end time.
				if ( ! empty( $padding_duration_length ) ) { #with padding
					$start_time = strtotime( "-{$padding_duration_length_min} minutes", $slot );
					$end_time = strtotime( "+{$padding_duration_length_min} minutes +{$interval} minutes", $slot );
				} else { // without padding
					$start_time = $slot;
					$end_time = strtotime( "+{$interval} minutes", $slot );
				}

				// Existing appointment lasts all day, force end day time.
				if ( $existing_appointment->is_all_day() ) {
					$end_time = strtotime( 'midnight +1 day', $end_time );
				}

				// Product duration set to day, force daily check.
				if ( 'day' === $this->get_duration_unit() ) {
					$start_time = strtotime( 'midnight', $start_time );
					$end_time = strtotime( 'midnight +1 day', $end_time );
				}

				if ( $existing_appointment->is_scheduled_on_day( $start_time, $end_time ) ) {
					// when existing appointment is scheduled with another product,
					// remove all available capacity, so staff becomes unavailable for this product.
					if ( $appointment_product_id != $this->id ) {
						$qty_to_add = $this->get_qty();
					// Only remove capacity scheduled for existing product.
					} else {
						$qty_to_add = isset( $existing_appointment->qty ) ? $existing_appointment->qty : 1;
					}

					$qty_scheduled_in_slot += $qty_to_add;

					// Staff for existing appointment.
					$staff_ids = $existing_appointment->get_staff_id();
					if ( ! is_array( $staff_ids ) ) {
						$staff_ids = array( $staff_ids );
					}

					// Current appointment has same staff.
					if ( in_array( $staff_id, $staff_ids ) ) {
						$existing_for_current_staff += 1;
					}
				}
			}

			// When current staff is NOT scheduled u
			if ( ! $existing_for_current_staff && $this->get_qty() > $slot_qty ) {
				$slot_qty = $this->get_qty();
			}

			// Calculate availably capacity.
			$available_qty = max( $slot_qty - $qty_scheduled_in_slot, 0 );

			#var_dump( $available_qty .' = '. $slot_qty .' - '. $qty_scheduled_in_slot . ' < ' . $qty . ' staff=' . $staff_id );
			#var_dump( 'break' );

			// Remaining places are less than requested qty, return an error.
			if ( $available_qty < $qty ) {
				if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
					return new WP_Error( 'Error', sprintf(
						_n( 'There is only %1$d place remaining on %2$s at %3$s.', 'There are %1$d places remaining on %2$s at %3$s.', $available_qty, 'woocommerce-appointments' ),
						max( $available_qty, 0 ),
						date_i18n( wc_date_format(), $slot ),
						date_i18n( wc_time_format(), $start_date )
					) );
				} elseif ( ! $available_qtys ) {
					return new WP_Error( 'Error', sprintf(
						_n( 'There is only %1$d place remaining on %2$s', 'There are %1$d places remaining on %2$s', $available_qty , 'woocommerce-appointments' ),
						$available_qty,
						date_i18n( wc_date_format(), $slot )
					) );
				} else {
					return new WP_Error( 'Error', sprintf(
						_n( 'There is only %1$d place remaining on %2$s', 'There are %1$d places remaining on %2$s', $available_qty , 'woocommerce-appointments' ),
						max( $available_qtys ),
						date_i18n( wc_date_format(), $slot )
					) );
				}
			}

			$available_qtys[] = $available_qty;
		}

		return min( $available_qtys );
	}

	/**
	 * Get existing appointments in a given date range
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @param int    $staff_id
	 * @return array
	 */
	public function get_appointments_in_date_range( $start_date, $end_date, $staff_id = null ) {
		if ( $this->has_staff() && $staff_id ) {
			if ( ! is_array( $staff_id ) ) {
				$staff_id = array( $staff_id );
			}
		}

		return WC_Appointments_Controller::get_appointments_in_date_range( $start_date, $end_date, $this->id, $staff_id );
	}

	/**
	 * Get rules in order of `override power`. The higher the index the higher the override power. Element at index 4 will
	 * override element at index 2.
	 *
	 * Within priority the rules will be ordered top to bottom.
	 *
	 * @return array  availability_rules {
	 *    @type $staff_id => array {
	 *
	 *       The $order_index depicts the levels override. `0` Is the lowest. `1` overrides `0` and `2` overrides `1`.
	 *       e.g. If monday is set to available in `1` and not available in `2` the results should be that Monday is
	 *       NOT available because `2` overrides `1`.
	 *       $order_index corresponds to override power. The higher the element index the higher the override power.
	 *       @type $order_index => array {
	 *          @type string $type   The type of range selected in admin.
	 *          @type string $range  Depending on the type this depicts what range and if available or not.
	 *          @type integer $priority
	 *          @type string $level Global, Product or Staff
	 *          @type integer $order The index for the order set in admin.
	 *      }
	 * }
	 */
	public function get_availability_rules( $for_staff = 0 ) {

		// Repeat the function if staff IDs are in array.
		if ( is_array( $for_staff ) ) {
			foreach ( $for_staff as $for_staff_id ) {
				return $this->get_availability_rules( $for_staff_id );
			}
		}

		if ( empty( $this->availability_rules[ $for_staff ] ) ) {
			$this->availability_rules[ $for_staff ] = array();

			// Rule types.
			$staff_rules 	= array();

			// Get availability of each staff - no staff has been chosen yet.
			if ( $this->has_staff() && ! $for_staff ) {
				$staff_rules = array();

				// All slots are available.
				if ( $this->get_default_availability() ) {
					# If all slotss are available by default, we should not hide days if we don't know which staff is going to be used.
				// All staff avaialability rules matter.
				} elseif ( $this->is_staff_assignment_type( 'all' ) ) {
					// Show slots where all staff is available.
					foreach ( $this->get_staff() as $staff_member ) {
						$staff_rules = array_merge( $staff_rules, $staff_member->get_availability() );
					}
				// All YES rules matter, but only overlapped NO rules matter.
				} else {
					// Add staff ID to each rule.
					$staff_rules = array();
					foreach ( $this->get_staff() as $staff_member ) {
						$staff_rule = $staff_member->get_availability();
						foreach ( (array) $staff_rule as $key => $value ) {
							$staff_rule[ $key ] = $value;
							$staff_rule[ $key ]['staff'] = $staff_member->ID;
						}
						$staff_rules = array_merge( $staff_rules, $staff_rule );
					}

					// Separate to YES and NO appointable rules.
					$availability_no_overlapped = array();
					$availability_no = array();
					$availability_yes = array();

					if ( ! empty( $staff_rules ) ) {
						foreach ( (array) $staff_rules as $key => $rule ) {
							if ( ! isset( $rule['appointable'] ) || ! isset( $rule['type'] ) || ! isset( $rule['from'] ) || ! isset( $rule['to'] ) ) {
								continue;
							}

							if ( 'yes' == $rule['appointable'] ) {
								$availability_yes[] = $rule;
							} else {
								$availability_no[ $rule['type'] ][ $rule['staff'] ][ $key ][] = $rule['from'];
								$availability_no[ $rule['type'] ][ $rule['staff'] ][ $key ][] = $rule['to'];
							}
						}
					}

					// Get overlapped NO rules.
					if ( ! empty( $availability_no ) ) {
						foreach ( (array) $availability_no as $rule_type => $rule_no ) {
							$return_staff = WC_Product_Appointment_Rule_Manager::process_staff_unavailability_rules( $rule_no, $rule_type );
							$availability_no_overlapped = array_merge( $availability_no_overlapped, $return_staff );
						}
					}

					// Merge overlapped NO rules with all YES rules
					$staff_rules = array_merge( $availability_no_overlapped, $availability_yes );
				}
			} elseif ( $for_staff ) {
				// Standard handling.
				$staff_rules	= (array) get_user_meta( $for_staff, '_wc_appointment_availability', true );
			}

			// Merge and reverse order so lower rules are evaluated first.
			$availability_rules = array_filter(
				array_merge(
					WC_Product_Appointment_Rule_Manager::get_global_availability_rules(),
					WC_Product_Appointment_Rule_Manager::get_product_availability_rules( $this->id ),
					WC_Product_Appointment_Rule_Manager::format_availability_rules( array_reverse( $staff_rules ), 'staff' )
				)
			);

			usort( $availability_rules, array( $this, 'rule_override_power_sort' ) );

			$this->availability_rules[ $for_staff ] = $availability_rules;
		}

		return apply_filters( 'woocommerce_appointment_get_availability_rules', $this->availability_rules[ $for_staff ], $for_staff, $this );
	}

	/**
	 * Sort rules in order of precedence.
	 *
	 * @version 2.3.1 sort order reversed
	 * The order produced will be from the lowest to the highest.
	 * The elements with higher indexes overrides those with lower indexes e.g. `4` overrides `3`
	 * Index corresponds to override power. The higher the element index the higher the override power
	 *
	 * Level    : `global` > `product` > `staff` (greater in terms off override power)
	 * Priority : within a level
	 * Order    : Within a priority The lower the order index higher the override power.
	 *
	 * @param array $rule1
	 * @param array $rule2
	 *
	 * @return integer
	 */
	public function rule_override_power_sort( $rule1, $rule2 ) {
		$level_weight = array(
			'staff' => 5,
			'product' => 3,
			'global' => 1,
		);

		if ( $level_weight[ $rule1['level'] ] === $level_weight[ $rule2['level'] ] ) {
			if ( $rule1['priority'] === $rule2['priority'] ) {
				// if `order index of 1` < `order index of 2` $rule1 one has a higher override power. So we increase the index for $rule1 which corresponds to override power.
				return ( $rule1['order'] < $rule2['order'] ) ? 1 : -1;
			}

			// if `priority of 1` < `priority of 2` $rule1 must have lower override power. So we decrease the index for 1 which corresponds to override power.
			return $rule1['priority'] < $rule2['priority'] ? 1 : -1;
		}

		// if `level of 1` < `level of 2` $rule1 must have lower override power. So we decrease the index for 1 which corresponds to override power.
		return $level_weight[ $rule1['level'] ] < $level_weight[ $rule2['level'] ] ? -1 : 1;
	}

	/**
	 * Check a date against the availability rules
	 *
	 * @version 2.3.1 removed all calls to break 2 to ensure we get to the highest
	 *                 priority rules, otherwise higher order/priority rules will not
	 *                 override lower ones and the function exit with the wrong value.
	 *
	 * @param  string $check_date date to check
	 * @param  string $staff_id id of staff for which to check the availability rules
	 * @param  string $get_capacity to return available capacity or if it is available
	 * @return bool available or not
	 */
	public function check_availability_rules_against_date( $check_date, $staff_id, $get_capacity = false ) {
		$year        = date( 'Y', $check_date );
		$month       = absint( date( 'm', $check_date ) );
		$day         = absint( date( 'j', $check_date ) );
		$day_of_week = absint( date( 'N', $check_date ) );
		$week        = absint( date( 'W', $check_date ) );
		$appointable = $default_availability = $this->get_default_availability();

		$rules = $this->get_availability_rules( $staff_id );

		// Staff capacity overrides product capacity
		$staff_capacity = false;
		if ( $staff_id && ! empty( $staff_id ) ) {
			$staff_capacity = get_user_meta( $staff_id, '_wc_appointment_staff_qty', true );
		}

		// Capacity.
		$capacity = $staff_capacity ? $staff_capacity : $this->get_qty();

		#var_dump($day);

		foreach ( $rules as $rule ) {
			$type  = $rule['type'];
			$range = $rule['range'];
			$qty   = $rule['qty'] && $rule['qty'] >= 1  ? $rule['qty'] : $capacity;

			switch ( $type ) {
				case 'months' :
					if ( isset( $range[ $month ] ) ) {
						$appointable = $range[ $month ];
						$capacity = $qty;
					}
				break;
				case 'weeks':
					if ( isset( $range[ $week ] ) ) {
						$appointable = $range[ $week ];
						$capacity = $qty;
					}
				break;
				case 'days' :
					if ( isset( $range[ $day_of_week ] ) ) {
						$appointable = $range[ $day_of_week ];
						$capacity = $qty;
					}
				break;
				case 'custom' :
					if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
						$appointable = $range[ $year ][ $month ][ $day ];
						$capacity = $qty;
					}
				break;
				case 'time':
				case 'time:1':
				case 'time:2':
				case 'time:3':
				case 'time:4':
				case 'time:5':
				case 'time:6':
				case 'time:7':
					if ( false === $default_availability && ( $day_of_week === $range['day'] || 0 === $range['day'] ) ) {
						$appointable = $range['rule'];
						// This function only checks to see if a date is available and this rule
						// only covers a few hours in a given date so as far as this rule is concerned a given
						// date may always be available as there are hours outside of the scope of this rule.
						if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) ) {
							$appointable = true;
						}
						$capacity = $qty;
					}
				break;
				case 'time:range':
					if ( false === $default_availability && ( isset( $range[ $year ][ $month ][ $day ] ) ) ) {
						$appointable = $range[ $year ][ $month ][ $day ]['rule'];
						// This function only checks to see if a date is available and this rule
						// only covers a few hours in a given date so as far as this rule is concerned a given
						// date may always be available as there are hours outside of the scope of this rule.
						if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) ) {
							$appointable = true;
						}
						$capacity = $qty;
					}
				break;
			}
		}

		#var_dump( date( 'Y-m-d H:i', $check_date ) . '__' . $appointable );

		// Return rule type capacity.
		if ( $get_capacity ) {
			return absint( $capacity );
		}

		return $appointable;
	}

	/**
	 * Check a time against the time specific availability rules
	 *
	 * @param  string	$slot_start_time timestamp to check
	 * @param  string 	$slot_end_time   timestamp to check
	 * @return bool 	available or not
	 */
	public function check_availability_rules_against_time( $slot_start_time, $slot_end_time, $staff_id, $get_capacity = false ) {
		$appointable	 = $this->get_default_availability();
		$slot_start_time = is_numeric( $slot_start_time ) ? $slot_start_time : strtotime( $slot_start_time );
		$slot_end_time   = is_numeric( $slot_end_time ) ? $slot_end_time : strtotime( $slot_end_time );

		$rules = $this->get_availability_rules( $staff_id );

		// Staff capacity overrides product capacity.
		$staff_capacity = false;
		if ( $staff_id && ! empty( $staff_id ) ) {
			$staff_capacity = get_user_meta( $staff_id, '_wc_appointment_staff_qty', true );
		}

		// Capacity.
		$capacity = $staff_capacity ? $staff_capacity : $this->get_qty();

		#var_dump( $staff_id );

		foreach ( $rules as $rule ) {
			$type  = $rule['type'];
			$range = $rule['range'];
			$qty   = $rule['qty'] && $rule['qty'] >= 1  ? $rule['qty'] : $capacity;

			#var_dump( $qty );

			// Skip all types which are not important here (all but time types).
			if ( in_array( $type, array( 'days', 'custom', 'months', 'weeks' ) ) ) {
				continue;
			}

			if ( 'time:range' === $type ) {
				$year = date( 'Y', $slot_start_time );
				$month = date( 'n', $slot_start_time );
				$day = date( 'j', $slot_start_time );

				if ( ! isset( $range[ $year ][ $month ][ $day ] ) ) {
					continue;
				}

				$rule_val = $range[ $year ][ $month ][ $day ]['rule'];
				$from     = $range[ $year ][ $month ][ $day ]['from'];
				$to       = $range[ $year ][ $month ][ $day ]['to'];
			} else {
				if ( ! empty( $range['day'] ) ) {
					if ( date( 'N', $slot_start_time ) != $range['day'] ) {
						continue;
					}
				} elseif ( ! empty( $range['date'] ) ) {
					if ( date( 'Y-m-d', $slot_start_time ) != $range['date'] ) {
						continue;
					}
				}

				$rule_val = $range['rule'];
				$from     = $range['from'];
				$to       = $range['to'];
			}

			$rule_start_time = strtotime( $from, $slot_start_time );
			$rule_end_time   = strtotime( $to, $slot_start_time );

			// Reverse time rule - The end time is tomorrow e.g. 16:00 today - 12:00 tomorrow
			if ( $rule_end_time <= $rule_start_time ) {
				if ( $slot_end_time > $rule_start_time ) {
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
				if ( $slot_start_time >= $rule_start_time && $slot_end_time >= $rule_end_time ) {
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
				// does this rule apply?
				// does slot start before rule start and end after rules start time {goes over start time}
				if ( $slot_start_time < $rule_start_time && $slot_end_time > $rule_start_time ) {
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
			} else {
				// Normal rule.
				if ( $slot_start_time >= $rule_start_time && $slot_end_time <= $rule_end_time ) {
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}

				// specific to hour duration types. If start time is in between
				// rule start and end times the rule should be applied.
				if ( 'hour' == $this->get_duration_unit() && $slot_start_time > $rule_start_time && $slot_start_time < $rule_end_time ) {
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
			}

			#var_dump( absint( $capacity ) .'__'. $type );
		}

		#var_dump( $staff_id . ' ... ' . date( 'Y-m-d H:i', $slot_start_time ) . '__' . date( 'Y-m-d H:i', $slot_end_time ) . ' == ' . absint( $capacity ) );
		#var_dump( date( 'Y-m-d H:i', $slot_start_time ) . '__' . $appointable );

		// Return rule type capacity.
		if ( $get_capacity ) {
			return absint( $capacity );
		}

		return $appointable;
	}

	/**
	 * Get an array of slots within in a specified date range - might be days, might be slots within days, depending on settings.
	 *
	 * @param       $start_date
	 * @param       $end_date
	 * @param array $intervals
	 * @param int   $staff_id
	 * @param array $scheduled
	 *
	 * @return array
	 */
	public function get_slots_in_range( $start_date, $end_date, $intervals = array(), $staff_id = 0, $scheduled = array() ) {
		$duration_unit = $this->get_duration_unit();

		if ( empty( $intervals ) ) {
			$default_interval = 'hour' === $duration_unit ? $this->get_duration() * 60 : $this->get_duration();
			$custom_interval = 'hour' === $duration_unit ? $this->get_duration() * 60 : $this->get_duration();
			if ( $this->get_interval_unit() && $this->get_interval() ) {
				$custom_interval = 'hour' === $this->get_interval_unit() ? $this->get_interval() * 60 : $this->get_interval();
			}
			$intervals = array( $default_interval, $custom_interval );
		}

		if ( 'day' === $duration_unit ) {
			$slots_in_range = $this->get_slots_in_range_for_day( $start_date, $end_date, $staff_id, $scheduled );
		} else {
			$slots_in_range = $this->get_slots_in_range_for_hour_or_minutes( $start_date, $end_date, $intervals, $staff_id, $scheduled );
		}

		asort( $slots_in_range ); #sort ascending by value so latest time goes at the end

		return array_unique( $slots_in_range );
	}

	/**
	 * Get slots/day slots in range for day duration unit.
	 *
	 * @param $start_date
	 * @param $end_date
	 * @param $staff_id
	 * @param $scheduled
	 *
	 * @return array
	 */
	public function get_slots_in_range_for_day( $start_date, $end_date, $staff_id, $scheduled ) {
		$slots = array();

		// get scheduled days with a counter to specify how many appointments on that date
		$scheduled_days_with_count = array();
		foreach ( $scheduled as $appointment ) {
			$appointment_start = $appointment[0];
			$appointment_end   = $appointment[1];
			$current_appointment_day = $appointment_start;

			// < because appointment end depicts an end of a day and not a start for a new day.
			while ( $current_appointment_day < $appointment_end ) {
				$date = date( 'Y-m-d', $current_appointment_day );

				if ( isset( $scheduled_days_with_count[ $date  ] ) ) {
					$scheduled_days_with_count[ $date ]++;
				} else {
					$scheduled_days_with_count[ $date ] = 1;
				}

				$current_appointment_day = strtotime( '+1 day', $current_appointment_day );
			}
		}

		// If exists always treat scheduling_period in minutes.
		$check_date = $start_date;
		while ( $check_date <= $end_date ) {
			if ( $this->check_availability_rules_against_date( $check_date, $staff_id ) ) {
				$available_qty = $this->check_availability_rules_against_date( $check_date, $staff_id, true );
				$date = date( 'Y-m-d', $check_date );
				if ( ! isset( $scheduled_days_with_count[ $date ] ) || $scheduled_days_with_count[ $date ] < $available_qty ) {
					$slots[] = $check_date;
				}
			}

			// move to next day
			$check_date = strtotime( '+1 day', $check_date );
		}

		return $slots;
	}

	/**
	 * Get slots in range for hour or minute duration unit.
	 * For minutes and hours find valid slots within THIS DAY ($check_date)
	 *
	 * @param $start_date
	 * @param $end_date
	 * @param $intervals
	 * @param $staff_id
	 * @param $scheduled
	 *
	 * @return array
	 */
	public function get_slots_in_range_for_hour_or_minutes( $start_date, $end_date, $intervals, $staff_id, $scheduled ) {
		// Setup.
		$slot_start_times_in_range		= array();
		$interval						= $intervals[0];
		$check_date						= $start_date;
		$default_appointable_minutes	= $this->get_default_availability() ? range( 0, ( 1440 + $interval ) ) : array();
		$rules							= $this->get_availability_rules( $staff_id ); // Work out what minutes are actually appointable on this day
		$minutes_not_available    		= $this->get_unavailable_minutes( $scheduled ); // Get available slot start times.

		// Looping day by day look for available slots.
		while ( $check_date <= $end_date ) {
			$appointable_minutes_for_date = array_merge( $default_appointable_minutes, WC_Product_Appointment_Rule_Manager::get_minutes_from_rules( $rules, $check_date ) );
			$appointable_start_and_end    = $this->get_appointable_minute_start_and_end( $appointable_minutes_for_date );
			$slots                   	  = $this->get_appointable_minute_slots_for_date( $check_date, $start_date, $end_date, $appointable_start_and_end, $intervals, $staff_id, $minutes_not_available );
			$slot_start_times_in_range	  = array_merge( $slots, $slot_start_times_in_range );

			$check_date = strtotime( '+1 day', $check_date ); // Move to the next day
		}

		return $slot_start_times_in_range;
	}

	/**
	 * @param array $appointable_minutes
	 *
	 * @return array
	 */
	public function get_appointable_minute_start_and_end( $appointable_minutes ) {

		// Break appointable minutes into sequences - appointments cannot have breaks
		$appointable_minute_slots     = array();
		$appointable_minute_slot_from = current( $appointable_minutes );

		foreach ( $appointable_minutes as $key => $minute ) {
			if ( isset( $appointable_minutes[ $key + 1 ] ) ) {
				if ( $appointable_minutes[ $key + 1 ] - 1 === $minute ) {
					continue;
				} else {
					// There was a break in the sequence
					$appointable_minute_slots[]   = array( $appointable_minute_slot_from, $minute );
					$appointable_minute_slot_from = $appointable_minutes[ $key + 1 ];
				}
			} else {
				// We're at the end of the appointable minutes
				$appointable_minute_slots[] = array( $appointable_minute_slot_from, $minute );
			}
		}

		return $appointable_minute_slots;
	}

	/**
	 * Return an array of that is not available for appointment.
	 *
	 * @since 2.3.0 introduced.
	 *
	 * @param array $scheduled. Pairs of scheduled slot start and end times.
	 * @return array $scheduled_minutes
	 */
	public function get_unavailable_minutes( $scheduled ) {
		$minutes_not_available = array();
		foreach ( $scheduled as $scheduled_slot ) {
			for ( $i = $scheduled_slot[0]; $i < $scheduled_slot[1]; $i += 60 ) {
				$minutes_not_available[] = $i; #previously set as: array_push( $minutes_not_available, $i );
			}
		}

		$minutes_not_available = array_count_values( $minutes_not_available );

		return $minutes_not_available;
	}

	/**
	 * Returns slots/time slots from a given start and end minute slots.
	 *
	 * This function take varied inputs but always retruns a slot array of available slots.
	 * Sometimes it gets the minutes and see if all is available some times it needs to make up the
	 * minutes based on what is scheduled.
	 *
	 * It uses start and end date to figure things out.
	 *
	 * @since 2.3.0 introduced.
	 *
	 * @param $check_date
	 * @param $start_date
	 * @param $end_date
	 * @param $appointable_start_and_end
	 * @param $intervals
	 * @param $staff_id
	 * @param $minutes_not_available
	 *
	 * @return array
	 */
	public function get_appointable_minute_slots_for_date( $check_date, $start_date, $end_date, $appointable_start_and_end, $intervals, $staff_id, $minutes_not_available ) {
		// slots as in an array of slots. $slot_start_times
		$slots = array();

		#var_dump( $intervals );

		// boring interval stuff
		$interval              = $intervals[0];
		$base_interval         = $intervals[1];

		// get a time stamp to check from and get a time stamp to check to
		$product_min_date = $this->get_min_date();
		$product_max_date = $this->get_max_date();
		if ( 'hour' === $product_min_date['unit'] ) {
			// Adding 1 hour to round up to the next whole hour to return what is expected.
			$product_min_date['value'] = (int) $product_min_date['value'] + 1;
		}

		$min_check_from   = strtotime( "+{$product_min_date['value']} {$product_min_date['unit']}", current_time( 'timestamp' ) );
		$max_check_to     = strtotime( "+{$product_max_date['value']} {$product_max_date['unit']}", current_time( 'timestamp' ) );
		$min_date         = $this->get_min_timestamp_for_date( $start_date );

		$current_time_stamp = current_time( 'timestamp' );

		// Loop the slots of appointable minutes and add a slot if there is enough room to book
		foreach ( $appointable_start_and_end as $time_slot ) {
			// the the slot start time calculation using end of the
			$time_slot_start        = strtotime( "midnight +{$time_slot[0]} minutes", $check_date );
			/* should rename ... slot makes one think of slot */
			$minutes_in_slot        = $time_slot[1] - $time_slot[0];
			$base_intervals_in_slot = floor( $minutes_in_slot / $base_interval );
			$time_slot_end_time 	= strtotime( "midnight +{$time_slot[1]} minutes", $check_date );

			// Only need to check first hour.
			if ( 'start' === $this->wc_appointment_availability_span ) {
				$base_interval = 1; #test
				$base_intervals_in_slot = 1; #test
			}

			for ( $i = 0; $i < $base_intervals_in_slot; $i++ ) {
				$from_interval = $i * $base_interval;
				$to_interval   = $from_interval + $interval;
				$start_time    = strtotime( "+{$from_interval} minutes", $time_slot_start );
				$end_time      = strtotime( "+{$to_interval} minutes", $time_slot_start );

				// Skip when dates do not match.
				if ( date( 'Y-n-j', $start_date ) !== date( 'Y-n-j', $start_time ) ) {
					continue;
				}

				// Available quantity.
				// $available_qty = $this->check_availability_rules_against_time( $start_time, $end_time, $staff_id, true );
				$available_qty = $this->get_qty(); // exact quantity is checked in get_available_slots_html() function

				// Break if start time is after the end date being calculated.
				if ( $start_time > $end_date && ( 'start' !== $this->wc_appointment_availability_span )  ) {
					break 2;
				}

				// Must be in the future.
				if ( $start_time < $min_date || $start_time <= $current_time_stamp ) {
					continue;
				}

				// Skip if minutes not available.
				if ( isset( $minutes_not_available[ $start_time ] ) && $minutes_not_available[ $start_time ] >= $available_qty ) {
					continue;
				}

				// Make sure minute & hour slots are not past minimum & max appointment settings.
				if ( $end_time < $min_check_from || $start_time > $max_check_to ) {
					continue;
				}

				/*
				// Skip if end time bigger than slot end time.
				// thrown out as it prevented 24/7 businesses last slot buildup
				if ( $end_time > $time_slot_end_time && ( 'start' !== $this->wc_appointment_availability_span ) ) {
					continue;
				}
				*/

				if ( $this->are_all_minutes_in_slot_available( $start_time, $end_time, $available_qty, $minutes_not_available ) && ! in_array( $start_time, $slots ) ) {
					$slots[] = $start_time;
				}
			}
		}

		return  $slots;
	}

	/**
	 * Checks all minutes in slot for availability. Comparing it with the minutes not available.
	 *
	 * @since 2.3.0
	 *
	 * @param $start_time
	 * @param $end_time
	 * @param $available_qty
	 *
	 * @return bool
	 */
	public function are_all_minutes_in_slot_available( $start_time, $end_time, $available_qty, $minutes_not_available ) {
		$loop_time = $start_time;

		while ( $loop_time < $end_time ) {
			if ( isset( $minutes_not_available[ $loop_time ] ) && $minutes_not_available[ $loop_time ] >= $available_qty ) {
				return false;
			}
			$loop_time = $loop_time + 60;
		}

		return true;
	}

	/**
	 * Returns available slots from a range of slots by looking at existing appointments.
	 * @param  array   $slots		The slots we'll be checking availability for.
	 * @param  array   $intervals   Array containing 2 items; the interval of the slot (maybe user set), and the base interval for the slot/product.
	 * @param  integer $staff_id	Staff we're getting slots for. Falls backs to product as a whole if 0.
	 * @param  string  $from        The starting date for the set of slots
	 * @return array 				The available slots array
	 */
	public function get_available_slots( $slots, $intervals = array(), $staff_id = 0, $from = '' ) {
		if ( empty( $intervals ) ) {
			$default_interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
			$custom_interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
			if ( $this->get_interval_unit() && $this->get_interval() ) {
				$custom_interval = 'hour' === $this->get_interval_unit() ? $this->get_interval() * 60 : $this->get_interval();
			}
			$intervals        = array( $default_interval, $custom_interval );
		}

		list( $interval, $base_interval ) = $intervals;

		$available_slots   = array();
		$start_date = empty( $from ) ? current( $slots ) : $from;
		$end_date = end( $slots );

		if ( ! empty( $slots ) ) {
			/**
			 * Grab all existing appointments for the date range
			 * @var array
			 */
			$existing_appointments_merged = $this->get_appointments_in_date_range(
				$start_date,
				$end_date + ( $interval * 60 ),
				$staff_id
			);

			// Remove duplicates in existing appointments, generated with merging.
			$existing_appointments = array();
			foreach ( $existing_appointments_merged as $existing_appointment_merged ) {
				$existing_appointments[ $existing_appointment_merged->id ] = $existing_appointment_merged;
			}

			// Staff scheduled array. Staff can be a "staff" but also just an appointment if it has no staff.
			$staff_scheduled = array( 0 => array() );
			$product_scheduled = array( 0 => array() );
			$existing_for_current_staff = 0;

			// Loop all existing appointments
			foreach ( $existing_appointments as $existing_appointment ) {
				if ( null === $existing_appointment->id ) {
					continue;
				}

				$appointment_staff_ids = $existing_appointment->get_staff_id();
				$appointment_product_id = $existing_appointment->get_product_id();

				// Google Calendar sync.
				if ( wc_appointments_gcal_synced_product_id() == $appointment_product_id ) {
					#$alt_appointment_product_id = 0; # Make product ID zero, so it adds scheduled dates/times to current product.
					$alt_appointment_product_id = $this->id; # Make product IDs same, so it adds scheduled dates/times to current product.
				} else {
					$alt_appointment_product_id = $appointment_product_id;
				}

				// Padding.
				$padding_duration_length = get_post_meta( $alt_appointment_product_id, '_wc_appointment_padding_duration', true );
				$padding_duration_unit = get_post_meta( $alt_appointment_product_id, '_wc_appointment_padding_duration_unit', true );
				$padding_duration_length_min = 'hour' === $padding_duration_unit ? $padding_duration_length * 60 : $padding_duration_length;
				$appointment_duration_unit = get_post_meta( $alt_appointment_product_id, '_wc_appointment_duration_unit', true );

				// Prepare staff and product array.
				foreach ( (array) $appointment_staff_ids as $appointment_staff_id ) {
					$staff_scheduled[ $appointment_staff_id ] = isset( $staff_scheduled[ $appointment_staff_id ] ) ? $staff_scheduled[ $appointment_staff_id ] : array();
				}
				$product_scheduled[ $alt_appointment_product_id ] = isset( $product_scheduled[ $alt_appointment_product_id ] ) ? $product_scheduled[ $alt_appointment_product_id ] : array();

				// Slot start/end time.
				if ( ! empty( $padding_duration_length ) && in_array( $appointment_duration_unit, array( 'minute', 'hour' ) ) ) { #with padding
					$start_time = strtotime( "-{$padding_duration_length_min} minutes", $existing_appointment->start );
					$end_time = strtotime( "+{$padding_duration_length_min} minutes", $existing_appointment->end );
				} else { #without padding
					$start_time = $existing_appointment->start;
					$end_time = $existing_appointment->end;
				}

				// Existing appointment lasts all day, force end day time.
				if ( $existing_appointment->is_all_day() ) {
					$end_time = strtotime( 'midnight +1 day', $end_time );
				}

				// Product duration set to day, force daily check
				if ( 'day' === $this->get_duration_unit() ) {
					$start_time = strtotime( 'midnight', $start_time );
					$end_time = strtotime( 'midnight +1 day', $end_time );
				}

				// When existing appointment is scheduled with another product,
				// remove all available capacity, so staff becomes unavailable for this product.
				if ( $appointment_product_id != $this->id ) {
					$repeat = max( 1, $this->get_qty() );
				// Only remove capacity scheduled for existing product.
				} else {
					$repeat = max( 1, $existing_appointment->qty );
				}

				// Repeat to add capacity for each scheduled qty.
				foreach ( (array) $appointment_staff_ids as $appointment_staff_id ) {
					for ( $i = 0; $i < $repeat; $i++ ) {
						array_push( $staff_scheduled[ $appointment_staff_id ], array( $start_time, $end_time ) );
					}
				}
				for ( $i = 0; $i < $repeat; $i++ ) {
					array_push( $product_scheduled[ $alt_appointment_product_id ], array( $start_time, $end_time ) );
				}
			}

			// Available times for product: Generate arrays that contain information about what slots to unset.
			$available_times = $this->get_slots_in_range( $start_date, $end_date, array( $interval, $base_interval ), $staff_id, isset( $product_scheduled[ $this->id ] ) ? $product_scheduled[ $this->id ] : $product_scheduled[0] );

			/*
			// Test
			$test = array();
			foreach ( $available_times as $available_time ) {
				$test[] = date( 'y-m-d H:i', $available_time );
			}
			var_dump( $test );
			*/

			// No preference.
			if ( $this->has_staff() && ! $staff_id ) {

				// Loop through product staff.
				$staff_ids = array();
				foreach ( $this->get_staff() as $staff_member ) {
					$staff_ids[] = $staff_member->ID;

					if ( isset( $staff_scheduled[ $staff_member->ID ] ) ) {
						$staff_scheduled_found[] = $staff_scheduled[ $staff_member->ID ][0];
					}
				}

				// Default to all staff if no staff ID is scheduled.
				if ( isset( $staff_scheduled_found ) ) {
					$staff_scheduled = $staff_scheduled_found;
				} else {
					$staff_scheduled = $staff_scheduled[0];
				}

				// Get slots in range.
				$times = $this->get_slots_in_range( $start_date, $end_date, array( $interval, $base_interval ), $staff_ids, $staff_scheduled );

				if ( $this->is_staff_assignment_type( 'all' ) ) {
					$available_times = array_intersect( $available_times, $times ); #merge times from staff that are also available in product
				} else {
					$available_times = array_merge( $available_times, $times ); #add times from staff to times from product
				}

			// Available times for specific staff: Staff ID.
			} elseif ( $this->has_staff() && $staff_id ) {

				// Loop through product staff.
				if ( is_array( $staff_id ) ) {
					foreach ( $this->get_staff() as $staff_member ) {
						if ( isset( $staff_scheduled[ $staff_member->ID ] ) ) {
							$staff_scheduled_found[] = $staff_scheduled[ $staff_member->ID ][0];
						}
					}
				}

				// Default to all staff if no staff ID is scheduled.
				if ( isset( $staff_scheduled_found ) ) {
					$staff_scheduled = $staff_scheduled_found;
				} else {
					$staff_scheduled = $staff_scheduled[0];
				}

				// Get slots in range.
				$times = $this->get_slots_in_range( $start_date, $end_date, array( $interval, $base_interval ), $staff_id, $staff_scheduled );
				$available_times = array_intersect( $available_times, $times ); #merge times from staff that are also available in product

			}

			/*
			// Test
			$test = array();
			foreach ( $available_times as $available_time ) {
				$test[] = date( 'y-m-d H:i', $available_time );
			}
			var_dump( $test );
			*/

			// Count scheduled times then loop the slots.
			$available_times = array_count_values( $available_times );

			// Loop through all slots and unset if they are allready scheduled
			foreach ( $slots as $slot ) {
				if ( isset( $available_times[ $slot ] ) ) {
					$available_slots[] = $slot;
				}
			}

			/*
			// Test
			$test2 = array();
			foreach ( $available_slots as $available_slot ) {
				$test2[] = date( 'y-m-d H:i', $available_slot );
			}
			var_dump( $test2 );
			*/
		}

		// Even though we checked hours against other days/slots, make sure we only return slots for this date..
		if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) && ! empty( $from ) ) {
			$time_slots = array();
			foreach ( $available_slots as $key => $slot_date ) {
				if ( date( 'ymd', $slot_date ) == date( 'ymd', $from ) ) {
					$time_slots[] = $slot_date;
				}
			}
			$available_slots = $time_slots;
		}

		/**
		 * Filter the available slots for a product within a given range
		 *
		 * @since 1.9.6 introduced
		 *
		 * @param array $available_slots
		 * @param WC_Product $appointments_product
		 * @param array $raw_range passed into this function.
		 * @param array $intervals
		 * @param integer $staff_id
		 */
		return apply_filters( 'wc_appointments_product_get_available_slots', $available_slots, $this, $slots, $intervals, $staff_id );
	}

	/**
	 * Find available slots and return HTML for the user to choose a slot. Used in class-wc-appointments-admin-ajax.php
	 * @param  array  $slots
	 * @param  array  $intervals
	 * @param  integer $staff_id
	 * @return string
	 */
	public function get_available_slots_html( $slots, $intervals = array(), $time_to_check = 0, $staff_id = 0, $from = '', $timezone = 'UTC' ) {
		list( $interval, $base_interval ) = $intervals;

		$start_date = current( $slots );
		$end_date = end( $slots );
		
		$slots		= $this->get_available_slots( $slots, $intervals, $staff_id, $from );
		$slot_html	= '';

		if ( $slots ) {

			// Timezones.
			$timezone_datetime = new DateTime();
			$local_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), $timezone );
			$site_time = wc_appointment_timezone_locale( 'site', 'user', $timezone_datetime->getTimestamp(), wc_time_format(), wc_timezone_string() );

			// Split day into three parts
			$times = apply_filters( 'woocommerce_appointments_times_split', array(
				'morning' => array(
					'name' => __( 'Morning', 'woocommerce-appointments' ),
					'from' => strtotime( '00:00' ),
					'to' => strtotime( '12:00' ),
				),
				'afternoon' => array(
					'name' => __( 'Afternoon', 'woocommerce-appointments' ),
					'from' => strtotime( '12:00' ),
					'to' => strtotime( '17:00' ),
				),
				'evening' => array(
					'name' => __( 'Evening', 'woocommerce-appointments' ),
					'from' => strtotime( '17:00' ),
					'to' => strtotime( '24:00' ),
				),
			));

			$slot_html .= "<div class=\"slot_row\">";
			foreach ( $times as $k => $v ) {
				$slot_html .= "<ul class=\"slot_column $k\">";
				$slot_html .= '<li class="slot_heading">' . $v['name'] . '</li>';
				$count = 0;

				foreach ( $slots as $slot ) {
					if ( $v['from'] <= strtotime( date( 'G:i', $slot ) ) && $v['to'] > strtotime( date( 'G:i', $slot ) ) ) {
						$selected = date( 'G:i', $slot ) == date( 'G:i', $time_to_check ) ? ' selected' : '';

						// Test availability for each slot.
						#$test_availability = 1;
						$test_availability = $this->get_available_appointments(
							$slot,
							strtotime( "+{$interval} minutes", $slot ),
							$staff_id,
							1
						);
						#$test_availability = $this->check_availability_rules_against_time( $slot, strtotime( "+{$interval} minutes", $slot ), $staff_id, true );

						#var_dump( $test_availability );
						#var_dump( date( 'G:i', $slot ) );

						// Return available qty for each slot.
						if ( ! is_wp_error( $test_availability ) ) {
							if ( is_array( $test_availability ) ) {
								$available_qty = max( $test_availability );
							} else {
								$available_qty = $test_availability;
							}
						} else {
							$available_qty = 0;
						}

						// Disply each slot HTML.
						if ( $available_qty > 0 ) {
							$slot_left = $this->get_qty() > $available_qty ? " <small class=\"spaces-left\">(" . sprintf( _n( '%d left', '%d left', $available_qty, 'woocommerce-appointments' ), absint( $available_qty ) ) . ")</small>" : "";
							$slot_locale = ( $local_time !== $site_time ) ? sprintf( __( ' data-locale="Your local time: %s"', 'woocommerce-appointments' ), wc_appointment_timezone_locale( 'site', 'user', $slot, wc_date_format() . ', ' . wc_time_format(), $timezone ) ) : '';
							$slot_html .= "<li class=\"slot$selected\"$slot_locale><a href=\"#\" data-value=\"" . date_i18n( 'G:i', $slot ) . "\">" . date_i18n( wc_time_format(), $slot ) . "$slot_left</a></li>";
						} else {
							continue;
						}
					} else {
						continue;
					}

					$count++;
				}

				if ( ! $count ) {
					$slot_html .= '<li class="slot slot_empty">' . __( '&#45;', 'woocommerce-appointments' ) . '</li>';
				}
				$slot_html .= "</ul>";
			}
			$slot_html .= "</div>";
		}

		return apply_filters( 'woocommerce_appointments_time_slots_html', $slot_html, $slots, $intervals, $time_to_check, $staff_id, $from, $timezone, $this );

	}
}
