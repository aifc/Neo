<?php
/**
 * Date picker
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/appointment-form/date-picker.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @version     1.0.0
 */
?>
<?php
wp_enqueue_script( 'wc-appointments-date-picker' );
extract( $field );

$discounted_days = isset( $discounted_days ) ? $discounted_days : ''; // experimental
$month_before_day = strpos( __( 'F j, Y' ), 'F' ) < strpos( __( 'F j, Y' ), 'j' );
?>
<fieldset class="wc-appointments-date-picker wc-appointments-date-picker-<?php echo esc_attr( $product_type ); ?> <?php echo implode( ' ', $class ); ?>">
	<legend>
		<span class="label"><?php echo $label; ?></span>: <small class="wc-appointments-date-picker-choose-date"><?php _e( 'Choose...', 'woocommerce-appointments' ); ?></small>
	</legend>

	<div class="picker" data-duration-unit="<?php echo esc_attr( $duration_unit );?>" data-availability="<?php echo esc_attr( wp_json_encode( $availability_rules ) ); ?>" data-default-availability="<?php echo $default_availability ? 'true' : 'false'; ?>" data-fully-scheduled-days="<?php echo esc_attr( wp_json_encode( $fully_scheduled_days ) ); ?>" data-partially-scheduled-days="<?php echo esc_attr( wp_json_encode( $partially_scheduled_days ) ); ?>" data-remaining-scheduled-days="<?php echo esc_attr( wp_json_encode( $remaining_scheduled_days ) ); ?>" data-padding-days="<?php echo esc_attr( wp_json_encode( $padding_days ) ); ?>" data-discounted-days="<?php echo esc_attr( wp_json_encode( $discounted_days ) ); ?>" data-min_date="<?php echo ! empty( $min_date_js ) ? $min_date_js : 0; ?>" data-max_date="<?php echo $max_date_js; ?>" data-default_date="<?php echo esc_attr( $default_date ); ?>"></div>
	
	<div class="wc-appointments-date-picker-date-fields">		
		<?php // woocommerce_appointments_mdy_format filter to choose between month/day/year and day/month/year format
		if ( $month_before_day && apply_filters( 'woocommerce_appointments_mdy_format', true ) ) : ?>
		<label>
			<input type="text" name="<?php echo $name; ?>_month" placeholder="<?php _e( 'mm', 'woocommerce-appointments' ); ?>" size="2" class="appointment_date_month" />
			<span><?php _e( 'Month', 'woocommerce-appointments' ); ?></span>
		</label> / <label>
			<input type="text" name="<?php echo $name; ?>_day" placeholder="<?php _e( 'dd', 'woocommerce-appointments' ); ?>" size="2" class="appointment_date_day" />
			<span><?php _e( 'Day', 'woocommerce-appointments' ); ?></span>
		</label>
		<?php else : ?>
		<label>
			<input type="text" name="<?php echo $name; ?>_day" placeholder="<?php _e( 'dd', 'woocommerce-appointments' ); ?>" size="2" class="appointment_date_day" />
			<span><?php _e( 'Day', 'woocommerce-appointments' ); ?></span>
		</label> / <label>
			<input type="text" name="<?php echo $name; ?>_month" placeholder="<?php _e( 'mm', 'woocommerce-appointments' ); ?>" size="2" class="appointment_date_month" />
			<span><?php _e( 'Month', 'woocommerce-appointments' ); ?></span>
		</label>
		<?php endif; ?> / <label>
			<input type="text" value="<?php echo date( 'Y' ); ?>" name="<?php echo $name; ?>_year" placeholder="<?php _e( 'YYYY', 'woocommerce-appointments' ); ?>" size="4" class="appointment_date_year" />
			<span><?php _e( 'Year', 'woocommerce-appointments' ); ?></span>
		</label>
	</div>
</fieldset>
