<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Appointment is confirmed
 *
 * An email sent to the user when an appointment is confirmed.
 *
 * @class 		WC_Email_Appointment_Confirmed
 * @extends 	WC_Email
 */
class WC_Email_Appointment_Confirmed extends WC_Email {

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->id 				= 'appointment_confirmed';
		$this->title 			= __( 'Appointment Confirmed', 'woocommerce-appointments' );
		$this->description		= __( 'Appointment confirmed emails are sent when the status of an appointment goes to confirmed.', 'woocommerce-appointments' );
		$this->heading 			= __( 'Appointment Confirmed', 'woocommerce-appointments' );
		$this->subject      	= __( '[{blogname}] Your appointment of "{product_title}" has been confirmed (Order {order_number}) - {order_date}', 'woocommerce-appointments' );
		$this->customer_email   = true;
		$this->template_html 	= 'emails/customer-appointment-confirmed.php';
		$this->template_plain 	= 'emails/plain/customer-appointment-confirmed.php';

		// Triggers for this email
		add_action( 'woocommerce_appointment_confirmed_notification', array( $this, 'trigger' ) );

		// `schedule_trigger` function must run after `trigger` as we must ensure that when trigger runs the next time
		// it will find the value set in the schedule `trigger` function. This is to allow for cases
		// where the dates are change and emails are sent before the new data is saved.
		#add_action( 'woocommerce_appointment_confirmed_notification', array( $this, 'schedule_trigger' ), 80 );

		// Call parent constructor
		parent::__construct();

		// Other settings
		$this->template_base = WC_APPOINTMENTS_TEMPLATE_PATH;
	}

	/**
	 * @since 2.3.0 introduced
	 * @param $appointmentg_id
	 */
	public function schedule_trigger( $appointmentg_id ) {
		$ids_pending_confirmation_email = (array) get_transient( 'wc_appointmentg_confirmation_email_send_ids' );
		// if id is in array it means were currently processing it in WC_Appointment_Email_Manager::trigger_confirmation_email
		if ( ! in_array( $appointmentg_id, $ids_pending_confirmation_email ) ) {
			$ids_pending_confirmation_email[] = $appointmentg_id;
			set_transient( 'wc_appointmentg_confirmation_email_send_ids', $ids_pending_confirmation_email, 0 );
		}
	}

	/**
	 * trigger function.
	 *
	 * @access public
	 * @return void
	 */
	public function trigger( $appointment_id ) {
		/*
		$appointment_ids = get_transient( 'wc_appointment_confirmation_email_send_ids' );
		if ( ! $appointment_ids ) ) {
			return;
		}
		if ( ! in_array( $appointment_id, $appointment_ids ) ) {
			return;
		}
		*/

		if ( $appointment_id ) {
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

				$this->recipient = apply_filters( 'woocommerce_email_confirmed_recipients', $this->object->get_order()->billing_email );
			} else {
				$this->find[]    = '{order_date}';
				$this->replace[] = date_i18n( wc_date_format(), strtotime( $this->object->appointment_date ) );

				$this->find[]    = '{order_number}';
				$this->replace[] = __( 'N/A', 'woocommerce-appointments' );

				if ( $this->object->customer_id && ( $customer = get_user_by( 'id', $this->object->customer_id ) ) ) {
					$this->recipient = apply_filters( 'woocommerce_email_confirmed_recipients', $customer->user_email );
				}
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
	public function get_content_html() {
		ob_start();
		wc_get_template( $this->template_html, array(
			'appointment' 		=> $this->object,
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
	public function get_content_plain() {
		ob_start();
		wc_get_template( $this->template_plain, array(
			'appointment' 		=> $this->object,
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

return new WC_Email_Appointment_Confirmed();
