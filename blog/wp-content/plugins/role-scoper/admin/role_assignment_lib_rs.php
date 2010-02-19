<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

class ScoperRoleAssignments {

	function get_assigned_blog_roles($role_basis, $role_type = 'rs') {
		global $wpdb;	

		$blog_roles = array();
		
		switch ( $role_basis ) {
			case ROLE_BASIS_USER:
				$col_ug_id = 'user_id';
				$ug_clause = 'AND user_id > 0';
				break;
			case ROLE_BASIS_GROUPS:
				$col_ug_id = 'group_id';
				$ug_clause = 'AND group_id > 0';
				break;
		}
		
		$qry = "SELECT assignment_id, $col_ug_id, role_name, date_limited, start_date_gmt, end_date_gmt, content_date_limited, content_min_date_gmt, content_max_date_gmt FROM $wpdb->user2role2object_rs"
			. " WHERE role_type = '$role_type' AND scope = 'blog' $ug_clause";

		$results = scoper_get_results($qry);
		
		foreach($results as $blogrole) {
			$role_handle = $role_type . '_' . $blogrole->role_name;
			$blog_roles[$role_handle] [$blogrole->$col_ug_id] = (array) $blogrole;
			$blog_roles[$role_handle] [$blogrole->$col_ug_id]['assign_for'] = ASSIGN_FOR_ENTITY_RS;
		}
		
		return $blog_roles;
	}

	// Return all assigned term or object roles for specified arguments
	// (NOTE: key order differs from front end implementation)
	function get_assigned_roles($scope, $role_basis, $src_or_tx_name, $args = '') {
		global $wpdb;

		$defaults = array( 'id' => false, 'ug_id' => 0, 'join' => '', 'role_handles' => '' );
		$args = array_merge($defaults, (array) $args);
		extract($args);
		
		if ( BLOG_SCOPE_RS == $scope )
			return ScoperRoleAssignments::get_assigned_blog_roles($role_basis);
		
		$SCOPER_ROLE_TYPE = SCOPER_ROLE_TYPE;
		
		$roles = array();
		
		switch ( $role_basis ) {
			case ROLE_BASIS_USER:
				$col_ug_id = 'user_id';
				$ug_clause = ($ug_id) ? " AND user_id = '$ug_id'" : 'AND user_id > 0';
				break;
			case ROLE_BASIS_GROUPS:
				$col_ug_id = 'group_id';
				$ug_clause = ($ug_id) ? " AND group_id = '$ug_id'" : 'AND group_id > 0';
				break;
		}
		
		$id_clause = ( false === $id ) ? '' : "AND obj_or_term_id = '$id'";
		
		if ( $role_handles ) {
			if ( ! is_array($role_handles) ) 
				$role_handles = (array) $role_handles;
			$role_clause = ( $role_handles ) ? "AND role_name IN ('" . implode( "', '", scoper_role_handles_to_names($role_handles) ) . "')" : '';
		} else
			$role_clause = '';
		
		$qry = "SELECT $col_ug_id, obj_or_term_id, role_name, assign_for, assignment_id, inherited_from, date_limited, start_date_gmt, end_date_gmt, content_date_limited, content_min_date_gmt, content_max_date_gmt FROM $wpdb->user2role2object_rs AS uro "
			. "$join WHERE role_type = '$SCOPER_ROLE_TYPE' $role_clause AND scope = '$scope' AND src_or_tx_name = '$src_or_tx_name' $id_clause $ug_clause";

		$results = scoper_get_results($qry);
		
		foreach($results as $role) {
			$role_handle = SCOPER_ROLE_TYPE . '_' . $role->role_name;
			$roles [$role->obj_or_term_id] [$role_handle] [$role->$col_ug_id] = (array) $role;	
		}
		
		return $roles;
	}
	
	// wrapper used for single object edit
	function organize_assigned_roles($scope, $src_or_tx_name, $obj_or_term_id, $role_handles = '', $role_basis = ROLE_BASIS_USER, $get_defaults = false) {
		$assignments = array();

		if ( $get_defaults )
			$obj_or_term_id = intval($obj_or_term_id);
		
		$args = array( 'role_handles' => $role_handles );
		$args['id'] = ( $obj_or_term_id || $get_defaults ) ? $obj_or_term_id : false;
		
		$roles = ScoperRoleAssignments::get_assigned_roles($scope, $role_basis, $src_or_tx_name, $args);
		
		$role_duration_enabled = scoper_get_option( 'role_duration_limits' );
		$content_date_limits_enabled = scoper_get_option ( 'content_date_limits' );
		
		if ( ! isset($roles[$obj_or_term_id]) )
			return array();
			
		foreach ( $roles[$obj_or_term_id] as $role_handle => $agents ) {
			foreach ( $agents as $ug_id => $ass ) {
				$ass_id = $ass['assignment_id'];
				$assign_for = $ass['assign_for'];
				
				$assignments[$role_handle] ['assigned'] [$ug_id] ['inherited_from'] = $ass['inherited_from'];
			
				$assignments[$role_handle] ['assigned'] [$ug_id] ['assign_for'] = $assign_for;
				$assignments[$role_handle] ['assigned'] [$ug_id] ['assignment_id'] = $ass_id;

				if ( $role_duration_enabled && $ass['date_limited'] ) {
					$assignments[$role_handle] ['assigned'] [$ug_id] ['date_limited'] = $ass['date_limited'];
					$assignments[$role_handle] ['assigned'] [$ug_id] ['start_date_gmt'] = $ass['start_date_gmt'];
					$assignments[$role_handle] ['assigned'] [$ug_id] ['end_date_gmt'] = $ass['end_date_gmt'];			
				}
				
				if ( $content_date_limits_enabled && $ass['content_date_limited'] ) {
					$assignments[$role_handle] ['assigned'] [$ug_id] ['content_date_limited'] = $ass['content_date_limited'];
					$assignments[$role_handle] ['assigned'] [$ug_id] ['content_min_date_gmt'] = $ass['content_min_date_gmt'];
					$assignments[$role_handle] ['assigned'] [$ug_id] ['content_max_date_gmt'] = $ass['content_max_date_gmt'];			
				}
					
				// also save the calling function some work by returning each flavor of assignment as an array keyed by user/group id
				if ( ('children' == $assign_for) || ('both' == $assign_for) )
					$assignments[$role_handle] ['children'] [$ug_id] = $ass_id;
				
				if ( ('entity' == $assign_for) || ('both' == $assign_for) )
					$assignments[$role_handle] ['entity'] [$ug_id] = $ass_id;
					
				if ( $ass['inherited_from'] )
					$assignments[$role_handle] ['propagated'] [$ass['inherited_from']] = $ass_id;
			}
		}
		
		return $assignments;
	}

} // end class
?>