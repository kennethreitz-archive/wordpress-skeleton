<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

add_filter('comments_array', array('QueryInterceptorFront_NonAdmin_RS', 'flt_comments_results'), 99);

global $wp_query;
if ( is_object($wp_query) && method_exists($wp_query, 'is_tax') && $wp_query->is_tax() )
	add_filter('posts_where', array('QueryInterceptorFront_NonAdmin_RS', 'flt_p2_where'), 1 );
	

class QueryInterceptorFront_NonAdmin_RS {
	
	// force scoping filter to process the query a second time, to handle the p2 clause imposed by WP core for custom taxonomy requirements
	function flt_p2_where( $where ) {
		if ( strpos( $where, 'p2.post_status' ) )
			$where = apply_filters( 'objects_where_rs', $where, 'post', '', array( 'source_alias' => 'p2' ) );

		return $where;
	}
	
	// Strips comments from teased posts/pages
	function flt_comments_results($results) {
		global $scoper;
	
		if ( $results && ! empty($scoper->teaser_ids) ) {
			foreach ( $results as $key => $row )
				if ( isset($row->comment_post_ID) && isset($scoper->teaser_ids['post'][$row->comment_post_ID]) )
					unset( $results[$key] );
		}
		
		return $results;
	}
}
?>