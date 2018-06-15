<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Memberships integration class.
 *
 * Last compatibility check: WooCommerce Memberships 1.9.5
 *
 * @since 3.2.0
 */
class WC_Appointments_Integration_Memberships {


	/**
	 * Constructor
	 *
	 * @since 3.2.0
	 */
	public function __construct() {
		// Adjusts discounts.
		add_filter( 'appointment_form_calculated_appointment_cost', array( $this, 'adjust_appointment_cost' ), 10, 3 );
	}


	/**
	 * Adjust appointment cost
	 *
	 * @since 3.2.0
	 * @param float $cost
	 * @param \WC_Appointment_Form $form
	 * @param array $posted
	 * @return float
	 */
	public function adjust_appointment_cost( $appointment_cost, $appointment_form, $posted ) {
        $discounted_cost = $appointment_cost;

        // Need to separate extra costs (staff, addons) from product price.
        // Product price is already discounted_cost
        $extra_cost  = $appointment_cost - $appointment_form->product->get_price();
        $extra_cost  = wc_memberships()->get_member_discounts_instance()->get_discounted_price( $extra_cost, $appointment_form->product );
        if ( ! $extra_cost ) {
            $extra_cost = 0;
        }

        $discounted_cost = $appointment_form->product->get_price() + $extra_cost;
        $member_discounts = wc_memberships()->get_member_discounts_instance();

        // Don't discount the price when adding an appointment to the cart.
		if ( doing_action( 'woocommerce_add_cart_item_data' ) ) {
            // Exists from WC Memberships 1.8.8 onwards.
            if ( null !== WC_Memberships::VERSION && version_compare( WC_Memberships::VERSION, '1.8.8', '>=' ) ) {
                $discounted_cost = $member_discounts->get_original_price( $discounted_cost, $appointment_form->product );
            } else {
                $discounted_cost = $appointment_form->product->get_regular_price();
            }
        } elseif ( is_admin() && isset( $posted['add_appointment_2'] ) ) {
			$discounted_cost = $member_discounts->get_discounted_price( $appointment_cost, $appointment_form->product );
		}

		return (float) $discounted_cost;
	}

}

$GLOBALS['wc_appointments_integration_memberships'] = new WC_Appointments_Integration_Memberships();
