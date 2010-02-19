<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

// separated these functions into separate module for use by RS extension plugins

if ( ! function_exists('awp_ver') ) {
function awp_ver($wp_ver_requirement) {
	static $cache_wp_ver;
	
	if ( empty($cache_wp_ver) ) {
		global $wp_version;
		$cache_wp_ver = $wp_version;
	}
	
	if ( ! version_compare($cache_wp_ver, '0', '>') ) {
		// If global $wp_version has been wiped by WP Security Scan plugin, temporarily restore it by re-including version.php
		if ( file_exists (ABSPATH . WPINC . '/version.php') ) {
			include ( ABSPATH . WPINC . '/version.php' );
			$return = version_compare($wp_version, $wp_ver_requirement, '>=');
			$wp_version = $cache_wp_ver;	// restore previous wp_version setting, assuming it was cleared for security purposes
			return $return;
		} else
			// Must be running a future version of WP which doesn't use version.php
			return true;
	}

	// normal case - global $wp_version has not been tampered with
	return version_compare($cache_wp_ver, $wp_ver_requirement, '>=');
}
}

// TODO: move these function to core-admin_lib.php, update extensions accordingly
if ( ! function_exists('awp_plugin_info_url') ) {
function awp_plugin_info_url( $plugin_slug ) {
	$url = ( awp_ver('2.7') ) ? get_option('siteurl') . "/wp-admin/plugin-install.php?tab=plugin-information&plugin=$plugin_slug" : "http://wordpress.org/extend/plugins/$plugin_slug";
	return $url;
}
}

if ( ! function_exists('awp_plugin_update_url') ) {
function awp_plugin_update_url( $plugin_file ) {
	$url = wp_nonce_url("update.php?action=upgrade-plugin&amp;plugin=$plugin_file", "upgrade-plugin_$plugin_file");
	return $url;
}
}

if ( ! function_exists('awp_plugin_search_url') ) {
function awp_plugin_search_url( $search, $search_type = 'tag' ) {
	$wp_org_dir = 'tags';
	
	$url = ( awp_ver('2.7') ) ? get_option('siteurl') . "/wp-admin/plugin-install.php?tab=search&type=$search_type&s=$search" : "http://wordpress.org/extend/plugins/$wp_org_dir/$search";
	return $url;
}
}


if ( ! function_exists('awp_is_mu') ) {
function awp_is_mu() {
	global $wpdb, $wpmu_version;
	
	return ( function_exists('get_current_site_name') || ! empty($wpmu_version) || ( ! empty( $wpdb->base_prefix ) && ( $wpdb->base_prefix != $wpdb->prefix ) ) );
}
}

// returns true GMT timestamp
if ( ! function_exists('agp_time_gmt') ) {
function agp_time_gmt() {	
	return strtotime( gmdate("Y-m-d H:i:s") );
}
}

// date_i18n does not support pre-1970 dates, as of WP 2.8.4
if ( ! function_exists('agp_date_i18n') ) {
function agp_date_i18n( $datef, $timestamp ) {
	if ( $timestamp >= 0 )
		return date_i18n( $datef, $timestamp );
	else
		return date( $datef, $timestamp );
}
}


// equivalent to current_user_can, 
// except it supports array of reqd_caps, supports non-current user, and does not support numeric reqd_caps
//
// set object_id to 'blog' to suppress any_object_check and any_term_check
if ( ! function_exists('awp_user_can') ) {
function awp_user_can($reqd_caps, $object_id = 0, $user_id = 0, $args = array() ) {
	// $args supports 'skip_revision_allowance'.  For now, skip array_merge with defaults, for perf

	if ( function_exists('is_site_admin') && is_site_admin() ) 
		return true;
	
	if ( $user_id )
		$user = new WP_User($user_id);  // don't need Scoped_User because only using allcaps property (which contain WP blogcaps).  flt_user_has_cap will instantiate new WP_Scoped_User based on the user_id we pass
	else
		$user = wp_get_current_user();
	
	if ( empty($user) )
		return false;

	$reqd_caps = (array) $reqd_caps;
	$check_caps = $reqd_caps;
	foreach ( $check_caps as $cap_name ) {
		if ( $meta_caps = map_meta_cap($cap_name, $user->ID, $object_id) ) {
			$reqd_caps = array_diff( $reqd_caps, array($cap_name) );
			$reqd_caps = array_unique( array_merge( $reqd_caps, $meta_caps ) );
		}
	}
	
	if ( defined( 'RVY_VERSION' ) && ! empty( $args['skip_revision_allowance'] ) ) {
		global $revisionary;
		$revisionary->skip_revision_allowance = true;	// this will affect the behavior of Role Scoper's user_has_cap filter
	}
	
	if ( 'blog' == $object_id ) {
		global $scoper;
		if ( isset($scoper) ) {	// if this is being called with Scoper loaded, any_object_check won't be called anyway
			$scoper->cap_interceptor->skip_any_object_check = true;
			$scoper->cap_interceptor->skip_any_term_check = true;
			$scoper->cap_interceptor->skip_id_generation = true;
		}
	}
	
	$_args = ( 'blog' == $object_id ) ? array( $reqd_caps, $user->ID, 0 ) : array( $reqd_caps, $user->ID, $object_id );
	
	$capabilities = apply_filters('user_has_cap', $user->allcaps, $reqd_caps, $_args);
	
	if ( ('blog' == $object_id) && isset($scoper) ) {
		$scoper->cap_interceptor->skip_any_object_check = false;
		$scoper->cap_interceptor->skip_any_term_check = false;
		$scoper->cap_interceptor->skip_id_generation = false;
	}
	
	if ( ! empty( $args['skip_revision_allowance'] ) )
		$revisionary->skip_revision_allowance = false;

	foreach ($reqd_caps as $cap_name) {
		if( empty($capabilities[$cap_name]) || ! $capabilities[$cap_name] ) {
			// if we're about to fail due to a missing create_child_pages cap, honor edit_pages cap as equivalent
			// TODO: abstract this with cap_defs property
			if ( 'create_child_pages' == $cap_name ) {
				$alternate_cap_name = 'edit_pages';
				$_args = array( array($alternate_cap_name), $user->ID, $object_id );
				$capabilities = apply_filters('user_has_cap', $user->allcaps, array($alternate_cap_name), $_args);
				
				if( empty($capabilities[$alternate_cap_name]) || ! $capabilities[$alternate_cap_name] )
					return false;
			} else
				return false;
		}
	}

	return true;
}
}

// WP < 2.8 does not define get_site_option().  This is also a factor for non-mu installations, which will use the blog-specific options table anyway
if ( ! awp_ver( '2.8' ) && ! function_exists('get_site_option') ) {
function get_site_option( $key, $default = false, $use_cache = true ) {
	return get_option($key, $default);
}
}

// WP < 2.8 does not define add_site_option().  This is also a factor for non-mu installations, which will use the blog-specific options table anyway
if ( ! awp_ver( '2.8' ) && ! function_exists('add_site_option') ) {
function add_site_option( $key, $value ) {
	return update_option($key, $value);
}
}

// wrapper for __(), prevents WP strings from being forced into plugin .po
if ( ! function_exists( '__awp' ) ) {
function __awp( $string, $unused = '' ) {
	return __( $string );		
}
}

?>