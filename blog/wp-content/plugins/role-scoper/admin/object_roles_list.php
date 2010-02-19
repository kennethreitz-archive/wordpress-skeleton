<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die( 'This page cannot be called directly.' );
	
function scoper_object_roles_list( $viewing_user, $args = '' ) {

$html = '';
	
if ( ! USER_ROLES_RS && ! GROUP_ROLES_RS )
	wp_die(__awp('Cheatin&#8217; uh?'));

$defaults = array( 'enforce_duration_limits' => true, 'is_user_profile' => false, 'echo' => true );
$args = array_merge( $defaults, (array) $args );
extract($args);

global $scoper, $wpdb, $current_user;

if ( $viewing_user ) {
	if ( ! is_object($viewing_user) ) {
		global $current_user;
		if ( $viewing_user == $current_user->ID )
			$viewing_user = $current_user;
		else
			$viewing_user = new WP_Scoped_User($viewing_user);
	}
}

$all_roles = array();
$role_display = array();
foreach ( $scoper->role_defs->get_all_keys() as $role_handle ) {
	if ( $viewing_user )
		$role_display[$role_handle] = $scoper->role_defs->get_display_name( $role_handle, OBJECT_UI_RS );
	else
		$role_display[$role_handle] = $scoper->role_defs->get_abbrev( $role_handle, OBJECT_UI_RS );
}

if ( ! $is_user_profile ) {
	$require_blogwide_editor = scoper_get_option('role_admin_blogwide_editor_only');
	
	if ( ( 'admin' === $require_blogwide_editor ) && ! is_user_administrator_rs() )
		return false;
		
	if ( ( 'admin_content' === $require_blogwide_editor ) && ! is_content_administrator_rs() )
		return false;
} else
	$require_blogwide_editor = false;
	
foreach ( $scoper->data_sources->get_all() as $src_name => $src) {
	$otype_count = 0;	
	
	if ( ! empty($src->taxonomy_only) || ( ($src_name == 'group') && ! $viewing_user ) )
		continue;

	$strict_objects = $scoper->get_restrictions(OBJECT_SCOPE_RS, $src_name);
	
	foreach ( $src->object_types as $object_type => $otype ) {
		$otype_count++;
	
		$disable_role_admin = false;
		if ( $require_blogwide_editor ) {
			$required_cap = ( 'page' == $object_type ) ? 'edit_others_pages' : 'edit_others_posts';

			global $current_user;
			if ( empty( $current_user->allcaps[$required_cap] ) )
				$disable_role_admin = true;
		}
		
		if ( ! empty($src->cols->type) && ! empty($otype->val) ) {
			$col_type = $src->cols->type;
			$otype_clause = "AND $src->table.$col_type = '$otype->val'";
		} elseif ( $otype_count < 2 )
			$otype_clause = '';
		else
			continue;
		
		$col_id = $src->cols->id;
		$col_name = $src->cols->name;
		$SCOPER_ROLE_TYPE = SCOPER_ROLE_TYPE;
		
		$ug_clause_for_user_being_viewed = ( $viewing_user ) ? $viewing_user->get_user_clause('uro') : '';
		
		// TODO: replace join with uro subselect
		
		$qry = "SELECT DISTINCT $src->table.$col_name, $src->table.$col_id, uro.role_name, uro.date_limited, uro.start_date_gmt, uro.end_date_gmt"
			. " FROM $src->table ";
		
		$join = " INNER JOIN $wpdb->user2role2object_rs AS uro"
			. " ON uro.obj_or_term_id = $src->table.$col_id"
			. " AND uro.src_or_tx_name = '$src_name'"
			. " AND uro.scope = 'object' AND uro.role_type = '$SCOPER_ROLE_TYPE'";
		
		$duration_clause = ( $enforce_duration_limits ) ? scoper_get_duration_clause( "{$src->table}.{$src->cols->date}" ) : '';
			
		$where = " WHERE 1=1 $otype_clause $duration_clause $ug_clause_for_user_being_viewed";
		$orderby = " ORDER BY $src->table.$col_name ASC, uro.role_name ASC";
		
		$qry .= $join . $where . $orderby;

		$results = scoper_get_results( $qry );
		
		if ( ! is_user_administrator_rs() ) {  // no need to filter admins - just query the assignments	
		
			// only list role assignments which the logged-in user can administer
			if ( isset($src->reqd_caps[OP_ADMIN_RS]) ) {
				$args['required_operation'] = OP_ADMIN_RS;
			} else {
				$reqd_caps = array();
				foreach (array_keys($src->statuses) as $status_name) {
					$admin_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_ADMIN_RS, $status_name);
					$delete_caps = $scoper->cap_defs->get_matching($src_name, $object_type, OP_DELETE_RS, $status_name);
					$reqd_caps[$object_type][$status_name] = array_merge(array_keys($admin_caps), array_keys($delete_caps));
				}
				$args['force_reqd_caps'] = $reqd_caps;
			}
			
			$qry = "SELECT $src->table.$col_id FROM $src->table WHERE 1=1";
			
			$args['require_full_object_role'] = true;
			$qry_flt = apply_filters('objects_request_rs', $qry, $src_name, $object_type, $args);
			$cu_admin_results = scoper_get_col( $qry_flt );
			
			if ( empty($viewing_user) || ( $current_user->ID != $viewing_user->ID ) ) {
				foreach ( $results as $key => $row )
					if ( ! in_array( $row->$col_id, $cu_admin_results) )
						unset($results[$key]);
			} else {
				// for current user's view of their own user profile, just de-link unadminable objects
				$link_roles = array();
				$link_objects = array();
				
				if ( ! $disable_role_admin ) {
					foreach ( $results as $key => $row )
						if ( in_array( $row->$col_id, $cu_admin_results) )
							$link_roles[$row->$col_id] = true;
							
					$args['required_operation'] = OP_EDIT_RS;
					$args['require_full_object_role'] = false;
					if ( isset($args['force_reqd_caps']) ) unset($args['force_reqd_caps']);
					$qry_flt = apply_filters('objects_request_rs', $qry, $src_name, $object_type, $args);
					$cu_edit_results = scoper_get_col( $qry_flt );
					
					foreach ( $results as $key => $row )
						if ( in_array( $row->$col_id, $cu_edit_results) )
							$link_objects[$row->$col_id] = true;
				}
			}
		}
		
		$object_roles = array();
		$objnames = array();
		
		if ( $results ) {
			$got_object_roles = true;
		
			foreach ( $results as $row ) {
				if ( ! isset($objnames[ $row->$col_id ]) ) {
					if ( 'post' == $src->name )
						$objnames[ $row->$col_id ] = apply_filters( 'the_title', $row->$col_name, $row->$col_id);
					else
						$objnames[ $row->$col_id ] = $row->$col_name;
				}
				
				$role_handle = SCOPER_ROLE_TYPE . '_' . $row->role_name;
				
				if ( $row->date_limited )
					$duration_key = serialize( array( 'start_date_gmt' => $row->start_date_gmt, 'end_date_gmt' => $row->end_date_gmt ) );
				else
					$duration_key = '';
				
				$object_roles[$duration_key][ $row->$col_id ] [ $role_handle ] = true;
			}
		} else
			continue;
		?>
		
		<?php
		$title_roles = __('edit roles', 'scoper');
		
		foreach ( array_keys($object_roles) as $duration_key ) {
			$date_caption = '';
			$limit_class = '';
			$limit_style = '';
			$link_class = '';
			
			if ( $duration_key ) {
				$html .= "<h3 style='margin-bottom:0'>$date_caption</h3>";	
				
				$duration_limits = unserialize( $duration_key );
				$duration_limits[ 'date_limited' ] = true;

				ScoperAdminUI::set_agent_formatting( $duration_limits, $date_caption, $limit_class, $link_class, $limit_style );
				$title = "title='$date_caption'";
				$date_caption = '<span class="rs-gray"> ' . trim($date_caption) . '</span>';
			} else
				$title = "title='$title_roles'";
			
			if ( ! $disable_role_admin && ( is_user_administrator_rs() || $cu_admin_results ) ) {
				if ( ( $src_name != $object_type ) && ( 'post' != $object_type ) ) {
					$roles_page = "rs-roles-{$object_type}_{$src_name}";
				} else {
					$roles_page = "rs-roles-$object_type";
				}
				
				$url = "admin.php?page=$roles_page";
				//$html .= "<h4><a name='$object_type' href='$url'><strong>" . sprintf( _ x('%1$s Roles%2$s:', 'Post/Page Roles', 'scoper'), $otype->display_name, '</strong></a><span style="font-weight:normal">' . $date_caption) . "</span></h4>";
				$html .= "<h4><a name='$object_type' href='$url'><strong>" . sprintf( __('%1$s Roles%2$s:', 'scoper'), $otype->display_name, '</strong></a><span style="font-weight:normal">' . $date_caption) . "</span></h4>";
			} else
				$html .= "<h4><strong>" . sprintf( __('%1$s Roles%2$s:', 'scoper'), $otype->display_name, $date_caption) . "</strong></h4>";
				//$html .= "<h4><strong>" . sprintf( _ x('%1$s Roles%2$s:', 'Post/Page Roles', 'scoper'), $otype->display_name, $date_caption) . "</strong></h4>";
			
			$html .=
			"<ul class='rs-termlist'><li>"
		
			. "<table class='widefat'>"
			. "<thead>"
			. "<tr class='thead'>"
			. "	<th class='rs-tightcol'>" . __('ID') . "</th>"
			. "	<th>" . __awp('Name') . "</th>"
			. "	<th>" . __('Role Assignments', 'scoper') . "</th>"
			. "</tr>"
			. "</thead>"
			. "<tbody id='roles-{$role_codes[$role_handle]}'>";
			
			$style = ' class="rs-backwhite"';
	
			//$title_item = sprintf(_ x('edit %s', 'post/page/category/etc.', 'scoper'), strtolower($otype->display_name) );
			$title_item = sprintf( __('edit %s', 'scoper'), strtolower($otype->display_name) );
			
			foreach ( $object_roles[$duration_key] as $obj_id => $roles ) {
				$object_name = attribute_escape($objnames[$obj_id]);
		
				$html .= "\n\t<tr$style>";
				
				$link_this_object = ( ! isset($link_objects) || isset($link_objects[$obj_id]) );
				
				// link from object ID to the object type's default editor, if defined
				if ( $link_this_object && ! empty($src->edit_url) ) {
					$src_edit_url = sprintf($src->edit_url, $obj_id);
					$html .= "<td><a href='$src_edit_url' class='edit' title='$title_item'>$obj_id</a></td>";
				} else
					$html .= "<td>$obj_id</td>";
					
				// link from object name to our "Edit Object Role Assignment" interface
				$link_this_role = ( ! isset($link_roles) || isset($link_roles[$obj_id]) );
				
				if ( $link_this_role ) {
					$rs_edit_url = "admin.php?page=rs-object_role_edit&amp;src_name=$src_name&amp;object_type=$object_type&amp;object_id=$obj_id&amp;object_name=$object_name";
					$html .= "\n\t<td><a {$title}{$limit_style}class='{$link_class}{$limit_class}' href='$rs_edit_url'>{$objnames[$obj_id]}</a></td>";
				} else
					$html .= "\n\t<td>{$objnames[$obj_id]}</td>";
				
				$html .= "<td>";
				
				$role_list = array();
				foreach ( array_keys($roles) as $role_handle ) {
					// roles which require object assignment are asterisked (bolding would contradict the notation of term roles list, where propogating roles are bolded)
					if ( isset($strict_objects['restrictions'][$role_handle][$obj_id]) 
					|| ( isset($strict_objects['unrestrictions'][$role_handle]) && is_array($strict_objects['unrestrictions'][$role_handle]) && ! isset($strict_objects['unrestrictions'][$role_handle][$obj_id]) ) )
						$role_list[] = "<span class='rs-backylw'>" . $role_display[$role_handle] . '</span>';
					else
						$role_list[] = $role_display[$role_handle];
				}
				
				$html .=( implode(', ', $role_list) );
				$html .= '</td></tr>';
	
				$style = ( ' class="alternate"' == $style ) ? ' class="rs-backwhite"' : ' class="alternate"';
			} // end foreach object_roles
		
			$html .= '</tbody></table>';
			$html .= '</li></ul><br />';
			
		} // end foreach role date range 
				
	} // end foreach object_types
	
} // end foreach data source

if ( $echo )
	echo $html;
else
	return $html;

} // end wrapper function

?>