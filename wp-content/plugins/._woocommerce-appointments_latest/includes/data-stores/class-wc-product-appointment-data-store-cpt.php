<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WC Bookable Product Data Store: Stored in CPT.
 *
 * @todo When 2.6 support is dropped, implement WC_Object_Data_Store_Interface
 */
class WC_Product_Appointment_Data_Store_CPT extends WC_Product_Data_Store_CPT {

	/**
	 * Meta keys and how they transfer to CRUD props.
	 *
	 * @var array
	 */
	private $appointment_meta_key_to_props = array(
		'_has_additional_costs'						=> 'has_additional_costs',
		'_wc_appointment_has_price_label'			=> 'has_price_label',
		'_wc_appointment_price_label'				=> 'price_label',
		'_wc_appointment_has_pricing'				=> 'has_pricing',
		'_wc_appointment_pricing'					=> 'pricing',
		'_wc_appointment_qty'						=> 'qty',
		'_wc_appointment_qty_min'					=> 'qty_min',
		'_wc_appointment_qty_max'					=> 'qty_max',
		'_wc_appointment_duration_unit'				=> 'duration_unit',
		'_wc_appointment_duration'					=> 'duration',
		'_wc_appointment_interval_unit'				=> 'interval_unit',
		'_wc_appointment_interval'					=> 'interval',
		'_wc_appointment_padding_duration_unit'		=> 'padding_duration_unit',
		'_wc_appointment_padding_duration'			=> 'padding_duration',
		'_wc_appointment_min_date_unit'				=> 'min_date_unit',
		'_wc_appointment_min_date'					=> 'min_date',
		'_wc_appointment_max_date_unit'				=> 'max_date_unit',
		'_wc_appointment_max_date'					=> 'max_date',
		'_wc_appointment_user_can_cancel'			=> 'user_can_cancel',
		'_wc_appointment_cancel_limit_unit'			=> 'cancel_limit_unit',
		'_wc_appointment_cancel_limit'				=> 'cancel_limit',
		'_wc_appointment_cal_color'					=> 'cal_color',
		'_wc_appointment_requires_confirmation'		=> 'requires_confirmation',
		'_wc_appointment_availability_span'			=> 'availability_span',
		'_wc_appointment_availability_autoselect'	=> 'availability_autoselect',
		'_wc_appointment_has_restricted_days'       => 'has_restricted_days',
		'_wc_appointment_restricted_days'           => 'restricted_days',
		'_wc_appointment_availability'				=> 'availability',
		'_wc_appointment_staff_label'				=> 'staff_label',
		'_wc_appointment_staff_assignment'			=> 'staff_assignment',
	);

	public function __construct() {
		if ( is_callable( 'parent::__construct' ) ) {
			parent::__construct();
		}

		$this->internal_meta_keys = array_merge( $this->internal_meta_keys, array_keys( $this->appointment_meta_key_to_props ) );
	}

	/**
	 * Method to create a new product in the database.
	 *
	 * @param WC_Product_Appointment $product
	 */
	public function create( &$product ) {

		// If we're not using 3.0.x we can only store meta data here.
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {

			$id = wp_insert_post( apply_filters( 'woocommerce_new_product_data', array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'post_title'     => $product->get_name() ? $product->get_name() : __( 'Product', 'woocommerce-appointments' ),
				'post_content'   => $product->get_description(),
				'post_excerpt'   => $product->get_short_description(),
				'post_parent'    => $product->get_parent_id(),
				'comment_status' => $product->get_reviews_allowed() ? 'open' : 'closed',
				'ping_status'    => 'closed',
				'menu_order'     => $product->get_menu_order(),
				'post_date'      => date( 'Y-m-d H:i:s', $product->get_date_created() ),
				'post_date_gmt'  => get_gmt_from_date( date( 'Y-m-d H:i:s', $product->get_date_created() ) ),
			) ), true );

			$product->set_id( $id );
			$this->update_post_meta( $product, true );
		} else {
			parent::create( $product );
		}
	}

	/**
	 * Method to read product data.
	 *
	 * @param WC_Product
	 */
	public function read( &$product ) {
		// If we're not using 3.0.x we can only store meta data here.
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$this->read_product_data( $product );
		} else {
			parent::read( $product );
		}
	}

	/**
	 * Method to update a product in the database.
	 *
	 * @param WC_Product
	 */
	public function update( &$product ) {
		// If we're not using 3.0.x we can only store meta data here.
		if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
			$this->update_post_meta( $product, true );
		} else {
			parent::update( $product );
		}
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @param WC_Product
	 * @param bool $force Force all props to be written even if not changed. This is used during creation.
	 * @since 3.0.0
	 */
	public function update_post_meta( &$product, $force = false ) {
		// Only call parent method if using full CRUD object as of 3.0.x.
		if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
			parent::update_post_meta( $product, $force );
		}

		foreach ( $this->appointment_meta_key_to_props as $key => $prop ) {
			if ( is_callable( array( $product, "get_$prop" ) ) ) {
				update_post_meta( $product->get_id(), $key, $product->{ "get_$prop" }( 'edit' ) );
			}
		}

		$this->update_staff( $product );
	}

	/**
	 * Read product data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Product
	 */
	public function read_product_data( &$product ) {
		// Only call parent method if using full CRUD object as of 3.0.x.
		if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
			parent::read_product_data( $product );
		}

		$set_props = array();

		foreach ( $this->appointment_meta_key_to_props as $key => $prop ) {
			if ( ! metadata_exists( 'post', $product->get_id(), $key ) ) {
				continue;
			}

			$value = get_post_meta( $product->get_id(), $key, true );
			$set_props[ $prop ] = $value;
		}

		$product->set_props( $set_props );
		$this->read_staff( $product );
	}

	/**
	 * Read staff from the database.
	 *
	 * @param WC_Product
	 */
	protected function read_staff( &$product ) {
		global $wpdb;

		$staff_ids = wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "
			SELECT users.ID
			FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
			LEFT JOIN $wpdb->users AS users ON users.ID = relationships.staff_id
			WHERE relationships.product_id = %d
			ORDER BY sort_order ASC
		", $product->get_id() ) ) );

		$product->set_staff_ids( $staff_ids );
		$product->set_staff_base_costs( get_post_meta( $product->get_id(), '_staff_base_costs', true ) );
		$product->set_staff_qtys( get_post_meta( $product->get_id(), '_staff_qtys', true ) );
	}

	/**
	 * Update staff.
	 *
	 * @param WC_Product
	 */
	protected function update_staff( &$product ) {
		global $wpdb;

		update_post_meta( $product->get_id(), '_staff_base_costs', $product->get_staff_base_costs( 'edit' ) );
		update_post_meta( $product->get_id(), '_staff_qtys', $product->get_staff_qtys( 'edit' ) );

		$index = 0;

		$current_staff_ids = $wpdb->get_results( $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->prefix}wc_appointment_relationships
			WHERE `product_id` = %d
			ORDER BY sort_order ASC
		", $product->get_id() ), ARRAY_A );

		$current_temp = array();
		foreach ( $current_staff_ids as $staff ) {
			$current_temp[ $staff['staff_id'] ] = $staff;
		}
		$current_staff_ids = $current_temp;

		foreach ( $product->get_staff_ids( 'edit' ) as $staff_id ) {

			$replace = array(
				'sort_order'  => ( $index ++ ),
				'product_id'  => $product->get_id(),
				'staff_id' => $staff_id,
			);

			if ( isset( $current_staff_ids[ $staff_id ] ) ) {
				$replace['ID'] = $current_staff_ids[ $staff_id ]['ID'];
				unset( $current_staff_ids[ $staff_id ] );
			}

			$wpdb->replace(
				"{$wpdb->prefix}wc_appointment_relationships",
				$replace
			);
		}

		if ( ! empty( $current_staff_ids ) ) {
			foreach ( $current_staff_ids as $staff ) {
				$wpdb->delete(
					"{$wpdb->prefix}wc_appointment_relationships",
					array(
						'ID' => $staff['ID'],
					)
				);
			}
		}
	}

	/**
	 * Remove staff from product by staff ID and product ID.
	 *
	 * @param int $staff_id staff ID (user ID).
	 * @param int $product_id product ID (post ID).
	 * @return void.
	 */
	public static function remove_staff_from_product( $staff_id, $product_id ) {
		global $wpdb;

		// Remove from the relationships table.
		$wpdb->delete(
			"{$wpdb->prefix}wc_appointment_relationships",
			array(
				'product_id' => $product_id,
				'staff_id' => $staff_id,
			)
		);

		// Get any staff left from the relationships table for the product and update its data and revert the relational db table and post meta logic that is set in class-wc-appointments-admin.php on line 559-593.
		$product_staff_left = $wpdb->get_results(
			$wpdb->prepare( "
				SELECT staff_id
				FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
				WHERE relationships.product_id = %d
				ORDER BY sort_order ASC
			", $product_id )
		);

		if ( ! empty( $product_staff_left ) ) {
			$max_loop = max( array_keys( $product_staff_left ) );
			$staff_base_costs  = array();
			$staff_qtys  = array();

			for ( $i = 0; $i <= $max_loop; $i ++ ) {

				$staff_id = absint( $product_staff_left[ $i ]->staff_id );
				// Update the sort order after the delete of the rows.
				$wpdb->update(
					"{$wpdb->prefix}wc_appointment_relationships",
					array(
						'sort_order'  => $i,
					),
					array(
						'product_id'  => $product_id,
						'staff_id' => $staff_id,
					)
				);
			}
		}
	}

	/**
	 * Get staff products by staff ID.
	 *
	 * @param int $staff_id staff ID (user ID).
	 * @return array Product object.
	 */
	public static function get_appointable_product_ids_for_staff( $staff_id ) {
		global $wpdb;

		$ids = array();

		if ( ! $staff_id ) {
			return $ids;
		}

		// Only get products that exist in posts table.
		$ids = wp_parse_id_list( $wpdb->get_col( $wpdb->prepare( "
			SELECT posts.ID
			FROM {$wpdb->prefix}wc_appointment_relationships AS relationships
			LEFT JOIN $wpdb->posts AS posts ON posts.ID = relationships.product_id
			WHERE relationships.staff_id = %d AND posts.ID != ''
			ORDER BY sort_order ASC
		", $staff_id ) ) );

		return $ids;
	}

	/**
	 * Get all appointment products.
	 *
	 * @return array
	 */
	public static function get_appointable_product_ids( $show_all = false ) {
		$args = apply_filters( 'get_appointment_products_args', array(
			'post_status'      => 'publish',
			'post_type'        => 'product',
			'posts_per_page'   => -1,
			'tax_query'        => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'appointment',
				),
			),
			'suppress_filters' => true,
			'fields'           => 'ids',
		) );

		// Get products, where current user is author.
		if ( ! current_user_can( 'manage_others_appointments' ) && ! $show_all ) {
			$args['author'] = get_current_user_id();
		}

		$posts_query = new WP_Query();
	    $ids = $posts_query->query( $args );

		// Get products, where current user assigned as staff member.
		if ( ! current_user_can( 'manage_others_appointments' ) && ! $show_all ) {
			$staff_product_ids = WC_Data_Store::load( 'product-appointment' )->get_appointable_product_ids_for_staff( get_current_user_id() );

			// Merge Products where current user is author with
			// products, where current user is assigned as staff member.
			$ids = array_merge( (array) $ids, (array) $staff_product_ids );
		}

		return wp_parse_id_list( $ids );
	}
}
