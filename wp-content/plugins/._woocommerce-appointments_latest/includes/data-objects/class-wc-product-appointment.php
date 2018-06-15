<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
	include_once( WC_APPOINTMENTS_ABSPATH . 'includes/compatibility/class-legacy-wc-product-appointment.php' );
	class WC_Product_Appointment_Compatibility extends Legacy_WC_Product_Appointment {}
} else {
	class WC_Product_Appointment_Compatibility extends WC_Product {}
}

/**
 * Class for the appointment product type.
 */
class WC_Product_Appointment extends WC_Product_Appointment_Compatibility {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $appointable_product_data = array(
		'has_additional_costs'		=> false,
		'has_price_label'			=> false,
		'price_label'				=> '',
		'has_pricing'				=> false,
		'pricing'					=> array(),
		'qty'						=> 1,
		'qty_min'					=> 1,
		'qty_max'					=> 1,
		'duration_unit'				=> 'hour',
		'duration'					=> 1,
		'interval_unit'				=> 'hour',
		'interval'					=> 1,
		'padding_duration_unit'		=> 'hour',
		'padding_duration'			=> 0,
		'min_date_unit'				=> 'day',
		'min_date'					=> 0,
		'max_date_unit'				=> 'month',
		'max_date'					=> 12,
		'user_can_cancel'			=> false,
		'cancel_limit_unit'			=> 'month',
		'cancel_limit'				=> 1,
		'cal_color'					=> '',
		'requires_confirmation'		=> false,
		'availability_span'			=> '',
		'availability_autoselect'	=> false,
		'has_restricted_days'       => false,
		'restricted_days'       	=> array(),
		'availability'				=> array(),
		'staff_label'				=> '',
		'staff_assignment'			=> '',
		'staff_id'					=> array(),
		'staff_ids'					=> array(),
		'staff_base_costs'			=> array(),
		'staff_qtys'				=> array(),
	);

	/**
	 * Stores availability rules once loaded.
	 *
	 * @var array
	 */
	public $availability_rules = array();

	/**
	 * Merges appointment product data into the parent object.
	 *
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		$this->data = array_merge( $this->data, $this->appointable_product_data );
		parent::__construct( $product );
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
		return $this->get_requires_confirmation() ? apply_filters( 'woocommerce_appointment_single_check_availability_text', __( 'Check Availability', 'woocommerce-appointments' ), $this ) : apply_filters( 'woocommerce_appointment_single_add_to_cart_text', __( 'Book Now', 'woocommerce-appointments' ), $this );
	}

	/**
	 * Return if appointment has label
	 * @return bool
	 */
	public function has_price_label() {
		$has_price_label = false;

		// Products must exist of course
		if ( $this->get_has_price_label() ) {
			$price_label = $this->get_price_label();
			$has_price_label = $price_label ? $price_label : __( 'Price Varies', 'woocommerce-appointments' );
		}

		return $has_price_label;
	}

	/**
	 * Get product price.
	 *
	 * @param string $context
	 * @param bool   $filters
	 * @return string
	 */
	/*
	public function get_price( $context = 'view' ) {
		$price = parent::get_price( $context );

		if ( ! $price ) {
			$price = parent::get_regular_price( $context );
		}

		return apply_filters( 'woocommerce_appointments_product_get_price', $price, $this );
	}
	*/

	/**
	 * Get price HTML
	 *
	 * @param string $price
	 * @return string
	 */
	public function get_price_html( $deprecated = '' ) {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$sale_price    = $this->get_price_html_from_to( $this->get_display_price( $this->regular_price ), $this->get_display_price( $this->get_base_cost() ) ) . $this->get_price_suffix();
			$regular_price = wc_price( $this->get_display_price( $this->get_base_cost() ) ) . $this->get_price_suffix();
		} else {
			$sale_price    = wc_format_sale_price( wc_get_price_to_display( $this, array(
				'qty'   => 1,
				'price' => $this->get_regular_price(),
			) ), wc_get_price_to_display( $this ) ) . $this->get_price_suffix();
			$regular_price = wc_price( wc_get_price_to_display( $this ) ) . $this->get_price_suffix();
		}

		// Price.
		if ( '' === $this->get_price() ) {
			$price = apply_filters( 'woocommerce_empty_price_html', '<span class="amount">' . __( 'Free!', 'woocommerce-appointments' ) . '</span>', $this );
		} elseif ( $this->is_on_sale() ) {
			$price = $sale_price;
		} else {
			$price = $regular_price;
		}

		// Default price display.
		$price_html = $price;

		// Price with additional cost.
		if ( $this->has_additional_costs() ) {
			/* translators: 1: display price */
			$price_html = sprintf( __( 'From: %s', 'woocommerce-apppointments' ), $price );
		}

		// Price label.
		if ( $this->has_price_label() ) {
			$price_html = $this->has_price_label();
		}

		// Duration.
		if ( in_array( $this->get_duration_unit(), array( 'day', 'minute' ) ) ) {
			$duration_full = wc_appointment_pretty_timestamp( $this->get_duration_in_minutes() );
			$duration_html = ' <small class="duration">' . $duration_full . '</small>';
		} else {
			/* translators: 1: display duration */
			$duration_html = ' <small class="duration">' . sprintf( _n( '%s hour', '%s hours', $this->get_duration(), 'woocommerce-appointments' ), $this->get_duration() ) . '</small>';
		}

		return apply_filters( 'woocommerce_get_price_html', apply_filters( 'woocommerce_return_price_html', $price_html, $this ) . apply_filters( 'woocommerce_return_duration_html', $duration_html, $this ), $this );
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'appointment';
	}

	/**
	 * @since 3.0.0
	 * @return bool
	 */
	public function is_wc_appointment_has_staff() {
		return $this->has_staff();
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters and setters.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get has_additional_costs.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_has_additional_costs( $context = 'view' ) {
		return $this->get_prop( 'has_additional_costs', $context );
	}

	/**
	 * Set has_additional_costs.
	 *
	 * @param boolean $value
	 */
	public function set_has_additional_costs( $value ) {
		$this->set_prop( 'has_additional_costs', wc_appointments_string_to_bool( $value ) );
	}

	/**
	 * Get has_price_label.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_has_price_label( $context = 'view' ) {
		return $this->get_prop( 'has_price_label', $context );
	}

	/**
	 * Set has_price_label.
	 *
	 * @param boolean $value
	 */
	public function set_has_price_label( $value ) {
		$this->set_prop( 'has_price_label', $value );
	}

	/**
	 * Get price_label.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_price_label( $context = 'view' ) {
		return $this->get_prop( 'price_label', $context );
	}

	/**
	 * Set get_price_label.
	 *
	 * @param string $value
	 */
	public function set_price_label( $value ) {
		$this->set_prop( 'price_label', $value );
	}

	/**
	 * Get has_pricing.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_has_pricing( $context = 'view' ) {
		return $this->get_prop( 'has_pricing', $context );
	}

	/**
	 * Set has_pricing.
	 *
	 * @param boolean $value
	 */
	public function set_has_pricing( $value ) {
		$this->set_prop( 'has_pricing', $value );
	}

	/**
	 * Get pricing_rules.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_pricing( $context = 'view' ) {
		return $this->get_prop( 'pricing', $context );
	}

	/**
	 * Set pricing_rules.
	 *
	 * @param array $value
	 */
	public function set_pricing( $value ) {
		$this->set_prop( 'pricing', (array) $value );
	}

	/**
	 * Get the qty available to schedule per slot.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_qty( $context = 'view' ) {
		return $this->get_prop( 'qty', $context );
	}

	/**
	 * Set qty.
	 *
	 * @param integer $value
	 */
	public function set_qty( $value ) {
		$this->set_prop( 'qty', absint( $value ) );
	}

	/**
	 * Get min qty available to schedule per slot.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_qty_min( $context = 'view' ) {
		return $this->get_prop( 'qty_min', $context );
	}

	/**
	 * Set min qty.
	 *
	 * @param integer $value
	 */
	public function set_qty_min( $value ) {
		$this->set_prop( 'qty_min', absint( $value ) );
	}
	/**
	 * Get max qty available to schedule per slot.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_qty_max( $context = 'view' ) {
		return $this->get_prop( 'qty_max', $context );
	}

	/**
	 * Set max qty.
	 *
	 * @param integer $value
	 */
	public function set_qty_max( $value ) {
		$this->set_prop( 'qty_max', absint( $value ) );
	}

	/**
	 * Get duration_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_duration_unit( $context = 'view' ) {
		$value = $this->get_prop( 'duration_unit', $context );

		if ( 'view' === $context ) {
			$value = apply_filters( 'woocommerce_appointments_get_duration_unit', $value, $this );
		}
		return $value;
	}

	/**
	 * Set duration_unit.
	 *
	 * @param string $value
	 */
	public function set_duration_unit( $value ) {
		$this->set_prop( 'duration_unit', (string) $value );
	}

	/**
	 * Get duration.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_duration( $context = 'view' ) {
		return $this->get_prop( 'duration', $context );
	}

	/**
	 * Get duration.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_duration_in_minutes() {
		$duration = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
		$duration = 'day' === $this->get_duration_unit() ? $this->get_duration() * 60 * 24 : $duration;

		return apply_filters( 'woocommerce_appointments_get_duration_in_minutes', $duration, $this );
	}

	/**
	 * Set duration.
	 *
	 * @param integer $value
	 */
	public function set_duration( $value ) {
		$this->set_prop( 'duration', absint( $value ) );
	}

	/**
	 * Get interval_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_interval_unit( $context = 'view' ) {
		$value = $this->get_prop( 'interval_unit', $context );

		if ( 'view' === $context ) {
			$value = apply_filters( 'woocommerce_appointments_get_interval_unit', $value, $this );
		}
		return $value;
	}

	/**
	 * Set interval_unit.
	 *
	 * @param string $value
	 */
	public function set_interval_unit( $value ) {
		$this->set_prop( 'interval_unit', (string) $value );
	}

	/**
	 * Get interval.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_interval( $context = 'view' ) {
		return $this->get_prop( 'interval', $context );
	}

	/**
	 * Set interval.
	 *
	 * @param integer $value
	 */
	public function set_interval( $value ) {
		$this->set_prop( 'interval', absint( $value ) );
	}

	/**
	 * Get padding_duration_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_padding_duration_unit( $context = 'view' ) {
		$value = $this->get_prop( 'padding_duration_unit', $context );

		if ( 'view' === $context ) {
			$value = apply_filters( 'woocommerce_appointments_get_padding_duration_unit', $value, $this );
		}

		return $value;
	}

	/**
	 * Set padding_duration_unit.
	 *
	 * @param string $value
	 */
	public function set_padding_duration_unit( $value ) {
		$this->set_prop( 'padding_duration_unit', (string) $value );
	}

	/**
	 * Get padding_duration.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_padding_duration( $context = 'view' ) {
		return $this->get_prop( 'padding_duration', $context );
	}

	/**
	 * Set padding_duration.
	 *
	 * @param integer $value
	 */
	public function set_padding_duration( $value ) {
		$this->set_prop( 'padding_duration', absint( $value ) );
	}

	/**
	 * Get min_date_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_min_date_unit( $context = 'view' ) {
		return $this->get_prop( 'min_date_unit', $context );
	}

	/**
	 * Set min_date_unit.
	 *
	 * @param string $value
	 */
	public function set_min_date_unit( $value ) {
		$this->set_prop( 'min_date_unit', (string) $value );
	}

	/**
	 * Get min_date.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_min_date( $context = 'view' ) {
		return $this->get_prop( 'min_date', $context );
	}

	/**
	 * Set min_date.
	 *
	 * @param integer $value
	 */
	public function set_min_date( $value ) {
		$this->set_prop( 'min_date', absint( $value ) );
	}

	/**
	 * Get max_date_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_max_date_unit( $context = 'view' ) {
		return $this->get_prop( 'max_date_unit', $context );
	}

	/**
	 * Set max_date_unit.
	 *
	 * @param string $value
	 */
	public function set_max_date_unit( $value ) {
		$this->set_prop( 'max_date_unit', (string) $value );
	}

	/**
	 * Get max_date.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_max_date( $context = 'view' ) {
		return $this->get_prop( 'max_date', $context );
	}

	/**
	 * Set max_date.
	 *
	 * @param integer $value
	 */
	public function set_max_date( $value ) {
		$this->set_prop( 'max_date', absint( $value ) );
	}

	/**
	 * Get user_can_cancel.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_user_can_cancel( $context = 'view' ) {
		return $this->get_prop( 'user_can_cancel', $context );
	}

	/**
	 * Set user_can_cancel.
	 *
	 * @param boolean $value
	 */
	public function set_user_can_cancel( $value ) {
		$this->set_prop( 'user_can_cancel', wc_appointments_string_to_bool( $value ) );
	}

	/**
	 * Get cancel_limit_unit.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_cancel_limit_unit( $context = 'view' ) {
		return $this->get_prop( 'cancel_limit_unit', $context );
	}

	/**
	 * Set cancel_limit_unit.
	 *
	 * @param string $value
	 */
	public function set_cancel_limit_unit( $value ) {
		$value = in_array( $value, array( 'month', 'day', 'hour', 'minute' ) ) ? $value : 'month';
		$this->set_prop( 'cancel_limit_unit', $value );
	}

	/**
	 * Get cancel_limit.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_cancel_limit( $context = 'view' ) {
		return $this->get_prop( 'cancel_limit', $context );
	}

	/**
	 * Set cancel_limit.
	 *
	 * @param integer $value
	 */
	public function set_cancel_limit( $value ) {
		$this->set_prop( 'cancel_limit', max( 1, absint( $value ) ) );
	}

	/**
	 * Get requires_confirmation.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_requires_confirmation( $context = 'view' ) {
		return $this->get_prop( 'requires_confirmation', $context );
	}

	/**
	 * Set requires_confirmation.
	 *
	 * @param boolean $value
	 */
	public function set_requires_confirmation( $value ) {
		$this->set_prop( 'requires_confirmation', wc_appointments_string_to_bool( $value ) );
	}

	/**
	 * Get cal_color.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_cal_color( $context = 'view' ) {
		return $this->get_prop( 'cal_color', $context );
	}

	/**
	 * Set get_cal_color.
	 *
	 * @param string $value
	 */
	public function set_cal_color( $value ) {
		$this->set_prop( 'cal_color', $value );
	}

	/**
	 * Get availability_span.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_availability_span( $context = 'view' ) {
		$value = $this->get_prop( 'availability_span', $context );

		if ( 'view' === $context ) {
			$value = apply_filters( 'woocommerce_appointments_get_availability_span', $value, $this );
		}
		return $value;
	}

	/**
	 * Set availability_span.
	 *
	 * @param string $value
	 */
	public function set_availability_span( $value ) {
		$this->set_prop( 'availability_span', (string) $value );
	}

	/**
	 * Get availability_autoselect.
	 *
	 * @param  string $context
	 * @return boolean
	 */
	public function get_availability_autoselect( $context = 'view' ) {
		return $this->get_prop( 'availability_autoselect', $context );
	}

	/**
	 * Set availability_autoselect.
	 *
	 * @param boolean $value
	 */
	public function set_availability_autoselect( $value ) {
		$this->set_prop( 'availability_autoselect', wc_appointments_string_to_bool( $value ) );
	}

	/**
	 * Get availability.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_availability( $context = 'view' ) {
		return $this->get_prop( 'availability', $context );
	}

	/**
	 * Set availability.
	 *
	 * @param array $value
	 */
	public function set_availability( $value ) {
		$this->set_prop( 'availability', (array) $value );
	}

	/**
	 * Get has_restricted_days.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_has_restricted_days( $context = 'view' ) {
		return $this->get_prop( 'has_restricted_days', $context );
	}

	/**
	 * Set has_restricted_days.
	 *
	 * @param string $value
	 */
	public function set_has_restricted_days( $value ) {
		$this->set_prop( 'has_restricted_days', $value );
	}

	/**
	 * Get restricted_days.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_restricted_days( $context = 'view' ) {
		return $this->get_prop( 'restricted_days', $context );
	}

	/**
	 * Set restricted_days.
	 *
	 * @param string $value
	 */
	public function set_restricted_days( $value ) {
		$this->set_prop( 'restricted_days', $value );
	}

	/**
	 * Get staff_label.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_staff_label( $context = 'view' ) {
		return $this->get_prop( 'staff_label', $context );
	}

	/**
	 * Set staff_label.
	 *
	 * @param string $value
	 */
	public function set_staff_label( $value ) {
		$this->set_prop( 'staff_label', $value );
	}

	/**
	 * Get staff_assignment.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_staff_assignment( $context = 'view' ) {
		return $this->get_prop( 'staff_assignment', $context );
	}

	/**
	 * Set staff_assignment.
	 *
	 * @param string $value
	 */
	public function set_staff_assignment( $value ) {
		$this->set_prop( 'staff_assignment', (string) $value );
	}

	/**
	 * Get staff_ids.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_staff_ids( $context = 'view' ) {
		return $this->get_prop( 'staff_ids', $context );
	}

	/**
	 * Set staff_ids.
	 *
	 * @param array $value
	 */
	public function set_staff_ids( $value ) {
		$this->set_prop( 'staff_ids', wp_parse_id_list( (array) $value ) );
	}

	/**
	 * Get staff_base_costs.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_staff_base_costs( $context = 'view' ) {
		return $this->get_prop( 'staff_base_costs', $context );
	}

	/**
	 * Set staff_base_costs.
	 *
	 * @param array $value
	 */
	public function set_staff_base_costs( $value ) {
		$this->set_prop( 'staff_base_costs', (array) $value );
	}

	/**
	 * Get staff_qtys.
	 *
	 * @param  string $context
	 * @return array
	 */
	public function get_staff_qtys( $context = 'view' ) {
		return $this->get_prop( 'staff_qtys', $context );
	}

	/**
	 * Set staff_qtys.
	 *
	 * @param array $value
	 */
	public function set_staff_qtys( $value ) {
		$this->set_prop( 'staff_qtys', (array) $value );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	|
	| Conditionals functions which return true or false.
	*/

	/**
	 * If this product class is a skeleton/place holder class (used for appointment addons).
	 *
	 * @return boolean
	 */
	public function is_skeleton() {
		return false;
	}

	/**
	 * If this product class is an addon for appointments.
	 *
	 * @return boolean
	 */
	public function is_appointments_addon() {
		return false;
	}

	/**
	 * Extension/plugin/add-on name for the appointment addon this product refers to.
	 *
	 * @return string
	 */
	public function appointments_addon_title() {
		return '';
	}

	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @todo Develop further to embrace WC stock statuses and backorders.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		return true;
		// return apply_filters( 'woocommerce_product_is_in_stock', 'instock' === $this->get_stock_status(), $this );
	}

	/**
	 * Appointments can always be purchased regardless of price.
	 *
	 * @return boolean
	 */
	public function is_purchasable() {
		$status = is_callable( array( $this, 'get_status' ) ) ? $this->get_status() : $this->post->post_status;
		return apply_filters( 'woocommerce_is_purchasable', $this->exists() && ( 'publish' === $status || current_user_can( 'edit_post', $this->get_id() ) ), $this );
	}

	/**
	 * Test duration type.
	 *
	 * @param string $type
	 * @return boolean
	 */
	public function is_duration_type( $type ) {
		return $this->get_duration_type() === $type;
	}

	/**
	 * The base cost will either be the 'base' cost or the base cost + cheapest staff
	 * @return string
	 */
	public function get_base_cost() {
		$base = $this->get_price();

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
	 * Return if appointment has extra costs.
	 *
	 * @return bool
	 */
	public function has_additional_costs() {
		if ( $this->get_has_additional_costs() ) {
			return true;
		}

		if ( $this->has_staff() ) {
			foreach ( (array) $this->get_staff() as $staff_member ) {
				if ( $staff_member->get_base_cost() ) {
					return true;
				}
			}
		}

		$costs = $this->get_costs();
		if ( ! empty( $costs ) && $this->get_has_pricing() ) {
			return true;
		}

		return false;
	}

	/**
	 * How staff are assigned.
	 *
	 * @param string $type
	 * @return boolean customer or automatic
	 */
	public function is_staff_assignment_type( $type ) {
		return $this->get_staff_assignment() === $type;
	}

	/**
	 * Checks if a product requires confirmation.
	 *
	 * @return bool
	 */
	public function requires_confirmation() {
		return apply_filters( 'woocommerce_appointment_requires_confirmation', $this->get_requires_confirmation(), $this );
	}

	/**
	 * See if the appointment can be cancelled.
	 *
	 * @return boolean
	 */
	public function can_be_cancelled() {
		return apply_filters( 'woocommerce_appointment_user_can_cancel', $this->get_user_can_cancel(), $this );
	}

	/**
	 * See if dates are by default appointable.
	 *
	 * @return bool
	 */
	public function get_default_availability() {
		return apply_filters( 'woocommerce_appointment_default_availability', false, $this );
	}

	/**
	 * See if this appointment product has restricted days.
	 *
	 * @return boolean
	 */
	public function has_restricted_days() {
		return $this->get_has_restricted_days();
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD getters
	|--------------------------------------------------------------------------
	*/
	/**
	 * Gets all formatted cost rules.
	 *
	 * @return array
	 */
	public function get_costs() {
		return WC_Product_Appointment_Rule_Manager::process_pricing_rules( $this->get_pricing() );
	}

	/**
	 * Get Min date.
	 *
	 * @return array|bool
	 */
	public function get_min_date_a() {
		$min_date['value'] = apply_filters( 'woocommerce_appointments_min_date', $this->get_min_date(), $this->get_id() );
		$min_date['unit']  = $this->get_min_date_unit() ? apply_filters( 'woocommerce_appointments_min_date_unit', $this->get_min_date_unit(), $this->get_id() ) : 'month';
		return $min_date;
	}

	/**
	 * Get max date.
	 *
	 * @return array
	 */
	public function get_max_date_a() {
		$max_date['value'] = $this->get_max_date() ? apply_filters( 'woocommerce_appointments_max_date', $this->get_max_date(), $this->get_id() ) : 1;
		$max_date['unit']  = $this->get_max_date_unit() ? apply_filters( 'woocommerce_appointments_max_date_unit', $this->get_max_date_unit(), $this->get_id() ) : 'month';
		return $max_date;
	}

	/**
	 * Get max year.
	 *
	 * @return int
	 */
	private function get_max_year() {
		$max_date           = $this->get_max_date_a();
		$max_date_timestamp = strtotime( "+{$max_date['value']} {$max_date['unit']}" );
		$max_year           = date( 'Y', $max_date_timestamp );
		if ( ! $max_year ) {
			$max_year = date( 'Y' );
		}
		return $max_year;
	}

	/**
	 * Get the Product padding period setting.
	 *
	 * @since 2.6.5 introduced.
	 * @return mixed $padding_duration
	 */
	public function get_padding_duration_minutes() {
		$padding_duration = $this->get_padding_duration();

		// If exists always treat appointment_period in minutes.
		if ( ! empty( $padding_duration ) && 'hour' === $this->get_duration_unit() ) {
			$padding_duration = $padding_duration * 60;
		}

		return $padding_duration;
	}

	/**
	 * Get default intervals.
	 *
	 * @since 3.2.0 introduced.
	 * @param  int $id
	 * @return Array
	 */
	public function get_intervals() {
		$default_interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
		$custom_interval = 'hour' === $this->get_duration_unit() ? $this->get_duration() * 60 : $this->get_duration();
		if ( $this->get_interval_unit() && $this->get_interval() ) {
			$custom_interval = 'hour' === $this->get_interval_unit() ? $this->get_interval() * 60 : $this->get_interval();
		}

		// Filters for the intervals.
		$default_interval = apply_filters( 'woocommerce_appointments_interval', $default_interval, $this );
		$custom_interval = apply_filters( 'woocommerce_appointments_base_interval', $custom_interval, $this );

		$intervals        = array( $default_interval, $custom_interval );

		return $intervals;
	}

	/**
	 * See if this appointment product has any staff.
	 * @return boolean
	 */
	public function has_staff() {
		$count_staff = count( $this->get_staff_ids() );
		return $count_staff ? $count_staff : false;
	}

	/**
	 * Get staff by ID.
	 *
	 * @param  int $id
	 * @return WC_Product_Appointment_Staff object
	 */
	public function get_staff() {
		$product_staff = array();

		foreach ( $this->get_staff_ids() as $staff_id ) {
			$product_staff[] = new WC_Product_Appointment_Staff( $staff_id, $this->get_id() );
		}

		return $product_staff;
	}

	/**
	 * Get staff member by ID
	 *
	 * @param  int $id
	 * @return WC_Product_Appointment_Staff object
	 */
	public function get_staff_member( $staff_id ) {
		if ( $this->has_staff() && ! empty( $staff_id ) ) {
			$staff_member = new WC_Product_Appointment_Staff( $staff_id, $this->get_id() );

			return $staff_member;
		}

		return false;
	}

	/**
	 * Get staff members by IDs
	 *
	 * @param  int $id
	 * @param  bool $names
	 * @param  bool $with_link
	 * @return WC_Product_Appointment_Staff object
	 */
	public function get_staff_members( $ids = array(), $names = false, $with_link = false ) {
		// If no IDs are give, get all product staff IDs.
		if ( ! $ids ) {
			$ids = $this->get_staff_ids();
		}

		if ( ! $ids ) {
			return false;
		}

		return wc_appointments_get_staff_from_ids( $ids, $names, $with_link );
	}

	/**
	 * Get available quantity.
	 *
	 * @since 3.2.0 introduced.
	 * @param $staff_id
	 * @return bool|int
	 */
	public function get_available_qty( $staff_id = '' ) {
		if ( $this->has_staff() ) {
			$qtys = $this->get_staff_qtys();
			$staff_qty = 0;
			$staff_qtys = array();

			if ( $staff_id && is_array( $staff_id ) ) {
				foreach ( (array) $staff_id as $staff_member_id ) {
					$qty  = isset( $qtys[ $staff_member_id ] ) && '' !== $qtys[ $staff_member_id ] ? $qtys[ $staff_member_id ] : $this->get_qty();
					$staff_qtys[] = $qty;
				}
				// Only count when $qtys is an array.
				if ( is_array( $staff_qtys ) && ! empty( $staff_qtys ) ) {
					$staff_qty = $this->is_staff_assignment_type( 'all' ) ? max( $staff_qtys ) : array_sum( $staff_qtys );
				}
			} elseif ( $staff_id && is_numeric( $staff_id ) ) {
				$staff_qty = isset( $qtys[ $staff_id ] ) && '' !== $qtys[ $staff_id ] ? $qtys[ $staff_id ] : $this->get_qty();
			} elseif ( ! $staff_id ) {
				foreach ( $this->get_staff_ids() as $staff_member_id ) {
					$staff_qtys[] = isset( $qtys[ $staff_member_id ] ) && '' !== $qtys[ $staff_member_id ] ? $qtys[ $staff_member_id ] : $this->get_qty();
				}
				// Only count when $qtys is an array.
				if ( is_array( $staff_qtys ) && ! empty( $staff_qtys ) ) {
					$staff_qty = $this->is_staff_assignment_type( 'all' ) ? max( $staff_qtys ) : array_sum( $staff_qtys );
				}
			}

			return $staff_qty ? $staff_qty : $this->get_qty();
		}

		return $this->get_qty();
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
		// Default to zero, when no staff is set.
		if ( empty( $for_staff ) ) {
			$for_staff = 0;
		}

		// Repeat the function if staff IDs are in array.
		if ( is_array( $for_staff ) ) {
			foreach ( $for_staff as $for_staff_id ) {
				return $this->get_availability_rules( $for_staff_id );
			}
		}

		if ( empty( $this->availability_rules[ $for_staff ] ) ) {
			$this->availability_rules[ $for_staff ] = array();

			// Rule types
			$staff_rules    = array();
			$product_rules  = $this->get_availability();
			$global_rules   = get_option( 'wc_global_appointment_availability', array() );

			// Get availability of each staff - no staff has been chosen yet.
			if ( $this->has_staff() && ! $for_staff ) {
				$staff_rules = array();

				// All slots are available.
				if ( $this->get_default_availability() ) {
					# If all slotss are available by default, we should not hide days if we don't know which staff is going to be used.
				} else {
					// Add staff ID to each rule.
					foreach ( $this->get_staff() as $staff_member ) {
						$temp_staff_rules = $staff_member->get_availability();
						$all_staff_rules = array();
						// Add staff ID to each rule
						if ( ! empty( $temp_staff_rules ) ) {
							foreach ( $temp_staff_rules as $index => $rule ) {
								$all_staff_rules[ $index ]             = $rule;
								$all_staff_rules[ $index ]['staff_id'] = $staff_member->get_id();
							}
						}
						$staff_rules = array_merge( $staff_rules, $all_staff_rules );
					}

					#var_dump($staff_rules);
					#$staff_rules = WC_Product_Appointment_Rule_Manager::process_overlapping_staff_rules( $staff_rules, $rule_appointable = 'no' );
				}
			} elseif ( $for_staff ) {
				// Standard handling.
				$staff_object = $this->get_staff_member( $for_staff );
				$temp_staff_rules = $staff_object ? $staff_object->get_availability() : array();
				// Add staff ID to each rule
				if ( ! empty( $temp_staff_rules ) ) {
					foreach ( $temp_staff_rules as $index => $rule ) {
						$staff_rules[ $index ]             = $rule;
						$staff_rules[ $index ]['staff_id'] = $for_staff;
					}
				}
			}

			// The order that these rules are put into the array are important due to the way that
			// the rules as processed for overrides.
			$availability_rules = array_filter(
				array_merge(
					WC_Product_Appointment_Rule_Manager::process_availability_rules( array_reverse( $global_rules ), 'global' ),
					WC_Product_Appointment_Rule_Manager::process_availability_rules( array_reverse( $product_rules ), 'product' ),
					WC_Product_Appointment_Rule_Manager::process_availability_rules( array_reverse( $staff_rules ), 'staff' )
				)
			);

			usort( $availability_rules, array( $this, 'rule_override_power_sort' ) );

			$this->availability_rules[ $for_staff ] = $availability_rules;
		}

		return apply_filters( 'woocommerce_appointment_get_availability_rules', $this->availability_rules[ $for_staff ], $for_staff, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Slot calculation functions. @todo move to own manager class
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check the staff availability against all the slots.
	 *
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  int    $qty
	 * @param  WC_Product_Appointment_Staff|null $appointment_staff
	 * @return string|WP_Error
	 */
	public function get_slots_availability( $start_date, $end_date, $qty, $staff_id ) {
		$slots    = $this->get_slots_in_range( $start_date, $end_date, '', $staff_id );
		$interval = $this->get_duration_in_minutes();

		if ( empty( $slots ) || ! in_array( $start_date, $slots ) ) {
			return false;
		}

		// Current product padding.
		$padding_duration = 'hour' === $this->get_padding_duration_unit() ? $this->get_padding_duration() * 60 : $this->get_padding_duration();

		// Slot end time with current product padding added.
		if ( ! empty( $padding_duration ) && in_array( $this->get_padding_duration_unit(), array( 'minute', 'hour' ) ) ) { #with padding
			$end_date = strtotime( "+{$padding_duration} minutes", $end_date );
		}

		$product_staff = $this->has_staff() && ! $staff_id ? $this->get_staff_ids() : $staff_id;

		#var_dump( date( 'Ymd H:i', $start_date ) . ' ======= ' . date( 'Ymd H:i', $end_date ) . '<br/>' );
		#var_dump( $slots );
		#var_dump( $staff_id );
		#var_dump( '<br/>' );

		/**
		 * Grab all existing appointments for the date range
		 * @var Array mixed with Object
		 */
		$existing_appointments_merged = $this->get_appointments_in_date_range(
			$start_date,
			$end_date,
			$product_staff
		);

		// Remove duplicates in existing appointments, generated with merging.
		$existing_appointments = array();
		foreach ( $existing_appointments_merged as $existing_appointment_merged ) {
			$existing_appointments[ $existing_appointment_merged->get_id() ] = $existing_appointment_merged;
		}

		$slots = array_unique( array_merge( array_map( function( $appointment ) {
			return $appointment->get_start();
		}, $existing_appointments ), $slots ) );

		$available_qtys        = array();

		#print '<pre>'; print_r( $slots ); print '</pre>';

		// Check all slots availability.
		foreach ( $slots as $slot ) {
			$qty_scheduled_in_slot = 0;

			// Check capacity based on duration unit.
			if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
				$slot_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $this, $slot, $slot + 1, $staff_id, true );
			} else {
				$slot_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $this, $slot, $staff_id, true );
			}

			#var_dump( date( 'G:i', $slot ) . '___' .'_qty:'. $slot_qty .'__qty_sch:'. $qty_scheduled_in_slot );
			#var_dump( $existing_appointments );

			if ( ! empty ( $existing_appointments ) ) {
				foreach ( $existing_appointments as $existing_appointment ) {
					if ( ! $existing_appointment->is_within_slot( $slot, strtotime( "+{$interval} minutes", $slot ) ) ) {
						continue;
					}
					if ( ! is_a( $existing_appointment, 'WC_Appointment' ) ) {
						continue;
					}

					$appointment_product_id = $existing_appointment->get_product_id();
					$appointment_staff_ids  = $existing_appointment->get_staff_ids();
					$appointment_product    = wc_get_product( $appointment_product_id );

					// Padding.
					$padding_duration_length = $appointment_product ? $appointment_product->get_padding_duration() : 0;
					$padding_duration_unit = $appointment_product ? $appointment_product->get_padding_duration_unit() : 0;
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
						// When existing appointment is scheduled with another product,
						// remove all available capacity, so staff becomes unavailable for this product.
						// Used for google calendar events in most cases.
						if ( $appointment_product_id !== $this->get_id() && apply_filters( 'wc_apointments_check_appointment_product', true, $appointment_product_id ) ) {
							$qty_to_add = $this->get_available_qty( $staff_id );
						// Only remove capacity scheduled for existing product.
						} else {
							$qty_to_add = $existing_appointment->get_qty() ? $existing_appointment->get_qty() : 1;
						}

						$qty_scheduled_in_slot += $qty_to_add;

						// Staff doesn't match, so don't check.
						if ( $staff_id
							 && ! is_array ( $staff_id )
						     && $appointment_staff_ids
							 && is_array( $appointment_staff_ids )
							 && ! in_array( $staff_id, $appointment_staff_ids ) ) {

							$qty_scheduled_in_slot -= $qty_to_add;

						} elseif ( $staff_id
							 && is_array ( $staff_id )
						     && $appointment_staff_ids
							 && is_array( $appointment_staff_ids )
							 && ! array_intersect( $staff_id, $appointment_staff_ids ) ) {

							$qty_scheduled_in_slot -= $qty_to_add;

						}
					}
				}
			}

			// Calculate availably capacity.
			$available_qty = max( $slot_qty - $qty_scheduled_in_slot, 0 );

			#var_dump( date( 'ymd H:i', $slot ) . '____' . $available_qty .' = '. $slot_qty .' - '. $qty_scheduled_in_slot . ' < ' . $qty . ' staff=' . $staff_id . '<br/>' );
			#var_dump( 'break' );

			// Remaining places are less than requested qty, return an error.
			if ( $available_qty < $qty ) {
				if ( in_array( $this->get_duration_unit(), array( 'hour', 'minute' ) ) ) {
					return new WP_Error( 'Error', sprintf(
						/* translators: 1: available quantity 2: appointment slot date 3: appointment slot time */
						_n( 'There is a maximum of %1$d place remaining on %2$s at %3$s.', 'There are a maximum of %1$d places remaining on %2$s at %3$s.', $available_qty, 'woocommerce-appointments' ),
						max( $available_qty, 0 ),
						date_i18n( wc_date_format(), $slot ),
						date_i18n( wc_time_format(), $start_date )
					) );
				} elseif ( ! $available_qtys ) {
					return new WP_Error( 'Error', sprintf(
						/* translators: 1: available quantity 2: appointment slot date */
						_n( 'There is a maximum of %1$d place remaining on %2$s', 'There are a maximum of %1$d places remaining on %2$s', $available_qty , 'woocommerce-appointments' ),
						$available_qty,
						date_i18n( wc_date_format(), $slot )
					) );
				} else {
					return new WP_Error( 'Error', sprintf(
						/* translators: 1: available quantity 2: appointment slot date */
						_n( 'There is a maximum of %1$d place remaining on %2$s', 'There are a maximum of %1$d places remaining on %2$s', $available_qty , 'woocommerce-appointments' ),
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
		$intervals = empty( $intervals ) ? $this->get_intervals() : $intervals;

		#var_dump( date( 'Y-n-j H:i', $start_date ) .'___'. date( 'Y-n-j H:i', $end_date ) );
		#var_dump( '<br/>' );
		#var_dump( $staff_id );
		#var_dump( '<br/>' );

		if ( 'day' === $this->get_duration_unit() ) {
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
			if ( WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $this, $check_date, $staff_id ) ) {
				$available_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $this, $check_date, $staff_id, true );
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
		$interval						= $intervals[0]; #duration
		$check_date						= $start_date;
		$default_appointable_minutes	= $this->get_default_availability() ? range( 0, ( 1440 + $interval ) ) : array();
		$rules							= $this->get_availability_rules( $staff_id ); // Work out what minutes are actually appointable on this day

		// Get available slot start times.
		$minutes_not_available    		= $this->get_unavailable_minutes( $scheduled ); // Get unavailable slot start times.

		#var_dump( date( 'Y-n-j H:i', $check_date ) .'___'. date( 'Y-n-j H:i', $end_date ) . '<br/>' );

		// Looping day by day look for available slots.
		while ( $check_date <= $end_date ) {
			#print '<pre>'; print_r( $check_date ); print '</pre>';
			$appointable_minutes_for_date = array_merge( $default_appointable_minutes, WC_Product_Appointment_Rule_Manager::get_minutes_from_rules( $rules, $check_date ) );
			if ( ! $this->get_default_availability() ) {
				$appointable_minutes_for_date  = $this->apply_first_slot_time( $appointable_minutes_for_date, 0 );
			}
			#print '<pre>'; print_r( $appointable_minutes_for_date ); print '</pre>';
			$appointable_start_and_end    = $this->get_appointable_minute_start_and_end( $appointable_minutes_for_date );
			#print '<pre>'; print_r( $appointable_start_and_end ); print '</pre>';
			$slots                   	  = $this->get_appointable_minute_slots_for_date( $check_date, $start_date, $end_date, $appointable_start_and_end, $intervals, $staff_id, $minutes_not_available );
			#print '<pre>'; print_r( $slots ); print '</pre>';
			$slot_start_times_in_range	  = array_merge( $slots, $slot_start_times_in_range );
			#print '<pre>'; print_r( $slot_start_times_in_range ); print '</pre>';

			$check_date = strtotime( '+1 day', $check_date ); // Move to the next day
		}

		return $slot_start_times_in_range;
	}

	/**
	 * From an array of minutes for a day remove all minutes before first slot time.
	 * @since 3.3.0
	 *
	 * @param array $appointable_minutes
	 * @param int $first_slot_minutes
	 *
	 * @return array $minutes
	 */
	public function apply_first_slot_time( $appointable_minutes, $first_slot_minutes ) {
		$minutes = array();
		foreach ( $appointable_minutes as $minute ) {
			if ( $first_slot_minutes <= $minute ) {
				$minutes[] = $minute;
			}
		}
		return $minutes;
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
					$appointable_minute_slots[]   = array( $appointable_minute_slot_from, $minute + 1 );
					$appointable_minute_slot_from = $appointable_minutes[ $key + 1 ];
				}
			} else {
				// We're at the end of the appointable minutes
				$appointable_minute_slots[] = array( $appointable_minute_slot_from, $minute + 1 );
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

		// boring interval stuff
		$interval              = $intervals[0]; #duration
		$base_interval         = $intervals[1]; #interval

		// get a time stamp to check from and get a time stamp to check to
		$product_min_date = $this->get_min_date_a();
		$product_max_date = $this->get_max_date_a();

		// Adding 1 hour to round up to the next whole hour to return what is expected.
		/*
		if ( 'hour' === $product_min_date['unit'] ) {
			$product_min_date['value'] = (int) $product_min_date['value'] + 1;
		}
		*/

		$min_check_from   = strtotime( "+{$product_min_date['value']} {$product_min_date['unit']}", current_time( 'timestamp' ) );
		if ( 'hour' === $this->get_duration_unit() ) {
			$min_check_from = strtotime( '+1 hour', $min_check_from );
		}
		$max_check_to     = strtotime( "+{$product_max_date['value']} {$product_max_date['unit']}", current_time( 'timestamp' ) );
		$min_date         = $this->get_min_timestamp_for_date( $start_date, $product_min_date['value'], $product_min_date['unit'] );

		#print '<pre>'; print_r( date( 'Y-m-d H:i', $min_check_from ) ); print '</pre>';
		#print '<pre>'; print_r( date( 'Y-m-d H:i', $max_check_to ) ); print '</pre>';

		$current_time_stamp = current_time( 'timestamp' );

		// if we have a padding, we will shift all times accordingly by changing the from_interval
		// e.g. 60 min paddingpadding shifts [ 480, 600, 720 ] into [ 480, 660, 840 ]
		$padding = $this->get_padding_duration_minutes() ? $this->get_padding_duration_minutes() : 0;

		#print '<pre>'; print_r( $check_date ); print '</pre>';
		#print '<pre>'; print_r( $appointable_start_and_end ); print '</pre>';

		// Loop the slots of appointable minutes and add a slot if there is enough room to book
		foreach ( $appointable_start_and_end as $time_slot ) {
			$range_start = $time_slot[0];
			$range_end   = $time_slot[1];
			/*
			if ( 'hour' === $this->get_duration_unit() ) {
				// Adding 1 minute to round up to a full hour.
				$range_end  += 1;
			}
			*/

			/*
			$time_slot_start        = strtotime( "midnight +{$range_start} minutes", $check_date );
			$minutes_in_slot        = $range_end - $range_start;
			$base_intervals_in_slot = floor( $minutes_in_slot / $base_interval );
			$time_slot_end_time 	= strtotime( "midnight +{$range_end} minutes", $check_date );
			*/

			$range_start_time        = strtotime( "midnight +{$range_start} minutes", $check_date );
			$range_end_time          = strtotime( "midnight +{$range_end} minutes", $check_date );
			$minutes_for_range       = $range_end - $range_start;
			$base_intervals_in_slot  = floor( $minutes_for_range / $base_interval );

			// Only need to check first hour.
			if ( 'start' === $this->get_availability_span() ) {
				$base_interval = 1; #test
				$base_intervals_in_slot = 1; #test
			}

			for ( $i = 0; $i < $base_intervals_in_slot; $i++ ) {
				#$from_interval = $i * ( $base_interval + $padding );
				$from_interval = $i * $base_interval;
				$to_interval   = $from_interval + $interval;
				$start_time    = strtotime( "+{$from_interval} minutes", $range_start_time );
				$end_time      = strtotime( "+{$to_interval} minutes", $range_start_time );

				#print '<pre>'; print_r( '$stime: ' . date('Y-n-j H:i', $range_start_time) ); print '</pre>';
				#print '<pre>'; print_r( '$etime: ' . date('Y-n-j H:i', $end_time) ); print '</pre>';

				// Remove 00:00 or 24:00 for same day slot.
				if ( strtotime( 'midnight +1 day', $start_date ) === $start_time ) {
					continue;
				}

				// Available quantity.
				$available_qty = WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $this, $start_time, $end_time, $staff_id, 1 );
				#$available_qty = $this->get_available_qty( $staff_id ); // exact quantity is checked in get_available_slots_html() function
				#print '<pre>'; print_r( date('Y-n-j H:i', $check_date) . '......' . date( 'H:i', $start_time ) . '___' . $available_qty . '___' . $staff_id ); print '</pre>';

				// Staff must be available or skip if no staff and no availability.
				if ( ( ! $available_qty && $staff_id ) || ( ! $this->has_staff() && ! $available_qty ) ) {
					continue;
				}

				// Break if start time is after the end date being calculated.
				if ( $start_time > $end_date && ( 'start' !== $this->get_availability_span() )  ) {
					break 2;
				}

				#print '<pre>'; print_r( date( 'Y-m-d H:i', $start_time ) .' < '. date( 'Y-m-d H:i', $min_check_from ) ); print '</pre>';

				// Must be in the future.
				if ( $start_time < $min_date || $start_time <= $current_time_stamp ) {
					continue;
				}

				// Check capacity settings.
				#if ( isset( $minutes_not_available[ $start_time ] ) ) {
					#var_dump( $minutes_not_available[ $start_time ] .' _>=_ '. $available_qty );
				#}
				// Skip if minutes not available.
				if ( isset( $minutes_not_available[ $start_time ] )
				     && $minutes_not_available[ $start_time ] >= $available_qty ) {
					continue;
				}

				// Make sure minute & hour slots are not past minimum & max appointment settings.
				if ( $start_time < $min_check_from || $end_time < $min_check_from || $start_time > $max_check_to ) {
					continue;
				}

				/*
				if ( $end_time > $range_end_time ) {
					#continue;
				}
				*/

				// make sure slot doesn't start after the end date.
				if ( $start_time > $end_date ) {
					continue;
				}

				/*
				// Skip if end time bigger than slot end time.
				// thrown out as it prevented 24/7 businesses last slot buildup
				if ( $end_time > $time_slot_end_time && ( 'start' !== $this->get_availability_span() ) ) {
					continue;
				}
				*/

				if ( $this->are_all_minutes_in_slot_available( $start_time, $end_time, $available_qty, $minutes_not_available ) && ! in_array( $start_time, $slots ) ) {
					$slots[] = $start_time;
				}
			}
		}

		#var_dump( $slots );

		return $slots;
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
	 * @param  array   $slots      The slots we'll be checking availability for.
	 * @param  array   $intervals   Array containing 2 items; the interval of the slot (maybe user set), and the base interval for the slot/product.
	 * @param  integer $staff_id Staff we're getting slots for. Falls backs to product as a whole if 0.
	 * @param  integer $from        The starting date for the set of slots
	 * @param  integer $to          Ending date for the set of slots
	 * @return array The available slots array
	 */
	 public function get_available_slots( $slots, $intervals = array(), $staff_id = 0, $from = '', $to = '' ) {
 		$intervals = empty( $intervals ) ? $this->get_intervals() : $intervals;

 		list( $interval, $base_interval ) = $intervals;

 		$available_slots   = array();

		$start_date = $from;
		if ( empty( $start_date ) ) {
			$start_date = reset( $slots );
		}

		$end_date = $to;
		if ( empty( $end_date ) ) {
			$end_date = absint( end( $slots ) );
		}

 		if ( ! empty( $slots ) ) {

			$product_staff = $this->has_staff() && ! $staff_id ? $this->get_staff_ids() : $staff_id;

			/**
 			 * Grab all existing appointments for the date range
 			 * @var array
 			 */
 			$existing_appointments_merged = $this->get_appointments_in_date_range(
 				$start_date,
 				$end_date + ( $interval * 60 ),
 				$product_staff
 			);

 			// Remove duplicates in existing appointments, generated with merging.
 			$existing_appointments = array();
 			foreach ( $existing_appointments_merged as $existing_appointment_merged ) {
 				$existing_appointments[ $existing_appointment_merged->get_id() ] = $existing_appointment_merged;
 			}

 			// Staff scheduled array. Staff can be a "staff" but also just an appointment if it has no staff.
 			$staff_scheduled = array( 0 => array() );
 			$product_scheduled = array( 0 => array() );

 			// Loop all existing appointments
 			foreach ( $existing_appointments as $existing_appointment ) {
				if ( ! is_a( $existing_appointment, 'WC_Appointment' ) ) {
					continue;
				}

 				$appointment_staff_ids = $existing_appointment->get_staff_ids();
 				$appointment_product_id = $existing_appointment->get_product_id();

				// Staff doesn't match, so don't check.
				if ( $staff_id
					 && ! is_array ( $staff_id )
				     && $appointment_staff_ids
					 && is_array( $appointment_staff_ids )
					 && ! in_array( $staff_id, $appointment_staff_ids ) ) {

					continue;

				} elseif ( $staff_id
					 && is_array ( $staff_id )
				     && $appointment_staff_ids
					 && is_array( $appointment_staff_ids )
					 && ! array_intersect( $staff_id, $appointment_staff_ids ) ) {

					continue;

				}

 				// Google Calendar sync.
 				if ( wc_appointments_gcal_synced_product_id() === absint( $appointment_product_id ) ) {
 					#$alt_appointment_product_id = 0; # Make product ID zero, so it adds scheduled dates/times to current product.
 					$alt_appointment_product_id = $this->get_id(); #Make product IDs same, so it adds scheduled dates/times to current product.
					$appointment_staff_ids = $this->get_staff_ids(); #Make all product staff unavailable at this time.
 				} else {
 					$alt_appointment_product_id = $appointment_product_id;
 				}

				$alt_appointment_product = wc_get_product( $alt_appointment_product_id );

				// Padding.
				$padding_duration_length = $alt_appointment_product ? $alt_appointment_product->get_padding_duration() : 0;
				$padding_duration_unit = $alt_appointment_product ? $alt_appointment_product->get_padding_duration_unit() : 0;
				$padding_duration_length_min = 'hour' === $padding_duration_unit ? $padding_duration_length * 60 : $padding_duration_length;

				// Duration unit.
				$appointment_duration_unit = $alt_appointment_product ? $alt_appointment_product->get_duration_unit() : 0;

 				// Prepare staff and product array.
 				foreach ( (array) $appointment_staff_ids as $appointment_staff_id ) {
 					$staff_scheduled[ $appointment_staff_id ] = isset( $staff_scheduled[ $appointment_staff_id ] ) ? $staff_scheduled[ $appointment_staff_id ] : array();
 				}
 				$product_scheduled[ $alt_appointment_product_id ] = isset( $product_scheduled[ $alt_appointment_product_id ] ) ? $product_scheduled[ $alt_appointment_product_id ] : array();

 				// Slot start/end time.
 				if ( ! empty( $padding_duration_length ) && in_array( $appointment_duration_unit, array( 'minute', 'hour' ) ) ) { #with padding
 					$start_time = strtotime( "-{$padding_duration_length_min} minutes", $existing_appointment->get_start() );
 					$end_time = strtotime( "+{$padding_duration_length_min} minutes", $existing_appointment->get_end() );
 				} else { #without padding
 					$start_time = $existing_appointment->get_start();
 					$end_time = $existing_appointment->get_end();
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
 				if ( $appointment_product_id !== $this->get_id() && apply_filters( 'wc_apointments_check_appointment_product', true, $appointment_product_id ) ) {
					$repeat = max( 1, $this->get_available_qty( $staff_id ) );
 				// Only remove capacity scheduled for existing product.
 				} else {
 					$repeat = max( 1, $existing_appointment->get_qty() );
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
 			$available_times = $this->get_slots_in_range( $start_date, $end_date, array( $interval, $base_interval ), $staff_id, isset( $product_scheduled[ $this->get_id() ] ) ? $product_scheduled[ $this->get_id() ] : $product_scheduled[0] );

			/*
 			// Test
 			$test = array();
 			foreach ( $available_times as $available_time ) {
 				$test[] = date( 'y-m-d H:i', $available_time );
 			}
 			print '<pre>'; print_r( $test ); print '</pre>';
			*/

 			// No preference.
 			if ( $this->has_staff() && ! $staff_id ) {

				// Loop through product staff.
				if ( ! empty( $staff_scheduled ) ) {
					foreach ( $staff_scheduled as $staff_scheduled_id => $staff_scheduled_times ) {
	 					if ( ! empty( $staff_scheduled_times ) && in_array( $staff_scheduled_id, $this->get_staff_ids() ) ) {
							foreach ( $staff_scheduled_times as $staff_scheduled_time ) {
								$staff_scheduled_found[] = $staff_scheduled_time;
							}
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
 				$times = $this->get_slots_in_range( $start_date, $end_date, array( $interval, $base_interval ), 0, $staff_scheduled );
				$available_times = array_merge( $available_times, $times ); #add times from staff to times from product

				/*
	 			// Test
	 			$test = array();
	 			foreach ( $available_times as $available_time ) {
	 				$test[] = date( 'y-m-d H:i', $available_time );
	 			}
	 			var_dump( $test );
	 			*/

 			// Available times for specific staff IDs.
 			} elseif ( $this->has_staff() && $staff_id ) {

				// Loop through all staff in array.
				if ( ! empty( $staff_scheduled ) && is_array( $staff_id ) ) {
					foreach ( $staff_scheduled as $staff_scheduled_id => $staff_scheduled_times ) {
						if ( ! empty( $staff_scheduled_times ) && in_array( $staff_scheduled_id, $staff_id ) ) {
							foreach ( $staff_scheduled_times as $staff_scheduled_time ) {
								$staff_scheduled_found[] = $staff_scheduled_time;
							}
						}
					}
				// Single staff.
				} elseif ( isset( $staff_scheduled[ $staff_id ] ) && ! empty( $staff_scheduled[ $staff_id ] ) ) {
					foreach ( $staff_scheduled[ $staff_id ] as $staff_scheduled_time ) {
						$staff_scheduled_found[] = $staff_scheduled_time;
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
		/*
 		if ( in_array( $this->get_duration_unit(), array( 'minute', 'hour' ) ) && ! empty( $from ) ) {
 			$time_slots = array();

 			foreach ( $available_slots as $key => $slot_date ) {
 				if ( date( 'ymd', $slot_date ) == date( 'ymd', $from ) ) {
 					$time_slots[] = $slot_date;
 				}
 			}

 			$available_slots = $time_slots;
 		}
		*/

		sort( $available_slots );

		/**
		 * Filter the available slots for a product within a given range
		 *
		 * @since 1.9.8 introduced
		 *
		 * @param array $available_slots
		 * @param WC_Product $appointments_product
		 * @param array $raw_range passed into this function.
		 * @param array $intervals
		 * @param integer $staff_id
		 */
		return apply_filters( 'wc_appointments_product_get_available_slots', array_unique( $available_slots ), $this, $slots, $intervals, $staff_id );
 	}

	/**
	 * Get the availability of all staff
	 *
	 * @param string $start_date
	 * @param string $end_date
	 * @param integer $qty
	 * @return array| WP_Error
	 */
	public function get_all_staff_availability( $start_date, $end_date, $qty ) {
		$staff           = $this->get_staff();
		$available_staff = array();

		foreach ( $staff as $staff_member ) {
			$availability = wc_appointments_get_total_available_appointments_for_range( $this, $start_date, $end_date, $staff_member->get_id(), $qty );

			if ( $availability && ! is_wp_error( $availability ) ) {
				$available_staff[ $staff_member->get_id() ] = $availability;
			}
		}

		if ( empty( $available_staff ) ) {
			return new WP_Error( 'Error', __( 'This slot cannot be scheduled.', 'woocommerce-appointments' ) );
		}

		return $available_staff;
	}


	/*
	|--------------------------------------------------------------------------
	| Deprecated Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the minutes that should be available based on the rules and the date to check.
	 *
	 * The minutes are returned in a range from the start incrementing minutes right up to the last available minute.
	 *
	 * @deprecated since 2.6.5
	 * @param array $rules
	 * @param int $check_date
	 * @return array $appointable_minutes
	 */
	public function get_minutes_from_rules( $rules, $check_date ) {
		return WC_Product_Appointment_Rule_Manager::get_minutes_from_rules( $rules, $check_date );
	}

	/**
	 * Find the minimum slot's timestamp based on settings.
	 *
	 * @deprecated Replaced with wc_appointments_get_min_timestamp_for_day
	 * @return int
	 */
	public function get_min_timestamp_for_date( $start_date ) {
		$min = $this->get_min_date_a();

		return wc_appointments_get_min_timestamp_for_day( $start_date, $min['value'], $min['unit'] );
	}

	/**
	 * Sort rules.
	 *
	 * @deprecated Replaced with WC_Product_Appointment_Rule_Manager::sort_rules_callback
	 */
	public function rule_override_power_sort( $rule1, $rule2 ) {
		return WC_Product_Appointment_Rule_Manager::sort_rules_callback( $rule1, $rule2 );
	}

	/**
	 * Return an array of staff which can be scheduled for a defined start/end date
	 *
	 * @deprecated Replaced with wc_appointments_get_slot_availability_for_range
	 * @param  string $start_date
	 * @param  string $end_date
	 * @param  string $staff_id
	 * @param  integer $qty being scheduled
	 * @return bool|WP_ERROR if no slots available, or int count of appointments that can be made, or array of available staff
	 */
	public function get_available_appointments( $start_date, $end_date, $staff_id = '', $qty = 1 ) {
		return wc_appointments_get_total_available_appointments_for_range( $this, $start_date, $end_date, $staff_id, $qty );
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
		} elseif ( $this->has_staff() && ! $staff_id ) {
			$staff_id = $this->get_staff_ids();
		}

		return WC_Appointments_Controller::get_appointments_in_date_range( $start_date, $end_date, $this->get_id(), $staff_id );
	}

	/**
	 * Check a date against the availability rules
	 *
	 * @param  string $check_date date to check
	 * @return bool available or not
	 */
	public function check_availability_rules_against_date( $check_date, $staff_id, $get_capacity = false ) {
		return WC_Product_Appointment_Rule_Manager::check_availability_rules_against_date( $this, $check_date, $staff_id, $get_capacity );
	}

	/**
	 * Check a time against the time specific availability rules
	 *
	 * @param  string       $slot_start_time timestamp to check
	 * @param  string 		$slot_end_time   timestamp to check
	 * @return bool available or not
	 */
	public function check_availability_rules_against_time( $slot_start_time, $slot_end_time, $staff_id, $get_capacity = false ) {
		return WC_Product_Appointment_Rule_Manager::check_availability_rules_against_time( $this, $slot_start_time, $slot_end_time, $staff_id, $get_capacity );
	}

	/**
	 * Find available slots and return HTML for the user to choose a slot. Used in class-wc-appointments-ajax.php.
	 *
	 * @deprecated since 3.0.8
	 * @param \WC_Product_Appointment $appointable_product
	 * @param  array   $slots
	 * @param  array   $intervals
	 * @param  integer $staff_id
	 * @param  string  $from The starting date for the set of slots
	 * @return string
	 */
	function get_available_slots_html( $appointable_product, $slots, $intervals = array(), $time_to_check = 0, $staff_id = 0, $from = '', $to = 0, $timezone = 'UTC' ) {
		_deprecated_function( 'Please use wc_appointments_get_time_slots_html', 'Appointments: 3.0.8' );
		return wc_appointments_get_time_slots_html( $appointable_product, $slots, $intervals, $time_to_check, $staff_id, $from, $to, $timezone );
	}
}
