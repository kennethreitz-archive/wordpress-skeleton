<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/*
File: wp-patches_agp.php
Description: Patches broadly influential bugs in the WordPress core
			 Part of the Role Scoper plugin, but can be used independantly.
			 
Usage: Put the following code in any WordPress plugin's main file:
	if ( ! function_exists('purge_pagelink_of_cloaked_ancestors') ) {
		function purge_pagelink_of_cloaked_ancestors() {
			$rel_dir = ''; // if this file and yours are in different folders, set this variable to relative or absolute path to wp-patches_agp (needs trailing slash)
			include_once( $rel_dir . 'wp-patches_agp.php' );
		}
		add_action( 'init', 'purge_pagelink_of_cloaked_ancestors' );
	}
*/

// if WP throws out an invalid page permalink due to an unpublished ancestor, switch to page_id permalink
function scoper_flt_page_link( $link, $id ) {
	static $home_path;
	
	if ( strlen($link) > 7 && strpos($link, '//', 7) > 7 ) {
		if ( empty($home_path) )
			$home_path = get_option('home');
		
		$link = $home_path . "/?page_id=$id";
	}
	
	return $link;
}

add_filter('_get_page_link', 'scoper_flt_page_link', 50, 2);
?>