<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'awscpane_mychris');

/** MySQL database username */
define('DB_USER', 'awscpane_mychris');

/** MySQL database password */
define('DB_PASSWORD', 'Pelicanoh.123');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '^TwQ^V~6qV5a8y9Rsx#N@w$98[^denpP`{!w h!1p.Gy4M RvP)zMSE63;u9S;Rx');
define('SECURE_AUTH_KEY',  '&`[99Sts$My}QWGCzacL4to%r/,]r1xQM)Fo#/[3rlaOq>cl0:jQTC.+ReTV&JB:');
define('LOGGED_IN_KEY',    '&<s}Qp$N|1bNIY?6?1PG?#},b K3!sfX:BxDA4j=8}3!?%2U)M=S( +(TX*lgwO9');
define('NONCE_KEY',        '@{(0jN75ch$?.r^vH.(7gaZAJ5|kRHz=-, ?#xxu!a=>UPeXHCrs-mcTPm)=GWLk');
define('AUTH_SALT',        'X|NDYhBvb1uI[&_/-]<BNeAT=>(}X>S58-GCBA:`886TWJO^)C%fDVK`T$f_`.|v');
define('SECURE_AUTH_SALT', 'LFTg%j<b6X;&CK8~~E>flQN@!ym.M13SE/f]c}f[$J1iu,J5jYQHA9h6,8;JBw> ');
define('LOGGED_IN_SALT',   ')U&UYMfUjn]B9?eyqnhPR}-l9[+16{zFRt;^vw76=4exCnAQGT,LOvY<$,A07q$i');
define('NONCE_SALT',       'lj4ZO$R@CX1zzZ_;)s*3r.<EsecJvHCk@ArL03F,DH ,.P *JIa:;>`ZOpPg=etg');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', true);
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', true );

// define( 'DEBUG_WP_REDIRECT', true );

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
