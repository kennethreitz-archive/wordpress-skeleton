<?php

// TODO: support multiple status types
function rs_get_post_revisions($post_id, $status = 'inherit', $args = '' ) {
	global $wpdb;
	
	$defaults = array( 'order' => 'DESC', 'orderby' => 'post_modified_gmt', 'use_memcache' => true, 'fields' => COLS_ALL_RS, 'return_flipped' => false );
	$args = wp_parse_args( $args, $defaults );
	extract( $args );
	
	if ( COL_ID_RS == $fields ) {
		// performance opt for repeated calls by user_has_cap filter
		if ( $use_memcache ) {
			static $last_results;
			
			if ( ! isset($last_results) )
				$last_results = array();
		
			elseif ( isset($last_results[$post_id][$status]) )
				return $last_results[$post_id][$status];
		}
		
		$revisions = scoper_get_col("SELECT $fields FROM $wpdb->posts WHERE post_type = 'revision' AND post_parent = '$post_id' AND post_status = '$status'");
	
		if ( $return_flipped )
			$revisions = array_fill_keys( $revisions, true );

		if ( $use_memcache ) {
			if ( ! isset($last_results[$post_id]) )
				$last_results[$post_id] = array();
				
			$last_results[$post_id][$status] = $revisions;
		}	
			
	} else {
		$order_clause = ( $order && $orderby ) ? "ORDER BY $orderby $order" : '';
		$revisions = scoper_get_results("SELECT * FROM $wpdb->posts WHERE post_type = 'revision' AND post_parent = '$post_id' AND post_status = '$status' $order_clause");
	}

	return $revisions;
}

?>