<?php
/**
 * Class dependencies
 */
if ( ! class_exists( 'WC_Appointment_Form_Picker' ) ) {
	include_once( 'class-wc-appointment-form-picker.php' );
}

/**
 * Date Picker class
 */
class WC_Appointment_Form_Date_Picker extends WC_Appointment_Form_Picker {

	private $field_type = 'date-picker';
	private $field_name = 'start_date';

	/**
	 * Constructor
	 * @param object $appointment_form The appointment form which called this picker
	 */
	public function __construct( $appointment_form ) {
		$this->appointment_form                = $appointment_form;
		$this->args                            = array();
		$this->args['type']                    = $this->field_type;
		$this->args['name']                    = $this->field_name;
		$this->args['min_date']                = $this->appointment_form->product->get_min_date();
		$this->args['max_date']                = $this->appointment_form->product->get_max_date();
		$this->args['default_availability']    = $this->appointment_form->product->get_default_availability();
		$this->args['min_date_js']             = $this->get_min_date();
		$this->args['max_date_js']             = $this->get_max_date();
		$this->args['duration_unit']           = $this->appointment_form->product->get_duration_unit();
		$this->args['availability_rules']      = array();
		$this->args['availability_rules'][0]   = $this->appointment_form->product->get_availability_rules();
		$this->args['label']                   = $this->get_field_label( __( 'Date', 'woocommerce-appointments' ) );
		$this->args['product_type']            = $this->appointment_form->product->get_type();
		$this->args['restricted_days']         = $this->appointment_form->product->has_restricted_days() ? $this->appointment_form->product->get_restricted_days() : false;

		if ( $this->appointment_form->product->has_staff() ) {
			foreach ( $this->appointment_form->product->get_staff_ids() as $staff_member_id ) {
				$this->args['availability_rules'][ $staff_member_id ] = $this->appointment_form->product->get_availability_rules( $staff_member_id );
			}
		}

		$fully_scheduled_slots = $this->find_fully_scheduled_slots();
		$padding_slots         = $this->find_padding_slots( $fully_scheduled_slots['fully_scheduled_days'] );

		$this->args = array_merge( $this->args, $fully_scheduled_slots, $padding_slots );

		$this->args['default_date'] = date( 'Y-m-d', $this->get_default_date( $fully_scheduled_slots, $padding_slots ) );
	}

	/**
	 * Attempts to find what date to default to in the date picker
	 * by looking at the fist available slot. Otherwise, the current date is used.
	 *
	 * @param  array $fully_scheduled_slots
	 * @param  array $padding_slots
	 * @return int Timestamp
	 */
	public function get_default_date( $fully_scheduled_slots = array(), $padding_slots = array() ) {
		/**
		 * Filter woocommerce_appointments_override_form_default_date
		 *
		 * @since 1.9.6
		 * @param int $default_date unix time stamp.
		 * @param WC_Appointment_Form_Picker $form_instance
		 */
		$default_date = apply_filters( 'woocommerce_appointments_override_form_default_date', null, $this );

		if ( $default_date ) {
			return $default_date;
		}

		$default_date = strtotime( 'midnight' );

		/**
		 * Filter wc_appointments_calendar_default_to_current_date. By default the calendar
		 * will show the current date first. If you would like it to display the first available date
		 * you can return false to this filter and then we'll search for the first available date,
		 * depending on the scheduled days calculation.
		 *
		 * @since 3.5.0
		 * @param bool
		 */
		if ( apply_filters( 'wc_appointments_calendar_default_to_current_date', true ) && $this->appointment_form->product->get_availability_autoselect() ) {

			$scheduled_dates = array_keys( array_merge( $fully_scheduled_slots['fully_scheduled_days'], $fully_scheduled_slots['unavailable_days'] ) );

			if ( isset( $padding_slots ) && isset( $padding_slots['padding_days'] ) ) {
				$scheduled_dates = array_merge( $scheduled_dates, array_keys( $padding_slots['padding_days'] ) );
			}

			if ( ! empty( $scheduled_dates ) ) {

				$default_date = $this->find_first_appointable_date( $scheduled_dates );

			}

			/*
			else {

				// Handles the case where a user can set all dates to be not-available by default
				// Also they add an availability rule where they are bookable at a future date in time

				$now      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$min      = $this->appointment_form->product->get_min_date_a();
				$max      = $this->appointment_form->product->get_max_date_a();
				$min_date = strtotime( 'midnight' );

				if ( ! empty( $min ) ) {
					$min_date = strtotime( "+{$min['value']} {$min['unit']}", $now );
				}

				// handling months differently due to performance impact it has
				// get it in three months batches to ensure
				// we can exit when we find the first one without going through all 12 months
				for ( $i = 1 ; $i <= $max['value'] ; $i += 3 ) {

					// $min_date calculated above first.
					// only add months up to the max value
					$range_end_increment = ( $i + 3 ) > $max['value'] ? $max['value'] : ( $i + 3 );
					$max_date            = strtotime( "+ $range_end_increment month", $now );
					$slots_in_range     = $this->appointment_form->product->get_slots_in_range( $min_date, $max_date );
					$last_element        = end( $slots_in_range );

					reset( $slots_in_range ); // restore the internal pointer.

					if ( $slots_in_range[0] > $last_element ) {
						// in certain cases the starting date is at the end
						// product->get_available_slots expects it to be at the beginning
						$slots_in_range = array_reverse( $slots_in_range );
					}

					$available_slots = $this->appointment_form->product->get_available_slots( $slots_in_range );

					if ( ! empty( $available_slots[0] ) ) {
						$default_date = $available_slots[0];
						break;
					} // else continue with loop until we get a default date where the calendar can start at.

					$min_date = strtotime( '+' . $i . ' month', $now );
				}

			}
			*/
		}

		return $default_date;
	}

	/**
	 * Find the first appointable date from an array of dates
	 * @param array $dates An array of dates to search
	 *
	 * @return The first appointable date
	 */
	private function find_first_appointable_date( $dates ) {
		// Converting dates into a timestamp because find_scheduled_day_slots is
		// formatting dates without leading zeros which can cause max to return the
		// wrong date. We can remove this once leading zeroes are added to the date format.
		//
		// e.g. max( array( '2017-11-9', '2017-11-30' ) ) will return 2017-11-9 as the max date
		$dates = array_map( function( $item ) {
			return strtotime( $item );
		}, $dates );

		$current_date        = strtotime( 'midnight' );
		$last_scheduled_date = max( $dates );
		$appointable_date    = strtotime( '+1 day', $last_scheduled_date );

		while ( $current_date < $last_scheduled_date ) {
			if ( ! in_array( $current_date, $dates ) ) {
				$appointable_date = $current_date;
				break;
			}
			$current_date = strtotime( '+1 day', $current_date );
		}

		return $appointable_date;
	}

	/**
	 * Find days which are padding days so they can be grayed out on the date picker
	 */
	protected function find_padding_slots() {
		$padding_days = WC_Appointments_Controller::find_padding_day_slots( $this->appointment_form->product );

		return array(
			'padding_days' => $padding_days,
		);
	}

	/**
	 * Finds days which are fully scheduled already so they can be sloted on the date picker
	 * @return array()
	 */
	protected function find_fully_scheduled_slots() {
		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $this->appointment_form->product->get_id() );

		return array(
			'partially_scheduled_days' => $scheduled['partially_scheduled_days'],
			'remaining_scheduled_days' => $scheduled['remaining_scheduled_days'],
			'fully_scheduled_days'     => $scheduled['fully_scheduled_days'],
			'unavailable_days'         => $scheduled['unavailable_days'],
		);
	}
}
