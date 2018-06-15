<?php
/**
 * The template for displaying the list of appointments in the summary for customers.
 * It is used in:
 * - templates/order/appointment-display.php
 * - templates/order/admin/appointment-display.php
 * It will display in four places:
 * - After checkout,
 * - In the order confirmation email, and
 * - When customer reviews order in My Account > Orders,
 * - When reviewing a customer order in the admin area.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/order/appointment-summary-list.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     	https://docs.woocommerce.com/document/template-structure/
 * @version 	1.0.0
 * @since   	3.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<ul class="wc-appointment-summary-list" style="padding: 0; list-style: none outside;">
	<?php if ( isset( $date ) && $date ) : ?>
		<li><strong><?php _e( 'When:', 'woocommerce-appointments' ); ?></strong> <?php echo $date; ?></li>
	<?php endif; ?>
	<?php if ( isset( $duration ) && $duration ) : ?>
		<li><strong><?php _e( 'Duration:', 'woocommerce-appointments' ); ?></strong> <?php echo $duration; ?></li>
	<?php endif; ?>
	<?php if ( isset( $providers ) && $providers ) : ?>
		<li><strong><?php echo $label; ?></strong> <?php echo $providers; ?></li>
	<?php endif; ?>
</ul>
