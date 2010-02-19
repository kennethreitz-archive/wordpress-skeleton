<?php
function scoper_startup_error() {
	// this is the normal situation on first pass after activation
	if ( ! strpos($_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php') || ( function_exists('is_plugin_active') && is_plugin_active(SCOPER_FOLDER . '/' . SCOPER_BASENAME) ) ) {
		rs_notice('Role Scoper cannot operate because another plugin or theme has already declared the function "set_current_user" or forced early execution of "pluggable.php".  <strong>All posts, pages and links are currently hidden</strong>.  Please remove the offending plugin, or deactivate Role Scoper to revert to blog-wide Wordpress roles.');
	}
	
	// To prevent inadverant content exposure, default to blocking all content if another plugin steals wp_set_current_user definition.
	if ( ! strpos($_SERVER['SCRIPT_NAME'], 'p-admin/plugins.php') ) {
		add_filter('posts_where', create_function('$a', "return 'AND 1=2';"), 99);
		add_filter('posts_results', create_function('$a', "return array();"), 1);
		add_filter('get_pages', create_function('$a', "return array();"), 99);
		add_filter('get_bookmarks', create_function('$a', "return array();"), 99);
		add_filter('get_categories', create_function('$a', "return array();"), 99);
		add_filter('get_terms', create_function('$a', "return array();"), 99);
		
		// Also run interference for all custom-defined where_hook, request_filter or results_filter
		require_once('role-scoper_main.php');
		
		global $scoper, $wpdb;
		$scoper = new Scoper();
		$scoper->load_config();
		
		foreach( $scoper->data_sources->get_all() as $src ) {
			if ( ! empty($src->query_hooks->request) )
				add_filter($src->query_hooks->request, create_function('$a', "return 'SELECT * FROM $wpdb->posts WHERE 1=2';"), 99);
		
			if ( ! empty($src->query_hooks->where) )
				add_filter($src->query_hooks->where, create_function('$a', "return 'AND 1=2';"), 99);
		
			if ( ! empty($src->query_hooks->results) )
				add_filter($src->query_hooks->results, create_function('$a', "return array();"), 1);
		}
	}
}

function awp_notice($message, $plugin_name) {
	// slick method copied from NextGEN Gallery plugin			// TODO: why isn't there a class that can turn this text black?
	add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade" style="color: black">' . $message . '</div>\';'));
	trigger_error("$plugin_name internal notice: $message");
	$err = new WP_Error($plugin_name, $message);
}
?>