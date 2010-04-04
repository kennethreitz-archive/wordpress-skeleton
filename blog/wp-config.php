<?php /* @package WordPress */

if ($_SERVER['HTTP_HOST'] == 'localhost.dev' /* dev domain name*/) { 
	// Settings for Dev Site
	define('DB_NAME', 'xxxxxxxxx');
	define('DB_USER', 'xxxxxxxxx');
	define('DB_PASSWORD', 'xxxxxxxxx');
	define('DB_HOST', 'localhost');
	define('WP_DEBUG', true);
	
} else { 
	// Settings for Live Site
	define('DB_NAME', 'xxxxxxxxx');
	define('DB_USER', 'xxxxxxxxx');
	define('DB_PASSWORD', 'xxxxxxxxx');
	define('DB_HOST', 'localhost');
	define('WP_DEBUG', false);
	
	define('FTP_USER', 'xxxxxxxxx');
	define('FTP_PASS', 'xxxxxxxxx');
	define('FTP_HOST', 'xxxxxxxxx');
	
	define('WP_CACHE', true);
}

// Override DB domain for requesting domain
define('WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST'] . '/blog');
define('WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] . '/');

// Limit post revisions (noone likes a huge database)
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 160 ); 
define('WP_ALLOW_REPAIR', true);

// Stuff you shouldn't touch
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');



/**#@+
 * Authentication Unique Keys.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY', 'put your unique phrase here');
define('SECURE_AUTH_KEY', 'put your unique phrase here');
define('LOGGED_IN_KEY', 'put your unique phrase here');
define('NONCE_KEY', 'put your unique phrase here');
/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress.  A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de.mo to wp-content/languages and set WPLANG to 'de' to enable German
 * language support.
 */
define ('WPLANG', '');

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
