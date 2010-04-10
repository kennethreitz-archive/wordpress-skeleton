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
$cp_email_footer = "\n\nPowered by CollabPress for WordPress\nhttp://wordpress.org/extend/plugins/collabpress/";

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

// Add CollabPress Styles
function cp_wp_add_styles() {
	$url = get_option('siteurl');
    $url = $url . '/wp-content/plugins/collabpress/style/cp-admin.css';
    echo '<link rel="stylesheet" type="text/css" href="' . $url . '" />';
}

add_action( 'admin_head', 'cp_wp_add_styles' );

// Add CollabPress Scripts
function cp_wp_add_scripts() {
	?>
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.0/jquery.min.js">
        </script>
        <script type="text/javascript" >
        $(document).ready(function()
        {
        $(".comment_button").click(function(){
        
        var element = $(this);
        var I = element.attr("id");
        
        $("#slidepanel"+I).slideToggle(300);
        $(this).toggleClass("active"); 
        
        return false;
        });
        });
        </script>
    <?php
}

//temp fix, should do: http://planetozh.com/blog/2008/04/how-to-load-javascript-with-your-wordpress-plugin/
//only load JS if on a CollabPress page
If ( strpos($_SERVER['REQUEST_URI'], 'cp')>0 ) {
	add_action( 'admin_head', 'cp_wp_add_scripts' );
}
?>