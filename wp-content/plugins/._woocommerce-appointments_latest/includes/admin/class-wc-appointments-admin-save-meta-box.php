<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

class WC_Appointments_Admin_Save_Meta_Box {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Meta box title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Meta box context.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * Meta box priority.
	 *
	 * @var string
	 */
	public $priority;

	/**
	 * Meta box post types.
	 * @var array
	 */
	public $post_types;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id         = 'woocommerce-appointment-save';
		$this->title      = __( 'Save', 'woocommerce-appointments' );
		$this->context    = 'side';
		$this->priority   = 'high';
		$this->post_types = array( 'wc_appointment' );
	}

	/**
	 * Render inner part of meta box.
	 */
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
}

return new WC_Appointments_Admin_Save_Meta_Box();
