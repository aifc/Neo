<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div id="appointments_availability" class="panel woocommerce_options_panel wc-metaboxes-wrapper">
	<div class="options_group show_if_appointment">
		<?php
		woocommerce_wp_select( array(
			'id'			=> '_wc_appointment_availability_span',
			'label'			=> __( 'Availability Check', 'woocommerce-appointments' ),
			'description'	=> __( 'By default availability per each slot in range is checked. You can also check availability for starting slot only.', 'woocommerce-appointments' ),
			'desc_tip'		=> true,
			'value'			=> $appointable_product->get_availability_span( 'edit' ),
			'options' => array(
				''        => __( 'All slots in availability range', 'woocommerce-appointments' ),
				'start'   => __( 'The starting slot only', 'woocommerce-appointments' ),
			),
		) );

		woocommerce_wp_checkbox( array(
			'id'			=> '_wc_appointment_availability_autoselect',
			'label'			=> __( 'Auto-select?', 'woocommerce-appointments' ),
			'value'       	=> $appointable_product->get_availability_autoselect( 'edit' ) ? 'yes' : 'no',
			'description'	=> __( 'Check this box if you want to auto-select first available day and/or time.', 'woocommerce-appointments' ),
		) );

		woocommerce_wp_checkbox( array(
				'id'          => '_wc_appointment_has_restricted_days',
				'value'       => $appointable_product->has_restricted_days( 'edit' ) ? 'yes' : 'no',
				'label'       => __( 'Restrict start days?', 'woocommerce-appointments' ),
				'description' => __( 'Restrict appointments so that they can only start on certain days of the week. Does not affect availability.', 'woocommerce-appointments' ),
		) );
		?>
		<div class="appointment-day-restriction">
			<table class="widefat">
				<tbody>
					<tr>
						<td>&nbsp;</td>
						<?php
						$start_of_week = absint( get_option( 'start_of_week', 1 ) );
						for ( $i = $start_of_week; $i < $start_of_week + 7; $i ++ ) {
							$day_time = strtotime( "next sunday +{$i} day" );
							$day_number = date_i18n( _x( 'w', 'date format', 'woocommerce-appointments' ), $day_time ); #day of week number (zero to six)
							$day_name = date_i18n( _x( 'l', 'date format', 'woocommerce-appointments' ), $day_time ); #day of week name (Mon to Sun)
							?>
							<td>
								<label class="checkbox" for="_wc_appointment_restricted_days[<?php echo $day_number; ?>]"><?php echo $day_name; ?>&nbsp;</label>
								<input type="checkbox" class="checkbox" name="_wc_appointment_restricted_days[<?php echo $day_number; ?>]" id="_wc_appointment_restricted_days[<?php echo $day_number; ?>]" value="<?php echo $day_number; ?>" <?php checked( $restricted_days[ $day_number ], $day_number ); ?>>
							</td>
						<?php
						}
						?>
						<td>&nbsp;</td>
					</tr>
				</tbody>
			</table>
		</div>
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
						<th class="range_capacity"><?php esc_html_e( 'Inventory', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'The maximum number of appointments per slot. Overrides product inventory.', 'woocommerce-appointments' ) ); ?></th>
						<th class="range_appointable"><?php esc_html_e( 'Appointable', 'woocommerce-appointments' ); ?><?php echo wc_help_tip( __( 'If not appointable, users won\'t be able to choose slots in this range for their appointment.', 'woocommerce-appointments' ) ); ?></th>
						<th class="remove" width="1%">&nbsp;</th>
					</tr>
				</thead>
				<tbody id="availability_rows">
					<?php
					$values = $appointable_product->get_availability( 'edit' );
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
