<?php
$last_status = ( ! empty( $followup->meta['appointments_last_status'] ) ) ? $followup->meta['appointments_last_status'] : '';
?>
<tr class="show-if-appointment-status">
    <th class="field-label">
        <label for="meta_appointments_last_status"><?php _e( 'Last Status', 'follow_up_emails' ); ?></label>
        <?php
        fue_tip(__(
            'Only send this email if the appointment\'s last status matches the selected value', 'follow_up_emails'
        ));
        ?>
    </th>
    <td class="field-input">
		<select name="meta[appointments_last_status]" id="meta_appointments_last_status">
			<option value="" <?php selected( $last_status, '' ); ?>><?php _e( 'Any status', 'follow_up_emails' ); ?></option>
			<?php foreach ( self::$statuses as $status ) : ?>
				<option value="<?php echo $status; ?>" <?php selected( $last_status, $status ); ?>>
					<?php echo ucfirst( $status ); ?>
				</option>
			<?php endforeach; ?>
		</select>
    </td>
</tr>
