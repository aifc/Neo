<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WC_Appointments_Admin_Details_Meta_Box {
	public $id;
	public $title;
	public $context;
	public $priority;
	public $post_types;

	public function __construct() {
		$this->id = 'woocommerce-appointment-data';
		$this->title = __( 'Appointment Details', 'woocommerce-appointments' );
		$this->context = 'normal';
		$this->priority = 'high';
		$this->post_types = array( 'wc_appointment' );

		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );
	}

	public function meta_box_inner( $post ) {
		wp_nonce_field( 'wc_appointments_details_meta_box', 'wc_appointments_details_meta_box_nonce' );

		// Scripts.
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-ui-datepicker' );

		$customer_id = get_post_meta( $post->ID, '_appointment_customer_id', true );
		$order_parent_id = apply_filters( 'woocommerce_order_number', _x( '#', 'hash before order number', 'woocommerce-appointments' ) . $post->post_parent, $post->post_parent );

		// Sanity check saved dates
		$start_date = get_post_meta( $post->ID, '_appointment_start', true );
		$end_date   = get_post_meta( $post->ID, '_appointment_end', true );
		$product_id = get_post_meta( $post->ID, '_appointment_product_id', true );
		$staff_ids  = get_post_meta( $post->ID, '_appointment_staff_id', false ); // false for mulitple IDs
		if ( ! is_array( $staff_ids ) ) {
			$staff_ids = array( $staff_ids );
		}

		if ( $start_date && strtotime( $start_date ) > strtotime( '+ 2 year', current_time( 'timestamp' ) ) ) {
			echo '<div class="updated highlight"><p>' . __( 'This appointment is scheduled over 2 years into the future. Please ensure this is correct.', 'woocommerce-appointments' ) . '</p></div>';
		}
		if ( $product_id && ( $product = wc_get_product( $product_id ) ) && ( $max = $product->get_max_date() ) ) {
			$max_date = strtotime( "+{$max['value']} {$max['unit']}", current_time( 'timestamp' ) );
			if ( strtotime( $start_date ) > $max_date || strtotime( $end_date ) > $max_date ) {
				echo '<div class="updated highlight"><p>' . sprintf( __( 'This appointment is scheduled over the products allowed max appointment date (%s). Please ensure this is correct.', 'woocommerce-appointments' ), date_i18n( wc_date_format(), $max_date ) ) . '</p></div>';
			}
		}
		if ( strtotime( $start_date ) && strtotime( $end_date ) && strtotime( $start_date ) > strtotime( $end_date ) ) {
			echo '<div class="error"><p>' . __( 'This appointment has an end date set before the start date.', 'woocommerce-appointments' ) . '</p></div>';
		}

		$product_check = wc_get_product( $product_id );

		if ( is_object( $product_check ) && $product_check->is_skeleton() ) {
			echo '<div class="error"><p>' . sprintf( __( 'This appointment is missing a required add-on (product type: %s). Some information is shown below but might be incomplete. Please install the missing add-on through the plugins screen.', 'woocommerce-appointments' ), $product_check->product_type ) . '</p></div>';
		}
		?>
		<style type="text/css">
			#post-body-content, #titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
		</style>
		<div class="panel-wrap woocommerce">
			<div id="appointment_data" class="panel">

				<h2><?php printf( __( 'Appointment #%s', 'woocommerce-appointments' ), esc_html( $post->ID ) ); ?> <a href="<?php echo admin_url( 'edit.php?post_type=wc_appointment&page=appointment_calendar&view=day&calendar_day=' . date( 'Y-m-d', strtotime( $start_date ) ) ); ?>" class="view-on-calendar" title="<?php echo __( 'View on calendar', 'woocommerce-appointments' ); ?>"><span class="dashicons dashicons-calendar"></span></a></h2>
				<p class="appointment_number"><?php

					if ( $post->post_parent ) {
						$order = wc_get_order( $post->post_parent );
						printf( ' ' . __( 'Order %s', 'woocommerce-appointments' ), '<a href="' . admin_url( 'post.php?post=' . absint( $post->post_parent ) . '&action=edit' ) . '">#' . esc_html( $order->get_order_number() ) . '</a>' );
					}

					if ( isset( $product ) && is_object( $product ) && $product->is_appointments_addon() ) {
						printf( ' ' . __( 'Appointment type: %s', 'woocommerce-appointments' ), $product->appointments_addon_title() );
					}

				?></p>

				<div class="appointment_data_column_container">
					<div class="appointment_data_column">

						<h4><?php _e( 'General Details', 'woocommerce-appointments' ); ?></h4>

						<p class="form-field form-field-wide order-form-field">
							<label for="_appointment_order_id"><?php _e( 'Order ID:', 'woocommerce-appointments' ); ?></label>
							<?php
							$order_string = '';
							if ( ! empty( $post->post_parent ) ) {
								$order_string = $order_parent_id . ' &ndash; ' . esc_html( get_the_title( $post->post_parent ) );
							}
							?>
							<input type="hidden" id="_appointment_order_id" name="_appointment_order_id" data-placeholder="<?php _e( 'N/A', 'woocommerce-appointments' ); ?>" data-selected="<?php echo esc_attr( $order_string ); ?>" value="<?php echo esc_attr( $post->post_parent ? $post->post_parent : '' ); ?>" data-allow_clear="true" />
						</p>

						<p class="form-field form-field-wide customer-form-field">
							<label for="_appointment_customer_id"><?php _e( 'Customer:', 'woocommerce-appointments' ); ?></label>
							<?php
							$appointment = new WC_Appointment( $post->ID );
							$customer = $appointment->get_customer();

							if ( $customer && $customer->user_id ) {
								$customer_id = $customer->user_id;
								$user_string = $customer->full_name;
							} elseif ( $customer && ! $customer->user_id ) {
								$customer_id = $customer->user_id;
								$user_string = $customer->full_name;
							} else {
								$customer_id = 0;
								$user_string = __( 'Guest', 'woocommerce-appointments' );
								_e( 'Guest', 'woocommerce-appointments' );
							}
							?>
							<input type="hidden" class="wc-customer-search" id="_appointment_customer_id" name="_appointment_customer_id" data-placeholder="<?php _e( 'Guest', 'woocommerce-appointments' ); ?>" data-selected="<?php echo esc_attr( $user_string ); ?>" value="<?php echo $customer_id; ?>" data-allow_clear="true" />
						</p>
						
						<?php
							$customer_statuses = array_unique( get_wc_appointment_statuses( 'customer', true ) );
							$selected_customer_status = get_post_meta( $post->ID, '_appointment_customer_status', true );
						?>

						<p class="form-field form-field-wide customer-status-field">
							<label for="_appointment_customer_status"><?php _e( 'Customer Status:', 'woocommerce-appointments' ); ?></label>
							<select id="_appointment_customer_status" name="_appointment_customer_status" class="wc-enhanced-select">
								<?php
									foreach ( $customer_statuses as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $selected_customer_status, false ) . '>' . esc_html__( $value, 'woocommerce-appointments' ) . '</option>';
									}
								?>
							</select>
						</p>

						<?php
							$statuses = array_unique( array_merge( get_wc_appointment_statuses( null, true ), get_wc_appointment_statuses( 'user', true ), get_wc_appointment_statuses( 'cancel', true ) ) );
						?>

						<p class="form-field form-field-wide appointment-status-field">
							<label for="_appointment_status"><?php _e( 'Appointment Status:', 'woocommerce-appointments' ); ?></label>
							<select id="_appointment_status" name="_appointment_status" class="wc-enhanced-select">
								<?php
									foreach ( $statuses as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $post->post_status, false ) . '>' . esc_html__( $value, 'woocommerce-appointments' ) . '</option>';
									}
								?>
							</select>
						</p>

						<?php do_action( 'woocommerce_admin_appointment_data_after_appointment_details', $post->ID ); ?>

					</div>
					<div class="appointment_data_column">

						<h4><?php _e( 'Specification', 'woocommerce-appointments' ); ?></h4>

						<?php

						// Product select.
						$appointable_products = array( '' => __( 'N/A', 'woocommerce-appointments' ) );
						foreach ( get_wc_appointment_products( true ) as $product ) {
							$appointable_products[ $product->ID ] = $product->post_title;
						}
						$appointable_products[ wc_appointments_gcal_synced_product_id() ] = __( '[ Google Calendar ]', 'woocommerce-appointments' );

						woocommerce_wp_select( array(
							'id' 			=> 'product_id',
							'wrapper_class' => 'form-field-wide',
							'class' 		=> 'wc-enhanced-select',
							'label' 		=> __( 'Product:', 'woocommerce-appointments' ),
							'options' 		=> $appointable_products,
							'value' 		=> $product_id,
						) );

						// Staff select.
						if ( $product_id && 'gcal' == $product_id ) {
							$staff = WC_Appointments_Admin::get_appointment_staff();
						} else {
							$staff = wc_appointment_get_product_staff( $product_id );
						}

						$appointable_staff = array();
						foreach ( $staff as $staff_member ) {
							$appointable_staff[ $staff_member->ID ] = $staff_member->display_name;
						}

						?>
						<p class="form-field form-field-wide">
							<label for="staff_ids"><?php _e( 'Staff:', 'woocommerce-appointments' ); ?></label>
							<select multiple="multiple" id="staff_ids" name="staff_ids[]" class="multiselect wc-enhanced-select">
								<?php
									foreach ( $appointable_staff as $key => $value ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $staff_ids ), true ) . '>' . esc_html__( $value, 'woocommerce-appointments' ) . '</option>';
									}
								?>
							</select>
						</p>
						<?php

						// Parent product ID.
						woocommerce_wp_text_input( array(
							'type' 			=> 'number',
							'id' 			=> '_appointment_parent_id',
							'label' 		=> __( 'Parent Appointment ID', 'woocommerce-appointments' ),
							'placeholder'	=> 'N/A',
						) );

						// Number of customers.
						$saved_qty = get_post_meta( $post->ID, '_appointment_qty', true );

						if ( $product_id ) {
							if ( ! empty( $saved_qty ) ) {
								woocommerce_wp_text_input( array(
									'type' 			=> 'number',
									'id'			=> '_appointment_qty',
									'label' 		=> __( 'Number of customers', 'woocommerce-appointments' ),
									'placeholder'	=> '0',
									'value' 		=> $saved_qty,
									'wrapper_class' => 'appointment-qty',
								) );
							}
						}

						?>
					</div>
					<div class="appointment_data_column">

						<h4><?php _e( 'Date/Time', 'woocommerce-appointments' ); ?></h4>

						<?php

						woocommerce_wp_checkbox( array(
							'id'			=> '_appointment_all_day',
							'label'			=> __( 'All Day:', 'woocommerce-appointments' ),
							'description' 	=> __( 'Check this box if the appointment lasts all day.', 'woocommerce-appointments' ),
							'value' 		=> get_post_meta( $post->ID, '_appointment_all_day', true ) ? 'yes' : 'no',
						) );

						woocommerce_wp_text_input( array(
							'id'			=> 'appointment_start_date',
							'type'			=> 'text',
							'label'			=> __( 'Start Date:', 'woocommerce-appointments' ),
							'placeholder'	=> 'yyyy-mm-dd',
							'value'			=> date( 'Y-m-d', strtotime( get_post_meta( $post->ID, '_appointment_start', true ) ) ),
							'class' 		=> 'date-picker',
						) );

						woocommerce_wp_text_input( array(
							'id'			=> 'appointment_start_time',
							'type' 			=> 'time',
							'label' 		=> __( 'Start Time:', 'woocommerce-appointments' ),
							'placeholder' 	=> 'hh:mm',
							'value' 		=> date( 'H:i', strtotime( get_post_meta( $post->ID, '_appointment_start', true ) ) ),
							'class' 		=> 'time-picker',
						) );

						woocommerce_wp_text_input( array(
							'id'			=> 'appointment_end_date',
							'type' 			=> 'text',
							'label' 		=> __( 'End Date:', 'woocommerce-appointments' ),
							'placeholder'	=> 'yyyy-mm-dd',
							'value' 		=> date( 'Y-m-d', strtotime( get_post_meta( $post->ID, '_appointment_end', true ) ) ),
							'class' 		=> 'date-picker',
						) );

						woocommerce_wp_text_input( array(
							'id'			=> 'appointment_end_time',
							'type' 			=> 'time',
							'label' 		=> __( 'End Time:', 'woocommerce-appointments' ),
							'placeholder' 	=> 'hh:mm',
							'value' 		=> date( 'H:i', strtotime( get_post_meta( $post->ID, '_appointment_end', true ) ) ),
							'class' 		=> 'time-picker',
						) );
						?>

					</div>
				</div>
				<div class="clear"></div>
			</div>
			<?php
			wc_enqueue_js( "
				$( '#_appointment_order_id' ).filter( ':not(.enhanced)' ).each( function() {
					var select2_args = {
						allowClear:  true,
						placeholder: $( this ).data( 'placeholder' ),
						minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
						escapeMarkup: function( m ) {
							return m;
						},
						ajax: {
							url:         '" . admin_url( 'admin-ajax.php' ) . "',
							dataType:    'json',
							quietMillis: 250,
							data: function( term, page ) {
								return {
									term:     term,
									action:   'wc_appointments_json_search_order',
									security: '" . wp_create_nonce( 'search-appointment-order' ) . "'
								};
							},
							results: function( data, page ) {
								var terms = [];
								if ( data ) {
									$.each( data, function( id, text ) {
										terms.push( { id: id, text: text } );
									});
								}
								return { results: terms };
							},
							cache: true
						}
					};
					select2_args.multiple = false;
					select2_args.initSelection = function( element, callback ) {
						var data = {id: element.val(), text: element.attr( 'data-selected' )};
						return callback( data );
					};
					$( this ).select2( select2_args ).addClass( 'enhanced' );
				});
			" );
			wc_enqueue_js( "
				$( '#_appointment_all_day' ).change( function () {
					if ( $( this ).is( ':checked' ) ) {
						$( '#appointment_start_time, #appointment_end_time' ).closest( 'p' ).hide();
					} else {
						$( '#appointment_start_time, #appointment_end_time' ).closest( 'p' ).show();
					}
				}).change();

				$( '.date-picker' ).datepicker({
					dateFormat: 'yy-mm-dd',
					numberOfMonths: 1,
					showOtherMonths: true,
					changeMonth: true,
					showButtonPanel: true
				});

				// Check if start- and end date are correct
				var start = $( '#appointment_start_date' );
				var end = $( '#appointment_end_date' );
				var start_date = start.val().replace(/-/g,'');
				var end_date = end.val().replace(/-/g,'');
				$( '#appointment_start_date, #appointment_end_date' ).on( 'click', function() {
					update_dates();
				});
				start.on( 'change', function() {
					if ( start_date > end_date ) {
						end.val( $( this ).val() );
						input_animate(end);
					} else if ( start_date === end_date ) {
   						end.val( $( this ).val() );
						input_animate(end);
   					}
				});
				end.on( 'change', function() {
					update_dates();
					if ( end_date < start_date ) {
						start.val( $( this ).val() );
						input_animate(start);
					}
				});

				function update_dates(){
					start_date = start.val().replace(/-/g,'');
					end_date = end.val().replace(/-/g,'');
				}

				function input_animate(e){
					e.stop().css({backgroundColor:'#ddd'}).animate({backgroundColor:'none'}, 500);
				}

			" );
	}

	/**
	 * Returns an array of labels (statuses wrapped in gettext)
	 * @param  array  $statuses
	 * @deprecated since 2.3.0. $this->get_wc_appointment_statuses now also comes with globalised strings.
	 * @return array
	 */
	public function get_labels_for_statuses( $statuses = array() ) {
		$labels = array();
		foreach ( $statuses as $status ) {
			$labels[ $status ] = __( $status, 'woocommerce-appointments' );
		}
		return $labels;
	}

	public function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['wc_appointments_details_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_appointments_details_meta_box_nonce'], 'wc_appointments_details_meta_box' ) ) {
			return $post_id;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
			return $post_id;
		}

		global $wpdb, $post;

		// Save simple fields.
		$appointment_order_id	= absint( $_POST['_appointment_order_id'] );
		$appointment_status		= wc_clean( $_POST['_appointment_status'] );
		$customer_status		= wc_clean( $_POST['_appointment_customer_status'] );
		$customer_id			= absint( $_POST['_appointment_customer_id'] );
		$product_id				= wc_clean( $_POST['product_id'] );
		$staff_ids				= isset( $_POST['staff_ids'] ) ? wc_clean( $_POST['staff_ids'] ) : '';
		$parent_id				= absint( $_POST['_appointment_parent_id'] );
		$all_day				= isset( $_POST['_appointment_all_day'] ) ? '1' : '0';
		$quantity				= isset( $_POST['_appointment_qty'] ) ? absint( $_POST['_appointment_qty'] ) : 1;

		// Get the post object.
		$order = get_post( $post_id );
		$maybe_prefix_appointment_status = $appointment_status;

		// Only on shop_order.
		if ( 'shop_order' === $order->post_type ) {
			$maybe_prefix_appointment_status = 'wc-' . $appointment_status;

			// WC doesn't have unpaid so we need to account for it.
			if ( 'unpaid' === $appointment_status ) {
				$maybe_prefix_appointment_status = 'wc-pending';
			}
		}

		// Update post_parent and status via query to prevent endless loops.
		$wpdb->update( $wpdb->posts, array( 'post_parent' => $appointment_order_id ), array( 'ID' => $post_id ) );
		$wpdb->update( $wpdb->posts, array( 'post_status' => $maybe_prefix_appointment_status ), array( 'ID' => $post_id ) );

		// Update Customer Status
		$old_customer_status = get_post_meta( $post_id, '_appointment_customer_status', true );
		if ( $old_customer_status != $customer_status ) {
			update_post_meta( $post_id, '_appointment_customer_status', $customer_status );
		}

		// Old status.
		$old_status = $post->post_status;

		// Shortcut Trigger actions manually.
		do_action( 'woocommerce_appointment_before_' . $appointment_status, $post_id );
		do_action( 'woocommerce_appointment_before_' . $old_status . '_to_' . $appointment_status, $post_id );

		// Note in the order.
		if ( ( 'wc_appointment' == $post->post_type ) && $appointment_order_id && function_exists( 'wc_get_order' ) && ( $order = wc_get_order( $appointment_order_id ) ) && ( $old_status !== $appointment_status ) ) {
			$order->add_order_note( sprintf( __( 'Appointment #%1$d status changed manually from "%2$s" to "%3$s"', 'woocommerce-appointments' ), $post_id, $old_status, $appointment_status ) );
		}

		/*
		// Reschedule cron manually
    	if ( ( $post->post_type == 'wc_appointment' ) && $old_status !== $appointment_status ) {
			$appointment = get_wc_appointment( $post_id );
			$appointment->schedule_events();
    	}
		*/

		// Get product.
		$product = wc_get_product( $product_id );

		// Product has changed?
		$old_product_id = get_post_meta( $post_id, '_appointment_product_id', true );
		$old_product_id_exists = get_post_meta( $post_id, '_appointment_product_id_orig', true );

		if ( $old_product_id != $product_id && ! $old_product_id_exists ) {
			update_post_meta( $post_id, '_appointment_product_id_orig', $old_product_id );
		}

		// Staff IDs.
		delete_post_meta( $post_id, '_appointment_staff_id' );
		if ( is_array( $staff_ids ) ) {
			foreach ( $staff_ids as $staff_id ) {
				add_post_meta( $post_id, '_appointment_staff_id', $staff_id );
			}
		}

		// Product ID.
		update_post_meta( $post_id, '_appointment_product_id', $product_id );

		// Update meta.
		update_post_meta( $post_id, '_appointment_customer_id', $customer_id );
		update_post_meta( $post_id, '_appointment_parent_id', $parent_id );
		update_post_meta( $post_id, '_appointment_all_day', $all_day );

		// Quantity.
		$saved_qty = get_post_meta( $post_id, '_appointment_qty', true );

		if ( ! empty( $product ) && ! empty( $saved_qty ) ) {
			update_post_meta( $post_id, '_appointment_qty', $quantity );
		}

		// Do date and time magic and save them in one field.
		$start_date = explode( '-', wc_clean( $_POST['appointment_start_date'] ) );
		$end_date   = explode( '-', wc_clean( $_POST['appointment_end_date'] ) );
		$start_time = explode( ':', wc_clean( $_POST['appointment_start_time'] ) );
		$end_time   = explode( ':', wc_clean( $_POST['appointment_end_time'] ) );

		$start = mktime( $start_time[0], $start_time[1], 0, $start_date[1], $start_date[2], $start_date[0] );
		$end   = mktime( $end_time[0], $end_time[1], 0, $end_date[1], $end_date[2], $end_date[0] );

		update_post_meta( $post_id, '_appointment_start', date( 'YmdHis', $start ) );
		update_post_meta( $post_id, '_appointment_end', date( 'YmdHis', $end ) );

		if ( ! empty( $order ) && $appointment_order_id ) {

			// Update customer ID.
			if ( isset( $customer_id ) && $customer_id != $order->get_user_id() ) {
				// Make sure customer exists.
				if ( false === get_user_by( 'id', $customer_id ) ) {
					throw new Exception( __( 'Customer does not exist', 'woocommerce-appointments' ) );
				}

				update_post_meta( $order->id, '_customer_user', $customer_id );
			}

			// Update order metas.
			foreach ( $order->get_items() as $item_id => $item ) {
				$appointment_id = __( 'Appointment ID', 'woocommerce-appointments' );

				if ( 'line_item' != $item['type'] || ( isset( $item['item_meta'][ $appointment_id ] ) && is_array( $item['item_meta'][ $appointment_id ] ) && ! in_array( $post_id, $item['item_meta'][ $appointment_id ] ) ) ) {
					continue;
				}

				$is_all_day = isset( $_POST['_appointment_all_day'] ) && 'yes' == $_POST['_appointment_all_day'];

				if ( ! metadata_exists( 'order_item', $item_id, $appointment_id ) ) {
					wc_add_order_item_meta( $item_id, $appointment_id, intval( $post_id ) );
				}

				// Update product ID.
				if ( ! empty( $product ) ) {
					if ( metadata_exists( 'order_item', $item_id, '_product_id' ) ) {
						wc_update_order_item_meta( $item_id, '_product_id', $product_id );
						wc_update_order_item( $item_id, array( 'order_item_name' => $product->get_title() ) );
					}
				}

				// Update date.
				$date = mktime( 0, 0, 0, $start_date[1], $start_date[2], $start_date[0] );
				if ( metadata_exists( 'order_item', $item_id, __( 'Date', 'woocommerce-appointments' ) ) ) {
					wc_update_order_item_meta( $item_id, __( 'Date', 'woocommerce-appointments' ), date_i18n( wc_date_format(), $date ) );
				} else {
					wc_add_order_item_meta( $item_id, __( 'Date', 'woocommerce-appointments' ), date_i18n( wc_date_format(), $date ) );
				}

				// Update time.
				if ( ! $is_all_day ) {
					$time = mktime( $start_time[0], $start_time[1], 0, $start_date[1], $start_date[2], $start_date[0] );
					if ( metadata_exists( 'order_item', $item_id, __( 'Time', 'woocommerce-appointments' ) ) ) {
						wc_update_order_item_meta( $item_id, __( 'Time', 'woocommerce-appointments' ), date_i18n( wc_time_format(), $time ) );
					} else {
						wc_add_order_item_meta( $item_id, __( 'Time', 'woocommerce-appointments' ), date_i18n( wc_time_format(), $time ) );
					}
				}

				// Update staff
				$staff = wc_appointment_get_product_staff_member( $product_id, $staff_ids );
				$staff_members = array();
				foreach ( $staff as $staff_member ) {
					$staff_members[] = $staff_member->display_name;
				}
				$staff_members = implode( ', ', $staff_members );

				if ( metadata_exists( 'order_item', $item_id, __( 'Provider', 'woocommerce-appointments' ) ) ) {
					wc_delete_order_item_meta( $item_id, __( 'Provider', 'woocommerce-appointments' ) ); // delete to switch from Provider to Providers terminology
				}
				if ( metadata_exists( 'order_item', $item_id, __( 'Providers', 'woocommerce-appointments' ) ) ) {
					wc_update_order_item_meta( $item_id, __( 'Providers', 'woocommerce-appointments' ), $staff_members );
				} else {
					if ( ! empty( $staff_members ) ) {
						wc_add_order_item_meta( $item_id, __( 'Providers', 'woocommerce-appointments' ), $staff_members );
					}
				}

				// Update quantity.
				if ( ! empty( $product ) ) {
					if ( metadata_exists( 'order_item', $item_id, '_qty' ) ) {
						wc_update_order_item_meta( $item_id, '_qty', $quantity );
					}
				}

				// Update duration.
				$start_diff = wc_clean( $_POST['appointment_start_date'] );
				$end_diff   = wc_clean( $_POST['appointment_end_date'] );

				if ( ! $is_all_day ) {
					$start_diff .= ' ' . wc_clean( $_POST['appointment_start_time'] );
					$end_diff   .= ' ' . wc_clean( $_POST['appointment_end_time'] );
				}

				$start = new DateTime( $start_diff );
				$end   = new DateTime( $end_diff );

				// Add one day because DateTime::diff does not include the last day
				if ( $is_all_day ) {
					$end->modify( '+1 day' );
				}

				$diffs = $end->diff( $start );

				$duration = array();
				foreach ( $diffs as $type => $diff ) {
					if ( 0 != $diff ) {
						switch ( $type ) {
							case 'y':
								$duration[] = _n( '%y year', '%y years', $diff, 'woocommerce-appointments' );
								break;
							case 'm':
								$duration[] = _n( '%m month', '%m months', $diff, 'woocommerce-appointments' );
								break;
							case 'd':
								$duration[] = _n( '%d day', '%d days', $diff, 'woocommerce-appointments' );
								break;
							case 'h':
								$duration[] = _n( '%h hour', '%h hours', $diff, 'woocommerce-appointments' );
								break;
							case 'i':
								$duration[] = _n( '%i minute', '%i minutes', $diff, 'woocommerce-appointments' );
								break;
						}
					}
				}

				$duration = implode( ', ', $duration );
				$duration = $diffs->format( $duration );

				if ( metadata_exists( 'order_item', $item_id, __( 'Duration', 'woocommerce-appointments' ) ) ) {
					wc_update_order_item_meta( $item_id, __( 'Duration', 'woocommerce-appointments' ), $duration );
				} else {
					if ( ! empty( $duration ) ) {
						wc_add_order_item_meta( $item_id, __( 'Duration', 'woocommerce-appointments' ), $duration );
					}
				}
			}
		}

		// Trigger actions manually
		do_action( 'woocommerce_appointment_' . $appointment_status, $post_id );
		do_action( 'woocommerce_appointment_' . $old_status . '_to_' . $appointment_status, $post_id );
		clean_post_cache( $post_id );

		WC_Cache_Helper::get_transient_version( 'appointments', true );

		do_action( 'woocommerce_appointment_process_meta', $post_id );
	}
}

return new WC_Appointments_Admin_Details_Meta_Box();
