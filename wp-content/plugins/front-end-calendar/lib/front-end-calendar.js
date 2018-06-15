jQuery(function() {

	// Tooltips
	jQuery( 'li.appointment_status' ).tipTip({
		'attribute' : 'data-tip',
		'fadeIn' : 50,
		'fadeOut' : 50,
		'delay' : 200
	});

	// Dialog
	jQuery( document ).on( 'click', '.appointments li', function(e) {

		e.preventDefault();

		if( jQuery(this).hasClass('multiple-staff-appointment')) {
			var parent_appointment_id = jQuery(this).attr('parent-appointment-id');
			var appointment = jQuery('*[data-appointment-id="' + parent_appointment_id + '"]');
		} else {
			var appointment = jQuery(this);
		}

		// Dialog elements
		var container = jQuery( '#wca-dialog-container-edit-appointment' );
		var wrap = jQuery( '#wca-dialog-wrap' );
		var dialog = jQuery( '#wca-dialog' );
		var backdrop = jQuery( '#wca-dialog-backdrop' );
		var submit = jQuery( '#wca-dialog-submit' );
		var close = jQuery( '#wca-dialog-close' );
		var detail_title = jQuery( '#wca-detail-customer' );
		var detail_product = jQuery( '#wca-detail-product dd' );
		var detail_staff = jQuery( '#wca-detail-staff dd' );
		var detail_date = jQuery( '#wca-detail-date dd' );
		var detail_time = jQuery( '#wca-detail-time dd' );
		var detail_qty = jQuery( '#wca-detail-qty dd' );
		var detail_status = jQuery( '#wca-detail-status dd' );
		var detail_cost = jQuery( '#wca-detail-cost dd' );

		// Data attributes
		var order_id = appointment.attr( 'data-order-id' );
		var order_status = appointment.attr( 'data-order-status' );
		var appointment_cost = appointment.attr( 'data-appointment-cost' );
		var appointment_id = appointment.attr( 'data-appointment-id' );
		var appointment_product = appointment.attr( 'data-product-title' );
		var appointment_product_id = appointment.attr( 'data-product-id' );
		var appointment_staff = appointment.attr( 'data-appointment-staff' );
		var appointment_date = appointment.attr( 'data-appointment-date' );
		var appointment_time = appointment.attr( 'data-appointment-time' );
		var appointment_qty = appointment.attr( 'data-appointment-qty' );
		var appointment_status = appointment.attr( 'data-appointment-status' );
		var customer = appointment.attr( 'data-customer-name' );
		var customer_phone = appointment.attr( 'data-customer-phone' );
		var customer_id = appointment.attr( 'data-customer-id' );
		var customer_url = appointment.attr( 'data-customer-url' );
		var customer_email = appointment.attr( 'data-customer-email' );
		var customer_status = appointment.attr( 'data-customer-status' );
		var customer_avatar = appointment.attr( 'data-customer-avatar' );

		//** DETAILS: start

			// Customer
			if ( customer_status ) {
				customer_status = '<span class="wca-customer-status">' + customer_status + '</span>';
			}
			
			customer = '<span class="wca-customer-name">' + customer + customer_status + '</span>';
			
			if ( customer_url ) {
				customer = '<a href="' + customer_url + '" class="wca-customer-url">' + customer + '</a>';
				jQuery('.wca-edit-customer').attr('href',customer_url);
			}
			if ( customer_phone ) {
				customer_phone = '<a href="tel:' + customer_phone + '" class="wca-customer-meta" title="' + customer_phone + '"><span class="dashicons dashicons-phone"></span></a>';
			}
			if ( customer_email ) {
				customer_email = '<a href="mailto:' + customer_email + '" class="wca-customer-meta" title="' + customer_email + '"><span class="dashicons dashicons-email"></span></a>';
			}
			if ( customer_avatar ) {
				jQuery('.wca-customer-avatar').attr('src', customer_avatar);
			}

			detail_title.html( customer + customer_phone + customer_email );

			// Date
			if ( appointment_date ) {
				detail_date.html( appointment_date );
			}

			// Product
			if ( appointment_product ) {
				// Quantity
				if ( appointment_qty ) {
					product_title = '<span id="wca-product-qty">' + appointment_qty + '&times;</span> ';
				}
				product_title += '<a href="<?php echo admin_url(); ?>post.php?post=' + appointment_product_id + '&action=edit">' + appointment_product + '</a>';
				detail_product.html( product_title );
			}

			// Staff
			if ( appointment_staff ) {
				detail_staff.html( appointment_staff );
			}

			// Time
			if ( appointment_time ) {
				detail_time.html( appointment_time );
			}


			// Status
			if ( appointment_status ) {
				detail_status.html( appointment_status );
				detail_status.addClass('wca-detail-status-' + appointment_status);
			}

			// Cost
			if ( appointment_cost ) {
				detail_cost.html( appointment_cost );
			}

		//** DETAILS: end

		// Edit Button
		jQuery( '#wca-dialog-submit' ).bind( 'click', function(f) {
			f.preventDefault();
			window.location = '<?php echo admin_url(); ?>post.php?post=' + appointment_id + '&action=edit';
		});

		<?php do_action( 'woocommerce_appointments_after_admin_dialog_script' ); ?>

		// Open dialog
		container.fadeIn( 100 );

	});

	// Hide Appointment Edit Modal
	jQuery( document ).on( 'click', '#wca-dialog-backdrop, #wca-dialog-close, #wca-dialog-cancel button', function(e) {
		jQuery( '#wca-dialog-container-edit-appointment' ).fadeOut( 100 );
	});

});
