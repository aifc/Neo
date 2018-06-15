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
	}

	/**
	 * Adjust the final appointment cost
	 */
	public function adjust_appointment_cost( $appointment_cost, $appointment_form, $posted ) {
		$addon_costs	= 0;
		$product_id		= $appointment_form->product->id;

		#print_r( $posted );

		$posted	= array_filter( $posted ); // remove empty values
		$_POST	= $posted;

		#print_r( $posted );

		// Get all fields associated with current product.
		$cpf_price_array = TM_EPO()->get_product_tm_epos( $product_id );

		if ( ! $cpf_price_array ) {
			return $appointment_cost;
		}

		$global_price_array = $cpf_price_array['global'];

		if ( empty( $global_price_array ) ) {
			return $appointment_cost;
		}

		// Get posted Product Price.
		$cpf_product_price = ( isset( $posted['cpf_product_price'] ) ) ? $posted['cpf_product_price'] : false;

		// Get all posted TM EPO files.
		$files = array();
		foreach ( $_FILES as $k => $file ) {
			if ( ! empty( $file['name'] ) ) {
				$files[ $k ] = $file['name'];
			}
		}

		// Get all posted TM EPO fields.
		$tmcp_post_fields = array_merge( TM_EPO_HELPER()->array_filter_key( $posted ), TM_EPO_HELPER()->array_filter_key( $files ) );
		if ( is_array( $tmcp_post_fields ) ) {
			$tmcp_post_fields = array_map( 'stripslashes_deep', $tmcp_post_fields );
		}

		if ( ! $tmcp_post_fields ) {
			return $appointment_cost;
		}

		#print_r( $tmcp_post_fields );
		#print_r( $global_price_array );

		$loop = 0;

		foreach ( $global_price_array as $priority => $priorities ) {
			foreach ( $priorities as $pid => $field ) {
				if ( isset( $field['sections'] ) && is_array( $field['sections'] ) ) {
					foreach ( $field['sections'] as $section_id => $section ) {
						if ( isset( $section['elements'] ) && is_array( $section['elements'] ) ) {
							foreach ( $section['elements'] as $element ) {
								$current_tmcp_post_fields = array_intersect_key( $tmcp_post_fields, array_flip( TM_EPO()->translate_fields( $element['options'], $element['type'], $loop ) ) );

								foreach ( $current_tmcp_post_fields as $attribute => $key ) {
									$addon_costs += TM_EPO()->calculate_price( null, $element, $key, $attribute, true, $cpf_product_price, false );
								}

								$loop++;
							}
						}
					}
				}
			}
		}

		return $appointment_cost + $addon_costs;
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

}

$GLOBALS['wc_appointments_integration_tm_epo'] = new WC_Appointments_Integration_TM_EPO();
