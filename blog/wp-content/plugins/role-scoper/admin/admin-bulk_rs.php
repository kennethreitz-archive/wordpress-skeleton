<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

require_once('admin_ui_lib_rs.php');

class ScoperAdminBulk {

function get_agents($role_bases = '') {
	if ( empty($role_bases) )
		$role_bases = array(ROLE_BASIS_USER, ROLE_BASIS_GROUPS);
	
	$agents = array();
	
	if ( in_array(ROLE_BASIS_USER, $role_bases) ) {
		global $scoper;
		$agents[ROLE_BASIS_USER] = $scoper->users_who_can('', COLS_ID_DISPLAYNAME_RS);
	}
	
	if ( in_array(ROLE_BASIS_GROUPS, $role_bases) ) {
		$agents[ROLE_BASIS_GROUPS] = ScoperAdminLib::get_all_groups(UNFILTERED_RS);
		if ( ! $agents[ROLE_BASIS_GROUPS] )
			unset( $agents[ROLE_BASIS_GROUPS] );
	}
	
	return $agents;
}

function agent_names($agents) {
	$agent_names = array();
	foreach ( array_keys($agents) as $role_basis )
		foreach( $agents[$role_basis] as $agent )
			$agent_names[$role_basis][$agent->ID] = str_replace(' ', '&nbsp;', $agent->display_name);

	return $agent_names;
}

function agent_list_prefixes() {
	$agent_list_prefix = array();
	$agent_list_prefix[ROLE_BASIS_USER] = '';
	$agent_list_prefix[ROLE_BASIS_GROUPS] = __('Groups') . ': ';
	
	return $agent_list_prefix;
}

function agent_captions_plural($role_bases) {
	if ( count($role_bases) > 1 )
		return __('Users or Groups', 'scoper');
	elseif ( in_array(ROLE_BASIS_USER, $role_bases) )
		return __awp('Users');
	elseif ( in_array(ROLE_BASIS_GROUPS, $role_bases) )
		return __('Groups', 'scoper');
}

function agent_captions($role_bases) {
	if ( count($role_bases) > 1 )
		return  __('User / Group', 'scoper');
	elseif ( in_array(ROLE_BASIS_USER, $role_bases) )
		return  __('User', 'scoper');
	elseif ( in_array(ROLE_BASIS_GROUPS, $role_bases) )
		return  __('Group', 'scoper');
}

function get_role_codes() {
	global $scoper;
	
	$role_defs = $scoper->role_defs->get_matching(SCOPER_ROLE_TYPE);
	
	$role_codes = array(); //generate temporary numeric id for each defined role, to reduce html bulk
	$i = 0;
	foreach( array_keys($role_defs) as $role_handle) {
		$role_codes[$role_handle] = $i; 
		$i++;
	}
	return $role_codes;
}

function display_inputs($mode, $assignment_modes, $args = '') {
	$defaults = array( 'role_bases' => '', 'agents' => '', 'agent_caption_plural' => '', 'max_scopes' => array(), 'scope' => '', 'src_or_tx_name' => '' );
	$args = array_merge($defaults, (array) $args);
	extract($args);
	
	global $scoper;

	echo "<br /><a name='scoper_submit'></a>";

	echo '<ul class="rs-list_horiz"><li style="float:left;"><h3>1.&nbsp;';
	
	if ( ROLE_ASSIGNMENT_RS == $mode ) {
		$msg = __('Select Assignment Mode', 'scoper');
		echo "$msg</h3></li>";
		
		if ( OBJECT_SCOPE_RS == $scope ) {
			$src = $scoper->data_sources->get($src_or_tx_name);
			$date_col_defined = ! empty( $src->cols->date );
		} elseif ( TERM_SCOPE_RS == $scope ) {
			$tx = $scoper->taxonomies->get($src_or_tx_name);
			$date_col_defined = ! empty( $tx->object_source->cols->date );
		} else
			$date_col_defined = true;

		$duration_limits_enabled = $date_col_defined && scoper_get_option('role_duration_limits');
		$content_date_limits_enabled = ( OBJECT_SCOPE_RS != $scope ) && $date_col_defined && scoper_get_option('role_content_date_limits');

		$num = ( $duration_limits_enabled || $content_date_limits_enabled ) ? 5 : 4;
	} else {
		$msg = __('Select Restriction Mode', 'scoper');
		echo "$msg</h3></li>";
		$num = 3;
		
		$duration_limits_enabled = $content_date_limits_enabled = false;
	}
	
	echo "<li style='float:right;'><h3>$num.&nbsp;";
	_e('Review and Submit', 'scoper');
	echo '</h3></li>';
	echo '</ul>';
	?>

	<ul class="rs-list_horiz">
	
	<?php 
	if( ROLE_RESTRICTION_RS == $mode ) {
		echo '<li>';
		echo '<select id="max_scope" name="max_scope">';
		$retain_value = ( isset($_POST["max_scope"]) ) ? $_POST["max_scope"] : '';
		
		foreach($max_scopes as $max_scope => $caption) {
			$selected = ( $status_id === $retain_value ) ? 'selected="selected"' : '';
			echo "<option value='$max_scope' $selected>$caption</option>";
		}
		echo '</select></li>';
	}
	?>
	
	<li style="margin-left:0.5em">
	<?php
	$for_name = ( ROLE_ASSIGNMENT_RS == $mode ) ? 'assign_for' : 'require_for';
	echo "<select id='$for_name' name='$for_name'>";
		$retain_value = ( isset($_POST[$for_name]) ) ? $_POST[$for_name] : 0;

		foreach($assignment_modes as $status_id => $caption) {
			$selected = ( $status_id === $retain_value ) ? 'selected="selected"' : '';
			echo "<option value='$status_id' $selected>$caption</option>";
		} 
	echo '</select>';
	?>
	</li><li style='margin: 0 0.25em 0.25em 0.5em;padding-top:0.35em;'>
	
	</li>
	<li style='float:right;margin: 0 0.25em 0.25em 0.25em;'><span class="submit" style="border:none;">
	<input type="submit" name="rs_submit" class="button-primary" value="<?php _e('Update &raquo;', 'scoper');?>" />
	</span></li>
	</ul>
	<p style="clear:both"></p>
	<?php
	if ( ROLE_ASSIGNMENT_RS == $mode ) {
		echo '<br /><h3>2.&nbsp;';
		//printf( _ x('Select %s to Modify', 'Users or Groups', 'scoper'), $agent_caption_plural );
		printf( __('Select %s to Modify', 'scoper'), $agent_caption_plural );
		echo '</h3>';
		
		$args = array( 'suppress_extra_prefix' => true, 'filter_threshold' => 20, 'default_hide_threshold' => 20, 'check_for_incomplete_submission' => true );
		require_once('agents_checklist_rs.php');
		ScoperAgentsChecklist::all_agents_checklist($role_bases, $agents, $args);
		
		echo '<p style="clear:both"></p>';
		echo '<hr />';
	}
	//=================== end users/groups and assignment mode selection display ====================
	
	if ( $duration_limits_enabled || $content_date_limits_enabled ) {
		echo '<br /><h3 style="margin-bottom: 0">3.&nbsp;';
		_e('Set Role Duration and/or Content Date Limits (optional)', 'scoper');
		echo '</h3>';

		include_once( 'admin_lib-bulk_rs.php' );
		ScoperAdminBulkLib::display_date_limit_inputs( $duration_limits_enabled, $content_date_limits_enabled );
	}

	if ( ROLE_ASSIGNMENT_RS == $mode ) {
		$num =  ( $duration_limits_enabled || $content_date_limits_enabled ) ? 4 : 3;
		echo "<br /><h3>$num.&nbsp;";
		$msg = __('Select Roles to Assign / Remove', 'scoper');
		echo "$msg</h3>";
	} else {
		$num = 2;
		echo "<br /><h3>$num.&nbsp;";
		$msg = __('Select Roles to Modify', 'scoper');
		echo "$msg</h3>";
	}
}

function role_submission($scope, $mode, $role_bases, $src_or_tx_name, $role_codes, $agent_caption_plural, $nonce_id) {
	global $scoper;
	$role_assigner = init_role_assigner();
	
	$err = 0;
	$role_count = 0;
	check_admin_referer( $nonce_id );

	$set_roles = array();
	$selected_roles = $_POST['roles'];
	
	if ( OBJECT_SCOPE_RS == $scope ) {
		$src = $scoper->data_sources->get($src_or_tx_name);
		$date_col_defined = ! empty( $src->cols->date );
	} elseif ( TERM_SCOPE_RS == $scope ) {
		$tx = $scoper->taxonomies->get($src_or_tx_name);
		$date_col_defined = ! empty( $tx->object_source->cols->date );
	} else
		$date_col_defined = true;
	
	switch ($mode) {
		case ROLE_ASSIGNMENT_RS:
			$assign_for = $_POST['assign_for'];

			$selected_agents = array();
			foreach ( $role_bases as $role_basis ) {
				if ( ! empty($_POST[$role_basis]) )
					$selected_agents[$role_basis] = $_POST[$role_basis];
				else {
					$csv_id = "{$role_basis}_csv";
					if ( ! empty( $_POST[$csv_id] ) )
						$selected_agents[$role_basis] = ScoperAdminLib::agent_ids_from_csv( $csv_id, $role_basis );
					else 
						$role_bases = array_diff($role_bases, array($role_basis) );
				}
			}

			$agents_msg = array();
			$valid_role_selection = ! empty($selected_roles);
			
			$duration_limits_enabled = $date_col_defined && scoper_get_option('role_duration_limits');
			$content_date_limits_enabled = $date_col_defined && scoper_get_option('role_content_date_limits');
		break;
		
		case ROLE_RESTRICTION_RS:
			$role_bases = array('n/a');
			$default_restrictions = isset($_POST['default_restrictions']) ? $_POST['default_restrictions'] : array();
			$max_scope = $_POST['max_scope'];
			$require_for = $_POST['require_for'];
			$selected_agents = array('n/a' => array(0) );
			$valid_role_selection = ! empty($selected_roles) || ! empty($default_restrictions);
			$modcount = 0;
			
			$duration_limits_enabled = $content_date_limits_enabled = false;
		break;
	}

	if ( ! $selected_agents ) {
		$_POST['scoper_error'] = 1;
		echo '<div id="message" class="error"><p><strong>';
		printf( __('Error: no %s were selected!', 'scoper'), $agent_caption_plural);
		echo '</strong></p></div>';
		$err = 1;
	} elseif ( ! $valid_role_selection ) {
		$_POST['scoper_error'] = 1;
		echo '<div id="message" class="error"><p><strong>';
		_e('Error: no roles were selected!', 'scoper');
		echo '</strong></p></div>';
		$err = 2;
	} else {
		if ( ROLE_ASSIGNMENT_RS == $mode ) {
			if ( ! empty($_POST['set_role_duration']) || ! empty($_POST['set_content_date_limits']) )
				$date_entries_gmt = ScoperAdminBulkLib::process_role_date_entries();
		}

		foreach ( $role_bases as $role_basis ) {
			foreach($selected_agents[$role_basis] as $agent_id) {
				// must set default restrictions first
				if ( ! empty($default_restrictions) ) {
					$def_roles = array();
					foreach($default_restrictions as $role) {
						$keys = explode('-', $role);	//keys[0]=role_code, 1=term_id or obj_id
						
						if ( count($keys) < 2 )
							continue;
						
						if ( ! $role_handle = array_search($keys[0], $role_codes) )
							continue;
			
						$def_roles[ $keys[1] ][ $role_handle ] = array( 'max_scope' => $max_scope, 'for_item' => false, 'for_children' => true );
						$modcount++;
					}

					$role_assigner->restrict_roles($scope, $src_or_tx_name, 0, $def_roles[0], array('force_flush' => true) );
				}

				if ( ! empty($selected_roles) ) {
					foreach($selected_roles as $role) {
						$keys = explode('-', $role);	//keys[0]=role_code, 1=term_id or obj_id, 2=group_id or user_id
						
						if ( count($keys) < 2 )
							continue;
						
						if ( ! $role_handle = array_search($keys[0], $role_codes) )
							continue;
			
						switch ($mode) {
							case ROLE_ASSIGNMENT_RS:
								$set_roles[ $role_basis ][ $keys[1] ][ $role_handle ][ $agent_id ] = ( $keys[1] || ! $assign_for ) ? $assign_for : ASSIGN_FOR_CHILDREN_RS;	// always assign default category assignments as for_children
							break;
							
							case ROLE_RESTRICTION_RS:
								$for_item = (ASSIGN_FOR_ENTITY_RS == $require_for) || (ASSIGN_FOR_BOTH_RS == $require_for);
								$for_children = (ASSIGN_FOR_CHILDREN_RS == $require_for) || (ASSIGN_FOR_BOTH_RS == $require_for);
								$set_roles[ $keys[1] ][ $role_handle ] = array( 'max_scope' => $max_scope, 'for_item' => $for_item, 'for_children' => $for_children );
								$modcount++;
							break;
						}
					}
				}

				if ( ROLE_ASSIGNMENT_RS == $mode ) {
					$args = array( 'force_flush' => true, 'set_role_duration' => $set_role_duration, 'set_content_date_limits' => $set_content_date_limits );
					
					if ( $duration_limits_enabled && ! empty($_POST['set_role_duration']) ) {
						$is_limited = ( $date_entries_gmt->start_date_gmt || ( $date_entries_gmt->end_date_gmt != SCOPER_MAX_DATE_STRING ) || ! empty( $_POST['start_date_gmt_keep-timestamp'] ) || ! empty( $_POST['end_date_gmt_keep-timestamp'] ) );
						$args[ 'set_role_duration' ] = (object) array( 'date_limited' => $is_limited, 'start_date_gmt' => $date_entries_gmt->start_date_gmt, 'end_date_gmt' => $date_entries_gmt->end_date_gmt );
					}
					
					if( $content_date_limits_enabled && ! empty($_POST['set_content_date_limits']) ) {
						$is_limited = ( $date_entries_gmt->content_min_date_gmt || ( $date_entries_gmt->content_max_date_gmt != SCOPER_MAX_DATE_STRING ) || ! empty( $_POST['content_min_date_gmt_keep-timestamp'] ) || ! empty( $_POST['content_max_date_gmt_keep-timestamp'] ) );
						$args[ 'set_content_date_limits' ] = (object) array( 'content_date_limited' => $is_limited, 'content_min_date_gmt' => $date_entries_gmt->content_min_date_gmt, 'content_max_date_gmt' => $date_entries_gmt->content_max_date_gmt );	
					}

					if ( isset($set_roles[$role_basis]) )
						foreach ( $set_roles[$role_basis] as $id => $item_roles )
							$role_assigner->assign_roles($scope, $src_or_tx_name, $id, $item_roles, $role_basis, $args );
				} else {
					foreach ( $set_roles as $id => $item_roles )
						$role_assigner->restrict_roles($scope, $src_or_tx_name, $id, $item_roles, array('force_flush' => true) );
				}
			} // end foreach selected agents

			if ( ! empty($selected_agents[$role_basis]) ) {
				if ( ROLE_BASIS_USER == $role_basis )
					$agents_msg []= sprintf(_n("%d user", "%d users", count($selected_agents[$role_basis]), 'scoper'), count($selected_agents[$role_basis]) );
				else
					$agents_msg []= sprintf(_n("%d group", "%d groups", count($selected_agents[$role_basis]), 'scoper'), count($selected_agents[$role_basis]) );
			}
		} // end foreach role basis
		
		echo '<div id="message" class="updated fade"><p>';
		
		switch ($mode) {
			case ROLE_ASSIGNMENT_RS:
				$roles_msg = sprintf(_n("%d role selection", "%d role selections", count($selected_roles), 'scoper'), count($selected_roles) );
				$agents_msg = implode( ", ", $agents_msg );
				//printf( _ x('Role Assignments Updated: %1$s for %2$s', 'n role selections for x users, y groups', 'scoper'), $roles_msg, $agents_msg );
				printf( __('Role Assignments Updated: %1$s for %2$s', 'scoper'), $roles_msg, $agents_msg );
			break;
			
			case ROLE_RESTRICTION_RS:
				printf(_n("Role Restrictions Updated: %d setting", "Role Restrictions Updated: %d settings", $modcount, 'scoper'), $modcount );
			break;
		}

		echo '</p></div>';

		// allow the DB server a little time to refresh before querying what we just put in 
		global $wpdb;
		$junk = scoper_get_col("SELECT assignment_id FROM $wpdb->user2role2object_rs LIMIT 10");
	} //endif no input error
	
	return $err;
}

function get_objects_info($object_ids, &$object_names, &$object_status, &$unlisted_objects, $src, $otype, $ignore_hierarchy) {
	global $wpdb;
	
	// buffer titles in case they are translated
	if ( 'page' == $otype->val )
		$titles = ScoperAdminUI::get_page_titles();
	
	$col_id = $src->cols->id;
	$col_name = $src->cols->name;

	$cols = "$col_name, $col_id";
	if ( isset($src->cols->parent) && ! $ignore_hierarchy ) {
		$col_parent = $src->cols->parent;
		$cols .= ", $col_parent";
	} else
		$col_parent = '';
	
	$col_status = ( ! empty($src->cols->status) ) ? $src->cols->status : '';
	if ( $col_status )
		$cols .= ", $col_status";
		
	$unroled_count = 0;
	$unroled_limit = ( ! empty($otype->admin_max_unroled_objects) ) ? $otype->admin_max_unroled_objects : 999999;

	if ( ! empty($src->cols->type) && ! empty($otype->val) )
		$otype_clause = "AND {$src->cols->type} = '$otype->val'";
	else
		$otype_clause = '';
	
	$obj = '';

	if ( $results = scoper_get_results("SELECT $cols FROM $src->table WHERE 1=1 $otype_clause ORDER BY $col_id DESC") ) {
	
		foreach ( $results as $row ) {
			if ( isset($titles[$row->$col_id]) )
				$object_names[$row->$col_id] = $titles[$row->$col_id];
			elseif ( 'post' == $src->name )
				$object_names[$row->$col_id] = apply_filters( 'the_title', $row->$col_name, $row->$col_id );
			else
				$object_names[$row->$col_id] = $row->$col_name;
			
			if ( $col_status )
				$object_status[$row->$col_id] = $row->$col_status;
			
			unset($obj);
			
			if ( $col_parent )	// temporarily key by name for alpha sort of additional items prior to hierarchy sort
				$obj = (object) array($col_id => $row->$col_id, $col_name => $row->$col_name, $col_parent => $row->$col_parent);
			else
				$obj = (object) array($col_id => $row->$col_id, $col_name => $row->$col_name);
			
			// List only a limited number of unroled objects
			if ( ($unroled_limit >= 0) && ! isset($object_ids[$row->$col_id]) ) {
				if ( $unroled_count >= $unroled_limit ) {

					$unlisted_objects[$row->$col_id] = $obj;
					continue;
				}
				$unroled_count++;
				
			}
			
			$listed_objects[$row->$col_id] = $obj;
		}
	}
	
	// restore buffered page titles in case they were filtered previously
	if ( 'page' == $otype->val ) {
		scoper_restore_property_array( $listed_objects, $titles, 'ID', 'post_title' );
		scoper_restore_property_array( $unlisted_objects, $titles, 'ID', 'post_title' );
	}

	return $listed_objects;
}

function filter_objects_listing($mode, &$role_settings, $src, $object_type) {
	global $wpdb;
	
	$filter_args = array();
	
	// only list role assignments which the logged-in user can administer
	if ( isset($src->reqd_caps[OP_ADMIN_RS]) ) {
		$filter_args['required_operation'] = OP_ADMIN_RS;
	} else {
		$reqd_caps = array();
		foreach (array_keys($src->statuses) as $status_name) {
			$admin_caps = $scoper->cap_defs->get_matching($src->name, $object_type, OP_ADMIN_RS, $status_name);
			$delete_caps = $scoper->cap_defs->get_matching($src->name, $object_type, OP_DELETE_RS, $status_name);
			$reqd_caps[$object_type][$status_name] = array_merge(array_keys($admin_caps), array_keys($delete_caps));
		}
		$filter_args['force_reqd_caps'] = $reqd_caps;
	}
	
	$qry = "SELECT $src->table.{$src->cols->id} FROM $src->table WHERE 1=1";
	
	$filter_args['require_full_object_role'] = true;
	$qry_flt = apply_filters('objects_request_rs', $qry, $src->name, $object_type, $filter_args);
	
	if ( $cu_admin_results = scoper_get_col( $qry_flt ) )
		$cu_admin_results = array_fill_keys( $cu_admin_results, true );
	
	if ( ROLE_ASSIGNMENT_RS == $mode ) {
		foreach ( array_keys($role_settings) as $role_basis )
			foreach ( array_keys($role_settings[$role_basis]) as $obj_id )
				if ( ! isset($cu_admin_results[$obj_id]) )
					unset($role_settings[$role_basis][$obj_id]);
	} else {
		$setting_types = array('restrictions', 'unrestrictions');
		foreach ($setting_types as $setting_type)
			if ( isset($role_settings[$setting_type]) )
				foreach ( array_keys($role_settings[$setting_type]) as $role_handle )
					foreach ( array_keys($role_settings[$setting_type][$role_handle]) as $obj_id )
						if ( ! isset($cu_admin_results[$obj_id]) )
							unset($role_settings[$setting_type][$role_handle][$obj_id]);
	}

	return $cu_admin_results;
}

function item_tree_jslinks($mode, $args='') {
	$defaults = array ( 'role_bases' => '', 'default_hide_empty' => false, 'hide_roles' => false, 'scope' => '', 'src' => '', 'otype' => '' );
	$args = array_merge($defaults, (array) $args);
	extract($args);

	if ( (ROLE_ASSIGNMENT_RS == $mode) && empty($role_bases) )
		$role_bases = array(ROLE_BASIS_USER, ROLE_BASIS_GROUPS);

	$tr_display = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) ? 'block' : 'table-row';
	
	/* // this is problematic because some roles are mutually exclusive for assignment
	echo "<a href='javascript:void(0);' onclick=\"agp_check_by_name('roles[]', true, false, true);\">";
	_e('select all roles', 'scoper');
	echo "</a> | ";
	*/
	
	echo "<a href='javascript:void(0);' onclick=\"agp_check_by_name('roles[]', '', false, true);\">";
	_e('unselect all roles', 'scoper');
	echo '</a> | ';
	
	if ( $role_bases && in_array(ROLE_BASIS_USER, $role_bases) ) {
		echo "<a id='rs-hide_users' href='javascript:void(0);' onclick=\"agp_setcss('.user-csv','display','none');agp_set_display('rs-show_users','inline',this.id);\">";
		_e('hide users', 'scoper');
		echo '</a>';
		
		echo "<a id='rs-show_users' style='display:none;' href='javascript:void(0);' onclick=\"agp_setcss('.user-csv','display','inline');agp_set_display('rs-hide_users','inline',this.id);\">";
		_e('show users', 'scoper');
		echo '</a> | ';
	}
	
	if ( $role_bases && in_array(ROLE_BASIS_GROUPS, $role_bases) ) {
		echo "<a id='rs-hide_groups' href='javascript:void(0);' onclick=\"agp_setcss('.groups-csv','display','none');agp_set_display('rs-show_groups','inline',this.id);\">";
		_e('hide groups', 'scoper');
		echo '</a>';
		
		echo "<a id='rs-show_groups' style='display:none;' href='javascript:void(0);' onclick=\"agp_setcss('.groups-csv','display','inline');agp_set_display('rs-hide_groups','inline',this.id);\">";
		_e('show groups', 'scoper');
		echo '</a> | ';
	}
	
	if ( $hide_roles ) {
		echo "<a id='rs-hide_roles' href='javascript:void(0);' onclick=\"agp_setcss('.rs-role-tbl','display','none');agp_set_display('rs-show_roles','inline',this.id);\">";
		_e('hide roles', 'scoper');
		echo '</a>';
		
		echo "<a id='rs-show_roles' style='display:none;' href='javascript:void(0);' onclick=\"agp_setcss('.rs-role-tbl','display','block');agp_set_display('rs-hide_roles','inline',this.id);\">";
		_e('show roles', 'scoper');
		echo '</a> | ';
	}
	
	
	// Hide Empty
	$hide_tr_sfx = ( $default_hide_empty ) ? '-hide' : '';
	$hide_li_sfx = ( $default_hide_empty ) ? '-hide' : '';

	$js_call = "
	agp_set_display('rs-show_empty','inline',this.id);
	agp_display_marked_elements('li','no-rol-li{$hide_li_sfx}','none');
	agp_setcss('.no-rol{$hide_tr_sfx}','display','none');
	agp_set_display('max_unroled_notice','none');
	";
	
	if ( $role_bases )
		$js_call .= "
		agp_setcss('.user-csv','display','inline');
		agp_setcss('.groups-csv','display','inline');
		agp_set_display('max_unroled_notice','none','');
		";

	$unroled_limit = ( ! empty($otype->max_unroled_objects) ) ? $otype->max_unroled_objects : 999999;

	$style = ( $default_hide_empty ) ? ' style="display:none;"' : '';
	$title = __('hide unmodified items', 'scoper');
	echo "<a id='rs-hide_empty' href='javascript:void(0);'{$style} onclick=\"$js_call\" title='$title'>";
	if ( ROLE_RESTRICTION_RS == $mode )
		_e('hide defaulted', 'scoper');
	else
		_e('hide unassigned', 'scoper');
	echo '</a>';
	
	$js_call = "
	agp_set_display('rs-hide_empty','inline',this.id);
	agp_display_marked_elements('li','no-rol-li{$hide_li_sfx}','block');
	agp_setcss('.no-rol{$hide_tr_sfx}','display','$tr_display');
	agp_set_display('max_unroled_notice','block');
	";
	$style = ( $default_hide_empty ) ? '' : ' style="display:none;"';
	
	$display_name_plural = ( ! empty($src->display_name) ) ? strtolower($src->display_name_plural) : __('items', 'scoper');
	
	if ( ROLE_RESTRICTION_RS == $mode )
		$title = sprintf(__('include the newest %s with default restrictions', 'scoper'), $display_name_plural);
	else
		$title = sprintf(__('include the newest %s with no role assignments', 'scoper'), $display_name_plural);
	
	echo "<a id='rs-show_empty' href='javascript:void(0);'{$style} onclick=\"$js_call\" title='$title'>";
	if ( ROLE_RESTRICTION_RS == $mode )
		_e('show defaulted', 'scoper');
	else
		_e('show unassigned', 'scoper');
	
	
	// Collapse All
	if ( empty($otype->ignore_object_hierarchy) ) {
		echo '</a> | ';

		$js_call = "
		agp_set_display('rs-expand_all','inline',this.id);
		agp_display_marked_elements('li','role-li','none');
		agp_set_marked_elem_property('a','term-tgl','innerHTML','+');
		agp_display_marked_elements('span','rs-termjump','none');
		";
		echo "<a id='rs-collapse_all' href='javascript:void(0);' onclick=\"$js_call\">";
		_e('collapse all', 'scoper');
		echo '</a>';
	}

	$js_call = "
	agp_set_display('rs-collapse_all','inline',this.id);
	agp_display_marked_elements('li','role-li','block');
	agp_set_marked_elem_property('a','term-tgl','innerHTML','-');
	agp_display_marked_elements('span','rs-termjump','inline');
	";
	echo "<a id='rs-expand_all' style='display:none;' href='javascript:void(0);' onclick=\"$js_call\">";
	_e('expand all', 'scoper');
	echo '</a>';
	
	if ( ! empty($otype->admin_max_unroled_objects) ) {
		$display_style = ( $default_hide_empty ) ? 'style="display:none;"' : '';
		echo "<div id='max_unroled_notice' class='rs-warning' $display_style><br />";
		if ( ROLE_RESTRICTION_RS == $mode )
			printf(__('Note: %1$s with default restrictions will not be listed here unless they are among the %2$s newest.', 'scoper'), $display_name_plural, $otype->admin_max_unroled_objects);
		else
			printf(__('Note: %1$s with no role assignments will not be listed here unless they are among the %2$s newest.', 'scoper'), $display_name_plural, $otype->admin_max_unroled_objects);
		echo '</div>';
	}
}

function item_tree($scope, $mode, $src, $otype_or_tx, $all_items, $assigned_roles, $strict_items, $role_defs_by_otype, $role_codes, $args = '') {

	$defaults = array ( 'admin_items' => '', 	'editable_roles' => '',
				'ul_class' => 'rs-termlist', 	'ie_link_style' => '',		'object_names' => '',		
				'table_captions' => '',			'err' => '',				'object_status' => '',
				'agent_caption_plural' => '', 	'agent_list_prefix' => '', 	'agent_names' => '',
				'default_hide_empty' => false,	'role_bases' => array(ROLE_BASIS_USER, ROLE_BASIS_GROUPS),
				'single_item' => false );
	$args = array_merge($defaults, (array) $args);
	extract($args);
	
	$col_id = $src->cols->id;
	$col_name = $src->cols->name;
	$col_parent = ( isset($src->cols->parent) ) ? $src->cols->parent : '';
	
	$display_name = $otype_or_tx->display_name;
	
	if ( TERM_SCOPE_RS == $scope ) {
		$src_or_tx_name = $otype_or_tx->name;
		$edit_url_base = ( ! empty($otype_or_tx->edit_url) ) ? $otype_or_tx->edit_url : '';
	} else {
		$src_or_tx_name = $src->name;
		$edit_url_base = ( ! empty($src->edit_url) ) ? $src->edit_url : '';
	}
	
	if ( $default_hide_empty ) {
		$hide_tr_sfx = '-hide';
		$hide_li_sfx = '-hide';
	} else {
		$hide_tr_sfx = '';
		$hide_li_sfx = '';
	}
	
	$nextlink = '';
	$prevlink = '';
	
	if ( empty($admin_items) )
		$admin_items = array();
	
	if ( empty($agent_caption_plural) )
		$agent_caption_plural = __('Users or Groups', 'scoper');
	
	if ( empty($agent_list_prefix) ) {
		$agent_list_prefix = array();
		$agent_list_prefix[ROLE_BASIS_USER] = '';
		$agent_list_prefix[ROLE_BASIS_GROUPS] = __('Groups') . ': ';
	}
	
	static $prevtext, $nexttext, $is_administrator, $role_header, $agents_header;
	if ( empty($prevtext) ) {
		// buffer prev/next caption for display with each term
		//$prevtext = _ x('prev', '|abbreviated link to previous item', 'scoper');
		//$nexttext = _ x('next', '|abbreviated link to next item', 'scoper');
		$prevtext = __('prev', 'scoper');
		$nexttext = __('next', 'scoper');
		
		$is_administrator = is_administrator_rs($src, 'user');
	
		$role_header = __awp('Role');
		
		switch ( $mode ) {
			case ROLE_ASSIGNMENT_RS:
				//$agents_header = sprintf( _ x('Current %s', 'users or groups', 'scoper'), $agent_caption_plural);
				$agents_header = sprintf( __('Current %s', 'scoper'), $agent_caption_plural);
			
			break;
			case ROLE_RESTRICTION_RS:
				$agents_header = __('Current Restrictions', 'scoper');

			break;
			default:
				return;
		}
	}
	
	global $scoper;
	
	// disregard roles that don't apply to this scope
	foreach ( $role_defs_by_otype as $object_type => $role_defs )
		foreach ( $role_defs as $role_handle => $role )
			if ( ! isset($role->valid_scopes[$scope]) )
				unset( $role_defs_by_otype[$object_type][$role_handle] );
	
	// for object scope, assign "private post reader" role, but label it as "post reader" to limit confusion
	$role_display_name = array();
	foreach ( $role_defs_by_otype as $role_defs )
		foreach ( array_keys($role_defs) as $role_handle )
			$role_display_name[$role_handle] = $scoper->role_defs->get_display_name( $role_handle, $scope . '_ui' );

	// display a separate role assignment list for each individual term / object
	$last_id = -1;
	$last_name = '';
	$last_parent_id = -1;
	$parent_id = 0;
	$parents = array();
	$depth = 0;
	
	$_top_link = "<a{$ie_link_style} href='#scoper_top'>" . __('top', 'scoper') . '</a>';
	$tr_display = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false) ? 'block' : 'table-row';
	$show_all_caption = __('show all', 'scoper');
	
	echo "<ul class='$ul_class' style='padding-left:0.1em;'>";
	
	if ( empty($all_items) )
		$all_items = array();
	
	if ( ! $single_item ) {
		if ( ('rs' == SCOPER_ROLE_TYPE) || (OBJECT_SCOPE_RS != $scope) || (ROLE_ASSIGNMENT_RS != $scope) ) {	// can't distinguish between post & page default roles with WP role type
			if ( ROLE_ASSIGNMENT_RS == $mode )
				$root_caption = sprintf(__('DEFAULTS for new %s', 'scoper'), $otype_or_tx->display_name_plural);
			else
				$root_caption = sprintf(__('DEFAULTS for all %s', 'scoper'), $otype_or_tx->display_name_plural);
		
			if ( TERM_SCOPE_RS == $scope ) {
				$root_item = (object) array( $col_id => 0, $col_name => $root_caption, $col_parent => 0 );
				array_unshift( $all_items, $root_item);
			} else {
				//$all_items = array( $root_caption => (object) array($col_id => 0) ) + $all_items;
				$obj = (object) array($col_id => 0);
				$all_items = array( $root_caption => $obj ) + $all_items;
				
				$object_names[0] = $root_caption;
				
				$status_names = ( 'post' == $src->name ) ? get_post_statuses() : array();
			}
		}
	}
	
	$title_roles = __('edit roles', 'scoper');
	//$title_item = sprintf(_ x('edit %s', 'post/page/category/etc.', 'scoper'), strtolower($display_name) );
	$title_item = sprintf(__('edit %s', 'scoper'), strtolower($display_name) );
	//$title_term = sprintf(_ x('edit %s', 'category/link category/etc', 'scoper'), strtolower($display_name) );

	foreach($all_items as $key => $item) {
		$id = $item->$col_id;
		
		if ( ! empty($object_names[$id]) )
			$name = attribute_escape(str_replace(' ', '&nbsp;', $object_names[$id]) );
		else
			$name = str_replace(' ', '&nbsp;', $item->$col_name);

		if ( $col_parent && isset($item->$col_parent) ) {
			$parent_id = $item->$col_parent;
			
			if ( $parent_id != $last_parent_id ) {
				if ( ($parent_id == $last_id) && $last_id ) {
					$parents[$last_name] = $last_id;
					echo "<ul class='$ul_class'>";
					$depth++;
				} elseif ($depth) {
					do {
						//echo "term $name: depth $depth, current parents: " . print_r($parents);
						array_pop($parents);
						echo '</li></ul>';
						$depth--;
					} while ( $parents && ( end($parents) != $parent_id ) && $depth);
				}
				
				$last_parent_id = $parent_id;
			}
		}
		
		if ( $is_administrator || isset($admin_items[$last_id]) )
			if ( ! $last_id ) // always close li for defaults
				echo '</li>';
			elseif ( (-1 != $last_id) && ($parent_id != $last_id) )
				echo '</li>';
		
		if ( OBJECT_SCOPE_RS == $scope ) {
			if ( isset($object_status) && ! empty($object_status[$id]) && ('publish' != $object_status[$id] ) && ('private' != $object_status[$id] ) )
				$status_text = isset( $status_names[ $object_status[$id] ] ) ? "{$status_names[ $object_status[$id] ]}, " : "{$object_status[$id]}, ";
			else
				$status_text = '';
		
			$link_span_open = ( $status_text ) ? "<span class='rs-brown'>" : '';
			$link_span_close = ( $status_text ) ? "</span>" : '';
				
			// link from object name to our "Edit Object Role Assignment" interface
			$rs_edit_url = "admin.php?page=rs-object_role_edit&amp;src_name=$src_or_tx_name&amp;object_type={$otype_or_tx->name}&amp;object_id=$id&amp;object_name=" . urlencode($name);
			$name_text = "$link_span_open<a title='$title_roles' href='$rs_edit_url'>$name</a>$link_span_close";
			
			// link from object ID to the object type's default editor, if defined
			if ( $id && $edit_url_base ) {
				$content_edit_url = sprintf($edit_url_base, $id);
				$id_text = "<a title='$title_item' href='$content_edit_url' class='edit'>$id</a>";
			} else
				$id_text = $id;

			$id_text = ( $id ) ? " ($status_text" . sprintf(__('id %s', 'scoper'), $id_text) . ')' : '';
			
		} elseif ( $id && (TERM_SCOPE_RS == $scope) && $edit_url_base ) {
			$content_edit_url = sprintf($edit_url_base, $id);
			$name_text = "<a class='rs-dlink_rev' href='$content_edit_url' title='$title_item'>$name</a>";
			$id_text = '';
		} else {
			$name_text = $name;
			$id_text = '';
		}
		
		//display scroll links for this term
		if (TERM_SCOPE_RS == $scope)
			$prevlink = ( $last_id && ! $single_item && $id ) ? "<a{$ie_link_style} href='#item-" . $last_id . "'>" . $prevtext . "</a>" : '';
			
		if ( ! $is_administrator && ( ! isset($admin_items[$id]) ) )
			continue;
		
		$last_id = $id;
		$last_name = $name;
		$next_id = ( $id && isset($all_items[$key + 1]) ) ? $all_items[$key + 1]->$col_id : 0;
			
		if (TERM_SCOPE_RS == $scope) {
			if ( $next_id )
				$nextlink = "<a{$ie_link_style} href='#item-" . $next_id . "'>" . $nexttext . "</a>";
			elseif ( $id )
				$nextlink = "<span class='rs-termlist_linkspacer'>$nexttext</span>";
			else
				$nextlink = '';
		}
		
		if ( $parents ) {
			//$color_class = ( TERM_SCOPE_RS == $scope ) ? 'rs-lgray' : 'rs-gray';
			//$item_path = "<span class='$color_class'>" . implode(' / ', array_keys($parents)) . ' / ' . '</span>';
			$item_path = implode(' / ', array_keys($parents)) . ' / ' ;
			$margin = '';
			$top_pad = '1.5em';
		} else {
			$item_path = '';
			$margin = 'margin-top:2em;';
			$top_pad = '0.2em';
		}
		
		$js_call = "agp_toggle_display('roles-$id','block','tgl-$id', '-', '+');"
				. "agp_toggle_display('jump-$id','block');";
		
		$role_class = '';
				
		if ( $id ) { // never hide defaults block
			if ( ROLE_ASSIGNMENT_RS == $mode ) {
				$role_class = '';
				if ( ! isset($assigned_roles[ROLE_BASIS_USER][$id]) && ! isset($assigned_roles[ROLE_BASIS_GROUPS][$id]) )
					$role_class = " no-rol-li{$hide_li_sfx}";
				elseif ( ! isset($assigned_roles[ROLE_BASIS_USER][$id]) )
					$role_class = " no-user-li";
				elseif ( ! isset($assigned_roles[ROLE_BASIS_GROUPS][$id]) )
					$role_class = " no-groups-li";
			
			} elseif ( ROLE_RESTRICTION_RS == $mode ) {
				$role_class = " no-rol-li{$hide_li_sfx}";
				$setting_types = array('restrictions', 'unrestrictions');
				foreach ( $setting_types as $setting_type ) {
					if ( isset($strict_items[$setting_type]) ) {
						foreach ( array_keys($strict_items[$setting_type]) as $role_handle ) {	// key is role_handle
							if ( isset($strict_items[$setting_type][$role_handle][$id]) ) {
								$role_class = '';
								break;
							}
						}
					}
				}
			}
		}
		
		$class = ($role_class) ? "class='" . trim($role_class) . "' " : '';
		
		echo "\r\n\r\n<li {$class}style='padding:$top_pad 0.5em 0 0.3em;{$margin}'>";
		
		if ( ! $single_item ) {
			$top_link = ( $id ) ? $_top_link : '';
		
			echo "<a name='item-$id'></a>"
			. "<span id='jump-$id' class='rs-termjump alignright'>{$prevlink}{$nextlink}"
			. $top_link . '</span>'
			. "<strong><a class='rs-link_plain_rev term-tgl' id='tgl-$id' href='javascript:void(0);' onclick=\"$js_call\" title=\"{$otype_or_tx->display_name} $id: $name\">"
			. "-</a></strong> " . $item_path . '<strong>' . $name_text . '</strong>' . $id_text . ': ';
		}
		
		echo "</li><li id='roles-$id' class='role-li{$role_class}'>";
?>
<table class='rs-widefat rs-role-tbl'>
<thead>
<tr class="thead">
	<th class="rs-tightcol"><?php
	$js_call = "agp_display_child_nodes( 'tbl-$id', 'TR', '$tr_display' );";
	echo "<a href='javascript:void(0);' title='$show_all_caption' onclick=\"$js_call\">+</a>";?></th>
	<th class="rs-tightcol"><?php
	echo $role_header;?></th>
	<th><?php echo $agents_header;?></th>
</tr>
</thead>
<tbody id='<?php echo("tbl-$id");?>'>
<?php	
		// display each role eligible for group/user assignment in this term/object
		foreach ( $role_defs_by_otype as $object_type => $role_defs ) {
			$vals = array();
			$ids = array(); 
			
			if ( ! $single_item ) {
				foreach ( array_keys($role_defs) as $role_handle ) {
					// retain previous selections in case of error ( user forgets to select groups/users )
					$vals[$role_handle] = "{$role_codes[$role_handle]}-{$id}";
					
					// pre-generate all checkbox ids in this op_type, to pass to javascript
					$ids[$role_handle] = 'rs-' . $vals[$role_handle];
				}
			}
			
			foreach ( array_keys($role_defs) as $role_handle ) {
				// Does current user have this role?
				if ( ( ! $single_item && ( $is_administrator || ! is_array($editable_roles) || ! empty($editable_roles[0][$role_handle]) || ! empty($editable_roles[$id][$role_handle]) ) ) ) {
					$form_id = ( $id || (ROLE_ASSIGNMENT_RS == $mode) ) ? 'roles' : 'default_restrictions';
				
					$checked = ( $err && isset($_POST[$form_id]) && in_array($vals[$role_handle], $_POST[$form_id]) ) ? 'checked="checked"' : '';
					
					if ( ROLE_ASSIGNMENT_RS == $mode ) {
						//$skip_if_id = 'assign_for';	// reduced html bulk by making 3rd & 4th args of agp_uncheck default to these values
						//$skip_if_val = REMOVE_ASSIGNMENT_RS;
						$js_call = "agp_uncheck('" . implode(',', $ids) . "',this.id);";
						$onclick = "onclick=\"$js_call\"";
					} else
						$onclick = '';
					
					$checkbox = "<input type='checkbox' name='{$form_id}[]' id='{$ids[$role_handle]}' value='{$vals[$role_handle]}' $checked $onclick />";
					$label = "<label for='{$ids[$role_handle]}'>" . str_replace(' ', '&nbsp;', $role_display_name[$role_handle]) . "</label>";
				} else {
					$checkbox = '';
					$label = str_replace(' ', '&nbsp;', $role_display_name[$role_handle]);
				}

				$classes = array();
				

				if ( $default_strict = isset($strict_items['unrestrictions'][$role_handle]) && is_array($strict_items['unrestrictions'][$role_handle]) )
					$setting = 'unrestrictions';
				else
					$setting = 'restrictions';
				
				if ( isset($strict_items[$setting][$role_handle][$id]) ) {
					if ( $single_item ) { 
						$require_for = $strict_items[$setting][$role_handle][$id];
						$open_brace = $close_brace = '';
					} else {
						$require_for = $strict_items[$setting][$role_handle][$id]['assign_for'];
						$open_brace = ( $strict_items[$setting][$role_handle][$id]['inherited_from'] ) ? '{' : '';
						$close_brace = ( $open_brace ) ? '}' : '';
					}
				} else {
					$require_for = false;
					$open_brace = $close_brace = '';
				}


				switch ( $mode ) {
				case ROLE_ASSIGNMENT_RS:
					$open_brace = $close_brace = '';

					$assignment_list = array();
					foreach ( $role_bases as $role_basis ) {
						if ( isset($assigned_roles[$role_basis][$id][$role_handle]) ) {
							$checkbox_id = ( $single_item ) ? '' : $role_basis;
						
							$assignment_names = array_intersect_key($agent_names[$role_basis], $assigned_roles[$role_basis][$id][$role_handle]);
							$assignment_list[$role_basis] = "<span class='$role_basis-csv'><span class='rs-bold'>" . $agent_list_prefix[$role_basis] . '</span>'
							. ScoperAdminUI::role_assignment_list($assigned_roles[$role_basis][$id][$role_handle], $assignment_names, $checkbox_id, $role_basis)
							. '</span>';
						}
					}
					
					$setting_display = implode( '&nbsp;&nbsp;', $assignment_list);
					
					// don't hide rows for default roles
					if ( $id ) {
						if ( ! isset($assigned_roles[ROLE_BASIS_USER][$id][$role_handle]) && ! isset($assigned_roles[ROLE_BASIS_GROUPS][$id][$role_handle]) )
							$classes []= "no-rol{$hide_tr_sfx}";
						elseif ( ! isset($assigned_roles[ROLE_BASIS_USER][$id][$role_handle]) )
							$classes []= "no-user";
						elseif ( ! isset($assigned_roles[ROLE_BASIS_GROUPS][$id][$role_handle]) )
							$classes []= "no-groups";
					}
						
				break;
				case ROLE_RESTRICTION_RS:
					if ( ! $id )
						$setting_display = $table_captions[$setting]['default'];
					elseif ( $require_for )
						$setting_display = $table_captions[$setting][$require_for];
					else {
						$setting_display = '(' . $table_captions[$setting][false] . ')';
						
						// don't hide rows for default restrictions
						if ( $id )
							$classes []= " no-rol{$hide_tr_sfx}";
					}
				} // end switch $mode

				switch ( $require_for ) {
					case ASSIGN_FOR_BOTH_RS:
						$open_brace = '<span class="rs-bold">' . $open_brace;
						$close_brace .= '</span>';
					break;
					case ASSIGN_FOR_CHILDREN_RS:
						$open_brace = '<span class="rs-gray">' . $open_brace;
						$close_brace .= '</span>';
				} // end switch
				
				if ( ( empty($default_strict) && $require_for && ($require_for != ASSIGN_FOR_CHILDREN_RS) ) || ( ! empty($default_strict) && ! $require_for) )
					$classes []= 'rs-backylw';
					
				$class = ($classes) ? " class='" . implode(' ', $classes) . "'" : '';
				
				echo "\r\n"
					. "<tr{$class}>"
					. "<td>$checkbox</td>"
					. "<td>$label</td>"
					. "<td>{$open_brace}$setting_display{$close_brace}</td>"
					. "</tr>";
			} // end foreach role

		} // end foreach object_type
		
		echo '</tbody></table>';
	} // end foreach term
	
	while($depth) {
		echo '</li></ul>';
		$depth--;
	}
	
	echo '</li>';
	echo '</ul><br /><ul>';
	
	// now display "select all" checkboxes for all terms in this taxonomy
	if ( empty( $single_item ) ) {
		if ( defined('SCOPER_EXTRA_SUBMIT_BUTTON') ) {
			echo '<li class="alignright"><span class="submit" style="border:none;"><input type="submit" name="rs_submit" value="' 
			. __('Update &raquo;', 'scoper') 
			. '" /></span></li>';
		}
?>
		<li><table class='widefat' style='width:auto;'>
		<thead>
		<tr class="thead">
		<th colspan="2"><?php printf(__('select / unselect all:', 'scoper'), strtolower($otype_or_tx->display_name_plural))?></th>
		<!--<th colspan="2" style="text-align: center"><?php _e('Actions') ?></th>-->
		</tr>
		</thead>
		<tbody id="term_roles-<?php echo($taxonomy);?>">
<?php
		//convert allterms stdobj to array for implosion
		$all_items_arr = array();
		foreach( $all_items as $item )
			$all_items_arr[] = $item->$col_id;
			
		$all_items_ser = implode('-', $all_items_arr);
		
		//display "check for every term" shortcuts for each individual role
		global $scoper;
		$style = ' class="rs-backwhite"';
		foreach ( $role_defs_by_otype as $object_type => $roles ) {
			foreach ( array_keys($roles) as $role_handle ) {
				$style = ( ' class="alternate"' == $style ) ? ' class="rs-backwhite"' : ' class="alternate"';

				// $check_shorcut was displayed in first <td>
				$id = "rs-Z-{$role_codes[$role_handle]}";
				$caption = ' <span class="rs-subtext">' . sprintf( __('(all %s)', 'scoper'), strtolower($otype_or_tx->display_name_plural) ) . '</span>'; 
				$js_call = "scoper_checkroles('$id', '$all_items_ser', '{$role_codes[$role_handle]}');";
				echo "\n\t<tr $style>"
					. "<td><input type='checkbox' id='$id' onclick=\"$js_call\" /></td>"
					. "<td><label for='$id'>" . $scoper->role_defs->get_display_name( $role_handle, $scope . '_ui' ) . "{$caption}</label></td>"
					. "</tr>";
			} // end foreach role
		} // end foreach roledef
		
		echo '</tbody></table></li></ul><br />';
	} // endif not single item
	
} // end function item_tree

} // end class ScoperAdminBulk

?>