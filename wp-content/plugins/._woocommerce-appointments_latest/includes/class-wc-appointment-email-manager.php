<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles email sending
 */
class WC_Appointments_Email_Manager {

	/**
	 * Constructor sets up actions
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', array( $this, 'init_emails' ) );
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_ics_file' ), 10, 3 );
		add_filter( 'woocommerce_template_directory', array( $this, 'template_directory' ), 10, 2 );
		add_action( 'init', array( $this, 'appointments_email_actions' ) );
		add_action( 'init', array( $this, 'trigger_confirmation_email' ) );
	}

	/**
	 * Include our mail templates
	 *
	 * @param  array $emails
	 * @return array
	 */
	public function init_emails( $emails ) {
		if ( ! isset( $emails['WC_Email_New_Appointment'] ) ) {
			$emails['WC_Email_New_Appointment'] = include( 'emails/class-wc-email-new-appointment.php' );
		}

		if ( ! isset( $emails['WC_Email_Appointment_Reminder'] ) ) {
			$emails['WC_Email_Appointment_Reminder'] = include( 'emails/class-wc-email-appointment-reminder.php' );
		}

		if ( ! isset( $emails['WC_Email_Appointment_Confirmed'] ) ) {
			$emails['WC_Email_Appointment_Confirmed'] = include( 'emails/class-wc-email-appointment-confirmed.php' );
		}

		if ( ! isset( $emails['WC_Email_Appointment_Notification'] ) ) {
			/* $emails['WC_Email_Appointment_Notification'] = include( 'emails/class-wc-email-appointment-notification.php' ); */
		}

		if ( ! isset( $emails['WC_Email_Appointment_Cancelled'] ) ) {
			$emails['WC_Email_Appointment_Cancelled'] = include( 'emails/class-wc-email-appointment-cancelled.php' );
		}

		if ( ! isset( $emails['WC_Email_Admin_Appointment_Cancelled'] ) ) {
			$emails['WC_Email_Admin_Appointment_Cancelled'] = include( 'emails/class-wc-email-admin-appointment-cancelled.php' );
		}

		return $emails;
	}

	/**
	 * Attach the .ics files in the emails.
	 *
	 * @param  array  $attachments
	 * @param  string $email_id
	 * @param  mixed  $appointment
	 *
	 * @return array
	 */
	public function attach_ics_file( $attachments, $email_id, $appointment ) {
		$available = apply_filters( 'woocommerce_appointments_emails_ics', array( 'appointment_confirmed', 'appointment_reminder', 'new_appointment' ) );

		if ( in_array( $email_id, $available ) ) {
			$generate = new WC_Appointments_ICS_Exporter;
			$attachments[] = $generate->get_appointment_ics( $appointment );
		}

		return $attachments;
	}

	/**
	 * Custom template directory.
	 *
	 * @param  string $directory
	 * @param  string $template
	 *
	 * @return string
	 */
	public function template_directory( $directory, $template ) {
		if ( false !== strpos( $template, '-appointment' ) ) {
			return 'woocommerce-appointments';
		}

		return $directory;
	}

	/**
	 * Functions checks for a transient to be set with appointments ids
	 * and then fires the woocommerce_appointment_confirmed hook for each of them.
	 *
	 * @since 2.3.0 introduced.
	 */
	public function trigger_confirmation_email() {
		// these values were set in WC_Email_Appointment_Confirmed:::schedule_trigger
		$appointment_ids = get_transient( 'wc_appointment_confirmation_email_send_ids' );
		if ( empty( $appointment_ids ) ) {
			return;
		}

		// Re-run the action hook as the we are certain that the data has been updated by now.
		// initially the trigger will not fire as we check for the same transient in the trigger
		// email function.
		foreach ( $appointment_ids as $appointment_id ) {
			do_action( 'woocommerce_appointment_confirmed', $appointment_id );
		}

		delete_transient( 'wc_appointment_confirmation_email_send_ids' );
	}

	/**
	 * Appointments email actions for transactional emails.
	 *
	 * @since   3.2.4
	 * @version 3.2.4
	 */
	public function appointments_email_actions() {
		// Email Actions
		$email_actions = apply_filters( 'woocommerce_appointments_email_actions', array(
			// New & Pending Confirmation
			'woocommerce_appointment_in-cart_to_paid',
			'woocommerce_appointment_in-cart_to_pending-confirmation',
			'woocommerce_appointment_unpaid_to_paid',
			'woocommerce_appointment_unpaid_to_pending-confirmation',
			'woocommerce_appointment_confirmed_to_paid',
			'woocommerce_admin_new_appointment',

			// Confirmed
			'woocommerce_appointment_confirmed',

			// Cancelled
			'woocommerce_appointment_pending-confirmation_to_cancelled',
			'woocommerce_appointment_confirmed_to_cancelled',
			'woocommerce_appointment_paid_to_cancelled',
		));

		foreach ( $email_actions as $action ) {
			add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
		}
	}
}

$GLOBALS['wc_appointments_email_manager'] = new WC_Appointments_Email_Manager();
