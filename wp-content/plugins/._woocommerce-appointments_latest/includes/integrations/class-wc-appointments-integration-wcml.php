<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Multilingual (WPML) integration class.
 *
 * Last compatibility check: WooCommerce Multilingual 4.2.7.1
 */
class WC_Appointments_Integration_WCML {

    /**
	 * @var WPML_Element_Translation_Package
	 */
	private $tp;

	/**
	 * @var SitePress
	 */
	private $sitepress;

	/**
	 * @var woocommerce_wpml
	 */
	private $woocommerce_wpml;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * WCML_Appointments constructor.
	 * @param SitePress $sitepress
	 * @param woocommerce_wpml $woocommerce_wpml
	 * @param wpdb $wpdb
	 * @param WPML_Element_Translation_Package $tp
	 */
	function __construct() {
        global $sitepress, $woocommerce_wpml, $wpdb;

		$this->sitepress        = $sitepress;
		$this->woocommerce_wpml = $woocommerce_wpml;
		$this->wpdb             = $wpdb;
		$this->tp               = new WPML_Element_Translation_Package();

        $this->add_hooks();
	}

	public function add_hooks(){
		add_action( 'woocommerce_appointments_after_appointment_pricing_base_cost', array(
			$this,
			'wcml_price_field_after_appointment_pricing_base_cost'
		), 10, 2 );
		add_action( 'woocommerce_appointments_after_appointment_pricing_cost', array(
			$this,
			'wcml_price_field_after_appointment_pricing_cost'
		), 10, 2 );
		add_action( 'woocommerce_appointments_after_staff_cost', array(
			$this,
			'wcml_price_field_after_staff_cost'
		), 10, 2 );
		add_action( 'woocommerce_appointments_after_staff_qty', array(
			$this,
			'wcml_price_field_after_staff_qty'
		), 10, 2 );
		add_action( 'woocommerce_appointments_after_appointments_pricing', array(
			$this,
			'after_appointments_pricing'
		) );

		add_action( 'save_post', array( $this, 'save_custom_costs' ), 110, 1 );
		add_action( 'wcml_before_sync_product_data', array( $this, 'sync_appointments' ), 10, 3 );
		add_action( 'wcml_before_sync_product', array( $this, 'sync_appointment_data' ), 10, 2 );

		add_filter( 'woocommerce_appointments_process_pricing_rules_cost', array(
			$this,
			'wc_appointments_process_pricing_rules_cost'
		), 10, 3 );
		add_filter( 'woocommerce_appointments_process_pricing_rules_base_cost', array(
			$this,
			'wc_appointments_process_pricing_rules_base_cost'
		), 10, 3 );
		add_filter( 'woocommerce_appointments_process_pricing_rules_override_slot', array(
			$this,
			'wc_appointments_process_pricing_rules_override_slot_cost'
		), 10, 3 );

		add_filter( 'wcml_multi_currency_ajax_actions', array( $this, 'wcml_multi_currency_is_ajax' ) );

		add_filter( 'wcml_cart_contents_not_changed', array(
			$this,
			'filter_bundled_product_in_cart_contents'
		), 10, 3 );

		add_action( 'woocommerce_appointments_after_create_appointment_page', array( $this, 'appointment_currency_dropdown' ) );
		add_action( 'init', array( $this, 'set_appointment_currency' ) );

		add_action( 'wp_ajax_wcml_appointment_set_currency', array( $this, 'set_appointment_currency_ajax' ) );
		add_action( 'woocommerce_appointments_create_appointment_page_add_order_item', array(
			$this,
			'set_order_currency_on_create_appointment_page'
		) );
		add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_appointment_currency_symbol' ) );
		add_filter( 'get_appointment_products_args', array( $this, 'filter_get_appointment_products_args' ) );
		add_filter( 'wcml_filter_currency_position', array( $this, 'create_appointment_page_client_currency' ) );

		add_filter( 'wcml_client_currency', array( $this, 'create_appointment_page_client_currency' ) );

		#add_action( 'wcml_gui_additional_box_html', array( $this, 'custom_box_html' ), 10, 3 );
		#add_filter( 'wcml_gui_additional_box_data', array( $this, 'custom_box_html_data' ), 10, 4 );
		add_filter( 'wcml_check_is_single', array( $this, 'show_custom_slots_for_staff' ), 10, 3 );
		add_filter( 'wcml_do_not_display_custom_fields_for_product', array( $this, 'replace_tm_editor_custom_fields_with_own_sections' ) );
		add_filter( 'wcml_not_display_single_fields_to_translate', array(
			$this,
			'remove_single_custom_fields_to_translate'
		) );
		add_filter( 'wcml_product_content_label', array( $this, 'product_content_staff_label' ), 10, 2 );
		add_action( 'wcml_update_extra_fields', array( $this, 'wcml_products_tab_sync_staff' ), 10, 4 );

		add_action( 'woocommerce_new_appointment', array( $this, 'duplicate_appointment_for_translations' ) );

		$appointments_statuses = array( 'unpaid', 'pending-confirmation', 'confirmed', 'paid', 'cancelled', 'complete', 'in-cart', 'was-in-cart' );
		foreach ( $appointments_statuses as $status ) {
			add_action( 'woocommerce_appointment_' . $status, array( $this, 'update_status_for_translations' ) );
		}

		add_filter( 'parse_query', array( $this, 'appointment_filters_query' ) );
		add_filter( 'woocommerce_appointments_in_date_range_query', array( $this, 'appointments_in_date_range_query' ) );
		add_action( 'before_delete_post', array( $this, 'delete_appointments' ) );
		add_action( 'wp_trash_post', array( $this, 'trash_appointments' ) );

		if ( is_admin() ) {
            add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_staff_to_translation_package' ), 10, 2 );
            #add_action( 'wpml_translation_job_saved',   array( $this, 'save_staff_translation' ), 10, 3 );

            // lock fields on translations pages
            add_filter( 'wcml_js_lock_fields_ids', array( $this, 'wcml_js_lock_fields_ids' ) );

			// Allow filtering staff by language
			add_filter( 'get_appointment_staff_args', array( $this, 'filter_get_appointment_staff_args' ) );

			if ( $this->sitepress->get_wp_api()->version_compare( $this->sitepress->get_wp_api()->constant( 'ICL_SITEPRESS_VERSION' ), '3.8.0', '<' ) ) {
				add_filter( 'get_translatable_documents', array( $this, 'filter_translatable_documents' ) );

				//@TODO review after WPML 3.6
				if ( $this->sitepress->get_wp_api()->version_compare( $this->sitepress->get_wp_api()->constant( 'ICL_SITEPRESS_VERSION' ), '3.6', '<' ) ) {
					add_action( 'added_post_meta', array(
						$this,
						'maybe_fix_double_serialized_wc_appointment_availability'
					), 10, 4 );
				}
			}
			add_filter( 'get_translatable_documents_all', array( $this, 'filter_translatable_documents' ) );

			add_filter( 'pre_wpml_is_translated_post_type', array( $this, 'filter_is_translated_post_type' ) );
			add_action( 'save_post', array( $this, 'sync_appointment_status' ), 10, 3 );
		}

		if ( ! is_admin() || isset( $_POST['action'] ) && $_POST['action'] == 'wc_appointments_calculate_costs' ) {
			add_filter( 'get_post_metadata', array( $this, 'filter_wc_appointment_cost' ), 10, 4 );
		}

		add_filter( 'wpml_language_filter_extra_conditions_snippet', array( $this, 'extra_conditions_to_filter_appointments' ) );

		$this->clear_transient_fields();

		add_filter( 'wpml_tm_dashboard_translatable_types', array(	$this, 'hide_appointments_type_on_tm_dashboard'	) );

		add_filter( 'woocommerce_appointments_account_tables', array( $this, 'filter_my_account_appointments_tables_by_current_language' ) );

		add_filter( 'wc_apointments_check_appointment_product', array( $this, 'filter_check_appointment_product' ), 1, 2 );

	}

	function wcml_price_field_after_appointment_pricing_base_cost( $pricing, $post_id ) {

		$this->echo_wcml_price_field( $post_id, 'wcml_wc_appointment_pricing_base_cost', $pricing );

	}

	function wcml_price_field_after_appointment_pricing_cost( $pricing, $post_id ) {

		$this->echo_wcml_price_field( $post_id, 'wcml_wc_appointment_pricing_cost', $pricing );

	}

	function wcml_price_field_after_staff_cost( $staff_id, $post_id ) {

		$this->echo_wcml_price_field( $post_id, 'wcml_wc_appointment_staff_cost', false, true, $staff_id );

	}

	function wcml_price_field_after_staff_qty( $staff_id, $post_id ) {

		$this->echo_wcml_price_field( $post_id, 'wcml_wc_appointment_staff_qty', false, true, $staff_id );

	}

	function echo_wcml_price_field( $post_id, $field, $pricing = false, $check = true, $staff_id = false ) {

		if ( ( ! $check || $this->woocommerce_wpml->products->is_original_product( $post_id ) ) && $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

			$currencies = $this->woocommerce_wpml->multi_currency->get_currencies();

			$wc_currencies = get_woocommerce_currencies();

			echo '<div class="wcml_custom_cost_field" >';

			foreach ( $currencies as $currency_code => $currency ) {

				switch ( $field ) {

					case 'wcml_wc_appointment_pricing_base_cost':

						if ( isset( $pricing[ 'base_cost_' . $currency_code ] ) ) {
							$value = $pricing[ 'base_cost_' . $currency_code ];
						} else {
							$value = '';
						}

						echo '<div class="wcml_appointments_range_slot" >';
						echo '<label>' . get_woocommerce_currency_symbol( $currency_code ) . '</label>';
						echo '<input type="number" step="0.01" name="wcml_wc_appointment_pricing_base_cost[' . $currency_code . '][]" class="wcml_appointments_custom_price" value="' . $value . '" placeholder="0" />';
						echo '</div>';
						break;

					case 'wcml_wc_appointment_pricing_cost':

						if ( isset( $pricing[ 'cost_' . $currency_code ] ) ) {
							$value = $pricing[ 'cost_' . $currency_code ];
						} else {
							$value = '';
						}

						echo '<div class="wcml_appointments_range_slot" >';
						echo '<label>' . get_woocommerce_currency_symbol( $currency_code ) . '</label>';
						echo '<input type="number" step="0.01" name="wcml_wc_appointment_pricing_cost[' . $currency_code . '][]" class="wcml_appointments_custom_price" value="' . $value . '" placeholder="0" />';
						echo '</div>';
						break;

					case 'wcml_wc_appointment_staff_cost':

						$staff_base_costs = maybe_unserialize( get_post_meta( $post_id, '_staff_base_costs', true ) );

						if ( isset( $staff_base_costs['custom_costs'][ $currency_code ][ $staff_id ] ) ) {
							$value = $staff_base_costs['custom_costs'][ $currency_code ][ $staff_id ];
						} else {
							$value = '';
						}

						echo '<div class="wcml_appointments_staff_slot" >';
						echo '<label>' . get_woocommerce_currency_symbol( $currency_code ) . '</label>';
						echo '<input type="number" step="0.01" name="wcml_wc_appointment_staff_cost[' . $staff_id . '][' . $currency_code . ']" class="wcml_appointments_custom_price" value="' . $value . '" placeholder="0" />';
						echo '</div>';
						break;

					default:
						break;

				}

			}

			echo '</div>';

		}
	}

	function after_appointments_pricing( $post_id ) {

		if ( in_array( 'appointment', wp_get_post_terms( $post_id, 'product_type', array( "fields" => "names" ) ) )
		     && $this->woocommerce_wpml->products->is_original_product( $post_id )
			 && $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

			$custom_costs_status = get_post_meta( $post_id, '_wcml_custom_costs_status', true );

			$checked = ! $custom_costs_status ? 'checked="checked"' : ' ';

			echo '<div class="wcml_custom_costs">';

			echo '<input type="radio" name="_wcml_custom_costs" id="wcml_custom_costs_auto" value="0" class="wcml_custom_costs_input" ' . $checked . ' />';
			echo '<label for="wcml_custom_costs_auto">' . __( 'Calculate costs in other currencies automatically', 'woocommerce-multilingual' ) . '</label>';

			$checked = $custom_costs_status == 1 ? 'checked="checked"' : ' ';

			echo '<input type="radio" name="_wcml_custom_costs" value="1" id="wcml_custom_costs_manually" class="wcml_custom_costs_input" ' . $checked . ' />';
			echo '<label for="wcml_custom_costs_manually">' . __( 'Set costs in other currencies manually', 'woocommerce-multilingual' ) . '</label>';

			wp_nonce_field( 'wcml_save_custom_costs', '_wcml_custom_pricing_nonce' );

			echo '</div>';
		}

	}

	function save_custom_costs( $post_id ) {
		$nonce = filter_var( isset( $_POST['_wcml_custom_pricing_nonce'] ) ? $_POST['_wcml_custom_pricing_nonce'] : '', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( isset( $_POST['_wcml_custom_costs'] ) && isset( $nonce ) && wp_verify_nonce( $nonce, 'wcml_save_custom_costs' ) ) {

			update_post_meta( $post_id, '_wcml_custom_costs_status', $_POST['_wcml_custom_costs'] );

			if ( 1 === (int) $_POST['_wcml_custom_costs'] ) {

				$currencies = $this->woocommerce_wpml->multi_currency->get_currencies();
				if ( empty( $currencies ) || 0 === $post_id ) {
					return false;
				}

				$this->update_appointment_pricing( $currencies, $post_id );

				if ( isset( $_POST['wcml_wc_appointment_staff_cost'] ) && is_array( $_POST['wcml_wc_appointment_staff_cost'] ) ) {
					$this->update_appointment_staff_cost( $currencies, $post_id, $_POST['wcml_wc_appointment_staff_cost'] );
				}

			} else {
				return false;
			}
		}

	}

	// sync existing product appointments for translations
	function sync_appointments( $original_product_id, $product_id, $lang ) {
		$all_appointments_for_product = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id as id FROM {$this->wpdb->postmeta} WHERE meta_key = '_appointment_product_id' AND meta_value = %d", $original_product_id ) );

		foreach ( $all_appointments_for_product as $appointment ) {
			$check_if_exists = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT pm3.* FROM {$this->wpdb->postmeta} AS pm1
                                            LEFT JOIN {$this->wpdb->postmeta} AS pm2 ON pm1.post_id = pm2.post_id
                                            LEFT JOIN {$this->wpdb->postmeta} AS pm3 ON pm1.post_id = pm3.post_id
                                            WHERE pm1.meta_key = '_appointment_duplicate_of' AND pm1.meta_value = %s AND pm2.meta_key = '_language_code' AND pm2.meta_value = %s AND pm3.meta_key = '_appointment_product_id'"
				, $appointment->id, $lang ) );

			if ( is_null( $check_if_exists ) ) {
				$this->duplicate_appointment_for_translations( $appointment->id, $lang );
			} elseif ( '' === $check_if_exists->meta_value ) {
				update_post_meta( $check_if_exists->post_id, '_appointment_product_id', $this->get_translated_appointment_product_id( $appointment->id, $lang ) );
				update_post_meta( $check_if_exists->post_id, '_appointment_staff_id', $this->get_translated_appointment_staff_id( $appointment->id, $lang ) );
			}
		}
	}

	function sync_appointment_data( $original_product_id, $current_product_id ) {
		if ( has_term( 'appointment', 'product_type', $original_product_id ) ) {
			global $pagenow, $iclTranslationManagement;

			// get language code
			$language_details = $this->sitepress->get_element_language_details( $original_product_id, 'post_product' );
			if ( $pagenow == 'admin.php' && empty( $language_details ) ) {
				//translation editor support: sidestep icl_translations_cache
				$language_details = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT element_id, trid, language_code, source_language_code FROM {$this->wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = 'post_product'", $original_product_id ) );
			}
			if ( empty( $language_details ) ) {
				return;
			}

			// pick posts to sync
			$posts        = array();
			$translations = $this->sitepress->get_element_translations( $language_details->trid, 'post_product' );
			foreach ( $translations as $translation ) {

				if ( ! $translation->original ) {
					$posts[ $translation->element_id ] = $translation;
				}
			}

			foreach ( $posts as $post_id => $translation ) {

				$trn_lang = $this->sitepress->get_language_for_element( $post_id, 'post_product' );

				// sync_staff.
				$this->sync_staff( $original_product_id, $post_id, $trn_lang );

			}

		}

	}

    function sync_staff( $original_product_id, $translated_product_id, $lang_code, $duplicate = true ) {
		$original_staff = $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT staff_id, sort_order FROM {$this->wpdb->prefix}wc_appointment_relationships WHERE product_id = %d",
            $original_product_id ) );

		$current_staff_ids = $this->wpdb->get_results( $this->wpdb->prepare( "
			SELECT *
			FROM {$this->wpdb->prefix}wc_appointment_relationships
			WHERE `product_id` = %d
			ORDER BY sort_order ASC
		", $translated_product_id ), ARRAY_A );

		$current_temp = array();
		foreach ( $current_staff_ids as $staff ) {
			$current_temp[ $staff['staff_id'] ] = $staff;
		}
		$current_staff_ids = $current_temp;

		foreach ( $original_staff as $staff_member ) {

			$replace = array(
				'sort_order'  => $staff_member->sort_order,
				'product_id'  => $translated_product_id,
				'staff_id'    => $staff_member->staff_id,
			);

			if ( isset( $current_staff_ids[ $staff_member->staff_id ] ) ) {
				$replace['ID'] = $current_staff_ids[ $staff_member->staff_id ]['ID'];
				unset( $current_staff_ids[ $staff_member->staff_id ] );
			}

			$this->wpdb->replace(
				$this->wpdb->prefix . 'wc_appointment_relationships',
				$replace
			);
		}

		if ( ! empty( $current_staff_ids ) ) {
			foreach ( $current_staff_ids as $staff ) {
				$this->wpdb->delete(
					$this->wpdb->prefix . 'wc_appointment_relationships',
					array(
						'ID' => $staff['ID'],
					)
				);
			}
		}

        $this->sync_staff_costs( $original_product_id, $translated_product_id, '_staff_base_costs', $lang_code );
    }

    function duplicate_staff_member( $tr_product_id, $staff_member, $lang_code ) {
        global $iclTranslationManagement;

        $this->wpdb->insert(
            $this->wpdb->prefix . 'wc_appointment_relationships',
            array(
                'product_id' => $tr_product_id,
                'staff_id' => $staff_member->staff_id,
                'sort_order' => $staff_member->sort_order,
            )
        );

        return $staff_member->staff_id;
    }

    public function update_product_staff_member( $product_id, $staff_id, $staff_data ) {
        $this->wpdb->update(
            $this->wpdb->prefix . 'wc_appointment_relationships',
            array(
                'sort_order' => $staff_data->sort_order,
            ),
            array(
                'product_id' => $product_id,
                'staff_id' => $staff_id,
            )
        );
    }

    public function add_product_staff_member( $product_id, $staff_id, $staff_data ) {
        $this->wpdb->insert(
            $this->wpdb->prefix . 'wc_appointment_relationships',
            array(
                'sort_order' => $staff_data->sort_order,
                'product_id' => $product_id,
                'staff_id' => $staff_id,
            )
        );
    }

    public function remove_staff_from_product( $product_id, $staff_id ) {
        $this->wpdb->delete(
            $this->wpdb->prefix . 'wc_appointment_relationships',
            array(
                'product_id'  => $product_id,
                'staff_id' => $staff_id,
            )
        );
    }

    function sync_staff_costs_with_translations( $object_id, $meta_key, $check = false ) {
        $original_product_id = apply_filters( 'translate_object_id', $object_id, 'product', true, $this->woocommerce_wpml->products->get_original_product_language( $object_id ) );

        if ( $object_id == $original_product_id ) {
            $trid = $this->sitepress->get_element_trid( $object_id, 'post_product' );
            $translations = $this->sitepress->get_element_translations( $trid, 'post_product' );

            foreach ( $translations as $translation ) {
                if ( ! $translation->original ) {
                    $this->sync_staff_costs( $original_product_id, $translation->element_id, $meta_key, $translation->language_code );
                }
            }

            return $check;
        } else {
            $language_code = $this->sitepress->get_language_for_element( $object_id, 'post_product' );
            $this->sync_staff_costs( $original_product_id, $object_id, $meta_key, $language_code );

            return true;
        }

    }

    function sync_staff_costs( $original_product_id, $object_id, $meta_key, $language_code ) {
        $original_costs = maybe_unserialize( get_post_meta( $original_product_id, $meta_key, true ) );
        $wc_appointment_staff_costs = array();

        if ( ! empty( $original_costs ) ) {
            foreach ( $original_costs as $staff_id => $costs ) {
                if ( 'custom_costs' == $staff_id && isset( $costs['custom_costs'] ) ) {
                    foreach ( $costs['custom_costs'] as $code => $currencies ) {
                        foreach ( $currencies as $custom_costs_staff_id => $custom_cost ) {
                            $trns_staff_id = apply_filters( 'translate_object_id', $custom_costs_staff_id, 'appointable_staff', true, $language_code );
                            $wc_appointment_staff_costs['custom_costs'][ $code ][ $trns_staff_id ] = $custom_cost;
                        }
                    }
                } else {
                    $trns_staff_id = apply_filters( 'translate_object_id', $staff_id, 'appointable_staff', true, $language_code );
                    $wc_appointment_staff_costs[ $trns_staff_id ] = $costs;
                }
            }
        }

        update_post_meta( $object_id, $meta_key, $wc_appointment_staff_costs );
    }

	function filter_wc_appointment_cost( $check, $object_id, $meta_key, $single ) {
		if ( in_array( $meta_key, array(
			'_wc_appointment_pricing',
            '_price',
			'_regular_price',
			'_sale_price',
			'_staff_base_costs',
		) ) ) {

			if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

				$original_id = $this->woocommerce_wpml->products->get_original_product_id( $object_id );
				$cost_status = get_post_meta( $original_id, '_wcml_custom_costs_status', true );
				$currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

				if ( $currency == get_option( 'woocommerce_currency' ) ) {
					return $check;
				}

				if ( in_array( $meta_key, array( '_price', '_regular_price', '_sale_price' ) ) ) {
                    return $check;
				}

				if ( in_array( $meta_key, array(
					'_wc_appointment_pricing',
					'_staff_base_costs',
				) ) ) {

					remove_filter( 'get_post_metadata', array( $this, 'filter_wc_appointment_cost' ), 10, 4 );

					if ( $meta_key == '_wc_appointment_pricing' ) {

						if ( $original_id != $object_id ) {
							$value = get_post_meta( $original_id, $meta_key );
						} else {
							$value = $check;
						}

					} else {

						$costs = maybe_unserialize( get_post_meta( $object_id, $meta_key, true ) );

						if ( ! $costs ) {
							$value = $check;
						} elseif ( $cost_status && isset( $costs['custom_costs'][ $currency ] ) ) {

							$res_costs = array();
							foreach ( $costs['custom_costs'][ $currency ] as $staff_id => $cost ) {
								$trns_staff_id               = apply_filters( 'translate_object_id', $staff_id, 'appointable_staff', true, $this->sitepress->get_current_language() );
								$res_costs[ $trns_staff_id ] = $cost;
							}
							$value = array( 0 => $res_costs );
						} elseif ( $cost_status && isset( $costs[0]['custom_costs'][ $currency ] ) ) {
							$value = array( 0 => $costs[0]['custom_costs'][ $currency ] );
						} else {

							$converted_values = array();

							foreach ( $costs as $staff_id => $cost ) {
								$converted_values[0][ $staff_id ] = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $cost, $currency );
							}

							$value = $converted_values;
						}

					}

					add_filter( 'get_post_metadata', array( $this, 'filter_wc_appointment_cost' ), 10, 4 );

					return $value;

				}

				$value = get_post_meta( $original_id, $meta_key . '_' . $currency, true );

				if ( $cost_status && ( ! empty( $value ) || ( empty( $value ) ) ) ) {

					return $value;

				} else {

					remove_filter( 'get_post_metadata', array( $this, 'filter_wc_appointment_cost' ), 10, 4 );

					$value = get_post_meta( $original_id, $meta_key, true );

					$value = $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $value, $currency );

					add_filter( 'get_post_metadata', array( $this, 'filter_wc_appointment_cost' ), 10, 4 );

					return $value;

				}

			}

		}

		return $check;
	}

	function wc_appointments_process_pricing_rules_cost( $cost, $fields, $key ) {
		return $this->filter_pricing_cost( $cost, $fields, 'cost_', $key );
	}

	function wc_appointments_process_pricing_rules_base_cost( $base_cost, $fields, $key ) {
		return $this->filter_pricing_cost( $base_cost, $fields, 'base_cost_', $key );
	}

	function wc_appointments_process_pricing_rules_override_slot_cost( $override_cost, $fields, $key ) {
		return $this->filter_pricing_cost( $override_cost, $fields, 'override_slot_', $key );
	}

	function filter_pricing_cost( $cost, $fields, $name, $key ) {
		global $product;

		if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

			$currency = $this->woocommerce_wpml->multi_currency->get_client_currency();

			if ( $currency == get_option( 'woocommerce_currency' ) ) {
				return $cost;
			}

			if ( isset( $_POST['form'] ) ) {
				parse_str( $_POST['form'], $posted );

				$appointment_id = $posted['add-to-cart'];

			} elseif ( isset( $_POST['add-to-cart'] ) ) {

				$appointment_id = $_POST['add-to-cart'];

			}

			if ( isset( $appointment_id ) ) {
				$original_id = $this->woocommerce_wpml->products->get_original_product_id( $appointment_id );

				if ( $appointment_id != $original_id ) {
					$fields = maybe_unserialize( get_post_meta( $original_id, '_wc_appointment_pricing', true ) );
					$fields = $fields[ $key ];
				}
			}

			$needs_filter_pricing_cost = $this->needs_filter_pricing_cost( $name, $fields );

			if ( $needs_filter_pricing_cost ){
				if ( isset( $fields[ $name . $currency ] ) ) {
					return $fields[ $name . $currency ];
				} else {
					return $this->woocommerce_wpml->multi_currency->prices->convert_price_amount( $cost, $currency );
				}
			}

		}

		return $cost;
	}

	function needs_filter_pricing_cost( $name, $fields ){
		$modifier_skip_values = array( 'divide', 'times' );

		if(
			'override_slot_' === $name ||
			( 'cost_' === $name && !in_array( $fields[ 'modifier' ], $modifier_skip_values ) ) ||
			( 'base_cost_' === $name && !in_array( $fields[ 'base_modifier' ], $modifier_skip_values ) )
		) {
			return true;
		} else {
			return false;
		}
	}

	function wcml_multi_currency_is_ajax( $actions ) {
		$actions[] = 'wc_appointments_calculate_costs';

		return $actions;
	}

	function filter_bundled_product_in_cart_contents( $cart_item, $key, $current_language ) {
		if ( $cart_item['data'] instanceof WC_Product_Appointment && isset( $cart_item['appointment'] ) ) {

			$current_id      = apply_filters( 'translate_object_id', $cart_item['product_id'], 'product', true, $current_language );
			$cart_product_id = $cart_item['product_id'];

			if ( $current_id != $cart_product_id ) {
				$cart_item['data'] = new WC_Product_Appointment( $current_id );
			}

			if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT || $current_id != $cart_product_id ) {

				$appointment_info = array(
					'wc_appointments_field_start_date_year'  => $cart_item['appointment']['_year'],
					'wc_appointments_field_start_date_month' => $cart_item['appointment']['_month'],
					'wc_appointments_field_start_date_day'   => $cart_item['appointment']['_day'],
					'add-to-cart'                            => $current_id
				);

				if ( isset( $cart_item['appointment']['_staff_id'] ) ) {
					$appointment_info['wc_appointments_field_staff'] = apply_filters( 'translate_object_id', $cart_item['appointment']['_staff_id'], 'appointable_staff', false, $current_language );
				}

				if ( isset( $cart_item['appointment']['_duration'] ) ) {
					$appointment_info['wc_appointments_field_duration'] = $cart_item['appointment']['_duration'];
				}

				if ( isset( $cart_item['appointment']['_time'] ) ) {
					$appointment_info['wc_appointments_field_start_date_time'] = $cart_item['appointment']['_time'];
				}

				$appointment_form = new WC_Appointment_Form( wc_get_product( $current_id ) );

				$cost = $appointment_form->calculate_appointment_cost( $appointment_info );
				if ( ! is_wp_error( $cost ) ) {
					$cart_item['data']->set_price( $cost );
				}
			}
		}

		if ( $cart_item['data'] instanceof WC_Product_Appointment && isset( $cart_item['addons'] ) ) {
			$cost = $cart_item['data']->get_price();

			foreach( $cart_item['addons'] as $addon ){
				$cost += $addon['price'];
			}

			$cart_item['data']->set_price( $cost );
		}

		return $cart_item;
	}

	function appointment_currency_dropdown() {
		if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {

			$current_appointment_currency = $this->get_cookie_appointment_currency();

			$wc_currencies = get_woocommerce_currencies();
			$currencies    = $this->woocommerce_wpml->multi_currency->get_currencies( $include_default = true );
			?>
			<tr valign="top">
				<th scope="row"><?php _e( 'Appointment currency', 'woocommerce-multilingual' ); ?></th>
				<td>
					<select id="dropdown_appointment_currency">

						<?php foreach ( $currencies as $currency => $count ): ?>

							<option
								value="<?php echo $currency ?>" <?php echo $current_appointment_currency == $currency ? 'selected="selected"' : ''; ?>><?php echo $wc_currencies[ $currency ]; ?></option>

						<?php endforeach; ?>

					</select>
				</td>
			</tr>

			<?php

			$wcml_appointment_set_currency_nonce = wp_create_nonce( 'appointment_set_currency' );

			wc_enqueue_js( "

            jQuery(document).on('change', '#dropdown_appointment_currency', function(){
               jQuery.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'wcml_appointment_set_currency',
                        currency: jQuery('#dropdown_appointment_currency').val(),
                        wcml_nonce: '" . $wcml_appointment_set_currency_nonce . "'
                    },
                    success: function( response ){
                        if(typeof response.error !== 'undefined'){
                            alert(response.error);
                        }else{
                           window.location = window.location.href;
                        }
                    }
                })
            });
        " );

		}
	}

	function set_appointment_currency_ajax() {
		$nonce = filter_input( INPUT_POST, 'wcml_nonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'appointment_set_currency' ) ) {
			echo wp_json_encode( array( 'error' => __( 'Invalid nonce', 'woocommerce-multilingual' ) ) );
			die();
		}

		$this->set_appointment_currency( filter_input( INPUT_POST, 'currency', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) );

		die();
	}

	function set_appointment_currency( $currency_code = false ) {
		if ( ! isset( $_COOKIE ['_wcml_appointment_currency'] ) && ! headers_sent() ) {

			$currency_code = get_woocommerce_currency();

			if ( $this->woocommerce_wpml->settings['enable_multi_currency'] == WCML_MULTI_CURRENCIES_INDEPENDENT ) {
				$order_currencies = $this->woocommerce_wpml->multi_currency->orders->get_orders_currencies();

				if ( ! isset( $order_currencies[ $currency_code ] ) ) {
					foreach ( $order_currencies as $currency_code => $count ) {
						$currency_code = $currency_code;
						break;
					}
				}
			}
		}

		if ( $currency_code ) {
			setcookie( '_wcml_appointment_currency', $currency_code, time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
		}
	}

	function get_cookie_appointment_currency() {
		if ( isset( $_COOKIE ['_wcml_appointment_currency'] ) ) {
			$currency = $_COOKIE['_wcml_appointment_currency'];
		} else {
			$currency = get_woocommerce_currency();
		}

		return $currency;
	}

	function filter_appointment_currency_symbol( $currency ) {
		global $pagenow;

		remove_filter( 'woocommerce_currency_symbol', array( $this, 'filter_appointment_currency_symbol' ) );
		if ( isset( $_COOKIE ['_wcml_appointment_currency'] ) && $pagenow == 'edit.php' && isset( $_GET['page'] ) && $_GET['page'] == 'create_appointment' ) {
			$currency = get_woocommerce_currency_symbol( $_COOKIE ['_wcml_appointment_currency'] );
		}
		add_filter( 'woocommerce_currency_symbol', array( $this, 'filter_appointment_currency_symbol' ) );

		return $currency;
	}

	function create_appointment_page_client_currency( $currency ) {
		global $pagenow;

		if ( wpml_is_ajax() && isset( $_POST['form'] ) ) {
			parse_str( $_POST['form'], $posted );
		}

		if ( ( $pagenow == 'edit.php' && isset( $_GET['page'] ) && $_GET['page'] == 'create_appointment' ) || ( isset( $posted['_wp_http_referer'] ) && strpos( $posted['_wp_http_referer'], 'page=create_appointment' ) !== false ) ) {
			$currency = $this->get_cookie_appointment_currency();
		}

		return $currency;
	}

	function set_order_currency_on_create_appointment_page( $order_id ) {
		update_post_meta( $order_id, '_order_currency', $this->get_cookie_appointment_currency() );
		update_post_meta( $order_id, 'wpml_language', $this->sitepress->get_current_language() );
	}

	function filter_get_appointment_products_args( $args ) {
		if ( isset( $args['suppress_filters'] ) ) {
			$args['suppress_filters'] = false;
		}

		return $args;
	}

    function custom_box_html( $obj, $product_id, $data ) {
        if ( 'appointment' != wc_get_product( $product_id )->get_type() ) {
            return;
        }

        $appointments_section = new WPML_Editor_UI_Field_Section( __( 'Appointments', 'woocommerce-appointments' ) );

        if ( 'yes' == get_post_meta( $product_id,'_wc_appointment_has_staff', true ) ) {
            $group = new WPML_Editor_UI_Field_Group( '', true );
            $appointment_field = new WPML_Editor_UI_Single_Line_Field( '_wc_appointment_staff_label', __( 'Staff Label', 'woocommerce-appointments' ), $data, true );
            $group->add_field( $appointment_field );
            $appointments_section->add_field( $group );
        }

        $orig_staff = maybe_unserialize( get_post_meta( $product_id, '_staff_base_costs', true ) );

        if ( $orig_staff ) {
            $group = new WPML_Editor_UI_Field_Group( __( 'Staff', 'woocommerce-appointments' ) );
            $group_title = __( 'Staff', 'woocommerce-appointments' );

            foreach ( $orig_staff as $staff_id => $cost ) {
                if ( 'custom_costs' == $staff_id ) {
					continue;
				}

                $group = new WPML_Editor_UI_Field_Group( $group_title );
                $group_title = '';

                $appointments_section->add_field( $group );
            }
        }

        if ( $orig_staff ) {
            $obj->add_field( $appointments_section );
        }
    }

    function custom_box_html_data( $data, $product_id, $translation, $lang ) {
        if ( 'appointment' != wc_get_product( $product_id )->get_type() ) {
            return $data;
        }

        if ( 'yes' == get_post_meta( $product_id,'_wc_appointment_has_staff',true ) ) {
            $data['_wc_appointment_staff_label'] = array( 'original' => get_post_meta( $product_id, '_wc_appointment_staff_label', true ) );
            $data['_wc_appointment_staff_label']['translation'] = $translation ? get_post_meta( $translation->ID, '_wc_appointment_staff_label', true ) : '';
        }

        $orig_staff = $this->get_original_staff( $product_id );

        if ( $orig_staff && is_array( $orig_staff ) ) {

            foreach ( $orig_staff as $staff_id => $cost ) {
                if ( 'custom_costs' === $staff_id ) {
                    continue;
                }
            }
        }

        return $data;
    }

    function get_original_staff( $product_id ) {
        $orig_staff = maybe_unserialize( get_post_meta( $product_id, '_staff_base_costs', true ) );

        return $orig_staff;
    }

	function show_custom_slots_for_staff( $check, $product_id, $product_content ) {
		if ( in_array( $product_content, array( 'wc_appointment_staff' ) ) ) {
			return false;
		}

		return $check;
	}

	function replace_tm_editor_custom_fields_with_own_sections( $fields ) {
		$fields[] = '_staff_base_costs';

		return $fields;
	}

    function remove_single_custom_fields_to_translate( $fields ) {
        $fields[] = '_wc_appointment_staff_label';

        return $fields;
    }

    function product_content_staff_label( $meta_key, $product_id ) {
        if ( '_wc_appointment_staff_label' == $meta_key ) {
            return __( 'Staff label', 'woocommerce-appointments' );
        }

        return $meta_key;
    }

    function wcml_products_tab_sync_staff( $original_product_id, $tr_product_id, $data, $language ) {
        global $wpml_post_translations;

        remove_action( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );

        $orig_staff = $this->get_original_staff( $original_product_id );

        if ( $orig_staff ) {

            foreach ( $orig_staff as $orig_staff_id => $cost ) {

                $staff_id = apply_filters( 'translate_object_id', $orig_staff_id, 'appointable_staff', false, $language );
                $orig_staff_member = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT staff_id, sort_order FROM {$this->wpdb->prefix}wc_appointment_relationships WHERE staff_id = %d AND product_id = %d", $orig_staff_id, $original_product_id ), OBJECT );

                if ( is_null( $staff_id ) ) {
                    if ( $orig_staff_member ) {
                        $staff_id = $this->duplicate_staff_member( $tr_product_id, $orig_staff_member, $language );
                    } else {
                        continue;
                    }
                } else {
                    // Update_relationship
                    $exist = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT ID FROM {$this->wpdb->prefix}wc_appointment_relationships WHERE staff_id = %d AND product_id = %d", $staff_id, $tr_product_id ) );

                    if ( ! $exist ) {
                        $this->wpdb->insert(
                            $this->wpdb->prefix . 'wc_appointment_relationships',
                            array(
                                'product_id' => $tr_product_id,
                                'staff_id' => $staff_id,
                                'sort_order' => $orig_staff_member->sort_order,
                            )
                        );
                    }
                }

                update_post_meta( $staff_id, 'wcml_is_translated', true );
            }

            // sync staff data.
            $this->sync_staff( $original_product_id, $tr_product_id, $language, false );

        }

        add_action( 'save_post', array( $wpml_post_translations, 'save_post_actions' ), 100, 2 );
    }

	function duplicate_appointment_for_translations( $appointment_id, $lang = false ) {
		$appointment_object = get_post( $appointment_id );

		$appointment_data = array(
			'post_type'   => 'wc_appointment',
			'post_title'  => $appointment_object->post_title,
			'post_status' => $appointment_object->post_status,
			'ping_status' => 'closed'
		);

		if( $appointment_object->post_parent && $lang ){
			$translated_parent = apply_filters( 'translate_object_id', $appointment_object->post_parent, get_post_type( $appointment_object->post_parent ), false, $lang );
			if( $translated_parent ) $appointment_data[ 'post_parent' ] = $translated_parent;
		}

		$active_languages = $this->sitepress->get_active_languages();

		foreach ( $active_languages as $language ) {

			$appointment_product_id = get_post_meta( $appointment_id, '_appointment_product_id', true );

			if ( ! $lang ) {
				$appointment_language = $this->sitepress->get_element_language_details( $appointment_product_id, 'post_product' );
				if ( $appointment_language->language_code == $language['code'] ) {
					continue;
				}
			} elseif ( $lang != $language['code'] ) {
				continue;
			}

			$trnsl_appointment_id = wp_insert_post( $appointment_data );
			$trid             = $this->sitepress->get_element_trid( $appointment_id );
			$this->sitepress->set_element_language_details( $trnsl_appointment_id, 'post_wc_appointment', $trid, $language['code'] );

			$meta_args = array(
				'_appointment_order_item_id' => 0,
				'_appointment_product_id'    => $this->get_translated_appointment_product_id( $appointment_id, $language['code'] ),
				'_appointment_staff_id'      => $this->get_translated_appointment_staff_id( $appointment_id, $language['code'] ),
				'_appointment_cost'          => get_post_meta( $appointment_id, '_appointment_cost', true ),
				'_appointment_start'         => get_post_meta( $appointment_id, '_appointment_start', true ),
				'_appointment_end'           => get_post_meta( $appointment_id, '_appointment_end', true ),
				'_appointment_all_day'       => intval( get_post_meta( $appointment_id, '_appointment_all_day', true ) ),
				'_appointment_parent_id'     => get_post_meta( $appointment_id, '_appointment_parent_id', true ),
				'_appointment_customer_id'   => get_post_meta( $appointment_id, '_appointment_customer_id', true ),
                '_appointment_qty'			 => get_post_meta( $appointment_id, '_appointment_qty', true ),
				'_appointment_duplicate_of'  => $appointment_id,
				'_language_code'         => $language['code'],
			);

			foreach ( $meta_args as $key => $value ) {
				update_post_meta( $trnsl_appointment_id, $key, $value );
			}

			WC_Cache_Helper::get_transient_version( 'appointments', true );
		}
	}

    function get_translated_appointment_product_id( $appointment_id, $language ) {
        $appointment_product_id = get_post_meta( $appointment_id, '_appointment_product_id', true );
        $trnsl_appointment_product_id = '';

        if ( $appointment_product_id ) {
            $trnsl_appointment_product_id = apply_filters( 'translate_object_id', $appointment_product_id, 'product', false, $language );
            if ( is_null( $trnsl_appointment_product_id ) ) {
                $trnsl_appointment_product_id = '';
            }
        }

        return $trnsl_appointment_product_id;
    }

    function get_translated_appointment_staff_id( $appointment_id, $language ) {
        $appointment_staff_id = get_post_meta( $appointment_id, '_appointment_staff_id', true );
        $trnsl_appointment_staff_id = '';

        if ( $appointment_staff_id ) {
            $trnsl_appointment_staff_id = apply_filters( 'translate_object_id', $appointment_staff_id, 'appointable_staff', false, $language );

            if ( is_null( $trnsl_appointment_staff_id ) ) {
                $trnsl_appointment_staff_id = '';
            }
        }

        return $trnsl_appointment_staff_id;
    }

    function update_status_for_translations( $appointment_id ) {
        $translated_appointments = $this->get_translated_appointments( $appointment_id );

        foreach ( $translated_appointments as $appointment ) {
            $status = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT post_status FROM {$this->wpdb->posts} WHERE ID = %d", $appointment_id ) ); // get_post_status( $appointment_id );
            $language = get_post_meta( $appointment->post_id, '_language_code', true );

            $this->wpdb->update(
                $this->wpdb->posts,
                array(
                    'post_status' => $status,
                    'post_parent' => wp_get_post_parent_id( $appointment_id ),
                ),
                array(
                    'ID' => $appointment->post_id,
                )
            );

            update_post_meta( $appointment->post_id, '_appointment_product_id', $this->get_translated_appointment_product_id( $appointment_id, $language ) );
            update_post_meta( $appointment->post_id, '_appointment_staff_id', $this->get_translated_appointment_staff_id( $appointment_id, $language ) );
        }
    }

	function get_translated_appointments( $appointment_id ) {
		$translated_appointments = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = '_appointment_duplicate_of' AND meta_value = %d", $appointment_id ) );

		return $translated_appointments;
	}

	public function appointment_filters_query( $query ) {
		global $typenow;

		if ( ( isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'wc_appointment' ) ) {

			$current_lang = $this->sitepress->get_current_language();

			$product_ids = $this->wpdb->get_col( $this->wpdb->prepare(
				"SELECT element_id
					FROM {$this->wpdb->prefix}icl_translations
					WHERE language_code = %s AND element_type = 'post_product'", $current_lang ) );

			$product_ids = array_diff( $product_ids, array( null ) );

			if ( ( ! isset( $_GET['lang'] ) || ( isset( $_GET['lang'] ) && $_GET['lang'] != 'all' ) ) ) {
				$query->query_vars['meta_query'][] = array(
					'relation' => 'OR',
					array(
						'key'      => '_language_code',
						'value'    => $current_lang,
						'compare ' => '='
					),
					array(
						'key'      => '_appointment_product_id',
						'value'    => $product_ids,
						'compare ' => 'IN'
					)
				);
			}
		}

		return $query;
	}

    function appointments_in_date_range_query( $appointment_ids ) {
        foreach ( $appointment_ids as $key => $appointment_id ) {
            $language_code = $this->sitepress->get_language_for_element( get_post_meta( $appointment_id, '_appointment_product_id', true ) , 'post_product' );
            $current_language = $this->sitepress->get_current_language();

            if ( $language_code != $current_language ) {
                unset( $appointment_ids[ $key ] );
            }
        }

        return $appointment_ids;
    }

    function clear_transient_fields() {
        if ( isset( $_GET['post_type'] ) && 'wc_appointment' == $_GET['post_type'] && isset( $_GET['page'] ) && 'appointment_calendar' == $_GET['page'] ) {
            // delete transient fields
            $this->wpdb->query("
                DELETE FROM {$this->wpdb->options}
		        WHERE option_name LIKE '%schedule_dr_%'
		    ");
        }
    }

    function delete_appointments( $appointment_id ) {
        if ( $appointment_id > 0 && get_post_type( $appointment_id ) == 'wc_appointment' ) {
            $translated_appointments = $this->get_translated_appointments( $appointment_id );

            remove_action( 'before_delete_post', array( $this, 'delete_appointments' ) );

            foreach ( $translated_appointments as $appointment ) {
                $this->wpdb->update(
                    $this->wpdb->posts,
                    array(
                        'post_parent' => 0,
                    ),
                    array(
                        'ID' => $appointment->post_id,
                    )
                );

                wp_delete_post( $appointment->post_id );
            }

            add_action( 'before_delete_post', array( $this, 'delete_appointments' ) );
        }
    }

    function trash_appointments( $appointment_id ) {
        if ( $appointment_id > 0 && get_post_type( $appointment_id ) == 'wc_appointment' ) {
            $translated_appointments = $this->get_translated_appointments( $appointment_id );

            foreach ( $translated_appointments as $appointment ) {
                $this->wpdb->update(
                    $this->wpdb->posts,
                    array(
                        'post_status' => 'trash',
                    ),
                    array(
                        'ID' => $appointment->post_id,
                    )
                );
            }
        }
    }

    function append_staff_to_translation_package( $package, $post ) {
        if ( 'product' == $post->post_type ) {
            $product = wc_get_product( $post->ID );

            // WC_Product::get_type() available from WooCommerce 2.4.0
            $product_type = method_exists( $product, 'get_type' ) ? $product->get_type() : $product->get_type();

            if ( 'appointment' == $product_type && $product->has_staff() ) {
                $staff = $product->get_staff();
                foreach ( $staff as $staff_member ) {
                    $package['contents'][ 'wc_appointments:staff:' . $staff_member->get_id() . ':name' ] = array(
                        'translate' => 1,
                        'data' => $this->tp->encode_field_data( $staff_member->display_name, 'base64' ),
                        'format' => 'base64',
                    );
                }
            }
        }

        return $package;
    }

    function save_staff_translation( $post_id, $data, $job ) {
        $staff_translations = array();

		if ( 'appointment' === WooCommerce_Functions_Wrapper::get_product_type( $post_id ) ) {

	        foreach ( $data as $value ) {
	            if ( $value['finished'] && strpos( $value['field_type'], 'wc_appointments:staff:' ) === 0 ) {
	                $exp = explode( ':', $value['field_type'] );

	                $staff_id  = $exp[2];
	                $field     = $exp[3];

	                $staff_translations[ $staff_id ][ $field ] = $value['data'];
	            }
	        }

	        if ( $staff_translations ) {
	           $translated_staff = $this->wpdb->get_col( $this->wpdb->prepare(
		            "SELECT staff_id FROM {$this->wpdb->prefix}wc_appointment_relationships WHERE product_id = %d",
		            $post_id ) );
				$product = wc_get_product( $post_id );

				foreach ( $staff_translations as $staff_id ) {
		            $translated_staff_id = apply_filters( 'translate_object_id', $staff_id, 'appointable_staff', false, $job->language_code );
					$staff_member        = $product->get_staff_member( absint( $staff_id ) );

		            if ( ! is_null( $translated_staff_id ) ) {

		                if ( in_array( $translated_staff_id, $translated_staff ) ) {
		                    $this->update_product_staff_member( $post_id, $translated_staff_id, $staff_member );
		                } else {
		                    $this->add_product_staff_member( $post_id, $translated_staff_id, $staff_member );
		                }
		                $used_translated_staff[] = $translated_staff_id;
		            } else {
		                if ( $duplicate ) {
		                    $this->duplicate_staff_member( $post_id, $staff_member, $job->language_code );
		                }
		            }
		        }
	        }
		}
    }

    function wcml_js_lock_fields_ids( $ids ) {
        $ids = array_merge( $ids, array(
			'_wc_appointment_has_price_label',
			'_wc_appointment_has_pricing',
			'_wc_appointment_qty',
			'_wc_appointment_qty_min',
			'_wc_appointment_qty_max',
			'_wc_appointment_staff_assignment',
			'_wc_appointment_duration',
			'_wc_appointment_duration_unit',
			'_wc_appointment_interval',
			'_wc_appointment_interval_unit',
			'_wc_appointment_min_date',
			'_wc_appointment_min_date_unit',
			'_wc_appointment_max_date',
			'_wc_appointment_max_date_unit',
			'_wc_appointment_padding_duration',
			'_wc_appointment_padding_duration_unit',
			'_wc_appointment_user_can_cancel',
			'_wc_appointment_cancel_limit',
			'_wc_appointment_cancel_limit_unit',
			'_wc_appointment_cal_color',
			'_wc_appointment_requires_confirmation',
			'_wc_appointment_availability_span',
			'_wc_appointment_availability_autoselect',
			'appointments_staff select',
			'appointments_staff button',
            'appointments__availability select',
        ) );

        return $ids;
    }

	/**
	 * @param array $args
	 *
	 * @return array
	 */
    public function filter_get_appointment_staff_args( $args ) {
         $screen = get_current_screen();

         if ( 'product' == $screen->id ) {
             $args['suppress_filters'] = false;
         }

         return $args;
     }

	/**
	 * @param array $currencies
	 * @param int $post_id
	 *
	 * @return bool
	 */
	private function update_appointment_pricing( $currencies = array(), $post_id = 0 ) {
		$updated_meta    = array();
		$appointment_pricing = get_post_meta( $post_id, '_wc_appointment_pricing', true );

        if ( empty( $appointment_pricing ) ) {
			return false;
		}

		foreach ( $appointment_pricing as $key => $prices ) {
			$updated_meta[ $key ] = $prices;
			foreach ( $currencies as $code => $currency ) {
				if ( isset( $_POST['wcml_wc_appointment_pricing_base_cost'][ $code ][ $key ] ) ) {
					$updated_meta[ $key ][ 'base_cost_' . $code ] = sanitize_text_field( $_POST['wcml_wc_appointment_pricing_base_cost'][ $code ][ $key ] );
				}
				if ( isset( $_POST['wcml_wc_appointment_pricing_cost'][ $code ][ $key ] ) ) {
					$updated_meta[ $key ][ 'cost_' . $code ] = sanitize_text_field( $_POST['wcml_wc_appointment_pricing_cost'][ $code ][ $key ] );
				}
			}

		}

		update_post_meta( $post_id, '_wc_appointment_pricing', $updated_meta );

		return true;
	}

    /**
	 * @param array $currencies
	 * @param int $post_id
	 * @param array $staff_cost
	 *
	 * @return bool
	 */
	private function update_appointment_staff_cost( $currencies = array(), $post_id = 0, $staff_cost = array() ) {
		if ( empty( $staff_cost ) ) {
			return false;
		}

		$updated_meta = get_post_meta( $post_id, '_staff_base_costs', true );
		if ( ! is_array( $updated_meta ) ) {
			$updated_meta = array();
		}

		$wc_appointment_staff_costs = array();

		foreach ( $staff_cost as $staff_id => $costs ) {
			foreach ( $currencies as $code => $currency ) {
				if ( isset( $costs[ $code ] ) ) {
					$wc_appointment_staff_costs[ $code ][ $staff_id ] = sanitize_text_field( $costs[ $code ] );
				}
			}
		}

		$updated_meta['custom_costs'] = $wc_appointment_staff_costs;

		update_post_meta( $post_id, '_staff_base_costs', $updated_meta );

		$this->sync_staff_costs_with_translations( $post_id, '_staff_base_costs' );

		return true;
	}

	public function maybe_fix_double_serialized_wc_appointment_availability( $mid, $object_id, $meta_key, $_meta_value ) {
		global $wpdb;

		if ( version_compare( ICL_SITEPRESS_VERSION, '3.6', '<' ) ) {

			$meta_keys_to_fix = array(
				'_wc_appointment_availability',
				'_wc_appointment_pricing'
			);

			if ( in_array( $meta_key, $meta_keys_to_fix ) ) {

				if ( is_string( $_meta_value ) ) {
					$wpdb->update( $wpdb->postmeta, array( 'meta_value' => $_meta_value ), array( 'meta_id' => $mid ) );
				}

			}

		}

	}

	public function extra_conditions_to_filter_appointments( $extra_conditions ){

		if( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'wc_appointment' && !isset( $_GET[ 'post_status' ] ) ){
			$extra_conditions = str_replace( "GROUP BY", " AND post_status = 'confirmed' GROUP BY", $extra_conditions );
		}

		return $extra_conditions;
	}

	public function hide_appointments_type_on_tm_dashboard( $types ){
		unset( $types[ 'wc_appointment' ] );
		return $types;
	}

	// unset "appointments" from translatable documents to hide WPML languages section from appointment edit page
	public function filter_translatable_documents( $icl_post_types ){

		if(
			( isset( $_GET[ 'post' ] ) && get_post_type( $_GET[ 'post' ] ) == 'wc_appointment' ) ||
			( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'wc_appointment' )
		){
			unset( $icl_post_types[ 'wc_appointment' ] );
		}

		return $icl_post_types;
	}

	// hide WPML languages links section from appointments list page
	public function filter_is_translated_post_type( $type ){

		if( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'wc_appointment' ){
			return false;
		}

		return $type;
	}

	/**
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $update
	 *
	 */
	public function sync_appointment_status( $post_id, $post, $update ){

		if( $post->post_type === 'wc_appointment' && $update ){

			$trid = $this->sitepress->get_element_trid( $post_id, 'post_wc_appointment' );
			$translations = $this->sitepress->get_element_translations( $trid, 'post_wc_appointment' );

			foreach( $translations as $translation ){
				if( $translation->element_id != $post_id ){
					$this->wpdb->update(
						$this->wpdb->posts,
						array( 'post_status' => $post->post_status ),
						array( 'ID' => $translation->element_id )
					);
				}
			}

		}

	}

	public function filter_my_account_appointments_tables_by_current_language( $tables ) {
		$current_language = $this->sitepress->get_current_language();

		foreach ( $tables as $table_key => $table ) {

			if ( isset( $table['appointments'] ) ) {

				foreach ( $table['appointments'] as $key => $appointment ) {
					$language_code    = get_post_meta( $appointment->get_id(), '_language_code', true );

					if ( ! $language_code ){
					    $language_code = $this->sitepress->get_language_for_element( $appointment->get_product_id(), 'post_product' );
                    }

					if ( $language_code !== $current_language ) {
						unset( $tables[ $table_key ]['appointments'][ $key ] );
					}
				}
			}

			$tables[ $table_key ]['appointments'] = array_values( $tables[ $table_key ]['appointments'] );
		}

		return $tables;
	}

	public function filter_check_appointment_product( $return = true, $appointment_product_id ) {
		if ( ! $this->woocommerce_wpml->products->is_original_product( $appointment_product_id ) ) {
            return false;
        }

		return $return;
	}
}

new WC_Appointments_Integration_WCML();
