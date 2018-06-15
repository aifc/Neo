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
		$this->staff   = $user;
		$this->product_id = $product_id;
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
		return $this->staff->ID;
	}

	/**
	 * Get the title of the staff
	 * @return string
	 */
	public function get_title() {
		return $this->staff->display_name;
	}

	/**
	 * Get the email of the staff
	 * @return string
	 */
	public function get_email() {
		return $this->staff->user_email;
	}

	/**
	 * Return the base cost
	 * @return int|float
	 */
	public function get_base_cost() {
		$costs = get_post_meta( $this->product_id, '_staff_base_costs', true );
		$cost  = isset( $costs[ $this->get_id() ] ) ? $costs[ $this->get_id() ] : '';

		return $cost;
	}

	/**
	 * Return the availability rules
	 * @return array
	 */
	public function get_availability() {
		return (array) get_user_meta( $this->staff->ID, '_wc_appointment_availability', true );
	}

	/**
	 * Return the capacity of the staff
	 * @return array
	 */
	public function get_qty() {
		return get_user_meta( $this->staff->ID, '_wc_appointment_staff_qty', true );
	}

}
