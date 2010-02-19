<?php

class ScoperAncestry {
	// ( derived from WP core _get_term_hierarchy() )
	// Removed option buffering since hierarchy is user-specific (get_terms query will be wp-cached anyway)
	// Also adds support for taxonomies that don't use wp_term_taxonomy schema
	function get_terms_children( $taxonomy, $option_value = '' ) {
		if ( ! is_taxonomy_hierarchical($taxonomy) )
			return array();
		
		$children = get_option("{$taxonomy}_children_rs");
		
		if ( is_array($children) )
			return $children;
					
		global $scoper;
		if ( ! $tx = $scoper->taxonomies->get($taxonomy) )
			return $option_value;
		
		if ( empty($tx->source->cols->parent) || empty($tx->source->cols->id) )
			return $option_value;
			
		$col_id = $tx->source->cols->id;	
		$col_parent = $tx->source->cols->parent;
		
		$children = array();
		
		$terms = $scoper->get_terms($taxonomy, UNFILTERED_RS);
		
		foreach ( $terms as $term )
			if ( $term->$col_parent )
				$children[$term->$col_parent][] = $term->$col_id;
	
		update_option("{$taxonomy}_children_rs", $children);
				
		return $children;
	}
	
	// note: rs_get_page_children() is no longer used internally by Role scoper
	function get_page_children() {
		$children = array();
	
		global $wpdb;
		if ( $pages = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type != 'revision'") ) {
			foreach ( $pages as $page )
				if ( $page->post_parent )
					$children[$page->post_parent][] = $page->ID;
		}
	
		return $children;
	}
	
	function _walk_ancestors($child_id, $ancestors, $parents) {
		if ( isset($parents[$child_id]) ) {
			if ( in_array( $parents[$child_id], $ancestors ) )  // prevent infinite recursion if a page has a descendant set as its parent page
			  return $ancestors;
	
			$ancestors []= $parents[$child_id];
			$ancestors = ScoperAncestry::_walk_ancestors($parents[$child_id], $ancestors, $parents);
		}
		return $ancestors;
	}
	
	
	function get_page_ancestors() {
		$ancestors = get_option("scoper_page_ancestors");
	
		if ( is_array($ancestors) )
			return $ancestors;
	
		$ancestors = array();
		
		global $wpdb;
		if ( $pages = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type != 'revision'") ) {
			$parents = array();
			foreach ( $pages as $page )
				if ( $page->post_parent )
					$parents[$page->ID] = $page->post_parent;
	
			foreach ( $pages as $page ) {
				$ancestors[$page->ID] = ScoperAncestry::_walk_ancestors($page->ID, array(), $parents);
				if ( empty( $ancestors[$page->ID] ) )
					unset( $ancestors[$page->ID] );
			}
			
			update_option("scoper_page_ancestors", $ancestors);
		}
		
		return $ancestors;
	}
	
	function get_term_ancestors($taxonomy) {
		$ancestors = get_option("{$taxonomy}_ancestors_rs");
	
		if ( is_array($ancestors) )
			return $ancestors;
	
		global $scoper;
	
		$tx = $scoper->taxonomies->get($taxonomy);	
		$col_id = $tx->source->cols->id;	
		$col_parent = $tx->source->cols->parent;
		
		$terms = $scoper->get_terms($taxonomy, UNFILTERED_RS);
	
		$ancestors = array();
	
		if ( $terms ) {
			$parents = array();
			
			foreach ( $terms as $term )
				if ( $term->$col_parent )
					$parents[$term->$col_id] = $term->$col_parent;
	
			foreach ( $terms as $term ) {
				$term_id = $term->$col_id;
				$ancestors[$term_id] = ScoperAncestry::_walk_ancestors($term_id, array(), $parents);
				if ( empty( $ancestors[$term_id] ) )
					unset( $ancestors[$term_id] );
			}
			
			update_option("{$taxonomy}_ancestors_rs", $ancestors);
		}
		
		return $ancestors;
	}

}

?>