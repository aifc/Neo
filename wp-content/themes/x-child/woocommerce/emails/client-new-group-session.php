<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<?php
	$appointment_type = get_post_meta( $appointment->id, 'appointment_type', true );
	$key_code = $member->key_code;
	$client = $appointment->get_customer();
    $organizer = get_user_meta($client->user_id, 'first_name', true) . ' ' . get_user_meta($client->user_id, 'last_name', true);
?>
<p><?php echo sprintf( __( 'You have been invited to join a group counselling session online organized by %s. The details of the appointment are shown below.', 'woocommerce-appointments' ),$organizer); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin:0 0 16px;" border="1" bordercolor="#eee">
	<tbody>
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
			            <a href="https://zoom.us/client/4.0.29656.0413/zoomusInstaller.pkg">download the Zoom software here.</a>';
			            break;
			    }
		    ?>
		    </td>
		</tr>
		<tr>
		    <th style="text-align:center; border: 1px solid #eee;" scope="row"><a href ="<?php echo get_site_url(null,'/response?key='.$key_code.'&status=accept'); ?>">ACCEPT</a></th>
		    <td style="font-weight:bold; text-align:center; border: 1px solid #eee;"><a href ="<?php echo get_site_url(null,'/response?key='.$key_code.'&status=decline'); ?>">DECLINE</a></td>
		</tr>
	</tbody>
</table>