<?php
   /*
   Plugin Name: Database Management
   Description: Custom plugin to run custom SQL for this WordPress
   Version: 1.0
   Author: Pursuit Technology
   */

register_activation_hook( __FILE__, 'manage_db' );

function manage_db() {
    // See https://premium.wpmudev.org/blog/creating-database-tables-for-plugins/
    //ob_start(); //uncomment for error tracking
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix;
    $AGENCY_COUPONS_TABLE_NAME = $prefix . 'agency_coupons';
    $AGENCY_COUPON_BATCH_TABLE_NAME = $prefix . 'agency_coupon_batch';
    $CLIENT_INFORMATION_TABLE_NAME = $prefix . 'client_information';
    $CLIENT_COUPON_COUNTS_TABLE_NAME = $prefix . 'client_coupon_counts';
    $CLIENT_COUPON_INTERIM_TABLE_NAME = $prefix . 'client_coupon_interim';
    $CLIENT_GROUP_SESSION = $prefix . 'client_group_sessions';

    // Deactivating and reactivating the plugin does not overwrite this table
    $sql = "CREATE TABLE ".$AGENCY_COUPONS_TABLE_NAME." (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        agency_id bigint(20) unsigned NOT NULL,
        coupon_code varchar(50) NOT NULL UNIQUE,
        threshold_warning_sent bit(1) NOT NULL DEFAULT 0,
        max_per_client mediumint(9) unsigned NOT NULL DEFAULT 4,
        UNIQUE KEY id (id)
    ) $charset_collate;";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    $sql = "CREATE TABLE $AGENCY_COUPON_BATCH_TABLE_NAME (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        coupon_code varchar(50) NOT NULL,
        amount_available bigint(20) unsigned NOT NULL,
        initial_amount bigint(20) unsigned NOT NULL,
        purchase_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        days_valid bigint(20) unsigned NOT NULL,
        FOREIGN KEY (coupon_code) REFERENCES ${AGENCY_COUPONS_TABLE_NAME}(coupon_code),
        UNIQUE KEY id (id)
    ) $charset_collate;";
    dbDelta( $sql );    
    // GROUP SESSION TABLE
    $sql = "CREATE TABLE $CLIENT_GROUP_SESSION (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        appointment_id mediumint(9) NOT NULL,
        email varchar(50) NOT NULL,
        status varchar(20) NOT NULL,
        key_code varchar(100) NOT NULL,
        FOREIGN KEY (appointment_id) REFERENCES wp_posts(ID),
        UNIQUE KEY id (id)
    ) $charset_collate;";
    dbDelta( $sql );

    $sql = "CREATE TABLE $CLIENT_INFORMATION_TABLE_NAME (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        initial_password varchar(50) NOT NULL,
        UNIQUE KEY id (id)
    ) $charset_collate;";
    dbDelta( $sql );

    // New entry is added when the client uses a coupon
    $sql = "CREATE TABLE $CLIENT_COUPON_COUNTS_TABLE_NAME (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        coupon_code varchar(50) NOT NULL,
        uses mediumint(9) unsigned NOT NULL DEFAULT 0,
        UNIQUE KEY id (id),
        FOREIGN KEY (coupon_code) REFERENCES ${AGENCY_COUPONS_TABLE_NAME}(coupon_code),
        CONSTRAINT UQ_username_coupon_code UNIQUE(username, coupon_code)
    ) $charset_collate;";
    dbDelta( $sql );

    // Tracks when client is in booking process and hasn't finalised booking. `username` really should be called email. Also add time.
    $sql = "CREATE TABLE $CLIENT_COUPON_INTERIM_TABLE_NAME (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL UNIQUE,
        coupon_code varchar(50) NOT NULL,
        appointment_type varchar(50) NOT NULL,
        time_granted TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY id (id),
        FOREIGN KEY (coupon_code) REFERENCES ${AGENCY_COUPONS_TABLE_NAME}(coupon_code)
    ) $charset_collate;";
    dbDelta( $sql );

    // https://codex.wordpress.org/Function_Reference/add_role
    add_role(
        'agency',
        'Agency',
        array(
            'read' => true,
        )
    );
    //trigger_error(ob_get_contents(),E_USER_ERROR); //uncomment for error tracking
}
