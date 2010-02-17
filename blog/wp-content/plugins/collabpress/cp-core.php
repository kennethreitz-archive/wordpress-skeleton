<?php

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

/*** 
 * Define the path and url of the CollabPress plugins directory. 
 * It is important to use plugins_url() core function to obtain 
 * the correct scheme used (http or https). 
 */
define( 'CP_PLUGIN_DIR', WP_PLUGIN_DIR . '/collabpress' );
define( 'CP_PLUGIN_URL', plugins_url( $path = '/collabpress' ) );

/*** 
 * Install or upgrade
 */
require ( CP_PLUGIN_DIR . '/cp-core/cp-core-install.php' );

define( 'CP_MINIMUM_USER', get_option('cp_user_level'));

/*** 
 * Include core functions
 */

// Activities
require ( CP_PLUGIN_DIR . '/cp-functions/cp-functions-activity.php' );

// Calendar
require ( CP_PLUGIN_DIR . '/cp-functions/cp-functions-calendar.php' );

// Projects
require ( CP_PLUGIN_DIR . '/cp-functions/cp-functions-projects.php' );

// Tasks
require ( CP_PLUGIN_DIR . '/cp-functions/cp-functions-tasks.php' );

// Users
require ( CP_PLUGIN_DIR . '/cp-functions/cp-functions-users.php' );


/*** 
 * Include Core Pages
 */

// Dashboard
require ( CP_PLUGIN_DIR . '/cp-core/cp-core-dashboard.php' );
$show_cp_core_dashboard = new cp_core_dashboard();

// Projects
require ( CP_PLUGIN_DIR . '/cp-core/cp-core-projects.php' );
$show_cp_core_projects = new cp_core_projects();

// Settings
require ( CP_PLUGIN_DIR . '/cp-core/cp-core-settings.php' );

/*** 
 * Hook into main dashboard to display widget
 */

// Hook into 'wp_dashboard_setup' to add dashboard widget
add_action('wp_dashboard_setup', 'cp_wp_add_dashboard_widgets' );

// Function: Add dashboard widget
function cp_wp_add_dashboard_widgets() {
	wp_add_dashboard_widget('cp_wp_dashboard_widget', 'CollabPress - Recent Activity', 'cp_wp_dashboard_widget_function');
}

// Function: Display dashboard widget
function cp_wp_dashboard_widget_function() {
	list_cp_activity();
}

// Add CollabPress CSS
function cp_wp_add_stylesheet() {
    $url = get_settings('siteurl');
    $url = $url . '/wp-content/plugins/collabpress/style/cp-admin.css';
    echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
}

add_action('admin_head', 'cp_wp_add_stylesheet');

?>