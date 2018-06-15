<?php
    $client = $appointment->get_customer();
    $user_data = get_userdata($client->user_id);
?>

<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Appointment Instructions', 'woocommerce-appointments' ); ?></th>
    <td style="text-align:left; border: 1px solid #eee;">
    <?php
    switch ($appointment_type) {
        case 'Face to face':
        case 'Face to Face':
            echo 'The appointment will take place at our offices at 7/92 Hoskins St, Mitchell ACT 2911.';
            break;
        case 'Face to screen':
        case 'Face to Screen':
        case 'Group Session':
        case 'Group session':
            echo 'The appointment will take place in a Zoom appointment.
            Please make sure you have access to a computer with a microphone and speakers. You can
            <a href="https://zoom.us/client/4.0.29656.0413/zoomusInstaller.pkg">download the Zoom software here.</a>
            Alternatively, you can download the Zoom software by clicking the Zoom link below.';
            break;
        case 'Phone to phone':
        case 'Phone to Phone':
            echo 'See client\'s number below.';
            break;
    }
    ?>
    </td>
</tr>
<?php if ($appointment_type == 'Face to screen' || $appointment_type == 'Face to Screen' || $appointment_type == 'Group Session' || $appointment_type == 'Group session')  : ?>
    <tr>
        <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Link to Join Zoom appointment', 'woocommerce-appointments' ); ?></th>
        <td style="text-align:left; border: 1px solid #eee;"><a href="<?php echo get_post_meta( $appointment->id, '_start_url', true ); ?>">Click Here</a></td>
    </tr>
<?php endif; ?>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row">Client Name</th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo get_user_meta($client->user_id, 'first_name', true) . ' ' . get_user_meta($client->user_id, 'last_name', true); ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row">Client Number</th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo get_user_meta($client->user_id, 'phone', true); ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row">Client Gender</th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo get_user_meta($client->user_id, 'gender', true); ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row">Client Date of Birth</th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo get_user_meta($client->user_id, 'date_of_birth', true); ?></td>
</tr>
<tr>
    <th style="text-align:left; border: 1px solid #eee;" scope="row">Client Email</th>
    <td style="text-align:left; border: 1px solid #eee;"><?php echo $user_data->user_login; ?></td>
</tr>