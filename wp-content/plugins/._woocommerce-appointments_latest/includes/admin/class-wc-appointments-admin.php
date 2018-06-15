<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main WC_Appointments_Admin class.
 */
class WC_Appointments_Admin {

	private static $_this;

	/**
	 * Constructor
	 */
	public function __construct() {
		self::$_this = $this;

		add_action( 'init', array( $this, 'init' ) );

		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_action( 'admin_init', array( $this, 'init_tabs' ) );
		add_action( 'admin_init', array( $this, 'include_post_type_handlers' ) );
		add_action( 'admin_init', array( $this, 'include_meta_box_handlers' ) );
		add_action( 'admin_init', array( $this, 'redirect_new_add_appointment_url' ) );
		add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'appointment_inventory' ) );
		add_filter( 'product_type_options', array( $this, 'product_type_options' ) );
		add_filter( 'product_type_selector' , array( $this, 'product_type_selector' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'styles_and_scripts' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'appointment_general' ) );
		add_action( 'load-options-general.php', array( $this, 'reset_ics_exporter_timezone_cache' ) );
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'appointment_display' ), 10, 3 );

		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ), 10, 1 );
		add_filter( 'woocommerce_template_overrides_scan_paths', array( $this, 'template_scan_path' ) );
		add_filter( 'set-screen-option', array( $this, 'calendar_set_option' ), 10, 3 );
		add_filter( 'screen_settings', array( $this, 'calendar_show_screen_options' ), 10, 2 );
		add_filter( 'woocommerce_appointments_calendar_view_by_staff', array( $this, 'calendar_view_by_staff' ) );

		// Saving data.
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ), 20 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'set_props' ), 20 );
		add_action( 'before_delete_post', array( $this, 'before_delete_product' ), 11 );

		include( 'class-wc-appointments-admin-menus.php' );
		include( 'class-wc-appointments-admin-staff-profile.php' );
	}

	public function init() {
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			add_action( 'woocommerce_duplicate_product', array( $this, 'woocommerce_duplicate_product_pre_wc30' ), 10, 2 );
		} else {
			add_action( 'woocommerce_product_duplicate', array( $this, 'woocommerce_duplicate_product' ), 10, 2 );
		}
	}

	/**
	 * Save Appointment data for the product in 2.6.x.
	 *
	 * @param int $post_id
	 */
	public function save_product_data( $post_id ) {
		if ( version_compare( WC_VERSION, '3.0', '>=' ) || 'appointment' !== sanitize_title( stripslashes( $_POST['product-type'] ) ) ) {
			return;
		}
		$product = new WC_Product_Appointment( $post_id );
		$this->set_props( $product );
		$product->save();
	}

	/**
	 * Get posted availability fields and format.
	 *
	 * @return array
	 */
	private function get_posted_availability() {
		$availability = array();
		$row_size     = isset( $_POST['wc_appointment_availability_type'] ) ? sizeof( $_POST['wc_appointment_availability_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$availability[ $i ]['type']     = wc_clean( $_POST['wc_appointment_availability_type'][ $i ] );
			$availability[ $i ]['appointable'] = wc_clean( $_POST['wc_appointment_availability_appointable'][ $i ] );
			$availability[ $i ]['qty'] = wc_clean( $_POST['wc_appointment_availability_qty'][ $i ] );

			switch ( $availability[ $i ]['type'] ) {
				case 'custom':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
					break;
				case 'months':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_month'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_month'][ $i ] );
					break;
				case 'weeks':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_week'][ $i ] );
					break;
				case 'days':
					$availability[ $i ]['from'] = wc_clean( $_POST['wc_appointment_availability_from_day_of_week'][ $i ] );
					$availability[ $i ]['to']   = wc_clean( $_POST['wc_appointment_availability_to_day_of_week'][ $i ] );
					break;
				case 'time':
				case 'time:1':
				case 'time:2':
				case 'time:3':
				case 'time:4':
				case 'time:5':
				case 'time:6':
				case 'time:7':
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );
					break;
				case 'time:range':
					$availability[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_from_time'][ $i ] );
					$availability[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_availability_to_time'][ $i ] );

					$availability[ $i ]['from_date'] = wc_clean( $_POST['wc_appointment_availability_from_date'][ $i ] );
					$availability[ $i ]['to_date']   = wc_clean( $_POST['wc_appointment_availability_to_date'][ $i ] );
					break;
			}
		}
		return $availability;
	}

	/**
	 * Get posted pricing fields and format.
	 *
	 * @return array
	 */
	private function get_posted_pricing() {
		$pricing = array();
		$row_size     = isset( $_POST['wc_appointment_pricing_type'] ) ? sizeof( $_POST['wc_appointment_pricing_type'] ) : 0;
		for ( $i = 0; $i < $row_size; $i ++ ) {
			$pricing[ $i ]['type']          = wc_clean( $_POST['wc_appointment_pricing_type'][ $i ] );
			$pricing[ $i ]['cost']          = wc_clean( $_POST['wc_appointment_pricing_cost'][ $i ] );
			$pricing[ $i ]['modifier']      = wc_clean( $_POST['wc_appointment_pricing_cost_modifier'][ $i ] );
			$pricing[ $i ]['base_cost']     = wc_clean( $_POST['wc_appointment_pricing_base_cost'][ $i ] );
			$pricing[ $i ]['base_modifier'] = wc_clean( $_POST['wc_appointment_pricing_base_cost_modifier'][ $i ] );

			switch ( $pricing[ $i ]['type'] ) {
				case 'custom':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_date'][ $i ] );
					break;
				case 'months':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_month'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_month'][ $i ] );
					break;
				case 'weeks':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_week'][ $i ] );
					break;
				case 'days':
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from_day_of_week'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to_day_of_week'][ $i ] );
					break;
				case 'time':
				case 'time:1':
				case 'time:2':
				case 'time:3':
				case 'time:4':
				case 'time:5':
				case 'time:6':
				case 'time:7':
					$pricing[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_to_time'][ $i ] );
					break;
				case 'time:range':
					$pricing[ $i ]['from'] = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_from_time'][ $i ] );
					$pricing[ $i ]['to']   = wc_appointment_sanitize_time( $_POST['wc_appointment_pricing_to_time'][ $i ] );

					$pricing[ $i ]['from_date'] = wc_clean( $_POST['wc_appointment_pricing_from_date'][ $i ] );
					$pricing[ $i ]['to_date']   = wc_clean( $_POST['wc_appointment_pricing_to_date'][ $i ] );
					break;
				default:
					$pricing[ $i ]['from'] = wc_clean( $_POST['wc_appointment_pricing_from'][ $i ] );
					$pricing[ $i ]['to']   = wc_clean( $_POST['wc_appointment_pricing_to'][ $i ] );
					break;
			}
		}
		return $pricing;
	}

	/**
	 * Get posted staff. Staffs are global, but appointment products store information about the relationship.
	 *
	 * @return array
	 */
	private function get_posted_staff( $product ) {
		$staff = array();

		if ( isset( $_POST['staff_id'] ) ) {
			$staff_ids         = $_POST['staff_id'];
			$staff_menu_order  = $_POST['staff_menu_order'];
			$staff_base_cost   = $_POST['staff_cost'];
			$staff_qty         = $_POST['staff_qty'];
			$max_loop          = max( array_keys( $_POST['staff_id'] ) );
			$staff_base_costs  = array();
			$staff_qtys  = array();

			foreach ( $staff_menu_order as $key => $value ) {
				$staff[ absint( $staff_ids[ $key ] ) ] = array(
					'base_cost'  => wc_clean( $staff_base_cost[ $key ] ),
					'qty'        => wc_clean( $staff_qty[ $key ] ),
				);
			}
		}

		return $staff;
	}

	/**
	 * Set data in 3.0.x
	 *
	 * @version  3.3.0
	 * @param    WC_Product $product
	 */
	public function set_props( $product ) {
		// Only set props if the product is a appointable product.
		if ( ! is_a( $product, 'WC_Product_Appointment' ) ) {
			return;
		}

		$staff = $this->get_posted_staff( $product );
		$product->set_props( array(
			'has_price_label' 				=> isset( $_POST['_wc_appointment_has_price_label'] ),
			'price_label'					=> wc_clean( $_POST['_wc_appointment_price_label'] ),
			'has_pricing'	 				=> isset( $_POST['_wc_appointment_has_pricing'] ),
			'pricing'						=> $this->get_posted_pricing(),
			'qty'							=> wc_clean( $_POST['_wc_appointment_qty'] ),
			'qty_min'						=> wc_clean( $_POST['_wc_appointment_qty_min'] ),
			'qty_max'						=> wc_clean( $_POST['_wc_appointment_qty_max'] ),
			'duration_unit'					=> wc_clean( $_POST['_wc_appointment_duration_unit'] ),
			'duration'						=> wc_clean( $_POST['_wc_appointment_duration'] ),
			'interval_unit'					=> wc_clean( $_POST['_wc_appointment_interval_unit'] ),
			'interval'						=> wc_clean( $_POST['_wc_appointment_interval'] ),
			'padding_duration_unit'			=> wc_clean( $_POST['_wc_appointment_padding_duration_unit'] ),
			'padding_duration'				=> wc_clean( $_POST['_wc_appointment_padding_duration'] ),
			'min_date_unit'					=> wc_clean( $_POST['_wc_appointment_min_date_unit'] ),
			'min_date'						=> wc_clean( $_POST['_wc_appointment_min_date'] ),
			'max_date_unit'					=> wc_clean( $_POST['_wc_appointment_max_date_unit'] ),
			'max_date'						=> wc_clean( $_POST['_wc_appointment_max_date'] ),
			'user_can_cancel'				=> isset( $_POST['_wc_appointment_user_can_cancel'] ),
			'cancel_limit_unit'				=> wc_clean( $_POST['_wc_appointment_cancel_limit_unit'] ),
			'cancel_limit'					=> wc_clean( $_POST['_wc_appointment_cancel_limit'] ),
			'cal_color'						=> wc_clean( $_POST['_wc_appointment_cal_color'] ),
			'requires_confirmation'			=> isset( $_POST['_wc_appointment_requires_confirmation'] ),
			'availability_span'  			=> wc_clean( $_POST['_wc_appointment_availability_span'] ),
			'availability_autoselect'		=> isset( $_POST['_wc_appointment_availability_autoselect'] ),
			'has_restricted_days'        	=> isset( $_POST['_wc_appointment_has_restricted_days'] ),
			'restricted_days'            	=> isset( $_POST['_wc_appointment_restricted_days'] ) ? wc_clean( $_POST['_wc_appointment_restricted_days'] ) : '',
			'availability'               	=> $this->get_posted_availability(),
			'staff_label'					=> wc_clean( $_POST['_wc_appointment_staff_label'] ),
			'staff_ids'						=> array_keys( $staff ),
			'staff_base_costs'				=> wp_list_pluck( $staff, 'base_cost' ),
			'staff_qtys'					=> wp_list_pluck( $staff, 'qty' ),
			'staff_assignment'				=> wc_clean( $_POST['_wc_appointment_staff_assignment'] ),
		) );
	}

	/**
	 * Init product edit tabs.
	 */
	public function init_tabs() {
		if ( version_compare( WC_VERSION, '2.6', '<' ) ) {
			add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'add_tab' ), 5 );
			add_action( 'woocommerce_product_write_panels', array( $this, 'appointment_panels' ) );
		} else {
			add_filter( 'woocommerce_product_data_tabs', array( $this, 'register_tab' ), 5 );
			add_action( 'woocommerce_product_data_panels', array( $this, 'appointment_panels' ) );
		}
	}

	/**
	 * Add tabs to WC 2.6+
	 *
	 * @param  array $tabs
	 * @return array
	 */
	public function register_tab( $tabs ) {
		$tabs['appointments_staff'] = array(
			'label'    => __( 'Staff', 'woocommerce-appointments' ),
			'target'   => 'appointments_staff',
			'class'    => array(
				'show_if_appointment',
			),
			'priority' => 25,
		);
		$tabs['appointments_availability'] = array(
			'label'    => __( 'Availability', 'woocommerce-appointments' ),
			'target'   => 'appointments_availability',
			'class'    => array(
				'show_if_appointment',
			),
			'priority' => 25,
		);

		// Inventory
		$tabs['inventory']['class'][] = 'show_if_appointment';

		return $tabs;
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
	 * Duplicate a post.
	 *
	 * @param  int     $new_post_id Duplicated product ID.
	 * @param  WP_Post $post        Original product post.
	 */
	public function woocommerce_duplicate_product_pre_wc_30( $new_post_id, $post ) {
		$product = wc_get_product( $post->ID );

		if ( $product->is_type( 'appointment' ) ) {
			global $wpdb;
			// Duplicate relationships
			$relationships = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wc_appointment_relationships WHERE product_id = %d;", $post->ID ), ARRAY_A );
			foreach ( $relationships as $relationship ) {
				$relationship['product_id'] = $new_post_id;
				unset( $relationship['ID'] );
				$wpdb->insert( "{$wpdb->prefix}wc_appointment_relationships", $relationship );
			}

			// Clone and re-save person types.
			foreach ( $product->get_person_types() as $person_type ) {
				$dupe_person_type = clone $person_type;
				$dupe_person_type->set_id( 0 );
				$dupe_person_type->set_parent_id( $new_post_id );
				$dupe_person_type->save();
			}
		}
	}

	/**
	 * Duplicate a post.
	 *
	 * @param  WC_Product $new_product Duplicated product.
	 * @param  WC_Product $product     Original product.
	 */
	public function woocommerce_duplicate_product( $new_product, $product ) {
		if ( $product->is_type( 'appointment' ) ) {
			// Clone and re-save person types.
			/*
			foreach ( $product->get_person_types() as $person_type ) {
				$dupe_person_type = clone $person_type;
				$dupe_person_type->set_id( 0 );
				$dupe_person_type->set_parent_id( $new_product->get_id() );
				$dupe_person_type->save();
			}
			*/
		}
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
	 * Show appointment data if a line item is linked to a appointment ID.
	 */
	public function appointment_display( $item_id, $item, $product ) {
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_item_id( $item_id );

		wc_get_template( 'order/admin/appointment-display.php', array( 'appointment_ids' => $appointment_ids ), '', WC_APPOINTMENTS_TEMPLATE_PATH );
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
	 * Get appointment products.
	 *
	 * @return array
	 */
	public static function get_appointment_products( $show_all = false ) {
		$ids               		= WC_Data_Store::load( 'product-appointment' )->get_appointable_product_ids( $show_all );
		$appointable_products 	= array();

		if ( ! $ids ) {
			return $appointable_products;
		}

		foreach ( $ids as $id ) {
			$appointable_products[] = new WC_Product_Appointment( $id );
		}

		return $appointable_products;
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
				delete_post_meta( $product_appointment->get_id(), '_appointment_product_id' );
			}
		}
	}

	/**
	 * Show the appointment inventory view
	 */
	public function appointment_inventory() {
		global $post, $appointable_product;

		if ( empty( $appointable_product ) || $appointable_product->get_id() !== $post->ID ) {
			$appointable_product = new WC_Product_Appointment( $post->ID );
		}

		include( 'views/html-appointment-inventory.php' );
	}

	/**
	 * Tweak product type options
	 * @param  array $options
	 * @return array
	 */
	public function product_type_options( $options ) {
		$options['virtual']['wrapper_class'] .= ' show_if_appointment';
		$options['downloadable']['wrapper_class'] .= ' show_if_appointment';

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
		global $post, $appointable_product;

		if ( empty( $appointable_product ) || $appointable_product->get_id() !== $post->ID ) {
			$appointable_product = new WC_Product_Appointment( $post->ID );
		}

		include( 'views/html-appointment-general.php' );
	}

	/**
	 * Show the appointment panels views
	 */
	public function appointment_panels() {
		global $post, $appointable_product;

		if ( empty( $appointable_product ) || $appointable_product->get_id() !== $post->ID ) {
			$appointable_product = new WC_Product_Appointment( $post->ID );
		}

		$restricted_meta = $appointable_product->get_restricted_days();

		for ( $i = 0; $i < 7; $i++ ) {

			if ( $restricted_meta && in_array( $i, $restricted_meta ) ) {
				$restricted_days[ $i ] = $i;
			} else {
				$restricted_days[ $i ] = false;
			}
		}

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

		// Remove ACF plugin's timepicker scripts.
		if ( 'wc_appointment' == get_post_type() ) {
			wp_deregister_script( 'acf-timepicker' );
			wp_dequeue_style( 'acf-timepicker' );
    	}

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
			'fistday'				=> absint( get_option( 'start_of_week', 1 ) ),
			'calendar_image'        => WC()->plugin_url() . '/assets/images/calendar.png',
		);

		wp_localize_script( 'wc_appointments_writepanel_js', 'wc_appointments_writepanel_js_params', $params );
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
