<?php

/*
Plugin Name: CollabPress
Plugin URI: http://wordpress.org/extend/plugins/collabpress/
Description: CollabPress adds project and task management to WordPress.
Author: WebDevStudios
Version: 0.5
Author URI: http://webdevstudios.com/
*/

// Avoid direct calls to this page
if (!function_exists ('add_action')) {
		header('Status: 403 Forbidden');
		header('HTTP/1.1 403 Forbidden');
		exit();
}

// Define current version
define( 'CP_VERSION', '0.5' );

// Add "View CollabPress Dashboard" link on plugins page
$cp_plugin = plugin_basename(__FILE__); 
add_filter( 'plugin_action_links_' . $cp_plugin, 'filter_plugin_actions' );

function filter_plugin_actions ( $links ) { 
	$settings_link = '<a href="options-general.php?page=cp-dashboard-page">View Dashboard</a>'; 
	array_unshift ( $links, $settings_link ); 
	return $links;
}

// Require core CollabPress code
require_once( WP_PLUGIN_DIR . '/collabpress/cp-core.php' );

// Install CollabPress
register_activation_hook(__FILE__,'cp_install');

?>