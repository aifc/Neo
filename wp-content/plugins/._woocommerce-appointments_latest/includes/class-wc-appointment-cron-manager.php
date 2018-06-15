<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Cron job handler
 */
class WC_Appointments_Cron_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wc-appointment-reminder', array( $this, 'send_appointment_reminder' ) );
		add_action( 'wc-appointment-complete', array( $this, 'maybe_mark_appointment_complete' ) );
		add_action( 'wc-appointment-remove-inactive-cart', array( $this, 'remove_inactive_appointment_from_cart' ) );
	}

	/**
	 * Send appointment reminder email
	 */
	public function send_appointment_reminder( $appointment_id ) {
		$mailer   = WC()->mailer();
		$reminder = $mailer->emails['WC_Email_Appointment_Reminder'];
		$reminder->trigger( $appointment_id );
	}

	/**
	 * Change the appointment status if it wasn't previously cancelled
	 */
	public function maybe_mark_appointment_complete( $appointment_id ) {
		$appointment = get_wc_appointment( $appointment_id );

		// Don't procede if id is not of a valid appointment.
		if ( ! is_a( $appointment, 'WC_Appointment' ) ) {
			return;
		}

		if ( 'cancelled' === get_post_status( $appointment_id ) ) {
			$appointment->schedule_events();
		} else {
			$this->mark_appointment_complete( $appointment );
		}
	}

	/**
	 * Change the appointment status to complete
	 */
	public function mark_appointment_complete( $appointment ) {
		$appointment->update_status( 'complete' );
		$appointment->update_customer_status( 'arrived' );
	}

	/**
	 * Remove inactive appointment
	 */
	public function remove_inactive_appointment_from_cart( $appointment_id ) {
		$appointment = $appointment_id ? get_wc_appointment( $appointment_id ) : false;
		if ( $appointment_id && $appointment && $appointment->has_status( 'in-cart' ) ) {
			wp_delete_post( $appointment_id );
		}
	}
}

$GLOBALS['wc_appointments_cron_manager'] = new WC_Appointments_Cron_Manager();
