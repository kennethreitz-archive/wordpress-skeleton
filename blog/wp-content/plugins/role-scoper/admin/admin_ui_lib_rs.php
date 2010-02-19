<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();
	
if ( awp_ver('2.7-dev') )
	add_filter('wp_dropdown_pages', array('ScoperAdminUI', 'flt_dropdown_pages') );  // WP < 2.7 must parse low-level query

/**
 * ScoperAdminUI PHP class for the WordPress plugin Role Scoper
 * scoper_admin_ui_lib.php
 * 
 * @author 		Kevin Behrens
 * @copyright 	Copyright 2009
 * 
 * Used by Role Scoper Plugin as a container for statically-called functions
 * These function can be used during activation, deactivation, or other 
 * scenarios where no Scoper or WP_Scoped_User object exists
 *
 */
class ScoperAdminUI {
	function role_assignment_list($roles, $agent_names, $checkbox_base_id = '', $role_basis = 'user') {
		$agent_grouping = array();
		$agent_list = array();
		$role_propagated = array();

		 if ( ! $checkbox_base_id )
			$link_end = '';
		
		$date_limits = array();
			
		// This would sort entire list (currently grouping by assign_for and alphabetizing each grouping)
		//$sorted_roles = array();
		//uasort($agent_names, 'strnatcasecmp');
		//foreach ( $agent_names as $agent_id => $agent_name )
		//	$sorted_roles[$agent_id] = $roles[$agent_id];
		foreach( $roles as $agent_id => $val ) { 
			if ( $limitation_type = $val['date_limited'] + ( 2 * $val['content_date_limited'] ) )
				$date_limits[ $agent_id ] = $val;
			
			if ( is_array($val) && ! empty($val['inherited_from']) )
				$role_propagated[$agent_id] = true;
		
			if ( is_array($val) && ( 'both' == $val['assign_for'] ) )
				$agent_grouping[$limitation_type][ASSIGN_FOR_BOTH_RS] [$agent_id]= $agent_names[$agent_id];
			
			elseif ( is_array($val) && ( 'children' == $val['assign_for'] ) )
				$agent_grouping[$limitation_type][ASSIGN_FOR_CHILDREN_RS] [$agent_id]= $agent_names[$agent_id];
				
			else
				$agent_grouping[$limitation_type][ASSIGN_FOR_ENTITY_RS] [$agent_id]= $agent_names[$agent_id];
		}
		
		
		// display for_entity assignments first, then for_both, then for_children
		$assign_for_order = array( 'entity', 'both', 'children');
		
		$use_agents_csv = scoper_get_option("{$role_basis}_role_assignment_csv");
		
		foreach ( array_keys($agent_grouping) as $limitation_type ) {
			
			foreach ( $assign_for_order as $assign_for ) {
				if ( ! isset($agent_grouping[$limitation_type][$assign_for]) )
					continue;
					
				// sort each assign_for grouping alphabetically
				uasort($agent_grouping[$limitation_type][$assign_for], 'strnatcasecmp');
				
				foreach ( $agent_grouping[$limitation_type][$assign_for] as $agent_id => $agent_name ) {
					// surround rolename with bars to indicated it was inherited
					$pfx = ( isset($role_propagated[$agent_id]) ) ? '{' : '';
					$sfx = '';
					
					if ( $checkbox_base_id ) {
						if ( $use_agents_csv )
							$js_call = "agp_append('{$role_basis}_csv', ', $agent_name');";
						else
							$js_call = "agp_check_it('{$checkbox_base_id}{$agent_id}');";
						
						$link_end = " href='javascript:void(0)' onclick=\"$js_call\">";
						$sfx = '</a>';
					}
						
					// surround rolename with braces to indicated it was inherited
					if ( $pfx )
						$sfx .= '}';
					
					$limit_class = '';
					$limit_style = '';
					$link_class = 'rs-link_plain';
					$title_text = '';
					
					if ( $limitation_type ) {
						ScoperAdminUI::set_agent_formatting( $date_limits[$agent_id], $title_text, $limit_class, $link_class, $limit_style );
						$title = "title='$title_text'";
					} else
						$title = "title='select'";

					switch ( $assign_for ) {
						case ASSIGN_FOR_BOTH_RS:
							//roles which are assigned for entity and children will be bolded in list
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class}{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_BOTH_RS] [$agent_id]= $pfx . $link . $agent_name . $sfx;
					
						break;
						case ASSIGN_FOR_CHILDREN_RS:
							//roles with are assigned only to children will be grayed
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class} rs-gray{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_CHILDREN_RS] [$agent_id]= $pfx . "<span class='rs-gray'>" . $link . $agent_names[$agent_id] . $sfx . '</span>';
							
						break;
						case ASSIGN_FOR_ENTITY_RS:
							$link = ( $link_end ) ? "<a {$title}{$limit_style}class='{$link_class}{$limit_class}'" . $link_end : '';
							$agent_list[$limitation_type][ASSIGN_FOR_ENTITY_RS] [$agent_id]= $pfx . $link . $agent_names[$agent_id] . $sfx;
					}
				} // end foreach agents

				$agent_list[$limitation_type][$assign_for] = implode(', ', $agent_list[$limitation_type][$assign_for]);
					
				if ( ASSIGN_FOR_ENTITY_RS != $assign_for )
					$agent_list[$limitation_type][$assign_for] = "<span class='rs-bold'>" .  $agent_list[$limitation_type][$assign_for] . '</span>';
			} // end foreach assign_for
			
			$agent_list[$limitation_type] = implode(', ', $agent_list[$limitation_type]);
		}
			
		if ( $agent_list )
			return implode(', ', $agent_list);
	}
	
	
	function set_agent_formatting( $date_limits, &$title, &$limit_class, &$link_class, &$limit_style, $title_wrap = true ) {
		
		static $current_gmt, $default_gmt_time, $gmt_seconds, $datef_no_time, $datef_time;
		static $starts_caption, $started_caption, $expired_caption, $expires_caption, $content_range_caption, $content_min_caption, $content_max_caption;
		
		if ( ! isset( $current_gmt ) ) {
			$current_gmt = agp_time_gmt();
			$gmt_offset = get_option( 'gmt_offset' );
			$gmt_seconds = $gmt_offset * 3600;
			
			$default_gmt_hour = - intval( $gmt_offset );
			if ( $default_gmt_hour < 0 )
				$default_gmt_hour = 24 + $default_gmt_hour;
			
			$default_gmt_time = "{$default_gmt_hour}:00";	// comparison string to determine whether date limit entry has a non-default time value
			if ( $gmt_offset < 10 )
				$default_gmt_time = '0' . $default_gmt_time;	
				
			$datef_no_time = __awp( 'M j, Y' );
			$datef_time = __awp( 'M j, Y G:i' );
			
			$starts_caption = __( 'TO START on %s', 'scoper' );
			$started_caption = __( 'started on %s', 'scoper' );
			$expired_caption = __( 'EXPIRED on %s', 'scoper' );
			$expires_caption = __( 'expire on %s', 'scoper' );
			
			$content_range_caption = __( '(for content %1$s to %2$s)', 'scoper' );
			$content_min_caption = __( '(for content after %1$s)', 'scoper' );
			$content_max_caption = __( '(for content before %1$s)', 'scoper' );
		}
		
		$title_captions = array();
		$content_title_caption = '';
		
		if ( ! empty($date_limits['date_limited']) ) {
			if ( $date_limits['start_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
				$limit_class .= ' rs-has_start';
				
				$start_date_gmt = strtotime( $date_limits['start_date_gmt'] );
				$datef = ( strpos( $date_limits['start_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( $start_date_gmt > $current_gmt ) {
					//$limit_class .= ' rs-future';
					$limit_style = 'style="background-color: #cfc" ';
					$title_captions []= sprintf( $starts_caption, agp_date_i18n( $datef, $start_date_gmt + $gmt_seconds ) );
				} else
					$title_captions []= sprintf( $started_caption, agp_date_i18n( $datef, $start_date_gmt + $gmt_seconds ) );
			}
				
			if ( $date_limits['end_date_gmt'] != SCOPER_MAX_DATE_STRING ) {
				$limit_class .= ' rs-has_end';
				
				$end_date_gmt = strtotime( $date_limits['end_date_gmt'] );
				$datef = ( strpos( $date_limits['end_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( strtotime( $date_limits['end_date_gmt'] ) < $current_gmt ) {
					//$limit_class .= ' rs-expired';	
					$limit_style = 'style="background-color: #fcc" ';
					$title_captions []= sprintf( $expired_caption, agp_date_i18n( $datef, $end_date_gmt + $gmt_seconds ) );
				} else
					$title_captions []= sprintf( $expires_caption, agp_date_i18n( $datef, $end_date_gmt + $gmt_seconds ) );
			} 
		}
		
		if ( ! empty($date_limits['content_date_limited']) ) {
			if ( $date_limits['content_min_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
				$limit_class .= ' rs-has_cmin';
				$link_class = 'rs-custom_link';
				
				$content_min_date_gmt = strtotime( $date_limits['content_min_date_gmt'] );
				$datef_min = ( strpos( $date_limits['content_min_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
			}

			if ( $date_limits['content_max_date_gmt'] != SCOPER_MAX_DATE_STRING ) {
				$limit_class .= ' rs-has_cmax';
				
				$content_max_date_gmt = strtotime( $date_limits['content_max_date_gmt'] );
				$datef_max = ( strpos( $date_limits['content_max_date_gmt'], $default_gmt_time ) ) ? $datef_no_time : $datef_time;
				
				if ( $date_limits['content_min_date_gmt'] != SCOPER_MIN_DATE_STRING ) {
					$content_title_caption = sprintf( $content_range_caption, agp_date_i18n( $datef_min, $content_min_date_gmt + $gmt_seconds ), agp_date_i18n( $datef_max, $content_max_date_gmt + $gmt_seconds ) );
				} else
					$content_title_caption = sprintf( $content_max_caption, agp_date_i18n( $datef_max, $content_max_date_gmt + $gmt_seconds ) );
			} else
				$content_title_caption = sprintf( $content_min_caption, agp_date_i18n( $datef_min, $content_min_date_gmt + $gmt_seconds ) );
		}
		
		$title = implode(", ", $title_captions) . ' ' . $content_title_caption;
	}
		
	
	function restriction_captions( $scope, $tx = '', $display_name = '', $display_name_plural = '') {
		$table_captions = array();
	
		if ( TERM_SCOPE_RS == $scope ) {
			if ( ! $display_name_plural ) 
				$display_name_plural = ( 'link_category' == $tx->name ) ? strtolower( $this->scoper->taxonomies->member_property('category', 'display_name_plural') ) : strtolower($tx->display_name_plural);
			if ( ! $display_name ) 
				$display_name = ( 'link_category' == $tx->name ) ? strtolower( $this->scoper->taxonomies->member_property('category', 'display_name') ) : strtolower($tx->display_name);
		}
		
		$table_captions = array();
		$table_captions['restrictions'] = array(	// captions for roles which are NOT default strict
			ASSIGN_FOR_ENTITY_RS => sprintf(__('Restricted for %s', 'scoper'), $display_name), 
			ASSIGN_FOR_CHILDREN_RS => sprintf(__('Unrestricted for %1$s, Restricted for sub-%2$s', 'scoper'), $display_name, $display_name_plural), 
			ASSIGN_FOR_BOTH_RS => sprintf(__('Restricted for selected and sub-%s', 'scoper'), $display_name_plural),
			false => sprintf(__('Unrestricted by default', 'scoper'), $display_name),
			'default' => sprintf(__('Unrestricted', 'scoper'), $display_name)
		);
		$table_captions['unrestrictions'] = array( // captions for roles which are default strict
			ASSIGN_FOR_ENTITY_RS => sprintf(__('Unrestricted for %s', 'scoper'), $display_name), 
			ASSIGN_FOR_CHILDREN_RS => sprintf(__('Unrestricted for sub-%s', 'scoper'), $display_name_plural), 
			ASSIGN_FOR_BOTH_RS => sprintf(__('Unrestricted for selected and sub-%s', 'scoper'), $display_name_plural),
			false => sprintf(__('Restricted by default', 'scoper'), $display_name),
			'default' => sprintf(__('Restricted', 'scoper'), $display_name)
		);
		
		return $table_captions;
	}
	
	function role_owners_key($tx_or_otype, $args = '') {
		$defaults = array( 'display_links' => true, 'display_restriction_key' => true, 'restriction_caption' => '',
							'role_basis' => '', 'agent_caption' => '' );
		$args = array_merge( $defaults, (array) $args);
		extract($args);
	
		$display_name_plural = strtolower($tx_or_otype->display_name_plural);
		$display_name = strtolower($tx_or_otype->display_name);

		if ( $role_basis ) {
			if ( ! $agent_caption && $role_basis )
				$agent_caption = ( ROLE_BASIS_GROUPS == $role_basis ) ? __('Group', 'scoper') : __('User', 'scoper');
				$generic_name = ( ROLE_BASIS_GROUPS == $role_basis ) ? __('Groupname', 'scoper') : __('Username', 'scoper');
		} else
			$generic_name = __awp('Name');
			
		$agent_caption = strtolower($agent_caption);
		
		echo '<h4 style="margin-bottom:0.1em"><a name="scoper_key"></a>' . __("Users / Groups Key", 'scoper') . ':</h4><ul class="rs-agents_key">';	
		
		$link_open = ( $display_links ) ? "<a class='rs-link_plain' href='javascript:void(0)'>" : '';
		$link_close = ( $display_links ) ? '</a>' : '';
		
		echo '<li>';
		echo "{$link_open}$generic_name{$link_close}: ";
		printf (__('%1$s has role assigned for the specified %2$s.', 'scoper'), $agent_caption, $display_name);
		echo '</li>';
		
		echo '<li>';
		echo "<span class='rs-bold'>{$link_open}$generic_name{$link_close}</span>: ";
		printf (__('%1$s has role assigned for the specified %2$s and, by default, for all its sub-%3$s. (Propagated roles can also be explicitly removed).', 'scoper'), $agent_caption, $display_name, $display_name_plural);
		echo '</li>';
		
		echo '<li>';
		echo "<span class='rs-bold rs-gray'>{$link_open}$generic_name{$link_close}</span>: ";
		printf (__('%1$s does NOT have role assigned for the specified %2$s, but has it by default for sub-%3$s.', 'scoper'), $agent_caption, $display_name, $display_name_plural);
		echo '</li>';
		
		echo '<li>';
		echo '<span class="rs-bold">{' . "{$link_open}$generic_name{$link_close}" . '}</span>: ';
		printf (__('%1$s has this role via propagation from parent %2$s, and by default for sub-%3$s.', 'scoper'), $agent_caption, $display_name, $display_name_plural);
		echo '</li>';
		
		if ( $display_restriction_key ) {
			echo '<li>';
			echo "<span class='rs-bold rs-backylw' style='border:1px solid #00a;padding-left:0.5em;padding-right:0.5em'>" . __('Role Name', 'scoper') . "</span>: ";
			echo "<span>" . sprintf(__('role is restricted for specified %s.', 'scoper'), $display_name) . "</span>";
			echo '</li>';
		}
		
		echo '</ul>';
	}
	
	function taxonomy_scroll_links($tx, $terms, $admin_terms = '') {
		if ( empty($terms) || ( is_array($admin_terms) && empty($admin_terms) ) )
			return;
		
		echo '<strong>' . __('Scroll to current settings:','scoper') . '</strong><br />';	
			
		if ( $admin_terms && ! is_array($admin_terms) )
			$admin_terms = '';
	
		$col_id = $tx->source->cols->id;
		$col_name = $tx->source->cols->name;
		$col_parent = $tx->source->cols->parent;

		$font_ems = 1.2;
		$text = '';
		$term_num = 0;

		$parent_id = 0;
		$last_id = -1;
		$last_parent_id = -1;
		$parents = array();
		$depth = 0;
		
		foreach( $terms as $term ) {
			$term_id = $term->$col_id;
			
			if ( isset($term->$col_parent) )
				$parent_id = $term->$col_parent;

			if ( ! $admin_terms || ! empty($admin_terms[$term_id]) ) {
				if ( $parent_id != $last_parent_id ) {
					if ( ($parent_id == $last_id) && $last_id ) {
						$parents[] = $last_id;
						$depth++;
					} elseif ($depth) {
						do {
							array_pop($parents);
							$depth--;
						} while ( $parents && ( end($parents) != $parent_id ) && $depth);
					}
					
					$last_parent_id = $parent_id;
				}

				//echo "term {$term->$col_name}: depth $depth, current parents: ";
				//dump($parents);
				
				if ( $term_num )
					$text .= ( $parent_id ) ? ' - ' : ' . ';
					
				if ( ! $parent_id )
					$depth = 0;
				
				$color_level_b = ($depth < 4) ? 220 - (60 * $depth) : 0;
				$hexb = dechex($color_level_b);
				if ( strlen($hexb) < 2 )
					$hexb = "0" . $hexb;
				
				$color_level_g = ($depth < 4) ? 80 + (40 * $depth) : 215;
				$hexg = dechex($color_level_g);
				
				$font_ems = ($depth < 5) ? 1.2 - (0.12 * $depth) : 0.6; 
				$text .= "<span style='font-size: {$font_ems}em;'><a class='rs-link_plain' href='#item-$term_id'><span style='color: #00{$hexg}{$hexb};'>{$term->$col_name}</span></a></span>";
			}
			
			$last_id = $term_id;
			$term_num++;
		}
		
		$text .= '<br />';
		
		return $text;
	}
	
	function common_ui_msg( $msg_id ) {
		if ( 'pagecat_plug' == $msg_id ) {
			$msg = __('Category Roles for WordPress pages are <a %s>disabled for this blog</a>. Object Roles can be assigned to individual pages, and optionally propagated to sub-pages.', 'scoper');
			echo '<li>';
			printf( $msg, 'href="admin.php?page=rs-options"');
			
			_e('Another option is to categorise pages via the <a>Page&nbsp;Category&nbsp;Plus</a>&nbsp;plugin.', 'scoper');

			echo '</li>';
		}
	}
	
	// make use of filter provided by WP 2.7
	function flt_dropdown_pages($orig_options_html) {
		global $scoper, $post_ID;
				
		//log_mem_usage_rs( 'start flt_dropdown_pages()' );

		if ( strpos( $_SERVER['SCRIPT_NAME'], 'p-admin/options-' ) )
			return $orig_options_html;

		if ( empty($post_ID) )
			$object_id = $scoper->data_sources->detect('id', 'post', 0, 'post');
		else
			$object_id = $post_ID;
		
		if ( $object_id )
			$stored_parent_id = $scoper->data_sources->detect('parent', 'post', $object_id);
		else
			$stored_parent_id = 0;
			
		//if ( is_content_administrator_rs() )	// WP 2.7 excludes private pages from Administrator's parent dropdown
		//	return $orig_options_html;

		if ( is_content_administrator_rs() ) {
			$can_associate_main = true;
			
		} elseif ( ! scoper_get_option( 'lock_top_pages' ) ) {
			global $current_user;
			$reqd_caps = array('edit_others_pages');
			$roles = $scoper->role_defs->qualify_roles($reqd_caps, '');
			
			$can_associate_main = array_intersect_key( $roles, $current_user->blog_roles[ANY_CONTENT_DATE_RS] );
		} else
			$can_associate_main = false;
		
		// Generate the filtered page parent options, but only if user can de-associate with main page, or if parent is already non-Main
		if ( $can_associate_main || ! $object_id || $stored_parent_id ) {
			$options_html = ScoperAdminUI::dropdown_pages($object_id, $stored_parent_id);
		} else {
			$options_html = '';
		}

		// User can't associate or de-associate a page with Main page unless they have edit_pages blog-wide.
		// Prepend the Main Page option if appropriate (or, to avoid submission errors, if we generated no other options)
		if ( ( strpos( $orig_options_html, __('Main Page (no parent)') ) || strpos($_SERVER['SCRIPT_NAME'], 'p-admin/page.php') || strpos($_SERVER['SCRIPT_NAME'], 'p-admin/page-new.php') ) 
		&& ( $can_associate_main || ( $object_id && ! $stored_parent_id ) || empty($options_html) ) ) {
			$current = ( $stored_parent_id ) ? '' : ' selected="selected"';
			$option_main = "\t" . '<option value=""' . $current . '> ' . __('Main Page (no parent)') . "</option>";
		} else
			$option_main = '';
		
		//return "<select name='parent_id' id='parent_id'>\n" . $option_main . $options_html . '</select>';
		
		//log_mem_usage_rs( 'end flt_dropdown_pages()' );
		
		
		// can't assume name/id for this dropdown (Quick Edit uses "post_parent")
		$mat = array();
		preg_match("/<select([^>]*)>/", $orig_options_html, $mat);
		
		// If the select tag was not passed in, don't pass it out
		if ( ! empty($mat[1]) )
			return "<select{$mat[1]}>\n" . $option_main . $options_html . '</select>';
			
		// (but if core dropdown_pages passes in a nullstring, we need to insert the missing select tag).  TODO: core patch to handle this more cleanly
		elseif ( ! $orig_options_html )
			return "<select name=\"page_id\" id=\"page_id\">\n" . $option_main . $options_html . '</select>';
		
		else
			return $option_main . $options_html;
	}
	
	function dropdown_pages($object_id = '', $stored_parent_id = '') {
		global $scoper, $wpdb;
		$args = array();

		// buffer titles in case they are filtered on get_pages hook
		$titles = ScoperAdminUI::get_page_titles();
		
		if ( ! is_numeric($object_id) ) {
			global $post_ID;
			
			if ( empty($post_ID) )
				$object_id = $scoper->data_sources->detect('id', 'post', 0, 'post');
			else
				$object_id = $post_ID;
		}
		
		if ( $object_id && ! is_numeric($stored_parent_id) )
			$stored_parent_id = $scoper->data_sources->detect('parent', 'post', $object_id);
		
		// make sure the currently stored parent page remains in dropdown regardless of current user roles
		if ( $stored_parent_id ) {
			$preserve_or_clause = " $wpdb->posts.ID = '$stored_parent_id' ";
			$args['preserve_or_clause'] = array();
			foreach (array_keys( $scoper->data_sources->member_property('post', 'statuses') ) as $status_name )
				$args['preserve_or_clause'][$status_name] = $preserve_or_clause;
		}
		
		// alternate_caps is a 2D array because objects_request / objects_where filter supports multiple alternate sets of qualifying caps
		$args['force_reqd_caps']['page'] = array();
		foreach (array_keys( $scoper->data_sources->member_property('post', 'statuses') ) as $status_name )
			$args['force_reqd_caps']['page'][$status_name] = array('edit_others_pages');
			
		$args['alternate_reqd_caps'][0] = array('create_child_pages');
		
		$all_pages_by_id = array();
		if ( $results = scoper_get_results( "SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_type = 'page'" ) )
			foreach ( $results as $row )
				$all_pages_by_id[$row->ID] = $row;

		// Editable / associable draft and pending pages will be included in Page Parent dropdown in Edit Forms, but not elsewhere
		if ( is_admin() && ( false === strpos($_SERVER['SCRIPT_NAME'], 'p-admin/page.php') ) && ( false === strpos($_SERVER['SCRIPT_NAME'], 'p-admin/page-new.php') ) )
			$status_clause = "AND $wpdb->posts.post_status IN ('publish', 'private')";
		else
			$status_clause = "AND $wpdb->posts.post_status IN ('publish', 'private', 'pending', 'draft')";

		$qry_parents = "SELECT ID, post_parent, post_title FROM $wpdb->posts WHERE post_type = 'page' $status_clause ORDER BY menu_order";
		
		$qry_parents = apply_filters('objects_request_rs', $qry_parents, 'post', 'page', $args);
		
		$filtered_pages_by_id = array();
		if ( $results = scoper_get_results($qry_parents) )
			foreach ( $results as $row )
				$filtered_pages_by_id [$row->ID] = $row;
			
		$hidden_pages_by_id = array_diff_key( $all_pages_by_id, $filtered_pages_by_id );

		// temporarily add in the hidden parents so we can order the visible pages by hierarchy
		$pages = ScoperAdminUI::add_missing_parents($filtered_pages_by_id, $hidden_pages_by_id, 'post_parent');

		// convert keys from post ID to title+ID so we can alpha sort them
		$args['pages'] = array();
		foreach ( array_keys($pages) as $id )
			$args['pages'][ $pages[$id]->post_title . chr(11) . $id ] = $pages[$id];

		// natural case alpha sort
		uksort($args['pages'], "strnatcasecmp");

		$args['pages'] = ScoperAdminUI::order_by_hierarchy($args['pages'], 'ID', 'post_parent');

		// take the hidden parents back out
		foreach ( $args['pages'] as $key => $page )
			if ( isset( $hidden_pages_by_id[$page->ID] ) )
				unset( $args['pages'][$key] );

		$output = '';
		
		// restore buffered titles in case they were filtered on get_pages hook
		scoper_restore_property_array( $args['pages'], $titles, 'ID', 'post_title' );
		
		if ( $object_id ) {
			$args['object_id'] = $object_id;
			$args['retain_page_ids'] = true; // retain static log to avoid redundant entries by subsequent call with use_parent_clause=false
			ScoperAdminUI::walk_parent_dropdown($output, $args, true, $stored_parent_id);
		}
	
		// next we'll add disjointed branches, but don't allow this page's descendants to be offered as a parent
		$arr_parent = array();
		$arr_children = array();
		
		if ( $results = scoper_get_results("SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page' $status_clause") ) {
			foreach ( $results as $row ) {
				$arr_parent[$row->ID] = $row->post_parent;
				
				if ( ! isset($arr_children[$row->post_parent]) )
					$arr_children[$row->post_parent] = array();
					
				$arr_children[$row->post_parent] []= $row->ID;
			}
			
			$descendants = array();
			if ( ! empty( $arr_children[$object_id] ) ) {
				foreach ( $arr_parent as $page_id => $parent_id ) {
					if ( ! $parent_id || ($page_id == $object_id) )
						continue;
						
					do {
						if ( $object_id == $parent_id ) {
							$descendants[$page_id] = true;
							break;
						}
						
						$parent_id = $arr_parent[$parent_id];
					} while ( $parent_id );
				}
			}
			$args['descendants'] = $descendants;
		}

		ScoperAdminUI::walk_parent_dropdown($output, $args, false, $stored_parent_id);
		
		//log_mem_usage_rs( 'end dropdown_pages()' );
		
		return $output;
	}
				
	// slightly modified transplant of WP 2.6 core parent_dropdown
	function walk_parent_dropdown( &$output, &$args, $use_parent_clause = true, $default = 0, $parent = 0, $level = 0 ) {
		static $use_class;
		static $page_ids;
		
		if ( ! isset($use_class) )
			$use_class = awp_ver('2.7');

		if ( ! isset( $page_ids ) )
			$page_ids = array();
			
		// todo: defaults, merge
		//extract($args);
		// args keys: pages, object_id
		
		$page_ids[$parent] = true;
		
		if ( ! is_array( $args['pages'] ) )
			$args['pages'] = array();

		if ( empty($args['descendants'] ) || ! is_array( $args['descendants'] ) )
			$args['descendants'] = array();

		foreach ( array_keys($args['pages']) as $key ) {
			// we call this without parent criteria to include pages whose parent is unassociable
			if ( $use_parent_clause && $args['pages'][$key]->post_parent != $parent )
				continue;
				
			$id = $args['pages'][$key]->ID;
				
			if ( in_array($id, array_keys($args['descendants']) ) )
				continue;

			if ( isset($page_ids[$id]) )
				continue;
		
			$page_ids[$id] = true;
		
			// A page cannot be its own parent.
			if ( $args['object_id'] && ( $id == $args['object_id'] ) )
				continue;

			$class = ( $use_class ) ? 'class="level-' . $level . '" ' : '';

			$current = ( $id == $default) ? ' selected="selected"' : '';
			$pad = str_repeat( '&nbsp;', $level * 3 );
			$output .= "\n\t<option " . $class . 'value="' . $id . '"' . $current . '>' . $pad . wp_specialchars($args['pages'][$key]->post_title) . '</option>';
			
			ScoperAdminUI::walk_parent_dropdown( $output, $args, true, $default, $id, $level +1 );
		}
		
		if ( ! $level && empty($args['retain_page_ids']) )
			$page_ids = array();
	}
	
	// object_array = db results 2D array
	function order_by_hierarchy($object_array, $col_id, $col_parent, $id_key = false) {
		$ordered_results = array();
		$find_parent_id = 0;
		$last_parent_id = array();
		
		do {
			$found_match = false;
			$lastcount = count($ordered_results);
			foreach ( $object_array as $key => $item )
				if ( $item->$col_parent == $find_parent_id ) {
					if ( $id_key )
						$ordered_results[$item->$col_id]= $object_array[$key];
					else
						$ordered_results[]= $object_array[$key];
					
					unset($object_array[$key]);
					$last_parent_id[] = $find_parent_id;
					$find_parent_id = $item->$col_id;
					
					$found_match = true;
					break;	
				}
			
			if ( ! $found_match ) {
				if ( ! count($last_parent_id) )
					break;
				else
					$find_parent_id = array_pop($last_parent_id);
			}
		} while ( true );
		
		return $ordered_results;
	}
	
	// listed_objects[object_id] = object, including at least the parent property
	// unlisted_objects[object_id] = object, including at least the parent property
	function add_missing_parents($listed_objects, $unlisted_objects, $col_parent) {
		$need_obj_ids = array();
		foreach ( $listed_objects as $obj )
			if ( $obj->$col_parent && ! isset($listed_objects[ $obj->$col_parent ]) )
				$need_obj_ids[$obj->$col_parent] = true;

		$last_need = '';
				
		while ( $need_obj_ids ) { // potentially query for several generations of object hierarchy (but only for parents of objects that have roles assigned)
			if ( $need_obj_ids == $last_need )
				break; //precaution

			$last_need = $need_obj_ids;

			if ( $add_objects = array_intersect_key( $unlisted_objects, $need_obj_ids) ) {
				$listed_objects = $listed_objects + $add_objects; // array_merge will not maintain numeric keys
				$unlisted_objects = array_diff_key($unlisted_objects, $add_objects);
			}
			
			$new_need = array();
			foreach ( array_keys($need_obj_ids) as $id ) {
				if ( ! empty($listed_objects[$id]->$col_parent) )  // does this object itself have a nonzero parent?
					$new_need[$listed_objects[$id]->$col_parent] = true;
			}

			$need_obj_ids = $new_need;
		}
		
		return $listed_objects;
	}
	
	function get_page_titles() {
		global $wpdb;
		
		$is_administrator = is_content_administrator_rs();
		
		if ( ! $is_administrator )
			remove_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);
		
		// don't retrieve post_content, to save memory
		$all_pages = scoper_get_results( "SELECT ID, post_parent, post_title, post_date, post_date_gmt, post_status, post_name, post_modified, post_modified_gmt, guid, menu_order, comment_count FROM $wpdb->posts WHERE post_type = 'page'" );
		
		foreach ( array_keys( $all_pages ) as $key )
			$all_pages[$key]->post_content = '';		// add an empty post_content property to each item, in case some plugin filter requires it
		
		$all_pages = apply_filters( 'get_pages', $all_pages );

		if ( ! $is_administrator )
			add_filter('get_pages', array('ScoperHardway', 'flt_get_pages'), 1, 2);

		$titles = scoper_get_property_array( $all_pages, 'ID', 'post_title' );
		
		unset( $all_pages );
	}
	
} // end class ScoperAdminUI
?>