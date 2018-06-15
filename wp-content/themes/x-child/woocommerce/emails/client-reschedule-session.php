<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<?php
	$coupon_code = $coupon['coupon_code'];
	$coupon_expiry = $coupon['coupon_expiry'];
?>
<p><?php echo sprintf( __( 'Your appointment has been cancelled. Please use the coupon and rebook before the set expiration date', 'woocommerce-appointments' )); ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee; margin:0 0 16px;" border="1" bordercolor="#eee">
	<tbody>
		<tr>
		    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Coupon code', 'woocommerce-appointments' ); ?></th>
		    <td style="text-align:left; border: 1px solid #eee;"><?php echo $coupon_code; ?></td>
		</tr>
		<tr>
		    <th style="text-align:left; border: 1px solid #eee;" scope="row"><?php _e( 'Expiration date', 'woocommerce-appointments' ); ?></th>
		    <td style="text-align:left; border: 1px solid #eee;"><?php echo $coupon_expiry; ?></td>
		</tr>
	</tbody>
</table>