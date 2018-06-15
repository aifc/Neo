<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles product appointment transitions
 */
class WC_Product_Appointment_Manager {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		add_action( 'wp_trash_post', array( __CLASS__, 'pre_trash_delete_handler' ), 10, 1 );
		add_action( 'before_delete_post', array( __CLASS__, 'pre_trash_delete_handler' ), 10, 1 );
	}

	/**
	 * Filters whether a appointable product deletion should take place.
	 * If there are Appointments linked to it, do not allow deletion.
	 *
	 * @since 2.5.0
	 *
	 * @param int $post_id  Post ID.
	 */
	public static function pre_trash_delete_handler( $post_id ) {
		if ( ! $post_id ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( 'product' === $post_type ) {
			$product = wc_get_product( $post_id );

			// TODO: Figure the most performant way.
			if ( 'appointment' === $product->get_type() ) {
				$appointments = WC_Appointments_Controller::get_appointments_for_objects( $post_id );

				if ( 0 !== count( $appointments ) ) {
					$message  = __( 'You cannot trash/delete an appointable product that has Appointments associated with it.', 'woocommerce-appointments' );

					wp_die( $message );
				}
			}
		}

		return;
	}
}

new WC_Product_Appointment_Manager();
