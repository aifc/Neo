<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for an appointment product's staff type
 */
class WC_Product_Appointment_Staff {

	private $staff;
	private $product_id;

	/**
	 * Constructor
	 */
	public function __construct( $user, $product_id = 0 ) {
		$this->staff = is_numeric( $user ) ? get_user_by( 'id', $user ) : $user;

		$appointable_product = wc_get_product( $product_id );

		$this->product = $appointable_product ? $appointable_product : false;
	}

	/**
	 * __isset function.
	 *
	 * @access public
	 * @param string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->staff->$key );
	}

	/**
	 * __get function.
	 *
	 * @access public
	 * @param string $key
	 * @return string
	 */
	public function __get( $key ) {
		return $this->staff->$key;
	}

	/**
	 * Return the ID
	 * @return int
	 */
	public function get_id() {
		return isset( $this->staff->ID ) ? $this->staff->ID : 0;
	}

	/**
	 * Get the title of the staff
	 * @return string
	 */
	public function get_display_name() {
		return $this->get_id() ? $this->staff->display_name : '';
	}

	/**
	 * Get the full name of the staff
	 * @return string
	 */
	public function get_full_name() {
		return $this->get_id() ? trim( $this->staff->user_firstname . ' ' . $this->staff->user_lastname ) : '';
	}

	/**
	 * Get the email of the staff
	 * @return string
	 */
	public function get_email() {
		return $this->get_id() ? $this->staff->user_email : '';
	}

	/**
	 * Return the base cost
	 * @return int|float
	 */
	public function get_base_cost() {
		$cost = 0;

		if ( $this->get_id() && $this->product ) {
			$costs = $this->product->get_staff_base_costs();
			$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : 0;
		}

		return (float) $cost;
	}

	/**
	 * Return the capacity of the staff
	 * @return array
	 */
	public function get_qty() {
		$qty = 0;

		if ( $this->get_id() && $this->product ) {
			$qtys = $this->product->get_staff_qtys();
			$qty  = isset( $qtys[ $this->get_id() ] ) ? $qtys[ $this->get_id() ] : 0;
		}

		// Default to product qty, when staff capacity not set on product level.
		if ( ! $qty ) {
			$qty = $appointable_product->get_qty();
		}

		return (float) $qty;
	}

	/**
	 * Return the availability rules
	 * @return array
	 */
	public function get_availability() {
		return $this->get_id() ? (array) get_user_meta( $this->get_id(), '_wc_appointment_availability', true ) : array();
	}

}
