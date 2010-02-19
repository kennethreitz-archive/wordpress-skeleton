<?php

add_action( 'create_category', 'scoper_watch_for_subscribe2_autosub' );

/*
// temp debug
global $subscribe2_category_rs;
$subscribe2_category_rs = 83;

global $wpdb;
scoper_limit_subscribe2_autosub("SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key='s2_autosub' AND $wpdb->usermeta.meta_value='yes'");
*/

function scoper_watch_for_subscribe2_autosub($cat_id) {
	add_filter( 'query', 'scoper_limit_subscribe2_autosub', 99 );
	
	global $subscribe2_category_rs;
	$subscribe2_category_rs = $cat_id;
}

function scoper_limit_subscribe2_autosub( $query ) {
	global $wpdb;

	if ( "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key='s2_autosub' AND $wpdb->usermeta.meta_value='yes'" == $query ) {
		global $scoper, $subscribe2_category_rs;

		//rs_errlog("subscribe2 cat creation: $subscribe2_category_rs");

		$post_roles = $scoper->role_defs->qualify_roles( 'read', SCOPER_ROLE_TYPE, 'post' );

		// WP roles containing the 'activate plugins' capability are always honored regardless of object or term restritions
		$admin_roles_wp = array();
		global $wp_roles;
		if ( isset($wp_roles->roles) ) {
			$admin_cap_name = ( defined( 'SCOPER_CONTENT_ADMIN_CAP' ) ) ? constant( 'SCOPER_CONTENT_ADMIN_CAP' ) : 'activate_plugins';
			
			foreach (array_keys($wp_roles->roles) as $wp_role_name)
				if ( ! empty($wp_roles->roles[$wp_role_name]['capabilities']) )
					if ( array_intersect_key($wp_roles->roles[$wp_role_name]['capabilities'], array($admin_cap_name => 1) ) )
						$admin_roles_wp = array_merge($admin_roles_wp, array($wp_role_name => 1) );
		}
						
		if ( $admin_roles_wp )
			$admin_roles_wp = scoper_role_names_to_handles(array_keys($admin_roles_wp), 'wp', true);  //arg: return as array keys

		$args = array( 'id' => $subscribe2_category_rs );
		$restrictions = $scoper->get_restrictions( TERM_SCOPE_RS, 'category', $args );

		$restricted_roles = array();
		
		if ( ! empty($restrictions['unrestrictions']) ) {
			if ( $restrictions['unrestrictions'] = array_intersect_key( $restrictions['unrestrictions'], $post_roles ) ) {
				foreach ( $restrictions['unrestrictions'] as $role_handle => $entries )
					if ( ! isset($entries[$subscribe2_category_rs]) || ( 'children' == $entries[$subscribe2_category_rs] ) )
						$restricted_roles [$role_handle] = true;
			}
		}
		
		if ( ! empty($restrictions['restrictions']) ) {
			if ( $restrictions['restrictions'] = array_intersect_key( $restrictions['restrictions'], $post_roles ) ) {
				foreach ( $restrictions['restrictions'] as $role_handle => $entries )
					if ( isset($entries[$subscribe2_category_rs]) && ( 'children' != $entries[$subscribe2_category_rs] ) )
						$restricted_roles [$role_handle] = true;
			}
		}
		
		$unrestricted_roles = array_diff_key( $post_roles, $restricted_roles );
		
		// for our purposes, a role is only restricted if all its contained qualifying roles are also restricted
		if ( $restricted_roles ) {
			foreach ( array_keys($restricted_roles) as $role_handle ) {
				if ( $contained_roles = $scoper->role_defs->get_contained_roles($role_handle, false, SCOPER_ROLE_TYPE) )
					if ( $contained_roles = array_intersect_key( $contained_roles, $unrestricted_roles ) )
						unset ( $restricted_roles[$role_handle] );
			}
			
			$unrestricted_roles = array_diff_key( $post_roles, $restricted_roles );
		}
		
		// account for WP blog roles
		if ( 'rs' == SCOPER_ROLE_TYPE ) {
			$unrestricted_roles_wp = array();
			$restricted_roles_wp = array();
			
			// Todo: modify qualify_roles to make passing of object_types equivalent to passing exclude_object_types, regarding handling of otype-ambiguous caps
			if ( $post_roles_wp = $scoper->role_defs->qualify_roles( 'read', 'wp', '', array( 'exclude_object_types' => array('page') ) ) ) {
				foreach ( array_keys($post_roles_wp) as $wp_role )
					if ( $contains_rs_roles = $scoper->role_defs->get_contained_roles($wp_role, false, 'rs') ) {
						if ( $contains_rs_roles = array_intersect_key($contains_rs_roles, $unrestricted_roles) )
							$unrestricted_roles_wp = array_merge($unrestricted_roles_wp, array($wp_role => true) );
					}

				$restricted_roles_wp = array_diff_key($post_roles_wp, $unrestricted_roles_wp);
			}
			
			$unrestricted_roles_wp = array_merge($unrestricted_roles_wp, $admin_roles_wp);
			$role_in_wp = implode( "', '", scoper_role_handles_to_names(array_keys($unrestricted_roles_wp)) );
		} else
			$unrestricted_roles = array_merge($unrestricted_roles, $admin_roles_wp);

		/*
		dump($post_roles);
		dump($restricted_roles);
		dump($restricted_roles_wp);
		dump($unrestricted_roles);
		dump($unrestricted_roles_wp);
		*/

		$role_type = SCOPER_ROLE_TYPE;
		
		// account for blog roles, where allowed
		if ( $unrestricted_roles ) {
			$wp_role_clause = ( ! empty($role_in_wp) ) ? "OR ( role_type = 'wp' AND scope = 'blog' AND role_name IN ('$role_in_wp') )" : '';
			
			$role_in = implode( "', '", scoper_role_handles_to_names(array_keys($unrestricted_roles)) );
			
			$qry = "SELECT DISTINCT user_id FROM $wpdb->user2role2object_rs"
				. " WHERE user_id > 0 AND ("
				. " ( role_type = '$role_type' AND scope = 'blog' AND role_name IN ('$role_in') ) $wp_role_clause )";

			$users = scoper_get_col( $qry );

			$qry = "SELECT DISTINCT group_id FROM $wpdb->user2role2object_rs"
				. " WHERE group_id > 0 AND ("
				. " ( role_type = '$role_type' AND scope = 'blog' AND role_name IN ('$role_in') ) $wp_role_clause )";

			if ( $groups = scoper_get_col( $qry ) ) {
				foreach ( $groups as $group_id )
					if ( $group_members = ScoperAdminLib::get_group_members($group_id, $cols, true) )
						$users = array_merge( $users, $group_members );
						
				$users = array_unique($users);
			}
		} else
			$users = array();
		
		// account for category roles
		$role_in = implode( "', '", scoper_role_handles_to_names(array_keys($post_roles)) );
		
		$qry = "SELECT DISTINCT user_id FROM $wpdb->user2role2object_rs"
			. " WHERE user_id > 0 AND role_type = '$role_type' AND scope = 'term' AND role_name IN ('$role_in')"
			. " AND assign_for IN ('entity', 'both')"
			. " AND src_or_tx_name = 'category' AND obj_or_term_id = '$subscribe2_category_rs'";
		
		$catrole_users = scoper_get_col( $qry );
		$users = array_merge( $users, $catrole_users );

		$qry = "SELECT DISTINCT group_id FROM $wpdb->user2role2object_rs"
			. " WHERE group_id > 0 AND role_type = '$role_type' AND scope = 'term' AND role_name IN ('$role_in')"
			. " AND assign_for IN ('entity', 'both')"
			. " AND src_or_tx_name = 'category' AND obj_or_term_id = '$subscribe2_category_rs'";

		if ( $groups = scoper_get_col( $qry ) ) {
			foreach ( $groups as $group_id )
				if ( $group_members = ScoperAdminLib::get_group_members($group_id, $cols, true) )
					$users = array_merge( $users, $group_members );
					
			$users = array_unique($users);
		}

		if ( $users )
			$query .= " AND user_id IN ('" . implode( "', '", $users) . "')";
		else
			$query .= ' AND 1=2';

		remove_filter( 'query', 'scoper_limit_subscribe2_autosub', 99 );
	}
	return $query;
}
?>