<?php

function scoper_mu_site_menu() {	
	$path = SCOPER_ABSPATH;
	
	// RS Site Options
	add_submenu_page('wpmu-admin.php', __('Role Scoper Options', 'scoper'), __('Role Options', 'scoper'), 'read', 'rs-site_options' );
	
	$func = "include_once('$path' . '/admin/options.php');scoper_options( true );";
	add_action('wpmu-admin_page_rs-site_options', create_function( '', $func ) );	

	
	global $scoper_default_options, $scoper_options_sitewide;
			
	// omit Option Defaults menu item if all options are controlled sitewide
	if ( empty($scoper_default_options) )
		scoper_refresh_default_options();
	
	if ( count($scoper_options_sitewide) != count($scoper_default_options) ) {
		// RS Default Options (for per-blog settings)
		add_submenu_page('wpmu-admin.php', __('Role Scoper Option Defaults', 'scoper'), __('Role Defaults', 'scoper'), 'read', 'rs-default_options' );
	
		$func = "include_once('$path' . '/admin/options.php');scoper_options( false, true );";
		add_action('wpmu-admin_page_rs-default_options', create_function( '', $func ) );
	}
}

function scoper_get_blog_list( $start = 0, $num = 10 ) {
	global $wpdb;

	$blogs = $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND spam = '0' AND deleted = '0' ORDER BY registered DESC", $wpdb->siteid), ARRAY_A );

	foreach ( (array) $blogs as $details ) {
		$blog_list[ $details['blog_id'] ] = $details;
		$blog_list[ $details['blog_id'] ]['postcount'] = $wpdb->get_var( "SELECT COUNT(ID) FROM " . $wpdb->base_prefix . $details['blog_id'] . "_posts WHERE post_status='publish' AND post_type='post'" );
	}
	unset( $blogs );
	$blogs = $blog_list;

	if( false == is_array( $blogs ) )
		return array();

	if( $num == 'all' )
		return array_slice( $blogs, $start, count( $blogs ) );
	else
		return array_slice( $blogs, $start, $num );
}

?>