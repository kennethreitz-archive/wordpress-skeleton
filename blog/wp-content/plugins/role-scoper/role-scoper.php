<?php
/*
Plugin Name: Role Scoper
Plugin URI: http://agapetry.net/
Description: CMS-like permissions for reading and editing. Content-specific restrictions and roles supplement/override WordPress roles. User groups optional.
Version: 1.1.7
Author: Kevin Behrens
Author URI: http://agapetry.net/
Min WP Version: 2.5
License: GPL version 2 - http://www.opensource.org/licenses/gpl-license.php
*/

/*
Copyright (c) 2009, Kevin Behrens.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
	
if ( defined( 'SCOPER_VERSION' ) ) {
	// don't allow two copies of RS to run simultaneously
	if ( is_admin() && strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php' ) && ! strpos( urldecode($_SERVER['REQUEST_URI']), 'deactivate' ) ) {
		$message = sprintf( __( '<strong>Error:</strong> Multiple copies of Role Scoper activated. Only version %1$s (in folder "%2$s") is functional.', 'scoper' ), SCOPER_VERSION, SCOPER_FOLDER );
		
		add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
	}

	return;
}


define ('SCOPER_VERSION', '1.1.7');
define ('SCOPER_DB_VERSION', '1.1.1');

define( 'ENABLE_PERSISTENT_CACHE', true );

/* --- ATTACHMENT FILTERING NOTE ---
Read access to uploaded file attachments is normally filtered (via .htaccess RewriteRules) to match post/page access.
To disable this attachment filtering, copy the following line to wp-config.php:
	define('DISABLE_ATTACHMENT_FILTERING', true);

To fail with a null response (no WP 404 screen, but still includes a 404 in response header), copy the folling line to wp-config.php:
	define ('SCOPER_QUIET_FILE_404', true);

Normally, files which are in the uploads directory but have no post/page attachment will not be blocked.
To block such files, copy the following line to wp-config.php:
	define('SCOPER_BLOCK_UNATTACHED_UPLOADS', true);
	
The Hidden Content Teaser may be configured to display the first X characters of a post/page if no excerpt or more tag is available.
To specify the number of characters (default is 50), copy the following line to wp-config.php:
	define('SCOPER_TEASER_NUM_CHARS', 100); // set to any number of your choice
	
To prevent teasing of feed items even if teaser is enabled for main posts/pages listing, copy the following line to wp-config.php:
	define( 'SCOPER_NO_FEED_TEASER', true );

To disable caching of the pages / categories listing, add the following lines to wp-config.php:
	define( 'SCOPER_NO_PAGES_CACHE', true );
	define( 'SCOPER_NO_TERMS_CACHE', true );
*/


// avoid lockout in case of editing plugin via wp-admin
if ( defined('RS_DEBUG') && is_admin() && ( strpos( urldecode($_SERVER['REQUEST_URI']), 'p-admin/plugin-editor.php' ) || strpos( urldecode($_SERVER['REQUEST_URI']), 'p-admin/plugins.php' ) ) && false === strpos( $_SERVER['REQUEST_URI'], 'activate' ) )
	return;
	
define ('COLS_ALL_RS', 0);
define ('COL_ID_RS', 1);
define ('COLS_ID_DISPLAYNAME_RS', 2);
define ('COL_TAXONOMY_ID_RS', 3);
define ('COL_COUNT_RS', 4);

define ('UNFILTERED_RS', 0);
define ('FILTERED_RS', 1);
define ('ADMIN_TERMS_FILTER_RS', 2);

define ('BASE_CAPS_RS', 1);

define ('STATUS_ANY_RS', -1);

define ('ORDERBY_HIERARCHY_RS', 'hierarchy');

define( 'SCOPER_MIN_DATE_STRING', '0000-00-00 00:00:00' );
define( 'SCOPER_MAX_DATE_STRING', '2035-01-01 00:00:00' );
define( 'SCOPER_MAX_DATE_VALUE', strtotime( constant('SCOPER_MAX_DATE_STRING') ) );

if ( defined('RS_DEBUG') ) {
	include_once('lib/debug.php');
	add_action( 'admin_footer', 'awp_echo_usage_message' );
} else
	include_once('lib/debug_shell.php');
	
//log_mem_usage_rs( 'plugin load' );
	
//if ( version_compare( phpversion(), '5.2', '<' ) )	// some servers (Ubuntu) return irregular version string format
if ( ! function_exists("array_fill_keys") )
	require_once('lib/php4support_rs.php');

require_once('lib/agapetry_lib.php');

// === awp_is_mu() function definition and usage: must be executed in this order, and before any checks of IS_MU_RS constant (such as in role-scoper_init.php) ===
require_once('lib/agapetry_wp_lib.php');
define( 'IS_MU_RS', awp_is_mu() );
// ----------------------------------------

require_once('role-scoper_init.php');	// Contains activate, deactivate, init functions. Adds mod_rewrite_rules.


// register these functions before any early exits so normal activation/deactivation can still run with RS_DEBUG
require_once('role-scoper_activation.php');	// Contains activate, deactivate, init functions (and also awp_plugin_is_active)
register_activation_hook(__FILE__, 'scoper_activate');
register_deactivation_hook(__FILE__, 'scoper_deactivate');


// define URL
define ('SCOPER_BASENAME', plugin_basename(__FILE__) );
define ('SCOPER_FOLDER', dirname( plugin_basename(__FILE__) ) );

if ( ! defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', site_url( 'wp-content' ) );

if ( ! defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', str_replace('\\', '/', ABSPATH) . 'wp-content' );

define ('SCOPER_ABSPATH', WP_CONTENT_DIR . '/plugins/' . SCOPER_FOLDER);

define ('ANON_ROLEHANDLE_RS', 'wp_public_reader');

define ('BLOG_SCOPE_RS', 'blog');
define ('TERM_SCOPE_RS', 'term');
define ('OBJECT_SCOPE_RS', 'object');

define ('OP_READ_RS', 'read');
define ('OP_ASSOCIATE_RS', 'associate');
define ('OP_EDIT_RS', 'edit');
define ('OP_PUBLISH_RS', 'publish');
define ('OP_DELETE_RS', 'delete');
define ('OP_ADMIN_RS', 'admin');

define ('ROLE_BASIS_GROUPS', 'groups');
define ('ROLE_BASIS_USER', 'user');
define ('ROLE_BASIS_USER_AND_GROUPS', 'ug');

define ('ANY_CONTENT_DATE_RS', '');
define ('NO_OBJSCOPE_CLAUSE_RS', '');

global $wpdb;

global $scoper_role_types;
$scoper_role_types = array('rs', 'wp', 'wp_cap');

if ( is_admin() || defined('XMLRPC_REQUEST') ) {
	// Early bailout for problematic 3rd party plugin ajax calls
	if ( strpos($_SERVER['SCRIPT_NAME'], 'wp-wall-ajax.php') )
		return;
		
	// skip WP version check and init operations when a WP plugin auto-update is in progress
	if ( false !== strpos($_SERVER['SCRIPT_NAME'], 'update.php') )
		return;
} else {
	require_once('feed-interceptor_rs.php'); // must define get_currentuserinfo early
}

//log_mem_usage_rs( 'initial requires' );

$bail = 0;

if ( ! $wpdb->has_cap( 'subqueries' ) ) {
	rs_notice('Sorry, this version of Role Scoper requires a database server that supports subqueries (such as MySQL 4.1+).  Please upgrade your server or deactivate Role Scoper.');
	$bail = 1;
}

if ( ! awp_ver('2.5') ) {
	rs_notice('Sorry, this version of Role Scoper requires WordPress 2.5.0 or higher.  Please upgrade Wordpress or deactivate Role Scoper.  If you must run WP 2.2 or 2.3, try <a href="http://agapetry.net/downloads/role-scoper_legacy">Role Scoper 0.9</a>.');
	$bail = 1;
}

// If someone else plugs set_current_user, we're going to take our marbles and go home - but first make sure they can't play either.
// set_current_user() is a crucial entry point to instantiate extended class WP_Scoped_User and set it as global current_user.
// There's no way to know that another set_current_user replacement will retain the set_current_user hook.
if ( function_exists('wp_set_current_user') || function_exists('set_current_user') ) {  //if is_administrator_rs exists, then these functions scoped_user.php somehow already executed (and plugged set_current_user) 
	require_once( 'error_rs.php' );
	scoper_startup_error();
	
	$bail = 1;
}

if ( ! $bail ) {
	require_once('defaults_rs.php');
	
	//log_mem_usage_rs( 'defaults_rs' );
	
	if ( IS_MU_RS )
		scoper_refresh_options_sitewide();
	
	//log_mem_usage_rs( 'refresh_options_sitewide' );
	
	//scoper_refresh_default_options();
	
	// if role options were just updated via http POST, use new values rather than loading old option values from DB
	// These option values are used in WP_Scoped_User constructor
	if ( is_admin() && isset( $_POST['role_type'] ) && strpos( urldecode($_SERVER['REQUEST_URI']), 'admin.php?page=rs-' )  ) {
		scoper_use_posted_init_options();
	} else
		scoper_get_init_options();
	
	if ( IS_MU_RS ) {
		// If groups are sitewide, default groups must also be defined/applied sitewide (and vice versa)
		global $scoper_sitewide_groups, $scoper_options_sitewide;
		if ( $scoper_sitewide_groups = scoper_get_site_option( 'mu_sitewide_groups' ) )
			$scoper_options_sitewide['default_groups'] = true;
		
		elseif( isset( $scoper_options_sitewide['default_groups'] ) )
			unset( $scoper_options_sitewide['default_groups'] );
	}

	// rs_blog_roles option has never been active in any RS release; leave commented here in case need arises
	//define ( 'RS_BLOG_ROLES', scoper_get_option('rs_blog_roles') );
	require_once('user-plug_rs.php');

	//log_mem_usage_rs( 'user-plug_rs' );
	
	// since sequence of set_current_user and init actions seems unreliable, make sure our current_user is loaded first
	add_action('set_current_user', 'scoper_maybe_init', 2);
	add_action('init', 'scoper_log_init_action', 1);
}
?>