<?php
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_script( 'wc_appointments_writepanel_js' );
?>

<div id="appointments_settings">
	<input type="hidden" name="appointments_availability_submitted" value="1" />
	<div id="poststuff">
		<div class="postbox">
			<h3 class="hndle"><?php _e( 'Global availability', 'woocommerce-appointments' ); ?></h3>
			<div class="inside">
				<p><?php _e( 'The availability rules you define here will affect all appointable products. You can override them for each product, staff.', 'woocommerce-appointments' ); ?></p>
				<div class="table_grid" id="appointments_availability">
					<table class="widefat">
						<thead>
							<tr>
								<th class="sort" width="1%">&nbsp;</th>
								<th class="range_type"><?php esc_html_e( 'Range type', 'woocommerce-appointments' ); ?></th>
								<th class="range_name"><?php esc_html_e( 'Range', 'woocommerce-appointments' ); ?></th>
								<th class="range_name2"></th>
								<!--<th class="range_capacity"><?php esc_html_e( 'Inventory', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'The maximum number of appointments per slot. Overrides product inventory.', 'woocommerce-appointments' ) ); ?></th>-->
								<th class="range_appointable"><?php esc_html_e( 'Appointable', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'If not appointable, users won\'t be able to choose slots in this range for their appointment.', 'woocommerce-appointments' ) ); ?></th>
								<th class="remove" width="1%">&nbsp;</th>
							</tr>
						</thead>
						<tbody id="availability_rows">
							<?php
							$values = get_option( 'wc_global_appointment_availability' );
							if ( ! empty( $values ) && is_array( $values ) ) {
								foreach ( $values as $availability ) {
									include( 'html-appointment-availability-fields.php' );
								}
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="7">
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
	</div>
</div>
