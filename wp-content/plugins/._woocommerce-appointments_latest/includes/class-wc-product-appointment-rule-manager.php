<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class that parses and returns rules for appointable products.
 */
class WC_Product_Appointment_Rule_Manager {

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_custom_range( $from, $to, $value ) {
		$availability = array();
		$from_date    = strtotime( $from );
		$to_date      = strtotime( $to );

		if ( empty( $to ) || empty( $from ) || $to_date < $from_date ) {
			return;
		}

		// We have at least 1 day, even if from_date == to_date
		$number_of_days = 1 + ( $to_date - $from_date ) / 60 / 60 / 24;

		for ( $i = 0; $i < $number_of_days; $i ++ ) {
			$year  = date( 'Y', strtotime( "+{$i} days", $from_date ) );
			$month = date( 'n', strtotime( "+{$i} days", $from_date ) );
			$day   = date( 'j', strtotime( "+{$i} days", $from_date ) );

			$availability[ $year ][ $month ][ $day ] = $value;
		}

		return $availability;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_months_range( $from, $to, $value ) {
		$months = array();
		$diff   = $to - $from;
		$diff   = ( $diff < 0 ) ? 12 + $diff : $diff;
		$month  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$months[ $month ] = $value;

			$month ++;

			if ( $month > 52 ) {
				$month = 1;
			}
		}

		return $months;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_weeks_range( $from, $to, $value ) {
		$weeks = array();
		$diff  = $to - $from;
		$diff  = ( $diff < 0 ) ? 52 + $diff : $diff;
		$week  = $from;

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$weeks[ $week ] = $value;

			$week ++;

			if ( $week > 52 ) {
				$week = 1;
			}
		}

		return $weeks;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_days_range( $from, $to, $value ) {
		$day_of_week  = $from;
		$diff         = $to - $from;
		$diff         = ( $diff < 0 ) ? 7 + $diff : $diff;
		$days         = array();

		for ( $i = 0; $i <= $diff; $i ++ ) {
			$days[ $day_of_week ] = $value;

			$day_of_week ++;

			if ( $day_of_week > 7 ) {
				$day_of_week = 1;
			}
		}

		return $days;
	}

	/**
	 * Get a range and put value inside each day
	 *
	 * @param  string $from
	 * @param  string $to
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_time_range( $from, $to, $value, $day = 0 ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
			'day'  => $day,
		);
	}

	/**
	 * Get a time range for a set of custom dates
	 * @param  string $from_date
	 * @param  string $to_date
	 * @param  string $from_time
	 * @param  string $to_time
	 * @param  mixed $value
	 * @return array
	 */
	private static function get_time_range_for_custom_date( $from_date, $to_date, $from_time, $to_time, $value ) {
		$time_range = array(
			'from' => $from_time,
			'to'   => $to_time,
			'rule' => $value,
		);
		return self::get_custom_range( $from_date, $to_date, $time_range );
	}

	/**
	 * Get duration range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_duration_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
		);
	}

	/**
	 * Get slots range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_slots_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
		);
	}

	/**
	 * Get quant range
	 * @param  [type] $from
	 * @param  [type] $to
	 * @param  [type] $value
	 * @return [type]
	 */
	private static function get_quant_range( $from, $to, $value ) {
		return array(
			'from' => $from,
			'to'   => $to,
			'rule' => $value,
		);
	}

	/**
	 * Process and return formatted cost rules
	 * @param  $rules array
	 * @return array
	 */
	public static function process_pricing_rules( $rules ) {
		$costs = array();
		$index = 1;

		if ( ! is_array( $rules ) ) {
			return $costs;
		}

		// Go through rules.
		foreach ( $rules as $key => $fields ) {
			if ( empty( $fields['cost'] ) && empty( $fields['base_cost'] ) && empty( $fields['override_slot'] ) ) {
				continue;
			}

			$cost           = apply_filters( 'woocommerce_appointments_process_pricing_rules_cost', $fields['cost'], $fields, $key );
			$modifier       = $fields['modifier'];
			$base_cost      = apply_filters( 'woocommerce_appointments_process_pricing_rules_base_cost', $fields['base_cost'], $fields, $key );
			$base_modifier  = $fields['base_modifier'];
			$override_slot  = apply_filters( 'woocommerce_appointments_process_pricing_rules_override_slot', ( isset( $fields['override_slot'] ) ? $fields['override_slot'] : '' ), $fields, $key );

			$cost_array = array(
				'base'     => array( $base_modifier, $base_cost ),
				'slot'     => array( $modifier, $cost ),
				'override' => $override_slot,
			);

			$type_function = self::get_type_function( $fields['type'] );
			if ( 'get_time_range_for_custom_date' === $type_function ) {
				$type_costs = self::$type_function( $fields['from_date'], $fields['to_date'], $fields['from'], $fields['to'], $cost_array );
			} else {
				$type_costs = self::$type_function( $fields['from'], $fields['to'], $cost_array );
			}

			// Ensure day gets specified for time: rules.
			if ( strrpos( $fields['type'], 'time:' ) === 0 && 'time:range' !== $fields['type'] ) {
				list( , $day ) = explode( ':', $fields['type'] );
				$type_costs['day'] = absint( $day );
			}

			if ( $type_costs ) {
				$costs[ $index ] = array( $fields['type'], $type_costs );
				$index ++;
			}
		}

		return $costs;
	}

	/**
	 * Returns a function name (for this class) that returns our time or date range
	 * @param  string $type rule type
	 * @return string       function name
	 */
	public static function get_type_function( $type ) {
		if ( 'time:range' === $type ) {
			return 'get_time_range_for_custom_date';
		}

		return strrpos( $type, 'time:' ) === 0 ? 'get_time_range' : 'get_' . $type . '_range';
	}

	/**
	 * Process and return formatted availability rules
	 *
	 * @version  3.3.0
	 * @param    $rules array
	 * @param    string $level. Staff, Product or Globally
	 * @return   array
	 */
	public static function process_availability_rules( $rules, $level ) {
		$formatted_rules = array();

		if ( empty( $rules ) ) {
			return $formatted_rules;
		}

		// Go through rules
		foreach ( $rules as $order_on_product => $fields ) {
			if ( empty( $fields['appointable'] ) ) {
				continue;
			}

			// Do not include dates that are in the past.
			if ( in_array( $fields['type'], array( 'custom', 'time:range' ) ) ) {
				$to_date = ! empty( $fields['to_date'] ) ? $fields['to_date'] : $fields['to'];
			 	if ( strtotime( $to_date ) < strtotime( 'midnight -1 day' ) ) {
					continue;
				}
			}

			$type_function = self::get_type_function( $fields['type'] );
			$appointable = 'yes' === $fields['appointable'] ? true : false;
			if ( 'get_time_range_for_custom_date' === $type_function ) {
				$type_availability = self::$type_function( $fields['from_date'], $fields['to_date'], $fields['from'], $fields['to'], $appointable );
			} else {
				$type_availability = self::$type_function( $fields['from'], $fields['to'], $appointable );
			}

			$priority = intval( ( isset( $fields['priority'] ) ? $fields['priority'] : 10 ) );
			$qty = intval( ( isset( $fields['qty'] ) ? absint( $fields['qty'] ) : 0 ) );

			// Ensure day gets specified for time: rules.
			if ( strrpos( $fields['type'], 'time:' ) === 0 && 'time:range' !== $fields['type'] ) {
				list( , $day ) = explode( ':', $fields['type'] );
				$type_availability['day'] = absint( $day );
			}

			if ( $type_availability ) {
				$formatted_rule = array(
					'type'     => $fields['type'],
					'range'    => $type_availability,
					'priority' => $priority,
					'qty'      => $qty,
					'level'    => $level,
					'order'    => $order_on_product,
				);

				if ( 'staff' === $level && ! empty( $fields['staff_id'] ) ) {
					$formatted_rule['staff_id'] = $fields['staff_id'];
				}

				$formatted_rules[] = $formatted_rule;
			}
		}

		return $formatted_rules;
	}

	/**
	 * Get the minutes that should be available based on the rules and the date to check.
	 *
	 * The minutes are returned in a range from the start incrementing minutes right up to the last available minute.
	 *
	 * This function expects the rules to be ordered in the sequence that is should be processed. Later rule minutes
	 * will override prior rule minutes in the order given.
	 *
	 * @since 3.1.1 moved from WC_Product_Appointment.
	 *
	 * @param array $rules
	 * @param int $check_date
	 *
	 * @return array $appointable_minutes
	 */
	public static function get_minutes_from_rules( $rules, $check_date ) {
		$appointable_minutes = array();
		$staff_minutes = array();

		foreach ( $rules as $rule ) {
			// Something terribly wrong if a rule has no level.
			if ( ! isset( $rule['level'] ) ) {
				continue;
			}

			$data_for_rule = self::get_rule_minute_range( $rule, $check_date );

			#print '<pre>'; print_r( $rule ); print '</pre>';
			#print '<pre>'; print_r( $data_for_rule ); print '</pre>';

			// split up the rules on a staff level to be dealt with independently
			// after the rules loop. This ensure staff do not affect one another
			if ( 'staff' === $rule['level'] ) {
				$staff_id                     = $rule['staff_id'];
				$availability_key             = $data_for_rule['is_appointable'] ? 'appointable' : 'not_appointable';
				// adding minutes in the order of the rules received, higher index higher override power.
				$staff_minutes[ $staff_id ][] = array( $availability_key => $data_for_rule['minutes'] );
				continue;
			}

			// At this point we assume all staff rules have been processed as they have a lower
			// override order in the $rules given.

			// Remove available staff minutes if being overridden at the product or global level
			if ( ! self::check_timestamp_against_rule( $check_date, $rule, true ) ) {
				$staff_minutes = array();
			}

			if ( $data_for_rule['is_appointable'] ) {
				// If this time range is appointable, add to appointable minutes
				$appointable_minutes = array_merge( $appointable_minutes, $data_for_rule['minutes'] );
				continue;
			}

			// Handle NON-staff removal of unavailable minutes.
			$appointable_minutes = array_diff( $appointable_minutes, $data_for_rule['minutes'] );

			// Handle staff specific removal of unavailable minutes.
			foreach ( $staff_minutes as $id => $minute_ranges ){
				foreach( $minute_ranges as $index => $minute_range ) {
					if ( ! isset(  $minute_range['appointable'] )  || empty( $data_for_rule['minutes'] )) {
						continue;
					}
					// remove the last minute from the array for hours not to be thrown off
					// what happens is that this last minute could fall right at the beginning of the
					// next slot like 7:00 to 8:00 range the last minute will be on 8:00 which means
					// 8:00 will be removed, leaving the resulting range to start at 8:01.
					array_pop( $data_for_rule['minutes'] );
					$staff_minutes[ $id ][ $index ]['appointable'] = array_diff( $minute_range['appointable'], $data_for_rule['minutes'] );
				}
			}
		}

		#print '<pre>'; print_r( $staff_minutes ); print '</pre>';

		// One staff should not override the other, when automatically assigned: as long as one is available.
		foreach ( $staff_minutes as $staff_id => $minutes_for_rule_order ) {
			$staff_minutes = array();

			foreach ( $minutes_for_rule_order as $rule_minutes_with_availability ) {
				$is_appointable = isset( $rule_minutes_with_availability['appointable'] );
				if ( $is_appointable ) {
					$staff_minutes = array_merge( $staff_minutes, $rule_minutes_with_availability['appointable'] );
				} else {
					$staff_minutes = array_diff( $staff_minutes, $rule_minutes_with_availability['not_appointable'] );
				}
			}

			$appointable_minutes = array_merge( $staff_minutes, $appointable_minutes );
		}

		$appointable_minutes = array_unique( array_values( $appointable_minutes ) );

		sort( $appointable_minutes );

		#var_dump($check_date);
		#var_dump($appointable_minutes);
		#var_dump('<br/><br/>');

		return $appointable_minutes;
	}

	/**
	 * This function is a mediator that simplifies the creation of
	 * a data object representing the range of rules minutes and the property of appointable or not.
	 *
	 * @since 3.5.6
	 *
	 * @param array $rule
	 * @param int $check_date
	 *
	 * @return array $minute_range
	 */
	public static function get_rule_minute_range( $rule, $check_date ) {
		$minute_range = array(
			'is_appointable' => false,
			'minutes'     => array(),
		);

		if ( strpos( $rule['type'], 'time' ) > -1 ) {
			$minute_range = self::get_rule_minutes_for_time( $rule, $check_date );
		} elseif ( 'days' === $rule['type'] ) {
			$minute_range = self::get_rule_minutes_for_days( $rule, $check_date );
		} elseif ( 'weeks' === $rule['type'] ) {
			$minute_range = self::get_rule_minutes_for_weeks( $rule, $check_date );
		} elseif ( 'months' === $rule['type'] ) {
			$minute_range = self::get_rule_minutes_for_months( $rule, $check_date );
		} elseif ( 'custom' === $rule['type'] ) {
			$minute_range = self::get_rule_minutes_for_custom( $rule, $check_date );
		}

		return $minute_range;
	}

	/**
	 * Get minutes from rules for a time rule type.
	 *
	 * @since 3.1.1
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_time( $rule, $check_date ) {
		$minutes = array(
			'is_appointable' => false,
			'minutes'        => array(),
		);
		$type    = $rule['type'];
		$range   = $rule['range'];

		$year        = absint( date( 'Y', $check_date ) );
		$month       = absint( date( 'n', $check_date ) );
		$day         = absint( date( 'j', $check_date ) );
		$day_of_week = absint( date( 'N', $check_date ) );

		$day_modifier = 0;

		#var_dump( $range );

		if ( 'time:range' === $type ) { // type: date range with time

			if ( ! isset( $range[ $year ][ $month ][ $day ] ) ) {
				return  $minutes;
			} else {
				$range = $range[ $year ][ $month ][ $day ];
			}

			$from                      = $range['from'];
			$to                        = $range['to'];
			$minutes['is_appointable'] = $range['rule'];

		} elseif ( strpos( $rule['type'], 'time:' ) > -1 ) { // type: single week day with time
			$day_of_week_ranges = array();

			#var_dump( 'works' );
			#var_dump( '<br/>' );

			// Add next day to rule if "to" rule is before "from".
			if ( ! empty( $range['day'] ) ) {
				$day_of_week_ranges[] = $range['day'];

				#var_dump( 'range_to: ' . $range['to'] );
				#var_dump( 'range_from: ' . $range['from'] );
				#var_dump( '<br/>' );
				#var_dump( 'range_day: ' . $range['day'] );
				#var_dump( 'day_of_week: ' . $day_of_week );
				#var_dump( '<br/>' );

				if ( ( $range['to'] < $range['from'] ) && ( $range['day'] !== $day_of_week ) ) {
					// Convert 8 to monday and add a day to all others for overnight rules;
					$day_of_week_ranges[] = ( $range['day'] + 1 ) === 8 ? 1 : ( $range['day'] + 1 );
					#var_dump( $day_of_week_ranges );
					#var_dump( $day_of_week );
					#var_dump( '<br/>' );
				}
			}

			// Skip.
			if ( ! empty( $range['day'] ) && ! in_array( $day_of_week, $day_of_week_ranges ) ) {
				return  $minutes;
			}

			/*
			if (  $day_of_week != $range['day'] ) {
				return  $minutes;
			}
			*/

			$from                      = $range['from'];
			$to                        = $range['to'];
			$minutes['is_appointable'] = $range['rule'];

		} else {  // type: time all week per day

			$from                      = $range['from'];
			$to                        = $range['to'];
			$minutes['is_appointable'] = $range['rule'];

		}

		$from_hour    = absint( date( 'H', strtotime( $from ) ) );
		$from_min     = absint( date( 'i', strtotime( $from ) ) );
		$to_hour      = absint( date( 'H', strtotime( $to ) ) );
		$to_min       = absint( date( 'i', strtotime( $to ) ) ); #deduct last minute, so next slot is built correctly

		// If "to" is set to midnight, it is safe to assume they mean the end of the day
		// php wraps 24 hours to "12AM the next day"
		if ( 0 === $to_hour && 0 === $to_min ) {
			$to_hour = 24;
		}

		// If "to" is before "from", it is safe to assume they mean the from is midnight
		// php wraps 0 hours to "12AM the same day"
		if ( ( $to_hour <= $from_hour ) && ! empty( $range['day'] ) && ( $range['day'] !== $day_of_week ) ) {
			$from_hour = 0;
			#var_dump( $range['day'] );
			#var_dump( $day_of_week );
		}

		$minute_range = array( ( ( $from_hour * 60 ) + $from_min ) + $day_modifier, ( ( $to_hour * 60 ) + ( $to_min - 1 ) ) + $day_modifier ); #deduct last minute from $to_min, so next slot is built correctly
		$merge_ranges = array();

		#var_dump( $minute_range );

		// if first time in range is larger than second, we
		// assume they want to go over midnight
		if ( $minute_range[0] > $minute_range[1] ) {
			$merge_ranges[] = array( $minute_range[0], 1440 );
			$merge_ranges[] = array( $minute_range[0], ( 1440 + $minute_range[1] ) );
		} else {
			$merge_ranges[] = array( $minute_range[0], $minute_range[1] );
		}

		foreach ( $merge_ranges as $range ) {
			// Add ranges to minutes this rule affects.
			$minutes['minutes'] = array_merge( $minutes['minutes'], range( $range[0], $range[1] ) );
		}

		return $minutes;
	}

	/**
	 * Get minutes from rules for days rule type.
	 *
	 * @since 3.1.1
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_days( $rule, $check_date ) {
		$_rules      = $rule['range'];
		$minutes     = array();
		$is_appointable = false;
		$day_of_week = intval( date( 'N', $check_date ) );

		if ( isset( $_rules[ $day_of_week ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_appointable = $_rules[ $day_of_week ];
		}

		return array(
			'is_appointable' => $is_appointable,
			'minutes'        => $minutes,
		);
	}

	/**
	 * Get minutes from rules for a weeks rule type.
	 *
	 * @since 3.1.1
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_weeks( $rule, $check_date ) {

		$range       = $rule['range'];
		$week_number = intval( date( 'W', $check_date ) );
		$minutes     = array();
		$is_appointable = false;

		if ( isset( $range[ $week_number ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_appointable = $range[ $week_number ];
		}

		return array(
			'is_appointable' => $is_appointable,
			'minutes'        => $minutes,
		);
	}

	/**
	 * Get minutes from rules for a months rule type.
	 *
	 * @since 3.1.1
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_months( $rule, $check_date ) {

		$range       = $rule['range'];
		$month       = date( 'n', $check_date );
		$minutes     = array();
		$is_appointable = false;
		if ( isset( $range[ $month ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_appointable = $range[ $month ];
		}

		return array(
			'is_appointable' => $is_appointable,
			'minutes'        => $minutes,
		);
	}

	/**
	 * Get minutes from rules for custom rule type.
	 * @since 3.1.1
	 * @param $rule
	 * @param integer $check_date
	 *
	 * @return array
	 */
	public static function get_rule_minutes_for_custom( $rule, $check_date ) {

		$range = $rule['range'];
		$year  = date( 'Y', $check_date );
		$month = date( 'n', $check_date );
		$day   = date( 'j', $check_date );

		$minutes     = array();
		$is_appointable = false;

		if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
			$minutes     = range( 0, 1440 );
			$is_appointable = $range[ $year ][ $month ][ $day ];
		}

		return array(
			'is_appointable' => $is_appointable,
			'minutes'        => $minutes,
		);
	}

	/**
	 * Sort rules in order of precedence.
	 *
	 * @version 3.1.1 sort order reversed
	 * The order produced will be from the lowest to the highest.
	 * The elements with higher indexes overrides those with lower indexes e.g. `4` overrides `3`
	 * Index corresponds to override power. The higher the element index the higher the override power
	 *
	 * Level    : `global` > `product` > `product` (greater in terms off override power)
	 * Priority : within a level
	 * Order    : Within a priority The lower the order index higher the override power.
	 *
	 * @param array $rule1
	 * @param array $rule2
	 *
	 * @return integer
	 */
	public static function sort_rules_callback( $rule1, $rule2 ) {
		$level_weight = apply_filters( 'wc_availability_rules_priority', array(
			'staff' => 5,
			'product' => 3,
			'global' => 1,
		));

		// The override power goes from the outside inward.
		// Priority is outside which means it has the most weight when sorting.
		// Then level(global, product, staff)
		// Lastly order is applied within the level.
		if ( $rule1['priority'] === $rule2['priority'] ) {
			if ( $level_weight[ $rule1['level'] ] === $level_weight[ $rule2['level'] ] ) {
				// if `order index of 1` < `order index of 2` $rule1 one has a higher override power. So we
				// increase the index for $rule1 which corresponds to override power.
				return ( $rule1['order'] < $rule2['order'] ) ? 1 : -1;
			}

			// if `level of 1` < `level of 2` $rule1 must have lower override power. So we
			// decrease the index for 1 which corresponds to override power.
			return $level_weight[ $rule1['level'] ] < $level_weight[ $rule2['level'] ] ? -1 : 1;
		}

		// if `priority of 1` < `priority of 2` $rule1 must have lower override power. So we
		// decrease the index for 1 which corresponds to override power.
		return $rule1['priority'] < $rule2['priority'] ? 1 : -1;
	}

	/**
	 * Filter out all but time rules.
	 * @param  array $rule
	 * @return boolean
	 */
	private static function filter_time_rules( $rule ) {
		return ! empty( $rule['type'] ) && ! in_array( $rule['type'], array( 'days', 'custom', 'months', 'weeks' ) );
	}

	/**
	 * Check a appointable product's availability rules against a time range and return if appointable or not.
	 *
	 * @param  WC_Product_Appointment $appointable_product
	 * @param  int $staff_id
	 * @param  int $start timestamp
	 * @param  int $end timestamp
	 * @return boolean
	 */
	public static function check_range_availability_rules( $appointable_product, $staff_id, $start, $end ) {
		// This is a time range.
		if ( in_array( $appointable_product->get_duration_unit(), array( 'minute', 'hour' ) ) ) {
			return self::check_availability_rules_against_time( $appointable_product, $start, $end, $staff_id );
		} // Else this is a date range (days).
		else {
			$timestamp = $start;

			while ( $timestamp < $end ) {
				if ( ! self::check_availability_rules_against_date( $appointable_product, $timestamp, $staff_id ) ) {
					return false;
				}
				if ( 'start' === $appointable_product->get_availability_span() ) {
					break; // Only need to check first day
				}
				$timestamp = strtotime( '+1 day', $timestamp );
			}
		}

		return true;
	}

	/**
	 * Check a time against the time specific availability rules
	 *
	 * @param integer $slot_start_time
	 * @param integer $slot_end_time
	 * @param integer $staff_id
	 * @param WC_Product_Appointment $appointable_product
	 * @param bool|null If not null, it will default to the boolean value. If null, it will use product default availability.

	 *
	 * @return bool available or not
	 */
	public static function check_availability_rules_against_time( $appointable_product, $slot_start_time, $slot_end_time, $staff_id, $get_capacity = false, $appointable = null ) {
		if ( is_null( $appointable ) ) {
			$appointable = $appointable_product->get_default_availability();
		}

		$slot_start_time = is_numeric( $slot_start_time ) ? $slot_start_time : strtotime( $slot_start_time );
		$slot_end_time   = is_numeric( $slot_end_time ) ? $slot_end_time : strtotime( $slot_end_time );

		#var_dump( date( 'Y-m-d H:i', $slot_start_time ) . '__' . date( 'Y-m-d H:i', $slot_end_time ) . '<br/>' );

		$rules           = $appointable_product->get_availability_rules( $staff_id );

		// Capacity.
		$capacity        = $appointable_product->get_available_qty( $staff_id );

		// Get the date values for the slots being checked
		$slot_year   = intval( date( 'Y', $slot_start_time ) );
		$slot_month  = intval( date( 'n', $slot_start_time ) );
		$slot_date   = intval( date( 'j', $slot_start_time ) );
		$slot_day_no = intval( date( 'N', $slot_start_time ) );
		$slot_week   = intval( date( 'W', $slot_start_time ) );

		// Slot next day.
		$slot_next_day = false;

		// default from and to for the whole day
		$from = strtotime( 'midnight', $slot_start_time );
		$to   = strtotime( 'midnight + 1 day', $slot_start_time );

		#var_dump( $rules );

		foreach ( $rules as $rule ) {
			$type  		= $rule['type'];
			$range 		= $rule['range'];
			$qty   		= $rule['qty'] && $rule['qty'] >= 1  ? $rule['qty'] : $capacity;

			#var_dump( $qty );
			#var_dump( $rule['qty'] );
			#var_dump( $capacity );
			#print_r( '<br/>' );

			// Handling NON-time specific rules first.
			if ( in_array( $type, array( 'days', 'custom', 'months', 'weeks' ) ) ) {
				if ( 'days' === $type ) {
					if ( ! isset( $range[ $slot_day_no ] ) ) {
						continue;
					}
				} elseif ( 'custom' === $type ) {
					if ( ! isset( $range[ $slot_year ][ $slot_month ][ $slot_date ] ) ) {
						continue;
					}
				} elseif ( 'months' === $type ) {
					if ( ! isset( $range[ $slot_month ] ) ) {
						continue;
					}
				} elseif ( 'weeks' === $type ) {
					if ( ! isset( $range[ $slot_week ] ) ) {
						continue;
					}
				}
				$rule_val = self::check_timestamp_against_rule( $slot_start_time, $rule, $appointable_product->get_default_availability(), $capacity );
				$from     = '00:00'; // start of day
				$to       = '00:00'; // end of day
				$capacity = self::check_timestamp_against_rule( $slot_start_time, $rule, $appointable_product->get_default_availability(), $capacity, ( $get_capacity ? true : false ) );
			}

			// Handling all time specific rules
			$apply_rule_times = false;
			if ( 'time:range' === $type ) {
				if ( ! isset( $range[ $slot_year ][ $slot_month ][ $slot_date ] ) ) {
					continue;
				}
				$time_range_rule = $range[ $slot_year ][ $slot_month ][ $slot_date ];
				$rule_val = $time_range_rule['rule'];
				$from     = $time_range_rule['from'];
				$to       = $time_range_rule['to'];
				$apply_rule_times = true;
			} elseif ( false !== strpos( $type, 'time' ) ) {

				#var_dump( $slot_day_no );
				#var_dump( $range['day'] );

				// Add next day to rule if "to" rule is before "from".
				if ( ! empty( $range['day'] ) ) {
					$prev_day = ( $slot_day_no - 1 ) === 0 ? 7 : ( $slot_day_no - 1 );
					if ( ( $range['to'] < $range['from'] ) && ( $range['day'] === $prev_day ) ) {
						$slot_next_day = $range['day'];
					} else {
						$slot_next_day = false;
					}
				}

				#var_dump( 'ruleFROM: ' . $range['from'] . ' ruleTO: ' . $range['to'] . 'ruleday: ' . $range['day'] . '____' . 'calday: ' . $slot_day_no . ' nextday: ' . $slot_next_day . '<br/>' );

				// if the day doesn't match and the day is not zero skip the rule
				// zero means all days. So rule only apply for zero or a matching day.
				if ( ! empty( $range['day'] ) && $slot_day_no != $range['day'] && $slot_next_day != $range['day'] ) {
					#var_dump( 'ruleday2: ' . $range['day'] . ' calday2: ' . $slot_day_no . '<br/>' );
					continue;
				}

				// check that the rule should be applied to the current slot
				// if not time it must be time:day_number
				if ( 'time' !== $type ) {
					if ( ! strpos( $type, (string) $slot_day_no ) && ! strpos( $type, (string) $slot_next_day ) ) {
						#var_dump( 'rultype3: ' . $type . ' calday3: ' . $slot_day_no . '<br/>' );
						continue;
					}
				}

				$rule_val = $range['rule'];
				$from     = $range['from'];
				$to       = $range['to'];
				$apply_rule_times = true;
			}

			#var_dump( $from );

			$rule_start_time = $apply_rule_times ? strtotime( $from, $slot_start_time ) : $slot_start_time;
			$rule_end_time   = $apply_rule_times ? strtotime( $to, $slot_start_time ) : $slot_start_time;


			// 24/7 availability.
			if ( $rule_start_time === $rule_end_time ) {
				#var_dump( 'x0' );
				$appointable = $rule_val;
				$capacity = $qty;
				continue;
			}

			/*
			if ( $to <= $from ) {
				$slot_start_time = strtotime( '+1 day', $slot_start_time );
			}
			*/

			// Make sure next day time rule converts correctly.
			if ( ! in_array( $type, array( 'days', 'custom', 'months', 'weeks' ) ) && ( $rule_end_time <= $rule_start_time ) ) {
				// When slot start larger than end on the next day, set rule end
				// on the next day.
				if ( ! $slot_next_day && ( date( 'ymd', strtotime( '+1 day', $slot_start_time ) ) === date( 'ymd', $slot_end_time ) ) ) {
					#var_dump( 'rule end +1 day' );
					$rule_end_time = strtotime( '+1 day', $rule_end_time );
				// When slot start larger than end on the next day, set rule end
				// on the next day.
				} elseif ( ! $slot_next_day && ( date( 'ymd', $slot_start_time ) === date( 'ymd', $slot_end_time ) ) ) {
					#var_dump( 'rule end +1 day' );
					$rule_end_time = strtotime( '+1 day', $rule_end_time );
				// When rule end time is midnight, set rule end
				// on the next day.
				} elseif ( ! $slot_next_day && ( '0000' === date( 'Hi', $rule_end_time ) ) ) {
					#var_dump( 'rule end midnight' );
					$rule_end_time = strtotime( '+1 day', $rule_end_time );
				}
				// When slot start is larger than end on the same day, set rule start
				// on the day before.
				if ( $slot_next_day && ( date( 'ymd', $slot_start_time ) === date( 'ymd', $slot_end_time ) ) ) {
					#var_dump( 'rule start -1 day' );
					$rule_start_time = strtotime( '-1 day', $rule_start_time );
				}
			}

			#var_dump( date( 'ymd H:i', $rule_start_time ) .'__'.date( 'ymd H:i', $rule_end_time ) .'_||_'. date( 'ymd H:i', $slot_start_time ) .'__'.date( 'ymd H:i', $slot_end_time ) .'=='. $rule['qty'] .'_??_'. $type . '<br/>' );

			// Reverse date/day rule.
			if ( in_array( $type, array( 'days', 'custom', 'months', 'weeks' ) ) && ( $rule_end_time <= $rule_start_time ) ) {
				if ( $slot_end_time > $rule_start_time ) {
					#var_dump( 'x1' . '__' . date( 'ymd H:i', $slot_start_time ) );
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
				if ( $slot_start_time >= $rule_start_time && $slot_end_time >= $rule_end_time ) {
					#var_dump( 'x2' . '__' . date( 'ymd H:i', $slot_start_time ) );
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
				// does this rule apply?
				// does slot start before rule start and end after rules start time {goes over start time}
				if ( $slot_start_time < $rule_start_time && $slot_end_time > $rule_start_time ) {
					#var_dump( 'x3' . '__' . date( 'ymd H:i', $slot_start_time ) );
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}
			} else {
				// Normal rule.
				if ( $slot_start_time >= $rule_start_time && $slot_end_time <= $rule_end_time ) {
				#if ( $slot_start_time < $rule_end_time && $slot_end_time > $rule_start_time ) {
					#var_dump( 'x11' . '__' . date( 'ymd H:i', $rule_start_time ) .'__'.date( 'ymd H:i', $rule_end_time ) .'_||_'. date( 'ymd H:i', $slot_start_time ) .'__'.date( 'ymd H:i', $slot_end_time ) .'=='. $rule_val .'<br/>' );
					$appointable = $rule_val;
					$capacity = $qty;
					continue;
				}

				// Specific to hour duration types. If start time is in between
				// rule start and end times the rule should be applied.
				if ( 'hour' == $appointable_product->get_duration_unit()
					 && $slot_start_time > $rule_start_time
	 				 && $slot_start_time < $rule_end_time
	 				 && $slot_end_time > $rule_start_time
	 				 && $slot_end_time < $rule_end_time ) {

						 #var_dump( 'x10' . '__' . date( 'ymd H:i', $rule_start_time ) .'__'.date( 'ymd H:i', $rule_end_time ) .'_||_'. date( 'ymd H:i', $slot_start_time ) .'__'.date( 'ymd H:i', $slot_end_time ) .'=='. $rule_val .'<br/>' );

					$appointable = $rule_val;
					$capacity = $qty;
					continue;

				}

				// If slot drops into any of the unavailable rules
				// make sure to include this rule as well.
				if ( ! $rule_val
				     && $slot_start_time >= $rule_start_time
					 && $slot_start_time < $rule_end_time ) {

						 #var_dump( 'x09' . '__' . date( 'ymd H:i', $rule_start_time ) .'__'.date( 'ymd H:i', $rule_end_time ) .'_||_'. date( 'ymd H:i', $slot_start_time ) .'__'.date( 'ymd H:i', $slot_end_time ) .'=='. $rule_val .'<br/>' );

					$appointable = $rule_val;
					$capacity = $qty;
					continue;

				}
				if ( ! $rule_val
				     && $slot_end_time > $rule_start_time
					 && $slot_end_time <= $rule_end_time ) {

						 #var_dump( 'x08' . '__' . date( 'Y-m-d H:i', $slot_start_time ) . '__' . date( 'Y-m-d H:i', $slot_end_time ) . ' ruleval=' .$rule_val . '<br/>' );

					$appointable = $rule_val;
					$capacity = $qty;
					continue;

				}
			}
		}

		#var_dump( $staff_id . ' ... ' . date( 'Y-m-d H:i', $slot_start_time ) . '__' . date( 'Y-m-d H:i', $slot_end_time ) . ' == ' . absint( $capacity ) . '<br/>' );
		#var_dump( date( 'Y-m-d H:i', $slot_start_time ) . '__' . date( 'Y-m-d H:i', $slot_end_time ) . '__' . $appointable  . '<br/>' );
		#var_dump( date( 'Y-m-d H:i', $slot_start_time ) . '__' . $get_capacity . '__' . $capacity . '_==_' . $appointable . '<br/>' );

		// Return rule type capacity.
		if ( $get_capacity ) {
			return $appointable ? absint( $capacity ) : 0;
		}

		return $appointable;
	}

	/**
	 * Check a date against the availability rules
	 *
	 * @version 2.7 Moved to this class from WC_Product_Appointment
	 *                 only apply rules if within their scope
	 *                 keep appointment value alive within the loop to ensure the next rule with higher power can override
	 * @version 2.6 removed all calls to break 2 to ensure we get to the highest
	 *                 priority rules, otherwise higher order/priority rules will not
	 *                 override lower ones and the function exit with the wrong value.
	 *
	 *
	 * @param  WC_Product_Appointment $appointable_product
	 * @param  int $staff_id
	 * @param  int $check_date timestamp
	 * @return bool available or not
	 */
	public static function check_availability_rules_against_date( $appointable_product, $check_date, $staff_id, $get_capacity = false, $appointable = null ) {
		if ( is_null( $appointable ) ) {
			$appointable = $appointable_product->get_default_availability();
		}

		// Capacity.
		$capacity = $appointable_product->get_available_qty( $staff_id );

		#var_dump($capacity);

		foreach ( $appointable_product->get_availability_rules( $staff_id ) as $rule ) {
			if ( self::does_rule_apply( $rule, $check_date ) ) {
				// passing $appointable into the next check as it overrides the previous value
				$appointable = self::check_timestamp_against_rule( $check_date, $rule, $appointable, $capacity, $get_capacity );
			}
		}

		return $appointable;
	}

	/**
	 * Does the time stamp fall within the scope of the rule?
	 *
	 * @param $rule
	 * @param $timestamp
	 * @return bool
	 */
	public static function does_rule_apply( $rule, $timestamp ) {
		$year        = intval( date( 'Y', $timestamp ) );
		$month       = intval( date( 'n', $timestamp ) );
		$day         = intval( date( 'j', $timestamp ) );
		$day_of_week = intval( date( 'N', $timestamp ) );
		$week        = intval( date( 'W', $timestamp ) );

		$range = $rule['range'];

		switch ( $rule['type'] ) {
			case 'months':
				if ( isset( $range[ $month ] ) ) {
					return true;
				}
				break;
			case 'weeks':
				if ( isset( $range[ $week ] ) ) {
					return true;
				}
				break;
			case 'days':
				if ( isset( $range[ $day_of_week ] ) ) {
					return true;
				}
				break;
			case 'custom':
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					return true;
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
				if ( $day_of_week === $range['day'] || 0 === $range['day'] ) {
					return true;
				}
				break;
			case 'time:range':
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Given a timestamp and rule check to see if the time stamp is appointable based on the rule.
	 *
	 * @since 3.0.0
	 *
	 * @param integer $timestamp
	 * @param array $rule
	 * @param boolean $default
	 * @return boolean
	 */
	public static function check_timestamp_against_rule( $timestamp, $rule, $default, $capacity = 1, $get_capacity = false ) {
		$year        = intval( date( 'Y', $timestamp ) );
		$month       = intval( date( 'n', $timestamp ) );
		$day         = intval( date( 'j', $timestamp ) );
		$day_of_week = intval( date( 'N', $timestamp ) );
		$week        = intval( date( 'W', $timestamp ) );

		$type  = $rule['type'];
		$range = $rule['range'];
		$qty   = $rule['qty'] && $rule['qty'] >= 1  ? $rule['qty'] : $capacity;

		$appointable = $default;

		switch ( $type ) {
			case 'months':
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
			case 'days':
				if ( isset( $range[ $day_of_week ] ) ) {
					$appointable = $range[ $day_of_week ];
					$capacity = $qty;
				}
				break;
			case 'custom':
				if ( isset( $range[ $year ][ $month ][ $day ] ) ) {
					$appointable = $range[ $year ][ $month ][ $day ];
					// $capacity = $qty; #skip since time:range applies it for this rule.
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
				if ( false === $default && ( $day_of_week === $range['day'] || 0 === $range['day'] ) ) {
					$appointable = $range['rule'];
					$capacity = $qty;
				}
				break;
			case 'time:range':
				if ( false === $default && ( isset( $range[ $year ][ $month ][ $day ] ) ) ) {
					$appointable = $range[ $year ][ $month ][ $day ]['rule'];
					$capacity = $qty;
				}
				break;
		}

		#var_dump( date( 'Y-m-d H:i', $timestamp ) . '__' . $appointable );
		#var_dump( date( 'Y-m-d H:i', $timestamp ) . '__' . $get_capacity . '__' . $capacity );

 		// Return rule type capacity.
 		if ( $get_capacity ) {
 			return absint( $capacity );
 		}

		return $appointable;
	}
}
