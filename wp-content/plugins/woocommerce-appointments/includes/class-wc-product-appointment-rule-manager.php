<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class that parses and returns rules for appointable products
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
		$numdays = 1 + ( $to_date - $from_date ) / 60 / 60 / 24;

		for ( $i = 0; $i < $numdays; $i ++ ) {
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

			$cost           = apply_filters( 'woocommerce_appointments_process_cost_rules_cost', $fields['cost'], $fields, $key );
			$modifier       = $fields['modifier'];
			$base_cost      = apply_filters( 'woocommerce_appointments_process_cost_rules_base_cost', $fields['base_cost'], $fields, $key );
			$base_modifier  = $fields['base_modifier'];
			$override_slot = apply_filters( 'woocommerce_appointments_process_cost_rules_override_slot', ( isset( $fields['override_slot'] ) ? $fields['override_slot'] : '' ), $fields, $key );

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
	 * Returns processed/formatted global rules
	 */
	public static function get_global_availability_rules() {
		// Get global rules.
		$global_rules = get_option( 'wc_global_appointment_availability', array() );
		$processed_global_rules = self::format_availability_rules( array_reverse( $global_rules ), 'global' );

		return $processed_global_rules;
	}

	/**
	 * Returns processed/formatted product rules
	 * @param  int $product_id  Id of product for which we want processed/formatted rules
	 */
	public static function get_product_availability_rules( $product_id ) {
		// Get product rules.
		$product_rules = (array) get_post_meta( $product_id, '_wc_appointment_availability', true );
		$processed_product_rules = self::format_availability_rules( array_reverse( $product_rules ), 'product' );

		return $processed_product_rules;
	}

	/**
	 * Returns processed/formatted staff rules
	 * @param  int $staff_id  Id of staff for which we want processed/formatted rules
	 */
	public static function get_staff_availability_rules( $staff_id ) {
		// Get staff rules.
		$staff_rules = (array) get_user_meta( $staff_id, '_wc_appointment_availability', true );
		$processed_staff_rules = self::format_availability_rules( array_reverse( $staff_rules ), 'staff' );

		return $processed_staff_rules;
	}

	/**
	 * Process and return formatted availability rules
	 * @param  $rules array
	 * @return array
	 */
	public static function format_availability_rules( $rules, $level ) {
		$formatted_rules = array();

		if ( empty( $rules ) ) {
			return $formatted_rules;
		}

		// See what types of rules we have before getting the rules themselves.
		$rule_types = array();

		foreach ( $rules as $fields ) {
			if ( empty( $fields['appointable'] ) ) {
				continue;
			}
			$rule_types[] = $fields['type'];
		}
		$rule_types = array_filter( $rule_types );

		// Go through rules.
		foreach ( $rules as $order_on_product => $fields ) {
			if ( empty( $fields['appointable'] ) ) {
				continue;
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

			// Enable days when user defines time rules, but not day rules.
			if ( ! in_array( 'custom', $rule_types ) && ! in_array( 'days', $rule_types ) && ! in_array( 'months', $rule_types ) && ! in_array( 'weeks', $rule_types ) ) {
				if ( 'time:range' === $fields['type'] ) {
					if ( 'yes' === $fields['appointable'] ) {
						$formatted_rules[] = array(
							'type'     => 'custom',
							'range'    => self::get_custom_range( $fields['from_date'], $fields['to_date'], true ),
							'priority' => $priority,
							'qty'      => $qty,
							'level'    => $level,
							'order'    => $order_on_product,
						);
					}
				} else {
					if ( strrpos( $fields['type'], 'time:' ) === 0 ) {
						list( , $day ) = explode( ':', $fields['type'] );
						if ( 'yes' === $fields['appointable'] ) {
							$formatted_rules[] = array(
								'type'     => 'days',
								'range'    => self::get_days_range( $day, $day, true ),
								'priority' => $priority,
								'qty'      => $qty,
								'level'    => $level,
								'order'    => $order_on_product,
							);
						}
					} elseif ( strrpos( $fields['type'], 'time' ) === 0 ) {
						if ( 'yes' === $fields['appointable'] ) {
							$formatted_rules[] = array(
								'type'     => 'days',
								'range'    => self::get_days_range( 0, 7, true ),
								'priority' => $priority,
								'qty'      => $qty,
								'level'    => $level,
								'order'    => $order_on_product,
							);
						}
					}
				}
			}

			if ( $type_availability ) {
				$formatted_rules[] = array(
					'type'     => $fields['type'],
					'range'    => $type_availability,
					'priority' => $priority,
					'qty'      => $qty,
					'level'    => $level,
					'order'    => $order_on_product,
				);
			}
		}

		return $formatted_rules;
	}

	/**
	 * Process and return formatted staff unavailability rules
	 * @param  $availability array
	 * @param  $rule_type string
	 * @return array
	 */
	public static function process_staff_unavailability_rules( $availability, $rule_type ) {
		// Placeholder array to contain the periods when everyone is available.
		$periods = array();

		while ( true ) {
			// Select every person's earliest date, then choose the latest of these dates.
			$start = array_reduce( $availability, function( $carry, $ranges ) {
				$start = array_reduce( $ranges, function( $carry, $range ) {
					// This person's earliest start date.
					return ! $carry ? $range[0] : min( $range[0], $carry );
				} );
				// The latest of all the start dates.
				return ! $carry ? $start : max( $start, $carry );
			} );

			// Select each person's range which contains this date.
			$matching_ranges = array_filter( array_map( function( $ranges ) use ( $start ) {
				return current( array_filter( $ranges, function( $range ) use ( $start ) {
					// The range starts before and ends after the start date.
					return $range[0] <= $start && $range[1] >= $start;
				} ) );
			}, $availability ) );

			// If anybody doesn't have a range containing the date, we're finished and can exit the loop.
			if ( count( $matching_ranges ) < count( $availability ) ) {
				break;
			}

			// Find the earliest of the ranges' end dates, and this completes our first period that everyone can attend.
			$end = array_reduce( $matching_ranges, function( $carry, $range ) {
				return ! $carry ? $range[1] : min( $range[1], $carry );
			} );

			// Add it to our list of periods.
			$periods[] = array( $start, $end );

			// Remove any availability periods which finish before the end of this new period.
			array_walk( $availability, function( &$ranges ) use ( $end ) {
				$ranges = array_filter( $ranges, function( $range ) use ( $end ) {
					return $range[1] > $end;
				} );
			} );
		}

		// Output the answer in the specified format.
		$processed_rules = array();
		$count = 0;
		foreach ( $periods as $period ) {
			$processed_rules[ $count ]['type'] = $rule_type;
			$processed_rules[ $count ]['appointable'] = 'no';
			$processed_rules[ $count ]['qty'] = '';
			$processed_rules[ $count ]['from'] = $period[0];
			$processed_rules[ $count ]['to'] = $period[1];

			$count++;
		}

		return $processed_rules;
	}

	/**
	 * Get the minutes that should be available based on the rules and the date to check.
	 *
	 * The minutes are returned in a range from the start incrementing minutes right up to the last available minute.
	 *
	 * @param array $rules
	 * @param int $check_date
	 * @return array $appointable_minutes
	 */
	public static function get_minutes_from_rules( $rules, $check_date ) {
		$appointable_minutes = array();

		// Reverse to correct order.
		#$rules = array_reverse( $rules );
		#var_dump( $rules );

		foreach ( $rules as $rule ) {
			$type	= $rule['type'];
			$range	= $rule['range'];

			if ( strrpos( $type, 'time' ) === 0 ) {

				if ( 'time:range' === $type ) {
					$year = date( 'Y', $check_date );
					$month = date( 'n', $check_date );
					$day = date( 'j', $check_date );

					if ( ! isset( $range[ $year ][ $month ][ $day ] ) ) {
						continue;
					}

					$day_mod = 0;
					$from = $range[ $year ][ $month ][ $day ]['from'];
					$to   = $range[ $year ][ $month ][ $day ]['to'];
					$rule_val = $range[ $year ][ $month ][ $day ]['rule'];
				} else {
					$day_mod = 0;
					if ( ! empty( $range['day'] ) ) {
						if ( date( 'N', $check_date ) != $range['day'] ) {
							$day_mod = 1440 * ( $range['day'] - date( 'N', $check_date ) );
						}
					// skip this rule for all dates, except selected one
					} elseif ( ! empty( $range['date'] ) ) {
						if ( date( 'Y-m-d', $check_date ) != $range['date'] ) {
							continue;
						}
					}

					$from = $range['from'];
					$to   = $range['to'];
					$rule_val = $range['rule'];
				}

				$from_hour    = absint( date( 'H', strtotime( $from ) ) );
				$from_min     = absint( date( 'i', strtotime( $from ) ) );
				$to_hour      = absint( date( 'H', strtotime( $to ) ) );
				$to_min       = absint( date( 'i', strtotime( $to ) ) );

				// If "to" is set to midnight, it is safe to assume they mean the end of the day php wraps 24 hours to "12AM the next day"
				if ( 0 === $to_hour ) {
					$to_hour = 24;
				}

				$minute_range = array( ( ( $from_hour * 60 ) + $from_min ) + $day_mod, ( ( $to_hour * 60 ) + $to_min ) + $day_mod );
				$merge_ranges = array();

				// if first time in range is larger than second, we assume they want to go over midnight.
				if ( $minute_range[0] > $minute_range[1] ) {
					$merge_ranges[] = array( $minute_range[0], 1440 ); #from
					$merge_ranges[] = array( $minute_range[0], ( 1440 + $minute_range[1] ) ); #to
				} else {
					$merge_ranges[] = array( $minute_range[0], $minute_range[1] ); #from, to
				}

				foreach ( $merge_ranges as $range ) {
					if ( $appointable = $rule_val ) {
						// If this time range is appointable, add to appointable minutes
						$appointable_minutes = array_merge( $appointable_minutes, range( $range[0], $range[1] ) );
					} else {
						// If this time range is not appointable, remove from appointable minutes
						$appointable_minutes = array_diff( $appointable_minutes, range( $range[0] + 1, $range[1] - 1 ) );
					}
				}
			}
		}

		// Get unique array elements.
		$appointable_minutes = array_unique( $appointable_minutes );

		// Sort array.
		sort( $appointable_minutes );

		return $appointable_minutes;
	}

}
