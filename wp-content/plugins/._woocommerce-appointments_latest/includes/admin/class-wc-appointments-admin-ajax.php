<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Appointment ajax callbacks.
 */
class WC_Appointments_Admin_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		// TODO: Switch from `wp_ajax` to `wc_ajax`
		add_action( 'wp_ajax_woocommerce_add_appointable_staff', array( $this, 'add_appointable_staff' ) );
		add_action( 'wp_ajax_woocommerce_remove_appointable_staff', array( $this, 'remove_appointable_staff' ) );
		add_action( 'wp_ajax_wc-appointment-confirm', array( $this, 'mark_appointment_confirmed' ) );
		add_action( 'wp_ajax_wc_appointments_calculate_costs', array( $this, 'calculate_costs' ) );
		add_action( 'wp_ajax_nopriv_wc_appointments_calculate_costs', array( $this, 'calculate_costs' ) );
		add_action( 'wp_ajax_wc_appointments_get_slots', array( $this, 'get_time_slots_for_date' ) );
		add_action( 'wp_ajax_nopriv_wc_appointments_get_slots', array( $this, 'get_time_slots_for_date' ) );
		add_action( 'wp_ajax_wc_appointments_json_search_order', array( $this, 'json_search_order' ) );
	}

	/**
	 * Add staff
	 */
	public function add_appointable_staff() {
		check_ajax_referer( 'add-appointable-staff', 'security' );

		$post_id        = intval( $_POST['post_id'] );
		$loop           = intval( $_POST['loop'] );
		$add_staff_id   = intval( $_POST['add_staff_id'] );

		if ( ! $add_staff_id ) {
			$staff = new WC_Product_Appointment_Staff();
			$add_staff_id = $staff->save();
		} else {
			$staff = new WC_Product_Appointment_Staff( $add_staff_id );
		}

		// Return html
		if ( $add_staff_id ) {
			$appointable_product	= new WC_Product_Appointment( $post_id );
			$staff_ids   			= $appointable_product->get_staff_ids();

			if ( in_array( $add_staff_id, $staff_ids ) ) {
				wp_send_json( array( 'error' => __( 'The staff has already been linked to this product', 'woocommerce-appointments' ) ) );
			}

			$staff_ids[] = $add_staff_id;
			$appointable_product->set_staff_ids( $staff_ids );
			$appointable_product->save();

			// get the post object due to it is used in the included template
			$post = get_post( $post_id );

			ob_start();
			include( 'views/html-appointment-staff-member.php' );
			wp_send_json( array( 'html' => ob_get_clean() ) );
		}

		wp_send_json( array( 'error' => __( 'Unable to add staff', 'woocommerce-appointments' ) ) );
	}

	/**
	 * Remove staff
	 * TO DO: you should revert post meta logic that is set in class-wc-appointments-admin.php on line 559-593 ????
	 */
	public function remove_appointable_staff() {
		check_ajax_referer( 'delete-appointable-staff', 'security' );

		$post_id		= absint( $_POST['post_id'] );
		$staff_id		= absint( $_POST['staff_id'] );
		$product		= new WC_Product_Appointment( $post_id );
		$staff_ids		= $product->get_staff_ids();
		$staff_ids		= array_diff( $staff_ids, array( $staff_id ) );
		$product->set_staff_ids( $staff_ids );
		$product->save();
		die();
	}

	/**
	 * Mark an appointment confirmed
	 */
	public function mark_appointment_confirmed() {
		if ( ! current_user_can( 'manage_appointments' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-appointments' ) );
		}
		if ( ! check_admin_referer( 'wc-appointment-confirm' ) ) {
			wp_die( __( 'You have taken too long. Please go back and retry.', 'woocommerce-appointments' ) );
		}
		$appointment_id = isset( $_GET['appointment_id'] ) && (int) $_GET['appointment_id'] ? (int) $_GET['appointment_id'] : '';
		if ( ! $appointment_id ) {
			die;
		}

		$appointment = get_wc_appointment( $appointment_id );
		if ( 'confirmed' !== $appointment->get_status() ) {
			$appointment->update_status( 'confirmed' );
		}

		wp_safe_redirect( wp_get_referer() );
	}

	/**
	 * Calculate costs
	 *
	 * Take posted appointment form values and then use these to quote a price for what has been chosen.
	 * Returns a string which is appended to the appointment form.
	 */
	public function calculate_costs() {

		$posted = array();

		parse_str( $_POST['form'], $posted );

		$product_id = $posted['add-to-cart'];
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json( array(
				'result' => 'ERROR',
				'html'   => '<span class="appointment-error">' . __( 'This appointment is unavailable.', 'woocommerce-appointments' ) . '</span>',
			) );
		}

		$appointment_form	= new WC_Appointment_Form( $product );
		$cost             	= $appointment_form->calculate_appointment_cost( $posted );

		if ( is_wp_error( $cost ) ) {
			wp_send_json( array(
				'result' => 'ERROR',
				'html'   => '<span class="appointment-error">' . $cost->get_error_message() . '</span>',
			) );
		}

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );

		if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_including_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_including_tax( 1, $cost );
			}
		} else {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$display_price = wc_get_price_excluding_tax( $product, array( 'price' => $cost ) );
			} else {
				$display_price = $product->get_price_excluding_tax( 1, $cost );
			}
		}

		if ( version_compare( WC_VERSION, '2.4.0', '>=' ) ) {
			$price_suffix = $product->get_price_suffix( $cost, 1 );
		} else {
			$price_suffix = $product->get_price_suffix();
		}

		$appointment_cost_html = '<dl><dt>' . _x( 'Cost', 'appointment cost string', 'woocommerce-appointments' ) . ':</dt><dd><strong>' . wc_price( $display_price ) . $price_suffix . '</strong></dd></dl>';
		$appointment_cost_html = apply_filters( 'woocommerce_appointments_appointment_cost_html', $appointment_cost_html, $product, $posted );

		wp_send_json( array(
			'result' => 'SUCCESS',
			'html'   => $appointment_cost_html,
		) );
	}

	/**
	 * Get a list of time slots available on a date
	 */
	public function get_time_slots_for_date() {
		// Clean posted data.
		$posted = array();
		parse_str( $_POST['form'], $posted );
		if ( empty( $posted['add-to-cart'] ) ) {
			return false;
		}

		// Product Checking.
		$product_id			= $posted['add-to-cart'];
		$product			= new WC_Product_Appointment( wc_get_product( $product_id ) );
		if ( ! $product ) {
			return false;
		}

		// Timezone.
		$timezone			= $_POST['timezone'];

		// Check selected date.
		if ( ! empty( $posted['wc_appointments_field_start_date_year'] ) && ! empty( $posted['wc_appointments_field_start_date_month'] ) && ! empty( $posted['wc_appointments_field_start_date_day'] ) ) {
			$year      = max( date( 'Y' ), absint( $posted['wc_appointments_field_start_date_year'] ) );
			$month     = absint( $posted['wc_appointments_field_start_date_month'] );
			$day       = absint( $posted['wc_appointments_field_start_date_day'] );
			$timestamp = strtotime( "{$year}-{$month}-{$day}" );
		}
		if ( empty( $timestamp ) ) {
			die( esc_html__( 'Please enter a valid date.', 'woocommerce-appointments' ) );
		}

		// Intervals.
		list( $interval, $base_interval ) = $product->get_intervals();

		$from				= $time_from = strtotime( 'midnight', $timestamp );
		$to					= strtotime( '+1 day', $from ) + $interval;
		#$to                 = strtotime( 'midnight', $to ) - 1; // cap the upper range.
		$time_to_check		= ! empty( $posted['wc_appointments_field_start_date_time'] ) ? strtotime( $posted['wc_appointments_field_start_date_time'] ) : 0;
		$staff_id_to_check	= ! empty( $posted['wc_appointments_field_staff'] ) ? $posted['wc_appointments_field_staff'] : 0;
		$staff_member       = $product->get_staff_member( absint( $staff_id_to_check ) );
		$staff              = $product->get_staff();
		if ( $staff_id_to_check && $staff_member ) {
			$staff_id_to_check = $staff_member->get_id();
		} elseif ( ( $staff ) && count( $staff ) === 1 ) {
			$staff_id_to_check = current( $staff )->ID;
		} elseif ( $product->is_staff_assignment_type( 'all' ) ) {
			$staff_id_to_check = $product->get_staff_ids();
		} else {
			$staff_id_to_check = 0;
		}

		#var_dump( date( 'Y-n-j H:i', $from ) .'___'. date( 'Y-n-j H:i', $to ) . '<br/>' );

		$slots     = $product->get_slots_in_range( $from, $to, array( $interval, $base_interval ), $staff_id_to_check );
		#var_dump($slots);
		$slot_html = wc_appointments_get_time_slots_html( $product, $slots, array( $interval, $base_interval ), $time_to_check, $staff_id_to_check, $from, $to, $timezone );

		if ( empty( $slot_html ) ) {
			$slot_html .= __( 'No slots available.', 'woocommerce-appointments' );
		}

		die( $slot_html );
	}

	/**
	 * Search for customers and return json.
	 */
	public function json_search_order() {
		global $wpdb;

		check_ajax_referer( 'search-appointment-order', 'security' );

		$term = wc_clean( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			die();
		}

		$found_orders = array();

		$term = apply_filters( 'woocommerce_appointment_json_search_order_number', $term );

		$query_orders = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, post_title FROM {$wpdb->posts} AS posts
			WHERE posts.post_type = 'shop_order'
			AND posts.ID LIKE %s
			LIMIT 10
		", $term . '%' ) );

		if ( $query_orders ) {
			foreach ( $query_orders as $item ) {
				$order = wc_get_order( $item->ID );
				if ( is_a( $order, 'WC_Order' ) ) {
					$found_orders[ ( is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : $order->id ) ] = $order->get_order_number() . ' &ndash; ' . date_i18n( wc_date_format(), strtotime( is_callable( array( $order, 'get_date_created' ) ) ? $order->get_date_created() : $order->post_date ) );
				}
			}
		}

		wp_send_json( $found_orders );
	}

}

$GLOBALS['wc_appointments_admin_ajax'] = new WC_Appointments_Admin_Ajax();
