<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Google Calendar Integration.
 */
class WC_Appointments_Integration_GCal extends WC_Settings_API {

	/**
	 * @var WC_Appointments_Integration_GCal The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Appointments_Integration_GCal Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		$this->plugin_id			= 'wc_appointments_';
		$this->id					= 'gcal';
		$this->method_title			= __( 'Google Calendar Sync', 'woocommerce-appointments' );

		// API.
		$this->oauth_uri			= 'https://accounts.google.com/o/oauth2/';
		$this->calendars_uri		= 'https://www.googleapis.com/calendar/v3/calendars/';
		$this->api_scope			= 'https://www.googleapis.com/auth/calendar';
		$this->redirect_uri			= WC()->api_request_url( 'wc_appointments_oauth_redirect' );
		$this->callback_uri			= WC()->api_request_url( 'wc_appointments_callback_read' );

		// User set variables.
		$this->client_id			= $this->get_option( 'client_id' );
		$this->client_secret		= $this->get_option( 'client_secret' );
		$this->calendar_id			= $this->get_option( 'calendar_id' );
		$this->debug				= $this->get_option( 'debug' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Actions.
		add_action( 'woocommerce_api_wc_appointments_oauth_redirect' , array( $this, 'oauth_redirect' ) );
		add_action( 'woocommerce_api_wc_appointments_callback_read' , array( $this, 'callback_read' ) );

		// Sync all statuses, but limit inside maybe_sync_to_gcal_from_status() function.
		foreach ( get_wc_appointment_statuses() as $status ) {
			add_action( 'woocommerce_appointment_' . $status, array( $this, 'sync_new_appointment' ) );
		}

		add_action( 'woocommerce_appointment_cancelled', array( $this, 'remove_from_gcal' ) );
		add_action( 'woocommerce_appointment_process_meta', array( $this, 'sync_edited_appointment' ) );
		add_action( 'trashed_post', array( $this, 'remove_from_gcal' ) );
		add_action( 'untrashed_post', array( $this, 'sync_unstrashed_appointment' ) );
		add_action( 'wc-appointment-sync-from-gcal', array( $this, 'sync_from_gcal' ) );
		add_action( 'wc-appointment-sync-full-from-gcal', array( $this, 'sync_full_from_gcal' ) );

		// Notices.
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		// Schedule incremental sync each hour.
		if ( ! wp_next_scheduled( 'wc-appointment-sync-from-gcal' ) ) {
			wp_schedule_event( time(), apply_filters( 'woocommerce_appointments_sync_from_gcal', 'hourly' ), 'wc-appointment-sync-from-gcal' );
		}

		// Schedule full sync each day.
		if ( ! wp_next_scheduled( 'wc-appointment-sync-full-from-gcal' ) ) {
			wp_schedule_event( time(), apply_filters( 'woocommerce_appointments_sync_full_from_gcal', 'daily' ), 'wc-appointment-sync-full-from-gcal' );
		}

		// Active logs.
		if ( 'yes' == $this->debug ) {
			if ( class_exists( 'WC_Logger' ) ) {
				$this->log = new WC_Logger();
			} else {
				$this->log = WC()->logger();
			}
		}
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'client_id' => array(
				'title'       => __( 'Client ID', 'woocommerce-appointments' ),
				'type'        => 'text',
				'description' => __( 'Your Google Client ID.', 'woocommerce-appointments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'client_secret' => array(
				'title'       => __( 'Client Secret', 'woocommerce-appointments' ),
				'type'        => 'text',
				'description' => __( 'Your Google Client Secret.', 'woocommerce-appointments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'calendar_id' => array(
				'title'       => __( 'Calendar ID', 'woocommerce-appointments' ),
				'type'        => 'text',
				'description' => __( 'Your Google Calendar ID.', 'woocommerce-appointments' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'authorization' => array(
				'title'       => __( 'Authorization', 'woocommerce-appointments' ),
				'type'        => 'gcal_authorization',
			),
			'testing' => array(
				'title'       => __( 'Testing', 'woocommerce-appointments' ),
				'type'        => 'title',
				'description' => '',
			),
			'debug' => array(
				'title'       => __( 'Debug Log', 'woocommerce-appointments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable logging', 'woocommerce-appointments' ),
				'default'     => 'no',
				'description' => sprintf( __( 'Log Google Calendar events, such as API requests, inside %s', 'woocommerce-appointments' ), '<code>woocommerce/logs/' . $this->id . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.txt</code>' ),
			),
		);
	}

	/**
	 * Validate the GCal Authorization field.
	 *
	 * @param  mixed $key
	 * @return string
	 */
	public function validate_gcal_authorization_field( $key ) {
		return '';
	}

	/**
	 * Generate the GCal Authorization field.
	 *
	 * @param  mixed $key
	 * @param  array $data
	 *
	 * @return string
	 */
	public function generate_gcal_authorization_html( $key, $data ) {
		$options       = $this->plugin_id . $this->id . '_';
		$id            = $options . $key;
		$client_id     = isset( $_POST[ $options . 'client_id' ] ) ? sanitize_text_field( $_POST[ $options . 'client_id' ] ) : $this->client_id;
		$client_secret = isset( $_POST[ $options . 'client_secret' ] ) ? sanitize_text_field( $_POST[ $options . 'client_secret' ] ) : $this->client_secret;
		$calendar_id   = isset( $_POST[ $options . 'calendar_id' ] ) ? sanitize_text_field( $_POST[ $options . 'calendar_id' ] ) : $this->calendar_id;
		$access_token  = $this->get_access_token();

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $data['title'] ); ?>
			</th>
			<td class="forminp">
				<?php
					if ( ! $access_token && ( $client_id && $client_secret && $calendar_id ) ) :
						$oauth_url = add_query_arg(
							array(
								'scope'           => $this->api_scope,
								'redirect_uri'    => $this->redirect_uri,
								'response_type'   => 'code',
								'client_id'       => $client_id,
								'approval_prompt' => 'force',
								'access_type'     => 'offline',
							),
							$this->oauth_uri . 'auth'
						);
			 	?>
					<p class="submit"><a class="button" href="<?php echo esc_url( $oauth_url ); ?>"><?php _e( 'Connect with Google', 'woocommerce-appointments' ); ?></a></p>
				<?php elseif ( $access_token ) : ?>
					<p><?php _e( 'Successfully authenticated.', 'woocommerce-appointments' ); ?></p>
					<p class="submit"><a class="button" href="<?php echo esc_url( add_query_arg( array( 'logout' => 'true' ), $this->redirect_uri ) ); ?>"><?php _e( 'Disconnect', 'woocommerce-appointments' ); ?></a></p>
				<?php else : ?>
					<p><?php _e( 'Unable to authenticate, you must enter with your <strong>Client ID</strong>, <strong>Client Secret</strong> and <strong>Calendar ID</strong>.', 'woocommerce-appointments' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Admin Options.
	 *
	 * @return string
	 */
	public function admin_options() {
		echo '<h3>' . $this->method_title . '</h3>';
		echo '<p>' . sprintf( __( 'To use this integration go through %s instructions.', 'woocommerce-appointments' ), '<a href="https://bizzthemes.com/help/setup/wc-appointments/google-calendar-integration/" target="_blank">' . __( 'Google Calendar Integration', 'woocommerce-appointments' ) . '</a>' ) . '</p>';
		echo '<table class="form-table">';
			$this->generate_settings_html();
		echo '</table>';
		echo '<div><input type="hidden" name="section" value="' . $this->id . '" /></div>';
	}

	/**
	 * Get Access Token.
	 *
	 * @param  string $code Authorization code.
	 *
	 * @return string       Access token.
	 */
	protected function get_access_token( $code = '' ) {

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Getting Google API Access Token...' );
		}

		$access_token = get_transient( 'wc_appointments_gcal_access_token' );

		if ( ! $code && false !== $access_token ) {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Access Token recovered by transients: ' . $access_token );
			}

			return $access_token;
		}

		$refresh_token = get_option( 'wc_appointments_gcal_refresh_token' );

		if ( ! $code && $refresh_token ) {

			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Generating a new Access Token...' );
			}

			$data = array(
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'refresh_token' => $refresh_token,
				'grant_type'    => 'refresh_token',
			);

			$params = array(
				'body'      => http_build_query( $data ),
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			);

			$response = wp_remote_post( $this->oauth_uri . 'token', $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				$response_data = json_decode( $response['body'] );
				$access_token  = sanitize_text_field( $response_data->access_token );

				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Google API Access Token generated successfully: ' . $access_token );
				}

				// Set the transient.
				set_transient( 'wc_appointments_gcal_access_token', $access_token, 3500 );

				return $access_token;
			} else {
				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while generating the Access Token: ' . var_export( $response, true ) );
				}
			}
		} elseif ( '' != $code ) {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Renewing the Access Token...' );
			}

			$data = array(
				'code'          => $code,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'redirect_uri'  => $this->redirect_uri,
				'grant_type'    => 'authorization_code',
			);

			$params = array(
				'body'      => http_build_query( $data ),
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			);

			$response = wp_remote_post( $this->oauth_uri . 'token', $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				$response_data = json_decode( $response['body'] );
				$access_token  = sanitize_text_field( $response_data->access_token );

				// Add refresh token.
				update_option( 'wc_appointments_gcal_refresh_token', $response_data->refresh_token );

				// Set the transient.
				set_transient( 'wc_appointments_gcal_access_token', $access_token, 3500 );

				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Google API Access Token renewed successfully: ' . $access_token );
				}

				return $access_token;
			} else {
				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while renewing the Access Token: ' . var_export( $response, true ) );
				}
			}
		}

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Failed to retrieve and generate the Access Token' );
		}

		return '';
	}

	/**
	 * OAuth Logout.
	 *
	 * @return bool
	 */
	protected function oauth_logout() {
		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Leaving the Google Calendar app...' );
		}

		$refresh_token = get_option( 'wc_appointments_gcal_refresh_token' );

		if ( $refresh_token ) {
			$params = array(
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type' => 'application/x-www-form-urlencoded',
				),
			);

			$response = wp_remote_get( $this->oauth_uri . 'revoke?token=' . $refresh_token, $params );

			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
				delete_option( 'wc_appointments_gcal_refresh_token' );
				delete_transient( 'wc_appointments_gcal_access_token' );

				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Leave the Google Calendar app successfully' );
				}

				return true;
			} else {
				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error when leaving the Google Calendar app: ' . var_export( $response, true ) );
				}
			}
		}

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Failed to leave the Google Calendar app' );
		}

		return false;
	}

	/**
	 * Process the oauth redirect.
	 *
	 * @return void
	 */
	public function oauth_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied!', 'woocommerce-appointments' ) );
		}

		$redirect_args = array(
			'page'    => 'wc-settings',
			'tab'     => 'appointments',
			'section' => $this->id,
		);

		// OAuth.
		if ( isset( $_GET['code'] ) ) {
			$code         = sanitize_text_field( $_GET['code'] );
			$access_token = $this->get_access_token( $code );

			if ( '' != $access_token ) {
				$redirect_args['wc_gcal_oauth'] = 'success';

				wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
				exit;
			}
		}
		if ( isset( $_GET['error'] ) ) {

			$redirect_args['wc_gcal_oauth'] = 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		// Logout.
		if ( isset( $_GET['logout'] ) ) {
			$logout = $this->oauth_logout();
			$redirect_args['wc_gcal_logout'] = ( $logout ) ? 'success' : 'fail';

			wp_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ), 301 );
			exit;
		}

		wp_die( __( 'Invalid request!', 'woocommerce-appointments' ) );
	}

	/**
	 * Display admin screen notices.
	 *
	 * @return string
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['wc_gcal_oauth'] ) ) {
			if ( 'success' == $_GET['wc_gcal_oauth'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-appointments' ) . '</strong> ' . __( 'Account connected successfully!', 'woocommerce-appointments' ) . '</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-appointments' ) . '</strong> ' . __( 'Failed to connect to your account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'woocommerce-appointments' ) . '</p></div>';
			}
		}

		if ( 'woocommerce_page_wc-settings' == $screen->id && isset( $_GET['wc_gcal_logout'] ) ) {
			if ( 'success' == $_GET['wc_gcal_logout'] ) {
				echo '<div class="updated fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-appointments' ) . '</strong> ' . __( 'Account disconnected successfully!', 'woocommerce-appointments' ) . '</p></div>';
			} else {
				echo '<div class="error fade"><p><strong>' . __( 'Google Calendar', 'woocommerce-appointments' ) . '</strong> ' . __( 'Failed to disconnect to your account, please try again, if the problem persists, turn on Debug Log option and see what is happening.', 'woocommerce-appointments' ) . '</p></div>';
			}
		}
	}

	/**
	 * Sync new Appointment with GCal.
	 *
	 * @param  int $appointment_id Appointment ID
	 * @return void
	 */
	public function sync_new_appointment( $appointment_id ) {
		if ( $this->is_edited_from_meta_box() ) {
			return;
		}

		$this->maybe_sync_to_gcal_from_status( $appointment_id );
	}

	/**
	 * Sync Appointment to GCal
	 *
	 * @param  int $appointment_id Appointment ID
	 * @return void
	 */
	public function sync_to_gcal( $appointment_id ) {
		$event_id     = get_post_meta( $appointment_id, '_wc_appointments_gcal_event_id', true );
		$appointment  = get_wc_appointment( $appointment_id );
		// Deprecated and will eventually be replaced with just: $calendar_id = $this->calendar_id ? $this->calendar_id : ''.
		$product      = $appointment->get_product();
		$product_id	  = is_object( $product ) ? get_post_meta( $product->id, '_wc_appointments_gcal_calendar_id', true ) : '';
		$calendar_id  = $this->calendar_id ? $this->calendar_id : $product_id;
		// Deprecated: end
		$order        = $appointment->get_order();
		$order_id  	  = get_post_meta( $appointment_id, '_appointment_order_item_id', true );
		$customer	  = $appointment->get_customer();
		$timezone     = wc_appointment_get_timezone_string();
		$summary      = sprintf( __( 'Appointment #%s', 'woocommerce-appointments' ), $appointment_id );
		$description  = '';

		// Add customer name.
		if ( $customer && $customer->name ) {
			$description .= sprintf( '%s: %s', __( 'Customer', 'woocommerce-appointments' ), $customer->name ) . PHP_EOL;
		} else {
			$description .= sprintf( '%s: %s', __( 'Customer', 'woocommerce-appointments' ), __( 'Guest', 'woocommerce-appointments' ) ) . PHP_EOL;
		}

		// Product name.
		if ( is_object( $product ) && isset( $product->post->post_title ) ) {
			$description .= sprintf( '%s: %s', __( 'Product', 'woocommerce-appointments' ), $product->post->post_title ) . PHP_EOL;
		}

		// If order exists for this appointment, fill the data.
		if ( $order ) {

			$order_items  = $order->get_items();

			// Need order items.
			if ( $order_items ) {
				foreach ( $order->get_items() as $item_id => $item ) {
					if ( 'line_item' != $item['type'] ) {
						continue;
					}

					// Prevent duplicates when multiple appointments are added to same order.
					if ( $item_id != $order_id ) {
						continue;
					}

					if ( $metadata = $order->has_meta( $item_id ) ) {
						foreach ( $metadata as $meta ) {

							// Skip hidden core fields.
							if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
								'_qty',
								'_tax_class',
								'_product_id',
								'_variation_id',
								'_line_subtotal',
								'_line_subtotal_tax',
								'_line_total',
								'_line_tax',
							) ) ) ) {
								continue;
							}

							// Appointment fields.
							if ( in_array( $meta['meta_key'], array( __( 'Appointment Date', 'woocommerce-appointments' ), __( 'Appointment Time', 'woocommerce-appointments' ) ) ) ) {
								continue;
							}

							$meta_value = $meta['meta_value'];

							// Skip serialised meta.
							if ( is_serialized( $meta_value ) ) {
								continue;
							}

							// Get attribute data.
							if ( taxonomy_exists( $meta['meta_key'] ) ) {
								global $wpdb;
								$term           = get_term_by( 'slug', $meta['meta_value'], $meta['meta_key'] );
								$attribute_name = str_replace( 'pa_', '', wc_clean( $meta['meta_key'] ) );
								$attribute      = $wpdb->get_var(
									$wpdb->prepare( "
											SELECT attribute_label
											FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
											WHERE attribute_name = %s;
										",
										$attribute_name
									)
								);

								$meta['meta_key']   = ( ! is_wp_error( $attribute ) && $attribute ) ? $attribute : $attribute_name;
								$meta['meta_value'] = ( isset( $term->name ) ) ? $term->name : $meta['meta_value'];
							}

							$description .= sprintf( __( '%1$s: %2$s', 'woocommerce-appointments' ), rawurldecode( html_entity_decode( $meta['meta_key'] ) ), rawurldecode( html_entity_decode( $meta_value ) ) ) . PHP_EOL;
		 				}
					}
				}
			}
		}

		// Set the event data.
		$data = array(
			'summary'     => wp_kses_post( $summary ),
			'description' => wp_kses_post( $description ),
		);

		// Set the event start and end dates.
		if ( $appointment->is_all_day() ) {
			$data['end'] = array(
				'date' => date( 'Y-m-d', ( $appointment->end + 1440 ) ),
			);

			$data['start'] = array(
				'date' => date( 'Y-m-d', $appointment->start ),
			);
		} else {
			$data['end'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $appointment->end ),
				'timeZone' => $timezone,
			);

			$data['start'] = array(
				'dateTime' => date( 'Y-m-d\TH:i:s', $appointment->start ),
				'timeZone' => $timezone,
			);
		}

		$data = apply_filters( 'woocommerce_appointments_gcal_sync', $data, $appointment );

		// Make sure calendar has correct ID, move if necessary.
		$old_product_id_exists = get_post_meta( $appointment_id, '_appointment_product_id_orig', true );

		if ( $old_product_id_exists ) {

			$old_calendar_id  = get_post_meta( $old_product_id_exists, '_wc_appointments_gcal_calendar_id', true );

			$params_move = array(
				'method'    => 'POST',
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_access_token(),
				),
			);

			$response_move = wp_remote_post( $this->calendars_uri . $old_calendar_id . '/events/' . $event_id . '/move?destination=' . $calendar_id, $params_move );

			if ( ! is_wp_error( $response_move ) && 200 == $response_move['response']['code'] && 'OK' == $response_move['response']['message'] ) {
				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Appointment successfully moved to another product!' );
				}

				// Delete old move token
				delete_post_meta( $appointment_id, '_appointment_product_id_orig' );

			} else {
				// Debug.
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while moving the appointment #' . $appointment_id . ': ' . var_export( $response_move, true ) );
				}
			}
		}

		// Connection params.
		$params = array(
			'method'    => 'POST',
			'body'      => wp_json_encode( $data ),
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
		);

		// Update event.
		if ( $event_id ) {
			$params['method'] = 'PUT';
		}

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Synchronizing appointment #' . $appointment->id . ' with Google Calendar...' );
		}

		$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events/' . $event_id, $params );

		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Appointment synchronized successfully!' );
			}

			// Updated the GCal event ID
			$response_data = json_decode( $response['body'], true );
			update_post_meta( $appointment->id, '_wc_appointments_gcal_event_id', $response_data['id'] );

		} else {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while synchronizing the appointment #' . $appointment->id . ': ' . var_export( $response, true ) );
			}
		}
	}

	/**
	 * Read GCal callbacks in live 2-way sync
	 *
	 * @return void
	 */
	public function sync_callback() {
		$calendar_id  = $this->calendar_id ? $this->calendar_id : '';
		$callback_id  = get_option( '_wc_appointments_gcal_callback_id' );
		$callback_rid = get_option( '_wc_appointments_gcal_callback_resourceid' );

		// If callback ID's exist, abort.
		if ( $callback_id && $callback_rid ) {
			return;
		}

		// Random ID.
		$generate_rand_id = wp_generate_password( 12, false );

		// Create callback ID if it doesn't exist yet.
		$data = array(
			'id' => $generate_rand_id,
			'type' => 'web_hook',
			'address' => $this->callback_uri,
		);

		// Connection params.
		$params = array(
			'method'    => 'POST',
			'body'      => wp_json_encode( $data ),
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
		);

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Synchronizing callback for calendar #' . $calendar_id . ' callback with Google Calendar...' );
		}

		$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events/watch', $params );

		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'calendar callback synchronized successfully!' );
			}

			// Json decode response.
			$response_data = json_decode( $response['body'], true );

			// Update the GCal event ID
			update_option( '_wc_appointments_gcal_callback_id', $response_data['id'] );
			update_option( '_wc_appointments_gcal_callback_resourceid', $response_data['resourceId'] );
		} else {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while synchronizing callback for the calendar #' . $calendar_id . ': ' . var_export( $response, true ) );
			}
		}

	}

	/**
	 * Process the oauth redirect.
	 *
	 * Read max 250 records at one time.
	 *
	 * @return void
	 */
	public function callback_read() {
		// Leave if callback not registered.
		if ( ! isset( $_SERVER['HTTP_X_GOOG_RESOURCE_ID'] ) ) {
			return;
		}

		// Get callback resource ID.
		$callback_rid = get_option( '_wc_appointments_gcal_callback_resourceid' );

		// Leave if callback resource ID doesn't exist.
		if ( ! $callback_rid ) {
			return;
		}

		// General params for listing events.
		$params = array(
			'method'    => 'GET',
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
		);

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Synchronizing appointments from Google Calendar...' );
		}

		/**
		 * Get sync token if it exists.
		 *
		 * Token obtained from the nextSyncToken field returned on the last page of results from the previous list request.
		 * It makes the result of this list request contain only entries that have changed since then.
		 * All events deleted since the previous list request will always be in the result set and it is not allowed to set showDeleted to False.
		 * There are several query parameters that cannot be specified together with nextSyncToken to ensure consistency of the client state.
		 *
		 * @return string
		 */
		$calendar_sync_token = rawurlencode( get_option( 'wc_appointments_gcal_sync_token' ) );

		// Apply sync token from previous update.
		if ( $calendar_sync_token ) {
			$response = wp_remote_post( $_SERVER['HTTP_X_GOOG_RESOURCE_URI'] . '&singleEvents=true&syncToken=' . $calendar_sync_token, $params );
		} else {
			$response = wp_remote_post( $_SERVER['HTTP_X_GOOG_RESOURCE_URI'] . '&singleEvents=true', $params );
		}

		/**
		 * If the syncToken expires, the server will respond with a 410 GONE response code
		 * and the client should clear its storage and perform a full synchronization without any syncToken.
		 */
		if ( 410 == $response['response']['code'] ) {
			// Delete sync token.
			delete_option( '_wc_appointments_gcal_sync_token' );

			// Perform a full synchronization without any syncToken.
			$response = wp_remote_post( $_SERVER['HTTP_X_GOOG_RESOURCE_URI'] . '&singleEvents=true', $params );
		} else {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while reading a callback: ' . var_export( $response, true ) );
			}
		}

		// List the events.
		$this->list_events_from_gcal( $response, $calendar_sync_token );

	}

	/**
	 * Sync Appointment with GCal when appointment is edited.
	 *
	 * @param  int $appointment_id Appointment ID
	 * @return void
	 */
	public function sync_edited_appointment( $appointment_id ) {
		if ( ! $this->is_edited_from_meta_box() ) {
			return;
		}

		$this->maybe_sync_to_gcal_from_status( $appointment_id );
	}

	/**
	 * Sync Appointment with GCal when appointment is untrashed.
	 *
	 * @param  int $appointment_id Appointment ID
	 *
	 * @return void
	 */
	public function sync_unstrashed_appointment( $appointment_id ) {
		$this->maybe_sync_to_gcal_from_status( $appointment_id );
	}

	/**
	 * Remove/cancel the appointment in GCal
	 *
	 * @param  int $appointment_id Appointment ID
	 * @return void
	 */
	public function remove_from_gcal( $appointment_id ) {
		$appointment	= get_wc_appointment( $appointment_id );
		// Deprecated and will eventually be replaced with just: $calendar_id = $this->calendar_id ? $this->calendar_id : ''.
		$product		= $appointment->get_product();
		$product_id	    = is_object( $product ) ? get_post_meta( $product->id, '_wc_appointments_gcal_calendar_id', true ) : '';
		$calendar_id    = $this->calendar_id ? $this->calendar_id : $product_id;
		// Deprecated: end
		$event_id 		= get_post_meta( $appointment_id, '_wc_appointments_gcal_event_id', true );

		if ( $event_id ) {
			$params = array(
				'method'    => 'DELETE',
				'sslverify' => false,
				'timeout'   => 60,
				'headers'   => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $this->get_access_token(),
				),
			);

			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Removing appointment #' . $appointment_id . ' with Google Calendar...' );
			}

			$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events/' . $event_id, $params );

			if ( ! is_wp_error( $response ) && 204 == $response['response']['code'] ) {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Appointment removed successfully!' );
				}

				// Remove event ID
				delete_post_meta( $appointment_id, '_wc_appointments_gcal_event_id' );

			} else {
				if ( 'yes' == $this->debug ) {
					$this->log->add( $this->id, 'Error while removing the appointment #' . $appointment_id . ': ' . var_export( $response, true ) );
				}
			}
		}
	}

	/**
	 * Maybe remove / sync appointment based on appointment status.
	 *
	 * @param int $appointment_id Appointment ID
	 * @return void
	 */
	public function maybe_sync_to_gcal_from_status( $appointment_id ) {
		global $wpdb;

		$status = $wpdb->get_var( $wpdb->prepare( "SELECT post_status FROM $wpdb->posts WHERE post_type = 'wc_appointment' AND ID = %d", $appointment_id ) );

		if ( 'cancelled' == $status ) {
			$this->remove_from_gcal( $appointment_id );
		} elseif ( in_array( $status, apply_filters( 'woocommerce_appointments_gcal_sync_statuses', array( 'confirmed', 'paid', 'complete' ) ) ) ) {
			$this->sync_to_gcal( $appointment_id );
		}
	}

	/**
	 * Is edited from post.php's meta box.
	 *
	 * @return bool
	 */
	public function is_edited_from_meta_box() {
		return (
			! empty( $_POST['wc_appointments_details_meta_box_nonce'] )
			&&
			wp_verify_nonce( $_POST['wc_appointments_details_meta_box_nonce'], 'wc_appointments_details_meta_box' )
		);
	}

	/**
	 * Sync back all events from GCal by deleting the sync token and forcing full sync.
	 *
	 * Read max 250 records at one time.
	 *
	 * @return void
	 */
	public function sync_full_from_gcal() {
		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Deleting sync token and creating full sync from Google Calendar...' );
		}

		// Delete sync token.
		delete_option( 'wc_appointments_gcal_sync_token' );

		// Reschedule again...
		wp_clear_scheduled_hook( 'wc-appointment-sync-from-gcal' );
		wp_schedule_event( time(), apply_filters( 'woocommerce_appointments_sync_from_gcal', 'hourly' ), 'wc-appointment-sync-from-gcal' );

		// Make sure live sync is connected.
		$this->sync_callback();
	}

	/**
	 * Sync back events from GCal, use sync token for partial sync of updated events only.
	 *
	 * @return void
	 */
	public function sync_from_gcal() {
		$calendar_id		= $this->calendar_id ? $this->calendar_id : '';

		// Stop here if calendar ID is not set.
		if ( ! $calendar_id ) {
			return;
		}

		// Get site TimeZone.
		$wp_appointments_timezone = wc_appointment_get_timezone_string();

		// Define parameters to call GCal for list of events.
		$params = array(
			'method'    => 'GET',
			'sslverify' => false,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->get_access_token(),
			),
		);

		// Debug.
		if ( 'yes' == $this->debug ) {
			$this->log->add( $this->id, 'Creating sync from Google Calendar...' );
		}

		/**
		 * Get sync token if it exists.
		 *
		 * Token obtained from the nextSyncToken field returned on the last page of results from the previous list request.
		 * It makes the result of this list request contain only entries that have changed since then.
		 * All events deleted since the previous list request will always be in the result set and it is not allowed to set showDeleted to False.
		 * There are several query parameters that cannot be specified together with nextSyncToken to ensure consistency of the client state.
		 *
		 * @return string
		 */
		$calendar_sync_token = rawurlencode( get_option( 'wc_appointments_gcal_sync_token' ) );

		// Don't sync events older than now.
		$timeMin = new DateTime();
		$timeMin->setTimezone( new DateTimeZone( $wp_appointments_timezone ) );
		$timeMin = $timeMin->format( \DateTime::RFC3339 );
		$timeMin = rawurlencode( $timeMin );

		// Perform a synchronization only for events that have been updated.
		if ( $calendar_sync_token ) {
			$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events' . "?singleEvents=true&syncToken=$calendar_sync_token", $params );
		// Perform a full synchronization without any syncToken.
		} else {
			$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events' . "?singleEvents=true&timeMin=$timeMin", $params );
		}

		/**
		 * If the syncToken expires, the server will respond with a 410 GONE response code
		 * and the client should clear its storage and perform a full synchronization without any syncToken.
		 */
		if ( ! is_wp_error( $response ) && 410 == $response['response']['code'] ) {
			// Delete sync token.
			delete_option( 'wc_appointments_gcal_sync_token' );

			// Perform a full synchronization without any syncToken.
			$response = wp_remote_post( $this->calendars_uri . $calendar_id . '/events' . "?singleEvents=true&timeMin=$timeMin", $params );
		} else {
			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while synchronizing from Google Calendar: ' . var_export( $response, true ) );
			}
		}

		// List the events.
		$this->list_events_from_gcal( $response, $calendar_sync_token );
	}

	/**
	 * List and sync all events from GCal.
	 *
	 * @param  array $response
	 * @param  string $calendar_sync_token
	 * @return void
	 */
	public function list_events_from_gcal( $response, $calendar_sync_token ) {
		// Get site TimeZone.
		$wp_appointments_timezone = wc_appointment_get_timezone_string();

		// List the events.
		if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && 'OK' == $response['response']['message'] ) {

			// Debug.
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Sync from Google Calendar is successful...' );
			}

			// Get response data.
			$response_data = json_decode( $response['body'], true );

			// Set sync token for the first time.
			if ( ! $calendar_sync_token ) {
				update_option( 'wc_appointments_gcal_sync_token', $response_data['nextSyncToken'] );
			}

			// Sync appointments.
			if ( is_array( $response_data['items'] ) && ! empty( $response_data['items'] ) ) {

				// Sync all events in loop.
				foreach ( $response_data['items'] as $data ) {

					// Check if event is already synced with existing product.
					$args = array(
						'meta_query'        => array(
							array(
								'key'       => '_wc_appointments_gcal_event_id',
								'value'     => $data['id'],
							),
						),
						'no_found_rows'     => true,
						'update_post_meta_cache' => false,
						'post_type'         => 'wc_appointment',
						'posts_per_page'    => '1',
					);

					$get_posts = new WP_Query();
					$posts = $get_posts->query( $args );
					$appointment_id = ( isset( $posts[0]->ID ) ) ? $posts[0]->ID : '';

					// Update existing appointment data.
					if ( $appointment_id ) {
						// Get appointment object.
						$appointment = get_wc_appointment( $appointment_id );

						// When event is deleted inside GCal set appointment status to cancelled and go to next event.
						if ( 'cancelled' == $data['status'] ) {
							// Update appointment status to cancelled.
							$appointment->update_status( 'cancelled' );

							// Remove event ID.
							delete_post_meta( $appointment_id, '_wc_appointments_gcal_event_id' );

							// Go to next event.
							continue;
						}
					}

					// When event is deleted inside GCal go to next event.
					if ( 'cancelled' == $data['status'] ) {
						continue;
					}

					// Update start time.
					if ( isset( $data['start']['dateTime'] ) ) {
						$start_date = new DateTime( $data['start']['dateTime'] );
						$start_date->setTimezone( new DateTimeZone( $wp_appointments_timezone ) );
						$sync_data['start'] = date( 'YmdHis', strtotime( $start_date->format( 'Y-m-d\TH:i:s\Z' ) ) );
						$sync_data['start_raw'] = strtotime( $start_date->format( 'Y-m-d\TH:i:s\Z' ) );
						$sync_data['_start_date'] = strtotime( $start_date->format( 'Y-m-d H:i:s' ) );
					} else {
						$start_date = new DateTime( $data['start']['date'] );
						$start_date->setTimezone( new DateTimeZone( $wp_appointments_timezone ) );
						$sync_data['start'] = date( 'YmdHis', strtotime( $data['start']['date'] ) );
						$sync_data['start_raw'] = strtotime( $data['start']['date'] );
						$sync_data['_start_date'] = strtotime( $start_date->format( 'Y-m-d' ) );
					}

					// Update end time.
					if ( isset( $data['end']['dateTime'] ) ) {
						$end_date = new DateTime( $data['end']['dateTime'] );
						$end_date->setTimezone( new DateTimeZone( $wp_appointments_timezone ) );
						$sync_data['end'] = date( 'YmdHis', strtotime( $end_date->format( 'Y-m-d\TH:i:s\Z' ) ) );
						$sync_data['end_raw'] = strtotime( $end_date->format( 'Y-m-d\TH:i:s\Z' ) );
						$sync_data['_end_date'] = strtotime( $end_date->format( 'Y-m-d H:i:s' ) );
					} else {
						$end_date = new DateTime( $data['end']['date'] );
						$end_date->setTimezone( new DateTimeZone( $wp_appointments_timezone ) );
						$sync_data['end'] = date( 'YmdHis', strtotime( $data['end']['date'] ) );
						$sync_data['end_raw'] = strtotime( $data['end']['date'] );
						$sync_data['_end_date'] = strtotime( $end_date->format( 'Y-m-d' ) );
					}

					// Check if appointment with same start/end time already synced
					$args2 = array(
						'post_type'					=> 'wc_appointment',
						'no_found_rows'				=> true,
						'update_post_meta_cache'	=> false,
						'meta_query' 				=> array(
							'relation' => 'AND',
							array(
								'key'       => '_appointment_start',
								'value'     => $sync_data['start'],
								'compare' 	=> '=',
							),
							array(
								'key'       => '_appointment_end',
								'value'     => $sync_data['end'],
								'compare' 	=> '=',
							),
							array(
								'key'       => '_appointment_product_id',
								'value'     => wc_appointments_gcal_synced_product_id(),
								'compare' 	=> '=',
							),
						),
					);

					$get_posts2 = new WP_Query();
					$existing_appointments = $get_posts2->query( $args2 );

					// Remove duplicates
					if ( $existing_appointments ) {

						// Trash all matching appointments except first one.
						if ( count( $existing_appointments ) > 1 ) {
							$count = 0;
							foreach ( $existing_appointments as $existing_appointment ) {
								// skip first one.
								if ( 1 == $count++ ) {
									continue;
								}

								// trash all duplicated.
								wp_trash_post( $existing_appointment->ID );

								// remove event ID.
								delete_post_meta( $existing_appointment->ID, '_wc_appointments_gcal_event_id' );
							}
						}

						// Go to next event if current already synced.
						continue;
					}

					// Update all day.
					$sync_data['all_day'] = ( isset( $data['start']['date'] ) && isset( $data['end']['date'] ) ) ? 1 : 0;

					// Update existing appointment data.
					if ( $appointment_id ) {

						// Prepare meta for updating.
						$meta_args = apply_filters( 'wc_appointments_gcal_sync_order_itemmeta', array(
							'_appointment_start'         => $sync_data['start'],
							'_appointment_end'           => $sync_data['end'],
							'_appointment_all_day'       => intval( $sync_data['all_day'] ),
						), $appointment_id, $data );

						// Apply update from GCal.
						foreach ( $meta_args as $key => $value ) {
							update_post_meta( $appointment_id, $key, $value );
						}

					// Add NEW appointment CREATED inside GCal and which doesn't exist yet on the site.
					} else {

						// Data to go into the appointment.
						$new_appointment_data = apply_filters( 'woocommerce_appointments_new_appointment_data_from_gcal', array(
							'user_id'			=> '',
							'product_id'		=> wc_appointments_gcal_synced_product_id(),
							'summary'			=> ' &mdash; ' . $data['summary'],
							'cost'				=> '',
							'start_date'		=> $sync_data['start_raw'],
							'end_date'			=> $sync_data['end_raw'],
							'all_day'			=> $sync_data['all_day'],
						) );

						// Create the appointment and assign 'pending-confirmation' status to it.
						$new_appointment = get_wc_appointment( $new_appointment_data );
						$new_appointment->create( apply_filters( 'woocommerce_appointments_create_from_gcal_status', 'pending-confirmation' ) );

						// Sync appointment with GCal.
						update_post_meta( $new_appointment->id, '_wc_appointments_gcal_event_id', $data['id'] );

					}
				}

				// Save sync token for next update.
				#update_option( 'wc_appointments_gcal_sync_token', $response_data['nextSyncToken'] );

			}
		} else {
			if ( 'yes' == $this->debug ) {
				$this->log->add( $this->id, 'Error while performing sync from Google Calendar: ' . var_export( $response, true ) );
			}
		}
	}
}

/**
 * Returns the main instance of WC_Appointments_Integration_GCal to prevent the need to use globals.
 *
 * @return WC_Appointments_Integration_GCal
 */
function wc_appointments_integration_gcal() {
	return WC_Appointments_Integration_GCal::instance();
}

add_action( 'init', 'integration_gcal' );
function integration_gcal() {
	return wc_appointments_integration_gcal();
}



/**
 * Google Calendar Product ID for synced back events, created inside Google Calendar.
 *
 * Must be integer value, due to database optimization and query
 *
 * @return int
 */
function wc_appointments_gcal_synced_product_id() {
	$return = apply_filters( 'woocommerce_appointments_gcal_synced_product_id', 2147483647 );

	return absint( $return );
}
