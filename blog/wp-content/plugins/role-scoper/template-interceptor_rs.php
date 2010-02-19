<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * TemplateInterceptor_RS PHP class for the WordPress plugin Role Scoper
 * template-interceptor_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */

class TemplateInterceptor_RS
{	
	//var $scoper;

	function TemplateInterceptor_RS() {
		//global $scoper;
		//$this->scoper =& $scoper;	
		
		if ( scoper_get_option( 'strip_private_caption' ) ) {
			add_filter('the_title', array(&$this, 'flt_title'), 10, 3);
		
			if ( defined ('WPLANG') && WPLANG )
				add_filter('gettext', array(&$this, 'flt_gettext'), 10, 3);
		}

		if ( defined('SCOPER_FILTER_COMMENT_COUNT') )
			add_filter('get_comments_number', array(&$this, 'flt_get_comments_number') ); // this filter should pass post_id as 2nd arg, but does not as of WP 2.7
	
		if ( awp_is_plugin_active('events-calendar') )
			add_filter( 'query', array(&$this, 'ec_getDaysEvents') );
			
		if ( awp_is_plugin_active('eventcalendar3') )
			add_filter( 'query', array(&$this, 'ec3_query') );
	}
	
	function ec_getDaysEvents( $query ) {
		if ( strpos( $query, 'eventscalendar_main') ) {
			static $busy;
			
			// IMPORTANT: don't execute recursively on db calls below
			if ( empty($busy) ) {
				$busy = true;
			
				global $wpdb;
				static $post_id_in;	// local buffer of readable post IDs which are related to any event
				
				if ( ! isset($post_id_in) ) {
					$qry = "SELECT postID FROM {$wpdb->prefix}eventscalendar_main";
					$event_ids = scoper_get_col($qry);
					$event_id_in = "'" . implode("','", $event_ids) . "'";

					// now generate and execute a scoped query for readable/unpublished posts
					$post_qry = "SELECT ID from $wpdb->posts WHERE 1=1 AND $wpdb->posts.ID IN ($event_id_in)";
					
					// custom arguments to force inclusion of unpublished posts (only relationship to an unreadable published/private posts can make an event unreadable)
					$force_statuses = array( 'published', 'private', 'draft', 'future', 'pending' );
					$reqd_caps = array();
					$reqd_caps['post'] = array( 'published' => array('read'), 'private' => array('read_private_posts'), 'draft' => array('read'), 'pending' => array('read'), 'future' => array('read') );

					$post_qry = apply_filters( 'objects_request_rs', $post_qry, 'post', 'post', array('skip_teaser' => true, 'force_statuses' => $force_statuses, 'force_reqd_caps' => $reqd_caps) );
					$post_ids = scoper_get_col($post_qry);
					$post_id_in = "'" . implode("','", $post_ids) . "'";
				}

				$id_clause = "( `postID` IS NULL OR `postID` IN ( $post_id_in ) ) AND";
				$table_name = $wpdb->prefix . 'eventscalendar_main';
				$query = str_replace("SELECT * FROM `$table_name` WHERE ", "SELECT * FROM `$table_name` WHERE $id_clause ", $query);

				$busy = false;
			}
		}
		
		return $query;
	}
	
	function ec3_query( $query ) {
		if ( strpos( $query, 'ec3_schedule') ) {
			global $wpdb;
			
			// filter calendar item query from ec3_util_calendar_days()
			if ( strpos( $query, "FROM $wpdb->posts,{$wpdb->prefix}ec3_schedule") ) {
				$where = apply_filters( 'objects_where_rs', '', 'post', 'post', array('skip_teaser' => true) );

				$query = str_replace( "AND post_type='post'", '', $query );
				$query = str_replace( "WHERE post_status='publish'", "WHERE 1=1 $where", $query );
			}
			
			// filter event listing query from ec3_get_events()
			if ( strpos( $query, "FROM {$wpdb->prefix}ec3_schedule s" ) && strpos( $query, "LEFT JOIN $wpdb->posts p") ) {
				$where = apply_filters( 'objects_where_rs', '', 'post', 'post', array( 'source_alias' => 'p', 'skip_teaser' => true) );	
				$query = str_replace( "WHERE p.post_status='publish'", "WHERE 1=1 $where", $query );
			}
		}
		
		return $query;
	}
	
	function flt_title($title) {
		if ( 0 === strpos( $title, 'Private: ' ) || 0 === strpos( $title, 'Protected: ' ) )
			$title = substr( $title, strpos( $title, ':' ) + 2 ); 
		
		return $title;
	}
	
	function flt_gettext($translated_text, $orig_text) {
		if ( ( 'Private: %s' == $orig_text ) || ( 'Protected: %s' == $orig_text ) )
			$translated_text = '%s';

		return $translated_text;
	} 

	/* note: This should not be necessary unless the stored comment count is invalid.
	
		Front-end comment count will not be run through this filter unless the following line is added to wp-config.php:
			define( 'SCOPER_FILTER_COMMENT_COUNT', true );
	*/
	function flt_get_comments_number($count) {
		global $wpdb;
		global $id; // get_comments_number should pass post_id as 2nd arg, but does not as of WP 2.7
		static $last_id;
		static $last_count;
		
		if ( isset($last_count) && ( $id == $last_id ) )
			return $last_count;
		
		$query = "SELECT COUNT( DISTINCT(comment_ID) ) FROM $wpdb->comments"
			. " INNER JOIN $wpdb->posts ON {$wpdb->posts}.ID = {$wpdb->comments}.comment_post_ID"
			. " WHERE comment_approved = '1' AND comment_post_ID = '$id'";

		$count = scoper_get_var( $query );
		
		$last_id = $id;
		$last_count = $count;
		
		return $count;
	}
}

function is_teaser_rs( $id = '' , $src_name = 'post' ) {
	global $scoper;
	
	if ( empty($scoper) || ( is_home() && is_single() ) )
		return false;

	if ( ! $id && ( 'post' == $src_name ) ) {
		global $post;
		
		if ( empty($post->ID) )
			return false;
			
		$id = $post->ID;
	}
	
	return ( isset( $scoper->teaser_ids[$src_name][$id] ) );
}


function is_restricted_rs( $id = '', $src_name = 'post', $op_type = 'read', $scope_criteria = '' ) {
	global $scoper;

	if ( empty($scoper) || ( is_home() && is_single() && ! $id ) )
		return false;
		
	if ( ( 'post' == $src_name ) && ! $id ) {
		global $post;

		if ( ! isset($post->ID) )
			return false;
		
		$id = $post->ID;
	}

	$listed_ids = ( is_single() || is_page() ) ? array( $id => true ) : array();

	require_once('role_usage_rs.php');
	$role_usage = new Role_Usage_RS();
	$role_usage->determine_role_usage_rs($src_name, $listed_ids);

	if ( 'object' == $scope_criteria )
		return ( isset( $scoper->objscoped_ids[$src_name][$id][$op_type] ) );
	elseif ( 'term' == $scope_criteria )
		return ( isset( $scoper->termscoped_ids[$src_name][$id][$op_type] ) );
	else
		return ( isset( $scoper->restricted_ids[$src_name][$id][$op_type] ) );
}

// legacy
function is_exclusive_rs( $id = '', $src_name = 'post', $op_type = 'read', $scope_criteria = '' ) {
	return is_restricted_rs( $id, $src_name, $op_type, $scope_criteria );
}


if ( ! function_exists( 'is_protected') ) {
	// wrapper to support existing themes which used the Disclose Secret plugin
	function is_protected($post_id = NULL) {
		return is_restricted_rs($post_id);
	}
}
?>