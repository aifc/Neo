<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce TM Extra Product Options integration class.
 */
class WC_Appointments_Integration_TM_EPO {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'appointment_form_calculated_appointment_cost', array( $this, 'adjust_appointment_cost' ), 10, 3 );
		add_filter( 'wc_epo_adjust_price', array( $this, 'appointment_epo_adjust_price' ), 10, 2 );
		add_filter( 'wcml_cart_contents_not_changed', array( $this, 'filter_bundled_product_in_cart_contents' ), 9999, 3 );
	}

	/**
	 * Adjust the final appointment cost
	 */
	public function adjust_appointment_cost( $appointment_cost, $appointment_form, $posted ) {
		$addon_costs	= 0;
		$product_id		= $appointment_form->product->get_id();

		#print_r( $posted );

		$posted	= array_filter( $posted ); // remove empty values
		$_POST	= $posted;

		$epos = TM_EPO()->tm_add_cart_item_data( array(), $product_id, $posted, TRUE );
		$extra_price = 0;
		if ( ! empty( $epos ) && ! empty( $epos['tmcartepo'] ) ) {
			foreach ( $epos['tmcartepo'] as $key => $value ) {
				if ( ! empty( $value['price'] ) ) {
					$price = floatval( $value['price'] );
					$option_price = 0;

					if ( ! $option_price ) {
						$option_price += $price;
					}

					$extra_price += $option_price;
				}
			}
		}

		#print_r( $extra_price );

		return $appointment_cost + $extra_price;
	}

	/**
	 * Remove EPO cart price adjustment for appointments as price is already adjusted by plugin itself
	 */
	public function appointment_epo_adjust_price( $value = true, $cart_item ) {
		if ( isset( $cart_item['appointment'] ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Compatibility with WPML multicurrency.
	 */
	public function filter_bundled_product_in_cart_contents( $cart_item, $key, $current_language ){
		global $woocommerce_wpml;

		if ( defined('WCML_MULTI_CURRENCIES_INDEPENDENT') && $cart_item['data'] instanceof WC_Product_Appointment && isset( $cart_item['appointment'] ) ) {

			$current_id      = apply_filters( 'translate_object_id', $cart_item['product_id'], 'product', true, $current_language );
			$cart_product_id = $cart_item['product_id'];

			if ( $woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT || $current_id != $cart_product_id ) {

				$tm_epo_options_prices = floatval( $cart_item['tm_epo_options_prices'] );
				$current_cost = floatval( $cart_item['data']->get_price() );

				$cart_item['data']->set_price( $current_cost + $tm_epo_options_prices );

			}

		}

		return $cart_item;
	}

}

$GLOBALS['wc_appointments_integration_tm_epo'] = new WC_Appointments_Integration_TM_EPO();
