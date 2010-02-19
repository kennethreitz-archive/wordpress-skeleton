<?php
/**
 * ScoperAnalyst PHP class for the WordPress plugin Role Scoper
 * analyst_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
  
 
class ScoperAnalyst {

	function identify_protected_attachments( $attachment_id = 0, $guid = '', $cols = '', $args = '' ) {
		$args = array( 'guid' => $guid );
		return ScoperAnalyst::identify_protected_posts( $attachment_id, true, $cols, $args );
	}
	
	
	function identify_protected_posts( $attachment_id = 0, $attachments = false, $cols = '',  $args = '' ) {
		$defaults = array( 'use_object_restrictions' => true, 'use_term_restrictions' => true, 'use_private_status' => true, 'guid' => '' );
		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $wpdb, $scoper;

		if ( ! isset($scoper) || is_null($scoper) ) {
			scoper_get_init_options();	
			scoper_init();
		}
		
		if ( empty($scoper->taxonomies) )
			$scoper->load_config();

		$role_type = SCOPER_ROLE_TYPE;

		$restricted_roles = array();
		$unrestricted_roles = array();				// TODO: also protect uploads based on restriction of other taxonomies
		
		$restricted_terms = array();
		$restricted_objects = array();
		
		$term_restriction_clause = '';
		$object_restriction_clause = '';
		$limit_clause = '';
		$unattached_clause = '';
		
		if ( $use_private_status )
			$role_clause = ( 'rs' == SCOPER_ROLE_TYPE ) ? "AND rs.role_name IN ('post_reader', 'page_reader')" : '';	// if also checking for private status, don't need to check for restriction of private_reader roles
		else
			$role_clause = ( 'rs' == SCOPER_ROLE_TYPE ) ? "AND rs.role_name IN ('post_reader', 'page_reader', 'private_post_reader', 'private_page_reader')" : '';
			
		
		if ( $use_term_restrictions ) {
			$term_restriction_query = "SELECT rs.obj_or_term_id AS term_id, rs.role_name, rs.max_scope FROM $wpdb->role_scope_rs AS rs "
									. "INNER JOIN $wpdb->term_taxonomy AS tt ON tt.taxonomy = rs.src_or_tx_name AND tt.taxonomy = 'category' AND tt.term_taxonomy_id = rs.obj_or_term_id "
									. "WHERE rs.role_type = '$role_type' AND rs.require_for IN ('entity', 'both') AND rs.topic = 'term' $role_clause";
			
			$term_default_restriction_query = "SELECT rs.role_name FROM $wpdb->role_scope_rs AS rs "
											. "WHERE rs.role_type = '$role_type' AND rs.require_for IN ('entity', 'both') AND rs.topic = 'term' AND rs.max_scope = 'term' AND rs.src_or_tx_name = 'category' AND rs.obj_or_term_id = '0' $role_clause";
			
			$all_terms = array();
			
			$all_terms['category'] = $scoper->get_terms( 'category', false, COL_ID_RS );
													
			if ( $results = scoper_get_results( $term_restriction_query ) ) {
				foreach ( $results as $row ) {
					if ( 'blog' == $row->max_scope )
						$unrestricted_roles['category'][$row->role_name] []= $row->term_id;
					else
						$restricted_roles['category'][$row->role_name] []= $row->term_id;
				}
			}
			
			
			// if there a role is default-restricted, mark all terms as restricted (may be unrestricted later)
			if ( $results = scoper_get_col( $term_default_restriction_query ) ) {
				foreach ( $results as $role_name ) {
					if ( isset( $unrestricted_roles['category'][$role_name] ) )
						$default_restricted = array_diff( $all_terms['category'], $unrestricted_roles['category'][$role_name] );
					else
						$default_restricted = $all_terms['category'];
		
					if ( isset( $restricted_roles['category'][$role_name] ) )
						$restricted_roles['category'][$role_name] = array_unique( array_merge( $restricted_roles['category'][$role_name], $default_restricted ) );
					else
						$restricted_roles['category'][$role_name] = $default_restricted;
				}											
			}
			
			$restricted_terms['category'] = isset($restricted_roles['category']) ? agp_array_flatten( $restricted_roles['category'] ) : array();
			
			if ( $restricted_terms['category'] ) {
				$term_restriction_clause = "OR post_parent IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts "
											. "INNER JOIN $wpdb->term_relationships AS tr ON tr.object_id = $wpdb->posts.ID "
											. "WHERE tr.term_taxonomy_id IN ('" . implode( "','", $restricted_terms['category'] ) . "') )";
			}
		}
		
			
		if ( $attachment_id ) {
			if ( is_array($attachment_id) )
				$id_clause = "AND ID IN '" . implode( "','", $attachment_id ) . "'";
			else {
				$id_clause = "AND ID = '$attachment_id'";
				$limit_clause = 'LIMIT 1';
			}
		} elseif ( $guid )
			$id_clause = "AND guid = '$file_path'";
		else
			$id_clause = '';
	
		
		if ( $attachments ) {
			// to reduce pool of objects, we only care about those that have an attachment
			$attachment_query = "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.ID IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' $id_clause ) ";
		}
		
	
		if ( $use_object_restrictions ) {
			$object_restriction_query = "SELECT rs.obj_or_term_id AS obj_id, rs.role_name, rs.max_scope FROM $wpdb->role_scope_rs AS rs "
									. "WHERE rs.role_type = '$role_type' AND rs.require_for IN ('entity', 'both') AND rs.topic = 'object' AND rs.src_or_tx_name = 'post' $role_clause AND rs.obj_or_term_id IN ( $attachment_query )";
			
			$object_default_restriction_query = "SELECT rs.role_name FROM $wpdb->role_scope_rs AS rs "
											. "WHERE rs.require_for IN ('entity', 'both') AND rs.topic = 'object' AND rs.max_scope = 'object' AND rs.src_or_tx_name = 'post' AND rs.obj_or_term_id = '0' $role_clause";
			
			$all_objects = array();
			$all_objects['post'] = scoper_get_col( $attachment_query );
									
			$restricted_roles = array();
			$unrestricted_roles = array();
							
			if ( $results = scoper_get_results( $object_restriction_query ) ) {
				foreach ( $results as $row ) {
					if ( 'blog' == $row->max_scope )
						$unrestricted_roles['post'][$row->role_name] []= $row->obj_id;
					else
						$restricted_roles['post'][$row->role_name] []= $row->obj_id;
				}
			}
	
		
			// if there a role is default-restricted, mark all terms as restricted (may be unrestricted later)
			if ( $results = scoper_get_col( $object_default_restriction_query ) ) {
				foreach ( $results as $role_name ) {
					if ( isset( $unrestricted_roles['category'][$role_name] ) )
						$default_restricted = array_diff( $all_terms['post'], $unrestricted_roles['post'][$role_name] );
					else
						$default_restricted = $all_objects['post'];
		
					if ( isset( $restricted_roles['post'][$role_name] ) )
						$restricted_roles['post'][$role_name] = array_unique( array_merge( $restricted_roles['post'][$role_name], $default_restricted ) );
					else
						$restricted_roles['post'][$role_name] = $default_restricted;
				}											
			}
			
			if ( ! empty( $restricted_objects ) ) {
				$restricted_objects['post'] = agp_array_flatten( $restricted_roles['post'] );
			
				if ( $restricted_objects['post'] )
					$object_restriction_clause = "OR post_parent IN ( SELECT ID FROM $wpdb->posts WHERE ID IN ('" . implode( "','", $restricted_objects['post'] ) . "') )";
			}
		}	
		
					
		if ( $use_private_status ) {
			$status_query = "AND post_parent IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status = 'private' )";
		}
		
		if ( $attachments ) {
			$attachment_type_clause = "post_type = 'attachment' AND";
			
			$unattached_clause = ( defined('SCOPER_BLOCK_UNATTACHED_UPLOADS') ) ? " OR post_parent < 1" : '';
		}
			
		
		$single_col = false;

		if ( COLS_ALL_RS == $cols )
			$query_cols = '*';
		elseif ( COL_ID_RS == $cols ) {
			$query_cols = 'ID';
			$single_col = true;
		} elseif ( COLS_ID_DISPLAYNAME_RS == $cols ) {
			if ( $attachment )
				$query_cols = 'ID, post_title, guid';
			else
				$query_cols = 'ID, post_title';
		} else {
			if ( $attachment )
				$query_cols = 'ID, guid';
			else {
				$query_cols = 'ID';
				$single_col = true;
			}
		}
		
		$query = "SELECT $query_cols FROM $wpdb->posts WHERE $attachment_type_clause ( 1=1 $status_query $term_restriction_clause $object_restriction_clause $unattached_clause ) $id_clause ORDER BY ID DESC $limit_clause";
		
		
		if ( $id_clause && ! is_array( $attachment_id ) ) {
			if ( $single_col )
				$results = scoper_get_var( $query );
			else
				$results = scoper_get_row( $query );
		} else {
			if ( $single_col )
				$results = scoper_get_col( $query );
			else
				$results = scoper_get_results( $query );
		}
		
		return $results;
	}

}

?>