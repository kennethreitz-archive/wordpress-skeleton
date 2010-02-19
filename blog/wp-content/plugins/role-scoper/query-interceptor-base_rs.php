<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class QueryInterceptorBase_RS {

	function QueryInterceptorBase_RS() {
		global $scoper;
		
		add_filter('posts_where', array('QueryInterceptorBase_RS', 'flt_defeat_publish_filter'), 1); // have to run this filter before QueryInterceptor_RS::flt_objects_where
		
		add_filter('objects_listing_rs', array('QueryInterceptorBase_RS', 'flt_objects_listing'), 50, 4);
		
		$arg_str = agp_get_lambda_argstring(1);
		foreach ( $scoper->data_sources->get_all() as $src_name => $src ) {
			if ( isset($src->query_hooks->listing) ) {
				// Call our abstract handlers with a lambda function that passes in original hook name
				// In effect, make WP pass the hook name so multiple hooks can be registered to a single handler 
				$rs_args = "'$src_name', '', '' ";
				$func = "return apply_filters( 'objects_listing_rs', $arg_str , $rs_args );";
				add_filter( $src->query_hooks->listing, create_function( $arg_str, $func ), 50, 1 );	
				//d_echo ("adding filter: $original_hook -> $func <br />");
			}
		} //foreach data_sources
	}

	
	// Eliminate a primary plugin incompatibility by replacing front-end status='publish' requirement with scoped equivalent 
	// (i.e. include private posts/pages that this user has access to via RS role assignment).  
	//
	// Also defeats status requirement imposed by WP core when query includes a custom taxonomy requirement
	function flt_defeat_publish_filter($where) {
		// don't alter the query if RS query filtering is disabled, or if this maneuver has been disabled via constant
		// note: for non-administrators, QueryInterceptor_RS::flt_objects_where will convert the publish requirement to publish OR private, if the user's blog role or RS-assigned roles grant private access
		if ( ! is_content_administrator_rs() || defined('SCOPER_RETAIN_PUBLISH_FILTER') || defined('DISABLE_QUERYFILTERS_RS') )
			return $where;
		
		global $wp_query;
		//if ( ! empty( $wp_query->query['post_status'] ) )
		if ( is_admin() && ! empty( $wp_query->query['post_status'] ) )
			return $where;
			
		// don't alter the where clause if in wp-admin and not filtering by taxonomy
		if ( is_admin() ) {
			global $wp_query;
			
			if ( empty($wp_query) && empty($wp_query->is_tax) )
				return $where;	
		}
			
		global $wpdb, $current_user;

		// don't alter the where clause for anonymous users
		if ( empty( $current_user->ID ) )
			return $where;

		//if ( is_admin() && is_content_administrator_rs() ) {  // for non-administrators in wp-admin, this is handled by posts_request / posts_where filter
		//	TODO: is this necessary when filtering Edit Posts / Pages listing by custom taxonomy?
		//	$where = preg_replace( "/$wpdb->posts.post_status\s*=\s*'publish'/", "($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'private' OR $wpdb->posts.post_status = 'draft' OR $wpdb->posts.post_status = 'pending' OR $wpdb->posts.post_status = 'future')", $where);
		//	$where = preg_replace( "/p2.post_status\s*=\s*'publish'/", "(p2.post_status = 'publish' OR p2.post_status = 'private' OR p2.post_status = 'draft' OR p2.post_status = 'pending' OR p2.post_status = 'future')", $where);
		//	$where = preg_replace( "/p.post_status\s*=\s*'publish'/", "(p.post_status = 'publish' OR p.post_status = 'private' OR p.post_status = 'draft' OR p.post_status = 'pending' OR p.post_status = 'future')", $where);
		//} else {
			$where = preg_replace( "/$wpdb->posts.post_status\s*=\s*'publish'/", "($wpdb->posts.post_status = 'publish' OR $wpdb->posts.post_status = 'private')", $where);
			$where = preg_replace( "/p2.post_status\s*=\s*'publish'/", "(p2.post_status = 'publish' OR p2.post_status = 'private')", $where);
			$where = preg_replace( "/p.post_status\s*=\s*'publish'/", "(p.post_status = 'publish' OR p.post_status = 'private')", $where);
		//}
	
		return $where;
	}
	
	// can't do this from posts_results or it will throw off found_rows used for admin paging
	function flt_objects_listing($results, $src_name, $object_types, $args = '') {
		global $wpdb;
		global $scoper;

		// it's not currently necessary or possible to log listed revisions from here
		//if ( isset($wpdb->last_query) && strpos( $wpdb->last_query, "post_type = 'revision'") )
		//	return $results;

		// if currently listed IDs are not already in post_cache, make our own equivalent memcache
		// ( create this cache for any data source, front end or admin )
		if ( 'post' == $src_name )
			global $wp_object_cache;
		
		$listed_ids = array();
		
		if ( ('post' != $src_name) || empty($wp_object_cache->cache['posts']) ) {
			if ( empty($scoper->listed_ids[$src_name]) ) {
				
				if ( $col_id = $scoper->data_sources->member_property( $src_name, 'cols', 'id' ) ) {
					$listed_ids = array();
					foreach ( $results as $row ) {
						if ( isset($row->$col_id) )
							$listed_ids [$row->$col_id] = true;
					}
					if ( empty($scoper->listed_ids) )
						$scoper->listed_ids = array();
					
					$scoper->listed_ids[$src_name] = $listed_ids;
				}
			} else
				return $results;
		}
		
		// now determine what restrictions were in place on these results 
		// (currently only for RS role type, post data source, front end or manage posts/pages)
		//
		// possible todo: support other data sources, WP role type
		if ( is_admin() && ( strpos($_SERVER['SCRIPT_NAME'], 'p-admin/edit.php') || strpos($_SERVER['SCRIPT_NAME'], 'p-admin/edit-pages.php') ) ) {
			
			if ( scoper_get_otype_option('restrictions_column', 'post') || scoper_get_otype_option('term_roles_column', 'post') || scoper_get_otype_option('object_roles_column', 'post') ) {
				require_once( 'role_usage_rs.php' );
				$role_usage = new Role_Usage_RS();
				$role_usage->determine_role_usage_rs( 'post', $listed_ids );
			}
		}
		
		return $results;
	}

} // end class
?>