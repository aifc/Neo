<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gravity Froms Addons integration class.
 */
class WC_Appointments_Integration_GF_Addons {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'appointment_form_calculated_appointment_cost', array( $this, 'adjust_appointment_cost' ), 10, 3 );
	}

	/**
	 * Adjust the final appointment cost
	 */
	public function adjust_appointment_cost( $appointment_cost, $appointment_form, $posted ) {
		$addon_costs  = 0;
		$gform_form_id = isset( $posted['gform_form_id'] ) ? $posted['gform_form_id'] : '';
		$form_meta = RGFormsModel::get_form_meta( $gform_form_id );
		$form_meta = gf_apply_filters( array( 'gform_pre_render', $gform_form_id ), $form_meta );

		#print '<pre>'; print_r( $form_meta ); print '</pre>';
		#print '<pre>'; print_r( $posted ); print '</pre>';

		if ( ! empty( $form_meta ) ) {

			$valid_fields = array();

			$i = 0;
			foreach ( $form_meta['fields'] as $field ) {
				#print '<pre>'; print_r( $field ); print '</pre>';

				if (
					//'product' == $field['type'] 			||
					//'hiddenproduct' == $field['inputType'] 	||
					'calculation' == $field['inputType'] 	||
					'total' == $field['type'] 				||
					( isset( $field['displayOnly'] ) && $field['displayOnly'] )
				) {
					continue;
				}

				$valid_fields[ $i ]['id'] = $field['id'];
				$valid_fields[ $i ]['inputType'] = $field['inputType'];
				$valid_fields[ $i ]['productField'] = $field['productField'];

				#print '<pre>'; print_r( $field['inputs'] ); print '</pre>';

				if ( ! empty( $field['inputs'] ) ) {
					foreach ( $field['inputs'] as $k => $v ) {
						$valid_fields[ $i ]['id'] = preg_replace( '#\.#', '_', $v['id'] );
						$valid_fields[ $i ]['inputType'] = $field['inputType'];
						$valid_fields[ $i ]['productField'] = $field['productField'];
						$i++;
					}
				}

				$i++;
			}

			#print '<pre>'; print_r( $valid_fields ); print '</pre>';
			#print '<pre>'; print_r( $posted ); print '</pre>';

			if ( ! empty( $valid_fields ) ) {
				foreach ( $valid_fields as $valid ) {
					$addon_cost = 0;

					if ( isset( $posted[ 'input_' . $valid['id'] ] ) && ! is_array( $posted[ 'input_' . $valid['id'] ] ) ) {
						if ( in_array( $valid['inputType'], array( 'calculation', 'singleproduct', 'hiddenproduct' ) ) ) {
							$pieces = explode( "_", $valid['id'] );
							$price = ( isset( $pieces['1'] ) && 2 == $pieces['1'] ) ? GFCommon::to_number( $posted[ 'input_' . $valid['id'] ] ) : 0;
							$quantity = ( isset( $posted[ 'input_' . $pieces['0'] . '_3' ] ) ) ? absint( $posted[ 'input_' . $pieces['0'] . '_3' ] ) : 1;
							if ( $valid['productField'] ) {
								$quantity = ( isset( $posted[ $valid['productField'] . '_3' ] ) ) ? $posted[ $valid['productField'] . '_3' ] : 1;
							}
							$cost = $price * $quantity;
							$addon_cost += $cost;
						} elseif ( in_array( $valid['inputType'], array( 'price' ) ) ) {
							$cost = isset( $posted[ 'input_' . $valid['id'] ] ) ? GFCommon::to_number( $posted[ 'input_' . $valid['id'] ] ) : 0;
							$addon_cost += $cost;
						} elseif ( in_array( $valid['inputType'], array( 'number' ) ) ) {
							if ( $valid['productField'] ) {
								$pfield = ( isset( $posted[ 'input_' . $valid['productField'] ] ) ) ? $posted[ 'input_' . $valid['productField'] ] : 'x|0';
								$pieces = explode( "|", $pfield );
								$price = ( isset( $pieces['1'] ) ) ? GFCommon::to_number( $pieces['1'] ) : 0;
								$quantity = ( isset( $posted[ 'input_' . $valid['id'] ] ) ) ? absint( $posted[ 'input_' . $valid['id'] ] ) : 1;
								$cost = $price * $quantity;
								$cost = $cost - $price;
								$addon_cost += $cost;
							}
						} else {
							$pieces = explode( "|", $posted[ 'input_' . $valid['id'] ] );
							if ( $valid['productField'] ) {
								$quantity = ( isset( $posted[ 'input_' . $valid['productField'] . '_3' ] ) ) ? absint( $posted[ 'input_' . $valid['productField'] . '_3' ] ) : 1;
								$price = ( isset( $pieces['1'] ) ) ? $pieces[1] : 0;
								$cost = $price * $quantity;
								$addon_cost += $cost;
							} else {
								$addon_cost += isset( $pieces[1] ) ? $pieces[1] : 0;
							}
						}
					}

					$addon_costs += $addon_cost;
				}
			}
		}

		return $appointment_cost + $addon_costs;
	}
}

$GLOBALS['wc_appointments_integration_gf_addons'] = new WC_Appointments_Integration_GF_Addons();
