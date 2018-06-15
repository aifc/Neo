<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Appointment admin
 */
class WC_Appointments_Admin {

	private static $_this;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$_this = $this;

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'woocommerce_admin_order_item_headers', array( $this, 'appointments_link_header' ) );
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'appointments_link' ), 10, 3 );
		add_action( 'admin_init', array( $this, 'include_post_type_handlers' ) );
		add_action( 'admin_init', array( $this, 'include_meta_box_handlers' ) );
		add_action( 'admin_init', array( $this, 'redirect_new_add_appointment_url' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tab' ), 5 );
		add_action( 'woocommerce_product_write_panels', array( $this, 'appointment_panels' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 20 );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'appointment_general' ) );
		add_action( 'load-options-general.php', array( $this, 'reset_ics_exporter_timezone_cache' ) );
		add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
		add_filter( 'woocommerce_template_overrides_scan_paths', array( $this, 'template_scan_path' ) );
		add_filter( 'set-screen-option', array( $this, 'calendar_set_option' ), 10, 3 );
		add_filter( 'screen_settings', array( $this, 'calendar_show_screen_options' ), 10, 2 );
		add_filter( 'woocommerce_appointments_calendar_view_by_staff', array( $this, 'calendar_view_by_staff' ) );

		add_action( 'save_post_product', array( $this, 'validate_appointment_data' ), 11 );
		add_action( 'before_delete_post', array( $this, 'before_delete_product' ), 11 );

		// Ajax
		add_action( 'wp_ajax_woocommerce_add_appointable_staff', array( $this, 'add_appointable_staff' ) );
		add_action( 'wp_ajax_woocommerce_remove_appointable_staff', array( $this, 'remove_appointable_staff' ) );

		include( 'class-wc-appointments-admin-menus.php' );
		include( 'class-wc-appointments-admin-staff-profile.php' );
	}

	/**
	 * Public access to instance object
	 *
	 * @return object
	 */
	public static function get_instance() {
		return self::$_this;
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		$messages['wc_appointment'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Appointment updated.', 'woocommerce-appointments' ),
			2 => __( 'Custom field updated.', 'woocommerce-appointments' ),
			3 => __( 'Custom field deleted.', 'woocommerce-appointments' ),
			4 => __( 'Appointment updated.', 'woocommerce-appointments' ),
			5 => '',
			6 => __( 'Appointment updated.', 'woocommerce-appointments' ),
			7 => __( 'Appointment saved.', 'woocommerce-appointments' ),
			8 => __( 'Appointment submitted.', 'woocommerce-appointments' ),
			9 => '',
			10 => '',
		);

		return $messages;
	}

	/**
	 * Header for appointments link TD
	 */
	public function appointments_link_header() {
		?><th>&nbsp;</th><?php
	}

	/**
	 * Link to appointments on order edit page
	 */
	public function appointments_link( $_product, $item, $item_id ) {
		global $wpdb;

		if ( $_product && $_product->is_type( 'appointment' ) ) {
			$appointment_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_appointment_order_item_id' AND meta_value = %d;", $item_id ) );
			if ( $appointment_id ) {
				?>
				<td>
					<a href="<?php echo admin_url( 'post.php?post=' . $appointment_id . '&action=edit' ); ?>"><?php _e( 'View appointment', 'woocommerce-appointments' ); ?></a>
				</td>
				<?php
			} else {
				echo '<td></td>';
			}
		} else {
			echo '<td></td>';
		}
	}

	/**
	 * Include CPT handlers
	 */
	public function include_post_type_handlers() {
		include( 'class-wc-appointments-admin-cpt.php' );
	}

	/**
	 * Include meta box handlers
	 */
	public function include_meta_box_handlers() {
		include( 'class-wc-appointments-admin-meta-boxes.php' );
	}

	/**
	 * Redirect the default add appointment url to the custom one
	 */
	public function redirect_new_add_appointment_url() {
		global $pagenow;

		if ( 'post-new.php' == $pagenow && isset( $_GET['post_type'] ) && 'wc_appointment' == $_GET['post_type'] ) {
			wp_redirect( admin_url( 'edit.php?post_type=wc_appointment&page=add_appointment' ), '301' );
		}
	}

	/**
	 * Get appointment products
	 * @return array
	 */
	public static function get_appointment_staff() {
		return get_users( apply_filters( 'get_appointment_staff_args', array(
			'role'      	 => 'shop_staff',
			'orderby' 		 => 'nicename',
			'order'          => 'asc',
		) ) );
	}

	/**
	 * Save screen options for calendar page
	 *
	 * @return void
	 */
	public function calendar_set_option( $status, $option, $value ) {
		if ( 'calendar_show_columns' == $option ) {
			$value = isset( $_POST['calendar_columns'] ) ? $_POST['calendar_columns'] : '';
		}
		return $value;
	}

	/**
	 * Show screen options for calendar page
	 *
	 * @return void
	 */
	public function calendar_show_screen_options( $status, $args ) {
		$return = $status;

		// Only show on calendar page.
		if ( 'wc_appointment_page_appointment_calendar' == $args->base ) {

			$button = get_submit_button( __( 'Apply', 'woocommece-appointments' ), 'button', 'screen-options-apply', false );
			$usermeta = get_user_meta( get_current_user_id(), 'calendar_show_columns', true );
			$saved = isset( $usermeta['staff_in_columns'] ) ? $usermeta['staff_in_columns'] : false;
			$checked = checked( $saved, 1, false );
			$return .= "
			<fieldset>
			<legend>" . __( 'Day View', 'woocommerce-appointments' ) . "</legend>
			<div class='metabox-prefs'>
			<div><input type='hidden' name='wp_screen_options[option]' value='calendar_show_columns' /></div>
			<div><input type='hidden' name='wp_screen_options[value]' value='yes' /></div>
			<div class='calendar_custom_fields'>
				<input type='checkbox' value='1' id='calendar_columns[staff_in_columns]' name='calendar_columns[staff_in_columns]' $checked />
				<label for='calendar_columns[staff_in_columns]'>" . __( 'Show Staff in Columns', 'bizzthemes' ) . "</label>
			</div>
			</div>
			</fieldset>
			<br class='clear'>
			$button";

		}

		return $return;
	}

	/**
	 * Calendar view staff in columns
	 *
	 * @return void
	 */
	public function calendar_view_by_staff( $return = false ) {
		$screenopt = get_user_meta( get_current_user_id(), 'calendar_show_columns', true );

		if ( isset( $screenopt['staff_in_columns'] ) ) {
			return true;
		}

		return $return;
	}

	/**
	 * On save product hook validates all the appointments for this product (excludes the completed ones)
	 *
	 * @param int $product_id The id of the product that we want appointments for
	 */
	public function validate_appointment_data( $product_id ) {
		$current_date = date( 'YmdHis' );
		$product_appointments_args = array(
			'meta_query' => array(
				array(
					'key' => '_appointment_product_id',
					'value' => absint( $product_id ),
				),
			),
			'post_status' => get_wc_appointment_statuses( 'validate' ),
			'meta_query'    => array(
				'start_date'  => array(
					'key'     => '_appointment_start',
					'value'   => $current_date,
					'compare' => '>=',
				),
			),
		);
		$latest_product_appointments = WC_Appointments_Controller::get_appointments( $product_appointments_args );
		foreach ( $latest_product_appointments as $product_appointment ) {
			WC_Appointments_Controller::validate_appointment( $product_appointment );
		}
	}

	/**
	 * On before delete product hook remove the product from all appointments
	 *
	 * @param int $product_id The id of the product that we are deleting
	 */
	public function before_delete_product( $product_id ) {
		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $product_id > 0 && 'product' === get_post_type( $product_id ) ) {
			$product_appointments = WC_Appointments_Controller::get_appointments_for_product( $product_id, get_wc_appointment_statuses( 'validate' ) );
			// Loop appointable products is added to remove the product from it
			foreach ( $product_appointments as $product_appointment ) {
				delete_post_meta( $appointment->id, '_appointment_product_id' );
			}
		}
	}

	/**
	 * Add staff
	 */
	public function add_appointable_staff() {
		global $wpdb;

		header( 'Content-Type: application/json; charset=utf-8' );

		check_ajax_referer( 'add-appointable-staff', 'security' );

		$post_id			= intval( $_POST['post_id'] );
		$loop				= intval( $_POST['loop'] );
		$add_staff_id		= intval( $_POST['add_staff_id'] );
		$add_staff_name		= wc_clean( $_POST['add_staff_name'] );
		$product_staff		= $wpdb->get_var( $wpdb->prepare( "SELECT staff_id FROM {$wpdb->prefix}wc_appointment_relationships WHERE product_id = %d AND staff_id = %d;", $post_id, $add_staff_id ) );

		// Already addded, prompt error
		if ( isset( $product_staff ) ) {
			die( wp_json_encode( array( 'error' => __( 'The staff has already been linked to this product', 'woocommerce-appointments' ) ) ) );
		}

		// Add staff
		if ( ! $add_staff_id ) {
			die( wp_json_encode( array( 'error' => __( 'Unable to add staff', 'woocommerce-appointments' ) ) ) );
		} else {
			$staff_id = $add_staff_id;
		}

		// Return html
		if ( $staff_id ) {

			// Link staff to product
			$wpdb->insert(
				"{$wpdb->prefix}wc_appointment_relationships",
				array(
					'product_id'  => $post_id,
					'staff_id' => $staff_id,
					'sort_order'  => $loop,
				)
			);

			do_action( 'delete_appointment_staff_transients', $post_id );

			/*
			// set default availability quantity
			update_post_meta( $staff_id, 'qty', 1 );
			*/

			$staff = get_user_by( 'id', $staff_id );
			ob_start();
			include( 'views/html-appointment-staff-member.php' );
			die( wp_json_encode( array( 'html' => ob_get_clean() ) ) );
		}

		die( wp_json_encode( array( 'error' => __( 'Unable to add staff', 'woocommerce-appointments' ) ) ) );
	}

	/**
	 * Remove staff
	 * TO DO: you should revert post meta logic that is set in class-wc-appointments-admin.php on line 559-593 ????
	 */
	public function remove_appointable_staff() {
		global $wpdb;

		check_ajax_referer( 'delete-appointable-staff', 'security' );

		$post_id  = absint( $_POST['post_id'] );
		$staff_id = absint( $_POST['staff_id'] );

		$wpdb->delete(
			"{$wpdb->prefix}wc_appointment_relationships",
			array(
				'product_id'  => $post_id,
				'staff_id' => $staff_id,
			)
		);

		do_action( 'delete_appointment_staff_transients', $post_id );

		die();
	}

	/**
	 * Tweak product type options
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_appointment';
		return $options;
	}

	/**
	 * Add the appointment product type
	 */
	public function product_type_selector( $types ) {
		$types['appointment'] = __( 'Appointable product', 'woocommerce-appointments' );
		return $types;
	}

	/**
	 * Show the appointment tab
	 */
	public function add_tab() {
		include( 'views/html-appointment-tab.php' );
	}

	/**
	 * Show the appointment general view
	 */
	public function appointment_general() {
		global $post;
		$post_id = $post->ID;

		include( 'views/html-appointment-general.php' );
	}

	/**
	 * Show the appointment panels views
	 */
	public function appointment_panels() {
		global $post;
		$post_id = $post->ID;

		wp_enqueue_script( 'wc_appointments_writepanel_js' );

		include( 'views/html-appointment-staff.php' );
		include( 'views/html-appointment-availability.php' );
	}

	/**
	 * Add admin styles
	 */
	public function styles_and_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'wc_appointments_admin_styles', WC_APPOINTMENTS_PLUGIN_URL . '/assets/css/admin.css', true, WC_APPOINTMENTS_VERSION );

		wp_enqueue_script( 'wp-color-picker' );

		wp_register_script( 'wc_appointments_writepanel_js', WC_APPOINTMENTS_PLUGIN_URL . '/assets/js/writepanel' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_APPOINTMENTS_VERSION, true );

		$params = array(
			'i18n_remove_staff'		=> esc_js( __( 'Are you sure you want to remove this staff?', 'woocommerce-appointments' ) ),
			'nonce_delete_staff'	=> wp_create_nonce( 'delete-appointable-staff' ),
			'nonce_add_staff'		=> wp_create_nonce( 'add-appointable-staff' ),
			'nonce_staff_html'		=> wp_create_nonce( 'appointable-staff-html' ),

			'i18n_minutes'          => esc_js( __( 'minutes', 'woocommerce-appointments' ) ),
			'i18n_hours'           	=> esc_js( __( 'hours', 'woocommerce-appointments' ) ),
			'i18n_days'             => esc_js( __( 'days', 'woocommerce-appointments' ) ),

			'post'                  => get_the_ID(),
			'plugin_url'            => WC()->plugin_url(),
			'ajax_url'              => admin_url( 'admin-ajax.php' ),
			'calendar_image'        => WC()->plugin_url() . '/assets/images/calendar.png',
		);

		wp_localize_script( 'wc_appointments_writepanel_js', 'wc_appointments_writepanel_js_params', $params );
	}

	/**
	 * Save Appointment data for the product
	 *
	 * @param  int $post_id
	 */
	public function save_product_data( $post_id ) {
		global $wpdb;

		$product_type = empty( $_POST['product-type'] ) ? 'simple' : sanitize_title( stripslashes( $_POST['product-type'] ) );
		$has_additional_costs = false;

		if ( 'appointment' !== $product_type ) {
			return;
		}

		// Save meta.
		$meta_to_save = array(
			'_wc_appointment_has_price_label'            => '',
			'_wc_appointment_price_label'          		 => '',
			'_wc_appointment_has_pricing'				 => '',
			'_wc_appointment_qty'                        => 'int',
			'_wc_appointment_qty_min'                    => 'int',
			'_wc_appointment_qty_max'                    => 'int',
			'_wc_appointment_staff_assignment'			 => '',
			'_wc_appointment_duration'                   => 'int',
			'_wc_appointment_duration_unit'              => '',
			'_wc_appointment_interval'                   => 'int',
			'_wc_appointment_interval_unit'              => '',
			'_wc_appointment_padding_duration'           => 'int',
			'_wc_appointment_padding_duration_unit'      => '',
			'_wc_appointment_min_date'                   => 'int',
			'_wc_appointment_min_date_unit'              => '',
			'_wc_appointment_max_date'                   => 'max_date',
			'_wc_appointment_max_date_unit'              => 'max_date_unit',
			'_wc_appointment_user_can_cancel'            => '',
			'_wc_appointment_cancel_limit'               => 'int',
			'_wc_appointment_cancel_limit_unit'          => '',
			'_wc_appointment_cal_color'          		 => '',
			'_wc_appointment_requires_confirmation'      => 'yesno',
			'_wc_appointment_availability_span'       	 => '',
			'_wc_appointment_availability_autoselect'	 => 'yesno',
			'_wc_appointment_staff_label'                => '',
		);

		foreach ( $meta_to_save as $meta_key => $sanitize ) {
			$value = ! empty( $_POST[ $meta_key ] ) ? $_POST[ $meta_key ] : '';
			switch ( $sanitize ) {
				case 'int' :
					$value = $value ? absint( $value ) : '';
					break;
				case 'float' :
					$value = $value ? floatval( $value ) : '';
					break;
				case 'yesno' :
					$value = 'yes' === $value ? 'yes' : 'no';
					break;
				case 'issetyesno' :
					$value = $value ? 'yes' : 'no';
					break;
				case 'max_date' :
					$value = absint( $value );
					if ( 0 == $value ) {
						$value = 1;
					}
					break;
				default :
					$value = sanitize_text_field( $value );
			}
			update_post_meta( $post_id, $meta_key, $value );
		}

		// Availability.
		$availability = array();
		$row_size     = isset( $_POST['wc_appointment_availability_type'] ) ? count( $_POST['wc_appointment_availability_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST['wc_appointment_availability_type'][ $i ] );
			$availability[ $i ]['appointable'] = wc_clean( $_POST['wc_appointment_availability_appointable'][ $i ] );
			$availability[ $i ]['qty'] = wc_clean( $_POST['wc_appointment_availability_qty'][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
				break;
				case 'months' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_month'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_month'][ $i ] );
				break;
				case 'weeks' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_week'][ $i ] );
				break;
				case 'days' :
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_day_of_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_day_of_week'][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );
				break;
				case 'time:range' :
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );

					$availability[ $i ]['from_date'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to_date']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
				break;
			}
		}

		update_post_meta( $post_id, '_wc_appointment_availability', $availability );

		// Pricing.
		$pricing = array();
		$row_size     = isset( $_POST['wc_appointment_pricing_type'] ) ? count( $_POST['wc_appointment_pricing_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$pricing[ $i ]['type']          = wc_clean( $_POST['wc_appointment_pricing_type'][ $i ] );
			$pricing[ $i ]['cost']          = wc_clean( $_POST['wc_appointment_pricing_cost'][ $i ] );
			$pricing[ $i ]['modifier']      = wc_clean( $_POST['wc_appointment_pricing_cost_modifier'][ $i ] );
			$pricing[ $i ]['base_cost']     = wc_clean( $_POST['wc_appointment_pricing_base_cost'][ $i ] );
			$pricing[ $i ]['base_modifier'] = wc_clean( $_POST['wc_appointment_pricing_base_cost_modifier'][ $i ] );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_date'][ $i ] );
				break;
				case 'months' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_month'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_month'][ $i ] );
				break;
				case 'weeks' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_week'][ $i ] );
				break;
				case 'days' :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_day_of_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_day_of_week'][ $i ] );
				break;
				case 'time' :
				case 'time:1' :
				case 'time:2' :
				case 'time:3' :
				case 'time:4' :
				case 'time:5' :
				case 'time:6' :
				case 'time:7' :
					$pricing[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_to_time'][ $i ] );
				break;
				case 'time:range' :
					$pricing[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_to_time'][ $i ] );

					$pricing[ $i ]['from_date'] = wc_clean( $_POST['wc_appointment_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to_date']   = wc_clean( $_POST['wc_appointment_pricing_to_date'][ $i ] );
				break;
				default :
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to'][ $i ] );
				break;
			}

			if ( $pricing[ $i ]['cost'] > 0 ) {
				$has_additional_costs = true;
			}
		}

		update_post_meta( $post_id, '_wc_appointment_pricing', $pricing );

		// Staff.
		if ( isset( $_POST['staff_id'] ) ) {
			$staff_ids         = $_POST['staff_id'];
			$staff_menu_order  = $_POST['staff_menu_order'];
			$staff_base_cost   = $_POST['staff_cost'];
			$max_loop          = max( array_keys( $_POST['staff_id'] ) );
			$staff_base_costs  = array();

			for ( $i = 0; $i <= $max_loop; $i ++ ) {
				if ( ! isset( $staff_ids[ $i ] ) ) {
					continue;
				}

				$staff_id = absint( $staff_ids[ $i ] );

				$wpdb->update(
					"{$wpdb->prefix}wc_appointment_relationships",
					array(
						'sort_order'  => $staff_menu_order[ $i ],
					),
					array(
						'product_id'  => $post_id,
						'staff_id' => $staff_id,
					)
				);

				$staff_base_costs[ $staff_id ]  = wc_clean( $staff_base_cost[ $i ] );

				if ( $staff_base_cost[ $i ] > 0 ) {
					$has_additional_costs = true;
				}
			}

			update_post_meta( $post_id, '_staff_base_costs', $staff_base_costs );

		}

		update_post_meta( $post_id, '_has_additional_costs', ( $has_additional_costs ? 'yes' : 'no' ) );
		update_post_meta( $post_id, '_manage_stock', 'no' );

	}

	/**
	 * Reset the ics exporter timezone string cache.
	 *
	 * @return void
	 */
	public function reset_ics_exporter_timezone_cache() {
		if ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ) {
			wp_cache_delete( 'wc_appointments_timezone_string' );
		}
	}

	/**
	 * Duplicate a post
	 */
	public function woocommerce_duplicate_product( $new_post_id, $post ) {
		global $wpdb;

		$product = wc_get_product( $post->ID );

		if ( $product->is_type( 'appointment' ) ) {
			// Duplicate relationships
			$relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_appointment_relationships WHERE product_id = %d;", $post->ID ), ARRAY_A );
			if ( $relationships ) {
				foreach ( $relationships as $relationship ) {
					$relationship['product_id'] = $new_post_id;
					unset( $relationship['ID'] );
					$wpdb->insert( "{$wpdb->prefix}wc_appointment_relationships", $relationship );
				}
			}
		}
	}

	/**
	 * Add memberships settings page
	 *
	 * @since 1.0
	 * @param array $settings
	 * @return array
	 */
	public function add_settings_page( $settings ) {

		$settings[] = include( 'class-wc-appointments-admin-settings.php' );
		return $settings;
	}

	/**
	 * Support scanning for template overrides in extension.
	 *
	 * @param  array $paths
	 * @return array
	 */
	public function template_scan_path( $paths ) {
		$paths['WooCommerce Appointments'] = WC_APPOINTMENTS_TEMPLATE_PATH;

		return $paths;
	}

	/**
	 * Show a notice highlighting bad template files.
	 *
	 */
	public static function template_file_check_notice() {
		$core_templates = WC_Admin_Status::scan_template_files( WC_APPOINTMENTS_TEMPLATE_PATH );
		$outdated       = false;

		foreach ( $core_templates as $file ) {

			$theme_file = false;
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_template_directory() . '/woocommerce/' . $file;
			}

			if ( false !== $theme_file ) {
				$core_version  = WC_Admin_Status::get_file_version( WC_APPOINTMENTS_TEMPLATE_PATH . $file );
				$theme_version = WC_Admin_Status::get_file_version( $theme_file );

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$outdated = true;
					break;
				}
			}
		}

		if ( $outdated ) {
			$theme = wp_get_theme();

			WC_Admin_Notices::add_custom_notice(
				'wc_appointments_template_files',
				sprintf(
					__( '<p><strong>Your theme (%1$s) contains outdated copies of some WooCommerce Appointments template files.</strong> These files may need updating to ensure they are compatible with the current version of WooCommerce Appointments. You can see which files are affected from the <a href="%2$s">system status page</a>. If in doubt, check with the author of the theme.<p><p class="submit"><a class="button-primary" href="%3$s" target="_blank">Learn More About Templates</a></p>', 'woocommerce-appointments' ),
					esc_html( $theme['Name'] ),
					esc_url( admin_url( 'admin.php?page=wc-status' ) ),
					esc_url( 'https://docs.woocommerce.com/document/template-structure/' )
				)
			);
		} else {
			WC_Admin_Notices::remove_notice( 'wc_appointments_template_files' );
		}
	}
}

$GLOBALS['wc_appointments_admin'] = new WC_Appointments_Admin();
