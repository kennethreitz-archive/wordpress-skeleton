<?php

class CapInterceptorBasic_RS
{	
	// CapInterceptorBasic_RS::flt_user_has_cap
	//
	// Scaled down current_user_can filter, mainly for use with post/page access checks by attachment filter on direct file access 
	//
	// NOTE: This should not be added as a filter simultaneously with its full-featured counterpart. (On direct access, the cap-interceptor_rs.php file is not even loaded)
	//
	// Capability filter applied by WP_User->has_cap (usually via WP current_user_can function)
	// Pertains to logged user's capabilities blog-wide, or for a single item
	//
	// $wp_blogcaps = current user's blog-wide capabilities
	// $reqd_caps = primitive capabilities being tested / requested
	// $args = array with:
	// 		$args[0] = original capability requirement passed to current_user_can (possibly a meta cap)
	// 		$args[1] = user being tested
	// 		$args[2] = object id (could be a postID, linkID, catID or something else)
	//
	// The intent here is to add to (or take away from) $wp_blogcaps based on scoper role assignments
	// (only offer an opinion on scoper-defined caps, with others left in $allcaps array as blog-wide caps)
	//
	function flt_user_has_cap($wp_blogcaps, $orig_reqd_caps, $args)	{
		if ( empty($args[2]) )
			return $wp_blogcaps;
			
		global $scoper;

		// Disregard caps which are not defined in Role Scoper config
		if ( ! $rs_reqd_caps = array_intersect( $orig_reqd_caps, $scoper->cap_defs->get_all_keys() ) )
			return $wp_blogcaps;	

		$user_id = ( isset($args[1]) ) ? $args[1] : 0;

		global $current_user;
		
		if ($user_id && ($user_id != $current_user->ID) )
			$user = new WP_Scoped_User($user_id);
		else
			$user = $current_user;

		
		$object_id = (int) $args[2];
		
		// since WP user_has_cap filter does not provide an object type / data source arg,
		// we determine data source and object type based on association to required cap(s)
		$object_types = $scoper->cap_defs->object_types_from_caps($rs_reqd_caps);

		// If an object id was provided, all required caps must share a common data source (object_types array is indexed by src_name)
		if ( count($object_types) > 1 || ! count($object_types) ) {
			return array();
		}

		$src_name = key($object_types);
		if ( ! $src = $scoper->data_sources->get($src_name) ) { 
			return array();
		}

		// If cap definition(s) did not specify object type (as with "read" cap), enlist help detecting it
		reset($object_types);
		if ( (count($object_types[$src_name]) == 1) && key($object_types[$src_name]) )
			$object_type = key($object_types[$src_name]);
		else
			$object_type = $scoper->data_sources->detect('type', $src, $object_id);

		// If caps pertain to more than one object type, filter will probably return empty set, but let it pass in case of strange and unanticipated (yet valid) usage


		$id_in = " AND $src->table.{$src->cols->id} = '$object_id'";

		$use_term_roles = $src->uses_taxonomies && scoper_get_otype_option( 'use_term_roles', $src_name, $object_type );	

		$use_object_roles = ( empty($src->no_object_roles) ) ? scoper_get_otype_option( 'use_object_roles', $src_name, $object_type ) : false;
		
		$this_args = array('object_type' => $object_type, 'user' => $user, 'use_term_roles' => $use_term_roles, 'use_object_roles' => $use_object_roles, 'skip_teaser' => true );
		
		// As of RS 1.1, using subselects in where clause instead
		//$join = $scoper->query_interceptor->flt_objects_join('', $src_name, $object_type, $this_args );
		
		$where = $scoper->query_interceptor->objects_where_role_clauses($src_name, $rs_reqd_caps, $this_args );

		if ( $where )
			$where = "AND ( $where )";
		
		// As of RS 1.1, using subselects in where clause instead
		//$query = "SELECT $src->table.{$src->cols->id} FROM $src->table $join WHERE 1=1 $where $id_in LIMIT 1";
		$query = "SELECT $src->table.{$src->cols->id} FROM $src->table WHERE 1=1 $where $id_in LIMIT 1";
		
		$id_ok = scoper_get_var($query);

		$rs_reqd_caps = array_fill_keys( $rs_reqd_caps, true );

		if ( ! $id_ok ) {
			//d_echo("object_id $object_id not okay!" );
			//rs_errlog( "object_id $object_id not okay!" );
			
			return array_diff_key( $wp_blogcaps, $rs_reqd_caps);	// required caps we scrutinized are excluded from this array
		} else {
			if ( $restore_caps = array_diff($orig_reqd_caps, array_keys($rs_reqd_caps) ) )
				$rs_reqd_caps = $rs_reqd_caps + array_fill_keys($restore_caps, true);

			//rs_errlog( 'RETURNING:' );
			//rs_errlog( serialize(array_merge($wp_blogcaps, $rs_reqd_caps)) );

			return array_merge($wp_blogcaps, $rs_reqd_caps);
		}
	}

} // end class
?>