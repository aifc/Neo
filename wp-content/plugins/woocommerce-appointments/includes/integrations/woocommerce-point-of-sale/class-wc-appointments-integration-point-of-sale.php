<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Point of Sale integration class.
 */
class WC_Appointments_Integration_POS {

    /**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_appointments_before_admin_dialog_button', array( $this, 'add_point_of_sale_button' ) );
		add_action( 'woocommerce_appointments_after_admin_dialog_script', array( $this, 'add_point_of_sale_button_action' ) );
		add_action( 'pos_admin_enqueue_scripts', array( $this, 'pos_appointment_enqueue_js' ) );

	}

	/**
	 * Add Point of Sale Button to Edit Appointment Dialog
	 */
	public function add_point_of_sale_button( $appointment_id ) {
		    ?>
			<button id="wca-dialog-pos" name="wca-dialog-pos" class="button button-success">
				<span class="dashicons dashicons-cart"></span>
				<?php _e( 'Raise Sale', 'woocommerce-appointments' ); ?>
			</button>
			<?php
	}

	/**
	 * Add Script to Appointment Edit Dialog
	 */
	public function add_point_of_sale_button_action( $appointment_id ) {
		global $wpdb;
		$current_user_id = get_current_user_id();
		$outlet = get_user_meta( $current_user_id, 'outlet', true );
		$wc_pos_registers = $wpdb->prefix . "wc_poin_of_sale_registers";
		// Get last used register by current user
		$register = $wpdb->get_var( "SELECT slug FROM $wc_pos_registers WHERE _edit_last = $current_user_id" );

			// jQuery Code
		    ?>
			console.log(order_status);
			if(order_status != 'completed' && order_status != 'refunded' && order_status != 'cancelled'){
				jQuery( '#wca-dialog-pos' ).show();
				var load_order_id = parseInt(order_id);
				jQuery( '#wca-dialog-pos' ).bind( 'click', function(f) {
					f.preventDefault();
					var outlet = '<?php echo $outlet; ?>';
					var register = '<?php echo $register; ?>';
					var register_url = '<?php echo site_url(); ?>' + '/point-of-sale/' + outlet + '/' + register + '/#';
					if( typeof outlet != 'undefined' && outlet && typeof register != 'undefined' && register ) {
						localStorage.setItem( 'pos_load_appointmentorder', load_order_id );
				    	window.open(register_url + load_order_id, register_url+'loaded');
				    } else {
					    alert('You are not assigned to an outlet or haven’t opened a register yet. Edit your user account to assign an outlet. Than open a register in POS at least once.');
				    }
				});
			} else {
				jQuery( '#wca-dialog-pos' ).hide();
			}

			<?php
	}

	/**
	 * Add Script to Point of Sale: To load appointment orders in POS
	 */
     public function pos_appointment_enqueue_js(){
         wp_enqueue_script( 'point-of-sale-appointment', WC_APPOINTMENTS_PLUGIN_URL . '/includes/integrations/woocommerce-point-of-sale/assets/js/pos_load_appointment.js', array( 'jquery' ), WC_APPOINTMENTS_VERSION, true );
     }


}

$GLOBALS['wc_appointments_integration_pos'] = new WC_Appointments_Integration_POS();
