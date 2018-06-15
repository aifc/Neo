<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Appointments WC ajax callbacks.
 */
class WC_Appointments_WC_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wc_ajax_wc_appointments_find_scheduled_day_slots', array( $this, 'find_scheduled_day_slots' ) );
	}

	/**
	 * This endpoint is supposed to replace the back-end logic in appointment-form.
	 */
	public function find_scheduled_day_slots() {
		check_ajax_referer( 'find-scheduled-day-slots', 'security' );

		$post_id = absint( $_GET['post_id'] );

		if ( empty( $post_id ) ) {
			die();
		}

		$args     = array();
		$product  = new WC_Product_Appointment( $post_id );

		// Initialize availability rules
		$args['availability_rules']    = array();
		$args['availability_rules'][0] = $product->get_availability_rules();

		if ( $product->has_staff() ) {
			foreach ( $product->get_staff_ids() as $staff_member_id ) {
				$args['availability_rules'][ $staff_member_id ] = $product->get_availability_rules( $staff_member_id );
			}
		}

		// Initialize min_date and max_date (either requested or from bookable product default)
		$args['min_date'] = isset( $_GET['min_date'] ) ? $_GET['min_date'] : $product->get_min_date();
		$args['max_date'] = isset( $_GET['max_date'] ) ? $_GET['max_date'] : $product->get_max_date();

		// Initialize scheduled day slots
		$scheduled = WC_Appointments_Controller::find_scheduled_day_slots( $product->get_id(), $args['min_date'], $args['max_date'] );
		$args['partially_scheduled_days'] = $scheduled['partially_scheduled_days'];
		$args['remaining_scheduled_days'] = $scheduled['remaining_scheduled_days'];
		$args['fully_scheduled_days']     = $scheduled['fully_scheduled_days'];
		$args['unavailable_days']         = $scheduled['unavailable_days'];

		// Initialize padding days
		$padding_days = WC_Appointments_Controller::get_padding_day_slots_for_scheduled_days( $product, $args['fully_scheduled_days'] );
		$args['padding_days'] = $padding_days;

		// TODO: See which of these variables are really needed.
		//$args['type']                    = $this->field_type;
		//$args['name']                    = $this->field_name;
		$args['default_availability']    = $product->get_default_availability();
		//$args['min_date_js']             = $this->get_min_date();
		//$args['max_date_js']             = $this->get_max_date();
		$args['duration_unit']           = $product->get_duration_unit();
		//$args['label']                   = $this->get_field_label( __( 'Date', 'woocommerce-appointments' ) );
		//$args['default_date']            = date( 'Y-m-d', $this->get_default_date() );
		$args['product_type']            = $product->get_type();
		$args['restricted_days']         = $product->has_restricted_days() ? $product->get_restricted_days() : false;

		wp_send_json( $args );
	}
}

new WC_Appointments_WC_Ajax();
