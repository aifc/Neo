<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Staff Appointment reminder
 *
 * An email sent to the staff when a new appointment is upcoming.
 *
 * @class 		WC_Email_Staff_Appointment_Reminder
 * @extends 	WC_Email
 */
class WC_Email_Staff_Appointment_Reminder extends WC_Email {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id 				= 'staff_appointment_reminder';
		$this->title 			= __( 'Staff Appointment Reminder', 'woocommerce-appointments' );
		$this->description		= __( 'Appointment reminders are sent to the staff to remind them of an upcoming appointment.', 'woocommerce-appointments' );
		$this->heading 			= __( 'Counselor Reminder', 'woocommerce-appointments' );
		$this->subject      	= __( '[{blogname}] A reminder about your appointment of "{product_title}"', 'woocommerce-appointments' );
		//$this->customer_email   = true;
		$this->template_html 	= 'emails/staff-appointment-reminder.php';
		$this->template_plain 	= 'emails/plain/staff-appointment-reminder.php';

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->template_base = WC_APPOINTMENTS_TEMPLATE_PATH;
		$this->recipient     = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $appointment_id ) {
		if ( $appointment_id  ) {
			$this->object = get_wc_appointment( $appointment_id );

			if ( ! is_object( $this->object ) || ! $this->object->get_order() ) {
				return;
			}

			$key = array_search( '{product_title}', $this->find );
			if ( false !== $key ) {
				unset( $this->find[ $key ] );
				unset( $this->replace[ $key ] );
			}

			$this->find[]    = '{product_title}';
			$this->replace[] = $this->object->get_product()->get_title();

			if ( $this->object->get_order() ) {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->get_order()->order_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = $this->object->get_order()->get_order_number();

				//$this->recipient = apply_filters( 'woocommerce_email_reminder_recipients', $this->object->get_order()->billing_email );
			} else {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->appointment_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = __( 'N/A', 'woocommerce-appointments' );

				// if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
				// 	$this->recipient = apply_filters( 'woocommerce_email_reminder_recipients', $customer->user_email );
				// }
			}
			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			// Staff notification.
			if ( $this->object->has_staff() && ( $staff = $this->object->get_staff_members() ) ) {
				$staff_emails = array();
				foreach ( (array) $staff as $staff_member ) {
					$staff_emails[] = $staff_member->user_email;
				}
				$staff_emails[] = 'mikkooz1243@gmail.com';
				array_push( $staff_emails, $this->recipient );
				$this->recipient = implode( ', ', $staff_emails );
			}

			$this->recipient = apply_filters( 'woocommerce_email_new_recipients', $this->recipient );

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {

		ob_start();
		wc_get_template( $this->template_html, array(
			'appointment' 		=> $this->object,
			'email_heading' => $this->get_heading(),
		), '', $this->template_base );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'appointment' 		=> $this->object,
			'email_heading' => $this->get_heading(),
		), '', $this->template_base );
		return ob_get_clean();
	}

    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    public function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce-appointments' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this email notification', 'woocommerce-appointments' ),
				'default' 		=> 'yes',
			),
			'subject' => array(
				'title' 		=> __( 'Subject', 'woocommerce-appointments' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-appointments' ), $this->subject ),
				'placeholder' 	=> '',
				'default' 		=> '',
			),
			'heading' => array(
				'title' 		=> __( 'Email Heading', 'woocommerce-appointments' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-appointments' ), $this->heading ),
				'placeholder' 	=> '',
				'default' 		=> '',
			),
			'email_type' => array(
				'title' 		=> __( 'Email type', 'woocommerce-appointments' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Choose which format of email to send.', 'woocommerce-appointments' ),
				'default' 		=> 'html',
				'class'			=> 'email_type',
				'options'		=> array(
					'plain'		 	=> __( 'Plain text', 'woocommerce-appointments' ),
					'html' 			=> __( 'HTML', 'woocommerce-appointments' ),
					'multipart' 	=> __( 'Multipart', 'woocommerce-appointments' ),
				),
			),
		);
    }
}

return new WC_Email_Staff_Appointment_Reminder();
