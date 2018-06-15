<div id="appointments_availability" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
	<div class="options_group show_if_appointment">
	<?php
		woocommerce_wp_select( array(
			'id'			=> '_wc_appointment_availability_span',
			'label'			=> __( 'Availability Check', 'woocommerce-appointments' ),
			'description'	=> __( 'By default availability per each slot in range is checked. You can also check availability for starting slot only.', 'woocommerce-appointments' ),
			'desc_tip'		=> true,
			'value'			=> get_post_meta( $post_id, '_wc_appointment_availability_span', true ),
			'options' => array(
				''        => __( 'All slots in availability range', 'woocommerce-appointments' ),
				'start'   => __( 'The starting slot only', 'woocommerce-appointments' ),
			),
		) );

		woocommerce_wp_checkbox( array(
			'id'			=> '_wc_appointment_availability_autoselect',
			'label'			=> __( 'Auto-select?', 'woocommerce-appointments' ),
			'description'	=> __( 'Check this box if you want to auto-select first available day and/or time.', 'woocommerce-appointments' ),
		) );
	?>
	</div>
	<div class="options_group">
		<div class="toolbar">
			<h3><?php _e( 'Custom Availability', 'woocommerce-appointments' ); ?></h3>
		</div>
		<p><?php printf( __( 'Add custom availability rules to override <a href="%s">global availability</a> for this appointment only.', 'woocommerce-appointments' ), admin_url( 'admin.php?page=wc-settings&tab=appointments' ) ); ?></p>
		<div class="table_grid">
			<table class="widefat">
				<thead>
					<tr>
						<th class="sort" width="1%">&nbsp;</th>
						<th class="range_type"><?php esc_html_e( 'Range type', 'woocommerce-appointments' ); ?></th>
						<th class="range_name"><?php esc_html_e( 'Range', 'woocommerce-appointments' ); ?></th>
						<th class="range_name2"></th>
						<th class="range_capacity"><?php esc_html_e( 'Capacity', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'The maximum number of appointments per slot. Overrides general product capacity.', 'woocommerce-appointments' ) ); ?></th>
						<th class="range_appointable"><?php esc_html_e( 'Appointable', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'If not appointable, users won\'t be able to choose slots in this range for their appointment.', 'woocommerce-appointments' ) ); ?></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tbody id="availability_rows">
					<?php
						$values = get_post_meta( $post_id, '_wc_appointment_availability', true );
						if ( ! empty( $values ) && is_array( $values ) ) {
							foreach ( $values as $availability ) {
								include( 'html-appointment-availability-fields.php' );
							}
						}
					?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="8">
							<a href="#" class="button add_row" data-row="<?php
								ob_start();
								include( 'html-appointment-availability-fields.php' );
								$html = ob_get_clean();
								echo esc_attr( $html );
							?>"><?php esc_html_e( 'Add Rule', 'woocommerce-appointments' ); ?></a>
							<span class="description"><?php esc_html_e( get_wc_appointment_rules_explanation() ); ?></span>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</div>
