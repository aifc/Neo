<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Add-ons integration class.
 */
class WC_Appointments_Integration_Addons {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_addons_show_grand_total', array( $this, 'addons_show_grand_total' ), 20, 2 );
		add_action( 'woocommerce_product_addons_panel_before_options', array( $this, 'addon_options' ), 20, 3 );
		add_action( 'woocommerce_product_addons_panel_option_heading', array( $this, 'addon_option_head' ), 10, 3 );
		add_action( 'woocommerce_product_addons_panel_option_row', array( $this, 'addon_option_body' ), 10, 4 );
		add_filter( 'woocommerce_product_addons_save_data', array( $this, 'save_addon_options' ), 20, 2 );
		add_action( 'woocommerce_appointments_create_appointment_page_add_order_item', array( $this, 'save_addon_options_in_admin' ), 10, 3 );
		add_filter( 'woocommerce_product_addons_adjust_price', array( $this, 'adjust_price' ), 20, 2 );
		add_filter( 'appointment_form_calculated_appointment_cost', array( $this, 'adjust_appointment_cost' ), 10, 3 );
		add_filter( 'woocommerce_product_addon_cart_item_data', array( $this, 'adjust_appointment_cart_data' ), 10, 4 );
		add_filter( 'appointment_form_posted_total_duration', array( $this, 'adjust_appointment_duration' ), 10, 3 );
		add_filter( 'woocommerce_product_addons_option_duration', array( $this, 'hide_product_addons_option_duration' ), 10, 5 );
		add_filter( 'woocommerce_product_addons_option_price', array( $this, 'hide_product_addons_option_price' ), 10, 5 );
	}

	/**
	 * Show grand total or not?
	 * @param  bool $show_grand_total
	 * @param  object $product
	 * @return bool
	 */
	public function addons_show_grand_total( $show_grand_total, $product ) {
		if ( $product && $product->is_type( 'appointment' ) ) {
			$show_grand_total = false;
		}
		return $show_grand_total;
	}

	/**
	 * Show options
	 */
	public function addon_options( $post, $addon, $loop ) {
		$css_classes = '';

		if ( is_object( $post ) ) {
			$product = wc_get_product( $post->ID );
			$css_classes .= 'show_if_appointment';
		}
		?>
		<tr class="<?php echo esc_attr( $css_classes ); ?>">
			<td class="addon_wc_appointment_hide_duration_label addon_required" width="50%">
				<label for="addon_wc_appointment_hide_duration_label_<?php echo $loop; ?>"><?php _e( 'Hide duration label for customers', 'woocommerce-appointments' ); ?></label>
				<input type="checkbox" id="addon_wc_appointment_hide_duration_label_<?php echo $loop; ?>" name="addon_wc_appointment_hide_duration_label[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_appointment_hide_duration_label'] ), true ); ?> />
			</td>
		</tr>
		<tr class="<?php echo esc_attr( $css_classes ); ?>">
			<td class="addon_wc_appointment_hide_price_label addon_required" width="50%">
				<label for="addon_wc_appointment_hide_price_label_<?php echo $loop; ?>"><?php _e( 'Hide price label for customers', 'woocommerce-appointments' ); ?></label>
				<input type="checkbox" id="addon_wc_appointment_hide_price_label_<?php echo $loop; ?>" name="addon_wc_appointment_hide_price_label[<?php echo $loop; ?>]" <?php checked( ! empty( $addon['wc_appointment_hide_price_label'] ), true ); ?> />
			</td>
		</tr>
		<?php
	}

	/**
	 * Show option head
	 */
	public function addon_option_head( $post, $addon, $loop ) {
		$product = wc_get_product( get_the_ID() );
		$css_classes = 'duration_column show_if_appointment';
		$duration_unit = ( $product && method_exists( $product, 'get_duration_unit' ) && 'day' === $product->get_duration_unit() ) ? __( 'days', 'woocommerce-appointments' ) : __( 'minutes', 'woocommerce-appointments' );
		if ( ! $product ) {
			$duration_unit = __( 'units', 'woocommerce-appointments' );
		}
		?>
		<th class="<?php echo esc_attr( $css_classes ); ?>"><?php printf( __( 'Duration (%s)', 'woocommerce-appointments' ), $duration_unit ); ?></th>
		<?php
	}

	/**
	 * Show option body
	 */
	public function addon_option_body( $post = null, $addon, $loop = 0, $option = array() ) {
		$product = wc_get_product( get_the_ID() );
		$css_classes = 'duration_column show_if_appointment';
		?>
		<td class="<?php echo esc_attr( $css_classes ); ?>"><input type="number" name="product_addon_option_duration[<?php echo $loop; ?>][]" value="<?php echo ( isset( $option['duration'] ) ) ? esc_attr( $option['duration'] ) : ''; ?>" placeholder="N/A" min="0" step="any" /></td>
		<?php
	}

	/**
	 * Save options
	 */
	public function save_addon_options( $data, $i ) {
		$addon_option_duration = $_POST['product_addon_option_duration'][ $i ];
		$addon_option_label = $_POST['product_addon_option_label'][ $i ];
		$addon_option_size = count( $addon_option_label );

		for ( $ii = 0; $ii < $addon_option_size; $ii++ ) {
			$duration = sanitize_text_field( stripslashes( $addon_option_duration[ $ii ] ) );
			$data['options'][ $ii ]['duration'] = $duration;
		}

		$data['wc_appointment_hide_duration_label'] = isset( $_POST['addon_wc_appointment_hide_duration_label'][ $i ] ) ? 1 : 0;
		$data['wc_appointment_hide_price_label'] = isset( $_POST['addon_wc_appointment_hide_price_label'][ $i ] ) ? 1 : 0;

		return $data;
	}

	/**
	 * Save options in admin
	 */
	public function save_addon_options_in_admin( $order_id, $item_id, $product ) {
		if ( ! $item_id ) {
			throw new Exception( __( 'Error: Could not create item', 'woocommerce-appointments' ) );
		}

		// Support new WooCommerce 3.0 WC_Product->get_id().
		if ( method_exists( $product, 'get_id' ) ) {
			$product_id = $product->get_id();
		} else {
			$product_id = $product->id;
		}

		$addons = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( '', $product_id, $_POST, true );

		if ( ! empty( $addons['addons'] ) ) {
			foreach ( $addons['addons'] as $addon ) {

				$name = $addon['name'];

				if ( $addon['price'] > 0 && apply_filters( 'woocommerce_addons_add_price_to_name', true ) ) {
					$name .= ' (' . strip_tags( wc_price( get_product_addon_price_for_display( $addon['price'] ) ) ) . ')';
				}

				wc_add_order_item_meta( $item_id, $name, $addon['value'] );
			}
		}
	}

	/**
	 * Don't adjust price for appointments since the appointment form class adds the costs itself
	 * @return bool
	 */
	public function adjust_price( $bool, $cart_item ) {
		if ( $cart_item['data']->is_type( 'appointment' ) ) {
			return false;
		}
		return $bool;
	}

	/**
	 * Adjust the final appointment cost
	 */
	public function adjust_appointment_cost( $appointment_cost, $appointment_form, $posted ) {
		// Product add-ons.
		$addons       = $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $appointment_form->product->get_id(), $posted, true );
		$addon_costs  = 0;
		$appointment_data = $appointment_form->get_posted_data( $posted );

		if ( ! empty( $addons['addons'] ) ) {
			foreach ( $addons['addons'] as $addon ) {
				$addon_cost = 0;
				$addon['price'] = ( ! empty( $addon['price'] ) ) ? $addon['price'] : 0;
				if ( ! empty( $appointment_data['_qty'] ) ) {
					$addon_cost += floatval( $addon['price'] ) * $appointment_data['_qty'];
				}
				if ( ! $addon_cost ) {
					$addon_cost += floatval( $addon['price'] );
				}
				$addon_costs += $addon_cost;
			}
		}

		return $appointment_cost + $addon_costs;
	}

	/**
	 * Adjust the final appointment cart item data.
	 *
	 * Insert missing duration data.
	 */
	public function adjust_appointment_cart_data( $data, $addon, $product_id, $post_data ) {
		// Modify default data array.
		$data_array = array();
		foreach ( $data as $data_key => $data_value ) {
			$data_array[ $data_key ] = $data_value;

			foreach ( $addon['options'] as $addon_key => $addon_value ) {
				switch ( $addon['type'] ) {
					case 'input_multiplier':
						$length = strlen( $addon_value['label'] );
						if ( isset( $addon_value['duration'] ) && ( substr( $data_value['name'], -$length ) === $addon_value['label'] ) ) {
							$data_array[ $data_key ]['duration'] = $addon_value['duration'] * $data_value['value'];
						}
						break;
					case 'custom':
					case 'custom_price':
					case 'custom_textarea':
						$length = strlen( $addon_value['label'] );
						if ( isset( $addon_value['duration'] ) && ( substr( $data_value['name'], -$length ) === $addon_value['label'] ) ) {
							$data_array[ $data_key ]['duration'] = $addon_value['duration'];
						}
						break;
					default:
						if ( isset( $addon_value['duration'] ) && ( $data_value['value'] === $addon_value['label'] ) ) {
							$data_array[ $data_key ]['duration'] = $addon_value['duration'];
						}
						break;
				}
			}
		}

		return $data_array;
	}

	/**
	 * Adjust the final appointment duration
	 */
	public function adjust_appointment_duration( $appointment_duration, $appointment_form, $posted ) {
		// Product add-ons.
		$addons       	= $GLOBALS['Product_Addon_Cart']->add_cart_item_data( array(), $appointment_form->product->get_id(), $posted, true );
		$addon_duration	= 0;

		if ( ! empty( $addons['addons'] ) ) {
			foreach ( $addons['addons'] as $addon ) {
				$addon_duration_units = 0;
				if ( ! empty( $addon['duration'] ) ) {
					$addon_duration_units += $addon['duration'];
				}
				$addon_duration += $addon_duration_units;
			}
		}

		#var_dump( $appointment_duration + $addon_duration );

		return $appointment_duration + $addon_duration;
	}

	/**
	 * Optionally hide duration label for customers.
	 *
	 */
	public function hide_product_addons_option_duration( $posted, $option, $key, $addon, $type = '' ) {
		$hide_label = isset( $addon['wc_appointment_hide_duration_label'] ) ? $addon['wc_appointment_hide_duration_label'] : false;
		if ( $hide_label ) {
			return;
		}

		return $posted;
	}

	/**
	 * Optionally hide price label for customers.
	 *
	 */
	public function hide_product_addons_option_price( $posted, $option, $key, $addon, $type = '' ) {
		$hide_label = isset( $addon['wc_appointment_hide_price_label'] ) ? $addon['wc_appointment_hide_price_label'] : false;
		if ( $hide_label ) {
			return;
		}

		return $posted;
	}

}

$GLOBALS['wc_appointments_integration_addons'] = new WC_Appointments_Integration_Addons();
