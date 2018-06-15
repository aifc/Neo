<div id="appointments_staff" class="woocommerce_options_panel panel wc-metaboxes-wrapper">

	<div class="options_group" id="staff_options">

		<?php
		woocommerce_wp_text_input( array(
			'id' 			=> '_wc_appointment_staff_label',
			'placeholder'	=> __( 'Providers', 'woocommerce-appointments' ),
			'label'			=> __( 'Label', 'woocommerce-appointments' ),
			'value'			=> $appointable_product->get_staff_label( 'edit' ),
			'desc_tip'		=> true,
			'description'	=> __( 'The label shown on the frontend if the staff is customer defined.', 'woocommerce-appointments' ),
		) ); ?>

		<?php
		woocommerce_wp_select( array(
			'id'			=> '_wc_appointment_staff_assignment',
			'label'			=> __( 'Selection', 'woocommerce-appointments' ),
			'value'			=> $appointable_product->get_staff_assignment( 'edit' ),
			'options' => array(
				'customer' 	  => __( 'Customer selected', 'woocommerce-appointments' ),
				'automatic'   => __( 'Automatically assigned', 'woocommerce-appointments' ),
				'all' 		  => __( 'Automatically assigned (all staff together)', 'woocommerce-appointments' ),
			),
			'desc_tip' => true,
			'description' => __( 'Customer selected staff allow customers to choose one from the appointment form.', 'woocommerce-appointments' ),
		) );
		?>

	</div>

	<div class="options_group">
		<div class="toolbar">
			<h3><?php _e( 'Staff', 'woocommerce-appointments' ); ?></h3>
		</div>
		<div class="woocommerce_appointable_staff wc-metaboxes">
			<?php
			global $post, $wpdb;

			$all_staff			= self::get_appointment_staff();
			$product_staff		= $appointable_product->get_staff_ids( 'edit' );
			$staff_base_costs	= $appointable_product->get_staff_base_costs( 'edit' );
			$staff_qtys			= $appointable_product->get_staff_qtys( 'edit' );
			$loop				= 0;

			if ( $product_staff ) {
				foreach ( $product_staff as $staff_id ) {
					$staff            = new WC_Product_Appointment_Staff( $staff_id );
					$staff_base_cost  = isset( $staff_base_costs[ $staff_id ] ) ? $staff_base_costs[ $staff_id ] : '';
					$staff_qty        = isset( $staff_qtys[ $staff_id ] ) ? $staff_qtys[ $staff_id ] : '';

					include( 'html-appointment-staff-member.php' );
					$loop++;
				}
			}
			?>
		</div>
		<p class="toolbar">
			<?php if ( $all_staff ) { ?>
				<button type="button" class="button button-primary add_staff"><?php _e( 'Link Staff', 'woocommerce-appointments' ); ?></button>
				<select name="add_staff_id" class="add_staff_id">
					<?php
					foreach ( $all_staff as $staff ) {
						if ( in_array( $staff->ID, $product_staff ) ){
							continue; // ignore resources that's already on the product
						}
						echo '<option value="' . esc_attr( $staff->ID ) . '">' . esc_html( $staff->display_name ) . '</option>';
					}
					?>
				</select>
			<?php } ?>
			<a href="<?php echo admin_url( 'users.php?role=shop_staff' ); ?>" target="_blank"><?php _e( 'Manage Staff', 'woocommerce-appointments' ); ?></a>
			<?php if ( current_user_can( 'create_users' ) ) { ?>
				&middot; <a href="<?php echo admin_url( 'user-new.php' ); ?>" target="_blank"><?php _e( 'Add Staff', 'woocommerce-appointments' ); ?></a>
			<?php } ?>
		</p>
	</div>
</div>
