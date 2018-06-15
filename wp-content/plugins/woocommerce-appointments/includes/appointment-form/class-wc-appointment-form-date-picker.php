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
		$this->appointment_form                    = $appointment_form;
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
		$this->args['default_date']            = date( 'Y-m-d', $this->get_default_date() );
		$this->args['product_type']            = $this->appointment_form->product->product_type;

		if ( $this->appointment_form->product->has_staff() ) {
			foreach ( $this->appointment_form->product->get_staff() as $staff_member ) {
				$this->args['availability_rules'][ $staff_member->ID ] = $this->appointment_form->product->get_availability_rules( $staff_member->ID );
			}
		}

		$this->find_padding_slots();
		$this->find_fully_scheduled_slots();

		// Experimental.
		if ( current_theme_supports( 'woocomerce-appointments-show-discounted-slots' ) ) {
			$this->find_discounted_slots();
		}
	}

	/**
	 * Attempts to find what date to default to in the date picker
	 * by looking at the fist available block. Otherwise, the current date is used.
	 */
	public function get_default_date() {

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

		/**
		 * Filter wc_appointments_calendar_default_to_current_date. By default the calendar
		 * will show the current date first. If you would like it to display the first available date
		 * you can return false to this filter and then we'll search for the first available date.
		 *
		 * @since 2.3.0
		 * @param bool
		 */
		if ( 'no' === $this->appointment_form->product->wc_appointment_availability_autoselect ) {
			return strtotime( 'midnight' );
		}

		$now = strtotime( 'midnight', current_time( 'timestamp' ) );
		$min = $this->appointment_form->product->get_min_date();
		if ( empty( $min ) ) {
			$min_date = strtotime( 'midnight' );
		} else {
			$min_date = strtotime( "+{$min['value']} {$min['unit']}", $now );
		}
		$max = $this->appointment_form->product->get_max_date();

		$unit_not_month = 'month' !== $max['unit'];
		$less_than_5_months = 'month' == $max['unit'] && 5 < $max['unit'];
		if ( $unit_not_month || $less_than_5_months  ) {
			$max_date = strtotime( "+{$max['value']}{$max['unit']}", $now );
			$slots_in_range  = $this->appointment_form->product->get_slots_in_range( $min_date, $max_date );
			$available_slots = $this->appointment_form->product->get_available_slots( $slots_in_range );
			$default_date = empty( $available_slots[0] ) ? strtotime( 'midnight' ) : $available_slots[0];

			return $default_date;
		}

		/** handling months differently due to performance impact it has.
            Get it in three months batches to ensure we can exit
			when we find the first one without going through all 12 months
        **/
		for ( $i = 1 ; $i <= $max['value'] ; $i = $i + 3, $min_date = strtotime( '+' . $i . ' month', $now ) ) {
			// $min_date calculated above first. Only add months up to the max value.
			$range_end_increment = ( $i + 3 ) > $max['value'] ? $max['value'] : ( $i + 3 );
			$max_date            = strtotime( "+ $range_end_increment month", $now );

			$slots_in_range  = $this->appointment_form->product->get_slots_in_range( $min_date, $max_date );
			$last_element = end( $slots_in_range );
			reset( $slots_in_range ); // Restore the internal pointer.
			if ( isset( $slots_in_range[0] ) && $slots_in_range[0] > $last_element ) {
				/** In certain cases the starting date is at the end
					product->get_available_slots expects it to be at the beginning.
				**/
				$slots_in_range = array_reverse( $slots_in_range );
			}

			$available_slots = $this->appointment_form->product->get_available_slots( $slots_in_range );

			# var_dump( $slots_in_range );
			# var_dump( $available_slots );

			// Else continue with loop until we get a default date where the calendar can start at.
			if ( ! empty( $available_slots[0] ) ) {
				$default_date = $available_slots[0];
				break;
			}
		}

		return $default_date;
	}

	/**
	 * Find days which are padding days so they can be grayed out on the date picker
	 */
	protected function find_padding_slots() {
		$padding_days = WC_Appointments_Controller::find_padding_day_slots( $this->appointment_form->product->id );
		$this->args['padding_days'] = $padding_days;
	}

	/**
	 * Finds days which are fully scheduled already so they can be sloted on the date picker
	 * @return array()
	 */
	protected function find_fully_scheduled_slots() {
		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $this->appointment_form->product->id );

		$this->args['partially_scheduled_days'] = $scheduled['partially_scheduled_days'];
		$this->args['remaining_scheduled_days'] = $scheduled['remaining_scheduled_days'];
		$this->args['fully_scheduled_days']     = $scheduled['fully_scheduled_days'];
	}

	/**
	 * Find days which are padding days so they can be grayed out on the date picker
	 */
	protected function find_discounted_slots() {
		$discounted_days = WC_Appointments_Controller::find_discounted_day_slots( $this->appointment_form->product->id );
		$this->args['discounted_days'] = $discounted_days;
	}
}
