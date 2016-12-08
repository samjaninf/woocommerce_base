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
define('DB_NAME', '');

/** MySQL database username */
define('DB_USER', '');

/** MySQL database password */
define('DB_PASSWORD', '');

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
define('AUTH_KEY',         'VIVA:#O(Xl>.-Obki^pR@,Gkn TS@XKf0#%^/^B{-l|0S(TsN!YO;OI?^*5F|i6i');
define('SECURE_AUTH_KEY',  '=1-r_og{UHEAtdjO(38%ydlG<P:WZFAs[{.T! @%IOT%qd<NH`[n]fq9Rd]);~kw');
define('LOGGED_IN_KEY',    '#>s|<=/>I2mBZl{T+K0@?5r_ge{/J+<bsmd~T&0R:+n$keghR~poW@}8|uOy0lLB');
define('NONCE_KEY',        '5JH@mQ9gIWEdB+=mF-Us}OrlEF~Cc*e78SL.AGaw@VsAW6y3U`e-q4DJ;2!^R[@?');
define('AUTH_SALT',        'tFn1sZ-Yf;eZcrH$wmO:6]?5E*IIll?BJA%LtEA+][bx@&$F>Ezt>Z_Wx/V9vBD{');
define('SECURE_AUTH_SALT', 'LMkIc9D3mlVH1lh]7T3XR3Nk-G^ya(Ze%Ff.l:sM_M]<JJ>?360vT{3d 1sT`m^4');
define('LOGGED_IN_SALT',   '^M8tapY]/U/}4>PP@).=dt3EqJ03,gxB7wSIj# 4$)8aCy~*|%}R9_k@R-Ye.l<M');
define('NONCE_SALT',       'wYvcSB-D;EWO8k:+yXiNCdx,E7,Y#3Q^dVLghjCzJ*9MK`x_ 5 i Wj#;H;]trx&');

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
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
