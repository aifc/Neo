<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Appointment is cancelled
 *
 * An email sent to the user when an appointment is cancelled or not approved.
 *
 * @class   WC_Email_Admin_Appointment_Cancelled
 * @extends WC_Email
 */
class WC_Email_Admin_Appointment_Cancelled extends WC_Email {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'admin_appointment_cancelled';
		$this->title          = __( 'Admin Appointment Cancelled', 'woocommerce-appointments' );
		$this->description    = __( 'Appointment cancelled emails are sent when the status of an appointment goes to cancelled.', 'woocommerce-appointments' );
		$this->heading        = __( 'Appointment Cancelled', 'woocommerce-appointments' );
		$this->subject        = __( '[{blogname}] An appointment of "{product_title}" has been cancelled', 'woocommerce-appointments' );
		$this->template_html  = 'emails/admin-appointment-cancelled.php';
		$this->template_plain = 'emails/plain/admin-appointment-cancelled.php';

		// Triggers for this email
		add_action( 'woocommerce_email_recipient_admin_appointment_cancelled', array( $this, 'email_recipients' ), 10, 2 );
		add_action( 'woocommerce_appointment_pending-confirmation_to_cancelled_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_appointment_confirmed_to_cancelled_notification', array( $this, 'trigger' ) );
		add_action( 'woocommerce_appointment_paid_to_cancelled_notification', array( $this, 'trigger' ) );

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->template_base = WC_APPOINTMENTS_TEMPLATE_PATH;
		$this->recipient     = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * When appointments are created, orders and other parts may not exist yet. e.g. during order creation on checkout.
	 *
	 * This ensures emails are sent last, once all other logic is complete.
	 */
	public function email_recipients( $recipient, $appointment ) {
		if ( ! is_a( $appointment, 'WC_Appointment' ) ) {
			return apply_filters( 'woocommerce_email_new_recipients', $recipient );
		}
		if ( $appointment->has_staff() && ( $staff = $appointment->get_staff_members() ) ) {
			$staff_emails = array();
			foreach ( (array) $staff as $staff_member ) {
				$staff_emails[] = $staff_member->user_email;
			}
			$staff_emails[] = $recipient;
			$recipient = implode( ', ', $staff_emails );
		}

		return apply_filters( 'woocommerce_email_admin_cancelled_recipients', $recipient );
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @param string $appointment_id
	 * @return void
	 */
	public function trigger( $appointment_id ) {
		// to avoid find and replace string to be mixed up multiple cancellation emails.
		$this->find = array();
		$this->replace = array();

		if ( $appointment_id ) {
			// Only send the appointment email for appointment post types, not orders, etc
			if ( 'wc_appointment' !== get_post_type( $appointment_id ) ) {
				return;
			}

			$this->object = get_wc_appointment( $appointment_id );

			if ( ! is_object( $this->object ) || ! $this->object->get_order() ) {
				return;
			}

			foreach ( array( '{blogname}', '{product_title}', '{order_date}', '{order_number}' ) as $key ) {
				$key = array_search( $key, $this->find );
				if ( false !== $key ) {
					unset( $this->find[ $key ] );
					unset( $this->replace[ $key ] );
				}
			}

			$this->find[]    = '{blogname}';
			$this->replace[] = get_option( 'blogname' );

			if ( $this->object->get_product() ) {
				$this->find[]    = '{product_title}';
				$this->replace[] = $this->object->get_product()->get_title();
			}

			if ( $this->object->get_order() ) {
				if ( version_compare( WC_VERSION, '3.0', '<' ) ) {
					$order_date = $this->object->get_order()->order_date;
				} else {
					$order_date = $this->object->get_order()->get_date_created() ? $this->object->get_order()->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
				}
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $order_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = $this->object->get_order()->get_order_number();
			} else {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->appointment_date ) );

				$this->find[]    = '{order_number}';
			}
		}

		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'appointment'   => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
		), '', $this->template_base );
		return ob_get_clean();
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'appointment'   => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
		), '', $this->template_base );
		return ob_get_clean();
	}

    /**
     * Initialise Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woocommerce-appointments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this email notification', 'woocommerce-appointments' ),
				'default'     => 'yes',
			),
			'recipient' => array(
				'title'       => __( 'Recipient(s)', 'woocommerce-appointments' ),
				'type'        => 'text',
				/* translators: 1: admin email */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'woocommerce-appointments' ), esc_attr( get_option( 'admin_email' ) ) ),
				'placeholder' => '',
				'default'     => '',
			),
			'subject' => array(
				'title'       => __( 'Subject', 'woocommerce-appointments' ),
				'type'        => 'text',
				/* translators: 1: subject */
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'woocommerce-appointments' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading' => array(
				'title'       => __( 'Email Heading', 'woocommerce-appointments' ),
				'type'        => 'text',
				/* translators: 1: heading */
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', 'woocommerce-appointments' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce-appointments' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce-appointments' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => array(
					'plain'     => __( 'Plain text', 'woocommerce-appointments' ),
					'html'      => __( 'HTML', 'woocommerce-appointments' ),
					'multipart' => __( 'Multipart', 'woocommerce-appointments' ),
				),
			),
		);
    }
}

return new WC_Email_Admin_Appointment_Cancelled();
