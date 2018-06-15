<?php if ( $appointment->has_staff() && ( $staff = $appointment->get_staff_members( $names = true ) ) ) : ?>
        <tr>
            <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment With', 'woocommerce-appointments' ); ?></th>
            <td style="text-align:left; border: 1px solid #eee;"><?php echo $staff; ?></td>
        </tr>
<?php endif; ?>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Type', 'woocommerce-appointments' ); ?></th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment_type; ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Date', 'woocommerce-appointments' ); ?></th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_start_date( wc_date_format(), '' ); ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Time', 'woocommerce-appointments' ); ?></th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo $appointment->get_start_date( '', wc_time_format() ) . ' &mdash; ' . $appointment->get_end_date( '', wc_time_format() ); ?></td>
</tr>