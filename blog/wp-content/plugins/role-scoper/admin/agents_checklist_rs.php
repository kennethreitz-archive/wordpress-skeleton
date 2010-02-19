<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

/**
 * ScoperAgentsChecklist PHP class for the WordPress plugin Role Scoper
 * agents_checklist_rs.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 */
 
define ('CURRENT_ITEMS_RS', 'current');
define ('ELIGIBLE_ITEMS_RS', 'eligible');
 
 class ScoperAgentsChecklist {
	function all_agents_checklist( $role_bases, $agents, $args, $class = 'rs-agents' ) {
		$groups_url = 'admin.php?page=rs-groups';
		$div_style = "class='$class' style='padding:0.5em 0 0.5em 0.5em'";
		
		//if ( in_array(ROLE_BASIS_GROUPS, $role_bases) && $agents[ROLE_BASIS_GROUPS] )
		//	$edit_groups_link = sprintf(_ x('%1$s define user groups%2$s', 'Args are link open, close tags', 'scoper'), "<a href='$groups_url'>", '</a>');
		//else
		//	$edit_groups_link = sprintf(_ x('<strong>Note:</strong> To assign roles to user groups, first %1$s define the group(s)%2$s.', 'Args are link open, close tags', 'scoper'), "<a href='$groups_url'>", '</a>');
		
		if ( in_array(ROLE_BASIS_GROUPS, $role_bases) && $agents[ROLE_BASIS_GROUPS] )
			$edit_groups_link = sprintf(__('%1$s define user groups%2$s', 'scoper'), "<a href='$groups_url'>", '</a>');
		else
			$edit_groups_link = sprintf(__('<strong>Note:</strong> To assign roles to user groups, first %1$s define the group(s)%2$s.', 'scoper'), "<a href='$groups_url'>", '</a>');
		
			
		foreach ( $role_bases as $role_basis ) {
			echo "<div $div_style>";
			ScoperAgentsChecklist::agents_checklist($role_basis, $agents[$role_basis], $role_basis, array(), $args);
			
			if ( ROLE_BASIS_GROUPS == $role_basis )
				echo $edit_groups_link;
				
			echo "</div>";
		}
		
		if ( ! in_array(ROLE_BASIS_GROUPS, $role_bases) )
			echo "<div $div_style>$edit_groups_link</div>";
	}
	
	function agents_checklist( $role_basis, $all_agents, $id_prefix = '', $stored_assignments = '', $args = '') {
		if ( empty($all_agents) && ! scoper_get_option("{$role_basis}_role_assignment_csv" ) )
			return;

		$key = array();
		$action_links = array();
		
		global $is_IE;
		
		// list current selections on top first
		if ( $stored_assignments ) {
			ScoperAgentsChecklist::_agents_checklist_display( CURRENT_ITEMS_RS, $role_basis, $all_agents, $id_prefix, $stored_assignments, $args, $key, $action_links); 
			if ( $is_IE )
				echo '<p class="rs-agents-spacer-ie">&nbsp;</p>';
		}
			
		ScoperAgentsChecklist::_agents_checklist_display( ELIGIBLE_ITEMS_RS, $role_basis, $all_agents, $id_prefix, $stored_assignments, $args, $key, $action_links); 
		if ( $is_IE )
			echo '<div class="rs-agents-spacer-ie">&nbsp;</div>';
		
		echo '<div style="clear:both; height:1px; margin:0">&nbsp;</div>';
		
		if ( $action_links ) {
			echo "<div class='rs-keytext' style='margin: 1em 0 1em 0'>";
			echo( sprintf( __('Actions: %s', 'scoper'), implode(' &nbsp; ', $action_links) ) );
			echo '</div>';
		}
		
		if ( $key ) {
			if ( empty($args['suppress_extra_prefix']) )
				$id_prefix .= "_{$role_basis}";

			echo "<div class='rs-keytext' id='rs-rolekey_{$id_prefix}' style=' 1em 0 0 0'>";
			//echo( _ x('Key:', 'explanation of user/group role symbolic prefix/suffix', 'scoper') );
			echo( __('Key:', 'scoper') );	
			echo '<p style="margin-top: 0.2em">';
			echo ( implode(' &nbsp; ', $key) );
			echo '</p></div>';
		}
	}
	
	function eligible_agents_input_box( $role_basis, $id_prefix, $propagation ) {
		$id = "{$id_prefix}_csv";
		$msg = ( ROLE_BASIS_GROUPS == $role_basis ) ? __( "Enter additional Group Names or IDs (comma-separate)", 'scoper') : __( "Enter additional User Names or IDs (comma-separate)", 'scoper');
		echo '<br /><div class="rs-agents_caption"><strong>' . $msg . ':</strong></div>';
		echo "<input name='$id' type='text' style='width: 99%' id='$id' />";
		
		if ( $propagation ) {
			echo '<br />';
			$msg = ( ROLE_BASIS_GROUPS == $role_basis ) ? __( "Enter additional Group Names or IDs for Subpages", 'scoper') : __( "Enter additional User Names or IDs for Subpages", 'scoper');
			echo '<br /><div class="rs-agents_caption"><strong>' . $msg . ':</strong></div>';
			echo "<input name='p_{$id}' type='text' style='width: 99%' id='p_{$id}' />";
		}
	}
	
	// stored_assignments[agent_id][inherited_from] = progenitor_assignment_id (note: this function treats progenitor_assignment_id as a boolean)
	function _agents_checklist_display( $agents_subset, $role_basis, $all_agents, $id_prefix, $stored_assignments, $args, &$key, &$action_links) {
		$defaults = array( 
		'eligible_ids' => '', 			'locked_ids' => '',
		'suppress_extra_prefix' => false, 					 				'check_for_incomplete_submission' => false,
		'checkall_threshold' => 6,		'filter_threshold' => 10, 			'default_hide_threshold' => 20,
		'caption_length_limit' => 20, 	'emsize_threshold' => 4, 
		'objtype_display_name' => '', 	'objtype_display_name_plural' => '',
		'propagation' => false, 		'for_children_ids' => '', 			'for_entity_ids' => '',
		'via_other_scope_ids' => '', 	'via_other_scope_prefix' => '/', 	'via_other_scope_suffix' => '/',
		'via_other_role_ids' => '', 	'via_other_role_prefix' => '(', 	'via_other_role_suffix' => ')',
		'via_other_basis_ids' => '', 	'via_other_basis_prefix' => "|", 	'via_other_basis_suffix' => '|',
		'inherited_prefix' => '{', 		'inherited_suffix' => '}' );

		$args = array_merge( $defaults, (array) $args );
		extract($args);
		
		global $is_IE;
		$ie_checkbox_style = ( $is_IE ) ? "style='height:1em'" : '';
		
		if ( ( ELIGIBLE_ITEMS_RS == $agents_subset ) && scoper_get_option("{$role_basis}_role_assignment_csv") )
			return ScoperAgentsChecklist::eligible_agents_input_box( $role_basis, $id_prefix, $propagation );

		if ( is_array($eligible_ids) && empty($eligible_ids) )
			$eligible_ids = array(-1);
		else
			if ( ! is_array($eligible_ids) ) $eligible_ids = array(); else $eligible_ids = array_flip($eligible_ids);

		if ( ! is_array($stored_assignments) ) $stored_assignments = array();
		if ( ! is_array($locked_ids) ) $locked_ids = array(); else $locked_ids = array_flip($locked_ids);
		if ( ! is_array($for_children_ids) ) $for_children_ids = array(); else $for_children_ids = array_flip($for_children_ids);
		if ( is_array($for_entity_ids) && ! empty($for_entity_ids) ) $for_entity_ids = array_flip($for_entity_ids);
		if ( ! $via_other_scope_ids || ! is_array($via_other_scope_ids) ) $via_other_scope_ids = array(); else $via_other_scope_ids = array_flip($via_other_scope_ids);
		if ( ! is_array($via_other_role_ids) ) $via_other_role_ids = array(); else $via_other_role_ids = array_flip($via_other_role_ids);
		if ( ! is_array($via_other_basis_ids) ) $via_other_basis_ids = array(); else $via_other_basis_ids = array_flip($via_other_basis_ids);
		
		if ( ! $suppress_extra_prefix )
			$id_prefix .= "_{$role_basis}";
		
		$any_inherited = $any_other_scope = $any_other_role = $any_other_basis = $any_date_limits = false;
		$agent_count = array();
		
		$agent_count[CURRENT_ITEMS_RS] = count($stored_assignments);
		
		if ( empty($eligible_ids) )
			$agent_count[ELIGIBLE_ITEMS_RS] = count($all_agents) - count( $stored_assignments );
		elseif ( $eligible_ids != array(-1) )
			$agent_count[ELIGIBLE_ITEMS_RS] = count( array_diff_key($eligible_ids, $stored_assignments) );
		else
			$agent_count[ELIGIBLE_ITEMS_RS] = 0;
					
		$default_hide_filtered_list = ( $default_hide_threshold && ( $agent_count[$agents_subset] > $default_hide_threshold ) );
			
		$checked = ( $agents_subset == CURRENT_ITEMS_RS ) ? $checked = "checked='checked'" : '';

		// determine whether to show caption, show/hide checkbox and filter textbox
		$any_display_filtering = ($agent_count[CURRENT_ITEMS_RS] > $filter_threshold) || ($agent_count[ELIGIBLE_ITEMS_RS] > $filter_threshold);
		
		if ( $agent_count[$agents_subset] > $filter_threshold ) {
			if ( ROLE_BASIS_GROUPS == $role_basis )
				$caption = ( CURRENT_ITEMS_RS == $agents_subset ) ? __('show current groups (%d)', 'scoper') : __('show eligible groups (%d)', 'scoper');
			else
				$caption = ( CURRENT_ITEMS_RS == $agents_subset ) ? __('show current users (%d)', 'scoper') : __('show eligible users (%d)', 'scoper');

			$js_call = "agp_display_if('div_{$agents_subset}_{$id_prefix}', this.id);"
					. "agp_display_if('chk-links_{$agents_subset}_{$id_prefix}', this.id);";
	
			$flt_checked = ( ! $default_hide_filtered_list ) ? "checked='checked'" : '';
	
			$ul_class = 'rs-agents-ul';
			
			echo "<ul class='rs-list_horiz $ul_class'><li>"; // IE6 (at least) does not render label reliably without this
			echo "<input type='checkbox' name='rs-jscheck[]' value='validate_me_{$agents_subset}_{$id_prefix}' id='chk_{$agents_subset}_{$id_prefix}' $flt_checked onclick=\"$js_call\" $ie_checkbox_style /> ";
			
			echo "<strong><label for='chk_{$agents_subset}_{$id_prefix}'>";
			printf ($caption, $agent_count[$agents_subset]);
			echo '</label></strong>';
			echo '</li>';
			
			$class = ( $default_hide_filtered_list ) ? '' : 'class="agp_js_show"';
			
			echo "\r\n" . "<li style='clear:both;'>&nbsp;&nbsp;<label for='flt_{$agents_subset}_{$id_prefix}' id='lbl_flt_{$id_prefix}'>";
			_e ( 'filter:', 'scoper');
			$js_call = "agp_filter_ul('list_{$agents_subset}_{$id_prefix}', this.value, 'chk_{$agents_subset}_{$id_prefix}', 'chk-links_{$agents_subset}_{$id_prefix}');";
			echo " <input type='text' id='flt_{$agents_subset}_{$id_prefix}' size='10' onkeyup=\"$js_call\" />";
			echo "</label></li>";
			
			echo "<li $class style='display:none;' id='chk-links_{$agents_subset}_{$id_prefix}'>";
		
			$js_call = "agp_check_by_name('{$id_prefix}[]', true, true, false, 'list_{$agents_subset}_{$id_prefix}', 1);";
			echo "\r\n" . "&nbsp;&nbsp;" . "<a href='javascript:void(0)' onclick=\"$js_call\">";
			_e ('select', 'scoper');
			echo '</a>&nbsp;&nbsp;';
			
			$js_call = "agp_check_by_name('{$id_prefix}[]', '', true, false, 'list_{$agents_subset}_{$id_prefix}', 1);";
			echo "\r\n" . "<a href='javascript:void(0)' onclick=\"$js_call\">";
			_e( 'unselect', 'scoper');
			echo "</a>";
				
			if ( $propagation ) {
				$js_call = "agp_check_by_name('p_{$id_prefix}[]', true, true, false, 'list_{$agents_subset}_{$id_prefix}', 1);";
				echo "\r\n" . "&nbsp;&nbsp;" . "<a href='javascript:void(0)' onclick=\"$js_call\">";
				_e ('propagate', 'scoper');
				echo '</a>&nbsp;&nbsp;';
				
				$js_call = "agp_check_by_name('p_{$id_prefix}[]', '', true, false, 'list_{$agents_subset}_{$id_prefix}', 1);";
				echo "\r\n" . "<a href='javascript:void(0)' onclick=\"$js_call\">";
				_e( 'unpropagate', 'scoper');
				echo "</a>";
			}
			
			echo '</li></ul>';
			
		} else {
			$ul_class = '';
			
			if ( $agent_count[$agents_subset] ) {
				echo "<ul class='rs-list_horiz rs-agents_filter $ul_class'><li>";
				if ( ROLE_BASIS_GROUPS == $role_basis )
					$caption = ( CURRENT_ITEMS_RS == $agents_subset ) ? __('current groups (%d):', 'scoper') : __('eligible groups (%d):', 'scoper');
				else
					$caption = ( CURRENT_ITEMS_RS == $agents_subset ) ? __('current users (%d):', 'scoper') : __('eligible users (%d):', 'scoper');
	
				printf ("<div class='rs-agents_caption'><strong>$caption</strong></div>", $agent_count[$agents_subset]);
				echo '</li></ul>';
			}
		}
	
		$title = '';
		if ( $propagation ) {
			if ( ! $objtype_display_name )
				$objtype_display_name = __('object', 'scoper');
			
			if ( ! $objtype_display_name_plural )
				$objtype_display_name_plural = __('objects', 'scoper');
		}
		
		if ( $any_display_filtering || $agent_count[$agents_subset] > $emsize_threshold ) {
			global $wp_locale;
			$rtl = ( isset($wp_locale) && ('rtl' == $wp_locale->text_direction) );
			
			// -------- determine required list item width -----------
			if ( $caption_length_limit > 40 )
				$caption_length_limit = 40;
			
			if ( $caption_length_limit < 10 )
				$caption_length_limit = 10;
			
			$longest_caption_length = 0;
			
			foreach( $all_agents as $agent ) {
				$id = $agent->ID;
				
				if ( is_array($for_entity_ids) )
					$role_assigned = isset($for_entity_ids[$id]) || isset($for_children_ids[$id]) ;
				else
					$role_assigned = isset($stored_assignments[$id]);
				
				switch ( $agents_subset ) {
					case CURRENT_ITEMS_RS:
						if ( ! $role_assigned ) continue 2;
						break;
					default: //ELIGIBLE_ITEMS_RS
						if ( $role_assigned ) continue 2;
						if ( $eligible_ids && ! isset($eligible_ids[$id] ) ) continue 2;
				}
				
				$caption = ( ( ROLE_BASIS_GROUPS == $role_basis ) && $agent->meta_id ) ? ScoperAdminLib::get_metagroup_name( $agent->meta_id ) : $agent->display_name;
				
				if ( $role_assigned && ! empty($stored_assignments[$id]['inherited_from']) )
					$caption = $inherited_prefix . $caption . $inherited_suffix;

				elseif ( ! $role_assigned && isset($via_other_basis_ids[$id]) )
					$caption = $via_other_basis_prefix . $caption . $via_other_basis_suffix;
					
				elseif ( isset($via_other_role_ids[$id]) )
					$caption = $via_other_role_prefix . $caption . $via_other_role_suffix;

				elseif ( isset($via_other_scope_ids[$id]) )
					$caption = $via_other_scope_prefix . $caption . $via_other_scope_suffix;

				if ( strlen($caption) > $longest_caption_length ) {
					if ( strlen($caption) >= $caption_length_limit )
						$longest_caption_length = $caption_length_limit + 2;
					else
						$longest_caption_length = strlen($caption);
				}
			}
			
			if ( $longest_caption_length < 10 )
				$longest_caption_length = 10;
			
			//if ( ! $ems_per_character = scoper_get_option('ems_per_character') )
			if ( defined( 'UI_EMS_PER_CHARACTER') )
				$ems_per_character = UI_EMS_PER_CHARACTER;
			else
				$ems_per_character = 0.85;
			
			$list_width_ems = $ems_per_character * $longest_caption_length;
			
			if ( $propagation )
				$list_width_ems = $list_width_ems + 1.0;

			$ems_integer = intval($list_width_ems);
			$ems_half = ( ($list_width_ems - $ems_integer) >= 0.5 ) ? '_5' : '';
			
			$ul_class = "rs-agents_list_{$ems_integer}{$ems_half}";
			$hide_class = ( $default_hide_filtered_list && $agent_count[$agents_subset] > $filter_threshold ) ? 'class="agp_js_hide"' : '';

			echo "\r\n" . "<div id='div_{$agents_subset}_{$id_prefix}' $hide_class>"
				. "<div class='rs-agents_emsized'>"
				. "<ul class='$ul_class' id='list_{$agents_subset}_{$id_prefix}'>";	
		} else {
			$ul_class = "rs-agents_list_auto";
			echo "\r\n<ul class='$ul_class' id='list_{$agents_subset}_{$id_prefix}'>";		
		}
		//-------- end list item width determination --------------
	
		$last_agents = array();
		$last_agents_prop = array();
		
		foreach( $all_agents as $agent ) {
			$id = $agent->ID;
			$agent_display_name = ( ( ROLE_BASIS_GROUPS == $role_basis ) && $agent->meta_id ) ? ScoperAdminLib::get_metagroup_name( $agent->meta_id ) : $agent->display_name;
			
			if ( is_array($for_entity_ids) )
				$role_assigned = isset($for_entity_ids[$id]) || isset($for_children_ids[$id]) ;
			else
				$role_assigned = isset($stored_assignments[$id]);
			
			switch ( $agents_subset ) {
				case CURRENT_ITEMS_RS:
					if ( ! $role_assigned ) continue 2;
					break;
				default: //ELIGIBLE_ITEMS_RS
					if ( $role_assigned ) continue 2;
					if ( $eligible_ids && ! isset($eligible_ids[$id] ) ) continue 2;
			}
			
			// markup for role duration / content date limits
			$title = '';			// we can't set the title because it's used by JS for onkey filtering
			$limit_class = '';
			$link_class = '';
			$limit_style = '';
			
			if ( isset( $stored_assignments[$id] ) )
				ScoperAdminUI::set_agent_formatting( $stored_assignments[$id], $title, $limit_class, $link_class, $limit_style );

			if ( $title ) {
				$any_date_limits = true;
				$label_title = " title='$title'";
			} else
				$label_title = '';
				
			$disabled = ( $locked_ids && isset($locked_ids[$id]) ) ? " disabled='disabled'" : '';
			
			$li_title = "title=' " . strtolower($agent_display_name) . " '";
			
			if ( $check_for_incomplete_submission && isset($_POST['scoper_error']) && isset($_POST[$id_prefix]) )
				$this_checked = ( in_array($id, $_POST[$id_prefix]) ) ? ' checked="checked"' : '';
			else {
				if ( $role_assigned && ( ! is_array($for_entity_ids) || isset($for_entity_ids[$id]) ) )
					$this_checked = ' checked="checked"';
				else
					$this_checked = '';
			}
			
			if ( $this_checked )
				$last_agents[] = $id;

			if ( isset($via_other_role_ids[$id]) )
				$label_class = " class='rs-via-r{$limit_class}'";
				
			elseif ( ! $role_assigned && isset($via_other_basis_ids[$id]) )
				$label_class =  " class='rs-via-b{$limit_class}'";
					
			elseif ( isset($via_other_scope_ids[$id]) )
				$label_class = " class='rs-via-s{$limit_class}'";
			elseif( $limit_class )
				$label_class = " class='" . trim($limit_class) . "'";
			else
				$label_class = '';
				
				
			echo "\r\n<li $li_title>"
				. "<input type='checkbox' name='{$id_prefix}[]'{$disabled}{$this_checked} value='$id' id='{$id_prefix}{$id}' $ie_checkbox_style />";
				
			if ( $propagation ) {
				if ( $check_for_incomplete_submission && isset($_POST['scoper_error']) && isset($_POST["p_{$id_prefix}"]) )
					$this_checked_prop = ( in_array($id, $_POST["p_{$id_prefix}"]) ) ? ' checked="checked"' : '';
				else {
					if ( isset($for_children_ids[$id]) )
						$this_checked_prop = " checked='checked'";
					else
						$this_checked_prop = '';
				}
				
				if ( $this_checked_prop )
					$last_agents_prop[] = $id;
				
				echo "{"
					. "<input type='checkbox' name='p_{$id_prefix}[]'{$disabled}{$this_checked_prop} value='$id' id='p_{$id_prefix}{$id}' $ie_checkbox_style />"
					. "}";
			}
			
			echo "<label $title $limit_style for='{$id_prefix}{$id}'{$label_class}{$label_title}>";
			
			$caption = $agent_display_name;
			
			if ( strlen($caption) > $caption_length_limit ) {
				if ( $rtl )
					$caption = '...' . substr( $caption, strlen($caption) - $caption_length_limit); 
				else
					$caption = substr($caption, 0, $caption_length_limit) . '...';
			}
			
			if ( $role_assigned && ! empty($stored_assignments[$id]['inherited_from']) ) {
				$caption = $inherited_prefix . $caption . $inherited_suffix;
				$any_inherited = true;
				
			} elseif ( isset($via_other_role_ids[$id]) ) {
				$caption = $via_other_role_prefix . $caption . $via_other_role_suffix;
				$any_other_role = true;

			} elseif ( ! $role_assigned && isset($via_other_basis_ids[$id]) ) {
				$caption = $via_other_basis_prefix . $caption . $via_other_basis_suffix;
				$any_other_basis = true;

			} elseif ( isset($via_other_scope_ids[$id]) ) {
				$caption = $via_other_scope_prefix . $caption . $via_other_scope_suffix;
				$any_other_scope = true;
			}
			
			$caption = ' ' . $caption;
				
			echo $caption; // str_replace(' ', '&nbsp;', $caption);
			echo '</label></li>';
			
		} //foreach agent
		
		echo "\r\n<li></li></ul>"; // prevent invalid markup if no other li's
		
		if ( CURRENT_ITEMS_RS == $agents_subset ) {
			$last_agents = implode("~", $last_agents);
			$last_agents_prop = implode("~", $last_agents_prop);
			echo "<input type=\"hidden\" id=\"last_{$id_prefix}\" name=\"last_{$id_prefix}\" value=\"$last_agents\" />";
			echo "<input type=\"hidden\" id=\"last_p_{$id_prefix}\" name=\"last_p_{$id_prefix}\" value=\"$last_agents_prop\" />";
		}
		
		if ( $any_display_filtering || $agent_count[$agents_subset] > $emsize_threshold ) 
			echo '</div></div>';
			
		// display key
		/*
		if ( $any_inherited && $inherited_prefix )
			$key ['inherited']= "$inherited_prefix $inherited_suffix"
				 . '<span class="rs-keytext">' . sprintf(_ x('inherited from parent %s', 'user/group role status key: this role is assigned via parent object', 'scoper'), strtolower($objtype_display_name)) . '</span>';
		
		if ( $any_other_role && $via_other_role_prefix )
			$key ['other_role']= "<span class='rs-via-r'>{$via_other_role_prefix}&nbsp;{$via_other_role_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', _ x('has via other role', 'user/group role status key: all caps in this role are assigned via another role', 'scoper') ) . '</span></span>';
		
		if ( $any_other_basis && $via_other_basis_prefix )
			$key ['other_basis']= "<span class='rs-via-b'>{$via_other_basis_prefix}&nbsp;{$via_other_basis_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', _ x('has via group', 'user role status key: this role is assigned to a group the user is in', 'scoper') ) . '</span></span>';
		
		if ( $any_other_scope && $via_other_scope_prefix )
			$key ['other_scope']= "<span class='rs-via-s'>{$via_other_scope_prefix}&nbsp;{$via_other_scope_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', _ x('has via other scope', 'user role status key: this role is assigned blog-wide or term-wide', 'scoper') ) . '</span></span>';
		 
		if ( $propagation )
			$key ['propagation']= "{<input type='checkbox' disabled='disabled' name='rs-prop_key_{$agents_subset}_{$id_prefix}' id='rs-prop_key_{$agents_subset}_{$id_prefix}' $ie_checkbox_style />}"
				 . '<span class="rs-keytext">' . sprintf(_ x('propagate to sub-%s', 'user/group role status key: propagate this role to sub-objects', 'scoper'), strtolower($objtype_display_name_plural)) . '</span>';
		*/
		
		if ( $any_inherited && $inherited_prefix )
			$key ['inherited']= "$inherited_prefix $inherited_suffix"
				 . '<span class="rs-keytext">' . sprintf(__('inherited from parent %s', 'scoper'), strtolower($objtype_display_name)) . '</span>';
		
		if ( $any_other_role && $via_other_role_prefix )
			$key ['other_role']= "<span class='rs-via-r'>{$via_other_role_prefix}&nbsp;{$via_other_role_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', __('has via other role', 'scoper') ) . '</span></span>';
		
		if ( $any_other_basis && $via_other_basis_prefix )
			$key ['other_basis']= "<span class='rs-via-b'>{$via_other_basis_prefix}&nbsp;{$via_other_basis_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', __('has via group', 'scoper') ) . '</span></span>';
		
		if ( $any_other_scope && $via_other_scope_prefix )
			$key ['other_scope']= "<span class='rs-via-s'>{$via_other_scope_prefix}&nbsp;{$via_other_scope_suffix}"
				 . '<span class="rs-keytext">' . str_replace( ' ', '&nbsp;', __('has via other scope', 'scoper') ) . '</span></span>';
		 
		if ( $propagation )
			$key ['propagation']= "{<input type='checkbox' disabled='disabled' name='rs-prop_key_{$agents_subset}_{$id_prefix}' id='rs-prop_key_{$agents_subset}_{$id_prefix}' $ie_checkbox_style />}"
				 . '<span class="rs-keytext">' . sprintf(__('propagate to sub-%s', 'scoper'), strtolower($objtype_display_name_plural)) . '</span>';
	
		
		if ( $any_date_limits && $object_id )
			$action_links ['limits']= sprintf( __('%1$sEdit date limits%2$s', 'scoper'), "<a href='admin.php?page=rs-$object_type-roles#item-$object_id'>", '</a>' );
	}

} // end class
?>