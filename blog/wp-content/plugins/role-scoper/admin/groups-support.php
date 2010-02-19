<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) ) 
	die('This page cannot be called directly.');

/* this file adapted from:
 Group Restriction plugin
 http://code.google.com/p/wp-group-restriction/
 Tiago Pocinho, Siemens Networks, S.A.
 
 some group-related functions also moved to ScoperAdminLib with slight adaptation
 */

class UserGroups_tp {
	function getUsersWithGroup($group_id) {
		return ScoperAdminLib::get_group_members($group_id);
	}
	
	function addGroupMembers ($group_id, $user_ids){
		ScoperAdminLib::add_group_user($group_id, $user_ids);
	}
	
	function deleteGroupMembers ($group_id, $user_ids) {
		ScoperAdminLib::remove_group_user($group_id, $user_ids);
	}
		
	
	function GetGroup($group_id) {
		return ScoperAdminLib::get_group($group_id);
	}
	
	function getGroupByName($name) {
		return ScoperAdminLib::get_group_by_name($name);
	}
	
	/**
	 * Creates a new Group
	 *
	 * @param string $name - Name of the group
	 * @param string $description - Group description (optional)
	 * @return group ID on successful creation
	 **/
	function createGroup ($name, $description = ''){
		global $wpdb;

		if( ! UserGroups_tp::isValidName($name) )
			return false;

		$insert = "INSERT INTO $wpdb->groups_rs ($wpdb->groups_name_col, $wpdb->groups_descript_col) VALUES ('$name','$description')";
		scoper_query( $insert );

		wpp_cache_flush_group('all_usergroups');
		wpp_cache_flush_group('group_members' );
		wpp_cache_flush_group('usergroups_for_user');
		wpp_cache_flush_group('usergroups_for_groups');
		wpp_cache_flush_group('usergroups_for_ug');
		
		do_action('created_group_rs', (int) $wpdb->insert_id);
		
		return (int) $wpdb->insert_id;
	}

	
	/**
	 * Removes a given group
	 *
	 * @param int $id - Identifier of the group to delete
	 * @param boolean True if the deletion is successful
	 **/
	function deleteGroup ($group_id){
		global $wpdb;

		$role_type = SCOPER_ROLE_TYPE;
		
		if( ! $group_id || ! UserGroups_tp::getGroup($group_id) )
			return false;

		do_action('delete_group_rs', $group_id);
		
		wpp_cache_flush_group( 'all_usergroups' );
		wpp_cache_flush_group( 'group_members' );
		wpp_cache_flush_group( 'usergroups_for_user' );
		wpp_cache_flush_group( 'usergroups_for_groups' );
		wpp_cache_flush_group( 'usergroups_for_ug' );
		
		// first delete all cache entries related to this group
		if ( $group_members = ScoperAdminLib::get_group_members( $group_id, COL_ID_RS ) ) {
			$id_in = "'" . implode("', '", $group_members) . "'";
			$any_user_roles = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE role_type = '$role_type' AND user_id IN ($id_in) LIMIT 1");
			
			foreach ($group_members as $user_id )
				wpp_cache_delete( $user_id, 'group_membership_for_user' );
		}
		
		//if ( $got_blogrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = '$role_type' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'blog' AND role_type = '$role_type' AND group_id = '$group_id'");
		
			scoper_flush_roles_cache( BLOG_SCOPE_RS, ROLE_BASIS_GROUPS );
			
			if ( $any_user_roles )
				scoper_flush_roles_cache( BLOG_SCOPE_RS, ROLE_BASIS_USER_AND_GROUPS, $group_members );
		//}
		
		//if ( $got_taxonomyrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'term' AND role_type = '$role_type' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'term' AND role_type = '$role_type' AND group_id = '$group_id'");
		
			scoper_flush_roles_cache( TERM_SCOPE_RS, ROLE_BASIS_GROUPS );
			
			if ( $any_user_roles )
				scoper_flush_roles_cache( TERM_SCOPE_RS, ROLE_BASIS_USER_AND_GROUPS, $group_members );
		//}
		
		//if ( $got_objectrole = scoper_get_var("SELECT assignment_id FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND role_type = '$role_type' AND group_id = '$group_id' LIMIT 1") ) {
			scoper_query("DELETE FROM $wpdb->user2role2object_rs WHERE scope = 'object' AND role_type = '$role_type' AND group_id = '$group_id'");

			scoper_flush_roles_cache( OBJECT_SCOPE_RS, ROLE_BASIS_GROUPS );
			
			if ( $any_user_roles )
				scoper_flush_roles_cache( OBJECT_SCOPE_RS, ROLE_BASIS_USER_AND_GROUPS, $group_members );
		//}
		
		//if ( $got_blogrole || $got_taxonomyrole || $got_objectrole ) {
			scoper_flush_results_cache( ROLE_BASIS_GROUPS );
			
			if ( $any_user_roles )
				scoper_flush_results_cache( ROLE_BASIS_USER_AND_GROUPS, $group_members );
		//}
		
		$delete = "DELETE FROM $wpdb->groups_rs WHERE $wpdb->groups_id_col='$group_id'";
		scoper_query( $delete );

		$delete = "DELETE FROM $wpdb->user2group_rs WHERE $wpdb->user2group_gid_col='$group_id'";
		scoper_query( $delete );
		
		return true;
	}

	/**
	 * Checks if a group with a given name exists
	 *
	 * @param string $name - Name of the group to test
	 * @return boolean True if the group exists, false otherwise.
	 **/
	function groupExists($name) {
		global $wpdb;

		$query = "SELECT COUNT(*) FROM $wpdb->groups_rs WHERE $wpdb->groups_name_col = '$name'";
		$results = scoper_get_var( $query );
		
		return $results != 0;
	}
	
	/**
	 * Verifies if a group name is valid (for a new group)
	 *
	 * @param string $string - Name of the group
	 * @return boolean True if the name is valid, false otherwise.
	 **/
	function isValidName($string){
		if($string == "" || UserGroups_tp::groupExists($string)){
			return false;
		}
		return true;
	}

	/**
	 * Updates an existing Group
	 *
	 * @param int $groupID - Group identifier
	 * @param string $name - Name of the group
	 * @param string $description - Group description (optional)
	 * @return boolean True on successful update
	 **/
	function updateGroup ($group_id, $name, $description = ''){
		global $wpdb;

		$description = strip_tags($description);

		if ( $prev = scoper_get_row("SELECT * FROM $wpdb->groups_rs WHERE $wpdb->groups_id_col='$group_id';") ) {
		
			if( ($prev->{$wpdb->groups_name_col} != $name) && ! UserGroups_tp::isValidName($name))
				return false;
				
			// don't allow updating of metagroup name / descript
			if( $prev->meta_id )
				return false;
		}
			
		do_action('update_group_rs', $group_id);
			
		$query = "UPDATE $wpdb->groups_rs SET $wpdb->groups_name_col = '$name', $wpdb->groups_descript_col='$description' WHERE $wpdb->groups_id_col='$group_id';";
		scoper_query( $query );

		wpp_cache_flush_group('all_usergroups');
		wpp_cache_flush_group('group_members' );
		wpp_cache_flush_group('usergroups_for_user');
		wpp_cache_flush_group('usergroups_for_groups');
		wpp_cache_flush_group('usergroups_for_ug');
		
		return true;
	}

	
	// Called once each for members checklist, managers checklist in admin UI.
	// In either case, current (checked) members are at the top of the list.
	function group_members_checklist( $group_id, $user_class = 'member', $all_users = '' ) {
		global $scoper;
		
		if ( ! $all_users )
			$all_users = $scoper->users_who_can('', COLS_ID_DISPLAYNAME_RS);
		
		if ( $group_id )
			$group = ScoperAdminLib::get_group($group_id);
			
		if ( 'manager' == $user_class ) {
			if ( $group_id ) {
				$group_role_defs = $scoper->role_defs->qualify_roles( 'manage_groups');

				require_once('role_assignment_lib_rs.php');
				$current_roles = ScoperRoleAssignments::organize_assigned_roles(OBJECT_SCOPE_RS, 'group', $group_id, array_keys($group_role_defs), ROLE_BASIS_USER);

				$current_roles = agp_array_flatten($current_roles, false);
				
				$current_ids = ( isset($current_roles['assigned']) ) ? $current_roles['assigned'] : array();
			} else
				$current_ids = array();
			
			$cap_name = ( defined( 'SCOPER_USER_ADMIN_CAP' ) ) ? constant( 'SCOPER_USER_ADMIN_CAP' ) : 'edit_users';
			$admin_ids = $scoper->users_who_can( $cap_name, COL_ID_RS );
				
			$require_blogwide_editor = false;
					
			if ( ! empty($group) ) {
				if ( ! strpos( $group->meta_id, '_nr_' ) ) {	// don't limit manager selection for groups that don't have role assignments
					$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');
				}
			}
				
			if ( 'admin' == $require_blogwide_editor ) {
				$eligible_ids = $admin_ids;
				
			} elseif ( 'admin_content' == $require_blogwide_editor ) {
				$cap_name = ( defined( 'SCOPER_CONTENT_ADMIN_CAP' ) ) ? constant( 'SCOPER_CONTENT_ADMIN_CAP' ) : 'activate_plugins';
				$eligible_ids = array_unique( array_merge( $admin_ids, $scoper->users_who_can( $cap_name, COL_ID_RS ) ) );
				
			} elseif ( $require_blogwide_editor ) {
				$post_editors = $scoper->users_who_can('edit_others_posts', COL_ID_RS);
				$page_editors = $scoper->users_who_can('edit_others_pages', COL_ID_RS);
				
				$eligible_ids = array_unique( array_merge($post_editors, $page_editors, $admin_ids) );
			
			} else
				$eligible_ids = '';

		} else {
			$current_ids = ($group_id) ? array_flip(ScoperAdminLib::get_group_members($group_id, COL_ID_RS)) : array();

			if ( ! empty($group) && in_array( $group->meta_id, array( 'rv_pending_rev_notice_ed_nr_', 'rv_scheduled_rev_notice_ed_nr_' ) ) ) {
				$args = array( 'any_object' => true );
				$post_eligible_ids = $scoper->users_who_can( array("edit_published_posts", "edit_others_posts"), COL_ID_RS, 'post', 0, $args );
				$page_eligible_ids = $scoper->users_who_can( array("edit_published_pages", "edit_others_pages"), COL_ID_RS, 'post', 0, $args );
				$eligible_ids = array_unique( array_merge( $post_eligible_ids, $page_eligible_ids ) );
			} else {
				// force_all_users arg is a temporary measure to ensure that any user can be viewed / added to a sitewide MU group regardless of what blog backend it's edited through 
				$_args = ( IS_MU_RS && scoper_get_option( 'mu_sitewide_groups', true ) ) ? array( 'force_all_users' => true ) : array();

				$eligible_ids = $scoper->users_who_can( '', COL_ID_RS, '', '', $_args );
			}
			
			$admin_ids = array();
		}
		
		$css_id = ( 'manager' == $user_class ) ? 'manager' : 'member';
		$args = array( 'eligible_ids' => $eligible_ids, 'via_other_scope_ids' => $admin_ids, 'suppress_extra_prefix' => true );
 		require_once('agents_checklist_rs.php');
		ScoperAgentsChecklist::agents_checklist( ROLE_BASIS_USER, $all_users, $css_id, $current_ids, $args);
	}
	
	/**
	 * Writes the success/error messages
	 * @param string $string - message to be displayed
	 * @param boolean $success - boolean that defines if is a success(true) or error(false) message
	 **/
	function write($string, $success=true, $id="message"){
		if($success){
			echo '<div id="'.$id.'" class="updated fade"><p>'.$string.'</p></div>';
		}else{
			echo '<div id="'.$id.'" class="error fade"><p>'.$string.'</p></div>';
		}
	}
}

?>
