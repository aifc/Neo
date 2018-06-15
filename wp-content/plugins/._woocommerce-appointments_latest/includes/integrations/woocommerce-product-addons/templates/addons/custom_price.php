<?php foreach ( $addon['options'] as $key => $option ) :
	$addon_key     = 'addon-' . sanitize_title( $addon['field-name'] );
	$option_key    = empty( $option['label'] ) ? $key : sanitize_title( $option['label'] );
	$current_value = isset( $_POST[ $addon_key ] ) && isset( $_POST[ $addon_key ][ $option_key ] ) ? wc_clean( $_POST[ $addon_key ][ $option_key ] ) : '';
	$price = apply_filters( 'woocommerce_product_addons_option_price',
		$option['price'] > 0 ? '<span class="amount-symbol">+</span>' . wc_price( get_product_addon_price_for_display( $option['price'] ) ) : '',
		$option,
		$key,
		$addon,
		'custom_price'
	);
	$duration = apply_filters( 'woocommerce_product_addons_option_duration',
		absint( $option['duration'] ) > 0 ? ' <span class="addon-duration"><span class="amount-symbol">+</span>' . wc_appointment_pretty_addon_duration( absint( $option['duration'] ) ) . '</span>' : '',
		$option,
		$key,
		$addon,
		'custom_price'
	);
	?>

	<p class="form-row form-row-wide addon-wrap-<?php echo sanitize_title( $addon['field-name'] ); ?>">
		<?php if ( ! empty( $option['label'] ) ) : ?>
			<label><?php echo wptexturize( $option['label'] ) . ' ' . $price . $duration; ?></label>
		<?php endif; ?>
		<input type="number" step="any" class="input-text addon addon-custom-price" data-raw-price="<?php echo esc_attr( $option['price'] ); ?>" name="<?php echo $addon_key ?>[<?php echo $option_key; ?>]" value="<?php echo esc_attr( $current_value ); ?>" <?php if ( ! empty( $option['min'] ) ) echo 'min="' . $option['min'] . '"'; ?> <?php if ( ! empty( $option['max'] ) ) echo 'max="' . $option['max'] . '"'; ?> />
	</p>

<?php endforeach; ?>