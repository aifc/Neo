<?php
/**
 * HIDDEN appointment form field
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/appointment-form/hidden.php.
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
<?php extract( $field ); ?>
<p class="form-field form-field-wide <?php echo implode( ' ', $class ); ?>" style="display: none;">
	<label for="<?php echo $name; ?>"><?php echo $label; ?>:</label>
	<input
		type="hidden"
		value="<?php echo ( ! empty( $min ) ) ? $min : 0; ?>"
		step="<?php echo ( isset( $step ) ) ? $step : ''; ?>"
		min="<?php echo ( isset( $min ) ) ? $min : ''; ?>"
		max="<?php echo ( isset( $max ) ) ? $max : ''; ?>"
		name="<?php echo $name; ?>"
		id="<?php echo $name; ?>"
		/> <?php echo ( ! empty( $after ) ) ? $after : ''; ?>
</p>
