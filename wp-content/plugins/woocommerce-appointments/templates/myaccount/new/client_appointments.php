<td class="appointment-id"><?php echo $appointment->get_staff_members()[0]->display_name; ?></td>
<td class="appointment-date"><?php echo $appointment->get_start_date( wc_date_format(), '' ); ?></td>
<td class="appointment-time"><?php echo $appointment->get_start_date( '', wc_time_format() ) . ' &mdash; ' . $appointment->get_end_date( '', wc_time_format() ); ?></td>
<td><?php echo get_post_meta( $appointment->id, 'appointment_type', true); ?></td>
<td>
    <?php if ( array_key_exists('joinable', get_object_vars($appointment)) && get_object_vars($appointment)['joinable']
        && (get_post_meta( $appointment->id, 'appointment_type', true) == 'Face to screen') ) : ?>
        <a class="x-btn x-btn-global" target="_blank" href="<?php echo get_post_meta($appointment->id, '_join_url', true); ?>">Go to appointment</a>
    <?php elseif ( $appointment->get_status() != 'cancelled' && $appointment->get_status() != 'completed' && ! $appointment->passed_cancel_day() ) : ?>
        <a href="<?php echo $appointment->get_cancel_url(); ?>" class="button cancel"><?php _e( 'Cancel', 'woocommerce-appointments' ); ?></a>
    <?php endif; ?>
</td>
