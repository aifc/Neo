<?php
/**
 * My Appointments
 *
 * Shows appointments on the account page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/appointments.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @version     1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
$user = wp_get_current_user();
?>

<?php if ( ! empty( $tables ) ) : ?>

	<?php foreach ( $tables as $table ) : ?>
	
		<h2><?php echo esc_html( $table['header'] ) ?></h2>

		<table class="shop_table my_account_appointments">
			<thead>
				<tr>
					<?php if ( in_array( 'shop_staff', (array) $user->roles ) ) : ?>
						<th scope="col" class="appointment-id"><?php _e( 'Name of Client', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-date">Client Email</th>
						<th scope="col" class="appointment-date"><?php _e( 'Date', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-time"><?php _e( 'Time', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-type"><?php _e( 'Appointment Type', 'woocommerce-appointments' ); ?></th>
						<th scope="col">Phone</th>
						<th scope="col">Gender</th>
						<th scope="col">Date of Birth</th>
						<th scope="col" class="appointment-cancel">Appointment Actions</th>
					<?php else : ?>
						<th scope="col" class="appointment-id"><?php _e( 'Counsellor', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-date"><?php _e( 'Date', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-time"><?php _e( 'Time', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-type"><?php _e( 'Appointment Type', 'woocommerce-appointments' ); ?></th>
						<th scope="col" class="appointment-cancel">Appointment Actions</th>
					<?php endif; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $table['appointments'] as $appointment ) : ?>
					<tr>
					<?php if ( in_array( 'shop_staff', (array) $user->roles ) ) : ?>
						<!-- A counsellor -->
						<?php require( __DIR__ . '/new/counsellor_appointments.php'); ?>
					<?php else : ?>
						<!-- A client -->
						<?php require( __DIR__ . '/new/client_appointments.php'); ?>
					<?php endif; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
	<?php endforeach; ?>

<?php else : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<a class="woocommerce-Button button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
			<?php _e( 'Go Book', 'woocommerce-appointments' ) ?>
		</a>
		<?php _e( 'No appointments scheduled yet.', 'woocommerce-appointments' ); ?>
	</div>
<?php endif; ?>
