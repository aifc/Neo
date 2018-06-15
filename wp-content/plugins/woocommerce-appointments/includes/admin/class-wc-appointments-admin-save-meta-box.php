<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WC_Appointments_Admin_Save_Meta_Box {
	public $id;
	public $title;
	public $context;
	public $priority;
	public $post_types;

	public function __construct() {
		$this->id         = 'woocommerce-appointment-save';
		$this->title      = __( 'Save', 'woocommerce-appointments' );
		$this->context    = 'side';
		$this->priority   = 'high';
		$this->post_types = array( 'wc_appointment' );

		add_action( 'save_post', array( $this, 'meta_box_save' ), 10, 1 );
	}

	public function meta_box_inner( $post ) {
		wp_nonce_field( 'wc_appointments_save_appointment_meta_box', 'wc_appointments_save_appointment_meta_box_nonce' );

		?>		
		<div class="submitbox">
			<div class="minor-save-actions">
				<div class="misc-pub-section curtime misc-pub-curtime">
					<label for="appointment_date"><?php _e( 'Created on:', 'woocommerce-appointments' ); ?></label>
					<input type="text" class="date-picker" name="appointment_date" id="appointment_date" maxlength="10" value="<?php echo date_i18n( 'Y-m-d', strtotime( $post->post_date ) ); ?>" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" /> @ <input type="number" class="hour" placeholder="<?php _e( 'h', 'woocommerce-appointments' ); ?>" name="appointment_date_hour" id="appointment_date_hour" maxlength="2" size="2" value="<?php echo date_i18n( 'H', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />:<input type="number" class="minute" placeholder="<?php _e( 'm', 'woocommerce-appointments' ); ?>" name="appointment_date_minute" id="appointment_date_minute" maxlength="2" size="2" value="<?php echo date_i18n( 'i', strtotime( $post->post_date ) ); ?>" pattern="\-?\d+(\.\d{0,})?" />
				</div>
				<div class="clear"></div>
			</div>
			<div class="major-save-actions">
				<div id="delete-action">
					<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?> "><?php _e( 'Move to Trash', 'woocommerce-appointments' ); ?></a>
				</div>
				<div id="publishing-action">
					<input type="submit" class="button save_order button-primary tips" name="save" value="<?php _e( 'Save Appointment', 'woocommerce-appointments' ); ?>" data-tip="<?php _e( 'Save/update the appointment', 'woocommerce-appointments' ); ?>" />
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	public function meta_box_save( $post_id ) {
		if ( ! isset( $_POST['wc_appointments_save_appointment_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['wc_appointments_save_appointment_meta_box_nonce'], 'wc_appointments_save_appointment_meta_box' ) ) {
			return $post_id;
		}

      	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
      	}

		if ( ! in_array( $_POST['post_type'], $this->post_types ) ) {
			return $post_id;
		}

		global $wpdb, $post;

		// Update Creation date.
		if ( empty( $_POST['appointment_date'] ) ) {
			$date = current_time( 'timestamp' );
		} else {
			$date = strtotime( $_POST['appointment_date'] . ' ' . (int) $_POST['appointment_date_hour'] . ':' . (int) $_POST['appointment_date_minute'] . ':00' );
		}

		$date = date_i18n( 'Y-m-d H:i:s', $date );

		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET post_date = %s, post_date_gmt = %s WHERE ID = %s", $date, get_gmt_from_date( $date ), $post_id ) );

	}
}

return new WC_Appointments_Admin_Save_Meta_Box();
