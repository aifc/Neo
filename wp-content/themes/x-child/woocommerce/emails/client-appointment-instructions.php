<?php
    $client = $appointment->get_customer();
?>

<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Instructions', 'woocommerce-appointments' ); ?></th>
    <td style="text-align:left; border: 1px solid #eee;">
    <?php
    switch ($appointment_type) {
        case 'Face to face':
        case 'Face to Face':
            echo 'Your appointment will take place at our offices at 7/92 Hoskins St, Mitchell ACT 2911.';
            break;
        case 'Face to screen':
        case 'Face to Screen':
        case 'Group Session':
        case 'Group session':
            echo 'Your appointment will take place in a Zoom appointment.
            Please make sure you have access to a computer with a microphone and speakers. You can
            <a href="https://zoom.us/client/4.0.29656.0413/zoomusInstaller.pkg">download the Zoom software here.</a>
            Alternatively, you can download the Zoom software by clicking the Zoom link below.';
            break;
        case 'Phone to phone':
        case 'Phone to Phone':
            echo 'Your counsellor will call you on '.get_user_meta($client->user_id, 'phone', true).'.'.
            'Please make sure you have access to this phone at the time of your appointment. Please contact OzHelp if you wish to update this number.';
            break;
    }
    ?>
    </td>
</tr>
<?php if ($appointment_type == 'Face to screen' || $appointment_type == 'Face to Screen' || $appointment_type == 'Group Session' || $appointment_type == 'Group session') : ?>
    <tr>
        <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Link to Join Zoom appointment', 'woocommerce-appointments' ); ?></th>
        <td style="text-align:left; border: 1px solid #eee;"><?php echo get_post_meta( $appointment->id, '_join_url', true ); ?></td>
    </tr>
<?php endif; ?>