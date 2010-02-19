<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('agapetry_wp_core_lib.php');

// ( derived from WP core _get_term_hierarchy() )
// Removed option buffering since hierarchy is user-specific (get_terms query will be wp-cached anyway)
// Also adds support for taxonomies that don't use wp_term_taxonomy schema
function rs_get_terms_children( $taxonomy, $option_value = '' ) {
	require_once( 'ancestry_lib_rs.php' );
	return ScoperAncestry::get_terms_children( $taxonomy, $option_value );
}

function awp_blend_option_array( $option_prefix = '', $option_name, $defaults, $key_dimensions = 1, $user_opt_val = -1 ) {
	if ( ! is_array($defaults) )
		$defaults = array();
	
	if ( -1 == $user_opt_val )
		$user_opt_val = get_option( $option_prefix . $option_name );
	
	if ( ! is_array($user_opt_val) )
		$user_opt_val = array();
	
	if ( isset( $defaults[$option_name] ) )
		$user_opt_val = agp_merge_md_array($defaults[$option_name], $user_opt_val, $key_dimensions );
	
	return $user_opt_val;
}

// written because WP function is_plugin_active() requires plugin folder in arg
function awp_is_plugin_active($check_plugin_file) {
	if ( ! $check_plugin_file )
		return false;

	$plugins = get_option('active_plugins');

	foreach ( $plugins as $plugin_file ) {
		if ( false !== strpos($plugin_file, $check_plugin_file) )
			return $plugin_file;
	}
}

function is_attachment_rs() {
	global $wp_query;
	return ! empty($wp_query->query_vars['attachment_id']) || ! empty($wp_query->query_vars['attachment']);
}

function awp_administrator_roles() {			
	// WP roles containing the 'activate plugins' capability are always honored regardless of object or term restritions
	global $wp_roles;
	$admin_roles = array();
	
	if ( isset($wp_roles->roles) ) {
		$admin_cap_name = ( defined( 'SCOPER_CONTENT_ADMIN_CAP' ) ) ? constant( 'SCOPER_CONTENT_ADMIN_CAP' ) : 'activate_plugins';
		
		foreach (array_keys($wp_roles->roles) as $wp_role_name)
			if ( ! empty($wp_roles->roles[$wp_role_name]['capabilities']) )
				if ( array_intersect_key($wp_roles->roles[$wp_role_name]['capabilities'], array($admin_cap_name => 1) ) ) {
					$role_handle = scoper_get_role_handle( $wp_role_name, 'wp' );
					$admin_roles = array_merge($admin_roles, array($role_handle => $wp_role_name) );
				}
	}
	
	return $admin_roles;
}
?>