<?php

if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
// WP 2.5 - 2.7 autosave wipes out page parent. WP 2.5 autosave sets author to current user
if ( isset($_POST['action']) && ($_POST['action'] == 'autosave') && isset($_POST['post_type']) )
	add_filter('query', array('ScoperAdminHardway', 'flt_autosave_bugstomper') );

if ( ! is_content_administrator_rs() )
	require_once( 'hardway-admin_non-administrator_rs.php' );

if ( is_admin() && scoper_get_option( 'filter_users_dropdown') )
	require_once( 'hardway-users_rs.php' );
	
if ( ! awp_ver('2.7-dev') )
	require_once('hardway-admin-legacy_rs.php');
	
/**
 * ScoperAdminHardway PHP class for the WordPress plugin Role Scoper
 * hardway-admin_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 * Used by Role Role Scoper Plugin as a container for statically-called functions
 *
 */
class ScoperAdminHardway {
	// WP autosave wipes out page parent and sets author to current user
	function flt_autosave_bugstomper($query) {
		global $wpdb;

		if ( ( strpos($query, "PDATE $wpdb->posts ") && strpos($query, "post_parent") ) ) {
			// as of WP 2.6, only the post_parent is being wiped.
			if ( ! awp_ver('2.6') ) {
				global $current_user;
				$query = preg_replace( "/,\s*`post_author`\s*=\s*'{$current_user->ID}'/", "", $query);
				$query = preg_replace( "/`post_author`\s*=\s*'{$current_user->ID}',/", "", $query);
			}
			
			$query = preg_replace( "/,\s*`post_parent`\s*=\s*'0'/", "", $query);
		}

		return $query;
	}
}
?>